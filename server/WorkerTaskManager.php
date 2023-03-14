<?php
/**
 * @author https://github.com/JaydenOK
 */

namespace module\server;

use Exception;
use InvalidArgumentException;
use module\task\TaskFactory;
use module\task\TaskModel;
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
    private $poolTable;
    /**
     * @var int
     */
    private $type;

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
            if (in_array($cmd, ['start', 'sendTask']) && empty($this->port)) {
                throw new InvalidArgumentException('error port:' . $this->port);
            }
            if (!in_array($this->type, [TaskModel::TYPE_PULL_ORDER, TaskModel::TYPE_CHECK_ORDER, TaskModel::TYPE_CHECK_EXCEPTION])) {
                throw new InvalidArgumentException('type error:' . $this->type);
            }
            $this->pidFile = $this->port . '.pid';
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
            'pid_file' => MODULE_DIR . '/logs/' . $this->pidFile,
            'task_worker_num' => $this->numOrParams,
        ];
        $this->setServerSetting($setting);
        $this->createTable();
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
        $this->logMessage('start, master_pid:' . $server->master_pid);
        $this->renameProcessName($this->processPrefix . $this->taskType . '-' . $this->port . '-master');
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

    //接收到客户端数据，回调方法
    public function onReceive(Server $server, $fd, $reactorId, $json)
    {
        try {
            $data = json_decode($json, true);
            if (!isset($data['taskType'], $data['type'], $data['params'])) {
                throw new Exception('params error');
            }
            $this->logMessage('receive:' . $json);
            $taskModel = TaskFactory::factory($data['taskType']);
            $params = ['taskType' => $data['taskType'], 'type' => $data['type'], 'limit' => $this->numOrParams, 'params' => $data['params']];
            //worker初始化此次执行的全部任务到mongo，然后发送taskNum数任务到task进程
            $taskModel->setType($this->type)->setParams($this->numOrParams)->initTask();
            $tasks = $taskModel->getTasks($params);
            foreach ($tasks as $task) {
                //投递异步任务
                $arr = [
                    'taskType' => $data['taskType'],
                    'type' => $data['type'],
                    'limit' => $this->numOrParams,
                    '_id' => $task['_id'],
                ];
                $server->task(json_encode($params));
            }
        } catch (Exception $e) {
            $this->logMessage('ReceiveException:' . $e->getMessage());
        }

    }

    public function onTask(Server $server, $taskId, $reactorId, $json)
    {
        try {
            $this->logMessage('onTask:' . $json);
            $data = json_decode($json, true);
            if (!isset($data['taskType'], $data['type'], $data['_id'])) {
                throw new Exception('params error');
            }
            //todo
            $taskModel = TaskFactory::factory($data['taskType']);
            //worker初始化此次执行的全部任务到mongo，然后发送taskNum数任务到task进程
            $result = $taskModel->setType($data['type'])->taskRun($data);
            $server->finish($result);
        } catch (Exception $e) {
            $this->logMessage('ReceiveException:' . $e->getMessage());
        }
    }

    public function onFinish(Server $server, $task_id, $data)
    {
        $this->logMessage('taskId:' . $task_id . ';data:' . $data);
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
        $pidFile = MODULE_DIR . '/logs/' . $this->pidFile;
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
        $pidFile = MODULE_DIR . '/logs/' . $this->pidFile;
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

    private function createTable()
    {
        if (true) {
            return 'not support now';
        }
        //存储数据size，即mysql总行数
        $size = 1024;
        $this->poolTable = new Table($size);
        $this->poolTable->column('created', Table::TYPE_INT, 10);
        $this->poolTable->column('pid', Table::TYPE_INT, 10);
        $this->poolTable->column('inuse', Table::TYPE_INT, 10);
        $this->poolTable->column('loadWaitTimes', Table::TYPE_FLOAT, 10);
        $this->poolTable->column('loadUseTimes', Table::TYPE_INT, 10);
        $this->poolTable->column('lastAliveTime', Table::TYPE_INT, 10);
        $this->poolTable->create();
        return true;
    }

    //协程客户端：客户端发送数据到server-worker
    //Swoole4 不再支持异步客户端，相应的需求完全可以用协程客户端代
    private function sendTask()
    {
        run(function () {
            //$taskModel = TaskFactory::factory($this->taskType);
            //$params = ['type' => $this->type, 'params' => $this->numOrParams];
            $client = new Client(SWOOLE_SOCK_TCP);
            if (!$client->connect($this->host, $this->port, 3)) {
                $this->logMessage("connect failed. Error: {$client->errCode}");
            }
            if (!empty($this->numOrParams)) {
                $this->numOrParams = json_decode($this->numOrParams, true);
            }
            $data = ['taskType' => $this->taskType, 'type' => $this->type, 'params' => $this->numOrParams];
            $json = json_encode($data);
            $client->send($json);
            echo $client->recv();
            $client->close();
            $this->logMessage('sendTask done');
        });
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


}