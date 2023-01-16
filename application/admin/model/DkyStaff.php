<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class DkyStaff extends Model
{

    use SoftDelete;

    

    // 表名
    protected $table = 'dky_staff';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'sex_text',
        'skill_rank_text',
        'type_text',
        'property_text',
        'status_text'
    ];
    

    
    public function getSexList()
    {
        return ['男' => __('男'), '女' => __('女')];
    }

    public function getSkillRankList()
    {
        return ['Level-1' => __('Level-1'), 'Level-2' => __('Level-2'), 'Level-3' => __('Level-3'), 'Level-4' => __('Level-4'), 'Level-5' => __('Level-5'), 'Level-6' => __('Level-6'), 'Level-7' => __('Level-7')];
    }

    public function getTypeList()
    {
        return ['A级检测人员' => __('A级检测人员'), 'B级检测人员' => __('B级检测人员')];
    }

    public function getPropertyList()
    {
        return ['设备' => __('设备'), '材料' => __('材料')];
    }

    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1'), '2' => __('Status 2')];
    }


    public function getSexTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['sex']) ? $data['sex'] : '');
        $list = $this->getSexList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getSkillRankTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['skill_rank']) ? $data['skill_rank'] : '');
        $list = $this->getSkillRankList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getPropertyTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['property']) ? $data['property'] : '');
        $list = $this->getPropertyList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}
