<?php
/**
 * User: Bruce Qin
 * Date: 2016/5/31
 * Time: 14:25
 */
namespace WebIm;

use Swoole;

/*
 *服务器主要实现的功能：
 * 1.接收直播员的比赛直播信息并广播给所有的在线观众；
 * 2.接收所有的比赛评论信息并广播给观众；
 * 3.需要验证直播员的身份；
 * 4.需要验证发布评论的观众身份信息；
 * */

class Server extends Swoole\Protocol\WebSocket
{
    /*以后可以考虑将这些信息保存到redis中，以增加服务器的并发数*/
    protected $allUsers = array();   //保存所有的在线观众
    protected $loginUsers = array(); //保存已经登录的观众
    protected $Directors = array(); //保存登录的直播员信息
    protected $type = array('sendMessage', 'Auth', 'Login', 'GetNumbers', 'CloseMatch');
    protected $store = null;
    protected $redis = array();

    const MESSAGE_MAX_LEN = 1024; //单条消息不得超过1K

    /*
     * 接收到消息时
     * type:1->直播员，2->观众
     * 直播员进行Auth之后将client_id保存到Director；
     * 观众进行Auth之后将client_id 保存到loginUser；
     * Auth认证消息{"cmd":"Auth","token":"xxx","type":1/2,"userid/directorid":"1234","matchid":"1234","uname":"name","authtime":"xxxxx"}
     *
     * 直播员不需要login，直接进行auth认证；观众进行login之后将client_id保存到allUser；
     * Login类型消息{"cmd":"Login","type":2,"matchid":"1234","logintime":"timestamp"}
     *
     *包括观众评论消息和直播员发送的比赛实时消息;
     * 直播员发送的比赛消息需要保存，而评论消息不需要保存
     * sendMessage类型消息{"cmd":"sendMessage","type":1/2,"userid/directorid":"xxxxx","uname/directorname":"name","matchid":"1234","msg":"xxxxx","sendtime":"timestamp"}
     *
     * 获取单场比赛在线人数
     * GetNumbers类型消息{"cmd":"GetNumbers","sendtime":"xxxxx","matchid":"xxxxx"}
     * */

    /*
     * 生成保存数据的对象
     * */
    function __construct(array $config)
    {
        $this->store = new \WebIm\store\Storage($this->redis);
        parent::__construct($config);
    }

    function onMessage($client_id, $message)
    {
        // TODO: Implement onMessage() method.
        $this->log("onMessage: " . $client_id . ' = ' . $message['message']);
        //$this->send($client_id, 'Server is ok!');
        $msg = json_decode($message['message'], true);
        //判断消息是否合法，不合法消息将返回错误信息
        if (!in_array($msg['cmd'], $this->type) || empty($msg['cmd']) || !method_exists($this, 'cmd_' . $msg['cmd'])) {
            $this->sendErrorMessage($client_id, \WebIm\error\WsErr::E101);
            return;
        }
        //处理消息
        $func = 'cmd_' . $msg['cmd'];
        $this->$func($client_id, $msg);
    }

    /*
     * 关闭连接并清空会话信息
     * */
    function onExit($client_id)
    {
        unset($this->allUsers[$client_id]);
        if (array_key_exists($client_id, $this->loginUsers)) {
            unset($this->loginUsers[$client_id]);
        }
        if (array_key_exists($client_id, $this->Directors)) {
            unset($this->Directors[$client_id]);
        }
        //关闭连接
        $this->close($client_id);
    }
    /*----------------receivedMessage----------*/

    /*----------------sendMessage--------------*/
    /*
     * 发送信息
     *直播员消息格式:
     * {"code":1,"cmd":"MatchMessage/ReplyComment","directorid":"directorid","directorname":"directorname","type":1,"msg":"msg","sendtime":"sendtime","matchid":"matchid"}
     *评论消息格式：
     * {"code":1,"cmd":"CommetMessage","userid":"userid","name":"uname","type":2,"msg":"msg","sendtime":"sendtime","matchid":"matchid"}
     *  */
    function cmd_sendMessage($client_id, $msg)
    {
        //判断消息最大长度
        if (strlen($msg['msg']) > self::MESSAGE_MAX_LEN) {
            $this->sendErrorMessage($client_id, \WebIm\error\WsErr::E106);
            return;
        }
        //类型判断:1->直播员消息 2->观众评论
        if ($msg['type'] == 1) {
            if (array_key_exists($client_id, $this->Directors) && $this->Directors[$client_id]['matchid'] == $msg['matchid']) {
                $resmsg = array(
                    "code" => 1,
                    "cmd" => $msg['cmd'],
                    "directorid" => $msg['directorid'],
                    "directorname" => $msg['directorname'],
                    "sendtime" => $msg['sendtime'],
                    "type" => 1,
                    "msg" => $msg['msg'],
                    "matchid" => $msg['matchid']
                );
                //保存消息到redis
                if ($this->store) {
                    $this->store->Put($msg['matchid'], json_encode($resmsg));
                }
                $this->broadCast($client_id, $resmsg);
            } else {
                $this->sendErrorMessage($client_id, \WebIm\error\WsErr::E103);
                return;
            }
        } elseif ($msg['type'] == 2) {
            if (array_key_exists($client_id, $this->loginUsers) && $this->loginUsers[$client_id]['matchid'] == $msg['matchid']) {
                $resmsg = array(
                    "code" => 1,
                    "cmd" => $msg['cmd'],
                    "userid" => $msg['userid'],
                    "name" => $msg['uname'],
                    "sendtime" => $msg['sendtime'],
                    "type" => 2,
                    "msg" => $msg['msg'],
                    "matchid" => $msg['matchid']
                );
                $this->broadCast($client_id, $resmsg);
            } else {
                $this->sendErrorMessage($client_id, \WebIm\error\WsErr::E103);
                return;
            }
        } else {
            $this->sendErrorMessage($client_id, \WebIm\error\WsErr::E104);
            return;
        }
    }

    /**
     * 发送错误信息
     * message format {"code":101,"msg":"xxxx"}
     */
    function sendErrorMessage($client_id, $array)
    {
        if ($this->send($client_id, json_encode($array)) === false) {
            $this->close($client_id);
        }
    }

    /*
     * 发送json信息
     * */
    function sendJson($client_id, $array)
    {
        if ($this->send($client_id, json_encode($array)) === false) {
            $this->close($client_id);
        }
    }

    /*
     * 发送一场比赛的广播信息
     * */
    function broadCast($session_id, $msg)
    {
        foreach ($this->allUsers as $client_id => $value) {
            if ($client_id != $session_id && $this->allUsers[$client_id]['matchid'] == $msg['matchid']) {
                $this->sendJson($client_id, $msg);
            }
        }
    }
    /*---------------------------------END sendMessage-----------------*/
    /*
     * 用户身份认证
     *
     * 暂时没有实现认证逻辑---2016-06-01
     * */
    function cmd_Auth($client_id, $msg)
    {
        //类型判断:1->直播员消息 2->观众评论
        if ($msg['type'] == 1) {
            //防止一个socket连接中重复授权
            if (!array_key_exists($client_id, $this->Directors) && !$this->allUsers[$client_id]) {
                $auth = array(
                    "type" => 1,
                    "directorid" => $msg['directorid'],
                    "matchid" => $msg['matchid'],
                    "authtime" => $msg['authtime']
                );
                $this->Directors[$client_id] = $auth;
                //还需要保存直播员信息到allusers以便能接收消息
                $this->allUsers[$client_id] = $auth;
                $this->sendJson($client_id, \WebIm\error\WsErr::E100);
            } else {
                $this->sendErrorMessage($client_id, \WebIm\error\WsErr::E105);
                return;
            }
        } elseif ($msg['type'] == 2) {
            if (!array_key_exists($client_id, $this->loginUsers[$client_id])) {
                $auth = array(
                    "type" => 2,
                    "uid" => $msg['userid'],
                    "matchid" => $msg['matchid'],
                    "authtime" => $msg['authtime']
                );
                $this->loginUsers[$client_id] = $auth;
                $this->sendJson($client_id, \WebIm\error\WsErr::E100);
            } else {
                $this->sendErrorMessage($client_id, \WebIm\error\WsErr::E105);
                return;
            }
        } else {
            $this->sendErrorMessage($client_id, \WebIm\error\WsErr::E104);
            return;
        }
    }

    /*
     * 观众上线
     * 将client_id作为key保存到allusers
     * {"uid":"userid","matchid":"xxx","logintime":"xxxxx"}
     * 并返回登录成功信息
     * */
    function cmd_Login($client_id, $msg)
    {
        //保存会话信息
        //防止重复login请求
        if (!array_key_exists($client_id, $this->allUsers)) {
            $loginsuccess = array(
                "uid" => $msg['userid'],
                "matchid" => $msg['matchid'],
                "logintime" => $msg['logintime']
            );
            $this->allUsers[$client_id] = $loginsuccess;
            //返回登录成功信息
            $this->sendJson($client_id, \WebIm\error\WsErr::E100);
        } else {
            $this->sendErrorMessage($client_id, \WebIm\error\WsErr::E102);
            return;
        }
    }

    /*
     * 获得当前比赛的在线人数
     * 返回消息类型
     * {"cmd":"GetNumbers","numbers":"111","sendtime":"xxxxx"}
     * */
    function cmd_GetNumbers($client_id, $msg)
    {
        if (array_key_exists($client_id, $this->allUsers) && $this->allUsers[$client_id]['matchid'] == $msg['matchid']) {
            $numbers = 0;
            foreach ($this->allUsers as $client => $k) {
                if ($this->allUsers[$client]['matchid'] == $msg['matchid']) {
                    $numbers++;
                }
            }
            $resmsg = array(
                "cmd" => $msg['cmd'],
                "numbers" => $numbers,
                "sendtime" => $msg['sendtime']
            );
            $this->sendJson($client_id, $resmsg);
        } else {
            $this->sendErrorMessage($client_id, \WebIm\error\WsErr::E103);
            return;
        }
    }

    /*
     * 关闭比赛直播
     * */
    function cmd_CloseMatch($client_id, $msg)
    {

    }
}