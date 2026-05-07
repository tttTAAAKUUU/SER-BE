<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('service_provider_profiles')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('package_type');
            $table->string('room_tier');
            $table->unsignedTinyInteger('bathroom_count')->default(1);
            $table->string('scheduling_mode'); // once_off, weekly, fortnightly
            $table->json('recurring_days'); // array of day names
            $table->date('start_date');
            $table->json('addon_config')->nullable(); // stored addon config for template
            $table->decimal('distance_km', 8, 2)->default(0);
            $table->string('service_location');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_templates');
    }
};