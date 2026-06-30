<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Models\ClassMember;
use App\Models\CourseLesson;
use App\Models\PracticeSubmission;
use App\Models\User;
use App\Models\UserLessonProgress;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $dosen = Auth::user();

        $classGroups = ClassGroup::where('dosen_id', $dosen->id)
            ->withCount('members')
            ->latest()
            ->get();

        $classIds = $classGroups->pluck('id');

        $studentIds = ClassMember::whereIn('class_group_id', $classIds)
            ->pluck('user_id')
            ->unique()
            ->values();

        $totalMahasiswa = $studentIds->count();
        $totalClasses = $classGroups->count();
        $activeClasses = $classGroups->where('is_active', true)->count();

        $totalLessons = CourseLesson::count();


        $latestPracticeSubmissions = PracticeSubmission::with(['user', 'lesson.module'])
            ->whereIn('user_id', $studentIds)
            ->latest('submitted_at')
            ->take(5)
            ->get();

        $latestProgress = UserLessonProgress::with(['user', 'lesson.module'])
            ->whereIn('user_id', $studentIds)
            ->latest('updated_at')
            ->take(5)
            ->get();

        $topMahasiswa = User::whereIn('id', $studentIds)
            ->withCount([
                'lessonProgress as completed_lessons_count' => function ($query) {
                    $query->where('completed', true);
                }
            ])
            ->get()
            ->map(function ($student) use ($totalLessons) {
                $student->progress_percentage = $totalLessons > 0
                    ? round(($student->completed_lessons_count / $totalLessons) * 100)
                    : 0;

                return $student;
            })
            ->sortByDesc('progress_percentage')
            ->take(5);

        return view('dosen.dashboard', compact(
            'dosen',
            'classGroups',
            'totalMahasiswa',
            'totalClasses',
            'activeClasses',
            'totalLessons',
            'latestPracticeSubmissions',
            'latestProgress',
            'topMahasiswa'
        ));
    }

}