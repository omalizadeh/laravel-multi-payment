<?php

return [

    /**
     *  driver class namespace.
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\PayIr\PayIr::class,

    /**
     *  gateway configurations.
     */
    'main' => [
        'api_key' => '', // use 'test' to enable sandbox mode
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using payir',
    ],
    'other' => [
        'api_key' => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using payir',
    ],
];
