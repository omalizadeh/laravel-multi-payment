<?php

namespace Omalizadeh\MultiPayment\Tests;

use Omalizadeh\MultiPayment\Providers\MultiPaymentServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            MultiPaymentServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $zarinpalSettings = require __DIR__.'../../config/gateway_zarinpal.php';

        $app['config']->set('gateway_zarinpal', $zarinpalSettings);
        $app['config']->set('gateway_zarinpal.main.merchant_id', 'xx-testing-merchant-id-xx');
        $app['config']->set('gateway_zarinpal.main.authorization_token', 'access-token');
        $app['config']->set('multipayment.default_gateway', 'zarinpal.main');
        $app['config']->set('multipayment.convert_to_rials', true);
    }
}
