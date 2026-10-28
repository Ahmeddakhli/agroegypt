<?php

namespace Tests;

use App\Models\Language;
use App\Models\Setting;
use App\Support\ContentCache;
use App\Support\UrlRedirects;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Per-request memos are static; reset them so no state leaks between tests.
        ContentCache::flush();
        Setting::flush();
        Language::flush();
        UrlRedirects::reset();
    }
}
