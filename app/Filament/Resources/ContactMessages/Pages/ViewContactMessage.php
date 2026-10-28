<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Support\Inbox;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->getRecord()->read_at === null) {
            $this->getRecord()->markAsRead();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Status & notes')->schema(Inbox::editSchema()),
            Inbox::toggleReadAction(),
            DeleteAction::make(),
        ];
    }
}
