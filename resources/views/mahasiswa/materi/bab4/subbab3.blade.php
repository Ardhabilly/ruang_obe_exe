{{-- SUBBAB_4_3_PENYELESAIAN_GAUSS_JORDAN_V1 --}}
@php
    $cek43Key = 'cek-pemahaman-4-3-membaca-rref';
    $activity43Key = 'aktivitas-4-2-gauss-jordan';
    $activity43DefinitionVersion = 'subbab-4-3-gauss-jordan-v1';

    $cek43Submission = $practiceSubmissions->get($cek43Key);
    $activity43Submission = $practiceSubmissions->get($activity43Key);

    $preparePracticeState = function ($submission, string $definitionVersion): array {
        $storedAnswers = is_array($submission?->answers)
            ? $submission->answers
            : [];

        $oldAnswers = old('answers', []);
        $oldAnswers = is_array($oldAnswers) ? $oldAnswers : [];

        $answers = array_replace($storedAnswers, $oldAnswers);

        $feedbackRaw = is_array($submission?->feedback)
            ? $submission->feedback
            : [];

        $feedback = is_array($feedbackRaw['fields'] ?? null)
            ? $feedbackRaw['fields']
            : collect($feedbackRaw)
                ->except(['_meta', 'groups', 'fields'])
                ->filter(fn ($item) => is_array($item))
                ->all();

        $meta = is_array($feedbackRaw['_meta'] ?? null)
            ? $feedbackRaw['_meta']
            : [];

        $usesComponentAttemptScope = ($meta['attempt_scope'] ?? null) === 'component'
            && ($meta['definition_version'] ?? null) === $definitionVersion;

        if (! $usesComponentAttemptScope && $submission) {
            $answers = $oldAnswers;
            $feedback = [];
            $meta = [];
        }

        /*
        | Jawaban yang dibuka setelah tiga kesempatan harus menggantikan old input.
        | Dengan cara ini, nilai benar tampil pada kolom terkait, bukan jawaban
        | salah dari percobaan terakhir.
        */
        foreach ($feedback as $fieldKey => $fieldFeedback) {
            if (($fieldFeedback['state'] ?? null) !== 'revealed') {
                continue;
            }

            $answers[$fieldKey] = (string) (
                $fieldFeedback['correct_answer']
                ?? $fieldFeedback['answer']
                ?? ($answers[$fieldKey] ?? '')
            );
        }

        return [
            'answers' => $answers,
            'feedback' => $feedback,
            'meta' => $meta,
            'uses_component_attempt_scope' => $usesComponentAttemptScope,
            'completed' => $usesComponentAttemptScope && (bool) ($submission?->is_completed ?? false),
            'assisted' => ($meta['completion_mode'] ?? null) === 'bantuan',
            'max_attempts' => max(1, (int) ($meta['max_attempts'] ?? 3)),
            'attempts' => max(0, (int) ($meta['attempts'] ?? 0)),
        ];
    };

    $cek43State = $preparePracticeState($cek43Submission, $activity43DefinitionVersion);
    $activity43State = $preparePracticeState($activity43Submission, $activity43DefinitionVersion);

    $activity43RemainingAttempts = max(
        0,
        $activity43State['max_attempts'] - min($activity43State['attempts'], $activity43State['max_attempts'])
    );

    $inputClass = function (array $feedback, string $fieldKey): string {
        return match ($feedback[$fieldKey]['state'] ?? null) {
            'correct' => 'border-green-500 bg-green-50 text-green-950 focus:border-green-500 focus:ring-green-500',
            'revealed' => 'border-indigo-400 bg-indigo-50 text-indigo-950 focus:border-indigo-500 focus:ring-indigo-500',
            'wrong' => 'border-red-500 bg-red-50 text-red-950 focus:border-red-500 focus:ring-red-500',
            'empty' => 'border-yellow-400 bg-yellow-50 text-yellow-950 focus:border-yellow-500 focus:ring-yellow-500',
            default => 'border-slate-300 bg-white text-slate-900 focus:border-cyan-500 focus:ring-cyan-500',
        };
    };

    $isLocked = function (array $feedback, string $fieldKey): bool {
        $field = $feedback[$fieldKey] ?? [];

        return ! empty($field['is_revealed'])
            || (! empty($field['is_correct']) && ($field['state'] ?? null) === 'correct');
    };

    $textInput = function (
        array $answers,
        array $feedback,
        string $fieldKey,
        string $label,
        string $sizeClass = 'h-10 w-16'
    ) use ($inputClass, $isLocked) {
        $value = (string) ($answers[$fieldKey] ?? '');
        $disabled = $isLocked($feedback, $fieldKey) ? ' disabled' : '';

        return new \Illuminate\Support\HtmlString(
            '<input type="text"'
            . ' name="answers[' . e($fieldKey) . ']"'
            . ' value="' . e($value) . '"'
            . ' aria-label="' . e($label) . '"'
            . $disabled
            . ' class="' . e($sizeClass . ' rounded-xl border px-2 text-center font-bold shadow-sm transition focus:outline-none focus:ring-2 ' . $inputClass($feedback, $fieldKey)) . '">'
        );
    };

    $operationInput = function (
        array $answers,
        array $feedback,
        string $fieldKey,
        string $label,
        string $prefix
    ) use ($inputClass, $isLocked) {
        $value = (string) ($answers[$fieldKey] ?? '');
        $readOnly = $isLocked($feedback, $fieldKey) ? ' read-only' : '';
        $hiddenId = $prefix . '-' . $fieldKey . '-hidden';

        return new \Illuminate\Support\HtmlString(
            '<div class="mt-2 rounded-2xl border p-2 shadow-sm transition ' . e($inputClass($feedback, $fieldKey)) . '">'
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

    $matrixRow = function (
        array $cells,
        string $label,
        array $answers = [],
        array $feedback = []
    ) use ($textInput) {
        $columns = count($cells);

        $html = '<span class="inline-grid items-center gap-x-2 gap-y-2 rounded-md border-x-2 border-slate-900 px-2 py-2 align-middle"'
            . ' style="grid-template-columns: repeat(' . $columns . ', minmax(3.25rem, auto));">';

        foreach ($cells as $index => $cell) {
            $rhsClass = $index === $columns - 1
                ? ' border-l-2 border-l-slate-700 pl-2'
                : '';

            if (is_array($cell) && isset($cell['field'])) {
                $html .= '<span class="' . $rhsClass . '">'
                    . $textInput(
                        $answers,
                        $feedback,
                        $cell['field'],
                        $label . ', elemen ' . ($index + 1),
                        'h-10 w-14'
                    )
                    . '</span>';
                continue;
            }

            $html .= '<span class="flex h-10 min-w-10 items-center justify-center font-semibold text-slate-900'
                . $rhsClass . '">\(' . e((string) $cell) . '\)</span>';
        }

        $html .= '</span>';

        return new \Illuminate\Support\HtmlString($html);
    };

    $matrixBlock = function (
        array $rows,
        string $label,
        array $answers = [],
        array $feedback = []
    ) use ($textInput) {
        $html = '<span class="inline-grid gap-y-2 rounded-md border-x-2 border-slate-900 px-2 py-2 align-middle">';

        foreach ($rows as $rowIndex => $cells) {
            $columns = count($cells);

            $html .= '<span class="grid items-center gap-x-2"'
                . ' style="grid-template-columns: repeat(' . $columns . ', minmax(3.25rem, auto));">';

            foreach ($cells as $index => $cell) {
                $rhsClass = $index === $columns - 1
                    ? ' border-l-2 border-l-slate-700 pl-2'
                    : '';

                if (is_array($cell) && isset($cell['field'])) {
                    $html .= '<span class="' . $rhsClass . '">'
                        . $textInput(
                            $answers,
                            $feedback,
                            $cell['field'],
                            $label . ', baris ' . ($rowIndex + 1) . ', elemen ' . ($index + 1),
                            'h-10 w-14'
                        )
                        . '</span>';
                    continue;
                }

                $html .= '<span class="flex h-10 min-w-10 items-center justify-center font-semibold text-slate-900'
                    . $rhsClass . '">\(' . e((string) $cell) . '\)</span>';
            }

            $html .= '</span>';
        }

        $html .= '</span>';

        return new \Illuminate\Support\HtmlString($html);
    };

    $inputCells = fn (array $fields): array => array_map(
        fn (string $field) => ['field' => $field],
        $fields
    );

    $activityOperations = [
        'f4a' => [
            'phase' => 'fase4',
            'decision' => 'f4_q1_baris2',
            'target_label' => 'Target 4A: Mengenolkan angka \(-1\) pada Baris-2 Kolom-3.',
            'action' => 'Nolkan Baris-2 dengan acuan Baris-3.',
            'notation' => 'f4a_notasi',
            'target_row' => 'B_2',
            'coefficient' => 'f4a_k',
            'source' => ['0', '0', '1', '1'],
            'old' => ['0', '1', '-1', '-1'],
            'product' => ['f4a_produk_1', 'f4a_produk_2', 'f4a_produk_3', 'f4a_produk_4'],
            'result' => ['f4a_hasil_1', 'f4a_hasil_2', 'f4a_hasil_3', 'f4a_hasil_4'],
        ],
        'f4b' => [
            'phase' => 'fase4',
            'decision' => 'f4_q2_baris1',
            'target_label' => 'Target 4B: Mengenolkan angka \(-\frac{1}{2}\) pada Baris-1 Kolom-3.',
            'action' => 'Nolkan Baris-1 dengan acuan Baris-3.',
            'notation' => 'f4b_notasi',
            'target_row' => 'B_1',
            'coefficient' => 'f4b_k',
            'source' => ['0', '0', '1', '1'],
            'old' => ['1', '1', '-1/2', '2'],
            'product' => ['f4b_produk_1', 'f4b_produk_2', 'f4b_produk_3', 'f4b_produk_4'],
            'result' => ['f4b_hasil_1', 'f4b_hasil_2', 'f4b_hasil_3', 'f4b_hasil_4'],
        ],
        'f5a' => [
            'phase' => 'fase5',
            'decision' => 'f5_q1_baris1',
            'target_label' => 'Target 5A: Mengenolkan angka \(1\) pada Baris-1 Kolom-2.',
            'action' => 'Nolkan Baris-1 dengan acuan Baris-2 yang baru.',
            'notation' => 'f5a_notasi',
            'target_row' => 'B_1',
            'coefficient' => 'f5a_k',
            'source' => ['0', '1', '0', '0'],
            'old' => ['1', '1', '0', '5/2'],
            'product' => ['f5a_produk_1', 'f5a_produk_2', 'f5a_produk_3', 'f5a_produk_4'],
            'result' => ['f5a_hasil_1', 'f5a_hasil_2', 'f5a_hasil_3', 'f5a_hasil_4'],
        ],
    ];

    $activityDecisions = [
        'f4_q1_baris2' => [
            'label' => 'Pertanyaan 1 (Evaluasi Baris-2)',
            'question' => 'Cek elemen tepat di atas elemen utama Baris-3, yaitu Baris-2 Kolom-3. Apakah nilainya sudah 0?',
            'correct' => 'tidak',
        ],
        'f4_q2_baris1' => [
            'label' => 'Pertanyaan 2 (Evaluasi Baris-1)',
            'question' => 'Cek elemen teratas pada Baris-1 Kolom-3. Apakah nilainya sudah 0?',
            'correct' => 'tidak',
        ],
        'f5_q1_baris1' => [
            'label' => 'Pertanyaan 1 (Evaluasi Baris-1)',
            'question' => 'Setelah Fase 4, cek elemen di atas 1 utama Baris-2, yaitu Baris-1 Kolom-2. Apakah nilainya sudah 0?',
            'correct' => 'tidak',
        ],
    ];

    $activityDecisionAnswers = collect($activityDecisions)
        ->mapWithKeys(fn (array $decision, string $key) => [
            $key => strtolower(trim((string) ($activity43State['answers'][$key] ?? ''))),
        ])
        ->all();

    /* SUBBAB_4_3_EXPECTED_DECISIONS_FIX_V1 */
    $activityExpectedDecisions = collect($activityDecisions)
        ->mapWithKeys(fn (array $item, string $key) => [
            $key => $item['correct'],
        ])
        ->all();
    $activityFieldStates = collect($activity43State['feedback'])
        ->mapWithKeys(fn (array $item, string $key) => [$key => $item['state'] ?? null])
        ->all();

    $activityModalPayload = session('practice_modal');

    $activityModal = is_array($activityModalPayload)
        && ($activityModalPayload['practice_key'] ?? null) === $activity43Key
        ? $activityModalPayload
        : null;

    $cekModalPayload = session('practice_modal');

    $cekModal = is_array($cekModalPayload)
        && ($cekModalPayload['practice_key'] ?? null) === $cek43Key
        ? $cekModalPayload
        : null;

    $statusClass = function (bool $completed, bool $assisted): string {
        if (! $completed) {
            return 'bg-yellow-50 text-yellow-700';
        }

        return $assisted
            ? 'bg-indigo-100 text-indigo-700'
            : 'bg-green-50 text-green-700';
    };

    $statusLabel = function (bool $completed, bool $assisted, string $label): string {
        if (! $completed) {
            return 'Perlu Dikerjakan';
        }

        return $assisted
            ? 'Selesai dengan Bantuan'
            : $label . ' Selesai';
    };
@endphp

<section class="space-y-8">
    <div class="space-y-4">
        <h2 class="text-2xl font-black text-slate-950">
            4.3 Menyelesaikan SPL dengan Metode Eliminasi Gauss-Jordan
        </h2>

        <p>
            Setelah matriks diperbesar mencapai bentuk eselon baris tereduksi,
            setiap variabel dapat dibaca langsung dari setiap baris. Oleh karena itu,
            Metode Eliminasi Gauss-Jordan tidak memerlukan substitusi balik.
        </p>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm">
        <p class="mb-4 text-sm font-black uppercase tracking-[0.18em] text-cyan-700">
            Matriks Eselon Baris Tereduksi
        </p>

        <div class="inline-block rounded-2xl border border-slate-200 bg-slate-50 px-6 py-4 text-lg text-slate-950">
            \[
                \left[
                \begin{array}{rrrr|r}
                    1 & 0 & 0 & 0 & \frac{1}{3} \\
                    0 & 1 & 0 & 0 & \frac{11}{3} \\
                    0 & 0 & 1 & 0 & \frac{4}{3} \\
                    0 & 0 & 0 & 1 & \frac{5}{3}
                \end{array}
                \right]
            \]
        </div>
    </div>

    <form
        id="cek-pemahaman-4-3-form"
        method="POST"
        action="{{ route('mahasiswa.practice.submit', [$lesson->slug, $cek43Key]) }}"
        class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
    >
        @csrf

        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">
                    Cek Pemahaman 4.3
                </p>

                <h3 class="mt-1 text-xl font-black text-slate-950">
                    Membaca Solusi dari Matriks Eselon Baris Tereduksi
                </h3>

                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Terjemahkan setiap baris matriks ke bentuk persamaan linear,
                    kemudian tentukan nilai setiap variabelnya.
                </p>
            </div>

            <span class="w-fit rounded-full px-4 py-2 text-sm font-bold {{ $statusClass($cek43State['completed'], $cek43State['assisted']) }}">
                {{ $statusLabel($cek43State['completed'], $cek43State['assisted'], 'Cek Pemahaman') }}
            </span>
        </div>

        @if ($cek43Submission && $cek43State['uses_component_attempt_scope'])
            <div class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4 text-sm text-cyan-900">
                Status pemeriksaan tersimpan. Kolom berwarna indigo menampilkan jawaban bantuan.
            </div>
        @endif

        <div class="mt-6 space-y-4">
            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="min-w-max space-y-4 text-sm text-slate-900">
                    <div class="flex items-center gap-2">
                        <span>\(1x_1 + 0x_2 + 0x_3 + 0x_4 =\)</span>
                        {!! $textInput($cek43State['answers'], $cek43State['feedback'], 'cek43_b1_ruas_kanan', 'Konstanta Baris 1') !!}
                        <span>\(\Rightarrow x_1 =\)</span>
                        {!! $textInput($cek43State['answers'], $cek43State['feedback'], 'cek43_b1_solusi', 'Nilai x1') !!}
                    </div>

                    <div class="flex items-center gap-2">
                        <span>\(0x_1 + 1x_2 + 0x_3 + 0x_4 =\)</span>
                        {!! $textInput($cek43State['answers'], $cek43State['feedback'], 'cek43_b2_ruas_kanan', 'Konstanta Baris 2') !!}
                        <span>\(\Rightarrow x_2 =\)</span>
                        {!! $textInput($cek43State['answers'], $cek43State['feedback'], 'cek43_b2_solusi', 'Nilai x2') !!}
                    </div>

                    <div class="flex items-center gap-2">
                        <span>\(0x_1 + 0x_2 + 1x_3 + 0x_4 =\)</span>
                        {!! $textInput($cek43State['answers'], $cek43State['feedback'], 'cek43_b3_ruas_kanan', 'Konstanta Baris 3') !!}
                        <span>\(\Rightarrow x_3 =\)</span>
                        {!! $textInput($cek43State['answers'], $cek43State['feedback'], 'cek43_b3_solusi', 'Nilai x3') !!}
                    </div>

                    <div class="flex items-center gap-2">
                        <span>\(0x_1 + 0x_2 + 0x_3 + 1x_4 =\)</span>
                        {!! $textInput($cek43State['answers'], $cek43State['feedback'], 'cek43_b4_ruas_kanan', 'Konstanta Baris 4') !!}
                        <span>\(\Rightarrow x_4 =\)</span>
                        {!! $textInput($cek43State['answers'], $cek43State['feedback'], 'cek43_b4_solusi', 'Nilai x4') !!}
                    </div>
                </div>
            </div>
        </div>

        @if (! $cek43State['completed'])
            <div class="mt-6 flex justify-end border-t border-slate-200 pt-5">
                <button
                    type="submit"
                    class="rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-700"
                >
                    Cek Pemahaman
                </button>
            </div>
        @endif
    </form>

    <form
        id="aktivitas-4-2-form"
        method="POST"
        action="{{ route('mahasiswa.practice.submit', [$lesson->slug, $activity43Key]) }}"
        x-data="aktivitasGaussJordan43({
            decisions: @js($activityDecisionAnswers),
            expectedDecisions: @js($activityExpectedDecisions),
            fieldStates: @js($activityFieldStates),
            completed: @js($activity43State['completed']),
        })"
        x-init="initialize()"
        class="space-y-8"
    >
        @csrf

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">
                        Aktivitas 4.2
                    </p>

                    <h3 class="mt-1 text-xl font-black text-slate-950">
                        Menyelesaikan SPL dengan Eliminasi Gauss-Jordan
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Lanjutkan matriks eselon baris dari aktivitas pada bab sebelumnya
                        dengan iterasi mundur hingga menjadi matriks eselon baris tereduksi.
                    </p>
                </div>

                <span class="w-fit rounded-full px-4 py-2 text-sm font-bold {{ $statusClass($activity43State['completed'], $activity43State['assisted']) }}">
                    {{ $statusLabel($activity43State['completed'], $activity43State['assisted'], 'Aktivitas') }}
                </span>
            </div>

            @if (! $activity43State['completed'])
                <div class="mt-5 flex flex-col gap-2 rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm text-cyan-950 sm:flex-row sm:items-center sm:justify-between">
                    <p class="font-bold">
                        Kesempatan tersisa: {{ $activity43RemainingAttempts }} dari {{ $activity43State['max_attempts'] }}
                    </p>

                    <p class="text-xs leading-5 text-cyan-800">
                        Kolom kosong tidak mengurangi kesempatan.
                    </p>
                </div>
            @endif

            @if ($activity43Submission && $activity43State['uses_component_attempt_scope'])
                <div class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4 text-sm text-cyan-900">
                    Nilai Aktivitas:
                    <span class="font-black">
                        {{ $activity43Submission->score }}/{{ $activity43Submission->max_score }}
                    </span>

                    @if ($activity43State['assisted'])
                        <span class="mt-1 block text-xs leading-5">
                            Jawaban bantuan ditampilkan langsung pada kolom berwarna indigo.
                        </span>
                    @endif
                </div>
            @endif

            <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-5 text-center">
                <p class="mb-4 text-sm font-black uppercase tracking-[0.18em] text-cyan-700">
                    Matriks Eselon Baris Awal
                </p>

                <div class="inline-block rounded-2xl border border-slate-200 bg-white px-6 py-4 text-lg text-slate-950">
                    \[
                        \left[
                        \begin{array}{rrr|r}
                            1 & 1 & -\frac{1}{2} & 2 \\
                            0 & 1 & -1 & -1 \\
                            0 & 0 & 1 & 1
                        \end{array}
                        \right]
                    \]
                </div>
            </div>

            <input type="hidden" name="answers[f4_q1_baris2]" x-model="decisions.f4_q1_baris2">
            <input type="hidden" name="answers[f4_q2_baris1]" x-model="decisions.f4_q2_baris1">
            <input type="hidden" name="answers[f5_q1_baris1]" x-model="decisions.f5_q1_baris1">

            <div class="mt-8 space-y-8">
                <section class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">
                        Fase 4
                    </p>

                    <h4 class="mt-1 text-xl font-black text-slate-950">
                        Evaluasi Kolom ke-3 – Bergerak ke Atas
                    </h4>

                    <div class="mt-5 space-y-5">
                        @foreach (['f4_q1_baris2', 'f4_q2_baris1'] as $decisionKey)
                            @php
                                $decision = $activityDecisions[$decisionKey];
                                $operation = collect($activityOperations)->first(
                                    fn (array $item) => $item['decision'] === $decisionKey
                                );
                            @endphp

                            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                                <p class="font-black text-slate-950">
                                    {{ $decision['label'] }}
                                </p>

                                <p class="mt-2 text-sm leading-6 text-slate-700">
                                    {{ $decision['question'] }}
                                </p>

                                <div class="mt-4 grid grid-cols-2 gap-3 sm:max-w-sm">
                                    <button
                                        type="button"
                                        @click="choose('{{ $decisionKey }}', 'ya')"
                                        :class="choiceClass('{{ $decisionKey }}', 'ya')"
                                        :disabled="isLocked('{{ $decisionKey }}')"
                                        class="rounded-xl border px-4 py-3 text-sm font-black transition"
                                    >
                                        Ya
                                    </button>

                                    <button
                                        type="button"
                                        @click="choose('{{ $decisionKey }}', 'tidak')"
                                        :class="choiceClass('{{ $decisionKey }}', 'tidak')"
                                        :disabled="isLocked('{{ $decisionKey }}')"
                                        class="rounded-xl border px-4 py-3 text-sm font-black transition"
                                    >
                                        Tidak
                                    </button>
                                </div>

                                <p
                                    x-cloak
                                    x-show="hasWrongDecision('{{ $decisionKey }}')"
                                    class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900"
                                >
                                    Periksa kembali elemen pada posisi yang ditanyakan sebelum melanjutkan.
                                </p>

                                <div
                                    x-cloak
                                    x-show="needsOperation('{{ $decisionKey }}')"
                                    x-transition
                                    class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4"
                                >
                                    <div class="space-y-2 text-sm leading-6 text-cyan-950">
                                        <p><strong>Tindakan:</strong> {{ $operation['action'] }}</p>
                                        <p><strong>{!! $operation['target_label'] !!}</strong></p>
                                    </div>

                                    <label class="mt-4 block text-sm font-black text-slate-800">
                                        Notasi Operasi
                                        {!! $operationInput($activity43State['answers'], $activity43State['feedback'], $operation['notation'], 'Notasi operasi ' . $operation['target_row'], 'subbab43') !!}
                                    </label>

                                    <div class="mt-5 overflow-x-auto rounded-2xl border border-cyan-200 bg-white p-4">
                                        <p class="text-sm font-black text-cyan-950">Rincian</p>

                                        <div class="mt-4 min-w-max space-y-4 text-center text-sm text-slate-900">
                                            <div class="flex items-center justify-center gap-2">
                                                <span>\({!! $operation['target_row'] !!} \leftarrow\)</span>
                                                {!! $textInput($activity43State['answers'], $activity43State['feedback'], $operation['coefficient'], 'Konstanta pengali ' . $operation['target_row']) !!}
                                                {!! $matrixRow($operation['source'], 'Baris acuan') !!}
                                                <span>\(+\)</span>
                                                {!! $matrixRow($operation['old'], 'Baris target awal') !!}
                                            </div>

                                            <div class="flex items-center justify-center gap-2">
                                                <span>\({!! $operation['target_row'] !!} \leftarrow\)</span>
                                                {!! $matrixRow($inputCells($operation['product']), 'Hasil perkalian', $activity43State['answers'], $activity43State['feedback']) !!}
                                                <span>\(+\)</span>
                                                {!! $matrixRow($operation['old'], 'Baris target awal') !!}
                                            </div>

                                            <div class="flex items-center justify-center gap-2">
                                                <span>\({!! $operation['target_row'] !!} \leftarrow\)</span>
                                                {!! $matrixRow($inputCells($operation['result']), 'Hasil operasi', $activity43State['answers'], $activity43State['feedback']) !!}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">
                        Fase 5
                    </p>

                    <h4 class="mt-1 text-xl font-black text-slate-950">
                        Evaluasi Kolom ke-2 – Bergerak ke Atas
                    </h4>

                    @php
                        $decisionKey = 'f5_q1_baris1';
                        $decision = $activityDecisions[$decisionKey];
                        $operation = $activityOperations['f5a'];
                    @endphp

                    <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-5">
                        <p class="font-black text-slate-950">
                            {{ $decision['label'] }}
                        </p>

                        <p class="mt-2 text-sm leading-6 text-slate-700">
                            {{ $decision['question'] }}
                        </p>

                        <div class="mt-4 grid grid-cols-2 gap-3 sm:max-w-sm">
                            <button
                                type="button"
                                @click="choose('{{ $decisionKey }}', 'ya')"
                                :class="choiceClass('{{ $decisionKey }}', 'ya')"
                                :disabled="isLocked('{{ $decisionKey }}')"
                                class="rounded-xl border px-4 py-3 text-sm font-black transition"
                            >
                                Ya
                            </button>

                            <button
                                type="button"
                                @click="choose('{{ $decisionKey }}', 'tidak')"
                                :class="choiceClass('{{ $decisionKey }}', 'tidak')"
                                :disabled="isLocked('{{ $decisionKey }}')"
                                class="rounded-xl border px-4 py-3 text-sm font-black transition"
                            >
                                Tidak
                            </button>
                        </div>

                        <p
                            x-cloak
                            x-show="hasWrongDecision('{{ $decisionKey }}')"
                            class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900"
                        >
                            Periksa kembali elemen pada posisi yang ditanyakan sebelum melanjutkan.
                        </p>

                        <div
                            x-cloak
                            x-show="needsOperation('{{ $decisionKey }}')"
                            x-transition
                            class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4"
                        >
                            <div class="space-y-2 text-sm leading-6 text-cyan-950">
                                <p><strong>Tindakan:</strong> {{ $operation['action'] }}</p>
                                <p><strong>{!! $operation['target_label'] !!}</strong></p>
                            </div>

                            <label class="mt-4 block text-sm font-black text-slate-800">
                                Notasi Operasi
                                {!! $operationInput($activity43State['answers'], $activity43State['feedback'], $operation['notation'], 'Notasi operasi ' . $operation['target_row'], 'subbab43') !!}
                            </label>

                            <div class="mt-5 overflow-x-auto rounded-2xl border border-cyan-200 bg-white p-4">
                                <p class="text-sm font-black text-cyan-950">Rincian</p>

                                <div class="mt-4 min-w-max space-y-4 text-center text-sm text-slate-900">
                                    <div class="flex items-center justify-center gap-2">
                                        <span>\({!! $operation['target_row'] !!} \leftarrow\)</span>
                                        {!! $textInput($activity43State['answers'], $activity43State['feedback'], $operation['coefficient'], 'Konstanta pengali ' . $operation['target_row']) !!}
                                        {!! $matrixRow($operation['source'], 'Baris acuan') !!}
                                        <span>\(+\)</span>
                                        {!! $matrixRow($operation['old'], 'Baris target awal') !!}
                                    </div>

                                    <div class="flex items-center justify-center gap-2">
                                        <span>\({!! $operation['target_row'] !!} \leftarrow\)</span>
                                        {!! $matrixRow($inputCells($operation['product']), 'Hasil perkalian', $activity43State['answers'], $activity43State['feedback']) !!}
                                        <span>\(+\)</span>
                                        {!! $matrixRow($operation['old'], 'Baris target awal') !!}
                                    </div>

                                    <div class="flex items-center justify-center gap-2">
                                        <span>\({!! $operation['target_row'] !!} \leftarrow\)</span>
                                        {!! $matrixRow($inputCells($operation['result']), 'Hasil operasi', $activity43State['answers'], $activity43State['feedback']) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">
                        Fase 6
                    </p>

                    <h4 class="mt-1 text-xl font-black text-slate-950">
                        Penyimpulan Solusi
                    </h4>

                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Susun matriks eselon baris tereduksi final, kemudian tuliskan
                        solusi setiap variabel secara langsung.
                    </p>

                    <div class="mt-5 overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <p class="text-sm font-black text-slate-900">
                            Matriks Eselon Baris Tereduksi Final
                        </p>

                        <div class="mt-4 flex min-w-max justify-center">
                            {!! $matrixBlock([
                                $inputCells(['f6_final_11', 'f6_final_12', 'f6_final_13', 'f6_final_14']),
                                $inputCells(['f6_final_21', 'f6_final_22', 'f6_final_23', 'f6_final_24']),
                                $inputCells(['f6_final_31', 'f6_final_32', 'f6_final_33', 'f6_final_34']),
                            ], 'Matriks eselon baris tereduksi final', $activity43State['answers'], $activity43State['feedback']) !!}
                        </div>
                    </div>

                    <div class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-5">
                        <p class="text-sm font-black text-cyan-950">
                            Tulis Langsung Solusi Variabelnya
                        </p>

                        <div class="mt-4 flex flex-wrap items-center gap-3 text-base font-bold text-slate-900">
                            <span>\(x =\)</span>
                            {!! $textInput($activity43State['answers'], $activity43State['feedback'], 'f6_solusi_x', 'Nilai x') !!}
                            <span>\(y =\)</span>
                            {!! $textInput($activity43State['answers'], $activity43State['feedback'], 'f6_solusi_y', 'Nilai y') !!}
                            <span>\(z =\)</span>
                            {!! $textInput($activity43State['answers'], $activity43State['feedback'], 'f6_solusi_z', 'Nilai z') !!}
                        </div>
                    </div>
                </section>
            </div>

            @if (! $activity43State['completed'])
                <div class="mt-8 flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs leading-5 text-slate-500">
                        Lengkapi setiap keputusan, notasi, rincian operasi, matriks akhir, dan solusi sebelum memeriksa jawaban.
                    </p>

                    <button
                        type="submit"
                        class="rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-700"
                    >
                        Cek Jawaban Aktivitas
                    </button>
                </div>
            @endif
        </div>
    </form>

    @foreach ([$cekModal, $activityModal] as $modal)
        @if ($modal)
            @php
                $modalStatus = $modal['status'] ?? 'revision';
                $modalIsSuccess = $modalStatus === 'success';
                $modalIsIncomplete = $modalStatus === 'incomplete';
                $modalIsAssisted = $modalStatus === 'assisted';

                $modalMessages = is_array($modal['feedback_messages'] ?? null)
                    ? collect($modal['feedback_messages'])
                        ->reject(fn ($message) => str_starts_with((string) $message, 'Bagian yang masih perlu diperbaiki:'))
                        ->values()
                        ->all()
                    : [];
            @endphp

            <div
                x-data="{ open: true }"
                x-cloak
                x-show="open"
                x-transition.opacity
                class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/70 px-4 py-6 backdrop-blur-sm"
                role="dialog"
                aria-modal="true"
            >
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="scale-95 opacity-0"
                    x-transition:enter-end="scale-100 opacity-100"
                    class="max-h-[calc(100vh-3rem)] w-full max-w-md overflow-y-auto rounded-[1.5rem] border border-white/10 bg-white shadow-2xl sm:max-w-lg"
                >
                    <div class="p-5 sm:p-6">
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-xl font-black {{ $modalIsSuccess ? 'bg-green-100 text-green-700' : ($modalIsAssisted ? 'bg-indigo-100 text-indigo-700' : ($modalIsIncomplete ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700')) }}">
                                {{ $modalIsSuccess ? '✓' : ($modalIsAssisted ? 'i' : ($modalIsIncomplete ? '!' : '×')) }}
                            </div>

                            <div class="min-w-0">
                                <p class="text-lg font-bold text-slate-900">
                                    {{ $modal['title'] ?? 'Hasil Pemeriksaan' }}
                                </p>

                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    {{ $modal['message'] ?? '' }}
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
                                @click="open = false"
                                class="w-full rounded-xl px-5 py-3 text-sm font-bold transition sm:w-auto {{ $modalIsSuccess ? 'bg-green-600 text-white hover:bg-green-700' : ($modalIsAssisted ? 'bg-indigo-600 text-white hover:bg-indigo-700' : ($modalIsIncomplete ? 'bg-yellow-400 text-slate-900 hover:bg-yellow-300' : 'bg-cyan-600 text-white hover:bg-cyan-700')) }}"
                            >
                                {{ $modal['button_label'] ?? 'Tutup' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</section>

<script>
    function aktivitasGaussJordan43(config) {
        return {
            decisions: config.decisions || {},
            expectedDecisions: config.expectedDecisions || {},
            fieldStates: config.fieldStates || {},
            completed: Boolean(config.completed),

            initialize() {
                this.bindMathFields();
            },

            bindMathFields() {
                this.$nextTick(() => {
                    document.querySelectorAll('[data-operation-math-field]').forEach((mathField) => {
                        const hiddenInput = document.getElementById(mathField.dataset.hiddenInput);

                        if (! hiddenInput || mathField.dataset.bound === 'true') {
                            return;
                        }

                        mathField.dataset.bound = 'true';

                        const syncValue = () => {
                            hiddenInput.value = mathField.value || '';
                        };

                        mathField.addEventListener('input', syncValue);
                        mathField.addEventListener('change', syncValue);
                        syncValue();
                    });
                });
            },

            isLocked(key) {
                const state = this.fieldStates[key] || null;

                return this.completed || state === 'correct' || state === 'revealed';
            },

            choose(key, value) {
                if (this.isLocked(key)) {
                    return;
                }

                this.decisions[key] = value;
            },

            hasWrongDecision(key) {
                return this.decisions[key]
                    && this.decisions[key] !== this.expectedDecisions[key];
            },

            needsOperation(key) {
                return this.decisions[key] === this.expectedDecisions[key]
                    && this.expectedDecisions[key] === 'tidak';
            },

            choiceClass(key, value) {
                const selected = this.decisions[key] === value;
                const state = this.fieldStates[key] || null;

                if (state === 'correct') {
                    return value === this.expectedDecisions[key]
                        ? 'border-green-500 bg-green-50 text-green-800'
                        : 'border-slate-200 bg-white text-slate-400';
                }

                if (state === 'revealed') {
                    return value === this.expectedDecisions[key]
                        ? 'border-indigo-400 bg-indigo-50 text-indigo-800'
                        : 'border-slate-200 bg-white text-slate-400';
                }

                if (state === 'wrong' && selected) {
                    return 'border-red-500 bg-red-50 text-red-800';
                }

                if (selected) {
                    return 'border-cyan-500 bg-cyan-50 text-cyan-800';
                }

                return 'border-slate-300 bg-white text-slate-700 hover:border-cyan-300';
            },
        };
    }

    (function () {
        const scrollKey = 'ruangobe-practice-scroll:subbab-4-3-gauss-jordan';

        document.querySelectorAll('#cek-pemahaman-4-3-form, #aktivitas-4-2-form').forEach((form) => {
            form.addEventListener('submit', function () {
                sessionStorage.setItem(scrollKey, String(window.scrollY));
            });
        });

        @if ($cekModal || $activityModal)
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
