<?php

namespace app\common\controller\api;

use app\common\model\service\MembershipCardOrderService;
use app\common\model\service\MembershipCardService;

class MembershipCardController extends ApiBaseController
{
    /**
     * 平台会员卡优惠券信息
     * @author: 张涛
     * @date: 2020/12/19
     */
    public function detail()
    {
        //全部走新版接口，旧版接口废弃
        return $this->newDetail();

        try {
            $uid = $this->request->log_uid;
            $rs = (new MembershipCardService())->getDetail();
            $rs['kefu']['phone'] = empty(cfg('site_phone')) ? [] : explode(' ', cfg('site_phone'));
            $platKefu = \think\facade\Db::name('merchant_store_kefu')->where('store_id', '=', 0)->where('belong', '=', 'plat')->find();
            $rs['kefu']['url'] = $platKefu ? build_im_chat_url('user_' . $uid, $platKefu['username'], 'user2plat') : '';
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }

    }

    /**
     * 保存订单
     * @author: 张涛
     * @date: 2020/12/19
     */
    public function saveOrder()
    {
        $this->checkLogin();
        try {
            $orderId = (new MembershipCardOrderService())->saveOrder($this->request->log_uid);
            return api_output(0, ['order_id' => $orderId, 'type' => 'mcard']);
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 我的购买记录
     * @author: 张涛
     * @date: 2020/12/19
     */
    public function myOrder()
    {
        $this->checkLogin();
        try {
            $param['page'] = $this->request->param('page', 1, 'intval');
            $param['pageSize'] = $this->request->param('pageSize', 20, 'intval');
            $rs = (new MembershipCardOrderService())->myOrder($this->request->log_uid, $param);
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 平台会员卡优惠券信息-新版
     */
    public function newDetail()
    {
        try {
            $uid = $this->request->log_uid;
            $rs = (new MembershipCardService())->getNewDetail($uid);
            $rs['kefu']['phone'] = empty(cfg('site_phone')) ? [] : explode(' ', cfg('site_phone'));
            $platKefu = \think\facade\Db::name('merchant_store_kefu')->where('store_id', '=', 0)->where('belong', '=', 'plat')->find();
            $rs['kefu']['url'] = $platKefu ? build_im_chat_url('user_' . $uid, $platKefu['username'], 'user2plat') : '';
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 获取使用会员卡的订单列表
     */
    public function getOrderList(){
        $this->checkLogin();
        $param['uid'] = $this->request->log_uid;
        $param['card_id'] = $this->request->param('card_id',0,'trim,intval');
        try {
            $rs = (new MembershipCardService())->getOrderList($param);
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}