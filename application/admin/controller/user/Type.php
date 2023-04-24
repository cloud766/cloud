<?php
namespace app\admin\controller\user;

use app\common\controller\Adminbase;
use app\common\model\user\UserType as UTModel;

class Type extends Adminbase
{

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        $list = UTModel::getListByMap();
        $this->assign('list', $list);
        $this->assign('left', 'type');
        return $this->fetch();
    }

    public function edit()
    {
        $id = $this->request->param('id');
        $data = UTModel::getDataByMap(['id' => $id]);
        $this->assign('data', $data);
        $this->assign('left', 'type');
        return $this->fetch();
    }

    public function save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $result = $this->validate($data, 'UserType');
            if ($result !== true) {
                $token = $this->request->token('__token__', 'sha1');
                $this->error($result, '', $token);
            }
            if ($data['id']) {
                $result = UTModel::editData($data, ['id' => $data['id']]);
            } else {
                $result = UTModel::addData($data);
            }
            if ($result) {
                return $this->success('保存成功', url('admin/user.type/index'));
            }
            return $this->error('保存失败');
        }
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
        $result = UTModel::deleteData($map);
        if ($result) {
            return json(true);
        }
        return json(false);
    }
}