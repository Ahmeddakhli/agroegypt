<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Allow-list HTML sanitizing for admin-authored rich text and the "custom HTML" section.
 */
final class Html
{
    private static ?HtmlSanitizer $rich = null;

    private static ?HtmlSanitizer $custom = null;

    /**
     * Rich text: formatting, lists, headings and links only.
     */
    public static function clean(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        self::$rich ??= new HtmlSanitizer(self::base()->allowElement('sup')->allowElement('sub'));

        return self::$rich->sanitize($html);
    }

    /**
     * Custom HTML sections: additionally keeps class/id attributes and images; scripts, inline
     * event handlers, styles, iframes and forms are always stripped.
     */
    public static function cleanCustom(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        self::$custom ??= new HtmlSanitizer(
            self::base()
                ->allowElement('div', ['class', 'id'])
                ->allowElement('section', ['class', 'id'])
                ->allowElement('figure', ['class'])
                ->allowElement('figcaption')
                ->allowElement('table')->allowElement('thead')->allowElement('tbody')->allowElement('tr')
                ->allowElement('th')->allowElement('td')
                ->allowElement('img', ['src', 'alt', 'width', 'height', 'class', 'loading'])
                ->allowMediaSchemes(['https'])
                ->allowRelativeMedias()
        );

        return self::$custom->sanitize($html);
    }

    private static function base(): HtmlSanitizerConfig
    {
        return (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->dropElement('img')
            ->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->withMaxInputLength(200_000);
    }
}
