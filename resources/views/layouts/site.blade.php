<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $currentLanguage?->direction ?? 'ltr' }}" @class(['no-animations' => ! $animationsEnabled, 'no-hover-effects' => ! $hoverEffects])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seo->title ?? $setting('company.name', config('app.name')) }}</title>
    @isset($seo)
        @if ($seo->description)<meta name="description" content="{{ $seo->description }}">@endif
        @if ($seo->keywords)<meta name="keywords" content="{{ $seo->keywords }}">@endif
        @if ($seo->noindex)<meta name="robots" content="noindex, nofollow">@endif
        <link rel="canonical" href="{{ $seo->canonical }}">
        @foreach ($seo->alternates as $code => $href)
            <link rel="alternate" hreflang="{{ $code }}" href="{{ $href }}">
        @endforeach
        @if ($seo->alternates)
            <link rel="alternate" hreflang="x-default" href="{{ $seo->alternates[\App\Models\Language::defaultCode()] ?? reset($seo->alternates) }}">
        @endif
        <meta property="og:type" content="{{ $seo->type }}">
        <meta property="og:title" content="{{ $seo->title }}">
        @if ($seo->description)<meta property="og:description" content="{{ $seo->description }}">@endif
        <meta property="og:url" content="{{ $seo->canonical }}">
        <meta property="og:locale" content="{{ app()->getLocale() }}">
        @if ($seo->image)<meta property="og:image" content="{{ $seo->image }}">@endif
        <meta name="twitter:card" content="summary_large_image">
        @foreach ($seo->jsonLd as $schema)
            <script type="application/ld+json">{!! json_encode(array_filter($schema, fn ($v) => $v !== null), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
        @endforeach
    @endisset
    @if ($favicon = $setting('theme.favicon'))
        <link rel="icon" href="{{ asset('storage/'.$favicon) }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if ($themeCss)
        <style>{!! $themeCss !!}</style>
    @endif
    @include('partials.analytics')
</head>
<body class="flex min-h-screen flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:start-2 focus:top-2 focus:z-[100] focus:bg-white focus:px-4 focus:py-2">{{ __('site.skip_to_content') }}</a>

    @include('partials.header')

    <main id="main" class="grow">
        @hasSection('hero')
            @yield('hero')
        @elseif (! empty($breadcrumbs))
            @include('partials.page-hero')
        @endif

        @yield('content')
    </main>

    @include('partials.footer')
    @include('partials.floating')
    @include('partials.cookie-consent')
</body>
</html>
