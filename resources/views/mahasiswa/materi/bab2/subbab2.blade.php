{{-- SUBBAB_2_2_OBE_REVISION_V2 --}}
{{-- SUBBAB_2_2_OBE_UI_REFINEMENT_V3 --}}
{{-- SUBBAB_2_2_OBE_MATHLIVE_NOTATION_FIX_V4 --}}
<style>
    /*
     * MathLive berada di dalam grid Tailwind. min-width: 0 pada pembungkus
     * mencegah elemen terpotong saat notasi lebih panjang daripada kolom.
     */
    .obe-operation-entry {
        width: 100%;
        min-width: 0;
        max-width: 100%;
    }

    math-field.obe-operation-mathfield {
        box-sizing: border-box;
        display: block;
        width: 100%;
        min-width: 0;
        min-inline-size: 100%;
        max-width: 100%;
        min-height: 3.4rem;
        padding: 0.7rem 0.9rem;
        overflow-x: auto;
        overflow-y: hidden;
        font-size: 1.125rem;
        line-height: 1.65;
        white-space: nowrap;
    }

    math-field.obe-operation-mathfield::part(container) {
        min-width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
    }

    @media (max-width: 639px) {
        math-field.obe-operation-mathfield {
            min-height: 3.2rem;
            padding: 0.65rem 0.75rem;
            font-size: 1rem;
        }
    }
</style>
<style>
    .obe-content-card {
        overflow: hidden;
    }

    .obe-content-card > * + * {
        scroll-margin-top: 7rem;
    }

    .obe-matrix {
        max-width: 100%;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .obe-matrix input {
        min-width: 3.5rem;
    }

    .obe-calculation-stack {
        padding: 0.25rem 0 0.5rem;
    }

    .obe-calculation-stack > div {
        display: inline-flex;
        min-width: max-content;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        margin: 0 auto;
        padding: 0.75rem;
        border: 1px solid rgb(226 232 240);
        border-radius: 0.875rem;
        background: rgba(255, 255, 255, 0.9);
    }

    .obe-operation-entry {
        position: relative;
    }

    math-field.obe-operation-mathfield {
        display: block;
        width: 100%;
        min-height: 2.75rem;
        padding: 0.55rem 0.8rem;
        border-width: 1px;
        border-radius: 0.75rem;
        font-size: 1rem;
        font-weight: 700;
        line-height: 1.5;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        --caret-color: rgb(8 145 178);
        --selection-background-color: rgba(34, 211, 238, 0.24);
        --contains-highlight-background-color: transparent;
        --primary: rgb(8 145 178);
    }

    math-field.obe-operation-mathfield:focus-within {
        outline: 2px solid rgba(34, 211, 238, 0.38);
        outline-offset: 2px;
    }

    math-field.obe-operation-mathfield[read-only] {
        cursor: not-allowed;
        opacity: 0.9;
    }

    @media (max-width: 639px) {
        .obe-matrix {
            gap: 0.35rem;
            padding: 0.4rem;
        }

        .obe-matrix input {
            min-width: 3.15rem;
        }

        .obe-calculation-stack > div {
            gap: 0.4rem;
            padding: 0.6rem;
        }
    }
</style>
@php
    $subbab22PracticeKeys = [
        'contoh-simulasi-2-2-pertukaran',
        'contoh-simulasi-2-2-perkalian-a',
        'contoh-simulasi-2-2-perkalian-b',
        'contoh-simulasi-2-2-penjumlahan-a',
        'contoh-simulasi-2-2-penjumlahan-b',
        'aktivitas-2-1-obe',
    ];

    $practiceState = function (string $key) use ($practiceSubmissions): array {
        $submission = $practiceSubmissions->get($key);
        $answers = is_array($submission?->answers) ? $submission->answers : [];
        $feedbackRaw = is_array($submission?->feedback) ? $submission->feedback : [];
        $feedback = is_array($feedbackRaw['fields'] ?? null)
            ? $feedbackRaw['fields']
            : collect($feedbackRaw)
                ->except(['_meta', 'groups', 'fields'])
                ->filter(fn ($item) => is_array($item))
                ->all();

        $meta = is_array($feedbackRaw['_meta'] ?? null) ? $feedbackRaw['_meta'] : [];

        return [
            'answers' => $answers,
            'feedback' => $feedback,
            'completed' => (bool) ($submission?->is_completed ?? false),
            'assisted' => ($meta['completion_mode'] ?? null) === 'bantuan',
            'attempts' => max(0, min(3, (int) ($meta['attempts'] ?? 0))),
        ];
    };

    $inputValue = fn (array $state, string $field): string => (string) ($state['answers'][$field] ?? '');

    $inputClass = function (array $state, string $field): string {
        return match ($state['feedback'][$field]['state'] ?? null) {
            'correct' => 'border-green-500 bg-green-50 text-green-950 focus:border-green-500 focus:ring-green-500',
            'revealed' => 'border-indigo-400 bg-indigo-50 text-indigo-950 focus:border-indigo-500 focus:ring-indigo-500',
            'wrong' => 'border-red-500 bg-red-50 text-red-950 focus:border-red-500 focus:ring-red-500',
            'empty' => 'border-yellow-400 bg-yellow-50 text-yellow-950 focus:border-yellow-500 focus:ring-yellow-500',
            default => 'border-slate-300 bg-white text-slate-900 focus:border-cyan-500 focus:ring-cyan-500',
        };
    };

    $inputLocked = function (array $state, string $field): bool {
        $feedback = $state['feedback'][$field] ?? [];

        return ! empty($feedback['is_revealed'])
            || (! empty($feedback['is_correct']) && ($feedback['state'] ?? null) === 'correct');
    };

    $hasFeedback = fn (array $state): bool => ! empty($state['feedback']);

    $feedbackSummary = function (array $state): ?string {
        if (empty($state['feedback'])) {
            return null;
        }

        if ($state['completed']) {
            return $state['assisted']
                ? 'Komponen selesai dengan bantuan. Pelajari kembali jawaban yang ditampilkan pada kolom terkait.'
                : 'Semua jawaban pada komponen ini sudah benar.';
        }

        return 'Periksa kembali kolom berwarna merah. Kolom kuning perlu dilengkapi dan tidak mengurangi kesempatan.';
    };

    $statusLabel = function (array $state, string $pending, string $done): string {
        if (! $state['completed']) {
            return $pending;
        }

        return $state['assisted'] ? 'Selesai dengan Bantuan' : $done;
    };

    $statusClass = function (array $state): string {
        if (! $state['completed']) {
            return 'bg-yellow-50 text-yellow-700';
        }

        return $state['assisted']
            ? 'bg-indigo-100 text-indigo-700'
            : 'bg-green-50 text-green-700';
    };

    $operationValueToLatex = function (string $value): string {
        $latex = trim($value);
        $latex = str_replace(['↔', '←'], ['\\leftrightarrow', '\\leftarrow'], $latex);

        return preg_replace_callback(
            '/(?<![A-Za-z])(-?\d+)\s*\/\s*(-?\d+)/',
            fn (array $matches) => '\\frac{' . $matches[1] . '}{' . $matches[2] . '}',
            $latex
        ) ?? $latex;
    };

    $fieldInput = function (
        array $state,
        string $field,
        string $ariaLabel,
        string $sizeClass = 'h-10 w-full'
    ) use ($inputValue, $inputLocked, $inputClass, $operationValueToLatex) {
        $isOperationNotation = $field === 'notasi' || str_ends_with($field, '_notasi');
        $isLocked = $inputLocked($state, $field);
        $value = $inputValue($state, $field);

        if ($isOperationNotation) {
            $fieldId = 'obe-operation-' . $field;
            $hiddenId = 'obe-operation-hidden-' . $field;

            return new \Illuminate\Support\HtmlString(
                '<div class="obe-operation-entry mt-2">'
                . '<math-field'
                . ' id="' . e($fieldId) . '"'
                . ' data-obe-operation-mathfield'
                . ' data-hidden-input="' . e($hiddenId) . '"'
                . ' aria-label="' . e($ariaLabel) . '"'
                . ' virtual-keyboard-mode="onfocus"'
                . ($isLocked ? ' read-only' : '')
                . ' class="obe-operation-mathfield ' . e($inputClass($state, $field)) . '">'
                . e($operationValueToLatex($value))
                . '</math-field>'
                . '<input type="hidden"'
                . ' id="' . e($hiddenId) . '"'
                . ' name="answers[' . e($field) . ']"'
                . ' value="' . e($value) . '">'
                . '</div>'
            );
        }

        return new \Illuminate\Support\HtmlString(
            '<input type="text"'
            . ' name="answers[' . e($field) . ']"'
            . ' value="' . e($value) . '"'
            . ' aria-label="' . e($ariaLabel) . '"'
            . ($isLocked ? ' disabled' : '')
            . ' class="' . e($sizeClass . ' rounded-xl border px-3 text-center font-bold shadow-sm transition focus:outline-none focus:ring-2 ' . $inputClass($state, $field)) . '">'
        );
    };
    $matrix = function (array $state, array $rows, string $ariaPrefix) use ($fieldInput) {
        $columns = count($rows[0] ?? []);
        $html = '<span class="obe-matrix inline-grid items-center gap-x-2 gap-y-2 rounded-md border-x-2 border-slate-900 px-2 py-2 align-middle" style="grid-template-columns:repeat(' . $columns . ', minmax(3.25rem, auto));">';

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $cell) {
                $rhsClass = $columnIndex === $columns - 1
                    ? ' border-l-2 border-l-slate-700 pl-2'
                    : '';

                if (is_array($cell) && isset($cell['field'])) {
                    $html .= '<span class="' . $rhsClass . '">'
                        . $fieldInput(
                            $state,
                            $cell['field'],
                            $ariaPrefix . ', baris ' . ($rowIndex + 1) . ', kolom ' . ($columnIndex + 1),
                            'h-10 w-14'
                        )
                        . '</span>';
                    continue;
                }

                $value = is_array($cell) ? ($cell['value'] ?? '') : $cell;
                $html .= '<span class="flex h-10 min-w-10 items-center justify-center font-semibold text-slate-900' . $rhsClass . '">\\(' . $value . '\\)</span>';
            }
        }

        $html .= '</span>';

        return new \Illuminate\Support\HtmlString($html);
    };

    $simPertukaran = $practiceState('contoh-simulasi-2-2-pertukaran');
    $simPerkalianA = $practiceState('contoh-simulasi-2-2-perkalian-a');
    $simPerkalianB = $practiceState('contoh-simulasi-2-2-perkalian-b');
    $simTambahA = $practiceState('contoh-simulasi-2-2-penjumlahan-a');
    $simTambahB = $practiceState('contoh-simulasi-2-2-penjumlahan-b');
    $aktivitas21 = $practiceState('aktivitas-2-1-obe');

    $practiceModalPayload = session('practice_modal');
    $practiceModal = is_array($practiceModalPayload)
        && in_array(($practiceModalPayload['practice_key'] ?? null), $subbab22PracticeKeys, true)
        ? $practiceModalPayload
        : null;
@endphp

<section class="space-y-10">
    <div class="space-y-4">
        {{-- <h2 class="text-2xl font-black text-slate-950">
            2.2 Jenis-Jenis Operasi Baris Elementer
        </h2> --}}

        <p>
            Untuk memanipulasi baris matriks hingga mencapai bentuk eselon, kita hanya diperbolehkan
            menggunakan tiga jenis operasi dasar.
        </p>
    </div>

    <div class="rounded-2xl border border-cyan-200 bg-cyan-50 p-6">
        <h3 class="text-xl font-black text-slate-950">Tiga Jenis Operasi Baris Elementer</h3>

        <div class="mt-5 grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-cyan-100 bg-white p-4">
                <p class="text-sm font-black text-cyan-700">1. Pertukaran Dua Baris</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">Menukar posisi dua baris pada matriks.</p>
            </div>

            <div class="rounded-2xl border border-cyan-100 bg-white p-4">
                <p class="text-sm font-black text-cyan-700">2. Perkalian Baris</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">Mengalikan seluruh elemen pada satu baris dengan konstanta tak nol.</p>
            </div>

            <div class="rounded-2xl border border-cyan-100 bg-white p-4">
                <p class="text-sm font-black text-cyan-700">3. Penjumlahan Kelipatan Baris</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">Menambahkan kelipatan suatu baris ke baris target.</p>
            </div>
        </div>
    </div>

    <section class="space-y-6">
        <div class="obe-content-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="text-xl font-black text-slate-900">1. Pertukaran Dua Baris</h3>

            <p class="mt-3">
                Operasi ini mengizinkan kita untuk menukar posisi dua baris di dalam matriks.
                Ini sama artinya dengan menukar urutan penulisan persamaan, yang tidak mengubah nilai apa pun.
            </p>

            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5 text-center">
                <p class="text-sm font-bold text-slate-600">Notasi Matematis</p>
                <div class="mt-2 text-2xl text-slate-950">
                    \[
                        B_i \leftrightarrow B_j
                    \]
                </div>
            </div>

            <p class="mt-5">
                Sebelum mengalikan baris dengan pecahan untuk mendapatkan angka 1 utama, perhatikan elemen
                pada kolom yang sama di baris bawahnya. Apabila terdapat elemen bernilai 1, operasi pertukaran
                baris biasanya lebih mudah dan cepat digunakan.
            </p>
        </div>

        <div class="obe-content-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">Contoh Simulasi</p>
                    <h3 class="mt-1 text-xl font-black text-slate-950">Pertukaran Dua Baris</h3>
                </div>

                <span class="w-fit rounded-full px-4 py-2 text-sm font-bold {{ $statusClass($simPertukaran) }}">
                    {{ $statusLabel($simPertukaran, 'Perlu Dikerjakan', 'Simulasi Selesai') }}
                </span>
            </div>

            <p class="mt-4">
                Perhatikan matriks teraugmentasi berikut. Kita ingin memperoleh angka 1 utama pada kolom pertama
                Baris-1 karena elemen pembuka Baris-1 sebelumnya bernilai 0.
            </p>

            <div class="mt-5 overflow-x-auto text-center">
                {!! $matrix($simPertukaran, [
                    ['0', '1', '3', '9'],
                    ['1', '1', '1', '6'],
                    ['2', '1', '-1', '3'],
                ], 'Matriks awal pertukaran dua baris') !!}
            </div>

            <form method="POST" action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'contoh-simulasi-2-2-pertukaran']) }}" class="mt-6">
                @csrf

                <p class="font-semibold text-slate-800">Mari kita terapkan logika perumusan di atas pada matriks ini.</p>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="block text-sm font-bold text-slate-700">
                        Baris yang diawali angka 0 dan harus diturunkan, \(i\)
                        {!! $fieldInput($simPertukaran, 'baris_target', 'Baris yang harus diturunkan') !!}
                    </label>

                    <label class="block text-sm font-bold text-slate-700">
                        Baris pengganti yang diawali angka 1 untuk dinaikkan, \(j\)
                        {!! $fieldInput($simPertukaran, 'baris_pengganti', 'Baris pengganti yang dinaikkan') !!}
                    </label>
                </div>

                <div class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4 text-center text-slate-900">
                    Maka notasi operasi adalah \(\,B_1 \leftrightarrow B_2\,\).
                </div>

                <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <p class="mb-4 text-center text-sm font-black text-slate-700">Hasil matriks setelah operasi</p>
                    <div class="text-center">
                        {!! $matrix($simPertukaran, [
                            [['field' => 'hasil_11'], '1', ['field' => 'hasil_13'], '6'],
                            ['0', ['field' => 'hasil_22'], '3', ['field' => 'hasil_24']],
                            ['2', '1', '-1', '3'],
                        ], 'Hasil pertukaran dua baris') !!}
                    </div>
                </div>

                @if ($hasFeedback($simPertukaran))
                    <p class="mt-4 rounded-xl px-4 py-3 text-sm font-semibold {{ $simPertukaran['completed'] ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-900' }}">
                        {{ $feedbackSummary($simPertukaran) }}
                    </p>
                @endif

                @if (! $simPertukaran['completed'])
                    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs leading-5 text-slate-500">
                            Kesempatan tersisa: {{ max(0, 3 - $simPertukaran['attempts']) }} dari 3. Kolom kosong tidak mengurangi kesempatan.
                        </p>

                        <button type="submit" class="rounded-xl bg-cyan-600 px-5 py-3 text-sm font-black text-white transition hover:bg-cyan-700">
                            Cek Simulasi
                        </button>
                    </div>
                @endif
            </form>
        </div>
    </section>

    <section class="space-y-6">
        <div class="obe-content-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="text-xl font-black text-slate-900">2. Perkalian Baris dengan Konstanta Tak Nol</h3>

            <p class="mt-3">
                Operasi ini berarti kita mengalikan seluruh elemen pada suatu baris \(B_i\) dengan konstanta \(k\).
                Konstanta tersebut dapat berupa bilangan positif, negatif, atau pecahan, tetapi tidak boleh bernilai nol.
                Operasi ini biasanya digunakan untuk menjadikan suatu elemen pembuka menjadi angka 1 utama.
            </p>

            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5 text-center">
                <p class="text-sm font-bold text-slate-600">Notasi Matematis</p>
                <div class="mt-2 text-2xl text-slate-950">
                    \[
                        B_i \leftarrow kB_i, \qquad k \ne 0
                    \]
                </div>
                <p class="mt-3 text-sm text-slate-700">
                    Baris ke-\(i\) yang baru diperoleh dengan mengalikan seluruh elemen Baris ke-\(i\) yang lama dengan konstanta \(k\).
                </p>
            </div>

            <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <h4 class="font-black text-amber-950">Logika Penentuan Rumus Operasi</h4>
                <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm leading-6 text-amber-900">
                    <li><strong>Menentukan Baris Target (\(i\)).</strong> Tentukan baris yang memiliki elemen pembuka tak nol yang ingin dijadikan 1 utama.</li>
                    <li><strong>Menentukan Konstanta Pengali (\(k\)).</strong> Jika angka targetnya \(a\), gunakan kebalikan perkaliannya, yaitu \(\frac{1}{a}\).</li>
                </ol>
            </div>
        </div>

        <div class="grid gap-6 2xl:grid-cols-2">
            <div class="obe-content-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">Contoh Simulasi A</p>
                        <h3 class="mt-1 text-lg font-black text-slate-950">Mengubah 2 Menjadi 1 Utama</h3>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass($simPerkalianA) }}">
                        {{ $statusLabel($simPerkalianA, 'Belum Selesai', 'Selesai') }}
                    </span>
                </div>

                <p class="mt-4 text-sm leading-6 text-slate-700">Perhatikan Baris-2 pada matriks di bawah ini.</p>

                <div class="mt-4 overflow-x-auto text-center">
                    {!! $matrix($simPerkalianA, [
                        ['1', '-2', '3'],
                        ['0', '2', '8'],
                    ], 'Matriks awal perkalian konstanta A') !!}
                </div>

                <p class="mt-4 text-sm leading-6 text-slate-700">
                    Target kita adalah menjadikan angka pembuka di Baris-2 Kolom-2 menjadi nilai 1 utama.
                </p>

                <div class="mt-4 rounded-2xl border border-cyan-200 bg-cyan-50 p-4 text-sm leading-6 text-slate-800">
                    <p>Baris target yang elemennya ingin diubah menjadi 1 utama, \(i\), adalah Baris ke-2.</p>
                    <p class="mt-1">Konstanta pengali pecahan yang dibutuhkan agar target menjadi 1, \(k\), adalah \(\frac{1}{2}\).</p>
                    <p class="mt-2 text-center font-black">Maka notasi operasi adalah \(\,B_2 \leftarrow \frac{1}{2}B_2\,\).</p>
                </div>

                <form method="POST" action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'contoh-simulasi-2-2-perkalian-a']) }}" class="mt-5">
                    @csrf

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-bold text-slate-700">Rincian perhitungan Baris-2 yang baru</p>

                        <div class="obe-calculation-stack mt-4 space-y-3 overflow-x-auto text-center text-sm text-slate-900">
                            <div>\(B_2 \leftarrow \frac{1}{2}B_2\)</div>
                            <div>\(B_2 \leftarrow \frac{1}{2}\) {!! $matrix($simPerkalianA, [[['field' => 'rincian_awal_21'], ['field' => 'rincian_awal_22'], ['field' => 'rincian_awal_23']]], 'Rincian awal perkalian A') !!}</div>
                            <div>\(B_2 \leftarrow\) {!! $matrix($simPerkalianA, [[['field' => 'rincian_hasil_21'], ['field' => 'rincian_hasil_22'], ['field' => 'rincian_hasil_23']]], 'Rincian hasil perkalian A') !!}</div>
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="mb-3 text-sm font-bold text-slate-700">Hasil matriks akhir setelah operasi</p>
                        <div class="text-center">
                            {!! $matrix($simPerkalianA, [
                                ['1', '-2', '3'],
                                [['field' => 'hasil_21'], ['field' => 'hasil_22'], ['field' => 'hasil_23']],
                            ], 'Hasil matriks perkalian A') !!}
                        </div>
                    </div>

                    @if ($hasFeedback($simPerkalianA))
                        <p class="mt-4 rounded-xl px-3 py-2 text-xs font-semibold {{ $simPerkalianA['completed'] ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-900' }}">
                            {{ $feedbackSummary($simPerkalianA) }}
                        </p>
                    @endif

                    @if (! $simPerkalianA['completed'])
                        <button type="submit" class="mt-4 w-full rounded-xl bg-cyan-600 px-4 py-3 text-sm font-black text-white hover:bg-cyan-700">Cek Simulasi</button>
                    @endif
                </form>
            </div>

            <div class="obe-content-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">Contoh Simulasi B</p>
                        <h3 class="mt-1 text-lg font-black text-slate-950">Mengubah Pecahan Menjadi 1 Utama</h3>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass($simPerkalianB) }}">
                        {{ $statusLabel($simPerkalianB, 'Belum Selesai', 'Selesai') }}
                    </span>
                </div>

                <p class="mt-4 text-sm leading-6 text-slate-700">Perhatikan matriks teraugmentasi di bawah ini.</p>

                <div class="mt-4 overflow-x-auto text-center">
                    {!! $matrix($simPerkalianB, [
                        ['1', '2', '5'],
                        ['0', '\frac{2}{3}', '4'],
                    ], 'Matriks awal perkalian konstanta B') !!}
                </div>

                <p class="mt-4 text-sm leading-6 text-slate-700">
                    Target kita adalah mengubah elemen pertama yang bernilai tak nol pada Baris-2 Kolom-2 agar berubah menjadi angka 1 utama.
                </p>

                <div class="mt-4 rounded-2xl border border-cyan-200 bg-cyan-50 p-4 text-sm leading-6 text-slate-800">
                    <p>Baris target yang elemennya ingin diubah menjadi 1 utama, \(i\), adalah Baris ke-2.</p>
                    <p class="mt-1">Konstanta pengali pecahan yang dibutuhkan agar target menjadi 1, \(k\), adalah \(\frac{3}{2}\).</p>
                    <p class="mt-2 text-center font-black">Maka notasi operasi adalah \(\,B_2 \leftarrow \frac{3}{2}B_2\,\).</p>
                </div>

                <form method="POST" action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'contoh-simulasi-2-2-perkalian-b']) }}" class="mt-5">
                    @csrf

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-bold text-slate-700">Rincian perhitungan Baris-2 yang baru</p>

                        <div class="obe-calculation-stack mt-4 space-y-3 overflow-x-auto text-center text-sm text-slate-900">
                            <div>\(B_2 \leftarrow \frac{3}{2}B_2\)</div>
                            <div>\(B_2 \leftarrow \frac{3}{2}\) {!! $matrix($simPerkalianB, [[['field' => 'rincian_awal_21'], ['field' => 'rincian_awal_22'], ['field' => 'rincian_awal_23']]], 'Rincian awal perkalian B') !!}</div>
                            <div>\(B_2 \leftarrow\) {!! $matrix($simPerkalianB, [[['field' => 'rincian_hasil_21'], ['field' => 'rincian_hasil_22'], ['field' => 'rincian_hasil_23']]], 'Rincian hasil perkalian B') !!}</div>
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="mb-3 text-sm font-bold text-slate-700">Hasil matriks akhir setelah operasi</p>
                        <div class="text-center">
                            {!! $matrix($simPerkalianB, [
                                ['1', '2', '5'],
                                [['field' => 'hasil_21'], ['field' => 'hasil_22'], ['field' => 'hasil_23']],
                            ], 'Hasil matriks perkalian B') !!}
                        </div>
                    </div>

                    @if ($hasFeedback($simPerkalianB))
                        <p class="mt-4 rounded-xl px-3 py-2 text-xs font-semibold {{ $simPerkalianB['completed'] ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-900' }}">
                            {{ $feedbackSummary($simPerkalianB) }}
                        </p>
                    @endif

                    @if (! $simPerkalianB['completed'])
                        <button type="submit" class="mt-4 w-full rounded-xl bg-cyan-600 px-4 py-3 text-sm font-black text-white hover:bg-cyan-700">Cek Simulasi</button>
                    @endif
                </form>
            </div>
        </div>
    </section>

    <section class="space-y-6">
        <div class="obe-content-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="text-xl font-black text-slate-900">3. Penjumlahan Baris dengan Kelipatan Baris Lain</h3>

            <p class="mt-3">
                Ini adalah operasi yang paling sering digunakan. Operasi ini dilakukan dengan menambahkan suatu baris
                dengan kelipatan dari baris lain. Tujuannya adalah untuk mengeliminasi atau mengenolkan angka tertentu pada matriks.
            </p>

            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5 text-center">
                <p class="text-sm font-bold text-slate-600">Notasi Matematis</p>
                <div class="mt-2 text-2xl text-slate-950">
                    \[
                        B_i \leftarrow kB_j + B_i
                    \]
                </div>
                <p class="mt-3 text-sm text-slate-700">
                    Baris ke-\(i\) yang baru diperoleh dengan mengalikan konstanta \(k\) dengan Baris ke-\(j\), lalu menambahkan hasilnya ke Baris ke-\(i\) yang lama.
                </p>
            </div>

            <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <h4 class="font-black text-amber-950">Logika Penentuan Rumus Operasi</h4>
                <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm leading-6 text-amber-900">
                    <li><strong>Menentukan Baris Target (\(i\)).</strong> Tentukan baris yang memuat angka yang ingin diubah menjadi 0.</li>
                    <li><strong>Menentukan Baris Acuan/Bantuan (\(j\)).</strong> Gunakan baris yang memiliki angka 1 utama pada kolom yang sama.</li>
                    <li><strong>Menentukan Angka Pengali (\(k\)).</strong> Gunakan nilai lawan dari angka target yang ingin dinolkan.</li>
                </ol>
            </div>
        </div>

        <div class="grid gap-6 2xl:grid-cols-2">
            <div class="obe-content-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">Contoh Simulasi A</p>
                        <h3 class="mt-1 text-lg font-black text-slate-950">Mengenolkan Baris-3 Kolom-1</h3>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass($simTambahA) }}">
                        {{ $statusLabel($simTambahA, 'Belum Selesai', 'Selesai') }}
                    </span>
                </div>

                <p class="mt-4 text-sm leading-6 text-slate-700">Perhatikan matriks berikut. Kita ingin mengubah Baris-3 Kolom-1 menjadi 0.</p>

                <div class="mt-4 overflow-x-auto text-center">
                    {!! $matrix($simTambahA, [
                        ['1', '1', '1', '6'],
                        ['0', '1', '3', '9'],
                        ['2', '1', '-1', '3'],
                    ], 'Matriks awal penjumlahan kelipatan A') !!}
                </div>

                <div class="mt-4 rounded-2xl border border-cyan-200 bg-cyan-50 p-4 text-sm leading-6 text-slate-800">
                    <p>Baris target yang elemennya ingin dinolkan, \(i\), adalah Baris ke-3.</p>
                    <p class="mt-1">Baris acuan yang memiliki angka 1 utama sebagai bantuan, \(j\), adalah Baris ke-1.</p>
                    <p class="mt-1">Konstanta pengali lawan agar target menjadi 0, \(k\), adalah \(-2\).</p>
                    <p class="mt-2 text-center font-black">Maka notasi operasi adalah \(\,B_3 \leftarrow -2B_1 + B_3\,\).</p>
                </div>

                <form method="POST" action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'contoh-simulasi-2-2-penjumlahan-a']) }}" class="mt-5">
                    @csrf

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-bold text-slate-700">Rincian perhitungan Baris-3 yang baru</p>

                        <div class="obe-calculation-stack mt-4 space-y-3 overflow-x-auto text-center text-sm text-slate-900">
                            <div>\(B_3 \leftarrow -2B_1 + B_3\)</div>
                            <div>
                                \(B_3 \leftarrow -2\)
                                {!! $matrix($simTambahA, [['1', '1', '1', '6']], 'Baris acuan penjumlahan A') !!}
                                \(+\)
                                {!! $matrix($simTambahA, [[['field' => 'rincian_target_31'], ['field' => 'rincian_target_32'], ['field' => 'rincian_target_33'], ['field' => 'rincian_target_34']]], 'Baris target awal penjumlahan A') !!}
                            </div>
                            <div>
                                \(B_3 \leftarrow\)
                                {!! $matrix($simTambahA, [[['field' => 'rincian_kali_31'], ['field' => 'rincian_kali_32'], ['field' => 'rincian_kali_33'], ['field' => 'rincian_kali_34']]], 'Hasil kali baris acuan A') !!}
                                \(+\)
                                {!! $matrix($simTambahA, [[['field' => 'rincian_jumlah_target_31'], ['field' => 'rincian_jumlah_target_32'], ['field' => 'rincian_jumlah_target_33'], ['field' => 'rincian_jumlah_target_34']]], 'Baris target untuk penjumlahan A') !!}
                            </div>
                            <div>\(B_3 \leftarrow\) {!! $matrix($simTambahA, [[['field' => 'rincian_hasil_31'], ['field' => 'rincian_hasil_32'], ['field' => 'rincian_hasil_33'], ['field' => 'rincian_hasil_34']]], 'Hasil rincian penjumlahan A') !!}</div>
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="mb-3 text-sm font-bold text-slate-700">Hasil matriks setelah operasi</p>
                        <div class="text-center">
                            {!! $matrix($simTambahA, [
                                ['1', '1', '1', '6'],
                                ['0', '1', '3', '9'],
                                [['field' => 'hasil_31'], ['field' => 'hasil_32'], ['field' => 'hasil_33'], ['field' => 'hasil_34']],
                            ], 'Hasil matriks penjumlahan A') !!}
                        </div>
                    </div>

                    @if ($hasFeedback($simTambahA))
                        <p class="mt-4 rounded-xl px-3 py-2 text-xs font-semibold {{ $simTambahA['completed'] ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-900' }}">
                            {{ $feedbackSummary($simTambahA) }}
                        </p>
                    @endif

                    @if (! $simTambahA['completed'])
                        <button type="submit" class="mt-4 w-full rounded-xl bg-cyan-600 px-4 py-3 text-sm font-black text-white hover:bg-cyan-700">Cek Simulasi</button>
                    @endif
                </form>
            </div>

            <div class="obe-content-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">Contoh Simulasi B</p>
                        <h3 class="mt-1 text-lg font-black text-slate-950">Mengenolkan Baris-2 Kolom-1</h3>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass($simTambahB) }}">
                        {{ $statusLabel($simTambahB, 'Belum Selesai', 'Selesai') }}
                    </span>
                </div>

                <p class="mt-4 text-sm leading-6 text-slate-700">Perhatikan matriks berikut. Kita ingin mengubah elemen pada Baris-2 Kolom-1 menjadi 0.</p>

                <div class="mt-4 overflow-x-auto text-center">
                    {!! $matrix($simTambahB, [
                        ['1', '2', '-1', '4'],
                        ['\frac{1}{2}', '3', '1', '4'],
                        ['0', '1', '3', '5'],
                    ], 'Matriks awal penjumlahan kelipatan B') !!}
                </div>

                <div class="mt-4 rounded-2xl border border-cyan-200 bg-cyan-50 p-4 text-sm leading-6 text-slate-800">
                    <p>Baris target yang elemennya ingin dinolkan, \(i\), adalah Baris ke-2.</p>
                    <p class="mt-1">Baris acuan yang memiliki angka 1 utama sebagai bantuan, \(j\), adalah Baris ke-1.</p>
                    <p class="mt-1">Konstanta pengali lawan agar target menjadi 0, \(k\), adalah \(-\frac{1}{2}\).</p>
                    <p class="mt-2 text-center font-black">Maka notasi operasi adalah \(\,B_2 \leftarrow -\frac{1}{2}B_1 + B_2\,\).</p>
                </div>

                <form method="POST" action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'contoh-simulasi-2-2-penjumlahan-b']) }}" class="mt-5">
                    @csrf

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-bold text-slate-700">Rincian perhitungan Baris-2 yang baru</p>

                        <div class="obe-calculation-stack mt-4 space-y-3 overflow-x-auto text-center text-sm text-slate-900">
                            <div>\(B_2 \leftarrow -\frac{1}{2}B_1 + B_2\)</div>
                            <div>
                                \(B_2 \leftarrow -\frac{1}{2}\)
                                {!! $matrix($simTambahB, [['1', '2', '-1', '4']], 'Baris acuan penjumlahan B') !!}
                                \(+\)
                                {!! $matrix($simTambahB, [[['field' => 'rincian_target_21'], ['field' => 'rincian_target_22'], ['field' => 'rincian_target_23'], ['field' => 'rincian_target_24']]], 'Baris target awal penjumlahan B') !!}
                            </div>
                            <div>
                                \(B_2 \leftarrow\)
                                {!! $matrix($simTambahB, [[['field' => 'rincian_kali_21'], ['field' => 'rincian_kali_22'], ['field' => 'rincian_kali_23'], ['field' => 'rincian_kali_24']]], 'Hasil kali baris acuan B') !!}
                                \(+\)
                                {!! $matrix($simTambahB, [[['field' => 'rincian_jumlah_target_21'], ['field' => 'rincian_jumlah_target_22'], ['field' => 'rincian_jumlah_target_23'], ['field' => 'rincian_jumlah_target_24']]], 'Baris target untuk penjumlahan B') !!}
                            </div>
                            <div>\(B_2 \leftarrow\) {!! $matrix($simTambahB, [[['field' => 'rincian_hasil_21'], ['field' => 'rincian_hasil_22'], ['field' => 'rincian_hasil_23'], ['field' => 'rincian_hasil_24']]], 'Hasil rincian penjumlahan B') !!}</div>
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="mb-3 text-sm font-bold text-slate-700">Hasil matriks setelah operasi</p>
                        <div class="text-center">
                            {!! $matrix($simTambahB, [
                                ['1', '2', '-1', '4'],
                                [['field' => 'hasil_21'], ['field' => 'hasil_22'], ['field' => 'hasil_23'], ['field' => 'hasil_24']],
                                ['0', '1', '3', '5'],
                            ], 'Hasil matriks penjumlahan B') !!}
                        </div>
                    </div>

                    @if ($hasFeedback($simTambahB))
                        <p class="mt-4 rounded-xl px-3 py-2 text-xs font-semibold {{ $simTambahB['completed'] ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-900' }}">
                            {{ $feedbackSummary($simTambahB) }}
                        </p>
                    @endif

                    @if (! $simTambahB['completed'])
                        <button type="submit" class="mt-4 w-full rounded-xl bg-cyan-600 px-4 py-3 text-sm font-black text-white hover:bg-cyan-700">Cek Simulasi</button>
                    @endif
                </form>
            </div>
        </div>
    </section>

    <section class="obe-content-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">Aktivitas 2.1</p>
                <h3 class="mt-1 text-2xl font-black text-slate-950">Latihan Mandiri Operasi Baris Elementer</h3>
                <p class="mt-2 text-slate-600">
                    Terapkan jenis OBE yang sesuai, tentukan komponennya, tuliskan rincian perhitungannya, kemudian isi hasil matriksnya.
                </p>
            </div>

            <span class="w-fit rounded-full px-4 py-2 text-sm font-bold {{ $statusClass($aktivitas21) }}">
                {{ $statusLabel($aktivitas21, 'Perlu Diselesaikan', 'Aktivitas Selesai') }}
            </span>
        </div>

        <form method="POST" action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'aktivitas-2-1-obe']) }}" class="mt-6 space-y-8">
            @csrf

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <h4 class="text-lg font-black text-slate-950">Kasus 1: Pertukaran Dua Baris</h4>
                <p class="mt-3 text-sm leading-6 text-slate-700">Perhatikan matriks teraugmentasi berikut.</p>

                <div class="mt-4 overflow-x-auto text-center">
                    {!! $matrix($aktivitas21, [
                        ['0', '2', '-1', '4'],
                        ['3', '-1', '5', '2'],
                        ['1', '4', '2', '7'],
                    ], 'Matriks kasus 1 aktivitas 2.1') !!}
                </div>

                <p class="mt-4 text-sm leading-6 text-slate-700">
                    Elemen pembuka pada Baris-1 tidak boleh bernilai 0. Gunakan Operasi Baris Elementer untuk
                    menukar baris tersebut dengan baris lain di bawahnya yang sudah diawali oleh angka 1.
                </p>

                <div class="mt-5 grid gap-4 md:grid-cols-3">
                    <label class="block text-sm font-bold text-slate-700">
                        Baris yang diawali angka 0 dan harus diturunkan, \(i\)
                        {!! $fieldInput($aktivitas21, 'k1_i', 'Kasus 1, baris yang diturunkan') !!}
                    </label>
                    <label class="block text-sm font-bold text-slate-700">
                        Baris pengganti yang diawali angka 1 untuk dinaikkan, \(j\)
                        {!! $fieldInput($aktivitas21, 'k1_j', 'Kasus 1, baris yang dinaikkan') !!}
                    </label>
                    <label class="block text-sm font-bold text-slate-700">
                        Notasi operasi
                        {!! $fieldInput($aktivitas21, 'k1_notasi', 'Kasus 1, notasi operasi') !!}
                    </label>
                </div>

                <div class="mt-5 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="mb-3 text-sm font-bold text-slate-700">Hasil matriks setelah operasi</p>
                    <div class="text-center">
                        {!! $matrix($aktivitas21, [
                            [['field' => 'k1_11'], ['field' => 'k1_12'], ['field' => 'k1_13'], ['field' => 'k1_14']],
                            [['field' => 'k1_21'], ['field' => 'k1_22'], ['field' => 'k1_23'], ['field' => 'k1_24']],
                            [['field' => 'k1_31'], ['field' => 'k1_32'], ['field' => 'k1_33'], ['field' => 'k1_34']],
                        ], 'Hasil kasus 1 aktivitas 2.1') !!}
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <h4 class="text-lg font-black text-slate-950">Kasus 2: Perkalian dengan Konstanta Tak Nol</h4>

                <div class="mt-5 grid gap-6 xl:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
                        <p class="font-black text-slate-950">a. Perkalian dengan konstanta negatif</p>

                        <div class="mt-4 overflow-x-auto text-center">
                            {!! $matrix($aktivitas21, [
                                ['1', '3', '-4', '7'],
                                ['0', '-3', '6', '-9'],
                                ['2', '1', '5', '4'],
                            ], 'Matriks kasus 2a aktivitas 2.1') !!}
                        </div>

                        <p class="mt-4 text-sm leading-6 text-slate-700">
                            Gunakan Operasi Baris Elementer agar elemen pertama yang bernilai tak nol pada Baris-2 Kolom-2 berubah menjadi 1 utama.
                        </p>

                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <label class="block text-sm font-bold text-slate-700">Baris target, \(i\)
                                {!! $fieldInput($aktivitas21, 'k2a_i', 'Kasus 2a, baris target') !!}
                            </label>
                            <label class="block text-sm font-bold text-slate-700">Konstanta pengali, \(k\)
                                {!! $fieldInput($aktivitas21, 'k2a_k', 'Kasus 2a, konstanta pengali') !!}
                            </label>
                            <label class="block min-w-0 text-sm font-bold text-slate-700 sm:col-span-3">
    Notasi operasi
    {!! $fieldInput($aktivitas21, 'k2a_notasi', 'Kasus 2a, notasi operasi') !!}
</label>
                        </div>

                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-bold text-slate-700">Rincian perhitungan Baris-2 yang baru</p>
                            <div class="obe-calculation-stack mt-3 space-y-3 overflow-x-auto text-center text-sm">
                                <div>\(B_2 \leftarrow\) {!! $matrix($aktivitas21, [[['field' => 'k2a_rincian_awal_21'], ['field' => 'k2a_rincian_awal_22'], ['field' => 'k2a_rincian_awal_23'], ['field' => 'k2a_rincian_awal_24']]], 'Rincian awal kasus 2a') !!}</div>
                                <div>\(B_2 \leftarrow\) {!! $matrix($aktivitas21, [[['field' => 'k2a_rincian_hasil_21'], ['field' => 'k2a_rincian_hasil_22'], ['field' => 'k2a_rincian_hasil_23'], ['field' => 'k2a_rincian_hasil_24']]], 'Rincian hasil kasus 2a') !!}</div>
                            </div>
                        </div>

                        <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="mb-3 text-sm font-bold text-slate-700">Hasil matriks setelah operasi</p>
                            <div class="text-center">
                                {!! $matrix($aktivitas21, [
                                    [['field' => 'k2a_11'], ['field' => 'k2a_12'], ['field' => 'k2a_13'], ['field' => 'k2a_14']],
                                    [['field' => 'k2a_21'], ['field' => 'k2a_22'], ['field' => 'k2a_23'], ['field' => 'k2a_24']],
                                    [['field' => 'k2a_31'], ['field' => 'k2a_32'], ['field' => 'k2a_33'], ['field' => 'k2a_34']],
                                ], 'Hasil kasus 2a aktivitas 2.1') !!}
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
                        <p class="font-black text-slate-950">b. Perkalian dengan pecahan</p>

                        <div class="mt-4 overflow-x-auto text-center">
                            {!! $matrix($aktivitas21, [
                                ['1', '2', '-1', '5'],
                                ['0', '-\frac{3}{4}', '3', '-6'],
                                ['-2', '1', '4', '8'],
                            ], 'Matriks kasus 2b aktivitas 2.1') !!}
                        </div>

                        <p class="mt-4 text-sm leading-6 text-slate-700">
                            Gunakan Operasi Baris Elementer agar elemen pertama yang bernilai tak nol pada Baris-2 Kolom-2 berubah menjadi 1 utama.
                        </p>

                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <label class="block text-sm font-bold text-slate-700">Baris target, \(i\)
                                {!! $fieldInput($aktivitas21, 'k2b_i', 'Kasus 2b, baris target') !!}
                            </label>
                            <label class="block text-sm font-bold text-slate-700">Konstanta pengali, \(k\)
                                {!! $fieldInput($aktivitas21, 'k2b_k', 'Kasus 2b, konstanta pengali') !!}
                            </label>
                            <label class="block min-w-0 text-sm font-bold text-slate-700 sm:col-span-3">
    Notasi operasi
    {!! $fieldInput($aktivitas21, 'k2b_notasi', 'Kasus 2b, notasi operasi') !!}
</label>
                        </div>

                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-bold text-slate-700">Rincian perhitungan Baris-2 yang baru</p>
                            <div class="obe-calculation-stack mt-3 space-y-3 overflow-x-auto text-center text-sm">
                                <div>\(B_2 \leftarrow\) {!! $matrix($aktivitas21, [[['field' => 'k2b_rincian_awal_21'], ['field' => 'k2b_rincian_awal_22'], ['field' => 'k2b_rincian_awal_23'], ['field' => 'k2b_rincian_awal_24']]], 'Rincian awal kasus 2b') !!}</div>
                                <div>\(B_2 \leftarrow\) {!! $matrix($aktivitas21, [[['field' => 'k2b_rincian_hasil_21'], ['field' => 'k2b_rincian_hasil_22'], ['field' => 'k2b_rincian_hasil_23'], ['field' => 'k2b_rincian_hasil_24']]], 'Rincian hasil kasus 2b') !!}</div>
                            </div>
                        </div>

                        <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="mb-3 text-sm font-bold text-slate-700">Hasil matriks setelah operasi</p>
                            <div class="text-center">
                                {!! $matrix($aktivitas21, [
                                    [['field' => 'k2b_11'], ['field' => 'k2b_12'], ['field' => 'k2b_13'], ['field' => 'k2b_14']],
                                    [['field' => 'k2b_21'], ['field' => 'k2b_22'], ['field' => 'k2b_23'], ['field' => 'k2b_24']],
                                    [['field' => 'k2b_31'], ['field' => 'k2b_32'], ['field' => 'k2b_33'], ['field' => 'k2b_34']],
                                ], 'Hasil kasus 2b aktivitas 2.1') !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <h4 class="text-lg font-black text-slate-950">Kasus 3: Penjumlahan Kelipatan Baris</h4>

                <div class="mt-5 grid gap-6 xl:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
                        <p class="font-black text-slate-950">a. Target bilangan bulat</p>

                        <div class="mt-4 overflow-x-auto text-center">
                            {!! $matrix($aktivitas21, [
                                ['1', '4', '7'],
                                ['3', '-2', '5'],
                            ], 'Matriks kasus 3a aktivitas 2.1') !!}
                        </div>

                        <p class="mt-4 text-sm leading-6 text-slate-700">Gunakan Operasi Baris Elementer untuk mengenolkan Baris-2 Kolom-1.</p>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <label class="block text-sm font-bold text-slate-700">Baris target, \(i\)
                                {!! $fieldInput($aktivitas21, 'k3a_i', 'Kasus 3a, baris target') !!}
                            </label>
                            <label class="block text-sm font-bold text-slate-700">Baris acuan, \(j\)
                                {!! $fieldInput($aktivitas21, 'k3a_j', 'Kasus 3a, baris acuan') !!}
                            </label>
                            <label class="block text-sm font-bold text-slate-700">Konstanta pengali lawan, \(k\)
                                {!! $fieldInput($aktivitas21, 'k3a_k', 'Kasus 3a, konstanta pengali') !!}
                            </label>
                            <label class="block text-sm font-bold text-slate-700">Notasi operasi
                                {!! $fieldInput($aktivitas21, 'k3a_notasi', 'Kasus 3a, notasi operasi') !!}
                            </label>
                        </div>

                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-bold text-slate-700">Rincian perhitungan Baris-2 yang baru</p>
                            <div class="obe-calculation-stack mt-3 space-y-3 overflow-x-auto text-center text-sm">
                                <div>\(B_2 \leftarrow\) {!! $matrix($aktivitas21, [[['field' => 'k3a_rincian_acuan_11'], ['field' => 'k3a_rincian_acuan_12'], ['field' => 'k3a_rincian_acuan_13']]], 'Baris acuan kasus 3a') !!} \(+\) {!! $matrix($aktivitas21, [[['field' => 'k3a_rincian_target_21'], ['field' => 'k3a_rincian_target_22'], ['field' => 'k3a_rincian_target_23']]], 'Baris target awal kasus 3a') !!}</div>
                                <div>\(B_2 \leftarrow\) {!! $matrix($aktivitas21, [[['field' => 'k3a_rincian_kali_21'], ['field' => 'k3a_rincian_kali_22'], ['field' => 'k3a_rincian_kali_23']]], 'Hasil kali kasus 3a') !!} \(+\) {!! $matrix($aktivitas21, [[['field' => 'k3a_rincian_jumlah_target_21'], ['field' => 'k3a_rincian_jumlah_target_22'], ['field' => 'k3a_rincian_jumlah_target_23']]], 'Baris target penjumlahan kasus 3a') !!}</div>
                                <div>\(B_2 \leftarrow\) {!! $matrix($aktivitas21, [[['field' => 'k3a_rincian_hasil_21'], ['field' => 'k3a_rincian_hasil_22'], ['field' => 'k3a_rincian_hasil_23']]], 'Hasil rincian kasus 3a') !!}</div>
                            </div>
                        </div>

                        <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="mb-3 text-sm font-bold text-slate-700">Hasil matriks setelah operasi</p>
                            <div class="text-center">
                                {!! $matrix($aktivitas21, [
                                    [['field' => 'k3a_11'], ['field' => 'k3a_12'], ['field' => 'k3a_13']],
                                    [['field' => 'k3a_21'], ['field' => 'k3a_22'], ['field' => 'k3a_23']],
                                ], 'Hasil kasus 3a aktivitas 2.1') !!}
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
                        <p class="font-black text-slate-950">b. Target pecahan</p>

                        <div class="mt-4 overflow-x-auto text-center">
                            {!! $matrix($aktivitas21, [
                                ['1', '6', '-3'],
                                ['-\frac{2}{3}', '5', '4'],
                            ], 'Matriks kasus 3b aktivitas 2.1') !!}
                        </div>

                        <p class="mt-4 text-sm leading-6 text-slate-700">Gunakan Operasi Baris Elementer untuk mengenolkan Baris-2 Kolom-1.</p>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <label class="block text-sm font-bold text-slate-700">Baris target, \(i\)
                                {!! $fieldInput($aktivitas21, 'k3b_i', 'Kasus 3b, baris target') !!}
                            </label>
                            <label class="block text-sm font-bold text-slate-700">Baris acuan, \(j\)
                                {!! $fieldInput($aktivitas21, 'k3b_j', 'Kasus 3b, baris acuan') !!}
                            </label>
                            <label class="block text-sm font-bold text-slate-700">Konstanta pengali lawan, \(k\)
                                {!! $fieldInput($aktivitas21, 'k3b_k', 'Kasus 3b, konstanta pengali') !!}
                            </label>
                            <label class="block text-sm font-bold text-slate-700">Notasi operasi
                                {!! $fieldInput($aktivitas21, 'k3b_notasi', 'Kasus 3b, notasi operasi') !!}
                            </label>
                        </div>

                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-bold text-slate-700">Rincian perhitungan Baris-2 yang baru</p>
                            <div class="obe-calculation-stack mt-3 space-y-3 overflow-x-auto text-center text-sm">
                                <div>\(B_2 \leftarrow\) {!! $matrix($aktivitas21, [[['field' => 'k3b_rincian_acuan_11'], ['field' => 'k3b_rincian_acuan_12'], ['field' => 'k3b_rincian_acuan_13']]], 'Baris acuan kasus 3b') !!} \(+\) {!! $matrix($aktivitas21, [[['field' => 'k3b_rincian_target_21'], ['field' => 'k3b_rincian_target_22'], ['field' => 'k3b_rincian_target_23']]], 'Baris target awal kasus 3b') !!}</div>
                                <div>\(B_2 \leftarrow\) {!! $matrix($aktivitas21, [[['field' => 'k3b_rincian_kali_21'], ['field' => 'k3b_rincian_kali_22'], ['field' => 'k3b_rincian_kali_23']]], 'Hasil kali kasus 3b') !!} \(+\) {!! $matrix($aktivitas21, [[['field' => 'k3b_rincian_jumlah_target_21'], ['field' => 'k3b_rincian_jumlah_target_22'], ['field' => 'k3b_rincian_jumlah_target_23']]], 'Baris target penjumlahan kasus 3b') !!}</div>
                                <div>\(B_2 \leftarrow\) {!! $matrix($aktivitas21, [[['field' => 'k3b_rincian_hasil_21'], ['field' => 'k3b_rincian_hasil_22'], ['field' => 'k3b_rincian_hasil_23']]], 'Hasil rincian kasus 3b') !!}</div>
                            </div>
                        </div>

                        <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="mb-3 text-sm font-bold text-slate-700">Hasil matriks setelah operasi</p>
                            <div class="text-center">
                                {!! $matrix($aktivitas21, [
                                    [['field' => 'k3b_11'], ['field' => 'k3b_12'], ['field' => 'k3b_13']],
                                    [['field' => 'k3b_21'], ['field' => 'k3b_22'], ['field' => 'k3b_23']],
                                ], 'Hasil kasus 3b aktivitas 2.1') !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($hasFeedback($aktivitas21))
                <p class="rounded-xl px-4 py-3 text-sm font-semibold {{ $aktivitas21['completed'] ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-900' }}">
                    {{ $feedbackSummary($aktivitas21) }}
                </p>
            @endif

            @if (! $aktivitas21['completed'])
                <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs leading-5 text-slate-500">
                        Kesempatan tersisa: {{ max(0, 3 - $aktivitas21['attempts']) }} dari 3. Aktivitas selesai apabila seluruh kasus sudah benar.
                    </p>

                    <button type="submit" class="rounded-xl bg-cyan-600 px-5 py-3 text-sm font-black text-white transition hover:bg-cyan-700">
                        Cek Aktivitas 2.1
                    </button>
                </div>
            @endif
        </form>
    </section>

    <div class="rounded-2xl border border-cyan-200 bg-cyan-50 p-5">
        <h3 class="text-base font-black text-cyan-950">Catatan Penting</h3>
        <p class="mt-2 text-sm leading-7 text-cyan-900">
            Pada setiap OBE, seluruh elemen pada baris yang dikenai operasi harus diproses secara konsisten,
            termasuk elemen konstanta pada ruas kanan matriks teraugmentasi.
        </p>
    </div>
</section>

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
        aria-labelledby="subbab22-modal-title">

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
                        <p id="subbab22-modal-title" class="text-lg font-bold text-slate-900">
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

<script>
    (function () {
        function latexToStoredValue(latex) {
            let value = String(latex || '');

            value = value
                .replace(/\\leftrightarrow/g, '↔')
                .replace(/\\longleftrightarrow/g, '↔')
                .replace(/\\leftarrow|\\gets|\\longleftarrow/g, '←');

            let previousValue;

            do {
                previousValue = value;
                value = value.replace(/\\(?:d?frac)\{([^{}]*)\}\{([^{}]*)\}/g, '$1/$2');
            } while (value !== previousValue);

            return value
                .replace(/\\left|\\right/g, '')
                .replace(/\\cdot/g, '')
                .replace(/\\,/g, ' ')
                .replace(/[{}]/g, '')
                .replace(/\\/g, '')
                .replace(/\s+/g, '')
                .trim();
        }

        function initialiseMathLiveOperations() {
            document.querySelectorAll('[data-obe-operation-mathfield]').forEach(function (mathField) {
                if (mathField.dataset.obeInitialised === 'true') {
                    return;
                }

                const hiddenInput = document.getElementById(mathField.dataset.hiddenInput);

                if (! hiddenInput) {
                    return;
                }

                if (! mathField.value && mathField.textContent.trim() !== '') {
                    mathField.value = mathField.textContent.trim();
                }

                const syncHiddenValue = function () {
                    hiddenInput.value = latexToStoredValue(mathField.getValue('latex'));
                    hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                };

                mathField.addEventListener('input', syncHiddenValue);
                mathField.addEventListener('change', syncHiddenValue);
                mathField.dataset.obeInitialised = 'true';
                syncHiddenValue();
            });
        }

        function bootMathLiveOperations() {
            if (window.customElements && customElements.get('math-field')) {
                initialiseMathLiveOperations();
                return;
            }

            if (window.customElements) {
                customElements.whenDefined('math-field').then(initialiseMathLiveOperations);
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootMathLiveOperations, { once: true });
        } else {
            bootMathLiveOperations();
        }
        // SUBBAB_2_2_OBE_POPUP_SCROLL_FIX_V5
        // Simpan posisi scroll sebelum form latihan dikirim. Setelah redirect dari
        // controller, posisi yang sama dipulihkan sehingga popup tidak membawa
        // mahasiswa kembali ke bagian atas halaman.
        const practiceScrollStorageKey = 'ruangobe:subbab22:practice-scroll-position';

        function rememberPracticeScrollPosition() {
            try {
                window.sessionStorage.setItem(
                    practiceScrollStorageKey,
                    JSON.stringify({
                        path: window.location.pathname,
                        y: Math.max(0, window.scrollY || window.pageYOffset || 0),
                        createdAt: Date.now(),
                    })
                );
            } catch (error) {
                // Penyimpanan browser dapat dibatasi. Form tetap dapat dikirim.
            }
        }

        function restorePracticeScrollPosition() {
            let rawValue = null;

            try {
                rawValue = window.sessionStorage.getItem(practiceScrollStorageKey);
            } catch (error) {
                return;
            }

            if (! rawValue) {
                return;
            }

            let storedPosition = null;

            try {
                storedPosition = JSON.parse(rawValue);
            } catch (error) {
                window.sessionStorage.removeItem(practiceScrollStorageKey);
                return;
            }

            const isSamePage = storedPosition && storedPosition.path === window.location.pathname;
            const isRecent = storedPosition && (Date.now() - Number(storedPosition.createdAt || 0)) < 120000;
            const targetY = Math.max(0, Number(storedPosition?.y || 0));

            if (! isSamePage || ! isRecent) {
                window.sessionStorage.removeItem(practiceScrollStorageKey);
                return;
            }

            const scrollToSavedPosition = function () {
                window.scrollTo({
                    top: targetY,
                    left: 0,
                    behavior: 'auto',
                });
            };

            // Pemulihan berulang memastikan tinggi konten dan popup sudah selesai
            // dirender sebelum posisi halaman dipastikan kembali ke lokasi semula.
            requestAnimationFrame(scrollToSavedPosition);
            window.setTimeout(scrollToSavedPosition, 80);
            window.setTimeout(scrollToSavedPosition, 240);

            window.setTimeout(function () {
                try {
                    window.sessionStorage.removeItem(practiceScrollStorageKey);
                } catch (error) {
                    // Tidak perlu tindakan tambahan.
                }
            }, 300);
        }

        function bindPracticeScrollPersistence() {
            document.querySelectorAll('form').forEach(function (form) {
                const hasPracticeAnswers = form.querySelector('[name^="answers["]') !== null;

                if (! hasPracticeAnswers || form.dataset.practiceScrollBound === 'true') {
                    return;
                }

                form.addEventListener('submit', rememberPracticeScrollPosition);
                form.dataset.practiceScrollBound = 'true';
            });
        }

        if ('scrollRestoration' in window.history) {
            window.history.scrollRestoration = 'manual';
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                bindPracticeScrollPersistence();
                restorePracticeScrollPosition();
            }, { once: true });
        } else {
            bindPracticeScrollPersistence();
            restorePracticeScrollPosition();
        }

    })();
</script>




