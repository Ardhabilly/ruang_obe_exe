<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ClassGroupController extends Controller
{
    public function index()
    {
        $classGroups = ClassGroup::where('dosen_id', Auth::id())
            ->withCount('members')
            ->latest()
            ->get();

        return view('dosen.kelas.index', compact('classGroups'));
    }

    public function create()
    {
        return view('dosen.kelas.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'kkm' => ['required', 'integer', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        ClassGroup::create([
            'dosen_id' => Auth::id(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'kkm' => $validated['kkm'],
            'is_active' => $request->boolean('is_active'),
            'token' => $this->generateUniqueToken(),
        ]);

        return redirect()
            ->route('dosen.kelas.index')
            ->with('success', 'Kelas berhasil dibuat.');
    }

    public function show(ClassGroup $classGroup)
    {
        $this->ensureOwner($classGroup);

        $classGroup->load(['members.user:id,name,email']);

        $quizAttempts = QuizAttempt::query()
            ->with([
                'user:id,name,email',
                'quiz:id,title,type',
            ])
            ->where('class_group_id', $classGroup->id)
            ->whereIn('status', ['submitted', 'auto_submitted'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();

        $attemptsByStudent = $quizAttempts->groupBy('user_id');

        $studentSummaries = $classGroup->members
            ->filter(fn ($member) => $member->user !== null)
            ->map(function ($member) use ($attemptsByStudent) {
                $studentAttempts = $attemptsByStudent->get($member->user_id, collect());

                /*
                | Data sudah diurutkan dari percobaan terbaru. Karena itu,
                | item pertama pada setiap kelompok kuis adalah kondisi terbaru.
                */
                $latestAttemptsByQuiz = $studentAttempts
                    ->groupBy('quiz_id')
                    ->map(fn ($attempts) => $attempts->first());

                $completedQuizCount = $latestAttemptsByQuiz->count();
                $passedQuizCount = $latestAttemptsByQuiz
                    ->filter(fn ($attempt) => (bool) $attempt->is_passed)
                    ->count();

                $needsRemedialCount = $latestAttemptsByQuiz
                    ->filter(fn ($attempt) => ! $attempt->is_passed && (int) $attempt->attempt_number < 3)
                    ->count();

                $unpassedQuizCount = $latestAttemptsByQuiz
                    ->filter(fn ($attempt) => ! $attempt->is_passed)
                    ->count();

                return [
                    'student' => $member->user,
                    'attempt_count' => $studentAttempts->count(),
                    'completed_quiz_count' => $completedQuizCount,
                    'passed_quiz_count' => $passedQuizCount,
                    'needs_remedial_count' => $needsRemedialCount,
                    'unpassed_quiz_count' => $unpassedQuizCount,
                    'last_activity' => $studentAttempts->first(),
                ];
            })
            ->sortBy(fn ($summary) => mb_strtolower($summary['student']->name))
            ->values();

        $quizSummary = [
            'students_with_attempts' => $studentSummaries
                ->filter(fn ($summary) => $summary['attempt_count'] > 0)
                ->count(),
            'completed_quizzes' => $studentSummaries->sum('completed_quiz_count'),
            'passed_quizzes' => $studentSummaries->sum('passed_quiz_count'),
            'needs_remedial' => $studentSummaries->sum('needs_remedial_count'),
        ];

        return view('dosen.kelas.show', compact(
            'classGroup',
            'studentSummaries',
            'quizSummary'
        ));
    }

    public function studentQuizHistory(ClassGroup $classGroup, User $student)
    {
        $this->ensureOwner($classGroup);

        $isClassMember = $classGroup->members()
            ->where('user_id', $student->id)
            ->exists();

        abort_unless($isClassMember, 404, 'Mahasiswa tidak tergabung pada kelas ini.');

        $quizAttempts = QuizAttempt::query()
            ->with('quiz:id,title,type')
            ->where('class_group_id', $classGroup->id)
            ->where('user_id', $student->id)
            ->whereIn('status', ['submitted', 'auto_submitted'])
            ->orderBy('quiz_id')
            ->orderBy('attempt_number')
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get();

        $quizHistories = $quizAttempts
            ->groupBy('quiz_id')
            ->map(function ($attempts) {
                $orderedAttempts = $attempts
                    ->sortBy(function ($attempt) {
                        return sprintf(
                            '%04d-%014d-%010d',
                            (int) $attempt->attempt_number,
                            (int) optional($attempt->submitted_at)->timestamp,
                            (int) $attempt->id
                        );
                    })
                    ->values();

                $latestAttempt = $orderedAttempts->last();

                return [
                    'quiz' => $latestAttempt?->quiz,
                    'attempts' => $orderedAttempts,
                    'latest_attempt' => $latestAttempt,
                ];
            })
            ->sortBy(fn ($history) => mb_strtolower($history['quiz']?->title ?? ''))
            ->values();

        $latestAttempts = $quizHistories->pluck('latest_attempt');

        $historySummary = [
            'quiz_count' => $quizHistories->count(),
            'attempt_count' => $quizAttempts->count(),
            'passed_quiz_count' => $latestAttempts
                ->filter(fn ($attempt) => $attempt && $attempt->is_passed)
                ->count(),
            'needs_remedial_count' => $latestAttempts
                ->filter(fn ($attempt) => $attempt && ! $attempt->is_passed && (int) $attempt->attempt_number < 3)
                ->count(),
        ];

        return view('dosen.kelas.quiz-history', compact(
            'classGroup',
            'student',
            'quizHistories',
            'historySummary'
        ));
    }

    public function quizAttemptDetail(ClassGroup $classGroup, QuizAttempt $attempt)
    {
        $this->ensureOwner($classGroup);

        abort_if(
            (int) $attempt->class_group_id !== (int) $classGroup->id,
            404,
            'Data hasil kuis tidak ditemukan.'
        );

        abort_if(
            ! in_array($attempt->status, ['submitted', 'auto_submitted'], true),
            404,
            'Hasil kuis belum tersedia.'
        );

        $attempt->load([
            'user:id,name,email',
            'quiz:id,title,type,description',
            'classGroup:id,name,kkm',
            'responses.question:id,order_number,question_text,question_type,question_data,points',
        ]);

        return view('dosen.kelas.quiz-attempt-detail', compact(
            'classGroup',
            'attempt'
        ));
    }

    public function edit(ClassGroup $classGroup)
    {
        $this->ensureOwner($classGroup);

        return view('dosen.kelas.edit', compact('classGroup'));
    }

    public function update(Request $request, ClassGroup $classGroup)
    {
        $this->ensureOwner($classGroup);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'kkm' => ['required', 'integer', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $classGroup->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'kkm' => $validated['kkm'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('dosen.kelas.index')
            ->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(ClassGroup $classGroup)
    {
        $this->ensureOwner($classGroup);

        $classGroup->delete();

        return redirect()
            ->route('dosen.kelas.index')
            ->with('success', 'Kelas berhasil dihapus.');
    }

    public function regenerateToken(ClassGroup $classGroup)
    {
        $this->ensureOwner($classGroup);

        $classGroup->update([
            'token' => $this->generateUniqueToken(),
        ]);

        return back()->with('success', 'Token kelas berhasil diperbarui.');
    }

    private function ensureOwner(ClassGroup $classGroup): void
    {
        abort_if(
            $classGroup->dosen_id !== Auth::id(),
            403,
            'Anda tidak memiliki akses ke kelas ini.'
        );
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = strtoupper(Str::random(8));
        } while (ClassGroup::where('token', $token)->exists());

        return $token;
    }
}
