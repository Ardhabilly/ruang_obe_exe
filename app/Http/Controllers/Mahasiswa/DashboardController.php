<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\CourseLesson;
use App\Models\PracticeSubmission;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\UserLessonProgress;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Katalog Interaksi Pembelajaran
    |--------------------------------------------------------------------------
    | Kunci ini mengikuti komponen yang diwajibkan pada setiap subbab.
    | Contoh simulasi dicatat sebagai interaksi karena mahasiswa tetap
    | perlu mengerjakannya sebelum dapat menyelesaikan materi terkait.
    |--------------------------------------------------------------------------
    */
    private const REQUIRED_PRACTICES_BY_LESSON = [
        'pengertian-sistem-persamaan-linear' => [
            'aktivitas-1-1',
        ],
        'bentuk-umum-sistem-persamaan-linear' => [
            'cek-pemahaman-1-2',
            'aktivitas-1-2-server',
        ],
        'kemungkinan-solusi-sistem-persamaan-linear' => [
            'cek-pemahaman-1-3',
            'aktivitas-1-3-solusi',
        ],
        'metode-penyelesaian-spl-menuju-representasi-matriks' => [
            'contoh-simulasi-1-4-perhitungan',
            'cek-pemahaman-1-4-metode',
            'contoh-simulasi-1-4-matriks-a',
            'contoh-simulasi-1-4-ax-b',
            'cek-pemahaman-1-4-ax-b',
            'contoh-simulasi-1-4-augmented',
            'cek-pemahaman-1-4-terjemahan-matriks',
            'aktivitas-1-4-matriks',
        ],
        'jenis-jenis-operasi-baris-elementer' => [
            'contoh-simulasi-2-2-pertukaran',
            'contoh-simulasi-2-2-perkalian-a',
            'contoh-simulasi-2-2-perkalian-b',
            'contoh-simulasi-2-2-penjumlahan-a',
            'contoh-simulasi-2-2-penjumlahan-b',
            'aktivitas-2-1-obe',
        ],
        'algoritma-syarat-matriks-eselon-baris' => [
            'aktivitas-3-1-eselon-baris',
        ],
        'simulasi-mengubah-matriks-menjadi-eselon-baris' => [
            'contoh-simulasi-3-2-eselon-baris',
        ],
        'menyelesaikan-spl-dengan-metode-eliminasi-gauss' => [
            'contoh-simulasi-3-3-substitusi-balik',
            'aktivitas-3-2-eliminasi-gauss',
        ],
        'algoritma-syarat-matriks-eselon-baris-tereduksi' => [
            'aktivitas-4-1-eselon-tereduksi',
        ],
        'simulasi-mengubah-matriks-menjadi-eselon-baris-tereduksi' => [
            'contoh-simulasi-4-2-eselon-baris-tereduksi',
        ],
        'menyelesaikan-spl-dengan-metode-eliminasi-gauss-jordan' => [
            'cek-pemahaman-4-3-membaca-rref',
            'aktivitas-4-2-gauss-jordan',
        ],
    ];

    private const COMPONENT_ATTEMPT_PRACTICE_KEYS = [
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
        'aktivitas-3-1-eselon-baris',
        'contoh-simulasi-3-2-eselon-baris',
        'contoh-simulasi-3-3-substitusi-balik',
        'aktivitas-3-2-eliminasi-gauss',
        'aktivitas-4-1-eselon-tereduksi',
        'contoh-simulasi-4-2-eselon-baris-tereduksi',
        'cek-pemahaman-4-3-membaca-rref',
        'aktivitas-4-2-gauss-jordan',
    ];

    public function index(): View
    {
        $user = Auth::user();

        /*
         * Ambil materi dari kursus yang aktif. Cadangan di bawah digunakan
         * apabila data lama belum menandai kursus sebagai aktif.
         */
        $allLessons = CourseLesson::query()
            ->join('course_modules', 'course_lessons.course_module_id', '=', 'course_modules.id')
            ->join('courses', 'course_modules.course_id', '=', 'courses.id')
            ->where('courses.is_active', true)
            ->orderBy('course_modules.order_number')
            ->orderBy('course_lessons.order_number')
            ->select('course_lessons.*')
            ->get();

        if ($allLessons->isEmpty()) {
            $allLessons = CourseLesson::query()
                ->join('course_modules', 'course_lessons.course_module_id', '=', 'course_modules.id')
                ->orderBy('course_modules.order_number')
                ->orderBy('course_lessons.order_number')
                ->select('course_lessons.*')
                ->get();
        }

        $lessonIds = $allLessons
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $moduleIds = $allLessons
            ->pluck('course_module_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $totalLessons = $lessonIds->count();

        $completedLessonIds = UserLessonProgress::query()
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->whereIn('course_lesson_id', $lessonIds)
            ->pluck('course_lesson_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $completedLessons = count($completedLessonIds);

        /*
         * Interaksi pembelajaran: cek pemahaman, contoh simulasi, dan aktivitas.
         */
        $requiredPracticeKeys = $allLessons
            ->flatMap(fn (CourseLesson $lesson) => self::REQUIRED_PRACTICES_BY_LESSON[$lesson->slug] ?? [])
            ->unique()
            ->values();

        $totalPractices = $requiredPracticeKeys->count();

        $completedPractices = PracticeSubmission::query()
            ->where('user_id', $user->id)
            ->whereIn('course_lesson_id', $lessonIds)
            ->whereIn('practice_key', $requiredPracticeKeys)
            ->get()
            ->filter(fn (PracticeSubmission $submission) => $this->isPracticeCompletionValid($submission))
            ->pluck('practice_key')
            ->unique()
            ->count();

        /*
         * Kuis dihitung berdasarkan status sudah dikumpulkan, bukan nilai.
         * Status lulus/tidak lulus tetap ditampilkan pada halaman hasil kuis.
         */
        $joinedClasses = $user->joinedClassGroups()
            ->with('dosen')
            ->latest('class_members.joined_at')
            ->get();

        $joinedClassIds = $joinedClasses
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $availableQuizzes = collect();

        if ($joinedClassIds->isNotEmpty()) {
            $availableQuizzes = Quiz::query()
                ->whereIn('class_group_id', $joinedClassIds)
                ->where('is_active', true)
                ->where(function ($query) use ($moduleIds) {
                    $query
                        ->where(function ($quizQuery) use ($moduleIds) {
                            $quizQuery
                                ->where('type', 'kuis_bab')
                                ->whereIn('course_module_id', $moduleIds);
                        })
                        ->orWhere('type', 'evaluasi_akhir');
                })
                ->get(['id', 'type', 'course_module_id']);
        }

        $completedQuizIds = collect();

        if ($availableQuizzes->isNotEmpty()) {
            $completedQuizIds = QuizAttempt::query()
                ->where('user_id', $user->id)
                ->whereIn('quiz_id', $availableQuizzes->pluck('id'))
                ->whereIn('status', ['submitted', 'auto_submitted'])
                ->pluck('quiz_id')
                ->map(fn ($id) => (int) $id)
                ->unique();
        }

        $completedBabQuizModules = $availableQuizzes
            ->filter(function (Quiz $quiz) use ($completedQuizIds) {
                return $quiz->type === 'kuis_bab'
                    && $quiz->course_module_id
                    && $completedQuizIds->contains((int) $quiz->id);
            })
            ->pluck('course_module_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->count();

        $totalBabQuizzes = $moduleIds->count();
        $completedBabQuizzes = min($completedBabQuizModules, $totalBabQuizzes);

        $totalFinalEvaluation = $totalLessons > 0 ? 1 : 0;

        $completedFinalEvaluation = $availableQuizzes
            ->filter(fn (Quiz $quiz) => $quiz->type === 'evaluasi_akhir')
            ->contains(fn (Quiz $quiz) => $completedQuizIds->contains((int) $quiz->id))
            ? 1
            : 0;

        /*
         * Bobot progres pembelajaran.
         */
        $lessonProgressPercentage = $totalLessons > 0
            ? (int) round(($completedLessons / $totalLessons) * 100)
            : 0;

        $practiceProgressPercentage = $totalPractices > 0
            ? (int) round(($completedPractices / $totalPractices) * 100)
            : 0;

        $quizProgressPercentage = $totalBabQuizzes > 0
            ? (int) round(($completedBabQuizzes / $totalBabQuizzes) * 100)
            : 0;

        $evaluationProgressPercentage = $totalFinalEvaluation > 0
            ? (int) round(($completedFinalEvaluation / $totalFinalEvaluation) * 100)
            : 0;

        $progressPercentage = (int) round(
            ($lessonProgressPercentage * 0.40)
            + ($practiceProgressPercentage * 0.30)
            + ($quizProgressPercentage * 0.20)
            + ($evaluationProgressPercentage * 0.10)
        );

        $nextLesson = $allLessons->first(function (CourseLesson $lesson) use ($completedLessonIds) {
            return ! in_array((int) $lesson->id, $completedLessonIds, true);
        });

        $hasQuizAccess = $joinedClassIds->isNotEmpty();

        return view('mahasiswa.dashboard', compact(
            'user',
            'totalLessons',
            'completedLessons',
            'totalPractices',
            'completedPractices',
            'totalBabQuizzes',
            'completedBabQuizzes',
            'totalFinalEvaluation',
            'completedFinalEvaluation',
            'lessonProgressPercentage',
            'practiceProgressPercentage',
            'quizProgressPercentage',
            'evaluationProgressPercentage',
            'progressPercentage',
            'joinedClasses',
            'nextLesson',
            'hasQuizAccess'
        ));
    }

    private function isPracticeCompletionValid(PracticeSubmission $submission): bool
    {
        if (! $submission->is_completed) {
            return false;
        }

        if (! in_array($submission->practice_key, self::COMPONENT_ATTEMPT_PRACTICE_KEYS, true)) {
            return true;
        }

        $feedback = is_array($submission->feedback)
            ? $submission->feedback
            : [];

        return ($feedback['_meta']['attempt_scope'] ?? null) === 'component';
    }
}
