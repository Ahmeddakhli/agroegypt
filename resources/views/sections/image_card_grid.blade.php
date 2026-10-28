{{--
    Grid of captioned images (Brands boxes, Gallery hub tiles).
    settings: columns 2-6, caption bold|normal, rounded (bool), divider_after (bool)
--}}
@php
    $columns = ['2' => 'sm:grid-cols-2', '3' => 'sm:grid-cols-3', '4' => 'sm:grid-cols-2 lg:grid-cols-4', '5' => 'sm:grid-cols-3 lg:grid-cols-5', '6' => 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-6'][(string) $section->setting('columns', 3)] ?? 'sm:grid-cols-3';
    $bold = $section->setting('caption', 'bold') === 'bold';
    $rounded = (bool) $section->setting('rounded', false);
@endphp
<x-section :section="$section" class="py-10">
    <div class="container-site">
        @if ($section->title)
            <h2 class="mb-10 text-center text-[36px]">{{ $section->title }}</h2>
        @endif
        <ul class="grid justify-center gap-x-6 gap-y-8 {{ $columns }}">
            @foreach ($section->items as $item)
                <li class="text-center">
                    <a @if ($item->url) href="{{ \App\Support\SiteIndex::link($item->url) }}" @endif class="hover-hang inline-flex flex-col items-center gap-3">
                        <x-img :media="$item->media" sizes="(min-width: 1024px) 360px, 50vw" @class(['h-auto max-w-full', 'rounded-xl' => $rounded]) :alt="$item->title" />
                        @if ($item->title)
                            <span @class(['text-[24px] leading-[34px] font-bold' => $bold, 'text-[18px]' => ! $bold])>{{ $item->title }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
        @if ($section->setting('divider_after'))
            @include('partials.divider', ['class' => 'mt-12'])
        @endif
    </div>
</x-section>
