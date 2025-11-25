<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      普通操作日志队列 ，本队列不允许塞业务日志，
	 *            另外，注意队列重复消费的问题
	 */

	namespace app\job;
    use app\community\model\service\HouseNewCashierService;
    use think\Exception;
	use think\queue\Job as DoJob;

	class AddPayOrderHouseJob
	{
		public function fire(DoJob $job , $data)
		{
			if ($job->attempts() >= 1){ //
				$job->delete();
			}
			try {
				(new HouseNewCashierService())->call1($data);
			}catch (Exception $e){
			    fdump_api([$data,$e->getMessage()],'AddPayOrderHouseJob_0720',1);
            }

		}

		public function failed($data)
		{
		}
	}