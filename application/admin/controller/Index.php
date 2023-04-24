<?php

namespace app\admin\controller;



use app\common\controller\Adminbase;

use think\Db;

use think\Env;

use app\common\util\Http;

use app\common\model\Gallery as GModel;

use app\common\model\Attachment as AModel;



class Index extends Adminbase

{

    public function index()

    {

        //数量统计

        $codeCount = Db::table('hx_yun_source_code')->count();

        $codeVersionCount = Db::table('hx_yun_source_code_version')->count();

        $this->assign('codeCount', $codeCount);

        $this->assign('codeVersionCount', $codeVersionCount);

        $addonCount = Db::table('hx_yun_addon')->count();

        $addonDownloadCount = Db::table('hx_yun_addon_download')->count();

        $addonAuthCount = Db::table('hx_yun_addon_auth')->count();

        $this->assign('addonCount', $addonCount);

        $this->assign('addonDownloadCount', $addonDownloadCount);

        $this->assign('addonAuthCount', $addonAuthCount);

        $templateCount = Db::table('hx_yun_template')->count();

        $templateDownloadCount = Db::table('hx_yun_template_download')->count();

        $templateAuthCount = Db::table('hx_yun_template_auth')->count();

        $this->assign('templateCount', $templateCount);

        $this->assign('templateDownloadCount', $templateDownloadCount);

        $this->assign('templateAuthCount', $templateAuthCount);

        //站点列表

        $websiteList = Db::table('hx_yun_website')->alias('w')

            ->field('w.*,u.nickname')

            ->join('hx_user_list u', 'u.id=w.user_id', 'LEFT')

            ->where(['w.is_black' => ['neq', '1']])

            ->limit(6)

            ->order('w.id desc')

            ->select();

        if ($websiteList) {

            foreach ($websiteList as $key => $value) {

                $websiteAuth = Db::table('hx_yun_source_code_auth')->where(['website_id' => $value['id']])->find();

                $websiteList[$key]['auth'] = $websiteAuth ? 1 : 0;

            }

        }

        $this->assign('websiteList', $websiteList);

        //站点黑名单

        $blackList = Db::table('hx_yun_website')->alias('w')

            ->field('w.*,u.nickname')

            ->join('hx_user_list u', 'u.id=w.user_id', 'LEFT')

            ->where(['is_black' => 1])

            ->limit(6)

            ->order('w.id desc')

            ->select();

        $this->assign('blackList', $blackList);

        //插件列表

        $addonList = Db::table('hx_yun_addon')->alias('a')

            ->field('a.*,u.nickname,sc.title as code_name')

            ->join('hx_user_list u', 'u.id=a.user_id', 'LEFT')

            ->join('hx_yun_source_code sc', 'sc.id=a.code_id', 'LEFT')

            ->limit(6)

            ->order('a.id desc')

            ->select();

        $this->assign('addonList', $addonList);

        //插件列表

        $templateList = Db::table('hx_yun_template')->alias('t')

            ->field('t.*,u.nickname,sc.title as code_name,tt.name as type_name')

            ->join('hx_user_list u', 'u.id=t.user_id', 'LEFT')

            ->join('hx_yun_source_code sc', 'sc.id=t.code_id', 'LEFT')

            ->join('hx_yun_template_type tt', 'tt.id=t.type', 'LEFT')

            ->limit(6)

            ->order('id desc')

            ->select();

        $this->assign('templateList', $templateList);

        return $this->fetch();

    }



    public function clearRuntime()

    {

        $R = ROOT_PATH . 'runtime';

        if ($this->_deleteDir($R)) {

            $msg = '清除缓存成功!';

        } else {

            $msg = '清除缓存失败!';

        }

        return json(['msg' => $msg]);

    }



    private function _deleteDir($R)

    {

        $handle = opendir($R);

        while (($item = readdir($handle)) !== false) {

            if ($item != '.' and $item != '..') {

                if (is_dir($R . '/' . $item)) {

                    $this->_deleteDir($R . '/' . $item);

                } else {

                    if (!unlink($R . '/' . $item))

                        die('error!');

                }

            }

        }

        closedir($handle);

        return rmdir($R);

    }



    public function webupload()

    {

        $param = $this->request->param();

        $list = AModel::getListByMap();

        $param['url'] = url('admin/index/upload', array('type' => $param['type']));

        $this->assign('param', $param);

        $this->assign('list', $list);

        return $this->fetch();

    }



    public function upload()

    {

        $file = request()->file('file');

        $type = $this->request->param('attachment_type');

        $config = get_config(['group' => 'img_info']);

        if ($file) {

            //移动到框架应用根目录/public/uploads/ 目录下

            $path = ROOT_PATH . 'public' . DS . 'uploads';

            $save = $file->move($path);

            /*水印开始*/

            if ($config['water_upload'] == 1) {

                //获取原图路径

                $path = ROOT_PATH . 'public' . DS . '\uploads' . '\\' . $save->getSaveName();

                //获取水印图路径

                $watepath = ROOT_PATH . 'public' . $config['wateimg'];

                //获取水印添加位置

                $locate = $config['water_position'];

                //获取水印透明度

                $alpha = $config['water_transparent'];  

                //添加水印

                $this->picture($watepath, $path, $locate, $alpha, $path);

            }         

            /*水印结束*/

            if ($save) {

                $info = $save->getInfo();

                $attachment['name'] = $info['name'];

                $attachment['path'] = '\uploads' . '\\' . $save->getSaveName();

                $attachment['size'] = $info['size'];

                $attachment['type'] = $type ? $type : 'file';

                $attachment['create_time'] = time();

                Db('attachment')->insert($attachment);

                $res = array(

                    'cod' => 1,

                    'msg' => '上传成功',

                    'path' => '\uploads' . '\\' . $save->getSaveName(),

                    'filename' => $info['name'],

                    'filesize' => $info['size']

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

                'msg' => '上传失败' . '没有图片'

            );

        }



        return json($res);

    }



    public function ajaxDelGallery()

    {

        $param = $this->request->param();

        $id = $param['id'];

        $result = GModel::destroy($id);

        if ($result) {

            return json(true);

        }

        return json(false);

    }

}

