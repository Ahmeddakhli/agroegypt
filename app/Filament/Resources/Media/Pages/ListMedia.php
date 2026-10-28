<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Resources\Media\MediaResource;
use App\Models\MediaFolder;
use App\Support\MediaLibrary;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use InvalidArgumentException;

class ListMedia extends ListRecords
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label('Upload files')
                ->icon('heroicon-o-arrow-up-tray')
                ->visible(fn () => auth()->user()?->can('create media'))
                ->schema([
                    FileUpload::make('files')
                        ->multiple()
                        ->required()
                        ->storeFiles(false)
                        ->maxFiles(50)
                        ->maxSize(20 * 1024)
                        ->acceptedFileTypes(array_keys(MediaLibrary::ALLOWED)),
                    Select::make('folder_id')->label('Folder')->options(fn () => MediaFolder::orderBy('name')->pluck('name', 'id')->all()),
                ])
                ->action(function (array $data) {
                    $stored = 0;
                    $errors = [];
                    foreach ($data['files'] as $file) {
                        try {
                            MediaLibrary::store($file, $data['folder_id'] ?? null);
                            $stored++;
                        } catch (InvalidArgumentException $e) {
                            $errors[] = $file->getClientOriginalName().': '.__($e->getMessage());
                        }
                    }
                    Notification::make()->title(__(':count file(s) added', ['count' => $stored]))->body(implode("\n", $errors) ?: null)->{$errors ? 'warning' : 'success'}()->send();
                }),
        ];
    }
}
