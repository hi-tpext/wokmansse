<?php

namespace wokmansse\common\logic;

use wokmansse\common\model;
use wokmansse\common\Module;

/**
 * 封装前台操作，用户推送
 */
class SseUser
{
    protected $config = [];

    public function __construct()
    {
        $this->config = Module::getInstance()->getConfig();
    }

    /**
     * Undocumented function
     *
     * @param int $app_id
     * @param int $uid
     * @param string $sign
     * @param int $time
     * @return array
     */
    public function validateUser($app_id, $uid, $sign, $time)
    {
        if (empty($app_id) || empty($uid) || empty($sign) || empty($time)) {
            return ['code' => 0, 'msg' => '参数错误'];
        }

        $sign_timeout = intval($this->config['sign_timeout'] ?? 60);

        if ($sign_timeout < 10) {
            $sign_timeout = 10;
        }

        if (abs(time() - $time) > $sign_timeout) {
            return ['code' => 0, 'msg' => 'sign超时请检查设备时间'];
        }

        $app = model\WokSseApp::where('id', $app_id)->find();

        if (!$app) {
            return ['code' => 0, 'msg' => 'app_id:应用未找到'];
        }

        if ($app['enable'] == 0) {
            return ['code' => 0, 'msg' => '推送应用未开启'];
        }

        $user = model\WokSseUser::where(['app_id' => $app_id, 'uid' => $uid])->find();

        if (!$user) {
            return ['code' => 0, 'msg' => 'uid:用户未找到' . $uid . '-' . $app_id];
        }

        if (empty($user['token'])) {
            return ['code' => 0, 'msg' => '用户token未设置'];
        }

        if ($sign != md5($user['token'] . $time)) {
            return ['code' => 0, 'msg' => 'sign验证失败'];
        }

        $user->save(['login_time' => date('Y-m-d H:i:s')]);

        unset($user['token']);

        return ['code' => 1, 'msg' => '成功', 'user' => $user];
    }
}
