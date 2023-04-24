<?php
namespace app\index\controller\store;

use app\index\controller\store\Base;
use app\common\model\yun\YunTemplate as YTModel;
use app\common\model\yun\YunTemplateType as YTTModel;
use app\common\model\yun\YunTemplateBuy as YTBModel;
use app\common\model\yun\YunTemplateAuth as YTAModel;
use app\common\model\yun\YunWebsite as YWModel;
use app\common\model\user\UserList as ULModel;
use app\common\model\Order as OModel;
use think\Db;
use mikkle\tp_alipay\Alipay;
use mikkle\tp_wxpay\Wxpay;
use think\Session;

class Template extends Base
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
        $list = YTModel::alias('yt')
            ->field('yt.*,ytt.name as type_name')
            ->join('__YUN_TEMPLATE_TYPE__ ytt', 'yt.type = ytt.id', 'LEFT')
            ->where($map)
            ->order($order)
            ->paginate(20, false, ['query' => ['time' => $time, 'price' => $price, 'free' => $free]]);
        $page = $list->render();
        $typeList = Db::name('yun_template_type')->select();
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
        $data = YTModel::getDataByMap(['id' => $id]);
        $user = Session::get('user');
        if ($data) {
            $data['pic_f'] = explode(',', $data['pic_f']);
            $this->assign('data', $data);
            $websiteList = Db::name('yun_website')->where(['user_id'=>$user['id']])->select();
            $this->assign('websiteList', $websiteList);
            // $buy = YTBModel::getDataByMap(['template_id' => $data['id'], 'user_id' => $user['id']]);
            // $buy = $buy ? true : false;
            // $this->assign('buy', $buy);

            // $downloadCount = db('yun_template_download')->where(['template_id' => $data['id']])->count();
            // $this->assign('downloadCount', $downloadCount);

            // $list = YTModel::getListByMap(['user_id' => $data['user_id'], 'id' => ['neq', $data['id']]], 6);
            // $this->assign('list', $list);

            // $this->assign('user', $user);
        }
        return $this->fetch();
    }

    /**
     * 余额购买模板 
     */
    public function balancePay()
    {
        if (input('post.')) {
            $data = input('post.');
            //扣除购买者余额
            $map['user_id'] = $data['user_id'];
            $map['template_id'] = $data['template_id'];
            $buy = YTBModel::getDataByMap($map);
            if ($buy) {
                return json(['code' => 0, 'msg' => '请勿重复购买']);
            }
            $user = db('user_list')->where('id', $data['user_id'])->find();
            if (!$user || $user['balance'] < $data['price']) {
                return json(['code' => 0, 'msg' => '余额不足']);
            }
            db('user_list')->where('id', $data['user_id'])->setDec('balance', $data['price']);
            //存入yun_temp_buy
            $sql = array(
                'user_id' => $data['user_id'],
                'template_id' => $data['template_id'],
                'buy_time' => time(),
                'buy_price' => $data['price']
            );
            $table_buy = db('yun_template_buy')->insert($sql); 
            //修改yun_template模板销售数据
            $table_temp = db('yun_template')->where('id', $data['template_id'])->setInc('sale_num');
            db('yun_template')->where('id', $data['template_id'])->setInc('income', $data['price']);
            //为网站添加授权
            $website = YWModel::getListByMap(['user_id' => $data['user_id']]);
            if ($website) {
                foreach ($website as $key => $value) {
                    $auth['website_id'] = $value['id'];
                    $auth['template_id'] = $data['template_id'];
                    YTAModel::addData($auth);
                }
            }
            return json(['code' => 1, 'msg' => '购买成功']);
        }
    }

    public function release()
    {
        $user = Session::get('user');
        if (!$user) {
            $this->redirect(url('index/store.index/index'));
        }
        //取出类别
        $cate = Db::name('yun_template_type')->select();
        $this->assign('cate', $cate);
        return $this->fetch();
    }

    /**
     * 保存
     */
    public function save()
    {
        if (input('post.')) {
            $data = input('post.');
            $user = Session::get('user');
            //存入模板表yun_template
            $result = array(
                'title' => $data['name'],
                'type' => $data['type'],
                'pic_a' => $data['pic_a'],
                'pic_f' => $data['path'],
                'down_path' => $data['down_path'],
                'content' => $data['content'],
                'price' => $data['price'],
                'create_time' => time(),
                'user_id' => $user['id'],
                'uploader' => 2,
                'author' => $user['nickname']
            );
            $relation_id = Db::name('yun_template')->insertGetId($result);
            //存入图片表gallery
            // $result = array(
            //     'path'=>$data['path'],
            //     'relation'=>'yun_template',
            //     'create_time'=>time(),
            //     'relation_id'=>$relation_id
            // );
            // $result = Db::name('gallery')->insert($result);
            if ($result !== false) {
                return $this->success('保存成功', url('/index/store/template/release'));
            }
            return $this->error('保存失败');
        }
    }
} 