<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->foreignId('shipping_provider_id')->constrained('shipping_providers');
            $table->string('shipping_provider')->nullable(); // Provider name for quick reference
            $table->string('tracking_number')->unique();
            $table->string('awb_code')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('booked_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('label_url')->nullable();
            $table->json('booking_request')->nullable();
            $table->json('booking_response')->nullable();
            $table->decimal('shipping_cost', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['order_id', 'status']);
            $table->index('tracking_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
