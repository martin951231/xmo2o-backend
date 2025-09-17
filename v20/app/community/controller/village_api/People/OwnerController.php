<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      业主控制器
	 */
	namespace app\community\controller\village_api\People;

	use app\community\controller\CommunityBaseController;
    use app\community\model\service\Device\HouseFaceImgService;
	use app\common\model\service\export\ExportService;
    use app\community\model\service\HouseNewParkingService;
    use app\community\model\service\HouseNewPorpertyService;
	use app\community\model\service\HouseVillageUserBindService;
    use app\community\model\service\OwnerService;
    use app\community\model\service\UserService;
	use app\community\model\service\VisitorInviteService;
    use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use think\App;
    use think\facade\Cache;
    use think\facade\Db;

	class OwnerController extends CommunityBaseController
	{
		public $houseVillageUserBindService;
		public $userService;
		public $houseNewPorptertyService;
		public $visitorInviteService;
		
		public function __construct (App $app)
		{
			parent::__construct($app);
			$this->houseVillageUserBindService = new HouseVillageUserBindService();
			$this->userService                 = new UserService();
			$this->houseNewPorptertyService    = new HouseNewPorpertyService();
			$this->visitorInviteService        = new VisitorInviteService();
			
		}

		public function index()
		{
			$village_id     = $this->adminUser['village_id'];
			$page           = $this->request->post('page',1);
			$property_id    = $this->adminUser['property_id'];
			
			if (empty($village_id)){
				return api_output(1002, [], '请先登录到小区后台！');
			}
			
			//判断是否启用了新版收费
			$isUseNewCharge = $this->houseNewPorptertyService->isUseNewCharge($property_id);
			
			//------------------------TODO 权限 、搜索 ------------------------------
			$where = [];
			$count = 0;
			//--------------------列表----------------------------------
			$where['village_id']  = $village_id;
            $where['status']  = [1,2];
            $where['type']  = [0,3];
			
			$count      = $this->houseVillageUserBindService->getUserCount($where);
			$fields     = "pigcms_id,village_id,uid,bind_number,usernum,name,phone,type,status,property_endtime,property_starttime";
			$userList   = [];
			$userList   = $this->houseVillageUserBindService->getHouseUserBindList($where,$fields,$page);
			if ($userList){
				$userList = $userList->toArray();
			}
			
			foreach ($userList as $k=>$item){
				//获取微信 openid
				if ($item['uid']) {
					$userInfo = $this->userService->getUserOne(['uid' => $item['uid']],'openid,wxapp_openid');
					$userList[$k]['openid'] = '';
					if($userInfo){
						$userList[$k]['openid'] = $userInfo->openid ? $userInfo->openid : $userInfo->wxapp_openid; 
					}
				}

				//新老板收费
//				if ($isUseNewCharge) {
					$userTimes = $this->visitorInviteService->getUserEndTime($item['pigcms_id'], ['isDelay' => 0]);
					if ( isset($userTimes['propertyEndTime'])) {
						$userList[$k]['property_endtime'] = date('Y-m-d',$userTimes['propertyEndTime']);
					} else {
						$userList[$k]['property_endtime'] = 0;
					}
					if (isset($userTimes['propertyStartTime'])) {
						$userList[$k]['property_starttime'] = date('Y-m-d',$userTimes['propertyStartTime']);
					} else {
						$userList[$k]['property_endtime'] = 0;
					}
//				}else{
//					$now_pay_info = $this->houseNewPorptertyService->getOldUserEndTime( ['bind_id' => $item['pigcms_id']],'end_time,start_time');
//					if (!$item['property_endtime']) {
//						$userList[$k]['property_endtime']   = $now_pay_info['end_time'];
//					}
//					if (!$item['property_starttime']) {
//						$userList[$k]['property_starttime']  = $now_pay_info['start_time'];
//					}
//				}
				
			}
			
			return  api_output(0, ['userList'=>$userList,'page'=>$page,'count'=>$count], 'SUCCESS');
			
		}
		
		/**
         * 人脸列表
         */
		public function getFaceList()
        {
            $param = [];
            $param['village_id'] = $this->adminUser['village_id'];
            $param['page'] = $this->request->post('page',1);
            $param['page_size'] = $this->request->post('pageSize',10);
            $param['name'] = $this->request->post('name','','trim');
            $param['phone'] = $this->request->post('phone','','trim');
            try{
                $list = (new HouseFaceImgService())->getFaceList($param);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return  api_output(0, $list, 'SUCCESS');
        }
        
        /**
         * 获取用户绑定的设备
         */
        public function getBindDevice()
        {
            $param = [];
            $param['village_id'] = $this->adminUser['village_id'];
            $param['checkId'] = $this->request->post('checkId',0);
            try{
                $list = (new OwnerService())->getBindDevice($param);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return  api_output(0, $list, 'SUCCESS');
        }
        
        /**
         * 可以下发的人脸设备列表
         */
        public function getFaceDeviceSyncList()
        {
            $param = [];
            $param['village_id'] = $this->adminUser['village_id'];
            $param['page'] = $this->request->post('page',1);
            $param['pageSize'] = $this->request->post('pageSize',10);
            $param['checkId'] = $this->request->post('checkId',0);
            try{
                $list = (new OwnerService())->getFaceDeviceSyncList($param);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return  api_output(0, $list, 'SUCCESS');
        }
        
        /**
         *  查询用户的人脸下发日志
         */
        public function getSynLogList()
        {
            $param = [];
            $param['village_id'] = $this->adminUser['village_id'];
            $param['page'] = $this->request->post('page',1);
            $param['pageSize'] = $this->request->post('pageSize',10);
            $param['pigcms_id'] = $this->request->post('pigcms_id',0,'trim');
            try{
                $list = (new OwnerService())->getSynLogList($param);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return  api_output(0, $list, 'SUCCESS');
        }
        
        /**
         * 绑定人脸设备
         */
        public function bindFaceDevice()
        {
            $param = [];
            $param['village_id'] = $this->adminUser['village_id'];
            $param['pigcms_id'] = $this->request->post('pigcms_id',[]);
            $param['device_ids'] = $this->request->post('device_ids',[]);
            $param['admin_id'] = $this->_uid;
            try{
                $list = (new OwnerService())->bindFaceDevice($param);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return  api_output(0, $list, 'SUCCESS'); 
        }
        /**
         * 批量绑定单元楼人脸设备
         */
        public function bindFaceDevices()
        {
            $param = [];
            $param['village_id'] = $this->adminUser['village_id'];
            $param['pigcms_ids'] = $this->request->post('pigcms_ids',[]);
            $param['admin_id'] = $this->_uid;
            try{
                $list = (new OwnerService())->bindFaceDevices($param);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return  api_output(0, $list, 'SUCCESS'); 
        }
        /**
         * 同步人脸
         */
        public function syncFace()
        {
            $param = [];
            $param['village_id'] = $this->adminUser['village_id'];
//            $param['all'] = $this->request->post('all',0,'trim');
            $param['pigcms_ids'] = $this->request->post('pigcms_ids',[]);
//            $param['device_ids'] = $this->request->post('device_ids',[]);
            try{
                $list = (new OwnerService())->syncFace($param);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return  api_output(0, $list, 'SUCCESS'); 
        }
        
        /**
         * 获取用户人脸信息
         */
        public function getFaceInfo()
        {
            $param['pigcms_id'] = $this->request->post('pigcms_id',[]);
            $param['village_id'] = $this->adminUser['village_id'];
            try{
                $list = (new OwnerService())->getFaceInfo($param);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return  api_output(0, $list, 'SUCCESS');
        }
        
        public function exportUserDataForLouYu()
        {
            $village_id = $this->adminUser['village_id'];
            if (empty($village_id)){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            $village_name = $this->adminUser['village_name'];
            $arr = $this->houseVillageUserBindService->exportUserDataForLouYu($village_id,$village_name);
            return api_output(0, $arr, 'success');
        }
        
	}