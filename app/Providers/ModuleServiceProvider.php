<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Admin\Providers\AdminModuleServiceProvider;
use App\Modules\Auth\Providers\AuthModuleServiceProvider;
use App\Modules\Customers\Providers\CustomersModuleServiceProvider;
use App\Modules\Identity\Providers\IdentityModuleServiceProvider;
use App\Modules\Invoices\Providers\InvoicesModuleServiceProvider;
use App\Modules\Merchants\Providers\MerchantsModuleServiceProvider;
use App\Modules\Notifications\Providers\NotificationsModuleServiceProvider;
use App\Modules\Orders\Providers\OrdersModuleServiceProvider;
use App\Modules\Payments\Providers\PaymentsModuleServiceProvider;
use App\Modules\Products\Providers\ProductsModuleServiceProvider;
use App\Modules\Settings\Providers\SettingsModuleServiceProvider;
use App\Modules\Stores\Providers\StoresModuleServiceProvider;
use App\Modules\Subscriptions\Providers\SubscriptionsModuleServiceProvider;
use App\Modules\WhatsApp\Providers\WhatsAppModuleServiceProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Registers Dawar domain modules (modular monolith).
 */
final class ModuleServiceProvider extends ServiceProvider
{
    /**
     * @var list<class-string<ServiceProvider>>
     */
    private const MODULE_PROVIDERS = [
        AuthModuleServiceProvider::class,
        IdentityModuleServiceProvider::class,
        AdminModuleServiceProvider::class,
        MerchantsModuleServiceProvider::class,
        StoresModuleServiceProvider::class,
        ProductsModuleServiceProvider::class,
        OrdersModuleServiceProvider::class,
        PaymentsModuleServiceProvider::class,
        InvoicesModuleServiceProvider::class,
        CustomersModuleServiceProvider::class,
        SubscriptionsModuleServiceProvider::class,
        NotificationsModuleServiceProvider::class,
        WhatsAppModuleServiceProvider::class,
        SettingsModuleServiceProvider::class,
    ];

    public function register(): void
    {
        foreach (self::MODULE_PROVIDERS as $provider) {
            $this->app->register($provider);
        }
    }
}
