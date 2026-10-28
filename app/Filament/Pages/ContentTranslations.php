<?php

namespace App\Filament\Pages;

use App\Filament\Support\TranslatesPageLabels;
use App\Models\Language;
use App\Support\TranslationCoverage;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Overview of content translations: per language and module, which records are complete, partial or missing.
 */
class ContentTranslations extends Page
{
    use TranslatesPageLabels;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Localization';

    protected static ?string $navigationLabel = 'Content translations';

    protected static ?string $title = 'Content translations';

    protected static ?int $navigationSort = 3;

    protected string $view = 'admin.content-translations';

    #[Url]
    public string $locale = '';

    #[Url]
    public string $module = '';

    #[Url]
    public string $status = 'incomplete';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view translations') ?? false;
    }

    public function mount(): void
    {
        $codes = Language::cached()->pluck('code');

        if (! $codes->contains($this->locale)) {
            $this->locale = (string) ($codes->first(fn (string $code) => $code !== Language::defaultCode()) ?? Language::defaultCode());
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $rows = TranslationCoverage::rows($this->locale, $this->module !== '' ? $this->module : null);
        $visible = array_values(array_filter($rows, fn (array $row) => match ($this->status) {
            'incomplete' => $row['status'] !== 'complete',
            'all' => true,
            default => $row['status'] === $this->status,
        }));

        return [
            'languages' => Language::cached()->reject(fn (Language $language) => $language->is_default),
            'modules' => array_map(fn (array $module) => __($module['label']), TranslationCoverage::modules()),
            'summary' => TranslationCoverage::summary($rows),
            'rows' => array_slice($visible, 0, 300),
            'hiddenCount' => max(0, count($visible) - 300),
        ];
    }
}
