<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManualWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'tracking_number' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:pending,booked,picked_up,in_transit,out_for_delivery,delivered,failed,returned,cancelled'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'tracking_number' => 'tracking number',
            'occurred_at' => 'occurred at',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'The selected status is invalid. Valid statuses are: pending, booked, picked_up, in_transit, out_for_delivery, delivered, failed, returned, cancelled.',
        ];
    }
}
