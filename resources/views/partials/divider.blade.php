{{-- Orange line + fruit icon divider used on About, Brands and Markets. --}}
@php($icon = $setting('theme.divider_icon'))
<div class="flex items-center justify-center gap-3 {{ $class ?? '' }}" aria-hidden="true">
    <span class="h-px grow bg-orange/80"></span>
    @if ($icon)
        <img src="{{ asset('storage/'.$icon) }}" alt="" class="size-6" loading="lazy">
    @else
        <svg class="size-6 fill-none stroke-orange" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="15" r="6"/><path d="M12 9V4l4-2"/></svg>
    @endif
    <span class="h-px grow bg-orange/80"></span>
</div>
