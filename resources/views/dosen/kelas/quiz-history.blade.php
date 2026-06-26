<x-app-layout>
    @php
        $formatDuration = function ($seconds) {
            $seconds = (int) $seconds;
            $minutes = intdiv($seconds, 60);
            $remainingSeconds = $seconds % 60;

            return str_pad($minutes, 2, '0', STR_PAD_LEFT)
                . ':'
                . str_pad($remainingSeconds, 2, '0', STR_PAD_LEFT);
        };
    @endphp

    <div class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl space-y-6">
            <section class="rounded-[2rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl sm:p-8">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-cyan-200">
                            Riwayat Hasil Kuis
                        </p>

                        <h1 class="mt-2 text-2xl font-black text-white sm:text-3xl">
                            {{ $student->name }}
                        </h1>

                        <p class="mt-2 text-sm text-slate-400">
                            {{ $student->email }}
                        </p>

                        <p class="mt-4 text-sm leading-6 text-slate-400">
                            Pilih kuis untuk melihat daftar percobaan. Setiap percobaan memiliki rincian jawaban dan langkah pengerjaan masing-masing.
                        </p>
                    </div>

                    <a
                        href="{{ route('dosen.kelas.show', $classGroup) }}"
                        class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-black text-white transition hover:bg-white/10">
                        Kembali ke Kelas
                    </a>
                </div>

                <div class="mt-7 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                        <p class="text-xs text-slate-400">
                            Kuis Dikerjakan
                        </p>

                        <p class="mt-2 text-2xl font-black text-white">
                            {{ $historySummary['quiz_count'] }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                        <p class="text-xs text-slate-400">
                            Jumlah Percobaan
                        </p>

                        <p class="mt-2 text-2xl font-black text-cyan-200">
                            {{ $historySummary['attempt_count'] }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                        <p class="text-xs text-slate-400">
                            Kuis Lulus
                        </p>

                        <p class="mt-2 text-2xl font-black text-green-300">
                            {{ $historySummary['passed_quiz_count'] }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                        <p class="text-xs text-slate-400">
                            Perlu Remedial
                        </p>

                        <p class="mt-2 text-2xl font-black text-yellow-200">
                            {{ $historySummary['needs_remedial_count'] }}
                        </p>
                    </div>
                </div>
            </section>

            <section class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                <div>
                    <h2 class="text-xl font-black text-white">
                        Pilih Kuis
                    </h2>

                    <p class="mt-2 text-sm leading-6 text-slate-400">
                        Buka salah satu kuis, lalu pilih percobaan yang ingin diperiksa.
                    </p>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($quizHistories as $history)
                        @php
                            $quiz = $history['quiz'];
                            $attempts = $history['attempts'];
                            $latestAttempt = $history['latest_attempt'];
                            $latestRawScore = $latestAttempt?->raw_score ?? $latestAttempt?->score;
                            $latestNeedsRemedial = $latestAttempt
                                && ! $latestAttempt->is_passed
                                && (int) $latestAttempt->attempt_number < 3;
                        @endphp

                        <details class="group overflow-hidden rounded-2xl border border-white/10 bg-slate-950/25">
                            <summary class="flex cursor-pointer list-none flex-col gap-4 px-5 py-4 transition hover:bg-white/[0.04] sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <p class="text-base font-black text-white">
                                        {{ $quiz?->title ?? 'Kuis' }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $attempts->count() }} percobaan tercatat
                                    </p>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-lg border border-white/10 bg-white/5 px-2.5 py-1 text-xs font-bold text-slate-200">
                                        Nilai Akhir: {{ $latestAttempt?->score ?? '-' }}
                                    </span>

                                    @if ($latestAttempt?->is_passed)
                                        <span class="rounded-lg border border-green-300/20 bg-green-400/10 px-2.5 py-1 text-xs font-black text-green-200">
                                            Lulus
                                        </span>
                                    @elseif ($latestNeedsRemedial)
                                        <span class="rounded-lg border border-yellow-300/20 bg-yellow-400/10 px-2.5 py-1 text-xs font-black text-yellow-200">
                                            Perlu Remedial
                                        </span>
                                    @else
                                        <span class="rounded-lg border border-red-300/20 bg-red-400/10 px-2.5 py-1 text-xs font-black text-red-200">
                                            Belum Lulus
                                        </span>
                                    @endif

                                    <span class="text-lg font-black text-slate-300 transition group-open:rotate-180">
                                        ˅
                                    </span>
                                </div>
                            </summary>

                            <div class="border-t border-white/10 px-5 py-5">
                                <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <h3 class="text-sm font-black text-white">
                                            Riwayat Percobaan
                                        </h3>

                                        <p class="mt-1 text-xs text-slate-400">
                                            Pilih rincian untuk melihat jawaban pada percobaan tersebut.
                                        </p>
                                    </div>

                                    <p class="text-xs font-semibold text-slate-400">
                                        Nilai perolehan terakhir: {{ $latestRawScore ?? '-' }}
                                    </p>
                                </div>

                                <div class="overflow-x-auto rounded-2xl border border-white/10">
                                    <table class="min-w-[840px] w-full divide-y divide-white/10">
                                        <thead class="bg-slate-950/50">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-400">
                                                    Percobaan
                                                </th>
                                                <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide text-slate-400">
                                                    Nilai Perolehan
                                                </th>
                                                <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide text-slate-400">
                                                    Nilai Akhir
                                                </th>
                                                <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide text-slate-400">
                                                    Status
                                                </th>
                                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-400">
                                                    Waktu
                                                </th>
                                                <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-400">
                                                    Aksi
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody class="divide-y divide-white/10">
                                            @foreach ($attempts as $attemptItem)
                                                @php
                                                    $rawScore = $attemptItem->raw_score ?? $attemptItem->score;
                                                    $isRemedial = $attemptItem->attempt_number > 1;
                                                    $isAttemptNeedsRemedial = ! $attemptItem->is_passed
                                                        && (int) $attemptItem->attempt_number < 3;
                                                @endphp

                                                <tr class="bg-white/[0.02]">
                                                    <td class="px-4 py-4">
                                                        <div class="flex flex-wrap gap-2">
                                                            <span class="rounded-lg border border-white/10 bg-white/5 px-2.5 py-1 text-xs font-bold text-slate-200">
                                                                Ke-{{ $attemptItem->attempt_number }}
                                                            </span>

                                                            @if ($isRemedial)
                                                                <span class="rounded-lg border border-yellow-300/20 bg-yellow-400/10 px-2.5 py-1 text-xs font-black text-yellow-200">
                                                                    Remedial
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </td>

                                                    <td class="px-4 py-4 text-center text-sm font-black text-cyan-200">
                                                        {{ $rawScore }}
                                                    </td>

                                                    <td class="px-4 py-4 text-center text-sm font-black text-green-300">
                                                        {{ $attemptItem->score }}
                                                    </td>

                                                    <td class="px-4 py-4 text-center">
                                                        @if ($attemptItem->is_passed)
                                                            <span class="inline-flex rounded-full border border-green-300/20 bg-green-400/10 px-3 py-1 text-xs font-black text-green-200">
                                                                Lulus
                                                            </span>
                                                        @elseif ($isAttemptNeedsRemedial)
                                                            <span class="inline-flex rounded-full border border-yellow-300/20 bg-yellow-400/10 px-3 py-1 text-xs font-black text-yellow-200">
                                                                Perlu Remedial
                                                            </span>
                                                        @else
                                                            <span class="inline-flex rounded-full border border-red-300/20 bg-red-400/10 px-3 py-1 text-xs font-black text-red-200">
                                                                Belum Lulus
                                                            </span>
                                                        @endif
                                                    </td>

                                                    <td class="px-4 py-4">
                                                        <p class="text-sm font-semibold text-slate-200">
                                                            {{ $formatDuration($attemptItem->duration_seconds) }}
                                                        </p>

                                                        <p class="mt-1 text-xs text-slate-400">
                                                            {{ $attemptItem->submitted_at?->format('d M Y · H:i') ?? '-' }}
                                                        </p>
                                                    </td>

                                                    <td class="px-4 py-4 text-right">
                                                        <a
                                                            href="{{ route('dosen.kelas.kuis.detail', ['classGroup' => $classGroup, 'attempt' => $attemptItem]) }}"
                                                            class="inline-flex rounded-xl border border-cyan-300/20 bg-cyan-400/10 px-3 py-2 text-xs font-black text-cyan-100 transition hover:bg-cyan-400/20">
                                                            Lihat Jawaban
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </details>
                    @empty
                        <div class="rounded-2xl border border-dashed border-white/10 px-5 py-10 text-center text-sm text-slate-400">
                            Mahasiswa ini belum memiliki riwayat kuis pada kelas ini.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
