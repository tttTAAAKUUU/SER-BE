<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_provider_service_category', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_provider_profile_id');
            $table->unsignedBigInteger('service_category_id');
            $table->timestamps();

            $table->foreign('service_provider_profile_id', 'sp_spc_spp_fk')
                ->references('id')->on('service_provider_profiles')
                ->cascadeOnDelete();
            $table->foreign('service_category_id', 'sp_spc_sc_fk')
                ->references('id')->on('service_categories')
                ->cascadeOnDelete();

            $table->unique(['service_provider_profile_id', 'service_category_id'], 'profile_id_category_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_provider_service_category');
    }
};