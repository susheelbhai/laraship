<?php

use Susheelbhai\Laraship\Adapters\MockAdapter;
use Susheelbhai\Laraship\Adapters\ShiprocketAdapter;

test('mock adapter returns balance', function () {
    $adapter = new MockAdapter([]);

    $balance = $adapter->getBalance();

    expect($balance)->not->toBeNull()
        ->and($balance)->toHaveKey('balance')
        ->and($balance)->toHaveKey('currency')
        ->and($balance['balance'])->toBe(5000.00)
        ->and($balance['currency'])->toBe('INR');
});

test('mock adapter can recharge wallet', function () {
    $adapter = new MockAdapter([]);

    $result = $adapter->rechargeWallet(1000.00);

    expect($result)->not->toBeNull()
        ->and($result)->toHaveKey('transaction_id')
        ->and($result)->toHaveKey('amount')
        ->and($result)->toHaveKey('status')
        ->and($result['amount'])->toBe(1000.00)
        ->and($result['status'])->toBe('success');
});

test('mock adapter recharge includes payment options', function () {
    $adapter = new MockAdapter([]);

    $result = $adapter->rechargeWallet(2500.00, [
        'payment_method' => 'upi',
        'transaction_reference' => 'TEST123',
    ]);

    expect($result)->not->toBeNull()
        ->and($result['amount'])->toBe(2500.00)
        ->and($result)->toHaveKey('raw_response');
});

test('shiprocket adapter has recharge wallet method', function () {
    expect(method_exists(ShiprocketAdapter::class, 'rechargeWallet'))->toBeTrue();
});

test('adapters without wallet support return null', function () {
    $adapter = new class([]) extends \Susheelbhai\Laraship\Adapters\DelhiveryAdapter
    {
        public function __construct(array $credentials)
        {
            // Skip parent constructor for testing
        }
    };

    $balance = $adapter->getBalance();
    $recharge = $adapter->rechargeWallet(1000);

    expect($balance)->toBeNull()
        ->and($recharge)->toBeNull();
});
