<?php
namespace Pool\mySql;

/**
 * Created by PhpStorm.
 * User: qinchong
 * Date: 2016/8/19
 * Time: 17:17
 */
class mySqlPool
{
    /**
     * @var Pool
     */
    private static $pool = null;
    private $POOL_SIZE = 10;

    public function __construct($size)
    {
        $this->POOL_SIZE = $size;
    }

    private function getPoolInstance()
    {
        self::$pool = new Pool($this->POOL_SIZE);
    }

    private function create(Pool $pool)
    {
        $pool->create(function () use ($pool) {
            $db = new swoole_mysql;
            $server = array(
                'host' => '10.1.20.84',
                'user' => 'bruce',
                'password' => '123456',
                'database' => 'soap',
            );
            $db->on('close', function ($db) use ($pool) {
                $pool->remove($db);
            });
            return $db->connect($server, function ($db, $result) use ($pool) {
                $pool->join($db);
            });
        });
    }

    private function execSql($sql, Pool $pool, &$result = array())
    {
        $pool->request(function ($db) use ($pool, $sql, $result) {
            $r = $db->query($sql, function (swoole_mysql $db, $r) use ($pool, $result) {
                if (empty($r) == false) {
                    $result = $r;
                }
                //释放资源，将连接放入可使用队列
                $pool->release($db);
            });
            if ($r == false) {
                die("xxx\n");
            }
        });
    }

    public function query($sql, $transaction = false)
    {
        if (self::$pool == null) {
            //初始化连接池
            $this->getPoolInstance();
            //传入连接池的创建实例方法
            $this->create(self::$pool);
        }
        //执行查询
        if ($transaction) {
            $result = array();
            $this->execSql($sql, self::$pool, $result);
            return $result;
        }
    }
}