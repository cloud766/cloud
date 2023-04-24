<?php
namespace app\common\model;
use app\common\model\Base;

class Admin extends Base
{
	public function Adminlogin($data){
        $admin=Admin::getByusername($data['username']);
       
        if($admin){
            if($admin['password']==md5($data['password'])){
                session('uid', $admin['id']);
                session('username', $admin['username']);
                return 2; //登录密码正确的情况
            }else{
                return 3; //登录密码错误
            }
        }else{
            return 1; //用户不存在的情况
        }
    }
}
?>