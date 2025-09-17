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
    use app\job\HouseListDatasExportOutJob;
    

    trait HouseListDatasExportOutTraits
    {
        /**
         * 队列名称
         * @return string
         */
        public function houseListDatasExportExcelOutTraits()
        {
            return 'house-list-datas-export-excel-out';
        }

        
        /**
         * 公用的立即下发队列
         * @param $queueData
         * @return mixed
         */
        protected function exportExcelOutJob($queueData)
        {
            return Queue::push(HouseListDatasExportOutJob::class, $queueData, $this->houseListDatasExportExcelOutTraits());
        }
        /***
        *延迟几秒在只执行
         **/
        public function exportExcelOutJobLater($queuData,$time=3)
        {
            return Queue::later($time,HouseListDatasExportOutJob::class,$queuData,$this->houseListDatasExportExcelOutTraits());
        }
    }