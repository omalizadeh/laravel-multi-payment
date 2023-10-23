<?php

return [

    /**
     * set default gateway.
     *
     * valid pattern --> GATEWAY_NAME.GATEWAY_CONFIG_KEY
     * valid GATEWAY_NAME  --> zarinpal, saman, mellat, novin, parsian, pasargad, zibal, payir, idpay, paystar
     */
    'default_gateway' => env('DEFAULT_PAYMENT_GATEWAY', 'zarinpal.main'),

    /**
     *  set to false if your in-app currency is IRR.
     */
    'convert_to_rials' => true,
];
