<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('package_type')->nullable()->after('service_location');
            $table->string('room_tier')->nullable()->after('package_type');
            $table->unsignedTinyInteger('bathroom_count')->default(1)->after('room_tier');
            $table->string('scheduling_mode')->nullable()->after('bathroom_count');
            $table->json('recurring_days')->nullable()->after('scheduling_mode');
            $table->date('start_date')->nullable()->after('recurring_days');
            $table->integer('projected_duration_minutes')->nullable()->after('start_date');
            $table->integer('break_minutes')->nullable()->after('projected_duration_minutes');
            $table->decimal('distance_km', 8, 2)->nullable()->after('break_minutes');
            $table->decimal('transport_deposit', 10, 2)->nullable()->after('distance_km');
            $table->decimal('subtotal', 10, 2)->nullable()->after('transport_deposit');
            $table->decimal('total', 10, 2)->nullable()->after('subtotal');
            $table->decimal('ser_commission', 10, 2)->nullable()->after('total');
            $table->decimal('cleaner_payout', 10, 2)->nullable()->after('ser_commission');
            $table->string('status')->default('pending_payment')->after('cleaner_payout');
            $table->timestamp('sign_off_at')->nullable()->after('status');
            $table->timestamp('no_show_grace_started_at')->nullable()->after('sign_off_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'package_type', 'room_tier', 'bathroom_count', 'scheduling_mode',
                'recurring_days', 'start_date', 'projected_duration_minutes',
                'break_minutes', 'distance_km', 'transport_deposit',
                'subtotal', 'total', 'ser_commission', 'cleaner_payout',
                'status', 'sign_off_at', 'no_show_grace_started_at',
            ]);
        });
    }
};