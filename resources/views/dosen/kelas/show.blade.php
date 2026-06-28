<x-app-layout>
    <div class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @if (session('success'))
                <div class="rounded-2xl border border-green-300/20 bg-green-400/10 p-4 text-sm font-semibold text-green-200">
                    {{ session('success') }}
                </div>
            @endif

            <section class="rounded-[2rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl sm:p-8">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-cyan-200">
                            Detail Kelas
                        </p>

                        <h1 class="mt-2 text-3xl font-black text-white">
                            {{ $classGroup->name }}
                        </h1>

                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-400">
                            {{ $classGroup->description ?: 'Tidak ada deskripsi.' }}
                        </p>
                    </div>

                    <a
                        href="{{ route('dosen.kelas.index') }}"
                        class="rounded-2xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10">
                        Kembali
                    </a>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-5">
                        <p class="text-sm text-slate-400">
                            Token Kelas
                        </p>

                        <p class="mt-2 font-mono text-3xl font-black tracking-widest text-white">
                            {{ $classGroup->token }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-5">
                        <p class="text-sm text-slate-400">
                            KKM
                        </p>

                        <p class="mt-2 text-3xl font-black text-white">
                            {{ $classGroup->kkm }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-5">
                        <p class="text-sm text-slate-400">
                            Jumlah Mahasiswa
                        </p>

                        <p class="mt-2 text-3xl font-black text-white">
                            {{ $classGroup->members->count() }}
                        </p>
                    </div>
                </div>
            </section>

            <section data-section="kuis-kelas" class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                <div class="flex flex-col gap-4 border-b border-white/10 pb-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-cyan-200">Manajemen Kuis Kelas</p>
                        <h2 class="mt-1 text-xl font-black text-white">Kuis {{ $classGroup->name }}</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-400">
                            Kuis pada bagian ini hanya berlaku untuk kelas ini. Kelola kuis tanpa tercampur dengan kelas lain.
                        </p>
                    </div>

                    <a href="{{ route('dosen.kuis.create', ['class_group_id' => $classGroup->id]) }}"
                       class="inline-flex w-fit items-center justify-center gap-2 rounded-xl bg-cyan-400 px-4 py-3 text-sm font-black text-slate-950 transition hover:bg-cyan-300">
                        <span class="text-base">+</span>
                        Buat Kuis untuk Kelas Ini
                    </a>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-4">
                    <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                        <p class="text-xs text-slate-400">Total Kuis</p>
                        <p class="mt-2 text-2xl font-black text-white">{{ $classQuizSummary['total'] }}</p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                        <p class="text-xs text-slate-400">Kuis Aktif</p>
                        <p class="mt-2 text-2xl font-black text-green-300">{{ $classQuizSummary['active'] }}</p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                        <p class="text-xs text-slate-400">Draf Kuis</p>
                        <p class="mt-2 text-2xl font-black text-yellow-200">{{ $classQuizSummary['draft'] }}</p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                        <p class="text-xs text-slate-400">Evaluasi Akhir</p>
                        <p class="mt-2 text-2xl font-black text-violet-200">{{ $classQuizSummary['final'] }}</p>
                    </div>
                </div>

                @php
                    $chapterQuizzes = $classQuizzes->where('type', 'kuis_bab');
                    $finalEvaluations = $classQuizzes->where('type', 'evaluasi_akhir');
                @endphp

                <div class="mt-6">
                    <div class="flex items-center gap-3">
                        <h3 class="text-sm font-black uppercase tracking-[0.14em] text-slate-300">Kuis Bab</h3>
                        <span class="h-px flex-1 bg-white/10"></span>
                    </div>

                    <div class="mt-3 grid gap-3 lg:grid-cols-2">
                        @forelse ($chapterQuizzes as $quiz)
                            <article class="rounded-2xl border border-white/10 bg-slate-950/35 p-4 transition hover:border-cyan-300/20">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-cyan-400/10 px-2.5 py-1 text-[11px] font-black text-cyan-100">
                                                Kuis Bab {{ $quiz->module?->order_number ?? '-' }}
                                            </span>
                                            <span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $quiz->is_active ? 'bg-green-400/10 text-green-200' : 'bg-yellow-400/10 text-yellow-200' }}">
                                                {{ $quiz->is_active ? 'Aktif' : 'Draf' }}
                                            </span>
                                        </div>

                                        <h4 class="mt-3 truncate text-base font-black text-white">{{ $quiz->title }}</h4>
                                        <p class="mt-1 truncate text-sm text-slate-400">{{ $quiz->module?->title ?? 'Materi belum dipilih' }}</p>
                                    </div>

                                    <a href="{{ route('dosen.kuis.show', $quiz) }}"
                                       class="shrink-0 rounded-xl border border-cyan-300/20 bg-cyan-400/10 px-3 py-2 text-xs font-black text-cyan-100 transition hover:bg-cyan-400/20">
                                        Kelola
                                    </a>
                                </div>

                                <div class="mt-4 grid grid-cols-3 gap-2 border-t border-white/10 pt-3 text-center">
                                    <div>
                                        <p class="text-[11px] text-slate-500">Durasi</p>
                                        <p class="mt-1 text-sm font-black text-white">{{ $quiz->duration_minutes }} mnt</p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] text-slate-500">Soal</p>
                                        <p class="mt-1 text-sm font-black text-white">{{ $quiz->questions_count }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] text-slate-500">Percobaan</p>
                                        <p class="mt-1 text-sm font-black text-white">{{ $quiz->attempts_count }}</p>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-2xl border border-dashed border-white/15 bg-slate-950/25 p-5 text-sm text-slate-400 lg:col-span-2">
                                Belum ada kuis bab untuk kelas ini.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="mt-6">
                    <div class="flex items-center gap-3">
                        <h3 class="text-sm font-black uppercase tracking-[0.14em] text-slate-300">Evaluasi Akhir</h3>
                        <span class="h-px flex-1 bg-white/10"></span>
                    </div>

                    <div class="mt-3 grid gap-3 lg:grid-cols-2">
                        @forelse ($finalEvaluations as $quiz)
                            <article class="rounded-2xl border border-violet-300/15 bg-violet-400/[0.05] p-4 transition hover:border-violet-300/30">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-violet-400/10 px-2.5 py-1 text-[11px] font-black text-violet-200">
                                                Evaluasi Akhir
                                            </span>
                                            <span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $quiz->is_active ? 'bg-green-400/10 text-green-200' : 'bg-yellow-400/10 text-yellow-200' }}">
                                                {{ $quiz->is_active ? 'Aktif' : 'Draf' }}
                                            </span>
                                        </div>

                                        <h4 class="mt-3 truncate text-base font-black text-white">{{ $quiz->title }}</h4>
                                        <p class="mt-1 text-sm text-slate-400">Mencakup seluruh materi Bab 1 sampai Bab 4.</p>
                                    </div>

                                    <a href="{{ route('dosen.kuis.show', $quiz) }}"
                                       class="shrink-0 rounded-xl border border-violet-300/20 bg-violet-400/10 px-3 py-2 text-xs font-black text-violet-100 transition hover:bg-violet-400/20">
                                        Kelola
                                    </a>
                                </div>

                                <div class="mt-4 grid grid-cols-3 gap-2 border-t border-violet-300/10 pt-3 text-center">
                                    <div>
                                        <p class="text-[11px] text-slate-500">Durasi</p>
                                        <p class="mt-1 text-sm font-black text-white">{{ $quiz->duration_minutes }} mnt</p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] text-slate-500">Soal</p>
                                        <p class="mt-1 text-sm font-black text-white">{{ $quiz->questions_count }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] text-slate-500">Percobaan</p>
                                        <p class="mt-1 text-sm font-black text-white">{{ $quiz->attempts_count }}</p>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-2xl border border-dashed border-violet-300/15 bg-violet-400/[0.03] p-5 text-sm text-slate-400 lg:col-span-2">
                                Evaluasi akhir belum dibuat untuk kelas ini.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
            <section class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h2 class="text-xl font-black text-white">
                            Hasil Kuis Mahasiswa
                        </h2>

                        <p class="mt-2 text-sm leading-6 text-slate-400">
                            Setiap mahasiswa ditampilkan satu kali. Pilih riwayat untuk melihat kuis dan percobaan yang telah dikerjakan.
                        </p>
                    </div>

                    <span class="rounded-xl border border-cyan-300/20 bg-cyan-400/10 px-3 py-2 text-xs font-bold text-cyan-100">
                        {{ $quizSummary['students_with_attempts'] }}/{{ $classGroup->members->count() }} mahasiswa sudah mengerjakan
                    </span>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                        <p class="text-xs text-slate-400">
                            Mahasiswa Mengerjakan
                        </p>

                        <p class="mt-2 text-2xl font-black text-cyan-200">
                            {{ $quizSummary['students_with_attempts'] }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                        <p class="text-xs text-slate-400">
                            Kuis Dikerjakan
                        </p>

                        <p class="mt-2 text-2xl font-black text-white">
                            {{ $quizSummary['completed_quizzes'] }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                        <p class="text-xs text-slate-400">
                            Kuis Lulus
                        </p>

                        <p class="mt-2 text-2xl font-black text-green-300">
                            {{ $quizSummary['passed_quizzes'] }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                        <p class="text-xs text-slate-400">
                            Perlu Remedial
                        </p>

                        <p class="mt-2 text-2xl font-black text-yellow-200">
                            {{ $quizSummary['needs_remedial'] }}
                        </p>
                    </div>
                </div>

                <div class="mt-5 overflow-x-auto rounded-2xl border border-white/10">
                    <table class="min-w-[1050px] w-full divide-y divide-white/10">
                        <thead class="bg-slate-950/50">
                            <tr>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-400">
                                    Mahasiswa
                                </th>
                                <th class="px-5 py-4 text-center text-xs font-bold uppercase tracking-wide text-slate-400">
                                    Kuis Dikerjakan
                                </th>
                                <th class="px-5 py-4 text-center text-xs font-bold uppercase tracking-wide text-slate-400">
                                    Kuis Lulus
                                </th>
                                <th class="px-5 py-4 text-center text-xs font-bold uppercase tracking-wide text-slate-400">
                                    Status Terbaru
                                </th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-400">
                                    Aktivitas Terakhir
                                </th>
                                <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-wide text-slate-400">
                                    Aksi
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-white/10">
                            @forelse ($studentSummaries as $summary)
                                @php
                                    $student = $summary['student'];
                                    $lastActivity = $summary['last_activity'];

                                    $hasNoAttempt = $summary['attempt_count'] === 0;
                                    $hasRemedial = $summary['needs_remedial_count'] > 0;
                                    $hasUnpassedQuiz = $summary['unpassed_quiz_count'] > 0;
                                    $allCompletedQuizzesPassed = $summary['completed_quiz_count'] > 0
                                        && $summary['completed_quiz_count'] === $summary['passed_quiz_count'];
                                @endphp

                                <tr class="bg-white/[0.02] transition hover:bg-white/[0.05]">
                                    <td class="px-5 py-4">
                                        <p class="text-sm font-bold text-white">
                                            {{ $student->name }}
                                        </p>

                                        <p class="mt-1 text-xs text-slate-400">
                                            {{ $student->email }}
                                        </p>
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        <p class="text-lg font-black text-white">
                                            {{ $summary['completed_quiz_count'] }}
                                        </p>

                                        <p class="mt-1 text-xs text-slate-400">
                                            {{ $summary['attempt_count'] }} percobaan
                                        </p>
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        <p class="text-lg font-black text-green-300">
                                            {{ $summary['passed_quiz_count'] }}
                                        </p>

                                        <p class="mt-1 text-xs text-slate-400">
                                            dari {{ $summary['completed_quiz_count'] }} kuis
                                        </p>
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        @if ($hasNoAttempt)
                                            <span class="inline-flex rounded-full border border-slate-300/20 bg-slate-400/10 px-3 py-1 text-xs font-black text-slate-200">
                                                Belum Mengerjakan
                                            </span>
                                        @elseif ($hasRemedial)
                                            <span class="inline-flex rounded-full border border-yellow-300/20 bg-yellow-400/10 px-3 py-1 text-xs font-black text-yellow-200">
                                                {{ $summary['needs_remedial_count'] }} Perlu Remedial
                                            </span>
                                        @elseif ($hasUnpassedQuiz)
                                            <span class="inline-flex rounded-full border border-red-300/20 bg-red-400/10 px-3 py-1 text-xs font-black text-red-200">
                                                Belum Lulus
                                            </span>
                                        @elseif ($allCompletedQuizzesPassed)
                                            <span class="inline-flex rounded-full border border-green-300/20 bg-green-400/10 px-3 py-1 text-xs font-black text-green-200">
                                                Semua Kuis Lulus
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full border border-cyan-300/20 bg-cyan-400/10 px-3 py-1 text-xs font-black text-cyan-100">
                                                Sedang Berproses
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4">
                                        @if ($lastActivity)
                                            <p class="text-sm font-semibold text-slate-200">
                                                {{ $lastActivity->quiz?->title ?? 'Kuis' }}
                                            </p>

                                            <p class="mt-1 text-xs text-slate-400">
                                                {{ $lastActivity->submitted_at?->format('d M Y · H:i') ?? '-' }}
                                            </p>
                                        @else
                                            <p class="text-sm text-slate-400">
                                                Belum ada aktivitas kuis.
                                            </p>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-right">
                                        @if ($summary['attempt_count'] > 0)
                                            <a
                                                href="{{ route('dosen.kelas.mahasiswa.riwayat', ['classGroup' => $classGroup, 'student' => $student]) }}"
                                                class="inline-flex rounded-xl border border-cyan-300/20 bg-cyan-400/10 px-3 py-2 text-xs font-black text-cyan-100 transition hover:bg-cyan-400/20">
                                                Lihat Riwayat
                                            </a>
                                        @else
                                            <span class="inline-flex rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-bold text-slate-500">
                                                Belum Ada Riwayat
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">
                                        Belum ada mahasiswa yang tergabung pada kelas ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                <h2 class="text-xl font-bold text-white">
                    Mahasiswa Tergabung
                </h2>

                <div class="mt-5 overflow-x-auto rounded-2xl border border-white/10">
                    <table class="min-w-[680px] w-full divide-y divide-white/10">
                        <thead class="bg-slate-950/50">
                            <tr>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-400">
                                    Nama
                                </th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-400">
                                    Email
                                </th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-400">
                                    Bergabung
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-white/10">
                            @forelse ($classGroup->members as $member)
                                <tr>
                                    <td class="px-5 py-4 text-sm font-semibold text-white">
                                        {{ $member->user?->name ?? '-' }}
                                    </td>

                                    <td class="px-5 py-4 text-sm text-slate-400">
                                        {{ $member->user?->email ?? '-' }}
                                    </td>

                                    <td class="px-5 py-4 text-sm text-slate-400">
                                        {{ $member->joined_at?->format('d M Y H:i') ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-5 py-6 text-center text-sm text-slate-400">
                                        Belum ada mahasiswa yang bergabung.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
