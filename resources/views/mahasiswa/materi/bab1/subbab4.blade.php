@php
    $practiceModalPayload = session('practice_modal');
    $practiceModalPayload = is_array($practiceModalPayload) ? $practiceModalPayload : null;

    $practiceUsesComponentAttemptScope = function ($submission): bool {
        $raw = is_array($submission?->feedback) ? $submission->feedback : [];

        return ($raw['_meta']['attempt_scope'] ?? null) === 'component';
    };

    $practiceContext = function (string $practiceKey) use ($practiceSubmissions, $practiceUsesComponentAttemptScope): array {
        $submission = $practiceSubmissions->get($practiceKey);
        $rawFeedback = is_array($submission?->feedback) ? $submission->feedback : [];

        $storedAnswers = $practiceUsesComponentAttemptScope($submission) && is_array($submission?->answers)
            ? $submission->answers
            : [];

        $oldAnswers = old('answers', []);
        $oldAnswers = is_array($oldAnswers) ? $oldAnswers : [];

        $feedback = $practiceUsesComponentAttemptScope($submission)
            ? (is_array($rawFeedback['fields'] ?? null)
                ? $rawFeedback['fields']
                : collect($rawFeedback)
                    ->except(['_meta', 'groups', 'fields'])
                    ->filter(fn ($item) => is_array($item))
                    ->all())
            : [];

        $meta = $practiceUsesComponentAttemptScope($submission) && is_array($rawFeedback['_meta'] ?? null)
            ? $rawFeedback['_meta']
            : [];

        $groups = is_array($meta['groups'] ?? null) ? $meta['groups'] : [];
        $isCompleted = $practiceUsesComponentAttemptScope($submission) && (bool) ($submission?->is_completed ?? false);

        return [
            'submission' => $submission,
            'answers' => array_replace($storedAnswers, $oldAnswers),
            'feedback' => $feedback,
            'meta' => $meta,
            'groups' => $groups,
            'is_completed' => $isCompleted,
            'with_help' => ($meta['completion_mode'] ?? null) === 'bantuan',
        ];
    };

    $simPerhitungan = $practiceContext('contoh-simulasi-1-4-perhitungan');
    $cekMetode = $practiceContext('cek-pemahaman-1-4-metode');
    $simMatriksA = $practiceContext('contoh-simulasi-1-4-matriks-a');
    $simAxb = $practiceContext('contoh-simulasi-1-4-ax-b');
    $cekAxb = $practiceContext('cek-pemahaman-1-4-ax-b');
    $simAugmented = $practiceContext('contoh-simulasi-1-4-augmented');
    $cekTerjemahan = $practiceContext('cek-pemahaman-1-4-terjemahan-matriks');

    // Ketiga studi kasus Aktivitas 1.4 diperiksa dalam satu kali pengumpulan.
    $aktivitas14 = $practiceContext('aktivitas-1-4-matriks');

    $inputClass = function (array $context, string $fieldKey): string {
        $state = $context['feedback'][$fieldKey]['state'] ?? null;

        return match ($state) {
            'correct' => 'border-green-500 bg-green-50 focus:border-green-500 focus:ring-green-500',
            'revealed' => 'border-indigo-400 bg-indigo-50 text-indigo-950 focus:border-indigo-500 focus:ring-indigo-500',
            'wrong' => 'border-red-500 bg-red-50 focus:border-red-500 focus:ring-red-500',
            'empty' => 'border-yellow-400 bg-yellow-50 focus:border-yellow-500 focus:ring-yellow-500',
            default => 'border-slate-300 bg-white focus:border-cyan-500 focus:ring-cyan-500',
        };
    };

    $inputLocked = function (array $context, string $fieldKey): bool {
        $field = $context['feedback'][$fieldKey] ?? [];

        return ! empty($field['is_revealed'])
            || (! empty($field['is_correct']) && ($field['state'] ?? null) === 'correct');
    };

    $inputValue = fn (array $context, string $fieldKey) => $context['answers'][$fieldKey] ?? '';

    $checkboxClass = function (array $context, string $fieldKey, string $optionKey): string {
        $state = $context['feedback'][$fieldKey]['option_statuses'][$optionKey]['state'] ?? 'neutral';

        return match ($state) {
            'correct' => 'border-green-500 bg-green-50',
            'revealed' => 'border-indigo-400 bg-indigo-50',
            'wrong' => 'border-red-500 bg-red-50',
            default => 'border-slate-200 bg-white hover:bg-cyan-50',
        };
    };

    $selectedOptions = fn (array $context, string $fieldKey): array => is_array($context['answers'][$fieldKey] ?? null)
        ? $context['answers'][$fieldKey]
        : [];

    $attemptInfo = function (array $context): array {
        $max = max(1, (int) ($context['meta']['max_attempts'] ?? 3));
        $used = max(0, min($max, (int) ($context['meta']['attempts'] ?? 0)));

        return [
            'max' => $max,
            'used' => $used,
            'remaining' => max(0, $max - $used),
            'show' => ! $context['is_completed'],
        ];
    };

    $badge = function (array $context, string $activeLabel, string $completeLabel): array {
        if (! $context['is_completed']) {
            return ['bg-yellow-100 text-yellow-800', $activeLabel];
        }

        if ($context['with_help']) {
            return ['bg-indigo-100 text-indigo-700', 'Selesai dengan Bantuan'];
        }

        return ['bg-green-100 text-green-700', $completeLabel];
    };

    $simPerhitunganAttempt = $attemptInfo($simPerhitungan);
    $cekMetodeAttempt = $attemptInfo($cekMetode);
    $simMatriksAAttempt = $attemptInfo($simMatriksA);
    $simAxbAttempt = $attemptInfo($simAxb);
    $cekAxbAttempt = $attemptInfo($cekAxb);
    $simAugmentedAttempt = $attemptInfo($simAugmented);
    $cekTerjemahanAttempt = $attemptInfo($cekTerjemahan);
    $aktivitas14Attempt = $attemptInfo($aktivitas14);

    $simPerhitunganBadge = $badge($simPerhitungan, 'Perlu Diselesaikan', 'Contoh Simulasi Selesai');
    $cekMetodeBadge = $badge($cekMetode, 'Cek Pemahaman', 'Cek Pemahaman Selesai');
    $simMatriksABadge = $badge($simMatriksA, 'Perlu Diselesaikan', 'Contoh Simulasi Selesai');
    $simAxbBadge = $badge($simAxb, 'Perlu Diselesaikan', 'Contoh Simulasi Selesai');
    $cekAxbBadge = $badge($cekAxb, 'Cek Pemahaman', 'Cek Pemahaman Selesai');
    $simAugmentedBadge = $badge($simAugmented, 'Perlu Diselesaikan', 'Contoh Simulasi Selesai');
    $cekTerjemahanBadge = $badge($cekTerjemahan, 'Cek Pemahaman', 'Cek Pemahaman Selesai');
    $aktivitas14Badge = $badge($aktivitas14, 'Perlu Diselesaikan', 'Aktivitas Selesai');

    $matrixAFields = [
        ['ma11', 'ma12', 'ma13'],
        ['ma21', 'ma22', 'ma23'],
        ['ma31', 'ma32', 'ma33'],
    ];

    $augmentedFields = [
        ['aug11', 'aug12', 'aug13', 'aug14'],
        ['aug21', 'aug22', 'aug23', 'aug24'],
        ['aug31', 'aug32', 'aug33', 'aug34'],
    ];

    $gameAFields = [
        ['game_a11', 'game_a12', 'game_a13'],
        ['game_a21', 'game_a22', 'game_a23'],
        ['game_a31', 'game_a32', 'game_a33'],
    ];

    $cloudFields = [
        ['cloud_a11', 'cloud_a12', 'cloud_a13', 'cloud_b1'],
        ['cloud_a21', 'cloud_a22', 'cloud_a23', 'cloud_b2'],
        ['cloud_a31', 'cloud_a32', 'cloud_a33', 'cloud_b3'],
    ];

    $debugFields = [
        ['debug_a11', 'debug_a12', 'debug_a13', 'debug_b1'],
        ['debug_a21', 'debug_a22', 'debug_a23', 'debug_b2'],
        ['debug_a31', 'debug_a32', 'debug_a33', 'debug_b3'],
    ];

    $supportedPracticeKeys = [
        'contoh-simulasi-1-4-perhitungan',
        'cek-pemahaman-1-4-metode',
        'contoh-simulasi-1-4-matriks-a',
        'contoh-simulasi-1-4-ax-b',
        'cek-pemahaman-1-4-ax-b',
        'contoh-simulasi-1-4-augmented',
        'cek-pemahaman-1-4-terjemahan-matriks',
        'aktivitas-1-4-matriks',
    ];

    $practiceModal = in_array($practiceModalPayload['practice_key'] ?? null, $supportedPracticeKeys, true)
        ? $practiceModalPayload
        : null;
@endphp

<section class="space-y-8">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        {{-- <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">
            1.4 METODE PENYELESAIAN SPL MENUJU REPRESENTASI MATRIKS
        </p> --}}

        <p class="mt-4 leading-7 text-slate-700">
            Sebelum mengeksplorasi teknik komputasi yang lebih kompleks, mari kita tinjau kembali pengetahuan fundamental mengenai pencarian himpunan penyelesaian (solusi) dari suatu Sistem Persamaan Linear (SPL). Secara analitis, untuk menyelesaikan sistem berskala kecil, terdapat dua pendekatan konvensional yang umum digunakan, yaitu:
        </p>

        <ol class="mt-5 list-decimal space-y-3 pl-6 leading-7 text-slate-700">
            <li>
                <span class="font-semibold">Metode Eliminasi:</span>
                Mengurangkan atau menjumlahkan persamaan-persamaan linear secara bersusun untuk mengeliminasi (menghilangkan) salah satu variabel.
            </li>
            <li>
                <span class="font-semibold">Metode Substitusi:</span>
                Menggantikan suatu variabel dengan nilai pengganti yang diperoleh dari persamaan lain.
            </li>
            <li>
                <span class="font-semibold">Metode Gabungan (Eliminasi-Substitusi):</span>
                Mengkombinasikan kedua metode sebelumnya, yakni mengeliminasi variabel pada tahap awal, kemudian mensubstitusikan nilai yang ditemukan ke dalam persamaan untuk mencari variabel lainnya.
            </li>
        </ol>

        <p class="mt-5 leading-7 text-slate-700">
            mari kita evaluasi penerapan metode gabungan tersebut pada sebuah kasus sistem persamaan.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-xl font-bold text-slate-950">
            1. Penyelesaian SPL Skala Kecil
        </h3>

        <p class="mt-4 leading-7 text-slate-700">
            Perhatikan Sistem Persamaan Linear dengan tiga variabel \((x, y, \text{ dan } z)\) berikut:
        </p>

        <div class="mt-5 overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-5 text-center">
            <div class="min-w-[260px] text-lg text-slate-950">
                \[
                    \begin{aligned}
                        3x + 2y + z &= 7 && \text{(Persamaan 1)} \\
                        x - y - z &= 2 && \text{(Persamaan 2)} \\
                        x + 3y + z &= 4 && \text{(Persamaan 3)}
                    \end{aligned}
                \]
            </div>
        </div>

        <p class="mt-6 leading-7 text-slate-700">
            Untuk menemukan titik solusi dari sistem di atas, serangkaian perhitungan bersusun (menggunakan metode gabungan) harus dilakukan:
        </p>

        <div class="mt-5 space-y-5">
            <article class="rounded-2xl border border-cyan-200 bg-cyan-50 p-5">
                <h4 class="font-bold text-cyan-950">
                    Langkah 1 (Eliminasi): Mengeliminasi variabel \(z\) dengan menjumlahkan Persamaan 1 dan Persamaan 2.
                </h4>

                <div class="mt-4 overflow-x-auto rounded-xl border border-cyan-200 bg-white p-4 text-center">
                    \[
                        \begin{aligned}
                            3x + 2y + z &= 7 \\
                            x - y - z &= 2 \\
                            \hline
                            4x + y &= 9 && \text{(Persamaan 4)}
                        \end{aligned}
                    \]
                </div>
            </article>

            <article class="rounded-2xl border border-cyan-200 bg-cyan-50 p-5">
                <h4 class="font-bold text-cyan-950">
                    Langkah 2 (Eliminasi): Mengeliminasi kembali variabel \(z\) dengan menjumlahkan Persamaan 2 dan Persamaan 3.
                </h4>

                <div class="mt-4 overflow-x-auto rounded-xl border border-cyan-200 bg-white p-4 text-center">
                    \[
                        \begin{aligned}
                            x - y - z &= 2 \\
                            x + 3y + z &= 4 \\
                            \hline
                            2x + 2y &= 6
                        \end{aligned}
                    \]
                </div>

                <p class="mt-4 text-sm leading-6 text-cyan-900">
                    (Sederhanakan persamaan dengan membagi kedua ruas dengan angka 2)
                </p>

                <div class="mt-3 rounded-xl border border-cyan-200 bg-white p-4 text-center">
                    \[
                        x + y = 3 \quad \text{(Persamaan 5)}
                    \]
                </div>
            </article>
        </div>
    </div>

    <div class="overflow-hidden rounded-[1.75rem] border border-cyan-200 bg-cyan-50 shadow-sm">
        <div class="border-b border-cyan-200 bg-gradient-to-br from-cyan-100 via-white to-cyan-50 px-5 py-6 sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-cyan-700">
                        Contoh Simulasi
                    </p>

                    <h3 class="mt-1 text-xl font-bold text-slate-950">
                        Penyelesaian SPL Skala Kecil
                    </h3>
                </div>

                <span class="w-fit shrink-0 rounded-full px-3.5 py-2 text-xs font-bold {{ $simPerhitunganBadge[0] }}">
                    {{ $simPerhitunganBadge[1] }}
                </span>
            </div>

            @if ($simPerhitunganAttempt['show'])
                <div class="mt-5 rounded-2xl border border-cyan-200 bg-white/80 px-4 py-3">
                    <p class="text-sm font-bold text-cyan-950">
                        Kesempatan tersisa: {{ $simPerhitunganAttempt['remaining'] }} dari {{ $simPerhitunganAttempt['max'] }}
                    </p>

                    <p class="mt-1 text-xs leading-5 text-cyan-800">
                        Berlaku untuk seluruh Contoh Simulasi. Kolom yang belum diisi tidak mengurangi kesempatan.
                    </p>
                </div>
            @endif
        </div>

        <form action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'contoh-simulasi-1-4-perhitungan']) }}" method="POST" data-preserve-scroll="true" class="space-y-6 p-5 sm:p-6">
            @csrf

            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <p class="font-bold text-slate-950">
                    Langkah 3 (Eliminasi Interaktif): Sekarang, giliran Anda! Eliminasi variabel \(y\) menggunakan hasil persamaan baru (Persamaan 4 dan Persamaan 5). Isikan setiap bagian bertanda titik-titik sesuai dengan langkah pada modul.
                </p>

                <div class="mt-5 overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="mx-auto min-w-[330px] space-y-3 text-center text-lg text-slate-950">
                        <div>\(4x + y = 9\)</div>
                        <div>\(x + y = 3\)</div>
                        <div class="mx-auto w-64 border-t-2 border-slate-500"></div>

                        <div class="flex items-center justify-center gap-2">
                            <input
                                type="text"
                                name="answers[sim_l3_ruas_kiri]"
                                value="{{ old('answers.sim_l3_ruas_kiri', $inputValue($simPerhitungan, 'sim_l3_ruas_kiri')) }}"
                                aria-label="Ruas kiri hasil eliminasi"
                                class="h-10 w-20 rounded-xl border px-2 text-center text-base font-semibold text-slate-900 {{ $inputClass($simPerhitungan, 'sim_l3_ruas_kiri') }}"
                                @if ($inputLocked($simPerhitungan, 'sim_l3_ruas_kiri')) readonly @endif>

                            <span>\(=\)</span>

                            <input
                                type="text"
                                name="answers[sim_l3_ruas_kanan]"
                                value="{{ old('answers.sim_l3_ruas_kanan', $inputValue($simPerhitungan, 'sim_l3_ruas_kanan')) }}"
                                aria-label="Ruas kanan hasil eliminasi"
                                class="h-10 w-16 rounded-xl border px-2 text-center text-base font-semibold text-slate-900 {{ $inputClass($simPerhitungan, 'sim_l3_ruas_kanan') }}"
                                @if ($inputLocked($simPerhitungan, 'sim_l3_ruas_kanan')) readonly @endif>
                        </div>

                        <div class="flex items-center justify-center gap-2">
                            <span>\(x =\)</span>

                            <input
                                type="text"
                                name="answers[sim_l3_nilai_x]"
                                value="{{ old('answers.sim_l3_nilai_x', $inputValue($simPerhitungan, 'sim_l3_nilai_x')) }}"
                                aria-label="Nilai x"
                                class="h-10 w-16 rounded-xl border px-2 text-center text-base font-semibold text-slate-900 {{ $inputClass($simPerhitungan, 'sim_l3_nilai_x') }}"
                                @if ($inputLocked($simPerhitungan, 'sim_l3_nilai_x')) readonly @endif>
                        </div>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    @foreach (['sim_l3_ruas_kiri', 'sim_l3_ruas_kanan', 'sim_l3_nilai_x'] as $fieldKey)
                        <x-practice-field-feedback :feedback="$simPerhitungan['feedback'][$fieldKey] ?? []" />
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <p class="font-bold text-slate-950">
                    Langkah 4 (Substitusi Interaktif): Setelah nilai \(x\) diketahui, lakukan substitusi mundur untuk menemukan nilai \(y\) dan \(z\). Isikan setiap bagian bertanda titik-titik sesuai urutan perhitungan pada modul.
                </p>

                <div class="mt-5 grid gap-5 lg:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <p class="text-sm font-bold text-slate-800">
                            Pertama, substitusikan nilai \(x\) ke Persamaan 5.
                        </p>

                        <div class="mt-4 space-y-3 text-center text-lg text-slate-950">
                            <div>\(x + y = 3\)</div>

                            <div class="flex items-center justify-center gap-2">
                                <input
                                    type="text"
                                    name="answers[sim_l4_y_sub_x]"
                                    value="{{ old('answers.sim_l4_y_sub_x', $inputValue($simPerhitungan, 'sim_l4_y_sub_x')) }}"
                                    aria-label="Nilai x yang disubstitusikan untuk mencari y"
                                    class="h-10 w-16 rounded-xl border px-2 text-center text-base font-semibold text-slate-900 {{ $inputClass($simPerhitungan, 'sim_l4_y_sub_x') }}"
                                    @if ($inputLocked($simPerhitungan, 'sim_l4_y_sub_x')) readonly @endif>

                                <span>\(+ y = 3\)</span>
                            </div>

                            <div class="flex items-center justify-center gap-2">
                                <span>\(y = 3 -\)</span>

                                <input
                                    type="text"
                                    name="answers[sim_l4_y_pengurang]"
                                    value="{{ old('answers.sim_l4_y_pengurang', $inputValue($simPerhitungan, 'sim_l4_y_pengurang')) }}"
                                    aria-label="Nilai pengurang untuk mencari y"
                                    class="h-10 w-16 rounded-xl border px-2 text-center text-base font-semibold text-slate-900 {{ $inputClass($simPerhitungan, 'sim_l4_y_pengurang') }}"
                                    @if ($inputLocked($simPerhitungan, 'sim_l4_y_pengurang')) readonly @endif>
                            </div>

                            <div class="flex items-center justify-center gap-2">
                                <span>\(y =\)</span>

                                <input
                                    type="text"
                                    name="answers[sim_l4_nilai_y]"
                                    value="{{ old('answers.sim_l4_nilai_y', $inputValue($simPerhitungan, 'sim_l4_nilai_y')) }}"
                                    aria-label="Nilai y"
                                    class="h-10 w-16 rounded-xl border px-2 text-center text-base font-semibold text-slate-900 {{ $inputClass($simPerhitungan, 'sim_l4_nilai_y') }}"
                                    @if ($inputLocked($simPerhitungan, 'sim_l4_nilai_y')) readonly @endif>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <p class="text-sm font-bold text-slate-800">
                            Kedua, substitusikan nilai \(x\) dan \(y\) ke Persamaan 2 untuk mencari \(z\).
                        </p>

                        <div class="mt-4 space-y-3 text-center text-lg text-slate-950">
                            <div>\(x - y - z = 2\)</div>

                            <div class="flex items-center justify-center gap-2">
                                <input
                                    type="text"
                                    name="answers[sim_l4_z_sub_x]"
                                    value="{{ old('answers.sim_l4_z_sub_x', $inputValue($simPerhitungan, 'sim_l4_z_sub_x')) }}"
                                    aria-label="Nilai x yang disubstitusikan untuk mencari z"
                                    class="h-10 w-16 rounded-xl border px-2 text-center text-base font-semibold text-slate-900 {{ $inputClass($simPerhitungan, 'sim_l4_z_sub_x') }}"
                                    @if ($inputLocked($simPerhitungan, 'sim_l4_z_sub_x')) readonly @endif>

                                <span>\(-\)</span>

                                <input
                                    type="text"
                                    name="answers[sim_l4_z_sub_y]"
                                    value="{{ old('answers.sim_l4_z_sub_y', $inputValue($simPerhitungan, 'sim_l4_z_sub_y')) }}"
                                    aria-label="Nilai y yang disubstitusikan untuk mencari z"
                                    class="h-10 w-16 rounded-xl border px-2 text-center text-base font-semibold text-slate-900 {{ $inputClass($simPerhitungan, 'sim_l4_z_sub_y') }}"
                                    @if ($inputLocked($simPerhitungan, 'sim_l4_z_sub_y')) readonly @endif>

                                <span>\(- z = 2\)</span>
                            </div>

                            <div class="flex items-center justify-center gap-2">
                                <input
                                    type="text"
                                    name="answers[sim_l4_z_ruas_kiri]"
                                    value="{{ old('answers.sim_l4_z_ruas_kiri', $inputValue($simPerhitungan, 'sim_l4_z_ruas_kiri')) }}"
                                    aria-label="Hasil penyederhanaan ruas kiri untuk mencari z"
                                    class="h-10 w-16 rounded-xl border px-2 text-center text-base font-semibold text-slate-900 {{ $inputClass($simPerhitungan, 'sim_l4_z_ruas_kiri') }}"
                                    @if ($inputLocked($simPerhitungan, 'sim_l4_z_ruas_kiri')) readonly @endif>

                                <span>\(- z = 2\)</span>
                            </div>

                            <div class="flex items-center justify-center gap-2">
                                <span>\(-z = 2 -\)</span>

                                <input
                                    type="text"
                                    name="answers[sim_l4_z_pengurang]"
                                    value="{{ old('answers.sim_l4_z_pengurang', $inputValue($simPerhitungan, 'sim_l4_z_pengurang')) }}"
                                    aria-label="Nilai pengurang untuk mencari z"
                                    class="h-10 w-16 rounded-xl border px-2 text-center text-base font-semibold text-slate-900 {{ $inputClass($simPerhitungan, 'sim_l4_z_pengurang') }}"
                                    @if ($inputLocked($simPerhitungan, 'sim_l4_z_pengurang')) readonly @endif>
                            </div>

                            <div class="flex items-center justify-center gap-2">
                                <span>\(-z =\)</span>

                                <input
                                    type="text"
                                    name="answers[sim_l4_z_negatif]"
                                    value="{{ old('answers.sim_l4_z_negatif', $inputValue($simPerhitungan, 'sim_l4_z_negatif')) }}"
                                    aria-label="Nilai negatif z"
                                    class="h-10 w-16 rounded-xl border px-2 text-center text-base font-semibold text-slate-900 {{ $inputClass($simPerhitungan, 'sim_l4_z_negatif') }}"
                                    @if ($inputLocked($simPerhitungan, 'sim_l4_z_negatif')) readonly @endif>
                            </div>

                            <div class="flex items-center justify-center gap-2">
                                <span>\(z =\)</span>

                                <input
                                    type="text"
                                    name="answers[sim_l4_nilai_z]"
                                    value="{{ old('answers.sim_l4_nilai_z', $inputValue($simPerhitungan, 'sim_l4_nilai_z')) }}"
                                    aria-label="Nilai z"
                                    class="h-10 w-16 rounded-xl border px-2 text-center text-base font-semibold text-slate-900 {{ $inputClass($simPerhitungan, 'sim_l4_nilai_z') }}"
                                    @if ($inputLocked($simPerhitungan, 'sim_l4_nilai_z')) readonly @endif>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    @foreach ([
                        'sim_l4_y_sub_x',
                        'sim_l4_y_pengurang',
                        'sim_l4_nilai_y',
                        'sim_l4_z_sub_x',
                        'sim_l4_z_sub_y',
                        'sim_l4_z_ruas_kiri',
                        'sim_l4_z_pengurang',
                        'sim_l4_z_negatif',
                        'sim_l4_nilai_z',
                    ] as $fieldKey)
                        <x-practice-field-feedback :feedback="$simPerhitungan['feedback'][$fieldKey] ?? []" />
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <p class="leading-7 text-slate-700">
                    Melalui tahapan di atas, himpunan penyelesaian sistem tersebut telah berhasil ditemukan, yaitu \((x=2, y=1, z=-1)\). Maka, Himpunan Penyelesaian sistem tersebut adalah:
                </p>

                <div class="mt-5 grid gap-4 sm:grid-cols-3">
                    @foreach ([
                        ['sim_hasil_x', 'x'],
                        ['sim_hasil_y', 'y'],
                        ['sim_hasil_z', 'z'],
                    ] as [$fieldKey, $variable])
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">
                                \( {{ $variable }} \)
                            </span>

                            <input
                                type="text"
                                name="answers[{{ $fieldKey }}]"
                                value="{{ old('answers.' . $fieldKey, $inputValue($simPerhitungan, $fieldKey)) }}"
                                class="mt-2 w-full rounded-xl border px-3 py-2.5 text-center text-slate-900 {{ $inputClass($simPerhitungan, $fieldKey) }}"
                                @if ($inputLocked($simPerhitungan, $fieldKey)) readonly @endif>

                            <x-practice-field-feedback :feedback="$simPerhitungan['feedback'][$fieldKey] ?? []" />
                        </label>
                    @endforeach
                </div>
            </div>

            @if (! $simPerhitungan['is_completed'])
                <div class="flex justify-end border-t border-slate-200 pt-5">
                    <button type="submit" class="w-full rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-cyan-700 sm:w-auto">
                        Cek Jawaban Perhitungan
                    </button>
                </div>
            @endif
        </form>
    </div>

    <div class="overflow-hidden rounded-[1.75rem] border border-cyan-200 bg-cyan-50 shadow-sm">
        <div class="border-b border-cyan-200 bg-gradient-to-br from-cyan-100 via-white to-cyan-50 px-5 py-6 sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-cyan-700">
                        Cek Pemahaman
                    </p>

                    <h3 class="mt-1 text-xl font-bold text-slate-950">
                        Keterbatasan Metode Dasar
                    </h3>
                </div>

                <span class="w-fit shrink-0 rounded-full px-3.5 py-2 text-xs font-bold {{ $cekMetodeBadge[0] }}">
                    {{ $cekMetodeBadge[1] }}
                </span>
            </div>

            @if ($cekMetodeAttempt['show'])
                <div class="mt-5 rounded-2xl border border-cyan-200 bg-white/80 px-4 py-3">
                    <p class="text-sm font-bold text-cyan-950">
                        Kesempatan tersisa: {{ $cekMetodeAttempt['remaining'] }} dari {{ $cekMetodeAttempt['max'] }}
                    </p>
                </div>
            @endif
        </div>

        <form action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'cek-pemahaman-1-4-metode']) }}" method="POST" data-preserve-scroll="true" class="space-y-4 p-5 sm:p-6">
            @csrf

            <p class="leading-7 text-slate-700">
                Berdasarkan proses perhitungan yang baru saja Anda lengkapi di atas, bayangkan jika Anda harus menyelesaikan sistem persamaan dengan 20 variabel \((x_1 \text{ hingga } x_{20})\). Manakah dari pernyataan di bawah ini yang merupakan konsekuensi logis jika komputer dipaksa menggunakan metode substitusi-eliminasi manual tersebut? (Centang semua jawaban yang benar)
            </p>

            @php
                $cekMetodeOptions = [
                    'cek_metode_a' => 'Proses komputasi akan memakan memori yang sangat besar dan waktu yang panjang karena panjangnya struktur kode bersusun.',
                    'cek_metode_b' => 'Algoritma penyelesaiannya sangat mudah dan efisien untuk diubah ke dalam baris kode pemrograman.',
                    'cek_metode_c' => 'Rawan terjadi kesalahan komputasi (error) akibat banyaknya operasi tanda minus/plus yang berulang pada setiap variabel.',
                    'cek_metode_d' => 'Merupakan metode komputasi yang paling direkomendasikan untuk memproses Big Data pada kecerdasan buatan (AI).',
                ];
                $cekMetodeSelected = $selectedOptions($cekMetode, 'cek_metode_pernyataan');
            @endphp

            <div class="space-y-3">
                @foreach ($cekMetodeOptions as $optionKey => $optionText)
                    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border p-4 transition {{ $checkboxClass($cekMetode, 'cek_metode_pernyataan', $optionKey) }}">
                        <input
                            type="checkbox"
                            name="answers[cek_metode_pernyataan][]"
                            value="{{ $optionKey }}"
                            @checked(in_array($optionKey, $cekMetodeSelected, true))
                            @disabled($inputLocked($cekMetode, 'cek_metode_pernyataan'))
                            class="mt-1 rounded border-slate-300 text-cyan-600">

                        <span class="text-sm leading-6 text-slate-700">
                            {{ $optionText }}
                        </span>
                    </label>
                @endforeach
            </div>

            <x-practice-field-feedback :feedback="$cekMetode['feedback']['cek_metode_pernyataan'] ?? []" />

            @if (! $cekMetode['is_completed'])
                <div class="flex justify-end border-t border-cyan-200 pt-5">
                    <button type="submit" class="w-full rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-cyan-700 sm:w-auto">
                        Cek Pemahaman
                    </button>
                </div>
            @endif
        </form>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-xl font-bold text-slate-950">
            2. Keterbatasan Metode Dasar dan Kebutuhan Representasi Matriks
        </h3>

        <p class="mt-4 leading-7 text-slate-700">
            Dalam praktiknya, sistem persamaan dapat memuat ratusan hingga ribuan variabel. Menyelesaikan komputasi skala besar menggunakan operasi manual seperti di atas sangat tidak efisien dan sulit diubah menjadi algoritma yang baku oleh arsitektur mesin. Oleh karena itu, para ahli komputasi mengekstraksi nilai dari sistem persamaan tersebut dan menyusunnya ke dalam struktur representasi data yang disebut sebagai Matriks.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-xl font-bold text-slate-950">
            3. Ekstraksi Pembentukan Matriks Koefisien \((A)\)
        </h3>

        <p class="mt-4 leading-7 text-slate-700">
            Mari kita ubah sistem persamaan sebelumnya ke dalam bentuk matriks.
        </p>

        <div class="mt-5 overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-5 text-center">
            \[
                \begin{aligned}
                    2x + 3y - z &= 5 \\
                    4x - y + 2z &= -1 \\
                    -x + 2y + 3z &= 4
                \end{aligned}
            \]
        </div>

        <p class="mt-5 leading-7 text-slate-700">
            Langkah pertama dalam transformasi SPL ke bentuk komputasi adalah mengekstraksi Koefisien (konstanta numerik yang mendampingi variabel). Ingat kembali tiga aturan dasar ekstraksi:
        </p>

        <ol class="mt-4 list-decimal space-y-2 pl-6 leading-7 text-slate-700">
            <li>Variabel tanpa angka di depannya (contoh: \(x\)) memiliki koefisien 1.</li>
            <li>Variabel dengan tanda negatif (contoh: \(-z\)) memiliki koefisien -1.</li>
            <li>Jika suatu variabel tidak muncul pada baris tertentu, maka koefisiennya bernilai 0.</li>
        </ol>
    </div>

    <div class="overflow-hidden rounded-[1.75rem] border border-cyan-200 bg-cyan-50 shadow-sm">
        <div class="border-b border-cyan-200 bg-gradient-to-br from-cyan-100 via-white to-cyan-50 px-5 py-6 sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-cyan-700">
                        Contoh Simulasi
                    </p>

                    <h3 class="mt-1 text-xl font-bold text-slate-950">
                        Ekstraksi Pembentukan Matriks Koefisien \((A)\)
                    </h3>
                </div>

                <span class="w-fit shrink-0 rounded-full px-3.5 py-2 text-xs font-bold {{ $simMatriksABadge[0] }}">
                    {{ $simMatriksABadge[1] }}
                </span>
            </div>

            @if ($simMatriksAAttempt['show'])
                <div class="mt-5 rounded-2xl border border-cyan-200 bg-white/80 px-4 py-3">
                    <p class="text-sm font-bold text-cyan-950">
                        Kesempatan tersisa: {{ $simMatriksAAttempt['remaining'] }} dari {{ $simMatriksAAttempt['max'] }}
                    </p>
                </div>
            @endif
        </div>

        <form action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'contoh-simulasi-1-4-matriks-a']) }}" method="POST" data-preserve-scroll="true" class="space-y-5 p-5 sm:p-6">
            @csrf

            <p class="leading-7 text-slate-700">
                Tugas Anda: Ekstraksilah koefisien dari sistem persamaan di atas, lalu masukkan angkanya ke dalam <span class="italic">cell</span> Matriks Koefisien \((A)\) berikut ini.
            </p>

            <div class="overflow-x-auto">
                <div class="mx-auto w-max rounded-2xl border border-slate-200 bg-white p-5">
                    <div class="flex items-center gap-4">
                        <span class="text-xl font-bold text-slate-900">\(A=\)</span>

                        <div class="grid gap-2" style="grid-template-columns: repeat(3, 64px);">
                            @foreach ($matrixAFields as $row)
                                @foreach ($row as $fieldKey)
                                    <input
                                        type="text"
                                        name="answers[{{ $fieldKey }}]"
                                        value="{{ old('answers.' . $fieldKey, $inputValue($simMatriksA, $fieldKey)) }}"
                                        class="h-11 rounded-xl border text-center font-semibold text-slate-900 {{ $inputClass($simMatriksA, $fieldKey) }}"
                                        @if ($inputLocked($simMatriksA, $fieldKey)) readonly @endif>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                @foreach (array_merge(...$matrixAFields) as $fieldKey)
                    <x-practice-field-feedback :feedback="$simMatriksA['feedback'][$fieldKey] ?? []" />
                @endforeach
            </div>

            @if (! $simMatriksA['is_completed'])
                <div class="flex justify-end border-t border-cyan-200 pt-5">
                    <button type="submit" class="w-full rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-cyan-700 sm:w-auto">
                        Cek Jawaban Matriks A
                    </button>
                </div>
            @endif
        </form>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-xl font-bold text-slate-950">
            4. Pendefinisian Persamaan Matriks dalam Format \(Ax=b\)
        </h3>

        <p class="mt-4 leading-7 text-slate-700">
            Setelah Matriks Koefisien dideklarasikan, elemen variabel dan elemen hasil observasi direpresentasikan ke dalam notasi standar \(Ax=b\), dengan rincian:
        </p>

        <ul class="mt-4 space-y-2 pl-6 leading-7 text-slate-700">
            <li>\(A\) = Matriks Koefisien (elemen pengali).</li>
            <li>\(x\) = Matriks Variabel (vektor kolom yang memuat variabel yang dicari).</li>
            <li>\(b\) = Matriks Konstanta (vektor kolom yang memuat nilai hasil di ruas kanan).</li>
        </ul>
    </div>

    <div class="overflow-hidden rounded-[1.75rem] border border-cyan-200 bg-cyan-50 shadow-sm">
        <div class="border-b border-cyan-200 bg-gradient-to-br from-cyan-100 via-white to-cyan-50 px-5 py-6 sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-cyan-700">
                        Contoh Simulasi
                    </p>

                    <h3 class="mt-1 text-xl font-bold text-slate-950">
                        Persamaan Matriks dalam Format \(Ax=b\)
                    </h3>
                </div>

                <span class="w-fit shrink-0 rounded-full px-3.5 py-2 text-xs font-bold {{ $simAxbBadge[0] }}">
                    {{ $simAxbBadge[1] }}
                </span>
            </div>

            @if ($simAxbAttempt['show'])
                <div class="mt-5 rounded-2xl border border-cyan-200 bg-white/80 px-4 py-3">
                    <p class="text-sm font-bold text-cyan-950">
                        Kesempatan tersisa: {{ $simAxbAttempt['remaining'] }} dari {{ $simAxbAttempt['max'] }}
                    </p>
                </div>
            @endif
        </div>

        <form action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'contoh-simulasi-1-4-ax-b']) }}" method="POST" data-preserve-scroll="true" class="space-y-5 p-5 sm:p-6">
            @csrf

            <p class="leading-7 text-slate-700">
                Tugas Anda: Berdasarkan SPL pada bagian Ekstraksi Pembentukan Matriks Koefisien \(A\), lengkapi vektor variabel dan vektor konstanta agar membentuk persamaan matriks \(Ax=b\) yang tepat.
            </p>

            <div class="overflow-x-auto">
                <div class="mx-auto grid w-max items-center gap-3 rounded-2xl border border-slate-200 bg-white p-5" style="grid-template-columns: auto auto auto;">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        \[
                            \begin{bmatrix}
                                2 & 3 & -1 \\
                                4 & -1 & 2 \\
                                -1 & 2 & 3
                            \end{bmatrix}
                        \]
                        <p class="mt-1 text-center text-xs font-semibold text-slate-500">Matriks \(A\)</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <div class="grid gap-2">
                                @foreach (['axb_x1', 'axb_x2', 'axb_x3'] as $fieldKey)
                                    <input
                                        type="text"
                                        name="answers[{{ $fieldKey }}]"
                                        value="{{ old('answers.' . $fieldKey, $inputValue($simAxb, $fieldKey)) }}"
                                        class="h-10 w-16 rounded-xl border text-center font-semibold text-slate-900 {{ $inputClass($simAxb, $fieldKey) }}"
                                        @if ($inputLocked($simAxb, $fieldKey)) readonly @endif>
                                @endforeach
                            </div>
                            <p class="mt-2 text-center text-xs font-semibold text-slate-500">Matriks \(x\)</p>
                        </div>

                        <span class="text-2xl font-bold text-slate-900">=</span>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <div class="grid gap-2">
                                @foreach (['axb_b1', 'axb_b2', 'axb_b3'] as $fieldKey)
                                    <input
                                        type="text"
                                        name="answers[{{ $fieldKey }}]"
                                        value="{{ old('answers.' . $fieldKey, $inputValue($simAxb, $fieldKey)) }}"
                                        class="h-10 w-16 rounded-xl border text-center font-semibold text-slate-900 {{ $inputClass($simAxb, $fieldKey) }}"
                                        @if ($inputLocked($simAxb, $fieldKey)) readonly @endif>
                                @endforeach
                            </div>
                            <p class="mt-2 text-center text-xs font-semibold text-slate-500">Matriks \(b\)</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                @foreach (['axb_x1', 'axb_x2', 'axb_x3', 'axb_b1', 'axb_b2', 'axb_b3'] as $fieldKey)
                    <x-practice-field-feedback :feedback="$simAxb['feedback'][$fieldKey] ?? []" />
                @endforeach
            </div>

            @if (! $simAxb['is_completed'])
                <div class="flex justify-end border-t border-cyan-200 pt-5">
                    <button type="submit" class="w-full rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-cyan-700 sm:w-auto">
                        Cek Jawaban Persamaan \(Ax=b\)
                    </button>
                </div>
            @endif
        </form>
    </div>

    <div class="overflow-hidden rounded-[1.75rem] border border-cyan-200 bg-cyan-50 shadow-sm">
        <div class="border-b border-cyan-200 bg-gradient-to-br from-cyan-100 via-white to-cyan-50 px-5 py-6 sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-cyan-700">
                        Cek Pemahaman
                    </p>

                    <h3 class="mt-1 text-xl font-bold text-slate-950">
                        Notasi \(Ax=b\)
                    </h3>
                </div>

                <span class="w-fit shrink-0 rounded-full px-3.5 py-2 text-xs font-bold {{ $cekAxbBadge[0] }}">
                    {{ $cekAxbBadge[1] }}
                </span>
            </div>

            @if ($cekAxbAttempt['show'])
                <div class="mt-5 rounded-2xl border border-cyan-200 bg-white/80 px-4 py-3">
                    <p class="text-sm font-bold text-cyan-950">
                        Kesempatan tersisa: {{ $cekAxbAttempt['remaining'] }} dari {{ $cekAxbAttempt['max'] }}
                    </p>
                </div>
            @endif
        </div>

        <form action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'cek-pemahaman-1-4-ax-b']) }}" method="POST" data-preserve-scroll="true" class="space-y-4 p-5 sm:p-6">
            @csrf

            <p class="leading-7 text-slate-700">
                Berdasarkan latihan pendefinisian notasi \(Ax=b\) di atas, manakah pernyataan analitis di bawah ini yang bernilai BENAR? (Centang semua jawaban yang tepat)
            </p>

            @php
                $cekAxbOptions = [
                    'cek_axb_a' => 'Notasi b merepresentasikan Matriks Konstanta, yaitu elemen hasil yang berada di ruas kanan sistem persamaan.',
                    'cek_axb_b' => 'Secara matematis, susunan entri pada Matriks Variabel (x) selalu ditulis dalam bentuk vektor horizontal (memanjang ke samping).',
                    'cek_axb_c' => 'Entri pada Matriks Koefisien (A) murni dibentuk dari nilai konstanta numerik yang mendampingi variabel pada sistem linear awal.',
                    'cek_axb_d' => 'Matriks Konstanta (b) selalu diisi dengan nilai mutlak 0 pada seluruh operasinya.',
                ];
                $cekAxbSelected = $selectedOptions($cekAxb, 'cek_axb_pernyataan');
            @endphp

            <div class="space-y-3">
                @foreach ($cekAxbOptions as $optionKey => $optionText)
                    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border p-4 transition {{ $checkboxClass($cekAxb, 'cek_axb_pernyataan', $optionKey) }}">
                        <input
                            type="checkbox"
                            name="answers[cek_axb_pernyataan][]"
                            value="{{ $optionKey }}"
                            @checked(in_array($optionKey, $cekAxbSelected, true))
                            @disabled($inputLocked($cekAxb, 'cek_axb_pernyataan'))
                            class="mt-1 rounded border-slate-300 text-cyan-600">

                        <span class="text-sm leading-6 text-slate-700">
                            {!! str_replace(
                                ['(A)', '(x)', '(b)'],
                                ['\\(A\\)', '\\(x\\)', '\\(b\\)'],
                                e($optionText)
                            ) !!}
                        </span>
                    </label>
                @endforeach
            </div>

            <x-practice-field-feedback :feedback="$cekAxb['feedback']['cek_axb_pernyataan'] ?? []" />

            @if (! $cekAxb['is_completed'])
                <div class="flex justify-end border-t border-cyan-200 pt-5">
                    <button type="submit" class="w-full rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-cyan-700 sm:w-auto">
                        Cek Pemahaman
                    </button>
                </div>
            @endif
        </form>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-xl font-bold text-slate-950">
            5. Representasi Matriks yang Diperbesar (Augmented Matrix)
        </h3>

        <p class="mt-4 leading-7 text-slate-700">
            Meskipun format \(Ax=b\) ideal untuk analisis teoretis, implementasi perhitungan algoritma akan sangat tidak efisien jika sistem harus terus-menerus mendeklarasikan Matriks Variabel.
        </p>

        <p class="mt-4 leading-7 text-slate-700">
            Untuk mengoptimalkan memori dan penyederhanaan komputasi, Matriks Koefisien \((A)\) dan Matriks Konstanta \((b)\) digabungkan ke dalam satu struktur matriks tunggal yang disebut Matriks yang Diperbesar (Augmented Matrix), dengan dibatasi oleh sebuah garis vertikal.
        </p>
    </div>

    <div class="overflow-hidden rounded-[1.75rem] border border-cyan-200 bg-cyan-50 shadow-sm">
        <div class="border-b border-cyan-200 bg-gradient-to-br from-cyan-100 via-white to-cyan-50 px-5 py-6 sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-cyan-700">
                        Contoh Simulasi
                    </p>

                    <h3 class="mt-1 text-xl font-bold text-slate-950">
                        Representasi Augmented Matrix
                    </h3>
                </div>

                <span class="w-fit shrink-0 rounded-full px-3.5 py-2 text-xs font-bold {{ $simAugmentedBadge[0] }}">
                    {{ $simAugmentedBadge[1] }}
                </span>
            </div>

            @if ($simAugmentedAttempt['show'])
                <div class="mt-5 rounded-2xl border border-cyan-200 bg-white/80 px-4 py-3">
                    <p class="text-sm font-bold text-cyan-950">
                        Kesempatan tersisa: {{ $simAugmentedAttempt['remaining'] }} dari {{ $simAugmentedAttempt['max'] }}
                    </p>
                </div>
            @endif
        </div>

        <form action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'contoh-simulasi-1-4-augmented']) }}" method="POST" data-preserve-scroll="true" class="space-y-5 p-5 sm:p-6">
            @csrf

            <p class="leading-7 text-slate-700">
                Tugas Anda: Gabungkan seluruh elemen matriks yang telah Anda susun ke dalam struktur <span class="italic">Augmented Matrix</span> di bawah ini!
            </p>

            <div class="overflow-x-auto">
                <div class="mx-auto w-max rounded-2xl border border-slate-200 bg-white p-5">
                    <div class="grid gap-2" style="grid-template-columns: repeat(4, 64px);">
                        @foreach ($augmentedFields as $row)
                            @foreach ($row as $columnIndex => $fieldKey)
                                <input
                                    type="text"
                                    name="answers[{{ $fieldKey }}]"
                                    value="{{ old('answers.' . $fieldKey, $inputValue($simAugmented, $fieldKey)) }}"
                                    class="h-11 rounded-xl border text-center font-semibold text-slate-900 {{ $inputClass($simAugmented, $fieldKey) }} {{ $columnIndex === 3 ? 'border-l-4 border-l-slate-700' : '' }}"
                                    @if ($inputLocked($simAugmented, $fieldKey)) readonly @endif>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                @foreach (array_merge(...$augmentedFields) as $fieldKey)
                    <x-practice-field-feedback :feedback="$simAugmented['feedback'][$fieldKey] ?? []" />
                @endforeach
            </div>

            @if (! $simAugmented['is_completed'])
                <div class="flex justify-end border-t border-cyan-200 pt-5">
                    <button type="submit" class="w-full rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-cyan-700 sm:w-auto">
                        Cek Jawaban Augmented Matrix
                    </button>
                </div>
            @endif
        </form>
    </div>

    <div class="overflow-hidden rounded-[1.75rem] border border-cyan-200 bg-cyan-50 shadow-sm">
        <div class="border-b border-cyan-200 bg-gradient-to-br from-cyan-100 via-white to-cyan-50 px-5 py-6 sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-cyan-700">
                        Cek Pemahaman
                    </p>

                    <h3 class="mt-1 text-xl font-bold text-slate-950">
                        Membaca Augmented Matrix
                    </h3>
                </div>

                <span class="w-fit shrink-0 rounded-full px-3.5 py-2 text-xs font-bold {{ $cekTerjemahanBadge[0] }}">
                    {{ $cekTerjemahanBadge[1] }}
                </span>
            </div>

            @if ($cekTerjemahanAttempt['show'])
                <div class="mt-5 rounded-2xl border border-cyan-200 bg-white/80 px-4 py-3">
                    <p class="text-sm font-bold text-cyan-950">
                        Kesempatan tersisa: {{ $cekTerjemahanAttempt['remaining'] }} dari {{ $cekTerjemahanAttempt['max'] }}
                    </p>
                </div>
            @endif
        </div>

        <form action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'cek-pemahaman-1-4-terjemahan-matriks']) }}" method="POST" data-preserve-scroll="true" class="space-y-5 p-5 sm:p-6">
            @csrf

            <p class="leading-7 text-slate-700">
                Mari kita gunakan sebuah matriks baru untuk menguji ketelitian Anda! Sebagai seorang perekayasa data, Anda harus mampu membaca kembali representasi memori matriks dan menerjemahkannya menjadi bahasa persamaan matematis standar.
            </p>

            <p class="leading-7 text-slate-700">
                Diberikan sebuah Augmented Matrix berisikan variabel \(x\), \(y\), dan \(z\) sebagai berikut:
            </p>

            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white p-5 text-center">
                \[
                    \left[
                    \begin{array}{ccc|c}
                        1 & -2 & 0 & 8 \\
                        0 & 3 & 1 & 4 \\
                        2 & 0 & -5 & -1
                    \end{array}
                    \right]
                \]
            </div>

            <p class="leading-7 text-slate-700">
                Tugas Anda: Terjemahkan matriks di atas menjadi sistem persamaan linear yang utuh. Ketikkan persamaannya ke dalam kotak yang tersedia di bawah ini!
            </p>

            <div class="space-y-4">
                @foreach ([
                    ['terjemah_baris1', 'Persamaan Baris 1:'],
                    ['terjemah_baris2', 'Persamaan Baris 2:'],
                    ['terjemah_baris3', 'Persamaan Baris 3:'],
                ] as [$fieldKey, $label])
                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">
                            {{ $label }}
                        </span>

                        <input
                            type="text"
                            name="answers[{{ $fieldKey }}]"
                            value="{{ old('answers.' . $fieldKey, $inputValue($cekTerjemahan, $fieldKey)) }}"
                            class="mt-2 w-full rounded-xl border px-3 py-2.5 text-slate-900 {{ $inputClass($cekTerjemahan, $fieldKey) }}"
                            @if ($inputLocked($cekTerjemahan, $fieldKey)) readonly @endif>

                        <x-practice-field-feedback :feedback="$cekTerjemahan['feedback'][$fieldKey] ?? []" />
                    </label>
                @endforeach
            </div>

            @if (! $cekTerjemahan['is_completed'])
                <div class="flex justify-end border-t border-cyan-200 pt-5">
                    <button type="submit" class="w-full rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-cyan-700 sm:w-auto">
                        Cek Pemahaman
                    </button>
                </div>
            @endif
        </form>
    </div>

    <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-gradient-to-br from-cyan-50 via-white to-slate-50 px-5 py-6 sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex min-w-0 items-start gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-cyan-100 text-sm font-black text-cyan-700">
                        1.4
                    </div>

                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-cyan-700">
                            Aktivitas
                        </p>

                        <h3 class="mt-1 text-xl font-bold text-slate-950">
                            Pemodelan Matriks pada Kasus Komputasi Dunia Nyata
                        </h3>
                    </div>
                </div>

                <span class="w-fit shrink-0 rounded-full px-3.5 py-2 text-xs font-bold {{ $aktivitas14Badge[0] }}">
                    {{ $aktivitas14Badge[1] }}
                </span>
            </div>
        </div>

        <form action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'aktivitas-1-4-matriks']) }}" method="POST" data-preserve-scroll="true" class="space-y-7 p-5 sm:p-6">
            @csrf

            @if ($aktivitas14Attempt['show'])
                <div class="rounded-2xl border border-cyan-200 bg-cyan-50 p-4">
                    <p class="text-sm font-bold text-cyan-950">
                        Kesempatan tersisa: {{ $aktivitas14Attempt['remaining'] }} dari {{ $aktivitas14Attempt['max'] }}
                    </p>

                    <p class="mt-1 text-xs leading-5 text-cyan-800">
                        Berlaku untuk seluruh Aktivitas 1.4. Kolom yang belum diisi tidak mengurangi kesempatan.
                    </p>
                </div>
            @endif

            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <h4 class="text-lg font-bold text-slate-950">
                    Studi Kasus 1: Keseimbangan Ekonomi pada <span class="italic">Game Development</span>
                </h4>

                <p class="mt-4 leading-7 text-slate-700">
                    Anda adalah seorang Game Developer yang sedang merancang sistem crafting (pembuatan senjata) untuk sebuah game RPG. Terdapat 3 jenis item (Pedang, Perisai, dan Zirah) yang dibuat dari 3 material dasar: Besi \((x)\), Perak \((y)\), dan Emas \((z)\).
                </p>

                <ul class="mt-4 list-disc space-y-2 pl-6 leading-7 text-slate-700">
                    <li>Untuk membuat 1 Pedang, dibutuhkan 3 Besi, 1 Perak, dan 0 Emas. Harga jual pedang diset menjadi 50 koin.</li>
                    <li>Untuk membuat 1 Perisai, dibutuhkan 2 Besi, 2 Perak, dan 1 Emas. Harga jual perisai diset menjadi 80 koin.</li>
                    <li>Untuk membuat 1 Zirah, dibutuhkan 0 Besi, 4 Perak, dan 3 Emas. Harga jual zirah diset menjadi 120 koin.</li>
                </ul>

                <p class="mt-5 leading-7 text-slate-700">
                    Tugas Anda: Untuk mengkalkulasi nilai tukar setiap material di dalam sistem game engine menggunakan aljabar linear, tulislah logika ekonomi di atas secara langsung ke dalam struktur array terpisah \(Ax=b\)!
                </p>

                <div class="mt-5 overflow-x-auto">
                    <div class="mx-auto grid w-max items-center gap-3 rounded-2xl border border-slate-200 bg-white p-5" style="grid-template-columns: auto auto auto;">
                        <div class="grid gap-2" style="grid-template-columns: repeat(3, 58px);">
                            @foreach ($gameAFields as $row)
                                @foreach ($row as $fieldKey)
                                    <input
                                        type="text"
                                        name="answers[{{ $fieldKey }}]"
                                        value="{{ old('answers.' . $fieldKey, $inputValue($aktivitas14, $fieldKey)) }}"
                                        class="h-10 rounded-xl border text-center font-semibold text-slate-900 {{ $inputClass($aktivitas14, $fieldKey) }}"
                                        @if ($inputLocked($aktivitas14, $fieldKey)) readonly @endif>
                                @endforeach
                            @endforeach
                        </div>

                        <div class="grid gap-2">
                            @foreach (['x', 'y', 'z'] as $variable)
                                <div class="flex h-10 w-12 items-center justify-center rounded-xl border border-slate-300 bg-slate-50 font-semibold text-slate-900">
                                    {{ $variable }}
                                </div>
                            @endforeach
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="text-2xl font-bold text-slate-900">=</span>

                            <div class="grid gap-2">
                                @foreach (['game_b1', 'game_b2', 'game_b3'] as $fieldKey)
                                    <input
                                        type="text"
                                        name="answers[{{ $fieldKey }}]"
                                        value="{{ old('answers.' . $fieldKey, $inputValue($aktivitas14, $fieldKey)) }}"
                                        class="h-10 w-16 rounded-xl border text-center font-semibold text-slate-900 {{ $inputClass($aktivitas14, $fieldKey) }}"
                                        @if ($inputLocked($aktivitas14, $fieldKey)) readonly @endif>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    $gameFeedbackFields = [
                        ...$gameAFields[0],
                        ...$gameAFields[1],
                        ...$gameAFields[2],
                        'game_b1',
                        'game_b2',
                        'game_b3',
                    ];
                @endphp

                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    @foreach ($gameFeedbackFields as $fieldKey)
                        <x-practice-field-feedback :feedback="$aktivitas14['feedback'][$fieldKey] ?? []" />
                    @endforeach
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <h4 class="text-lg font-bold text-slate-950">
                    Studi Kasus 2: Pemodelan <span class="italic">Resource</span> pada <span class="italic">Cloud Computing</span>
                </h4>

                <p class="mt-4 leading-7 text-slate-700">
                    Sebuah perusahaan startup menyewa 3 jenis server di layanan Cloud: Tipe Basic \((x)\), Tipe Pro \((y)\), dan Tipe Enterprise \((z)\). Seorang Cloud Engineer mencatat total kapasitas yang digunakan oleh ketiga jenis server tersebut dalam sebulan dengan rincian sebagai berikut:
                </p>

                <ul class="mt-4 list-disc space-y-2 pl-6 leading-7 text-slate-700">
                    <li>Total CPU (Core): Setiap server Basic memakai 2 core, Pro memakai 4 core, dan Enterprise memakai 8 core. Total CPU yang disewa adalah 64 core.</li>
                    <li>Total RAM (GB): Setiap server Basic memakai 4 GB, Pro memakai 16 GB, dan Enterprise memakai 32 GB. Total RAM yang disewa adalah 200 GB.</li>
                    <li>Total Storage (TB): Setiap server Basic memakai 1 TB, Pro memakai 1 TB, dan Enterprise memakai 2 TB. Total Storage yang disewa adalah 20 TB.</li>
                </ul>

                <p class="mt-5 leading-7 text-slate-700">
                    Tugas Anda: Jika perusahaan ingin mencari tahu berapa persisnya jumlah server \((x, y, z)\) yang disewa, langkah pertama yang harus dilakukan komputer adalah mengubah deskripsi di atas menjadi Augmented Matrix. Isikan angka yang tepat ke dalam cell matriks di bawah ini agar algoritma komputer bisa memprosesnya!
                </p>

                <div class="mt-5 overflow-x-auto">
                    <div class="mx-auto w-max rounded-2xl border border-slate-200 bg-white p-5">
                        <div class="grid gap-2" style="grid-template-columns: repeat(4, 64px);">
                            @foreach ($cloudFields as $row)
                                @foreach ($row as $columnIndex => $fieldKey)
                                    <input
                                        type="text"
                                        name="answers[{{ $fieldKey }}]"
                                        value="{{ old('answers.' . $fieldKey, $inputValue($aktivitas14, $fieldKey)) }}"
                                        class="h-11 rounded-xl border text-center font-semibold text-slate-900 {{ $inputClass($aktivitas14, $fieldKey) }} {{ $columnIndex === 3 ? 'border-l-4 border-l-slate-700' : '' }}"
                                        @if ($inputLocked($aktivitas14, $fieldKey)) readonly @endif>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    @foreach (array_merge(...$cloudFields) as $fieldKey)
                        <x-practice-field-feedback :feedback="$aktivitas14['feedback'][$fieldKey] ?? []" />
                    @endforeach
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <h4 class="text-lg font-bold text-slate-950">
                    Studi Kasus 3: Debugging (Memperbaiki Kesalahan Input Matriks)
                </h4>

                <p class="mt-4 leading-7 text-slate-700">
                    Seorang Programmer Junior di tim Anda ditugaskan untuk memasukkan sebuah Sistem Persamaan Linear ke dalam database berbentuk Augmented Matrix. Sistem persamaannya adalah sebagai berikut:
                </p>

                <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white p-4 text-center">
                    \[
                        \begin{aligned}
                            x - 3y &= 5 \\
                            2x + y + 4z &= 10 \\
                            -y + 5z &= 2
                        \end{aligned}
                    \]
                </div>

                <p class="mt-4 leading-7 text-slate-700">
                    Namun, karena kurang teliti, Programmer Junior tersebut menuliskan matriksnya menjadi seperti ini di dalam sistem:
                </p>

                <div class="mt-4 overflow-x-auto rounded-xl border border-red-200 bg-red-50 p-4 text-center">
                    \[
                        \left[
                        \begin{array}{ccc|c}
                            1 & -3 & 5 & 0 \\
                            2 & 1 & 4 & 10 \\
                            -2 & 5 & 0 & 2
                        \end{array}
                        \right]
                    \]
                </div>

                <p class="mt-5 leading-7 text-slate-700">
                    Akibatnya, program mengalami error dan perhitungan menjadi salah total. Tugas Analisis 3A (Pilih Semua yang Benar): Sebagai Senior Programmer, Anda harus mengidentifikasi di mana letak kesalahan bawahan Anda. Bandingkan persamaan asli dengan matriks buatan Programmer Junior di atas. Manakah pernyataan di bawah ini yang menunjukkan kesalahan yang ia lakukan? (Centang semua jawaban yang tepat!)
                </p>

                @php
                    $debugOptions = [
                        'debug_a' => 'Pada Baris 1, ia memasukkan angka hasil (5) ke dalam kolom variabel z. Seharusnya kolom z diisi 0 dan angka 5 diletakkan di ruas paling kanan.',
                        'debug_b' => 'Pada Baris 2, ia salah memasukkan koefisien variabel y, seharusnya diisi 0.',
                        'debug_c' => 'Pada Baris 3, ia salah menggeser posisi koefisien. Angka -1 (milik y) diletakkan di kolom x, dan angka 5 (milik z) diletakkan di kolom y.',
                    ];
                    $debugSelected = $selectedOptions($aktivitas14, 'debug_pernyataan');
                @endphp

                <div class="mt-4 space-y-3">
                    @foreach ($debugOptions as $optionKey => $optionText)
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border p-4 transition {{ $checkboxClass($aktivitas14, 'debug_pernyataan', $optionKey) }}">
                            <input
                                type="checkbox"
                                name="answers[debug_pernyataan][]"
                                value="{{ $optionKey }}"
                                @checked(in_array($optionKey, $debugSelected, true))
                                @disabled($inputLocked($aktivitas14, 'debug_pernyataan'))
                                class="mt-1 rounded border-slate-300 text-cyan-600">

                            <span class="text-sm leading-6 text-slate-700">
                                {!! str_replace(
                                    ['variabel z', 'kolom z', 'variabel y', 'kolom x', 'kolom y'],
                                    ['variabel \\(z\\)', 'kolom \\(z\\)', 'variabel \\(y\\)', 'kolom \\(x\\)', 'kolom \\(y\\)'],
                                    e($optionText)
                                ) !!}
                            </span>
                        </label>
                    @endforeach
                </div>

                <x-practice-field-feedback :feedback="$aktivitas14['feedback']['debug_pernyataan'] ?? []" />

                <p class="mt-6 leading-7 text-slate-700">
                    Tugas Perbaikan 3B (Input Matriks): Sekarang, perbaiki kesalahan tersebut! Ketikkan Augmented Matrix yang benar dan bersih dari kesalahan pada kotak di bawah ini agar sistem kembali berjalan normal.
                </p>

                <div class="mt-5 overflow-x-auto">
                    <div class="mx-auto w-max rounded-2xl border border-slate-200 bg-white p-5">
                        <div class="grid gap-2" style="grid-template-columns: repeat(4, 64px);">
                            @foreach ($debugFields as $row)
                                @foreach ($row as $columnIndex => $fieldKey)
                                    <input
                                        type="text"
                                        name="answers[{{ $fieldKey }}]"
                                        value="{{ old('answers.' . $fieldKey, $inputValue($aktivitas14, $fieldKey)) }}"
                                        class="h-11 rounded-xl border text-center font-semibold text-slate-900 {{ $inputClass($aktivitas14, $fieldKey) }} {{ $columnIndex === 3 ? 'border-l-4 border-l-slate-700' : '' }}"
                                        @if ($inputLocked($aktivitas14, $fieldKey)) readonly @endif>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    @foreach (array_merge(...$debugFields) as $fieldKey)
                        <x-practice-field-feedback :feedback="$aktivitas14['feedback'][$fieldKey] ?? []" />
                    @endforeach
                </div>
            </article>

            @if (! $aktivitas14['is_completed'])
                <div class="flex justify-end border-t border-slate-200 pt-5">
                    <button type="submit" class="w-full rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-cyan-700 sm:w-auto">
                        Cek Jawaban Kasus 1, 2, dan 3
                    </button>
                </div>
            @endif
        </form>

        @if ($aktivitas14['is_completed'])
            <section class="mx-5 mb-5 rounded-2xl border {{ $aktivitas14['with_help'] ? 'border-indigo-200 bg-indigo-50' : 'border-green-200 bg-green-50' }} p-5 sm:mx-6 sm:mb-6" aria-label="Hasil Aktivitas 1.4">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $aktivitas14['with_help'] ? 'bg-indigo-100 text-indigo-700' : 'bg-green-100 text-green-700' }} text-xl font-black">
                            {{ $aktivitas14['with_help'] ? 'i' : '✓' }}
                        </div>

                        <div>
                            <p class="text-sm font-bold {{ $aktivitas14['with_help'] ? 'text-indigo-800' : 'text-green-800' }}">
                                Hasil Aktivitas 1.4
                            </p>

                            <p class="mt-1 text-sm leading-6 {{ $aktivitas14['with_help'] ? 'text-indigo-700' : 'text-green-700' }}">
                                {{ $aktivitas14['with_help']
                                    ? 'Aktivitas selesai dengan bantuan jawaban pada bagian yang belum tepat.'
                                    : 'Aktivitas telah diselesaikan secara mandiri.' }}
                            </p>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white/80 px-4 py-3 text-right shadow-sm">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">
                            Nilai Aktivitas
                        </p>

                        <p class="mt-1 text-2xl font-black {{ $aktivitas14['with_help'] ? 'text-indigo-700' : 'text-green-700' }}">
                            {{ $aktivitas14['submission']?->score }}/{{ $aktivitas14['submission']?->max_score }}
                        </p>
                    </div>
                </div>

                @if ($aktivitas14['with_help'])
                    <p class="mt-4 rounded-xl border border-indigo-200 bg-white/70 px-4 py-3 text-sm leading-6 text-indigo-800">
                        Poin hanya diperoleh dari nomor yang selesai benar secara mandiri sebelum jawaban bantuan ditampilkan.
                    </p>
                @endif
            </section>
        @endif
    </div>

    @if ($practiceModal)
        @php
            $modalStatus = $practiceModal['status'] ?? 'revision';
            $modalIsSuccess = $modalStatus === 'success';
            $modalIsIncomplete = $modalStatus === 'incomplete';
            $modalIsAssisted = $modalStatus === 'assisted';
            $modalMessages = is_array($practiceModal['feedback_messages'] ?? null)
                ? $practiceModal['feedback_messages']
                : [];
        @endphp

        <div
            x-data="{ showPracticeModal: true }"
            x-cloak
            x-show="showPracticeModal"
            x-transition.opacity
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/70 px-4 py-6 backdrop-blur-sm"
            role="dialog"
            aria-modal="true"
            aria-labelledby="subbab4-modal-title">

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
                            <p id="subbab4-modal-title" class="text-lg font-bold text-slate-900">
                                {{ $practiceModal['title'] ?? 'Hasil Pemeriksaan' }}
                            </p>

                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                {{ $practiceModal['message'] ?? '' }}
                            </p>
                        </div>
                    </div>

                    @if (! $modalIsIncomplete && ! empty($modalMessages))
                        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-800">
                                Perhatikan kembali:
                            </p>

                            <ul class="mt-3 space-y-2 text-sm leading-6 text-slate-600">
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
