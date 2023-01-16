<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class MaintenanceLog extends Model
{

    use SoftDelete;

    

    // 表名
    protected $table = 'maintenance_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'type_text'
    ];
    

    
    public function getTypeList()
    {
        return ['检测设备' => __('检测设备'), '工位' => __('工位'), 'AGV设备' => __('Agv设备'), '仓储设备' => __('仓储设备')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}
