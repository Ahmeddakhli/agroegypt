@php($heroMedia = $hero ?? null)
@php($heroUrl = $heroMedia?->url(1920) ?? (($default = $setting('theme.default_hero')) ? asset('storage/'.$default) : null))
<section class="relative flex h-[250px] items-center justify-center overflow-hidden bg-dark bg-cover bg-center md:h-[345px]"
         @if ($heroUrl) style="background-image: url('{{ $heroUrl }}')" @endif>
    <div class="relative px-4 text-center text-white">
        <h1 class="text-[52px] leading-[1.15] font-bold md:text-hero">{{ last($breadcrumbs)[0] }}</h1>
        <nav aria-label="{{ __('site.breadcrumb') }}" class="mt-3">
            <ol class="flex flex-wrap items-center justify-center gap-x-3 text-[18px]">
                @foreach ($breadcrumbs as [$label, $url])
                    <li class="flex items-center gap-3">
                        @if (! $loop->first)<span class="size-1.5 rounded-full bg-primary" aria-hidden="true"></span>@endif
                        @if ($url && ! $loop->last)
                            <a href="{{ $url }}" class="hover:text-orange">{{ $label }}</a>
                        @else
                            <span aria-current="page">{{ $label }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    </div>
</section>
