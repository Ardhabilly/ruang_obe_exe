<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('class_group_id')
                ->constrained('class_groups')
                ->cascadeOnDelete();

            $table->foreignId('course_module_id')
                ->nullable()
                ->constrained('course_modules')
                ->nullOnDelete();

            $table->string('title');
            $table->string('slug');

            $table->enum('type', [
                'kuis_bab',
                'evaluasi_akhir',
            ])->default('kuis_bab');

            $table->text('description')->nullable();

            $table->text('instruction')->nullable();

            $table->integer('duration_minutes')->default(20);

            $table->integer('max_attempts')->default(1);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['class_group_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};