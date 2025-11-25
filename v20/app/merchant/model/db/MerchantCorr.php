<?php
/**
 * MerchantCorr.php
 * 营业信息纠错model
 * Create on 2021/5/8
 * Created by wangchen
 */

namespace app\merchant\model\db;

use think\Model;

class MerchantCorr extends Model
{
    public function getAll($where, $page, $pageSize)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('a')
            ->join($prefix . 'merchant_store' . ' m1', 'm1.store_id = a.store_id')
            ->join($prefix . 'user' . ' m2', 'm2.uid = a.uid')
            ->join($prefix . 'merchant' . ' m3', 'm3.mer_id = a.mer_id')
            ->field('a.id,a.mer_id,a.uid,a.status,a.pic,a.content,a.add_time,m1.name as store_name,m2.nickname as user_name,m2.phone as user_phone,m3.name as mer_name')
            ->where($where)
            ->page($page, $pageSize)
            ->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 按照条件查出来的总数
     * @param $where
     * @return mixed
     */
    public function getCorrCount($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $count = $this->alias('a')
            ->join($prefix . 'merchant_store' . ' m1', 'm1.store_id = a.store_id')
            ->join($prefix . 'user' . ' m2', 'm2.uid = a.uid')
            ->join($prefix . 'merchant' . ' m3', 'm3.mer_id = a.mer_id')
            ->field('a.id,a.mer_id,a.uid,a.status,a.pic,a.content,a.add_time,m1.name as store_name,m2.nickname as user_name,m2.phone as user_phone,m3.name as mer_name')
            ->where($where)
            ->count('id');
        return $count;
    }

    /**
     * 获取反馈内容
     * @param $where
     * @return array
     */
    public function getByCondition($where)
    {
        $arr = $this->field(true)->where($where)->find();
        if(!empty($arr)){
            return $arr->toArray();
        }else{
            return [];
        }
    }

    /**
     * 设置为已处理
     * @param $where
     * @return MallGoodReply
     */
    public function getEditCorr($where, $data)
    {
        $result = $this->where($where)->update($data);
        return $result;
    }
}