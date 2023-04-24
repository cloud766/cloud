<?php
namespace app\admin\validate;
use think\Validate;
/**
 * 配置验证器
 * @package app\admin\validate
 */
class YunAddonVersion extends Validate
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
        'version|插件版本'     => 'require',
        'file_name|插件文件名'     => 'require',
    ];

    //定义验证提示
    protected $message = [
        'version.require' => '请输入插件版本',
        'file_name.require' => '请上传插件文件',
    ];

    //定义验证场景
    protected $scene = [

    ];
}
