# workman-sse

## workman推送系统

### 请使用composer安装**think-worker**后再安装本扩展

#### tp8.0

注：think-worker(5.x) 不支持windows环境，请在linux环境下安装。

```bash
composer require topthink/think-worker:^5.0
```

### 使用

#### 修改配置

`/app/event.php`

```php
<?php
// 事件定义文件
return [
    'bind'      => [
    ],

    'listen'    => [
        'AppInit'  => [],
        'HttpRun'  => [],
        'HttpEnd'  => [],
        'LogLevel' => [],
        'LogWrite' => [],
        // 新增worker.init事件，用于初始化worker
        'worker.init' => ['wokmansse\\common\\WorkerInit'],
    ],

    'subscribe' => [
    ],
];
```

#### webman修改配置

`/config/process.php`

```php
 return [
    //....其它配置，这里省略....
    'wokmansse'  => [
        'handler'  => 'wokmansse\\sse\\Index',
        'listen'  => 'http://0.0.0.0:22990',
        'count' => 1, // 进程数(只能是1)
        'user' => 'www',
        'group' => 'www',
    ],
];
//修改完重启webman
```

#### 环境要求

需要使用以下php方法，确保以下方法未被禁用：

```bash
pcntl_wait
pcntl_signal
pcntl_fork
pcntl_signal_dispatch
pcntl_alarm
其他（待补充）
```

#### tp 启动脚本,start.sh

```bash
COUNT1=`ps -ef |grep WorkerMan|grep -v "grep" |wc -l`;

echo $COUNT1

if [ $COUNT1 -eq 0 ];then

    cd /www/wwwroot/www.localhost.com

    php83 think worker # 宝塔安装了php多版本，可使用php83代表php 8.3

    #/www/server/php/83/bin/php think worker # 或者可使用php绝对路径

fi
```

如果需要使用守护进程方式运行，建议使用supervisor来管理进程

#### 启动成功

在linux终端执行以下命令，以判断启动成功

`ps aux | grep WorkerMan`

如果输出类似以下，说明启动成功。

```bash
root      132200  0.0  0.1 217728 13776 ?        S    11:43   0:00 WorkerMan: master process  start_file=/www/wwwroot/www.localhost.com/think
www       133280  0.0  0.2 218316 22000 ?        S    11:55   0:00 WorkerMan: worker process  wokmansse websocket://0.0.0.0:22990
```

如果只有第一条[master process]没有[worker process]，则是启动失败，请到网站的`runimeme`目录里面查看`worker22990.stdout.log`日志分析原因。

使用文档和api文档见 [doc.md](doc.md)
