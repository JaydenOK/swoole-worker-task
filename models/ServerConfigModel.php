<?php

namespace module\models;

class ServerConfigModel extends BaseDbModel
{

    public function tableName()
    {
        return 'server_config';
    }

    public function saveConfig($taskType, $type, $port, $masterPid, $managerPid, $setting)
    {
        $serverConfig = $this->findOne(['task_type' => $taskType, 'type' => $type]);
        if (empty($serverConfig)) {
            $data = [
                'task_type' => $taskType,
                'type' => $type,
                'port' => $port,
                'master_pid' => $masterPid,
                'manager_pid' => $managerPid,
                'setting' => $setting,
                'create_time' => date('Y-m-d H:i:s'),
            ];
            $res = $this->insertOne($data);
        } else {
            $data = [
                'port' => $port,
                'master_pid' => $masterPid,
                'manager_pid' => $managerPid,
                'setting' => $setting,
                'update_time' => date('Y-m-d H:i:s'),
            ];
            $res = $this->updateOne(['_id' => $serverConfig['_id']], $data);
        }
        return $res;
    }


}