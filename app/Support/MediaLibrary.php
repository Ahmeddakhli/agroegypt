<?php

namespace App\Support;

use App\Models\Media;
use App\Models\Setting;
use finfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use InvalidArgumentException;
use Throwable;

/**
 * Stores files in the public media library: sniffs the real MIME type, rejects unsafe files,
 * de-duplicates by SHA-1 and generates responsive WebP variants for raster images.
 */
final class MediaLibrary
{
    /** @var list<int> */
    public const VARIANT_WIDTHS = [480, 768, 1280, 1920];

    /** @var array<string, list<string>> MIME type => allowed extensions */
    public const ALLOWED = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
        'image/gif' => ['gif'],
        'image/svg+xml' => ['svg'],
        'application/pdf' => ['pdf'],
        'application/msword' => ['doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        'application/vnd.ms-excel' => ['xls'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
        'video/mp4' => ['mp4'],
    ];

    /** @var array<string, list<string>> table => media id columns referencing the library */
    private const REFERENCES = [
        'pages' => ['hero_media_id'],
        'page_translations' => ['og_media_id'],
        'page_sections' => ['media_id'],
        'section_items' => ['media_id', 'icon_media_id'],
        'categories' => ['icon_media_id', 'card_media_id', 'hero_media_id'],
        'products' => ['icon_media_id', 'card_media_id', 'image_media_id', 'hero_media_id', 'season_icon_media_id'],
        'product_images' => ['media_id'],
        'product_packagings' => ['icon_media_id'],
        'packaging_specs' => ['icon_media_id'],
        'galleries' => ['cover_media_id', 'hero_media_id'],
        'gallery_images' => ['media_id'],
        'slides' => ['media_id', 'mobile_media_id'],
        'documents' => ['file_media_id', 'image_media_id'],
    ];

    private const RASTER = ['image/jpeg', 'image/png', 'image/webp'];

    public static function store(UploadedFile $file, ?int $folderId = null): Media
    {
        return self::import($file->getRealPath(), $file->getClientOriginalName(), $folderId);
    }

    /**
     * Add a local file to the library; returns the existing record when identical content is already stored.
     */
    public static function import(string $absolutePath, ?string $originalName = null, ?int $folderId = null): Media
    {
        $sha1 = sha1_file($absolutePath);
        if ($existing = Media::firstWhere('sha1', $sha1)) {
            return $existing;
        }

        $name = $originalName ?? basename($absolutePath);
        $file = self::inspect($absolutePath, $name);
        $path = self::targetPath($name, $sha1, $file['extension']);

        Storage::disk('public')->put($path, file_get_contents($absolutePath));

        try {
            return Media::create([
                'folder_id' => $folderId,
                'disk' => 'public',
                'path' => $path,
                'name' => Str::limit($name, 250, ''),
                'mime_type' => $file['mime'],
                'size' => filesize($absolutePath),
                'width' => $file['width'],
                'height' => $file['height'],
                'sha1' => $sha1,
                'variants' => self::makeVariants($path, $file['mime'], $file['width']),
            ]);
        } catch (Throwable $e) {
            Storage::disk('public')->delete($path);
            throw $e;
        }
    }

    /**
     * Swap the file behind an existing record, so every page using it shows the new file.
     */
    public static function replace(Media $media, UploadedFile $upload): Media
    {
        $absolutePath = $upload->getRealPath();
        $sha1 = sha1_file($absolutePath);
        if ($sha1 === $media->sha1) {
            return $media;
        }
        if (Media::where('sha1', $sha1)->whereKeyNot($media->getKey())->exists()) {
            throw new InvalidArgumentException('This file already exists in the media library.');
        }

        $file = self::inspect($absolutePath, $upload->getClientOriginalName());
        $path = self::targetPath($upload->getClientOriginalName(), $sha1, $file['extension']);
        Storage::disk('public')->put($path, file_get_contents($absolutePath));
        $oldFiles = self::files($media);

        DB::transaction(fn () => $media->update([
            'path' => $path,
            'name' => Str::limit($upload->getClientOriginalName(), 250, ''),
            'mime_type' => $file['mime'],
            'size' => filesize($absolutePath),
            'width' => $file['width'],
            'height' => $file['height'],
            'sha1' => $sha1,
            'variants' => self::makeVariants($path, $file['mime'], $file['width']),
        ]));

        Storage::disk('public')->delete($oldFiles);

        return $media;
    }

    /**
     * Number of places referencing the file (content records and settings).
     */
    public static function usageCount(Media $media): int
    {
        $count = 0;
        foreach (self::REFERENCES as $table => $columns) {
            $count += DB::table($table)->where(function ($query) use ($columns, $media) {
                foreach ($columns as $column) {
                    $query->orWhere($column, $media->getKey());
                }
            })->count();
        }

        return $count + Setting::where('value', $media->path)->count();
    }

    public static function delete(Media $media): void
    {
        if (self::usageCount($media) > 0) {
            throw new InvalidArgumentException('This file is still in use and cannot be deleted.');
        }

        $files = self::files($media);
        $media->delete();
        Storage::disk($media->disk)->delete($files);
    }

    /**
     * @return array{mime: string, extension: string, width: int|null, height: int|null}
     */
    private static function inspect(string $absolutePath, string $name): array
    {
        $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($absolutePath);
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if ($mime === 'text/xml' || $mime === 'text/plain') {
            $mime = $extension === 'svg' ? 'image/svg+xml' : $mime;
        }

        if (! isset(self::ALLOWED[$mime]) || ! in_array($extension, self::ALLOWED[$mime], true)) {
            throw new InvalidArgumentException(__('Files of type [:mime] with extension [.:extension] are not allowed.', ['mime' => $mime, 'extension' => $extension]));
        }

        if ($mime === 'image/svg+xml' && ! self::isSafeSvg((string) file_get_contents($absolutePath))) {
            throw new InvalidArgumentException('SVG files containing scripts, event handlers or external content are not allowed.');
        }

        $size = in_array($mime, [...self::RASTER, 'image/gif'], true) ? @getimagesize($absolutePath) : false;

        return ['mime' => $mime, 'extension' => $extension, 'width' => $size[0] ?? null, 'height' => $size[1] ?? null];
    }

    public static function isSafeSvg(string $svg): bool
    {
        return ! preg_match('/<script|<foreignObject|\son\w+\s*=|javascript:|<iframe|<embed|<object|xlink:href\s*=\s*["\'](?!#)|<!ENTITY/i', $svg);
    }

    private static function targetPath(string $name, string $sha1, string $extension): string
    {
        $stem = Str::slug(pathinfo($name, PATHINFO_FILENAME)) ?: 'file';

        return 'media/'.date('Y/m').'/'.Str::limit($stem, 80, '').'-'.substr($sha1, 0, 8).'.'.$extension;
    }

    /**
     * @return array<int, string>|null width => path
     */
    private static function makeVariants(string $path, string $mime, ?int $width): ?array
    {
        if (! in_array($mime, self::RASTER, true) || $width === null) {
            return null;
        }

        $variants = [];
        $manager = ImageManager::usingDriver(Driver::class);
        $source = Storage::disk('public')->path($path);
        $base = preg_replace('/\.\w+$/', '', $path);

        foreach (self::VARIANT_WIDTHS as $variantWidth) {
            if ($variantWidth >= $width) {
                break;
            }
            $variantPath = "{$base}-{$variantWidth}.webp";
            $encoded = $manager->decodePath($source)->scaleDown(width: $variantWidth)->encode(new WebpEncoder(quality: 80));
            Storage::disk('public')->put($variantPath, (string) $encoded);
            $variants[$variantWidth] = $variantPath;
        }

        return $variants ?: null;
    }

    /**
     * @return list<string>
     */
    private static function files(Media $media): array
    {
        return array_values([$media->path, ...array_values($media->variants ?? [])]);
    }
}
