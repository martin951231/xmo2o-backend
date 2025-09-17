<?php
/**
 * @author : liukezhu
 * @date : 2022/3/24
 */
namespace app\community\model\db;

use think\Model;

class HouseAdminGroupLabelRelation extends Model{


    public function addAll($data)
    {
        $res = $this->insertAll($data);
        return $res;
    }

    public function delOne($where) {
        $del = $this->where($where)->delete();
        return $del;
    }


    public function getList($where,$order='id desc',$field=true) {
        $list = $this->where($where)->field($field)->order($order)->select();
        return $list;
    }

    public function getGroupLabel($where,$order = 'id desc', $field = true,  $page = 0, $limit = 10)
    {
        $sql = $this->alias('g')
            ->leftJoin('house_village_label_cat c','c.cat_id = g.cat_id')
            ->leftJoin('house_village_label b','b.id = g.label_id')
            ->where($where)->field($field)->order($order);
        if ($page) {
            $list = $sql->page($page, $limit)->select();
        } else {
            $list = $sql->select();
        }
        return $list;
    }



}