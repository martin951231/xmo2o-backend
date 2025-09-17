<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      大华 队列
	 */

	namespace app\job;

    use app\community\model\service\Device\DeviceFingerprintService;
    use think\Exception;
	use think\queue\Job as DoJob;
    use app\community\model\service\FaceDeviceService;
    use app\consts\DahuaConst;
    use app\consts\DeviceConst;
    use think\facade\Cache;

	class FaceDeviceDHCloudJob
	{
		/**
		 * @param DoJob $job
		 * @param     $data  
         * thirdProtocol 为大华相关协议 $data = [
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
                $job_id = $job->getJobId();
                $cacheTag  = DahuaConst::DH_JOB_REDIS_TAG;
                $cacheKey  = DahuaConst::DH_JOB_REDIS_KEY . $job_id;
                Cache::store('redis')->tag($cacheTag)->set($cacheKey,1);
                if ($data && isset($data['thirdProtocol'])) {
                    switch ($data['thirdProtocol']) {
                        case DeviceConst::DH_YUNRUI:
                            if (isset($data['jobType'])) {
                                $jobType = $data['jobType'];
                                unset($data['jobType']);
                            } else {
                                $jobType = '';
                            }
                            if (isset($data['job_id'])) {
                                unset($data['job_id']);
                            }
                            $this->getEnvFunction($jobType, $data);
                            break;
                    }
                }
			} catch (Exception $e){
                fdump_api([$data,$e->getMessage()],'$fireErr');
            }

		}

		public function failed($data)
		{
		}

		public function getEnvFunction($jobType, $data) {
            switch ($jobType) {
                case 'groupToDeviceCloud':
                    (new FaceDeviceService())->groupToDeviceCloud($data);
                    break;
                case 'villageToDeviceCloud':
                    (new FaceDeviceService())->villageToDeviceCloud($data);
                    break;
                case 'addDeviceToCloud':
                    (new FaceDeviceService())->addDeviceToCloud($data);
                    break;
                case 'addPersonToDhYunRuiCloud':
                    (new FaceDeviceService())->addPersonToDhYunRuiCloud($data);
                    break;
                case 'autoDHBuildDataBind':
                    (new FaceDeviceService())->autoDHBuildDataBind($data);
                    break;
                case 'bindDHBuildUnitRoom':
                    $bindId    = isset($data['bindId'])    && $data['bindId']    ? $data['bindId']    : 0;
                    $bindParam = isset($data['bindParam']) && $data['bindParam'] ? $data['bindParam'] : [];
                    $orgParam  = isset($data['orgParam'])  && $data['orgParam']  ? $data['orgParam']  : [];
                    (new FaceDeviceService())->bindDHBuildUnitRoom($bindId, $bindParam, $orgParam);
                    break;
                case 'addPersonsToCloud':
                    (new FaceDeviceService())->addPersonsToCloud($data);
                    break;
                case 'addDhAuth':
                    (new FaceDeviceService())->addDhAuth($data);
                    break;
                case 'userSynToDevice':
                    (new FaceDeviceService())->userSynToDevice($data);
                    break;
                case 'getDhWonerDoorOpenRecord':
                    (new FaceDeviceService())->getDhWonerDoorOpenRecord($data);
                    break;
                case 'getDeviceStatus':
                    (new FaceDeviceService())->getDeviceStatus($data);
                    break;
                case 'getDhPersonFingerprintByRoom':
                    (new DeviceFingerprintService())->getDhPersonFingerprintByRoom($data);
                    break;
                case 'getDhPersonByPrfoleId':
                    $pigcms_id = isset($data['pigcms_id']) && $data['pigcms_id'] ? intval($data['pigcms_id']) : 0;
                    $isAddAuth = isset($data['isAddAuth']) && $data['isAddAuth'] ? intval($data['isAddAuth']) : 0;
                    $uid       = isset($data['uid'])       && $data['uid']       ? intval($data['uid'])       : 0;
                    (new FaceDeviceService())->getDhPersonByPrfoleId($pigcms_id,$uid, $isAddAuth, $data);
                    break;
            }
        }
	}