{{--
    Image + text (About "Our History"/"Packing Station", Markets "Shipping"/"Markets").
    settings: image_position start|end, image_offset (px the image rises above the section), layout default|split_heading
--}}
@php
    $imageEnd = $section->setting('image_position', 'start') === 'end';
    $offset = (int) $section->setting('image_offset', 0);
    $split = $section->setting('layout') === 'split_heading';
    $hasImage = $section->media && ! $section->setting('media_as_background', false);
@endphp
<x-section :section="$section" bg="dark" class="section-y">
    <div class="container-site">
        @if ($split)
            <div class="grid items-center gap-8 md:grid-cols-[220px_1fr] md:gap-12">
                <div class="text-center">
                    <h2 class="text-[32px] leading-[1.2] font-bold">{{ $section->title }}</h2>
                    @include('partials.divider', ['class' => 'mx-auto mt-1 w-[110px]'])
                </div>
                <div class="prose-site text-justify text-[16px] leading-[27px]">{!! \App\Support\Html::clean($section->body) !!}</div>
            </div>
        @else
            <div @class(['grid items-start gap-10', 'md:grid-cols-[minmax(0,4fr)_minmax(0,8fr)]' => $hasImage && ! $imageEnd, 'md:grid-cols-[minmax(0,7fr)_minmax(0,4fr)] md:gap-5' => $hasImage && $imageEnd, 'md:grid-cols-2' => ! $hasImage])>
                @if ($hasImage)
                    <figure @class(['text-center', 'md:order-last' => $imageEnd]) @if ($offset) style="--offset: -{{ $offset }}px" @endif>
                        <x-img :media="$section->media" sizes="(min-width: 768px) 40vw, 100vw" @class(['mx-auto h-auto max-w-full', 'md:mt-[var(--offset)]' => $offset]) />
                        @if ($section->subtitle)
                            <figcaption class="mt-3 text-[18px]">{{ $section->subtitle }}</figcaption>
                        @endif
                    </figure>
                @endif
                <div>
                    @if ($section->title)
                        <h2 @class(['heading-display' => $hasImage, 'text-[24px] leading-[34px] font-bold' => ! $hasImage, 'mb-6'])>{{ $section->title }}</h2>
                    @endif
                    <div class="prose-site text-justify">{!! \App\Support\Html::clean($section->body) !!}</div>
                    @if ($section->button_label && $section->button_url)
                        <a href="{{ \App\Support\SiteIndex::link($section->button_url) }}" class="btn-primary mt-8">{{ $section->button_label }}</a>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-section>
