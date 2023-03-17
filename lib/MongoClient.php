<?php

//官方SDK1.6版本文档：https://www.mongodb.com/docs/php-library/v1.6/tutorial/install-php-library/

namespace module\lib;


use MongoDB\Client;

class MongoClient
{

    protected $config = [];

    /**
     * @return mixed
     * @throws \Exception
     */
    protected function getConfig()
    {
        $configDir = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
        $filePath = $configDir . 'mongodb.php';
        if (!file_exists($filePath)) {
            throw new \Exception('config not exist:' . $filePath);
        }
        $mongoDbConfig = include($filePath);
        return $mongoDbConfig;
    }

    public function getClient()
    {
        if (empty($this->config)) {
            $this->config = $this->getConfig();
        }
        $dsn = 'mongodb://' . $this->config['host'] . ':' . $this->config['port'] . '/' . $this->config['dbname'];
        $options = ['username' => $this->config['username'], 'password' => $this->config['password']];
        $client = new Client($dsn, $options);
        return $client;
    }

}