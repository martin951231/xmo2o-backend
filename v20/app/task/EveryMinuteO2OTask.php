<?php

namespace app\task;

use app\merchant\model\service\CouponService;
use app\survey\service\FormService;
use yunwuxin\cron\Task;

/**
 * O2O每分钟计划任务
 *
 * @author: zt
 * @date: 2023/12/27
 */
class EveryMinuteO2OTask extends Task
{
	public function configure()
	{
		$this->everyMinute();
	}

	/**
	 * 执行任务
	 * @return mixed
	 */
	protected function execute()
	{
		try {
			invoke_cms_model('Plan/smartDelivery');
			//同步批量上传商品图片
			invoke_cms_model('Shop_goods/picSync');
			//用户推广佣金结算
			(new \app\common\model\service\UserSpreadListService)->spreadToCompleteTask();
		} catch (\Exception $th) {
			//throw $th;
		}
		//发放问卷调查表
		try {
			(new FormService())->sendFormNotice();
		} catch (\Exception $e) {
			fdump_api([$e->getMessage(), $e->getLine(), $e->getFile()], 'userNotice/cron_error', true);
		}

		//发放商家优惠券
		try {
			(new CouponService())->addCouponUserToQueue();
		} catch (\Exception $e) {
			fdump_api([$e->getMessage(), $e->getLine(), $e->getFile()], 'cron/CouponServiceError', true);
		}
	}
}
