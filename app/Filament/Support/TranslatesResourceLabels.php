<?php

namespace App\Filament\Support;

use Illuminate\Support\Str;

use function Filament\Support\get_model_label;

/**
 * Resource labels are English keys; this passes them through the translator so the dashboard follows its locale.
 */
trait TranslatesResourceLabels
{
    public static function getModelLabel(): string
    {
        return __(static::$modelLabel ?? get_model_label(static::getModel()));
    }

    public static function getPluralModelLabel(): string
    {
        return __(static::$pluralModelLabel ?? Str::plural(static::$modelLabel ?? get_model_label(static::getModel())));
    }

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ? __(static::$navigationLabel) : static::getTitleCasePluralModelLabel();
    }
}
