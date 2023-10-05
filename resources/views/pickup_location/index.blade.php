@extends('laraship::layouts.app')

@section('head')
    <title>All Locations</title>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h4>All Locations</h4>
            <a href="{{ route('laraship.pickupLocation.create') }}" class="btn btn-primary">Add new</a>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Location</th>
                        <th>Address Type</th>
                        <th>Address</th>
                        <th>city</th>
                        <th>Pin</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($locations as $i)
                    <tr>
                        <td>{{ $i->pickup_location }}</td>
                        <td>{{ $i->address_type }}</td>
                        <td>{{ $i->address }}</td>
                        <td>{{ $i->city }}</td>
                        <td>{{ $i->pin_code }}</td>
                        <td>Edit</td>
                    </tr>
                    @empty
                        
                    @endforelse

                </tbody>
            </table>
        </div>
    </div>
@endsection
