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
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('accepted_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', [
                'pending',
                'shortlisted',
                'accepted',
                'rejected',
                'in-progress',
                'completed'
            ])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
