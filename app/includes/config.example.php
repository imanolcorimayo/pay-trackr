<?php

return [
    'api_url' => 'https://mangos-api.wiseutils.com',

    'firebase' => [
        'apiKey'            => '',
        'authDomain'        => '',
        'projectId'         => '',
        'storageBucket'     => '',
        'messagingSenderId' => '',
        'appId'             => '',
    ],
    // Mirror of server/config.php['web_push']['vapid_public_key']. The SW needs
    // it in the browser to subscribe to push. Public — embedding in HTML is fine.
    'vapid_public_key' => '',
];
