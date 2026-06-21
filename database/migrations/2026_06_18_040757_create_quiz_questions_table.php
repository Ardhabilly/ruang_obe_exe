<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_id')
                ->constrained('quizzes')
                ->cascadeOnDelete();

            $table->longText('question_text');

            $table->enum('question_type', [
                'short_text',
                'math_notation',
                'checkbox',
                'matrix',
                'matrix_equation',
                'augmented_matrix',
                'canvas_final_answer',
            ]);

            $table->json('question_data')->nullable();

            $table->json('answer_key')->nullable();

            $table->json('accepted_answers')->nullable();

            $table->text('explanation')->nullable();

            $table->integer('points')->default(10);

            $table->integer('order_number')->default(1);

            $table->boolean('is_required')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};