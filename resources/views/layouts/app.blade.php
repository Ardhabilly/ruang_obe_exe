<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'RuangOBE') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        window.MathJax = {
            tex: {
                inlineMath: [['\\(', '\\)']],
                displayMath: [['\\[', '\\]']],
            },
            options: {
                skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code'],
            },
        };

        window.renderMathJax = function (element) {
            if (! window.MathJax || ! window.MathJax.typesetPromise) {
                return;
            }

            const targets = element ? [element] : undefined;

            if (window.MathJax.typesetClear && targets) {
                window.MathJax.typesetClear(targets);
            }

            window.MathJax.typesetPromise(targets).catch(() => {});
        };
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml.js"></script>
</head>
<body class="min-h-screen bg-slate-950 font-sans text-slate-100 antialiased">
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -top-40 left-1/2 h-96 w-96 -translate-x-1/2 rounded-full bg-blue-500/20 blur-3xl"></div>
        <div class="absolute left-[-120px] top-40 h-80 w-80 rounded-full bg-cyan-400/10 blur-3xl"></div>
        <div class="absolute bottom-[-140px] right-[-100px] h-96 w-96 rounded-full bg-violet-500/20 blur-3xl"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(59,130,246,0.10),transparent_35%),linear-gradient(to_bottom,rgba(15,23,42,0.10),rgba(2,6,23,1))]"></div>
    </div>

    @php
        $isCbtPage = request()->routeIs('mahasiswa.kuis.attempt');
    @endphp

    <div class="relative min-h-screen {{ $isCbtPage ? 'pt-0' : 'pt-20' }}">
        @unless ($isCbtPage)
            @include('layouts.navigation')
        @endunless

        @isset($header)
            @unless ($isCbtPage)
                <header class="bg-white shadow">
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endunless
        @endisset

        <main>
            {{ $slot }}
        </main>
    </div>

<script>
    document.addEventListener('submit', function (event) {
        const form = event.target;

        if (form.matches('[data-preserve-scroll="true"]')) {
            sessionStorage.setItem('ruangobe_scroll_position', String(window.scrollY));
        }
    });

    window.addEventListener('load', function () {
        const savedPosition = sessionStorage.getItem('ruangobe_scroll_position');

        if (savedPosition !== null) {
            window.scrollTo(0, parseInt(savedPosition));
            sessionStorage.removeItem('ruangobe_scroll_position');
        }
    });
</script>
</body>
</html>
