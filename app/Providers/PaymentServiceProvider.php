<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Services\Payments\PaymentGatewayFactory;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Factory resolves any gateway by name (per-transaction selection).
        $this->app->singleton(PaymentGatewayFactory::class);

        // Default binding = the platform's configured gateway. Existing code that
        // type-hints PaymentGatewayInterface keeps working unchanged.
        $this->app->singleton(
            PaymentGatewayInterface::class,
            fn ($app) => $app->make(PaymentGatewayFactory::class)->make()
        );

        // Alias for easier access
        $this->app->alias(PaymentGatewayInterface::class, 'payment.gateway');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load currency helpers
        require_once app_path('Helpers/Currency.php');
    }
}
