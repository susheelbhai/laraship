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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('selected_shipping_provider_id')
                ->nullable()
                ->after('status')
                ->constrained('shipping_providers');
            $table->decimal('shipping_cost', 10, 2)->nullable()->after('total_amount');
            $table->boolean('requires_shipping')->default(true)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['selected_shipping_provider_id']);
            $table->dropColumn(['selected_shipping_provider_id', 'shipping_cost', 'requires_shipping']);
        });
    }
};
