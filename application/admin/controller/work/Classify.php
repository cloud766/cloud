<?php
namespace app\admin\controller\work;

use think\Db;
use app\common\controller\Adminbase;
/**
 * 分类管理列表
 */
class Classify extends Adminbase{
    public function _initialize(){
        parent::_initialize();      
    }

    /**
     *首页
     * @return [type] [description]
     */
    public function index(){    
        $keyword = $this->request->get("keyword", '');
        $map = [];
        if ($keyword) {
            $map['name'] = ['like', '%'.$keyword.'%'];
        }
        $list = db::name('work_classify')->where($map)->paginate(15, false, ['query' => ['keyword' => $keyword]]);        
        $pages = $list->render();       
        $this->assign('list', $list);
        $this->assign('pages', $pages);
        $this->assign('keyword', $keyword);
        return $this->fetch();
    }

    /**
     * 添加
     * @return [type] [description]
     */
    public function add(){
        return $this->fetch();
    }

    /**
     * 编辑
     * @return [type] [description]
     */
    public function edit(){
        $param = $this->request->param();
        $id = $param['id'];
        $user = db::name('work_classify')->where('id',$id)->find();
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
            //删除多余字段
            unset($data['image']);
            // p($data);die;
            // 验证数据合法性
            if (!$data['id']) {
                //存入时间戳
                $data['create_time'] = time();
                $result = db::name('work_classify')->insert($data);
            }else{
                $id = $data['id'];
                unset($data['id']);
                //存入时间戳
                $data['update_time'] = time();
                $result = db::name('work_classify')->where('id', $id)->update($data);
            }
            if ($result !== false) {
                return $this->success('保存成功',url('/admin/work/classify'));
            }
            return $this->error('保存失败');
        }
        $this->redirect(url('/admin/work/classify'));
    }

    /**
     * 删除
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
                $result = db::name('work_classify')->delete($v);
            }
        }else{
            $result = db::name('work_classify')->delete($id);
        }
        if ($result) {
            return json(true);
        }
        return json(false);
    }

}