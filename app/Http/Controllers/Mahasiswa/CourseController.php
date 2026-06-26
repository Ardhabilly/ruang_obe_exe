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

class CourseController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $courses = Course::with(['modules.lessons'])
            ->where('is_active', true)
            ->get();

        $totalLessons = CourseLesson::count();

        $completedLessonIds = UserLessonProgress::where('user_id', $user->id)
            ->where('completed', true)
            ->pluck('course_lesson_id')
            ->toArray();

        $completedLessons = count($completedLessonIds);

        $progressPercentage = $totalLessons > 0
            ? round(($completedLessons / $totalLessons) * 100)
            : 0;

        return view('mahasiswa.materi.index', compact(
            'courses',
            'totalLessons',
            'completedLessons',
            'completedLessonIds',
            'progressPercentage'
        ));
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

        $accessibleLessonIds = $this->getAccessibleLessonIds($allLessons, $completedLessonIds);

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
            'quizzesByModule'
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

        $progress->update([
            'completed' => true,
            'completed_at' => now(),
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

    private function getAccessibleLessonIds(Collection $allLessons, array $completedLessonIds): array
    {
        $accessibleLessonIds = [];

        foreach ($allLessons as $lesson) {
            $accessibleLessonIds[] = $lesson->id;

            if (! in_array($lesson->id, $completedLessonIds, true)) {
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