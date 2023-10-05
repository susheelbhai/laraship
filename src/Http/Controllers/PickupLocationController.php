<?php

namespace Susheelbhai\Laraship\Http\Controllers;

use GuzzleHttp\Client;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Susheelbhai\Laraship\Services\Facades\Laraship;


class PickupLocationController extends Controller
{
    
    public function __construct()
    {
       
    }

    public function index()
    {
       $data = Laraship::locations();
       if ($data['success'] == 'false') {
         $locations = [];
       }
       else{
         $locations = $data['data']->shipping_address;
       }
       return view('laraship::pickup_location.index', compact('locations'));
    }

    public function create()
    {
       return view('laraship::pickup_location.create');
    }
    
    public function store(Request $request)
    {
      $data =  Laraship::createLocation($request);
      if ($data['success'] == 'true') {
        return redirect()->route('laraship.pickupLocation.index')->with('success', 'Location added successfully');
      }
      else{
        return redirect()->route('laraship.pickupLocation.index')->with('error', 'something went wrong'.$data['message']);
      }
    }
    
}
