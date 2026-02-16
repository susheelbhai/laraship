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
        Schema::table('products', function (Blueprint $table) {
            $table->integer('weight_grams')->nullable()->after('price');
            $table->integer('length_cm')->nullable()->after('weight_grams');
            $table->integer('width_cm')->nullable()->after('length_cm');
            $table->integer('height_cm')->nullable()->after('width_cm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['weight_grams', 'length_cm', 'width_cm', 'height_cm']);
        });
    }
};
