<nav x-data="{ open: false }" class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-slate-950/80 shadow-lg shadow-slate-950/40 backdrop-blur-xl">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-20 items-center justify-between">
            <div class="flex items-center gap-8">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl border border-cyan-300/30 bg-cyan-400/10 shadow-lg shadow-cyan-500/10">
                        <span class="text-lg font-black text-cyan-300">R</span>
                    </div>

                    <div>
                        <div class="text-lg font-extrabold tracking-tight text-white">
                            RuangOBE
                        </div>
                        <div class="text-xs font-medium text-cyan-200/70">
                            Linear Algebra Learning Space
                        </div>
                    </div>
                </a>

                <div class="hidden items-center gap-2 md:flex">
                    @if (auth()->user()->role === 'mahasiswa')
                        <a href="{{ route('mahasiswa.dashboard') }}"
                           class="rounded-xl px-4 py-2 text-sm font-semibold transition
                           {{ request()->routeIs('mahasiswa.dashboard') ? 'bg-cyan-400/15 text-cyan-200 ring-1 ring-cyan-300/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('mahasiswa.kelas.index') }}"
                            class="rounded-xl px-4 py-2 text-sm font-semibold transition
                            {{ request()->routeIs('mahasiswa.kelas.*') ? 'bg-cyan-400/15 text-cyan-200 ring-1 ring-cyan-300/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                Kelas
                        </a>
                        <a href="{{ route('mahasiswa.materi.index') }}"
                            class="rounded-xl px-4 py-2 text-sm font-semibold transition
                            {{ request()->routeIs('mahasiswa.materi.*') ? 'bg-cyan-400/15 text-cyan-200 ring-1 ring-cyan-300/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                Materi
                        </a>
                    @endif

                    @if (auth()->user()->role === 'dosen')
                        <a href="{{ route('dosen.dashboard') }}"
                           class="rounded-xl px-4 py-2 text-sm font-semibold transition
                           {{ request()->routeIs('dosen.dashboard') ? 'bg-cyan-400/15 text-cyan-200 ring-1 ring-cyan-300/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('dosen.kelas.index') }}"
                            class="rounded-xl px-4 py-2 text-sm font-semibold transition
                            {{ request()->routeIs('dosen.kelas.*') ? 'bg-cyan-400/15 text-cyan-200 ring-1 ring-cyan-300/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                Kelas
                        </a>
                        <a href="{{ route('dosen.kuis.index') }}"
                           class="rounded-xl px-4 py-2 text-sm font-semibold transition
                           {{ request()->routeIs('dosen.kuis.*') ? 'bg-cyan-400/15 text-cyan-200 ring-1 ring-cyan-300/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                            Manajemen Kuis
                        </a>
                    @endif
                </div>
            </div>

            <div class="hidden items-center gap-4 md:flex">
                <div class="text-right">
                    <div class="text-sm font-bold text-white">
                        {{ Auth::user()->name }}
                    </div>
                    <div class="text-xs capitalize text-slate-400">
                        {{ Auth::user()->role }}
                    </div>
                </div>

                <a href="{{ route('profile.edit') }}"
                   class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-white/10">
                    Profil
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit"
                            class="rounded-xl bg-cyan-400 px-4 py-2 text-sm font-bold text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-300">
                        Keluar
                    </button>
                </form>
            </div>

            <button @click="open = ! open"
                    class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 p-2 text-slate-200 md:hidden">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <div :class="{ 'block': open, 'hidden': !open }" class="hidden border-t border-white/10 md:hidden">
        <div class="space-y-2 px-4 py-4">
            <a href="{{ route('dashboard') }}"
               class="block rounded-xl bg-white/5 px-4 py-3 text-sm font-semibold text-slate-200">
                Dashboard
            </a>
            @if (auth()->user()->role === 'dosen')
                <a href="{{ route('dosen.kelas.index') }}"
                   class="block rounded-xl bg-white/5 px-4 py-3 text-sm font-semibold text-slate-200">
                    Kelas
                </a>

                <a href="{{ route('dosen.kuis.index') }}"
                   class="block rounded-xl bg-white/5 px-4 py-3 text-sm font-semibold text-slate-200">
                    Manajemen Kuis
                </a>
            @endif

            <a href="{{ route('profile.edit') }}"
               class="block rounded-xl bg-white/5 px-4 py-3 text-sm font-semibold text-slate-200">
                Profil
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit"
                        class="block w-full rounded-xl bg-cyan-400 px-4 py-3 text-left text-sm font-bold text-slate-950">
                    Keluar
                </button>
            </form>
        </div>
    </div>
</nav>