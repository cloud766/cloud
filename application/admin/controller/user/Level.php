<?php
namespace app\admin\controller\user;
use app\common\controller\Adminbase;
use app\common\model\user\UserLevel as ULModel;
/**
*会员等级列表
*/
class Level extends Adminbase{
    
	public function _initialize(){
        parent::_initialize();
    }
    /**
     * 会员等级列表
     * @return [type] [description]
     */
    public function index(){    
        $keyword = $this->request->get("keyword", '');
        $map = [];
        if ($keyword) {
            $map['name'] = ['like', '%'.$keyword.'%'];
        }
        $list = ULModel::where($map)->paginate();
        $pages = $list->render();
        $this->assign('list', $list);
        $this->assign('pages', $pages);
        $this->assign('keyword', $keyword);
        return $this->fetch();
    }

    /**
     * 添加等级
     * @return [type] [description]
     */
    public function add(){
        return $this->fetch();
    }

    /**
     * 编辑等级
     * @return [type] [description]
     */
    public function edit(){
        $param = $this->request->param();
        $id = $param['id'];
        $user = ULModel::where('id',$id)->find();
        $this->assign('user', $user);
    	return $this->fetch();
    }

    /**
     * 保存
     * @return [type] [description]
     */
    public function save(){
        if ($this->request->isPost()) {
            $data = $this->request->post();
            //空数据则退出
            if(empty($data['name'])){
                return $this->error('保存失败');  
            }
            if (!$data['id']) {
                //存入时间戳
                $data['create_time'] = time();
                $result = ULModel::create($data);
            }else{
                $id = $data['id'];
                unset($data['id']);
                //存入时间戳
                $data['update_time'] = time();
                $result = ULModel::update($data, ['id'=>$id]);
            }
            if ($result !== false) {
                return $this->success('保存成功',url('admin/user.user_level/index'));
            }
            return $this->error('保存失败');
        }
        $this->redirect(url('admin/user.user_level/index'));
    }

    /**
     * 删除用户
     * @return [type] [description]
     */
    public function delete(){
        $param = $this->request->param();     
        $id = $param['id'];
        if (!isset($id)) {
            return json(false);
        }
        if(is_array($id)){
            $map['id'] = ['in', $id];
        }else{
            $map['id'] = $id;
        }
        $result = ULModel::deleteData($map);
        if ($result) {
            return json(true);
        }
        return json(false);
    }
}