<?php
namespace app\admin\controller;

use think\db;
use think\Controller;
use app\common\model\Admin;
use gt3\GeetestLib;
use app\common\controller\Adminbase;

class Login extends Controller
{
    public function _initialize()
    {
        $header = $this->request->header();
        if ($header['host'] != 'cloud.dayongjiaoyu.cn') {
            exit();
        }
    }

    public function index()
    {
        if (request()->isPost()) {
            $admin = new Admin();
            $num = $admin->Adminlogin(input('post.'));
            if ($num == 1) {
                $this->error('用户不存在！');
            }
            if ($num == 2) {
                $this->success('登录成功！', url('index/index'));
            }
            if ($num == 3) {
                $this->error('密码错误！');
            }
            return;
        }
        $list = Db('config')->where(array('name' => 'open_login_code'))->find();
        $flag = false;
        if ($list['value'] == 1) {
            $flag = true;
        }
        $this->assign('flag', $flag);
        return view();
    }

    public function gt()
    {
        $GtSdk = new GeetestLib('57e091046ca1626046194146391757bf', '9513de412604e4cf4dd26f8c3cdf5613');
        session_start();
        $data = array(
            "user_id" => "test", # 网站用户id
            "client_type" => "web", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
            "ip_address" => "127.0.0.1" # 请在此处传输用户请求验证时所携带的IP
        );
        $status = $GtSdk->pre_process($data, 1);
        $_SESSION['gtserver'] = $status;
        $_SESSION['user_id'] = $data['user_id'];
        return json(json_decode($GtSdk->get_response_str()));
    }

    public function gt_check()
    {
        $param = $this->request->param();
        $GtSdk = new GeetestLib('57e091046ca1626046194146391757bf', '9513de412604e4cf4dd26f8c3cdf5613');
        $result = $GtSdk->success_validate($param['geetest_challenge'], $param['geetest_validate'], $param['geetest_seccode'], $param, 1);
        return json($result);
    }

    public function logout()
    {
        session('uid', null);
        session('username', null);
        $this->success('退出成功', 'login/index');
    }

    public function editPassword()
    {
        $this->assign('id', session('uid'));
        return $this->fetch();
    }

    public function savePassword()
    {
        $param = $this->request->param();
        if (!$param['password'] || trim($param['password']) == '') {
            $this->error('请输入旧密码', url('admin/login/editPassword'));
        }
        if (!$param['password1'] || trim($param['password1']) == '') {
            $this->error('请输入新密码', url('admin/login/editPassword'));
        }
        if (!$param['password2'] || trim($param['password2']) == '') {
            $this->error('请输入重复密码', url('admin/login/editPassword'));
        }
        $admin = Admin::getDataByMap(['id' => $param['id']]);
        if (!$admin) {
            $this->error('请重新登录', url('admin/login/editPassword'));
        }
        if (md5($param['password']) != $admin['password']) {
            $this->error('密码错误', url('admin/login/editPassword'));
        }
        if ($param['password1'] != $param['password2']) {
            $this->error('两次输入的密码不一致', url('admin/login/editPassword'));
        }
        $result = Admin::editData(['password' => md5($param['password1'])], ['id' => $param['id']]);
        if ($result) {
            $this->success('修改成功', url('admin/login/editPassword'));
        }
        $this->error('修改失败', url('admin/login/editPassword'));
    }
}
