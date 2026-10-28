<?php

namespace Tests\Feature;

use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Resources\Galleries\Pages\ListGalleries;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Redirects\Pages\ListRedirects;
use App\Filament\Resources\Sliders\Pages\ListSliders;
use App\Models\Category;
use App\Models\Document;
use App\Models\Gallery;
use App\Models\Page;
use App\Models\Product;
use App\Models\Redirect;
use App\Models\Slider;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Tests\AdminTestCase;

class AdminDeleteRestoreTest extends AdminTestCase
{
    public function test_soft_deletable_content_can_be_deleted_and_restored(): void
    {
        $page = Page::factory()->create();
        $product = Product::factory()->create();
        $category = Category::factory()->create();

        Livewire::test(ListPages::class)->callAction(TestAction::make('delete')->table($page));
        Livewire::test(ListProducts::class)->callAction(TestAction::make('delete')->table($product));
        Livewire::test(ListCategories::class)->callAction(TestAction::make('delete')->table($category));

        $this->assertSoftDeleted($page);
        $this->assertSoftDeleted($product);
        $this->assertSoftDeleted($category);

        Livewire::test(ListProducts::class)
            ->filterTable('trashed', true)
            ->callAction(TestAction::make('restore')->table($product));

        $this->assertNotSoftDeleted($product);
    }

    public function test_other_content_can_be_deleted(): void
    {
        $gallery = Gallery::factory()->create();
        $document = Document::factory()->create();
        $slider = Slider::factory()->create();
        $redirect = Redirect::create(['from_path' => '/old', 'to_path' => '/en']);

        Livewire::test(ListGalleries::class)->callAction(TestAction::make('delete')->table($gallery));
        Livewire::test(ListDocuments::class)->callAction(TestAction::make('delete')->table($document));
        Livewire::test(ListSliders::class)->callAction(TestAction::make('delete')->table($slider));
        Livewire::test(ListRedirects::class)->callAction(TestAction::make('delete')->table($redirect));

        $this->assertModelMissing($gallery);
        $this->assertModelMissing($document);
        $this->assertModelMissing($slider);
        $this->assertModelMissing($redirect);
    }

    public function test_categories_with_products_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        Livewire::test(ListCategories::class)
            ->assertActionDisabled(TestAction::make('delete')->table($category));

        $this->assertNotSoftDeleted($category);
    }

    public function test_pages_can_be_deleted_from_the_edit_screen(): void
    {
        $page = Page::factory()->create();

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])->callAction('delete');

        $this->assertSoftDeleted($page);
    }
}
