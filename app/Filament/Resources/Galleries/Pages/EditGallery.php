<?php

namespace App\Filament\Resources\Galleries\Pages;

use App\Filament\Resources\Galleries\GalleryResource;
use App\Filament\Support\HasTranslatableForm;
use Filament\Resources\Pages\EditRecord;

class EditGallery extends EditRecord
{
    use HasTranslatableForm;

    protected static string $resource = GalleryResource::class;
}
