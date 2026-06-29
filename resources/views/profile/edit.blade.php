{{-- PROFILE_THEME_KONSISTEN_V1 --}}
<x-app-layout>
    @php
        $profileClassNames = $user->role === 'dosen'
            ? $user->classGroupsAsDosen()
                ->orderBy('name')
                ->pluck('name')
                ->all()
            : $user->joinedClassGroups()
                ->orderBy('class_groups.name')
                ->pluck('class_groups.name')
                ->all();
    
        $profileClassLabel = $user->role === 'dosen'
            ? 'Kelas Dikelola'
            : 'Kelas';
    
        $profileClassSummary = empty($profileClassNames)
            ? 'Belum ada kelas'
            : implode(', ', array_slice($profileClassNames, 0, 2))
                . (count($profileClassNames) > 2
                    ? ' +' . (count($profileClassNames) - 2)
                    : '');
    @endphp

    @php
        $profileInitial = strtoupper(mb_substr((string) ($user->name ?? 'R'), 0, 1));
    @endphp

    <main class="mx-auto w-full max-w-6xl px-4 py-7 sm:px-6 lg:px-8">
        <div class="space-y-6">
            <section class="overflow-hidden rounded-3xl border border-white/10 bg-slate-900/70 shadow-2xl shadow-slate-950/30">
                <div class="border-b border-white/10 bg-gradient-to-r from-cyan-400/15 via-slate-900 to-slate-900 px-6 py-7 sm:px-8">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border border-cyan-300/25 bg-cyan-400/15 text-2xl font-black text-cyan-100 shadow-lg shadow-cyan-500/10">
                            {{ $profileInitial }}
                        </span>

                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-cyan-200/70">
                                Pengaturan Akun
                            </p>

                            <h1 class="mt-2 truncate text-2xl font-black tracking-tight text-white sm:text-3xl">
                                Profil Saya
                            </h1>

                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-400">
                                Kelola informasi akun, keamanan kata sandi, dan pengaturan akun Anda.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 px-6 py-5 text-sm sm:grid-cols-2 sm:px-8">
                    <div class="rounded-2xl border border-white/10 bg-white/[0.035] px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</p>
                        <p class="mt-1 truncate font-bold text-white">{{ $user->name }}</p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/[0.035] px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Peran</p>
                        <p class="mt-1 font-bold capitalize text-cyan-100">{{ $user->role }}</p>
                    </div>

                                        </div>
            </section>

            <!-- PROFILE_DELETE_SECTION_REMOVED_V2 -->
            <div class="mx-auto max-w-3xl">
                <div class="space-y-6">
                    <section class="rounded-3xl border border-white/10 bg-slate-900/70 p-5 shadow-xl shadow-slate-950/20 sm:p-7">
                        @include('profile.partials.update-profile-information-form')
                    </section>

                    <section class="rounded-3xl border border-white/10 bg-slate-900/70 p-5 shadow-xl shadow-slate-950/20 sm:p-7">
                        @include('profile.partials.update-password-form')
                    </section>
                </div>

                </div>
        </div>
    </main>

    {{-- PROFILE_SUCCESS_POPUP_INCLUDE_V1 --}}
    @include('profile.partials.profile-success-popup')
</x-app-layout>
