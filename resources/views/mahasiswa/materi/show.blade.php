{{-- MATERI_SHOW_TANPA_SIDEBAR_LOKAL_FLOWBITE_V1 --}}
<x-app-layout>
    @php
        $contentView = match ($lesson->slug) {
            'pengertian-sistem-persamaan-linear' => 'mahasiswa.materi.bab1.subbab1',
            'bentuk-umum-sistem-persamaan-linear' => 'mahasiswa.materi.bab1.subbab2',
            'kemungkinan-solusi-sistem-persamaan-linear' => 'mahasiswa.materi.bab1.subbab3',
            'metode-penyelesaian-spl-menuju-representasi-matriks' => 'mahasiswa.materi.bab1.subbab4',

            'pengertian-operasi-baris-elementer' => 'mahasiswa.materi.bab2.subbab1',
            'jenis-jenis-operasi-baris-elementer' => 'mahasiswa.materi.bab2.subbab2',

            'algoritma-syarat-matriks-eselon-baris' => 'mahasiswa.materi.bab3.subbab1',
            'simulasi-mengubah-matriks-menjadi-eselon-baris' => 'mahasiswa.materi.bab3.subbab2',
            'menyelesaikan-spl-dengan-metode-eliminasi-gauss' => 'mahasiswa.materi.bab3.subbab3',

            'algoritma-syarat-matriks-eselon-baris-tereduksi' => 'mahasiswa.materi.bab4.subbab1',
            'simulasi-mengubah-matriks-menjadi-eselon-baris-tereduksi' => 'mahasiswa.materi.bab4.subbab2',
            'menyelesaikan-spl-dengan-metode-eliminasi-gauss-jordan' => 'mahasiswa.materi.bab4.subbab3',

            default => null,
        };

        $canCompleteLesson = empty($requiredPractices)
            || empty(array_diff(array_keys($requiredPractices), $completedPracticeKeys ?? []));
    @endphp

    {{-- Sidebar hanya berasal dari resources/views/layouts/navigation.blade.php --}}
    <main class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="space-y-6">
            @if (session('success'))
                <div class="rounded-2xl border border-green-300/20 bg-green-400/10 px-5 py-4 text-sm font-semibold leading-6 text-green-100">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="rounded-2xl border border-yellow-300/20 bg-yellow-400/10 px-5 py-4 text-sm font-semibold leading-6 text-yellow-100">
                    {{ session('warning') }}
                </div>
            @endif

            <section class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl sm:p-7">
                <a href="{{ route('mahasiswa.materi.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-cyan-200 transition hover:text-cyan-100">
                    <svg class="h-4 w-4" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7" />
                    </svg>

                    Daftar Materi
                </a>

                <div class="mt-5 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-cyan-200">
                            {{ $lesson->module->title }}
                        </p>

                        <h1 class="mt-2 text-2xl font-black tracking-tight text-white sm:text-3xl">
                            {{ $lesson->title }}
                        </h1>
                    </div>

                    <span class="w-fit shrink-0 rounded-full px-4 py-2 text-sm font-bold {{ $progress->completed ? 'bg-green-400/10 text-green-200' : 'bg-yellow-400/10 text-yellow-200' }}">
                        {{ $progress->completed ? 'Selesai' : 'Belum selesai' }}
                    </span>
                </div>
            </section>

            <article class="rounded-[1.5rem] border border-white/10 bg-white/[0.96] p-5 text-slate-800 shadow-2xl shadow-slate-950/20 sm:p-7">
                <div class="space-y-6 leading-8">
                    @if ($contentView && view()->exists($contentView))
                        @include($contentView)
                    @else
                        <div class="rounded-2xl border border-yellow-200 bg-yellow-50 p-5 text-yellow-800">
                            Konten materi untuk subbab ini belum tersedia.
                        </div>
                    @endif
                </div>
            </article>

            <section class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                    <div class="min-w-0">
                        <h2 class="font-bold text-white">
                            Penyelesaian Materi
                        </h2>

                        <p class="mt-1 text-sm leading-6 text-slate-400">
                            Selesaikan aktivitas wajib dengan benar sebelum menandai materi selesai.
                        </p>

                        @if (! empty($requiredPractices))
                            <div class="mt-4 rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                                <p class="text-sm font-bold text-white">
                                    Syarat menyelesaikan materi:
                                </p>

                                <ul class="mt-3 space-y-2">
                                    @foreach ($requiredPractices as $key => $title)
                                        @php
                                            $isPracticeDone = in_array($key, $completedPracticeKeys ?? [], true);
                                        @endphp

                                        <li class="flex items-start gap-2 text-sm">
                                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-xs font-black {{ $isPracticeDone ? 'bg-green-400/10 text-green-200' : 'bg-yellow-400/10 text-yellow-200' }}">
                                                {{ $isPracticeDone ? '✓' : '!' }}
                                            </span>

                                            <span class="{{ $isPracticeDone ? 'text-green-200' : 'text-yellow-200' }}">
                                                {{ $title }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <div class="shrink-0">
                        @if ($progress->completed)
                            <span class="inline-flex w-fit items-center gap-2 rounded-2xl bg-green-400/15 px-5 py-3 text-sm font-bold text-green-200">
                                <span>✓</span>
                                Materi Selesai
                            </span>
                        @else
                            <form action="{{ route('mahasiswa.materi.complete', $lesson->slug) }}" method="POST">
                                @csrf

                                <button type="submit" @disabled(! $canCompleteLesson) class="rounded-2xl px-5 py-3 text-sm font-bold transition {{ $canCompleteLesson ? 'bg-cyan-400 text-slate-950 hover:bg-cyan-300' : 'cursor-not-allowed bg-slate-700 text-slate-300' }}">
                                    {{ $canCompleteLesson ? 'Tandai Selesai' : 'Selesaikan Aktivitas Dahulu' }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </section>

            <div class="flex flex-col gap-3 border-t border-white/10 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    @if ($previousLesson)
                        <a href="{{ route('mahasiswa.materi.show', $previousLesson->slug) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10 sm:w-auto">
                            ← Materi Sebelumnya
                        </a>
                    @endif
                </div>

                <div>
                    @if ($nextLesson)
                        @if (in_array($nextLesson->id, $accessibleLessonIds ?? [], true))
                            <a href="{{ route('mahasiswa.materi.show', $nextLesson->slug) }}" class="inline-flex w-full items-center justify-center rounded-2xl bg-cyan-400 px-5 py-3 text-sm font-bold text-slate-950 transition hover:bg-cyan-300 sm:w-auto">
                                Materi Selanjutnya →
                            </a>
                        @else
                            <button type="button" class="inline-flex w-full cursor-not-allowed items-center justify-center rounded-2xl bg-slate-700 px-5 py-3 text-sm font-bold text-slate-300 sm:w-auto" disabled>
                                🔒 Materi Berikutnya Terkunci
                            </button>
                        @endif
                    @else
                        <a href="{{ route('mahasiswa.materi.index') }}" class="inline-flex w-full items-center justify-center rounded-2xl bg-cyan-400 px-5 py-3 text-sm font-bold text-slate-950 transition hover:bg-cyan-300 sm:w-auto">
                            Kembali ke Daftar Materi
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </main>
</x-app-layout>
