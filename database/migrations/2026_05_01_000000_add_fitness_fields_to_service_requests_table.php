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
            $table->enum('service_pillar', ['mobile', 'virtual', 'group'])->nullable();
            $table->unsignedTinyInteger('capacity')->nullable()->default(1);
            $table->enum('workout_category', ['functional', 'strength', 'reformer'])->nullable();
            $table->boolean('equipment_required')->nullable()->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn(['service_pillar', 'capacity', 'workout_category', 'equipment_required']);
        });
    }
};
