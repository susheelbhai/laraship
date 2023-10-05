@extends('laraship::layouts.app')

@section('head')
    <title>All Locations</title>
@endsection

@section('content')
    
<div class="card text-white bg-success mb-3" style="max-width: 18rem;">
    <div class="card-header">Available Balance</div>
    <div class="card-body">
      <h5 class="card-title">Available Wallet Balance</h5>
      <p class="card-text form-control-lg"> {{$data['data']['balance']}} </p>
    </div>
  </div>
@endsection
