@props(['media', 'sizes' => '100vw', 'lazy' => true, 'alt' => null])
@if ($media)
    <img src="{{ $media->url() }}"
         @if ($media->variants) srcset="{{ $media->srcset() }}" sizes="{{ $sizes }}" @endif
         @if ($media->width) width="{{ $media->width }}" height="{{ $media->height }}" @endif
         alt="{{ $alt ?? $media->altText() }}"
         @if ($lazy) loading="lazy" decoding="async" @else fetchpriority="high" @endif
         {{ $attributes }}>
@endif
