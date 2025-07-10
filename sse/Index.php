<?php

namespace wokmansse\sse;

use wokmansse\common\logic;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

class Index
{
    /**
     * @var logic\Sse
     */
    protected $sseLogic = null;

    /**
     * Undocumented function
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @return void
     */
    public function onMessage($connection, $request)
    {
        $this->sseLogic->onMessage($connection, $request);
    }

    public function onWorkerStart($worker)
    {
        $this->sseLogic = new logic\Sse;
        $this->sseLogic->onWorkerStart($worker);
    }

    /**
     * Undocumented function
     *
     * @param TcpConnection $connection
     * @return void
     */
    public function onConnect($connection)
    {
        $this->sseLogic->onConnect($connection);
    }

    /**
     * Undocumented function
     *
     * @param TcpConnection $connection
     * @return void
     */
    public function onClose($connection)
    {
        $this->sseLogic->onClose($connection);
    }

    public function onWorkerReload($worker)
    {
        $this->sseLogic->onWorkerReload($worker);
    }

    /**
     * 当客户端的连接上发生错误时触发
     * @param TcpConnection $connection
     * @param string $code
     * @param string $msg
     */
    public function onError($connection, $code, $msg)
    {
        $this->sseLogic->onError($connection, $code, $msg);
    }
}
