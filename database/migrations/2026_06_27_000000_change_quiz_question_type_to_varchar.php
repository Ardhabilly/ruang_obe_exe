<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `quiz_questions` MODIFY `question_type` VARCHAR(80) NOT NULL");
    }

    public function down(): void
    {
        /*
         | Kolom dipertahankan sebagai VARCHAR agar tipe soal yang sudah
         | tersimpan tidak hilang saat rollback.
         */
    }
};