<?php
namespace app\common\controller;

use think\Controller;

class base extends Controller
{

    public function _initialize()
    {
        // $dispatch = $this->request->dispatch();
        // $header = $this->request->header();
        // if ($dispatch['module'][0] == 'api') {
        //     if ($header['host'] != 'api.dayongjiaoyu.cn') {
        //         exit();
        //     }
        // } else {
        //     if ($header['host'] != 'store.dayongjiaoyu.cn') {
        //         $this->redirect('http://store.dayongjiaoyu.cn');
        //     }
        // }
    }

}
