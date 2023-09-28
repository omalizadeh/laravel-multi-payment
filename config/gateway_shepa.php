<?php

return [

    /**
     *  driver class namespace.
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\Shepa\Shepa::class,

    /**
     *  soap client options.
     */
    'soap_options' => [
        'encoding' => 'UTF-8',
    ],

    /**
     *  gateway configurations
     */
    'drivers' => [
        'behandam' => [
            'api_key' => 'your api key',
            'callback' => 'https://yoursite.com/path/to',
            'base_url' => 'https://sandbox.shepa.com/',
        ],
        'sandbox' => [
            'api_key' => 'your api key',
            'callback' => 'https://yoursite.com/path/to',
            'base_url' => 'https://sandbox.shepa.com/',

        ],
    ],
];
