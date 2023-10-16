<?php

namespace Susheelbhai\Laraship\Repository\Shiprocket;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Exception\BadResponseException;
use Susheelbhai\Laraship\Repository\Shiprocket\Shiprocket;

class PickupLocationRepository
{

    public $token;
    public $headers;
    public function __construct()
    {
        $repo = new Shiprocket();
        $response = $repo->authenticate();
        if ($response['success'] == 'true') {
            $this->token = $response['token'];
        } else {
            $this->token = '';
        }
        $this->headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer $this->token"
        ];
    }
    public function locations()
    {
        $client = new Client();
        try {
            $request = new Request('GET', 'https://apiv2.shiprocket.in/v1/external/settings/company/pickup', $this->headers);
            $res = $client->sendAsync($request)->wait();
            return  $response = array('success'=> 'true', 'data'=> json_decode($res->getBody())->data);
        } catch (\Exception $error) {
            return  $response = array('success'=> 'false', 'message'=> $error->getMessage());
        }
    }
    public function createLocation($input)
    {
        $client = new Client();
             $body = '{
            "pickup_location": "' . $input['pickup_location'] . '",
            "name": "' . $input['name'] . '",
            "email": "' . $input['email'] . '",
            "phone": "' . $input['phone'] . '",
            "address": "' . $input['address'] . '",
            "address_2": "' . $input['address_2'] . '",
            "city": "' . $input['city'] . '",
            "state": "' . $input['state'] . '",
            "country": "' . $input['country'] . '",
            "pin_code": "' . $input['pin_code'] . '"
          }';

        try {
            $request = new Request('POST', 'https://apiv2.shiprocket.in/v1/external/settings/company/addpickup', $this->headers, $body);
            $res = $client->sendAsync($request)->wait();
            return  $response = array('success'=> 'true', 'data'=> json_decode($res->getBody())->data);
        } catch (\Exception $error) {
            return  $response = array('success'=> 'false', 'message'=> $error->getMessage());
        }
    }
}
