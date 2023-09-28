<?php

return [

    /**
     *  driver class namespace.
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\Mellat\Mellat::class,

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
        'terminal_id' => '',
        'username' => '',
        'password' => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using mellat gateway',
    ],
    'other' => [
        'terminal_id' => '',
        'username' => '',
        'password' => '',
        'callback_url' => 'https://yoursite.com/path/to',
        'description' => 'payment using mellat gateway',
    ],
];
