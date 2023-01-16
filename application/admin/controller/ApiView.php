<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;

class ApiView extends Controller{
    //显示过程数据视图
    public function process()
    {
        $s_code = input('param.s_code','','addslashes');
        $e_name = input('param.e_name','','addslashes');
        //$url = 'http://localhost/elec/public/index.php/api/index/getProcessData';
        $url = config('process_data_url');
        $url .= "?s_code={$s_code}&e_name={$e_name}";
        $res = $this->getUrl($url);
        $data = isset(json_decode($res,true)['data']) ? json_decode($res,true)['data'] : [];
        $this->assign('e_name',$e_name);
        $this->assign('s_code',$s_code);
        $this->assign('process_data',$data);
        $this->assign('url',config('process_data_url'));
        //echo config('process_data_url');exit;
        return $this->fetch('index');
    }

    //get方式访问接口
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

    //显示结果数据视图
    public function report()
    {
        $s_code = input('param.s_code','','addslashes');
        $this->assign('s_code',$s_code);
        $this->assign('url',config('report_data_url'));
        return $this->fetch();
    }

    //显示校园交通大数据分析图
    public function traffic()
    {
        return $this->fetch();
    }

    //显示警告列表
    public function warning()
    {
        $dict = array(
            "gz_not"=>'未穿工装',
            "helmet_not"=>'未戴安全头盔',
            "normal"=>'正常',
        );
        $res = [];
        $warnings = Db::table('warning')->order('id desc')->select();
        foreach ($warnings as $k=>$v)
        {
            $json = $v['json'];
            $json_arr = json_decode(stripslashes($json),true);
            if ( $json_arr !== null)
            {
                //告警内容
                $warning_content = '';
                $coord = '';
                if (isset($json_arr['data'][0])){
                    $row['time'] = $json_arr['data'][0]['devTime'];
                    $l = explode('/',rtrim($json_arr['data'][0]['location'],'/'));
                    $len = count($l);
                    $row['location'] = $l[$len-1];
                    $row['pic_url'] = $json_arr['data'][0]['picData'];
                }
                foreach ($json_arr['data'] as $kk=>$vv)
                {
                    $warning_content .= isset($dict[$vv['className']]) ? $dict[$vv['className']] : '未知';
                    $warning_content .= ',';
                    //$coord .= implode(',',$vv['objCoord']).'-';
                    $coord .= trim(trim($vv['objCoord'],'['),']').'-';
                }
                $row['content'] =rtrim($warning_content,',');
                $row['coord'] =rtrim($coord,'-');
                $res[] = $row;
            }
        }

            //echo '<pre>';print_r($res);exit;
        $this->assign('res',$res);
        $url = config('warning_data_url');
        $this->assign('url',$url);
        return $this->fetch();
    }

    public function getWarningBak()
    {
        $dict = array(
            "gz_not"=>'未穿工装',
            "helmet_not"=>'未戴安全头盔',
            "normal"=>'正常',
        );
        $res = [];
        $warnings = Db::table('warning')->order('id desc')->select();
        foreach ($warnings as $k=>$v)
        {
            $json = $v['json'];
            $json_arr = json_decode(stripslashes($json),true);
            if ( $json_arr !== null)
            {
                //告警内容
                $warning_content = '';
                $coord = '';
                if (isset($json_arr['data'][0])){
                    $row['time'] = $json_arr['data'][0]['devTime'];
                    $l = explode('/',rtrim($json_arr['data'][0]['location'],'/'));
                    $len = count($l);
                    $row['location'] = $l[$len-1];
                    $row['pic_url'] = $json_arr['data'][0]['picData'];
                }
                foreach ($json_arr['data'] as $kk=>$vv)
                {
                    $warning_content .= isset($dict[$vv['className']]) ? $dict[$vv['className']] : '未知';
                    $warning_content .= ',';
                    //$coord .= implode(',',$vv['objCoord']).'-';
                    $coord .= trim(trim($vv['objCoord'],'['),']').'-';
                }
                $row['content'] =rtrim($warning_content,',');
                $row['coord'] =rtrim($coord,'-');
                $res[] = $row;
            }
        }
        return json_encode($res,JSON_UNESCAPED_UNICODE);
    }

    public function showWarningPic()
    {
        $url = input('param.url','','addslashes');
        $coord = input('param.coord','','addslashes');
        $this->assign('url' , $url);
        $this->assign('coord' , $coord);
        return $this->fetch('picture');
    }

    public function drawrectangle()
    {
        return $this->fetch();
    }

}