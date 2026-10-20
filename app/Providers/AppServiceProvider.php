<?php

namespace App\Providers;

use App\Integrations\Contracts\BillOfMaterialSource;
use App\Integrations\Contracts\CoaAccountSource;
use App\Integrations\Contracts\DocumentTemplateSource;
use App\Integrations\Contracts\InventoryDocumentSource;
use App\Integrations\Contracts\LookupSource;
use App\Integrations\Contracts\MasterDataImportSource;
use App\Integrations\Contracts\MasterMenuSource;
use App\Integrations\Contracts\MenuSource;
use App\Integrations\Contracts\PricelistSource;
use App\Integrations\Contracts\ProductCategorySource;
use App\Integrations\Contracts\ProductSource;
use App\Integrations\Contracts\ProductSubCategorySource;
use App\Integrations\Contracts\PurchaseInvoiceSource;
use App\Integrations\Contracts\PurchaseOrderSource;
use App\Integrations\Contracts\PurchaseRequestSource;
use App\Integrations\Contracts\PurposeSource;
use App\Integrations\Contracts\SalesDataSink;
use App\Integrations\Contracts\SimplePurchaseSource;
use App\Integrations\Contracts\SupplierCategorySource;
use App\Integrations\Contracts\SupplierSource;
use App\Integrations\Contracts\UnitOfMeasureSource;
use App\Integrations\ESB\Clients\BillOfMaterialClient;
use App\Integrations\ESB\Clients\CoaAccountClient;
use App\Integrations\ESB\Clients\DocumentTemplateClient;
use App\Integrations\ESB\Clients\InventoryDocumentClient;
use App\Integrations\ESB\Clients\LookupClient;
use App\Integrations\ESB\Clients\MasterDataImportClient;
use App\Integrations\ESB\Clients\MasterMenuClient;
use App\Integrations\ESB\Clients\MenuClient;
use App\Integrations\ESB\Clients\PricelistClient;
use App\Integrations\ESB\Clients\ProductCategoryClient;
use App\Integrations\ESB\Clients\ProductClient;
use App\Integrations\ESB\Clients\ProductSubCategoryClient;
use App\Integrations\ESB\Clients\PurchaseInvoiceClient;
use App\Integrations\ESB\Clients\PurchaseOrderClient;
use App\Integrations\ESB\Clients\PurchaseRequestClient;
use App\Integrations\ESB\Clients\PurposeClient;
use App\Integrations\ESB\Clients\SalesDataClient;
use App\Integrations\ESB\Clients\SimplePurchaseClient;
use App\Integrations\ESB\Clients\SupplierCategoryClient;
use App\Integrations\ESB\Clients\SupplierClient;
use App\Integrations\ESB\Clients\UnitOfMeasureClient;
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
        $this->app->bind(MasterDataImportSource::class, MasterDataImportClient::class);
        $this->app->bind(InventoryDocumentSource::class, InventoryDocumentClient::class);
        $this->app->bind(MenuSource::class, MenuClient::class);
        $this->app->bind(MasterMenuSource::class, MasterMenuClient::class);
        $this->app->bind(BillOfMaterialSource::class, BillOfMaterialClient::class);
        $this->app->bind(PurposeSource::class, PurposeClient::class);
        $this->app->bind(LookupSource::class, LookupClient::class);
        $this->app->bind(UnitOfMeasureSource::class, UnitOfMeasureClient::class);
        $this->app->bind(ProductCategorySource::class, ProductCategoryClient::class);
        $this->app->bind(ProductSubCategorySource::class, ProductSubCategoryClient::class);
        $this->app->bind(SupplierCategorySource::class, SupplierCategoryClient::class);
        $this->app->bind(SupplierSource::class, SupplierClient::class);
        $this->app->bind(CoaAccountSource::class, CoaAccountClient::class);
        $this->app->bind(PricelistSource::class, PricelistClient::class);
        $this->app->bind(DocumentTemplateSource::class, DocumentTemplateClient::class);
        $this->app->bind(PurchaseRequestSource::class, PurchaseRequestClient::class);
        $this->app->bind(PurchaseInvoiceSource::class, PurchaseInvoiceClient::class);
        $this->app->bind(PurchaseOrderSource::class, PurchaseOrderClient::class);
        $this->app->bind(SimplePurchaseSource::class, SimplePurchaseClient::class);
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
