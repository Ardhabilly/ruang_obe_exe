<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RuangOBE - Media Pembelajaran Operasi Baris Elementer</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800,900" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-950 font-sans text-white antialiased">
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -top-40 left-1/2 h-96 w-96 -translate-x-1/2 rounded-full bg-cyan-400/20 blur-3xl"></div>
        <div class="absolute left-[-120px] top-40 h-80 w-80 rounded-full bg-blue-500/10 blur-3xl"></div>
        <div class="absolute bottom-[-160px] right-[-120px] h-96 w-96 rounded-full bg-violet-500/20 blur-3xl"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(34,211,238,0.10),transparent_35%),linear-gradient(to_bottom,rgba(15,23,42,0.10),rgba(2,6,23,1))]"></div>
    </div>

    <div class="relative z-10">
        <header class="sticky top-0 z-50 border-b border-white/10 bg-slate-950/75 backdrop-blur-xl">
            <nav class="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <a href="{{ route('landing') }}" class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl border border-cyan-300/30 bg-cyan-400/10 shadow-lg shadow-cyan-500/10">
                        <span class="text-lg font-black text-cyan-300">R</span>
                    </div>

                    <div>
                        <div class="text-lg font-extrabold tracking-tight text-white">
                            RuangOBE
                        </div>
                        <div class="text-xs font-medium text-cyan-200/70">
                            Elementary Row Operations Learning Space
                        </div>
                    </div>
                </a>

                <div class="hidden items-center gap-8 md:flex">
                    <a href="#fitur" class="text-sm font-semibold text-slate-300 hover:text-cyan-200">
                        Fitur
                    </a>

                    <a href="#alur" class="text-sm font-semibold text-slate-300 hover:text-cyan-200">
                        Alur Belajar
                    </a>

                    <a href="#materi" class="text-sm font-semibold text-slate-300 hover:text-cyan-200">
                        Materi
                    </a>
                </div>

                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="rounded-2xl bg-cyan-400 px-5 py-3 text-sm font-black text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-300">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="hidden rounded-2xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10 sm:inline-flex">
                            Masuk
                        </a>

                        <a href="{{ route('register') }}"
                           class="rounded-2xl bg-cyan-400 px-5 py-3 text-sm font-black text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-300">
                            Daftar
                        </a>
                    @endauth
                </div>
            </nav>
        </header>

        <main>
            <section class="mx-auto grid min-h-[calc(100vh-5rem)] max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[1.15fr_0.85fr] lg:px-8">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-cyan-300/20 bg-cyan-400/10 px-4 py-2 text-sm font-bold text-cyan-200">
                        <span class="h-2 w-2 rounded-full bg-cyan-300"></span>
                        Media Pembelajaran Operasi Baris Elementer
                    </div>

                    <h1 class="mt-7 max-w-4xl text-5xl font-black tracking-tight text-white sm:text-6xl lg:text-7xl">
                        Pusat Pembelajaran
                        <span class="bg-gradient-to-r from-cyan-300 via-blue-400 to-violet-400 bg-clip-text text-transparent">
                            Operasi Baris Elementer
                        </span>
                        {{-- dan terarah. --}}
                    </h1>

                    <p class="mt-6 max-w-2xl text-base leading-8 text-slate-300 sm:text-lg">
                        RuangOBE membantu mahasiswa memahami Sistem Persamaan Linear,
                        Operasi Baris Elementer, Eliminasi Gauss, dan Gauss-Jordan melalui
                        materi bertahap, latihan wajib, serta kuis berbasis kelas.
                    </p>

                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        @auth
                            <a href="{{ route('dashboard') }}"
                               class="inline-flex justify-center rounded-2xl bg-cyan-400 px-7 py-4 text-sm font-black text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-300">
                                Masuk ke Dashboard
                            </a>
                        @else
                            <a href="{{ route('register') }}"
                               class="inline-flex justify-center rounded-2xl bg-cyan-400 px-7 py-4 text-sm font-black text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-300">
                                Mulai Belajar
                            </a>

                            <a href="{{ route('login') }}"
                               class="inline-flex justify-center rounded-2xl border border-white/10 bg-white/5 px-7 py-4 text-sm font-black text-white transition hover:bg-white/10">
                                Masuk ke Akun
                            </a>
                        @endauth
                    </div>

                    <div class="mt-10 grid max-w-2xl grid-cols-3 gap-3">
                        <div class="rounded-2xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur-xl">
                            <p class="text-2xl font-black text-white">4</p>
                            <p class="mt-1 text-xs font-medium text-slate-400">Bab Materi</p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur-xl">
                            <p class="text-2xl font-black text-white">12</p>
                            <p class="mt-1 text-xs font-medium text-slate-400">Subbab</p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur-xl">
                            <p class="text-2xl font-black text-white">KKM</p>
                            <p class="mt-1 text-xs font-medium text-slate-400">Per Kelas</p>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <div class="absolute -inset-6 rounded-[2.5rem] bg-cyan-400/10 blur-3xl"></div>

                    <div class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-white/[0.06] p-6 shadow-2xl shadow-cyan-950/40 backdrop-blur-xl">
                        <div class="rounded-[1.5rem] border border-cyan-300/20 bg-slate-950/60 p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-cyan-200">
                                        Simulasi Operasi Baris Elementer
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        Representasi SPL menuju matriks
                                    </p>
                                </div>

                                <span class="rounded-full bg-green-400/10 px-3 py-1 text-xs font-bold text-green-200">
                                    Aktif
                                </span>
                            </div>

                            <div class="mt-6 space-y-3">
                                <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                                    <p class="font-mono text-sm text-slate-300">
                                        3x + 2y + z = 80.000
                                    </p>
                                </div>

                                <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                                    <p class="font-mono text-sm text-slate-300">
                                        x + 4y + 2z = 100.000
                                    </p>
                                </div>

                                <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                                    <p class="font-mono text-sm text-slate-300">
                                        2x + y + 3z = 90.000
                                    </p>
                                </div>
                            </div>

                            <div class="mt-6 rounded-2xl border border-cyan-300/20 bg-cyan-400/10 p-5">
                                <p class="text-xs font-bold uppercase tracking-wide text-cyan-200">
                                    Bentuk Matriks
                                </p>

                                <p class="mt-3 text-center font-mono text-lg font-black text-white">
                                    [A][X] = [B]
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-2 gap-4">
                            <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                                <p class="text-xs text-slate-400">Progress</p>
                                <p class="mt-2 text-3xl font-black text-white">0%</p>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                                <p class="text-xs text-slate-400">Nilai Latihan</p>
                                <p class="mt-2 text-3xl font-black text-white">-</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="fitur" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <p class="text-sm font-bold uppercase tracking-[0.25em] text-cyan-300">
                        Fitur Utama
                    </p>

                    <h2 class="mt-3 text-4xl font-black tracking-tight text-white">
                        Dibuat untuk pembelajaran yang terstruktur.
                    </h2>

                    <p class="mt-4 text-sm leading-7 text-slate-400">
                        RuangOBE menggabungkan materi, latihan, kuis, kelas, dan analitik
                        agar proses belajar dapat dipantau dengan lebih jelas.
                    </p>
                </div>

                <div class="mt-10 grid gap-5 md:grid-cols-3">
                    <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-400/10 text-lg font-black text-cyan-300">
                            01
                        </div>

                        <h3 class="mt-5 text-xl font-black text-white">
                            Materi Berurutan
                        </h3>

                        <p class="mt-3 text-sm leading-6 text-slate-400">
                            Mahasiswa perlu menyelesaikan materi dan aktivitas wajib sebelum lanjut ke subbab berikutnya.
                        </p>
                    </div>

                    <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-400/10 text-lg font-black text-blue-300">
                            02
                        </div>

                        <h3 class="mt-5 text-xl font-black text-white">
                            Kelas dengan Token
                        </h3>

                        <p class="mt-3 text-sm leading-6 text-slate-400">
                            Mahasiswa masuk ke kelas menggunakan token. Kuis hanya dapat diakses setelah tergabung di kelas.
                        </p>
                    </div>

                    <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-400/10 text-lg font-black text-violet-300">
                            03
                        </div>

                        <h3 class="mt-5 text-xl font-black text-white">
                            Progress
                        </h3>

                        <p class="mt-3 text-sm leading-6 text-slate-400">
                            Progress materi, nilai latihan, nilai kuis, dan aktivitas mahasiswa dapat dipantau oleh dosen.
                        </p>
                    </div>
                </div>
            </section>

            <section id="alur" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="rounded-[2rem] border border-white/10 bg-white/[0.06] p-8 backdrop-blur-xl">
                    <div class="grid gap-8 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
                        <div>
                            <p class="text-sm font-bold uppercase tracking-[0.25em] text-cyan-300">
                                Alur Belajar
                            </p>

                            <h2 class="mt-3 text-4xl font-black text-white">
                                Dari kelas sampai evaluasi.
                            </h2>

                            <p class="mt-4 text-sm leading-7 text-slate-400">
                                Alur ini dirancang agar mahasiswa tidak langsung menuju kuis,
                                tetapi mempelajari materi dan latihan terlebih dahulu.
                            </p>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-5">
                                <p class="text-sm font-black text-cyan-200">1. Daftar Akun</p>
                                <p class="mt-2 text-sm leading-6 text-slate-400">
                                    Mahasiswa membuat akun dan masuk ke sistem RuangOBE.
                                </p>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-5">
                                <p class="text-sm font-black text-cyan-200">2. Gabung Kelas</p>
                                <p class="mt-2 text-sm leading-6 text-slate-400">
                                    Mahasiswa memasukkan token kelas yang diberikan oleh dosen.
                                </p>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-5">
                                <p class="text-sm font-black text-cyan-200">3. Selesaikan Materi</p>
                                <p class="mt-2 text-sm leading-6 text-slate-400">
                                    Materi dibuka berurutan berdasarkan progress belajar mahasiswa.
                                </p>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-5">
                                <p class="text-sm font-black text-cyan-200">4. Kerjakan Kuis</p>
                                <p class="mt-2 text-sm leading-6 text-slate-400">
                                    Kuis dikelola dosen dan KKM dapat berbeda pada setiap kelas.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="materi" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="text-center">
                    <p class="text-sm font-bold uppercase tracking-[0.25em] text-cyan-300">
                        Materi
                    </p>

                    <h2 class="mt-3 text-4xl font-black text-white">
                        Topik Pembelajaran RuangOBE
                    </h2>

                    <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-slate-400">
                        Materi disusun sesuai urutan pembelajaran Operasi Baris Elementer dari konsep dasar
                        sampai penyelesaian Metode Eliminasi Gauss & Gauss-Jordan.
                    </p>
                </div>

                <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                        <p class="text-sm font-black text-cyan-200">Bab 1</p>
                        <h3 class="mt-3 text-xl font-black text-white">
                            Sistem Persamaan Linear
                        </h3>
                        <p class="mt-3 text-sm leading-6 text-slate-400">
                            Pengertian SPL, bentuk umum, kemungkinan solusi, dan representasi matriks.
                        </p>
                    </div>

                    <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                        <p class="text-sm font-black text-cyan-200">Bab 2</p>
                        <h3 class="mt-3 text-xl font-black text-white">
                            Operasi Baris Elementer
                        </h3>
                        <p class="mt-3 text-sm leading-6 text-slate-400">
                            Konsep OBE dan jenis operasi pada baris matriks.
                        </p>
                    </div>

                    <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                        <p class="text-sm font-black text-cyan-200">Bab 3</p>
                        <h3 class="mt-3 text-xl font-black text-white">
                            Eliminasi Gauss
                        </h3>
                        <p class="mt-3 text-sm leading-6 text-slate-400">
                            Matriks eselon baris dan penyelesaian SPL dengan Eliminasi Gauss.
                        </p>
                    </div>

                    <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6 backdrop-blur-xl">
                        <p class="text-sm font-black text-cyan-200">Bab 4</p>
                        <h3 class="mt-3 text-xl font-black text-white">
                            Gauss-Jordan
                        </h3>
                        <p class="mt-3 text-sm leading-6 text-slate-400">
                            Matriks eselon baris tereduksi dan penyelesaian SPL sampai bentuk akhir.
                        </p>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="relative overflow-hidden rounded-[2rem] border border-cyan-300/20 bg-cyan-400/10 p-8 text-center backdrop-blur-xl">
                    <div class="absolute left-1/2 top-0 h-72 w-72 -translate-x-1/2 rounded-full bg-cyan-400/20 blur-3xl"></div>

                    <div class="relative">
                        <h2 class="text-4xl font-black tracking-tight text-white">
                            Siap memulai pembelajaran?
                        </h2>

                        <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-slate-300">
                            Daftar sebagai mahasiswa, masuk ke kelas menggunakan token dari dosen,
                            lalu mulai belajar materi Operasi Baris Elementer secara bertahap.
                        </p>

                        <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                            @auth
                                <a href="{{ route('dashboard') }}"
                                   class="rounded-2xl bg-cyan-400 px-7 py-4 text-sm font-black text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-300">
                                    Buka Dashboard
                                </a>
                            @else
                                <a href="{{ route('register') }}"
                                   class="rounded-2xl bg-cyan-400 px-7 py-4 text-sm font-black text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-300">
                                    Daftar Sekarang
                                </a>

                                <a href="{{ route('login') }}"
                                   class="rounded-2xl border border-white/10 bg-white/5 px-7 py-4 text-sm font-black text-white transition hover:bg-white/10">
                                    Masuk
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-white/10 py-8">
            <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 text-sm text-slate-500 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
                <p>
                    © {{ date('Y') }} RuangOBE. Media Pembelajaran Operasi Baris Elementer.
                </p>

                <p>
                    Sistem Persamaan Linear · OBE · Gauss · Gauss-Jordan
                </p>
            </div>
        </footer>
    </div>
</body>
</html>