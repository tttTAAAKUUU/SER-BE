<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_provider_profiles', function (Blueprint $table) {
            $table->enum('washer_tier', ['essential', 'pro_tech'])->nullable()->after('bio');
            $table->timestamp('washer_tier_approved_at')->nullable()->after('washer_tier');
            $table->boolean('washer_equipment_verified')->default(false)->after('washer_tier_approved_at');
            $table->timestamp('washer_next_verification_at')->nullable()->after('washer_equipment_verified');
        });
    }

    public function down(): void
    {
        Schema::table('service_provider_profiles', function (Blueprint $table) {
            $table->dropColumn(['washer_tier', 'washer_tier_approved_at', 'washer_equipment_verified', 'washer_next_verification_at']);
        });
    }
};