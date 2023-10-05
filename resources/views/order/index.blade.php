@extends('laraship::layouts.app')

@section('head')
    <title>All Orders</title>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h4>All Order</h4>
            <a href="{{ route('laraship.order.create') }}" class="btn btn-primary">Add new</a>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order Id</th>
                        <th>Order date</th>
                        <th>Location</th>
                        <th>Address</th>
                        <th>Status</th>
                        <th>Edit</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $i)
                        <tr>
                            <td>{{ $i->id ?? '' }}</td>
                            <td>{{ $i->created_at ?? '' }}</td>
                            <td>{{ $i->pickup_location ?? '' }}</td>
                            <td>{{ $i->customer_address ?? '' }}</td>
                            <td>{{ $i->status ?? '' }}</td>
                            <td> <a class="btn btn-primary" href="{{ route('laraship.order.edit', $i->id) }}">Edit</a> </td>
                            <td>
                                <form action="{{ route('laraship.order.destroy', $i->id) }}" method="post">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-danger" type="submit">Cancel</button>
                                </form>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="7">
                                @if ($success == 'true')
                                    No Data Found
                                @else
                                    Something went wrong
                                @endif
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>
    </div>
@endsection
