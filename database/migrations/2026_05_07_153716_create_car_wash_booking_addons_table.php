<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_wash_booking_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_wash_booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('car_wash_addon_id')->constrained();
            $table->foreignId('car_wash_car_type_id')->constrained();
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_wash_booking_addons');
    }
};