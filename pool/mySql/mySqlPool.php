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
        $this->create(self::$pool);
    }

    private function getPoolInstance()
    {
        self::$pool = new Pool($this->POOL_SIZE);
    }

    private function create(Pool $pool)
    {
        if (self::$pool == null) {
            //初始化连接池
            $this->getPoolInstance();
        }
        $pool->create(function () use ($pool) {
            $db = new \swoole_mysql;
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

    private function execSql($sql,&$result = array())
    {
        $pool = self::$pool;
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
        $this->execSql($sql);
    }
}