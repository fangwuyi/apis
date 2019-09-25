<?php
/**
 * 首页资讯
 * author: wuyi
 * Date: 2019-3-12
 */
namespace app\api\controller;

use think\worker\Server;
class Worker extends Server {

    protected $socket = 'http://0.0.0.0:2346';

    public function onConnect($connection,$data)
    {
        echo '12321';
        $connection->send(json_encode($data));
    }

    public function onClose($connection,$data)
    {
        $connection->send(json_encode($data));
    }

    public function onMessage($connection,$data)
    {
        $connection->send(json_encode($data));
    }

    public function onError($connection, $code, $msg)
    {
        echo "error $code $msg\n";
    }

    public function onWorkerStart($worker)
    {
 
    }


}