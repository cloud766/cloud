<?php
namespace app\api\controller;

use app\common\controller\Base;
use mikkle\tp_alipay\Alipay;
use mikkle\tp_wxpay\Wxpay;
use think\Log;
use app\common\model\Order as OModel;
use app\common\model\yun\YunTemplate as YTModel;
use app\common\model\yun\YunTemplateBuy as YTBModel;
use app\common\model\yun\YunTemplateAuth as YTAModel;
use app\common\model\yun\YunWebsite as YWModel;
use app\common\model\yun\YunAddonVersion as YAVModel;
use app\common\model\yun\YunAddonBuy as YABModel;
use app\common\model\yun\YunAddonAuth as YAAModel;
use think\Db;
/**
 * 第三方登录接口
 */
class Pay extends Base
{

    public function _initialize()
    {

    }

    public function alipay()
    {
        $map = get_config(['group' => 'ali_pay']);
        //获取保存的支付宝配置
        $options = [
            "app_id" => $map['app_id'],
            "public_key" => $map['public_key'],
            "alipay_public_key" => $map['alipay_public_key'],
            "private_key" => $map['private_key'],
        ];

        $result = Alipay::instance($options)->PagePay()
            ->setParam([
                "return_url" => "https://api.dayongjiaoyu.cn/api/pay/alipay_return_url",
                "notify_url" => "https://api.dayongjiaoyu.cn/api/pay/alipay_notify_url"
            ])
            ->setBizContentParam([
                "subject" => "debug",
                "out_trade_no" => (string)time(),
                "total_amount" => "0.01",
            ])
            ->getQuickPayUrl();
        $this->redirect($result);
    }

    public function aliqrpay()
    {
        return $this->fetch();
    }

    public function aliqrcode()
    {
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
                "notify_url" => "http://store.dayongjiaoyu.cn/api/pay/alipay_notify_url"
            ])
            ->setBizContentParam([
                "subject" => "debug",
                "out_trade_no" => (string)time(),
                "total_amount" => "0.01",
            ])->getResult();
        $result = Alipay::instance($options)->Precreate()->getResponseArray();
        if ($result['alipay_trade_precreate_response']['code'] == 10000) {
            qrcode(urldecode($result['alipay_trade_precreate_response']['qr_code']), 4);
        }
    }

    /**
     * 支付宝同步回调，这里不处理支付结果，只做对应跳转
     * @return [type] [description]
     */
    public function alipay_return_url()
    {
        $order_sn = $this->request->param('out_trade_no');
        $order = OModel::getDataByParam(['map' => ['order_sn' => $order_sn]]);
        if (!$order) {
            $this->redirect(url('index/store.index/index'));
        }
        if ($order['relation'] == 'template') {
            $this->redirect(url('index/store.template/detail', array('id' => $order['relation_id'])));
        }
        if ($order['relation'] == 'addon') {
            $this->redirect(url('index/store.addon/detail', array('id' => $order['relation_id'])));
        }
        $this->redirect(url('index/store.index/index'));
    }

    /**
     * 支付宝异步回调
     * @return [type] [description]
     */
    public function alipay_notify_url()
    {
        $data = $this->request->param();
        $result = self::alipay_callback($data);
        if ($result) {
            echo "success";
        } else {
            echo "error";
        }
    }

    /**
     * 验证支付宝回调是否能通过验证
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    private function alipay_callback($data)
    {
        $map = get_config(['group' => 'ali_pay']);
        $options = [
            "app_id" => $map['app_id'],
            "public_key" => $map['public_key'],
            "alipay_public_key" => $map['alipay_public_key'],
            "private_key" => $map['private_key'],
        ];
        $result = Alipay::instance($options)->Notify()->verifyRsaSign($data);
        if (!$result) {
            return false;
        }
        //支付结果验证通过，操作相关订单
        $order_sn = $this->request->post('out_trade_no');
        $payResult = self::pay_callback($order_sn);
        if (!$payResult) {
            return false;
        }
        return true;
    }

    /**
     * 支付方法，更改订单状态，添加订单相关数据
     * @param  [type] $order_sn [description]
     * @return [type]           [description]
     */
    private function pay_callback($order_sn)
    {
        $orderPay = Db::name('order_pay')->where(['order_sn' => $order_sn])->find();
        if (!$orderPay) {
            return false;
        }
        if ($orderPay['pay_status']) {
            return false;
        }
        $orderPayUpdate['pay_status'] = 1;
        $orderPayUpdate['pay_time'] = time();
        $result = Db::name('order_pay')->where(['order_sn' => $order_sn])->update($orderPayUpdate);
        if (!$result) {
            return false;
        }
        //更新用户订单
        Db::name('order')->where(['id' => ['in', $orderPay['relation_id']]])->update(['pay_status'=>1, 'pay_time'=>time()]);
        $orderList = Db::name('order')->where(['id' => ['in', $orderPay['relation_id']]])->select();
        if ($orderList) {
            foreach ($orderList as $key => $value) {
                if ($value['relation'] == 'template') {
                    $templateMap['website_id'] = $value['website_id'];
                    $templateMap['template_id'] = $value['relation_id'];
                    $templateBuy = YTBModel::getDataByMap($templateMap);
                    if (!$templateBuy) {
                        $templateBuy = array(
                            'website_id' => $value['website_id'],
                            'template_id' => $value['relation_id'],
                            'buy_time' => time(),
                            'buy_price' => $value['price']
                        );
                        db('yun_template_buy')->insert($templateBuy);
                        db('yun_template')->where('id', $value['relation_id'])->setInc('sale_num');
                        db('yun_template')->where('id', $value['relation_id'])->setInc('income', $value['pay_price']);
                    }
                    $templateAuth = Db::name("yun_template_auth")->where($templateMap)->find();
                    if (!$templateAuth) {
                        $templateAuth = array(
                            'website_id' => $value['website_id'],
                            'template_id' => $value['relation_id'],
                            'create_time' => time(),
                            'update_time' => time()
                        );
                        db('yun_template_auth')->insert($templateAuth);
                    }
                }
                if ($value['relation'] == 'addon') {
                    $addonMap['website_id'] = $value['website_id'];
                    $addonMap['addon_id'] = $value['relation_id'];
                    $addonBuy = YABModel::getDataByMap($addonMap);
                    if (!$addonBuy) {
                        $addonBuy = array(
                            'website_id' => $value['website_id'],
                            'addon_id' => $value['relation_id'],
                            'buy_time' => time(),
                            'buy_price' => $value['price']
                        );
                        db('yun_addon_buy')->insert($addonBuy);
                        db('yun_addon_version')->where('id', $value['relation_id'])->setInc('sale_num');
                        db('yun_addon_version')->where('id', $value['relation_id'])->setInc('income', $value['pay_price']);
                    }
                    $addonAuth = Db::name("yun_addon_auth")->where($addonMap)->find();
                    if (!$addonAuth) {
                        $addonAuth = array(
                            'website_id' => $value['website_id'],
                            'addon_id' => $value['relation_id'],
                            'create_time' => time(),
                            'update_time' => time()
                        );
                        db('yun_addon_auth')->insert($addonAuth);
                    }
                }
                if ($value['relation'] == 'sourcecode') {
                    $sourcecodeMap['website_id'] = $value['website_id'];
                    $sourcecodeMap['sourcecode_id'] = $value['relation_id'];
                    $sourcecodeAuth = Db::name("yun_source_code_auth")->where($sourcecodeMap)->find();
                    if(!$sourcecodeAuth){
                        $websiteAuth['website_id'] = $value['website_id'];
                        $websiteAuth['sourcecode_id'] = $value['relation_id'];
                        $websiteAuth['deadline'] = 0;
                        $chars = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
                        $update_key = "";
                        for ($i = 0; $i < 16; $i++) {
                            $rand = mt_rand(0, count($chars));
                            $update_key .= $chars[$rand];
                        }
                        $websiteAuth['update_key'] = $update_key;
                        $websiteAuth['create_time'] = time();
                        $websiteAuth['update_time'] = time();
                        $result = Db::name('yun_source_code_auth')->insert($websiteAuth);
                    }
                }
            }
        }
        return true;
    }

    public function wechatpay()
    {
        return $this->fetch();
    }

    public function wechatpay_qrcode()
    {
        $map = get_config(['group' => 'wechat_pay']);
        $options = [
            "appid" => $map['wechat_pay_appid'],
            "secret" => $map['wechat_pay_appsecret'],
            "mch_id" => $map['wechat_pay_mchid'],
            "key" => $map['wechat_pay_key'],
        ];
        $resultData = [
            'body' => "{\"h5_info\": {\"type\":\"Wap\",\"wap_url\": \"https://pay.qq.com\",\"wap_name\": \"腾讯充值\"}} ",
            /**商品描述*/
            'out_trade_no' => time(),
            /**商户订单号*/
            'total_fee' => 1,
            /**标价金额(单位分)*/
            'notify_url' => "http://store.dayongjiaoyu.cn/api/pay/wechat_pay_notify_url",
            /**通知地址(WchatConfig::$notificationURL)*/
            'trade_type' => "NATIVE",
            /**交易类型  NATIVE  MWEB  */
            'product_id' => 2
        ];
        $result = Wxpay::instance($options)->unifiedOrder()->setParam($resultData)->getPayUrl();
        qrcode(urldecode($result), 4);
    }

    public function wechat_pay_notify_url()
    {
        $map = get_config(['group' => 'wechat_pay']);
        $options = [
            "appid" => $map['wechat_pay_appid'],
            "secret" => $map['wechat_pay_appsecret'],
            "mch_id" => $map['wechat_pay_mchid'],
            "key" => $map['wechat_pay_key'],
        ];
        try {
            $callback = Wxpay::instance($options)->Notify();
            $result = $callback->getPostData();
            if ($result['return_code'] == 'SUCCESS') {
                $result["sign_status"] = $callback->checkSign() ? 1 : 0;
                if ($result['sign_status']) {
                    //支付结果验证通过，操作相关订单
                    $order_sn = $result['out_trade_no'];
                    self::pay_callback($order_sn);
                }
                $returnResult = $callback->returnResult(true);
                Log::write($returnResult);
                echo $returnResult;
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());

        }
    }
}