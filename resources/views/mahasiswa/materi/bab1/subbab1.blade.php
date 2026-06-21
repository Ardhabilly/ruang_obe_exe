@include('mahasiswa.materi.bab1.pengantar')

@php
    $aktivitas11 = $practiceSubmissions['aktivitas-1-1'] ?? null;
    $aktivitas11Answers = old('answers', $aktivitas11->answers ?? []);
    $aktivitas11Feedback = $aktivitas11->feedback ?? [];
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
            <div class="text-3xl font-black text-slate-950">
                ax + by = c
            </div>
        </div>

        <p class="mt-4">
            Pada bentuk tersebut, <span class="font-semibold">a</span>,
            <span class="font-semibold">b</span>, dan <span class="font-semibold">c</span>
            merupakan konstanta real. Nilai <span class="font-semibold">a</span> dan
            <span class="font-semibold">b</span> tidak boleh keduanya bernilai nol.
            Persamaan semacam ini disebut persamaan linear dengan variabel
            <span class="font-semibold">x</span> dan <span class="font-semibold">y</span>.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-xl font-black text-slate-900">
            Bentuk Umum Persamaan Linear
        </h3>

        <p class="mt-3">
            Seiring bertambahnya kompleksitas, kita dapat memiliki sangat banyak variabel.
            Secara umum, persamaan linear dengan <span class="font-semibold">n</span> variabel
            dapat dinyatakan dalam bentuk:
        </p>

        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-6 text-center">
            <div class="text-2xl font-black text-slate-950">
                a₁x₁ + a₂x₂ + ... + aₙxₙ = b
            </div>
        </div>

        <p class="mt-4">
            Pada bentuk tersebut, <span class="font-semibold">a₁, a₂, ..., aₙ</span> dan
            <span class="font-semibold">b</span> merupakan konstanta real. Variabel-variabel
            di dalam persamaan ini sering disebut sebagai faktor-faktor yang tidak diketahui
            atau <span class="italic">unknowns</span>.
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
                    <li>• 2x + 3y = 7</li>
                    <li>• 4x₁ − 5x₂ + ½x₃ = 9</li>
                    <li>• x + y − z = 0</li>
                </ul>
            </div>

            <div class="rounded-2xl border border-red-200 bg-white p-5">
                <h4 class="font-black text-red-700">
                    Contoh Bukan Persamaan Linear
                </h4>

                <ul class="mt-4 space-y-3 text-slate-700">
                    <li>
                        • x² + y = 5
                        <span class="block text-sm text-slate-500">
                            Variabel x berpangkat dua.
                        </span>
                    </li>

                    <li>
                        • 2x + √y = 4
                        <span class="block text-sm text-slate-500">
                            Variabel y berada di dalam akar.
                        </span>
                    </li>

                    <li>
                        • x₁x₂ + 3x₃ = −4
                        <span class="block text-sm text-slate-500">
                            Terdapat hasil kali antarvariabel.
                        </span>
                    </li>

                    <li>
                        • sin x + y = 2
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

            <span class="w-fit rounded-full bg-cyan-50 px-4 py-2 text-sm font-bold text-cyan-700">
                Nilai Latihan
            </span>
        </div>

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
                @foreach (['√', 'x₁', 'x₂', 'x₃', '½', '−'] as $symbol)
                    <button
                        type="button"
                        onclick="insertStaticSymbol('{{ $symbol }}')"
                        class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">
                        {{ $symbol }}
                    </button>
                @endforeach
            </div>

            <p class="mt-2 text-xs text-slate-500">
                Klik salah satu simbol setelah memilih kotak jawaban.
            </p>
        </div>

        <form action="{{ route('mahasiswa.practice.submit', [$lesson->slug, 'aktivitas-1-1']) }}" method="POST" data-preserve-scroll="true" class="mt-6 space-y-6">
            @csrf

            <ol class="space-y-6">
                <li class="grid gap-3 md:grid-cols-[32px_1fr]">
                    <div class="font-bold text-slate-900">1.</div>

                    <div>
                        <div class="mb-3 text-lg font-black text-slate-950">
                            4x₁ − 5x₂ + ½x₃ = 9
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
                                    value="{{ $aktivitas11Answers['q1_suku_bermasalah'] ?? '' }}"
                                    class="static-answer mt-2 block w-full rounded-xl border-slate-300 px-3 text-sm focus:border-cyan-500 focus:ring-cyan-500 md:w-64"
                                >

                                @error('answers.q1_suku_bermasalah')
                                    <p class="mt-2 rounded-lg bg-red-50 p-2 text-sm font-semibold text-red-700">
                                        {{ $message }}
                                    </p>
                                @enderror

                                @if (isset($aktivitas11Feedback['q1_suku_bermasalah']))
                                    <p class="mt-2 rounded-lg p-2 text-sm font-semibold
                                        {{ $aktivitas11Feedback['q1_suku_bermasalah']['is_correct'] ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                        {{ $aktivitas11Feedback['q1_suku_bermasalah']['message'] }}
                                    </p>
                                @endif
                            </li>

                            <li class="list-disc">
                                <label class="text-slate-700">
                                    Analisis Karakteristik Pangkat Variabel:
                                    Masukkan pangkat tertinggi dari variabel-variabel di atas.
                                </label>

                                <input
                                    type="text"
                                    name="answers[q1_pangkat]"
                                    value="{{ $aktivitas11Answers['q1_pangkat'] ?? '' }}"
                                    class="static-answer mt-2 block w-full rounded-xl border-slate-300 px-3 text-sm focus:border-cyan-500 focus:ring-cyan-500 md:w-64"
                                >

                                @error('answers.q1_pangkat')
                                    <p class="mt-2 rounded-lg bg-red-50 p-2 text-sm font-semibold text-red-700">
                                        {{ $message }}
                                    </p>
                                @enderror

                                @if (isset($aktivitas11Feedback['q1_pangkat']))
                                    <p class="mt-2 rounded-lg p-2 text-sm font-semibold
                                        {{ $aktivitas11Feedback['q1_pangkat']['is_correct'] ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                        {{ $aktivitas11Feedback['q1_pangkat']['message'] }}
                                    </p>
                                @endif
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="grid gap-3 md:grid-cols-[32px_1fr]">
                    <div class="font-bold text-slate-900">2.</div>

                    <div>
                        <div class="mb-3 text-lg font-black text-slate-950">
                            2x + 3√y − z = 0
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
                                    value="{{ $aktivitas11Answers['q2_pelanggar'] ?? '' }}"
                                    class="static-answer mt-2 block w-full rounded-xl border-slate-300 px-3 text-sm focus:border-cyan-500 focus:ring-cyan-500 md:w-64"
                                >

                                @error('answers.q2_pelanggar')
                                    <p class="mt-2 rounded-lg bg-red-50 p-2 text-sm font-semibold text-red-700">
                                        {{ $message }}
                                    </p>
                                @enderror

                                @if (isset($aktivitas11Feedback['q2_pelanggar']))
                                    <p class="mt-2 rounded-lg p-2 text-sm font-semibold
                                        {{ $aktivitas11Feedback['q2_pelanggar']['is_correct'] ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                        {{ $aktivitas11Feedback['q2_pelanggar']['message'] }}
                                    </p>
                                @endif
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="grid gap-3 md:grid-cols-[32px_1fr]">
                    <div class="font-bold text-slate-900">3.</div>

                    <div>
                        <div class="mb-3 text-lg font-black text-slate-950">
                            x₁x₂ + 3x₃ = −4
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
                                    value="{{ $aktivitas11Answers['q3_pelanggar'] ?? '' }}"
                                    class="static-answer mt-2 block w-full rounded-xl border-slate-300 px-3 text-sm focus:border-cyan-500 focus:ring-cyan-500 md:w-64"
                                >

                                @error('answers.q3_pelanggar')
                                    <p class="mt-2 rounded-lg bg-red-50 p-2 text-sm font-semibold text-red-700">
                                        {{ $message }}
                                    </p>
                                @enderror

                                @if (isset($aktivitas11Feedback['q3_pelanggar']))
                                    <p class="mt-2 rounded-lg p-2 text-sm font-semibold
                                        {{ $aktivitas11Feedback['q3_pelanggar']['is_correct'] ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                        {{ $aktivitas11Feedback['q3_pelanggar']['message'] }}
                                    </p>
                                @endif
                            </li>
                        </ul>
                    </div>
                </li>
            </ol>

            <div class="flex flex-col gap-3 rounded-2xl border border-cyan-200 bg-cyan-50 p-5 md:flex-row md:items-center md:justify-between">
                <div>
                    @if ($aktivitas11)
                        <p class="text-sm font-bold text-cyan-900">
                            Nilai latihan terakhir: {{ $aktivitas11->score }}/{{ $aktivitas11->max_score }}
                        </p>

                        <p class="mt-1 text-sm text-cyan-800">
                            Jawaban sudah diperiksa dan tersimpan sebagai nilai latihan.
                        </p>
                    @else
                        <p class="text-sm font-semibold text-cyan-900">
                            Jawaban aktivitas ini akan diperiksa dan disimpan sebagai nilai latihan.
                        </p>
                    @endif
                </div>

                <button
                    type="submit"
                    class="rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white hover:bg-cyan-700">
                    Cek Jawaban Aktivitas
                </button>
            </div>
        </form>
    </div>
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