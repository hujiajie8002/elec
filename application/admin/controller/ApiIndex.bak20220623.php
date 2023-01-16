<?php
namespace app\admin\controller;

use think\Config;
use think\Db;
use think\Controller;

use think\Request;
use think\Exception;
use think\Log;
use function fast\e;

class ApiIndex extends Controller
{
    protected $noNeedLogin = [
        'searchStationInfo',
        'getSampleinfo',
        'getReport',
        'saveProcessData',
        'getStationInfo',
        'getProcessData',
        'getModuleInfo',
        'getAllModule',
        'saveWarning',
        'sendMission',
        'getStationStatus',
        'crontabDelete',
        'analyseOvertime',
        'getDigitalId'
    ];

    public  $district_reflection = [
        '南京'=>1,
        '苏州'=>2,
        '泰州'=>3,
        '徐州'=>4,
    ];
    public  $name_reflection = [
        '省中心（电科院）'=>1,
        '苏中分中心'=>2,
        '苏南分中心'=>3,
        '苏北分中心'=>4,
    ];

    //查询本年度检测量设备类
    public $device_list = [
        '配电变压器',
        '隔离开关（35kV及以下）',
        '电流互感器（10kV~35kV）',
        '电压互感器（10kV~35kV）',
        '避雷器（10kV~35kV）',
        '高压开关柜',
        '消弧线圈',
        '电力电容器',
        '箱式变电站',
        '环网柜',
        '低压开关柜',
        '低压综合配电箱（JP柜）',
        '柱上开关设备',
        '电缆分支箱（10kV~35kV）',
        '电缆分支箱（0.4kV）',
        '电能计量箱'
    ];

    //查询本年度检测量材料类
    public  $material_list = [
        '电力电缆',
        '低压电力电缆（1kV）',
        '电缆保护管',
        '架空绝缘导线（1kV）',
        '架空绝缘导线（10kV）',
        '角钢塔',
        '钢管塔（杆）',
        '导、地线',
        '线路金具',
        '线路绝缘子',
        '变电站支柱绝缘子（10kV~750kV）',
        '环形混凝土电杆'
    ];


    public function index()
    {
        return '请在此url地址后加上/你具体的请求地址<br>例如：http://119.23.226.108:81/elec/public/index.php/api/index/getStationInfo';
        return '<style type="text/css">*{ padding: 0; margin: 0; } .think_default_text{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:)</h1><p> ThinkPHP V5.1<br/><span style="font-size:30px">十年磨一剑 - 为API开发设计的高性能框架</span></p></div><script type="text/javascript" src="http://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script><script type="text/javascript" src="http://ad.topthink.com/Public/static/client.js"></script><thinkad id="ad_bd568ce7058a1091"></thinkad>';
    }

    public function hello($name = 'ThinkPHP5')
    {
        return 'hello,' . $name;
    }

    /**
     * 访问思创的实验报告api,处理完后返回给前端,并记录对应日志
     */
    public  function getReport()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST,GET,OPTIONS');
        header('Access-Control-Allow-Headers:Origin,x-requested-with,content-type,Accept');
        $test_code = input('param.s_code','','addslashes,trim,htmlspecialchars,strip_tags');
        if (!$test_code)
        {
            $arr = array(
                'code'=>0,
                'msg'=>'错误，请先输入样品二维码'
            );
            return json_encode($arr,JSON_UNESCAPED_UNICODE);
        }

        if (config('is_internet'))
        {
            $url = $this->combineUrl('report_api_url_bak');
        }else{
            $url = config('report_api_url');
        }

        $url .= $test_code;
        //$res = file_get_contents($url);
        $res = $this->getUrl($url);
        /*存储访问思创报告接口的log*/
        $array = array(
          'testcode' => $test_code,
          'result' => $res,
        );
         Db::table('report')->insert($array);
        //如果返回值中不含有<,代表无有效信息返回
        if (strpos($res,'<') !== false)
        {
            //禁止引用外部xml实体
            libxml_disable_entity_loader(true);
            //return json_encode(simplexml_load_string($res),JSON_UNESCAPED_UNICODE);
            $result = json_decode(json_encode(simplexml_load_string($res)), true);
            //return json_encode(json_decode($result[0]));
            $arr = json_decode($result[0],true);
            //处理返回结果，包括字段的转化等，例如思创返回的是15s，前端要显示的是15s绝缘电阻值
            $ret = $this->processResultData($arr);
            return $ret;
            //echo '<pre>';
            //print_r($ret);
        }else{
            $res = array(
                'code'=>1,
                'msg'=>'此样品编号无信息',
                'data' => []
            );
            return json_encode($res,JSON_UNESCAPED_UNICODE);
        }

    }

    //处理结果数据
    public function processResultData($arr=[])
    {
        $res = array(
            'code'=>1,
            'msg'=>'success'
        );


        //样品类型
        $type = isset($arr['样品信息'][0]['样品名称']) ? $arr['样品信息'][0]['样品名称'] : '';
        $res['type'] = $type;
        switch ($type) {
            case '配电变压器':
                //获取数组下标
                $index = $this->getTrueIndex($arr,'绕组绝缘电阻试验');
                if ($index !== false)
                {
                    for ($i=0;$i<count($arr[$index]);$i++)
                    {
                        $res['data']['绕组绝缘电阻试验'][] = array(
                            '15S绝缘电阻值' => isset($arr[$index][$i]['_15S']) ? $arr[$index][$i]['_15S'] : '' ,
                            '30S绝缘电阻值' => isset($arr[$index][$i]['_30S']) ? $arr[$index][$i]['_30S'] : '' ,
                            '45S绝缘电阻值' => isset($arr[$index][$i]['_45S']) ? $arr[$index][$i]['_45S'] : '' ,
                            '1Min绝缘电阻值' => isset($arr[$index][$i]['_1Min']) ? $arr[$index][$i]['_1Min'] : '' ,
                            '10Min绝缘电阻值' => isset($arr[$index][$i]['_10Min']) ? $arr[$index][$i]['_10Min'] : '' ,
                        );
                    }
                }

                $experiments = array(
                    '感应耐压试验',
                    '电压比测量和联结组标号检定试验',
                    '外施耐压试验',
                    '空载电流及空载损耗试验',
                    '短路阻抗及负载损耗试验',
                    '电容量及介质损耗试验',
                );

                foreach ($experiments as $v)
                {
                    $index = $this->getTrueIndex($arr,$v);
                    if ( $index!== false)
                    {
                        for ($i=0;$i<count($arr[$index]);$i++)
                        {
                            $res['data'][$v][] = isset($arr[$index][$i]) ? $arr[$index][$i] : [] ;
                        }
                    }
                }




                 //获取数组下标
                $index = $this->getTrueIndex($arr, '绕组直流电阻测量');
//var_dump($index);exit;
                if ($index !== false)
                {
//var_dump($arr[$index]);exit;
                    $keys = array(
                        '高压侧电阻平均值', '高压侧不平衡率','低压侧电阻平均值','低压不平衡率','分接位置','直阻温度','低压相不平衡率','高压相不平衡率',
                        '是否额定分接','额定分接','分接总数'
                    );
                    for ($i=0;$i<count($arr[$index]);$i++){
                        foreach ($keys as $key)
                        {
                            $res['data']['绕组直流电阻测量'][$i][$key] = isset($arr[$index][$i][$key]) ? $arr[$index][$i][$key] : '' ;
                        }

                        $res['data']['绕组直流电阻测量'][$i]['AB相电阻'] = isset($arr[$index][$i]['AB相电阻1']) ? $arr[$index][$i]['AB相电阻1'] : '' ;
                        $res['data']['绕组直流电阻测量'][$i]['BC相电阻'] = isset($arr[$index][$i]['BC相电阻1']) ? $arr[$index][$i]['BC相电阻1'] : '' ;
                        $res['data']['绕组直流电阻测量'][$i]['CA相电阻'] = isset($arr[$index][$i]['CA相电阻1']) ? $arr[$index][$i]['CA相电阻1'] : '' ;
                        $res['data']['绕组直流电阻测量'][$i]['ab相电阻'] = isset($arr[$index][$i]['ab相电阻2']) ? $arr[$index][$i]['ab相电阻2'] : '' ;
                        $res['data']['绕组直流电阻测量'][$i]['bc相电阻'] = isset($arr[$index][$i]['bc相电阻2']) ? $arr[$index][$i]['bc相电阻2'] : '' ;
                        $res['data']['绕组直流电阻测量'][$i]['ca相电阻'] = isset($arr[$index][$i]['ca相电阻2']) ? $arr[$index][$i]['bc相电阻2'] : '' ;
                        $res['data']['绕组直流电阻测量'][$i]['a0相电阻'] = isset($arr[$index][$i]['a0相电阻2']) ? $arr[$index][$i]['a0相电阻2'] : '' ;
                        $res['data']['绕组直流电阻测量'][$i]['a0相电阻'] = isset($arr[$index][$i]['a0相电阻2']) ? $arr[$index][$i]['a0相电阻2'] : '' ;
                        $res['data']['绕组直流电阻测量'][$i]['c0相电阻'] = isset($arr[$index][$i]['c0相电阻2']) ? $arr[$index][$i]['c0相电阻2'] : '' ;
                    }
                }




                //温升实验给的字段多余了，筛选出电科院需要的字段
                //获取数组下标
                $index = $this->getTrueIndex($arr,'温升试验');
                if ($index !== false)
                {
                    $keys = array(
                        '冷环温','冷油温','高压绕组平均温升','低压绕组平均温升','空载功率','负载功率','总功率','施加总功率','分接位置','额定电流|施加电流',
                        '额定电流|热环温','额定电流|热油温','额定电流|热油温','额定电流|出口温度','额定电流|进口温度','总损耗|施加电流','总损耗|低压侧施加电流','低压侧额定电流',
                        '高压侧额定电流','总损耗|出口温度','总损耗|进口温度','总损耗|热环温','总损耗|热油温','总损耗|顶层油温升','热态电阻|高压曲线图',
                        '热态电阻|高压侧温度曲线图','热态电阻|低压曲线图','热态电阻|低压侧温度曲线图','温度变化曲线图','总持续时间','断开电源油平均温度',
                        '曲线平终点油均温度','额定电流|顶低部油平均温度','总损耗|顶低部油平均温度','热直阻|顶低部油平均温度','热态电阻|高压侧热直阻1',
                        '热态电阻|高压侧热直阻2','热态电阻|高压侧热直阻3','热态电阻|高压侧热直阻4','热态电阻|高压侧热直阻5','热态电阻|高压侧热直阻6',
                        '热态电阻|高压侧热直阻7','热态电阻|高压侧热直阻8','热态电阻|高压侧热直阻9','热态电阻|高压侧热直阻10','热态电阻|高压侧热直阻11',
                        '热态电阻|高压侧热直阻12','热态电阻|高压侧热直阻13','热态电阻|高压侧热直阻14','热态电阻|高压侧热直阻15','热态电阻|高压侧热直阻16',
                        '热态电阻|高压侧热直阻17','热态电阻|高压侧热直阻18','热态电阻|高压侧热直阻19','热态电阻|高压侧热直阻20','热态电阻|低压侧热直阻1',
                        '热态电阻|低压侧热直阻2','热态电阻|低压侧热直阻3','热态电阻|低压侧热直阻4','热态电阻|低压侧热直阻5','热态电阻|低压侧热直阻6',
                        '热态电阻|低压侧热直阻7','热态电阻|低压侧热直阻8','热态电阻|低压侧热直阻9','热态电阻|低压侧热直阻10','热态电阻|低压侧热直阻11',
                        '热态电阻|低压侧热直阻12','热态电阻|低压侧热直阻13','热态电阻|低压侧热直阻14','热态电阻|低压侧热直阻15','热态电阻|低压侧热直阻16',
                        '热态电阻|低压侧热直阻17','热态电阻|低压侧热直阻18','热态电阻|低压侧热直阻19','热态电阻|低压侧热直阻20','热直阻|油温',
                        '热直阻|出口温度','热直阻|进口温度','断开高压绕组均温度','断开低压绕组均温度','断开高压绕组电阻','断开低压绕组电阻'
                    );
                    for ($i=0;$i<count($arr[$index]);$i++){
                        foreach ($keys as $key)
                        {
                            $res['data']['温升试验'][$i][$key] = isset($arr[$index][$i][$key]) ? $arr[$index][$i][$key] : '' ;
                        }

                        $res['data']['温升试验'][$i]['冷直阻BC'] = isset($arr[$index][$i]['冷直阻BC1']) ? $arr[$index][$i]['冷直阻BC1'] : '' ;
                        $res['data']['温升试验'][$i]['冷直阻bc'] = isset($arr[$index][$i]['冷直阻bc2']) ? $arr[$index][$i]['冷直阻bc2'] : '' ;
                        $res['data']['温升试验'][$i]['热直阻BC'] = isset($arr[$index][$i]['热直阻BC1']) ? $arr[$index][$i]['热直阻BC1'] : '' ;
                        $res['data']['温升试验'][$i]['热直阻bc'] = isset($arr[$index][$i]['热直阻bc2']) ? $arr[$index][$i]['热直阻bc2'] : '' ;
                    }
                }


                break;
            case '避雷器':
                //获取数组下标
                $index = $this->getTrueIndex($arr,'避雷器直流参考电压试验及0.75倍直流参考电压下漏电流试验');
                if ($index !== false)
                {
                    for ($i=0;$i<count($arr[$index]);$i++){
                        $res['data']['避雷器直流参考电压试验及0.75倍直流参考电压下漏电流试验'][] = array(
                            '1mA电压' => isset($arr[$index][$i]['_1mA电压']) ? $arr[$index][$i]['_1mA电压'] : '' ,
                            '0.75mA电流' => isset($arr[$index][$i]['_0.75mA电流']) ? $arr[$index][$i]['_0.75mA电流'] : '' ,
                            '0.75mA电压' => isset($arr[$index][$i]['_0.75mA电压']) ? $arr[$index][$i]['_0.75mA电压'] : '' ,
                            '避雷器序号' => isset($arr[$index][$i]['避雷器序号']) ? $arr[$index][$i]['避雷器序号'] : '' ,
                        );
                    }
                }

                //获取数组下标
                $index = $this->getTrueIndex($arr,'持续电流试验及工频参考电压试验');
                if ($index !== false)
                {
                    for ($i=0;$i<count($arr[$index]);$i++){
                        $res['data']['持续电流试验及工频参考电压试验'][] = array(
                            '持续电流(Ir)' => isset($arr[$index][$i]['持续电流(Ir)']) ? $arr[$index][$i]['持续电流(Ir)'] : '' ,
                            '持续电流(Ix)' => isset($arr[$index][$i]['持续电流(Ix)']) ? $arr[$index][$i]['持续电流(Ix)'] : '' ,
                            '1mA下参考电压' => isset($arr[$index][$i]['_1mA下参考电压']) ? $arr[$index][$i]['_1mA下参考电压'] : '' ,
                            '避雷器序号' => isset($arr[$index][$i]['避雷器序号']) ? $arr[$index][$i]['避雷器序号'] : '' ,
                            '阻性电流基波峰值' => isset($arr[$index][$i]['阻性电流基波峰值']) ? $arr[$index][$i]['阻性电流基波峰值'] : '' ,
                            '角度' => isset($arr[$index][$i]['角度']) ? $arr[$index][$i]['角度'] : '' ,
                        );
                    }
                }

                //获取数组下标
                $index = $this->getTrueIndex($arr,'避雷器直流参考电压试验及0.75倍直流参考电压下漏电流试验(密封后)');
                if ($index !== false)
                {
                    for ($i=0;$i<count($arr[$index]);$i++){
                        $res['data']['密封试验'][] = array(
                            '1mA电压' => isset($arr[$index][$i]['_1mA电压']) ? $arr[$index][$i]['_1mA电压'] : '' ,
                            '1mA电压(密封前)' => isset($arr[$index][$i]['_1mA电压(密封前)']) ? $arr[$index][$i]['_1mA电压(密封前)'] : '' ,
                            '1mA电压变化率' => isset($arr[$index][$i]['_1mA电压变化率']) ? $arr[$index][$i]['_1mA电压变化率'] : '' ,
                            '0.75mA电流' => isset($arr[$index][$i]['_0.75mA电流']) ? $arr[$index][$i]['_0.75mA电流'] : '' ,
                            '0.75mA电流(密封前)' => isset($arr[$index][$i]['_0.75mA电流(密封前)']) ? $arr[$index][$i]['_0.75mA电流(密封前)'] : '' ,
                            '0.75mA电流变化量' => isset($arr[$index][$i]['_0.75mA电流变化量']) ? $arr[$index][$i]['_0.75mA电流变化量'] : '' ,
                            '0.75mA电压' => isset($arr[$index][$i]['_0.75mA电压']) ? $arr[$index][$i]['_0.75mA电压'] : '' ,
                            '0.75mA电压(密封前)' => isset($arr[$index][$i]['_0.75mA电压(密封前)']) ? $arr[$index][$i]['_0.75mA电压(密封前)'] : '' ,
                            '0.75mA电压变化率' => isset($arr[$index][$i]['_0.75mA电压变化率']) ? $arr[$index][$i]['_0.75mA电压变化率'] : '' ,
                            '避雷器序号' => isset($arr[$index][$i]['避雷器序号']) ? $arr[$index][$i]['避雷器序号'] : '' ,
                        );
                    }
                }

                break;
            case '断路器':
                $experiments = array(
                    '主回路交流耐压试验',
                    '主回路电阻测量（温升前）',
                    '主回路电阻测量（温升后）',
                    '主回路交流耐压试验',
                );

                foreach ($experiments as $v)
                {
                    $index = $this->getTrueIndex($arr,$v);
                    if ( $index!== false)
                    {
                        for ($i=0;$i<count($arr[$index]);$i++){
                            $res['data'][$v][] = isset($arr[$index][$i]) ? $arr[$index][$i] : [] ;
                        }
                    }
                }

                //获取数组下标
                $index = $this->getTrueIndex($arr,'机械操作试验');
                if ($index !== false)
                {
                    for ($i=0;$i<count($arr[$index]);$i++){
                        $res['data']['机械操作'][$i] =isset($arr[$index][$i]) ? $arr[$index][$i] : [] ;
                        $res['data']['断路器机械试验'][$i]['操作次数'] = isset($arr[$index][$i]['试验次数']) ? $arr[$index][$i]['试验次数'] : '' ;
                        $res['data']['断路器机械试验'][$i]['是否正常'] = isset($arr[$index][$i]['失败次数']) && $arr[$index][$i]['失败次数'] <=0 ? '正常' : '不正常' ;
                    }
                }

                break;
            default:
                $res = array(
                    'type'=>$type,
                    'data'=>[],
                );

                //$arr = ['mdn试验'=>[['a','c','d'=>'dddd']],'aa'=>'','mdn试验1'=>[],'mdn试验结果'=>[['a','c','d'=>'dddd']]];
                //查找到所有试验，原样返回
                $keys = array_keys($arr);
                foreach ($keys as $v)
                {
                    if (strpos($v,'试验') !== false &&  strpos($v,'结果') === false)
                    {
                        for ($i=0;$i<count($arr[$v]);$i++){
                            $res['data'][$v][] = isset($arr[$v][$i]) ? $arr[$v][$i] : 0;
                        }
                    }
                }

                break;
        }

        //给试验数据加上单位
        $res = $this->appendUnit($res);
        return json_encode($res,JSON_UNESCAPED_UNICODE);

    }


    /**给试验数据加上单位
     * @param $res
     */
    public function appendUnit($res)
    {
        //单位集合，最终在此数组中获取集合
        //array(4) {
        //  ["拐点电压"]=>
        //  string(1) "V"
        //  ["拐点电流"]=>
        //  string(1) "A"
        //  ["最大电压"]=>
        //  string(1) "V"
        //  ["最大电流"]=>
        //  string(1) "A"
        //   }
        $units = [];
        $units = Db::table('units')->column('name,valueunit');

        if (isset($res['data']) && $res['data'])
        {
            foreach ($res['data'] as $k => $v)
            {
                if ($v)
                {
                    foreach ($v as $kk=>$vv)
                    {
                        foreach ($vv as $kkk=>$vvv){
                            $res['data'][$k][$kk][$kkk] .= '--';
                            $res['data'][$k][$kk][$kkk] .= isset($units[$kkk]) ? $units[$kkk] : '';
                        }
                    }
                }

            }
        }
        return $res;
    }

    /**
     * 模糊查询二维数组中包含某字符的键名
     * @param $arr
     * @param $str
     * @return false|mixed
     */
    public function getTrueIndex($arr, $str)
    {
        $keys = array_keys($arr);
        foreach ($keys as $v)
        {
            if(strpos($v, $str) !== false){
                return $v;
            }
        }
        return false;
    }

    function getUrl($url){
        //$headerArray =array("Content-type:application/json;","Accept:application/json");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //设为TRUE把curl_exec()结果转化为字串，而不是直接输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch,CURLOPT_HTTPHEADER,$headerArray);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }


    public function posturl($url,$data){
        $data  = json_encode($data);
        $headerArray =array("Content-type:application/json;charset='utf-8'","Accept:application/json");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl,CURLOPT_HTTPHEADER,$headerArray);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return json_decode($output,true);
    }


    public function puturl($url,$data){
        $data = json_encode($data);
        $ch = curl_init(); //初始化CURL句柄
        curl_setopt($ch, CURLOPT_URL, $url); //设置请求的URL
        curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); //设为TRUE把curl_exec()结果转化为字串，而不是直接输出
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"PUT"); //设置请求方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//设置提交的字符串
        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output,true);
    }

    public function delurl($url,$data){
        $data  = json_encode($data);
        $ch = curl_init();
        curl_setopt ($ch,CURLOPT_URL,$url);
        curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output,true);
    }

    public function patchurl($url,$data){
        $data  = json_encode($data);
        $ch = curl_init();
        curl_setopt ($ch,CURLOPT_URL,$url);
        curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);     //20170611修改接口，用/id的方式传递，直接写在url中了
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output);
        return $output;
    }

    public function canal()
    {
        $a = '<?xml version="1.0" encoding="utf-8"?>
<string xmlns="http://tempuri.org/">{"样品信息":[{"试品编号":"byq0001","样品名称":"油浸式变压器","样品描述":"","批次":"","型号规格":"","任务编号":"","报告编号":"","盲样编号":"","委托单位":"","样品编号":"byq0001","生产厂商":"","试验人员":"","检测日期":"2020-08-29T20:14:09"}],"参数信息":[{"高压侧低分接电压":"10.50","高压侧额定电压":"10","高压侧高分接电压":"9.50","变压器联结方式":"D/yn","环温探头":"4","高压侧额定电流":"23.1","低压侧额定电压":"400","低压侧额定电流":"577.4","阻抗电压":"3.98","额定容量":"400","额定频率":"50","额定变比":"25","分接间距":"2.5","分接范围":"±2×2.5","组别号":"11","分接总数":"5","测试分接":"3","相数":"三相","冷却方式":"ONAN(油浸式)","高压侧试验电流":"自动","低压侧试验电流":"自动","负载测量分接":"3","最高温升":"75","油温探头":"1","高压侧顶部探头":"5","高压侧底部探头":"6","绕组探头":"9;10","铁芯探头":"7","套管探头":"8","低对高及地":"5","高对低及地":"10","持续时间":"60","变压器类型":"非晶","绝缘等级":"A","噪音水平":"60","托盘高度":"200","变压器高度":"832","高压测套管高度":"245","低压测套管高度":"126","高压测螺柱高度":"30","低压测螺柱高度":"43"}],"检验标准":[],"检验标准2":[],"检测仪器":[],"绕组绝缘电阻试验":[],"绕组绝缘电阻试验结果":[{"任务号":null,"试验状态":"","试验日期":null,"油温":null,"环境温度":null,"相对湿度":null}],"外施耐压试验":[],"外施耐压试验结果":[{"任务号":null,"试验状态":"","试验日期":null,"油温":null,"环境温度":null,"相对湿度":null}],"感应耐压试验":[],"感应耐压试验结果":[{"任务号":null,"试验状态":"","试验日期":null,"油温":null,"环境温度":null,"相对湿度":null}],"绕组直流电阻试验":[],"绕组直流电阻试验结果":[{"任务号":null,"试验状态":"","试验日期":null,"油温":null,"环境温度":null,"相对湿度":null}],"电压比测量及联结组标号检定试验":[],"电压比测量及联结组标号检定试验结果":[{"任务号":null,"试验状态":"","试验日期":null,"油温":null,"环境温度":null,"相对湿度":null}],"空载电流及空载损耗试验":[],"空载电流及空载损耗试验结果":[{"任务号":null,"试验状态":"","试验日期":null,"油温":null,"环境温度":null,"相对湿度":null}],"在90%和110额定电压下的空载损耗和空载电流测量":[],"在90%和110额定电压下的空载损耗和空载电流测量结果":[{"任务号":null,"试验状态":"","试验日期":null,"油温":null,"环境温度":null,"相对湿度":null}],"声级试验":[],"声级试验结果":[{"任务号":null,"试验状态":"","试验日期":null,"油温":null,"环境温度":null,"相对湿度":null}],"短路阻抗及负载损耗试验":[],"短路阻抗及负载损耗试验结果":[{"任务号":null,"试验状态":"","试验日期":null,"油温":null,"环境温度":null,"相对湿度":null}],"油变温升试验":[],"油变温升试验结果":[{"任务号":null,"试验状态":"","试验日期":null,"油温":null,"环境温度":null,"相对湿度":null}],"短时过负载试验":[],"短时过负载试验结果":[{"任务号":null,"试验状态":"","试验日期":null,"油温":null,"环境温度":null,"相对湿度":null}],"生成报告":[{"URL":"http://192.168.18.3/rxjc_web/page/report.aspx?testcode=byq0001&amp;mrtfile=油浸式变压器&amp;request=0"}]}</string>';
        $a = str_repeat($a,10);
        $sql = "insert into report(testcode,result) values('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abc','{$a}'),('abcddd','{$a}')";
        //$sql = "update report set result='best' where id>1180";
        //$sql = "delete from  report where id>1136";
        Db::table('report')->query($sql);
    }

    public function saveProcessData()
    {
        $ret = array(
          'code'=>1,
          'msg'=>'success'
        );
        //样品二维码
        $sample_barcode = input('param.s_code','','addslashes,trim,htmlspecialchars,strip_tags');
        // 实验名称
        $experiment_name = input('param.e_name','','addslashes,trim,htmlspecialchars,strip_tags');
        $date = input('param.date','1996-12-30 00:00:00','addslashes,trim,htmlspecialchars,strip_tags');
        $json = input('param.json','{}','trim');
        $type = input('param.type','','addslashes,trim,htmlspecialchars,strip_tags');
        $jcjg = input('param.jcjg','','addslashes,trim,htmlspecialchars,strip_tags');
        $mission_no = input('param.mission_no','','addslashes,trim,htmlspecialchars,strip_tags');
        $station = input('param.station','','addslashes,trim,htmlspecialchars,strip_tags');

        $ip = $this->getIpAddress();
        if (!$sample_barcode || !$experiment_name || $json == '{}' || !$json || !$type || !$jcjg || !$mission_no || !$station)
        {
            $ret['code'] = 0;
            $ret['msg'] = '关键参数不能为空';
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
        }
        $array = array(
            'sample_barcode' => $sample_barcode,
            'experiment_name' => $experiment_name,
            'date' => $date,
            'json' => $json,
            'type' => $type,
            'mission_no' => $mission_no,
            'jcjg' => $jcjg,
            'station' => $station,
            'ip' => $ip,
        );

        //限制提交间隔
        $interval = $this->getInterval('process_data');
        //提交间隔过短，不插入
        if ($interval < config('process_data_interval'))
        {
            $ret['code'] = 666;
            $ret['msg'] = 'success';
            return json_encode($ret, JSON_UNESCAPED_UNICODE);
        }

        $res = Db::table('process_data')->insert($array);
        if (!$res)
        {
            $ret['code'] = 0;
            $ret['msg'] = '插入失败，请稍后再试';
        }


        //创建redis连接
        $redis = new \Redis();
        $redis->connect(config('redis.ip'),config('redis.port'));
        $redis->auth(config('redis.fh'));

        // 总功率阶段，0.5小时监测一次总损耗测量值，以样品二维码+阶段为list键名，同时设置一个30分钟过期的string，命名为二维码_阶段_has_recorded
        //如果不存在flag则新增并记录当时的总损耗，存入到list中，如果存在则pass
        // 额定电流阶段，1小时监测一次油温，以样品二维码+阶段为list键名，同时设置一个60分钟过期的string

        if (strpos($type,'配电变压器') !== false && $experiment_name == '温升试验')
        {
            $json_arr = json_decode($json,true)[0];
            $period = $json_arr['阶段名称'];
            if ($period == '总功率')
            {
                //记录总功率阶段的总功率
                //判断是否存在 '已经记录过，无需再记录' flag
                $if_exist = $redis->exists($sample_barcode.'_zgl_has_recorded');

                if (!$if_exist)
                {
                    //记录总损耗测量值，即总功率
                    if (isset($json_arr['总功率']))
                    {
                        $zgl = $json_arr['总功率'];
                        $redis->rPush($sample_barcode.'_zgl',$zgl);
                        $redis->setex($sample_barcode.'_zgl_has_recorded',1800,'ok');
                    }else{
                        $array = array(
                            'msg'=>'配电变压器温升试验总功率阶段所传字段缺少参数：总功率',
                            'type'=>'试验质量服务缺少字段',
                            'url'=>'',
                        );
                        Db::table('log')->insert($array);
                    }
                }

                //记录总功率阶段的油温
                //判断是否存在 '已经记录过，无需再记录' flag
                $if_exist = $redis->exists($sample_barcode.'_zgl_has_recorded_yw');
                if (!$if_exist)
                {
                    //记录油温
                    if (isset($json_arr['油温1']))
                    {
                        $yw = $json_arr['油温1'];
                        $redis->rPush($sample_barcode.'_zgl_yw',$yw);
                        $redis->setex($sample_barcode.'_zgl_has_recorded_yw',3600,'ok');
                    }else{
                        $array = array(
                            'msg'=>'配电变压器温升试验总功率阶段所传字段缺少参数：总功率',
                            'type'=>'试验质量服务缺少字段',
                            'url'=>'',
                        );
                        Db::table('log')->insert($array);
                    }
                }

            }elseif ($period == '额定电流'){
                //检测电流大小
                //记录试验时间
                if (isset($json_arr['电流']))
                {
                    $dl = $json_arr['电流'];
                    $redis->rPush($sample_barcode.'_eddl_dl',$dl);
                }else{
                    $array = array(
                        'msg'=>'配电变压器温升试验额定电流阶段所传字段缺少参数：电流',
                        'type'=>'试验质量服务缺少字段',
                        'url'=>'',
                    );
                    Db::table('log')->insert($array);
                }


                //记录试验时间
                if (isset($json_arr['试验时间']))
                {
                    $zgl = $json_arr['试验时间'];
                    $redis->rPush($sample_barcode.'_eddl_sj',$zgl);
                    $redis->setex($sample_barcode.'_eddl_sj_has_recorded',3600,'ok');
                }else{
                    $array = array(
                        'msg'=>'配电变压器温升试验额定电流阶段所传字段缺少参数：试验时间',
                        'type'=>'试验质量服务缺少字段',
                        'url'=>'',
                    );
                    Db::table('log')->insert($array);
                }
            }
        }


        if (in_array($type,['隔离开关（35kV及以下）','高压开关柜','环网柜','柱上开关设备','电缆分支箱（10kV~35kV）']) && $experiment_name === '温升试验')
        {
            $json_arr = json_decode($json,true);
            //记录各部位的温度值
            //判断是否存在 '已经记录过，无需再记录' flag
            $if_exist = $redis->exists($sample_barcode.'_has_recorded');
            if (!$if_exist)
            {
               foreach ($json_arr as $v){
                   //记录温度
                   if (isset($v['温度值']))
                   {
                       if (isset($v['部位'])){
                           $redis->rPush($sample_barcode.'_wdz',json_encode($v));
                           $redis->setex($sample_barcode.'_has_recorded',3600,'ok');
                       }else{
                           $array = array(
                               'msg'=>$type.'温升试验所传字段缺少参数：部位',
                               'type'=>'试验质量服务缺少字段',
                               'url'=>'',
                           );
                           Db::table('log')->insert($array);
                       }
                   }else{
                       $array = array(
                           'msg'=>$type.'温升试验所传字段缺少参数：温度值',
                           'type'=>'试验质量服务缺少字段',
                           'url'=>'',
                       );
                       Db::table('log')->insert($array);
                   }
               }
            }
        }


        return json_encode($ret, JSON_UNESCAPED_UNICODE);
    }

    public function getInterval($table_name)
    {
        $newest_record = Db::table($table_name)->order('id desc')->find();
        //最新一条记录的插入时间
        $create_at = $newest_record['create_at'];
        $interval = time() - strtotime($create_at);
        return $interval;

    }

    public function getIpAddress(){
        //ip是否来自共享互联网
        if (!empty($_SERVER['HTTP_CLIENT_IP'])){
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        }
        //ip是否来自代理
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        //ip是否来自远程地址
        else{
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }
        return $ip_address;
    }


    //查找工位信息接口
    public  function searchStationInfo()
    {

        $ret = array(
            'code' => 1,
            'msg' => 'success',
            'data' => [],
        );



        //工位id,例如A4
        $station_id = input('param.station_id','','addslashes,trim,htmlspecialchars,strip_tags');
        //区域
        $district = input('param.district','','addslashes,trim,htmlspecialchars,strip_tags');
        if (!$station_id || !$district)
        {
            $ret['code'] = 0;
            $ret['msg'] = '请输入正确的工位号和地区';
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
        }
        //工位状态api的url地址
        if (trim($district) == '南京'){
            if (config('is_internet'))
            {
                $station_status_url = $this->combineUrl('station_status_url_bak_nj');
            }else{
                $station_status_url = config('station_status_url_nj');
            }
        }elseif (trim($district) == '苏州'){
            if (config('is_internet'))
            {
                $station_status_url = $this->combineUrl('station_status_url_bak_sz');
            }else{
                $station_status_url = config('station_status_url_sz');
            }
        }



        $res = $this->getUrl($station_status_url);
        $array = array(
            'msg'=>$res,
            'type'=>'searchStationInfo访问工位接口'
        );
        Db::table('log')->insert($array);
        //如果返回值中不含有<,代表无有效信息返回
        if (strpos($res,'<') !== false)
        {
            //禁止引用外部xml实体
            libxml_disable_entity_loader(true);
            //return json_encode(simplexml_load_string($res),JSON_UNESCAPED_UNICODE);
            $result = json_decode(json_encode(simplexml_load_string($res)), true);
            //return json_encode(json_decode($result[0]));
            //echo '<pre>';
            //print_r(json_decode($result[0],true));
            $array = json_decode($result[0],true);
            //$array['任务状态'][] = ['二维码'=>'S2020121814331003','状态'=>'mdn ing','开始日期'=>'2020','结束日期'=>'2012'];
            //echo '<pre>';
            //var_dump($array);
            //处理思创的工位接口数据，给自己前端调用
            $a = $this->processStationInfo($array, $station_id);
            if($a == '无相关工位的信息'){
                $ret['code'] = 0;
                $ret['msg'] = '无相关工位的信息';
                return json_encode($ret,JSON_UNESCAPED_UNICODE);
            }



            $ret['data'] = $a;
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
        }else{
            $ret['code'] = 0;
            $ret['msg'] = '思创api1无正确返回值';
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
        }
    }

    //处理思创的工位接口数据，给自己前端调用
    public function processStationInfo($arr, $station_id)
    {
        //获取包含所有工位编号的一维数组
        $stations = array_column($arr['工位状态'],'workstation');
        //查找
        $index = array_search($station_id, $stations);
        if ($index === false)
        {
            return  '无相关工位的信息';
        }
        //工位状态
        $station_state = $arr['工位状态'][$index]['state'];
        //实验名称
        $experiment = $arr['工位状态'][$index]['experimentname'];
        //样品二维码
        $sample_barcode = $arr['工位状态'][$index]['samplebarcode'];
        //样品名称
        $sample_name = $arr['工位状态'][$index]['samplename'];
        //温度
        $temperature = $arr['工位状态'][$index]['wd'];
        //湿度
        $humidity = $arr['工位状态'][$index]['sd'];
        //在实验状态中查找本样品正在进行的实验的信息
        $sample_barcodes = array_column($arr['任务状态'],'二维码');
        $exp_index = array_search($sample_barcode, $sample_barcodes);
        if ($exp_index === false)
        {
            //实验状态
            $exp_state = '无此实验的状态信息';
            $exp_start = '';
            //实验结束日期
            $exp_end ='';
        }else{
            //实验状态
            $exp_state = $arr['任务状态'][$exp_index]['状态'];
            //实验开始日期
            $exp_start = $arr['任务状态'][$exp_index]['开始日期'];
            //实验结束日期
            $exp_end = $arr['任务状态'][$exp_index]['结束日期'];
        }

        //当日实验总数
        //待测数量
        $statistic = $arr['当日状态'];
        $total = 0;
        $num_to_test = 0;
        if ($statistic)
        {
            foreach ($statistic as $v)
            {
                $total += $v['完成数量'];
                $num_to_test += $v['下单数量'] - $v['完成数量'];
            }
        }

        $data = array(
            'station_id' => $station_id,
            'station_state' => $station_state,
            'experiment' => $experiment,
            'sample_barcode' => $sample_barcode,
            'sample_name' => $sample_name,
            'temperature' => $temperature,
            'humidity' => $humidity,
            'exp_state' => $exp_state,
            'exp_start' => $exp_start,
            'exp_end' => $exp_end,
            'total' => $total,
            'num_to_test' => $num_to_test,
        );
        return $data;

    }

    //返回所有的工位以及工位上的样品名称
    public function getStationInfo()
    {
        $ret = array(
            'code' => 1,
            'msg' => 'success',
            'data' => [],
        );
        //工位状态api的url地址

        if (config('is_internet'))
        {
            $station_status_url = $this->combineUrl('station_status_url_bak');
        }else{
            $station_status_url = config('station_status_url');
        }
        $res = $this->getUrl($station_status_url);
        $array = array(
            'msg'=>$res,
            'type'=>'getStationInfo访问工位接口'
        );
        Db::table('log')->insert($array);
        //如果返回值中不含有<,代表无有效信息返回
        if (strpos($res,'<') !== false)
        {
            //禁止引用外部xml实体
            libxml_disable_entity_loader(true);
            $result = json_decode(json_encode(simplexml_load_string($res)), true);
            $array = json_decode($result[0],true);
            //echo '<pre>';
            //var_dump($array);
            if (isset($array['工位状态'])){
                foreach ($array['工位状态'] as $v)
                {
                    $ret['data'][] = ['station_id'=>$v['workstation'],
                        'samplename'=>$v['samplename'],
                        'state'=>$v['state'],
                        'samplecode'=>$v['samplebarcode'],
                    ];
                }
            }
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
        }else{
            $ret['code'] = 0;
            $ret['msg'] = '思创api2无正确返回值';
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
        }
    }


    //前端获取过程数据
    public function getProcessData()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST,GET,OPTIONS');
        header('Access-Control-Allow-Headers:Origin,x-requested-with,content-type,Accept');
        $ret = array(
          'code'=>1,
          'msg'=>'success',
          'data'=>[]
        );



        $s_code = input('param.s_code','','addslashes,trim,htmlspecialchars,strip_tags');
        $e_name = input('param.e_name','','addslashes,trim,htmlspecialchars,strip_tags');
        if (!$s_code || !$e_name)
        {
            $ret['code'] = 0;
            $ret['msg'] = '请输入正确的样品二维码和实验名称';
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
        }



        $process_data = Db::table('process_data')
            ->where('sample_barcode',$s_code)
            ->where('experiment_name',$e_name)
            ->order('create_at desc')
            ->limit('0,1')
            ->value('json');

        //去除插入数据库时addslashes,trim,htmlspecialchars,strip_tags增加的转义符/
        //TODO 若最终过程数据外面套了一层数组，则此处要改为[][0]
        $ret['data'] = json_decode(stripslashes($process_data),true)[0];


        //進行排序
        if ($ret['data'])
        {
            ksort($ret['data']);
        }
        return json_encode($ret,JSON_UNESCAPED_UNICODE);

    }

    public function simulate()
    {
        return '此功能暂未开放，请联系管理员';
        $array = array(
            'sample_barcode' => 'code1',
            'experiment_name' => '核聚变实验',
            'json'=>'{"A相电压":"399.04","B相电压":"400.75","C相电压":"398.17","施加平均电压":"399.32","A相平均电压":"398.19","B相平均电压":"400.56","C相平均电压":"397.08","施加方均根电压":"398.61","A相电流":"3.9152","B相电流":"2.8212","C相电流":"3.4025","施加平均电流":"3.38","A相有功":"466.6","B相有功":"311.03","C相有功":"565.9","空载损耗测量值":"1343.53","A相无功":"755.14","B相无功":"550.91","C相无功":"509.23","频率":"49.99","空载电流":"0.3742","空载校正电流":"3.40","试验温度":"7.6","空载损耗":"1350.4","标准空载损耗":"{标准$空载损耗}","标准空载电流":"{标准$空载电流}","标准施加电压":"400"}',
            'date'=>date('Y-m-d H:i:s')
        );
        //$arrays = array_fill(0,1000,$array);

        while(true){
            $res = Db::table('process_data')->insert($array);
            //sleep(1);

        };

    }


    //定时删除process_data表中过期数据,暂定保留50w条,每天0点启动
    public function crontabDelete()
    {
        set_time_limit(0);
        $rows_preserved = config('rows');
        $sql = "DELETE tb FROM process_data AS tb ,(SELECT id FROM process_data ORDER BY id desc LIMIT {$rows_preserved},1) AS tmp
WHERE tb.id<tmp.id;";
        $res = Db::table('process_data')->query($sql);
        $sql_optimize = 'optimize table process_data';
        $res = Db::table('process_data')->query($sql_optimize);
        $array = array(
            'msg'=>'删除多余过程数据成功',
            'type'=>'delete'
        );
        Db::table('log')->insert($array);
        return json_encode($res,320);
    }

    
    
    //定时分析超期任务信息，每天1点启动
    public function analyseOvertime()
    {
        set_time_limit(0);

        //区分未完成和已经完成的，未完成的自己计算超期时长，已完成的使用质控的超期时长，那个更加准确

        //未完成的
        $res = Db::table('dky_mission')
            ->field('distribute_time,overtime_norm,twins_token,testing_institution,device_type,id')
            ->where('finish_time','1970-01-01 00:00:00')
            ->select();



        //所有有逾期问题的数字孪生id的数组集合，用来检测是否存在该问题
        $twins_token_arr = Db::table('dky_testing_problem')
            ->where('problem_type','逾期')
            ->column('twins_token');


        foreach ($res as $v){
            $distribute_time = $v['distribute_time'];
            $overtime_norm = $v['overtime_norm'];
            //deadline的时间戳
            $deadline = strtotime($distribute_time) + 86400 * $overtime_norm;
            //超期时间
            $overtime_duration = round((time() - $deadline) / 86400,2);
            //如果当前时间超过了deadline，则表示逾期了
            if ($deadline < time())
            {
                $id = $v['id'];
                //超期预警，一个任务只有一条，有则改，没有再插入
                if (in_array($v['twins_token'],$twins_token_arr)){
                    //有则修改下超期时长
                    Db::table('dky_testing_problem')
                        ->where('id',$id)
                        ->update(['overtime_duration'=>$overtime_duration]);
                }else{
                    $jcjg = $v['testing_institution'];
                    if (isset($this->name_reflection[$jcjg])){
                        $jcjg_id = $this->name_reflection[$jcjg];
                    }else{
                        $jcjg_id = 0;
                    }

                    $array = array(
                        'problem_type'=>'逾期',
                        'district_id'=>$jcjg_id,
                        'type'=>$v['device_type'],
                        'twins_token'=>$v['twins_token'],
                        'description'=>$v['device_type'].'已经逾期，超过规定时间完成检测',
                        'status'=>0,
                        'occur_at'=>date('Y-m-d H:i:s'),
                        'overtime_duration'=>$overtime_duration,
                        'createtime'=>time(),
                        'updatetime'=>time(),
                    );

                    Db::table('dky_testing_problem')->insert($array);
                }
            }
        }




        //已经完成的
        $res = Db::table('dky_mission')
            ->field('overtime_duration,testing_institution,twins_token,device_type,id')
            ->where('finish_time','<>','1970-01-01 00:00:00')
            ->where('overtime_duration','>',0)
            ->where('flag',1)
            ->select();
        foreach ($res as $v){
            //超期时间
            $overtime_duration = $v['overtime_duration'];

            $jcjg = $v['testing_institution'];
            if (isset($this->name_reflection[$jcjg])){
                $jcjg_id = $this->name_reflection[$jcjg];
            }else{
                $jcjg_id = 0;
            }

            $array = array(
                'problem_type'=>'逾期',
                'district_id'=>$jcjg_id,
                'type'=>$v['device_type'],
                'twins_token'=>$v['twins_token'],
                'description'=>$v['device_type'].'已经逾期，超过规定时间完成检测',
                'status'=>0,
                'occur_at'=>date('Y-m-d H:i:s'),
                'overtime_duration'=>$overtime_duration,
                'createtime'=>time(),
                'updatetime'=>time(),
            );

            Db::table('dky_testing_problem')->insert($array);
            //同时将任务表中的flag改为0，避免多次检查该任务
            $id = $v['id'];
            Db::table('dky_mission')->where('id',$id)
                ->update(['flag'=>0]);
            }
    }

    public function bubbleSort()
    {

        $str = "5,1,2,3,0,9,8,6,7,4";
        $arr = explode(',',$str);
        if (!$arr) echo '请输入非空数组';
        for($i=0;$i<count($arr)-1;$i++)
        {
            for($j=0;$j<count($arr)-1-$i;$j++)
            {
                //前一个数比后一个数大则交换位置，最终数组由小到大排列
                //需要由大到小排列则把>改为<号
                if ($arr[$j]>$arr[$j+1])
                {
                    $temp = $arr[$j+1];
                    $arr[$j+1] = $arr[$j];
                    $arr[$j] = $temp;
                }
            }
        }

        echo implode(',',$arr);
    }

    public function selectionSort()
    {
        $str = "5,3,2,1,0,9,8,6,7,4";
        $arr = explode(',',$str);
        for($i=0;$i<count($arr)-1;$i++)
        {
            $minIndex = $i;
            for ($j=$i+1;$j<count($arr);$j++)
            {
                if ($arr[$j]<$arr[$minIndex])
                {
                    $minIndex = $j;
                }
            }

            $temp = $arr[$i];
            $arr[$i] = $arr[$minIndex];
            $arr[$minIndex] = $temp;
            //echo implode(',',$arr).'<br/>';
        }

        echo implode(',',$arr);
    }

    public function insertSort()
    {
        //对于未排序数据，在已排序数列中从后向前扫描，找到相应位置并插入。
        $str = "5,3,2,1,0,9,8,6,7,4";
        for ($uu=0;$uu<10000;$uu++)
        {
            $str .= ','. rand(0,100);
        }
        $arr = explode(',',$str);
        if (!$arr) return 'wrong parameter';
        for ($i=0;$i<count($arr)-1;$i++)
        {
            $current = $arr[$i+1];
            $preindex = $i;
            while($preindex>=0 && $current<$arr[$preindex])
            {
                $arr[$preindex+1] = $arr[$preindex];
                $preindex--;
            }
            $arr[$preindex+1] = $current;
        }

        echo implode(',',$arr);
    }


    //获取重定向后的url
    public function getRedirectUrl($url='http://www.sceec.com/API')
    {
        $redirects = array();
        $http = stream_context_create();
        stream_context_set_params(
            $http,
            array(
                "notification" => function() use (&$redirects)
                {
                    if (func_get_arg(0) === STREAM_NOTIFY_REDIRECTED) {
                        $redirects[] = func_get_arg(2);
                    }
                }
            )
        );
        file_get_contents($url, false, $http);
        return $redirects;
    }


    public function shellSort()
    {
        $str = "9,3,2,1,0,6,8,5,7,4";
//        for ($uu=0;$uu<10000;$uu++)
//        {
//            $str .= ','. rand(0,100);
//        }

        $arr = explode(',',$str);
        $count = count($arr);
        // gap为步长，每次减为原来的一半。
        $gap = $count/2;

        while($gap>0)
        {
            //按原理是多个gap分组执行，实际操作是多个分组交替执行
            for ($i=$gap;$i<$count;$i++)
            {
                //echo 1;
                $current = $arr[$i];
                $preindex = $i - $gap;
                while($preindex >= 0 && $arr[$preindex] > $current)
                {
                    $arr[$preindex+$gap] = $arr[$preindex];
                    $preindex -= $gap;
                }
                $arr[$preindex+$gap] = $current;
            }
            $gap= floor($gap/2);
            echo implode(',',$arr).'<br>';
        }

        //echo implode(',',$arr);
    }
    //通过重定向后的ip和config中url拼接起来
    public function combineUrl($config){
        $config_url = config($config);
        $redirects = $this->getRedirectUrl(config('sceec_url'));
        $ip = explode(':',$redirects[1])[1];
        return 'http:'.$ip.$config_url;
    }


    public function getModuleInfo()
    {
        $ret = array(
          'code'=>1,
          'msg'=>'success',
          'data'=>[]
        );
        $station_id = input('param.labor','','addslashes,trim,htmlspecialchars,strip_tags');
        $modele_name = input('param.module_name','','addslashes,trim,htmlspecialchars,strip_tags');
        if (!$station_id || !$modele_name)
        {
            $ret['code'] = 0;
            $ret['msg'] = '请输入正确的工位名称和模块名称';
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
        }
        //工位状态api的url地址
        if (config('is_internet'))
        {
            $url = $this->combineUrl('module_url_bak');
        }else{
            $url = config('module_url');
        }
        $url .= '?labor='.$station_id;
        $res = $this->getUrl($url);
        $array = array(
            'msg'=>$res,
            'type'=>'getModuleInfo访问模块接口'
        );
        Db::table('log')->insert($array);
        if (strpos($res,'<') !== false)
        {
            //禁止引用外部xml实体
            libxml_disable_entity_loader(true);
            $result = json_decode(json_encode(simplexml_load_string($res)), true);
            $array = json_decode($result[0],true);
            //无对应工位信息则返回
            if (isset($array['flag']) && $array['flag'] == 'fail')
            {
                $ret['code'] = 0;
                $ret['msg'] = $array['msg'];
                return json_encode($ret,JSON_UNESCAPED_UNICODE);
            }

            foreach ($array as $k=>$v)
            {
                //var_dump($v);
                if ($v['仪器名称'] == $modele_name)
                {
                    $data = array(
                        //名称
                        'mc'=>isset($array[$k]['仪器名称']) ? $array[$k]['仪器名称'] : '' ,
                        //型号
                        'xh'=>isset($array[$k]['型号']) ? $array[$k]['型号'] : '' ,
                        //编号
                        'bh'=>isset($array[$k]['仪器编号']) ? $array[$k]['仪器编号'] : '' ,
                        //生产厂家
                        'sccj'=>isset($array[$k]['仪器生产厂家']) ? $array[$k]['仪器生产厂家'] : '' ,
                        //计量校准单位？
                        'jljzdw'=>isset($array[$k]['计量校准单位']) ? $array[$k]['计量校准单位'] : '' ,
                        //证书有效期
                        'zsyxq'=>isset($array[$k]['有效期（年）']) ? $array[$k]['有效期（年）'] : '' ,
                        //测量范围？
                        'clfw'=>isset($array[$k]['测量范围']) ? $array[$k]['测量范围'] : '' ,
                        //测量不确定度？
                        'jd'=>isset($array[$k]['测量不确定度']) ? $array[$k]['测量不确定度'] : '' ,
                    );
                    $ret['data'] = $data;
                }
            }
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
        }else{
            $ret['code'] = 0;
            $ret['msg'] = '思创api3无正确返回值';
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
        }
    }


    public function getAllModule()
    {
        $ret = array(
            'code'=>1,
            'msg'=>'success',
            'data'=>[]
        );
        $station_id = input('param.labor','','addslashes,trim,htmlspecialchars,strip_tags');
        //工位状态api的url地址
        if (config('is_internet'))
        {
            $url = $this->combineUrl('module_url_bak');
        }else{
            $url = config('module_url');
        }
        $url .= '?labor='.$station_id;
        $res = $this->getUrl($url);
        $array = array(
            'msg'=>$res,
            'type'=>'getAllModule访问模块接口'
        );
        Db::table('log')->insert($array);
        if (strpos($res,'<') !== false) {
            //禁止引用外部xml实体
            libxml_disable_entity_loader(true);
            $result = json_decode(json_encode(simplexml_load_string($res)), true);
            $array = json_decode($result[0], true);
            //无对应工位信息则返回
            if (isset($array['flag']) && $array['flag'] == 'fail') {
                $ret['code'] = 0;
                $ret['msg'] = $array['msg'];
                return json_encode($ret, JSON_UNESCAPED_UNICODE);
            }

            foreach ($array as $k=>$v)
            {
                $ret['data'][] = $v['仪器名称'];
            }
            return json_encode($ret, JSON_UNESCAPED_UNICODE);
        }else{
            $ret['code'] = 0;
            $ret['msg'] = '思创api4无正确返回值';
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
        }
    }

    public function saveWarning()
    {
        $ret = array(
            'code'=>1,
            'msg'=>'success'
        );

        $json = input('param.json','{}','addslashes,trim,htmlspecialchars,strip_tags');
        //判斷是否為正確格式的json字符串
        $temp = json_decode(stripslashes($json));
        $ip = $this->getIpAddress();
        if ($json == '{}' || !$json || $temp === null)
        {
            $ret['code'] = 0;
            $ret['msg'] = '错误，请输入正确格式的json字符串';
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
        }
        $array = array(
            'json' => $json,
            'ip' => $ip,
        );

        //限制提交间隔
        $interval = $this->getInterval('warning');
        //提交间隔过短，不插入
        if ($interval < config('warning_data_interval'))
        {
            $ret['code'] = 666;
            $ret['msg'] = 'success';
            return json_encode($ret, JSON_UNESCAPED_UNICODE);
        }

        $res = Db::table('warning')->insert($array);
        if (!$res)
        {
            $ret['code'] = 0;
            $ret['msg'] = '插入失败，请稍后再试';
        }

        return json_encode($ret, JSON_UNESCAPED_UNICODE);
    }

    //给方天调用，获取检测任务信息
    public function sendMission(Request $request)
    {
    //  header('Content-type: application/json'); 
   // $a = $request->param();
  //return json_encode($a,320);
        set_time_limit(0);
        $ret = array(
            'code'=>1,
            'msg'=>'success'
        );
        //盲样号
        $sample_code = input('param.sample_code','','addslashes,trim,htmlspecialchars,strip_tags');
//      $arr['sample_code'] = $sample_code;
//eturn json_encode($arr);
//r_dump($sample_code);exit;
        //二次盲样号
        $sample_index = input('param.sample_index','','addslashes,trim,htmlspecialchars,strip_tags');
        // 设备类型
        $device_type = input('param.device_type','','addslashes,trim,htmlspecialchars,strip_tags');
        //物料描述
        $description = input('param.description','','addslashes,trim,htmlspecialchars,strip_tags');
        //任务分发时间
        $distribute_time = input('param.distribute_time','','addslashes,trim,htmlspecialchars,strip_tags');
        //任务完成时间
        $finish_time = input('param.finish_time','','addslashes,trim,htmlspecialchars,strip_tags');
        //实验类别
        $experiment_type = input('param.experiment_type','','addslashes,trim,htmlspecialchars,strip_tags');
        //实验具体项目
        $experiments = input('param.experiments','','addslashes,trim,htmlspecialchars,strip_tags');
        //检测单位
        $testing_institution = input('param.testing_institution','','addslashes,trim,htmlspecialchars,strip_tags');
        //检测周期
        $testing_duration = input('param.testing_duration',0,'addslashes,trim,htmlspecialchars,strip_tags');
        //超期时长
        $overtime_duration = input('param.overtime_duration',0,'addslashes,trim,htmlspecialchars,strip_tags');
        //超期阈值
        $overtime_norm = input('param.overtime_norm',0,'addslashes,trim,htmlspecialchars,strip_tags');
        //检测结论
        $conclusion = input('param.conclusion','','addslashes,trim,htmlspecialchars,strip_tags');

        if ($sample_code == '' || !$sample_code)
        {
            $ret['code'] = 0;
            $ret['msg'] = '错误，盲样号不能为空';
            return json_encode($ret,320);
        }

        $array = [
            'sample_code',
            'sample_index',
            'device_type',
            'description',
            'distribute_time',
            'finish_time',
            'experiment_type',
            'experiments',
            'testing_institution',
            'testing_duration',
            'overtime_duration',
            'overtime_norm',
            'conclusion',
            ];

        $params = [];
        foreach ($array as $v)
        {
            if($$v){
                $params[$v] = $$v;
            }
        }

        //dump($params);
        $if_exist = Db::table('dky_mission')->where('sample_code',$sample_code)->count();
        if ($if_exist)
        {
            //如果已经存在，则计算实际花费时间
            if (!isset($params['testing_duration'])){
                $ret['code'] = 1;
                $ret['msg'] = '错误！再次传输某任务信息时，检测周期（耗时）不能为空';
                return json_encode($ret,320);
            }

            $res = Db::table('dky_mission')->where('sample_code',$sample_code)->update($params);
            //var_dump(Db::table('dyk_mission')->getLastSql());

        }else{
            if (!isset($params['device_type']) || !isset($params['description']) || !isset($params['distribute_time'])
                || !isset($params['testing_institution'])|| !isset($params['experiment_type']) || !isset($params['sample_index'])
                || !isset($params['experiments']) || !isset($params['overtime_norm']))
            {
                $ret['code'] = 01;
                $ret['msg'] = '错误！初次传输某任务信息时，物资类别，物料描述，任务下发时间，检测单位，试验类别，超期阈值，试验具体项目不能为空';
                return json_encode($ret,320);
            }

            //材料OR设备
            $params['device_type_belong'] = $this->getDeviceTypeBelong($device_type);


            //状态默认为待检
            $params['status'] = '待检';
            //处理试验项目，把手动的项目去除，只传在工位上做的试验项目给思创
            $params['experiments_station'] = $this->extract_experiments($params['experiments']);



            //随机大写字母+随机小写字母+时间戳+随机数
            $time = substr(time(),2);
            $twins_token = chr(rand(65,90)).chr(rand(97,122)).$time.mt_rand(10000,99999);
            $params['twins_token'] = $twins_token;

            //返回插入mission表数据的id
            $res = Db::table('dky_mission')->insertGetId($params);

            if ($res !== false){
                //将试验项目集合中的循环插入到任务试验绑定表中

                $experiments_arr = explode(',',$params['experiments_station']);

                foreach ( $experiments_arr as $v){
                    $array = [
                        'mission'=> $res,
                        'experiment'=> $v,
                        'sample_index'=>$sample_index,
                        'type'=>$device_type,
                        'twins_token'=>$twins_token,
                        'testing_institution'=>$testing_institution,
                        'status'=>'待检',
                        'createtime'=>time(),
                        'updatetime'=>time()
                    ];
                    Db::table('dky_mission_experiment')->insert($array);
                }


                //进行下一步操作，将盲样号处理后生成的孪生id和要做的试验列表传给思创

                // 下单服务搁置
                //$this->dispatch($twins_token,$params['device_type'],$params['experiments_station'],$params['testing_institution'],$params['description']);
                //改为传输二次盲样号和数字孪生id给思创,思创在检测人员下单时查找检测人员所输入二次盲样号对应的数字孪生id，当做他们系统中的样品二维码

            }
        }

        if ($res === false){
            $ret['code'] = 0;
            $ret['msg'] = '插入失败，请重试';
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
        }else{
            $ret['code'] = 1;
            $ret['msg'] = 'success';
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
        }

    }

    public function getDeviceTypeBelong($device_type)
    {
        if (in_array($device_type,$this->material_list)){
            return '材料';
        }elseif (in_array($device_type,$this->device_list)){
            return '设备';
        }else{
            $array = array(
                'msg'=>'方天所传:'.$device_type.' 找不到对应类别',
                'type'=>'设备OR材料查找错误',
                'url'=>'',
            );
            Db::table('log')->insert($array);
            return '';
        }
    }

    public function dispatchId($twins_token,$sample_index)
    {

    }

    //处理试验项目，把工位上做的项目加进来




    public function extract_experiments($experiments)
    {
        //获取方天传的试验集合
        $experiments_arr = explode(',',$experiments);

        $arr = [];
        //获取工位上做的试验项目集合
        $station_experiments = Db::table('experiment')
            ->column('name');
        foreach ($experiments_arr as $k=>$v)
        {
            if (in_array($v,$station_experiments))
            {
                $arr[] = $v;
            }
        }

        $experiments_str = implode(',',$arr);
        return $experiments_str;

    }

    //下发任务，暂时只有南京和苏州需要向思创系统下单
    public function dispatch($twins_token,$device_type,$experiments_station,$testing_institution,$descripton)
    {
        $url = '';
        if ($testing_institution == '省中心（电科院）'){
            $url = config('njxd_url');
        }elseif ($testing_institution == '苏南分中心'){
            $url = config('szxd_url');
        }else{
            return false;
        }
        //用于向思创下单接口传数据
        $task_json_arr = [];
        $task_json_arr['Samplelist'][0]['SampleInfo']['samplecode'] = $twins_token;
        $task_json_arr['Samplelist'][0]['SampleInfo']['SampleExpList'] = $experiments_station;
        //配电变压器应思创需求，需区分油浸式和干式
        if ($device_type == '配电变压器'){
            if (strpos($descripton,'油浸') !== false)
            {
                $device_type = "油浸式配电变压器";
            }elseif (strpos($descripton,'干式') !== false){
                $device_type = "干式配电变压器";
            }
        }

        $task_json_arr['Samplelist'][0]['SampleInfo']['samplename'] = $device_type;

        $task_json = json_encode($task_json_arr,JSON_UNESCAPED_UNICODE);


        $url .= '?taskJson='.$task_json;
        //dump($url);
        $res = $this->getUrl($url);
        //dump($res);

        if (strpos($res,'<') !== false) {
            //禁止引用外部xml实体
            libxml_disable_entity_loader(true);
            $result = json_decode(json_encode(simplexml_load_string($res)), true);
            $array = json_decode($result[0], true);
            if (!isset($array['flag']) ||  $array['flag'] === 'false' || $array['msg'] !=='')
            {
                $array = array(
                    'msg'=>$res,
                    'type'=>'向思创接口下单错误',
                    'url'=>$url,
                );
                Db::table('log')->insert($array);
            }else{
                $array = array(
                    'msg'=>$res,
                    'type'=>'向思创接口下单',
                    'url'=>$url,
                );
                Db::table('log')->insert($array);
            }
            //dump($array);
        }else{
            //下单失败则生成报警信息
            $array = array(
                'msg'=>$res,
                'type'=>'向思创接口下单错误',
                'url'=>$url,
            );
            Db::table('log')->insert($array);
        }
    }


    //获取工位看板和统计信息
    public function getStationStatus()
    {
        $ret = array(
            'code' => 1,
            'msg' => 'success',
            'data' => [],
        );

        $district = input('param.district','','addslashes,trim,htmlspecialchars,strip_tags');

        if (!$district || !in_array($district,['南京','苏州']))
        {
            $ret['code'] = 0;
            $ret['msg'] = '错误，请输入正确的地区信息';
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
        }

        $district_id = $this->district_reflection[$district];
        if ($district == '南京')
        {
            //南京
            $station_status_url = config('njgwzt_url');
        }elseif($district == '苏州'){
            //苏州
            $station_status_url = config('szgwzt_url');
        }


        $res = $this->getUrl($station_status_url);
        $array = array(
            'msg'=>$res,
            'type'=>'getStationStatus访问工位接口'
        );
        Db::table('log')->insert($array);
        //如果返回值中不含有<,代表无有效信息返回
        if (strpos($res,'<') !== false)
        {
            //禁止引用外部xml实体
            libxml_disable_entity_loader(true);
            //return json_encode(simplexml_load_string($res),JSON_UNESCAPED_UNICODE);
            $result = json_decode(json_encode(simplexml_load_string($res)), true);

            $array = json_decode($result[0],true);
            $station_array = $array['nodesInfo'];
            $arr = [
                'gz'=>[],
                'kx'=>[],
                'zj'=>[],
                'sum'=>[],
            ];
            $ret['data']['statistics'] = array(
              'total'=>0,
              'kx'=>0,
              'yx'=>0,
              'gz'=>0,
              'in_use_rate'=>0
            );
            foreach ($station_array as $k=>$v)
            {
                $ret['data']['statistics']['total']++;
                //工位名称
                $station_name = $v['nodeName'];
                //根据工位名称和检测机构查找负责人名称
                $principal = Db::table('dky_station')
                    ->where('name',$station_name)
                    ->where('district_id',$district_id)
                    ->value('principal');
                if ($v['static'] == '在检'){
                    $arr['zj'][] = ['name'=>$station_name,'principal'=>$principal,'status'=>'在检'];
                    $arr['sum'][] = ['name'=>$station_name,'principal'=>$principal,'status'=>'在检'];
                    $ret['data']['statistics']['yx']++;
                }
                if ($v['static'] == '空闲'){
                    $arr['kx'][] = ['name'=>$station_name,'principal'=>$principal,'status'=>'空闲'];
                    $arr['sum'][] = ['name'=>$station_name,'principal'=>$principal,'status'=>'空闲'];
                    $ret['data']['statistics']['kx']++;
                }
                if ($v['static'] == '未开启'){
                    $arr['gz'][] = ['name'=>$station_name,'principal'=>$principal,'status'=>'故障'];
                    $arr['sum'][] = ['name'=>$station_name,'principal'=>$principal,'status'=>'故障'];
                    $ret['data']['statistics']['gz']++;
                }

            }
            $ret['data']['statistics']['in_use_rate'] =  round($ret['data']['statistics']['yx'] / $ret['data']['statistics']['total'],2);
            $ret['data']['panel'] = $arr;
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
        }else{
            $ret['code'] = 0;
            $ret['msg'] = '思创api5无正确返回值';
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
        }
    }


    //获取样品信息接口
    public function getSampleinfo()
    {
        $ret = array(
            'code' => 1,
            'msg' => 'success',
            'data' => [],
        );


        $district = input('param.district','','addslashes,trim,htmlspecialchars,strip_tags');
        $s_code = input('param.s_code','','addslashes,trim,htmlspecialchars,strip_tags');

        if (!$district || !in_array($district,['南京','苏州']) || !$s_code)
        {
            $ret['code'] = 0;
            $ret['msg'] = '错误，请输入正确的地区信息和s_code';
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
        }

        if ($district == '南京')
        {
            //南京
            $url = config('njypxx_url');
        }elseif($district == '苏州'){
            //苏州
            $url = config('szypxx_url');
        }

        $url.='?sampleCode='.$s_code;
        $res = $this->getUrl($url);

        $array = array(
            'msg'=>$res,
            'type'=>'getsampleInfo访问样品接口'
        );
        Db::table('log')->insert($array);
        //如果返回值中不含有<,代表无有效信息返回
        if (strpos($res,'<') !== false)
        {
            //禁止引用外部xml实体
            libxml_disable_entity_loader(true);
            //return json_encode(simplexml_load_string($res),JSON_UNESCAPED_UNICODE);
            $result = json_decode(json_encode(simplexml_load_string($res)), true);

            $array = json_decode($result[0],true);
            $array['state'] = $array['static'];
            unset($array['static']);
            $ret['data'] = $array;
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
        }else{
            $ret['code'] = 0;
            $ret['msg'] = '思创api6无正确返回值';
            return json_encode($ret,JSON_UNESCAPED_UNICODE);
        }
    }


    public function getDigitalId()
    {
        $ret = array(
            'code' => 1,
            'msg' => 'success',
            'id' => '',
        );

        $s_code = input('param.s_code','','addslashes,trim,htmlspecialchars,strip_tags');
        if (!$s_code){
            $ret['code'] = 0;
            //$ret['msg'] = '找不到此二次盲样号对应的记录，请检查输入的二次盲样号是否和接样时输入的一致';
            $ret['msg'] = '二次盲样号不能为空';
            return json_encode($ret,320);
        }
        $res = Db::table('dky_mission')->where('sample_index',$s_code)
            ->find();
        if (!$res){
            $ret['code'] = 0;
            $ret['msg'] = '找不到此二次盲样号对应的记录，请检查输入的二次盲样号是否和接样时输入的一致';
            return json_encode($ret,320);
        }
        $twins_token = $res['twins_token'];
        if (!$twins_token){
            $ret['code'] = 0;
            $ret['msg'] = '数字孪生id异常，请联系数字孪生平台';
            return json_encode($ret,320);
        }else{
            $ret['id'] = $twins_token;
            return json_encode($ret,320);
        }

    }
    
    


}
