<?php


return [
    'default_provider' => 'shiprocket',
    'shiprocket'=>[
        'email' => env('SHIPROCKET_EMAIL', ''),
        'password' => env('SHIPROCKET_PASSWORD', ''),
    ]
];