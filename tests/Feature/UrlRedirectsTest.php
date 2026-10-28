<?php

namespace Tests\Feature;

use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\Redirect;
use Livewire\Livewire;
use Tests\AdminTestCase;

class UrlRedirectsTest extends AdminTestCase
{
    private Category $citrus;

    private Product $navel;

    protected function setUp(): void
    {
        parent::setUp();

        Page::factory()->template(Page::TEMPLATE_PRODUCTS)->create(['en' => ['title' => 'Our Products', 'slug' => 'our-products'], 'ar' => ['title' => 'منتجاتنا', 'slug' => 'our-products']]);
        $this->citrus = Category::factory()->create(['en' => ['name' => 'Citrus', 'slug' => 'citrus'], 'ar' => ['name' => 'الموالح', 'slug' => 'citrus']]);
        $this->navel = Product::factory()->create(['category_id' => $this->citrus->id, 'en' => ['name' => 'Navels', 'slug' => 'navel'], 'ar' => ['name' => 'أبو سرة', 'slug' => 'navel']]);
    }

    public function test_renaming_a_slug_redirects_the_old_url(): void
    {
        Livewire::test(EditProduct::class, ['record' => $this->navel->getRouteKey()])
            ->fillForm(['en' => ['slug' => 'navel-oranges']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/en/our-products/citrus/navel-oranges')->assertOk();
        $this->get('/en/our-products/citrus/navel')->assertStatus(301)->assertRedirect(url('/en/our-products/citrus/navel-oranges'));
        $this->get('/ar/our-products/citrus/navel')->assertOk(); // the Arabic slug did not change
    }

    public function test_moving_a_category_redirects_it_and_everything_below_it(): void
    {
        $fruit = Category::factory()->create(['en' => ['name' => 'Fruit', 'slug' => 'fruit']]);

        Livewire::test(EditCategory::class, ['record' => $this->citrus->getRouteKey()])
            ->fillForm(['parent_id' => $fruit->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/en/our-products/citrus')->assertRedirect(url('/en/our-products/fruit/citrus'));
        $this->get('/en/our-products/citrus/navel')->assertRedirect(url('/en/our-products/fruit/citrus/navel'));
        $this->get('/ar/our-products/citrus/navel')->assertRedirect(url('/ar/our-products/fruit/citrus/navel'));
        $this->get('/en/our-products/fruit/citrus/navel')->assertOk();
    }

    public function test_existing_redirects_are_repointed_and_renaming_back_does_not_loop(): void
    {
        Redirect::create(['from_path' => '/our-products/citrus/navel/', 'to_path' => '/en/our-products/citrus/navel']);

        $this->navel->update(['en' => ['slug' => 'navels']]);
        $this->assertSame('/en/our-products/citrus/navels', Redirect::firstWhere('from_path', '/our-products/citrus/navel')->to_path);

        $this->navel->update(['en' => ['slug' => 'navel']]);

        $this->get('/en/our-products/citrus/navel')->assertOk();
        $this->get('/en/our-products/citrus/navels')->assertRedirect(url('/en/our-products/citrus/navel'));
        $this->get('/our-products/citrus/navel/')->assertRedirect(url('/en/our-products/citrus/navel'));
        $this->assertNull(Redirect::firstWhere('from_path', '/en/our-products/citrus/navel'));
    }

    public function test_unpublishing_does_not_create_redirects(): void
    {
        $this->navel->update(['status' => 'draft']);

        $this->assertSame(0, Redirect::count());
        $this->get('/en/our-products/citrus/navel')->assertNotFound();
    }
}
