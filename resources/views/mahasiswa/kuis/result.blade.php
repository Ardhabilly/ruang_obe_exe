<x-app-layout>
    @php
        $statusColor = $attempt->is_passed
            ? 'green'
            : ($canRemedial ? 'yellow' : 'red');
    @endphp

    <div class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl space-y-6">
            <section class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-white/[0.06] p-8 backdrop-blur-xl">
                <div class="absolute right-[-100px] top-[-100px] h-72 w-72 rounded-full {{ $statusColor === 'green' ? 'bg-green-400/10' : ($statusColor === 'yellow' ? 'bg-yellow-400/10' : 'bg-red-400/10') }} blur-3xl"></div>

                <div class="relative">
                    <p class="text-sm font-bold uppercase tracking-[0.25em] {{ $statusColor === 'green' ? 'text-green-300' : ($statusColor === 'yellow' ? 'text-yellow-300' : 'text-red-300') }}">
                        Hasil CBT · Percobaan ke-{{ $attempt->attempt_number }}
                    </p>

                    <h1 class="mt-3 text-4xl font-black tracking-tight text-white">
                        {{ $attempt->quiz->title }}
                    </h1>

                    <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-400">
                        Hasil pengerjaan kuis telah disimpan ke dalam sistem.
                    </p>
                </div>
            </section>

            <section class="grid gap-5 md:grid-cols-4">
                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-5 backdrop-blur-xl">
                    <p class="text-sm text-slate-400">Nilai Tercatat</p>
                    <p class="mt-2 text-3xl font-black text-white">
                        {{ $attempt->score }}
                    </p>
                </div>

                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-5 backdrop-blur-xl">
                    <p class="text-sm text-slate-400">KKM</p>
                    <p class="mt-2 text-3xl font-black text-white">
                        {{ $attempt->classGroup->kkm }}
                    </p>
                </div>

                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-5 backdrop-blur-xl">
                    <p class="text-sm text-slate-400">Benar</p>
                    <p class="mt-2 text-3xl font-black text-white">
                        {{ $attempt->correct_answers }}/{{ $attempt->total_questions }}
                    </p>
                </div>

                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-5 backdrop-blur-xl">
                    <p class="text-sm text-slate-400">Status</p>
                    <p class="mt-2 text-2xl font-black {{ $attempt->is_passed ? 'text-green-300' : ($canRemedial ? 'text-yellow-300' : 'text-red-300') }}">
                        {{ $attempt->is_passed ? 'Lulus' : ($canRemedial ? 'Remedial' : 'Belum Lulus') }}
                    </p>
                </div>
            </section>

            @if ($attempt->is_passed && $attempt->attempt_number === 1)
                <section class="rounded-[1.5rem] border border-green-300/20 bg-green-400/10 p-5 text-sm leading-6 text-green-100">
                    <p class="font-black">Anda lulus pada percobaan pertama.</p>
                    <p class="mt-2">
                        Nilai tercatat sesuai hasil kuis dan remedial tidak diperlukan.
                    </p>
                </section>
            @elseif ($attempt->is_passed)
                <section class="rounded-[1.5rem] border border-green-300/20 bg-green-400/10 p-5 text-sm leading-6 text-green-100">
                    <p class="font-black">Anda lulus melalui remedial.</p>
                    <p class="mt-2">
                        Sesuai ketentuan remedial, nilai yang dicatat maksimal sebesar KKM kelas.
                        @if ($remedialScoreCapped)
                            Nilai tercatat telah disesuaikan menjadi {{ $attempt->classGroup->kkm }}.
                        @endif
                    </p>
                </section>
            @elseif ($canRemedial)
                <section class="rounded-[1.5rem] border border-yellow-300/20 bg-yellow-400/10 p-5 text-sm leading-6 text-yellow-100">
                    <p class="font-black">Remedial wajib dikerjakan.</p>
                    <p class="mt-2">
                        Nilai belum mencapai KKM. Anda masih memiliki kesempatan untuk mengikuti remedial
                        percobaan ke-{{ $nextAttemptNumber }} dari {{ $maxAttempts }}.
                    </p>
                </section>
            @else
                <section class="rounded-[1.5rem] border border-red-300/20 bg-red-400/10 p-5 text-sm leading-6 text-red-100">
                    <p class="font-black">Batas percobaan telah habis.</p>
                    <p class="mt-2">
                        Nilai belum mencapai KKM setelah {{ $maxAttempts }} percobaan total.
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
                        <a href="{{ route('mahasiswa.materi.index') }}"
                           class="rounded-2xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-black text-white hover:bg-white/10">
                            Kembali ke Materi
                        </a>

                        @if ($canRemedial)
                            <form action="{{ route('mahasiswa.kuis.start', $attempt->quiz) }}" method="POST">
                                @csrf

                                <button type="submit"
                                        class="rounded-2xl bg-yellow-400 px-5 py-3 text-sm font-black text-slate-950 hover:bg-yellow-300">
                                    Mulai Remedial Ke-{{ $nextAttemptNumber }}
                                </button>
                            </form>
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

                            <div class="mt-4 rounded-xl border border-white/10 bg-white/5 p-4 text-sm leading-6 {{ $response->is_correct ? 'text-green-200' : 'text-red-200' }}">
                                {{ $response->feedback }}
                            </div>

                            @if ($response->canvas_data && $response->question->question_type === 'canvas_final_answer')
                                <div class="mt-4 rounded-xl border border-cyan-300/20 bg-cyan-400/10 p-4 text-sm text-cyan-100">
                                    File langkah pengerjaan:
                                    <a href="{{ asset('storage/' . $response->canvas_data) }}"
                                       target="_blank"
                                       class="font-black underline hover:text-cyan-200">
                                        Lihat file
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
