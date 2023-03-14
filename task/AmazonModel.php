<?php

namespace module\task;

class AmazonModel extends TaskModel
{

    protected $taskType = 'Amazon';

    public function tableName()
    {
        return 'yibai_amazon_account';
    }

    public function getAccountList()
    {
        // TODO: Implement getTaskList() method.
        if ($this->isUsePool) {
            $sql = "select * from {$this->tableName()} limit {$this->params['limit']}";
            $queryResult = $this->poolObject->query($sql);
            $result = [];
            while ($row = $queryResult->fetch_assoc()) {
                $result[] = $row;
            }
        } else {
            $result = $this->query->page(1)->limit($this->params['limit'])->paginate($this->tableName());
        }
        return $result;
    }


    /**
     * 重新解压，编译支持https
     * phpize && ./configure --enable-openssl --enable-http2 && make && sudo make install
     * @param $params
     * @return mixed
     * @throws \Exception
     */
    public function pullOrder($params)
    {
        try {
            //todo 模拟业务耗时处理逻辑
            $data = ['refresh_num' => mt_rand(0, 10)];
            $res = $this->query->where('id', $params['id'])->update($this->tableName(), $data);
            $id = $params['id'];
            $appId = $params['app_id'];
            $sellingPartnerId = $params['selling_partner_id'];
            $host = 'api.amazon.com';
            $path = '/auth/o2/token';
            $data = [];
            $data['grant_type'] = 'refresh_token';
            $data['client_id'] = '111';
            $data['client_secret'] = '222';
            $data['refresh_token'] = '333';
            $cli = new \Swoole\Coroutine\Http\Client($host, 443, true);
            $cli->set(['timeout' => 10]);
            $cli->setHeaders([
                'Host' => $host,
                'grant_type' => 'refresh_token',
                'client_id' => 'refresh_token',
                "User-Agent" => 'Chrome/49.0.2587.3',
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8',
            ]);
            $cli->post($path, http_build_query($data));
            $responseBody = $cli->body;
            //处理请求返回数据
            $data = ['refresh_msg' => json_encode($responseBody, 256), 'refresh_time' => date('Y-m-d H:i:s')];
            $res = $this->query->where('id', $id)->update($this->tableName(), $data);
        } catch (\Exception $e) {

        }
    }

    public function checkOrder($params)
    {
        try {

        } catch (\Exception $e) {

        }
    }

    public function checkException($params)
    {
        try {

        } catch (\Exception $e) {

        }
    }

}