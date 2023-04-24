<?php
namespace app\admin\controller\work;

use app\common\controller\Adminbase;
use think\Db;
/**
 * 工单列表
 */
class Work extends Adminbase{
    public function _initialize(){
        parent::_initialize();      
    }

    /**
     * 首页
     * @return [type] [description]
     */
    public function index(){
        //查询关键词    
        $keyword = $this->request->get("keyword", '');
        //查询栏目
        $select = $this->request->get("select", '');
        $map = [];
        if ($keyword) {    
            if($select == 'classify'){
                //栏目搜索
                $select = db::name('work_classify')->where('name',$keyword)->value('id');
                $map['config_classify'] = ['like', '%'.$select.'%'];
            }elseif($select == 'keyword'){
                //关键词搜索
                $map['config_status|config_theme'] = ['like', '%'.$keyword.'%'];
            }
        }
        $list = db::name('work')->where($map)->paginate(15, false, ['query' => ['keyword' => $keyword]]);   
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
        //获取分类信息
        $classify = db('work_classify')->field('name')->select();
        $this->assign('classify', $classify);
        return $this->fetch();
    }

    /**
     * 编辑
     * @return [type] [description]
     */
    public function edit(){
        $param = $this->request->param();
        $id = $param['id'];
        $user = db::name('work')->where('id',$id)->find();
        
        //获取分类信息
        $classify = db('work_classify')->field('name')->select();
        //获取自定义字段信息
        $field = db('work_field')->where('pid',$id)->select();
        //格式化时间戳
        $user['config_starttime'] = date('Y-m-d', $user['config_starttime']);
        $user['config_endtime'] = date('Y-m-d', $user['config_endtime']);
        $this->assign('field', $field);
        $this->assign('classify', $classify);
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
            // p($data);die;        
            //删除无用字段
            unset($data['image']);
            unset($data['option']);
            //从主数据提取自定义字段到数组
            $field = array();
            foreach ($data as $k => $v) {             
                if(stripos($k,'ield_')){
                    $field[$k] = $v;
                    //删除原数组数据中的自定义字段键值对
                    unset($data[$k]);
                }
            }
            /*插入数据*/     
            if (!$data['id']) {
                //存入时间戳
                $data['create_time'] = time();
                //转换有效期成时间戳
                $data['config_starttime'] = strtotime($data['config_starttime']);
                $data['config_endtime'] = strtotime($data['config_endtime']);
                //存入数据并返回主键ID
                $pid = db::name('work')->insertGetId($data);

                //获取自定义字段的上传图片和录音设置并销毁
                $plural = $field['field_plural'];
                $ivoice = $field['field_ivoice'];
                unset($field['field_plural'],$field['field_ivoice']);
                //存入自定义字段开始
                    //构建必须存入的字段:姓名 手机号
                $result = array(
                    array(
                        'field_title'=>'姓名',
                        'field_displayorder'=>'顶端',
                        'field_essential'=>'1',
                        'field_type'=>'text',
                        'field_bind'=>'realname',
                        'field_plural'=>$plural,
                        'field_ivoice'=>$ivoice,
                        'create_time'=>time(),
                        'pid'=>$pid
                    ),
                    array(
                        'field_title'=>'手机',
                        'field_displayorder'=>'顶端',
                        'field_essential'=>'1',
                        'field_type'=>'number',
                        'field_bind'=>'mobile',
                        'field_plural'=>$plural,
                        'field_ivoice'=>$ivoice,
                        'create_time'=>time(),
                        'pid'=>$pid
                    )
                );
                foreach($result as $v){
                    $result = db('work_field')->insert($v);
                }               
                    //构建自定义字段数组
                if(!empty($field['field_title'])){
                    $result = array();
                    $temp = array();
                    foreach ($field['field_title'] as $key => $value) {
                        $temp = array(
                            'field_title'=>$value,
                            'field_displayorder'=>$field['field_displayorder'][$key],
                            'field_image'=>$field['field_image'][$key],
                            'field_type'=>$field['field_type'][$key],
                            'field_bind'=>$field['field_bind'][$key],
                            'field_plural'=>$plural,
                            'field_ivoice'=>$ivoice,
                            'field_essential'=>$field['field_essential'][$key],
                            'pid'=>$pid,
                            'create_time'=>time()
                    );
                        array_push($result,$temp);
                    }
                    foreach ($result as $v) { 
                        //自定义字段存入数据库
                        db('work_field')->insert($v);
                    }
                }
            /*更新数据*/                   
            }else{
                $id = $data['id'];
                unset($data['id']);
                //存入时间戳
                $data['update_time'] = time();
                //转换有效期成时间戳
                $data['config_starttime'] = strtotime($data['config_starttime']);
                $data['config_endtime'] = strtotime($data['config_endtime']);
                //存入修改数据
                $result = db::name('work')->where('id', $id)->update($data);

                /*存入自定义字段开始*/
                //删除原数据
                db('work_field')->where('pid',$id)->delete();
                //获取自定义字段的上传图片和录音设置并销毁
                $plural = $field['field_plural'];
                $ivoice = $field['field_ivoice'];
                unset($field['field_plural'],$field['field_ivoice']);

                //构建必须存入的字段:姓名 手机号
                $result = array(
                    array(
                        'field_title'=>'姓名',
                        'field_displayorder'=>'顶端',
                        'field_essential'=>'1',
                        'field_type'=>'text',
                        'field_bind'=>'realname',
                        'field_plural'=>$plural,
                        'field_ivoice'=>$ivoice,
                        'create_time'=>time(),
                        'pid'=>$id
                    ),
                    array(
                        'field_title'=>'手机',
                        'field_displayorder'=>'顶端',
                        'field_essential'=>'1',
                        'field_type'=>'number',
                        'field_bind'=>'mobile',
                        'field_plural'=>$plural,
                        'field_ivoice'=>$ivoice,
                        'create_time'=>time(),
                        'pid'=>$id
                    )
                );
                foreach($result as $v){
                    $result = db('work_field')->insert($v);
                }               
                    //构建自定义字段数组
                if(!empty($field['field_title'])){
                    $result = array();
                    $temp = array();
                    foreach ($field['field_title'] as $key => $value) {
                        $temp = array(
                            'field_title'=>$value,
                            'field_displayorder'=>$field['field_displayorder'][$key],
                            'field_image'=>$field['field_image'][$key],
                            'field_type'=>$field['field_type'][$key],
                            'field_bind'=>$field['field_bind'][$key],
                            'field_plural'=>$plural,
                            'field_ivoice'=>$ivoice,
                            'field_essential'=>$field['field_essential'][$key],
                            'pid'=>$id,
                            'create_time'=>time()
                    );
                        array_push($result,$temp);
                    }
                    foreach ($result as $v) { 
                        //自定义字段存入数据库
                        db('work_field')->insert($v);
                    }
                }
            }
            if ($result !== false) {
                return $this->success('保存成功',url('/admin/work/work'));
            }
            return $this->error('保存失败');
        }
        $this->redirect(url('/admin/work/work'));
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
                db('work_field')->where('pid',$v)->delete();
                $result = db::name('work')->delete($v);
            }
        }else{
            db('work_field')->where('pid',$id)->delete();
            $result = db::name('work')->delete($id);
        }
        if ($result) {
            return json(true);
        }
        return json(false);
    }

}