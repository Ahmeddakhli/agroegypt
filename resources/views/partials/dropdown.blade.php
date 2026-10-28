{{-- Hover/focus dropdown, 200px white panel; nested levels fly out to the inline-end side. --}}
<ul @class([
    'invisible absolute w-[200px] bg-white py-4 opacity-0 shadow-[0_9px_68px_rgba(38,43,43,0.1)] transition-all duration-200',
    'start-0 top-[calc(100%-14px)] group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100' => $level === 1,
    'start-full -top-4 group-hover/sub:visible group-hover/sub:opacity-100 group-focus-within/sub:visible group-focus-within/sub:opacity-100' => $level > 1,
])>
    @foreach ($items as $child)
        <li class="group/sub relative">
            <a href="{{ $child['url'] }}" target="{{ $child['target'] }}"
               @class(['flex items-center justify-between px-[30px] py-1 text-[16px] leading-[25px] transition-colors hover:text-primary', 'text-primary' => $child['url'] === url()->current(), 'text-deep' => $child['url'] !== url()->current()])>
                {{ $child['label'] }}
                @if ($child['children'])
                    <svg class="size-2.5 rtl:rotate-180" viewBox="0 0 6 10" aria-hidden="true"><path d="M1 1l4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
                @endif
            </a>
            @if ($child['children'])
                @include('partials.dropdown', ['items' => $child['children'], 'level' => $level + 1])
            @endif
        </li>
    @endforeach
</ul>
