<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->restrictOnDelete();
            $table->foreignId('theme_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('challenge_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('program_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('review_phase_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

            // A. Identitas produk
            $table->string('product_name');
            $table->string('slug')->unique();
            $table->string('short_summary', 300)->nullable();
            $table->unsignedTinyInteger('tkt_level')->nullable();
            $table->string('prototype_status', 40)->nullable();

            // B. Tim dan institusi
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->text('inventors')->nullable();
            $table->text('previous_work')->nullable();

            // C. Latar belakang riset dan pengembangan
            $table->text('existing_results')->nullable();
            $table->text('ip_publications')->nullable();

            // D. Aspek teknis
            $table->text('technical_description')->nullable();
            $table->text('technical_validation')->nullable();
            $table->text('technical_limitations')->nullable();

            // E. Pasar, pengguna, dan dampak
            $table->text('target_users')->nullable();
            $table->text('market_description')->nullable();
            $table->text('competitors')->nullable();
            $table->text('value_proposition')->nullable();
            $table->text('market_validation')->nullable();
            $table->text('impact_description')->nullable();
            $table->text('adoption_plan')->nullable();

            // F. Kesesuaian tantangan
            $table->text('problem_statement')->nullable();
            $table->text('challenge_importance')->nullable();
            $table->text('solution_description')->nullable();
            $table->text('minimum_functions')->nullable();
            $table->text('success_indicators')->nullable();
            $table->text('data_facility_needs')->nullable();
            $table->text('pilot_location')->nullable();

            // G. Kesiapan legal, keselamatan, etik, dan data
            $table->string('ip_status', 40)->nullable();
            $table->text('ip_notes')->nullable();
            $table->string('regulatory_status', 40)->nullable();
            $table->text('regulatory_notes')->nullable();
            $table->text('third_party_dependencies')->nullable();
            $table->text('safety_risks')->nullable();
            $table->string('data_risk_status', 40)->nullable();
            $table->text('data_notes')->nullable();
            $table->string('ethics_status', 40)->nullable();
            $table->text('ethics_notes')->nullable();
            $table->text('environmental_risks')->nullable();
            $table->text('mitigation_plan')->nullable();

            // H. Tautan pendukung
            $table->string('demo_video_url', 500)->nullable();
            $table->string('repository_url', 500)->nullable();

            // Alur kerja
            $table->string('status', 32)->default('draft')->index();
            $table->unsignedInteger('version')->default(0);
            $table->timestamp('first_submitted_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reopened_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('decision_note')->nullable();

            // Publikasi: ringkasan publik terpisah dari isian privat.
            $table->boolean('is_public')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('unpublished_at')->nullable();
            $table->text('hidden_reason')->nullable();
            $table->string('public_slug')->nullable()->unique();
            $table->boolean('public_pdf_enabled')->default(false);
            $table->string('public_title')->nullable();
            $table->string('public_short_description', 500)->nullable();
            $table->text('public_full_description')->nullable();
            $table->text('public_problem')->nullable();
            $table->text('public_solution')->nullable();
            $table->text('public_target_users')->nullable();
            $table->text('public_technology')->nullable();
            $table->text('public_impact')->nullable();
            $table->string('public_image_path')->nullable();
            $table->string('public_team_label')->nullable();
            $table->boolean('public_show_team')->default(false);
            $table->boolean('public_show_tkt')->default(false);
            $table->boolean('public_show_links')->default(false);
            $table->string('public_badge', 32)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['theme_id', 'status']);
            $table->index(['is_public', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
