<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('template', 30)->default('default');
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('hero_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('show_hero')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
        });

        Schema::create('page_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 12);
            $table->foreign('locale')->references('code')->on('languages')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->foreignId('og_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unique(['page_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });

        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['page_id', 'sort']);
        });

        Schema::create('page_section_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_section_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 12);
            $table->foreign('locale')->references('code')->on('languages')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->longText('body')->nullable();
            $table->string('button_label')->nullable();
            $table->string('button_url')->nullable();
            $table->unique(['page_section_id', 'locale']);
        });

        Schema::create('section_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_section_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('icon_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('url')->nullable();
            $table->string('value')->nullable();
            $table->timestamps();

            $table->index(['page_section_id', 'sort']);
        });

        Schema::create('section_item_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_item_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 12);
            $table->foreign('locale')->references('code')->on('languages')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->unique(['section_item_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_item_translations');
        Schema::dropIfExists('section_items');
        Schema::dropIfExists('page_section_translations');
        Schema::dropIfExists('page_sections');
        Schema::dropIfExists('page_translations');
        Schema::dropIfExists('pages');
    }
};
