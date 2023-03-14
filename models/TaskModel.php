<?php

namespace module\models;

class TaskModel extends BaseDbModel
{

    public function tableName()
    {
        return 'task';
    }

    public static function model()
    {
        return new static(__CLASS__);
    }

}