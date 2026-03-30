<?php
/**
 * Auth middleware.
 * Verifies Firebase ID token and returns the authenticated user's UID.
 * Auto-creates user row + default categories on first login.
 *
 * Expects $pdo to be available in scope (from config.php).
 * Sets $user_id for downstream endpoint files.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

define('FIREBASE_PROJECT_ID', 'pay-tracker-7a5a6');
define('GOOGLE_CERTS_URL', 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com');
define('CERTS_CACHE_FILE', __DIR__ . '/../.google-certs-cache.json');
define('CERTS_CACHE_TTL', 3600);

/**
 * Fetch Google's public keys (cached for 1 hour).
 */
function get_google_certs(): array {
    if (file_exists(CERTS_CACHE_FILE)) {
        $cache = json_decode(file_get_contents(CERTS_CACHE_FILE), true);
        if ($cache && $cache['expires'] > time()) {
            return $cache['keys'];
        }
    }

    $json = file_get_contents(GOOGLE_CERTS_URL);
    if ($json === false) {
        json_error('Failed to fetch Google public keys', 500);
    }

    $keys = json_decode($json, true);
    file_put_contents(CERTS_CACHE_FILE, json_encode([
        'expires' => time() + CERTS_CACHE_TTL,
        'keys' => $keys,
    ]));

    return $keys;
}

/**
 * Verify Firebase ID token from Authorization header.
 * Returns the authenticated user's Firebase UID.
 * Auto-creates user row in MySQL on first login.
 */
function require_auth(): string {
    global $pdo;

    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
        json_error('Missing or invalid Authorization header', 401);
    }

    $token = $matches[1];
    $certs = get_google_certs();

    // Build Key objects from X.509 certificates
    $keys = [];
    foreach ($certs as $kid => $cert) {
        $keys[$kid] = new Key($cert, 'RS256');
    }

    try {
        $payload = JWT::decode($token, $keys);
    } catch (\Exception $e) {
        json_error('Invalid token: ' . $e->getMessage(), 401);
    }

    // Verify Firebase-specific claims
    if (($payload->iss ?? '') !== 'https://securetoken.google.com/' . FIREBASE_PROJECT_ID) {
        json_error('Invalid token issuer', 401);
    }
    if (($payload->aud ?? '') !== FIREBASE_PROJECT_ID) {
        json_error('Invalid token audience', 401);
    }

    $uid = $payload->sub ?? '';
    if (empty($uid)) {
        json_error('Invalid token subject', 401);
    }

    // Auto-create user on first login
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$uid]);

    if (!$stmt->fetch()) {
        $email = $payload->email ?? '';
        $name = $payload->name ?? $email;
        $avatar = $payload->picture ?? null;

        $stmt = $pdo->prepare(
            "INSERT INTO users (id, email, name, avatar_url, google_id) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$uid, $email, $name, $avatar, $uid]);

        seed_categories_for_user($uid);
    }

    return $uid;
}

/**
 * Copy default categories into expense_categories for a new user.
 */
function seed_categories_for_user(string $user_id): void {
    global $pdo;

    $defaults = $pdo->query("SELECT name, color FROM default_categories")->fetchAll();
    $stmt = $pdo->prepare(
        "INSERT INTO expense_categories (id, user_id, name, color) VALUES (?, ?, ?, ?)"
    );

    foreach ($defaults as $cat) {
        $id = bin2hex(random_bytes(14));
        $stmt->execute([$id, $user_id, $cat['name'], $cat['color']]);
    }
}
