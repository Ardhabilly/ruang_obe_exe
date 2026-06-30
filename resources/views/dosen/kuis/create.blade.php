<x-app-layout>
    <div data-form="kuis-kelas-terpilih" class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl space-y-7">
            <div class="flex flex-wrap items-center gap-2 text-sm text-slate-400">
                <a href="{{ route('dosen.dashboard') }}" class="transition hover:text-cyan-200">Dashboard Dosen</a>
                <span>/</span>
                <a href="{{ route('dosen.kelas.index') }}" class="transition hover:text-cyan-200">Kelas</a>
                <span>/</span>
                <a href="{{ route('dosen.kelas.show', $classGroup) }}" class="transition hover:text-cyan-200">{{ $classGroup->name }}</a>
                <span>/</span>
                <span class="text-white">Buat Kuis</span>
            </div>

            <section class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-white/[0.06] p-7 shadow-2xl shadow-blue-950/30 backdrop-blur-xl sm:p-8">
                <div class="absolute right-[-70px] top-[-70px] h-56 w-56 rounded-full bg-cyan-400/10 blur-3xl"></div>

                <div class="relative">
                    <div class="inline-flex rounded-full border border-cyan-300/20 bg-cyan-400/10 px-4 py-2 text-sm font-semibold text-cyan-200">
                        Kelas terpilih
                    </div>

                    <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-white md:text-4xl">
                        Buat Kuis Baru
                    </h1>

                    <p class="mt-3 max-w-3xl text-base leading-7 text-slate-300">
                        Atur identitas dan pengaturan dasar kuis untuk kelas yang sedang dibuka. Kuis akan tersimpan sebagai draf sebelum soal disusun.
                    </p>
                </div>
            </section>

            <form method="POST" action="{{ route('dosen.kuis.store') }}"
                  x-data="{ quizType: @js(old('type', 'kuis_bab')) }"
                  class="space-y-6 rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                @csrf
                <input type="hidden" name="class_group_id" value="{{ $classGroup->id }}">

                <section class="rounded-2xl border border-cyan-300/20 bg-cyan-400/[0.06] p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-cyan-200">Kelas Tujuan</p>
                            <h2 class="mt-2 text-lg font-black text-white">{{ $classGroup->name }}</h2>

                            @if ($classGroup->description)
                                <p class="mt-1 text-sm text-slate-400">{{ $classGroup->description }}</p>
                            @endif
                        </div>

                        <div class="w-fit rounded-xl border border-white/10 bg-slate-950/35 px-3 py-2 text-right">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">KKM</p>
                            <p class="mt-1 text-base font-black text-white">{{ $classGroup->kkm }}</p>
                        </div>
                    </div>
                </section>

                @error('class_group_id')
                    <p class="-mt-3 text-sm text-red-300">{{ $message }}</p>
                @enderror

                <div>
                    <label for="duration_minutes" class="text-sm font-bold text-white">
                        Durasi Pengerjaan <span class="text-cyan-200">*</span>
                    </label>

                    <div class="relative mt-3 max-w-sm">
                        <input id="duration_minutes" name="duration_minutes" type="number" min="5" max="180"
                               value="{{ old('duration_minutes', 20) }}" required
                               class="w-full rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 pr-20 text-sm text-white outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">

                        <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-sm font-semibold text-slate-400">
                            menit
                        </span>
                    </div>

                    @error('duration_minutes')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <p class="text-sm font-bold text-white">
                        Jenis Kuis <span class="text-cyan-200">*</span>
                    </p>

                    <div class="mt-3 grid gap-4 md:grid-cols-2">
                        <label class="cursor-pointer rounded-2xl border p-5 transition"
                               :class="quizType === 'kuis_bab' ? 'border-cyan-300/40 bg-cyan-400/10' : 'border-white/10 bg-slate-950/40 hover:border-white/20'">
                            <input type="radio" name="type" value="kuis_bab" x-model="quizType" class="sr-only">

                            <span class="block text-sm font-bold text-white">Kuis Bab</span>
                            <span class="mt-1 block text-sm leading-6 text-slate-400">
                                Kuis setelah mahasiswa menyelesaikan seluruh materi pada satu bab.
                            </span>
                        </label>

                        <label class="cursor-pointer rounded-2xl border p-5 transition"
                               :class="quizType === 'evaluasi_akhir' ? 'border-violet-300/40 bg-violet-400/10' : 'border-white/10 bg-slate-950/40 hover:border-white/20'">
                            <input type="radio" name="type" value="evaluasi_akhir" x-model="quizType" class="sr-only">

                            <span class="block text-sm font-bold text-white">Evaluasi Akhir</span>
                            <span class="mt-1 block text-sm leading-6 text-slate-400">
                                Evaluasi setelah mahasiswa menyelesaikan seluruh materi dari semua bab.
                            </span>
                        </label>
                    </div>

                    @error('type')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <div x-show="quizType === 'kuis_bab'" x-transition>
                    <label for="course_module_id" class="text-sm font-bold text-white">
                        Bab Materi <span class="text-cyan-200">*</span>
                    </label>

                    <select id="course_module_id" name="course_module_id"
                            :required="quizType === 'kuis_bab'"
                            :disabled="quizType !== 'kuis_bab'"
                            class="mt-3 w-full rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">
                        <option value="" class="bg-slate-950">Pilih bab materi</option>
                        @foreach ($modules as $module)
                            <option value="{{ $module->id }}"
                                @selected((string) old('course_module_id') === (string) $module->id)
                                class="bg-slate-950">
                                {{ $module->title }}
                            </option>
                        @endforeach
                    </select>

                    @error('course_module_id')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="title" class="text-sm font-bold text-white">
                        Judul Kuis <span class="text-cyan-200">*</span>
                    </label>

                    <input id="title" name="title" type="text" maxlength="180"
                           value="{{ old('title') }}"
                           placeholder="Contoh: Kuis Bab 2 — Operasi Baris Elementer" required
                           class="mt-3 w-full rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">

                    @error('title')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="text-sm font-bold text-white">
                        Deskripsi Singkat
                    </label>

                    <textarea id="description" name="description" rows="3" maxlength="1000"
                              placeholder="Jelaskan tujuan atau cakupan kuis."
                              class="mt-3 w-full resize-y rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm leading-6 text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">{{ old('description') }}</textarea>

                    @error('description')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="instruction" class="text-sm font-bold text-white">
                        Instruksi untuk Mahasiswa
                    </label>

                    <textarea id="instruction" name="instruction" rows="6" maxlength="3000"
                              placeholder="Contoh: Bacalah setiap soal dengan cermat. Pastikan seluruh jawaban terisi sebelum mengumpulkan kuis."
                              class="mt-3 w-full resize-y rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm leading-6 text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/50 focus:ring-2 focus:ring-cyan-400/10">{{ old('instruction') }}</textarea>

                    @error('instruction')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-2xl border border-cyan-300/15 bg-cyan-400/[0.06] p-5">
                    <p class="font-bold text-cyan-100">Status awal: Draf</p>
                    <p class="mt-2 text-sm leading-6 text-slate-300">
                        Kuis belum dapat diakses mahasiswa setelah dibuat. Tambahkan soal terlebih dahulu, kemudian aktifkan kuis pada halaman detail kuis.
                    </p>
                </div>

                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                    <a href="{{ route('dosen.kelas.show', $classGroup) }}"
                       class="inline-flex justify-center rounded-xl border border-white/10 bg-white/[0.04] px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10">
                        Batal
                    </a>

                    <button type="submit"
                            class="inline-flex justify-center rounded-xl bg-cyan-400 px-5 py-3 text-sm font-black text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-300">
                        Simpan sebagai Draf
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>