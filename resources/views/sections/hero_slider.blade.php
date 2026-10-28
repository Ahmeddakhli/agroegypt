{{-- Full-width hero slider (reference: Revolution Slider, 813/600/450px, 5s, zoom-fade). --}}
@if ($data && $data->activeSlides->isNotEmpty())
    @php($slides = $data->activeSlides)
    <section x-data="slider({ count: {{ $slides->count() }}, interval: {{ (int) $data->interval_ms }}, autoplay: {{ $data->autoplay ? 'true' : 'false' }} })"
             @touchstart.passive="touchStart($event)" @touchend="touchEnd($event)" @mouseenter="stop()" @mouseleave="play()"
             @keydown.arrow-right="document.dir === 'rtl' ? prev() : next()" @keydown.arrow-left="document.dir === 'rtl' ? next() : prev()"
             class="relative overflow-hidden bg-dark text-white h-[var(--h-m)] md:h-[var(--h-t)] lg:h-[var(--h-d)]"
             style="--h-d: {{ (int) $data->height_desktop }}px; --h-t: {{ (int) $data->height_tablet }}px; --h-m: {{ (int) $data->height_mobile }}px; --slide-ms: {{ (int) $data->transition_ms }}ms"
             aria-roledescription="carousel" aria-label="{{ $section->title ?: $data->name }}" tabindex="0">
        @foreach ($slides as $slide)
            <div x-show="current === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif
                 x-transition:enter="transition ease-[cubic-bezier(.77,0,.18,1)] duration-[var(--slide-ms)]"
                 @switch($data->effect)
                     @case('slide') x-transition:enter-start="opacity-0 translate-x-full rtl:-translate-x-full" x-transition:enter-end="opacity-100 translate-x-0" @break
                     @case('fade') x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" @break
                     @default x-transition:enter-start="opacity-0 scale-[.7] -translate-y-1/2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 @endswitch
                 x-transition:leave="transition ease-in duration-500" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="absolute inset-0" role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} / {{ $slides->count() }}">
                @if ($slide->media)
                    <picture>
                        @if ($slide->mobileMedia)<source media="(max-width: 767px)" srcset="{{ $slide->mobileMedia->url() }}">@endif
                        <x-img :media="$slide->media" :lazy="! $loop->first" sizes="100vw" alt="" class="absolute inset-0 size-full object-cover" />
                    </picture>
                @endif
                <div class="relative flex h-full flex-col items-center justify-center px-4 text-center">
                    @if ($slide->title)
                        <p class="text-[48px] leading-none font-bold tracking-tight uppercase drop-shadow-sm sm:text-[80px] lg:text-[150px]">{{ $slide->title }}</p>
                    @endif
                    @if ($slide->subtitle)
                        <p class="mt-4 max-w-2xl text-[18px] md:text-[24px]">{{ $slide->subtitle }}</p>
                    @endif
                    @if ($slide->button_label && $slide->url)
                        <a href="{{ \App\Support\SiteIndex::link($slide->url) }}" class="btn-primary mt-8">{{ $slide->button_label }}</a>
                    @endif
                </div>
            </div>
        @endforeach

        @if ($data->show_arrows && $slides->count() > 1)
            <button type="button" @click="prev()" class="absolute start-0 top-1/2 hidden size-[70px] -translate-y-1/2 items-center justify-center rounded-full bg-black/25 transition hover:bg-black/50 min-[600px]:flex" aria-label="{{ __('site.previous') }}">
                <svg class="size-5 rtl:rotate-180" viewBox="0 0 6 10" aria-hidden="true"><path d="M5 1L1 5l4 4" fill="none" stroke="currentColor" stroke-width="1.4"/></svg>
            </button>
            <button type="button" @click="next()" class="absolute end-[30px] top-1/2 hidden size-[70px] -translate-y-1/2 items-center justify-center rounded-full bg-black/25 transition hover:bg-black/50 min-[600px]:flex" aria-label="{{ __('site.next') }}">
                <svg class="size-5 rtl:rotate-180" viewBox="0 0 6 10" aria-hidden="true"><path d="M1 1l4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.4"/></svg>
            </button>
        @endif
    </section>
@endif
