<?php
namespace app\admin\controller\user;

use app\common\controller\Adminbase;
use think\Db;

class Aptitude extends Adminbase
{

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        $list = Db::name('user_aptitude')->alias('ua')
            ->field('ua.*, u.nickname')
            ->join('__USER_LIST__ u', 'u.id=ua.user_id', 'LEFT')
            ->order('id desc')->paginate();
        $this->assign('list', $list);
        $this->assign('page', $list->render());
        $this->assign('left', 'aptitude');
        return $this->fetch();
    }

    public function ajaxStatus()
    {
        $param = $this->request->param();
        $result = Db::name('user_aptitude')->where(['id' => $param['id']])->setField('status', $param['status']);
        if ($result !== false) {
            if ($param['status'] == 1) {
                Db::name('user_list')->where(['id' => $param['user_id']])->setField('aptitude', $param['type']);
            }
            return json(true);
        }
        return json(false);
    }

    public function delete()
    {
        $param = $this->request->param();
        $id = $param['id'];
        if (!isset($id)) {
            return json(false);
        }
        if (is_array($id)) {
            $map['id'] = ['in', $id];
        } else {
            $map['id'] = $id;
        }
        $result = Db::where($map)->delete();
        if ($result) {
            return json(true);
        }
        return json(false);
    }
}