<?php

namespace app\merchant\controller\merchant;

use app\common\model\db\UserLevel;
use app\common\model\service\coupon\MerchantCouponService;
use app\common\model\service\UserLevelService;
use app\merchant\controller\merchant\AuthBaseController;
use app\merchant\model\service\CouponService;
use think\response\Json;

class CouponController extends AuthBaseController
{
	const STATUS_OK = 1000;

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

	/**
	 * 优惠券列表
	 * @return Json
	 */
	public function couponList(): Json
	{
		$data = (new CouponService())->couponList($this->merId)->toArray();
		return api_output(self::STATUS_OK, $data);
	}

	/**
	 * 优惠券发送记录
	 * @return Json
	 */
	public function couponSendRecord(): Json
	{
		$planId = input('plan_id', 0, 'intval');
		if (empty($planId)) {
			return api_output_error(1001, '参数错误');
		}
		$data = (new CouponService())->couponSendRecord($this->merId, $planId, input(),true);
		return api_output(self::STATUS_OK, $data);
	}

	/**
	 * 保存任务
	 * @return Json
	 */
	public function savePlan(): Json
	{
		$params = input();
		$this->validate($params, [
			'plan_name|任务名称' => 'require',
			'start_date|任务开始日期' => 'require',
			'end_date|任务结束时间' => 'require',
			'status|状态' => 'require|in:0,1',
			'type|发放类型' => 'require|in:1,2',
			'type_day|发放日期' => 'require',
			'send_time|发放时间' => 'require',
			'user_type|筛选类型' => 'require',
			'coupon_info|优惠券选择' => 'require',
		]);

		$data = (new CouponService())->savePlan($this->merId, $params);
		return api_output(self::STATUS_OK, $data);
	}

	/**
	 * 任务列表
	 * @return Json
	 */
	public function planList(): Json
	{
		$data = (new CouponService())->planList($this->merId, input())->toArray();
		return api_output(self::STATUS_OK, $data);
	}

	/**
	 * 改变任务状态
	 * @return Json
	 */
	public function changePlanStatus(): Json
	{
		$id = input('id', 0, 'intval');
		if (empty($id)) {
			return api_output_error(1001, '参数错误');
		}
		$data = (new CouponService())->changePlanStatus($this->merId, $id);
		return api_output(self::STATUS_OK, $data);
	}

	/**
	 * 删除任务
	 * @return Json
	 */
	public function deletePlan(): Json
	{
		$id = input('id', 0, 'intval');
		if (empty($id)) {
			return api_output_error(1001, '参数错误');
		}
		$data = (new CouponService())->deletePlan($this->merId, $id);
		return api_output(self::STATUS_OK, $data);
	}

	/**
	 * 平台等级
	 * @return Json
	 */
	public function levelList(): Json
	{
		return api_output(self::STATUS_OK, UserLevelService::levelList()->toArray());
	}

	/**
	 * 会员分组
	 * @return Json
	 */
	public function groupList(): Json
	{
		$data = (new CouponService())->groupList($this->merId)->toArray();
		return api_output(self::STATUS_OK, $data);
	}
}
