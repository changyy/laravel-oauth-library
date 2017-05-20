<?php
return [
    'methods' => [
        'facebook' => [
            'tag' => 'facebook',
            'default_graph_version' => 'v2.9',
            'app_id' => '',
            'secret_key' => '',
            'scope' => [ 'email' ],
            'done_handler' => '/',
            'error_handler' => '/connect/error',
        ],
        'google' => [
            'tag' => 'google',
            'client_id' => '',
            'client_secret' => '',
            'scope' => [ 'email', 'profile' ],
            'done_handler' => '/',
            'error_handler' => '/connect/error',
        ],
    ]
];
