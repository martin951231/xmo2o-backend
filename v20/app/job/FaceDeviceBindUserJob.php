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
    use app\community\model\service\OwnerService;
    use app\consts\DeviceConst;
    use think\Exception;
	use think\queue\Job as DoJob;
    use app\community\model\service\FaceDeviceService;
    use app\consts\DahuaConst;
    use think\facade\Cache;

	class FaceDeviceBindUserJob
	{
		/**
		 * @param DoJob $job
		 * @param     $data  
		 */
		public function fire(DoJob $job , $data)
		{
            if ($job->attempts() >= 1) {
                $job->delete();
            }
            $job_id = $job->getJobId();
            $cacheTag  = DeviceConst::FACE_DEVICE_USER_JOB_REDIS_TAG;
            $cacheKey   = DeviceConst::FACE_DEVICE_USER_JOB_REDIS_KEY . $job_id;
            Cache::store('redis')->tag($cacheTag)->set($cacheKey,1);
            try {
                // todo 对应执行
                fdump_api([
                    '$data'     => $data,
                    '$cacheTag' => $cacheTag,
                    '$cacheKey' => $cacheKey,
                    '$job_id'   => $job_id,
                ], 'faceDeviceBindUser/job', 1);
                $jobInfo = (new OwnerService())->bindPersonal($data);
                fdump_api([
                    '$jobInfo'     => $jobInfo,
                ], 'faceDeviceBindUser/fireGarbageLog', 1);
                return true;
            } catch (\Exception $e) {
                fdump_api(['data' => $data,'err' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()],'faceDeviceBindUser/ErrGarbageJobFire',1);
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