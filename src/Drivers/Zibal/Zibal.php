<?php

namespace Omalizadeh\MultiPayment\Drivers\Zibal;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Omalizadeh\MultiPayment\Drivers\Contracts\Driver;
use Omalizadeh\MultiPayment\Exceptions\HttpRequestFailedException;
use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;
use Omalizadeh\MultiPayment\Exceptions\PaymentAlreadyVerifiedException;
use Omalizadeh\MultiPayment\Exceptions\PaymentFailedException;
use Omalizadeh\MultiPayment\Exceptions\PurchaseFailedException;
use Omalizadeh\MultiPayment\Receipt;
use Omalizadeh\MultiPayment\RedirectionForm;

class Zibal extends Driver
{
    public function purchase(): string
    {
        $purchaseData = $this->getPurchaseData();
        $response = $this->callApi($this->getPurchaseUrl(), $purchaseData);

        if ($response['result'] !== $this->getSuccessResponseStatusCode()) {
            $message = $this->getStatusMessage($response['result']);

            throw new PurchaseFailedException($message, $response['result'], $purchaseData);
        }

        $this->getInvoice()->setTransactionId($response['trackId']);

        return $response['trackId'];
    }

    public function pay(): RedirectionForm
    {
        $transactionId = $this->getInvoice()->getTransactionId();

        $paymentUrl = $this->getPaymentUrl();
        $paymentUrl .= $transactionId;

        return $this->redirect($paymentUrl, [], 'GET');
    }

    public function verify(): Receipt
    {
        $success = (int) request('success');

        if ($success !== 1) {
            throw new PaymentFailedException('عملیات پرداخت ناموفق بود یا توسط کاربر لغو شد.');
        }

        $response = $this->callApi($this->getVerificationUrl(), $this->getVerificationData());

        $responseCode = (int) $response['result'];

        if ($responseCode !== $this->getSuccessResponseStatusCode()) {
            $message = $this->getStatusMessage($responseCode);

            if ($responseCode === $this->getPaymentAlreadyVerifiedStatusCode()) {
                throw new PaymentAlreadyVerifiedException($message, $responseCode);
            }

            throw new PaymentFailedException($message, $responseCode);
        }

        $refNum = $response['refNumber'];

        if ($this->settings['merchant'] === 'zibal' && is_null($refNum)) {
            $refNum = 'zibal-sandbox';
        }

        return new Receipt(
            $this->getInvoice(),
            $refNum,
            $refNum,
            $response['cardNumber'],
        );
    }

    protected function getPurchaseData(): array
    {
        if (empty($this->settings['merchant'])) {
            throw new InvalidConfigurationException('Merchant code has not been set.');
        }

        $description = $this->getInvoice()->getDescription() ?? $this->settings['description'];

        $mobile = $this->getInvoice()->getPhoneNumber();

        if (! empty($mobile)) {
            $mobile = $this->checkPhoneNumberFormat($mobile);
        }

        return [
            'merchant' => $this->settings['merchant'],
            'amount' => $this->getInvoice()->getAmount(),
            'callbackUrl' => $this->getInvoice()->getCallbackUrl() ?: $this->settings['callback_url'],
            'description' => $description,
            'orderId' => $this->getInvoice()->getInvoiceId(),
            'mobile' => $mobile,
        ];
    }

    protected function getVerificationData(): array
    {
        $trackId = request('trackId', $this->getInvoice()->getTransactionId());

        return [
            'merchant' => $this->settings['merchant'],
            'trackId' => $trackId,
        ];
    }

    protected function getStatusMessage($statusCode): string
    {
        $messages = [
            -1 => 'در انتظار پرداخت',
            -2 => 'خطای داخلی',
            1 => 'پرداخت شده - تایید شده',
            2 => 'پرداخت شده - تایید نشده',
            3 => 'لغو شده توسط کاربر',
            4 => 'شماره کارت نامعتبر می باشد.',
            5 => 'موجودی حساب کافی نمی باشد.',
            6 => 'رمز وارد شده اشتباه می باشد.',
            7 => 'تعداد درخواست ها بیش از حد مجاز می باشد.',
            8 => 'تعداد پرداخت اینترنتی روزانه بیش از حد مجاز می باشد.',
            9 => 'مبلغ پرداخت اینترنتی روزانه بیش از حد مجاز می باشد.',
            10 => 'صادر کننده کارت نامعتبر می باشد.',
            11 => 'خطای سوییچ',
            12 => 'کارت قابل دسترسی نمی باشد.',
            102 => 'merchant یافت نشد.',
            103 => 'merchant غیرفعال',
            104 => 'merchant نامعتبر',
            105 => 'مبلغ باید بیشتر از 1000 ریال باشد.',
            106 => 'callback_url نامعتبر می باشد.',
            113 => 'مبلغ تراکنش از سقف میزان تراکنش بیشتر است.',
            201 => 'قبلا تایید شده.',
        ];

        $unknownError = 'خطای ناشناخته رخ داده است.';

        return array_key_exists($statusCode, $messages) ? $messages[$statusCode] : $unknownError;
    }

    protected function getSuccessResponseStatusCode(): int
    {
        return 100;
    }

    private function getPaymentAlreadyVerifiedStatusCode(): int
    {
        return 201;
    }

    protected function getPurchaseUrl(): string
    {
        return 'https://gateway.zibal.ir/v1/request';
    }

    protected function getPaymentUrl(): string
    {
        return 'https://gateway.zibal.ir/start/';
    }

    protected function getVerificationUrl(): string
    {
        return 'https://gateway.zibal.ir/v1/verify';
    }

    private function getRequestHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    private function callApi(string $url, array $data)
    {
        $headers = $this->getRequestHeaders();

        $response = Http::withHeaders($headers)->post($url, $data);

        if ($response->successful()) {
            return $response->json();
        }

        throw new HttpRequestFailedException($response->body(), $response->status());
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
}
