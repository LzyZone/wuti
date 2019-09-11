<?php
namespace WuTi\Library\Shard;

use WuTi\Library\Database\DbMysqli;

class MysqlShard{
    /**
     * @var DbMysqli
     */
    private static $_mysqlInstance;
    private static $shardConfig;

    /**
     * 初始化配置
     * @param $config 查看 example/mysql.config.example.php
     */
    public static function init($config){
        self::$shardConfig = $config;
    }

    /**
     * @param $shard_name
     * @param $table
     * @param $shard_id
     * @param string $master
     * @return DbMysqli mysql
     */
    public static function getMysqlByShard($shard_name,$table,$shard_id,$master=true){
        $config = self::_getConfigByShard($shard_name,$shard_id,$master);
        $mysql = self::getMysql($config);
        return [$mysql,$config['database'].'.'.$table.$config['table_index']];
    }

    /**
     * 获取mysql对象
     * @param $config
     * @return DbMysqli
     */
    public static function getMysql($config){
        $key = implode(',',[
            $config['hostname'],$config['username'],$config['password'],
            $config['port'],$config['database']
        ]);
        $key = md5($key);
        if(isset(self::$_mysqlInstance[$key])){
            return self::$_mysqlInstance[$key];
        }
        self::$_mysqlInstance[$key] = new DbMysqli($config);
        return self::$_mysqlInstance[$key];
    }

    /**
     * 获取数据库配置
     * @param $shard_name 分片名
     * @param $shard_id 分片id
     * @param $master 主从 true=master,false=slave
     * @return array
     */
    private static function _getConfigByShard($shard_name,$shard_id,$master){
        $number = abs(crc32($shard_id));
        $shard_config = self::$shardConfig['config_shard'][$shard_name];
        $databases = $shard_config['databases'];
        $tables = $shard_config['tables'];
        $type = $master ? 'master' : 'slave';
        $shard_host = self::$shardConfig['shard_host'][$shard_name][$type];
        if($databases > 1){
            $index = $number % $databases;
            $config['database'] = $shard_name.$index;
            foreach ($shard_host as $k=>$v){
                if(!is_numeric($k)){
                    list($_start,$_end) = explode('-',$k);
                    if($index >= $_start && $index <= $_end){
                        $config = $v;break;
                    }
                }else if($k == $index){
                    $config = $v;break;
                }
            }
        }else{
            $config = current($shard_host);
            $config['database'] = $shard_name;
        }

        $table_index = '';
        if($tables > 1){
            $table_index .= strval($number % $tables);
        }

        $config['table_index'] = $table_index;
        return $config;
    }

}
