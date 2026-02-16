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
        Schema::create('shipping_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_provider_id')->constrained('shipping_providers');
            $table->foreignId('shipment_id')->nullable()->constrained();
            $table->text('payload');
            $table->string('signature')->nullable();
            $table->string('event_type')->nullable();
            $table->boolean('processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['shipping_provider_id', 'processed', 'created_at'], 'shipping_webhooks_provider_processed_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_webhooks');
    }
};
