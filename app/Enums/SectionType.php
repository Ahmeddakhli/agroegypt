<?php

namespace App\Enums;

/**
 * Page builder section types. Each maps to resources/views/sections/{value}.blade.php.
 */
enum SectionType: string
{
    case HeroSlider = 'hero_slider';
    case IconGrid = 'icon_grid';
    case FeatureGrid = 'feature_grid';
    case BandHeading = 'band_heading';
    case Certificates = 'certificates';
    case CtaBackground = 'cta_background';
    case ImageText = 'image_text';
    case OverlapImage = 'overlap_image';
    case Mission = 'mission';
    case ProgressBars = 'progress_bars';
    case RichText = 'rich_text';
    case ImageCardGrid = 'image_card_grid';
    case CatalogGrid = 'catalog_grid';
    case MarketRegions = 'market_regions';
    case FullImage = 'full_image';
    case ContactInfo = 'contact_info';
    case ContactForm = 'contact_form';
    case CareersForm = 'careers_form';
    case Map = 'map';
    case GalleryGrid = 'gallery_grid';
    case Video = 'video';
    case CustomHtml = 'custom_html';

    /**
     * Admin form fields each section type uses (text fields are per language; items are repeatable rows).
     *
     * @return list<string>
     */
    public function fields(): array
    {
        return match ($this) {
            self::HeroSlider => ['slider_key'],
            self::IconGrid => ['title', 'items', 'item.icon', 'item.title', 'item.url'],
            self::FeatureGrid => ['title', 'items', 'item.icon', 'item.title', 'item.body'],
            self::BandHeading => ['title', 'button_url'],
            self::Certificates, self::CatalogGrid => ['title'],
            self::CtaBackground => ['title', 'subtitle', 'button_label', 'button_url', 'document', 'media'],
            self::ImageText => ['title', 'subtitle', 'body', 'button_label', 'button_url', 'media', 'image_position', 'image_offset', 'layout', 'media_as_background'],
            self::OverlapImage => ['title', 'body', 'media'],
            self::Mission => ['title', 'body', 'items', 'item.media'],
            self::ProgressBars => ['title', 'body', 'media', 'button_label', 'button_url', 'items', 'item.title', 'item.value'],
            self::RichText => ['title', 'body', 'divider', 'align'],
            self::ImageCardGrid => ['title', 'items', 'item.media', 'item.title', 'item.url', 'columns', 'caption', 'rounded', 'divider_after'],
            self::MarketRegions => ['items', 'item.title', 'item.body'],
            self::FullImage => ['media', 'boxed'],
            self::ContactInfo => ['items', 'item.icon', 'item.body', 'item.url'],
            self::ContactForm, self::CareersForm => ['title', 'body', 'button_label'],
            self::Map => ['title', 'button_url'],
            self::GalleryGrid => [],
            self::Video => ['title', 'button_url', 'media'],
            self::CustomHtml => ['html'],
        };
    }

    public function uses(string $field): bool
    {
        return in_array($field, $this->fields(), true);
    }

    public function label(): string
    {
        return __(str($this->name)->headline()->toString());
    }

    public function view(): string
    {
        return 'sections.'.$this->value;
    }
}
