<?php
namespace app\admin\validate;
use think\Validate;
/**
 * 配置验证器
 * @package app\admin\validate
 */
class UserType extends Validate
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
        'name|类型名称'     => 'require',
        'discount|折扣'     => 'require',
    ];

    //定义验证提示
    protected $message = [
        'name.require' => '请输入类型',
        'discount.require' => '请输入折扣'
    ];

    //定义验证场景
    protected $scene = [

    ];
}
