<?php

require_once __DIR__.'/vendor/autoload.php';
$host = '127.0.0.1';
$port = 6379;
$pwd = '';
$redis = \WuTi\Library\Factory\CacheFactory::redisInstance($host,$port,$pwd);
$info = $redis->info();
var_dump($info);