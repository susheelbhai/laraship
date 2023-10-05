<?php

namespace Susheelbhai\Laraship\Http\Controllers;

use App\Http\Controllers\Controller;
use Susheelbhai\Laraship\Services\Facades\Laraship;

class HomeController extends Controller
{
    
    public function __construct()
    {
       
    }

    public function checkBalance()
    {
       $data = Laraship::checkBalance();
       if($data['success'] == 'true'){
        $balance = $data['balance'];
       }
       else{
        $balance = $data['message'];
       }
        return view('laraship::index', compact('balance'));

    }
    
}
