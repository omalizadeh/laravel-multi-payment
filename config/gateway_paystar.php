<?php

return [

    /**
     *  driver class namespace.
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\Paystar\Paystar::class,

    /**
     *  gateway configurations.
     */
    'main' => [
        'gateway_id' => '',
        'callback' => 'https://yoursite.com/path/to',
        'description' => 'payment using paystar',
    ],
    'other' => [
        'gateway_id' => '',
        'callback' => 'https://yoursite.com/path/to',
        'description' => 'payment using paystar',
    ],
];
