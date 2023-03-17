<?php

namespace module\models;

class SystemLogModel extends BaseDbMongoModel
{

    public function tableName()
    {
        return 'system_log';
    }

}