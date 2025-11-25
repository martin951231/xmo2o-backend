<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      小区设备获取开门记录业务
	 */

	namespace app\job;

	use app\community\model\service\HouseFaceDeviceService;
    use think\Exception;
	use think\queue\Job as DoJob;

	class FaceDevicePassRecordJob
	{
		/**
		 * @param DoJob $job
		 * @param     $data  eg.
         *  A4设备(deviceType==4) $data = [
         *      'startTime' => '查询开门记录的开始时间 选传',
         *      'log_time_max' => '目前已有记录的最新时间-即获取开门记录的开始时间，以此为准 选传',
         *      'village_id' => '小区ID 必须有',
         *      'communityId' => 'A4添加小区返回的小区ID 选传',
         *	];
		 *
		 *  可以参考：v20/app/traits/FaceDevicePassRecordTraits.php
		 */
		public function fire(DoJob $job , $data)
		{
			if ($job->attempts() >= 1){
				$job->delete();
			}
			try {
			    if ($data && isset($data['deviceType'])) {
                    switch ($data['deviceType']) {
                        case 4:
                            (new HouseFaceDeviceService())->a4GetDoorOpenRecordJob($data);
                            break;
                    }
                }
			}catch (Exception $e){

            }

		}

		public function failed($data)
		{
		}
	}