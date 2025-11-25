<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      人脸设备同步
	 */

	namespace app\traits;
    use think\facade\Queue;
    use app\job\WorksOrderAutoEvaluateJob;
    

    trait WorksOrderAutoEvaluateTraits
    {
        /**
         * 队列名称
         * @return string
         */
        public function worksOrderAutoEvaluateToCloudTraits()
        {
            return 'house-new-repair-works-order-auto-evaluate';
        }

        
        /**
         * 公用的立即下发队列
         * @param $queueData
         * @return mixed
         */
        protected function worksOrderAutoEvaluateVillageQueuePushToJob($queueData)
        {
            return Queue::push(WorksOrderAutoEvaluateJob::class, $queueData, $this->worksOrderAutoEvaluateToCloudTraits());
        }
        
    }