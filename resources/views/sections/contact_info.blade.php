{{-- Contact page: four centred icon + text items (address, phone, fax, email). --}}
<x-section :section="$section" class="py-24">
    <ul class="container-site grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($section->items as $item)
            <li class="flex flex-col items-center gap-5 text-center text-[16px] leading-[33px]">
                <x-img :media="$item->icon ?? $item->media" sizes="48px" class="size-12 object-contain" alt="" />
                @if ($item->url)
                    <a href="{{ \App\Support\SiteIndex::link($item->url) }}" class="hover:text-primary" @if (str_starts_with($item->url, 'tel:')) dir="ltr" @endif>{{ $item->body }}</a>
                @else
                    <span class="max-w-[270px]">{!! nl2br(e($item->body)) !!}</span>
                @endif
            </li>
        @endforeach
    </ul>
</x-section>
