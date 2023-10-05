@extends('laraship::layouts.app')

@section('head')
    <title>Pickup Location | Laraship</title>
@endsection

@section('content')

    <div class="container">

        <h1 class="text-center p-5"> Add Pickup Location </h1>
        <div class="card">
            <div class="card-header">
                Add Pickup Location
            </div>
            <div class="card-body">
                <form class="row g-3 needs-validation" action="{{ route('laraship.pickupLocation.store') }}" method="post">
                    @csrf
                    <div class="col-md-6">
                        <label for="pickup_location" class="form-label"> Pickup Location</label>
                        <input type="text" class="form-control" name="pickup_location" id="pickup_location" value="" required>
                    </div>
                    <div class="col-md-6">
                        <label for="name" class="form-label"> Location Name</label>
                        <input type="text" class="form-control" name="name" id="name" required>
                    </div>
                     
                    <div class="col-md-6">
                        <label for="email" class="form-label"> Email</label>
                        <input type="email" class="form-control" name="email" id="email" required>
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label"> Phone</label>
                        <input type="number" class="form-control" name="phone" id="phone" min="6666666666" max="9999999999" required>
                    </div>
                    <div class="col-md-6">
                        <label for="address" class="form-label"> Address</label>
                        <input type="text" class="form-control" name="address" id="address" value="" required>
                    </div>
                    <div class="col-md-6">
                        <label for="address_2" class="form-label"> Address 2</label>
                        <input type="text" class="form-control" name="address_2" id="address_2" value="" required>
                    </div>
                    <div class="col-md-6">
                        <label for="city" class="form-label">  City</label>
                        <input type="text" class="form-control" name="city" id="city" value="" required>
                    </div>
                    <div class="col-md-6">
                        <label for="state" class="form-label"> state</label>
                        <input type="text" class="form-control" name="state" id="state" value="" required>
                    </div>
                    <div class="col-md-6">
                        <label for="pin_code" class="form-label"> Pin Code</label>
                        <input type="number" class="form-control" name="pin_code" id="pin_code" min="111111" max="999999" value="" required>
                    </div>
                    <div class="col-md-6">
                        <label for="country" class="form-label"> country</label>
                        <input type="text" class="form-control" name="country" id="country" value="" required>
                    </div>
                    <div class="col-12 text-center">
                        <button class="btn btn-primary " type="submit">Submit form</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection
