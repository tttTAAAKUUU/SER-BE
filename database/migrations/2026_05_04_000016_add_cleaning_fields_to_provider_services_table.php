<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_services', function (Blueprint $table) {
            $table->decimal('transport_rate', 8, 2)->default(4.80)->after('description');
            $table->decimal('service_radius_km', 8, 2)->nullable()->after('transport_rate');
        });
    }

    public function down(): void
    {
        Schema::table('provider_services', function (Blueprint $table) {
            $table->dropColumn(['transport_rate', 'service_radius_km']);
        });
    }
};