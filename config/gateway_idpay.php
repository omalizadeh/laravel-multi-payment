<?php

return [

    /**
     *  driver class namespace.
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\IDPay\IDPay::class,

    /**
     *  gateway configurations.
     */
    'main' => [
        'api_key' => '',
        'sandbox' => true, // sandbox (testing) mode activation
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using idpay',
    ],
    'other' => [
        'api_key' => '',
        'sandbox' => true,
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using idpay',
    ],
];
