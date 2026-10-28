{{-- Admin views cannot use the site's Tailwind build, so layout uses inline styles and Filament components. --}}
<x-filament-panels::page>
    <div style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end">
        <label style="display:grid;gap:.25rem;min-width:12rem">
            <span style="font-size:.875rem;font-weight:500">{{ __('Language') }}</span>
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="locale">
                    @foreach ($languages as $language)
                        <option value="{{ $language->code }}">{{ $language->native_name }} ({{ $language->code }})</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </label>
        <label style="display:grid;gap:.25rem;min-width:14rem">
            <span style="font-size:.875rem;font-weight:500">{{ __('Module') }}</span>
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="module">
                    <option value="">{{ __('All modules') }}</option>
                    @foreach ($modules as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </label>
        <label style="display:grid;gap:.25rem;min-width:12rem">
            <span style="font-size:.875rem;font-weight:500">{{ __('Show') }}</span>
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="status">
                    <option value="incomplete">{{ __('Missing or partial') }}</option>
                    <option value="missing">{{ __('Missing') }}</option>
                    <option value="partial">{{ __('Partial') }}</option>
                    <option value="complete">{{ __('Complete') }}</option>
                    <option value="all">{{ __('All') }}</option>
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </label>
    </div>

    @if ($languages->isEmpty())
        <x-filament::section>{{ __('Add a second language under Languages to translate content.') }}</x-filament::section>
    @else
        <x-filament::section :heading="__('Completion by module')">
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(14rem,1fr));gap:.75rem">
                @foreach ($summary as $key => $module)
                    <button type="button" wire:click="$set('module', '{{ $key }}')" style="text-align:start;padding:.75rem;border:1px solid rgb(0 0 0 / .1);border-radius:.5rem">
                        <div style="font-weight:600">{{ __($module['label']) }}</div>
                        <div style="font-size:1.5rem;font-weight:700">{{ $module['percent'] }}%</div>
                        <div style="font-size:.75rem;opacity:.7">{{ __(':complete complete · :partial partial · :missing missing', ['complete' => $module['complete'], 'partial' => $module['partial'], 'missing' => $module['missing']]) }}</div>
                    </button>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section :heading="trans_choice(':count record|:count records', count($rows))">
            @if ($rows === [])
                <p style="opacity:.7">{{ __('Nothing to show — everything matching these filters is translated.') }}</p>
            @else
                <table style="width:100%;border-collapse:collapse;font-size:.875rem">
                    <thead>
                        <tr style="text-align:start;border-bottom:1px solid rgb(0 0 0 / .1)">
                            <th style="padding:.5rem;text-align:start">{{ __('Module') }}</th>
                            <th style="padding:.5rem;text-align:start">{{ __('Content (default language)') }}</th>
                            <th style="padding:.5rem;text-align:start">{{ __('Status') }}</th>
                            <th style="padding:.5rem"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr style="border-bottom:1px solid rgb(0 0 0 / .05)">
                                <td style="padding:.5rem;white-space:nowrap">{{ __($row['module_label']) }}</td>
                                <td style="padding:.5rem">{{ $row['label'] }}</td>
                                <td style="padding:.5rem">
                                    <x-filament::badge :color="match ($row['status']) { 'complete' => 'success', 'partial' => 'warning', default => 'danger' }">
                                        {{ __(ucfirst($row['status'])) }}
                                    </x-filament::badge>
                                </td>
                                <td style="padding:.5rem;text-align:end">
                                    @if ($row['edit_url'])
                                        <x-filament::link :href="$row['edit_url']" icon="heroicon-m-pencil-square">{{ __('Translate') }}</x-filament::link>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($hiddenCount)
                    <p style="margin-top:.75rem;opacity:.7">{{ __(':count more — narrow the list with the module filter.', ['count' => $hiddenCount]) }}</p>
                @endif
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
