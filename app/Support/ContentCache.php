<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Versioned cache for rendered-content lookups (site index, menus). Any content change bumps the
 * version, which invalidates every entry at once without needing cache tags.
 */
final class ContentCache
{
    private const VERSION_KEY = 'content.version';

    private const TTL_SECONDS = 86400;

    /** @var array<string, mixed> per-request copies, so repeated lookups skip the cache store */
    private static array $memo = [];

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function remember(string $key, Closure $callback): mixed
    {
        if (array_key_exists($key, self::$memo)) {
            return self::$memo[$key];
        }

        $version = Cache::rememberForever(self::VERSION_KEY, fn () => 1);

        return self::$memo[$key] = Cache::remember("content.{$version}.{$key}", self::TTL_SECONDS, $callback);
    }

    public static function flush(): void
    {
        self::$memo = [];
        Cache::forever(self::VERSION_KEY, (int) Cache::get(self::VERSION_KEY, 1) + 1);
    }
}
