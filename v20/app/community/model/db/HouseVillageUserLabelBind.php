<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2021/10/26
 * Time: 15:07
 *======================================================
 */

namespace app\community\model\db;


use think\Model;

class HouseVillageUserLabelBind extends Model
{
    /**
     * 获取用户标签列表
     * @param array $where
     * @param bool $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserLabelList($where = [],$field = true,$oderby='hvl.id desc'){
        $data = $this->alias('ulb')
            ->leftJoin('house_village_label hvl','hvl.id = ulb.village_label_id')
            ->where($where)
            ->field($field)->order($oderby)
            ->select();
        if(!empty($data)){
            $data = $data->toArray();
        }
        return $data;
    }

    /**
     * 获取用户某一标签
     * @param array $where
     * @param bool $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserLabelOne($where = [],$field = true,$order = 'ulb.id DESC'){
        $data = $this->alias('ulb')
            ->leftJoin('house_village_label hvl','hvl.id = ulb.village_label_id')
            ->where($where)
            ->field($field)
            ->order($order)
            ->find();
        if(!empty($data)){
            $data = $data->toArray();
        }
        return $data;
    }

    /**
     * 修改小区用户绑定标签表
     * @param $bind_id
     * @param $data
     * @return bool
     */
    public function updateUserLabelBind($bind_id,$data){
        return $this->where(array(['bind_id','=',$bind_id]))->data($data)->save();
    }

    /**
     * 保存数据
     * @param $data
     * @return int|string
     */
    public function addUserLabelBind($data){
        return $this->insertAll($data);
    }

    public function getColumn($where,$column=true)
    {
        $column = $this->where($where)->column($column);
        return $column;
    }

    public function delOne($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }

    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count>0 ? $count:0;
    }

}