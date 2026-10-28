<?php

namespace App\Filament\Support;

/**
 * For Create/Edit pages of translatable resources: loads per-language state and persists it.
 */
trait HasTranslatableForm
{
    /** @var list<string> */
    protected array $emptiedLocales = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [...$data, ...Translations::fill($this->getRecord())];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        Translations::strip($data);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->emptiedLocales = Translations::strip($data);

        return $data;
    }

    protected function afterSave(): void
    {
        Translations::forget($this->getRecord(), $this->emptiedLocales);
    }
}
