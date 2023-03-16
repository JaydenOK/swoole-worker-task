<?php
/**
 * @author https://github.com/JaydenOK
 */

namespace module\server;

use Exception;
use InvalidArgumentException;
use module\models\ServerConfigModel;
use module\models\TaskLogModel;
use module\task\TaskFactory;
use module\task\TaskModel;
use Swoole\Atomic;
use Swoole\Coroutine\Client;
use Swoole\Process;
use Swoole\Server;
use Swoole\Table;
use function Swoole\Coroutine\run;

class WorkerTaskManager
{

    const EVENT_START = 'start';
    const EVENT_MANAGER_START = 'managerStart';
    const EVENT_WORKER_START = 'workerStart';
    const EVENT_WORKER_STOP = 'workerStop';
    const EVENT_RECEIVE = 'receive';
    const EVENT_TASK = 'task';
    const EVENT_FINISH = 'finish';

    const STATUS_INIT = 0;      //初始化
    const STATUS_RUNNING = 1;   //运行中
    const STATUS_SUCCESS = 2;   //成功
    const STATUS_FAIL = 3;     //失败
    const TASK_TIME_OUT = 10 * 60;   //task任务超时时间(秒)

    /**
     * @var Server
     */
    protected $server;
    /**
     * @var string
     */
    private $taskType;
    /**
     * @var string
     */
    private $host = "0.0.0.0";
    /**
     * @var int|string
     */
    private $port;
    private $processPrefix = 'task-';
    private $setting = [
        'enable_coroutine' => true,
        'worker_num' => 1,
        'task_worker_num' => 10,
        //一个 worker 进程在处理完超过此数值的任务后将自动退出，进程退出后会释放所有内存和资源
        'max_request' => 1000000,
        //最大连接数，适当设置，提高并发数，max_connection 最大不得超过操作系统 ulimit -n 的值(增加服务器文件描述符的最大值)，否则会报一条警告信息，并重置为 ulimit -n 的值
        //修改保存: vim /etc/security/limits.conf:
        // * soft nofile 10000
        // * hard nofile 10000
        'max_conn' => 10000,
        //设置Worker进程收到停止服务通知后最大等待时间【默认值：3】，需大于定时器周期时间，否则通知会报Warning异常
        'max_wait_time' => 20,
        'task_ipc_mode' => 2,
        'task_max_request' => 10000,
        'dispatch_mode' => 1
    ];
    /**
     * @var bool
     */
    private $daemon = true;
    /**
     * @var string
     */
    private $pidFile;
    /**
     * task任务数 或者 任务json参数
     * @var int|string
     */
    private $numOrParams;
    /**
     * @var Table
     */
    private $table;
    /**
     * @var int
     */
    private $type;
    /**
     * @var Atomic
     */
    private $taskAtomic;

    public function run($argv)
    {
        try {
            $cmd = isset($argv[1]) ? (string)$argv[1] : '';
            $this->taskType = isset($argv[2]) ? (string)$argv[2] : '';
            $this->type = isset($argv[3]) ? (string)$argv[3] : '';
            $this->port = isset($argv[4]) ? (int)$argv[4] : 0;
            $this->numOrParams = isset($argv[5]) ? $argv[5] : null;
            if (empty($cmd) || empty($this->taskType) || empty($this->type)) {
                throw new InvalidArgumentException('params error');
            }
            if (in_array($cmd, ['start']) && empty($this->port)) {
                throw new InvalidArgumentException('error port:' . $this->port);
            }
            if (!in_array($this->type, [TaskModel::TYPE_PULL_ORDER, TaskModel::TYPE_CHECK_ORDER, TaskModel::TYPE_CHECK_EXCEPTION])) {
                throw new InvalidArgumentException('type error:' . $this->type);
            }
            $this->pidFile = $this->taskType . '.' . $this->type . '.pid';
            switch ($cmd) {
                case 'start':
                    $this->start();
                    break;
                case 'stop':
                    $this->stop();
                    break;
                case 'status':
                    $this->status();
                    break;
                case 'sendTask':
                    $this->sendTask();
                    break;
                default:
                    break;
            }
        } catch (Exception $e) {
            $this->logMessage($e->getMessage());
        }
    }

    //swoole启动master进程，manager进程
    //master启动reactor线程（多线程）：维护客户端 TCP 连接，处理客户端请求，发送数据到worker进程，响应数据返回，包数据处理，异步不阻塞
    //manager进程启动worker进程，taskWorker进程（负责创建/回收worker/task进程）
    //worker进程：接收reactor线程数据，回调onReceive方法，调用task方法，发送数据到taskWorker进程（可以是异步非阻塞模式，也可以是同步阻塞模式）
    //taskWorker进程：同步阻塞模式，接收worker数据，适用于处理耗时任务，调用finish()方法通知worker进程，任务已执行完成
    private function start()
    {
        //Coroutine::set(['hook_flags' => SWOOLE_HOOK_TCP]);
        //$this->renameProcessName($this->processPrefix . $this->taskType);
        $this->server = new Server($this->host, $this->port);
        $setting = [
            'daemonize' => (bool)$this->daemon,
            'log_file' => MODULE_DIR . '/logs/server-' . date('Y-m') . '.log',
            'pid_file' => MODULE_DIR . '/cache/' . $this->pidFile,
            'task_worker_num' => $this->numOrParams,
        ];
        $this->setServerSetting($setting);
        $this->createTable();
        $this->createAtomic();
        $this->bindEvent(self::EVENT_START, [$this, 'onStart']);
        $this->bindEvent(self::EVENT_MANAGER_START, [$this, 'onManagerStart']);
        $this->bindEvent(self::EVENT_WORKER_START, [$this, 'onWorkerStart']);
        $this->bindEvent(self::EVENT_WORKER_STOP, [$this, 'onWorkerStop']);
        $this->bindEvent(self::EVENT_RECEIVE, [$this, 'onReceive']);
        $this->bindEvent(self::EVENT_TASK, [$this, 'onTask']);
        $this->bindEvent(self::EVENT_FINISH, [$this, 'onFinish']);
        $this->startServer();
    }

    /**
     * 当前进程重命名
     * @param $processName
     * @return bool|mixed
     */
    private function renameProcessName($processName)
    {
        if (function_exists('cli_set_process_title')) {
            return cli_set_process_title($processName);
        } else if (function_exists('swoole_set_process_name')) {
            return swoole_set_process_name($processName);
        }
        return false;
    }

    private function setServerSetting($setting = [])
    {
        $this->server->set(array_merge($this->setting, $setting));
    }

    private function bindEvent($event, callable $callback)
    {
        $this->server->on($event, $callback);
    }

    private function startServer()
    {
        $this->server->start();
    }

    public function onStart(Server $server)
    {
        try {
            $this->logMessage('start, master_pid:' . $server->master_pid);
            $this->logMessage('start, manager_pid:' . $server->manager_pid);
            $this->renameProcessName($this->processPrefix . $this->taskType . '-' . $this->port . '-master');
            ServerConfigModel::model()->saveConfig(
                $this->taskType, $this->type, $this->port, $server->master_pid, $server->manager_pid, $server->setting
            );
            $this->taskAtomic->set(intval($server->setting['task_worker_num']));
        } catch (Exception $e) {
            $this->logMessage(['exception' => 'onStart', 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        }
    }

    public function onManagerStart(Server $server)
    {
        $this->logMessage('manager start, manager_pid:' . $server->manager_pid);
        $this->renameProcessName($this->processPrefix . $this->taskType . '-' . $this->port . '-manager');
    }

    //连接池，每个worker进程隔离
    public function onWorkerStart(Server $server, int $workerId)
    {
        $this->logMessage('worker start, worker_pid:' . $server->worker_pid);
        $this->renameProcessName($this->processPrefix . $this->taskType . '-' . $this->port . '-worker-' . $workerId);
    }

    public function onWorkerStop(Server $server, int $workerId)
    {
        $this->logMessage('worker stop, worker_pid:' . $server->worker_pid);
    }

    //worker进程，接收到客户端数据，回调方法
    public function onReceive(Server $server, $fd, $reactorId, $json)
    {
        try {
            $data = json_decode($json, true);
            if (!isset($data['taskType'], $data['type'], $data['params'])) {
                throw new Exception('params error');
            }
            $this->logMessage('receive:' . $json);
            $taskModel = TaskFactory::factory($data['taskType']);
            $serverConfig = ServerConfigModel::model()->findOne(['task_type' => $this->taskType, 'type' => $this->type]);
            if (empty($serverConfig)) {
                throw new Exception('serverConfig not exist');
            }
            $params = ['taskType' => $data['taskType'], 'type' => $data['type'], 'limit' => $serverConfig['task_worker_num'], 'params' => $data['params']];
            //worker初始化此次执行的全部任务到mongo，然后发送taskNum数任务到task进程
            $taskModel->setType($this->type)->setTaskWorkerNum($serverConfig['task_worker_num'])->setParams($this->numOrParams);
            if ($taskModel->initTask()) {
                $tasks = $taskModel->getTasks($params);
                foreach ($tasks as $task) {
                    //投递异步任务
                    $_id = json_decode(json_encode($task['_id']), true);
                    $this->deliverTask($server, $taskModel, $data['taskType'], $data['type'], $_id['$oid']);
                }
                $this->logMessage('onReceive:taskNum:' . count($tasks));
            }
        } catch (Exception $e) {
            $this->logMessage(['exception' => 'onReceive', 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        }

    }

    private function deliverTask(Server &$server, TaskModel $taskModel, $taskType, $type, $_id)
    {
        $this->logMessage('deliverTask:' . $_id);
        $taskModel->updateTaskStatus($_id, TaskLogModel::STATUS_RUNNING);
        $arr = [
            'taskType' => $taskType,
            'type' => $type,
            '_id' => $_id,
        ];
        $taskId = $server->task(json_encode($arr));
        if ($taskId !== false) {
            $key = $taskType . '.' . $type . '.' . $taskId;
            $this->tableAdd($key, self::STATUS_INIT);
            return true;
        } else {
            $taskModel->updateTaskStatus($_id, TaskLogModel::STATUS_INIT);
            return false;
        }
    }

    /**
     * @param Server $server
     * @param $taskId int 执行任务的 task 进程 id【$task_id 和 $src_worker_id 组合起来才是全局唯一的，不同的 worker 进程投递的任务 ID 可能会有相同】
     * @param $reactorId int 投递任务的 worker 进程 id【$task_id 和 $src_worker_id 组合起来才是全局唯一的，不同的 worker 进程投递的任务 ID 可能会有相同】
     * @param $json
     */
    public function onTask(Server $server, $taskId, $reactorId, $json)
    {
        try {
            $params = json_decode($json, true);
            if (!isset($params['taskType'], $params['type'], $params['_id'])) {
                throw new Exception('params error');
            }
            $key = $params['taskType'] . '.' . $params['type'] . '.' . $taskId;
            $this->tableUpdate($key, self::STATUS_RUNNING);
            $taskModel = TaskFactory::factory($params['taskType']);
            //worker初始化此次执行的全部任务到mongo，然后发送taskNum数任务到task进程
            $result = $taskModel->setType($params['type'])->taskRun($params);
            $this->tableUpdate($key, self::STATUS_SUCCESS);
            $server->finish($result);
        } catch (Exception $e) {
            $this->logMessage(['exception' => 'onTask', 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);
            if (isset($key)) {
                $this->tableUpdate($key, self::STATUS_FAIL);
            }
        }
    }

    //worker进程回调
    public function onFinish(Server $server, $taskId, $data)
    {
        try {
            $this->logMessage('onFinish:' . $taskId . ';data:' . $data);
            $key = $this->taskType . '.' . $this->type . '.' . $taskId;
            $this->logMessage('del key:' . $key);
            $this->table->del($key);
            $this->logMessage('running num:' . $this->table->count() . ';atomic:' . $this->taskAtomic->get());
            //检查任务超时情况记录，删除
            foreach ($this->table as $k => $row) {
                if ($row['create_time'] + self::TASK_TIME_OUT < time()) {
                    $this->table->del($k);
                }
            }
            $task = true;
            $taskModel = TaskFactory::factory($this->taskType);
            while ($this->table->count() < $this->taskAtomic->get() && !empty($task)) {
                //小于task任务数，可以继续投递
                $task = $taskModel->getNextTask();
                if (!empty($task)) {
                    $_id = json_decode(json_encode($task['_id']), true);
                    $this->deliverTask($server, $taskModel, $this->taskType, $this->type, $_id['$oid']);
                } else {
                    $this->logMessage('break:' . $this->table->count() . ';atomic:' . $this->taskAtomic->get());
                    break;
                }
            }
            $this->logMessage('onFinish done:' . $taskId . ';data:' . $data);
        } catch (Exception $e) {
            $this->logMessage(['exception' => 'onFinish', 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);
        }
    }

    private function logMessage($logData)
    {
        $logData = (is_array($logData) || is_object($logData)) ? json_encode($logData, JSON_UNESCAPED_UNICODE) : $logData;
        echo date('[Y-m-d H:i:s]') . $logData . PHP_EOL;
    }

    /**
     * @param bool $force
     * @throws Exception
     */
    private function stop($force = false)
    {
        $pidFile = MODULE_DIR . '/cache/' . $this->pidFile;
        if (!file_exists($pidFile)) {
            throw new Exception('server not running');
        }
        $pid = file_get_contents($pidFile);
        if (!Process::kill($pid, 0)) {
            unlink($pidFile);
            throw new Exception("pid not exist:{$pid}");
        } else {
            if ($force) {
                Process::kill($pid, SIGKILL);
            } else {
                Process::kill($pid);
            }
        }
    }

    /**
     * @throws Exception
     */
    private function status()
    {
        $pidFile = MODULE_DIR . '/cache/' . $this->pidFile;
        if (!file_exists($pidFile)) {
            throw new Exception('server not running');
        }
        $pid = file_get_contents($pidFile);
        //$signo=0，可以检测进程是否存在，不会发送信号
        if (!Process::kill($pid, 0)) {
            echo 'not running, pid:' . $pid . PHP_EOL;
        } else {
            echo 'running, pid:' . $pid . PHP_EOL;
        }
    }

    //Table 可以用于多进程之间共享数据
    private function createTable()
    {
        //存储数据size，即mysql总行数
        $size = 10240;
        $this->table = new Table($size);
        $this->table->column('create_time', Table::TYPE_INT);
        $this->table->column('last_time', Table::TYPE_INT);
        $this->table->column('status', Table::TYPE_INT);
        $this->table->create();
        return true;
    }

    private function tableAdd($key, $status = 0)
    {
        $this->table->set($key, ['create_time' => time(), 'last_time' => time(), 'status' => $status]);
    }

    private function tableUpdate($key, $status = 1)
    {
        $this->table->set($key, ['last_time' => time(), 'status' => $status]);
    }

    //进程间无锁计数器 Atomic
    private function createAtomic()
    {
        $this->taskAtomic = new Atomic();
        return true;
    }

    //异步客户端
    private function sendBak()
    {
        $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
        if (!$client->connect($this->host, $this->port)) {
            exit("connect failed. Error: {$client->errCode}\n");
        }
        if (!$client->isConnected()) {
            exit('connect fail');
        }
        $data = "hello world\n";
        $client->send($data);
        echo $client->recv();
        $client->close();
    }

    //协程客户端：客户端发送数据到server-worker
    //Swoole4 不再支持异步客户端，相应的需求完全可以用协程客户端代
    private function sendTask()
    {
        run(function () {
            $serverConfig = ServerConfigModel::model()->findOne(['task_type' => $this->taskType, 'type' => $this->type]);
            if (empty($serverConfig)) {
                echo 'server config not exist';
                return;
            }
            $client = new Client(SWOOLE_SOCK_TCP);
            if (!$client->connect($this->host, $serverConfig['port'], 10)) {
                $this->logMessage("connect failed. Error: {$client->errCode}，errMsg:{$client->errMsg}");
                return;
            }
            if (!empty($this->numOrParams)) {
                $this->numOrParams = json_decode($this->numOrParams, true);
            } else {
                $this->numOrParams = [];
            }
            $data = ['taskType' => $this->taskType, 'type' => $this->type, 'params' => $this->numOrParams];
            $json = json_encode($data);
            $client->send($json);
            echo $client->recv();
            $client->close();
            $this->logMessage('sendTask done');
        });
    }


}