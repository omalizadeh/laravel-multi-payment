<?php

namespace Omalizadeh\MultiPayment\Tests;

use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;
use Omalizadeh\MultiPayment\Facades\PaymentGateway;

class PaymentGatewayConfigTest extends TestCase
{
    public function test_default_gateway_is_set_correctly(): void
    {
        $this->assertEquals('zarinpal', PaymentGateway::getProviderName());
        $this->assertEquals('main', PaymentGateway::getProviderInstanceConfigKey());
    }

    public function test_gateway_can_be_changed(): void
    {
        PaymentGateway::setProvider('zarinpal', 'other');

        $this->assertEquals('zarinpal', PaymentGateway::getProviderName());
        $this->assertEquals('other', PaymentGateway::getProviderInstanceConfigKey());
    }

    public function test_invalid_gateway_config_throws_exception(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        PaymentGateway::setProvider('zarinpal', 'invalid_key');
    }
}
