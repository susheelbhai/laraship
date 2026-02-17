<?php

use App\Models\Admin;

test('shipping provider creation fails gracefully with missing shiprocket credentials', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.shipping_provider.store'), [
            'name' => 'shiprocket',
            'display_name' => 'Shiprocket',
            'adapter_class' => 'Susheelbhai\Laraship\Adapters\ShiprocketAdapter',
            'credentials_email' => '',
            'credentials_password' => '',
            'priority' => 0,
        ]);

    $response->assertSessionHasErrors(['credentials_email', 'credentials_password']);
    $response->assertRedirect();
});

test('shipping provider creation accepts shiprocket email and password credentials', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.shipping_provider.store'), [
            'name' => 'shiprocket',
            'display_name' => 'Shiprocket',
            'adapter_class' => 'Susheelbhai\Laraship\Adapters\ShiprocketAdapter',
            'credentials_email' => 'test@example.com',
            'credentials_password' => 'test-password',
            'priority' => 0,
        ]);

    // Will fail authentication but should not throw 500 error
    $response->assertSessionHasErrors('credentials_email');
    expect($response->getStatusCode())->toBe(302);
});

test('shipping provider creation requires api key for non-shiprocket adapters', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.shipping_provider.store'), [
            'name' => 'delhivery',
            'display_name' => 'Delhivery',
            'adapter_class' => 'Susheelbhai\Laraship\Adapters\DelhiveryAdapter',
            'credentials_api_key' => '',
            'priority' => 0,
        ]);

    $response->assertSessionHasErrors('credentials_api_key');
    $response->assertRedirect();
});
