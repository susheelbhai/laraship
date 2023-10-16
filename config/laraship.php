<?php


return [
    'default' => [
       'provider' => 'shiprocket',
       'middleware' => 'auth',
       'guard' => 'web',
    ],
    'shiprocket'=>[
        'email' => env('SHIPROCKET_EMAIL', ''),
        'password' => env('SHIPROCKET_PASSWORD', ''),
    ]
];