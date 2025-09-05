<?php

return [
    'credentials' => [
        'file' => env('FIREBASE_CREDENTIALS'),
        'auto_discovery' => true,
    ],

    'database' => [
        'url' => env('FIREBASE_DATABASE_URL'),
    ],

    'storage' => [
        'default_bucket' => env('FIREBASE_STORAGE_BUCKET'),
    ],

    'messaging' => [
        'default_sender' => env('FIREBASE_MESSAGING_SENDER'),
    ],

    'dynamic_links' => [
        'default_domain' => env('FIREBASE_DYNAMIC_LINKS_DOMAIN'),
    ],
];
