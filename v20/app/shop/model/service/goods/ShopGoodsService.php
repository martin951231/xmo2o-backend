<?php
/**
 * 外卖商品
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/19 09:55
 */

namespace app\shop\model\service\goods;

use app\common\model\db\MiaomaiGoods;
use app\common\model\db\MiaomaiOrderSync;
use app\foodshop\model\service\goods\FoodshopGoodsLibraryService;
use app\foodshop\model\service\order\DiningOrderTempService;
use app\shop\model\db\ShopGoods;
use app\shop\model\db\ShopGoods as ShopGoodsModel;
use app\group\model\db\GroupFoodshopPackageData as GroupFoodshopPackageDataModel;
use app\shop\model\service\goods\ShopGoodsSortService as ShopGoodsSortService;
use app\shop\model\service\goods\GoodsImageService as GoodsImageService;
use app\shop\model\service\goods\ShopGoodsPropertiesService as ShopGoodsPropertiesService;
use app\shop\model\service\goods\ShopGoodsSpecService as ShopGoodsSpecService;
use app\shop\model\service\goods\ShopGoodsSpecValueService as ShopGoodsSpecValueService;
use app\shop\model\service\goods\ShopSubsidiaryPieceService as ShopSubsidiaryPieceService;
use app\shop\model\service\goods\TimeLimitedDiscountGoodsService as TimeLimitedDiscountGoodsService;
use app\shop\model\service\goods\TimeLimitedDiscountGoodsSpecService as TimeLimitedDiscountGoodsSpecService;
use app\shop\model\service\store\MerchantStoreShopService as MerchantStoreShopService;
use app\foodshop\model\service\package\FoodshopGoodsPackageService;

class ShopGoodsService
{
    public $shopGoodsModel = null;
    public $sendTimeType = null;
    public $spicyArray = [];

    public function __construct()
    {
        $this->shopGoodsModel = new ShopGoodsModel();

        $this->sendTimeType = [
            '分钟',
            '小时',
            '天',
            '周',
            '月',
        ];

        // 辣度
        $this->spicyArray = [
            '1' => '不辣',
            '2' => '微辣',
            '3' => '中辣',
            '4' => '重辣',
        ];
    }

    /**
     * 获得商品列表
     * @param $where
     * @return array
     */
    public function getGoodsList($where = [])
    {
        $limit = isset($where['limit']) ? $where['limit'] : 0;//每页显示数量
        $page = request()->param('page', '1', 'intval');//页码

        // 构造查询条件
        $condition = [];

        // 排序
        $order = [
            'sort' => 'DESC',
            'goods_id' => 'ASC',
        ];

        // 搜索商品状态
        if (isset($where['status']) && $where['status']) {
            $condition[] = ['status', '=', $where['status']];
        }

        // 搜索商品名称
        if (isset($where['name']) && $where['name']) {
            $condition[] = ['name', 'like', '%' . $where['name'] . '%'];
        }

        // 根据商品id查询
        if (isset($where['goods_ids']) && $where['goods_ids']) {
            $condition[] = ['goods_id', 'in', $where['goods_ids']];
        }

        // 店铺id
        if (isset($where['store_id']) && $where['store_id']) {
            $condition[] = ['store_id', '=', $where['store_id']];
        }

        // 分类id
        if (isset($where['sort_id']) && $where['sort_id']) {
            $condition[] = ['sort_id', '=', $where['sort_id']];
        }

        //统计条件（不带状态条件）
        $countWhereBase = $condition;

        // 状态
        $tabs = [];

        if (isset($where['order_status']) && $where['order_status']) {
            unset($countWhere);

            switch ($where['order_status']) {
                case '0'://全部商品
                    break;
                case '1'://售卖中
                    $condition[] = ['status', '=', 1];
                    break;
                case '2'://已下架
                    $condition[] = ['status', '=', 0];
                    break;
                case '3'://已售完
                    $condition[] = ['stock_num', '=', 0];
                    break;
            }
            $condition[] = ['sort_id', '=', $where['sort_id']];
        }

        // 商品列表
        $goodsList = $this->getGoodsListByCondition($condition, $order, $limit, $page);
        $total = $this->shopGoodsModel->getCount($condition);

        foreach ($goodsList as &$goods) {
            $goods = $this->formatGoods($goods);
        }
        $returnArr['list'] = $goodsList;
        $returnArr['total'] = $total;
        return $returnArr;
    }

    /**
     * 获得商品库公共组件商品列表
     * @return array
     */
    public function getGoodsLibraryList($param)
    {
        if (empty($param['store_id'])) {
            throw new \think\Exception(L_("缺少参数"), 1001);
        }

        $param['limit'] = 0;//不分页
        $result = $this->getGoodsList($param);

        //来源 关联表名
        if (!in_array($param['source'], ['foodshop_goods_library','mall_goods'])) {
            throw new \think\Exception(L_("参数错误"), 1003);
        }

        // 查询已经添加过的商品
        $condition = [];
        // 搜索商品名称
        if (isset($where['name']) && $where['name']) {
            $condition[] = ['s.name', 'like', '%' . $where['name'] . '%'];
            $limit = 10;
        }
        $condition[] = ['t.store_id', '=', $param['store_id']];
        if($param['source'] == 'mall_goods'){
            $condition[] = ['t.is_del', '=', 0];
        }
        $field = $param['source'] == 'mall_goods' ? 't.common_goods_id' : 't.goods_id';
        $goodsList = $this->getGoodsListByModule($param['source'], $condition, [], $param['limit'], 1, $field);

        if($param['source'] == 'mall_goods'){
            $goodsIds = array_column($goodsList, 'common_goods_id');
        }else{
            $goodsIds = array_column($goodsList, 'goods_id');
        }
        foreach ($result['list'] as &$_goods) {
            // 是否已经添加过商品
            $_goods['selected'] = 0;
            if (in_array($_goods['goods_id'], $goodsIds)) {
                $_goods['selected'] = 1;
            }
        }
        return $result;
    }

    /**
     * 获得商品库公共组件商品列表 -- 商城
     * @return array
     */
    public function getMallGoodsList($param)
    {
        if (empty($param['store_id']) || empty($param['sort_id'])) {
            throw new \think\Exception(L_("缺少参数"), 1001);
        }

        $param['limit'] = 0;//不分页
        $result = $this->getGoodsList($param);

        //来源 关联表名
        if (!in_array($param['source'], ['mall_goods'])) {
            throw new \think\Exception(L_("参数错误"), 1003);
        }

        // 查询已经添加过的商品
        $condition = [];
        // 搜索商品名称
        if (isset($where['name']) && $where['name']) {
            $condition[] = ['s.name', 'like', '%' . $where['name'] . '%'];
            $limit = 10;
        }
        $condition[] = ['t.store_id', '=', $param['store_id']];
        $goodsList = $this->getGoodsListByModule($param['source'], $condition, [], $param['limit']);
        $goodsIds = array_column($goodsList, 'goods_id');
        foreach ($result['list'] as &$_goods) {
            // 是否已经添加过商品
            $_goods['selected'] = 0;
            if (in_array($_goods['goods_id'], $goodsIds)) {
                $_goods['selected'] = 1;
            }
        }
        return $result;
    }

    /**
     * 获得商品详情
     * @return array
     */
    public function getGoodsDetail($param)
    {
        if (empty($param['goods_id'])) {
            throw new \think\Exception(L_("商品id不存在"), 1001);
        }
        $goods = $this->getGoodsByGoodsId($param['goods_id']);
        $detail = $this->formatGoods($goods, 1);
        return $detail;
    }

    /**
     * 组装返回数据结构
     * @param $tableName string 其它商品表名
     * @param $where array 条件
     * @return array
     */
    public function formatGoods($goods = [], $isAll = 0)
    {
        $returnArr = [];
        if ($isAll) {
            //图文详情处理
            if(isset($goods['des']) && $goods['des']){
                $goods['des'] = str_replace('<img src="/upload/', '<img src="' . file_domain() . '/upload/', $goods['des']);
            }
            $returnArr = $goods;
            // 附属菜
            $goods['subsidiary_piece'] = (new ShopSubsidiaryPieceService())->getWapSubsidiaryPieceByGoodsId($goods['goods_id']);
            foreach ($goods['subsidiary_piece'] as $key => $_subsidiary_piece) {
                foreach ($_subsidiary_piece['goods'] as $k => $good_value) {
                    $_subsidiary_piece['goods'][$k] = (new FoodshopGoodsLibraryService())->getFormartSubsidiaryGoods($good_value);
                }
                $goods['subsidiary_piece'][$key]['goods'] = $_subsidiary_piece['goods'];
            }
            $returnArr['subsidiary_piece'] = $goodsDetail['subsidiary_piece'] ?? [];
            // 获得商品规格属性
            $return = $this->formatSpecValue($goods['spec_value'], $goods['goods_id'], $goods['is_properties'], $goods['min_num'], []);
//            var_dump($return);
            // 处理商品规格属性
            $returnArr['json'] = isset($return['json']) ? json_encode($return['json']) : '';
            $returnArr['properties_list'] = isset($return['properties_list']) ? array_values($return['properties_list']) : '';
            $returnArr['properties_status_list'] = isset($return['properties_status_list']) ? $return['properties_status_list'] : '';
            $returnArr['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
            $returnArr['list'] = isset($return['list']) ? $return['list'] : '';
            if ($returnArr['list']) {
                foreach ($returnArr['list'] as $key => &$_list) {
                    $_list['index'] = $key;
                    $_list['index_name'] = '';
                    foreach ($_list['spec'] as &$_spec) {
                        $_list['spec_val_sid_' . $_spec['spec_val_sid']] = $_spec['spec_val_name'];
                        $_list['index_name'] .= $_spec['spec_val_name'] . ' ';
                    }
                }
                $returnArr['list'] = array_values($returnArr['list']);
            }
        }

        $goodsImageService = new GoodsImageService();
        // 图片
        if(!empty($goods['image'])){
            $img_arr=array();
            $goods_image=explode(";",$goods['image']);
            foreach ($goods_image as $key=>$val){
                $img_arr[]=replace_file_domain($val);
            }
            $returnArr['image']=implode(";",$img_arr);
        }
        $tmpPicArr = $goodsImageService->getAllImageByPath($goods['image'], 's');
        $returnArr['image_url'] = thumb_img($tmpPicArr[0], 180, 180, 'fill');
        if (empty($returnArr['image_url'])) {

            if (cfg('is_open_goods_default_image') && cfg('goods_default_image')) {
                $returnArr['image_url'] = cfg('goods_default_image');
            } else {
                $returnArr['image_url'] = cfg('site_url') . '/tpl/Merchant/default/static/images/default_img.png';
            }
        }
        // 商品id
        $returnArr['goods_id'] = $goods['goods_id'];
        // 商品名
        $returnArr['name'] = $goods['name'];
        // 商品价格
        $returnArr['price'] = $goods['price'];

        //商品是否有规格
        $returnArr['has_spec'] = $goods['spec_value'] ? 1 : 0;

        //视频前缀可能没有斜线
        if($goods['video_url'] && substr($goods['video_url'], 0, 6) == 'upload'){
            $returnArr['video_url'] = '/' . $goods['video_url'];
        }

        $returnArr['unit'] = $goods['unit'] ??'';

        return $returnArr;
    }

    /**
     * 根据条件获取其他模块商品列表
     * @param $tableName string 其它商品表名
     * @param $where array 条件
     * @return array
     */
    public function getGoodsListByModule($tableName, $where = [], $order = [], $limit = '0', $page = '1', $field = '*')
    {
        ini_set('memory_limit', '512M');
        if (!$tableName) {
            return [];
        }
        $goodsList = $this->shopGoodsModel->getGoodsListByModule($tableName, $where, $order, $limit, intval($page), $field);
//		var_dump($this->shopGoodsModel->getLastSql());die;
        if (!$goodsList) {
            return [];
        }
        return is_array($goodsList) ? $goodsList : $goodsList->toArray();
    }

    /**
     * 根据条件获取其他模块商品总数
     * @param $tableName string 其它商品表名
     * @param $where array 条件
     * @return array
     */
    public function getGoodsCountByModule($tableName, $where = [])
    {
        if (!$tableName) {
            return [];
        }
        $count = $this->shopGoodsModel->getGoodsCountByModule($tableName, $where);
        if (!$count) {
            return 0;
        }
        return $count;
    }

    /**
     * 根据条件获取其他模块商品详情
     * @param $tableName string 其它商品表名
     * @param $goodsId intval 商品id
     * @return array
     */
    public function getGoodsDetailByModule($tableName, $goodsId)
    {
        if (!$tableName || !$goodsId) {
            return [];
        }
        $goodsList = $this->shopGoodsModel->getGoodsDetailByModule($tableName, $goodsId);
        if (!$goodsList) {
            return [];
        }
        return $goodsList->toArray();
    }


    /**
     * 根据条件获取商品列表
     * @return array
     */
    public function getGoodsListByCondition($where, $order = [], $limit = '0', $page = '1')
    {
        $sortList = $this->shopGoodsModel->getGoodsListByCondition($where, $order, $limit, $page);
        if (!$sortList) {
            return [];
        }
        return is_array($sortList) ? $sortList : $sortList->toArray();
    }


    /**
     * 根据条件获取商品列表
     * @return array
     */
    public function getGoodsByGoodsId($goodsId)
    {
        $goods = $this->shopGoodsModel->getGoodsByGoodsId($goodsId);
        if (!$goods) {
            return [];
        }
        return $goods->toArray();
    }

    /**
     * 获取前端所需单个商品详情
     * @param $nowGoods
     * @param $stockType
     * @param $groupId
     * @return array
     */
    public function formatGoodsDetail($nowGoods, $stockType, $groupId = 0)
    {
        $spicyArray = (new ShopGoodsService())->spicyArray;
        $returnArr = [];

        //如果设置了最小起购，限购就无效
        if ($nowGoods['min_num'] > 1) {
            $nowGoods['max_num'] = 0;
        } else {
            $nowGoods['max_num'] = $nowGoods['max_num'];
        }

        //拼团商品
        $group_info = [];
        if ($groupId > 0) {
            // TODO
            // $group_info=D('Mall_group_act')->get_group_by_id($groupId,$goods_id,$nowGoods);
            // if(!empty($group_info)){
            // 	$nowGoods['group_info']=$group_info;
            // 	$group_info['group_type']=3;
            // 	$nowGoods['group_id']=$groupId;
            // 	$nowGoods['group_limit_num']=$group_info['limit_num'];
            // 	$nowGoods['group_ftime']=$group_info['ftime'];
            // 	//获取用户当前参团购买商品次数
            // 	D('Mall_group_act')->get_user_buy_count($groupId,$_SESSION['user']['uid']);
            // }
        }

        // 商品图片
        if (!empty($nowGoods['image'])) {
            $goodsImageService = new GoodsImageService();
            $tmpPicArr = explode(';', $nowGoods['image']);
            foreach ($tmpPicArr as $key => $value) {
                $nowGoods['pic_arr'][$key]['title'] = $value;
                if ('http' === substr($value, 0, 4)) {
                    $nowGoods['pic_arr'][$key]['url'] = $value;
                } else {
                    $nowGoods['pic_arr'][$key]['url'] = $goodsImageService->getImageByPath($value, 'm');
                }
            }
            $nowGoods['image'] = $nowGoods['pic_arr'][0]['url'];
        } else if (cfg('goods_default_image')) {
            // 默认图片
            $nowGoods['image'] = cfg('goods_default_image');
        }

        // 商品名
        $nowGoods['name'] = html_entity_decode($nowGoods['name'], ENT_QUOTES);
        // 商品折扣前价格
        $nowGoods['o_price'] = get_format_number($nowGoods['price']);
        // 商品价格
        $nowGoods['old_price'] = get_format_number($nowGoods['price']);
        // 打包费
        $nowGoods['packing_charge'] = get_format_number($nowGoods['packing_charge']);

        // 限时优惠默认没有
        $nowGoods['is_seckill_price'] = false;

        // 获得限时优惠
        $seckillRecord = (new TimeLimitedDiscountGoodsService())->getInProgressRecord($nowGoods['goods_id']);

        if ($seckillRecord) {
            //存在限时优惠
            $nowGoods['is_seckill_price'] = true;
            $nowGoods['price'] = $seckillRecord['is_spec'] == 1 ? $nowGoods['o_price'] : get_format_number($seckillRecord['limit_price']);
            $seckill_stock_num = $seckillRecord['stock'];
            $nowGoods['seckill_price'] = get_format_number($seckillRecord['limit_price']);
            $nowGoods['seckill_stock'] = $seckill_stock_num;
            $nowGoods['limit_num'] = $seckillRecord['limit_num'];
            $nowGoods['max_num'] = $seckillRecord['limit_num'];
        } else {
            $nowGoods['price'] = get_format_number($nowGoods['price']);
            $seckill_stock_num = 0;
        }
        $nowGoods['seckill_price'] = get_format_number($nowGoods['seckill_price']);

        // 获得商品规格属性
        $return = $this->formatSpecValue($nowGoods['spec_value'], $nowGoods['goods_id'], $nowGoods['is_properties'], $nowGoods['min_num'], $group_info);

        // 处理商品规格属性
        $nowGoods['json'] = isset($return['json']) ? json_encode($return['json']) : '';
        $nowGoods['properties_list'] = isset($return['properties_list']) ? $return['properties_list'] : '';
        $nowGoods['properties_status_list'] = isset($return['properties_status_list']) ? $return['properties_status_list'] : '';
        $nowGoods['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
        $nowGoods['list'] = isset($return['list']) ? $return['list'] : '';

        $today = date('Ymd');
        $nowGoods['sell_day'] = $stockType ? $today : $nowGoods['sell_day'];
        $nowGoods['today_sell_spec'] = json_decode($nowGoods['today_sell_spec'], true);
        if ($nowGoods['extra_pay_price'] > 0) {
            $nowGoods['extra_pay_price_name'] = C('config.Currency_txt') . L_('宝');
        }

        if ($nowGoods['is_seckill_price']) {
            // 有限时优惠
            $nowGoods['stock_num'] = $seckill_stock_num;
        } else {
            // 无限时优惠
            if ($nowGoods['sell_day'] != $today) {
                if ($nowGoods['sell_type'] == 2) {
                    $nowGoods['stock_num'] = $nowGoods['original_stock'];
                    if ($nowGoods['original_weight'] == -1) {
                        $nowGoods['stock_num'] = -1;
                    } else {
                        $goods_weight = $nowGoods['weight_unit'] == 2 ? $nowGoods['weight'] * 1000 : $nowGoods['weight'];
                        $nowGoods['stock_num'] = floor($nowGoods['original_weight'] / $goods_weight);
                    }
                }
            } else {
                if ($nowGoods['sell_type'] == 2 && $nowGoods['stock_weight'] == -1) {
                    $nowGoods['stock_num'] = -1;
                } elseif ($nowGoods['sell_type'] == 2) {
                    $goods_weight = $nowGoods['weight_unit'] == 2 ? $nowGoods['weight'] * 1000 : $nowGoods['weight'];
                    $nowGoods['stock_num'] = floor($nowGoods['stock_weight'] / $goods_weight);
                }
            }
        }

        // 处理规格库存
        foreach ($nowGoods['list'] as $key => &$row) {
            if ($nowGoods['is_seckill_price']) {
                $row['stock_num'] = $seckill_stock_num;
            } else {
                $t_count = isset($nowGoods['today_sell_spec'][$key]) ? intval($nowGoods['today_sell_spec'][$key]) : 0;
                if ($nowGoods['sell_day'] == $today) {
                    $row['stock_num'] = $row['stock_num'] == -1 ? -1 : (intval($row['stock_num'] - $t_count) > 0 ? intval($row['stock_num'] - $t_count) : 0);
                }
            }
        }

        $template_id = intval($nowGoods['freight_template']);
        if ($template_id) {
            // TODO
            // if ($min = D('Express_template_value')->field(true)->where(array('tid' => $template_id, 'full_money' => array('gt', 0)))->find('first_freight')) {
            // 	$min = 0;
            // } else {
            // 	$min = D('Express_template_value')->where(array('tid' => $template_id))->min('first_freight');
            // 	$min = min($min, $nowGoods['freight_value']);
            // }
            // $max = D('Express_template_value')->where(array('tid' => $template_id))->max('first_freight');
            // $max = max($max, $nowGoods['freight_value']);
            // if ($min < $max) {
            // 	$nowGoods['deliver_fee'] = floatval($min) . '~' . floatval($max);
            // } else {
            // 	$nowGoods['deliver_fee'] = floatval($min);
            // }
        } else {
            $nowGoods['deliver_fee'] = floatval($nowGoods['freight_value']);
        }

        // 是否新品
        $nowtime = time();
        $nowGoods['is_new'] = (($nowtime - $nowGoods['add_time']) > 864000) ? 0 : 1;

        // 处理限购
        if (!$nowGoods['spec_list']) {
            if ($nowGoods['min_num'] > 0 && $nowGoods['stock_num'] >= 0 && $nowGoods['min_num'] > $nowGoods['stock_num']) {
                $nowGoods['stock_num'] = 0;
            }
            if ($nowGoods['min_num'] > 0 && $nowGoods['seckill_stock'] >= 0 && $nowGoods['min_num'] > $nowGoods['seckill_stock']) {
                $nowGoods['seckill_stock'] = 0;
            }
        }

        /*判断商品是否是自营商品*/
        if (cfg('open_kefu_url') && cfg('kefu_mer_id') > 0) {
            // todo
            $store_arr = M('Merchant_store')->where(array('mer_id' => cfg('kefu_mer_id')))->getField('store_id', true);
            if (is_array($store_arr) && in_array($nowGoods['store_id'], $store_arr)) {
                $nowGoods['is_self'] = 1;
            } else {
                $nowGoods['is_self'] = 0;
            }
        }

        $nowGoods['label_arr'] = $nowGoods['label_str'] ? explode(',', $nowGoods['label_str']) : [];

        //辣度
        $nowGoods['spicy'] = isset($this->spicyArray[$nowGoods['spicy']]) ? $this->spicyArray[$nowGoods['spicy']] : '';
        //口味
        $nowGoods['flavor'] = $nowGoods['flavor'] ? json_decode($nowGoods['flavor']) : [];
        //原料
        $nowGoods['material'] = $nowGoods['material'] ? json_decode($nowGoods['material']) : [];
        //特殊食材
        $nowGoods['special_food_materials'] = $nowGoods['special_food_materials'] ? json_decode($nowGoods['special_food_materials']) : [];
        //做法
        $nowGoods['cooking_methods'] = $nowGoods['cooking_methods'] ? json_decode($nowGoods['cooking_methods']) : [];
        return $nowGoods;
    }


    /**
     * 处理商品规格属性
     * @param $str string 外卖商品规格详情
     * @param $goodsId
     * @param $is_prorerties
     * @param $minNum
     * @param $group_info
     * @param $foodshop_str
     * @param $showLimit string 是否包含限时优惠
     * @return array
     */
	public function formatSpecValue($str, $goodsId, $is_prorerties = 1, $minNum = 0,$group_info=null,$foodshop_str = '',$showLimit=1)
	{
		// 图片路径类
		$goodsImageService = new GoodsImageService();
		
		$goodsPropertiesList = [];
		$goodsPropertiesStatusList = [];
		if ($is_prorerties || $str) {
			// 商品属性
			$shopGoodsPropertiesService = new ShopGoodsPropertiesService();
			$goodsPropertiesTemp = $shopGoodsPropertiesService->getPropertiesByGoodsId($goodsId);

			if($goodsPropertiesTemp&&is_array($goodsPropertiesTemp)){
				foreach ($goodsPropertiesTemp as $_goods) {
				
					$vals = explode(',', $_goods['val']);
					$value = array();
					$valueStatus = array();
					foreach ($vals as $val) {
						$valArr = explode(':', $val);
						$decodeVal = html_entity_decode($valArr[0],ENT_QUOTES);
						if (!(isset($valArr[1]) && $valArr[1] == 0)) {
							$value[] = $decodeVal;
							$valueStatus[] = array($decodeVal, 1);
						} else {
							$valueStatus[] = array($decodeVal, 0);
						}
					}
					$_goods['val'] = $value;
					$_goods['val_status'] = $valueStatus;
					$goodsPropertiesStatusList[$_goods['id']] = $_goods;
					if ($value) {
						$goodsPropertiesList[$_goods['id']] = $_goods;
					}
				}
			}
			unset($goodsPropertiesTemp);
		}

		$limitId = 0;
		$goodSpecList = [];
		$return = [];
		$json = [];

		if ($str) {
			// 规格表
			$shopGoodsSpecService = new ShopGoodsSpecService();
			// 规格对应的属性值
			$shopGoodsSpecValueService = new ShopGoodsSpecValueService();
			

			// 获取规格列表
			$goodsSpecTemp = $shopGoodsSpecService->getSpecByGoodsId($goodsId);

			$specIds = array();
			foreach ($goodsSpecTemp as $_goods) {
				$specIds[] = $_goods['id'];
				$goodSpecList[$_goods['id']] = $_goods;
			}
			unset($goodsSpecTemp);

			$specValueList = array();
			if ($specIds) {
				// 规格对应的属性值
				$specValuseTemp = $shopGoodsSpecValueService->getSpecValueBySid($specIds);
				foreach ($specValuseTemp as $v_temp) {
					$specValueList[$v_temp['id']] = $v_temp;
					$goodSpecList[$v_temp['sid']]['list'][$v_temp['id']] = $v_temp;
				}
				unset($specValuseTemp, $specIds);
			}
			

			// 外卖商品规格
			$specArray = explode('#', $str);

			// 餐饮商品规格
			$foodshop_str && $foodshop_spec_array = explode('#', $foodshop_str);

			$p_ids = array();
			$is_sort = true;
			$goodSpecListNew = [];

			if($showLimit){
                // 限时优惠
                $seckillRecord = (new TimeLimitedDiscountGoodsService())->getInProgressRecord($goodsId,0);
                    // 限时优惠规格
                $spec_list = isset($seckillRecord['spec_list']) ? $seckillRecord['spec_list'] : [];
                    // 限时优惠id
                $limitId = $seckillRecord ? $seckillRecord['id'] : 0;
            }else{
                $limitId = 0;
                $spec_list = [];
                $seckillRecord = [];
            }
			if($specArray&&is_array($specArray)){
				foreach ($specArray as $key=>$row) {
					$row_array = explode('|', $row);
					$spec_ids = explode(':', $row_array[0]);

					// 餐饮商品规格
					$foodshop_str && isset($foodshop_spec_array[$key]) && $foodshop_row_array = explode('|', $foodshop_spec_array[$key]);

					$t_index = '';
					$t_pre = '';
					$spec_data = array();
					if($spec_ids&&is_array($spec_ids)){
						foreach ($spec_ids as $id) {
							$t_index .= $t_pre . 'id_' . $id;
							$t_pre = '_';
							$spec_data[] = array(
								'spec_val_id' => $id, 
								'spec_val_name' => isset($specValueList[$id]['name']) ? html_entity_decode($specValueList[$id]['name'], ENT_QUOTES) : '',
								'spec_val_sid' => isset($specValueList[$id]['sid']) ? $specValueList[$id]['sid'] : '',
							);
							if ($is_sort && isset($specValueList[$id]['sid']) && isset($goodSpecList[$specValueList[$id]['sid']])) {
								$goodSpecListNew[] = $goodSpecList[$specValueList[$id]['sid']];
							}
						}
					}
					$is_sort = false;
					$index = implode('_', $spec_ids);
					
					$return[$index]['index'] = $t_index;
					$return[$index]['spec'] = $spec_data;
                    $return[$index]['spec_str'] = implode(',',array_column($spec_data,'spec_val_name'));


					$prices = explode(':', $row_array[1]);
					//重置限时优惠价格和库存
					if (isset($spec_list[$index])) {
						$prices[2] = $spec_list[$index]['limit_price'];
						$prices[3] = $spec_list[$index]['stock'];
						$limitId = $spec_list[$index]['id'];
					}else{
						$limitId = 0;
					}
					$return[$index]['limit_id'] = $limitId;

					// 餐饮商品规格
                    if(isset($foodshop_row_array[1])){
                        $foodshop_prices = explode(':', $foodshop_row_array[1]);
                    }

                    $return[$index]['old_price'] = floatval($prices[0]);
                    $return[$index]['price'] = isset($foodshop_prices[0]) ? floatval($foodshop_prices[0]) : floatval($prices[1]);
                    $return[$index]['seckill_price'] = floatval($prices[2]);
                    $return[$index]['seckill_discount'] = $return[$index]['price'] ? round($return[$index]['seckill_price'] / $return[$index]['price'] * 10, 1) : 10;
                    $return[$index]['stock_num'] = isset($foodshop_prices[1]) ? $foodshop_prices[1] : $prices[3];
                    $return[$index]['cost_price'] = isset($prices[4]) ? $prices[4] : 0;
                    if ($minNum > 1) {
                        $return[$index]['max_num'] = 0;
                    } else {
                        $return[$index]['max_num'] = isset($prices[5]) ? $prices[5] : 0;
                    }
                    if ($seckillRecord) {
                        $return[$index]['limit_num'] = $seckillRecord['limit_num'];
                        $return[$index]['max_num'] = $seckillRecord['limit_num'];
                    }
                    $return[$index]['weight'] = isset($prices[6]) ? $prices[6] : 0;
                    $return[$index]['account_money'] = isset($prices[7]) ? $prices[7] : 0;
                    $return[$index]['account_score'] = isset($prices[8]) ? $prices[8] : 0;

                    $return[$index]['vip_wholesale_price'] = isset($prices[7]) ? $prices[7] : 0;
                    $return[$index]['goods_weight'] = isset($prices[8]) ? $prices[8] : 0;
                    $return[$index]['goods_volume'] = isset($prices[9]) ? $prices[9] : 0;

                    $return[$index]['number'] = isset($row_array[3]) ? $row_array[3] : '';
                    $reg_title = isset($row_array[5]) ? urldecode($row_array[5]) : '';
                    $return[$index]['reg_title'] = $reg_title;
                    $return[$index]['reg_img'] = $goodsImageService->getImageByPath($reg_title);

                    if (isset($row_array[2]) && $row_array[2] && strstr($row_array[2], '=')) {
                        $p_data = array();
                        $tdata = array();
                        $properties = explode(':', $row_array[2]);
                        if ($properties && is_array($properties)) {
                            foreach ($properties as $k => $pro) {
                                $pro_array = explode('=', $pro);
                                $decodePropertiesVal = html_entity_decode(isset($goodsPropertiesStatusList[$pro_array[0]]['name']) ? $goodsPropertiesStatusList[$pro_array[0]]['name'] : '', ENT_QUOTES);
                                $p_data[] = array('id' => intval($pro_array[0]), 'num' => intval($pro_array[1]), 'name' => $decodePropertiesVal);
                                $tdata['num' . $k . '[]'] = $pro_array[1];
                            }
                        }
                        $return[$index]['properties'] = $p_data;
                        $json[$t_index] = $tdata;
                    }
                    if (empty($return[$index]['number']) && isset($row_array[2]) && $row_array[2] && !strstr($row_array[2], '=')) {
                        $return[$index]['number'] = $row_array[2];
                    }
                    $json[$t_index]['old_prices[]'] = $prices[0];
                    $json[$t_index]['prices[]'] = isset($foodshop_prices[0]) ? floatval($foodshop_prices[0]) : floatval($prices[1]);
                    $json[$t_index]['seckill_prices[]'] = $prices[2];
                    $json[$t_index]['account_moneys[]'] = isset($prices[7]) ? $prices[7] : '';
                    $json[$t_index]['account_scores[]'] = isset($prices[8]) ? $prices[8] : '';
                    $json[$t_index]['stock_nums[]'] = isset($foodshop_prices[1]) ? $foodshop_prices[1] : $prices[3];
                    $json[$t_index]['cost_prices[]'] = isset($prices[4]) ? floatval($prices[4]) : 0;
                    $json[$t_index]['max_nums[]'] = isset($prices[5]) ? floatval($prices[5]) : 0;
                    $json[$t_index]['numbers[]'] = isset($row_array[3]) ? $row_array[3] : '';
                    if (is_array($return[$index]['reg_img'])) {
                        $json[$t_index]['reg_imgs[]'] =
                            array(
                                'title' => $reg_title,
                                'url' => $return[$index]['reg_img']['image']
                            );
                    } else {
                        $json[$t_index]['reg_imgs[]'] =
                            array(
                                'title' => '',
                                'url' => $return[$index]['reg_img']
                            );
                    }
                    /*拼团活动的商品*/
                    if (isset($group_info) && $group_info) {
                        $group_type = $group_info['group_type'];
                        if ($group_type == 1 || $group_type == 2) {
                            if ($group_type == 1) {
                                $tprice = $group_info['team_price'][$key];
                                $return[$index]['cost_price'] = $group_info['join_price'][$key];
                            } else {
                                $tprice = $group_info['join_price'][$key];
                            }
                            $return[$index]['price'] = $tprice;
                            $return[$index]['seckill_price'] = 0;
                            $return[$index]['seckill_discount'] = 0;
                            $return[$index]['max_num'] = 0;

                            $json[$t_index]['prices[]'] = $tprice;
                            $json[$t_index]['seckill_prices[]'] = 0;
                            $json[$t_index]['max_nums[]'] = 0;
                        } elseif ($group_type == 3) {
                            $team_price = $group_info['team_price'][$key];
                            $join_price = $group_info['join_price'][$key];
                            $return[$index]['team_price'] = $team_price;
                            $return[$index]['join_price'] = $join_price;
                            $json[$t_index]['team_price[]'] = $team_price;
                            $json[$t_index]['join_price[]'] = $join_price;
                        }
                    }

                }
            }
        }

        $data = array();
        $data['limit_id'] = $limitId;
        $goodSpecList && $data['spec_list'] = $goodSpecListNew;
        $goodsPropertiesList && $data['properties_list'] = $goodsPropertiesList;
        $goodsPropertiesStatusList && $data['properties_status_list'] = $goodsPropertiesStatusList;
        $return && $data['list'] = $return;
        $json && $data['json'] = $json;
        return $data ?: [];
    }

    /**
     * 仅获得规格的数组
     * @param $str string 外卖商品规格详情
     * @param $goodsId
     * @param $is_prorerties
     * @param $minNum
     * @param $group_info
     * @param $foodshop_str
     * @return array
     */
    public function formatSpec($str, $goodsId, $minNum = 0, $foodshop_str = '')
    {
        $goodSpecList = [];
        $return = [];

        if ($str) {
            // 规格表
            $shopGoodsSpecService = new ShopGoodsSpecService();

            // 获取规格列表
            $goodsSpecTemp = $shopGoodsSpecService->getSpecByGoodsId($goodsId);

            $specIds = array();
            foreach ($goodsSpecTemp as $_goods) {
                $specIds[] = $_goods['id'];
                $goodSpecList[$_goods['id']] = $_goods;
            }
            unset($goodsSpecTemp);

            $specValueList = array();

            // 外卖商品规格
            $specArray = explode('#', $str);

            // 餐饮商品规格
            $foodshop_str && $foodshop_spec_array = explode('#', $foodshop_str);

            $is_sort = true;
            $goodSpecListNew = [];

            if ($specArray && is_array($specArray)) {
                foreach ($specArray as $key => $row) {
                    $row_array = explode('|', $row);
                    $spec_ids = explode(':', $row_array[0]);

                    // 餐饮商品规格
                    $foodshop_str && isset($foodshop_spec_array[$key]) && $foodshop_row_array = explode('|', $foodshop_spec_array[$key]);

                    $t_index = '';
                    $t_pre = '';
                    $spec_data = array();
                    if ($spec_ids && is_array($spec_ids)) {
                        foreach ($spec_ids as $id) {
                            $t_index .= $t_pre . 'id_' . $id;
                            $t_pre = '_';
                            $spec_data[] = array(
                                'spec_val_id' => $id,
                                'spec_val_name' => isset($specValueList[$id]['name']) ? html_entity_decode($specValueList[$id]['name'], ENT_QUOTES) : '',
                                'spec_val_sid' => isset($specValueList[$id]['sid']) ? $specValueList[$id]['sid'] : '',
                            );
                            if ($is_sort && isset($specValueList[$id]['sid']) && isset($goodSpecList[$specValueList[$id]['sid']])) {
                                $goodSpecListNew[] = $goodSpecList[$specValueList[$id]['sid']];
                            }
                        }
                    }
                    $is_sort = false;
                    $index = implode('_', $spec_ids);

                    $return[$index]['index'] = $t_index;
                    $return[$index]['spec'] = $spec_data;


                    $prices = explode(':', $row_array[1]);

                    // 餐饮商品规格
                    if (isset($foodshop_row_array[1])) {
                        $foodshop_prices = explode(':', $foodshop_row_array[1]);
                    }

                    $return[$index]['old_price'] = floatval($prices[0]);
                    $return[$index]['price'] = isset($foodshop_prices[0]) ? floatval($foodshop_prices[0]) : floatval($prices[1]);
                    $return[$index]['seckill_price'] = floatval($prices[2]);
                    $return[$index]['seckill_discount'] = $return[$index]['price'] ? round($return[$index]['seckill_price'] / $return[$index]['price'] * 10, 1) : 10;
                    $return[$index]['stock_num'] = isset($foodshop_prices[1]) ? $foodshop_prices[1] : $prices[3];
                    $return[$index]['cost_price'] = isset($prices[4]) ? $prices[4] : 0;
                    if ($minNum > 1) {
                        $return[$index]['max_num'] = 0;
                    } else {
                        $return[$index]['max_num'] = isset($prices[5]) ? $prices[5] : 0;
                    }
                    $return[$index]['weight'] = isset($prices[6]) ? $prices[6] : 0;
                    $return[$index]['account_money'] = isset($prices[7]) ? $prices[7] : 0;
                    $return[$index]['account_score'] = isset($prices[8]) ? $prices[8] : 0;

                    $return[$index]['vip_wholesale_price'] = isset($prices[7]) ? $prices[7] : 0;
                    $return[$index]['goods_weight'] = isset($prices[8]) ? $prices[8] : 0;
                    $return[$index]['goods_volume'] = isset($prices[9]) ? $prices[9] : 0;

                    $return[$index]['number'] = isset($row_array[3]) ? $row_array[3] : '';
                    $reg_title = isset($row_array[5]) ? urldecode($row_array[5]) : '';
                    $return[$index]['reg_title'] = $reg_title;

                    if (isset($row_array[2]) && $row_array[2] && strstr($row_array[2], '=')) {
                        $p_data = array();
                        $tdata = array();

                        $return[$index]['properties'] = $p_data;
                    }
                    if (empty($return[$index]['number']) && isset($row_array[2]) && $row_array[2] && !strstr($row_array[2], '=')) {
                        $return[$index]['number'] = $row_array[2];
                    }

                }
            }
        }

        $data = array();
        $return && $data['list'] = $return;
        return $data ?: [];
    }

    /**
     * 处理规格属性，获得需要的数据类型
     * @param $nowGoods
     * @return array
     */
    public function getWapSpecValue($nowGoods)
    {
        $today = date('Ymd');
        // 计算规格属性库存
        if ($nowGoods['list'] && is_array($nowGoods['list'])) {
            foreach ($nowGoods['list'] as $list_key => &$row) {
                // if ($nowGoods['is_seckill_price']) {
                // $row['stock_num'] = $seckill_stock_num;
                // } else {
                $t_count = isset($nowGoods['today_sell_spec'][$list_key]) ? intval($nowGoods['today_sell_spec'][$list_key]) : 0;
                if ($nowGoods['sell_day'] == $today) {
                    $row['stock_num'] = $row['stock_num'] == -1 ? -1 : (intval($row['stock_num'] - $t_count) > 0 ? intval($row['stock_num'] - $t_count) : 0);
                }
                // }
            }
        }


        $nowtime = time();
        if (!$nowGoods['spec_list']) {
            if (isset($nowGoods['min_num']) && $nowGoods['min_num'] > 0 && $nowGoods['stock_num'] >= 0 && $nowGoods['min_num'] > $nowGoods['stock_num']) {
                $nowGoods['stock_num'] = 0;
            }
            if (isset($nowGoods['min_num']) && $nowGoods['min_num'] > 0 && $nowGoods['seckill_stock'] >= 0 && $nowGoods['min_num'] > $nowGoods['seckill_stock']) {
                $nowGoods['seckill_stock'] = 0;
            }
        }

        if ($nowGoods['spec_list']) {
            foreach ($nowGoods['spec_list'] as &$value) {
                $value['list'] = array_values($value['list']);
                $value['id_'] = $value['id'];
                unset($value['id']);
                foreach ($value['list'] as &$v) {
                    $v['id_'] = $v['id'];
                    unset($v['id']);
                }
            }
            $nowGoods['spec_list'] = array_values($nowGoods['spec_list']);
        } else {
            $nowGoods['spec_list'] = [];
        }

        if ($nowGoods['properties_list']) {
            foreach ($nowGoods['properties_list'] as &$properties) {
                $properties['id_'] = $properties['id'];
                unset($properties['id']);
            }
            $nowGoods['properties_list'] = array_values($nowGoods['properties_list']);
        } else {
            $nowGoods['properties_list'] = [];
        }

        if ($nowGoods['list']) {
            foreach ($nowGoods['list'] as &$value1) {
                $value1['price'] = strval(floatval($value1['price']));
                if (isset($value1['properties']) && $value1['properties']) {
                    foreach ($value1['properties'] as &$v) {
                        $v['id_'] = $v['id'];
                        unset($v['id']);
                    }
                } else {
                    $value1['properties'] = [];
                }
            }
        } else {
            $nowGoods['list'] = (object)[];
        }
        return $nowGoods;
    }

    /**
     * 更新库存
     * @param $goods
     * @param $type 操作类型 0：加销量，减库存，1：加库存，减销量
     * @param $isSub 是否是自商品库同步更新
     * @return array
     */
    public function updateStock($goods, $type = 0, $isSub = 0)
    {
        static $shops;
        $today = date('Ymd');
        $nowGoods = $this->getGoodsByGoodsId($goods['goods_id']);
        if (empty($nowGoods)) {
            throw new \think\Exception("商品不存在");
        }

        if (isset($shops[$nowGoods['store_id']])) {
            $shop = $shops[$nowGoods['store_id']];
        } else {
            // 外卖店铺详情
            $shop = (new MerchantStoreShopService())->getStoreByStoreId($nowGoods['store_id']);
            $shop && $shops[$shop['store_id']] = $shop;
        }

        //$shop['stock_type']库存变更类型，0:每天更新成固定库存，1：不会自动更新库存
        $stockType = $shop['stock_type'] ?? 0;
        $nowGoods['sell_day'] = $stockType ? $today : $nowGoods['sell_day'];

        if ($type == 0) {//加销量
            $num = $goods['num'];
            $total_num = $goods['num'];
            $seckill_num = $goods['num'];
            if (isset($goods['sell_type']) && $goods['sell_type'] == 2) { //计重商品
                $weight = $goods['goods_weight'] * $goods['num'];
                if ($goods['code_weight']) $weight += $goods['code_weight'];
            }
        } else {//减销量
            $total_num = $goods['num'] * -1;
            if ($today == date('Ymd', strtotime($goods['create_time']))) {//下单是就是今天时候要实时回滚销量
                $num = $goods['num'] * -1;
                $seckill_num = $goods['num'] * -1;
            } else {//下单不是今天的情况
                if ($stockType == 0) {//每天固定库存的情况下，就无需回滚今天的销量
                    $num = 0;
                } else {
                    $num = $goods['num'] * -1;
                }
                if ($nowGoods['seckill_type'] == 1) {//秒杀库存类型，1：每天固定库存，就无需回滚今天的销量
                    $seckill_num = 0;
                } else {//0：固定库存
                    $seckill_num = $goods['num'] * -1;
                }
            }
            if (isset($goods['sell_type']) && $goods['sell_type'] == 2) { //计重商品
                if ($today == date('Ymd', strtotime($goods['create_time']))) {//下单是就是今天时候要实时回滚销量
                    $weight = ($goods['goods_weight'] * $goods['num']) * -1;
                    if ($goods['code_weight']) $weight -= $goods['code_weight'];
                } else {//下单不是今天的情况
                    if ($stockType == 0) {//每天固定库存的情况下，就无需回滚今天的销量
                        $weight = 0;
                    } else {
                        $weight = ($goods['goods_weight'] * $goods['num']) * -1;
                        if ($goods['code_weight']) $weight -= $goods['code_weight'];
                    }
                }
            }
        }

        $today_sell_count = $nowGoods['today_sell_count'];//今日销量
        $today_sell_weight = $nowGoods['today_sell_weight'];//今日销售重量
        $sell_count = $nowGoods['sell_count'];//总销量
        $sell_weight = $nowGoods['sell_weight'];//总销售重量
        $today_sell_spec = $nowGoods['today_sell_spec'] ? json_decode($nowGoods['today_sell_spec'], true) : '';//今日每种规格下的销量
        if (empty($today_sell_spec) || !is_array($today_sell_spec)) {
            $today_sell_spec = array();
        }

        $today_seckill_count = $nowGoods['today_seckill_count'];//今日秒杀的销量
        if ($today == $nowGoods['sell_day']) {
            $stock_num = $nowGoods['stock_num'];//库存
            $stock_weight = $nowGoods['stock_weight'];//库存重量
        } else {
            $stock_num = $nowGoods['original_stock'];//库存
            $stock_weight = $nowGoods['original_weight'];//库存重量
        }

        if ($goods['spec_id']) {//某种规格
            $id_index = $goods['spec_id'];
            isset($today_sell_spec[$id_index]) || $today_sell_spec[$id_index] = 0;
            if ($today == $nowGoods['sell_day']) {
                $today_sell_spec[$id_index] = $today_sell_spec[$id_index] + $num;
                $today_sell_count += $num;
            } else {
                $today_sell_spec[$id_index] = $num;
                $today_sell_count = $num;
            }
            $sell_count += $total_num;
            $today_sell_spec[$id_index] = max(0, $today_sell_spec[$id_index]);

            if (isset($goods['is_seckill']) && $goods['is_seckill'] && $isSub == 0) {
                if ($nowGoods['seckill_type'] == 1) {
                    if ($today == $nowGoods['sell_day']) {
                        $today_seckill_count += $seckill_num;
                    } else {
                        $today_seckill_count = $seckill_num;
                    }
                } else {
                    $today_seckill_count += $seckill_num;
                }
            } elseif ($today != $nowGoods['sell_day']) {
                $today_seckill_count = 0;
            }
        } else {
            if ($today == $nowGoods['sell_day']) {
                $today_sell_count += $num;
                isset($weight) && $today_sell_weight += $weight;
            } else {
                $today_sell_count = $num;
                isset($weight) && $today_sell_weight = $weight;
            }
            $sell_count += $total_num;
            isset($weight) && $sell_weight += $weight;
            $today_sell_count = max(0, $today_sell_count);
            $today_sell_weight = max(0, $today_sell_weight);

            if (isset($goods['is_seckill']) && $goods['is_seckill'] && $isSub == 0) {
                if ($nowGoods['seckill_type'] == 1) {
                    if ($today == $nowGoods['sell_day']) {
                        $today_seckill_count += $seckill_num;
                    } else {
                        $today_seckill_count = $seckill_num;
                    }
                } else {
                    $today_seckill_count += $seckill_num;
                }
            } elseif ($today != $nowGoods['sell_day']) {
                $today_seckill_count = 0;
            }
        }
        if ($stock_num >= 0) {
            if ($stock_num - $num >= 0) {
                $stock_num -= $num;
            } else {
                $stock_num = 0;
            }
        }
        $goods_stock_num_key = 'goods_stock_' . $goods['goods_id'];
        // S($goods_stock_num_key,$stock_num);//加缓存

        if ($stock_weight > 0 && isset($weight)) {
            if ($stock_weight - $weight >= 0) {
                $stock_weight -= $weight;
            } else {
                $stock_weight = 0;
            }
        }
        $sell_count = max(0, $sell_count);
        $today_sell_count = max(0, $today_sell_count);
        $today_seckill_count = max(0, $today_seckill_count);

        if (!empty($goods['specs_id']) > 0) {
            // TODO
            // $mall_periodic_purchase_specs = M('Mall_periodic_purchase_specs')->where(array('specs_id' => $goods['specs_id']))->find();
            // if ($mall_periodic_purchase_specs && $total_num>0) {
            //     M('Mall_periodic_purchase_specs')->where(array('specs_id' => $goods['specs_id']))->setInc('specs_sales', $total_num);
            // } elseif ($mall_periodic_purchase_specs && $total_num<0) {
            //     $specs_sales_num = abs($total_num);
            //     M('Mall_periodic_purchase_specs')->where(array('specs_id' => $goods['specs_id']))->setInc('specs_sales', $specs_sales_num);
            // }
        }

        // 超值换购库存销量处理
        if (isset($goods['rep_id']) && $goods['rep_id'] > 0) {

            // TODO
            // $repurchase_where = array('rep_id' => $goods['rep_id'],'goods_id' => $goods['goods_id']);
            // $shop_repurchase_goods = M('Shop_repurchase_goods')->where($repurchase_where)->find();
            // if ($shop_repurchase_goods) {
            // 	if ($shop_repurchase_goods['stock_num'] != -1) {
            // 		$repurchase_data['stock_num'] = max(0,$shop_repurchase_goods['stock_num']-$total_num);
            // 		$repurchase_data['sell_count'] = $shop_repurchase_goods['sell_count']+$total_num;
            // 	}else{
            // 		$repurchase_data['sell_count'] = $shop_repurchase_goods['sell_count']+$total_num;
            // 	}

            //     M('Shop_repurchase_goods')->where($repurchase_where)->save($repurchase_data);
            // } 
        }

        //限时优惠不更新商品库库存，更新限时优惠活动库存
        if (isset($goods['is_seckill']) && $goods['is_seckill'] && $goods['sell_type'] == 1 && $isSub == 0) {
            //$type 操作类型 0：加销量，减库存，1：加库存，减销量
            if ($goods['spec_id']) {
                //规格
                (new TimeLimitedDiscountGoodsSpecService())->updateStock($goods['num'], $goods['limit_id'], $type);
            } else {
                //无规格
                (new TimeLimitedDiscountGoodsService())->updateStock($goods['num'], $goods['limit_id'], $type);
            }
        } else {
            // 更新外卖库存
            $updateData = [
                'stock_num' => $stock_num,
                'stock_weight' => $stock_weight,
                'sell_day' => $today,
                'today_seckill_count' => $today_seckill_count,
                'sell_count' => $sell_count, 'sell_weight' => $sell_weight,
                'today_sell_count' => $today_sell_count,
                'today_sell_weight' => $today_sell_weight,
                'today_sell_spec' => $today_sell_spec ? json_encode($today_sell_spec) : ''
            ];
            $res = $this->updateByGoodsId($goods['goods_id'], $updateData);

            //有赞云商品售出和回滚库存同步
            if (isset($nowGoods['youzanyun_goods_id']) && $nowGoods['youzanyun_goods_id']) {
				// 规格id
                $nowGoods['spec_id'] = $goods['spec_id'] ?? 0;
                $nowGoods['today_sell_spec'] = $updateData['today_sell_spec'];
                // if ($type == 0) {//减库存
                //     $stockNum = $goods['num'] * -1;
                // } else {// 加库存
                //     $stockNum = $goods['num'];
                // }
                $param = [
                    'goods' => $nowGoods,
                    'shop' => $shop,
                    'stockNum' => $stock_num,
                ];
                invoke_cms_model('Shop_goods/updateYouzanyunStock', $param);
            }
            if ($res) {
                return true;
            } else {
                return false;
            }
        }
    }

    //计算附属商品数量 当主商品数量增加 附属商品数量相应增加
    public function combinationDataNum($data_list = array())
    {
        $goodsList = $data_list;
        foreach ($data_list as $key => $value) {
            if ($value['host_goods_id'] == 0 and $value['count'] > 1) {
                foreach ($goodsList as $k => $v) {
                    if ($v['host_goods_id'] == $value['productId'] and $v['host_goods_id'] > 0 and $v['uniqueness_number'] == $value['uniqueness_number']) {
                        $data_list[$k]['count'] = $value['count'] * $v['count'];
                    }
                }
            }
        }
        return $data_list;
    }

    /**
     * 组合附属商品
     * @param $dataList
     * @param $wstr
     * @param $x
     * @return array
     */
    public function combinationData($dataList = array(), $wstr = 'str', $x = 0)
    {
        // 第二种语言
        $wstr_second = $wstr . '_second';

        // 商品列表
        $goodsList = $dataList;


        // 多语言处理
        $multilingual_goods_name = [];
        $specNameList = [];
        $propNameList = [];
        if (cfg('open_multilingual')) {
            $goods_ids = array_unique(array_column($goodsList, 'goods_id'));
            // 商品名称
            $multilingual_goods_name = $this->getGoodsListByCondition($where = [['goods_id', 'in', $goods_ids]]);
            $multilingual_goods_name = array_column($multilingual_goods_name, NULL, 'goods_id');

            // 规格id
            $specIds = [];
            // 属性id
            $propIds = [];
            foreach ($goodsList as &$_goods) {
                $_goods['name'] = isset($multilingual_goods_name[$_goods['goods_id']]['name']) ? $multilingual_goods_name[$_goods['goods_id']]['name'] : $_goods
                ['name'];
                if($_goods['spec_id']){
                    $specId = explode('_',$_goods['spec_id']);
                    $specIds = array_merge($specIds,$specId);
                }
                if(isset($_goods['properties']) && $_goods['properties']){
                    $propIdArr = explode('_',$_goods['properties']);
                    $propId = [];
                    foreach ($propIdArr as $prop){
                        if($prop){
                            $propId[] = explode(':',$prop)[0];
                        }
                    }
                    $propIds = array_merge($propIds,$propId);
                }
            }
            if($specIds){
                $specList = (new ShopGoodsSpecValueService())->getSome([['id','in',implode(',',$specIds)]]);
                $specNameList = array_column($specList,'name','id');
            }
            if($propIds){
                $propList = (new ShopGoodsPropertiesService())->getSome([['id','in',implode(',',$propIds)]]);
                $propNameList = [];
                foreach ($propList as $_prop) {
                    $propNameList[$_prop['id']] = $_prop;
                }
            }
        }

        $dataList = (new DiningOrderTempService())->getSortDiscountInfo($dataList,true);
        foreach ($dataList as $key => $value) {
            if (cfg('open_multilingual')) {
                $dataList[$key]['name'] = isset($multilingual_goods_name[$value['goods_id']]['name']) ? $multilingual_goods_name[$value['goods_id']]['name'] : $value
                    ['name'];
            }
            if (isset($value['host_goods_id']) && $value['host_goods_id'] == 0) {
                $newstr = '';
                $newstr2 = '';

                // 第二种语言
                $newstr_second = '';
                $newstr_second2 = '';

                $price = 0;
                $pay_price = 0;
                $total = 0;
                $discount_total = 0;

                $old_price_all = 0;
                $discount_price_all = 0;
                $discount_price = 0;
                $subList = [];

                $dataList[$key]['discount_price'] = $value['sort_discount'] ? ($value['price'] * ($value['sort_discount'] / 10 )) : $value['price'];
                foreach ($goodsList as $k => $v) {
                    if (isset($v['host_goods_id']) && $v['host_goods_id'] == $value['goods_id'] and $v['uniqueness_number'] == $value['uniqueness_number'] && ((isset($value['order_num']) && $value['order_num'] == $v['order_num']) || !isset($value['order_num']))) {

                        $price += $v['price'] * $v['num'];//附属菜总价
                        isset($v['pay_price']) && $pay_price += $v['pay_price'] * $v['num'];//附属菜总价
                        isset($v['total']) && $total += $v['total'];//附属菜总价
                        isset($v['discount_total']) && $discount_total += $v['discount_total'];//附属菜总价

                        isset($v['old_price_all']) && $old_price_all += $v['old_price_all'];// * $v['num'];//附属菜总价
                        isset($v['discount_price_all']) && $discount_price_all += $v['discount_price_all'];// * $v['num'];//附属菜总价
                        isset($v['discount_price']) && $discount_price += $v['discount_price'] * $v['num'];// * $v['num'];//附属菜总价

                        // '米饭×3，水煮鱼(大份、大辣)×3，水煮鱼(中份、大辣)×3，三鲜汤×3';
                        $str = '';
                        $str_second = '';

                        // 附属菜规格
                        if( $specNameList || $propNameList ){
                            $_tempSpec = [];
                            if(isset($v['spec_id']) && $v['spec_id'] && $specNameList){
                                $specIds = explode('_', $v['spec_id']);
                                $specNameIds = explode('、', $v[$wstr]);
                                foreach ($specIds as $sk => $_sid) {
                                    $_tempSpec[] = isset($specNameList[$_sid]) ? $specNameList[$_sid] : ($specNameIds[$sk] ?? '');
                                }
                            }

                            if(isset($v['properties']) && $v['properties'] && $propNameList){
                                $propIds = explode('_', $v['properties']);

                                foreach ($propIds as $sk => $_pid) {
                                    $pidArr = explode(':',$_pid);
                                    if(isset($propNameList[$pidArr[0]]) && $propNameList[$pidArr[0]]){
                                        $propValueArr = explode(',',$propNameList[$_pid]['val']);
                                        $_tempSpec = explode(':',$propValueArr[$pidArr[1]])[0];
                                    }
                                }
                            }

                            $str = '(' . implode('、',$_tempSpec) . ')';
                        }else{
                            if (!empty($v[$wstr])) {
                                $str = '(' . $v[$wstr] . ')';
                            }
                        }

                        // 附属菜第二种语言的规格
                        if (!empty($v[$wstr_second])) {
                            $str_second = '(' . $v[$wstr_second] . ')';
                        }

                        if ($x) {
                            $newstr .= $v['name'] . $str . '×' . $v['num'] . '，';
                            // 第二种语言名称
                            if ($value[$wstr_second]) {
                                $newstr_second .= $v['name_second'] . $str . '×' . $v['num'] . '，';
                            }
                        } else {
                            $newstr .= $v['name'] . $str . '×' . $v['num'] / $value['num'] . '，';
                            // 第二种语言名称
                            if (isset($value[$wstr_second]) && $value[$wstr_second]) {
                                $newstr_second .= $v['name_second'] . $str . '×' . $v['num'] / $value['num'] . '，';
                            }
                        }
                        $subList[] = [
                            'price' => get_format_number($v['price'] * $v['num']),
                            'total_price' => get_format_number($v['price'] * $v['num']),
                            'name' => $v['name'],
                            'num' => get_format_number($v['num']),
                            'unit' => $v['unit'],
                            'spec' => $v[$wstr],
                            'spec_arr' => $v[$wstr] ? explode('、', $v[$wstr]) : [],
                        ];
                    }
                }

                $newstr2 = substr($newstr, 0, strlen($newstr) - 3);

                // 第二种语言
                $newstr_second && $newstr_second2 = substr($newstr_second, 0, strlen($newstr_second) - 3);

                if (empty($dataList[$key][$wstr])) {
                    $dataList[$key][$wstr] = $newstr2 ? $newstr2 : '';
                    if ($newstr_second) {
                        $dataList[$key][$wstr_second] = $newstr_second2 ? $newstr_second2 : '';
                    }
                } else {
                    if( $specNameList || $propNameList ){
                        $_tempSpec = [];
                        if(isset($dataList[$key]['spec_id']) && $dataList[$key]['spec_id'] && $specNameList){
                            $specIds = explode('_', $dataList[$key]['spec_id']);
                            $specNameIds = explode('、', $dataList[$key][$wstr]);
                            foreach ($specIds as $sk => $_sid) {
                                $_tempSpec[] = isset($specNameList[$_sid]) ? $specNameList[$_sid] : ($specNameIds[$sk] ?? '');
                            }
                        }

                        if(isset($dataList[$key]['properties']) && $dataList[$key]['properties'] && $propNameList){
                            $propIds = explode('_', $dataList[$key]['properties']);

                            foreach ($propIds as $sk => $_pid) {
                                $pidArr = explode(':',$_pid);
                                if(isset($propNameList[$pidArr[0]]) && $propNameList[$pidArr[0]]){
                                    $propValueArr = explode(',',$propNameList[$pidArr[0]]['val']);
                                    $_tempSpec[] = explode(':',$propValueArr[$pidArr[1]])[0];
                                }
                            }
                        }

                        $dataList[$key][$wstr] = implode('、',$_tempSpec);
                    }
                    $dataList[$key][$wstr] .= ' ' . $newstr2;
                    if ($newstr_second) {
                        $dataList[$key][$wstr_second] .= ' ' . $newstr_second2;
                    }
                }

                // 附属菜单独存储字段
                $dataList[$key][$wstr . '_sub'] = $newstr2 ? $newstr2 : '';
                $dataList[$key][$wstr_second . '_sub'] = $newstr_second2 ? $newstr_second2 : '';

                $dataList[$key]['sub_list'] = $subList;

                if ($value['price'] >= 0 && $value['num'] > 0) {
                    $dataList[$key]['price'] = get_format_number($dataList[$key]['price'] + $price / $value['num']);
                }

                if (isset($value['pay_price']) && $value['pay_price'] >= 0 && $value['num'] > 0) {
                    $dataList[$key]['pay_price'] = get_format_number($dataList[$key]['pay_price'] + $pay_price / $value['num']);
                }

                if (isset($value['total']) && $value['total'] >= 0 && $value['num'] > 0) {
                    $dataList[$key]['total'] = get_format_number($dataList[$key]['total'] + $total / $value['num']);
                }

                if (isset($value['discount_total']) && $value['discount_total'] >= 0 && $value['num'] > 0) {
                    $dataList[$key]['discount_total'] = get_format_number($dataList[$key]['discount_total'] + $discount_total / $value['num']);
                }

                // ajax_cart
                if (isset($value['old_price_all']) && $value['old_price_all'] >= 0) {
                    $dataList[$key]['old_price_all'] = get_format_number($dataList[$key]['old_price_all'] + $old_price_all);
                }
                if (isset($value['discount_price_all']) && $value['discount_price_all'] >= 0) {
                    $dataList[$key]['discount_price_all'] = get_format_number($dataList[$key]['discount_price_all'] + $discount_price_all);
                }

                if (isset($value['discount_price']) && $value['discount_price'] >= 0 && $value['num'] > 0) {
                    $dataList[$key]['discount_price'] = get_format_number($dataList[$key]['discount_price'] + $discount_price * $value['num']);
                }
            } else {
                unset($dataList[$key]);
            }
        }
        return array_values($dataList);
    }

    /**
     * 组合套餐商品
     * @param $dataList
     * @param $wstr
     * @param $x
     * @return array
     */
    public function combinationPackageData($dataList = array(), $wstr = 'str', $x = 0)
    {
        // 第二种语言
        $wstr_second = $wstr.'_second';

        $goodsList = [];
        // 多语言处理
        if (cfg('open_multilingual')) {
            $goods_ids = array_unique(array_column($dataList, 'goods_id'));
            $multilingual_goods_name = $this->getGoodsListByCondition($where = [['goods_id', 'in', $goods_ids]]);
            $multilingual_goods_name = array_column($multilingual_goods_name, NULL, 'goods_id');

            foreach ($dataList as &$_value)
            {
                $value['name'] = isset($multilingual_goods_name[$_value['goods_id']]['name']) ? $multilingual_goods_name[$_value['goods_id']]['name'] : $_value
                ['name'];
            }
        }
        foreach ($dataList as $value)
        {
            $goodsList[$value['uniqueness_number']] = $value;
            $goodsList[$value['uniqueness_number']]['id'] = $value['uniqueness_number'];
            $goodsList[$value['uniqueness_number']]['goods_id'] = $value['package_id'];
            $goodsList[$value['uniqueness_number']]['package_id'] = $value['package_id'];
            $goodsList[$value['uniqueness_number']]['num'] = $value['package_num'];
            //查询核销数据
            if (isset($value['temp_id']) && !empty($value['temp_id'])) {//用户端临时单不核销套餐
                $goodsList[$value['uniqueness_number']]['verific_num'] = 0;//已核销数量
                $goodsList[$value['uniqueness_number']]['isRefundPackageGoods'] = true;
            } else {
                $where = [
                    ['order_id', '=', $value['order_id']],
                    ['uniqueness_number', '=', $value['uniqueness_number']]
                ];
                $verific_num = (new GroupFoodshopPackageDataModel())->getCount($where);
                $goodsList[$value['uniqueness_number']]['verific_num'] = $verific_num;//已核销数量
                if (isset($value['refundNum'])) {
                    if ($verific_num == $value['package_num'] - $value['refundNum']) {
                        $goodsList[$value['uniqueness_number']]['isRefundPackageGoods'] = false;//套餐商品已全部核销，禁止退菜
                    } else {
                        $goodsList[$value['uniqueness_number']]['isRefundPackageGoods'] = true;
                    }
                } else {
                    if ($verific_num == $value['package_num']) {
                        $goodsList[$value['uniqueness_number']]['isRefundPackageGoods'] = false;//套餐商品已全部核销，禁止退菜
                    } else {
                        $goodsList[$value['uniqueness_number']]['isRefundPackageGoods'] = true;
                    }
                }
            }
            $goodsList[$value['uniqueness_number']]['unit'] = '份';
            $goodsList[$value['uniqueness_number']]['is_package_goods'] = true;
        }
        foreach($goodsList as $key => $goods) {
            foreach ($dataList as $_key => $value) {
                if ($goods['uniqueness_number'] == $value['uniqueness_number']) {
                    $goodsList[$key]['packages'][] = $value;
                }
            }
        }

        foreach ($goodsList as $key => $value) {
            $detail = (new FoodshopGoodsPackageService())->getOne($param = ['id' => $value['package_id']]);
            $goodsList[$key]['name'] = $detail['name'];
            $goodsList[$key]['price'] = $detail['price'];
            if (isset($value['verificNum'])) {//核销数量
                $goodsList[$key]['total_price'] = $detail['price'] * ($value['num']-$value['verificNum']);
            } else {
                $goodsList[$key]['total_price'] = $detail['price'] * $value['num'];
            }

            $goodsList[$key]['note'] = $detail['note'];
            $goodsList[$key]['product_image'] = !empty($detail['image']) ? cfg('site_url') . $detail['image'] : '';;

            $newstr = '';
            $newstr2 = '';
            $newstr3 = '';

            // 第二种语言
            $newstr_second = '';
            $newstr_second2 = '';

            $price = 0;
            $pay_price = 0;
            $total = 0;
            $discount_total = 0;

            $old_price_all = 0;
            $discount_price_all = 0;
            $discount_price = 0;
            $subList = [];
            foreach ($value['packages'] as $v) {
                // '米饭×3，水煮鱼(大份、大辣)×3，水煮鱼(中份、大辣)×3，三鲜汤×3';
                $str = '';
                $str_second = '';

                // 附属菜规格
                if (!empty($v[$wstr])) {
                    $str = '(' . $v[$wstr] . ')';
                }

                // 附属菜第二种语言的规格
                if (!empty($v[$wstr_second])) {
                    $str_second = '(' . $v[$wstr_second] . ')';
                }

                if ($x) {
                    if (!empty($v[$wstr])) {
                        $newstr .= $v['name'] . $str . '×' . $value['num'] . '，';
                        // 第二种语言名称
                        if($v[$wstr_second]){
                            $newstr_second .= $v['name_second'] . $str . '×' . $value['num'] . '，';
                        }
                    }
                    $newstr3 .= $v['name'] . $str . '×' . $value['num'] . '，';

                } else {
                    if (!empty($v[$wstr])) {
                        $newstr .= $v['name'] . $str . '×' . $v['num'] / $value['package_num'] . '，';
                        // 第二种语言名称
                        if(isset($v[$wstr_second])&&$v[$wstr_second]){
                            $newstr_second .= $v['name_second'] . $str . '×' . $v['num'] / $value['package_num'] . '，';
                        }
                    }
                    $newstr3 .= $v['name'] . $str . '×' . $v['num'] / $value['package_num'] . '，';
                }
                $subList[] = [
                    'goods_id' =>  $v['goods_id'],
                    'price' =>  get_format_number($v['price'] * ($v['num'] / $value['package_num'])),
                    'total_price' =>  get_format_number($v['price'] * ($v['num'] / $value['package_num'])),
                    'name' => $v['name'],
                    'sort_id' => $v['sort_id'],
                    'num' => get_format_number($v['num'] / $value['package_num']),
                    'unit' => $v['unit'],
                    'spec' => $v[$wstr],
                    'spec_arr' => $v[$wstr] ? explode('、',$v[$wstr]) : [],
                ];
            }
            $newstr2 = substr($newstr, 0, strlen($newstr) - 3);
            $newstr3 = substr($newstr3, 0, strlen($newstr3) - 3);

            // 第二种语言
            $newstr_second && $newstr_second2 = substr($newstr_second, 0, strlen($newstr_second) - 3);

            if(empty($goodsList[$key][$wstr])){
                $goodsList[$key][$wstr] = $newstr2 ? $newstr2 : '';
                if ($newstr_second) {
                    $goodsList[$key][$wstr_second] = $newstr_second2 ? $newstr_second2 : '';
                }
            }else{
                $goodsList[$key][$wstr] .= ' ' . $newstr2;
                if ($newstr_second) {
                    $goodsList[$key][$wstr_second] .= ' ' . $newstr_second2;
                }
            }

            // 附属菜单独存储字段
            $goodsList[$key][$wstr.'_sub'] = $newstr3 ? $newstr3 : '';
            $goodsList[$key][$wstr_second.'_sub'] = $newstr_second2 ? $newstr_second2 : '';
            unset($goodsList[$key]['packages']);
            $goodsList[$key]['sub_list'] = $subList;

            if(isset($value['pay_price'])&&$value['pay_price'] >= 0){
                $goodsList[$key]['pay_price'] = get_format_number($goodsList[$key]['pay_price'] + $pay_price / $value['num']);
            }

            if(isset($value['total'])&&$value['total'] >= 0){
                $goodsList[$key]['total'] = get_format_number($goodsList[$key]['total'] + $total / $value['num']);
            }

            if(isset($value['discount_total'])&&$value['discount_total'] >= 0){
                $goodsList[$key]['discount_total'] = get_format_number($goodsList[$key]['discount_total'] + $discount_total / $value['num']);
            }

            // ajax_cart
            if(isset($value['old_price_all'])&&$value['old_price_all'] >= 0){
                $goodsList[$key]['old_price_all'] = get_format_number( $goodsList[$key]['old_price_all'] + $old_price_all);
            }
            if(isset($value['discount_price_all'])&&$value['discount_price_all'] >= 0){
                $goodsList[$key]['discount_price_all'] = get_format_number($goodsList[$key]['discount_price_all'] + $discount_price_all);
            }

            if(isset($value['discount_price'])&&$value['discount_price'] >= 0){
//                $goodsList[$key]['discount_price'] = get_format_number($goodsList[$key]['discount_price'] + $discount_price * $value['num']);
                $goodsList[$key]['discount_price'] = $goodsList[$key]['price'];
            }
        }
        return array_values($goodsList);
    }


    /**
     * 更新数据
     * @param $igoodsIdd
     * @param $data
     * @return bool
     */
    public function updateByGoodsId($goodsId, $data)
    {
        if (!$goodsId || !$data) {
            return false;
        }

        try {
            $result = $this->shopGoodsModel->updateByGoodsId($goodsId, $data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     * @param $goods_id
     * @return mixed
     * 添加商品时从商品库获取的信息
     * @author zhumengqun
     */
    public function getGoodsLibInfo($goods_id)
    {
        $field_goods = "name as goods_name,unit,price,image,video_url,stock_num as lib_stock_num";
        $goods_where = ['gs.goods_id' => $goods_id];
        $goods = $this->getGoodsByGoodsId($goods_id);
        $field_spec = 'gs.id as spec_id,gs.name as spec_name';
        $spec_where = ['gs.goods_id' => $goods_id];
        $spec = $this->shopGoodsModel->getSpec($field_spec, $spec_where);
        $specValService = new ShopGoodsSpecValueService();
        if (!empty($spec)) {
            foreach ($spec as $key => $val) {
                $spec[$key]['spec_val'] = $specValService->getSpecValueBySid([$val['spec_id']]);
                $t1[] = array_column($spec[$key]['spec_val'], 'name');
            }
            $spec_ids = array_column($spec, 'spec_id');
            $spec['spec_val'] = $specValService->getSpecValueBySid($spec_ids);
            $t2 = $this->cartesian($t1);
            foreach ($t2 as $kk => $vv) {
                $t2_arr = explode(',', $vv);
                $spec['combine'][] = $t2_arr;
            }
        }
        if (!empty($goods['image'])) {
            $images = explode(';', $goods['image']);
            foreach ($images as $k => $v) {
                $image[] = cfg('site_url') . '/' . $v;
            }
        }
        $property = (new ShopGoodsPropertiesService())->getPropertiesByGoodsId($goods_id);
        $list['goods'] = ['goods_name' => $goods['name'],
            'unit' => $goods['unit'],
            'price' => $goods['price'],
            'image' => $image,
            'video_url' => $goods['video_url'],
            'lib_stock_num' => $goods['stock_num']];
        $list['spec'] = $spec;
        $list['property'] = $property;
        return $list;
    }

    /**
     ** 实现二维数组的笛卡尔积组合
     ** $arr 要进行笛卡尔积的二维数组
     ** $str 最终实现的笛卡尔积组合,可不写
     **/
    public function cartesian($arr, $str = array())
    {
        //去除第一个元素
        $first = array_shift($arr);
        //判断是否是第一次进行拼接
        if (count($str) > 1) {
            foreach ($str as $k => $val) {
                foreach ($first as $key => $value) {
                    //可根据具体需求进行变更
                    $str2[] = $val . ',' . $value;
                }
            }
        } else {
            foreach ($first as $key => $value) {
                //最终实现的格式 1,3,76
                //可根据具体需求进行变更
                $str2[] = $value;
            }
        }
        //递归进行拼接
        if (count($arr) > 0) {
            $str2 = $this->cartesian($arr, $str2);
        }
        //返回最终笛卡尔积
        return $str2;
    }
    
    /*
     * 获取商品积分比例
     */
    public function getGoodsScore($goodsId)
    {
        if(empty(cfg('shop_goods_score_edit'))){
            return -1;
        }
        $scorePercent = ShopGoods::where('goods_id', $goodsId)->value('score_percent');
        if(strpos($scorePercent,'%')){
            $scorePercent = bcdiv($scorePercent,100,2);
        }
        
        return $scorePercent;
    }
    
    public static function miaomaiGoodsSync($params): bool
    {
        fdump_api($params, 'miaomai/miaomaiGoodsSync', 1);
        $miaomaiGoods = MiaomaiGoods::where(['only_coding' => $params['only_coding']])->find();
        if(!$miaomaiGoods){
            return false;
        }
        $goodsInfo = json_decode($miaomaiGoods['goods_info'], true);
        $goods = ShopGoods::where(['miaomaiyun_barcode_id' => $goodsInfo['barcode_id']])->find();
        if (!$goods) {
            return false;
        }
        $goods->stock_num = $params['stock_num'];
        return $goods->save();
    }
}