<?php

return [

    /**
     *  driver class namespace.
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\Zarinpal\Zarinpal::class,

    /**
     *  gateway configurations.
     */
    'main' => [
        'authorization_token' => '', // optional, used only to refund payments (can be created from zarinpal panel)
        'merchant_id' => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'mode' => 'sandbox', // supported values: sandbox, normal, zaringate
        'description' => 'payment using zarinpal',
    ],
    'other' => [
        'authorization_token' => '',
        'merchant_id' => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'mode' => 'normal',
        'description' => 'payment using zarinpal',
    ],
];
