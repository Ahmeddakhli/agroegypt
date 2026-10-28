@php
    $actions = array_filter([
        ['New product', \App\Filament\Resources\Products\ProductResource::class, 'create', 'heroicon-o-plus'],
        ['New page', \App\Filament\Resources\Pages\PageResource::class, 'create', 'heroicon-o-document-plus'],
        ['Upload media', \App\Filament\Resources\Media\MediaResource::class, 'index', 'heroicon-o-photo'],
        ['Edit menus', \App\Filament\Pages\ManageMenus::class, null, 'heroicon-o-bars-3'],
        ['Translations', \App\Filament\Resources\LanguageLines\LanguageLineResource::class, 'index', 'heroicon-o-language'],
    ], fn ($a) => class_exists($a[1]) && ($a[2] === null ? $a[1]::canAccess() : ($a[2] === 'create' ? $a[1]::canCreate() : $a[1]::canViewAny())));
@endphp
<x-filament-widgets::widget>
    <x-filament::section :heading="__('Quick actions')">
        <div class="flex flex-wrap gap-3">
            @foreach ($actions as [$label, $class, $page, $icon])
                <x-filament::button tag="a" :href="$page ? $class::getUrl($page) : $class::getUrl()" :icon="$icon" color="gray">{{ __($label) }}</x-filament::button>
            @endforeach
            <x-filament::button tag="a" :href="url('/')" target="_blank" icon="heroicon-o-arrow-top-right-on-square" color="gray">{{ __('View website') }}</x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
