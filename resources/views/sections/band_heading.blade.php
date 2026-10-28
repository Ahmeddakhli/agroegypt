<x-section :section="$section" bg="olive">
    <div class="container-site py-1.5 md:ps-16">
        <h2 class="text-center text-[30px] leading-[42px] font-bold md:text-start md:text-[36px]">
            @if ($section->button_url)
                <a href="{{ \App\Support\SiteIndex::link($section->button_url) }}" class="hover:text-dark">{{ $section->title }}</a>
            @else
                {{ $section->title }}
            @endif
        </h2>
    </div>
</x-section>
