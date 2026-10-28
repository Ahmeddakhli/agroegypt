<?php

namespace App\Filament\Support;

/**
 * Page titles are English keys; this passes them through the translator so the dashboard follows its locale.
 */
trait TranslatesPageLabels
{
    public static function getNavigationLabel(): string
    {
        return __(parent::getNavigationLabel());
    }

    public function getTitle(): string
    {
        return __(parent::getTitle());
    }
}
