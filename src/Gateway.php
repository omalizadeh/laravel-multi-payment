<?php

namespace Omalizadeh\MultiPayment;

use Closure;
use Exception;
use Omalizadeh\MultiPayment\Drivers\Contracts\PurchaseInterface;
use Omalizadeh\MultiPayment\Drivers\Contracts\RefundInterface;
use Omalizadeh\MultiPayment\Drivers\Contracts\UnverifiedPaymentsInterface;
use Omalizadeh\MultiPayment\Exceptions\ConfigurationNotFoundException;
use Omalizadeh\MultiPayment\Exceptions\DriverNotFoundException;
use Omalizadeh\MultiPayment\Exceptions\InvalidConfigurationException;
use ReflectionClass;

class Gateway
{
    protected array $settings;
    protected string $gatewayName;
    protected string $gatewayConfigKey;
    protected PurchaseInterface $driver;

    /**
     * start payment process for given invoice
     *
     * @param  Invoice  $invoice
     * @param  Closure|null  $callback
     * @return RedirectionForm
     * @throws DriverNotFoundException
     */
    public function purchase(Invoice $invoice, ?Closure $callback = null): RedirectionForm
    {
        $this->validateDriverInterfaceImplementation(PurchaseInterface::class);

        $transactionId = $this->getDriver()->setInvoice($invoice)->purchase();

        if ($callback) {
            $callback($transactionId);
        }

        return $this->getDriver()->pay();
    }

    /**
     * verify payment was successful
     *
     * @param  Invoice  $invoice
     * @return Receipt
     * @throws Exception
     */
    public function verify(Invoice $invoice): Receipt
    {
        $this->validateDriverInterfaceImplementation(PurchaseInterface::class);

        return $this->getDriver()->setInvoice($invoice)->verify();
    }

    /**
     * get a list of unverified payments
     *
     * @return array
     * @throws Exception
     */
    public function unverifiedPayments(): array
    {
        $this->validateDriverInterfaceImplementation(UnverifiedPaymentsInterface::class);

        return $this->getDriver()->latestUnverifiedPayments();
    }

    /**
     * refund a payment back to user
     *
     * @param  Invoice  $invoice
     * @return array
     * @throws DriverNotFoundException
     */
    public function refund(Invoice $invoice): array
    {
        $this->validateDriverInterfaceImplementation(RefundInterface::class);

        return $this->getDriver()->setInvoice($invoice)->refund();
    }

    /**
     * @param  string  $gateway
     * @return $this
     * @throws InvalidConfigurationException
     */
    public function setGateway(string $gateway): Gateway
    {
        $gatewayConfig = explode('.', $gateway);

        if (count($gatewayConfig) !== 2 || empty($gatewayConfig[0]) || empty($gatewayConfig[1])) {
            throw new InvalidConfigurationException('Invalid gateway. valid gateway pattern: GATEWAY_NAME.GATEWAY_CONFIG_KEY');
        }

        $this->setGatewayName($gatewayConfig[0]);
        $this->setGatewayConfigKey($gatewayConfig[1]);
        $this->setSettings();
        $this->setDriver();

        return $this;
    }

    /**
     * @return string
     */
    public function getGatewayName(): string
    {
        if (empty($this->gatewayName)) {
            $this->setDefaultGateway();
        }

        return $this->gatewayName;
    }

    /**
     * @return string
     */
    public function getGatewayConfigKey(): string
    {
        if (empty($this->gatewayConfigKey)) {
            $this->setDefaultGateway();
        }

        return $this->gatewayConfigKey;
    }

    private function setDriver(): void
    {
        $this->validateDriver();

        $class = config($this->getDriverNamespaceConfigKey());

        $this->driver = new $class($this->settings);
    }

    private function setSettings(): void
    {
        $settings = config($this->getSettingsConfigKey());

        if (empty($settings) || !is_array($settings)) {
            throw new InvalidConfigurationException('Settings for '.$this->getSettingsConfigKey().' not found.');
        }

        $this->settings = $settings;
    }

    private function setGatewayName(string $gatewayName): void
    {
        $this->gatewayName = $gatewayName;
    }

    private function setGatewayConfigKey(string $gatewayConfigKey): void
    {
        $this->gatewayConfigKey = $gatewayConfigKey;
    }

    private function getSettingsConfigKey(): string
    {
        return 'gateway_'.$this->getGatewayName().'.'.$this->getGatewayConfigKey();
    }

    private function getDriverNamespaceConfigKey(): string
    {
        return 'gateway_'.$this->getGatewayName().'.driver';
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
        $this->setGateway(config('multipayment.default_gateway'));
    }

    private function validateDriver(): void
    {
        if (empty($this->getGatewayName())) {
            throw new ConfigurationNotFoundException('Gateway not selected or default gateway does not exist.');
        }

        if (empty($this->getGatewayConfigKey())) {
            throw new ConfigurationNotFoundException('Gateway configuration key not selected or default configuration does not exist.');
        }

        if (empty(config($this->getSettingsConfigKey())) || empty(config($this->getDriverNamespaceConfigKey()))) {
            throw new DriverNotFoundException('Gateway driver settings not found in config file.');
        }

        if (!class_exists(config($this->getDriverNamespaceConfigKey()))) {
            throw new DriverNotFoundException('Gateway driver class not found. Check driver aliases or try updating the package');
        }
    }

    private function validateDriverInterfaceImplementation(string $interfaceName): void
    {
        $reflect = new ReflectionClass($this->getDriver());

        if (!$reflect->implementsInterface($interfaceName)) {
            throw new DriverNotFoundException("Driver does not implement $interfaceName.");
        }
    }
}
