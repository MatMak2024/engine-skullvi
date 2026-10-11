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
        Schema::create('applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('candidate_id')->constrained('candidates', 'user_id')->cascadeOnDelete();
            $table->foreignUuid('program_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('course_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('offer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('education_level');
            $table->string('field_of_study')->nullable();
            $table->unsignedTinyInteger('years_experience')->default(0);
            $table->unsignedTinyInteger('projects_count')->default(0);
            $table->string('github_url')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('availability');
            $table->text('motivation_text');
            $table->string('cv_path')->nullable();
            $table->string('status')->default('received')->index();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('examination_authorized_at')->nullable();
            $table->timestamps();

            $table->unique(['candidate_id', 'program_id']);
            $table->unique(['candidate_id', 'course_id']);
            $table->unique(['candidate_id', 'offer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
