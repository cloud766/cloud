<?php
namespace app\api\controller;

use app\common\controller\Base;
use think\Db;

class Download extends Base
{
    public function sourcecode()
    {
        $file_dir = ROOT_PATH . 'data/source_code/HXCMS/1.2/hxcms1.2-install.zip';
        if (!file_exists($file_dir)) {

            $this->error('文件未找到');

        } else {

            // 打开文件

            $file1 = fopen($file_dir, "r");

            // 输入文件标签

            Header("Content-type: application/octet-stream");

            Header("Accept-Ranges: bytes");

            Header("Accept-Length:" . filesize($file_dir));

            Header("Content-Disposition: attachment;filename=" . $file_dir);

            ob_clean();     // 重点！！！

            flush();        // 重点！！！！可以清除文件中多余的路径名以及解决乱码的问题：

            //输出文件内容

            //读取文件内容并直接输出到浏览器

            echo fread($file1, filesize($file_dir));

            fclose($file1);

            exit();

        }
    }

    /**
     * 安装统计，记录站点安装源码的信息
     * @return [type] [description]
     */
    public function codeInstallStatistics()
    {
        $param = $this->request->param();
        if (!$param['sourcecode_id']) {
            return json(['code' => 0, 'msg' => '缺少参数sourcecode_id']);
        }
        if (!$param['code_version']) {
            return json(['code' => 0, 'msg' => '缺少参数code_version']);
        }
        if (!$param['domain']) {
            return json(['code' => 0, 'msg' => '缺少参数domain']);
        }
        $website = Db::name('yun_source_code_website')->where(['ip' => $param['ip'], 'domain' => $param['domain']])->find();
        if (!$website) {
            $data['sourcecode_id'] = $param['sourcecode_id'];
            $data['code_version'] = $param['code_version'];
            $data['domain'] = $param['domain'];
            $data['ip'] = $param['ip'];
            $data['create_time'] = time();
            Db::name('yun_source_code_website')->insert($data);
        }
        return json(['code' => 1]);
    }
}