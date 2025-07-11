<?php

namespace wokmansse\common;

use think\worker\Manager;
use think\worker\Worker;
use wokmansse\sse\Index;

## tp8 自定义worker
## 监听`worker.init`事件 注入`Manager`对象，调用addWorker方法添加

/**
 * Undocumented class
 */
class WorkerInit extends Index
{
    public function handle(Manager $manager)
    {
        $manager->addWorker([$this, 'createHttpServer'], 'wokmansse', 1);
    }

    public function createHttpServer()
    {
        $config = Module::getInstance()->getConfig();

        $host = '0.0.0.0';
        $port = $config['port'] ?: 22990;

        $server = new Worker("http://{$host}:{$port}");

        if ($config['user']) {
            $server->user = $config['user'];
        }
        if ($config['group']) {
            $server->group = $config['group'];
        }

        $callbackMap = [
            'onConnect',
            'onMessage',
            'onClose',
            'onError',
            'onBufferFull',
            'onBufferDrain',
            'onWorkerStop',
            'onWebSocketConnect',
            'onWorkerReload'
        ];

        foreach ($callbackMap as $name) {
            if (method_exists($this, $name)) {
                $server->$name = [$this, $name];
            }
        }

        if (method_exists($this, 'onWorkerStart')) {
            call_user_func([$this, 'onWorkerStart'], $server);
        }

        $server->listen();
    }
}
