<?php

namespace Susheelbhai\Laraship\Repository\Shiprocket;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class OrderRepository
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
    public function orders()
    {
        $client = new Client();
        try {
            $request = new Request('GET', 'https://apiv2.shiprocket.in/v1/external/orders', $this->headers);
            $res = $client->sendAsync($request)->wait();
            return  $response = array('success' => 'true', 'data' => (array) json_decode($res->getBody())->data);
        } catch (\Exception $error) {
            return  $response = array('success' => 'false', 'data' => [], 'message' => $error->getMessage());
        }
    }

    public function order($id)
    {
        $client = new Client();
        try {
            $request = new Request('GET', 'https://apiv2.shiprocket.in/v1/external/orders/show/' . $id, $this->headers);
            $res = $client->sendAsync($request)->wait();
            return  $response = array('success' => 'true', 'data' => (array) json_decode($res->getBody())->data);
        } catch (\Exception $error) {
            return  $response = array('success' => 'false', 'data' => [], 'message' => $error->getMessage());
        }
    }
    public function cancelOrder($id)
    {
        $client = new Client();
        $body = '{
            "ids": [
              "' . $id . '"
            ]
          }';

        try {
            $request = new Request('POST', 'https://apiv2.shiprocket.in/v1/external/orders/cancel', $this->headers, $body);
            $res = $client->sendAsync($request)->wait();
            return  $response = array('success' => 'true', 'data' => (array) json_decode($res->getBody()));
        } catch (\Exception $error) {
            return  $response = array('success' => 'false', 'message' => $error->getMessage());
        }
    }
    public function createOrder($input)
    {
        // return $input;
        $client = new Client();

        $body = '{
          "order_id": "' . $input['order_id'] . '",
          "order_date": "' . $input['order_date'] . '",
          "pickup_location": "' . $input['pickup_location'] . '",
          "channel_id": "",
          "comment": "' . $input['comment'] . '",
          "billing_customer_name": "' . $input['billing_customer_name'] . '",
          "billing_last_name": "' . $input['billing_last_name'] . '",
          "billing_address": "' . $input['billing_address'] . '",
          "billing_address_2": "' . $input['billing_address_2'] . '",
          "billing_city": "' . $input['billing_city'] . '",
          "billing_pincode": "' . $input['billing_pincode'] . '",
          "billing_state": "' . $input['billing_state'] . '",
          "billing_country": "' . $input['billing_country'] . '",
          "billing_email": "' . $input['billing_email'] . '",
          "billing_phone": "' . $input['billing_phone'] . '",
          "shipping_is_billing": true,
          "shipping_customer_name": "",
          "shipping_last_name": "",
          "shipping_address": "",
          "shipping_address_2": "",
          "shipping_city": "",
          "shipping_pincode": "",
          "shipping_country": "",
          "shipping_state": "",
          "shipping_email": "' . $input['email'] . '",
          "shipping_phone": "' . $input['phone'] . '",
          "order_items": [
            {
              "name": "Kunai",
              "sku": "chakra123",
              "units": 10,
              "selling_price": "' . $input['amount'] . '",
              "discount": "",
              "tax": "",
              "hsn": 441122
            }
          ],
          "payment_method": "Prepaid",
          "shipping_charges": 0,
          "giftwrap_charges": 0,
          "transaction_charges": 0,
          "total_discount": 0,
          "sub_total": 9000,
          "length": 10,
          "breadth": 15,
          "height": 20,
          "weight": 2.5
        }';
        try {
            $request = new Request('POST', 'https://apiv2.shiprocket.in/v1/external/orders/create/adhoc', $this->headers, $body);
            $res = $client->sendAsync($request)->wait();
            return  $response = array('success' => 'true', 'data' => (array) json_decode($res->getBody()));
        } catch (\Exception $error) {
            return  $response = array('success' => 'false', 'message' => $error->getMessage());
        }
    }
}
