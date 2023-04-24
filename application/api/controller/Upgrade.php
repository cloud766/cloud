<?php
namespace app\api\controller;

use app\common\controller\ApiBase;
use think\Log;
use ZipArchive;
use app\common\model\yun\YunSourceCodeAuth as YSCAModel;
use app\common\model\yun\YunSourceCodeDownload as YSCDModel;
use app\common\model\yun\YunSourceCodeWebsite as YSCWModel;
use app\common\model\yun\YunWebsite as YWModel;
use think\Db;

/**
 * 系统更新接口
 */
class Upgrade extends ApiBase
{

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 获取文件总数，用户文件校验时访问，返回云端代码文件总数
     * @return [type] [description]
     */
    public function getFileCount()
    {
        $param = $this->request->param();
        $auth = self::codeCheckAuth($param);
        if ($auth['code'] != 1) {
            return json($auth);
        }
        $data = db('yun_source_code_version')->where(['version' => $param['code_version'], 'sourcecode_id' => $param['code_id']])->order('version desc')->find();
        if (!$data || !$data['check_file_name']) {
            return json(['code' => 0, 'msg' => '校验文件不存在']);
        }
        $sourcecode = Db::name('yun_source_code')->where(['id' => $param['code_id']])->find();
        $path = ROOT_PATH . 'data/source_code/' . $sourcecode['name'] . DS . $data['version'] . DS . $data['check_file_name'];
        if (!file_exists($path)) {
            return json(['code' => 0, 'msg' => '校验文件不存在']);
        }
        $zip = zip_open($path);
        $count = 0;
        if ($zip) {
            while ($zip_entry = zip_read($zip)) {
                $name = zip_entry_name($zip_entry);
                $last = substr($name, strlen($name) - 1, strlen($name));
                $count += $last != '/' && $name != 'application/database.php' ? 1 : 0;
            }
            zip_close($zip);
        }
        return json(['code' => 1, 'count' => $count]);
    }

    /**
     * 获取检测的文件，返回云端文件列表，数组区间
     * @return [type] [description]
     */
    public function getFileList()
    {
        $param = $this->request->param();
        $start = $param['start'];
        $data = db('yun_source_code_version')->where(['version' => $param['code_version'], 'sourcecode_id' => $param['code_id']])->order('version desc')->find();
        $sourcecode = Db::name('yun_source_code')->where(['id' => $param['code_id']])->find();
        $path = ROOT_PATH . 'data/source_code/' . $sourcecode['name'] . DS . $param['code_version'] . DS . $data['check_file_name'];
        $zip = zip_open($path);
        $filelist = [];
        if ($zip) {
            while ($zip_entry = zip_read($zip)) {
                $name = zip_entry_name($zip_entry);
                $last = substr($name, strlen($name) - 1, strlen($name));
                if ($last != '/' && $name != 'application/database.php') {
                    $file['path'] = $name;
                    $file['size'] = zip_entry_filesize($zip_entry);
                    array_push($filelist, $file);
                }
            }
            zip_close($zip);
        }
        $list = array_slice($filelist, $start, 100);
        return json(['code' => 1, 'list' => $list]);
    }

    /**
     * 获取需要下载的文件，返回云端文件代码
     * @return [type] [description]
     */
    public function getFile()
    {
        $param = $this->request->param();
        $path = $param['path'];
        $data = db('yun_source_code_version')->where(['version' => $param['code_version'], 'sourcecode_id' => $param['code_id']])->order('version desc')->find();
        $sourcecode = Db::name('yun_source_code')->where(['id' => $param['code_id']])->find();
        $name = ROOT_PATH . 'data/source_code/' . $sourcecode['name'] . DS . $param['code_version'] . DS . $data['check_file_name'];
        $zip = zip_open($name);
        $filelist = [];
        if ($zip) {
            while ($zip_entry = zip_read($zip)) {
                $entryname = zip_entry_name($zip_entry);
                $last = substr($entryname, strlen($entryname) - 1, strlen($entryname));
                if ($last != '/' && $entryname == $path) {
                    $entryread = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                    break;
                }
            }
            zip_close($zip);
            return json(['code' => 1, 'data' => base64_encode($entryread)]);
        }
        return json(['code' => 0, 'msg' => '下载文件不存在']);
    }

    /**
     * 检测客户代码版本是否需要更新，返回最新的版本信息
     * @return [type] [description]
     */
    public function versionCheck()
    {
        $param = $this->request->param();
        $map['sourcecode_id'] = $param['code_id'];
        $map['version'] = ['gt', $param['code_version']];
        $version = db('yun_source_code_version')->where($map)->order('version asc')->find();
        $auth['version'] = $version;
        return json($auth);
    }

    /**
     * 下载新版本的源码
     * @return [type] [description]
     */
    public function versionDownload()
    {
        $param = $this->request->param();
        $auth = self::codeCheckAuth($param);
        if ($auth['code'] != 1) {
            return json($auth);
        }
        $map['sourcecode_id'] = $param['code_id'];
        $map['version'] = $param['update_code_version'];
        $data = Db::name('yun_source_code_version')->where($map)->find();
        if (!$data || !$data['file_name']) {
            return json(['code' => 0, 'msg' => '更新文件不存在']);
        }
        $sourcecode = Db::name('yun_source_code')->where(['id' => $param['code_id']])->find();
        $path = ROOT_PATH . 'data/source_code/' . $sourcecode['name'] . DS . $data['version'] . DS . $data['file_name'];
        if (!file_exists($path)) {
            return json(['code' => 0, 'msg' => '更新文件不存在']);
        }
        $file = fopen($path, 'r+');
        $fdata = fread($file, filesize($path));
        fclose($file);
        self::downloadStatistics($param['website_id'], $param['ip'], $data['id'], $data['sourcecode_id']);
        return json(['code' => 1, 'data' => base64_encode($fdata)]);
    }

    public function checkAuth()
    {
        $param = $this->request->param();
        $result = self::codeCheckAuth($param);
        $result['copyright'] = 'Copyright © 2014-2018 Hong Xun Technology Development Co., Ltd. All rights reserved.';
        return json($result);
    }

    /**
     * 检验是否有授权
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function codeCheckAuth($param)
    {
        if (!$param['update_key']) {
            return ['code' => 0, 'msg' => '缺少参数update_key'];
        }
        $auth = YSCAModel::getDataByMap(['website_id' => $param['website_id']]);
        if (!$auth) {
            return ['code' => 0, 'msg' => '域名未获得授权'];
        }
        if ($auth['update_key'] != $param['update_key']) {
            return ['code' => 0, 'msg' => '授权key与域名不匹配'];
        }
        if ($auth['deadline'] == 0) {
            return ['code' => 1, 'msg' => '授权正常', 'end_time' => '永久', 'start_time' => date('Y-m-d', $auth['create_time'])];
        }
        if ($auth['deadline'] != 0 && $auth['deadline'] < time()) {
            return ['code' => 2, 'msg' => '授权已过期'];
        }
        return ['code' => 1, 'msg' => '授权正常', 'end_time' => date('Y-m-d', $auth['deadline']), 'start_time' => date('Y-m-d', $auth['create_time'])];
    }

    /**
     * 下载统计
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    private function downloadStatistics($website_id, $ip, $version_id, $sourcecode_id)
    {
        $download['website_id'] = $website_id;
        $download['ip'] = $ip;
        $download['version_id'] = $version_id;
        $download['sourcecode_id'] = $sourcecode_id;
        $result = YSCDModel::addData($download);
    }

    /**
     * 安装统计，记录站点安装源码的信息
     * @return [type] [description]
     */
    public function installStatistics()
    {
        $param = $this->request->param();
        if (!$param['sourcecode_id']) {
            return json(['code' => 0, 'msg' => '缺少参数sourcecode_id']);
        }
        if (!$param['code_version']) {
            return json(['code' => 0, 'msg' => '缺少参数code_version']);
        }
        $website = YWModel::getDataByMap(['id' => $param['website_id']]);
        if (!$website) {
            return json(['code' => 0, 'msg' => '站点不存在']);
        }
        YSCWModel::addData($param);
        return json(['code' => 1]);
    }
}