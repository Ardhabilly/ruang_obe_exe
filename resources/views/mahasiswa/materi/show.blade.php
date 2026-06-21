<x-app-layout>
    <div 
        x-data="{
            sidebarOpen: false,
            sidebarCollapsed: false,
            openModules: @js([$lesson->course_module_id]),

            toggleModule(id) {
                if (this.openModules.includes(id)) {
                    this.openModules = this.openModules.filter(item => item !== id);
                } else {
                    this.openModules.push(id);
                }
            },

            isModuleOpen(id) {
                return this.openModules.includes(id);
            }
        }">
        <div class="mx-auto max-w-7xl">
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
            @endphp

            <div class="mb-4 flex items-center justify-between gap-3 lg:hidden">
                <button
                    type="button"
                    @click="sidebarOpen = true"
                    class="inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3 text-sm font-bold text-white backdrop-blur-xl">
                    <span>☰</span>
                    <span>Navigasi Materi</span>
                </button>

                <a href="{{ route('mahasiswa.materi.index') }}"
                   class="rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3 text-sm font-bold text-white backdrop-blur-xl">
                    Daftar Materi
                </a>
            </div>

            <div
                x-cloak
                x-show="sidebarOpen"
                x-transition.opacity
                @click="sidebarOpen = false"
                class="fixed inset-x-0 bottom-0 top-20 z-30 bg-slate-950/70 backdrop-blur-sm lg:hidden">
            </div>

            <div class="lg:flex lg:items-start lg:gap-6">
                <aside
                    x-bind:class="{
                        'translate-x-0': sidebarOpen,
                        '-translate-x-full': !sidebarOpen,
                        'lg:w-20 lg:p-3': sidebarCollapsed,
                        'lg:w-80 lg:p-5': !sidebarCollapsed
                    }"
                    class="no-scrollbar fixed bottom-0 left-0 top-20 z-40 w-80 overflow-y-auto border-r border-white/10 bg-slate-950/95 p-5 shadow-2xl shadow-slate-950/50 backdrop-blur-xl transition-all duration-300 lg:sticky lg:top-28 lg:z-10 lg:h-[calc(100vh-8rem)] lg:translate-x-0 lg:rounded-[1.5rem] lg:border lg:bg-white/[0.06]">
                      <div class="mb-5 rounded-2xl border border-white/10 bg-slate-950/40 p-4"
                        x-bind:class="{ 'lg:p-2': sidebarCollapsed }">
                        <div class="flex items-center justify-between gap-3"
                            x-bind:class="{ 'lg:flex-col': sidebarCollapsed }">

                            <div class="min-w-0" x-bind:class="{ 'lg:hidden': sidebarCollapsed }">
                                <p class="text-xs font-bold uppercase tracking-[0.25em] text-cyan-300">
                                    Navigasi
                                </p>

                                <h2 class="mt-1 text-lg font-black text-white">
                                    Materi
                                </h2>

                                <div class="mt-3 h-1 w-16 rounded-full bg-gradient-to-r from-cyan-300 to-blue-500"></div>
                            </div>

                            <button
                                type="button"
                                @click="sidebarOpen = false"
                                class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm font-bold text-white lg:hidden">
                                ✕
                            </button>

                            <button
                                type="button"
                                @click="sidebarCollapsed = !sidebarCollapsed"
                                class="hidden h-10 w-10 items-center justify-center rounded-2xl border border-cyan-300/20 bg-cyan-400/10 text-sm font-black text-cyan-200 hover:bg-cyan-400/20 lg:inline-flex"
                                title="Buka atau tutup sidebar">
                                <span x-text="sidebarCollapsed ? '☰' : '←'"></span>
                            </button>
                        </div>
                    </div>

                    <div class="mt-5 space-y-5">
                        @foreach ($course->modules as $module)
                            @php
                                $isCurrentModule = $module->id === $lesson->course_module_id;
                                $moduleQuizzes = $quizzesByModule[$module->id] ?? collect();
                            @endphp

                            <div class="rounded-2xl border border-white/10 bg-white/[0.04]">
                                <button 
                                    type="button"
                                    @click="toggleModule({{ $module->id }})"
                                    class="flex w-full items-center justify-between gap-3 rounded-2xl px-4 py-3 text-left transition hover:bg-white/[0.05]"
                                    :class="isModuleOpen({{ $module->id }}) ? 'bg-white/[0.06]' : ''">

                                    <div class="min-w-0">
                                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-cyan-300/80">
                                            Bab {{ $loop->iteration }}
                                        </p>

                                        <h3 class="mt-1 line-clamp-2 text-sm font-black leading-5 text-white">
                                            {{ $module->title }}
                                        </h3>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-2">
                                        @if ($isCurrentModule)
                                            <span class="rounded-full bg-cyan-400/15 px-2.5 py-1 text-[10px] font-black text-cyan-200">
                                                Aktif
                                            </span>
                                        @endif

                                        <span 
                                            class="text-lg font-black text-slate-300 transition"
                                            :class="isModuleOpen({{ $module->id }}) ? 'rotate-180 text-cyan-200' : ''">
                                        ⌄
                                        </span>
                                    </div>
                                </button>

                                <div 
                                    x-show="isModuleOpen({{ $module->id }})"
                                    x-transition
                                    class="space-y-2 border-t border-white/10 px-3 py-3">

                                    @foreach ($module->lessons as $sidebarLesson)
                                        @php
                                            $isActiveLesson = $sidebarLesson->id === $lesson->id;
                                            $isAccessibleLesson = in_array($sidebarLesson->id, $accessibleLessonIds);
                                        @endphp

                                        @if ($isAccessibleLesson)
                                            <a href="{{ route('mahasiswa.materi.show', $sidebarLesson->slug) }}"
                                            class="group flex items-start gap-3 rounded-2xl px-3 py-3 transition
                                            {{ $isActiveLesson 
                                                    ? 'bg-cyan-400/15 text-cyan-100 ring-1 ring-cyan-300/20' 
                                                    : 'text-slate-300 hover:bg-white/[0.06] hover:text-white' }}">

                                                <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-xl text-xs font-black
                                                    {{ $isActiveLesson ? 'bg-cyan-400/20 text-cyan-100' : 'bg-white/10 text-slate-300' }}">
                                                    {{ $loop->iteration }}
                                                </span>

                                                <span class="min-w-0 flex-1">
                                                    <span class="block text-sm font-bold leading-5">
                                                        {{ $sidebarLesson->title }}
                                                    </span>

                                                    @if ($isActiveLesson)
                                                        <span class="mt-1 block text-xs text-cyan-100/70">
                                                            Sedang dibuka
                                                        </span>
                                                    @endif
                                                </span>
                                            </a>
                                        @else
                                            <div class="flex cursor-not-allowed items-start gap-3 rounded-2xl px-3 py-3 text-slate-500">
                                                <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-xl bg-white/5 text-xs font-black">
                                                    🔒
                                                </span>

                                                <span class="min-w-0 flex-1">
                                                    <span class="block text-sm font-bold leading-5">
                                                        {{ $sidebarLesson->title }}
                                                    </span>

                                                    <span class="mt-1 block text-xs text-slate-500">
                                                        Selesaikan materi sebelumnya
                                                    </span>
                                                </span>
                                            </div>
                                        @endif
                                    @endforeach

                                    @if ($moduleQuizzes->isNotEmpty())
                                        <div class="mt-3 space-y-2 border-t border-white/10 pt-3">
                                            @foreach ($moduleQuizzes as $sidebarQuiz)
                                                @if ($sidebarQuiz->is_unlocked)
                                                    <a href="{{ route('mahasiswa.kuis.instruction', $sidebarQuiz) }}"
                                                    class="group flex items-start gap-3 rounded-2xl border border-cyan-300/20 bg-cyan-400/10 px-3 py-3 text-cyan-100 transition hover:bg-cyan-400/15">

                                                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-xl bg-cyan-400/20 text-[10px] font-black text-cyan-100">
                                                            CBT
                                                        </span>

                                                        <span class="min-w-0 flex-1">
                                                            <span class="block text-sm font-black leading-5">
                                                                {{ $sidebarQuiz->title }}
                                                            </span>

                                                            <span class="mt-1 block text-xs leading-5 text-cyan-100/70">
                                                                {{ $sidebarQuiz->questions_count }} soal · {{ $sidebarQuiz->duration_minutes }} menit · KKM {{ $sidebarQuiz->classGroup->kkm }}
                                                            </span>
                                                        </span>
                                                    </a>
                                                @else
                                                    <div class="flex cursor-not-allowed items-start gap-3 rounded-2xl border border-yellow-300/10 bg-yellow-400/5 px-3 py-3 text-yellow-100/70">
                                                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-xl bg-yellow-400/10 text-xs font-black">
                                                            🔒
                                                        </span>

                                                        <span class="min-w-0 flex-1">
                                                            <span class="block text-sm font-black leading-5">
                                                                {{ $sidebarQuiz->title }}
                                                            </span>

                                                            <span class="mt-1 block text-xs leading-5 text-yellow-100/60">
                                                                {{ $sidebarQuiz->locked_reason }}
                                                            </span>
                                                        </span>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </aside>

                <main class="min-w-0 flex-1 space-y-6">
                    <section class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-7 backdrop-blur-xl">
                        <p class="text-sm font-semibold text-cyan-200">
                            {{ $lesson->module->title }}
                        </p>

                        <div class="mt-3 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h1 class="text-3xl font-black tracking-tight text-white">
                                    {{ $lesson->title }}
                                </h1>                                
                            </div>

                            <span class="w-fit rounded-full px-4 py-2 text-sm font-bold
                                {{ $progress->completed ? 'bg-green-400/10 text-green-200' : 'bg-yellow-400/10 text-yellow-200' }}">
                                {{ $progress->completed ? 'Selesai' : 'Belum selesai' }}
                            </span>
                        </div>

                        @if ($lesson->learning_outcome)
                            <div class="mt-6 rounded-2xl border border-cyan-300/20 bg-cyan-400/10 p-5">
                                <p class="text-sm font-semibold text-cyan-200">
                                    Capaian Subbab
                                </p>

                                <p class="mt-2 text-sm leading-6 text-slate-300">
                                    {{ $lesson->learning_outcome }}
                                </p>
                            </div>
                        @endif
                    </section>

                    <article class="rounded-[1.5rem] border border-white/10 bg-white/[0.96] p-7 text-slate-800 shadow-2xl shadow-slate-950/20">
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
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h2 class="font-bold text-white">
                                    Tandai Materi Selesai
                                </h2>

                                <p class="mt-1 text-sm text-slate-400">
                                    Klik tombol selesai jika Anda sudah membaca, memahami materi, dan menyelesaikan aktivitas wajib.
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

                                                <li class="flex items-center gap-2 text-sm">
                                                    <span class="flex h-5 w-5 items-center justify-center rounded-full text-xs font-black
                                                        {{ $isPracticeDone ? 'bg-green-400/10 text-green-200' : 'bg-yellow-400/10 text-yellow-200' }}">
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

                            <form action="{{ route('mahasiswa.materi.complete', $lesson->slug) }}" method="POST">
                                @csrf

                                <button type="submit"
                                        class="rounded-2xl bg-cyan-400 px-5 py-3 text-sm font-bold text-slate-950 hover:bg-cyan-300">
                                    Tandai Selesai
                                </button>
                            </form>
                        </div>
                    </section>

                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            @if ($previousLesson)
                                <a href="{{ route('mahasiswa.materi.show', $previousLesson->slug) }}"
                                   class="inline-flex rounded-2xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-bold text-white hover:bg-white/10">
                                    ← Materi Sebelumnya
                                </a>
                            @endif
                        </div>

                        <div>
                            @if ($nextLesson)
                                @if (in_array($nextLesson->id, $accessibleLessonIds ?? [], true))
                                    <a href="{{ route('mahasiswa.materi.show', $nextLesson->slug) }}"
                                       class="inline-flex rounded-2xl bg-cyan-400 px-5 py-3 text-sm font-bold text-slate-950 hover:bg-cyan-300">
                                        Materi Selanjutnya →
                                    </a>
                                @else
                                    <button type="button"
                                            class="inline-flex cursor-not-allowed rounded-2xl bg-slate-700 px-5 py-3 text-sm font-bold text-slate-300"
                                            disabled>
                                        🔒 Materi Berikutnya Terkunci
                                    </button>
                                @endif
                            @else
                                <a href="{{ route('mahasiswa.materi.index') }}"
                                   class="inline-flex rounded-2xl bg-cyan-400 px-5 py-3 text-sm font-bold text-slate-950 hover:bg-cyan-300">
                                    Kembali ke Daftar Materi
                                </a>
                            @endif
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
</x-app-layout>