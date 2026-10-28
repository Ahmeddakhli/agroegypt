<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Redirect;
use App\Models\Setting;
use App\Support\ContentCache;
use App\Support\SiteIndex;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * "/" goes to the visitor's preferred active language, else the default one.
     */
    public function home(Request $request): RedirectResponse
    {
        // getPreferredLanguage() falls back to the first entry when the browser sends no preference.
        $codes = Language::active()->sortByDesc('is_default')->pluck('code')->values()->all();
        $preferred = $request->getPreferredLanguage($codes);

        return redirect(SiteIndex::localizedUrl(in_array($preferred, $codes, true) ? $preferred : Language::defaultCode()), 302);
    }

    public function sitemap(): Response
    {
        $xml = ContentCache::remember('sitemap', function (): string {
            $languages = Language::active();
            $urls = [];
            foreach ($languages as $language) {
                foreach (SiteIndex::entries($language->code) as $key => $entry) {
                    if (! $entry['translated'] && ! $language->is_default) {
                        continue;
                    }
                    $urls[$key][$language->code] = $entry + ['url' => SiteIndex::localizedUrl($language->code, $entry['path'])];
                }
            }

            return view('seo.sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $lines = ['User-agent: *'];
        if (Setting::get('seo.indexing', '1') === '1') {
            $lines[] = 'Disallow: /admin';
            foreach (preg_split('/\R/', (string) Setting::get('seo.robots_disallow', '')) as $path) {
                if (preg_match('#^/[\w\-./*]*$#', trim($path))) {
                    $lines[] = 'Disallow: '.trim($path);
                }
            }
            $lines[] = '';
            $lines[] = 'Sitemap: '.url('sitemap.xml');
        } else {
            $lines[] = 'Disallow: /';
        }

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * Any unmatched URL: legacy redirect when one is configured, otherwise 404.
     */
    public function fallback(Request $request): RedirectResponse
    {
        return Redirect::respondTo($request->path()) ?? abort(404);
    }
}
