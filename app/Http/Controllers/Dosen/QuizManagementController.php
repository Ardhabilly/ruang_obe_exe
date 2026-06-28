<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Models\CourseModule;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuizManagementController extends Controller
{
    private const BASIC_QUESTION_TYPES = [
        'short_text',
        'math_notation',
        'variable_values',
        'checkbox',
    ];

    public function index()
    {
        return redirect()->route('dosen.kelas.index');
    }

    public function create(ClassGroup $classGroup)
    {
        $this->ensureClassGroupOwner($classGroup);

        $modules = CourseModule::query()
            ->orderBy('order_number')
            ->get(['id', 'title', 'order_number']);

        return view('dosen.kuis.create', compact('classGroup', 'modules'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_group_id' => ['required', 'integer'],
            'type' => ['required', Rule::in(['kuis_bab', 'evaluasi_akhir'])],
            'course_module_id' => [
                'nullable',
                'required_if:type,kuis_bab',
                'integer',
                Rule::exists('course_modules', 'id'),
            ],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:1000'],
            'instruction' => ['nullable', 'string', 'max:3000'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:180'],
        ]);

        $classGroup = ClassGroup::query()
            ->where('dosen_id', Auth::id())
            ->find($validated['class_group_id']);

        abort_if(! $classGroup, 403, 'Anda tidak memiliki akses ke kelas tersebut.');

        $type = $validated['type'];
        $courseModuleId = $type === 'kuis_bab'
            ? (int) $validated['course_module_id']
            : null;

        $duplicateQuery = Quiz::query()
            ->where('class_group_id', $classGroup->id)
            ->where('type', $type);

        if ($type === 'kuis_bab') {
            $duplicateQuery->where('course_module_id', $courseModuleId);
        } else {
            $duplicateQuery->whereNull('course_module_id');
        }

        if ($duplicateQuery->exists()) {
            $message = $type === 'kuis_bab'
                ? 'Kuis untuk bab yang dipilih pada kelas ini sudah tersedia.'
                : 'Evaluasi akhir untuk kelas ini sudah tersedia.';

            return back()
                ->withInput()
                ->withErrors(['type' => $message]);
        }

        $quiz = Quiz::create([
            'class_group_id' => $classGroup->id,
            'course_module_id' => $courseModuleId,
            'title' => $validated['title'],
            'slug' => $this->makeUniqueSlug($validated['title'], $classGroup->id),
            'type' => $type,
            'description' => $validated['description'] ?? null,
            'instruction' => $validated['instruction'] ?? null,
            'duration_minutes' => (int) $validated['duration_minutes'],
            'max_attempts' => 3,
            'is_active' => false,
        ]);

        return redirect()
            ->route('dosen.kuis.show', $quiz)
            ->with('success', 'Kuis berhasil dibuat sebagai draf. Tambahkan soal sebelum kuis diaktifkan.');
    }

    public function show(Quiz $quiz)
    {
        $this->ensureQuizOwner($quiz);

        $quiz->load([
            'classGroup:id,name,kkm',
            'module:id,title,order_number',
            'questions' => fn ($query) => $query->orderBy('order_number')->orderBy('id'),
        ])->loadCount('attempts');

        $totalPoints = $quiz->questions->sum('points');

        $hasStartedAttempts = $quiz->attempts()
            ->whereIn('status', ['in_progress', 'submitted', 'auto_submitted'])
            ->exists();

        $canManageQuestions = ! $hasStartedAttempts;

        return view('dosen.kuis.show', compact(
            'quiz',
            'totalPoints',
            'hasStartedAttempts',
            'canManageQuestions'
        ));
    }

    public function toggleStatus(Quiz $quiz)
    {
        $this->ensureQuizOwner($quiz);

        if (! $quiz->is_active && $quiz->questions()->count() === 0) {
            return back()->with(
                'warning',
                'Kuis belum dapat diaktifkan karena belum memiliki soal.'
            );
        }

        $quiz->update([
            'is_active' => ! $quiz->is_active,
        ]);

        return back()->with(
            'success',
            $quiz->is_active
                ? 'Kuis berhasil diaktifkan dan dapat diakses mahasiswa sesuai syarat pembelajaran.'
                : 'Kuis berhasil dinonaktifkan. Mahasiswa tidak dapat memulai percobaan baru.'
        );
    }

    public function createQuestion(Quiz $quiz)
    {
        $this->ensureQuizOwner($quiz);

        if ($this->hasStartedAttempts($quiz)) {
            return redirect()
                ->route('dosen.kuis.show', $quiz)
                ->with('warning', 'Soal tidak dapat ditambahkan karena kuis sudah memiliki percobaan mahasiswa.');
        }

        $quiz->load(['classGroup:id,name,kkm', 'module:id,title,order_number']);

        $question = new QuizQuestion([
            'question_type' => 'short_text',
            'points' => 5,
            'is_required' => true,
        ]);

        return view('dosen.kuis.question-form', [
            'quiz' => $quiz,
            'question' => $question,
            'formMode' => 'create',
        ]);
    }

    public function storeQuestion(Request $request, Quiz $quiz)
    {
        $this->ensureQuizOwner($quiz);

        if ($this->hasStartedAttempts($quiz)) {
            return redirect()
                ->route('dosen.kuis.show', $quiz)
                ->with('warning', 'Soal tidak dapat ditambahkan karena kuis sudah memiliki percobaan mahasiswa.');
        }

        $payload = $this->buildQuestionPayload($request);

        $payload['quiz_id'] = $quiz->id;
        $payload['order_number'] = ((int) $quiz->questions()->max('order_number')) + 1;

        QuizQuestion::create($payload);

        return redirect()
            ->route('dosen.kuis.show', $quiz)
            ->with('success', 'Soal berhasil ditambahkan.');
    }

    public function editQuestion(Quiz $quiz, QuizQuestion $question)
    {
        $this->ensureQuizOwner($quiz);
        $this->ensureQuestionBelongsToQuiz($quiz, $question);

        if ($this->hasStartedAttempts($quiz)) {
            return redirect()
                ->route('dosen.kuis.show', $quiz)
                ->with('warning', 'Soal tidak dapat diubah karena kuis sudah memiliki percobaan mahasiswa.');
        }

        if (! in_array($question->question_type, self::BASIC_QUESTION_TYPES, true)) {
            return redirect()
                ->route('dosen.kuis.show', $quiz)
                ->with('warning', 'Tipe soal ini termasuk tipe lanjutan dan belum dapat diedit pada tahap manajemen soal dasar.');
        }

        $quiz->load(['classGroup:id,name,kkm', 'module:id,title,order_number']);

        return view('dosen.kuis.question-form', [
            'quiz' => $quiz,
            'question' => $question,
            'formMode' => 'edit',
        ]);
    }

    public function updateQuestion(Request $request, Quiz $quiz, QuizQuestion $question)
    {
        $this->ensureQuizOwner($quiz);
        $this->ensureQuestionBelongsToQuiz($quiz, $question);

        if ($this->hasStartedAttempts($quiz)) {
            return redirect()
                ->route('dosen.kuis.show', $quiz)
                ->with('warning', 'Soal tidak dapat diubah karena kuis sudah memiliki percobaan mahasiswa.');
        }

        if (! in_array($question->question_type, self::BASIC_QUESTION_TYPES, true)) {
            return redirect()
                ->route('dosen.kuis.show', $quiz)
                ->with('warning', 'Tipe soal ini termasuk tipe lanjutan dan belum dapat diedit pada tahap manajemen soal dasar.');
        }

        $question->update($this->buildQuestionPayload($request));

        return redirect()
            ->route('dosen.kuis.show', $quiz)
            ->with('success', 'Soal berhasil diperbarui.');
    }

    public function destroyQuestion(Quiz $quiz, QuizQuestion $question)
    {
        $this->ensureQuizOwner($quiz);
        $this->ensureQuestionBelongsToQuiz($quiz, $question);

        if ($this->hasStartedAttempts($quiz)) {
            return redirect()
                ->route('dosen.kuis.show', $quiz)
                ->with('warning', 'Soal tidak dapat dihapus karena kuis sudah memiliki percobaan mahasiswa.');
        }

        $question->delete();
        $this->renumberQuestions($quiz);

        return redirect()
            ->route('dosen.kuis.show', $quiz)
            ->with('success', 'Soal berhasil dihapus dan nomor soal telah dirapikan.');
    }

    private function buildQuestionPayload(Request $request): array
    {
        $validated = $request->validate([
            'question_type' => ['required', Rule::in(self::BASIC_QUESTION_TYPES)],
            'question_text' => ['required', 'string', 'max:4000'],
            'equations_text' => ['nullable', 'string', 'max:3000'],
            'accepted_answers_text' => ['nullable', 'string', 'max:3000'],
            'checkbox_options' => ['nullable', 'array'],
            'checkbox_options.*' => ['nullable', 'string', 'max:500'],
            'checkbox_correct' => ['nullable', 'array'],
            'checkbox_correct.*' => ['nullable', 'string', 'max:10'],
            'variable_definitions' => ['nullable', 'array', 'min:1', 'max:8'],
            'variable_definitions.*' => ['nullable', 'array'],
            'variable_definitions.*.label' => ['nullable', 'string', 'max:60'],
            'variable_definitions.*.answer' => ['nullable', 'string', 'max:100'],
            'explanation' => ['nullable', 'string', 'max:2000'],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
            'is_required' => ['nullable', 'boolean'],
        ]);

        $questionType = $validated['question_type'];
        $equations = $this->splitLines($validated['equations_text'] ?? null);

        $questionData = [];

        if (! empty($equations)) {
            $questionData['equations'] = $equations;
        }

        if ($questionType === 'variable_values') {
            $definitions = $validated['variable_definitions'] ?? [];

            if (! is_array($definitions) || empty($definitions)) {
                throw ValidationException::withMessages([
                    'variable_definitions' => 'Masukkan minimal satu variabel yang harus dijawab.',
                ]);
            }

            $fields = [];
            $acceptedAnswers = [];
            $answerKey = [];
            $usedLabels = [];

            foreach (array_values($definitions) as $index => $definition) {
                $label = $this->normalizeVariableLabel($definition['label'] ?? null);
                $answer = $this->nullableText($definition['answer'] ?? null);
                $position = $index + 1;

                if ($label === null) {
                    throw ValidationException::withMessages([
                        'variable_definitions.' . $index . '.label' => 'Masukkan nama variabel ke-' . $position . '.',
                    ]);
                }

                $labelFingerprint = $this->variableLabelFingerprint($label);

                if (isset($usedLabels[$labelFingerprint])) {
                    throw ValidationException::withMessages([
                        'variable_definitions.' . $index . '.label' => 'Nama variabel tidak boleh sama.',
                    ]);
                }

                if ($answer === null) {
                    throw ValidationException::withMessages([
                        'variable_definitions.' . $index . '.answer' => 'Masukkan jawaban yang diterima untuk ' . $label . '.',
                    ]);
                }

                $key = 'v' . $position;

                $usedLabels[$labelFingerprint] = true;
                $fields[] = [
                    'key' => $key,
                    'label' => $label,
                ];
                $answerKey[$key] = $answer;
                $acceptedAnswers[$key] = [$answer];
            }

            return [
                'question_text' => trim($validated['question_text']),
                'question_type' => 'variable_values',
                'question_data' => array_filter([
                    'equations' => $questionData['equations'] ?? null,
                    'fields' => $fields,
                ]),
                'answer_key' => $answerKey,
                'accepted_answers' => $acceptedAnswers,
                'explanation' => $this->nullableText($validated['explanation'] ?? null),
                'points' => (int) $validated['points'],
                'is_required' => $request->boolean('is_required', true),
            ];
        }
        if (in_array($questionType, ['short_text', 'math_notation'], true)) {
            $acceptedAnswers = $this->splitLines($validated['accepted_answers_text'] ?? null);

            if (empty($acceptedAnswers)) {
                throw ValidationException::withMessages([
                    'accepted_answers_text' => 'Masukkan minimal satu jawaban yang diterima.',
                ]);
            }

            return [
                'question_text' => trim($validated['question_text']),
                'question_type' => $questionType,
                'question_data' => $questionData,
                'answer_key' => ['answer' => $acceptedAnswers[0]],
                'accepted_answers' => $acceptedAnswers,
                'explanation' => $this->nullableText($validated['explanation'] ?? null),
                'points' => (int) $validated['points'],
                'is_required' => $request->boolean('is_required', true),
            ];
        }

        $options = collect($validated['checkbox_options'] ?? [])
            ->map(fn ($option) => trim((string) $option))
            ->filter()
            ->all();

        if (count($options) < 2) {
            throw ValidationException::withMessages([
                'checkbox_options' => 'Masukkan minimal dua pilihan jawaban.',
            ]);
        }

        $selected = collect($validated['checkbox_correct'] ?? [])
            ->map(fn ($key) => strtoupper(trim((string) $key)))
            ->filter(fn ($key) => array_key_exists($key, $options))
            ->unique()
            ->values()
            ->all();

        if (empty($selected)) {
            throw ValidationException::withMessages([
                'checkbox_correct' => 'Tentukan minimal satu pilihan jawaban yang benar.',
            ]);
        }

        $questionData['options'] = $options;

        return [
            'question_text' => trim($validated['question_text']),
            'question_type' => 'checkbox',
            'question_data' => $questionData,
            'answer_key' => ['selected' => $selected],
            'accepted_answers' => [],
            'explanation' => $this->nullableText($validated['explanation'] ?? null),
            'points' => (int) $validated['points'],
            'is_required' => $request->boolean('is_required', true),
        ];
    }

    private function normalizeVariableLabel($value): ?string
    {
        $label = trim((string) $value);
        $label = preg_replace('/\s+/u', ' ', $label) ?? '';

        if ($label === '') {
            return null;
        }

        $length = function_exists('mb_strlen')
            ? mb_strlen($label)
            : strlen($label);

        return $length <= 40 ? $label : null;
    }

    private function variableLabelFingerprint(string $label): string
    {
        return function_exists('mb_strtolower')
            ? mb_strtolower($label)
            : strtolower($label);
    }

    private function splitLines(?string $value): array
    {
        return collect(preg_split('/\R/u', (string) $value) ?: [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function nullableText(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function renumberQuestions(Quiz $quiz): void
    {
        $quiz->questions()
            ->orderBy('order_number')
            ->orderBy('id')
            ->get()
            ->values()
            ->each(function (QuizQuestion $question, int $index) {
                $question->update([
                    'order_number' => $index + 1,
                ]);
            });
    }

    private function hasStartedAttempts(Quiz $quiz): bool
    {
        return $quiz->attempts()
            ->whereIn('status', ['in_progress', 'submitted', 'auto_submitted'])
            ->exists();
    }

    private function ensureQuestionBelongsToQuiz(Quiz $quiz, QuizQuestion $question): void
    {
        abort_unless(
            (int) $question->quiz_id === (int) $quiz->id,
            404,
            'Soal tidak ditemukan pada kuis ini.'
        );
    }

    private function ensureClassGroupOwner(ClassGroup $classGroup): void
    {
        abort_unless(
            (int) $classGroup->dosen_id === (int) Auth::id(),
            403,
            'Anda tidak memiliki akses ke kelas ini.'
        );
    }

    private function ensureQuizOwner(Quiz $quiz): void
    {
        $isOwner = $quiz->classGroup()
            ->where('dosen_id', Auth::id())
            ->exists();

        abort_unless($isOwner, 403, 'Anda tidak memiliki akses ke kuis ini.');
    }

    private function makeUniqueSlug(string $title, int $classGroupId): string
    {
        $baseSlug = Str::slug($title);

        if ($baseSlug === '') {
            $baseSlug = 'kuis';
        }

        $baseSlug = Str::limit($baseSlug, 180, '');

        do {
            $slug = $baseSlug
                . '-kelas-'
                . $classGroupId
                . '-'
                . Str::lower(Str::random(8));
        } while (Quiz::where('slug', $slug)->exists());

        return $slug;
    }
}