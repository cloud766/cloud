<?php
namespace app\admin\validate;
use think\Validate;
/**
 * 配置验证器
 * @package app\admin\validate
 */
class YunAddon extends Validate
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
        'title|插件标题'     => 'require',
        'name|插件名称'     => 'require|unique:yun_addon',
        'version|插件版本'     => 'require',
        'type|插件类型'     => 'require',
        'file_name|插件文件名'     => 'require',
        'author|插件作者'     => 'require',
        'sale_num|插件销量'     => 'require',
        'price|插件售价'     => 'require',
        'income|插件收入'     => 'require',
    ];

    //定义验证提示
    protected $message = [
        'title.require' => '请输入插件标题',
        'name.require' => '请输入插件名称',
        'version.require' => '请输入插件版本',
        'type.require' => '请选择插件类型',
        'file_name.require' => '请上传插件文件',
        'author.require' => '请输入插件作者',
        'sale_num.require' => '请输入插件销量',
        'price.require' => '请输入插件售价',
        'income.require' => '请输入插件收入',
        'name.unique' => '名称重复，请重新输入'
    ];

    //定义验证场景
    protected $scene = [

    ];
}
