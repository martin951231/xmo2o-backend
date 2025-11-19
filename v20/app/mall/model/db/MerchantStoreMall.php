<?php
/**
 * MerchantStoreMall.php
 * 店铺扩展表
 * Create on 2020/9/22 17:10
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use app\common\model\db\db_trait\CommonFunc;
use think\Model;

class MerchantStoreMall extends Model
{
    use CommonFunc;

    /**
     * @param $where
     * @param $param
     * @return bool
     */
    public function updateStoreConfig($where, $param)
    {
        $result = $this->where($where)->save($param);
        return $result;
    }

    public function addStoreConfig($param)
    {
        $result = $this->insert($param);
        return $result;
    }

    /**
     * 获取配置列表
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getStoreConfigList($where, $field)
    {
        $arr = $this->field($field)->where($where)->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }


    /**
     * @param $where
     * @param $field
     * @return mixed
     *获得店铺ids
     */
    public function getStoreIDList($where, $field)
    {
//        $arr = $this->field($field)->where($where)->column($field);
        $arr = $this->alias('a')
            ->join('merchant_store b','a.store_id = b.store_id')
            ->where($where)
            ->column($field);
        return $arr;
    }

    public function getIds()
    {
        return $this->field('store_id')->select()->toArray();
    }

    public function getFullInfo($where)
    {
        $data = $this->alias('m')
            ->leftJoin('merchant_store s', 's.store_id=m.store_id')
            ->where($where)
            ->find();
        return $data;
    }
}