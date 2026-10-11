<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('candidates') && ! Schema::hasColumn('candidates', 'suspension_reason')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->text('suspension_reason')->nullable();
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'candidate_removed_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('candidate_removed_at')->nullable();
                $table->text('candidate_removal_reason')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('candidates', 'suspension_reason')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->dropColumn('suspension_reason');
            });
        }

        if (Schema::hasColumn('users', 'candidate_removed_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['candidate_removed_at', 'candidate_removal_reason']);
            });
        }
    }
};
