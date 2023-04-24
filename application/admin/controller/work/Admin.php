<?php
namespace app\admin\controller\work;

use think\Db;
use app\common\controller\Adminbase;
/**
 * 管理员列表
 */
class Admin extends Adminbase{
    public function _initialize(){
        parent::_initialize();      
    }

    /**
     * 管理员列表
     * @return [type] [description]
     */
    public function index(){    
        $keyword = $this->request->get("keyword", '');
        $map = [];
        if ($keyword) {
            $map['username|nickname'] = ['like', '%'.$keyword.'%'];
        }
        $list = db::name('work_admin')->where($map)->paginate(15, false, ['query' => ['keyword' => $keyword]]);        
        $pages = $list->render();       
        $this->assign('list', $list);
        $this->assign('pages', $pages);
        $this->assign('keyword', $keyword);
        return $this->fetch();
    }

    /**
     * 添加用户
     * @return [type] [description]
     */
    public function add(){
        return $this->fetch();
    }

    /**
     * 编辑用户
     * @return [type] [description]
     */
    public function edit(){
        $param = $this->request->param();
        $id = $param['id'];
        $user = db::name('work_admin')->where('id',$id)->find();
        $this->assign('user', $user);
    	return $this->fetch();
    }

    /**
     * 保存用户
     * @return [type] [description]
     */
    public function save(){
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // p($data);die;
            // 验证数据合法性
            if (!$data['id']) {
                //存入时间戳
                $data['create_time'] = time();
                $result = db::name('work_admin')->insert($data);
            }else{
                $id = $data['id'];
                unset($data['id']);
                //存入时间戳
                $data['update_time'] = time();
                $result = db::name('work_admin')->where('id', $id)->update($data);
            }
            if ($result !== false) {
                return $this->success('保存成功',url('/admin/work/admin'));
            }
            return $this->error('保存失败');
        }
        $this->redirect(url('/admin/work/admin'));
    }

    /**
     * 删除用户
     * @return [type] [description]
     */
    public function delete(){
        $param = $this->request->param();
        $id = $param['id'];
        if (is_null($id)) {
            return json(false);
        }
        //循环删除
        if(is_array($id)){
            foreach($id as $v){
                $result = db::name('work_admin')->delete($v);
            }
        }else{
            $result = db::name('work_admin')->delete($id);
        }
        if ($result) {
            return json(true);
        }
        return json(false);
    }

}