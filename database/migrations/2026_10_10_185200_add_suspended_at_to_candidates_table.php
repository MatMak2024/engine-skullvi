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
        if (Schema::hasColumn('candidates', 'suspended_at')) {
            return;
        }

        Schema::table('candidates', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('candidates', 'suspended_at')) {
            return;
        }

        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn('suspended_at');
        });
    }
};
