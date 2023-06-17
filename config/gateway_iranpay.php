<?php

return [

    /**
     *  driver class namespace.
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\IranPay\IranPay::class,

    /**
     *  gateway configurations.
     */
    'main' => [
        'merchant_id' => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using iran pay',
    ],
];
