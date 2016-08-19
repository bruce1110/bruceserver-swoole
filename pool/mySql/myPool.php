<?php
namespace Pool\mySql;

/**
 * 通用的连接池框架
 * @package Pool\mySql
 */
class Pool
{
    /**
     * 连接池的尺寸，最大连接数
     * @var int $poolSize
     */
    protected $poolSize;

    protected $resourceNum;

    /**
     * 全局资源池
     * @var array $resourcePool
     */
    protected $resourcePool = array();

    /**
     * @var \SplQueue
     * 可以使用的数据库连接池
     */
    protected $idlePool;


    /**
     * @var \SplQueue
     * 待完成的任务池
     */
    protected $taskQueue;

    protected $createFunction;

    /**
     * @param int $poolSize
     * @throws \Exception
     */
    public function __construct($poolSize = 100)
    {
        $this->poolSize = $poolSize;
        //保存回调函数
        $this->taskQueue = new \SplQueue();
        //保存可用的数据库连接
        $this->idlePool = new \SplQueue();
    }

    /**
     * 加入到连接池中
     * @param $resource
     */
    function join($resource)
    {
        //先保存到全局资源池
        $this->resourcePool[spl_object_hash($resource)] = $resource;
        //保存到空闲连接池
        $this->release($resource);
    }

    /**
     * @param $callback
     */
    function create($callback)
    {
        $this->createFunction = $callback;
    }

    /**
     * 移除资源
     * @param $resource
     * @return bool
     */
    function remove($resource)
    {
        $rid = spl_object_hash($resource);
        if (!isset($this->resourcePool[$rid])) {
            return false;
        }

        //重建IdlePool
        $tmpPool = array();
        while (count($this->idlePool) > 0) {
            $_resource = $this->idlePool->dequeue();
            if (spl_object_hash($_resource) == $rid) {
                continue;
            }
            $tmpPool[] = $_resource;
        }
        //添加到空闲队列
        foreach ($tmpPool as $_resource) {
            $this->idlePool->enqueue($_resource);
        }

        $this->resourceNum--;
        unset($rid);
        return true;
    }

    /**
     * 请求资源
     * @param callable $callback
     * @return bool
     */
    public function request(callable $callback)
    {
        //入队列
        $this->taskQueue->enqueue($callback);
        //没有可用的资源, 创建新的连接
        if (count($this->resourcePool) < $this->poolSize && $this->resourceNum < $this->poolSize) {
            $r = call_user_func($this->createFunction);
            if ($r) {
                $this->resourceNum++;
            }
        } //有可用资源
        else if (count($this->idlePool) > 0) {
            $this->doTask();
        }
    }

    /**
     * 释放资源
     * @param $resource
     */
    public function release($resource)
    {
        //加入空闲连接池
        $this->idlePool->enqueue($resource);
        //判断任务池是否有任务
        if (count($this->taskQueue) > 0) {
            $this->doTask();
        }
    }

    protected function doTask()
    {
        //获得数据库连接
        $resource = $this->idlePool->dequeue();
        //获得一个任务
        $callback = $this->taskQueue->dequeue();
        //执行回调任务传入参数为数据库连接
        call_user_func($callback, $resource);
    }
}