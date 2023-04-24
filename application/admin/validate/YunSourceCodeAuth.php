<?php
namespace app\admin\validate;
use think\Validate;
/**
 * 配置验证器
 * @package app\admin\validate
 */
class YunSourceCodeAuth extends Validate
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
        'sourcecode_id|源码'     => 'require',
        'update_key|授权key'     => 'require',
        'deadline|到期时间'     => 'require',
    ];

    //定义验证提示
    protected $message = [
        'website_id.require' => '请选择站点',
        'sourcecode_id.require' => '请选择源码',
        'update_key.require' => '请输入授权key',
        'deadline.require' => '请选择到期时间',
    ];

    //定义验证场景
    protected $scene = [

    ];
}
