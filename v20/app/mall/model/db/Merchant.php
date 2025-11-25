<?php
/**
 * Merchant.php
 * 商家model
 * Create on 2020/9/14 9:26
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use think\Model;

class Merchant extends Model
{
    public function getOne($where, $field)
    {
        $arr = $this->field($field)->where($where)->find();
        if(!empty($arr)){
            $arr=$arr->toArray();
        }else{
            $arr=[];
        }
        return $arr;
    }

    /**
     * 获取商家列表
     * @param $where
     * @param $field
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function getMerList($where, $field, $page, $pageSize)
    {
        $arr = $this->field($field)->where($where)->page($page, $pageSize)->select()->toArray();
        return $arr;
    }

    public function getMerListCount($where, $field)
    {
        $count = $this->field($field)->where($where)->count('name');
        return $count;
    }

    public function getMerList1($where, $field)
    {
        $arr = $this->field($field)->where($where)->select()->toArray();
        return $arr;
    }
}