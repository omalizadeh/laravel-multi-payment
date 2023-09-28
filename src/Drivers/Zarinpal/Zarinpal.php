<?php

namespace Omalizadeh\MultiPayment\Drivers\Zarinpal;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Omalizadeh\MultiPayment\Drivers\Contracts\Driver;
use Omalizadeh\MultiPayment\Drivers\Contracts\RefundInterface;
use Omalizadeh\MultiPayment\Drivers\Contracts\UnverifiedPaymentsInterface;
use Omalizadeh\MultiPayment\Exceptions\HttpRequestFailedException;
use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;
use Omalizadeh\MultiPayment\Exceptions\PaymentAlreadyVerifiedException;
use Omalizadeh\MultiPayment\Exceptions\PaymentFailedException;
use Omalizadeh\MultiPayment\Exceptions\PurchaseFailedException;
use Omalizadeh\MultiPayment\Exceptions\RefundFailedException;
use Omalizadeh\MultiPayment\Receipt;
use Omalizadeh\MultiPayment\RedirectionForm;

class Zarinpal extends Driver implements UnverifiedPaymentsInterface, RefundInterface
{
    public function purchase(): string
    {
        $purchaseData = $this->getPurchaseData();
        $response = $this->callApi($this->getPurchaseUrl(), $purchaseData);

        if (isset($response['errors']['code'])) {
            $responseCode = (int) $response['errors']['code'];
            $message = $this->getStatusMessage($responseCode);

            throw new PurchaseFailedException($message, $responseCode, $purchaseData);
        }

        if (empty($response['data']['authority']) || (int) $response['data']['code'] !== $this->getSuccessResponseStatusCode()) {
            $message = $this->getStatusMessage($response['data']['code']);

            throw new PurchaseFailedException($message, $response['data']['code'], $purchaseData);
        }

        $this->getInvoice()->setTransactionId($response['data']['authority']);

        return $response['data']['authority'];
    }

    public function pay(): RedirectionForm
    {
        $transactionId = $this->getInvoice()->getTransactionId();
        $paymentUrl = $this->getPaymentUrl();

        if ($this->getMode() === 'zaringate') {
            $paymentUrl = str_replace(':authority', $transactionId, $paymentUrl);
        } else {
            $paymentUrl .= $transactionId;
        }

        return $this->redirect($paymentUrl, [], 'GET');
    }

    public function verify(): Receipt
    {
        if (request('Status') !== 'OK' && ! app()->runningUnitTests()) {
            throw new PaymentFailedException('عملیات پرداخت ناموفق بود یا توسط کاربر لغو شد.');
        }

        $response = $this->callApi($this->getVerificationUrl(), $this->getVerificationData());

        if (isset($response['errors']['code'])) {
            $responseCode = (int) $response['errors']['code'];
            $message = $this->getStatusMessage($responseCode);

            throw new PaymentFailedException($message, $responseCode);
        }

        $responseCode = (int) $response['data']['code'];

        if ($responseCode !== $this->getSuccessResponseStatusCode()) {
            $message = $this->getStatusMessage($responseCode);

            if ($responseCode === $this->getPaymentAlreadyVerifiedStatusCode()) {
                throw new PaymentAlreadyVerifiedException($message, $responseCode);
            }

            throw new PaymentFailedException($message, $responseCode);
        }

        $refId = $response['data']['ref_id'];

        return new Receipt($this->getInvoice(), $refId, $refId, $response['data']['card_pan'] ?? null);
    }

    public function latestUnverifiedPayments(): array
    {
        $response = $this->callApi($this->getUnverifiedPaymentsUrl(), $this->getUnverifiedPaymentsData());

        if ((int) $response['data']['code'] !== $this->getSuccessResponseStatusCode()) {
            $message = $this->getStatusMessage($response['data']['code']);

            throw new HttpRequestFailedException($message, $response['data']['code']);
        }

        return $response['data']['authorities'];
    }

    public function refund(): array
    {
        $refundData = $this->getRefundPaymentData();

        $response = $this->callApi(
            $this->getRefundPaymentsUrl(),
            Arr::except($refundData, 'authorization_token'),
            $refundData['authorization_token'],
        );

        if ((int) $response['data']['code'] !== $this->getSuccessResponseStatusCode()) {
            $message = $this->getStatusMessage($response['data']['code']);

            throw new RefundFailedException($message, $response['data']['code']);
        }

        return $response['data'];
    }

    protected function getPurchaseData(): array
    {
        if (empty($this->settings['merchant_id'])) {
            throw new InvalidConfigurationException('Merchant id has not been set.');
        }

        if (! empty($this->getInvoice()->getDescription())) {
            $description = $this->getInvoice()->getDescription();
        } else {
            $description = $this->settings['description'];
        }

        $mobile = $this->getInvoice()->getPhoneNumber();
        $email = $this->getInvoice()->getEmail();

        if (! empty($mobile)) {
            $mobile = $this->checkPhoneNumberFormat($mobile);
        }

        return [
            'merchant_id' => $this->settings['merchant_id'],
            'amount' => $this->getInvoice()->getAmount(),
            'callback_url' => $this->getInvoice()->getCallbackUrl() ?: $this->settings['callback_url'],
            'description' => $description,
            'meta_data' => [
                'mobile' => $mobile,
                'email' => $email,
            ],
        ];
    }

    protected function getVerificationData(): array
    {
        $authority = request('Authority', $this->getInvoice()->getTransactionId());

        return [
            'merchant_id' => $this->settings['merchant_id'],
            'authority' => $authority,
            'amount' => $this->getInvoice()->getAmount(),
        ];
    }

    protected function getUnverifiedPaymentsData(): array
    {
        if (empty($this->settings['merchant_id'])) {
            throw new InvalidConfigurationException('Merchant id has not been set.');
        }

        return [
            'merchant_id' => $this->settings['merchant_id'],
        ];
    }

    protected function getRefundPaymentData(): array
    {
        if (empty($this->settings['merchant_id'])) {
            throw new InvalidConfigurationException('Merchant id has not been set.');
        }

        if (empty($this->settings['authorization_token'])) {
            throw new InvalidConfigurationException('Authorization token needed for refunding payments.');
        }

        if (empty($this->getInvoice()->getTransactionId())) {
            throw new RefundFailedException('Invoice authority (transaction id) has not been set.');
        }

        return [
            'authorization_token' => $this->settings['authorization_token'],
            'merchant_id' => $this->settings['merchant_id'],
            'authority' => $this->getInvoice()->getTransactionId(),
        ];
    }

    protected function getStatusMessage(int|string $statusCode): string
    {
        $messages = [
            -9 => 'خطای اعتبار سنجی',
            -10 => 'آی پی یا مرچنت کد صحیح نیست.',
            -11 => 'مرچنت کد فعال نیست.',
            -12 => 'تلاش بیش از حد در یک بازه زمانی کوتاه',
            -15 => 'ترمینال شما به حالت تعلیق درآمده است.',
            -16 => 'سطح تایید پذیرنده پایین تر از سطح نقره ای است.',
            -30 => 'اجازه دسترسی به تسویه اشتراکی شناور ندارید.',
            -31 => 'حساب بانکی تسویه را به پنل اضافه کنید، مقادیر وارد شده برای تسهیم صحیح نیست.',
            -32 => 'مجموع درصدهای تسهیم از سقف مجاز فراتر رفته است.',
            -33 => 'درصدهای وارد شده صحیح نیست.',
            -34 => 'مبلغ از کل تراکنش بالاتر است.',
            -35 => 'تعداد افراد دریافت کننده تسهیم بیش از حد مجاز است.',
            -40 => 'خطا در اطلاعات ورودی',
            -50 => 'مقدار پرداخت شده با مبلغ وریفای متفاوت است.',
            -51 => 'پرداخت ناموفق',
            -52 => 'خطای غیرمنتظره، با پشتیبانی در تماس باشید.',
            -53 => 'اتوریتی برای این مرچنت نیست.',
            -54 => 'اتوریتی نامعتبر',
            100 => 'عملیات موفق',
            101 => 'تراکنش قبلا وریفای شده است.',
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
        return 101;
    }

    protected function getPurchaseUrl(): string
    {
        $mode = $this->getMode();

        if ($mode === 'sandbox') {
            return 'https://sandbox.zarinpal.com/pg/v4/payment/request.json';
        }

        return 'https://api.zarinpal.com/pg/v4/payment/request.json';
    }

    protected function getPaymentUrl(): string
    {
        $mode = $this->getMode();

        switch ($mode) {
            case 'sandbox':
                $url = 'https://sandbox.zarinpal.com/pg/StartPay/';

                break;
            case 'zaringate':
                $url = 'https://zarinpal.com/pg/StartPay/:authority/ZarinGate';

                break;
            default:
                $url = 'https://zarinpal.com/pg/StartPay/';

                break;
        }

        return $url;
    }

    protected function getVerificationUrl(): string
    {
        $mode = $this->getMode();

        if ($mode === 'sandbox') {
            return 'https://sandbox.zarinpal.com/pg/v4/payment/verify.json';
        }

        return 'https://api.zarinpal.com/pg/v4/payment/verify.json';
    }

    protected function getUnverifiedPaymentsUrl(): string
    {
        $mode = $this->getMode();

        if ($mode === 'sandbox') {
            return 'https://sandbox.zarinpal.com/pg/v4/payment/unVerified.json';
        }

        return 'https://api.zarinpal.com/pg/v4/payment/unVerified.json';
    }

    protected function getRefundPaymentsUrl(): string
    {
        $mode = $this->getMode();

        if ($mode === 'sandbox') {
            return 'https://sandbox.zarinpal.com/pg/v4/payment/refund.json';
        }

        return 'https://api.zarinpal.com/pg/v4/payment/refund.json';
    }

    private function getMode(): string
    {
        return strtolower(trim($this->settings['mode']));
    }

    private function getRequestHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    private function callApi(string $url, array $data, ?string $authorizationToken = null)
    {
        $headers = $this->getRequestHeaders();

        $http = Http::withHeaders($headers);

        if (! is_null($authorizationToken)) {
            $http = $http->withToken($authorizationToken);
        }

        $response = $http->post($url, $data);

        $responseArray = $response->json();

        if (isset($responseArray['data']['code']) || isset($responseArray['errors']['code'])) {
            return $responseArray;
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
