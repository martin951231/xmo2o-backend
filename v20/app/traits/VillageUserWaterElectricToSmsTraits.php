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
    use app\job\VillageUserWaterElectricToSmsJob;
    use app\job\VillageUserWaterElectricOpenJob;

    trait VillageUserWaterElectricToSmsTraits
    {
        /**
         * 队列名称
         * @return string
         */
        public function villageUserWaterElectricToSmsToCloudTraits()
        {
            return 'village-user-water-electric-to-sms';
        }

        
        /**
         * 公用的立即下发队列
         * @param $queueData
         * @return mixed
         */
        protected function villageMeterAutoTipsQueuePushToJob($queueData)
        {
            return Queue::push(VillageUserWaterElectricToSmsJob::class, $queueData, $this->villageUserWaterElectricToSmsToCloudTraits());
        }
        /**
        *60秒后回复水电
         ***/
        protected function villageOpenWaterElectricQueuePushToJob($queueData,$time=60)
        {
            return Queue::later($time,VillageUserWaterElectricOpenJob::class,$queueData,$this->villageUserWaterElectricToSmsToCloudTraits());
        }
    
    }