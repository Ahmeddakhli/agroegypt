<?php

namespace App\Models;

use App\Models\Concerns\AuditableTranslation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'subtitle', 'body', 'button_label', 'button_url'])]
#[WithoutTimestamps]
class PageSectionTranslation extends Model
{
    use AuditableTranslation;
}
