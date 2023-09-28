<?php

namespace Omalizadeh\MultiPayment\Drivers\Pasargad\Helpers;

class RSAProcessor
{
    public const KEY_TYPE_XML_FILE = 'xml_file';
    public const KEY_TYPE_XML_STRING = 'xml_string';

    private $publicKey;
    private $privateKey;
    private $modulus;
    private $keyLength;

    public function __construct($key, $keyType = null)
    {
        $xmlObject = null;
        $keyType = is_null($keyType) ? null : strtolower($keyType);

        if ($keyType == null || $keyType == self::KEY_TYPE_XML_STRING) {
            $xmlObject = simplexml_load_string($key);
        } elseif ($keyType == self::KEY_TYPE_XML_FILE) {
            $xmlObject = simplexml_load_string(file_get_contents($key));
        }

        $this->modulus = RSA::binaryToNumber(base64_decode($xmlObject->Modulus));
        $this->publicKey = RSA::binaryToNumber(base64_decode($xmlObject->Exponent));
        $this->privateKey = RSA::binaryToNumber(base64_decode($xmlObject->D));
        $this->keyLength = strlen(base64_decode($xmlObject->Modulus)) * 8;
    }

    /**
     * Retrieve public key.
     *
     * @return string|null
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * Retrieve private key.
     *
     * @return string|null
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * Retrieve key length.
     *
     * @return int
     */
    public function getKeyLength()
    {
        return $this->keyLength;
    }

    /**
     * Retrieve modulus.
     *
     * @return string|null
     */
    public function getModulus()
    {
        return $this->modulus;
    }

    /**
     * Encrypt given data.
     *
     * @param string $data
     *
     * @return string
     */
    public function encrypt($data)
    {
        return base64_encode(RSA::rsaEncrypt($data, $this->publicKey, $this->modulus, $this->keyLength));
    }

    /**
     * Decrypt given data.
     *
     * @param $data
     *
     * @return string
     */
    public function decrypt($data)
    {
        return RSA::rsaDecrypt($data, $this->privateKey, $this->modulus, $this->keyLength);
    }

    /**
     * Sign given data.
     *
     * @param string $data
     *
     * @return string
     */
    public function sign($data)
    {
        return RSA::rsaSign($data, $this->privateKey, $this->modulus, $this->keyLength);
    }

    /**
     * Verify RSA data.
     *
     * @param string $data
     *
     * @return bool
     */
    public function verify($data)
    {
        return RSA::rsaVerify($data, $this->publicKey, $this->modulus, $this->keyLength);
    }
}
