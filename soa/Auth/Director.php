<?php
namespace SoaServer\Auth;
    /**
     * User: Bruce Qin
     * Date: 2016/6/10
     * Time: 17:55
     */
/**
 * 判断token是否合法
 */
class Director
{
    public static function token()
    {
        global $php;
        var_dump($php->config['db']['master']);
        return true;
    }
}