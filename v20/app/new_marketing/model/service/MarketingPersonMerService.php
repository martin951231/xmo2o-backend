<?php
/**
 * 商家绑定业务员管理service
 * User: wangchen
 * Date: 2021/8/24
 */
namespace app\new_marketing\model\service;

use app\community\model\service\PackageOrderService;
use app\merchant\model\service\MerchantStoreService;
use app\new_marketing\model\db\NewMarketingPersonMer;
use app\new_marketing\model\db\NewMarketingOrder;
use app\new_marketing\model\db\NewMarketingTeam;
use app\new_marketing\model\db\NewMarketingPerson;
use app\new_marketing\model\db\NewMarketingPersonSalesman;
use think\db\Where;
use think\route\Rule;

class MarketingPersonMerService
{
	// 注册商家列表
	public function teamManagementMerchantList($where, $field, $order, $page, $pageSize){
		$list = (new NewMarketingPersonMer())->teamManagementMerchantList($where, $field, $order, $page, $pageSize);
		foreach($list['list'] as $k=>$v){
			// 注册时间
			$list['list'][$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
			// 已购买店铺数量/订单总金额
			$orderList = (new NewMarketingOrder())->where(['mer_id'=>$v['mer_id']])->select();
			$total_price = 0;
			$total_num = 0;
			foreach($orderList as $vs){
				$total_price = $total_price + $vs['total_price'];
				$total_num = $total_num + $vs['total_num'];
			}
			$list['list'][$k]['total_price'] = $total_price;
			$list['list'][$k]['total_num'] = $total_num;
			// 订单数量
			$orderCoutn = (new NewMarketingOrder())->where(['mer_id'=>$v['mer_id']])->count();
			$list['list'][$k]['order_num'] = $orderCoutn;
		}
		return $list;
	}

	// 商家业务转移团队成员列表
	public function teamManagementMerchantTransferList($params){
		if($params['team_id'] > 0){
			$listTop = (new NewMarketingTeam())->where([['is_del','=',0],['id','=',$params['team_id']]])->field('id,name')->select()->toArray();
			$lists = (new NewMarketingTeam())->where([['is_del','=',0],['id','<>',$params['team_id']]])->field('id,name')->select()->toArray();
			$list = array_merge($listTop, $lists);
			foreach($list as $k=>$v){
				$personList = (new NewMarketingPersonSalesman())->getCanSalesmanFind(['g.team_id'=>$v['id']], 'b.*');
				$list[$k]['lists'] = $personList;
			}
		}else{
			$list = (new NewMarketingTeam())->where([['is_del','=',0]])->field('id,name')->select();
			foreach($list as $k=>$v){
				$personList = (new NewMarketingPersonSalesman())->getCanSalesmanFind(['g.team_id'=>$v['id']], 'b.*');
				if($personList){
					$personLists = $personList;
				}else{
					$personLists = [];
				}
				$list[$k]['lists'] = $personLists;
			}
		}
		return $list;
	}

	// 商家业务转移操作
	public function teamManagementMerchantTransferCreate($params){
		$list = (new NewMarketingPersonMer())->where(['id'=>$params['id']])->update(['person_id'=>$params['person_id'],'team_id'=>$params['team_id']]);
		return $list;
	}

	/**
	 * 获得商家列表
	 */
	public function getMerchantList($params){
		$where = [];
		$page = $params['page'] ?? 1;
		$pageSize = $params['pageSize'] ?? 10;

		// 区域搜索
		if(isset($params['area']) && $params['area']){
			$area = $params['area'];
			if(isset($area[0]) && $area[0]){
				$where[] = ['team.province_id', '=', $area[0]];// 省份
				if(isset($area[1]) && $area[1]){
					$where[] = ['team.city_id', '=', $area[1]];// 城市
					if(isset($area[2]) && $area[2]){
						$where[] = ['team.area_id', '=', $area[2]];// 区域
					}
				}
			}
		}

		// 区域代理
		if(isset($params['area_uid']) && $params['area_uid']){
			$where[] = ['team.area_uid', '=', $params['area_uid']];
		}

		// 注册开始时间
		if(isset($params['begin_time']) && $params['begin_time']){
			$where[] = ['m.reg_time', '>=', strtotime($params['begin_time'])];
		}

		// 注册结束时间
		if(isset($params['end_time']) && $params['end_time']){
			$where[] = ['m.reg_time', '<=', strtotime($params['end_time'])+86400];
		}

		// 商家名
		if(isset($params['merchant_name']) && $params['merchant_name']){
			$where[] = ['m.name', 'like', '%'.$params['merchant_name'].'%'];
		}

		// 商家状态
		$where[] = ['m.status', '<>', 4];

		// 业务员
		if(isset($params['user_name']) && $params['user_name']){
			$where[] = ['p.name', 'like', '%'.$params['user_name'].'%'];
		}

		$field = 'm.name as merchant_name,m.reg_time,m.mer_id,p.name as user_name,pm.id,pm.person_id';
		$order = [
			'm.reg_time' => 'DESC'
		];

		$list = (new NewMarketingPersonMer())->getMerchantList($where, $field, $order, $page, $pageSize);
		$count = (new NewMarketingPersonMer())->getMerchantCount($where);
	
		foreach($list as $k=> &$_merchant){
			$_merchant['reg_time'] = date('Y-m-d H:i:s', $_merchant['reg_time']);
			$_merchant['store_count_buy'] = 0;// 购买店铺数
			$_merchant['store_count_used'] = 0;// 创建店铺数
			$_merchant['store_count_overdue'] = 0;// 过期店铺数
			$_merchant['total_money'] = 0;// 订单总金额

			// 获得订单列表
			$where = [
				'mer_id' => $_merchant['mer_id'],
				'paid' => 1,
			];
			$_merchant['total_money'] = (new MarketingOrderService)->getSum($where);
			
			// 商家id
			$where = ['mer_id'=> $_merchant['mer_id']];
			
			// 购买店铺数
			$_merchant['store_count_used']  =(new MarketingStoreService)->getUsedStoreNumber($where);
			// 创建店铺数
			$_merchant['store_count_buy']  =(new MarketingStoreService)->getStoreNumber($where);
			
			$where = [
				['mer_id', '=', $_merchant['mer_id']],
				['end_time', '<', time()],
				['status', '<>', 4],
			];
			// 过期店铺数
			$_merchant['store_count_overdue']  =(new MerchantStoreService)->getCount($where);
		}

		$returnArr['list'] = $list;
		$returnArr['count'] = $count;
		return $returnArr;
	}

	/**
	 * @return mixed
	 * 用户端 店铺列表
	 */
	public function getUserMerchantList($params,$where){
		$page = $params['page'] ?? 1;
		$pageSize = $params['pageSize'] ?? 10;

		// 商家状态
		$where[] = ['m.status', '<>', 4];

		$field = 'm.name as merchant_name,m.reg_time,m.mer_id,p.name as user_name,pm.id,pm.person_id';
		$order = [
			'm.reg_time' => 'DESC'
		];
        $where[] = ['pm.status', '=', 0];
		$list = (new NewMarketingPersonMer())->getMerchantList($where, $field, $order, $page, $pageSize);
		$count = (new NewMarketingPersonMer())->getMerchantCount($where);

		foreach($list as $k=> &$_merchant){
			$_merchant['reg_time'] = date('Y-m-d', $_merchant['reg_time']);

			$store_where = [
				['mer_id', '=', $_merchant['mer_id']],
				['status', '=', 1],
                ['end_time', '>', 1]
			];

			$count_arr = (new MerchantStoreService)->getMerStoreInfo($store_where,'end_time as effect_time');
			$_merchant['merchant_info'] = '';
			$_merchant['merchant_info_status'] = 0;
			if($count_arr['count1'] > 0){
				$_merchant['merchant_info'] .= $count_arr['count1'].'个店铺已过期 ';
				$_merchant['merchant_info_status'] = 1;
			}
			if($count_arr['count2'] > 0){
				$_merchant['merchant_info'] .= $count_arr['count2'].'个店铺即将过期 ';
				$_merchant['merchant_info_status'] = 2;
			}

			// 获得订单列表
			$where = [
				'mer_id' => $_merchant['mer_id'],
				'paid' => 1,
			];
			$_merchant['order_num']   = (new MarketingOrderService)->getCount($where);

			$where = [
				['mer_id', '=', $_merchant['mer_id']],
				['end_time', '<', time()],
				['status', '<>', 4],
			];
			// 过期店铺数
			$_merchant['store_count_overdue']  =(new MerchantStoreService)->getCount($where);

			// 商家id
			$where = ['mer_id'=> $_merchant['mer_id']];

			// 购买店铺数
			$_merchant['store_count_buy']  =(new MarketingStoreService)->getStoreNumber($where);
			$_merchant['merchant_number']  = $_merchant['mer_id'];
			// 创建店铺数
			$_merchant['store_count_used'] = (new MarketingStoreService)->getUsedStoreNumber($where);
		}

		$returnArr['list'] = $list;
		$returnArr['count'] = $count;
		return $returnArr;
	}


	/**
	 * 获得物业列表
	 */
	public function getHousePropertyList($params){
		$where = [];
		$page = $params['page'] ?? 1;
		$pageSize = $params['pageSize'] ?? 10;

		// 区域搜索
		if(isset($params['area']) && $params['area']){
			$area = $params['area'];
			if(isset($area[0]) && $area[0]){
				$where[] = ['team.province_id', '=', $area[0]];// 省份
				if(isset($area[1]) && $area[1]){
					$where[] = ['team.city_id', '=', $area[1]];// 城市
					if(isset($area[2]) && $area[2]){
						$where[] = ['team.area_id', '=', $area[2]];// 区域
					}
				}
			}
		}

		// 区域代理
		if(isset($params['area_uid']) && $params['area_uid']){
			$where[] = ['team.area_uid', '=', $params['area_uid']];
		}

		// 注册开始时间
		if(isset($params['begin_time']) && $params['begin_time']){
			$where[] = ['m.create_time', '>=', strtotime($params['begin_time'])];
		}

		// 注册结束时间
		if(isset($params['end_time']) && $params['end_time']){
			$where[] = ['m.create_time', '<=', strtotime($params['end_time'])+86400];
		}

		// 物业名
		if(isset($params['property_name']) && $params['property_name']){
			$where[] = ['m.property_name', 'like', '%'.$params['property_name'].'%'];
		}

		// 商家状态
		$where[] = ['m.status', '<>', 4];

		// 业务员
		if(isset($params['user_name']) && $params['user_name']){
			$where[] = ['p.name', 'like', '%'.$params['user_name'].'%'];
		}

		$field = 'm.property_name,m.property_phone,m.property_address,m.create_time,m.id as property_id,p.name as user_name,pm.id,pm.person_id';
		$order = [
			'm.create_time' => 'DESC'
		];

		$list = (new NewMarketingPersonMer())->getPropertyList($where, $field, $order, $page, $pageSize);

		foreach($list['list'] as $k=> &$_property){
			$_property['reg_time'] = date('Y-m-d H:i:s', $_property['create_time']);
			$_property['order_count'] = 0;// 订单数量
			$_property['total_money'] = 0;// 订单总金额

			// 获得订单列表
			$where = [
				'property_id' => $_property['property_id'],
				'status' => 1,
			];
			$_property['total_money'] = (new PackageOrderService)->getSum($where);
			$_property['order_count'] = (new PackageOrderService)->getCount($where);
		}

		return $list;
	}

    /**
     * 获得一条数据
     * @return array
     */
    public function getOne($where = [], $field = true, $order = []){
        $list = (new NewMarketingPersonMer())->getOne($where,$field,$order);
        if(!$list) {
            return [];
        }
        return $list->toArray();
    }
	/**
	 * @return mixed
	 * 用户端 店铺列表
	 */
	public function getUserMerchantInfo($merId){
		$where = [];
		// 商家状态
		$where[] = ['m.status', '<>', 4];
		$where[] = ['m.mer_id', '=', $merId];

		$field = 'm.name as merchant_name,m.reg_time,m.mer_id,p.name as user_name,pm.id,pm.person_id,m.phone merchant_phone,m.address merchant_address';
		$order = [
			'm.reg_time' => 'DESC',
		];
		$list  = (new NewMarketingPersonMer())->getMerchantInfo($where, $field, $order);

		$list['reg_time'] = date('Y-m-d', $list['reg_time']);

		$store_where = [
			['mer_id', '=', $list['mer_id']],
			['status', '=', 1],
		];

		$count_arr             = (new MerchantStoreService)->getMerStoreInfo($store_where, 'end_time as effect_time');
		$list['merchant_info'] = '';
		$list['merchant_info_status'] = 0;
		if ($count_arr['count1'] > 0) {
			$list['merchant_info'] .= $count_arr['count1'] . '个店铺已过期 ';
			$list['merchant_info_status'] = 1;
		}
		if ($count_arr['count2'] > 0) {
			$list['merchant_info'] .= $count_arr['count2'] . '个店铺即将过期 ';
			$list['merchant_info_status'] = 2;
		}

		// 商家id
		$where = ['mer_id' => $list['mer_id']];

		// 购买店铺数
		$list['store_count_buy'] = (new MarketingStoreService)->getStoreNumber($where);
		// 创建店铺数
		$list['store_count_used'] = (new MarketingStoreService)->getUsedStoreNumber($where);
		$list['store_count_disused'] = $list['store_count_buy'] - $list['store_count_used'] > 0 ? $list['store_count_buy'] - $list['store_count_used'] : 0;
		$list['merchant_number']  = $list['mer_id'];
		// 获得订单列表
		$where             = [
			'mer_id' => $list['mer_id'],
			'paid'   => 1,
		];
		$list['order_num'] = (new MarketingOrderService)->getCount($where);

		$where = [
			['mer_id', '=', $list['mer_id']],
			['end_time', '<', time()],
			['status', '<>', 4],
		];
		// 过期店铺数
		$list['store_count_overdue'] = (new MerchantStoreService)->getCount($where);

		return $list;
	}

    /**
     * 获取商家绑定业务员ID
     */
    public function getPersonId($merId) {
        $person_id = (new NewMarketingPersonMer())->where(['mer_id' => $merId])->limit(1)->value('person_id');
        return $person_id;
    }

}