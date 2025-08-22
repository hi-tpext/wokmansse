<?php

use tpext\builder\common\Form;
use tpext\common\ExtLoader;

return [
    'port' => 22990,
    'user' => 'www',
    'group' => 'www',
    'sign_timeout' => 60 * 60 * 24,
    'deploy_model' => 0,
    //配置描述
    '__config__' => function (Form $form) {
        if (!ExtLoader::isWebman()) {
            $form->text('port', '端口号')->help('1000~65535');
            $form->text('user', '运行用户')->help('(linux系统有效)一般为www或www-data，不确定则留空');
            $form->text('group', '运行用户组')->help('(linux系统有效)一般为www或www-data，不确定则留空');
        } else {
            $form->raw('tips', '提示')->value('<p>进程配置信息在`/config/process.php`中设置</p>');
        }
        $form->radio('deploy_model', '部署模式')->options([1 => 'sse', 2 => 'ws', 0 => '都有']);
        $form->text('sign_timeout', '设备时间误差')->help('允许的时间误差，当客户端与服务器时间不同步超过值时sign会验证失败');
    },
];
