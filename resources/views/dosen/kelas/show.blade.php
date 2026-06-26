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
