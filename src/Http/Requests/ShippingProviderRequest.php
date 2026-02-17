<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShippingProviderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert flat credentials fields to nested array
        $credentials = [];

        // Handle API key/secret (Delhivery, FedEx, DHL, etc.)
        if ($this->filled('credentials_api_key')) {
            $credentials['api_key'] = $this->credentials_api_key;
        }

        if ($this->filled('credentials_secret_key')) {
            $credentials['secret_key'] = $this->credentials_secret_key;
        }

        // Handle email/password (Shiprocket)
        if ($this->filled('credentials_email')) {
            $credentials['email'] = $this->credentials_email;
        }

        if ($this->filled('credentials_password')) {
            $credentials['password'] = $this->credentials_password;
        }

        // Handle username/password (EcomExpress)
        if ($this->filled('credentials_username')) {
            $credentials['username'] = $this->credentials_username;
        }

        // Handle license_key/login_id (Bluedart)
        if ($this->filled('credentials_license_key')) {
            $credentials['license_key'] = $this->credentials_license_key;
        }

        if ($this->filled('credentials_login_id')) {
            $credentials['login_id'] = $this->credentials_login_id;
        }

        if (! empty($credentials)) {
            $this->merge(['credentials' => $credentials]);
        }

        // Convert is_enabled string to boolean
        if ($this->has('is_enabled')) {
            $this->merge([
                'is_enabled' => filter_var($this->is_enabled, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        // Decode JSON strings to arrays if needed (for nested submission)
        if ($this->has('credentials') && is_string($this->credentials)) {
            $this->merge([
                'credentials' => json_decode($this->credentials, true) ?? [],
            ]);
        }

        if ($this->has('config') && is_string($this->config)) {
            $this->merge([
                'config' => json_decode($this->config, true) ?? [],
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'display_name' => 'required|string|max:255',
            'config' => 'nullable|array',
            'priority' => 'nullable|integer|min:0',
            'tracking_url_template' => 'nullable|string|max:500',
        ];

        // For store (POST), name and adapter_class are required
        if ($this->isMethod('post')) {
            $rules['name'] = 'required|string|max:255|unique:shipping_providers,name';
            $rules['adapter_class'] = 'required|string';
        }

        // Conditional validation based on adapter class
        $adapterClass = $this->input('adapter_class');

        if ($adapterClass && str_contains($adapterClass, 'ShiprocketAdapter')) {
            // Shiprocket requires email and password
            $rules['credentials_email'] = 'required|email';
            $rules['credentials_password'] = 'required|string';
        } elseif ($adapterClass && str_contains($adapterClass, 'EcomExpressAdapter')) {
            // EcomExpress requires username and password
            $rules['credentials_username'] = 'required|string';
            $rules['credentials_password'] = 'required|string';
        } elseif ($adapterClass && str_contains($adapterClass, 'BluedartAdapter')) {
            // Bluedart requires license_key and login_id
            $rules['credentials_license_key'] = 'required|string';
            $rules['credentials_login_id'] = 'required|string';
        } elseif ($adapterClass && (str_contains($adapterClass, 'FedexAdapter') || str_contains($adapterClass, 'DhlAdapter'))) {
            // FedEx and DHL require api_key and secret_key
            $rules['credentials_api_key'] = 'required|string';
            $rules['credentials_secret_key'] = 'required|string';
        } else {
            // Default: Most adapters require just api_key
            $rules['credentials_api_key'] = 'required|string';
        }

        return $rules;
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Provider name is required.',
            'name.unique' => 'A provider with this name already exists.',
            'display_name.required' => 'Display name is required.',
            'adapter_class.required' => 'Please select an adapter class.',
            'credentials_api_key.required' => 'API Key is required.',
            'credentials_secret_key.required' => 'Secret Key is required.',
            'credentials_email.required' => 'Email is required.',
            'credentials_email.email' => 'Please provide a valid email address.',
            'credentials_password.required' => 'Password is required.',
            'credentials_username.required' => 'Username is required.',
            'credentials_license_key.required' => 'License Key is required.',
            'credentials_login_id.required' => 'Login ID is required.',
            'priority.integer' => 'Priority must be a number.',
            'priority.min' => 'Priority cannot be negative.',
        ];
    }
}
