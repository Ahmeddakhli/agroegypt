{{-- Wrapper applying a section's design settings: background colour/image and entrance animation (reveal=false when the section animates its items instead). --}}
@props(['section', 'bg' => null, 'as' => 'section', 'reveal' => true])
@php
    $palette = ['dark' => 'bg-dark text-white', 'dark-2' => 'bg-dark-2 text-white', 'deep' => 'bg-deep text-white', 'olive' => 'bg-olive text-white', 'white' => 'bg-white text-dark', 'none' => ''];
    $bgKey = $section->setting('background', $bg ?? 'white');
    $animation = $section->setting('animation', 'none');
    $bgImage = $section->media?->isImage() && $section->setting('media_as_background', false) ? $section->media->url(1920) : null;
@endphp
<{{ $as }} id="section-{{ $section->id }}"
    {{ $attributes->class([$palette[$bgKey] ?? '', 'bg-cover bg-center' => $bgImage]) }}
    @if ($bgImage) style="background-image: linear-gradient(rgb(18 28 16 / {{ (int) $section->setting('overlay', 55) }}%), rgb(18 28 16 / {{ (int) $section->setting('overlay', 55) }}%)), url('{{ $bgImage }}')" @endif
    @if ($reveal && in_array($animation, ['fade', 'slide', 'zoom'], true)) data-reveal="{{ $animation }}" @endif>
    {{ $slot }}
</{{ $as }}>
