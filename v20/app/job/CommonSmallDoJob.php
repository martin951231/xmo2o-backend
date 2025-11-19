<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      普通操作日志队列 ，本队列不允许塞业务日志，
	 *            另外，注意队列重复消费的问题
	 */

	namespace app\job;
    use think\Exception;
	use think\queue\Job as DoJob;

	class CommonSmallDoJob
	{
		public function fire(DoJob $job , $data)
		{
			if ($job->attempts() >= 1){ //
				$job->delete();
			}
            $root=dirname(dirname(dirname(__DIR__)));
            $basePath =  rtrim($root,'/');
            $_SERVER['DOCUMENT_ROOT']=$basePath;
            fdump_api(['basePath'=>$basePath,'data'=>$data],'00CommonSmallDoJob',1);
            try {
                $opt_type = isset($data['opt_type']) ? trim($data['opt_type']) : '';
                    switch ($opt_type) {
                        case 'jpush_house_village_user_bind':
                            $houseAppJpushInfoService= new \app\community\model\service\HouseAppJpushInfoService();
                            $houseAppJpushInfoService->handleVillageUserJpushInfoSend($data);
                            break;
                        case 'autoMaticCallToQueue':
                            $houseNewCashierService= new \app\community\model\service\HouseNewCashierService();
                            $houseNewCashierService->autoMaticCallFromQueue($data);
                            break;
                            
                    }
                    
			}catch (Exception $e){
			    fdump_api([$data,$e->getMessage()],'00CommonSmallDoJob',1);
            }
            return true;
		}

		public function failed($data)
		{
            
		}
	}