<?php

return [

    /**
     *  driver class namespace.
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\Paystar\Paystar::class,

    /**
     *  gateway configurations.
     * gateway_id is required
     * type is required => direct | pardakht
     * use_sign is optional => true | false
     * secret_key is optional => If use sign is true fill this value, It's your gateway secret key for generate sign
     */
    'main' => [
        'gateway_id' => '',
        'secret_key' => '',
        'type' => '',
        'use_sign' => '',
        'callback' => 'https://yoursite.com/path/to',
        'description' => 'payment using paystar',
    ],
    'other' => [
        'gateway_id' => '',
        'secret_key' => '',
        'type' => '',
        'use_sign' => '',
        'callback' => 'https://yoursite.com/path/to',
        'description' => 'payment using paystar',
    ],
];
