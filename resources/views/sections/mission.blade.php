{{-- About "Our Mission": background slideshow (section items' images) under a dark overlay, centred text. --}}
@php($images = $section->items->pluck('media')->filter()->values())
<section id="section-{{ $section->id }}" class="relative overflow-hidden bg-deep text-white"
         @if ($images->count() > 1) x-data="slider({ count: {{ $images->count() }}, interval: 5000 })" @endif>
    @foreach ($images as $image)
        <div class="absolute inset-0 bg-cover bg-center transition-opacity duration-[1500ms]" style="background-image: url('{{ $image->url(1920) }}')"
             @if ($images->count() > 1) :class="current === {{ $loop->index }} ? 'opacity-100' : 'opacity-0'" @endif aria-hidden="true"></div>
    @endforeach
    <div class="absolute inset-0 bg-deep/90" aria-hidden="true"></div>
    <div class="container-site relative py-10 pb-28 text-center" @if ($section->setting('animation', 'none') !== 'none') data-reveal="{{ $section->setting('animation') }}" @endif>
        <h2 class="heading-display">{{ $section->title }}</h2>
        @include('partials.divider', ['class' => 'mx-auto mt-2 w-full max-w-[640px]'])
        <div class="prose-site mt-10 text-justify">{!! \App\Support\Html::clean($section->body) !!}</div>
    </div>
</section>
