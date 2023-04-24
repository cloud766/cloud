<?php
namespace app\admin\controller\yun;

use app\common\controller\Adminbase;
use app\common\util\Http;
use app\common\util\Dir;
use app\common\model\yun\YunSourceCode as YSCModel;
use app\common\model\yun\YunSourceCodeType as YSCTModel;
use app\common\model\yun\YunSourceCodeVersion as YSCVModel;
use app\common\model\yun\YunSourceCodeWebsite as YSCWModel;
use app\common\model\yun\YunSourceCodeDownload as YSCDModel;
use app\common\model\yun\YunSourceCodeAuth as YSCAModel;
use app\common\model\yun\YunWebsite as YWModel;
use think\Db;
use think\Session;
use think\Loader;
/**
 * 源码管理
 */
class Sourcecode extends Adminbase
{
    /**
     *首页
     */
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 源码类型列表
     * @return [type] [description]
     */
    public function type_list()
    {
        $list = YSCTModel::order('id desc')->paginate();
        $pages = $list->render();
        $this->assign('pages', $pages);
        $this->assign('list', $list);
        $this->assign('left', 'type_list');
        return $this->fetch();
    }

    public function type_add()
    {
        $this->assign('left', 'type_list');
        return $this->fetch();
    }

    /**
     * 编辑源码类型
     * @return [type] [description]
     */
    public function type_edit()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = YSCTModel::where('id', $id)->find();
        $this->assign('data', $data);
        $this->assign('left', 'type_list');
        return $this->fetch();
    }

    /**
     * 保存源码类型
     * @return [type] [description]
     */
    public function type_save()
    {
        if ($this->request->isPost()) {;
            $data = $this->request->post();
            $result = $this->validate($data, 'YunSourceCodeType');
            if ($result !== true) {
                $token = $this->request->token('__token__', 'sha1');
                $this->error($result, '', $token);
            }
            if (!$data['id']) {
                //存入时间戳
                $data['create_time'] = time();
                $data['update_time'] = time();
                $result = YSCTModel::create($data);
            } else {
                $id = $data['id'];
                unset($data['id']);
                //存入时间戳
                $data['update_time'] = time();
                $result = YSCTModel::update($data, ['id' => $id]);
            }
            if ($result !== false) {
                return $this->success('保存成功', url('admin/yun.sourcecode/type_list'));
            }
            $token = $this->request->token('__token__', 'sha1');
            $this->error('保存失败', '', $token);
        }
    }

    /**
     * 删除源码类型
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
        $result = YSCTModel::deleteData($map);
        if ($result) {
            return json(true);
        }
        return json(false);
    }

    /**
     * 源码列表
     * @return [type] [description]
     */
    public function code_list()
    {
        $list = Db::table('__YUN_SOURCE_CODE__')->alias('ysc')
            ->join('__YUN_SOURCE_CODE_TYPE__ ysct', 'ysct.id=ysc.type', 'LEFT')
            ->order('ysc.id desc')
            ->field('ysc.id,ysc.title,ysc.name,ysc.description,ysc.create_time, ysct.name as type_name')
            ->paginate();
        $pages = $list->render();
        $this->assign('list', $list);
        $this->assign('pages', $pages);
        $this->assign('left', 'code_list');
        return $this->fetch();
    }

    /**
     * 编辑源码
     * @return [type] [description]
     */
    public function code_edit()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = YSCModel::where('id', $id)->find();
        $type_list = YSCTModel::select();
        $this->assign('type_list', $type_list);
        $this->assign('data', $data);
        $this->assign('left', 'code_list');
        return $this->fetch();
    }

    public function code_save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $result = $this->validate($data, 'YunSourceCode');
            if ($result !== true) {
                $token = $this->request->token('__token__', 'sha1');
                $this->error($result, '', $token);
            }
            if (!$data['id']) {
                $result = YSCModel::addData($data);
            } else {
                $result = YSCModel::editData($data, ['id' => $data['id']]);
            }
            if ($result !== false) {
                return $this->success('保存成功', url('admin/yun.sourcecode/code_list'));
            }
            $token = $this->request->token('__token__', 'sha1');
            return $this->error('保存失败', '', $token);
        }
    }

    public function code_delete()
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
        $result = YSCModel::deleteData($map);
        if ($result) {
            return json(true);
        }
        return json(false);
    }

    /**
     * 源码版本列表
     * @return [type] [description]
     */
    public function version_list()
    {
        $sourcecode_id = $this->request->param('sourcecode_id');
        $sourcecode = YSCModel::getDataByMap(['id' => $sourcecode_id]);
        $data = YSCVModel::getPageByParam(['map' => ['sourcecode_id' => $sourcecode_id]]);
        $this->assign('sourcecode', $sourcecode);
        $this->assign('list', $data['list']);
        $this->assign('page', $data['page']);
        $this->assign('left', 'code_list');
        return $this->fetch();
    }

    /**
     * 编辑源码版本
     * @return [type] [description]
     */
    public function version_edit()
    {
        $param = $this->request->param();
        $data = YSCVModel::where('id', $param['id'])->find();
        if (!$data) {
            $data['sourcecode_id'] = $param['sourcecode_id'];
        }
        $type_list = YSCTModel::select();
        $this->assign('type_list', $type_list);
        $this->assign('data', $data);
        $this->assign('left', 'code_list');
        return $this->fetch();
    }

    /**
     * 保存源码版本
     * @return [type] [description]
     */
    public function version_save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $result = $this->validate($data, 'YunSourceCodeVersion');
            if ($result !== true) {
                $token = $this->request->token('__token__', 'sha1');
                $this->error($result, '', $token);
            }
            //添加更新包时，判断更新包版本号是否为最大
            if (!$data['id']) {
                $sourcecodeVersion = Db::name('yun_source_code_version')->where(['sourcecode_id' => $data['sourcecode_id']])->order('version desc')->find();
                if ($sourcecodeVersion && $sourcecodeVersion['version'] >= $data['version']) {
                    $token = $this->request->token('__token__', 'sha1');
                    $this->error('新增版本时版本必须为最大', '', $token);
                }
            } else {
                $sourcecodeVersion = Db::name('yun_source_code_version')->where(['version' => $data['version'], 'sourcecode_id' => $data['sourcecode_id'], 'id' => ['neq', $data['id']]])->find();
                if ($sourcecodeVersion) {
                    $token = $this->request->token('__token__', 'sha1');
                    $this->error('版本号不可重复', '', $token);
                }
            }
            if (!$data['id']) {
                //存入时间戳
                $data['create_time'] = time();
                $result = YSCVModel::create($data);
            } else {
                $id = $data['id'];
                unset($data['id']);
                //存入时间戳
                $data['update_time'] = time();
                $result = YSCVModel::update($data, ['id' => $id]);
            }
            if ($result !== false) {
                //移动文件
                $sourcecode = Db::name('yun_source_code')->where(['id' => $data['sourcecode_id']])->find();
                $path = ROOT_PATH . 'data' . DS . 'source_code' . DS . $sourcecode['name'] . DS . $data['version'] . DS;
                if (!is_dir($path)) {
                    Dir::create($path);
                }
                if ($data['path']) {
                    rename(ROOT_PATH . $data['path'], $path . $data['file_name']);
                }
                if ($data['check_path']) {
                    rename(ROOT_PATH . $data['check_path'], $path . $data['check_file_name']);
                }
                return $this->success('保存成功', url('admin/yun.sourcecode/version_list', array('sourcecode_id' => $data['sourcecode_id'])));
            }
            return $this->error('保存失败');
        }
    }

    /**
     * 删除源码版本
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
        $result = YSCVModel::deleteData($map);
        if ($result) {
            return json(true);
        }
        return json(false);
    }


    public function code_file_list()
    {
        $path = '../data/source_code' . DS;
        if (!is_dir($path)) {
            Dir::create($path);
        }
        $flag = \FilesystemIterator::KEY_AS_FILENAME;
        $glob = new \FilesystemIterator($path, $flag);
        $list = [];
        foreach ($glob as $key => $value) {
            $code['name'] = $key;
            $code['size'] = $value->getSize();
            $code['version'] = YSCVModel::getDataByMap(['file_name' => $key]);
            array_push($list, $code);
        }
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function website_list()
    {
        $list = YSCWModel::alias('yscw')
            ->join('__YUN_WEBSITE__ yw', 'yw.id=yscw.website_id', 'LEFT')
            ->join('__YUN_SOURCE_CODE__ ysc', 'ysc.id=yscw.sourcecode_id', 'LEFT')
            ->join('__YUN_SOURCE_CODE_TYPE__ ysct', 'ysct.id=ysc.type', 'LEFT')
            ->join('__YUN_SOURCE_CODE_VERSION__ yscv', 'yscv.id=yscw.version_id', 'LEFT')
            ->field('yscw.create_time, yw.name as website_name, ysc.title as sourcecode_name, yscv.version as sourcecode_verison, ysct.name as type_name')
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
        $data = YSCWModel::where('id', $id)->find();
        $version_list = YSCVModel::select();
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
                $result = YSCWModel::create($data);
            } else {
                $id = $data['id'];
                unset($data['id']);
                //存入时间戳
                $data['update_time'] = time();
                $result = YSCWModel::update($data, ['id' => $id]);
            }
            if ($result !== false) {
                return $this->success('保存成功');
            }
            return $this->error('保存失败');
        }
        $this->redirect(url('admin/yun.sourcecode/version_list'));
    }

    /**
     * 删除站点
     * @return [type] [description]
     */
    public function website_delete()
    {
        $param = $this->request->param();
        $id = $param['id'];
        //检查是否为空 为空返回false
        if (is_null($id)) {
            return json(false);
        }
        //循环删除
        if (is_array($id)) {
            foreach ($id as $v) {
                $result = YSCWModel::where(['id' => $v])->delete();
            }
            if ($result) {
                return json(true);
            }
        } else {
            $result = YSCWModel::where(['id' => $id])->delete();
        }
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
        $list = YSCDModel::alias('yscd')
            ->join('__YUN_WEBSITE__ yw', 'yw.id=yscd.website_id', 'LEFT')
            ->join('__YUN_SOURCE_CODE__ ysc', 'ysc.id=yscd.sourcecode_id', 'LEFT')
            ->join('__YUN_SOURCE_CODE_VERSION__ yscv', 'yscv.id=yscd.version_id', 'LEFT')
            ->field('yscd.id, yscd.create_time, yscd.ip, yw.name, yw.domain,yscv.title')
            ->order('id desc')->paginate();
        $pages = $list->render();
        $this->assign('list', $list);
        $this->assign('pages', $pages);
        $this->assign('left', 'download_list');
        return $this->fetch();
    }

    public function auth_list()
    {
        $list = YSCAModel::alias('ysca')
            ->join('__YUN_SOURCE_CODE__ ysc', 'ysca.sourcecode_id=ysc.id', 'LEFT')
            ->join('__YUN_WEBSITE__ yw', 'ysca.website_id=yw.id', 'LEFT')
            ->field('ysca.id,ysca.update_key,ysca.update_time,ysca.deadline,ysc.title,yw.name,yw.ip,yw.domain,yw.phone,yw.qq')
            ->order('ysca.id desc')
            ->paginate();
        $pages = $list->render();
        $this->assign('pages', $pages);
        $this->assign('list', $list);
        $this->assign('left', 'auth_list');
        return $this->fetch();
    }

    public function auth_edit()
    {
        $id = $this->request->param('id');
        $data = YSCAModel::getDataByMap(['id' => $id]);
        if($data){
            $data['deadline_type'] = $data['deadline'] == 0 ? 1 : 0;
            $data['deadline'] = $data['deadline'] == 0 ? date('Y-m-d', time()+365*24*3600) : date('Y-m-d', $data['deadline']);
            $data['website'] = Db::name('yun_website')->where(['id'=>$data['website_id']])->find();
            $data['sourcecode'] = Db::name('yun_source_code')->where(['id'=>$data['sourcecode_id']])->find();
        }else{
            $data['deadline'] = date('Y-m-d', time()+365*24*3600);
            $data['deadline_type'] = 1;
        }
        $this->assign('data', $data);
        $this->assign('left', 'auth_list');
        return $this->fetch();
    }

    public function auth_ajax()
    {
        $param = $this->request->param();
        if (!$param['keyword'] || !$param['type']) {
            return json(['code' => 0]);
        }
        if ($param['type'] == 'sourcecode') {
            $list = YSCModel::getListByMap(['title' => ['like', '%' . $param['keyword'] . '%']]);
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
            $data = $this->request->post();
            if(!$data['id']){
                $result = $this->validate($data, 'YunSourceCodeAuth');
                if ($result !== true) {
                    $token = $this->request->token('__token__', 'sha1');
                    $this->error($result, '', $token);
                }
                $auth = YSCAModel::getDataByMap(['website_id' => $data['website_id'], 'sourcecode_id' => $data['sourcecode_id']]);
                if ($auth) {
                    $token = $this->request->token('__token__', 'sha1');
                    $this->error('请勿重复授权', '', $token);
                }
            }
            if($data['deadline_type'] == 1){
                $data['deadline'] = 0;
            }else{
                $data['deadline'] = strtotime($data['deadline']);
            }
            if (!$data['id']) {
                $result = YSCAModel::addData($data);
            } else {
                $result = YSCAModel::editData($data, ['id' => $data['id']]);
            }
            if ($result !== false) {
                return $this->success('保存成功', url('admin/yun.sourcecode/auth_list'));
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
        $result = YSCAModel::deleteData($map);
        if ($result) {
            return json(true);
        }
        return json(false);
    }

    public function upload()
    {
        $file = request()->file('file');
        $info = $file->getInfo();
        $name = $info['name'];
        if ($file) {
            $path = RUNTIME_PATH . 'sourcecode';
            $save = $file->move($path);
            if ($save) {
                $res = array(
                    'cod' => 1,
                    'msg' => '上传成功',
                    'filename' => $name,
                    'path' => 'runtime' . DS . 'sourcecode' . DS . $save->getSaveName(),
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

    // public function upload()
    // {
    //     $file = request()->file('file');
    //     $info = $file->getInfo();
    //     $name = $info['name'];
    //     if ($file) {
    //         $path = ROOT_PATH . 'data' . DS . 'source_code';
    //         $save = $file->move($path, $name);
    //         if ($save) {
    //             $res = array(
    //                 'cod' => 1,
    //                 'msg' => '上传成功',
    //                 'filename' => $name
    //             );
    //         } else {
    //         // 上传失败获取错误信息
    //             $res = array(
    //                 'cod' => 2,
    //                 'msg' => '上传失败' . ':' . $file->getError()
    //             );
    //         }
    //     } else {
    //         $res = array(
    //             'cod' => 3,
    //             'msg' => '上传失败' . '没有文件'
    //         );
    //     }

    //     return json($res);
    // }

}