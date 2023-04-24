<?php
namespace app\index\controller;

use app\common\controller\HomeBase;
use think\Db;

class CreateTag extends HomeBase
{

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        return $this->fetch();
    }

    public function nav()
    {
        return $this->fetch();
    }

    public function column()
    {
        return $this->fetch();
    }

    public function lists()
    {
        return $this->fetch();
    }

    public function createTagLib()
    {
        $param = $this->request->param();
        $tagType = $param['tag_type'];
        $tag = '';
        switch ($tagType) {
            case 'config':
                $tag .= '{hxtag:config name="' . $param['name'] . '"}';
                break;
            case 'url':
                $tag .= '{hxtag:url name="' . $param['name'] . '"}';
                break;
            case 'nav':
                $limit = intval($param['limit']) ? $param['limit'] : 10;
                $tag .= '{hxtag:nav typeid="' . $param['typeid'] . '" limit="' . $limit . '"}' . PHP_EOL;
                $tag .= '    <li><a href="{$nav.url}">{$nav.title}</a></li>' . PHP_EOL;
                $tag .= '{/hxtag:nav}';
                break;
            case 'position':
                $cid = trim($param['cid']) ? $param['cid'] : '$cid';
                $delimiter = trim($param['delimiter']) ? $param['delimiter'] : ',';
                $tag .= '{hxtag:position cid="' . $cid . '" name="' . $param['name'] . '" url="' . $param['url'] . '" delimiter="' . $delimiter . '"}';
                break;
            case 'columnlist':
                $limit = intval($param['limit']) ? $param['limit'] : 10;
                $cid = trim($param['cid']) ? $param['cid'] : '$cid';
                $tag .= '{hxtag:columnlist cid="' . $cid . '" type="' . $param['type'] . '" limit="' . $limit . '" flag="' . $param['flag'] . '"}' . PHP_EOL;
                $tag .= '    <li><a href="{$columnlist.url}">{$columnlist.title}</a></li>' . PHP_EOL;
                $tag .= '{/hxtag:columnlist}';
                break;
            case 'column':
                $cid = trim($param['cid']) ? $param['cid'] : '$cid';
                $tag .= '{hxtag:column cid="' . $cid . '" type="' . $param['type'] . '"}' . PHP_EOL;
                $tag .= '    <li><a href="{$column.url}">{$column.title}</a></li>' . PHP_EOL;
                $tag .= '{/hxtag:column}';
                break;
            case 'lists':
                $cid = trim($param['cid']) ? $param['cid'] : '$cid';
                $orderby = $param['orderby_1'] . ' ' . $param['orderby_2'];
                $limit = intval($param['limit']) ? $param['limit'] : 10;
                $keyword = trim($param['keyword']) ? $param['keyword'] : '';
                $tag .= '{hxtag:lists cid="' . $cid . '" orderby="' . $orderby . '" keyword="' . $keyword . '" limit="' . $limit . '"}' . PHP_EOL;
                $tag .= '   <a href="{$lists.url}"><img src="{$lists.thumb}" alt="{$lists.title}"></a>' . PHP_EOL;
                $tag .= '   <span>{:date("Y-m-d,", $lists.create_time)}</span>' . PHP_EOL;
                $tag .= '   <p>{$lists.title}</p>' . PHP_EOL;
                $tag .= '{/hxtag:lists}';
                break;
            case 'banner':
                $limit = intval($param['limit']) ? $param['limit'] : 10;
                $tid = trim($param['tid']) ? $param['tid'] : '$tid';
                $orderby = $param['orderby_1'] . ' ' . $param['orderby_2'];
                $tag .= '{hxtag:bannerlist tid="' . $tid . '" orderby="' . $orderby . '" limit="' . $limit . '"}' . PHP_EOL;
                $tag .= '    <li><a href="{$bannerlist.url}">' . PHP_EOL;
                $tag .= '        {if $bannerlist.show_type}' . PHP_EOL;
                $tag .= '            <img src="{$bannerlist.pic}" alt="{$bannerlist.title}">' . PHP_EOL;
                $tag .= '        {else/}' . PHP_EOL;
                $tag .= '             {$bannerlist.text}' . PHP_EOL;
                $tag .= '        {/if}' . PHP_EOL;
                $tag .= '    </a></li>' . PHP_EOL;
                $tag .= '{/hxtag:bannerlist}';
                break;
            case 'link':
                $limit = intval($param['limit']) ? $param['limit'] : 10;
                $orderby = $param['orderby_1'] . ' ' . $param['orderby_2'];
                $flag = $param['flag'] ? 1 : 0;
                $tag .= '{hxtag:linklist orderby="' . $orderby . '" limit="' . $limit . '"}' . PHP_EOL;
                if ($flag) {
                    $tag .= '   <a href="{$linklist.url}"><img src="{$linklist.pic}" alt="{$linklist.title}"></a>' . PHP_EOL;
                } else {
                    $tag .= '   <a href="{$linklist.url}">{$linklist.title}</a>' . PHP_EOL;
                }
                $tag .= '{/hxtag:linklist}';
                break;
            default:
                # code...
                break;
        }
        return json($tag);
    }
}
