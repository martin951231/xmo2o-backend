<?php
/**
 * 酒店分类
 * Author: 衡婷妹
 * Date Time: 2021/04/28
 */

namespace app\hotel\model\service;

use app\group\model\service\GroupService;
use app\hotel\model\db\TradeHotelCategory;
use app\hotel\model\db\TradeHotelStock;

class TradeHotelCategoryService
{
    public $tradeHotelCategoryModel = null;

    public function __construct()
    {
        $this->tradeHotelCategoryModel = new TradeHotelCategory();
    }

    function getAllList($merId,$has_stock=false,$depTime='',$endTime=''){
	
        $where = [
            'mer_id'=>$merId,
            'is_remove'=>'0'
        ];
        $tmp_category_list = $this->getSome($where,true,'cat_fid ASC,cat_sort DESC,cat_id ASC');

		$category_list = array();
		
		if($has_stock){
			$stock_list = $this->getCategoryRoomStock($merId,$depTime,$endTime);
		}

		$tradeHotelImageService = new TradeHotelImageService();
		
		foreach($tmp_category_list as $value){
			if($value['cat_fid'] == '0'){
                if ($value['cat_pic']) {
                    $value['cat_pic_list'] = $tradeHotelImageService->getAllImageByPath($value['cat_pic']);
                } else {
                    $value['cat_pic_list'] = [];
                }
				
				unset($value['cat_pic'],$value['is_remove'],$value['mer_id'],$value['code'],$value['cat_sort'],$value['enter_time'],$value['has_receipt'],$value['has_refund'],$value['refund_hour']);
				
				$category_list[$value['cat_id']] = $value;
			}else{
				if($has_stock){
					$value['stock_num'] = $stock_list[$value['cat_id']]['room'] ?? 0;
					$value['price_txt'] = $stock_list[$value['cat_id']]['price'] ?? "" ;
					$value['discount_price_txt'] = isset($stock_list[$value['cat_id']]['discount_price']) && $stock_list[$value['cat_id']]['discount_price'] ? $stock_list[$value['cat_id']]['discount_price']:'0';
                    $value['discount_price_txt'] = get_format_number($value['discount_price_txt']);

				}
				$value['refund_txt'] = $this->getRefundTxt($value['has_refund'],$value['refund_hour'],$value['enter_time']);
				unset($value['is_remove'],$value['cat_pic'],$value['window_info'],$value['floor_info'],$value['room_size'],$value['bed_info'],$value['network_info'],$value['breakfast_info'],$value['code'],$value['cat_sort'],$value['mer_id']);
				$category_list[$value['cat_fid']]['son_list'][] = $value;
			}
		}

		
		if($has_stock){
			foreach($category_list as $key=>$value){
				if(isset($value['son_list']) && is_array($value['son_list'])){
					foreach($value['son_list'] as $k=>$v){
						if($v['price_txt'] && (empty($value['min_price']) || ($v['discount_price_txt']?:$v['price_txt']) < $value['min_price'])){
							$category_list[$key]['min_price'] = $value['min_price'] = $v['discount_price_txt']?:$v['price_txt'];
						}
						// if($v['price_txt'] != $value['min_price']){
						// 	$category_list[$key]['many_min_price'] = true;
						// }

						if($v['stock_num'] && $v['price_txt']){
							$category_list[$key]['has_room'] = true;
							// break;
						}elseif(!$v['stock_num'] && $v['price_txt']){
							$category_list[$key]['has_room'] = false;
						}
					}
				}
			}
		}
		return $category_list;
	}

    function getTradeHotelStock($param){
        $groupId = $param['group_id'] ?? 0;
        if(!$groupId){
            return [];
        }

		$nowGroup = (new GroupService())->getOne(['group_id'=>$groupId]);
        $depDate = $param['dep_time'] ?? '';
        $endDate = $param['end_time'] ?? '';

		$hotel_list_tmp = $this->getAllList($nowGroup['mer_id'], true, $depDate, $endDate);
		$hotel_cat_id = explode(',',$nowGroup['trade_info']);
		foreach($hotel_list_tmp as $key=>$v){
			if(in_array($key,$hotel_cat_id)){
				$hotel_list[] = $v;
			}
		}

		return $hotel_list;
	}

    /**
     * 获得提交停单时的价格详情
     */
    public function getCatPrice($merId, $catId, $depTime, $endTime, $num = 1){

        // 每日价格信息
		$hotel_stock_list = (new TradeHotelStock())->getCatPrice($merId, $catId, $depTime, $endTime);
        // 当前房间信息
		$hotel_cat = $this->tradeHotelCategoryModel->getOne(array('cat_id'=>$catId));

        if($hotel_cat['book_day'] != 0 && $hotel_cat['book_day']<(strtotime($endTime)-strtotime($depTime))/86400){
            throw new \think\Exception('最多可预订'.$hotel_cat['book_day'].'天');
        }

        if($depTime && $endTime && $depTime==$endTime){
            throw new \think\Exception('不能选择同一天');
        }

		if(count($hotel_stock_list) <  (strtotime($endTime)-strtotime($depTime))/86400){
            throw new \think\Exception('部分日期没有设置价格');
		}else{
			$now_stock = 0;
			$price_count = 0;
            $discount_price_count = 0;
			$stock_list = array();
			
			//非对接第三方
			if(!cfg('baodi_hotel_url') || empty($hotel_cat['code'])){
				foreach($hotel_stock_list as &$value){
					if($value['stock'] == 0){
                        throw new \think\Exception($value['stock_day'].'已经售完');
					}

					if($value['stock'] < $num){
                        throw new \think\Exception($value['stock_day'].'剩余房间数量不足，仅剩'.$value['stock'].'间');
					}
					$price_count += $value['price'];
					$discount_price_count += $value['discount_price'];

					if($now_stock){
						if($value['stock'] < $now_stock){
							$now_stock = $value['stock'];
						}
					}else{
						$now_stock = $value['stock'];
					}
					$stock_list[] = array('day'=>$value['stock_day'],'stock'=>$value['stock'],'price'=>get_format_number($value['price']),'discount_price'=>get_format_number($value['discount_price']));
				}
			}else{
				// import('@.ORG.third_custom.baodi.baodiHotel');
				// $baodiHotel = new baodiHotel();
				
				// //第二天离店，第二天不用收费
				// $tmp_end_time = date('Ymd', strtotime($endTime) - 86400);
				// $result = $baodiHotel->getDayData($depTime, $tmp_end_time, $hotel_cat['code'], $hotel_cat['hotelCode'], $hotel_cat['rateCode'], true);
				// foreach($result as $key=>$value){
				// 	$stock_list[] = [
				// 		'day' => strval($key),
				// 		'stock' => $value['stock'],
				// 		'price' => floatval($value['price']),
				// 		'discount_price' => floatval($value['price']),
				// 	];
					
				// 	$price_count += $value['price'];
				// 	$now_stock = $value['stock'];
					
				// }
			}

			return ['err_code'=>false, 'cat_id'=>$hotel_cat['cat_id'],'cat_code'=>$hotel_cat['code'], 'hotelCode'=>$hotel_cat['hotelCode'], 'rateCode'=>$hotel_cat['rateCode'], 'enter_time'=>$hotel_cat['enter_time'], 'stock'=>$now_stock, 'discount_room'=>$hotel_cat['discount_room'], 'price'=>get_format_number($price_count),'cat_name'=>$hotel_cat['cat_name'],'discount_price'=>get_format_number($discount_price_count), 'stock_list'=>$stock_list];
		}
	}

    function getRefundTxt($has_refund,$refund_hour,$enter_time){
		if($has_refund == 0){
			$has_refund_txt = '任意退';
		}else if($has_refund == 1){
			$has_refund_txt = '不可取消';
		}else{
			$has_refund_txt = '入住时间('.str_replace(':00','点',$enter_time).')前'.$refund_hour.'小时内能退';
			
		}
		return $has_refund_txt;
	}

    function getCategoryRoomStock($merId,$depTime,$endTime){
        $where[] = ['mer_id', '=', $merId];
        if($depTime&&$endTime){
            $where[] = ['stock_day', '>=', $depTime];
            $where[] = ['stock_day', '<', $endTime];
        }
		$hotel_stock_list = (new TradeHotelStockService())->getSome($where);
		

		$tmp_arr = array();
		foreach($hotel_stock_list as $key=>$value){
			$tmp_arr[$value['cat_id']][] = $key;
		}
		foreach($tmp_arr as $value){
			// if($)
			if(count($value) < (strtotime($endTime)-strtotime($depTime))/86400){
				foreach($value as $v){
					unset($hotel_stock_list[$v]);
				}
			}
		}
		$stock_list = array();
		$no_stock_day = array();
		foreach($hotel_stock_list as $value){
			if(!in_array($value['cat_id'],$no_stock_day)){
				if(empty($value['stock'])){
					$stock_list[$value['cat_id']]['room'] = 0;
					$stock_list[$value['cat_id']]['price'] = floatval($value['price']);
					$stock_list[$value['cat_id']]['discount_price'] = floatval($value['discount_price']);
					$no_stock_day[] = $value['cat_id'];
					//break;
				}else{
					if(isset($stock_list[$value['cat_id']])){
						if($stock_list[$value['cat_id']]['room'] > $value['stock']){
							$stock_list[$value['cat_id']]['room'] = $value['stock'];
						}
						if($stock_list[$value['cat_id']]['price'] != $value['price']){
							if($stock_list[$value['cat_id']]['price'] > $value['price']){
								$stock_list[$value['cat_id']]['price'] = floatval($value['price']);
								$stock_list[$value['cat_id']]['discount_price'] = floatval($value['discount_price']);
							}
						}
					}else{
						$stock_list[$value['cat_id']]['room'] = $value['stock'];
						$stock_list[$value['cat_id']]['price'] = floatval($value['price']);
						$stock_list[$value['cat_id']]['discount_price'] = floatval($value['discount_price']);
					}
				}
			}
		}

		return $stock_list;
	}

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data)
    {
        $id = $this->tradeHotelCategoryModel->insertGetId($data);
        if (!$id) {
            return false;
        }

        return $id;
    }

    /**
     *批量插入数据
     * @param $data array
     * @return array
     */
    public function addAll($data)
    {
        if (empty($data)) {
            return false;
        }

        try {
            // insertAll方法添加数据成功返回添加成功的条数
            $result = $this->tradeHotelCategoryModel->insertAll($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     * 更新数据
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data)
    {
        if (empty($data) || empty($where)) {
            return false;
        }

        $result = $this->tradeHotelCategoryModel->where($where)->update($data);
        return $result;
    }

    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where, $order = [])
    {
        if (empty($where)) {
            return false;
        }

        $result = $this->tradeHotelCategoryModel->getOne($where, $order);
        if (empty($result)) {
            return [];
        }

        return $result->toArray();
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where, $field = true, $order = true, $page = 0, $limit = 0)
    {
        $start = ($page-1)*$limit;
        $result = $this->tradeHotelCategoryModel->getSome($where, $field, $order, $start, $limit);
        if(empty($result)){
            return [];
        }

        return $result->toArray();
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getCount($where){
        $count = $this->tradeHotelCategoryModel->getCount($where);
        if(!$count) {
            return 0;
        }
        return $count;
    }
}