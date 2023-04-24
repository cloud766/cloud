<?php
namespace app\admin\controller\yun;

use app\common\controller\Adminbase;
use app\common\model\yun\YunWebsite as YWModel;
use app\common\model\yun\YunTemplateAuth as YTAModel;
use app\common\model\yun\YunAddonAuth as YAAModel;
/**
 * 站点管理
 */
class Website extends Adminbase
{

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        $isBlack = $this->request->param('is_black');
        if ($isBlack) {
            $map['is_black'] = 1;
            $left = 'black';
        } else {
            $map['is_black'] = 0;
            $left = 'index';
        }
        $list = YWModel::alias('yw')
            ->where($map)
            ->join('hx_user_list ul', 'ul.id=yw.user_id', 'LEFT')
            ->field('yw.*, ul.nickname, ul.username')
            ->order('id desc')
            ->paginate();
        $pages = $list->render();
        $this->assign('list', $list);
        $this->assign('pages', $pages);
        $this->assign('left', $left);
        return $this->fetch();
    }

    public function edit()
    {
        $id = $this->request->param('id');
        $data = YWModel::getDataByMap(['id' => $id]);
        $this->assign('data', $data);
        $this->assign('left', 'index');
        return $this->fetch();
    }

    public function save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $result = $this->validate($data, 'YunWebsite');
            if ($result !== true) {
                $token = $this->request->token('__token__', 'sha1');
                $this->error($result, '', $token);
            }
            if ($data['id']) {
                $result = YWModel::editData($data, ['id' => $data['id']]);
            } else {
                $result = YWModel::addData($data);
            }
            if ($result) {
                return $this->success('保存成功', url('admin/yun.website/index'));
            }
            return $this->error('保存失败');
        }
    }

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
        $result = YWModel::deleteData($map);
        if ($result) {
            return json(true);
        }
        return json(false);
    }

    public function template()
    {
        $id = $this->request->param('id');
        $website = YWModel::getDataByMap(['id' => $id]);
        $list = YTAModel::alias('yta')
            ->join('hx_yun_website yw', 'yw.id=yta.website_id')
            ->join('hx_yun_template yt', 'yt.id=yta.template_id')
            ->field('yta.id,yta.create_time,yw.name as website,yw.domain,yw.ip,yt.title as template,yt.author,yt.price')
            ->where(['yta.website_id' => $id])
            ->select();
        $this->assign('website', $website);
        $this->assign('list', $list);
        $this->assign('left', 'index');
        return $this->fetch();
    }

    public function template_auth()
    {
        $id = $this->request->param('id');
        $website = YWModel::getDataByMap(['id' => $id]);
        $this->assign('website', $website);
        $this->assign('left', 'index');
        return $this->fetch();
    }

    public function template_auth_save()
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
                $this->error('请勿重复授权','',$token);
            }
            $result = YTAModel::addData($data);
            if ($result) {
                return $this->success('保存成功',url('admin/yun.website/template', array('id' => $data['website_id'])));
            }
            return $this->error('保存失败');
        }
    }

    public function addon()
    {
        $id = $this->request->param('id');
        $website = YWModel::getDataByMap(['id' => $id]);
        $list = YAAModel::alias('yaa')
            ->join('hx_yun_website yw', 'yw.id=yaa.website_id')
            ->join('hx_yun_addon ya', 'ya.id=yaa.addon_id')
            ->field('yaa.id,yaa.create_time,yw.name as website,yw.domain,yw.ip,ya.title as addon,ya.author,ya.price')
            ->where(['yaa.website_id' => $id])
            ->order('yaa.id desc')
            ->select();
        $this->assign('website', $website);
        $this->assign('list', $list);
        $this->assign('left', 'index');
        return $this->fetch();
    }

    public function addon_auth()
    {
        $id = $this->request->param('id');
        $website = YWModel::getDataByMap(['id' => $id]);
        $this->assign('website', $website);
        $this->assign('left', 'index');
        return $this->fetch();
    }

    public function addon_auth_save()
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
                $this->error('请勿重复授权','',$token);
            }
            $result = YAAModel::addData($data);
            if ($result) {
                return $this->success('保存成功',url('admin/yun.website/addon', array('id' => $data['website_id'])));
            }
            return $this->error('保存失败');
        }
    }
}