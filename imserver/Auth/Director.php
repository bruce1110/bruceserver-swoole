<?php
namespace WebIm\Auth;
/**
 * User: Bruce Qin
 * Date: 2016/6/13
 * Time: 17:27
 * 直播员认证类
 */
class Director
{
    public static $rpc = null;

    public static function Auth($token=null, $directorid=null)
    {
        if (!self::$rpc) {
            self::$rpc = \WebIm\Rpc\Client::connect();
        }
        $result = self::$rpc->task('SoaServer\Auth\Director::token', array($token, $directorid));
        if (self::$rpc->wait(0.5) == 1 && $result->data) {
            return true;
        } else {
            return false;
        }
    }
}