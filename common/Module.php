<?php

namespace wokmansse\common;

use tpext\common\Module as baseModule;
use tpext\common\ExtLoader;

/**
 * Undocumented class
 */
class Module  extends baseModule
{
    protected $version = '1.0.1';

    protected $name = 'wokman.sse';

    protected $title = 'workerman推送';

    protected $description = '基于workerman实现的SSE(Server-Sent Events)推送';

    protected $root = __DIR__ . '/../';

    protected $modules = [
        'admin' => ['woksseapp', 'woksseuser'],
        'api' => ['woksseadmin'],
    ];

    protected $versions = [
        '1.0.1' => '',
    ];

    /**
     * 后台菜单
     *
     * @var array
     */
    protected $menus = [
        [
            'title' => '推送管理',
            'sort' => 1,
            'url' => '#',
            'icon' => 'mdi mdi-mixcloud',
            'children' => [
                [
                    'title' => '应用管理',
                    'sort' => 1,
                    'url' => '/admin/woksseapp/index',
                    'icon' => 'mdi mdi-apple-keyboard-command',
                ],
                [
                    'title' => '用户管理',
                    'sort' => 2,
                    'url' => '/admin/woksseuser/index',
                    'icon' => 'mdi mdi-account-outline',
                ]
            ],
        ]
    ];

    /**
     * 默认的configPath()是composer模式带`src`的，extend模式没有src所以重写一下。
     * 不重写此方法也可以，创建一个`src`目录把config.php放里面
     *
     * @return string
     */
    public function configPath()
    {
        return realpath($this->getRoot() . 'config.php');
    }

    /**
     * Undocumented function
     *
     * @return boolean
     */
    public function install()
    {
        if (!ExtLoader::isWebman() && !class_exists('\\think\\worker\\Worker')) { //根据think-worker中某一个类是否存在来判断sdk是否已经安装

            $this->errors[] = new \Exception('<p>仅支付TP8以上版本，请使用composer安装think-worker后再安装本扩展！</p><pre>composer require topthink/think-worker:^5.*</pre>');

            return false;
        }

        return parent::install();
    }
}
