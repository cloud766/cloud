<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use app\common\model\Config as CModel;
/**
*系统配置
*/
class Config extends Adminbase{
    public function _initialize(){
        parent::_initialize();
    }
    /**
    *首页
    */    
       public function index(){
        $param = $this->request->param();
        $group = $param['group'];
        $config = get_config(['group'=>$group]);
        $this->assign('config', $config);
        $this->assign('group', $group);
        return $this->fetch($group);
    }

    public function testIndex(){
        $param = $this->request->param();
        $group = $param['group'];
   
        $map['group'] = $group;    
        $map['type'] = ['neq', 'parent'];
        $list = CModel::where($map)->order('sort asc')->select();

        $parent = CModel::where(array('id'=>$list[0]['pid']))->find();   
        $title = $parent['pname'];
        $navList = CModel::where(['pname'=>$parent['pname']])->group('group')->select();

        foreach ($list as $key => $value){                 
            if ($value['type'] == 'select' || $value['type'] == 'selectm'){          
                $list[$key]['options'] = parse_attr($value['content']);
            }
            $list[$key]['hide'] = 0;
        }
        foreach ($list as $key => $value) {
            if ($value['hide_relation'] == 1 && $value['value'] == '0') {
                foreach ($list as $k => $v) {
                    if ($v['hide_relation_id'] == $value['id']) {
                        $list[$k]['hide'] = 1;
                    }
                }
            }
        }
        $flag = false;
        if (count($navList)>1) {
            $flag = true;
        }
        $this->assign('title',$title);      
        $this->assign('navList',$navList);      
        $this->assign('flag',$flag);        
        $this->assign('list', $list);
        $this->assign('group', $group);
        return $this->fetch();
    }

    public function saveConfig(){
         if ($this->request->isPost()) {
            $data = $this->request->post();
            if($data['open_verify_code']){
                $data['open_verify_code'] = implode(',',$data['open_verify_code']);
            }
            
            /*p($data);die;*/
            $list = [];
            $group = $data['group'];
            unset($data['group']);
            foreach ($data as $key => $value){
                CModel::update(['value'=>$value], ['name'=>$key, 'group'=>$group]);
            }
            return $this->success('保存成功',url('admin/config/index',array('group'=>$group)));
        }
        $this->redirect(url('admin/index/welcome'));
    }

    public function configList(){
        $map = [];
        $order = 'id desc';
        $group = $this->request->get("group", '');
        if ($group) {
            $map['group'] = $group;
            $order = 'sort asc';
        }
        $list = CModel::where($map)->order($order)->paginate();
        $pages = $list->render();
        $groupList = CModel::where(['type'=>['neq', 'parent']])->group('group')->select();
        $this->assign('groupList', $groupList);
        $this->assign('list', $list);
        $this->assign('pages', $pages);
        $this->assign('group', $group);
        return $this->fetch();
    }

    public function add(){
        $switchList = CModel::where('type', 'switch')->select();
        $groupList = CModel::where(['type'=>['neq', 'parent']])->group('group')->select();
        $this->assign('groupList', $groupList);
        $this->assign('switchList', $switchList);
        return $this->fetch();
    }

    public function edit(){
        $param = $this->request->param();
        $id = $param['id'];
        $config = CModel::where('id',$id)->find();

        $config['map_relation_name'] = '';
        $config['map_relation_title'] = '';
        if ($config['map_relation_id']) {
            $map =  CModel::where('id',$config['map_relation_id'])->find();
            $config['map_relation_name'] = $map['name'];
            $config['map_relation_title'] = $map['title'];
        }

        $switchList = CModel::where('type', 'switch')->select();
        $groupList = CModel::where(['type'=>['neq', 'parent']])->group('group')->select();
        $this->assign('groupList', $groupList);
        $this->assign('data', $config);
        $this->assign('switchList', $switchList);
        return $this->fetch();
    }

    public function save(){
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if ($data['type'] == 'baidumap') {
               $bdm_relaction['name'] = $data['map_relation_name'];
               $bdm_relaction['title'] = $data['map_relation_title'];
               $bdm_relaction['type'] = 'baidumap_relation';
            }
            unset($data['map_relation_name']);
            unset($data['map_relation_title']);
            if ($data['type'] != 'parent') {
                $pid = CModel::where(['type'=>'parent', 'group'=>$data['group']])->column('id');
                $data['pid'] = $pid[0] ? $pid[0] : 0;
            }
            if (!$data['id']) {
                $result = CModel::create($data);
            }else{
                $id = $data['id'];
                unset($data['id']);
                $result = CModel::update($data, ['id'=>$id]);
            }
            if ($result !== false) {
                $bdm_relaction['map_relation_id'] = !$id ? $result->id : $id;
                $map_relation = CModel::where(['map_relation_id' => $bdm_relaction['map_relation_id']])->find();
                if ($map_relation) {
                    CModel::update($bdm_relaction, ['map_relation_id' => $bdm_relaction['map_relation_id']]);        
                }else{
                    if ($data['type'] == 'baidumap') {
                        $res = CModel::create($bdm_relaction);    
                        CModel::update(['map_relation_id'=>$res->id], ['id' => $bdm_relaction['map_relation_id']]);       
                    }
                }
                return $this->success('保存成功');
            }
            return $this->error('保存失败');
        }
        $this->redirect(url('admin/config/list'));
    }

    /**
     * 删除配置
     * @return [type] [description]
     */
    public function delete(){
        $param = $this->request->param();
        $id = $param['id'];
        if (is_null($id)) {
            return json(false);
        }
        if(is_array($id)){
            foreach($id as $v){
                $result = CModel::destroy($v);
            }
        }else{
            $result = CModel::destroy($id);
        }
        if ($result) {
            return json(true);
        }
        return json(false);
    }

}