<?php
namespace App\Controller;
use Swoole;
/**
 * User: Bruce Qin
 * Date: 2016/6/9
 * Time: 16:12
 */
class Director extends Swoole\Controller
{
    function login()
    {
        $r = Model('Director');
        $r->get(1);
        var_dump($r);
    }
}

