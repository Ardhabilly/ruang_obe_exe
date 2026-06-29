{{-- SUBBAB_3_3_ELIMINASI_GAUSS_V1 --}}
@php
    $demoKey = 'contoh-simulasi-3-3-substitusi-balik';
    $activityKey = 'aktivitas-3-2-eliminasi-gauss';
    $definitionVersion = 'subbab33_eliminasi_gauss_v1';

    $buildPracticeState = function (string $practiceKey) use ($practiceSubmissions, $definitionVersion): array {
        $submission = $practiceSubmissions->get($practiceKey);

        $storedAnswers = is_array($submission?->answers) ? $submission->answers : [];
        $oldAnswers = old('answers', []);
        $oldAnswers = is_array($oldAnswers) ? $oldAnswers : [];
        $answers = array_replace($storedAnswers, $oldAnswers);

        $feedbackRaw = is_array($submission?->feedback) ? $submission->feedback : [];
        $feedback = is_array($feedbackRaw['fields'] ?? null)
            ? $feedbackRaw['fields']
            : collect($feedbackRaw)
                ->except(['_meta', 'groups', 'fields'])
                ->filter(fn ($item) => is_array($item))
                ->all();

        $meta = is_array($feedbackRaw['_meta'] ?? null)
            ? $feedbackRaw['_meta']
            : [];

        $usesComponentScope = ($meta['attempt_scope'] ?? null) === 'component'
            && ($meta['definition_version'] ?? null) === $definitionVersion;

        if (! $usesComponentScope && $submission) {
            $answers = $oldAnswers;
            $feedback = [];
            $meta = [];
        }

        $completed = $usesComponentScope && (bool) ($submission?->is_completed ?? false);
        $assisted = ($meta['completion_mode'] ?? null) === 'bantuan';
        $maxAttempts = max(1, (int) ($meta['max_attempts'] ?? 3));
        $attempts = max(0, min($maxAttempts, (int) ($meta['attempts'] ?? 0)));

        return [
            'submission' => $submission,
            'answers' => $answers,
            'feedback' => $feedback,
            'meta' => $meta,
            'completed' => $completed,
            'assisted' => $assisted,
            'max_attempts' => $maxAttempts,
            'attempts' => $attempts,
            'remaining_attempts' => max(0, $maxAttempts - $attempts),
        ];
    };

    $demo = $buildPracticeState($demoKey);
    $activity = $buildPracticeState($activityKey);

    $inputClass = function (array $state, string $fieldKey): string {
        return match ($state['feedback'][$fieldKey]['state'] ?? null) {
            'correct' => 'border-green-500 bg-green-50 text-green-950 focus:border-green-500 focus:ring-green-500',
            'revealed' => 'border-indigo-400 bg-indigo-50 text-indigo-950 focus:border-indigo-500 focus:ring-indigo-500',
            'wrong' => 'border-red-500 bg-red-50 text-red-950 focus:border-red-500 focus:ring-red-500',
            'empty' => 'border-yellow-400 bg-yellow-50 text-yellow-950 focus:border-yellow-500 focus:ring-yellow-500',
            default => 'border-slate-300 bg-white text-slate-900 focus:border-cyan-500 focus:ring-cyan-500',
        };
    };

    $inputLocked = function (array $state, string $fieldKey): bool {
        $field = $state['feedback'][$fieldKey] ?? [];

        return ! empty($field['is_revealed'])
            || (! empty($field['is_correct']) && ($field['state'] ?? null) === 'correct');
    };

    $fieldInput = function (
        array $state,
        string $fieldKey,
        string $label,
        string $sizeClass = 'h-10 w-14'
    ) use ($inputClass, $inputLocked) {
        $value = (string) ($state['answers'][$fieldKey] ?? '');
        $disabled = $inputLocked($state, $fieldKey) ? ' disabled' : '';

        return new \Illuminate\Support\HtmlString(
            '<input type="text"'
            . ' name="answers[' . e($fieldKey) . ']"'
            . ' value="' . e($value) . '"'
            . ' autocomplete="off"'
            . ' aria-label="' . e($label) . '"'
            . $disabled
            . ' class="' . e($sizeClass . ' rounded-xl border px-2 text-center font-bold shadow-sm transition focus:outline-none focus:ring-2 ' . $inputClass($state, $fieldKey)) . '">'
        );
    };

    $inlineBlank = function (array $state, string $fieldKey, string $label, string $sizeClass = 'h-9 w-16') use ($fieldInput) {
        return new \Illuminate\Support\HtmlString(
            '<span class="mx-1 inline-flex align-middle">'
            . $fieldInput($state, $fieldKey, $label, $sizeClass)
            . '</span>'
        );
    };

    $operationInput = function (array $state, string $fieldKey, string $label) use ($inputClass, $inputLocked) {
        $value = (string) ($state['answers'][$fieldKey] ?? '');
        $hiddenId = 'subbab33-' . $fieldKey . '-hidden';
        $readOnly = $inputLocked($state, $fieldKey) ? ' read-only' : '';

        return new \Illuminate\Support\HtmlString(
            '<div class="mt-2 rounded-2xl border p-2 shadow-sm transition '
            . e($inputClass($state, $fieldKey))
            . '">'
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

    $matrixRow = function (array $state, array $cells, string $label) use ($fieldInput) {
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
                        $state,
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

    $matrixBlock = function (array $state, array $rows, string $label) use ($fieldInput) {
        $columns = count($rows[0] ?? []);

        $html = '<span class="inline-grid gap-y-2 rounded-md border-x-2 border-slate-900 px-3 py-2 align-middle">';

        foreach ($rows as $rowIndex => $cells) {
            $html .= '<span class="grid items-center gap-x-2" style="grid-template-columns: repeat(' . $columns . ', minmax(3.25rem, auto));">';

            foreach ($cells as $columnIndex => $cell) {
                $rhsClass = $columnIndex === $columns - 1
                    ? ' border-l-2 border-l-slate-700 pl-2'
                    : '';

                if (is_array($cell) && isset($cell['field'])) {
                    $html .= '<span class="' . $rhsClass . '">'
                        . $fieldInput(
                            $state,
                            $cell['field'],
                            $label . ', Baris ' . ($rowIndex + 1) . ' Kolom ' . ($columnIndex + 1),
                            'h-10 w-14'
                        )
                        . '</span>';
                    continue;
                }

                $html .= '<span class="flex h-10 min-w-10 items-center justify-center font-semibold text-slate-900'
                    . $rhsClass . '">\\(' . e((string) $cell) . '\\)</span>';
            }

            $html .= '</span>';
        }

        $html .= '</span>';

        return new \Illuminate\Support\HtmlString($html);
    };

    $fractionInput = function (
        array $state,
        string $fieldKey,
        string $label,
        string $denominator,
        string $sizeClass = 'h-9 w-16'
    ) use ($fieldInput) {
        return new \Illuminate\Support\HtmlString(
            '<span class="mx-1 inline-flex min-w-12 flex-col items-stretch align-middle leading-none">'
            . $fieldInput($state, $fieldKey, $label, $sizeClass)
            . '<span class="mt-1 border-t border-slate-700 px-1 pt-1 text-center text-sm font-semibold text-slate-900">' . e($denominator) . '</span>'
            . '</span>'
        );
    };

    $inputCells = fn (array $fields): array => array_map(
        fn (string $field) => ['field' => $field],
        $fields
    );

    $statusClass = function (array $state): string {
        if (! $state['completed']) {
            return 'bg-yellow-50 text-yellow-700';
        }

        return $state['assisted']
            ? 'bg-indigo-100 text-indigo-700'
            : 'bg-green-50 text-green-700';
    };

    $statusLabel = function (array $state, string $completedLabel): string {
        if (! $state['completed']) {
            return 'Perlu Dikerjakan';
        }

        return $state['assisted']
            ? 'Selesai dengan Bantuan'
            : $completedLabel;
    };

    $feedbackSummary = function (array $state, string $type): ?string {
        if (empty($state['feedback'])) {
            return null;
        }

        if ($state['completed']) {
            return $state['assisted']
                ? $type . ' selesai dengan bantuan. Pelajari kembali jawaban yang ditampilkan pada kolom terkait.'
                : 'Seluruh jawaban sudah tepat.';
        }

        return 'Periksa kembali kolom berwarna merah. Kolom kuning perlu dilengkapi dan tidak mengurangi kesempatan.';
    };

    $decisionBlock = function (
        array $state,
        string $fieldKey,
        string $question,
        string $correctAnswer,
        string $detail
    ) use ($inputClass, $inputLocked) {
        $answer = strtolower(trim((string) ($state['answers'][$fieldKey] ?? '')));
        $isLocked = $inputLocked($state, $fieldKey);
        $fieldState = $state['feedback'][$fieldKey]['state'] ?? null;
        $fieldMessage = $state['feedback'][$fieldKey]['message'] ?? null;
        $hiddenId = 'subbab33-decision-' . $fieldKey;

        return new \Illuminate\Support\HtmlString(
            '<div x-data="{ choice: ' . e(json_encode($answer, JSON_UNESCAPED_UNICODE)) . ' }" class="rounded-2xl border border-slate-200 bg-slate-50 p-5">'
            . '<p class="font-black text-slate-950">' . e($question) . '</p>'
            . '<input type="hidden" id="' . e($hiddenId) . '" name="answers[' . e($fieldKey) . ']" x-model="choice">'
            . '<div class="mt-4 grid grid-cols-2 gap-3 sm:max-w-sm">'
            . '<button type="button" @click="choice = \'ya\'" '
            . ($isLocked ? 'disabled ' : '')
            . 'class="rounded-xl border px-4 py-3 text-sm font-black transition '
            . (($answer === 'ya' && $fieldState === 'correct') ? 'border-green-500 bg-green-50 text-green-800' : (($answer === 'ya' && $fieldState === 'wrong') ? 'border-red-500 bg-red-50 text-red-800' : 'border-slate-300 bg-white text-slate-700 hover:border-cyan-300 hover:bg-cyan-50'))
            . '">Ya</button>'
            . '<button type="button" @click="choice = \'tidak\'" '
            . ($isLocked ? 'disabled ' : '')
            . 'class="rounded-xl border px-4 py-3 text-sm font-black transition '
            . (($answer === 'tidak' && $fieldState === 'correct') ? 'border-green-500 bg-green-50 text-green-800' : (($answer === 'tidak' && $fieldState === 'wrong') ? 'border-red-500 bg-red-50 text-red-800' : 'border-slate-300 bg-white text-slate-700 hover:border-cyan-300 hover:bg-cyan-50'))
            . '">Tidak</button>'
            . '</div>'
            . '<p x-show="choice === \'ya\'" x-cloak class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm leading-6 text-red-800">'
            . 'Jawaban yang benar adalah TIDAK. ' . e($detail) . '</p>'
            . '<p x-show="choice === \'tidak\'" x-cloak class="mt-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm leading-6 text-green-800">'
            . 'Benar. ' . e($detail) . '</p>'
            . ($fieldMessage ? '<p class="mt-3 text-sm font-semibold text-slate-700">' . e($fieldMessage) . '</p>' : '')
            . '</div>'
        );
    };

    $practiceModalPayload = session('practice_modal');
    $practiceModal = is_array($practiceModalPayload)
        && in_array($practiceModalPayload['practice_key'] ?? null, [$demoKey, $activityKey], true)
            ? $practiceModalPayload
            : null;

    $phaseOne = [
        [
            'decision' => 'a_f1_q1_pivot',
            'question' => 'Pertanyaan 1: Apakah elemen utama Baris-1 bernilai 1? (Ya/Tidak). Jika tidak, lakukan operasi perkalian konstanta.',
            'detail' => 'Elemen utama Baris-1 bernilai 2 sehingga harus diubah menjadi 1 utama.',
            'notation' => 'a_f1_pivot_notasi',
            'action' => 'Kalikan Baris-1 dengan konstanta \\(\\frac{1}{2}\\).',
            'result' => ['a_f1_pivot_11', 'a_f1_pivot_12', 'a_f1_pivot_13', 'a_f1_pivot_14'],
        ],
        [
            'decision' => 'a_f1_q2_baris2',
            'question' => 'Pertanyaan 2 (Evaluasi Baris-2): Cek elemen Baris-2 Kolom-1. Apakah sudah bernilai 0? (Ya/Tidak). Jika tidak, lakukan eliminasi.',
            'detail' => 'Elemen pada Baris-2 Kolom-1 bernilai 4 sehingga perlu dieliminasi dengan acuan Baris-1.',
            'notation' => 'a_f1_b2_notasi',
            'action' => 'Gunakan Baris-1 sebagai acuan untuk mengenolkan elemen 4 pada Baris-2.',
            'product' => ['a_f1_b2_produk_1', 'a_f1_b2_produk_2', 'a_f1_b2_produk_3', 'a_f1_b2_produk_4'],
            'result' => ['a_f1_b2_hasil_1', 'a_f1_b2_hasil_2', 'a_f1_b2_hasil_3', 'a_f1_b2_hasil_4'],
            'old' => ['4', '1', '1', '11'],
            'target' => 'B_2',
        ],
        [
            'decision' => 'a_f1_q3_baris3',
            'question' => 'Pertanyaan 3 (Evaluasi Baris-3): Cek elemen Baris-3 Kolom-1. Apakah sudah bernilai 0? (Ya/Tidak). Jika tidak, lakukan eliminasi.',
            'detail' => 'Elemen pada Baris-3 Kolom-1 bernilai -2 sehingga perlu dieliminasi dengan acuan Baris-1.',
            'notation' => 'a_f1_b3_notasi',
            'action' => 'Gunakan Baris-1 sebagai acuan untuk mengenolkan elemen -2 pada Baris-3.',
            'product' => ['a_f1_b3_produk_1', 'a_f1_b3_produk_2', 'a_f1_b3_produk_3', 'a_f1_b3_produk_4'],
            'result' => ['a_f1_b3_hasil_1', 'a_f1_b3_hasil_2', 'a_f1_b3_hasil_3', 'a_f1_b3_hasil_4'],
            'old' => ['-2', '1', '3', '-2'],
            'target' => 'B_3',
        ],
    ];

    $phaseTwo = [
        [
            'decision' => 'a_f2_q1_pivot',
            'question' => 'Pertanyaan 1: Berdasarkan perhitunganmu, apakah elemen utama Baris-2 bernilai 1? (Ya/Tidak). Jika tidak, kalikan dengan pecahannya.',
            'detail' => 'Elemen utama Baris-2 bernilai -3 sehingga harus diubah menjadi 1 utama.',
            'notation' => 'a_f2_pivot_notasi',
            'action' => 'Kalikan Baris-2 dengan konstanta \\(-\\frac{1}{3}\\).',
            'result' => ['a_f2_pivot_23', 'a_f2_pivot_24'],
        ],
        [
            'decision' => 'a_f2_q2_baris3',
            'question' => 'Pertanyaan 2 (Evaluasi Baris-3): Cek elemen di bawahnya. Apakah elemen Baris-3 sudah 0? (Ya/Tidak). Jika belum, eliminasikan dengan acuan Baris-2.',
            'detail' => 'Elemen pada Baris-3 Kolom-2 bernilai 3 sehingga perlu dieliminasi dengan acuan Baris-2.',
            'notation' => 'a_f2_b3_notasi',
            'action' => 'Gunakan Baris-2 sebagai acuan untuk mengenolkan elemen 3 pada Baris-3.',
            'product' => ['a_f2_b3_produk_1', 'a_f2_b3_produk_2', 'a_f2_b3_produk_3', 'a_f2_b3_produk_4'],
            'old' => ['a_f2_b3_awal_1', 'a_f2_b3_awal_2', 'a_f2_b3_awal_3', 'a_f2_b3_awal_4'],
            'result' => ['a_f2_b3_hasil_3', 'a_f2_b3_hasil_4'],
            'target' => 'B_3',
        ],
    ];
@endphp

<section class="mt-8 space-y-8">
    <div class="space-y-5">
        <h2 class="text-2xl font-black text-slate-950">
            3.3 Menyelesaikan SPL dengan Metode Eliminasi Gauss
        </h2>

        <p>
            Metode Eliminasi Gauss berfokus pada teknik Substitusi Balik
            (<span class="italic">back-substitution</span>). Setelah matriks mencapai bentuk eselon
            baris pada subbab sebelumnya, langkah selanjutnya adalah memecahkan solusi dari
            persamaan paling bawah lalu bergerak ke atas.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">
                    Contoh Simulasi 3.3
                </p>

                <h3 class="mt-1 text-xl font-black text-slate-950">
                    Substitusi Balik dari Bentuk Eselon Baris
                </h3>

                <p class="mt-2 text-slate-600">
                    Selesaikan nilai \(\left(x_1, x_2, x_3, x_4\right)\) dari baris paling bawah ke atas.
                </p>
            </div>

            <span class="w-fit rounded-full px-4 py-2 text-sm font-bold {{ $statusClass($demo) }}">
                {{ $statusLabel($demo, 'Simulasi Selesai') }}
            </span>
        </div>

        @if (! $demo['completed'])
            <div class="mt-5 flex flex-col gap-2 rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm text-cyan-950 sm:flex-row sm:items-center sm:justify-between">
                <p class="font-bold">
                    Kesempatan tersisa: {{ $demo['remaining_attempts'] }} dari {{ $demo['max_attempts'] }}
                </p>

                <p class="text-xs leading-5 text-cyan-800">
                    Berlaku untuk seluruh contoh simulasi. Kolom yang belum diisi tidak mengurangi kesempatan.
                </p>
            </div>
        @endif

        @if ($feedbackSummary($demo, 'Contoh simulasi'))
            <div class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4 text-sm text-cyan-900">
                {{ $feedbackSummary($demo, 'Contoh simulasi') }}
            </div>
        @endif

        <form
            action="{{ route('mahasiswa.practice.submit', [$lesson->slug, $demoKey]) }}"
            method="POST"
            data-scroll-form
            class="mt-6 space-y-8">
            @csrf

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <p class="text-sm font-bold uppercase tracking-wide text-cyan-700">Baris 4</p>

                <div class="mt-4 overflow-x-auto">
                    <div class="min-w-max rounded-2xl border border-slate-200 bg-white px-6 py-4 text-center text-lg font-semibold text-slate-950">
                        \(x_4 = \frac{5}{3}\)
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <p class="text-sm font-bold uppercase tracking-wide text-cyan-700">Baris 3</p>

                <div class="mt-4 overflow-x-auto">
                    <div class="min-w-max space-y-3 rounded-2xl border border-slate-200 bg-white p-5 text-center text-lg text-slate-950">
                        <div>\(x_3 - 5x_4 = -7\)</div>
                        <div>\(x_3 - 5\left(\frac{5}{3}\right) = -7\)</div>
                        <div>\(x_3 - \frac{25}{3} = -7\)</div>
                        <div>\(x_3 = -7 + \frac{25}{3}\)</div>
                        <div class="flex items-center justify-center gap-1">
                            <span>\(x_3 =\)</span>
                            {!! $fractionInput($demo, 'c_b3_numerator_a', 'Pembilang negatif tujuh dalam penyebut tiga', '3') !!}
                            <span>\(+ \frac{25}{3}\)</span>
                        </div>
                        <div class="flex items-center justify-center gap-1">
                            <span>\(x_3 =\)</span>
                            {!! $fractionInput($demo, 'c_b3_numerator_b', 'Pembilang hasil x tiga dalam penyebut tiga', '3') !!}
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <p class="text-sm font-bold uppercase tracking-wide text-cyan-700">Baris 2</p>

                <div class="mt-4 overflow-x-auto">
                    <div class="min-w-max space-y-3 rounded-2xl border border-slate-200 bg-white p-5 text-center text-lg text-slate-950">
                        <div>\(x_2 + x_3 = 5\)</div>
                        <div class="flex items-center justify-center gap-1">
                            <span>\(x_2 +\)</span>
                            {!! $fractionInput($demo, 'c_b2_sub_x3', 'Nilai x tiga pada penyebut tiga', '3') !!}
                            <span>\(= 5\)</span>
                        </div>
                        <div class="flex items-center justify-center gap-1">
                            <span>\(x_2 = 5 -\)</span>
                            {!! $fractionInput($demo, 'c_b2_pengurang', 'Nilai yang dikurangkan pada Baris 2', '3') !!}
                        </div>
                        <div class="flex items-center justify-center gap-1">
                            <span>\(x_2 =\)</span>
                            {!! $fractionInput($demo, 'c_b2_pembilang_5', 'Pembilang lima dalam penyebut tiga', '3') !!}
                            <span>\(-\)</span>
                            {!! $fractionInput($demo, 'c_b2_pembilang_sub', 'Pembilang pengurang Baris 2', '3') !!}
                        </div>
                        <div class="flex items-center justify-center gap-1">
                            <span>\(x_2 =\)</span>
                            {!! $fractionInput($demo, 'c_b2_hasil', 'Pembilang hasil x dua', '3') !!}
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <p class="text-sm font-bold uppercase tracking-wide text-cyan-700">Baris 1</p>

                <div class="mt-4 overflow-x-auto">
                    <div class="min-w-max space-y-3 rounded-2xl border border-slate-200 bg-white p-5 text-center text-lg text-slate-950">
                        <div>\(x_1 + x_2 + 2x_3 - x_4 = 5\)</div>
                        <div class="flex flex-wrap items-center justify-center gap-1">
                            <span>\(x_1 +\)</span>
                            {!! $fractionInput($demo, 'c_b1_x2', 'Pembilang nilai x dua', '3') !!}
                            <span>\(+ 2\)</span>
                            {!! $fractionInput($demo, 'c_b1_x3', 'Pembilang nilai x tiga', '3') !!}
                            <span>\(- \frac{5}{3} = 5\)</span>
                        </div>
                        <div class="flex flex-wrap items-center justify-center gap-1">
                            <span>\(x_1 +\)</span>
                            {!! $fractionInput($demo, 'c_b1_penyederhanaan_a', 'Pembilang hasil penyederhanaan Baris 1', '3') !!}
                            <span>\(+\)</span>
                            {!! $fractionInput($demo, 'c_b1_penyederhanaan_b', 'Pembilang dua kali x tiga pada Baris 1', '3') !!}
                            <span>\(- \frac{5}{3} = 5\)</span>
                        </div>
                        <div class="flex items-center justify-center gap-1">
                            <span>\(x_1 +\)</span>
                            {!! $fractionInput($demo, 'c_b1_total', 'Pembilang total pada Baris 1', '3') !!}
                            <span>\(= 5\)</span>
                        </div>
                        <div class="flex items-center justify-center gap-1">
                            <span>\(x_1 = 5 -\)</span>
                            {!! $fractionInput($demo, 'c_b1_pengurang', 'Pembilang pengurang Baris 1', '3') !!}
                        </div>
                        <div class="flex items-center justify-center gap-1">
                            <span>\(x_1 =\)</span>
                            {!! $fractionInput($demo, 'c_b1_pembilang_5', 'Pembilang lima dalam penyebut tiga pada Baris 1', '3') !!}
                            <span>\(-\)</span>
                            {!! $fractionInput($demo, 'c_b1_pembilang_sub', 'Pembilang pengurang dalam penyebut tiga pada Baris 1', '3') !!}
                        </div>
                        <div class="flex items-center justify-center gap-1">
                            <span>\(x_1 =\)</span>
                            {!! $fractionInput($demo, 'c_b1_hasil', 'Pembilang hasil x satu', '3') !!}
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-cyan-200 bg-cyan-50 p-5">
                <p class="font-black text-slate-950">Jadi hasilnya adalah</p>

                <div class="mt-4 flex flex-wrap items-center gap-3 text-lg font-bold text-slate-950">
                    <span>\(x_1 =\)</span>
                    {!! $fieldInput($demo, 'c_hasil_x1', 'Nilai akhir x satu', 'h-10 w-20') !!}
                    <span>, \(x_2 =\)</span>
                    {!! $fieldInput($demo, 'c_hasil_x2', 'Nilai akhir x dua', 'h-10 w-20') !!}
                    <span>, \(x_3 =\)</span>
                    {!! $fieldInput($demo, 'c_hasil_x3', 'Nilai akhir x tiga', 'h-10 w-20') !!}
                    <span>, \(x_4 =\)</span>
                    {!! $fieldInput($demo, 'c_hasil_x4', 'Nilai akhir x empat', 'h-10 w-20') !!}
                </div>
            </div>

            @if (! $demo['completed'])
                <button
                    type="submit"
                    class="rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-700">
                    Cek Simulasi
                </button>
            @endif
        </form>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">
                    Aktivitas 3.2
                </p>

                <h3 class="mt-1 text-xl font-black text-slate-950">
                    Menyelesaikan SPL dengan Eliminasi Gauss
                </h3>

                <p class="mt-2 text-slate-600">
                    Uji kemampuan komputasi logismu pada sistem persamaan linear berikut. Jangan lewatkan proses evaluasinya.
                </p>
            </div>

            <span class="w-fit rounded-full px-4 py-2 text-sm font-bold {{ $statusClass($activity) }}">
                {{ $statusLabel($activity, 'Aktivitas Selesai') }}
            </span>
        </div>

        @if (! $activity['completed'])
            <div class="mt-5 flex flex-col gap-2 rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm text-cyan-950 sm:flex-row sm:items-center sm:justify-between">
                <p class="font-bold">
                    Kesempatan tersisa: {{ $activity['remaining_attempts'] }} dari {{ $activity['max_attempts'] }}
                </p>

                <p class="text-xs leading-5 text-cyan-800">
                    Berlaku untuk seluruh Aktivitas 3.2. Kolom yang belum diisi tidak mengurangi kesempatan.
                </p>
            </div>
        @endif

        @if ($feedbackSummary($activity, 'Aktivitas'))
            <div class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4 text-sm text-cyan-900">
                {{ $feedbackSummary($activity, 'Aktivitas') }}
            </div>
        @endif

        <div class="mt-6 overflow-x-auto">
            <div class="min-w-max rounded-2xl border border-slate-200 bg-slate-50 px-7 py-5 text-xl text-slate-950">
                \[
                    \left[
                    \begin{array}{ccc|c}
                        2 & 2 & -1 & 4 \\
                        4 & 1 & 1 & 11 \\
                        -2 & 1 & 3 & -2
                    \end{array}
                    \right]
                \]
            </div>
        </div>

        <form
            action="{{ route('mahasiswa.practice.submit', [$lesson->slug, $activityKey]) }}"
            method="POST"
            data-scroll-form
            class="mt-8 space-y-8">
            @csrf

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-lg font-black text-slate-950">Fase 1: Evaluasi Kolom 1</p>

                <div class="mt-5 space-y-6">
                    @foreach ($phaseOne as $index => $step)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            {!! $decisionBlock(
                                $activity,
                                $step['decision'],
                                $step['question'],
                                'tidak',
                                $step['detail']
                            ) !!}

                            <div class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-5">
                                <p class="text-sm font-bold text-cyan-800">Tindakan</p>
                                <p class="mt-2 leading-7 text-slate-700">{{ $step['action'] }}</p>

                                <label class="mt-5 block text-sm font-black text-slate-800">
                                    Notasi Operasi
                                </label>
                                {!! $operationInput($activity, $step['notation'], 'Notasi operasi fase 1 langkah ' . ($index + 1)) !!}

                                @if ($index === 0)
                                    <p class="mt-5 text-sm font-bold text-slate-800">Hasil Matriks</p>
                                    <div class="mt-3 overflow-x-auto">
                                        <div class="min-w-max rounded-2xl border border-slate-200 bg-white p-4 text-center">
                                            {!! $matrixBlock($activity, [
                                                $inputCells($step['result']),
                                                ['4', '1', '1', '11'],
                                                ['-2', '1', '3', '-2'],
                                            ], 'Hasil Matriks Fase 1') !!}
                                        </div>
                                    </div>
                                @else
                                    <p class="mt-5 text-sm font-bold text-slate-800">Rincian</p>

                                    <div class="mt-3 overflow-x-auto">
                                        <div class="min-w-max space-y-4 rounded-2xl border border-slate-200 bg-white p-4 text-center text-slate-950">
                                            <div class="flex items-center justify-center gap-3">
                                                <span class="font-semibold">\({!! $step['target'] !!} \leftarrow\)</span>
                                                {!! $matrixRow($activity, $inputCells($step['product']), 'Hasil perkalian fase 1') !!}
                                                <span class="font-semibold">\( + \)</span>
                                                {!! $matrixRow($activity, $step['old'], 'Baris target awal fase 1') !!}
                                            </div>

                                            <div class="flex items-center justify-center gap-3">
                                                <span class="font-semibold">\({!! $step['target'] !!} \leftarrow\)</span>
                                                {!! $matrixRow($activity, $inputCells($step['result']), 'Hasil akhir fase 1') !!}
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-lg font-black text-slate-950">Fase 2: Evaluasi Kolom 2</p>

                <div class="mt-5 space-y-6">
                    @foreach ($phaseTwo as $index => $step)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            {!! $decisionBlock(
                                $activity,
                                $step['decision'],
                                $step['question'],
                                'tidak',
                                $step['detail']
                            ) !!}

                            <div class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-5">
                                <p class="text-sm font-bold text-cyan-800">Tindakan</p>
                                <p class="mt-2 leading-7 text-slate-700">{{ $step['action'] }}</p>

                                <label class="mt-5 block text-sm font-black text-slate-800">
                                    Notasi Operasi
                                </label>
                                {!! $operationInput($activity, $step['notation'], 'Notasi operasi fase 2 langkah ' . ($index + 1)) !!}

                                @if ($index === 0)
                                    <p class="mt-5 text-sm font-bold text-slate-800">Hasil Matriks</p>
                                    <div class="mt-3 overflow-x-auto">
                                        <div class="min-w-max rounded-2xl border border-slate-200 bg-white p-4">
                                            <div class="flex items-center justify-center gap-3 text-slate-950">
                                                <span class="font-semibold">\(B_2 \leftarrow\)</span>
                                                {!! $matrixRow($activity, ['0', '1', ['field' => $step['result'][0]], ['field' => $step['result'][1]]], 'Hasil Baris 2 Fase 2') !!}
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <p class="mt-5 text-sm font-bold text-slate-800">Rincian</p>

                                    <div class="mt-3 overflow-x-auto">
                                        <div class="min-w-max space-y-4 rounded-2xl border border-slate-200 bg-white p-4 text-center text-slate-950">
                                            <div class="flex items-center justify-center gap-3">
                                                <span class="font-semibold">\({!! $step['target'] !!} \leftarrow\)</span>
                                                {!! $matrixRow($activity, $inputCells($step['product']), 'Hasil perkalian fase 2') !!}
                                                <span class="font-semibold">\( + \)</span>
                                                {!! $matrixRow($activity, $inputCells($step['old']), 'Baris target awal fase 2') !!}
                                            </div>

                                            <div class="flex items-center justify-center gap-3">
                                                <span class="font-semibold">\({!! $step['target'] !!} \leftarrow\)</span>
                                                {!! $matrixRow($activity, ['0', '0', ['field' => $step['result'][0]], ['field' => $step['result'][1]]], 'Hasil akhir fase 2') !!}
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-lg font-black text-slate-950">Fase 3: Penyelesaian</p>

                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <p class="font-black text-slate-950">
                        Pertanyaan 1: Jadikan elemen pembuka Baris-3 yang baru menjadi 1 utama.
                    </p>

                    <label class="mt-5 block text-sm font-black text-slate-800">
                        Notasi Operasi
                    </label>
                    {!! $operationInput($activity, 'a_f3_notasi', 'Notasi operasi Fase 3') !!}

                    <p class="mt-5 text-sm font-bold text-slate-800">
                        Tuliskan Matriks Eselon Baris Final
                    </p>

                    <div class="mt-3 overflow-x-auto">
                        <div class="min-w-max rounded-2xl border border-slate-200 bg-white p-4 text-center">
                            {!! $matrixBlock($activity, [
                                $inputCells(['a_f3_final_11', 'a_f3_final_12', 'a_f3_final_13', 'a_f3_final_14']),
                                ['0', '1', ['field' => 'a_f3_final_23'], ['field' => 'a_f3_final_24']],
                                ['0', '0', '1', ['field' => 'a_f3_final_34']],
                            ], 'Matriks Eselon Baris Final') !!}
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-lg font-black text-slate-950">
                    Fase 4: Menyelesaikan SPL dengan Metode Eliminasi Gauss
                </p>

                <p class="mt-3 leading-7 text-slate-700">
                    Berdasarkan matriks eselon baris final yang kamu dapatkan di atas, jabarkan proses
                    substitusi baliknya untuk mencari nilai \(z\), \(y\), dan \(x\).
                </p>

                <div class="mt-5 space-y-5">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <p class="font-black text-slate-950">1. Baris 3</p>
                        <div class="mt-4 text-center text-lg font-semibold text-slate-950">
                            \(z =\) {!! $inlineBlank($activity, 'a_f4_z', 'Nilai z', 'h-10 w-20') !!}
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <p class="font-black text-slate-950">2. Baris 2</p>

                        <div class="mt-4 overflow-x-auto">
                            <div class="min-w-max space-y-3 rounded-2xl border border-slate-200 bg-white p-5 text-center text-lg text-slate-950">
                                <div>
                                    \(y +\)
                                    {!! $inlineBlank($activity, 'a_f4_y_koefisien', 'Koefisien z pada Baris 2') !!}
                                    \(z =\)
                                    {!! $inlineBlank($activity, 'a_f4_y_ruas_kanan_awal', 'Ruas kanan Baris 2') !!}
                                </div>
                                <div>
                                    \(y +\)
                                    {!! $inlineBlank($activity, 'a_f4_y_koefisien_sub', 'Koefisien z setelah substitusi') !!}
                                    {!! $inlineBlank($activity, 'a_f4_y_sub_z', 'Nilai z setelah substitusi') !!}
                                    \(=\)
                                    {!! $inlineBlank($activity, 'a_f4_y_ruas_kanan_sub', 'Ruas kanan setelah substitusi') !!}
                                </div>
                                <div>
                                    \(y -\)
                                    {!! $inlineBlank($activity, 'a_f4_y_pengurang', 'Nilai yang dikurangkan pada Baris 2') !!}
                                    \(=\)
                                    {!! $inlineBlank($activity, 'a_f4_y_ruas_kanan_sederhana', 'Ruas kanan sederhana Baris 2') !!}
                                </div>
                                <div>
                                    \(y =\)
                                    {!! $inlineBlank($activity, 'a_f4_y_ruas_kanan_pindah', 'Ruas kanan setelah pindah ruas') !!}
                                    \(+\)
                                    {!! $inlineBlank($activity, 'a_f4_y_pindah_ruas', 'Nilai pindah ruas Baris 2') !!}
                                </div>
                                <div>
                                    \(y =\)
                                    {!! $inlineBlank($activity, 'a_f4_y_hasil', 'Nilai y') !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <p class="font-black text-slate-950">3. Baris 1</p>

                        <div class="mt-4 overflow-x-auto">
                            <div class="min-w-max space-y-3 rounded-2xl border border-slate-200 bg-white p-5 text-center text-lg text-slate-950">
                                <div>
                                    \(x + y - \frac{1}{2}z =\)
                                    {!! $inlineBlank($activity, 'a_f4_x_ruas_kanan_awal', 'Ruas kanan Baris 1') !!}
                                </div>
                                <div>
                                    \(x +\)
                                    {!! $inlineBlank($activity, 'a_f4_x_sub_y', 'Nilai y pada Baris 1') !!}
                                    \(- \frac{1}{2}\)
                                    {!! $inlineBlank($activity, 'a_f4_x_sub_z', 'Nilai z pada Baris 1') !!}
                                    \(=\)
                                    {!! $inlineBlank($activity, 'a_f4_x_ruas_kanan_sub', 'Ruas kanan setelah substitusi Baris 1') !!}
                                </div>
                                <div>
                                    \(x - \frac{1}{2} =\)
                                    {!! $inlineBlank($activity, 'a_f4_x_ruas_kanan_sederhana', 'Ruas kanan sederhana Baris 1') !!}
                                </div>
                                <div>
                                    \(x =\)
                                    {!! $inlineBlank($activity, 'a_f4_x_pindah_ruas', 'Ruas kanan setelah pindah ruas Baris 1') !!}
                                    \(+ \frac{1}{2}\)
                                </div>
                                <div class="flex items-center justify-center gap-1">
                                    <span>\(x =\)</span>
                                    {!! $fractionInput($activity, 'a_f4_x_pembilang', 'Pembilang hasil x', '2') !!}
                                    <span>\(+ \frac{1}{2}\)</span>
                                </div>
                                <div class="flex items-center justify-center gap-1">
                                    <span>\(x =\)</span>
                                    {!! $fractionInput($activity, 'a_f4_x_hasil_pembilang', 'Pembilang nilai x', '2') !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-cyan-200 bg-cyan-50 p-5">
                        <p class="font-black text-slate-950">Jadi hasilnya adalah</p>

                        <div class="mt-4 flex flex-wrap items-center gap-3 text-lg font-bold text-slate-950">
                            <span>\(x =\)</span>
                            {!! $fieldInput($activity, 'a_f4_hasil_x', 'Nilai akhir x', 'h-10 w-20') !!}
                            <span>, \(y =\)</span>
                            {!! $fieldInput($activity, 'a_f4_hasil_y', 'Nilai akhir y', 'h-10 w-20') !!}
                            <span>, \(z =\)</span>
                            {!! $fieldInput($activity, 'a_f4_hasil_z', 'Nilai akhir z', 'h-10 w-20') !!}
                        </div>
                    </div>
                </div>
            </div>

            @if (! $activity['completed'])
                <button
                    type="submit"
                    class="rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-700">
                    Cek Jawaban Aktivitas
                </button>
            @endif
        </form>
    </div>

    @if ($practiceModal)
        @php
            $modalStatus = $practiceModal['status'] ?? 'revision';
            $modalIsSuccess = $modalStatus === 'success';
            $modalIsIncomplete = $modalStatus === 'incomplete';
            $modalIsAssisted = $modalStatus === 'assisted';
        @endphp

        <div
            x-data="{ showPracticeModal: true }"
            x-cloak
            x-show="showPracticeModal"
            x-transition.opacity
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/70 px-4 py-6 backdrop-blur-sm"
            role="dialog"
            aria-modal="true"
            aria-labelledby="subbab33-modal-title">

            <div
                x-show="showPracticeModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="scale-95 opacity-0"
                x-transition:enter-end="scale-100 opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="scale-100 opacity-100"
                x-transition:leave-end="scale-95 opacity-0"
                class="max-h-[calc(100vh-3rem)] w-full max-w-md overflow-y-auto rounded-[1.5rem] border border-white/10 bg-white shadow-2xl sm:max-w-lg">

                <div class="p-5 sm:p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-xl font-black {{ $modalIsSuccess ? 'bg-green-100 text-green-700' : ($modalIsAssisted ? 'bg-indigo-100 text-indigo-700' : ($modalIsIncomplete ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700')) }}">
                            {{ $modalIsSuccess ? '✓' : ($modalIsAssisted ? 'i' : ($modalIsIncomplete ? '!' : '×')) }}
                        </div>

                        <div class="min-w-0">
                            <p id="subbab33-modal-title" class="text-lg font-bold text-slate-900">
                                {{ $practiceModal['title'] ?? 'Hasil Pemeriksaan' }}
                            </p>

                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                {{ $practiceModal['message'] ?? '' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button
                            type="button"
                            @click="showPracticeModal = false"
                            class="w-full rounded-xl px-5 py-3 text-sm font-bold transition sm:w-auto {{ $modalIsSuccess ? 'bg-green-600 text-white hover:bg-green-700' : ($modalIsAssisted ? 'bg-indigo-600 text-white hover:bg-indigo-700' : ($modalIsIncomplete ? 'bg-yellow-400 text-slate-900 hover:bg-yellow-300' : 'bg-cyan-600 text-white hover:bg-cyan-700')) }}">
                            {{ $practiceModal['button_label'] ?? 'Tutup' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</section>

<script>
    (function () {
        const scrollStorageKey = 'ruangobe:subbab-3-3:scroll-position';

        function syncMathField(mathField) {
            const hiddenInput = document.getElementById(mathField.dataset.hiddenInput);

            if (! hiddenInput) {
                return;
            }

            const value = typeof mathField.getValue === 'function'
                ? mathField.getValue('latex')
                : mathField.value;

            hiddenInput.value = value || '';
            hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-operation-math-field]').forEach(function (mathField) {
                mathField.addEventListener('input', function () {
                    syncMathField(mathField);
                });

                mathField.addEventListener('change', function () {
                    syncMathField(mathField);
                });

                mathField.addEventListener('blur', function () {
                    syncMathField(mathField);
                });
            });

            document.querySelectorAll('[data-scroll-form]').forEach(function (form) {
                form.addEventListener('submit', function () {
                    /*
                    | Beberapa perangkat hanya menyimpan nilai MathLive saat elemen
                    | kehilangan fokus. Salin seluruh nilai lagi tepat sebelum form
                    | dikirim agar jawaban notasi operasi tidak terkirim kosong atau
                    | memakai nilai lama.
                    */
                    form.querySelectorAll('[data-operation-math-field]').forEach(function (mathField) {
                        syncMathField(mathField);
                    });

                    sessionStorage.setItem(scrollStorageKey, String(window.scrollY));
                });
            });

            const savedPosition = sessionStorage.getItem(scrollStorageKey);

            if (savedPosition !== null) {
                requestAnimationFrame(function () {
                    window.scrollTo({ top: Number(savedPosition), left: 0, behavior: 'instant' });
                    sessionStorage.removeItem(scrollStorageKey);
                });
            }

            window.renderMathJax?.();
        });
    })();
</script>
