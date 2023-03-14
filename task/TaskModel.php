<?php

//mysql模型

namespace module\task;

use module\lib\MysqliClient;
use module\models\TaskLogModel;

abstract class TaskModel implements Task
{

    const TYPE_PULL_ORDER = 'pullOrder';
    const TYPE_CHECK_ORDER = 'checkOrder';
    const TYPE_CHECK_EXCEPTION = 'checkException';

    /**
     * @var string
     */
    protected $taskType;
    /**
     * @var string
     */
    protected $type = '';
    /**
     * @var MysqliClient
     */
    protected $mysqlClient;
    /**
     * @var \module\lib\MysqliDb
     */
    protected $query;
    /**
     * @var bool
     */
    protected $isUsePool = false;
    /**
     * @var \mysqli
     */
    protected $poolObject;
    /**
     * @var string
     */
    protected $params;


    /**
     * TaskModel constructor.
     * @param null $poolObject
     */
    public function __construct($poolObject = null)
    {
        if ($poolObject !== null) {
            $this->isUsePool = true;
            $this->poolObject = $poolObject;
        } else {
            $this->mysqlClient = new MysqliClient();
            $this->query = $this->mysqlClient->getQuery();
        }
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType(string $type)
    {
        $this->type = $type;
        return $this;
    }


    /**
     * @return string
     */
    public function getTaskType()
    {
        return $this->taskType;
    }

    /**
     * @param string $taskType
     * @return $this
     */
    public function setTaskType(string $taskType)
    {
        $this->taskType = $taskType;
        return $this;
    }

    /**
     * @return string
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param $params
     * @return $this
     */
    public function setParams($params)
    {
        $this->params = $params;
        return $this;
    }

    //初始化任务，获取mysql表账号，插入mongo任务表
    public function initTask()
    {
        $accountList = $this->getAccountList();
        $taskLogModel = TaskLogModel::model();
        $documents = [];
        foreach ($accountList as $account) {
            $documents[] = [
                'task_type' => $this->taskType,
                'type' => $this->type,
                'account_id' => $account['id'],
                'account_name' => $account['account_s_name'],
                'status' => TaskLogModel::STATUS_INIT,
                'create_start_time' => '',
                'create_end_time' => '',
                'update_start_time' => '',
                'update_end_time' => '',
                'execute_time' => '',
                'request' => '',
                'response' => '',
                'create_time' => '',
                'update_time' => '',
                'account_data' => $account,
            ];
            if (count($documents) === 100) {
                $count = $taskLogModel->insertMany($documents);
                $documents = [];
            }
        }
    }

    //获取mongo执行任务
    public function getTasks($params)
    {
        $taskLogModel = TaskLogModel::model();
        $taskList = $taskLogModel->findMany(
            ['task_type' => $this->taskType, 'status' => TaskLogModel::STATUS_INIT],
            ['limit' => $params['limit']]
        );
        return $taskList;
    }

    public function taskRun($params)
    {
        if ($this->type == self::TYPE_PULL_ORDER) {
            $this->pullOrder($params);
        } else if ($this->type == self::TYPE_PULL_ORDER) {
            $this->checkOrder($params);
        } else if ($this->type == self::TYPE_PULL_ORDER) {
            $this->checkException($params);
        } else {
            throw new \Exception('not support:' . $this->type);
        }
    }

    //关闭mysql短连接
    public function __destruct()
    {
        if ($this->isUsePool) {
            //不断开连接，直接归还连接池
//            if (method_exists($this->poolObject, 'close')) {
//                $this->poolObject->close();   //mysqli
//            }

//            $this->poolObject = null;     //pdo
        } else {
            //MysqliDb
            if (method_exists($this->query, 'disconnect')) {
                $this->query->disconnect();
            }
            //\mysqli
            if (method_exists($this->query, 'close')) {
                $this->query->close();
            }
            $this->query = null;
            $this->mysqlClient = null;
        }
    }

}