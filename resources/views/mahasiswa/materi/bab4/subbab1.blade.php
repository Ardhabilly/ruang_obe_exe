{{-- SUBBAB_4_1_ESELON_BARIS_TEREDUKSI_V1 --}}
@php
    $activity41Key = 'aktivitas-4-1-eselon-tereduksi';
    $activity41Submission = $practiceSubmissions->get($activity41Key);

    $activity41StoredAnswers = is_array($activity41Submission?->answers)
        ? $activity41Submission->answers
        : [];

    $activity41OldAnswers = old('answers', []);
    $activity41OldAnswers = is_array($activity41OldAnswers) ? $activity41OldAnswers : [];

    $activity41Answers = array_replace($activity41StoredAnswers, $activity41OldAnswers);

    $activity41FeedbackRaw = is_array($activity41Submission?->feedback)
        ? $activity41Submission->feedback
        : [];

    $activity41Feedback = is_array($activity41FeedbackRaw['fields'] ?? null)
        ? $activity41FeedbackRaw['fields']
        : collect($activity41FeedbackRaw)
            ->except(['_meta', 'groups', 'fields'])
            ->filter(fn ($item) => is_array($item))
            ->all();

    $activity41Meta = is_array($activity41FeedbackRaw['_meta'] ?? null)
        ? $activity41FeedbackRaw['_meta']
        : [];

    $activity41DefinitionVersion = 'subbab41_eselon_tereduksi_v2';

    $activity41UsesComponentAttemptScope = ($activity41Meta['attempt_scope'] ?? null) === 'component'
        && ($activity41Meta['definition_version'] ?? null) === $activity41DefinitionVersion;

    if (! $activity41UsesComponentAttemptScope && $activity41Submission) {
        $activity41Answers = $activity41OldAnswers;
        $activity41Feedback = [];
        $activity41Meta = [];
    }

    /* SUBBAB_4_BANTUAN_TAMPIL_JAWABAN_FIX */
    foreach ($activity41Feedback as $fieldKey => $fieldFeedback) {
        if (($fieldFeedback['state'] ?? null) !== 'revealed') {
            continue;
        }

        $activity41Answers[$fieldKey] = (string) (
            $fieldFeedback['correct_answer']
            ?? $fieldFeedback['answer']
            ?? ($activity41Answers[$fieldKey] ?? '')
        );
    }
    $activity41Completed = $activity41UsesComponentAttemptScope
        && (bool) ($activity41Submission?->is_completed ?? false);

    $activity41Assisted = ($activity41Meta['completion_mode'] ?? null) === 'bantuan';

    $activity41MaxAttempts = max(1, (int) ($activity41Meta['max_attempts'] ?? 3));

    $activity41Attempts = max(0, min(
        $activity41MaxAttempts,
        (int) ($activity41Meta['attempts'] ?? 0)
    ));

    $activity41RemainingAttempts = max(0, $activity41MaxAttempts - $activity41Attempts);

    $matrixCards = [
        'matrix_a' => [
            'label' => 'Matriks A',
            'tex' => '\\begin{bmatrix} 1 & 0 & 0 & 4 \\\\ 0 & 1 & 0 & -2 \\\\ 0 & 0 & 1 & 3 \\end{bmatrix}',
        ],
        'matrix_b' => [
            'label' => 'Matriks B',
            'tex' => '\\begin{bmatrix} 1 & 3 & -1 & 5 \\\\ 0 & 1 & 2 & 1 \\\\ 0 & 0 & 1 & 4 \\end{bmatrix}',
        ],
        'matrix_c' => [
            'label' => 'Matriks C',
            'tex' => '\\begin{bmatrix} 1 & 2 & 0 & 0 & 7 \\\\ 0 & 0 & 1 & 0 & 3 \\\\ 0 & 0 & 0 & 1 & -1 \\end{bmatrix}',
        ],
        'matrix_d' => [
            'label' => 'Matriks D',
            'tex' => '\\begin{bmatrix} 1 & 0 & 4 & 2 \\\\ 0 & 1 & -3 & 5 \\\\ 0 & 0 & 1 & 8 \\end{bmatrix}',
        ],
        'matrix_e' => [
            'label' => 'Matriks E',
            'tex' => '\\begin{bmatrix} 1 & 0 & 0 & 9 \\\\ 0 & 1 & 0 & 2 \\\\ 0 & 0 & 0 & 0 \\end{bmatrix}',
        ],
    ];

    $activity41Assignments = collect(array_keys($matrixCards))
        ->mapWithKeys(function (string $key) use ($activity41Answers) {
            $answer = (string) ($activity41Answers[$key] ?? '');

            return [$key => in_array($answer, ['eselon', 'tereduksi'], true) ? $answer : ''];
        })
        ->all();

    $activity41FieldStates = collect(array_keys($matrixCards))
        ->mapWithKeys(function (string $key) use ($activity41Feedback) {
            return [$key => $activity41Feedback[$key]['state'] ?? null];
        })
        ->all();

    $activity41StatusClass = function () use ($activity41Completed, $activity41Assisted): string {
        if (! $activity41Completed) {
            return 'bg-yellow-50 text-yellow-700';
        }

        return $activity41Assisted
            ? 'bg-indigo-100 text-indigo-700'
            : 'bg-green-50 text-green-700';
    };

    $activity41StatusLabel = function () use ($activity41Completed, $activity41Assisted): string {
        if (! $activity41Completed) {
            return 'Perlu Diselesaikan';
        }

        return $activity41Assisted
            ? 'Selesai dengan Bantuan'
            : 'Aktivitas Selesai';
    };

    $activity41FeedbackSummary = function () use (
        $activity41Feedback,
        $activity41Completed,
        $activity41Assisted
    ): ?string {
        if (empty($activity41Feedback)) {
            return null;
        }

        if ($activity41Completed) {
            return $activity41Assisted
                ? 'Aktivitas selesai dengan bantuan. Pelajari kembali setiap kolom yang memuat 1 utama.'
                : 'Semua matriks telah dikelompokkan dengan tepat.';
        }

        return 'Periksa kembali kartu berwarna merah. Pada matriks eselon baris tereduksi, setiap kolom yang memuat 1 utama harus memiliki elemen nol di atas dan di bawahnya.';
    };

    $practiceModalPayload = session('practice_modal');

    $practiceModal = is_array($practiceModalPayload)
        && ($practiceModalPayload['practice_key'] ?? null) === $activity41Key
        ? $practiceModalPayload
        : null;
@endphp

@include('mahasiswa.materi.bab4.pengantar')

<section class="space-y-8">
    <div class="space-y-4">
        <h2 class="text-2xl font-black text-slate-950">
            4.1 Matriks Eselon Baris Tereduksi
        </h2>

        <p>
            Agar sistem komputer dapat langsung menampilkan hasil variabel tanpa
            substitusi balik, matriks harus mencapai Bentuk Eselon Baris Tereduksi
            atau <span class="italic">Reduced Row Echelon Form</span>.
        </p>
    </div>

    <div class="rounded-2xl border border-cyan-200 bg-cyan-50 p-6">
        <h3 class="text-xl font-black text-slate-950">
            Syarat Matriks Eselon Baris Tereduksi
        </h3>

        <ol class="mt-5 space-y-4">
            <li class="flex gap-4 rounded-2xl border border-cyan-100 bg-white p-4">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-cyan-600 text-sm font-black text-white">
                    1
                </span>

                <p class="text-sm leading-6 text-slate-700">
                    Matriks harus sudah memenuhi ketiga syarat dasar matriks eselon
                    baris, yaitu memiliki 1 utama, baris nol berada di bagian bawah,
                    dan posisi 1 utama membentuk pola tangga ke kanan.
                </p>
            </li>

            <li class="flex gap-4 rounded-2xl border border-cyan-100 bg-white p-4">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-cyan-600 text-sm font-black text-white">
                    2
                </span>

                <p class="text-sm leading-6 text-slate-700">
                    Pada setiap kolom yang memuat 1 utama, seluruh elemen lain pada
                    kolom tersebut, baik di bawah maupun di atas 1 utama, wajib
                    bernilai 0.
                </p>
            </li>
        </ol>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">
                    Aktivitas 4.1
                </p>

                <h3 class="mt-1 text-xl font-black text-slate-950">
                    Uji Visual Matriks Eselon Baris Tereduksi
                </h3>

                <p class="mt-2 text-slate-600">
                    Kelompokkan setiap matriks berdasarkan syarat tambahan matriks
                    eselon baris tereduksi.
                </p>
            </div>

            <span class="w-fit rounded-full px-4 py-2 text-sm font-bold {{ $activity41StatusClass() }}">
                {{ $activity41StatusLabel() }}
            </span>
        </div>

        @if (! $activity41Completed)
            <div class="mt-5 flex flex-col gap-2 rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm text-cyan-950 sm:flex-row sm:items-center sm:justify-between">
                <p class="font-bold">
                    Kesempatan tersisa: {{ $activity41RemainingAttempts }} dari {{ $activity41MaxAttempts }}
                </p>

                <p class="text-xs leading-5 text-cyan-800">
                    Kartu yang belum ditempatkan tidak mengurangi kesempatan.
                </p>
            </div>
        @endif

        @if ($activity41Submission && $activity41UsesComponentAttemptScope)
            <div class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50 p-4 text-sm text-cyan-900">
                Nilai Aktivitas:
                <span class="font-black">
                    {{ $activity41Submission->score }}/{{ $activity41Submission->max_score }}
                </span>

                @if ($activity41Assisted)
                    <span class="mt-1 block text-xs leading-5">
                        Poin hanya diberikan untuk pengelompokan matriks yang benar secara mandiri.
                    </span>
                @endif
            </div>
        @endif

        <form
            id="aktivitas-4-1-form"
            method="POST"
            action="{{ route('mahasiswa.practice.submit', [$lesson->slug, $activity41Key]) }}"
            class="mt-6"
            x-data="matriksEselonBarisTereduksiActivity({
                initialAssignments: @js($activity41Assignments),
                fieldStates: @js($activity41FieldStates),
                completed: @js($activity41Completed),
            })"
        >
            @csrf

            @foreach ($matrixCards as $matrixKey => $matrixCard)
                <input
                    type="hidden"
                    name="answers[{{ $matrixKey }}]"
                    x-model="assignments['{{ $matrixKey }}']"
                >
            @endforeach

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <p class="font-black text-slate-900">
                    Petunjuk Aktivitas
                </p>

                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Seret setiap kartu matriks ke zona yang tepat. Pada perangkat layar
                    sentuh, pilih kartu terlebih dahulu, kemudian tekan zona tujuan.
                    Gunakan tombol <strong>Kembalikan</strong> apabila kartu perlu
                    dipindahkan kembali.
                </p>
            </div>

            <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-black text-slate-900">
                            Daftar Matriks
                        </p>

                        <p class="mt-1 text-sm text-slate-600">
                            Pilih atau seret satu kartu untuk dikelompokkan.
                        </p>
                    </div>

                    <button
                        type="button"
                        x-cloak
                        x-show="selectedKey"
                        @click="selectedKey = null"
                        class="w-fit rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-100"
                    >
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
                            tabindex="0"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-black text-slate-900">
                                    {{ $matrixCard['label'] }}
                                </p>

                                <span
                                    class="rounded-lg bg-slate-100 px-2 py-1 text-[11px] font-black uppercase tracking-wide text-slate-500"
                                    x-text="selectedKey === '{{ $matrixKey }}' ? 'Dipilih' : 'Seret / Pilih'"
                                ></span>
                            </div>

                            <div class="mt-4 overflow-x-auto text-center text-lg text-slate-950">
                                \[
                                    {!! $matrixCard['tex'] !!}
                                \]
                            </div>
                        </article>
                    @endforeach
                </div>

                <p
                    x-cloak
                    x-show="unassignedKeys().length === 0"
                    class="mt-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-center text-sm font-bold text-green-800"
                >
                    Semua matriks telah dikelompokkan. Periksa kembali sebelum menekan tombol pemeriksaan.
                </p>
            </div>

            <div class="mt-6 grid gap-5 lg:grid-cols-2">
                <section
                    @dragover.prevent
                    @drop.prevent="assignDragged('eselon')"
                    @click="assignSelected('eselon')"
                    :class="zoneClass('eselon')"
                    class="min-h-[280px] rounded-2xl border-2 border-dashed p-5 transition"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold uppercase tracking-wide text-amber-700">
                                Zona Eselon Baris
                            </p>

                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Tempatkan matriks yang sudah berbentuk eselon baris,
                                tetapi belum memenuhi syarat tereduksi.
                            </p>
                        </div>

                        <span
                            class="rounded-xl bg-white/80 px-3 py-2 text-xs font-black text-slate-700"
                            x-text="keysFor('eselon').length + ' kartu'"
                        ></span>
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
                                tabindex="0"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-black text-slate-900">
                                        {{ $matrixCard['label'] }}
                                    </p>

                                    <button
                                        type="button"
                                        x-cloak
                                        x-show="! isLocked('{{ $matrixKey }}')"
                                        @click.stop="unassign('{{ $matrixKey }}')"
                                        class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-100"
                                    >
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

                    <div
                        x-show="keysFor('eselon').length === 0"
                        class="mt-5 flex min-h-24 items-center justify-center rounded-2xl border border-amber-200 bg-white/70 px-4 text-center text-sm font-semibold text-amber-800"
                    >
                        Seret kartu ke sini atau pilih kartu lalu tekan zona ini.
                    </div>
                </section>

                <section
                    @dragover.prevent
                    @drop.prevent="assignDragged('tereduksi')"
                    @click="assignSelected('tereduksi')"
                    :class="zoneClass('tereduksi')"
                    class="min-h-[280px] rounded-2xl border-2 border-dashed p-5 transition"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold uppercase tracking-wide text-green-700">
                                Zona Eselon Baris Tereduksi
                            </p>

                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Tempatkan matriks yang sudah memenuhi syarat tambahan
                                sehingga menjadi bentuk akhir.
                            </p>
                        </div>

                        <span
                            class="rounded-xl bg-white/80 px-3 py-2 text-xs font-black text-slate-700"
                            x-text="keysFor('tereduksi').length + ' kartu'"
                        ></span>
                    </div>

                    <div class="mt-5 space-y-4">
                        @foreach ($matrixCards as $matrixKey => $matrixCard)
                            <article
                                x-show="assignments['{{ $matrixKey }}'] === 'tereduksi'"
                                x-transition.opacity
                                :draggable="! isLocked('{{ $matrixKey }}')"
                                @dragstart.stop="startDrag('{{ $matrixKey }}')"
                                @dragend="draggingKey = null"
                                @click.stop="selectCard('{{ $matrixKey }}')"
                                @keydown.enter.prevent="selectCard('{{ $matrixKey }}')"
                                @keydown.space.prevent="selectCard('{{ $matrixKey }}')"
                                :class="cardClass('{{ $matrixKey }}')"
                                class="cursor-pointer overflow-hidden rounded-2xl border bg-white p-4 shadow-sm transition"
                                tabindex="0"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-black text-slate-900">
                                        {{ $matrixCard['label'] }}
                                    </p>

                                    <button
                                        type="button"
                                        x-cloak
                                        x-show="! isLocked('{{ $matrixKey }}')"
                                        @click.stop="unassign('{{ $matrixKey }}')"
                                        class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-100"
                                    >
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

                    <div
                        x-show="keysFor('tereduksi').length === 0"
                        class="mt-5 flex min-h-24 items-center justify-center rounded-2xl border border-green-200 bg-white/70 px-4 text-center text-sm font-semibold text-green-800"
                    >
                        Seret kartu ke sini atau pilih kartu lalu tekan zona ini.
                    </div>
                </section>
            </div>

            @if ($activity41FeedbackSummary())
                <p class="mt-5 rounded-xl px-4 py-3 text-sm font-semibold {{ $activity41Completed ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-900' }}">
                    {{ $activity41FeedbackSummary() }}
                </p>
            @endif

            @if (! $activity41Completed)
                <div class="mt-6 flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs leading-5 text-slate-500">
                        Pastikan seluruh matriks sudah ditempatkan pada salah satu zona sebelum memeriksa jawaban.
                    </p>

                    <button
                        type="submit"
                        class="rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-700"
                    >
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
            aria-labelledby="aktivitas41-modal-title"
        >
            <div
                x-show="showActivityModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="scale-95 opacity-0"
                x-transition:enter-end="scale-100 opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="scale-100 opacity-100"
                x-transition:leave-end="scale-95 opacity-0"
                class="max-h-[calc(100vh-3rem)] w-full max-w-md overflow-y-auto rounded-[1.5rem] border border-white/10 bg-white shadow-2xl sm:max-w-lg"
            >
                <div class="p-5 sm:p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-xl font-black {{ $modalIsSuccess ? 'bg-green-100 text-green-700' : ($modalIsAssisted ? 'bg-indigo-100 text-indigo-700' : ($modalIsIncomplete ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700')) }}">
                            {{ $modalIsSuccess ? '✓' : ($modalIsAssisted ? 'i' : ($modalIsIncomplete ? '!' : '×')) }}
                        </div>

                        <div class="min-w-0">
                            <p id="aktivitas41-modal-title" class="text-lg font-bold text-slate-900">
                                {{ $practiceModal['title'] ?? 'Hasil Pemeriksaan' }}
                            </p>

                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                {{ $practiceModal['message'] ?? '' }}
                            </p>
                        </div>
                    </div>

                    @if (! empty($modalMessages))
                        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <ul class="space-y-2 text-sm leading-6 text-slate-600">
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
                            class="w-full rounded-xl px-5 py-3 text-sm font-bold transition sm:w-auto {{ $modalIsSuccess ? 'bg-green-600 text-white hover:bg-green-700' : ($modalIsAssisted ? 'bg-indigo-600 text-white hover:bg-indigo-700' : ($modalIsIncomplete ? 'bg-yellow-400 text-slate-900 hover:bg-yellow-300' : 'bg-cyan-600 text-white hover:bg-cyan-700')) }}"
                        >
                            {{ $practiceModal['button_label'] ?? 'Tutup' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</section>

<script>
    function matriksEselonBarisTereduksiActivity(config) {
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
                        ? 'border-amber-400 bg-amber-50 ring-2 ring-amber-100'
                        : 'border-amber-300 bg-amber-50/60';
                }

                return active
                    ? 'border-green-400 bg-green-50 ring-2 ring-green-100'
                    : 'border-green-300 bg-green-50/60';
            },
        };
    }

    (function () {
        const scrollKey = 'ruangobe-practice-scroll:aktivitas-4-1-eselon-tereduksi';
        const form = document.getElementById('aktivitas-4-1-form');

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
                        window.scrollTo({
                            top: Number(savedPosition),
                            left: 0,
                            behavior: 'auto'
                        });

                        sessionStorage.removeItem(scrollKey);
                    }, 0);
                });
            }
        @endif
    })();
</script>
