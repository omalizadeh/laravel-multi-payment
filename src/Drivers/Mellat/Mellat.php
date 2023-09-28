<?php

namespace Omalizadeh\MultiPayment\Drivers\Mellat;

use Illuminate\Support\Str;
use Omalizadeh\MultiPayment\Drivers\Contracts\Driver;
use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;
use Omalizadeh\MultiPayment\Exceptions\PaymentAlreadyVerifiedException;
use Omalizadeh\MultiPayment\Exceptions\PaymentFailedException;
use Omalizadeh\MultiPayment\Exceptions\PurchaseFailedException;
use Omalizadeh\MultiPayment\Receipt;
use Omalizadeh\MultiPayment\RedirectionForm;
use SoapClient;

class Mellat extends Driver
{
    public function purchase(): string
    {
        $soap = new SoapClient($this->getPurchaseUrl(), $this->getSoapOptions());
        $purchaseData = $this->getPurchaseData();
        $response = $soap->bpPayRequest($purchaseData);
        $responseData = explode(',', $response->return);
        $responseCode = $responseData[0];

        if ($responseCode != $this->getSuccessResponseStatusCode()) {
            throw new PurchaseFailedException(
                $this->getStatusMessage($responseCode),
                $responseCode,
                $purchaseData,
            );
        }

        $hashCode = $responseData[1];

        $this->getInvoice()->setTransactionId($hashCode);

        return $hashCode;
    }

    public function pay(): RedirectionForm
    {
        $mobileNo = $this->getInvoice()->getPhoneNumber();

        if (! empty($mobileNo)) {
            $mobileNo = $this->checkPhoneNumberFormat($mobileNo);
        }

        $data = [
            'RefId' => $this->getInvoice()->getTransactionId(),
            'mobileNo' => $mobileNo,
        ];

        return $this->redirect($this->getPaymentUrl(), $data);
    }

    public function verify(): Receipt
    {
        $responseCode = request('ResCode');

        if ($responseCode != $this->getSuccessResponseStatusCode()) {
            throw new PaymentFailedException($this->getStatusMessage($responseCode), $responseCode);
        }

        $data = $this->getVerificationData();
        $soap = new SoapClient($this->getVerificationUrl(), $this->getSoapOptions());
        $verificationResponse = $soap->bpVerifyRequest($data);
        $responseCode = $verificationResponse->return;

        if ($responseCode != $this->getSuccessResponseStatusCode()) {
            if ($responseCode != $this->getPaymentAlreadyVerifiedStatusCode()) {
                $soap->bpReversalRequest($data);

                throw new PaymentFailedException($this->getStatusMessage($responseCode), $responseCode);
            }

            throw new PaymentAlreadyVerifiedException($this->getStatusMessage($responseCode), $responseCode);
        }

        $settlingResponse = $soap->bpSettleRequest($data);
        $responseCode = $settlingResponse->return;

        if ($responseCode != $this->getSuccessResponseStatusCode()) {
            if ($responseCode != $this->getPaymentAlreadySettledStatusCode() || $responseCode != $this->getPaymentAlreadyReversedStatusCode()) {
                $soap->bpReversalRequest($data);
            }

            throw new PaymentFailedException($this->getStatusMessage($responseCode), $responseCode);
        }

        $this->getInvoice()->setTransactionId(request('RefId'));
        $this->getInvoice()->setInvoiceId(request('SaleOrderId'));

        return new Receipt($this->getInvoice(), $data['saleReferenceId'], request('RefId'), request('CardHolderPan'));
    }

    protected function getPurchaseData(): array
    {
        if (empty($this->settings['terminal_id'])) {
            throw new InvalidConfigurationException('Terminal id has not been set.');
        }

        if (empty($this->settings['username']) || empty($this->settings['password'])) {
            throw new InvalidConfigurationException('Username or password has not been set.');
        }

        if (! empty($this->getInvoice()->getDescription())) {
            $description = $this->getInvoice()->getDescription();
        } else {
            $description = $this->settings['description'];
        }

        return [
            'terminalId' => $this->settings['terminal_id'],
            'userName' => $this->settings['username'],
            'userPassword' => $this->settings['password'],
            'callBackUrl' => $this->getInvoice()->getCallbackUrl() ?: $this->settings['callback_url'],
            'amount' => $this->getInvoice()->getAmount(),
            'localDate' => now()->format('Ymd'),
            'localTime' => now()->format('Gis'),
            'orderId' => (int) $this->getInvoice()->getInvoiceId(),
            'additionalData' => $description,
            'payerId' => $this->getInvoice()->getUserId(),
        ];
    }

    protected function getVerificationData(): array
    {
        $orderId = request('SaleOrderId', $this->getInvoice()->getInvoiceId());
        $verifySaleOrderId = request('SaleOrderId', $this->getInvoice()->getInvoiceId());
        $verifySaleReferenceId = request('SaleReferenceId', $this->getInvoice()->getTransactionId());

        return [
            'terminalId' => $this->settings['terminal_id'],
            'userName' => $this->settings['username'],
            'userPassword' => $this->settings['password'],
            'orderId' => $orderId,
            'saleOrderId' => $verifySaleOrderId,
            'saleReferenceId' => $verifySaleReferenceId,
        ];
    }

    protected function getStatusMessage(int|string $statusCode): string
    {
        $translations = [
            '0' => 'تراکنش با موفقیت انجام شد',
            '11' => 'شماره کارت نامعتبر است',
            '12' => 'موجودی کافی نیست',
            '13' => 'رمز نادرست است',
            '14' => 'تعداد دفعات وارد کردن رمز بیش از حد مجاز است',
            '15' => 'کارت نامعتبر است',
            '16' => 'دفعات برداشت وجه بیش از حد مجاز است',
            '17' => 'کاربر از انجام تراکنش منصرف شده است',
            '18' => 'تاریخ انقضای کارت گذشته است',
            '19' => 'مبلغ برداشت وجه بیش از حد مجاز است',
            '111' => 'صادر کننده کارت نامعتبر است',
            '112' => 'خطای سوییچ صادر کننده کارت',
            '113' => 'پاسخی از صادر کننده کارت دریافت نشد',
            '114' => 'دارنده کارت مجاز به انجام این تراکنش نیست',
            '21' => 'پذیرنده نامعتبر است',
            '23' => 'خطای امنیتی رخ داده است',
            '24' => 'اطلاعات کاربری پذیرنده نامعتبر است',
            '25' => 'مبلغ نامعتبر است',
            '31' => 'پاسخ نامعتبر است',
            '32' => 'فرمت اطلاعات وارد شده صحیح نمی‌باشد',
            '33' => 'حساب نامعتبر است',
            '34' => 'خطای سیستمی',
            '35' => 'تاریخ نامعتبر است',
            '41' => 'شماره درخواست تکراری است',
            '42' => 'تراکنش Sale یافت نشد',
            '43' => 'قبلا درخواست Verify داده شده است',
            '44' => 'درخواست Verify یافت نشد',
            '45' => 'تراکنش Settle شده است',
            '46' => 'تراکنش Settle نشده است',
            '47' => 'تراکنش Settle یافت نشد',
            '48' => 'تراکنش Reverse شده است',
            '412' => 'شناسه قبض نادرست است',
            '413' => 'شناسه پرداخت نادرست است',
            '414' => 'سازمان صادر کننده قبض نامعتبر است',
            '415' => 'زمان جلسه کاری به پایان رسیده است',
            '416' => 'خطا در ثبت اطلاعات',
            '417' => 'شناسه پرداخت کننده نامعتبر است',
            '418' => 'اشکال در تعریف اطلاعات مشتری',
            '419' => 'تعداد دفعات ورود اطلاعات از حد مجاز گذشته است',
            '421' => 'IP نامعتبر است',
            '51' => 'تراکنش تکراری است',
            '54' => 'تراکنش مرجع موجود نیست',
            '55' => 'تراکنش نامعتبر است',
            '61' => 'خطا در واریز',
            '62' => 'مسیر بازگشت به سایت در دامنه ثبت شده برای پذیرنده قرار ندارد',
            '98' => 'سقف استفاده از رمز ایستا به پایان رسیده است',
        ];

        $unknownError = 'خطای ناشناخته رخ داده است.';

        return array_key_exists($statusCode, $translations) ? $translations[$statusCode] : $unknownError;
    }

    protected function getSuccessResponseStatusCode(): string
    {
        return '0';
    }

    private function getPaymentAlreadyVerifiedStatusCode(): string
    {
        return '43';
    }

    private function getPaymentAlreadySettledStatusCode(): string
    {
        return '45';
    }

    private function getPaymentAlreadyReversedStatusCode(): string
    {
        return '48';
    }

    protected function getPurchaseUrl(): string
    {
        return 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';
    }

    protected function getPaymentUrl(): string
    {
        return 'https://bpm.shaparak.ir/pgwchannel/startpay.mellat';
    }

    protected function getVerificationUrl(): string
    {
        return 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';
    }

    private function getSoapOptions(): array
    {
        return config('gateway_mellat.soap_options', [
            'encoding' => 'UTF-8',
        ]);
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
}
