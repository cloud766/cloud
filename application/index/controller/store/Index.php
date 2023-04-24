<?php
namespace app\index\controller\store;
use app\index\controller\store\Base;
use app\common\model\yun\YunTemplate as YTModel;
class Index extends Base{

    public function _initialize(){
        parent::_initialize();
    }

    /**
     * 首页
     */
    public function index(){
    	$newList = YTModel::getListByMap([], 8);
    	$this->assign('newList', $newList);

        $topList = [];
        if ($newList) {
            foreach ($newList as $key => $value) {
                $topList[$key] = $value;
                $type = db('yun_template_type')->where(['id'=>$value['type']])->find();
                $topList[$key]['type_name'] = $type['name'];
                if ($key == 2) {
                    break;
                }
            }
        }    
        $this->assign('topList', $topList);

    	$saleList = YTModel::getListByMap([], 8,true, 'sale_num desc');
    	$this->assign('saleList', $saleList);
    	$freeList = YTModel::getListByMap(['price'=>0], 8);
    	$this->assign('freeList', $freeList);
        return $this->fetch();
    }
} 