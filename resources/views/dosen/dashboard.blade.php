<x-app-layout>
    <div class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-8">
            <section class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-white/[0.06] p-8 shadow-2xl shadow-blue-950/30 backdrop-blur-xl">
                <div class="absolute right-[-80px] top-[-80px] h-72 w-72 rounded-full bg-cyan-400/10 blur-3xl"></div>
                <div class="absolute bottom-[-80px] left-[-80px] h-72 w-72 rounded-full bg-violet-500/10 blur-3xl"></div>

                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="inline-flex rounded-full border border-cyan-300/20 bg-cyan-400/10 px-4 py-2 text-sm font-semibold text-cyan-200">
                            Dashboard Dosen
                        </div>

                        <h1 class="mt-6 max-w-4xl text-4xl font-extrabold tracking-tight text-white md:text-5xl">
                            Monitoring Pembelajaran
                            <span class="bg-gradient-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                                RuangOBE
                            </span>
                        </h1>

                        <p class="mt-5 max-w-3xl text-base leading-8 text-slate-300">
                            Pantau kelas, aktivitas pembelajaran, dan keterlibatan mahasiswa
                            melalui dashboard RuangOBE.
                        </p>
                    </div>

                    <a href="{{ route('dosen.kelas.index') }}"
                       class="w-fit rounded-2xl bg-cyan-400 px-6 py-3 text-sm font-black text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-300">
                        Kelola Kelas
                    </a>
                </div>
            </section>

            <section class="grid w-full grid-cols-1 gap-5 md:grid-cols-2">
                <div class="flex min-h-[172px] flex-col justify-between rounded-[1.5rem] border border-white/10 bg-gradient-to-br from-white/[0.09] to-white/[0.04] p-6 shadow-lg shadow-slate-950/10 backdrop-blur-xl">
                    <p class="text-sm font-semibold text-slate-400">Total Mahasiswa</p>
                    <p class="mt-4 text-4xl font-black text-white">{{ $totalMahasiswa }}</p>
                    <p class="mt-2 text-xs text-cyan-200">Tergabung dalam kelas</p>
                </div>

                <div class="flex min-h-[172px] flex-col justify-between rounded-[1.5rem] border border-white/10 bg-gradient-to-br from-white/[0.09] to-white/[0.04] p-6 shadow-lg shadow-slate-950/10 backdrop-blur-xl">
                    <p class="text-sm font-semibold text-slate-400">Kelas Aktif</p>
                    <p class="mt-4 text-4xl font-black text-white">{{ $activeClasses }}</p>
                    <p class="mt-2 text-xs text-cyan-200">Dari {{ $totalClasses }} kelas</p>
                </div>


            </section>

            <section class="grid gap-5 lg:grid-cols-[1fr_0.9fr]">
                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-bold text-white">
                            Kelas Terbaru
                        </h2>

                        <a href="{{ route('dosen.kelas.index') }}"
                           class="text-sm font-bold text-cyan-200 hover:text-cyan-100">
                            Lihat semua →
                        </a>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($classGroups->take(4) as $classGroup)
                            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-bold text-white">{{ $classGroup->name }}</p>
                                        <p class="mt-1 text-sm text-slate-400">
                                            {{ $classGroup->members_count }} mahasiswa · KKM {{ $classGroup->kkm }}
                                        </p>
                                    </div>

                                    <span class="rounded-full px-3 py-1 text-xs font-bold
                                        {{ $classGroup->is_active ? 'bg-green-400/10 text-green-200' : 'bg-red-400/10 text-red-200' }}">
                                        {{ $classGroup->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>

                                <p class="mt-3 font-mono text-sm font-bold tracking-widest text-cyan-200">
                                    {{ $classGroup->token }}
                                </p>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-yellow-300/20 bg-yellow-400/10 p-5 text-sm text-yellow-200">
                                Belum ada kelas. Buat kelas terlebih dahulu agar mahasiswa dapat bergabung.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                    <h2 class="text-lg font-bold text-white">
                        Mahasiswa Teratas
                    </h2>

                    <div class="mt-5 space-y-3">
                        @forelse ($topMahasiswa as $student)
                            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="font-bold text-white">{{ $student->name }}</p>
                                        <p class="mt-1 text-sm text-slate-400">
                                            {{ $student->email }}
                                        </p>
                                    </div>

                                    <p class="text-xl font-black text-cyan-200">
                                        {{ $student->progress_percentage }}%
                                    </p>
                                </div>

                                <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/10">
                                    <div class="h-full rounded-full bg-gradient-to-r from-cyan-300 to-blue-500"
                                         style="width: {{ $student->progress_percentage }}%"></div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-5 text-sm text-slate-400">
                                Belum ada mahasiswa yang dapat ditampilkan.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="grid gap-5 lg:grid-cols-2">
                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                    <h2 class="text-lg font-bold text-white">
                        Aktivitas Terbaru
                    </h2>

                    <div class="mt-5 space-y-3">
                        @forelse ($latestPracticeSubmissions as $submission)
                            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                                <p class="text-sm font-bold text-cyan-200">
                                    {{ $submission->user->name }}
                                </p>

                                <p class="mt-1 font-semibold text-white">
                                    {{ $submission->title }}
                                </p>

                                <p class="mt-1 text-sm text-slate-400">
                                    @if ((int) $submission->max_score > 0)
                                        Nilai {{ $submission->score }}/{{ $submission->max_score }}
                                    @else
                                        Komponen pembelajaran disimpan
                                    @endif
                                    · {{ $submission->submitted_at?->format('d M Y H:i') }}
                                </p>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-5 text-sm text-slate-400">
                                Belum ada aktivitas yang tercatat.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                    <h2 class="text-lg font-bold text-white">
                        Progres Materi Terbaru
                    </h2>

                    <div class="mt-5 space-y-3">
                        @forelse ($latestProgress as $progress)
                            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                                <p class="text-sm font-bold text-cyan-200">
                                    {{ $progress->user->name }}
                                </p>

                                <p class="mt-1 font-semibold text-white">
                                    {{ $progress->lesson->title }}
                                </p>

                                <p class="mt-1 text-sm text-slate-400">
                                    {{ $progress->completed ? 'Selesai' : 'Sedang dipelajari' }}
                                    · {{ $progress->updated_at?->format('d M Y H:i') }}
                                </p>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-5 text-sm text-slate-400">
                                Belum ada progres materi yang tercatat.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>