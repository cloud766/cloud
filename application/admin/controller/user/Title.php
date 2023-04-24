<?php
namespace app\admin\controller\user;
use app\common\controller\Adminbase;
use app\common\model\user\UserTitle as UTModel;
/**
*会员头衔列表
*/
class Title extends Adminbase{
    /**
    *首页
    */
	public function _initialize(){
        parent::_initialize();
    }

    /**
     * 会员头衔列表
     * @return [type] [description]
     */
    public function index(){    
        $keyword = $this->request->get("keyword", '');
        $map = [];
        if ($keyword) {
            $map['name'] = ['like', '%'.$keyword.'%'];
        }
        $list = UTModel::where($map)->paginate();
        $pages = $list->render();
        $this->assign('list', $list);
        $this->assign('pages', $pages);
        $this->assign('keyword', $keyword);
        return $this->fetch();
    }

    /**
     * 添加会员头衔
     * @return [type] [description]
     */
    public function add(){
        return $this->fetch();
    }

    /**
     * 编辑会员头衔
     * @return [type] [description]
     */
    public function edit(){
        $param = $this->request->param();
        $id = $param['id'];
        $user = UTModel::where('id',$id)->find();
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
                $result = UTModel::create($data);
            }else{
                $id = $data['id'];
                unset($data['id']);
                //存入时间戳
                $data['update_time'] = time();
                $result = UTModel::update($data, ['id'=>$id]);
            }
            if ($result !== false) {
                return $this->success('保存成功',url('admin/user.user_title/index'));
            }
            return $this->error('保存失败');
        }
        $this->redirect(url('admin/user.user_title/index'));
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
        $result = UTModel::deleteData($map);
        if ($result) {
            return json(true);
        }
        return json(false);
    }
}