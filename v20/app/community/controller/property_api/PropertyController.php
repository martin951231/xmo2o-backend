<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      物业
	 */

	namespace app\community\controller\property_api;

	use app\common\model\service\AreaService;
	use app\community\controller\CommunityBaseController;
	use app\community\model\service\HousePropertyDigitService;
	use app\community\model\service\HousePropertyService;
	use app\community\model\service\PropertyAdminService;
	use app\community\model\service\AdminLoginService;
    use app\community\model\service\HousePropertyVillageWorkerService;
	use app\traits\CommonLogTraits;
    use app\traits\house\AdminLoginTraits;
	use token\Token;

	class PropertyController extends CommunityBaseController
	{
		use CommonLogTraits;
        use AdminLoginTraits;
		/**
		 * 当前物业登录角色的的 id
		 * @var int
		 */
		protected $nowPropertyUserId = 0;

		public function initialize()
		{
			parent::initialize();
			$tokenInfo = Token::checkToken(Token::getToken());
			if ($tokenInfo){
				$this->nowPropertyUserId = $tokenInfo['memberId'];
			}
		}

		/**
		 * 修改密码
		 */
		public function passwordChange()
		{
			$property_id = $this->adminUser['property_id'];
			if (!$property_id) {
				return api_output(1002, [], "物业信息不存在");
			}

			$property = (new HousePropertyService())->getFind(['id'=>$property_id] );
			if (empty($property)){
				return api_output(1003, [], "非法访问");
			}

			$password = $this->request->post('password','');
			$confirm_password = $this->request->post('confirm_password','');
			$oldPassword = $this->request->post('old_password','');

			if (mb_strlen($password) < 6){
				return api_output(1003, [], "密码最少6位!");
			}

			if ($password !== $confirm_password){
				return api_output(1003, [], "新密码和确认密码不一致!");
			}

			if ($password == $oldPassword){
				return api_output(1003, [], "新密码和原密码不能一致!");
			}

			$adminLogService = new AdminLoginService();

			if (!empty($password) && in_array($this->login_role,$adminLogService->propertyUserArr) && $this->nowPropertyUserId > 0 ) { //普通管理员
				$propertyAdminInfo = (new PropertyAdminService())->getFind(['id'=>$this->nowPropertyUserId]);
				if ($propertyAdminInfo['pwd'] !== md5($oldPassword)){
					return api_output(1003, [], "原密码不正确!");
				}

				$where = [
					'id' => $this->nowPropertyUserId
				];
                $newPwd= md5($password);
				$data = [
					'pwd' =>$newPwd
				];
                $res= (new PropertyAdminService())->editData($where,$data);
				if($res){
                    if(isset($propertyAdminInfo['wid']) && $propertyAdminInfo['wid']>0){
                        $housePropertyVillageWorkerService=new HousePropertyVillageWorkerService();
                        $housePropertyVillageWorkerService->synWorkerToPropertyWorkerInfo($propertyAdminInfo['wid'],$newPwd,'password',$property_id);
                    }
					$queuData = [
						'logData' => [
							'tbname' => '物业普通管理员',
							'table'  => 'property_admin',
							'client' => '物业后台',
							'trigger_path' => '物业设置->修改密码',
							'trigger_type' => $this->getUpdateNmae(),
							'addtime'      => time(),
							'property_id'   => $property_id
						],
						'newData' => $data,
						'oldData' => $propertyAdminInfo
					];
					$this->laterLogInQueue($queuData);

					return api_output(0, [], "修改密码成功");
				}
				return api_output(1003, [], "修改密码失败!");

			}else{ //这里是要判断，进入是物业创始人理员权限修改

				if (!empty($password)){
					if ($property['password'] !== md5($oldPassword)){
						return api_output(1003, [], "原密码不正确!");
					}
					$where = [
						'id' => $property_id
					];
					$data = [
						'password' => md5($password)
					];
					if ( (new HousePropertyService())->editData($where,$data)){
						$queuData = [
							'logData' => [
								'tbname' => '物业信息表',
								'table'  => 'house_property',
								'client' => '物业后台',
								'trigger_path' => '物业设置->修改密码（创始人)',
								'trigger_type' => $this->getUpdateNmae(),
								'addtime'      => time(),
								'property_id'   => $property_id
							],
							'newData' => $data,
							'oldData' => $property
						];
						$this->laterLogInQueue($queuData);
						return api_output(0, [], "修改密码成功");
					}
					return api_output(1003, [], "修改密码失败!");
				}
			}


		}

		/**
		 * 省
		 * @return \json
		 */
		public function ajaxProvince()
		{
			$data = (new AreaService())->getSelectProvince();
			if(isset($data['list']) && !empty($data['list'])){
				$tmp = [];
				foreach ($data['list'] as $k=>$d){
					$tmp[$k]['area_id']     = $d['area_id'];
					$tmp[$k]['area_name']   = $d['area_name'];
				}
				$data['list'] = $tmp;
			}
			return api_output(0, $data, "成功");
		}

		/**
		 * 区
		 * @return \json
		 */
		public function ajaxArea()
		{
			$params['id'] = $this->request->post('id',0);
			$params['name'] = $this->request->post('name','');
			$data = (new AreaService())->getSelectArea($params);
			if(isset($data['list']) && !empty($data['list'])){
				$tmp = [];
				foreach ($data['list'] as $k=>$d){
					$tmp[$k]['area_id']     = $d['area_id'];
					$tmp[$k]['area_name']   = $d['area_name'];
				}
				$data['list'] = $tmp;
			}
			return api_output(0, $data, "成功");
		}

		/**
		 * 市
		 * @return \json
		 */
		public function ajaxCity()
		{
			$params['id'] = $this->request->post('id',0);
			$params['type'] = $this->request->post('type',0);
			$params['name'] = $this->request->post('name','');
			$data = (new AreaService())->getSelecCity($params);
			if(isset($data['list']) && !empty($data['list'])){
				$tmp = [];
				foreach ($data['list'] as $k=>$d){
					$tmp[$k]['area_id']     = $d['area_id'];
					$tmp[$k]['area_name']   = $d['area_name'];
				}
				$data['list'] = $tmp;
			}
			return api_output(0, $data, "成功");
		}


		/**
		 * 物业基本信息
		 * @return \json
		 */
		public function config()
		{
			$property_id = $this->adminUser['property_id'];
			if (!$property_id) {
				return api_output(1002, [], "物业信息不存在");
			}
			$field = "id,account,property_name,create_time,property_phone,property_address,property_logo,long,lat,province_id,city_id,area_id,village_num,property_short_name,set_name_time,set_short_name_time,is_virtual";
			$config = (new HousePropertyService())->getFind(['id'=>$property_id] ,$field );
			if (empty($config)){
				return api_output(1003, [], "非法访问");
			}
			$config['house_property_login'] = '';
			if (cfg('property_self_logo')==1) {
				// 开启了自定义URL
				$house_property_login = cfg('site_url').'/v20/public/platform/#/user/community/login?property='.$property_id;
				$config['house_property_login'] = $house_property_login;
			}

			if (!empty($config['property_logo'])){
				$config['property_logo'] = replace_file_domain($config['property_logo']);
			}

			return api_output(0, $config, "成功");

		}

		public function saveConfig()
		{
			$property_id = $this->adminUser['property_id'];
			if (!$property_id) {
				return api_output(1002, [], "物业信息不存在");
			}
			$property = (new HousePropertyService())->getFind(['id'=>$property_id] );
			if (empty($property)){
				return api_output(1003, [], "非法访问");
			}
			$property = $property->toArray();

			if ($this->request->isPost()){

				$province_id    = $this->request->post('province_id',0);
				$city_id        = $this->request->post('city_id',0);
				$area_id        = $this->request->post('area_id',0);
				$property_name  = $this->request->post('property_name','');
				$property_short_name  = $this->request->post('property_short_name','');
		        $long           = $this->request->post('long',0);
		        $lat            = $this->request->post('lat',0);
				$property_address= $this->request->post('property_address','');
				$property_phone = $this->request->post('property_phone','');
				$property_logo  = $this->request->post('property_logo','');

				$data = [];
				if($province_id){
					$data['province_id'] = $province_id;
				}
				if($city_id){
					$data['city_id'] = $city_id;
				}
				if($area_id){
					$data['area_id'] = $area_id;
				}
				if($property_logo){
					$data['property_logo'] = $property_logo;
				}
				if ($property['set_name_time'] < 1 ){
					if (!empty($property_name) && trim($property_name)!=trim($property['property_name']) ) {
						$data['property_name'] = trim($property_name);
						$data['set_name_time'] = time();
					} elseif ($this->request->has('property_name','post') && empty($property_name)) {
						return api_output(1003, [], "请填写物业名称");
					}
				}

				if($property['set_name_time'] > 1 && $this->request->has('property_name','post')){
					return api_output(1003, [], "名称修改次数已用完,请联系平台修改");
				}

				if ($property['set_short_name_time'] < 1){
					if (!empty($property_short_name) && trim($property_short_name)!=trim($property['property_short_name'])) {
						$data['property_short_name'] = trim($property_short_name);
						$data['set_short_name_time'] = time();
					}
				}

				if($property['set_short_name_time'] > 1 && $this->request->has('property_short_name','post')){
					return api_output(1003, [], "简称修改次数已用完,请联系平台修改");
				}

				$data['long'] = floatval($long);
				$data['lat'] = floatval($lat);
				$data['property_address'] = $property_address;
				$data['property_phone'] = $property_phone;

				$res = (new HousePropertyService())->editData(['id'=>$property_id],$data);

				if($res){
					$queuData = [
						'logData' => [
							'tbname' => '物业信息表',
							'table'  => 'house_property',
							'client' => '物业后台',
							'trigger_path' => '物业设置->基本信息->基本设置',
							'trigger_type' => $this->getUpdateNmae(),
							'addtime'      => time(),
							'property_id'   => $property_id
						],
						'newData' => $data,
						'oldData' => $property
					];
					$this->laterLogInQueue($queuData);
					return api_output(0, [], "信息编辑成功");
				}else{
					return api_output(1003, [], "编辑失败!");
				}
			}

		}

		/**
		 * 小数位+账单作废时限
		 */
		public function digit()
		{
			$property_id = $this->adminUser['property_id'];
			if (!$property_id) {
				return api_output(1002, [], "物业信息不存在");
			}
			$info = (new HousePropertyDigitService())->get_one_digit(['property_id'=>$property_id]);
			return api_output(0, $info, "成功");
		}


		public function saveDigit()
		{
			$property_id = $this->adminUser['property_id'];
			if (!$property_id) {
				return api_output(1002, [], "物业信息不存在");
			}
			$info = (new HousePropertyDigitService())->get_one_digit(['property_id'=>$property_id]);


			$other_digit    = $this->request->post('other_digit',0);
			$meter_digit    = $this->request->post('meter_digit',0);
			$deleteBillMin  = $this->request->post('deleteBillMin',30);
			$type           = $this->request->post('type',1);

			if($other_digit > 4 || $meter_digit > 4){
				return api_output(1002, [], "参数设置小数数位不能大于4");
			}
			$digit_info['type']         = $type;
			$digit_info['other_digit']  = $other_digit || strlen($other_digit) > 0 ? $other_digit : 2;
			$digit_info['property_id']  = $property_id;
			$digit_info['meter_digit']  = $meter_digit || strlen($meter_digit) > 0 ? $meter_digit : 2;
			$digit_info['deleteBillMin'] = $deleteBillMin;


			if($info){
				$digit_info['id'] = $info['id'];
				(new HousePropertyDigitService())->updateDigit($digit_info);
				$queuData = [
					'logData' => [
						'tbname' => '物业参数设置表',
						'table'  => 'house_property_digit',
						'client' => '物业后台',
						'trigger_path' => '物业设置->基本信息->参数设置',
						'trigger_type' => $this->getUpdateNmae(),
						'addtime'      => time(),
						'property_id'   => $property_id
					],
					'newData' => $digit_info,
					'oldData' => $info
				];
				$this->laterLogInQueue($queuData);
			}else{
				$digit_info['add_time'] = time();
				(new HousePropertyDigitService())->addDigit($digit_info);
				$queuData = [
					'logData' => [
						'tbname' => '物业参数设置表',
						'table'  => 'house_property_digit',
						'client' => '物业后台',
						'trigger_path' => '物业设置->基本信息->参数设置',
						'trigger_type' => $this->getAddLogName(),
						'addtime'      => time(),
						'property_id'   => $property_id
					],
					'newData' => $digit_info,
					'oldData' => []
				];
				$this->laterLogInQueue($queuData);
			}
			return api_output(0, [], "信息编辑成功");
		}
	}