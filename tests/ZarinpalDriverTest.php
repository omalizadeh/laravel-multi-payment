<?php

namespace Omalizadeh\MultiPayment\Tests;

use Illuminate\Support\Facades\Http;
use Omalizadeh\MultiPayment\Facades\PaymentGateway;
use Omalizadeh\MultiPayment\Invoice;

class ZarinpalDriverTest extends TestCase
{
    public function test_invoice_can_be_purchased(): void
    {
        $invoice = new Invoice(1200);

        Http::fake([
            'https://sandbox.zarinpal.com/pg/v4/payment/request.json' => Http::response([
                'data' => [
                    'code' => 100,
                    'authority' => 'testing-authority',
                ],
            ]),
        ]);

        $redirect = PaymentGateway::purchase($invoice)->toArray();

        $this->assertEquals('GET', $redirect['method']);
        $this->assertStringContainsString('testing-authority', $redirect['action']);
    }

    public function test_paid_invoice_can_be_verified(): void
    {
        Http::fake([
            'https://sandbox.zarinpal.com/pg/v4/payment/verify.json' => Http::response([
                'data' => [
                    'code' => 100,
                    'card_pan' => '66-****-99',
                    'ref_id' => '111111',
                ],
            ]),
        ]);

        $invoice = new Invoice(1200, 'testing-transaction');

        $receipt = PaymentGateway::verify($invoice);

        $this->assertEquals('111111', $receipt->getReferenceId());
        $this->assertEquals('66-****-99', $receipt->getCardNumber());
    }

    public function test_payment_can_be_refunded(): void
    {
        Http::fake([
            'https://sandbox.zarinpal.com/pg/v4/payment/refund.json' => Http::response([
                'data' => [
                    'code' => 100,
                    'iban' => 'IR-XXX-XXXX',
                    'ref_id' => 456666,
                    'session' => 4654552213,
                ],
            ]),
        ]);

        $invoice = new Invoice(1200, 'testing-transaction');

        $response = PaymentGateway::refund($invoice);

        $this->assertEquals('IR-XXX-XXXX', $response['iban']);
    }

    public function test_unverified_payments_can_be_fetched(): void
    {
        Http::fake([
            'https://sandbox.zarinpal.com/pg/v4/payment/unVerified.json' => Http::response([
                'data' => [
                    'code' => 100,
                    'authorities' => [
                        [
                            'uthority' => 'A00000000000000000000000000207288780',
                            'amount' => 50500,
                            'callback_url' => 'https://test.com/pay',
                            'referer' => 'https://test.com/form',
                            'date' => '2020-07-01 17:33:25',
                        ],
                    ],
                ],
            ]),
        ]);

        $unverifiedPayments = PaymentGateway::unverifiedPayments();

        $this->assertIsArray($unverifiedPayments);
        $this->assertNotEmpty($unverifiedPayments);
    }
}
