<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\CourseLesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\UserLessonProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    public function instruction(Quiz $quiz)
    {
        $user = Auth::user();

        $this->ensureMember($quiz);

        if (! $this->isQuizUnlocked($quiz, $user->id)) {
            return redirect()
                ->route('mahasiswa.materi.index')
                ->with('warning', 'Kuis masih terkunci. Selesaikan seluruh materi pada bab terkait terlebih dahulu.');
        }

        $quiz->load(['classGroup', 'module', 'questions']);

        $inProgressAttempt = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->latest()
            ->first();

        if ($inProgressAttempt && now()->gte($inProgressAttempt->expires_at)) {
            $this->finalizeAttempt($inProgressAttempt, 'auto_submitted');
            $inProgressAttempt = null;
        }

        $submittedAttempts = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['submitted', 'auto_submitted'])
            ->orderBy('attempt_number')
            ->get();

        $latestAttempt = $submittedAttempts->sortByDesc('attempt_number')->first();
        $passedAttempt = $submittedAttempts->firstWhere('is_passed', true);
        $attemptsUsed = $submittedAttempts->count();
        $nextAttemptNumber = $attemptsUsed + 1;

        /*
        | Mahasiswa dapat mengulang kuis selama belum memenuhi KKM.
        | Percobaan tambahan ditutup hanya setelah terdapat satu percobaan lulus.
        */
        $canStartAttempt = ! $inProgressAttempt && ! $passedAttempt;

        return view('mahasiswa.kuis.instruction', compact(
            'quiz',
            'inProgressAttempt',
            'latestAttempt',
            'passedAttempt',
            'attemptsUsed',
            'nextAttemptNumber',
            'canStartAttempt'
        ));
    }

    public function start(Quiz $quiz)
    {
        $user = Auth::user();

        $this->ensureMember($quiz);

        if (! $this->isQuizUnlocked($quiz, $user->id)) {
            return redirect()
                ->route('mahasiswa.materi.index')
                ->with('warning', 'Kuis masih terkunci.');
        }

        $quiz->load(['questions', 'classGroup']);

        $inProgressAttempt = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->latest()
            ->first();

        if ($inProgressAttempt) {
            if (now()->lt($inProgressAttempt->expires_at)) {
                return redirect()->route('mahasiswa.kuis.attempt', $inProgressAttempt);
            }

            $this->finalizeAttempt($inProgressAttempt, 'auto_submitted');
        }

        $submittedAttempts = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['submitted', 'auto_submitted'])
            ->orderBy('attempt_number')
            ->get();

        $passedAttempt = $submittedAttempts->firstWhere('is_passed', true);

        if ($passedAttempt) {
            return redirect()
                ->route('mahasiswa.kuis.result', $passedAttempt)
                ->with('success', 'Anda sudah lulus kuis ini. Percobaan tambahan tidak diperlukan.');
        }

        /*
        | Tidak ada batas jumlah remedial. Mahasiswa dapat memulai
        | percobaan baru sampai memperoleh nilai minimal sebesar KKM.
        */
        $submittedCount = $submittedAttempts->count();

        $attempt = DB::transaction(function () use ($quiz, $user, $submittedCount) {
            $attempt = QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'class_group_id' => $quiz->class_group_id,
                'user_id' => $user->id,
                'attempt_number' => $submittedCount + 1,
                'raw_score' => 0,
                'score' => 0,
                'max_score' => $quiz->questions->sum('points'),
                'correct_answers' => 0,
                'total_questions' => $quiz->questions->count(),
                'duration_seconds' => 0,
                'is_passed' => false,
                'status' => 'in_progress',
                'started_at' => now(),
                'expires_at' => now()->addMinutes($quiz->duration_minutes),
            ]);

            foreach ($quiz->questions as $question) {
                $attempt->responses()->create([
                    'quiz_question_id' => $question->id,
                    'response_value' => null,
                    'canvas_data' => null,
                    'is_marked_doubtful' => false,
                    'is_answered' => false,
                    'is_correct' => false,
                    'points_earned' => 0,
                    'feedback' => null,
                ]);
            }

            return $attempt;
        });

        return redirect()->route('mahasiswa.kuis.attempt', $attempt);
    }

    public function attempt(QuizAttempt $attempt)
    {
        $this->ensureAttemptOwner($attempt);

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('mahasiswa.kuis.result', $attempt);
        }

        if (now()->gte($attempt->expires_at)) {
            $this->finalizeAttempt($attempt, 'auto_submitted');

            return redirect()
                ->route('mahasiswa.kuis.result', $attempt)
                ->with('warning', 'Waktu kuis sudah habis. Jawaban dikumpulkan otomatis.');
        }

        $attempt->load([
            'quiz.classGroup',
            'quiz.module',
            'quiz.questions',
            'responses',
        ]);

        $responsesByQuestion = $attempt->responses->keyBy('quiz_question_id');

        $remainingSeconds = max(0, (int) floor(now()->diffInSeconds($attempt->expires_at, false)));

        return view('mahasiswa.kuis.attempt', compact(
            'attempt',
            'remainingSeconds',
            'responsesByQuestion'
        ));
    }

    public function save(Request $request, QuizAttempt $attempt)
    {
        $this->ensureAttemptOwner($attempt);

        if ($attempt->status !== 'in_progress') {
            return response()->json([
                'saved' => false,
                'message' => 'Attempt kuis sudah selesai.',
            ], 409);
        }

        if (now()->gte($attempt->expires_at)) {
            $this->finalizeAttempt($attempt, 'auto_submitted');

            return response()->json([
                'saved' => false,
                'expired' => true,
                'redirect' => route('mahasiswa.kuis.result', $attempt),
                'message' => 'Waktu kuis sudah habis.',
            ], 409);
        }

        $attempt->load(['quiz.questions']);

        $responsesInput = $request->input('responses', []);

        foreach ($attempt->quiz->questions as $question) {
            $payload = $responsesInput[$question->id] ?? [];

            if (! is_array($payload)) {
                $payload = [];
            }

            $existingResponse = $attempt->responses()
                ->where('quiz_question_id', $question->id)
                ->first();

            $canvasData = $existingResponse?->canvas_data;

            if (array_key_exists('canvas_data', $payload)) {
                $canvasData = trim((string) $payload['canvas_data']);
                $canvasData = $canvasData !== '' ? $canvasData : null;
            }

            unset($payload['step_file'], $payload['canvas_data']);

            $attempt->responses()->updateOrCreate(
                [
                    'quiz_question_id' => $question->id,
                ],
                [
                    'response_value' => $payload,
                    'canvas_data' => $canvasData,
                    'is_marked_doubtful' => (bool) ($payload['is_marked_doubtful'] ?? false),
                    'is_answered' => $this->isAnswered($question, $payload),
                ]
            );
        }

        return response()->json([
            'saved' => true,
            'saved_at' => now()->format('H:i:s'),
        ]);
    }

    public function submit(Request $request, QuizAttempt $attempt)
    {
        $this->ensureAttemptOwner($attempt);

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('mahasiswa.kuis.result', $attempt);
        }

        $attempt->load('quiz.questions');

        $responsesInput = $request->input('responses', []);

        foreach ($attempt->quiz->questions as $question) {
            $payload = $responsesInput[$question->id] ?? [];

            if (! is_array($payload)) {
                $payload = [];
            }

            $existingResponse = $attempt->responses()
                ->where('quiz_question_id', $question->id)
                ->first();

            $canvasData = $existingResponse?->canvas_data;

            if (array_key_exists('canvas_data', $payload)) {
                $canvasData = trim((string) $payload['canvas_data']);
                $canvasData = $canvasData !== '' ? $canvasData : null;
            }

            unset($payload['step_file'], $payload['canvas_data']);

            $attempt->responses()->updateOrCreate(
                [
                    'quiz_question_id' => $question->id,
                ],
                [
                    'response_value' => $payload,
                    'canvas_data' => $canvasData,
                    'is_marked_doubtful' => (bool) ($payload['is_marked_doubtful'] ?? false),
                    'is_answered' => $this->isAnswered($question, $payload),
                ]
            );
        }

        $status = $request->boolean('auto_submitted') || now()->gte($attempt->expires_at)
            ? 'auto_submitted'
            : 'submitted';

        $attempt->refresh();
        $this->finalizeAttempt($attempt, $status);

        return redirect()
            ->route('mahasiswa.kuis.result', $attempt)
            ->with($status === 'auto_submitted' ? 'warning' : 'success', $status === 'auto_submitted'
                ? 'Waktu habis. Kuis dikumpulkan otomatis.'
                : 'Kuis berhasil dikumpulkan.');
    }

    public function result(QuizAttempt $attempt)
    {
        $this->ensureAttemptOwner($attempt);

        $attempt->load([
            'quiz.classGroup',
            'quiz.module',
            'quiz.questions',
            'responses.question',
        ]);

        $attemptHistory = QuizAttempt::query()
            ->where('quiz_id', $attempt->quiz_id)
            ->where('user_id', $attempt->user_id)
            ->whereIn('status', ['submitted', 'auto_submitted'])
            ->get(['attempt_number', 'is_passed']);

        $hasPassedAttempt = $attemptHistory
            ->contains(fn (QuizAttempt $item) => (bool) $item->is_passed);

        /*
        | Remedial selalu tersedia selama belum ada satu pun percobaan lulus.
        */
        $canRemedial = ! $hasPassedAttempt;
        $nextAttemptNumber = ((int) $attemptHistory->max('attempt_number')) + 1;
        $rawScore = $attempt->raw_score ?? $attempt->score;
        $remedialScoreCapped = $attempt->attempt_number > 1
            && $attempt->is_passed
            && $rawScore > $attempt->score;

        return view('mahasiswa.kuis.result', compact(
            'attempt',
            'canRemedial',
            'nextAttemptNumber',
            'rawScore',
            'remedialScoreCapped'
        ));
    }

    private function ensureMember(Quiz $quiz): void
    {
        $isMember = Auth::user()
            ->joinedClassGroups()
            ->where('class_groups.id', $quiz->class_group_id)
            ->exists();

        abort_if(! $isMember, 403, 'Anda tidak memiliki akses ke kuis ini.');
    }

    private function ensureAttemptOwner(QuizAttempt $attempt): void
    {
        abort_if($attempt->user_id !== Auth::id(), 403, 'Anda tidak memiliki akses ke attempt kuis ini.');
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

    private function finalizeAttempt(QuizAttempt $attempt, string $status): void
    {
        if ($attempt->status !== 'in_progress') {
            return;
        }

        $attempt->loadMissing([
            'quiz.questions',
            'responses',
            'classGroup',
        ]);

        $responsesByQuestion = $attempt->responses->keyBy('quiz_question_id');

        $rawScore = 0;
        $correctAnswers = 0;
        $totalQuestions = $attempt->quiz->questions->count();
        $maxScore = $attempt->quiz->questions->sum('points');

        foreach ($attempt->quiz->questions as $question) {
            $response = $responsesByQuestion->get($question->id);
            $payload = $response?->response_value ?? [];

            if (! is_array($payload)) {
                $payload = [];
            }

            $scored = $this->scoreQuestion($question, $payload);

            if ($scored['is_correct']) {
                $correctAnswers++;
            }

            $rawScore += $scored['points_earned'];

            $attempt->responses()->updateOrCreate(
                [
                    'quiz_question_id' => $question->id,
                ],
                [
                    'response_value' => $payload,
                    'is_marked_doubtful' => (bool) ($payload['is_marked_doubtful'] ?? false),
                    'is_answered' => $scored['is_answered'],
                    'is_correct' => $scored['is_correct'],
                    'points_earned' => $scored['points_earned'],
                    'feedback' => $scored['feedback'],
                ]
            );
        }

        $durationSeconds = $attempt->started_at
            ? $attempt->started_at->diffInSeconds(now())
            : 0;

        $kkm = (int) $attempt->classGroup->kkm;
        $isPassed = $rawScore >= $kkm;
        $recordedScore = $this->calculateRecordedScore(
            $rawScore,
            (int) $attempt->attempt_number,
            $kkm
        );

        $attempt->update([
            'raw_score' => $rawScore,
            'score' => $recordedScore,
            'max_score' => $maxScore,
            'correct_answers' => $correctAnswers,
            'total_questions' => $totalQuestions,
            'duration_seconds' => $durationSeconds,
            'is_passed' => $isPassed,
            'status' => $status,
            'submitted_at' => now(),
        ]);
    }

    private function calculateRecordedScore(int $rawScore, int $attemptNumber, int $kkm): int
    {
        /*
        | Percobaan pertama mencatat nilai asli.
        | Saat lulus melalui remedial, nilai tercatat maksimal sebesar KKM.
        | Percobaan remedial yang belum lulus tetap menyimpan nilai mentahnya.
        */
        if ($attemptNumber > 1 && $rawScore >= $kkm) {
            return $kkm;
        }

        return $rawScore;
    }

    private function scoreQuestion($question, array $payload): array
    {
        $isAnswered = $this->isAnswered($question, $payload);
        $isCorrect = false;

        switch ($question->question_type) {
            case 'checkbox':
                $selected = collect($payload['selected'] ?? [])
                    ->map(fn ($item) => strtoupper(trim((string) $item)))
                    ->sort()
                    ->values()
                    ->all();

                $key = collect($question->answer_key['selected'] ?? [])
                    ->map(fn ($item) => strtoupper(trim((string) $item)))
                    ->sort()
                    ->values()
                    ->all();

                $isCorrect = $selected === $key;
                break;

            case 'short_text':
            case 'math_notation':
                $answer = $this->normalizeText($payload['answer'] ?? '');

                $acceptedAnswers = $question->accepted_answers
                    ?: [$question->answer_key['answer'] ?? ''];

                $acceptedAnswers = collect($acceptedAnswers)
                    ->map(fn ($item) => $this->normalizeText($item))
                    ->all();

                $isCorrect = in_array($answer, $acceptedAnswers, true);
                break;

            case 'variable_values':
                $isCorrect = $this->compareVariableValues(
                    $question,
                    $payload['answers'] ?? [],
                    $question->accepted_answers ?? []
                );
                break;
            case 'canvas_final_answer':
                $finalAnswer = $payload['final'] ?? [];
                $accepted = $question->accepted_answers ?? [];

                $isCorrect = true;

                foreach ($accepted as $field => $acceptedValues) {
                    $studentValue = $this->normalizeScalar($finalAnswer[$field] ?? '');

                    $normalizedAccepted = collect($acceptedValues)
                        ->map(fn ($item) => $this->normalizeScalar($item))
                        ->all();

                    if (! in_array($studentValue, $normalizedAccepted, true)) {
                        $isCorrect = false;
                        break;
                    }
                }
                break;

            case 'obe_matrix_operation':
                $operation = $this->normalizeObeOperation($payload['operation'] ?? '');

                $acceptedOperations = $question->accepted_answers
                    ?: [$question->answer_key['operation'] ?? ''];

                $acceptedOperations = collect($acceptedOperations)
                    ->map(fn ($item) => $this->normalizeObeOperation($item))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $operationCorrect = in_array($operation, $acceptedOperations, true);

                $matrixCorrect = $this->compareMatrix(
                    $payload['result_matrix'] ?? [],
                    $question->answer_key['matrix'] ?? []
                );

                $isCorrect = $operationCorrect && $matrixCorrect;
                break;

            case 'gauss_jordan':
                $matrixCorrect = $this->compareGaussJordanMatrix(
                    $payload['reduced_matrix'] ?? [],
                    $question->answer_key['rref_matrix'] ?? []
                );

                $finalCorrect = $this->compareGaussFinalAnswers(
                    $payload['final'] ?? [],
                    $question->accepted_answers ?? []
                );

                $isCorrect = $matrixCorrect && $finalCorrect;
                break;
            case 'gauss_elimination':
                $matrixCorrect = $this->isValidGaussEchelonMatrix(
                    $payload['echelon_matrix'] ?? [],
                    $question->answer_key['initial_matrix'] ?? []
                );

                $finalCorrect = $this->compareGaussFinalAnswers(
                    $payload['final'] ?? [],
                    $question->accepted_answers ?? []
                );

                $isCorrect = $matrixCorrect && $finalCorrect;
                break;
            case 'multi_short_text':
                $answers = $payload['answers'] ?? [];
                $acceptedAnswers = $question->accepted_answers ?? [];
                $isCorrect = is_array($answers) && is_array($acceptedAnswers) && ! empty($acceptedAnswers);

                if ($isCorrect) {
                    foreach ($acceptedAnswers as $field => $acceptedValues) {
                        $studentAnswer = $this->normalizeText($answers[$field] ?? '');
                        $values = is_array($acceptedValues) ? $acceptedValues : [$acceptedValues];
                        $normalizedAccepted = collect($values)
                            ->map(fn ($item) => $this->normalizeText($item))
                            ->all();

                        if (! in_array($studentAnswer, $normalizedAccepted, true)) {
                            $isCorrect = false;
                            break;
                        }
                    }
                }
                break;
            case 'matrix':
            case 'augmented_matrix':
                $isCorrect = $this->compareMatrix(
                    $payload['matrix'] ?? [],
                    $question->answer_key['matrix'] ?? []
                );
                break;

            case 'matrix_equation':
                $isCorrect = $this->compareMatrix(
                    $payload['A'] ?? [],
                    $question->answer_key['A'] ?? []
                ) && $this->compareVector(
                    $payload['b'] ?? [],
                    $question->answer_key['b'] ?? []
                );
                break;
        }

        return [
            'is_answered' => $isAnswered,
            'is_correct' => $isCorrect,
            'points_earned' => $isCorrect ? $question->points : 0,
            'feedback' => $isCorrect
                ? 'Jawaban benar.'
                : 'Jawaban belum tepat. '.$question->explanation,
        ];
    }

    private function isAnswered($question, array $payload): bool
    {
        return match ($question->question_type) {
            'checkbox' => ! empty($payload['selected']),
            'short_text', 'math_notation' => trim((string) ($payload['answer'] ?? '')) !== '',
            'variable_values' => $this->hasCompleteVariableResponse($question, $payload),
            'multi_short_text' => $this->hasCompleteMultiTextResponse($question, $payload),            'canvas_final_answer' => $this->hasAnyValue($payload['final'] ?? []),
            'obe_matrix_operation' => trim((string) ($payload['operation'] ?? '')) !== ''
                && $this->hasAnyValue($payload['result_matrix'] ?? []),
            'gauss_elimination', 'gauss_jordan' => $this->hasCompleteGaussResponse($question, $payload),            'matrix', 'augmented_matrix' => $this->hasAnyValue($payload['matrix'] ?? []),
            'matrix_equation' => $this->hasAnyValue($payload['A'] ?? []) || $this->hasAnyValue($payload['b'] ?? []),
            default => false,
        };
    }

    private function variableFields($question): array
    {
        $rawFields = $question->question_data['fields'] ?? ['x', 'y', 'z'];
        $legacyLabels = $question->question_data['labels'] ?? [];

        if (! is_array($rawFields)) {
            $rawFields = ['x', 'y', 'z'];
        }

        if (! is_array($legacyLabels)) {
            $legacyLabels = [];
        }

        $normalized = [];
        $usedKeys = [];

        foreach (array_values($rawFields) as $index => $field) {
            if (is_array($field)) {
                $key = trim((string) ($field['key'] ?? ''));
                $label = trim((string) ($field['label'] ?? $key));
            } else {
                $key = trim((string) $field);
                $label = trim((string) ($legacyLabels[$key] ?? $key));
            }

            if ($key === '' || ! preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,49}$/', $key)) {
                $key = 'v' . ($index + 1);
            }

            if ($label === '') {
                $label = $key;
            }

            if (isset($usedKeys[$key])) {
                continue;
            }

            $usedKeys[$key] = true;
            $normalized[] = [
                'key' => $key,
                'label' => $label,
            ];
        }

        return ! empty($normalized)
            ? $normalized
            : [
                ['key' => 'x', 'label' => 'x'],
                ['key' => 'y', 'label' => 'y'],
                ['key' => 'z', 'label' => 'z'],
            ];
    }

    private function hasCompleteVariableResponse($question, array $payload): bool
    {
        $answers = $payload['answers'] ?? [];

        if (! is_array($answers)) {
            return false;
        }

        foreach ($this->variableFields($question) as $field) {
            if (trim((string) ($answers[$field['key']] ?? '')) === '') {
                return false;
            }
        }

        return true;
    }

    private function compareVariableValues($question, array $studentAnswers, $acceptedAnswers): bool
    {
        if (! is_array($acceptedAnswers) || empty($acceptedAnswers)) {
            return false;
        }

        foreach ($this->variableFields($question) as $field) {
            $key = $field['key'];
            $studentValue = $this->parseGaussNumber($studentAnswers[$key] ?? null);

            if ($studentValue === null) {
                return false;
            }

            $expectedValues = $acceptedAnswers[$key] ?? [];
            $expectedValues = is_array($expectedValues) ? $expectedValues : [$expectedValues];

            $matched = false;

            foreach ($expectedValues as $expectedValue) {
                $numericExpected = $this->parseGaussNumber($expectedValue);

                if ($numericExpected !== null && abs($studentValue - $numericExpected) < 0.000001) {
                    $matched = true;
                    break;
                }
            }

            if (! $matched) {
                return false;
            }
        }

        return true;
    }
    private function hasCompleteMultiTextResponse($question, array $payload): bool
    {
        $answers = $payload['answers'] ?? [];
        $fields = $question->question_data['fields'] ?? array_keys($question->accepted_answers ?? []);

        if (! is_array($answers) || ! is_array($fields) || empty($fields)) {
            return false;
        }

        foreach ($fields as $field) {
            if (trim((string) ($answers[$field] ?? '')) === '') {
                return false;
            }
        }

        return true;
    }
    private function hasAnyValue($value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->hasAnyValue($item)) {
                    return true;
                }
            }

            return false;
        }

        return trim((string) $value) !== '';
    }

    private function compareMatrix(array $studentMatrix, array $answerMatrix): bool
    {
        foreach ($answerMatrix as $rowIndex => $row) {
            foreach ($row as $columnIndex => $expectedValue) {
                $studentValue = $studentMatrix[$rowIndex][$columnIndex] ?? null;

                if ($this->normalizeScalar($studentValue) !== $this->normalizeScalar($expectedValue)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function compareVector(array $studentVector, array $answerVector): bool
    {
        foreach ($answerVector as $index => $expectedValue) {
            $studentValue = $studentVector[$index] ?? null;

            if ($this->normalizeScalar($studentValue) !== $this->normalizeScalar($expectedValue)) {
                return false;
            }
        }

        return true;
    }

    private function hasCompleteGaussResponse($question, array $payload): bool
    {
        $data = $question->question_data ?? [];
        $rows = (int) ($data['rows'] ?? 0);
        $columns = (int) ($data['columns'] ?? 0);
        $matrixKey = $question->question_type === 'gauss_jordan'
            ? 'reduced_matrix'
            : 'echelon_matrix';
        $matrix = $payload[$matrixKey] ?? [];        $final = $payload['final'] ?? [];
        $finalFields = $data['final_fields'] ?? array_keys($question->accepted_answers ?? []);

        if ($rows < 1 || $columns < 1 || ! is_array($matrix) || ! is_array($final)) {
            return false;
        }

        for ($row = 0; $row < $rows; $row++) {
            for ($column = 0; $column < $columns; $column++) {
                if (trim((string) ($matrix[$row][$column] ?? '')) === '') {
                    return false;
                }
            }
        }

        foreach ($finalFields as $field) {
            if (trim((string) ($final[$field] ?? '')) === '') {
                return false;
            }
        }

        return true;
    }

    private function compareGaussFinalAnswers(array $studentFinal, $acceptedAnswers): bool
    {
        if (! is_array($acceptedAnswers) || empty($acceptedAnswers)) {
            return false;
        }

        foreach ($acceptedAnswers as $field => $acceptedValues) {
            $studentValue = $this->parseGaussNumber($studentFinal[$field] ?? null);

            if ($studentValue === null) {
                return false;
            }

            $values = is_array($acceptedValues) ? $acceptedValues : [$acceptedValues];
            $matches = false;

            foreach ($values as $acceptedValue) {
                $numericAccepted = $this->parseGaussNumber($acceptedValue);

                if ($numericAccepted !== null && abs($studentValue - $numericAccepted) < 0.000001) {
                    $matches = true;
                    break;
                }
            }

            if (! $matches) {
                return false;
            }
        }

        return true;
    }

    private function compareGaussJordanMatrix(array $studentMatrix, $expectedMatrix): bool
    {
        if (! is_array($expectedMatrix) || empty($expectedMatrix)) {
            return false;
        }

        $student = $this->toNumericMatrix($studentMatrix, $expectedMatrix);
        $expected = $this->toNumericMatrix($expectedMatrix, $expectedMatrix);

        if ($student === null || $expected === null) {
            return false;
        }

        return $this->matricesAreClose($student, $expected);
    }
    private function isValidGaussEchelonMatrix(array $studentMatrix, $initialMatrix): bool
    {
        if (! is_array($initialMatrix) || empty($initialMatrix)) {
            return false;
        }

        $student = $this->toNumericMatrix($studentMatrix, $initialMatrix);
        $initial = $this->toNumericMatrix($initialMatrix, $initialMatrix);

        if ($student === null || $initial === null) {
            return false;
        }

        return $this->isRowEchelonForm($student)
            && $this->matricesAreClose(
                $this->reducedRowEchelonForm($student),
                $this->reducedRowEchelonForm($initial)
            );
    }

    private function toNumericMatrix($matrix, array $referenceMatrix): ?array
    {
        if (! is_array($matrix) || count($matrix) !== count($referenceMatrix)) {
            return null;
        }

        $numericMatrix = [];

        foreach ($referenceMatrix as $rowIndex => $referenceRow) {
            if (! is_array($referenceRow) || ! isset($matrix[$rowIndex]) || ! is_array($matrix[$rowIndex])) {
                return null;
            }

            if (count($matrix[$rowIndex]) !== count($referenceRow)) {
                return null;
            }

            $numericRow = [];

            foreach ($referenceRow as $columnIndex => $unusedValue) {
                $number = $this->parseGaussNumber($matrix[$rowIndex][$columnIndex] ?? null);

                if ($number === null) {
                    return null;
                }

                $numericRow[] = $number;
            }

            $numericMatrix[] = $numericRow;
        }

        return $numericMatrix;
    }

    private function parseGaussNumber($value): ?float
    {
        $value = trim((string) $value);
        $value = str_replace(['−', '–', '—'], '-', $value);
        $value = str_ireplace('per', '/', $value);
        $value = str_replace(',', '.', $value);
        $value = preg_replace('/\s+/', '', $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^([+-]?(?:\d+(?:\.\d*)?|\.\d+))\/([+-]?(?:\d+(?:\.\d*)?|\.\d+))$/', $value, $matches)) {
            $denominator = (float) $matches[2];

            if (abs($denominator) < 0.000000001) {
                return null;
            }

            return (float) $matches[1] / $denominator;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function isRowEchelonForm(array $matrix): bool
    {
        $epsilon = 0.000001;
        $lastLeadingColumn = -1;
        $encounteredZeroRow = false;
        $rowCount = count($matrix);

        foreach ($matrix as $rowIndex => $row) {
            $leadingColumn = -1;

            foreach ($row as $columnIndex => $value) {
                if (abs($value) >= $epsilon) {
                    $leadingColumn = $columnIndex;
                    break;
                }
            }

            if ($leadingColumn === -1) {
                $encounteredZeroRow = true;
                continue;
            }

            if ($encounteredZeroRow || $leadingColumn <= $lastLeadingColumn) {
                return false;
            }

            for ($below = $rowIndex + 1; $below < $rowCount; $below++) {
                if (abs($matrix[$below][$leadingColumn] ?? 0) >= $epsilon) {
                    return false;
                }
            }

            $lastLeadingColumn = $leadingColumn;
        }

        return true;
    }

    private function reducedRowEchelonForm(array $matrix): array
    {
        $epsilon = 0.000001;
        $rowCount = count($matrix);
        $columnCount = count($matrix[0] ?? []);
        $pivotRow = 0;

        for ($column = 0; $column < $columnCount && $pivotRow < $rowCount; $column++) {
            $bestRow = $pivotRow;

            for ($row = $pivotRow + 1; $row < $rowCount; $row++) {
                if (abs($matrix[$row][$column]) > abs($matrix[$bestRow][$column])) {
                    $bestRow = $row;
                }
            }

            if (abs($matrix[$bestRow][$column]) < $epsilon) {
                continue;
            }

            if ($bestRow !== $pivotRow) {
                [$matrix[$bestRow], $matrix[$pivotRow]] = [$matrix[$pivotRow], $matrix[$bestRow]];
            }

            $pivot = $matrix[$pivotRow][$column];

            for ($index = 0; $index < $columnCount; $index++) {
                $matrix[$pivotRow][$index] /= $pivot;
            }

            for ($row = 0; $row < $rowCount; $row++) {
                if ($row === $pivotRow) {
                    continue;
                }

                $factor = $matrix[$row][$column];

                if (abs($factor) < $epsilon) {
                    continue;
                }

                for ($index = 0; $index < $columnCount; $index++) {
                    $matrix[$row][$index] -= $factor * $matrix[$pivotRow][$index];
                }
            }

            $pivotRow++;
        }

        foreach ($matrix as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                if (abs($value) < $epsilon) {
                    $matrix[$rowIndex][$columnIndex] = 0.0;
                }
            }
        }

        return $matrix;
    }

    private function matricesAreClose(array $left, array $right): bool
    {
        if (count($left) !== count($right)) {
            return false;
        }

        foreach ($left as $rowIndex => $row) {
            if (count($row) !== count($right[$rowIndex] ?? [])) {
                return false;
            }

            foreach ($row as $columnIndex => $value) {
                if (abs($value - $right[$rowIndex][$columnIndex]) >= 0.000001) {
                    return false;
                }
            }
        }

        return true;
    }
    private function normalizeScalar($value): string
    {
        $value = trim((string) $value);
        $value = str_replace(['−', '–', '—'], '-', $value);
        $value = str_replace(',', '.', $value);

        if ($value === '') {
            return '';
        }

        if (is_numeric($value)) {
            $number = (float) $value;

            if (abs($number - round($number)) < 0.000001) {
                return (string) (int) round($number);
            }

            return rtrim(rtrim(sprintf('%.10F', $number), '0'), '.');
        }

        return $this->normalizeText($value);
    }

    private function normalizeObeOperation($value): string
    {
        $value = strtolower(trim((string) $value));

        $value = str_replace(
            ['₁', '₂', '₃', '₄', '−', '–', '—', '↔', '⟷', '⇄', '←', '⟵', '⟸', '×', '·'],
            ['1', '2', '3', '4', '-', '-', '-', '<->', '<->', '<->', '<-', '<-', '<-', '', ''],
            $value
        );

        $value = str_ireplace(['baris'], ['b'], $value);
        $value = str_replace(['*', '_', '(', ')'], '', $value);
        $value = preg_replace('/\s+/', '', $value);

        return str_replace('+-', '-', $value);
    }

    private function normalizeText($value): string
    {
        $value = strtolower(trim((string) $value));

        $value = str_replace(
            ['√', '−', '–', '—', '₁', '₂', '₃', '₄'],
            ['sqrt', '-', '-', '-', '1', '2', '3', '4'],
            $value
        );

        $value = str_replace(['_', '*', '(', ')'], '', $value);
        $value = preg_replace('/\s+/', '', $value);
        $value = str_replace('+-', '-', $value);

        return $value;
    }
}