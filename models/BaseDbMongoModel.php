<?php
/**
 * mongo库连接信息
 */

namespace module\models;

class BaseDbMongoModel extends MongoModel
{

    protected $prefix = 'tt_';

    public function dbName()
    {
        return 'hwc';
    }

}