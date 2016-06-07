<?php
namespace WebIm\store;

use Swoole;

/**
 * User: Bruce Qin
 * Date: 2016/6/3
 * Time: 14:16
 * 使用redis保存历史直播消息
 * 使用lists数据结构
 * 使用matchid作为key来保存数据
 * 插入使用rpush
 */
class Storage
{
    protected $redis = null;
    protected $config = array(
        'host' => "127.0.0.1",
        'port' => 6379,
        'password' => '',
        'timeout' => 0.25,
        'pconnect' => false,
    );

    function __construct(array $config)
    {
        $this->connect($this->config);
    }

    /*
     * 连接redis
     * */
    function connect($config)
    {
        $this->redis = new Swoole\Redis($config);
    }

    /*
     * 插入一条数据
    * 使用方法rpush('key','value')
     * */
    function Put($matchid, $msg)
    {
        if (!$this->redis->rpush($matchid, $msg)) {
            return;
        }
    }

    /**
     *删除一条直播消息
     */
    function Del()
    {

    }

    /**
     * 默认获取后100条记录
     */
    function GetHistory($matchid, $start = 0, $end = 100)
    {
        if ($start > $end) {
            $start = 0;
            $end = 100;
        }
        $r = $this->redis->lrange($matchid, $start, $end);
        if ($r) {
            $resmsg = array(
                "length" => count($r),
                "history" => $r,
                "matchid" => $matchid
            );
            return $resmsg;
        } else {
            return NULL;
        }
    }
}