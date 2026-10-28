<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use App\Filament\Support\HasTranslatableForm;
use Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    use HasTranslatableForm;

    protected static string $resource = PageResource::class;
}
