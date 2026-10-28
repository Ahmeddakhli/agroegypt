<?php

namespace App\Filament\Resources\LanguageLines\Pages;

use App\Filament\Resources\LanguageLines\LanguageLineResource;
use App\Filament\Widgets\TranslationProgress;
use App\Models\Language;
use App\Models\LanguageLine;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Http\UploadedFile;
use JsonException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManageLanguageLines extends ManageRecords
{
    protected static string $resource = LanguageLineResource::class;

    protected function getHeaderWidgets(): array
    {
        return [TranslationProgress::class];
    }

    protected function getHeaderActions(): array
    {
        $canEdit = auth()->user()?->can('update translations') ?? false;

        return [
            CreateAction::make(),
            Action::make('sync')
                ->label('Scan language files')
                ->icon('heroicon-o-arrow-path')
                ->visible($canEdit)
                ->action(function () {
                    $created = LanguageLine::syncFromFiles();
                    Notification::make()->success()->title(__(':count new strings imported', ['count' => $created]))->send();
                }),
            ActionGroup::make([
                Action::make('exportCsv')->label('Export CSV')->icon('heroicon-o-arrow-down-tray')->action(fn () => $this->exportCsv()),
                Action::make('exportJson')->label('Export JSON')->icon('heroicon-o-arrow-down-tray')->action(fn () => $this->exportJson()),
                Action::make('import')
                    ->label('Import CSV / JSON')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->visible($canEdit)
                    ->schema([
                        FileUpload::make('file')
                            ->required()
                            ->storeFiles(false)
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/json', 'application/vnd.ms-excel'])
                            ->maxSize(2048),
                        Toggle::make('overwrite')->label('Overwrite existing translations')->default(true),
                    ])
                    ->action(fn (array $data) => $this->import($data['file'], (bool) $data['overwrite'])),
            ])->label('Import / export')->button()->color('gray'),
        ];
    }

    private function exportCsv(): StreamedResponse
    {
        $codes = Language::cached()->pluck('code')->all();

        return response()->streamDownload(function () use ($codes) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so spreadsheet apps show Arabic correctly
            fputcsv($out, ['group', 'key', ...$codes], escape: '');
            LanguageLine::orderBy('group')->orderBy('key')->each(function (LanguageLine $line) use ($out, $codes) {
                fputcsv($out, [$line->group, $line->key, ...array_map(fn ($code) => $line->text[$code] ?? '', $codes)], escape: '');
            });
            fclose($out);
        }, 'translations-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function exportJson(): StreamedResponse
    {
        $lines = LanguageLine::orderBy('group')->orderBy('key')->get(['group', 'key', 'text']);

        return response()->streamDownload(
            fn () => print (json_encode($lines, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'translations-'.now()->format('Y-m-d').'.json',
            ['Content-Type' => 'application/json'],
        );
    }

    private function import(UploadedFile $file, bool $overwrite): void
    {
        try {
            $rows = str_ends_with(strtolower($file->getClientOriginalName()), '.json')
                ? $this->parseJson((string) file_get_contents($file->getRealPath()))
                : $this->parseCsv($file->getRealPath());
        } catch (JsonException) {
            Notification::make()->danger()->title(__('The JSON file is not valid.'))->send();

            return;
        }

        $codes = Language::cached()->pluck('code')->all();
        $byGroup = [];
        foreach ($rows as $row) {
            if (! preg_match('/^[\w\-*]+$/', (string) $row['group']) || ! preg_match('/^[\w.\-]+$/', (string) $row['key'])) {
                continue;
            }
            $byGroup[$row['group']][$row['key']] = array_map(
                fn ($text) => mb_substr(strip_tags((string) $text), 0, 5000),
                array_intersect_key($row['text'], array_flip($codes)),
            );
        }

        $created = 0;
        foreach ($byGroup as $group => $values) {
            $created += LanguageLine::merge($group, $values, $overwrite);
        }

        Notification::make()->success()->title(__('Imported :rows rows (:count new strings)', ['rows' => count($rows), 'count' => $created]))->send();
    }

    /**
     * @return list<array{group: string, key: string, text: array<string, string>}>
     */
    private function parseJson(string $json): array
    {
        return collect(json_decode($json, true, 4, JSON_THROW_ON_ERROR))
            ->filter(fn ($row) => is_array($row) && isset($row['group'], $row['key']) && is_array($row['text'] ?? null))
            ->map(fn ($row) => ['group' => $row['group'], 'key' => $row['key'], 'text' => array_filter($row['text'], 'is_string')])
            ->values()->all();
    }

    /**
     * @return list<array{group: string, key: string, text: array<string, string>}>
     */
    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, escape: '');
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) ($header[0] ?? ''));
        $rows = [];

        while (($values = fgetcsv($handle, escape: '')) !== false) {
            $row = array_combine(array_slice($header, 0, count($values)), array_slice($values, 0, count($header)));
            if (isset($row['group'], $row['key'])) {
                $rows[] = ['group' => $row['group'], 'key' => $row['key'], 'text' => array_filter(array_diff_key($row, ['group' => 1, 'key' => 1]), fn ($v) => $v !== '')];
            }
        }
        fclose($handle);

        return $rows;
    }
}
