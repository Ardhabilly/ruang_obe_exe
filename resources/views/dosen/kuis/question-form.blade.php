<x-app-layout>
    @php
        $isEditing = $formMode === 'edit';

        $data = $question->question_data ?? [];
        $answerKey = $question->answer_key ?? [];
        $acceptedAnswers = $question->accepted_answers ?? [];

        $questionType = old('question_type', $question->question_type ?: 'short_text');
        $equationsText = old('equations_text', implode("\n", $data['equations'] ?? []));
        $acceptedAnswersText = old('accepted_answers_text', implode("\n", $acceptedAnswers));

        $defaultOptions = [
            'A' => '',
            'B' => '',
            'C' => '',
            'D' => '',
        ];

        $checkboxOptions = old('checkbox_options', array_merge($defaultOptions, $data['options'] ?? []));
        $checkboxCorrect = old('checkbox_correct', $answerKey['selected'] ?? []);

        $rawVariableFields = $data['fields'] ?? ['x', 'y', 'z'];
        $legacyVariableLabels = $data['labels'] ?? [];
        $variableDefinitions = [];

        if (! is_array($rawVariableFields)) {
            $rawVariableFields = ['x', 'y', 'z'];
        }

        if (! is_array($legacyVariableLabels)) {
            $legacyVariableLabels = [];
        }

        foreach (array_values($rawVariableFields) as $index => $field) {
            if (is_array($field)) {
                $key = trim((string) ($field['key'] ?? 'v' . ($index + 1)));
                $label = trim((string) ($field['label'] ?? $key));
            } else {
                $key = trim((string) $field);
                $label = trim((string) ($legacyVariableLabels[$key] ?? $key));
            }

            if ($key === '') {
                $key = 'v' . ($index + 1);
            }

            if ($label === '') {
                $label = $key;
            }

            $variableDefinitions[] = [
                'label' => $label,
                'answer' => $answerKey[$key] ?? '',
            ];
        }

        if (empty($variableDefinitions)) {
            $variableDefinitions = [
                ['label' => 'x', 'answer' => ''],
                ['label' => 'y', 'answer' => ''],
                ['label' => 'z', 'answer' => ''],
            ];
        }

        $variableDefinitions = old('variable_definitions', $variableDefinitions);

        if (! is_array($variableDefinitions) || empty($variableDefinitions)) {
            $variableDefinitions = [
                ['label' => 'x', 'answer' => ''],
            ];
        }

        $matrixRows = (int) old('matrix_rows', $data['rows'] ?? 3);
        $matrixColumns = (int) old('matrix_columns', $data['columns'] ?? 3);
        $matrixSeparatorBeforeColumn = (int) old(
            'matrix_separator_before_column',
            $data['separator_before_column'] ?? $matrixColumns
        );

        if ($matrixRows < 1 || $matrixRows > 6) {
            $matrixRows = 3;
        }

        if ($matrixColumns < 1 || $matrixColumns > 8) {
            $matrixColumns = 3;
        }

        if ($matrixSeparatorBeforeColumn < 2 || $matrixSeparatorBeforeColumn > $matrixColumns) {
            $matrixSeparatorBeforeColumn = $matrixColumns;
        }

        $matrixLabel = old('matrix_label', $data['label'] ?? 'A');
        $matrixAnswers = old('matrix_answers', $answerKey['matrix'] ?? []);

        if (! is_array($matrixAnswers)) {
            $matrixAnswers = [];
        }        $obeRows = (int) old('obe_rows', $data['rows'] ?? 3);
        $obeColumns = (int) old('obe_columns', $data['columns'] ?? 4);
        $obeHasSeparator = old('obe_has_separator', $data['has_separator'] ?? isset($data['separator_before_column']));
        $obeSeparatorBeforeColumn = (int) old(
            'obe_separator_before_column',
            $data['separator_before_column'] ?? $obeColumns
        );
        $obeInitialMatrix = old('obe_initial_matrix', $data['initial_matrix'] ?? []);
        $obeResultMatrix = old('obe_result_matrix', $answerKey['matrix'] ?? []);

        $storedObeOperationsLatex = $data['accepted_operations_latex'] ?? [];

        if (! is_array($storedObeOperationsLatex)) {
            $storedObeOperationsLatex = [];
        }

        $obeOperationsLatex = old(
            'obe_operations_latex',
            ! empty($storedObeOperationsLatex)
                ? $storedObeOperationsLatex
                : ($acceptedAnswers ?? [])
        );

        if (! is_array($obeOperationsLatex) || empty($obeOperationsLatex)) {
            $obeOperationsLatex = [''];
        }

        if ($obeRows < 1 || $obeRows > 6) {
            $obeRows = 3;
        }

        if ($obeColumns < 1 || $obeColumns > 8) {
            $obeColumns = 4;
        }

        if ($obeSeparatorBeforeColumn < 2 || $obeSeparatorBeforeColumn > $obeColumns) {
            $obeSeparatorBeforeColumn = $obeColumns;
        }

        if (! is_array($obeInitialMatrix)) {
            $obeInitialMatrix = [];
        }

        if (! is_array($obeResultMatrix)) {
            $obeResultMatrix = [];
        }        $gaussRows = (int) old('gauss_rows', $data['rows'] ?? 3);
        $gaussColumns = (int) old('gauss_columns', $data['columns'] ?? 4);
        $gaussHasSeparator = old('gauss_has_separator', $data['has_separator'] ?? true);
        $gaussSeparatorBeforeColumn = (int) old(
            'gauss_separator_before_column',
            $data['separator_before_column'] ?? $gaussColumns
        );
        $gaussInitialMatrix = old('gauss_initial_matrix', $answerKey['initial_matrix'] ?? []);
        $gaussReferenceMatrix = old('gauss_reference_matrix', $data['reference_echelon_matrix'] ?? []);

        if ($gaussRows < 1 || $gaussRows > 6) {
            $gaussRows = 3;
        }

        if ($gaussColumns < 1 || $gaussColumns > 8) {
            $gaussColumns = 4;
        }

        if ($gaussSeparatorBeforeColumn < 2 || $gaussSeparatorBeforeColumn > $gaussColumns) {
            $gaussSeparatorBeforeColumn = $gaussColumns;
        }

        if (! is_array($gaussInitialMatrix)) {
            $gaussInitialMatrix = [];
        }

        if (! is_array($gaussReferenceMatrix)) {
            $gaussReferenceMatrix = [];
        }

        $gaussFinalDefinitions = [];
        $gaussFinalFields = $data['final_fields'] ?? array_keys($acceptedAnswers ?? []);
        $gaussFinalLabels = $data['final_labels'] ?? [];

        if (! is_array($gaussFinalFields)) {
            $gaussFinalFields = [];
        }

        if (! is_array($gaussFinalLabels)) {
            $gaussFinalLabels = [];
        }

        foreach (array_values($gaussFinalFields) as $index => $field) {
            $key = trim((string) $field);

            if ($key === '') {
                $key = 'g' . ($index + 1);
            }

            $answer = $acceptedAnswers[$key] ?? '';
            $answer = is_array($answer) ? ($answer[0] ?? '') : $answer;

            $gaussFinalDefinitions[] = [
                'label' => trim((string) ($gaussFinalLabels[$key] ?? $key)),
                'answer' => $answer,
            ];
        }

        if (empty($gaussFinalDefinitions)) {
            $gaussFinalDefinitions = [
                ['label' => 'x', 'answer' => ''],
                ['label' => 'y', 'answer' => ''],
                ['label' => 'z', 'answer' => ''],
            ];
        }

        $gaussFinalDefinitions = old('gauss_final_definitions', $gaussFinalDefinitions);

        if (! is_array($gaussFinalDefinitions) || empty($gaussFinalDefinitions)) {
            $gaussFinalDefinitions = [
                ['label' => 'x', 'answer' => ''],
            ];
        }
        $gjRows = (int) old('gj_rows', $data['rows'] ?? 3);
        $gjColumns = (int) old('gj_columns', $data['columns'] ?? 4);
        $gjHasSeparator = old('gj_has_separator', $data['has_separator'] ?? true);
        $gjSeparatorBeforeColumn = (int) old(
            'gj_separator_before_column',
            $data['separator_before_column'] ?? $gjColumns
        );
        $gjInitialMatrix = old(
            'gj_initial_matrix',
            $answerKey['initial_matrix'] ?? $data['initial_matrix'] ?? []
        );
        $gjRrefMatrix = old('gj_rref_matrix', $answerKey['rref_matrix'] ?? []);

        if ($gjRows < 1 || $gjRows > 6) {
            $gjRows = 3;
        }

        if ($gjColumns < 1 || $gjColumns > 8) {
            $gjColumns = 4;
        }

        if ($gjSeparatorBeforeColumn < 2 || $gjSeparatorBeforeColumn > $gjColumns) {
            $gjSeparatorBeforeColumn = $gjColumns;
        }

        if (! is_array($gjInitialMatrix)) {
            $gjInitialMatrix = [];
        }

        if (! is_array($gjRrefMatrix)) {
            $gjRrefMatrix = [];
        }

        $gjFinalDefinitions = [];
        $gjFinalFields = $data['final_fields'] ?? array_keys($acceptedAnswers ?? []);
        $gjFinalLabels = $data['final_labels'] ?? [];

        if (! is_array($gjFinalFields)) {
            $gjFinalFields = [];
        }

        if (! is_array($gjFinalLabels)) {
            $gjFinalLabels = [];
        }

        foreach (array_values($gjFinalFields) as $index => $field) {
            $key = trim((string) $field);

            if ($key === '') {
                $key = 'j' . ($index + 1);
            }

            $answer = $acceptedAnswers[$key] ?? '';
            $answer = is_array($answer) ? ($answer[0] ?? '') : $answer;

            $gjFinalDefinitions[] = [
                'label' => trim((string) ($gjFinalLabels[$key] ?? $key)),
                'answer' => $answer,
            ];
        }

        if (empty($gjFinalDefinitions)) {
            $gjFinalDefinitions = [
                ['label' => 'x', 'answer' => ''],
                ['label' => 'y', 'answer' => ''],
                ['label' => 'z', 'answer' => ''],
            ];
        }

        $gjFinalDefinitions = old('gj_final_definitions', $gjFinalDefinitions);

        if (! is_array($gjFinalDefinitions) || empty($gjFinalDefinitions)) {
            $gjFinalDefinitions = [
                ['label' => 'x', 'answer' => ''],
            ];
        }
    @endphp

    <div class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl space-y-6">
            <div class="flex flex-wrap items-center gap-2 text-sm text-slate-400">
                <a href="{{ route('dosen.kelas.index') }}" class="transition hover:text-cyan-200">Kelas</a>
                <span>/</span>
                <a href="{{ route('dosen.kelas.show', $quiz->classGroup) }}" class="transition hover:text-cyan-200">{{ $quiz->classGroup->name }}</a>
                <span>/</span>
                <a href="{{ route('dosen.kuis.show', $quiz) }}" class="transition hover:text-cyan-200">{{ $quiz->title }}</a>
                <span>/</span>
                <span class="text-white">{{ $isEditing ? 'Ubah Soal' : 'Tambah Soal' }}</span>
            </div>

            <section class="rounded-2xl border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                <a href="{{ route('dosen.kuis.show', $quiz) }}"
                   class="inline-flex items-center gap-2 text-sm font-bold text-cyan-200 transition hover:text-cyan-100">
                    ← Kembali ke Detail Kuis
                </a>

                <div class="mt-5">
                    <p class="text-sm font-semibold text-cyan-200">
                        {{ $isEditing ? 'Perbarui Soal' : 'Tambah Soal Baru' }}
                    </p>

                    <h1 class="mt-1 text-2xl font-black tracking-tight text-white sm:text-3xl">
                        {{ $quiz->title }}
                    </h1>

                    <p class="mt-2 text-sm leading-6 text-slate-400">
                        Buat soal isian, notasi matematika, nilai variabel, matriks, operasi baris elementer, Eliminasi Gauss, Gauss-Jordan, atau pilihan lebih dari satu jawaban.
                    </p>
                </div>
            </section>

            <form
                data-variable-fields="dynamic"
                method="POST"
                action="{{ $isEditing ? route('dosen.kuis.soal.update', [$quiz, $question]) : route('dosen.kuis.soal.store', $quiz) }}"
                x-data="{
                    questionType: @js($questionType),
                    maximumVariables: 8,
                    gjRows: @js($gjRows),
                    gjColumns: @js($gjColumns),
                    gjHasSeparator: @js((bool) $gjHasSeparator),
                    gjSeparatorBeforeColumn: @js($gjSeparatorBeforeColumn),
                    gjFinalDefinitions: @js(array_values($gjFinalDefinitions)),
                    gjFinalCount: {{ min(max(count($gjFinalDefinitions), 1), 8) }},

                    ensureGjSettings() {
                        this.gjRows = Math.min(6, Math.max(1, Number.parseInt(this.gjRows, 10) || 1));
                        this.gjColumns = Math.min(8, Math.max(1, Number.parseInt(this.gjColumns, 10) || 1));

                        if (this.gjHasSeparator && this.gjColumns < 2) {
                            this.gjColumns = 2;
                        }

                        this.gjSeparatorBeforeColumn = Math.min(
                            this.gjColumns,
                            Math.max(2, Number.parseInt(this.gjSeparatorBeforeColumn, 10) || this.gjColumns)
                        );
                    },

                    nextGjLabel() {
                        const defaults = ['x', 'y', 'z', 'a', 'b', 'c', 'd', 'e'];
                        const used = this.gjFinalDefinitions
                            .map(item => String(item.label || '').trim().toLowerCase());

                        return defaults.find(label => ! used.includes(label.toLowerCase()))
                            || 'v' + (this.gjFinalDefinitions.length + 1);
                    },

                    addGjFinal() {
                        if (this.gjFinalDefinitions.length >= this.maximumVariables) {
                            return;
                        }

                        this.gjFinalDefinitions.push({
                            label: this.nextGjLabel(),
                            answer: ''
                        });

                        this.gjFinalCount = this.gjFinalDefinitions.length;
                    },

                    removeGjFinal(index) {
                        if (this.gjFinalDefinitions.length <= 1) {
                            return;
                        }

                        this.gjFinalDefinitions.splice(index, 1);
                        this.gjFinalCount = this.gjFinalDefinitions.length;
                    },

                    synchronizeGjFinalCount() {
                        let count = Number.parseInt(this.gjFinalCount, 10);

                        if (! Number.isFinite(count)) {
                            count = this.gjFinalDefinitions.length || 1;
                        }

                        count = Math.min(this.maximumVariables, Math.max(1, count));

                        while (this.gjFinalDefinitions.length < count) {
                            this.gjFinalDefinitions.push({
                                label: this.nextGjLabel(),
                                answer: ''
                            });
                        }

                        while (this.gjFinalDefinitions.length > count) {
                            this.gjFinalDefinitions.pop();
                        }

                        this.gjFinalCount = count;
                    },

                    gaussRows: @js($gaussRows),
                    gaussColumns: @js($gaussColumns),
                    gaussHasSeparator: @js((bool) $gaussHasSeparator),
                    gaussSeparatorBeforeColumn: @js($gaussSeparatorBeforeColumn),
                    gaussFinalDefinitions: @js(array_values($gaussFinalDefinitions)),
                    gaussFinalCount: {{ min(max(count($gaussFinalDefinitions), 1), 8) }},

                    ensureGaussSettings() {
                        this.gaussRows = Math.min(6, Math.max(1, Number.parseInt(this.gaussRows, 10) || 1));
                        this.gaussColumns = Math.min(8, Math.max(1, Number.parseInt(this.gaussColumns, 10) || 1));

                        if (this.gaussHasSeparator && this.gaussColumns < 2) {
                            this.gaussColumns = 2;
                        }

                        this.gaussSeparatorBeforeColumn = Math.min(
                            this.gaussColumns,
                            Math.max(2, Number.parseInt(this.gaussSeparatorBeforeColumn, 10) || this.gaussColumns)
                        );
                    },

                    nextGaussLabel() {
                        const defaults = ['x', 'y', 'z', 'a', 'b', 'c', 'd', 'e'];
                        const used = this.gaussFinalDefinitions
                            .map(item => String(item.label || '').trim().toLowerCase());

                        return defaults.find(label => ! used.includes(label.toLowerCase()))
                            || 'v' + (this.gaussFinalDefinitions.length + 1);
                    },

                    addGaussFinal() {
                        if (this.gaussFinalDefinitions.length >= this.maximumVariables) {
                            return;
                        }

                        this.gaussFinalDefinitions.push({
                            label: this.nextGaussLabel(),
                            answer: ''
                        });

                        this.gaussFinalCount = this.gaussFinalDefinitions.length;
                    },

                    removeGaussFinal(index) {
                        if (this.gaussFinalDefinitions.length <= 1) {
                            return;
                        }

                        this.gaussFinalDefinitions.splice(index, 1);
                        this.gaussFinalCount = this.gaussFinalDefinitions.length;
                    },

                    synchronizeGaussFinalCount() {
                        let count = Number.parseInt(this.gaussFinalCount, 10);

                        if (! Number.isFinite(count)) {
                            count = this.gaussFinalDefinitions.length || 1;
                        }

                        count = Math.min(this.maximumVariables, Math.max(1, count));

                        while (this.gaussFinalDefinitions.length < count) {
                            this.gaussFinalDefinitions.push({
                                label: this.nextGaussLabel(),
                                answer: ''
                            });
                        }

                        while (this.gaussFinalDefinitions.length > count) {
                            this.gaussFinalDefinitions.pop();
                        }

                        this.gaussFinalCount = count;
                    },

                    obeOperationsLatex: @js(array_values($obeOperationsLatex)),

                    addObeOperation() {
                        if (this.obeOperationsLatex.length < 12) {
                            this.obeOperationsLatex.push('');
                        }
                    },

                    removeObeOperation(index) {
                        if (this.obeOperationsLatex.length <= 1) {
                            return;
                        }

                        this.obeOperationsLatex.splice(index, 1);
                    },

                    insertObeToken(index, latex) {
                        const mathField = this.$root.querySelector(`[data-obe-math-field='${index}']`);

                        if (! mathField) {
                            return;
                        }

                        mathField.focus();

                        if (typeof mathField.insert === 'function') {
                            mathField.insert(latex);
                        } else {
                            mathField.value = `${mathField.value || ''}${latex}`;
                        }

                        this.obeOperationsLatex[index] = mathField.value;
                    },                    obeRows: @js($obeRows),
                    obeColumns: @js($obeColumns),
                    obeHasSeparator: @js((bool) $obeHasSeparator),
                    obeSeparatorBeforeColumn: @js($obeSeparatorBeforeColumn),

                    ensureObeSettings() {
                        this.obeRows = Math.min(6, Math.max(1, Number.parseInt(this.obeRows, 10) || 1));
                        this.obeColumns = Math.min(8, Math.max(1, Number.parseInt(this.obeColumns, 10) || 1));

                        if (this.obeHasSeparator && this.obeColumns < 2) {
                            this.obeColumns = 2;
                        }

                        this.obeSeparatorBeforeColumn = Math.min(
                            this.obeColumns,
                            Math.max(2, Number.parseInt(this.obeSeparatorBeforeColumn, 10) || this.obeColumns)
                        );
                    },
                    matrixRows: @js($matrixRows),
                    matrixColumns: @js($matrixColumns),
                    matrixSeparatorBeforeColumn: @js($matrixSeparatorBeforeColumn),

                    ensureMatrixSettings() {
                        this.matrixRows = Math.min(6, Math.max(1, Number.parseInt(this.matrixRows, 10) || 1));
                        this.matrixColumns = Math.min(8, Math.max(1, Number.parseInt(this.matrixColumns, 10) || 1));

                        if (this.questionType === 'augmented_matrix' && this.matrixColumns < 2) {
                            this.matrixColumns = 2;
                        }

                        this.matrixSeparatorBeforeColumn = Math.min(
                            this.matrixColumns,
                            Math.max(2, Number.parseInt(this.matrixSeparatorBeforeColumn, 10) || this.matrixColumns)
                        );
                    },
                    variableDefinitions: @js(array_values($variableDefinitions)),
                    variableCount: {{ min(max(count($variableDefinitions), 1), 8) }},
                    defaultLabels: ['x', 'y', 'z', 'a', 'b', 'c', 'd', 'e'],

                    nextLabel() {
                        const used = this.variableDefinitions
                            .map(item => String(item.label || '').trim().toLowerCase());

                        return this.defaultLabels.find(label => !used.includes(label.toLowerCase()))
                            || 'v' + (this.variableDefinitions.length + 1);
                    },

                    addVariable() {
                        if (this.variableDefinitions.length >= this.maximumVariables) {
                            return;
                        }

                        this.variableDefinitions.push({
                            label: this.nextLabel(),
                            answer: ''
                        });

                        this.variableCount = this.variableDefinitions.length;
                    },

                    removeVariable(index) {
                        if (this.variableDefinitions.length <= 1) {
                            return;
                        }

                        this.variableDefinitions.splice(index, 1);
                        this.variableCount = this.variableDefinitions.length;
                    },

                    synchronizeVariableCount() {
                        let count = Number.parseInt(this.variableCount, 10);

                        if (!Number.isFinite(count)) {
                            count = this.variableDefinitions.length || 1;
                        }

                        count = Math.min(this.maximumVariables, Math.max(1, count));

                        while (this.variableDefinitions.length < count) {
                            this.variableDefinitions.push({
                                label: this.nextLabel(),
                                answer: ''
                            });
                        }

                        while (this.variableDefinitions.length > count) {
                            this.variableDefinitions.pop();
                        }

                        this.variableCount = count;
                    }
                }"
                class="space-y-6 rounded-2xl border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">

                @csrf

                @if ($isEditing)
                    @method('PUT')
                @endif

                <section>
                    <p class="text-sm font-black text-white">
                        Tipe Soal <span class="text-cyan-200">*</span>
                    </p>

                    <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        <label class="cursor-pointer rounded-2xl border p-4 transition"
                               :class="questionType === 'short_text' ? 'border-cyan-300/40 bg-cyan-400/10' : 'border-white/10 bg-slate-950/35 hover:border-white/20'">
                            <input type="radio" name="question_type" value="short_text" x-model="questionType" class="sr-only">
                            <span class="block text-sm font-black text-white">Isian Singkat</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-400">Jawaban berupa teks atau nilai pendek.</span>
                        </label>

                        <label class="cursor-pointer rounded-2xl border p-4 transition"
                               :class="questionType === 'math_notation' ? 'border-cyan-300/40 bg-cyan-400/10' : 'border-white/10 bg-slate-950/35 hover:border-white/20'">
                            <input type="radio" name="question_type" value="math_notation" x-model="questionType" class="sr-only">
                            <span class="block text-sm font-black text-white">Notasi Matematika</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-400">Jawaban memakai notasi atau bentuk aljabar.</span>
                        </label>

                        <label class="cursor-pointer rounded-2xl border p-4 transition"
                               :class="questionType === 'variable_values' ? 'border-cyan-300/40 bg-cyan-400/10' : 'border-white/10 bg-slate-950/35 hover:border-white/20'">
                            <input type="radio" name="question_type" value="variable_values" x-model="questionType" class="sr-only">
                            <span class="block text-sm font-black text-white">Nilai Variabel</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-400">Tentukan jumlah serta nama variabel sesuai kebutuhan soal.</span>
                        </label>

                        <label class="cursor-pointer rounded-2xl border p-4 transition"
                               :class="questionType === 'matrix' ? 'border-cyan-300/40 bg-cyan-400/10' : 'border-white/10 bg-slate-950/35 hover:border-white/20'">
                            <input type="radio" name="question_type" value="matrix" x-model="questionType" @change="ensureMatrixSettings()" class="sr-only">
                            <span class="block text-sm font-black text-white">Matriks</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-400">Mahasiswa melengkapi matriks dengan ukuran yang ditentukan.</span>
                        </label>

                        <label class="cursor-pointer rounded-2xl border p-4 transition"
                               :class="questionType === 'augmented_matrix' ? 'border-violet-300/40 bg-violet-400/10' : 'border-white/10 bg-slate-950/35 hover:border-white/20'">
                            <input type="radio" name="question_type" value="augmented_matrix" x-model="questionType" @change="ensureMatrixSettings()" class="sr-only">
                            <span class="block text-sm font-black text-white">Matriks Teraugmentasi</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-400">Matriks dengan garis pemisah antara koefisien dan ruas kanan.</span>
                        </label>

                        <label class="cursor-pointer rounded-2xl border p-4 transition"
                               :class="questionType === 'gauss_jordan' ? 'border-violet-300/40 bg-violet-400/10' : 'border-white/10 bg-slate-950/35 hover:border-white/20'">
                            <input type="radio" name="question_type" value="gauss_jordan" x-model="questionType" @change="ensureGjSettings()" class="sr-only">
                            <span class="block text-sm font-black text-white">Gauss-Jordan</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-400">Mahasiswa membentuk eselon baris tereduksi dan menentukan nilai akhir.</span>
                        </label>
                        <label class="cursor-pointer rounded-2xl border p-4 transition"
                               :class="questionType === 'gauss_elimination' ? 'border-violet-300/40 bg-violet-400/10' : 'border-white/10 bg-slate-950/35 hover:border-white/20'">
                            <input type="radio" name="question_type" value="gauss_elimination" x-model="questionType" @change="ensureGaussSettings()" class="sr-only">
                            <span class="block text-sm font-black text-white">Eliminasi Gauss</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-400">Mahasiswa membentuk eselon baris dan menentukan nilai akhir.</span>
                        </label>
                        <label class="cursor-pointer rounded-2xl border p-4 transition"
                               :class="questionType === 'obe_matrix_operation' ? 'border-violet-300/40 bg-violet-400/10' : 'border-white/10 bg-slate-950/35 hover:border-white/20'">
                            <input type="radio" name="question_type" value="obe_matrix_operation" x-model="questionType" @change="ensureObeSettings()" class="sr-only">
                            <span class="block text-sm font-black text-white">Operasi Baris Elementer</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-400">Mahasiswa menulis operasi baris dan hasil matriksnya.</span>
                        </label>

                        <label class="cursor-pointer rounded-2xl border p-4 transition"
                               :class="questionType === 'checkbox' ? 'border-violet-300/40 bg-violet-400/10' : 'border-white/10 bg-slate-950/35 hover:border-white/20'">
                            <input type="radio" name="question_type" value="checkbox" x-model="questionType" class="sr-only">
                            <span class="block text-sm font-black text-white">Pilihan Lebih dari Satu</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-400">Mahasiswa dapat menandai lebih dari satu jawaban.</span>
                        </label>
                    </div>

                    @error('question_type')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </section>

                <section class="grid gap-5 md:grid-cols-[minmax(0,1fr)_180px]">
                    <div>
                        <label for="question_text" class="text-sm font-black text-white">
                            Pertanyaan <span class="text-cyan-200">*</span>
                        </label>

                        <textarea id="question_text" name="question_text" rows="5" maxlength="4000" required
                                  placeholder="Tuliskan pertanyaan untuk mahasiswa."
                                  class="mt-3 w-full resize-y rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm leading-6 text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">{{ old('question_text', $question->question_text) }}</textarea>

                        @error('question_text')
                            <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="points" class="text-sm font-black text-white">
                            Poin <span class="text-cyan-200">*</span>
                        </label>

                        <input id="points" name="points" type="number" min="1" max="100" required
                               value="{{ old('points', $question->points ?: 5) }}"
                               class="mt-3 w-full rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm font-black text-white outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">

                        @error('points')
                            <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                        @enderror

                        <label class="mt-5 flex cursor-pointer items-center gap-3 rounded-xl border border-white/10 bg-slate-950/35 px-3 py-3">
                            <input type="hidden" name="is_required" value="0">
                            <input type="checkbox" name="is_required" value="1"
                                   @checked(old('is_required', $question->is_required ?? true))
                                   class="rounded border-slate-500 bg-slate-900 text-cyan-400 focus:ring-cyan-400">
                            <span class="text-xs font-bold text-slate-300">Wajib dijawab</span>
                        </label>
                    </div>
                </section>

                <section>
                    <label for="equations_text" class="text-sm font-black text-white">
                        Baris Persamaan atau Informasi Matematis
                        <span class="font-medium text-slate-500">(opsional)</span>
                    </label>

                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        Tulis satu persamaan pada setiap baris. Bagian ini akan tampil terpisah di bawah pertanyaan.
                    </p>

                    <textarea id="equations_text" name="equations_text" rows="4" maxlength="3000"
                              placeholder="Contoh:&#10;x + y = 5&#10;2x - y = 4"
                              class="mt-3 w-full resize-y rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 font-mono text-sm leading-6 text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">{{ $equationsText }}</textarea>

                    @error('equations_text')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </section>

                <section x-show="questionType === 'short_text' || questionType === 'math_notation'" x-transition>
                    <label for="accepted_answers_text" class="text-sm font-black text-white">
                        Jawaban yang Diterima <span class="text-cyan-200">*</span>
                    </label>

                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        Tulis satu variasi jawaban per baris. Contoh: <span class="font-mono">x=3</span> dan <span class="font-mono">x = 3</span>.
                    </p>

                    <textarea id="accepted_answers_text" name="accepted_answers_text" rows="5" maxlength="3000"
                              placeholder="Contoh:&#10;x=3&#10;x = 3"
                              class="mt-3 w-full resize-y rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 font-mono text-sm leading-6 text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">{{ $acceptedAnswersText }}</textarea>

                    @error('accepted_answers_text')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </section>

                <section data-matrix-configuration="dynamic" x-show="questionType === 'matrix' || questionType === 'augmented_matrix'" x-transition>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-sm font-black text-white">
                                Konfigurasi Matriks <span class="text-cyan-200">*</span>
                            </p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Tentukan ukuran dan seluruh elemen matriks yang menjadi jawaban benar.
                            </p>
                        </div>

                        <div class="flex gap-3">
                            <label class="w-24">
                                <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Baris</span>
                                <input type="number" name="matrix_rows" min="1" max="6"
                                       x-model.number="matrixRows" @change="ensureMatrixSettings()"
                                       class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-center text-sm font-black text-white outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                            </label>

                            <label class="w-24">
                                <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Kolom</span>
                                <input type="number" name="matrix_columns" min="1" max="8"
                                       x-model.number="matrixColumns" @change="ensureMatrixSettings()"
                                       class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-center text-sm font-black text-white outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                            </label>
                        </div>
                    </div>

                    @error('matrix_rows')
                        <p class="mt-3 text-sm text-red-300">{{ $message }}</p>
                    @enderror

                    @error('matrix_columns')
                        <p class="mt-3 text-sm text-red-300">{{ $message }}</p>
                    @enderror

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label x-show="questionType === 'matrix'" x-transition class="block">
                            <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Label Matriks</span>
                            <input type="text" name="matrix_label" value="{{ $matrixLabel }}" maxlength="30"
                                   placeholder="Contoh: A"
                                   class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-sm font-black text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                        </label>

                        <label x-show="questionType === 'augmented_matrix'" x-transition class="block">
                            <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Kolom Awal Ruas Kanan</span>
                            <input type="number" name="matrix_separator_before_column" min="2"
                                   :max="matrixColumns" x-model.number="matrixSeparatorBeforeColumn"
                                   @change="ensureMatrixSettings()"
                                   class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-center text-sm font-black text-white outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                        </label>
                    </div>

                    @error('matrix_separator_before_column')
                        <p class="mt-3 text-sm text-red-300">{{ $message }}</p>
                    @enderror

                    <div class="mt-5 overflow-x-auto rounded-2xl border border-white/10 bg-slate-950/35 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-black text-white">Elemen Jawaban Matriks</p>
                            <span class="rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-[11px] font-bold text-slate-300">
                                <span x-text="matrixRows"></span> × <span x-text="matrixColumns"></span>
                            </span>
                        </div>

                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            Masukkan setiap elemen sesuai jawaban yang harus diperoleh mahasiswa.
                        </p>

                        <div class="mt-4 w-max">
                            <div class="grid gap-2" :style="'grid-template-columns: repeat(' + matrixColumns + ', 68px)'">
                                @for ($row = 0; $row < 6; $row++)
                                    @for ($column = 0; $column < 8; $column++)
                                        <input type="text"
                                               x-show="matrixRows > {{ $row }} && matrixColumns > {{ $column }}"
                                               name="matrix_answers[{{ $row }}][{{ $column }}]"
                                               value="{{ $matrixAnswers[$row][$column] ?? '' }}"
                                               autocomplete="off"
                                               placeholder="0"
                                               :class="{ 'border-l-4 border-l-violet-300': questionType === 'augmented_matrix' && matrixSeparatorBeforeColumn === {{ $column + 1 }} }"
                                               class="h-11 rounded-xl border border-white/10 bg-slate-950/60 px-2 text-center font-mono text-sm font-black text-white placeholder:text-slate-600 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                                    @endfor
                                @endfor
                            </div>
                        </div>
                    </div>

                    @error('matrix_answers')
                        <p class="mt-3 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </section>
                <section data-gauss-jordan-configuration="dynamic" x-show="questionType === 'gauss_jordan'" x-transition>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-sm font-black text-white">
                                Konfigurasi Gauss-Jordan <span class="text-cyan-200">*</span>
                            </p>

                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Mahasiswa akan membentuk bentuk eselon baris tereduksi dan mengisi nilai akhir setiap variabel.
                            </p>
                        </div>

                        <div class="flex gap-3">
                            <label class="w-24">
                                <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Baris</span>
                                <input type="number" name="gj_rows" min="1" max="6"
                                       x-model.number="gjRows" @change="ensureGjSettings()"
                                       class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-center text-sm font-black text-white outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                            </label>

                            <label class="w-24">
                                <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Kolom</span>
                                <input type="number" name="gj_columns" min="1" max="8"
                                       x-model.number="gjColumns" @change="ensureGjSettings()"
                                       class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-center text-sm font-black text-white outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                            </label>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-[auto_minmax(0,1fr)] sm:items-end">
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-white/10 bg-slate-950/35 px-4 py-3">
                            <input type="hidden" name="gj_has_separator" value="0">
                            <input type="checkbox"
                                   name="gj_has_separator"
                                   value="1"
                                   x-model="gjHasSeparator"
                                   @change="ensureGjSettings()"
                                   class="rounded border-slate-500 bg-slate-900 text-cyan-400 focus:ring-cyan-400">
                            <span class="text-sm font-bold text-slate-300">Gunakan garis pemisah ruas kanan</span>
                        </label>

                        <label x-show="gjHasSeparator" x-transition class="block">
                            <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Kolom Awal Ruas Kanan</span>
                            <input type="number" name="gj_separator_before_column" min="2"
                                   :max="gjColumns" x-model.number="gjSeparatorBeforeColumn"
                                   @change="ensureGjSettings()"
                                   class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-center text-sm font-black text-white outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                        </label>
                    </div>

                    <div class="mt-5 grid gap-4 xl:grid-cols-2">
                        <div class="overflow-x-auto rounded-2xl border border-white/10 bg-slate-950/35 p-4">
                            <p class="text-sm font-black text-white">Matriks Awal</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Matriks yang akan diselesaikan menggunakan metode Gauss-Jordan.</p>

                            <div class="mt-4 w-max">
                                <div class="grid gap-2" :style="'grid-template-columns: repeat(' + gjColumns + ', 62px)'">
                                    @for ($row = 0; $row < 6; $row++)
                                        @for ($column = 0; $column < 8; $column++)
                                            <input type="text"
                                                   x-show="gjRows > {{ $row }} && gjColumns > {{ $column }}"
                                                   name="gj_initial_matrix[{{ $row }}][{{ $column }}]"
                                                   value="{{ $gjInitialMatrix[$row][$column] ?? '' }}"
                                                   autocomplete="off"
                                                   placeholder="0"
                                                   :class="{ 'border-l-4 border-l-violet-300': gjHasSeparator && gjSeparatorBeforeColumn === {{ $column + 1 }} }"
                                                   class="h-11 rounded-xl border border-white/10 bg-slate-950/60 px-2 text-center font-mono text-sm font-black text-white placeholder:text-slate-600 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                                        @endfor
                                    @endfor
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-2xl border border-white/10 bg-slate-950/35 p-4">
                            <p class="text-sm font-black text-white">Target Bentuk Eselon Baris Tereduksi</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Diisi sebagai jawaban matriks yang akan dinilai pada mahasiswa.</p>

                            <div class="mt-4 w-max">
                                <div class="grid gap-2" :style="'grid-template-columns: repeat(' + gjColumns + ', 62px)'">
                                    @for ($row = 0; $row < 6; $row++)
                                        @for ($column = 0; $column < 8; $column++)
                                            <input type="text"
                                                   x-show="gjRows > {{ $row }} && gjColumns > {{ $column }}"
                                                   name="gj_rref_matrix[{{ $row }}][{{ $column }}]"
                                                   value="{{ $gjRrefMatrix[$row][$column] ?? '' }}"
                                                   autocomplete="off"
                                                   placeholder="0"
                                                   :class="{ 'border-l-4 border-l-violet-300': gjHasSeparator && gjSeparatorBeforeColumn === {{ $column + 1 }} }"
                                                   class="h-11 rounded-xl border border-white/10 bg-slate-950/60 px-2 text-center font-mono text-sm font-black text-white placeholder:text-slate-600 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                                        @endfor
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 rounded-2xl border border-white/10 bg-slate-950/35 p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="text-sm font-black text-white">
                                    Nilai Akhir Variabel <span class="text-cyan-200">*</span>
                                </p>

                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    Atur jumlah, nama, dan jawaban akhir variabel yang harus diisi mahasiswa.
                                </p>
                            </div>

                            <label class="w-full sm:w-40">
                                <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Jumlah variabel</span>
                                <input type="number" min="1" max="8"
                                       x-model.number="gjFinalCount"
                                       @change="synchronizeGjFinalCount()"
                                       @input.debounce.300ms="synchronizeGjFinalCount()"
                                       class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-center text-sm font-black text-white outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                            </label>
                        </div>

                        <div class="mt-4 space-y-3">
                            <template x-for="(variable, index) in gjFinalDefinitions" :key="index">
                                <div class="grid gap-3 rounded-xl border border-white/10 bg-white/[0.04] p-3 sm:grid-cols-[40px_minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-400/10 text-sm font-black text-violet-100" x-text="index + 1"></span>

                                    <label class="block">
                                        <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Nama Variabel</span>
                                        <input type="text"
                                               :name="'gj_final_definitions[' + index + '][label]'"
                                               x-model="variable.label"
                                               maxlength="40"
                                               placeholder="Contoh: x"
                                               autocomplete="off"
                                               class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-sm font-black text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                                    </label>

                                    <label class="block">
                                        <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Jawaban Akhir</span>
                                        <input type="text"
                                               :name="'gj_final_definitions[' + index + '][answer]'"
                                               x-model="variable.answer"
                                               placeholder="Contoh: -1/2"
                                               autocomplete="off"
                                               class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-center font-mono text-sm font-black text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                                    </label>

                                    <button type="button"
                                            @click="removeGjFinal(index)"
                                            :disabled="gjFinalDefinitions.length <= 1"
                                            class="h-10 rounded-xl border border-red-300/20 bg-red-400/10 px-3 text-xs font-black text-red-200 transition hover:bg-red-400/20 disabled:cursor-not-allowed disabled:opacity-40">
                                        Hapus
                                    </button>
                                </div>
                            </template>
                        </div>

                        <button type="button"
                                @click="addGjFinal()"
                                :disabled="gjFinalDefinitions.length >= maximumVariables"
                                class="mt-4 rounded-xl border border-violet-300/20 bg-violet-400/10 px-3.5 py-2 text-xs font-black text-violet-100 transition hover:bg-violet-400/20 disabled:cursor-not-allowed disabled:opacity-40">
                            + Tambah Variabel
                        </button>
                    </div>

                    @foreach (['gj_rows', 'gj_columns', 'gj_separator_before_column', 'gj_initial_matrix', 'gj_rref_matrix', 'gj_final_definitions'] as $errorField)
                        @error($errorField)
                            <p class="mt-3 text-sm text-red-300">{{ $message }}</p>
                        @enderror
                    @endforeach
                </section>
                <section data-gauss-elimination-configuration="dynamic" x-show="questionType === 'gauss_elimination'" x-transition>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-sm font-black text-white">
                                Konfigurasi Eliminasi Gauss <span class="text-cyan-200">*</span>
                            </p>

                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Mahasiswa akan mengisi bentuk eselon baris serta nilai akhir setiap variabel.
                            </p>
                        </div>

                        <div class="flex gap-3">
                            <label class="w-24">
                                <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Baris</span>
                                <input type="number" name="gauss_rows" min="1" max="6"
                                       x-model.number="gaussRows" @change="ensureGaussSettings()"
                                       class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-center text-sm font-black text-white outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                            </label>

                            <label class="w-24">
                                <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Kolom</span>
                                <input type="number" name="gauss_columns" min="1" max="8"
                                       x-model.number="gaussColumns" @change="ensureGaussSettings()"
                                       class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-center text-sm font-black text-white outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                            </label>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-[auto_minmax(0,1fr)] sm:items-end">
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-white/10 bg-slate-950/35 px-4 py-3">
                            <input type="hidden" name="gauss_has_separator" value="0">
                            <input type="checkbox"
                                   name="gauss_has_separator"
                                   value="1"
                                   x-model="gaussHasSeparator"
                                   @change="ensureGaussSettings()"
                                   class="rounded border-slate-500 bg-slate-900 text-cyan-400 focus:ring-cyan-400">
                            <span class="text-sm font-bold text-slate-300">Gunakan garis pemisah ruas kanan</span>
                        </label>

                        <label x-show="gaussHasSeparator" x-transition class="block">
                            <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Kolom Awal Ruas Kanan</span>
                            <input type="number" name="gauss_separator_before_column" min="2"
                                   :max="gaussColumns" x-model.number="gaussSeparatorBeforeColumn"
                                   @change="ensureGaussSettings()"
                                   class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-center text-sm font-black text-white outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                        </label>
                    </div>

                    <div class="mt-5 grid gap-4 xl:grid-cols-2">
                        <div class="overflow-x-auto rounded-2xl border border-white/10 bg-slate-950/35 p-4">
                            <p class="text-sm font-black text-white">Matriks Awal</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Matriks yang akan dieliminasi oleh mahasiswa.</p>

                            <div class="mt-4 w-max">
                                <div class="grid gap-2" :style="'grid-template-columns: repeat(' + gaussColumns + ', 62px)'">
                                    @for ($row = 0; $row < 6; $row++)
                                        @for ($column = 0; $column < 8; $column++)
                                            <input type="text"
                                                   x-show="gaussRows > {{ $row }} && gaussColumns > {{ $column }}"
                                                   name="gauss_initial_matrix[{{ $row }}][{{ $column }}]"
                                                   value="{{ $gaussInitialMatrix[$row][$column] ?? '' }}"
                                                   autocomplete="off"
                                                   placeholder="0"
                                                   :class="{ 'border-l-4 border-l-violet-300': gaussHasSeparator && gaussSeparatorBeforeColumn === {{ $column + 1 }} }"
                                                   class="h-11 rounded-xl border border-white/10 bg-slate-950/60 px-2 text-center font-mono text-sm font-black text-white placeholder:text-slate-600 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                                        @endfor
                                    @endfor
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-2xl border border-white/10 bg-slate-950/35 p-4">
                            <p class="text-sm font-black text-white">Acuan Bentuk Eselon Baris</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Diisi sebagai acuan dosen. Sistem tetap menerima bentuk eselon lain yang valid dan ekuivalen.</p>

                            <div class="mt-4 w-max">
                                <div class="grid gap-2" :style="'grid-template-columns: repeat(' + gaussColumns + ', 62px)'">
                                    @for ($row = 0; $row < 6; $row++)
                                        @for ($column = 0; $column < 8; $column++)
                                            <input type="text"
                                                   x-show="gaussRows > {{ $row }} && gaussColumns > {{ $column }}"
                                                   name="gauss_reference_matrix[{{ $row }}][{{ $column }}]"
                                                   value="{{ $gaussReferenceMatrix[$row][$column] ?? '' }}"
                                                   autocomplete="off"
                                                   placeholder="0"
                                                   :class="{ 'border-l-4 border-l-violet-300': gaussHasSeparator && gaussSeparatorBeforeColumn === {{ $column + 1 }} }"
                                                   class="h-11 rounded-xl border border-white/10 bg-slate-950/60 px-2 text-center font-mono text-sm font-black text-white placeholder:text-slate-600 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                                        @endfor
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 rounded-2xl border border-white/10 bg-slate-950/35 p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="text-sm font-black text-white">
                                    Nilai Akhir Variabel <span class="text-cyan-200">*</span>
                                </p>

                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    Atur jumlah, nama, dan jawaban akhir variabel yang harus diisi mahasiswa.
                                </p>
                            </div>

                            <label class="w-full sm:w-40">
                                <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Jumlah variabel</span>
                                <input type="number" min="1" max="8"
                                       x-model.number="gaussFinalCount"
                                       @change="synchronizeGaussFinalCount()"
                                       @input.debounce.300ms="synchronizeGaussFinalCount()"
                                       class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-center text-sm font-black text-white outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                            </label>
                        </div>

                        <div class="mt-4 space-y-3">
                            <template x-for="(variable, index) in gaussFinalDefinitions" :key="index">
                                <div class="grid gap-3 rounded-xl border border-white/10 bg-white/[0.04] p-3 sm:grid-cols-[40px_minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-400/10 text-sm font-black text-violet-100" x-text="index + 1"></span>

                                    <label class="block">
                                        <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Nama Variabel</span>
                                        <input type="text"
                                               :name="'gauss_final_definitions[' + index + '][label]'"
                                               x-model="variable.label"
                                               maxlength="40"
                                               placeholder="Contoh: x"
                                               autocomplete="off"
                                               class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-sm font-black text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                                    </label>

                                    <label class="block">
                                        <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Jawaban Akhir</span>
                                        <input type="text"
                                               :name="'gauss_final_definitions[' + index + '][answer]'"
                                               x-model="variable.answer"
                                               placeholder="Contoh: -1/2"
                                               autocomplete="off"
                                               class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-center font-mono text-sm font-black text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                                    </label>

                                    <button type="button"
                                            @click="removeGaussFinal(index)"
                                            :disabled="gaussFinalDefinitions.length <= 1"
                                            class="h-10 rounded-xl border border-red-300/20 bg-red-400/10 px-3 text-xs font-black text-red-200 transition hover:bg-red-400/20 disabled:cursor-not-allowed disabled:opacity-40">
                                        Hapus
                                    </button>
                                </div>
                            </template>
                        </div>

                        <button type="button"
                                @click="addGaussFinal()"
                                :disabled="gaussFinalDefinitions.length >= maximumVariables"
                                class="mt-4 rounded-xl border border-violet-300/20 bg-violet-400/10 px-3.5 py-2 text-xs font-black text-violet-100 transition hover:bg-violet-400/20 disabled:cursor-not-allowed disabled:opacity-40">
                            + Tambah Variabel
                        </button>
                    </div>

                    @foreach (['gauss_rows', 'gauss_columns', 'gauss_separator_before_column', 'gauss_initial_matrix', 'gauss_reference_matrix', 'gauss_final_definitions'] as $errorField)
                        @error($errorField)
                            <p class="mt-3 text-sm text-red-300">{{ $message }}</p>
                        @enderror
                    @endforeach
                </section>
                <section data-obe-configuration="dynamic" x-show="questionType === 'obe_matrix_operation'" x-transition>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-sm font-black text-white">
                                Konfigurasi Operasi Baris Elementer <span class="text-cyan-200">*</span>
                            </p>

                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Masukkan matriks awal, notasi operasi yang diterima, serta matriks hasil yang benar.
                            </p>
                        </div>

                        <div class="flex gap-3">
                            <label class="w-24">
                                <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Baris</span>
                                <input type="number" name="obe_rows" min="1" max="6"
                                       x-model.number="obeRows" @change="ensureObeSettings()"
                                       class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-center text-sm font-black text-white outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                            </label>

                            <label class="w-24">
                                <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Kolom</span>
                                <input type="number" name="obe_columns" min="1" max="8"
                                       x-model.number="obeColumns" @change="ensureObeSettings()"
                                       class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-center text-sm font-black text-white outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                            </label>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-[auto_minmax(0,1fr)] sm:items-end">
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-white/10 bg-slate-950/35 px-4 py-3">
                            <input type="checkbox"
                                   name="obe_has_separator"
                                   value="1"
                                   x-model="obeHasSeparator"
                                   @change="ensureObeSettings()"
                                   class="rounded border-slate-500 bg-slate-900 text-cyan-400 focus:ring-cyan-400">
                            <span class="text-sm font-bold text-slate-300">Gunakan garis pemisah ruas kanan</span>
                        </label>

                        <label x-show="obeHasSeparator" x-transition class="block">
                            <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Kolom Awal Ruas Kanan</span>
                            <input type="number" name="obe_separator_before_column" min="2"
                                   :max="obeColumns" x-model.number="obeSeparatorBeforeColumn"
                                   @change="ensureObeSettings()"
                                   class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-center text-sm font-black text-white outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                        </label>
                    </div>

                    <div class="mt-5 grid gap-4 xl:grid-cols-2">
                        <div class="overflow-x-auto rounded-2xl border border-white/10 bg-slate-950/35 p-4">
                            <p class="text-sm font-black text-white">Matriks Awal</p>
                            <p class="mt-1 text-xs text-slate-500">Elemen matriks sebelum dilakukan operasi.</p>

                            <div class="mt-4 w-max">
                                <div class="grid gap-2" :style="'grid-template-columns: repeat(' + obeColumns + ', 62px)'">
                                    @for ($row = 0; $row < 6; $row++)
                                        @for ($column = 0; $column < 8; $column++)
                                            <input type="text"
                                                   x-show="obeRows > {{ $row }} && obeColumns > {{ $column }}"
                                                   name="obe_initial_matrix[{{ $row }}][{{ $column }}]"
                                                   value="{{ $obeInitialMatrix[$row][$column] ?? '' }}"
                                                   autocomplete="off"
                                                   placeholder="0"
                                                   :class="{ 'border-l-4 border-l-violet-300': obeHasSeparator && obeSeparatorBeforeColumn === {{ $column + 1 }} }"
                                                   class="h-11 rounded-xl border border-white/10 bg-slate-950/60 px-2 text-center font-mono text-sm font-black text-white placeholder:text-slate-600 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                                        @endfor
                                    @endfor
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-2xl border border-white/10 bg-slate-950/35 p-4">
                            <p class="text-sm font-black text-white">Matriks Hasil</p>
                            <p class="mt-1 text-xs text-slate-500">Elemen matriks yang benar setelah operasi dilakukan.</p>

                            <div class="mt-4 w-max">
                                <div class="grid gap-2" :style="'grid-template-columns: repeat(' + obeColumns + ', 62px)'">
                                    @for ($row = 0; $row < 6; $row++)
                                        @for ($column = 0; $column < 8; $column++)
                                            <input type="text"
                                                   x-show="obeRows > {{ $row }} && obeColumns > {{ $column }}"
                                                   name="obe_result_matrix[{{ $row }}][{{ $column }}]"
                                                   value="{{ $obeResultMatrix[$row][$column] ?? '' }}"
                                                   autocomplete="off"
                                                   placeholder="0"
                                                   :class="{ 'border-l-4 border-l-violet-300': obeHasSeparator && obeSeparatorBeforeColumn === {{ $column + 1 }} }"
                                                   class="h-11 rounded-xl border border-white/10 bg-slate-950/60 px-2 text-center font-mono text-sm font-black text-white placeholder:text-slate-600 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                                        @endfor
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="text-sm font-black text-white">
                                    Notasi Operasi yang Diterima <span class="text-cyan-200">*</span>
                                </p>

                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    Gunakan editor matematika untuk menulis notasi operasi secara rapi. Tambahkan variasi hanya apabila memang ingin menerima bentuk notasi lain.
                                </p>
                            </div>

                            <button type="button"
                                    @click="addObeOperation()"
                                    :disabled="obeOperationsLatex.length >= 12"
                                    class="inline-flex justify-center rounded-xl border border-cyan-300/20 bg-cyan-400/10 px-3.5 py-2 text-xs font-black text-cyan-100 transition hover:bg-cyan-400/20 disabled:cursor-not-allowed disabled:opacity-40">
                                + Tambah Variasi
                            </button>
                        </div>

                        @error('obe_operations_latex')
                            <p class="mt-3 text-sm text-red-300">{{ $message }}</p>
                        @enderror

                        <div class="mt-4 space-y-3">
                            <template x-for="(operation, index) in obeOperationsLatex" :key="index">
                                <div class="rounded-2xl border border-white/10 bg-slate-950/35 p-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <p class="text-sm font-black text-white">
                                            Variasi Notasi <span x-text="index + 1"></span>
                                        </p>

                                        <button type="button"
                                                @click="removeObeOperation(index)"
                                                :disabled="obeOperationsLatex.length <= 1"
                                                class="rounded-lg border border-red-300/20 bg-red-400/10 px-3 py-1.5 text-xs font-black text-red-200 transition hover:bg-red-400/20 disabled:cursor-not-allowed disabled:opacity-40">
                                            Hapus
                                        </button>
                                    </div>

                                    <div class="mt-3 overflow-hidden rounded-xl border border-white/10 bg-white">
                                        <math-field
                                            :data-obe-math-field="index"
                                            x-init="$nextTick(() => { $el.value = operation || ''; })"
                                            @input="obeOperationsLatex[index] = $event.target.value"
                                            math-virtual-keyboard-policy="auto"
                                            smart-fence="on"
                                            class="block min-h-[64px] w-full border-0 bg-white px-4 py-3 text-center text-xl text-slate-900 shadow-none outline-none">
                                        </math-field>
                                    </div>

                                    <input type="hidden"
                                           :name="'obe_operations_latex[' + index + ']'"
                                           x-model="obeOperationsLatex[index]">

                                    

                                </div>
                            </template>
                        </div>

                        <p class="mt-3 text-xs leading-5 text-slate-500">
                            Contoh tampilan: <span class="font-semibold text-slate-300">B₂ ← −1/3 B₂</span>. Sistem tetap menerima jawaban mahasiswa dengan format <span class="font-mono">B2 &lt;- -1 per 3 B2</span>.
                        </p>
                    </div>
                </section>
                <section x-show="questionType === 'variable_values'" x-transition>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-sm font-black text-white">
                                Variabel dan Jawaban yang Diterima <span class="text-cyan-200">*</span>
                            </p>

                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Tentukan jumlah variabel, nama setiap variabel, dan nilai jawabannya. Mahasiswa hanya melihat variabel yang Anda buat.
                            </p>
                        </div>

                        <div class="w-full sm:w-40">
                            <label for="variable_count" class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                Jumlah variabel
                            </label>

                            <input id="variable_count"
                                   type="number"
                                   min="1"
                                   max="8"
                                   x-model.number="variableCount"
                                   @change="synchronizeVariableCount()"
                                   @input.debounce.300ms="synchronizeVariableCount()"
                                   class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-center text-sm font-black text-white outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                        </div>
                    </div>

                    @error('variable_definitions')
                        <p class="mt-3 text-sm text-red-300">{{ $message }}</p>
                    @enderror

                    <div class="mt-4 space-y-3">
                        <template x-for="(variable, index) in variableDefinitions" :key="index">
                            <div class="grid gap-3 rounded-2xl border border-white/10 bg-slate-950/35 p-4 sm:grid-cols-[46px_minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-400/10 text-sm font-black text-cyan-100" x-text="index + 1"></span>

                                <label class="block">
                                    <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Nama Variabel</span>

                                    <input type="text"
                                           :name="'variable_definitions[' + index + '][label]'"
                                           x-model="variable.label"
                                           maxlength="40"
                                           placeholder="Contoh: x atau harga"
                                           autocomplete="off"
                                           class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-sm font-black text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                                </label>

                                <label class="block">
                                    <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Jawaban Diterima</span>

                                    <input type="text"
                                           :name="'variable_definitions[' + index + '][answer]'"
                                           x-model="variable.answer"
                                           placeholder="Contoh: -1/2"
                                           autocomplete="off"
                                           class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-center font-mono text-sm font-black text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                                </label>

                                <button type="button"
                                        @click="removeVariable(index)"
                                        :disabled="variableDefinitions.length <= 1"
                                        class="h-10 rounded-xl border border-red-300/20 bg-red-400/10 px-3 text-xs font-black text-red-200 transition hover:bg-red-400/20 disabled:cursor-not-allowed disabled:opacity-40">
                                    Hapus
                                </button>
                            </div>
                        </template>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <button type="button"
                                @click="addVariable()"
                                :disabled="variableDefinitions.length >= maximumVariables"
                                class="rounded-xl border border-cyan-300/20 bg-cyan-400/10 px-3.5 py-2 text-xs font-black text-cyan-100 transition hover:bg-cyan-400/20 disabled:cursor-not-allowed disabled:opacity-40">
                            + Tambah Variabel
                        </button>

                        <p class="text-xs text-slate-500">
                            Maksimal 8 variabel. Nama dapat berupa <span class="font-mono">x_1</span>, <span class="font-mono">a</span>, <span class="font-mono">harga</span>, atau nama lain yang mudah dibaca.
                        </p>
                    </div>

                    <p class="mt-4 text-xs leading-5 text-slate-500">
                        Sistem menerima nilai bilangan atau pecahan, misalnya <span class="font-mono">-1/2</span> atau <span class="font-mono">-1 per 2</span>.
                    </p>
                </section>
                <section x-show="questionType === 'checkbox'" x-transition>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-sm font-black text-white">
                                Pilihan Jawaban <span class="text-cyan-200">*</span>
                            </p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Isi pilihan yang tersedia dan centang jawaban yang benar.
                            </p>
                        </div>

                        @error('checkbox_correct')
                            <p class="text-sm text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-3 space-y-3">
                        @foreach (['A', 'B', 'C', 'D'] as $key)
                            <label class="grid gap-3 rounded-xl border border-white/10 bg-slate-950/35 p-3 sm:grid-cols-[auto_minmax(0,1fr)] sm:items-center">
                                <span class="flex items-center gap-2">
                                    <input type="checkbox"
                                           name="checkbox_correct[]"
                                           value="{{ $key }}"
                                           @checked(in_array($key, $checkboxCorrect, true))
                                           class="rounded border-slate-500 bg-slate-900 text-cyan-400 focus:ring-cyan-400">

                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-white/10 text-xs font-black text-white">
                                        {{ $key }}
                                    </span>
                                </span>

                                <input type="text"
                                       name="checkbox_options[{{ $key }}]"
                                       value="{{ $checkboxOptions[$key] ?? '' }}"
                                       placeholder="Tulis pilihan {{ $key }}"
                                       class="w-full rounded-lg border border-white/10 bg-slate-950/60 px-3 py-2.5 text-sm text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                            </label>
                        @endforeach
                    </div>

                    @error('checkbox_options')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </section>

                <section>
                    <label for="explanation" class="text-sm font-black text-white">
                        Penjelasan Umpan Balik
                        <span class="font-medium text-slate-500">(opsional)</span>
                    </label>

                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        Penjelasan ini digunakan sebagai umpan balik saat jawaban mahasiswa belum tepat.
                    </p>

                    <textarea id="explanation" name="explanation" rows="4" maxlength="2000"
                              placeholder="Contoh: Periksa kembali koefisien dan operasi hitung pada setiap persamaan."
                              class="mt-3 w-full resize-y rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm leading-6 text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">{{ old('explanation', $question->explanation) }}</textarea>

                    @error('explanation')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </section>

                <div class="rounded-2xl border border-cyan-300/15 bg-cyan-400/[0.06] p-4 text-sm leading-6 text-slate-300">
                    Soal baru akan memperoleh nomor terakhir secara otomatis. Setelah kuis memiliki percobaan mahasiswa, isi soal akan dikunci agar nilai dan riwayat tetap konsisten.
                </div>

                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                    <a href="{{ route('dosen.kuis.show', $quiz) }}"
                       class="inline-flex justify-center rounded-xl border border-white/10 bg-white/[0.04] px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10">
                        Batal
                    </a>

                    <button type="submit"
                            class="inline-flex justify-center rounded-xl bg-cyan-400 px-5 py-3 text-sm font-black text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-300">
                        {{ $isEditing ? 'Simpan Perubahan' : 'Tambahkan Soal' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>