<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('cv_path')->nullable()->after('city');
            $table->string('education_level')->nullable()->after('cv_path');
            $table->string('field_of_study')->nullable()->after('education_level');
            $table->unsignedTinyInteger('years_experience')->default(0)->after('field_of_study');
            $table->unsignedTinyInteger('projects_count')->default(0)->after('years_experience');
            $table->string('github_url')->nullable()->after('projects_count');
            $table->string('portfolio_url')->nullable()->after('github_url');
            $table->string('linkedin_url')->nullable()->after('portfolio_url');
            $table->string('availability')->nullable()->after('linkedin_url');
            $table->json('interests')->nullable()->after('availability');
        });

        Schema::create('candidate_skill', function (Blueprint $table) {
            $table->foreignUuid('candidate_id')->constrained('candidates', 'user_id')->cascadeOnDelete();
            $table->foreignUuid('skill_id')->constrained()->cascadeOnDelete();
            $table->string('level')->default('beginner');
            $table->primary(['candidate_id', 'skill_id']);
        });

        Schema::create('candidate_diplomas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('candidate_id')->constrained('candidates', 'user_id')->cascadeOnDelete();
            $table->string('title');
            $table->string('institution')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('candidate_trainings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('candidate_id')->constrained('candidates', 'user_id')->cascadeOnDelete();
            $table->string('title');
            $table->string('organization')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_trainings');
        Schema::dropIfExists('candidate_diplomas');
        Schema::dropIfExists('candidate_skill');

        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn([
                'cv_path',
                'education_level',
                'field_of_study',
                'years_experience',
                'projects_count',
                'github_url',
                'portfolio_url',
                'linkedin_url',
                'availability',
                'interests',
            ]);
        });
    }
};
