<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Repeater;
use Livewire\Livewire;
use Tests\AdminTestCase;

class ProductEditorAdminTest extends AdminTestCase
{
    private Category $citrus;

    protected function setUp(): void
    {
        parent::setUp();

        Page::factory()->template(Page::TEMPLATE_PRODUCTS)->create(['en' => ['title' => 'Our Products', 'slug' => 'our-products']]);
        $this->citrus = Category::factory()->create(['en' => ['name' => 'Citrus', 'slug' => 'citrus'], 'ar' => ['name' => 'الموالح', 'slug' => 'citrus']]);
    }

    public function test_product_specifications_are_saved_with_translations_and_rendered(): void
    {
        $carton = $this->image('carton.png', 300, 300);
        $pallet = $this->image('pallet.png', 120, 120);
        $photo = $this->image('navel.jpg', 900, 900);
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'en' => ['name' => 'Navels', 'slug' => 'navel', 'subtitle' => 'Navels', 'description' => '<p>Seedless.</p>', 'spec_label' => 'Counts'],
                'ar' => ['name' => 'أبو سرة', 'slug' => 'navel', 'spec_label' => 'الأحجام'],
                'category_id' => $this->citrus->id,
                'status' => ContentStatus::Published,
                'image_media_id' => $photo->id,
                'spec_values' => ['40', '48', '56'],
                'varieties' => [
                    ['en' => ['name' => 'Washington Navels'], 'ar' => ['name' => 'أبو سرة واشنطن']],
                    ['en' => ['name' => 'Cara Cara Navels']],
                ],
                'packagings' => [[
                    'icon_media_id' => $carton->id,
                    'en' => ['title' => 'Open Top Carton (Net Weight 15 Kgs)'],
                    'ar' => ['title' => 'كرتونة مفتوحة'],
                    'specs' => [
                        ['value' => '20', 'icon_media_id' => $pallet->id, 'en' => ['label' => 'No. of Pallets/Container']],
                        ['value' => '65', 'en' => ['label' => 'No. of Cartons/Pallet'], 'ar' => ['label' => 'عدد الكراتين']],
                    ],
                ]],
                'seasons' => [['months' => [12, 1, 2], 'en' => ['label' => 'Washington Navels']]],
                'imageItems' => [['media_id' => $photo->id]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $undoRepeaterFake();

        $product = Product::firstOrFail();
        $this->assertSame(['40', '48', '56'], $product->spec_values);
        $this->assertSame(['Washington Navels', 'Cara Cara Navels'], $product->varieties->map(fn ($v) => $v->translate('en')->name)->all());
        $this->assertSame('أبو سرة واشنطن', $product->varieties->first()->translate('ar')->name);
        $this->assertSame(['20', '65'], $product->packagings->first()->specs->pluck('value')->all());
        $this->assertSame([12, 1, 2], $product->seasons->first()->months);
        $this->assertSame([$photo->id], $product->images->pluck('id')->all());

        $this->get('/en/our-products/citrus/navel')->assertOk()
            ->assertSeeInOrder(['Washington Navels', 'Cara Cara Navels'])
            ->assertSee('Open Top Carton (Net Weight 15 Kgs)')
            ->assertSee('No. of Cartons/Pallet')
            ->assertSee('Dec')
            ->assertSee($pallet->url());

        $this->get('/ar/our-products/citrus/navel')->assertOk()
            ->assertSee('أبو سرة واشنطن')
            ->assertSee('عدد الكراتين')
            ->assertSee('No. of Pallets/Container'); // fallback to English
    }

    public function test_specification_rows_can_be_edited_reordered_and_removed(): void
    {
        $product = Product::factory()->create(['category_id' => $this->citrus->id, 'en' => ['name' => 'Lemons', 'slug' => 'lemon']]);
        $eureka = $product->varieties()->create(['sort' => 1, 'en' => ['name' => 'Eureka']]);
        $lisbon = $product->varieties()->create(['sort' => 2, 'en' => ['name' => 'Lisbon']]);
        $packaging = $product->packagings()->create(['sort' => 1, 'en' => ['title' => 'Carton']]);
        $season = $product->seasons()->create(['sort' => 1, 'months' => [12], 'en' => ['label' => 'Eureka']]);

        $livewire = Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormSet(["varieties.record-{$eureka->id}.en.name" => 'Eureka', "seasons.record-{$season->id}.months" => [12]]);

        $varieties = $livewire->get('data.varieties');
        $varieties["record-{$eureka->id}"]['en']['name'] = 'Eureka Lemon';

        $livewire
            ->set('data.varieties', ["record-{$lisbon->id}" => $varieties["record-{$lisbon->id}"], "record-{$eureka->id}" => $varieties["record-{$eureka->id}"]])
            ->set('data.packagings', [])
            ->fillForm(["seasons.record-{$season->id}.months" => [12, 1, 2, 3]])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertSame(['Lisbon', 'Eureka Lemon'], $product->varieties->map(fn ($v) => $v->translate('en')->name)->all());
        $this->assertSame(0, $product->packagings()->count());
        $this->assertNull($packaging->fresh());
        $this->assertSame([12, 1, 2, 3], $product->seasons->first()->months);
    }

    public function test_products_can_be_published_and_unpublished_in_bulk(): void
    {
        $products = Product::factory()->count(2)->draft()->create(['category_id' => $this->citrus->id]);

        Livewire::test(ListProducts::class)
            ->selectTableRecords($products)
            ->callAction(TestAction::make('publish')->table()->bulk());

        $this->assertSame(2, Product::where('status', ContentStatus::Published)->count());
        $this->get('/en/our-products/citrus')->assertSee($products[0]->name);

        Livewire::test(ListProducts::class)
            ->selectTableRecords($products)
            ->callAction(TestAction::make('unpublish')->table()->bulk());

        $this->assertSame(0, Product::where('status', ContentStatus::Published)->count());
    }

    public function test_products_list_searches_by_translated_name_and_filters_by_category(): void
    {
        $navel = Product::factory()->create(['category_id' => $this->citrus->id, 'en' => ['name' => 'Navels', 'slug' => 'navel']]);
        $mango = Product::factory()->create(['en' => ['name' => 'Mango', 'slug' => 'mango']]);

        Livewire::test(ListProducts::class)
            ->searchTable('Nav')
            ->assertCanSeeTableRecords([$navel])
            ->assertCanNotSeeTableRecords([$mango]);

        Livewire::test(ListProducts::class)
            ->filterTable('category_id', $this->citrus->id)
            ->assertCanSeeTableRecords([$navel])
            ->assertCanNotSeeTableRecords([$mango]);
    }

    public function test_category_is_created_and_moved_with_its_products(): void
    {
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'en' => ['name' => 'Oranges', 'slug' => 'orange'],
                'ar' => ['name' => 'البرتقال', 'slug' => 'orange'],
                'parent_id' => $this->citrus->id,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $oranges = Category::whereTranslation('slug', 'orange')->firstOrFail();
        Product::factory()->create(['category_id' => $oranges->id, 'en' => ['name' => 'Valencia', 'slug' => 'valencia']]);

        $this->get('/en/our-products/citrus/orange')->assertOk()->assertSee('Valencia');
        $this->get('/ar/our-products/citrus')->assertOk()->assertSee('البرتقال');

        Livewire::test(EditCategory::class, ['record' => $oranges->getRouteKey()])
            ->fillForm(['parent_id' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/en/our-products/orange/valencia')->assertOk();
    }

    public function test_hidden_categories_hide_their_products(): void
    {
        Product::factory()->create(['category_id' => $this->citrus->id, 'en' => ['name' => 'Lemons', 'slug' => 'lemon']]);
        $this->citrus->update(['is_active' => false]);

        $this->get('/en/our-products/citrus')->assertNotFound();
        $this->get('/en/our-products/citrus/lemon')->assertNotFound();
    }
}
