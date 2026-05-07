<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('washer_equipment_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_provider_profile_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Essential tier equipment
            $table->boolean('two_buckets')->default(false);
            $table->boolean('microfiber_mitts_cloths')->default(false);
            $table->boolean('ph_neutral_shampoo')->default(false);
            $table->boolean('wheel_brush')->default(false);
            $table->boolean('manual_vacuum')->default(false);
            $table->boolean('tyre_polish')->default(false);
            $table->boolean('car_air_freshener')->default(false);

            // Pro Tech tier equipment
            $table->boolean('pressure_washer')->default(false);
            $table->boolean('snow_foam_cannon')->default(false);
            $table->boolean('wet_dry_vacuum')->default(false);
            $table->boolean('da_polisher')->default(false);
            $table->boolean('steam_cleaner')->default(false);
            $table->boolean('clay_bar_kit')->default(false);
            $table->boolean('microfiber_drying_towels')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('washer_equipment_checklists');
    }
};