<?php
namespace app\index\controller\store;

use app\index\controller\store\Base;

use app\common\model\Order as OModel;

use app\common\model\yun\YunAddonVersion as YAVModel;
use app\common\model\yun\YunAddonBuy as YABModel;
use app\common\model\yun\YunAddonAuth as YAAModel;

use app\common\model\yun\YunTemplate as YTModel;
use app\common\model\yun\YunTemplateBuy as YTBModel;
use app\common\model\yun\YunTemplateAuth as YTAModel;

use app\common\model\yun\YunWebsite as YWModel;

use mikkle\tp_alipay\Alipay;
use mikkle\tp_wxpay\Wxpay;

class Order extends Base
{
    /**
     * 第三方支付创建订单
     */
    public function addOrder()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $user_id = $param['user_id'];
        $relation = $param['relation'];
        if (!$id || !$user_id || !$relation) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        if ($relation == 'template') {
            //模板
            $templateParam['map'] = ['id' => $id];
            $template = YTModel::getDataByParam($templateParam);
            if (!$template) {
                return json(['code' => 0, 'msg' => '模板不存在']);
            }
            $buyParam['map'] = ['user_id' => $user_id, 'id' => $template['id']];
            $buy = YTBModel::getDataByParam($buyParam);
            if ($buy) {
                return json(['code' => 0, 'msg' => '请勿重复购买']);
            }
            //如果模板的价格为0，直接添加购买记录和模板授权
            if ($template['price'] == 0) {
                 //存入yun_temp_buy
                $sql = array(
                    'user_id' => $user_id,
                    'template_id' => $id,
                    'buy_time' => time(),
                    'buy_price' => $template['price']
                );
                $table_buy = db('yun_template_buy')->insert($sql); 
                //修改yun_template模板销售数据
                $table_temp = db('yun_template')->where('id', $id)->setInc('sale_num');
                db('yun_template')->where('id', $id)->setInc('income', $template['price']);
                //为网站添加授权
                $website = YWModel::getListByMap(['user_id' => $user_id]);
                if ($website) {
                    foreach ($website as $key => $value) {
                        $auth['website_id'] = $value['id'];
                        $auth['template_id'] = $id;
                        YTAModel::addData($auth);
                    }
                }
                return json(['code' => 2, 'msg' => '购买成功']);
            }
            $order['price'] = $template['price'];
        } elseif ($relation == 'addon') {
            //插件
            $addonParam['map'] = ['id' => $id];
            $addon = YAVModel::getDataByParam($addonParam);
            if (!$addon) {
                return json(['code' => 0, 'msg' => '插件不存在']);
            }
            $buyParam['map'] = ['user_id' => $user_id, 'id' => $addon['id']];
            $buy = YABModel::getDataByParam($buyParam);
            if ($buy) {
                return json(['code' => 0, 'msg' => '请勿重复购买']);
            }
            //如果模板的价格为0，直接添加购买记录和模板授权
            if ($addon['price'] == 0) {
                 //存入yun_temp_buy
                $sql = array(
                    'user_id' => $user_id,
                    'addon_id' => $id,
                    'buy_time' => time(),
                    'buy_price' => $addon['price']
                );
                $table_buy = db('yun_addon_buy')->insert($sql); 
                //修改yun_addon模板销售数据
                $table_temp = db('yun_addon')->where('id', $id)->setInc('sale_num');
                db('yun_addon')->where('id', $id)->setInc('income', $addon['price']);
                //为网站添加授权
                $website = YWModel::getListByMap(['user_id' => $user_id]);
                if ($website) {
                    foreach ($website as $key => $value) {
                        $auth['website_id'] = $value['id'];
                        $auth['addon_id'] = $id;
                        YAAModel::addData($auth);
                    }
                }
                return json(['code' => 2, 'msg' => '购买成功']);
            }
            $order['price'] = $addon['price'];
        } elseif ($relation == 'codeauth') {

        }
        $now = time();
        $oldOrder = OModel::getDataByMap(['user_id' => $user_id, 'relation' => $relation, 'relation_id' => $id, 'expiration_time' => ['gt', $now]]);
        if ($oldOrder) {
            return json(['code' => 1, 'order_sn' => $oldOrder['order_sn']]);
        }
        $order['user_id'] = $user_id;
        $order['relation_id'] = $id;
        $order['relation'] = $relation;
        $order['expiration_time'] = $now + 60 * 15;
        $order['order_sn'] = date('YmdHis', $now) . rand(10000, 99999);
        $id = OModel::addData($order);
        if (!$id) {
            return json(['code' => 0, 'msg' => '创建订单失败']);
        } else {
            return json(['code' => 1, 'order_sn' => $order['order_sn']]);
        }
    }

    /**
     * 检测订单支付状态
     */
    public function checkPayStatus()
    {
        $order_sn = $this->request->param('order_sn');
        if (!$order_sn) {
            return json(['code' => 0, 'msg' => '订单信息错误']);
        }
        $order = OModel::getDataByMap(['order_sn' => $order_sn]);
        if (!$order) {
            return json(['code' => 0, 'msg' => '订单错误']);
        }
        if ($order['pay_status'] == 1) {
            return json(['code' => 1, 'msg' => '订单支付成功']);
        }
        return json(['code' => 0, 'msg' => '订单未支付']);
    }

    /**
     * 微信支付二维码
     */
    public function wechatqrcode()
    {
        $order_sn = $this->request->param('order_sn');
        if (!$order_sn) {
            return false;
        }
        $order = OModel::getDataByMap(['order_sn' => $order_sn]);
        if (!$order) {
            return false;
        }
        if ($order['pay_status'] == 1) {
            return false;
        }
        $map = get_config(['group' => 'wechat_pay']);
        $options = [
            "appid" => $map['wechat_pay_appid'],
            "secret" => $map['wechat_pay_appsecret'],
            "mch_id" => $map['wechat_pay_mchid'],
            "key" => $map['wechat_pay_key'],
        ];
        // $body = $order['relation'] == 'template' ? '购买模板' : '购买插件';
        switch ($order['relation']) {
            case 'template':
                $body = '购买模板';
                break;
            case 'addon':
                $body = '购买插件';
                break;
            case 'sourcecode':
                $body = '购买源码授权';
                break;

            default:
                # code...
                break;
        }
        $resultData = [
            'body' => $body,
            /**商品描述*/
            'out_trade_no' => $order['order_sn'],
            /**商户订单号*/
            'total_fee' => $order['price'] * 100,
            /**标价金额(单位分)*/
            // 'total_fee' => 1, /**标价金额(单位分)*/
            'notify_url' => "http://api.dayongjiaoyu.cn/api/pay/wechat_pay_notify_url",
            /**通知地址(WchatConfig::$notificationURL)*/
            'trade_type' => "NATIVE",
            /**交易类型  NATIVE  MWEB  */
            'product_id' => 2
        ];
        $result = Wxpay::instance($options)->unifiedOrder()->setParam($resultData)->getPayUrl();
        qrcode(urldecode($result), 4);
    }

    /**
     * 支付宝支付二维码
     */
    public function aliqrcode()
    {
        $order_sn = $this->request->param('order_sn');
        if (!$order_sn) {
            return false;
        }
        $order = OModel::getDataByMap(['order_sn' => $order_sn]);
        if (!$order) {
            return false;
        }
        if ($order['pay_status'] == 1) {
            return false;
        }
        $map = get_config(['group' => 'wechat_pay']);
        $options = [
            "appid" => $map['wechat_pay_appid'],
            "secret" => $map['wechat_pay_appsecret'],
            "mch_id" => $map['wechat_pay_mchid'],
            "key" => $map['wechat_pay_key'],
        ];
        // $subject = $order['relation'] == 'template' ? '购买模板' : '购买插件';
        switch ($order['relation']) {
            case 'template':
                $subject = '购买模板';
                break;
            case 'addon':
                $subject = '购买插件';
                break;
            case 'sourcecode':
                $subject = '购买源码授权';
                break;

            default:
                # code...
                break;
        }
        $map = get_config(['group' => 'ali_pay']);
        //获取保存的支付宝配置
        $options = [
            "app_id" => $map['app_id'],
            "public_key" => $map['public_key'],
            "alipay_public_key" => $map['alipay_public_key'],
            "private_key" => $map['private_key'],
        ];

        Alipay::instance($options)->Precreate()
            ->setParam([
                "notify_url" => "http://api.dayongjiaoyu.cn/api/pay/alipay_notify_url"
            ])
            ->setBizContentParam([
                "subject" => $subject,
                "out_trade_no" => $order['order_sn'],
                "total_amount" => $order['price'],
                // "total_amount"=>"0.01",
            ])->getResult();
        $result = Alipay::instance($options)->Precreate()->getResponseArray();
        if ($result['alipay_trade_precreate_response']['code'] == 10000) {
            qrcode(urldecode($result['alipay_trade_precreate_response']['qr_code']), 4);
        }
    }

}