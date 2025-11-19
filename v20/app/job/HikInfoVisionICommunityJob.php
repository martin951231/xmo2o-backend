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
    use app\consts\HikConst;
    use app\consts\DeviceConst;
    use think\Exception;
	use think\queue\Job as DoJob;
    use app\community\model\service\Device\HikInfoVisionICommunityService;
    use think\facade\Cache;

	class HikInfoVisionICommunityJob
	{

        public function fire(DoJob $job, $data)
        {
            if ($job->attempts() >= 1) {
                $job->delete();
            }
            try {
                // todo 对应执行
                $job_id = $job->getJobId();
                $cacheTag = HikConst::HIK_COMMUNITY_JOB_REDIS_TAG;
                $cacheKey = HikConst::HIK_COMMUNITY_JOB_REDIS_KEY . $job_id;
                Cache::store('redis')->tag($cacheTag)->set($cacheKey, 1);

                $hikInfoVisionICommunityService = new HikInfoVisionICommunityService();
                $handle_type = isset($data['handle_type']) && $data['handle_type'] ? $data['handle_type'] : 'getRegionsHouses';
                switch ($handle_type) {
                    case 'getRegionsHouses':
                        $hikInfoVisionICommunityService->getRegionsHousesOnQueue($data);
                        break;
                    case 'getAcsDoorSearch':
                        $pageNo = isset($data['pageNo']) && intval($data['pageNo']) ? intval($data['pageNo']) : 1;
                        $pageSize = isset($data['pageSize']) && intval($data['pageSize']) ? intval($data['pageSize']) : 100;
                        $hikInfoVisionICommunityService->getAcsDoorSearch($pageNo, $pageSize, $data);
                        break;
                    case 'getDoorState':
                        $device_id = isset($data['device_id']) && intval($data['device_id']) ? intval($data['device_id']) : 0;
                        $doorIndexCode = isset($data['doorIndexCode']) && intval($data['doorIndexCode']) ? intval($data['doorIndexCode']) : '';
                        $hikInfoVisionICommunityService->getDoorState($device_id, $doorIndexCode);
                        break;
                    case 'sycBindUserToHikCommunity':
                        $hikInfoVisionICommunityService->pushBindUserToHikCommunity($data);
                        break;
                }
            } catch (Exception $e) {
                fdump_api([$data, $e->getMessage()], 'ErrHikInfoVisionICommunityJobFire', 1);
            }
        }

        public function failed($data)
        {
            
        }
	}