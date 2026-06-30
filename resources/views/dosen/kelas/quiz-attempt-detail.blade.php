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
                        @endphp

                        <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-5">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <p class="text-sm font-black text-cyan-200">
                                        Soal {{ $question?->order_number ?? '-' }}
                                    </p>

                                    <p class="mt-2 text-sm leading-7 text-slate-300">
                                        {{ $question?->question_text ?? 'Soal tidak ditemukan.' }}
                                    </p>
                                </div>

                                <div class="shrink-0 rounded-xl px-3 py-2 text-sm font-black {{ $response->is_correct ? 'bg-green-400/10 text-green-300' : 'bg-red-400/10 text-red-300' }}">
                                    {{ $response->points_earned }}/{{ $question?->points ?? 0 }} poin
                                </div>
                            </div>

                            <x-quiz-response-display
                                :question="$question"
                                :response="$response"
                                mode="mahasiswa"
                            />

                            <div class="mt-4 rounded-xl border border-white/10 bg-white/5 p-4 text-sm leading-6 {{ $response->is_correct ? 'text-green-200' : 'text-red-200' }}">
                                {{ $response->feedback }}
                            </div>
                        </div>
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