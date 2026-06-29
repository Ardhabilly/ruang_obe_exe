{{-- PROFILE_DELETE_THEME_KONSISTEN_V1 --}}
<section x-data="{ deleteModalOpen: false }" class="space-y-6">
    <header>
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-rose-300/20 bg-rose-400/10 text-rose-200">
                <svg class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 3h12a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-1V4a5 5 0 0 0-10 0v2H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z" />
                </svg>
            </span>

            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-rose-200/70">Zona Berisiko</p>
                <h2 class="mt-1 text-xl font-black text-white">Hapus Akun</h2>
                <p class="mt-2 text-sm leading-6 text-slate-400">Tindakan ini menghapus akun dan seluruh data terkait secara permanen.</p>
            </div>
        </div>
    </header>

    <div class="rounded-2xl border border-rose-300/15 bg-slate-950/25 p-4">
        <p class="text-sm leading-6 text-slate-300">Pastikan data yang masih diperlukan telah dicadangkan sebelum melanjutkan penghapusan akun.</p>
    </div>

    <button type="button" @click="deleteModalOpen = true" class="inline-flex items-center justify-center rounded-xl border border-rose-300/25 bg-rose-400/10 px-5 py-3 text-sm font-black text-rose-100 transition hover:bg-rose-400/20 focus:outline-none focus:ring-4 focus:ring-rose-400/15">Hapus Akun</button>

    <div x-cloak x-show="deleteModalOpen" x-transition.opacity @keydown.escape.window="deleteModalOpen = false" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/75 p-4 backdrop-blur-sm">
        <div @click.outside="deleteModalOpen = false" x-transition class="w-full max-w-lg rounded-3xl border border-white/10 bg-slate-900 p-6 shadow-2xl shadow-slate-950/70 sm:p-7">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-rose-200/70">Konfirmasi Penghapusan</p>
                    <h3 class="mt-2 text-xl font-black text-white">Hapus akun secara permanen?</h3>
                </div>

                <button type="button" @click="deleteModalOpen = false" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/[0.04] text-slate-400 transition hover:bg-white/[0.08] hover:text-white" aria-label="Tutup konfirmasi">
                    <svg class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-width="2" d="m6 18 12-12M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <p class="mt-4 text-sm leading-6 text-slate-400">Masukkan kata sandi untuk mengonfirmasi bahwa Anda benar-benar ingin menghapus akun ini.</p>

            <form method="post" action="{{ route('profile.destroy') }}" class="mt-6">
                @csrf
                @method('delete')

                <label for="delete_account_password" class="text-sm font-bold text-slate-200">Kata Sandi</label>
                <input id="delete_account_password" name="password" type="password" autocomplete="current-password" placeholder="Masukkan kata sandi" class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder:text-slate-600 transition outline-none focus:border-rose-300/50 focus:ring-4 focus:ring-rose-400/10">

                @error('password', 'userDeletion')
                    <p class="mt-2 text-sm font-medium text-rose-200">{{ $message }}</p>
                @enderror

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" @click="deleteModalOpen = false" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/[0.05] px-5 py-3 text-sm font-bold text-slate-200 transition hover:bg-white/[0.1] hover:text-white">Batal</button>
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-rose-400 px-5 py-3 text-sm font-black text-slate-950 transition hover:bg-rose-300 focus:outline-none focus:ring-4 focus:ring-rose-400/20">Hapus Akun Permanen</button>
                </div>
            </form>
        </div>
    </div>
</section>
