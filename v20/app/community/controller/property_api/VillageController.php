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

	namespace app\community\controller\property_api;

	use app\community\controller\CommunityBaseController;
	use app\community\model\service\HouseVillageService;

	class VillageController extends CommunityBaseController
	{

		public function villageList()
		{
			// 获取登录信息
			$property_id =  $this->adminUser['property_id'];
			if (empty($property_id)){
				return api_output(1002, [], '请先登录到物业后台！');
			}

			$village_name   = isset($this->request->village_name)   ? $this->request->village_name  : NULL;
			$province_id    = isset($this->request->province_id)    ? $this->request->province_id   : 0;
			$city_id        = isset($this->request->city_id)        ? $this->request->city_id       : 0;
			$area_id        = isset($this->request->area_id)        ? $this->request->area_id       : 0;

			$condition_where = [];

			$villageService = new HouseVillageService();

			$page = $this->request->param('page',0,'intval');

			$condition_where[]  = ['property_id', '=', $property_id];
			$condition_where[]  = ['status','<>',5];
			$count = $villageService->getVillageNum($condition_where);
			$dataList = $villageService->getList($condition_where,true,$page);

			$data = [
				'dataList' => $dataList,
				'count' => $count
			];

			return api_output(0, $data , '获取成功');
		}

	}