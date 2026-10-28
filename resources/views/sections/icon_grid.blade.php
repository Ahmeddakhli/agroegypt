{{-- Home "Our Products": orange line icons with labels, hang hover. --}}
<x-section :section="$section" bg="dark" class="py-16">
    <div class="container-site">
        @if ($section->title)
            <h2 class="text-center text-[36px] leading-[1.5] font-normal md:text-[48px]">{{ $section->title }}</h2>
        @endif
        <ul class="mt-12 grid grid-cols-2 gap-y-10 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ($section->items as $item)
                <li class="text-center">
                    <a @if ($item->url) href="{{ \App\Support\SiteIndex::link($item->url) }}" @endif class="hover-hang inline-flex flex-col items-center gap-4">
                        <x-img :media="$item->icon ?? $item->media" sizes="64px" class="h-16 w-auto" alt="" />
                        <span class="text-[24px] leading-[34px] font-bold text-orange">{{ $item->title }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</x-section>
