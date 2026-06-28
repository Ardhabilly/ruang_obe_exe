<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('course_lessons', 'learning_outcome')) {
            Schema::table('course_lessons', function (Blueprint $table) {
                $table->dropColumn('learning_outcome');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('course_lessons', 'learning_outcome')) {
            Schema::table('course_lessons', function (Blueprint $table) {
                $table->text('learning_outcome')->nullable();
            });
        }
    }
};