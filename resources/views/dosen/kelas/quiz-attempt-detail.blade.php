<x-app-layout>
    @php
        $rawScore = $attempt->raw_score ?? $attempt->score;
        $isRemedial = $attempt->attempt_number > 1;
        $isScoreCapped = $isRemedial
            && $attempt->is_passed
            && $rawScore > $attempt->score;

        $durationMinutes = intdiv((int) $attempt->duration_seconds, 60);
        $durationSeconds = (int) $attempt->duration_seconds % 60;

        $orderedResponses = $attempt->responses->sortBy(function ($response) {
            return $response->question?->order_number ?? 999;
        });
    @endphp

    <div class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl space-y-6">
            <section class="rounded-[2rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl sm:p-8">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-cyan-200">
                            Rincian Hasil Kuis
                        </p>

                        <h1 class="mt-2 text-2xl font-black text-white sm:text-3xl">
                            {{ $attempt->quiz?->title ?? 'Kuis' }}
                        </h1>

                        <div class="mt-4 space-y-1 text-sm text-slate-400">
                            <p>
                                Mahasiswa:
                                <span class="font-bold text-slate-200">
                                    {{ $attempt->user?->name ?? '-' }}
                                </span>
                            </p>

                            <p>
                                Email:
                                <span class="font-bold text-slate-200">
                                    {{ $attempt->user?->email ?? '-' }}
                                </span>
                            </p>

                            <p>
                                Kelas:
                                <span class="font-bold text-slate-200">
                                    {{ $classGroup->name }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        @if ($attempt->is_passed)
                            <span class="rounded-2xl border border-green-300/20 bg-green-400/10 px-4 py-3 text-sm font-black text-green-200">
                                Lulus
                            </span>
                        @else
                            <span class="rounded-2xl border border-red-300/20 bg-red-400/10 px-4 py-3 text-sm font-black text-red-200">
                                Belum Lulus
                            </span>
                        @endif

                        <a href="{{ route('dosen.kelas.mahasiswa.riwayat', ['classGroup' => $classGroup, 'student' => $attempt->user_id]) }}"
                           class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-black text-white transition hover:bg-white/10">
                            Kembali ke Riwayat
                        </a>
                    </div>
                </div>

                <div class="mt-7 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                        <p class="text-xs text-slate-400">
                            Percobaan
                        </p>

                        <p class="mt-2 text-2xl font-black text-white">
                            Ke-{{ $attempt->attempt_number }}
                        </p>

                        @if ($isRemedial)
                            <p class="mt-1 text-xs font-semibold text-yellow-200">
                                Remedial
                            </p>
                        @endif
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                        <p class="text-xs text-slate-400">
                            Nilai Perolehan
                        </p>

                        <p class="mt-2 text-2xl font-black text-cyan-200">
                            {{ $rawScore }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                        <p class="text-xs text-slate-400">
                            Nilai Akhir
                        </p>

                        <p class="mt-2 text-2xl font-black text-green-300">
                            {{ $attempt->score }}
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            KKM {{ $classGroup->kkm }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                        <p class="text-xs text-slate-400">
                            Durasi
                        </p>

                        <p class="mt-2 text-2xl font-black text-white">
                            {{ str_pad($durationMinutes, 2, '0', STR_PAD_LEFT) }}:{{ str_pad($durationSeconds, 2, '0', STR_PAD_LEFT) }}
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            {{ $attempt->submitted_at?->format('d M Y · H:i') ?? '-' }}
                        </p>
                    </div>
                </div>

                @if ($isScoreCapped)
                    <div class="mt-5 rounded-2xl border border-yellow-300/20 bg-yellow-400/10 p-4 text-sm leading-6 text-yellow-100">
                        Mahasiswa lulus melalui remedial. Nilai yang diperoleh adalah
                        <span class="font-black">{{ $rawScore }}</span>,
                        sedangkan nilai akhir dibatasi sebesar KKM kelas, yaitu
                        <span class="font-black">{{ $classGroup->kkm }}</span>.
                    </div>
                @endif
            </section>

            <section class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                <div>
                    <p class="text-sm font-semibold text-cyan-200">
                        Rincian Jawaban
                    </p>

                    <h2 class="mt-1 text-xl font-black text-white">
                        Jawaban dan Proses Pengerjaan
                    </h2>
                </div>

                <div class="mt-6 space-y-5">
                    @forelse ($orderedResponses as $response)
                        @php
                            $question = $response->question;
                            $payload = $response->response_value ?? [];

                            if (!is_array($payload)) {
                                $payload = [];
                            }

                            $questionType = $question?->question_type;
                        @endphp

                        <article class="overflow-hidden rounded-2xl border border-white/10 bg-slate-950/25">
                            <div class="flex flex-col gap-4 border-b border-white/10 px-5 py-4 md:flex-row md:items-start md:justify-between">
                                <div class="min-w-0">
                                    <p class="text-xs font-black uppercase tracking-[0.16em] text-cyan-200">
                                        Soal {{ $question?->order_number ?? '-' }}
                                    </p>

                                    <p class="mt-2 whitespace-pre-line text-sm font-semibold leading-6 text-white">
                                        {{ $question?->question_text ?? 'Soal tidak ditemukan.' }}
                                    </p>
                                </div>

                                <div class="flex shrink-0 flex-wrap gap-2">
                                    <span class="rounded-lg border border-white/10 bg-white/5 px-2.5 py-1 text-xs font-bold text-slate-200">
                                        {{ $response->points_earned }}/{{ $question?->points ?? 0 }} poin
                                    </span>

                                    @if ($response->is_correct)
                                        <span class="rounded-lg border border-green-300/20 bg-green-400/10 px-2.5 py-1 text-xs font-black text-green-200">
                                            Benar
                                        </span>
                                    @elseif ($response->is_answered)
                                        <span class="rounded-lg border border-red-300/20 bg-red-400/10 px-2.5 py-1 text-xs font-black text-red-200">
                                            Belum Tepat
                                        </span>
                                    @else
                                        <span class="rounded-lg border border-slate-300/20 bg-slate-400/10 px-2.5 py-1 text-xs font-black text-slate-200">
                                            Tidak Dijawab
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="px-5 py-5">
                                @if ($questionType === 'checkbox')
                                    @php
                                        $options = $question?->question_data['options'] ?? [];
                                        $selectedOptions = collect($payload['selected'] ?? [])
                                            ->map(function ($key) use ($options) {
                                                return $key . '. ' . ($options[$key] ?? '');
                                            })
                                            ->filter()
                                            ->values();
                                    @endphp

                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                        Jawaban Mahasiswa
                                    </p>

                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @forelse ($selectedOptions as $selectedOption)
                                            <span class="rounded-xl border border-cyan-300/20 bg-cyan-400/10 px-3 py-2 text-sm font-semibold text-cyan-100">
                                                {{ $selectedOption }}
                                            </span>
                                        @empty
                                            <span class="text-sm text-slate-400">
                                                Tidak ada pilihan yang dipilih.
                                            </span>
                                        @endforelse
                                    </div>
                                @elseif (in_array($questionType, ['short_text', 'math_notation']))
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                        Jawaban Mahasiswa
                                    </p>

                                    @if ($questionType === 'math_notation' && !empty($payload['answer']))
                                        <div class="mt-3 overflow-x-auto rounded-2xl border border-white/10 bg-white/5 p-4">
                                            <math-field
                                                read-only
                                                virtual-keyboard-mode="off"
                                                class="block border-0 bg-transparent text-lg text-white">
                                                {{ $payload['answer'] }}
                                            </math-field>
                                        </div>
                                    @else
                                        <div class="mt-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-semibold text-slate-100">
                                            {{ $payload['answer'] ?? 'Tidak diisi.' }}
                                        </div>
                                    @endif
                                @elseif ($questionType === 'canvas_final_answer')
                                    @php
                                        $canvasData = $response->canvas_data;
                                        $isLegacyFile = is_string($canvasData)
                                            && \Illuminate\Support\Str::startsWith($canvasData, 'quiz-step-files/');
                                    @endphp

                                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_260px]">
                                        <div>
                                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                                Langkah Pengerjaan
                                            </p>

                                            @if ($isLegacyFile)
                                                <a href="{{ asset('storage/' . $canvasData) }}"
                                                   target="_blank"
                                                   class="mt-3 inline-flex rounded-xl border border-cyan-300/20 bg-cyan-400/10 px-4 py-3 text-sm font-black text-cyan-100 transition hover:bg-cyan-400/20">
                                                    Buka File Langkah Pengerjaan
                                                </a>
                                            @elseif (!empty($canvasData))
                                                @php
                                                    $workspaceLatex = trim((string) $canvasData);

                                                    $hasMultilineWrapper = \Illuminate\Support\Str::contains($workspaceLatex, [
                                                        '\begin{array}',
                                                        '\begin{aligned}',
                                                        '\begin{gathered}',
                                                        '\displaylines{',
                                                    ]);

                                                    /*
                                                    | Jika data lama hanya memiliki simbol \\ tanpa pembungkus multi-baris,
                                                    | bungkus menjadi displaylines agar setiap langkah tetap berada di baris sendiri.
                                                    */
                                                    if (
                                                        ! $hasMultilineWrapper
                                                        && \Illuminate\Support\Str::contains($workspaceLatex, '\\\\')
                                                    ) {
                                                        $workspaceLatex = '\displaylines{' . $workspaceLatex . '}';
                                                    }
                                                @endphp

                                                <div
                                                    x-data="{ workspaceLatex: @js($workspaceLatex) }"
                                                    x-init="
                                                        const renderWorkspace = () => {
                                                            const mathField = $refs.workspaceViewer;

                                                            if (!mathField) {
                                                                return;
                                                            }

                                                            mathField.value = workspaceLatex;
                                                            mathField.readOnly = true;
                                                        };

                                                        if (customElements.get('math-field')) {
                                                            $nextTick(renderWorkspace);
                                                        } else {
                                                            customElements.whenDefined('math-field').then(() => {
                                                                $nextTick(renderWorkspace);
                                                            });
                                                        }
                                                    "
                                                    class="mt-3 overflow-x-auto rounded-2xl border border-white/10 bg-white/5 p-4">

                                                    <math-field
                                                        x-ref="workspaceViewer"
                                                        read-only
                                                        virtual-keyboard-mode="off"
                                                        class="block min-h-[180px] border-0 bg-transparent text-lg text-white">
                                                    </math-field>
                                                </div>
                                            @else
                                                <div class="mt-3 rounded-2xl border border-dashed border-white/10 px-4 py-5 text-sm text-slate-400">
                                                    Mahasiswa tidak menuliskan langkah pengerjaan.
                                                </div>
                                            @endif
                                        </div>

                                        <div>
                                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                                Jawaban Akhir
                                            </p>

                                            <div class="mt-3 space-y-2">
                                                @foreach (($payload['final'] ?? []) as $variable => $answer)
                                                    <div class="flex items-center justify-between rounded-xl border border-white/10 bg-white/5 px-4 py-3">
                                                        <span class="font-black text-cyan-200">
                                                            {{ $variable }} =
                                                        </span>

                                                        <span class="font-black text-white">
                                                            {{ $answer }}
                                                        </span>
                                                    </div>
                                                @endforeach

                                                @if (empty($payload['final'] ?? []))
                                                    <div class="rounded-xl border border-dashed border-white/10 px-4 py-4 text-sm text-slate-400">
                                                        Jawaban akhir tidak diisi.
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @elseif (in_array($questionType, ['matrix', 'augmented_matrix']))
                                    @php
                                        $matrix = $payload['matrix'] ?? [];
                                    @endphp

                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                        Jawaban Mahasiswa
                                    </p>

                                    @if (!empty($matrix))
                                        <div class="mt-3 overflow-x-auto">
                                            <table class="border-separate border-spacing-2">
                                                <tbody>
                                                    @foreach ($matrix as $row)
                                                        <tr>
                                                            @foreach ($row as $columnIndex => $cell)
                                                                <td class="min-w-[54px] rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-center text-sm font-black text-white {{ $questionType === 'augmented_matrix' && $loop->last ? 'border-l-4 border-l-cyan-300' : '' }}">
                                                                    {{ $cell }}
                                                                </td>
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="mt-3 text-sm text-slate-400">
                                            Matriks tidak diisi.
                                        </p>
                                    @endif
                                @elseif ($questionType === 'matrix_equation')
                                    @php
                                        $matrixA = $payload['A'] ?? [];
                                        $vectorB = $payload['b'] ?? [];
                                    @endphp

                                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto_180px]">
                                        <div>
                                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                                Matriks A
                                            </p>

                                            @if (!empty($matrixA))
                                                <div class="mt-3 overflow-x-auto">
                                                    <table class="border-separate border-spacing-2">
                                                        <tbody>
                                                            @foreach ($matrixA as $row)
                                                                <tr>
                                                                    @foreach ($row as $cell)
                                                                        <td class="min-w-[54px] rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-center text-sm font-black text-white">
                                                                            {{ $cell }}
                                                                        </td>
                                                                    @endforeach
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <p class="mt-3 text-sm text-slate-400">
                                                    Matriks A tidak diisi.
                                                </p>
                                            @endif
                                        </div>

                                        <div class="hidden items-center justify-center text-3xl font-black text-slate-300 lg:flex">
                                            =
                                        </div>

                                        <div>
                                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                                Vektor b
                                            </p>

                                            <div class="mt-3 space-y-2">
                                                @forelse ($vectorB as $cell)
                                                    <div class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-center text-sm font-black text-white">
                                                        {{ $cell }}
                                                    </div>
                                                @empty
                                                    <p class="text-sm text-slate-400">
                                                        Vektor b tidak diisi.
                                                    </p>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if ($response->feedback)
                                    <div class="mt-5 rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                            Umpan Balik Sistem
                                        </p>

                                        <p class="mt-2 text-sm leading-6 text-slate-200">
                                            {{ $response->feedback }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-white/10 p-8 text-center text-sm text-slate-400">
                            Tidak ada data jawaban untuk percobaan ini.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/mathlive"></script>
</x-app-layout>