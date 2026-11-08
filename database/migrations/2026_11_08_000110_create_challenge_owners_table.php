<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenge_owners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_name', 100)->nullable();
            $table->string('type', 40)->default('lainnya');
            $table->foreignId('theme_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description')->nullable();
            $table->text('initial_focus')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            // Kontak internal: tidak pernah ditampilkan ke publik.
            $table->string('contact_name')->nullable();
            $table->string('contact_position')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->string('participation_status', 32)->default('invited');
            $table->boolean('is_public')->default(false);
            $table->text('internal_notes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_owners');
    }
};
