<?php

/**
 * User: Bruce Qin
 * Date: 2016/6/14
 * Time: 16:17
 * 用户身份验证
 */
class User
{
    /*
     * token验证
     * */
    const flag = 'bruce.net.cn';

    public static function token($token = null, $userid = null)
    {
        if ($token && $userid) {
            $user_model = model('User');
            $result = $user_model->gets('user_id = ' . $userid);
            if ($result && $result[0]['token'] == $token) {
                return true;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    /**
     * 密码验证
     * 默认验证方式为sha(username+密码密文+bruce.net.cn)
     *还支持md5()
     */
    public static function password($userid, $password, $enc = 'sha1')
    {
        //加密方法不存在则使用sha1
        if (!function_exists($enc)) {
            $enc = 'sha1';
        }
        $str = $userid . $password . self::flag;
        $real_pass = call_user_func($enc, array($str));
        $user_model = model('User');
        $result = $user_model->gets('user_id = ' . $userid);
        if ($result && $result[0]['password'] == $real_pass) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * 密码修改
     */
    public static function modify_password($userid,$oldpassword,$newpassword)
    {

    }
}