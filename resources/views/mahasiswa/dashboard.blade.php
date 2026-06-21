<x-app-layout>
    <div class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-8">
            <section class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-white/[0.06] p-8 shadow-2xl shadow-cyan-950/30 backdrop-blur-xl">
                <div class="absolute right-0 top-0 h-64 w-64 rounded-full bg-cyan-400/10 blur-3xl"></div>
                <div class="absolute bottom-0 left-0 h-64 w-64 rounded-full bg-blue-500/10 blur-3xl"></div>

                <div class="relative grid gap-8 lg:grid-cols-[1.3fr_0.7fr] lg:items-center">
                    <div>
                        <div class="inline-flex rounded-full border border-cyan-300/20 bg-cyan-400/10 px-4 py-2 text-sm font-semibold text-cyan-200">
                            Dashboard Mahasiswa
                        </div>

                        <h1 class="mt-6 max-w-3xl text-4xl font-extrabold tracking-tight text-white md:text-5xl">
                            Selamat datang,
                            <span class="bg-gradient-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                                {{ $user->name }}
                            </span>
                        </h1>

                        <p class="mt-5 max-w-2xl text-base leading-8 text-slate-300">
                            Lanjutkan pembelajaran Sistem Persamaan Linear, Operasi Baris Elementer,
                            Eliminasi Gauss, dan Gauss-Jordan secara bertahap melalui RuangOBE.
                        </p>

                        <div class="mt-8 flex flex-wrap gap-3">
                            @if ($nextLesson)
                                <a href="{{ route('mahasiswa.materi.show', $nextLesson->slug) }}"
                                   class="rounded-2xl bg-cyan-400 px-6 py-3 text-sm font-bold text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-300">
                                    Lanjut Belajar
                                </a>
                            @else
                                <a href="{{ route('mahasiswa.materi.index') }}"
                                   class="rounded-2xl bg-cyan-400 px-6 py-3 text-sm font-bold text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-300">
                                    Lihat Materi
                                </a>
                            @endif

                            <a href="{{ route('mahasiswa.kelas.index') }}"
                               class="rounded-2xl border border-white/10 bg-white/5 px-6 py-3 text-sm font-bold text-white transition hover:bg-white/10">
                                Kelas Saya
                            </a>
                        </div>
                    </div>

                    <div class="rounded-[2rem] border border-white/10 bg-slate-950/40 p-6">
                        <p class="text-sm font-semibold text-slate-400">
                            Progres Belajar
                        </p>

                        <div class="mt-4 flex items-end gap-2">
                            <span class="text-5xl font-black text-white">{{ $progressPercentage }}</span>
                            <span class="pb-2 text-sm font-semibold text-slate-400">%</span>
                        </div>

                        <div class="mt-5 h-3 overflow-hidden rounded-full bg-white/10">
                            <div class="h-full rounded-full bg-gradient-to-r from-cyan-300 to-blue-500"
                                 style="width: {{ $progressPercentage }}%"></div>
                        </div>

                        <p class="mt-4 text-sm text-slate-400">
                            {{ $completedLessons }} dari {{ $totalLessons }} materi selesai.
                        </p>

                        <div class="mt-6 grid grid-cols-2 gap-3">
                            <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                                <p class="text-xs text-slate-400">Nilai latihan</p>
                                <p class="mt-2 text-2xl font-black text-white">
                                    {{ $averagePracticeScore !== null ? $averagePracticeScore : '-' }}
                                </p>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                                <p class="text-xs text-slate-400">Durasi belajar</p>
                                <p class="mt-2 text-2xl font-black text-white">
                                    {{ $durationMinutes }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-5 md:grid-cols-4">
                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                    <p class="text-sm font-semibold text-slate-400">Kelas Diikuti</p>
                    <p class="mt-4 text-4xl font-black text-white">{{ $joinedClasses->count() }}</p>
                    <p class="mt-2 text-xs text-cyan-200">Kelas aktif</p>
                </div>

                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                    <p class="text-sm font-semibold text-slate-400">Materi Selesai</p>
                    <p class="mt-4 text-4xl font-black text-white">{{ $completedLessons }}</p>
                    <p class="mt-2 text-xs text-cyan-200">Dari {{ $totalLessons }} materi</p>
                </div>

                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                    <p class="text-sm font-semibold text-slate-400">Latihan Dikerjakan</p>
                    <p class="mt-4 text-4xl font-black text-white">{{ $practiceCount }}</p>
                    <p class="mt-2 text-xs text-cyan-200">Aktivitas tersimpan</p>
                </div>

                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                    <p class="text-sm font-semibold text-slate-400">Nilai Kuis</p>
                    <p class="mt-4 text-4xl font-black text-white">-</p>
                    <p class="mt-2 text-xs text-cyan-200">Belum tersedia</p>
                </div>
            </section>

            <section class="grid gap-5 lg:grid-cols-[0.9fr_1.1fr]">
                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-bold text-white">
                            Kelas Saya
                        </h2>

                        <a href="{{ route('mahasiswa.kelas.index') }}"
                           class="text-sm font-bold text-cyan-200 hover:text-cyan-100">
                            Kelola →
                        </a>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($joinedClasses as $classGroup)
                            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-bold text-white">{{ $classGroup->name }}</p>
                                        <p class="mt-1 text-sm text-slate-400">
                                            Dosen: {{ $classGroup->dosen->name }}
                                        </p>
                                    </div>

                                    <div class="rounded-xl border border-cyan-300/20 bg-cyan-400/10 px-3 py-2 text-center">
                                        <p class="text-xs text-cyan-200">KKM</p>
                                        <p class="text-lg font-black text-white">{{ $classGroup->kkm }}</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-yellow-300/20 bg-yellow-400/10 p-5 text-sm text-yellow-200">
                                Anda belum tergabung dalam kelas. Masukkan token dari dosen untuk mengakses kuis.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                    <h2 class="text-lg font-bold text-white">
                        Aktivitas Terbaru
                    </h2>

                    <div class="mt-5 space-y-3">
                        @if ($latestPractice)
                            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-5">
                                <p class="text-sm font-bold text-cyan-200">
                                    Latihan terakhir
                                </p>

                                <h3 class="mt-2 font-bold text-white">
                                    {{ $latestPractice->title }}
                                </h3>

                                <p class="mt-2 text-sm text-slate-400">
                                    Nilai: {{ $latestPractice->score }}/{{ $latestPractice->max_score }}
                                </p>

                                <p class="mt-1 text-sm text-slate-500">
                                    {{ $latestPractice->submitted_at?->format('d M Y H:i') }}
                                </p>
                            </div>
                        @endif

                        @if ($latestProgress)
                            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-5">
                                <p class="text-sm font-bold text-cyan-200">
                                    Progress materi terakhir
                                </p>

                                <h3 class="mt-2 font-bold text-white">
                                    {{ $latestProgress->lesson->title }}
                                </h3>

                                <p class="mt-2 text-sm text-slate-400">
                                    {{ $latestProgress->completed ? 'Materi sudah selesai.' : 'Materi sedang dipelajari.' }}
                                </p>
                            </div>
                        @endif

                        @if (! $latestPractice && ! $latestProgress)
                            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-5 text-sm text-slate-400">
                                Belum ada aktivitas belajar yang tercatat.
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>