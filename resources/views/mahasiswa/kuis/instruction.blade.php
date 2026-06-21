<x-app-layout>
    @php
        $buttonText = $attemptsUsed === 0
            ? 'Mulai Kuis'
            : 'Mulai Remedial';
    @endphp

    <div class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl space-y-6">
            <section class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-white/[0.06] p-8 backdrop-blur-xl">
                <div class="absolute right-[-100px] top-[-100px] h-72 w-72 rounded-full bg-cyan-400/10 blur-3xl"></div>

                <div class="relative">
                    <p class="text-sm font-bold uppercase tracking-[0.25em] text-cyan-300">
                        Instruksi CBT
                    </p>

                    <h1 class="mt-3 text-4xl font-black tracking-tight text-white">
                        {{ $quiz->title }}
                    </h1>

                    <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-400">
                        Bacalah instruksi pengerjaan sebelum memulai kuis. Setelah tombol mulai ditekan, waktu kuis akan berjalan.
                    </p>
                </div>
            </section>

            <section class="grid gap-5 md:grid-cols-4">
                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-5 backdrop-blur-xl">
                    <p class="text-sm text-slate-400">Soal</p>
                    <p class="mt-2 text-2xl font-black text-white">
                        {{ $quiz->questions->count() }} Soal
                    </p>
                </div>

                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-5 backdrop-blur-xl">
                    <p class="text-sm text-slate-400">Waktu</p>
                    <p class="mt-2 text-2xl font-black text-white">
                        {{ $quiz->duration_minutes }} Menit
                    </p>
                </div>

                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-5 backdrop-blur-xl">
                    <p class="text-sm text-slate-400">KKM</p>
                    <p class="mt-2 text-2xl font-black text-white">
                        {{ $quiz->classGroup->kkm }}
                    </p>
                </div>

                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-5 backdrop-blur-xl">
                    <p class="text-sm text-slate-400">Maks. Percobaan</p>
                    <p class="mt-2 text-2xl font-black text-white">
                        {{ $maxAttempts }}x
                    </p>
                </div>
            </section>

            @if ($passedAttempt)
                <section class="rounded-[1.5rem] border border-green-300/20 bg-green-400/10 p-5 text-sm leading-6 text-green-100">
                    <p class="font-black">Kuis telah lulus.</p>

                    <p class="mt-2">
                        Anda lulus pada percobaan ke-{{ $passedAttempt->attempt_number }} dengan nilai tercatat
                        <span class="font-black">{{ $passedAttempt->score }}/{{ $passedAttempt->max_score }}</span>.

                        @if ($passedAttempt->attempt_number === 1)
                            Anda tidak perlu mengikuti remedial.
                        @else
                            Sesuai ketentuan remedial, nilai yang dicatat maksimal sebesar KKM kelas.
                        @endif
                    </p>
                </section>
            @elseif ($latestAttempt)
                <section class="rounded-[1.5rem] border {{ $remainingAttempts > 0 ? 'border-yellow-300/20 bg-yellow-400/10 text-yellow-100' : 'border-red-300/20 bg-red-400/10 text-red-100' }} p-5 text-sm leading-6">
                    <p class="font-black">
                        {{ $remainingAttempts > 0 ? 'Remedial diperlukan.' : 'Batas percobaan telah habis.' }}
                    </p>

                    <p class="mt-2">
                        Percobaan ke-{{ $latestAttempt->attempt_number }} memperoleh nilai tercatat
                        <span class="font-black">{{ $latestAttempt->score }}/{{ $latestAttempt->max_score }}</span>,
                        sedangkan KKM kelas adalah <span class="font-black">{{ $quiz->classGroup->kkm }}</span>.

                        @if ($remainingAttempts > 0)
                            Anda wajib mengerjakan remedial. Sisa kesempatan: {{ $remainingAttempts }} dari {{ $maxAttempts }} percobaan total.
                        @else
                            Seluruh kesempatan remedial telah digunakan.
                        @endif
                    </p>
                </section>
            @endif

            <section class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-8 backdrop-blur-xl">
                <h2 class="text-2xl font-black text-white">
                    Petunjuk Pengerjaan
                </h2>

                <div class="mt-6 whitespace-pre-line rounded-2xl border border-white/10 bg-slate-950/40 p-6 text-sm leading-7 text-slate-300">
                    {{ $quiz->instruction }}
                </div>

                <div class="mt-6 rounded-2xl border border-yellow-300/20 bg-yellow-400/10 p-5 text-sm leading-6 text-yellow-100">
                    <p class="font-black">
                        Peringatan
                    </p>

                    <p class="mt-2">
                        Jawaban pilihan dan isian disimpan otomatis. Jika halaman direfresh sebelum kuis dikumpulkan,
                        file langkah pengerjaan perlu dipilih kembali sebelum menekan tombol kumpulkan.
                    </p>
                </div>
            </section>

            <section class="flex flex-col gap-3 rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-black text-white">
                        @if ($passedAttempt)
                            Kuis sudah selesai
                        @elseif ($attemptsUsed === 0)
                            Siap memulai kuis?
                        @elseif ($remainingAttempts > 0)
                            Siap mengikuti remedial?
                        @else
                            Kuis sudah selesai
                        @endif
                    </h2>

                    <p class="mt-1 text-sm text-slate-400">
                        @if ($passedAttempt)
                            Anda telah memenuhi KKM pada percobaan ke-{{ $passedAttempt->attempt_number }}.
                        @elseif ($attemptsUsed === 0)
                            Anda memiliki maksimal {{ $maxAttempts }} percobaan total bila diperlukan remedial.
                        @elseif ($remainingAttempts > 0)
                            Percobaan berikutnya: ke-{{ $nextAttemptNumber }} dari {{ $maxAttempts }}.
                        @else
                            Tidak ada kesempatan percobaan tambahan.
                        @endif
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('mahasiswa.materi.index') }}"
                       class="rounded-2xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-black text-white hover:bg-white/10">
                        Kembali ke Materi
                    </a>

                    @if ($inProgressAttempt)
                        <a href="{{ route('mahasiswa.kuis.attempt', $inProgressAttempt) }}"
                           class="rounded-2xl bg-cyan-400 px-5 py-3 text-sm font-black text-slate-950 shadow-lg shadow-cyan-500/20 hover:bg-cyan-300">
                            Lanjutkan Kuis
                        </a>
                    @elseif ($passedAttempt)
                        <a href="{{ route('mahasiswa.kuis.result', $passedAttempt) }}"
                           class="rounded-2xl bg-green-400 px-5 py-3 text-sm font-black text-slate-950 shadow-lg shadow-green-500/20 hover:bg-green-300">
                            Lihat Hasil
                        </a>
                    @elseif ($canStartAttempt)
                        <form action="{{ route('mahasiswa.kuis.start', $quiz) }}" method="POST">
                            @csrf

                            <button type="submit"
                                    class="rounded-2xl bg-cyan-400 px-5 py-3 text-sm font-black text-slate-950 shadow-lg shadow-cyan-500/20 hover:bg-cyan-300">
                                {{ $buttonText }}
                                @if ($attemptsUsed > 0)
                                    · Ke-{{ $nextAttemptNumber }}
                                @endif
                            </button>
                        </form>
                    @elseif ($latestAttempt)
                        <a href="{{ route('mahasiswa.kuis.result', $latestAttempt) }}"
                           class="rounded-2xl bg-red-400 px-5 py-3 text-sm font-black text-slate-950 shadow-lg shadow-red-500/20 hover:bg-red-300">
                            Lihat Hasil Terakhir
                        </a>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
