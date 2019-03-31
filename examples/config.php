<?php // -*- mode: php -*-

return [
    'route' => '/auth/:strategy/:action',

    'default' => 'password',

    'strategies' => [
        'password' => [
            'users' => [
                [
                    'user_id' => 'admin',
                    'password' => "$2y$10$1xtzJNWlfv6l1PDyXwiXb.8lpU968CeSXV0p/uTvd6qaqMC2/4GXa"
                ]
            ]
        ],
        'provider' => [
            'key' => 'LDFmiilYf8Fyw5W10rx4W1KsVrieQCnpBzzpTBWA5vJidQKDx8pMJbmw28R1C4m',
            "provider" => '/auth'
        ]
    ]
];
