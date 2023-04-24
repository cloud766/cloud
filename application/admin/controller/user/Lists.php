<?php
namespace app\admin\controller\user;
use app\common\controller\Adminbase;
use app\common\model\user\UserList as ULModel;
/**
*会员列表
*/
class Lists extends Adminbase{
    /**
    *首页
    */
	public function _initialize(){
        parent::_initialize();        
    }

    /**
     * 用户列表
     * @return [type] [description]
     */
    public function index(){    
        $keyword = $this->request->get("keyword", '');
        $map = [];
        if ($keyword) {
            $map['username|nickname'] = ['like', '%'.$keyword.'%'];
        }
        $list = ULModel::where($map)->order('id desc')->paginate();        
        $pages = $list->render();       
        $this->assign('list', $list);
        $this->assign('pages', $pages);
        $this->assign('keyword', $keyword);
        $this->assign('left', 'lists');
        return $this->fetch();
    }
    
    public function ajax(){
    	$param = $this->request->param();
    	$type = $param['type'];
    	$list = db('user_expand')->where(['group'=>$type])->select();
        if($list){
    		return json(['code'=>1, 'list'=>$list]);
    	}else{
    		return json(['code'=>0]);
    	}    	
    }

    /**
     * 编辑用户
     * @return [type] [description]
     */
    public function edit(){
    	$listclass = db('user_level')->select();
    	$listIntegral = db('user_title')->select(); 
        $typelist = db('user_type')->select();     
        $param = $this->request->param();        
        $id = $param['id'];
        $user = ULModel::where('id',$id)->find();
        if (!$user) {
            $user['type'] = 4;
        }
        $expand = json_decode($user['expand'],true);
        $list = db('user_expand')->where(['group'=>$user['type']])->select();
        foreach ($list as $key => $value) {
            $list[$key]['value'] = $expand[$value['name']];
            if ($value['type'] == 'select'){            
                $list[$key]['options'] = explode(',', $value['content']);
            }
        }
        $this->assign('listIntegral',$listIntegral);
        $this->assign('listclass',$listclass);
        $this->assign('typelist',$typelist);
        $this->assign('list', $list);
        $this->assign('user', $user);
        $this->assign('left', 'lists');
    	return $this->fetch();
    }

    /**
     * 保存用户
     * @return [type] [description]
     */
    public function save(){
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $result = $this->validate($data, 'UserLists');
            if ($result !== true) {
                $token = $this->request->token('__token__', 'sha1');
                $this->error($result, '', $token);
            }
            // foreach ($data as $key => $value) {
            //     if (strpos($key,'expand_') !== false) {
            //         $name = str_replace('expand_', '', $key);
            //         $expand[$name] = $value;
            //         unset($data[$key]);
            //     }
            // }
            // $data['expand'] = json_encode($expand);
            if (!$data['id']) {
                //存入时间戳
                $data['create_time'] = time();
                $data['password']=md5($data['password']);
                $result = ULModel::create($data);
            }else{
                $id = $data['id'];
                unset($data['id']);
                //存入时间戳
                $data['update_time'] = time();
                $result = ULModel::update($data, ['id'=>$id]);
            }
            if ($result !== false) {
                return $this->success('保存成功',url('admin/user.lists/index'));
            }
            return $this->error('保存失败');
        }
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