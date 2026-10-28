<?php

namespace App\Models;

use App\Models\Concerns\AuditableTranslation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['label'])]
#[WithoutTimestamps]
class MenuItemTranslation extends Model
{
    use AuditableTranslation;
}
