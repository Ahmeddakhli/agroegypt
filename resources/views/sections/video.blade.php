{{-- YouTube/Vimeo embed (privacy-enhanced) or an uploaded video file. --}}
@php
    $url = (string) $section->button_url;
    $embed = null;
    if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})~', $url, $m)) {
        $embed = 'https://www.youtube-nocookie.com/embed/'.$m[1];
    } elseif (preg_match('~vimeo\.com/(\d+)~', $url, $m)) {
        $embed = 'https://player.vimeo.com/video/'.$m[1];
    }
@endphp
<x-section :section="$section" class="section-y">
    <div class="container-site max-w-[960px]">
        @if ($section->title)
            <h2 class="mb-8 text-center text-[36px]">{{ $section->title }}</h2>
        @endif
        @if ($embed)
            <iframe src="{{ $embed }}" title="{{ $section->title ?: __('site.video') }}" class="aspect-video w-full" loading="lazy" allow="encrypted-media; picture-in-picture" allowfullscreen></iframe>
        @elseif ($section->media && str_starts_with($section->media->mime_type, 'video/'))
            <video src="{{ $section->media->url() }}" controls preload="metadata" class="w-full"></video>
        @endif
    </div>
</x-section>
