<!-- SUBBAB_3_2_SIMULASI_ESELON_BARIS_KEPUTUSAN_V3_NATIVEPHP_FIX -->
<?php
    $simulation32Key = 'contoh-simulasi-3-2-eselon-baris';
    $simulation32DefinitionVersion = 'subbab-3-2-keputusan-v3';
    $simulation32Submission = $practiceSubmissions->get($simulation32Key);

    $simulation32StoredAnswers = is_array($simulation32Submission?->answers)
        ? $simulation32Submission->answers
        : [];

    $simulation32OldAnswers = old('answers', []);
    $simulation32OldAnswers = is_array($simulation32OldAnswers)
        ? $simulation32OldAnswers
        : [];

    $simulation32Answers = array_replace($simulation32StoredAnswers, $simulation32OldAnswers);

    $simulation32FeedbackRaw = is_array($simulation32Submission?->feedback)
        ? $simulation32Submission->feedback
        : [];

    $simulation32Feedback = is_array($simulation32FeedbackRaw['fields'] ?? null)
        ? $simulation32FeedbackRaw['fields']
        : collect($simulation32FeedbackRaw)
            ->except(['_meta', 'groups', 'fields'])
            ->filter(fn ($item) => is_array($item))
            ->all();

    $simulation32Meta = is_array($simulation32FeedbackRaw['_meta'] ?? null)
        ? $simulation32FeedbackRaw['_meta']
        : [];

    $simulation32HasCurrentDecisionStructure = isset($simulation32Feedback['fase1_q1_pivot'])
        || array_key_exists('fase1_q1_pivot', $simulation32StoredAnswers)
        || array_key_exists('fase4_pivot_hasil_45', $simulation32StoredAnswers);

    $simulation32UsesComponentAttemptScope = ($simulation32Meta['attempt_scope'] ?? null) === 'component'
        && (
            ($simulation32Meta['definition_version'] ?? null) === $simulation32DefinitionVersion
            || (
                empty($simulation32Meta['definition_version'] ?? null)
                && $simulation32HasCurrentDecisionStructure
            )
        );

    if (! $simulation32UsesComponentAttemptScope && $simulation32Submission) {
        $simulation32Answers = $simulation32OldAnswers;
        $simulation32Feedback = [];
        $simulation32Meta = [];
    }

    $simulation32Completed = $simulation32UsesComponentAttemptScope
        && (bool) ($simulation32Submission?->is_completed ?? false);

    $simulation32Assisted = ($simulation32Meta['completion_mode'] ?? null) === 'bantuan';

    $simulation32MaxAttempts = max(1, (int) ($simulation32Meta['max_attempts'] ?? 3));
    $simulation32Attempts = max(0, min(
        $simulation32MaxAttempts,
        (int) ($simulation32Meta['attempts'] ?? 0)
    ));
    $simulation32RemainingAttempts = max(0, $simulation32MaxAttempts - $simulation32Attempts);

    $simulation32InputClass = function (string $fieldKey) use ($simulation32Feedback): string {
        return match ($simulation32Feedback[$fieldKey]['state'] ?? null) {
            'correct' => 'border-green-500 bg-green-50 text-green-950 focus:border-green-500 focus:ring-green-500',
            'revealed' => 'border-indigo-400 bg-indigo-50 text-indigo-950 focus:border-indigo-500 focus:ring-indigo-500',
            'wrong' => 'border-red-500 bg-red-50 text-red-950 focus:border-red-500 focus:ring-red-500',
            'empty' => 'border-yellow-400 bg-yellow-50 text-yellow-950 focus:border-yellow-500 focus:ring-yellow-500',
            default => 'border-slate-300 bg-white text-slate-900 focus:border-cyan-500 focus:ring-cyan-500',
        };
    };

    $simulation32InputLocked = function (string $fieldKey) use ($simulation32Feedback): bool {
        $field = $simulation32Feedback[$fieldKey] ?? [];

        return ! empty($field['is_revealed'])
            || (! empty($field['is_correct']) && ($field['state'] ?? null) === 'correct');
    };

    $fieldInput = function (
        string $fieldKey,
        string $label,
        string $sizeClass = 'h-10 w-14'
    ) use ($simulation32Answers, $simulation32InputClass, $simulation32InputLocked) {
        $value = (string) ($simulation32Answers[$fieldKey] ?? '');
        $disabled = $simulation32InputLocked($fieldKey) ? ' disabled' : '';

        return new \Illuminate\Support\HtmlString(
            '<input type="text"'
            . ' name="answers[' . e($fieldKey) . ']"'
            . ' value="' . e($value) . '"'
            . ' aria-label="' . e($label) . '"'
            . $disabled
            . ' class="' . e($sizeClass . ' rounded-xl border px-2 text-center font-bold shadow-sm transition focus:outline-none focus:ring-2 ' . $simulation32InputClass($fieldKey)) . '">'
        );
    };

    /* SUBBAB_3_2_BANTUAN_NOTASI_PLAIN_V2
     *
     * Jawaban bantuan sengaja memakai penulisan B2/B3/B4 biasa agar sama
     * dengan bentuk yang diketik mahasiswa pada MathLive. Nilai ini hanya
     * dipakai untuk tampilan ketika sistem menampilkan bantuan.
     */
        /* SUBBAB_3_2_BANTUAN_NOTASI_MATHLIVE_V4
     *
     * Nilai berikut adalah LaTeX tampilan untuk MathLive. Penulisan B2/B3/B4
     * tetap memakai angka biasa (bukan subskrip), sedangkan panah dan pecahan
     * dirender sama seperti notasi yang diketik mahasiswa pada MathLive.
     */
    $simulation32PreferredRevealedNotation = [
        'fase1_target1a_notasi' => 'B2 \leftarrow B1 + B2',
        'fase1_target1b_notasi' => 'B3 \leftarrow -2B1 + B3',
        'fase1_target1c_notasi' => 'B4 \leftarrow -B1 + B4',
        'fase2_pivot_notasi' => 'B2 \leftarrow \frac{1}{3} B2',
        'fase2_target2a_notasi' => 'B3 \leftarrow B2 + B3',
        'fase3_pivot_notasi' => 'B3 \leftarrow -B3',
        'fase3_target3a_notasi' => 'B4 \leftarrow B3 + B4',
        'fase4_pivot_notasi' => 'B4 \leftarrow -\frac{1}{3} B4',
    ];

    /*
    |--------------------------------------------------------------------------
    | SUBBAB_3_2_BANTUAN_NOTASI_MATHLIVE_RENDER_V5
    |--------------------------------------------------------------------------
    | Jawaban bantuan tetap memakai <math-field>. Nilai LaTeX tidak ditulis
    | sebagai isi HTML karena akan terlihat mentah, misalnya "\leftarrow".
    | Nilai diletakkan pada data-operation-latex dan dimasukkan setelah
    | MathLive siap melalui JavaScript.
    */
    $operationInput = function (string $fieldKey, string $label) use (
        $simulation32Answers,
        $simulation32Feedback,
        $simulation32PreferredRevealedNotation,
        $simulation32InputClass,
        $simulation32InputLocked
    ) {
        $value = (string) ($simulation32Answers[$fieldKey] ?? '');
        $isRevealed = ($simulation32Feedback[$fieldKey]['state'] ?? null) === 'revealed';

        if ($isRevealed && isset($simulation32PreferredRevealedNotation[$fieldKey])) {
            $value = $simulation32PreferredRevealedNotation[$fieldKey];
        }

        $hiddenId = 'subbab32-' . $fieldKey . '-hidden';
        $readOnly = $simulation32InputLocked($fieldKey) ? ' read-only' : '';

        return new \Illuminate\Support\HtmlString(
            '<div class="mt-2 rounded-2xl border p-2 shadow-sm transition '
            . e($simulation32InputClass($fieldKey))
            . '">'
            . '<math-field'
            . ' data-operation-math-field'
            . ' data-hidden-input="' . e($hiddenId) . '"'
            . ' data-operation-latex="' . e($value) . '"'
            . ' aria-label="' . e($label) . '"'
            . ' virtual-keyboard-mode="manual"'
            . $readOnly
            . ' class="block min-h-12 w-full overflow-x-auto rounded-xl border-0 bg-transparent px-3 py-2 text-left text-lg font-bold outline-none">'
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

    $simulation32StatusClass = function () use ($simulation32Completed, $simulation32Assisted): string {
        if (! $simulation32Completed) {
            return 'bg-yellow-50 text-yellow-700';
        }

        return $simulation32Assisted
            ? 'bg-indigo-100 text-indigo-700'
            : 'bg-green-50 text-green-700';
    };

    $simulation32StatusLabel = function () use ($simulation32Completed, $simulation32Assisted): string {
        if (! $simulation32Completed) {
            return 'Perlu Dikerjakan';
        }

        return $simulation32Assisted
            ? 'Selesai dengan Bantuan'
            : 'Simulasi Selesai';
    };

    $simulation32FeedbackSummary = function () use (
        $simulation32Feedback,
        $simulation32Completed,
        $simulation32Assisted
    ): ?string {
        if (empty($simulation32Feedback)) {
            return null;
        }

        if ($simulation32Completed) {
            return $simulation32Assisted
                ? 'Simulasi selesai dengan bantuan. Pelajari kembali jawaban yang ditampilkan pada kolom terkait.'
                : 'Seluruh jawaban keputusan, notasi operasi, dan rincian perhitungan sudah tepat.';
        }

        return 'Periksa kembali kolom berwarna merah. Kolom kuning perlu dilengkapi dan tidak mengurangi kesempatan.';
    };

    $phase1Operations = [
        [
            'question' => 'Pertanyaan 2 (Evaluasi Baris-2)',
            'result' => 'TIDAK, nilainya \\(-1\\).',
            'action' => 'Lakukan operasi untuk mengenolkan Baris-2 dengan acuan Baris-1.',
            'target' => 'Target 1A: Mengenolkan angka \\(-1\\) pada Baris-2 Kolom-1.',
            'prompt' => 'Berapa angka pengali \\(k\\) yang dibutuhkan? Tuliskan notasi operasi lengkapnya.',
            'notation' => 'fase1_target1a_notasi',
            'target_row' => 'B_2',
            'coefficient' => 'fase1_target1a_k',
            'source' => ['1', '1', '2', '-1', '5'],
            'old' => ['-1', '2', '1', '1', '10'],
            'product' => [
                'fase1_target1a_produk_21',
                'fase1_target1a_produk_22',
                'fase1_target1a_produk_23',
                'fase1_target1a_produk_24',
                'fase1_target1a_produk_25',
            ],
            'result_fields' => [
                'fase1_target1a_hasil_21',
                'fase1_target1a_hasil_22',
                'fase1_target1a_hasil_23',
                'fase1_target1a_hasil_24',
                'fase1_target1a_hasil_25',
            ],
        ],
        [
            'question' => 'Pertanyaan 3 (Evaluasi Baris-3)',
            'result' => 'TIDAK, nilainya \\(2\\).',
            'action' => 'Nolkan Baris-3 dengan acuan Baris-1.',
            'target' => 'Target 1B: Mengenolkan angka \\(2\\) pada Baris-3 Kolom-1.',
            'prompt' => 'Tentukan angka pengali \\(k\\) dan tulis notasinya.',
            'notation' => 'fase1_target1b_notasi',
            'target_row' => 'B_3',
            'coefficient' => 'fase1_target1b_k',
            'source' => ['1', '1', '2', '-1', '5'],
            'old' => ['2', '1', '2', '3', '12'],
            'product' => [
                'fase1_target1b_produk_31',
                'fase1_target1b_produk_32',
                'fase1_target1b_produk_33',
                'fase1_target1b_produk_34',
                'fase1_target1b_produk_35',
            ],
            'result_fields' => [
                'fase1_target1b_hasil_31',
                'fase1_target1b_hasil_32',
                'fase1_target1b_hasil_33',
                'fase1_target1b_hasil_34',
                'fase1_target1b_hasil_35',
            ],
        ],
        [
            'question' => 'Pertanyaan 4 (Evaluasi Baris-4)',
            'result' => 'TIDAK, nilainya \\(1\\).',
            'action' => 'Nolkan Baris-4 dengan acuan Baris-1.',
            'target' => 'Target 1C: Mengenolkan angka \\(1\\) pada Baris-4 Kolom-1.',
            'prompt' => 'Tentukan angka pengali \\(k\\) dan tulis notasinya.',
            'notation' => 'fase1_target1c_notasi',
            'target_row' => 'B_4',
            'coefficient' => 'fase1_target1c_k',
            'source' => ['1', '1', '2', '-1', '5'],
            'old' => ['1', '1', '1', '1', '7'],
            'product' => [
                'fase1_target1c_produk_41',
                'fase1_target1c_produk_42',
                'fase1_target1c_produk_43',
                'fase1_target1c_produk_44',
                'fase1_target1c_produk_45',
            ],
            'result_fields' => [
                'fase1_target1c_hasil_41',
                'fase1_target1c_hasil_42',
                'fase1_target1c_hasil_43',
                'fase1_target1c_hasil_44',
                'fase1_target1c_hasil_45',
            ],
        ],
    ];

    $phase2Operation = [
        'question' => 'Pertanyaan 2 (Evaluasi Baris-3)',
        'result' => 'TIDAK, nilainya \\(-1\\).',
        'action' => 'Nolkan Baris-3 menggunakan Baris-2 sebagai acuan.',
        'target' => 'Target 2A: Mengenolkan angka \\(-1\\) pada Baris-3 Kolom-2.',
        'prompt' => 'Tentukan angka pengali \\(k\\) dan tulis notasinya.',
        'notation' => 'fase2_target2a_notasi',
        'target_row' => 'B_3',
        'coefficient' => 'fase2_target2a_k',
        'source' => ['0', '1', '1', '0', '5'],
        'old' => ['0', '-1', '-2', '5', '2'],
        'product' => [
            'fase2_target2a_produk_31',
            'fase2_target2a_produk_32',
            'fase2_target2a_produk_33',
            'fase2_target2a_produk_34',
            'fase2_target2a_produk_35',
        ],
        'result_fields' => [
            'fase2_target2a_hasil_31',
            'fase2_target2a_hasil_32',
            'fase2_target2a_hasil_33',
            'fase2_target2a_hasil_34',
            'fase2_target2a_hasil_35',
        ],
    ];

    $phase3Operation = [
        'question' => 'Pertanyaan 2 (Evaluasi Baris-4)',
        'result' => 'TIDAK, masih memuat angka \\(-1\\).',
        'action' => 'Nolkan Baris-4 dengan bantuan Baris-3.',
        'target' => 'Target 3A: Mengenolkan angka \\(-1\\) pada Baris-4 Kolom-3.',
        'prompt' => 'Tentukan angka pengali \\(k\\) dan tulis notasinya.',
        'notation' => 'fase3_target3a_notasi',
        'target_row' => 'B_4',
        'coefficient' => 'fase3_target3a_k',
        'source' => ['0', '0', '1', '-5', '-7'],
        'old' => ['0', '0', '-1', '2', '2'],
        'product' => [
            'fase3_target3a_produk_41',
            'fase3_target3a_produk_42',
            'fase3_target3a_produk_43',
            'fase3_target3a_produk_44',
            'fase3_target3a_produk_45',
        ],
        'result_fields' => [
            'fase3_target3a_hasil_41',
            'fase3_target3a_hasil_42',
            'fase3_target3a_hasil_43',
            'fase3_target3a_hasil_44',
            'fase3_target3a_hasil_45',
        ],
    ];


    /*
    |--------------------------------------------------------------------------
    | Pertanyaan keputusan Ya/Tidak
    |--------------------------------------------------------------------------
    | Setiap hasil pada modul dijadikan pertanyaan yang dijawab mahasiswa.
    | Jika kondisi sudah sesuai, pertanyaan berikutnya dibuka. Jika belum,
    | tindakan Operasi Baris Elementer ditampilkan terlebih dahulu.
    */
    $phase1Operations[0]['decision_key'] = 'fase1_q1_baris2';
    $phase1Operations[1]['decision_key'] = 'fase1_q1_baris3';
    $phase1Operations[2]['decision_key'] = 'fase1_q1_baris4';
    $phase2Operation['decision_key'] = 'fase2_q2_baris3';
    $phase3Operation['decision_key'] = 'fase3_q2_baris4';

    $simulation32Decisions = [
        'fase1_q1_pivot' => [
            'step' => 0,
            'label' => 'Pertanyaan 1',
            'question' => 'Cek elemen utama pada Baris-1 Kolom-1. Apakah nilainya sudah 1?',
            'correct' => 'ya',
            'description' => 'Elemen utama pada Baris-1 Kolom-1 bernilai 1.',
        ],
        'fase1_q1_baris2' => [
            'step' => 1,
            'label' => 'Pertanyaan 2 (Evaluasi Baris-2)',
            'question' => 'Cek elemen tepat di bawah 1 utama pada Baris-2 Kolom-1. Apakah nilainya sudah 0?',
            'correct' => 'tidak',
            'description' => 'Elemen pada Baris-2 Kolom-1 bernilai -1.',
        ],
        'fase1_q1_baris3' => [
            'step' => 2,
            'label' => 'Pertanyaan 3 (Evaluasi Baris-3)',
            'question' => 'Selanjutnya, cek elemen pada Baris-3 Kolom-1. Apakah nilainya sudah 0?',
            'correct' => 'tidak',
            'description' => 'Elemen pada Baris-3 Kolom-1 bernilai 2.',
        ],
        'fase1_q1_baris4' => [
            'step' => 3,
            'label' => 'Pertanyaan 4 (Evaluasi Baris-4)',
            'question' => 'Terakhir untuk Kolom-1, cek elemen pada Baris-4 Kolom-1. Apakah nilainya sudah 0?',
            'correct' => 'tidak',
            'description' => 'Elemen pada Baris-4 Kolom-1 bernilai 1.',
        ],
        'fase2_q1_pivot' => [
            'step' => 4,
            'label' => 'Pertanyaan 1',
            'question' => 'Cek elemen utama pada Baris-2 Kolom-2. Apakah nilainya sudah 1?',
            'correct' => 'tidak',
            'description' => 'Elemen utama pada Baris-2 Kolom-2 bernilai 3.',
        ],
        'fase2_q2_baris3' => [
            'step' => 5,
            'label' => 'Pertanyaan 2 (Evaluasi Baris-3)',
            'question' => 'Cek elemen di bawah 1 utama pada Baris-3 Kolom-2. Apakah nilainya sudah 0?',
            'correct' => 'tidak',
            'description' => 'Elemen pada Baris-3 Kolom-2 bernilai -1.',
        ],
        'fase2_q3_baris4' => [
            'step' => 6,
            'label' => 'Pertanyaan 3 (Evaluasi Baris-4)',
            'question' => 'Cek elemen pada Baris-4 Kolom-2. Apakah nilainya sudah 0?',
            'correct' => 'ya',
            'description' => 'Elemen pada Baris-4 Kolom-2 sudah bernilai 0.',
        ],
        'fase3_q1_pivot' => [
            'step' => 7,
            'label' => 'Pertanyaan 1',
            'question' => 'Abaikan Baris-1 dan Baris-2. Apakah elemen utama pada Baris-3 Kolom-3 sudah bernilai 1?',
            'correct' => 'tidak',
            'description' => 'Elemen utama pada Baris-3 Kolom-3 bernilai -1.',
        ],
        'fase3_q2_baris4' => [
            'step' => 8,
            'label' => 'Pertanyaan 2 (Evaluasi Baris-4)',
            'question' => 'Cek elemen di bawah 1 utama pada Baris-4 Kolom-3. Apakah nilainya sudah 0?',
            'correct' => 'tidak',
            'description' => 'Elemen pada Baris-4 Kolom-3 masih bernilai -1.',
        ],
        'fase4_q1_pivot' => [
            'step' => 9,
            'label' => 'Pertanyaan 1',
            'question' => 'Apakah elemen utama pada Baris-4 Kolom-4 sudah bernilai 1?',
            'correct' => 'tidak',
            'description' => 'Elemen utama pada Baris-4 Kolom-4 bernilai -3.',
        ],
    ];

    /* SUBBAB_3_2_DECISION_KEYS_FIXED */
    foreach ($simulation32Decisions as $simulation32DecisionKey => &$simulation32Decision) {
        $simulation32Decision['key'] = $simulation32DecisionKey;
    }
    unset($simulation32Decision);

    $simulation32DecisionAnswers = collect($simulation32Decisions)
        ->mapWithKeys(fn (array $decision, string $key) => [
            $key => strtolower(trim((string) ($simulation32Answers[$key] ?? ''))),
        ])
        ->all();

    $simulation32DecisionCorrectAnswers = collect($simulation32Decisions)
        ->mapWithKeys(fn (array $decision, string $key) => [
            $key => $decision['correct'],
        ])
        ->all();

    $simulation32DecisionDescriptions = collect($simulation32Decisions)
        ->mapWithKeys(fn (array $decision, string $key) => [
            $key => $decision['description'],
        ])
        ->all();

    $simulation32DecisionOrder = array_keys($simulation32Decisions);

    $practiceModalPayload = session('practice_modal');
    $practiceModal = is_array($practiceModalPayload)
        && ($practiceModalPayload['practice_key'] ?? null) === $simulation32Key
        ? $practiceModalPayload
        : null;
?>

<section class="space-y-8">
    <div class="space-y-4">
        {{-- <h2 class="text-2xl font-black text-slate-950">
            3.2 Simulasi Mengubah Matriks Menjadi Eselon Baris
        </h2> --}}

        <p>
            Mari kita simulasikan algoritma ini pada Sistem Persamaan Linear empat variabel
            \(\left(x_1, x_2, x_3, x_4\right)\) yang direpresentasikan ke dalam
            <span class="italic">augmented matrix</span> berikut.
        </p>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm">
        <p class="mb-4 text-sm font-black uppercase tracking-[0.18em] text-cyan-700">
            Matriks Diperbesar Awal
        </p>

        <div class="inline-block rounded-2xl border border-slate-200 bg-slate-50 px-6 py-4 text-lg text-slate-950">
            \[
                \left[
                \begin{array}{rrrr|r}
                    1 & 1 & 2 & -1 & 5 \\
                    -1 & 2 & 1 & 1 & 10 \\
                    2 & 1 & 2 & 3 & 12 \\
                    1 & 1 & 1 & 1 & 7
                \end{array}
                \right]
            \]
        </div>
    </div>


    <form
        method="POST"
        action="<?= e(route('mahasiswa.practice.submit', [$lesson->slug, $simulation32Key])) ?>"
        data-practice-scroll-form
        data-simulation32-decisions='<?= e(json_encode($simulation32DecisionAnswers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>'
        data-simulation32-decision-order='<?= e(json_encode($simulation32DecisionOrder, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>'
        data-simulation32-correct-answers='<?= e(json_encode($simulation32DecisionCorrectAnswers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>'
        data-simulation32-descriptions='<?= e(json_encode($simulation32DecisionDescriptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>'
        x-data="simulation32Component($el)"
        class="space-y-8">
        <?= csrf_field() ?>

        <!-- Fase 1 -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">Fase 1</p>
                    <h3 class="mt-1 text-xl font-black text-slate-950">Evaluasi Baris ke-1</h3>
                </div>

                <span class="w-fit rounded-full px-4 py-2 text-sm font-bold <?= e($simulation32StatusClass()) ?>">
                    <?= e($simulation32StatusLabel()) ?>
                </span>
            </div>

            <?php $decision = $simulation32Decisions['fase1_q1_pivot']; ?>
            <div x-show="isVisible(<?= e($decision['step']) ?>)" x-cloak x-transition class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <p class="font-black text-slate-950"><?= e($decision['label']) ?></p>
                <p class="mt-2 text-sm leading-6 text-slate-700"><?= e($decision['question']) ?></p>

                <input type="hidden" name="answers[<?= e($decision['key']) ?>]" x-model="decisions['<?= e($decision['key']) ?>']">

                <div class="mt-4 grid grid-cols-2 gap-3 sm:max-w-sm">
                    <button
                        type="button"
                        @click="choose('<?= e($decision['key']) ?>', 'ya', <?= e($decision['step']) ?>)"
                        :class="choiceClass('<?= e($decision['key']) ?>', 'ya')"
                        <?= e($simulation32InputLocked($decision['key']) ? 'disabled' : '') ?>
                        class="rounded-xl border px-4 py-3 text-sm font-black transition">
                        Ya
                    </button>
                    <button
                        type="button"
                        @click="choose('<?= e($decision['key']) ?>', 'tidak', <?= e($decision['step']) ?>)"
                        :class="choiceClass('<?= e($decision['key']) ?>', 'tidak')"
                        <?= e($simulation32InputLocked($decision['key']) ? 'disabled' : '') ?>
                        class="rounded-xl border px-4 py-3 text-sm font-black transition">
                        Tidak
                    </button>
                </div>

                <div x-show="hasAnswer('<?= e($decision['key']) ?>')" x-cloak :class="feedbackClass('<?= e($decision['key']) ?>')" class="mt-4 rounded-2xl border p-4 text-sm leading-6">
                    <p x-text="feedbackMessage('<?= e($decision['key']) ?>')"></p>
                </div>
            </div>

            <div class="mt-5 space-y-5">
                <?php foreach ($phase1Operations as $operation): ?>
                    <?php $decision = $simulation32Decisions[$operation['decision_key']]; ?>

                    <div x-show="isVisible(<?= e($decision['step']) ?>)" x-cloak x-transition class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <p class="font-black text-slate-950"><?= e($decision['label']) ?></p>
                        <p class="mt-2 text-sm leading-6 text-slate-700"><?= e($decision['question']) ?></p>

                        <input type="hidden" name="answers[<?= e($decision['key']) ?>]" x-model="decisions['<?= e($decision['key']) ?>']">

                        <div class="mt-4 grid grid-cols-2 gap-3 sm:max-w-sm">
                            <button
                                type="button"
                                @click="choose('<?= e($decision['key']) ?>', 'ya', <?= e($decision['step']) ?>)"
                                :class="choiceClass('<?= e($decision['key']) ?>', 'ya')"
                                <?= e($simulation32InputLocked($decision['key']) ? 'disabled' : '') ?>
                                class="rounded-xl border px-4 py-3 text-sm font-black transition">
                                Ya
                            </button>
                            <button
                                type="button"
                                @click="choose('<?= e($decision['key']) ?>', 'tidak', <?= e($decision['step']) ?>)"
                                :class="choiceClass('<?= e($decision['key']) ?>', 'tidak')"
                                <?= e($simulation32InputLocked($decision['key']) ? 'disabled' : '') ?>
                                class="rounded-xl border px-4 py-3 text-sm font-black transition">
                                Tidak
                            </button>
                        </div>

                        <div x-show="hasAnswer('<?= e($decision['key']) ?>')" x-cloak :class="feedbackClass('<?= e($decision['key']) ?>')" class="mt-4 rounded-2xl border p-4 text-sm leading-6">
                            <p x-text="feedbackMessage('<?= e($decision['key']) ?>')"></p>
                        </div>

                        <div
                            x-show="needsOperation('<?= e($decision['key']) ?>')"
                            x-cloak
                            x-transition
                            data-operation-block="<?= e($decision['key']) ?>"
                            class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4">
                            <div class="space-y-2 text-sm leading-6 text-cyan-950">
                                <p><strong>Tindakan:</strong> <?= e($operation['action']) ?></p>
                                <p><strong><?= $operation['target'] ?></strong></p>
                                <p><?= $operation['prompt'] ?></p>
                            </div>

                            <label class="mt-4 block text-sm font-black text-slate-800">
                                Notasi Operasi
                                <?= $operationInput($operation['notation'], 'Notasi operasi ' . strip_tags($operation['target'])) ?>
                            </label>

                            <div class="mt-5 overflow-x-auto rounded-2xl border border-cyan-200 bg-white p-4">
                                <p class="text-sm font-black text-cyan-950">Rincian</p>

                                <div class="mt-4 min-w-max space-y-4 text-center text-sm text-slate-900">
                                    <div class="flex items-center justify-center gap-2">
                                        <span>\(<?= e($operation['target_row']) ?> \leftarrow\)</span>
                                        <?= $fieldInput($operation['coefficient'], 'Konstanta pengali ' . $operation['target']) ?>
                                        <?= $matrixRow($operation['source'], 'Baris acuan ' . $operation['target']) ?>
                                        <span>\(+\)</span>
                                        <?= $matrixRow($operation['old'], 'Baris target awal ' . $operation['target']) ?>
                                    </div>

                                    <div class="flex items-center justify-center gap-2">
                                        <span>\(<?= e($operation['target_row']) ?> \leftarrow\)</span>
                                        <?= $matrixRow($inputCells($operation['product']), 'Hasil perkalian ' . $operation['target']) ?>
                                        <span>\(+\)</span>
                                        <?= $matrixRow($operation['old'], 'Baris target awal ' . $operation['target']) ?>
                                    </div>

                                    <div class="flex items-center justify-center gap-2">
                                        <span>\(<?= e($operation['target_row']) ?> \leftarrow\)</span>
                                        <?= $matrixRow($inputCells($operation['result_fields']), 'Hasil akhir ' . $operation['target']) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p x-show="actionNotice['<?= e($decision['key']) ?>']" x-cloak x-text="actionNotice['<?= e($decision['key']) ?>']" class="text-sm font-semibold text-red-700"></p>
                                <button
                                    type="button"
                                    @click="continueAfterOperation('<?= e($decision['key']) ?>', <?= e($decision['step']) ?>)"
                                    class="w-full rounded-xl bg-cyan-600 px-5 py-3 text-sm font-black text-white transition hover:bg-cyan-700 sm:ml-auto sm:w-auto">
                                    Lanjut ke Pertanyaan Berikutnya
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div x-show="isVisible(4)" x-cloak x-transition class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-5 text-center">
                <p class="mb-4 text-sm font-black text-slate-700">Cek Poin Matriks Saat Ini</p>
                <div class="inline-block rounded-2xl border border-slate-200 bg-slate-50 px-6 py-4 text-lg text-slate-950">
                    \[
                        \left[
                        \begin{array}{rrrr|r}
                            1 & 1 & 2 & -1 & 5 \\
                            0 & 3 & 3 & 0 & 15 \\
                            0 & -1 & -2 & 5 & 2 \\
                            0 & 0 & -1 & 2 & 2
                        \end{array}
                        \right]
                    \]
                </div>
            </div>
        </div>

        <!-- Fase 2 -->
        <div x-show="isVisible(4)" x-cloak x-transition class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">Fase 2</p>
                <h3 class="mt-1 text-xl font-black text-slate-950">Evaluasi Baris ke-2</h3>
            </div>

            <?php $decision = $simulation32Decisions['fase2_q1_pivot']; ?>
            <div x-show="isVisible(<?= e($decision['step']) ?>)" x-cloak x-transition class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <p class="font-black text-slate-950"><?= e($decision['label']) ?></p>
                <p class="mt-2 text-sm leading-6 text-slate-700"><?= e($decision['question']) ?></p>
                <input type="hidden" name="answers[<?= e($decision['key']) ?>]" x-model="decisions['<?= e($decision['key']) ?>']">

                <div class="mt-4 grid grid-cols-2 gap-3 sm:max-w-sm">
                    <button type="button" @click="choose('<?= e($decision['key']) ?>', 'ya', <?= e($decision['step']) ?>)" :class="choiceClass('<?= e($decision['key']) ?>', 'ya')" <?= e($simulation32InputLocked($decision['key']) ? 'disabled' : '') ?> class="rounded-xl border px-4 py-3 text-sm font-black transition">Ya</button>
                    <button type="button" @click="choose('<?= e($decision['key']) ?>', 'tidak', <?= e($decision['step']) ?>)" :class="choiceClass('<?= e($decision['key']) ?>', 'tidak')" <?= e($simulation32InputLocked($decision['key']) ? 'disabled' : '') ?> class="rounded-xl border px-4 py-3 text-sm font-black transition">Tidak</button>
                </div>

                <div x-show="hasAnswer('<?= e($decision['key']) ?>')" x-cloak :class="feedbackClass('<?= e($decision['key']) ?>')" class="mt-4 rounded-2xl border p-4 text-sm leading-6">
                    <p x-text="feedbackMessage('<?= e($decision['key']) ?>')"></p>
                </div>

                <div x-show="needsOperation('<?= e($decision['key']) ?>')" x-cloak x-transition data-operation-block="<?= e($decision['key']) ?>" class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4">
                    <div class="space-y-2 text-sm leading-6 text-cyan-950">
                        <p><strong>Tindakan:</strong> Berapa konstanta pecahan yang harus dikalikan agar menjadi 1? Tuliskan notasinya.</p>
                    </div>

                    <label class="mt-4 block text-sm font-black text-slate-800">
                        Notasi Operasi
                        <?= $operationInput('fase2_pivot_notasi', 'Notasi operasi Fase 2 Baris 2') ?>
                    </label>

                    <div class="mt-5 overflow-x-auto rounded-2xl border border-cyan-200 bg-white p-4">
                        <p class="text-sm font-black text-cyan-950">Rincian</p>
                        <div class="mt-4 flex min-w-max items-center justify-center gap-2 text-center text-sm text-slate-900">
                            <span>\(B_2 \leftarrow\)</span>
                            <?= $matrixRow($inputCells([
                                'fase2_pivot_hasil_21',
                                'fase2_pivot_hasil_22',
                                'fase2_pivot_hasil_23',
                                'fase2_pivot_hasil_24',
                                'fase2_pivot_hasil_25',
                            ]), 'Hasil perubahan Baris-2 pada Fase 2') ?>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p x-show="actionNotice['<?= e($decision['key']) ?>']" x-cloak x-text="actionNotice['<?= e($decision['key']) ?>']" class="text-sm font-semibold text-red-700"></p>
                        <button type="button" @click="continueAfterOperation('<?= e($decision['key']) ?>', <?= e($decision['step']) ?>)" class="w-full rounded-xl bg-cyan-600 px-5 py-3 text-sm font-black text-white transition hover:bg-cyan-700 sm:ml-auto sm:w-auto">
                            Lanjut ke Pertanyaan Berikutnya
                        </button>
                    </div>
                </div>
            </div>

            <?php $decision = $simulation32Decisions['fase2_q2_baris3']; ?>
            <div x-show="isVisible(<?= e($decision['step']) ?>)" x-cloak x-transition class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <p class="font-black text-slate-950"><?= e($decision['label']) ?></p>
                <p class="mt-2 text-sm leading-6 text-slate-700"><?= e($decision['question']) ?></p>
                <input type="hidden" name="answers[<?= e($decision['key']) ?>]" x-model="decisions['<?= e($decision['key']) ?>']">

                <div class="mt-4 grid grid-cols-2 gap-3 sm:max-w-sm">
                    <button type="button" @click="choose('<?= e($decision['key']) ?>', 'ya', <?= e($decision['step']) ?>)" :class="choiceClass('<?= e($decision['key']) ?>', 'ya')" <?= e($simulation32InputLocked($decision['key']) ? 'disabled' : '') ?> class="rounded-xl border px-4 py-3 text-sm font-black transition">Ya</button>
                    <button type="button" @click="choose('<?= e($decision['key']) ?>', 'tidak', <?= e($decision['step']) ?>)" :class="choiceClass('<?= e($decision['key']) ?>', 'tidak')" <?= e($simulation32InputLocked($decision['key']) ? 'disabled' : '') ?> class="rounded-xl border px-4 py-3 text-sm font-black transition">Tidak</button>
                </div>

                <div x-show="hasAnswer('<?= e($decision['key']) ?>')" x-cloak :class="feedbackClass('<?= e($decision['key']) ?>')" class="mt-4 rounded-2xl border p-4 text-sm leading-6">
                    <p x-text="feedbackMessage('<?= e($decision['key']) ?>')"></p>
                </div>

                <div x-show="needsOperation('<?= e($decision['key']) ?>')" x-cloak x-transition data-operation-block="<?= e($decision['key']) ?>" class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4">
                    <div class="space-y-2 text-sm leading-6 text-cyan-950">
                        <p><strong>Tindakan (Target 2A):</strong> <?= e($phase2Operation['action']) ?></p>
                        <p><strong><?= $phase2Operation['target'] ?></strong></p>
                        <p><?= $phase2Operation['prompt'] ?></p>
                    </div>

                    <label class="mt-4 block text-sm font-black text-slate-800">
                        Notasi Operasi
                        <?= $operationInput($phase2Operation['notation'], 'Notasi operasi Target 2A') ?>
                    </label>

                    <div class="mt-5 overflow-x-auto rounded-2xl border border-cyan-200 bg-white p-4">
                        <p class="text-sm font-black text-cyan-950">Rincian</p>

                        <div class="mt-4 min-w-max space-y-4 text-center text-sm text-slate-900">
                            <div class="flex items-center justify-center gap-2">
                                <span>\(B_3 \leftarrow\)</span>
                                <?= $fieldInput($phase2Operation['coefficient'], 'Konstanta pengali Target 2A') ?>
                                <?= $matrixRow($phase2Operation['source'], 'Baris acuan Target 2A') ?>
                                <span>\(+\)</span>
                                <?= $matrixRow($phase2Operation['old'], 'Baris target awal Target 2A') ?>
                            </div>
                            <div class="flex items-center justify-center gap-2">
                                <span>\(B_3 \leftarrow\)</span>
                                <?= $matrixRow($inputCells($phase2Operation['product']), 'Hasil perkalian Target 2A') ?>
                                <span>\(+\)</span>
                                <?= $matrixRow($phase2Operation['old'], 'Baris target awal Target 2A') ?>
                            </div>
                            <div class="flex items-center justify-center gap-2">
                                <span>\(B_3 \leftarrow\)</span>
                                <?= $matrixRow($inputCells($phase2Operation['result_fields']), 'Hasil akhir Target 2A') ?>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p x-show="actionNotice['<?= e($decision['key']) ?>']" x-cloak x-text="actionNotice['<?= e($decision['key']) ?>']" class="text-sm font-semibold text-red-700"></p>
                        <button type="button" @click="continueAfterOperation('<?= e($decision['key']) ?>', <?= e($decision['step']) ?>)" class="w-full rounded-xl bg-cyan-600 px-5 py-3 text-sm font-black text-white transition hover:bg-cyan-700 sm:ml-auto sm:w-auto">
                            Lanjut ke Pertanyaan Berikutnya
                        </button>
                    </div>
                </div>
            </div>

            <?php $decision = $simulation32Decisions['fase2_q3_baris4']; ?>
            <div x-show="isVisible(<?= e($decision['step']) ?>)" x-cloak x-transition class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <p class="font-black text-slate-950"><?= e($decision['label']) ?></p>
                <p class="mt-2 text-sm leading-6 text-slate-700"><?= e($decision['question']) ?></p>
                <input type="hidden" name="answers[<?= e($decision['key']) ?>]" x-model="decisions['<?= e($decision['key']) ?>']">

                <div class="mt-4 grid grid-cols-2 gap-3 sm:max-w-sm">
                    <button type="button" @click="choose('<?= e($decision['key']) ?>', 'ya', <?= e($decision['step']) ?>)" :class="choiceClass('<?= e($decision['key']) ?>', 'ya')" <?= e($simulation32InputLocked($decision['key']) ? 'disabled' : '') ?> class="rounded-xl border px-4 py-3 text-sm font-black transition">Ya</button>
                    <button type="button" @click="choose('<?= e($decision['key']) ?>', 'tidak', <?= e($decision['step']) ?>)" :class="choiceClass('<?= e($decision['key']) ?>', 'tidak')" <?= e($simulation32InputLocked($decision['key']) ? 'disabled' : '') ?> class="rounded-xl border px-4 py-3 text-sm font-black transition">Tidak</button>
                </div>

                <div x-show="hasAnswer('<?= e($decision['key']) ?>')" x-cloak :class="feedbackClass('<?= e($decision['key']) ?>')" class="mt-4 rounded-2xl border p-4 text-sm leading-6">
                    <p x-text="feedbackMessage('<?= e($decision['key']) ?>')"></p>
                </div>
            </div>

            <div x-show="isVisible(7)" x-cloak x-transition class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-5 text-center">
                <p class="mb-4 text-sm font-black text-slate-700">Cek Poin Matriks Saat Ini</p>
                <div class="inline-block rounded-2xl border border-slate-200 bg-slate-50 px-6 py-4 text-lg text-slate-950">
                    \[
                        \left[
                        \begin{array}{rrrr|r}
                            1 & 1 & 2 & -1 & 5 \\
                            0 & 1 & 1 & 0 & 5 \\
                            0 & 0 & -1 & 5 & 7 \\
                            0 & 0 & -1 & 2 & 2
                        \end{array}
                        \right]
                    \]
                </div>
            </div>
        </div>

        <!-- Fase 3 -->
        <div x-show="isVisible(7)" x-cloak x-transition class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">Fase 3</p>
                <h3 class="mt-1 text-xl font-black text-slate-950">Evaluasi Baris ke-3</h3>
                <p class="mt-2 text-sm text-slate-600">Abaikan Baris-1 dan Baris-2, lalu fokus pada elemen utama Baris-3.</p>
            </div>

            <?php $decision = $simulation32Decisions['fase3_q1_pivot']; ?>
            <div x-show="isVisible(<?= e($decision['step']) ?>)" x-cloak x-transition class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <p class="font-black text-slate-950"><?= e($decision['label']) ?></p>
                <p class="mt-2 text-sm leading-6 text-slate-700"><?= e($decision['question']) ?></p>
                <input type="hidden" name="answers[<?= e($decision['key']) ?>]" x-model="decisions['<?= e($decision['key']) ?>']">

                <div class="mt-4 grid grid-cols-2 gap-3 sm:max-w-sm">
                    <button type="button" @click="choose('<?= e($decision['key']) ?>', 'ya', <?= e($decision['step']) ?>)" :class="choiceClass('<?= e($decision['key']) ?>', 'ya')" <?= e($simulation32InputLocked($decision['key']) ? 'disabled' : '') ?> class="rounded-xl border px-4 py-3 text-sm font-black transition">Ya</button>
                    <button type="button" @click="choose('<?= e($decision['key']) ?>', 'tidak', <?= e($decision['step']) ?>)" :class="choiceClass('<?= e($decision['key']) ?>', 'tidak')" <?= e($simulation32InputLocked($decision['key']) ? 'disabled' : '') ?> class="rounded-xl border px-4 py-3 text-sm font-black transition">Tidak</button>
                </div>

                <div x-show="hasAnswer('<?= e($decision['key']) ?>')" x-cloak :class="feedbackClass('<?= e($decision['key']) ?>')" class="mt-4 rounded-2xl border p-4 text-sm leading-6">
                    <p x-text="feedbackMessage('<?= e($decision['key']) ?>')"></p>
                </div>

                <div x-show="needsOperation('<?= e($decision['key']) ?>')" x-cloak x-transition data-operation-block="<?= e($decision['key']) ?>" class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4">
                    <div class="space-y-2 text-sm leading-6 text-cyan-950">
                        <p><strong>Tindakan:</strong> Tentukan konstanta \(k\) agar menjadi 1 dan tulis notasinya.</p>
                    </div>

                    <label class="mt-4 block text-sm font-black text-slate-800">
                        Notasi Operasi
                        <?= $operationInput('fase3_pivot_notasi', 'Notasi operasi Fase 3 Baris 3') ?>
                    </label>

                    <div class="mt-5 overflow-x-auto rounded-2xl border border-cyan-200 bg-white p-4">
                        <p class="text-sm font-black text-cyan-950">Rincian</p>
                        <div class="mt-4 flex min-w-max items-center justify-center gap-2 text-center text-sm text-slate-900">
                            <span>\(B_3 \leftarrow\)</span>
                            <?= $matrixRow($inputCells([
                                'fase3_pivot_hasil_31',
                                'fase3_pivot_hasil_32',
                                'fase3_pivot_hasil_33',
                                'fase3_pivot_hasil_34',
                                'fase3_pivot_hasil_35',
                            ]), 'Hasil perubahan Baris-3 pada Fase 3') ?>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p x-show="actionNotice['<?= e($decision['key']) ?>']" x-cloak x-text="actionNotice['<?= e($decision['key']) ?>']" class="text-sm font-semibold text-red-700"></p>
                        <button type="button" @click="continueAfterOperation('<?= e($decision['key']) ?>', <?= e($decision['step']) ?>)" class="w-full rounded-xl bg-cyan-600 px-5 py-3 text-sm font-black text-white transition hover:bg-cyan-700 sm:ml-auto sm:w-auto">
                            Lanjut ke Pertanyaan Berikutnya
                        </button>
                    </div>
                </div>
            </div>

            <?php $decision = $simulation32Decisions['fase3_q2_baris4']; ?>
            <div x-show="isVisible(<?= e($decision['step']) ?>)" x-cloak x-transition class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <p class="font-black text-slate-950"><?= e($decision['label']) ?></p>
                <p class="mt-2 text-sm leading-6 text-slate-700"><?= e($decision['question']) ?></p>
                <input type="hidden" name="answers[<?= e($decision['key']) ?>]" x-model="decisions['<?= e($decision['key']) ?>']">

                <div class="mt-4 grid grid-cols-2 gap-3 sm:max-w-sm">
                    <button type="button" @click="choose('<?= e($decision['key']) ?>', 'ya', <?= e($decision['step']) ?>)" :class="choiceClass('<?= e($decision['key']) ?>', 'ya')" <?= e($simulation32InputLocked($decision['key']) ? 'disabled' : '') ?> class="rounded-xl border px-4 py-3 text-sm font-black transition">Ya</button>
                    <button type="button" @click="choose('<?= e($decision['key']) ?>', 'tidak', <?= e($decision['step']) ?>)" :class="choiceClass('<?= e($decision['key']) ?>', 'tidak')" <?= e($simulation32InputLocked($decision['key']) ? 'disabled' : '') ?> class="rounded-xl border px-4 py-3 text-sm font-black transition">Tidak</button>
                </div>

                <div x-show="hasAnswer('<?= e($decision['key']) ?>')" x-cloak :class="feedbackClass('<?= e($decision['key']) ?>')" class="mt-4 rounded-2xl border p-4 text-sm leading-6">
                    <p x-text="feedbackMessage('<?= e($decision['key']) ?>')"></p>
                </div>

                <div x-show="needsOperation('<?= e($decision['key']) ?>')" x-cloak x-transition data-operation-block="<?= e($decision['key']) ?>" class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4">
                    <div class="space-y-2 text-sm leading-6 text-cyan-950">
                        <p><strong>Tindakan (Target 3A):</strong> <?= e($phase3Operation['action']) ?></p>
                        <p><strong><?= $phase3Operation['target'] ?></strong></p>
                        <p><?= $phase3Operation['prompt'] ?></p>
                    </div>

                    <label class="mt-4 block text-sm font-black text-slate-800">
                        Notasi Operasi
                        <?= $operationInput($phase3Operation['notation'], 'Notasi operasi Target 3A') ?>
                    </label>

                    <div class="mt-5 overflow-x-auto rounded-2xl border border-cyan-200 bg-white p-4">
                        <p class="text-sm font-black text-cyan-950">Rincian</p>
                        <div class="mt-4 min-w-max space-y-4 text-center text-sm text-slate-900">
                            <div class="flex items-center justify-center gap-2">
                                <span>\(B_4 \leftarrow\)</span>
                                <?= $fieldInput($phase3Operation['coefficient'], 'Konstanta pengali Target 3A') ?>
                                <?= $matrixRow($phase3Operation['source'], 'Baris acuan Target 3A') ?>
                                <span>\(+\)</span>
                                <?= $matrixRow($phase3Operation['old'], 'Baris target awal Target 3A') ?>
                            </div>
                            <div class="flex items-center justify-center gap-2">
                                <span>\(B_4 \leftarrow\)</span>
                                <?= $matrixRow($inputCells($phase3Operation['product']), 'Hasil perkalian Target 3A') ?>
                                <span>\(+\)</span>
                                <?= $matrixRow($phase3Operation['old'], 'Baris target awal Target 3A') ?>
                            </div>
                            <div class="flex items-center justify-center gap-2">
                                <span>\(B_4 \leftarrow\)</span>
                                <?= $matrixRow($inputCells($phase3Operation['result_fields']), 'Hasil akhir Target 3A') ?>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p x-show="actionNotice['<?= e($decision['key']) ?>']" x-cloak x-text="actionNotice['<?= e($decision['key']) ?>']" class="text-sm font-semibold text-red-700"></p>
                        <button type="button" @click="continueAfterOperation('<?= e($decision['key']) ?>', <?= e($decision['step']) ?>)" class="w-full rounded-xl bg-cyan-600 px-5 py-3 text-sm font-black text-white transition hover:bg-cyan-700 sm:ml-auto sm:w-auto">
                            Lanjut ke Pertanyaan Berikutnya
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fase 4 -->
        <div x-show="isVisible(9)" x-cloak x-transition class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">Fase 4</p>
                <h3 class="mt-1 text-xl font-black text-slate-950">Evaluasi Baris ke-4</h3>
            </div>

            <?php $decision = $simulation32Decisions['fase4_q1_pivot']; ?>
            <div x-show="isVisible(<?= e($decision['step']) ?>)" x-cloak x-transition class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <p class="font-black text-slate-950"><?= e($decision['label']) ?></p>
                <p class="mt-2 text-sm leading-6 text-slate-700"><?= e($decision['question']) ?></p>
                <input type="hidden" name="answers[<?= e($decision['key']) ?>]" x-model="decisions['<?= e($decision['key']) ?>']">

                <div class="mt-4 grid grid-cols-2 gap-3 sm:max-w-sm">
                    <button type="button" @click="choose('<?= e($decision['key']) ?>', 'ya', <?= e($decision['step']) ?>)" :class="choiceClass('<?= e($decision['key']) ?>', 'ya')" <?= e($simulation32InputLocked($decision['key']) ? 'disabled' : '') ?> class="rounded-xl border px-4 py-3 text-sm font-black transition">Ya</button>
                    <button type="button" @click="choose('<?= e($decision['key']) ?>', 'tidak', <?= e($decision['step']) ?>)" :class="choiceClass('<?= e($decision['key']) ?>', 'tidak')" <?= e($simulation32InputLocked($decision['key']) ? 'disabled' : '') ?> class="rounded-xl border px-4 py-3 text-sm font-black transition">Tidak</button>
                </div>

                <div x-show="hasAnswer('<?= e($decision['key']) ?>')" x-cloak :class="feedbackClass('<?= e($decision['key']) ?>')" class="mt-4 rounded-2xl border p-4 text-sm leading-6">
                    <p x-text="feedbackMessage('<?= e($decision['key']) ?>')"></p>
                </div>

                <div x-show="needsOperation('<?= e($decision['key']) ?>')" x-cloak x-transition data-operation-block="<?= e($decision['key']) ?>" class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4">
                    <div class="space-y-2 text-sm leading-6 text-cyan-950">
                        <p><strong>Tindakan:</strong> Tentukan konstanta pengali untuk mengubahnya menjadi 1 utama.</p>
                    </div>

                    <label class="mt-4 block text-sm font-black text-slate-800">
                        Notasi Operasi
                        <?= $operationInput('fase4_pivot_notasi', 'Notasi operasi Fase 4 Baris 4') ?>
                    </label>

                    <div class="mt-5 overflow-x-auto rounded-2xl border border-cyan-200 bg-white p-4">
                        <p class="text-sm font-black text-cyan-950">Rincian</p>
                        <div class="mt-4 flex min-w-max items-center justify-center gap-2 text-center text-sm text-slate-900">
                            <span>\(B_4 \leftarrow\)</span>
                            <?= $matrixRow($inputCells([
                                'fase4_pivot_hasil_41',
                                'fase4_pivot_hasil_42',
                                'fase4_pivot_hasil_43',
                                'fase4_pivot_hasil_44',
                                'fase4_pivot_hasil_45',
                            ]), 'Hasil perubahan Baris-4 pada Fase 4') ?>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p x-show="actionNotice['<?= e($decision['key']) ?>']" x-cloak x-text="actionNotice['<?= e($decision['key']) ?>']" class="text-sm font-semibold text-red-700"></p>
                        <button type="button" @click="continueAfterOperation('<?= e($decision['key']) ?>', <?= e($decision['step']) ?>)" class="w-full rounded-xl bg-cyan-600 px-5 py-3 text-sm font-black text-white transition hover:bg-cyan-700 sm:ml-auto sm:w-auto">
                            Lihat Output Matriks Eselon Baris
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="isWorkflowFinished()" x-cloak x-transition class="overflow-x-auto rounded-2xl border border-cyan-200 bg-cyan-50 p-6 text-center">
            <p class="text-sm font-black uppercase tracking-[0.14em] text-cyan-800">
                Output Matriks Eselon Baris
            </p>
            <p class="mt-2 text-sm text-cyan-900">
                Algoritma selesai.
            </p>

            <div class="mt-5 inline-block rounded-2xl border border-cyan-200 bg-white px-6 py-4 text-lg text-slate-950">
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

        <?php if ($simulation32FeedbackSummary()): ?>
            <p x-show="isWorkflowFinished()" x-cloak class="rounded-xl px-4 py-3 text-sm font-semibold <?= e($simulation32Completed ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-900') ?>">
                <?= e($simulation32FeedbackSummary()) ?>
            </p>
        <?php endif; ?>
        <?php if (! $simulation32Completed): ?>
            <div x-show="isWorkflowFinished()" x-cloak class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs leading-5 text-slate-500">
                    Kesempatan tersisa: <?= e($simulation32RemainingAttempts) ?> dari <?= e($simulation32MaxAttempts) ?>.
                    Kolom yang belum diisi tidak mengurangi kesempatan.
                </p>

                <button type="submit" class="rounded-xl bg-cyan-600 px-5 py-3 text-sm font-black text-white transition hover:bg-cyan-700">
                    Cek Simulasi
                </button>
            </div>
        <?php endif; ?>
    </form>


    <?php if ($practiceModal): ?>
        <?php
            $modalStatus = $practiceModal['status'] ?? 'revision';
            $modalIsSuccess = $modalStatus === 'success';
            $modalIsIncomplete = $modalStatus === 'incomplete';
            $modalIsAssisted = $modalStatus === 'assisted';
        ?>

        <div
            x-data="{ showSimulationModal: true }"
            x-cloak
            x-show="showSimulationModal"
            x-transition.opacity
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/70 px-4 py-6 backdrop-blur-sm"
            role="dialog"
            aria-modal="true"
            aria-labelledby="simulasi32-modal-title">

            <div
                x-show="showSimulationModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="scale-95 opacity-0"
                x-transition:enter-end="scale-100 opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="scale-100 opacity-100"
                x-transition:leave-end="scale-95 opacity-0"
                class="max-h-[calc(100vh-3rem)] w-full max-w-md overflow-y-auto rounded-[1.5rem] border border-white/10 bg-white shadow-2xl sm:max-w-lg">

                <div class="p-5 sm:p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-xl font-black <?= e($modalIsSuccess ? 'bg-green-100 text-green-700' : ($modalIsAssisted ? 'bg-indigo-100 text-indigo-700' : ($modalIsIncomplete ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'))) ?>">
                            <?= e($modalIsSuccess ? '✓' : ($modalIsAssisted ? 'i' : ($modalIsIncomplete ? '!' : '×'))) ?>
                        </div>

                        <div class="min-w-0">
                            <p id="simulasi32-modal-title" class="text-lg font-bold text-slate-900">
                                <?= e($practiceModal['title'] ?? 'Hasil Pemeriksaan') ?>
                            </p>

                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                <?= e($practiceModal['message'] ?? '') ?>
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button
                            type="button"
                            @click="showSimulationModal = false"
                            class="w-full rounded-xl px-5 py-3 text-sm font-bold transition sm:w-auto <?= e($modalIsSuccess ? 'bg-green-600 text-white hover:bg-green-700' : ($modalIsAssisted ? 'bg-indigo-600 text-white hover:bg-indigo-700' : ($modalIsIncomplete ? 'bg-yellow-400 text-slate-900 hover:bg-yellow-300' : 'bg-cyan-600 text-white hover:bg-cyan-700'))) ?>">
                            <?= e($practiceModal['button_label'] ?? 'Tutup') ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>


<script>
    /* SUBBAB_3_2_ALPINE_COMPONENT_FIXED */
    window.simulation32Component = function (element) {
        const parseData = function (value, fallback) {
            try {
                return JSON.parse(value || '');
            } catch (error) {
                return fallback;
            }
        };

        return {
            decisions: parseData(element.dataset.simulation32Decisions, {}),
            decisionOrder: parseData(element.dataset.simulation32DecisionOrder, []),
            correctAnswers: parseData(element.dataset.simulation32CorrectAnswers, {}),
            descriptions: parseData(element.dataset.simulation32Descriptions, {}),
            activeStep: 0,
            actionNotice: {},

            init() {
                this.$nextTick(() => this.restoreProgress());
            },

            isVisible(step) {
                return step <= this.activeStep;
            },

            hasAnswer(key) {
                return (this.decisions[key] || '') !== '';
            },

            isCorrect(key) {
                return this.decisions[key] === this.correctAnswers[key];
            },

            needsOperation(key) {
                return this.hasAnswer(key) && this.correctAnswers[key] === 'tidak';
            },

            choiceClass(key, answer) {
                if (this.decisions[key] !== answer) {
                    return 'border-slate-300 bg-white text-slate-700 hover:border-cyan-300 hover:bg-cyan-50';
                }

                return this.isCorrect(key)
                    ? 'border-green-500 bg-green-50 text-green-800'
                    : 'border-red-500 bg-red-50 text-red-800';
            },

            feedbackClass(key) {
                return this.isCorrect(key)
                    ? 'border-green-200 bg-green-50 text-green-900'
                    : 'border-red-200 bg-red-50 text-red-900';
            },

            feedbackMessage(key) {
                const actual = this.correctAnswers[key];
                const selectedCorrectly = this.isCorrect(key);
                const answerLabel = actual === 'ya' ? 'Ya' : 'Tidak';
                const opening = selectedCorrectly
                    ? 'Benar. Jawabannya ' + answerLabel + '.'
                    : 'Jawaban yang benar adalah ' + answerLabel + '.';

                const nextInstruction = actual === 'ya'
                    ? ' Tidak diperlukan operasi. Pertanyaan berikutnya telah dibuka.'
                    : ' Elemen tersebut belum memenuhi syarat, sehingga lakukan operasi berikut.';

                return opening + ' ' + (this.descriptions[key] || '') + nextInstruction;
            },

            choose(key, answer, step) {
                this.decisions[key] = answer;
                this.actionNotice[key] = '';

                if (this.correctAnswers[key] === 'ya') {
                    this.activeStep = Math.max(this.activeStep, step + 1);
                }
            },

            operationIsComplete(key) {
                const block = this.$root.querySelector('[data-operation-block="' + key + '"]');

                if (! block) {
                    return true;
                }

                const inputs = Array.from(block.querySelectorAll('input'))
                    .filter((input) => input.name && input.name.startsWith('answers['));

                return inputs.length > 0
                    && inputs.every((input) => String(input.value || '').trim() !== '');
            },

            continueAfterOperation(key, step) {
                if (! this.operationIsComplete(key)) {
                    this.actionNotice[key] = 'Lengkapi seluruh isian pada tindakan dan rincian sebelum melanjutkan.';
                    return;
                }

                this.actionNotice[key] = '';
                this.activeStep = Math.max(this.activeStep, step + 1);
            },

            restoreProgress() {
                this.activeStep = 0;

                for (let index = 0; index < this.decisionOrder.length; index++) {
                    const key = this.decisionOrder[index];

                    if (! this.hasAnswer(key)) {
                        break;
                    }

                    if (this.correctAnswers[key] === 'tidak' && ! this.operationIsComplete(key)) {
                        this.activeStep = index;
                        break;
                    }

                    this.activeStep = index + 1;
                }
            },

            isWorkflowFinished() {
                return this.activeStep >= this.decisionOrder.length;
            }
        };
    };
</script>
<script>
    (function () {
        const scrollStorageKey = 'ruangobe:subbab-3-2:scroll-position';

        /* SUBBAB32_OPERATION_INPUT_SYNC_V2 */
        function normalizeOperationLatex(latex) {
            let value = String(latex || '');

            for (let iteration = 0; iteration < 5; iteration += 1) {
                const before = value;

                value = value
                    .replace(/\\(?:mathrel|mathbin|mathord|mathop|mathrm|mathit|operatorname|text|mathbf|mathsf|mathtt)\s*\{([^{}]*)\}/g, '$1')
                    .replace(/\\(?:d?frac|tfrac)\s*\{([^{}]*)\}\s*\{([^{}]*)\}/g, '$1/$2')
                    .replace(/\\(?:d?frac|tfrac)\s*\{([^{}]*)\}\s*([+-]?\d+)/g, '$1/$2')
                    .replace(/\\(?:d?frac|tfrac)\s*([+-]?\d+)\s*\{([^{}]*)\}/g, '$1/$2');

                if (value === before) {
                    break;
                }
            }

            return value
                .replace(/\\longleftrightarrow|\\leftrightarrow|\\Longleftrightarrow|\\Leftrightarrow|\\iff/g, '↔')
                .replace(/\\longleftarrow|\\leftarrow|\\gets/g, '←')
                .replace(/\\longrightarrow|\\rightarrow/g, '→')
                .replace(/\\displaystyle|\\textstyle|\\scriptstyle|\\scriptscriptstyle|\\limits|\\nolimits/g, '')
                .replace(/\\left|\\right|\\bigl|\\bigr|\\Bigl|\\Bigr/g, '')
                .replace(/\\,|\\;|\\:|\\!|\\quad|\\qquad|~/g, '')
                .replace(/\\cdot|\\times|·|×/g, '')
                .replace(/[{}\\]/g, '')
                .replace(/[₁₂₃₄]/g, (symbol) => ({ '₁': '1', '₂': '2', '₃': '3', '₄': '4' }[symbol]))
                .replace(/[−–—]/g, '-')
                .replace(/[_*()[\]]/g, '')
                .replace(/[\u00A0\u2009\u202F]/g, '')
                .replace(/\s+/g, '')
                .toLowerCase()
                .trim();
        }

        function readOperationLatex(mathField) {
            if (! mathField) {
                return '';
            }

            try {
                if (typeof mathField.getValue === 'function') {
                    const latex = mathField.getValue('latex');

                    if (latex) {
                        return latex;
                    }
                }
            } catch (error) {
                // Gunakan nilai cadangan MathLive.
            }

            return mathField.value || mathField.textContent || '';
        }

        function syncMathField(mathField) {
            const hiddenInput = document.getElementById(mathField?.dataset?.hiddenInput || '');

            if (! hiddenInput) {
                return;
            }

            hiddenInput.value = normalizeOperationLatex(readOperationLatex(mathField));
            hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function initialiseMathField(mathField) {
            if (! mathField) {
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Nilai Awal MathLive
            |--------------------------------------------------------------------------
            | Atribut data-operation-latex menyimpan LaTeX yang aman untuk
            | ditampilkan. Nilai harus dipasang melalui API MathLive agar
            | \leftarrow dan \frac dirender menjadi panah serta pecahan,
            | bukan ditampilkan sebagai teks mentah.
            */
            const initialLatex = String(
                mathField.dataset.operationLatex || mathField.textContent || ''
            ).trim();

            if (initialLatex !== '' && mathField.dataset.subbab32LatexApplied !== 'true') {
                try {
                    if (typeof mathField.setValue === 'function') {
                        mathField.setValue(initialLatex);
                    } else {
                        mathField.value = initialLatex;
                    }
                } catch (error) {
                    mathField.value = initialLatex;
                }

                mathField.dataset.subbab32LatexApplied = 'true';
            }

            if (mathField.dataset.subbab32OperationInitialised !== 'true') {
                const syncValue = function () {
                    syncMathField(mathField);
                };

                mathField.addEventListener('input', syncValue);
                mathField.addEventListener('change', syncValue);
                mathField.addEventListener('blur', syncValue);
                mathField.dataset.subbab32OperationInitialised = 'true';
            }

            syncMathField(mathField);
        }

        const initializeMathFields = function () {
            document.querySelectorAll('[data-operation-math-field]').forEach(initialiseMathField);
        };

        const syncAllMathFields = function () {
            document.querySelectorAll('[data-operation-math-field]').forEach(syncMathField);
        };

        if (window.customElements && customElements.get('math-field')) {
            initializeMathFields();
        } else if (window.customElements) {
            customElements.whenDefined('math-field').then(initializeMathFields);
        }

        document.addEventListener('submit', function (event) {
            if (event.target && event.target.matches('[data-practice-scroll-form]')) {
                syncAllMathFields();
            }
        }, true);
        document.querySelectorAll('[data-practice-scroll-form]').forEach(function (form) {
            form.addEventListener('submit', function () {
                sessionStorage.setItem(scrollStorageKey, String(window.scrollY));
            });
        });

        const savedScroll = sessionStorage.getItem(scrollStorageKey);

        if (savedScroll !== null) {
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(function () {
                    window.scrollTo({
                        top: Number(savedScroll) || 0,
                        behavior: 'auto'
                    });
                });
            });
        }
    })();
</script>