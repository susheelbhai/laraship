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
        Schema::create('booking_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->foreignId('shipping_provider_id')->constrained('shipping_providers');
            $table->boolean('success')->default(false);
            $table->text('error_message')->nullable();
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
            $table->index(['shipping_provider_id', 'success']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_attempts');
    }
};
