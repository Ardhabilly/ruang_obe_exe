<section class="space-y-8">
    <div class="text-center">
        <p class="text-sm font-bold uppercase tracking-[0.25em] text-cyan-600">
            Pengantar Bab 1
        </p>

        <h2 class="mt-3 text-3xl font-black text-slate-950">
            SISTEM PERSAMAAN LINEAR
        </h2>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <figure class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <figcaption class="mb-3 text-center text-sm font-semibold text-slate-700">
                Gambar 2 Representasi Matriks Transaksi
            </figcaption>

            <div class="rounded-xl bg-slate-100 p-2">
                <img
                    src="{{ asset('images/materi/bab1/representasi-matriks-transaksi.png') }}"
                    alt="Representasi Matriks Transaksi"
                    class="h-auto w-full rounded-xl object-contain"
                >
            </div>
        </figure>

        <figure class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <figcaption class="mb-3 text-center text-sm font-semibold text-slate-700">
                Gambar 1 Pasar Terapung Lok Baintan
            </figcaption>

            <div class="rounded-xl bg-slate-100 p-2">
                <img
                    src="{{ asset('images/materi/bab1/pasar-terapung-lok-baintan.png') }}"
                    alt="Pasar Terapung Lok Baintan"
                    class="h-auto w-full rounded-xl object-contain"
                >
            </div>
        </figure>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="space-y-4 leading-8 text-slate-700">
            <p>
                Pasar Terapung Lok Baintan. Bagi masyarakat, kawasan ini merupakan pusat
                perdagangan tradisional yang hidup di atas riak Sungai Martapura. Namun dalam
                perspektif matematika terapan, aktivitas ekonomi ini sejatinya dapat
                didekonstruksi menjadi jalinan <span class="font-semibold text-slate-950">variabel dan konstanta</span>.
            </p>

            <p>
                Kondisi niaga di atas perahu kelotok kerap memunculkan teka-teki ketika
                pedagang menawarkan paket komoditas gabungan dan hanya menyebutkan harga
                totalnya. Kuncinya bersandar pada konsep Sistem Persamaan Linear.
            </p>

            <p>
                Sistem komputasi tidak pernah mengenali wujud fisik barang ataupun keranjang
                belanjaan. Supaya komparasi nilai tersebut dapat dihitung secara mekanis,
                kompleksitas transaksi riil harus diekstrak dan disederhanakan menjadi
                susunan angka berwujud baris dan kolom. Formasi numerik inilah yang
                dinamakan matriks.
            </p>

            <p>
                Pada bagian ini, mahasiswa akan mempelajari konsep awal mengenai Sistem
                Persamaan Linear. Materi ini merupakan titik awal dalam mempelajari Aljabar
                Linear, karena hampir seluruh pembahasan selanjutnya akan menggunakan SPL
                sebagai dasar pemikiran.
            </p>

            <p>
                Sebelum masuk ke teknik penyelesaian menggunakan matriks dan Operasi Baris
                Elementer, mahasiswa perlu memahami apa yang dimaksud dengan Sistem Persamaan
                Linear, bagaimana bentuknya, serta apa yang dimaksud dengan solusi suatu
                sistem. Oleh karena itu, pada bagian pengantar ini pembahasan difokuskan pada
                pemahaman konsep dasar SPL secara bertahap.
            </p>
        </div>
    </div>

    <!-- TUJUAN_PEMBELAJARAN_BAB_1_V1 -->
    <div class="rounded-2xl border border-cyan-200 bg-cyan-50 p-6">
        <p class="text-sm font-bold uppercase tracking-[0.18em] text-cyan-700">
            Tujuan Pembelajaran Bab 1
        </p>

        <h3 class="mt-2 text-xl font-black text-slate-950">
            Setelah mempelajari bab ini, mahasiswa diharapkan mampu:
        </h3>

        <ol class="mt-5 space-y-4 text-slate-700">
            <li class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-cyan-600 text-sm font-black text-white">
                    1
                </span>

                <span class="leading-7">
                    Membedakan persamaan linear dan non-linear berdasarkan karakteristik aljabarnya.
                </span>
            </li>

            <li class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-cyan-600 text-sm font-black text-white">
                    2
                </span>

                <span class="leading-7">
                    Mengidentifikasi komponen dan bentuk umum Sistem Persamaan Linear, meliputi
                    koefisien, variabel, konstanta, serta SPL homogen dan non-homogen.
                </span>
            </li>

            <li class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-cyan-600 text-sm font-black text-white">
                    3
                </span>

                <span class="leading-7">
                    Menganalisis kemungkinan solusi SPL, yaitu solusi tunggal, banyak solusi,
                    atau tidak memiliki solusi.
                </span>
            </li>

            <li class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-cyan-600 text-sm font-black text-white">
                    4
                </span>

                <span class="leading-7">
                    Menyelesaikan SPL sederhana dengan metode eliminasi dan substitusi sebagai
                    pengantar penyelesaian sistem.
                </span>
            </li>

            <li class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-cyan-600 text-sm font-black text-white">
                    5
                </span>

                <span class="leading-7">
                    Merepresentasikan SPL ke dalam matriks koefisien, bentuk \(Ax=b\), dan
                    matriks diperbesar (<span class="italic">augmented matrix</span>).
                </span>
            </li>
        </ol>
    </div>
</section>