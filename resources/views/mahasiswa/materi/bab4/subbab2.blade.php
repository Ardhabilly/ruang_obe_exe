{{-- SUBBAB_4_2_SIMULASI_ESELON_TEREDUKSI_V1 --}}
@php
    $simulation42Key = 'contoh-simulasi-4-2-eselon-baris-tereduksi';
    $simulation42DefinitionVersion = 'subbab-4-2-gauss-jordan-v1';
    $simulation42Submission = $practiceSubmissions->get($simulation42Key);

    $simulation42StoredAnswers = is_array($simulation42Submission?->answers)
        ? $simulation42Submission->answers
        : [];

    $simulation42OldAnswers = old('answers', []);
    $simulation42OldAnswers = is_array($simulation42OldAnswers)
        ? $simulation42OldAnswers
        : [];

    $simulation42Answers = array_replace($simulation42StoredAnswers, $simulation42OldAnswers);

    $simulation42FeedbackRaw = is_array($simulation42Submission?->feedback)
        ? $simulation42Submission->feedback
        : [];

    $simulation42Feedback = is_array($simulation42FeedbackRaw['fields'] ?? null)
        ? $simulation42FeedbackRaw['fields']
        : collect($simulation42FeedbackRaw)
            ->except(['_meta', 'groups', 'fields'])
            ->filter(fn ($item) => is_array($item))
            ->all();

    $simulation42Meta = is_array($simulation42FeedbackRaw['_meta'] ?? null)
        ? $simulation42FeedbackRaw['_meta']
        : [];

    $simulation42UsesComponentAttemptScope = ($simulation42Meta['attempt_scope'] ?? null) === 'component'
        && ($simulation42Meta['definition_version'] ?? null) === $simulation42DefinitionVersion;

    if (! $simulation42UsesComponentAttemptScope && $simulation42Submission) {
        $simulation42Answers = $simulation42OldAnswers;
        $simulation42Feedback = [];
        $simulation42Meta = [];
    }

    /* SUBBAB_4_BANTUAN_TAMPIL_JAWABAN_FIX */
    foreach ($simulation42Feedback as $fieldKey => $fieldFeedback) {
        if (($fieldFeedback['state'] ?? null) !== 'revealed') {
            continue;
        }

        $simulation42Answers[$fieldKey] = (string) (
            $fieldFeedback['correct_answer']
            ?? $fieldFeedback['answer']
            ?? ($simulation42Answers[$fieldKey] ?? '')
        );
    }
    $simulation42Completed = $simulation42UsesComponentAttemptScope
        && (bool) ($simulation42Submission?->is_completed ?? false);

    /* SUBBAB_4_REVEALED_ANSWER_RENDER_FIX_V4 */
    foreach ($simulation42Feedback as $fieldKey => $fieldFeedback) {
        if (($fieldFeedback['state'] ?? null) !== 'revealed') {
            continue;
        }

        $revealedValue = (string) (
            $fieldFeedback['correct_answer']
            ?? $fieldFeedback['answer']
            ?? ($simulation42Answers[$fieldKey] ?? '')
        );

        $simulation42Answers[$fieldKey] = $revealedValue;
    }

    $simulation42Assisted = ($simulation42Meta['completion_mode'] ?? null) === 'bantuan';

    $simulation42MaxAttempts = max(1, (int) ($simulation42Meta['max_attempts'] ?? 3));
    $simulation42Attempts = max(0, min(
        $simulation42MaxAttempts,
        (int) ($simulation42Meta['attempts'] ?? 0)
    ));
    $simulation42RemainingAttempts = max(0, $simulation42MaxAttempts - $simulation42Attempts);

    $simulation42InputClass = function (string $fieldKey) use ($simulation42Feedback): string {
        return match ($simulation42Feedback[$fieldKey]['state'] ?? null) {
            'correct' => 'border-green-500 bg-green-50 text-green-950 focus:border-green-500 focus:ring-green-500',
            'revealed' => 'border-indigo-400 bg-indigo-50 text-indigo-950 focus:border-indigo-500 focus:ring-indigo-500',
            'wrong' => 'border-red-500 bg-red-50 text-red-950 focus:border-red-500 focus:ring-red-500',
            'empty' => 'border-yellow-400 bg-yellow-50 text-yellow-950 focus:border-yellow-500 focus:ring-yellow-500',
            default => 'border-slate-300 bg-white text-slate-900 focus:border-cyan-500 focus:ring-cyan-500',
        };
    };

    $simulation42InputLocked = function (string $fieldKey) use ($simulation42Feedback): bool {
        $field = $simulation42Feedback[$fieldKey] ?? [];

        return ! empty($field['is_revealed'])
            || (! empty($field['is_correct']) && ($field['state'] ?? null) === 'correct');
    };

    $fieldInput = function (
        string $fieldKey,
        string $label,
        string $sizeClass = 'h-10 w-14'
    ) use ($simulation42Answers, $simulation42InputClass, $simulation42InputLocked) {
        $value = (string) ($simulation42Answers[$fieldKey] ?? '');
        $disabled = $simulation42InputLocked($fieldKey) ? ' disabled' : '';

        return new \Illuminate\Support\HtmlString(
            '<input type="text"'
            . ' name="answers[' . e($fieldKey) . ']"'
            . ' value="' . e($value) . '"'
            . ' aria-label="' . e($label) . '"'
            . $disabled
            . ' class="' . e($sizeClass . ' rounded-xl border px-2 text-center font-bold shadow-sm transition focus:outline-none focus:ring-2 ' . $simulation42InputClass($fieldKey)) . '">'
        );
    };

    $operationInput = function (string $fieldKey, string $label) use (
        $simulation42Answers,
        $simulation42InputClass,
        $simulation42InputLocked,
        $simulation42Feedback
    ) {
        $value = (string) ($simulation42Answers[$fieldKey] ?? '');
        $readOnly = $simulation42InputLocked($fieldKey) ? ' read-only' : '';
        $hiddenId = 'subbab42-' . $fieldKey . '-hidden';

        if (($simulation42Feedback[$fieldKey]['state'] ?? null) === 'revealed') {
            $value = (string) ($simulation42Feedback[$fieldKey]['correct_answer'] ?? $value);
        }

        return new \Illuminate\Support\HtmlString(
            '<div class="mt-2 rounded-2xl border p-2 shadow-sm transition ' . e($simulation42InputClass($fieldKey)) . '">'
            . '<math-field'
            . ' data-operation-math-field'
            . ' data-hidden-input="' . e($hiddenId) . '"'
            . ' aria-label="' . e($label) . '"'
            . ' virtual-keyboard-mode="manual"'
            . $readOnly
            . ' class="block min-h-12 w-full overflow-x-auto rounded-xl border-0 bg-transparent px-3 py-2 text-left text-lg font-bold outline-none">'
            . e($value)
            . '</math-field>'
            . '<input'
            . ' type="hidden"'
            . ' id="' . e($hiddenId) . '"'
            . ' name="answers[' . e($fieldKey) . ']"'
            . ' value="' . e($value) . '">'
            . '</div>'
        );
    };

    $matrixRow = function (array $cells, string $label) use ($fieldInput) {
        $columns = count($cells);

        $html = '<span class="inline-grid items-center gap-x-2 gap-y-2 rounded-md border-x-2 border-slate-900 px-2 py-2 align-middle"'
            . ' style="grid-template-columns: repeat(' . $columns . ', minmax(3.25rem, auto));">';

        foreach ($cells as $index => $cell) {
            $rhsClass = $index === $columns - 1
                ? ' border-l-2 border-l-slate-700 pl-2'
                : '';

            if (is_array($cell) && isset($cell['field'])) {
                $html .= '<span class="' . $rhsClass . '">'
                    . $fieldInput(
                        $cell['field'],
                        $label . ', elemen ' . ($index + 1),
                        'h-10 w-14'
                    )
                    . '</span>';
                continue;
            }

            $html .= '<span class="flex h-10 min-w-10 items-center justify-center font-semibold text-slate-900'
                . $rhsClass . '">\\(' . e((string) $cell) . '\\)</span>';
        }

        $html .= '</span>';

        return new \Illuminate\Support\HtmlString($html);
    };

    $inputCells = fn (array $fields): array => array_map(
        fn (string $field) => ['field' => $field],
        $fields
    );

    $simulation42StatusClass = function () use ($simulation42Completed, $simulation42Assisted): string {
        if (! $simulation42Completed) {
            return 'bg-yellow-50 text-yellow-700';
        }

        return $simulation42Assisted
            ? 'bg-indigo-100 text-indigo-700'
            : 'bg-green-50 text-green-700';
    };

    $simulation42StatusLabel = function () use ($simulation42Completed, $simulation42Assisted): string {
        if (! $simulation42Completed) {
            return 'Perlu Dikerjakan';
        }

        return $simulation42Assisted
            ? 'Selesai dengan Bantuan'
            : 'Simulasi Selesai';
    };

    $simulation42FeedbackSummary = function () use (
        $simulation42Feedback,
        $simulation42Completed,
        $simulation42Assisted
    ): ?string {
        if (empty($simulation42Feedback)) {
            return null;
        }

        if ($simulation42Completed) {
            return $simulation42Assisted
                ? 'Simulasi selesai dengan bantuan. Pelajari kembali operasi baris pada setiap fase yang telah ditampilkan.'
                : 'Seluruh jawaban keputusan, notasi operasi, rincian perhitungan, dan matriks akhir sudah tepat.';
        }

        return 'Periksa kembali kolom berwarna merah. Kolom kuning perlu dilengkapi dan tidak mengurangi kesempatan.';
    };

    $steps = [
        [
            'phase' => 5,
            'step' => 0,
            'key' => 'f5_q1_baris3',
            'label' => 'Pertanyaan 1 (Evaluasi Baris-3)',
            'question' => 'Cek elemen tepat di atas 1 utama pada Baris-4, yaitu elemen Baris-3 Kolom-4. Apakah nilainya sudah 0?',
            'correct' => 'tidak',
            'description' => 'Elemen Baris-3 Kolom-4 bernilai \\(-5\\), sehingga harus dinolkan.',
            'operation' => [
                'title' => 'Target 5A',
                'target' => 'Mengenolkan angka \\(-5\\) pada Baris-3 Kolom-4.',
                'notation' => 'f5a_notasi',
                'row' => 'B_3',
                'coefficient' => 'f5a_k',
                'source' => ['0', '0', '0', '1', '\\frac{5}{3}'],
                'old' => ['0', '0', '1', '-5', '-7'],
                'product' => ['f5a_produk_1', 'f5a_produk_2', 'f5a_produk_3', 'f5a_produk_4', 'f5a_produk_5'],
                'result' => ['f5a_hasil_1', 'f5a_hasil_2', 'f5a_hasil_3', 'f5a_hasil_4', 'f5a_hasil_5'],
            ],
        ],
        [
            'phase' => 5,
            'step' => 1,
            'key' => 'f5_q2_baris2',
            'label' => 'Pertanyaan 2 (Evaluasi Baris-2)',
            'question' => 'Cek elemen Baris-2 Kolom-4. Apakah nilainya sudah 0?',
            'correct' => 'ya',
            'description' => 'Elemen Baris-2 Kolom-4 sudah bernilai \\(0\\), sehingga tidak memerlukan operasi tambahan.',
        ],
        [
            'phase' => 5,
            'step' => 2,
            'key' => 'f5_q3_baris1',
            'label' => 'Pertanyaan 3 (Evaluasi Baris-1)',
            'question' => 'Cek elemen paling atas pada Kolom-4, yaitu elemen Baris-1 Kolom-4. Apakah nilainya sudah 0?',
            'correct' => 'tidak',
            'description' => 'Elemen Baris-1 Kolom-4 bernilai \\(-1\\), sehingga harus dinolkan.',
            'operation' => [
                'title' => 'Target 5B',
                'target' => 'Mengenolkan angka \\(-1\\) pada Baris-1 Kolom-4.',
                'notation' => 'f5b_notasi',
                'row' => 'B_1',
                'coefficient' => 'f5b_k',
                'source' => ['0', '0', '0', '1', '\\frac{5}{3}'],
                'old' => ['1', '1', '2', '-1', '5'],
                'product' => ['f5b_produk_1', 'f5b_produk_2', 'f5b_produk_3', 'f5b_produk_4', 'f5b_produk_5'],
                'result' => ['f5b_hasil_1', 'f5b_hasil_2', 'f5b_hasil_3', 'f5b_hasil_4', 'f5b_hasil_5'],
            ],
        ],
        [
            'phase' => 6,
            'step' => 3,
            'key' => 'f6_q1_baris2',
            'label' => 'Pertanyaan 1 (Evaluasi Baris-2)',
            'question' => 'Cek elemen tepat di atas 1 utama pada Baris-3, yaitu elemen Baris-2 Kolom-3. Apakah nilainya sudah 0?',
            'correct' => 'tidak',
            'description' => 'Elemen Baris-2 Kolom-3 bernilai \\(1\\), sehingga harus dinolkan.',
            'operation' => [
                'title' => 'Target 6A',
                'target' => 'Mengenolkan angka \\(1\\) pada Baris-2 Kolom-3.',
                'notation' => 'f6a_notasi',
                'row' => 'B_2',
                'coefficient' => 'f6a_k',
                'source' => ['0', '0', '1', '0', '\\frac{4}{3}'],
                'old' => ['0', '1', '1', '0', '5'],
                'product' => ['f6a_produk_1', 'f6a_produk_2', 'f6a_produk_3', 'f6a_produk_4', 'f6a_produk_5'],
                'result' => ['f6a_hasil_1', 'f6a_hasil_2', 'f6a_hasil_3', 'f6a_hasil_4', 'f6a_hasil_5'],
            ],
        ],
        [
            'phase' => 6,
            'step' => 4,
            'key' => 'f6_q2_baris1',
            'label' => 'Pertanyaan 2 (Evaluasi Baris-1)',
            'question' => 'Cek elemen paling atas pada Kolom-3, yaitu elemen Baris-1 Kolom-3. Apakah nilainya sudah 0?',
            'correct' => 'tidak',
            'description' => 'Elemen Baris-1 Kolom-3 bernilai \\(2\\), sehingga harus dinolkan.',
            'operation' => [
                'title' => 'Target 6B',
                'target' => 'Mengenolkan angka \\(2\\) pada Baris-1 Kolom-3.',
                'notation' => 'f6b_notasi',
                'row' => 'B_1',
                'coefficient' => 'f6b_k',
                'source' => ['0', '0', '1', '0', '\\frac{4}{3}'],
                'old' => ['1', '1', '2', '0', '\\frac{20}{3}'],
                'product' => ['f6b_produk_1', 'f6b_produk_2', 'f6b_produk_3', 'f6b_produk_4', 'f6b_produk_5'],
                'result' => ['f6b_hasil_1', 'f6b_hasil_2', 'f6b_hasil_3', 'f6b_hasil_4', 'f6b_hasil_5'],
            ],
        ],
        [
            'phase' => 7,
            'step' => 5,
            'key' => 'f7_q1_baris1',
            'label' => 'Pertanyaan 1 (Evaluasi Baris-1)',
            'question' => 'Setelah Fase 6, cek elemen Baris-1 Kolom-2 yang berada di atas 1 utama pada Baris-2. Apakah nilainya sudah 0?',
            'correct' => 'tidak',
            'description' => 'Elemen Baris-1 Kolom-2 bernilai \\(1\\), sehingga harus dinolkan.',
            'operation' => [
                'title' => 'Target 7A',
                'target' => 'Mengenolkan angka \\(1\\) pada Baris-1 Kolom-2.',
                'notation' => 'f7a_notasi',
                'row' => 'B_1',
                'coefficient' => 'f7a_k',
                'source' => ['0', '1', '0', '0', '\\frac{11}{3}'],
                'old' => ['1', '1', '0', '0', '4'],
                'product' => ['f7a_produk_1', 'f7a_produk_2', 'f7a_produk_3', 'f7a_produk_4', 'f7a_produk_5'],
                'result' => ['f7a_hasil_1', 'f7a_hasil_2', 'f7a_hasil_3', 'f7a_hasil_4', 'f7a_hasil_5'],
            ],
        ],
    ];

    $stepsByPhase = collect($steps)->groupBy('phase')->all();

    $decisionAnswers = collect($steps)
        ->mapWithKeys(fn (array $step) => [
            $step['key'] => strtolower(trim((string) ($simulation42Answers[$step['key']] ?? ''))),
        ])
        ->all();

    $decisionCorrectAnswers = collect($steps)
        ->mapWithKeys(fn (array $step) => [$step['key'] => $step['correct']])
        ->all();

    $decisionDescriptions = collect($steps)
        ->mapWithKeys(fn (array $step) => [$step['key'] => $step['description']])
        ->all();

    $operationFields = collect($steps)
        ->filter(fn (array $step) => isset($step['operation']))
        ->mapWithKeys(function (array $step) {
            $operation = $step['operation'];

            return [
                $step['key'] => array_merge(
                    [$operation['notation'], $operation['coefficient']],
                    $operation['product'],
                    $operation['result']
                ),
            ];
        })
        ->all();

    $fieldStates = collect($simulation42Feedback)
        ->mapWithKeys(fn (array $feedback, string $key) => [$key => $feedback['state'] ?? null])
        ->all();

    $fieldMessages = collect($simulation42Feedback)
        ->mapWithKeys(fn (array $feedback, string $key) => [$key => $feedback['message'] ?? null])
        ->all();

    $practiceModalPayload = session('practice_modal');
    $practiceModal = is_array($practiceModalPayload)
        && ($practiceModalPayload['practice_key'] ?? null) === $simulation42Key
        ? $practiceModalPayload
        : null;
@endphp

<section class="space-y-8">
    <div class="space-y-4">
        <h2 class="text-2xl font-black text-slate-950">
            4.2 Simulasi Mengubah Matriks Menjadi Eselon Baris Tereduksi
        </h2>

        <p>
            Mari lanjutkan algoritma matriks empat variabel
            \(\left(x_1, x_2, x_3, x_4\right)\) yang telah mencapai bentuk
            eselon baris pada materi sebelumnya. Untuk memperoleh bentuk eselon
            baris tereduksi, proses dilakukan secara mundur dari kanan bawah
            ke kiri atas.
        </p>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm">
        <p class="mb-4 text-sm font-black uppercase tracking-[0.18em] text-cyan-700">
            Matriks Eselon Baris Awal
        </p>

        <div class="inline-block rounded-2xl border border-slate-200 bg-slate-50 px-6 py-4 text-lg text-slate-950">
            \[
                \left[
                \begin{array}{rrrr|r}
                    1 & 1 & 2 & -1 & 5 \\
                    0 & 1 & 1 & 0 & 5 \\
                    0 & 0 & 1 & -5 & -7 \\
                    0 & 0 & 0 & 1 & \frac{5}{3}
                \end{array}
                \right]
            \]
        </div>
    </div>

    <form
        id="contoh-simulasi-4-2-form"
        method="POST"
        action="{{ route('mahasiswa.practice.submit', [$lesson->slug, $simulation42Key]) }}"
        data-practice-scroll-form
        x-data="gaussJordanSimulation({
            decisions: @js($decisionAnswers),
            correctAnswers: @js($decisionCorrectAnswers),
            descriptions: @js($decisionDescriptions),
            operationFields: @js($operationFields),
            fieldStates: @js($fieldStates),
            fieldMessages: @js($fieldMessages),
            completed: @js($simulation42Completed),
        })"
        x-init="initialize()"
        class="space-y-8"
    >
        @csrf

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">
                        Contoh Simulasi 4.2
                    </p>

                    <h3 class="mt-1 text-xl font-black text-slate-950">
                        Iterasi Mundur Metode Eliminasi Gauss-Jordan
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Jawab pertanyaan secara berurutan, lalu tuliskan notasi dan
                        rincian Operasi Baris Elementer pada setiap target.
                    </p>
                </div>

                <span class="w-fit rounded-full px-4 py-2 text-sm font-bold {{ $simulation42StatusClass() }}">
                    {{ $simulation42StatusLabel() }}
                </span>
            </div>

            @if (! $simulation42Completed)
                <div class="mt-5 flex flex-col gap-2 rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm text-cyan-950 sm:flex-row sm:items-center sm:justify-between">
                    <p class="font-bold">
                        Kesempatan tersisa: {{ $simulation42RemainingAttempts }} dari {{ $simulation42MaxAttempts }}
                    </p>

                    <p class="text-xs leading-5 text-cyan-800">
                        Kolom yang belum diisi tidak mengurangi kesempatan.
                    </p>
                </div>
            @endif

            @if ($simulation42Submission && $simulation42UsesComponentAttemptScope)
                <div class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4 text-sm text-cyan-900">
                    Nilai Contoh Simulasi:
                    <span class="font-black">
                        {{ $simulation42Submission->score }}/{{ $simulation42Submission->max_score }}
                    </span>
                </div>
            @endif

            @foreach ($stepsByPhase as $phase => $phaseSteps)
                @php
                    $phaseHeading = match ((int) $phase) {
                        5 => 'Evaluasi Kolom ke-4 — Bergerak ke Atas',
                        6 => 'Evaluasi Kolom ke-3 — Bergerak ke Atas',
                        default => 'Evaluasi Kolom ke-2 — Bergerak ke Atas',
                    };

                    $phaseDescription = match ((int) $phase) {
                        5 => 'Gunakan 1 utama pada Baris-4 sebagai acuan untuk mengenolkan semua elemen di atasnya pada Kolom-4.',
                        6 => 'Pindah satu kolom ke kiri. Gunakan 1 utama pada Baris-3 untuk mengenolkan elemen di atasnya pada Kolom-3.',
                        default => 'Pindah satu kolom lagi ke kiri. Gunakan 1 utama pada Baris-2 untuk mengenolkan elemen terakhir di atasnya.',
                    };
                @endphp

                <section
                    x-show="isVisible({{ $phaseSteps[0]['step'] }})"
                    x-cloak
                    x-transition
                    class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">
                            Fase {{ $phase }}
                        </p>

                        <h3 class="mt-1 text-xl font-black text-slate-950">
                            {{ $phaseHeading }}
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            {{ $phaseDescription }}
                        </p>
                    </div>

                    @foreach ($phaseSteps as $step)
                        <div
                            x-show="isVisible({{ $step['step'] }})"
                            x-cloak
                            x-transition
                            class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5"
                        >
                            <p class="font-black text-slate-950">
                                {{ $step['label'] }}
                            </p>

                            <p class="mt-2 text-sm leading-6 text-slate-700">
                                {!! $step['question'] !!}
                            </p>

                            <input
                                type="hidden"
                                name="answers[{{ $step['key'] }}]"
                                x-model="decisions['{{ $step['key'] }}']"
                            >

                            <div class="mt-4 grid grid-cols-2 gap-3 sm:max-w-sm">
                                <button
                                    type="button"
                                    @click="choose('{{ $step['key'] }}', 'ya', {{ $step['step'] }})"
                                    :class="choiceClass('{{ $step['key'] }}', 'ya')"
                                    @disabled($simulation42InputLocked($step['key']))
                                    class="rounded-xl border px-4 py-3 text-sm font-black transition"
                                >
                                    Ya
                                </button>

                                <button
                                    type="button"
                                    @click="choose('{{ $step['key'] }}', 'tidak', {{ $step['step'] }})"
                                    :class="choiceClass('{{ $step['key'] }}', 'tidak')"
                                    @disabled($simulation42InputLocked($step['key']))
                                    class="rounded-xl border px-4 py-3 text-sm font-black transition"
                                >
                                    Tidak
                                </button>
                            </div>

                            <div
                                x-show="hasAnswer('{{ $step['key'] }}')"
                                x-cloak
                                :class="feedbackClass('{{ $step['key'] }}')"
                                class="mt-4 rounded-2xl border p-4 text-sm leading-6"
                            >
                                <p x-text="feedbackMessage('{{ $step['key'] }}')"></p>
                            </div>

                            @if (isset($step['operation']))
                                @php
                                    $operation = $step['operation'];
                                @endphp

                                <div
                                    x-show="needsOperation('{{ $step['key'] }}')"
                                    x-cloak
                                    x-transition
                                    class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4"
                                >
                                    <div class="space-y-2 text-sm leading-6 text-cyan-950">
                                        <p>
                                            <strong>Tindakan ({{ $operation['title'] }}):</strong>
                                            {{ $operation['target'] }}
                                        </p>

                                        <p>
                                            Tentukan konstanta pengali \(k\), kemudian tuliskan
                                            notasi Operasi Baris Elementer secara lengkap.
                                        </p>
                                    </div>

                                    <label class="mt-4 block text-sm font-black text-slate-800">
                                        Notasi Operasi
                                        {!! $operationInput($operation['notation'], 'Notasi operasi ' . $operation['title']) !!}
                                    </label>

                                    <div class="mt-5 overflow-x-auto rounded-2xl border border-cyan-200 bg-white p-4">
                                        <p class="text-sm font-black text-cyan-950">
                                            Rincian
                                        </p>

                                        <div class="mt-4 min-w-max space-y-4 text-center text-sm text-slate-900">
                                            <div class="flex items-center justify-center gap-2">
                                                <span>\({!! $operation['row'] !!} \leftarrow\)</span>
                                                {!! $fieldInput($operation['coefficient'], 'Konstanta pengali ' . $operation['title']) !!}
                                                {!! $matrixRow($operation['source'], 'Baris acuan ' . $operation['title']) !!}
                                                <span>\(+\)</span>
                                                {!! $matrixRow($operation['old'], 'Baris target awal ' . $operation['title']) !!}
                                            </div>

                                            <div class="flex items-center justify-center gap-2">
                                                <span>\({!! $operation['row'] !!} \leftarrow\)</span>
                                                {!! $matrixRow($inputCells($operation['product']), 'Hasil perkalian ' . $operation['title']) !!}
                                                <span>\(+\)</span>
                                                {!! $matrixRow($operation['old'], 'Baris target awal ' . $operation['title']) !!}
                                            </div>

                                            <div class="flex items-center justify-center gap-2">
                                                <span>\({!! $operation['row'] !!} \leftarrow\)</span>
                                                {!! $matrixRow($inputCells($operation['result']), 'Hasil operasi ' . $operation['title']) !!}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <p
                                            x-show="actionNotice['{{ $step['key'] }}']"
                                            x-cloak
                                            x-text="actionNotice['{{ $step['key'] }}']"
                                            class="text-sm font-semibold text-red-700"
                                        ></p>

                                        <button
                                            type="button"
                                            @click="continueAfterOperation('{{ $step['key'] }}', {{ $step['step'] }})"
                                            class="w-full rounded-xl bg-cyan-600 px-5 py-3 text-sm font-black text-white transition hover:bg-cyan-700 sm:ml-auto sm:w-auto"
                                        >
                                            Lanjut ke Pertanyaan Berikutnya
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    @if ((int) $phase === 5)
                        <div x-show="isVisible(3)" x-cloak x-transition class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-5 text-center">
                            <p class="mb-4 text-sm font-black text-slate-700">
                                Matriks Setelah Fase 5
                            </p>

                            <div class="inline-block rounded-2xl border border-slate-200 bg-slate-50 px-6 py-4 text-lg text-slate-950">
                                \[
                                    \left[
                                    \begin{array}{rrrr|r}
                                        1 & 1 & 2 & 0 & \frac{20}{3} \\
                                        0 & 1 & 1 & 0 & 5 \\
                                        0 & 0 & 1 & 0 & \frac{4}{3} \\
                                        0 & 0 & 0 & 1 & \frac{5}{3}
                                    \end{array}
                                    \right]
                                \]
                            </div>
                        </div>
                    @endif

                    @if ((int) $phase === 6)
                        <div x-show="isVisible(5)" x-cloak x-transition class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-5 text-center">
                            <p class="mb-4 text-sm font-black text-slate-700">
                                Matriks Setelah Fase 6
                            </p>

                            <div class="inline-block rounded-2xl border border-slate-200 bg-slate-50 px-6 py-4 text-lg text-slate-950">
                                \[
                                    \left[
                                    \begin{array}{rrrr|r}
                                        1 & 1 & 0 & 0 & 4 \\
                                        0 & 1 & 0 & 0 & \frac{11}{3} \\
                                        0 & 0 & 1 & 0 & \frac{4}{3} \\
                                        0 & 0 & 0 & 1 & \frac{5}{3}
                                    \end{array}
                                    \right]
                                \]
                            </div>
                        </div>
                    @endif
                </section>
            @endforeach

            <section
                x-show="isVisible(6)"
                x-cloak
                x-transition
                class="mt-8 rounded-2xl border border-green-200 bg-green-50 p-6"
            >
                <p class="text-sm font-bold uppercase tracking-wide text-green-700">
                    Output Matriks Eselon Baris Tereduksi
                </p>

                <h3 class="mt-1 text-xl font-black text-slate-950">
                    Algoritma Selesai
                </h3>

                <p class="mt-2 text-sm leading-6 text-slate-700">
                    Susun hasil akhir seluruh baris ke dalam matriks berikut.
                </p>

                <div class="mt-5 overflow-x-auto rounded-2xl border border-green-200 bg-white p-5">
                    <div class="min-w-max space-y-4 text-center text-sm text-slate-900">
                        <div class="flex items-center justify-center gap-2">
                            <span>\(B_1\)</span>
                            {!! $matrixRow($inputCells(['final_11', 'final_12', 'final_13', 'final_14', 'final_15']), 'Matriks akhir Baris-1') !!}
                        </div>

                        <div class="flex items-center justify-center gap-2">
                            <span>\(B_2\)</span>
                            {!! $matrixRow($inputCells(['final_21', 'final_22', 'final_23', 'final_24', 'final_25']), 'Matriks akhir Baris-2') !!}
                        </div>

                        <div class="flex items-center justify-center gap-2">
                            <span>\(B_3\)</span>
                            {!! $matrixRow($inputCells(['final_31', 'final_32', 'final_33', 'final_34', 'final_35']), 'Matriks akhir Baris-3') !!}
                        </div>

                        <div class="flex items-center justify-center gap-2">
                            <span>\(B_4\)</span>
                            {!! $matrixRow($inputCells(['final_41', 'final_42', 'final_43', 'final_44', 'final_45']), 'Matriks akhir Baris-4') !!}
                        </div>
                    </div>
                </div>
            </section>

            @if ($simulation42FeedbackSummary())
                <p class="mt-6 rounded-xl px-4 py-3 text-sm font-semibold {{ $simulation42Completed ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-900' }}">
                    {{ $simulation42FeedbackSummary() }}
                </p>
            @endif

            @if (! $simulation42Completed)
                <div class="mt-6 flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs leading-5 text-slate-500">
                        Pastikan setiap fase dan matriks akhir telah dilengkapi sebelum memeriksa jawaban.
                    </p>

                    <button
                        type="submit"
                        class="rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-700"
                    >
                        Cek Jawaban Simulasi
                    </button>
                </div>
            @endif
        </div>
    </form>

    @if ($practiceModal)
        @php
            $modalStatus = $practiceModal['status'] ?? 'revision';
            $modalIsSuccess = $modalStatus === 'success';
            $modalIsIncomplete = $modalStatus === 'incomplete';
            $modalIsAssisted = $modalStatus === 'assisted';

            $modalMessages = is_array($practiceModal['feedback_messages'] ?? null)
                ? collect($practiceModal['feedback_messages'])
                    ->reject(fn ($message) => str_starts_with((string) $message, 'Bagian yang masih perlu diperbaiki:'))
                    ->values()
                    ->all()
                : [];
        @endphp

        <div
            x-data="{ showSimulationModal: true }"
            x-cloak
            x-show="showSimulationModal"
            x-transition.opacity
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/70 px-4 py-6 backdrop-blur-sm"
            role="dialog"
            aria-modal="true"
            aria-labelledby="simulasi42-modal-title"
        >
            <div
                x-show="showSimulationModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="scale-95 opacity-0"
                x-transition:enter-end="scale-100 opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="scale-100 opacity-100"
                x-transition:leave-end="scale-95 opacity-0"
                class="max-h-[calc(100vh-3rem)] w-full max-w-md overflow-y-auto rounded-[1.5rem] border border-white/10 bg-white shadow-2xl sm:max-w-lg"
            >
                <div class="p-5 sm:p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-xl font-black {{ $modalIsSuccess ? 'bg-green-100 text-green-700' : ($modalIsAssisted ? 'bg-indigo-100 text-indigo-700' : ($modalIsIncomplete ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700')) }}">
                            {{ $modalIsSuccess ? '✓' : ($modalIsAssisted ? 'i' : ($modalIsIncomplete ? '!' : '×')) }}
                        </div>

                        <div class="min-w-0">
                            <p id="simulasi42-modal-title" class="text-lg font-bold text-slate-900">
                                {{ $practiceModal['title'] ?? 'Hasil Pemeriksaan' }}
                            </p>

                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                {{ $practiceModal['message'] ?? '' }}
                            </p>
                        </div>
                    </div>

                    @if (! empty($modalMessages))
                        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <ul class="space-y-2 text-sm leading-6 text-slate-600">
                                @foreach ($modalMessages as $modalMessage)
                                    <li class="flex gap-2">
                                        <span class="mt-1 font-black text-red-500">•</span>
                                        <span>{{ $modalMessage }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mt-6 flex justify-end">
                        <button
                            type="button"
                            @click="showSimulationModal = false"
                            class="w-full rounded-xl px-5 py-3 text-sm font-bold transition sm:w-auto {{ $modalIsSuccess ? 'bg-green-600 text-white hover:bg-green-700' : ($modalIsAssisted ? 'bg-indigo-600 text-white hover:bg-indigo-700' : ($modalIsIncomplete ? 'bg-yellow-400 text-slate-900 hover:bg-yellow-300' : 'bg-cyan-600 text-white hover:bg-cyan-700')) }}"
                        >
                            {{ $practiceModal['button_label'] ?? 'Tutup' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</section>

<script>
    function gaussJordanSimulation(config) {
        return {
            decisions: config.decisions || {},
            correctAnswers: config.correctAnswers || {},
            descriptions: config.descriptions || {},
            operationFields: config.operationFields || {},
            fieldStates: config.fieldStates || {},
            fieldMessages: config.fieldMessages || {},
            completed: Boolean(config.completed),
            unlockedStep: 0,
            actionNotice: {},

            initialize() {
                if (this.completed) {
                    this.unlockedStep = 6;
                    return;
                }

                const keys = Object.keys(this.correctAnswers);
                let highest = 0;

                for (let index = 0; index < keys.length; index++) {
                    const key = keys[index];

                    if (this.decisions[key] !== this.correctAnswers[key]) {
                        break;
                    }

                    if (
                        this.correctAnswers[key] === 'tidak'
                        && !this.operationIsFilled(key)
                    ) {
                        break;
                    }

                    highest = index + 1;
                }

                this.unlockedStep = highest;
            },

            isVisible(step) {
                return this.completed || step <= this.unlockedStep;
            },

            hasAnswer(key) {
                return Boolean(this.decisions[key]) || Boolean(this.fieldStates[key]);
            },

            choose(key, value, step) {
                if (this.completed || ['correct', 'revealed'].includes(this.fieldStates[key])) {
                    return;
                }

                this.decisions[key] = value;
                this.actionNotice[key] = '';

                if (value === this.correctAnswers[key] && value === 'ya') {
                    this.unlockedStep = Math.max(this.unlockedStep, step + 1);
                }
            },

            needsOperation(key) {
                return this.decisions[key] === this.correctAnswers[key]
                    && this.correctAnswers[key] === 'tidak';
            },

            operationIsFilled(key) {
                const fields = this.operationFields[key] || [];

                return fields.every((fieldName) => {
                    const input = document.querySelector(`[name="answers[${fieldName}]"]`);

                    return input && String(input.value || '').trim() !== '';
                });
            },

            continueAfterOperation(key, step) {
                if (!this.operationIsFilled(key)) {
                    this.actionNotice[key] = 'Lengkapi notasi operasi dan seluruh rincian perhitungan terlebih dahulu.';
                    return;
                }

                this.actionNotice[key] = '';
                this.unlockedStep = Math.max(this.unlockedStep, step + 1);
            },

            choiceClass(key, option) {
                const answer = this.decisions[key];
                const state = this.fieldStates[key] || null;

                if (state === 'revealed') {
                    return option === this.correctAnswers[key]
                        ? 'border-indigo-400 bg-indigo-50 text-indigo-800'
                        : 'border-slate-200 bg-white text-slate-500';
                }

                if (state === 'correct') {
                    return option === this.correctAnswers[key]
                        ? 'border-green-500 bg-green-50 text-green-800'
                        : 'border-slate-200 bg-white text-slate-500';
                }

                if (state === 'wrong' && answer === option) {
                    return 'border-red-500 bg-red-50 text-red-800';
                }

                if (answer === option) {
                    return answer === this.correctAnswers[key]
                        ? 'border-green-500 bg-green-50 text-green-800'
                        : 'border-red-400 bg-red-50 text-red-800';
                }

                return 'border-slate-300 bg-white text-slate-700 hover:border-cyan-400 hover:bg-cyan-50';
            },

            feedbackClass(key) {
                const answer = this.decisions[key];
                const state = this.fieldStates[key] || null;

                if (state === 'revealed') {
                    return 'border-indigo-200 bg-indigo-50 text-indigo-900';
                }

                if (state === 'correct' || answer === this.correctAnswers[key]) {
                    return 'border-green-200 bg-green-50 text-green-900';
                }

                return 'border-red-200 bg-red-50 text-red-900';
            },

            feedbackMessage(key) {
                if (this.fieldMessages[key]) {
                    return this.fieldMessages[key];
                }

                if (this.decisions[key] === this.correctAnswers[key]) {
                    return this.descriptions[key];
                }

                return 'Pilihan tersebut belum tepat. Periksa kembali nilai elemen pada posisi yang ditanyakan.';
            },
        };
    }

    (function () {
        const scrollKey = 'ruangobe-practice-scroll:contoh-simulasi-4-2-eselon-baris-tereduksi';
        const form = document.getElementById('contoh-simulasi-4-2-form');

        document.querySelectorAll('math-field[data-operation-math-field]').forEach(function (mathField) {
            const hiddenInput = document.getElementById(mathField.dataset.hiddenInput);

            if (! hiddenInput) {
                return;
            }

            const initialValue = hiddenInput.value || mathField.textContent.trim();

            if (initialValue && ! mathField.value) {
                mathField.value = initialValue;
            }

            hiddenInput.value = mathField.value || initialValue || '';

            mathField.addEventListener('input', function () {
                hiddenInput.value = mathField.value || '';
            });

            mathField.addEventListener('change', function () {
                hiddenInput.value = mathField.value || '';
            });
        });

        if (form) {
            form.addEventListener('submit', function () {
                sessionStorage.setItem(scrollKey, String(window.scrollY));
            });
        }

        @if ($practiceModal)
            const savedPosition = sessionStorage.getItem(scrollKey);

            if (savedPosition !== null) {
                requestAnimationFrame(function () {
                    setTimeout(function () {
                        window.scrollTo({
                            top: Number(savedPosition),
                            left: 0,
                            behavior: 'auto'
                        });

                        sessionStorage.removeItem(scrollKey);
                    }, 0);
                });
            }
        @endif
    })();
</script>
