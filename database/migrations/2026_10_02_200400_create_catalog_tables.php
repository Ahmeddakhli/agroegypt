<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->foreignId('icon_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('card_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('hero_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'sort']);
        });

        Schema::create('category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 12);
            $table->foreign('locale')->references('code')->on('languages')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('menu_label')->nullable();
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->unique(['category_id', 'locale']);
            $table->index(['locale', 'slug']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('icon_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('card_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('hero_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('season_icon_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->json('spec_values')->nullable();
            $table->string('status', 20)->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'status', 'sort']);
        });

        Schema::create('product_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 12);
            $table->foreign('locale')->references('code')->on('languages')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('menu_label')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('short_description', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('spec_label')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->unique(['product_id', 'locale']);
            $table->index(['locale', 'slug']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->unique(['product_id', 'media_id']);
        });

        Schema::create('product_varieties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->index(['product_id', 'sort']);
        });

        Schema::create('product_variety_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variety_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 12);
            $table->foreign('locale')->references('code')->on('languages')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name');
            $table->unique(['product_variety_id', 'locale']);
        });

        Schema::create('product_packagings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('icon_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->index(['product_id', 'sort']);
        });

        Schema::create('product_packaging_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_packaging_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 12);
            $table->foreign('locale')->references('code')->on('languages')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('title');
            $table->unique(['product_packaging_id', 'locale'], 'pkg_trans_unique');
        });

        Schema::create('packaging_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_packaging_id')->constrained()->cascadeOnDelete();
            $table->foreignId('icon_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('value', 50);
            $table->unsignedInteger('sort')->default(0);
        });

        Schema::create('packaging_spec_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('packaging_spec_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 12);
            $table->foreign('locale')->references('code')->on('languages')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('label');
            $table->unique(['packaging_spec_id', 'locale']);
        });

        Schema::create('product_seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->json('months');
            $table->unsignedInteger('sort')->default(0);
            $table->index(['product_id', 'sort']);
        });

        Schema::create('product_season_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_season_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 12);
            $table->foreign('locale')->references('code')->on('languages')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('label');
            $table->unique(['product_season_id', 'locale']);
        });
    }

    public function down(): void
    {
        foreach ([
            'product_season_translations', 'product_seasons', 'packaging_spec_translations', 'packaging_specs',
            'product_packaging_translations', 'product_packagings', 'product_variety_translations', 'product_varieties',
            'product_images', 'product_translations', 'products', 'category_translations', 'categories',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
