<x-app-layout>
    <div class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-8">
            <section class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-white/[0.06] p-8 shadow-2xl shadow-blue-950/30 backdrop-blur-xl">
                <div class="absolute right-[-70px] top-[-70px] h-64 w-64 rounded-full bg-cyan-400/10 blur-3xl"></div>
                <div class="absolute bottom-[-80px] left-[-80px] h-64 w-64 rounded-full bg-violet-500/10 blur-3xl"></div>

                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="inline-flex rounded-full border border-cyan-300/20 bg-cyan-400/10 px-4 py-2 text-sm font-semibold text-cyan-200">
                            Manajemen Kuis
                        </div>

                        <h1 class="mt-5 text-4xl font-extrabold tracking-tight text-white md:text-5xl">
                            Kuis Pembelajaran
                        </h1>

                        <p class="mt-4 max-w-3xl text-base leading-8 text-slate-300">
                            Buat dan kelola kuis untuk setiap kelas. Kuis baru disimpan sebagai draf agar soal dapat disusun terlebih dahulu sebelum dibuka untuk mahasiswa.
                        </p>
                    </div>

                    <a href="{{ route('dosen.kuis.create') }}"
                       class="inline-flex w-fit items-center justify-center rounded-2xl bg-cyan-400 px-6 py-3 text-sm font-black text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-300">
                        <span class="mr-2 text-lg">+</span>
                        Buat Kuis
                    </a>
                </div>
            </section>

            <section class="grid gap-5 md:grid-cols-3">
                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                    <p class="text-sm font-semibold text-slate-400">Total Kuis</p>
                    <p class="mt-3 text-4xl font-black text-white">{{ $quizSummary['total'] }}</p>
                    <p class="mt-2 text-xs text-cyan-200">Pada seluruh kelas Anda</p>
                </div>

                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                    <p class="text-sm font-semibold text-slate-400">Kuis Aktif</p>
                    <p class="mt-3 text-4xl font-black text-white">{{ $quizSummary['active'] }}</p>
                    <p class="mt-2 text-xs text-cyan-200">Dapat diakses mahasiswa</p>
                </div>

                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                    <p class="text-sm font-semibold text-slate-400">Draf Kuis</p>
                    <p class="mt-3 text-4xl font-black text-white">{{ $quizSummary['draft'] }}</p>
                    <p class="mt-2 text-xs text-cyan-200">Menunggu penyusunan soal</p>
                </div>
            </section>

            <section class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                <div class="flex flex-col gap-3 border-b border-white/10 pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-white">Daftar Kuis</h2>
                        <p class="mt-1 text-sm text-slate-400">
                            Kuis ditampilkan berdasarkan kelas yang Anda kelola.
                        </p>
                    </div>

                    <p class="text-sm font-semibold text-cyan-200">
                        {{ $classGroups->count() }} kelas tersedia
                    </p>
                </div>

                <div class="mt-6 grid gap-5 lg:grid-cols-2">
                    @forelse ($quizzes as $quiz)
                        <article class="rounded-2xl border border-white/10 bg-slate-950/40 p-5 transition hover:border-cyan-300/20">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap gap-2">
                                        <span class="rounded-full px-3 py-1 text-xs font-bold
                                            {{ $quiz->type === 'evaluasi_akhir' ? 'bg-violet-400/10 text-violet-200' : 'bg-cyan-400/10 text-cyan-200' }}">
                                            {{ $quiz->type === 'evaluasi_akhir' ? 'Evaluasi Akhir' : 'Kuis Bab' }}
                                        </span>

                                        <span class="rounded-full px-3 py-1 text-xs font-bold
                                            {{ $quiz->is_active ? 'bg-emerald-400/10 text-emerald-200' : 'bg-yellow-400/10 text-yellow-200' }}">
                                            {{ $quiz->is_active ? 'Aktif' : 'Draf' }}
                                        </span>
                                    </div>

                                    <h3 class="mt-4 text-lg font-bold text-white">
                                        {{ $quiz->title }}
                                    </h3>

                                    <p class="mt-2 text-sm text-slate-400">
                                        {{ $quiz->description ?: 'Belum ada deskripsi kuis.' }}
                                    </p>
                                </div>

                                <div class="rounded-xl border border-white/10 bg-white/[0.04] px-3 py-2 text-right">
                                    <p class="text-xs text-slate-400">Durasi</p>
                                    <p class="mt-1 font-bold text-white">{{ $quiz->duration_minutes }} menit</p>
                                </div>
                            </div>

                            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-xl border border-white/10 bg-white/[0.04] p-3">
                                    <p class="text-xs text-slate-400">Kelas</p>
                                    <p class="mt-1 truncate text-sm font-bold text-white">{{ $quiz->classGroup->name }}</p>
                                </div>

                                <div class="rounded-xl border border-white/10 bg-white/[0.04] p-3">
                                    <p class="text-xs text-slate-400">Materi</p>
                                    <p class="mt-1 truncate text-sm font-bold text-white">
                                        {{ $quiz->type === 'evaluasi_akhir' ? 'Seluruh Bab' : ($quiz->module?->title ?? '-') }}
                                    </p>
                                </div>

                                <div class="rounded-xl border border-white/10 bg-white/[0.04] p-3">
                                    <p class="text-xs text-slate-400">Soal</p>
                                    <p class="mt-1 text-sm font-bold text-white">{{ $quiz->questions_count }} soal</p>
                                </div>
                            </div>

                            <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-white/10 pt-4">
                                <p class="text-xs text-slate-400">
                                    {{ $quiz->attempts_count }} percobaan mahasiswa tercatat
                                </p>

                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-semibold {{ $quiz->questions_count > 0 ? 'text-cyan-200' : 'text-yellow-200' }}">
                                        {{ $quiz->questions_count > 0 ? 'Soal telah tersedia' : 'Belum ada soal' }}
                                    </span>

                                    <a href="{{ route('dosen.kuis.show', $quiz) }}"
                                       class="rounded-xl border border-cyan-300/20 bg-cyan-400/10 px-3 py-2 text-xs font-black text-cyan-100 transition hover:bg-cyan-400/20">
                                        Kelola Kuis →
                                    </a>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-white/15 bg-slate-950/30 p-8 text-center lg:col-span-2">
                            <p class="text-lg font-bold text-white">Belum ada kuis</p>
                            <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-400">
                                Buat kuis untuk kelas Anda. Setelah itu, lanjutkan dengan penyusunan soal sebelum kuis dibuka bagi mahasiswa.
                            </p>

                            <a href="{{ route('dosen.kuis.create') }}"
                               class="mt-5 inline-flex rounded-xl bg-cyan-400 px-5 py-3 text-sm font-bold text-slate-950 transition hover:bg-cyan-300">
                                Buat Kuis Pertama
                            </a>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>