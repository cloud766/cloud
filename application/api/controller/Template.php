<?php
namespace app\api\controller;

use app\common\controller\ApiBase;
use think\Log;
use ZipArchive;
use app\common\model\yun\YunTemplate as YTModel;
use app\common\model\yun\YunTemplateAuth as YTAModel;
use app\common\model\yun\YunTemplateDownload as YTDModel;
/**
 * 模板接口
 */
class Template extends ApiBase
{

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 模板列表
     * @return [type] [description]
     */
    public function templateList()
    {
        $website_id = $this->request->param('website_id');
        $page = $this->request->param('page');
        $pageSize = $this->request->param('pageSize');
        $page = $page > 0 ? $page - 1 : 0;
        $pageSize = $pageSize ? $pageSize : config('paginate.list_rows');
        $page = $page * $pageSize;
        $noMore = 1;
        $list = YTAModel::alias('yta')
            ->field('yt.title,yt.name,yt.pic_a,yt.id as template_id,yt.author,ytt.name as type,yta.id')
            ->join('__YUN_TEMPLATE__ yt', 'yt.id = yta.template_id', 'LEFT')
            ->join('__YUN_TEMPLATE_TYPE__ ytt', 'ytt.id = yt.type', 'LEFT')
            ->order('yta.id desc')
            ->where(['yta.website_id' => $website_id, 'yt.status' => 1])
            ->limit($page, $pageSize)
            ->select();
        if ($list) {
            foreach ($list as $key => $value) {
                $list[$key]['pic_a'] = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $value['pic_a'];
            }
            $moreList = YTAModel::alias('yta')
                ->where(['yta.website_id' => $website_id])
                ->order('yta.id desc')
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
     * 下载模板
     * @return [type] [description]
     */
    public function templateDownload()
    {
        $param = $this->request->param();
        $amap['template_id'] = $param['template_id'];
        $amap['website_id'] = $param['website_id'];
        $auth = YTAModel::getDataByMap($amap);
        if (!$auth) {
            return json(['code' => 0, 'msg' => '未授权，请前往官网购买模板']);
        }
        $map['id'] = $param['template_id'];
        $data = YTModel::getDataByMap($map);
        if (!$data || !$data['file_name']) {
            return json(['code' => 0, 'msg' => '模板不存在']);
        }
        $path = ROOT_PATH . 'data/template/' . $data['file_name'];
        if (!file_exists($path)) {
            return json(['code' => 0, 'msg' => '模板压缩包不存在']);
        }
        if ($data['status'] != 1) {
            return json(['code' => 0, 'msg' => '插件无法下载']);
        }
        $file = fopen($path, 'r+');
        $fdata = fread($file, filesize($path));
        fclose($file);
        //下载记录
        $download['website_id'] = $param['website_id'];
        $download['template_id'] = $param['template_id'];
        $download['ip'] = $param['ip'];
        YTDModel::addData($download);
        return json(['code' => 1, 'data' => base64_encode($fdata), 'template' => $data]);
    }
}