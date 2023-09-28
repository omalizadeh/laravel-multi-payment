<?php

namespace Omalizadeh\MultiPayment\Drivers\PayIr;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Omalizadeh\MultiPayment\Drivers\Contracts\Driver;
use Omalizadeh\MultiPayment\Exceptions\HttpRequestFailedException;
use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;
use Omalizadeh\MultiPayment\Exceptions\PaymentFailedException;
use Omalizadeh\MultiPayment\Exceptions\PurchaseFailedException;
use Omalizadeh\MultiPayment\Receipt;
use Omalizadeh\MultiPayment\RedirectionForm;

class PayIr extends Driver
{
    public function purchase(): string
    {
        $purchaseData = $this->getPurchaseData();
        $response = $this->callApi($this->getPurchaseUrl(), $this->getPurchaseData());

        if ($response['status'] !== $this->getSuccessResponseStatusCode()) {
            $message = $response['errorMessage'] ?? $this->getStatusMessage($response['errorCode']);

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
        if (empty($this->settings['api_key'])) {
            throw new InvalidConfigurationException('Api key has not been set.');
        }

        $description = $this->getInvoice()->getDescription() ?? $this->settings['description'];

        $mobile = $this->getInvoice()->getPhoneNumber();

        if (! empty($mobile)) {
            $mobile = $this->checkPhoneNumberFormat($mobile);
        }

        return [
            'api' => $this->settings['api_key'],
            'amount' => $this->getInvoice()->getAmount(),
            'redirect' => $this->getInvoice()->getCallbackUrl() ?: $this->settings['callback_url'],
            'mobile' => $mobile,
            'factorNumber' => $this->getInvoice()->getInvoiceId(),
            'description' => $description,
        ];
    }

    protected function getVerificationData(): array
    {
        $token = request('token', $this->getInvoice()->getToken());

        return [
            'api' => $this->settings['api_key'],
            'token' => $token,
        ];
    }

    protected function getStatusMessage(int|string $statusCode): string
    {
        $messages = [
            '0' => 'درحال حاضر درگاه بانکی قطع شده و مشکل بزودی برطرف می شود',
            '-1' => 'API Key ارسال نمی شود',
            '-2' => 'Token ارسال نمی شود',
            '-3' => 'API Key ارسال شده اشتباه است',
            '-4' => 'امکان انجام تراکنش برای این پذیرنده وجود ندارد',
            '-5' => 'تراکنش با خطا مواجه شده است',
            '-6' => 'تراکنش تکراریست یا قبلا انجام شده',
            '-7' => 'مقدار Token ارسالی اشتباه است',
            '-8' => 'شماره تراکنش ارسالی اشتباه است',
            '-9' => 'زمان مجاز برای انجام تراکنش تمام شده',
            '-10' => 'مبلغ تراکنش ارسال نمی شود',
            '-11' => 'مبلغ تراکنش باید به صورت عددی و با کاراکترهای لاتین باشد',
            '-12' => 'مبلغ تراکنش می بایست عددی بین 10,000 و 500,000,000 ریال باشد',
            '-13' => 'مقدار آدرس بازگشتی ارسال نمی شود',
            '-14' => 'آدرس بازگشتی ارسالی با آدرس درگاه ثبت شده در شبکه پرداخت پی یکسان نیست',
            '-15' => 'امکان وریفای وجود ندارد. این تراکنش پرداخت نشده است',
            '-16' => 'یک یا چند شماره موبایل از اطلاعات پذیرندگان ارسال شده اشتباه است',
            '-17' => 'میزان سهم ارسالی باید بصورت عددی و بین 1 تا 100 باشد',
            '-18' => 'فرمت پذیرندگان صحیح نمی باشد',
            '-19' => 'هر پذیرنده فقط یک سهم میتواند داشته باشد',
            '-20' => 'مجموع سهم پذیرنده ها باید 100 درصد باشد',
            '-21' => 'Reseller ID ارسالی اشتباه است',
            '-22' => 'فرمت یا طول مقادیر ارسالی به درگاه اشتباه است',
            '-23' => 'سوییچ PSP ( درگاه بانک ) قادر به پردازش درخواست نیست. لطفا لحظاتی بعد مجددا تلاش کنید',
            '-24' => 'شماره کارت باید بصورت 16 رقمی، لاتین و چسبیده بهم باشد',
            '-25' => 'امکان استفاده از سرویس در کشور مبدا شما وجود نداره',
            '-26' => 'امکان انجام تراکنش برای این درگاه وجود ندارد',
        ];

        return array_key_exists($statusCode, $messages) ? $messages[$statusCode] : 'خطای تعریف نشده رخ داده است.';
    }

    protected function getSuccessResponseStatusCode(): int
    {
        return 1;
    }

    protected function getPurchaseUrl(): string
    {
        return 'https://pay.ir/pg/send';
    }

    protected function getPaymentUrl(): string
    {
        return 'https://pay.ir/pg/';
    }

    protected function getVerificationUrl(): string
    {
        return 'https://pay.ir/pg/verify';
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
