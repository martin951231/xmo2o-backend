<?php

namespace app\classify\model\db;

use app\common\model\db\ClassifyCategory;
use think\Model;

class ClassifyMenu extends Model
{
    protected $globalScope = ['delete_time'];

    public function scopeDeleteTime($query)
    {
        $query->where('delete_time',0);
    }
    
    public function setClassifyIdsAttr($value){
        return implode(',', $value);
    }

    public function getClassifyIdsAttr($value){
        return $value ? explode(',', $value) : [];
    }
    
    public function classifyDetailList()
    {
        return $this->hasMany(ClassifyMenuDetail::class, 'menu_id');
    }
    
    public function searchClassifyIdAttr($query, $value)
    {
        $value && $query->where("FIND_IN_SET({$value}, classify_ids)");
    }
    
    public function getClassifyAttr($value, $data)
    {
        return ClassifyCategory::where('cid','IN', $data['classify_ids'])
            ->where(['subdir' => ClassifyCategory::CATE_LEVEL_2])
            ->field("cid, fcid, CONCAT_WS('-',fcid,cid) cate_id, cat_name, cat_url, cat_status")
            ->select();
    }
}