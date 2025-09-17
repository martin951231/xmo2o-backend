<?php

namespace app\merchant\controller\merchant;

use app\common\model\service\coupon\MerchantCouponService;
use app\merchant\controller\merchant\AuthBaseController;

class CouponController extends AuthBaseController
{

    /**
     * 商家优惠券核销记录
     * @author: 张涛
     * @date: 2020/11/25
     */
    public function merCouponUseRecords()
    {
        $param['mer_id'] = $this->merId;
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 20, 'intval');
        $param['business_type'] = $this->request->param('business_type', '', 'trim');
        $param['is_discount'] = $this->request->param('is_discount', '-1','intval');
        $param['is_mobile_pay'] = $this->request->param('is_mobile_pay', '-1','intval');
        $param['keyword'] = $this->request->param('keyword', '', 'trim');
        $param['start_time'] = $this->request->param('start_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['store_id'] = $this->request->param('store_id', '', 'intval');
        $rs = (new MerchantCouponService())->getUseRecords($param);
        return api_output(0, $rs);
    }

    /**
     * 商家优惠券领取记录
     */
    public function merCouponGetRecords()
    {
        $param['mer_id']      = $this->merId;
        $param['page']        = $this->request->param('page', 1, 'intval');
        $param['pageSize']    = $this->request->param('pageSize', 10, 'intval');
        $param['search_type'] = $this->request->param('search_type', 1, 'intval');
        $param['keyword']     = $this->request->param('keyword', '', 'trim');
        $param['time_type']   = $this->request->param('time_type', 1, 'intval');
        $param['begin_time']  = $this->request->param('begin_time', '', 'trim');
        $param['end_time']    = $this->request->param('end_time', '', 'trim');
        try {
            $arr = (new MerchantCouponService())->getHadpullRecords($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 商家优惠券回收
     */
    public function updateUse()
    {
        $param['id'] = $this->request->param('id', 0, 'intval');
        $param['uid'] = $this->request->param('uid', 0, 'intval');
        $param['note']     = $this->request->param('note', '', 'trim');
        $param['coupon_name']     = $this->request->param('coupon_name', '', 'trim');
        try {
            $arr = (new MerchantCouponService())->updateUse($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 导出商家优惠券领取记录
     */
    public function exportMerGetRecords()
    {
        $param['mer_id']      = $this->merId;
        $param['page']        = $this->request->param('page', 1, 'intval');
        $param['pageSize']    = $this->request->param('pageSize', 10, 'intval');
        $param['search_type'] = $this->request->param('search_type', 1, 'intval');
        $param['keyword']     = $this->request->param('keyword', '', 'trim');
        $param['time_type']   = $this->request->param('time_type', 1, 'intval');
        $param['begin_time']  = $this->request->param('begin_time', '', 'trim');
        $param['end_time']    = $this->request->param('end_time', '', 'trim');
        try {
            $arr = (new MerchantCouponService())->exportMerGetRecords($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}
