<?php

namespace Omalizadeh\MultiPayment\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use Omalizadeh\MultiPayment\Invoice;
use Omalizadeh\MultiPayment\Receipt;
use Omalizadeh\MultiPayment\RedirectionForm;

/**
 * @method static RedirectionForm purchase(Invoice $invoice, ?Closure $closure = null)
 * @method static Receipt verify(Invoice $invoice)
 * @method static array refund(Invoice $invoice)
 * @method static array unverifiedPayments()
 * @method static \Omalizadeh\MultiPayment\PaymentGateway setProvider(string $providerName, string $providerInstanceConfigKey)
 * @method static string getProviderName()
 * @method static string getProviderInstanceConfigKey()
 *
 * @see \Omalizadeh\MultiPayment\PaymentGateway
 */
class PaymentGateway extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Omalizadeh\MultiPayment\PaymentGateway::class;
    }
}
