<?php
/**
 * 抄表相关
 * @author weili
 * @date 2020/10/19
 */

namespace app\community\model\db;

use think\Model;
class HouseVillageMeterReading extends Model
{
    /**
     * 抄表记录
     * @author lijie
     * @date_time 2021/07/10
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where=[],$field=true,$page=1,$limit=15,$order='m.id DESC')
    {
        if($page)
            $data = $this->alias('m')->leftJoin('admin a','a.id = m.role_id')->leftJoin('house_new_charge_project p','p.id = m.project_id')->where($where)->field($field)->page($page,$limit)->order($order)->select();
        else
            $data = $this->alias('m')->leftJoin('admin a','a.id = m.role_id')->leftJoin('house_new_charge_project p','p.id = m.project_id')->where($where)->field($field)->order($order)->select();
        return $data;
    }

    public function getLists($where=[],$field=true,$page=1,$limit=15,$order='id DESC')
    {
        $data = $this->where($where)->field($field)->page($page,$limit)->order($order)->select();
        return $data;
    }

    /**
     * Notes: 查询数量
     * @param $where
     * @param string $group
     * @return int
     * @author: weili
     * @datetime: 2020/10/20 17:31
     */
    public function getCount($where,$group='')
    {
        $sql = $this->where($where);
        if($group)
        {
            $count = $sql->group($group)->count();
        }else{
            $count = $sql->count();
        }
        return $count;
    }

    public function getOne($where=[],$field=true,$order='id DESC')
    {
        $data = $this->where($where)->field($field)->order($order)->find();
        return $data;
    }

    public function getMeterCount($where=[])
    {
        $count = $this->alias('m')->where($where)->count();
        return $count;
    }

    /**
     * 添加抄表记录
     * @author lijie
     * @date_time 2021/07/10
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $id = $this->insertGetId($data);
        return $id;
    }

    public function updateOneData($whereArr,$updateData=array())
    {
        if(empty($whereArr) || empty($updateData)){
            return false;
        }
        $ret=$this->where($whereArr)->update($updateData);
        return $ret;
    }

    public function getSum($where,$field)
    {
        $num = $this->alias('m')
            ->leftJoin('admin a','a.id = m.role_id')
            ->leftJoin('house_new_charge_project p','p.id = m.project_id')
            ->where($where)->sum($field);
        return $num;
    }

    public function getDegreesSum($where,$field)
    {
        $num = $this->alias('m')
            ->leftJoin('admin a','a.id = m.role_id')
            ->leftJoin('house_new_charge_project p','p.id = m.project_id')
            ->where($where)->field($field)->find();
        return $num;
    }

}