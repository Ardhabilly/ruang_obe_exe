<x-app-layout>
    <div class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl space-y-6">
            <section class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-white/[0.06] p-8 backdrop-blur-xl">
                <div class="absolute right-[-100px] top-[-100px] h-72 w-72 rounded-full {{ $attempt->is_passed ? 'bg-green-400/10' : 'bg-red-400/10' }} blur-3xl"></div>

                <div class="relative">
                    <p class="text-sm font-bold uppercase tracking-[0.25em] {{ $attempt->is_passed ? 'text-green-300' : 'text-red-300' }}">
                        Hasil CBT
                    </p>

                    <h1 class="mt-3 text-4xl font-black tracking-tight text-white">
                        {{ $attempt->quiz->title }}
                    </h1>

                    <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-400">
                        Hasil pengerjaan kuis Anda sudah disimpan ke dalam sistem.
                    </p>
                </div>
            </section>

            <section class="grid gap-5 md:grid-cols-4">
                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-5 backdrop-blur-xl">
                    <p class="text-sm text-slate-400">Nilai tercatat</p>
                    <p class="mt-2 text-3xl font-black text-white">
                        {{ $attempt->score }}
                    </p>
                </div>

                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-5 backdrop-blur-xl">
                    <p class="text-sm text-slate-400">Nilai asli</p>
                    <p class="mt-2 text-3xl font-black text-white">
                        {{ $rawScore }}
                    </p>
                    <p class="mt-1 text-xs font-semibold text-slate-400">
                        {{ $attempt->correct_answers }}/{{ $attempt->total_questions }} jawaban benar
                    </p>
                </div>

                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-5 backdrop-blur-xl">
                    <p class="text-sm text-slate-400">KKM</p>
                    <p class="mt-2 text-3xl font-black text-white">
                        {{ $attempt->classGroup->kkm }}
                    </p>
                </div>

                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-5 backdrop-blur-xl">
                    <p class="text-sm text-slate-400">Status</p>
                    <p class="mt-2 text-2xl font-black {{ $attempt->is_passed ? 'text-green-300' : 'text-red-300' }}">
                        {{ $attempt->is_passed ? 'Lulus' : 'Belum Lulus' }}
                    </p>
                </div>
            </section>

            @if ($remedialScoreCapped)
                <section class="rounded-[1.5rem] border border-cyan-300/20 bg-cyan-400/10 p-5 text-sm leading-6 text-cyan-100">
                    <p class="font-black">Nilai remedial dicatat sesuai KKM.</p>
                    <p class="mt-2">
                        Nilai mentah Anda adalah <span class="font-black">{{ $rawScore }}</span>.
                        Karena kelulusan diperoleh melalui remedial, nilai tercatat ditetapkan sebesar
                        KKM, yaitu <span class="font-black">{{ $attempt->score }}</span>.
                    </p>
                </section>
            @elseif ($canRemedial)
                <section class="rounded-[1.5rem] border border-yellow-300/20 bg-yellow-400/10 p-5 text-sm leading-6 text-yellow-100">
                    <p class="font-black">Remedial masih tersedia.</p>
                    <p class="mt-2">
                        Nilai Anda belum mencapai KKM. Anda dapat mengikuti remedial ke-{{ $nextAttemptNumber }}
                        dan mengulang sampai nilai memenuhi KKM.
                    </p>
                </section>
            @elseif ($attempt->is_passed)
                <section class="rounded-[1.5rem] border border-green-300/20 bg-green-400/10 p-5 text-sm leading-6 text-green-100">
                    <p class="font-black">Kuis telah selesai.</p>
                    <p class="mt-2">
                        Anda telah memenuhi KKM. Percobaan tambahan tidak diperlukan.
                    </p>
                </section>
            @endif

            <section class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-xl font-black text-white">
                            Rincian Jawaban
                        </h2>

                        <p class="mt-1 text-sm text-slate-400">
                            Feedback ditampilkan setelah kuis selesai.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        {{-- NEXT_LEARNING_ACTION_AFTER_CHAPTER_QUIZ_VIEW_V1 --}}
                        @if ($attempt->is_passed && $nextLessonAfterQuiz)
                            <a
                                href="{{ route('mahasiswa.materi.show', $nextLessonAfterQuiz->slug) }}"
                                class="rounded-2xl bg-cyan-400 px-5 py-3 text-sm font-black text-slate-950 transition hover:bg-cyan-300"
                            >
                                Lanjut ke Bab Berikutnya
                            </a>
                        @elseif ($attempt->is_passed && $nextEvaluationAfterQuiz)
                            <a
                                href="{{ route('mahasiswa.kuis.instruction', $nextEvaluationAfterQuiz) }}"
                                class="rounded-2xl bg-cyan-400 px-5 py-3 text-sm font-black text-slate-950 transition hover:bg-cyan-300"
                            >
                                Lanjut ke Evaluasi Akhir
                            </a>
                        @endif

                        @if ($canRemedial)
                            <a href="{{ route('mahasiswa.kuis.instruction', $attempt->quiz) }}"
                               class="rounded-2xl bg-yellow-400 px-5 py-3 text-sm font-black text-slate-950 hover:bg-yellow-300">
                                Ikuti Remedial Ke-{{ $nextAttemptNumber }}
                            </a>
                        @endif
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    @foreach ($attempt->responses->sortBy('question.order_number') as $response)
                        <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-5">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <p class="text-sm font-black text-cyan-200">
                                        Soal {{ $response->question->order_number }}
                                    </p>

                                    <p class="mt-2 text-sm leading-7 text-slate-300">
                                        {{ $response->question->question_text }}
                                    </p>
                                </div>

                                <div class="shrink-0 rounded-xl px-3 py-2 text-sm font-black {{ $response->is_correct ? 'bg-green-400/10 text-green-300' : 'bg-red-400/10 text-red-300' }}">
                                    {{ $response->points_earned }}/{{ $response->question->points }}
                                </div>
                            </div>

                            <x-quiz-response-display
                                :question="$response->question"
                                :response="$response"
                                mode="mahasiswa"
                            />
                            <div class="mt-4 rounded-xl border border-white/10 bg-white/5 p-4 text-sm leading-6 {{ $response->is_correct ? 'text-green-200' : 'text-red-200' }}">
                                {{ $response->feedback }}
                            </div>

                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/mathlive"></script>
</x-app-layout>