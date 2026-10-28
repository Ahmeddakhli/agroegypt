{{-- Home features: 2x2 icon boxes; each box runs the section's entrance animation (fade on the reference). --}}
@php($animation = $section->setting('animation', 'fade'))
<x-section :section="$section" bg="dark-2" :reveal="false" class="section-y">
    <div class="container-site">
        @if ($section->title)
            <h2 class="mb-12 text-center text-[36px] md:text-[48px]">{{ $section->title }}</h2>
        @endif
        <div class="mx-auto grid max-w-[1040px] gap-x-6 gap-y-12 md:grid-cols-2">
            @foreach ($section->items as $item)
                <div class="flex flex-col items-start gap-4 sm:flex-row" @if (in_array($animation, ['fade', 'slide', 'zoom'], true)) data-reveal="{{ $animation }}" @endif>
                    <x-img :media="$item->icon ?? $item->media" sizes="80px" class="size-20 shrink-0 object-contain" alt="" />
                    <div>
                        <h3 class="text-[24px] leading-[34px]">{{ $item->title }}</h3>
                        <p class="mt-1 text-[16px] leading-[24px]">{{ $item->body }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-section>
