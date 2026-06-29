<?php

namespace App\Providers;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\UserLessonProgress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/*
|--------------------------------------------------------------------------
| MaterialSidebarServiceProvider
|--------------------------------------------------------------------------
| Menyediakan data sidebar materi pada layout global. Sidebar menampilkan
| penguncian bab berdasarkan subbab dan kuis bab yang telah lulus.
|--------------------------------------------------------------------------
*/
class MaterialSidebarServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('layouts.navigation', function ($view): void {
            $user = Auth::user();

            $sidebar = [
                'enabled' => false,
                'course' => null,
                'modules' => collect(),
                'active_lesson_id' => null,
                'active_module_id' => null,
                'open_module_ids' => [],
                'completed_lesson_ids' => [],
                'accessible_lesson_ids' => [],
                'quizzes_by_module' => collect(),
                'final_evaluations' => collect(),
            ];

            $isMaterialRoute = $user
                && $user->role === 'mahasiswa'
                && request()->routeIs('mahasiswa.materi.*');

            if (! $isMaterialRoute) {
                $view->with('materialSidebar', $sidebar);

                return;
            }

            $routeLesson = request()->route('lesson');
            $course = null;

            if ($routeLesson instanceof CourseLesson) {
                $routeLesson->loadMissing('module.course');

                $course = $routeLesson->module?->course;
                $sidebar['active_lesson_id'] = (int) $routeLesson->id;
                $sidebar['active_module_id'] = (int) $routeLesson->course_module_id;
            }

            if (! $course) {
                $course = Course::query()
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->first();
            }

            if (! $course) {
                $view->with('materialSidebar', $sidebar);

                return;
            }

            $modules = $course->modules()
                ->with([
                    'lessons' => function ($query) {
                        $query->orderBy('order_number');
                    },
                ])
                ->orderBy('order_number')
                ->get();

            $orderedLessons = $modules
                ->flatMap(fn ($module) => $module->lessons)
                ->values();

            $completedLessonIds = UserLessonProgress::query()
                ->where('user_id', $user->id)
                ->where('completed', true)
                ->pluck('course_lesson_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $joinedClassIds = $user->joinedClassGroups()
                ->pluck('class_groups.id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $moduleIds = $modules
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $chapterQuizzes = collect();
            $quizzesByModule = collect();
            $passedQuizModuleIds = [];

            if (! empty($joinedClassIds) && ! empty($moduleIds)) {
                $chapterQuizzes = Quiz::query()
                    ->withCount('questions')
                    ->whereIn('class_group_id', $joinedClassIds)
                    ->whereIn('course_module_id', $moduleIds)
                    ->where('type', 'kuis_bab')
                    ->where('is_active', true)
                    ->orderBy('course_module_id')
                    ->orderBy('id')
                    ->get();

                $passedQuizIds = QuizAttempt::query()
                    ->where('user_id', $user->id)
                    ->whereIn('quiz_id', $chapterQuizzes->pluck('id'))
                    ->whereIn('status', ['submitted', 'auto_submitted'])
                    ->where('is_passed', true)
                    ->pluck('quiz_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->all();

                $passedQuizModuleIds = $chapterQuizzes
                    ->filter(fn (Quiz $quiz) => in_array((int) $quiz->id, $passedQuizIds, true))
                    ->pluck('course_module_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->all();

                $quizzesByModule = $chapterQuizzes
                    ->map(function (Quiz $quiz) use ($modules, $completedLessonIds, $passedQuizIds) {
                        $module = $modules->firstWhere('id', $quiz->course_module_id);

                        $moduleLessonIds = $module
                            ? $module->lessons->pluck('id')->map(fn ($id) => (int) $id)->all()
                            : [];

                        $quiz->is_unlocked = ! empty($moduleLessonIds)
                            && empty(array_diff($moduleLessonIds, $completedLessonIds));

                        $quiz->is_passed = in_array((int) $quiz->id, $passedQuizIds, true);

                        $quiz->locked_reason = $quiz->is_unlocked
                            ? null
                            : 'Selesaikan seluruh subbab pada bab ini terlebih dahulu.';

                        return $quiz;
                    })
                    ->groupBy('course_module_id');
            }

            /*
             * Subbab pada bab selanjutnya hanya dapat diakses bila kuis pada
             * bab sebelumnya telah lulus KKM.
             */
            $accessibleLessonIds = [];
            $lockRemainingLessons = false;
            $previousModuleId = null;

            foreach ($modules as $module) {
                if (
                    $previousModuleId !== null
                    && ! in_array((int) $previousModuleId, $passedQuizModuleIds, true)
                ) {
                    $lockRemainingLessons = true;
                }

                foreach ($module->lessons as $lesson) {
                    if (! $lockRemainingLessons) {
                        $accessibleLessonIds[] = (int) $lesson->id;
                    }

                    if (! in_array((int) $lesson->id, $completedLessonIds, true)) {
                        $lockRemainingLessons = true;
                    }
                }

                $previousModuleId = (int) $module->id;
            }

            $finalEvaluations = collect();

            if (! empty($joinedClassIds)) {
                $allLessonIds = $orderedLessons
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $allLessonsComplete = ! empty($allLessonIds)
                    && empty(array_diff($allLessonIds, $completedLessonIds));

                $allChapterQuizzesPassed = ! empty($moduleIds)
                    && empty(array_diff($moduleIds, $passedQuizModuleIds));

                $finalEvaluations = Quiz::query()
                    ->withCount('questions')
                    ->whereIn('class_group_id', $joinedClassIds)
                    ->where('type', 'evaluasi_akhir')
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->get()
                    ->map(function (Quiz $quiz) use ($allLessonsComplete, $allChapterQuizzesPassed) {
                        $quiz->is_unlocked = $allLessonsComplete
                            && $allChapterQuizzesPassed;

                        $quiz->locked_reason = ! $allLessonsComplete
                            ? 'Selesaikan seluruh materi Bab 1 sampai Bab 4 terlebih dahulu.'
                            : (! $allChapterQuizzesPassed
                                ? 'Selesaikan dan capai KKM pada seluruh kuis bab terlebih dahulu.'
                                : null);

                        return $quiz;
                    });
            }

            $sidebar = [
                'enabled' => true,
                'course' => $course,
                'modules' => $modules,
                'active_lesson_id' => $sidebar['active_lesson_id'],
                'active_module_id' => $sidebar['active_module_id'],
                'open_module_ids' => $sidebar['active_module_id']
                    ? [$sidebar['active_module_id']]
                    : ($modules->isNotEmpty() ? [(int) $modules->first()->id] : []),
                'completed_lesson_ids' => $completedLessonIds,
                'accessible_lesson_ids' => $accessibleLessonIds,
                'quizzes_by_module' => $quizzesByModule,
                'final_evaluations' => $finalEvaluations,
            ];

            $view->with('materialSidebar', $sidebar);
        });
    }
}
