<?php
namespace App\Controller;
use Swoole;

class User extends Swoole\Controller
{
    function login()
    {
        //使用crypt密码
        Swoole\Auth::$password_hash = 'sha1_single';

        $this->session->start();
        //已经登录了，跳转到
        if ($this->user->isLogin())
        {
            $this->http->redirect('/user/home/');
            return;
        }
        if (!empty($_POST['password']))
        {
            $r = $this->user->login(trim($_POST['username']), $_POST['password']);
            if ($r)
            {
                $this->http->redirect('/user/home/');
                return;
            }
            else
            {
                echo "登录失败";
            }
        }
        else
        {
            $this->display('user/login.php');
        }
    }

    function home()
    {
        //$this->tpl->assign('my_var','12344');
        $this->tpl->display('user/home.html');
    }

    function logout()
    {
        $this->session->start();
        $this->user->logout();
    }
}