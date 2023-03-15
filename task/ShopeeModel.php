<?php

namespace module\task;

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
            $sql = "select * from {$this->tableName()} limit {$this->params['limit']}";
            $queryResult = $this->poolObject->query($sql);
            $result = [];
            while ($row = $queryResult->fetch_assoc()) {
                $result[] = $row;
            }
        } else {
            $result = $this->query->page(1)->limit($this->params['limit'])->paginate($this->tableName());
        }
        return $result;
    }

    public function pullOrder($params)
    {
        // TODO: Implement pullOrder() method.
    }

    public function checkOrder($params)
    {
        // TODO: Implement checkOrder() method.
    }

    public function checkException($params)
    {
        // TODO: Implement checkException() method.
    }
}