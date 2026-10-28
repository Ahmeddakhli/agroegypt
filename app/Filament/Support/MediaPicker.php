<?php

namespace App\Filament\Support;

use App\Models\Media;
use App\Support\MediaLibrary;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use InvalidArgumentException;

/**
 * Searchable media-library select with thumbnails and an inline "upload new file" option.
 */
final class MediaPicker
{
    public static function make(string $field, string $label, bool $imagesOnly = true): Select
    {
        return Select::make($field)
            ->label($label)
            ->allowHtml()
            ->searchable()
            ->getSearchResultsUsing(fn (string $search) => Media::query()
                ->when($imagesOnly, fn ($q) => $q->where('mime_type', 'like', 'image/%'))
                ->where('name', 'like', '%'.addcslashes($search, '%_').'%')
                ->latest('id')->limit(30)->get()
                ->mapWithKeys(fn (Media $media) => [$media->id => self::label($media)])->all())
            ->getOptionLabelUsing(fn ($value) => ($media = Media::find($value)) ? self::label($media) : null)
            ->createOptionForm([
                FileUpload::make('upload')
                    ->label('Upload a new file')
                    ->storeFiles(false)
                    ->required()
                    ->maxSize(20 * 1024)
                    ->acceptedFileTypes($imagesOnly ? ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'] : array_keys(MediaLibrary::ALLOWED)),
            ])
            ->createOptionUsing(function (array $data): int {
                try {
                    return MediaLibrary::store($data['upload'])->id;
                } catch (InvalidArgumentException $e) {
                    Notification::make()->danger()->title(__($e->getMessage()))->send();

                    throw new Halt;
                }
            });
    }

    public static function label(Media $media): string
    {
        $thumb = $media->isImage() ? '<img src="'.e($media->url(480)).'" alt="" style="width:40px;height:40px;object-fit:contain;display:inline-block;vertical-align:middle;margin-inline-end:8px;background:#f3f4f6">' : '📄 ';

        return $thumb.'<span>'.e($media->name).'</span>';
    }
}
