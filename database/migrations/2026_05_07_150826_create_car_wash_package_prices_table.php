<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_wash_package_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_wash_package_id')->constrained()->onDelete('cascade');
            $table->foreignId('car_wash_car_type_id')->constrained()->onDelete('cascade');
            $table->enum('washer_tier', ['essential', 'pro_tech']);
            $table->decimal('min_price', 10, 2);
            $table->decimal('max_price', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['car_wash_package_id', 'car_wash_car_type_id', 'washer_tier'], 'package_tier_car_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_wash_package_prices');
    }
};