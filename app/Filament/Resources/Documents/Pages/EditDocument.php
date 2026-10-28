<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Support\HasTranslatableForm;
use Filament\Resources\Pages\EditRecord;

class EditDocument extends EditRecord
{
    use HasTranslatableForm;

    protected static string $resource = DocumentResource::class;
}
