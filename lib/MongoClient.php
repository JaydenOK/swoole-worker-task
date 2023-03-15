<?php

//官方SDK1.6版本文档：https://www.mongodb.com/docs/php-library/v1.6/tutorial/install-php-library/

namespace module\lib;


use MongoDB\Client;

class MongoClient
{

    /**
     * @return mixed
     * @throws \Exception
     */
    protected function databaseConfig()
    {
        $configDir = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
        $filePath = $configDir . 'mongodb.php';
        if (!file_exists($filePath)) {
            throw new \Exception('config not exist:' . $filePath);
        }
        $mongoDbConfig = include_once($filePath);
        return $mongoDbConfig;
    }

    public function getClient()
    {
        $mongoDbConfig = $this->databaseConfig();
        $dsn = 'mongodb://' . $mongoDbConfig['host'] . ':' . $mongoDbConfig['port'] . '/' . $mongoDbConfig['dbname'];
        $options = ['username' => $mongoDbConfig['username'], 'password' => $mongoDbConfig['password']];
        $client = new Client($dsn, $options);
        return $client;
    }

}