@props([
    'question',
    'response',
    'mode' => 'dosen',
])

@php
    $isMahasiswa = $mode === 'mahasiswa';
    $questionType = (string) ($question?->question_type ?? '');
    $data = is_array($question?->question_data ?? null) ? $question->question_data : [];
    $payload = is_array($response?->response_value ?? null) ? $response->response_value : [];
    $canvasData = is_string($response?->canvas_data ?? null) ? trim((string) $response->canvas_data) : '';

    $titleClass = $isMahasiswa ? 'text-slate-700' : 'text-slate-400';
    $cardClass = $isMahasiswa ? 'border-slate-200 bg-slate-50' : 'border-white/10 bg-white/[0.035]';
    $valueClass = $isMahasiswa ? 'border-slate-300 bg-white text-slate-900' : 'border-white/10 bg-white/5 text-white';
    $emptyClass = $isMahasiswa ? 'border-slate-300 text-slate-500' : 'border-white/10 text-slate-400';
    $accentClass = $isMahasiswa ? 'border-cyan-300/60 bg-cyan-50 text-cyan-800' : 'border-cyan-300/20 bg-cyan-400/10 text-cyan-100';

    $separatorBefore = (int) ($data['separator_before_column'] ?? 0);

    $fieldList = static function ($fields, $labels = [], array $fallback = []): array {
        $fields = is_array($fields) ? array_values($fields) : [];
        $labels = is_array($labels) ? $labels : [];

        if (empty($fields)) {
            $fields = $fallback;
        }

        $result = [];
        $used = [];

        foreach ($fields as $index => $field) {
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

            if (isset($used[$key])) {
                continue;
            }

            $used[$key] = true;
            $result[] = compact('key', 'label');
        }

        return $result;
    };

    $matrixBlocks = [];
    $addMatrix = static function (array &$blocks, string $label, $matrix, int $separator = 0): void {
        $matrix = is_array($matrix) ? $matrix : [];
        $rows = collect($matrix)
            ->map(fn ($row) => is_array($row) ? array_values($row) : [$row])
            ->values()
            ->all();

        $columns = max(1, (int) collect($rows)->map(fn ($row) => count($row))->max());
        $hasValue = ! empty($rows) && collect($rows)->flatten()->contains(
            fn ($value) => trim((string) $value) !== ''
        );

        $blocks[] = compact('label', 'rows', 'columns', 'separator', 'hasValue');
    };

    $answerLines = [];
    $addLine = static function (array &$lines, string $label, $value, bool $math = false): void {
        if (is_array($value)) {
            $value = collect(\Illuminate\Support\Arr::flatten($value))
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->implode(' · ');
        }

        $lines[] = [
            'label' => $label,
            'value' => is_bool($value) ? ($value ? 'Ya' : 'Tidak') : trim((string) $value),
            'math' => $math,
        ];
    };

    $finalValues = is_array($payload['final'] ?? null) ? $payload['final'] : [];
    $finalFields = $fieldList($data['final_fields'] ?? [], $data['final_labels'] ?? [], array_keys($finalValues));

    if ($questionType === 'checkbox') {
        $options = is_array($data['options'] ?? null) ? $data['options'] : [];
        $selected = collect($payload['selected'] ?? [])
            ->map(fn ($key) => trim((string) ($options[$key] ?? '')) !== '' ? $key . '. ' . $options[$key] : (string) $key)
            ->filter()
            ->implode(' · ');
        $addLine($answerLines, 'Pilihan yang dipilih', $selected);
    } elseif (in_array($questionType, ['short_text', 'math_notation'], true)) {
        $addLine($answerLines, 'Jawaban', $payload['answer'] ?? '', $questionType === 'math_notation');
    } elseif ($questionType === 'variable_values') {
        $answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];
        foreach ($fieldList($data['fields'] ?? [], $data['labels'] ?? [], ['x', 'y', 'z']) as $field) {
            $addLine($answerLines, $field['label'], $answers[$field['key']] ?? '');
        }
    } elseif ($questionType === 'multi_short_text') {
        $sourceMatrix = $data['matrix'] ?? [];
        if (! empty($sourceMatrix)) {
            $addMatrix($matrixBlocks, 'Matriks pada soal', $sourceMatrix, $separatorBefore);
        }

        $answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];
        foreach ($fieldList($data['fields'] ?? [], $data['labels'] ?? [], array_keys($answers)) as $field) {
            $addLine($answerLines, $field['label'], $answers[$field['key']] ?? '');
        }
    } elseif ($questionType === 'obe_matrix_operation') {
        $initial = $data['initial_matrix'] ?? [];
        if (! empty($initial)) {
            $addMatrix($matrixBlocks, $separatorBefore > 0 ? 'Matriks teraugmentasi awal' : 'Matriks awal', $initial, $separatorBefore);
        }

        $addLine($answerLines, 'Notasi Operasi', $payload['operation'] ?? '');
        $addMatrix($matrixBlocks, 'Matriks Hasil Operasi', $payload['result_matrix'] ?? [], $separatorBefore);
    } elseif (in_array($questionType, ['gauss_elimination', 'gauss_jordan'], true)) {
        $initial = $question?->answer_key['initial_matrix'] ?? $data['initial_matrix'] ?? [];
        if (! empty($initial)) {
            $addMatrix($matrixBlocks, $separatorBefore > 0 ? 'Matriks teraugmentasi awal' : 'Matriks awal', $initial, $separatorBefore);
        }

        $matrixKey = $questionType === 'gauss_jordan' ? 'reduced_matrix' : 'echelon_matrix';
        $matrixLabel = $questionType === 'gauss_jordan' ? 'Matriks Eselon Baris Tereduksi' : 'Matriks Eselon Baris';
        $addMatrix($matrixBlocks, $matrixLabel, $payload[$matrixKey] ?? [], $separatorBefore);

        foreach ($finalFields as $field) {
            $addLine($answerLines, 'Jawaban akhir ' . $field['label'], $finalValues[$field['key']] ?? '');
        }
    } elseif ($questionType === 'canvas_final_answer') {
        foreach ($finalFields as $field) {
            $addLine($answerLines, 'Jawaban akhir ' . $field['label'], $finalValues[$field['key']] ?? '');
        }
    } elseif (in_array($questionType, ['matrix', 'augmented_matrix'], true)) {
        $addMatrix(
            $matrixBlocks,
            $questionType === 'augmented_matrix' ? 'Matriks Teraugmentasi' : 'Matriks Jawaban',
            $payload['matrix'] ?? [],
            $questionType === 'augmented_matrix' ? $separatorBefore : 0
        );
    } elseif ($questionType === 'matrix_equation') {
        $addMatrix($matrixBlocks, 'Matriks Koefisien A', $payload['A'] ?? []);
        $vectorRows = collect(is_array($payload['b'] ?? null) ? $payload['b'] : [])
            ->map(fn ($value) => [$value])
            ->all();
        $addMatrix($matrixBlocks, 'Vektor b', $vectorRows);
    } else {
        $labelMap = [
            'operation' => 'Notasi operasi',
            'result_matrix' => 'Matriks hasil operasi',
            'echelon_matrix' => 'Matriks eselon baris',
            'reduced_matrix' => 'Matriks eselon baris tereduksi',
            'answer' => 'Jawaban',
            'answers' => 'Jawaban',
            'final' => 'Jawaban akhir',
            'selected' => 'Pilihan jawaban',
            'matrix' => 'Matriks jawaban',
            'A' => 'Matriks A',
            'b' => 'Vektor b',
        ];

        foreach ($payload as $key => $value) {
            if (in_array($key, ['is_marked_doubtful', 'canvas_data', 'step_file'], true)) {
                continue;
            }

            $addLine($answerLines, $labelMap[$key] ?? ucwords(str_replace(['_', '-'], ' ', (string) $key)), $value);
        }
    }

    $shouldShowWorkspace = $canvasData !== ''
        || (bool) ($data['canvas_required'] ?? false)
        || $questionType === 'canvas_final_answer';

    /*
    |--------------------------------------------------------------------------
    | Susunan Baris MathLive
    |--------------------------------------------------------------------------
    | Struktur MathLive yang sudah berupa array, aligned, gathered, atau
    | matrix tidak dibungkus ulang agar urutan baris sama dengan input
    | mahasiswa.
    */
    /* MATHLIVE_ROW_ORDER_SAFE_V1 */
    $workspaceLatex = $canvasData;

    if (
        $workspaceLatex !== ''
        && ! \Illuminate\Support\Str::contains(
            $workspaceLatex,
            [
                '\\begin{array}',
                '\\begin{aligned}',
                '\\begin{alignedat}',
                '\\begin{gathered}',
                '\\begin{gather}',
                '\\begin{split}',
                '\\begin{cases}',
                '\\begin{matrix}',
                '\\begin{bmatrix}',
                '\\begin{pmatrix}',
                '\\begin{Bmatrix}',
                '\\begin{vmatrix}',
                '\\begin{Vmatrix}',
                '\\begin{smallmatrix}',
                '\\displaylines{',
            ]
        )
        && \Illuminate\Support\Str::contains($workspaceLatex, '\\\\')
    ) {
        /*
        | Data lama berisi beberapa baris biasa. Array vertikal menjaga
        | urutan setiap baris tanpa mengubah isi penulisan mahasiswa.
        */
        $workspaceLatex = '\\begin{array}{l}'
            . $workspaceLatex
            . '\\end{array}';
    }
    $legacyCanvasFile = $canvasData !== '' && \Illuminate\Support\Str::startsWith($canvasData, 'quiz-step-files/');
@endphp

<div class="mt-5 space-y-4">
    <div class="rounded-2xl border {{ $cardClass }} p-4">
        <p class="text-xs font-bold uppercase tracking-wide {{ $titleClass }}">
            {{ $isMahasiswa ? 'Jawaban' : 'Jawaban Mahasiswa' }}
        </p>

        <div class="mt-3 space-y-4">
            @if (! empty($answerLines))
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($answerLines as $line)
                        <div class="rounded-xl border {{ $valueClass }} px-4 py-3">
                            <p class="text-xs font-bold {{ $titleClass }}">{{ $line['label'] }}</p>
                            @if ($line['math'] && $line['value'] !== '')
                                <div class="mt-2 overflow-x-auto">
                                    <math-field read-only virtual-keyboard-mode="off" class="block border-0 bg-transparent text-lg shadow-none outline-none">{{ $line['value'] }}</math-field>
                                </div>
                            @else
                                <p class="mt-1 whitespace-pre-wrap break-words text-sm font-black leading-6">
                                    {{ $line['value'] !== '' ? $line['value'] : 'Tidak diisi.' }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @forelse ($matrixBlocks as $matrixBlock)
                <div class="overflow-x-auto rounded-2xl border {{ $isMahasiswa ? 'border-slate-300 bg-white' : 'border-white/10 bg-slate-950/35' }} p-4">
                    <p class="mb-3 text-center text-sm font-black {{ $titleClass }}">{{ $matrixBlock['label'] }}</p>

                    @if ($matrixBlock['hasValue'])
                        <div class="mx-auto grid w-max gap-2" style="grid-template-columns: repeat({{ $matrixBlock['columns'] }}, minmax(56px, auto));">
                            @foreach ($matrixBlock['rows'] as $row)
                                @for ($columnIndex = 0; $columnIndex < $matrixBlock['columns']; $columnIndex++)
                                    @php
                                        $cell = $row[$columnIndex] ?? '';
                                        $separator = $matrixBlock['separator'] > 0 && ($columnIndex + 1) === $matrixBlock['separator'];
                                    @endphp
                                    <div class="flex min-h-11 min-w-14 items-center justify-center rounded-xl border px-3 py-2 text-center text-sm font-black {{ $valueClass }} {{ $separator ? 'border-l-4 border-l-cyan-500' : '' }}">
                                        {{ $cell === '' ? '–' : $cell }}
                                    </div>
                                @endfor
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-xl border border-dashed {{ $emptyClass }} px-4 py-5 text-center text-sm">
                            {{ strtolower($matrixBlock['label']) }} belum diisi.
                        </div>
                    @endif
                </div>
            @empty
                @if (empty($answerLines))
                    <div class="rounded-xl border border-dashed {{ $emptyClass }} px-4 py-5 text-sm">
                        Jawaban belum diisi.
                    </div>
                @endif
            @endforelse
        </div>
    </div>

    @if ($shouldShowWorkspace)
        <div class="rounded-2xl border {{ $cardClass }} p-4">
            <p class="text-xs font-bold uppercase tracking-wide {{ $titleClass }}">Langkah Pengerjaan</p>

            @if ($legacyCanvasFile)
                <a href="{{ asset('storage/' . $canvasData) }}" target="_blank" class="mt-3 inline-flex rounded-xl border border-cyan-300/20 bg-cyan-400/10 px-4 py-3 text-sm font-black text-cyan-100 transition hover:bg-cyan-400/20">
                    Buka Lampiran Langkah Pengerjaan
                </a>
            @elseif ($workspaceLatex !== '')
                <div class="mt-3 overflow-x-auto rounded-2xl border {{ $valueClass }} p-4">
                    <div
    x-data="{ workspaceResultLatex: @js($workspaceLatex) }"
    x-init="
        (() => {
            const applyWorkspaceLatex = () => {
                const field = $refs.workspaceResultField;

                if (! field) {
                    return;
                }

                /*
                | Nilai diberikan langsung melalui API MathLive, bukan sebagai
                | teks HTML. Dengan cara ini environment array/aligned serta
                | pemisah \ yang tersimpan tetap dibaca sebagai baris.
                */
                field.value = workspaceResultLatex || '';
                field.readOnly = true;
            };

            if (window.customElements && customElements.get('math-field')) {
                $nextTick(applyWorkspaceLatex);
            } else if (window.customElements) {
                customElements.whenDefined('math-field').then(() => {
                    $nextTick(applyWorkspaceLatex);
                });
            }
        })()
    "
    class="min-h-[160px]"
>
    {{-- MATHLIVE_RESULT_VALUE_RENDER_V1 --}}
    <math-field
        x-ref="workspaceResultField"
        read-only
        virtual-keyboard-mode="off"
        math-virtual-keyboard-policy="manual"
        smart-mode="off"
        aria-label="Langkah pengerjaan mahasiswa"
        class="block min-h-[160px] border-0 bg-transparent text-lg shadow-none outline-none"
    ></math-field>
</div>
                </div>
            @else
                <div class="mt-3 rounded-xl border border-dashed {{ $emptyClass }} px-4 py-5 text-sm">
                    Mahasiswa tidak menuliskan langkah pengerjaan.
                </div>
            @endif
        </div>
    @endif
</div>
