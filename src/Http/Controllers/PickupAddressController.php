<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Susheelbhai\Laraship\Http\Requests\PickupAddressRequest;
use Susheelbhai\Laraship\Models\PickupAddress;

class PickupAddressController extends Controller
{
    public function index()
    {
        $data = PickupAddress::query()
            ->orderByDesc('is_default')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15)
            ->through(fn ($address) => [
                'id' => $address->id,
                'name' => $address->name,
                'phone' => $address->phone,
                'email' => $address->email,
                'address_line1' => $address->address_line1,
                'address_line2' => $address->address_line2,
                'city' => $address->city,
                'state' => $address->state,
                'pincode' => $address->pincode,
                'country' => $address->country,
                'full_address' => $address->full_address,
                'is_default' => $address->is_default,
                'is_active' => $address->is_active,
                'created_at' => $address->created_at,
                'updated_at' => $address->updated_at,
            ]);

        return $this->render('admin/resources/pickup_address/index', compact('data'));
    }

    public function create()
    {
        return $this->render('admin/resources/pickup_address/create');
    }

    public function store(PickupAddressRequest $request)
    {
        $data = $request->validated();

        // Ensure boolean fields have default values if not provided
        $data['is_default'] = $data['is_default'] ?? false;
        $data['is_active'] = $data['is_active'] ?? true;

        PickupAddress::create($data);

        return redirect()
            ->route('admin.pickup_address.index')
            ->with('success', 'Pickup address created successfully');
    }

    public function show(PickupAddress $address)
    {
        $data = [
            'id' => $address->id,
            'name' => $address->name,
            'phone' => $address->phone,
            'email' => $address->email,
            'address_line1' => $address->address_line1,
            'address_line2' => $address->address_line2,
            'city' => $address->city,
            'state' => $address->state,
            'pincode' => $address->pincode,
            'country' => $address->country,
            'full_address' => $address->full_address,
            'is_default' => $address->is_default,
            'is_active' => $address->is_active,
            'created_at' => $address->created_at,
            'updated_at' => $address->updated_at,
        ];

        return $this->render('admin/resources/pickup_address/show', compact('data'));
    }

    public function edit(PickupAddress $address)
    {
        $data = [
            'id' => $address->id,
            'name' => $address->name,
            'phone' => $address->phone,
            'email' => $address->email,
            'address_line1' => $address->address_line1,
            'address_line2' => $address->address_line2,
            'city' => $address->city,
            'state' => $address->state,
            'pincode' => $address->pincode,
            'country' => $address->country,
            'is_default' => $address->is_default,
            'is_active' => $address->is_active,
        ];

        return $this->render('admin/resources/pickup_address/edit', compact('data'));
    }

    public function update(PickupAddressRequest $request, PickupAddress $address)
    {
        $address->update($request->validated());

        return redirect()
            ->route('admin.pickup_address.index')
            ->with('success', 'Pickup address updated successfully');
    }

    public function destroy(PickupAddress $address)
    {
        if ($address->is_default) {
            return back()->withErrors(['error' => 'Cannot delete default address. Set another address as default first.']);
        }

        $address->delete();

        return redirect()
            ->route('admin.pickup_address.index')
            ->with('success', 'Pickup address deleted successfully');
    }

    public function toggle(PickupAddress $address)
    {
        $address->update(['is_active' => ! $address->is_active]);

        return back()->with('success', 'Address status updated successfully');
    }
}
