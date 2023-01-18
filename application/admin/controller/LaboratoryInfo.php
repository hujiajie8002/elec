<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 实验室基本情况
 *
 * @icon fa fa-circle-o
 */
class LaboratoryInfo extends Backend
{
    
    /**
     * LaboratoryInfo模型对象
     * @var \app\admin\model\LaboratoryInfo
     */
    protected $model = null;

    protected $noNeedLogin = ['getCity'];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\LaboratoryInfo;
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

    public function getCity()
    {
        $str = '[{"id":1,"name":"张三","sex":"男"},{"id":2,"name":"李四","sex":"男"}]';
        $a = json_decode($str,true);

        return json_encode($a,320);
        return '[{"id":1,"name":"省中心（电科院）","pid":0},{"id":3,"name":"苏中分中心","pid":0},{"id":4,"name":"苏北分中心","pid":0},{"id":2,"name":"苏南分中心","pid":0}]';
    }
}
