<?php

namespace Omalizadeh\MultiPayment;

use Closure;
use Exception;
use Omalizadeh\MultiPayment\Drivers\Contracts\PurchaseInterface;
use Omalizadeh\MultiPayment\Drivers\Contracts\RefundInterface;
use Omalizadeh\MultiPayment\Drivers\Contracts\UnverifiedPaymentsInterface;
use Omalizadeh\MultiPayment\Exceptions\DriverNotFoundException;
use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;
use ReflectionClass;

class PaymentGateway
{
    private array $configs;
    private string $providerName;
    private string $providerInstanceConfigKey;
    private PurchaseInterface $driver;

    /**
     * start payment process for given invoice.
     *
     * @param  Invoice  $invoice
     * @param  Closure|null  $callback
     * @return RedirectionForm
     * @throws DriverNotFoundException
     */
    public function purchase(Invoice $invoice, ?Closure $callback = null): RedirectionForm
    {
        $this->checkDriverImplementsInterface(PurchaseInterface::class);

        $transactionId = $this->getDriver()->setInvoice($invoice)->purchase();

        if ($callback) {
            $callback($transactionId);
        }

        return $this->getDriver()->pay();
    }

    /**
     * start payment process for given invoice
     *
     * @param  Invoice  $invoice
     * @param  Closure|null  $callback
     * @return RedirectionForm
     * @throws DriverNotFoundException
     */
    public function purchaseView(Invoice $invoice, ?Closure $callback = null)
    {
        $this->checkDriverImplementsInterface(PurchaseInterface::class);

        $view = $this->getDriver()->setInvoice($invoice)->purchaseView();

        return $view;
    }


    /**
     * verify payment was successful.
     *
     * @param  Invoice  $invoice
     * @return Receipt
     * @throws Exception
     */
    public function verify(Invoice $invoice): Receipt
    {
        $this->checkDriverImplementsInterface(PurchaseInterface::class);

        return $this->getDriver()->setInvoice($invoice)->verify();
    }

    /**
     * get a list of unverified payments.
     *
     * @return array
     * @throws Exception
     */
    public function unverifiedPayments(): array
    {
        $this->checkDriverImplementsInterface(UnverifiedPaymentsInterface::class);

        return $this->getDriver()->latestUnverifiedPayments();
    }

    /**
     * refund a payment back to user.
     *
     * @param  Invoice  $invoice
     * @return array
     * @throws DriverNotFoundException
     */
    public function refund(Invoice $invoice): array
    {
        $this->checkDriverImplementsInterface(RefundInterface::class);

        return $this->getDriver()->setInvoice($invoice)->refund();
    }

    /**
     * @param  string  $providerName
     * @param  string  $providerInstanceConfigKey
     * @return $this
     * @throws InvalidConfigurationException
     */
    public function setProvider(string $providerName, string $providerInstanceConfigKey): PaymentGateway
    {
        $this->setProviderName($providerName);
        $this->setProviderInstanceConfigKey($providerInstanceConfigKey);
        $this->setConfigs();
        $this->setDriver();

        return $this;
    }

    /**
     * @return string
     * @throws InvalidConfigurationException
     */
    public function getProviderName(): string
    {
        if (empty($this->providerName)) {
            $this->setDefaultGateway();
        }

        return $this->providerName;
    }

    /**
     * @return string
     * @throws InvalidConfigurationException
     */
    public function getProviderInstanceConfigKey(): string
    {
        if (empty($this->providerInstanceConfigKey)) {
            $this->setDefaultGateway();
        }

        return $this->providerInstanceConfigKey;
    }

    private function setConfigs(): void
    {
        $configs = config($this->getConfigKey());

        if (empty($configs) || ! is_array($configs)) {
            throw new InvalidConfigurationException('Configurations for '.$this->getConfigKey().' not found.');
        }

        $this->configs = $configs;
    }

    private function setDriver(): void
    {
        $this->validateDriver();

        $class = config($this->getDriverConfigKey());

        $this->driver = new $class($this->getConfigs());
    }

    private function setProviderName(string $providerName): void
    {
        $this->providerName = $providerName;
    }

    private function setProviderInstanceConfigKey(string $providerInstanceConfigKey): void
    {
        $this->providerInstanceConfigKey = $providerInstanceConfigKey;
    }

    private function getConfigs(): array
    {
        return $this->configs;
    }

    private function getConfigKey(): string
    {
        return 'gateway_'.$this->getProviderName().'.'.$this->getProviderInstanceConfigKey();
    }

    private function getDriverConfigKey(): string
    {
        return 'gateway_'.$this->getProviderName().'.driver';
    }

    private function getDriver(): PurchaseInterface
    {
        if (empty($this->driver)) {
            $this->setDefaultGateway();
        }

        return $this->driver;
    }

    private function setDefaultGateway(): void
    {
        $providerSpecs = explode('.', config('multipayment.default_gateway'));

        if (count($providerSpecs) !== 2 || empty($providerSpecs[0]) || empty($providerSpecs[1])) {
            throw new InvalidConfigurationException('Invalid default gateway. valid gateway pattern: GATEWAY_NAME.GATEWAY_CONFIG_KEY');
        }

        $this->setProvider($providerSpecs[0], $providerSpecs[1]);
    }

    private function validateDriver(): void
    {
        if (empty(config($this->getDriverConfigKey()))) {
            throw new DriverNotFoundException('Driver not specified.');
        }

        if (! class_exists(config($this->getDriverConfigKey()))) {
            throw new DriverNotFoundException('Driver class not found.');
        }
    }

    private function checkDriverImplementsInterface(string $interface): void
    {
        $reflect = new ReflectionClass($this->getDriver());

        if (! $reflect->implementsInterface($interface)) {
            throw new DriverNotFoundException("Driver does not implement $interface.");
        }
    }
}
