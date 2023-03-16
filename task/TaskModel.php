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

    const CODE_SUCCESS = 1;
    const CODE_FAIL = 0;

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
     * @var int
     */
    protected $taskWorkerNum;


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
     * @param $taskWorkerNum
     * @return $this
     */
    public function setTaskWorkerNum($taskWorkerNum)
    {
        $this->taskWorkerNum = (int)$taskWorkerNum;
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
                'account_name' => $account['account_name'],
                'status' => TaskLogModel::STATUS_INIT,
                'create_start_time' => '',
                'create_end_time' => '',
                'update_start_time' => '',
                'update_end_time' => '',
                'execute_time' => '',
                'request' => '',
                'response' => '',
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => '',
                'account_data' => $account,
            ];
            if (count($documents) === 100) {
                $taskLogModel->insertMany($documents);
                $documents = [];
            }
        }
        if (!empty($documents)) {
            $taskLogModel->insertMany($documents);
        }
        return true;
    }

    //查找要执行任务的账号，从数据库获取或者接口获取所有账号
    public function getAccountList()
    {
        return [];
    }

    //获取mongo执行任务，第一次投递taskNum个任务
    public function getTasks($params)
    {
        $taskLogModel = TaskLogModel::model();
        $taskList = $taskLogModel->findMany(
            ['task_type' => $this->taskType, 'status' => TaskLogModel::STATUS_INIT],
            ['limit' => $this->taskWorkerNum]
        );
        return $taskList;
    }

    //获取下一个任务
    public function getNextTask()
    {
        $taskLogModel = TaskLogModel::model();
        $task = $taskLogModel->findOne(
            ['task_type' => $this->taskType, 'status' => TaskLogModel::STATUS_INIT],
            ['limit' => 1]
        );
        return $task;
    }

    //更新任务状态
    public function updateTaskStatus($_id, $status)
    {
        $taskLogModel = TaskLogModel::model();
        $task = $taskLogModel->updateOne(
            ['_id' => mongoObjectId($_id)],
            ['status' => $status]
        );
        return $task;
    }

    public function taskRun($params)
    {
        if (!method_exists($this, $this->type)) {
            throw new \Exception('Task method not exist:' . $this->type);
        }
        return $this->{$this->type}($params);
    }

    //关闭mysql短连接
    public function __destruct()
    {
        if ($this->isUsePool) {
            //不断开连接，直接归还连接池
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