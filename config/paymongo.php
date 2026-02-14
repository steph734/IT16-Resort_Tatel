<?php

return [
    // Secret key for server-to-server requests
    'secret_key' => env('PAYMONGO_SECRET_KEY'),

    // Public key for client usage if needed in the future
    'public_key' => env('PAYMONGO_PUBLIC_KEY'),

    // Base API URL
    'base_url' => env('PAYMONGO_BASE_URL', 'https://api.paymongo.com/v1'),

    // Optional webhook secret for signature verification
    'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),

    // Preferred BDO Online method type for Checkout Sessions
    // Common value: 'online_banking_bdo'. Keep this configurable in case your
    // account uses a different identifier. You can override via .env
    // PAYMONGO_BDO_METHOD_TYPE=online_banking_bdo
    'bdo_method_type' => env('PAYMONGO_BDO_METHOD_TYPE', 'online_banking_bdo'),

    // Default payment methods we allow in Checkout Sessions
    // We restrict to GCash and BDO Online by default. The controller will
    // gracefully fallback to only ['gcash'] if BDO method is unsupported.
    'default_method_types' => [
        'gcash',
        env('PAYMONGO_BDO_METHOD_TYPE', 'online_banking_bdo'),
    ],
];
