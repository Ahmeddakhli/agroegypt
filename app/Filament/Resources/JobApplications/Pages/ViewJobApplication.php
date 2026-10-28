<?php

namespace App\Filament\Resources\JobApplications\Pages;

use App\Filament\Resources\JobApplications\JobApplicationResource;
use App\Filament\Support\Inbox;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewJobApplication extends ViewRecord
{
    protected static string $resource = JobApplicationResource::class;

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
            Action::make('downloadCv')
                ->label('Download CV')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => $this->getRecord()->cv_path && Storage::disk('local')->exists($this->getRecord()->cv_path))
                ->action(fn () => Storage::disk('local')->download($this->getRecord()->cv_path, $this->getRecord()->cv_original_name)),
            EditAction::make()->label('Status & notes')->schema(Inbox::editSchema()),
            Inbox::toggleReadAction(),
            DeleteAction::make(),
        ];
    }
}
