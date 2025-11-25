<?php

declare(strict_types=1);

namespace app\api\controller;

use app\api\model\service\building\ParkService;
use app\building\service\ParkOrderService;

//停车管理
class ParkController extends BaseController
{
    //获取车场列表
    public function lists()
    {
        $params = [
            'mall_id' => $this->request->param('mall_id', 0, 'intval'),
            'park_type' => $this->request->param('park_type', 0, 'intval'),
            'page' => $this->request->param('page', 1, 'intval'),
            'page_size' => $this->request->param('page_size', 20, 'intval'),
        ];
        try {
            $result = (new ParkService())->lists($params);
            return $this->apiOutput(1000, ['result' => $result], '');
        } catch (\Exception $e) {
            return $this->apiOutput(1001, [], $e->getMessage());
        }
    }

    //获取停车场信息
    public function getInfo()
    {
        $params = [
            'merchant_id' => $this->request->param('merchant_id', 0, 'intval'),
            'api_key' => $this->request->param('api_key', '', 'trim'),
        ];
        try {
            $result = (new ParkService())->getInfo($params['merchant_id'], $params['api_key']);
            return $this->apiOutput(1000, ['result' => $result], '');
        } catch (\Exception $e) {
            return $this->apiOutput(1001, [], $e->getMessage());
        }
    }


    //添加、编辑停车场
    public function save()
    {
        $params = [
            'id' => $this->request->param('id', 0, 'intval'),
            'mall_id' => $this->request->param('mall_id', 0, 'intval'),
            'park_type' => $this->request->param('park_type', 0, 'intval'),
            'merchant_id' => $this->request->param('merchant_id', 0, 'intval'),
            'api_key' => $this->request->param('api_key', '', 'trim'),
            'park_name' => $this->request->param('park_name', '', 'trim'),
            'park_id' => $this->request->param('park_id', 0, 'intval'),
        ];
        try {
            $id = (new ParkService())->savePark($params);
            return $this->apiOutput(1000, ['id' => $id], '');
        } catch (\Exception $e) {
            return $this->apiOutput(1001, [], $e->getMessage());
        }
    }

    //删除停车场
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        try {
            $result = (new ParkService())->deleteParkById($id);
            return $this->apiOutput(1000, ['result' => $result], '');
        } catch (\Exception $e) {
            return $this->apiOutput(1001, [], $e->getMessage());
        }
    }

    //停车场优惠券
    public function coupon()
    {
        $id = $this->request->param('id', 0, 'intval');
        try {
            $couponLists = (new ParkService())->coupon($id);
            return $this->apiOutput(1000, $couponLists, '');
        } catch (\Exception $e) {
            return $this->apiOutput(1001, [], $e->getMessage());
        }
    }

    //积分兑换停车券设置
    public function couponSetting()
    {
        $id = $this->request->param('id', 0, 'intval');
        $scoreCouponSetting = $this->request->param('score_coupon_setting', []);
        try {
            $result = (new ParkService())->couponSetting($id, $scoreCouponSetting);
            return $this->apiOutput(1000, ['result' => $result], '');
        } catch (\Exception $e) {
            return $this->apiOutput(1001, [], $e->getMessage());
        }
    }

    //停车订单列表
    public function orderLists()
    {
        $params = [
            'mall_id' => $this->request->param('mall_id', 0, 'intval'),
            'park_id' => $this->request->param('park_id', 0, 'intval'),
            'car_number' => $this->request->param('car_number', '', 'trim'),
            'order_no' => $this->request->param('order_no', '', 'trim'),
            'status' => $this->request->param('status', -1, 'intval'),
            'pay_start_time' => $this->request->param('pay_start_time', '', 'trim'),
            'pay_end_time' => $this->request->param('pay_end_time', '', 'trim'),
            'paid_extra' => $this->request->param('paid_extra', '', 'trim'),
            'pay_type' => $this->request->param('pay_type', '', 'trim'),
            'page' => $this->request->param('page', 1, 'intval'),
            'page_size' => $this->request->param('page_size', 20, 'intval'),
        ];
        try {
            $result = (new ParkService())->orderLists($params);
            return $this->apiOutput(1000, ['result' => $result], '');
        } catch (\Exception $e) {
            return $this->apiOutput(1001, [], $e->getMessage());
        }
    }

    //订单退款
    public function orderRefund()
    {
        $params = [
            'order_id' => $this->request->param('order_id', 0, 'intval'),
            'amount' => $this->request->param('amount', 0, 'floatval'),
            'reason' => $this->request->param('reason', '', 'trim'),
        ];
        try {
            $result = (new ParkOrderService())->orderRefund($params);
            return $this->apiOutput(1000, ['result' => $result], '');
        } catch (\Exception $e) {
            return $this->apiOutput(1001, [], $e->getMessage());
        }
    }
}
