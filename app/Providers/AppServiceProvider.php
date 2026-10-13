<?php

namespace App\Providers;

use App\Integrations\Contracts\MasterMenuSource;
use App\Integrations\Contracts\MenuSource;
use App\Integrations\Contracts\ProductSource;
use App\Integrations\Contracts\PurchaseInvoiceSource;
use App\Integrations\Contracts\PurchaseRequestSource;
use App\Integrations\Contracts\SalesDataSink;
use App\Integrations\ESB\Clients\MasterMenuClient;
use App\Integrations\ESB\Clients\MenuClient;
use App\Integrations\ESB\Clients\ProductClient;
use App\Integrations\ESB\Clients\PurchaseInvoiceClient;
use App\Integrations\ESB\Clients\PurchaseRequestClient;
use App\Integrations\ESB\Clients\SalesDataClient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Which provider answers a canonical request is a binding, not a
        // type-hint on a concrete class (ADR-016). The ESB is the only provider
        // today; a second one changes this line rather than the callers.
        $this->app->bind(ProductSource::class, ProductClient::class);
        $this->app->bind(MenuSource::class, MenuClient::class);
        $this->app->bind(MasterMenuSource::class, MasterMenuClient::class);
        $this->app->bind(PurchaseRequestSource::class, PurchaseRequestClient::class);
        $this->app->bind(PurchaseInvoiceSource::class, PurchaseInvoiceClient::class);
        $this->app->bind(SalesDataSink::class, SalesDataClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // Generated URLs must not downgrade to http behind a load balancer
        // that terminates TLS (docs/10-security.md).
        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
