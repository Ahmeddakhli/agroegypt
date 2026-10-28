@php($gaId = (string) $setting('analytics.ga_measurement_id'))
@php($consentRequired = $setting('cookies.enabled', '0') === '1')
@if (preg_match('/^G-[A-Z0-9]{4,12}$/', $gaId) && (! $consentRequired || request()->cookie('cookie_consent') === 'accepted'))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','{{ $gaId }}');</script>
@endif
