<?php
namespace app\common\taglib;
use think\template\TagLib;
class Tag extends TagLib{
    /**
     * 定义标签列表
     */
    protected $tags   =  [
        // 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次
        'upload'    => ['attr' => 'name,path', 'close'=>0],
        'config'    => ['attr' => 'name', 'close'=>1],
    ];
    
    public function tagUpload($tag ,$content){
        $input = $tag['input'] ? $tag['input'] : 'pic';
        $path = $tag['path'] ? parent::autoBuildVar($tag['path']) : '';
        $value = $tag['value'] ? parent::autoBuildVar($tag['value']) : '';
        $multiple = $tag['multiple'] ? $tag['multiple'] : 0;
        $type = $tag['type'] ? $tag['type'] : 'image';
        $num = $tag['num'] ? $tag['num'] : 1;
        $callback = $tag['callback'] ? $tag['callback'] : 'upload_callback';
        $str = '';
        if (!$multiple) {
            $str = '<input type="text" class="form-control" id="';
            $str .= $input;
            $str .= '" name="';
            $str .= $input;
            $str .= '"';
            if ($value) {
                $str .= ' value="<?php echo ';
                $str .= $value;
                $str .= '?>" ';
            }
            $str .= ' placeholder="">';
        }
        $str .= '<span><button type="button" onclick="openIframe(this)" data-title="上传图片" data-url="';
        $str .= url('admin/index/webupload',array('multiple'=>$multiple, 'type'=>$type, 'num'=>$num, 'callback'=>$callback, 'input'=>$input));
        $str .= '" class="btn btn-danger btn-sm"><i class="fa fa-upload"></i> 上传</button></span>';
        if (!$multiple) {
            $str .= '<div class="input-group" style="margin-top:.5em;"><img src="';
            if ($path) {
                $str .= '<?php echo ';
                $str .= $path;
                $str .= '?>';
            }else{
                $str .= '__STATIC__/hx/images/nopic.jpg';
            }
            $str .= '" class="img-responsive" width="100px" id="p-';
            $str .= $input;
            $str .= '"><em class="close" style="position:absolute; top: 0px; right: 5px;" title="删除这张图片" onclick="sDelImg(this)">×</em></div>';
        }else{
            $str .= '<div class="box-footer"><ul class="mailbox-attachments clearfix" id="g-';
            $str .= $input;
            $str .= '"></ul>';
        }
        return $str;
    }

    public function tagConfig($tag){
        $config = get_config(['name'=>$tag['name']]);
        $value = $config[$tag['name']];
        return $value;
    }
}