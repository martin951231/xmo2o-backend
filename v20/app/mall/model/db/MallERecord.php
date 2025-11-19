<?php
/**
 * MallERecord.php
 * 电子发票model
 * Create on 2020/10/13 14:35
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use think\Model;

class MallERecord extends Model
{
    /**
     * 获取所有开发票记录
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getAllERecord($where, $page = 1, $pageSize = 10)
    {
        $data = $this->alias('r')->where($where)->field(true)->order('id DESC')->page($page, $pageSize)->select();
        if (!empty($data)) {
            return $data->toArray();
        } else {
            return [];
        }
    }

    /**
     * 联合店铺名称查询
     * @param $where
     * @return array
     */
    public function getSearch1($where, $page = 1, $pageSize = 10)
    {
        $perfix = cfg('DB_PREFIX');
        $data = $this->alias('r')
            ->field('r.*,m.name as store_name')
            ->join($perfix . 'merchant_store m ', 'm.store_id=r.store_id')
            ->where($where)
            ->order('id desc')
            ->page($page, $pageSize)
            ->select();
        if (!empty($data)) {
            return $data->toArray();
        } else {
            return [];
        }
    }

    /**
     * 联合订单查询
     * @param $where
     * @return array
     */
    public function getSearch2($where, $page = 1, $pageSize = 10)
    {
        $perfix = cfg('DB_PREFIX');
        $data = $this->alias('r')
            ->field('r.*')
            ->join($perfix . 'mall_order o', 'o.order_id=r.order_id')
            ->where($where)
            ->order('id desc')
            ->page($page, $pageSize)
            ->select();
        if (!empty($data)) {
            return $data->toArray();
        } else {
            return [];
        }
    }

    /**
     * 添加开发票记录
     * @param $post_params
     * @return mixed
     */
    public function addErecord($post_params)
    {
        $res = $this->data($post_params)->save();
        return $res;
    }

    /**
     * 展示发票信息
     * @param $where
     * @param $field
     * @return array
     */
    public function showInvoice($where, $field)
    {
        $arr = $this->field($field)->where($where)->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * @param $data
     * @param $where
     * @return bool
     * 根据条件跟新
     */
    public function updateOne($data, $where)
    {
        return $this->where($where)->data($data)->save();
    }
}