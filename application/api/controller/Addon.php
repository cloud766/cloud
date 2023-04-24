<?php
namespace app\api\controller;

use app\common\controller\ApiBase;
use think\Log;
use think\cache\driver\Redis;
use think\Request;
use ZipArchive;
use app\common\model\yun\YunAddonVersion as YAVModel;
use app\common\model\yun\YunAddonAuth as YAAModel;
use app\common\model\yun\YunAddonDownload as YADModel;
use think\Db;
/**
 * 插件接口
 */
class Addon extends ApiBase
{

    public function _initialize()
    {
        // parent::_initialize();
    }

    /**
     * 插件列表
     * @return [type] [description]
     */
    public function addonList()
    {
        $website_id = $this->request->param('website_id');
        $addon_ids = $this->request->param('addon_ids');
        $page = $this->request->param('page');
        $pageSize = $this->request->param('pageSize');
        $page = $page > 0 ? $page - 1 : 0;
        $pageSize = $pageSize ? $pageSize : config('paginate.list_rows');
        $page = $page * $pageSize;
        $noMore = 1;
        $list = YAAModel::alias('yaa')
            ->join('__YUN_ADDON__ ya', 'ya.id = yaa.addon_id', 'LEFT')
            ->field('ya.title,ya.name,ya.id as addon_id,ya.author,ya.type,ya.pic_a,yaa.id')
            ->where(['yaa.website_id' => $website_id, 'ya.status' => 1, 'ya.id' => ['not in', $addon_ids]])
            ->limit($page, $pageSize)
            ->select();
        if ($list) {
            foreach ($list as $key => $value) {
                $list[$key]['pic_a'] = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $value['pic_a'];
            }
            $moreList = YAAModel::alias('yaa')
                ->join('__YUN_ADDON__ ya', 'ya.id = yaa.addon_id', 'LEFT')
                ->field('ya.title,ya.name,ya.id as addon_id,ya.author,ya.type')
                ->where(['yaa.website_id' => $website_id])
                ->limit($page + $pageSize, $pageSize)
                ->select();
            if ($moreList) {
                $noMore = 0;
            }
        } elseif ($page == 0) {
            return json(['code' => 0]);
        }
        return json(['code' => 1, 'list' => $list, 'noMore' => $noMore]);
    }

    /**
     * 下载插件
     * @return [type] [description]
     */
    public function addonDownload()
    {
        $param = $this->request->param();
        $amap['addon_id'] = $param['addon_id'];
        $amap['website_id'] = $param['website_id'];
        $auth = YAAModel::getDataByMap($amap);
        if (!$auth) {
            return json(['code' => 0, 'msg' => '未授权，请前往插件商城购买']);
        }
        $map['id'] = $param['addon_id'];
        $data = Db::name('yun_addon')->where($map)->find();
        if (!$data || !$data['file_name']) {
            return json(['code' => 0, 'msg' => '插件不存在']);
        }
        if ($data['status'] != 1) {
            return json(['code' => 0, 'msg' => '插件无法下载']);
        }
        $path = ROOT_PATH . 'data' . DS . 'addon' . DS . $data['name'] . DS . $data['file_name'];
        if (!file_exists($path)) {
            return json(['code' => 0, 'msg' => '插件压缩包不存在']);
        }
        $file = fopen($path, 'r+');
        $fdata = fread($file, filesize($path));
        fclose($file);
        //下载记录
        $download['website_id'] = $param['website_id'];
        $download['addon_id'] = $param['addon_id'];
        $download['ip'] = $param['ip'];
        YADModel::addData($download);
        return json(['code' => 1, 'data' => base64_encode($fdata), 'addon' => $data]);
    }

    /**
     * 检测插件是否可以更新
     */
    public function addonUpdateInfo()
    {
        $param = $this->request->param();
        $amap['addon_id'] = $param['addon_id'];
        $amap['website_id'] = $param['website_id'];
        $auth = YAAModel::getDataByMap($amap);
        if (!$auth) {
            return json(false);
        }
        $vmap['addon_id'] = $param['addon_id'];
        $vmap['version'] = ['gt', $param['addon_version']];
        $vmap['status'] = 1;
        $newVersion = Db::name('yun_addon_version')->where($vmap)->order('version')->find();
        if ($newVersion) {
            return json(['version' => $newVersion['version'], 'content' => $newVersion['content']]);
        }
        return json(false);
    }

    /**
     * 下载插件更新包
     */
    public function addonUpdateDownload()
    {
        $param = $this->request->param();
        $amap['addon_id'] = $param['addon_id'];
        $amap['website_id'] = $param['website_id'];
        $auth = YAAModel::getDataByMap($amap);
        if (!$auth) {
            return json(['code' => 0, 'msg' => '未授权，请前往插件商城购买']);
        }
        $vmap['addon_id'] = $param['addon_id'];
        $vmap['version'] = ['gt', $param['addon_version']];
        $newVersion = Db::name('yun_addon_version')->where($vmap)->order('version')->find();
        if (!$newVersion) {
            return json(['code' => 0, 'msg' => '插件已是最新版本']);
        }
        if ($newVersion['version'] != $param['update_version']) {
            return json(['code' => 0, 'msg' => '版本过高，请下载对应的更新包']);
        }
        if ($newVersion['status'] != 1) {
            return json(['code' => 0, 'msg' => '更新包未通过审核，无法下载']);
        }
        $path = ROOT_PATH . 'data' . DS . 'addon' . DS . $param['addon_name'] . DS . $newVersion['version'] . DS . $newVersion['file_name'];
        if (!file_exists($path)) {
            return json(['code' => 0, 'msg' => '插件更新包不存在，无法下载']);
        }
        $file = fopen($path, 'r+');
        $fdata = fread($file, filesize($path));
        fclose($file);
        return json(['code' => 1, 'data' => base64_encode($fdata), 'file_name' => $newVersion['file_name']]);
    }
}