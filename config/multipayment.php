<?php

return [
    
    /**
     * set default gateway
     * 
     * valid pattern --> GATEWAY_NAME.GATEWAY_CONFIG_KEY 
     */
    'default_gateway' => env('DEFAULT_GATEWAY', 'zarinpal.default'),

    /**
     *  set to false if your in-app currency is IRR
     */
    'convert_to_rials' => true
];
