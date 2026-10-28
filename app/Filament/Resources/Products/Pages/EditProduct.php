<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\HasTranslatableForm;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    use HasTranslatableForm;

    protected static string $resource = ProductResource::class;
}
