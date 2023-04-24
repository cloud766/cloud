<?php
namespace app\index\controller\user;

use think\Db;
use think\Request;
use app\common\model\yun\YunWebsite as YWModel;
use app\common\model\yun\YunAddonBuy as YABModel;
use app\common\model\yun\YunAddonAuth as YAAModel;
use app\common\model\yun\YunTemplateBuy as YTBModel;
use app\common\model\yun\YunTemplateAuth as YTAModel;
use think\Session;
use app\common\controller\HomeBase;
use think\Config;
use app\common\util\Data;

class Index extends HomeBase
{

    public function _initialize()
    {
        parent::_initialize();
        $user = Session::get('user');
        if (!$user) {
            $this->redirect(url('index/user.login/login'));
        }
    }

    //站点列表
    public function domain()
    {
        $user = Session::get('user');
        $list = Db::name('yun_website')->where('user_id', $user['id'])->select();
        if ($list) {
            foreach ($list as $key => $value) {
                $auth = Db::name('yun_source_code_auth')->where(['website_id' => $value['id']])->find();
                if ($auth) {
                    $list[$key]['auth'] = 1;
                    $sourcecode = Db::name('yun_source_code')->where(['id' => $auth['sourcecode_id']])->find();
                    $list[$key]['auth_title'] = $sourcecode['title'];
                    $list[$key]['update_key'] = $auth['update_key'];
                } else {
                    $list[$key]['auth'] = 0;
                }
            }
        }
        $user = Db::name('user_list')->where('id', $user['id'])->find();
        $this->assign('user', $user);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 注册站点
     */
    public function websiteRegister()
    {
        $user = Session::get('user');
        if (Request::instance()->isPost()) {
            $data = Request::instance()->param();
            $data['user_id'] = $user['id'];
            if ($data['id']) {
                $data['update_time'] = time();
                $result = Db::name('yun_website')->where(['id' => $data['id']])->update($data);
            } else {
                $data['create_time'] = time();
                $data['update_time'] = time();
                $result = Db::name('yun_website')->insert($data);
            }
            if ($result !== false) {
                return $this->success('保存成功', url('domain'));
            } else {
                return $this->error('保存失败');
            }
        }
        $id = $this->request->param('id');
        if ($id) {
            $data = Db::name('yun_website')->where(['id' => $id, 'user_id' => $user['id']])->find();
            $this->assign('data', $data);
        }
        return $this->fetch();
    }

    /**
     * 头像和主页图片
     */
    public function portrait()
    {
        $user = Session::get('user');
        $user = Db::name('user_list')->where('id', $user['id'])->find();
        $this->assign('user', $user);
        return $this->fetch();
    }

    /**
     * 修改密码
     */
    public function reset_pwd()
    {
        $user = Session::get('user');
        $user = Db::name('user_list')->where('id', $user['id'])->find();
        $this->assign('user', $user);
        return $this->fetch();
    }

    /**
     * 个人资料
     */
    public function user_info()
    {
        $user = Session::get('user');
        $user = Db::name('user_list')->where('id', $user['id'])->find();
        $this->assign('user', $user);
        return $this->fetch();
    }

    /**
     * 保存
     */
    public function save()
    {
        $user = Session::get('user');
        if (input('post.') && $user) {
            $data = input('post.');         
            //判断submit的页面以及更新数据
            $data['update_time'] = time();
            switch ($data['page']) {
                //修改密码
                case 'reset_pwd':
                    if (empty($data['password'])) {
                        $this->error('密码为空');
                    }
                    if (!(strlen($data['password']) > 4 && strlen($data['password']) < 21)) {
                        $this->error('密码长度应在5-20位');
                    }
                    if ($data['password'] != $data['user_password_again']) {
                        return $this->error('两次输入的新密码不一致');
                    }
                    $user = Db::name('user_list')->where('id', $user['id'])->find();
                    if (md5($data['old_password']) != $user['password']) {
                        return $this->error('旧密码错误');
                    }
                    $data['password'] = md5($data['password']);
                    $result = Db::name('user_list')->where('id', $user['id'])->update($data);
                    break;
            }
            $result = Db::name('user_list')->where('id', $user['id'])->update($data);
            if ($result !== false) {
                Session::set('user', Db::name('user_list')->where('id', $user['id'])->find());
                return $this->success('保存成功');
            }
            return $this->error('保存失败');
        }
        $this->redirect(url('user_info.html'));
    }

    /**
     * 图片上传
     */
    public function upload()
    {
        // 获取表单上传文件
        $file = request()->file('myimage');
        $name = input('param.name');
        $config = get_config(['group' => 'img_info']);
        if ($file) {
            //移动到框架应用根目录/public/uploads/ 目录下
            $path = ROOT_PATH . 'public' . DS . 'uploads';
            $save = $file->move($path);
            /*水印开始*/
            if ($config['water_upload'] == 1) {
                //获取原图路径
                $path = ROOT_PATH . 'public' . DS . '\uploads' . '\\' . $save->getSaveName();
                //获取水印图路径
                $watepath = ROOT_PATH . 'public' . $config['wateimg'];
                //获取水印添加位置
                $locate = $config['water_position'];
                //获取水印透明度
                $alpha = $config['water_transparent'];  
                //添加水印
                $this->picture($watepath, $path, $locate, $alpha, $path);
            }         
            /*水印结束*/
            if ($save) {
                $res = array(
                    'cod' => 1,
                    'msg' => '上传成功',
                    'path' => '\uploads' . '\\' . $save->getSaveName()
                );
            } else {
            // 上传失败获取错误信息
                $res = array(
                    'cod' => 2,
                    'msg' => '上传失败' . ':' . $file->getError()
                );
            }
        } else {
            $res = array(
                'cod' => 3,
                'msg' => '上传失败' . '没有图片'
            );
        }

        return $res;
    }

    public function aptitude()
    {
        $user = Session::get('user');
        $user = Db::name('user_list')->where('id', $user['id'])->find();
        if ($user['aptitude'] == 1) {
            $this->redirect('user_info');
        }
        $aptitude = Db::name('user_aptitude')->where(['user_id' => $user['id'], 'status' => 0])->find();
        if ($aptitude) {
            $this->assign('aptitude', 1);
        }
        $this->assign('user', $user);
        return $this->fetch();
    }

    public function aptitudeSave()
    {
        $user = Session::get('user');
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $aptitude = Db::name('user_aptitude')->where(['user_id' => $data['user_id'], 'status' => 0])->find();
            if ($aptitude) {
                return $this->error('资质审核中，请勿重复申请');
            }
            if (!$data['id_card_front']) {
                return $this->error('请上传手持身份证正面照');
            }
            if (!$data['id_card_back']) {
                return $this->error('请上传手持身份证反面照');
            }
            if ($data['type'] == 2 && !$data['business_licence']) {
                return $this->error('请上传营业执照');
            }
            $data['create_time'] = time();
            $result = Db::name('user_aptitude')->insert($data);
            if ($result !== false) {
                return $this->success('保存成功');
            }
            return $this->error('保存失败');
        }
        $this->redirect(url('aptitude'));
    }

    public function template()
    {
        $user = Session::get('user');
        $user = Db::name('user_list')->where('id', $user['id'])->find();
        if ($user['aptitude'] == 0) {
            $this->redirect(url('user_info'));
        }
        $this->assign('user', $user);
        $list = Db::name('yun_template')->where(['user_id' => $user['id']])->order('id desc')->paginate(1);
        $this->assign('list', $list);
        $this->assign('page', $list->render());
        return $this->fetch();
    }

    public function templateAdd()
    {
        $list = Db::name('yun_template_type')->select();
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function templateSave()
    {
        $user = Session::get('user');
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['create_time'] = time();
            $data['user_id'] = $user['id'];
            $data['uploader'] = 2;
            $result = Db::name('yun_template')->insert($data);
            if ($result !== false) {
                return $this->success('保存成功', url('template'));
            }
            return $this->error('保存失败');
        }
        $this->redirect(url('template'));
    }

    public function addon()
    {
        $user = Session::get('user');
        $user = Db::name('user_list')->where('id', $user['id'])->find();
        if ($user['aptitude'] == 0) {
            $this->redirect(url('user_info'));
        }
        $this->assign('user', $user);
        $list = Db::name('yun_addon_version')->where(['user_id' => $user['id']])->order('id desc')->paginate(1);
        $this->assign('list', $list);
        $this->assign('page', $list->render());
        return $this->fetch();
    }

    public function addonAdd()
    {
        $list = Db::name('yun_addon_type')->select();
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function addonSave()
    {
        $user = Session::get('user');
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['create_time'] = time();
            $data['user_id'] = $user['id'];
            $data['uploader'] = 2;
            $result = Db::name('yun_addon_version')->insert($data);
            if ($result !== false) {
                return $this->success('保存成功', url('addon'));
            }
            return $this->error('保存失败');
        }
        $this->redirect(url('addon'));
    }

    public function wallet()
    {
        $user = Session::get('user');
        $user = Db::name('user_list')->where(['id' => $user['id']])->find();
        $income = Db::name('user_account_log')->where(['type' => 1, 'user_id' => $user['id']])->sum('money');
        $expenditure = Db::name('user_account_log')->where(['type' => 2, 'user_id' => $user['id']])->sum('money');
        $this->assign('user', $user);
        $this->assign('income', $income);
        $this->assign('expenditure', $expenditure);
        return $this->fetch();
    }

    public function accountLog()
    {
        $user = Session::get('user');
        $map['user_id'] = $user['id'];
        $startDate = $this->request->param('startDate');
        $endDate = $this->request->param('endDate');
        $type = $this->request->param('type');
        if ($startDate && $endDate) {
            $map['create_time'] = ['gt', strtotime($startDate . ' 00:00:00')];
            $map['create_time'] = ['lt', strtotime($endDate . ' 23:59:59')];
        }
        if ($type) {
            $map['type'] = $type;
        }
        $list = Db::name('user_account_log')
            ->where($map)
            ->order('id desc')
            ->paginate(Config::get('paginate.list_rows', false, ['query' => ['startDate' => $startDate, 'endDate' => $endDate]]));
        $page = $list->render();
        $list = $list->all();
        $income = Db::name('user_account_log')->where(['type' => 1, 'user_id' => $user['id']])->sum('money');
        $expenditure = Db::name('user_account_log')->where(['type' => 2, 'user_id' => $user['id']])->sum('money');
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('income', $income);
        $this->assign('expenditure', $expenditure);
        $this->assign('startDate', $startDate);
        $this->assign('endDate', $endDate);
        $this->assign('type', $type);
        return $this->fetch();
    }
    public function balanceLog()
    {
        $user = Session::get('user');
        $map['user_id'] = $user['id'];
        $startDate = $this->request->param('startDate');
        $endDate = $this->request->param('endDate');
        if ($startDate && $endDate) {
            $map['create_time'] = ['gt', strtotime($startDate . ' 00:00:00')];
            $map['create_time'] = ['lt', strtotime($endDate . ' 23:59:59')];
        }
        $list = Db::name('user_account_log')
            ->where($map)
            ->where(function ($query) {
                $query->where('project', '提现')->whereOr('project', '充值');
            })
            ->order('id desc')
            ->paginate(Config::get('paginate.list_rows', false, ['query' => ['startDate' => $startDate, 'endDate' => $endDate]]));
        $page = $list->render();
        $list = $list->all();
        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }

    public function buyAuth()
    {
        $user = Session::get('user');
        $websiteList = Db::name('yun_website')->where(['user_id' => $user['id']])->select();
        $codeList = Db::name('yun_source_code')->select();
        $this->assign('websiteList', $websiteList);
        $this->assign('codeList', $codeList);
        return $this->fetch();
    }

    public function codePrice()
    {
        $id = $this->request->param('id');
        if (!$id) {
            return json(false);
        }
        $sourcecode = Db::name('yun_source_code')->where(['id' => $id])->find();
        if (!$sourcecode) {
            return json(false);
        }
        return json($sourcecode['price']);
    }

    public function createAuthOrder()
    {
        $user = Session::get('user');
        $user_id = $user['id'];
        $relation = 'sourcecode';
        $param = $this->request->param();
        $website_id = $param['website_id'];
        $sourcecode_id = $param['sourcecode_id'];
        $price = $param['price'];
        if (!$website_id || !$sourcecode_id || !$website_id) {
            return json(['code' => 0, 'msg' => '创建订单失败']);
        }
        $auth = Db::name('yun_source_code_auth')->where(['website_id' => $website_id, 'sourcecode_id' => $sourcecode_id])->find();
        if ($auth) {
            return json(['code' => 0, 'msg' => '网站已获授权，请勿重复购买']);
        }
        $now = time();
        $oldOrder = Db::name('order')
            ->where(['user_id' => $user_id, 'relation' => $relation, 'relation_id' => $sourcecode_id, 'website_id' => $website_id, 'expiration_time' => ['gt', $now], 'pay_status' => 0])
            ->find();;
        if ($oldOrder) {
            return json(['code' => 1, 'order_sn' => $oldOrder['order_sn']]);
        }
        $order['user_id'] = $user_id;
        $order['price'] = $price;
        $order['relation_id'] = $sourcecode_id;
        $order['website_id'] = $website_id;
        $order['relation'] = $relation;
        $order['expiration_time'] = $now + 60 * 15;
        $order['order_sn'] = date('YmdHis', $now) . rand(10000, 99999);
        $order['create_time'] = $now;
        $order['update_time'] = $now;
        $id = Db::name('order')->insert($order);
        if (!$id) {
            return json(['code' => 0, 'msg' => '创建订单失败']);
        } else {
            return json(['code' => 1, 'order_sn' => $order['order_sn']]);
        }
    }

    public function order()
    {
        $user = Session::get('user');
        $list = Db::name('order')
            ->where(['user_id' => $user['id']])
            ->order('id desc')
            ->paginate(Config::get('paginate.list_rows'));
        $page = $list->render();
        $list = $list->all();
        if ($list) {
            foreach ($list as $key => $value) {
                $website = Db::name('yun_website')->where(['id' => $value['website_id']])->find();
                $list[$key]['website_name'] = $website['name'];
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
                if ($value['pay_status'] == 1) {
                    $list[$key]['order_status'] = '已支付';
                } else {
                    $list[$key]['order_status'] = '未支付';
                }
            }
        }
        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }

    public function workOrder()
    {
        $user = Session::get('user');
        $list = Db::name('work_order')
            ->where(['user_id' => $user['id']])
            ->order('id desc')
            ->paginate(Config::get('paginate.list_rows'));
        $page = $list->render();
        $list = $list->all();
        if ($list) {
            foreach ($list as $key => $value) {
                $list[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
                if ($value['finish_time']) {
                    $list[$key]['finish_time'] = date('Y-m-d H:i:s', $value['finish_time']);
                    $list[$key]['total_time'] = timeDiff($value['create_time'], $value['finish_time']);
                } else {
                    $list[$key]['finish_time'] = '';
                }
            }
        }
        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }

    public function workOrderType()
    {
        $categoryList = Db::name('work_order_category')->where(['parent_id' => 0])->order('sort')->select();
        $this->assign('categoryList', $categoryList);
        return $this->fetch();
    }

    public function workOrderAdd()
    {
        $id = $this->request->param('id');
        $user = Session::get('user');
        $data = Db::name('work_order')->where(['user_id' => $user['id'], 'id' => $id])->find();
        if ($data) {
            $category_id = $data['category_id'];
        } else {
            $category_id = $this->request->param('category_id');
        }
        $category = Db::name('work_order_category')->where(['id' => $category_id])->find();
        $categoryList = Db::name('work_order_category')->where(['parent_id' => $category_id])->order('sort')->select();
        $websiteList = Db::name('yun_website')->where(['user_id' => $user['id']])->select();
        $this->assign('data', $data);
        $this->assign('websiteList', $websiteList);
        $this->assign('category', $category);
        $this->assign('categoryList', $categoryList);
        return $this->fetch();
    }

    public function workOrderSave()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $user = Session::get('user');
            $data['user_id'] = $user['id'];
            $result = $this->validate($data, 'workOrder');
            if ($result !== true) {
                $this->error($result, '', $token);
            }
            $data['create_time'] = time();
            $data['status'] = 0;
            $data['order_sn'] = date('YmdHis', time()) . rand(10000, 99999);
            if ($data['id']) {
                $result = Db::name('work_order')->where(['id' => $data['id'], 'user_id' => $user['id']])->update($data);
            } else {
                $result = Db::name('work_order')->insert($data);
            }
            if ($result !== false) {
                $this->success('提交成功', url('workOrder'));
            } else {
                $this->error('提交失败，请稍后重试');
            }
        }
    }

    public function workOrderMore()
    {
        $id = $this->request->param('id');
        $user = Session::get('user');
        $data = Db::name('work_order')->where(['user_id' => $user['id'], 'id' => $id])->find();
        $this->assign('data', $data);
        return $this->fetch();
    }

    public function workOrderMoreSave()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $user = Session::get('user');
            if (!empty($data['more_info_content'])) {
                $data['more_info_content'] = htmlspecialchars($data['more_info_content']);
                $data['more_info_status'] = 0;
                $result = Db::name('work_order')->where(['id' => $data['id'], 'user_id' => $user['id']])->update($data);
                if ($result !== false) {
                    $this->success('提交成功', url('workOrder'));
                } else {
                    $this->error('提交失败，请稍后重试');
                }
            } else {
                return json(['code' => 0, 'msg' => '请填写补充信息']);
            }
        }
    }
} 