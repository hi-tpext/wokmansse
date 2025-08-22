<?php

namespace wokmansse\common\logic;

use think\Validate;
use think\facade\Db;
use Workerman\Timer;
use think\facade\Log;
use Workerman\Worker;
use think\facade\Cache;
use think\facade\Config;
use tpext\common\ExtLoader;
use think\exception\ValidateException;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Protocols\Http\ServerSentEvents;

class Sse
{
    public const HEARTBEAT_TIME = 60;

    protected $appConnections = [];

    /**
     * Undocumented variable
     *
     * @var SseUser
     */
    protected $userLogic;

    protected $innerTextWorker = null;

    protected $isWebSocket = false;

    /**
     * Undocumented function
     *
     * @param TcpConnection $connection
     * @param Request|string $request
     * @return void
     */
    public function onMessage($connection, $request)
    {
        $connection->lastMessageTime = time();
        $data = $this->isWebSocket ? json_decode($request, true) : $request->get();

        if (!empty($data) && !empty($data['app_id']) && !empty($data['uid']) && !empty($data['sign']) && !empty($data['time'])) {
            $res = $this->login($connection, $data);
            if (!empty($connection->app_id) && !empty($connection->uid)) {
                if ($this->isWebSocket) {
                    Timer::del($connection->auth_timer_id); //验证成功，删除定时器，防止连接被关闭
                    // 发送一个登录成功事件
                    $connection->send(json_encode($res, JSON_UNESCAPED_UNICODE));
                } else {
                    if ($request->header('accept') === 'text/event-stream') { //sse
                        // 发送一个 Content-Type: text/event-stream 头的响应
                        $response = new Response(200, ['Content-Type' => 'text/event-stream'], "\r\n");
                        $this->setHeaders($response, $request->header('origin'));
                        $connection->send($response);
                        // 发送一个登录成功事件
                        $connection->send(new ServerSentEvents(['event' => 'message', 'data' => json_encode($res, JSON_UNESCAPED_UNICODE)]));
                    }
                }
                return;
            }

            $res = json_encode($res, JSON_UNESCAPED_UNICODE);
            $response = new Response(200, ['Content-Type' => 'application/json'], $res);
            $this->setHeaders($response, $request->header('origin'));
            $connection->close($response);
        }
        if (!$this->isWebSocket) {
            $connection->close("HTTP/1.0 400 Bad Request\r\nServer: workerman\r\n\r\n<div style=\"text-align:center\"><h1>ServerSentEvents</h1><hr>workerman</div>", true);
        }
    }

    /**
     * Summary of setHeaders
     * @param Response $response
     * @param string $origin
     * 
     * @return void
     */
    protected function setHeaders($response, $origin = '')
    {
        $response->header('Access-Control-Allow-Origin', $origin ?? '*');
        $response->header('Access-Control-Allow-Credentials', 'false');
        $response->header('Access-Control-Max-Age', 86400);
        $response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    }

    /**
     * Summary of login
     * @param TcpConnection $connection
     * @param array $data
     * 
     * @return array{code: int, msg: array|bool|string}
     */
    protected function login($connection, $data)
    {
        $result = $this->validate($data, [
            'app_id|应用app_id' => 'require|number',
            'uid|用户id' => 'require|number',
            'sign|sign签名' => 'require',
            'time|时间戳' => 'require|number',
        ]);

        if ($result !== true) {
            return ['code' => 0, 'msg' => $result];
        }

        $res = $this->userLogic->validateUser($data['app_id'], $data['uid'], $data['sign'], $data['time']);

        if ($res['code'] == 1) {
            //登录成功，设置session
            $app_id = $res['user']['app_id'];
            $uid = $res['user']['uid'];

            if (!isset($this->appConnections[$app_id])) {
                $this->appConnections[$app_id] = [
                    $uid => [],
                ];
            }

            $connection->app_id = $app_id;
            $connection->uid = $uid;

            $this->appConnections[$app_id][$uid][$connection->id] = $connection;

            return ['code' => 1, 'msg' => '登录成功', 'event' => 'login_succeed'];
        }

        return ['code' => 0, 'msg' => '登录失败-' . $res['msg'], 'event' => 'login_failed'];
    }

    public function onWorkerStart($worker)
    {
        $socketName = $worker->getSocketName();

        $this->isWebSocket = stripos($socketName, 'websocket') !== false;

        Log::info("wokmansse onWorkerStart:" . $worker->name);

        $this->initDb();

        $this->userLogic = new SseUser;

        $this->heartBeat($worker);

        $this->innerWoker();
    }

    /**
     * 心跳
     */
    protected function heartBeat($worker)
    {
        if (!ExtLoader::isWebman()) {
            Timer::add(50, function () {
                Cache::get('ping');
                Db::query('SELECT 1'); //保存数据库连接
            });
        }

        if ($this->isWebSocket) {
            Timer::add(10, function () use ($worker) {
                $timeNow = time();
                foreach ($worker->connections as $connection) {
                    // 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
                    if (empty($connection->lastMessageTime)) {
                        $connection->lastMessageTime = $timeNow;
                        continue;
                    }
                    // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
                    if ($timeNow - $connection->lastMessageTime > static::HEARTBEAT_TIME) {
                        $data = ['code' => 1, 'msg' => 'bye'];
                        $connection->send(json_encode($data));
                        $connection->close();
                    }
                }
            });
        } else {
            Timer::add(300, function () use ($worker) {
                foreach ($worker->connections as $connection) {
                    $connection->send(new ServerSentEvents([
                        'event' => 'message',
                        'data' => json_encode(['event' => 'ping', 'msg' => '每5分钟发送一次，中断则说明掉线'], JSON_UNESCAPED_UNICODE)
                    ]));
                }
            });
        }
    }

    /**
     * Undocumented function
     *
     * @param TcpConnection $connection
     * @return void
     */
    public function onConnect($connection)
    {
        $connection->maxSendBufferSize = 4 * 1024 * 1024; //4MB，防止数据截断(默认1MB)

        if ($this->isWebSocket) {
            // 临时给$connection对象添加一个auth_timer_id属性存储定时器id
            // 定时10秒关闭连接，需要客户端10秒内完成用户登陆验证
            $connection->auth_timer_id = Timer::add(10, function () use ($connection) {
                $connection->close();
            }, null, false);
        }
    }

    /**
     * Undocumented function
     *
     * @param TcpConnection $connection
     * @return void
     */
    public function onClose($connection)
    {
        if (isset($connection->app_id) && isset($connection->uid)) {
            // 连接断开时删除映射
            // $connection->id 为`workerman`框架自带属性
            unset($this->appConnections[$connection->app_id][$connection->uid][$connection->id]);

            $connection->uid = 0;
            $connection->app_id = 0;
        }
    }

    public function onWorkerReload($worker)
    {
        Log::info("wokmansse onWorkerReload");

        $this->initDb();
    }

    /**
     * 当客户端的连接上发生错误时触发
     * @param TcpConnection $connection
     * @param string $code
     * @param string $msg
     */
    public function onError($connection, $code, $msg)
    {
        Log::error("wokmansse error $code $msg");
    }

    /**
     * Undocumented function
     * 
     * @param int $app_id
     * @param string $uid
     * @param array|string $data
     * @return boolean
     */
    protected function sendMessageByUid($app_id, $uid, $data)
    {
        if (!is_string($data)) {
            $data = json_encode($data);
        }

        $uids = explode(',', $uid);

        $num = 0;
        foreach ($uids as $uid) {
            if (isset($this->appConnections[$app_id]) && isset($this->appConnections[$app_id][$uid])) {
                $userConnections = $this->appConnections[$app_id][$uid];
                foreach ($userConnections as $conn) {
                    if ($this->isWebSocket) {
                        if (!is_string($data)) {
                            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
                        }
                        $conn->send($data);
                    } else {
                        $conn->send(new ServerSentEvents(['event' => 'message', 'data' => $data]));
                    }
                }
                $num += 1;
            }
        }

        return $num > 0;
    }

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate($data, $validate, $message = [])
    {
        $v = new Validate;
        $v->rule($validate);

        if (is_array($message)) {
            $v->message($message);
        }

        if (!$v->check($data)) {
            return $v->getError();
        }

        return true;
    }

    // 开启一个内部端口，方便内部系统推送数据，Text协议格式 文本+换行符
    protected function innerWoker()
    {
        $that = $this;

        $count = 2;
        $port = $this->isWebSocket ? 11330 : 11331;

        $this->innerTextWorker = new Worker("Text://127.0.0.1:{$port}");
        $this->innerTextWorker->count = $count;
        $this->innerTextWorker->reusePort = true;

        $this->innerTextWorker->onMessage = function ($connection, $data = '{}') use ($that) {
            $data = json_decode($data, true);
            if (!empty($data) && isset($data['action']) && $data['action'] == 'push_msg') { //通过管理员接口添加消息
                $res = $that->sendMessageByUid($data['app_id'], $data['uid'], $data['data']);
                if ($res) {
                    $connection->send('done');
                    return;
                }
            }
            $connection->send('failed');
        };

        $this->innerTextWorker->listen();

        echo ($this->isWebSocket ? 'Wokman-ws' : 'Wokman-sse') . " innerWoker\t\tText://127.0.0.1:{$port}\t\t{$count}\t\t[ok]\n";
    }

    protected function initDb()
    {
        if (ExtLoader::isWebman()) {
            //无需处理数据库
        } else if (ExtLoader::isTP60()) {
            $config = array_merge(Config::get('database.connections.mysql'), ['break_reconnect' => true]);

            Db::connect('mysql')->connect($config);
        }
    }
}
