<?php

use App\Http\Controllers\ContentController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\SeoController;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SiteMaintenance;
use App\Models\Language;
use Illuminate\Support\Facades\Route;

Route::get('/', [SeoController::class, 'home'])->middleware(SiteMaintenance::class)->name('root');
Route::get('sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('robots.txt', [SeoController::class, 'robots'])->name('robots');

Route::prefix('{locale}')
    ->where(['locale' => Language::CODE_PATTERN])
    ->middleware([SiteMaintenance::class, SetLocale::class])
    ->group(function () {
        Route::post('forms/contact', [FormController::class, 'contact'])->middleware('throttle:forms')->name('forms.contact');
        Route::post('forms/careers', [FormController::class, 'careers'])->middleware('throttle:forms')->name('forms.careers');
        Route::get('preview/pages/{page}', [ContentController::class, 'preview'])->middleware('signed')->name('pages.preview');
        Route::get('{path?}', ContentController::class)->where('path', '.*')->name('content');
    });

Route::fallback([SeoController::class, 'fallback']);
