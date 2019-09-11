<?php
namespace WuTi\Library\Shard;

use WuTi\Library\Cache\RedisCache;

class RedisShard{

    /**
     * 配置,查看example目录下的样例
     * @var Array
     * @example
     *
     */
    private static $shardConfig;

    /**
     * @var \Redis
     */
    private static $_redis_instance = null;

    /**
     * @param $config 初始化配置,example/redis.config.example.php
     */
    public static function init($config){
        self::$shardConfig = $config;
    }

    /**
     * 获取redis对象
     * @param $shard_name
     * @param $shard_id
     * @return \Redis
     */
    public static function getRedisByShard($shard_name,$shard_id){
        $config = self::getShardConfig($shard_name,$shard_id);
        $config['db'] = isset($config['db']) ? intval($config['db']) : 0;
        $config['timeout'] = isset($config['timeout']) ? intval($config['timeout']) : 3;
        $config['ext'] = empty($config['ext']) ? [] : $config['ext'];
        return self::_redisInstance($config['host'],$config['port'],$config['pwd'],
            $config['db'],$config['timeout'],$config['ext']);
    }

    /**
     * 获取redis对象
     * @param $host
     * @param $port
     * @param $pwd
     * @param int $db
     * @param int $timeout
     * @param array $ext
     * @return mixed
     */
    private static function _redisInstance($host, $port, $pwd,$db=0,$timeout = 3,$ext=[]){
        $key = md5($host.$port.$pwd.$db);
        if(empty(self::$_redis_instance[$key])){
            echo "redis instance\r\n";
            self::$_redis_instance[$key] = new RedisCache($host,$port,$pwd,$timeout,$db,$ext);
        }
        return self::$_redis_instance[$key];
    }

    /**
     * 获取redis连接配置
     * @param $shard_name
     * @param $shard_id
     * @return array
     */
    private static function getShardConfig($shard_name,$shard_id){
        $host_list = self::$shardConfig[$shard_name];
        $max = count($host_list);
        if($max == 1){
            return current($host_list);
        }
        $number = abs(crc32($shard_id));
        $index = $number % $max;

        return $host_list[$index];
    }
}