<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use App\Filament\Support\HasTranslatableForm;
use App\Models\Language;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    use HasTranslatableForm;

    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make(Language::active()->map(fn (Language $language) => Action::make('preview_'.$language->code)
                ->label($language->native_name)
                ->url(fn () => PageResource::previewUrl($this->getRecord(), $language->code))
                ->openUrlInNewTab())->all())
                ->label('Preview (saved version)')
                ->icon('heroicon-o-eye')
                ->button()
                ->color('gray'),
            DeleteAction::make(),
        ];
    }
}
