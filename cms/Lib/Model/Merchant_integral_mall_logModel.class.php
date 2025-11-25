<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * Date: 2019/5/16
 * Time: 11:48
 */
class Merchant_integral_mall_logModel extends Model
{
    public function add_log($param) {
        if (empty($param['order_id'])) {
            return false;
        }
        if ($order = D('Merchant_integral_mall_goods_order')->field(true)->where(array('order_id' => $param['order_id']))->find()) {
            // '状态： 0 下单 1 用户支付 2 商家发货 3 用户收货 4 用户退款 5 用户自提
            $data = array('date_line' => time());
            $data['order_id'] = intval($param['order_id']);
            $data['status'] = isset($param['status']) ? intval($param['status']) : 0;
            $data['phone'] = isset($param['phone']) ? $param['phone'] : '';
            $data['name'] = isset($param['name']) ? $param['name'] : '';
            $data['from_uid'] = isset($param['from_uid']) ? $param['from_uid'] : '';
            $data['note'] = isset($param['note']) ? $param['note'] : '';
            $data['from_type'] = isset($param['from_type']) ? intval($param['from_type']) : 0;
            return $this->add($data);
        }
    }
}