<?php

namespace Susheelbhai\Laraship\Repository\Shiprocket;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Http;

class Shiprocket
{

    public $token;
    public $headers;
    public function __construct()
    {
        $this->authenticate();
        $this->headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer $this->token"
        ];
    }

    public function checkBalance()
    {
        $client = new Client();
        // return 3;
        try {
            $request = new Request('GET', 'https://apiv2.shiprocket.in/v1/external/account/details/wallet-balance', $this->headers);
            $res = $client->sendAsync($request)->wait();
            // dd(json_decode($res->getBody())->data);
            $balance =  '₹ '. number_format(json_decode($res->getBody())->data->balance_amount,2) ;
            return  $response = array('success'=> 'true', 'balance'=> $balance);
        } catch (\Exception $error) {
            return  $response = array('success'=> 'false', 'message'=> $error->getMessage());
        } catch (BadResponseException $error) {
            return  $response = array('success'=> 'false', 'message'=> $error->getMessage());
        }
        
    }





    public function authenticate()
    {
        $email = config('laraship.shiprocket.email');
        $password = config('laraship.shiprocket.password');
        $client = new Client();
        $headers = [
            'Content-Type' => 'application/json'
        ];
        $body = '{
                    "email": "' . $email . '",
                    "password": "' . $password . '"
                }';

        try {
            $request = new Request('POST', 'https://apiv2.shiprocket.in/v1/external/auth/login', $headers, $body);
             $res = $client->sendAsync($request)->wait()->getBody();
             $this->token = json_decode($res)->token;
            return  $response = array('success'=> 'true', 'token'=> $this->token);
        } catch (\Exception $error) {
            return  $response = array('success'=> 'false', 'message'=> $error->getMessage());
        } catch (BadResponseException $error) {
            return  $response = array('success'=> 'false', 'message'=> $error->getMessage());
        }
    }
}
