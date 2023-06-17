<?php

namespace Omalizadeh\MultiPayment\Drivers\IranPay;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Omalizadeh\MultiPayment\Drivers\Contracts\Driver;
use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;
use Omalizadeh\MultiPayment\Exceptions\PaymentAlreadyVerifiedException;
use Omalizadeh\MultiPayment\Exceptions\PaymentFailedException;
use Omalizadeh\MultiPayment\Exceptions\PurchaseFailedException;
use Omalizadeh\MultiPayment\Receipt;
use Omalizadeh\MultiPayment\RedirectionForm;

class IranPay extends Driver
{
    private ?string $paymentUrl = null;

    public function purchase(): string
    {
        $response =  $this->callApi($this->getPurchaseUrl(),$this->getPurchaseData());

        if (isset($response['status']) && $response['status'] !== $this->getSuccessResponseStatusCode()) {
            $message = $response['message'] ?? $this->getStatusMessage($response['status']);
            throw new PurchaseFailedException($message, $response['status']);
        }


        return  $response->body();
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

    private function checkPhoneNumberFormat(string $phoneNumber): string
    {
        if (strlen($phoneNumber) === 12 && Str::startsWith($phoneNumber, '98')) {
            return Str::replaceFirst('98', '0', $phoneNumber);
        }
        if (strlen($phoneNumber) === 10 && Str::startsWith($phoneNumber, '9')) {
            return '0'.$phoneNumber;
        }

        return $phoneNumber;
    }

    private function callApi(string $url, array $data)
    {
        return $response = Http::withHeaders($this->getRequestHeaders())->post($url, $data);
    }

    private function getRequestHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
//            'X-API-KEY' => $this->settings['api_key'],
//            'X-SANDBOX' => (int) $this->settings['sandbox'],
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

    private function setPaymentUrl(string $url): void
    {
        $this->paymentUrl = $url;
    }

    public function pay(): RedirectionForm
    {
        return $this->redirect($this->getPaymentUrl(), [], 'GET');
    }

    protected function getPaymentUrl(): string
    {
        return $this->getBaseUrl().'gateway/connect_gateway';
    }

    public function verify(): Receipt
    {
        $status = (int) request('status');

        if (! in_array($status, [
            $this->getPendingVerificationStatusCode(),
            $this->getPaymentAlreadyVerifiedStatusCode(),
            $this->getSuccessResponseStatusCode(),
        ], true)
        ) {
            throw new PaymentFailedException($this->getStatusMessage($status), $status);
        }

        $response = $this->callApi($this->getVerificationUrl(), $this->getVerificationData());

        if (isset($response['error_code'])) {
            $message = $response['error_message'] ?? $this->getStatusMessage($response['error_code']);

            throw new PaymentFailedException($message, $response['error_code']);
        }

        $status = (int) $response['status'];

        if ($status === $this->getPaymentAlreadyVerifiedStatusCode()) {
            throw new PaymentAlreadyVerifiedException('پرداخت قبلا تایید شده است', $status);
        }

        if ($status !== $this->getSuccessResponseStatusCode()) {
            throw new PaymentFailedException($this->getStatusMessage($status), $status);
        }

        return new Receipt(
            $this->getInvoice(),
            $response['track_id'],
            $response['payment']['track_id'],
            $response['payment']['card_no'],
        );
    }

    private function getPendingVerificationStatusCode(): int
    {
        return 10;
    }

    private function getPaymentAlreadyVerifiedStatusCode(): int
    {
        return 101;
    }

    protected function getSuccessResponseStatusCode(): int
    {
        return 0200;
    }

    protected function getVerificationUrl(): string
    {
        return 'https://api.idpay.ir/v1.1/payment/verify';
    }

    protected function getVerificationData(): array
    {
        return [
            'id' => request('id', $this->getInvoice()->getTransactionId()),
            'order_id' => request('order_id', $this->getInvoice()->getInvoiceId()),
        ];
    }
}
