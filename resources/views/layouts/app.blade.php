<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'WordStockt - Multiplayer Word Game')</title>
    <meta name="description" content="@yield('description', 'Challenge friends to a battle of words! WordStockt is a free multiplayer word game for iOS and Android. No ads, fair rules, and real-time gameplay.')">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('og_title', 'WordStockt - Multiplayer Word Game')">
    <meta property="og:description" content="@yield('og_description', 'Challenge friends to a battle of words! WordStockt is a free multiplayer word game for iOS and Android. No ads, fair rules, and real-time gameplay.')">
    <meta property="og:image" content="@yield('og_image', asset('og-image.png'))">
    <meta property="og:site_name" content="WordStockt">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="@yield('og_title', 'WordStockt - Multiplayer Word Game')">
    <meta name="twitter:description" content="@yield('og_description', 'Challenge friends to a battle of words! WordStockt is a free multiplayer word game for iOS and Android. No ads, fair rules, and real-time gameplay.')">
    <meta name="twitter:image" content="@yield('og_image', asset('og-image.png'))">

    <!-- Theme & Mobile -->
    <meta name="theme-color" content="#0D1B2A">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <!-- Apple Smart App Banner -->
    @if(config('app.ios_app_id'))
    <meta name="apple-itunes-app" content="app-id={{ config('app.ios_app_id') }}">
    @endif

    <!-- Canonical URL -->
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="96x96" href="/assets/logos/android-96.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/css/landing.css'])
</head>
<body class="min-h-screen" style="background-color: var(--color-background); color: var(--color-text-primary);">
    {{-- Global animated background --}}
    <div class="global-gradient"></div>
    <div class="global-orb global-orb-1"></div>
    <div class="global-orb global-orb-2"></div>
    <div class="global-orb global-orb-3"></div>

    @yield('content')

    @vite(['resources/js/landing.js'])
</body>
</html>
