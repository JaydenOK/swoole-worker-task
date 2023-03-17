<?php

namespace module\models;

class OrderModel extends BaseDbMongoModel
{

    public function tableName()
    {
        return 'order';
    }

}