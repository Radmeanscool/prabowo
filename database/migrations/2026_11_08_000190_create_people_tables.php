<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Direktori mentor, juri, dan narasumber. Juri tidak otomatis menjadi mentor.
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type', 20);
            $table->string('category', 20)->nullable();
            $table->string('affiliation')->nullable();
            $table->string('position')->nullable();
            $table->text('expertise')->nullable();
            $table->text('bio')->nullable();
            $table->string('photo_path')->nullable();
            $table->foreignId('theme_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_public')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_public']);
        });

        Schema::create('mentor_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proposal_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role', 20)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['person_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_assignments');
        Schema::dropIfExists('people');
    }
};
