<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->constrained()->restrictOnDelete();
            $table->foreignId('challenge_owner_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('summary', 500)->nullable();
            $table->text('challenge_statement')->nullable();
            $table->text('problem_statement')->nullable();
            $table->text('affected_users')->nullable();
            $table->text('current_baseline')->nullable();
            $table->text('desired_outcome')->nullable();
            $table->text('minimum_functions')->nullable();
            $table->text('success_indicators')->nullable();
            $table->text('constraints')->nullable();
            $table->text('available_data')->nullable();
            $table->boolean('data_public')->default(false);
            $table->text('pilot_location')->nullable();
            $table->boolean('pilot_location_public')->default(false);
            $table->text('contact_info')->nullable();
            $table->boolean('contact_public')->default(false);
            $table->text('internal_notes')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'theme_id']);
        });

        // Tema mana saja yang boleh memilih tantangan ini (Pangan, Kesehatan, Hybrid).
        Schema::create('challenge_theme', function (Blueprint $table) {
            $table->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
            $table->primary(['challenge_id', 'theme_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_theme');
        Schema::dropIfExists('challenges');
    }
};
