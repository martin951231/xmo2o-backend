<?php
/**
 * 订单管理service
 * User: wangchen
 * Date: 2021/8/24
 */
namespace app\new_marketing\model\service;

use app\merchant\model\db\MerchantStore;
use app\merchant\model\service\MerchantMoneyListService;
use app\new_marketing\model\db\NewMarketingBusinessPerson;
use app\new_marketing\model\db\NewMarketingOrder;
use app\new_marketing\model\db\NewMarketingClassRegion;
use app\new_marketing\model\db\NewMarketingPackage;
use app\new_marketing\model\db\NewMarketingPackageRegion;
use app\new_marketing\model\db\NewMarketingPerson;
use app\new_marketing\model\db\NewMarketingOrderTypeArtisan;
use app\new_marketing\model\db\NewMarketingOrderTypeStore;
use app\new_marketing\model\db\NewMarketingOrderTypePerson;
use app\new_marketing\model\db\NewMarketingOrderType;
use app\new_marketing\model\db\NewMarketingArtisan;
use app\new_marketing\model\db\NewMarketingPersonSalesman;
use app\new_marketing\model\db\NewMarketingTeam;
use app\new_marketing\model\db\MerchantCategory as NewMerchantCategory;
use app\common\model\db\Merchant;
use app\common\model\db\Area;
use app\common\model\db\MerchantCategory;
use app\common\model\service\UserService;
use app\merchant\model\service\MerchantService;
use app\merchant\model\service\MerchantStoreService;
use app\merchant\model\service\pay\PayService;
use app\recruit\model\db\NewRecruitResumeLog;
use think\db\Where;
use think\route\Rule;

class MarketingOrderService
{	
	// 订单类型
	public $orderTypeArr = null;

	// 订单所属业务
	public $orderBusinessArr = null;

    public function __construct()
    {
        $this->orderTypeArr = [
			0 => L_('新订单'),
			1 => L_('续费订单'),
		];
		
        $this->orderBusinessArr = [
			0 => L_('店铺'),
			1 => L_('社区'),
		];
    }
	// 团队业绩详情列表
	public function teamManagementSavage($where, $field, $order, $page, $pageSize){
		$list = (new NewMarketingOrder())->teamManagementSavage($where, $field, $order, $page, $pageSize);
		foreach($list['list'] as $k=>$v){
			// 下单时间
			$list['list'][$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
			$list['list'][$k]['pay_time'] = date('Y-m-d H:i:s',$v['pay_time']);
			// 业务员名称
			if(!$v['per_name']){
				$list['list'][$k]['per_name'] = '';
			}
			// 订单类型
			if($v['order_type']==0){
				$list['list'][$k]['order_type_status'] = '新订单';
			}else{
				$list['list'][$k]['order_type_status'] = '续费订单';
			}
			// 订单业务
			if($v['order_business']==0){
				$list['list'][$k]['order_business_status'] = '店铺';
			}else{
				$list['list'][$k]['order_business_status'] = '社区';
				// $list['list'][$k]['mer_name'] = $list['list'][$k]['property_name'];
			}
			// 下单店铺
			if($v['pack_region_id'] > 0){
				$pack_region_id = (new NewMarketingPackageRegion())->where(['id'=>$v['pack_region_id']])->find();
				$package = (new NewMarketingPackage())->where(['id'=>$pack_region_id['package_id']])->find();
				$list['list'][$k]['store_name'] = $package['name'] ? $package['name'] : '';
			}elseif($v['class_region_id'] > 0){
				$class_region_id = (new NewMarketingClassRegion())->where(['id'=>$v['class_region_id']])->find();
				if($class_region_id){
					$cate = (new MerchantCategory())->where(['cat_id'=>$class_region_id['class_id']])->field('cat_id,cat_fid,cat_name')->find();
					if($cate){
						$cate_name = $cate['cat_name'];
						if($cate['cat_fid'] > 0){
							$catef = (new MerchantCategory())->where(['cat_id'=>$cate['cat_fid']])->field('cat_id,cat_name')->find();
							if($catef){
								$cate_name = $catef['cat_name'].'-'.$cate['cat_name'];
							}else{
								$cate_name = $cate['cat_name'];
							}
						}
					}else{
						$cate_name = '';
					}
					$list['list'][$k]['store_name'] = $cate_name;
				}else{
					$list['list'][$k]['store_name'] = '';
				}
			}elseif($v['store_id']){
				$store = (new MerchantStore())->where(['store_id'=>$v['store_id']])->field('name')->find();
				if($store){
					$list['list'][$k]['store_name'] = $store['name'];
				}else{
					$list['list'][$k]['store_name'] = '';
				}
			}else{
				$list['list'][$k]['store_name'] = $v['class_region_id'] ? $v['class_region_id'] : '';
			}

		}
		return $list;
	}

	// 店铺订单详情
	public function teamManagementSavageDetail($params){
		$list = (new NewMarketingOrder())->where(['order_id'=>$params['order_id']])->find();
		if(!$list){
			throw new \think\Exception(L_('订单不存在'), 1001);
		}
		$list['mer_number'] = '123456789';
		$list['mer_info'] = '';
		// 商家名称/手机/地址
		$mer_name = (new Merchant())->where(['mer_id'=>$list['mer_id']])->field('name,phone,province_id,city_id,area_id,address')->find();
		if($mer_name){
			$area = (new Area())->where([['area_id','in',[$mer_name['province_id'],$mer_name['city_id'],$mer_name['area_id']]]])->field('area_id,area_name')->select()->toArray();
			$areaList = '';
			foreach($area as $v){
				$areaList = $areaList.$v['area_name'];
			}
			$list['mer_name'] = $mer_name['name'] ? $mer_name['name'] : '';
			$list['mer_phone'] = $mer_name['phone'] ? $mer_name['phone'] : '';
			$list['mer_address'] = $mer_name['address'] ? $areaList.$mer_name['address'] : '';
		}else{
			$list['mer_name'] = '';
			$list['mer_phone'] = '';
			$list['mer_address'] = '';
		}
		// 订单信息
		$list['shop_num'] = $list['store_num'];
		$list['total_num'] = $list['buy_num'];
		// 支付时间
		$list['pay_time'] = date('Y-m-d H:i:s',$list['pay_time']);
		// 支付方式
		$list['pay_type'] = $this->pay_type($list['pay_type'],$list['paid']);
		$mer_info = '';
		// 下单店铺
		if($list['pack_region_id'] > 0){
			$list['pack_name'] = $list['pack_name'] ? $list['pack_name'] : '';
			$packageRegion = (new NewMarketingPackageRegion())->where(['id'=>$list['pack_region_id'],'status'=>1])->find();
			$packageFind = (new NewMarketingPackage())->where(['id'=>$packageRegion['package_id']])->find();
			$all_num = 0;
			$pack_detail = [];
			if($packageFind){
				$store_detail = json_decode($packageFind['store_detail']);
				foreach($store_detail as $k=>$v){
					$v = $this->object_array($v);
					$all_num = $all_num + $v['num'];
					$catId = (new MerchantCategory())->where(['cat_id'=>$v['type']])->find();
					if($catId){
						if($catId['cat_fid'] > 0){
							$catFid = (new MerchantCategory())->where(['cat_id'=>$catId['cat_fid']])->find();
							if($catFid){
								$cat_name = $catFid['cat_name'].'-'.$catId['cat_name'];
							}else{
								$cat_name = $catId['cat_name'];
							}
						}else{
							$cat_name = $catId['cat_name'];
						}
					}else{
						$cat_name = '';
					}
					$pack_detail[$k] = array(
						'num' => $v['num'],
						'name' => $cat_name,
						'years' => $list['pay_years'],
					);
					if($k == 0){
						$mer_info = $list['buy_num'].'个'.$cat_name.'(周期'.$list['pay_years'].'年)店';
					}else{
						$mer_info .= '+'.$list['buy_num'].'个'.$cat_name.'(周期'.$list['pay_years'].'年)店';
					}
				}
			}
			$list['pack_detail'] = $pack_detail;
			$list['mer_info'] = $mer_info;
		}elseif($list['class_region_id']){
			$class_region_id = (new NewMarketingClassRegion())->where(['id'=>$list['class_region_id']])->find();
			$catId = (new MerchantCategory())->where(['cat_id'=>$class_region_id['class_id']])->field('cat_id,cat_fid,cat_name')->find();
			$cat_name = '';
			$cat_fname = '';
			if($catId){
				$cat_name = $catId['cat_name'];
				if($catId['cat_fid'] > 0){
					$catFid = (new MerchantCategory())->where(['cat_id'=>$catId['cat_fid']])->field('cat_id,cat_name')->find();
					if($catFid){
						$cat_fname = $catFid['cat_name'];
					}
				}
			}
			$cateName = '';
			if($cat_name){
				if($cat_fname){
					$cateName = $cat_fname.'-'.$cat_name;
				}else{
					$cateName = $cat_name;
				}
			}
			$list['mer_info'] = $list['buy_num'].'个'.$cateName .'(周期'.$list['pay_years'].'年)店';
			$list['pack_name'] = $list['pack_name'] ? $list['pack_name'] : '';
			$list['pack_detail'] = [[
				'num' => 1,
				'name' => $cateName,
				'years' => $list['pay_years'],
			]];
			$all_num = 1;
		}elseif($list['store_id']){
			$store = (new MerchantStore())->where(['store_id'=>$list['store_id']])->field('name')->find();
			if($store){
				$list['pack_name'] = $store['name'];
			}else{
				$list['pack_name'] = '';
			}
		}else{
			$list['pack_name'] = '';
			$list['pack_detail'] = [];
			$all_num = 0;
		}
		// 订单类型
		if($list['order_type'] == 0){
			$list['order_type_status'] = '新订单';
		}else{
			$list['order_type_status'] = '续费订单';
		}
		// 订单业务
		if($list['order_business'] == 0){
			$list['order_business_status'] = '店铺';
		}else{
			$list['order_business_status'] = '社区';
		}
		// 购买店铺数量
		// $list['total_num'] = $list['buy_num'] * $all_num;
		// 订单服务人员
		// 营销人员
		$orderType = (new NewMarketingOrderType())->where(['order_id'=>$list['order_id']])->find();
		$personFind = (new NewMarketingOrderTypePerson())->where(['type_id'=>$orderType['id']])->find();
		// 业务员
		$person = (new NewMarketingPerson())->findByWhere(['id'=>$personFind['person_id']],'id,name,is_del');
		if($person){
			$person_name = $person['name'] ? $person['name'] : '';
			$is_quit = $person['is_del'] ? $person['is_del'] : 0;
		}else{
			$person_name = '';
			$is_quit = 0;
		}
		$list['person_list'] = array(
			'id' => $personFind['person_id'] ? $personFind['person_id'] : '',
			'name' => $person_name,
			'proportion' => $personFind['person_proportion'] ? $personFind['person_proportion'] : '',
			'price' => $personFind['person_price'] ? $personFind['person_price'] : '0.00',
			'is_quit' => $is_quit,
		);
		// 业务经理
		$manager = (new NewMarketingPerson())->findByWhere(['id'=>$personFind['manager_id']],'id,name,is_del');
		if($manager){
			$manager_name = $manager['name'] ? $manager['name'] : '';
			$is_quit = $manager['is_del'] ? $manager['is_del'] : 0;
		}else{
			$manager_name = '';
			$is_quit = 0;
		}
		$list['manager_list'] = array(
			'id' => $personFind['manager_id'],
			'name' => $manager_name,
			'proportion' => $personFind['manager_proportion'],
			'price' => $personFind['manager_price'],
			'is_quit' => $is_quit,
		);
		// 区域代理
		if($personFind['agent_id'] > 0){
			$agent = (new NewMarketingPerson())->findByWhere(['id'=>$personFind['agent_id']],'id,name,is_del');
			if($agent){
				$agent_name = $agent['name'] ? $agent['name'] : '';
				$is_quit = $agent['is_del'] ? $agent['is_del'] : 0;
			}else{
				$agent_name = '';
				$is_quit = 0;
			}
			$list['agent_list'] = array(
				'id' => $personFind['agent_id'],
				'name' => $agent_name,
				'proportion' => $personFind['agent_proportion'],
				'price' => $personFind['agent_price'],
				'is_quit' => $is_quit,
			);
		}else{
			$list['agent_list'] = [];
		}
		// 技术人员
        $artisanFind = (new NewMarketingOrderTypeArtisan())->where(['type_id'=>$orderType['id'],'identity'=>0])->select()->toArray();
		foreach($artisanFind as $k=>$v){
			$find = (new NewMarketingArtisan())->where(['id'=>$v['artisan_id']])->field('name')->find();
			$artisanFind[$k]['name'] = $find['name'];
			if($v['status'] == 1){
				$is_quit = 1;
			}else{
				$is_quit = 0;
			}
			$artisanFind[$k]['is_quit'] = $is_quit;
		}
		$list['artisan_list'] = $artisanFind;
		// 主管
        $directorFind = (new NewMarketingOrderTypeArtisan())->where(['type_id'=>$orderType['id'],'identity'=>1])->find();
		if(empty($directorFind)){
			$list['director_list'] = [];
		}else{
			$finds = (new NewMarketingArtisan())->where(['id'=>$directorFind['artisan_id']])->field('name')->find();
			$directorFind['name'] = $finds['name'];
			if($directorFind['status'] == 1){
				$is_quit = 1;
			}else{
				$is_quit = 0;
			}
			$directorFind['is_quit'] = $is_quit;
			$list['director_list'] = $directorFind;
		}
		// 使用情况
		// $list['used_num'] = $list['total_num'] - $list['use_num'];
        // $storeList = (new NewMarketingOrderTypeStore())->where(['type_id'=>$orderType['id']])->select()->toArray();
		// if($storeList){
		// 	foreach($storeList as $k=>$v){
		// 		$lable_list = explode(";",$v['lable_id']);
		// 		if(count($lable_list) > 0){
		// 			$cates = (new MerchantCategory())->where([['cat_id','in',$lable_list]])->field('cat_id,cat_name')->select()->toArray();
		// 			$cate_names = '';
		// 			foreach($cates as $ks=>$vs){
		// 				if($ks==0){
		// 					$cate_names = $vs['cat_name'];
		// 				}else{
		// 					$cate_names = $cate_names . '-' . $vs['cat_name'];
		// 				}
		// 			}
		// 			$storeList[$k]['lable'] = $cate_names;
		// 		}else{
		// 			$storeList[$k]['lable'] = '';
		// 		}
		// 		$yeras = date('Y',$v['effect_time']) - date('Y',$v['add_time']);
		// 		$storeList[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
		// 		$storeList[$k]['effect_time'] = date('Y-m-d H:i:s',$v['effect_time']).'（'.$yeras.'年）';
		// 	}
		// }
		// $list['usage_list'] = $storeList ? $storeList : '';
		return $list;
	}

	// 社区订单详情
	public function getCommunityOrderDetail($params){
		$orderType = (new NewMarketingOrderType())->where(['order_id'=>$params['order_id'],'order_type'=>1])->find();
		// 营销人员
		$personFind = (new NewMarketingOrderTypePerson())->where(['type_id'=>$orderType['id']])->find();
		// 业务员
		if($personFind['person_id'] > 0){
			$person = (new NewMarketingPerson())->findByWhere(['id'=>$personFind['person_id']],'id,name,is_del');
			if($person){
				$person_name = $person['name'] ? $person['name'] : '';
				$is_quit = $person['is_del'] ? $person['is_del'] : 0;
			}else{
				$person_name = '';
				$is_quit = 0;
			}
			$list['person_list'] = array(
				'id' => $personFind['per_id'] ? $personFind['per_id'] : '',
				'name' => $person_name,
				'proportion' => $personFind['per_proportion'] ? $personFind['per_proportion'] : 0,
				'price' => $personFind['per_price'] ? $personFind['per_price'] : '0.00',
				'is_quit' => $is_quit,
			);
		}else{
			$list['person_list'] = [];
		}
		// 业务经理
		if($personFind['manager_id'] > 0){
			$manager = (new NewMarketingPerson())->findByWhere(['id'=>$personFind['manager_id']],'id,name,is_del');
			if($manager){
				$manager_name = $manager['name'] ? $manager['name'] : '';
				$is_quit = $manager['is_del'] ? $manager['is_del'] : 0;
			}else{
				$manager_name = '';
				$is_quit = 0;
			}
			$list['manager_list'] = array(
				'id' => $personFind['manager_id'],
				'name' => $manager_name,
				'proportion' => $personFind['manager_proportion'] ? $personFind['manager_proportion'] : 0,
				'price' => $personFind['manager_price'] ? $personFind['manager_price'] : '0.00',
				'is_quit' => $is_quit,
			);
		}else{
			$list['manager_list'] = [];
		}
		// 区域代理
		if($personFind['agent_id'] > 0){
			$agent = (new NewMarketingPerson())->findByWhere(['id'=>$personFind['agent_id']],'id,name,is_del');
			if($agent){
				$agent_name = $agent['name'] ? $agent['name'] : '';
				$is_quit = $agent['is_del'] ? $agent['is_del'] : 0;
			}else{
				$agent_name = '';
				$is_quit = 0;
			}
			$list['agent_list'] = array(
				'id' => $personFind['agent_id'],
				'name' => $agent_name,
				'proportion' => $personFind['agent_proportion'] ? $personFind['agent_proportion'] : 0,
				'price' => $personFind['agent_price'] ? $personFind['agent_price'] : '0.00',
				'is_quit' => $is_quit,
			);
		}else{
			$list['agent_list'] = [];
		}
		return $list;
	}

    // 获取人员提成结算列表
	public function getPersonSettleList($param, $limit) {
        $where = [
            ['o.paid', '=', 1]
        ];
        $where1 = [];
        $where2 = [];
        $type = 0;
	    if (!empty($param['name'])) {
            $where[] = ['b.name|e.name', 'like', '%' . $param['name'] . '%'];
            $where1[] = ['name', 'like', '%' . $param['name'] . '%'];
        }
//        if (!empty($param['area'][0])) {
//            $where[] = ['b.province_id', '=', $param['area'][0]];
//        }
//        if (!empty($param['area'][1])) {
//            $where[] = ['b.city_id', '=', $param['area'][1]];
//        }
//        if (!empty($param['area'][2])) {
//            $where[] = ['b.area_id', '=', $param['area'][2]];
//        }
        if (!empty($param['member'])) {
            if ($param['member_type'] == 1) {
                $type = 1;
                $where[] = ['e.id', '=', $param['member']];
                $where1[] = ['id', '=', $param['member']];
                $where1[] = ['status', '=', 0];
            } else {
                $type = 2;
                $where[] = ['b.id', '=', $param['member']];
                $where1[] = ['id', '=', $param['member']];
                $where1[] = ['is_del', '=', 0];
            }
        }
        if (!empty($param['quitMember'])) {
            if ($param['member_type'] == 1) {
                $type = 1;
                $where[] = ['e.id', '=', $param['quitMember']];
                $where1[] = ['id', '=', $param['quitMember']];
                $where1[] = ['status', '=', 1];
            } else {
                $type = 2;
                $where[] = ['b.id', '=', $param['quitMember']];
                $where1[] = ['id', '=', $param['quitMember']];
                $where1[] = ['is_del', '=', 1];
            }
        }
        if ($param['order_type'] != -1) {
            $where[] = ['o.order_type', '=', $param['order_type']];
        }
        if ($param['order_business'] != -1) {
            $where[] = ['g.order_type', '=', $param['order_business']];
        }
        if (!empty($param['start_time'])) {
            $where[] = ['o.pay_time', '>=', strtotime($param['start_time'])];
            $where2[] = ['o.pay_time', '>=', strtotime($param['start_time'])];
        }
        if (!empty($param['end_time'])) {
            $where[] = ['o.pay_time', '<', strtotime($param['end_time']) + 86400];
            $where2[] = ['o.pay_time', '<', strtotime($param['end_time']) + 86400];
        }
        $field = 'o.order_id,o.orderid,o.pack_region_id,o.pack_name,o.store_id,o.class_region_id,o.total_price,o.buy_num,o.store_num,o.order_type,o.pay_time,g.order_type as order_business,b.name as person_name,b.id as person_id,c.name as merchant_name,e.id as artisan_id,e.name as artisan_name,f.property_name';
        $list = (new NewMarketingOrder())->getPersonSettleList($where, $field, $limit)->toArray();
        $list['member_name'] = '';//成员名称
        $list['is_del'] = 0;//是否离职
        $list['member_num'] = 0;//记录查出人员数
        $list['identity'] = 0;//身份
        $list['achievement'] = 0;//业绩
        $list['commission'] = 0;//总提成
        $list['team_achievement'] = 0;//业务经理团队总业绩
        $list['area_team_achievement'] = 0;//区域代理团队总业绩
        $list['currency'] = cfg('Currency_symbol');//货币符号
        if ($list['data']) {
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['pay_time'] = date('Y-m-d H:i:s', $v['pay_time']);
                $list['data'][$k]['member_name'] = $v['person_name'] ?? $v['artisan_name'];
                $list['data'][$k]['store_name'] = $v['pack_name'];
                if (!$list['data'][$k]['store_name'] && $v['store_id']) {
                    $list['data'][$k]['store_name'] = (new MerchantStore())->getStoreByStoreId($v['store_id'])['name'] ?? '';
                }
                if ($v['pack_region_id']) {
                    $pack_id = (new NewMarketingPackageRegion())->getOneData(['id' => $v['pack_region_id']])['package_id'];
                    $all_num = (new NewMarketingPackage())->getOneData(['id' => $pack_id])['all_num'] ?? 0;
                    $list['data'][$k]['store_num'] = $all_num ? $all_num * $v['buy_num'] : $v['buy_num'];
                }
//                  else if ($v['store_id']) {
//                    $storeData = (new MerchantStore())->getStoreByStoreId($v['store_id']);
//                    $cat_name = '';
//                    $fcat_name = '';
//                    if ($storeData) {
//                        if ($storeData['cat_id']) {
//                            $cat_name = (new MerchantCategory)->where(['cat_id' => $storeData['cat_id']])->value('cat_name') ?? '';
//                        }
//                        if ($storeData['cat_fid']) {
//                            $fcat_name = (new MerchantCategory)->where(['cat_id' => $storeData['cat_fid']])->value('cat_name') ?? '';
//                        }
//                    }
//                    $list['data'][$k]['store_name'] = $fcat_name . ' - ' . $cat_name;
//                } else
                if ($v['order_business'] == 1) {//取物业名称
                    $list['data'][$k]['merchant_name'] = $v['property_name'];
                }
            }
        }
        if ($where1) {
            $personList = [];
            if ($type == 1) {//技术人员
                $personList = (new NewMarketingArtisan())->getListByWhere($where1, 'id,name,5 as identity,0 as is_agency,0 as is_manager,0 as is_salesman,is_director,total_commission,status as is_del')->toArray();
            } else if ($type == 2) {//营销人员
                $personList = (new NewMarketingPerson)->getListByWhere($where1, 'id,name,0 as identity,is_agency,is_manager,is_salesman,is_del')->toArray();
            } else if (!empty($param['name'])) {//名称搜索
                $personList1 = (new NewMarketingPerson)->getListByWhere($where1, 'id,name,0 as identity,is_agency,is_manager,is_salesman,is_del')->toArray();
                $personList2 = (new NewMarketingArtisan())->getListByWhere($where1, 'id,name,5 as identity,0 as is_agency,0 as is_manager,0 as is_salesman,is_director,total_commission,status as is_del')->toArray();
                $personList = array_merge($personList1, $personList2);
            }
            $list['member_num'] = count($personList);
            if ($list['member_num'] == 1) {
                $list['member_name'] = $personList[0]['name'];
                $list['is_del'] = $personList[0]['is_del'];
                $list['identity'] = $personList[0]['identity'];
                if ($personList[0]['is_agency'] == 0 && $personList[0]['is_manager'] == 0 && $personList[0]['is_salesman'] == 1) {
                    $list['identity'] = 1;//业务员
                }
                if ($personList[0]['is_agency'] == 0 && $personList[0]['is_manager'] == 1) {
                    $list['identity'] = 2;//业务经理
                }
                if ($personList[0]['is_agency'] == 1 && $personList[0]['is_manager'] == 0) {
                    $list['identity'] = 3;//区域代理
                }
                if ($personList[0]['is_agency'] == 1 && $personList[0]['is_manager'] == 1) {
                    $list['identity'] = 4;//区域代理兼业务经理身份
                }
                switch ($list['identity']) {
                    case 1:
                        $where2[] = ['a.person_id', '=', $personList[0]['id']];
                        $list['achievement'] = (new NewMarketingOrderTypePerson())->getPersonAchievement($where2, 'o.total_price');//时间筛选的订单该业务员的总业绩
                        $list['commission'] = (new NewMarketingOrderTypePerson())->getPersonCommission($where2, 'a.person_price');//时间筛选的订单该业务员的总提成
                        break;
                    case 2:
                        $perIds = (new NewMarketingPersonSalesman())->getIdsByWhere(['b.manager_uid' => $personList[0]['id']]);
                        $list['achievement'] = (new NewMarketingOrderTypePerson())->getPersonAchievement(array_merge($where2, [['a.person_id', '=', $personList[0]['id']]]), 'o.total_price');//时间筛选的订单该业务经理个人业绩总金额
                        $list['team_achievement'] = (new NewMarketingOrderTypePerson())->getPersonAchievement(array_merge($where2, [['a.person_id', 'in', $perIds]]), 'o.total_price');//时间筛选的订单该业务经理团队所有成员的业绩总金额
                        $list['commission'] = (new NewMarketingOrderTypePerson())->getPersonCommission(array_merge($where2, [['a.manager_id', '=', $personList[0]['id']]]), 'a.manager_price');//时间筛选的订单该业务经理（包含团队抽成以及个人业绩抽成）提成总和
                        break;
                    case 3:
                        $perIds = (new NewMarketingPersonSalesman())->getIdsByWhere(['b.area_uid' => $personList[0]['id']]);
                        $list['area_team_achievement'] = (new NewMarketingOrderTypePerson())->getPersonAchievement(array_merge($where2, [['a.person_id', 'in', $perIds]]), 'o.total_price');//时间筛选的订单该区域代理 绑定的所有下级团队业绩总金额
                        $list['commission'] = (new NewMarketingOrderTypePerson())->getPersonCommission(array_merge($where2, [['a.agent_id', '=', $personList[0]['id']]]), 'a.agent_price');//时间筛选的订单该区域代理 绑定的所有下级团队的总抽成
                        break;
                    case 4:
                        $perIds = (new NewMarketingPersonSalesman())->getIdsByWhere(['b.manager_uid' => $personList[0]['id']]);
                        $list['achievement'] = (new NewMarketingOrderTypePerson())->getPersonAchievement(array_merge($where2, [['a.person_id', '=', $personList[0]['id']]]), 'o.total_price');//时间筛选的订单该区域代理在业务经理身份下的个人业绩总金额
                        $list['team_achievement'] = (new NewMarketingOrderTypePerson())->getPersonAchievement(array_merge($where2, [['a.person_id', 'in', $perIds]]), 'o.total_price');//时间筛选的订单该区域代理在业务经理身份下的团队所有成员的业绩总金额
                        $list['commission'] = (new NewMarketingOrderTypePerson())->getPersonCommission(array_merge($where2, [['a.manager_id|a.agent_id', '=', $personList[0]['id']]]), 'a.manager_price');//时间筛选的订单该区域代理所有身份（业务经理）下的抽成总和
                        $list['commission'] += (new NewMarketingOrderTypePerson())->getPersonCommission(array_merge($where2, [['a.manager_id|a.agent_id', '=', $personList[0]['id']]]), 'a.agent_price');//时间筛选的订单该区域代理所有身份（业务经理）下的抽成总和
                        break;
                    case 5:
                        $list['identity'] += $personList[0]['is_director'];
                        $list['commission'] = (new NewMarketingOrderTypeArtisan())->getArtisanCommission(array_merge($where2, [['a.artisan_id', '=', $personList[0]['id']]]), 'a.price');//时间筛选的订单该技术人员/技术主管抽取的总提成（技术人员抽成+技术主管抽成）
                        break;
                    default:
                        break;
                }
                $list['achievement'] = cfg('Currency_symbol') . $list['achievement'];//业绩
                $list['commission'] = cfg('Currency_symbol') . $list['commission'];//总提成
                $list['team_achievement'] = cfg('Currency_symbol') . $list['team_achievement'];//业务经理团队总业绩
                $list['area_team_achievement'] = cfg('Currency_symbol') . $list['area_team_achievement'];//区域代理团队总业绩
            }
        }
        return $list;
    }

    /**
     * 获得一条数据
     * @return array
     */
    public function getOne($where = [], $field = true, $order = []){
        $list = (new NewMarketingOrder())->getOne($where,$field,$order);
        if(!$list) {
            return [];
        }
        return $list->toArray();
    }

    /**
     * 获得多条数据
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
		$start = ($page - 1) * $limit;
		$start = max($start, 0);
        $list = (new NewMarketingOrder())->getSome($where,$field,$order,$start,$limit);
        if(!$list) {
            return [];
        }
        return $list->toArray();
    }

    /**
     * 统计
     * @return float
     */
    public function getSum($where = [], $field='total_price'){
        $count =  (new NewMarketingOrder())->where($where)->sum($field);
        if(!$count) {
            return 0;
        }
        return $count;
    }

    /**
     * 获得总数
     * @return int
     */
    public function getCount($where = []){
        $res = (new NewMarketingOrder())->getCount($where);
        if(!$res) {
            return 0;
        }
        return $res;
    }

    /**
     * 添加一条数据
     * @return int|bool
     */
    public function add($data){
		if(empty($data)){
			return false;
		}
        $id = (new NewMarketingOrder())->add($data);
        if(!$id) {
            return false;
        }
        return $id;
    }


    /**
     * 更新一条数据
     * @return bool
     */
    public function updateThis($where, $data){
		if(empty($data) || empty($where)){
			return false;
		}
        $res = (new NewMarketingOrder())->updateThis($where, $data);
        if(!$res) {
            return false;
        }
        return $res;
    }

    //获取商家店铺列表
    public function getMerchantStoreList($merId) {
        $list = (new NewMarketingOrderStore())->getList(['mer_id' => $merId], 'store_id,mer_id,lable_id,name,phone,address,add_time,effect_time', 'add_time desc');
        if ($list) {
            foreach ($list as $k => $v) {
                $list[$k]['cat_fid'] = 0;
                $list[$k]['cat_fname'] = '';
                $list[$k]['cat_id'] = 0;
                $list[$k]['cat_name'] = '';
                $list[$k]['store_type_name'] = '';
                if ($v['lable_id']) {
                    $lable_id = explode(';', $v['lable_id']);
                    if (!empty($lable_id[0])) {
                        $list[$k]['cat_fid'] = $lable_id[0];
                        $list[$k]['cat_fname'] = (new NewMerchantCategory())->getOneData(['cat_id' => $lable_id[0]])['cat_name'] ?? '';
                    }
                    if (!empty($lable_id[1])) {
                        $list[$k]['cat_id'] = $lable_id[1];
                        $list[$k]['cat_name'] = (new NewMerchantCategory())->getOneData(['cat_id' => $lable_id[1]])['cat_name'] ?? '';
                    }
                    $list[$k]['store_type_name'] = $list[$k]['cat_fname'] . '-' . $list[$k]['cat_name'];
                }
                $list[$k]['add_time'] = $v['add_time'] ? date('Y-m-d H:i:s', $v['add_time']) : '无';
                $list[$k]['status_value'] = '';
                $list[$k]['status'] = 1;//使用中
                if ($v['effect_time'] <= time()) {
                    $list[$k]['status'] = 3;//已过期
                    $list[$k]['status_value'] = '已过期';
                } else if ($v['effect_time'] <= time()+30*86400) {
                    $list[$k]['status'] = 2;//即将过期
                    $list[$k]['status_value'] = '即将过期';
                } else {
                    $list[$k]['status_value'] = '使用中';
                }
                $list[$k]['effect_time'] = $v['effect_time'] ? date('Y-m-d H:i:s', $v['effect_time']) : '无';
            }
        }
        return $list;
    }

	/**
	 * 获得订单列表
	 */
	public function getOrderList($params){
		$where = [];
		$page = $params['page'] ?? 1;
		$pageSize = $params['pageSize'] ?? 10;

		// 商家id
		if(isset($params['mer_id']) && $params['mer_id']){
			$where[] = ['mer_id', '=', $params['mer_id']];
		}

		$where[] = ['paid', '=', 1];
		$order = [
			'add_time' => 'DESC'
		];
		$list = $this->getSome($where, true, $order, $page, $pageSize);
		$count = $this->getCount($where);
	
		foreach($list as $k=> &$_order){
			$_order['pay_time'] = date('Y-m-d H:i:s', $_order['pay_time']);
			$_order['order_type_str'] = $this->orderTypeArr[$_order['order_type']];// 订单类型
			$_order['total_price'] = get_format_number($_order['total_price']);
			$_order['order_name'] = $_order['pack_name'];
		}

		$returnArr['list'] = $list;
		$returnArr['count'] = $count;
		return $returnArr;
	}

	/**
	 * 获得订单详情
	 */
	public function getOrderDetail($params){
		$where = [];
		$page = $params['page'] ?? 1;
		$pageSize = $params['pageSize'] ?? 10;
		$orderId = $params['order_id'] ?? 0;

		if(empty($orderId)){
            throw new \think\Exception(L_("缺少参数"), 1001);
		}

		$where = [];
		$where[] = ['order_id', '=', $orderId];

		// 商家id
		if(isset($params['mer_id']) && $params['mer_id']){
			$where[] = ['mer_id', '=', $params['mer_id']];
		}

		$detail = $this->getOne($where);		
		if(empty($detail)){
            throw new \think\Exception(L_("缺少参数"), 1003);
		}

		$detail['add_time'] = date('Y-m-d H:i:s', $detail['add_time']);
		$detail['pay_time'] = date('Y-m-d H:i:s', $detail['pay_time']);
		$detail['total_price'] = get_format_number($detail['total_price']);
		$detail['discount_money'] = get_format_number($detail['discount_money']);
		$detail['order_type_str'] = $this->orderTypeArr[$detail['order_type']];// 订单类型

		$detail['store_detail'] = [];
		$detail['store_name'] = '';
		$detail['store_type_name'] = '';
		if($detail['pack_region_id']){// 购买套餐
			$packDetail = (new MarketingPackageRegionService)->getPackByRegion($detail['pack_region_id']);
			$detail['store_detail'] = $packDetail['store_detail'] ?? [];
			foreach($detail['store_detail'] as &$_store){
				$_store['num'] = $_store['num'] * $detail['buy_num'];
			}
		}elseif($detail['class_region_id']){// 购买分类
			$class_region_id = (new NewMarketingClassRegion())->where(['id' => $detail['class_region_id']])->find();
			$childCategory = (new MerchantCategoryService())->getOneData(['cat_id' => $class_region_id['class_id']]);// 子分类
			$categoryName = $childCategory['cat_name'];
			if($childCategory){
				$category = (new MerchantCategoryService())->getOneData(['cat_id' => $childCategory['cat_fid']]);// 父分类
				if($category && $category['cat_name']){
					$categoryName =  $category['cat_name'] . '-' . $categoryName;
				}
			}
			$detail['store_name'] = $categoryName;
			$detail['store_detail'][] = [
				'num' => $detail['store_num'],
				'type_name' => $categoryName,
			];

		}elseif($detail['store_id']){// 续费订单
			$store = (new MerchantStoreService)->getOne(['store_id'=>$detail['store_id']]);
			$detail['store_name'] = $store['name'];

			$childCategory = (new MerchantCategoryService())->getOneData(['cat_id' => $store['cat_id']]);// 子分类
			$categoryName = $childCategory['cat_name'];
			
			$category = (new MerchantCategoryService())->getOneData(['cat_id' => $store['cat_fid']]);// 父分类
			if($category && $category['cat_name']){
				$categoryName =  $category['cat_name'] . '-' . $categoryName;
			}
			$detail['store_type_name'] = $categoryName;
			
		}
	

		return $detail;
	}

	/**
	 * 获得订单详情
	 * @param int $orderId 订单id
	 */
	public function getPayOrder($orderId){
		// 订单信息
        $nowOrder = $this->getOne(['order_id' => $orderId]);
        if(!$nowOrder){
            throw new \think\Exception(L_("订单不存在"), 1003);
		}

		// 支付类型
        $nowOrder['order_type'] = 'newmarket';

		// 支付描述
		$nowOrder['order_name'] = '店铺商城购买';
		if($nowOrder['pack_name']){
			$nowOrder['order_name'] = $nowOrder['pack_name'];
		}elseif($nowOrder['store_id']){
			$store = (new MerchantStoreService)->getStoreByStoreId($nowOrder['store_id']);
			$nowOrder['order_name'] = $store['name'];
		}

		// 支付金额
		$nowOrder['pay_money'] = $nowOrder['total_price'];
		return $nowOrder;
	}

	/**
	 * 获得分类购买详情
	 * @param int $catId 分类id
	 * @param int $merId 商家id
	 * @param int $proviceId 省份id
	 */
	public function getRenewalOrderPayInfo($param){		
		$years = $param['years'] ?? 1;// 使用年份
		$merId = $param['mer_id'] ?? 1;// 商家id
		$storeId = $param['store_id'] ?? 0;
		if(empty($storeId)){			
            throw new \think\Exception(L_("缺少参数"), 1001);
		}

		// 店铺信息
		$store = (new MerchantStoreService)->getOne(['store_id' => $storeId]);
		if(empty($store)){			
            throw new \think\Exception(L_("店铺不存在"), 1001);
		}
		
		// 商家信息
		$merchant = (new MerchantService)->getMerchantByMerId($merId);
		if(empty($store)){			
            throw new \think\Exception(L_("商家不存在"), 1001);
		}		

		$returnArr['store']['name'] = $store['name'];
		$returnArr['store']['store_id'] = $store['store_id'];
		// 店铺图片
		$storeImageService = new \app\merchant\model\service\storeImageService();
		$images = $storeImageService->getAllImageByPath($store['pic_info']);
		$image = $images[0] ?? '';
		$returnArr['store']['image'] = $image ?: replace_file_domain($store['logo']);

		// 获得分类价格详情
		$classicPrice = (new ClassPriceService)->getCategoryPriceDetail($store['cat_id'], $store['cat_fid'], $store['province_id']);
		$priceDetail = (new ClassPriceService)->getClassDetailData([], $classicPrice);
		$returnArr['price_detail'] = $priceDetail;
		$money = (new ClassPriceService)->getDiscountPayPrice([], $years, 1, $classicPrice);
		$returnArr['pay']['pay_money'] = $money['pay'];// 应付金额
		$returnArr['pay']['discount_money'] = $money['discount'];// 优惠金额
		$returnArr['pay']['merchant_money'] = get_format_number($merchant['money']);// 商家金额
		$returnArr['pay']['pay_type'] = (new PayService)->getPayType();// 支付方式
		return $returnArr;
	}

	/**
	 * 保存续费订单详情
	 * @param int $catId 分类id
	 * @param int $merId 商家id
	 * @param int $proviceId 省份id
	 */
	public function savePayInfo($param){	
		$years = $param['years'] ?? 1;// 使用年份
		$merId = $param['mer_id'] ?? 1;// 商家id
		$storeId = $param['store_id'] ?? 0;
		$num = $param['num'] ?? 1;
		$payType = $param['pay_type'] ?? 1;
		
		// 获取支付信息
		$payInfo = $this->getRenewalOrderPayInfo($param);

		$orderData = [
            'orderid' => build_real_orderid($merId),
            'mer_id' => $merId,
            'pack_name' => '',
            'buy_num' => $num,
			'store_num' => 1,
            'store_id' => $storeId,
            'pay_years' => $years,
            'order_type' => 1,// 0-新订单1-续费
            'paid' => 0,
			'pack_name' => $payInfo['store']['name'],
            'pay_type' => $payType,
            'discount_money' => $payInfo['pay']['discount_money'],
            'total_price' =>  $payInfo['pay']['pay_money'],
            'add_time' => time(),
        ];

        $orderId = (new MarketingOrderService())->add($orderData);
        if(!$orderId){
            throw new \think\Exception(L_("订单保存失败，请稍后重试"), 1003);
		}
		$returnArr['order_id'] = $orderId;
		$returnArr['order_type'] = 'newmarket';
		return $returnArr;
	}

    /**
     * 支付完成
     * @param int $orderData 订单数据
     * @param int $id 区域套餐ID/区域分类店铺ID
     * @param int $type 1=购买套餐,2=购买分类店铺
     */
    public function after_pay($param) {
        $orderData = $param['orderData'];
		if($orderData['paid'] == 1){
			return true;
		}
        $id = $param['id'] ?? 0;
        $type = $param['type'] ?? '';
        $desc = $type == 2 ? '商户购买分类店铺减少金额' : '商户购买套餐减少金额';
        if ($type == 2) {//购买分类店铺
            $classData = (new ClassPriceService())->getClassData(['id' => $id]);
            $saveData = [
                'mer_id' => $orderData['mer_id'],
                'cat_id' => $classData['class_id'],
                'cat_fid' => (new NewMerchantCategory())->getOneData(['cat_id' => $classData['class_id']])['cat_fid'],
                'years_num' => $orderData['pay_years'],
                'store_count' => $orderData['store_num'],
                'used_count' => 0
            ];
            (new MarketingStoreService())->save($saveData);
        } elseif($type == 1) {//购买套餐
            $packageData = (new MarketingPackageRegionService())->getWhereData(['a.id' => $id], 'b.store_detail');
            $store_detail = json_decode($packageData['store_detail'], true);
            foreach ($store_detail as $k => $v) {
                $saveData = [
                    'mer_id' => $orderData['mer_id'],
                    'cat_id' => $v['type'][1],
                    'cat_fid' => $v['type'][0],
                    'years_num' => $orderData['pay_years'],
                    'store_count' => $v['num'] * $param['num'],
                    'used_count' => 0
                ];
                (new MarketingStoreService())->save($saveData);
            }
        }elseif($orderData['store_id']){// 店铺续费
			$storeWhere = ['store_id' => $orderData['store_id']];
			$store = (new MerchantStoreService)->getOne($storeWhere);
			$desc =  '店铺'.$store['name'].'续费减少金额';
			if($store){
				// 增加到期时间
				$addTime = $orderData['pay_years']*86400*365;
				if($store['end_time'] > time()){
					(new MerchantStore())->where($storeWhere)->inc('end_time', $addTime)->update();
				}else{
					$saveData = [
						'status' => 1,
						'end_time' => $addTime + time(),
					];
					(new MerchantStoreService())->updateThis($storeWhere,$saveData);
				}
			}
		}

        (new MerchantMoneyListService())->useMoney($orderData['mer_id'], $orderData['total_price'], $orderData['order_type'], $desc, $orderData['order_id']);

        (new NewMarketingOrderType())->insert([//记录订单类型
            'order_type' => 0,
            'order_id' => $orderData['order_id']
        ]);
        $afterPay = [
            'pay_time' => time(),
            'paid' => 1,
            'balance_pay' => $orderData['total_price']
        ];
        (new MarketingOrderService())->updateThis(['order_id' => $orderData['order_id']], $afterPay);

//		if($orderData['order_type'] == 0){// 支付完成后业务抽成
			try {
				$this->sendPersonSpreadMoney($orderData['order_id']);
			}catch (\Exception $e) {
			}
//		}
        return true;
    }


	/**
     * 支付完成后业务抽成
     * @param int $order_id 订单id
     */
    public function sendPersonSpreadMoney($orderId) {
        $nowOrder = $this->getOne(['order_id' => $orderId]);
		if(empty($nowOrder)){
			throw new \think\Exception(L_('订单不存在'));
		}
		if($nowOrder['paid'] == 0){
			throw new \think\Exception(L_('订单未支付'));
		}

		// 订单类型表
		$where = [
			'order_type' => 0,
			'order_id' => $nowOrder['order_id'],
		];
		$orderTypeInfo = (new MarketingOrderTypeService)->getOne($where);

		// 订单金额
		$totalMoney = $nowOrder['total_price'];

		// 商家关联业务员
		$where = ['mer_id' => $nowOrder['mer_id']];
		$personMerchant = (new MarketingPersonMerService)->getOne($where);
		if(empty($personMerchant)){// 商家没有推广人
			throw new \think\Exception(L_('商家没有推广人'));
		}

		//查询推广人
		$where = ['id' => $personMerchant['person_id'],'is_del' => 0];
		$person = (new MarketingPersonService)->getOne($where);
		if(empty($person)){// 推广人不存在
			throw new \think\Exception(L_('推广人不存在'));
		}

		if(!$person['is_salesman'] && !$person['is_manager']){// 业务员身份错误
			throw new \think\Exception(L_('业务员身份错误'));
		}

		$savePersonData = [
			'add_time' => time(),
			'type_id' => $orderTypeInfo['id'],
		];// 业务员抽成记录

		$salesman = [];// 业务员信息
		$manager = [];// 业务经理信息
		$agency = [];// 区域代理信息
		$team = [];// 团队信息
		$agencyPercentage = 0;
		$salesmanPercentage = 0;// 业务员抽成
		$managerPercentage = 0;// 业务经理抽成
		if($person['is_salesman']){// 业务员
			// 给业务员抽成
							
			// 业务员 提成比例shop_percent
			$salesman = (new MarketingPersonSalesmanService)->getOne(['person_id' => $person['id'],'is_del' => 0]);

			// 团队信息
			$team = (new MarketingTeamService)->teamManagementBasic(['id' => $salesman['team_id'],'is_del' => 0]);

			if($team['basic']['area_find']){// 区域代理
				$agency = (new MarketingPersonAgencyService)->getOne(['person_id' => $team['basic']['area_find']['id'],'is_del' => 0]);
			}

			if($team['basic']['manager_find']){// 业务经理
				$manager = (new MarketingPersonManagerService)->getOne(['person_id' => $team['basic']['manager_find']['id'],'is_del' => 0]);
			}
			
			$savePersonData['person_id'] = $salesman['person_id']; // 业务员id
			$savePersonData['person_proportion'] = $salesman['shop_percent'] ?? 0; // 业务员提成比例
			$salesmanPercentage = $savePersonData['person_price'] = get_format_number($totalMoney * $savePersonData['person_proportion'] / 100); // 业务员提成金额
			
			$savePersonData['manager_id'] = $manager['person_id'] ?? 0; // 业务经理id
			$savePersonData['manager_proportion'] = $team['basic']['manager_percent'] ?? 0; // 业务经理提成比例			
			$managerPercentage = $savePersonData['manager_price'] = get_format_number($totalMoney * $savePersonData['manager_proportion'] / 100); // 业务员提成金额
		}elseif($person['is_manager']){// 业务经理
			
			// 业务经理信息
			$manager = (new MarketingPersonManagerService)->getOne(['person_id' => $person['id'],'is_del' => 0]);

			// 团队信息
			$team = (new MarketingTeamService)->teamManagementBasic(['id' => $manager['team_id'],'is_del' => 0]);

			if($team['basic']['area_find']){// 区域代理
				$agency = (new MarketingPersonAgencyService)->getOne(['person_id' => $team['basic']['area_find']['id'],'is_del' => 0]);
			}

			$savePersonData['person_id'] = $person['id']; // 业务员id
			$savePersonData['person_proportion'] = $team['basic']['personnel_percent'] ?? 0; // 业务员提成比例
			$salesmanPercentage = $savePersonData['person_price'] = get_format_number($totalMoney * $savePersonData['person_proportion'] / 100); // 业务员提成金额
			
			$savePersonData['manager_id'] = $manager['person_id'] ?? 0; // 业务经理id
			$savePersonData['manager_proportion'] = $team['basic']['manager_percent'] ?? 0; // 业务经理提成比例			
			$managerPercentage = $savePersonData['manager_price'] = get_format_number($totalMoney * $savePersonData['manager_proportion'] / 100); // 业务员提成金额
		}

		// 区域代理抽成
		$savePersonData['agent_id'] = $agency['person_id'] ?? 0; // 业务经理id
		$savePersonData['agent_proportion'] = $agency['store_percent'] ?? 0; // 业务经理提成比例			
		$agencyPercentage = $savePersonData['agent_price'] = get_format_number($totalMoney * $savePersonData['agent_proportion'] / 100); // 业务员提成金额

		// 插入业务员抽成记录
		(new MarketingOrderTypePersonService)->add($savePersonData);

		// 更新业务员的总抽成、总业绩
		$where = [
			'id' => $salesman['id']
		];
		(new MarketingPersonSalesmanService)->incCommission($where, $totalMoney, 'total_performance');
		(new MarketingPersonSalesmanService)->incCommission($where, $salesmanPercentage, 'total_percentage');	

		// 更新业务经理的总抽成、总业绩
		$where = [
			'id' => $manager['id']
		];
		(new MarketingPersonManagerService)->incCommission($where, $totalMoney, 'total_performance');
		(new MarketingPersonManagerService)->incCommission($where, $managerPercentage, 'total_percentage');

		// 更新区域代理的总抽成、总业绩
		$where = [
			'id' => $agency['id']
		];
		(new MarketingPersonAgencyService)->incCommission($where, $totalMoney, 'total_performance');
		(new MarketingPersonAgencyService)->incCommission($where, $agencyPercentage, 'total_percentage');	

		// 更新团队的总业绩 
		$where = [
			'id' => $manager['team_id']
		];
		(new MarketingTeamService)->incCommission($where, $totalMoney, 'achievement');

		// 技术人员抽成
		if($team['basic']['artisan_list']){
			$saveArtisanData = [
				'add_time' => time(),
				'type_id' => $orderTypeInfo['id'],
			];

			// 技术人员的佣金总额
			$artisanMoney = get_format_number($totalMoney * $team['basic']['technology_percent'] / 100);

			// 技术人员的总数
			$artisanCount = count($team['basic']['artisan_list']);

			// 每个技术人员获得的金额（平分）
			$artisanMoneyAverage = get_format_number($artisanMoney / $artisanCount);

			$artisanIdArr = []; // 技术人员的id，用于查询技术主管
			foreach($team['basic']['artisan_list'] as $_artisan){
				
				$saveArtisanData['artisan_id'] = $_artisan['id']; // 技术id
				$saveArtisanData['proportion'] = $team['basic']['technology_percent'] ?? 0; // 技术提成比例
				$saveArtisanData['price'] = $artisanMoneyAverage; // 技术提成金额
				(new MarketingOrderTypeArtisanService)->add($saveArtisanData);

				$where = [
					'id' => $_artisan['id']
				];
				if($_artisan['is_director']){
					// 更新技术主管提成总金额
					(new MarketingArtisanService)->incCommission($where, $artisanMoneyAverage, 'personal_commission');
				}else{
					// 更新技术员提成总金额
					(new MarketingArtisanService)->incCommission($where, $artisanMoneyAverage, 'total_commission');
				}

				$artisanIdArr[] = $_artisan['director_id'];
			}

			if($artisanIdArr){ // 技术主管获得抽成
				$where = [
					['id' ,'in', implode(',', $artisanIdArr)]
				];
				$artisanList = (new MarketingArtisanService)->getSome($where);
				if($artisanList){
					foreach($artisanList as $_artisan){
						$saveArtisanData['artisan_id'] = $_artisan['id']; // 技术id
						$saveArtisanData['identity'] = $_artisan['is_director']; // 是否主管 0否 1是
						$saveArtisanData['proportion'] = $_artisan['team_percent'] ?? 0; // 技术主管提成比例
						$saveArtisanData['price'] = get_format_number($totalMoney * $saveArtisanData['proportion'] / 100); 
						// 技术主管提成金额
						(new MarketingOrderTypeArtisanService)->add($saveArtisanData);
	
						// 更新技术主管团队提成
						(new MarketingArtisanService)->incCommission($where, $saveArtisanData['price'], 'team_commission');	
					}
				}
			}
		}

		if(isset($team['basic'])&& isset($team['basic']['id'])){
			(new MarketingOrderTypeService())->updateThis(['id' =>$orderTypeInfo['id']],['team_id' => $team['basic']['id']]);
		}
        return true;
	}

    /**
     * 物业订单列表
     */
    public function getPropertyOrderList($params) {
		// 用戶ID
		$business = (new NewMarketingPerson())->where(['uid'=>$params['uid']])->find();
		if($business){
			$user_id = $business['id'];
		}else{
			$user_id = 0;
		}
		// 社区
        $where[] = ['g.order_type','=',1];
		// 团队
		if($params['team_id'] > 0){
			$where[] = ['g.team_id','=',$params['team_id']];
			// 团队成员
			if($params['person_id'] > 0){
				$where[] = ['b.person_id','=',$params['person_id']];
			}
		}else{
			if($user_id > 0){
				if($business['is_agency'] == 1){
					$where[] = ['b.agent_id','=',$user_id];
				}elseif($business['is_manager'] == 1){
					$where[] = ['b.manager_id','=',$user_id];
				}else{
					$where[] = ['b.person_id','=',$user_id];
				}
			}
		}
		// 开始结束时间
		if($params['start_time'] && $params['end_time']){
			$start_time = strtotime($params['start_time'].' 00:00:00');
			$end_time = strtotime($params['end_time'].' 23:59:59');
			$where[] = ['a.pay_time','>',$start_time];
			$where[] = ['a.pay_time','<',$end_time];
		}
		$order = 'a.pay_time DESC';
		$field = 'a.*, b.person_price, b.manager_price, b.agent_price, b.person_id, b.manager_id, b.agent_id, g.team_id';
		$page = $params['page'];
		$pageSize = $params['pageSize'];
        $list = (new NewMarketingOrderType())->getPropertyOrderList($where, $field, $order, $page, $pageSize);
		$total_achievement = 0;
		$total_commission = 0;
		foreach($list['list'] as $k=>$v){
			$list['list'][$k]['pay_time'] = date('Y-m-d H:i:s',$v['pay_time']);
			$list['list'][$k]['create_time'] = date('Y-m-d',$v['create_time']);
			$total_achievement = $total_achievement + $v['pay_money'];
			if($params['team_id'] > 0 && $params['person_id'] < 1){
				$total_commission = $total_commission + get_format_number($v['person_price']) + get_format_number($v['manager_price']) + get_format_number($v['agent_price']);
			}elseif($params['team_id'] > 0 && $params['person_id'] > 0){
				// 业务人员、业务经理、区域代理总提成
				if($v['person_id'] == $params['person_id']){
					$total_commission = $total_commission + get_format_number($v['person_price']);
				}
			}else{
				// 业务人员、业务经理、区域代理总提成
				if($v['person_id'] == $user_id){
					$total_commission = $total_commission + get_format_number($v['person_price']);
				}
				if($v['manager_id'] == $user_id){
					$total_commission = $total_commission + get_format_number($v['manager_price']);
				}
				if($v['agent_id'] == $user_id){
					$total_commission = $total_commission + get_format_number($v['agent_price']);
				}
			}
			$list['list'][$k]['orderid'] = $v['order_no'];
			$list['list'][$k]['package_name'] = $v['package_title'];
		}
		$list['total_achievement'] = $total_achievement;
		$list['total_commission'] = $total_commission;
        return $list;
    }

    /**
     * 店铺订单列表
     */
    public function getShopOrderList($params) {
		// 商家
        $where[] = ['g.order_type','=',0];
		// 团队
		if($params['team_id'] > 0){
			$where[] = ['g.team_id','=',$params['team_id']];
			// 团队成员
			if($params['person_id'] > 0){
				$where[] = ['b.person_id','=',$params['person_id']];
			}
		}else{
			// 用戶ID
			$person = (new NewMarketingPerson())->where(['uid'=>$params['uid']])->find();
			if($person){
				if($params['person_id'] > 0){
					$where[] = ['b.person_id','=',$params['person_id']];
				}else{
					if($person['is_agency'] == 1){
						$where[] = ['b.agent_id','=',$person['id']];
					}elseif($person['is_manager'] == 1){
						$where[] = ['b.manager_id','=',$person['id']];
					}else{
						$where[] = ['b.person_id','=',$person['id']];
					}
				}
			}else{
				$artisan = (new NewMarketingArtisan())->where(['uid'=>$params['uid']])->find();
				if($artisan){
					$where[] = ['c.artisan_id','=',$artisan['id']];
				}
			}
		}
		// 开始结束时间
		if($params['start_time'] && $params['end_time']){
			$start_time = strtotime($params['start_time'].' 00:00:00');
			$end_time = strtotime($params['end_time'].' 23:59:59');
			$where[] = ['a.pay_time','>',$start_time];
			$where[] = ['a.pay_time','<',$end_time];
		}
		$order = 'a.pay_time DESC';
		$field = 'a.*, b.person_price, b.manager_price, b.agent_price, b.person_id, b.manager_id, b.agent_id, m.name as mer_name, g.id as type_id, g.team_id';
		$page = $params['page'];
		$pageSize = $params['pageSize'];
        $list = (new NewMarketingOrderType())->getShopOrderList($where, $field, $order, $page, $pageSize);
		// 提成
		$business = (new NewMarketingPerson())->where(['uid'=>$params['uid']])->find();
		if($business){
			$user_id = $business['id'];
		}else{
			$artisan = (new NewMarketingArtisan())->where(['uid'=>$params['uid']])->find();
			if($artisan){
				$user_id = $artisan['id'];
			}else{
				$user_id = 0;
			}
		}
		$total_achievement = 0;
		$total_commission = 0;
		$total_price_list = (new NewMarketingOrderType())->getShopOrderSum($where, 'a.total_price, b.person_price, b.manager_price, b.agent_price, b.person_id, b.manager_id, b.agent_id, m.name as mer_name, g.id as type_id, g.team_id', $order);
		foreach($total_price_list as $v){
			$total_achievement =  $total_achievement + get_format_number($v['total_price']);
			if($params['team_id'] > 0 && $params['person_id'] < 1){
				// 业务人员、业务经理、区域代理总提成
				$total_commission = $total_commission + get_format_number($v['person_price']) + get_format_number($v['manager_price']) + get_format_number($v['agent_price']);
				// 技术人员、技术主管总提成
				$technology = (new NewMarketingOrderTypeArtisan())->where(['type_id'=>$v['type_id']])->select();
				foreach($technology as $vs){
					if($vs['artisan_id'] == $user_id){
						$total_commission = $total_commission + $vs['price'];
					}
				}
			}elseif($params['team_id'] > 0 && $params['person_id'] > 0){
				// 业务人员总提成
				if($v['person_id'] == $params['person_id']){
					$total_commission = $total_commission + get_format_number($v['person_price']);
				}
			}else{
				// 业务人员、业务经理、区域代理总提成
				if($v['person_id'] == $user_id){
					$total_commission = $total_commission + get_format_number($v['person_price']);
				}
				if($v['manager_id'] == $user_id){
					$total_commission = $total_commission + get_format_number($v['manager_price']);
				}
				if($v['agent_id'] == $user_id){
					$total_commission = $total_commission + get_format_number($v['agent_price']);
				}
				// 技术人员、技术主管总提成
				$technology = (new NewMarketingOrderTypeArtisan())->where(['type_id'=>$v['type_id']])->select();
				foreach($technology as $vs){
					if($vs['artisan_id'] == $user_id){
						$total_commission = $total_commission + get_format_number($vs['price']);
					}
				}
			}
		}

		foreach($list['list'] as $k=>$v){
			$list['list'][$k]['pay_time'] = date('Y-m-d H:i:s',$v['pay_time']);
			$list['list'][$k]['create_time'] = date('Y-m-d',$v['add_time']);
			// $total_achievement = $total_achievement + get_format_number($v['total_price']);
			$list['list'][$k]['package_name'] = $v['pack_name'];
			$list['list'][$k]['order_money'] = get_format_number($v['total_price']);
		}
		$list['total_achievement'] = $total_achievement;
		$list['total_commission'] = $total_commission;
        return $list;
    }

	// 订单详情
	public function getOrderInfo($where, $field, $order, $page, $pageSize){
		$list = (new NewMarketingOrder())->getOrderInfo($where, $field, $order, $page, $pageSize);
		foreach($list['list'] as $k=>$v){
			// 下单时间
			$list['list'][$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
			$list['list'][$k]['pay_time'] = date('Y-m-d H:i:s',$v['pay_time']);
			// 业务员名称
			if(!$v['per_name']){
				$list['list'][$k]['per_name'] = '';
			}
			// 订单类型
			if($v['order_type']==0){
				$list['list'][$k]['order_type_status'] = '新订单';
			}else{
				$list['list'][$k]['order_type_status'] = '续费订单';
			}
			// 订单业务
			if($v['order_business']==0){
				$list['list'][$k]['order_business_status'] = '店铺';
			}else{
				$list['list'][$k]['order_business_status'] = '社区';
				$list['list'][$k]['mer_name'] = $list['list'][$k]['property_name'];
			}
			// 下单店铺
			if($v['pack_region_id'] > 0){
				$packageRegion = (new NewMarketingPackageRegion())->where(['id'=>$v['pack_region_id'],'status'=>1])->find();
				$package = (new NewMarketingPackage())->where(['id'=>$packageRegion['package_id']])->find();
				$list['list'][$k]['store_name'] = $package['name'] ? $package['name'] : '';
			}elseif($v['class_region_id'] > 0){
				$class_region_id = (new NewMarketingClassRegion())->where(['id'=>$v['class_region_id']])->find();
				if($class_region_id){
					$cate = (new MerchantCategory())->where(['cat_id'=>$class_region_id['class_id']])->field('cat_id,cat_fid,cat_name')->find();
					if($cate){
						$cate_name = $cate['cat_name'];
						if($cate['cat_fid'] > 0){
							$catef = (new MerchantCategory())->where(['cat_id'=>$cate['cat_fid']])->field('cat_id,cat_name')->find();
							if($catef){
								$cate_name = $catef['cat_name'].'-'.$cate['cat_name'];
							}else{
								$cate_name = $cate['cat_name'];
							}
						}
					}else{
						$cate_name = '';
					}
					$list['list'][$k]['store_name'] = $cate_name;
				}else{
					$list['list'][$k]['store_name'] = '';
				}
			}elseif($v['store_id']){
				$store = (new MerchantStore())->where(['store_id'=>$v['store_id']])->field('name')->find();
				if($store){
					$list['list'][$k]['store_name'] = $store['name'];
				}else{
					$list['list'][$k]['store_name'] = '';
				}
			}else{
				$list['list'][$k]['store_name'] = $v['class_region_id'] ? $v['class_region_id'] : '';
			}

		}
		return $list;
	}

	// object转array
	function object_array($array) { 
		if(is_object($array)) { 
			$array = (array)$array; 
		} if(is_array($array)) { 
			foreach($array as $key=>$value) { 
				$array[$key] = $this->object_array($value); 
			} 
		} 
		return $array; 
	}

	// 支付方式
	function pay_type($pay_type, $paid) { 
		if($pay_type == 'balance'){
			return '商家余额';
		}elseif($pay_type == 'alipay'){
			return '支付宝';
		}elseif($pay_type == 'alipayh5'){
			return '支付宝WAP支付';
		}elseif($pay_type == 'weixin'){
			return '微信支付';
		}elseif($pay_type == 'tenpay'){
			return '财付通';
		}elseif($pay_type == 'yeepay'){
			return '易宝支付';
		}elseif($pay_type == 'allinpay'){
			return '通联支付';
		}elseif($pay_type == 'chinabank'){
			return '网银在线';
		}elseif($pay_type == 'weixinh5'){
			return '微信WAP支付';
		}elseif($pay_type == 'baidu'){
			return '百度钱包';
		}elseif($pay_type == 'unionpay'){
			return '银联支付';
		}elseif($pay_type == 'offline'){
			return '线下付款';
		}elseif($pay_type == 'ccb'){
			return '建设银行';
		}elseif($pay_type == 'yzfpay'){
			return '翼支付';
		}elseif($pay_type == 'yzfpay_offline'){
			return '翼支付线下';
		}elseif($pay_type == 'wftpay'){
			return '网付通';
		}elseif($pay_type == 'allinpay_mer'){
			return '通联支付';
		}elseif($pay_type == 'merchantwarrior'){
			return '信用卡支付';
		}elseif($pay_type == 'nmgpay'){
			return '在线支付';
		}elseif($pay_type == 'qiye'){
			return '企业预付款支付';
		}elseif($pay_type == 'yzfpay_offline'){
			return '翼支付线下';
		}else{
			if ($paid) {
				return '余额支付';
			} else {
				return L_('未支付');
			}
		}
	}
}