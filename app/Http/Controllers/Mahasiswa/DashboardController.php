<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\CourseLesson;
use App\Models\PracticeSubmission;
use App\Models\UserLessonProgress;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $totalLessons = CourseLesson::count();

        $completedLessonIds = UserLessonProgress::where('user_id', $user->id)
            ->where('completed', true)
            ->pluck('course_lesson_id')
            ->toArray();

        $completedLessons = count($completedLessonIds);

        $progressPercentage = $totalLessons > 0
            ? round(($completedLessons / $totalLessons) * 100)
            : 0;

        $durationSeconds = UserLessonProgress::where('user_id', $user->id)
            ->sum('duration_seconds');

        $durationMinutes = round($durationSeconds / 60);

        $joinedClasses = $user->joinedClassGroups()
            ->with('dosen')
            ->latest('class_members.joined_at')
            ->get();

        $practiceCount = PracticeSubmission::where('user_id', $user->id)
            ->count();

        $averagePracticeScore = PracticeSubmission::where('user_id', $user->id)
            ->avg('score');

        $averagePracticeScore = $averagePracticeScore !== null
            ? round($averagePracticeScore)
            : null;

        $latestPractice = PracticeSubmission::with('lesson.module')
            ->where('user_id', $user->id)
            ->latest('submitted_at')
            ->first();

        $latestProgress = UserLessonProgress::with('lesson.module')
            ->where('user_id', $user->id)
            ->latest('updated_at')
            ->first();

        $allLessons = CourseLesson::query()
            ->join('course_modules', 'course_lessons.course_module_id', '=', 'course_modules.id')
            ->orderBy('course_modules.order_number')
            ->orderBy('course_lessons.order_number')
            ->select('course_lessons.*')
            ->get();

        $nextLesson = $allLessons->first(function ($lesson) use ($completedLessonIds) {
            return ! in_array($lesson->id, $completedLessonIds, true);
        });

        return view('mahasiswa.dashboard', compact(
            'user',
            'totalLessons',
            'completedLessons',
            'progressPercentage',
            'durationMinutes',
            'joinedClasses',
            'practiceCount',
            'averagePracticeScore',
            'latestPractice',
            'latestProgress',
            'nextLesson'
        ));
    }
}