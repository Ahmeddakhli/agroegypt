<?php

namespace App\Filament\Widgets;

use App\Enums\ContentStatus;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\JobApplication;
use App\Models\Language;
use App\Models\Page;
use App\Models\Product;
use App\Support\TranslationStats;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SiteOverview extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $translation = TranslationStats::percentages();
        $other = collect($translation)->except(Language::defaultCode());
        $average = $other->isEmpty() ? 100 : (int) round($other->avg(fn (array $s) => $s['content']));

        return [
            Stat::make(__('Published pages'), Page::where('status', ContentStatus::Published)->count())->description(__(':count in total', ['count' => Page::count()]))->icon('heroicon-o-document-duplicate'),
            Stat::make(__('Products'), Product::where('status', ContentStatus::Published)->count())->description(__(':count in total', ['count' => Product::count()]))->icon('heroicon-o-shopping-bag'),
            Stat::make(__('Categories'), Category::count())->icon('heroicon-o-folder'),
            Stat::make(__('Languages'), Language::active()->count())->description(__(':count configured', ['count' => Language::cached()->count()]))->icon('heroicon-o-language'),
            Stat::make(__('Translation completion'), $average.'%')->description(__('Average content translated into non-default languages'))->icon('heroicon-o-chat-bubble-left-right')
                ->color($average === 100 ? 'success' : 'warning'),
            Stat::make(__('New messages'), ContactMessage::unread()->count() + JobApplication::unread()->count())
                ->description(__(':contact contact · :applications applications', ['contact' => ContactMessage::unread()->count(), 'applications' => JobApplication::unread()->count()]))
                ->icon('heroicon-o-inbox')
                ->color('warning')
                ->url(ContactMessageResource::getUrl('index')),
        ];
    }
}
