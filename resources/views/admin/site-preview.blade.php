{{-- Admin views cannot use the site's Tailwind build, so this preview uses inline styles. --}}
@php
    $livewire = $getLivewire();
    $url = $livewire->previewUrl();
    $current = \App\Models\Language::active()->firstWhere('code', $livewire->previewLanguage)?->code ?? \App\Models\Language::defaultCode();
@endphp
<div>
    <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:.75rem">
        @foreach (\App\Models\Language::active() as $language)
            <x-filament::button size="sm" :color="$language->code === $current ? 'primary' : 'gray'" wire:click="$set('previewLanguage', '{{ $language->code }}')">
                {{ $language->native_name }}
            </x-filament::button>
        @endforeach
        <x-filament::link :href="$url" target="_blank" icon="heroicon-m-arrow-top-right-on-square" size="sm">{{ __('Open in a new tab') }}</x-filament::link>
    </div>
    <iframe wire:key="site-preview-{{ md5($url) }}" src="{{ $url }}" title="{{ __('Site preview') }}" loading="lazy"
            style="width:100%;height:640px;border:1px solid rgb(0 0 0 / .1);border-radius:.5rem"></iframe>
</div>
