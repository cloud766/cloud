<?php
namespace app\common\controller;

use app\common\controller\Base;

class HomeBase extends Base
{
    public function _initialize()
    {
        parent::_initialize();
        $header = $this->request->header();
        if($header['host'] != 'store.dayongjiaoyu.cn'){
            exit();
        }
        $configParent = db('config')->where(['pid' => 0])->select();
        $configList = [];
        foreach ($configParent as $key => $value) {
            $config = get_config(['group' => $value['name']]);
            $configList[$value['name']] = $config;
        }
        $this->configList = $configList;

        $config = $configList['web_info'];
        if ($config['open_site'] != '0') {
            $this->error('站点已关闭', '');
        }
        $this->assign('configList', $configList);
        $this->assign('user', session('user'));
    }
}
