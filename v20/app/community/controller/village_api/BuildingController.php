<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      楼栋、单元、楼层管理控制器
	 */

	namespace app\community\controller\village_api;

	use app\common\model\service\config\ConfigCustomizationService;
    use app\common\model\service\UploadFileService;
	use app\community\controller\CommunityBaseController;
    use app\community\model\db\HouseVillageThirdConfig;
    use app\community\model\service\AreaService;
    use app\community\model\service\ChatSidebarService;
    use app\community\model\service\HardwareBrandService;
    use app\community\model\service\HouseNewCashierService;
    use app\community\model\service\HouseNewChargeService;
    use app\community\model\service\HouseVillageCheckauthApplyService;
    use app\community\model\service\HouseVillageCheckauthSetService;
    use app\community\model\service\HouseVillageConfigService;
	use app\community\model\service\HouseVillageService;
	use app\community\model\service\HouseVillageSingleService;
	use app\community\model\service\HouseVillageUserVacancyService;
    use app\community\model\service\HouseVillageVacancyIccardService;
    use app\community\model\service\HouseWorkerService;
	use app\community\model\service\KefuService;
    use app\community\model\service\NewBuildingService;
    use app\community\model\service\PropertyFrameworkService;
	use app\traits\CacheTypeTraits;
    use app\traits\CommonLogTraits;
    use app\traits\house\AdminLoginTraits;
    use app\traits\house\HouseTraits;
	use app\traits\ImportExcelTraits;
	use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
	use PhpOffice\PhpSpreadsheet\Exception;
	use PhpOffice\PhpSpreadsheet\IOFactory;
	use PhpOffice\PhpSpreadsheet\RichText\RichText;
    use app\community\model\service\Device\HikInfoVisionICommunityService;
	use think\facade\Cache;
	use think\facade\Config;
	class BuildingController extends CommunityBaseController
	{
		use HouseTraits,ImportExcelTraits,CacheTypeTraits;
        use CommonLogTraits;
        use AdminLoginTraits;
		 
		/**
		 * 楼栋列表
		 * @return \json
		 */
		public function index()
		{

			//TODO 后期可以考虑分页。一般一个小区楼栋也不会太多。
			$village_id = $this->adminUser['village_id'];
			if (empty($village_id)){
				return api_output(1002, [], '请先登录到小区后台！');
			}

			$houseVillageSignService = new HouseVillageSingleService();
            $where = [];
            $where[] = ['village_id','=',$village_id];
            $where[] = ['is_public_rental','=',0];
            $where[] = ['status','<>',4];
			$count = $houseVillageSignService->getBuildingCount($where);

			$field = "id,single_name,status,floor_num,vacancy_num,single_number,village_id,`sort`,measure_area,upper_layer_num,lower_layer_num,contract_time_start,contract_time_end";
			$oderby = ['sort'=>'DESC','id'=>'DESC'];
			$info = $houseVillageSignService->getList($where,$field,$oderby);
			$buiding = [];
            $jz_wsxdsj_net_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
            $is_func_cloudmrsqfw_special = (new ConfigCustomizationService())->getFuncCloudmrsqfwSpecial();
            if ($jz_wsxdsj_net_customized_open) {
                $communityQuality = true;
            } else {
                $communityQuality = false;
            }
			if ($info){
				$buiding = $info->toArray();
                //  allowEdit 是否支持【编辑】 allowDelete 是否支持【删除】 allowEditHousekeeper 是否支持【楼栋管家】 allowChangeStatus 是否支持【变更状态】
				foreach ($buiding as &$item){
					$item['contract_time_start'] = ($item['contract_time_start'] > 1)  ? date('Y-m-d',$item['contract_time_start']) : '-';
					$item['contract_time_end']   = ($item['contract_time_end'] > 1)    ? date('Y-m-d',$item['contract_time_end'])   : '-';
                    $item['allowEdit'] = true;
                    $item['allowDelete'] = true;
                    $item['allowEditHousekeeper'] = true;
                    $item['allowChangeStatus'] = true;
                    $item['allowChangeStatusTip'] = $item['allowChangeStatus']?'':'请前往智慧楼宇管理系统进行操作！';
                    $item['is_draw']=intval(cfg('cockpit')) ? true : false;
                    $item['allowCommunityQuality'] = $communityQuality;
				}
				//  allowImport 是否支持【导入楼栋/单元/楼层】 allowRoomImport 是否支持【前往导入房间】
				$data = [
					'count'    => $count,
					'building' => $buiding,
                    'allowImport' => true,
                    'allowRoomImport' => true,
				];

                $data['hik_info_vision_i_community']=0;
                $app_key = cfg('hik_info_vision_i_community_app_key');
                $app_secret = cfg('hik_info_vision_i_community_app_secret');
                if ($app_key && $app_secret) {
                    $data['hik_info_vision_i_community']=1;
                }
                $houseVillageService=new HouseVillageService();
                $houseVillageObj=$houseVillageService->getHouseVillageInfo(array('village_id' => $village_id),'*');
                $data['lilin_pf_village_room']=0;
                if($is_func_cloudmrsqfw_special && isset($houseVillageObj['lilin_pf_client_id']) && $houseVillageObj['lilin_pf_client_id'] && $houseVillageObj['lilin_pf_client_secret'] && $houseVillageObj['lilin_village_no']){
                    $data['lilin_pf_village_room']=1;
                }

                $data['role_housekeep']=$houseVillageService->checkPermissionMenu(112172,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
                $data['role_edit']=$houseVillageService->checkPermissionMenu(112173,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
                $data['role_del']=$houseVillageService->checkPermissionMenu(112174,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
                $data['role_import']=$houseVillageService->checkPermissionMenu(112175,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
                $data['role_del_all']=$houseVillageService->checkPermissionMenu(1113018,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
                return api_output(0,$data);

			}
			return api_output(1001, [], '异常访问！');
		}


		/**
		 * 需要传递 single_id 楼栋id
		 * 获取楼栋详细信息
		 * @return \json
		 */
		public function buildingInfo()
		{
			$village_id = $this->adminUser['village_id'];
			if (empty($village_id)){
				return api_output(1002, [], '请先登录到小区后台！');
			}

			$single_id = $this->request->post('single_id',0);
            $houseVillageInfoService = new HouseVillageService();
            $villageInfo = $houseVillageInfoService->getHouseVillageInfoExtend(['village_id'=>$village_id],'contract_time_end,contract_time_start');
			$houseVillageSignService = new HouseVillageSingleService();
			$where = [
				'village_id' => $village_id,
				'id'         => $single_id
			];
            $is_func_cloudmrsqfw_special = (new ConfigCustomizationService())->getFuncCloudmrsqfwSpecial();
			$info = $houseVillageSignService->getSingleInfo($where);
			$buiding = [];
			if ($info){
				$buiding = $info->toArray();

				$buiding['contract_time_start'] = ($buiding['contract_time_start'] > 1)  ? date('Y-m-d',$buiding['contract_time_start']) : '';
				$buiding['contract_time_end']   = ($buiding['contract_time_end'] > 1)    ? date('Y-m-d',$buiding['contract_time_end'])   : '';

                $houseVillageObj=$houseVillageInfoService->getHouseVillageInfo(array('village_id' => $village_id),'*');

				//合同时间，是楼栋的合同时间必须在小区设置的合同时间范围内
				if (!empty($villageInfo)){
					$buiding['village_contract_time_start'] = ($villageInfo['contract_time_start'] > 1)  ? date('Y-m-d',$villageInfo['contract_time_start']) : '';
					$buiding['village_contract_time_end']   = ($villageInfo['contract_time_end'] > 1)    ? date('Y-m-d',$villageInfo['contract_time_end'])   : '';
				}else{
					$buiding['village_contract_time_start'] = '';
					$buiding['village_contract_time_end']   = '';
				}
                $app_key = cfg('hik_info_vision_i_community_app_key');
                $app_secret = cfg('hik_info_vision_i_community_app_secret');
                $buiding['hik_info_vision_i_community']   = 0;
                if ($app_key && $app_secret) {
                    try {
                        $buiding['hik_info_vision_i_community'] = 1;
                        $hikInfoVisionICommunityService = new HikInfoVisionICommunityService();
                        $buiding['hik_info_community_single'] = $hikInfoVisionICommunityService->getCommunitySingle($village_id, $buiding['third_single_index_id']);
                    } catch (\Exception $e) {
                        $buiding['hik_info_community_single']=array();
                    }
                }
                $buiding['single_number_disabled']=false;
                if($is_func_cloudmrsqfw_special && isset($houseVillageObj['lilin_pf_client_id']) && $houseVillageObj['lilin_pf_client_id'] && $houseVillageObj['lilin_pf_client_secret'] && $houseVillageObj['lilin_village_no']){
                    $buiding['single_number_disabled']=true;
                }
				return api_output(0,$buiding);
			}
            
			return api_output(1001, [], '异常访问！');
		}

		/**
		 * 更新单个楼栋信息
		 * @return \json
		 */
		public function updateBuildingInfoByID()
		{
			$village_id = $this->adminUser['village_id'];
			if (empty($village_id)){
				return api_output(1002, [], '请先登录到小区后台！');
			}

			$single_id              = $this->request->post('single_id',0);
			$contract_time_end      = $this->request->post('contract_time_end',0);
			$contract_time_start    = $this->request->post('contract_time_start',0);
			$single_name            = $this->request->post('single_name','','trim');
			$status                 = $this->request->post('status',0);
			$sort                   = $this->request->post('sort',0);
			$measure_area           = $this->request->post('measure_area',0);
			$floor_num              = $this->request->post('floor_num',0);
			$vacancy_num            = $this->request->post('vacancy_num',0);
			$single_number          = $this->request->post('single_number',0);
			$upper_layer_num        = $this->request->post('upper_layer_num',0);
			$lower_layer_num        = $this->request->post('lower_layer_num',0);
			$single_keeper_name     = $this->request->post('single_keeper_name','');
			$single_keeper_phone    = $this->request->post('single_keeper_phone','');
			$single_keeper_head     = $this->request->post('single_keeper_head','');
            $long     = $this->request->post('long','');
            $lat     = $this->request->post('lat','');
            $third_single_index_id     = $this->request->post('third_single_index_id','','trim');
			if (empty($single_name)){
				return api_output(1001, [], '请填写楼栋名称！');
			}

			$houseVillageSingleService = new HouseVillageSingleService();

			$where = [];
			$where[] = ['village_id','=',$village_id];
			$where[] = ['single_name','=',$single_name];
			$where[] = ['id','<>',$single_id];
			$where[] = ['status','<>',4];
			$checkNameRepeat = $houseVillageSingleService->getSingleInfo($where);
			if ($checkNameRepeat){
				return api_output(1001, [], '楼栋名称【'.$single_name.'】已存在,请重新添加!！');
			}
            $contract_time_start = $contract_time_start ? strtotime($contract_time_start):0;
            $contract_time_end   = $contract_time_end ? strtotime($contract_time_end):0;
            if($contract_time_end>0 && $contract_time_start >= $contract_time_end){
                return api_output(1001, [], '合同开始时间不能大于合同结束时间！');
            }
			$houseVillageInfoService = new HouseVillageService();
			$villageInfo = $houseVillageInfoService->getHouseVillageInfoExtend(['village_id'=>$village_id],'contract_time_end,contract_time_start');
			//合同时间，是楼栋的合同时间必须在小区设置的合同时间范围内
			if (!empty($villageInfo)){

				if ($villageInfo['contract_time_start'] < 1 || $villageInfo['contract_time_end'] < 1){
					return api_output(1001, [], '请先去小区设置合同开始结束时间！');
				}

				if($contract_time_start>0 &&  $contract_time_start< $villageInfo['contract_time_start']){
					return api_output(1001, [], '楼栋合同开始时间不能小于小区合同开始时间！当前小区的合同开始时间【'.date('Y-m-d',$villageInfo['contract_time_start']).'】');
				}
				if($contract_time_end>0 && $contract_time_end > $villageInfo['contract_time_end']){
					return api_output(1001, [], '楼栋合同开始时间不能大于小区合同结束时间！当前小区的合同结束时间【'.date('Y-m-d',$villageInfo['contract_time_end']).'】');
				}
			}else{
				return api_output(1001, [], '请先去小区设置合同开始结束时间！');
			}
            if($contract_time_start>0 || $contract_time_end>0){
                //查询房间的物业服务时间是否大于设置合同时间
                $room_data=[];
                $room_data['village_id']    =   $village_id;
                $room_data['single_id']     =   $single_id;
                $room_data['contract_time_start'] = $contract_time_start;
                $room_data['contract_time_end']   = $contract_time_end;

                $checkRoomNowServiceTime = ( new HouseVillageUserVacancyService())->checkVacancyServiceTime($room_data);
                if ($checkRoomNowServiceTime['status'] != 1 ){
                    return api_output(1001, [], $checkRoomNowServiceTime['msg']);
                }
            }
			$villageConfig = (new HouseVillageConfigService())->getConfig(['village_id'=>$village_id]);
			$village_single_support_digit = intval($villageConfig['village_single_support_digit']) ? intval($villageConfig['village_single_support_digit']) : 2;
			if (!$village_single_support_digit || $village_single_support_digit!=3) {
				$village_single_support_digit = 2;
			}

			$check_number = "/^[0-9]+$/";
			if(!preg_match($check_number, $single_number)){
				return api_output(1001, [], '楼栋编号只允许数字！');
			}
			if((intval($single_number)<=0 || intval($single_number) > 99) && $village_single_support_digit < 3){
				return api_output(1001, [], '楼栋编号必须为1-99的数字！');
			}
			if (strlen($single_number)>2 && $village_single_support_digit < 3 ) {
				return api_output(1001, [], '楼栋编号只允最多2位数字！');
			} else if (strlen($single_number)<$village_single_support_digit) {
				// 不足2位 补足2位
				$single_number = str_pad($single_number,$village_single_support_digit,"0",STR_PAD_LEFT);
			}

			$where = [];
			$where[] = ['village_id','=',$village_id];
			$where[] = ['single_number','=',$single_number];
			$where[] = ['id','<>',$single_id];
			$where[] = ['status','<>',4];
			$checkNameRepeat = $houseVillageSingleService->getSingleInfo($where);
			if ($checkNameRepeat && $checkNameRepeat->id != $single_id) {
				return api_output(1001, [], '该楼栋编号已经存在！');
			}

			$data['single_number']  = $single_number;
			$data['single_name']    = $single_name;
			$data['status']         = $status;
			$data['sort']           = $sort;
			$data['measure_area']   = $measure_area;
			$data['floor_num']      = $floor_num;
			$data['vacancy_num']    = $vacancy_num;
			$data['upper_layer_num']     = $upper_layer_num;
			$data['lower_layer_num']     = $lower_layer_num;
			$data['single_keeper_name']  = $single_keeper_name;
			$data['single_keeper_phone'] = $single_keeper_phone;
			$data['single_keeper_head']  = $single_keeper_head;
			$data['contract_time_start'] = $contract_time_start;
			$data['contract_time_end']   = $contract_time_end;
            $data['long']   = $long ;
            $data['lat']   =  $lat;
            $data['update_time']   = time();
            $app_key = cfg('hik_info_vision_i_community_app_key');
            $app_secret = cfg('hik_info_vision_i_community_app_secret');
            $is_hik_info_vision_i_community=0;
            $old_third_single_index_id='';
            if($app_key && $app_secret){
                $is_hik_info_vision_i_community=1;
                $data['third_single_index_id']=$third_single_index_id;
                $where = [
                    'village_id' => $village_id,
                    'id'         => $single_id
                ];
                $oldSingleInfo=$houseVillageSingleService->getSingleInfo($where);
                if($oldSingleInfo && isset($oldSingleInfo['third_single_index_id'])){
                    $old_third_single_index_id=$oldSingleInfo['third_single_index_id'];
                }
                try {
                    if($third_single_index_id && $old_third_single_index_id!=$third_single_index_id){
                        $hikInfoVisionICommunityService= new HikInfoVisionICommunityService();
                        $isBinded=$hikInfoVisionICommunityService->checkSingleIsBinded($third_single_index_id,$single_id);
                        if($isBinded){
                            return api_output(1001, [], '该海康智慧社区楼栋已被其他楼栋先绑定了，请选择其他楼栋绑定！');
                        }
                    }
                } catch (\Exception $e) {
                    return api_output_error(-1, $e->getMessage());
                }
            }

			$where = [
				'village_id' => $village_id,
				'id'         => $single_id
			];
			$saveResult = $houseVillageSingleService->saveSingleInfo($where,$data);

			if ($saveResult){
				//TODO 如果是修改楼栋编号成功需要触发编号其楼栋下房屋编号和对应房屋人员编号变动 ---> 放入队列执行【如果是基于新版之后，其实不用作这层操作】
				//如果需要：cms/Lib/Model/House_village_user_vacancyModel.class.php --> change_number()
                $this->cacheTagKey = 'village:cache:'.$village_id;
                Cache::tag($this->cacheTagKey)->clear();
                if($is_hik_info_vision_i_community==1 && $old_third_single_index_id!=$third_single_index_id){
                    $hikInfoVisionICommunityService= new HikInfoVisionICommunityService();
                    $hikInfoVisionICommunityService->handleHikInfoVisionCommunityVacancy(['village_id'=>$village_id,'old_third_single_index_id'=>$old_third_single_index_id,'third_single_index_id'=>$third_single_index_id,'single_id'=>$single_id,'handle_type'=>'handleSingleVacancy']);
                }
				return api_output(0, [], '信息保存成功！');
			}else{
				return api_output(101, [], '信息保存失败！');
			}

		}

		//获取楼栋管家详细页面数据
		public function getBuildingButler()
		{
			$village_id = $this->adminUser['village_id'];
			$property_id = $this->adminUser['property_id'];

			if (empty($village_id)){
				return api_output(1002, [], '请先登录到小区后台！');
			}
			$single_id              = $this->request->post('single_id',0);

			$where = [
				'village_id' => $village_id,
				'single_id' => $single_id,
				'floor_id' => 0,
			];
			$buldingButler = (new KefuService())->getBuildingButlerInfo($where);
			$buldingButlerBindList = [];
			if ($buldingButler && $buldingButler->work_arr){
				$work_arr = explode(',',$buldingButler->work_arr);
				if ($work_arr) {

					$where_workers = [
						'wid' => $work_arr,
						'status' => 1
					];
					$buldingButlerBindList = (new HouseWorkerService())->getWorker($where_workers,"wid,name");
				}
                if($buldingButler['qy_qrcode']){
                    $buldingButler['qy_qrcode']=replace_file_domain($buldingButler['qy_qrcode']);
                }
                if($buldingButler['template_url']){
                    $buldingButler['template_url']=replace_file_domain($buldingButler['template_url']);
                }
                if($buldingButler['effect_img']){
                    $buldingButler['effect_img']=replace_file_domain($buldingButler['effect_img']);
                }
			}

			//小区小所有工作人员
			$enterpriseWxCorpid = (new PropertyFrameworkService())->getEnterpriseWxBind(['bind_id'=>$property_id,'bind_type'=>0],'corpid');
			$enterprise_wx_corpid = '';
			$where = [];
			if ($enterpriseWxCorpid){
				$where[] = ['village_id','=',$village_id];
				$where[] = ['qy_status','=',1];
				$where[] = ['qy_id','<>',''];
				$where[] = ['status','=',1];
				$enterprise_wx_corpid = $enterpriseWxCorpid->corpid;
			}else{
				$where = [
					'village_id' => $village_id,
					'status'     => 1,
					'is_del'     => 0
				];
			}
            $isAihorseCustomizedJudge = (new ConfigCustomizationService())->getAihorseCustomizedJudge();
            if($isAihorseCustomizedJudge>0){
                $where = [
                    'village_id' => $village_id,
                    'status'     => 1,
                    'is_del'     => 0
                ];
            }
			$buldingButlerList = (new HouseWorkerService())->getWorker($where);

			$data = [
				'buldingButler'         => $buldingButler,
				'buldingButlerBindList' => $buldingButlerBindList,
				'buldingButlerList'     => $buldingButlerList,
				'corpid'                => $enterprise_wx_corpid,
				'qyhelp_url'            => cfg('site_url').'/static/file/village/information/qyhelp.pdf'
			];
			return api_output(0, $data, '查询成功！');
		}


		//添加或者更新楼栋管家
		public function saveBuildingButler()
		{
			$village_id = $this->adminUser['village_id'];
			$property_id = $this->adminUser['property_id'];

			if (empty($village_id)){
				return api_output(1002, [], '请先登录到小区后台！');
			}
			$single_id              = $this->request->post('single_id',0);
			$housekeeper_id         = $this->request->post('housekeeper_id',0); //编辑的时候不需要传也是可以
			$is_kefu                = $this->request->post('is_kefu',0);
			$welcome_tip            = $this->request->post('welcome_tip','','trim');
			$work_arr               = $this->request->post('work_arr','');
			$qy_qrcode              = $this->request->post('qy_qrcode','');
			$template_type          = $this->request->post('template_type',0,'intval');
			$template_url           = $this->request->post('template_url',0,'trim');
			$effect_img             = $this->request->post('effect_img',0,'trim');

			if ($this->request->has('housekeeper_id') && $housekeeper_id > 0){ //编辑
				$where = [
					'village_id'     => $village_id,
					'housekeeper_id' => $housekeeper_id,

				];
				$buldingButler = (new KefuService())->getBuildingButlerInfo($where);
				if (empty($buldingButler)){
					return api_output(1001, [], '非法操作对象！');
				}
			}
			$data['village_id']     = $village_id;
			$data['property_id']    = $property_id;
			$data['single_id']      = $single_id;
			$data['is_kefu']        = $is_kefu;
			$data['work_arr']       = $work_arr ? implode(',',$work_arr) : '';
			$data['welcome_tip']    = $welcome_tip;
			$data['qy_qrcode']      = $qy_qrcode;
			$data['template_type']  = $template_type;
			$data['template_url']   = $template_url;
			$data['effect_img']     = $effect_img;

			if ($this->request->has('housekeeper_id') && $housekeeper_id > 0) {
				$data['last_time'] = time();
				$where = [
					'housekeeper_id' => $buldingButler->housekeeper_id,
				];
				$result = (new KefuService())->saveBuildingButlerInfo($where,$data);
			} else {
				$data['add_time'] = time();
				$result = (new KefuService())->addBuildingButlerInfo($data);
			}
			if (!$result){
				return api_output(101, [], '楼栋管家更改或添加失败！');
			}
			return api_output(0, [], '修改成功！');
		}


		/**
		 * 软删除楼栋信息
		 * @return \json
		 */
		public function deleteBuilding()
		{
			$village_id = $this->adminUser['village_id'];
			if (empty($village_id)){
				return api_output(1002, [], '请先登录到小区后台！');
			}

			$single_id = $this->request->post('single_id',0);
			$is_all = $this->request->post('is_all',0);//是否一键删除
            $where = [
                'village_id'    => $village_id,
                'id'     => $single_id,
            ];
            $houseVillageSignService = new HouseVillageSingleService();
            $singleInfo=$houseVillageSignService->getSingleInfo($where,'*');
            if($singleInfo && !$singleInfo->isEmpty()){
                $singleInfo=$singleInfo->toArray();
            }else{
                $singleInfo=array();
            }
            if($is_all == 1){//一键删除指定楼栋下所有单元、楼层、房间数据以及用户 未支付订单作废
                $del = $houseVillageSignService->deleteSingleAllInfo($village_id,$single_id);
                if($del['error']){
                    return api_output(1003, [], $del['msg']);
                }
                $isCloudmrsqfwSpecial = (new ConfigCustomizationService())->getFuncCloudmrsqfwSpecial();
                if($isCloudmrsqfwSpecial  && $singleInfo['third_single_source']=='lilin_pf' &&  $singleInfo['third_single_index_id']){
                    $liLinDevAccessApiService= new \app\community\model\service\Device\LiLinDevAccessApiService();
                    $liLinDevAccessApiService->deleteBuilding(array('structId'=>$singleInfo['third_floor_index_id']*1,'type'=>3),$village_id);
                }
                $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
                $queuData = [
                    'logData' => [
                        'tbname' => '楼栋表、单元表、楼层表、房间表、用户绑定小区表',
                        'table'  => 'house_village_single、house_village_floor、house_village_layer、house_village_user_vacancy、house_village_user_bind',
                        'client' => '小区后台',
                        'trigger_path' => '楼栋管理->删除楼栋所有数据',
                        'trigger_type' => $this->getDeleteName(),
                        'addtime'      => time(),
                        'village_id'   => $village_id,
                        'op_id'        => $parseInfo['op_id'],
                        'op_type'      => $parseInfo['op_type'],
                        'op_name'      => $parseInfo['op_name'],
                    ],
                    'newData' => ['single_id'=>0],
                    'oldData' => ['single_id'=>$single_id],
                ];
                $this->laterLogInQueue($queuData);

                return api_output(0, [], '删除楼栋成功！');
            }
            
			$where = [];
			$where[] = ['village_id','=',$village_id];
			$where[] = ['single_id','=',$single_id];
			$where[] = ['status','<>',4];
			$checkHasFloor  = $houseVillageSignService->getFloorInfo($where,'floor_id');
			if ($checkHasFloor){
				return api_output(1003, [], '该楼栋下存在单元不可删除，请先删除单元！');
			}

			$data = ['status' => 4];
            $app_key = cfg('hik_info_vision_i_community_app_key');
            $app_secret = cfg('hik_info_vision_i_community_app_secret');
            if ($app_key && $app_secret) {
                $data['third_single_index_id']='';
            }
			$houseVillageSignService->softDeleteBuilding($where,$data);

            $isCloudmrsqfwSpecial = (new ConfigCustomizationService())->getFuncCloudmrsqfwSpecial();
            if($isCloudmrsqfwSpecial  && $singleInfo['third_single_source']=='lilin_pf' &&  $singleInfo['third_single_index_id']){
                $liLinDevAccessApiService= new \app\community\model\service\Device\LiLinDevAccessApiService();
                $liLinDevAccessApiService->deleteBuilding(array('structId'=>$singleInfo['third_floor_index_id']*1,'type'=>3),$village_id);
            }

            $this->cacheTagKey = 'village:cache:'.$village_id;
            Cache::tag($this->cacheTagKey)->clear();
            if ($app_key && $app_secret) {
                $hikInfoVisionICommunityService = new HikInfoVisionICommunityService();
                $tmpCommunityRoom = ['village_id' => $village_id, 'handle_type' => 'handleSingleVacancy'];
                $tmpCommunityRoom['single_id'] = $single_id;
                $tmpCommunityRoom['third_single_index_id'] = $singleInfo && isset($singleInfo['third_single_index_id']) ? $singleInfo['third_single_index_id']:'';
                $hikInfoVisionICommunityService->unbindHikInfoVisionCommunityVacancy($village_id, $tmpCommunityRoom);
            }
			return api_output(0, [], '删除楼栋成功！');
		}

		public function updateBuildingStatus()
		{
			$village_id = $this->adminUser['village_id'];
			if (empty($village_id)){
				return api_output(1002, [], '请先登录到小区后台！');
			}
			$single_id = $this->request->post('single_id',0);
			$status    = $this->request->post('status',0,'intval');

			$houseVillageSingleService = new HouseVillageSingleService();
			$where = [
				'village_id'    => $village_id,
				'id'     => $single_id,
			];
			$checkNameRepeat = $houseVillageSingleService->getSingleInfo($where);
			if (empty($checkNameRepeat)) {
				return api_output(1001, [], '非法操作！');
			}

			$data = [
				'status' => $status
			];

			$houseVillageSingleService->saveSingleInfo(['id'=>$checkNameRepeat->id],$data);
            $this->cacheTagKey = 'village:cache:'.$village_id;
            Cache::tag($this->cacheTagKey)->clear();
			return api_output(0, [], '修改成功！');
		}


        /**
         * 获取对应小区单元或者对应楼栋单元
         * @param integer single_id 楼栋id 选传
         * @return \json
         */
		public function unitFloor()
		{
            $village_id = $this->adminUser['village_id'];
            if (empty($village_id)){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            $houseVillageInfoService = new HouseVillageService();
            $single_id = $this->request->post('single_id',0);
            $whereFloor = [];
            $whereFloor[] = ['village_id','=',$village_id];
            $whereFloor[] = ['is_public_rental','=',0];
            $whereFloor[] = ['status','<>',4];
            if ($single_id) {
                $whereFloor[] = ['single_id','=',$single_id];
            }
            $count = $houseVillageInfoService->getFloorCount($whereFloor);
            $field = "floor_id,village_id,floor_name,status,single_id";
            $oderby = ['status'=>'DESC', 'sort'=>'DESC','floor_id'=>'DESC'];
            $list = $houseVillageInfoService->getFloorList($whereFloor,$field,$oderby);
            $unit = [];
            $data = [
                'count'    => $count,
                'unit' => $unit,
            ];
            $houseVillageSingleService = new HouseVillageSingleService();
            if ($single_id) {
                $whereSingle = [];
                $whereSingle[] = ['village_id','=',$village_id];
                $whereSingle[] = ['id','=',$single_id];
                $fieldSingle = 'id,single_name';
                $singleInfo = $houseVillageSingleService->getSingleInfo($whereSingle,$fieldSingle);
                if (isset($singleInfo['single_name'])) {
                    $data['single_name'] = $singleInfo['single_name'];
                    $data['single_name'] = $this->traitAutoFixLouDongTips($data['single_name'],true,$village_id);
                }
            }
            $isAihorseCustomizedJudge = (new ConfigCustomizationService())->getAihorseCustomizedJudge();
            $data['have_filter_data']=array('is_filter'=>0);
            if($isAihorseCustomizedJudge){
                $data['have_filter_data']['is_filter']=1;
                $optionType = (new NewBuildingService())->getRoomOptionType($village_id,0);
                $data['have_filter_data']['room_type_list']=$optionType['room_type_list'];
                $data['have_filter_data']['sell_status_list']=$optionType['sell_status_list'];
            }
            if ($list){
                $unit = $list->toArray();
                //  allowEdit 是否支持【编辑】 allowDelete 是否支持【删除】
                foreach ($unit as &$item){
                    $item['allowEdit'] = true;
                    $item['allowDelete'] = true;
                    $item['floor_name'] = $this->traitAutoFixDanyuanTips($item['floor_name'],true,$village_id);
                }
                $data['unit'] = $unit;
            }
            return api_output(0,$data);
		}


        /**
         * 获取对应小区单元的楼层和关联的房屋信息  整合返回
         * @param integer floor_id 单元id 选传
         * @return \json
         */
		public function floorLayerRooms() {
            $village_id = $this->adminUser['village_id'];
            if (empty($village_id)){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            $floor_id = $this->request->post('floor_id',0);
            $house_type = $this->request->post('house_type',0,'intval');
            $sell_status = $this->request->post('sell_status',0,'intval');
            $property_id = $this->adminUser['property_id'];
            $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
            $param = [
                'village_id' => $village_id,
                'property_id' => $property_id,
                'floor_id' => $floor_id,
                'house_type'=>$house_type,
                'sell_status'=>$sell_status,
            ];
            try{
                $data = $service_house_village_user_vacancy->floorLayerRooms($param);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$data);
        }


        /**
         * 需要传递 floor_id 单元id
         * 获取单元详细信息
         * @return \json
         */
        public function floorInfo()
        {
            $village_id = $this->adminUser['village_id'];
            if (empty($village_id)){
                return api_output(1002, [], '请先登录到小区后台！');
            }

            $floor_id = $this->request->post('floor_id',0);
            $houseVillageSignService = new HouseVillageSingleService();
            $houseVillageService=new HouseVillageService();
            $whereFloor = [
                'village_id' => $village_id,
                'floor_id'   => $floor_id
            ];
            $is_func_cloudmrsqfw_special = (new ConfigCustomizationService())->getFuncCloudmrsqfwSpecial();
            $info = $houseVillageSignService->getFloorInfo($whereFloor);
            $floor = [];
            if ($info && !$info->isEmpty()){
                $floor = $info->toArray();
                $houseVillageObj=$houseVillageService->getHouseVillageInfo(array('village_id' => $village_id),'*');
                $floor['write_iccard']=0;
                if($houseVillageObj && !$houseVillageObj->isEmpty()){
                    $floor['write_iccard']=$houseVillageObj['write_iccard'];
                }
                $app_key = cfg('hik_info_vision_i_community_app_key');
                $app_secret = cfg('hik_info_vision_i_community_app_secret');
                $floor['hik_info_vision_i_community']   = 0;
                $thirdConfigInfo = (new HouseVillageThirdConfig())->getCloudIntercomConfig([['village_id','=',$village_id],['type','in',['chinapopin_sichuan','ezc_chinapopin']]],'village_num');
                $floor['show_dwid'] = $thirdConfigInfo ? 1 : 0;
                if ($app_key && $app_secret) {
                    try {
                        $floor['hik_info_vision_i_community'] = 1;
                        $hikInfoVisionICommunityService = new HikInfoVisionICommunityService();
                        $floor['hik_info_community_floor'] = $hikInfoVisionICommunityService->getCommunitySingleFloor($village_id, $floor['single_id'],$info['third_floor_index_id']);
                    } catch (\Exception $e) {
                        $floor['hik_info_community_floor']=array();
                    }
                }
                $floor['floor_number_disabled']=false;
                if($is_func_cloudmrsqfw_special && isset($houseVillageObj['lilin_pf_client_id']) && $houseVillageObj['lilin_pf_client_id'] && $houseVillageObj['lilin_pf_client_secret'] && $houseVillageObj['lilin_village_no']){
                    $floor['floor_number_disabled']=true;
                }

                return api_output(0,$floor);
            }
            return api_output(1001, [], '异常访问！');
        }


        /**
         * 更新单个单元信息
         * @return \json
         */
        public function updateFloorInfoByID() {
            $village_id = $this->adminUser['village_id'];
            if (empty($village_id)){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            $floor_id               = $this->request->post('floor_id',0);
            $floor_name             = $this->request->post('floor_name','','trim');
            $floor_number           = $this->request->post('floor_number',0);
            $sort                   = $this->request->post('sort',0);
            $long_lat               = $this->request->post('long_lat');
            $door_control           = $this->request->post('door_control','','trim');
            $status                 = $this->request->post('status',-1);
            $dwid                   = $this->request->post('dwid','','trim');
            $floor_area             = $this->request->post('floor_area',0);
            $house_num              = $this->request->post('house_num',0);
            $floor_upper_layer_num  = $this->request->post('floor_upper_layer_num',0);
            $floor_lower_layer_num  = $this->request->post('floor_lower_layer_num',0);
            $start_layer_num        = $this->request->post('start_layer_num',0);
            $end_layer_num          = $this->request->post('end_layer_num',0);
            $floor_keeper_name      = $this->request->post('floor_keeper_name','');
            $floor_keeper_phone     = $this->request->post('floor_keeper_phone','');
            $floor_keeper_head      = $this->request->post('floor_keeper_head','');
            $third_floor_index_id      = $this->request->post('third_floor_index_id','','trim');
            if (empty($floor_name)){
                return api_output(1001, [], '请填写单元名称！');
            }
            $houseVillageSignService = new HouseVillageSingleService();
            $where = [];
            $where[] = ['village_id','=',$village_id];
            $where[] = ['floor_id','=',$floor_id];
            $updateFloor = $houseVillageSignService->getFloorInfo($where);
            if (!$updateFloor || !isset($updateFloor['floor_id'])){
                return api_output(1001, [], '操作对象不存在！');
            }
            $single_id = $updateFloor['single_id'];
            $where = [];
            $where[] = ['village_id','=',$village_id];
            $where[] = ['single_id','=',$single_id];
            $where[] = ['floor_name','=',$floor_name];
            $where[] = ['floor_id','<>',$floor_id];
            $where[] = ['status','<>',4];
            $checkNameRepeat = $houseVillageSignService->getFloorInfo($where);
            if ($checkNameRepeat&&isset($checkNameRepeat['floor_id'])){
                return api_output(1001, [], '同楼栋该单元名称【'.$floor_name.'】已存在,请重新添加！');
            }

            $check_number = "/^[0-9]+$/";
            if(!preg_match($check_number, $floor_number)){
                return api_output(1001, [], '单元编号只允许数字！');
            }
            if((intval($floor_number)<=0 || intval($floor_number) > 99)){
                return api_output(1001, [], '单元编号必须为1-99的数字！');
            }
            if (strlen($floor_number)>2) {
                return api_output(1001, [], '单元编号只允最多2位数字！');
            } elseif (strlen($floor_number)<2) {
                // 不足2位 补足2位
                $floor_number = str_pad($floor_number,2,"0",STR_PAD_LEFT);
            }
            $where = [];
            $where[] = ['village_id','=',$village_id];
            $where[] = ['single_id','=',$single_id];
            $where[] = ['floor_number','=',$floor_number];
            $where[] = ['floor_id','<>',$floor_id];
            $where[] = ['status','<>',4];
            $checkNameRepeat = $houseVillageSignService->getFloorInfo($where);
            if ($checkNameRepeat && isset($checkNameRepeat['floor_id'])) {
                return api_output(1001, [], '同楼栋该单元编号已经存在！');
            }

            $data = [];
            $data['floor_number']   = $floor_number;
            $data['floor_name']     = $floor_name;
            if ($status!=-1) {
                $data['status']     = $status;
            }
            $data['sort']           = $sort;
            if ($long_lat) {
                $long_lat = explode(',',$long_lat);
                if ($long_lat && $long_lat[0] && $long_lat[1]) {
                    $data['long'] = floatval($long_lat[0]);
                    $data['lat'] = floatval($long_lat[1]);
                }
            }

            $data['dwid']           = $dwid ? $dwid : '';
            $data['floor_area']     = $floor_area ? $floor_area : '';
            $data['house_num']      = $house_num ? $house_num : '';
            $data['floor_upper_layer_num']     = $floor_upper_layer_num ? $floor_upper_layer_num : '';
            $data['floor_lower_layer_num']     = $floor_lower_layer_num ? $floor_lower_layer_num : '';
            $data['start_layer_num']           = $start_layer_num ? $start_layer_num : '';
            $data['end_layer_num']             = $end_layer_num ? $end_layer_num : '';
            $data['floor_keeper_name']         = $floor_keeper_name ? $floor_keeper_name : '';
            $data['floor_keeper_phone']        = $floor_keeper_phone ? $floor_keeper_phone : '';
            $data['floor_keeper_head']         = $floor_keeper_head ? $floor_keeper_head : '';
            $app_key = cfg('hik_info_vision_i_community_app_key');
            $app_secret = cfg('hik_info_vision_i_community_app_secret');
            //为统一前端 只更新以下字段
            $data = [
                'floor_number'=>$floor_number,
                'floor_name'=>$floor_name,
                'update_time'=>time(),
                'dwid'=>$data['dwid']
            ];
            $houseVillageService=new HouseVillageService();
            $houseVillageObj=$houseVillageService->getHouseVillageInfo(array('village_id' => $village_id),'write_iccard');
            if($houseVillageObj && !$houseVillageObj->isEmpty() && $houseVillageObj['write_iccard']>0){
                $data['door_control']   = $door_control ? $door_control : '';
            }
            $is_hik_info_vision_i_community=0;
            $old_third_floor_index_id='';
            if($app_key && $app_secret){
                $is_hik_info_vision_i_community=1;
                $data['third_floor_index_id']=$third_floor_index_id;
                if($updateFloor && isset($updateFloor['third_floor_index_id'])){
                    $old_third_floor_index_id=$updateFloor['third_floor_index_id'];
                }
                try {
                    if($third_floor_index_id && $old_third_floor_index_id!=$third_floor_index_id){
                        $hikInfoVisionICommunityService= new HikInfoVisionICommunityService();
                        $isBinded=$hikInfoVisionICommunityService->checkFloorIsBinded($village_id,$third_floor_index_id,$floor_id);
                        if($isBinded){
                            return api_output(1001, [], '该海康智慧社区单元已被其他楼栋先绑定了，请选择其他单元绑定！');
                        }
                    }
                } catch (\Exception $e) {
                    return api_output_error(-1, $e->getMessage());
                }
            }
            $where = [
                'village_id' => $village_id,
                'floor_id'         => $floor_id
            ];
            $saveResult = $houseVillageSignService->saveFloorInfo($where,$data);

            if ($saveResult){
                //TODO 如果是修改楼栋编号成功需要触发编号其楼栋下房屋编号和对应房屋人员编号变动 ---> 放入队列执行【如果是基于新版之后，其实不用作这层操作】
                //如果需要：cms/Lib/Model/House_village_user_vacancyModel.class.php --> change_number()
                $this->cacheTagKey = 'village:cache:'.$village_id;
                Cache::tag($this->cacheTagKey)->clear();
                if($is_hik_info_vision_i_community==1 && $old_third_floor_index_id!=$third_floor_index_id){
                    $hikInfoVisionICommunityService= new HikInfoVisionICommunityService();
                    $hikInfoVisionICommunityService->handleHikInfoVisionCommunityVacancy(['village_id'=>$village_id,'old_third_floor_index_id'=>$old_third_floor_index_id,'third_floor_index_id'=>$third_floor_index_id,'single_id'=>$single_id,'floor_id'=>$floor_id,'handle_type'=>'handleFloorVacancy']);
                }
                return api_output(0, [], '信息保存成功！');
            }else{
                return api_output(101, [], '信息保存失败！');
            }
        }


        /**
         * 软删除单元信息
         * @return \json
         */
        public function deleteFloor()
        {
            $village_id = $this->adminUser['village_id'];
            if (empty($village_id)){
                return api_output(1002, [], '请先登录到小区后台！');
            }

            $floor_id = $this->request->post('floor_id',0);

            $houseVillageSignService = new HouseVillageSingleService();
            $where = [];
            $where[] = ['village_id','=',$village_id];
            $where[] = ['floor_id','=',$floor_id];
            $where[] = ['status','<>',4];
            $checkHasLayer  = $houseVillageSignService->getLayerInfo($where,'id');
            if ($checkHasLayer&&isset($checkHasLayer['id'])){
                return api_output(1003, [], '该单元下存在楼层不可删除，请先删除楼层！');
            }

            $where = [
                'village_id'   => $village_id,
                'floor_id'     => $floor_id,
            ];
            $floorInfo=$houseVillageSignService->getFloorInfo($where);
            if($floorInfo && !$floorInfo->isEmpty()){
                $floorInfo=$floorInfo->toArray();
            }else{
                $floorInfo=array();
            }
            $data = ['status' => 4];
            $app_key = cfg('hik_info_vision_i_community_app_key');
            $app_secret = cfg('hik_info_vision_i_community_app_secret');
            if($app_key && $app_secret){
                $data['third_floor_index_id']='';
            }
            $houseVillageSignService->softDeleteFloor($where,$data);
            $isCloudmrsqfwSpecial = (new ConfigCustomizationService())->getFuncCloudmrsqfwSpecial();
            if($isCloudmrsqfwSpecial  && $floorInfo['third_floor_source']=='lilin_pf' &&  $floorInfo['third_floor_index_id']){
                $liLinDevAccessApiService= new \app\community\model\service\Device\LiLinDevAccessApiService();
                $liLinDevAccessApiService->deleteBuilding(array('structId'=>$floorInfo['third_floor_index_id']*1,'type'=>4),$village_id);
            }
            $this->cacheTagKey = 'village:cache:'.$village_id;
            Cache::tag($this->cacheTagKey)->clear();
            if ($app_key && $app_secret) {
                $hikInfoVisionICommunityService = new HikInfoVisionICommunityService();
                $tmpCommunityRoom = ['village_id' => $village_id, 'handle_type' => 'handleFloorVacancy'];
                $tmpCommunityRoom['single_id'] = $floorInfo && isset($floorInfo['single_id']) ? $floorInfo['single_id']:0;
                $tmpCommunityRoom['floor_id'] = $floor_id;
                $tmpCommunityRoom['third_floor_index_id'] = $floorInfo && isset($floorInfo['third_floor_index_id']) ? $floorInfo['third_floor_index_id']:'';
                $hikInfoVisionICommunityService->unbindHikInfoVisionCommunityVacancy($village_id, $tmpCommunityRoom);
            }
            return api_output(0, [], '删除单元成功！');
        }



        /**
         * 需要传递 layer_id 楼层id
         * 获取单元详细信息
         * @return \json
         */
        public function layerInfo()
        {
            $village_id = $this->adminUser['village_id'];
            if (empty($village_id)){
                return api_output(1002, [], '请先登录到小区后台！');
            }

            $layer_id = $this->request->post('layer_id',0);
            $houseVillageSignService = new HouseVillageSingleService();

            $whereLayer = [
                'village_id' => $village_id,
                'id'         => $layer_id
            ];

            $info = $houseVillageSignService->getLayerInfo($whereLayer);
            $layer = [];
            if ($info && !$info->isEmpty()){
                $layer = $info->toArray();
                if($layer['status']==1){
                    $layer['status']=2;
                }
                return api_output(0,$layer);
            }
            return api_output(1001, [], '异常访问！');
        }

        /**
         * 更新单个楼层信息
         * @return \json
         */
        public function updatelayerInfoByID() {
            $village_id = $this->adminUser['village_id'];
            if (empty($village_id)){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            $layer_id               = $this->request->post('layer_id',0);
            $layer_name             = $this->request->post('layer_name','','trim');
            $layer_number           = $this->request->post('layer_number',0);
            $sort                   = $this->request->post('sort',0);
            $status                 = $this->request->post('status',-1,'intval');

            if (empty($layer_name)){
                return api_output(1001, [], '请填写楼层名称！');
            }
            $houseVillageSignService = new HouseVillageSingleService();
            $where = [];
            $where[] = ['village_id','=',$village_id];
            $where[] = ['id','=',$layer_id];
            $updateLayer = $houseVillageSignService->getLayerInfo($where);
            if (!$updateLayer || !isset($updateLayer['id'])){
                return api_output(1001, [], '操作对象不存在！');
            }
            $floor_id = $updateLayer['floor_id'];
            $where = [];
            $where[] = ['village_id','=',$village_id];
            $where[] = ['floor_id','=',$floor_id];
            $where[] = ['layer_name','=',$layer_name];
            $where[] = ['id','<>',$layer_id];
            $where[] = ['status','<>',4];
            $checkNameRepeat = $houseVillageSignService->getLayerInfo($where);
            if ($checkNameRepeat&&isset($checkNameRepeat['id'])){
                return api_output(1001, [], '同单元楼层名称【'.$layer_name.'】已存在,请重新添加！');
            }

            $check_number = "/^[0-9]+$/";
            if(!preg_match($check_number, $layer_number)){
                return api_output(1001, [], '楼层编号只允许数字！');
            }
            if((intval($layer_number)<=0 || intval($layer_number) > 99)){
                return api_output(1001, [], '楼层编号必须为1-99的数字！');
            }
            if (strlen($layer_number)>2) {
                return api_output(1001, [], '单元编号只允最多2位数字！');
            } elseif (strlen($layer_number)<2) {
                // 不足2位 补足2位
                $layer_number = str_pad($layer_number,2,"0",STR_PAD_LEFT);
            }
            $where = [];
            $where[] = ['village_id','=',$village_id];
            $where[] = ['floor_id','=',$floor_id];
            $where[] = ['layer_number','=',$layer_number];
            $where[] = ['id','<>',$layer_id];
            $where[] = ['status','<>',4];
            $checkNameRepeat = $houseVillageSignService->getLayerInfo($where);
            if ($checkNameRepeat && isset($checkNameRepeat['id'])) {
                return api_output(1001, [], '同单元该楼层编号已经存在！');
            }

            $data = [];
            $data['layer_number']   = $layer_number;
            $data['layer_name']     = $layer_name;
            if ($status==1 || $status==2) {
                $data['status']     = $status-1;
            }
            //$data['sort']           = $sort;
            $where = [
                'village_id' => $village_id,
                'id'         => $layer_id
            ];
            $saveResult = $houseVillageSignService->saveLayerInfo($where,$data);

            if ($saveResult){
                //TODO 如果是修改楼栋编号成功需要触发编号其楼栋下房屋编号和对应房屋人员编号变动 ---> 放入队列执行【如果是基于新版之后，其实不用作这层操作】
                //如果需要：cms/Lib/Model/House_village_user_vacancyModel.class.php --> change_number()
                $this->cacheTagKey = 'village:cache:'.$village_id;
                Cache::tag($this->cacheTagKey)->clear();
                return api_output(0, [], '信息保存成功！');
            }else{
                return api_output(101, [], '信息保存失败！');
            }
        }


        /**
         * 软删除楼层信息
         * @return \json
         */
        public function deleteLayer()
        {
            $village_id = $this->adminUser['village_id'];
            if (empty($village_id)){
                return api_output(1002, [], '请先登录到小区后台！');
            }

            $layer_id = $this->request->post('layer_id',0);

            $houseVillageUserVacancyService = new HouseVillageUserVacancyService();
            $where = [];
            $where[] = ['village_id','=',$village_id];
            $where[] = ['layer_id','=',$layer_id];
            $where[] = ['status','<>',4];
            $where[] = ['is_del','=',0];
            $checkHasLayer  = $houseVillageUserVacancyService->getUserVacancyInfo($where,'pigcms_id');
            if ($checkHasLayer&&isset($checkHasLayer['pigcms_id'])){
                return api_output(1003, [], '该楼层下存在房屋不可删除，请先删除对应房屋！');
            }

            $houseVillageSignService = new HouseVillageSingleService();
            $where = [
                'village_id'  => $village_id,
                'id'          => $layer_id,
            ];
            $layerInfo=$houseVillageSignService->getLayerInfo($where);
            if($layerInfo && !$layerInfo->isEmpty()){
                $layerInfo=$layerInfo->toArray();
            }else{
                $layerInfo=array();
            }
            $data = ['status' => 4];
            $houseVillageSignService->softDeleteLayer($where,$data);

            $this->cacheTagKey = 'village:cache:'.$village_id;
            Cache::tag($this->cacheTagKey)->clear();
            $app_key = cfg('hik_info_vision_i_community_app_key');
            $app_secret = cfg('hik_info_vision_i_community_app_secret');
            if ($app_key && $app_secret) {
                $hikInfoVisionICommunityService = new HikInfoVisionICommunityService();
                $tmpCommunityRoom = ['village_id' => $village_id, 'handle_type' => 'handleLayerVacancy'];
                $tmpCommunityRoom['single_id'] = $layerInfo && isset($layerInfo['single_id']) ? $layerInfo['single_id']:0;
                $tmpCommunityRoom['floor_id'] = $layerInfo && isset($layerInfo['floor_id']) ? $layerInfo['floor_id']:0;
                $tmpCommunityRoom['layer_id'] = $layer_id;
                $hikInfoVisionICommunityService->unbindHikInfoVisionCommunityVacancy($village_id, $tmpCommunityRoom);
            }
            return api_output(0, [], '删除楼层成功！');
        }

		public function getQyWxCode()
		{
			$village_id = $this->adminUser['village_id'];
			if (empty($village_id)){
				return api_output(1002, [], '请先登录到小区后台！');
			}

			$params = [
				'qy_qrcode' => '/upload/house/village/20220801/09/62e7264d7705f.jpg',
				'template_type'=>1,
				'village_name'=>"哈哈哈哈"
			];
			$s= $this->traitMakePreviewImgForQyWx($village_id,$params);
			return api_output(0, $s, '1111！');
		}

		public function villageUploadFile()
		{
			$village_id = $this->adminUser['village_id'];
			if (empty($village_id)){
				return api_output(1002, [], '请先登录到小区后台，再上传！');
			}
			$service = new UploadFileService();
			$file = $this->request->file('file');
			$upload_dir = $this->request->param('upload_dir');
			$type = $this->request->param('type','');
			if (empty($type)){
				return api_output_error(1003, "缺少必传参数");
			}
			
			$processCacheKey = $this->getProcessCacheKey($village_id,$type);
			try {
				$processInfo = $this->queueReids()->get($processCacheKey);
			}catch (\RedisException $e){
				return api_output(1003, ['error' => '请先检查和配置'.app()->getRootPath() .'config/cache.php'], $e->getMessage());
			}catch (\think\Exception $e){
				return api_output(1003, ['error' => $e->getMessage()], '请先配置Redis');
			}

			$process = $processInfo['process'];
			if (!is_null($process)){
				if ($process == 100){
					$this->queueReids()->delete($processCacheKey);
				}else{
					return api_output_error(1003, "当前已有数据在执行中，当前执行进度【{$process}%】请耐心等待执行完，再导入！");
				}
			}

			$valdate = [
				'fileSize' => 1024 * 1024 * 10, // 10M 
				'fileExt' => 'xls,xlsx'
			];
			$upload_dir = $upload_dir .DIRECTORY_SEPARATOR.$village_id;
			try {
				$savepath = $service->uploadFile($file, $upload_dir,$valdate);
				return api_output(0, $savepath, 'success');
			} catch (\Exception $e) {
				return api_output_error(1003, $e->getMessage());
			}
		}
		
		
		//上传文件解析
		public function importForExcel()
		{
			$village_id = $this->adminUser['village_id'];
			if (empty($village_id)){
				return api_output(1002, [], '请先登录到小区后台！');
			}
			
			$excelPath = $this->request->post('inputFileName','');
            if (empty($excelPath)){
				return api_output_error(1003, '请上传文件');
            }
			//获取文件
			$inputFileName = app()->getRootPath().'..'.$excelPath;
 
			$fileArr = explode('.',$inputFileName);
			$fileExt = strtolower(end($fileArr));

			if ($fileExt === 'xls'){
				$inputFileType = "Xls";
			}else if($fileExt === 'xlsx'){
				$inputFileType = "Xlsx";
			}

			try {
				$reader = IOFactory::createReader($inputFileType);
				$reader->setReadDataOnly(true); //只读
				$worksheetData = $reader->listWorksheetInfo($inputFileName);
			}catch (Exception $e){
				return api_output_error(1003, $e->getMessage());
			}
			
			//可以覆盖的字段
			$replaceFieldSelect = [
				[
					'option'=> 'single_number',
					'title' => '楼栋编号'
				],
				[
					'option'=> 'single_name',
					'title' => '楼栋名称'
				]
			];
			
			return api_output(0, ['worksheet'=>$worksheetData,'replaceFieldSelect'=>$replaceFieldSelect], '文件上传成功！');
		}

		//返回当前选择 sheet 和 系统支持的字段和 Excel 的列和表头
		public function startBindExcelCol()
		{
			$village_id = $this->adminUser['village_id'];
			if (empty($village_id)){
				return api_output(1002, [], '请先登录到小区后台！');
			}
			$worksheetName          = $this->request->post('worksheetName','');
			$excelPath              = $this->request->post('inputFileName','');
			$selectWorkSheetIndex   = $this->request->post('selectWorkSheetIndex',0);
			
			//获取文件
			$inputFileName = app()->getRootPath().'..'.$excelPath;
			
			$fileArr = explode('.',$inputFileName);
			$fileExt = strtolower(end($fileArr));

			if ($fileExt === 'xls'){
				$inputFileType = "Xls";
			}else if($fileExt === 'xlsx'){
				$inputFileType = "Xlsx";
			}
			
			$reader = IOFactory::createReader($inputFileType);
			$reader->setReadDataOnly(true); //只读

			$spreadsheet = $reader->load($inputFileName);

//			$sheet = $spreadsheet->getSheetByName($worksheetName);//获取定制 sheet name 的工作表的内容
			$sheet = $spreadsheet->getSheet($selectWorkSheetIndex);//获取定制 sheet index 的工作表的内容
			$highestRow = $sheet->getHighestRow(); // 总行数
			$highestColumn = $sheet->getHighestColumn();// 最后有数据的一列，比如 S 列
			$highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);//把列字母转为数字,比如可以得到有 10 列
		 

			//获取列 （字母）
			$columnNames = [];        //列数 初始化,
			for ($j = 0 ; $j <= $highestColumnIndex ; $j++){
				$columnNames[] = Coordinate::stringFromColumnIndex($j);//得到列的首字母
			}

			//获取第一行（表头）
			$excelCloumns = [];
			$colTitle = [];
			foreach ($columnNames as $col){
				$temp = [];
				$text = $sheet->getCell($col.'1')->getValue();
				if (empty($text)){
					continue;
				}
				$text = ($text instanceof RichText) ? trim($text->getPlainText()) : trim($text);
				
				$temp['key']    = $col;
				$temp['val']    = $text;
				$excelCloumns[] = $temp;
				$colTitle[] =  $text;
			}

			//获取系统支持的字段
			$sourceMapFields = [
				[
					'key' => '楼栋名称',
					'val' => [
						'field'     => 'single_name',
						'isMust'    => false,
						'fieldType' => 'string'
					],
					'selected'  => false
				],
				[
					'key' => '楼栋编号',
					'val' => [
						'field'     => 'single_number',
						'isMust'    => true,
						'fieldType' => 'string'
					],
					'selected'  => false
				],
				 [
					 'key' => '楼栋面积',
					 'val' => [
						 'field'     => 'measure_area',
						 'isMust'    => false,
						 'fieldType' => 'number'
					 ],
					 'selected'  => false
				 ],
                [
                    'key' => '所含单元数',
                    'val' => [
                        'field'     => 'floor_num',
                        'isMust'    => false,
                        'fieldType' => 'number'
                    ],
                    'selected'  => false
                ],
                [
                    'key' => '所含房屋数',
                    'val' => [
                        'field'     => 'vacancy_num',
                        'isMust'    => false,
                        'fieldType' => 'number'
                    ],
                    'selected'  => false
                ],
				[
					'key' => '排序(倒序)',
					'val' => [
						'field'     => 'sort',
						'isMust'    => false,
						'fieldType' => 'number'
					],
					'selected'  => false
				],
				[
					'key' => '合同开始时间',
					'val' => [
						'field'     => 'contract_time_start',
						'isMust'    => false,
						'fieldType' => 'date'
					],
					'selected'  => false
				],
				[
					'key' => '合同结束时间',
					'val' => [
						'field'     => 'contract_time_end',
						'isMust'    => false,
						'fieldType' => 'date'
					],
					'selected'  => false
				]
//				,[
//					'key' => '所含单元数',
//					'val' => [
//						'field'     => 'floor_num',
//						'isMust'    => false,
//						'fieldType' => 'number'
//					],
//					'selected'  => false
//				], [
//					'key' => '所含房屋数',
//					'val' => [
//						'field'     => 'vacancy_num',
//						'isMust'    => false,
//						'fieldType' => 'number'
//					],
//					'selected'  => false
//				] 
			];

		 
			foreach ($sourceMapFields as $k=>&$source){
				foreach ($colTitle as $title){
					if ($source['key'] == trim($title)){
						$source['selected'] = true;
					}
				}
			}
			
			return api_output(0, ['excelCloumns'=>$excelCloumns,'sourceMapFields'=>$sourceMapFields], '返回对应关系！');

		}
		
		//准备导入
		public function startImport()
		{
			$village_id = $this->adminUser['village_id'];
			if (empty($village_id)){
				return api_output(1002, [], '请先登录到小区后台！');
			}
			
			//1、接收到 映射关系
			//2、放入队列
			//3、告知前端调整到进度展示画面
			//4、队列调用命令行执行
			
			$excelBinFieldRelationShip = $this->request->post('relationship','');
			$inputFileName          = $this->request->post('inputFileName','');
			$worksheetName          = $this->request->post('worksheetName','');
			$isRepeated             = $this->request->post('isRepeated',0,'intval');//数据重复时 0 跳过[默认]，1 覆盖
			$replaceField           = $this->request->post('replaceField','','trim');//当{$replaceField}重复时覆盖现有楼栋数据
			$selectWorkSheetIndex   = $this->request->post('selectWorkSheetIndex',0);
			
			$nowVillageInfo    = [
				'village_id'        => $this->adminUser['village_id'],
				'village_name'      => $this->adminUser['village_name'],
				'village_address'   => $this->adminUser['village_address'],
				'account'           => $this->adminUser['account'],
				'property_phone'    => $this->adminUser['property_phone'],
				'adminName'         => $this->adminUser['adminName'],
				'user_name'         => $this->adminUser['user_name'],
				'adminId'           => $this->adminUser['adminId'],
				'loginType'         => $this->adminUser['loginType'],
			];
			
			// 是否允许 楼栋名称 、 楼栋编号
			
			if ($isRepeated == 1){
				if (empty($replaceField)){
					return api_output(1, [], '重复数据覆盖时，必须要选择需要覆盖的字段！');
				}
			}
			
			$queueData = [
				'excelBinFieldRelationShip' => $excelBinFieldRelationShip,
				'selectWorkSheetIndex'      => $selectWorkSheetIndex,
				'inputFileName'             => $inputFileName,
				'worksheetName'             => $worksheetName,
				'isRepeated'                => $isRepeated,
				'replaceField'              => $replaceField,
				'nowVillageInfo'            => $nowVillageInfo,
			];
			$queueId = $this->buildingImportQueue($queueData);
			
			return api_output(0, ['queueId'=>$queueId], '提交成功，开始导入！'); 
			
		}
		
		//获取当前导入的进度条
		public function refreshProcess()
		{
			$village_id = $this->adminUser['village_id'];
			if (empty($village_id)){
				return api_output(1002, [], '请先登录到小区后台！');
			}
			$type = $this->request->param('type','');
			if (empty($type)){
				return api_output_error(1003, "缺少必传参数");
			}
			$processCacheKey = $this->getProcessCacheKey($village_id,$type);
			 
			try {
			   $processInfo = $this->queueReids()->get($processCacheKey);
			}catch (\RedisException $e){
				return api_output(2, ['error' => app()->getRootPath() .'config/cache.php'], $e->getMessage());
			}catch (\think\Exception $e){
				return api_output(1003, ['error' => $e->getMessage()], '请先配置Redis');
			}
			$process = $processInfo['process'];
			if ($process == 100){
				$this->queueReids()->delete($processCacheKey);
                $this->cacheTagKey = 'village:cache:'.$village_id;
                Cache::tag($this->cacheTagKey)->clear();
			}
			return  api_output(0, ['process'=>$process,'processInfo'=>$processInfo], '当前进度！');
		}

		/**
		 * @param $village_id 小区ID
		 * @param $bussiness 业务名称
		 *
		 * @return string
		 */
		protected function getProcessCacheKey($village_id,$bussiness)
		{
			return 'fastwhale:'.$bussiness.':process:'.$village_id;
		}


        /**
         * 测试参数
        $property_id=13;
        $village_id=50;
        $vacancy_id=14396;
        $pigcms_id=5873;
        $street_id=4;
         */

        /**
         * 获取房间选项集合
         * @author : lkz
         * @date : 2022/12/8
         * @return \json
         */
		public function getRoomOptionType(){
            $vacancy_id = $this->request->param('vacancy_id',0,'int');
            $village_id = $this->adminUser['village_id'];
            if (!$village_id){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            if (!$vacancy_id){
                return api_output(1001,[],'缺少必要参数');
            }
            try{
                $list = (new NewBuildingService())->getRoomOptionType($village_id,$vacancy_id);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$list);
        }

        /**
         * 获取房间绑定的用户
         * @author : lkz
         * @date : 2022/12/8
         * @return \json
         */
		public function getRoomBindUserList(){
            $vacancy_id = $this->request->param('vacancy_id',0,'int');
            $village_id = $this->adminUser['village_id'];
            $page = $this->request->param('page',1,'int');
            $limit = $this->request->param('limit',10,'int');
            if (!$village_id){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            if (!$vacancy_id){
                return api_output(1001,[],'缺少必要参数');
            }
            try{
                $param=array(
                    'village_id'=>$village_id,
                    'vacancy_id'=>$vacancy_id,
                    'page'=>$page,
                    'limit'=>$limit
                );
                $list = (new NewBuildingService())->getRoomBindUserList($param);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$list);
        }

        /**
         * 获取业主信息
         * @author : lkz
         * @date : 2022/12/12
         * @return \think\response\Json
         */
        public function getRoomBindOwnerData(){
            $vacancy_id = $this->request->param('vacancy_id',0,'int');
            $village_id = $this->adminUser['village_id'];
            if (!$village_id){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            if (!$vacancy_id){
                return api_output(1001,[],'缺少必要参数');
            }
            try{
                $list = (new NewBuildingService())->getRoomBindOwnerData($village_id,$vacancy_id,$this->adminUser['property_id']);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$list);
        }

        /**
         *获取用户数据
         * @author : lkz
         * @date : 2022/12/12
         * @return \think\response\Json
         */
        public function getRoomBindUserData(){
            $vacancy_id = $this->request->param('vacancy_id',0,'int');
            $pigcms_id = $this->request->param('pigcms_id',0,'int');
            $village_id = $this->adminUser['village_id'];
            if(!$village_id){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            if (!$vacancy_id){
                return api_output(1001,[],'缺少必要参数');
            }
            try{
                $param=array(
                    'property_id'=>$this->adminUser['property_id'],
                    'street_id'=>$this->adminUser['street_id'],
                    'village_id'=>$village_id,
                    'vacancy_id'=>$vacancy_id,
                    'pigcms_id'=>$pigcms_id
                );
                $list = (new NewBuildingService())->getRoomBindUserData($param);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$list);
        }

        /**
         * 提交用户数据
         * @author : lkz
         * @date : 2022/12/14
         * @return \think\response\Json
         */
        public function subRoomBindUserData(){
            $basic_data = $this->request->param('basic_data','');
            $pigcms_id = $this->request->param('pigcms_id',0,'int');
            $village_id = $this->adminUser['village_id'];
            if(!$village_id){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            if (!$basic_data){
                return api_output(1001,[],'缺少必要参数');
            }
            try{
                $param=array(
                    'village_id'=>$village_id,
                    'pigcms_id'=>$pigcms_id,
                    'basic_data'=>is_array($basic_data) ? $basic_data : json_decode($basic_data,true)
                );
                $list = (new NewBuildingService())->subRoomBindUserData($param);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$list);
        }

        /**
         * 提交编辑信息标注
         * @author : lkz
         * @date : 2022/12/14
         * @return \think\response\Json
         */
        public function subStreetPartyBindUser(){
            $pigcms_id = $this->request->param('pigcms_id',0,'int');
            $village_id = $this->adminUser['village_id'];
            $user_political_affiliation = $this->request->param('user_political_affiliation',0,'int');//政治面貌
            $user_party_id = $this->request->param('user_party_id',0,'int');//党支部id
            $user_special_groups = $this->request->param('user_special_groups','');//特殊人群身份
            $user_focus_groups = $this->request->param('user_focus_groups','');//重点人群身份
            $user_vulnerable_groups = $this->request->param('user_vulnerable_groups','');//关怀群体
            if(!$village_id){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            if (!$pigcms_id){
                return api_output(1001,[],'缺少必要参数');
            }
            try{
                $param=array(
                    'village_id'=>$village_id,
                    'pigcms_id'=>$pigcms_id,
                    'user_political_affiliation'=>$user_political_affiliation,
                    'user_party_id'=>$user_party_id,
                    'user_special_groups'=>$user_special_groups,
                    'user_focus_groups'=>$user_focus_groups,
                    'user_vulnerable_groups'=>$user_vulnerable_groups,
                );
                $list = (new NewBuildingService())->subStreetPartyBindUser($param);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$list);
        }

        /**
         * 获取省份
         * @return \think\response\Json
         * @author : lkz
         * Date: 2023/1/10
         * Time: 11:04
         */
        public function getHouseVillageProvince(){
            $village_id = $this->adminUser['village_id'];
            if (!$village_id){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            try{
                $list = (new AreaService())->getProvince();
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$list);
        }

        /**
         *获取市
         * @return \think\response\Json
         * @author : lkz
         * Date: 2023/1/10
         * Time: 11:04
         */
        public function getHouseVillageCity(){
            $village_id = $this->adminUser['village_id'];
            $id = $this->request->param('id',0,'int');
            $name = $this->request->param('name','','trim');
            if (!$village_id){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            if (!$id || !$name){
                return api_output(1001,[],'缺少必要参数');
            }
            try{
                $list = (new AreaService())->getCity($id,$name);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$list);
        }

        /**
         * 提交编辑用户标签
         * @author : lkz
         * @date : 2022/12/14
         * @return \think\response\Json
         */
        public function subBindUserLabel(){
            $pigcms_id = $this->request->param('pigcms_id',0,'int');
            $village_id = $this->adminUser['village_id'];
            $user_label_groups = $this->request->param('user_label_groups','');//标签id集合
            if(!$village_id){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            if (!$pigcms_id){
                return api_output(1001,[],'缺少必要参数');
            }
            try{
                $param=array(
                    'village_id'=>$village_id,
                    'pigcms_id'=>$pigcms_id,
                    'user_label_groups'=>$user_label_groups
                );
                $list = (new NewBuildingService())->subBindUserLabel($param);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$list);
        }

        /**
         * 获取房间详情
         * @author : lkz
         * @date : 2022/12/8
         */
        public function getRoomDetails(){
            $vacancy_id = $this->request->param('vacancy_id',0,'int');
            $village_id = $this->adminUser['village_id'];
            if (!$village_id){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            if (!$vacancy_id){
                return api_output(1001,[],'缺少必要参数');
            }
            try{
                $list = (new NewBuildingService())->getRoomDetails($village_id,$vacancy_id);
                $app_key = cfg('hik_info_vision_i_community_app_key');
                $app_secret = cfg('hik_info_vision_i_community_app_secret');
                $list['hik_info_vision_i_community'] = 0;
                if($app_key && $app_secret){
                    $single_id=$list['room_info']['single_id'];
                    $floor_id=$list['room_info']['floor_id'];
                    $layer_id=$list['room_info']['layer_id'];
                    $vacancy_id=$list['room_info']['vacancy_id'];
                    $third_room_id=$list['room_info']['third_room_id'];
                    $hikInfoVisionICommunityService= new HikInfoVisionICommunityService();
                    $tmpData=array('single_id'=>$single_id,'floor_id'=>$floor_id);
                    $tmpData['layer_id']=$layer_id;
                    $tmpData['vacancy_id']=$vacancy_id;
                    $tmpData['third_room_id']=$third_room_id;
                    $list['hik_info_vision_i_community'] = 1;
                    $list['hik_info_community_room']=$hikInfoVisionICommunityService->getCommunitySingleFloorRooms($village_id,$tmpData);
                }
                $list['is_aihorse_customized'] = 0;
                $isAihorseCustomizedJudge = (new ConfigCustomizationService())->getAihorseCustomizedJudge();
                if($isAihorseCustomizedJudge>0){
                    $list['is_aihorse_customized'] = 1;
                }
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$list);
        }

        /**
         * 编辑提交房间属性
         * @author : lkz
         * @date : 2022/12/9
         * @return \json
         */
        public function getRoomAttachmentList()
        {
            $vacancy_id = $this->request->param('vacancy_id', 0, 'int');
            $village_id = $this->adminUser['village_id'];
            if (!$village_id) {
                return api_output(1002, [], '请先登录到小区后台！');
            }
            if (!$vacancy_id) {
                return api_output(1001, [], '缺少必要参数');
            }
            try {
                $list = (new ChatSidebarService())->getRoomAttachmentList($village_id, $vacancy_id);
            } catch (\Exception $e) {
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0, $list);
        }

        public function saveRoomAttachmentList()
        {
            $vacancy_id = $this->request->param('vacancy_id', 0, 'int');
            $files = $this->request->param('files', []);
            $village_id = $this->adminUser['village_id'];
            if (!$village_id) {
                return api_output(1002, [], '请先登录到小区后台！');
            }
            if (!$vacancy_id) {
                return api_output(1001, [], '缺少必要参数');
            }
            try {
                $list = (new ChatSidebarService())->saveRoomAttachmentList($village_id, $vacancy_id, $files);
            } catch (\Exception $e) {
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0, $list);
        }

        public function subRoomAttribute(){
            //房间属性参数
            $vacancy_id = $this->request->param('vacancy_id',0,'int');
            $usernum = $this->request->param('usernum','','trim');
            $room_number = $this->request->param('room_number','','trim');
            $contract_time_start = $this->request->param('contract_time_start','','trim');
            $contract_time_end = $this->request->param('contract_time_end','','trim');
            $house_type = $this->request->param('house_type',0,'int');
            $user_status = $this->request->param('user_status',0,'int');
            $sell_status = $this->request->param('sell_status',0,'int');
            $housesize = $this->request->param('housesize','','trim');
            $sort = $this->request->param('sort',0,'int');
            $status = $this->request->param('status',0,'int');
            //室内机参数
            $indoor_device_sn = $this->request->param('indoor_device_sn','','trim');
            $indoor_status = $this->request->param('indoor_status',0,'int');
            //水电燃参数
            $water_number = $this->request->param('water_number','','trim');
            $heat_water_number = $this->request->param('heat_water_number','','trim');
            $ele_number = $this->request->param('ele_number','','trim');
            $gas_number = $this->request->param('gas_number','','trim');
            $room_alias_id = $this->request->param('room_alias_id','','trim');
            $village_id = $this->adminUser['village_id'];
            $room =  $this->request->param('room','','trim');
            $heating_size = $this->request->param('heating_size','0','trim');
            $billable_size = $this->request->param('billable_size','0','trim');
            $electric_meter_data = $this->request->param('electric_meter_data','');
            $water_meter_data = $this->request->param('water_meter_data','');
            $third_room_id =  $this->request->param('third_room_id','','trim');
            $house_in_time =  $this->request->param('house_in_time','','trim');
            $water_balance =  $this->request->param('water_balance',0);
            $room_imgs =  $this->request->param('room_imgs','');  //图片 数组
            $lease_time_start =  $this->request->param('lease_time_start','','trim');
            $lease_time_end =  $this->request->param('lease_time_end','','trim');
            $yumenlao_special_meter = $this->request->param('yumenlao_special_meter','');
            if (!$village_id){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            if (!$vacancy_id){
                return api_output(1001,[],'缺少必要参数');
            }
            if(empty($usernum)){
                return api_output(1001,[],'物业编号不为空');
            }
            $contract_time_start=$contract_time_start ? strtotime($contract_time_start.' 00:00:00'):0;
            $contract_time_start=$contract_time_start>31420800 ? $contract_time_start:0;
            $contract_time_end=$contract_time_end ? strtotime($contract_time_end.' 23:59:59'):0;
            $contract_time_end=$contract_time_end>31420800 ? $contract_time_end:0;
            $lease_time_start=$lease_time_start ? strtotime($lease_time_start):'';
            $lease_time_end=$lease_time_end ? strtotime($lease_time_end):'';
            $param=[
                'village_id'=>$village_id,
                'vacancy_id'=>$vacancy_id,
                'usernum'=>$usernum,
                'contract_time_start'=>$contract_time_start,
                'contract_time_end'=>$contract_time_end,
                'house_type'=>$house_type,
                'user_status'=>$user_status,
                'sell_status'=>$sell_status,
                'housesize'=>$housesize,
                'sort'=>$sort,
                'status'=>$status,
                'indoor_device_sn'=>$indoor_device_sn,
                'indoor_status'=>$indoor_status,
                'water_number'=>$water_number,
                'heat_water_number'=>$heat_water_number,
                'ele_number'=>$ele_number,
                'gas_number'=>$gas_number,
                'room'=>$room,
                'room_alias_id'=>$room_alias_id,
                'heating_size'=>$heating_size,
                'billable_size'=>$billable_size,
                'electric_meter_data'=>$electric_meter_data,
                'water_meter_data'=>$water_meter_data,
                'house_in_time'=>$house_in_time,
                'water_balance'=>$water_balance,
                'room_imgs'=>$room_imgs,
                'lease_time_start'=>$lease_time_start ? $lease_time_start:'',
                'lease_time_end'=>$lease_time_end ? $lease_time_end:'',
                'room_number'=>$room_number ? $room_number:'',
                'yumenlao_special_meter'=>$yumenlao_special_meter,
            ];
            $app_key = cfg('hik_info_vision_i_community_app_key');
            $app_secret = cfg('hik_info_vision_i_community_app_secret');
            $is_hik_info_vision_i_community=0;
            $old_third_room_id='';
            $newBuildingService= new NewBuildingService();
            if($app_key && $app_secret){
                $is_hik_info_vision_i_community=1;
                $param['third_room_id']=$third_room_id;
                $where = [
                    'village_id' => $village_id,
                    'pigcms_id'  => $vacancy_id
                ];
                $tmpCommunityRoom=['village_id'=>$village_id,'single_id'=>0,'floor_id'=>0,'layer_id'=>0,'old_third_room_id'=>'','third_room_id'=>$third_room_id,'vacancy_id'=>$vacancy_id,'handle_type'=>'handleRoomVacancy'];
                $oldVacancyInfo=$newBuildingService->getHouseVillageUserVacancyFindMethod($where,'single_id,floor_id,layer_id,third_room_id');
                if($oldVacancyInfo && isset($oldVacancyInfo['third_room_id'])){
                    $old_third_room_id=$oldVacancyInfo['third_room_id'];
                    $tmpCommunityRoom['single_id']=$oldVacancyInfo['single_id'];
                    $tmpCommunityRoom['floor_id']=$oldVacancyInfo['floor_id'];
                    $tmpCommunityRoom['layer_id']=$oldVacancyInfo['layer_id'];
                    $tmpCommunityRoom['old_third_room_id']=$old_third_room_id;
                }
                try {
                    if($third_room_id && $old_third_room_id!=$third_room_id){
                        $hikInfoVisionICommunityService= new HikInfoVisionICommunityService();
                        $isBinded=$hikInfoVisionICommunityService->checkRoomIsBinded($village_id,$third_room_id,$vacancy_id);
                        if($isBinded){
                            return api_output(1001, [], '该海康智慧社区房间已被其他房间先绑定了，请选择其他房间绑定！');
                        }
                    }
                } catch (\Exception $e) {
                    return api_output_error(-1, $e->getMessage());
                }
            }
            try{
                $list = $newBuildingService->editRoomParam($param);
                if($is_hik_info_vision_i_community==1 && $old_third_room_id!=$third_room_id){
                    $hikInfoVisionICommunityService= new HikInfoVisionICommunityService();
                    $hikInfoVisionICommunityService->handleHikInfoVisionCommunityVacancy($tmpCommunityRoom);
                }
                $this->cacheTagKey = 'village:cache:'.$village_id;
                Cache::tag($this->cacheTagKey)->clear();
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$list);
        }

        /**
         * 获取房间对应的收费标准
         * @author : lkz
         * @date : 2022/12/10
         * @return \json
         */
        public function getRoomBindChargeRuleList(){
            $vacancy_id = $this->request->param('vacancy_id',0,'int');
            $village_id = $this->adminUser['village_id'];
            $page = $this->request->param('page',1,'int');
            $limit = $this->request->param('limit',10,'int');
            if (!$village_id){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            if (!$vacancy_id){
                return api_output(1001,[],'缺少必要参数');
            }
            try{
                $param=array(
                    'village_id'=>$village_id,
                    'vacancy_id'=>$vacancy_id,
                    'page'=>$page,
                    'limit'=>$limit
                );
                $list = (new NewBuildingService())->getRoomBindChargeRuleList($param);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$list);
        }

        /**
         * 获取房间绑定的车辆
         * @author : lkz
         * @date : 2022/12/10
         * @return \json
         */
        public function getRoomBindVehicleList(){
            $vacancy_id = $this->request->param('vacancy_id',0,'int');
            $village_id = $this->adminUser['village_id'];
            $page = $this->request->param('page',1,'int');
            $limit = $this->request->param('limit',10,'int');
            if (!$village_id){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            if (!$vacancy_id){
                return api_output(1001,[],'缺少必要参数');
            }
            try{
                $param=array(
                    'village_id'=>$village_id,
                    'vacancy_id'=>$vacancy_id,
                    'page'=>$page,
                    'limit'=>$limit
                );
                $list = (new NewBuildingService())->getRoomBindVehicleList($param);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$list);
        }

        /**
         * 获取房产账单
         * @author : lkz
         * @date : 2022/12/12
         * @return \think\response\Json
         */
        public function getRoomBindBillList(){
            $vacancy_id = $this->request->param('vacancy_id',0,'int');
            $village_id = $this->adminUser['village_id'];
            $property_id = $this->adminUser['property_id'];
            $type = $this->request->param('type',2,'int');
            $page = $this->request->param('page',1,'int');
            $limit = $this->request->param('limit',10,'int');
            if (!$village_id){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            if (!$vacancy_id){
                return api_output(1001,[],'缺少必要参数');
            }
            try{
                $param=array(
                    'village_id'=>$village_id,
                    'vacancy_id'=>$vacancy_id,
                    'property_id'=>$property_id,
                    'type'=>$type,
                    'page'=>$page,
                    'limit'=>$limit
                );
                $list = (new NewBuildingService())->getRoomBindBillList($param);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$list);
        }

        /**
         * 获取关联工单
         * @author : lkz
         * @date : 2022/12/13
         * @return \think\response\Json
         */
        public function getRoomBindWorksOrderList(){
            $vacancy_id = $this->request->param('vacancy_id',0,'int');
            $village_id = $this->adminUser['village_id'];
            $page = $this->request->param('page',1,'int');
            $limit = $this->request->param('limit',10,'int');
            if (!$village_id){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            if (!$vacancy_id){
                return api_output(1001,[],'缺少必要参数');
            }
            try{
                $param=array(
                    'village_id'=>$village_id,
                    'vacancy_id'=>$vacancy_id,
                    'page'=>$page,
                    'limit'=>$limit
                );
                $list = (new NewBuildingService())->getRoomBindWorksOrderList($param);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$list);
        }

        /**
         * 获取关联卡号
         * @author : lkz
         * @date : 2022/12/13
         * @return \think\response\Json
         */
        public function getRoomBindIcCardList(){
            $vacancy_id = $this->request->param('vacancy_id',0,'int');
            $village_id = $this->adminUser['village_id'];
            $page = $this->request->param('page',1,'int');
            $limit = $this->request->param('limit',10,'int');
            if (!$village_id){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            if (!$vacancy_id){
                return api_output(1001,[],'缺少必要参数');
            }
            try{
                $param=array(
                    'village_id'=>$village_id,
                    'vacancy_id'=>$vacancy_id,
                    'page'=>$page,
                    'limit'=>$limit
                );
                $list = (new NewBuildingService())->getRoomBindIcCardList($param);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$list);
        }
        
        /**
         * 房间列表配置数据
         * @return \think\response\Json
         * @author : lkz
         * Date: 2023/1/7
         * Time: 17:23
         */
        public function getRoomListConfig(){
            try{
                $param=array(
                    'property_id'=>$this->adminUser['property_id'],
                    'village_id'=>$this->adminUser['village_id'],
                    'write_iccard'=>$this->adminUser['write_iccard'],
                );
                $list = (new NewBuildingService())->getRoomListConfig($param);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$list);
        }
        
        /**
         * 获取房间列表
         * @return \think\response\Json
         * @author : lkz
         * Date: 2023/1/6
         * Time: 15:17
         */
        public function getRoomDataList(){
            $village_id = $this->adminUser['village_id'];
            $property_id = $this->adminUser['property_id'];
            $room_status = $this->request->param('room_status',0,'int');
            $find_type = $this->request->param('find_type',0,'int');
            $find_value = $this->request->param('find_value','','trim');
            $single_id = $this->request->param('single_id',0,'int');
            $floor_id = $this->request->param('floor_id',0,'int');
            $layer_id = $this->request->param('layer_id',0,'int');
            $vacancy_id = $this->request->param('vacancy_id',0,'int');
            $page = $this->request->param('page',1,'int');
            $limit = $this->request->param('limit',10,'int');
            if (!$village_id){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            try{
                $param=array(
                    'property_id'=>$property_id,
                    'village_id'=>$village_id,
                    'room_status'=>$room_status,
                    'find_type'=>$find_type,
                    'find_value'=>$find_value,
                    'single_id'=>$single_id,
                    'floor_id'=>$floor_id,
                    'layer_id'=>$layer_id,
                    'vacancy_id'=>$vacancy_id,
                    'page'=>$page,
                    'limit'=>$limit
                );
                $list = (new NewBuildingService())->getRoomDataList($param);
                $site_url=cfg('site_url');
                $site_url=trim($site_url,'/');
                $list['excelExportOutUrl']=$site_url.'/shequ.php?g=House&c=Unit&a=roomExport&export_from=buildingRoom';
                $list['excelExportOutFileUrl']=$site_url.'/index.php?g=Index&c=ExportFile&a=download_export_file&export_from=buildingRoom';
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            $houseVillageService=new HouseVillageService();
            $list['role_export']=$houseVillageService->checkPermissionMenu(112176,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $list['role_import']=$houseVillageService->checkPermissionMenu(112177,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $list['role_edit']=$houseVillageService->checkPermissionMenu(112178,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $list['role_del']=$houseVillageService->checkPermissionMenu(112179,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $houseVillageCheckauthApplyService = new HouseVillageCheckauthApplyService();
            $whereCount=array(['village_id','=',$village_id],['xtype','=','order_servicetime_check'],['status','<=',1]);
            $list['apply_count']=$houseVillageCheckauthApplyService->getApplyCount($whereCount);
            return api_output(0,$list);
        }

        /**
         *删除房间
         * @return \think\response\Json
         * @author : lkz
         * Date: 2023/1/9
         * Time: 11:22
         */
        public function delRoomOperation(){
            $vacancy_id = $this->request->param('vacancy_id',[]);
            $village_id = $this->adminUser['village_id'];
            if (!$village_id){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            if (!$vacancy_id || !is_array($vacancy_id)){
                return api_output(1001,[],'缺少必要参数');
            }
            try{
                $newBuildingService = new NewBuildingService();
                $list = $newBuildingService->delRoomOperation($village_id, $vacancy_id);
                $this->cacheTagKey = 'village:cache:' . $village_id;
                Cache::tag($this->cacheTagKey)->clear();
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$list);
        }

        /**
         *读取设备品牌
         * @return \think\response\Json
         * @author : lkz
         * Date: 2023/1/9
         * Time: 14:32
         */
        public function getIcDeviceBrand(){
            try{
                $list = (new NewBuildingService())->getIcDeviceBrand(6);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$list);
        }

        /**
         * 读取设备类型
         * @return \think\response\Json
         * @author : lkz
         * Date: 2023/1/9
         * Time: 14:32
         */
        public function getIcDeviceType(){
            $brand_id = $this->request->param('brand_id',0,'int');
            if (!$brand_id){
                return api_output(1001,[],'参数不合法');
            }
            try{
                $list = (new NewBuildingService())->getIcDeviceType($brand_id);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$list);
        }

        /**
         * 添加ic卡
         * @return \think\response\Json
         * @author : lkz
         * Date: 2023/1/9
         * Time: 15:08
         */
        public function subVacancyIcCard(){
            $brand_name = $this->request->param('brand_name','','trim');
            $type_name = $this->request->param('type_name','','trim');
            $ic_card = $this->request->param('ic_card','','trim');
            $vacancy_id = $this->request->param('vacancy_id',0,'int');
            $village_id = $this->adminUser['village_id'];
            if (!$village_id){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            if (!$brand_name){
                return api_output(1001,[],'请选择设备品牌');
            }
            if (!$type_name){
                return api_output(1001,[],'请选择设备类型');
            }
            if (!$ic_card){
                return api_output(1001,[],'卡号不能为空');
            }
            try{
                $param=[
                    'village_id'=>$village_id,
                    'vacancy_id'=>$vacancy_id,
                    'ic_card'=>$ic_card,
                    'add_time'=>time(),
                    'device_brand'=>$brand_name,
                    'device_type'=>$type_name,
                ];
                $list =(new HouseVillageVacancyIccardService())->addIcCard($param);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$list);
        }

        /**
         * 删除IC卡
         * @return \think\response\Json
         * @author : lkz
         * Date: 2023/1/9
         * Time: 18:10
         */
        public function delVacancyIcCard(){
            $bind_id = $this->request->param('bind_id',0,'int');
            $village_id = $this->adminUser['village_id'];
            if (!$village_id){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            if (!$bind_id){
                return api_output(1001,[],'缺少必要参数');
            }
            try{
                $list = (new HouseVillageVacancyIccardService())->delIcCard($village_id,$bind_id);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$list);
        }

        /**
         * 已解绑住户记录
         * @return \think\response\Json
         * @author : lkz
         * Date: 2023/1/9
         * Time: 19:03
         */
        public function getRoomUnbindingUserList(){
            $village_id = $this->adminUser['village_id'];
            $vacancy_id = $this->request->param('vacancy_id',0,'int');
            $page = $this->request->param('page',1,'int');
            $limit = $this->request->param('limit',10,'int');
            if (!$village_id){
                return api_output(1002, [], '请先登录到小区后台！');
            }
            try{
                $param=array(
                    'village_id'=>$village_id,
                    'vacancy_id'=>$vacancy_id,
                    'page'=>$page,
                    'limit'=>$limit
                );
                $list = (new NewBuildingService())->getRoomUnbindingUserList($param);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            return api_output(0,$list);
        }
        
        public function getHikInfoCommunityRegionsHouses(){
            set_time_limit(0);
            $app_key = cfg('hik_info_vision_i_community_app_key');
            $app_secret = cfg('hik_info_vision_i_community_app_secret');
            if ($app_key && $app_secret) {
                $hikInfoVisionICommunityService= new HikInfoVisionICommunityService();
                $village_id = $this->adminUser['village_id'];
                $hikInfoVisionICommunityService->getICommunityRegions();
                $retInfo=$hikInfoVisionICommunityService->laterQueueGetRegionsHouses($village_id);
                return api_output(0,$retInfo);
            }
            return api_output(1001,[],'您还没配置好新海康智慧社区管理平台对接配置');
        }

        public function getLiLinPfVillageRoomHouses(){
            set_time_limit(0);
            $village_id = $this->adminUser['village_id'];
            $is_func_cloudmrsqfw_special = (new ConfigCustomizationService())->getFuncCloudmrsqfwSpecial();
            $houseVillageInfoService = new HouseVillageService();
            if ($is_func_cloudmrsqfw_special && $village_id) {
                $houseVillageObj=$houseVillageInfoService->getHouseVillageInfo(array('village_id' => $village_id),'*');
                if($houseVillageObj && isset($houseVillageObj['lilin_pf_client_id']) && $houseVillageObj['lilin_pf_client_id'] && $houseVillageObj['lilin_pf_client_secret'] && $houseVillageObj['lilin_village_no']){
                    $liLinDevAccessApiService= new \app\community\model\service\Device\LiLinDevAccessApiService();
                    $retInfo=$liLinDevAccessApiService->laterQueueGetLiLinPfVillageRoomHouses($village_id);
                    return api_output(0,$retInfo);
                }

            }
            return api_output(1001,[],'您还没配置好立林社区管理平台对接配置');
        }

        public function getRoomOrderServiceTimeInfo(){
            $village_id = $this->adminUser['village_id'];
            $vacancy_id = $this->request->param('room_id',0,'intval');
            $project_id = $this->request->param('project_id',0,'intval');
            $getapplystatus = $this->request->param('getapplystatus',0,'intval');
            
            $orderServiceTimeInfo=array('charge_types'=>array(['key' => '','value' => '请选择收费类别']));
            $serviceHouseNewPorperty = new HouseNewChargeService();
            $charge_types=$serviceHouseNewPorperty->charge_type_arr;
            $no_need_arr=array('property','water','electric','gas','park_new','park','qrcode','pile','qunuanfei','electricity_loss','non_motor_vehicle');
            foreach($charge_types as $cvv){
                if(!in_array($cvv['key'],$no_need_arr)){
                    $orderServiceTimeInfo['charge_types'][]=$cvv;
                }
            }
            if($getapplystatus>0){
                $orderServiceTimeInfo['applyStatus']=array(['key'=>0,'value'=>'全部'],['key'=>1,'value'=>'审核中'],['key'=>2,'value'=>'审核通过'],['key'=>3,'value'=>'审核未通过']);
            }
            $orderServiceTimeInfo['currentServiceTime']='';
            if($project_id>0 && $vacancy_id>0){
                $service_house_new_cashier = new HouseNewCashierService();
                $whereArr=array(['project_id','=',$project_id],['room_id','=',$vacancy_id],['village_id','=',$village_id]);
                $orderLogObj = $service_house_new_cashier->getOrderLog($whereArr,'*','id DESC');
                if($orderLogObj && !$orderLogObj->isEmpty()){
                    $orderLog=$orderLogObj->toArray();
                    $service_end_time='';
                    if($orderLog['service_end_time']>100){
                        $service_end_time=date('Y-m-d H:i:s',$orderLog['service_end_time']);
                    }
                    $service_start_time='';
                    if($orderLog['service_start_time']>100 && $orderLog['service_start_time']<$orderLog['service_end_time']){
                        $service_start_time=date('Y-m-d H:i:s',$orderLog['service_start_time']);
                    }else{
                        $service_start_time=$service_end_time;
                    }
                    if($service_end_time){
                        $orderServiceTimeInfo['currentServiceTime']=$service_start_time.' 至 '.$service_end_time;
                    }
                }
            }

            return api_output(0,$orderServiceTimeInfo);
        }
        public function saveRoomOrderNewServiceTime(){
            $village_id = $this->adminUser['village_id'];
            $vacancy_id = $this->request->param('room_id',0,'intval');
            $project_id = $this->request->param('project_id',0,'intval');
            $charge_type = $this->request->param('charge_type','','trim');
            $apply_reason = $this->request->param('apply_reason','','trim');
            $service_new_time= $this->request->param('service_new_time','');
            if(empty($service_new_time) || !is_array($service_new_time) || empty($service_new_time['0'])){
                return api_output(1001,[],'修改服务时间未正确选择！');
            }
            if($vacancy_id<1){
                return api_output(1001,[],'房间ID错误！');
            }
            if(empty($charge_type)){
                return api_output(1001,[],'所选收费类别错误！');
            }
            if($project_id<1){
                return api_output(1001,[],'所选收费项目错误！');
            }
            $service_start_time=strtotime($service_new_time['0']);
            $service_end_time=strtotime($service_new_time['1'])+86399;
            $property_id=0;
            $houseVillageService =new HouseVillageService();
            $houseVillage=$houseVillageService->getHouseVillageInfo(array('village_id'=>$village_id),'village_id,village_name,property_id');
            if($houseVillage && isset($houseVillage['property_id'])){
                $property_id=$houseVillage['property_id'];
            }
            $extra = array('login_role' => $this->login_role, 'wid' => 0, 'apply_uid' => $this->_uid, 'apply_name' => '', 'apply_phone' => '');
            if (isset($this->adminUser['wid']) && ($this->adminUser['wid'] > 0)) {
                $extra['wid'] = $this->adminUser['wid'];
            }
            if (isset($this->adminUser['user_name']) && !empty($this->adminUser['user_name'])) {
                $extra['apply_name'] = $this->adminUser['user_name'];
            }
            if (isset($this->adminUser['phone']) && !empty($this->adminUser['phone'])) {
                $extra['apply_phone'] = $this->adminUser['phone'];
            }
            $houseNewCashierService = new HouseNewCashierService();
            $apply_current_start_time=0;
            $apply_current_end_time=0;
            if($project_id>0 && $vacancy_id>0){
                $whereArr=array(['project_id','=',$project_id],['room_id','=',$vacancy_id],['village_id','=',$village_id]);
                $orderLogObj = $houseNewCashierService->getOrderLog($whereArr,'*','id DESC');
                if($orderLogObj && !$orderLogObj->isEmpty()){
                    $orderLog=$orderLogObj->toArray();
                    if($orderLog['service_end_time']>100){
                        $apply_current_end_time=$orderLog['service_end_time'];
                    }
                    if($orderLog['service_start_time']>100 && $orderLog['service_start_time']<$orderLog['service_end_time']){
                        $apply_current_start_time=$orderLog['service_start_time'];
                    }else{
                        $apply_current_start_time=$apply_current_end_time;
                    }
                }
            }
            $houseVillageCheckauthApplyService = new HouseVillageCheckauthApplyService();
            $whereProjectArr=array(['id','=',$project_id],['village_id','=',$village_id]);
            $chargeProjectInfo=$houseVillageCheckauthApplyService->getOneChargeProjectInfo($whereProjectArr,'id,name');
            $project_name=$chargeProjectInfo ? $chargeProjectInfo['name']:'';
            $houseVillageCheckauthSetService = new HouseVillageCheckauthSetService();
            $orderRefundCheckWhere = array('village_id' => $village_id, 'xtype' => 'order_servicetime_check');
            $checkauthSet = $houseVillageCheckauthSetService->getOneCheckauthSet($orderRefundCheckWhere);
            
            $whereOrderArr=array(['village_id','=',$village_id],['room_id','=',$vacancy_id],['project_id','=',$project_id],['is_discard','=',1],['is_paid','=',2]);
            $onePayOrder=$houseVillageCheckauthApplyService->getOnePayOrderInfo($whereOrderArr,'order_id,order_name,room_id');
            if($onePayOrder && isset($onePayOrder['order_id'])){
                return api_output(1001,[],'此房间【'.$project_name.'】收费项目下有待缴账单，请先去缴费或者作废掉待缴账单！');
            }
            $orderRefundCheckArr = array('property_id' => $property_id, 'village_id' => $village_id);
            $orderRefundCheckArr['xtype'] = 'order_servicetime_check';
            $orderRefundCheckArr['apply_login_role'] = $extra['login_role'];
            $orderRefundCheckArr['apply_name'] = $extra['apply_name'];
            $orderRefundCheckArr['apply_phone'] = $extra['apply_phone'];
            $orderRefundCheckArr['apply_uid'] = $extra['apply_uid'];
            $orderRefundCheckArr['service_start_time'] = $service_start_time;
            $orderRefundCheckArr['service_end_time'] = $service_end_time;
            $orderRefundCheckArr['apply_reason'] = $apply_reason;
            $orderRefundCheckArr['room_id'] = $vacancy_id;
            $orderRefundCheckArr['project_id'] = $project_id;
            $orderRefundCheckArr['charge_type'] = $charge_type;
            $newBuildingService=new NewBuildingService();
            $whereRoomArr=array(['pigcms_id' ,'=', $vacancy_id],['village_id' ,'=', $village_id]);
            $vacancyInfo=$newBuildingService->getHouseVillageUserVacancyFindMethod($whereRoomArr,'*');
            if($vacancyInfo && $vacancyInfo['pigcms_id']){
                $orderRefundCheckArr['single_id'] = $vacancyInfo['single_id'];
                $orderRefundCheckArr['floor_id'] =  $vacancyInfo['floor_id'];
                $orderRefundCheckArr['layer_id'] = $vacancyInfo['layer_id'];
            }
            if (!empty($checkauthSet) && ($checkauthSet['is_open'] > 0) && !empty($checkauthSet['check_level'])) {
                $orderRefundCheckArr['status'] = 1;  //0未审核 1审核中 2审核通过
                $extra['checkauth_set'] = $checkauthSet;
                $extra['project_name']=$project_name;
                $whereArr=array(['village_id','=',$village_id],['room_id','=',$vacancy_id],['project_id','=',$project_id],['status','<',2],['xtype','=','order_servicetime_check']);
                $oneTmpAddApply=$houseVillageCheckauthApplyService->getOneAddApply($whereArr,'*');
                if($oneTmpAddApply && isset($oneTmpAddApply['id'])){
                    return api_output(1001,[],'此房间【'.$project_name.'】收费项目已有在审核中的申请数据，请等待已有的申请有审核结果后再申请！');
                }
                $extra_data=array('need_chcek'=>1,'log_order_id'=>0,'opt_time'=>date('Y-m-d H:i:s'),'project_name'=>$project_name);
                $extra_data['apply_current_start_time']=$apply_current_start_time;
                $extra_data['apply_current_end_time']=$apply_current_end_time;
                $extra_data_json=json_encode($extra_data,JSON_UNESCAPED_UNICODE);
                $orderRefundCheckArr['extra_data']=$extra_data_json;
                $insert_id = $houseVillageCheckauthApplyService->addApply($orderRefundCheckArr, $extra);
                if($insert_id>0){
                    $msg='申请修改计费时间已提交，请等待审核';
                    $oneTmpApply=$houseVillageCheckauthApplyService->getOneAddApply(array('id'=>$insert_id),'*');
                    if($oneTmpApply['status']==2){
                        $msg='您的申请修改计费时间审核已通过！';
                    }
                    return api_output(0,['apply_id'=>$insert_id,'msg'=>$msg]);
                }else{
                    return api_output(1001,[],'申请数据保存失败！');
                }
            }else{
                $nowtime=time();
                
                $logOrder = [
                    'order_type'=>$charge_type,
                    'order_name'=>$project_name,
                    'project_id'=>$project_id,
                    'room_id'=> $vacancy_id,
                    'village_id'=>$village_id,
                    'property_id'=>$property_id,
                    'desc'=>'通过申请修改计费时间',
                    'service_start_time'=>$service_start_time,
                    'service_end_time'=>$service_end_time,
                    'add_time'=>time()
                ];
                $log_id=$houseNewCashierService->addOrderLog($logOrder);
                $orderRefundCheckArr['add_time'] = $nowtime;
                $orderRefundCheckArr['apply_time'] = $nowtime;
                $orderRefundCheckArr['other_relation_id']=$log_id;
                $orderRefundCheckArr['status'] = 2;  //0未审核 1审核中 2审核通过
                $extra_data=array('need_chcek'=>0,'log_order_id'=>$log_id,'project_name'=>$project_name,'opt_time'=>date('Y-m-d H:i:s'));
                $extra_data['apply_current_start_time']=$apply_current_start_time;
                $extra_data['apply_current_end_time']=$apply_current_end_time;
                $extra_data_json=json_encode($extra_data,JSON_UNESCAPED_UNICODE);
                $orderRefundCheckArr['extra_data']=$extra_data_json;
                $insert_id= $houseVillageCheckauthApplyService->addDirectApply($orderRefundCheckArr);
                return api_output(0,['apply_id'=>$insert_id,'msg'=>'您的申请修改计费时间已成功！']);
            }
        }

        public function getApplyServicetimeCheckList(){
            $village_id = $this->adminUser['village_id'];
            $vacancy_id = $this->request->param('vacancy_id',0,'intval');
            $project_id = $this->request->param('project_id',0,'intval');
            $charge_type = $this->request->param('charge_type','','trim');
            $status = $this->request->param('status',0,'intval');
            $single_id = $this->request->param('single_id',0,'intval');
            $floor_id = $this->request->param('floor_id',0,'intval');
            $layer_id = $this->request->param('layer_id',0,'intval');
            $page = $this->request->param('page',1,'intval');
            $page=$page>0 ?$page:1;
            $limit=8;
            $whereArr=array(['village_id','=',$village_id],['xtype','=','order_servicetime_check']);
            if($vacancy_id>0){
                $whereArr[]=array('room_id','=',$vacancy_id);
            }
            if($layer_id>0){
                $whereArr[]=array('layer_id','=',$layer_id);
            }
            if($floor_id>0){
                $whereArr[]=array('floor_id','=',$floor_id);
            }
            if($single_id>0){
                $whereArr[]=array('single_id','=',$single_id);
            }
            if($project_id>0){
                $whereArr[]=array('project_id','=',$project_id);
            }
            if(!empty($charge_type)){
                $whereArr[]=array('charge_type','=',$charge_type);
            }
            if($status>0){
                $whereArr[]=array('status','=',$status);
            }
            try {
                $serviceHouseNewPorperty = new HouseNewChargeService();
                $charge_types=$serviceHouseNewPorperty->charge_type;
                $houseVillageCheckauthApplyService = new HouseVillageCheckauthApplyService();
                $houseVillageCheckauthSetService =new HouseVillageCheckauthSetService();
                $orderRefundCheckWhere = array('village_id' => $village_id, 'xtype' => 'order_servicetime_check');
                $orderRefundCheckOpen=$houseVillageCheckauthSetService->isOpenSet($orderRefundCheckWhere);
                $wid=0;
                $check_level_info=array();
                if (in_array($this->login_role, $this->villageOrderCheckRole) && $orderRefundCheckOpen > 0 && isset($this->adminUser['wid']) && ($this->adminUser['wid'] > 0)) {
                    $wid = $this->adminUser['wid'];
                    $userAuthLevel = $houseVillageCheckauthSetService->getOneCheckauthSet($orderRefundCheckWhere);
                    if (!empty($userAuthLevel)) {
                        $check_level_info = array('wid' => $this->adminUser['wid'], 'check_level' => $userAuthLevel['check_level']);
                    }
                }
                $dataRet=$houseVillageCheckauthApplyService->getCheckauthApplyList($charge_types,$check_level_info,$whereArr, '*', $page, $limit);
                $dataRet['login_role']=$this->login_role;
                $dataRet['wid']=$wid;
                return api_output(0,$dataRet);
            }catch (\Exception $e){
                    return api_output_error(-1, $e->getMessage());
            }
        }
    }
