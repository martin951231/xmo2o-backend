<?php
namespace app\marriage_helper\model\db;

use think\Model;

class JobPerson extends Model
{
    /**
     * 高手列表
     * @return \json
     */
    public function getPersonList($where, $order, $page, $pageSize)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('a')
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

	public function getPersonDetail($where, $field, $page = 1, $limit = 10){
		$prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('a')
            ->join($prefix . 'merchant_position' . ' b', 'b.id = a.job_id')
            ->join($prefix . 'merchant_store' . ' c', 'c.store_id = a.store_id')
            ->field($field)
            ->where($where)
            ->order('a.add_time desc')
            ->page($page, $limit)
            ->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 高手列表总数
     * @param $where
     * @return mixed
     */
    public function getPersonCount($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $count = $this->alias('a')
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
     * 高手详情
     * @return \json
     */
    public function getPersonView($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('a')
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
     * 高手拉黑
     * @return \json
     */
    public function getPersonDel($where)
    {
        $result = $this->where($where)->update(['is_black'=>1]);
        return $result;
    }
}