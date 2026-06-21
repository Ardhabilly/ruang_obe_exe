<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('practice_submissions')) {
            Schema::create('practice_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('course_lesson_id')->constrained('course_lessons')->cascadeOnDelete();
                $table->string('practice_key');
                $table->string('title');
                $table->string('type')->default('aktivitas');
                $table->json('answers')->nullable();
                $table->json('feedback')->nullable();
                $table->integer('score')->default(0);
                $table->integer('max_score')->default(100);
                $table->boolean('is_completed')->default(false);
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'course_lesson_id', 'practice_key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_submissions');
    }
};