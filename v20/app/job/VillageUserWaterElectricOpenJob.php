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
    use app\community\model\service\CustomizeMeterWaterReadingService;

	class VillageUserWaterElectricOpenJob
	{

        public function fire(DoJob $job , $data)
        {
            if ($job->attempts() >= 1){
                $job->delete();
            }
            try {
                $meterWaterReadingService=new CustomizeMeterWaterReadingService();
                $meterWaterReadingService->handleUserToOpenWaterElectricJob($data);

            }catch (Exception $e){}
        }

        public function failed($data)
        {
            
        }
	}