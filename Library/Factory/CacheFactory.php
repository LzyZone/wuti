<?php

namespace WuTi\Library\Factory;

use WuTi\Library\Cache\RedisCache;

class CacheFactory{
    /**
     * @var \Redis
     */
    private static $_redis_instance = null;

    /**
     * 初始化redis
     * @param $ip
     * @param $port
     * @param $pwd
     * @param int $db
     * @param int $timeout
     * @return \Redis
     */
    public static function redisInstance($host, $port, $pwd,$db=0,$timeout = 3,$ext=[]){
        $key = md5($host.$port.$pwd.$db);
        if(empty(self::$_redis_instance[$key])){
            echo "redis instance\r\n";
            self::$_redis_instance[$key] = new RedisCache($host,$port,$pwd,$timeout,$db,$ext);
        }
        return self::$_redis_instance[$key];
    }
}

