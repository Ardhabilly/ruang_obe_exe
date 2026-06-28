{{-- SUBBAB_3_1_ESELON_BARIS_V2 --}}
@php
    $activity31Key = 'aktivitas-3-1-eselon-baris';
    $activity31Submission = $practiceSubmissions->get($activity31Key);

    $activity31StoredAnswers = is_array($activity31Submission?->answers)
        ? $activity31Submission->answers
        : [];

    $activity31OldAnswers = old('answers', []);
    $activity31OldAnswers = is_array($activity31OldAnswers) ? $activity31OldAnswers : [];
    $activity31Answers = array_replace($activity31StoredAnswers, $activity31OldAnswers);

    $activity31FeedbackRaw = is_array($activity31Submission?->feedback)
        ? $activity31Submission->feedback
        : [];

    $activity31Feedback = is_array($activity31FeedbackRaw['fields'] ?? null)
        ? $activity31FeedbackRaw['fields']
        : collect($activity31FeedbackRaw)
            ->except(['_meta', 'groups', 'fields'])
            ->filter(fn ($item) => is_array($item))
            ->all();

    $activity31Meta = is_array($activity31FeedbackRaw['_meta'] ?? null)
        ? $activity31FeedbackRaw['_meta']
        : [];

    $activity31UsesComponentAttemptScope = ($activity31Meta['attempt_scope'] ?? null) === 'component';

    if (! $activity31UsesComponentAttemptScope && $activity31Submission) {
        $activity31Answers = $activity31OldAnswers;
        $activity31Feedback = [];
        $activity31Meta = [];
    }

    $activity31Completed = $activity31UsesComponentAttemptScope
        && (bool) ($activity31Submission?->is_completed ?? false);

    $activity31Assisted = ($activity31Meta['completion_mode'] ?? null) === 'bantuan';
    $activity31MaxAttempts = max(1, (int) ($activity31Meta['max_attempts'] ?? 3));
    $activity31Attempts = max(0, min(
        $activity31MaxAttempts,
        (int) ($activity31Meta['attempts'] ?? 0)
    ));
    $activity31RemainingAttempts = max(0, $activity31MaxAttempts - $activity31Attempts);

    $matrixCards = [
        'matrix_a' => [
            'label' => 'Matriks A',
            'tex' => '\\begin{bmatrix} 1 & 2 & 3 & 4 \\\\ 0 & 1 & -1 & 5 \\\\ 0 & 0 & 1 & 2 \\end{bmatrix}',
        ],
        'matrix_b' => [
            'label' => 'Matriks B',
            'tex' => '\\begin{bmatrix} 1 & 0 & 0 & 2 \\\\ 0 & 0 & 1 & 3 \\\\ 0 & 1 & 0 & 4 \\end{bmatrix}',
        ],
        'matrix_c' => [
            'label' => 'Matriks C',
            'tex' => '\\begin{bmatrix} 1 & 2 & 3 & 4 \\\\ 0 & 2 & 1 & 5 \\\\ 0 & 0 & 1 & 6 \\end{bmatrix}',
        ],
        'matrix_d' => [
            'label' => 'Matriks D',
            'tex' => '\\begin{bmatrix} 1 & 4 & 5 & 0 & 9 \\\\ 0 & 0 & 1 & 2 & 3 \\\\ 0 & 0 & 0 & 0 & 0 \\end{bmatrix}',
        ],
        'matrix_e' => [
            'label' => 'Matriks E',
            'tex' => '\\begin{bmatrix} 0 & 1 & 2 & -1 \\\\ 1 & 0 & 0 & 2 \\\\ 0 & 0 & 0 & 0 \\end{bmatrix}',
        ],
    ];

    $activity31Assignments = collect(array_keys($matrixCards))
        ->mapWithKeys(function (string $key) use ($activity31Answers) {
            $answer = (string) ($activity31Answers[$key] ?? '');

            return [$key => in_array($answer, ['eselon', 'bukan'], true) ? $answer : ''];
        })
        ->all();

    $activity31FieldStates = collect(array_keys($matrixCards))
        ->mapWithKeys(function (string $key) use ($activity31Feedback) {
            return [$key => $activity31Feedback[$key]['state'] ?? null];
        })
        ->all();

    $activity31StatusClass = function () use ($activity31Completed, $activity31Assisted): string {
        if (! $activity31Completed) {
            return 'bg-yellow-50 text-yellow-700';
        }

        return $activity31Assisted
            ? 'bg-indigo-100 text-indigo-700'
            : 'bg-green-50 text-green-700';
    };

    $activity31StatusLabel = function () use ($activity31Completed, $activity31Assisted): string {
        if (! $activity31Completed) {
            return 'Perlu Diselesaikan';
        }

        return $activity31Assisted ? 'Selesai dengan Bantuan' : 'Aktivitas Selesai';
    };

    $activity31FeedbackSummary = function () use ($activity31Feedback, $activity31Completed, $activity31Assisted): ?string {
        if (empty($activity31Feedback)) {
            return null;
        }

        if ($activity31Completed) {
            return $activity31Assisted
                ? 'Aktivitas selesai dengan bantuan. Pelajari kembali posisi setiap matriks pada zona yang ditampilkan.'
                : 'Semua matriks telah dikelompokkan dengan tepat.';
        }

        return 'Periksa kembali kartu berwarna merah. Kartu berwarna kuning belum ditempatkan pada salah satu zona.';
    };

    $practiceModalPayload = session('practice_modal');
    $practiceModal = is_array($practiceModalPayload)
        && ($practiceModalPayload['practice_key'] ?? null) === $activity31Key
        ? $practiceModalPayload
        : null;
@endphp

@include('mahasiswa.materi.bab3.pengantar')
<section class="space-y-8">
    <div class="space-y-4">
        <h2 class="text-2xl font-black text-slate-950">
            3.1 Matriks Eselon Baris
        </h2>

        <p>
            Suatu matriks dikatakan berada dalam bentuk eselon baris apabila memenuhi tiga syarat.
            Bentuk ini digunakan sebagai hasil antara pada proses Eliminasi Gauss sebelum solusi
            Sistem Persamaan Linear ditentukan.
        </p>
    </div>

    <div class="rounded-2xl border border-cyan-200 bg-cyan-50 p-6">
        <h3 class="text-xl font-black text-slate-950">
            Syarat Matriks Eselon Baris
        </h3>

        <ol class="mt-5 space-y-4">
            <li class="flex gap-4 rounded-2xl border border-cyan-100 bg-white p-4">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-cyan-600 text-sm font-black text-white">1</span>
                <p class="text-sm leading-6 text-slate-700">
                    Jika satu baris tidak seluruhnya terdiri dari nol, maka bilangan tak nol pertama
                    pada baris itu adalah angka 1. Angka 1 ini disebut sebagai <strong>1 utama</strong>.
                </p>
            </li>

            <li class="flex gap-4 rounded-2xl border border-cyan-100 bg-white p-4">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-cyan-600 text-sm font-black text-white">2</span>
                <p class="text-sm leading-6 text-slate-700">
                    Jika terdapat baris yang seluruhnya terdiri dari angka nol, maka baris-baris
                    tersebut harus dikelompokkan bersama pada bagian paling bawah matriks.
                </p>
            </li>

            <li class="flex gap-4 rounded-2xl border border-cyan-100 bg-white p-4">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-cyan-600 text-sm font-black text-white">3</span>
                <p class="text-sm leading-6 text-slate-700">
                    Jika terdapat dua baris berurutan yang tidak seluruhnya terdiri dari nol, maka
                    1 utama pada baris yang lebih rendah harus terletak pada kolom yang lebih kanan
                    daripada 1 utama pada baris di atasnya.
                </p>
            </li>
        </ol>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-xl font-black text-slate-900">
            Contoh Matriks Eselon Baris
        </h3>

        <p class="mt-3">
            Perhatikan bagaimana angka 1 utama pada matriks berikut membentuk pola tangga yang
            menurun ke kanan. Setiap elemen tepat di bawah 1 utama juga bernilai 0.
        </p>

        <div class="mt-5 grid gap-4 lg:grid-cols-2">
            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-5 text-center">
                <p class="text-sm font-bold text-slate-600">Contoh 1</p>
                <div class="mt-3 text-xl text-slate-950">
                    \[
                        \begin{bmatrix}
                            1 & 4 & -3 & 7 \\
                            0 & 1 & 6 & 2 \\
                            0 & 0 & 1 & 5
                        \end{bmatrix}
                    \]
                </div>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-5 text-center">
                <p class="text-sm font-bold text-slate-600">Contoh 2</p>
                <div class="mt-3 text-xl text-slate-950">
                    \[
                        \begin{bmatrix}
                            1 & 1 & 0 & 3 \\
                            0 & 1 & 0 & 4 \\
                            0 & 0 & 0 & 0
                        \end{bmatrix}
                    \]
                </div>
            </div>
        </div>

        <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950">
            <p class="font-black">Petunjuk visual</p>
            <p class="mt-1">
                Baca setiap baris dari kiri ke kanan. Temukan bilangan tak nol pertama, lalu periksa
                apakah nilainya 1 dan apakah posisinya membentuk pola tangga ke kanan.
            </p>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">
                    Aktivitas 3.1
                </p>

                <h3 class="mt-1 text-xl font-black text-slate-950">
                    Uji Visual Eselon Baris
                </h3>

                <p class="mt-2 text-slate-600">
                    Kelompokkan lima matriks ke dalam zona yang sesuai berdasarkan tiga syarat
                    matriks eselon baris.
                </p>
            </div>

            <span class="w-fit rounded-full px-4 py-2 text-sm font-bold {{ $activity31StatusClass() }}">
                {{ $activity31StatusLabel() }}
            </span>
        </div>

        @if (! $activity31Completed)
            <div class="mt-5 flex flex-col gap-2 rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm text-cyan-950 sm:flex-row sm:items-center sm:justify-between">
                <p class="font-bold">
                    Kesempatan tersisa: {{ $activity31RemainingAttempts }} dari {{ $activity31MaxAttempts }}
                </p>

                <p class="text-xs leading-5 text-cyan-800">
                    Kartu yang belum ditempatkan tidak mengurangi kesempatan.
                </p>
            </div>
        @endif

        @if ($activity31Submission && $activity31UsesComponentAttemptScope)
            <div class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4 text-sm text-cyan-900">
                Nilai Aktivitas: <span class="font-black">{{ $activity31Submission->score }}/{{ $activity31Submission->max_score }}</span>

                @if ($activity31Assisted)
                    <span class="mt-1 block text-xs leading-5">
                        Poin hanya diberikan untuk pengelompokan matriks yang benar secara mandiri.
                    </span>
                @endif
            </div>
        @endif

        <form
            id="aktivitas-3-1-form"
            method="POST"
            action="{{ route('mahasiswa.practice.submit', [$lesson->slug, $activity31Key]) }}"
            class="mt-6"
            x-data="eselonBarisActivity({
                initialAssignments: @js($activity31Assignments),
                fieldStates: @js($activity31FieldStates),
                completed: @js($activity31Completed),
            })">
            @csrf

            @foreach ($matrixCards as $matrixKey => $matrixCard)
                <input type="hidden" name="answers[{{ $matrixKey }}]" x-model="assignments['{{ $matrixKey }}']">
            @endforeach

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <p class="font-black text-slate-900">Petunjuk Aktivitas</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Tarik dan lepaskan setiap kartu matriks ke zona yang sesuai. Pada perangkat layar sentuh,
                    pilih kartu terlebih dahulu, kemudian tekan zona tujuan. Gunakan tombol <strong>Kembalikan</strong>
                    untuk memindahkan kartu yang belum terkunci.
                </p>
            </div>

            <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-black text-slate-900">Daftar Matriks</p>
                        <p class="mt-1 text-sm text-slate-600">Pilih atau seret satu kartu untuk dikelompokkan.</p>
                    </div>

                    <button
                        type="button"
                        x-cloak
                        x-show="selectedKey"
                        @click="selectedKey = null"
                        class="w-fit rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-100">
                        Batalkan Pilihan
                    </button>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($matrixCards as $matrixKey => $matrixCard)
                        <article
                            x-show="assignments['{{ $matrixKey }}'] === ''"
                            x-transition.opacity
                            :draggable="! isLocked('{{ $matrixKey }}')"
                            @dragstart="startDrag('{{ $matrixKey }}')"
                            @dragend="draggingKey = null"
                            @click="selectCard('{{ $matrixKey }}')"
                            @keydown.enter.prevent="selectCard('{{ $matrixKey }}')"
                            @keydown.space.prevent="selectCard('{{ $matrixKey }}')"
                            :class="cardClass('{{ $matrixKey }}')"
                            class="cursor-pointer overflow-hidden rounded-2xl border bg-white p-4 shadow-sm transition"
                            tabindex="0">

                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-black text-slate-900">{{ $matrixCard['label'] }}</p>
                                <span class="rounded-lg bg-slate-100 px-2 py-1 text-[11px] font-black uppercase tracking-wide text-slate-500" x-text="selectedKey === '{{ $matrixKey }}' ? 'Dipilih' : 'Seret / Pilih'"></span>
                            </div>

                            <div class="mt-4 overflow-x-auto text-center text-lg text-slate-950">
                                \[
                                    {!! $matrixCard['tex'] !!}
                                \]
                            </div>
                        </article>
                    @endforeach
                </div>

                <p x-cloak x-show="unassignedKeys().length === 0" class="mt-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-center text-sm font-bold text-green-800">
                    Semua matriks telah dikelompokkan. Periksa kembali sebelum menekan tombol pemeriksaan.
                </p>
            </div>

            <div class="mt-6 grid gap-5 lg:grid-cols-2">
                <section
                    @dragover.prevent
                    @drop.prevent="assignDragged('eselon')"
                    @click="assignSelected('eselon')"
                    :class="zoneClass('eselon')"
                    class="min-h-[280px] rounded-2xl border-2 border-dashed p-5 transition">

                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold uppercase tracking-wide text-green-700">Zona Matriks Eselon Baris</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Tempatkan matriks yang memenuhi ketiga syarat eselon baris.
                            </p>
                        </div>

                        <span class="rounded-xl bg-white/80 px-3 py-2 text-xs font-black text-slate-700" x-text="keysFor('eselon').length + ' kartu'"></span>
                    </div>

                    <div class="mt-5 space-y-4">
                        @foreach ($matrixCards as $matrixKey => $matrixCard)
                            <article
                                x-show="assignments['{{ $matrixKey }}'] === 'eselon'"
                                x-transition.opacity
                                :draggable="! isLocked('{{ $matrixKey }}')"
                                @dragstart.stop="startDrag('{{ $matrixKey }}')"
                                @dragend="draggingKey = null"
                                @click.stop="selectCard('{{ $matrixKey }}')"
                                @keydown.enter.prevent="selectCard('{{ $matrixKey }}')"
                                @keydown.space.prevent="selectCard('{{ $matrixKey }}')"
                                :class="cardClass('{{ $matrixKey }}')"
                                class="cursor-pointer overflow-hidden rounded-2xl border bg-white p-4 shadow-sm transition"
                                tabindex="0">

                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-black text-slate-900">{{ $matrixCard['label'] }}</p>
                                    <button
                                        type="button"
                                        x-cloak
                                        x-show="! isLocked('{{ $matrixKey }}')"
                                        @click.stop="unassign('{{ $matrixKey }}')"
                                        class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-100">
                                        Kembalikan
                                    </button>
                                </div>

                                <div class="mt-4 overflow-x-auto text-center text-lg text-slate-950">
                                    \[
                                        {!! $matrixCard['tex'] !!}
                                    \]
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div x-show="keysFor('eselon').length === 0" class="mt-5 flex min-h-24 items-center justify-center rounded-2xl border border-green-200 bg-white/70 px-4 text-center text-sm font-semibold text-green-800">
                        Seret kartu ke sini atau pilih kartu lalu tekan zona ini.
                    </div>
                </section>

                <section
                    @dragover.prevent
                    @drop.prevent="assignDragged('bukan')"
                    @click="assignSelected('bukan')"
                    :class="zoneClass('bukan')"
                    class="min-h-[280px] rounded-2xl border-2 border-dashed p-5 transition">

                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold uppercase tracking-wide text-rose-700">Zona Bukan Eselon Baris</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Tempatkan matriks yang melanggar salah satu atau beberapa syarat.
                            </p>
                        </div>

                        <span class="rounded-xl bg-white/80 px-3 py-2 text-xs font-black text-slate-700" x-text="keysFor('bukan').length + ' kartu'"></span>
                    </div>

                    <div class="mt-5 space-y-4">
                        @foreach ($matrixCards as $matrixKey => $matrixCard)
                            <article
                                x-show="assignments['{{ $matrixKey }}'] === 'bukan'"
                                x-transition.opacity
                                :draggable="! isLocked('{{ $matrixKey }}')"
                                @dragstart.stop="startDrag('{{ $matrixKey }}')"
                                @dragend="draggingKey = null"
                                @click.stop="selectCard('{{ $matrixKey }}')"
                                @keydown.enter.prevent="selectCard('{{ $matrixKey }}')"
                                @keydown.space.prevent="selectCard('{{ $matrixKey }}')"
                                :class="cardClass('{{ $matrixKey }}')"
                                class="cursor-pointer overflow-hidden rounded-2xl border bg-white p-4 shadow-sm transition"
                                tabindex="0">

                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-black text-slate-900">{{ $matrixCard['label'] }}</p>
                                    <button
                                        type="button"
                                        x-cloak
                                        x-show="! isLocked('{{ $matrixKey }}')"
                                        @click.stop="unassign('{{ $matrixKey }}')"
                                        class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-100">
                                        Kembalikan
                                    </button>
                                </div>

                                <div class="mt-4 overflow-x-auto text-center text-lg text-slate-950">
                                    \[
                                        {!! $matrixCard['tex'] !!}
                                    \]
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div x-show="keysFor('bukan').length === 0" class="mt-5 flex min-h-24 items-center justify-center rounded-2xl border border-rose-200 bg-white/70 px-4 text-center text-sm font-semibold text-rose-800">
                        Seret kartu ke sini atau pilih kartu lalu tekan zona ini.
                    </div>
                </section>
            </div>

            @if ($activity31FeedbackSummary())
                <p class="mt-5 rounded-xl px-4 py-3 text-sm font-semibold {{ $activity31Completed ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-900' }}">
                    {{ $activity31FeedbackSummary() }}
                </p>
            @endif

            @if (! $activity31Completed)
                <div class="mt-6 flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs leading-5 text-slate-500">
                        Pastikan seluruh matriks sudah ditempatkan pada salah satu zona sebelum memeriksa jawaban.
                    </p>

                    <button type="submit" class="rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-700">
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
                ? collect($practiceModal['feedback_messages'])
                    ->reject(fn ($message) => str_starts_with((string) $message, 'Bagian yang masih perlu diperbaiki:'))
                    ->values()
                    ->all()
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
            aria-labelledby="aktivitas31-modal-title">

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
                            <p id="aktivitas31-modal-title" class="text-lg font-bold text-slate-900">
                                {{ $practiceModal['title'] ?? 'Hasil Pemeriksaan' }}
                            </p>

                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                {{ $practiceModal['message'] ?? '' }}
                            </p>
                        </div>
                    </div>

                    @if (! empty($modalMessages))
                        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-800">Perhatikan kembali:</p>
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
    function eselonBarisActivity(config) {
        return {
            assignments: config.initialAssignments || {},
            fieldStates: config.fieldStates || {},
            completed: Boolean(config.completed),
            selectedKey: null,
            draggingKey: null,
            cardOrder: ['matrix_a', 'matrix_b', 'matrix_c', 'matrix_d', 'matrix_e'],

            isLocked(key) {
                const state = this.fieldStates[key] || null;

                return this.completed || state === 'correct' || state === 'revealed';
            },

            startDrag(key) {
                if (this.isLocked(key)) {
                    return;
                }

                this.draggingKey = key;
                this.selectedKey = key;
            },

            selectCard(key) {
                if (this.isLocked(key)) {
                    return;
                }

                this.selectedKey = this.selectedKey === key ? null : key;
            },

            assignDragged(zone) {
                if (! this.draggingKey) {
                    return;
                }

                this.assign(this.draggingKey, zone);
                this.draggingKey = null;
            },

            assignSelected(zone) {
                if (! this.selectedKey) {
                    return;
                }

                this.assign(this.selectedKey, zone);
            },

            assign(key, zone) {
                if (this.isLocked(key)) {
                    return;
                }

                this.assignments[key] = zone;
                this.selectedKey = null;
            },

            unassign(key) {
                if (this.isLocked(key)) {
                    return;
                }

                this.assignments[key] = '';
                this.selectedKey = null;
            },

            keysFor(zone) {
                return this.cardOrder.filter((key) => this.assignments[key] === zone);
            },

            unassignedKeys() {
                return this.cardOrder.filter((key) => ! this.assignments[key]);
            },

            cardClass(key) {
                const state = this.fieldStates[key] || null;
                const selected = this.selectedKey === key;
                let style = 'border-slate-200 hover:border-cyan-300 hover:shadow-md';

                if (state === 'correct') {
                    style = 'border-green-400 bg-green-50';
                } else if (state === 'revealed') {
                    style = 'border-indigo-400 bg-indigo-50';
                } else if (state === 'wrong') {
                    style = 'border-red-400 bg-red-50';
                } else if (state === 'empty') {
                    style = 'border-yellow-400 bg-yellow-50';
                }

                return style + (selected ? ' ring-2 ring-cyan-400 ring-offset-2' : '');
            },

            zoneClass(zone) {
                const active = this.selectedKey !== null || this.draggingKey !== null;

                if (zone === 'eselon') {
                    return active
                        ? 'border-green-400 bg-green-50 ring-2 ring-green-100'
                        : 'border-green-300 bg-green-50/60';
                }

                return active
                    ? 'border-rose-400 bg-rose-50 ring-2 ring-rose-100'
                    : 'border-rose-300 bg-rose-50/60';
            },
        };
    }

    (function () {
        const scrollKey = 'ruangobe-practice-scroll:aktivitas-3-1-eselon-baris';
        const form = document.getElementById('aktivitas-3-1-form');

        if (form) {
            form.addEventListener('submit', function () {
                sessionStorage.setItem(scrollKey, String(window.scrollY));
            });
        }

        @if ($practiceModal)
            const savedPosition = sessionStorage.getItem(scrollKey);

            if (savedPosition !== null) {
                requestAnimationFrame(function () {
                    setTimeout(function () {
                        window.scrollTo({ top: Number(savedPosition), left: 0, behavior: 'auto' });
                        sessionStorage.removeItem(scrollKey);
                    }, 0);
                });
            }
        @endif
    })();
</script>
