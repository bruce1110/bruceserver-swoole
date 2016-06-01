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
    protected $allUsers;   //保存所有的在线观众
    protected $loginUsers; //保存已经登录的观众
    protected $Directors; //保存登录的直播员信息
    protected $type = array('sendMessage','Auth','Login');

    const MESSAGE_MAX_LEN = 1024; //单条消息不得超过1K
    /*
     * 接收到消息时
     * type:1->直播员，2->观众
     * 直播员进行Auth之后将client_id保存到Director；
     * 观众进行Auth之后将client_id 保存到loginUser；
     * Auth认证消息{"cmd":"Auth","token":"xxx","type":1/2,"id":"111222","uname":"name"}
     * 直播员不需要login，直接进行auth认证；观众进行login之后将client_id保存到allUser；
     * Login消息{"cmd":"Login","type":2,"logintime":"timestamp"}
     *包括观众评论消息和直播员发送的比赛实时消息
     * sendMessage消息{"cmd":"sendMessage","type":1/2,"id":"xxxxx","uname":"name","msg":"xxxxx","sendtime":"timestamp"}
     * */
    function onMessage($client_id, $message)
    {
        // TODO: Implement onMessage() method.
        /*$this->log("onMessage: ".$client_id.' = '.$message['message']);
        $this->send($client_id, 'Server is ok!');*/
        $msg = json_decode($message['message'],true);
        //判断消息是否合法，不合法消息将返回错误信息
        if(! in_array($msg['cmd'],$this->type)||empty($msg['cmd'])||! method_exists($this,'cmd_'.$msg['cmd']))
        {
            $this->sendErrorMessage($client_id,'nukonwm message',101);
            return;
        }
        //处理消息
        $func = 'cmd_'.$msg['cmd'];
        $this->$func($client_id,$msg);
    }
    /*----------------receivedMessage----------*/

    /*----------------sendMessage--------------*/
    /*
     * 发送信息
     *直播员消息格式:
     * */
    function cmd_sendMessage($client_id,$msg)
    {
        //类型判断:1->直播员消息 2->观众评论
        if($msg['type'] ==1)
        {
            if(array_key_exists($client_id,$this->Directors))
            {
                $resmsg=array(
                    "code"=>1,
                    "cmd"=>"WatchMessage",
                    "director"=>$msg['uname'],
                    "sendtime"=>$msg['sendtime'],
                    "type"=>1,
                    "msg"=>$msg['msg']
                );
                $this->broadCast($client_id,$resmsg);
            }
            else
            {
                $this->sendErrorMessage($client_id,'no logined',103);
            }
        }
        if($msg['type']==2)
        {
            if(array_key_exists($client_id,$this->loginUsers))
            {
                $resmsg=array(
                    "code"=>1,
                    "cmd"=>"CommetMessage",
                    "name"=>$msg['uname'],
                    "sendtime"=>$msg['sendtime'],
                    "type"=>2,
                    "msg"=>$msg['msg']
                );
                $this->broadCast($client_id,$resmsg);
            }
            else
            {
                $this->sendErrorMessage($client_id,'no logined',103);
            }
        }
    }
    /**
     * 发送错误信息
     * message format {"code":101,"cmd":"error","msg":"xxxx"}
    */
    function sendErrorMessage($client_id,$msg,$code)
    {
        $this->sendJson($client_id,array('code'=>$code,'cmd'=>'error','msg'=>$msg));
    }
    /*
     * 发送json信息
     * */
    function sendJson($client_id,$array)
    {
        $msg = json_encode($array);
        if ($this->send($client_id, $msg) === false) {
            $this->close($client_id);
        }
    }
    /*
     * 广播发送信息
     * */
    function broadCast($session_id,$msg)
    {
        foreach($this->allUsers as $client_id=>$value)
        {
            if($client_id != $session_id)
            {
                $this->sendJson($client_id, $msg);
            }
        }
    }
    /*---------------------------------END sendMessage-----------------*/
    /*
     * 用户身份认证
     * 暂时不需要---2016-06-01
     * */
    function cmd_Auth($client_id,$msg)
    {
        //类型判断:1->直播员消息 2->观众评论
        if($msg['type']==1)
        {
            $this->Directors[$client_id]=array(
                "code"=>1
            );
        }
        if($msg['type']==2)
        {
            $this->Directors[$client_id]=array(
                "code"=>1
            );
        }
    }
    /*
     * 观众上线
     * 将client_id保存到allusers
     * 并返回登录成功信息
     * */
    function cmd_Login($client_id,$msg)
    {
        $loginsuccess=array(
            "code"=>1,
            "cmd"=>"login",
            "fd"=>$client_id,
            "msg"=>"success",
            "logintime"=>$msg['logintime']
        );
        //保存会话信息
        if(!array_key_exists($client_id,$this->allUsers))
        {
            $this->allUsers[$client_id] = $loginsuccess;
            //返回登录成功信息
            $this->sendJson($client_id, $loginsuccess);
        }
        else
        {
            $this->sendErrorMessage($client_id,"repeat login",102);
        }
    }
}