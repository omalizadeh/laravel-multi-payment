<?php

namespace Omalizadeh\MultiPayment\Exceptions;

use Exception;

class PurchaseFailedException extends Exception
{
    private array $context;

    public function __construct($message = '', $code = 0, array $context = [])
    {
        parent::__construct($message, $code);

        $this->context = $context;
    }

    public function context(): array
    {
        return $this->context;
    }
}
