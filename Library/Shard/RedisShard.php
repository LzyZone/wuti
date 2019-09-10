<?php
namespace Library\Shard;

use Library\Factory\CacheFactory;

class RedisShard{

    /**
     * 配置,查看example目录下的样例
     * @var Array
     * @example
     *
     */
    private static $shardConfig;

    public static function init($config){
        self::$shardConfig = $config;
    }

    private static function getShardConfig($target_name,$shard_id){
        $host_list = self::$shardConfig[$target_name];
        $number = abs(crc32($shard_id));
        $max = count($host_list);
        if($max == 1){
            return current($host_list);
        }
        $index = $number % $max;

        return $host_list[$index];
    }

    public static function getRedis($target_name,$shard_id){
        $config = self::getShardConfig($target_name,$shard_id);
        $config['db'] = isset($config['db']) ? intval($config['db']) : 0;
        $config['timeout'] = isset($config['timeout']) ? intval($config['timeout']) : 3;
        return CacheFactory::redisInstance($config['host'],$config['port'],
            $config['db'],$config['timeout']);
    }
}