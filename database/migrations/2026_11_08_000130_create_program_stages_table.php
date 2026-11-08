<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_stages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 48)->unique();
            $table->unsignedTinyInteger('phase_number')->nullable();
            $table->string('phase_name')->nullable();
            $table->string('name');
            $table->string('name_id')->nullable();
            $table->string('month_label', 64)->nullable();
            $table->unsignedTinyInteger('start_month')->nullable();
            $table->unsignedTinyInteger('end_month')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->text('description')->nullable();
            $table->text('activities')->nullable();
            $table->text('output')->nullable();
            $table->string('status', 20)->default('upcoming');
            $table->boolean('is_public')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_stages');
    }
};
