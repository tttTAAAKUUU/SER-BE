<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('washer_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_provider_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('car_wash_addon_id')->constrained()->onDelete('cascade');
            $table->foreignId('car_wash_car_type_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->unique(['service_provider_profile_id', 'car_wash_addon_id', 'car_wash_car_type_id'], 'washer_addon_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('washer_addons');
    }
};