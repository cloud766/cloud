<?php
namespace app\api\controller;

use mikkle\tp_wechat\WechatApi;
use think\Log;

/**
 * 微信推送接口控制器
 */
class Wechat extends WechatApi
{

    protected $valid = true;  //网站第一次匹配 true 1为匹配
    protected $isHook = false; //是否开启钩子


    public function _initialize()
    {
        
    }

    /**
     * 接收微信服务器配置推送
     * @return [type] [description]
     */
    public function index()
    {
        try {
            parent::index();
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
        $type = $this->type;
        Log::write('行为类型'.$type);
        //根据不同的推送类型执行动作
        switch ($type) {
            case 'value':
                # code...
                break;

            default:
                # code...
                break;
        }
    }

}