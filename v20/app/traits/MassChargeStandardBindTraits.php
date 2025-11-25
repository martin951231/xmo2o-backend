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
    use app\job\HouseMassChargeStandardBindJob;
    

    trait MassChargeStandardBindTraits
    {
        /**
         * 队列名称
         * @return string
         */
        public function houseMassChargeStandardBindTraits()
        {
            return 'house-mass-charge-standard-tobind';
        }

        
        /**
         * 公用的立即下发队列
         * @param $queueData
         * @return mixed
         */
        protected function houseMassChargeStandardBindToJob($queueData)
        {
            return Queue::push(HouseMassChargeStandardBindJob::class, $queueData, $this->houseMassChargeStandardBindTraits());
        }
        /***
        *延迟几秒在只执行
         **/
        public function houseMassChargeStandardBindToJobLater($queuData,$time=3)
        {
            return Queue::later($time,HouseMassChargeStandardBindJob::class,$queuData,$this->houseMassChargeStandardBindTraits());
        }
    }