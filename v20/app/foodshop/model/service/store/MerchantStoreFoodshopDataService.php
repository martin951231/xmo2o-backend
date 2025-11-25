<?php
/**
 * 系统后台餐饮店铺model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/26 15:11
 */

namespace app\foodshop\model\service\store;
use app\foodshop\model\db\MerchantStoreFoodshopData as MerchantStoreFoodshopDataModel;
use app\merchant\model\service\MerchantStoreService as MerchantStoreService;
use app\foodshop\model\service\goods\FoodshopGoodsLibraryService as FoodshopGoodsLibraryService;
use app\common\model\service\AreaService as AreaService;
use longLat;
class MerchantStoreFoodshopDataService{
    public $merchantStoreFoodshopDataModel = null;
    public function __construct()
    {
        $this->merchantStoreFoodshopDataModel = new MerchantStoreFoodshopDataModel();
    }
	/**
     * 获取店铺
     * @param $storeId int 
     * @return array
     */
    public function getStoreDataByStoreId($storeId) {
        if(!$storeId){
            return [];
        }

        $store = $this->merchantStoreFoodshopDataModel->getStoreDataByStoreId($storeId);
        if(!$store) {
            return [];
        }
         
        return $store->toArray(); 
	}

	/**
     * 验证并得到店铺信息
     * @param $storeId int 
     * @param $isReturn bool 是否验证 
     * @param $checkOpenTime bool 是否营业时间
     * @return array
     */
	public function getStoreDataInfo($storeId)
	{
		// 店铺装修数据 未同步餐饮数据
		$subjectInfo = $this->getStoreDataByStoreId($storeId);

		//今日特惠是否展示
		$returnArr['shop_preferential'] = array('good_count'=>0);
		$returnArr['shop_preferential_show'] = 0;


		// 橱窗名称
		$returnArr['shop_preferential']['name'] = $subjectInfo['preferential_name'] ?? '';

        if(!empty($subjectInfo['banner'])) { //banner图
            $banner_list = unserialize($subjectInfo['banner']);

            foreach($banner_list as $key => $value) {
                $banner_list[$key]['url'] = html_entity_decode($value['url']);
                if($value['status'] == 1) {
                    $banner_list[$key]['pic'] = replace_file_domain($value['pic']);
                } else {
                    unset($banner_list[$key]);
                }
            }
        } else {
            $banner_list = [];
        }
        $returnArr['banner'] = array_values($banner_list);

        // 商品信息
		$whereGoods = [
			'goods_ids' => $subjectInfo['preferential_good_id'] ?? '',
		];
		$goodList = (new FoodshopGoodsLibraryService())->getGoodsListByStoreId($storeId,$whereGoods, 'wap');
		if(empty($goodList)){
			return $returnArr;
		}


		// 重新排序
		$tempGoodList = array();
		foreach($goodList as $tmpGoods){
			$tempGoodList[$tmpGoods['product_id']] = $tmpGoods;
		}

		$goodList['list'] = array();
		if(isset($subjectInfo['preferential_good_id'])){
            $goodsIdArr = explode(',', $subjectInfo['preferential_good_id']);
            foreach($goodsIdArr as $goodsId){
                if(isset($tempGoodList[$goodsId]) && $tempGoodList[$goodsId]){
                    $goodList['list'][] = $tempGoodList[$goodsId];
                }
            }
        }

        if($subjectInfo && $subjectInfo['preferential_status']){
            $returnArr['shop_preferential_show'] = 1;
            $returnArr['shop_preferential']['list'] = $goodList['list'];
            $returnArr['shop_preferential']['good_count'] = count($goodList['list']);
        }
		return $returnArr;
	}

    /**
     * 获得热门商品的ids
     * @param $storeId int
     * @param $Type string 类型
     * @param $checkOpenTime bool 是否营业时间
     * @return array
     */
    public function getStoreDataInfoType($storeId, $type = '')
    {
        // 店铺装修数据 未同步餐饮数据
		$subjectInfo = $this->getStoreDataByStoreId($storeId);
		
        $goodList = [];
        // 商品信息
        if($type == 'hot') { //热销
            if(isset($subjectInfo['cat_hot_good_id']) && !empty($subjectInfo['cat_hot_good_id']) && $subjectInfo['cat_hot_status'] == 1) {

                $whereGoods = [
                    'goods_ids' => $subjectInfo['cat_hot_good_id']
                ];
            } else {
                return $goodList;
            }

            $typeInfo = ['cat_id' => $type, 'cat_name' => $subjectInfo['cat_hot_name'], 'desc' => $subjectInfo['cat_hot_desc'], 'sort' => $subjectInfo['cat_hot_sort']];
		} elseif($type == 'discount') { //折扣 优惠
            if(isset($subjectInfo['cat_discount_good_id']) && !empty($subjectInfo['cat_discount_good_id']) && $subjectInfo['cat_discount_status'] == 1) {
                $whereGoods = [
                    'goods_ids' => $subjectInfo['cat_discount_good_id']
                ];
            } else {
                return $goodList;
			}

            $typeInfo = ['cat_id' => $type, 'cat_name' => $subjectInfo['cat_discount_name'], 'desc' => $subjectInfo['cat_discount_desc'], 'sort' => $subjectInfo['cat_discount_sort']];
        } else {

            $typeInfo = [];
        }
        $whereGoods['show_type'] = 'tree';
		$goodList = (new FoodshopGoodsLibraryService())->getGoodsListByStoreId($storeId, $whereGoods, 'wap', $typeInfo);

        if($typeInfo['sort']){
            foreach ($goodList as $key => &$_goods) {
                $priceArr = array_column($_goods['goods_list'],'product_price');
                if($typeInfo['sort'] == 2){
                    array_multisort($priceArr,SORT_ASC,SORT_REGULAR ,$_goods['goods_list']);
                }else{
                    array_multisort($priceArr,SORT_DESC,SORT_REGULAR ,$_goods['goods_list']);
                }
            }
        }
        return $goodList;
    }
	
	/**
     * 获取店铺列表
     * @param $params array 
     * @return array
     */
    public function getStoreList($params){
		$areaId = isset($params['area_id']) ? $params['area_id'] : '';
		$catId = isset($params['cat_id']) ? $params['cat_id'] : '';
		$sort = isset($params['sort']) ? $params['sort'] : '';
		$lat = isset($params['lat']) ? $params['lat'] : '';
		$long = isset($params['long']) ? $params['long'] : '';
		$recommend = isset($params['recommend']) ? $params['recommend'] : '';
		$keyword = isset($params['keyword']) ? $params['keyword'] : '';
		$is_queue = isset($params['queue']) ? $params['queue'] : '-1';
		$page = isset($params['page']) ? $params['page'] : '';
		$areaKeyword = isset($params['areaKeyword']) ? $params['areaKeyword'] : '';
		$isMap = isset($params['isMap']) ? $params['isMap'] : '';

        // 网站域名
		$site_url  = cfg('site_url');
		
		$circleId = 0;
		if (!empty($areaId)) {
			$tmpArea = (new AreaService())->getAreaByAreaId($areaId);
			if(empty($tmpArea)){
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
		if($catId){
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
        $condition_where = "s.city_id='" . cfg('now_city') . "' AND s.have_meal=1 AND s.status=1 AND s.foodshop_on_sale=1";
        
        // 当前时间
        $time = date('H:i:s');

        //获取分类下店铺歇业时显示方式
        $searchCatId = $catId ? $catId : $catFid;
        if($searchCatId){
            $mealStoreCategory = (new MealStoreCategoryService())->getCategoryByCatId($searchCatId);
            $show_method = isset($mealStoreCategory['show_method']) ? $mealStoreCategory['show_method'] : '';
        }
		//0:不显示，1:正常显示，2：靠后显示
        $show_method = isset($show_method)&&is_numeric($show_method) ? $show_method : 2;
		if($show_method==0){			
			$condition_where .= " AND (";
            $condition_where .= "(`s`.`open_1`='00:00:00' AND `s`.`close_1`='00:00:00')";
            $condition_where .= " OR ((`s`.`open_1`<'$time' AND `s`.`close_1`>'$time')";
            $condition_where .= " OR (`s`.`open_2`<'$time' AND `s`.`close_2`>'$time')";
            $condition_where .= " OR (`s`.`open_3`<'$time' AND `s`.`close_3`>'$time')))";
		}elseif($show_method==2){
			$condition_field .= ",(CASE
	        WHEN (`s`.`open_1`='00:00:00' and `s`.`open_2`='00:00:00' and `s`.`open_3`='00:00:00' and `s`.`close_1`='00:00:00' and `s`.`close_2`='00:00:00' and `s`.`close_3`='00:00:00') then 2
	        WHEN (`s`.`open_1` = `s`.`close_1`) then 2
	        WHEN ((`s`.`open_1`<'$time' and `s`.`close_1`>'$time') OR (`s`.`open_2`<'$time' and `s`.`close_2`>'$time') OR (`s`.`open_3`<'$time' and `s`.`close_3`>'$time')) then 2
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

        // 分类关系
		if ($catFid || $catId) {
            if($catId){
                $relation = (new MealStoreCategoryRelationService())->getCategoryRelationByCatId($catId);
            }elseif($cat_fid){
                $relation = (new MealStoreCategoryRelationService())->getCategoryRelationByCatFid($catFid);
            }
            
            // 店铺ID
            $storeIdArr = array_unique(array_column($relation,'store_id'));
		
			if (empty($storeIdArr)) {
				return array('store_list' => null, 'store_count' => 0, 'totalPage' => 0);
			}
		}

        // 搜索店铺
		if (isset($params['store_id'])&&$params['store_id']) {
            // 取交集
            $storeIdArr && $params['store_id'] = array_intersect($storeIdArr, $params['store_id']);
            if ($params['store_id']) {
                $condition_where .= ' AND s.store_id IN (' . implode(',', $params['store_id']) . ')';
            } else {
                return array('store_list' => null, 'totalPage' => 0, 'store_count' => 0);
            }
        }else{
            if (isset($storeIdArr)&&$storeIdArr) {
                $condition_where .= ' AND s.store_id IN (' . implode(',', $storeIdArr) . ')';
            }
        }

        // 关键字搜索
        if (isset($keyword)&&$keyword) {
			$condition_where .= " AND `s`.`name` LIKE '%{$keyword}%'";
		}


		// 筛选
		if($recommend){
			$recommendArr = explode(',', $recommend);
			foreach($recommendArr as $_recommend){
				switch($recommendArr){
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
						$condition_where .= " AND `s`.`permoney` <= 50";
					break;
					case '50_100'://50_100
						$condition_where .= " AND `s`.`permoney` >= 50 AND `s`.`permoney` <= 100";
					break;
					case '100_300'://100_300
						$condition_where .= " AND `s`.`permoney` >= 100 AND `s`.`permoney` <= 300";
					break;
					case '300_all'://300_all
						$condition_where .= " AND `s`.`permoney` >= 300";
					break;
					
				}

			}
		}

        // 经纬度
		if ($lat>0 && $long>0) {
			if (in_array($sort, ['juli','defaults','score']) || $isMap) {
                $condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
			}
		} else {
			$sort = $sort == 'juli' ? '' : $sort;
        }
        
        $join_field = '';
        
        // 排序
        switch ($sort) {
            case 'score'://店铺评分
                $order['ms.score_mean'] = 'DESC';
                if ($lat>0 && $long>0) {
                    $order['juli'] = 'ASC';
                }
                $order['ms.create_time'] = 'DESC';
                break;
            case 'permoney_desc'://人均由高到低
                $order['permoney'] = 'DESC';
                $order['ms.create_time'] = 'DESC';
                break;
            case 'permoney_asc'://人均由低到高
                $order['permoney'] = 'ASC';
                $order['ms.create_time'] = 'DESC';
                break;
            case 'juli'://距离
                $order['juli'] = 'ASC';
                $order['ms.create_time'] = 'DESC';
                break;
            default://智能排序
                $order['ms.sort'] = 'DESC';
                if ($lat>0 && $long>0) {
                    $order['juli'] = 'ASC';
                }
                $order['ms.create_time'] = 'DESC';
		}
		
        // 当前页数
		$page = request()->param('page', '1', 'intval');

		// 获得店铺列表
		$storeList = (new MerchantStoreService())->getStoreListByModule('merchant_store_foodshop',$condition_where, $order, $condition_field, $page);

		$result = [];
		// 获得所需字段
		foreach ($storeList as $tmp) {
			$circleIdArr[] = $tmp['circle_id'];
		    $_store = $this->formatStore($tmp);
			$result[$tmp['store_id']] = $_store;
		}

		// 商圈id数组
		$circleIdArr = [];

		// 分类数组
		$categorybyStore = [];

		// 团购列表

		// 店铺ID
		$storeIdArr = array_keys($result);
		if ($storeIdArr) {
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
				if(isset($categorybyStore[$_category['store_id']])&&count($categorybyStore[$_category['store_id']])>=2){
					continue;
				}
				$categorybyStore[$_category['store_id']][] = $categorybyCatId[$_category['cat_id']];
			}

			// 团购列表
			$groups = [];
			foreach ($groups as $row) {
				if (isset($result[$row['store_id']])) {
					if ($result[$row['store_id']]['group_count'] < 2) {
						$result[$row['store_id']]['group_list'][] = $row;
					}
					$result[$row['store_id']]['group_count']++;
				}
			}
		}

		$_store_list = array();
		foreach ($result as $row) {
			// 店铺分类
			$row['category_arr'] = isset($categorybyStore[$row['store_id']]) ? $categorybyStore[$row['store_id']] : [];
			// 店铺商圈
			$row['area_name'] = isset($areabyAreaId[$row['circle_id']]) ? $areabyAreaId[$row['circle_id']] : [];
			$_store_list[] = $row;
		}


		$return['store_list'] = $_store_list;
		return $return;
	}
	
	public function formatStore($store, $long = '', $lat = ''){
		$returnArr = [];

		//店铺名称
		$returnArr['name'] = $store['name'];
		//评分
		$returnArr['score_mean'] = $store['score_mean']==0 ? 5.0 : $store['score_mean'];
		//右侧标识
		$returnArr['bus_label'] = [];
		// 排号
		if($store['queue_is_open']==1){
			$returnArr['bus_label'][] = [
				'type' => 'queue',
				'name' => '排',
			];
		}
		// 外卖
		if($store['shop_on_sale']==1 && $store['have_shop']==1){
			$returnArr['bus_label'][] = [
				'type' => 'shop',
				'name' => '外',
			];
		}
		// 预订
		if($store['is_book']==1){
			$returnArr['bus_label'][] = [
				'type' => 'book',
				'name' => '订',
			];
		}
		
		// 快速买单
		$store['discount_txt'] = $store['discount_txt'] ? unserialize($store['discount_txt']) : array();
		if(cfg('pay_in_store') && $store['discount_txt']){
			$returnArr['bus_label'][] = [
				'type' => 'check',
				'name' => '买',
			];
		}

		// 店铺图片
		$images = (new \app\merchant\model\service\storeImageService())->getAllImageByPath($store['pic_info']);
		$returnArr['image'] = $images ? thumb(array_shift($images),112) : '';
		
		//人均消费金额
		$returnArr['permoney'] = $store['permoney'];
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

		// 外卖优惠
		$returnArr['shop_discount'] = [];
		$shop_discount = (new \app\merchant\model\service\ShopDiscountService())->getDiscounts($store['mer_id'], $store['store_id']);
		if($shop_discount){
			$shop_discount = (new \app\merchant\model\service\ShopDiscountService())->formartDiscount($shop_discount, $store['store_id']);
			$shop_discount = (new \app\merchant\model\service\ShopDiscountService())->simpleParseCoupon($shop_discount['coupon_list']);
			$returnArr['shop_discount'] = $shop_discount;
		}

		// 快速买单优惠
		$store_pay_discount = (new \app\merchant\model\service\MerchantStoreService())->getStoreQuickDiscount($store['discount_txt']);
		$returnArr['store_pay_discount'] = $store_pay_discount ? [$store_pay_discount] : [];
		
	

		// $tmps['state'] = 0;//根据营业时间判断
		// $time = time();
		// $open_1 = strtotime(date('Y-m-d') . $tmp['open_1']);
		// $close_1 = strtotime(date('Y-m-d') . $tmp['close_1']);
		// $open_2 = strtotime(date('Y-m-d') . $tmp['open_2']);
		// $close_2 = strtotime(date('Y-m-d') . $tmp['close_2']);
		// $open_3 = strtotime(date('Y-m-d') . $tmp['open_3']);
		// $close_3 = strtotime(date('Y-m-d') . $tmp['close_3']);
		// if ($tmp['open_1'] == '00:00:00' && $tmp['close_1'] == '00:00:00') {
		// 	$tmps['state'] = 1;
		// } elseif (($open_1 < $time && $close_1 > $time) || ($open_2 < $time && $close_2 > $time) || ($open_3 < $time && $close_3 > $time)) {
		// 	$tmps['state'] = 1;
		// }
		$returnArr['group_count'] = 0;
		// $tmps['store_pay'] = $site_url.str_replace('appapi.php','wap.php',U('My/pay', array('store_id' => $tmp['store_id'])));
		$returnArr['url'] = cfg('site_url').'/packapp/plat/pages/store/homePage?store_id='.$store['store_id'];
		$returnArr['store_id'] = $store['store_id'];
		$returnArr['circle_id'] = $store['circle_id'];
		return $returnArr;
		
	}
	
    /**
     * 获取店铺筛选信息
     * @param $discount array 店铺优惠
     * @return array
     */
    public function getScreenList() {
		$returnArr = [];
		//附近
		$returnArr['nearby'] = (new AreaService())->getAreaListByAreaPid();
		
		//美食分类
		$returnArr['category_list'] = (new MealStoreCategoryService())->getAllCategory();
		
		//排序
		$returnArr['sort'] = [
			[
				"sort_name" => "智能排序",
				"sort_value" => "defaults",
			],
			[
				"sort_name" => "评价排序",
				"sort_value" => "score",
			],
			[
				"sort_name" => "离我最近排序",
				"sort_value" => "juli",
			],
			[
				"sort_name" => "人均由高到低排序",
				"sort_value" => "permoney_desc",
			],
			[
				"sort_name" => "人均由低到高排序",
				"sort_value" => "permoney_asc",
			],

		];

		// 筛选
		$returnArr['recommend']  = [
			[
				'name' => '服务',
				'recommend_list' => [
					[
						'value' => 'quick_pay',
						'name' => '买单',
					],
					[
						'value' => 'order_table',
						'name' => '在线订座',
					],
					[
						'value' => 'order_queue',
						'name' => '在线排队',
					],
					[
						'value' => 'open_shop',
						'name' => '在线外卖',
					],
				],
			],
			[
				'name' => '价格',
				'recommend_list' => [
					[
						'value' => '0_50',
						'name' => '50以下',
					],
					[
						'value' => '50_100',
						'name' => '50-100',
					],
					[
						'value' => '100_300',
						'name' => '100-300',
					],
					[
						'value' => '300_all',
						'name' => '300以上',
					],
				],
			],
		];

        return $returnArr;
    }

    
}