<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_lesson_id')->constrained('course_lessons')->cascadeOnDelete();
            $table->boolean('completed')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'course_lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_lesson_progress');
    }
};