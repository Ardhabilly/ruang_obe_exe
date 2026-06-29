<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('user_lesson_progress', 'last_accessed_at')) {
            Schema::table('user_lesson_progress', function (Blueprint $table) {
                $table->timestamp('last_accessed_at')
                    ->nullable()
                    ->after('started_at')
                    ->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('user_lesson_progress', 'last_accessed_at')) {
            Schema::table('user_lesson_progress', function (Blueprint $table) {
                $table->dropColumn('last_accessed_at');
            });
        }
    }
};
