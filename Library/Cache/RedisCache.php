<?php

namespace Library\Cache;

/**
 * @var \Redis
 * @notes       : redis操作类，按需连接
 * @author      : gary.Lee<321539047@qq.com>
 * @create time : 2019-09-10 16:02
 * @details     :
 * @package Library\Cache
 * @return \Redis
 */
class RedisCache
{
    /**
     * @var \Redis
     */
    private $redis = null;
    private $conn = null;
    /**
     * @var array [ip,port,timeout,pwd,serializer,db]
     */
    private $config = [];

    /**
     * RedisCache constructor.
     * @param $ip
     * @param $port
     * @param $pwd
     * @param int $timeout
     * @param int $db
     * @param bool $serializer
     */
    public function __construct($ip, $port, $pwd, $timeout = 3,$db=0,$ext=[])
    {
        $this->config['ip']     = $ip;
        $this->config['port']   = intval($port);
        $this->config['timeout'] = $timeout;
        $this->config['pwd'] = $pwd;
        $this->config['db'] = intval($db);
        !empty($ext) && $this->config = array_merge($this->config,$ext);
    }

    /**
     * 连接redis
     * @throws \RedisException
     */
    public function connection(){
        $i = 2;
        while ($i > 0){
            $i--;
            echo "i=={$i}\r\n";
            try{
                if($this->conn && method_exists($this->redis,'ping') && $this->redis->ping()){
                    return true;
                }

                $config = $this->config;
                $this->redis = new \Redis();

                $conn = $this->redis->connect($config['ip'],$config['port'],$config['timeout']);
                if(!$conn){
                    throw new \RedisException("redis connection fail,config:".json_encode($config));
                }
                if($config['pwd']){
                    $this->redis->auth($config['pwd']);
                }
                if($config['db'])$this->redis->select($config['db']);

                $this->redis->ping();
                $this->conn = true;
                return true;
            }catch (\RedisException $ex){
                $this->redis = null;
                $this->conn = null;
                if($i == 0)throw new \RedisException($ex->getMessage());
            }
        }
        return false;
    }

    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
        $this->connection();
        return call_user_func_array([$this->redis,$name],$arguments);
    }

}