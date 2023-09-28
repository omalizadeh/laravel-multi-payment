<?php

namespace Omalizadeh\MultiPayment\Drivers\Novin;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Omalizadeh\MultiPayment\Drivers\Contracts\Driver;
use Omalizadeh\MultiPayment\Exceptions\HttpRequestFailedException;
use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;
use Omalizadeh\MultiPayment\Exceptions\PaymentFailedException;
use Omalizadeh\MultiPayment\Exceptions\PurchaseFailedException;
use Omalizadeh\MultiPayment\Receipt;
use Omalizadeh\MultiPayment\RedirectionForm;

class Novin extends Driver
{
    protected const BANK_BUY_TRANSACTION_TYPE = 'EN_GOODS';

    private ?string $sessionId = null;

    public function purchase(): string
    {
        $loginData = $this->getLoginData();
        $response = $this->callApi($this->getLoginUrl(), $loginData);

        if ($response['Result'] == $this->getSuccessResponseStatusCode()) {
            $this->sessionId = $response['SessionId'];
        } else {
            throw new PurchaseFailedException(
                $this->getStatusMessage($response['Result']),
                $response['Result'],
                $loginData,
            );
        }

        $purchaseData = $this->getPurchaseData();
        $response = $this->callApi($this->getPurchaseUrl(), $purchaseData);

        if ($response['Result'] == $this->getSuccessResponseStatusCode()) {
            $dataToSign = $response['DataToSign'];
            $dataUniqueId = $response['UniqueId'];

            $signature = $this->getSignature($dataToSign);

            $tokenGenerationData = [
                'WSContext' => $this->getAuthData(),
                'Signature' => $signature,
                'UniqueId' => $dataUniqueId,
            ];

            $response = $this->callApi($this->getTokenGenerationUrl(), $tokenGenerationData);

            if ($response['Result'] == $this->getSuccessResponseStatusCode()) {
                $token = $response['Token'];
                $this->getInvoice()->setToken($token);

                return $this->getInvoice()->getInvoiceId();
            }
        }

        throw new PurchaseFailedException(
            $this->getStatusMessage($response['Result']),
            $response['Result'],
            $purchaseData,
        );
    }

    public function pay(): RedirectionForm
    {
        $payUrl = $this->getPaymentUrl();

        $payUrl .= ('?token='.$this->getInvoice()->getToken().'&language='.$this->getLanguage());

        return $this->redirect($payUrl, [
            'Token' => $this->getInvoice()->getToken(),
            'Language' => $this->getLanguage(),
        ]);
    }

    public function verify(): Receipt
    {
        if (! empty(request('State')) && strtoupper(request('State')) !== 'OK') {
            throw new PaymentFailedException('کاربر از انجام تراکنش منصرف شده است.');
        }

        $verificationData = $this->getVerificationData();
        $response = $this->callApi($this->getVerificationUrl(), $verificationData);

        if ($response['Result'] == $this->getSuccessResponseStatusCode() && $response['Amount'] == $this->getInvoice()->getAmount()) {
            $this->getInvoice()->setTransactionId(request('RefNum'));
            $this->getInvoice()->setInvoiceId(request('ResNum'));

            return new Receipt(
                $this->getInvoice(),
                request('TraceNo'),
                request('CustomerRefNum'),
                request('CardMaskPan'),
            );
        }

        throw new PaymentFailedException($this->getStatusMessage($response['Result']));
    }

    private function getSignature(string $dataToSign): string
    {
        $unsignedFile = fopen($this->getUnsignedDataFilePath(), 'w');
        fwrite($unsignedFile, $dataToSign);
        fclose($unsignedFile);

        $signedFile = fopen($this->getSignedDataFilePath(), 'w');
        fwrite($signedFile, '');
        fclose($signedFile);

        openssl_pkcs7_sign(
            $this->getUnsignedDataFilePath(),
            $this->getSignedDataFilePath(),
            'file://'.$this->settings['certificate_path'],
            ['file://'.$this->settings['certificate_path'], $this->settings['certificate_password']],
            [],
            PKCS7_NOSIGS,
        );

        $sigendData = file_get_contents($this->getSignedDataFilePath());
        $sigendDataParts = explode("\n\n", $sigendData, 2);
        $signedDataFirstPart = $sigendDataParts[1];

        return explode("\n\n", $signedDataFirstPart, 2)[0];
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

    private function getLoginData(): array
    {
        if (empty($this->settings['username'])) {
            throw new InvalidConfigurationException('Username has not been set.');
        }

        if (empty($this->settings['password'])) {
            throw new InvalidConfigurationException('Password has not been set.');
        }

        return [
            'UserName' => $this->settings['username'],
            'Password' => $this->settings['password'],
        ];
    }

    private function getAuthData(): array
    {
        if (! empty($this->sessionId)) {
            return [
                'SessionId' => $this->sessionId,
            ];
        }

        return [
            'UserId' => $this->settings['username'],
            'Password' => $this->settings['password'],
        ];
    }

    protected function getPurchaseData(): array
    {
        $phoneNumber = $this->getInvoice()->getPhoneNumber();

        if (! empty($phoneNumber)) {
            $phoneNumber = $this->checkPhoneNumberFormat($phoneNumber);
        }

        return [
            'WSContext' => $this->getAuthData(),
            'TransType' => static::BANK_BUY_TRANSACTION_TYPE,
            'ReserveNum' => $this->getInvoice()->getInvoiceId(),
            'Amount' => $this->getInvoice()->getAmount(),
            'RedirectUrl' => $this->getInvoice()->getCallbackUrl() ?: $this->settings['callback_url'],
            'MobileNo' => $phoneNumber,
            'Email' => $this->getInvoice()->getEmail(),
            'UserId' => $this->getInvoice()->getUserId(),
        ];
    }

    protected function getVerificationData(): array
    {
        return [
            'WSContext' => $this->getAuthData(),
            'Token' => request('token', $this->getInvoice()->getToken()),
            'RefNum' => request('RefNum', $this->getInvoice()->getTransactionId()),
        ];
    }

    protected function getStatusMessage(int|string $statusCode): string
    {
        $messages = [
            'erSucceed' => 'سرویس با موفقیت اجرا شد.',
            'erAAS_UseridOrPassIsRequired' => 'کد کاربری و رمز الزامی هست.',
            'erAAS_InvalidUseridOrPass' => 'کد کاربری و رمز صحیح نمی باشد.',
            'erAAS_InvalidUserType' => 'نوع کاربر نمی باشد.',
            'erAAS_UserExpired' => 'کاربر منقضی شده است.',
            'erAAS_UserNotActive' => 'کاربر غیرفعال است.',
            'erAAS_UserTemporaryInActive' => 'کاربر موقتا غیرفعال شده است.',
            'erAAS_UserSessionGenerateError' => 'خطا در تولید شناسه لاگین',
            'erAAS_UserPassMinLengthError' => 'حداقل طول رمز رعایت نشده است.',
            'erAAS_UserPassMaxLengthError' => 'حداکثر طول رمز رعایت نشده است.',
            'erAAS_InvalidUserCertificate' => 'برای کاربر فایل سرتیفکیت تعریف نشده است.',
            'erAAS_InvalidPasswordChars' => 'کاراکترهای غیرمجاز در رمز',
            'erAAS_InvalidSession' => 'شناسه لاگین معتبر نمی باشد.',
            'erAAS_InvalidChannelId' => 'کانال معتبر نمی باشد.',
            'erAAS_InvalidParam' => 'پارامترها معتبر نمی باشد.',
            'erAAS_NotAllowedToService' => 'کاربر مجوز سرویس را ندارد.',
            'erAAS_SessionIsExpired' => 'شناسه لاگین معتبر نمی باشد.',
            'erAAS_InvalidData' => 'داده ها معتبر نمی باشد.',
            'erAAS_InvalidSignature' => 'امضاء دیتا درست نمی باشد.',
            'erAAS_InvalidToken' => 'توکن معتبر نمی باشد.',
            'erAAS_InvalidSourceIp' => 'آدرس آی پی معتبر نمی باشد.',
            'erMts_ParamIsNull' => 'پارامترهای ورودی خالی می باشد.',
            'erMts_InvalidAmount' => 'مبلغ معتبر نمی باشد.',
            'erMts_InvalidGoodsReferenceIdLen' => 'طول شناسه خرید معتبر نمی باشد.',
            'erMts_InvalidMerchantGoodsReferenceIdLen' => 'طول شناسه خرید پذیرنده معتبر نمی باشد.',
            'erMts_InvalidMobileNo' => 'فرمت شماره موبایل معتبر نمی باشد.',
            'erMts_InvalidRedirectUrl' => 'طول یا فرمت آدرس صفحه رجوع معتبر نمی باشد.',
            'erMts_InvalidReferenceNum' => 'طول یا فرمت شماره رفرنس معتبر نمی باشد.',
            'erMts_InvalidRequestParam' => 'پارامترهای درخواست معتبر نمی باشد.',
            'erMts_InvalidReserveNum' => 'طول یا فرمت شماره رزرو معتبر نمی باشد.',
            'erMts_InvalidSessionId' => 'شناسه لاگین معتبر نمی باشد.',
            'erMts_InvalidSignature' => 'طول یا فرمت امضاء دیتا معتبر نمی باشد.',
            'erMts_InvalidTerminal' => 'کد ترمینال معتبر نمی باشد.',
            'erMts_InvalidToken' => 'توکن معتبر نمی باشد.',
            'erMts_InvalidUniqueId' => 'کد یکتا معتبر نمی باشد.',
            'erScm_InvalidAcceptor' => 'پذیرنده معتبر نمی باشد.',
        ];

        return array_key_exists($statusCode, $messages) ? $messages[$statusCode] : $statusCode;
    }

    protected function getSuccessResponseStatusCode(): string
    {
        return 'erSucceed';
    }

    protected function getLoginUrl(): string
    {
        return $this->getBaseRestServiceUrl().'merchantLogin/';
    }

    protected function getPurchaseUrl(): string
    {
        return $this->getBaseRestServiceUrl().'generateTransactionDataToSign/';
    }

    protected function getTokenGenerationUrl(): string
    {
        return $this->getBaseRestServiceUrl().'generateSignedDataToken/';
    }

    protected function getPaymentUrl(): string
    {
        return 'https://pna.shaparak.ir/_ipgw_//payment/';
    }

    protected function getVerificationUrl(): string
    {
        return $this->getBaseRestServiceUrl().'verifyMerchantTrans/';
    }

    private function getBaseRestServiceUrl(): string
    {
        return 'https://pna.shaparak.ir/ref-payment2/RestServices/mts/';
    }

    private function getRequestHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    private function getLanguage(): string
    {
        return config('gateway_novin.language', 'fa');
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

    private function getUnsignedDataFilePath(): string
    {
        return rtrim($this->settings['temp_files_dir'], '/').'/unsigned.txt';
    }

    private function getSignedDataFilePath(): string
    {
        return rtrim($this->settings['temp_files_dir'], '/').'/signed.txt';
    }
}
