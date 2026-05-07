<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_addons', function (Blueprint $table) {
            $table->string('addon_category')->nullable()->after('duration_minutes');
            $table->boolean('countable')->default(true)->after('addon_category');
        });
    }

    public function down(): void
    {
        Schema::table('service_addons', function (Blueprint $table) {
            $table->dropColumn(['addon_category', 'countable']);
        });
    }
};