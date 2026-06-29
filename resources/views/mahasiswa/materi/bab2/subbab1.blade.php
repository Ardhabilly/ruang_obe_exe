@include('mahasiswa.materi.bab2.pengantar')
<section class="space-y-6">
    <div class="rounded-2xl border border-cyan-100 bg-cyan-50/70 p-5 sm:p-6">

        <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
            2.1 Pengertian Operasi Baris Elementer
        </h2>

        <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600 sm:text-base">
            Setelah Sistem Persamaan Linear ditulis dalam bentuk matriks diperbesar,
            langkah berikutnya adalah melakukan perubahan pada baris-baris matriks
            secara teratur untuk memperoleh bentuk yang lebih mudah diselesaikan.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h3 class="text-lg font-black text-slate-900">
            Definisi Operasi Baris Elementer
        </h3>

        <p class="mt-3 text-sm leading-7 text-slate-700 sm:text-base">
            Operasi Baris Elementer atau OBE merupakan serangkaian operasi dasar
            yang diterapkan pada baris-baris suatu matriks. Operasi ini digunakan
            untuk mengubah bentuk matriks secara bertahap tanpa mengubah makna
            Sistem Persamaan Linear yang direpresentasikan oleh matriks tersebut.
        </p>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-100 text-lg font-black text-violet-700">
                1
            </div>

            <h3 class="mt-4 text-base font-black text-slate-900">
                Mengubah Bentuk Matriks
            </h3>

            <p class="mt-2 text-sm leading-7 text-slate-600">
                OBE digunakan untuk mengubah matriks diperbesar menjadi bentuk
                eselon baris atau bentuk eselon baris tereduksi agar penyelesaian
                SPL dapat ditemukan dengan lebih mudah dan sistematis.
            </p>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-lg font-black text-emerald-700">
                2
            </div>

            <h3 class="mt-4 text-base font-black text-slate-900">
                Menjaga Himpunan Penyelesaian
            </h3>

            <p class="mt-2 text-sm leading-7 text-slate-600">
                Walaupun elemen-elemen pada matriks dapat berubah setelah
                dilakukan OBE, himpunan penyelesaian dari Sistem Persamaan Linear
                tetap sama.
            </p>
        </article>
    </div>

    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:p-6">
        <div class="flex gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-200 text-sm font-black text-amber-800">
                !
            </div>

            <div>
                <h3 class="text-base font-black text-amber-950">
                    Penting untuk Diingat
                </h3>

                <p class="mt-2 text-sm leading-7 text-amber-900">
                    OBE mengubah bentuk matriks, bukan solusi dari SPL. Oleh karena itu,
                    setiap operasi baris harus dilakukan sesuai aturan agar proses
                    penyelesaian tetap benar.
                </p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 sm:p-6">
        <h3 class="text-base font-black text-slate-900">
            Gambaran Sederhana
        </h3>

        <p class="mt-3 text-sm leading-7 text-slate-700">
            Misalkan sebuah SPL telah ditulis dalam bentuk matriks diperbesar.
            Dengan OBE, mahasiswa dapat menyederhanakan elemen-elemen pada matriks
            secara bertahap hingga bentuk matriks tersebut lebih mudah digunakan
            untuk menentukan nilai setiap variabel.
        </p>

        <p class="mt-3 text-sm font-bold leading-7 text-cyan-700">
            Pada subbab berikutnya, mahasiswa akan mempelajari tiga jenis Operasi
            Baris Elementer beserta notasinya.
        </p>
    </div>
</section>