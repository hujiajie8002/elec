<?php


// +----------------------------------------------------------------------
// | 自定义设置
//
$ip = 'http://localhost:23701/dky.php/';
$ip_nj = 'http://26.47.100.215:8082/';
$ip_sz = 'http://26.47.100.215:8082/';
$ip_tz = 'http://26.47.100.215:8082/';
$ip_xz = 'http://26.47.100.215:8082/';
return array(
    //思创api地址
    'sceec_url' => 'http://www.sceec.com/API',
    //思创的报告api地址
    //'report_api_url' => 'http://180.157.68.166:60001/WSforBGJSON.asmx/GetBGJSON?testcode=',
    //'report_api_url' => 'http://101.229.207.53:60001/WSforBGJSON.asmx/GetBGJSON?testcode=',
    //'report_api_url' => 'http://192.168.18.207:8081/WSforBGJSON.asmx/GetBGJSON?testcode=',
    //'report_api_url' => 'http://116.237.40.163:60001/WSforBGJSON.asmx/GetBGJSON?testcode=',
    //内网
    'report_api_url' => 'http://26.47.100.215:8082/WSforBGJSON.asmx/GetBGJSON?testcode=',
    //外网
    'report_api_url_bak' => ':60001/WSforBGJSON.asmx/GetBGJSON?testcode=',
    //工位状态api地址
    //'station_status_url' => 'http://192.168.18.207:8081/WSforZTJSON.asmx/GetZTMessageJSON',

    //内网南京
    'station_status_url_nj' => 'http://26.47.100.215:8082/WSforZTJSON.asmx/GetZTMessageJSON',
    //外网南京
    'station_status_url_bak_nj' => ':60001/WSforZTJSON.asmx/GetZTMessageJSON',

    //内网苏州
    'station_status_url_sz' => 'http://26.47.100.215:8082/WSforZTJSON.asmx/GetZTMessageJSON',
    //外网苏州
    'station_status_url_bak_sz' => ':60001/WSforZTJSON.asmx/GetZTMessageJSON',




    //获取模块信息接口(内网)
    'module_url' => 'http://26.47.100.215:8082/WSforZTJSON.asmx/GetEquipmentJSON',
    //获取模块信息接口(外网)
    'module_url_bak' => ':60001/WSforZTJSON.asmx/GetEquipmentJSON',
    //过程数据提交间隔s
    'process_data_interval' => 1,
    //警告数据提交间隔s
    'warning_data_interval' => 1,
    //每次删除过程数据时保留的记录数
    'rows' => 500000,
    //查询历史数据接口，返回数据上限
    'history_limit'=>100,
    //查询告警数据接口，返回数据上限
    'alert_limit'=>100,
    //是否为互联网,1代表互联网,0代表电科院内网
    'is_internet' => 0,

    //本地过程数据接口地址
    'process_data_url' => $ip . 'api_index/getProcessData',
    //本地结果数据接口地址
    'report_data_url' => $ip . 'api_index/getReport',

    //本地告警数据接口
    'warning_data_url' => $ip . 'api_view/getWarningBak',


    'url_common_param'       => true,
    'url_html_suffix'        => '',
    'controller_auto_search' => true,
    'response_list' => array(
        '1'=>'success',
        '01'=>'错误！请输入必要参数',
        '02'=>'错误！无相关记录'
    ),




    //新版南京工位状态接口
    'njgwzt_url'=>$ip_nj.'WSforSzlsJson.asmx/getNodesInfo',
    //新版苏州工位状态接口
    'szgwzt_url'=>$ip_sz.'WSforSzlsJson.asmx/getNodesInfo',
    //新版泰州工位状态接口
    'tzgwzt_url'=>$ip_tz.'WSforSzlsJson.asmx/getNodesInfo',
    //新版徐州工位状态接口
    'xzgwzt_url'=>$ip_xz.'WSforSzlsJson.asmx/getNodesInfo',

    //新版南京样品信息接口
    'njypxx_url'=>$ip_nj.'WSforSzlsJson.asmx/getsampleInfo',
    //新版苏州样品信息接口
    'szypxx_url'=>$ip_sz.'WSforSzlsJson.asmx/getsampleInfo',
    //新版泰州样品信息接口
    'tzypxx_url'=>$ip_tz.'WSforSzlsJson.asmx/getsampleInfo',
    //新版徐州样品信息接口
    'xzypxx_url'=>$ip_xz.'WSforSzlsJson.asmx/getsampleInfo',



    //新版南京各工位任务接口
    'njrw_url'=>$ip_nj.'WSforSzlsJson.asmx/getNodeSampleInfo',

    //新版苏州各工位任务接口
    'szrw_url'=>$ip_nj.'WSforSzlsJson.asmx/getNodeSampleInfo',

    //新版泰州各工位任务接口
    'tzrw_url'=>$ip_nj.'WSforSzlsJson.asmx/getNodeSampleInfo',

    //新版徐州各工位任务接口
    'xzrw_url'=>$ip_nj.'WSforSzlsJson.asmx/getNodeSampleInfo',



    //新版南京下单接口
    'njxd_url'=>$ip_nj.'WSforSzlsJson.asmx/uploadTask',
    //新版苏州下单接口
    'szxd_url'=>$ip_sz.'WSforSzlsJson.asmx/uploadTask',

);




