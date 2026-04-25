<?php

return [
    /*
    |--------------------------------------------------------------------------
    | M-Pesa Daraja API Configuration
    |--------------------------------------------------------------------------
    | Get credentials from https://developer.safaricom.co.ke
    */

    'consumer_key'    => env('MPESA_CONSUMER_KEY'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
    'shortcode'       => env('MPESA_SHORTCODE'),           // Your Till Number
    'base_url'        => env('MPESA_BASE_URL', 'https://sandbox.safaricom.co.ke'),
    'callback_url'    => env('MPESA_CALLBACK_URL'),        // Your public HTTPS domain

    // For TransactionStatus queries (optional)
    'initiator_name'      => env('MPESA_INITIATOR_NAME'),
    'security_credential' => env('MPESA_SECURITY_CREDENTIAL'),
];