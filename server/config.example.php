<?php
/**
 * Server configuration.
 * Copy to config.php and fill in credentials.
 */

return [
    'db' => [
        'host' => 'localhost',
        'name' => 'mangos',
        'user' => '',
        'pass' => '',
    ],

    // DigitalOcean Spaces — receipts/audios/PDFs uploaded through the AI input.
    // Same shared `wiseutils-cdn` bucket as errege/aula-de-gabi; objects are
    // uploaded with ACL=private and served back through the PHP proxy at
    // GET /api/payments/artifact?id=<payment_id>. CDN URLs return 403.
    // Create the access key in DO: bucket Settings → Access Keys → Create Access Key
    //   - Name: mangos-app
    //   - Scope: Limited Access → wiseutils-cdn
    //   - Permissions: Read + Write + Delete
    'spaces' => [
        'key'      => 'CHANGE_ME',
        'secret'   => 'CHANGE_ME',
        'region'   => 'nyc3',
        'bucket'   => 'wiseutils-cdn',
        'endpoint' => 'https://nyc3.digitaloceanspaces.com',
        'prefix'   => 'mangos',  // use 'mangos-dev' on dev
    ],
    // Web Push (VAPID). Generate a keypair once with:
    //   php -r "require 'vendor/autoload.php'; \
    //     echo json_encode(Minishlink\\WebPush\\VAPID::createVapidKeys(), JSON_PRETTY_PRINT);"
    // Public key also goes to app/includes/config.php['vapid_public_key'].
    'web_push' => [
        'vapid_public_key'  => 'CHANGE_ME',
        'vapid_private_key' => 'CHANGE_ME',
        'vapid_subject'     => 'mailto:you@example.com',
    ],
    // Shared secret for cron-only routes (X-Cron-Secret header). Droplet cron
    // file reads it from /etc/mangos/cron.secret (mode 0600, owner www-data).
    'cron_secret' => 'CHANGE_ME',
];
