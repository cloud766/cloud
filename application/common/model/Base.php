<?php
namespace app\common\model;

use think\Model;
use app\common\util\Data;

class Base extends Model
{
	
	/**
	 * 返回单个数据
	 * @param  [type] $map [description]
	 * @return [type]      [description]
	 */
	public function getDataByMap($map=[],$field=true,$order='id desc'){
		$data = self::where($map)->field($field)->order($order)->find();
		if ($data) {
			return $data->toArray();
		}else{
			return false;
		}
	}

	/**
	 * 添加数据
	 * @param [type] $data [description]
	 */
	public function addData($data){
		//带id的添加操作，判断主键是否已存在
		if ($data['id'] && self::where(['id'=>$data['id']])->find()) {
			return false;
		}
		if (!$data['id']) {
			unset($data['id']);
		}
		$data['create_time'] = $data['create_time'] ? $data['create_time'] : time();
		$data['update_time'] = $data['update_time'] ? $data['update_time'] : time();
		$result = self::create($data);
		if ($result) {
			//返回id
			return $result->id;
		}
		return $result;
	}

	/**
	 * 编辑数据
	 * @param  array  $data [description]
	 * @param  array  $map  [description]
	 * @return [type]       [description]
	 */
	public function editData($data=[], $map=[]){
		if (!$map) {
           	return false;
		}
		//禁止修改id，防止自增主键异常，导致更新操作抛出异常
		unset($data['id']);
		$data['update_time'] = time();
       	$result = self::where($map)->update($data);
       	if ($result !== false) {
       		return true;
       	}
        return false;
    }

    /**
     * 删除数据
     * @param  [type] $id [description]
     * @param  [type] $del [description]
     * @return [type]     [description]
     */
    public function deleteData($map=[], $del=true){
    	//条件为空的危险操作
        if (!$map) {
            return false;
        }
        if ($del === true || is_null($del)) {
        	//真删除
        	$result = self::where($map)->delete();
        }else{
        	//软删除
        	$result = self::where($map)->setField('del_flag', 1);
        }
       	return $result;
    }

    /**
     * 返回列表数据
     * @param  array   $map   [description]
     * @param  integer $limit [description]
     * @param  boolean $field [description]
     * @param  string  $order [description]
     * @return [type]         [description]
     */
	public function getListByMap($map=[], $limit=0, $field=true, $order='id desc'){
        $object_list = self::where($map)->limit($limit)->field($field)->order($order)->select();
        if ($object_list) {
	        $list=[];
			foreach ($object_list as $key => $value) {
				array_push($list, $value->toArray());
			}
        }else{
        	$list = false;
        }
		return $list;
	}

	/**
	 * 获取分页数据
	 * @param  array   $map      [description]
	 * @param  integer $pageSize [description]
	 * @param  boolean $field    [description]
	 * @param  string  $order    [description]
	 * @param  string  $map_time 时间区间搜索参数
	 * @return [type]            [description]
	 */
	public function getPageByMap($map=[], $map_time=[], $pageSize=15, $field=true, $order='id desc'){
		$option['list_rows'] = $pageSize;
		if(!empty($map_time)){
			//时间区间搜索
			$paginate = self::where($map)->whereTime('create_time', 'between', [$map_time['start'],$map_time['end']])->order($order)->field($field)->paginate($option);
		}else{
			//普通搜索
			$paginate = self::where($map)->order($order)->field($field)->paginate($option);  
		}
		//渲染分页列表
        $pages = $paginate->render();
        //记录总数
        $total = $paginate->total();
        //可视化列表
        if ($paginate) {
        	$list=[];
			foreach ($paginate as $key => $value) {
				array_push($list, $value->toArray());
			}
        }
        return ['list'=>$list, 'page'=>['pages'=>$pages, 'total'=>$total]];  
	}

	/**
	 * 返回树形结构数据
	 * @param  array  $list   [description]
	 * @param  string $name   [description]
	 * @param  string $child  [description]
	 * @param  string $parent [description]
	 * @return [type]         [description]
	 */
	public function getTreeData($list=[], $name='name', $child='id', $parent='pid'){
		$tree = Data::tree($list, $name, $child, $parent);
		return $tree;
	}


    /**
    * 递归获取所有父节点
    */
    public function getAllParent($pid,$arr=[]){
        $category = self::where(array('id'=>$pid))->find()->toArray();
        if($category['parent_id'] == 0){
        	array_push($arr, $category);
            return $arr;
        }else{
            array_push($arr, $category);
            return self::getAllParent($category['parent_id'],$arr);
        }
    }

    /**
    * 递归获取所有子节点(不可用)
    */
    public function getAllChildren($id,$arr=array()){
        array_push($arr, $id);
        $children = self::where(array('pid'=>$id))->select();
        if(count($children) == 0){
            return $arr;
        }else{
            foreach ($children as $key => $value) {
                $arr = self::getAllChildren($value['id'], $arr);
            }
            return $arr;
        }
	}
	
	/**
	 * 返回单个数据
	 * @param  [type] $param [description]
	 * @return [type]      [description]
	 */
	public function getDataByParam($param=[]){
		$map = $param['map'] ? $param['map'] : [];
		$field = $param['field'] ? $param['field'] : true;
		$order = $param['order'] ? $param['order'] : 'id desc';
		$data = self::where($map)->field($field)->order($order)->find();
		if ($data) {
			return $data->toArray();
		}else{
			return false;
		}
	}

	/**
     * 返回列表数据
     * @param  array   $param   [description]
     * @return [type]         [description]
     */
	public function getListByParam($param=[]){
		$map = $param['map'] ? $param['map'] : [];
		$limit = $param['limit'] ? $param['limit'] : 0;
		$field = $param['field'] ? $param['field'] : true;
		$order = $param['order'] ? $param['order'] : 'id desc';
        $array = self::where($map)->limit($limit)->field($field)->order($order)->select();
        if ($array) {
	        $list=[];
			foreach ($array as $key => $value) {
				array_push($list, $value->toArray());
			}
        }else{
        	$list = false;
        }
		return $list;
	}

	/**
	 * 获取分页数据
	 * @param  array   $map      [description]
	 * @param  integer $pageSize [description]
	 * @param  boolean $field    [description]
	 * @param  string  $order    [description]
	 * @param  string  $map_time 时间区间搜索参数
	 * @return [type]            [description]
	 */
	public function getPageByParam($param=[]){
		$map = $param['map'] ? $param['map'] : [];
		$map_time = $param['map_time'] ? $param['map_time'] : [];
		$pageSize = $param['pageSize'] ? $param['pageSize'] : 10;
		$field = $param['field'] ? $param['field'] : true;
		$order = $param['order'] ? $param['order'] : 'id desc';
		$option['list_rows'] = $pageSize;
		if(!empty($map_time)){
			//时间区间搜索
			$paginate = self::where($map)->whereTime('create_time', 'between', [$map_time['start'],$map_time['end']])->order($order)->field($field)->paginate($option);
		}else{
			//普通搜索
			$paginate = self::where($map)->order($order)->field($field)->paginate($option);  
		}
		//渲染分页列表
        $pages = $paginate->render();
        //记录总数
        $total = $paginate->total();
        //可视化列表
        if ($paginate) {
        	$list=[];
			foreach ($paginate as $key => $value) {
				array_push($list, $value->toArray());
			}
        }
        return ['list'=>$list, 'page'=>['pages'=>$pages, 'total'=>$total]];  
	}
    
}
