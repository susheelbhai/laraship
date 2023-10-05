<?php

namespace Susheelbhai\Laraship\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Laraship;

class OrderController extends Controller
{

  public function index()
  {
    $data = Laraship::orders();
    return view('laraship::order.index', compact('data'));
  }

  public function show($id)
  {
    $data = Laraship::order($id)['data'];
    return view('laraship::order.show', compact('data'));
  }

  public function create()
  {
    $locations = Laraship::locations();
    $locations = $locations['data']['shipping_address'];
    return view('laraship::order.create', compact('locations'));
  }

  public function store(Request $req)
  {
    $input = $req->input();
    $data =  Laraship::createOrder($input);
    if ($data['success'] == 'true') {
      return redirect()->route('laraship.order.index')->with('success', 'Order created successfully');
    } else {
      return redirect()->route('laraship.order.index')->with('error', 'something went wrong' . $data['message']);
    }
  }

  public function edit($id)
  {
    $locations = Laraship::locations();
    $locations = $locations['data']['shipping_address'];
    $data = Laraship::order($id)['data'];
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
    } else {
      return redirect()->route('laraship.order.index')->with('error', 'something went wrong' . $data['message']);
    }
  }
}
