<?php
namespace app\index\controller\store;
use app\common\controller\HomeBase;
use app\common\model\yun\YunTemplateType as YTTModel;
use app\common\model\yun\YunAddonType as YATModel;
class Base extends HomeBase{

	public function _initialize(){
		parent::_initialize();
		$navTemplate = YTTModel::getListByMap();
		$this->assign('navTemplate', $navTemplate);
		$navAddon = YATModel::getListByMap();
		$this->assign('navAddon', $navAddon);
		$user = session('user');
		$this->assign('user', $user);
    }

} 