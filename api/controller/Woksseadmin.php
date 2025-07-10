<?php

namespace wokmansse\api\controller;

use think\Controller;
use wokmansse\common\logic\SseApp;
use wokmansse\common\logic\SseUser;

/**
 * 管理接口
 */
class Woksseadmin extends Controller
{
    /**
     * Undocumented variable
     *
     * @var SseApp
     */
    protected $appLogic;

    /**
     * Undocumented variable
     *
     * @var SseUser
     */
    protected $userLogic;

    protected function initialize()
    {
        $this->appLogic = new SseApp;
        $this->userLogic = new SseUser;
    }

    /**
     * Undocumented function
     *
     * @param array|null $data
     * @return array
     */
    private function validateApp($data = null)
    {
        if ($data == null) {
            $data = request()->post();
        }

        if (isset($data['secret'])) {
            return ['code' => 0, 'msg' => '不要传secret参数'];
        }

        $result = $this->validate($data, [
            'app_id|应用app_id' => 'require|number',
            'sign|sign签名' => 'require',
            'time|时间戳' => 'require|number',
        ]);

        if ($result !== true) {
            return ['code' => 0, 'msg' => $result];
        }

        $res = $this->appLogic->validateApp($data['app_id'], $data['sign'], $data['time']);

        return $res;
    }

    /**
     * 同步用户信息
     * 把外部系统用户推送到系统
     * 
     * @return mixed
     */
    public function pushUser()
    {
        $data = request()->post();

        $valdate = $this->validateApp($data);

        if ($valdate['code'] != 1) {
            return json($valdate);
        }

        $result = $this->validate($data, [
            'uid|用户uid' => 'require|number',
            //'nickname|用户昵称' => 'require',
            //'remark|用户备注' => 'require',
            //'token|用户token' => 'require'
        ]);

        if ($result !== true) {
            return json([
                'code' => 0,
                'msg' => $result
            ]);
        }

        if (!isset($data['token'])) {
            $data['token'] = '';
        }

        if (!isset($data['remark'])) {
            $data['remark'] = $data['nickname'];
        }

        $res = $this->appLogic->pushUser($data['uid'], $data['nickname'], $data['remark'], $data['token']);

        return json($res);
    }

    /**
     * 创建消息
     *
     * @return mixed
     */
    public function pushMsg()
    {
        $data = request()->post();

        $valdate = $this->validateApp($data);
        if ($valdate['code'] != 1) {
            return json($valdate);
        }

        $result = $this->validate($data, [
            'uid|接收用户uid' => 'require',
            'data|发送内容' => 'require',
        ]);

        if ($result !== true) {
            return json($valdate);
        }

        $client = stream_socket_client('tcp://127.0.0.1:11330', $errno, $errstr, 1);

        $data = ['action' => 'push_msg', 'uid' => $data['uid'], 'app_id' => $data['app_id'], 'data' => $data['data']];

        fwrite($client, json_encode($data) . "\n");
        // 读取推送结果
        $result = fread($client, 8192);
        if ($result && trim($result) == 'done') {
            return json([
                'code' => 1,
                'msg' => 'done'
            ]);
        }

        return json([
            'code' => 1,
            'msg' => 'failed',
            'result' => $result
        ]);
    }
}
