<?php
/**
 * @author : liukezhu
 * @date : 2022/3/24
 */
namespace app\community\model\db;

use think\Model;

class HouseProgrammeRelation extends Model{

    public function addAll($data)
    {
        $res = $this->insertAll($data);
        return $res;
    }

    public function delOne($where) {
        $del = $this->where($where)->delete();
        return $del;
    }


    public function getRelationWorkColumn($where,$field)
    {
        $list = $this->alias('r')
            ->leftJoin('house_worker w','w.wid = r.wid')
            ->where($where)->order('r.wid desc')->column($field);
        return $list;
    }

    public function getColumn($where,$field)
    {
        $column = $this->where($where)->column($field);
        return $column;
    }


    public function getProgrammeRelationWid($where,$field)
    {
        $list = $this->alias('r')
            ->leftJoin('house_programme p','p.id = r.pid')
            ->where($where)->order('r.id desc')->column($field);
        return $list;
    }


}