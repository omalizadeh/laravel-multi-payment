<?php

namespace Omalizadeh\MultiPayment\Drivers\Shepa;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Omalizadeh\MultiPayment\Drivers\Contracts\Driver;
use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;
use Omalizadeh\MultiPayment\Exceptions\PaymentFailedException;
use Omalizadeh\MultiPayment\Exceptions\PurchaseFailedException;
use Omalizadeh\MultiPayment\Receipt;
use Omalizadeh\MultiPayment\RedirectionForm;

class Shepa extends Driver
{
    public function purchase(): string
    {
        $purchaseData = $this->getPurchaseData();
        $response = Http::withHeaders($this->getRequestHeaders())
            ->post($this->getPurchaseUrl(), $purchaseData);

        if ($response->successful()) {
            $response = json_decode($response, 1);
            if (Arr::get($response, 'success') !== $this->getSuccessResponseStatusCode()) {
                throw new PurchaseFailedException(Arr::get($response, 'error.0'), Arr::get($response, 'errorCode'),
                    $purchaseData);
            }

            $this->getInvoice()->setTransactionId(Arr::get($response, 'result.token'));
            return $this->getInvoice()->getTransactionId();
        }

        throw new PurchaseFailedException($response->body(), $response->status(), $purchaseData);
    }

    protected function getPurchaseData(): array
    {

        $validator = Validator::make($this->settings, [
            'api_key' => 'required',
            'callback' => 'required',
        ]);

        if ($validator->fails()) {
            throw new InvalidConfigurationException($validator->errors()->toJson());
        }

        $cellNumber = $this->getInvoice()->getPhoneNumber();

        if (!empty($cellNumber)) {
            $cellNumber = $this->checkPhoneNumberFormat($cellNumber);
        }

        return [
            'api' => $this->settings['api_key'],
            'callback' => $this->settings['callback'],
            'amount' => $this->getInvoice()->getAmount(),
            'CellNumber' => $cellNumber,
            'order' => [
                'total' => $this->getInvoice()->getAmount(),
                'billing' => $this->getInvoice()->getBilling(),
                'products' => $this->getInvoice()->getProducts()
            ],
        ];
    }

    private function checkPhoneNumberFormat(string $phoneNumber): string
    {
        if (strlen($phoneNumber) === 12 and Str::startsWith($phoneNumber, '98')) {
            return $phoneNumber;
        }
        if (strlen($phoneNumber) === 11 and Str::startsWith($phoneNumber, '0')) {
            return Str::replaceFirst('0', '98', $phoneNumber);
        }
        if (strlen($phoneNumber) === 10) {
            return '98'.$phoneNumber;
        }

        return $phoneNumber;
    }

    private function getRequestHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    protected function getPurchaseUrl(): string
    {
        return Arr::get($this->settings, 'base_url').'api/v1/token';
    }

    protected function getSuccessResponseStatusCode()
    {
        return true;
    }

    public function pay(): RedirectionForm
    {
        return $this->redirect($this->getPaymentUrl(), [], 'GET');
    }

    protected function getPaymentUrl(): string
    {
        return Arr::get($this->settings, 'base_url').'v1/'.$this->getInvoice()->getTransactionId();
    }

    public function verify(): Receipt
    {
        $response = Http::withHeaders($this->getRequestHeaders())
            ->post($this->getVerificationUrl(), $this->getVerificationData());

        $response = json_decode($response, 1);

        if (Arr::get($response, 'success') !== $this->getSuccessResponseStatusCode()) {
            throw new PaymentFailedException(Arr::get($response, 'error.0'),
                (int) Arr::get($response, 'errorCode'));
        }

        $this->getInvoice()->setTransactionId(Arr::get($response, 'result.transaction_id'));

        return new Receipt(
            $this->getInvoice(),
            Arr::get($response, 'result.refid'),
            Arr::get($response, 'result.transaction_id'),
            Arr::get($response, 'result.card_pan')
        );
    }

    protected function getVerificationUrl(): string
    {
        return Arr::get($this->settings, 'base_url').'api/v1/verify';
    }

    protected function getVerificationData(): array
    {
        return [
            'token' => request('token'),
            'amount' => $this->getInvoice()->getAmount(),
            'api' => $this->settings['api_key'],
        ];
    }

    public function refund()
    {
        $response = Http::withHeaders($this->getRequestHeaders())
            ->post($this->getRefundUrl(), $this->getRefundData());

        $response = json_decode($response, 1);

        if (Arr::get($response, 'success') !== $this->getSuccessResponseStatusCode()) {
            throw new PaymentFailedException(Arr::get($response, 'error.0'),
                (int) Arr::get($response, 'errorCode'));
        }

    }

    protected function getRefundUrl(): string
    {
        return Arr::get($this->settings, 'base_url').'api/v1/refund-transaction';
    }

    protected function getRefundData(): array
    {
        return [
            'transaction' => $this->getInvoice()->getTransactionId(),
            'amount' => $this->getInvoice()->getAmount(),
        ];
    }

    protected function getSuccessfulPaymentStatusCode(): string
    {
        return 'success';
    }

    protected function getStatusMessage($statusCode): string
    {
        // TODO: Implement getStatusMessage() method.
    }

    private function getCallbackMethod()
    {
        if (isset($this->settings['callback_method']) && strtoupper($this->settings['callback_method']) === 'GET') {
            return 'true';
        }

        return null;
    }
}
