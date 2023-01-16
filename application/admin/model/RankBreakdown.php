<?php

namespace app\admin\model;

use think\Model;


class RankBreakdown extends Model
{

    

    

    // 表名
    protected $table = 'rank_breakdown';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'quarter_text',
        'institution_text'
    ];
    

    
    public function getQuarterList()
    {
        return ['第一季度' => __('第一季度'), '第二季度' => __('第二季度'), '第三季度' => __('第三季度'), '第四季度' => __('第四季度')];
    }

    public function getInstitutionList()
    {
        return ['省中心（电科院）' => __('省中心（电科院）'), '苏南分中心' => __('苏南分中心'), '苏中分中心' => __('苏中分中心'), '苏北分中心' => __('苏北分中心')];
    }


    public function getQuarterTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['quarter']) ? $data['quarter'] : '');
        $list = $this->getQuarterList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getInstitutionTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['institution']) ? $data['institution'] : '');
        $list = $this->getInstitutionList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}
