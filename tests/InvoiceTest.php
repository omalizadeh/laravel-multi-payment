<?php

namespace Omalizadeh\MultiPayment\Tests;

use Omalizadeh\MultiPayment\Invoice;

class InvoiceTest extends TestCase
{
    /** @test */
    public function tomansToRialsAutoConversionTest()
    {
        $invoice = new Invoice(12000);
        $this->assertEquals(120000, $invoice->getAmount());
        $this->assertEquals(12000, $invoice->getAmountInTomans());
    }

    /** @test */
    public function rialsToTomansAutoConversionTest()
    {
        config(['multipayment.convert_to_rials' => false]);
        $invoice = new Invoice(12000);
        $this->assertEquals(12000, $invoice->getAmount());
        $this->assertEquals(1200, $invoice->getAmountInTomans());
    }

    /** @test */
    public function uuidIsAutoGeneratedTest()
    {
        $invoice = new Invoice(111);
        $this->assertNotEmpty($invoice->getUuid());
        $this->assertIsString($invoice->getUuid());
    }

    /** @test */
    public function uuidIsRandomTest()
    {
        $firstInvoice = new Invoice(111);
        $secondInvoice = new Invoice(222);
        $this->assertNotEquals($firstInvoice->getUuid(), $secondInvoice->getUuid());
    }
}
