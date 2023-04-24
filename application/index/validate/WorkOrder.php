<?php
namespace app\index\validate;

use think\Validate;
/**
 * 工单验证器
 * @package app\index\validate
 */
class WorkOrder extends Validate
{
    //自动完成——写入，更新
    protected $auto = [];
    //自动完成——写入
    protected $insert = [];  
    //自动完成——更新
    protected $update = [];  

    //定义验证规则
    protected $rule = [
        'website_id|站点id' => 'require',
        'user_id|用户id' => 'require',
        'category_id|分类id' => 'require',
        'sub_category_id|子分类id' => 'require',
        'title|问题标题' => 'require',
        'description|问题描述' => 'require',
        'operation|操作描述' => 'require',
        'website_domain|站点网址' => 'require',
        'website_account|创始人账号' => 'require',
        'website_password|创始人密码' => 'require',
        'qq|联系QQ' => 'require',
        'phone|金额' => 'require',
    ];

    //定义验证提示
    protected $message = [
        'website_id.require' => '请选择站点',
        'user_id.require' => '请登录',
        'category_id.require' => '请选择工单分类',
        'sub_category_id.require' => '请选择工单子分类',
        'title.require' => '请填写问题标题',
        'description.require' => '请填写问题描述',
        'operation.require' => '请填写操作描述',
        'pic.require' => '请上传图片',
        'website_domain.require' => '请填写站点网址',
        'website_account.require' => '请填写创始人账号',
        'website_password.require' => '请填写创始人密码',
        'phone.require' => '请填写手机号'
    ];

    //定义验证场景
    protected $scene = [];
}
