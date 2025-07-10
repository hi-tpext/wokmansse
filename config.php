<?php

return [
    'port' => 22990,
    'user' => 'www',
    'group' => 'www',
    'sign_timeout' => 60,
    //
    //配置描述
    '__config__' => [
        'port' => ['type' => 'text', 'label' => '端口号', 'size' => [2, 8], 'help' => '1000~65535'],
        'user' => ['type' => 'text', 'label' => '运行用户', 'size' => [2, 8], 'help' => '(linux系统有效)一般为www或www-data，确保系统中用户存在，或者留空'],
        'group' => ['type' => 'text', 'label' => '运行用户组', 'size' => [2, 8], 'help' => '(linux系统有效)一般为www或www-data，确保系统中分组存在，或者留空'],
        'sign_timeout' => ['type' => 'text', 'label' => '设备时间误差', 'size' => [2, 8], 'help' => '允许的时间误差，当客户端与服务器时间不同步超过值时sign会验证失败'],
    ],
];
