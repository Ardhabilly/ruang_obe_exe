<x-app-layout>
    <div data-layout="minimal-quiz-detail" class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl space-y-5">
            @if (session('success'))
                <div class="rounded-2xl border border-green-300/20 bg-green-400/10 px-5 py-4 text-sm font-semibold text-green-100">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="rounded-2xl border border-yellow-300/20 bg-yellow-400/10 px-5 py-4 text-sm font-semibold text-yellow-100">
                    {{ session('warning') }}
                </div>
            @endif

            <section class="rounded-2xl border border-white/10 bg-white/[0.06] p-5 backdrop-blur-xl sm:p-6">
                <a href="{{ route('dosen.kelas.show', $quiz->classGroup) }}"
                   class="inline-flex items-center gap-2 text-sm font-bold text-cyan-200 transition hover:text-cyan-100">
                    ← Kembali ke Kelas
                </a>

                <div class="mt-5 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $quiz->type === 'evaluasi_akhir' ? 'bg-violet-400/10 text-violet-200' : 'bg-cyan-400/10 text-cyan-200' }}">
                                {{ $quiz->type === 'evaluasi_akhir' ? 'Evaluasi Akhir' : 'Kuis Bab' }}
                            </span>

                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $quiz->is_active ? 'bg-emerald-400/10 text-emerald-200' : 'bg-yellow-400/10 text-yellow-200' }}">
                                {{ $quiz->is_active ? 'Aktif' : 'Draf' }}
                            </span>
                        </div>

                        <h1 class="mt-3 text-2xl font-black tracking-tight text-white sm:text-3xl">
                            {{ $quiz->title }}
                        </h1>

                        @if ($quiz->description)
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-400">
                                {{ $quiz->description }}
                            </p>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('dosen.kuis.toggle-status', $quiz) }}" class="shrink-0">
                        @csrf
                        @method('PATCH')

                        <button type="submit"
                                class="inline-flex w-full items-center justify-center rounded-xl px-4 py-2.5 text-sm font-black transition sm:w-auto {{ $quiz->is_active ? 'bg-yellow-400 text-slate-950 hover:bg-yellow-300' : 'bg-emerald-400 text-slate-950 hover:bg-emerald-300' }}">
                            {{ $quiz->is_active ? 'Nonaktifkan Kuis' : 'Aktifkan Kuis' }}
                        </button>
                    </form>
                </div>
            </section>

            <section class="rounded-2xl border border-white/10 bg-white/[0.06] backdrop-blur-xl">
                <div class="grid divide-y divide-white/10 sm:grid-cols-2 sm:divide-x sm:divide-y-0 lg:grid-cols-5">
                    <div class="p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Kelas</p>
                        <p class="mt-2 truncate text-sm font-black text-white">{{ $quiz->classGroup->name }}</p>
                        <p class="mt-1 text-xs text-cyan-200">KKM {{ $quiz->classGroup->kkm }}</p>
                    </div>

                    <div class="p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Materi</p>
                        <p class="mt-2 text-sm font-black leading-5 text-white">
                            {{ $quiz->type === 'evaluasi_akhir' ? 'Seluruh Bab' : ($quiz->module?->title ?? '-') }}
                        </p>
                    </div>

                    <div class="p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Durasi</p>
                        <p class="mt-2 text-sm font-black text-white">{{ $quiz->duration_minutes }} menit</p>
                    </div>

                    <div class="p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Soal & Nilai</p>
                        <p class="mt-2 text-sm font-black text-white">{{ $quiz->questions->count() }} soal</p>
                        <p class="mt-1 text-xs text-slate-400">{{ $totalPoints }} poin</p>
                    </div>

                    <div class="p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Percobaan</p>
                        <p class="mt-2 text-sm font-black text-white">{{ $quiz->attempts_count }}</p>
                        <p class="mt-1 text-xs text-slate-400">tercatat</p>
                    </div>
                </div>
            </section>

            @if ($hasStartedAttempts)
                <section class="rounded-2xl border border-yellow-300/20 bg-yellow-400/10 px-5 py-4 text-sm leading-6 text-yellow-100">
                    Soal dikunci karena sudah terdapat percobaan mahasiswa. Penguncian ini menjaga jawaban, nilai, dan riwayat kuis tetap konsisten.
                </section>
            @endif

            <section class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.06] backdrop-blur-xl">
                <div class="flex flex-col gap-4 border-b border-white/10 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <h2 class="text-lg font-black text-white">Daftar Soal</h2>
                        <p class="mt-1 text-sm text-slate-400">
                            Susun isi kuis sebelum kuis diaktifkan dan dikerjakan mahasiswa.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <span class="w-fit rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-bold text-slate-300">
                            {{ $quiz->questions->count() }} soal · {{ $totalPoints }} poin
                        </span>

                        @if ($canManageQuestions)
                            <a href="{{ route('dosen.kuis.soal.create', $quiz) }}"
                               class="inline-flex items-center justify-center rounded-xl bg-cyan-400 px-3.5 py-2 text-xs font-black text-slate-950 transition hover:bg-cyan-300">
                                + Tambah Soal
                            </a>
                        @endif
                    </div>
                </div>

                @forelse ($quiz->questions as $question)
                    @php
                        $typeLabel = match ($question->question_type) {
                            'checkbox' => 'Pilihan lebih dari satu',
                            'short_text' => 'Isian singkat',
                            'math_notation' => 'Notasi matematika',
                            'variable_values' => 'Nilai variabel',
                            'matrix' => 'Matriks',
                            'augmented_matrix' => 'Matriks teraugmentasi',
                            'matrix_equation' => 'Persamaan matriks',
                            'obe_matrix_operation' => 'Operasi baris elementer',
                            'canvas_final_answer' => 'Langkah dan jawaban akhir',
                            'gauss_elimination' => 'Eliminasi Gauss',
                            'gauss_jordan' => 'Gauss-Jordan',
                            'multi_short_text' => 'Isian beberapa persamaan',
                            default => ucfirst(str_replace('_', ' ', $question->question_type)),
                        };

                        $isBasicType = in_array($question->question_type, [
                            'short_text',
                            'math_notation',
                            'variable_values',
                            'checkbox',
                        ], true);
                    @endphp

                    <article class="flex flex-col gap-3 border-b border-white/10 px-5 py-4 last:border-b-0 sm:px-6 md:flex-row md:items-start md:justify-between">
                        <div class="flex min-w-0 gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-400/10 text-xs font-black text-cyan-100">
                                {{ $question->order_number }}
                            </span>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-[11px] font-bold text-slate-300">
                                        {{ $typeLabel }}
                                    </span>

                                    @if (! $isBasicType)
                                        <span class="rounded-full border border-violet-300/15 bg-violet-400/[0.07] px-2.5 py-1 text-[11px] font-bold text-violet-200">
                                            Tipe lanjutan
                                        </span>
                                    @endif
                                </div>

                                <p class="mt-2 text-sm leading-6 text-slate-200">
                                    {{ $question->question_text }}
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-2 md:justify-end">
                            <span class="rounded-lg border border-cyan-300/20 bg-cyan-400/10 px-3 py-1.5 text-xs font-black text-cyan-100">
                                {{ $question->points }} poin
                            </span>

                            @if ($canManageQuestions && $isBasicType)
                                <a href="{{ route('dosen.kuis.soal.edit', [$quiz, $question]) }}"
                                   class="rounded-lg border border-white/10 bg-white/[0.05] px-3 py-1.5 text-xs font-black text-slate-200 transition hover:bg-white/10">
                                    Ubah
                                </a>
                            @endif

                            @if ($canManageQuestions)
                                <form method="POST"
                                      action="{{ route('dosen.kuis.soal.destroy', [$quiz, $question]) }}"
                                      onsubmit="return confirm('Hapus soal nomor {{ $question->order_number }}? Nomor soal setelahnya akan dirapikan.');">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="rounded-lg border border-red-300/20 bg-red-400/10 px-3 py-1.5 text-xs font-black text-red-200 transition hover:bg-red-400/20">
                                        Hapus
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="px-6 py-10 text-center">
                        <p class="text-base font-black text-white">Belum ada soal</p>
                        <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-400">
                            Tambahkan soal untuk mulai menyusun kuis ini.
                        </p>

                        @if ($canManageQuestions)
                            <a href="{{ route('dosen.kuis.soal.create', $quiz) }}"
                               class="mt-5 inline-flex rounded-xl bg-cyan-400 px-4 py-2.5 text-sm font-black text-slate-950 transition hover:bg-cyan-300">
                                + Tambah Soal Pertama
                            </a>
                        @endif
                    </div>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>