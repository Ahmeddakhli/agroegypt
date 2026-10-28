@php
    $currentUrl = url()->current();
    $isActive = fn (array $item) => $item['url'] === $currentUrl
        || ($item['children'] && str_starts_with($currentUrl, $item['url'].'/'));
    $logo = $setting('theme.logo');
    $homeUrl = \App\Support\SiteIndex::localizedUrl(app()->getLocale());
    $buttonLabel = $setting('header.button_label');
    $buttonUrl = \App\Support\SiteIndex::link($setting('header.button_url'));
    $sticky = $setting('header.sticky', '0') === '1';
@endphp
<header x-data="{ open: false, scrolled: false }" @keydown.escape.window="open = false"
        @if ($sticky) @scroll.window.passive="scrolled = window.scrollY > 10" :class="scrolled && 'shadow-[0_4px_20px_rgba(0,0,0,0.08)]'" @endif
        @class(['z-50 bg-white transition-shadow', 'sticky top-0' => $sticky, 'relative' => ! $sticky])>
    <div class="container-site flex h-[90px] items-center justify-between gap-6">
        <a href="{{ $homeUrl }}" class="shrink-0" aria-label="{{ $setting('company.name', config('app.name')) }}">
            @if ($logo)
                <img src="{{ asset('storage/'.$logo) }}" alt="{{ $setting('company.name', config('app.name')) }}" width="190" height="72" class="h-auto w-[130px] nav:w-[190px]">
            @else
                <span class="text-2xl font-bold">{{ $setting('company.name', config('app.name')) }}</span>
            @endif
        </a>

        {{-- Desktop navigation --}}
        <nav class="hidden items-center nav:flex" aria-label="{{ __('site.main_navigation') }}">
            <ul class="flex items-center gap-5">
                @foreach ($headerMenu as $item)
                    <li class="group relative">
                        <a href="{{ $item['url'] }}" target="{{ $item['target'] }}"
                           @if ($isActive($item)) aria-current="page" @endif
                           @class(['inline-flex items-center gap-1 text-[18px] leading-[90px] transition-colors hover:text-active', 'text-active' => $isActive($item), 'text-dark' => ! $isActive($item)])>
                            {{ $item['label'] }}
                            @if ($item['children'])
                                <svg class="size-2.5" viewBox="0 0 10 6" aria-hidden="true"><path d="M1 1l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
                            @endif
                        </a>
                        @if ($item['children'])
                            @include('partials.dropdown', ['items' => $item['children'], 'level' => 1])
                        @endif
                    </li>
                @endforeach
            </ul>
            @if ($buttonLabel && $buttonUrl)
                <a href="{{ $buttonUrl }}" class="btn-primary ms-6 !px-5 !py-2.5">{{ $buttonLabel }}</a>
            @endif
            @include('partials.language-switcher')
        </nav>

        {{-- Mobile toggle --}}
        <div class="flex items-center gap-3 nav:hidden">
            @include('partials.language-switcher')
            <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-controls="mobile-menu"
                    class="flex size-11 flex-col items-center justify-center gap-[6px]" aria-label="{{ __('site.toggle_menu') }}">
                <span class="h-0.5 w-6 bg-dark transition" :class="open && 'translate-y-2 rotate-45'"></span>
                <span class="h-0.5 w-6 bg-dark transition" :class="open && 'opacity-0'"></span>
                <span class="h-0.5 w-6 bg-dark transition" :class="open && '-translate-y-2 -rotate-45'"></span>
            </button>
        </div>
    </div>

    {{-- Mobile accordion menu --}}
    <nav id="mobile-menu" x-cloak x-show="open" x-transition.opacity class="border-t border-dark/10 bg-white nav:hidden" aria-label="{{ __('site.main_navigation') }}">
        <ul class="container-site py-3">
            @foreach ($headerMenu as $item)
                @include('partials.mobile-item', ['item' => $item, 'depth' => 0])
            @endforeach
            @if ($buttonLabel && $buttonUrl)
                <li class="pt-3"><a href="{{ $buttonUrl }}" class="btn-primary w-full">{{ $buttonLabel }}</a></li>
            @endif
        </ul>
    </nav>
</header>
