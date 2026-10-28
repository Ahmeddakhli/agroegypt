<?php

namespace App\Models;

use App\Models\Concerns\AuditableTranslation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'subtitle', 'button_label'])]
#[WithoutTimestamps]
class SlideTranslation extends Model
{
    use AuditableTranslation;
}
