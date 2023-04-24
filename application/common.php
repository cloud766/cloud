<?php
use think\Loader;
use think\Config;
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
/**
 * 设置错误级别，解决未定义数组报错机制
 */
error_reporting(E_ERROR | E_WARNING | E_PARSE);
define("MAX_MENU_LENGTH", '3');
define("MAX_SUB_MENU_LENGTH", "5");
// 应用公共文件

/**
 * 通用打印数据
 * @param  [type] $data [description]
 * @return [type]       [description]
 */

function p($data)
{
    // 定义样式
    $str = '<pre style="display: block;padding: 9.5px;margin: 44px 0 0 0;font-size: 13px;line-height: 1.42857;color: #333;word-break: break-all;word-wrap: break-word;background-color: #F5F5F5;border: 1px solid #CCC;border-radius: 4px;">';
    // 如果是boolean或者null直接显示文字；否则print
    if (is_bool($data)) {
        $show_data = $data ? 'true' : 'false';
    } elseif (is_null($data)) {
        $show_data = 'null';
    } else {
        $show_data = print_r($data, true);
    }
    $str .= $show_data;
    $str .= '</pre>';
    echo $str;
}

/**
 * 使用curl获取远程数据
 * @param  string $url url连接
 * @return string      获取到的数据
 */
function curl_get_contents($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);                //设置访问的url地址
    // curl_setopt($ch,CURLOPT_HEADER,1);               //是否显示头部信息
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);               //设置超时
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);   //用户访问代理 User-Agent
    curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);        //设置 referer
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);          //跟踪301
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);        //返回结果
    $r = curl_exec($ch);
    curl_close($ch);
    return $r;
}

/**
 * 获取配置数组
 * @param  [type] $map [description]
 * @return [type]      [description]
 */
function get_config($map)
{
    $config = db('Config')->where($map)->select();
    $list = [];
    foreach ($config as $key => $value) {
        $list[$value['name']] = $value['value'];
    }
    return $list;
}

/**
 * 将数据库中查出的列表以指定的 name 作为数组的key
 * @param  [type] $arr      [description]
 * @param  [type] $key_name [description]
 * @return [type]           [description]
 */
function convert_arr_key($arr, $key_name)
{
    $arr2 = array();
    foreach ($arr as $key => $val) {
        $arr2[$val[$key_name]] = $val;
    }
    return $arr2;
}


/**
 * 返回插件路径
 * @param  [type] $name [description]
 * @return [type]       [description]
 */
function get_addons_class($name)
{
    return "addons\\{$name}\\{$name}";
}

/**
 * 获取微信对象
 * @return [type] [description]
 */
function get_wechat()
{
    vendor('mikkle.tp_wechat.Wechat');
    $map['type'] = 'wechat_info';
    $config = get_config($map);
    $wechat = new Wechat($config);
    return $wechat;
}


function scanFile($path, $result)
{
    $files = scandir($path);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $file_path = $path . '/' . $file;
            if (is_dir($file_path)) {
                $result = scanFile($file_path, $result);
            } else {
                $data['name'] = basename($file);
                $data['md5_code'] = md5_file($file_path);
                $new_path = str_replace(ROOT_PATH, '', $file_path);
                $data['path'] = $new_path;
                $result[] = $data;
            }
        }
    }
    return $result;
}
/**
 * 获得插件自动加载的配置
 * @return array
 */
function get_addon_autoload_config($truncate = false)
{
    // 读取addons的配置
    $config = (array)Config::get('addons');
    if ($truncate) {
        // 清空手动配置的钩子
        $config['hooks'] = [];
    }
    $route = [];
    // 读取插件目录及钩子列表
    $base = get_class_methods("\\think\\Addons");
    $base = array_merge($base, ['install', 'uninstall', 'enable', 'disable']);

    $url_domain_deploy = Config::get('url_domain_deploy');
    $addons = get_addon_list();
    $domain = [];
    foreach ($addons as $name => $addon) {
        if (!$addon['state'])
            continue;

        // 读取出所有公共方法
        $methods = (array)get_class_methods("\\addons\\" . $name . "\\" . ucfirst($name));
        // 跟插件基类方法做比对，得到差异结果
        $hooks = array_diff($methods, $base);
        // 循环将钩子方法写入配置中
        foreach ($hooks as $hook) {
            $hook = Loader::parseName($hook, 0, false);
            if (!isset($config['hooks'][$hook])) {
                $config['hooks'][$hook] = [];
            }
            // 兼容手动配置项
            if (is_string($config['hooks'][$hook])) {
                $config['hooks'][$hook] = explode(',', $config['hooks'][$hook]);
            }
            if (!in_array($name, $config['hooks'][$hook])) {
                $config['hooks'][$hook][] = $name;
            }
        }
        $conf = get_addon_config($addon['name']);
        if ($conf) {
            $conf['rewrite'] = isset($conf['rewrite']) && is_array($conf['rewrite']) ? $conf['rewrite'] : [];
            $rule = array_map(function ($value) use ($addon) {
                return "{$addon['name']}/{$value}";
            }, array_flip($conf['rewrite']));
            if ($url_domain_deploy && isset($conf['domain']) && $conf['domain']) {
                $domain[] = [
                    'addon' => $addon['name'],
                    'domain' => $conf['domain'],
                    'rule' => $rule
                ];
            } else {
                $route = array_merge($route, $rule);
            }
        }
    }
    $config['route'] = $route;
    $config['route'] = array_merge($config['route'], $domain);
    return $config;
}

/**
 * 获取插件类的类名
 * @param $name 插件名
 * @param string $type 返回命名空间类型
 * @param string $class 当前类名
 * @return string
 */
function get_addon_class($name, $type = 'hook', $class = null)
{
    $name = Loader::parseName($name);
    
    // 处理多级控制器情况
    if (!is_null($class) && strpos($class, '.')) {
        $class = explode('.', $class);

        $class[count($class) - 1] = Loader::parseName(end($class), 1);
        $class = implode('\\', $class);
    } else {
        $class = Loader::parseName(is_null($class) ? $name : $class, 1);
    }
    switch ($type) {
        case 'controller':
            $namespace = "\\addons\\" . $name . "\\controller\\" . $class;
            break;
        default:
            $namespace = "\\addons\\" . $name . "\\" . $class;
    }
    return class_exists($namespace) ? $namespace : '';
}

/**
 * 读取插件的基础信息
 * @param string $name 插件名
 * @return array
 */
function get_addon_info($name)
{
    $addon = get_addon_instance($name);
    if (!$addon) {
        return [];
    }
    return getInfo($name);
}
/**
 * 获取插件类的配置值值
 * @param string $name 插件名
 * @return array
 */
function get_addon_config($name)
{
    $addon = get_addon_instance($name);
    if (!$addon) {
        return [];
    }
    return getConfig($name);
}
function getConfig($name = '')
{
    if (empty($name)) {
        $name = $this->getName();
    }
    if (Config::has($name, 'addonconfig')) {
        return Config::get($name, 'addonconfig');
    }
    $config = [];
    $config_file = ROOT_PATH . 'addons' . DS . $name . DS . 'config.php';
    if (is_file($config_file)) {
        $temp_arr = include $config_file;
        foreach ($temp_arr as $key => $value) {
            $config[$value['name']] = $value['value'];
        }
        unset($temp_arr);
    }
    Config::set($name, $config, 'addonconfig');

    return $config;
}
function getInfo($name = '')
{

    if (empty($name)) {
        $name = $this->getName();
    }
    if (Config::has($name, 'addoninfo')) {
        return Config::get($name, 'addoninfo');
    }
    $info_file = ROOT_PATH . 'addons' . DS . $name . DS . 'info.ini';
    if (is_file($info_file)) {
        $info = Config::parse($info_file, '', $name, 'addoninfo');
        $info['url'] = addon_url($name);
    }
    Config::set($name, $info, 'addoninfo');

    return $info;
}
/**
 * 获取插件类的配置数组
 * @param string $name 插件名
 * @return array
 */
function get_addon_fullconfig($name)
{
    $addon = get_addon_instance($name);
    if (!$addon) {
        return [];
    }
    return $addon->getFullConfig($name);
}


/**
 * 获取插件的单例
 * @param $name
 * @return mixed|null
 */
function get_addon_instance($name)
{
    static $_addons = [];
    if (isset($_addons[$name])) {
        return $_addons[$name];
    }
    $class = get_addon_class($name);
    if (class_exists($class)) {
        $_addons[$name] = new $class();
        return $_addons[$name];
    } else {
        return null;
    }
}

/**
 * 插件显示内容里生成访问插件的url
 * @param $url 地址 格式：插件名/控制器/方法
 * @param array $vars 变量参数
 * @param bool|string $suffix 生成的URL后缀
 * @param bool|string $domain 域名
 * @return bool|string
 */
function addon_url($url, $vars = [], $suffix = true, $domain = false)
{
    $url = ltrim($url, '/');
    $addon = substr($url, 0, stripos($url, '/'));
    if (!is_array($vars)) {
        parse_str($vars, $params);
        $vars = $params;
    }
    $params = [];
    foreach ($vars as $k => $v) {
        if (substr($k, 0, 1) === ':') {
            $params[$k] = $v;
            unset($vars[$k]);
        }
    }
    $val = "@addons/{$url}";
    $config = get_addon_config($addon);
    $dispatch = think\Request::instance()->dispatch();
    $indomain = isset($dispatch['var']['indomain']) && $dispatch['var']['indomain'] ? true : false;
    $domainprefix = $config && isset($config['domain']) && $config['domain'] ? $config['domain'] : '';
    $rewrite = $config && isset($config['rewrite']) && $config['rewrite'] ? $config['rewrite'] : [];
    if ($rewrite) {
        $path = substr($url, stripos($url, '/') + 1);
        if (isset($rewrite[$path]) && $rewrite[$path]) {
            $val = $rewrite[$path];
            array_walk($params, function ($value, $key) use (&$val) {
                $val = str_replace("[{$key}]", $value, $val);
            });
            $val = str_replace(['^', '$'], '', $val);
            if (substr($val, -1) === '/') {
                $suffix = false;
            }
        } else {
            // 如果采用了域名部署,则需要去掉前两段
            if ($indomain && $domainprefix) {
                $arr = explode("/", $val);
                $val = implode("/", array_slice($arr, 2));
            }
        }
    } else {
        // 如果采用了域名部署,则需要去掉前两段
        if ($indomain && $domainprefix) {
            $arr = explode("/", $val);
            $val = implode("/", array_slice($arr, 2));
        }
        foreach ($params as $k => $v) {
            $vars[substr($k, 1)] = $v;
        }
    }
    return url($val, [], $suffix, $domain) . ($vars ? '?' . http_build_query($vars) : '');
}

/**
 * 设置基础配置信息
 * @param string $name 插件名
 * @param array $array
 * @return boolean
 * @throws Exception
 */
function set_addon_info($name, $array)
{
    $file = ADDON_PATH . $name . DIRECTORY_SEPARATOR . 'info.ini';
    $addon = get_addon_instance($name);
    $array = $addon->setInfo($name, $array);
    $res = array();
    foreach ($array as $key => $val) {
        if (is_array($val)) {
            $res[] = "[$key]";
            foreach ($val as $skey => $sval)
                $res[] = "$skey = " . (is_numeric($sval) ? $sval : $sval);
        } else
            $res[] = "$key = " . (is_numeric($val) ? $val : $val);
    }
    if ($handle = fopen($file, 'w')) {
        fwrite($handle, implode("\n", $res) . "\n");
        fclose($handle);
        //清空当前配置缓存
        Config::set($name, null, 'addoninfo');
    } else {
        throw new Exception("文件没有写入权限");
    }
    return true;
}

/**
 * 写入配置文件
 * @param string $name 插件名
 * @param array $config 配置数据
 * @param boolean $writefile 是否写入配置文件
 */
function set_addon_config($name, $config, $writefile = true)
{
    $addon = get_addon_instance($name);
    $addon->setConfig($name, $config);
    $fullconfig = get_addon_fullconfig($name);
    foreach ($fullconfig as $k => &$v) {
        if (isset($config[$v['name']])) {
            $value = $v['type'] !== 'array' && is_array($config[$v['name']]) ? implode(',', $config[$v['name']]) : $config[$v['name']];
            $v['value'] = $value;
        }
    }
    if ($writefile) {
        // 写入配置文件
        set_addon_fullconfig($name, $fullconfig);
    }
    return true;
}

/**
 * 写入配置文件
 *
 * @param string $name 插件名
 * @param array $array
 * @return boolean
 * @throws Exception
 */
function set_addon_fullconfig($name, $array)
{
    $file = ADDON_PATH . $name . DIRECTORY_SEPARATOR . 'config.php';
    if (!is_really_writable($file)) {
        throw new Exception("文件没有写入权限");
    }
    if ($handle = fopen($file, 'w')) {
        fwrite($handle, "<?php\n\n" . "return " . var_export($array, true) . ";\n");
        fclose($handle);
    } else {
        throw new Exception("文件没有写入权限");
    }
    return true;
}

function parse_attr($value = '')
{
    if (is_array($value)) return $value;
    $array = preg_split('/[,;\r\n]+/', trim($value, ",;\r\n"));
    if (strpos($value, ':')) {
        $value = array();
        foreach ($array as $val) {
            list($k, $v) = explode(':', $val);
            $value[$k] = $v;
        }
    } else {
        $value = $array;
    }
    return $value;
}

function qrcode($url, $size = 4)
{
    Vendor('Phpqrcode.phpqrcode');
    QRcode::png($url, false, QR_ECLEVEL_L, $size, 2, false, 0xFFFFFF, 0x000000);
}

function timeDiff($unixTime_1, $unixTime_2)
{
    $timediff = abs($unixTime_2 - $unixTime_1);
    //计算天数
    $days = intval($timediff / 86400);
    //计算小时数
    $remain = $timediff % 86400;
    $hours = intval($remain / 3600);
    //计算分钟数
    $remain = $remain % 3600;
    $mins = intval($remain / 60);
    //计算秒数
    $secs = $remain % 60;
    return ['day' => $days, 'hour' => $hours, 'min' => $mins, 'sec' => $secs];
}
