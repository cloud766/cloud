<?php
namespace app\admin\validate;
use think\Validate;
/**
 * 配置验证器
 * @package app\admin\validate
 */
class YunTemplateAuth extends Validate
{
    //自动完成——写入，更新
    protected $auto = [];
    //自动完成——写入
    protected $insert = [];  
    //自动完成——更新
    protected $update = [];  

    //定义验证规则
    protected $rule = [
        '__token__|表单令牌'     => 'require|token',
        'website_id|站点'     => 'require',
        'template_id|模板'     => 'require',
    ];

    //定义验证提示
    protected $message = [
        'website_id.require' => '请选择站点',
        'template_id.require' => '请选择模板',
    ];

    //定义验证场景
    protected $scene = [

    ];
}
