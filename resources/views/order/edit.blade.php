@extends('laraship::layouts.app')

@section('head')
    <title>Payment Form | Laraship</title>
@endsection

@section('content')

    <div class="container">

        <h1 class="text-center p-5"> Laraship Testing Environment </h1>
        <div class="card">
            <div class="card-header">
                Shipment Detail
            </div>
            <div class="card-body">
                <form class="row g-3 needs-validation" action="{{ route('laraship.order.edit', 8658) }}" method="post">
                    @csrf
                    <div class="col-md-6">
                        <label for="order_id" class="form-label"> Order Id</label>
                        <input type="text" class="form-control" name="order_id" id="order_id" value="{{ $data->id }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="order_date" class="form-label"> Order Date</label>
                        <input type="date" class="form-control" name="order_date" id="order_date" value="{{ $data->id }}" min="2023-07-09" required>
                    </div>
                    <div class="col-md-6">
                        <label for="redirect_url" class="form-label"> Pickup Location</label>
                        <select name="pickup_location" id="pickup_location" class="form-control">
                            <option value="{{ $data->id }}">Please select *</option>
                            @foreach ($locations->shipping_address as $i)
                                <option value="{{ $i->pickup_location }}">{{ $i->pickup_location }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="billing_address" class="form-label"> Billing Address</label>
                        <input type="text" class="form-control" name="billing_address" id="billing_address"value="{{ $data->billing_address }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="billing_address_2" class="form-label"> Billing Address 2</label>
                        <input type="text" class="form-control" name="billing_address_2" id="billing_address_2"value="{{ $data->billing_address_2 }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="billing_city" class="form-label"> Billing City</label>
                        <input type="text" class="form-control" name="billing_city" id="billing_city"value="{{ $data->billing_city }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="billing_customer_name" class="form-label"> Billing Name</label>
                        <input type="text" class="form-control" name="billing_customer_name" id="billing_customer_name"value="{{ $data->customer_name }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="billing_last_name" class="form-label"> Billing Name</label>
                        <input type="date" class="form-control" name="billing_last_name" id="billing_last_name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="billing_pincode" class="form-label"> Billing Pin Code</label>
                        <input type="number" class="form-control" name="billing_pincode" id="billing_pincode"value="{{ $data->customer_pincode }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="billing_state" class="form-label"> Billing State</label>
                        <input type="text" class="form-control" name="billing_state" id="billing_state"value="{{ $data->customer_state }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="billing_country" class="form-label"> Billing Country</label>
                        <input type="text" class="form-control" name="billing_country" id="billing_country"value="{{ $data->billing_country_name }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="billing_email" class="form-label">Billing Email</label>
                        <input type="email" class="form-control" name="billing_email" id="billing_email"value="{{ $data->customer_email }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="billing_phone" class="form-label"> Billing Phone</label>
                        <input type="number" class="form-control" name="billing_phone" id="billing_phone"value="{{ $data->customer_phone }}" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="email" class="form-label"> Email</label>
                        <input type="email" class="form-control" name="email" id="email" value="{{ $data->customer_email }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label"> Phone</label>
                        <input type="number" class="form-control" name="phone" id="phone" value="{{ $data->customer_phone }}" required>
                    </div>
                    
                    <div class="col-12 text-center">
                        <button class="btn btn-primary " type="submit">Submit form</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection
