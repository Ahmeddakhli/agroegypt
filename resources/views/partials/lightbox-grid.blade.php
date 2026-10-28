@php($lightboxImages = $images->map(fn ($m) => ['src' => $m->url(), 'alt' => $m->altText()])->values())
<div x-data="lightbox(@js($lightboxImages))" @keydown.escape.window="isOpen && close()" @keydown.arrow-right.window="isOpen && (document.dir === 'rtl' ? prev() : next())" @keydown.arrow-left.window="isOpen && (document.dir === 'rtl' ? next() : prev())">
    <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        @foreach ($images as $image)
            <li>
                <button type="button" @click="open({{ $loop->index }}, $event)" class="group block w-full overflow-hidden" aria-label="{{ __('site.open_image', ['name' => $image->altText()]) }}">
                    <x-img :media="$image" sizes="(min-width: 1024px) 280px, 50vw" class="aspect-[4/3] w-full object-cover transition duration-300 group-hover:scale-105" />
                </button>
            </li>
        @endforeach
    </ul>

    <div x-cloak x-show="isOpen" x-transition.opacity class="fixed inset-0 z-[90] flex items-center justify-center bg-black/90 p-4" role="dialog" aria-modal="true" aria-label="{{ __('site.image_viewer') }}" @click.self="close()">
        <img :src="image?.src" :alt="image?.alt" class="max-h-[85vh] max-w-full object-contain">
        <button type="button" x-ref="close" @click="close()" class="absolute end-4 top-4 flex size-11 items-center justify-center text-3xl text-white" aria-label="{{ __('site.close') }}">&times;</button>
        <button type="button" @click="prev()" class="absolute start-2 top-1/2 flex size-12 -translate-y-1/2 items-center justify-center text-white" aria-label="{{ __('site.previous') }}">
            <svg class="size-6 rtl:rotate-180" viewBox="0 0 6 10" aria-hidden="true"><path d="M5 1L1 5l4 4" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
        </button>
        <button type="button" @click="next()" class="absolute end-2 top-1/2 flex size-12 -translate-y-1/2 items-center justify-center text-white" aria-label="{{ __('site.next') }}">
            <svg class="size-6 rtl:rotate-180" viewBox="0 0 6 10" aria-hidden="true"><path d="M1 1l4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
        </button>
        <p class="absolute bottom-4 text-[14px] text-white/80" x-text="(index + 1) + ' / ' + images.length"></p>
    </div>
</div>
