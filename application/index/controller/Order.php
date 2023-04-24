<?php
namespace app\index\controller;

use app\common\controller\HomeBase;
use think\Db;
use think\Session;
use mikkle\tp_wxpay\Wxpay;
use mikkle\tp_alipay\Alipay;
use app\common\model\yun\YunTemplate as YTModel;
use app\common\model\yun\YunTemplateBuy as YTBModel;
use app\common\model\yun\YunTemplateAuth as YTAModel;
use app\common\model\yun\YunWebsite as YWModel;
use app\common\model\yun\YunAddonVersion as YAVModel;
use app\common\model\yun\YunAddonBuy as YABModel;
use app\common\model\yun\YunAddonAuth as YAAModel;

class Order extends HomeBase
{
    public function _initialize()
    {
        parent::_initialize();
        $user = Session::get('user');
        if (!$user) {
            $this->redirect(url('index/user.login/login'));
        }
    }

    /**
     * 购物车页面
     */
    public function cart()
    {
        $user = Session::get('user');
        Session::delete('cart_buy_ids');
        $list = Db::name('order_cart')->where(['user_id' => $user['id']])->order('id desc')->select();
        if ($list) {
            foreach ($list as $key => $value) {
                $website = Db::name('yun_website')->where(['id' => $value['website_id']])->find();
                $list[$key]['website_name'] = $website['name'];
                $list[$key]['website_domain'] = $website['domain'];
                switch ($value['relation']) {
                    case 'addon':
                        $project = Db::name('yun_addon')->where(['id' => $value['relation_id']])->find();
                        break;
                    case 'template':
                        $project = Db::name('yun_template')->where(['id' => $value['relation_id']])->find();
                        break;
                    case 'sourcecode':
                        $project = Db::name('yun_source_code')->where(['id' => $value['relation_id']])->find();
                        break;

                    default:
                # code...
                        break;
                }
                $list[$key]['project_name'] = $project['title'];
            }
        }
        $count = count($list);
        $this->assign('list', $list);
        $this->assign('count', $count);
        return $this->fetch();
    }

    public function deteleCart()
    {
        $user = Session::get('user');
        $id = $this->request->param('id');
        if (!$id) {
            return json(['code' => 0, 'msg' => '删除失败']);
        }
        $result = Db::name('order_cart')->where(['id' => $id, 'user_id' => $user['id']])->delete();
        if (!$result) {
            return json(['code' => 0, 'msg' => '删除失败']);
        }
        return json(['code' => 1, 'msg' => '删除成功']);
    }

    /**
     * 添加购物车
     */
    public function addCart()
    {
        $cart = $this->request->param();
        $user = Session::get('user');
        $cart['user_id'] = $user['id'];
        $oldData = Db::name('order_cart')->where($cart)->find();
        if ($oldData) {
            return json(['code' => 0, 'msg' => '请勿重复添加']);
        }
        switch ($cart['relation']) {
            case 'addon':
                //判断是否已授权 
                $auth = Db::name('yun_addon_auth')->where(['website_id' => $cart['website_id'], 'addon_id' => $cart['relation_id']])->find();
                if ($auth) {
                    return json(['code' => 0, 'msg' => '已获授权， 请勿重复购买']);
                }
                $project = Db::name('yun_addon')->where(['id' => $cart['relation_id']])->find();
                break;
            case 'template':
                //判断是否已授权 
                $auth = Db::name('yun_template_auth')->where(['website_id' => $cart['website_id'], 'template_id' => $cart['relation_id']])->find();
                if ($auth) {
                    return json(['code' => 0, 'msg' => '已获授权， 请勿重复购买']);
                }
                $project = Db::name('yun_template')->where(['id' => $cart['relation_id']])->find();
                break;
            case 'sourcecode':
                //判断是否已授权 
                $auth = Db::name('yun_source_code_auth')->where(['website_id' => $cart['website_id'], 'sourcecode_id' => $cart['relation_id']])->find();
                if ($auth) {
                    return json(['code' => 0, 'msg' => '已获授权， 请勿重复购买']);
                }
                $project = Db::name('yun_source_code')->where(['id' => $cart['relation_id']])->find();
                break;

            default:
                # code...
                break;
        }
        $cart['price'] = $project['price'];
        $discount = 100;
        $cart['pay_price'] = ($project['price'] / 100) * $discount;
        $validate = $this->validate($cart, 'Cart');
        if ($validate !== true) {
            return json(['code' => 0, 'msg' => '添加购物车失败，请稍后重试']);
        }
        $cart['create_time'] = time();
        $cart['update_time'] = time();
        $result = Db::name('order_cart')->insert($cart);
        if ($result) {
            return json(['code' => 1, 'url' => url('index/order/cart'), 'msg' => '添加成功']);
        } else {
            return json(['code' => 0, 'msg' => '添加购物车失败，请稍后重试']);
        }
    }

    /**
     * 立即购买
     */
    public function buyNow()
    {
        $cart = $this->request->param();
        $user = Session::get('user');
        //判断是否加入了购物车
        $cart['user_id'] = $user['id'];
        $oldData = Db::name('order_cart')->where($cart)->find();
        if ($oldData) {
            $cartId = $oldData['id'];
        } else {
            switch ($cart['relation']) {
                case 'addon':
                    //判断是否已授权 
                    $auth = Db::name('yun_addon_auth')->where(['website_id' => $cart['website_id'], 'addon_id' => $cart['relation_id']])->find();
                    if ($auth) {
                        return json(['code' => 0, 'msg' => '已获授权， 请勿重复购买']);
                    }
                    $project = Db::name('yun_addon')->where(['id' => $cart['relation_id']])->find();
                    break;
                case 'template':
                    //判断是否已授权 
                    $auth = Db::name('yun_template_auth')->where(['website_id' => $cart['website_id'], 'template_id' => $cart['relation_id']])->find();
                    if ($auth) {
                        return json(['code' => 0, 'msg' => '已获授权， 请勿重复购买']);
                    }
                    $project = Db::name('yun_template')->where(['id' => $cart['relation_id']])->find();
                    break;
                case 'sourcecode':
                    //判断是否已授权 
                    $auth = Db::name('yun_source_code_auth')->where(['website_id' => $cart['website_id'], 'sourcecode_id' => $cart['relation_id']])->find();
                    if ($auth) {
                        return json(['code' => 0, 'msg' => '已获授权， 请勿重复购买']);
                    }
                    $project = Db::name('yun_source_code')->where(['id' => $cart['relation_id']])->find();
                    break;

                default:
                    # code...
                    break;
            }
            $cart['price'] = $project['price'];
            $discount = 100;
            $cart['pay_price'] = ($project['price'] / 100) * $discount;
            $validate = $this->validate($cart, 'Cart');
            if ($validate !== true) {
                return json(['code' => 0, 'msg' => '添加购物车失败，请稍后重试']);
            }
            $cart['create_time'] = time();
            $cart['update_time'] = time();
            $cartId = Db::name('order_cart')->insertGetId($cart);
            if (!$cartId) {
                return json(['code' => 0, 'msg' => '添加购物车失败，请稍后重试']);
            }
        }
        Session::set('cart_buy_ids', $cartId);
        return json(['code' => 1, 'url' => url('index/order/confirm')]);
    }

    /**
     * 计算订单价格
     */
    public function calculatePrice()
    {
        $ids = $this->request->param('ids');
        $price = 0;
        if (!$ids) {
            Session::set('cart_buy_ids', null);
            return json(['code' => 0, 'price' => $price]);
        }
        $list = Db::name('order_cart')->where(['id' => ['in', $ids]])->select();
        if ($list) {
            foreach ($list as $key => $value) {
                $price += $value['pay_price'];
            }
        }
        Session::set('cart_buy_ids', $ids);
        return json(['code' => 1, 'price' => $price]);
    }

    /**
     * 确认订单
     */
    public function confirm()
    {
        $user = Session::get('user');
        $ids = Session::get('cart_buy_ids');
        $list = Db::name('order_cart')->where(['user_id' => $user['id'], 'id' => ['in', $ids]])->order('id desc')->select();
        $price = 0;
        if ($list) {
            foreach ($list as $key => $value) {
                $website = Db::name('yun_website')->where(['id' => $value['website_id']])->find();
                $list[$key]['website_name'] = $website['name'];
                $list[$key]['website_domain'] = $website['domain'];
                switch ($value['relation']) {
                    case 'addon':
                        $project = Db::name('yun_addon')->where(['id' => $value['relation_id']])->find();
                        break;
                    case 'template':
                        $project = Db::name('yun_template')->where(['id' => $value['relation_id']])->find();
                        break;
                    case 'sourcecode':
                        $project = Db::name('yun_source_code')->where(['id' => $value['relation_id']])->find();
                        break;

                    default:
                # code...
                        break;
                }
                $list[$key]['project_name'] = $project['title'];
                $price += $value['pay_price'];
            }
        }
        $this->assign('list', $list);
        $this->assign('count', $count);
        $this->assign('price', $price);
        return $this->fetch();
    }

    /**
     * 创建统一支付订单
     */
    public function submitOrder()
    {
        $user = Session::get('user');
        $ids = Session::get('cart_buy_ids');
        $list = Db::name('order_cart')->where(['user_id' => $user['id'], 'id' => ['in', $ids]])->order('id desc')->select();
        if (!$list) {
            return json(['code' => 0, 'msg' => '未选择购买商品，请确保购物车已选中需要购买的商品', 'url' => url('index')]);
        }
        $now = time();
        $total_price = 0;
        $total_pay_price = 0;
        //创建用户订单
        $orderIds = [];
        foreach ($list as $key => $value) {
            $order['user_id'] = $user['id'];
            $order['website_id'] = $value['website_id'];
            $order['relation'] = $value['relation'];
            $order['relation_id'] = $value['relation_id'];
            $order['price'] = $value['price'];
            $order['pay_price'] = $value['pay_price'];
            $order['create_time'] = $now;
            $order['update_time'] = $now;
            $order['expiration_time'] = $now + 15 * 60;
            $order['order_sn'] = date('YmdHis', $now) . rand(10000, 99999);
            $id = Db::name('order')->insertGetId($order);
            $total_pay_price += $value['pay_price'];
            $total_price += $value['price'];
            $orderIds[] = $id;
        }
        $orderIds = implode($orderIds);
        //创建支付订单
        $payOrder['order_sn'] = date('YmdHis', $now) . rand(10000, 99999);
        $payOrder['relation_id'] = $orderIds;
        $payOrder['expiration_time'] = $now + 15 * 60;
        $payOrder['user_id'] = $user['id'];
        $payOrder['create_time'] = $now;
        $payOrder['pay_price'] = $total_pay_price;
        $payOrder['price'] = $total_price;
        $payId = Db::name('order_pay')->insertGetId($payOrder);
        if (!$payId) {
            return json(['code' => 0, 'msg' => '创建订单失败']);
        }
        //清除购物车
        Db::name('order_cart')->where(['user_id' => $user['id'], 'id' => ['in', $ids]])->delete();
        //清除id缓存
        Session::delete('cart_buy_ids');
        return json(['code' => 1, 'order_sn' => $payOrder['order_sn'], 'msg' => '订单创建成功，正在为您跳转支付页面', 'url' => url('pay', array('order_sn' => $payOrder['order_sn']))]);
    }

    /**
     * 创建单个商品支付订单
     */
    public function submitSingleOrderPay()
    {
        $user = Session::get('user');
        $order_sn = $this->request->param('order_sn');
        if (!$order_sn) {
            return json(['code' => 0, 'msg' => '参数错误，无法创建支付订单']);
        }
        $order = Db::name('order')->where(['order_sn' => $order_sn, 'user_id' => $user['id']])->find();
        if (!$order) {
            return json(['code' => 0, 'msg' => '订单不存在，无法创建支付订单']);
        }
        $orderPay = Db::name('order_pay')->where(['user_id' => $user['id'], 'relation_id' => $order['id']])->find();
        if ($orderPay) {
            return json(['code' => 1, 'url' => url('pay', array('order_sn' => $orderPay['order_sn']))]);
        }
        $now = time();
        $orderPay['order_sn'] = date('YmdHis', $now) . rand(10000, 99999);
        $orderPay['relation_id'] = $order['id'];
        $orderPay['expiration_time'] = $now + 15 * 60;
        $orderPay['user_id'] = $user['id'];
        $orderPay['create_time'] = $now;
        $orderPay['pay_price'] = $order['pay_price'];
        $orderPay['price'] = $order['price'];
        $result = Db::name('order_pay')->insert($orderPay);
        if ($result) {
            return json(['code' => 1, 'url' => url('pay', array('order_sn' => $orderPay['order_sn']))]);
        } else {
            return json(['code' => 0, 'msg' => '创建支付订单失败，请稍后重试']);
        }
    }

    /**
     * 订单支付页面
     */
    public function pay()
    {
        $user = Session::get('user');
        $order_sn = $this->request->param('order_sn');
        $order = Db::name('order_pay')->where(['order_sn' => $order_sn, 'user_id' => $user['id']])->find();
        $this->assign('order', $order);
        return $this->fetch();
    }

    /**
     * 检测订单是否可以支付
     */
    public function checkOrderPayStatus()
    {
        $order_sn = $this->request->param('order_sn');
        if (!$order_sn) {
            return json(['code' => 0, 'msg' => '参数错误，无法支付']);
        }
        $orderPay = Db::name('order_pay')->where(['order_sn' => $order_sn])->find();
        if (!$orderPay) {
            return json(['code' => 0, 'msg' => '查询不到指定订单，无法支付']);
        }
        if ($orderPay['pay_status'] == 1) {
            return json(['code' => 0, 'msg' => '订单已支付，请勿重复支付']);
        }
        if (!$orderPay['relation_id']) {
            return json(['code' => 0, 'msg' => '订单状态异常（code:10001），请联系客服']);
        }
        $orderList = Db::name('order')->where(['id' => ['in', $orderPay['relation_id']]])->select();
        if (!$orderList) {
            return json(['code' => 0, 'msg' => '订单状态异常（code:10002），请联系客服']);
        }
        $orderCheck = true;
        foreach ($orderList as $key => $value) {
            if ($value['pay_status'] == 1) {
                $orderCheck = false;
                break;
            }
        }
        if (!$orderCheck) {
            return json(['code' => 0, 'msg' => '订单存在已支付的商品，请前往 个人中心-订单 查看', 'url' => url('index/user.index/order')]);
        }
        return json(['code' => 1]);
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
        $order = Db::name('order_pay')->where(['order_sn' => $order_sn])->find();
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
            'body' => '弘讯应用商店',
            /**商品描述*/
            'out_trade_no' => $order['order_sn'],
            /**商户订单号*/
            'total_fee' => $order['pay_price'] * 100,
            /**标价金额(单位分)*/
            // 'total_fee' => 1, /**标价金额(单位分)*/
            "notify_url" => "https://api.dayongjiaoyu.cn/api/pay/wechat_pay_notify_url",
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
        $order = Db::name('order_pay')->where(['order_sn' => $order_sn])->find();
        if (!$order) {
            return false;
        }
        if ($order['pay_status'] == 1) {
            return false;
        }
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
                "notify_url" => "https://api.dayongjiaoyu.cn/api/pay/alipay_notify_url"
            ])
            ->setBizContentParam([
                "subject" => '弘讯应用商店',
                "out_trade_no" => $order['order_sn'],
                "total_amount" => $order['price'],
                // "total_amount"=>"0.01",
            ])->getResult();
        $result = Alipay::instance($options)->Precreate()->getResponseArray();
        if ($result['alipay_trade_precreate_response']['code'] == 10000) {
            qrcode(urldecode($result['alipay_trade_precreate_response']['qr_code']), 4);
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
        $order = Db::name('order_pay')->where(['order_sn' => $order_sn])->find();
        if (!$order) {
            return json(['code' => 0, 'msg' => '订单错误']);
        }
        if ($order['pay_status'] == 1) {
            return json(['code' => 1, 'msg' => '订单支付成功']);
        }
        return json(['code' => 0, 'msg' => '订单未支付']);
    }
}
