<?php
/**
 * MerchantStore.php
 * 店铺model
 * Create on 2020/9/11 11:19
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use think\Model;

class MerchantStore extends Model
{
    protected $pk = 'store_id';

    public function merchantStoreMall()
    {
        return $this->hasOne(MerchantStoreMall::class, 'store_id', 'store_id');
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class, 'mer_id', 'mer_id');
    }

    public function mallDelivery()
    {
        return $this->hasOne(MallDelivery::class, 'store_id', 'store_id');
    }

    public function searchStoreNameAttr($query, $value)
    {
        !empty($value) && $query->where('name','like', "%$value%");
    }

    /**
     * @param $where
     * @param array $order
     * @param bool $field
     * @return mixed
     * 店铺列表
     */
    public function getStoreByArea($where,$order,$field=true){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('s')
            ->field($field)
            ->join($prefix.'area'.' m','s.area_id = m.area_id')
            ->where($where)
            ->order($order)
            ->select()
            ->toArray();
        return $result;
    }

    public function getStoreByCondition($where)
    {
        $arr = $this->field('store_id,name')->where($where)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    public function getStoreList($where, $field, $page, $pageSize)
    {
        $arr = $this->field($field)->where($where)->page($page, $pageSize)->select();
        $count = $this->field($field)->where($where)->count('name');

        if (!empty($arr)) {
            $arr->toArray();
            $arr['count'] = $count;
            return $arr;
        } else {
            return [];
        }
    }

    public function getStoreList1($where, $field)
    {
        $arr = $this->field($field)->where($where)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    public function getOne($where)
    {
        $arr = $this->field(true)->where($where)->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }

    }

    /**
     * @param $where
     * @param $field
     * @param $order
     * @param $page
     * @param $pageSize
     * @return mixed
     * 关联新版后台装修页feed流分类下店铺手动排序表
     */
    public function getListByStoreSort($where,$field,$order, $page, $pageSize){
        $list = $this->alias('a')
            ->leftJoin('diypage_feed_store_sort s','a.store_id = s.store_id')
            ->leftJoin('merchant m','m.mer_id = a.mer_id')
            ->field($field)->where($where);
        $assign['count']=$list->count();
        $assign['list']=$list->order($order)->page($page, $pageSize)->select()->toArray();
        return $assign;
    }

    public function getStoreListByCat($where, $field, $page, $pageSize)
    {
        $arr = $this->field($field)->where($where)->page($page, $pageSize)->select();
        $count = $this->field($field)->where($where)->count('name');

        if (!empty($arr)) {
            $arr1['list']=$arr->toArray();
            $arr1['count'] = $count;
            return $arr1;
        } else {
            return [];
        }
    }
}