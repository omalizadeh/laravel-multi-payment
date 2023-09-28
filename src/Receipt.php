<?php

namespace Omalizadeh\MultiPayment;

use Exception;

class Receipt
{
    protected Invoice $invoice;
    protected string $traceNumber;
    protected ?string $cardNumber;
    protected ?string $referenceId;

    /**
     * Receipt constructor.
     * @param  Invoice  $invoice
     * @param  string  $traceNumber
     * @param  string|null  $referenceId
     * @param  string|null  $cardNumber
     */
    public function __construct(
        Invoice $invoice,
        string $traceNumber,
        ?string $referenceId = null,
        ?string $cardNumber = null,
    ) {
        $this->invoice = $invoice;
        $this->traceNumber = $traceNumber;
        $this->referenceId = $referenceId;
        $this->cardNumber = $cardNumber;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getInvoiceId(): string
    {
        return $this->invoice->getInvoiceId();
    }

    /**
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->invoice->getTransactionId();
    }

    /**
     * @return string
     */
    public function getTraceNumber(): string
    {
        return $this->traceNumber;
    }

    /**
     * @return string|null
     */
    public function getReferenceId(): ?string
    {
        return $this->referenceId;
    }

    /**
     * @return string|null
     */
    public function getCardNumber(): ?string
    {
        return $this->cardNumber;
    }
}
