<?php

namespace App\Filament\Pages;

use App\Filament\Support\TranslatesPageLabels;
use App\Filament\Support\Translations;
use App\Models\Language;
use App\Models\Setting;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Base for settings screens. Global values are stored with locale '' and localized values per
 * language code; form state uses "group.key" paths, localized ones under "localized.{code}.group.key".
 */
abstract class SettingsPage extends Page
{
    use TranslatesPageLabels;

    public ?array $data = [];

    /**
     * @return list<string> "group.key" settings shared by all languages
     */
    abstract protected function globalKeys(): array;

    /**
     * @return list<string> "group.key" settings with a value per language
     */
    protected function localizedKeys(): array
    {
        return [];
    }

    abstract protected function permission(): string;

    public static function canAccess(): bool
    {
        return auth()->user()?->can((new static)->permission()) ?? false;
    }

    public function mount(): void
    {
        $all = Setting::allCached();
        $state = [];

        // Keys without a stored value are left out so the fields' defaults apply (e.g. toggles that default to on).
        foreach ($this->globalKeys() as $key) {
            if (isset($all[$key][''])) {
                data_set($state, $key, $all[$key]['']);
            }
        }
        foreach (Language::cached() as $language) {
            foreach ($this->localizedKeys() as $key) {
                // A language-neutral value (locale '') is what every language falls back to; show it as the default language's text.
                $value = $all[$key][$language->code] ?? ($language->is_default ? ($all[$key][''] ?? null) : null);
                data_set($state, "localized.{$language->code}.{$key}", $value);
            }
        }

        // fill() only applies field defaults when called without data: take the defaults first, then layer the stored values.
        $this->form->fill();
        $this->form->fill(array_replace_recursive($this->form->getRawState(), $state));
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components($this->formComponents());
    }

    /**
     * @return array<int, mixed>
     */
    abstract protected function formComponents(): array;

    /**
     * Tabs with one group per language, for fields built by the closure.
     *
     * @param  Closure(Language, bool): array<int, mixed>  $fields
     */
    protected function localizedTabs(Closure $fields, string $label): Group
    {
        return Group::make([Translations::tabs($fields, $label)])->statePath('localized')->columnSpanFull();
    }

    public function save(): void
    {
        $state = $this->form->getState();

        DB::transaction(function () use ($state) {
            foreach ($this->globalKeys() as $key) {
                $this->store($key, data_get($state, $key), '');
            }
            foreach (Language::cached() as $language) {
                foreach ($this->localizedKeys() as $key) {
                    $this->store($key, data_get($state, "localized.{$language->code}.{$key}"), $language->code);
                }
            }
        });

        Notification::make()->success()->title(__('Settings saved'))->send();
        $this->dispatch('settings-saved');
    }

    private function store(string $key, mixed $value, string $locale): void
    {
        $value = is_bool($value) ? ($value ? '1' : '0') : $value;

        if (blank($value) && $locale !== '') {
            [$group, $name] = explode('.', $key, 2);
            Setting::where(['group' => $group, 'key' => $name, 'locale' => $locale])->delete();

            return;
        }

        Setting::put($key, is_array($value) ? implode(',', $value) : $value, $locale);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([Action::make('save')->label('Save settings')->submit('save')->keyBindings(['mod+s'])])->sticky()->key('form-actions'),
                ]),
            ...$this->extraContent(),
        ]);
    }

    /**
     * @return array<int, mixed>
     */
    protected function extraContent(): array
    {
        return [];
    }
}
