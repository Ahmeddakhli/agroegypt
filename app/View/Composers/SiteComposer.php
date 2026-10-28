<?php

namespace App\View\Composers;

use App\Models\Language;
use App\Models\Setting;
use App\Support\MenuTree;
use App\Support\SiteIndex;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * Shared data for the public layout: menus, contact/social settings, theme variables, languages.
 */
class SiteComposer
{
    /** @var array<string, string> theme setting => CSS variable */
    public const COLOR_VARIABLES = [
        'theme.color_dark' => '--c-dark',
        'theme.color_dark_2' => '--c-dark-2',
        'theme.color_primary' => '--c-primary',
        'theme.color_primary_hover' => '--c-primary-hover',
        'theme.color_accent' => '--c-orange',
        'theme.color_band' => '--c-olive',
        'theme.color_text' => '--c-text',
        'theme.color_body_bg' => '--c-body-bg',
    ];

    /** @var array<string, string> card shadow presets (no free-form CSS) */
    public const SHADOWS = [
        'none' => 'none',
        'soft' => '0 0 30px rgba(0,0,0,.07)',
        'medium' => '0 0 60px rgba(0,0,0,.12)',
        'strong' => '0 12px 60px rgba(0,0,0,.25)',
    ];

    /** @var list<string> appearance settings the admin can preview before saving */
    public const PREVIEWABLE = [
        'theme.color_dark', 'theme.color_dark_2', 'theme.color_primary', 'theme.color_primary_hover', 'theme.color_accent',
        'theme.color_band', 'theme.color_text', 'theme.color_body_bg', 'theme.font_latin', 'theme.font_arabic',
        'theme.radius', 'theme.section_spacing', 'theme.shadow', 'theme.animations', 'theme.hover_effects',
    ];

    /** @var array<string, array<string, string>> bundled font choices (no external font requests) */
    public const FONTS = [
        'latin' => ['Heebo' => 'Heebo (original)', 'system-ui' => 'System font'],
        'arabic' => ['Cairo' => 'Cairo', 'system-ui' => 'System font'],
    ];

    /** @var array<string, mixed>|null */
    private ?array $shared = null;

    public function compose(View $view): void
    {
        $view->with($this->shared());

        if ($view->name() === 'layouts.site' || $view->name() === 'errors::404') {
            $switcherModel = $view->getData()['switcherModel'] ?? null;
            $view->with('languages', Language::active()->map(fn (Language $language) => [
                'language' => $language,
                'url' => ($switcherModel ? SiteIndex::url($switcherModel, $language->code) : null) ?? SiteIndex::localizedUrl($language->code),
            ]));
        }
    }

    /**
     * Computed once per request and locale (the composer is a singleton) and shared with every public view.
     *
     * @return array<string, mixed>
     */
    private function shared(): array
    {
        $locale = app()->getLocale();
        $requestKey = spl_object_id(request()).'|'.$locale;

        if ($this->shared !== null && $this->shared['key'] === $requestKey) {
            return $this->shared;
        }

        $fallback = config('translatable.fallback_locale');
        $setting = fn (string $key, mixed $default = null) => Setting::get($key, $default, $locale, $fallback);

        $preview = $this->previewOverrides();
        $flag = fn (string $key) => in_array($preview[$key] ?? $setting($key, '1'), [true, 1, '1'], true);

        return $this->shared = [
            'key' => $requestKey,
            'currentLanguage' => Language::findByCode($locale),
            'headerMenu' => MenuTree::for('header'),
            'footerMenu' => MenuTree::for('footer'),
            'setting' => $setting,
            'themeCss' => $this->themeCss($preview),
            'animationsEnabled' => $flag('theme.animations'),
            'hoverEffects' => $flag('theme.hover_effects'),
        ];
    }

    /**
     * Only validated values reach the stylesheet: hex colours, a px radius and a spacing factor.
     */
    /**
     * @param  array<string, mixed>  $overrides  unsaved values from the admin preview
     */
    private function themeCss(array $overrides = []): string
    {
        // First valid candidate wins: the unsaved preview value, then the saved setting.
        $value = function (string $key, callable $isValid) use ($overrides): mixed {
            foreach ([$overrides[$key] ?? null, Setting::get($key)] as $candidate) {
                if ($candidate !== null && $isValid($candidate)) {
                    return $candidate;
                }
            }

            return null;
        };
        $rules = [];

        foreach (self::COLOR_VARIABLES as $key => $variable) {
            if ($color = $value($key, fn ($v) => is_string($v) && preg_match('/^#[0-9a-fA-F]{3,8}$/', $v))) {
                $rules[] = "{$variable}:{$color}";
            }
        }

        foreach (['latin', 'arabic'] as $script) {
            if ($font = $value("theme.font_{$script}", fn ($v) => is_string($v) && isset(self::FONTS[$script][$v]))) {
                $rules[] = "--font-{$script}:".($font === 'system-ui' ? 'system-ui' : "'{$font}'");
            }
        }

        if ($shadow = $value('theme.shadow', fn ($v) => is_string($v) && isset(self::SHADOWS[$v]))) {
            $rules[] = '--shadow-card:'.self::SHADOWS[$shadow];
        }

        $radius = $value('theme.radius', fn ($v) => is_numeric($v) && $v >= 0 && $v <= 40);
        if ($radius !== null) {
            $rules[] = '--radius-card:'.(int) $radius.'px';
        }

        $spacing = $value('theme.section_spacing', fn ($v) => is_numeric($v) && $v >= 0.25 && $v <= 3);
        if ($spacing !== null) {
            $rules[] = '--section-space:'.(float) $spacing;
        }

        return $rules === [] ? '' : ':root{'.implode(';', $rules).'}';
    }

    /**
     * Unsaved appearance values sent by the admin's live preview. Only honoured on a valid signed URL,
     * only for whitelisted keys, and every value still passes the same validation as saved settings.
     *
     * @return array<string, mixed>
     */
    private function previewOverrides(): array
    {
        $request = request();
        $payload = $request->query('theme_preview');

        if (! is_string($payload) || ! $request->hasValidSignature()) {
            return [];
        }

        $data = json_decode((string) base64_decode($payload, true), true);

        return is_array($data) ? array_intersect_key($data, array_flip(self::PREVIEWABLE)) : [];
    }

    /**
     * Signed link to the site rendered with unsaved appearance values (admin live preview).
     *
     * @param  array<string, mixed>  $values  "group.key" => value
     */
    public static function previewUrl(string $locale, array $values): string
    {
        // Expiry rounded to the hour so the URL (and the preview iframe) only changes when the values do.
        return URL::temporarySignedRoute('content', now()->startOfHour()->addHours(2), [
            'locale' => $locale,
            'theme_preview' => base64_encode((string) json_encode(array_intersect_key($values, array_flip(self::PREVIEWABLE)))),
        ]);
    }
}
