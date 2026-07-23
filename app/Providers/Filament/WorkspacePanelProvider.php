<?php

namespace App\Providers\Filament;

use App\Filament\Workspace\Resources\KycVerifications\KycVerificationResource;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\EnsureTenantNotSuspended;
use App\Support\Kyc;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class WorkspacePanelProvider extends PanelProvider
{
    public function register(): void
    {
        // Only boot the workspace panel when KYC is opted-in and the package is installed.
        if (! Kyc::ready()) {
            return;
        }

        parent::register();
    }

    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->id('workspace')
            ->path('workspace')
            ->brandName(fn (): string => (string) (tenant('name') ?? config('app.name')))
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->login()
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                PreventAccessFromCentralDomains::class,
                InitializeTenancyByDomainOrSubdomain::class,
                EnsureTenantNotSuspended::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetLocale::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        /*
         | kyc-ai/laravel ships KycFilamentPlugin for Filament ^3.
         | Tenant Kit uses Filament 5, so we register a compatible resource bridge.
         | Prefer the package plugin when Forms\Form still exists (Filament 3 apps).
         */
        if (class_exists(\KycAi\Laravel\Filament\KycFilamentPlugin::class)
            && class_exists(\Filament\Forms\Form::class)) {
            $panel->plugin(\KycAi\Laravel\Filament\KycFilamentPlugin::make());
        } else {
            $panel->resources([
                KycVerificationResource::class,
            ]);
        }

        return $panel;
    }
}
