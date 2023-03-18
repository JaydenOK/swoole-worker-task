<?php

namespace module\task;

use module\models\TaskLogModel;

class AmazonModel extends TaskModel
{

    protected $taskType = 'Amazon';

    public function tableName()
    {
        return 'yibai_amazon_account';
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
            //$result = $this->query->where('account_type', 1)->get($this->tableName());
            $result = $this->query->where('account_type', 1)->page(1)->limit(1000)->paginate($this->tableName());   //test
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
            //todo 模拟业务耗时处理逻辑
            //sleep(mt_rand(1, 3));
            $data['access_token'] = 'aaaaaaa';
            $url = 'https://sellingpartnerapi-na.amazon.com/orders/v0/orders';
            //$url = 'https://oms.goodcang.net/public_open/return_order/search';
            $header = [
                'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
            ];
            $taskLogModel->updateOne($filter, ['update_time' => nowDate()]);
            $responseBody = curlPost($url, $data, 5, $header);       //curl对task进程有影响??
//            $responseBody = curlGet($url, 5);       //curl对task进程有影响??

            //$responseBody = file_get_contents($url);
            $taskLogModel->updateOne($filter, ['response_time' => nowDate()]);
            //todo 处理业务逻辑，保存下载的订单
//            $orderData = [];
//            $result = OrderModel::model()->insertOne($orderData);
            !isset($responseBody) && $responseBody = 'aa';
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