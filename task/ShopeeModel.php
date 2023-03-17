<?php

namespace module\task;

use module\models\SystemLogModel;
use module\models\TaskLogModel;

class ShopeeModel extends TaskModel
{

    protected $taskType = 'Shopee';

    public function tableName()
    {
        return 'yibai_shopee_account';
    }

    public function getAccountList()
    {
        // TODO: Implement getTaskList() method.
        if ($this->isUsePool) {
            $sql = "select * from {$this->tableName()} where account_type=1";
            $queryResult = $this->poolObject->query($sql);
            $result = [];
            while ($row = $queryResult->fetch_assoc()) {
                $result[] = $row;
            }
        } else {
            $result = $this->query->where('account_type', 1)->get($this->tableName());
        }
        return $result;
    }


    /**
     * @param $params
     * @return int
     */
    public function pullOrder($params)
    {
        $filter = ['_id' => mongoObjectId($params['_id'])];
        $taskLogModel = TaskLogModel::model();
        try {
            $task = $taskLogModel->findOne($filter);
            if (empty($task)) {
                throw new \Exception('task not exist');
            }
            //SystemLogModel::model()->insertOne(['type' => 'info', 'create_time' => nowDate(), 'task' => $task]);
            $taskLogModel->updateOne($filter, ['status' => TaskLogModel::STATUS_RUNNING, 'execute_time' => nowDate()]);
            $endpointHost = 'https://sellingpartnerapi-na.amazon.com/';
            //todo 模拟业务耗时处理逻辑
            sleep(mt_rand(1, 3));
            $data['access_token'] = 'aaaaaaa';
            $url = $endpointHost . '/orders/v0/orders';
            $header = [
                'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
            ];
            $taskLogModel->updateOne($filter, ['update_time' => nowDate()]);
            $responseBody = curlPost($url, $data, 60, $header);
            $taskLogModel->updateOne($filter, ['response_time' => nowDate()]);
            //todo 处理业务逻辑，保存下载的订单
//            $orderData = [];
//            $result = OrderModel::model()->insertOne($orderData);

            //处理请求返回数据
            $taskLogModel->updateOne(
                $filter,
                ['status' => TaskLogModel::STATUS_SUCCESS, 'update_time' => nowDate(), 'response' => $responseBody]
            );
            return self::CODE_SUCCESS;
        } catch (\Exception $e) {
            $taskLogModel->updateOne(
                $filter,
                ['status' => TaskLogModel::STATUS_FAIL, 'update_time' => nowDate(), 'message' => $e->getMessage()]
            );
            return self::CODE_FAIL;
        }
    }

    public function checkOrder($params)
    {
        try {

        } catch (\Exception $e) {

        }
    }

    public function checkException($params)
    {
        try {

        } catch (\Exception $e) {

        }
    }

}