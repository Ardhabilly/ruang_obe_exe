<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_responses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_attempt_id')
                ->constrained('quiz_attempts')
                ->cascadeOnDelete();

            $table->foreignId('quiz_question_id')
                ->constrained('quiz_questions')
                ->cascadeOnDelete();

            $table->json('response_value')->nullable();

            $table->longText('canvas_data')->nullable();

            $table->boolean('is_marked_doubtful')->default(false);

            $table->boolean('is_answered')->default(false);

            $table->boolean('is_correct')->default(false);

            $table->integer('points_earned')->default(0);

            $table->text('feedback')->nullable();

            $table->timestamps();

            $table->unique(['quiz_attempt_id', 'quiz_question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_responses');
    }
};