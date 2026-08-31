<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default gateway
    |--------------------------------------------------------------------------
    |
    | Which crypto payment gateway drives checkout. "manual" needs no external
    | service: the order is created as pending and an admin confirms payment
    | from the back office. Switch to a real gateway once its keys are set.
    |
    | Supported: manual, nowpayments
    |
    */

    'default' => env('PAYMENT_GATEWAY', 'manual'),

    /*
    |--------------------------------------------------------------------------
    | Accepted crypto methods (shown at checkout)
    |--------------------------------------------------------------------------
    */

    'methods' => [
        'usdt_bsc' => ['label' => 'USDT (BSC)', 'note' => 'Low fee'],
        'usdt_trc20' => ['label' => 'USDT (TRC20)', 'note' => null],
        'usdt_erc20' => ['label' => 'USDT (ERC20)', 'note' => null],
        'usdt_sol' => ['label' => 'USDT (Solana)', 'note' => null],
        'btc' => ['label' => 'Bitcoin (BTC)', 'note' => null],
        'usdc_eth' => ['label' => 'USDC (ETH)', 'note' => null],
    ],

    /*
    |--------------------------------------------------------------------------
    | Gateway settings
    |--------------------------------------------------------------------------
    */

    'gateways' => [

        'manual' => [
            // Wallet addresses the trader sends crypto to (per method).
            // Fill these from the admin settings / .env when going live.
            'wallets' => [
                'usdt_bsc' => env('WALLET_USDT_BSC', ''),
                'usdt_trc20' => env('WALLET_USDT_TRC20', ''),
                'usdt_erc20' => env('WALLET_USDT_ERC20', ''),
                'usdt_sol' => env('WALLET_USDT_SOL', ''),
                'btc' => env('WALLET_BTC', ''),
                'usdc_eth' => env('WALLET_USDC_ETH', ''),
            ],
        ],

        'nowpayments' => [
            'api_key' => env('NOWPAYMENTS_API_KEY'),
            'ipn_secret' => env('NOWPAYMENTS_IPN_SECRET'),
            'base_url' => env('NOWPAYMENTS_BASE_URL', 'https://api.nowpayments.io/v1'),
            // Pay-currency sent to NOWPayments per selected method.
            'pay_currencies' => [
                'usdt_bsc' => 'usdtbsc',
                'usdt_trc20' => 'usdttrc20',
                'usdt_erc20' => 'usdterc20',
                'usdt_sol' => 'usdtsol',
                'btc' => 'btc',
                'usdc_eth' => 'usdc',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Rewards / points
    |--------------------------------------------------------------------------
    */

    'points_per_dollar' => 100,          // 100 points = $1.00 when redeeming
    'cashback_points_per_dollar' => 10,  // earn 10 points per $1 spent

];
