<?php

/**
 * 每30分中执行一次系统任务
 */

namespace app\task;

use think\Exception;
use yunwuxin\cron\Task;

class EveryThirtyMinutesSysTask extends Task
{
	public function configure()
	{
		$this->everyThirtyMinutes();
	}

	public function execute()
	{
		try {
			//同步外卖店铺评分
			(new \app\common\model\service\MerchantStoreShopService())->syncShopScore();

			//同步商场猜你喜欢
			(new \app\building\service\ShoppingMallService())->syncMallGuessYouLikeTask();
		} catch (\Exception $e) {
			//就这样了，下次同步呗
		}
	}
}
