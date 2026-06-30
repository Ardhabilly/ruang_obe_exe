<x-guest-layout>
    <a href="{{ route('landing') }}"
       class="fixed left-6 top-6 z-50 inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3 text-sm font-bold text-white shadow-lg shadow-slate-950/30 backdrop-blur-xl transition hover:bg-white/10">
        <span>←</span>
        <span>Kembali</span>
    </a>
    <div class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
    <div class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid w-full max-w-6xl overflow-hidden rounded-[2rem] border border-white/10 bg-white/[0.06] shadow-2xl shadow-cyan-950/40 backdrop-blur-xl lg:grid-cols-[0.95fr_1.05fr]">
            <section class="p-6 sm:p-10">
                <div class="mx-auto max-w-md">
                    <div class="mb-8 text-center lg:hidden">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-cyan-400 text-xl font-black text-slate-950">
                            R
                        </div>

                        <h1 class="mt-4 text-3xl font-black text-white">
                            RuangOBE
                        </h1>

                        <p class="mt-2 text-sm text-slate-400">
                            Elementary Row Operations Learning Space
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.25em] text-cyan-300">
                            Daftar
                        </p>

                        <h2 class="mt-3 text-3xl font-black tracking-tight text-white">
                            Buat akun mahasiswa
                        </h2>

                        <p class="mt-2 text-sm leading-6 text-slate-400">
                            Akun baru otomatis terdaftar sebagai mahasiswa. Setelah masuk, Anda dapat bergabung ke kelas menggunakan token dari dosen.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
                        @csrf

                        <div>
                            <label for="name" class="text-sm font-semibold text-slate-200">
                                Nama Lengkap
                            </label>

                            <input
                                id="name"
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                required
                                autofocus
                                autocomplete="name"
                                class="mt-2 w-full rounded-2xl border-white/10 bg-slate-950/50 px-4 py-3 text-white placeholder:text-slate-500 focus:border-cyan-400 focus:ring-cyan-400"
                                placeholder="Masukkan nama lengkap"
                            >

                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <label for="email" class="text-sm font-semibold text-slate-200">
                                Email
                            </label>

                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autocomplete="username"
                                class="mt-2 w-full rounded-2xl border-white/10 bg-slate-950/50 px-4 py-3 text-white placeholder:text-slate-500 focus:border-cyan-400 focus:ring-cyan-400"
                                placeholder="nama@email.com"
                            >

                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <label for="password" class="text-sm font-semibold text-slate-200">
                                Password
                            </label>

                            <input
                                id="password"
                                type="password"
                                name="password"
                                required
                                autocomplete="new-password"
                                class="mt-2 w-full rounded-2xl border-white/10 bg-slate-950/50 px-4 py-3 text-white placeholder:text-slate-500 focus:border-cyan-400 focus:ring-cyan-400"
                                placeholder="Minimal 8 karakter"
                            >

                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div>
                            <label for="password_confirmation" class="text-sm font-semibold text-slate-200">
                                Konfirmasi Password
                            </label>

                            <input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                required
                                autocomplete="new-password"
                                class="mt-2 w-full rounded-2xl border-white/10 bg-slate-950/50 px-4 py-3 text-white placeholder:text-slate-500 focus:border-cyan-400 focus:ring-cyan-400"
                                placeholder="Ulangi password"
                            >

                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                        </div>

                        <button
                            type="submit"
                            class="w-full rounded-2xl bg-cyan-400 px-5 py-3 text-sm font-black text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-300">
                            Daftar sebagai Mahasiswa
                        </button>

                        <p class="text-center text-sm text-slate-400">
                            Sudah punya akun?
                            <a href="{{ route('login') }}" class="font-bold text-cyan-300 hover:text-cyan-200">
                                Masuk di sini
                            </a>
                        </p>
                    </form>
                </div>
            </section>

            <section class="relative hidden overflow-hidden bg-slate-950/50 p-10 lg:block">
                <div class="absolute left-[-120px] top-[-120px] h-80 w-80 rounded-full bg-cyan-400/20 blur-3xl"></div>
                <div class="absolute bottom-[-120px] right-[-120px] h-80 w-80 rounded-full bg-violet-500/20 blur-3xl"></div>

                <div class="relative flex h-full flex-col justify-between">
                    <div>
                        <div class="inline-flex items-center gap-3 rounded-2xl border border-cyan-300/20 bg-cyan-400/10 px-4 py-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-cyan-400 text-lg font-black text-slate-950">
                                R
                            </div>

                            <div>
                                <p class="text-lg font-black text-white">
                                    RuangOBE
                                </p>
                                <p class="text-xs font-semibold text-cyan-200/80">
                                    Linear Algebra Learning Space
                                </p>
                            </div>
                        </div>

                        <h1 class="mt-10 max-w-xl text-5xl font-black tracking-tight text-white">
                            Mulai belajar dengan
                            <span class="bg-gradient-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent">
                                alur terarah
                            </span>
                        </h1>

                        <p class="mt-5 max-w-lg text-base leading-8 text-slate-300">
                            Bergabung ke kelas menggunakan token dari dosen, selesaikan materi secara berurutan,
                            kerjakan latihan, lalu ikuti kuis sesuai kelas.
                        </p>
                    </div>

                    <div class="space-y-3">
                        <div class="rounded-2xl border border-white/10 bg-white/[0.05] p-4">
                            <p class="font-bold text-cyan-200">01. Gabung Kelas</p>
                            <p class="mt-1 text-sm text-slate-400">Masukkan token kelas yang diberikan dosen.</p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/[0.05] p-4">
                            <p class="font-bold text-cyan-200">02. Selesaikan Materi</p>
                            <p class="mt-1 text-sm text-slate-400">Materi dibuka bertahap sesuai progres belajar.</p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/[0.05] p-4">
                            <p class="font-bold text-cyan-200">03. Ikuti Kuis</p>
                            <p class="mt-1 text-sm text-slate-400">Kuis mengikuti kelas dan KKM yang ditetapkan dosen.</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-guest-layout>