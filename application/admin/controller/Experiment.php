<?php

namespace app\admin\controller;

use app\admin\library\Auth;
use app\common\controller\Backend;
use think\Db;
use think\exception\PDOException;
use think\Log;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Experiment extends Backend
{

    /**
     * Experiment模型对象
     * @var \app\admin\model\Experiment
     */
    protected $model = null;
    protected $noNeedLogin = [
        'index',
        'getLaboratoryInfo',
        'getMissionBasicInfo',
        'getMissionDetail',
        'getStaff',
        'getTestingProblem',
        'getTestingDuration',
        'getDeviceInfo',
        'getAgvInfo',
        'getStationInfo',
        'getStorageInfo',
        'getStaffInfo',
        'getAllStaff',
        'getMaintainInfo',
        'getMissionStatus',
        'sendMissionStatus',
        'sendExperimentData',
        'getMaintenanceByDeviceNo',
        'getSamplePassRate',
        'getEnvironment',
        'showNameAndUnityToken',
        'getAlert',
        'getStaffExperiment',
        'getStaffDetail',
        'getSampleInfo',
        'getSampleExperiment',
        'getSamplePanel',
        'getTagForHistory',
        'searchSample',
        'getStationPanel',
        'getDeviceManagement',
        'getAlertInfo',
        'searchTestingQuantity',
        'getTestingInfo',
        'getRankBreakDown',
        'sendReportData',
    ];


    public $excludeFields = [''];
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


    public   $district_reflection = [
        '南京'=>1,
        '苏州'=>2,
        '泰州'=>3,
        '徐州'=>4,
    ];

    public   $district_reflection_inverse = [
        1=>'南京',
        2=>'苏州',
        3=>'泰州',
        4=>'徐州',
    ];

    public  $name_reflection = [
        '省中心（电科院）'=>1,
        '苏中分中心'=>3,
        '苏南分中心'=>2,
        '苏北分中心'=>4,
    ];

    public  $name_reflection_inverse = [
        1=>'省中心（电科院）',
        3=>'苏中分中心',
        2=>'苏南分中心',
        4=>'苏北分中心',
    ];


    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Experiment;

    }

    public function import()
    {
        parent::import();
    }



    public function getUrl($url){
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


    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    public function getLaboratoryInfo()
    {

        $ret = array(
            'code'=>1,
            'msg'=>'success'
        );

        $res = Db::table('laboratory_info')->where('deletetime',null)->select();

        //在检数量
        $this_year = date('Y');
        $arr = Db::table('dky_mission')
            ->field('count(*) as num,testing_institution,status')
            ->where('device_type','in',$this->device_list)
            ->where('distribute_time','like',$this_year.'%')
            ->group('testing_institution,status')
            ->select();

        $arr1 = Db::table('dky_mission')
            ->field('count(*) as num,testing_institution,status')
            ->where('device_type','in',$this->material_list)
            ->where('distribute_time','like',$this_year.'%')
            ->group('testing_institution,status')
            ->select();


        //规范预警数量
        $res_yj = Db::table('dky_testing_problem')
            ->field('count(*) as number,district_id')
            ->where('occur_at','like',$this_year.'%')
            ->where('deletetime',null)
            ->group('district_id')
            ->select();

        //超期数量
        $res_cq = Db::table('dky_mission')
            ->field('count(*) as number,testing_institution')
            ->where('distribute_time','like',$this_year.'%')
            ->where('overtime_duration','>',0)
            ->group('testing_institution')
            ->force('idx_6')
            ->select();






        foreach ($res as $k=>$v){
            //只有四个中心要计算的数据
            if (in_array($res[$k]['name'],$this->name_reflection_inverse)){
                $res[$k]['device_count'] = $res[$k]['device_a'] + $res[$k]['device_b'] + $res[$k]['device_c'];
                $res[$k]['material_count'] = $res[$k]['material_a'] + $res[$k]['material_b'] + $res[$k]['material_c'];
                $res[$k]['device_zj_count'] = 0;
                $res[$k]['material_zj_count'] = 0;
                $res[$k]['yj_count'] = 0;
                $res[$k]['cq_count'] = 0;
                foreach ($arr as $kkkkk=>$vvvvv){
                    if (($vvvvv['testing_institution'] == $v['name']) && ($vvvvv['status'] == '在检')){
                        $res[$k]['device_zj_count'] = $vvvvv['num'];
                    }
                }
                foreach ($arr1 as $kk=>$vv){
                    if (($vv['testing_institution'] == $v['name']) && ($vv['status'] == '在检')){
                        $res[$k]['material_zj_count'] = $vv['num'];
                    }
                }
                foreach ($res_yj as $kkk=>$vvv){
                    if ($this->name_reflection_inverse[$vvv['district_id']] == $v['name']){
                        $res[$k]['yj_count'] = $vvv['number'];
                    }
                }

                foreach ($res_cq as $kkkk=>$vvvv){
                    if ($vvvv['testing_institution'] == $v['name']){
                        $res[$k]['cq_count'] = $vvvv['number'];
                    }
                }

            }

        }


        //逾期完成样品数


        $ret['data'] = $res;

        return json_encode($ret,320);
    }

    //获取人员详细信息
    public function getStaffDetail()
    {
        $ret = array(
            'code'=>1,
            'msg'=>'success',
            'data'=>[]
        );
        $staff_ids = input('param.ids','','addslashes,trim,htmlspecialchars,strip_tags');
        if (!$staff_ids){
            $ret['code'] = 0;
            $ret['msg'] = '人员id非空';
            return  json_encode($ret,320);
        }
        $staff_ids = explode(',',$staff_ids);
        $arr = [];
        foreach ($staff_ids as $v){
            $line = Db::table('dky_staff')->where('id',$v)->find();
            $arr[] = $line;
        }
        $ret['data'] = $arr;
        return json_encode($ret,320);
    }

    /**
     *查询检测基本信息
     */
    public function getMissionBasicInfo()
    {
        $ret = array(
            'code'=>1,
            'msg'=>'success',
            'data'=>[]
        );

        $this_year = date('Y');
        //全省检测量
        $qs_number =  Db::table('dky_mission')
            ->where('distribute_time','like',$this_year.'%')
            ->where('finish_time','<>','1970-01-01 00:00:00')
            ->count();
        $ret['data']['qs_number'] = $qs_number;
        //全省检测合格数量
        $qs_pass_number =  Db::table('dky_mission')
            ->where('distribute_time','like',$this_year.'%')
            ->where('conclusion',1)
            ->count();
        //全省合格率
        if ($qs_number != 0){
            $qs_pass_rate = round($qs_pass_number / $qs_number,2);
        }else{
            // 避免除法报错，给默认值
            $qs_number = 1;
            $qs_pass_rate = 0;
        }

        $ret['data']['qs_pass_rate'] = $qs_pass_rate;
        //全省自检量
        $district_list = [
            '省中心（电科院）',
            '苏北分中心',
            '苏中分中心',
            '苏南分中心',
        ];
        $qs_self_number = Db::table('dky_mission')
            ->where('testing_institution','in',$district_list)
            ->where('distribute_time','like',$this_year.'%')
            ->where('finish_time','<>','1970-01-01 00:00:00')
            ->count();

        //全省自检率
        if ($qs_pass_number != 0){
            $qs_self_rate = round($qs_self_number/$qs_number,2);
        }else{
            $qs_self_rate = 0;
        }
        $ret['data']['qs_self_rate'] = $qs_self_rate;

        //查询各地区当年检测量
        $temp = Db::table('dky_mission')
            ->field('count(*) as number,testing_institution')
            ->where('distribute_time','like',$this_year.'%')
            ->where('finish_time','<>','1970-01-01 00:00:00')
            ->group('testing_institution')
            ->select();

        //从结果集中抽出检测单位列
        $temp1 = array_column($temp,null,'testing_institution');


        //查询各地区当年合格量
        $temp2 =  Db::table('dky_mission')
            ->field('count(*) as number,testing_institution')
            ->where('distribute_time','like',$this_year.'%')
            ->where('conclusion',1)
            ->group('testing_institution')
            //mysql自身未自动找到索引，强制使用索引
            ->force('idx_5')
            ->select();

        //echo Db::table('dky_mission')->getLastSql();exit;
        //从结果集中抽出检测单位列
        $temp3 = array_column($temp2,null,'testing_institution');

        if (isset($temp1['省中心（电科院）']) && $temp1['省中心（电科院）']['number'] != 0){
            if (isset($temp3['省中心（电科院）'])){
                //省中心检测量
                $szx_number = $temp1['省中心（电科院）']['number'];
                //省中心通过量
                $szx_pass_number = $temp3['省中心（电科院）']['number'];
                //省中心通过率
                $szx_pass_rate = round($szx_pass_number / $szx_number,2);
                //省中心检测量占全省检测量的比例
                $szx_pass_proportion =  round($szx_number / $qs_number,2);
            }else{
                //省中心检测量
                $szx_number = $temp1['省中心（电科院）']['number'];
                //省中心通过量
                $szx_pass_number = 0;
                //省中心通过率
                $szx_pass_rate = 0;
                //省中心检测量占全省检测量的比例
                $szx_pass_proportion =  round($szx_number / $qs_number,2);
            }
        }else{
            //省中心检测量
            $szx_number = 0;
            //省中心通过量
            $szx_pass_number = 0;
            //省中心通过率
            $szx_pass_rate = 0;
            //省中心检测量占全省检测量的比例
            $szx_pass_proportion =  0;
        }


        if (isset($temp1['苏北分中心']) && $temp1['苏北分中心']['number'] != 0){
            if (isset($temp3['苏北分中心'])){
                //苏北分中心检测量
                $sb_number = $temp1['苏北分中心']['number'];
                //苏北分中心通过量
                $sb_pass_number = $temp3['苏北分中心']['number'];
                //苏北分中心通过率
                $sb_pass_rate = round($sb_pass_number / $sb_number,2);
                //苏北分中心检测量占全省检测量的比例
                $sb_pass_proportion =  round($sb_number / $qs_number,2);
            }else{
                //苏北分中心检测量
                $sb_number = $temp1['苏北分中心']['number'];
                //苏北分中心通过量
                $sb_pass_number = 0;
                //苏北分中心通过率
                $sb_pass_rate = 0;
                //苏北分中心检测量占全省检测量的比例
                $sb_pass_proportion =  round($sb_number / $qs_number,2);
            }
        }else{
            //苏北分中心检测量
            $sb_number = 0;
            //苏北分中心通过量
            $sb_pass_number = 0;
            //苏北分中心通过率
            $sb_pass_rate = 0;
            //苏北分中心检测量占全省检测量的比例
            $sb_pass_proportion =  0;
        }

        if (isset($temp1['苏中分中心']) && $temp1['苏中分中心']['number'] != 0){
            if (isset($temp3['苏中分中心'])){
                //苏中分中心检测量
                $sz_number = $temp1['苏中分中心']['number'];
                //苏中分中心通过量
                $sz_pass_number = $temp3['苏中分中心']['number'];
                //苏中分中心通过率
                $sz_pass_rate = round($sz_pass_number / $sz_number,2);
                //苏中分中心检测量占全省检测量的比例
                $sz_pass_proportion =  round($sz_number / $qs_number,2);
            }else{
                //苏中分中心检测量
                $sz_number = $temp1['苏中分中心']['number'];
                //苏中分中心通过量
                $sz_pass_number = 0;
                //苏中分中心通过率
                $sz_pass_rate = 0;
                //苏中分中心检测量占全省检测量的比例
                $sz_pass_proportion =  round($sz_number / $qs_number,2);
            }
        }else{
            //苏中分中心检测量
            $sz_number = 0;
            //苏中分中心通过量
            $sz_pass_number = 0;
            //苏中分中心通过率
            $sz_pass_rate = 0;
            //苏中分中心检测量占全省检测量的比例
            $sz_pass_proportion =  0;
        }

        if (isset($temp1['苏南分中心']) && $temp1['苏南分中心']['number'] != 0){
            if (isset($temp3['苏南分中心'])){
                //苏南分中心检测量
                $sn_number = $temp1['苏南分中心']['number'];
                //苏南分中心通过量
                $sn_pass_number = $temp3['苏南分中心']['number'];
                //苏南分中心通过率
                $sn_pass_rate = round($sn_pass_number / $sn_number,2);
                //苏南分中心检测量占全省检测量的比例
                $sn_pass_proportion =  round($sn_number / $qs_number,2);
            }else{
                //苏南分中心检测量
                $sn_number = $temp1['苏南分中心']['number'];
                //苏南分中心通过量
                $sn_pass_number = 0;
                //苏南分中心通过率
                $sn_pass_rate = 0;
                //苏南分中心检测量占全省检测量的比例
                $sn_pass_proportion =  round($sn_number / $qs_number,2);
            }
        }else{
            //苏南分中心检测量
            $sn_number = 0;
            //苏南分中心通过量
            $sn_pass_number = 0;
            //苏南分中心通过率
            $sn_pass_rate = 0;
            //苏南分中心检测量占全省检测量的比例
            $sn_pass_proportion =  0;
        }

        $ret['data']['basic_info']['szx']['number'] = $szx_number;
        $ret['data']['basic_info']['szx']['pass_rate'] = $szx_pass_rate;
        $ret['data']['basic_info']['szx']['pass_proportion'] = $szx_pass_proportion;
        $ret['data']['basic_info']['sb']['number'] = $sb_number;
        $ret['data']['basic_info']['sb']['pass_rate'] = $sb_pass_rate;
        $ret['data']['basic_info']['sb']['pass_proportion'] = $sb_pass_proportion;
        $ret['data']['basic_info']['sz']['number'] = $sz_number;
        $ret['data']['basic_info']['sz']['pass_rate'] = $sz_pass_rate;
        $ret['data']['basic_info']['sz']['pass_proportion'] = $sz_pass_proportion;
        $ret['data']['basic_info']['sn']['number'] = $sn_number;
        $ret['data']['basic_info']['sn']['pass_rate'] = $sn_pass_rate;
        $ret['data']['basic_info']['sn']['pass_proportion'] = $sn_pass_proportion;


        //检测质量情况

        $ret['data']['mission_result'] = $this->getMissionResult($qs_number,$szx_number,$sb_number,$sz_number,$sn_number);

        //检测时效情况
        $ret['data']['mission_duration'] = $this->getMissionDuration($qs_number,$szx_number,$sb_number,$sz_number,$sn_number);
        return json_encode($ret,320);

    }

    /**
     *查询检测质量情况
     */
    public function getMissionResult($qs_number,$szx_number,$sb_number,$sz_number,$sn_number)
    {
        $arr = [];
        $this_year = date('Y');
        $qs_problem_number =  $res = Db::table('dky_testing_problem')
            ->where('occur_at','like',$this_year.'%')
            ->where('deletetime',null)
            ->count();
        $arr['qs_problem_number'] = $qs_problem_number;
        $arr['qs_problem_rate'] = round($qs_problem_number/$qs_number,2);

        $res = Db::table('dky_testing_problem')
            ->field('count(*) as number,district_id')
            ->where('occur_at','like',$this_year.'%')
            ->where('deletetime',null)
            ->group('district_id')
            ->select();
        $res1 = array_column($res,null,'district_id');

        $szx_problem_number = isset($res1['1']['number']) ? $res1['1']['number'] : 0;
        $sz_problem_number = isset($res1['3']['number']) ? $res1['3']['number'] : 0;
        $sn_problem_number = isset($res1['2']['number']) ? $res1['2']['number'] : 0;
        $sb_problem_number = isset($res1['4']['number']) ? $res1['4']['number'] : 0;

        if ($szx_number != 0){
            $szx_problem_rate = round($szx_problem_number / $szx_number,2);
        }else{
            $szx_problem_rate = 0;
        }

        if ($sz_number != 0){
            $sz_problem_rate = round($sz_problem_number / $sz_number,2);
        }else{
            $sz_problem_rate = 0;
        }

        if ($sn_number != 0){
            $sn_problem_rate = round($sn_problem_number / $sn_number,2);
        }else{
            $sn_problem_rate  = 0;
        }

        if ($sb_number != 0){
            $sb_problem_rate = round($sb_problem_number / $sb_number,2);
        }else{
            $sb_problem_rate = 0;
        }

        $arr['szx_problem_number'] = $szx_problem_number;
        $arr['sz_problem_number'] = $sz_problem_number;
        $arr['sn_problem_number'] = $sn_problem_number;
        $arr['sb_problem_number'] = $sb_problem_number;
        $arr['szx_problem_rate'] = $szx_problem_rate;
        $arr['sz_problem_rate'] = $sz_problem_rate;
        $arr['sn_problem_rate'] = $sn_problem_rate;
        $arr['sb_problem_rate'] = $sb_problem_rate;

        return $arr;
    }

    /**
     *查询检测时效情况
     */
    public function getMissionDuration($qs_number,$szx_number,$sb_number,$sz_number,$sn_number)
    {
        $ret = [];
        $this_year = date('Y');
        $qs_overtime_number = Db::table('dky_mission')
            ->where('distribute_time','like',$this_year.'%')
            ->where('overtime_duration','>',0)
            ->count();


        //全省超期率
        $qs_overtime_rate = $qs_number != 0 ? round($qs_overtime_number / $qs_number,2) : 0;

        $ret['qs_overtime_rate'] = $qs_overtime_rate;

        //计算各分中心超期率
        $res = Db::table('dky_mission')
            ->field('count(*) as number,testing_institution')
            ->where('distribute_time','like',$this_year.'%')
            ->where('overtime_duration','>',0)
            ->group('testing_institution')
            ->force('idx_6')
            ->select();
        $res = array_column($res,null,'testing_institution');

        //echo Db::table('dky_mission')->getLastSql();exit;
        if (isset($res['省中心（电科院）']) && $szx_number)
        {
            $szx_overtime_rate = round($res['省中心（电科院）']['number'] / $szx_number,2);
        }else{
            $szx_overtime_rate = 0;
        }

        if (isset($res['苏北分中心']) && $sb_number)
        {
            $sb_overtime_rate = round($res['苏北分中心']['number'] / $sb_number,2);
        }else{
            $sb_overtime_rate = 0;
        }

        if (isset($res['苏中分中心']) && $sz_number)
        {
            $sz_overtime_rate = round($res['苏中分中心']['number'] / $sz_number,2);
        }else{
            $sz_overtime_rate = 0;
        }

        if (isset($res['苏南分中心']) && $sn_number)
        {
            $sn_overtime_rate = round($res['苏南分中心']['number'] / $sn_number,2);
        }else{
            $sn_overtime_rate = 0;
        }

        $ret['szx_overtime_rate'] = $szx_overtime_rate;
        $ret['sz_overtime_rate'] = $sz_overtime_rate;
        $ret['sn_overtime_rate'] = $sn_overtime_rate;
        $ret['sb_overtime_rate'] = $sb_overtime_rate;



        //全省设备类检测时长
        $qs_device_time = Db::table('dky_mission')
            ->where('device_type','in',$this->device_list)
            ->where('distribute_time','like',$this_year.'%')
            ->where('finish_time','<>','1970-01-01 00:00:00')
            ->sum('testing_duration');

        //全省设备类检测量


       $device_experiment_count = Db::table('dky_mission')
        ->where('device_type','in',$this->device_list)
        ->where('distribute_time','like',$this_year.'%')
        ->where('finish_time','<>','1970-01-01 00:00:00')
        ->count();


       //全省设备类平均检测时长
        $qs_device_average_time = $device_experiment_count != 0 ? round($qs_device_time/$device_experiment_count,2) : 0;
        $ret['qs_device_average_time'] = $qs_device_average_time;

        //------------


        //全省材料类检测时长
        $qs_material_time  = Db::table('dky_mission')
            ->where('device_type','in',$this->material_list)
            ->where('distribute_time','like',$this_year.'%')
            ->where('finish_time','<>','1970-01-01 00:00:00')
            ->sum('testing_duration');
        //Log::write(Db::table('dky_mission')->getLastSql());


        //全省材料类检测量

        $material_experiment_count = Db::table('dky_mission')
        ->where('device_type','in',$this->material_list)
        ->where('distribute_time','like',$this_year.'%')
        ->where('finish_time','<>','1970-01-01 00:00:00')
        ->count();


        //全省材料类平均检测时长
        $qs_material_average_time = $material_experiment_count != 0 ? round($qs_material_time/$material_experiment_count,2) : 0;

        $ret['qs_material_average_time'] = $qs_material_average_time;


        //计算各地区设备类检测总时长

        $res_device_time = Db::table('dky_mission')
            ->field('sum(testing_duration) as number,testing_institution')
            ->where('device_type','in',$this->device_list)
            ->where('distribute_time','like',$this_year.'%')
            ->where('finish_time','<>','1970-01-01 00:00:00')
            ->group('testing_institution')
            ->select();
        //echo Db::table('dky_mission')->getLastSql();exit;
            //Log::write(Db::table('dky_mission')->getLastSql());
        $res_device_time = array_column($res_device_time,null,'testing_institution');

        //计算各地区材料类检测总时长
        $res_material_time = Db::table('dky_mission')
            ->field('sum(testing_duration) as number,testing_institution')
            ->where('device_type','in',$this->material_list)
            ->where('distribute_time','like',$this_year.'%')
            ->where('finish_time','<>','1970-01-01 00:00:00')
            ->group('testing_institution')
            ->select();
        $res_material_time = array_column($res_material_time,null,'testing_institution');

        //计算各地区设备类检测量
        $res_device_number = Db::table('dky_mission')
            ->field('count(*) as number,testing_institution')
            ->where('device_type','in',$this->device_list)
            ->where('distribute_time','like',$this_year.'%')
            ->where('finish_time','<>','1970-01-01 00:00:00')
            ->group('testing_institution')
            ->select();
        $res_device_number = array_column($res_device_number,null,'testing_institution');

        //计算各地区材料类检测量
        $res_material_number = Db::table('dky_mission')
            ->field('count(*) as number,testing_institution')
            ->where('device_type','in',$this->material_list)
            ->where('distribute_time','like',$this_year.'%')
            ->where('finish_time','<>','1970-01-01 00:00:00')
            ->group('testing_institution')
            ->select();
        $res_material_number = array_column($res_material_number,null,'testing_institution');

        if (isset($res_device_number['省中心（电科院）']['number']) && $res_device_number['省中心（电科院）']['number'] && isset($res_device_time['省中心（电科院）']['number']))
        {
            //省中心设备类平均检测时长
            $szx_device_average_time = round($res_device_time['省中心（电科院）']['number'] / $res_device_number['省中心（电科院）']['number'] ,2 );
        }else{
            $szx_device_average_time = 0;
        }

        if (isset($res_material_number['省中心（电科院）']['number']) && $res_material_number['省中心（电科院）']['number'] && isset($res_material_time['省中心（电科院）']['number']))
        {
            //省中心材料类平均检测时长
            $szx_material_average_time = round($res_material_time['省中心（电科院）']['number'] / $res_material_number['省中心（电科院）']['number'] ,2 );
        }else{
            $szx_material_average_time = 0;
        }

        if (isset($res_device_number['苏北分中心']['number']) && $res_device_number['苏北分中心']['number'] && isset($res_device_time['苏北分中心']['number']))
        {
            //苏北设备类平均检测时长
            $sb_device_average_time = round($res_device_time['苏北分中心']['number'] / $res_device_number['苏北分中心']['number'] ,2 );
        }else{
            $sb_device_average_time = 0;
        }

        if (isset($res_material_number['苏北分中心']['number']) && $res_material_number['苏北分中心']['number'] && isset($res_material_time['苏北分中心']['number']))
        {
            //苏北材料类平均检测时长
            $sb_material_average_time = round($res_material_time['苏北分中心']['number'] / $res_material_number['苏北分中心']['number'] ,2 );
        }else{
            $sb_material_average_time = 0;
        }


        if (isset($res_device_number['苏中分中心']['number']) && $res_device_number['苏中分中心']['number'] && isset($res_device_time['苏中分中心']['number']))
        {
            //苏中设备类平均检测时长
            $sz_device_average_time = round($res_device_time['苏中分中心']['number'] / $res_device_number['苏中分中心']['number'] ,2 );
        }else{
            $sz_device_average_time = 0;
        }


        if (isset($res_material_number['苏中分中心']['number']) && $res_material_number['苏中分中心']['number'] && isset($res_material_time['苏中分中心']['number']))
        {
            //苏中材料类平均检测时长
            $sz_material_average_time = round($res_material_time['苏中分中心']['number'] / $res_material_number['苏中分中心']['number'] ,2 );
        }else{
            $sz_material_average_time = 0;
        }


        if (isset($res_device_number['苏南分中心']['number']) && $res_device_number['苏南分中心']['number'] && isset($res_device_time['苏南分中心']['number']))
        {
            //苏南设备类平均检测时长
            $sn_device_average_time = round($res_device_time['苏南分中心']['number'] / $res_device_number['苏南分中心']['number'] ,2 );
        }else{
            $sn_device_average_time = 0;
        }


        if (isset($res_material_number['苏南分中心']['number']) && $res_material_number['苏南分中心']['number'] && isset($res_material_time['苏南分中心']['number']))
        {
            //苏南材料类平均检测时长
            $sn_material_average_time = round($res_material_time['苏南分中心']['number'] / $res_material_number['苏南分中心']['number'] ,2 );
        }else{
            $sn_material_average_time = 0;
        }


        $ret['szx_device_average_time'] = $szx_device_average_time;
        $ret['szx_material_average_time'] = $szx_material_average_time;
        $ret['sb_device_average_time'] = $sb_device_average_time;
        $ret['sb_material_average_time'] = $sb_material_average_time;
        $ret['sz_device_average_time'] = $sz_device_average_time;
        $ret['sz_material_average_time'] = $sz_material_average_time;
        $ret['sn_device_average_time'] = $sn_device_average_time;
        $ret['sn_material_average_time'] = $sn_material_average_time;


        return $ret;
    }


    public function getMissionDetail()
    {
        $ret = ['data'=>['device'=>[],'material'=>[]],
            'code'=>1,
            'msg'=>'success'
        ];
        $this_year = date('Y');
        //省中心已分配检测任务总数
        $szx_number =  Db::table('dky_mission')
            ->where('testing_institution', '省中心（电科院）')
            ->where('distribute_time','like',$this_year.'%')
            ->count();


        //省中心已完成检测量
        $szx_ywc_number =  Db::table('dky_mission')
            ->where('testing_institution', '省中心（电科院）')
            ->where('distribute_time','like',$this_year.'%')
            ->where('finish_time','<>','1970-01-01 00:00:00')
            ->count();
        //省中心检测合格量
        $szx_hg_number =  Db::table('dky_mission')
            ->where('testing_institution', '省中心（电科院）')
            ->where('distribute_time','like',$this_year.'%')
            ->where('conclusion',1)
            ->count();
        //省中心检测合格率
        $szx_hgl = $szx_ywc_number != 0 ? round($szx_hg_number/$szx_ywc_number,2) : 0;
        //省中心在检任务总数
        $szx_zj_number =  Db::table('dky_mission')
            ->where('testing_institution', '省中心（电科院）')
            ->where('distribute_time','like',$this_year.'%')
            ->where('status','在检')
            ->count();

        //省中心待检任务总数
        $szx_dj_number =  Db::table('dky_mission')
            ->where('testing_institution', '省中心（电科院）')
            ->where('distribute_time','like',$this_year.'%')
            ->where('status','待检')
            ->count();

        $ret['data']['szx_number'] = $szx_number;
        $ret['data']['szx_ywc_number'] = $szx_ywc_number;
        $ret['data']['szx_hgl'] = $szx_hgl;
        $ret['data']['szx_zj_number'] = $szx_zj_number;
        $ret['data']['szx_dj_number'] = $szx_dj_number;




        //设备类检测量
        $temp_device_finish = $this->getDeviceFinish();

        $temp_device_hg = Db::table('dky_mission')
            ->where('testing_institution', '省中心（电科院）')
            ->field('count(*) as number,device_type')
            ->where('device_type','in',$this->device_list)
            ->where('distribute_time','like',$this_year.'%')
            ->where('conclusion',1)
            ->group('device_type')
            ->select();
        $temp_device_hg = array_column($temp_device_hg,null,'device_type');

        foreach ($temp_device_finish as $k=>$v){
            if ($v['number'] != 0 && isset($temp_device_hg[$k]))
            {
                $pass_rate = round($temp_device_hg[$k]['number'] / $v['number'],2);
                $ret['data']['device'][] = ['name'=>$k,'pass_rate'=>$pass_rate,'number'=>$v['number']];
            }
        }



        //材料类检测完成量
        $temp_material_finish = $this->getMaterialFinish();

        //dump($temp_material_finish);
        $temp_material_hg = Db::table('dky_mission')
            ->field('count(*) as number,device_type')
            ->where('testing_institution', '省中心（电科院）')
            ->where('device_type','in',$this->material_list)
            ->where('distribute_time','like',$this_year.'%')
            ->where('conclusion',1)
            ->group('device_type')
            ->select();
        $temp_material_hg = array_column($temp_material_hg,null,'device_type');
        //dump($temp_material_hg);
        foreach ($temp_material_finish as $k=>$v){
            if ($v['number'] != 0 && isset($temp_material_hg[$k]))
            {
                $pass_rate = round($temp_material_hg[$k]['number'] / $v['number'],2);
                $ret['data']['material'][] = ['name'=>$k,'pass_rate'=>$pass_rate,'number'=>$v['number']];
            }
        }


        return json_encode($ret,320);


    }


    //获取检测人员信息
    public function getStaff()
    {


        $ret = [
            'code'=>1,
            'msg'=>'success',
            'device'=>[],
            'material'=>[],
            ];

        //设备类
        $temp_device = Db::table('dky_staff')
            ->field('count(*) as number,type')
            ->where('property','设备')
            ->where('deletetime',null)
            ->where('status','<>',1)
            ->where('district_id',1)
            ->group('type')
            ->select();
        $temp_device = array_column($temp_device,null,'type');
        $device_a = isset($temp_device['A级检测人员']) ?  $temp_device['A级检测人员']['number'] : 0;
        $device_b = isset($temp_device['B级检测人员']) ?  $temp_device['B级检测人员']['number'] : 0;

        //材料类
        $temp_material = Db::table('dky_staff')
            ->field('count(*) as number,type')
            ->where('property','材料')
            ->where('deletetime',null)
            ->where('status','<>',1)
            ->where('district_id',1)
            ->group('type')
            ->select();
        $temp_material = array_column($temp_material,null,'type');
        $material_a = isset($temp_material['A级检测人员']) ?  $temp_material['A级检测人员']['number'] : 0;
        $material_b = isset($temp_material['B级检测人员']) ?  $temp_material['B级检测人员']['number'] : 0;

        $ret['device']['device_a']['number'] = $device_a;
        $ret['device']['device_b']['number'] = $device_b;
        $ret['material']['material_a']['number']= $material_a;
        $ret['material']['material_b']['number'] = $material_b;

        //设备类
        $temp_device = Db::table('dky_staff')
            ->field('count(*) as number,type')
            ->where('property','设备')
            ->where('deletetime',null)
            ->where('status','<>',1)
            ->where('district_id',2)
            ->group('type')
            ->select();
        $temp_device = array_column($temp_device,null,'type');
        $device_a = isset($temp_device['A级检测人员']) ?  $temp_device['A级检测人员']['number'] : 0;
        $device_b = isset($temp_device['B级检测人员']) ?  $temp_device['B级检测人员']['number'] : 0;

        //材料类
        $temp_material = Db::table('dky_staff')
            ->field('count(*) as number,type')
            ->where('property','材料')
            ->where('deletetime',null)
            ->where('status','<>',1)
            ->where('district_id',2)
            ->group('type')
            ->select();
        $temp_material = array_column($temp_material,null,'type');
        $material_a = isset($temp_material['A级检测人员']) ?  $temp_material['A级检测人员']['number'] : 0;
        $material_b = isset($temp_material['B级检测人员']) ?  $temp_material['B级检测人员']['number'] : 0;

        $ret['device']['device_a']['number_sn'] = $device_a;
        $ret['device']['device_b']['number_sn'] = $device_b;
        $ret['material']['material_a']['number_sn']= $material_a;
        $ret['material']['material_b']['number_sn'] = $material_b;


        //设备类
        $temp_device = Db::table('dky_staff')
            ->field('count(*) as number,type')
            ->where('property','设备')
            ->where('deletetime',null)
            ->where('status','<>',1)
            ->where('district_id',3)
            ->group('type')
            ->select();
        $temp_device = array_column($temp_device,null,'type');
        $device_a = isset($temp_device['A级检测人员']) ?  $temp_device['A级检测人员']['number'] : 0;
        $device_b = isset($temp_device['B级检测人员']) ?  $temp_device['B级检测人员']['number'] : 0;

        //材料类
        $temp_material = Db::table('dky_staff')
            ->field('count(*) as number,type')
            ->where('property','材料')
            ->where('deletetime',null)
            ->where('status','<>',1)
            ->where('district_id',3)
            ->group('type')
            ->select();
        $temp_material = array_column($temp_material,null,'type');
        $material_a = isset($temp_material['A级检测人员']) ?  $temp_material['A级检测人员']['number'] : 0;
        $material_b = isset($temp_material['B级检测人员']) ?  $temp_material['B级检测人员']['number'] : 0;

        $ret['device']['device_a']['number_sz'] = $device_a;
        $ret['device']['device_b']['number_sz'] = $device_b;
        $ret['material']['material_a']['number_sz']= $material_a;
        $ret['material']['material_b']['number_sz'] = $material_b;


        //设备类
        $temp_device = Db::table('dky_staff')
            ->field('count(*) as number,type')
            ->where('property','设备')
            ->where('deletetime',null)
            ->where('status','<>',1)
            ->where('district_id',4)
            ->group('type')
            ->select();
        $temp_device = array_column($temp_device,null,'type');
        $device_a = isset($temp_device['A级检测人员']) ?  $temp_device['A级检测人员']['number'] : 0;
        $device_b = isset($temp_device['B级检测人员']) ?  $temp_device['B级检测人员']['number'] : 0;

        //材料类
        $temp_material = Db::table('dky_staff')
            ->field('count(*) as number,type')
            ->where('property','材料')
            ->where('deletetime',null)
            ->where('status','<>',1)
            ->where('district_id',4)
            ->group('type')
            ->select();
        $temp_material = array_column($temp_material,null,'type');
        $material_a = isset($temp_material['A级检测人员']) ?  $temp_material['A级检测人员']['number'] : 0;
        $material_b = isset($temp_material['B级检测人员']) ?  $temp_material['B级检测人员']['number'] : 0;

        $ret['device']['device_a']['number_sb'] = $device_a;
        $ret['device']['device_b']['number_sb'] = $device_b;
        $ret['material']['material_a']['number_sb']= $material_a;
        $ret['material']['material_b']['number_sb'] = $material_b;


        //测试用，后期uwb接上再改,TODO
       /* $ret['device']['device_a']['working'] = 0;
        $ret['device']['device_a']['in_job'] = 0;
        $ret['device']['device_b']['working'] = 0;
        $ret['device']['device_b']['in_job'] = 0;
        $ret['material']['material_a']['working'] = 0;
        $ret['material']['material_a']['in_job'] = 0;
        $ret['material']['material_b']['working'] = 0;
        $ret['material']['material_b']['in_job'] = 0;*/
        return json_encode($ret,320);
    }


    //获取检测质量情况
    public function getTestingProblem()
    {


        $ret = ['data'=>['device'=>[],'material'=>[]],
            'code'=>1,
            'msg'=>'success'
        ];

        $this_year = date('Y');



        //设备类检测量
        $temp_device_finish = $this->getDeviceFinish();

        //设备类问题量
        $temp_device_hg = Db::table('dky_testing_problem')
            ->field('count(distinct(twins_token)) as number,type')
            ->where('district_id', 1)
            ->where('type','in',$this->device_list)
            ->where('occur_at','like',$this_year.'%')
            ->where('deletetime',null)
            ->group('type')
            ->select();

        $temp_device_hg = array_column($temp_device_hg,null,'type');

        foreach ($temp_device_finish as $k=>$v){
            if ($v['number'] != 0 && isset($temp_device_hg[$k]))
            {
                $problem_rate = round($temp_device_hg[$k]['number'] / $v['number'],2);
                $ret['data']['device'][] = ['name'=>$k,'problem_rate'=>$problem_rate,'number'=>$temp_device_hg[$k]['number']];
            }
        }


        //材料类检测完成量
        $temp_material_finish = $this->getMaterialFinish();

        //材料类问题量
        $temp_material_hg = Db::table('dky_testing_problem')
            ->field('count(distinct(twins_token)) as number,type')
            ->where('district_id', 1)
            ->where('type','in',$this->material_list)
            ->where('occur_at','like',$this_year.'%')
            ->where('deletetime',null)
            ->group('type')
            ->select();

        $temp_material_hg = array_column($temp_material_hg,null,'type');

        foreach ($temp_material_finish as $k=>$v){
            if ($v['number'] != 0 && isset($temp_material_hg[$k]))
            {
                $problem_rate = round($temp_material_hg[$k]['number'] / $v['number'],2);
                $ret['data']['material'][] = ['name'=>$k,'problem_rate'=>$problem_rate,'number'=>$temp_material_hg[$k]['number']];
            }
        }

        return json_encode($ret,320);
    }


    //获取检测时效情况
    public function getTestingDuration()
    {

        $ret = ['data'=>['device'=>[],'material'=>[]],
            'code'=>1,
            'msg'=>'success'
        ];

        $this_year = date('Y');
        //省中心设备类检测量
        $temp_device_finish = $this->getDeviceFinish();
        //省中心材料类检测完成量
        $temp_material_finish = $this->getMaterialFinish();

        //省中心设备类超期任务数
        $temp_device_overtime_number = Db::table('dky_mission')
            ->field('count(*) as number,device_type')
            ->where('testing_institution', '省中心（电科院）')
            ->where('device_type','in',$this->device_list)
            ->where('distribute_time','like',$this_year.'%')
            ->where('overtime_duration','>',0)
            ->group('device_type')
            ->select();

        $temp_device_overtime_number = array_column($temp_device_overtime_number,null,'device_type');

        //省中心材料类超期任务数
        $temp_material_overtime_number = Db::table('dky_mission')
            ->field('count(*) as number,device_type')
            ->where('testing_institution', '省中心（电科院）')
            ->where('device_type','in',$this->material_list)
            ->where('distribute_time','like',$this_year.'%')
            ->where('overtime_duration','>',0)
            ->group('device_type')
            ->select();
        $temp_material_overtime_number = array_column($temp_material_overtime_number,null,'device_type');


        //省中心设备类检测总时长
        $temp_device_duration = Db::table('dky_mission')
            ->field('sum(testing_duration) as number,device_type')
            ->where('testing_institution', '省中心（电科院）')
            ->where('device_type','in',$this->device_list)
            ->where('distribute_time','like',$this_year.'%')
            ->where('finish_time','<>','1970-01-01 00:00:00')
            ->group('device_type')
            ->select();
        $temp_device_duration = array_column($temp_device_duration,null,'device_type');

        //省中心材料类检测总时长
        $temp_material_duration = Db::table('dky_mission')
            ->field('sum(testing_duration) as number,device_type')
            ->where('testing_institution', '省中心（电科院）')
            ->where('device_type','in',$this->material_list)
            ->where('distribute_time','like',$this_year.'%')
            ->where('finish_time','<>','1970-01-01 00:00:00')
            ->group('device_type')
            ->select();
        $temp_material_duration = array_column($temp_material_duration,null,'device_type');



        //省中心设备类平均检测时长 =  设备类检测总时长/完成任务数
        //省中心材料类平均检测时长 =  材料类检测总时长/完成任务数

        //省中心设备类超期率 =  超期任务数/完成任务数
        //省中心材料类超期率 =  超期任务数/完成任务数
        foreach ($temp_device_finish as $k=>$v)
        {

            //检测时长
            if (isset($temp_device_duration[$k])){
               $ret['data']['device']['duration'][] = ['name'=>$k,'number'=>round($temp_device_duration[$k]['number'] / $v['number'],2)];
            }
            //超期率
            if (isset($temp_device_overtime_number[$k])){
                $ret['data']['device']['overtime'][] = ['name'=>$k,'number'=>round($temp_device_overtime_number[$k]['number'] / $v['number'],2)];
            }
        }

        foreach ($temp_material_finish as $k=>$v)
        {
            //检测时长
            if (isset($temp_material_duration[$k])){
                $ret['data']['material']['duration'][] = ['name'=>$k,'number'=>round($temp_material_duration[$k]['number'] / $v['number'],2)];
            }
            //超期率
            if (isset($temp_device_overtime_number[$k])){
                $ret['data']['material']['overtime'][] = ['name'=>$k,'number'=>round($temp_material_overtime_number[$k]['number'] / $v['number'],2)];
            }
        }

        return json_encode($ret,320);

    }

    public function getDeviceFinish()
    {
        $this_year = date('Y');
        //设备类检测完成量
        $temp_device_finish = Db::table('dky_mission')
            ->field('count(*) as number,device_type')
            ->where('testing_institution', '省中心（电科院）')
            ->where('device_type','in',$this->device_list)
            ->where('distribute_time','like',$this_year.'%')
            ->where('finish_time','<>','1970-01-01 00:00:00')
            ->group('device_type')
            ->select();

        $temp_device_finish = array_column($temp_device_finish,null,'device_type');
        return $temp_device_finish;

    }

    public function getMaterialFinish()
    {
        $this_year = date('Y');
        //材料类检测完成量
        $temp_material_finish = Db::table('dky_mission')
            ->field('count(*) as number,device_type')
            ->where('testing_institution', '省中心（电科院）')
            ->where('device_type','in',$this->material_list)
            ->where('distribute_time','like',$this_year.'%')
            ->where('finish_time','<>','1970-01-01 00:00:00')
            ->group('device_type')
            ->select();

        $temp_material_finish = array_column($temp_material_finish,null,'device_type');
        return $temp_material_finish;
    }

    /**
     * @param $unity_token
     * 返回检测设备基本信息
     */
    public function getDeviceInfo($unity_token='')
    {
        $ret = [
          'code'=>1,
          'msg'=>'success',
          'data'=>[]
        ];
        $unity_token = input('param.unity_token','','addslashes,trim,htmlspecialchars,strip_tags');
        $district = input('param.district','','addslashes,trim,htmlspecialchars,strip_tags');
        if (!$unity_token || !$district)
        {
            $ret['code'] = 0;
            $ret['msg'] = '请输入正确参数';
            return json_encode($ret,320);
        }
        $district_id = $this->district_reflection[$district];

        $res = Db::table('dky_device')
            ->field('name,model_number,zcbh,company,zsyxq,jljzdw,principal,clfw,clbqdd,experiment_ids,device_no')
            ->where('unity_token',$unity_token)
            ->where('district_id',$district_id)
            ->where('deletetime',null)
            ->find();


        if (!$res)
        {
            $ret['code'] = 2;
            $ret['msg'] = '数据库无此unity_token对应的信息，请到后台维护';
            return json_encode($ret,320);
        }


        $res['experiment_names'] = $this->getExperimentName($res);
        $device_no = $res['device_no'];
        $res['maintain'] = $this->getMaintainInfo($device_no);

        $ret['data'] = $res;
        return json_encode($ret,320);
    }

    /**
     * @param $unity_token
     * 返回Agv基本信息
     */
    public function getAgvInfo($unity_token='')
    {
        $ret = [
            'code'=>1,
            'msg'=>'success',
            'data'=>[]
        ];
        $unity_token = input('param.unity_token','','addslashes,trim,htmlspecialchars,strip_tags');
        $district = input('param.district','','addslashes,trim,htmlspecialchars,strip_tags');
        if (!$unity_token || !$district)
        {
            $ret['code'] = 0;
            $ret['msg'] = '请输入正确参数';
            return json_encode($ret,320);
        }
        $district_id = $this->district_reflection[$district];

        $res = Db::table('dky_agv')
            ->field('name,model_number,zcbh,company,zsyxq,jljzdw,principal,device_no')
            ->where('unity_token',$unity_token)
            ->where('district_id',$district_id)
            ->where('deletetime',null)
            ->find();
        if (!$res)
        {
            $ret['code'] = 2;
            $ret['msg'] = '数据库无此unity_token对应的信息，请到后台维护';
            return json_encode($ret,320);
        }

        $device_no = $res['device_no'];
        $res['maintain'] = $this->getMaintainInfo($device_no);
        $ret['data'] = $res;
        return json_encode($ret,320);

    }

    /**
     * @param $unity_token
     * 返回工位基本信息
     */
    public function getStationInfo($unity_token='')
    {
        $ret = [
            'code'=>1,
            'msg'=>'success',
            'data'=>[]
        ];

        $unity_token = input('param.unity_token','','addslashes,trim,htmlspecialchars,strip_tags');
        $district = input('param.district','','addslashes,trim,htmlspecialchars,strip_tags');
        if (!$unity_token || !$district)
        {
            $ret['code'] = 0;
            $ret['msg'] = '请输入正确参数';
            return json_encode($ret,320);
        }
        $district_id = $this->district_reflection[$district];

        $res = Db::table('dky_station')
            ->field('name,model_number,station_token,zcbh,company,experiment_ids,device_no')
            ->where('unity_token',$unity_token)
            ->where('district_id',$district_id)
            ->where('deletetime',null)
            ->find();
        if (!$res)
        {
            $ret['code'] = 2;
            $ret['msg'] = '数据库无此unity_token对应的信息，请到后台维护';
            return json_encode($ret,320);
        }

        $res['experiment_names'] = $this->getExperimentName($res);
        $device_no = $res['device_no'];
        $res['maintain'] = $this->getMaintainInfo($device_no);
        $ret['data'] = $res;
        return json_encode($ret,320);
    }

    /**
     * @param $unity_token
     * 返回存储设备基本信息
     */
    public function getStorageInfo($unity_token='')
    {
        $ret = [
            'code'=>1,
            'msg'=>'success',
            'data'=>[]
        ];
        $unity_token = input('param.unity_token','','addslashes,trim,htmlspecialchars,strip_tags');
        $district = input('param.district','','addslashes,trim,htmlspecialchars,strip_tags');
        if (!$unity_token || !$district)
        {
            $ret['code'] = 0;
            $ret['msg'] = '请输入正确参数';
            return json_encode($ret,320);
        }
        $district_id = $this->district_reflection[$district];

        $res = Db::table('dky_storage_rack')
            ->field('name,model_number,zcbh,company,zsyxq,jljzdw,principal,device_no')
            ->where('unity_token',$unity_token)
            ->where('district_id',$district_id)
            ->where('deletetime',null)
            ->find();
        if (!$res)
        {
            $ret['code'] = 2;
            $ret['msg'] = '数据库无此unity_token对应的信息，请到后台维护';
            return json_encode($ret,320);
        }

        $device_no = $res['device_no'];
        $res['maintain'] = $this->getMaintainInfo($device_no);
        $ret['data'] = $res;
        return json_encode($ret,320);
    }

    /**
     * @param $unity_token
     * 返回人员基本信息
     */
    public function getStaffInfo()
    {
        //TODO
        //人员的接口与其余四个设备类接口有区别
        //人员信息是后台先去调用uwb接口，获取到人员的uwb_id以及人员位置信息，再根据uwb_id去dky_staff表中查询人员基本信息，
        //整合人员位置信息以及人员基本信息后返回给前端unity刷模型及显示信息
    }

    public function getExperimentName($res)
    {
        //跟据可做试验的id，查询其名字
        $experiment_ids = $res['experiment_ids'];

        $ids_arr = explode(',',$experiment_ids);
        $experiment = Db::table('experiment')
            ->column('name','id');
        $experiment_names ='';
        foreach ($ids_arr as $k=>$v)
        {
            if (isset($experiment[$v]))
            {
                $experiment_names .= $experiment[$v].',';
            }
        }
        //去除最右边的'，'
        $experiment_names = rtrim($experiment_names,',');
        return $experiment_names;
    }


    //获取某device_no对应的最新一条运维记录
    public function getMaintainInfo($device_no)
    {
        $res = Db::table('maintenance_log')
            ->field('maintenance_at as date,description')
            ->where('device_no',$device_no)
            ->where('deletetime',null)
            ->order('createtime','desc')
            ->find();

        if (!$res)
        {
            $res = ['date'=>'','description'=>''];
        }else{
            $res['date'] = substr($res['date'],0,-9);
        }
        return $res;
    }

    //获取所有人员信息，用于前端显示人员看板
    public function getAllStaff()
    {
        $ret = [
            'code'=>1,
            'msg'=>'success',
            'data'=>[]
        ];
        $res = Db::table('dky_staff')
            ->field('name,sex,avatar,number,skill_rank,job')
            ->where('district_id',1)
            ->where('deletetime',null)
            ->where('status','<>',1)
            ->select();
        $ret['data'] = $res;
        return json_encode($ret,320);

    }

    //获取设备类和材料类任务的状态，包括待检，在检，已检
    public function getMissionStatus()
    {

        $ret = [
            'code'=>1,
            'msg'=>'success',
            'data'=>[]
        ];
        //统计设备类
        $res = Db::table('dky_mission')
            ->field('count(*) as number,device_type,status')
            ->where('device_type','in',$this->device_list)
            ->where('testing_institution','省中心（电科院）')
            ->group('device_type,status')
            ->select();

        $arr = [];
       foreach ($res as $k=>$v)
        {
            $arr[$v['device_type']][$v['status']] = $v['number'];
        }


       foreach ($arr as $k=>$v)
        {
            $arr[$k]['总数'] = (isset($arr[$k]['在检']) ? $arr[$k]['在检'] : 0) + (isset($arr[$k]['待检']) ? $arr[$k]['待检'] : 0) + (isset($arr[$k]['已检']) ? $arr[$k]['已检'] : 0);
        }




       //补数据
        foreach ($this->device_list as $k=>$v)
        {
            if (!isset($arr[$v]))
            {
                $arr[$v] = [
                    '在检'=>0,
                    '待检'=>0,
                    '已检'=>0,
                    '总数'=>0,
                ];
            }
        }
       $ret['data']['device'] = $arr;


       //统计材料类

        $res = Db::table('dky_mission')
            ->field('count(*) as number,device_type,status')
            ->where('device_type','in',$this->material_list)
            ->where('testing_institution','省中心（电科院）')
            ->group('device_type,status')
            ->select();

        $arr = [];
        foreach ($res as $k=>$v)
        {
            $arr[$v['device_type']][$v['status']] = $v['number'];
        }


        foreach ($arr as $k=>$v)
        {
            $arr[$k]['总数'] = (isset($arr[$k]['在检']) ? $arr[$k]['在检'] : 0) + (isset($arr[$k]['待检']) ? $arr[$k]['待检'] : 0) + (isset($arr[$k]['已检']) ? $arr[$k]['已检'] : 0);
        }




        //补数据
        foreach ($this->material_list as $k=>$v)
        {
            if (!isset($arr[$v]))
            {
                $arr[$v] = [
                    '在检'=>0,
                    '待检'=>0,
                    '已检'=>0,
                    '总数'=>0,
                ];
            }
        }

        $ret['data']['material'] = $arr;

        return json_encode($ret,320);

    }




    /**
     * 存储思创发送的任务状态数据
     * @return false|string
     */
    public function sendMissionStatus()
    {
        $ret = array(
            'code'=>1,
            'msg'=>'success'
        );
        //二次盲样号,实际上是数字孪生id
        $s_code = input('param.s_code','','addslashes,trim,htmlspecialchars,strip_tags');
        //试验名称
        $e_name = input('param.e_name','','addslashes,trim,htmlspecialchars,strip_tags');
        //检测机构
        $jcjg = input('param.jcjg','','addslashes,trim,htmlspecialchars,strip_tags');
        //状态码
        $flag = input('param.flag',0,'addslashes,trim,htmlspecialchars,strip_tags');
        //工位
        $station = input('param.station',0,'addslashes,trim,htmlspecialchars,strip_tags');
        //任务号
        $mission_no = input('param.mission_no',0,'addslashes,trim,htmlspecialchars,strip_tags');
        //结果数据json字符串
        $json = input('param.json','','trim,xss_clean');
        if (!$s_code  || !$jcjg || !$flag ||!$station || !$mission_no)
        {
            $ret['code'] = 0;
            $ret['msg'] = '错误，二次盲样号、检测机构、状态码、工位名称、任务号不能为空';
            return json_encode($ret,320);
        }


        if (!in_array($jcjg,['苏南分中心','省中心（电科院）','苏中分中心','苏北分中心']))
        {
            $ret['code'] = 3;
            $ret['msg'] = '错误，检测机构参数非法，请参考接口文档';
            return json_encode($ret,320);
        }

        if (!in_array($flag,['1','2','3','4']))
        {
            $ret['code'] = 4;
            $ret['msg'] = '错误，flag状态码非法，请参考接口文档';
            return json_encode($ret,320);
        }


        //TODO 根据flag状态码更新dky_mission表中该任务的状态
        //flag 为1时将任务状态改为在检，并且把试验状态改为在检
        if ($flag == 1)
        {
            Db::table('dky_mission')
                ->where('twins_token',$s_code)
                ->update(['status'=>'在检']);
            Db::table('dky_mission_experiment')
                ->where('twins_token',$s_code)
                ->where('experiment',$e_name)
                ->update(['status'=>'在检','station'=>$station]);
        }elseif ($flag == 2){
            //flag 为2的时候，将试验状态改为在检
            Db::table('dky_mission_experiment')
                ->where('twins_token',$s_code)
                ->where('experiment',$e_name)
                ->update(['status'=>'在检','station'=>$station]);
        }elseif ($flag == 3){
            //flag 为3的时候，将试验状态改为已检
            Db::table('dky_mission_experiment')
                ->where('twins_token',$s_code)
                ->where('experiment',$e_name)
                ->update(['status'=>'已检','station'=>$station]);
        }elseif ($flag == 4) {
            //flag 为4时将任务状态改为已检
            Db::table('dky_mission')
                ->where('twins_token',$s_code)
                ->update(['status'=>'已检']);

            //TODO 同时要检测试验的完整性和顺序性

        }

        $res = Db::table('dky_mission_status')->insert(
            [
                's_code'=>$s_code,
                'e_name'=>$e_name,
                'jcjg'=>$jcjg,
                'flag'=>$flag,
                'station'=>$station,
                'mission_no'=>$mission_no,
                'json'=>$json,
            ]
        );

        if (!$res)
        {
            $ret['code'] = 2;
            $ret['msg'] = '插入失败，请重试';
        }else{
            $ret['code'] = 1;
            $ret['msg'] = 'success,插入成功';
        }
        return json_encode($ret,320);
    }

    /**
     * 发送结果和过程数据，用以进行质量控制
     */
    public function sendExperimentData()
    {

        $ret = array(
            'code'=>1,
            'msg'=>'success'
        );
        //二次盲样号
        $s_code = input('param.s_code','','addslashes,trim,htmlspecialchars,strip_tags');
        //试验名称
        $e_name = input('param.e_name','','addslashes,trim,htmlspecialchars,strip_tags');
        //物资类别
        $type = input('param.type','','addslashes,trim,htmlspecialchars,strip_tags');
        //检测机构
        $jcjg = input('param.jcjg','','addslashes,trim,htmlspecialchars,strip_tags');
        //阶段
        $period = input('param.period','','addslashes,trim,htmlspecialchars,strip_tags');
        //阶段状态码
        $period_status = input('param.period_status','','addslashes,trim,htmlspecialchars,strip_tags');
        //工位
        $station = input('param.station',0,'addslashes,trim,htmlspecialchars,strip_tags');
        //任务号
        $mission_no = input('param.mission_no',0,'addslashes,trim,htmlspecialchars,strip_tags');
        //参数json字符串
        $json = input('param.json','','trim,xss_clean');
        if (!$s_code || !$e_name || !$type || !$jcjg || !$station || !$mission_no)
        {
            $ret['code'] = 0;
            $ret['msg'] = '错误，二次盲样号、试验名称、检测机构、物资类别、工位名称、任务号不能为空';
            return json_encode($ret,320);
        }

        //参数列表
        $param = [
            's_code'=>$s_code,
            'e_name'=>$e_name,
            'jcjg'=>$jcjg,
            'type'=>$type,
            'station'=>$station,
            'mission_no'=>$mission_no,
            'period'=>$period,
            'period_status'=>$period_status,
            'json'=>$json,
        ];
        $res = Db::table('dky_experiment_data')->insert($param);

        if (!$res)
        {
            $ret['code'] = 2;
            $ret['msg'] = '插入失败，请重试';
        }else{
            $ret['code'] = 1;
            $ret['msg'] = 'success,插入成功';
        }


        $this->disposeExperimentData($param);

        return json_encode($ret,320);
    }


    /**
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 根据device_no获取该设备的运维记录
     */
    public function getMaintenanceByDeviceNo()
    {
        $ret = array(
            'code'=>1,
            'msg'=>'success'
        );
        $device_no = input('param.device_no',0,'addslashes,trim,htmlspecialchars,strip_tags');
        $page = input('param.page',0,'addslashes,trim,htmlspecialchars,strip_tags');
        $limit = input('param.limit',0,'addslashes,trim,htmlspecialchars,strip_tags');
        if (!$device_no || !$page || !$limit)
        {
            $ret['code'] = 0;
            $ret['msg'] = '错误，设备ID，页码，每页显示数量不能为空';
            return json_encode($ret,320);
        }

        $offset = ($page-1) * $limit;
        $res = Db::table('maintenance_log')
            ->where('device_no',$device_no)
            ->where('deletetime',null)
            ->limit($offset,$limit)
            ->select();

        foreach ($res as $k=>$v)
        {
            $res[$k]['user'] =  $v['operator'];
            unset($res[$k]['operator']);
        }

        $ret['data'] = $res;
        $ret['page'] = $page;
        return json_encode($ret,320);
    }

    /**
     * 统计样品合格率看板
     */
    public function getSamplePassRate()
    {

        $ret = array(
            'code'=>1,
            'msg'=>'success'
        );

        $today = date('Y-m-d');
        $res_today = Db::table('dky_mission')
            ->field('count(*) as number,conclusion')
            ->where('finish_time','like',$today.'%')
            ->group('conclusion')
            ->select();

        $res_today = array_column($res_today,null,'conclusion');
        $pass_number_today = isset($res_today[1]['number']) ? $res_today[1]['number'] : 0;
        $number_today = Db::table('dky_mission')
            ->where('finish_time','like',$today.'%')
            ->count();
        $pass_rate_today = $number_today != 0 ? round($pass_number_today / $number_today,2) : 0;

        $res_all = Db::table('dky_mission')
            ->field('count(*) as number,conclusion')
            ->where('finish_time','<>','1970-01-01 00:00:00')
            ->group('conclusion')
            ->select();

        $res_all = array_column($res_all,null,'conclusion');
        $pass_number_all = isset($res_all[1]['number']) ? $res_all[1]['number'] : 0;
        $number_all = Db::table('dky_mission')
            ->where('finish_time','<>','1970-01-01 00:00:00')
            ->count();

        $pass_rate_all = $number_all != 0 ? round($pass_number_all / $number_all,2) : 0;

        //当日检测完成总数
        $ret['data']['number_today'] = $number_today;
        //当日检测合格率
        $ret['data']['pass_rate_today'] = $pass_rate_today;
        //累计完成总数
        $ret['data']['number_all'] = $number_all;
        //累计合格率
        $ret['data']['pass_rate_all'] = $pass_rate_all;
        return json_encode($ret,320);
    }


    /**
     * 获取温湿度
     */
    public function getEnvironment()
    {

        //TODO
        //临时，后期根据思创温湿度接口修改
        $ret = array(
            'code'=>1,
            'msg'=>'success',
            'data'=>[
                //温度
                'temperature'=>'35',
                //湿度
                'humidity'=>'80'
            ]
        );

        return json_encode($ret,320);
    }


    //返回各区域下设备名称和unity_id的对应
    public function showNameAndUnityToken()
    {
        $ret = array(
            'code'=>1,
            'msg'=>'success',
            'data'=>[]
        );
        $res = Db::table('dky_device')
            ->field('district_id,name,unity_token')
            ->where('deletetime',null)
            ->select();


        $res1 = Db::table('dky_agv')
            ->field('district_id,name,unity_token')
            ->where('deletetime',null)
            ->select();

        $res2 = Db::table('dky_station')
            ->field('district_id,name,unity_token')
            ->where('deletetime',null)
            ->select();

        $res3 = Db::table('dky_storage_rack')
            ->field('district_id,name,unity_token')
            ->where('deletetime',null)
            ->select();

        $arr = [];
        foreach ($res as $k=>$v)
        {
            $arr[$this->district_reflection_inverse[$v['district_id']]][] = $v;
        }

        foreach ($res1 as $k=>$v)
        {
            $arr[$this->district_reflection_inverse[$v['district_id']]][] = $v;
        }

        foreach ($res2 as $k=>$v)
        {
            $arr[$this->district_reflection_inverse[$v['district_id']]][] = $v;
        }

        foreach ($res3 as $k=>$v)
        {
            $arr[$this->district_reflection_inverse[$v['district_id']]][] = $v;
        }
        $ret['data'] = $arr;

       return json_encode($ret,320);
    }


    /**
     * 返回所有未处理的告警信息
     */

    public function getAlert()
    {

        return '请联系管理员';
        $ret = array(
            'code'=>1,
            'msg'=>'success'
        );

        $district = input('param.district',0,'addslashes,trim,htmlspecialchars,strip_tags');
        $page = input('param.page',0,'addslashes,trim,htmlspecialchars,strip_tags');
        $limit = input('param.limit',0,'addslashes,trim,htmlspecialchars,strip_tags');
        $type = input('param.type','','addslashes,trim,htmlspecialchars,strip_tags');

        if (!$page || !$limit || !$district || !$type)
        {
            $ret['code'] = 0;
            $ret['msg'] = '错误，页码，每页显示数量,区域名称,告警类型不能为空';
            return json_encode($ret,320);
        }

        //前台传过来的是城市名称，换成地区id
        if (!isset($this->district_reflection[$district]))
        {
            $ret['code'] = 2;
            $ret['msg'] = '区域名称不存在，请填入南京，苏州，泰州，徐州之一';
            return json_encode($ret,320);
        }else{
            $district_id = $this->district_reflection[$district];
        }

        $offset = ($page-1) * $limit;

        $res = Db::table('dky_testing_problem')
            ->where('status',0)
            ->where('district_id',$district_id)
            ->where('problem_type',$type)
            ->where('deletetime',null)
            ->order('occur_at','desc')
            ->limit($offset,$limit)
            ->select();


        //处理二维数组
        //$res = $this->dispose_double_dimension_array($res,'problem_type');

        $ret['data'] = $res;
        $ret['page'] = $page;
        return json_encode($ret,320);
    }

    /**
     * @param $param
     * 处理试验数据，检测是否存在试验质量告警
     */
    public function disposeExperimentData($param)
    {
        //样品二维码
        $s_code = $param['s_code'];
        //试验名称
        $e_name = $param['e_name'];
        //检测机构
        $jcjg = $param['jcjg'];
        //物资类别
        $type = $param['type'];
        //工位
        $station = $param['station'];
        //结果数据
        $json = $param['json'];

        switch ($type){
            case "油浸式配电变压器":
                if (strpos($e_name,'温升试验') !== false){
                    $this->check_pdbyq_ws($param);
                }
                if (strpos($e_name,'绕组电阻测量') !== false)
                {
                    $this->check_pdbyq_rzdzcl('油浸式',$param);
                    break;
                }
                if (strpos($e_name,'空载电流及空载损耗试验') !== false)
                {
                    $this->check_pdbyq_kzshhkzdlcl($param);
                    break;
                }
                if (strpos($e_name,'短路阻抗及负载损耗试验') !== false)
                {
                    $this->check_pdbyq_dlzkhfzshcl('油浸式',$param);
                    break;
                }
                if (strpos($e_name,'外施耐压试验') !== false)
                {
                    $this->check_pdbyq_wsny($param);
                    break;
                }
                if (strpos($e_name,'感应耐压试验') !== false)
                {
                    $this->check_pdbyq_gyny($param);
                    break;
                }
                if (strpos($e_name,'绕组对地及绕组间直流绝缘电阻测量') !== false)
                {
                    $this->check_pdbyq_rzdd($param);
                    break;
                }

                break;
            case "配电变压器":
                if (strpos($e_name,'温升试验') !== false){
                    $this->check_pdbyq_ws($param);
                }
                if (strpos($e_name,'绕组电阻测量') !== false)
                {
                    $this->check_pdbyq_rzdzcl('油浸式',$param);
                    break;
                }
                if (strpos($e_name,'空载电流及空载损耗试验') !== false)
                {
                    $this->check_pdbyq_kzshhkzdlcl($param);
                    break;
                }
                if (strpos($e_name,'短路阻抗及负载损耗试验') !== false)
                {
                    $this->check_pdbyq_dlzkhfzshcl('油浸式',$param);
                    break;
                }
                if (strpos($e_name,'外施耐压试验') !== false)
                {
                    $this->check_pdbyq_wsny($param);
                    break;
                }
                if (strpos($e_name,'感应耐压试验') !== false)
                {
                    $this->check_pdbyq_gyny($param);
                    break;
                }
                if (strpos($e_name,'绕组对地及绕组间直流绝缘电阻测量') !== false)
                {
                    $this->check_pdbyq_rzdd($param);
                    break;
                }

                break;
            case "干式配电变压器":
                if (strpos($e_name,'温升试验') !== false){
                    $this->check_pdbyq_ws($param);
                }
                if (strpos($e_name,'绕组直流电阻测量') !== false)
                {
                    $this->check_pdbyq_rzdzcl('干式',$param);
                    break;
                }
                if (strpos($e_name,'空载电流及空载损耗试验') !== false)
                {
                    $this->check_pdbyq_kzshhkzdlcl($param);
                    break;
                }
                if (strpos($e_name,'短路阻抗及负载损耗试验') !== false)
                {
                    $this->check_pdbyq_dlzkhfzshcl('干式',$param);
                    break;
                }
                if (strpos($e_name,'外施耐压试验') !== false)
                {
                    $this->check_pdbyq_wsny($param);
                    break;
                }

                if (strpos($e_name,'感应耐压试验') !== false)
                {
                    $this->check_pdbyq_gyny($param);
                    break;
                }
                if (strpos($e_name,'绕组绝缘电阻试验') !== false)
                {
                    $this->check_pdbyq_rzdd($param);
                    break;
                }
                break;
            case "干式变压器":
                if (strpos($e_name,'温升试验') !== false){
                    $this->check_pdbyq_ws($param);
                }
                if (strpos($e_name,'绕组直流电阻测量') !== false)
                {
                    $this->check_pdbyq_rzdzcl('干式',$param);
                    break;
                }
                if (strpos($e_name,'空载电流及空载损耗试验') !== false)
                {
                    $this->check_pdbyq_kzshhkzdlcl($param);
                    break;
                }
                if (strpos($e_name,'短路阻抗及负载损耗试验') !== false)
                {
                    $this->check_pdbyq_dlzkhfzshcl('干式',$param);
                    break;
                }
                if (strpos($e_name,'外施耐压试验') !== false)
                {
                    $this->check_pdbyq_wsny($param);
                    break;
                }

                if (strpos($e_name,'感应耐压试验') !== false)
                {
                    $this->check_pdbyq_gyny($param);
                    break;
                }
                if (strpos($e_name,'绕组绝缘电阻试验') !== false)
                {
                    $this->check_pdbyq_rzdd($param);
                    break;
                }
                break;
            case "隔离开关（35kV及以下）":
                if ($e_name == '温升试验'){
                    $this->check_glkg_ws($param);
                    break;
                }
                if ($e_name == '主回路回路电阻试验（温升后）'){
                    $this->check_glkg_hldzcl($param);
                    break;
                }
                if ($e_name == '主回路绝缘电阻试验'){
                    $this->check_glkg_jysy($param);
                    break;
                }
                break;
            case "避雷器（10kV~35kV）":
                if ($e_name == '持续电流试验及工频参考电压试验'){
                    $this->check_blq_gpck($param);
                    break;
                }
                if ($e_name == '避雷器直流参考电压试验及0.75倍直流参考电压下漏电流试验'){
                    $this->check_blq_zlck($param);
                    break;
                }
                if ($e_name == '避雷器直流参考电压试验及0.75倍直流参考电压下漏电流试验(密封后)'){
                    $this->check_blq_mf($param);
                    break;
                }
                break;
            case "高压开关柜":
                if (strpos($e_name,'温升试验') !== false){
                    $this->check_gykgg_ws($param);
                    break;
                }
                if (strpos($e_name,'主回路交流耐压试验') !== false){
                    $this->check_gykgg_gpnysy($param);
                    break;
                }

                 if (strpos($e_name,'主回路回路电阻试验（温升前）') !== false){
                     $this->check_gykgg_zhlhldz($param);
                     break;
                 }
                break;
            case "环网柜":
                if (strpos($e_name,'温升试验') !== false){
                    $this->check_hwg_ws($param);
                    break;
                }
                if (strpos($e_name,'主回路交流耐压试验') !== false){
                    $this->check_hwg_gpnysy($param);
                    break;
                }
                if (strpos($e_name,'主回路回路电阻试验（温升前）') !== false){
                    $this->check_hwg_zhlhldz($param);
                    break;
                }

                if (strpos($e_name,'局部放电试验') !== false){
                    $this->check_hwg_jbfd($param);
                    break;
                }
                break;
            case "柱上开关设备":
                if (strpos($e_name,'温升试验') !== false){
                    $this->check_zskg_ws($param);
                    break;
                }
                if (strpos($e_name,'主回路交流耐压试验') !== false){
                    $this->check_zskg_gpnysy($param);
                    break;
                }
                if (strpos($e_name,'主回路回路电阻试验（温升前）') !== false){
                    $this->check_zskg_zhlhldz($param);
                    break;
                }
                if (strpos($e_name,'机械操作试验') !== false){
                    $this->check_zskg_jxcz($param);
                    break;
                }
                if (strpos($e_name,'机械特性试验') !== false){
                    $this->check_zskg_jxtx($param);
                    break;
                }

                break;
            case "电缆分支箱（10kV~35kV）":
                if (strpos($e_name,'温升试验') !== false){
                    $this->check_dlfzx_ws($param);
                    break;
                }
                if (strpos($e_name,'主回路交流耐压试验') !== false){
                    $this->check_dlfzx_gpnysy($param);
                    break;
                }
                if (strpos($e_name,'主回路回路电阻试验（温升前）') !== false){
                    $this->check_dlfzx_zhlhldz($param);
                    break;
                }
                break;
            default:
                echo 3;
        }

    }

    public function check_dlfzx_zhlhldz($param)
    {
        $this->check_glkg_hldzcl($param);
    }

    public function check_dlfzx_gpnysy($param)
    {
        $this->check_glkg_jysy($param);
    }

    public function check_dlfzx_ws($param)
    {
        $this->check_glkg_ws($param);
    }


    public function check_zskg_jxtx($param)
    {
        //样品二维码
        $s_code = $param['s_code'];
        //试验名称
        $e_name = $param['e_name'];
        //检测机构
        $jcjg = $param['jcjg'];
        if (isset($this->name_reflection[$jcjg])){
            $jcjg_id = $this->name_reflection[$jcjg];
        }else{
            $jcjg_id = 0;
            $this->saveLog('找不到该区域对应的区域id：'.$jcjg,'试验质量服务所传区域错误');
        }
        //物资类别
        $type = $param['type'];
        //工位
        $station = $param['station'];
        //结果数据
        $json = $param['json'];
        if (isset(json_decode($json,true)[0]))
        {
            $json_arr = json_decode($json,true)[0];
        }else{
            $json_arr = json_decode($json,true);
        }

        $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,'A相分闸时间','分闸时间不符合要求',$e_name.'所传字段缺少参数：A相分闸时间',['<'=>40],1);
        $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,'A相合闸时间','合闸时间不符合要求',$e_name.'所传字段缺少参数：A相合闸时间',['<'=>60],1);

    }

    public function check_zskg_jxcz($param)
    {
        //样品二维码
        $s_code = $param['s_code'];
        //试验名称
        $e_name = $param['e_name'];
        //检测机构
        $jcjg = $param['jcjg'];
        if (isset($this->name_reflection[$jcjg])){
            $jcjg_id = $this->name_reflection[$jcjg];
        }else{
            $jcjg_id = 0;
            $this->saveLog('找不到该区域对应的区域id：'.$jcjg,'试验质量服务所传区域错误');
        }
        //物资类别
        $type = $param['type'];
        //工位
        $station = $param['station'];
        //结果数据
        $json = $param['json'];
        if (isset(json_decode($json,true)[0]))
        {
            $json_arr = json_decode($json,true)[0];
        }else{
            $json_arr = json_decode($json,true);
        }
        if (isset($json_arr["试验部位"])){
            $sybw = $json_arr["试验部位"];
            if ($sybw == '额定电压'){
                $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,'试验次数','试验次数不符合要求',$e_name.'所传字段缺少参数：试验次数',['='=>5],1);
            }
            if ($sybw == '110%'){
                $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,'试验次数','试验次数不符合要求',$e_name.'所传字段缺少参数：试验次数',['='=>5],1);
            }
            if ($sybw == '85%'){
                $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,'试验次数','试验次数不符合要求',$e_name.'所传字段缺少参数：试验次数',['='=>5],1);
            }
            if ($sybw == '30%'){
                $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,'试验次数','试验次数不符合要求',$e_name.'所传字段缺少参数：试验次数',['='=>5],1);
            }
        }else{
            $this->saveLog($type.$e_name.'所传字段缺少参数：试验部位','试验质量服务缺少字段');

        }

        //
    }   
    public function check_zskg_zhlhldz($param)
    {
        $this->check_glkg_hldzcl($param);
    }

    public function check_zskg_gpnysy($param)
    {
        $this->check_glkg_jysy($param);
    }

    public function check_zskg_ws($param)
    {
        $this->check_glkg_ws($param);
    }

    //环网柜-局部放电试验
    public function check_hwg_jbfd($param)
    {
        //样品二维码
        $s_code = $param['s_code'];
        //试验名称
        $e_name = $param['e_name'];
        //检测机构
        $jcjg = $param['jcjg'];
        if (isset($this->name_reflection[$jcjg])){
            $jcjg_id = $this->name_reflection[$jcjg];
        }else{
            $jcjg_id = 0;
            $this->saveLog('找不到该区域对应的区域id：'.$jcjg,'试验质量服务所传区域错误');
        }
        //物资类别
        $type = $param['type'];
        //工位
        $station = $param['station'];
        //结果数据
        $json = $param['json'];
        if (isset(json_decode($json,true)[0]))
        {
            $json_arr = json_decode($json,true)[0];
        }else{
            $json_arr = json_decode($json,true);
        }
        //额定电压
        $eddy = $json_arr['额定电压'];
        $yjdy = $json_arr['预加电压'];
        if ((($yjdy - $eddy) / $eddy) < 0.3){
            $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'施加电压不符合要求',0);
        }
        //测量电压
        $cldy = $json_arr['测量电压'];
        if ((abs(($cldy - 1.1*$eddy)/1.1*$eddy) >= 0.01)){

            $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'施加额定电压的值不符合要求',0);

        }
    }
    //环网柜 -电流
    public function check_hwg_zhlhldz($param)
    {
        $this->check_glkg_hldzcl($param);
    }
    //环网柜-工频耐压试验
    public function check_hwg_gpnysy($param)
    {
        $this->check_glkg_jysy($param);
    }


    //环网柜-温升
    public function check_hwg_ws($param)
    {
        $this->check_glkg_ws($param);
    }

    //高压开关柜-主回路电阻测量
    public function check_gykgg_zhlhldz($param)
    {
        $this->check_glkg_hldzcl($param);
    }
    //高压开关柜-工频耐压试验
    public function check_gykgg_gpnysy($param)
    {
       $this->check_glkg_jysy($param);
    }
    //高压开关柜 温升
    public function check_gykgg_ws($param)
    {
       $this->check_glkg_ws($param);
    }

    public function check_blq_mf($param)
    {
        //样品二维码
        $s_code = $param['s_code'];
        //试验名称
        $e_name = $param['e_name'];
        //检测机构
        $jcjg = $param['jcjg'];
        if (isset($this->name_reflection[$jcjg])){
            $jcjg_id = $this->name_reflection[$jcjg];
        }else{
            $jcjg_id = 0;
            $this->saveLog('找不到该区域对应的区域id：'.$jcjg,'试验质量服务所传区域错误');
        }
        //物资类别
        $type = $param['type'];
        //工位
        $station = $param['station'];
        //结果数据
        $json = $param['json'];
        if (isset(json_decode($json,true)[0]))
        {
            $json_arr = json_decode($json,true)[0];
        }else{
            $json_arr = json_decode($json,true);
        }

        if (isset($json_arr['1mA电压(密封前)']) && isset($json_arr['1mA电压'])){
            if (abs(($json_arr['1mA电压'] - $json_arr['1mA电压(密封前)']) / $json_arr['1mA电压(密封前)']) > 0.05 ){
                $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'试验前后直流参考电压的差值不符合要求',0);
            }
        }

        if (isset($json_arr['0.75mA电流(密封前)']) && isset($json_arr['0.75mA电流'])){
            if (abs(($json_arr['0.75mA电流'] - $json_arr['0.75mA电流(密封前)'])) >= 20 ){
                $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'试验前后漏电流的差值不符合要求',0);
            }
        }

    }
    public function check_blq_zlck($param)
    {
        //样品二维码
        $s_code = $param['s_code'];
        //试验名称
        $e_name = $param['e_name'];
        //检测机构
        $jcjg = $param['jcjg'];
        if (isset($this->name_reflection[$jcjg])){
            $jcjg_id = $this->name_reflection[$jcjg];
        }else{
            $jcjg_id = 0;
            $this->saveLog('找不到该区域对应的区域id：'.$jcjg,'试验质量服务所传区域错误');
        }
        //物资类别
        $type = $param['type'];
        //工位
        $station = $param['station'];
        //结果数据
        $json = $param['json'];
        if (isset(json_decode($json,true)[0]))
        {
            $json_arr = json_decode($json,true)[0];
        }else{
            $json_arr = json_decode($json,true);
        }

        //泄漏电流不用检测
        //施加电压
        $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,'1mA电压','施加电压值不符合要求',$e_name.'所传字段缺少参数：1mA电压',['>='=>24],1);

        //0.75mA电流
        $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,'0.75mA电流','施加泄漏电流的值不符合要求',$e_name.'所传字段缺少参数：0.75mA电流',['<'=>50],1);

    }

    //避雷器-工频参考电压试验
    public function check_blq_gpck($param)
    {
        //样品二维码
        $s_code = $param['s_code'];
        //试验名称
        $e_name = $param['e_name'];
        //检测机构
        $jcjg = $param['jcjg'];
        if (isset($this->name_reflection[$jcjg])){
            $jcjg_id = $this->name_reflection[$jcjg];
        }else{
            $jcjg_id = 0;
            $this->saveLog('找不到该区域对应的区域id：'.$jcjg,'试验质量服务所传区域错误');
        }
        //物资类别
        $type = $param['type'];
        //工位
        $station = $param['station'];
        //结果数据
        $json = $param['json'];
        if (isset(json_decode($json,true)[0]))
        {
            $json_arr = json_decode($json,true)[0];
        }else{
            $json_arr = json_decode($json,true);
        }
        //施加电压
        if (isset($json_arr['1mA下参考电压']) && isset($json_arr['额定电压'])){
            $sjdy = $json_arr['1mA下参考电压'];
            $eddy = $json_arr['额定电压'];
            if ($sjdy <= $eddy){
                $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'施加电压的值不符合要求',0);
            }

        }

    }
    //隔离开关-绝缘试验
    public function check_glkg_jysy($param)
    {
        //样品二维码
        $s_code = $param['s_code'];
        //试验名称
        $e_name = $param['e_name'];
        //检测机构
        $jcjg = $param['jcjg'];
        if (isset($this->name_reflection[$jcjg])){
            $jcjg_id = $this->name_reflection[$jcjg];
        }else{
            $jcjg_id = 0;
            $this->saveLog('找不到该区域对应的区域id：'.$jcjg,'试验质量服务所传区域错误');
        }
        //物资类别
        $type = $param['type'];
        //工位
        $station = $param['station'];
        //结果数据
        $json = $param['json'];
        if (isset(json_decode($json,true)[0]))
        {
            $json_arr = json_decode($json,true)[0];
        }else{
            $json_arr = json_decode($json,true);
        }


        //根据试验部位不同，区分相间电压和断口电压
        if (isset($json_arr['试验部位'])){
            if ($json_arr['试验部位'] == '对地'){
                if (isset($json_arr['电压'])){
                    $sjdy = $json_arr['电压'];
                    if (abs(($sjdy-42)/42)>0.01){
                        $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'施加相间电压的值不符合要求',0);
                    }
                }else{
                    $this->saveLog($type.$e_name.'所传字段缺少参数：施加电压','试验质量服务缺少字段');
                }
            }elseif ($json_arr['试验部位'] == '断口'){
                if (isset($json_arr['电压'])){
                    $sjdy = $json_arr['电压'];
                    if (abs(($sjdy-48)/48)>0.01){
                        $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'施加断口电压的值不符合要求',0);
                    }
                }else{
                    $this->saveLog($type.$e_name.'所传字段缺少参数：施加电压','试验质量服务缺少字段');
                }
            }
        }else{
            $this->saveLog($type.$e_name.'所传字段缺少参数：试验部位','试验质量服务缺少字段');
        }



    }

    //隔离开关-回路电阻测量
    public function check_glkg_hldzcl($param)
    {
        //样品二维码
        $s_code = $param['s_code'];
        //试验名称
        $e_name = $param['e_name'];
        //检测机构
        $jcjg = $param['jcjg'];
        if (isset($this->name_reflection[$jcjg])){
            $jcjg_id = $this->name_reflection[$jcjg];
        }else{
            $jcjg_id = 0;
            $this->saveLog('找不到该区域对应的区域id：'.$jcjg,'试验质量服务所传区域错误');
        }
        //物资类别
        $type = $param['type'];
        //工位
        $station = $param['station'];
        //结果数据
        $json = $param['json'];
        if (isset(json_decode($json,true)[0]))
        {
            $json_arr = json_decode($json,true)[0];
        }else{
            $json_arr = json_decode($json,true);
        }
        $val = $this->getParamFromResult($s_code,'主回路回路电阻试验（温升前）','A相电流值','隔离开关回路电阻测量所传字段缺少参数：A相电阻值','试验质量服务缺少字段');
        if (abs(($val - 100)/100) > 0.01){
            $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'施加电流的值不符合要求',0);
        }

    }

    //隔离开关-温升试验
    public function check_glkg_ws($param)
    {
        //样品二维码
        $s_code = $param['s_code'];
        //试验名称
        $e_name = $param['e_name'];
        //检测机构
        $jcjg = $param['jcjg'];
        if (isset($this->name_reflection[$jcjg])){
            $jcjg_id = $this->name_reflection[$jcjg];
        }else{
            $jcjg_id = 0;
            $this->saveLog('找不到该区域对应的区域id：'.$jcjg,'试验质量服务所传区域错误');
        }
        //物资类别
        $type = $param['type'];
        //工位
        $station = $param['station'];
        //结果数据
        $json = $param['json'];
        if (isset(json_decode($json,true)[0]))
        {
            $json_arr = json_decode($json,true)[0];
        }else{
            $json_arr = json_decode($json,true);
        }

        //额定电流
        $eddl = $json_arr['额定电流'];
        //施加电流
        $sjdl = $json_arr['施加电流'];
        if (abs(($sjdl-(1.1*$eddl))/(1.1*$eddl))> 0.02)  {
            $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'施加额定电流的值不符合要求',0);
        }
        //环境温度
        $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,'温度','环境温度测量值不符合要求',$type.'温升试验所传字段缺少参数：温度',['<='=>40,'>='=>10],1);
        //从redis中取每小时温度值
        $wd = $this->getResultFromRedis(3,$s_code.'_wdz',4);
        for($i=0;$i<count($wd)-1;$i++){
            //油温差
            $ywx = $wd[$i+1] - $wd[$i];
            if ($ywx > 1){
                $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'最后四分之一试验期间温升的变化大于1K/h',0);
                break;
            }
        }

        //前电阻值
        $qdzz = $this->getParamFromResult($s_code,'主回路回路电阻试验（温升前）','A相电阻值',$type.$e_name.'所传字段缺少参数：A相电阻值','试验质量服务缺少字段');

        //后电阻值
        $hdzz = $this->getParamFromResult($s_code,'主回路回路电阻试验（温升后）','A相电阻值',$type.$e_name.'所传字段缺少参数：A相电阻值','试验质量服务缺少字段');

        if ($qdzz != '参数不存在' && $hdzz != '参数不存在'){
            if (($hdzz - $qdzz) / $qdzz > 0.2) {
                $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'温升试验前后同一位置电阻值不符合要求',0);
            }
        }

    }
    //配电变压器-绕组对地及绕组间直流绝缘电阻测量
    public function check_pdbyq_rzdd($param)
    {
        //样品二维码
        $s_code = $param['s_code'];
        //试验名称
        $e_name = $param['e_name'];
        //检测机构
        $jcjg = $param['jcjg'];
        if (isset($this->name_reflection[$jcjg])){
            $jcjg_id = $this->name_reflection[$jcjg];
        }else{
            $jcjg_id = 0;
            $this->saveLog('找不到该区域对应的区域id：'.$jcjg,'试验质量服务所传区域错误');
        }
        //物资类别
        $type = $param['type'];
        //工位
        $station = $param['station'];
        //结果数据
        $json = $param['json'];
        if (isset(json_decode($json,true)[0]))
        {
            $json_arr = json_decode($json,true)[0];
        }else{
            $json_arr = json_decode($json,true);
        }
        //检测温度
        $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,'温度','环境温度测量值不符合要求','配电变压器绕组对地及绕组间直流绝缘电阻测量所传字段缺少参数：温度',['<='=>40,'>='=>5],1);
        //检测湿度
        if (isset($json_arr['湿度'])){
            $shidu = $json_arr['湿度'];
            $shidu = str_replace('%','',$shidu);
            $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,$shidu,'环境湿度测量值不符合要求','配电变压器绕组对地及绕组间直流绝缘电阻测量所传字段缺少参数：湿度',['<'=>80],2);
        }else{
            $this->saveLog('配电变压器绕组对地及绕组间直流绝缘电阻测量所传字段缺少参数：湿度','试验质量服务缺少字段');
        }

        //施加电压
        $sjdy = $json_arr['施加电压测量值'];
        if ($sjdy <= 2500 ){
            $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'施加电压值不符合要求',0);
        }

    }


    //配电变压器-感应耐压试验
    public function check_pdbyq_gyny($param){
        //样品二维码
        $s_code = $param['s_code'];
        //试验名称
        $e_name = $param['e_name'];
        //检测机构
        $jcjg = $param['jcjg'];
        if (isset($this->name_reflection[$jcjg])){
            $jcjg_id = $this->name_reflection[$jcjg];
        }else{
            $jcjg_id = 0;
            $this->saveLog('找不到该区域对应的区域id：'.$jcjg,'试验质量服务所传区域错误');
        }
        //物资类别
        $type = $param['type'];
        //工位
        $station = $param['station'];
        //结果数据
        $json = $param['json'];
        if (isset(json_decode($json,true)[0]))
        {
            $json_arr = json_decode($json,true)[0];
        }else{
            $json_arr = json_decode($json,true);
        }
        //检测温度
        $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,'温度','环境温度测量值不符合要求','配电变压器感应耐压试验所传字段缺少参数：温度',['<='=>40,'>='=>5],1);
        //检测湿度
        if (isset($json_arr['湿度'])){
            $shidu = $json_arr['湿度'];
            $shidu = str_replace('%','',$shidu);
            $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,$shidu,'环境湿度测量值不符合要求','配电变压器感应耐压试验所传字段缺少参数：湿度',['<'=>80],2);
        }else{
            $this->saveLog('配电变压器感应耐压试验所传字段缺少参数：湿度','试验质量服务缺少字段');
        }

        //施加电压
        $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,'平均电压','施加电压的值不符合要求','配电变压器感应耐压试验所传字段缺少参数：平均电压',['<='=>802,'>='=>798],1);

        //施加电压频率
        if (isset($json_arr['感应频率'])){
            $gypl = $json_arr['感应频率'];
            $t = 120*50/$gypl;
            if (isset($json_arr['持续时间'])){
                if ($json_arr['持续时间'] < $t){
                    $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'试验时间要求不符合要求',0);
                }
            }else{
                $this->saveLog('配电变压器感应耐压试验所传字段缺少参数：持续时间','试验质量服务缺少字段');
            }
        }else{
            $this->saveLog('配电变压器感应耐压试验所传字段缺少参数：感应频率','试验质量服务缺少字段');
        }

        if (isset($json_arr['A相电压']) && isset($json_arr['B相电压']) && isset($json_arr['C相电压'])){
            //检测电压
            $ua_pj =  $json_arr['A相电压'];
            $ub_pj =  $json_arr['B相电压'];
            $uc_pj =  $json_arr['C相电压'];

            $max = max([$ua_pj,$ub_pj,$uc_pj]);
            $min = min([$ua_pj,$ub_pj,$uc_pj]);
            $ratio = ($max-$min) / (($ua_pj + $ub_pj + $uc_pj)/3);

            if (abs($ratio) >= 0.02){
                $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'三相施加低压额定电压的值不符合要求',0);
            }
        }else{
            $this->saveLog('配电变压器感应耐压试验所传字段缺少参数：ABC相电压','试验质量服务缺少字段');
        }



    }


    //配电变压器-外施耐压试验
    public function check_pdbyq_wsny($param)
    {
        //样品二维码
        $s_code = $param['s_code'];
        //试验名称
        $e_name = $param['e_name'];
        //检测机构
        $jcjg = $param['jcjg'];
        if (isset($this->name_reflection[$jcjg])){
            $jcjg_id = $this->name_reflection[$jcjg];
        }else{
            $jcjg_id = 0;
            $this->saveLog('找不到该区域对应的区域id：'.$jcjg,'试验质量服务所传区域错误');
        }
        //物资类别
        $type = $param['type'];
        //工位
        $station = $param['station'];
        //结果数据
        $json = $param['json'];
        if (isset(json_decode($json,true)[0]))
        {
            $json_arr = json_decode($json,true)[0];
        }else{
            $json_arr = json_decode($json,true);
        }
        //检测温度
        $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,'温度','环境温度测量值不符合要求','配电变压器外施耐压试验所传字段缺少参数：温度',['<='=>40,'>='=>5],1);
        //检测湿度
        if (isset($json_arr['湿度'])){
            $shidu = $json_arr['湿度'];
            $shidu = str_replace('%','',$shidu);
            $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,$shidu,'环境湿度测量值不符合要求','配电变压器外施耐压试验所传字段缺少参数：湿度',['<'=>80],2);
        }else{
            $this->saveLog('配电变压器外施耐压试验所传字段缺少参数：湿度','试验质量服务缺少字段');
        }

        //根据试验部位不同，区分高压侧电压和低压测电压
        if (isset($json_arr['试验部位'])){
            if ($json_arr['试验部位'] == '高对低及地'){
                if (isset($json_arr['施加电压'])){
                    $sjdy = $json_arr['施加电压'];
                    if (abs(($sjdy-35)/35)>0.01){
                        $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'高压侧施加电压的值不符合要求',0);
                    }
                }else{
                    $this->saveLog('配电变压器外施耐压试验所传字段缺少参数：施加电压','试验质量服务缺少字段');
                }
            }elseif ($json_arr['试验部位'] == '低对高及地'){
                if (isset($json_arr['施加电压'])){
                    $sjdy = $json_arr['施加电压'];
                    if (abs(($sjdy-5)/5)>0.01){
                        $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'低压侧施加电压的值不符合要求',0);
                    }
                }else{
                    $this->saveLog('配电变压器外施耐压试验所传字段缺少参数：施加电压','试验质量服务缺少字段');
                }
            }
        }else{
            $this->saveLog('配电变压器外施耐压试验所传字段缺少参数：试验部位','试验质量服务缺少字段');
        }

        //试验持续时间
        $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,'试验持续时间','试验时间不符合要求','配电变压器外施耐压试验所传字段缺少参数：试验持续时间',['<='=>61,'>='=>59],1);
        //规定试验电压,即试验施加电压
        if (isset($json_arr['试验施加电压'])){
            $gdsydy = $json_arr['试验施加电压'];
            //在过程数据中找该样品二维码，外施耐压试验的第一条数据，获取初始加压值
            $data = Db::table('process_data')
                ->where('sample_barcode',$s_code)
                ->where('experiment_name',$e_name)
                ->order('create_at asc')
                ->value('json');
            $data = json_decode($data,true)[0];
            if (isset($data['施加电压'])){
                if ($data['施加电压' >= ($gdsydy/3)]){
                    $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'初始加压值不符合要求',0);
                }else{
                    $this->saveLog('配电变压器外施耐压试验所传字段缺少参数：施加电压(初始加压值)','试验质量服务缺少字段');
                }
            }

            //获取最终加压值
            $data = Db::table('process_data')
                ->where('sample_barcode',$s_code)
                ->where('experiment_name',$e_name)
                ->order('create_at desc')
                ->value('json');
            $data = json_decode($data,true)[0];
            if (isset($data['施加电压'])){
                if ($data['施加电压' >= ($gdsydy/3)]){
                    $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'最终加压值不符合要求',0);
                }else{
                    $this->saveLog('配电变压器外施耐压试验所传字段缺少参数：施加电压(最终加压值)','试验质量服务缺少字段');
                }
            }
            //电压
            if (isset($json_arr['电压'])){
                //电压有效值
                $dyyxz = $json_arr['电压'];
                //电压峰值
                $dyfz = sqrt(2)*$dyyxz;
                //由于峰值为根号2倍的有效值，所以判断规则必定成立，无需判断
            }else{
                $this->saveLog('配电变压器外施耐压试验所传字段缺少参数：电压','试验质量服务缺少字段');
            }

        }else{
            $this->saveLog('配电变压器外施耐压试验所传字段缺少参数：试验施加电压','试验质量服务缺少字段');
        }


    }

    //配电变压器-短路阻抗和负载损耗测量
    public function check_pdbyq_dlzkhfzshcl($test_type,$param)
    {
        //样品二维码
        $s_code = $param['s_code'];
        //试验名称
        $e_name = $param['e_name'];
        //检测机构
        $jcjg = $param['jcjg'];
        if (isset($this->name_reflection[$jcjg])){
            $jcjg_id = $this->name_reflection[$jcjg];
        }else{
            $jcjg_id = 0;
            $this->saveLog('找不到该区域对应的区域id：'.$jcjg,'试验质量服务所传区域错误');
        }
        //物资类别
        $type = $param['type'];
        //工位
        $station = $param['station'];
        //结果数据
        $json = $param['json'];
        if (isset(json_decode($json,true)[0]))
        {
            $json_arr = json_decode($json,true)[0];
        }else{
            $json_arr = json_decode($json,true);
        }
        //检测温度
        $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,'温度','环境温度测量值不符合要求','配电变压器短路阻抗和负载损耗测量所传字段缺少参数：温度',['<='=>40,'>='=>5],1);
        //检测湿度
        if (isset($json_arr['湿度'])){
            $shidu = $json_arr['湿度'];
            $shidu = str_replace('%','',$shidu);
            $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,$shidu,'环境湿度测量值不符合要求','配电变压器短路阻抗和负载损耗测量所传字段缺少参数：湿度',['<'=>80],2);
        }else{
            $this->saveLog('配电变压器短路阻抗和负载损耗测量所传字段缺少参数：湿度','试验质量服务缺少字段');
        }
        if ($test_type === '油浸式'){
            //环境温度5到40
            $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,'温度','环境温度测量值不符合要求','配电变压器所传字段缺少参数：温度',['<='=>40,'>='=>5],1);
            if (isset($json_arr['直阻温度'])){
                $val = $json_arr['直阻温度'];
                //底部液体温度
                if (isset($json_arr['底部液体温度'])){
                    $yw = $json_arr['底部液体温度'];
                    if (abs($yw-$val) > 5){
                        $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'直阻温度和底部液体温度的温差值不符合要求',0);
                    }
                }else{
                    $this->saveLog('配电变压器短路阻抗和负载损耗测量所传字段缺少参数：底部液体温度','试验质量服务缺少字段');
                }

            }else{
                $this->saveLog('配电变压器绕组电阻测量所传字段缺少参数：直阻温度','试验质量服务缺少字段');
            }
        }

        if ($test_type === '干式'){
            if (isset($json_arr['绕组温度'])){
                //环境温度5到40
                $val = $json_arr['绕组温度'];
                $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,'温度','环境温度测量值不符合要求','配电变压器所传字段缺少参数：温度',['<='=>40,'>='=>5],1);
                //环境温度与绕组温度相差小于2度
                $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,'温度','环境温度测量值不符合要求','配电变压器所传字段缺少参数：温度',['<'=>$val+2,'>'=>$val-2],1);
            }else{
                $this->saveLog('配电变压器绕组电阻测量所传字段缺少参数：绕组温度','试验质量服务缺少字段');
            }

        }


        if (isset($json_arr['施加电流']) && isset($json_arr['高压侧额定电流'])){
            //施加电流
            $sjdl = $json_arr['施加电流'];
            //额定电流
            $eddl = $json_arr['高压侧额定电流'];
            if ($sjdl < 0.5 * $eddl){
                $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'施加电流的值不符合要求',0);
            }

        }else{
            $this->saveLog('配电变压器短路阻抗和负载损耗测量所传字段缺少参数：施加电流【25】或者高压侧额定电流【26】','试验质量服务缺少字段');
        }

        if (isset($json_arr['A相平均电压']) && isset($json_arr['B相平均电压']) && isset($json_arr['C相平均电压'])){
            //检测电压
            $ua_pj =  $json_arr['A相平均电压'];
            $ub_pj =  $json_arr['B相平均电压'];
            $uc_pj =  $json_arr['C相平均电压'];

            $max = max([$ua_pj,$ub_pj,$uc_pj]);
            $min = min([$ua_pj,$ub_pj,$uc_pj]);
            $ratio = ($max-$min) / (($ua_pj + $ub_pj + $uc_pj)/3);

            if (abs($ratio) >= 0.02){
                $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'三相施加低压额定电压的值不符合要求',0);
            }
        }else{
            $this->saveLog('配电变压器绕组电阻测量所传字段缺少参数：ABC相平均电压','试验质量服务缺少字段');
        }


    }

    //配电变压器-空载损耗和空载电流测量
    public function check_pdbyq_kzshhkzdlcl($param)
    {
        //样品二维码
        $s_code = $param['s_code'];
        //试验名称
        $e_name = $param['e_name'];
        //检测机构
        $jcjg = $param['jcjg'];
        if (isset($this->name_reflection[$jcjg])){
            $jcjg_id = $this->name_reflection[$jcjg];
        }else{
            $jcjg_id = 0;
            $this->saveLog('找不到该区域对应的区域id：'.$jcjg,'试验质量服务所传区域错误');
        }
        //物资类别
        $type = $param['type'];
        //工位
        $station = $param['station'];
        //结果数据
        $json = $param['json'];
        if (isset(json_decode($json,true)[0]))
        {
            $json_arr = json_decode($json,true)[0];
        }else{
            $json_arr = json_decode($json,true);
        }
        //检测温度
        $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,'温度','环境温度测量值不符合要求','配电变压器空载损耗和空载电流测量所传字段缺少参数：温度',['<='=>40,'>='=>5],1);
        //检测湿度
        if (isset($json_arr['湿度'])){
            $shidu = $json_arr['湿度'];
            $shidu = str_replace('%','',$shidu);
            $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,$shidu,'环境湿度测量值不符合要求','配电变压器空载损耗和空载电流测量所传字段缺少参数：湿度',['<'=>80],2);
        }else{
            $this->saveLog('配电变压器空载损耗和空载电流测量所传字段缺少参数：湿度','试验质量服务缺少字段');
        }

        //检测电压
        if (isset($json_arr['A相平均电压']) && isset($json_arr['B相平均电压']) && isset($json_arr['C相平均电压'])
            && isset($json_arr['A相电压']) && isset($json_arr['B相电压']) && isset($json_arr['C相电压'])
        ){
            $ua_pj =  $json_arr['A相平均电压'];
            $ub_pj =  $json_arr['B相平均电压'];
            $uc_pj =  $json_arr['C相平均电压'];
            $ua = $json_arr['A相电压'];
            $ub = $json_arr['B相电压'];
            $uc = $json_arr['C相电压'];
            //方均根值
            $fjgz = sqrt(pow($ua_pj,2)+pow($ub_pj,2)+pow($uc_pj,2));
            $ratio = ($fjgz - ($ua_pj + $ub_pj + $uc_pj)/3) / (($ua_pj + $ub_pj + $uc_pj)/3);

            $max = max([$ua,$ub,$uc]);
            $min = min([$ua,$ub,$uc]);
            $ratio1 = ($max-$min) / (($ua + $ub + $uc)/3);

            if (abs($ratio)>0.03 || $ratio1 > 0.02){
                $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'三相施加低压额定电压的值不符合要求',0);
            }
        }else{
            $this->saveLog('配电变压器空载损耗和空载电流测量所传字段缺少参数：ABC相平均电压和ABC相电压','试验质量服务缺少字段');
        }

    }

    //配电变压器绕组电阻测量
    public function check_pdbyq_rzdzcl($test_type,$param)
    {
        //样品二维码
        $s_code = $param['s_code'];
        //试验名称
        $e_name = $param['e_name'];
        //检测机构
        $jcjg = $param['jcjg'];
        if (isset($this->name_reflection[$jcjg])){
            $jcjg_id = $this->name_reflection[$jcjg];
        }else{
            $jcjg_id = 0;
            $this->saveLog('找不到该区域对应的区域id：'.$jcjg,'试验质量服务所传区域错误');
        }
        //物资类别
        $type = $param['type'];
        //工位
        $station = $param['station'];
        //结果数据
        $json = $param['json'];
        if (isset(json_decode($json,true)[0]))
        {
            $json_arr = json_decode($json,true)[0];
        }else{
            $json_arr = json_decode($json,true);
        }
        if ($test_type === '油浸式'){
            //环境温度5到40
            $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,'温度','环境温度测量值不符合要求','配电变压器所传字段缺少参数：温度',['<='=>40,'>='=>5],1);
            if (isset($json_arr['直阻温度'])){
                $val = $json_arr['直阻温度'];
                //底部液体温度
              if (isset($json_arr['底部液体温度'])){
                  $yw = $json_arr['底部液体温度'];
                  if (abs($yw-$val) > 5){
                      $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'直阻温度和底部液体温度的温差值不符合要求',0);
                  }
              }else{
                  $this->saveLog('配电变压器绕组电阻测量所传字段缺少参数：底部液体温度','试验质量服务缺少字段');
              }
            }else{
                $this->saveLog('配电变压器绕组电阻测量所传字段缺少参数：直阻温度','试验质量服务缺少字段');
            }
        }

        if ($test_type === '干式'){
            if (isset($json_arr['绕组温度'])){
                //环境温度5到40
                $val = $json_arr['绕组温度'];
                $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,'温度','环境温度测量值不符合要求','配电变压器所传字段缺少参数：温度',['<='=>40,'>='=>5],1);
                //环境温度与绕组温度相差小于2度
                $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,'温度','环境温度测量值不符合要求','配电变压器所传字段缺少参数：温度',['<'=>$val+2,'>'=>$val-2],1);
            }else{
                $this->saveLog('配电变压器绕组电阻测量所传字段缺少参数：绕组温度','试验质量服务缺少字段');
            }

        }
    }





    //检查配电变压器温升试验的试验质量
    //根据《试验质控控制》文档查找要检查的参数，找到则进行比对，找不到记录到log表
    public function check_pdbyq_ws($param)
    {
        //样品二维码
        $s_code = $param['s_code'];
        //试验名称
        $e_name = $param['e_name'];
        //检测机构
        $jcjg = $param['jcjg'];
        if (isset($this->name_reflection[$jcjg])){
            $jcjg_id = $this->name_reflection[$jcjg];
        }else{
            $jcjg_id = 0;
            $this->saveLog('找不到该区域对应的区域id：'.$jcjg,'试验质量服务所传区域错误');
        }
        //物资类别
        $type = $param['type'];
        //工位
        $station = $param['station'];
        //结果数据
        $json = $param['json'];
        if (isset(json_decode($json,true)[0]))
        {
            $json_arr = json_decode($json,true)[0];
        }else{
            $json_arr = json_decode($json,true);
        }

        //检测温度
        $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,'温度','环境温度测量值不符合要求','配电变压器温升试验所传字段缺少参数：温度',['<='=>40,'>='=>5],1);
        //检测湿度
        if (isset($json_arr['湿度'])){
            $shidu = $json_arr['湿度'];
            $shidu = str_replace('%','',$shidu);
            $this->checkParameter($json_arr,$jcjg_id,$station,$type,$e_name,$s_code,$shidu,'环境湿度测量值不符合要求','配电变压器温升试验所传字段缺少参数：湿度',['<'=>80],2);
        }else{
            $this->saveLog('配电变压器温升试验所传字段缺少参数：湿度','试验质量服务缺少字段');
        }

        //检测损耗,总损耗从redis中取，空载损耗和负载损耗从结果数据中取
        //0.5小时检测一次，持续3小时，则检测倒数7次数据
        //空载损耗
        $kzsh = $this->getParamFromResult($s_code,'空载电流及空载损耗试验','空载损耗测量值','配电变压器空载损耗和空载电流测量所传字段缺少参数：空载损耗测量值','试验质量服务缺少字段');
        //负载损耗
        $fzsh = $this->getParamFromResult($s_code,'短路阻抗及负载损耗试验','负载损耗','配电变压器空载损耗和空载电流测量所传字段缺少参数：空载损耗测量值','试验质量服务缺少字段');
        //总损耗
        $zsh = $this->getResultFromRedis(2,$s_code.'_zgl',7);
        if (count($zsh) < 7){
            $this->saveLog('配电变压器温升试验所传字段缺少参数:总损耗数据不足3小时，即七次','试验质量服务缺少字段');
        }

        if ($kzsh != '参数不存在' && $fzsh != '参数不存在'){
            foreach ($zsh as $v){
                if ($v != $kzsh + $fzsh){
                    $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'总损耗的值和空负载损耗不匹配',0);
                    break;
                }
            }
        }

        //顶层油温，每小时检测一次，最终三小时温度变化小于1K
        $dcyw = $this->getResultFromRedis(2,$s_code.'_zgl_yw',4);
        if (count($dcyw) < 4){
            $this->saveLog('配电变压器温升试验所传字段缺少参数:顶层油温数据不足3小时，即四次','试验质量服务缺少字段');
        }
        for($i=0;$i<count($dcyw)-1;$i++){
            //油温差
            $ywx = $dcyw[$i+1] - $dcyw[$i];
            if ($ywx > 1){
                $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'顶层油温温升的变化大于1K/h',0);
                break;
            }
        }


        //高压侧额定电流
        if (isset($json_arr['高压侧额定电流'])){
            $val = $json_arr['高压侧额定电流'];
            //检查施加电流值
            $eddl = $this->getResultFromRedis(1,$s_code.'_eddl_dl');
            foreach ($eddl as $v){
                if (($v > $val+0.2) || ($val < $val - 0.2)){
                    $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'施加额定电流的值不符合要求',0);
                    break;
                }
            }
            //检查施加电流时间
            $start_at = $this->getResultFromRedis(4,$s_code.'_eddl_sj',1);
            $finish_at = $this->getResultFromRedis(2,$s_code.'_eddl_sj',1);
            $run_time = strtotime($finish_at) - strtotime($start_at);
            if ($run_time != 3600){
                $this->saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,'额定电流阶段试验时间不满足要求',0);
            }
        }else{
            $this->saveLog('配电变压器温升试验所传字段缺少参数：高压侧额定电流','试验质量服务缺少字段');
        }

    }


    //人员信息接口,获取人员和试验绑定信息，用于UI07地图-人员和10人员管理
    public function getStaffExperiment()
    {

        $ret = [
            'code'=>1,
            'msg'=>'success',
            'data'=>[]
        ];
        $num = input('param.num',6,'addslashes');
        $type = input('param.type','','addslashes');
        if ($num == 0)
        {
            $ret['code'] = 0;
            $ret['msg'] = 'num不能为0';
            return json_encode($ret,320);

        }

        //只返回某检测机构的信息
        if ($type){
            $res = $this->getStaffExperimentAssist($type,$num);
            $ret['data'][$type] = $res;
            return json_encode($ret,320);
        }else{
            //全返回
            $res = $this->getStaffExperimentAssist('省中心（电科院）',$num);
            $ret['data']['省中心（电科院）'] = $res;
            $res = $this->getStaffExperimentAssist('苏北分中心',$num);
            $ret['data']['苏北分中心'] = $res;
            $res = $this->getStaffExperimentAssist('苏南分中心',$num);
            $ret['data']['苏南分中心'] = $res;
            $res = $this->getStaffExperimentAssist('苏中分中心',$num);
            $ret['data']['苏中分中心'] = $res;
            return json_encode($ret,320);
        }


    }

    //辅助getStaffExperiment，获取人员和试验绑定信息
    public function getStaffExperimentAssist($type,$num)
    {
        $res = Db::table('dky_mission_experiment')
            ->where('testing_institution',$type)
            ->order('updatetime desc')
            ->limit(0,$num)
            ->select();

        //获取所有人员信息，将人员信息加到任务试验绑定信息中去
        $staff = Db::table('dky_staff')
            ->where('deletetime',null)
            ->column('name,yxq','id');



        foreach ($res as $k=>$v){
            $res[$k]['name'] = '';
            $res[$k]['certificate_status'] = '/';
            $res[$k]['yxq'] = '/';
            $staff_ids_str = $v['dky_staff_ids'];
            $staff_ids = explode(',',$staff_ids_str);
            foreach ($staff_ids as $vv)
            {
                //单任务会显示多个绑定人，但是证书有效期会以最后一个绑定人的信息为准
                if (isset($staff[$vv])){
                    if (isset($res[$k]['name'])){
                        $res[$k]['name'] .= ','.$staff[$vv]['name'];
                    }else{
                        $res[$k]['name'] = $staff[$vv]['name'];
                    }
                    $res[$k]['name']  = trim($res[$k]['name'] ,',');

                    $yxq = $staff[$vv]['yxq'];
                    $res[$k]['yxq'] = $yxq;
                    $yxq_timestamp = strtotime($yxq);
                    $now = time();
                    //过期
                    if ($yxq_timestamp < $now)
                    {
                        $res[$k]['certificate_status'] = '已过期';
                    }elseif (($yxq_timestamp - $now ) >= 5184000){
                        //有效期>=60天，表示正常
                        $res[$k]['certificate_status'] = '正常';
                    }else{
                        //否则代表小于60天
                        $res[$k]['certificate_status'] = '快到期';
                    }
                }
            }
        }

        return $res;
    }



    //获取样品信息
    public function getSampleInfo()
    {

        $ret = [
            'code'=>1,
            'msg'=>'success',
            'data'=>[]
        ];
        $num = input('param.num',9,'addslashes');
        $type = input('param.type','','addslashes');
        if ($num == 0)
        {
            $ret['code'] = 0;
            $ret['msg'] = 'num不能为0';
            return json_encode($ret,320);

        }

        //只返回某检测机构的信息
        if ($type){
            $res = $this->getSampleInfoAssist($type,$num);
            $ret['data'][$type] = $res;
            return json_encode($ret,320);
        }else{
            //全返回
            $res = $this->getSampleInfoAssist('省中心（电科院）',$num);
            $ret['data']['省中心（电科院）'] = $res;
            $res = $this->getSampleInfoAssist('苏北分中心',$num);
            $ret['data']['苏北分中心'] = $res;
            $res = $this->getSampleInfoAssist('苏南分中心',$num);
            $ret['data']['苏南分中心'] = $res;
            $res = $this->getSampleInfoAssist('苏中分中心',$num);
            $ret['data']['苏中分中心'] = $res;
            return json_encode($ret,320);
        }


    }

    public function getSampleInfoAssist($type,$num)
    {
        //只显示未完成的样品任务
        $res = Db::table('dky_mission')
            ->where('testing_institution',$type)
            ->where('finish_time','1970-01-01 00:00:00')
            ->order('distribute_time desc')
            ->limit(0,$num)
            ->select();

        $i = 1;
        foreach ($res as $k=>$v){
            $experiments_station = $v['experiments_station'];
            if ($experiments_station){
                $experiments_station_arr = explode(',',$experiments_station);
                //规定试验项目数
                $res[$k]['gdsy'] = count($experiments_station_arr);
            }else{
                $experiments_station_arr = explode(',',$experiments_station);
                //规定试验项目数
                $res[$k]['gdsy'] = 0;
            }

            //已试验项目数量
            $count = Db::table('dky_mission_experiment')
                ->where('twins_token',$v['twins_token'])
                ->where('status','已检')
                ->count();
            $res[$k]['yj'] = $count;
            $res[$k]['wj'] = $res[$k]['gdsy'] - $count;
            $res[$k]['xh'] = $i++;
            $distribute_time = $v['distribute_time'];
            //已经花了多久
            $have_spend = round(((time() - strtotime($distribute_time)) / 86400),1);
            //超期阈值
            $cqyz = $v['overtime_norm'];
            //剩余时效
            $res[$k]['sysx'] = round($cqyz - $have_spend,0);
            //规定完成
            $gdwc = date('Y-m-d',(strtotime($distribute_time) + $cqyz * 86400));
            $res[$k]['gdwc'] = $gdwc;
            //实际完成
            $finish_time = date('Y-m-d',strtotime($v['finish_time']));
            if ($finish_time == '1970-01-01 00:00:00'){
                $res[$k]['sjwc'] = '/';
            }else{
                $res[$k]['sjwc'] = $finish_time ;
            }
            //类别，设备OR材料
            $res[$k]['device_type_belong'] = $v['device_type_belong'] ;
        }
        return $res;
    }

    //获取样品的试验项目
    public function getSampleExperiment()
    {
        $ret = [
            'code'=>1,
            'msg'=>'success',
            'data'=>[]
        ];
        $twins_token = input('param.twins_token','','addslashes');
        if (!$twins_token)
        {
            $ret['code'] = 0;
            $ret['msg'] = '参数不能为空';
            return json_encode($ret,320);
        }

        $res = Db::table('dky_mission_experiment')
            ->where('twins_token',$twins_token)
            ->select();

        if(!$res){
            $ret['code'] = 0;
            $ret['msg'] = '无此样品相关试验';
            return json_encode($ret,320);
        }
        $i = 1;
        foreach ($res as $k=>$v){
            $res[$k]['xh'] = $i++;
            if(($v['status'] == '在检' || $v['status'] == '待检') && $v['conclusion'] == '合格'){
                $res[$k]['conclusion'] = '无';
            }
        }
        $ret['data'] = $res;
        return json_encode($ret,320);
    }


    //样品合格率看板
    public function getSamplePanel()
    {
        $ret = [
            'code'=>1,
            'msg'=>'success',
            'data'=>[]
        ];


        $type = input('param.type','','addslashes');
        if(!in_array($type,$this->name_reflection_inverse)){
            $ret['code'] = 0;
            $ret['msg'] = 'type参数非法';
            return json_encode($ret,320);
        }

        //当日检测完成总数
        $today = date('Y-m-d');
        $year = date('Y');
        $today_finish = Db::table('dky_mission')
            ->where('testing_institution',$type)
            ->where('finish_time','like',$today.'%')
            ->count();

        //当日待检数量
        $today_dj = Db::table('dky_mission')
            ->where('testing_institution',$type)
            ->where('status','待检')
            ->count();

        //当日在检数量
        $today_zj = Db::table('dky_mission')
            ->where('testing_institution',$type)
            ->where('status','在检')
            ->count();

        //当年检测总数
        $this_year =  Db::table('dky_mission')
            ->where('testing_institution',$type)
            ->where('distribute_time','like',$year.'%')
            ->where('finish_time','<>','1970-01-01 00:00:00')
            ->count();
        //累计检测总量
        $all = Db::table('dky_mission')
            ->where('testing_institution',$type)
            ->where('finish_time','<>','1970-01-01 00:00:00')
            ->count();


        $ret['data']['today_finish'] = $today_finish;
        $ret['data']['today_dj'] = $today_dj;
        $ret['data']['today_zj'] = $today_zj;
        $ret['data']['this_year'] = $this_year;
        $ret['data']['all'] = $all;
        return json_encode($ret,320);

    }


    //用于历史搜索页面最左侧类型和物资品类下拉列表
    public function getTagForHistory()
    {
        $ret = [
            'code'=>1,
            'msg'=>'success',
            'data'=>[]
        ];
        $ret['data']['设备'] = $this->device_list;
        $ret['data']['材料'] = $this->material_list;
        return json_encode($ret,320);
    }

    //搜索样品接口
    public function searchSample()
    {
        $ret = [
            'code'=>1,
            'msg'=>'success',
            'data'=>[]
        ];
        //二次盲样号
        $s_code = input('param.s_code','','addslashes,trim,htmlspecialchars,strip_tags');
        //类型
        $device_type_belong = input('param.device_type_belong','','addslashes,trim,htmlspecialchars,strip_tags');
        //物资品类
        $device_type = input('param.device_type','','addslashes,trim,htmlspecialchars,strip_tags');

        if ($device_type_belong && $device_type_belong != '设备' && $device_type_belong != '材料'){
            $ret['code'] = 0;
            $ret['msg'] = '非法类型，请输入设备或者材料';
            return json_encode($ret,320);
        }

        $res = Db::table('dky_mission');
        if ($s_code){
            $res = $res->where('twins_token',$s_code);
        }

        if ($device_type_belong){
            $res = $res->where('device_type_belong',$device_type_belong);
        }

        if ($device_type){
            $res = $res->where('device_type',$device_type);
        }
        $limit = config('history_limit');
        //历史搜索只显示已完成的任务
        $res = $res->where('finish_time','<>','1970-01-01 00:00:00')->limit(0,$limit)->order('distribute_time desc')->select();
        if (!$res){
            $ret['code'] = 0;
            $ret['msg'] = '无相关样品信息';
            return json_encode($ret,320);
        }

        $i=1;
        foreach ($res as $k=>$v){
            $res[$k]['xh'] = $i++;
        }
        $ret['data'] = $res;
        return json_encode($ret,320);
    }

    //获取工位看板信息
    public function getStationPanel()
    {
        $ret = array(
            'code' => 1,
            'msg' => 'success',
            'data' => [],
        );

        $district = input('param.district','','addslashes,trim,htmlspecialchars,strip_tags');
        //只返回某检测机构的信息
        if ($district){
            $res = $this->getStationPanelAssist($district);
            $ret['data'][$district] = $res;
            return json_encode($ret,320);
        }else{
            //全返回
            $res = $this->getStationPanelAssist('省中心（电科院）');
            $ret['data']['省中心（电科院）'] = $res;
            $res = $this->getStationPanelAssist('苏北分中心');
            $ret['data']['苏北分中心'] = $res;
            $res = $this->getStationPanelAssist('苏南分中心');
            $ret['data']['苏南分中心'] = $res;
            $res = $this->getStationPanelAssist('苏中分中心');
            $ret['data']['苏中分中心'] = $res;
            return json_encode($ret,320);
        }


    }

    //获取工位看板助手函数
    public function getStationPanelAssist($district)
    {
        $data = [];
        if ($district == '省中心（电科院）')
        {
            //南京
            $station_status_url = config('njgwzt_url');
        }elseif($district == '苏南分中心'){
            //苏州
            $station_status_url = config('szgwzt_url');
        }elseif($district == '苏北分中心'){
            //徐州
            $station_status_url = config('xzgwzt_url');
        }elseif($district == '苏中分中心'){
            //泰州
            $station_status_url = config('tzgwzt_url');
        }else{
            return [];
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
            //return json_encode(simplexml_load_string($res),320);
            $result = json_decode(json_encode(simplexml_load_string($res)), true);

            $array = json_decode($result[0],true);
            $station_array = $array['nodesInfo'];
           $data = array(
                'total'=>0,
                'kx'=>0,
                'yx'=>0,
                'gz'=>0,
                'list'=>[
                    'kxz'=>[],
                    'gzz'=>[],
                    'gz'=>[],
                    'all'=>[]
                ]
            );
            foreach ($station_array as $k=>$v)
            {
                $data['total']++;
                if ($v['static'] == '在检'){
                    $data['yx']++;
                    if ($v['sampleCode']){
                        //TODO  此处在检时通过思创getsampleInfo接口获取工位信息，但是那个接口里工位名称为空，后期可以在此处把工位名称传过去，而不是从思创接口获取
                        $data['list']['gzz'][] = $this->getStationPanelAssist2($district,$v['sampleCode']);
                        $data['list']['all'][] = $this->getStationPanelAssist2($district,$v['sampleCode']);
                    }
                }
                if ($v['static'] == '空闲'){
                    $data['list']['kxz'][] = array(
                        'curNode'=>$v['nodeName'],
                        'state'=>$v['static'],
                        'sampleType'=>'/',
                        'curExpName'=>'/',
                    );
                    $data['list']['all'][] = array(
                        'curNode'=>$v['nodeName'],
                        'state'=>$v['static'],
                        'sampleType'=>'/',
                        'curExpName'=>'/',
                    );
                    $data['kx']++;
                }
                if ($v['static'] == '未开启'){
                    $data['list']['gz'][] = array(
                        'curNode'=>$v['nodeName'],
                        'state'=>$v['static'],
                        'sampleType'=>'/',
                        'curExpName'=>'/',
                    );
                    $data['list']['all'][] = array(
                        'curNode'=>$v['nodeName'],
                        'state'=>$v['static'],
                        'sampleType'=>'/',
                        'curExpName'=>'/',
                    );
                    $data['gz']++;
                }
            }
            return $data;
        }else{
            return [];
        }
    }


    //获取样品信息助手函数
    public function getStationPanelAssist2($district,$s_code)
    {

        if ($district == '省中心（电科院）')
        {
            //南京
            $url = config('njypxx_url');
        }elseif($district == '苏南分中心'){
            //苏州
            $url = config('szypxx_url');
        }elseif($district == '苏北分中心'){
            //徐州
            $url = config('xzypxx_url');
        }elseif($district == '苏中分中心'){
            //泰州
            $url = config('tzypxx_url');
        }else{
            return [];
        }

        $url.='?sampleCode='.$s_code;
        $res = $this->getUrl($url);

        //如果返回值中不含有<,代表无有效信息返回
        if (strpos($res,'<') !== false)
        {
            //禁止引用外部xml实体
            libxml_disable_entity_loader(true);
            //return json_encode(simplexml_load_string($res),320);
            $result = json_decode(json_encode(simplexml_load_string($res)), true);

            $array = json_decode($result[0],true);
            if ($array){
                $array['state'] =  $array['static'];
                unset($array['static']);
                $array['curNode'] =  $array['BindNode'];
                unset($array['BindNode']);
            }
            return $array;
        }else{
           return [];
        }
    }

    //设备管理模块下各个机构的信息
    public function getDeviceManagement()
    {

        //dump(config('database'));
        //dump(Db::table('dky_staff')->select());exit;

        $ret = array(
            'code' => 1,
            'msg' => 'success',
            'data' => [],
        );

        $type = input('param.type','','addslashes,trim,htmlspecialchars,strip_tags');
        if (!in_array($type,['苏南分中心','省中心（电科院）','苏中分中心','苏北分中心']))
        {
            $ret['code'] = 0;
            $ret['msg'] = '错误，检测机构参数非法，请参考接口文档';
            return json_encode($ret,320);
        }

        if ($type == '省中心（电科院）')
        {
            //南京
            $url = config('njrw_url');
        }elseif($type == '苏南分中心'){
            //苏州
            $url = config('szrw_url');
        }elseif($type == '苏北分中心'){
            //徐州
            $url = config('xzrw_url');
        }elseif($type == '苏中分中心'){
            //泰州
            $url = config('tzrw_url');
        }

        $res = $this->getUrl($url);
        //如果返回值中不含有<,代表无有效信息返回
        if (strpos($res,'<') !== false)
        {
            //禁止引用外部xml实体
            libxml_disable_entity_loader(true);
            //return json_encode(simplexml_load_string($res),320);
            $result = json_decode(json_encode(simplexml_load_string($res)), true);

            //工位信息，包含工位正在做的试验和下一个试验
            $station_info = json_decode($result[0],true);

        }else{
           $station_info = [];
        }


        //test
//        $str = '[{"nodename":"A2","samplelist":[{"samplecode":"S21070516170013","samplename":"油浸式配电变压器","createdate":"2021/7/14 14:19:46","explist":[{"expname":"","status":"合格"},{"expname":"","status":"试验中1"},{"expname":"","status":"合格"},{"expname":"","status":"合格"},{"expname":"","status":"合格"},{"expname":"","status":"合格"},{"expname":"啦啦啦试验","status":"试验中"}]},{"samplecode":"S21071615101816","samplename":"油浸式配电变压器","createdate":"2021/7/20 9:09:23","explist":[{"expname":"绕组电阻测量——短路前","status":"合格"},{"expname":"空载损耗和空载电流测量——短路前","status":"合格"},{"expname":"短路阻抗和负载损耗测量——短路前","status":"合格"},{"expname":"感应耐压试验——短路前","status":"合格"},{"expname":"外施耐压试验——短路前","status":"试验中"},{"expname":"温升试验","status":""}]}]},{"nodename":"A3","samplelist":[{"samplecode":"S21070516170022","samplename":"油浸式配电变压器","createdate":"2021/7/8 11:20:29","explist":[{"expname":"","status":"合格"},{"expname":"","status":"合格"},{"expname":"对对对试验","status":"试验中"},{"expname":"","status":"合格"},{"expname":"","status":"合格"},{"expname":"","status":"合格"},{"expname":"","status":"合格"}]},{"samplecode":"S21070516170002","samplename":"油浸式配电变压器","createdate":"2021/7/6 18:33:57","explist":[{"expname":"绕组电阻测量——短路前","status":"合格"},{"expname":"空载损耗和空载电流测量——短路前","status":"合格"},{"expname":"短路阻抗和负载损耗测量——短路前","status":"合格"}]},{"samplecode":"S21071615101811","samplename":"油浸式配电变压器","createdate":"2021/7/22 9:32:36","explist":[{"expname":"","status":"合格"},{"expname":"","status":"合格"},{"expname":"","status":"合格"},{"expname":"","status":"合格"},{"expname":"","status":"合格"}]}]},{"nodename":"A4","samplelist":[{"samplecode":"S20042016433112","samplename":"避雷器（10kV~35kV）","createdate":"2020/4/29 18:48:35","explist":[{"expname":"0.75倍直流参考电压下漏电流试验","status":"合格"},{"expname":"工频参考电压试验 ","status":"合格"}]}]},{"nodename":"A5","samplelist":[{"samplecode":"07361120","samplename":"","createdate":"2021/6/9 19:22:09","explist":[{"expname":"","status":"检修"}]}]}]';
//        $station_info = json_decode($str,true);





        //所有记录
        $arr = [];
        $line = [];

        foreach ($station_info as $k=>$v){
            //单条记录
            $line = [];
            //工位名称
            $line['station'] = $v['nodename'];
            $line['status'] = '空闲中';
            foreach ($v['samplelist'] as $kk=>$vv){
                foreach ($vv['explist'] as $kkk=>$vvv){
                    //如果该工位有试验是处于“试验中”，则该工位状态为工作中，否则为空闲中
                    //如果该工位有试验是处于“试验中”，则需要查找下一个试验信息
                    if ($vvv['status'] == '试验中'){
                        $line['status'] = '工作中';
                        $line['type'] = $vv['samplename'];
                        $line['twins_token'] = $vv['samplecode'];
                        $line['e_name'] = $vvv['expname'];
                        if (isset($vv['explist'][$kkk+1])){
                            //如果该样品有下一个试验，则将其作为下一个信息
                            $line['next_type'] = $vv['samplename'];
                            $line['next_twins_token'] = $vv['samplecode'];
                        }elseif (isset($v['samplelist'][$k+1])){
                            //如果该样品没有下一个试验，但是该工位有下一个样品，将下一个样品的第一个试验作为下一个信息
                            $line['next_type'] = $v['samplelist'][$k+1]['samplename'];
                            $line['next_twins_token'] = $v['samplelist'][$k+1]['samplecode'];
                        }else{
                            //不存在下一个试验了
                            $line['next_type'] = '/';
                            $line['next_twins_token'] = '/';
                        }
                        break 2;
                    }else{
                        $line['type'] = '/';
                        $line['twins_token'] = '/';



                        $line['e_name'] = '/';
                        $line['next_type'] = '/';
                        $line['next_twins_token'] = '/';
                    }
                }
            }
            $arr[] = $line;
        }

        //设备管理界面中的工位信息
        $ret['data']['station'] = $arr;

        //设备管理中工位统计上半部分
        $ret['data']['statistics'] = $this->getStationPanelAssist($type);
        //删除多余数据
        unset($ret['data']['statistics']['list']);
        //设备管理中工位统计下半部分
        //统计该机构所有工位全年做了多少试验
        $res = Db::table('dky_mission_status')
            ->field('count(*) as number,station')
            ->where('jcjg',$type)
            ->where('flag',3)
            ->group('station')
            ->select();


        $ret['data']['tq'] = $res;

        return json_encode($ret,320);
    }


    //新版本获取告警数据接口，区分已读和未读，以occur_at倒序排列
    public function getAlertInfo()
    {

        $ret = array(
            'code' => 1,
            'msg' => 'success',
            'data' => [],
        );

        //默认为1（未读），传2则返回已读
        $type = input('param.type',1,'addslashes,trim,htmlspecialchars,strip_tags');
        //默认为1（不修改已读状态），传2则将告警改为已读
        $flag = input('param.flag',1,'addslashes,trim,htmlspecialchars,strip_tags');
        //district地区
        $district = input('param.district','','addslashes,trim,htmlspecialchars,strip_tags');
        $limit = config('alert_limit');
        if (!in_array($type,[1,2])){
            $ret['code'] = 0;
            $ret['msg'] = '错误，类型参数非法，请参考接口文档';
            return json_encode($ret,320);
        }



        if (!$district || !isset($this->district_reflection[$district])){
            $ret['code'] = 0;
            $ret['msg'] = '错误，地区参数非法，请参考接口文档';
            return json_encode($ret,320);
        }else{
            $district_id = $this->district_reflection[$district];
        }

        if ($type == 1){
            //未读
            $res = Db::table('dky_testing_problem')
                ->where('district_id',$district_id)
                ->where('is_read',0)
                ->where('deletetime',null)
                ->order('occur_at desc')
                ->select();
            $count = count($res);

            if ($flag == 2){
                $ids = [];
                foreach ($res as $k=>$v){
                    $ids[] = $v['id'];
                }
                Db::table('dky_testing_problem')
                    ->where('id','in',$ids)
                    ->update(['is_read'=>1]);
            }
        }elseif ($type == 2){
            //已读
            $res = Db::table('dky_testing_problem')
                ->where('district_id',$district_id)
                ->where('is_read',1)
                ->where('deletetime',null)
                ->limit(0,$limit)
                ->order('occur_at desc')
                ->select();
            $count = count($res);
        }

        $i = 1;
        foreach ($res as $k=>$v){
            $res[$k]['xh'] = $i++;
        }
        $ret['data']['alarm'] = $res;
        $ret['data']['count'] = $count;
        return json_encode($ret,320);

    }


    //查询日期区间内的检测量信息助手函数
    public function searchTestingQuantityAssist($begin_at='',$end_at='')
    {
        if (!$begin_at){
            $begin_at = date('Y').'-01-01';
        }

        if (!$end_at){
            $end_at = date('Y-m-d');
        }

        if (!strtotime($begin_at) || !strtotime($end_at)){
            return '时间格式错误';
        }

        $arr = [];
        //全省检测量
        //下发时间在区间内且已完成的任务
        $res = Db::table('dky_mission')
            ->field('count(*) as num,testing_institution')
            ->where('distribute_time','between',[$begin_at,$end_at])
            ->where('finish_time','<>','1970-01-01 00:00:00')
            ->group('testing_institution')
            ->select();

        //var_dump(Db::table('dky_mission')->getLastSql());
        $res = array_column($res,null,'testing_institution');
        //全省总检测量
        $count = 0;
        foreach ($res as $k=>$v){
            $count += $v['num'];
        }
        $arr['count'] = $count;
        $arr['szx'] = isset($res['省中心（电科院）']) ? $res['省中心（电科院）']['num'] : 0;
        $arr['sn'] = isset($res['苏南分中心']) ? $res['苏南分中心']['num'] : 0;
        $arr['sz'] = isset($res['苏中分中心']) ? $res['苏中分中心']['num'] : 0;
        $arr['sb'] = isset($res['苏北分中心']) ? $res['苏北分中心']['num'] : 0;
        $arr['other'] = $count - $arr['szx'] - $arr['sn'] - $arr['sz'] - $arr['sb'];
        return $arr;

    }

    //查询日期区间内的检测量信息接口
    public function searchTestingQuantity()
    {
        $ret = array(
            'code' => 1,
            'msg' => 'success',
            'data' => [],
        );


        $begin_at = input('param.begin_at','','addslashes,trim,htmlspecialchars,strip_tags');
        $end_at = input('param.end_at','','addslashes,trim,htmlspecialchars,strip_tags');

        $res = $this->searchTestingQuantityAssist($begin_at,$end_at);
        if ($res == '时间格式错误'){
            $ret['code'] = 0;
            $ret['msg'] = '错误，时间格式错误，请参考接口文档';
            return json_encode($ret,320);
        }else{
            $ret['data'] = $res;
            return json_encode($ret,320);
        }
    }

    //检测信息接口
    public function getTestingInfo()
    {
        $ret = array(
            'code' => 1,
            'msg' => 'success',
            'data' => [],
        );
        $ret['data']['num'] = $this->searchTestingQuantityAssist();
        //计算检测数量占比
        $szx = $ret['data']['num']['szx'];
        $sz = $ret['data']['num']['sz'];
        $sb = $ret['data']['num']['sb'];
        $sn = $ret['data']['num']['sn'];
        $count = $ret['data']['num']['count'];
        $other = $ret['data']['num']['other'];
        if (!$count){
            $ret['data']['rate']['szx'] = 0;
            $ret['data']['rate']['sn'] = 0;
            $ret['data']['rate']['sb'] = 0;
            $ret['data']['rate']['sz'] = 0;
            $ret['data']['rate']['other'] = 0;
            //检测自检率
            $ret['data']['self_check_rate'] = 0;
        }else{
            $ret['data']['rate']['szx'] = round($szx / $count,2);
            $ret['data']['rate']['sn'] = round($sn / $count,2);
            $ret['data']['rate']['sb'] = round($sb / $count,2);
            $ret['data']['rate']['sz'] = round($sz / $count,2);
            $ret['data']['rate']['other'] = 1 - round($szx / $count,2) - round($sn / $count,2)
                - round($sb / $count,2) - round($sz / $count,2) ;
            //检测自检率
            $ret['data']['self_check_rate'] = round(($szx + $sn + $sb + $sz) / $count,2);
        }

        //季度评分排行榜
        //获取当前季度
        $quarter = ceil(date('m') / 3);
        $quarter_reflection = array(
            '1'=>'第一季度',
            '2'=>'第二季度',
            '3'=>'第三季度',
            '4'=>'第四季度',
        );
        $quarter_name = $quarter_reflection[$quarter];
        $year = date('Y');
        //1、新建本季度四个机构的rank记录
        //2、往rank_breakdown表中插入试验质量告警和逾期的数据
        //3、并计算该季度四个机构的分数
        //得分=100-本季度完成任务累计超期天数*0.5-系统发现的作业规范性问题数*1-∑手动维护的扣分
        $rank_ids = $this->getRankIds($year,$quarter_name);

        if ($quarter == 1){

            $overtime_info = Db::table('dky_testing_problem')
                ->field('sum(overtime_duration) as num,district_id')
                ->where('problem_type','逾期')
                ->where('occur_at','>',$year.'-01-01')
                ->group('district_id')
                ->select();

            foreach ($overtime_info as $k=>$v){
                $district = $this->name_reflection_inverse[$v['district_id']];
                //看是否存在此机构该季度的超期信息，有就修改，没有就新增

                $id = Db::table('rank_breakdown')
                    ->where('year',$year)
                    ->where('quarter',$quarter_name)
                    ->where('institution',$district)
                    ->where('name','逾期')
                    ->value('id');

                if ($id){
                    $sum = -0.5*$v['num'];
                    Db::table('rank_breakdown')->where('id',$id)->update([
                        'score'=>-0.5,
                        'num'=>$v['num'],
                        'sum'=>$sum,
                        'name'=>'逾期',
                        'content'=>'本季度完成任务累计超期'.$v['num'].'天',
                            'updatetime'=>time(),
                        ]
                    );
                }else{
                    $sum = -0.5*$v['num'];
                    Db::table('rank_breakdown')->insert([
                            'rank_token'=>$rank_ids[$v['district_id']],
                            'year'=>$year,
                            'quarter'=>$quarter_name,
                            'institution'=>$district,
                            'score'=>-0.5,
                            'num'=>$v['num'],
                            'sum'=>$sum,
                            'name'=>'逾期',
                            'content'=>'本季度完成任务累计超期'.$v['num'].'天',
                            'createtime'=>time(),
                            'updatetime'=>time(),
                        ]
                    );
                }

            }


            $gf_info =   $overtime_info = Db::table('dky_testing_problem')
                ->field('count(*) as  num,district_id')
                ->where('problem_type','作业不规范')
                ->where('occur_at','>',$year.'-01-01')
                ->group('district_id')
                ->select();


            foreach ($gf_info as $k=>$v){
                $district = $this->name_reflection_inverse[$v['district_id']];
                //看是否存在此机构该季度的超期信息，有就修改，没有就新增

                $id = Db::table('rank_breakdown')
                    ->where('year',$year)
                    ->where('quarter',$quarter_name)
                    ->where('institution',$district)
                    ->where('name','作业不规范')
                    ->value('id');

                if ($id){
                    $sum = -1*$v['num'];
                    Db::table('rank_breakdown')->where('id',$id)->update([
                            'score'=>-1,
                            'num'=>$v['num'],
                            'sum'=>$sum,
                            'name'=>'作业不规范',
                            'content'=>'系统发现的规范问题数'.$v['num'],
                            'updatetime'=>time(),
                        ]
                    );
                }else{
                    $sum = -1*$v['num'];
                    Db::table('rank_breakdown')->insert([
                            'rank_token'=>$rank_ids[$v['district_id']],
                            'year'=>$year,
                            'quarter'=>$quarter_name,
                            'institution'=>$district,
                            'score'=>-1,
                            'num'=>$v['num'],
                            'sum'=>$sum,
                            'name'=>'作业不规范',
                            'content'=>'系统发现的规范问题数'.$v['num'],
                            'createtime'=>time(),
                            'updatetime'=>time(),
                            ]
                    );
                }

            }



        }elseif ($quarter == 2){
            $overtime_info = Db::table('dky_testing_problem')
                ->field('sum(overtime_duration) as num,district_id')
                ->where('problem_type','逾期')
                ->where('occur_at','>',$year.'-04-01')
                ->group('district_id')
                ->select();

            foreach ($overtime_info as $k=>$v){
                $district = $this->name_reflection_inverse[$v['district_id']];
                //看是否存在此机构该季度的超期信息，有就修改，没有就新增

                $id = Db::table('rank_breakdown')
                    ->where('year',$year)
                    ->where('quarter',$quarter_name)
                    ->where('institution',$district)
                    ->where('name','逾期')
                    ->value('id');

                if ($id){
                    $sum = -0.5*$v['num'];
                    Db::table('rank_breakdown')->where('id',$id)->update([
                            'score'=>-0.5,
                            'num'=>$v['num'],
                            'sum'=>$sum,
                            'name'=>'逾期',
                            'content'=>'本季度完成任务累计超期'.$v['num'].'天',

                            'updatetime'=>time(),
                        ]
                    );
                }else{
                    $sum = -0.5*$v['num'];
                    Db::table('rank_breakdown')->insert([
                            'rank_token'=>$rank_ids[$v['district_id']],
                            'year'=>$year,
                            'quarter'=>$quarter_name,
                            'institution'=>$district,
                            'score'=>-0.5,
                            'num'=>$v['num'],
                            'sum'=>$sum,
                            'name'=>'逾期',
                            'content'=>'本季度完成任务累计超期'.$v['num'].'天',
                            'createtime'=>time(),
                            'updatetime'=>time(),
                        ]
                    );
                }

            }


            $gf_info =   $overtime_info = Db::table('dky_testing_problem')
                ->field('count(*) as  num,district_id')
                ->where('problem_type','作业不规范')
                ->where('occur_at','>',$year.'-04-01')
                ->group('district_id')
                ->select();


            foreach ($gf_info as $k=>$v){
                $district = $this->name_reflection_inverse[$v['district_id']];
                //看是否存在此机构该季度的超期信息，有就修改，没有就新增

                $id = Db::table('rank_breakdown')
                    ->where('year',$year)
                    ->where('quarter',$quarter_name)
                    ->where('institution',$district)
                    ->where('name','作业不规范')
                    ->value('id');

                if ($id){
                    $sum = -1*$v['num'];
                    Db::table('rank_breakdown')->where('id',$id)->update([
                            'score'=>-1,
                            'num'=>$v['num'],
                            'sum'=>$sum,
                            'name'=>'作业不规范',
                            'content'=>'系统发现的规范问题数'.$v['num'],
                            'updatetime'=>time(),
                        ]
                    );
                }else{
                    $sum = -1*$v['num'];
                    Db::table('rank_breakdown')->insert([
                            'rank_token'=>$rank_ids[$v['district_id']],
                            'year'=>$year,
                            'quarter'=>$quarter_name,
                            'institution'=>$district,
                            'score'=>-1,
                            'num'=>$v['num'],
                            'sum'=>$sum,
                            'name'=>'作业不规范',
                            'content'=>'系统发现的规范问题数'.$v['num'],
                            'createtime'=>time(),
                            'updatetime'=>time(),
                            ]
                    );
                }

            }

        }elseif ($quarter == 3){
            $overtime_info = Db::table('dky_testing_problem')
                ->field('sum(overtime_duration) as num,district_id')
                ->where('problem_type','逾期')
                ->where('occur_at','>',$year.'-07-01')
                ->group('district_id')
                ->select();

            foreach ($overtime_info as $k=>$v){
                $district = $this->name_reflection_inverse[$v['district_id']];
                //看是否存在此机构该季度的超期信息，有就修改，没有就新增

                $id = Db::table('rank_breakdown')
                    ->where('year',$year)
                    ->where('quarter',$quarter_name)
                    ->where('institution',$district)
                    ->where('name','逾期')
                    ->value('id');

                if ($id){
                    $sum = -0.5*$v['num'];
                    Db::table('rank_breakdown')->where('id',$id)->update([
                            'score'=>-0.5,
                            'num'=>$v['num'],
                            'sum'=>$sum,
                            'name'=>'逾期',
                            'content'=>'本季度完成任务累计超期'.$v['num'].'天',
                        ]
                    );
                }else{
                    $sum = -0.5*$v['num'];
                    Db::table('rank_breakdown')->insert([
                            'rank_token'=>$rank_ids[$v['district_id']],
                            'year'=>$year,
                            'quarter'=>$quarter_name,
                            'institution'=>$district,
                            'score'=>-0.5,
                            'num'=>$v['num'],
                            'sum'=>$sum,
                            'name'=>'逾期',
                            'content'=>'本季度完成任务累计超期'.$v['num'].'天',
                        ]
                    );
                }

            }


            $gf_info =   $overtime_info = Db::table('dky_testing_problem')
                ->field('count(*) as  num,district_id')
                ->where('problem_type','作业不规范')
                ->where('occur_at','>',$year.'-07-01')
                ->group('district_id')
                ->select();


            foreach ($gf_info as $k=>$v){
                $district = $this->name_reflection_inverse[$v['district_id']];
                //看是否存在此机构该季度的超期信息，有就修改，没有就新增

                $id = Db::table('rank_breakdown')
                    ->where('year',$year)
                    ->where('quarter',$quarter_name)
                    ->where('institution',$district)
                    ->where('name','作业不规范')
                    ->value('id');

                if ($id){
                    $sum = -1*$v['num'];
                    Db::table('rank_breakdown')->where('id',$id)->update([
                            'score'=>-1,
                            'num'=>$v['num'],
                            'sum'=>$sum,
                            'name'=>'作业不规范',
                            'content'=>'系统发现的规范问题数'.$v['num'],
                        ]
                    );
                }else{
                    $sum = -1*$v['num'];
                    Db::table('rank_breakdown')->insert([
                            'rank_token'=>$rank_ids[$v['district_id']],
                            'year'=>$year,
                            'quarter'=>$quarter_name,
                            'institution'=>$district,
                            'score'=>-1,
                            'num'=>$v['num'],
                            'sum'=>$sum,
                            'name'=>'作业不规范',
                            'content'=>'系统发现的规范问题数'.$v['num'],                        ]
                    );
                }

            }

        }elseif($quarter == 4){
            $overtime_info = Db::table('dky_testing_problem')
                ->field('sum(overtime_duration) as num,district_id')
                ->where('problem_type','逾期')
                ->where('occur_at','>',$year.'-10-01')
                ->group('district_id')
                ->select();

            foreach ($overtime_info as $k=>$v){
                $district = $this->name_reflection_inverse[$v['district_id']];
                //看是否存在此机构该季度的超期信息，有就修改，没有就新增

                $id = Db::table('rank_breakdown')
                    ->where('year',$year)
                    ->where('quarter',$quarter_name)
                    ->where('institution',$district)
                    ->where('name','逾期')
                    ->value('id');

                if ($id){
                    $sum = -0.5*$v['num'];
                    Db::table('rank_breakdown')->where('id',$id)->update([
                            'score'=>-0.5,
                            'num'=>$v['num'],
                            'sum'=>$sum,
                            'name'=>'逾期',
                            'content'=>'本季度完成任务累计超期'.$v['num'].'天',
                        ]
                    );
                }else{
                    $sum = -0.5*$v['num'];
                    Db::table('rank_breakdown')->insert([
                            'rank_token'=>$rank_ids[$v['district_id']],
                            'year'=>$year,
                            'quarter'=>$quarter_name,
                            'institution'=>$district,
                            'score'=>-0.5,
                            'num'=>$v['num'],
                            'sum'=>$sum,
                            'name'=>'逾期',
                            'content'=>'本季度完成任务累计超期'.$v['num'].'天',
                        ]
                    );
                }

            }


            $gf_info =   $overtime_info = Db::table('dky_testing_problem')
                ->field('count(*) as  num,district_id')
                ->where('problem_type','作业不规范')
                ->where('occur_at','>',$year.'-10-01')
                ->group('district_id')
                ->select();


            foreach ($gf_info as $k=>$v){
                $district = $this->name_reflection_inverse[$v['district_id']];
                //看是否存在此机构该季度的超期信息，有就修改，没有就新增

                $id = Db::table('rank_breakdown')
                    ->where('year',$year)
                    ->where('quarter',$quarter_name)
                    ->where('institution',$district)
                    ->where('name','作业不规范')
                    ->value('id');

                if ($id){
                    $sum = -1*$v['num'];
                    Db::table('rank_breakdown')->where('id',$id)->update([
                            'score'=>-1,
                            'num'=>$v['num'],
                            'sum'=>$sum,
                            'name'=>'作业不规范',
                            'content'=>'系统发现的规范问题数'.$v['num'],
                        ]
                    );
                }else{
                    $sum = -1*$v['num'];
                    Db::table('rank_breakdown')->insert([
                            'rank_token'=>$rank_ids[$v['district_id']],
                            'year'=>$year,
                            'quarter'=>$quarter_name,
                            'institution'=>$district,
                            'score'=>-1,
                            'num'=>$v['num'],
                            'sum'=>$sum,
                            'name'=>'作业不规范',
                            'content'=>'系统发现的规范问题数'.$v['num'],                        ]
                    );
                }

            }

        }


        //计算各中心季度分数
        $this->calculateRank($rank_ids,$year,$quarter_name);
        $res = Db::table('rank')
            ->where('year',$year)
            ->where('quarter',$quarter_name)
            ->order('score desc')
            ->select();
        $i = 1;
        foreach ($res as $k=>$v){
            $res[$k]['xh'] = $i++;
        }
        $ret['data']['score'] = $res;
        //全省各中心平均节省率
        $ret['data']['save'] = $this->getSavingRate();
        return json_encode($ret,320);
    }

    public function calculateRank($rank_ids,$year,$quarter_name)
    {
        $this->calculateRankAssist($rank_ids[1],$year,$quarter_name,'省中心（电科院）');
        $this->calculateRankAssist($rank_ids[2],$year,$quarter_name,'苏南分中心');
        $this->calculateRankAssist($rank_ids[3],$year,$quarter_name,'苏中分中心');
        $this->calculateRankAssist($rank_ids[4],$year,$quarter_name,'苏北分中心');
    }

    public function calculateRankAssist($id,$year,$quarter_name,$institution)
    {
        $rank_breakdown_sum = Db::table('rank_breakdown')
        ->where('year',$year)
        ->where('quarter',$quarter_name)
        ->where('institution',$institution)
        ->sum('sum');
        $score = 100 + $rank_breakdown_sum;
        Db::table('rank')->where('id',$id)->update(['score'=>$score,'updatetime'=>time()]);
        return $score;
    }

    public function getSavingRate()
    {
        $arr = [];
        $arr['szx']['device'] = 0;
        $arr['szx']['material'] = 0;
        $arr['sz']['device'] = 0;
        $arr['sz']['material'] = 0;
        $arr['sn']['device'] = 0;
        $arr['sn']['material'] = 0;
        $arr['sb']['device'] = 0;
        $arr['sb']['material'] = 0;
        $arr['other']['device'] = 0;
        $arr['other']['material'] = 0;

        //仅统计当年已完成的
        $res = Db::table('dky_mission')
            ->field('sum(testing_duration) as sjhf,sum(overtime_norm) as gdhf,testing_institution,device_type_belong')
            ->where('finish_time','<>','1970-01-01 00:00:00')
            ->whereNotNull('device_type_belong')
            ->group('testing_institution,device_type_belong')
            ->select();
        $other_device_gdhf = 0;
        $other_device_sjhf = 0;
        $other_material_gdhf = 0;
        $other_material_sjhf = 0;
        foreach ($res as $k=>$v){
            $gdhf = $v['gdhf'];
            $sjhf = $v['sjhf'];
            if ($v['testing_institution'] == '省中心（电科院）' && $v['device_type_belong'] == '设备'){
                if ($gdhf){
                    $arr['szx']['device'] = round(($gdhf - $sjhf) / $gdhf , 2);
                }else{
                    $arr['szx']['device'] = 0;
                }
            }elseif ($v['testing_institution'] == '省中心（电科院）' && $v['device_type_belong'] == '材料'){
                if ($gdhf){
                    $arr['szx']['material'] = round(($gdhf - $sjhf) / $gdhf , 2);
                }else{
                    $arr['szx']['material'] = 0;
                }
            }elseif ($v['testing_institution'] == '苏北分中心' && $v['device_type_belong'] == '设备'){
                if ($gdhf){
                    $arr['sb']['device'] = round(($gdhf - $sjhf) / $gdhf , 2);
                }else{
                    $arr['sb']['device'] = 0;
                }
            }elseif ($v['testing_institution'] == '苏北分中心' && $v['device_type_belong'] == '材料'){
                if ($gdhf){
                    $arr['sb']['material'] = round(($gdhf - $sjhf) / $gdhf , 2);
                }else{
                    $arr['sb']['material'] = 0;
                }
            }elseif ($v['testing_institution'] == '苏中分中心' && $v['device_type_belong'] == '设备'){
                if ($gdhf){
                    $arr['sz']['device'] = round(($gdhf - $sjhf) / $gdhf , 2);
                }else{
                    $arr['sz']['device'] = 0;
                }
            }elseif ($v['testing_institution'] == '苏中分中心' && $v['device_type_belong'] == '材料'){
                if ($gdhf){
                    $arr['sz']['material'] = round(($gdhf - $sjhf) / $gdhf , 2);
                }else{
                    $arr['sz']['material'] = 0;
                }
            }elseif ($v['testing_institution'] == '苏南分中心' && $v['device_type_belong'] == '设备'){
                if ($gdhf){
                    $arr['sn']['device'] = round(($gdhf - $sjhf) / $gdhf , 2);
                }else{
                    $arr['sn']['device'] = 0;
                }
            }elseif ($v['testing_institution'] == '苏南分中心' && $v['device_type_belong'] == '材料'){
                if ($gdhf){
                    $arr['sn']['material'] = round(($gdhf - $sjhf) / $gdhf , 2);
                }else{
                    $arr['sn']['material'] = 0;
                }
            }else{
                if ($v['device_type_belong'] == '设备'){
                    $other_device_gdhf += $gdhf;
                    $other_device_sjhf += $sjhf;
                }
                if ($v['device_type_belong'] == '材料'){
                    $other_material_gdhf += $gdhf;
                    $other_material_sjhf += $sjhf;
                }
            }
        }

        if ($other_device_sjhf){
            $arr['other']['device'] = round(($other_device_gdhf - $other_device_sjhf) / $other_device_gdhf , 2);
        }else{
            $arr['other']['device'] = 0;
        }
        if ($other_material_sjhf){
            $arr['other']['material'] = round(($other_material_gdhf - $other_material_sjhf) / $other_material_gdhf , 2);
        }else{
            $arr['other']['material'] = 0;
        }
        return $arr;
    }


    //获取某年某季度四个机构的rank表的主键id
    public function getRankIds($year,$quarter_name)
    {
        $arr = [
            1=>0,
            3=>0,
            2=>0,
            4=>0,
        ];
        $res = Db::table('rank')
            ->where('year',$year)
            ->where('quarter',$quarter_name)
            ->select();
        $res = array_column($res,null,'institution');
        if (isset($res['省中心（电科院）'])){
            $arr[1] = $res['省中心（电科院）']['id'];
        }else{
            $arr[1] = Db::table('rank')->insertGetId([
                'year'=>$year,
                'quarter'=>$quarter_name,
                'institution'=>'省中心（电科院）',
                'createtime'=>time(),
                'updatetime'=>time()
            ]);
        }

        if (isset($res['苏中分中心'])){
            $arr[3] = $res['苏中分中心']['id'];
        }else{
            $arr[3] = Db::table('rank')->insertGetId([
                'year'=>$year,
                'quarter'=>$quarter_name,
                'institution'=>'苏中分中心',
                'createtime'=>time(),
                'updatetime'=>time()
            ]);
        }

        if (isset($res['苏北分中心'])){
            $arr[4] = $res['苏北分中心']['id'];
        }else{
            $arr[4] = Db::table('rank')->insertGetId([
                'year'=>$year,
                'quarter'=>$quarter_name,
                'institution'=>'苏北分中心',
                'createtime'=>time(),
                'updatetime'=>time()
            ]);
        }

        if (isset($res['苏南分中心'])){
            $arr[2] = $res['苏南分中心']['id'];
        }else{
            $arr[2] = Db::table('rank')->insertGetId([
                'year'=>$year,
                'quarter'=>$quarter_name,
                'institution'=>'苏南分中心',
                'createtime'=>time(),
                'updatetime'=>time()
            ]);
        }
        return $arr;
    }


    public function getRankBreakdown()
    {
        $ret = [
            'code'=>1,
            'msg'=>'success',
            'data'=>[]
        ];
        $id = input('param.token','','addslashes,trim,htmlspecialchars,strip_tags');
        if (!$id)
        {
            $ret = [
                'code'=>0,
                'msg'=>'id不能为空',
                'data'=>[]
            ];
            return json_encode($ret,320);
        }
        $res = Db::table('rank_breakdown')->where('rank_token',$id)->select();
        $i = 1;
        foreach ($res as $k=>$v){
            $res[$k]['xh'] = $i++;
        }
        $ret['data'] = $res;
        return json_encode($ret,320);

    }

    /**
     * 发送结果数据，用以展示结果报告
     */
    public function sendReportData()
    {
        $ret = array(
            'code'=>1,
            'msg'=>'success'
        );
        //二次盲样号
        $s_code = input('param.s_code','','addslashes,trim,htmlspecialchars,strip_tags');

        //物资类别
        $type = input('param.type','','addslashes,trim,htmlspecialchars,strip_tags');
        //检测机构
        $jcjg = input('param.jcjg','','addslashes,trim,htmlspecialchars,strip_tags');

        //参数json字符串
        $json = input('param.json','','trim,xss_clean');
        if (!$s_code ||  !$type || !$jcjg  )
        {
            $ret['code'] = 0;
            $ret['msg'] = '错误，二次盲样号、检测机构、物资类别不能为空';
            return json_encode($ret,320);
        }



        if (!in_array($jcjg,['苏南分中心','省中心（电科院）','苏中分中心','苏北分中心']))
        {
            $ret['code'] = 3;
            $ret['msg'] = '错误，检测机构参数非法，请参考接口文档';
            return json_encode($ret,320);
        }

        //TODO 处理结果数据字段
        $json = $this->disposeResultData($json);
        //$if_exist = Db::table('dky_report_data')->where('s_code',$s_code)->count();
        //参数列表
        $param = [
            's_code'=>$s_code,
            'jcjg'=>$jcjg,
            'type'=>$type,
            'json'=>$json,
        ];
//        if ($if_exist)
//        {
//            $res = Db::table('dky_report_data')->where('s_code',$s_code)->update($param);
//        }else{
            $res = Db::table('dky_report_data')->insert($param);
        //}

        if ($res === false)
        {
            $ret['code'] = 2;
            $ret['msg'] = '插入失败，请重试';
        }else{
            $ret['code'] = 1;
            $ret['msg'] = 'success,插入成功';
        }


        return json_encode($ret,320);
    }


    public function disposeResultData($json)
    {
        //如果json为空，则返回空
        if (!$json)
        {
            return '{}';
        }

        $json_arr = json_decode($json,true);
        if (isset($json_arr['testDataMap'])){
            //获取结果数据数组
            //$results = $json_arr['testDataMap'];
            //获取数据映射规则
            $maps = Db::table('report_mapping')->select();
            $maps = array_column($maps,null,'key');
            foreach ($json_arr['testDataMap'] as $k=>$v){
                if (isset($maps[$k])){
                    //替换字段名,需将每个字段对应到各个试验
                    $json_arr['testDataMapPlus'][$maps[$k]['experiment_name']][0][$maps[$k]['name']] = $v.' '.$maps[$k]['unit'];
                }
            }
            unset($json_arr['testDataMap']);
            unset($json_arr['blindNumber']);
            unset($json_arr['testIndex']);
            //将处理完后的json数组转化为json字符串
            $json_str = json_encode($json_arr,320);
            return $json_str;

        }else{
            //如果不存在，则原样返回
            return $json;
        }

    }
}
