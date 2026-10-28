<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ \App\Models\Setting::localized('company.name', config('app.name')) }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-dark px-6 text-center text-white">
    <div>
        <p class="text-[40px] font-bold text-orange">{{ \App\Models\Setting::localized('company.name', config('app.name')) }}</p>
        <p class="mt-4">We are updating our website. Please check back soon. — نقوم بتحديث موقعنا، يرجى العودة قريباً.</p>
        @if ($email = \App\Models\Setting::get('contact.email'))
            <p class="mt-6"><a href="mailto:{{ $email }}" class="text-orange underline">{{ $email }}</a></p>
        @endif
    </div>
</body>
</html>
