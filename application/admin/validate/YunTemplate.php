<?php
namespace app\admin\validate;

use think\Validate;
/**
 * 配置验证器
 * @package app\admin\validate
 */
class YunTemplate extends Validate
{
    //自动完成——写入，更新
    protected $auto = [];
    //自动完成——写入
    protected $insert = [];  
    //自动完成——更新
    protected $update = [];  

    //定义验证规则
    protected $rule = [
        'title|模板标题' => 'require',
        'name|模板名称' => 'require',
        'type|模板类型' => 'require',
        'file_name|模板文件' => 'require',
        'author|模板作者' => 'require',
        'sale_num|模板销量' => 'require',
        'price|模板售价' => 'require',
        'income|模板收入' => 'require',
        'pic_a|模板缩略图' => 'require',
        'pic_f|模板轮播图' => 'require',
        '__token__|表单令牌' => 'require|token',
    ];

    //定义验证提示
    protected $message = [
        'title.require' => '请输入模板标题',
        'name.require' => '请输入模板名称',
        'type.require' => '请选择模板类型',
        'file_name.require' => '请上传模板文件',
        'author.require' => '请输入模板作者',
        'sale_num.require' => '请输入模板销量',
        'price.require' => '请输入模板售价',
        'income.require' => '请输入模板收入',
        'pic_a.require' => '请上传模板缩略图',
        'pic_f.require' => '请上传模板轮播图'
    ];

    //定义验证场景
    protected $scene = [];
}
