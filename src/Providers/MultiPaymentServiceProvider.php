<?php

namespace Omalizadeh\MultiPayment\Providers;

use Illuminate\Support\ServiceProvider;
use Omalizadeh\MultiPayment\PaymentGateway;

class MultiPaymentServiceProvider extends ServiceProvider
{
    public function register()
    {
        parent::register();

        $this->app->bind('PaymentGateway', function () {
            return new PaymentGateway();
        });

        $this->mergeConfigFrom(
            __DIR__.'/../../config/multipayment.php',
            'multipayment.php',
        );
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'multipayment');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../resources/views' => resource_path('views/vendor/multipayment'),
                __DIR__.'/../../config' => config_path(),
            ]);

            $this->publishes([
                __DIR__.'/../../resources/views' => resource_path('views/vendor/multipayment'),
            ], 'multipayment-view');

            $this->publishes([
                __DIR__.'/../../config/multipayment.php' => config_path('multipayment.php'),
            ], 'multipayment-config');

            $this->publishes([
                __DIR__.'/../../config/gateway_zarinpal.php' => config_path('gateway_zarinpal.php'),
            ], 'zarinpal-config');

            $this->publishes([
                __DIR__.'/../../config/gateway_zibal.php' => config_path('gateway_zibal.php'),
            ], 'zibal-config');

            $this->publishes([
                __DIR__.'/../../config/gateway_mellat.php' => config_path('gateway_mellat.php'),
            ], 'mellat-config');

            $this->publishes([
                __DIR__.'/../../config/gateway_saman.php' => config_path('gateway_saman.php'),
            ], 'saman-config');

            $this->publishes([
                __DIR__.'/../../config/gateway_novin.php' => config_path('gateway_novin.php'),
            ], 'novin-config');

            $this->publishes([
                __DIR__.'/../../config/gateway_pasargad.php' => config_path('gateway_pasargad.php'),
            ], 'pasargad-config');

            $this->publishes([
                __DIR__.'/../../config/gateway_parsian.php' => config_path('gateway_parsian.php'),
            ], 'parsian-config');

            $this->publishes([
                __DIR__.'/../../config/gateway_idpay.php' => config_path('gateway_idpay.php'),
            ], 'idpay-config');

            $this->publishes([
                __DIR__.'/../../config/gateway_payir.php' => config_path('gateway_payir.php'),
            ], 'payir-config');

            $this->publishes([
                __DIR__.'/../../config/gateway_paystar.php' => config_path('gateway_paystar.php'),
            ], 'paystar-config');
        }
    }
}
