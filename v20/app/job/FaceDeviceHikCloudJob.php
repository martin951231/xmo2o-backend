<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      海康云眸 队列
	 */

	namespace app\job;


    use app\community\model\service\Device\AlarmDeviceService;
    use app\community\model\service\Device\DeviceBindUserService;
    use app\community\model\service\Device\DeviceHkNeiBuHandleService;
    use app\community\model\service\Device\Hik6000CCameraService;
    use app\consts\HikConst;
    use app\consts\DeviceConst;
    use think\Exception;
	use think\queue\Job as DoJob;
    use app\community\model\service\FaceDeviceService;
    use think\facade\Cache;

	class FaceDeviceHikCloudJob
	{
		/**
		 * @param DoJob $job
		 * @param     $data 
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
            if ($job->attempts() >= 1) {
                $job->delete();
            }
            try {
                // todo 对应执行
                /*
                $job_id = $job->getJobId();
                $cacheTag  = HikConst::HIK_JOB_REDIS_TAG;
                $cacheKey  = HikConst::HIK_JOB_REDIS_KEY . $job_id;
                Cache::store('redis')->tag($cacheTag)->set($cacheKey,1,864000);
                */
                if ($data && isset($data['thirdProtocol'])) {
                    switch ($data['thirdProtocol']) {
                        case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                        case DeviceConst::HIK_YUNMO_NEIBU_6000C:
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
            } catch (\Exception $e) {
                fdump_api(['data' => $data, 'jobType'=>$jobType,'getMessage' => $e->getMessage(), 'msg' => '错误', 'line'=>$e->getLine(), 'file'=>$e->getFile()], 'v20Face/faceDeviceHikCloudJob',1);
            }

        }

        public function failed($data)
        {
        }

        /**
         * 根据 队列类型调取对应方法
         * @param string $jobType 队列类型
         * villageToDeviceCloud：小区信息同步至设备云
         * @param array $data 参数
         * @return mixed
         */
        public function getEnvFunction($jobType, $data) {
            usleep(500000); // 休眠500毫秒
            switch ($jobType) {
                case 'villageToDeviceCloud':
                    (new FaceDeviceService())->villageToDeviceCloud($data);
                    break;
                case 'addDeviceToCloud':
                    (new FaceDeviceService())->addDeviceToCloud($data);
                    break;
                case 'buildToDeviceCloud':
                    (new DeviceHkNeiBuHandleService())->buildToDeviceCloud($data);
                    break;
                case 'singleHikHBuildingToDeviceCloud':
                    (new DeviceHkNeiBuHandleService())->singleHikHBuildingToDeviceCloud($data);
                    break;
                case 'floorToDeviceBox':
                    (new DeviceHkNeiBuHandleService())->floorToDeviceBox($data);
                    break;
                case 'singleFloorToHikDeviceCloud':
                    if (isset($data['floor_param'])) {
                        $floor_param = $data['floor_param'];
                        unset($data['floor_param']);
                    } else {
                        $floor_param = [];
                    }
                    (new DeviceHkNeiBuHandleService())->singleFloorToHikDeviceCloud($floor_param, $data);
                    break;
                case 'getDeviceStatus':
                    (new FaceDeviceService())->getDeviceStatus($data);
                    break;
                case 'roomToDeviceBox':
                    (new DeviceHkNeiBuHandleService())->roomToDeviceBox($data);
                    break;
                case 'singleRoomToDeviceCloud':
                    (new DeviceHkNeiBuHandleService())->singleRoomToDeviceCloud($data);
                    break;
                case 'userSynToDevice':
                    (new FaceDeviceService())->userSynToDevice($data);
                    break;
                case 'userSynDeviceByPigcmsId':
                    (new FaceDeviceService())->userSynDevice($data['pigcms_id'],0);
                    break;
                case 'addPersonsToCloud':
                    (new FaceDeviceService())->addPersonsToCloud($data);
                    break;
                case 'addSinglePersonToCloud':
                    try {
                        (new DeviceHkNeiBuHandleService())->addSinglePersonToCloud($data);
                    } catch (\Exception $e) {
                        fdump_api(['jobType' => $jobType, 'data' => $data, 'getMessage' => $e->getMessage(), 'msg' => '错误', 'line'=>$e->getLine(), 'file'=>$e->getFile()], 'v20Face/faceDeviceHikCloudJob',1);
                    }
                    break;
                case 'authorityIssued':
                    if (isset($data['issuedParam'])) {
                        $issuedParam = $data['issuedParam'];
                        unset($data['issuedParam']);
                    } else {
                        $issuedParam = [];
                    }
                    (new DeviceHkNeiBuHandleService())->IssuedPersonToDevice($data, $issuedParam);
                    break;
                case 'handleCommunityMessageAccessState':
                    (new DeviceBindUserService())->handleCommunityMessageAccessState($data);
                    break;
                case 'hik6000CGetDeviceByCommunityId':
                    $village_id = isset($data['village_id']) ? $data['village_id'] : 0;
                    $param = ['isJob' => 1];
                    (new Hik6000CCameraService())->getDeviceByCommunityId($village_id, '', $param);
                    break;
                case 'hik6000CGetDeviceChannelByCommunityId':
                    $village_id   = isset($data['village_id'])   ? $data['village_id']   : 0;
                    $community_id = isset($data['community_id']) ? $data['community_id'] : '';
                    $param        = $data;
                    $param['isJob'] = 1;
                    (new Hik6000CCameraService())->getDeviceChannelByCommunityId($village_id, $community_id, $param);
                    break;
                case 'alarmToWorkOrder':
                    (new AlarmDeviceService())->alarmToWorkOrder($data);
                    break;
            }
            return $data;
        }
	}