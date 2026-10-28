{{-- Gallery hub: album tiles; featured albums (e.g. Packing Station) as rounded cards below. --}}
@php($albums = $data ?? collect())
<x-section :section="$section" class="py-24">
    <div class="container-site">
        <ul class="grid grid-cols-2 gap-x-6 gap-y-10 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ($albums->where('is_featured', false) as $album)
                @if ($url = \App\Support\SiteIndex::url($album))
                    <li class="text-center">
                        <a href="{{ $url }}" class="hover-hang inline-flex flex-col items-center gap-4">
                            <x-img :media="$album->cover" sizes="(min-width: 1024px) 180px, 45vw" class="h-[150px] w-auto object-contain" :alt="$album->title" />
                            <span class="text-[24px] leading-[34px] font-bold">{{ $album->title }}</span>
                        </a>
                    </li>
                @endif
            @endforeach
        </ul>
        @php($featured = $albums->where('is_featured', true)->values())
        @if ($featured->isNotEmpty())
            <ul class="mt-24 flex flex-wrap justify-center gap-10">
                @foreach ($featured as $album)
                    @if ($url = \App\Support\SiteIndex::url($album))
                        <li class="text-center">
                            <a href="{{ $url }}" class="hover-hang inline-flex flex-col items-center gap-4">
                                <x-img :media="$album->cover" sizes="360px" class="h-[180px] w-[360px] rounded-xl object-cover" :alt="$album->title" />
                                <span class="text-[24px] leading-[34px] font-bold">{{ $album->title }}</span>
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        @endif
    </div>
</x-section>
