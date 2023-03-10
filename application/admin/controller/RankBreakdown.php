<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use Exception;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Route;
use think\Session;

/**
 * 排名加减分细目
 *
 * @icon fa fa-circle-o
 */
class RankBreakdown extends Backend
{
    
    /**
     * RankBreakdown模型对象
     * @var \app\admin\model\RankBreakdown
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\RankBreakdown;
        $this->view->assign("quarterList", $this->model->getQuarterList());
        $this->view->assign("institutionList", $this->model->getInstitutionList());
    }

    public function import()
    {
        parent::import();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


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
     * 添加
     */
    public function add()
    {

        if ($this->request->isPost()) {
            $this->token();
            $params = $this->request->post("row/a");
            if ($params) {
                $this->fzyq($params);
                $params = $this->preExcludeFields($params);

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

                    $id = $params['rank_token'];
                    //查找对应的year，quarter，institution
                    $line = Db::table('rank')->where('id',$id)->find();
                    $params['year'] = $line['year'];
                    $params['institution'] = $line['institution'];
                    $params['quarter'] = $line['quarter'];

                    if (!$params['content']){
                        $this->error('扣分项内容不能为空');
                    }

                    $this->checkIsThisQuarter($params,$line['year']);

                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                    // 请求下前台接口，触发计算评分功能
                   $class = new Experiment();
                   $class->getTestingInfo();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error(Session::get('fzyq_error_info')?? Session::get('ph_error') ?? $e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));

        }

        //获取扣分项列表
        $list = Db::table('score_list')->where('deletetime',null)->select();
        $this->assign('list',$list);
        $this->assignconfig('list',$list);

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

                    $this->checkIsThisQuarter($params,$params['year']);

                    if (!$params['content']){
                        $this->error('扣分项内容不能为空');
                    }
                    
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                    // 请求下前台接口，触发计算评分功能
                    $class = new Experiment();
                    $class->getTestingInfo();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error(Session::get('fzyq_error_info')?? Session::get('ph_error') ?? $e->getMessage());
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

        //获取扣分项列表
        $list = Db::table('score_list')->where('deletetime',null)->select();
        $this->assign('list',$list);
        $this->assignconfig('list',$list);

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
                    $info = Db::table('rank_breakdown')->where('id',$ids)->find();
                    $this->checkIsThisQuarter($info,$info['year']);
                    $count += $v->delete();
                }
                Db::commit();
                // 请求下前台接口，触发计算评分功能
                $class = new Experiment();
                $class->getTestingInfo();
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error(Session::get('fzyq_error_info')?? Session::get('ph_error') ?? $e->getMessage());
            }
            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    public function checkIsThisQuarter($params,$year_old)
    {
        if (!empty($params['year']) && !empty($params['quarter'])){
            $quarter_reflection = array(
                '第一季度'=>'1',
                '第二季度'=>'2',
                '第三季度'=>'3',
                '第四季度'=>'4',
            );
            $quarter = ceil(date('m') / 3);
            $year = date('Y');
            if (isset($quarter_reflection[$params['quarter']])){
                if ($quarter_reflection[$params['quarter']] != $quarter || $year_old != $year  ){
                    $error_info = '仅可以对当季度排行榜分值进行修改~';
                    Session::set('ph_error',$error_info);
                    $this->error($error_info);
                }
            }
        }
    }
}
