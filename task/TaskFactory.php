<?php

namespace module\task;
/**
 * 工厂方法，生产任务模型
 * Class TaskFactory
 * @package module\task
 */
class TaskFactory
{
    const TASK_AMAZON = 'Amazon';
    const TASK_SHOPEE = 'Shopee';
    const TASK_EBAY = 'Ebay';

    public static function taskList()
    {
        $taskList = [];
        $class = new \ReflectionClass(TaskFactory::class);
        $constants = $class->getConstants();
        foreach ($constants as $key => $value) {
            if (strpos($key, 'TASK_') !== false) {
                $taskList[$key] = $value;
            }
        }
        return $taskList;
    }

    /**
     * @param $taskType
     * @param null $poolObject
     * @return AmazonModel|Ebay|ShopeeModel|null
     * @throws \Exception
     */
    public static function factory($taskType, $poolObject = null)
    {
        $task = null;
        switch ($taskType) {
            case self::TASK_AMAZON:
                $task = new AmazonModel($poolObject);
                break;
            case self::TASK_SHOPEE:
                $task = new ShopeeModel($poolObject);
                break;
            case self::TASK_EBAY:
                $task = new Ebay($poolObject);
                break;
            default:
                break;
        }
        if ($task === null) {
            throw new \Exception('task model not defined');
        }
        return $task;
    }


}