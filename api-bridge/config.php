<?php
/**
 * Metin2 Web Panel - Server Configuration
 * Real server: 192.168.1.105
 */

return [
    'databases' => [
        'account' => [
            'host' => '127.0.0.1',
            'port' => 3306,
            'user' => 'root',
            'pass' => 'password',
            'db' => 'srv1_account'     // ← Gerçek DB ismi
        ],
        'player' => [
            'host' => '127.0.0.1',
            'port' => 3306,
            'user' => 'root',
            'pass' => 'password',
            'db' => 'srv1_player'      // ← Gerçek DB ismi
        ],
        'common' => [
            'host' => '127.0.0.1',
            'port' => 3306,
            'user' => 'root',
            'pass' => 'password',
            'db' => 'srv1_common'      // ← Gerçek DB ismi
        ],
        'log' => [
            'host' => '127.0.0.1',
            'port' => 3306,
            'user' => 'root',
            'pass' => 'password',
            'db' => 'srv1_log'         // ← Gerçek DB ismi
        ]
    ],

    // Security settings
    'security' => [
        'allowed_origins' => ['*'],
        'rate_limit' => 100,
    ]
];
