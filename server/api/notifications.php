<?php
// $pdo, $user_id (when present), $notif_action provided by index.php.
//
// Routes:
//   GET  /api/notifications/prefs           — return current user's prefs
//   PUT  /api/notifications/prefs           — toggle master_off / per-kind toggles
//   POST /api/notifications/subscribe       — store a push subscription
//   POST /api/notifications/unsubscribe     — drop a push subscription by endpoint
//   POST /api/notifications/test-push       — send a "Hola desde Mangos" push to the caller
//
// Cron-only routes (no $user_id, secret-gated) are dispatched separately and
// will be added in PR2.

require_once __DIR__ . '/../vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

function notif_get_prefs(PDO $pdo, string $user_id): array {
    $stmt = $pdo->prepare("SELECT * FROM notification_pref WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    if (!$row) {
        // Lazy-create with defaults so the UI always has something to render.
        $pdo->prepare("INSERT INTO notification_pref (user_id) VALUES (?)")->execute([$user_id]);
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();
    }
    return [
        'master_off'             => (bool)$row['master_off'],
        'daily_enabled'          => (bool)$row['daily_enabled'],
        'weekly_enabled'         => (bool)$row['weekly_enabled'],
        'anomaly_alerts_enabled' => (bool)$row['anomaly_alerts_enabled'],
    ];
}

function handle_notif_prefs(PDO $pdo, string $user_id): void {
    if (method() === 'GET') {
        json_response(notif_get_prefs($pdo, $user_id));
    }
    if (method() === 'PUT') {
        $body = get_json_body();
        notif_get_prefs($pdo, $user_id); // ensure row exists
        $fields = ['master_off', 'daily_enabled', 'weekly_enabled', 'anomaly_alerts_enabled'];
        $sets = []; $params = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $body)) {
                $sets[] = "$f = ?";
                $params[] = $body[$f] ? 1 : 0;
            }
        }
        if (!empty($sets)) {
            $params[] = $user_id;
            $pdo->prepare("UPDATE notification_pref SET " . implode(', ', $sets) . " WHERE user_id = ?")
                ->execute($params);
        }
        json_response(notif_get_prefs($pdo, $user_id));
    }
    json_error('Method not allowed', 405);
}

function handle_notif_subscribe(PDO $pdo, string $user_id): void {
    if (method() !== 'POST') json_error('Method not allowed', 405);
    $body = get_json_body();
    $endpoint = $body['endpoint'] ?? '';
    $p256dh = $body['keys']['p256dh'] ?? '';
    $auth = $body['keys']['auth'] ?? '';
    if (!$endpoint || !$p256dh || !$auth) {
        json_error('endpoint, keys.p256dh, keys.auth required');
    }
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $id = bin2hex(random_bytes(14));
    // Upsert by endpoint — re-subscribing the same device updates timestamps
    // instead of accumulating dead rows. user_id reassigns in case the device
    // was previously linked to another account.
    $pdo->prepare(
        "INSERT INTO push_subscription (id, user_id, endpoint, p256dh, auth, user_agent)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            user_id = VALUES(user_id),
            p256dh = VALUES(p256dh),
            auth = VALUES(auth),
            user_agent = VALUES(user_agent),
            last_used_ts = CURRENT_TIMESTAMP"
    )->execute([$id, $user_id, $endpoint, $p256dh, $auth, $ua]);
    json_response(['subscribed' => true]);
}

function handle_notif_unsubscribe(PDO $pdo, string $user_id): void {
    if (method() !== 'POST') json_error('Method not allowed', 405);
    $body = get_json_body();
    $endpoint = $body['endpoint'] ?? '';
    if (!$endpoint) json_error('endpoint required');
    $pdo->prepare("DELETE FROM push_subscription WHERE user_id = ? AND endpoint = ?")
        ->execute([$user_id, $endpoint]);
    json_response(['unsubscribed' => true]);
}

function handle_notif_test_push(PDO $pdo, string $user_id): void {
    if (method() !== 'POST') json_error('Method not allowed', 405);

    $stmt = $pdo->prepare("SELECT endpoint, p256dh, auth FROM push_subscription WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $subs = $stmt->fetchAll();
    if (empty($subs)) {
        json_error('No hay dispositivos suscriptos. Activá las notificaciones primero.', 400);
    }

    $delivered = notif_send($pdo, $user_id, $subs, [
        'title' => 'Hola desde Mangos',
        'body'  => 'Las notificaciones funcionan ✅',
        'url'   => '/',
    ]);

    notif_log($pdo, $user_id, 'test', null);
    json_response(['sent' => $delivered]);
}

/**
 * Send a push payload to a list of subscriptions. Returns count delivered.
 * Removes subscriptions the push service reports as gone (404/410).
 */
function notif_send(PDO $pdo, string $user_id, array $subs, array $payload): int {
    $conf = $GLOBALS['mangos_config']['web_push'] ?? null;
    if (!$conf || empty($conf['vapid_public_key']) || empty($conf['vapid_private_key'])) {
        json_error('Web push no configurado', 503);
    }

    $webPush = new WebPush([
        'VAPID' => [
            'subject'    => $conf['vapid_subject'],
            'publicKey'  => $conf['vapid_public_key'],
            'privateKey' => $conf['vapid_private_key'],
        ],
    ]);

    foreach ($subs as $s) {
        $webPush->queueNotification(
            Subscription::create([
                'endpoint'        => $s['endpoint'],
                'publicKey'       => $s['p256dh'],
                'authToken'       => $s['auth'],
                'contentEncoding' => 'aesgcm',
            ]),
            json_encode($payload, JSON_UNESCAPED_UNICODE)
        );
    }

    $delivered = 0;
    foreach ($webPush->flush() as $report) {
        if ($report->isSuccess()) {
            $delivered++;
            continue;
        }
        // 404/410 = subscription is gone (user revoked / cleared browser data).
        // Drop it so we don't keep retrying.
        if ($report->isSubscriptionExpired()) {
            $pdo->prepare("DELETE FROM push_subscription WHERE endpoint = ?")
                ->execute([$report->getRequest()->getUri()->__toString()]);
        } else {
            error_log('[notifications] push failed for ' . $report->getRequest()->getUri() . ': ' . $report->getReason());
        }
    }
    return $delivered;
}

function notif_log(PDO $pdo, string $user_id, string $kind, ?string $ref_id): void {
    $id = bin2hex(random_bytes(14));
    $pdo->prepare("INSERT INTO notification_log (id, user_id, kind, ref_id) VALUES (?, ?, ?, ?)")
        ->execute([$id, $user_id, $kind, $ref_id]);
}

// ── Dispatch ────────────────────────────────────────────────────────────────
switch ($notif_action) {
    case 'prefs':       handle_notif_prefs($pdo, $user_id); break;
    case 'subscribe':   handle_notif_subscribe($pdo, $user_id); break;
    case 'unsubscribe': handle_notif_unsubscribe($pdo, $user_id); break;
    case 'test-push':   handle_notif_test_push($pdo, $user_id); break;
    default:            json_error('Unknown notification action', 404);
}
