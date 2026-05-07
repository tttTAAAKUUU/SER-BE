<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->json('room_tiers')->nullable()->after('duration_minutes');
            $table->unsignedTinyInteger('break_duration_minutes')->nullable()->after('room_tiers');
            $table->string('package_type')->nullable()->after('break_duration_minutes');
            $table->unsignedTinyInteger('bathroom_cap')->default(2)->after('package_type');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['room_tiers', 'break_duration_minutes', 'package_type', 'bathroom_cap']);
        });
    }
};