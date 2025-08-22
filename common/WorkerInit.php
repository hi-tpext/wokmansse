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
    protected $config = [];
    protected $deployModel = 0;
    protected $port = 0;

    public function handle(Manager $manager)
    {
        $this->config = Module::getInstance()->getConfig();
        $this->deployModel = $this->config['deploy_model'] ?? 0;
        $this->port = $this->config['port'] ?: 22990;

        if ($this->deployModel == 1) {
            $manager->addWorker([$this, 'createHttpServer'], 'wokmansse:' . $this->port, 1);
        } else if ($this->deployModel == 2) {
            $manager->addWorker([$this, 'createHttpServer'], 'wokmansse-ws:' . $this->port, 1);
        } else {
            $manager->addWorker([$this, 'createHttpServer'], 'wokmansse:' . $this->port, 1);
            $manager->addWorker([$this, 'createHttpServer'], 'wokmansse-ws:' . ($this->port + 1), 1);
        }
    }

    public function createHttpServer($worker)
    {
        $host = '0.0.0.0';
        $socketName = '';

        if ($this->deployModel == 1) {
            $socketName = "http://{$host}:{$this->port}";
        } else if ($this->deployModel == 2) {
            $socketName = "websocket://{$host}:{$this->port}";
        } else {
            if (strstr($worker->name, 'wokmansse:')) {
                $socketName = "http://{$host}:{$this->port}";
            } else {
                $this->port += 1;
                $socketName = "websocket://{$host}:{$this->port}";
            }
        }

        $server = new Worker($socketName);

        if ($this->config['user']) {
            $server->user = $this->config['user'];
        }
        if ($this->config['group']) {
            $server->group = $this->config['group'];
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

        if ($this->deployModel == 0) {
            $server->reusePort = true;
        }

        $server->listen();
    }
}
