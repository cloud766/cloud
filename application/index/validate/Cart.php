<?php
namespace app\index\validate;

use think\Validate;
/**
 * 购物车验证器
 * @package app\index\validate
 */
class Cart extends Validate
{
    //自动完成——写入，更新
    protected $auto = [];
    //自动完成——写入
    protected $insert = [];  
    //自动完成——更新
    protected $update = [];  

    //定义验证规则
    protected $rule = [
        'website_id|站点id' => 'require',
        'user_id|用户id' => 'require',
        'relation|关联类型' => 'require',
        'relation_id|关联id' => 'require',
        'price|金额' => 'require',
        'pay_price|折后金额' => 'require',
    ];

    //定义验证提示
    protected $message = [
        'website_id.require' => '缺少站点id',
        'user_id.require' => '缺少用户id',
        'relation.require' => '缺少关联类型',
        'relation_id.require' => '缺少关联id',
        'price.require' => '缺少金额',
        'pay_price.require' => '缺少折后金额',
    ];

    //定义验证场景
    protected $scene = [];
}
