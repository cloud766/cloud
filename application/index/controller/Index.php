<?php
namespace app\index\controller;

use app\common\controller\HomeBase;
use think\Db;

class Index extends HomeBase
{

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        $templateList = Db::name('yun_template')->order('sale_num desc')->limit(5)->where(['status' => 1])->select();
        $addonList = Db::name('yun_addon')->order('sale_num desc')->limit(5)->where(['status' => 1])->select();
        $this->assign('templateList', $templateList);
        $this->assign('addonList', $addonList);
        return $this->fetch();
    }

    public function domainCheck()
    {
        $url = $this->request->param('domain');
        if ($url) {
            $auth = db('yun_source_code_auth')->alias('ysca')
                ->join('__YUN_WEBSITE__ yw', 'yw.id=ysca.website_id', 'LEFT')
                ->where('yw.domain like "%' . $domain . '%"')
                ->field('ysca.*')
                ->find();
            if ($auth) {
                return json(true);
            } else {
                return json(false);
            }
        } else {
            return json(false);
        }
    }

    public function about()
    {
        return $this->fetch();
    }

    public function codeDownload()
    {
        return $this->fetch();
    }

    public function downloadCode()
    {
        $file_dir = ROOT_PATH . 'data' . DS . 'source_code' . DS . 'HXCMS' . DS . '1.2' . DS . 'hxcms.zip';
        if (!file_exists($file_dir)) {
            $this->error('文件未找到');
        } else {
            // 打开文件
            $file1 = fopen($file_dir, "r");
            // 输入文件标签
            Header("Content-type: application/octet-stream");
            Header("Accept-Ranges: bytes");
            Header("Accept-Length:" . filesize($file_dir));
            Header("Content-Disposition: attachment;filename=hxcms.zip");
            ob_clean();     // 重点！！！
            flush();        // 重点！！！！可以清除文件中多余的路径名以及解决乱码的问题：
            //输出文件内容
            //读取文件内容并直接输出到浏览器
            echo fread($file1, filesize($file_dir));
            fclose($file1);
            exit();
        }
    }

    public function codeAuth()
    {
        return $this->fetch();
    }

    public function buy_auth()
    {
        return $this->fetch();
    }

    public function license()
    {
        $url = $this->request->param('url');
        $auth = true;
        if ($url) {
            $auth = db('yun_source_code_auth')->alias('ysca')
                ->join('__YUN_WEBSITE__ yw', 'yw.id=ysca.website_id', 'LEFT')
                ->where('yw.domain like "%' . $url . '%"')
                ->field('ysca.*')
                ->find();
            if (!$auth) {
                $auth = false;
            }
        } else {
            $auth = false;
        }
        $this->assign('url', $url);
        $this->assign('auth', $auth);
        return $this->fetch();
    }

}
