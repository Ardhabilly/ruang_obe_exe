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
            'submission' => $submission,
            'answers' => $answers,
            'feedback' => $feedback,
            'meta' => $meta,
            'completed' => (bool) ($submission?->is_completed ?? false),
            'assisted' => ($meta['completion_mode'] ?? null) === 'bantuan',
            'attempts' => max(0, min(3, (int) ($meta['attempts'] ?? 0))),
        ];
    };

    $inputValue = function (array $state, string $field): string {
        return (string) ($state['answers'][$field] ?? '');
    };

    $inputClass = function (array $state, string $field): string {
        $fieldState = $state['feedback'][$field]['state'] ?? null;

        return match ($fieldState) {
            'correct' => 'border-green-500 bg-green-50 text-green-950 focus:border-green-500 focus:ring-green-500',
            'revealed' => 'border-indigo-400 bg-indigo-50 text-indigo-950 focus:border-indigo-500 focus:ring-indigo-500',
            'wrong' => 'border-red-500 bg-red-50 text-red-950 focus:border-red-500 focus:ring-red-500',
            'empty' => 'border-yellow-400 bg-yellow-50 text-yellow-950 focus:border-yellow-500 focus:ring-yellow-500',
            default => 'border-slate-300 bg-white text-slate-900 focus:border-cyan-500 focus:ring-cyan-500',
        };
    };

    $inputLocked = function (array $state, string $field): bool {
        $fieldFeedback = $state['feedback'][$field] ?? [];

        return ! empty($fieldFeedback['is_revealed'])
            || (! empty($fieldFeedback['is_correct']) && ($fieldFeedback['state'] ?? null) === 'correct');
    };

    $hasFeedback = function (array $state): bool {
        return ! empty($state['feedback']);
    };

    $feedbackSummary = function (array $state): ?string {
        if (empty($state['feedback'])) {
            return null;
        }

        if ($state['completed']) {
            return $state['assisted']
                ? 'Komponen selesai dengan bantuan. Jawaban yang belum tepat telah ditampilkan pada kolom terkait.'
                : 'Semua jawaban pada komponen ini sudah benar.';
        }

        return 'Periksa kembali kolom berwarna merah. Kolom kuning perlu dilengkapi dan tidak mengurangi kesempatan.';
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

<section class="space-y-8">
    <div class="space-y-4">
        <h2 class="text-2xl font-black text-slate-950">
            2.2 Jenis-Jenis Operasi Baris Elementer
        </h2>

        <p>
            Untuk memanipulasi baris matriks hingga mencapai bentuk eselon, hanya terdapat
            tiga jenis Operasi Baris Elementer yang diperbolehkan. Ketiga operasi tersebut
            dapat digunakan secara bertahap tanpa mengubah himpunan penyelesaian SPL.
        </p>
    </div>

    <div class="rounded-2xl border border-cyan-200 bg-cyan-50 p-6">
        <h3 class="text-xl font-black text-slate-950">
            Tiga Jenis Operasi Baris Elementer
        </h3>

        <div class="mt-5 grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-cyan-100 bg-white p-4">
                <p class="text-sm font-black text-cyan-700">1. Pertukaran Baris</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">Menukar posisi dua baris.</p>
            </div>

            <div class="rounded-2xl border border-cyan-100 bg-white p-4">
                <p class="text-sm font-black text-cyan-700">2. Perkalian Baris</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">Mengalikan semua elemen suatu baris dengan konstanta tak nol.</p>
            </div>

            <div class="rounded-2xl border border-cyan-100 bg-white p-4">
                <p class="text-sm font-black text-cyan-700">3. Penjumlahan Kelipatan Baris</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">Menambahkan kelipatan suatu baris ke baris target.</p>
            </div>
        </div>
    </div>

    <section class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-xl font-black text-slate-900">
                1. Pertukaran Dua Baris
            </h3>

            <p class="mt-3">
                Operasi ini mengizinkan kita menukar posisi dua baris di dalam matriks.
                Pertukaran baris sama artinya dengan menukar urutan penulisan persamaan,
                sehingga tidak mengubah nilai penyelesaian SPL.
            </p>

            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5 text-center">
                <p class="text-sm font-bold text-slate-600">Notasi Matematika</p>
                <div class="mt-2 text-2xl text-slate-950">
                    \[
                        B_i \leftrightarrow B_j
                    \]
                </div>
            </div>

            <p class="mt-5">
                Sebelum mengalikan baris dengan pecahan untuk memperoleh angka 1 utama,
                perhatikan elemen pada kolom yang sama di baris bawahnya. Apabila terdapat
                baris yang telah memiliki angka 1, pertukaran baris biasanya lebih cepat
                dilakukan.
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">Contoh Simulasi</p>
                    <h3 class="mt-1 text-xl font-black text-slate-950">
                        Pertukaran Baris untuk Memperoleh Pivot Awal
                    </h3>
                </div>

                <span class="w-fit rounded-full px-4 py-2 text-sm font-bold {{ $simPertukaran['completed'] ? ($simPertukaran['assisted'] ? 'bg-indigo-100 text-indigo-700' : 'bg-green-50 text-green-700') : 'bg-yellow-50 text-yellow-700' }}">
                    {{ $simPertukaran['completed'] ? ($simPertukaran['assisted'] ? 'Selesai dengan Bantuan' : 'Simulasi Selesai') : 'Perlu Dikerjakan' }}
                </span>
            </div>

            <p class="mt-4">
                Perhatikan matriks teraugmentasi berikut. Kita ingin memperoleh angka 1 utama
                pada kolom pertama Baris-1 karena elemen pembuka Baris-1 masih bernilai 0.
            </p>

            <div class="mt-5 overflow-x-auto">
                <div class="mx-auto w-max rounded-2xl border border-slate-200 bg-slate-50 px-6 py-4 text-2xl text-slate-950">
                    \[
                        \left[
                        \begin{array}{ccc|c}
                            0 & 1 & 3 & 9 \\
                            1 & 1 & 1 & 6 \\
                            2 & 1 & -1 & 3
                        \end{array}
                        \right]
                    \]
                </div>
            </div>

            <form method="POST" action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'contoh-simulasi-2-2-pertukaran']) }}" class="mt-6">
                @csrf

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">Baris yang diturunkan, \(i\)</span>
                        <input type="text" name="answers[baris_target]" value="{{ $inputValue($simPertukaran, 'baris_target') }}" @disabled($inputLocked($simPertukaran, 'baris_target'))
                               class="mt-2 h-11 w-full rounded-xl px-4 text-center font-bold {{ $inputClass($simPertukaran, 'baris_target') }}">
                    </label>

                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">Baris pengganti yang dinaikkan, \(j\)</span>
                        <input type="text" name="answers[baris_pengganti]" value="{{ $inputValue($simPertukaran, 'baris_pengganti') }}" @disabled($inputLocked($simPertukaran, 'baris_pengganti'))
                               class="mt-2 h-11 w-full rounded-xl px-4 text-center font-bold {{ $inputClass($simPertukaran, 'baris_pengganti') }}">
                    </label>
                </div>

                <label class="mt-4 block">
                    <span class="text-sm font-bold text-slate-700">Notasi operasi</span>
                    <input type="text" name="answers[notasi]" value="{{ $inputValue($simPertukaran, 'notasi') }}" @disabled($inputLocked($simPertukaran, 'notasi'))
                           placeholder="Tulis notasi pertukaran baris"
                           class="mt-2 h-11 w-full rounded-xl px-4 text-center font-bold {{ $inputClass($simPertukaran, 'notasi') }}">
                    <span class="mt-1 block text-xs text-slate-500">Gunakan bentuk umum \(B_i \leftrightarrow B_j\).</span>
                </label>

                <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <p class="mb-4 text-center text-sm font-black text-slate-700">Hasil matriks setelah operasi</p>

                    <div class="mx-auto grid w-max gap-2" style="grid-template-columns: repeat(4, 64px);">
                        @foreach ([
                            'hasil_11', 'hasil_12', 'hasil_13', 'hasil_14',
                            'hasil_21', 'hasil_22', 'hasil_23', 'hasil_24',
                            'hasil_31', 'hasil_32', 'hasil_33', 'hasil_34',
                        ] as $index => $field)
                            <input type="text" name="answers[{{ $field }}]" value="{{ $inputValue($simPertukaran, $field) }}" @disabled($inputLocked($simPertukaran, $field))
                                   class="h-11 rounded-xl px-2 text-center font-bold {{ ($index % 4) === 3 ? 'border-l-4 border-l-slate-700' : '' }} {{ $inputClass($simPertukaran, $field) }}">
                        @endforeach
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
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-xl font-black text-slate-900">
                2. Perkalian Baris dengan Konstanta Tak Nol
            </h3>

            <p class="mt-3">
                Operasi ini dilakukan dengan mengalikan seluruh elemen pada suatu baris dengan
                konstanta \(k\) yang tidak bernilai nol. Operasi ini biasa digunakan untuk
                mengubah elemen pembuka tak nol menjadi angka 1 utama.
            </p>

            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5 text-center">
                <p class="text-sm font-bold text-slate-600">Notasi Matematika</p>
                <div class="mt-2 text-2xl text-slate-950">
                    \[
                        B_i \leftarrow kB_i, \qquad k \ne 0
                    \]
                </div>
            </div>

            <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <h4 class="font-black text-amber-950">Logika Penentuan Operasi</h4>
                <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm leading-6 text-amber-900">
                    <li>Tentukan baris target yang elemen pembuka tak nolnya akan diubah menjadi 1 utama.</li>
                    <li>Tentukan konstanta pengali berupa kebalikan perkalian dari elemen target.</li>
                </ol>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">Contoh Simulasi A</p>
                        <h3 class="mt-1 text-lg font-black text-slate-950">Mengubah 2 menjadi 1 utama</h3>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $simPerkalianA['completed'] ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700' }}">{{ $simPerkalianA['completed'] ? 'Selesai' : 'Belum selesai' }}</span>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <div class="mx-auto w-max text-xl text-slate-950">
                        \[
                            \left[
                            \begin{array}{cc|c}
                                1 & -2 & 3 \\
                                0 & 2 & 8
                            \end{array}
                            \right]
                        \]
                    </div>
                </div>

                <p class="mt-4 text-sm leading-6 text-slate-700">Ubah elemen pembuka pada Baris-2 Kolom-2 menjadi 1 utama.</p>

                <form method="POST" action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'contoh-simulasi-2-2-perkalian-a']) }}" class="mt-5">
                    @csrf

                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="block text-sm font-bold text-slate-700">Baris target, \(i\)
                            <input type="text" name="answers[baris_target]" value="{{ $inputValue($simPerkalianA, 'baris_target') }}" @disabled($inputLocked($simPerkalianA, 'baris_target'))
                                   class="mt-2 h-10 w-full rounded-xl px-3 text-center {{ $inputClass($simPerkalianA, 'baris_target') }}">
                        </label>

                        <label class="block text-sm font-bold text-slate-700">Konstanta, \(k\)
                            <input type="text" name="answers[konstanta]" value="{{ $inputValue($simPerkalianA, 'konstanta') }}" @disabled($inputLocked($simPerkalianA, 'konstanta'))
                                   class="mt-2 h-10 w-full rounded-xl px-3 text-center {{ $inputClass($simPerkalianA, 'konstanta') }}">
                        </label>
                    </div>

                    <label class="mt-3 block text-sm font-bold text-slate-700">Notasi operasi
                        <input type="text" name="answers[notasi]" value="{{ $inputValue($simPerkalianA, 'notasi') }}" @disabled($inputLocked($simPerkalianA, 'notasi'))
                               placeholder="Tulis notasi operasi"
                               class="mt-2 h-10 w-full rounded-xl px-3 text-center {{ $inputClass($simPerkalianA, 'notasi') }}">
                    </label>

                    <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-bold text-slate-700">Baris-2 yang baru</p>
                        <div class="mt-3 grid grid-cols-3 gap-2">
                            @foreach (['hasil_21', 'hasil_22', 'hasil_23'] as $index => $field)
                                <input type="text" name="answers[{{ $field }}]" value="{{ $inputValue($simPerkalianA, $field) }}" @disabled($inputLocked($simPerkalianA, $field))
                                       class="h-10 rounded-xl px-2 text-center font-bold {{ $index === 2 ? 'border-l-4 border-l-slate-700' : '' }} {{ $inputClass($simPerkalianA, $field) }}">
                            @endforeach
                        </div>
                    </div>

                    @if ($hasFeedback($simPerkalianA))
                        <p class="mt-4 rounded-xl px-3 py-2 text-xs font-semibold {{ $simPerkalianA['completed'] ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-900' }}">{{ $feedbackSummary($simPerkalianA) }}</p>
                    @endif

                    @if (! $simPerkalianA['completed'])
                        <button type="submit" class="mt-4 w-full rounded-xl bg-cyan-600 px-4 py-3 text-sm font-black text-white hover:bg-cyan-700">Cek Simulasi</button>
                    @endif
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">Contoh Simulasi B</p>
                        <h3 class="mt-1 text-lg font-black text-slate-950">Mengubah pecahan menjadi 1 utama</h3>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $simPerkalianB['completed'] ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700' }}">{{ $simPerkalianB['completed'] ? 'Selesai' : 'Belum selesai' }}</span>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <div class="mx-auto w-max text-xl text-slate-950">
                        \[
                            \left[
                            \begin{array}{cc|c}
                                1 & 2 & 5 \\
                                0 & \frac{2}{3} & 4
                            \end{array}
                            \right]
                        \]
                    </div>
                </div>

                <p class="mt-4 text-sm leading-6 text-slate-700">Ubah elemen pembuka pada Baris-2 Kolom-2 menjadi 1 utama.</p>

                <form method="POST" action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'contoh-simulasi-2-2-perkalian-b']) }}" class="mt-5">
                    @csrf

                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="block text-sm font-bold text-slate-700">Baris target, \(i\)
                            <input type="text" name="answers[baris_target]" value="{{ $inputValue($simPerkalianB, 'baris_target') }}" @disabled($inputLocked($simPerkalianB, 'baris_target'))
                                   class="mt-2 h-10 w-full rounded-xl px-3 text-center {{ $inputClass($simPerkalianB, 'baris_target') }}">
                        </label>

                        <label class="block text-sm font-bold text-slate-700">Konstanta, \(k\)
                            <input type="text" name="answers[konstanta]" value="{{ $inputValue($simPerkalianB, 'konstanta') }}" @disabled($inputLocked($simPerkalianB, 'konstanta'))
                                   class="mt-2 h-10 w-full rounded-xl px-3 text-center {{ $inputClass($simPerkalianB, 'konstanta') }}">
                        </label>
                    </div>

                    <label class="mt-3 block text-sm font-bold text-slate-700">Notasi operasi
                        <input type="text" name="answers[notasi]" value="{{ $inputValue($simPerkalianB, 'notasi') }}" @disabled($inputLocked($simPerkalianB, 'notasi'))
                               placeholder="Tulis notasi operasi"
                               class="mt-2 h-10 w-full rounded-xl px-3 text-center {{ $inputClass($simPerkalianB, 'notasi') }}">
                    </label>

                    <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-bold text-slate-700">Baris-2 yang baru</p>
                        <div class="mt-3 grid grid-cols-3 gap-2">
                            @foreach (['hasil_21', 'hasil_22', 'hasil_23'] as $index => $field)
                                <input type="text" name="answers[{{ $field }}]" value="{{ $inputValue($simPerkalianB, $field) }}" @disabled($inputLocked($simPerkalianB, $field))
                                       class="h-10 rounded-xl px-2 text-center font-bold {{ $index === 2 ? 'border-l-4 border-l-slate-700' : '' }} {{ $inputClass($simPerkalianB, $field) }}">
                            @endforeach
                        </div>
                    </div>

                    @if ($hasFeedback($simPerkalianB))
                        <p class="mt-4 rounded-xl px-3 py-2 text-xs font-semibold {{ $simPerkalianB['completed'] ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-900' }}">{{ $feedbackSummary($simPerkalianB) }}</p>
                    @endif

                    @if (! $simPerkalianB['completed'])
                        <button type="submit" class="mt-4 w-full rounded-xl bg-cyan-600 px-4 py-3 text-sm font-black text-white hover:bg-cyan-700">Cek Simulasi</button>
                    @endif
                </form>
            </div>
        </div>
    </section>

    <section class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-xl font-black text-slate-900">
                3. Penjumlahan Baris dengan Kelipatan Baris Lain
            </h3>

            <p class="mt-3">
                Operasi ini digunakan untuk menambahkan kelipatan dari suatu baris ke baris lain.
                Tujuan utamanya adalah mengeliminasi atau mengenolkan elemen tertentu pada matriks.
            </p>

            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5 text-center">
                <p class="text-sm font-bold text-slate-600">Notasi Matematika</p>
                <div class="mt-2 text-2xl text-slate-950">
                    \[
                        B_i \leftarrow kB_j + B_i
                    \]
                </div>
            </div>

            <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <h4 class="font-black text-amber-950">Logika Penentuan Operasi</h4>
                <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm leading-6 text-amber-900">
                    <li>Tentukan baris target yang memuat elemen yang ingin diubah menjadi 0.</li>
                    <li>Tentukan baris acuan yang memiliki angka 1 utama pada kolom yang sama.</li>
                    <li>Tentukan pengali berupa nilai lawan dari elemen target yang akan dinolkan.</li>
                </ol>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">Contoh Simulasi A</p>
                        <h3 class="mt-1 text-lg font-black text-slate-950">Mengenolkan elemen 2 pada Baris-3</h3>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $simTambahA['completed'] ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700' }}">{{ $simTambahA['completed'] ? 'Selesai' : 'Belum selesai' }}</span>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <div class="mx-auto w-max text-xl text-slate-950">
                        \[
                            \left[
                            \begin{array}{ccc|c}
                                1 & 1 & 1 & 6 \\
                                0 & 1 & 3 & 9 \\
                                2 & 1 & -1 & 3
                            \end{array}
                            \right]
                        \]
                    </div>
                </div>

                <form method="POST" action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'contoh-simulasi-2-2-penjumlahan-a']) }}" class="mt-5">
                    @csrf

                    <div class="grid gap-3 sm:grid-cols-3">
                        <label class="block text-sm font-bold text-slate-700">Target, \(i\)
                            <input type="text" name="answers[baris_target]" value="{{ $inputValue($simTambahA, 'baris_target') }}" @disabled($inputLocked($simTambahA, 'baris_target'))
                                   class="mt-2 h-10 w-full rounded-xl px-3 text-center {{ $inputClass($simTambahA, 'baris_target') }}">
                        </label>
                        <label class="block text-sm font-bold text-slate-700">Acuan, \(j\)
                            <input type="text" name="answers[baris_acuan]" value="{{ $inputValue($simTambahA, 'baris_acuan') }}" @disabled($inputLocked($simTambahA, 'baris_acuan'))
                                   class="mt-2 h-10 w-full rounded-xl px-3 text-center {{ $inputClass($simTambahA, 'baris_acuan') }}">
                        </label>
                        <label class="block text-sm font-bold text-slate-700">Pengali, \(k\)
                            <input type="text" name="answers[konstanta]" value="{{ $inputValue($simTambahA, 'konstanta') }}" @disabled($inputLocked($simTambahA, 'konstanta'))
                                   class="mt-2 h-10 w-full rounded-xl px-3 text-center {{ $inputClass($simTambahA, 'konstanta') }}">
                        </label>
                    </div>

                    <label class="mt-3 block text-sm font-bold text-slate-700">Notasi operasi
                        <input type="text" name="answers[notasi]" value="{{ $inputValue($simTambahA, 'notasi') }}" @disabled($inputLocked($simTambahA, 'notasi'))
                               placeholder="Tulis notasi operasi"
                               class="mt-2 h-10 w-full rounded-xl px-3 text-center {{ $inputClass($simTambahA, 'notasi') }}">
                    </label>

                    <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-bold text-slate-700">Baris-3 yang baru</p>
                        <div class="mt-3 grid grid-cols-4 gap-2">
                            @foreach (['hasil_31', 'hasil_32', 'hasil_33', 'hasil_34'] as $index => $field)
                                <input type="text" name="answers[{{ $field }}]" value="{{ $inputValue($simTambahA, $field) }}" @disabled($inputLocked($simTambahA, $field))
                                       class="h-10 rounded-xl px-2 text-center font-bold {{ $index === 3 ? 'border-l-4 border-l-slate-700' : '' }} {{ $inputClass($simTambahA, $field) }}">
                            @endforeach
                        </div>
                    </div>

                    @if ($hasFeedback($simTambahA))
                        <p class="mt-4 rounded-xl px-3 py-2 text-xs font-semibold {{ $simTambahA['completed'] ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-900' }}">{{ $feedbackSummary($simTambahA) }}</p>
                    @endif

                    @if (! $simTambahA['completed'])
                        <button type="submit" class="mt-4 w-full rounded-xl bg-cyan-600 px-4 py-3 text-sm font-black text-white hover:bg-cyan-700">Cek Simulasi</button>
                    @endif
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">Contoh Simulasi B</p>
                        <h3 class="mt-1 text-lg font-black text-slate-950">Mengenolkan elemen \(\frac{1}{2}\) pada Baris-2</h3>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $simTambahB['completed'] ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700' }}">{{ $simTambahB['completed'] ? 'Selesai' : 'Belum selesai' }}</span>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <div class="mx-auto w-max text-xl text-slate-950">
                        \[
                            \left[
                            \begin{array}{ccc|c}
                                1 & 2 & -1 & 4 \\
                                \frac{1}{2} & 3 & 1 & 4 \\
                                0 & 1 & 3 & 5
                            \end{array}
                            \right]
                        \]
                    </div>
                </div>

                <form method="POST" action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'contoh-simulasi-2-2-penjumlahan-b']) }}" class="mt-5">
                    @csrf

                    <div class="grid gap-3 sm:grid-cols-3">
                        <label class="block text-sm font-bold text-slate-700">Target, \(i\)
                            <input type="text" name="answers[baris_target]" value="{{ $inputValue($simTambahB, 'baris_target') }}" @disabled($inputLocked($simTambahB, 'baris_target'))
                                   class="mt-2 h-10 w-full rounded-xl px-3 text-center {{ $inputClass($simTambahB, 'baris_target') }}">
                        </label>
                        <label class="block text-sm font-bold text-slate-700">Acuan, \(j\)
                            <input type="text" name="answers[baris_acuan]" value="{{ $inputValue($simTambahB, 'baris_acuan') }}" @disabled($inputLocked($simTambahB, 'baris_acuan'))
                                   class="mt-2 h-10 w-full rounded-xl px-3 text-center {{ $inputClass($simTambahB, 'baris_acuan') }}">
                        </label>
                        <label class="block text-sm font-bold text-slate-700">Pengali, \(k\)
                            <input type="text" name="answers[konstanta]" value="{{ $inputValue($simTambahB, 'konstanta') }}" @disabled($inputLocked($simTambahB, 'konstanta'))
                                   class="mt-2 h-10 w-full rounded-xl px-3 text-center {{ $inputClass($simTambahB, 'konstanta') }}">
                        </label>
                    </div>

                    <label class="mt-3 block text-sm font-bold text-slate-700">Notasi operasi
                        <input type="text" name="answers[notasi]" value="{{ $inputValue($simTambahB, 'notasi') }}" @disabled($inputLocked($simTambahB, 'notasi'))
                               placeholder="Tulis notasi operasi"
                               class="mt-2 h-10 w-full rounded-xl px-3 text-center {{ $inputClass($simTambahB, 'notasi') }}">
                    </label>

                    <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-bold text-slate-700">Baris-2 yang baru</p>
                        <div class="mt-3 grid grid-cols-4 gap-2">
                            @foreach (['hasil_21', 'hasil_22', 'hasil_23', 'hasil_24'] as $index => $field)
                                <input type="text" name="answers[{{ $field }}]" value="{{ $inputValue($simTambahB, $field) }}" @disabled($inputLocked($simTambahB, $field))
                                       class="h-10 rounded-xl px-2 text-center font-bold {{ $index === 3 ? 'border-l-4 border-l-slate-700' : '' }} {{ $inputClass($simTambahB, $field) }}">
                            @endforeach
                        </div>
                    </div>

                    @if ($hasFeedback($simTambahB))
                        <p class="mt-4 rounded-xl px-3 py-2 text-xs font-semibold {{ $simTambahB['completed'] ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-900' }}">{{ $feedbackSummary($simTambahB) }}</p>
                    @endif

                    @if (! $simTambahB['completed'])
                        <button type="submit" class="mt-4 w-full rounded-xl bg-cyan-600 px-4 py-3 text-sm font-black text-white hover:bg-cyan-700">Cek Simulasi</button>
                    @endif
                </form>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">Aktivitas 2.1</p>
                <h3 class="mt-1 text-2xl font-black text-slate-950">
                    Latihan Mandiri Operasi Baris Elementer
                </h3>
                <p class="mt-2 text-slate-600">
                    Terapkan jenis OBE yang sesuai, tentukan komponennya, kemudian isi hasil pada baris yang berubah.
                </p>
            </div>

            <span class="w-fit rounded-full px-4 py-2 text-sm font-bold {{ $aktivitas21['completed'] ? ($aktivitas21['assisted'] ? 'bg-indigo-100 text-indigo-700' : 'bg-green-50 text-green-700') : 'bg-yellow-50 text-yellow-700' }}">
                {{ $aktivitas21['completed'] ? ($aktivitas21['assisted'] ? 'Selesai dengan Bantuan' : 'Aktivitas Selesai') : 'Perlu Diselesaikan' }}
            </span>
        </div>

        <form method="POST" action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'aktivitas-2-1-obe']) }}" class="mt-6 space-y-8">
            @csrf

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <h4 class="text-lg font-black text-slate-950">Kasus 1: Pertukaran Dua Baris</h4>
                <p class="mt-2 text-sm leading-6 text-slate-700">Tukar Baris-1 yang diawali 0 dengan baris di bawahnya yang sudah diawali angka 1.</p>

                <div class="mt-4 overflow-x-auto">
                    <div class="mx-auto w-max text-xl text-slate-950">
                        \[
                            \left[
                            \begin{array}{ccc|c}
                                0 & 2 & -1 & 4 \\
                                3 & -1 & 5 & 2 \\
                                1 & 4 & 2 & 7
                            \end{array}
                            \right]
                        \]
                    </div>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-3">
                    @foreach ([
                        'k1_i' => 'Baris target, \(i\)',
                        'k1_j' => 'Baris pengganti, \(j\)',
                        'k1_notasi' => 'Notasi operasi',
                    ] as $field => $label)
                        <label class="block text-sm font-bold text-slate-700">{{ $label }}
                            <input type="text" name="answers[{{ $field }}]" value="{{ $inputValue($aktivitas21, $field) }}" @disabled($inputLocked($aktivitas21, $field))
                                   class="mt-2 h-10 w-full rounded-xl px-3 text-center {{ $inputClass($aktivitas21, $field) }}">
                        </label>
                    @endforeach
                </div>

                <div class="mt-5 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="mb-3 text-sm font-bold text-slate-700">Hasil matriks setelah operasi</p>
                    <div class="mx-auto grid w-max gap-2" style="grid-template-columns: repeat(4, 64px);">
                        @foreach ([
                            'k1_11', 'k1_12', 'k1_13', 'k1_14',
                            'k1_21', 'k1_22', 'k1_23', 'k1_24',
                            'k1_31', 'k1_32', 'k1_33', 'k1_34',
                        ] as $index => $field)
                            <input type="text" name="answers[{{ $field }}]" value="{{ $inputValue($aktivitas21, $field) }}" @disabled($inputLocked($aktivitas21, $field))
                                   class="h-10 rounded-xl px-2 text-center font-bold {{ ($index % 4) === 3 ? 'border-l-4 border-l-slate-700' : '' }} {{ $inputClass($aktivitas21, $field) }}">
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <h4 class="text-lg font-black text-slate-950">Kasus 2a: Perkalian Konstanta Tak Nol</h4>
                    <div class="mt-4 overflow-x-auto">
                        <div class="mx-auto w-max text-xl text-slate-950">
                            \[
                                \left[
                                \begin{array}{ccc|c}
                                    1 & 3 & -4 & 7 \\
                                    0 & -3 & 6 & -9 \\
                                    2 & 1 & 5 & 4
                                \end{array}
                                \right]
                            \]
                        </div>
                    </div>

                    <p class="mt-3 text-sm leading-6 text-slate-700">Ubah elemen pertama yang tidak bernilai 0 pada Baris-2 menjadi 1 utama.</p>

                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        @foreach ([
                            'k2a_i' => 'Baris target, \(i\)',
                            'k2a_k' => 'Konstanta, \(k\)',
                            'k2a_notasi' => 'Notasi operasi',
                        ] as $field => $label)
                            <label class="block text-sm font-bold text-slate-700">{{ $label }}
                                <input type="text" name="answers[{{ $field }}]" value="{{ $inputValue($aktivitas21, $field) }}" @disabled($inputLocked($aktivitas21, $field))
                                       class="mt-2 h-10 w-full rounded-xl px-3 text-center {{ $inputClass($aktivitas21, $field) }}">
                            </label>
                        @endforeach
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                        <p class="text-sm font-bold text-slate-700">Baris-2 yang baru</p>
                        <div class="mt-3 grid grid-cols-4 gap-2">
                            @foreach (['k2a_21', 'k2a_22', 'k2a_23', 'k2a_24'] as $index => $field)
                                <input type="text" name="answers[{{ $field }}]" value="{{ $inputValue($aktivitas21, $field) }}" @disabled($inputLocked($aktivitas21, $field))
                                       class="h-10 rounded-xl px-2 text-center font-bold {{ $index === 3 ? 'border-l-4 border-l-slate-700' : '' }} {{ $inputClass($aktivitas21, $field) }}">
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <h4 class="text-lg font-black text-slate-950">Kasus 2b: Perkalian dengan Pecahan</h4>
                    <div class="mt-4 overflow-x-auto">
                        <div class="mx-auto w-max text-xl text-slate-950">
                            \[
                                \left[
                                \begin{array}{ccc|c}
                                    1 & 2 & -1 & 5 \\
                                    0 & -\frac{3}{4} & 3 & -6 \\
                                    -2 & 1 & 4 & 8
                                \end{array}
                                \right]
                            \]
                        </div>
                    </div>

                    <p class="mt-3 text-sm leading-6 text-slate-700">Ubah elemen pertama yang tidak bernilai 0 pada Baris-2 menjadi 1 utama.</p>

                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        @foreach ([
                            'k2b_i' => 'Baris target, \(i\)',
                            'k2b_k' => 'Konstanta, \(k\)',
                            'k2b_notasi' => 'Notasi operasi',
                        ] as $field => $label)
                            <label class="block text-sm font-bold text-slate-700">{{ $label }}
                                <input type="text" name="answers[{{ $field }}]" value="{{ $inputValue($aktivitas21, $field) }}" @disabled($inputLocked($aktivitas21, $field))
                                       class="mt-2 h-10 w-full rounded-xl px-3 text-center {{ $inputClass($aktivitas21, $field) }}">
                            </label>
                        @endforeach
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                        <p class="text-sm font-bold text-slate-700">Baris-2 yang baru</p>
                        <div class="mt-3 grid grid-cols-4 gap-2">
                            @foreach (['k2b_21', 'k2b_22', 'k2b_23', 'k2b_24'] as $index => $field)
                                <input type="text" name="answers[{{ $field }}]" value="{{ $inputValue($aktivitas21, $field) }}" @disabled($inputLocked($aktivitas21, $field))
                                       class="h-10 rounded-xl px-2 text-center font-bold {{ $index === 3 ? 'border-l-4 border-l-slate-700' : '' }} {{ $inputClass($aktivitas21, $field) }}">
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <h4 class="text-lg font-black text-slate-950">Kasus 3a: Penjumlahan Kelipatan Baris</h4>
                    <div class="mt-4 overflow-x-auto">
                        <div class="mx-auto w-max text-xl text-slate-950">
                            \[
                                \left[
                                \begin{array}{cc|c}
                                    1 & 4 & 7 \\
                                    3 & -2 & 5
                                \end{array}
                                \right]
                            \]
                        </div>
                    </div>

                    <p class="mt-3 text-sm leading-6 text-slate-700">Gunakan OBE untuk mengenolkan Baris-2 Kolom-1.</p>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ([
                            'k3a_i' => 'Baris target, \(i\)',
                            'k3a_j' => 'Baris acuan, \(j\)',
                            'k3a_k' => 'Konstanta, \(k\)',
                            'k3a_notasi' => 'Notasi operasi',
                        ] as $field => $label)
                            <label class="block text-sm font-bold text-slate-700">{{ $label }}
                                <input type="text" name="answers[{{ $field }}]" value="{{ $inputValue($aktivitas21, $field) }}" @disabled($inputLocked($aktivitas21, $field))
                                       class="mt-2 h-10 w-full rounded-xl px-3 text-center {{ $inputClass($aktivitas21, $field) }}">
                            </label>
                        @endforeach
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                        <p class="text-sm font-bold text-slate-700">Baris-2 yang baru</p>
                        <div class="mt-3 grid grid-cols-3 gap-2">
                            @foreach (['k3a_21', 'k3a_22', 'k3a_23'] as $index => $field)
                                <input type="text" name="answers[{{ $field }}]" value="{{ $inputValue($aktivitas21, $field) }}" @disabled($inputLocked($aktivitas21, $field))
                                       class="h-10 rounded-xl px-2 text-center font-bold {{ $index === 2 ? 'border-l-4 border-l-slate-700' : '' }} {{ $inputClass($aktivitas21, $field) }}">
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <h4 class="text-lg font-black text-slate-950">Kasus 3b: Penjumlahan Kelipatan dengan Pecahan</h4>
                    <div class="mt-4 overflow-x-auto">
                        <div class="mx-auto w-max text-xl text-slate-950">
                            \[
                                \left[
                                \begin{array}{cc|c}
                                    1 & 6 & -3 \\
                                    -\frac{2}{3} & 5 & 4
                                \end{array}
                                \right]
                            \]
                        </div>
                    </div>

                    <p class="mt-3 text-sm leading-6 text-slate-700">Gunakan OBE untuk mengenolkan Baris-2 Kolom-1.</p>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ([
                            'k3b_i' => 'Baris target, \(i\)',
                            'k3b_j' => 'Baris acuan, \(j\)',
                            'k3b_k' => 'Konstanta, \(k\)',
                            'k3b_notasi' => 'Notasi operasi',
                        ] as $field => $label)
                            <label class="block text-sm font-bold text-slate-700">{{ $label }}
                                <input type="text" name="answers[{{ $field }}]" value="{{ $inputValue($aktivitas21, $field) }}" @disabled($inputLocked($aktivitas21, $field))
                                       class="mt-2 h-10 w-full rounded-xl px-3 text-center {{ $inputClass($aktivitas21, $field) }}">
                            </label>
                        @endforeach
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                        <p class="text-sm font-bold text-slate-700">Baris-2 yang baru</p>
                        <div class="mt-3 grid grid-cols-3 gap-2">
                            @foreach (['k3b_21', 'k3b_22', 'k3b_23'] as $index => $field)
                                <input type="text" name="answers[{{ $field }}]" value="{{ $inputValue($aktivitas21, $field) }}" @disabled($inputLocked($aktivitas21, $field))
                                       class="h-10 rounded-xl px-2 text-center font-bold {{ $index === 2 ? 'border-l-4 border-l-slate-700' : '' }} {{ $inputClass($aktivitas21, $field) }}">
                            @endforeach
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
            Pada setiap OBE, seluruh elemen pada baris yang dikenai operasi harus diproses
            secara konsisten, termasuk elemen konstanta pada ruas kanan matriks teraugmentasi.
        </p>
    </div>
</section>

@if ($practiceModal)
    <div x-data="{ open: true }" x-show="open" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" @click="open = false"></div>

        <div class="relative w-full max-w-md rounded-3xl border border-white/20 bg-white p-6 shadow-2xl">
            <p class="text-lg font-black text-slate-950">{{ $practiceModal['title'] ?? 'Hasil Pemeriksaan' }}</p>
            <p class="mt-3 text-sm leading-6 text-slate-600">{{ $practiceModal['message'] ?? '' }}</p>

            @foreach (($practiceModal['feedback_messages'] ?? []) as $message)
                <p class="mt-3 rounded-xl bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-900">{{ $message }}</p>
            @endforeach

            <button type="button" @click="open = false" class="mt-5 w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-black text-white">
                {{ $practiceModal['button_label'] ?? 'Tutup' }}
            </button>
        </div>
    </div>
@endif