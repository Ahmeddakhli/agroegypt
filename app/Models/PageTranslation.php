<?php

namespace App\Models;

use App\Models\Concerns\AuditableTranslation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'slug', 'meta_title', 'meta_description', 'meta_keywords', 'og_media_id'])]
#[WithoutTimestamps]
class PageTranslation extends Model
{
    use AuditableTranslation;
}
