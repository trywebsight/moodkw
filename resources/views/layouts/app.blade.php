<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $pageTitle = $title ?? app(\App\Services\SettingsService::class)->getSeoTitle();
    @endphp
    <title>{{ $pageTitle }}</title>
    <x-seo-meta :title="$pageTitle" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="menu-body antialiased">
    @isset($slot)
        {{ $slot }}
    @else
        @yield('content')
    @endisset
    @livewireScripts
    @stack('scripts')
</body>
</html>
