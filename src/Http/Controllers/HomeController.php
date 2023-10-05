<?php

namespace Susheelbhai\Laraship\Http\Controllers;

use App\Http\Controllers\Controller;
use Laraship;

class HomeController extends Controller
{
    public function checkBalance()
    {
        $data = Laraship::checkBalance();
        return view('laraship::index', compact('data'));
    }
}
