<?php
return [
    'config_shard' => [
        'user' => [
            'databases' => 1,
            'tables'    => 1
        ],
        'game' => [
            'databases'     => 10,
            'tables'        => 10
        ]
    ],
    'shard_host' => [
        'user' => [
            'master' => [
                '0-0' => [
                    'hostname'      => '127.0.0.1',
                    'username'      => 'root',
                    'password'  => '',
                    'port'      => 3306,
                    'charset'   => 'utf8'
                ]
            ],
            'slave'  => [

            ]
        ],
        'game' => [
            'master' => [
                '0-4' => [
                    'host'      => '127.0.0.1',
                    'user'      => 'root',
                    'password'  => '',
                    'port'      => 3306,
                    'charset'   => 'utf8'
                ],
                '5-9' => [
                    'host'      => '127.0.0.1',
                    'user'      => 'root',
                    'password'  => '',
                    'port'      => 3306,
                    'charset'   => 'utf8'
                ]
            ],
            'slave'  => [

            ]
        ]
    ]
];
