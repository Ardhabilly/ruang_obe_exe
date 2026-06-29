<section>
    <header>
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-cyan-300/20 bg-cyan-400/10 text-cyan-200">
                <svg class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A9.001 9.001 0 0 1 12 15c2.5 0 4.76 1.02 6.38 2.665M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
            </span>

            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-cyan-200/70">Informasi Akun</p>
                <h2 class="mt-1 text-xl font-black text-white">Informasi Profil</h2>
                <p class="mt-2 text-sm leading-6 text-slate-400">Perbarui nama dan alamat email yang digunakan untuk masuk ke RuangOBE.</p>
            </div>
        </div>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-7 space-y-5">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="text-sm font-bold text-slate-200">Nama</label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder:text-slate-600 transition outline-none focus:border-cyan-300/50 focus:ring-4 focus:ring-cyan-400/10">
            @error('name')
                <p class="mt-2 text-sm font-medium text-rose-200">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="text-sm font-bold text-slate-200">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username" class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder:text-slate-600 transition outline-none focus:border-cyan-300/50 focus:ring-4 focus:ring-cyan-400/10">
            @error('email')
                <p class="mt-2 text-sm font-medium text-rose-200">{{ $message }}</p>
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-4 rounded-2xl border border-yellow-300/15 bg-yellow-400/[0.06] p-4">
                    <p class="text-sm leading-6 text-yellow-100">Alamat email Anda belum terverifikasi.</p>
                    <button form="send-verification" class="mt-2 text-sm font-bold text-yellow-200 underline decoration-yellow-300/40 underline-offset-4 transition hover:text-yellow-100">Kirim ulang email verifikasi</button>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-3 text-sm font-bold text-green-200">Tautan verifikasi baru telah dikirim ke alamat email Anda.</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-3 pt-1">
            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-cyan-400 px-5 py-3 text-sm font-black text-slate-950 transition hover:bg-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-400/20">Simpan Perubahan</button>

            </div>
    </form>
</section>
