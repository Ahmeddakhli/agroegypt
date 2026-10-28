{{-- About "Our Company": wide photo straddling the dark section above, then centred heading + text. --}}
<section id="section-{{ $section->id }}" class="pb-16">
    @if ($section->media)
        <div class="bg-[linear-gradient(to_bottom,var(--c-dark)_50%,transparent_50%)]">
            <div class="container-site">
                <x-img :media="$section->media" sizes="(min-width: 1000px) 940px, 100vw" class="mx-auto h-auto w-full max-w-[940px]" />
            </div>
        </div>
    @endif
    <div class="container-site mt-8" @if ($section->setting('animation', 'none') !== 'none') data-reveal="{{ $section->setting('animation') }}" @endif>
        @if ($section->title)
            <h2 class="heading-display text-center">{{ $section->title }}</h2>
        @endif
        <div class="prose-site mt-10 text-justify">{!! \App\Support\Html::clean($section->body) !!}</div>
    </div>
</section>
