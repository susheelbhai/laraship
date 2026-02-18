<?php

namespace Susheelbhai\Laraship\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PickupAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Convert boolean strings to actual booleans
        if ($this->has('is_default')) {
            $this->merge([
                'is_default' => filter_var($this->is_default, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'pincode' => ['required', 'string', 'max:10'],
            'country' => ['required', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Contact name is required',
            'phone.required' => 'Phone number is required',
            'address_line1.required' => 'Address line 1 is required',
            'city.required' => 'City is required',
            'state.required' => 'State is required',
            'pincode.required' => 'Pincode is required',
            'country.required' => 'Country is required',
        ];
    }
}
