<?php
namespace app\admin\validate;

use think\Validate;
/**
 * 配置验证器
 * @package app\admin\validate
 */
class YunWebsite extends Validate
{
    //自动完成——写入，更新
    protected $auto = [];
    //自动完成——写入
    protected $insert = [];  
    //自动完成——更新
    protected $update = [];  

    //定义验证规则
    protected $rule = [
        'name|站点名称' => 'require',
        'domain|站点域名' => 'require',
        'ip|站点ip' => 'require',
        'qq|站点qq' => 'require',
        'phone|站点电话' => 'require',
        'verify_type|站点验证方式' => 'require',
        'auth_type|站点域名授权方式' => 'require',
        'is_black|站点黑名单' => 'require',
    ];

    //定义验证提示
    protected $message = [
        'name.require' => '请输入站点名称',
        'domain.require' => '请输入站点域名',
        'ip.require' => '请输入站点ip',
        'qq.require' => '请输入站点qq',
        'phone.require' => '请输入站点电话',
        'verify_type.require' => '请选择站点验证方式',
        'auth_type.require' => '请选择站点域名授权方式',
        'is_black.require' => '请选择站点是否为黑名单',
    ];

    //定义验证场景
    protected $scene = [];
}
