<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->unsignedInteger('raw_score')
                ->nullable()
                ->after('score');
        });

        DB::table('quiz_attempts')
            ->whereNull('raw_score')
            ->update([
                'raw_score' => DB::raw('score'),
            ]);
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn('raw_score');
        });
    }
};
