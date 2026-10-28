<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Resources\Media\MediaResource;
use App\Filament\Support\HasTranslatableForm;
use Filament\Resources\Pages\EditRecord;

class EditMedia extends EditRecord
{
    use HasTranslatableForm;

    protected static string $resource = MediaResource::class;
}
