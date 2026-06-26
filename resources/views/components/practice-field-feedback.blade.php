@props(['feedback' => []])

@php
    $feedback = is_array($feedback) ? $feedback : [];
    $state = $feedback['state'] ?? null;
    $isCheckbox = array_key_exists('option_statuses', $feedback);

    /*
    | Status "empty" pada input teks cukup ditunjukkan oleh warna kuning
    | pada kolom. Tidak perlu kartu feedback per kolom karena akan membuat
    | tampilan terlalu panjang. Untuk checkbox, tetap tampilkan pengingat
    | singkat karena tidak ada warna kuning pada setiap opsi.
    */
    $shouldShow = in_array($state, ['wrong', 'revealed'], true)
        || ($state === 'empty' && $isCheckbox);

    $style = match ($state) {
        'wrong' => [
            'wrapper' => 'border-red-200 bg-red-50 text-red-800',
            'icon' => 'bg-red-100 text-red-600',
            'symbol' => '×',
            'title' => 'Periksa kembali jawaban',
        ],
        'revealed' => [
            'wrapper' => 'border-indigo-200 bg-indigo-50 text-indigo-800',
            'icon' => 'bg-indigo-100 text-indigo-600',
            'symbol' => 'i',
            'title' => 'Jawaban bantuan ditampilkan',
        ],
        default => [
            'wrapper' => 'border-yellow-200 bg-yellow-50 text-yellow-800',
            'icon' => 'bg-yellow-100 text-yellow-600',
            'symbol' => '!',
            'title' => 'Pilih jawaban terlebih dahulu',
        ],
    };

    $message = match ($state) {
        'wrong' => $feedback['message'] ?? 'Jawaban belum tepat. Periksa kembali langkah penyelesaiannya.',
        'revealed' => $feedback['message'] ?? 'Jawaban ditampilkan sebagai bantuan. Pelajari kembali langkah penyelesaiannya.',
        default => 'Centang minimal satu pilihan sebelum melakukan pemeriksaan.',
    };
@endphp

@if ($shouldShow)
    <div class="mt-2 flex items-start gap-2 rounded-xl border px-3 py-2.5 text-xs leading-5 {{ $style['wrapper'] }}" role="status">
        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-xs font-black {{ $style['icon'] }}">
            {{ $style['symbol'] }}
        </span>

        <div class="min-w-0">
            <p class="font-bold">{{ $style['title'] }}</p>
            <p class="mt-0.5 opacity-90">{{ $message }}</p>
        </div>
    </div>
@endif
