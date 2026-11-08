<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt', 500)->nullable();
            $table->longText('body');
            $table->string('category', 20)->default('pengumuman');
            $table->string('audience', 20)->default('public');
            $table->boolean('is_published')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_published', 'audience', 'published_at']);
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category', 20)->default('panduan');
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('external_url', 500)->nullable();
            $table->string('visibility', 20)->default('admins');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('agenda_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('session_type', 40)->nullable();
            $table->date('date')->nullable();
            $table->string('date_label', 100)->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->text('speakers')->nullable();
            $table->string('status', 20)->default('planned');
            $table->boolean('is_public')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('summary', 500)->nullable();
            $table->longText('body');
            $table->boolean('is_published')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->string('category', 60)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('agenda_items');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('announcements');
    }
};
