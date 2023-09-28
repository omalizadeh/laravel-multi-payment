<?php

return [

    /**
     *  driver class namespace.
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\Parsian\Parsian::class,

    /**
     *  soap client options.
     */
    'soap_options' => [
        'encoding' => 'UTF-8',
    ],

    /**
     *  gateway configurations.
     */
    'main' => [
        'pin' => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => '',
    ],
    'other' => [
        'pin' => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using parsian',
    ],
];
