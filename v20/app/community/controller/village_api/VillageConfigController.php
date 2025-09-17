<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      代码功能描述
	 */

	namespace app\community\controller\village_api;

	use app\community\controller\CommunityBaseController;
	use app\community\model\service\HouseVillageService;

	class VillageConfigController extends CommunityBaseController
	{

		public $isPmwlcDomain   = false;
		public $isHzDomain      = false;

		public function initialize ()
		{
			parent::initialize();

			if (intval(cfg('cockpit'))){
				$this->isPmwlcDomain = true ;
			}

			if (intval(cfg('cockpit_hz'))){
				$this->isHzDomain = true ;
			}
		}

		/**
		 * 小区基本信息
		 * 参考
		 * Lib/Action/House/IndexAction.class.php
		 * @return \json
		 */
		public function baseConfig()
		{
			$village_id = $this->adminUser['village_id'];
			if (empty($village_id)){
				return api_output(1002, [], '请先登录到小区后台！');
			}
			$houseVillageService = new HouseVillageService();
			$fields = "`village_id`,`long`,`lat`,`work_time`,`property_name`,`property_phone`,`property_address`,`property_price`,`status`,`province_id`,
			`city_id`, `area_id`,`write_iccard`,`read_idcard`,`street_id`,`community_id`,`village_name`,`village_address`";
			$villageInfo = $houseVillageService->getHouseVillageInfo(['village_id'=>$village_id],$fields);

			$villageInfo['long'] = floatval($villageInfo['long']);
			$villageInfo['lat'] = floatval($villageInfo['lat']);
			$villageInfo['start_time']= $villageInfo['end_time']='';
			if(!empty($villageInfo['work_time'])){
				$work_time=explode('-',$villageInfo['work_time']);
				$villageInfo['start_time']=$work_time[0];
				$villageInfo['end_time']=$work_time[1];

			}

			return api_output(0,$villageInfo);
		}

		//获取小区信息数据
		public function getVillageInfo()
		{
			$village_id = $this->adminUser && isset($this->adminUser['village_id']) ? $this->adminUser['village_id']:0;
			if (empty($village_id)){
                $villageAboutInfo=array('village_id'=>0);
                return api_output(0,$villageAboutInfo);
			}
			$houseVillageService = new HouseVillageService();
			//获取小区扩展信息数据
		    $filed =  "village_id,info_id,plot_area,building_area,public_area,contract_time_start,contract_time_end,grab_order_time,green_area,garage_area,design_parking_area,remark,help_info";
			$villageAboutInfo = $houseVillageService->getHouseVillageInfoExtend(['village_id'=>$village_id],$filed);
			if (!empty($villageAboutInfo)){
				if ($villageAboutInfo['contract_time_start']>1){
					$villageAboutInfo['contract_time_start']=date('Y-m-d',$villageAboutInfo['contract_time_start']);
				}else{
					$villageAboutInfo['contract_time_start']='';
				}
				if ($villageAboutInfo['contract_time_end']>1){
					$villageAboutInfo['contract_time_end']=date('Y-m-d',$villageAboutInfo['contract_time_end']);
				}else{
					$villageAboutInfo['contract_time_end']='';
				}
			}
			if(isset($villageAboutInfo['grab_order_time']) && $villageAboutInfo['grab_order_time']<1){
				$villageAboutInfo['grab_order_time']=1;
			}
			return api_output(0,$villageAboutInfo);
		}
		
		/**
         * 获取配置信息
         */
		public function getConfig()
        {
            $village_id = $this->adminUser['village_id'];
            $configInfo = (new HouseVillageService())->getConfig($village_id);
            return api_output(0,$configInfo);
        }

	}