<?php
/**
 * 订单数据
 * Created by : PhpStorm
 * User: wangyi
 * Date: 2021/8/26
 * Time: 10:41
 */

namespace app\new_marketing\controller\platform;


use app\new_marketing\model\service\OrderInfoService;

class OrderInfoController extends AuthBaseController
{
	public function orderList()
	{
		$orderInfoService = new OrderInfoService();

		$team_id     = $this->request->param('team_id', 0, 'intval');//团队id
		$page        = $this->request->param('page', 1, 'intval');
		$pageSize    = $this->request->param('pageSize', 10, 'intval');
//		$person_id   = $this->request->param('person_id', 0, 'intval');//业务员id
		$area_uid    = $this->request->param('area_uid', 0, 'intval');//区域代理id
		$name        = $this->request->param('name', '', 'trim');//商家名称
		$team_name   = $this->request->param('team_name', '', 'trim');//团队名字
		$person_name = $this->request->param('person_name', '', 'trim');//业务员名字
		$property_name = $this->request->param('property_name', '', 'trim');//物业名字
		$orderid     = $this->request->param('orderid', '', 'trim');//订单号
		$type        = $this->request->param('type', -1, 'intval');
		$begin_time  = $this->request->param('begin_time', '', 'trim');
		$end_time    = $this->request->param('end_time', '', 'trim');

		$province_id = $this->request->param('province_id', 0, 'intval');
		$city_id     = $this->request->param('city_id', 0, 'intval');
		$area_id     = $this->request->param('area_id', 0, 'intval');

		$order_type   = $this->request->param('order', 0, 'intval');
		$order_business   = $this->request->param('order_business', -1, 'intval');

		try {
			$where = [];
			$team_id_where = [];
			if ($orderid > 0 || $name || $team_name || $person_name || $property_name) {
				if($orderid > 0){
					$where[] = ['g.orderid', '=', $orderid];
				}
				// 商家名称
				if ($name != '') {
                    $where[] = ['t.order_type', '=', 0];
					$where[] = ['c.name', 'like', '%' . $name . '%'];
				}
				// 物业名称
				if ($property_name != '') {
                    $where[] = ['t.order_type', '=', 1];
					$where[] = ['f.property_name', 'like', '%' . $property_name . '%'];
				}
				// 团队名称
				if ($team_name != '') {
					$where[] = ['mt.name', 'like', '%' . $team_name . '%'];
				}
				// 业务员名称
				if ($person_name != '') {
					$where[] = ['b.name', 'like', '%' . $person_name . '%'];
				}

			} else {//如果有手动收搜索 其他条件作废
				if (!($team_id > 0)) {//团队优先级高
					if ($province_id > 0) {
						$area_where[] = ['province_id', '=', $province_id];
						if ($city_id > 0) {
							$area_where[] = ['city_id', '=', $city_id];
							if ($area_id > 0) {
								$area_where[] = ['area_id', '=', $area_id];
							}
						}
						$area_where [] = ['is_del', '=', 0];
						$area_team_id  = $orderInfoService->teamListByWhere($area_where, 'id')->toarray();
						$area_team_id = array_column($area_team_id,'id');
						if ($area_team_id) {
							$team_id_where = ['t.team_id', 'in', $area_team_id];
						}else{
							$team_id_where = ['t.team_id', '=', -1];
						}
					}

					if ($area_uid > 0) {
						$area_team_id = $orderInfoService->teamListByWhere(['area_uid' => $area_uid, 'is_del' => 0], 'id')->toarray();
						$area_team_id = array_column($area_team_id,'id');
						if ($area_team_id) {
							$team_id_where = ['t.team_id', 'in', $area_team_id];
						}else{
							$team_id_where = ['t.team_id', '=', -1];//空
						}
					}

					if ($team_name) {
						$team_where [] = ['name', 'like', '%' . $team_name . '%'];
						$team_where [] = ['is_del', '=', 0];
						$area_team_id  = $orderInfoService->teamListByWhere($team_where, 'id')->toarray();
						$area_team_id = array_column($area_team_id,'id');
						if ($area_team_id) {
							$team_id_where = ['t.team_id', 'in', $area_team_id];
						}else{
							$team_id_where = ['t.team_id', '=', -1];
						}
					}
					if(!empty($team_id_where)){
						$where[] = $team_id_where;
					}
				}

				// 条件 团队id
				if ($team_id > 0) {
					$where[] = ['t.team_id', '=', $team_id];
				}
				if ($person_name) {
					$where[] = ['b.name', 'like', '%' . $person_name . '%'];
				}

				// 状态类型
				if ($type > -1) {
					$where[] = ['g.order_type', '=', $type];
				}
				//
				if ($order_business > -1) {
					$where[] = ['t.order_type', '=', $order_business];
				}
				// 下单时间
				if ($begin_time != '' && $end_time != '') {
					$arr   = [['g.pay_time', '>=', strtotime($begin_time . ' 00:00:00')], ['g.pay_time', '<=', strtotime($end_time . ' 23:59:59')]];
					$where = array_merge($where, $arr);
				}

			}
			// 字段
			$field = 'g.*, b.name as per_name, c.name as mer_name,f.property_name,t.order_type order_business';

			$order = 'g.order_id DESC';

			if($order_type == 1){
				$order = 'g.pay_time desc';
			}elseif($order_type == 2){
				$order = 'g.pay_time asc';
			}

			$arr   = $orderInfoService->getOrderList($where, $field, $order, $page, $pageSize);
			return api_output(0, $arr, 'success');
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
	}

	public function getOrderInfo()
	{
		$orderInfoService  = new OrderInfoService();
		$param['order_id'] = $this->request->param('order_id', '', 'trim');//订单号

		try {
			if (empty($param['order_id'])) {
				return api_output_error(1003, '订单id错误');
			}
			$arr = $orderInfoService->getOrderInfo($param);
			return api_output(0, $arr, 'success');
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
	}

	/**
	 * @return \json
	 * 通过区域代理获取区域代理
	 */
	public function getAreaUidByProvince()
	{
		$orderInfoService = new OrderInfoService();
		$province_id      = $this->request->param('province_id', 0, 'intval');
		$city_id          = $this->request->param('city_id', 0, 'intval');
		$area_id          = $this->request->param('area_id', 0, 'intval');

		try {
			$where [] = ['p.is_del', '=', 0];
			$where [] = ['p.is_agency', '=', 1];
			if ($province_id > 0) {
				$where[] = ['pa.province_id', '=', $province_id];
			}
			if ($city_id > 0) {
				$where[] = ['pa.city_id', '=', $city_id];
			}
			if ($area_id > 0) {
				$where[] = ['pa.area_id', '=', $area_id];
			}
			$area_uid = $orderInfoService->getListByWhere($where, 'p.id,p.name');

			return api_output(0, $area_uid, 'success');
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
	}

	/**
	 * @return \json
	 * 通过区域代理获取团队id
	 */
	public function getTeamIdByAreaUid()
	{
		$orderInfoService = new OrderInfoService();
		$area_uid         = $this->request->param('area_uid', -1, 'intval');

		try {
			$area_team_id = $orderInfoService->teamListByWhere(['area_uid' => $area_uid, 'is_del' => 0], 'id,name');

			return api_output(0, $area_team_id, 'success');
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
	}
}