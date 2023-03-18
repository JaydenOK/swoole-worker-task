<?php
/**
 * @author https://github.com/JaydenOK
 */

namespace module\server;

use Exception;
use InvalidArgumentException;
use module\lib\DbManager;
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
    const EVENT_WORKER_ERROR = 'workerError';

    const STATUS_INIT = 0;      //初始化
    const STATUS_RUNNING = 1;   //运行中
    const STATUS_SUCCESS = 2;   //成功
    const STATUS_FAIL = 3;     //失败

    const TASK_TIME_OUT = 600;   //task任务超时时间(秒)

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
        'worker_num' => 1,
        'task_worker_num' => 10,
        'enable_coroutine' => false,      //回调开启协程
        'task_enable_coroutine' => false,   //开启 Task 协程支持。【默认值：false】，v4.2.12 起支持；-task_enable_coroutine 必须在 enable_coroutine 为 true 时才可以使用；协程onTask()回调参数也不同
        'max_request' => 10000, //设置 worker 进程的最大任务数。【默认值：0 即不会退出进程】
        //'max_conn' => 10000,  //服务器程序，最大允许的连接数。【默认值：ulimit -n】
        //'max_wait_time' => 20,    //设置Worker进程收到停止服务通知后最大等待时间【默认值：3】，需大于定时器周期时间，否则通知会报Warning异常
        'task_ipc_mode' => 1,   //设置 Task 进程与 Worker 进程之间通信的方式，1支持定向投递，2,3系统消息队列通信
        'task_max_request' => 50000,    //设置 task 进程的最大任务数。【默认值：0】，超过退出//dispatch_mode数据包分发策略。【默认值：2】,
        'dispatch_mode' => 1,    //客户端数据包分发策略（对于Worker进程）；1轮循模式；2固定模式（保证同一个连接发来的数据只会被同一个 Worker 处理）； 3	抢占模式； 4	IP 分配	根据客户端 IP 进行取模 hash；（无状态 Server 可以使用 1 或 3，同步阻塞 Server 使用 3，异步非阻塞 Server 使用 1，有状态使用 2、4、5）
        'log_level' => SWOOLE_LOG_TRACE,
        'trace_flags' => SWOOLE_TRACE_SERVER | SWOOLE_TRACE_HTTP2,
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
        $this->bindEvent(self::EVENT_WORKER_ERROR, [$this, 'onWorkerError']);
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
            DbManager::initDb();
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
        $name = $server->taskworker ? 'taskworker-' . $workerId : 'worker-' . $workerId;
        $this->logMessage('worker start, worker_pid:' . $server->worker_pid);
        $this->renameProcessName($this->processPrefix . $this->taskType . '-' . $this->port . '-' . $name);
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
            $this->deleteTimeoutKeys();
            $this->logMessage('receive:' . $json);
            DbManager::initDb();
            $taskModel = TaskFactory::factory($data['taskType']);
            $serverConfig = ServerConfigModel::model()->findOne(['task_type' => $this->taskType, 'type' => $this->type]);
            if (empty($serverConfig)) {
                throw new Exception('serverConfig not exist');
            }
            $params = ['taskType' => $data['taskType'], 'type' => $data['type'], 'limit' => $serverConfig['task_worker_num'], 'params' => $data['params']];
            //worker初始化此次执行的全部任务到mongo，然后发送taskNum数任务到task进程
            $taskModel->setType($this->type)->setTaskWorkerNum($serverConfig['task_worker_num'])->setParams($this->numOrParams);
            if ($taskModel->initTask()) {
                $this->logMessage($server->stats());
                $tasks = $taskModel->getTasks($params);
                foreach ($tasks as $task) {
                    //初次投递异步任务
                    $_id = json_decode(json_encode($task['_id']), true);
                    $this->deliverTask($server, $taskModel, $data['taskType'], $data['type'], $_id['$oid']);
                }
            }
            $this->logMessage('onReceive:done');
        } catch (Exception $e) {
            $this->logMessage(['exception' => 'onReceive', 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        }

    }

    //投递前，先加数，投递完如果失败还原
    private function deliverTask(Server $server, TaskModel $taskModel, $taskType, $type, $_id)
    {
        $stats = $server->stats();
        $this->logMessage('task_idle:' . $stats['task_idle_worker_num'] . '; tasking_num:' . $stats['tasking_num']);
        $key = $taskType . '.' . $type . '.' . $_id;
        $taskModel->updateTaskStatus($_id, TaskLogModel::STATUS_RUNNING);
        $this->tableAdd($key, $_id, self::STATUS_INIT);
        $arr = [
            'taskType' => $taskType,
            'type' => $type,
            '_id' => $_id,
        ];
        $taskId = $server->task(json_encode($arr));
        if ($taskId !== false) {
            $this->logMessage('deliverTask:' . $_id);
            return true;
        } else {
            //投递失败还原
            $this->logMessage('deliverTaskFail:' . $_id);
            $this->tableDelete($key);
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
            DbManager::initDb();
            $this->logMessage('onTaskStart:' . $params['_id']);
            $key = $params['taskType'] . '.' . $params['type'] . '.' . $params['_id'];
            //$this->tableUpdate($key, $params['_id'], self::STATUS_RUNNING);
            $taskModel = TaskFactory::factory($params['taskType']);
            //worker初始化此次执行的全部任务到mongo，然后发送taskNum数任务到task进程
            $result = $taskModel->setType($params['type'])->taskRun($params);
            //$this->tableUpdate($key, $params['_id'], self::STATUS_SUCCESS);
            $this->logMessage('onTaskEnd:' . $params['_id'] . '; result:' . $result);
            $res = $server->finish($params['_id']);
            if ($res) {
                $this->logMessage('onTaskFinish:' . $params['_id']);
            } else {
                $this->logMessage('onTaskFinishFail:' . $params['_id']);
            }
        } catch (Exception $e) {
            $this->logMessage(['exception' => 'onTask', 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);
            if (isset($key) && isset($params['_id'])) {
                $this->tableDelete($key);
            }
        }
    }

    //worker进程回调
    //- task 进程的 onTask 事件中没有调用 finish 方法或者 return 结果，worker 进程不会触发 onFinish
    //- 执行 onFinish 逻辑的 worker 进程与下发 task 任务的 worker 进程是同一个进程（执行完onReceive才会执行此方法）
    public function onFinish(Server $server, $taskId, $data)
    {
        try {
            $stats = $server->stats();
            $this->logMessage('task_idle:' . $stats['task_idle_worker_num'] . '; tasking_num:' . $stats['tasking_num'] . '; task_queue_num:' . $stats['task_queue_num']);
            //$data即$_id
            $this->logMessage('onFinish:' . $data);
            $key = $this->taskType . '.' . $this->type . '.' . $data;
            $this->tableDelete($key);
            $this->logMessage('runningNum:' . $this->tableCount() . '; atomicNum:' . $this->taskAtomic->get());
            //检查任务超时情况记录，删除，
            $this->deleteTimeoutKeys();
            if ($this->tableCount() < $this->taskAtomic->get()) {
                $taskModel = TaskFactory::factory($this->taskType);
                //小于task任务数，可以继续投递
                $task = $taskModel->setType($this->type)->getNextTask();
                if (!empty($task)) {
                    $_id = json_decode(json_encode($task['_id']), true);
                    $this->deliverTask($server, $taskModel, $this->taskType, $this->type, $_id['$oid']);
                } else {
                    $this->logMessage('taskDone:' . $this->tableCount());
                }
            } else {
                $this->logMessage('full:' . $this->tableCount());
            }
        } catch (Exception $e) {
            $this->logMessage(['exception' => 'onFinish', 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);
        }
    }

    public function onWorkerError(Server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal)
    {
        $this->logMessage('onWorkerError:' . 'pid:' . $worker_pid . '; exit_code:' . $exit_code . '; signal:' . $signal);
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

    //Table 可以用于多进程之间共享数据，请勿在遍历时删除。
    private function createTable()
    {
        //存储数据size，即mysql总行数
        $size = 10240;
        $this->table = new Table($size);
        $this->table->column('_id', Table::TYPE_STRING, 64);
        $this->table->column('create_time', Table::TYPE_INT);
        $this->table->column('status', Table::TYPE_INT);
        $this->table->create();
        return true;
    }

    private function tableAdd($key, $_id, $status = 0)
    {
        return $this->table->set($key, ['_id' => $_id, 'create_time' => time(), 'status' => $status]);
    }

    private function tableUpdate($key, $_id, $status = 1)
    {
        return $this->table->set($key, ['_id' => $_id, 'create_time' => time(), 'status' => $status]);
    }

    private function tableDelete($key)
    {
        return $this->table->del($key);
    }

    private function tableCount()
    {
        return $this->table->count();
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
            DbManager::initDb();
            $serverConfig = ServerConfigModel::model()->findOne(['task_type' => $this->taskType, 'type' => $this->type]);
            if (empty($serverConfig)) {
                echo 'server config not exist';
                return;
            }
            $client = new Client(SWOOLE_SOCK_TCP);
            if (!$client->connect($this->host, $serverConfig['port'], 20)) {
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
            //$response = $client->recv();
            $client->close();
            $this->logMessage('sendTask done');
        });
    }

    //超时删除
    private function deleteTimeoutKeys()
    {
        $deleteKeys = [];
        foreach ($this->table as $key => $row) {
            if ($row['create_time'] + self::TASK_TIME_OUT < time()) {
                $deleteKeys[$key] = $row;
            }
        }
        foreach ($deleteKeys as $key => $row) {
            $this->logMessage('timeOut:' . $row['_id'] . '; create_time:' . $row['create_time'] . '; date:' . date('Y-m-d H:i:s', $row['create_time']));
            $this->tableDelete($key);
        }
    }


}