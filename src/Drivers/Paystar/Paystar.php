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
    /**
     * @throws PurchaseFailedException
     * @throws HttpRequestFailedException
     * @throws InvalidConfigurationException
     */
    public function purchase(): string
    {
        $purchaseData = $this->getPurchaseData();
        $response = $this->callApi($this->getPurchaseUrl(), $this->getPurchaseData());

        if ($response['status'] !== $this->getSuccessResponseStatusCode()) {
            $message = $response['message'] ?? $this->getStatusMessage($response['status']);

            throw new PurchaseFailedException($message, $response['status'], $purchaseData);
        }

        $this->getInvoice()->setToken($response['data']['token']);
        $this->getInvoice()->setTransactionId($response['data']['ref_num']);
        $this->getInvoice()->setInvoiceId($response['data']['order_id']);

        return $this->getInvoice()->getTransactionId();
    }

    public function pay(): RedirectionForm
    {
        $token = $this->getInvoice()->getToken();
        $paymentUrl = $this->getPaymentUrl();

        return $this->redirect($paymentUrl, ['token' => $token]);
    }

    /**
     * @throws PaymentFailedException
     * @throws HttpRequestFailedException
     * @throws InvalidConfigurationException
     */
    public function verify(): Receipt
    {
        $success = (int) request('status');

        if ($success !== $this->getSuccessResponseStatusCode()) {
            throw new PaymentFailedException('عملیات پرداخت ناموفق بود یا توسط کاربر لغو شد.');
        }

        $response = $this->callApi($this->getVerificationUrl(), $this->getVerificationData());

        if ($response['status'] !== $this->getSuccessResponseStatusCode()) {
            $message = $response['message'] ?? $this->getStatusMessage($response['status']);

            throw new PaymentFailedException($message, $response['status']);
        }

        $this->getInvoice()->setTransactionId($response['data']['ref_num']);

        return new Receipt(
            $this->getInvoice(),
            $response['data']['ref_num'],
            null,
            $response['data']['card_number'],
        );
    }

    /**
     * @throws InvalidConfigurationException
     * @throws \Exception
     * @throws \Exception
     */
    protected function getPurchaseData(): array
    {
        if (empty($this->settings['gateway_id'])) {
            throw new InvalidConfigurationException('gateway_id key has not been set.');
        }

        if (empty($this->settings['type'])) {
            throw new InvalidConfigurationException('type key has not been set.');
        }

        $description = $this->getInvoice()->getDescription() ?? $this->settings['description'];

        $mobile = $this->getInvoice()->getPhoneNumber();
        $email = $this->getInvoice()->getEmail();

        if ($this->settings['use_sign']) {
            if (empty($this->settings['secret_key'])) {
                throw new InvalidConfigurationException('secret_key key has not been set.');
            }

            $sign = hash_hmac('sha512', $this->getInvoice()->getAmount().'#'.$this->getInvoice()->getInvoiceId().'#'.$this->settings['callback'], $this->settings['secret_key']);
        }

        if (! empty($mobile)) {
            $mobile = $this->checkPhoneNumberFormat($mobile);
        }

        $callback = $this->getInvoice()->getCallbackUrl() ?: $this->settings['callback'];
        if ($this->settings['use_sign']) {
            return [
                'amount' => $this->getInvoice()->getAmount(),
                'callback' => $callback,
                'mobile' => $mobile,
                'email' => $email ?? '',
                'order_id' => $this->getInvoice()->getInvoiceId(),
                'description' => $description,
                'sign' => $sign ?? '',
            ];
        }

        return [
            'amount' => $this->getInvoice()->getAmount(),
            'callback' => $callback,
            'mobile' => $mobile,
            'email' => $email ?? '',
            'order_id' => $this->getInvoice()->getInvoiceId(),
            'description' => $description,
        ];

    }

    /**
     * @throws InvalidConfigurationException
     */
    protected function getVerificationData(): array
    {
        $cartNumber = request('card_number');
        $trackingCode = request('tracking_code');
        if ($this->settings['use_sign']) {
            if (empty($this->settings['secret_key'])) {
                throw new InvalidConfigurationException('secret_key key has not been set.');
            }

            $sign = hash_hmac('sha512', $this->getInvoice()->getAmount().'#'.$this->getInvoice()->getTransactionId().'#'.$cartNumber.'#'.$trackingCode, $this->settings['secret_key']);
        }

        if ($this->settings['use_sign']) {
            return [
                'ref_num' => $this->getInvoice()->getTransactionId(),
                'amount' => $this->getInvoice()->getAmount(),
                'sign' => $sign ?? '',
            ];
        }

        return [
            'ref_num' => $this->getInvoice()->getTransactionId(),
            'amount' => $this->getInvoice()->getAmount(),
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
            '-1' => 'درخواست نامعتبر (خطا در پارامترهای ورودی)',
            '-2' => 'درگاه فعال نیست',
            '-3' => 'توکن تکراری است',
            '-4' => 'مبلغ بیشتر از سقف مجاز درگاه است',
            '-5' => 'شناسه ref_num معتبر نیست',
            '-6' => 'تراکنش قبلا وریفای شده است',
            '-7' => 'پارامترهای ارسال شده نامعتبر است',
            '-8' => 'تراکنش را نمیتوان وریفای کرد',
            '-9' => 'تراکنش وریفای نشد',
            '-98' => 'تراکنش ناموفق',
            '-99' => 'خطای سامانه',
        ];

        return array_key_exists($statusCode, $messages) ? $messages[$statusCode] : 'خطای تعریف نشده رخ داده است.';
    }

    protected function getSuccessResponseStatusCode(): int
    {
        return 1;
    }

    protected function getPurchaseUrl(): string
    {
        return 'https://core.paystar.ir/api/'.$this->settings['type'].'/create';
    }

    protected function getPaymentUrl(): string
    {
        return 'https://core.paystar.ir/api/'.$this->settings['type'].'/payment';
    }

    protected function getVerificationUrl(): string
    {
        return 'https://core.paystar.ir/api/'.$this->settings['type'].'/verify';
    }

    private function getRequestHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * @throws HttpRequestFailedException
     */
    private function callApi(string $url, array $data)
    {
        $headers = $this->getRequestHeaders();
        $response = Http::withHeaders($headers)->withToken($this->settings['gateway_id'])->post($url, $data);

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
