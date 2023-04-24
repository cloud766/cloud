<?php
namespace app\admin\validate;
use think\Validate;
/**
 * 配置验证器
 * @package app\admin\validate
 */
class YunSourceCode extends Validate
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
        'title|源码标题'     => 'require',
        'name|源码名称'     => 'require',
        'type|源码类型'     => 'require',
        'description|源码描述'     => 'require',
        'price|价格'     => 'require',
    ];

    //定义验证提示
    protected $message = [
        'title.require' => '请输入源码标题',
        'name.require' => '请输入源码名称',
        'type.require' => '请选择源码类型',
        'description.require' => '请输入源码描述',
        'price.require' => '请输入源码价格',
    ];

    //定义验证场景
    protected $scene = [

    ];
}
