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
                <table class="table">
                    <tbody>
                        <tr>
                            <td> Order Id</td>
                            <td> {{ $data['id'] ?? '' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
