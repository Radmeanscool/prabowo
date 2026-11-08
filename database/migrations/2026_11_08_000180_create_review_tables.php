<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_phase_id')->constrained()->cascadeOnDelete();
            // Kosong = berlaku untuk semua tema.
            $table->foreignId('theme_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('max_score')->default(5);
            $table->decimal('weight', 5, 2);
            $table->boolean('requires_comment')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('reviewer_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('review_phase_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24)->default('assigned');
            $table->timestamp('due_at')->nullable();
            $table->text('conflict_reason')->nullable();
            $table->timestamp('conflict_declared_at')->nullable();
            $table->text('override_note')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->foreignId('removed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('removal_reason')->nullable();
            $table->timestamps();

            $table->index(['reviewer_id', 'status']);
            $table->index(['proposal_id', 'review_phase_id']);
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reviewer_assignment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('review_phase_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('draft');
            $table->string('recommendation', 40)->nullable();
            $table->text('overall_comment')->nullable();
            $table->text('private_notes')->nullable();
            $table->decimal('weighted_score', 6, 2)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reopened_at')->nullable();
            $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['proposal_id', 'review_phase_id', 'status']);
        });

        Schema::create('review_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('review_criterion_id')->constrained('review_criteria')->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['review_id', 'review_criterion_id']);
        });

        Schema::create('conflict_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('proposal_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('institution')->nullable();
            $table->text('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conflict_declarations');
        Schema::dropIfExists('review_scores');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('reviewer_assignments');
        Schema::dropIfExists('review_criteria');
    }
};
