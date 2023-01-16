<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think;

class Lang
{
    /**
     * @var array 语言数据
     */
    private static $lang = [];

    /**
     * @var string 语言作用域
     */
    private static $range = 'zh-cn';

    /**
     * @var string 语言自动侦测的变量
     */
    protected static $langDetectVar = 'lang';

    /**
     * @var string 语言 Cookie 变量
     */
    protected static $langCookieVar = 'think_var';

    /**
     * @var int 语言 Cookie 的过期时间
     */
    protected static $langCookieExpire = 3600;

    /**
     * @var array 允许语言列表
     */
    protected static $allowLangList = [];

    /**
     * @var array Accept-Language 转义为对应语言包名称 系统默认配置
     */
    protected static $acceptLanguage = ['zh-hans-cn' => 'zh-cn'];

    /**
     * 设定当前的语言
     * @access public
     * @param  string $range 语言作用域
     * @return string
     */
    public static function range($range = '')
    {
        if ($range) {
            self::$range = $range;
        }

        return self::$range;
    }

    /**
     * 设置语言定义(不区分大小写)
     * @access public
     * @param  string|array  $name  语言变量
     * @param  string        $value 语言值
     * @param  string        $range 语言作用域
     * @return mixed
     */
    public static function set($name, $value = null, $range = '')
    {
        $range = $range ?: self::$range;

        if (!isset(self::$lang[$range])) {
            self::$lang[$range] = [];
        }

        if (is_array($name)) {
            return self::$lang[$range] = array_change_key_case($name) + self::$lang[$range];
        }

        return self::$lang[$range][strtolower($name)] = $value;
    }

    /**
     * 加载语言定义(不区分大小写)
     * @access public
     * @param  array|string $file 语言文件
     * @param  string $range      语言作用域
     * @return mixed
     */
    public static function load($file, $range = '')
    {

        $range = $range ?: self::$range;
        $file  = is_string($file) ? [$file] : $file;

        if (!isset(self::$lang[$range])) {
            self::$lang[$range] = [];
        }

        $lang = [];

        foreach ($file as $_file) {
            if (is_file($_file)) {
                // 记录加载信息
                App::$debug && Log::record('[ LANG ] ' . $_file, 'info');

                $white_list = [
                    'D:\phpstudy_pro\WWW\dky2\background\thinkphp\lang\zh-cn.php'=>'D:\phpstudy_pro\WWW\dky2\background\thinkphp\lang\zh-cn.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/lang/zh-cn.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/lang/zh-cn.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin\lang\zh-cn.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin\lang\zh-cn.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/addon.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/addon.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/ajax.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/ajax.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/category.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/category.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/config.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/config.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/dashboard.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/dashboard.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/district.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/district.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/dky_agv.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/dky_agv.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/dky_api.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/dky_api.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/dky_device.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/dky_device.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/dky_mission.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/dky_mission.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/dky_mission_experiment.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/dky_mission_experiment.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/dky_staff.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/dky_staff.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/dky_station.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/dky_station.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/dky_storage_rack.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/dky_storage_rack.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/dky_testing_problem.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/dky_testing_problem.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/experiment.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/experiment.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/index.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/index.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/laboratory_info.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/laboratory_info.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/maintenance_log.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/maintenance_log.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/rank.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/rank.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/rank_breakdown.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/rank_breakdown.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/sample_and_uwb.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/sample_and_uwb.php',
                    'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/score_list.php'=>'D:\phpstudy_pro\WWW\dky2\background\public/../application/admin/lang/zh-cn/score_list.php',

                    '/usr/local/nginx/html/elec/thinkphp/lang/zh-cn.php'=>'/usr/local/nginx/html/elec/thinkphp/lang/zh-cn.php',
                    '/usr/local/nginx/html/elec/public/../application/lang/zh-cn.php'=>'/usr/local/nginx/html/elec/public/../application/lang/zh-cn.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/addon.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/addon.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/ajax.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/ajax.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/category.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/category.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/config.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/config.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/dashboard.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/dashboard.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/district.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/district.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/dky_agv.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/dky_agv.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/dky_api.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/dky_api.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/dky_device.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/dky_device.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/dky_mission.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/dky_mission.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/dky_mission_experiment.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/dky_mission_experiment.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/dky_staff.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/dky_staff.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/dky_station.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/dky_station.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/dky_storage_rack.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/dky_storage_rack.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/dky_testing_problem.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/dky_testing_problem.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/experiment.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/experiment.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/index.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/index.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/laboratory_info.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/laboratory_info.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/maintenance_log.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/maintenance_log.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/rank.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/rank.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/rank_breakdown.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/rank_breakdown.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/dky_desample_and_uwbvice.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/sample_and_uwb.php',
                    '/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/score_list.php'=>'/usr/local/nginx/html/elec/public/../application/admin/lang/zh-cn/score_list.php',
                ];

                if (isset($white_list[$_file])){
                    $_lang = include  $white_list[$_file];
                }else{
                    $_lang = '';
                }


                if (is_array($_lang)) {
                    $lang = array_change_key_case($_lang) + $lang;
                }
            }
        }

        if (!empty($lang)) {
            self::$lang[$range] = $lang + self::$lang[$range];
        }

        return self::$lang[$range];
    }

    /**
     * 获取语言定义(不区分大小写)
     * @access public
     * @param  string|null $name  语言变量
     * @param  string      $range 语言作用域
     * @return mixed
     */
    public static function has($name, $range = '')
    {
        $range = $range ?: self::$range;

        return isset(self::$lang[$range][strtolower($name)]);
    }

    /**
     * 获取语言定义(不区分大小写)
     * @access public
     * @param  string|null $name  语言变量
     * @param  array       $vars  变量替换
     * @param  string      $range 语言作用域
     * @return mixed
     */
    public static function get($name = null, $vars = [], $range = '')
    {
        $range = $range ?: self::$range;

        // 空参数返回所有定义
        if (empty($name)) {
            return self::$lang[$range];
        }

        $key   = strtolower($name);
        $value = isset(self::$lang[$range][$key]) ? self::$lang[$range][$key] : $name;

        // 变量解析
        if (!empty($vars) && is_array($vars)) {
            /**
             * Notes:
             * 为了检测的方便，数字索引的判断仅仅是参数数组的第一个元素的key为数字0
             * 数字索引采用的是系统的 sprintf 函数替换，用法请参考 sprintf 函数
             */
            if (key($vars) === 0) {
                // 数字索引解析
                array_unshift($vars, $value);
                $value = call_user_func_array('sprintf', $vars);
            } else {
                // 关联索引解析
                $replace = array_keys($vars);
                foreach ($replace as &$v) {
                    $v = "{:{$v}}";
                }
                $value = str_replace($replace, $vars, $value);
            }

        }

        return $value;
    }



    /**
     * 设置语言自动侦测的变量
     * @access public
     * @param  string $var 变量名称
     * @return void
     */
    public static function setLangDetectVar($var)
    {
        self::$langDetectVar = $var;
    }

    /**
     * 设置语言的 cookie 保存变量
     * @access public
     * @param  string $var 变量名称
     * @return void
     */
    public static function setLangCookieVar($var)
    {
        self::$langCookieVar = $var;
    }

    /**
     * 设置语言的 cookie 的过期时间
     * @access public
     * @param  string $expire 过期时间
     * @return void
     */
    public static function setLangCookieExpire($expire)
    {
        self::$langCookieExpire = $expire;
    }

    /**
     * 设置允许的语言列表
     * @access public
     * @param  array $list 语言列表
     * @return void
     */
    public static function setAllowLangList($list)
    {
        self::$allowLangList = $list;
    }
}
