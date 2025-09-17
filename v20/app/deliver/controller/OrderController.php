<?php

namespace app\deliver\controller;


use app\deliver\model\service\DeliverSupplyService;
use app\deliver\model\service\DeliverUserService;

/**
 * 订单控制器
 * @author: 张涛
 * @date: 2020/9/8
 * @package app\deliver\controller
 */
class OrderController extends ApiBaseController
{

    public function initialize()
    {
//        parent::initialize();
    }

    /**
     * 订单状态数量统计
     * @author: 张涛
     * @date: 2020/09/16
     */
    public function count()
    {
        $this->checkLogin();
        try {
            $data = [];
            $uid = $this->request->log_uid;
            $serviceMod = new DeliverUserService();
            $data['new_order_count'] = $serviceMod->getNewOrderCount($uid);
            $data['pick_count'] = $serviceMod->getToPickOrderCount($uid);
            $data['finish_count'] = $serviceMod->getToFinishOrderCount($uid);
            return api_output(0, $data);
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 任务订单
     * @author: 张涛
     * @date: 2020/09/09
     */
    public function lists()
    {
        ini_set('memory_limit', '1024M');
        $this->checkLogin();
        $status = $this->request->param('status', '', 'trim');
        $supplyId = $this->request->param('supply_id', 0, 'intval');
        $orderType = $this->request->param('order_type', '', 'trim');
        $uid = $this->request->log_uid;
        if ($status && !in_array($status, ['new', 'pick', 'finish'])) {
            return api_output(1001, [], L_('参数有误'));
        }
        //筛选订单
        try {
            $orders = [];
            if ($supplyId > 0) {
                $orders = (new DeliverUserService())->getOrderBySupplyId($supplyId);
            } else {
                $orders = (new DeliverUserService())->getOrderLists($status, $uid, $orderType);
            }

            if (isset($orders['max_num'])) {
                return api_output($orders['code'], ['max_num' => $orders['max_num']], $orders['message']);
            }
            return api_output(0, $orders);
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 更改配送状态
     * @author: 张涛
     * @date: 2020/9/9
     */
    public function updateDeliverStatus()
    {
        $this->checkLogin();
        $supplyId = $this->request->param('supply_id', 0, 'intval');
        $action = $this->request->param('action', '', 'trim');
        $uid = $this->request->log_uid;
        $transferToUid = $this->request->param('uid', 0, 'intval');
        if ($supplyId < 1 || empty($action)) {
            return api_output(1001, [], L_('参数有误'));
        }
        try {
            (new DeliverUserService())->updateDeliverStatus($uid, $supplyId, $action, $transferToUid);
            return api_output(0, []);
        } catch (\Throwable $th) {
            return api_output($th->getCode() ? $th->getCode() : 1003, [], $th->getMessage());
        }
    }

    /**
     * 订单详情
     * @author: 张涛
     * @date: 2020/9/14
     */
    public function detail()
    {
        $this->checkLogin();
        $supplyId = $this->request->param('supply_id', 0, 'intval');
        $uid = $this->request->log_uid;
        if ($supplyId < 1 || $uid < 1) {
            return api_output(1001, [], L_('参数有误'));
        }
        try {
            $detail = (new DeliverUserService())->orderDetail($supplyId, $uid);
            return api_output(0, $detail);
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }


    /**
     * 扫码收单检查配送单状态
     * @author: 张涛
     * @date: 2020/9/17
     */
    public function scanOrderCheck()
    {
        $this->checkLogin();
        $content = $this->request->param('content', '', 'trim');
        $uid = $this->request->log_uid;
        if (empty($content)) {
            return api_output(1001, [], L_('二维码内容不能为空'));
        }
        try {
            $rs = (new DeliverUserService())->scanOrderCheck($uid, $content);
            return api_output(0, $rs);
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 扫码流转下一步
     * @author: 张涛
     * @date: 2020/9/17
     */
    public function flowToNextStatus()
    {
        $this->checkLogin();
        $supplyId = $this->request->param('supply_id', 0, 'intval');
        $action = $this->request->param('action', '', 'trim');
        $uid = $this->request->log_uid;
        if ($supplyId < 1 && empty($action)) {
            return api_output(1001, [], L_('参数有误'));
        }
        try {
            (new DeliverUserService())->flowToNextStatus($uid, $supplyId, $action);
            return api_output(0, []);
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }


    /**
     * 帮我送语音发单完善信息
     *
     * @return void
     * @date: 2021/08/26
     */
    public function perfectInfo()
    {
        $this->checkLogin();
        $params['supply_id'] = $this->request->param('supply_id', 0, 'intval');
        $params['long'] = $this->request->param('long', '');
        $params['lat'] = $this->request->param('lat', '');
        $params['address'] = $this->request->param('address', '', 'trim');
        $params['detail'] = $this->request->param('detail', '', 'trim');
        $params['name'] = $this->request->param('name', '', 'trim');
        $params['phone'] = $this->request->param('phone', '', 'trim');
        $params['goods_category'] = $this->request->param('goods_category', '');
        $params['weight'] = $this->request->param('weight', 0);
        $params['uid'] = $this->request->log_uid;

        try {
            (new DeliverUserService)->perfectInfo($params);
            return api_output(0, []);
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }
}
