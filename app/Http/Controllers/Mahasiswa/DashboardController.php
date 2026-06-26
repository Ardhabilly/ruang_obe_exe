<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\CourseLesson;
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

        $joinedClasses = $user->joinedClassGroups()
            ->with('dosen')
            ->latest('class_members.joined_at')
            ->get();

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
            'joinedClasses',
            'nextLesson'
        ));
    }
}