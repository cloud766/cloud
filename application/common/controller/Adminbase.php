<?php
namespace app\common\controller;

use think\Request;
use app\common\controller\Base;
use think\Image;

class Adminbase extends Base
{
    //后台管理基类
    public function _initialize()
    {
        parent::_initialize();
        $header = $this->request->header();
        if($header['host'] != 'cloud.dayongjiaoyu.cn'){
            exit();
        }
        if (!session('uid')) {
            $this->redirect(url('login/index'));
        } else {
            //这里记住现在登录的id
            $this->assign('hx_uid', session('uid'));
            $this->assign('hx_username', session('username'));
        }
        $this->getConf();
    }

    //这里写入配置数据
    public function getConf()
    {
        $config = get_config(['group' => 'web_info']);
        $this->assign('config', $config);
        return $config;
    }
}
