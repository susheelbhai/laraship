<?php


return [
    'default' => [
       'provider' => 'shiprocket',
       'middleware' => 'auth_admin',
       'guard' => 'admin',
    ],
    'shiprocket'=>[
        'email' => env('SHIPROCKET_EMAIL', ''),
        'password' => env('SHIPROCKET_PASSWORD', ''),
    ]
];