<?php
namespace app\admin\controller\yun;

use app\common\controller\Adminbase;
use app\common\util\Http;
use app\common\util\Dir;
use app\common\model\yun\YunAddon as YAModel;
use app\common\model\yun\YunAddonType as YATModel;
use app\common\model\yun\YunAddonVersion as YAVModel;
use app\common\model\yun\YunAddonWebsite as YAWModel;
use app\common\model\yun\YunAddonDownload as YADModel;
use app\common\model\yun\YunAddonAuth as YAAModel;
use app\common\model\yun\YunAddonBuy as YABModel;
use app\common\model\yun\YunWebsite as YWModel;
use think\Db;
/**
 * 插件管理
 */
class Addon extends Adminbase
{
    /**
     *首页
     */
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 插件类型列表
     * @return [type] [description]
     */
    public function type_list()
    {
        $list = YATModel::order('id desc')->paginate();
        $pages = $list->render();
        $this->assign('pages', $pages);
        $this->assign('list', $list);
        $this->assign('left', 'type_list');
        return $this->fetch();
    }

    /**
     * 编辑插件类型
     * @return [type] [description]
     */
    public function type_edit()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = YATModel::where('id', $id)->find();
        $this->assign('data', $data);
        $this->assign('left', 'type_list');
        return $this->fetch();
    }

    /**
     * 保存插件类型
     * @return [type] [description]
     */
    public function type_save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $result = $this->validate($data, 'YunAddonType');
            if ($result !== true) {
                $token = $this->request->token('__token__', 'sha1');
                $this->error($result, '', $token);
            }
            if (!$data['id']) {
                //存入时间戳
                $data['create_time'] = time();
                $data['update_time'] = time();
                $result = YATModel::create($data);
            } else {
                $id = $data['id'];
                unset($data['id']);
                //存入时间戳
                $data['update_time'] = time();
                $result = YATModel::update($data, ['id' => $id]);
            }
            if ($result !== false) {
                return $this->success('保存成功', url('admin/yun.addon/type_list'));
            }
            return $this->error('保存失败');
        }
    }

    /**
     * 删除插件类型
     * @return [type] [description]
     */
    public function type_delete()
    {
        $param = $this->request->param();
        $id = $param['id'];
        if (!isset($id)) {
            return json(false);
        }
        if (is_array($id)) {
            $map['id'] = ['in', $id];
        } else {
            $map['id'] = $id;
        }
        $result = YATModel::deleteData($map);
        if ($result) {
            return json(true);
        }
        return json(false);
    }

    /**
     * 插件列表
     * @return [type] [description]
     */
    public function addon_list()
    {
        $list = Db::name('yun_addon')->alias('ya')
            ->join('yun_addon_type yat', 'yat.id = ya.type', 'LEFT')
            ->field('ya.*, yat.name as type_name')
            ->order('id desc')
            ->paginate();
        $pages = $list->render();
        $this->assign('pages', $pages);
        $this->assign('list', $list);
        $this->assign('left', 'addon_list');
        return $this->fetch();
    }

    /**
     * 编辑插件
     * @return [type] [description]
     */
    public function addon_edit()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = Db::name('yun_addon')->where('id', $id)->find();
        if($data){
            $data['imageList'] = explode(',', $data['pic_f']);
        }
        $type_list = Db::name('yun_addon_type')->select();
        $this->assign('data', $data);
        $this->assign('type_list', $type_list);
        $this->assign('left', 'addon_list');
        return $this->fetch();
    }

    /**
     * 保存插件
     * @return [type] [description]
     */
    public function addon_save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $result = $this->validate($data, 'YunAddon');
            if ($result !== true) {
                $token = $this->request->token('__token__', 'sha1');
                $this->error($result, '', $token);
            }
            if (!$data['id']) {
                //存入时间戳
                $data['create_time'] = time();
                $data['update_time'] = time();
                $result = Db::name('yun_addon')->insert($data);
            } else {
                $id = $data['id'];
                unset($data['id']);
                //存入时间戳
                $data['update_time'] = time();
                $result = Db::name('yun_addon')->where(['id' => $id])->update($data);
            }
            if ($result !== false) {
                if($data['path']){
                    $addonPath = ROOT_PATH . 'data' . DS . 'addon' . DS . $data['name'] . DS;
                    if (!is_dir($addonPath)) {
                        Dir::create($addonPath);
                    }
                    rename(ROOT_PATH . $data['path'], $addonPath . $data['file_name']);
                }
                return $this->success('保存成功', url('admin/yun.addon/addon_list'));
            }
            return $this->error('保存失败');
        }
    }

    /**
     * 删除插件
     * @return [type] [description]
     */
    public function addon_delete()
    {
        $param = $this->request->param();
        $id = $param['id'];
        if (!isset($id)) {
            return json(false);
        }
        if (is_array($id)) {
            $map['id'] = ['in', $id];
            $vmap['addon_id'] = ['in', $id];
        } else {
            $map['id'] = $id;
            $vmap['addon_id'] = $id;
        }
        $addon = Db::name('yun_addon')->where($map)->find();
        $result = Db::name('yun_addon')->where($map)->delete();
        if ($result) {
            Dir::delDir(ROOT_PATH . 'data' . DS . 'addon' . DS . $addon['name']);
            Db::name('yun_addon_version')->where($vmap)->delete();
            return json(true);
        }
        return json(false);
    }

    /**
     * 插件版本列表
     * @return [type] [description]
     */
    public function version_list()
    {
        $addon_id = $this->request->param('id');
        if (!$addon_id) {
            $this->redirect(url('admin/yun.addon/addon_list'));
        }
        $list = Db::name('yun_addon_version')->where(['addon_id' => $addon_id])->order('id desc')->paginate();
        $pages = $list->render();
        $this->assign('list', $list);
        $this->assign('addon_id', $addon_id);
        $this->assign('pages', $pages);
        $this->assign('left', 'addon_list');
        return $this->fetch();
    }

    /**
     * 编辑插件版本
     * @return [type] [description]
     */
    public function version_edit()
    {
        $param = $this->request->param();
        $id = $param['id'];
        if ($id) {
            $data = YAVModel::where('id', $id)->find();
        } else {
            $data['addon_id'] = $param['addon_id'];
        }
        $this->assign('data', $data);
        $this->assign('left', 'addon_list');
        return $this->fetch();
    }

    /**
     * 保存插件版本
     * @return [type] [description]
     */
    public function version_save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $result = $this->validate($data, 'YunAddonVersion');
            if ($result !== true) {
                $token = $this->request->token('__token__', 'sha1');
                $this->error($result, '', $token);
            }
            $addon = Db::name('yun_addon')->where(['id' => $data['addon_id']])->find();
            if ($addon['version'] >= $data['version']) {
                $token = $this->request->token('__token__', 'sha1');
                $this->error('保存更新包时版本号必须大于初始版本', '', $token);
            }
            //添加更新包时，判断更新包版本号是否为最大
            if (!$data['id']) {
                $addonVersion = Db::name('yun_addon_version')->where(['addon_id' => $data['addon_id']])->order('version desc')->find();
                if ($addonVersion && $addonVersion['version'] >= $data['version']) {
                    $token = $this->request->token('__token__', 'sha1');
                    $this->error('新增更新包时版本必须为最大', '', $token);
                }
            }else{
                $addonVersion = Db::name('yun_addon_version')->where(['version' => $data['version'], 'addon_id' => $data['addon_id'],'id'=>['neq', $data['id']]])->find();
                if ($addonVersion) {
                    $token = $this->request->token('__token__', 'sha1');
                    $this->error('版本号不可重复', '', $token);
                }
            }
            if (!$data['id']) {
                //存入时间戳
                $result = YAVModel::addData($data);
            } else {
                $id = $data['id'];
                unset($data['id']);
                //存入时间戳
                $data['update_time'] = time();
                $result = YAVModel::editData($data, ['id' => $id]);
            }
            if ($result !== false) {
                if ($data['path']) {
                    //移动文件
                    $path = ROOT_PATH . 'data' . DS . 'addon' . DS . $addon['name'] . DS . $data['version'] . DS;
                    if (!is_dir($path)) {
                        Dir::create($path);
                    }
                    rename(ROOT_PATH . $data['path'], $path . $data['file_name']);
                }
                return $this->success('保存成功', url('admin/yun.addon/version_list', array('id' => $data['addon_id'])));
            }
            return $this->error('保存失败');
        }
    }

    /**
     * 删除插件版本
     * @return [type] [description]
     */
    public function version_delete()
    {
        $param = $this->request->param();
        $id = $param['id'];
        if (!isset($id)) {
            return json(false);
        }
        if (is_array($id)) {
            $map['id'] = ['in', $id];
        } else {
            $map['id'] = $id;
        }
        $result = YAVModel::deleteData($map);
        if ($result) {
            return json(true);
        }
        return json(false);
    }

    public function website_list()
    {
        $list = YAWModel::alias('yscw')
            ->join('hx_yun_addon_version yav', 'yscw.version_id=yav.id', 'LEFT')
            ->join('hx_yun_addon_type yat', 'yat.id=yav.type', 'LEFT')
            ->field('yscw.*, yat.name as type_name, yav.name as version_name, yav.version')
            ->paginate();
        $pages = $list->render();
        $this->assign('list', $list);
        $this->assign('pages', $pages);
        return $this->fetch();
    }

    /**
     * 编辑站点
     * @return [type] [description]
     */
    public function website_edit()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = YAWModel::where('id', $id)->find();
        $version_list = YAVModel::select();
        $this->assign('version_list', $version_list);
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * 保存站点
     * @return [type] [description]
     */
    public function website_save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (!$data['id']) {
                $data['create_time'] = time();
                $result = YAWModel::create($data);
            } else {
                $id = $data['id'];
                unset($data['id']);
                //存入时间戳
                $data['update_time'] = time();
                $result = YAWModel::update($data, ['id' => $id]);
            }
            if ($result !== false) {
                return $this->success('保存成功');
            }
            return $this->error('保存失败');
        }
        $this->redirect(url('admin/yun.addon/version_list'));
    }

    /**
     * 删除站点
     * @return [type] [description]
     */
    public function website_delete()
    {
        $param = $this->request->param();
        $id = $param['id'];
        if (!isset($id)) {
            return json(false);
        }
        if (is_array($id)) {
            $map['id'] = ['in', $id];
        } else {
            $map['id'] = $id;
        }
        $result = YAWModel::deleteData($map);
        if ($result) {
            return json(true);
        }
        return json(false);
    }

    public function download_list()
    {
        $list = YADModel::alias('yad')
            ->join('hx_yun_addon ya', 'yad.addon_id=ya.id', 'LEFT')
            ->join('hx_yun_addon_website yaw', 'yad.website_id=yaw.id', 'LEFT')
            ->field('yad.*, ya.title as version_name, ya.version,yaw.name as website_name')
            ->order('yad.id desc')
            ->paginate();
        $pages = $list->render();
        $this->assign('list', $list);
        $this->assign('pages', $pages);
        $this->assign('left', 'download_list');
        return $this->fetch();
    }

    public function auth_list()
    {
        $list = YAAModel::alias('yaa')
            ->join('hx_yun_website yw', 'yw.id=yaa.website_id')
            ->join('hx_yun_addon ya', 'ya.id=yaa.addon_id')
            ->field('yaa.id,yaa.create_time,yw.name as website,yw.domain,yw.ip,ya.title as addon,ya.author,ya.price')
            ->order('yaa.id desc')
            ->paginate();
        $pages = $list->render();
        $this->assign('list', $list);
        $this->assign('pages', $pages);
        $this->assign('left', 'auth_list');
        return $this->fetch();
    }

    public function auth_edit()
    {
        $this->assign('left', 'auth_edit');
        return $this->fetch();
    }

    public function auth_ajax()
    {
        $param = $this->request->param();
        if (!$param['keyword'] || !$param['type']) {
            return json(['code' => 0]);
        }
        if ($param['type'] == 'addon') {
            $list = Db::name('yun_addon')->where(['title' => ['like', '%' . $param['keyword'] . '%']])->select();
        } else {
            $list = YWModel::getListByMap(['name' => ['like', '%' . $param['keyword'] . '%']]);
        }
        if ($list) {
            return json(['code' => 1, 'list' => $list]);
        }
        return json(['code' => 0]);
    }

    public function auth_save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $result = $this->validate($data, 'YunAddonAuth');
            if ($result !== true) {
                $token = $this->request->token('__token__', 'sha1');
                $this->error($result, '', $token);
            }
            $auth = YAAModel::getDataByMap(['website_id' => $data['website_id'], 'addon_id' => $data['addon_id']]);
            if ($auth) {
                $token = $this->request->token('__token__', 'sha1');
                $this->error('请勿重复授权', '', $token);
            }
            $result = YAAModel::addData($data);
            if ($result) {
                return $this->success('保存成功');
            }
            return $this->error('保存失败');
        }
        $this->redirect(url('admin/yun.addon/auth_list'));
    }

    public function auth_delete()
    {
        $param = $this->request->param();
        $id = $param['id'];
        if (!isset($id)) {
            return json(false);
        }
        if (is_array($id)) {
            $map['id'] = ['in', $id];
        } else {
            $map['id'] = $id;
        }
        $result = YAAModel::deleteData($map);
        if ($result) {
            return json(true);
        }
        return json(false);
    }

    /**
     * 购买列表
     */
    public function buy_list()
    {
        $list = YABModel::alias('yab')
            ->join('hx_yun_addon ya', 'yab.addon_id = ya.id')
            ->join('hx_user_list ul', 'yab.user_id = ul.id')
            ->field('yab.id,yab.buy_time as time,yab.buy_price as price,ya.title as addon_name,ul.username as user_name,ul.nickname as nick_name')
            ->order('yab.id desc')
            ->select();
        $this->assign('list', $list);
        $this->assign('left', 'buy_list');
        return $this->fetch();
    }

    /**
     * 插件更新页面
     * @return [type] [description]
     */
    public function addon_update()
    {
        //发送http请求，获取云平台插件数量
        $respon = Http::post('https://demo.dayongjiaoyu.cn/api/upgrade/getAddonCount');
        $respon = json_decode($respon);
        $this->assign('count', $respon->count);
        return $this->fetch();
    }

    public function upload()
    {
        $file = request()->file('file');
        $info = $file->getInfo();
        $name = $info['name'];
        if ($file) {
            $path = RUNTIME_PATH . 'addon';
            $save = $file->move($path);
            if ($save) {
                $res = array(
                    'cod' => 1,
                    'msg' => '上传成功',
                    'filename' => $name,
                    'path' => 'runtime' . DS . 'addon' . DS . $save->getSaveName(),
                );
            } else {
            // 上传失败获取错误信息
                $res = array(
                    'cod' => 2,
                    'msg' => '上传失败' . ':' . $file->getError()
                );
            }
        } else {
            $res = array(
                'cod' => 3,
                'msg' => '上传失败' . '没有文件'
            );
        }

        return json($res);
    }

    public function auditing_list()
    {
        $list = Db::name('yun_addon')->alias('ya')
            ->join('hx_yun_addon_type yat', 'yat.id=ya.type')
            ->field('ya.*, yat.name as type_name')
            ->where(['ya.status' => ['neq', 1]])
            ->order('ya.id desc')
            ->paginate();
        $pages = $list->render();
        $this->assign('pages', $pages);
        $this->assign('list', $list);
        $this->assign('left', 'auditing_list');
        return $this->fetch();
    }

    public function ajaxStatus()
    {
        $param = $this->request->param();
        $result = Db::name('yun_addon')->where(['id' => $param['id']])->setField('status', $param['status']);
        if ($result !== false) {
            return json(true);
        }
        return json(false);
    }
}