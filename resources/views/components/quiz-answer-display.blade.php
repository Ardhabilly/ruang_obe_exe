@props(['question', 'response'])

@php
    $type = (string) ($question?->question_type ?? '');
    $data = is_array($question?->question_data ?? null) ? $question->question_data : [];
    $payload = is_array($response?->response_value ?? null) ? $response->response_value : [];
    $separatorBefore = (int) ($data['separator_before_column'] ?? 0);

    $canvasData = is_string($response?->canvas_data ?? null)
        ? trim((string) $response->canvas_data)
        : '';

    $showWorkspace = $canvasData !== ''
        || (bool) ($data['canvas_required'] ?? false)
        || $type === 'canvas_final_answer';

    $fieldList = static function ($rawFields, $labels, array $fallback = []): array {
        $rawFields = is_array($rawFields) ? array_values($rawFields) : [];
        $labels = is_array($labels) ? $labels : [];

        if (empty($rawFields)) {
            $rawFields = $fallback;
        }

        $fields = [];

        foreach ($rawFields as $index => $field) {
            if (is_array($field)) {
                $key = trim((string) ($field['key'] ?? $field['name'] ?? ''));
                $label = trim((string) ($field['label'] ?? $labels[$key] ?? $key));
            } else {
                $key = trim((string) $field);
                $label = trim((string) ($labels[$key] ?? $key));
            }

            if ($key === '') {
                $key = 'nilai_' . ($index + 1);
            }

            if ($label === '') {
                $label = $key;
            }

            $fields[] = compact('key', 'label');
        }

        return $fields;
    };

    $workspaceLatex = $canvasData;

    if (
        $workspaceLatex !== ''
        && ! \Illuminate\Support\Str::contains($workspaceLatex, [
            '\begin{array}',
            '\begin{aligned}',
            '\begin{gathered}',
            '\displaylines{',
        ])
        && \Illuminate\Support\Str::contains($workspaceLatex, '\\\\')
    ) {
        $workspaceLatex = '\displaylines{' . $workspaceLatex . '}';
    }
@endphp

<div class="mt-5 space-y-4">
    <section class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-700">Jawaban Mahasiswa</p>

        @if ($type === 'checkbox')
            @php
                $options = is_array($data['options'] ?? null) ? $data['options'] : [];
                $selected = collect($payload['selected'] ?? [])
                    ->map(fn ($key) => trim((string) ($options[$key] ?? '')) !== '' ? $key . '. ' . $options[$key] : (string) $key)
                    ->filter()
                    ->values();
            @endphp
            <div class="mt-3 flex flex-wrap gap-2">
                @forelse ($selected as $option)
                    <span class="rounded-xl border border-cyan-300 bg-cyan-50 px-3 py-2 text-sm font-semibold text-cyan-800">{{ $option }}</span>
                @empty
                    <p class="text-sm text-slate-500">Tidak ada pilihan yang dipilih.</p>
                @endforelse
            </div>

        @elseif (in_array($type, ['short_text', 'math_notation'], true))
            @php $answer = trim((string) ($payload['answer'] ?? '')); @endphp
            @if ($type === 'math_notation' && $answer !== '')
                <div class="mt-3 overflow-x-auto rounded-2xl border border-slate-300 bg-white p-4">
                    <math-field read-only virtual-keyboard-mode="off" class="block border-0 bg-transparent text-lg text-slate-900 shadow-none outline-none">{{ $answer }}</math-field>
                </div>
            @else
                <div class="mt-3 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold leading-6 text-slate-900">
                    {{ $answer !== '' ? $answer : 'Tidak diisi.' }}
                </div>
            @endif

        @elseif ($type === 'variable_values')
            @php
                $answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];
                $fields = $fieldList($data['fields'] ?? [], $data['labels'] ?? [], ['x', 'y', 'z']);
            @endphp
            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($fields as $field)
                    <div class="rounded-xl border border-slate-300 bg-white px-4 py-3">
                        <p class="text-xs font-bold text-slate-600">{{ $field['label'] }}</p>
                        <p class="mt-1 text-lg font-black text-slate-900">{{ trim((string) ($answers[$field['key']] ?? '')) ?: '–' }}</p>
                    </div>
                @endforeach
            </div>

        @elseif ($type === 'multi_short_text')
            @php
                $answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];
                $fields = $fieldList($data['fields'] ?? [], $data['labels'] ?? [], array_keys($answers));
                $sourceMatrix = is_array($data['matrix'] ?? null) ? $data['matrix'] : [];
            @endphp
            <div class="mt-3 space-y-4">
                @if (! empty($sourceMatrix))
                    <x-quiz-answer-matrix :matrix="$sourceMatrix" label="Matriks pada Soal" :separator-before="$separatorBefore" />
                @endif
                @forelse ($fields as $field)
                    <div class="rounded-xl border border-slate-300 bg-white px-4 py-3">
                        <p class="text-xs font-bold text-slate-600">{{ $field['label'] }}</p>
                        <p class="mt-1 whitespace-pre-wrap break-words text-sm font-semibold leading-6 text-slate-900">{{ trim((string) ($answers[$field['key']] ?? '')) ?: 'Tidak diisi.' }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Jawaban belum diisi.</p>
                @endforelse
            </div>

        @elseif ($type === 'obe_matrix_operation')
            @php
                $initial = is_array($data['initial_matrix'] ?? null) ? $data['initial_matrix'] : [];
                $operation = trim((string) ($payload['operation'] ?? ''));
            @endphp
            <div class="mt-3 space-y-4">
                @if (! empty($initial))
                    <x-quiz-answer-matrix :matrix="$initial" :label="$separatorBefore > 0 ? 'Matriks Teraugmentasi Awal' : 'Matriks Awal'" :separator-before="$separatorBefore" />
                @endif
                <div class="rounded-2xl border border-slate-300 bg-white px-4 py-3">
                    <p class="text-xs font-bold text-slate-600">Notasi Operasi</p>
                    <p class="mt-1 text-lg font-black text-slate-900">{{ $operation !== '' ? $operation : 'Tidak diisi.' }}</p>
                </div>
                <x-quiz-answer-matrix
                    :matrix="$payload['result_matrix'] ?? []"
                    label="Matriks Hasil Operasi"
                    :separator-before="$separatorBefore"
                    empty-message="Matriks hasil operasi belum diisi."
                />
            </div>

        @elseif (in_array($type, ['gauss_elimination', 'gauss_jordan'], true))
            @php
                $initial = is_array($question?->answer_key['initial_matrix'] ?? null)
                    ? $question->answer_key['initial_matrix']
                    : (is_array($data['initial_matrix'] ?? null) ? $data['initial_matrix'] : []);

                $matrixKey = $type === 'gauss_jordan' ? 'reduced_matrix' : 'echelon_matrix';
                $matrixLabel = $type === 'gauss_jordan' ? 'Matriks Eselon Baris Tereduksi' : 'Matriks Eselon Baris';

                $final = is_array($payload['final'] ?? null) ? $payload['final'] : [];
                $fields = $fieldList($data['final_fields'] ?? [], $data['final_labels'] ?? [], array_keys($final));
            @endphp
            <div class="mt-3 space-y-4">
                @if (! empty($initial))
                    <x-quiz-answer-matrix :matrix="$initial" :label="$separatorBefore > 0 ? 'Matriks Teraugmentasi Awal' : 'Matriks Awal'" :separator-before="$separatorBefore" />
                @endif
                <x-quiz-answer-matrix :matrix="$payload[$matrixKey] ?? []" :label="$matrixLabel" :separator-before="$separatorBefore" :empty-message="$matrixLabel . ' belum diisi.'" />
                <div>
                    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-700">Jawaban Akhir</p>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @forelse ($fields as $field)
                            <div class="rounded-xl border border-slate-300 bg-white px-4 py-3">
                                <p class="text-xs font-bold text-slate-600">{{ $field['label'] }}</p>
                                <p class="mt-1 text-lg font-black text-slate-900">{{ trim((string) ($final[$field['key']] ?? '')) ?: '–' }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Jawaban akhir belum diisi.</p>
                        @endforelse
                    </div>
                </div>
            </div>

        @elseif ($type === 'canvas_final_answer')
            @php
                $final = is_array($payload['final'] ?? null) ? $payload['final'] : [];
                $fields = $fieldList($data['final_fields'] ?? [], $data['final_labels'] ?? [], array_keys($final));
            @endphp
            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($fields as $field)
                    <div class="rounded-xl border border-slate-300 bg-white px-4 py-3">
                        <p class="text-xs font-bold text-slate-600">{{ $field['label'] }}</p>
                        <p class="mt-1 text-lg font-black text-slate-900">{{ trim((string) ($final[$field['key']] ?? '')) ?: '–' }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Jawaban akhir belum diisi.</p>
                @endforelse
            </div>

        @elseif (in_array($type, ['matrix', 'augmented_matrix'], true))
            <div class="mt-3">
                <x-quiz-answer-matrix
                    :matrix="$payload['matrix'] ?? []"
                    :label="$type === 'augmented_matrix' ? 'Matriks Teraugmentasi' : 'Matriks Jawaban'"
                    :separator-before="$type === 'augmented_matrix' ? ($separatorBefore ?: null) : null"
                    :empty-message="$type === 'augmented_matrix' ? 'Matriks teraugmentasi belum diisi.' : 'Matriks jawaban belum diisi.'"
                />
            </div>

        @elseif ($type === 'matrix_equation')
            @php
                $vector = is_array($payload['b'] ?? null)
                    ? collect($payload['b'])->map(fn ($value) => [$value])->all()
                    : [];
            @endphp
            <div class="mt-3 grid gap-4 xl:grid-cols-[minmax(0,1fr)_220px]">
                <x-quiz-answer-matrix :matrix="$payload['A'] ?? []" label="Matriks Koefisien A" empty-message="Matriks koefisien A belum diisi." />
                <x-quiz-answer-matrix :matrix="$vector" label="Vektor b" empty-message="Vektor b belum diisi." />
            </div>

        @else
            @php
                $answers = collect($payload)
                    ->except(['is_marked_doubtful', 'canvas_data', 'step_file'])
                    ->filter(fn ($value) => is_array($value) ? ! empty($value) : trim((string) $value) !== '');
            @endphp
            <div class="mt-3 space-y-3">
                @forelse ($answers as $key => $value)
                    <div class="rounded-xl border border-slate-300 bg-white px-4 py-3">
                        <p class="text-xs font-bold text-slate-600">{{ ucwords(str_replace(['_', '-'], ' ', (string) $key)) }}</p>
                        <p class="mt-1 whitespace-pre-wrap break-words text-sm font-semibold leading-6 text-slate-900">
                            {{ is_array($value) ? collect(\Illuminate\Support\Arr::flatten($value))->filter()->implode(' · ') : $value }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Jawaban belum diisi.</p>
                @endforelse
            </div>
        @endif
    </section>

    @if ($showWorkspace)
        <section class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-700">Langkah Pengerjaan</p>

            @if ($canvasData !== '' && \Illuminate\Support\Str::startsWith($canvasData, 'quiz-step-files/'))
                <a href="{{ asset('storage/' . $canvasData) }}" target="_blank" class="mt-3 inline-flex rounded-xl border border-cyan-300 bg-cyan-50 px-4 py-3 text-sm font-black text-cyan-800">
                    Buka Lampiran Langkah Pengerjaan
                </a>
            @elseif ($workspaceLatex !== '')
                <div
                    x-data="{ workspaceLatex: @js($workspaceLatex) }"
                    x-init="
                        const renderWorkspace = () => {
                            const mathField = $refs.workspaceViewer;
                            if (! mathField) return;
                            mathField.value = workspaceLatex;
                            mathField.readOnly = true;
                        };
                        if (customElements.get('math-field')) {
                            $nextTick(renderWorkspace);
                        } else {
                            customElements.whenDefined('math-field').then(() => $nextTick(renderWorkspace));
                        }
                    "
                    class="mt-3 overflow-x-auto rounded-2xl border border-slate-300 bg-white p-4"
                >
                    <math-field x-ref="workspaceViewer" read-only virtual-keyboard-mode="off" class="block min-h-[160px] border-0 bg-transparent text-lg text-slate-900 shadow-none outline-none"></math-field>
                </div>
            @else
                <div class="mt-3 rounded-xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">
                    Mahasiswa tidak menuliskan langkah pengerjaan.
                </div>
            @endif
        </section>
    @endif

    @if ($response?->feedback)
        <section class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-700">Umpan Balik Sistem</p>
            <p class="mt-2 text-sm leading-6 {{ $response->is_correct ? 'text-green-700' : 'text-red-700' }}">{{ $response->feedback }}</p>
        </section>
    @endif
</div>
