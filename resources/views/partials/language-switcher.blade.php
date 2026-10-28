@if ($setting('header.show_language_switcher', '1') === '1' && $languages->count() > 1)
    <div x-data="{ open: false }" @click.outside="open = false" class="relative ms-4">
        <button type="button" @click="open = ! open" :aria-expanded="open.toString()" class="flex items-center gap-1.5 text-[15px] text-dark hover:text-primary" aria-label="{{ __('site.change_language') }}">
            @if ($flag = $currentLanguage?->flagUrl())<img src="{{ $flag }}" alt="" class="h-3.5 w-5 object-cover">@endif
            <span>{{ strtoupper(app()->getLocale()) }}</span>
            <svg class="size-2.5" viewBox="0 0 10 6" aria-hidden="true"><path d="M1 1l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
        </button>
        <ul x-cloak x-show="open" x-transition.opacity class="absolute end-0 top-full mt-2 min-w-[150px] bg-white py-2 shadow-[0_9px_68px_rgba(38,43,43,0.15)]">
            @foreach ($languages as $entry)
                <li>
                    <a href="{{ $entry['url'] }}" hreflang="{{ $entry['language']->code }}" lang="{{ $entry['language']->code }}"
                       @if ($entry['language']->code === app()->getLocale()) aria-current="true" @endif
                       class="flex items-center gap-2 px-4 py-1.5 text-[15px] text-deep hover:text-primary">
                        @if ($flag = $entry['language']->flagUrl())<img src="{{ $flag }}" alt="" class="h-3.5 w-5 object-cover">@endif
                        {{ $entry['language']->native_name }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
