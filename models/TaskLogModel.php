<?php

namespace module\models;

class TaskLogModel extends BaseDbMongoModel
{
    const STATUS_INIT = 0;      //初始化
    const STATUS_RUNNING = 1;   //运行中
    const STATUS_SUCCESS = 2;   //成功
    const STATUS_FAIL = 3;     //失败

    public function tableName()
    {
        return 'task_log';
    }

}