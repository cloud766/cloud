<?php
namespace app\admin\controller\work;

use think\Db;
use app\common\controller\Adminbase;
/**
 * 工单列表
 */
class Index extends Adminbase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 工单列表
     * @return [type] [description]
     */
    public function index()
    {
        $s = $this->request->param("s", '');
        $map = [];
        if ($s == 'finish') {
            $map['status'] = 2;
            $left = 'finish';
        } else {
            $map['status'] = ['neq', 2];
            $left = 'wait';
        }
        $list = Db::name('work_order')->where($map)->paginate(15, false, ['query' => ['keyword' => $keyword]]);
        $pages = $list->render();
        $this->assign('list', $list);
        $this->assign('pages', $pages);
        $this->assign('left', $left);
        return $this->fetch();
    }

    /**
     * 编辑工单
     * @return [type] [description]
     */
    public function edit()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = Db::name('work_order')->where('id', $id)->find();
        if($data){
            $categoryList = Db::name('work_order_category')->where(['id'=>$data['category_id']])->select();
            $subCategoryList = Db::name('work_order_category')->where(['parent_id'=>$data['category_id']])->select();
            $this->assign('categoryList', $categoryList);
            $this->assign('subCategoryList', $subCategoryList);
        }
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * 查看工单
     * @return [type] [description]
     */
    public function view()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = Db::name('work_order')->where('id', $id)->find();
        if ($data) {
            $website = Db::name('yun_website')->where(['id' => $data['website_id']])->find();
            $data['website_name'] = $website['name'];
            $user = Db::name('user_list')->where(['id' => $data['user_id']])->find();
            $data['nickname'] = $user['nickname'];
            $category = Db::name('work_order_category')->where(['id' => $data['category_id']])->find();
            $pcategory = Db::name('work_order_category')->where(['id' => $category['parent_id']])->find();
            $data['category'] = $pcategory['title'] . '  ' . $category['title'];
            if ($data['status'] == 2) {
                $left = 'finish';
            } else {
                $left = 'wait';
            }
            $this->assign('left', $left);
        }
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * 保存工单
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
                $result = Db::name('work_order')->insert($data);
            } else {
                $id = $data['id'];
                unset($data['id']);
                $result = Db::name('work_order')->where('id', $id)->update($data);
            }
            if ($result !== false) {
                return $this->success('保存成功', url('/admin/work/index'));
            }
            return $this->error('保存失败');
        }
        $this->redirect(url('/admin/work/index'));
    }

    /**
     * 删除工单
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
        $result = Db::name('work_order')->where($map)->delete();
        if ($result) {
            return json(true);
        }
        return json(false);
    }

    public function changeStatus()
    {
        $param = $this->request->param();
        if (!$param['id']) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        if ($param['status'] == 2) {
            $update['finish_time'] = time();
        }
        if ($param['status'] == 0) {
            $update['finish_time'] = '';
        }
        $update['status'] = $param['status'];
        $result = Db::name('work_order')->where(['id' => $param['id']])->update($update);
        if ($result !== false) {
            return json(['code' => 1]);
        }
        return json(['code' => 0, 'msg' => '操作失败']);
    }

    public function moreInfo(){
        $param = $this->request->param();
        $id = $param['id'];
        $data = Db::name('work_order')->where('id', $id)->find();
        $this->assign('data', $data);
        return $this->fetch();
    }
}