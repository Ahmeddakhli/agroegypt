<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sliders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key', 50)->unique();
            $table->string('effect', 20)->default('zoom');
            $table->boolean('autoplay')->default(true);
            $table->unsignedInteger('interval_ms')->default(5000);
            $table->unsignedInteger('transition_ms')->default(1500);
            $table->boolean('show_arrows')->default(true);
            $table->unsignedSmallInteger('height_desktop')->default(813);
            $table->unsignedSmallInteger('height_tablet')->default(600);
            $table->unsignedSmallInteger('height_mobile')->default(450);
            $table->timestamps();
        });

        Schema::create('slides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('mobile_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('url')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['slider_id', 'is_active', 'sort']);
        });

        Schema::create('slide_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slide_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 12);
            $table->foreign('locale')->references('code')->on('languages')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('button_label')->nullable();
            $table->unique(['slide_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slide_translations');
        Schema::dropIfExists('slides');
        Schema::dropIfExists('sliders');
    }
};
