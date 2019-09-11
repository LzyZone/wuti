<?php
namespace WuTi\Library\Common;

/**
 * @notes       : 通用函数
 * @author      : gary.Lee<321539047@qq.com>
 * @create time : 2019-09-10 17:00
 * @details     :
 * @package WuTi\Library\Common
 */
class functions {
    public static function makeToken($len=8,$type=2){
        $str = [
            '0123456789',
            'abcdefghijklmnopqrstuvwxyz',
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            '~!@#$%^&*()_+'
        ];
        $token = '';
        $string = '';
        for ($i=0;$i<=$type;$i++){
            $string .= $str[$i];
        }
        $string_len = strlen($string)-1;
        for ($i=0;$i<$len;$i++){
            $start = mt_rand(0,$string_len);
            $token .= substr($string,$start,1);
        }
        return $token;
    }
}
