<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('applications', 'examination_authorized_at')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->timestamp('examination_authorized_at')->nullable();
            });
        }

        foreach (['offers', 'programs', 'courses'] as $table) {
            if (! Schema::hasColumn($table, 'requested_documents')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->json('requested_documents')->nullable();
                });
            }
        }

        if (! Schema::hasTable('application_documents')) {
            Schema::create('application_documents', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('application_id')->constrained()->cascadeOnDelete();
                $table->string('label');
                $table->string('path');
                $table->timestamps();

                $table->unique(['application_id', 'label']);
            });
        }

        if (Schema::hasTable('evaluations')) {
            DB::table('applications')
                ->whereNull('examination_authorized_at')
                ->whereExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('evaluations')
                        ->whereColumn('evaluations.application_id', 'applications.id')
                        ->where('is_current', true);
                })
                ->update(['examination_authorized_at' => DB::raw('submitted_at')]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
