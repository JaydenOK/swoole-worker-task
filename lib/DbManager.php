<?php

namespace module\lib;

use MongoDB\Client;

/**
 * DB管理类，每个任务进程独立连接
 * Class DbManager
 * @package module\lib
 */
class DbManager
{

    /**
     * @var MysqliDb
     */
    protected static $mysqlDb;
    /**
     * @var  Client
     */
    protected static $mongoDb;

    /**
     * @return MysqliDb
     */
    public static function getMysqlDb()
    {
        return self::$mysqlDb;
    }

    /**
     * @param null $mysqlDb
     */
    public static function setMysqlDb($mysqlDb = null): void
    {
        if ($mysqlDb === null) {
            self::$mysqlDb = (new MysqliClient())->getQuery();
        } else {
            self::$mysqlDb = $mysqlDb;
        }
    }

    /**
     * @return Client
     */
    public static function getMongoDb()
    {
        return self::$mongoDb;
    }

    /**
     * @param mixed $mongoDb
     */
    public static function setMongoDb($mongoDb = null): void
    {
        if ($mongoDb === null) {
            self::$mongoDb = (new MongoClient())->getClient();
        } else {
            self::$mongoDb = $mongoDb;
        }
    }

    /**
     * 初始化新的db连接
     */
    public static function initDb()
    {
        self::setMysqlDb();
        self::setMongoDb();
    }

}