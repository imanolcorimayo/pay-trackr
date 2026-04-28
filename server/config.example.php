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
];
