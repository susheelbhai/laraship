<?php

namespace Susheelbhai\Shiprocket\Repository;

use Illuminate\Support\Facades\Http;

class Shiprocket
{

    public $token;
    public function __construct()
    {
        
    }
    public function checkBalance()
    {
        return 1;
    }

    private function authenticate() {
        $email = config('whatsapp.larapay.email');
        $password = config('whatsapp.larapay.password');
        return $response = Http::post("https://apiv2.shiprocket.in/v1/external/auth/login");
    }
    
}
