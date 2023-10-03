<?php


namespace Susheelbhai\Laraship\Services;

use Susheelbhai\Shiprocket\Repository\Shiprocket;


class LarashipService
{

    public function checkBalance()
    {
        $repo = new Shiprocket();
        return $repo->checkBalance();
    }
}
