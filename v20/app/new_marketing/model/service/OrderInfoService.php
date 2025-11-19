<?php
/**
 * Created by : PhpStorm
 * User: wangyi
 * Date: 2021/8/26
 * Time: 10:50
 */

namespace app\new_marketing\model\service;


use app\new_marketing\model\db\NewMarketingOrder;
use app\new_marketing\model\db\NewMarketingPerson;
use app\new_marketing\model\db\NewMarketingTeam;

class OrderInfoService
{
	public function getOrderList($where, $field, $order, $page, $pageSize){
		$marketingOrderService = new MarketingOrderService();

		$data = $marketingOrderService->getOrderInfo($where, $field, $order, $page, $pageSize);

		foreach($data['list'] as $k=>$v){
			// 下单时间
			$data['list'][$k]['place_time'] = $v['pay_time'];
			$data['list'][$k]['total_num']  = $v['buy_num'];
			$data['list'][$k]['buy_num']    = $v['store_num'];
		}

		$data['areaList']=(new RegionalAgencyService())->ajax_province();
		return $data;
	}

	public function getOrderInfo($param){
		$marketingOrderService = new MarketingOrderService();

		return $marketingOrderService->teamManagementSavageDetail($param);
	}

	public function teamListByWhere($where,$field){
		$newMarketingTeam = new NewMarketingTeam();

		return $newMarketingTeam->getSome($where,$field);
	}

	public function getListByWhere($where,$field){
		$newMarketingPerson = new NewMarketingPerson();

		return $newMarketingPerson->getAgencyListByWhere($where,$field);
	}
}