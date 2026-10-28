{{-- Admin views cannot use the site's Tailwind build, so this preview uses inline styles. --}}
@php
    $render = function (array $items, int $depth = 0) use (&$render) {
        $style = $depth
            ? 'margin-inline-start:1.25rem;padding-inline-start:.75rem;border-inline-start:1px solid #e5e7eb'
            : 'display:flex;flex-wrap:wrap;gap:.25rem 2rem;align-items:flex-start';
        $html = '<ul style="'.$style.'">';
        foreach ($items as $item) {
            $html .= '<li style="padding:2px 0"><a href="'.e($item['url']).'" target="_blank" rel="noopener" style="font-size:.875rem;'.($depth ? 'color:#4b5563' : 'font-weight:600').'">'.e($item['label']).'</a>';
            $html .= $item['children'] ? $render($item['children'], $depth + 1) : '';
            $html .= '</li>';
        }

        return $html.'</ul>';
    };
@endphp
<div style="display:grid;gap:1.5rem">
    @foreach ($tree as $language => $items)
        <div dir="auto">
            <p style="margin-bottom:.5rem;font-size:.75rem;font-weight:500;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">{{ $language }}</p>
            @if ($items)
                {!! $render($items) !!}
            @else
                <p style="font-size:.875rem;color:#6b7280">{{ __('This menu is empty.') }}</p>
            @endif
        </div>
    @endforeach
</div>
