<?php
/**
 * 餐饮订单支付表model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/06/08 18:07
 */

namespace app\foodshop\model\db;
use think\Model;
class DiningOrderPay extends Model {

    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 根据条件返回订单列表
     * @param $where array 条件
     * @param $order array 排序
     * @return array|bool|Model|null
     */
    public function getOrderListByCondition($where,$order=['sort_id']) {
        if(!$where){
            return null;
        }

        $result = $this->where($where)->order($order)->select();
        return $result;
    }

    /**
     * 根据订单id条件返回订单
     * @param $orderId int 条件
     * @return array|bool|Model|null
     */
    public function getOrderByOrderId($orderId) {
        if(!$orderId){
            return null;
        }

        $where = [
            'pay_order_id' => $orderId
        ];

        $result = $this->where($where)->find();
        return $result;
    }

    /**
     * 根据第三方流水号返回订单
     * @param $thirdId string 流水号
     * @param $field string 查询字段
     * @return array|bool|Model|null
     */
    public function getOrderListByThirdId($thirdId, $field = true) {
        if(!$thirdId){ 
            return null;
        }

        $where = [
            ['p.paid_extra' ,'like', '%'.$thirdId.'%']
        ];

        $prefix = config('database.connections.mysql.prefix');

        $result = $this ->alias('op')
            ->where($where)
            ->field($field)
            ->leftJoin($prefix.'pay_order_info p','p.orderid=op.third_id')
            ->select();
        return $result;
    }

    /**
     * 根据条件返回订单
     * @param $where int 条件
     * @return array|bool|Model|null
     */
    public function getOne($where) {
        if(!$where){
            return null;
        }

        $result = $this->where($where)->find();
        return $result;
    }
}