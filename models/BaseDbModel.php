<?php
/**
 * mongo ,base 库连接信息
 */

namespace module\models;

class BaseDbModel extends Model
{

    public function dbName()
    {
        return 'hkdcm_order';
    }

}