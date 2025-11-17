<?php
// config/cors.php

return [
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'storage/*', // Untuk gambar
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000', // <-- Ganti ke port tetap Anda
        'http://127.0.0.1:3000', // <-- Ganti ke port tetap Anda
    ],
    

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // <-- Pastikan ini tetap true
];