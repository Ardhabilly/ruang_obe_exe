@php
    $practiceModalPayload = session('practice_modal');
    $practiceModalPayload = is_array($practiceModalPayload) ? $practiceModalPayload : null;

    $cekPemahaman12 = $practiceSubmissions->get('cek-pemahaman-1-2');
    $aktivitas12 = $practiceSubmissions->get('aktivitas-1-2-server');

    $practiceUsesComponentAttemptScope = function ($submission): bool {
        $raw = is_array($submission?->feedback) ? $submission->feedback : [];

        return ($raw['_meta']['attempt_scope'] ?? null) === 'component';
    };

    $practiceAnswers = function ($submission) use ($practiceUsesComponentAttemptScope): array {
        $stored = $practiceUsesComponentAttemptScope($submission) && is_array($submission?->answers)
            ? $submission->answers
            : [];
        $oldAnswers = old('answers', []);
        $oldAnswers = is_array($oldAnswers) ? $oldAnswers : [];

        return array_replace($stored, $oldAnswers);
    };

    $practiceFeedback = function ($submission) use ($practiceUsesComponentAttemptScope): array {
        if (! $practiceUsesComponentAttemptScope($submission)) {
            return [];
        }

        $raw = is_array($submission?->feedback) ? $submission->feedback : [];

        return is_array($raw['fields'] ?? null)
            ? $raw['fields']
            : collect($raw)
                ->except(['_meta', 'groups', 'fields'])
                ->filter(fn ($item) => is_array($item))
                ->all();
    };

    $practiceMeta = function ($submission) use ($practiceUsesComponentAttemptScope): array {
        if (! $practiceUsesComponentAttemptScope($submission)) {
            return [];
        }

        $raw = is_array($submission?->feedback) ? $submission->feedback : [];

        return is_array($raw['_meta'] ?? null) ? $raw['_meta'] : [];
    };

    $cekAnswers = $practiceAnswers($cekPemahaman12);
    $cekFeedback = $practiceFeedback($cekPemahaman12);
    $cekMeta = $practiceMeta($cekPemahaman12);
    $cekGroups = is_array($cekMeta['groups'] ?? null) ? $cekMeta['groups'] : [];
    $cekSelesai = $practiceUsesComponentAttemptScope($cekPemahaman12) && (bool) ($cekPemahaman12?->is_completed ?? false);
    $cekDenganBantuan = ($cekMeta['completion_mode'] ?? null) === 'bantuan';

    $aktivitas12Answers = $practiceAnswers($aktivitas12);
    $aktivitas12Feedback = $practiceFeedback($aktivitas12);
    $aktivitas12Meta = $practiceMeta($aktivitas12);
    $aktivitas12Groups = is_array($aktivitas12Meta['groups'] ?? null) ? $aktivitas12Meta['groups'] : [];
    $aktivitas12Selesai = $practiceUsesComponentAttemptScope($aktivitas12) && (bool) ($aktivitas12?->is_completed ?? false);
    $aktivitas12DenganBantuan = ($aktivitas12Meta['completion_mode'] ?? null) === 'bantuan';

    $inputClass = function (array $feedback, string $fieldKey): string {
        $state = $feedback[$fieldKey]['state'] ?? null;

        return match ($state) {
            'correct' => 'border-green-500 bg-green-50 focus:border-green-500 focus:ring-green-500',
            'revealed' => 'border-indigo-400 bg-indigo-50 text-indigo-950 focus:border-indigo-500 focus:ring-indigo-500',
            'wrong' => 'border-red-500 bg-red-50 focus:border-red-500 focus:ring-red-500',
            'empty' => 'border-yellow-400 bg-yellow-50 focus:border-yellow-500 focus:ring-yellow-500',
            default => 'border-slate-300 bg-white focus:border-cyan-500 focus:ring-cyan-500',
        };
    };

    $inputLocked = function (array $feedback, string $fieldKey): bool {
        $field = $feedback[$fieldKey] ?? [];

        return ! empty($field['is_revealed'])
            || (! empty($field['is_correct']) && ($field['state'] ?? null) === 'correct');
    };

    $groupMessage = function (array $groups, string $groupKey): ?string {
        $group = $groups[$groupKey] ?? null;

        if (! is_array($group)) {
            return null;
        }

        return match ($group['status'] ?? null) {
            'passed' => 'Jawaban pada nomor ini sudah benar.',
            default => null,
        };
    };

    $componentAttemptInfo = function (array $meta, bool $isCompleted): array {
        $maxAttempts = max(1, (int) ($meta['max_attempts'] ?? 3));
        $attempts = max(0, min($maxAttempts, (int) ($meta['attempts'] ?? 0)));

        return [
            'max' => $maxAttempts,
            'used' => $attempts,
            'remaining' => max(0, $maxAttempts - $attempts),
            'show' => ! $isCompleted,
        ];
    };

    $cekAttemptInfo = $componentAttemptInfo($cekMeta, $cekSelesai);
    $aktivitas12AttemptInfo = $componentAttemptInfo($aktivitas12Meta, $aktivitas12Selesai);

    $cekInputClass = fn (string $fieldKey): string => $inputClass($cekFeedback, $fieldKey);
    $cekInputLocked = fn (string $fieldKey): bool => $inputLocked($cekFeedback, $fieldKey);
    $aktivitas12InputClass = fn (string $fieldKey): string => $inputClass($aktivitas12Feedback, $fieldKey);
    $aktivitas12InputLocked = fn (string $fieldKey): bool => $inputLocked($aktivitas12Feedback, $fieldKey);

    $aktivitas12CheckboxClass = function (string $optionKey) use ($aktivitas12Feedback): string {
        $state = $aktivitas12Feedback['q5_pernyataan']['option_statuses'][$optionKey]['state'] ?? 'neutral';

        return match ($state) {
            'correct' => 'border-green-500 bg-green-50',
            'revealed' => 'border-indigo-400 bg-indigo-50',
            'missing' => 'border-yellow-400 bg-yellow-50',
            'wrong' => 'border-red-500 bg-red-50',
            default => 'border-slate-200 bg-white hover:bg-cyan-50',
        };
    };

    $cekModal = ($practiceModalPayload['practice_key'] ?? null) === 'cek-pemahaman-1-2'
        ? $practiceModalPayload
        : null;

    $aktivitasModal = ($practiceModalPayload['practice_key'] ?? null) === 'aktivitas-1-2-server'
        ? $practiceModalPayload
        : null;
@endphp

<section class="space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        {{-- <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">
            1.1 BENTUK UMUM SISTEM PERSAMAAN LINEAR
        </p> --}}

        <p class="mt-4 leading-7 text-slate-700">
            Ketika sebuah masalah di dunia nyata melibatkan banyak hal yang saling memengaruhi, satu persamaan saja tidak akan cukup untuk menemukan jawaban pastinya. Kita butuh sekumpulan persamaan yang bekerja bersama-sama sekaligus untuk menemukan nilai dari variabel-variabel tersebut. Kumpulan dari beberapa persamaan linear yang saling berkaitan inilah yang kita sebut sebagai Sistem Persamaan Linear (SPL).
        </p>

        <p class="mt-4 leading-7 text-slate-700">
            Suatu Sistem Persamaan Linear dengan \(m\) persamaan dan \(n\) variabel dapat dituliskan dalam bentuk umum sebagai berikut:
        </p>

        <div class="mt-5 overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-6">
            <div class="min-w-[620px] text-center text-lg font-normal text-slate-950">
                \[
                    \begin{aligned}
                        a_{11}x_1 + a_{12}x_2 + \cdots + a_{1n}x_n &= b_1 \\
                        a_{21}x_1 + a_{22}x_2 + \cdots + a_{2n}x_n &= b_2 \\
                        &\vdots \\
                        a_{m1}x_1 + a_{m2}x_2 + \cdots + a_{mn}x_n &= b_m
                    \end{aligned}
                \]
            </div>
        </div>

        <p class="mt-5 leading-7 text-slate-700">
            Pada bentuk tersebut:
        </p>

        <ul class="mt-3 list-disc space-y-2 pl-6 leading-7 text-slate-700">
            <li>\(a_{ij}\) dengan \(i\) (baris) \(= 1, 2, \ldots, m\) dan \(j\) (kolom) \(= 1, 2, \ldots, n\) adalah Koefisien</li>
            <li>\(x_1, x_2, \ldots, x_n\) adalah Variabel</li>
            <li>\(b_1, b_2, \ldots, b_m\) adalah Konstanta</li>
            <li>Pada setiap baris persamaan, koefisien \(a_{i1}, a_{i2}, \ldots, a_{in}\) tidak semuanya nol</li>
        </ul>

        <p class="mt-4 leading-7 text-slate-700">
            Jumlah persamaan dan jumlah variabel tidak harus sama, sehingga karakteristik solusi SPL dapat berbeda-beda.
        </p>

        <p class="mt-4 leading-7 text-slate-700">
            Dalam aljabar, jawaban atau solusi dari sebuah sistem sangat dipengaruhi oleh angka-angka hasil (konstanta) di ruas kanan. Berdasarkan nilai konstanta ini, sistem dibagi menjadi dua jenis:
        </p>

        <ol class="mt-4 list-decimal space-y-4 pl-6 leading-7 text-slate-700">
            <li>
                <span class="font-semibold">Sistem Persamaan Linear Homogen:</span>
                Sistem disebut homogen jika semua konstanta di ruas kanan bernilai nol \(b_1 = 0, b_2 = 0, \ldots, b_m = 0\). Sistem jenis ini sangat spesial karena dijamin pasti memiliki minimal satu jawaban, yaitu ketika semua variabelnya bernilai nol \(x_1 = 0, x_2 = 0, \ldots, x_n = 0\). Jawaban ini biasa disebut sebagai solusi trivial.
            </li>
            <li>
                <span class="font-semibold">Sistem Persamaan Linear Non-Homogen:</span>
                Sistem disebut non-homogen jika ada minimal satu saja konstanta di ruas kanan yang nilainya bukan nol \(b_i \ne 0\).
            </li>
        </ol>
    </div>

    <div class="rounded-2xl border border-cyan-200 bg-cyan-50 p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <p class="text-base font-semibold leading-7 text-slate-800">
                Cek Pemahaman: Sambil membaca materi di atas, mari uji pemahaman Anda dalam membedah bagian-bagian Sistem Persamaan Linear (SPL). Perhatikan dengan saksama sistem persamaan linear berikut ini:
            </p>

            <span class="w-fit shrink-0 rounded-full px-4 py-2 text-sm font-bold {{ $cekSelesai ? ($cekDenganBantuan ? 'bg-indigo-100 text-indigo-700' : 'bg-green-100 text-green-700') : 'bg-cyan-100 text-cyan-700' }}">
                {{ $cekSelesai ? ($cekDenganBantuan ? 'Selesai dengan Bantuan' : 'Cek Pemahaman Selesai') : 'Cek Pemahaman' }}
            </span>
        </div>

        @if ($cekAttemptInfo['show'])
            <div class="mt-5 flex flex-col gap-2 rounded-2xl border border-cyan-200 bg-white/80 px-4 py-3 text-sm text-cyan-950 sm:flex-row sm:items-center sm:justify-between">
                <p class="font-bold">
                    Kesempatan tersisa: {{ $cekAttemptInfo['remaining'] }} dari {{ $cekAttemptInfo['max'] }}
                </p>

                <p class="text-xs leading-5 text-cyan-800">
                    Berlaku untuk seluruh Cek Pemahaman. Kolom yang belum diisi tidak mengurangi kesempatan.
                </p>
            </div>
        @endif

        <div class="mt-5 overflow-x-auto rounded-2xl border border-cyan-200 bg-white p-5">
            <div class="min-w-[300px] text-center text-lg font-normal text-slate-950">
                \[
                    \begin{aligned}
                        3x - 2y + 5z &= 14 \\
                        x + 4y - z &= 0 \\
                        -5x + z &= -7
                    \end{aligned}
                \]
            </div>
        </div>

        <p class="mt-5 leading-7 text-slate-700">
            Lengkapi bagian yang kosong di bawah ini berdasarkan sistem di atas untuk memastikan Anda telah memahami strukturnya!
        </p>

        <form action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'cek-pemahaman-1-2']) }}" method="POST" data-preserve-scroll="true" class="mt-6 space-y-5">
            @csrf

            <div class="rounded-2xl border border-cyan-200 bg-white p-5">
                <p class="leading-7 text-slate-800">
                    1. Menghitung Ukuran Sistem: Berdasarkan bentuk umumnya, sistem di atas terdiri dari \(m =\)
                    <input type="text" name="answers[m]" value="{{ $cekAnswers['m'] ?? '' }}" class="mx-1 inline-block w-20 rounded-lg border px-2 py-1 text-center text-slate-900 {{ $cekInputClass('m') }}" @if ($cekInputLocked('m')) readonly @endif>
                    persamaan dan melibatkan \(n =\)
                    <input type="text" name="answers[n]" value="{{ $cekAnswers['n'] ?? '' }}" class="mx-1 inline-block w-20 rounded-lg border px-2 py-1 text-center text-slate-900 {{ $cekInputClass('n') }}" @if ($cekInputLocked('n')) readonly @endif>
                    variabel (\(x\), \(y\), dan \(z\)).
                </p>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <x-practice-field-feedback :feedback="$cekFeedback['m'] ?? []" />
                    <x-practice-field-feedback :feedback="$cekFeedback['n'] ?? []" />
                </div>

                @if ($groupMessage($cekGroups, 'q1'))
                    <p class="mt-4 text-sm font-semibold {{ ($cekGroups['q1']['status'] ?? null) === 'assisted' ? 'text-indigo-700' : 'text-slate-600' }}">
                        {{ $groupMessage($cekGroups, 'q1') }}
                    </p>
                @endif
            </div>

            <div class="rounded-2xl border border-cyan-200 bg-white p-5">
                <p class="leading-7 text-slate-800">
                    2. Membaca Alamat Koefisien (\(a_{ij}\)):
                </p>

                <ul class="mt-3 space-y-3 pl-5 leading-7 text-slate-800">
                    <li>
                        Nilai dari \(a_{12}\) adalah
                        <input type="text" name="answers[a12]" value="{{ $cekAnswers['a12'] ?? '' }}" class="mx-1 inline-block w-24 rounded-lg border px-2 py-1 text-center text-slate-900 {{ $cekInputClass('a12') }}" @if ($cekInputLocked('a12')) readonly @endif>
                    </li>
                    <li>
                        Nilai dari \(a_{23}\) adalah
                        <input type="text" name="answers[a23]" value="{{ $cekAnswers['a23'] ?? '' }}" class="mx-1 inline-block w-24 rounded-lg border px-2 py-1 text-center text-slate-900 {{ $cekInputClass('a23') }}" @if ($cekInputLocked('a23')) readonly @endif>
                    </li>
                    <li>
                        Nilai dari \(a_{32}\) adalah
                        <input type="text" name="answers[a32]" value="{{ $cekAnswers['a32'] ?? '' }}" class="mx-1 inline-block w-24 rounded-lg border px-2 py-1 text-center text-slate-900 {{ $cekInputClass('a32') }}" @if ($cekInputLocked('a32')) readonly @endif>
                    </li>
                </ul>

                <div class="mt-4 grid gap-3">
                    <x-practice-field-feedback :feedback="$cekFeedback['a12'] ?? []" />
                    <x-practice-field-feedback :feedback="$cekFeedback['a23'] ?? []" />
                    <x-practice-field-feedback :feedback="$cekFeedback['a32'] ?? []" />
                </div>

                @if ($groupMessage($cekGroups, 'q2'))
                    <p class="mt-4 text-sm font-semibold {{ ($cekGroups['q2']['status'] ?? null) === 'assisted' ? 'text-indigo-700' : 'text-slate-600' }}">
                        {{ $groupMessage($cekGroups, 'q2') }}
                    </p>
                @endif
            </div>

            <div class="rounded-2xl border border-cyan-200 bg-white p-5">
                <p class="leading-7 text-slate-800">
                    3. Melihat Konstanta (\(b_m\)) dan Jenis Sistem
                </p>

                <ul class="mt-3 space-y-3 pl-5 leading-7 text-slate-800">
                    <li>
                        \(b_1 =\)
                        <input type="text" name="answers[b1]" value="{{ $cekAnswers['b1'] ?? '' }}" class="mx-1 inline-block w-24 rounded-lg border px-2 py-1 text-center text-slate-900 {{ $cekInputClass('b1') }}" @if ($cekInputLocked('b1')) readonly @endif>
                    </li>
                    <li>
                        \(b_2 =\)
                        <input type="text" name="answers[b2]" value="{{ $cekAnswers['b2'] ?? '' }}" class="mx-1 inline-block w-24 rounded-lg border px-2 py-1 text-center text-slate-900 {{ $cekInputClass('b2') }}" @if ($cekInputLocked('b2')) readonly @endif>
                    </li>
                    <li>
                        \(b_3 =\)
                        <input type="text" name="answers[b3]" value="{{ $cekAnswers['b3'] ?? '' }}" class="mx-1 inline-block w-24 rounded-lg border px-2 py-1 text-center text-slate-900 {{ $cekInputClass('b3') }}" @if ($cekInputLocked('b3')) readonly @endif>
                    </li>
                </ul>

                <p class="mt-4 leading-7 text-slate-800">
                    maka secara keseluruhan sistem ini termasuk ke dalam Sistem Persamaan Linear
                    <input type="text" name="answers[jenis]" value="{{ $cekAnswers['jenis'] ?? '' }}" class="mx-1 inline-block w-44 rounded-lg border px-2 py-1 text-center text-slate-900 {{ $cekInputClass('jenis') }}" @if ($cekInputLocked('jenis')) readonly @endif>
                </p>

                <div class="mt-4 grid gap-3">
                    <x-practice-field-feedback :feedback="$cekFeedback['b1'] ?? []" />
                    <x-practice-field-feedback :feedback="$cekFeedback['b2'] ?? []" />
                    <x-practice-field-feedback :feedback="$cekFeedback['b3'] ?? []" />
                    <x-practice-field-feedback :feedback="$cekFeedback['jenis'] ?? []" />
                </div>

                @if ($groupMessage($cekGroups, 'q3'))
                    <p class="mt-4 text-sm font-semibold {{ ($cekGroups['q3']['status'] ?? null) === 'assisted' ? 'text-indigo-700' : 'text-slate-600' }}">
                        {{ $groupMessage($cekGroups, 'q3') }}
                    </p>
                @endif
            </div>

            @if (! $cekSelesai)
                <button type="submit" class="rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-700">
                    Cek Pemahaman
                </button>
            @endif
        </form>
    </div>

    <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-gradient-to-br from-cyan-50 via-white to-slate-50 px-5 py-6 sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex min-w-0 items-start gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-cyan-100 text-sm font-black text-cyan-700">
                        1.2
                    </div>

                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-cyan-700">
                            Aktivitas
                        </p>

                        <h3 class="mt-1 text-xl font-bold text-slate-950">
                            Pemodelan Alokasi Sumber Daya Server
                        </h3>

                        <p class="mt-1 text-sm leading-6 text-slate-600">
                            Studi Kasus: Optimalisasi Pusat Data (Data Center)
                        </p>
                    </div>
                </div>

                <span class="w-fit shrink-0 rounded-full px-3.5 py-2 text-xs font-bold {{ $aktivitas12Selesai ? ($aktivitas12DenganBantuan ? 'bg-indigo-100 text-indigo-700' : 'bg-green-100 text-green-700') : 'bg-yellow-100 text-yellow-800' }}">
                    {{ $aktivitas12Selesai ? ($aktivitas12DenganBantuan ? 'Selesai dengan Bantuan' : 'Aktivitas Selesai') : 'Perlu Diselesaikan' }}
                </span>
            </div>
        </div>

        <div class="space-y-5 p-5 sm:p-6">
            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 shadow-sm sm:p-5">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
                    Studi Kasus
                </p>

                <p class="mt-3 leading-7 text-slate-700">
                    Sebuah perusahaan teknologi mengelola dua jenis layanan awan (<span class="italic">cloud services</span>), yaitu Layanan Web \((x)\) dan Layanan Basis Data \((y)\). Kedua layanan ini berjalan bersamaan menggunakan sumber daya berupa CPU dan RAM di dalam pusat data.
                </p>

                <p class="mt-4 leading-7 text-slate-700">
                    Perhatikan kebutuhan sumber daya untuk masing-masing layanan berikut:
                </p>

                <ul class="mt-4 grid gap-3 sm:grid-cols-3">
                    <li class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm leading-6 text-slate-700">
                        Setiap Layanan Web \((x)\) memerlukan 2 unit CPU dan 1 unit RAM.
                    </li>

                    <li class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm leading-6 text-slate-700">
                        Setiap Layanan Basis Data \((y)\) memerlukan 3 unit CPU dan 4 unit RAM.
                    </li>

                    <li class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm leading-6 text-slate-700">
                        Pada hari ini, sistem membatasi penggunaan total maksimal sebesar 40 unit CPU dan 65 unit RAM.
                    </li>
                </ul>
            </div>

            @if ($aktivitas12AttemptInfo['show'])
                <div class="rounded-2xl border border-cyan-200 bg-cyan-50 p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-bold text-cyan-950">
                                Kesempatan tersisa: {{ $aktivitas12AttemptInfo['remaining'] }} dari {{ $aktivitas12AttemptInfo['max'] }}
                            </p>

                            <p class="mt-1 text-xs leading-5 text-cyan-800">
                                Berlaku untuk seluruh Aktivitas 1.2. Kolom yang belum diisi tidak mengurangi kesempatan.
                            </p>
                        </div>

                        <span class="w-fit rounded-xl border border-cyan-200 bg-white px-3 py-1.5 text-xs font-semibold text-cyan-800">
                            Periksa seluruh jawaban
                        </span>
                    </div>
                </div>
            @endif

        <form action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'aktivitas-1-2-server']) }}" method="POST" data-preserve-scroll="true" class="mt-6 space-y-4">
            @csrf

            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 shadow-sm sm:p-5">
                <p class="leading-7 text-slate-800">
                    1. Berdasarkan jumlah batas sumber daya yang ada (CPU dan RAM) serta jenis layanan yang dikelola, maka model matematika ini akan membentuk sebuah sistem yang memiliki \(m =\)
                    <input type="text" name="answers[q1_jumlah_persamaan]" value="{{ $aktivitas12Answers['q1_jumlah_persamaan'] ?? '' }}" class="mx-1 inline-block w-20 rounded-lg border px-2 py-1 text-center text-slate-900 {{ $aktivitas12InputClass('q1_jumlah_persamaan') }}" @if ($aktivitas12InputLocked('q1_jumlah_persamaan')) readonly @endif>
                    persamaan dan \(n =\)
                    <input type="text" name="answers[q1_jumlah_variabel]" value="{{ $aktivitas12Answers['q1_jumlah_variabel'] ?? '' }}" class="mx-1 inline-block w-20 rounded-lg border px-2 py-1 text-center text-slate-900 {{ $aktivitas12InputClass('q1_jumlah_variabel') }}" @if ($aktivitas12InputLocked('q1_jumlah_variabel')) readonly @endif>
                    variabel.
                </p>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <x-practice-field-feedback :feedback="$aktivitas12Feedback['q1_jumlah_persamaan'] ?? []" />
                    <x-practice-field-feedback :feedback="$aktivitas12Feedback['q1_jumlah_variabel'] ?? []" />
                </div>

                @if ($groupMessage($aktivitas12Groups, 'q1'))
                    <p class="mt-4 text-sm font-semibold {{ ($aktivitas12Groups['q1']['status'] ?? null) === 'assisted' ? 'text-indigo-700' : 'text-slate-600' }}">{{ $groupMessage($aktivitas12Groups, 'q1') }}</p>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 shadow-sm sm:p-5">
                <label class="block leading-7 text-slate-800">
                    2. Tuliskan persamaan linear yang merepresentasikan total penggunaan CPU berdasarkan kebutuhan masing-masing layanan dan batas maksimal unit yang tersedia!
                    <input type="text" name="answers[q2_persamaan_cpu]" value="{{ $aktivitas12Answers['q2_persamaan_cpu'] ?? '' }}" class="mt-3 block w-full rounded-xl border px-3 py-2.5 text-slate-900 {{ $aktivitas12InputClass('q2_persamaan_cpu') }}" @if ($aktivitas12InputLocked('q2_persamaan_cpu')) readonly @endif>
                </label>

                <x-practice-field-feedback :feedback="$aktivitas12Feedback['q2_persamaan_cpu'] ?? []" />

                @if ($groupMessage($aktivitas12Groups, 'q2'))
                    <p class="mt-4 text-sm font-semibold {{ ($aktivitas12Groups['q2']['status'] ?? null) === 'assisted' ? 'text-indigo-700' : 'text-slate-600' }}">{{ $groupMessage($aktivitas12Groups, 'q2') }}</p>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 shadow-sm sm:p-5">
                <label class="block leading-7 text-slate-800">
                    3. Tuliskan persamaan linear yang merepresentasikan total penggunaan RAM berdasarkan kebutuhan masing-masing layanan dan batas maksimal unit yang tersedia!
                    <input type="text" name="answers[q3_persamaan_ram]" value="{{ $aktivitas12Answers['q3_persamaan_ram'] ?? '' }}" class="mt-3 block w-full rounded-xl border px-3 py-2.5 text-slate-900 {{ $aktivitas12InputClass('q3_persamaan_ram') }}" @if ($aktivitas12InputLocked('q3_persamaan_ram')) readonly @endif>
                </label>

                <x-practice-field-feedback :feedback="$aktivitas12Feedback['q3_persamaan_ram'] ?? []" />

                @if ($groupMessage($aktivitas12Groups, 'q3'))
                    <p class="mt-4 text-sm font-semibold {{ ($aktivitas12Groups['q3']['status'] ?? null) === 'assisted' ? 'text-indigo-700' : 'text-slate-600' }}">{{ $groupMessage($aktivitas12Groups, 'q3') }}</p>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 shadow-sm sm:p-5">
                <label class="block leading-7 text-slate-800">
                    4. Jika seluruh batas maksimal unit CPU dan RAM tiba-tiba diubah oleh sistem menjadi 0, maka jenis Sistem Persamaan Linear yang terbentuk otomatis berubah menjadi sistem
                    <input type="text" name="answers[q4_jenis_sistem]" value="{{ $aktivitas12Answers['q4_jenis_sistem'] ?? '' }}" class="mx-1 inline-block w-48 rounded-lg border px-2 py-1 text-center text-slate-900 {{ $aktivitas12InputClass('q4_jenis_sistem') }}" @if ($aktivitas12InputLocked('q4_jenis_sistem')) readonly @endif>
                </label>

                <x-practice-field-feedback :feedback="$aktivitas12Feedback['q4_jenis_sistem'] ?? []" />

                @if ($groupMessage($aktivitas12Groups, 'q4'))
                    <p class="mt-4 text-sm font-semibold {{ ($aktivitas12Groups['q4']['status'] ?? null) === 'assisted' ? 'text-indigo-700' : 'text-slate-600' }}">{{ $groupMessage($aktivitas12Groups, 'q4') }}</p>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <p class="leading-7 text-slate-800">
                    5. Berdasarkan pemahaman tentang anatomi komponen (koefisien, variabel, dan konstanta), centang SEMUA pernyataan di bawah ini yang bernilai BENAR:
                </p>

                @php
                    $pilihanBenar = $aktivitas12Answers['q5_pernyataan'] ?? [];
                    $pilihanBenar = is_array($pilihanBenar) ? $pilihanBenar : [];
                    $q5Locked = $aktivitas12InputLocked('q5_pernyataan');
                @endphp

                <div class="mt-4 space-y-3 leading-7 text-slate-700">
                    @foreach ([
                        'a11' => 'Koefisien \(a_{11}\) bernilai 2, yang merepresentasikan kebutuhan CPU untuk Layanan Web \((x)\).',
                        'a21' => 'Nilai koefisien \(a_{21}\) adalah 0 karena tidak ada angka yang ditulis secara eksplisit di depan variabel \(x\) pada persamaan RAM.',
                        'b2' => 'Konstanta \(b_2\) bernilai 65, yang menunjukkan batas maksimal ketersediaan RAM.',
                        'non_homogen' => 'Secara keseluruhan, model yang Anda susun ini diklasifikasikan sebagai Sistem Persamaan Linear Non-Homogen.',
                    ] as $optionKey => $optionText)
                        <label class="flex items-start gap-3 rounded-xl border p-3 transition {{ $aktivitas12CheckboxClass($optionKey) }} {{ $q5Locked ? 'cursor-default' : 'cursor-pointer' }}">
                            <input type="checkbox" name="answers[q5_pernyataan][]" value="{{ $optionKey }}" @checked(in_array($optionKey, $pilihanBenar, true)) @disabled($q5Locked) class="mt-1 rounded border-slate-300 text-cyan-600">
                            <span>{{ $optionText }}</span>
                        </label>
                    @endforeach
                </div>

                <x-practice-field-feedback :feedback="$aktivitas12Feedback['q5_pernyataan'] ?? []" />

                @if ($groupMessage($aktivitas12Groups, 'q5'))
                    <p class="mt-4 text-sm font-semibold {{ ($aktivitas12Groups['q5']['status'] ?? null) === 'assisted' ? 'text-indigo-700' : 'text-slate-600' }}">{{ $groupMessage($aktivitas12Groups, 'q5') }}</p>
                @endif
            </div>

            @if (! $aktivitas12Selesai)
                <div class="flex flex-col gap-4 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs leading-5 text-slate-500">
                        Pastikan seluruh jawaban sudah diisi sebelum melakukan pemeriksaan.
                    </p>

                    <button type="submit" class="w-full rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-cyan-700 sm:w-auto">
                        Cek Jawaban Aktivitas
                    </button>
                </div>
            @endif
        </form>

        @if ($aktivitas12Selesai)
            <section class="rounded-2xl border {{ $aktivitas12DenganBantuan ? 'border-indigo-200 bg-indigo-50' : 'border-green-200 bg-green-50' }} p-5" aria-label="Hasil Aktivitas 1.2">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $aktivitas12DenganBantuan ? 'bg-indigo-100 text-indigo-700' : 'bg-green-100 text-green-700' }} text-xl font-black">
                            {{ $aktivitas12DenganBantuan ? 'i' : '✓' }}
                        </div>

                        <div>
                            <p class="text-sm font-bold {{ $aktivitas12DenganBantuan ? 'text-indigo-800' : 'text-green-800' }}">
                                Hasil Aktivitas 1.2
                            </p>

                            <p class="mt-1 text-sm leading-6 {{ $aktivitas12DenganBantuan ? 'text-indigo-700' : 'text-green-700' }}">
                                {{ $aktivitas12DenganBantuan
                                    ? 'Aktivitas selesai dengan bantuan jawaban pada bagian yang belum tepat.'
                                    : 'Aktivitas telah diselesaikan secara mandiri.' }}
                            </p>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white/80 px-4 py-3 text-right shadow-sm">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">
                            Nilai Aktivitas
                        </p>

                        <p class="mt-1 text-2xl font-black {{ $aktivitas12DenganBantuan ? 'text-indigo-700' : 'text-green-700' }}">
                            {{ $aktivitas12->score }}/{{ $aktivitas12->max_score }}
                        </p>
                    </div>
                </div>

                @if ($aktivitas12DenganBantuan)
                    <p class="mt-4 rounded-xl border border-indigo-200 bg-white/70 px-4 py-3 text-sm leading-6 text-indigo-800">
                        Poin hanya diperoleh dari nomor yang selesai benar secara mandiri sebelum jawaban bantuan ditampilkan.
                    </p>
                @endif
            </section>
        @endif
    </div>

    @foreach ([$cekModal, $aktivitasModal] as $modalPayload)
        @if ($modalPayload)
            @php
                $modalStatus = $modalPayload['status'] ?? 'revision';
                $modalIsSuccess = $modalStatus === 'success';
                $modalIsIncomplete = $modalStatus === 'incomplete';
                $modalIsAssisted = $modalStatus === 'assisted';
                $modalMessages = is_array($modalPayload['feedback_messages'] ?? null)
                    ? $modalPayload['feedback_messages']
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
                aria-label="Hasil pemeriksaan">

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
                                <p class="text-lg font-bold text-slate-900">
                                    {{ $modalPayload['title'] ?? 'Hasil Pemeriksaan' }}
                                </p>

                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    {{ $modalPayload['message'] ?? '' }}
                                </p>
                            </div>
                        </div>

                        @if (! empty($modalMessages))
                            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-semibold text-slate-800">Perhatikan kembali:</p>

                                <ul class="mt-3 space-y-2 text-sm leading-6 text-slate-600">
                                    @foreach ($modalMessages as $modalMessage)
                                        <li class="flex gap-2">
                                            <span class="mt-1 font-black {{ $modalIsAssisted ? 'text-indigo-500' : 'text-red-500' }}">•</span>
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
                                {{ $modalPayload['button_label'] ?? 'Tutup' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

</section>
