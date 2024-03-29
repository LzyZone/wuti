<?php
require_once __DIR__.'/vendor/autoload.php';
/**
 * this is a example
 */
$redis_config = [
    'redis_ios' => [
        [
            'host' => '127.0.0.1',
            'port' => 6379,
            "password" => '',
        ],
        [
            'host' => '127.0.0.1',
            'port' => 6379,
            "password" => '',
        ]
    ],
    'redis_android' => [
        [
            'host' => '127.0.0.1',
            'port' => 6379,
            "password" => '',
        ]
    ],
];

\WuTi\Library\Shard\RedisShard::init($redis_config);
$redis = \WuTi\Library\Shard\RedisShard::getRedisByShard('redis_ios',0);
$info = $redis->info();
var_dump($info);

$db_config = [
    'config_shard' => [
        'devices' => [
            'databases' => 1,
            'tables'    => 1
        ],
        'game' => [
            'databases'     => 10,
            'tables'        => 10
        ]
    ],
    'shard_host' => [
        'devices' => [
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

\WuTi\Library\Shard\MysqlShard::init($db_config);
list($mysql,$table) = \WuTi\Library\Shard\MysqlShard::getMysqlByShard('devices','device_flow',0);

$data = $mysql->select('*',$table,'',1);
var_dump($data);