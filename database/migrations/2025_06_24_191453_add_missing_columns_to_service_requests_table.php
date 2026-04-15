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
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('provider_service_id')->nullable()->constrained('provider_services')->cascadeOnDelete();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->dateTime('accepted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropForeign(['provider_service_id']);
            $table->dropColumn([
                'provider_service_id',
                'starts_at',
                'completed_at',
                'cancelled_at',
                'rejected_at',
                'accepted_at',
            ]);
        });
    }
};