<?php
/**
 * 系统后台餐饮店铺model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/26 15:11
 */

namespace app\foodshop\model\service\store;

use app\common\model\service\weixin\RecognitionService;
use app\foodshop\model\db\MerchantStoreFoodshop as MerchantStoreFoodshopModel;
use app\merchant\model\db\MerchantStore;
use app\merchant\model\service\MerchantService;
use app\merchant\model\service\MerchantStoreOpenTimeService;
use app\merchant\model\service\MerchantStoreService as MerchantStoreService;
use app\common\model\service\AreaService as AreaService;
use app\group\model\service\StoreGroupService;
use app\foodshop\model\service\order_print\DiningPrintRuleService;
use app\merchant\model\service\MerchantUserRelationService;
use map\longLat;
use think\facade\Db;

class MerchantStoreFoodshopService
{
    public $merchantStoreFoodshopModel = null;

    public function __construct()
    {
        $this->merchantStoreFoodshopModel = new MerchantStoreFoodshopModel();
    }

    /**
     * 获取店铺
     * @param $storeId int
     * @return array
     */
    public function getStoreByStoreId($storeId)
    {
        if (!$storeId) {
            return [];
        }

        $store = $this->merchantStoreFoodshopModel->getStoreByStoreId($storeId);
        if (!$store) {
            return [];
        }

        return $store->toArray();
    }

    /**
     * 验证线上排号功能状态,是否配置打印排号小票打印机
     * @param $storeId int
     * @param $checkIsOpen bool 是否线上排号功能
     * @param $checkIsPrint bool 是否配置打印排号小票打印机
     * @return array
     */
    public function checkStoreQueue($storeId, $checkIsOpen = false, $checkIsPrint = false)
    {
        // 餐饮店铺信息
        $foodshopStore = $this->getStoreByStoreId($storeId);

        if (empty($foodshopStore)) {
            return false;
        }

        if ($checkIsOpen) {
            if ($foodshopStore['queue_is_open'] == 0) {
                return  false;
            }

            // 店铺信息
            $store = (new \app\merchant\model\service\MerchantStoreService())->getStoreByStoreId($storeId);

            $isClose = 0;
            if ($store['open_1'] == '00:00:00' && $store['close_1'] == '00:00:00') {
                $store['business_time'] = '24小时营业';
            } else {
                $now_time = time();
                $isClose = 1;
                $open_1 = strtotime(date('Y-m-d') . $store['open_1']);
                $close_1 = strtotime(date('Y-m-d') . $store['close_1']);
                if ($open_1 >= $close_1) {
                    $close_1 += 86400;
                }
                if ($open_1 < $now_time && $now_time < $close_1) {
                    $isClose = 0;
                }
                if ($store['open_2'] != '00:00:00' || $store['close_2'] != '00:00:00') {
                    $open_2 = strtotime(date('Y-m-d') . $store['open_2']);
                    $close_2 = strtotime(date('Y-m-d') . $store['close_2']);
                    if ($open_2 >= $close_2) {
                        $close_2 += 86400;
                    }
                    if ($open_2 < $now_time && $now_time < $close_2) {
                        $isClose = 0;
                    }
                }
                if ($store['open_3'] != '00:00:00' || $store['close_3'] != '00:00:00') {
                    $store['business_time'] .= ';' . substr($store['open_3'], 0, -3) . '~' . substr($store['close_3'], 0, -3);
                    $open_3 = strtotime(date('Y-m-d') . $store['open_3']);
                    $close_3 = strtotime(date('Y-m-d') . $store['close_3']);
                    if ($open_3 >= $close_3) {
                        $close_3 += 86400;
                    }
                    if ($open_3 < $now_time && $now_time < $close_3) {
                        $isClose = 0;
                    }
                }
            }

            if ($isClose) {
                return  false;
            }
        }

        if ($checkIsPrint) {
            // 排号小票打印机列表
            $condition_where = [];
            $condition_where[] = ['r.store_id', '=', $storeId];
            $condition_where[] = ['', 'exp', Db::raw("FIND_IN_SET('3',r.print_type)")];
            $diningPrintRuleService = new DiningPrintRuleService();
            $printList = $diningPrintRuleService->getRuleAndPrint($condition_where);

            if (empty($printList)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 验证并得到店铺信息
     * @param $storeId int
     * @param $isReturn bool 是否验证
     * @param $checkOpenTime bool 是否营业时间
     * @return array
     */
    public function checkStore($storeId, $isReturn = true, $checkOpenTime = true)
    {
        // 店铺信息
        $store = (new \app\merchant\model\service\MerchantStoreService())->getStoreByStoreId($storeId);
        // 餐饮店铺信息
        $foodshopStore = $this->getStoreByStoreId($storeId);

        // 店铺状态异常
        if ($isReturn) {
            if (empty($store)) {
                throw new \think\Exception(L_("不存在的店铺"),1003);
            } elseif ($store['status'] != 1) {
                throw new \think\Exception(L_("店铺状态异常"),1003);
            } elseif ($store['have_meal'] != 1) {
                throw new \think\Exception(L_("店铺不支持餐饮"),1003);
            } elseif ($store['foodshop_on_sale'] == 2) {
                throw new \think\Exception(L_("店铺已被下架"),1003);
            }

            // 商家
            $merchant = (new \app\merchant\model\service\MerchantService())->getMerchantByMerId($store['mer_id']);
            if (empty($merchant)) {
                throw new \think\Exception(L_("不存在的商家"),1003);
            } elseif ($merchant['status'] != 1) {
                throw new \think\Exception(L_("商家状态异常"),1003);
            }

            // 验证营业时间
            if ($checkOpenTime == true) {
                $store['business_time'] = '';
                $isClose = 0;
                if ($store['open_1'] == '00:00:00' && $store['close_1'] == '00:00:00') {
                    $store['business_time'] = '24小时营业';
                } else {
                    $now_time = time();
                    $isClose = 1;
                    $open_1 = strtotime(date('Y-m-d') . $store['open_1']);
                    $close_1 = strtotime(date('Y-m-d') . $store['close_1']);
                    if ($open_1 >= $close_1) {
                        $close_1 += 86400;
                    }
                    if ($open_1 < $now_time && $now_time < $close_1) {
                        $isClose = 0;
                    }
                    if ($store['open_2'] != '00:00:00' || $store['close_2'] != '00:00:00') {
                        $open_2 = strtotime(date('Y-m-d') . $store['open_2']);
                        $close_2 = strtotime(date('Y-m-d') . $store['close_2']);
                        if ($open_2 >= $close_2) {
                            $close_2 += 86400;
                        }
                        if ($open_2 < $now_time && $now_time < $close_2) {
                            $isClose = 0;
                        }
                    }
                    if ($store['open_3'] != '00:00:00' || $store['close_3'] != '00:00:00') {
                        $store['business_time'] .= ';' . substr($store['open_3'], 0, -3) . '~' . substr($store['close_3'], 0, -3);
                        $open_3 = strtotime(date('Y-m-d') . $store['open_3']);
                        $close_3 = strtotime(date('Y-m-d') . $store['close_3']);
                        if ($open_3 >= $close_3) {
                            $close_3 += 86400;
                        }
                        if ($open_3 < $now_time && $now_time < $close_3) {
                            $isClose = 0;
                        }
                    }
                }

                if ($isClose) {
                    throw new \think\Exception(L_("店铺不在营业中"),1003);
                }
            }
        }


        $images = (new \app\merchant\model\service\storeImageService())->getAllImageByPath($store['pic_info']);
        $store['image_list'] = $images;
        $store['image'] = $images ? array_shift($images) : '';

        if ($store['logo']) {
            $store['logo'] = replace_file_domain($store['logo']);
        }

        $background = '';
        if (!empty($foodshopStore['background'])) {
            $tmpPicArr = explode(';', $foodshopStore['background']);
            $img = $tmpPicArr[0] ?? '';
            $background = (new FoodshopStoreImageService())->getImageByPath($img);
            $background = $background && $background['image'] ? $background['image'] : '';
        }
        $foodshopStore['background'] = $background ? $background : '';

        return array_merge($store, $foodshopStore);
    }

    /**
     * 获取店铺列表
     * @param $params array
     * @return array
     */
    public function getStoreList($params,$user=[]){
		$areaId = isset($params['area_id']) ? $params['area_id'] : '';
		$catId = isset($params['cat_id']) ? $params['cat_id'] : '';
		$sort = isset($params['sort']) ? $params['sort'] : '';
		$lat = isset($params['lat']) ? $params['lat'] : '';
		$long = isset($params['long']) ? $params['long'] : '';
		$recommend = isset($params['recommend']) ? $params['recommend'] : '';
		$keyword = isset($params['keyword']) ? $params['keyword'] : '';
		$is_queue = isset($params['queue']) ? $params['queue'] : '-1';
		$page = isset($params['page']) ? $params['page'] : '';
        $pageSize = isset($params['pageSize']) ? $params['pageSize'] : '10';
		$areaKeyword = isset($params['areaKeyword']) ? $params['areaKeyword'] : '';
		$isMap = isset($params['isMap']) ? $params['isMap'] : '';
		$merchantWxapp = isset($params['merchant_wxapp']) ? $params['merchant_wxapp'] : '0'; // 是否商家小程序
        $merId = isset($params['mer_id']) ? $params['mer_id'] : '';
		$diningType = isset($params['dining_type']) ? $params['dining_type'] : '';//就餐方式
		$storeId = isset($params['store_id']) ? $params['store_id'] : '';//店铺id
        $now_city = isset($params['now_city']) && !empty($params['now_city']) ? $params['now_city'] : cfg('now_city');

        // 网站域名
        $site_url = cfg('site_url');

        $circleId = 0;
        if (!empty($areaId)) {
            $tmpArea = (new AreaService())->getAreaByAreaId($areaId);
            if (empty($tmpArea)) {
                throw new \think\Exception("当前区域不存在！");
            }
            if ($tmpArea['area_type'] == 3) {//区域
                $nowArea = $tmpArea;
            } else {
                //商圈
                $nowCircle = $tmpArea;
                $nowArea = (new AreaService())->getAreaByAreaId($tmpArea['area_pid']);
                if (empty($nowArea)) {
                    throw new \think\Exception("当前区域不存在！");
                }
                $circleId = $nowCircle['area_id'];
            }
            $areaId = $nowArea['area_id'];
        }
        $catFid = 0;
        if ($catId) {
            $nowCategory = (new \app\foodshop\model\service\store\MealStoreCategoryService())->getCategoryByCatId($catId);
            if (empty($nowCategory)) {
                throw new \think\Exception("此分类不存在！");
            }

            if (!empty($nowCategory['cat_fid'])) {
                // 存在父分类
                $fatherCategory = (new \app\foodshop\model\service\store\MealStoreCategoryService())->getCategoryByCatId($nowCategory['cat_fid']);
                $catFid = $nowCategory['cat_fid'];
                $catId = $nowCategory['cat_id'];
            } else {
                $catId = 0;
                $catFid = $nowCategory['cat_id'];
            }
        }


        //排序方式
        $order = [];

        //搜索条件
        $condition_where = '';

        // 搜索字段
        $condition_field = 'ms.*, s.*,ms.foodshop_tables_discount as foodshop_tables_discount_shop,`ms`.other_discount as other_discount_shop';
        // 搜索条件

        $condition_where .= "s.city_id='" . $now_city  . "' AND s.have_meal=1 AND s.status=1 AND s.foodshop_on_sale=1";

        // 当前时间
        $time = date('H:i:s');

        //获取分类下店铺歇业时显示方式
        $searchCatId = $catId ? $catId : $catFid;
        if ($searchCatId) {
            $mealStoreCategory = (new MealStoreCategoryService())->getCategoryByCatId($searchCatId);
            $show_method = isset($mealStoreCategory['show_method']) ? $mealStoreCategory['show_method'] : '';
        }
        //0:不显示，1:正常显示，2：靠后显示
        $show_method = isset($show_method) && is_numeric($show_method) ? $show_method : 2;
        if ($show_method == 0) {
            $condition_where .= " AND `s`.`is_business_open`= 1";
        } elseif ($show_method == 2) {
            $condition_field .= ",(CASE
	        WHEN (`s`.`is_business_open`='1') then 1
	        ELSE 0
	        END) as `t_sort`";
            $order['t_sort'] = 'DESC';
        }

        // 是否开启排号
		if ($is_queue != -1) {
			if ($is_queue == 1) {
				$condition_where .= ' AND ms.queue_is_open=1';
			} elseif ($is_queue == 0) {
				$condition_where .= ' AND ms.queue_is_open=0';
			}
        }

		// 商家id
		if ($merId) {
		    $condition_where .= ' AND s.mer_id='.$merId;
        }
        // 店铺id
        if ($storeId) {
            if(is_array($storeId)){
                $condition_where .= ' AND s.store_id in ('.implode(',' ,$storeId).')';
            }else{
                $condition_where .= ' AND s.store_id='.$storeId;
            }
        }


        // 就餐方式 selftake - 自取 inhouse - 堂食 book-预定
		if($diningType){
		    switch ($diningType){
                case 'book':
                    $condition_where .= ' AND ms.is_book=1';
                    break;
                case 'selftake':
                    $condition_where .= ' AND ms.dining_type in (2,3)';
                    break;
                case 'inhouse':
                    $condition_where .= ' AND ms.dining_type in (1,3)';
                    break;
            }
        }

		// 地图商圈关键字搜索
		$circleIdArr = [];
		if (isset($areaKeyword)&&$areaKeyword) {
			//搜索当前城市的商圈
			$areaList = (new AreaService())->getAreaListByKeyword($areaKeyword);
			$circleIdArr = array_column($areaList,'area_id');
			if($circleIdArr){
				$condition_where .= " AND `s`.`circle_id` in(".implode(',',$circleIdArr).")";
			}
		}

		//区域搜索
		if($areaId || $circleId){
			if ($circleId) {
				$condition_where .= " AND `s`.`circle_id`='{$circleId}'";
			} else {
				$condition_where .= " AND `s`.`area_id`='{$areaId}'";
			}
		}
        $storeIdArr = [];
        // 分类关系
        if ($catFid || $catId) {
            if ($catId) {
                $relation = (new MealStoreCategoryRelationService())->getCategoryRelationByCatId($catId);
            } elseif ($catFid) {
                $relation = (new MealStoreCategoryRelationService())->getCategoryRelationByCatFid($catFid);
            }

            // 店铺ID
            $storeIdArr = array_unique(array_column($relation, 'store_id'));

            if (empty($storeIdArr)) {
                return array('store_list' => null, 'store_count' => 0, 'totalPage' => 0);
            }
        }

        // 搜索店铺
        if (isset($params['store_id']) && $params['store_id']) {
            // 取交集
            $storeIdArr && $params['store_id'] = array_intersect($storeIdArr, $params['store_id']);
            if ($params['store_id']) {
                $condition_where .= ' AND s.store_id IN (' . implode(',', $params['store_id']) . ')';
            } else {
                return array('store_list' => null, 'totalPage' => 0, 'store_count' => 0);
            }
        } else {
            if (isset($storeIdArr) && $storeIdArr) {
                $condition_where .= ' AND s.store_id IN (' . implode(',', $storeIdArr) . ')';
            }
        }

        // 关键字搜索
        if (isset($keyword) && $keyword) {
            $condition_where .= " AND `s`.`name` LIKE '%{$keyword}%'";
        }


        // 筛选
        if ($recommend) {
            $recommendArr = explode(',', $recommend);
            foreach ($recommendArr as $_recommend) {
                switch ($_recommend) {
                    case 'quick_pay'://买单
                        $quick_pay = 1;
                        break;
                    case 'order_table'://在线订座
                        $condition_where .= " AND `ms`.`is_book`=1";
                        break;
                    case 'order_queue'://在线排队
                        $condition_where .= " AND `ms`.`queue_is_open`=1";
                        break;
                    case 'open_shop'://在线外卖
                        $condition_where .= " AND `s`.`shop_on_sale`=1 AND `s`.`have_shop`=1";
                        break;
                    case '0_50'://50以下
                        $condition_where .= " AND `ms`.`mean_money` <= 50";
                        break;
                    case '50_100'://50_100
                        $condition_where .= " AND `ms`.`mean_money` >= 50 AND `ms`.`mean_money` <= 100";
                        break;
                    case '100_300'://100_300
                        $condition_where .= " AND `ms`.`mean_money` >= 100 AND `ms`.`mean_money` <= 300";
                        break;
                    case '300_all'://300_all
                        $condition_where .= " AND `ms`.`mean_money` >= 300";
                        break;

                }

            }
        }

        // 经纬度
        if ($lat > 0 && $long > 0) {
            if (in_array($sort, ['juli', 'defaults', 'score']) || $isMap) {
                $condition_field .= ", ROUND(6367.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
            }
        } else {
            $sort = $sort == 'juli' ? '' : $sort;
        }

        $join_field = '';

        // 排序
        switch ($sort) {
            case 'score'://店铺评分
                $order['ms.score_mean'] = 'DESC';
                if ($lat > 0 && $long > 0) {
                    $order['juli'] = 'ASC';
                }
                $order['ms.create_time'] = 'DESC';
                break;
            case 'permoney_desc'://人均由高到低
                $order['ms.mean_money'] = 'DESC';
                $order['ms.create_time'] = 'DESC';
                break;
            case 'permoney_asc'://人均由低到高
                $order['ms.mean_money'] = 'ASC';
                $order['ms.create_time'] = 'DESC';
                break;
            case 'juli'://距离
                $order['juli'] = 'ASC';
                $order['ms.create_time'] = 'DESC';
                break;
            default://智能排序
                $order['ms.sort'] = 'DESC';
                if ($lat > 0 && $long > 0) {
                    $order['juli'] = 'ASC';
                }
                $order['ms.create_time'] = 'DESC';
        }

        // 当前页数
		$page = request()->param('page', '1', 'intval');
        if($isMap){
            $page = 0;
            $pageSize = 30;
        }

        // 获得店铺列表
        $storeList = (new MerchantStoreService())->getStoreListByModule('merchant_store_foodshop', $condition_where, $order, $condition_field, $page, $pageSize);

        $result = [];

        // 商圈id数组
        $circleIdArr = [];

		// 获得所需字段
		foreach ($storeList as $tmp) {
			$circleIdArr[] = $tmp['circle_id'];
		    $_store = $this->formatStore($tmp,$long,$lat);
			$result[$tmp['store_id']] = $_store;
		}

		// 分类数组
		$categorybyStore = [];

		// 团购列表

		// 店铺ID
		$storeIdArr = array_keys($result);
		if ($storeIdArr && !$merchantWxapp) {
			$now_time = time();
			// 店铺ID
			$storeIdStr = implode(',', $storeIdArr);
			// 获得商圈
			$areabyAreaId = [];
			if($circleIdArr){
				$areaWhere[] = ['area_id','in',implode(',',$circleIdArr)];
				$areaList = (new AreaService())->getAreaListByCondition($areaWhere);
				$areabyAreaId = array_column($areaList, 'area_name', 'area_id');
			}

			// 获取店铺分类
			$relationCategory = (new \app\foodshop\model\service\store\MealStoreCategoryRelationService())->getCategoryRelationByStoreId($storeIdStr);
			$catIdArr = array_column($relationCategory,'cat_id');
			$where[] = ['cat_id', 'in', implode(',',$catIdArr)];
			$categoryList = (new \app\foodshop\model\service\store\MealStoreCategoryService())->getCategoryByCondition($where);
			$categorybyCatId = array_column($categoryList, 'cat_name', 'cat_id');
			foreach($relationCategory as $_category){
				if(isset($categorybyStore[$_category['store_id']]) && count($categorybyStore[$_category['store_id']])>=2){
					continue;
				}
				$categorybyStore[$_category['store_id']][] = $categorybyCatId[$_category['cat_id']] ?? [];
			}

			// 团购列表
			$storeGroupService = new StoreGroupService();
			$groupList = $storeGroupService->getNormalStoreGroup($storeIdStr);
			foreach ($groupList as $row) {
				if (isset($result[$row['store_id']])) {
					if ($result[$row['store_id']]['group_count'] < 3) {
						$result[$row['store_id']]['group_list'][] = $row;
					}
					$result[$row['store_id']]['group_count']++;
				}
			}
		}

		// 查看店铺是否收藏过
        $collectStoreIdArr = [];
        if($storeIdArr && $user && $user['uid']){
            $where = [
                'type' => 'store',
                'uid' => $user['uid']
            ];
            $collectStoreIdArr = array_column((new MerchantUserRelationService())->getSome($where),'store_id');
        }

		$_store_list = array();
		foreach ($result as $row) {
			// 店铺分类
			$row['category_arr'] = isset($categorybyStore[$row['store_id']]) ? $categorybyStore[$row['store_id']] : [];
			// 店铺商圈
			$row['area_name'] = isset($areabyAreaId[$row['circle_id']]) ? $areabyAreaId[$row['circle_id']] : '';
			// 是否收藏
            $row['is_collect'] = 0;
			if(in_array($row['store_id'],$collectStoreIdArr)){
                $row['is_collect'] = 1;
            }
            //传递了该参数，店铺经纬度转换成腾讯地图
            if ($isMap && abs($row['lat']) > 0 && abs($row['long']) > 0) {
                $longlatClass = new longlat();
                $gcj_longlat = $longlatClass->baiduToGcj02($row['lat'], $row['long']);
                $row['lat'] = $gcj_longlat['lat'];
                $row['long'] = $gcj_longlat['lng'];
            }
			$_store_list[] = $row;
		}


		$return['store_list'] = $_store_list;
		return $return;
	}
	
	public function formatStore($store, $long = '', $lat = '', $merchantWxapp = 0){
		$returnArr = [];

		//店铺名称
		$returnArr['name'] = $store['name'];
		// 是否关店
		$returnArr['is_close'] = $store['is_business_open'] ? 0 : 1;
		//评分
		$returnArr['score_mean'] = $store['score_mean']==0 ? '5.0' : $store['score_mean'];
		//右侧标识
		$returnArr['bus_label'] = [];
		// 排号
		if($store['queue_is_open']==1){
			$returnArr['bus_label'][] = [
				'type' => 'queue',
				'name' => L_('排'),
			];
		}

		// 外卖
        // 外卖优惠
        $returnArr['shop_discount'] = [];
        if ($store['shop_on_sale'] == 1 && $store['have_shop'] == 1) {
            $returnArr['bus_label'][] = [
                'type' => 'shop',
                'name' => L_('外'),
            ];

            $shop_discount = (new \app\merchant\model\service\ShopDiscountService())->getDiscounts($store['mer_id'], $store['store_id']);
            if ($shop_discount) {
                $shop_discount = (new \app\merchant\model\service\ShopDiscountService())->formartDiscount($shop_discount, $store['store_id']);
                $shop_discount = (new \app\merchant\model\service\ShopDiscountService())->simpleParseCoupon($shop_discount['coupon_list']);
                $returnArr['shop_discount'] = $shop_discount;
            }
        }

        // 预订
        if ($store['is_book'] == 1) {
            $returnArr['bus_label'][] = [
                'type' => 'book',
                'name' => L_('订'),
            ];
        }


        // 快速买单
        $returnArr['store_pay_discount'] = [];
        $store['discount_txt'] = $store['discount_txt'] ? unserialize($store['discount_txt']) : array();
        if (cfg('pay_in_store') && $store['discount_txt']) {
            $returnArr['bus_label'][] = [
                'type' => 'check',
                'name' => L_('买'),
            ];

            // 快速买单优惠
            $store_pay_discount = (new \app\merchant\model\service\MerchantStoreService())->getStoreQuickDiscount($store['discount_txt']);
            $returnArr['store_pay_discount'] = $store_pay_discount ? [$store_pay_discount] : [];
		}

		// 店铺图片
		$images = (new \app\merchant\model\service\storeImageService())->getAllImageByPath($store['pic_info']);
		$returnArr['image'] = $images ? thumb(array_shift($images),180) : '';
		
		//人均消费金额
		$returnArr['permoney'] = $store['mean_money'];
		//经度
		$returnArr['long'] = $store['long'];
		// 纬度
		$returnArr['lat'] = $store['lat'];
		//距离
		if (isset($store['juli'])&&$store['juli']) {
			$returnArr['range'] = get_range($store['juli']);
		} else if($long && $lat){
			$location2 = (new longLat())->gpsToBaidu($store['lat'], $store['long']);//转换腾讯坐标到百度坐标
			$jl = get_distance($location2['lat'], $location2['lng'], $lat, $long);
			$returnArr['range'] = get_range($jl);
		}else{
			$returnArr['range'] = 0;
		}

		//惠(团购)
		$returnArr['group_list'] = [];

        // 当日营业时间
        $returnArr['open_time'] = (new MerchantStoreOpenTimeService())->getTodayOpenTime($store['store_id']);

        // 支持的点餐模式
        $returnArr['dining_type'] = [];
        if($store['is_book']){
            $returnArr['dining_type'][] = [
                'type' => 'book',
                'name' => L_('预订')
            ];
        }

        if(in_array($store['dining_type'],[1,3])){
            $returnArr['dining_type'][] = [
                'type' => 'inhouse',
                'name' => L_('堂食')
            ];
        }

        if(in_array($store['dining_type'],[2,3])){
            $returnArr['dining_type'][] = [
                'type' => 'selftake',
                'name' => L_('自取')
            ];
        }
        // 店铺地址
        $returnArr['address'] = $store['adress'];
        // 店铺电话
        $phone = explode(' ',$store['phone']);
        $returnArr['phone'] = $phone[0] ?? '';
        $returnArr['book_type'] = $store['book_type'];//预订方式 1-提前选桌 2-提前选菜


		$returnArr['group_count'] = 0;
		// $tmps['store_pay'] = $site_url.str_replace('appapi.php','wap.php',U('My/pay', array('store_id' => $tmp['store_id'])));
        //$returnArr['url'] = cfg('site_url').'/packapp/plat/pages/store/homePage?store_id='.$store['store_id'];
        $returnArr['url'] = get_base_url('pages/store/v1/home/index?store_id=' . $store['store_id'], true);
		$returnArr['store_id'] = $store['store_id'];
		$returnArr['circle_id'] = $store['circle_id'];
		return $returnArr;
		
	}
	
	/**
     * 获取平台所有店铺列表
     * @param $params array
     * @return array
     */
    public function getPlatStoreList($params, $systemUser)
    {

        $where = "s.status=1 AND s.have_meal=1";//array('status' => 1);

        if (!empty($params['keyword'])) {
            $where .= " AND s.name LIKE '%{$params['keyword']}%'";
        }

        if ($systemUser['area_id']) {
            $nowArea = (new AreaService())->getAreaByAreaId($systemUser['area_id']);

            if ($nowArea['area_type'] == 3) {//区域管理员
                $areaIndex = 'area_id';
            } elseif ($nowArea['area_type'] == 2) {
                $areaIndex = 'city_id';//城市管理员
            } elseif ($nowArea['area_type'] == 1) {
                $areaIndex = 'province_id';//省份管理员
            }
            $where .= " AND s.{$areaIndex} = '{$systemUser['area_id']}'";
        }


        // 当前页数
        $page = request()->param('page', '1', 'intval');

        // 每页显示条数
        $pageSize = isset($params['pageSize']) ? $params['pageSize'] : '10';

        // 总数
        $count = (new MerchantStoreService())->getStoreCountByModule('merchant_store_foodshop', $where);


        // 搜索字段
        $conditionField = 'ms.*, s.*,s.name as store_name,`mm`.name as merchant_name,ms.sort as store_sort';
        // 排序
        $order = [
            'ms.sort' => 'DESC',
            's.store_id' => 'DESC',
        ];
        if ($params['order'] && is_array($params['order'])) {
            foreach ($params['order'] as $key => $_order) {
                $sort = $_order == 'descend' ? 'DESC' : 'ASC';
                switch ($key) {
                    case 'store_sort'://排序值
                        $order = [];
                        $order['ms.sort'] = $sort;
                        break;
                    case 'last_time'://创建时间
                        $order = [];
                        $order['s.last_time'] = $sort;
                        break;

                }
            }
            $order['s.store_id'] = 'DESC';
        }


        // 获得店铺列表
        $storeList = (new MerchantStoreService())->getStoreListByModule('merchant_store_foodshop', $where, $order, $conditionField, $page, $pageSize);


        // 判断该管理员是否有登录商家后台的权限
        // var_dump($systemUser);
        if ($systemUser['level'] < 2) {
            if (!in_array(310, $systemUser['menus'])) $return['notShow'] = 1;
        }

        $result = [];
        // 获得所需字段
        foreach ($storeList as &$tmp) {
            // $tmp = $this->formatStore($tmp);
            $tmp['key'] = (string)$tmp['store_id'];
            $tmp['last_time'] = date('Y-m-d H:i:s', $tmp['last_time']);
        }

        $return['store_list'] = $storeList;
        $return['count'] = $count;
        return $return;
    }

    /**
     * 保存店铺排序
     * @param $param array 店铺优惠
     * @return array
     */
    public function saveSort($param)
    {
        $storeId = isset($param['store_id']) ? $param['store_id'] : 0;
        $sort = isset($param['sort']) ? $param['sort'] : 0;

        $where = ['store_id' => $storeId];
        $data = ['sort' => $sort];
        $res = $this->updateThis($where, $data);

        if ($res === false) {
            throw new \think\Exception("更新失败");
        }
        return $res;
    }

    /**
     * 获取店铺筛选信息
     * @param $discount array 店铺优惠
     * @return array
     */
    public function getScreenList($now_city)
    {
        $returnArr = [];
        //附近
        $returnArr['nearby'] = (new AreaService())->getAreaListByAreaPid($now_city);

        //美食分类
        $returnArr['category_list'] = (new MealStoreCategoryService())->getAllCategory(1);

        //排序
        $returnArr['sort'] = [
            [
                "sort_name" => L_("智能排序"),
                "sort_value" => "defaults",
            ],
            [
                "sort_name" => L_("评价排序"),
                "sort_value" => "score",
            ],
            [
                "sort_name" => L_("离我最近排序"),
                "sort_value" => "juli",
            ],
            [
                "sort_name" => L_("人均由高到低排序"),
                "sort_value" => "permoney_desc",
            ],
            [
                "sort_name" => L_("人均由低到高排序"),
                "sort_value" => "permoney_asc",
            ],

        ];

        // 筛选
        $returnArr['recommend'] = [
            [
                'name' => L_('服务'),
                'recommend_list' => [
                    [
                        'value' => 'quick_pay',
                        'name' => L_('买单'),
                    ],
                    [
                        'value' => 'order_table',
                        'name' => L_('在线订座'),
                    ],
                    [
                        'value' => 'order_queue',
                        'name' => L_('在线排队'),
                    ],
                    [
                        'value' => 'open_shop',
                        'name' => L_('在线外卖'),
                    ],
                ],
            ],
            [
                'name' => L_('价格'),
                'recommend_list' => [
                    [
                        'value' => '0_50',
                        'name' => L_('50以下'),
                    ],
                    [
                        'value' => '50_100',
                        'name' => L_('50-100'),
                    ],
                    [
                        'value' => '100_300',
                        'name' => L_('100-300'),
                    ],
                    [
                        'value' => '300_all',
                        'name' => L_('300以上'),
                    ],
                ],
            ],
        ];

        return $returnArr;
    }


    /**
     * 获取店铺列表
     * @author 张涛
     * @date 2020/07/06
     */
    public function getFoodshopStoreListByMerId($merId, $page = 1, $pageSize = 15)
    {
        $storeMod = new MerchantStore();
        $where = ['s.mer_id' => $merId, 's.have_meal' => 1, 's.status' => 1];
        $fields = '`s`.* , `f`.`store_id` AS `sid`, `f`.`open_from_shop`';
        $count = $storeMod->getFoodshopStoreCountByMerId($where);
        if ($count < 1) {
            $lists = [];
        } else {
            $lists = $storeMod->getFoodshopStoreListByMerId($where, $fields, $page, $pageSize);
        }
        $rs['list'] = $lists;
        $rs['total'] = $count;
        return $rs;
    }

    /**
     * 获取店铺列表
     * @author 张涛
     * @date 2020/07/08
     */
    public function foodshopEdit($post)
    {
        if (empty($post['store_id'])) {
            throw new \think\Exception("店铺ID不存在");
        }
        $storeId = intval($post['store_id']);
        $foodshopMod = $this->merchantStoreFoodshopModel;
        $foodshop = $foodshopMod->where('store_id', $storeId)->find();

        $allFields = ['store_id', 'store_notice', 'is_book', 'book_type', 'book_time', 'book_day', 'cancel_time', 'take_seat_by_scan', 'settle_accounts_type', 'dining_type', 'share_table_type',
            'open_online_pay', 'mean_money', 'book_start', 'book_stop', 'queue_is_open', 'queue_content','print_type','scan_order_bg_img'];
        $updateFoodshopData = $post;
        //开启预定，判断开始时间不能大于结束时间
        if (isset($updateFoodshopData['is_book']) && $updateFoodshopData['is_book'] && strtotime($updateFoodshopData['book_start']) > strtotime($updateFoodshopData['book_stop'])) {
            throw new \think\Exception("预定结束时间不能大于开始时间");
        }

        if (empty($foodshop)) {//第一次完善
            $updateFoodshopData['store_id'] = $storeId;
            if ($foodshopMod->create($updateFoodshopData, $allFields) === false) {
                throw new \think\Exception("更新失败");
            }
        } else {
            if ($foodshopMod->update($updateFoodshopData, ['store_id' => $storeId], $allFields) === false) {
                throw new \think\Exception("更新失败");
            }
        }

        //更新店铺所属分类
        (new MealStoreCategoryRelationService)->updateRelationByStoreId($storeId, $post['cate_id_arr']);
    }

    /**
     * 获得商家二维码id
     * @param $param array 登录信息
     * @return array
     */
    public function seeQrcode($storeId)
    {
        $merchantStoreService = new MerchantStoreService();
        $store = $merchantStoreService->getQrcode($storeId);
        if (empty($store)) {
            return false;
        }
        try {
            $result = (new RecognitionService())->seeQrcode($store['qrcode_id'], 'meal', $storeId);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data)
    {

        $result = $this->merchantStoreFoodshopModel->save($data);
        if (!$result) {
            return false;
        }

        return $this->merchantStoreFoodshopModel->id;
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getOne($where)
    {
        $detail = $this->merchantStoreFoodshopModel->getOne($where);
        if (!$detail) {
            return [];
        }
        return $detail->toArray();
    }

    /**
     * 更新店铺数据
     * @param $where array 条件
     * @param $data array 数据
     * @return array
     */
    public function updateThis($where, $data)
    {
        if (empty($where) || !$data) {
            return false;
        }

        $result = $this->merchantStoreFoodshopModel->updateThis($where, $data);
        if ($result === false) {
            return false;
        }

        return $result;
    }
}