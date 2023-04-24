<?php
namespace app\admin\validate;
use think\Validate;
/**
 * 配置验证器
 * @package app\admin\validate
 */
class UserLists extends Validate
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
        'username|用户名'     => 'require',
        'nickname|昵称'     => 'require',
        'email|邮箱'     => 'require',
        'level|等级'     => 'require',
        'gender|邮箱'     => 'require',
        'qq|邮箱'     => 'require',
        'wechat|邮箱'     => 'require',
        'type|邮箱'     => 'require',
    ];

    //定义验证提示
    protected $message = [
        'username.require' => '请输入用户名',
        'nickname.require' => '请输入昵称',
        'email.require' => '请输入邮箱',
        'level.require' => '请选择等级',
        'gender.require' => '请选择性别',
        'qq.require' => '请输入QQ',
        'wechat.require' => '请输入微信号',
        'type.require' => '请选择类型',
    ];

    //定义验证场景
    protected $scene = [

    ];
}
