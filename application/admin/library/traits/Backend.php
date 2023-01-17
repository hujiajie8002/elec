<?php

namespace app\admin\library\traits;

use app\admin\library\Auth;
use Exception;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Session;

trait Backend
{

    //导入时需要清空的表名
    protected $need_truncate_tables = array(
      'experiment'
    );

    public $error_info = '';
    /**
     * 排除前台提交过来的字段
     * @param $params
     * @return array
     */
    protected function preExcludeFields($params)
    {
        if (is_array($this->excludeFields)) {
            foreach ($this->excludeFields as $field) {
                if (key_exists($field, $params)) {
                    unset($params[$field]);
                }
            }
        } else {
            if (key_exists($this->excludeFields, $params)) {
                unset($params[$this->excludeFields]);
            }
        }
        return $params;
    }


    /**
     * 查看
     */
    public function index()
    {
        $admin = Session::get('admin');
        $username = $admin['username'];
        $baseUrl = request()->baseUrl();
        $baseUrl = substr($baseUrl,9);

        $reflect = [
            'snfzx'=>2,
            'sbfzx'=>4,
            'szfzx'=>3,
        ];
        $reflect2 = [
            'snfzx'=>'苏南分中心',
            'sbfzx'=>'苏北分中心',
            'szfzx'=>'苏中分中心',
        ];
        //所有需要区分机构名称的方法
        $arr = array(
            'dky_device',
            'dky_station',
            'dky_agv',
            'dky_storage_rack',
            'maintenance_log',
            'dky_testing_problem',
            'dky_device/index',
            'dky_station/index',
            'dky_agv/index',
            'dky_storage_rack/index',
            'maintenance_log/index',
            'dky_testing_problem/index',
            'dky_staff',
            'dky_staff/index'

        );

        $arr2 = array(
            'laboratory_info/index',
            'laboratory_info',
            'district/index',
            'district',
        );

        $arr3 = array(
            'dky_mission',
            'dky_mission/index'
        );

        $arr4 = array(
            'rank',
            'rank/index',
            'rank_breakdown',
            'rank_breakdown/index'
        );
        //设置过滤方法
        $this->request->filter(['trim','strip_tags']);
        if ($this->request->isAjax()) {

            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            if (($username == 'snfzx' || $username == 'szfzx' || $username == 'sbfzx') && in_array($baseUrl,$arr))
            {
                $list = $this->model
                    ->where($where)
                    ->where('district_id',$reflect[$username])
                    ->order($sort, $order)
                    ->paginate($limit);
            }elseif (($username == 'snfzx' || $username == 'szfzx' || $username == 'sbfzx') && in_array($baseUrl,$arr2)){
                $list = $this->model
                    ->where($where)
                    ->where('name',$reflect2[$username])
                    ->order($sort, $order)
                    ->paginate($limit);
            }elseif (($username == 'snfzx' || $username == 'szfzx' || $username == 'sbfzx') && in_array($baseUrl,$arr3)){
                $list = $this->model
                    ->where($where)
                    ->where('testing_institution',$reflect2[$username])
                    ->order($sort, $order)
                    ->paginate($limit);
            }elseif (($username == 'snfzx' || $username == 'szfzx' || $username == 'sbfzx') && in_array($baseUrl,$arr4)){
                $list = $this->model
                    ->where($where)
                    ->where('institution',$reflect2[$username])
                    ->order($sort, $order)
                    ->paginate($limit);
            }else{
                $list = $this->model
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);
            }


            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 回收站
     */
    public function recyclebin()
    {
        //设置过滤方法
        $this->request->filter(['trim','strip_tags','xss_clean']);
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                ->onlyTrashed()
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $this->token();
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                $this->fzyq($params);
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error(Session::get('fzyq_error_info')??$e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $this->token();
            $params = $this->request->post("row/a");
            if ($params) {
                $this->fzyq($params);

                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }

                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error(Session::get('fzyq_error_info')??$e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->fzyq($row);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }

        $ids = $ids ? $ids : $this->request->post("ids");
        if ($ids) {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where($pk, 'in', $ids)->select();
            $count = 0;
            Db::startTrans();
            try {
                foreach ($list as $k => $v) {
                    $this->fzyq($v);
                    $count += $v->delete();
                }
                Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error(Session::get('fzyq_error_info')??$e->getMessage());
            }
            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 真实删除
     */
    public function destroy($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        $pk = $this->model->getPk();
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }
        if ($ids) {
            $this->model->where($pk, 'in', $ids);
        }
        $count = 0;
        Db::startTrans();
        try {
            $list = $this->model->onlyTrashed()->select();
            foreach ($list as $k => $v) {
                $this->fzyq($v);
                $count += $v->delete(true);
            }
            Db::commit();
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            Db::rollback();
            $this->error(Session::get('fzyq_error_info')??$e->getMessage());
        }
        if ($count) {
            $this->success();
        } else {
            $this->error(__('No rows were deleted'));
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 还原
     */
    public function restore($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        $pk = $this->model->getPk();
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }
        if ($ids) {
            $this->model->where($pk, 'in', $ids);
        }
        $count = 0;
        Db::startTrans();
        try {
            $list = $this->model->onlyTrashed()->select();
            foreach ($list as $index => $item) {
                $this->fzyq($item);
                $count += $item->restore();
            }
            Db::commit();
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            Db::rollback();
            $this->error(Session::get('fzyq_error_info')??$e->getMessage());
        }
        if ($count) {
            $this->success();
        }
        $this->error(__('No rows were updated'));
    }

    /**
     * 批量更新
     */
    public function multi($ids = "")
    {
        $this->error('未开放此功能');
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        if ($ids) {
            if ($this->request->has('params')) {
                parse_str($this->request->post("params"), $values);
                $values = $this->auth->isSuperAdmin() ? $values : array_intersect_key($values, array_flip(is_array($this->multiFields) ? $this->multiFields : explode(',', $this->multiFields)));
                if ($values) {
                    $adminIds = $this->getDataLimitAdminIds();
                    if (is_array($adminIds)) {
                        $this->model->where($this->dataLimitField, 'in', $adminIds);
                    }
                    $count = 0;
                    Db::startTrans();
                    try {
                        $list = $this->model->where($this->model->getPk(), 'in', $ids)->select();
                        foreach ($list as $index => $item) {
                            $count += $item->allowField(true)->isUpdate(true)->save($values);
                        }
                        Db::commit();
                    } catch (PDOException $e) {
                        Db::rollback();
                        $this->error($e->getMessage());
                    } catch (Exception $e) {
                        Db::rollback();
                        $this->error($e->getMessage());
                    }
                    if ($count) {
                        $this->success();
                    } else {
                        $this->error(__('No rows were updated'));
                    }
                } else {
                    $this->error(__('You have no permission'));
                }
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 导入
     */
    protected function import()
    {
        $this->error('未开放此功能');
        $file = $this->request->request('file');
        if (!$file) {
            $this->error(__('Parameter %s can not be empty', 'file'));
        }
        $filePath = ROOT_PATH . DS . 'public' . DS . $file;
        if (!is_file($filePath)) {
            $this->error(__('No results were found'));
        }
        //实例化reader
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
            $this->error(__('Unknown data format'));
        }
        if ($ext === 'csv') {
            $file = fopen($filePath, 'r');
            $filePath = tempnam(sys_get_temp_dir(), 'import_csv');
            $fp = fopen($filePath, "w");
            $n = 0;
            while ($line = fgets($file)) {
                $line = rtrim($line, "\n\r\0");
                $encoding = mb_detect_encoding($line, ['utf-8', 'gbk', 'latin1', 'big5']);
                if ($encoding != 'utf-8') {
                    $line = mb_convert_encoding($line, 'utf-8', $encoding);
                }
                if ($n == 0 || preg_match('/^".*"$/', $line)) {
                    fwrite($fp, $line . "\n");
                } else {
                    fwrite($fp, '"' . str_replace(['"', ','], ['""', '","'], $line) . "\"\n");
                }
                $n++;
            }
            fclose($file) || fclose($fp);

            $reader = new Csv();
        } elseif ($ext === 'xls') {
            $reader = new Xls();
        } else {
            $reader = new Xlsx();
        }

        //导入文件首行类型,默认是注释,如果需要使用字段名称请使用name
        $importHeadType = isset($this->importHeadType) ? $this->importHeadType : 'comment';

        $table = $this->model->getQuery()->getTable();
        $database = \think\Config::get('database.database');
        $fieldArr = [];
        $list = db()->query("SELECT COLUMN_NAME,COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ?", [$table, $database]);
        //如果导入的时候还需清空表的话，则加上下面的判断
        if ( in_array($table,$this->need_truncate_tables)){
            db()->query('truncate table '.$table);
        }
        foreach ($list as $k => $v) {
            if ($importHeadType == 'comment') {
                //根据excel表首行的文字注释和mysql表的字段注释进行对应
                $fieldArr[$v['COLUMN_COMMENT']] = $v['COLUMN_NAME'];
            } else {
                $fieldArr[$v['COLUMN_NAME']] = $v['COLUMN_NAME'];
            }
        }


        //加载文件
        $insert = [];
        try {
            if (!$PHPExcel = $reader->load($filePath)) {
                $this->error(__('Unknown data format'));
            }
            $currentSheet = $PHPExcel->getSheet(0);  //读取文件中的第一个工作表
            $allColumn = $currentSheet->getHighestDataColumn(); //取得最大的列号
            $allRow = $currentSheet->getHighestRow(); //取得一共有多少行
            $maxColumnNumber = Coordinate::columnIndexFromString($allColumn);
            $fields = [];
            //获取excel第一行的注释
            for ($currentRow = 1; $currentRow <= 1; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $fields[] = $val;
                }
            }

            //获取第二行开始的数据
            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                $values = [];
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $values[] = is_null($val) ? '' : $val;
                }
                $row = [];
                $temp = array_combine($fields, $values);
                foreach ($temp as $k => $v) {
                    if (isset($fieldArr[$k]) && $k !== '') {
                        $row[$fieldArr[$k]] = $v;
                    }
                }

                if ($row) {
                    $insert[] = $row;
                }
            }


        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
        //var_dump($insert);
        if (!$insert) {
            $this->error(__('No rows were updated'));
        }

        try {
            //是否包含admin_id字段
            $has_admin_id = false;
            foreach ($fieldArr as $name => $key) {
                if ($key == 'admin_id') {
                    $has_admin_id = true;
                    break;
                }
            }
            if ($has_admin_id) {
                $auth = Auth::instance();
                foreach ($insert as &$val) {
                    if (!isset($val['admin_id']) || empty($val['admin_id'])) {
                        $val['admin_id'] = $auth->isLogin() ? $auth->id : 0;
                    }
                }
            }
            $this->model->saveAll($insert);
        } catch (PDOException $exception) {
            $msg = $exception->getMessage();
            if (preg_match("/.+Integrity constraint violation: 1062 Duplicate entry '(.+)' for key '(.+)'/is", $msg, $matches)) {
                $msg = "导入失败，包含【{$matches[1]}】的记录已存在";
            };
            $this->error($msg);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success();
    }

    //将device_no根据device_no的主键值处理一下，更新为主键值+1
    public function processDeviceNo($params, $type)
    {
        $ret = ['params'=>$params,'flag'=>0];
        if (isset($params['device_no'])){
            $max_device_no = Db::table('device_no')->max('id');
            $ret['params']['device_no'] = ++$max_device_no;
            //向device_no表中插数据
            $line = [
                'id'=>$max_device_no,
                'type'=>$type
            ];
            $res = Db::table('device_no')->insert($line);
            if ($res){
                $ret['flag'] = 1;
            }else{
                $ret['flag'] = 0;
            }
        }
        return $ret;
    }



    /**
     * @param $haystack
     * @param $needle
     * @return array
     * 用于从二维数组中抽出needle列当键
     */
    public function dispose_double_dimension_array($haystack,$needle)
    {
        $arr = [];
        foreach ($haystack as $k=>$v)
        {
            $arr[$v[$needle]][] = $v;
        }
        return $arr;
    }

    public function saveLog($msg,$type)
    {
        $array = array(
            'msg'=>$msg,
            'type'=>$type,
            'url'=>'',
        );
        Db::table('log')->insert($array);
    }

    public function getParamFromResult($s_code,$e_name,$param,$msg,$type)
    {
        $result_json = Db::table('dky_experiment_data')
            ->where('s_code',$s_code)
            ->where('e_name','like',$e_name.'%')
            ->value('json');

        $result_json_arr = json_decode($result_json,true)[0];
        if (isset($result_json_arr[$param])){
            return $result_json_arr[$param];
        }else{
            $this->saveLog($msg,$type);
            return '参数不存在';
        }
    }

    /**
     * @param $type 取数据方式
     * @param $s_code redis key
     * @param int $n n
     * @return array
     */
    public function getResultFromRedis($type,$key,$n=0)
    {
        //创建redis连接
        $redis = new \Redis();
        $redis->connect(config('redis.ip'),config('redis.port'));
        $redis->auth(config('redis.fh'));
        //redis中取数据有三种
        $list_len = $redis->lLen($key);
        $arr = [];
        if ($type === 1){
            //1.list中全部
            for ($i=0;$i<=$list_len-1;$i++)
            {
                $val = $redis->lIndex($key,$i);
                $arr[] = $val;
            }
        }elseif ($type === 2){
            //2.list倒数n个
            $arr = $redis->lRange($key,-$n,-1);
        }elseif ($type === 3){
            //3.list 倒数 1/n 个
            $num = floor($list_len/$n);
            $arr = $redis->lRange($key,-$num,-1);
        }elseif ($type === 4){
            //list正数n个
            $arr = $redis->lRange($key,0,$n-1);
        }

        return $arr;

    }

    /**
     * @param $jcjg_id 检测机构id
     * @param $station 工位
     * @param $type 物资类型
     * @param $e_name 试验名称
     * @param $s_code 样品二维码
     * @param $description 描述
     * @param $status 状态
     */
    public function saveTestingProblem($jcjg_id,$station,$type,$e_name,$s_code,$description,$status)
    {
        $array = array(
            'district_id'=>$jcjg_id,
            'station'=>$station,
            'type'=>$type,
            'experiment_name'=>$e_name,
            'twins_token'=>$s_code,
            'description'=>$description,
            'status'=>$status,
            'occur_at'=>date('Y-m-d H:i:s'),
            'problem_type'=>'作业不规范'
        );

        Db::table('dky_testing_problem')->insert($array);
        Db::table('dky_mission_experiment')
            ->where('twins_token',$s_code)
            ->where('experiment',$e_name)
            ->update(['conclusion'=>'不合格']);
    }

    /**
     * @param $json_arr json数组
     * @param $district_id 区域id
     * @param $station 工位
     * @param $type 物资类别
     * @param $e_name 试验名称
     * @param $s_code 样品二维码
     * @param $parameter 参数
     * @param $description 错误描述
     * @param $log 日志信息
     * @param $flag 类型，1表示去json数组中查找以parameter为下标的值，2表示直接对比
     */
    public function checkParameter($json_arr,$district_id,$station,$type,$e_name,$s_code,$parameter,$description,$log,$rule,$flag)
    {
//dump($json_arr);
//dump($parameter);
//dump($rule);
//dump($json_arr[$parameter]);
//exit;
        if ($flag === 1){
            if (isset($json_arr[$parameter]))
            {
                $parameter_value = $json_arr[$parameter];
                //根据$rule传来的规则循环判断
		//dump($rule);exit;	
                //$rule_arr = explode(',',$rule);
//dump($rule_arr);
                //foreach ($rule_arr as $k=>$v)
                foreach ($rule as $k=>$v)
                {
                    //如果规定了要大于某个值，而实际上小于等于，则报警
                    if (($k === '>' )&& ($parameter_value <= $v)){
                        $array = array(
                            'district_id'=>$district_id,
                            'station'=>$station,
                            'type'=>$type,
                            'experiment_name'=>$e_name,
                            'twins_token'=>$s_code,
                            'description'=>$description,
                            'status'=>0,
                            'occur_at'=>date('Y-m-d H:i:s'),
                        );

                        Db::table('dky_testing_problem')->insert($array);
                    }

                    //如果规定了要大于等于某个值，而实际上小于，则报警
                    if (($k === '>=') && ($parameter_value < $v)){
                        $array = array(
                            'district_id'=>$district_id,
                            'station'=>$station,
                            'type'=>$type,
                            'experiment_name'=>$e_name,
                            'twins_token'=>$s_code,
                            'description'=>$description,
                            'status'=>0,
                            'occur_at'=>date('Y-m-d H:i:s'),
                        );

                        Db::table('dky_testing_problem')->insert($array);
                    }
                    //如果规定了要小于某个值，而实际上大于等于，则报警
                    if (($k === '<' )&& ($parameter_value >= $v)){
                        $array = array(
                            'district_id'=>$district_id,
                            'station'=>$station,
                            'type'=>$type,
                            'experiment_name'=>$e_name,
                            'twins_token'=>$s_code,
                            'description'=>$description,
                            'status'=>0,
                            'occur_at'=>date('Y-m-d H:i:s'),
                        );

                        Db::table('dky_testing_problem')->insert($array);
                    }

                    //如果规定了要小于等于某个值，而实际上大于等于，则报警
                    if (($k === '<=') && ($parameter_value > $v)){
//return 000;
                        $array = array(
                            'district_id'=>$district_id,
                            'station'=>$station,
                            'type'=>$type,
                            'experiment_name'=>$e_name,
                            'twins_token'=>$s_code,
                            'description'=>$description,
                            'status'=>0,
                            'occur_at'=>date('Y-m-d H:i:s'),
                        );

                        Db::table('dky_testing_problem')->insert($array);
                    }
                    if (($k === '=') && ($parameter_value != $v)){
                        $array = array(
                            'district_id'=>$district_id,
                            'station'=>$station,
                            'type'=>$type,
                            'experiment_name'=>$e_name,
                            'twins_token'=>$s_code,
                            'description'=>$description,
                            'status'=>0,
                            'occur_at'=>date('Y-m-d H:i:s'),
                        );

                        Db::table('dky_testing_problem')->insert($array);
                    }

                }
            }else{
                $this->saveLog($log,'试验质量服务缺少字段');
            }
        }elseif($flag === 2){
            //根据$rule传来的规则循环判断
            //$rule_arr = explode(',',$rule);
            //foreach ($rule_arr as $k=>$v)
            foreach ($rule as $k=>$v)
            {
                //如果规定了要大于某个值，而实际上小于等于，则报警
                if ($k === '>' && $parameter <= $v){
                    $array = array(
                        'district_id'=>$district_id,
                        'station'=>$station,
                        'type'=>$type,
                        'experiment_name'=>$e_name,
                        'twins_token'=>$s_code,
                        'description'=>$description,
                        'status'=>0,
                        'occur_at'=>date('Y-m-d H:i:s'),
                    );

                    Db::table('dky_testing_problem')->insert($array);
                }

                //如果规定了要大于等于某个值，而实际上小于，则报警
                if ($k === '>=' && $parameter < $v){
                    $array = array(
                        'district_id'=>$district_id,
                        'station'=>$station,
                        'type'=>$type,
                        'experiment_name'=>$e_name,
                        'twins_token'=>$s_code,
                        'description'=>$description,
                        'status'=>0,
                        'occur_at'=>date('Y-m-d H:i:s'),
                    );

                    Db::table('dky_testing_problem')->insert($array);
                }
                //如果规定了要小于某个值，而实际上大于等于，则报警
                if ($k === '<' && $parameter >= $v){
                    $array = array(
                        'district_id'=>$district_id,
                        'station'=>$station,
                        'type'=>$type,
                        'experiment_name'=>$e_name,
                        'twins_token'=>$s_code,
                        'description'=>$description,
                        'status'=>0,
                        'occur_at'=>date('Y-m-d H:i:s'),
                    );

                    Db::table('dky_testing_problem')->insert($array);
                }

                //如果规定了要小于等于某个值，而实际上大于等于，则报警
                if ($k === '<=' && $parameter > $v){
                    $array = array(
                        'district_id'=>$district_id,
                        'station'=>$station,
                        'type'=>$type,
                        'experiment_name'=>$e_name,
                        'twins_token'=>$s_code,
                        'description'=>$description,
                        'status'=>0,
                        'occur_at'=>date('Y-m-d H:i:s'),
                    );

                    Db::table('dky_testing_problem')->insert($array);
                }
            }
        }

    }

    //防止越权
    public function fzyq($params)
    {
        $baseUrl = request()->baseUrl();
        $baseUrl = substr($baseUrl,9);
        $admin = Session::get('admin');
        $username = $admin['username'];
        $reflect = [
            'snfzx'=>2,
            'sbfzx'=>4,
            'szfzx'=>3,
        ];
        $reflect2 = [
            'snfzx'=>'苏南分中心',
            'sbfzx'=>'苏北分中心',
            'szfzx'=>'苏中分中心',
        ];
        $arr = array(
            'dky_device',
            'dky_station',
            'dky_agv',
            'dky_storage_rack',
            'maintenance_log',
            'dky_testing_problem',
            'dky_device/add',
            'dky_station/add',
            'dky_agv/add',
            'dky_storage_rack/add',
            'maintenance_log/add',
            'dky_testing_problem/add',
            'dky_staff',
            'dky_staff/add',
            'dky_device/edit',
            'dky_station/edit',
            'dky_agv/edit',
            'dky_storage_rack/edit',
            'maintenance_log/edit',
            'dky_testing_problem/edit',
            'dky_staff/edit',
            'dky_device/del',
            'dky_station/del',
            'dky_agv/del',
            'dky_storage_rack/del',
            'maintenance_log/del',
            'dky_testing_problem/del',
            'dky_staff/del',
        );

        $arr2 = array(
            'laboratory_info/add',
            'laboratory_info',
            'district/add',
            'district',
            'laboratory_info/edit',
            'laboratory_info/del',
            'district/edit',
            'district/del',
        );

        $arr3 = array(
            'rank',
            'rank/add',
            'rank/edit',
            'rank/del',
            'rank_breakdown',
            'rank_breakdown/add',
            'rank_breakdown/edit',
            'rank_breakdown/del',
        );
        //省中心普通用户无操作权限
        if ($username == 'szx_general'){
            Db::rollback();
            $error_info = '您无权操作此数据，请注意各分中心只能维护分中心自己的数据~';
            $this->error_info = $error_info;
            Session::set('fzyq_error_info',$error_info);
            $this->error($error_info);
        }
        if ($this->search_assistant($baseUrl,$arr)){
            //分中心用户仅有本中心记录增删改查权限
            if (isset($params['district_id']) && in_array($username,['snfzx','sbfzx','szfzx']))
            {
                if ($params['district_id'] != $reflect[$username]){
                    Db::rollback();
                    $error_info = '您无权操作此数据，请注意各分中心只能维护分中心自己的数据~';
                    $this->error_info = $error_info;
                    Session::set('fzyq_error_info',$error_info);
                    $this->error($error_info);                }
            }
        }

        if ($this->search_assistant($baseUrl,$arr2)){
            //分中心用户仅有本中心记录增删改查权限
            if (isset($params['name']) && in_array($username,['snfzx','sbfzx','szfzx']))
            {
                if ($params['name'] != $reflect2[$username]){
                    Db::rollback();
                    $error_info = '您无权操作此数据，请注意各分中心只能维护分中心自己的数据~';
                    $this->error_info = $error_info;
                    Session::set('fzyq_error_info',$error_info);
                    $this->error($error_info);                  }
            }
        }

        if ($this->search_assistant($baseUrl,$arr3)){
            //分中心用户仅有本中心记录增删改查权限
            if (isset($params['institution']) && in_array($username,['snfzx','sbfzx','szfzx']))
            {
                if ($params['institution'] != $reflect2[$username]){
                    Db::rollback();
                    $error_info = '您无权操作此数据，请注意各分中心只能维护分中心自己的数据~';
                    $this->error_info = $error_info;
                    Session::set('fzyq_error_info',$error_info);
                    $this->error($error_info);                  }
            }
        }

    }

    //模糊查询$haystack中是否含有$needle
    public function search_assistant($needle,$haystack){
        foreach ($haystack as $v){
            if (strpos($needle,$v) !== false){
                return true;
            }
        }
        return  false;
    }

}
