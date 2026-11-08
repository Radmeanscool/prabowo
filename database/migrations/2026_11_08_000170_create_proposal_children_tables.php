<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_research_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->string('year', 20);
            $table->text('title');
            $table->text('researchers')->nullable();
            $table->decimal('funding_amount', 15, 2)->nullable();
            $table->string('funding_source')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('proposal_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('disk', 32)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->string('checksum', 64)->nullable();
            // File internal (mis. hasil visitasi legal) hanya untuk admin.
            $table->boolean('is_internal')->default(false);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['proposal_id', 'type']);
        });

        Schema::create('proposal_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('data');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->unique(['proposal_id', 'version']);
        });

        Schema::create('proposal_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->text('note')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });

        // Pertanyaan tambahan khusus tema (opsional, dapat diatur admin).
        Schema::create('theme_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->nullable()->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->text('help_text')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('word_limit')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('proposal_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('theme_question_id')->constrained()->cascadeOnDelete();
            $table->text('answer')->nullable();
            $table->timestamps();

            $table->unique(['proposal_id', 'theme_question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_answers');
        Schema::dropIfExists('theme_questions');
        Schema::dropIfExists('proposal_status_histories');
        Schema::dropIfExists('proposal_versions');
        Schema::dropIfExists('proposal_files');
        Schema::dropIfExists('proposal_research_histories');
    }
};
