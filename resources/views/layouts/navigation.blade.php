{{-- FLOWBITE_TAILWIND_MATERIAL_SIDEBAR_V1 --}}
@php
    $currentUser = Auth::user();
    $isMahasiswa = $currentUser?->role === 'mahasiswa';
    $isDosen = $currentUser?->role === 'dosen';
    $initial = strtoupper(mb_substr((string) ($currentUser?->name ?? 'R'), 0, 1));

    $materialSidebar = $materialSidebar ?? [
        'enabled' => false,
        'course' => null,
        'modules' => collect(),
        'active_lesson_id' => null,
        'active_module_id' => null,
        'open_module_ids' => [],
        'completed_lesson_ids' => [],
        'accessible_lesson_ids' => [],
        'quizzes_by_module' => collect(),
        'final_evaluations' => collect(),
    ];

    $isMaterialSidebar = (bool) ($materialSidebar['enabled'] ?? false);
    $courseTitle = ($materialSidebar['course'] ?? null)?->title ?? 'Sistem Persamaan Linear';

    $continueUrl = \Illuminate\Support\Facades\Route::has('mahasiswa.materi.lanjutkan')
        ? route('mahasiswa.materi.lanjutkan')
        : route('mahasiswa.materi.lanjutkan');
@endphp

<nav
    x-data="{
        sidebarOpen: false,
        userMenuOpen: false,
        openModules: @js($materialSidebar['open_module_ids'] ?? []),

        toggleModule(id) {
            if (this.openModules.includes(id)) {
                this.openModules = this.openModules.filter((item) => item !== id);
            } else {
                this.openModules.push(id);
            }
        },

        isModuleOpen(id) {
            return this.openModules.includes(id);
        }
    }"
    @keydown.escape.window="sidebarOpen = false; userMenuOpen = false"
    class="contents"
>
    {{-- Topbar: struktur bawaan Flowbite/Tailwind --}}
    <header class="fixed inset-x-0 top-0 z-50 h-16 border-b border-slate-800 bg-slate-950/95 backdrop-blur">
        <div class="flex h-full items-center justify-between px-3 sm:pl-4 lg:px-5 lg:pl-3">
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="sidebarOpen = true"
                    class="inline-flex items-center rounded-lg p-2 text-sm text-slate-300 transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-700 sm:hidden"
                    aria-label="Buka sidebar"
                >
                    <svg class="h-6 w-6" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h10" />
                    </svg>
                </button>

                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 sm:ml-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-400/15 text-sm font-black text-cyan-200">
                        R
                    </span>

                    <span class="self-center whitespace-nowrap text-lg font-semibold text-white">
                        RuangOBE
                    </span>
                </a>

                {{-- <span class="hidden border-l border-slate-800 pl-4 text-sm font-medium text-slate-400 lg:inline">
                    {{ $isMaterialSidebar ? $courseTitle : ($isMahasiswa ? 'Mahasiswa' : 'Dosen') }}
                </span> --}}
            </div>

            <div class="relative flex items-center" @click.outside="userMenuOpen = false">
                <button
                    type="button"
                    @click="userMenuOpen = !userMenuOpen"
                    class="flex items-center gap-2 rounded-full p-1 text-sm transition focus:outline-none focus:ring-4 focus:ring-slate-700"
                    :aria-expanded="userMenuOpen"
                >
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-cyan-400/15 text-xs font-black text-cyan-100">
                        {{ $initial }}
                    </span>

                    <span class="hidden text-left sm:block">
                        <span class="block max-w-32 truncate text-sm font-medium text-white">
                            {{ $currentUser->name }}
                        </span>
                        <span class="hidden text-left sm:block text-sm font text-slate-400 lg:inline">
                            {{ ($isMahasiswa ? 'Mahasiswa' : 'Dosen') }}
                        </span>
                    </span>
                </button>

                <div
                    x-cloak
                    x-show="userMenuOpen"
                    x-transition
                    class="absolute right-0 top-full z-50 mt-3 w-52 overflow-hidden rounded-lg border border-slate-700 bg-slate-900 shadow-xl"
                >
                    <div class="border-b border-slate-700 px-4 py-3">
                        <p class="truncate text-sm font-medium text-white">
                            {{ $currentUser->name }}
                        </p>

                        <p class="truncate text-sm text-slate-400">
                            {{ $currentUser->email }}
                        </p>
                    </div>

                    <ul class="space-y-1 p-2 text-sm font-medium text-slate-300">
                        <li>
                            <a href="{{ route('profile.edit') }}" class="inline-flex w-full items-center rounded-md p-2 transition hover:bg-slate-800 hover:text-white">
                                Profil Saya
                            </a>
                        </li>

                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <button type="submit" class="inline-flex w-full items-center rounded-md p-2 text-rose-200 transition hover:bg-rose-400/10 hover:text-rose-100">
                                    Keluar
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    {{-- Overlay untuk sidebar mobile --}}
    <div
        x-cloak
        x-show="sidebarOpen"
        x-transition.opacity
        @click="sidebarOpen = false"
        class="fixed inset-0 z-30 bg-slate-950/70 sm:hidden"
    ></div>

    {{-- Sidebar: struktur bawaan Flowbite/Tailwind --}}
    <aside
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        class="fixed left-0 top-0 z-40 h-screen w-64 border-r border-slate-800 bg-slate-950 transition-transform sm:translate-x-0"
        aria-label="{{ $isMaterialSidebar ? 'Sidebar materi' : 'Sidebar utama' }}"
    >
        <div class="flex h-16 items-center border-b border-slate-800 px-4">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-400/15 text-sm font-black text-cyan-200">
                    R
                </span>

                <span class="self-center whitespace-nowrap text-lg font-semibold text-white">
                    RuangOBE
                </span>
            </a>

            <button
                type="button"
                @click="sidebarOpen = false"
                class="ml-auto inline-flex items-center rounded-lg p-2 text-slate-400 transition hover:bg-slate-800 hover:text-white sm:hidden"
                aria-label="Tutup sidebar"
            >
                <svg class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-width="2" d="m6 18 12-12M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="no-scrollbar h-[calc(100vh-4rem)] overflow-y-auto px-3 py-4">
            @if ($isMaterialSidebar)
                {{-- Tampilan materi--}}

                <div class="mb-3">
                    <a
                        href="{{ route('mahasiswa.dashboard') }}"
                        @click="sidebarOpen = false"
                        class="group flex items-center rounded-lg border border-slate-800 bg-slate-900/60 px-2 py-2 text-sm font-medium text-slate-200 transition hover:bg-slate-800 hover:text-white"
                    >
                        <svg class="h-5 w-5 text-slate-400 transition group-hover:text-cyan-200" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6.025A7.5 7.5 0 1 0 17.975 14H10V6.025Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 3c-.169 0-.334.014-.5.025V11h7.975c.011-.166.025-.331.025-.5A7.5 7.5 0 0 0 13.5 3Z" />
                        </svg>

                        <span class="ml-3">Dashboard</span>

                        <svg class="ml-auto h-4 w-4 text-slate-500 transition group-hover:translate-x-0.5 group-hover:text-cyan-200" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
                        </svg>
                    </a>
                </div>
                <div class="mb-4 border-b border-slate-800 pb-4">
                    <p class="px-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                        Materi Pembelajaran
                    </p>

                    <p class="mt-2 px-2 text-sm font-semibold leading-5 text-white">
                        {{ $courseTitle }}
                    </p>
</div>

                <ul class="space-y-2 font-medium">
                    @forelse ($materialSidebar['modules'] as $module)
                        @php
                            $isActiveModule = (int) $module->id === (int) $materialSidebar['active_module_id'];
                            $moduleQuizzes = $materialSidebar['quizzes_by_module']->get($module->id, collect());
                        @endphp

                        <li>
                            <button
                                type="button"
                                @click="toggleModule({{ (int) $module->id }})"
                                class="group flex w-full items-center rounded-lg px-2 py-2 text-left text-sm transition {{ $isActiveModule ? 'bg-slate-800 text-cyan-100' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                            >
                                <svg class="h-5 w-5 shrink-0 {{ $isActiveModule ? 'text-cyan-200' : 'text-slate-400 group-hover:text-cyan-200' }}" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16.5A1.5 1.5 0 0 0 18.5 18H6.75A2.75 2.75 0 0 0 4 20.75V5.5Zm0 0V21m0 0A2.75 2.75 0 0 1 6.75 18H20" />
                                </svg>

                                <span class="ml-3 flex-1">
                                    <span class="block text-[10px] font-semibold uppercase tracking-wide {{ $isActiveModule ? 'text-cyan-200/75' : 'text-slate-500' }}">
                                        Bab {{ $loop->iteration }}
                                    </span>

                                    <span class="mt-0.5 block text-sm font-medium leading-5">
                                        {{ $module->title }}
                                    </span>
                                </span>

                                <svg class="h-4 w-4 shrink-0 transition" :class="isModuleOpen({{ (int) $module->id }}) ? 'rotate-180 text-cyan-200' : 'text-slate-500'" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                                </svg>
                            </button>

                            <ul
                                x-cloak
                                x-show="isModuleOpen({{ (int) $module->id }})"
                                x-transition
                                class="mt-1 space-y-1 border-l border-slate-800 py-1 pl-3"
                            >
                                @foreach ($module->lessons as $sidebarLesson)
                                    @php
                                        $isActiveLesson = (int) $sidebarLesson->id === (int) $materialSidebar['active_lesson_id'];
                                        $isAccessibleLesson = in_array((int) $sidebarLesson->id, $materialSidebar['accessible_lesson_ids'], true);
                                        $isCompletedLesson = in_array((int) $sidebarLesson->id, $materialSidebar['completed_lesson_ids'], true);
                                    @endphp

                                    @if ($isAccessibleLesson)
                                        <li>
                                            <a
                                                href="{{ route('mahasiswa.materi.show', $sidebarLesson->slug) }}"
                                                @click="sidebarOpen = false"
                                                class="group flex items-center rounded-lg px-2 py-2 text-xs transition {{ $isActiveLesson ? 'bg-cyan-400/10 font-semibold text-cyan-100' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                                            >
                                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md text-[10px] font-bold {{ $isActiveLesson ? 'bg-cyan-400/15 text-cyan-100' : ($isCompletedLesson ? 'bg-green-400/10 text-green-200' : 'bg-slate-800 text-slate-400') }}">
                                                    {{ $isCompletedLesson ? '✓' : $loop->iteration }}
                                                </span>

                                                <span class="ml-2 min-w-0 flex-1 truncate">
                                                    {{ $sidebarLesson->title }}
                                                </span>
                                            </a>
                                        </li>
                                    @else
                                        <li>
                                            <span class="flex cursor-not-allowed items-center rounded-lg px-2 py-2 text-xs text-slate-600">
                                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-slate-900 text-[10px]">
                                                    🔒
                                                </span>

                                                <span class="ml-2 min-w-0 flex-1 truncate">
                                                    {{ $sidebarLesson->title }}
                                                </span>
                                            </span>
                                        </li>
                                    @endif
                                @endforeach

                                @if ($moduleQuizzes->isNotEmpty())
                                    <li class="mt-2 border-t border-slate-800 pt-2">
                                        @foreach ($moduleQuizzes as $sidebarQuiz)
                                            @if ($sidebarQuiz->is_unlocked)
                                                <a
                                                    href="{{ route('mahasiswa.kuis.instruction', $sidebarQuiz) }}"
                                                    @click="sidebarOpen = false"
                                                    class="group flex items-center rounded-lg px-2 py-2 text-xs text-cyan-200 transition hover:bg-slate-800 hover:text-cyan-100"
                                                >
                                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-cyan-400/10 text-[8px] font-black">
                                                        CBT
                                                    </span>

                                                    <span class="ml-2 min-w-0 flex-1 truncate font-semibold">
                                                        {{ $sidebarQuiz->title }}
                                                    </span>
                                                </a>
                                            @else
                                                <span class="flex cursor-not-allowed items-center rounded-lg px-2 py-2 text-xs text-yellow-100/45">
                                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-yellow-400/5 text-[10px]">
                                                        🔒
                                                    </span>

                                                    <span class="ml-2 min-w-0 flex-1 truncate">
                                                        {{ $sidebarQuiz->title }}
                                                    </span>
                                                </span>
                                            @endif
                                        @endforeach
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @empty
                        <li class="rounded-lg border border-slate-800 p-3 text-sm text-slate-500">
                            Struktur materi belum tersedia.
                        </li>
                    @endforelse
                </ul>

                @if ($materialSidebar['final_evaluations']->isNotEmpty())
                    <div class="mt-5 border-t border-slate-800 pt-4">
                        <p class="mb-2 px-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-violet-300/70">
                            Tahap Akhir
                        </p>

                        <ul class="space-y-1 font-medium">
                            @foreach ($materialSidebar['final_evaluations'] as $finalEvaluation)
                                @if ($finalEvaluation->is_unlocked)
                                    <li>
                                        <a
                                            href="{{ route('mahasiswa.kuis.instruction', $finalEvaluation) }}"
                                            @click="sidebarOpen = false"
                                            class="group flex items-center rounded-lg px-2 py-2 text-sm text-violet-100 transition hover:bg-slate-800"
                                        >
                                            <svg class="h-5 w-5 shrink-0 text-violet-300" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>

                                            <span class="ml-3 min-w-0 flex-1 truncate">
                                                {{ $finalEvaluation->title }}
                                            </span>
                                        </a>
                                    </li>
                                @else
                                    <li>
                                        <span class="flex cursor-not-allowed items-center rounded-lg px-2 py-2 text-sm text-yellow-100/45">
                                            <svg class="h-5 w-5 shrink-0" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 3h12a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-1V4a5 5 0 0 0-10 0v2H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z" />
                                            </svg>

                                            <span class="ml-3 min-w-0 flex-1 truncate">
                                                {{ $finalEvaluation->title }}
                                            </span>
                                        </span>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif
            @else
                {{-- Sidebar utama memakai pola menu Flowbite yang sama --}}
                <ul class="space-y-2 font-medium">
                    @if ($isMahasiswa)
                        <li>
                            <a href="{{ route('mahasiswa.dashboard') }}" @click="sidebarOpen = false" class="group flex items-center rounded-lg px-2 py-2 text-sm transition {{ request()->routeIs('mahasiswa.dashboard') ? 'bg-slate-800 text-cyan-100' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <svg class="h-5 w-5 transition {{ request()->routeIs('mahasiswa.dashboard') ? 'text-cyan-200' : 'text-slate-400 group-hover:text-cyan-200' }}" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6.025A7.5 7.5 0 1 0 17.975 14H10V6.025Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 3c-.169 0-.334.014-.5.025V11h7.975c.011-.166.025-.331.025-.5A7.5 7.5 0 0 0 13.5 3Z" />
                                </svg>
                                <span class="ml-3">Dashboard</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('mahasiswa.kelas.index') }}" @click="sidebarOpen = false" class="group flex items-center rounded-lg px-2 py-2 text-sm transition {{ request()->routeIs('mahasiswa.kelas.*') ? 'bg-slate-800 text-cyan-100' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <svg class="h-5 w-5 transition {{ request()->routeIs('mahasiswa.kelas.*') ? 'text-cyan-200' : 'text-slate-400 group-hover:text-cyan-200' }}" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 19h4a1 1 0 0 0 1-1v-1a3 3 0 0 0-3-3h-2m-2.236-4a3 3 0 1 0 0-4M3 18v-1a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Zm8-10a3 3 0 1 1-6 0 3 3 0 0Z" />
                                </svg>
                                <span class="ml-3">Kelas</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ $continueUrl }}" @click="sidebarOpen = false" class="group flex items-center rounded-lg px-2 py-2 text-sm text-slate-300 transition hover:bg-slate-800 hover:text-white">
                                <svg class="h-5 w-5 text-slate-400 transition group-hover:text-cyan-200" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16.5A1.5 1.5 0 0 0 18.5 18H6.75A2.75 2.75 0 0 0 4 20.75V5.5Zm0 0V21m0 0A2.75 2.75 0 0 1 6.75 18H20" />
                                </svg>
                                <span class="ml-3 flex-1 whitespace-nowrap">Materi</span>
                                <span class="rounded bg-slate-800 px-1.5 py-0.5 text-xs font-medium text-cyan-200">Lanjut</span>
                            </a>
                        </li>
                    @endif

                    @if ($isDosen)
                        <li>
                            <a href="{{ route('dosen.dashboard') }}" @click="sidebarOpen = false" class="group flex items-center rounded-lg px-2 py-2 text-sm transition {{ request()->routeIs('dosen.dashboard') ? 'bg-slate-800 text-cyan-100' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <svg class="h-5 w-5 transition {{ request()->routeIs('dosen.dashboard') ? 'text-cyan-200' : 'text-slate-400 group-hover:text-cyan-200' }}" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6.025A7.5 7.5 0 1 0 17.975 14H10V6.025Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 3c-.169 0-.334.014-.5.025V11h7.975c.011-.166.025-.331.025-.5A7.5 7.5 0 0 0 13.5 3Z" />
                                </svg>
                                <span class="ml-3">Dashboard</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('dosen.kelas.index') }}" @click="sidebarOpen = false" class="group flex items-center rounded-lg px-2 py-2 text-sm transition {{ request()->routeIs('dosen.kelas.*') || request()->routeIs('dosen.kuis.*') ? 'bg-slate-800 text-cyan-100' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <svg class="h-5 w-5 transition {{ request()->routeIs('dosen.kelas.*') || request()->routeIs('dosen.kuis.*') ? 'text-cyan-200' : 'text-slate-400 group-hover:text-cyan-200' }}" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5.75A2.75 2.75 0 0 1 5.75 3h12.5A2.75 2.75 0 0 1 21 5.75v12.5A2.75 2.75 0 0 1 18.25 21H5.75A2.75 2.75 0 0 1 3 18.25V5.75ZM7 7h4m-4 4h10m-10 4h7" />
                                </svg>
                                <span class="ml-3 flex-1">Kelola Kelas</span>
                                <span class="rounded bg-slate-800 px-1.5 py-0.5 text-xs font-medium text-slate-300">Kuis</span>
                            </a>
                        </li>
                    @endif
                </ul>

                <div class="mt-6 border-t border-slate-800 pt-4">
                    <a href="{{ route('profile.edit') }}" @click="sidebarOpen = false" class="group flex items-center rounded-lg px-2 py-2 text-sm text-slate-300 transition hover:bg-slate-800 hover:text-white">
                        <svg class="h-5 w-5 text-slate-400 transition group-hover:text-cyan-200" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A9.001 9.001 0 0 1 12 15c2.5 0 4.76 1.02 6.38 2.665M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        <span class="ml-3">Profil Saya</span>
                    </a>
                </div>
            @endif
        </div>
    </aside>
</nav>
