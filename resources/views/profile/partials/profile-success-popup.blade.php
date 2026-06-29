{{-- PROFILE_SUCCESS_POPUP_V1 --}}
@php
    $profilePopupStatus = session('status');

    $isProfileUpdated = $profilePopupStatus === 'profile-updated';
    $isPasswordUpdated = $profilePopupStatus === 'password-updated';

    $popupTitle = $isProfileUpdated
        ? 'Profil berhasil disimpan'
        : 'Kata sandi berhasil diperbarui';

    $popupMessage = $isProfileUpdated
        ? 'Informasi akun Anda telah diperbarui.'
        : 'Gunakan kata sandi baru saat masuk kembali.';
@endphp

@if ($isProfileUpdated || $isPasswordUpdated)
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <div
        x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 4200)"
        x-show="show"
        x-cloak
        x-transition:enter="transform transition ease-out duration-300"
        x-transition:enter-start="translate-x-8 opacity-0"
        x-transition:enter-end="translate-x-0 opacity-100"
        x-transition:leave="transform transition ease-in duration-200"
        x-transition:leave-start="translate-x-0 opacity-100"
        x-transition:leave-end="translate-x-8 opacity-0"
        class="fixed right-4 top-20 z-[120] w-[calc(100%-2rem)] max-w-md"
        role="status"
        aria-live="polite"
    >
        <div class="flex items-start gap-3 rounded-2xl border bg-slate-900/95 p-4 shadow-2xl shadow-slate-950/60 backdrop-blur-xl {{ $isProfileUpdated ? 'border-cyan-300/25' : 'border-violet-300/25' }}">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $isProfileUpdated ? 'bg-cyan-400/15 text-cyan-200' : 'bg-violet-400/15 text-violet-200' }}">
                <svg class="h-6 w-6" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m5 12 4.5 4.5L19 7" />
                </svg>
            </span>

            <div class="min-w-0 flex-1 pr-2">
                <p class="text-sm font-black text-white">
                    {{ $popupTitle }}
                </p>

                <p class="mt-1 text-sm leading-5 text-slate-400">
                    {{ $popupMessage }}
                </p>
            </div>

            <button
                type="button"
                @click="show = false"
                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-white/[0.08] hover:text-white"
                aria-label="Tutup notifikasi"
            >
                <svg class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-width="2" d="m6 18 12-12M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
@endif
