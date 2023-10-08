[![Latest Stable Version](https://poser.pugx.org/omalizadeh/laravel-multi-payment/v)](//packagist.org/packages/omalizadeh/laravel-multi-payment)
[![Tests](https://github.com/omalizadeh/laravel-multi-payment/actions/workflows/tests.yml/badge.svg)](https://github.com/omalizadeh/laravel-multi-payment/actions/workflows/tests.yml)
[![Total Downloads](https://poser.pugx.org/omalizadeh/laravel-multi-payment/downloads)](//packagist.org/packages/omalizadeh/laravel-multi-payment)
[![License](https://poser.pugx.org/omalizadeh/laravel-multi-payment/license)](//packagist.org/packages/omalizadeh/laravel-multi-payment)

# Laravel Online Payment Gateway Package

This is a laravel gateway payment package with multi driver support. Each driver can have multiple configurations.
Supports laravel **v8.0+** and requires php **v8.1+**

> Star! if you liked this package.

<div dir="rtl">

> **[مستندات فارسی][readme-link-fa]**
</div>

## Supported Gateways

- [Mellat Bank (Behpardakht)](https://behpardakht.com)
- [Saman Bank (Sep)](https://sep.ir)
- [Parsian Bank (Top)](https://pec.ir)
- [Pasargad Bank (Pep)](https://pep.co.ir)
- [Eghtesad Novin Bank (Pardakht Novin)](https://pna.co.ir)
- [Zarinpal](https://zarinpal.com)
- [IDPay](https://idpay.ir)
- [Pay.ir](https://pay.ir)
- [Zibal](https://zibal.ir)
- [PayStar](https://paystar.ir)


## Installation & Configuration

Install using composer

```bash 
  composer require omalizadeh/laravel-multi-payment
```

Publish main config file

```bash
  php artisan vendor:publish --tag=multipayment-config
```

Publish gateway config file based on tags. like:
- zarinpal-config
- mellat-config
- saman-config
- pasargad-config
- novin-config
  
For example:

```bash
  php artisan vendor:publish --tag=zarinpal-config
```

Also you can publish view file for gateway redirection and customize it
```bash
  php artisan vendor:publish --tag=multipayment-view
```

In main config file `multipayment.php`, you can specify default driver. For example, `zarinpal.second` value states that `zarinpal` gateway with configuration under `second` key section on zarinpal config file will be used. There is also an option for auto amount conversion from Iranian Tomans to Iranian Rials currency (IRR) and vice versa.

```php
     /**
     * set default gateway
     * 
     * valid pattern --> GATEWAY_NAME.GATEWAY_CONFIG_KEY 
     */
    'default_gateway' => env('DEFAULT_PAYMENT_GATEWAY', 'zarinpal.second'),

    /**
     *  set to false if your in-app currency is IRR
     */
    'convert_to_rials' => true
```

In each gateway config file, you can specify multiple credentials, therefore you may have multiple gateways for your app from same provider.

```php
     /**
     *  gateway configurations
     */
    'first' => [
        'merchant_id'  => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'mode'        => 'normal', // Supported values: normal, sandbox, zaringate
        'description' => 'payment using zarinpal',
    ],
    'second' => [
        'merchant_id'  => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'mode'        => 'sandbox',
        'description' => 'payment using zarinpal',
    ]
```

## Usage

Gateway payment has two major phases. first is purchase (start process by calling gateway api for a
transaction_id/token) and opening gateway payment web page with received data. second is verification (checking
payment was successful).

### Purchase

`Inovice` objects hold payment data. first you create an invoice, set amount and other information, then you pass invoice to `PaymentGateway` Facade to start payment process. you can use `setProvider` method on facade to change gateway before payment.

```php
    // On top...
    use Omalizadeh\MultiPayment\Facades\PaymentGateway;

    ////

    $invoice = new Invoice(10000);
    $invoice->setPhoneNumber("989123456789");
    
    return PaymentGateway::purchase($invoice, function (string $transactionId) {
        // Save transaction_id and do stuff...
    })->view();
```

### Verification

After payment gateway redirection to your app, you must create an invoice and set it's transaction_id and amount. then use `PaymentGateway` to verify invoice successful payment.

```php
    try {
        // Get amount & transaction_id from database or gateway request
        $invoice = new Invoice($amount, $transactionId);
        $receipt = PaymentGateway::verify($invoice);
        // Save receipt data and return response
        //
    } catch (PaymentAlreadyVerifiedException $exception) {
        // Optional: Handle repeated verification request
    } catch (PaymentFailedException $exception) {
        // Handle exception for failed payments
        return $exception->getMessage();
    }
```

### Other Features

#### Unverified Payments

There is also a method (supported by zarinpal only for now) to get a list of successful unverified payments. use `unverifiedPayments` method in `PaymentGateway` facade for this feature.

#### Refund

Using `refund` method, you can refund a successful payment back to customer.

[readme-link-fa]: README-FA.md

[readme-link-en]: README.md
