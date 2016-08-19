<?php
namespace Pool\mySql;

/**
 * ͨ�õ����ӳؿ��
 * @package Pool\mySql
 */
class Pool
{
    /**
     * ���ӳصĳߴ磬���������
     * @var int $poolSize
     */
    protected $poolSize;

    protected $resourceNum;

    /**
     * ȫ����Դ��
     * @var array $resourcePool
     */
    protected $resourcePool = array();

    /**
     * @var \SplQueue
     * ����ʹ�õ����ݿ����ӳ�
     */
    protected $idlePool;


    /**
     * @var \SplQueue
     * ����ɵ������
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
        //����ص�����
        $this->taskQueue = new \SplQueue();
        //������õ����ݿ�����
        $this->idlePool = new \SplQueue();
    }

    /**
     * ���뵽���ӳ���
     * @param $resource
     */
    function join($resource)
    {
        //�ȱ��浽ȫ����Դ��
        $this->resourcePool[spl_object_hash($resource)] = $resource;
        //���浽�������ӳ�
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
     * �Ƴ���Դ
     * @param $resource
     * @return bool
     */
    function remove($resource)
    {
        $rid = spl_object_hash($resource);
        if (!isset($this->resourcePool[$rid])) {
            return false;
        }

        //�ؽ�IdlePool
        $tmpPool = array();
        while (count($this->idlePool) > 0) {
            $_resource = $this->idlePool->dequeue();
            if (spl_object_hash($_resource) == $rid) {
                continue;
            }
            $tmpPool[] = $_resource;
        }
        //��ӵ����ж���
        foreach ($tmpPool as $_resource) {
            $this->idlePool->enqueue($_resource);
        }

        $this->resourceNum--;
        unset($rid);
        return true;
    }

    /**
     * ������Դ
     * @param callable $callback
     * @return bool
     */
    public function request(callable $callback)
    {
        //�����
        $this->taskQueue->enqueue($callback);
        //û�п��õ���Դ, �����µ�����
        if (count($this->resourcePool) < $this->poolSize && $this->resourceNum < $this->poolSize) {
            $r = call_user_func($this->createFunction);
            if ($r) {
                $this->resourceNum++;
            }
        } //�п�����Դ
        else if (count($this->idlePool) > 0) {
            $this->doTask();
        }
    }

    /**
     * �ͷ���Դ
     * @param $resource
     */
    public function release($resource)
    {
        //����������ӳ�
        $this->idlePool->enqueue($resource);
        //�ж�������Ƿ�������
        if (count($this->taskQueue) > 0) {
            $this->doTask();
        }
    }

    protected function doTask()
    {
        //������ݿ�����
        $resource = $this->idlePool->dequeue();
        //���һ������
        $callback = $this->taskQueue->dequeue();
        //ִ�лص����������Ϊ���ݿ�����
        call_user_func($callback, $resource);
    }
}