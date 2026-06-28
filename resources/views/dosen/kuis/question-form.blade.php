<x-app-layout>
    @php
        $isEditing = $formMode === 'edit';

        $data = $question->question_data ?? [];
        $answerKey = $question->answer_key ?? [];
        $acceptedAnswers = $question->accepted_answers ?? [];

        $questionType = old('question_type', $question->question_type ?: 'short_text');
        $equationsText = old('equations_text', implode("\n", $data['equations'] ?? []));
        $acceptedAnswersText = old('accepted_answers_text', implode("\n", $acceptedAnswers));

        $defaultOptions = [
            'A' => '',
            'B' => '',
            'C' => '',
            'D' => '',
        ];

        $checkboxOptions = old('checkbox_options', array_merge($defaultOptions, $data['options'] ?? []));
        $checkboxCorrect = old('checkbox_correct', $answerKey['selected'] ?? []);

        $rawVariableFields = $data['fields'] ?? ['x', 'y', 'z'];
        $legacyVariableLabels = $data['labels'] ?? [];
        $variableDefinitions = [];

        if (! is_array($rawVariableFields)) {
            $rawVariableFields = ['x', 'y', 'z'];
        }

        if (! is_array($legacyVariableLabels)) {
            $legacyVariableLabels = [];
        }

        foreach (array_values($rawVariableFields) as $index => $field) {
            if (is_array($field)) {
                $key = trim((string) ($field['key'] ?? 'v' . ($index + 1)));
                $label = trim((string) ($field['label'] ?? $key));
            } else {
                $key = trim((string) $field);
                $label = trim((string) ($legacyVariableLabels[$key] ?? $key));
            }

            if ($key === '') {
                $key = 'v' . ($index + 1);
            }

            if ($label === '') {
                $label = $key;
            }

            $variableDefinitions[] = [
                'label' => $label,
                'answer' => $answerKey[$key] ?? '',
            ];
        }

        if (empty($variableDefinitions)) {
            $variableDefinitions = [
                ['label' => 'x', 'answer' => ''],
                ['label' => 'y', 'answer' => ''],
                ['label' => 'z', 'answer' => ''],
            ];
        }

        $variableDefinitions = old('variable_definitions', $variableDefinitions);

        if (! is_array($variableDefinitions) || empty($variableDefinitions)) {
            $variableDefinitions = [
                ['label' => 'x', 'answer' => ''],
            ];
        }
    @endphp

    <div class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl space-y-6">
            <div class="flex flex-wrap items-center gap-2 text-sm text-slate-400">
                <a href="{{ route('dosen.kelas.index') }}" class="transition hover:text-cyan-200">Kelas</a>
                <span>/</span>
                <a href="{{ route('dosen.kelas.show', $quiz->classGroup) }}" class="transition hover:text-cyan-200">{{ $quiz->classGroup->name }}</a>
                <span>/</span>
                <a href="{{ route('dosen.kuis.show', $quiz) }}" class="transition hover:text-cyan-200">{{ $quiz->title }}</a>
                <span>/</span>
                <span class="text-white">{{ $isEditing ? 'Ubah Soal' : 'Tambah Soal' }}</span>
            </div>

            <section class="rounded-2xl border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                <a href="{{ route('dosen.kuis.show', $quiz) }}"
                   class="inline-flex items-center gap-2 text-sm font-bold text-cyan-200 transition hover:text-cyan-100">
                    ← Kembali ke Detail Kuis
                </a>

                <div class="mt-5">
                    <p class="text-sm font-semibold text-cyan-200">
                        {{ $isEditing ? 'Perbarui Soal' : 'Tambah Soal Baru' }}
                    </p>

                    <h1 class="mt-1 text-2xl font-black tracking-tight text-white sm:text-3xl">
                        {{ $quiz->title }}
                    </h1>

                    <p class="mt-2 text-sm leading-6 text-slate-400">
                        Buat soal isian, notasi matematika, nilai variabel, atau pilihan lebih dari satu jawaban.
                    </p>
                </div>
            </section>

            <form
                data-variable-fields="dynamic"
                method="POST"
                action="{{ $isEditing ? route('dosen.kuis.soal.update', [$quiz, $question]) : route('dosen.kuis.soal.store', $quiz) }}"
                x-data="{
                    questionType: @js($questionType),
                    maximumVariables: 8,
                    variableDefinitions: @js(array_values($variableDefinitions)),
                    variableCount: {{ min(max(count($variableDefinitions), 1), 8) }},
                    defaultLabels: ['x', 'y', 'z', 'a', 'b', 'c', 'd', 'e'],

                    nextLabel() {
                        const used = this.variableDefinitions
                            .map(item => String(item.label || '').trim().toLowerCase());

                        return this.defaultLabels.find(label => !used.includes(label.toLowerCase()))
                            || 'v' + (this.variableDefinitions.length + 1);
                    },

                    addVariable() {
                        if (this.variableDefinitions.length >= this.maximumVariables) {
                            return;
                        }

                        this.variableDefinitions.push({
                            label: this.nextLabel(),
                            answer: ''
                        });

                        this.variableCount = this.variableDefinitions.length;
                    },

                    removeVariable(index) {
                        if (this.variableDefinitions.length <= 1) {
                            return;
                        }

                        this.variableDefinitions.splice(index, 1);
                        this.variableCount = this.variableDefinitions.length;
                    },

                    synchronizeVariableCount() {
                        let count = Number.parseInt(this.variableCount, 10);

                        if (!Number.isFinite(count)) {
                            count = this.variableDefinitions.length || 1;
                        }

                        count = Math.min(this.maximumVariables, Math.max(1, count));

                        while (this.variableDefinitions.length < count) {
                            this.variableDefinitions.push({
                                label: this.nextLabel(),
                                answer: ''
                            });
                        }

                        while (this.variableDefinitions.length > count) {
                            this.variableDefinitions.pop();
                        }

                        this.variableCount = count;
                    }
                }"
                class="space-y-6 rounded-2xl border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">

                @csrf

                @if ($isEditing)
                    @method('PUT')
                @endif

                <section>
                    <p class="text-sm font-black text-white">
                        Tipe Soal <span class="text-cyan-200">*</span>
                    </p>

                    <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <label class="cursor-pointer rounded-2xl border p-4 transition"
                               :class="questionType === 'short_text' ? 'border-cyan-300/40 bg-cyan-400/10' : 'border-white/10 bg-slate-950/35 hover:border-white/20'">
                            <input type="radio" name="question_type" value="short_text" x-model="questionType" class="sr-only">
                            <span class="block text-sm font-black text-white">Isian Singkat</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-400">Jawaban berupa teks atau nilai pendek.</span>
                        </label>

                        <label class="cursor-pointer rounded-2xl border p-4 transition"
                               :class="questionType === 'math_notation' ? 'border-cyan-300/40 bg-cyan-400/10' : 'border-white/10 bg-slate-950/35 hover:border-white/20'">
                            <input type="radio" name="question_type" value="math_notation" x-model="questionType" class="sr-only">
                            <span class="block text-sm font-black text-white">Notasi Matematika</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-400">Jawaban memakai notasi atau bentuk aljabar.</span>
                        </label>

                        <label class="cursor-pointer rounded-2xl border p-4 transition"
                               :class="questionType === 'variable_values' ? 'border-cyan-300/40 bg-cyan-400/10' : 'border-white/10 bg-slate-950/35 hover:border-white/20'">
                            <input type="radio" name="question_type" value="variable_values" x-model="questionType" class="sr-only">
                            <span class="block text-sm font-black text-white">Nilai Variabel</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-400">Tentukan jumlah serta nama variabel sesuai kebutuhan soal.</span>
                        </label>

                        <label class="cursor-pointer rounded-2xl border p-4 transition"
                               :class="questionType === 'checkbox' ? 'border-violet-300/40 bg-violet-400/10' : 'border-white/10 bg-slate-950/35 hover:border-white/20'">
                            <input type="radio" name="question_type" value="checkbox" x-model="questionType" class="sr-only">
                            <span class="block text-sm font-black text-white">Pilihan Lebih dari Satu</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-400">Mahasiswa dapat menandai lebih dari satu jawaban.</span>
                        </label>
                    </div>

                    @error('question_type')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </section>

                <section class="grid gap-5 md:grid-cols-[minmax(0,1fr)_180px]">
                    <div>
                        <label for="question_text" class="text-sm font-black text-white">
                            Pertanyaan <span class="text-cyan-200">*</span>
                        </label>

                        <textarea id="question_text" name="question_text" rows="5" maxlength="4000" required
                                  placeholder="Tuliskan pertanyaan untuk mahasiswa."
                                  class="mt-3 w-full resize-y rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm leading-6 text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">{{ old('question_text', $question->question_text) }}</textarea>

                        @error('question_text')
                            <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="points" class="text-sm font-black text-white">
                            Poin <span class="text-cyan-200">*</span>
                        </label>

                        <input id="points" name="points" type="number" min="1" max="100" required
                               value="{{ old('points', $question->points ?: 5) }}"
                               class="mt-3 w-full rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm font-black text-white outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">

                        @error('points')
                            <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                        @enderror

                        <label class="mt-5 flex cursor-pointer items-center gap-3 rounded-xl border border-white/10 bg-slate-950/35 px-3 py-3">
                            <input type="hidden" name="is_required" value="0">
                            <input type="checkbox" name="is_required" value="1"
                                   @checked(old('is_required', $question->is_required ?? true))
                                   class="rounded border-slate-500 bg-slate-900 text-cyan-400 focus:ring-cyan-400">
                            <span class="text-xs font-bold text-slate-300">Wajib dijawab</span>
                        </label>
                    </div>
                </section>

                <section>
                    <label for="equations_text" class="text-sm font-black text-white">
                        Baris Persamaan atau Informasi Matematis
                        <span class="font-medium text-slate-500">(opsional)</span>
                    </label>

                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        Tulis satu persamaan pada setiap baris. Bagian ini akan tampil terpisah di bawah pertanyaan.
                    </p>

                    <textarea id="equations_text" name="equations_text" rows="4" maxlength="3000"
                              placeholder="Contoh:&#10;x + y = 5&#10;2x - y = 4"
                              class="mt-3 w-full resize-y rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 font-mono text-sm leading-6 text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">{{ $equationsText }}</textarea>

                    @error('equations_text')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </section>

                <section x-show="questionType === 'short_text' || questionType === 'math_notation'" x-transition>
                    <label for="accepted_answers_text" class="text-sm font-black text-white">
                        Jawaban yang Diterima <span class="text-cyan-200">*</span>
                    </label>

                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        Tulis satu variasi jawaban per baris. Contoh: <span class="font-mono">x=3</span> dan <span class="font-mono">x = 3</span>.
                    </p>

                    <textarea id="accepted_answers_text" name="accepted_answers_text" rows="5" maxlength="3000"
                              placeholder="Contoh:&#10;x=3&#10;x = 3"
                              class="mt-3 w-full resize-y rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 font-mono text-sm leading-6 text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">{{ $acceptedAnswersText }}</textarea>

                    @error('accepted_answers_text')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </section>

                <section x-show="questionType === 'variable_values'" x-transition>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-sm font-black text-white">
                                Variabel dan Jawaban yang Diterima <span class="text-cyan-200">*</span>
                            </p>

                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Tentukan jumlah variabel, nama setiap variabel, dan nilai jawabannya. Mahasiswa hanya melihat variabel yang Anda buat.
                            </p>
                        </div>

                        <div class="w-full sm:w-40">
                            <label for="variable_count" class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                Jumlah variabel
                            </label>

                            <input id="variable_count"
                                   type="number"
                                   min="1"
                                   max="8"
                                   x-model.number="variableCount"
                                   @change="synchronizeVariableCount()"
                                   @input.debounce.300ms="synchronizeVariableCount()"
                                   class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-center text-sm font-black text-white outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                        </div>
                    </div>

                    @error('variable_definitions')
                        <p class="mt-3 text-sm text-red-300">{{ $message }}</p>
                    @enderror

                    <div class="mt-4 space-y-3">
                        <template x-for="(variable, index) in variableDefinitions" :key="index">
                            <div class="grid gap-3 rounded-2xl border border-white/10 bg-slate-950/35 p-4 sm:grid-cols-[46px_minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-400/10 text-sm font-black text-cyan-100" x-text="index + 1"></span>

                                <label class="block">
                                    <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Nama Variabel</span>

                                    <input type="text"
                                           :name="'variable_definitions[' + index + '][label]'"
                                           x-model="variable.label"
                                           maxlength="40"
                                           placeholder="Contoh: x atau harga"
                                           autocomplete="off"
                                           class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-sm font-black text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                                </label>

                                <label class="block">
                                    <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Jawaban Diterima</span>

                                    <input type="text"
                                           :name="'variable_definitions[' + index + '][answer]'"
                                           x-model="variable.answer"
                                           placeholder="Contoh: -1/2"
                                           autocomplete="off"
                                           class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-center font-mono text-sm font-black text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                                </label>

                                <button type="button"
                                        @click="removeVariable(index)"
                                        :disabled="variableDefinitions.length <= 1"
                                        class="h-10 rounded-xl border border-red-300/20 bg-red-400/10 px-3 text-xs font-black text-red-200 transition hover:bg-red-400/20 disabled:cursor-not-allowed disabled:opacity-40">
                                    Hapus
                                </button>
                            </div>
                        </template>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <button type="button"
                                @click="addVariable()"
                                :disabled="variableDefinitions.length >= maximumVariables"
                                class="rounded-xl border border-cyan-300/20 bg-cyan-400/10 px-3.5 py-2 text-xs font-black text-cyan-100 transition hover:bg-cyan-400/20 disabled:cursor-not-allowed disabled:opacity-40">
                            + Tambah Variabel
                        </button>

                        <p class="text-xs text-slate-500">
                            Maksimal 8 variabel. Nama dapat berupa <span class="font-mono">x_1</span>, <span class="font-mono">a</span>, <span class="font-mono">harga</span>, atau nama lain yang mudah dibaca.
                        </p>
                    </div>

                    <p class="mt-4 text-xs leading-5 text-slate-500">
                        Sistem menerima nilai bilangan atau pecahan, misalnya <span class="font-mono">-1/2</span> atau <span class="font-mono">-1 per 2</span>.
                    </p>
                </section>
                <section x-show="questionType === 'checkbox'" x-transition>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-sm font-black text-white">
                                Pilihan Jawaban <span class="text-cyan-200">*</span>
                            </p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Isi pilihan yang tersedia dan centang jawaban yang benar.
                            </p>
                        </div>

                        @error('checkbox_correct')
                            <p class="text-sm text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-3 space-y-3">
                        @foreach (['A', 'B', 'C', 'D'] as $key)
                            <label class="grid gap-3 rounded-xl border border-white/10 bg-slate-950/35 p-3 sm:grid-cols-[auto_minmax(0,1fr)] sm:items-center">
                                <span class="flex items-center gap-2">
                                    <input type="checkbox"
                                           name="checkbox_correct[]"
                                           value="{{ $key }}"
                                           @checked(in_array($key, $checkboxCorrect, true))
                                           class="rounded border-slate-500 bg-slate-900 text-cyan-400 focus:ring-cyan-400">

                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-white/10 text-xs font-black text-white">
                                        {{ $key }}
                                    </span>
                                </span>

                                <input type="text"
                                       name="checkbox_options[{{ $key }}]"
                                       value="{{ $checkboxOptions[$key] ?? '' }}"
                                       placeholder="Tulis pilihan {{ $key }}"
                                       class="w-full rounded-lg border border-white/10 bg-slate-950/60 px-3 py-2.5 text-sm text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                            </label>
                        @endforeach
                    </div>

                    @error('checkbox_options')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </section>

                <section>
                    <label for="explanation" class="text-sm font-black text-white">
                        Penjelasan Umpan Balik
                        <span class="font-medium text-slate-500">(opsional)</span>
                    </label>

                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        Penjelasan ini digunakan sebagai umpan balik saat jawaban mahasiswa belum tepat.
                    </p>

                    <textarea id="explanation" name="explanation" rows="4" maxlength="2000"
                              placeholder="Contoh: Periksa kembali koefisien dan operasi hitung pada setiap persamaan."
                              class="mt-3 w-full resize-y rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm leading-6 text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">{{ old('explanation', $question->explanation) }}</textarea>

                    @error('explanation')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </section>

                <div class="rounded-2xl border border-cyan-300/15 bg-cyan-400/[0.06] p-4 text-sm leading-6 text-slate-300">
                    Soal baru akan memperoleh nomor terakhir secara otomatis. Setelah kuis memiliki percobaan mahasiswa, isi soal akan dikunci agar nilai dan riwayat tetap konsisten.
                </div>

                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                    <a href="{{ route('dosen.kuis.show', $quiz) }}"
                       class="inline-flex justify-center rounded-xl border border-white/10 bg-white/[0.04] px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10">
                        Batal
                    </a>

                    <button type="submit"
                            class="inline-flex justify-center rounded-xl bg-cyan-400 px-5 py-3 text-sm font-black text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-300">
                        {{ $isEditing ? 'Simpan Perubahan' : 'Tambahkan Soal' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>