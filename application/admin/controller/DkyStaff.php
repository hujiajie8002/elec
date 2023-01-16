<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 人员管理
 *
 * @icon fa fa-circle-o
 */
class DkyStaff extends Backend
{
    
    /**
     * DkyStaff模型对象
     * @var \app\admin\model\DkyStaff
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\DkyStaff;
        $this->view->assign("sexList", $this->model->getSexList());
        $this->view->assign("skillRankList", $this->model->getSkillRankList());
        $this->view->assign("typeList", $this->model->getTypeList());
        $this->view->assign("propertyList", $this->model->getPropertyList());
        $this->view->assign("statusList", $this->model->getStatusList());
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





}
