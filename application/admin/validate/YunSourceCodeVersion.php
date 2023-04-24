<?php
namespace app\admin\validate;
use think\Validate;
/**
 * 配置验证器
 * @package app\admin\validate
 */
class YunSourceCodeVersion extends Validate
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
        'title|版本标题'     => 'require',
        'name|版本名称'     => 'require',
        'file_name|文件'     => 'require',
        'version|版本号'     => 'require',
        'author|版本作者'     => 'require',
        'update_content|更新内容'     => 'require',
    ];

    //定义验证提示
    protected $message = [
        'title.require' => '请输入版本标题',
        'name.require' => '请输入版本名称',
        'file_name.require' => '请上传版本文件',
        'version.require' => '请输入版本号',
        'author.require' => '请输入版本作者',
        'update_content.require' => '请输入版本更新内容',
    ];

    //定义验证场景
    protected $scene = [

    ];
}
