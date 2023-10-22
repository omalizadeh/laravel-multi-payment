<?php

return [

    /**
     *  driver class namespace.
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\Paystar\Paystar::class,

    /**
     * gateway configurations.
     */
    'main' => [
        'gateway_id' => '',
        'secret_key' => '', // If you use sign is true fill this value, It's your gateway secret key for generate sign
        'type' => '', // Type is required => direct | pardakht
        'use_sign' => false,
        'callback' => 'https://yoursite.com/path/to',
        'description' => 'payment using Paystar',
    ],
    'other' => [
        'gateway_id' => '',
        'secret_key' => '',
        'type' => '',
        'use_sign' => false,
        'callback' => 'https://yoursite.com/path/to',
        'description' => 'payment using Paystar',
    ],
];
