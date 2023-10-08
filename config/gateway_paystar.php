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
        'pin' => 'exg1m6l481zxy1',
        'secret' => '',
        'callback' => 'https://yoursite.com/path/to',
        'description' => 'payment using paystar',
    ],
    'other' => [
        'pin' => '',
        'secret' => '',
        'callback' => 'https://yoursite.com/path/to',
        'description' => 'payment using paystar',
    ],
];
