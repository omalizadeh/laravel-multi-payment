<?php

namespace Omalizadeh\MultiPayment\Drivers\Contracts;

use Omalizadeh\MultiPayment\Invoice;
use Omalizadeh\MultiPayment\Receipt;
use Omalizadeh\MultiPayment\RedirectionForm;

abstract class Driver implements PurchaseInterface
{
    protected Invoice $invoice;
    protected array $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function setInvoice(Invoice $invoice): self
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }

    abstract public function purchase(): string;

    abstract public function pay(): RedirectionForm;

    abstract public function verify(): Receipt;

    abstract protected function getPurchaseData(): array;

    abstract protected function getVerificationData(): array;

    abstract protected function getStatusMessage(int|string $statusCode): string;

    abstract protected function getSuccessResponseStatusCode();

    abstract protected function getPurchaseUrl(): string;

    abstract protected function getPaymentUrl(): string;

    abstract protected function getVerificationUrl(): string;

    protected function redirect(string $action, array $inputs = [], string $method = 'POST'): RedirectionForm
    {
        return new RedirectionForm($action, $inputs, $method);
    }
}
