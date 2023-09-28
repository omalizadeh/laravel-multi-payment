<?php

namespace Omalizadeh\MultiPayment\Drivers\Pasargad;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Omalizadeh\MultiPayment\Drivers\Contracts\Driver;
use Omalizadeh\MultiPayment\Drivers\Pasargad\Helpers\RSAProcessor;
use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;
use Omalizadeh\MultiPayment\Exceptions\PaymentFailedException;
use Omalizadeh\MultiPayment\Exceptions\PurchaseFailedException;
use Omalizadeh\MultiPayment\Receipt;
use Omalizadeh\MultiPayment\RedirectionForm;

class Pasargad extends Driver
{
    protected const BANK_BUY_ACTION_CODE = 1003;

    public function purchase(): string
    {
        $purchaseData = $this->getPurchaseData();
        $response = $this->callApi($this->getPurchaseUrl(), $purchaseData);

        if ($response->successful()) {
            $result = $response->json();

            if ($result['IsSuccess'] != $this->getSuccessResponseStatusCode()) {
                throw new PurchaseFailedException($result['Message'], $response->status(), $purchaseData);
            }

            $this->getInvoice()->setToken($result['Token']);

            return $purchaseData['InvoiceNumber'];
        }

        throw new PurchaseFailedException($response->body(), $response->status(), $purchaseData);
    }

    public function pay(): RedirectionForm
    {
        $payUrl = $this->getPaymentUrl();

        $data = [
            'Token' => $this->getInvoice()->getToken(),
        ];

        return $this->redirect($payUrl, $data);
    }

    public function verify(): Receipt
    {
        $checkTransactionData = $this->getCheckTransactionData();
        $response = $this->callApi($this->getCheckTransactionUrl(), $checkTransactionData);

        if ($response->successful()) {
            $checkTransactionResult = $response->json();

            if ($checkTransactionResult['IsSuccess'] != $this->getSuccessResponseStatusCode()) {
                throw new PaymentFailedException($checkTransactionResult['Message'], $response->status());
            }

            $verificationData = array_merge($this->getVerificationData(), [
                'InvoiceNumber' => $checkTransactionResult['InvoiceNumber'],
                'InvoiceDate' => $checkTransactionResult['InvoiceDate'],
                'Amount' => $checkTransactionResult['Amount'],
            ]);

            $response = $this->callApi($this->getVerificationUrl(), $verificationData);

            if ($response->successful()) {
                $verificationResult = $response->json();

                if ($verificationResult['IsSuccess'] != $this->getSuccessResponseStatusCode()) {
                    throw new PaymentFailedException($verificationResult['Message'], $response->status());
                }

                $this->getInvoice()->setTransactionId($checkTransactionResult['TransactionReferenceID']);

                return new Receipt(
                    $this->getInvoice(),
                    $checkTransactionResult['TraceNumber'],
                    $checkTransactionResult['ReferenceNumber'],
                    $verificationResult['MaskedCardNumber'],
                );
            }
        }

        throw new PaymentFailedException($response->body(), $response->status());
    }

    private function callApi(string $url, array $data)
    {
        $body = json_encode($data, JSON_THROW_ON_ERROR);
        $sign = $this->signData($body);
        $headers = $this->getRequestHeaders();
        $headers = array_merge($headers, [
            'Sign' => $sign,
        ]);

        return Http::withHeaders($headers)->post($url, $data);
    }

    private function signData(string $stringifiedData)
    {
        $stringifiedData = sha1($stringifiedData, true);
        $certificate = $this->settings['certificate'];
        $certificateType = $this->settings['certificate_type'];
        $processor = new RSAProcessor($certificate, $certificateType);
        $data = $processor->sign($stringifiedData);

        return base64_encode($data);
    }

    protected function getPurchaseData(): array
    {
        if (empty($this->settings['merchant_code'])) {
            throw new InvalidConfigurationException('Merchant code has not been set.');
        }

        if (empty($this->settings['terminal_code'])) {
            throw new InvalidConfigurationException('Terminal code has not been set.');
        }

        $mobile = $this->getInvoice()->getPhoneNumber();

        if (! empty($mobile)) {
            $mobile = $this->checkPhoneNumberFormat($mobile);
        }

        return [
            'Action' => static::BANK_BUY_ACTION_CODE,
            'MerchantCode' => $this->settings['merchant_code'],
            'TerminalCode' => $this->settings['terminal_code'],
            'RedirectAddress' => $this->getInvoice()->getCallbackUrl() ?: $this->settings['callback_url'],
            'Amount' => $this->getInvoice()->getAmount(),
            'Mobile' => $mobile,
            'Email' => $this->getInvoice()->getEmail(),
            'InvoiceNumber' => $this->getInvoice()->getInvoiceId(),
            'InvoiceDate' => now()->format('Y/m/d H:i:s'),
            'Timestamp' => now()->format('Y/m/d H:i:s'),
        ];
    }

    protected function getVerificationData(): array
    {
        return [
            'MerchantCode' => $this->settings['merchant_code'],
            'TerminalCode' => $this->settings['terminal_code'],
            'Timestamp' => now()->format('Y/m/d H:i:s'),
        ];
    }

    protected function getCheckTransactionData(): array
    {
        return [
            'MerchantCode' => $this->settings['merchant_code'],
            'TerminalCode' => $this->settings['terminal_code'],
            'InvoiceNumber' => request('iN', $this->getInvoice()->getInvoiceId()),
            'InvoiceDate' => request('iD', now()->format('Y/m/d H:i:s')),
        ];
    }

    protected function getStatusMessage(int|string $statusCode): string
    {
        return 'خطا در تبادل اطلاعات با درگاه';
    }

    protected function getSuccessResponseStatusCode(): bool
    {
        return true;
    }

    protected function getPurchaseUrl(): string
    {
        return 'https://pep.shaparak.ir/Api/v1/Payment/GetToken';
    }

    protected function getPaymentUrl(): string
    {
        return 'https://pep.shaparak.ir/payment.aspx';
    }

    protected function getVerificationUrl(): string
    {
        return 'https://pep.shaparak.ir/Api/v1/Payment/VerifyPayment';
    }

    private function getCheckTransactionUrl(): string
    {
        return 'https://pep.shaparak.ir/Api/v1/Payment/CheckTransactionResult';
    }

    private function getRequestHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    private function checkPhoneNumberFormat(string $phoneNumber): string
    {
        if (strlen($phoneNumber) === 12 && Str::startsWith($phoneNumber, '98')) {
            return Str::replaceFirst('98', '', $phoneNumber);
        }

        if (strlen($phoneNumber) === 11 && Str::startsWith($phoneNumber, '0')) {
            return Str::replaceFirst('0', '', $phoneNumber);
        }

        return $phoneNumber;
    }
}
