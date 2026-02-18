<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_provider_pickup_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_provider_id')->constrained('shipping_providers')->cascadeOnDelete();
            $table->foreignId('pickup_address_id')->constrained('pickup_addresses')->cascadeOnDelete();
            $table->string('provider_address_id')->comment('Unique identifier from provider API');
            $table->timestamps();

            $table->unique(['shipping_provider_id', 'pickup_address_id'], 'provider_pickup_unique');
            $table->index('provider_address_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_provider_pickup_addresses');
    }
};
