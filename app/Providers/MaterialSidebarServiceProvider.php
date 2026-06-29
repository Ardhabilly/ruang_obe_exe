<?php

namespace App\Providers;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\Quiz;
use App\Models\UserLessonProgress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/*
|--------------------------------------------------------------------------
| MaterialSidebarServiceProvider
|--------------------------------------------------------------------------
| Menyediakan data untuk sidebar materi pada layout global. Oleh karena itu,
| halaman subbab tidak perlu lagi memiliki sidebar sendiri.
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

            $accessibleLessonIds = [];
            $lockRemainingLessons = false;

            foreach ($orderedLessons as $lesson) {
                if (! $lockRemainingLessons) {
                    $accessibleLessonIds[] = (int) $lesson->id;
                }

                if (! in_array((int) $lesson->id, $completedLessonIds, true)) {
                    $lockRemainingLessons = true;
                }
            }

            $joinedClassIds = $user->joinedClassGroups()
                ->pluck('class_groups.id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $moduleIds = $modules
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $quizzesByModule = collect();

            if (! empty($joinedClassIds) && ! empty($moduleIds)) {
                $quizzesByModule = Quiz::query()
                    ->withCount('questions')
                    ->whereIn('class_group_id', $joinedClassIds)
                    ->whereIn('course_module_id', $moduleIds)
                    ->where('type', 'kuis_bab')
                    ->where('is_active', true)
                    ->orderBy('course_module_id')
                    ->get()
                    ->map(function (Quiz $quiz) use ($modules, $completedLessonIds) {
                        $module = $modules->firstWhere('id', $quiz->course_module_id);

                        $moduleLessonIds = $module
                            ? $module->lessons->pluck('id')->map(fn ($id) => (int) $id)->all()
                            : [];

                        $quiz->is_unlocked = ! empty($moduleLessonIds)
                            && empty(array_diff($moduleLessonIds, $completedLessonIds));

                        $quiz->locked_reason = $quiz->is_unlocked
                            ? null
                            : 'Selesaikan seluruh subbab pada bab ini terlebih dahulu.';

                        return $quiz;
                    })
                    ->groupBy('course_module_id');
            }

            $finalEvaluations = collect();

            if (! empty($joinedClassIds)) {
                $allLessonIds = $orderedLessons
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $finalEvaluations = Quiz::query()
                    ->withCount('questions')
                    ->whereIn('class_group_id', $joinedClassIds)
                    ->where('type', 'evaluasi_akhir')
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->get()
                    ->map(function (Quiz $quiz) use ($allLessonIds, $completedLessonIds) {
                        $quiz->is_unlocked = ! empty($allLessonIds)
                            && empty(array_diff($allLessonIds, $completedLessonIds));

                        $quiz->locked_reason = $quiz->is_unlocked
                            ? null
                            : 'Selesaikan seluruh materi Bab 1 sampai Bab 4 terlebih dahulu.';

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
