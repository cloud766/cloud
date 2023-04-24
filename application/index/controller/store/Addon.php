<?php
namespace app\index\controller\store;

use app\index\controller\store\Base;
use app\common\model\yun\YunAddonVersion as YAVModel;
use app\common\model\yun\YunAddonType as YATModel;
use app\common\model\yun\YunAddonBuy as YABModel;
use app\common\model\yun\YunAddonAuth as YAAModel;
use app\common\model\yun\YunWebsite as YWModel;
use app\common\model\user\UserList as ULModel;
use app\common\model\Order as OModel;
use think\Db;

class Addon extends Base
{
    /**
     * 首页
     */
    public function index()
    {
        $cid = $this->request->param('cid');
        $time = $this->request->param('time');
        $price = $this->request->param('price');
        $keyword = $this->request->param('keyword');
        $free = $this->request->param('free');
        $map = [];
        if ($cid) {
            $map['type'] = $cid;
        }
        if ($keyword) {
            $map['title'] = ['like', '%' . $keyword . '%'];
        }
        if ($free) {
            $map['price'] = 0;
        }
        $order = 'sale_num desc';
        $order = $time ? $time == 'up' ? 'id asc' : 'id desc' : $order;
        $order = $price ? $price == 'up' ? 'price asc' : 'price desc' : $order;
        $map['status'] = 1;
        $list = Db::name('yun_addon')->alias('ya')
            ->field('ya.*,yat.name as type_name')
            ->join('__YUN_ADDON_TYPE__ yat', 'ya.type = yat.id', 'LEFT')
            ->where($map)
            ->order($order)
            ->paginate(20, false, ['query' => ['time' => $time, 'price' => $price, 'free' => $free]]);
        $typeList = Db::name('yun_addon_type')->select();
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('typeList', $typeList);
        $this->assign('page', $page);
        $this->assign('time', $time);
        $this->assign('price', $price);
        $this->assign('cid', $cid);
        $this->assign('free', $free);
        $this->assign('keyword', $keyword);
        return $this->fetch();
    }

    public function detail()
    {
        $id = $this->request->param('id');
        $data = Db::name('yun_addon')->where(['id' => $id])->find();
        $user = session('user');
        if ($data) {
            $data['pic_f'] = explode(',', $data['pic_f']);
            $this->assign('data', $data);
            $websiteList = Db::name('yun_website')->where(['user_id' => $user['id']])->select();
            $this->assign('websiteList', $websiteList);
            // $buy = YABModel::getDataByMap(['addon_id' => $data['id'], 'user_id' => $user['id']]);
            // $buy = $buy ? true : false;
            // $this->assign('buy', $buy);
            // $data['cate'] = YATModel::getDataByMap(['id' => $data['type']]);

            // $downloadCount = db('yun_addon_download')->where(['addon_id' => $data['id']])->count();
            // $this->assign('downloadCount', $downloadCount);

            // $list = YAVModel::getListByMap(['user_id'=>$data['user_id'], 'id'=>['neq', $data['id']]], 6);
            // $this->assign('list', $list);
        }
        return $this->fetch();
    }

    public function balancePay()
    {
        if (input('post.')) {
            $data = input('post.');
            //扣除购买者余额
            $map['user_id'] = $data['user_id'];
            $map['addon_id'] = $data['addon_id'];
            $buy = YABModel::getDataByMap($map);
            if ($buy) {
                return json(['code' => 0, 'msg' => '请勿重复购买']);
            }
            $user = db('user_list')->where('id', $data['user_id'])->find();
            if ($user['balance'] < $data['price']) {
                return json(['code' => 0, 'msg' => '余额不足']);
            }
            db('user_list')->where('id', $data['user_id'])->setDec('balance', $data['price']);
            //存入yun_temp_buy
            $sql = array(
                'user_id' => $data['user_id'],
                'addon_id' => $data['addon_id'],
                'buy_time' => time(),
                'buy_price' => $data['price']
            );
            $table_buy = db('yun_addon_buy')->insert($sql); 
            //修改yun_addon插件销售数据
            $table_temp = db('yun_addon_version')->where('id', $data['addon_id'])->setInc('sale_num');
            db('yun_addon_version')->where('id', $data['addon_id'])->setInc('income', $data['price']);
            //为网站添加授权
            $website = YWModel::getListByMap(['user_id' => $data['user_id']]);
            if ($website) {
                foreach ($website as $key => $value) {
                    $auth['website_id'] = $value['id'];
                    $auth['addon_id'] = $data['addon_id'];
                    YAAModel::addData($auth);
                }
            }
            return json(['code' => 1, 'msg' => '购买成功']);
        }
    }
} 