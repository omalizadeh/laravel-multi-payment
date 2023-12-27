<?php

namespace Omalizadeh\MultiPayment;

use Exception;
use Illuminate\Support\Facades\Validator;
use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;
use Ramsey\Uuid\Uuid;

class Invoice
{
    protected float $amount;
    protected string $uuid;
    protected ?int $userId = null;
    protected ?string $token = null;
    protected ?string $email = null;
    protected ?string $userName = null;
    protected ?string $invoiceId = null;
    protected ?string $description = null;
    protected ?string $phoneNumber = null;
    protected ?string $transactionId = null;
    protected ?string $callbackUrl = null;
    protected array $billing = [];
    protected array $products = [];


    /**
     * @param  float  $amount
     * @param  string|null  $transactionId
     */
    public function __construct(float $amount, ?string $transactionId = null)
    {
        $this->setAmount($amount);
        $this->uuid = Uuid::uuid4()->toString();

        if (! empty($transactionId)) {
            $this->setTransactionId($transactionId);
        }
    }

    /**
     * @return string|null
     */
    public function getCallbackUrl(): ?string
    {
        return $this->callbackUrl;
    }

    /**
     * @param  string  $callbackUrl
     * @return $this
     */
    public function setCallbackUrl(string $callbackUrl): self
    {
        $this->callbackUrl = $callbackUrl;

        return $this;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @param $amount
     * @return $this
     */
    public function setAmount($amount): self
    {
        if (config('multipayment.convert_to_rials')) {
            $this->amount = $amount * 10;
        } else {
            $this->amount = $amount;
        }

        return $this;
    }

    /**
     * @return float|int
     */
    public function getAmountInTomans()
    {
        return $this->amount / 10;
    }

    /**
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * @param  string  $token
     * @return $this
     */
    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    /**
     * @param  string  $id
     * @return $this
     */
    public function setTransactionId(string $id): self
    {
        $this->transactionId = $id;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param  string  $description
     * @return $this
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUserName(): ?string
    {
        return $this->userName;
    }

    /**
     * @param  string  $name
     * @return $this
     */
    public function setUserName(string $name): self
    {
        $this->userName = $name;

        return $this;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getInvoiceId(): string
    {
        if (empty($this->invoiceId)) {
            $this->invoiceId = crc32($this->getUuid()).random_int(0, 99999);
        }

        return $this->invoiceId;
    }

    /**
     * @param  string  $invoiceId
     * @return $this
     */
    public function setInvoiceId(string $invoiceId): self
    {
        $this->invoiceId = $invoiceId;

        return $this;
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @return array
     */
    public function getCustomerInfo(): array
    {
        return [
            'user_id' => $this->getUserId(),
            'phone' => $this->getPhoneNumber(),
            'email' => $this->getEmail()
        ];
    }

    /**
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * @param  int  $userId
     * @return $this
     */
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @param  string  $phone
     * @return $this
     */
    public function setPhoneNumber(string $phone): self
    {
        $this->phoneNumber = $phone;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param  string  $email
     * @return $this
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return array
     */
    public function getBilling(): array
    {
        return $this->billing;
    }

    /**
     * @param  array  $billing
     * @return Invoice
     * @throws InvalidConfigurationException
     */
    public function setBilling(array $billing): Invoice
    {
        $validator = Validator::make($billing, [
            'first_name' => 'required',
            'last_name' => 'required',
            'address_1' => 'required',
            'city' => 'required',
            'state' => 'required',
            'postcode' => 'required',
            'country' => 'required',
            'email' => 'required',
            'phone' => 'required',
        ]);

        if ($validator->fails())
            throw new InvalidConfigurationException($validator->errors()->toJson());

        $this->billing = $billing;
        return $this;
    }

    /**
     * @return array
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    /**
     * @param  array  $product
     * @return $this
     */
    public function setProducts(array $product): Invoice
    {
        $validator = Validator::make($product, [
            '*.id'=>'required',
            '*.name'=>'required',
            '*.price'=>'required',
            '*.qty'=>'required',
        ]);

        if ($validator->fails())
            throw new InvalidConfigurationException($validator->errors()->toJson());


        $this->products = $product;
        return $this;
    }
}
