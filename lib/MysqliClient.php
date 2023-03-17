<?php

namespace module\lib;

use Swoole\Database\MysqliConfig;
use Swoole\Database\MysqliPool;

class MysqliClient
{

    protected $config = [];
    /**
     * @var MysqliDb
     */
    private $query;

    /**
     * @return mixed
     * @throws \Exception
     */
    protected function config()
    {
        $configDir = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
        $filePath = $configDir . 'database.php';
        if (!file_exists($filePath)) {
            throw new \Exception('database config not exist:' . $filePath);
        }
        return include($filePath);
    }

    public function getQuery()
    {
        if (empty($this->config)) {
            $this->config = $this->config();
        }
        $this->query = new MysqliDb([
            'host' => $this->config['host'],
            'username' => $this->config['user'],
            'password' => $this->config['password'],
            'db' => $this->config['dbname'],
            'port' => $this->config['port'],
            //'prefix' => 't_',
            'charset' => $this->config['charset'],
        ]);
        return $this->query;
    }

    /**
     * @param int $poolSize
     * @return MysqliPool
     * @throws \Exception
     */
    public function initPool($poolSize = 10)
    {
        $config = $this->config();
        $pool = new MysqliPool(
            (new MysqliConfig)->withHost($config['host'])->withUsername($config['user'])->withPassword($config['password'])
                ->withPort($config['port'])->withDbName($config['dbname'])->withCharset($config['charset']),
            $poolSize);
        return $pool;
    }

}