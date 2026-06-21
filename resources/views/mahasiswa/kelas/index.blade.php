<x-app-layout>
    <div class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @if (session('success'))
                <div class="rounded-2xl border border-green-300/20 bg-green-400/10 p-4 text-sm font-semibold text-green-200">
                    {{ session('success') }}
                </div>
            @endif

            <section class="grid gap-6 lg:grid-cols-[0.85fr_1.15fr]">
                <div class="rounded-[2rem] border border-white/10 bg-white/[0.06] p-8 backdrop-blur-xl">
                    <p class="text-sm font-semibold text-cyan-200">Gabung Kelas</p>
                    <h1 class="mt-2 text-3xl font-black text-white">Masukkan Token Kelas</h1>
                    <p class="mt-3 text-sm leading-6 text-slate-400">
                        Masukkan token yang diberikan oleh dosen agar Anda dapat mengakses kuis sesuai kelas.
                    </p>

                    <form action="{{ route('mahasiswa.kelas.join') }}" method="POST" class="mt-6 space-y-4">
                        @csrf

                        <div>
                            <label class="text-sm font-semibold text-slate-200">Token Kelas</label>
                            <input type="text" name="token" value="{{ old('token') }}"
                                   class="mt-2 w-full rounded-2xl border-white/10 bg-slate-950/40 text-white placeholder:text-slate-500 focus:border-cyan-400 focus:ring-cyan-400"
                                   placeholder="Contoh: A1B2C3D4">
                            @error('token')
                                <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit"
                                class="rounded-2xl bg-cyan-400 px-5 py-3 text-sm font-bold text-slate-950 hover:bg-cyan-300">
                            Gabung Kelas
                        </button>
                    </form>
                </div>

                <div class="rounded-[2rem] border border-white/10 bg-white/[0.06] p-8 backdrop-blur-xl">
                    <p class="text-sm font-semibold text-cyan-200">Kelas Saya</p>
                    <h2 class="mt-2 text-3xl font-black text-white">Daftar Kelas Terdaftar</h2>

                    <div class="mt-6 space-y-4">
                        @forelse ($joinedClasses as $classGroup)
                            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-5">
                                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <h3 class="text-lg font-bold text-white">
                                            {{ $classGroup->name }}
                                        </h3>

                                        <p class="mt-2 text-sm text-slate-400">
                                            Dosen: {{ $classGroup->dosen->name }}
                                        </p>

                                        <p class="mt-2 text-sm text-slate-500">
                                            {{ $classGroup->description ?: 'Tidak ada deskripsi.' }}
                                        </p>
                                    </div>

                                    <div class="rounded-2xl border border-cyan-300/20 bg-cyan-400/10 px-4 py-3 text-center">
                                        <p class="text-xs text-cyan-200">KKM</p>
                                        <p class="text-2xl font-black text-white">
                                            {{ $classGroup->kkm }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-6 text-sm text-slate-400">
                                Anda belum tergabung dalam kelas apa pun.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>