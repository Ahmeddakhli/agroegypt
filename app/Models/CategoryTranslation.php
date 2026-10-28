<?php

namespace App\Models;

use App\Models\Concerns\AuditableTranslation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'menu_label', 'description', 'meta_title', 'meta_description'])]
#[WithoutTimestamps]
class CategoryTranslation extends Model
{
    use AuditableTranslation;
}
