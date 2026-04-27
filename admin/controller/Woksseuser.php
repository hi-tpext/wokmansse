<?php

namespace wokmansse\admin\controller;

use wokmansse\common\model\WokSseUser as WokSseUserModel;
use think\Controller;
use tpext\builder\traits\actions;
use think\facade\Lang;
use wokmansse\common\Module;
use tpext\think\App;

/**
 * @time tpextmanager 生成于2021-08-06 17:23:30
 * @title 推送用户
 */
class Woksseuser extends Controller
{
    use actions\HasBase;
    use actions\HasIndex;
    use actions\HasAdd;
    use actions\HasEdit;
    use actions\HasView;
    use actions\HasDelete;

    /**
     * Undocumented variable
     * @var WokSseUserModel
     */
    protected $dataModel;

    protected function initialize()
    {
        $this->dataModel = new WokSseUserModel;
        $this->pageTitle = '推送用户';
        $this->selectTextField = '{app_id}#{uid}:{nickname}';
        $this->selectSearch = 'uid|nickname';
        $this->pk = 'id';
        $this->pagesize = 10;
        $this->sortOrder = 'id desc';

        $this->indexWith = ['app'];

        Lang::load(Module::getInstance()->getRoot() . implode(DIRECTORY_SEPARATOR, ['admin', 'lang', App::getDefaultLang(), 'woksseuser' . '.php']));
    }

    /**
     * 构建搜索
     * @return mixed
     */
    protected function buildSearch()
    {
        $search = $this->search;

        $search->defaultDisplayerColSize(3);

        $search->select('app_id')->dataUrl(url('/admin/woksseapp/selectpage'));
        $search->text('nickname');
        $search->text('remark');
        $search->text('group');
        $search->text('uid');
    }

    /**
     * 构建搜索条件
     * @return mixed
     */
    protected function filterWhere()
    {
        $searchData = request()->get();
        $where = [];
        if (isset($searchData['app_id']) && $searchData['app_id'] != '') {
            $where[] = ['app_id', '=', $searchData['app_id']];
        }
        if (isset($searchData['nickname']) && $searchData['nickname'] != '') {
            $where[] = ['nickname', 'like', '%' . trim($searchData['nickname']) . '%'];
        }
        if (isset($searchData['remark']) && $searchData['remark'] != '') {
            $where[] = ['remark', 'like', '%' . trim($searchData['remark']) . '%'];
        }
        if (isset($searchData['group']) && $searchData['group'] != '') {
            $where[] = ['group', 'like', '%' . trim($searchData['group']) . '%'];
        }
        if (isset($searchData['uid']) && $searchData['uid'] != '') {
            $where[] = ['uid', '=', $searchData['uid']];
        }

        return $where;
    }

    /**
     * 构建表格
     * @param array $data
     * @return mixed
     */
    protected function buildTable(&$data = [], $isExporting = false)
    {
        $table = $this->table;

        $table->show('id');
        $table->show('nickname');
        $table->show('app_id')->to('{app_id}#{app.name}');
        $table->show('remark');
        $table->show('group');
        $table->show('uid');

        $table->show('login_time');
        $table->show('create_time');
        $table->show('update_time');

        $table->getToolbar()
            ->btnAdd()
            ->btnDelete()
            ->btnRefresh();

        $table->getActionbar()
            ->btnEdit()
            ->btnView()
            ->btnDelete();

        $table->sortable('id,app_id,uid');
    }

    /**
     * 构建搜索条件
     * @param boolean $isEdit
     * @param array $data
     * @return mixed
     */
    protected function buildForm($isEdit, &$data = [])
    {
        $form = $this->form;

        $form->hidden('id');

        $form->text('uid')->required();
        $form->select('app_id')->dataUrl(url('/admin/woksseapp/selectpage'))->required();
        $form->text('nickname')->maxlength(55)->required();
        $form->textarea('remark')->maxlength(55)->required();
        $form->text('group')->maxlength(50)->required();

        if ($isEdit) {
            $form->show('create_time');
            $form->show('update_time');
        }
    }

    /**
     * 保存数据
     * @param integer $id
     * @return mixed
     */
    private function save($id = 0)
    {
        $data = request()->post();

        $result = $this->validate($data, [
            'nickname|昵称' => 'require',
            'uid|用户uid' => 'require',
            'app_id|应用app_id' => 'require',
        ]);

        if (true !== $result) {
            $this->error($result);
        }

        return $this->doSave($data, $id);
    }
}
