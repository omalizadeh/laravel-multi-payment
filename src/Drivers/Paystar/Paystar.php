<?php

namespace Omalizadeh\MultiPayment\Drivers\Paystar;


use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Omalizadeh\MultiPayment\Drivers\Contracts\Driver;
use Omalizadeh\MultiPayment\Exceptions\HttpRequestFailedException;
use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;
use Omalizadeh\MultiPayment\Exceptions\PaymentFailedException;
use Omalizadeh\MultiPayment\Exceptions\PurchaseFailedException;
use Omalizadeh\MultiPayment\Receipt;
use Omalizadeh\MultiPayment\RedirectionForm;


class Paystar extends Driver
{
    public function purchase(): string
    {
        $purchaseData = $this->getPurchaseData();
        $response = $this->callApi($this->getPurchaseUrl(), $this->getPurchaseData());
        if ($response['status'] !== $this->getSuccessResponseStatusCode()) {
            $message = $response['message'] ?? $this->getStatusMessage($response['status']);

            throw new PurchaseFailedException($message, $response['errorCode'], $purchaseData);
        }

        $this->getInvoice()->setToken($response['token']);

        return $this->getInvoice()->getInvoiceId();
    }

    public function pay(): RedirectionForm
    {
        $token = $this->getInvoice()->getToken();

        $paymentUrl = $this->getPaymentUrl();
        $paymentUrl .= $token;

        return $this->redirect($paymentUrl, [], 'GET');
    }

    public function verify(): Receipt
    {
        $success = (int) request('status');

        if ($success !== $this->getSuccessResponseStatusCode()) {
            throw new PaymentFailedException('عملیات پرداخت ناموفق بود یا توسط کاربر لغو شد.');
        }

        $response = $this->callApi($this->getVerificationUrl(), $this->getVerificationData());

        if ($response['status'] !== $this->getSuccessResponseStatusCode()) {
            $message = $response['errorMessage'] ?? $this->getStatusMessage($response['errorCode']);

            throw new PaymentFailedException($message, $response['errorCode']);
        }

        $this->getInvoice()->setTransactionId($response['transId']);

        return new Receipt(
            $this->getInvoice(),
            $response['transId'],
            null,
            $response['cardNumber'],
        );
    }

    protected function getPurchaseData(): array
    {
        if (empty($this->settings['pin'])) {
            throw new InvalidConfigurationException('Pin key has not been set.');
        }

        $description = $this->getInvoice()->getDescription() ?? $this->settings['description'];

        $mobile = $this->getInvoice()->getPhoneNumber();

        if (! empty($mobile)) {
            $mobile = $this->checkPhoneNumberFormat($mobile);
        }

        $callback=$this->getInvoice()->getCallbackUrl() ?: $this->settings['callback'];
        return [
            'pin' => $this->settings['pin'],
            'amount' => $this->getInvoice()->getAmount(),
            'callback' => $callback,
            'mobile' => $mobile,
            'order_id' => $this->getInvoice()->getInvoiceId(),
            'description' => $description,
            'sign'=>hash_hmac('sha512',$this->getInvoice()->getAmount().'#'.$this->getInvoice()->getInvoiceId().'#'.$callback, $this->settings['secret'])
        ];
    }

    protected function getVerificationData(): array
    {
        $token = request('token', $this->getInvoice()->getToken());

        return [
            'pin' => $this->settings['pin'],
            'token' => $token,
        ];
    }

    protected function getStatusMessage(int|string $statusCode): string
    {
        $messages = [
            '-101' => 'درخواست نامعتبر (خطا در پارامترهای ورودی)',
            '-102' => 'درگاه فعال نیست',
            '-103' => 'توکن تکراری است',
            '-104' => 'مبلغ بیشتر از سقف مجاز درگاه است',
            '-105' => 'شناسه ref_num معتبر نیست',
            '-106' => 'تراکنش قبلا وریفای شده است',
            '-107' => 'پارامترهای ارسال شده نامعتبر است',
            '-108' => 'تراکنش را نمیتوان وریفای کرد',
            '-109' => 'تراکنش وریفای نشد',
            '-198' => 'تراکنش ناموفق',
            '-199' => 'خطای سامانه',
        ];

        return array_key_exists($statusCode, $messages) ? $messages[$statusCode] : 'خطای تعریف نشده رخ داده است.';
    }

    protected function getSuccessResponseStatusCode(): int
    {
        return 1;
    }

    protected function getPurchaseUrl(): string
    {
        return 'https://core.paystar.ir/api/direct/create';
    }

    protected function getPaymentUrl(): string
    {
        return 'https://core.paystar.ir/api/direct';
    }

    protected function getVerificationUrl(): string
    {
        return 'https://core.paystar.ir/api/direct/payment';
    }

    private function getRequestHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization: Bearer '.$this->settings['pin']
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
