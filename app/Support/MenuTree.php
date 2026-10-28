<?php

namespace App\Support;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Support\Collection;

/**
 * Resolved, cached menu trees per location and language.
 */
final class MenuTree
{
    /**
     * @return list<array{label: string, url: string, target: string, children: list<mixed>}>
     */
    public static function for(string $location): array
    {
        $locale = app()->getLocale();

        return ContentCache::remember("menu.{$location}.{$locale}", function () use ($location, $locale): array {
            $menu = Menu::where('location', $location)->first();
            if ($menu === null) {
                return [];
            }

            $items = MenuItem::where('menu_id', $menu->id)->where('is_visible', true)
                ->with(['translations', 'linkable'])->orderBy('sort')->get();

            return self::branch($items->groupBy(fn (MenuItem $item) => $item->parent_id ?? 0), 0, $locale);
        });
    }

    /**
     * @param  Collection<int|string, Collection<int, MenuItem>>  $byParent
     * @return list<array{label: string, url: string, target: string, children: list<mixed>}>
     */
    private static function branch(Collection $byParent, int $parentId, string $locale): array
    {
        $branch = [];

        foreach ($byParent->get($parentId, collect()) as $item) {
            $url = $item->linkable ? SiteIndex::url($item->linkable, $locale) : SiteIndex::link($item->url, $locale);
            if ($url === null) {
                continue; // linked record is unpublished: hide the entry instead of rendering a dead link
            }

            $branch[] = [
                'label' => (string) $item->label,
                'url' => $url,
                'target' => $item->target === '_blank' ? '_blank' : '_self',
                'children' => self::branch($byParent, $item->id, $locale),
            ];
        }

        return $branch;
    }
}
