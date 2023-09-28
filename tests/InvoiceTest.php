<?php

namespace Omalizadeh\MultiPayment\Tests;

use Omalizadeh\MultiPayment\Invoice;

class InvoiceTest extends TestCase
{
    public function test_tomans_to_rials_auto_conversion(): void
    {
        $invoice = new Invoice(12000);

        $this->assertEquals(120000, $invoice->getAmount());
        $this->assertEquals(12000, $invoice->getAmountInTomans());
    }

    public function test_rials_to_tomans_auto_conversion(): void
    {
        config(['multipayment.convert_to_rials' => false]);

        $invoice = new Invoice(12000);

        $this->assertEquals(12000, $invoice->getAmount());
        $this->assertEquals(1200, $invoice->getAmountInTomans());
    }

    public function test_uuid_is_generated_automatically(): void
    {
        $invoice = new Invoice(111);

        $this->assertNotEmpty($invoice->getUuid());
        $this->assertIsString($invoice->getUuid());
    }

    public function test_uuid_is_random(): void
    {
        $firstInvoice = new Invoice(111);
        $secondInvoice = new Invoice(222);

        $this->assertNotEquals($firstInvoice->getUuid(), $secondInvoice->getUuid());
    }

    public function test_string_amount_is_accepted_and_converted(): void
    {
        config(['multipayment.convert_to_rials' => false]);

        $invoice = new Invoice('110.99');

        $this->assertEquals(110.99, $invoice->getAmount());
    }
}
