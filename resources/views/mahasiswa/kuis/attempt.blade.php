<x-app-layout>
    @php
        $quiz = $attempt->quiz;
        $questions = $quiz->questions->values();
        $responsesByQuestion = $responsesByQuestion ?? collect();
    @endphp

    <div
        x-data="{
            current: 0,
            remaining: {{ (int) $remainingSeconds }},
            totalQuestions: {{ $questions->count() }},
            statuses: {},

            saveUrl: @js(route('mahasiswa.kuis.save', $attempt)),
            csrfToken: @js(csrf_token()),
            saveTimer: null,
            isSaving: false,
            saveQueued: false,
            saveError: null,

            showSubmitModal: false,
            submitModalMode: 'confirm',
            isSubmitting: false,

            timerText() {
                const total = Math.max(0, Math.floor(this.remaining));
                const minutes = Math.floor(total / 60);
                const seconds = total % 60;

                return String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            },

            init() {
                this.refreshStatuses();
                this.initTimer();

                this.$watch('current', () => {
                    this.$nextTick(() => window.renderMathJax?.());
                });

                this.$nextTick(() => {
                    this.refreshStatuses();
                    window.renderMathJax?.();
                });
            },

            initTimer() {
                const interval = setInterval(() => {
                    if (this.remaining > 0) {
                        this.remaining--;
                    }

                    if (this.remaining <= 0) {
                        clearInterval(interval);
                        document.getElementById('auto_submitted').value = '1';
                        document.getElementById('quiz-form').requestSubmit();
                    }
                }, 1000);
            },

            scheduleSave() {
                if (this.remaining <= 0) {
                    return;
                }

                this.saveError = null;
                clearTimeout(this.saveTimer);

                this.saveTimer = setTimeout(() => {
                    this.saveProgress();
                }, 700);
            },

            async saveProgress() {
                if (this.remaining <= 0) {
                    return;
                }

                if (this.isSaving) {
                    this.saveQueued = true;
                    return;
                }

                const form = document.getElementById('quiz-form');

                if (!form) {
                    return;
                }

                const formData = new FormData(form);

                for (const key of Array.from(formData.keys())) {
                    if (key.includes('[step_file]')) {
                        formData.delete(key);
                    }
                }

                this.isSaving = true;
                this.saveError = null;

                try {
                    const response = await fetch(this.saveUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    const data = await response.json().catch(() => ({}));

                    if (response.status === 409 && data.redirect) {
                        window.location.href = data.redirect;
                        return;
                    }

                    if (!response.ok || !data.saved) {
                        throw new Error(data.message || 'Jawaban gagal disimpan.');
                    }

                } catch (error) {
                    this.saveError = error.message || 'Jawaban gagal disimpan.';
                } finally {
                    this.isSaving = false;

                    if (this.saveQueued) {
                        this.saveQueued = false;
                        this.scheduleSave();
                    }
                }
            },

            refreshStatuses() {
                const nextStatuses = {};

                document.querySelectorAll('[data-question-card]').forEach((card) => {
                    const questionId = card.dataset.questionId;

                    nextStatuses[questionId] = {
                        answered: this.isQuestionAnswered(card),
                        doubtful: this.isQuestionDoubtful(card),
                    };
                });

                this.statuses = nextStatuses;
            },

            isQuestionAnswered(card) {
                const checkboxes = card.querySelectorAll(
                    '[data-answer-input][type=checkbox]:not([data-doubt-input])'
                );

                if (checkboxes.length > 0) {
                    return Array.from(checkboxes).some(input => input.checked);
                }

                const finalInputs = card.querySelectorAll('[data-final-answer-input]');

                if (finalInputs.length > 0) {
                    return Array.from(finalInputs).every(input => input.value.trim() !== '');
                }

                const matrixInputs = card.querySelectorAll('[data-matrix-input]');

                if (matrixInputs.length > 0) {
                    return Array.from(matrixInputs).every(input => input.value.trim() !== '');
                }

                const textInputs = card.querySelectorAll(
                    '[data-answer-input]:not([type=checkbox])'
                );

                if (textInputs.length > 0) {
                    return Array.from(textInputs).some(input => input.value.trim() !== '');
                }

                return false;
            },

            isQuestionDoubtful(card) {
                const doubtInput = card.querySelector('[data-doubt-input]');

                return doubtInput ? doubtInput.checked : false;
            },

            answeredCount() {
                return Object.values(this.statuses)
                    .filter(status => status.answered)
                    .length;
            },

            doubtfulCount() {
                return Object.values(this.statuses)
                    .filter(status => status.doubtful)
                    .length;
            },

            allAnswered() {
                return this.answeredCount() === this.totalQuestions;
            },

            questionButtonClass(questionId, index) {
                const status = this.statuses[questionId] || {
                    answered: false,
                    doubtful: false
                };

                const isActive = this.current === index;

                if (status.doubtful) {
                    return isActive
                        ? 'bg-yellow-400 text-slate-950 ring-2 ring-cyan-200 hover:bg-yellow-300'
                        : 'bg-yellow-400 text-slate-950 hover:bg-yellow-300';
                }

                if (isActive) {
                    return 'bg-cyan-400 text-slate-950 ring-2 ring-cyan-200';
                }

                if (status.answered) {
                    return 'bg-green-400 text-slate-950 hover:bg-green-300';
                }

                return 'bg-white/10 text-slate-200 hover:bg-white/20';
            },

            handleFormSubmit() {
                const isAutoSubmitted =
                    document.getElementById('auto_submitted').value === '1';

                if (isAutoSubmitted) {
                    this.isSubmitting = true;
                    document.getElementById('quiz-form').submit();
                    return;
                }

                this.requestSubmit();
            },

            requestSubmit() {
                this.refreshStatuses();

                if (!this.allAnswered()) {
                    this.submitModalMode = 'incomplete';
                    this.showSubmitModal = true;
                    return;
                }

                this.submitModalMode = 'confirm';
                this.showSubmitModal = true;
            },

            closeSubmitModal() {
                if (!this.isSubmitting) {
                    this.showSubmitModal = false;
                }
            },

            confirmSubmit() {
                this.refreshStatuses();

                if (!this.allAnswered()) {
                    this.submitModalMode = 'incomplete';
                    return;
                }

                this.isSubmitting = true;
                this.showSubmitModal = false;

                document.getElementById('quiz-form').submit();
            }
        }"
        x-init="init()"
        class="min-h-screen overflow-y-auto px-3 py-3 sm:px-4 lg:h-screen lg:overflow-hidden lg:px-5">

        <form
            id="quiz-form"
            action="{{ route('mahasiswa.kuis.submit', $attempt) }}"
            method="POST"
            enctype="multipart/form-data"
            class="min-h-screen lg:h-full lg:min-h-0"
            @submit.prevent="handleFormSubmit()"
            @input="refreshStatuses(); scheduleSave()"
            @change="refreshStatuses(); scheduleSave()">

            @csrf

            <input type="hidden" name="auto_submitted" id="auto_submitted" value="0">

            <div class="mx-auto grid min-h-screen max-w-[1500px] gap-4 lg:h-full lg:min-h-0 lg:grid-cols-[minmax(0,1fr)_290px]">
                <section class="order-2 flex min-h-0 flex-col lg:order-1 lg:h-full">
                    <div class="min-h-0 flex-1">
                        @foreach ($questions as $index => $question)
                            @php
                                $data = $question->question_data ?? [];

                                $savedResponse = $responsesByQuestion->get($question->id);
                                $savedValue = $savedResponse?->response_value ?? [];

                                if (!is_array($savedValue)) {
                                    $savedValue = [];
                                }

                                $savedDoubtful = (bool) ($savedResponse?->is_marked_doubtful ?? false);

                                $displayText = $question->question_text;
                                $equationsTex = [];
                                $matrixRowTex = null;
                                $extraNote = null;

                                if ($question->order_number === 5) {
                                    $displayText = 'Diberikan baris kedua dari sebuah Augmented Matrix yang memuat variabel x, y, dan z. Tuliskan bentuk persamaan linear utuh dari array di bawah ini.';
                                    $matrixRowTex = '\left[\,0\quad 3\quad -1\mid 8\,\right]';
                                }

                                if ($question->order_number === 7) {
                                    $displayText = 'Selesaikan Sistem Persamaan Linear berikut.';
                                    $equationsTex = [
                                        'x + y + z = 6',
                                        '2x - y + z = 3',
                                        'x + 2y - z = 2',
                                    ];
                                    $extraNote = 'Unggah file langkah pengerjaan, lalu masukkan jawaban akhir.';
                                }

                                if ($question->order_number === 8) {
                                    $displayText = 'Perhatikan Sistem Persamaan Linear berikut. Ekstraksilah koefisien dari ketiga persamaan ke dalam struktur Matriks Koefisien (A) berukuran 3 × 3.';
                                    $equationsTex = [
                                        '2x + 3y - z = 5',
                                        'x + 2z = 4',
                                        '-x + y + z = 0',
                                    ];
                                }

                                if ($question->order_number === 9) {
                                    $displayText = 'Diberikan sebuah Sistem Persamaan Linear. Lengkapi struktur notasi standar Ax = b berdasarkan komponen variabel dan nilai hasil dari sistem di bawah ini.';
                                    $equationsTex = [
                                        '4x - y + 2z = 7',
                                        '-x + 3y - z = -2',
                                        '2x + y + 5z = 10',
                                    ];
                                }

                                if ($question->order_number === 10) {
                                    $displayText = 'Sebagai tantangan terakhir, ubahlah Sistem Persamaan Linear di bawah ini ke dalam format komputasi tunggal atau Augmented Matrix.';
                                    $equationsTex = [
                                        'x - 2y + 3z = 9',
                                        '-x + 3y = -4',
                                        '2x - 5y + 5z = 17',
                                    ];
                                }
                            @endphp

                            <div
                                x-show="current === {{ $index }}"
                                x-cloak
                                data-question-card
                                data-question-id="{{ $question->id }}"
                                class="flex min-h-[520px] flex-col rounded-[1.35rem] border border-white/10 bg-white/[0.96] text-slate-800 shadow-2xl shadow-slate-950/20 lg:h-full lg:min-h-0">

                                <div class="shrink-0 border-b border-slate-200 px-4 py-3 sm:px-5">
                                    <p class="text-xs font-black uppercase tracking-[0.18em] text-cyan-700">
                                        Soal {{ $index + 1 }} dari {{ $questions->count() }}
                                    </p>

                                    <h2 class="mt-2 text-base font-black leading-6 text-slate-900 lg:text-lg lg:leading-7">
                                        {{ $displayText }}
                                    </h2>

                                    @if ($extraNote)
                                        <p class="mt-1 text-xs font-semibold text-slate-600 lg:text-sm">
                                            {{ $extraNote }}
                                        </p>
                                    @endif
                                </div>

                                <div class="flex-1 overflow-visible px-4 py-3 sm:px-5 lg:overflow-hidden">
                                    @if ($matrixRowTex)
                                        <div class="mb-4 flex justify-center overflow-x-auto pb-1">
                                            <div class="rounded-2xl border border-slate-300 bg-slate-50 px-7 py-3 text-lg font-black text-slate-900">
                                                \[{{ $matrixRowTex }}\]
                                            </div>
                                        </div>
                                    @endif

                                    @if (!empty($equationsTex))
                                        <div class="mb-4 flex justify-center">
                                            <div class="min-w-[230px] rounded-2xl border border-slate-300 bg-slate-50 px-6 py-2 text-center text-base font-semibold leading-7 text-slate-900">
                                                @foreach ($equationsTex as $equation)
                                                    <div>\({{ $equation }}\)</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if (!empty($data['image']))
                                        <div class="mb-4 flex justify-center">
                                            <img
                                                src="{{ asset($data['image']) }}"
                                                alt="Gambar soal"
                                                class="max-h-56 rounded-2xl border border-slate-200 bg-white object-contain p-2">
                                        </div>
                                    @endif

                                    @if ($question->question_type === 'checkbox')
                                        <div class="space-y-2">
                                            @foreach (($data['options'] ?? []) as $key => $option)
                                                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:bg-cyan-50">
                                                    <input
                                                        type="checkbox"
                                                        name="responses[{{ $question->id }}][selected][]"
                                                        value="{{ $key }}"
                                                        data-answer-input
                                                        @checked(in_array($key, $savedValue['selected'] ?? [], true))
                                                        class="mt-1 rounded border-slate-300 text-cyan-600">

                                                    <span class="text-sm leading-6 text-slate-700 lg:text-base">
                                                        <span class="font-black text-slate-900">{{ $key }}.</span>
                                                        <span class="ml-1">{{ $option }}</span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @elseif (in_array($question->question_type, ['short_text', 'math_notation']))
                                        <label
                                            class="mx-auto block max-w-2xl"
                                            @if ($question->question_type === 'math_notation')
                                                x-data="{ mathAnswer: @js($savedValue['answer'] ?? '') }"
                                            @endif>

                                            <span class="text-sm font-black text-slate-700">
                                                Jawaban
                                            </span>

                                            <input
                                                type="text"
                                                name="responses[{{ $question->id }}][answer]"
                                                value="{{ $savedValue['answer'] ?? '' }}"
                                                autocomplete="off"
                                                data-answer-input
                                                @if ($question->question_type === 'math_notation')
                                                    x-model="mathAnswer"
                                                    @input.debounce.300ms="$nextTick(() => window.renderMathJax?.($root))"
                                                @endif
                                                class="mt-2 w-full rounded-2xl border-slate-300 bg-white px-4 py-3 text-center text-lg font-bold text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                                                placeholder="Ketik jawaban Anda di sini">

                                            @if ($question->question_type === 'math_notation')
                                                <div
                                                    class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-center text-lg font-semibold text-slate-900"
                                                    x-text="'\\(' + (mathAnswer.trim() || '\\text{Pratinjau notasi}') + '\\)'">
                                                </div>
                                            @endif
                                        </label>
                                    @elseif ($question->question_type === 'canvas_final_answer')
                                        <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_260px]">
                                            <div class="rounded-2xl border border-slate-300 bg-slate-50 p-4">
                                                <p class="text-sm font-black text-slate-700">
                                                    Upload file langkah pengerjaan
                                                </p>

                                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                                    Unggah file berisi langkah penyelesaian. Format: PDF, JPG, JPEG, atau PNG. Maksimal 5 MB.
                                                </p>

                                                <input
                                                    type="file"
                                                    name="responses[{{ $question->id }}][step_file]"
                                                    accept=".pdf,.jpg,.jpeg,.png"
                                                    class="mt-3 w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-xl file:border-0 file:bg-cyan-100 file:px-3 file:py-2 file:text-xs file:font-black file:text-cyan-800 hover:file:bg-cyan-200">
                                            </div>

                                            <div class="min-w-0 rounded-2xl border border-slate-300 bg-slate-50 p-4">
                                                <p class="text-sm font-black text-slate-700">
                                                    Jawaban akhir
                                                </p>

                                                <div class="mt-3 grid gap-2">
                                                    @foreach (($data['final_fields'] ?? ['x', 'y', 'z']) as $field)
                                                        <label class="grid min-w-0 grid-cols-[32px_minmax(0,1fr)] items-center gap-2">
                                                            <span class="text-base font-black text-slate-900">
                                                                {{ $field }} =
                                                            </span>

                                                            <input
                                                                type="text"
                                                                name="responses[{{ $question->id }}][final][{{ $field }}]"
                                                                value="{{ $savedValue['final'][$field] ?? '' }}"
                                                                autocomplete="off"
                                                                data-final-answer-input
                                                                class="h-10 min-w-0 w-full rounded-2xl border-slate-300 bg-white px-3 text-center font-bold text-slate-900 focus:border-cyan-500 focus:ring-cyan-500">
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @elseif ($question->question_type === 'matrix')
                                        @php
                                            $rows = $data['rows'] ?? 3;
                                            $columns = $data['columns'] ?? 3;
                                        @endphp

                                        <div class="overflow-x-auto pb-2">
                                            <div class="mx-auto w-max rounded-2xl border border-slate-300 bg-slate-50 p-4">
                                                <p class="mb-3 text-center text-sm font-black text-slate-700">
                                                    Matriks {{ $data['label'] ?? 'A' }}
                                                </p>

                                                <div class="grid gap-2" style="grid-template-columns: repeat({{ $columns }}, 64px);">
                                                    @for ($r = 0; $r < $rows; $r++)
                                                        @for ($c = 0; $c < $columns; $c++)
                                                            <input
                                                                type="text"
                                                                name="responses[{{ $question->id }}][matrix][{{ $r }}][{{ $c }}]"
                                                                value="{{ $savedValue['matrix'][$r][$c] ?? '' }}"
                                                                autocomplete="off"
                                                                data-matrix-input
                                                                class="h-11 rounded-xl border-slate-300 text-center font-bold text-slate-900 focus:border-cyan-500 focus:ring-cyan-500">
                                                        @endfor
                                                    @endfor
                                                </div>
                                            </div>
                                        </div>
                                    @elseif ($question->question_type === 'matrix_equation')
                                        @php
                                            $rows = $data['matrix_rows'] ?? 3;
                                            $columns = $data['matrix_columns'] ?? 3;
                                        @endphp

                                        <div class="overflow-x-auto pb-2">
                                            <div class="mx-auto grid w-max items-center gap-3" style="grid-template-columns: auto 28px auto 28px auto;">
                                                <div class="rounded-2xl border border-slate-300 bg-slate-50 p-3">
                                                    <p class="mb-2 text-center text-sm font-black text-slate-700">
                                                        A
                                                    </p>

                                                    <div class="grid gap-2" style="grid-template-columns: repeat({{ $columns }}, 56px);">
                                                        @for ($r = 0; $r < $rows; $r++)
                                                            @for ($c = 0; $c < $columns; $c++)
                                                                <input
                                                                    type="text"
                                                                    name="responses[{{ $question->id }}][A][{{ $r }}][{{ $c }}]"
                                                                    value="{{ $savedValue['A'][$r][$c] ?? '' }}"
                                                                    autocomplete="off"
                                                                    data-matrix-input
                                                                    class="h-10 rounded-xl border-slate-300 text-center font-bold text-slate-900 focus:border-cyan-500 focus:ring-cyan-500">
                                                            @endfor
                                                        @endfor
                                                    </div>
                                                </div>

                                                <div class="text-center text-2xl font-black text-slate-900">
                                                    ×
                                                </div>

                                                <div class="rounded-2xl border border-slate-300 bg-slate-50 p-3">
                                                    <p class="mb-2 text-center text-sm font-black text-slate-700">
                                                        x
                                                    </p>

                                                    <div class="grid gap-2">
                                                        @foreach (($data['vector_x'] ?? ['x', 'y', 'z']) as $variable)
                                                            <div class="flex h-10 w-14 items-center justify-center rounded-xl border border-slate-300 bg-white font-black text-slate-900">
                                                                {{ $variable }}
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                <div class="text-center text-2xl font-black text-slate-900">
                                                    =
                                                </div>

                                                <div class="rounded-2xl border border-slate-300 bg-slate-50 p-3">
                                                    <p class="mb-2 text-center text-sm font-black text-slate-700">
                                                        b
                                                    </p>

                                                    <div class="grid gap-2">
                                                        @for ($r = 0; $r < ($data['vector_b_rows'] ?? 3); $r++)
                                                            <input
                                                                type="text"
                                                                name="responses[{{ $question->id }}][b][{{ $r }}]"
                                                                value="{{ $savedValue['b'][$r] ?? '' }}"
                                                                autocomplete="off"
                                                                data-matrix-input
                                                                class="h-10 w-14 rounded-xl border-slate-300 text-center font-bold text-slate-900 focus:border-cyan-500 focus:ring-cyan-500">
                                                        @endfor
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @elseif ($question->question_type === 'augmented_matrix')
                                        @php
                                            $rows = $data['rows'] ?? 3;
                                            $columns = $data['columns'] ?? 4;
                                            $separatorBefore = $data['separator_before_column'] ?? 4;
                                        @endphp

                                        <div class="overflow-x-auto pb-2">
                                            <div class="mx-auto w-max rounded-2xl border border-slate-300 bg-slate-50 p-4">
                                                <p class="mb-3 text-center text-sm font-black text-slate-700">
                                                    Augmented Matrix
                                                </p>

                                                <div class="grid gap-2" style="grid-template-columns: repeat({{ $columns }}, 64px);">
                                                    @for ($r = 0; $r < $rows; $r++)
                                                        @for ($c = 0; $c < $columns; $c++)
                                                            <input
                                                                type="text"
                                                                name="responses[{{ $question->id }}][matrix][{{ $r }}][{{ $c }}]"
                                                                value="{{ $savedValue['matrix'][$r][$c] ?? '' }}"
                                                                autocomplete="off"
                                                                data-matrix-input
                                                                class="h-11 rounded-xl border-slate-300 text-center font-bold text-slate-900 focus:border-cyan-500 focus:ring-cyan-500 {{ ($c + 1) === $separatorBefore ? 'border-l-4 border-l-slate-700' : '' }}">
                                                        @endfor
                                                    @endfor
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="shrink-0 border-t border-slate-200 px-4 py-3 sm:px-5">
                                    <div class="grid grid-cols-3 items-center gap-2 sm:gap-3">
                                        <button
                                            type="button"
                                            @click="saveProgress(); current = Math.max(0, current - 1)"
                                            class="justify-self-start rounded-2xl border border-slate-300 bg-white px-3 py-2.5 text-xs font-black text-slate-700 hover:bg-slate-50 sm:px-5 sm:text-sm">
                                            Sebelumnya
                                        </button>

                                        <label class="inline-flex cursor-pointer items-center justify-self-center gap-2 rounded-2xl border border-yellow-300 bg-yellow-50 px-3 py-2.5 text-xs font-black text-yellow-700 hover:bg-yellow-100 sm:px-4 sm:text-sm">
                                            <input
                                                type="hidden"
                                                name="responses[{{ $question->id }}][is_marked_doubtful]"
                                                value="0">

                                            <input
                                                type="checkbox"
                                                name="responses[{{ $question->id }}][is_marked_doubtful]"
                                                value="1"
                                                data-doubt-input
                                                @checked($savedDoubtful)
                                                @change="refreshStatuses(); scheduleSave()"
                                                class="rounded border-yellow-300 text-yellow-500">

                                            Ragu-ragu
                                        </label>

                                        <button
                                            type="button"
                                            @click="saveProgress(); current = Math.min({{ $questions->count() - 1 }}, current + 1)"
                                            class="justify-self-end rounded-2xl bg-cyan-600 px-3 py-2.5 text-xs font-black text-white hover:bg-cyan-500 sm:px-5 sm:text-sm">
                                            Selanjutnya
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <aside class="order-1 flex min-h-0 flex-col rounded-[1.35rem] border border-white/10 bg-white/[0.06] p-3 backdrop-blur-xl lg:order-2 lg:h-full lg:p-4">
                    <div class="shrink-0">
                        <div class="rounded-2xl border border-cyan-300/20 bg-cyan-400/10 px-4 py-3 text-center lg:px-5 lg:py-4">
                            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-cyan-200">
                                Sisa Waktu
                            </p>

                            <p
                                class="mt-1 font-mono text-2xl font-black leading-none text-white lg:text-3xl"
                                x-text="timerText()">
                            </p>

                            <div class="mt-2 text-[11px] font-bold text-cyan-100/80">
                                <span x-show="!isSaving && !saveError && !lastSavedAt" x-cloak>
                                    Autosave aktif
                                </span>

                                <span x-show="saveError" x-cloak class="text-yellow-200">
                                    Autosave gagal
                                </span>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-3 lg:block">
                            <div>
                                <h2 class="text-base font-black text-white lg:text-lg">
                                    Navigasi Soal
                                </h2>

                                <p class="mt-1 hidden text-sm text-slate-400 sm:block">
                                    Klik nomor untuk berpindah soal.
                                </p>
                            </div>

                            <div class="text-right text-xs font-bold text-slate-400 lg:hidden">
                                <span x-text="answeredCount()"></span>/<span>{{ $questions->count() }}</span> terjawab
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-10 gap-1.5 sm:gap-2 lg:mt-4 lg:grid-cols-5">
                        @foreach ($questions as $index => $question)
                            <button
                                type="button"
                                @click="saveProgress(); current = {{ $index }}"
                                class="h-9 rounded-xl text-xs font-black transition sm:h-10 sm:text-sm"
                                :class="questionButtonClass('{{ $question->id }}', {{ $index }})">
                                {{ $index + 1 }}
                            </button>
                        @endforeach
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-2 lg:mt-4 lg:gap-3">
                        <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-3">
                            <p class="text-xs text-slate-400">
                                Terjawab
                            </p>

                            <p class="mt-1 text-xl font-black text-green-300 lg:text-2xl">
                                <span x-text="answeredCount()"></span>/<span>{{ $questions->count() }}</span>
                            </p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-3">
                            <p class="text-xs text-slate-400">
                                Ragu
                            </p>

                            <p
                                class="mt-1 text-xl font-black text-yellow-300 lg:text-2xl"
                                x-text="doubtfulCount()">
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 hidden rounded-2xl border border-white/10 bg-slate-950/40 p-3 text-xs leading-6 text-slate-300 lg:block">
                        <p><span class="font-black text-cyan-300">Biru</span> = sedang dibuka</p>
                        <p><span class="font-black text-green-300">Hijau</span> = sudah dijawab</p>
                        <p><span class="font-black text-yellow-300">Kuning</span> = ragu-ragu</p>
                        <p><span class="font-black text-slate-300">Abu-abu</span> = belum dijawab</p>
                    </div>

                    <div class="mt-3 lg:mt-auto lg:pt-4">
                        <button
                            type="button"
                            @click="requestSubmit()"
                            :aria-disabled="!allAnswered()"
                            class="w-full rounded-2xl px-5 py-2.5 text-sm font-black shadow-lg transition lg:py-3"
                            :class="allAnswered()
                                ? 'bg-green-400 text-slate-950 shadow-green-500/20 hover:bg-green-300'
                                : 'bg-slate-700 text-slate-300 shadow-slate-950/20 hover:bg-slate-600'">
                            Kumpulkan Kuis
                        </button>

                        <p x-show="!allAnswered()" x-cloak class="mt-2 text-xs leading-5 text-yellow-200 lg:mt-3">
                            Lengkapi semua soal terlebih dahulu untuk mengumpulkan kuis.
                        </p>
                    </div>
                </aside>
            </div>
        </form>

        <div
            x-cloak
            x-show="showSubmitModal"
            x-transition.opacity
            @click.self="closeSubmitModal()"
            @keydown.escape.window="closeSubmitModal()"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/70 px-4 backdrop-blur-sm">

            <div
                x-show="showSubmitModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="scale-95 opacity-0"
                x-transition:enter-end="scale-100 opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="scale-100 opacity-100"
                x-transition:leave-end="scale-95 opacity-0"
                role="dialog"
                aria-modal="true"
                aria-label="Konfirmasi pengumpulan kuis"
                class="w-full max-w-md rounded-[1.5rem] border border-white/10 bg-slate-900 p-6 shadow-2xl shadow-slate-950/60">

                <template x-if="submitModalMode === 'confirm'">
                    <div>
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-cyan-400/15 text-xl font-black text-cyan-200">
                                ?
                            </div>

                            <div>
                                <p class="text-lg font-black text-white">
                                    Kumpulkan kuis?
                                </p>

                                <p class="mt-2 text-sm leading-6 text-slate-300">
                                    Periksa kembali jawaban Anda. Setelah kuis dikumpulkan, jawaban tidak dapat diubah.
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-2 gap-3">
                            <button
                                type="button"
                                @click="closeSubmitModal()"
                                class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-black text-slate-200 transition hover:bg-white/10">
                                Batal
                            </button>

                            <button
                                type="button"
                                @click="confirmSubmit()"
                                :disabled="isSubmitting"
                                class="rounded-2xl bg-cyan-500 px-4 py-3 text-sm font-black text-slate-950 transition hover:bg-cyan-400 disabled:cursor-not-allowed disabled:opacity-60">

                                <span x-show="!isSubmitting">
                                    Ya, Kumpulkan
                                </span>

                                <span x-show="isSubmitting" x-cloak>
                                    Mengumpulkan...
                                </span>
                            </button>
                        </div>
                    </div>
                </template>

                <template x-if="submitModalMode === 'incomplete'">
                    <div>
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-yellow-400/15 text-xl font-black text-yellow-200">
                                !
                            </div>

                            <div>
                                <p class="text-lg font-black text-white">
                                    Jawaban belum lengkap
                                </p>

                                <p class="mt-2 text-sm leading-6 text-slate-300">
                                    Masih ada
                                    <span
                                        class="font-black text-yellow-200"
                                        x-text="totalQuestions - answeredCount()">
                                    </span>
                                    soal yang belum dijawab. Lengkapi seluruh soal sebelum mengumpulkan kuis.
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="closeSubmitModal()"
                            class="mt-6 w-full rounded-2xl bg-yellow-400 px-4 py-3 text-sm font-black text-slate-950 transition hover:bg-yellow-300">
                            Kembali Mengerjakan
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-app-layout>
