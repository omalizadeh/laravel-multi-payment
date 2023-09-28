<?php

return [

    /**
     *  driver class namespace.
     */
    'driver' => Omalizadeh\MultiPayment\Drivers\Pasargad\Pasargad::class,

    /**
     *  gateway configurations.
     */
    'main' => [
        'merchant_code' => '',
        'terminal_code' => '',
        'certificate_type' => 'xml_file', // supported values: xml_file, xml_string
        'certificate' => '', // certificate as a string or certificate.xml file path
        'callback_url' => 'https://yoursite.com/path/to',
    ],
    'other' => [
        'merchant_code' => '',
        'terminal_code' => '',
        'certificate_type' => 'xml_file',
        'certificate' => '',
        'callback_url' => 'https://yoursite.com/path/to',
    ],
];
