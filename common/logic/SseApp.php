<?php

namespace wokmansse\common\logic;

use wokmansse\common\model;
use wokmansse\common\Module;

/**
 * 封装后台操作，添加用户、修改用户
 */

class SseApp
{
    protected $app_id = 0;

    /**
     * Undocumented variable
     *
     * @var model\WokSseApp
     */
    protected $app = null;

    protected $config = [];

    public function __construct()
    {
        $this->config = Module::getInstance()->getConfig();
    }

    /**
     * Undocumented function
     *
     * @param int $app_id
     * @param string $sign
     * @param int $time
     * @return array
     */
    public function validateApp($app_id, $sign, $time)
    {
        if (empty($app_id) || empty($sign) || empty($time)) {
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
            return ['code' => 0, 'msg' => '应用未开启'];
        }

        if (empty($app['secret'])) {
            return ['code' => 0, 'msg' => '系统错误，secret配置有误'];
        }

        if ($sign != md5($app['secret'] . $time)) {
            return ['code' => 0, 'msg' => 'sign验证失败'];
        }

        unset($app['secret']);

        $this->app = $app;
        $this->app_id = $app_id;

        return ['code' => 1, 'msg' => '成功'];
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    public function isValidateApp()
    {
        if (empty($this->app) || empty($this->app_id)) {
            return ['code' => 0, 'msg' => 'app验证未通过'];
        }

        return ['code' => 1, 'msg' => '成功'];
    }

    // 过滤掉emoji表情
    protected function filterEmoji($str)
    {
        $str = preg_replace_callback(    //执行一个正则表达式搜索并且使用一个回调进行替换
            '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '?' : $match[0];
            },
            $str
        );

        return $str;
    }

    /**
     * Undocumented function
     *
     * @param string $uid
     * @param string $nickname
     * @param string $remark
     * @param string $token
     * @param string $group
     * @return array
     */
    public function pushUser($uid, $nickname, $remark, $token = '', $group = '')
    {
        $valdate = $this->isValidateApp();

        if ($valdate['code'] != 1) {
            return $valdate;
        }

        if (empty($remark)) {
            $remark = $nickname;
        }

        if ($exist = model\WokSseUser::where(['app_id' => $this->app_id, 'uid' => $uid])->find()) {

            //未传递token，
            if (empty($token)) {
                if ($exist['token']) {
                    $token = $exist['token'];
                } else { //生成一个
                    $token = md5(__FILE__ . $this->app_id . $uid . time() . mt_rand(100, 999));
                }
            }

            $res = $exist->save([
                'nickname' => $this->filterEmoji($nickname),
                'remark' => $this->filterEmoji($remark),
                'token' => $token,
                'group' => $group,
            ]);

            if ($res) {
                return ['code' => 1, 'msg' => '成功', 'data' => ['token' => $token]];
            }

            return ['code' => 0, 'msg' => '保存失败',  'data' => ''];
        }

        //未传递token，生成一个
        if (empty($token)) {
            $token = md5($this->app_id . $uid . time() . mt_rand(100, 999));
        }

        $user = new model\WokSseUser;

        $data = [
            'app_id' => $this->app_id,
            'uid' => $uid,
            'nickname' => $this->filterEmoji($nickname),
            'remark' => $this->filterEmoji($remark),
            'token' => $token,
            'group' => $group,
        ];

        $res = $user->save($data);

        if ($res) {
            return ['code' => 1, 'msg' => '成功', 'data' => ['token' => $token]];
        }

        return ['code' => 0, 'msg' => '添加失败', 'data' => ''];
    }
}
