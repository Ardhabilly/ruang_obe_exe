<x-app-layout>
    <div class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @if (session('success'))
                <div class="rounded-2xl border border-green-300/20 bg-green-400/10 p-4 text-sm font-semibold text-green-200">
                    {{ session('success') }}
                </div>
            @endif

            <section class="flex flex-col gap-4 rounded-[2rem] border border-white/10 bg-white/[0.06] p-8 backdrop-blur-xl md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm font-semibold text-cyan-200">Manajemen Kelas</p>
                    <h1 class="mt-2 text-3xl font-black text-white">Kelas Pembelajaran</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-400">
                        Dosen dapat membuat kelas, mengatur KKM, membagikan token, dan memantau mahasiswa yang tergabung.
                    </p>
                </div>

                <a href="{{ route('dosen.kelas.create') }}"
                   class="rounded-2xl bg-cyan-400 px-5 py-3 text-sm font-bold text-slate-950 shadow-lg shadow-cyan-500/20 hover:bg-cyan-300">
                    + Tambah Kelas
                </a>
            </section>

            <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($classGroups as $classGroup)
                    <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-xl font-bold text-white">
                                    {{ $classGroup->name }}
                                </h2>

                                <p class="mt-2 text-sm text-slate-400">
                                    {{ $classGroup->description ?: 'Tidak ada deskripsi.' }}
                                </p>
                            </div>

                            <span class="rounded-full px-3 py-1 text-xs font-bold
                                {{ $classGroup->is_active ? 'bg-green-400/10 text-green-200' : 'bg-red-400/10 text-red-200' }}">
                                {{ $classGroup->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>

                        <div class="mt-5 grid grid-cols-2 gap-3">
                            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                                <p class="text-xs text-slate-400">KKM</p>
                                <p class="mt-1 text-2xl font-black text-white">{{ $classGroup->kkm }}</p>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                                <p class="text-xs text-slate-400">Mahasiswa</p>
                                <p class="mt-1 text-2xl font-black text-white">{{ $classGroup->members_count }}</p>
                            </div>
                        </div>

                        <div class="mt-5 rounded-2xl border border-cyan-300/20 bg-cyan-400/10 p-4">
                            <p class="text-xs font-semibold text-cyan-200">Token Kelas</p>
                            <p class="mt-2 font-mono text-2xl font-black tracking-widest text-white">
                                {{ $classGroup->token }}
                            </p>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-2">
                            <a href="{{ route('dosen.kelas.show', $classGroup) }}"
                               class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-bold text-white hover:bg-white/10">
                                Detail
                            </a>

                            <a href="{{ route('dosen.kelas.edit', $classGroup) }}"
                               class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-bold text-white hover:bg-white/10">
                                Edit
                            </a>

                            <form action="{{ route('dosen.kelas.regenerate-token', $classGroup) }}" method="POST">
                                @csrf
                                @method('PATCH')

                                <button type="submit"
                                        class="rounded-xl border border-cyan-300/20 bg-cyan-400/10 px-4 py-2 text-sm font-bold text-cyan-200 hover:bg-cyan-400/20">
                                    Token Baru
                                </button>
                            </form>

                            <form action="{{ route('dosen.kelas.destroy', $classGroup) }}" method="POST"
                                  onsubmit="return confirm('Yakin ingin menghapus kelas ini? Semua data anggota kelas juga akan terhapus.')">
                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                        class="rounded-xl border border-red-300/20 bg-red-400/10 px-4 py-2 text-sm font-bold text-red-200 hover:bg-red-400/20">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-8 text-slate-300 backdrop-blur-xl md:col-span-2 xl:col-span-3">
                        Belum ada kelas. Silakan buat kelas pertama.
                    </div>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>