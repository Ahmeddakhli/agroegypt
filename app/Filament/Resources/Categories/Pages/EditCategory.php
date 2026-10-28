<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Support\HasTranslatableForm;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    use HasTranslatableForm;

    protected static string $resource = CategoryResource::class;
}
