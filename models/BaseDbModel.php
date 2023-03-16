<?php
/**
 * mongo库连接信息
 */

namespace module\models;

class BaseDbModel extends Model
{

    protected $prefix = 'tt_';

    public function dbName()
    {
        return 'hwc';
    }

}