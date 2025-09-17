<?php
/**
 * @Author: 朱梦群
 * @Date:   2020-09-04 11:17:43
 * @Desc:   商城3.0分类管理db
 */

namespace app\mall\model\db;

use think\model;

class MallGoodsSort extends Model
{
    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'mer_id', 'mer_id');
    }
    
    public function merchantStore()
    {
        return $this->belongsTo(MerchantStore::class, 'store_id', 'store_id');
    }
    /**
     * @param: $where array
     * @param  $order array
     * @return :  array
     * @Desc:   获取分类列表(分组排序 分页)
     */
    public function getCategoryByCondition($where, $order)
    {
        $goodsSort = $this->where($where)->field(true)->order($order)->select()->toArray();
        if (!$goodsSort) {
            return [];
        }
        return $goodsSort;
    }
    /**
     * @param: $where array
     * @param  $order array
     * @return :  array
     * @Desc:   获取分类列表
     */
    public function getCategoryByCondition2($where,$order,$param)
    {
        if(!empty($param)){
            $goodsSort = $this->where($where)->order($order)->page($param['page'],$param['pageSize'])->select()->toArray();
        }else{
            $goodsSort = $this->where($where)->order($order)->select()->toArray();
        }
        if (!$goodsSort) {
            return [];
        }
        return $goodsSort;
    }
    /**
     * @param: string $where
     * @return :  int
     * @Desc:   获取分类总数
     */
    public function getSortCount($where)
    {
        $count = $goodsSort = $this->where($where)->count();
        return $count;
    }

    /**
     * @param: $arr array
     * @param  $where array
     * @return :  int
     * @Desc:   编辑分类
     */
    public function editSort($where, $arr)
    {
        $res = $this->where($where)->update($arr);
        return $res;
    }

    /**
     * @param: $arr array
     * @return :  int
     * @Desc:   新增分类
     */
    public function addSort($arr)
    {
        $res = $this->insertGetId($arr);
        return $res;
    }

    /**
     * @param: $arr array
     * @return :  int
     * @Desc:   删除分类
     */
    public function delSort($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }

    /**
     * @param: $arr array()
     * @return :  array
     * @Desc:   获取被编辑的分类
     */
    public function getEdit($where)
    {
        $arr = $this->where($where)->find();
        if(!empty($arr)){
            return $arr->toArray();
        }else{
            return [];
        }
    }

    /**
     * 根据条件获取分类
     * @param $field
     * @param $order
     * @param $where
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function getSortByCondition($field,$order,$where,$page,$pageSize){
        $arr = $this->field($field)->where($where)->order($order)->page($page,$pageSize)->select();
        if(!empty($arr)){
            return $arr->toArray();
        }else{
            return [];
        }
    }

    /**
     * 根据条件获取分类
     * @param $field
     * @param $order
     * @param $where
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function getSortByCondition1($field,$order,$where){
        $arr = $this->field($field)
            ->where($where)
            ->with(['merchant' => function($query){
                $query->field('mer_id, name');
            }, 'merchant_store' => function($query){
                $query->field('store_id, name');
            }])
            ->order($order)
            ->select()
            ->each(function ($item){
                $item->mer_name = $item->merchant['name'] ?? '';
                $item->store_name = $item->merchant_store['name'] ?? '';
                unset($item->merchant, $item->merchant_store);
            });
        if(!empty($arr)){
            return $arr->toArray();
        }else{
            return [];
        }
    }
}