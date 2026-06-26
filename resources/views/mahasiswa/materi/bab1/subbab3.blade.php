@php
    $practiceModalPayload = session('practice_modal');
    $practiceModalPayload = is_array($practiceModalPayload) ? $practiceModalPayload : null;

    $cekPemahaman13 = $practiceSubmissions->get('cek-pemahaman-1-3');
    $aktivitas13 = $practiceSubmissions->get('aktivitas-1-3-solusi');

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

    $cekAnswers = $practiceAnswers($cekPemahaman13);
    $cekFeedback = $practiceFeedback($cekPemahaman13);
    $cekMeta = $practiceMeta($cekPemahaman13);
    $cekGroups = is_array($cekMeta['groups'] ?? null) ? $cekMeta['groups'] : [];
    $cekSelesai = $practiceUsesComponentAttemptScope($cekPemahaman13) && (bool) ($cekPemahaman13?->is_completed ?? false);
    $cekDenganBantuan = ($cekMeta['completion_mode'] ?? null) === 'bantuan';

    $aktivitasAnswers = $practiceAnswers($aktivitas13);
    $aktivitasFeedback = $practiceFeedback($aktivitas13);
    $aktivitasMeta = $practiceMeta($aktivitas13);
    $aktivitasGroups = is_array($aktivitasMeta['groups'] ?? null) ? $aktivitasMeta['groups'] : [];
    $aktivitasSelesai = $practiceUsesComponentAttemptScope($aktivitas13) && (bool) ($aktivitas13?->is_completed ?? false);
    $aktivitasDenganBantuan = ($aktivitasMeta['completion_mode'] ?? null) === 'bantuan';

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
            'assisted' => 'Jawaban pada bagian yang belum tepat telah ditampilkan.',
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
    $aktivitasAttemptInfo = $componentAttemptInfo($aktivitasMeta, $aktivitasSelesai);

    $cekInputClass = fn (string $fieldKey): string => $inputClass($cekFeedback, $fieldKey);
    $cekInputLocked = fn (string $fieldKey): bool => $inputLocked($cekFeedback, $fieldKey);
    $aktivitasInputClass = fn (string $fieldKey): string => $inputClass($aktivitasFeedback, $fieldKey);
    $aktivitasInputLocked = fn (string $fieldKey): bool => $inputLocked($aktivitasFeedback, $fieldKey);

    $checkboxClass = function (array $feedback, string $fieldKey, string $optionKey): string {
        $state = $feedback[$fieldKey]['option_statuses'][$optionKey]['state'] ?? 'neutral';

        return match ($state) {
            'correct' => 'border-green-500 bg-green-50',
            'revealed' => 'border-indigo-400 bg-indigo-50',
            'wrong' => 'border-red-500 bg-red-50',

            /*
            | Data lama mungkin masih menyimpan state "missing".
            | Tampilkan netral agar tidak membocorkan opsi yang benar.
            */
            'missing' => 'border-slate-200 bg-white hover:bg-cyan-50',
            default => 'border-slate-200 bg-white hover:bg-cyan-50',
        };
    };

    $cekSelectedQ3 = is_array($cekAnswers['cek_q3_pernyataan'] ?? null)
        ? $cekAnswers['cek_q3_pernyataan']
        : [];

    $aktivitasSelectedQ3 = is_array($aktivitasAnswers['aktivitas_q3_pernyataan'] ?? null)
        ? $aktivitasAnswers['aktivitas_q3_pernyataan']
        : [];

    $cekModal = ($practiceModalPayload['practice_key'] ?? null) === 'cek-pemahaman-1-3'
        ? $practiceModalPayload
        : null;

    $aktivitasModal = ($practiceModalPayload['practice_key'] ?? null) === 'aktivitas-1-3-solusi'
        ? $practiceModalPayload
        : null;
@endphp

<section class="space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">
            1.3 KEMUNGKINAN SOLUSI SISTEM PERSAMAAN LINEAR
        </p>

        <p class="mt-4 leading-7 text-slate-700">
            Setelah kita bisa mengenali bentuk umum dan bagian-bagian dari Sistem Persamaan Linear (SPL), pertanyaan berikutnya adalah “Apakah semua sistem persamaan matematika pasti bisa diselesaikan dan menghasilkan satu jawaban pasti?”
        </p>

        <p class="mt-4 leading-7 text-slate-700">
            Ternyata tidak selalu. Berdasarkan hukum aljabar linear, ketika kita mencoba mencari jalan keluar atau jawaban dari sebuah sistem persamaan linear, hanya ada tiga kemungkinan hasil yang akan kita temui. Tidak ada kemungkinan keempat atau kelima.
        </p>

        <div class="mt-6 grid gap-4 lg:grid-cols-3">
            <article class="rounded-2xl border border-green-200 bg-green-50 p-5">
                <p class="text-sm font-bold text-green-700">
                    1. Sistem dengan Tepat Satu Solusi (Solusi Tunggal)
                </p>

                <p class="mt-3 leading-7 text-slate-700">
                    Karakteristik pertama adalah kondisi ideal yang paling sering kita harapkan. Pada kondisi ini, semua persamaan di dalam sistem saling bekerja bersama-sama sekaligus untuk menunjuk ke satu-satunya nilai jawaban yang pasti dan benar untuk setiap variabelnya. Secara grafik, garis-garis tersebut berpotongan hanya pada satu titik.
                </p>
            </article>

            <article class="rounded-2xl border border-cyan-200 bg-cyan-50 p-5">
                <p class="text-sm font-bold text-cyan-700">
                    2. Sistem dengan Tak Berhingga Banyaknya Solusi (Solusi Banyak)
                </p>

                <p class="mt-3 leading-7 text-slate-700">
                    Karakteristik kedua adalah situasi di mana sistem memiliki pilihan jawaban yang sangat banyak dan tidak terbatas. Kondisi ini biasanya terjadi ketika persamaan yang satu sebenarnya merupakan kelipatan atau bentuk lain dari persamaan yang lain, sehingga mereka tidak memberikan petunjuk baru yang berbeda. Secara grafik, garis-garis tersebut saling berhimpitan, sehingga setiap titik pada garis adalah titik potong (solusi)
                </p>
            </article>

            <article class="rounded-2xl border border-red-200 bg-red-50 p-5">
                <p class="text-sm font-bold text-red-700">
                    3. Sistem yang Tidak Memiliki Solusi (Sama Sekali)
                </p>

                <p class="mt-3 leading-7 text-slate-700">
                    Karakteristik ketiga adalah kondisi buntu, di mana sistem persamaan tersebut tidak memiliki jawaban sama sekali. Hal ini terjadi karena petunjuk-petunjuk di dalam persamaan tersebut saling bertentangan atau mustahil dipenuhi secara bersamaan. Secara grafik, garis-garis tersebut sejajar dan tidak pernah berpotongan.
                </p>
            </article>
        </div>

        <p class="mt-6 leading-7 text-slate-700">
            Tidak semua sistem persamaan linear memiliki solusi. Suatu sistem persamaan yang tidak memiliki solusi sama sekali disebut tidak konsisten (<span class="italic">inconsistent</span>). Sebaliknya, jika terdapat paling tidak satu solusi di dalam sistem, maka sistem tersebut disebut konsisten (<span class="italic">consistent</span>).
        </p>
    </div>

    <div class="overflow-hidden rounded-[1.75rem] border border-cyan-200 bg-cyan-50 shadow-sm">
        <div class="border-b border-cyan-200 bg-gradient-to-br from-cyan-100 via-white to-cyan-50 px-5 py-6 sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-cyan-700">
                        Cek Pemahaman
                    </p>

                    <h3 class="mt-1 text-xl font-bold text-slate-950">
                        Kemungkinan Solusi Sistem Persamaan Linear
                    </h3>
                </div>

                <span class="w-fit shrink-0 rounded-full px-3.5 py-2 text-xs font-bold {{ $cekSelesai ? ($cekDenganBantuan ? 'bg-indigo-100 text-indigo-700' : 'bg-green-100 text-green-700') : 'bg-yellow-100 text-yellow-800' }}">
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
        </div>

        <div class="space-y-6 p-5 sm:p-6">
            <p class="leading-7 text-slate-700">
                Perhatikan bentuk Sistem Persamaan Linear (SPL) beserta hasil gambar grafiknya di bawah ini:
            </p>

            <form action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'cek-pemahaman-1-3']) }}" method="POST" data-preserve-scroll="true" class="space-y-6">
                @csrf

                <div class="rounded-2xl border border-cyan-200 bg-white p-5">
                    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-center">
                        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <div class="min-w-[250px] text-center text-lg font-normal text-slate-950">
                                \[
                                    \begin{aligned}
                                        x + y &= 3 \\
                                        x - y &= 1
                                    \end{aligned}
                                \]
                            </div>
                        </div>

                        <img
                            src="{{ asset('images/materi/bab1/subbab3/grafik-solusi-tunggal.png') }}"
                            alt="Grafik dua garis berpotongan pada satu titik"
                            class="mx-auto w-full max-w-[360px] rounded-2xl border border-slate-200 bg-white object-contain p-2">
                    </div>

                    <p class="mt-5 leading-7 text-slate-700">
                        Tanpa perlu menghitung persamaannya, coba amati grafik di atas!
                    </p>

                    <p class="mt-4 leading-7 text-slate-700">
                        Sebagai seorang analis data, Anda sedang mengamati grafik dari persamaan \(x+y=3\) dan \(x-y=1\) di atas. Garis biru dan garis merah pada grafik tersebut melaju dengan arah kemiringan yang berbeda, sehingga mereka akhirnya bertemu dan menabrak tepat di satu koordinat, yaitu \((2, 1)\). Secara visual dan istilah matematika, kondisi kedua garis yang membentuk satu-satunya titik solusi ini disebut saling...? (Ketik: BERPOTONGAN / BERHIMPIT / SEJAJAR)
                    </p>

                    <label class="mt-4 block text-slate-800">
                        Jawaban:
                        <input
                            type="text"
                            name="answers[cek_q1_garis]"
                            value="{{ $cekAnswers['cek_q1_garis'] ?? '' }}"
                            class="mt-2 block w-full rounded-xl border px-3 py-2 text-sm text-slate-900 transition sm:max-w-sm {{ $cekInputClass('cek_q1_garis') }}"
                            @if ($cekInputLocked('cek_q1_garis')) readonly @endif>
                    </label>

                    <x-practice-field-feedback :feedback="$cekFeedback['cek_q1_garis'] ?? []" />

                    @if ($groupMessage($cekGroups, 'q1'))
                        <p class="mt-4 text-sm font-semibold {{ ($cekGroups['q1']['status'] ?? null) === 'assisted' ? 'text-indigo-700' : 'text-slate-600' }}">
                            {{ $groupMessage($cekGroups, 'q1') }}
                        </p>
                    @endif
                </div>

                <div class="rounded-2xl border border-cyan-200 bg-white p-5">
                    <p class="leading-7 text-slate-700">
                        Perhatikan bentuk Sistem Persamaan Linear (SPL) beserta hasil gambar grafiknya di bawah ini:
                    </p>

                    <div class="mt-5 grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-center">
                        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <div class="min-w-[250px] text-center text-lg font-normal text-slate-950">
                                \[
                                    \begin{aligned}
                                        x + y &= 2 \\
                                        2x + 2y &= 4
                                    \end{aligned}
                                \]
                            </div>
                        </div>

                        <img
                            src="{{ asset('images/materi/bab1/subbab3/grafik-solusi-banyak.png') }}"
                            alt="Grafik dua garis berhimpit"
                            class="mx-auto w-full max-w-[360px] rounded-2xl border border-slate-200 bg-white object-contain p-2">
                    </div>

                    <p class="mt-5 leading-7 text-slate-700">
                        Sebuah sistem komputer mencoba mencari satu titik temu pasti dari persamaan \(x+y=2\) dan \(2x+2y=4\) pada grafik di atas. Namun, komputer gagal menemukan hanya satu titik karena mendeteksi bahwa kedua garis tersebut menumpuk di jalur yang persis sama, sehingga setiap titik di sepanjang garis menjadi solusi yang sah. Dalam istilah matematika, kondisi dua garis yang menumpuk ini disebut saling...? (Ketik: BERPOTONGAN / BERHIMPIT / SEJAJAR)
                    </p>

                    <label class="mt-4 block text-slate-800">
                        Jawaban:
                        <input
                            type="text"
                            name="answers[cek_q2_garis]"
                            value="{{ $cekAnswers['cek_q2_garis'] ?? '' }}"
                            class="mt-2 block w-full rounded-xl border px-3 py-2 text-sm text-slate-900 transition sm:max-w-sm {{ $cekInputClass('cek_q2_garis') }}"
                            @if ($cekInputLocked('cek_q2_garis')) readonly @endif>
                    </label>

                    <x-practice-field-feedback :feedback="$cekFeedback['cek_q2_garis'] ?? []" />

                    @if ($groupMessage($cekGroups, 'q2'))
                        <p class="mt-4 text-sm font-semibold {{ ($cekGroups['q2']['status'] ?? null) === 'assisted' ? 'text-indigo-700' : 'text-slate-600' }}">
                            {{ $groupMessage($cekGroups, 'q2') }}
                        </p>
                    @endif
                </div>

                <div class="rounded-2xl border border-cyan-200 bg-white p-5">
                    <p class="leading-7 text-slate-700">
                        Perhatikan bentuk Sistem Persamaan Linear (SPL) beserta hasil gambar grafiknya di bawah ini:
                    </p>

                    <div class="mt-5 grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-center">
                        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <div class="min-w-[250px] text-center text-lg font-normal text-slate-950">
                                \[
                                    \begin{aligned}
                                        x + y &= 3 \\
                                        x + y &= 5
                                    \end{aligned}
                                \]
                            </div>
                        </div>

                        <img
                            src="{{ asset('images/materi/bab1/subbab3/grafik-tidak-memiliki-solusi.png') }}"
                            alt="Grafik dua garis sejajar"
                            class="mx-auto w-full max-w-[360px] rounded-2xl border border-slate-200 bg-white object-contain p-2">
                    </div>

                    <p class="mt-5 leading-7 text-slate-700">
                        Berdasarkan pengamatan visual pada grafik, centang SEMUA pernyataan di bawah ini yang bernilai BENAR:
                    </p>

                    <div class="mt-4 space-y-3">
                        @foreach ([
                            'cek_q3_a' => 'Sistem di atas mustahil memiliki solusi karena petunjuknya saling bertentangan secara logika (mustahil \(x+y\) menghasilkan 3 dan 5 sekaligus).',
                            'cek_q3_b' => 'Jika digambarkan pada bidang grafik, kedua persamaan di atas ternyata membentuk dua garis yang saling sejajar.',
                            'cek_q3_c' => 'Titik potong dari kedua garis di atas berada tepat di koordinat pusat \((0,0)\).',
                            'cek_q3_d' => 'Karena kedua garis tidak akan pernah bertemu sampai kapan pun, maka jumlah solusi dari sistem di atas adalah 0 solusi.',
                        ] as $optionKey => $optionText)
                            <label class="flex items-start gap-3 rounded-xl border p-3 transition {{ $checkboxClass($cekFeedback, 'cek_q3_pernyataan', $optionKey) }} {{ $cekInputLocked('cek_q3_pernyataan') ? 'cursor-default' : 'cursor-pointer' }}">
                                <input
                                    type="checkbox"
                                    name="answers[cek_q3_pernyataan][]"
                                    value="{{ $optionKey }}"
                                    @checked(in_array($optionKey, $cekSelectedQ3, true))
                                    @disabled($cekInputLocked('cek_q3_pernyataan'))
                                    class="mt-1 rounded border-slate-300 text-cyan-600">

                                <span class="leading-6 text-slate-700">
                                    {!! $optionText !!}
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <x-practice-field-feedback :feedback="$cekFeedback['cek_q3_pernyataan'] ?? []" />

                    @if ($groupMessage($cekGroups, 'q3'))
                        <p class="mt-4 text-sm font-semibold {{ ($cekGroups['q3']['status'] ?? null) === 'assisted' ? 'text-indigo-700' : 'text-slate-600' }}">
                            {{ $groupMessage($cekGroups, 'q3') }}
                        </p>
                    @endif
                </div>

                <div class="rounded-2xl border border-cyan-200 bg-white p-5">
                    <p class="leading-7 text-slate-700">
                        Uji daya ingat Anda setelah membaca materi di atas! Jika sebuah sistem persamaan linear ternyata membentuk dua garis sejajar yang tidak punya titik temu sama sekali, maka sistem tersebut diklasifikasikan sebagai sistem yang...? (Ketik: KONSISTEN / INKONSISTEN)
                    </p>

                    <label class="mt-4 block text-slate-800">
                        Jawaban:
                        <input
                            type="text"
                            name="answers[cek_q4_konsistensi]"
                            value="{{ $cekAnswers['cek_q4_konsistensi'] ?? '' }}"
                            class="mt-2 block w-full rounded-xl border px-3 py-2 text-sm text-slate-900 transition sm:max-w-sm {{ $cekInputClass('cek_q4_konsistensi') }}"
                            @if ($cekInputLocked('cek_q4_konsistensi')) readonly @endif>
                    </label>

                    <x-practice-field-feedback :feedback="$cekFeedback['cek_q4_konsistensi'] ?? []" />

                    @if ($groupMessage($cekGroups, 'q4'))
                        <p class="mt-4 text-sm font-semibold {{ ($cekGroups['q4']['status'] ?? null) === 'assisted' ? 'text-indigo-700' : 'text-slate-600' }}">
                            {{ $groupMessage($cekGroups, 'q4') }}
                        </p>
                    @endif
                </div>

                @if (! $cekSelesai)
                    <div class="flex flex-col gap-4 border-t border-cyan-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs leading-5 text-cyan-800">
                            Pastikan seluruh jawaban sudah diisi sebelum melakukan pemeriksaan.
                        </p>

                        <button type="submit" class="w-full rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-cyan-700 sm:w-auto">
                            Cek Pemahaman
                        </button>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-gradient-to-br from-cyan-50 via-white to-slate-50 px-5 py-6 sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex min-w-0 items-start gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-cyan-100 text-sm font-black text-cyan-700">
                        1.3
                    </div>

                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-cyan-700">
                            Aktivitas
                        </p>

                        <h3 class="mt-1 text-xl font-bold text-slate-950">
                            Analisis Skenario Solusi di Dunia Nyata
                        </h3>

                        <p class="mt-1 text-sm leading-6 text-slate-600">
                            Studi Kasus: Optimalisasi Pusat Data (Data Center)
                        </p>
                    </div>
                </div>

                <span class="w-fit shrink-0 rounded-full px-3.5 py-2 text-xs font-bold {{ $aktivitasSelesai ? ($aktivitasDenganBantuan ? 'bg-indigo-100 text-indigo-700' : 'bg-green-100 text-green-700') : 'bg-yellow-100 text-yellow-800' }}">
                    {{ $aktivitasSelesai ? ($aktivitasDenganBantuan ? 'Selesai dengan Bantuan' : 'Aktivitas Selesai') : 'Perlu Diselesaikan' }}
                </span>
            </div>
        </div>

        <div class="space-y-5 p-5 sm:p-6">
            @if (! $aktivitasSelesai)
                <div class="rounded-2xl border border-cyan-200 bg-cyan-50 p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-bold text-cyan-950">
                                Kesempatan tersisa: {{ $aktivitasAttemptInfo['remaining'] }} dari {{ $aktivitasAttemptInfo['max'] }}
                            </p>

                            <p class="mt-1 text-xs leading-5 text-cyan-800">
                                Berlaku untuk seluruh Aktivitas 1.3. Kolom yang belum diisi tidak mengurangi kesempatan.
                            </p>
                        </div>

                        <span class="w-fit rounded-xl border border-cyan-200 bg-white px-3 py-1.5 text-xs font-semibold text-cyan-800">
                            Periksa seluruh jawaban
                        </span>
                    </div>
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
                    Studi Kasus
                </p>

                <p class="mt-3 leading-7 text-slate-700">
                    Sebagai seorang lulusan Ilmu Komputer atau Software Engineer, Anda akan sering dihadapkan pada kebutuhan untuk menerjemahkan masalah dunia nyata ke dalam model matematika. Saat memodelkan sebuah sistem yang berisi banyak batasan (persamaan linear), Anda harus bisa menganalisis apakah sistem operasi tersebut akan berjalan lancar, memiliki terlalu banyak toleransi, atau justru crash (rusak) karena aturan yang saling bertentangan.
                </p>

                <p class="mt-3 leading-7 text-slate-700">
                    Tugas Anda: Analisislah tiga skenario sistem digital di bawah ini. Tentukan karakteristik solusi yang paling tepat berdasarkan prinsip visual dan logika Sistem Persamaan Linear yang baru saja Anda pelajari!
                </p>
            </div>

            <form action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'aktivitas-1-3-solusi']) }}" method="POST" data-preserve-scroll="true" class="space-y-4">
                @csrf

                <ol class="space-y-4">
                    <li class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 sm:p-5 md:grid-cols-[40px_minmax(0,1fr)]">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-100 text-sm font-black text-cyan-700">
                            1.
                        </div>

                        <div>
                            <p class="text-base font-bold text-slate-950">
                                Jalur Pertemuan pada Navigasi Drone
                            </p>

                            <p class="mt-3 leading-7 text-slate-700">
                                Dua buah drone sedang terbang untuk saling bertukar barang di udara. Pada layar radar pemantau, komputer melihat bahwa rute terbang kedua drone tersebut membentuk dua garis lurus yang arah kemiringannya berbeda. Karena arahnya berbeda, kedua jalur terbang ini akhirnya saling bersilangan dan menabrak persis di satu titik kumpul. Berdasarkan hasil pantauan radar ini, sistem navigasi tersebut menghasilkan... (Ketik: SOLUSI TUNGGAL / SOLUSI BANYAK)
                            </p>

                            <label class="mt-4 block text-slate-800">
                                Jawaban:
                                <input
                                    type="text"
                                    name="answers[aktivitas_q1_solusi]"
                                    value="{{ $aktivitasAnswers['aktivitas_q1_solusi'] ?? '' }}"
                                    class="mt-2 block w-full rounded-xl border px-3 py-2 text-sm text-slate-900 transition sm:max-w-md {{ $aktivitasInputClass('aktivitas_q1_solusi') }}"
                                    @if ($aktivitasInputLocked('aktivitas_q1_solusi')) readonly @endif>
                            </label>

                            <x-practice-field-feedback :feedback="$aktivitasFeedback['aktivitas_q1_solusi'] ?? []" />

                            @if ($groupMessage($aktivitasGroups, 'q1'))
                                <p class="mt-4 text-sm font-semibold {{ ($aktivitasGroups['q1']['status'] ?? null) === 'assisted' ? 'text-indigo-700' : 'text-slate-600' }}">
                                    {{ $groupMessage($aktivitasGroups, 'q1') }}
                                </p>
                            @endif
                        </div>
                    </li>

                    <li class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 sm:p-5 md:grid-cols-[40px_minmax(0,1fr)]">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-100 text-sm font-black text-cyan-700">
                            2.
                        </div>

                        <div>
                            <p class="text-base font-bold text-slate-950">
                                Perintah Ganda pada AC Pintar (Pengatur Suhu)
                            </p>

                            <p class="mt-3 leading-7 text-slate-700">
                                Sebuah ruangan memiliki pengatur suhu otomatis (AC Pintar). Sensor pertama mengirimkan aturan batas suhu ke komputer pusat. Namun, karena sistem sedang error, sensor kedua secara tidak sengaja ikut mengirimkan aturan suhu cadangan yang sebenarnya hanyalah salinan kembar (fotokopi) dari aturan pertama.
                            </p>

                            <p class="mt-3 leading-7 text-slate-700">
                                Saat komputer menggambar kedua perintah ini ke dalam layar, terlihat bahwa garis dari kedua aturan tersebut saling berhimpit. Akibatnya, komputer kebingungan harus memilih titik suhu yang mana, karena semua titik di sepanjang garis tersebut dianggap sah. Kasus aturan yang menumpuk seperti ini diklasifikasikan memiliki... (Ketik: SOLUSI TUNGGAL / SOLUSI BANYAK)
                            </p>

                            <label class="mt-4 block text-slate-800">
                                Jawaban:
                                <input
                                    type="text"
                                    name="answers[aktivitas_q2_solusi]"
                                    value="{{ $aktivitasAnswers['aktivitas_q2_solusi'] ?? '' }}"
                                    class="mt-2 block w-full rounded-xl border px-3 py-2 text-sm text-slate-900 transition sm:max-w-md {{ $aktivitasInputClass('aktivitas_q2_solusi') }}"
                                    @if ($aktivitasInputLocked('aktivitas_q2_solusi')) readonly @endif>
                            </label>

                            <x-practice-field-feedback :feedback="$aktivitasFeedback['aktivitas_q2_solusi'] ?? []" />

                            @if ($groupMessage($aktivitasGroups, 'q2'))
                                <p class="mt-4 text-sm font-semibold {{ ($aktivitasGroups['q2']['status'] ?? null) === 'assisted' ? 'text-indigo-700' : 'text-slate-600' }}">
                                    {{ $groupMessage($aktivitasGroups, 'q2') }}
                                </p>
                            @endif
                        </div>
                    </li>

                    <li class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 sm:p-5 md:grid-cols-[40px_minmax(0,1fr)]">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-100 text-sm font-black text-cyan-700">
                            3.
                        </div>

                        <div>
                            <p class="text-base font-bold text-slate-950">
                                Kesalahan Kode (Bug) pada Pemrograman Game
                            </p>

                            <p class="mt-3 leading-7 text-slate-700">
                                Dalam pembuatan sebuah game petualangan, seorang pembuat game (programmer) menetapkan aturan dasar: Total gabungan poin Kekuatan (Strength) dan Kecepatan (Speed) karakter tidak boleh lebih dari 50 poin. Namun saat update terbaru, terjadi kesalahan kode (bug) yang memaksakan aturan baru bahwa total gabungan keduanya harus 100 poin.
                            </p>

                            <p class="mt-3 leading-7 text-slate-700">
                                Secara logika, mustahil karakter tersebut memiliki batas poin 50 dan 100 di saat yang bersamaan. Saat komputer mencoba menggambar kedua aturan ini ke dalam grafik, yang muncul adalah dua garis sejajar yang melaju berdampingan. Karena mencari jalan buntu, game tersebut akhirnya rusak (crash).
                            </p>

                            <p class="mt-3 leading-7 text-slate-700">
                                Berdasarkan kasus error di atas, manakah pernyataan matematika yang paling tepat?
                            </p>

                            <div class="mt-4 space-y-3">
                                @foreach ([
                                    'aktivitas_q3_a' => 'Kasus di atas diklasifikasikan sebagai Sistem Inkonsisten.',
                                    'aktivitas_q3_b' => 'Kasus di atas diklasifikasikan sebagai Sistem Konsisten.',
                                    'aktivitas_q3_c' => 'Jika dipaksakan, titik potong dari kedua aturan game tersebut berada di angka 0.',
                                    'aktivitas_q3_d' => 'Karena kedua garis sejajar dan tidak akan pernah bertemu, maka sistem tersebut secara mutlak memiliki 0 solusi.',
                                ] as $optionKey => $optionText)
                                    <label class="flex items-start gap-3 rounded-xl border p-3 transition {{ $checkboxClass($aktivitasFeedback, 'aktivitas_q3_pernyataan', $optionKey) }} {{ $aktivitasInputLocked('aktivitas_q3_pernyataan') ? 'cursor-default' : 'cursor-pointer' }}">
                                        <input
                                            type="checkbox"
                                            name="answers[aktivitas_q3_pernyataan][]"
                                            value="{{ $optionKey }}"
                                            @checked(in_array($optionKey, $aktivitasSelectedQ3, true))
                                            @disabled($aktivitasInputLocked('aktivitas_q3_pernyataan'))
                                            class="mt-1 rounded border-slate-300 text-cyan-600">

                                        <span class="leading-6 text-slate-700">
                                            {{ $optionText }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>

                            <x-practice-field-feedback :feedback="$aktivitasFeedback['aktivitas_q3_pernyataan'] ?? []" />

                            @if ($groupMessage($aktivitasGroups, 'q3'))
                                <p class="mt-4 text-sm font-semibold {{ ($aktivitasGroups['q3']['status'] ?? null) === 'assisted' ? 'text-indigo-700' : 'text-slate-600' }}">
                                    {{ $groupMessage($aktivitasGroups, 'q3') }}
                                </p>
                            @endif
                        </div>
                    </li>
                </ol>

                @if (! $aktivitasSelesai)
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

            @if ($aktivitasSelesai)
                <section class="rounded-2xl border {{ $aktivitasDenganBantuan ? 'border-indigo-200 bg-indigo-50' : 'border-green-200 bg-green-50' }} p-5" aria-label="Hasil Aktivitas 1.3">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $aktivitasDenganBantuan ? 'bg-indigo-100 text-indigo-700' : 'bg-green-100 text-green-700' }} text-xl font-black">
                                {{ $aktivitasDenganBantuan ? 'i' : '✓' }}
                            </div>

                            <div>
                                <p class="text-sm font-bold {{ $aktivitasDenganBantuan ? 'text-indigo-800' : 'text-green-800' }}">
                                    Hasil Aktivitas 1.3
                                </p>

                                <p class="mt-1 text-sm leading-6 {{ $aktivitasDenganBantuan ? 'text-indigo-700' : 'text-green-700' }}">
                                    {{ $aktivitasDenganBantuan
                                        ? 'Aktivitas selesai dengan bantuan jawaban pada bagian yang belum tepat.'
                                        : 'Aktivitas telah diselesaikan secara mandiri.' }}
                                </p>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-white/80 px-4 py-3 text-right shadow-sm">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                Nilai Aktivitas
                            </p>

                            <p class="mt-1 text-2xl font-black {{ $aktivitasDenganBantuan ? 'text-indigo-700' : 'text-green-700' }}">
                                {{ $aktivitas13->score }}/{{ $aktivitas13->max_score }}
                            </p>
                        </div>
                    </div>

                    @if ($aktivitasDenganBantuan)
                        <p class="mt-4 rounded-xl border border-indigo-200 bg-white/70 px-4 py-3 text-sm leading-6 text-indigo-800">
                            Poin hanya diperoleh dari nomor yang selesai benar secara mandiri sebelum jawaban bantuan ditampilkan.
                        </p>
                    @endif
                </section>
            @endif
        </div>
    </div>

    @foreach ([
        'cek' => $cekModal,
        'aktivitas' => $aktivitasModal,
    ] as $modalKey => $practiceModal)
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
                aria-labelledby="{{ $modalKey }}-pemahaman13-modal-title">

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
                                <p id="{{ $modalKey }}-pemahaman13-modal-title" class="text-lg font-bold text-slate-900">
                                    {{ $practiceModal['title'] ?? 'Hasil Pemeriksaan' }}
                                </p>

                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    {{ $practiceModal['message'] ?? '' }}
                                </p>
                            </div>
                        </div>

                        @if (! empty($modalMessages))
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
    @endforeach
</section>
