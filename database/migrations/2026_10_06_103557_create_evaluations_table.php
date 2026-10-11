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
        Schema::create('evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('application_id')->constrained()->cascadeOnDelete();
            $table->string('rubric_version');
            $table->unsignedTinyInteger('total_score');
            $table->unsignedTinyInteger('completeness_rate');
            $table->string('suggested_status');
            $table->json('flags')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamp('computed_at');
            $table->timestamps();

            $table->index(['application_id', 'is_current']);
            $table->index('total_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
