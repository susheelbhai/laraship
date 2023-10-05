<?php

namespace Susheelbhai\Laraship\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Susheelbhai\Laraship\Services\Facades\Laraship;

class OrderController extends Controller
{
    
    public function __construct()
    {
       
    }

    public function index()
    {
       $data = Laraship::orders();
       if ($data['success'] == 'false') {
         $data = [];
         $success = 'false';
       }
       else{
         $data = $data['data'];
         $success = 'true';
       }
       return view('laraship::order.index', compact('data', 'success'));
    }

    public function create()
    {
       $locations = Laraship::locations();
       if ($locations['success'] == 'false') {
        $locations = [];
       }
       else{
        $locations = $locations['data']->shipping_address;
       }
       return view('laraship::order.create', compact('locations'));
    }
    
    public function store(Request $req)
    {
      $input = $req->input();
      $data =  Laraship::createOrder($input);
      if ($data['success'] == 'true') {
        return redirect()->route('laraship.order.index')->with('success', 'Order created successfully');
      }
      else{
        return redirect()->route('laraship.order.index')->with('error', 'something went wrong'.$data['message']);
      }
    }

    public function edit($id)
    {
      $locations = Laraship::locations();
      $data = Laraship::order($id);
       return view('laraship::order.edit', compact('data', 'locations'));
    }
    
    public function update(Request $req)
    {
      $input = $req->input();
      return  Laraship::createOrder($input);
    }
    
    public function destroy($id)
    {
      $data =  Laraship::cancelOrder($id);
      if ($data['success'] == 'true') {
        return redirect()->route('laraship.order.index')->with('success', 'Order cancelled successfully');
      }
      else{
        return redirect()->route('laraship.order.index')->with('error', 'something went wrong'.$data['message']);
      }
    }
    
}
