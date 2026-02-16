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
        Schema::create('shipping_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('adapter_class');
            $table->text('credentials'); // encrypted
            $table->json('config')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->integer('priority')->default(0);
            $table->string('tracking_url_template')->nullable();
            $table->timestamps();

            $table->index(['is_enabled', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_providers');
    }
};
