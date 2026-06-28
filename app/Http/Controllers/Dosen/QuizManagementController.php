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
        'matrix',
        'augmented_matrix',
        'obe_matrix_operation',
        'gauss_elimination',
        'gauss_jordan',
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
            'matrix_rows' => ['nullable', 'integer', 'min:1', 'max:6'],
            'matrix_columns' => ['nullable', 'integer', 'min:1', 'max:8'],
            'matrix_separator_before_column' => ['nullable', 'integer', 'min:2', 'max:8'],
            'matrix_label' => ['nullable', 'string', 'max:30'],
            'matrix_answers' => ['nullable', 'array'],
            'matrix_answers.*' => ['nullable', 'array'],
            'matrix_answers.*.*' => ['nullable', 'string', 'max:100'],
            'obe_rows' => ['nullable', 'integer', 'min:1', 'max:6'],
            'obe_columns' => ['nullable', 'integer', 'min:1', 'max:8'],
            'obe_has_separator' => ['nullable', 'boolean'],
            'obe_separator_before_column' => ['nullable', 'integer', 'min:2', 'max:8'],
            'obe_initial_matrix' => ['nullable', 'array'],
            'obe_initial_matrix.*' => ['nullable', 'array'],
            'obe_initial_matrix.*.*' => ['nullable', 'string', 'max:100'],
            'obe_result_matrix' => ['nullable', 'array'],
            'obe_result_matrix.*' => ['nullable', 'array'],
            'obe_result_matrix.*.*' => ['nullable', 'string', 'max:100'],
            'obe_operations_latex' => ['nullable', 'array', 'min:1', 'max:12'],
            'obe_operations_latex.*' => ['nullable', 'string', 'max:1000'],
            'gauss_rows' => ['nullable', 'integer', 'min:1', 'max:6'],
            'gauss_columns' => ['nullable', 'integer', 'min:1', 'max:8'],
            'gauss_has_separator' => ['nullable', 'boolean'],
            'gauss_separator_before_column' => ['nullable', 'integer', 'min:2', 'max:8'],
            'gauss_initial_matrix' => ['nullable', 'array'],
            'gauss_initial_matrix.*' => ['nullable', 'array'],
            'gauss_initial_matrix.*.*' => ['nullable', 'string', 'max:100'],
            'gauss_reference_matrix' => ['nullable', 'array'],
            'gauss_reference_matrix.*' => ['nullable', 'array'],
            'gauss_reference_matrix.*.*' => ['nullable', 'string', 'max:100'],
            'gauss_final_definitions' => ['nullable', 'array', 'min:1', 'max:8'],
            'gauss_final_definitions.*' => ['nullable', 'array'],
            'gauss_final_definitions.*.label' => ['nullable', 'string', 'max:60'],
            'gauss_final_definitions.*.answer' => ['nullable', 'string', 'max:100'],
            'gj_rows' => ['nullable', 'integer', 'min:1', 'max:6'],
            'gj_columns' => ['nullable', 'integer', 'min:1', 'max:8'],
            'gj_has_separator' => ['nullable', 'boolean'],
            'gj_separator_before_column' => ['nullable', 'integer', 'min:2', 'max:8'],
            'gj_initial_matrix' => ['nullable', 'array'],
            'gj_initial_matrix.*' => ['nullable', 'array'],
            'gj_initial_matrix.*.*' => ['nullable', 'string', 'max:100'],
            'gj_rref_matrix' => ['nullable', 'array'],
            'gj_rref_matrix.*' => ['nullable', 'array'],
            'gj_rref_matrix.*.*' => ['nullable', 'string', 'max:100'],
            'gj_final_definitions' => ['nullable', 'array', 'min:1', 'max:8'],
            'gj_final_definitions.*' => ['nullable', 'array'],
            'gj_final_definitions.*.label' => ['nullable', 'string', 'max:60'],
            'gj_final_definitions.*.answer' => ['nullable', 'string', 'max:100'],
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

        if ($questionType === 'gauss_jordan') {
            $rows = (int) ($validated['gj_rows'] ?? 0);
            $columns = (int) ($validated['gj_columns'] ?? 0);

            if ($rows < 1 || $rows > 6 || $columns < 1 || $columns > 8) {
                throw ValidationException::withMessages([
                    'gj_rows' => 'Ukuran matriks Gauss-Jordan harus terdiri dari 1–6 baris dan 1–8 kolom.',
                ]);
            }

            $hasSeparator = $request->boolean('gj_has_separator');

            if ($hasSeparator && $columns < 2) {
                throw ValidationException::withMessages([
                    'gj_columns' => 'Matriks dengan garis pemisah memerlukan minimal dua kolom.',
                ]);
            }

            $separatorBeforeColumn = $hasSeparator
                ? (int) ($validated['gj_separator_before_column'] ?? $columns)
                : null;

            if ($hasSeparator
                && ($separatorBeforeColumn < 2 || $separatorBeforeColumn > $columns)) {
                throw ValidationException::withMessages([
                    'gj_separator_before_column' => 'Kolom awal ruas kanan harus berada pada rentang 2 sampai jumlah kolom matriks.',
                ]);
            }

            $initialMatrix = $this->buildRequiredMatrix(
                (array) ($validated['gj_initial_matrix'] ?? []),
                $rows,
                $columns,
                'gj_initial_matrix',
                'matriks awal'
            );

            $rrefMatrix = $this->buildRequiredMatrix(
                (array) ($validated['gj_rref_matrix'] ?? []),
                $rows,
                $columns,
                'gj_rref_matrix',
                'target bentuk eselon baris tereduksi'
            );

            $definitions = $validated['gj_final_definitions'] ?? [];

            if (! is_array($definitions) || empty($definitions)) {
                throw ValidationException::withMessages([
                    'gj_final_definitions' => 'Masukkan minimal satu nilai akhir variabel.',
                ]);
            }

            $finalFields = [];
            $finalLabels = [];
            $acceptedAnswers = [];
            $usedLabels = [];

            foreach (array_values($definitions) as $index => $definition) {
                $label = $this->normalizeVariableLabel($definition['label'] ?? null);
                $answer = $this->nullableText($definition['answer'] ?? null);
                $position = $index + 1;

                if ($label === null) {
                    throw ValidationException::withMessages([
                        'gj_final_definitions.' . $index . '.label' => 'Masukkan nama variabel ke-' . $position . '.',
                    ]);
                }

                $fingerprint = $this->variableLabelFingerprint($label);

                if (isset($usedLabels[$fingerprint])) {
                    throw ValidationException::withMessages([
                        'gj_final_definitions.' . $index . '.label' => 'Nama variabel akhir tidak boleh sama.',
                    ]);
                }

                if ($answer === null) {
                    throw ValidationException::withMessages([
                        'gj_final_definitions.' . $index . '.answer' => 'Masukkan nilai akhir untuk ' . $label . '.',
                    ]);
                }

                $key = 'j' . $position;

                $usedLabels[$fingerprint] = true;
                $finalFields[] = $key;
                $finalLabels[$key] = $label;
                $acceptedAnswers[$key] = [$answer];
            }

            $gaussJordanData = $questionData;
            $gaussJordanData['rows'] = $rows;
            $gaussJordanData['columns'] = $columns;
            $gaussJordanData['has_separator'] = $hasSeparator;
            $gaussJordanData['initial_matrix'] = $initialMatrix;
            $gaussJordanData['final_fields'] = $finalFields;
            $gaussJordanData['final_labels'] = $finalLabels;

            if ($hasSeparator) {
                $gaussJordanData['separator_before_column'] = $separatorBeforeColumn;
            }

            return [
                'question_text' => trim($validated['question_text']),
                'question_type' => 'gauss_jordan',
                'question_data' => $gaussJordanData,
                'answer_key' => [
                    'initial_matrix' => $initialMatrix,
                    'rref_matrix' => $rrefMatrix,
                ],
                'accepted_answers' => $acceptedAnswers,
                'explanation' => $this->nullableText($validated['explanation'] ?? null),
                'points' => (int) $validated['points'],
                'is_required' => $request->boolean('is_required', true),
            ];
        }
        if ($questionType === 'gauss_elimination') {
            $rows = (int) ($validated['gauss_rows'] ?? 0);
            $columns = (int) ($validated['gauss_columns'] ?? 0);

            if ($rows < 1 || $rows > 6 || $columns < 1 || $columns > 8) {
                throw ValidationException::withMessages([
                    'gauss_rows' => 'Ukuran matriks Eliminasi Gauss harus terdiri dari 1–6 baris dan 1–8 kolom.',
                ]);
            }

            $hasSeparator = $request->boolean('gauss_has_separator');

            if ($hasSeparator && $columns < 2) {
                throw ValidationException::withMessages([
                    'gauss_columns' => 'Matriks dengan garis pemisah memerlukan minimal dua kolom.',
                ]);
            }

            $separatorBeforeColumn = $hasSeparator
                ? (int) ($validated['gauss_separator_before_column'] ?? $columns)
                : null;

            if ($hasSeparator
                && ($separatorBeforeColumn < 2 || $separatorBeforeColumn > $columns)) {
                throw ValidationException::withMessages([
                    'gauss_separator_before_column' => 'Kolom awal ruas kanan harus berada pada rentang 2 sampai jumlah kolom matriks.',
                ]);
            }

            $initialMatrix = $this->buildRequiredMatrix(
                (array) ($validated['gauss_initial_matrix'] ?? []),
                $rows,
                $columns,
                'gauss_initial_matrix',
                'matriks awal'
            );

            $referenceMatrix = $this->buildRequiredMatrix(
                (array) ($validated['gauss_reference_matrix'] ?? []),
                $rows,
                $columns,
                'gauss_reference_matrix',
                'matriks acuan bentuk eselon baris'
            );

            $definitions = $validated['gauss_final_definitions'] ?? [];

            if (! is_array($definitions) || empty($definitions)) {
                throw ValidationException::withMessages([
                    'gauss_final_definitions' => 'Masukkan minimal satu nilai akhir variabel.',
                ]);
            }

            $finalFields = [];
            $finalLabels = [];
            $acceptedAnswers = [];
            $usedLabels = [];

            foreach (array_values($definitions) as $index => $definition) {
                $label = $this->normalizeVariableLabel($definition['label'] ?? null);
                $answer = $this->nullableText($definition['answer'] ?? null);
                $position = $index + 1;

                if ($label === null) {
                    throw ValidationException::withMessages([
                        'gauss_final_definitions.' . $index . '.label' => 'Masukkan nama variabel ke-' . $position . '.',
                    ]);
                }

                $fingerprint = $this->variableLabelFingerprint($label);

                if (isset($usedLabels[$fingerprint])) {
                    throw ValidationException::withMessages([
                        'gauss_final_definitions.' . $index . '.label' => 'Nama variabel akhir tidak boleh sama.',
                    ]);
                }

                if ($answer === null) {
                    throw ValidationException::withMessages([
                        'gauss_final_definitions.' . $index . '.answer' => 'Masukkan nilai akhir untuk ' . $label . '.',
                    ]);
                }

                $key = 'g' . $position;

                $usedLabels[$fingerprint] = true;
                $finalFields[] = $key;
                $finalLabels[$key] = $label;
                $acceptedAnswers[$key] = [$answer];
            }

            $gaussData = $questionData;
            $gaussData['rows'] = $rows;
            $gaussData['columns'] = $columns;
            $gaussData['has_separator'] = $hasSeparator;
            $gaussData['reference_echelon_matrix'] = $referenceMatrix;
            $gaussData['final_fields'] = $finalFields;
            $gaussData['final_labels'] = $finalLabels;

            if ($hasSeparator) {
                $gaussData['separator_before_column'] = $separatorBeforeColumn;
            }

            return [
                'question_text' => trim($validated['question_text']),
                'question_type' => 'gauss_elimination',
                'question_data' => $gaussData,
                'answer_key' => ['initial_matrix' => $initialMatrix],
                'accepted_answers' => $acceptedAnswers,
                'explanation' => $this->nullableText($validated['explanation'] ?? null),
                'points' => (int) $validated['points'],
                'is_required' => $request->boolean('is_required', true),
            ];
        }
        if ($questionType === 'obe_matrix_operation') {
            $rows = (int) ($validated['obe_rows'] ?? 0);
            $columns = (int) ($validated['obe_columns'] ?? 0);

            if ($rows < 1 || $rows > 6 || $columns < 1 || $columns > 8) {
                throw ValidationException::withMessages([
                    'obe_rows' => 'Ukuran matriks OBE harus terdiri dari 1–6 baris dan 1–8 kolom.',
                ]);
            }

            $hasSeparator = $request->boolean('obe_has_separator');

            if ($hasSeparator && $columns < 2) {
                throw ValidationException::withMessages([
                    'obe_columns' => 'Matriks dengan garis pemisah memerlukan minimal dua kolom.',
                ]);
            }

            $separatorBeforeColumn = $hasSeparator
                ? (int) ($validated['obe_separator_before_column'] ?? $columns)
                : null;

            if ($hasSeparator
                && ($separatorBeforeColumn < 2 || $separatorBeforeColumn > $columns)) {
                throw ValidationException::withMessages([
                    'obe_separator_before_column' => 'Kolom awal ruas kanan harus berada pada rentang 2 sampai jumlah kolom matriks.',
                ]);
            }

            $initialMatrix = $this->buildRequiredMatrix(
                (array) ($validated['obe_initial_matrix'] ?? []),
                $rows,
                $columns,
                'obe_initial_matrix',
                'matriks awal'
            );

            $resultMatrix = $this->buildRequiredMatrix(
                (array) ($validated['obe_result_matrix'] ?? []),
                $rows,
                $columns,
                'obe_result_matrix',
                'matriks hasil'
            );

            $obeOperationsLatex = collect($validated['obe_operations_latex'] ?? [])
                ->map(fn ($operation) => trim((string) $operation))
                ->filter()
                ->values()
                ->all();

            $acceptedOperations = $this->normalizeObeMathLiveOperations($obeOperationsLatex);

            if (empty($acceptedOperations)) {
                throw ValidationException::withMessages([
                    'obe_operations_latex' => 'Masukkan minimal satu notasi operasi yang diterima.',
                ]);
            }

            $obeData = $questionData;
            $obeData['rows'] = $rows;
            $obeData['columns'] = $columns;
            $obeData['initial_matrix'] = $initialMatrix;
            $obeData['has_separator'] = $hasSeparator;
            $obeData['accepted_operations_latex'] = $obeOperationsLatex;

            if ($hasSeparator) {
                $obeData['separator_before_column'] = $separatorBeforeColumn;
            }

            return [
                'question_text' => trim($validated['question_text']),
                'question_type' => 'obe_matrix_operation',
                'question_data' => $obeData,
                'answer_key' => ['matrix' => $resultMatrix],
                'accepted_answers' => $acceptedOperations,
                'explanation' => $this->nullableText($validated['explanation'] ?? null),
                'points' => (int) $validated['points'],
                'is_required' => $request->boolean('is_required', true),
            ];
        }
        if (in_array($questionType, ['matrix', 'augmented_matrix'], true)) {
            $rows = (int) ($validated['matrix_rows'] ?? 0);
            $columns = (int) ($validated['matrix_columns'] ?? 0);

            if ($rows < 1 || $rows > 6 || $columns < 1 || $columns > 8) {
                throw ValidationException::withMessages([
                    'matrix_rows' => 'Ukuran matriks harus terdiri dari 1–6 baris dan 1–8 kolom.',
                ]);
            }

            if ($questionType === 'augmented_matrix' && $columns < 2) {
                throw ValidationException::withMessages([
                    'matrix_columns' => 'Matriks teraugmentasi memerlukan minimal dua kolom.',
                ]);
            }

            $separatorBeforeColumn = $questionType === 'augmented_matrix'
                ? (int) ($validated['matrix_separator_before_column'] ?? $columns)
                : null;

            if ($questionType === 'augmented_matrix'
                && ($separatorBeforeColumn < 2 || $separatorBeforeColumn > $columns)) {
                throw ValidationException::withMessages([
                    'matrix_separator_before_column' => 'Kolom awal ruas kanan harus berada pada rentang 2 sampai jumlah kolom matriks.',
                ]);
            }

            $matrixInput = $validated['matrix_answers'] ?? [];
            $matrix = [];

            for ($row = 0; $row < $rows; $row++) {
                $matrix[$row] = [];

                for ($column = 0; $column < $columns; $column++) {
                    $value = $this->nullableText($matrixInput[$row][$column] ?? null);

                    if ($value === null) {
                        throw ValidationException::withMessages([
                            'matrix_answers.' . $row . '.' . $column => 'Lengkapi seluruh elemen jawaban matriks.',
                        ]);
                    }

                    $matrix[$row][$column] = $value;
                }
            }

            $matrixData = $questionData;
            $matrixData['rows'] = $rows;
            $matrixData['columns'] = $columns;

            if ($questionType === 'augmented_matrix') {
                $matrixData['separator_before_column'] = $separatorBeforeColumn;
            } else {
                $matrixData['label'] = $this->nullableText($validated['matrix_label'] ?? null) ?? 'A';
            }

            return [
                'question_text' => trim($validated['question_text']),
                'question_type' => $questionType,
                'question_data' => $matrixData,
                'answer_key' => ['matrix' => $matrix],
                'accepted_answers' => [],
                'explanation' => $this->nullableText($validated['explanation'] ?? null),
                'points' => (int) $validated['points'],
                'is_required' => $request->boolean('is_required', true),
            ];
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

    private function normalizeObeMathLiveOperations(array $operations): array
    {
        return collect($operations)
            ->map(fn ($operation) => $this->mathLiveObeToPlainText($operation))
            ->map(fn ($operation) => $this->normalizeObeOperationForStorage($operation))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function mathLiveObeToPlainText($value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $value = str_replace(
            [
                '\\longleftrightarrow',
                '\\leftrightarrow',
                '\\Longleftrightarrow',
                '\\longleftarrow',
                '\\leftarrow',
                '\\gets',
                '\\Leftarrow',
            ],
            [
                '<->',
                '<->',
                '<->',
                '<-',
                '<-',
                '<-',
                '<-',
            ],
            $value
        );

        $value = preg_replace_callback(
            '/\\\\frac\s*\{([^{}]*)\}\s*\{([^{}]*)\}/u',
            static fn (array $matches): string => '(' . $matches[1] . ')/(' . $matches[2] . ')',
            $value
        ) ?? $value;

        $value = preg_replace('/([A-Za-z])_\{?(\d+)\}?/u', '$1$2', $value) ?? $value;

        $value = str_replace(
            ['\\cdot', '\\times', '\\,', '\\!', '\\;', '\\:', '\\left', '\\right', '{', '}', '×', '·'],
            ['', '', '', '', '', '', '', '', '', '', ''],
            $value
        );

        return $value;
    }

    private function normalizeObeOperationForStorage($value): string
    {
        $value = strtolower(trim((string) $value));

        $value = str_replace(
            ['₁', '₂', '₃', '₄', '₅', '₆', '−', '–', '—', '↔', '⟷', '⇄', '←', '⟵', '⟸', '×', '·'],
            ['1', '2', '3', '4', '5', '6', '-', '-', '-', '<->', '<->', '<->', '<-', '<-', '<-', '', ''],
            $value
        );

        $value = str_ireplace('baris', 'b', $value);
        $value = str_replace(['*', '_', '(', ')'], '', $value);
        $value = preg_replace('/\s+/u', '', $value) ?? $value;

        return str_replace('+-', '-', $value);
    }
    private function buildRequiredMatrix(
        array $input,
        int $rows,
        int $columns,
        string $inputName,
        string $matrixLabel
    ): array {
        $matrix = [];

        for ($row = 0; $row < $rows; $row++) {
            $matrix[$row] = [];

            for ($column = 0; $column < $columns; $column++) {
                $value = $this->nullableText($input[$row][$column] ?? null);

                if ($value === null) {
                    throw ValidationException::withMessages([
                        $inputName . '.' . $row . '.' . $column => 'Lengkapi seluruh elemen ' . $matrixLabel . '.',
                    ]);
                }

                $matrix[$row][$column] = $value;
            }
        }

        return $matrix;
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