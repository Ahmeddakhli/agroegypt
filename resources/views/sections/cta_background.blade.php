{{-- Catalogue download: full-width image background with centred heading and small button. --}}
@php
    $bg = $section->media?->url(1920);
    $documentUrl = $data?->file?->url();
    $href = $documentUrl ?? \App\Support\SiteIndex::link($section->button_url);
    $isDownload = $documentUrl !== null || str_ends_with(strtolower(parse_url((string) $section->button_url, PHP_URL_PATH) ?? ''), '.pdf');
@endphp
<section id="section-{{ $section->id }}" class="relative flex min-h-[420px] items-start justify-center bg-dark bg-cover bg-center pt-28 text-white md:min-h-[591px]"
         @if ($bg) style="background-image: url('{{ $bg }}')" @endif @if ($section->setting('animation', 'none') !== 'none') data-reveal="{{ $section->setting('animation') }}" @endif>
    <div class="container-site text-center">
        @if ($section->title)
            <h2 class="text-[34px] leading-[1.3] font-bold md:text-[48px]">{{ $section->title }}</h2>
        @endif
        @if ($section->subtitle)
            <p class="mx-auto mt-4 max-w-2xl">{{ $section->subtitle }}</p>
        @endif
        @if ($section->button_label && $href)
            <a href="{{ $href }}" @if ($isDownload) download @endif
               class="mt-5 inline-block bg-primary px-1.5 py-px text-[14px] leading-[20px] font-medium text-white uppercase hover:bg-primary-hover">{{ $section->button_label }}</a>
        @endif
    </div>
</section>
