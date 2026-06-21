<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_id')
                ->constrained('quizzes')
                ->cascadeOnDelete();

            $table->foreignId('class_group_id')
                ->constrained('class_groups')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->integer('attempt_number')->default(1);

            $table->integer('score')->default(0);

            $table->integer('max_score')->default(0);

            $table->integer('correct_answers')->default(0);

            $table->integer('total_questions')->default(0);

            $table->integer('duration_seconds')->default(0);

            $table->boolean('is_passed')->default(false);

            $table->enum('status', [
                'in_progress',
                'submitted',
                'auto_submitted',
            ])->default('in_progress');

            $table->timestamp('started_at')->nullable();

            $table->timestamp('expires_at')->nullable();

            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};