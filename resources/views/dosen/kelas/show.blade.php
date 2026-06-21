<x-app-layout>
    <div class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @if (session('success'))
                <div class="rounded-2xl border border-green-300/20 bg-green-400/10 p-4 text-sm font-semibold text-green-200">
                    {{ session('success') }}
                </div>
            @endif

            <section class="rounded-[2rem] border border-white/10 bg-white/[0.06] p-8 backdrop-blur-xl">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-cyan-200">Detail Kelas</p>
                        <h1 class="mt-2 text-3xl font-black text-white">{{ $classGroup->name }}</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-400">
                            {{ $classGroup->description ?: 'Tidak ada deskripsi.' }}
                        </p>
                    </div>

                    <a href="{{ route('dosen.kelas.index') }}"
                       class="rounded-2xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-bold text-white hover:bg-white/10">
                        Kembali
                    </a>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-5">
                        <p class="text-sm text-slate-400">Token Kelas</p>
                        <p class="mt-2 font-mono text-3xl font-black tracking-widest text-white">
                            {{ $classGroup->token }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-5">
                        <p class="text-sm text-slate-400">KKM</p>
                        <p class="mt-2 text-3xl font-black text-white">{{ $classGroup->kkm }}</p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-5">
                        <p class="text-sm text-slate-400">Jumlah Mahasiswa</p>
                        <p class="mt-2 text-3xl font-black text-white">{{ $classGroup->members->count() }}</p>
                    </div>
                </div>
            </section>

            <section class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                <h2 class="text-xl font-bold text-white">Mahasiswa Tergabung</h2>

                <div class="mt-5 overflow-hidden rounded-2xl border border-white/10">
                    <table class="min-w-full divide-y divide-white/10">
                        <thead class="bg-slate-950/50">
                            <tr>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-400">Nama</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-400">Email</th>
                                <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-400">Bergabung</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-white/10">
                            @forelse ($classGroup->members as $member)
                                <tr>
                                    <td class="px-5 py-4 text-sm font-semibold text-white">
                                        {{ $member->user->name }}
                                    </td>
                                    <td class="px-5 py-4 text-sm text-slate-400">
                                        {{ $member->user->email }}
                                    </td>
                                    <td class="px-5 py-4 text-sm text-slate-400">
                                        {{ $member->joined_at?->format('d M Y H:i') ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-5 py-6 text-center text-sm text-slate-400">
                                        Belum ada mahasiswa yang bergabung.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>