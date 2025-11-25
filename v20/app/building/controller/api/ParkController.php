<?php

namespace app\building\controller\api;

use app\building\service\ParkOrderService;
use app\building\service\ParkService;
use app\mall\controller\api\ApiBaseController;

//停车管理
class ParkController extends ApiBaseController
{
    //停车配置
    public function config()
    {
        $data = [
            'province_list' => array_values(province_short_list()),
            'park_notice' => '每自然年最多可绑定5个车牌号可享受会员每日免停权 益及其他停车优惠；超过限制，可正常缴纳停车费， 但不再享受停车权益及优惠。',
            'limit_car_num' => ParkService::LIMIT_CAR_NUM,
            'default_province' => '皖',
        ];
        return api_output(0, $data, 'success');
    }

    //添加车辆
    public function addCar()
    {
        if (empty($this->request->log_uid)) {
            return api_output_error(1002, '当前接口需要登录');
        }

        $params['car_number'] = $this->request->param('car_number', '', 'trim');
        try {
            (new ParkService)->addCar($this->request->log_uid, $params);
            return api_output(0, [], '添加成功');
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    //编辑车辆
    public function editCar()
    {
        if (empty($this->request->log_uid)) {
            return api_output_error(1002, '当前接口需要登录');
        }
        $params = [
            'id' => $this->request->param('id', 0, 'intval'),
            'car_number' => $this->request->param('car_number', '', 'trim'),
            'is_bind' => $this->request->param('is_bind', 0, 'intval'),
        ];
        try {
            (new ParkService)->editCar($this->request->log_uid, $params);
            return api_output(0, [], '编辑成功');
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    //删除车辆
    public function delCar()
    {
        if (empty($this->request->log_uid)) {
            return api_output_error(1002, '当前接口需要登录');
        }

        $carNumber = $this->request->param('car_number', '', 'trim');
        try {
            (new ParkService)->delCarByCarNumber($this->request->log_uid, $carNumber);
            return api_output(0, [], '删除成功');
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    //我的车辆
    public function myCars()
    {
        if (empty($this->request->log_uid)) {
            return api_output_error(1002, '当前接口需要登录');
        }

        try {
            $lists = (new ParkService)->getMyCars($this->request->log_uid);
            $leftNum = ParkService::LIMIT_CAR_NUM - count($lists);
            $userLevelInfo = (new \app\common\model\service\UserService())->getLevelByUid($this->request->log_uid);
            if ($userLevelInfo) {
                $userLevelInfo['tips'] = '已绑定会员停车权益';
            }
            return api_output(0, ['list' => $lists, 'left_car_num' => $leftNum > 0 ? $leftNum : 0, 'user_level_info' => $userLevelInfo], 'success');
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    //停车缴费接口
    public function index()
    {
        if (empty($this->request->log_uid)) {
            return api_output_error(1002, '当前接口需要登录');
        }

        $uid = $this->request->log_uid;
        $mallId = $this->request->param('mall_id', 0, 'intval');
        $rs = [];
        //登录用户信息
        $rs['user_info'] = [
            'uid' => $this->userInfo['uid'],
            'nickname' => $this->userInfo['nickname'],
            'avatar' => $this->userInfo['avatar'],
            'phone' => phone_show($this->userInfo['phone']),
            'score_count' => $this->userInfo['score_count'],
            'now_money' => $this->userInfo['now_money'],
            'level' => $this->userInfo['level'],
            'level_time' => $this->userInfo['level_time'],
        ];

        //获取商场停车场
        $rs['park_list'] = (new ParkService)->getParkByMallId($mallId, 'id AS park_id,park_name');
        if (empty($rs['park_list'])) {
            return api_output_error(1001, '当前商场暂无停车场');
        }

        $parkId = $this->request->param('park_id', 0, 'intval');
        if (empty($parkId)) {
            $parkId = $rs['park_list'][0]['park_id'];
        }
        //获取停车场信息
        $rs['park_info'] = (new ParkService)->getParkInfo($parkId);

        //获取对应停车场支持的停车券兑换
        $rs['coupon'] = [
            'title' => '停车券兑换',
            'list' => (new ParkService)->getCouponList($parkId)
        ];

        //获取会员权益
        $rs['benefits'] = [
            'title' => '会员权益',
            'list' => (new ParkService)->getBenefitsList($uid, $mallId)
        ];
        return api_output(0, $rs, 'success');
    }

    //兑换停车券
    public function exchangeCoupon()
    {
        if (empty($this->request->log_uid)) {
            return api_output_error(1002, '当前接口需要登录');
        }
        $params = [
            'uid' => $this->request->log_uid,
            'park_id' => $this->request->param('park_id', 0, 'intval'),
            'coupon_id' => $this->request->param('coupon_id', 0, 'intval'),
            'car_number' => $this->request->param('car_number', '', 'trim'),
        ];
        try {
            $info = (new ParkService)->exchangeCoupon($params);
            return api_output(0, $info, '兑换成功');
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    //查询停车缴费信息
    public function query()
    {
        if (empty($this->request->log_uid)) {
            return api_output_error(1002, '当前接口需要登录');
        }
        $parkId = $this->request->param('park_id', 0, 'intval');
        $carNumber = $this->request->param('car_number', '', 'trim');
        $uid = $this->request->log_uid;
        try {
            $result = (new ParkService)->getPayInfo($parkId, $carNumber, $uid);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    //领取会员权益停车优惠券
    public function getBenefitCoupon()
    {
        if (empty($this->request->log_uid)) {
            return api_output_error(1002, '当前接口需要登录');
        }
        $params = [
            'uid' => $this->request->log_uid,
            'park_id' => $this->request->param('park_id', 0, 'intval'),
            'coupon_id' => $this->request->param('coupon_id', 0, 'intval'),
            'car_number' => $this->request->param('car_number', '', 'trim'),
        ];
        try {
            $result = (new ParkService)->getBenefitCoupon($params);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    //保存订单
    public function saveOrder()
    {
        if (empty($this->request->log_uid)) {
            return api_output_error(1002, '当前接口需要登录');
        }

        $params['uid'] = $this->request->log_uid;
        $params['mall_id'] = $this->request->param('mall_id', 0, 'intval');
        $params['park_id'] = $this->request->param('park_id', 0, 'intval');
        $params['car_number'] = $this->request->param('car_number', '', 'trim');
        try {
            $result = (new ParkOrderService())->createOrder($params);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    //停车订单列表
    public function orders()
    {
        if (empty($this->request->log_uid)) {
            return api_output_error(1002, '当前接口需要登录');
        }
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('page_size', 10, 'intval');
        try {
            $result = (new ParkOrderService())->getOrderList($this->request->log_uid, $page, $pageSize);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    //停车订单详情
    public function orderDetail()
    {
        if (empty($this->request->log_uid)) {
            return api_output_error(1002, '当前接口需要登录');
        }
        $orderId = $this->request->param('order_id', 0, 'intval');
        try {
            $result = (new ParkOrderService())->getOrderDetail($this->request->log_uid, $orderId);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }
}
