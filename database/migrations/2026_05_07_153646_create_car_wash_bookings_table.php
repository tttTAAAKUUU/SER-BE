<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_wash_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_provider_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('car_wash_package_id')->constrained();
            $table->foreignId('car_wash_car_type_id')->constrained();
            $table->enum('washer_tier', ['essential', 'pro_tech']);
            $table->dateTime('scheduled_at');
            $table->string('client_address');
            $table->decimal('total_price', 10, 2);
            $table->decimal('ser_cut', 10, 2);
            $table->decimal('washer_payout', 10, 2);
            $table->string('status')->default('pending_payment');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('dispute_window_closes_at')->nullable();
            $table->timestamps();

            $table->index(['service_provider_profile_id', 'scheduled_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_wash_bookings');
    }
};