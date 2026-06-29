@php
    $dosenNavbarUser = Auth::user();
    $dosenNavbarInitial = strtoupper(mb_substr((string) ($dosenNavbarUser->name ?? 'D'), 0, 1));
    $isDosenDashboard = request()->routeIs('dosen.dashboard');
    $isDosenClassPage = request()->routeIs('dosen.kelas.*') || request()->routeIs('dosen.kuis.*');
@endphp

<nav
    x-data="{ mobileMenuOpen: false, profileMenuOpen: false }"
    @keydown.escape.window="mobileMenuOpen = false; profileMenuOpen = false"
    class="fixed inset-x-0 top-0 z-50 border-b border-slate-800 bg-slate-950/95 backdrop-blur-xl"
>
    <div class="mx-auto flex min-h-16 max-w-screen-xl flex-wrap items-center justify-between gap-3 px-4 py-3 lg:px-6">
        <a href="{{ route('dosen.dashboard') }}" class="flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl border border-cyan-300/25 bg-cyan-400/15 text-sm font-black text-cyan-100">R</span>

            <span>
                <span class="block text-lg font-black tracking-tight text-white">RuangOBE</span>
                {{-- <span class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-cyan-200/65">Area Dosen</span> --}}
            </span>
        </a>

        <div class="order-2 flex items-center gap-2 md:order-3">
        
            <div class="relative" @click.outside="profileMenuOpen = false">
                <button
                    type="button"
                    @click="profileMenuOpen = !profileMenuOpen"
                    class="flex items-center gap-2 rounded-full p-1 text-sm transition focus:outline-none focus:ring-4 focus:ring-slate-700"
                    :aria-expanded="profileMenuOpen"
                >
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-cyan-400/15 text-xs font-black text-cyan-100">
                        {{ $dosenNavbarInitial }}
                    </span>

                    <span class="hidden text-left sm:block">
                        <span class="block max-w-32 truncate text-sm font-medium text-white">
                            {{ $dosenNavbarUser->name }}
                        </span>

                        <span class="block text-xs text-slate-400">
                            Dosen
                        </span>
                    </span>
                </button>

                <div
                    x-cloak
                    x-show="profileMenuOpen"
                    x-transition
                    class="absolute right-0 top-full z-[60] mt-3 w-56 overflow-hidden rounded-lg border border-slate-700 bg-slate-900 shadow-xl"
                >
                    <div class="border-b border-slate-700 px-4 py-3">
                        <p class="truncate text-sm font-medium text-white">
                            {{ $dosenNavbarUser->name }}
                        </p>

                        <p class="mt-1 truncate text-sm text-slate-400">
                            {{ $dosenNavbarUser->email }}
                        </p>
                    </div>

                    <ul class="space-y-1 p-2 text-sm font-medium text-slate-300">
                        <li>
                            <a
                                href="{{ route('profile.edit') }}"
                                @click="profileMenuOpen = false"
                                class="inline-flex w-full items-center rounded-md p-2 transition hover:bg-slate-800 hover:text-white"
                            >
                                Profil Saya
                            </a>
                        </li>

                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <button
                                    type="submit"
                                    class="inline-flex w-full items-center rounded-md p-2 text-rose-200 transition hover:bg-rose-400/10 hover:text-rose-100"
                                >
                                    Keluar
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
            <button
                type="button"
                @click="mobileMenuOpen = !mobileMenuOpen"
                class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/[0.04] text-slate-300 transition hover:bg-white/[0.08] focus:outline-none focus:ring-4 focus:ring-cyan-400/15 md:hidden"
                :aria-expanded="mobileMenuOpen"
                aria-label="Buka menu navigasi"
            >
                <svg x-show="!mobileMenuOpen" class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h14" />
                </svg>
                <svg x-cloak x-show="mobileMenuOpen" class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-width="2" d="m6 18 12-12M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div
            class="order-3 hidden w-full md:order-2 md:ml-auto md:mr-5 md:block md:w-auto"
            :class="{ '!block': mobileMenuOpen }"
        >
            <ul class="mt-2 flex flex-col gap-1 rounded-2xl border border-white/10 bg-slate-900/95 p-2 text-sm font-semibold shadow-xl shadow-slate-950/30 md:mt-0 md:flex-row md:items-center md:gap-2 md:border-0 md:bg-transparent md:p-0 md:shadow-none">
                <li>
                    <a href="{{ route('dosen.dashboard') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2 rounded-xl px-3 py-2.5 transition {{ $isDosenDashboard ? 'bg-cyan-400/15 text-cyan-100' : 'text-slate-300 hover:bg-white/[0.07] hover:text-white' }}">
                        <svg class="h-4 w-4 {{ $isDosenDashboard ? 'text-cyan-200' : 'text-slate-400' }}" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13h8V3H3v10Zm0 8h8v-4H3v4Zm10 0h8V11h-8v10Zm0-18v4h8V3h-8Z" />
                        </svg>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="{{ route('dosen.kelas.index') }}" @click="mobileMenuOpen = false" class="flex items-center gap-2 rounded-xl px-3 py-2.5 transition {{ $isDosenClassPage ? 'bg-cyan-400/15 text-cyan-100' : 'text-slate-300 hover:bg-white/[0.07] hover:text-white' }}">
                        <svg class="h-4 w-4 {{ $isDosenClassPage ? 'text-cyan-200' : 'text-slate-400' }}" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5.75A2.75 2.75 0 0 1 5.75 3h12.5A2.75 2.75 0 0 1 21 5.75v12.5A2.75 2.75 0 0 1 18.25 21H5.75A2.75 2.75 0 0 1 3 18.25V5.75ZM7 7h4m-4 4h10m-10 4h7" />
                        </svg>
                        Kelola Kelas
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
