<?php

namespace App\Filament\Resources\Media;

use App\Filament\Resources\Media\Pages\EditMedia;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Filament\Support\TranslatesResourceLabels;
use App\Filament\Support\Translations;
use App\Models\Language;
use App\Models\Media;
use App\Models\MediaFolder;
use App\Support\MediaLibrary;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;
use InvalidArgumentException;
use UnitEnum;

class MediaResource extends Resource
{
    use TranslatesResourceLabels;

    protected static ?string $model = Media::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Media library';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make(__('File'))->columnSpan(1)->schema([
                    View::make('admin.media-preview'),
                    TextEntry::make('mime_type')->label('Type'),
                    TextEntry::make('dimensions')->state(fn (Media $record) => $record->width ? "{$record->width} × {$record->height} px" : '—'),
                    TextEntry::make('size')->state(fn (Media $record) => Number::fileSize($record->size)),
                    TextEntry::make('usage')->label('Used in')->state(fn (Media $record) => __(':count place(s)', ['count' => MediaLibrary::usageCount($record)])),
                    TextEntry::make('path')->label('URL')->state(fn (Media $record) => $record->url())->copyable()->limit(40),
                ]),
                Section::make(__('Details'))->columnSpan(2)->schema([
                    TextInput::make('name')->required()->maxLength(250),
                    Select::make('folder_id')
                        ->label('Folder')
                        ->relationship('folder', 'name')
                        ->createOptionForm([TextInput::make('name')->required()->maxLength(100)])
                        ->searchable()
                        ->preload(),
                    Translations::tabs(fn (Language $language) => [
                        TextInput::make('alt')->label('Alternative text')->maxLength(255)->helperText(__('Describes the image for screen readers and search engines.')),
                        TextInput::make('title')->maxLength(255),
                    ], 'Alt text per language'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->contentGrid(['md' => 3, 'xl' => 5])
            ->paginated([20, 40, 80])
            ->columns([
                Stack::make([
                    ImageColumn::make('thumb')
                        ->state(fn (Media $record) => $record->isImage() ? $record->url(480) : null)
                        ->defaultImageUrl(asset('vendor/admin/file.svg'))
                        ->imageHeight(140)
                        ->extraImgAttributes(['class' => 'object-contain w-full', 'loading' => 'lazy']),
                    TextColumn::make('name')->searchable()->limit(30)->weight('medium'),
                    TextColumn::make('meta')
                        ->state(fn (Media $record) => ($record->width ? "{$record->width}×{$record->height} · " : '').Number::fileSize($record->size))
                        ->color('gray')->size('xs'),
                ])->space(2),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(['image' => __('Images'), 'document' => __('Documents'), 'video' => __('Video')])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'image' => $query->where('mime_type', 'like', 'image/%'),
                        'video' => $query->where('mime_type', 'like', 'video/%'),
                        'document' => $query->where('mime_type', 'not like', 'image/%')->where('mime_type', 'not like', 'video/%'),
                        default => $query,
                    }),
                SelectFilter::make('folder_id')->label('Folder')->options(fn () => MediaFolder::orderBy('name')->pluck('name', 'id')->all()),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('replace')
                    ->icon('heroicon-o-arrow-path')
                    ->modalDescription(__('Upload a new file; every page using this item will show the new file.'))
                    ->schema([FileUpload::make('file')->required()->storeFiles(false)->maxSize(20 * 1024)->acceptedFileTypes(array_keys(MediaLibrary::ALLOWED))])
                    ->visible(fn () => auth()->user()?->can('update media'))
                    ->action(function (Media $record, array $data) {
                        try {
                            MediaLibrary::replace($record, $data['file']);
                            Notification::make()->success()->title(__('File replaced'))->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->danger()->title(__($e->getMessage()))->send();
                        }
                    }),
                DeleteAction::make()
                    ->using(function (Media $record) {
                        try {
                            MediaLibrary::delete($record);
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->danger()->title(__($e->getMessage()))->send();

                            return false;
                        }

                        return true;
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedia::route('/'),
            'edit' => EditMedia::route('/{record}/edit'),
        ];
    }
}
