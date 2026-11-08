<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('leader_id')->constrained('users')->restrictOnDelete();
            $table->string('institution')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->text('profile')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->string('join_code', 16)->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('institution')->nullable();
            $table->string('discipline')->nullable();
            $table->string('role', 20)->default('member');
            $table->string('status', 20)->default('active');
            $table->timestamp('joined_at')->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
    }
};
