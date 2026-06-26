<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Mahasiswa\DashboardController as MahasiswaDashboardController;
use App\Http\Controllers\Dosen\DashboardController as DosenDashboardController;
use App\Http\Controllers\Dosen\ClassGroupController as DosenClassGroupController;
use App\Http\Controllers\Mahasiswa\ClassJoinController as MahasiswaClassJoinController;
use App\Http\Controllers\Mahasiswa\CourseController as MahasiswaCourseController;
use App\Http\Controllers\Mahasiswa\PracticeController as MahasiswaPracticeController;
use App\Http\Controllers\Mahasiswa\QuizController as MahasiswaQuizController;
use App\Http\Controllers\Dosen\QuizManagementController as DosenQuizManagementController;

Route::view('/', 'welcome')->name('landing');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        if (auth()->user()->role === 'dosen') {
            return redirect()->route('dosen.dashboard');
        }

        return redirect()->route('mahasiswa.dashboard');
    })->name('dashboard');

    Route::middleware(['role:mahasiswa'])
        ->prefix('mahasiswa')
        ->name('mahasiswa.')
        ->group(function () {
            Route::get('/dashboard', [MahasiswaDashboardController::class, 'index'])
                ->name('dashboard');

            Route::get('/kelas', [MahasiswaClassJoinController::class, 'index'])
                ->name('kelas.index');

            Route::post('/kelas/gabung', [MahasiswaClassJoinController::class, 'join'])
                ->name('kelas.join');

            Route::get('/materi', [MahasiswaCourseController::class, 'index'])
                ->name('materi.index');

            Route::get('/materi/{lesson:slug}', [MahasiswaCourseController::class, 'show'])
                ->name('materi.show');

            Route::post('/materi/{lesson:slug}/selesai', [MahasiswaCourseController::class, 'complete'])
                ->name('materi.complete');

            Route::post('/materi/{lesson:slug}/latihan/{practiceKey}', [MahasiswaPracticeController::class, 'submit'])
                ->name('practice.submit');

            Route::get('/kuis/{quiz}/instruksi', [MahasiswaQuizController::class, 'instruction'])
                ->name('kuis.instruction');

            Route::post('/kuis/{quiz}/mulai', [MahasiswaQuizController::class, 'start'])
                ->name('kuis.start');

            Route::get('/kuis/attempt/{attempt}', [MahasiswaQuizController::class, 'attempt'])
                ->name('kuis.attempt');

            Route::post('/kuis/attempt/{attempt}/simpan', [MahasiswaQuizController::class, 'save'])
                ->name('kuis.save');

            Route::post('/kuis/attempt/{attempt}/submit', [MahasiswaQuizController::class, 'submit'])
                ->name('kuis.submit');

            Route::get('/kuis/attempt/{attempt}/hasil', [MahasiswaQuizController::class, 'result'])
                ->name('kuis.result');
        });

    Route::middleware(['role:dosen'])
        ->prefix('dosen')
        ->name('dosen.')
        ->group(function () {
            Route::get('/dashboard', [DosenDashboardController::class, 'index'])
                ->name('dashboard');

            Route::get('/kelas', [DosenClassGroupController::class, 'index'])
                ->name('kelas.index');

            Route::get('/kelas/create', [DosenClassGroupController::class, 'create'])
                ->name('kelas.create');

            Route::post('/kelas', [DosenClassGroupController::class, 'store'])
                ->name('kelas.store');

            Route::get('/kelas/{classGroup}', [DosenClassGroupController::class, 'show'])
                ->name('kelas.show');

            Route::get('/kelas/{classGroup}/mahasiswa/{student}/riwayat-kuis', [DosenClassGroupController::class, 'studentQuizHistory'])
                ->name('kelas.mahasiswa.riwayat');

            Route::get('/kelas/{classGroup}/hasil-kuis/{attempt}', [DosenClassGroupController::class, 'quizAttemptDetail'])
                ->name('kelas.kuis.detail');

            Route::get('/kelas/{classGroup}/edit', [DosenClassGroupController::class, 'edit'])
                ->name('kelas.edit');

            Route::put('/kelas/{classGroup}', [DosenClassGroupController::class, 'update'])
                ->name('kelas.update');

            Route::delete('/kelas/{classGroup}', [DosenClassGroupController::class, 'destroy'])
                ->name('kelas.destroy');

            Route::patch('/kelas/{classGroup}/token', [DosenClassGroupController::class, 'regenerateToken'])
                ->name('kelas.regenerate-token');
            Route::get('/kuis', [DosenQuizManagementController::class, 'index'])
                ->name('kuis.index');

            Route::get('/kuis/buat', [DosenQuizManagementController::class, 'create'])
                ->name('kuis.create');

            Route::post('/kuis', [DosenQuizManagementController::class, 'store'])
                ->name('kuis.store');
        });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

require __DIR__.'/auth.php';
