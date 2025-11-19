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
    use think\Exception;
	use think\queue\Job as DoJob;
    use app\community\model\service\Device\XiHanEventService;
    use think\facade\Cache;

	class XiHanFaceDeviceJob
	{

        public function fire(DoJob $job, $data)
        {
            if ($job->attempts() >= 1) {
                $job->delete();
            }
            try {
                // todo 对应执行
                $job_id = $job->getJobId();
                $cacheTag  ='xiHan:FaceDeviceBatch';
                $cacheKey ='xiHan:FaceDeviceBatch_job'. $job_id;
                Cache::store('redis')->tag($cacheTag)->set($cacheKey, 1);

                $xiHanEventService = new XiHanEventService();
                $handle_type = isset($data['handle_type']) && $data['handle_type'] ? $data['handle_type'] : '';
                switch ($handle_type) {
                    case 'sycBindUserToXiHanDevice':
                        $xiHanEventService->pushBindUserToXiHanDevice($data);
                        break;
                }
                return true;
            } catch (Exception $e) {
                fdump_api([$data, $e->getMessage()], 'xihan/queue_add_person', 1);
            }
        }

        public function failed($data)
        {
            
        }
	}