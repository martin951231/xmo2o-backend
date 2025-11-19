<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      人脸设备定时获取开门记录公共方法
	 *            注意，为了避免重复名称污染，该文件的方法名统一使用 trait 开头
	 */

	namespace app\traits;

    use think\facade\Queue;
    use app\job\FaceDevicePassRecordJob;

    trait FaceDevicePassRecordTraits
    {
        /**
         * 队列名称
         * @return string
         */
        public function queueNameFaceDevicePassRecordTraits()
        {
            return 'face-device-pass-record';
        }
        /**
         * 泰首>A1、A2；笛虎云>A4；海康威视>A5、A6；快鲸>A185；智国互联>D1；华安视讯>D3；蓝牛>D4
         * 字段参考下面对应设备类型处理传参
         */
        public function laterGetPassRecordQueue($queuData,$time=5){
            if ($queuData && isset($queuData['deviceType'])) {
                switch ($queuData['deviceType']) {
                    case 4:
                        $this->laterA4($queuData,$time);
                        break;
                }
            }
        }

        /**
         * 把A4（笛虎云）获取开门记录压入队列，默认延迟5秒执行
         * @param     $queuData
         *             eg.
         *  $data = [
         *      'startTime' => '查询开门记录的开始时间 选传',
         *      'log_time_max' => '目前已有记录的最新时间-即获取开门记录的开始时间，以此为准 选传',
         *      'village_id' => '小区ID 必须有',
         *      'communityId' => 'A4添加小区返回的小区ID 选传',
         *	];
         *
         * @param int $time default 5 s
         */
        protected function laterA4($queuData,$time=5)
        {
            Queue::later($time,FaceDevicePassRecordJob::class,$queuData,$this->queueNameFaceDevicePassRecordTraits());
        }
    }