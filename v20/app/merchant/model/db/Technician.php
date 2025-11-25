<?php
namespace app\merchant\model\db;

use think\Model;
use app\merchant\model\db\JobPerson;

class Technician extends Model
{
    /**
     * 店铺技师认证列表
     * @return \json
     */
    public function getTechnicianList($where, $order, $page, $pageSize)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = (new JobPerson())->alias('a')
            ->join($prefix . 'merchant_position' . ' b', 'b.id = a.job_id')
            ->join($prefix . 'user' . ' c', 'c.uid = a.uid')
            ->join($prefix . 'merchant' . ' d', 'd.mer_id = a.mer_id')
            ->join($prefix . 'merchant_store' . ' e', 'e.store_id = a.store_id')
            ->field('a.*, b.name as pos_name, c.phone, d.name as mer_name, e.name as store_name')
            ->where($where)
            ->order($order)
            ->page($page, $pageSize)
            ->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 店铺技师认证列表总数
     * @param $where
     * @return mixed
     */
    public function getTechnicianCount($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $count = (new JobPerson())->alias('a')
            ->join($prefix . 'merchant_position' . ' b', 'b.id = a.job_id')
            ->join($prefix . 'user' . ' c', 'c.uid = a.uid')
            ->join($prefix . 'merchant' . ' d', 'd.mer_id = a.mer_id')
            ->join($prefix . 'merchant_store' . ' e', 'e.store_id = a.store_id')
            ->field('a.id')
            ->where($where)
            ->count();
        return $count;
    }

    /**
     * 店铺技师认证详情
     * @return \json
     */
    public function getTechnicianView($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = (new JobPerson())->alias('a')
            ->join($prefix . 'merchant_position' . ' b', 'b.id = a.job_id')
            ->join($prefix . 'user' . ' c', 'c.uid = a.uid')
            ->join($prefix . 'merchant_store' . ' e', 'e.store_id = a.store_id')
            ->field('a.*, b.name as pos_name, c.province_id, c.city_id, e.name as store_name')
            ->where($where)
            ->find();
        if(!empty($arr)){
            return $arr->toArray();
        }else{
            return [];
        }
    }

    /**
     * 店铺技师认证审核
     * @return \json
     */
    public function getTechnicianExamine($where, $data)
    {
        $result = (new JobPerson())->where($where)->update($data);
        return $result;
    }

    /**
     * 店铺技师认证拉黑
     * @return \json
     */
    public function getTechnicianDel($where, $data)
    {
        $result = (new JobPerson())->where($where)->update($data);
        return $result;
    }
}