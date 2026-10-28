<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\ContactMessage;
use App\Models\JobApplication;
use App\Models\Language;
use App\Models\LoginLog;
use App\Models\Redirect;
use App\Models\Setting;
use App\Models\User;
use App\Support\ContentCache;
use App\Support\UrlRedirects;
use App\View\Composers\SiteComposer;
use Filament\Schemas\Components\Section;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SiteComposer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Language::syncTranslatableConfig();
        $this->applyTimezone();

        Section::configureUsing(fn (Section $section) => $section->columnSpanFull());

        $this->flushContentCacheOnChanges();

        View::composer(['layouts.site', 'pages.*', 'catalog.*', 'gallery.*', 'sections.*', 'partials.*', 'errors::*'], SiteComposer::class);

        RateLimiter::for('forms', fn ($request) => Limit::perMinute(5)->by($request->ip()));

        Gate::before(fn (User $user) => $user->hasRole(User::SUPER_ADMIN) ? true : null);

        Model::preventLazyLoading(! $this->app->isProduction());
        Model::handleLazyLoadingViolationUsing(
            fn (Model $model, string $relation) => logger()->warning('Lazy loading '.$model::class."::{$relation}")
        );

        Event::listen(Login::class, function (Login $event) {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
                $this->logLogin($event->user->getKey(), $event->user->email, true);
            }
        });

        Event::listen(Failed::class, fn (Failed $event) => $this->logLogin(
            $event->user?->getAuthIdentifier(),
            $event->credentials['email'] ?? null,
            false,
        ));
    }

    /**
     * Any content model change (incl. translations) invalidates the cached site index and menus; URL-moving
     * changes leave 301 redirects behind (see UrlRedirects).
     */
    private function flushContentCacheOnChanges(): void
    {
        $ignored = [AuditLog::class, LoginLog::class, ContactMessage::class, JobApplication::class, User::class, Redirect::class];

        Event::listen('eloquent.updating: App\\Models\\*', function (string $name, array $payload) {
            if (UrlRedirects::affectsUrls($payload[0])) {
                UrlRedirects::capture();
            }
        });

        foreach (['saved', 'deleted', 'restored'] as $event) {
            Event::listen("eloquent.{$event}: App\\Models\\*", function (string $name, array $payload) use ($ignored) {
                if (! in_array($payload[0]::class, $ignored, true)) {
                    ContentCache::flush();
                    UrlRedirects::commit();
                }
            });
        }
    }

    /**
     * Admin-selected timezone for dates shown in the dashboard (stored UTC timestamps are unaffected).
     */
    private function applyTimezone(): void
    {
        try {
            $timezone = Setting::get('general.timezone');
        } catch (\Throwable) {
            return; // settings table not migrated yet
        }

        if (is_string($timezone) && in_array($timezone, timezone_identifiers_list(), true)) {
            FilamentTimezone::set($timezone);
        }
    }

    private function logLogin(?int $userId, ?string $email, bool $successful): void
    {
        LoginLog::create([
            'user_id' => $userId,
            'email' => $email,
            'successful' => $successful,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 500),
        ]);
    }
}
