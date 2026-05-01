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

/**
 * CRON · daily fijo reminders.
 *
 * For each user with master_off=false AND daily_enabled=true, query their
 * recurrents that are due in the next 3 days OR overdue this month and not
 * yet paid, then bundle them into a single push. Dedups via notification_log
 * keyed by (user, daily-fijos, YYYY-MM-DD) — once per day per user max,
 * regardless of how often the cron fires.
 */
function handle_notif_cron_daily(PDO $pdo): void {
    if (method() !== 'POST') json_error('Method not allowed', 405);

    $tz = new DateTimeZone('America/Argentina/Buenos_Aires');
    $now = new DateTime('now', $tz);
    $today = $now->format('Y-m-d');
    $year = (int)$now->format('Y');
    $mon  = (int)$now->format('m');
    $day  = (int)$now->format('d');
    $last_day = (int)$now->format('t');

    // Eligible users: master on + daily kind on. We left-join push_subscription
    // and skip users with no devices (nothing to push to anyway).
    $stmt = $pdo->prepare(
        "SELECT DISTINCT p.user_id
         FROM notification_pref p
         JOIN push_subscription s ON s.user_id = p.user_id
         WHERE p.master_off = 0 AND p.daily_enabled = 1"
    );
    $stmt->execute();
    $user_ids = array_column($stmt->fetchAll(), 'user_id');

    $report = ['users' => 0, 'sent' => 0, 'skipped_dedup' => 0, 'skipped_empty' => 0];

    foreach ($user_ids as $uid) {
        // Dedup: one daily-fijos push per user per day, no matter how often
        // the cron runs.
        $check = $pdo->prepare(
            "SELECT 1 FROM notification_log
             WHERE user_id = ? AND kind = 'daily-fijos' AND ref_id = ? LIMIT 1"
        );
        $check->execute([$uid, $today]);
        if ($check->fetchColumn()) { $report['skipped_dedup']++; continue; }

        // Build the list of due-soon + overdue fijos for this user. Compare
        // against transactions in the current month to detect "already paid".
        $window_end_day = min($day + 3, $last_day);
        $stmt = $pdo->prepare(
            "SELECT r.id, r.title, ABS(r.amount) AS amount, r.due_date_day
             FROM recurrent r
             WHERE r.user_id = ?
               AND (r.end_date IS NULL OR r.end_date >= ?)
               AND r.due_date_day BETWEEN 1 AND ?
               AND NOT EXISTS (
                   SELECT 1 FROM `transaction` t
                    WHERE t.recurrent_id = r.id
                      AND t.is_paid = 1
                      AND DATE_FORMAT(t.due_ts, '%Y-%m') = ?
               )"
        );
        $stmt->execute([$uid, $today, $window_end_day, sprintf('%04d-%02d', $year, $mon)]);
        $fijos = $stmt->fetchAll();

        if (empty($fijos)) { $report['skipped_empty']++; continue; }

        // Split into overdue (day < today) and due-soon.
        $overdue = []; $due_soon = []; $total = 0;
        foreach ($fijos as $f) {
            $f['amount'] = (float)$f['amount'];
            $total += $f['amount'];
            if ((int)$f['due_date_day'] < $day) $overdue[] = $f;
            else                                $due_soon[] = $f;
        }

        $payload = build_daily_payload($overdue, $due_soon, $total);
        if (!$payload) { $report['skipped_empty']++; continue; }

        $sub_stmt = $pdo->prepare("SELECT endpoint, p256dh, auth FROM push_subscription WHERE user_id = ?");
        $sub_stmt->execute([$uid]);
        $subs = $sub_stmt->fetchAll();

        $delivered = notif_send($pdo, $uid, $subs, $payload);
        if ($delivered > 0) {
            notif_log($pdo, $uid, 'daily-fijos', $today);
            $report['sent']++;
        }
        $report['users']++;
    }

    json_response($report);
}

/**
 * Build the {title, body, url} payload for a daily fijo push. Bundling rule:
 * one push per user, listing overdue + due-soon items together. Returns null
 * if there's nothing meaningful to say.
 */
function build_daily_payload(array $overdue, array $due_soon, float $total): ?array {
    $count = count($overdue) + count($due_soon);
    if ($count === 0) return null;

    $title = $count === 1
        ? ($overdue[0]['title'] ?? $due_soon[0]['title'])
        : "$count fijos por revisar";

    $parts = [];
    if (!empty($overdue)) {
        $parts[] = count($overdue) . ' vencido' . (count($overdue) > 1 ? 's' : '');
    }
    if (!empty($due_soon)) {
        $parts[] = count($due_soon) . ' por vencer';
    }
    $parts[] = '$ ' . number_format($total, 0, ',', '.');
    $body = implode(' · ', $parts);

    return ['title' => $title, 'body' => $body, 'url' => '/fijos'];
}

/**
 * CRON · always-fires test push.
 *
 * Hits every subscribed device with a "cron alive" notification regardless of
 * fijo state. Useful while wiring the droplet cron — proves the pipe is alive
 * end-to-end without needing real qualifying data. Skips the master_off / kind
 * toggles intentionally: if you've subscribed a device, you'll get this.
 *
 * REMOVE FROM /etc/cron.d/mangos before going live.
 */
function handle_notif_cron_test(PDO $pdo): void {
    if (method() !== 'POST') json_error('Method not allowed', 405);

    $stmt = $pdo->prepare("SELECT user_id, endpoint, p256dh, auth FROM push_subscription");
    $stmt->execute();
    $rows = $stmt->fetchAll();

    // Group by user so notif_send can attribute logs/cleanup correctly.
    $by_user = [];
    foreach ($rows as $r) {
        $by_user[$r['user_id']][] = $r;
    }

    $delivered = 0;
    foreach ($by_user as $uid => $subs) {
        $delivered += notif_send($pdo, $uid, $subs, [
            'title' => 'Cron alive',
            'body'  => 'El cron disparó — ' . date('H:i:s'),
            'url'   => '/notificaciones',
        ]);
        notif_log($pdo, $uid, 'cron-test', date('Y-m-d H:i'));
    }

    json_response(['users' => count($by_user), 'sent' => $delivered]);
}

// ── Dispatch ────────────────────────────────────────────────────────────────
switch ($notif_action) {
    case 'prefs':       handle_notif_prefs($pdo, $user_id); break;
    case 'subscribe':   handle_notif_subscribe($pdo, $user_id); break;
    case 'unsubscribe': handle_notif_unsubscribe($pdo, $user_id); break;
    case 'test-push':   handle_notif_test_push($pdo, $user_id); break;
    case 'cron-daily':  handle_notif_cron_daily($pdo); break;
    case 'cron-test':   handle_notif_cron_test($pdo); break;
    default:            json_error('Unknown notification action', 404);
}
