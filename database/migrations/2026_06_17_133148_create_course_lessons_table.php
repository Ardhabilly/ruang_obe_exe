<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_module_id')->constrained('course_modules')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('learning_outcome')->nullable();
            $table->integer('estimated_minutes')->default(10);
            $table->integer('order_number')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_lessons');
    }
};