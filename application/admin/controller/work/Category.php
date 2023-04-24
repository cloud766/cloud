<?php
namespace app\admin\controller\work;

use think\Db;
use app\common\controller\Adminbase;
use app\common\util\Data;
/**
 * 工单分类列表
 */
class Category extends Adminbase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 工单分类列表
     * @return [type] [description]
     */
    public function index()
    {
        $list = Db::name('work_order_category')->order('sort')->select();
        $list = Data::tree($list, 'title', 'id', 'parent_id');
        $this->assign('list', $list);
        $this->assign('left', 'category');
        return $this->fetch();
    }

    /**
     * 编辑工单分类
     * @return [type] [description]
     */
    public function edit()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = Db::name('work_order_category')->where('id', $id)->find();
        $list = Db::name('work_order_category')->order('sort')->select();
        $list = Data::tree($list, 'title', 'id', 'parent_id');
        $this->assign('data', $data);
        $this->assign('list', $list);
        $this->assign('left', 'category');
        return $this->fetch();
    }

    /**
     * 保存工单分类
     * @return [type] [description]
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 验证数据合法性
            if (!$data['id']) {
                //存入时间戳
                $data['create_time'] = time();
                $result = Db::name('work_order_category')->insert($data);
            } else {
                $id = $data['id'];
                unset($data['id']);
                //存入时间戳
                $data['update_time'] = time();
                $result = Db::name('work_order_category')->where('id', $id)->update($data);
            }
            if ($result !== false) {
                return $this->success('保存成功', url('/admin/work.category/index'));
            }
            $token = $this->request->token('__token__', 'sha1');
            return $this->error('保存失败');
        }
        $this->redirect(url('/admin/work.category/index'));
    }

    /**
     * 删除工单分类
     * @return [type] [description]
     */
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
        $result = Db::name('work_order_category')->where($map)->delete();
        if ($result) {
            return json(true);
        }
        return json(false);
    }

}