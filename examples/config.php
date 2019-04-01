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
            "provider" => '/auth',
            'key' => 'ahPho9eenaewaqu8oojiehoS3vah3lae'
        ]
    ]
];
