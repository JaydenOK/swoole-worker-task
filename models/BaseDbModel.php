<?php
/**
 * mongo ,base库连接信息
 */

namespace module\models;

class BaseDbModel extends Model
{

    public function dbName()
    {
        return 'hwc';
    }

}