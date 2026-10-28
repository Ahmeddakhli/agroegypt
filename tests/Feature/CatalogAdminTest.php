<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\Language;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogAdminTest extends TestCase
{
    use RefreshDatabase;

    private Category $onions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Language::factory()->english()->create();
        Language::factory()->arabic()->create();
        $this->actingAs(User::factory()->create()->assignRole('editor'));
        $this->onions = Category::factory()->create(['en' => ['name' => 'Onions', 'slug' => 'onions']]);
    }

    public function test_editor_creates_a_product_with_translations(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'en' => ['name' => 'Red Onion', 'slug' => 'red', 'description' => '<p>Round and mild.</p>'],
                'ar' => ['name' => 'البصل الأحمر', 'slug' => 'red'],
                'category_id' => $this->onions->id,
                'status' => ContentStatus::Published,
                'spec_values' => ['40-60', '60-80'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::firstOrFail();
        $this->assertSame('البصل الأحمر', $product->translate('ar')->name);
        $this->assertSame(['40-60', '60-80'], $product->spec_values);
        $this->get('/ar/our-products/onions/red')->assertStatus(404); // no products page exists in this test
    }

    public function test_empty_translations_are_not_stored(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm(['en' => ['name' => 'Garlic', 'slug' => 'garlic'], 'ar' => ['name' => '', 'slug' => ''], 'status' => ContentStatus::Draft])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertFalse(Product::firstOrFail()->hasTranslation('ar'));
    }

    public function test_slugs_must_be_unique_among_siblings_only(): void
    {
        Product::factory()->create(['category_id' => $this->onions->id, 'en' => ['name' => 'Red', 'slug' => 'red']]);
        $garlic = Category::factory()->create(['en' => ['name' => 'Garlic', 'slug' => 'garlic']]);

        Livewire::test(CreateProduct::class)
            ->fillForm(['en' => ['name' => 'Red 2', 'slug' => 'red'], 'category_id' => $this->onions->id, 'status' => ContentStatus::Draft])
            ->call('create')
            ->assertHasFormErrors(['en.slug']);

        Livewire::test(CreateProduct::class)
            ->fillForm(['en' => ['name' => 'Red Garlic', 'slug' => 'red'], 'category_id' => $garlic->id, 'status' => ContentStatus::Draft])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_clearing_a_translation_deletes_it(): void
    {
        $product = Product::factory()->create(['ar' => ['name' => 'ثوم', 'slug' => 'garlic']]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormSet(['ar.name' => 'ثوم'])
            ->fillForm(['ar' => ['name' => '', 'slug' => '', 'subtitle' => null, 'description' => null]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($product->fresh()->hasTranslation('ar'));
    }

    public function test_duplicate_copies_children_as_a_draft(): void
    {
        $product = Product::factory()->create(['en' => ['name' => 'Navels', 'slug' => 'navel']]);
        $product->varieties()->create(['sort' => 0, 'en' => ['name' => 'Cara Cara']]);
        $packaging = $product->packagings()->create(['sort' => 0, 'en' => ['title' => 'Carton']]);
        $packaging->specs()->create(['value' => '65', 'sort' => 0, 'en' => ['label' => 'Cartons/Pallet']]);
        $product->seasons()->create(['months' => [12, 1], 'sort' => 0, 'en' => ['label' => 'Navels']]);

        $copy = ProductResource::duplicate($product);

        $this->assertSame(ContentStatus::Draft, $copy->status);
        $this->assertSame('Navels (copy)', $copy->translate('en')->name);
        $this->assertSame('Cara Cara', $copy->varieties()->first()->translate('en')->name);
        $this->assertSame('65', $copy->packagings()->first()->specs()->first()->value);
        $this->assertSame([12, 1], $copy->seasons()->first()->months);
        $this->assertSame(1, $product->varieties()->count());
    }

    public function test_categories_cannot_become_their_own_ancestors(): void
    {
        $child = Category::factory()->create(['parent_id' => $this->onions->id]);

        $options = CategoryResource::parentOptions($this->onions);

        $this->assertArrayNotHasKey($this->onions->id, $options);
        $this->assertArrayNotHasKey($child->id, $options);
    }

    public function test_translators_cannot_create_catalog_records_and_inbox_users_cannot_see_them(): void
    {
        $this->actingAs(User::factory()->create()->assignRole('translator'));
        $this->get(ProductResource::getUrl('index'))->assertOk();
        Livewire::test(CreateCategory::class)->assertForbidden();

        $this->actingAs(User::factory()->create()->assignRole('inbox'));
        $this->get(ProductResource::getUrl('index'))->assertForbidden();
    }
}
