<?php

namespace module\task;

interface Task
{
    public function tableName();

    public function getAccountList();

    public function pullOrder($params);

    public function checkOrder($params);

    public function checkException($params);
}