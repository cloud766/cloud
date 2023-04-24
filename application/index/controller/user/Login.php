<?php
namespace app\index\controller\user;

use app\common\controller\HomeBase;
use app\common\model\user\UserList as ULModel;

class Login extends HomeBase
{

    public function _initialize()
    {
        parent::_initialize();
    }

    public function login()
    {
        if (request()->isPost()) {
            $data = $this->request->param();
            // if(!captcha_check($data['verify'])) {
            //     return $this->error('验证码不正确');
            // }
            if (!$data['username']) {
                $this->error('请输入用户名');
            }
            if (!$data['password']) {
                $this->error('请输入密码');
            }
            $user = ULModel::getDataByMap(['username' => $data['username']]);
            if (!$user) {
                $this->error('用户不存在');
            }
            if (md5($data['password']) != $user['password']) {
                $this->error('密码错误');
            }
            session('user', $user);
            $this->success('登录成功', url('index/index/index'));
        }
        return $this->fetch();
    }

    public function loginout()
    {
        session('user', null);
        $this->redirect(url('index/index/index'));
    }

    public function register()
    {
        if (request()->isPost()) {
            $data = input('post.');
            //验证验证码
            // if(!captcha_check($data['verify'])) {
            //     return $this->error('验证码不正确');
            // }
            //验证用户名
            if (empty($data['username'])) {
                $this->error('用户名为空');
            } else if (ULModel::getDataByMap(['username' => $data['username']])) {
                $this->error('用户名已存在');
            }
            //验证密码
            if (empty($data['password'])) {
                $this->error('密码为空');
            }
            if (!(strlen($data['password']) > 4 && strlen($data['password']) < 21)) {
                $this->error('密码长度应在5-20位');
            }
            if ($data['password'] != $data['repassword']) {
                $this->error('两次密码输入不一致');
            }
            //存入数据库
            $data['password'] = md5($data['password']);
            $result = ULModel::addData($data);
            if (!$result) {
                $this->error('注册失败');
            } else {
                $this->success('注册成功', url('index/user.login/login'));
            }
        }
        return $this->fetch();
    }

    public function forgetpwd()
    {
        if (request()->isPost()) {
            $data = input('post.');
            //验证验证码
            // if(!captcha_check($data['verify'])) {
            //     return $this->error('验证码不正确');
            // }
            //验证用户名
            if (empty($data['username'])) {
                $this->error('用户名为空');
            } else if (ULModel::getDataByMap(['username' => $data['username']])) {
                $this->error('用户名已存在');
            }
            //验证密码
            if (empty($data['password'])) {
                $this->error('密码为空');
            }
            if (!(strlen($data['password']) > 4 && strlen($data['password']) < 21)) {
                $this->error('密码长度应在5-20位');
            }
            if ($data['password'] != $data['repassword']) {
                $this->error('两次密码输入不一致');
            }
            //存入数据库
            $data['password'] = md5($data['password']);
            $result = ULModel::addData($data);
            if (!$result) {
                $this->error('注册失败');
            } else {
                $this->success('注册成功', url('index/user.login/login'));
            }
        }
        return $this->fetch();
    }
} 