<?php
namespace app\common\controller;

use app\common\controller\Base;
use app\common\util\Data;
use app\common\model\yun\YunWebsite as YWModel;
use think\Log;
use think\Db;

class ApiBase extends Base
{
    public function _initialize()
    {
        parent::_initialize();
        $header = $this->request->header();
        if($header['host'] != 'api.dayongjiaoyu.cn'){
            exit();
        }
        $param = $this->request->param();
        $res = self::checkWebsite($param);
        if ($res['code'] != 1) {
            exit(json_encode($res));
        }
        Log::write('进行对应操作');
    }

    /**
     * 检测站点是否为平台站点
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    private function checkWebsite($param)
    {
        if (!$param['website_id']) {
            return ['code' => 0, 'msg' => '缺少参数website_id'];
        }
        if (!$param['domain']) {
            return ['code' => 0, 'msg' => '缺少参数domain'];
        }
        if (!$param['ip']) {
            return ['code' => 0, 'msg' => '缺少参数ip'];
        }
        // if (!preg_match('/^(?=^.{3,255}$)(http(s)?:\/\/)?(www\.)?[a-zA-Z0-9][-a-zA-Z0-9]{0,62}(\.[a-zA-Z0-9][-a-zA-Z0-9]{0,62})+(:\d+)*(\/\w+\.\w+)*$/', $param['domain'])) {
        //     return ['code' => 0, 'msg' => '域名格式错误'];
        // }
        // $website = YWModel::getDataByMap(['id' => $param['website_id']]);
        $website = Db::name('yun_website')->where(['id' => $param['website_id']])->find();
        if (!$website) {
            return ['code' => 0, 'msg' => '此站点无权访问'];
        }
        $auth = self::websiteAuth($param, $website);
        if ($auth['code'] != 1) {
            return $auth;
        }
        return ['code' => 1, 'msg' => '通过base验证'];
    }

    /**
     * 验证网站是否有授权
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    private function websiteAuth($param, $website)
    {
        if ($website['verify_type'] == 1 && $website['ip'] != $param['ip']) {
            return ['code' => 0, 'msg' => 'ip错误，此站点无权访问'];
        }
        //去除http:// https://
        $param_host = str_replace('http://', '', $param['domain']);
        $param_host = str_replace('https://', '', $param_host);
        $website_host = str_replace('http://', '', $website['domain']);
        $website_host = str_replace('https://', '', $website_host);
        if ($website['auth_type'] == 0 && $param_host != $website_host) {
            return ['code' => 0, 'msg' => '域名不匹配，此站点无权访问'];
        }
        if ($website['auth_type'] == 1) {
            $param_host = self::get_host($param_host);
            $website_host = self::get_host($website_host);
            if ($param_host != $website_host) {
                return ['code' => 0, 'msg' => '域名不匹配，此站点无权访问'];
            }
        }
        return ['code' => 1];
    }

    /**
     * 获取 一级域名.顶级域名 格式
     * @param  [type] $url [description]
     * @return [type]      [description]
     */
    private function get_host($url)
    {
        $data = explode('.', $url);
        $count = count($data);
        //判断是否是双后缀
        $doubleSuffix = true;
        $hostCn = 'com.cn,net.cn,org.cn,gov.cn';
        $hostCn = explode(',', $hostCn);
        foreach ($hostCn as $host) {
            if (strpos($url, $host)) {
                $doubleSuffix = false;
            }
        }
        //如果是返回FALSE ，如果不是返回true
        if ($doubleSuffix == true) {
            $host = $data[$count - 2] . '.' . $data[$count - 1];
        } else {
            $host = $data[$count - 3] . '.' . $data[$count - 2] . '.' . $data[$count - 1];
        }
        return $host;
    }


}
