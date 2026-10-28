<?php

namespace App\Filament\Widgets;

use App\Models\Language;
use App\Support\TranslationStats;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TranslationProgress extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected function getHeading(): string
    {
        return __('Translation completion');
    }

    protected function getStats(): array
    {
        $stats = TranslationStats::percentages();

        return Language::cached()->map(fn (Language $language) => Stat::make(
            $language->native_name.($language->is_active ? '' : ' '.__('(disabled)')),
            __(':percent% content', ['percent' => $stats[$language->code]['content']]),
        )
            ->description(__(':percent% interface strings', ['percent' => $stats[$language->code]['interface']]))
            ->color(min($stats[$language->code]) === 100 ? 'success' : 'warning'))->all();
    }
}
