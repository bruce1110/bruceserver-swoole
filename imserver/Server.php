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
    protected $type = array('sendMessage','Auth','Login');

    const MESSAGE_MAX_LEN = 1024; //单条消息不得超过1K
    /*
     * 接收到消息时
     * type:1->直播员，2->观众
     * 直播员进行Auth之后将client_id保存到Director；
     * 观众进行Auth之后将client_id 保存到loginUser；
     * Auth认证消息{"cmd":"Auth","token":"xxx","type":1/2,"userid/directorid":"1234","matchid":"1234","uname":"name","authtime":"xxxxx"}
     * 直播员不需要login，直接进行auth认证；观众进行login之后将client_id保存到allUser；
     * Login类型消息{"cmd":"Login","type":2,"matchid":"1234","logintime":"timestamp"}
     *包括观众评论消息和直播员发送的比赛实时消息;
     * 直播员发送的比赛消息需要保存，而评论消息不需要保存
     * sendMessage类型消息{"cmd":"sendMessage","type":1/2,"userid/directorid":"xxxxx","uname/directorname":"name","matchid":"1234","msg":"xxxxx","sendtime":"timestamp"}
* */
    function onMessage($client_id, $message)
    {
        // TODO: Implement onMessage() method.
        $this->log("onMessage: ".$client_id.' = '.$message['message']);
        //$this->send($client_id, 'Server is ok!');
        $msg = json_decode($message['message'],true);
        //判断消息是否合法，不合法消息将返回错误信息
        if(! in_array($msg['cmd'],$this->type)||empty($msg['cmd'])||! method_exists($this,'cmd_'.$msg['cmd']))
        {
            $this->sendErrorMessage($client_id,'unrecognized message',101);
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
     * {"code":1,"cmd":"MatchMessage/ReplyComment","directorid":"directorid","directorname":"directorname","type":1,"msg":"msg","sendtime":"sendtime","matchid":"matchid"}
     *评论消息格式：
     * {"code":1,"cmd":"CommetMessage","userid":"userid","name":"uname","type":2,"msg":"msg","sendtime":"sendtime","matchid":"matchid"}
     *  */
    function cmd_sendMessage($client_id,$msg)
    {
        //类型判断:1->直播员消息 2->观众评论
        if($msg['type'] ==1)
        {
            if(array_key_exists($client_id,$this->Directors)&&array_key_exists($msg['matchid'],$this->Directors[$client_id]))
            {
                $resmsg=array(
                    "code"=>1,
                    "cmd"=>$msg['cmd'],
                    "directorid"=>$msg['directorid'],
                    "directorname"=>$msg['directorname'],
                    "sendtime"=>$msg['sendtime'],
                    "type"=>1,
                    "msg"=>$msg['msg'],
                    "matchid"=>$msg['matchid']
                );
                $this->broadCast($client_id,$resmsg);
            }
            else
            {
                $this->sendErrorMessage($client_id,'no logined',103);
            }
        }
        elseif($msg['type']==2)
        {
            if(array_key_exists($client_id,$this->loginUsers)&&array_key_exists($msg['matchid'],$this->loginUsers[$client_id]))
            {
                $resmsg=array(
                    "code"=>1,
                    "cmd"=>$msg['cmd'],
                    "userid"=>$msg['userid'],
                    "name"=>$msg['uname'],
                    "sendtime"=>$msg['sendtime'],
                    "type"=>2,
                    "msg"=>$msg['msg'],
                    "matchid"=>$msg['matchid']
                );
                $this->broadCast($client_id,$resmsg);
            }
            else
            {
                $this->sendErrorMessage($client_id,'no logined',103);
            }
        }
        else
        {
            $this->sendErrorMessage($client_id,'unrecognized message type',104);
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
     *
     * 暂时没有实现认证逻辑---2016-06-01
     * */
    function cmd_Auth($client_id,$msg)
    {
        //类型判断:1->直播员消息 2->观众评论
        if($msg['type']==1)
        {
            $auth=array(
                "type"=>1,
                "directorid"=>$msg['directorid'],
                "matchid"=>$msg['matchid'],
                "authtime"=>$msg['authtime']
            );
            $resmsg=array(
                "code"=>1,
                "status"=>"success"
            );
            $this->Directors[$client_id]=$auth;
            //还需要保存直播员信息到allusers以便能接收消息
            $this->allUsers[$client_id]=$auth;
            $this->sendJson($client_id,$resmsg);
        }
        elseif($msg['type']==2)
        {
            $auth=array(
                "type"=>2,
                "uid"=>$msg['userid'],
                "matchid"=>$msg['matchid'],
                "authtime"=>$msg['authtime']
            );
            $resmsg=array(
                "code"=>1,
                "status"=>"success"
            );
            $this->loginUsers[$client_id]=$auth;
            $this->sendJson($client_id,$resmsg);
        }
        else
        {
            $this->sendErrorMessage($client_id,'nuknown message type',104);
        }
    }
    /*
     * 观众上线
     * 将client_id作为key保存到allusers
     * {"uid":"userid","matchid":"xxx","logintime":"xxxxx"}
     * 并返回登录成功信息
     * */
    function cmd_Login($client_id,$msg)
    {
        $loginsuccess=array(
            "uid"=>$msg['userid'],
            "matchid"=>$msg['matchid'],
            "logintime"=>$msg['logintime']
        );
        $resok=array(
            "code"=>1,
            "msg"=>"login ok!"
        );
        //保存会话信息
        //规则client_id@matchid
        if(!array_key_exists($client_id,$this->allUsers))
        {
            $this->allUsers[$client_id] = $loginsuccess;
            //返回登录成功信息
            $this->sendJson($client_id, $resok);
        }
        else
        {
            $this->sendErrorMessage($client_id,"repeat login",102);
        }
    }
    /*
     * 获得当前比赛的在线人数
     * */
    function cmd_GetNumbers($client_id,$msg)
    {

    }
}