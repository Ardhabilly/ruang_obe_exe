@include('mahasiswa.materi.bab1.pengantar')

@php
    $aktivitas11 = $practiceSubmissions->get('aktivitas-1-1');
    $aktivitas11StoredAnswers = is_array($aktivitas11?->answers) ? $aktivitas11->answers : [];
    $aktivitas11OldAnswers = old('answers', []);
    $aktivitas11OldAnswers = is_array($aktivitas11OldAnswers) ? $aktivitas11OldAnswers : [];
    $aktivitas11Answers = array_replace($aktivitas11StoredAnswers, $aktivitas11OldAnswers);

    $aktivitas11FeedbackRaw = is_array($aktivitas11?->feedback) ? $aktivitas11->feedback : [];
    $aktivitas11Feedback = is_array($aktivitas11FeedbackRaw['fields'] ?? null)
        ? $aktivitas11FeedbackRaw['fields']
        : collect($aktivitas11FeedbackRaw)
            ->except(['_meta', 'groups', 'fields'])
            ->filter(fn ($item) => is_array($item))
            ->all();

    $aktivitas11Groups = is_array($aktivitas11FeedbackRaw['_meta']['groups'] ?? null)
        ? $aktivitas11FeedbackRaw['_meta']['groups']
        : [];

    $aktivitas11UsesComponentAttemptScope = ($aktivitas11FeedbackRaw['_meta']['attempt_scope'] ?? null) === 'component';

    if (! $aktivitas11UsesComponentAttemptScope && $aktivitas11) {
        $aktivitas11Answers = $aktivitas11OldAnswers;
        $aktivitas11FeedbackRaw = [];
        $aktivitas11Feedback = [];
        $aktivitas11Groups = [];
    }

    $practiceModalPayload = session('practice_modal');
    $practiceModal = is_array($practiceModalPayload)
        && ($practiceModalPayload['practice_key'] ?? null) === 'aktivitas-1-1'
            ? $practiceModalPayload
            : null;

    $aktivitas11Selesai = $aktivitas11UsesComponentAttemptScope && (bool) ($aktivitas11?->is_completed ?? false);
    $aktivitas11DenganBantuan = ($aktivitas11FeedbackRaw['_meta']['completion_mode'] ?? null) === 'bantuan';

    $aktivitas11MaxAttempts = max(1, (int) ($aktivitas11FeedbackRaw['_meta']['max_attempts'] ?? 3));
    $aktivitas11Attempts = max(0, min(
        $aktivitas11MaxAttempts,
        (int) ($aktivitas11FeedbackRaw['_meta']['attempts'] ?? 0)
    ));
    $aktivitas11RemainingAttempts = max(0, $aktivitas11MaxAttempts - $aktivitas11Attempts);

    $aktivitas11InputClass = function (string $fieldKey) use ($aktivitas11Feedback): string {
        $state = $aktivitas11Feedback[$fieldKey]['state'] ?? null;

        return match ($state) {
            'correct' => 'border-green-500 bg-green-50 focus:border-green-500 focus:ring-green-500',
            'revealed' => 'border-indigo-400 bg-indigo-50 text-indigo-950 focus:border-indigo-500 focus:ring-indigo-500',
            'wrong' => 'border-red-500 bg-red-50 focus:border-red-500 focus:ring-red-500',
            'empty' => 'border-yellow-400 bg-yellow-50 focus:border-yellow-500 focus:ring-yellow-500',
            default => 'border-slate-300 bg-white focus:border-cyan-500 focus:ring-cyan-500',
        };
    };

    $aktivitas11InputLocked = function (string $fieldKey) use ($aktivitas11Feedback): bool {
        $field = $aktivitas11Feedback[$fieldKey] ?? [];

        return ! empty($field['is_revealed'])
            || (! empty($field['is_correct']) && ($field['state'] ?? null) === 'correct');
    };

    $aktivitas11GroupMessage = function (string $groupKey) use ($aktivitas11Groups): ?string {
        $group = $aktivitas11Groups[$groupKey] ?? null;

        if (! is_array($group)) {
            return null;
        }

        return match ($group['status'] ?? null) {
            'passed' => 'Jawaban pada nomor ini sudah benar.',
            default => null,
        };
    };
@endphp

<section class="mt-8 space-y-8">
    <div class="space-y-5">
        <h2 class="text-2xl font-black text-slate-950">
            1.1 Pengertian Sistem Persamaan Linear
        </h2>

        <p>
            Dalam kehidupan sehari-hari, kita sering dihadapkan pada permasalahan yang melibatkan
            perhitungan berbagai variabel yang saling berkaitan, seperti mengurai total harga dari
            gabungan barang belanjaan. Namun, mesin atau sistem komputasi tidak memandang masalah
            ini melalui kerumitan wujud fisiknya, melainkan menyederhanakannya ke dalam bentuk
            matematis.
        </p>

        <p>
            Untuk memproses informasi yang memiliki hubungan antarvariabel secara proporsional,
            kita perlu menerjemahkannya ke dalam bahasa aljabar. Sebelum melangkah lebih jauh ke
            dalam teknik penyelesaian sistem yang kompleks, mahasiswa perlu mengingat kembali
            fondasi dasarnya, yaitu persamaan linear.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-xl font-black text-slate-900">
            Bentuk Persamaan Linear Dua Variabel
        </h3>

        <p class="mt-3">
            Secara visual, sebuah garis yang terletak pada bidang dua dimensi dapat dinyatakan
            secara aljabar dalam suatu persamaan berbentuk:
        </p>

        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-6 text-center">
            <div class="text-3xl text-slate-950">
                \[
                    ax + by = c
                \]
            </div>
        </div>

        <p class="mt-4">
            Pada bentuk tersebut, \(a\), \(b\), dan \(c\)
            merupakan konstanta real. Nilai \(a\) dan \(b\) tidak boleh keduanya bernilai nol.
            Persamaan semacam ini disebut persamaan linear dengan variabel
            \(x\) dan \(y\).
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-xl font-black text-slate-900">
            Bentuk Umum Persamaan Linear
        </h3>

        <p class="mt-3">
            Seiring bertambahnya kompleksitas, kita dapat memiliki sangat banyak variabel.
            Secara umum, persamaan linear dengan \(n\) variabel dapat dinyatakan dalam bentuk:
        </p>

        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-6 text-center">
            <div class="text-2xl text-slate-950">
                \[
                    a_1x_1 + a_2x_2 + \cdots + a_nx_n = b
                \]
            </div>
        </div>

        <p class="mt-4">
            Pada bentuk tersebut, \(a_1, a_2, \ldots, a_n\) dan
            \(b\) merupakan konstanta real. Variabel-variabel di dalam persamaan ini sering
            disebut sebagai faktor-faktor yang tidak diketahui atau <span class="italic">unknowns</span>.
        </p>
    </div>

    <div class="rounded-2xl border border-cyan-200 bg-cyan-50 p-6">
        <h3 class="text-xl font-black text-slate-950">
            Syarat Persamaan Linear
        </h3>

        <p class="mt-3">
            Syarat utama agar sebuah persamaan diakui sebagai linear adalah setiap variabel
            harus berada dalam bentuk pangkat pertama. Persamaan linear tidak boleh mengandung
            hasil kali antarvariabel, akar dari variabel, atau variabel sebagai bagian dari
            fungsi trigonometri, logaritma, maupun eksponensial.
        </p>

        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <div class="rounded-2xl border border-green-200 bg-white p-5">
                <h4 class="font-black text-green-700">
                    Contoh Persamaan Linear
                </h4>

                <ul class="mt-4 space-y-2 text-slate-700">
                    <li>• \(2x + 3y = 7\)</li>
                    <li>• \(4x_1 - 5x_2 + \frac{1}{2}x_3 = 9\)</li>
                    <li>• \(x + y - z = 0\)</li>
                </ul>
            </div>

            <div class="rounded-2xl border border-red-200 bg-white p-5">
                <h4 class="font-black text-red-700">
                    Contoh Bukan Persamaan Linear
                </h4>

                <ul class="mt-4 space-y-3 text-slate-700">
                    <li>
                        • \(x^2 + y = 5\)
                        <span class="block text-sm text-slate-500">
                            Variabel \(x\) berpangkat dua.
                        </span>
                    </li>

                    <li>
                        • \(2x + \sqrt{y} = 4\)
                        <span class="block text-sm text-slate-500">
                            Variabel \(y\) berada di dalam akar.
                        </span>
                    </li>

                    <li>
                        • \(x_1x_2 + 3x_3 = -4\)
                        <span class="block text-sm text-slate-500">
                            Terdapat hasil kali antarvariabel.
                        </span>
                    </li>

                    <li>
                        • \(\sin x + y = 2\)
                        <span class="block text-sm text-slate-500">
                            Variabel menjadi argumen fungsi trigonometri.
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-xl font-black text-slate-900">
            Sistem Persamaan Linear
        </h3>

        <p class="mt-3">
            Berdasarkan fondasi tersebut, sejumlah tertentu persamaan linear dalam beberapa
            variabel disebut sebagai Sistem Persamaan Linear atau sistem linear. Suatu sistem
            yang memuat lebih dari satu persamaan linear inilah yang kemudian dipecahkan untuk
            mencari nilai pasti dari setiap variabelnya.
        </p>

        <p class="mt-3">
            Terdapat beberapa cara penyelesaian untuk sistem ini, di antaranya menggunakan
            metode matriks dan Operasi Baris Elementer (OBE), seperti metode Eliminasi Gauss
            dan metode Gauss-Jordan.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">
                    Aktivitas 1.1
                </p>

                <h3 class="mt-1 text-xl font-black text-slate-950">
                    Laboratorium Validasi Aljabar
                </h3>

                <p class="mt-2 text-slate-600">
                    Studi Kasus: Debugging Model Matematika pada Kernel Komputasi
                </p>
            </div>

            <span class="w-fit rounded-full px-4 py-2 text-sm font-bold {{ $aktivitas11Selesai ? ($aktivitas11DenganBantuan ? 'bg-indigo-100 text-indigo-700' : 'bg-green-50 text-green-700') : 'bg-yellow-50 text-yellow-700' }}">
                {{ $aktivitas11Selesai ? ($aktivitas11DenganBantuan ? 'Selesai dengan Bantuan' : 'Aktivitas Selesai') : 'Perlu Diselesaikan' }}
            </span>
        </div>

        @if (! $aktivitas11Selesai)
            <div class="mt-5 flex flex-col gap-2 rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm text-cyan-950 sm:flex-row sm:items-center sm:justify-between">
                <p class="font-bold">
                    Kesempatan tersisa: {{ $aktivitas11RemainingAttempts }} dari {{ $aktivitas11MaxAttempts }}
                </p>

                <p class="text-xs leading-5 text-cyan-800">
                    Berlaku untuk seluruh Aktivitas 1.1. Kolom yang belum diisi tidak mengurangi kesempatan.
                </p>
            </div>
        @endif

        @if ($aktivitas11 && $aktivitas11UsesComponentAttemptScope)
            <div class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4 text-sm text-cyan-900">
                Nilai Aktivitas: <span class="font-black">{{ $aktivitas11->score }}/{{ $aktivitas11->max_score }}</span>

                @if ($aktivitas11DenganBantuan)
                    <span class="mt-1 block text-indigo-700">
                        Poin hanya diperoleh dari nomor yang selesai benar secara mandiri sebelum jawaban bantuan ditampilkan.
                    </span>
                @endif
            </div>
        @endif

        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <p>
                Dalam arsitektur perangkat lunak berbasis aljabar modern, sebuah solver linear
                dirancang dengan algoritma yang mengasumsikan bahwa semua input data memiliki
                sifat proporsionalitas murni. Sebelum data masuk ke dalam matriks teraugmentasi,
                sistem akan menjalankan fungsi enkapsulasi untuk memeriksa integritas linearitas
                dari setiap persamaan.
            </p>

            <p class="mt-3">
                Sebagai seorang analis, Anda diminta untuk melakukan pemeriksaan kritis terhadap
                tiga ekspresi matematika berikut. Alih-alih hanya menebak, Anda harus
                mengidentifikasi komponen spesifik yang berpotensi merusak jalannya algoritma
                solver.
            </p>
        </div>

        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="mb-3 text-sm font-bold text-slate-700">
                Bantuan Simbol
            </p>

            <div class="flex flex-wrap gap-2">
                @foreach ([
                    ['value' => '√', 'label' => '\(\sqrt{}\)'],
                    ['value' => 'x₁', 'label' => '\(x_1\)'],
                    ['value' => 'x₂', 'label' => '\(x_2\)'],
                    ['value' => 'x₃', 'label' => '\(x_3\)'],
                    ['value' => '½', 'label' => '\(\frac{1}{2}\)'],
                    ['value' => '−', 'label' => '\(-\)'],
                ] as $symbol)
                    <button
                        type="button"
                        onclick='insertStaticSymbol(@js($symbol["value"]))'
                        class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-100">
                        {{ $symbol['label'] }}
                    </button>
                @endforeach
            </div>

            <p class="mt-2 text-xs text-slate-500">
                Klik salah satu simbol setelah memilih kotak jawaban.
            </p>
        </div>

        <form
            action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'aktivitas-1-1']) }}"
            method="POST"
            data-preserve-scroll="true"
            class="mt-6 space-y-6">

            @csrf

            <ol class="space-y-6">
                <li class="grid gap-3 md:grid-cols-[32px_1fr]">
                    <div class="font-bold text-slate-900">1.</div>

                    <div>
                        <div class="mb-3 text-lg text-slate-950">
                            \(4x_1 - 5x_2 + \frac{1}{2}x_3 = 9\)
                        </div>

                        <ul class="space-y-4 pl-5">
                            <li class="list-disc">
                                <label class="text-slate-700">
                                    Identifikasi Suku Bermasalah:
                                    Ketik “Tidak Ada” atau “Ada” jika persamaan sudah linear atau belum linear.
                                </label>

                                <input
                                    type="text"
                                    name="answers[q1_suku_bermasalah]"
                                    value="{{ old('answers.q1_suku_bermasalah', $aktivitas11Answers['q1_suku_bermasalah'] ?? '') }}"
                                    class="static-answer mt-2 block w-full rounded-xl border px-3 py-2 text-sm text-slate-900 transition md:w-64 {{ $aktivitas11InputClass('q1_suku_bermasalah') }}"
                                    @if ($aktivitas11InputLocked('q1_suku_bermasalah')) readonly @endif>

                                @error('answers.q1_suku_bermasalah')
                                    <p class="mt-2 rounded-lg bg-yellow-50 p-2 text-sm font-semibold text-yellow-800">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </li>

                            <li class="list-disc">
                                <label class="text-slate-700">
                                    Analisis Karakteristik Pangkat Variabel:
                                    Masukkan pangkat tertinggi dari variabel-variabel di atas.
                                </label>

                                <input
                                    type="text"
                                    name="answers[q1_pangkat]"
                                    value="{{ old('answers.q1_pangkat', $aktivitas11Answers['q1_pangkat'] ?? '') }}"
                                    class="static-answer mt-2 block w-full rounded-xl border px-3 py-2 text-sm text-slate-900 transition md:w-64 {{ $aktivitas11InputClass('q1_pangkat') }}"
                                    @if ($aktivitas11InputLocked('q1_pangkat')) readonly @endif>

                                @error('answers.q1_pangkat')
                                    <p class="mt-2 rounded-lg bg-yellow-50 p-2 text-sm font-semibold text-yellow-800">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </li>
                        </ul>

                        @if ($aktivitas11GroupMessage('q1'))
                            <p class="mt-4 text-sm font-semibold {{ ($aktivitas11Groups['q1']['status'] ?? null) === 'assisted' ? 'text-indigo-700' : 'text-slate-600' }}">
                                {{ $aktivitas11GroupMessage('q1') }}
                            </p>
                        @endif
                    </div>
                </li>

                <li class="grid gap-3 md:grid-cols-[32px_1fr]">
                    <div class="font-bold text-slate-900">2.</div>

                    <div>
                        <div class="mb-3 text-lg text-slate-950">
                            \(2x + 3\sqrt{y} - z = 0\)
                        </div>

                        <ul class="space-y-4 pl-5">
                            <li class="list-disc">
                                <label class="text-slate-700">
                                    Komponen Pelanggar (Suku Non-Linear):
                                    Ketik suku spesifik yang melanggar aturan.
                                </label>

                                <input
                                    type="text"
                                    name="answers[q2_pelanggar]"
                                    value="{{ old('answers.q2_pelanggar', $aktivitas11Answers['q2_pelanggar'] ?? '') }}"
                                    class="static-answer mt-2 block w-full rounded-xl border px-3 py-2 text-sm text-slate-900 transition md:w-64 {{ $aktivitas11InputClass('q2_pelanggar') }}"
                                    @if ($aktivitas11InputLocked('q2_pelanggar')) readonly @endif>

                                @error('answers.q2_pelanggar')
                                    <p class="mt-2 rounded-lg bg-yellow-50 p-2 text-sm font-semibold text-yellow-800">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </li>
                        </ul>

                        @if ($aktivitas11GroupMessage('q2'))
                            <p class="mt-4 text-sm font-semibold {{ ($aktivitas11Groups['q2']['status'] ?? null) === 'assisted' ? 'text-indigo-700' : 'text-slate-600' }}">
                                {{ $aktivitas11GroupMessage('q2') }}
                            </p>
                        @endif
                    </div>
                </li>

                <li class="grid gap-3 md:grid-cols-[32px_1fr]">
                    <div class="font-bold text-slate-900">3.</div>

                    <div>
                        <div class="mb-3 text-lg text-slate-950">
                            \(x_1x_2 + 3x_3 = -4\)
                        </div>

                        <ul class="space-y-4 pl-5">
                            <li class="list-disc">
                                <label class="text-slate-700">
                                    Komponen Pelanggar (Suku Non-Linear):
                                    Ketik suku spesifik yang melanggar aturan.
                                </label>

                                <input
                                    type="text"
                                    name="answers[q3_pelanggar]"
                                    value="{{ old('answers.q3_pelanggar', $aktivitas11Answers['q3_pelanggar'] ?? '') }}"
                                    class="static-answer mt-2 block w-full rounded-xl border px-3 py-2 text-sm text-slate-900 transition md:w-64 {{ $aktivitas11InputClass('q3_pelanggar') }}"
                                    @if ($aktivitas11InputLocked('q3_pelanggar')) readonly @endif>

                                @error('answers.q3_pelanggar')
                                    <p class="mt-2 rounded-lg bg-yellow-50 p-2 text-sm font-semibold text-yellow-800">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </li>
                        </ul>

                        @if ($aktivitas11GroupMessage('q3'))
                            <p class="mt-4 text-sm font-semibold {{ ($aktivitas11Groups['q3']['status'] ?? null) === 'assisted' ? 'text-indigo-700' : 'text-slate-600' }}">
                                {{ $aktivitas11GroupMessage('q3') }}
                            </p>
                        @endif
                    </div>
                </li>
            </ol>

            @if (! $aktivitas11Selesai)
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <button
                        type="submit"
                        class="rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-700">
                        Cek Jawaban Aktivitas
                    </button>
                </div>
            @endif
        </form>
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
            x-data="{ showActivityModal: true }"
            x-cloak
            x-show="showActivityModal"
            x-transition.opacity
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/70 px-4 py-6 backdrop-blur-sm"
            role="dialog"
            aria-modal="true"
            aria-labelledby="aktivitas11-modal-title">

            <div
                x-show="showActivityModal"
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
                            <p id="aktivitas11-modal-title" class="text-lg font-bold text-slate-900">
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
                            @click="showActivityModal = false"
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
    let staticActiveInput = null;

    document.addEventListener('focusin', function (event) {
        if (event.target.classList.contains('static-answer')) {
            staticActiveInput = event.target;
        }
    });

    function insertStaticSymbol(symbol) {
        if (!staticActiveInput) {
            return;
        }

        const start = staticActiveInput.selectionStart;
        const end = staticActiveInput.selectionEnd;
        const value = staticActiveInput.value;

        staticActiveInput.value = value.substring(0, start) + symbol + value.substring(end);
        staticActiveInput.focus();
        staticActiveInput.selectionStart = staticActiveInput.selectionEnd = start + symbol.length;
    }
</script>