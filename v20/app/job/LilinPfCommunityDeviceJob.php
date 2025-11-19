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
    use app\community\model\service\HouseVillageLilinPushRecordService;
    use think\facade\Cache;

	class LilinPfCommunityDeviceJob
	{

        public function fire(DoJob $job, $data)
        {
            if ($job->attempts() >= 1) {
                $job->delete();
            }
            try {
                
                $dir_root=dirname(dirname(dirname(__FILE__)));
                $dir_root=dirname($dir_root);
                $basePath =  rtrim($dir_root,'/');
                $_SERVER['DOCUMENT_ROOT']=$basePath;
                
                // todo 对应执行
                fdump_api([$data,'basePath'=>$basePath,'dtime'=>date('Y-m-d H:i:s')], 'liLinAccess/LilinPfCommunityDeviceJob');
                $houseVillageLilinPushRecordService = new HouseVillageLilinPushRecordService();
                $handle_type = isset($data['handle_type']) && $data['handle_type'] ? $data['handle_type'] : 'getRegionsHouses';
                switch ($handle_type) {
                    case 'deviceMassUserSyc':
                        //设备一键同步
                        $houseVillageLilinPushRecordService->deviceMassUserSyc($data);
                        break;
                    case 'sycUserInfoToDevice':
                        //最红人脸 ic卡同步出来
                        $houseVillageLilinPushRecordService->sycUserInfoToDevice($data);
                        break;
                    case 'sycUserToDevice':
                        //单个人同步
                        $houseVillageLilinPushRecordService->sycUserToDevice($data['village_id'],$data['pigcms_id']);
                        break;

                }
            } catch (Exception $e) {
                fdump_api([$data, $e->getMessage()], 'liLinAccess/LilinPfCommunityDeviceJobException', 1);
            }
        }

        public function failed($data)
        {
            
        }
	}