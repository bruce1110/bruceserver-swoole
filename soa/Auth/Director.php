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
    public static function token($token, $directorid)
    {
        if ($token && $directorid) {
            $direc_model = model('Director');
            $result = $direc_model->gets('director_id = ' . $directorid);
            if ($result && $result[0]['token'] == $token) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}