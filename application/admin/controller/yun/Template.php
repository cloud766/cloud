<?php
namespace app\admin\controller\yun;

use app\common\controller\Adminbase;
use app\common\model\yun\YunTemplate as YTModel;
use app\common\model\yun\YunTemplateType as YTTModel;
use app\common\model\yun\YunTemplateDownload as YTDModel;
use app\common\model\yun\YunTemplateAuth as YTAModel;
use app\common\model\yun\YunTemplateBuy as YTBModel;
use app\common\model\yun\YunWebsite as YWModel;
use app\common\model\Gallery as GModel;
use think\Db;
use think\Config;
use app\common\util\Dir;

/**
 * 模板管理
 */
class Template extends Adminbase
{
    /**
     *首页
     */
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 模板列表
     * @return [type] [description]
     */
    public function list()
    {
        $list = YTModel::alias('yt')
            ->join('__YUN_TEMPLATE_TYPE__ ytt', 'ytt.id=yt.type', 'LEFT')
            ->field('yt.*, ytt.name as type_name')
            ->order('yt.id desc')
            ->paginate();
        $pages = $list->render();
        $this->assign('pages', $pages);
        $this->assign('list', $list);
        $this->assign('left', 'list');
        return $this->fetch();
    }

    /**
     * 编辑模板
     * @return [type] [description]
     */
    public function edit()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = YTModel::where('id', $id)->find();
        if ($data) {
            $data['imageList'] = $data['pic_f'] ? explode(',', $data['pic_f']) : [];
        }
        $typeList = YTTModel::select();
        $this->assign('typeList', $typeList);
        $this->assign('data', $data);
        $this->assign('left', 'list');
        return $this->fetch();
    }

    /**
     * 保存模板
     * @return [type] [description]
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $result = $this->validate($data, 'YunTemplate');
            if ($result !== true) {
                $token = $this->request->token('__token__', 'sha1');
                $this->error($result, '', $token);
            }
            if ($data['name'] . '.zip' != $data['file_name']) {
                $token = $this->request->token('__token__', 'sha1');
                $this->error('名称必须与文件名对应', '', $token);
            }
            $template = Db::name('yun_template')->where(['name' => $param['name']])->find();
            if (!$data['id']) {
                if ($template) {
                    $token = $this->request->token('__token__', 'sha1');
                    $this->error('模板名称重复', '', $token);
                }
                $data['create_time'] = time();
                $data['update_time'] = time();
                $result = YTModel::create($data);
            } else {
                if ($template && $template['id'] != $data['id']) {
                    $token = $this->request->token('__token__', 'sha1');
                    $this->error('模板名称重复1', '', $token);
                }
                $id = $data['id'];
                unset($data['id']);
                //存入时间戳
                $data['update_time'] = time();
                $result = YTModel::update($data, ['id' => $id]);
            }
            if ($result !== false) {
                if ($data['path']) {
                    //移动文件
                    $path = ROOT_PATH . 'data' . DS . 'template' . DS;
                    if (!is_dir($path)) {
                        Dir::create($path);
                    }
                    rename(ROOT_PATH . $data['path'], $path . $data['file_name']);
                }
                return $this->success('保存成功', url('admin/yun.template/list'));
            }
            return $this->error('保存失败');
        }
    }

    /**
     * 删除模板
     * @return [type] [description]
     */
    public function delete()
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
        $result = YTModel::deleteData($map);
        if ($result) {
            return json(true);
        }
        return json(false);
    }

    /**
     * 模板类型列表
     * @return [type] [description]
     */
    public function type_list()
    {
        $list = YTTModel::order('id desc')->paginate();
        $pages = $list->render();
        $this->assign('pages', $pages);
        $this->assign('list', $list);
        $this->assign('left', 'type_list');
        return $this->fetch();
    }

    /**
     * 编辑模板类型
     * @return [type] [description]
     */
    public function type_edit()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = YTTModel::where('id', $id)->find();
        $this->assign('data', $data);
        $this->assign('left', 'type_list');
        return $this->fetch();
    }

    /**
     * 保存模板类型
     * @return [type] [description]
     */
    public function type_save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $result = $this->validate($data, 'YunTemplateType');
            if ($result !== true) {
                $token = $this->request->token('__token__', 'sha1');
                $this->error($result, '', $token);
            }
            if (!$data['id']) {
                //存入时间戳
                $data['create_time'] = time();
                $result = YTTModel::create($data);
            } else {
                $id = $data['id'];
                unset($data['id']);
                //存入时间戳
                $data['update_time'] = time();
                $result = YTTModel::update($data, ['id' => $id]);
            }
            if ($result !== false) {
                return $this->success('保存成功', url('admin/yun.template/type_list'));
            }
            return $this->error('保存失败');
        }
    }

    /**
     * 删除模板类型
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
        $result = YTTModel::deleteData($map);
        if ($result) {
            return json(true);
        }
        return json(false);
    }

    /**
     * 下载列表
     * @return [type] [description]
     */
    public function download_list()
    {
        $list = YTDModel::alias('ytd')
            ->join('hx_yun_website yw', 'ytd.website_id=yw.id')
            ->join('hx_yun_template yt', 'ytd.template_id=yt.id')
            ->field('ytd.*, yw.name as website_name, yt.title as template_name')
            ->order('ytd.id desc')
            ->paginate();
        $pages = $list->render();
        $this->assign('list', $list);
        $this->assign('pages', $pages);
        $this->assign('left', 'download_list');
        return $this->fetch();
    }

    public function auth_list()
    {
        $list = YTAModel::alias('yta')
            ->join('hx_yun_website yw', 'yw.id=yta.website_id')
            ->join('hx_yun_template yt', 'yt.id=yta.template_id')
            ->field('yta.id,yta.create_time,yw.name as website,yw.domain,yw.ip,yt.title as template,yt.author,yt.price')
            ->order('yta.id desc')
            ->paginate();
        $pages = $list->render();
        $this->assign('list', $list);
        $this->assign('pages', $pages);
        $this->assign('left', 'auth_list');
        return $this->fetch();
    }

    public function auth_edit()
    {
        $this->assign('left', 'auth_list');
        return $this->fetch();
    }

    public function auth_ajax()
    {
        $param = $this->request->param();
        if (!$param['keyword'] || !$param['type']) {
            return json(['code' => 0]);
        }
        if ($param['type'] == 'template') {
            $list = YTModel::getListByMap(['title' => ['like', '%' . $param['keyword'] . '%']]);
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
            $result = $this->validate($data, 'YunTemplateAuth');
            if ($result !== true) {
                $token = $this->request->token('__token__', 'sha1');
                $this->error($result, '', $token);
            }
            $auth = YTAModel::getDataByMap(['website_id' => $data['website_id'], 'template_id' => $data['template_id']]);
            if ($auth) {
                $token = $this->request->token('__token__', 'sha1');
                $this->error('请勿重复授权', '', $token);
            }
            $result = YTAModel::addData($data);
            if ($result) {
                return $this->success('保存成功', url('admin/yun.template/auth_list'));
            }
            return $this->error('保存失败');
        }
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
        $result = YTAModel::deleteData($map);
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
        $list = YTBModel::alias('ytb')
            ->join('hx_yun_template yt', 'ytb.template_id = yt.id')
            ->join('hx_user_list ul', 'ytb.user_id = ul.id')
            ->field('ytb.id,ytb.buy_time as time,ytb.buy_price as price,yt.title as template_name,ul.username as user_name,ul.nickname as nick_name')
            ->order('ytb.id desc')
            ->select();
        $this->assign('list', $list);
        $this->assign('left', 'buy_list');
        return $this->fetch();
    }

    public function upload()
    {
        $file = request()->file('file');
        $info = $file->getInfo();
        $name = $info['name'];
        if ($file) {
            $path = RUNTIME_PATH . 'template';
            $save = $file->move($path);
            if ($save) {
                $res = array(
                    'cod' => 1,
                    'msg' => '上传成功',
                    'filename' => $name,
                    'path' => 'runtime' . DS . 'template' . DS . $save->getSaveName(),
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
        $list = YTModel::alias('yt')
            ->join('__YUN_TEMPLATE_TYPE__ ytt', 'ytt.id=yt.type', 'LEFT')
            ->field('yt.*, ytt.name as type_name')
            ->where(['status' => ['neq', 1]])
            ->order('yt.id desc')
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
        $result = Db::name('yun_template')->where(['id' => $param['id']])->setField('status', $param['status']);
        if ($result !== false) {
            return json(true);
        }
        return json(false);
    }

}