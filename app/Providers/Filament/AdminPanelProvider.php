<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\QuickActions;
use App\Filament\Widgets\RecentActivity;
use App\Filament\Widgets\SiteOverview;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetAdminLocale;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Field;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Infolists\Components\Entry;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /**
     * Labels in the admin code are English keys; translate them (and auto-generated ones) into the dashboard locale.
     */
    public function boot(): void
    {
        foreach ([Field::class, Entry::class, Tab::class, Fieldset::class, Column::class, BaseFilter::class, Action::class, ActionGroup::class] as $component) {
            $component::configureUsing(fn ($instance) => $instance->translateLabel());
        }
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->profile()
            ->brandName('AgroEgypt')
            ->brandLogo(fn () => ($logo = Setting::get('theme.logo')) ? asset('storage/'.$logo) : null)
            ->brandLogoHeight('2.5rem')
            ->favicon(fn () => ($icon = Setting::get('theme.favicon')) ? asset('storage/'.$icon) : null)
            ->colors([
                'primary' => Color::hex('#789915'),
                'gray' => Color::Stone,
            ])
            ->navigationGroups(collect(['Content', 'Catalog', 'Inbox', 'Localization', 'Settings', 'Users & security'])
                ->mapWithKeys(fn (string $group) => [$group => NavigationGroup::make(fn () => __($group))])->all())
            ->userMenuItems(collect(SetAdminLocale::LOCALES)->map(fn (string $name, string $code) => Action::make('locale_'.$code)
                ->label($name)
                ->icon('heroicon-o-language')
                ->url(fn () => route('filament.admin.locale', $code))
                ->hidden(fn () => app()->getLocale() === $code))->values()->all())
            ->routes(function (): void {
                Route::get('locale/{locale}', function (Request $request, string $locale) {
                    $request->session()->put('admin_locale', $locale);

                    return back();
                })->whereIn('locale', array_keys(SetAdminLocale::LOCALES))->name('locale');
            })
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([SiteOverview::class, QuickActions::class, RecentActivity::class])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->middleware([SetAdminLocale::class, SecurityHeaders::class], isPersistent: true)
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
