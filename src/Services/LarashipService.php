<?php


namespace Susheelbhai\Laraship\Services;

use Susheelbhai\Laraship\Repository\Shiprocket\OrderRepository;
use Susheelbhai\Laraship\Repository\Shiprocket\PickupLocationRepository;
use Susheelbhai\Laraship\Repository\Shiprocket\Shiprocket;



class LarashipService
{

    public function checkBalance()
    {
        $repo = new Shiprocket();
        return $repo->checkBalance();
    }
    public function locations()
    {
        $repo = new PickupLocationRepository();
        return $repo->locations();
    }
    public function createLocation($input)
    {
        $repo = new PickupLocationRepository();
        return $repo->createLocation($input);
    }
    public function orders()
    {
        $repo = new OrderRepository();
        return $repo->orders();
    }
    public function order($id)
    {
        $repo = new OrderRepository();
        return $repo->order($id);
    }
    public function createOrder($input)
    {
        $repo = new OrderRepository();
        return $repo->createOrder($input);
    }
    public function cancelOrder($id)
    {
        $repo = new OrderRepository();
        return $repo->cancelOrder($id);
    }
}
