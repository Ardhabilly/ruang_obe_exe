<section>
    <header>
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-violet-300/20 bg-violet-400/10 text-violet-200">
                <svg class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 3h12a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-1V4a5 5 0 0 0-10 0v2H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z" />
                </svg>
            </span>

            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-200/70">Keamanan Akun</p>
                <h2 class="mt-1 text-xl font-black text-white">Ubah Kata Sandi</h2>
                <p class="mt-2 text-sm leading-6 text-slate-400">Gunakan kata sandi yang kuat dan tidak digunakan pada akun lain.</p>
            </div>
        </div>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-7 space-y-5">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="text-sm font-bold text-slate-200">Kata Sandi Saat Ini</label>
            <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white transition outline-none focus:border-violet-300/50 focus:ring-4 focus:ring-violet-400/10">
            @error('current_password', 'updatePassword')
                <p class="mt-2 text-sm font-medium text-rose-200">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="update_password_password" class="text-sm font-bold text-slate-200">Kata Sandi Baru</label>
            <input id="update_password_password" name="password" type="password" autocomplete="new-password" class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white transition outline-none focus:border-violet-300/50 focus:ring-4 focus:ring-violet-400/10">
            @error('password', 'updatePassword')
                <p class="mt-2 text-sm font-medium text-rose-200">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="update_password_password_confirmation" class="text-sm font-bold text-slate-200">Konfirmasi Kata Sandi Baru</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white transition outline-none focus:border-violet-300/50 focus:ring-4 focus:ring-violet-400/10">
            @error('password_confirmation', 'updatePassword')
                <p class="mt-2 text-sm font-medium text-rose-200">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-wrap items-center gap-3 pt-1">
            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-violet-400 px-5 py-3 text-sm font-black text-slate-950 transition hover:bg-violet-300 focus:outline-none focus:ring-4 focus:ring-violet-400/20">Perbarui Kata Sandi</button>

            </div>
    </form>
</section>
