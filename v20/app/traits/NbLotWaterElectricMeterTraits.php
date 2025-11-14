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
    use app\job\NbLotWaterElectricMeterJob;

    trait NbLotWaterElectricMeterTraits
    {
        /**
         * 队列名称
         * @return string
         */
        public function nbLotWaterElectricMeterCloudTraits()
        {
            return 'nb-lot-water-electric-meter';
        }

        
        protected function nbLotWaterElectricMeterQueuePushToJob($queueData)
        {
            return Queue::push(NbLotWaterElectricMeterJob::class, $queueData, $this->nbLotWaterElectricMeterCloudTraits());
        }

        protected function nbLotWaterElectricMeterLaterQueuePushToJob($queueData,$time=60)
        {
            return Queue::later($time,NbLotWaterElectricMeterJob::class,$queueData,$this->nbLotWaterElectricMeterCloudTraits());
        }
    
    }