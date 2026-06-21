@php
    $flashMessages = [];

    if (session('success')) {
        $flashMessages[] = [
            'title' => 'Berhasil',
            'message' => session('success'),
            'icon' => '✓',
            'accent' => 'bg-green-400',
            'iconClass' => 'bg-green-400/15 text-green-300 ring-green-300/20',
        ];
    }

    if (session('warning')) {
        $flashMessages[] = [
            'title' => 'Perhatian',
            'message' => session('warning'),
            'icon' => '!',
            'accent' => 'bg-yellow-400',
            'iconClass' => 'bg-yellow-400/15 text-yellow-300 ring-yellow-300/20',
        ];
    }

    if (session('error')) {
        $flashMessages[] = [
            'title' => 'Gagal',
            'message' => session('error'),
            'icon' => '×',
            'accent' => 'bg-red-400',
            'iconClass' => 'bg-red-400/15 text-red-300 ring-red-300/20',
        ];
    }

    if (session('status')) {
        $flashMessages[] = [
            'title' => 'Informasi',
            'message' => session('status'),
            'icon' => 'i',
            'accent' => 'bg-cyan-400',
            'iconClass' => 'bg-cyan-400/15 text-cyan-300 ring-cyan-300/20',
        ];
    }

    if ($errors->any() && ! session('warning') && ! session('error')) {
        $flashMessages[] = [
            'title' => 'Periksa Kembali',
            'message' => 'Ada isian yang belum sesuai. Silakan periksa kembali form yang tersedia.',
            'icon' => '!',
            'accent' => 'bg-red-400',
            'iconClass' => 'bg-red-400/15 text-red-300 ring-red-300/20',
        ];
    }
@endphp

@if (count($flashMessages) > 0)
    <div class="fixed right-4 top-24 z-[9999] w-[calc(100%-2rem)] max-w-sm space-y-3 sm:right-6">
        @foreach ($flashMessages as $index => $flash)
            <div
                x-data="{ show: true }"
                x-init="setTimeout(() => show = false, {{ 4200 + ($index * 400) }})"
                x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-8 opacity-0 scale-95"
                x-transition:enter-end="translate-x-0 opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-x-0 opacity-100 scale-100"
                x-transition:leave-end="translate-x-8 opacity-0 scale-95"
                class="relative overflow-hidden rounded-2xl border border-white/10 bg-slate-950/95 p-4 text-white shadow-2xl shadow-slate-950/50 ring-1 ring-white/10 backdrop-blur-xl"
            >
                <div class="absolute inset-x-0 top-0 h-1 {{ $flash['accent'] }}"></div>

                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-sm font-black ring-1 {{ $flash['iconClass'] }}">
                        {{ $flash['icon'] }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-black text-white">
                            {{ $flash['title'] }}
                        </p>

                        <p class="mt-1 text-sm leading-5 text-slate-300">
                            {{ $flash['message'] }}
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="show = false"
                        class="rounded-lg px-2 py-1 text-sm font-black text-slate-400 transition hover:bg-white/10 hover:text-white">
                        ×
                    </button>
                </div>
            </div>
        @endforeach
    </div>
@endif