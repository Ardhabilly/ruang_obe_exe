<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'RuangOBE') }}</title>

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

    <main class="relative z-10 min-h-screen">
        <x-flash-popup />
        
        {{ $slot }}
    </main>
</body>
</html>