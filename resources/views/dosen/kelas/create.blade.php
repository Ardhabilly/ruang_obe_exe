<x-app-layout>
    <div class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl">
            <div class="rounded-[2rem] border border-white/10 bg-white/[0.06] p-8 backdrop-blur-xl">
                <p class="text-sm font-semibold text-cyan-200">Tambah Kelas</p>
                <h1 class="mt-2 text-3xl font-black text-white">Buat Kelas Baru</h1>

                <form action="{{ route('dosen.kelas.store') }}" method="POST" class="mt-8 space-y-5">
                    @csrf

                    <div>
                        <label class="text-sm font-semibold text-slate-200">Nama Kelas</label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="mt-2 w-full rounded-2xl border-white/10 bg-slate-950/40 text-white placeholder:text-slate-500 focus:border-cyan-400 focus:ring-cyan-400"
                               placeholder="Contoh: Aljabar Linear A">
                        @error('name')
                            <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-200">Deskripsi</label>
                        <textarea name="description" rows="4"
                                  class="mt-2 w-full rounded-2xl border-white/10 bg-slate-950/40 text-white placeholder:text-slate-500 focus:border-cyan-400 focus:ring-cyan-400"
                                  placeholder="Deskripsi singkat kelas">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-200">KKM Kelas</label>
                        <input type="number" name="kkm" value="{{ old('kkm', 70) }}" min="0" max="100"
                               class="mt-2 w-full rounded-2xl border-white/10 bg-slate-950/40 text-white placeholder:text-slate-500 focus:border-cyan-400 focus:ring-cyan-400">
                        @error('kkm')
                            <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                        <input type="checkbox" name="is_active" value="1" checked
                               class="rounded border-white/10 bg-slate-950 text-cyan-400 focus:ring-cyan-400">
                        <span class="text-sm font-semibold text-slate-200">Kelas aktif</span>
                    </label>

                    <div class="flex flex-wrap gap-3 pt-3">
                        <button type="submit"
                                class="rounded-2xl bg-cyan-400 px-5 py-3 text-sm font-bold text-slate-950 hover:bg-cyan-300">
                            Simpan Kelas
                        </button>

                        <a href="{{ route('dosen.kelas.index') }}"
                           class="rounded-2xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-bold text-white hover:bg-white/10">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>