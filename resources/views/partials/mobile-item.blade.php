<li x-data="{ expanded: false }" class="border-b border-dark/5 last:border-0">
    <div class="flex items-center justify-between">
        <a href="{{ $item['url'] }}" target="{{ $item['target'] }}" class="block grow py-2.5 text-[16px] text-dark hover:text-primary" style="padding-inline-start: {{ $depth * 16 }}px">{{ $item['label'] }}</a>
        @if ($item['children'])
            <button type="button" @click="expanded = ! expanded" :aria-expanded="expanded.toString()" class="flex size-10 items-center justify-center" aria-label="{{ __('site.toggle_submenu', ['name' => $item['label']]) }}">
                <svg class="size-3 transition" :class="expanded && 'rotate-180'" viewBox="0 0 10 6" aria-hidden="true"><path d="M1 1l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.4"/></svg>
            </button>
        @endif
    </div>
    @if ($item['children'])
        <ul x-cloak x-show="expanded">
            @foreach ($item['children'] as $child)
                @include('partials.mobile-item', ['item' => $child, 'depth' => $depth + 1])
            @endforeach
        </ul>
    @endif
</li>
