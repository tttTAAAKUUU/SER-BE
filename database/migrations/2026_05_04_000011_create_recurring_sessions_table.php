<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('recurring_templates')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('scheduled_date');
            $table->string('status')->default('pending'); // pending, confirmed, completed, skipped
            $table->json('addon_config')->nullable(); // session-specific addon override
            $table->decimal('subtotal', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->integer('projected_duration_minutes')->nullable();
            $table->timestamps();

            $table->index(['template_id', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_sessions');
    }
};