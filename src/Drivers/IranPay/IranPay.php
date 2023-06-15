<?php

namespace Omalizadeh\MultiPayment\Drivers\IranPay;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Omalizadeh\MultiPayment\Drivers\Contracts\Driver;
use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;
use Omalizadeh\MultiPayment\Exceptions\PurchaseFailedException;
use Omalizadeh\MultiPayment\Receipt;
use Omalizadeh\MultiPayment\RedirectionForm;

class IranPay extends Driver
{
    private ?string $paymentUrl = null;

    public function purchase(): string
    {
    }


    public function purchaseView(): string
    {
        $response =  $this->callApi($this->getPurchaseUrl(),$this->getPurchaseData());

        if (isset($response['status']) && $response['status'] !== $this->getSuccessResponseStatusCode()) {
            $message = $response['message'] ?? $this->getStatusMessage($response['status']);
            throw new PurchaseFailedException($message, $response['status']);
        }


        return  $response->body();
    }

    /**
     * @throws InvalidConfigurationException
     * @throws \Exception
     */
    protected function getPurchaseData(): array
    {
        if (empty($this->settings['merchant_id'])) {
            throw new InvalidConfigurationException('merchant_id has not been set.');
        }

        return [
            'amount' => 10,
            'merchant_id' => $this->settings['merchant_id'],
            'gateway_name'=>$this->settings['gateway_name']
        ];
    }


    private function callApi(string $url, array $data)
    {
        return Http::withHeaders($this->getRequestHeaders())->post($url, $data);
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
        return $this->getBaseUrl().'gateway/connect_gateway';
    }

    private function getBaseUrl(): string
    {
        return 'https://epayment724.com/api/';
    }

    protected function getStatusMessage($statusCode) : string
    {
        $messages = [
            O403 => 'Sorry we couldn t find you or you havent been verified yet',
            O502 => 'The payment was not made or was canceled by the user',
            O200 => 'Payment was successful',
            O404 => 'merchant id not found',
            O503 => 'You gateway not found',
            404 => 'please enter valid email',
        ];

        $unknownError = 'خطای ناشناخته رخ داده است.';

        return array_key_exists($statusCode, $messages) ? $messages[$statusCode] : $unknownError;
    }

    public function pay(): RedirectionForm
    {
    }

    protected function getPaymentUrl(): string
    {
    }

    public function verify(): Receipt
    {

    }

    protected function getSuccessResponseStatusCode(): int
    {
        return 0200;
    }

    protected function getVerificationUrl(): string
    {
    }

    protected function getVerificationData(): array
    {

    }
}
