<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\PracticeSubmission;
use App\Models\UserLessonProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\Models\Quiz;
use App\Models\QuizAttempt;

class CourseController extends Controller
{
    /* MATERI_LANJUTKAN_TERAKHIR_V2 */
    public function continueLearning()
    {
        $user = Auth::user();

        $lastProgress = UserLessonProgress::query()
            ->with(['lesson.module.course'])
            ->where('user_id', $user->id)
            ->whereNotNull('last_accessed_at')
            ->whereHas('lesson.module.course', function ($query) {
                $query->where('is_active', true);
            })
            ->orderByDesc('last_accessed_at')
            ->first();

        /*
         * Cadangan untuk data progres lama yang dibuat sebelum
         * last_accessed_at tersedia.
         */
        if (! $lastProgress) {
            $lastProgress = UserLessonProgress::query()
                ->with(['lesson.module.course'])
                ->where('user_id', $user->id)
                ->whereHas('lesson.module.course', function ($query) {
                    $query->where('is_active', true);
                })
                ->orderByDesc('updated_at')
                ->first();
        }

        if ($lastProgress?->lesson) {
            return redirect()->route('mahasiswa.materi.show', $lastProgress->lesson->slug);
        }

        $firstLesson = CourseLesson::query()
            ->join('course_modules', 'course_lessons.course_module_id', '=', 'course_modules.id')
            ->join('courses', 'course_modules.course_id', '=', 'courses.id')
            ->where('courses.is_active', true)
            ->orderBy('course_modules.order_number')
            ->orderBy('course_lessons.order_number')
            ->select('course_lessons.*')
            ->first();

        if ($firstLesson) {
            return redirect()->route('mahasiswa.materi.show', $firstLesson->slug);
        }

        return redirect()
            ->route('mahasiswa.dashboard')
            ->with('warning', 'Materi pembelajaran belum tersedia.');
    }
    public function show(CourseLesson $lesson)
    {
        $user = Auth::user();

        $lesson->load('module.course');

        $course = $lesson->module->course;

        $modules = $course->modules()
            ->with('lessons')
            ->orderBy('order_number')
            ->get();

        $completedLessonIds = UserLessonProgress::where('user_id', $user->id)
            ->where('completed', true)
            ->pluck('course_lesson_id')
            ->toArray();

        $allLessons = $this->getOrderedLessons($course);

        $currentIndex = $allLessons->search(function ($item) use ($lesson) {
            return $item->id === $lesson->id;
        });

        $lockedTarget = $this->firstUnfinishedBefore($allLessons, $currentIndex, $completedLessonIds);

        if ($lockedTarget) {
            return redirect()
                ->route('mahasiswa.materi.show', $lockedTarget->slug)
                ->with('warning', 'Materi tersebut masih terkunci. Selesaikan materi sebelumnya terlebih dahulu.');
        }

        /*
        |--------------------------------------------------------------------------
        | Kuis Bab sebagai Penutup Bab
        |--------------------------------------------------------------------------
        | Sebelum subbab pada bab baru dapat diakses, seluruh kuis pada bab
        | sebelumnya harus telah selesai dengan status lulus KKM.
        */
        /* CHAPTER_QUIZ_GATE_V1 */
        $chapterQuizGate = $this->chapterQuizGateForLesson($lesson, $user->id);

        if ($chapterQuizGate) {
            $requiredModule = $chapterQuizGate['module'];
            $requiredBab = (int) $requiredModule->order_number;

            if ($chapterQuizGate['quiz']) {
                return redirect()
                    ->route('mahasiswa.kuis.instruction', $chapterQuizGate['quiz'])
                    ->with(
                        'warning',
                        "Bab berikutnya masih terkunci. Selesaikan dan capai KKM pada Kuis Bab {$requiredBab} terlebih dahulu."
                    );
            }

            return redirect()
                ->route('mahasiswa.kelas.index')
                ->with(
                    'warning',
                    "Kuis Bab {$requiredBab} belum tersedia untuk kelas Anda. Bergabunglah ke kelas atau hubungi dosen."
                );
        }

        $accessibleLessonIds = $this->getAccessibleLessonIds(
            $allLessons,
            $completedLessonIds,
            $user->id
        );

        $progress = UserLessonProgress::firstOrCreate(
            [
                'user_id' => $user->id,
                'course_lesson_id' => $lesson->id,
            ],
            [
                'started_at' => now(),
                'completed' => false,
            ]
        );

        if (! $progress->started_at) {
            $progress->update([
                'started_at' => now(),
            ]);
        }

        /* MATERI_LAST_ACCESSED_UPDATE_V2 */
        $progress->update([
            'last_accessed_at' => now(),
        ]);

        $previousLesson = $currentIndex > 0
            ? $allLessons[$currentIndex - 1]
            : null;

        $nextLesson = $currentIndex !== false && $currentIndex < $allLessons->count() - 1
            ? $allLessons[$currentIndex + 1]
            : null;

        $practiceSubmissions = PracticeSubmission::where('user_id', $user->id)
            ->where('course_lesson_id', $lesson->id)
            ->get()
            ->keyBy('practice_key');

        $requiredPractices = $this->requiredPractices($lesson->slug);

        $completedPracticeKeys = $practiceSubmissions
            ->filter(fn (PracticeSubmission $submission) => $this->isPracticeCompletionValid($submission))
            ->keys()
            ->toArray();

        $joinedClassIds = $user->joinedClassGroups()
            ->pluck('class_groups.id')
            ->toArray();

        $moduleIds = $course->modules
            ->pluck('id')
            ->toArray();

        $quizzesByModule = Quiz::with(['classGroup'])
            ->withCount('questions')
            ->whereIn('class_group_id', $joinedClassIds)
            ->whereIn('course_module_id', $moduleIds)
            ->where('type', 'kuis_bab')
            ->where('is_active', true)
            ->orderBy('course_module_id')
            ->get()
            ->map(function ($quiz) use ($user) {
                $quiz->is_unlocked = $this->isQuizUnlocked($quiz, $user->id);
                $quiz->locked_reason = $quiz->is_unlocked
                    ? null
                    : 'Selesaikan seluruh materi pada bab ini terlebih dahulu.';

                return $quiz;
            })
            ->groupBy('course_module_id');

        $finalEvaluations = Quiz::with(['classGroup'])
            ->withCount('questions')
            ->whereIn('class_group_id', $joinedClassIds)
            ->where('type', 'evaluasi_akhir')
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(function ($quiz) use ($user) {
                $quiz->is_unlocked = $this->isQuizUnlocked($quiz, $user->id);
                $quiz->locked_reason = $quiz->is_unlocked
                    ? null
                    : 'Selesaikan seluruh materi Bab 1 sampai Bab 4 terlebih dahulu.';

                return $quiz;
            });

        return view('mahasiswa.materi.show', compact(
            'lesson',
            'course',
            'modules',
            'progress',
            'completedLessonIds',
            'previousLesson',
            'nextLesson',
            'accessibleLessonIds',
            'practiceSubmissions',
            'requiredPractices',
            'completedPracticeKeys',
            'quizzesByModule',
            'finalEvaluations'
        ));
    }

    public function complete(Request $request, CourseLesson $lesson)
    {
        $user = Auth::user();

        $lesson->load('module.course');

        $course = $lesson->module->course;

        $completedLessonIds = UserLessonProgress::where('user_id', $user->id)
            ->where('completed', true)
            ->pluck('course_lesson_id')
            ->toArray();

        $allLessons = $this->getOrderedLessons($course);

        $currentIndex = $allLessons->search(function ($item) use ($lesson) {
            return $item->id === $lesson->id;
        });

        $lockedTarget = $this->firstUnfinishedBefore($allLessons, $currentIndex, $completedLessonIds);

        if ($lockedTarget) {
            return redirect()
                ->route('mahasiswa.materi.show', $lockedTarget->slug)
                ->with('warning', 'Selesaikan materi sebelumnya terlebih dahulu.');
        }

        $requiredPractices = $this->requiredPractices($lesson->slug);

        if (! empty($requiredPractices)) {
            $completedPracticeKeys = PracticeSubmission::where('user_id', $user->id)
                ->where('course_lesson_id', $lesson->id)
                ->get()
                ->filter(fn (PracticeSubmission $submission) => $this->isPracticeCompletionValid($submission))
                ->pluck('practice_key')
                ->toArray();

            $missingPractices = [];

            foreach ($requiredPractices as $key => $title) {
                if (! in_array($key, $completedPracticeKeys, true)) {
                    $missingPractices[] = $title;
                }
            }

            if (! empty($missingPractices)) {
                return back()->with(
                    'warning',
                    'Materi belum bisa ditandai selesai. Kerjakan terlebih dahulu: ' . implode(', ', $missingPractices) . '.'
                );
            }
        }

        $progress = UserLessonProgress::firstOrCreate(
            [
                'user_id' => $user->id,
                'course_lesson_id' => $lesson->id,
            ],
            [
                'started_at' => now(),
                'completed' => false,
            ]
        );

        $durationSeconds = 0;

        if ($progress->started_at) {
            $durationSeconds = max(60, $progress->started_at->diffInSeconds(now()));
        }

        /* MATERI_COMPLETE_LAST_ACCESSED_V2 */
        $progress->update([
            'completed' => true,
            'completed_at' => now(),
            'last_accessed_at' => now(),
            'duration_seconds' => $durationSeconds,
        ]);

        return back()->with('success', 'Materi berhasil ditandai selesai. Materi berikutnya sudah terbuka.');
    }

    private function getOrderedLessons(Course $course): Collection
    {
        return CourseLesson::query()
            ->join('course_modules', 'course_lessons.course_module_id', '=', 'course_modules.id')
            ->where('course_modules.course_id', $course->id)
            ->orderBy('course_modules.order_number')
            ->orderBy('course_lessons.order_number')
            ->select('course_lessons.*')
            ->get();
    }

    private function firstUnfinishedBefore(Collection $allLessons, int|false $currentIndex, array $completedLessonIds): ?CourseLesson
    {
        if ($currentIndex === false || $currentIndex <= 0) {
            return null;
        }

        return $allLessons
            ->take($currentIndex)
            ->first(function ($item) use ($completedLessonIds) {
                return ! in_array($item->id, $completedLessonIds, true);
            });
    }


    /*
    |--------------------------------------------------------------------------
    | Pemeriksaan Prasyarat Kuis Bab
    |--------------------------------------------------------------------------
    */
    /* CHAPTER_QUIZ_GATE_HELPER_V1 */
    private function chapterQuizGateForLesson(CourseLesson $lesson, int $userId): ?array
    {
        $lesson->loadMissing('module.course');

        $currentModule = $lesson->module;
        $course = $currentModule?->course;

        if (! $currentModule || ! $course) {
            return null;
        }

        $previousModules = $course->modules()
            ->where('order_number', '<', $currentModule->order_number)
            ->orderBy('order_number')
            ->get();

        if ($previousModules->isEmpty()) {
            return null;
        }

        $joinedClassIds = \App\Models\ClassMember::query()
            ->where('user_id', $userId)
            ->pluck('class_group_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($previousModules as $previousModule) {
            $chapterQuizzes = Quiz::query()
                ->whereIn('class_group_id', $joinedClassIds)
                ->where('course_module_id', $previousModule->id)
                ->where('type', 'kuis_bab')
                ->where('is_active', true)
                ->orderBy('id')
                ->get();

            if ($chapterQuizzes->isEmpty()) {
                return [
                    'module' => $previousModule,
                    'quiz' => null,
                ];
            }

            $hasPassedQuiz = QuizAttempt::query()
                ->where('user_id', $userId)
                ->whereIn('quiz_id', $chapterQuizzes->pluck('id'))
                ->whereIn('status', ['submitted', 'auto_submitted'])
                ->where('is_passed', true)
                ->exists();

            if (! $hasPassedQuiz) {
                return [
                    'module' => $previousModule,
                    'quiz' => $chapterQuizzes->first(),
                ];
            }
        }

        return null;
    }
    /* CHAPTER_QUIZ_ACCESSIBILITY_V1 */
    private function getAccessibleLessonIds(
        Collection $allLessons,
        array $completedLessonIds,
        int $userId
    ): array {
        $accessibleLessonIds = [];

        foreach ($allLessons as $lesson) {
            /*
             * Saat memasuki bab baru, kuis bab sebelumnya harus sudah lulus.
             * Dengan demikian sidebar menampilkan Bab berikutnya dalam status
             * terkunci sebelum mahasiswa memenuhi prasyarat kuis.
             */
            if ($this->chapterQuizGateForLesson($lesson, $userId)) {
                break;
            }

            $accessibleLessonIds[] = (int) $lesson->id;

            if (! in_array((int) $lesson->id, $completedLessonIds, true)) {
                break;
            }
        }

        return $accessibleLessonIds;
    }

    private function isPracticeCompletionValid(PracticeSubmission $submission): bool
    {
        if (! $submission->is_completed) {
            return false;
        }

        $componentAttemptPracticeKeys = [
            'aktivitas-1-1',
            'cek-pemahaman-1-2',
            'aktivitas-1-2-server',
            'cek-pemahaman-1-3',
            'aktivitas-1-3-solusi',
            'contoh-simulasi-1-4-perhitungan',
            'cek-pemahaman-1-4-metode',
            'contoh-simulasi-1-4-matriks-a',
            'contoh-simulasi-1-4-ax-b',
            'cek-pemahaman-1-4-ax-b',
            'contoh-simulasi-1-4-augmented',
            'cek-pemahaman-1-4-terjemahan-matriks',
            'aktivitas-1-4-matriks',
            'contoh-simulasi-2-2-pertukaran',
            'contoh-simulasi-2-2-perkalian-a',
            'contoh-simulasi-2-2-perkalian-b',
            'contoh-simulasi-2-2-penjumlahan-a',
            'contoh-simulasi-2-2-penjumlahan-b',
            'aktivitas-2-1-obe',
            // SUBBAB_3_1_ESELON_BARIS_V1
            'aktivitas-3-1-eselon-baris',
            'contoh-simulasi-3-2-eselon-baris',
            'contoh-simulasi-3-3-substitusi-balik',
                        /* SUBBAB_4_1_ESELON_TEREDUKSI_ATTEMPT_KEY_START */
            'aktivitas-4-1-eselon-tereduksi',
            /* SUBBAB_4_1_ESELON_TEREDUKSI_ATTEMPT_KEY_END */
            'aktivitas-3-2-eliminasi-gauss',
            'contoh-simulasi-4-2-eselon-baris-tereduksi',
                'cek-pemahaman-4-3-membaca-rref',
                'aktivitas-4-2-gauss-jordan',
        ];

        if (! in_array($submission->practice_key, $componentAttemptPracticeKeys, true)) {
            return true;
        }

        $feedback = is_array($submission->feedback) ? $submission->feedback : [];

        return ($feedback['_meta']['attempt_scope'] ?? null) === 'component';
    }

    private function requiredPractices(string $lessonSlug): array
    {
        return match ($lessonSlug) {
            'pengertian-sistem-persamaan-linear' => [
                'aktivitas-1-1' => 'Aktivitas 1.1 - Laboratorium Validasi Aljabar',
            ],

            'bentuk-umum-sistem-persamaan-linear' => [
                'cek-pemahaman-1-2' => 'Cek Pemahaman Bentuk Umum Sistem Persamaan Linear',
                'aktivitas-1-2-server' => 'Aktivitas 1.2 Pemodelan Alokasi Sumber Daya Server',
            ],

            'kemungkinan-solusi-sistem-persamaan-linear' => [
                'cek-pemahaman-1-3' => 'Cek Pemahaman Kemungkinan Solusi Sistem Persamaan Linear',
                'aktivitas-1-3-solusi' => 'Aktivitas 1.3 Analisis Skenario Solusi di Dunia Nyata',
            ],

            'metode-penyelesaian-spl-menuju-representasi-matriks' => [
                'contoh-simulasi-1-4-perhitungan' => 'Contoh Simulasi 1.4 Penyelesaian SPL Skala Kecil',
                'cek-pemahaman-1-4-metode' => 'Cek Pemahaman Keterbatasan Metode Dasar',
                'contoh-simulasi-1-4-matriks-a' => 'Contoh Simulasi Matriks Koefisien A',
                'contoh-simulasi-1-4-ax-b' => 'Contoh Simulasi Persamaan Matriks Ax = b',
                'cek-pemahaman-1-4-ax-b' => 'Cek Pemahaman Notasi Ax = b',
                'contoh-simulasi-1-4-augmented' => 'Contoh Simulasi Augmented Matrix',
                'cek-pemahaman-1-4-terjemahan-matriks' => 'Cek Pemahaman Terjemahan Augmented Matrix',
                'aktivitas-1-4-matriks' => 'Aktivitas 1.4 Pemodelan Matriks pada Kasus Komputasi Dunia Nyata',
            ],


            /* SUBBAB_3_1_ESELON_BARIS_V1 */
            'algoritma-syarat-matriks-eselon-baris' => [
                'aktivitas-3-1-eselon-baris' => 'Aktivitas 3.1 - Uji Visual Eselon Baris',
            ],
            'jenis-jenis-operasi-baris-elementer' => [
                'contoh-simulasi-2-2-pertukaran' => 'Contoh Simulasi Pertukaran Dua Baris',
                'contoh-simulasi-2-2-perkalian-a' => 'Contoh Simulasi Perkalian Baris',
                'contoh-simulasi-2-2-perkalian-b' => 'Contoh Simulasi Perkalian Baris dengan Pecahan',
                'contoh-simulasi-2-2-penjumlahan-a' => 'Contoh Simulasi Penjumlahan Kelipatan Baris',
                'contoh-simulasi-2-2-penjumlahan-b' => 'Contoh Simulasi Penjumlahan Kelipatan dengan Pecahan',
                'aktivitas-2-1-obe' => 'Aktivitas 2.1 - Latihan Mandiri Operasi Baris Elementer',
            ],

            'simulasi-mengubah-matriks-menjadi-eselon-baris' => [
                'contoh-simulasi-3-2-eselon-baris' => 'Contoh Simulasi 3.2 - Mengubah Matriks Menjadi Eselon Baris',
            ],

            'menyelesaikan-spl-dengan-metode-eliminasi-gauss' => [
                'contoh-simulasi-3-3-substitusi-balik' => 'Contoh Simulasi 3.3 - Substitusi Balik',
                'aktivitas-3-2-eliminasi-gauss' => 'Aktivitas 3.2 - Menyelesaikan SPL dengan Eliminasi Gauss',
            ],

                        /* SUBBAB_4_1_ESELON_TEREDUKSI_REQUIRED_START */
            'algoritma-syarat-matriks-eselon-baris-tereduksi' => [
                'aktivitas-4-1-eselon-tereduksi' => 'Aktivitas 4.1 - Uji Visual Matriks Eselon Baris Tereduksi',
            ],
            /* SUBBAB_4_1_ESELON_TEREDUKSI_REQUIRED_END */
'simulasi-mengubah-matriks-menjadi-eselon-baris-tereduksi' => [
    'contoh-simulasi-4-2-eselon-baris-tereduksi' => 'Contoh Simulasi 4.2 - Mengubah Matriks Menjadi Eselon Baris Tereduksi',
],
            'menyelesaikan-spl-dengan-metode-eliminasi-gauss-jordan' => [
                'cek-pemahaman-4-3-membaca-rref' => 'Cek Pemahaman 4.3 - Membaca Solusi dari Matriks Eselon Baris Tereduksi',
                'aktivitas-4-2-gauss-jordan' => 'Aktivitas 4.2 - Menyelesaikan SPL dengan Metode Eliminasi Gauss-Jordan',
            ],




            default => [],
        };
    }

    private function isQuizUnlocked(Quiz $quiz, int $userId): bool
    {
        if ($quiz->type === 'evaluasi_akhir') {
            $lessonIds = CourseLesson::pluck('id')->toArray();
        } else {
            if (! $quiz->course_module_id) {
                return false;
            }

            $lessonIds = CourseLesson::where('course_module_id', $quiz->course_module_id)
                ->pluck('id')
                ->toArray();
        }

        if (empty($lessonIds)) {
            return false;
        }

        $completedCount = UserLessonProgress::where('user_id', $userId)
            ->whereIn('course_lesson_id', $lessonIds)
            ->where('completed', true)
            ->distinct()
            ->count('course_lesson_id');

        return $completedCount === count($lessonIds);
    }
}