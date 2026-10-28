{{-- Google Maps embed; only https://www.google.com/maps/embed URLs are accepted. --}}
@php($src = (string) $section->button_url)
@if (str_starts_with($src, 'https://www.google.com/maps/embed'))
    <section id="section-{{ $section->id }}">
        <iframe src="{{ $src }}" title="{{ $section->title ?: __('site.map') }}" class="block h-[450px] w-full border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
    </section>
@endif
