<?php
// 猜你喜欢
class Plat_recommendModel extends Model{

	public function get_limit_list_page_wap( $condition = array(),$lat='',$lng='',$page=1,$pageSize = 10, $uid = 0 ,$is_shop=0)
    {
        if ($condition['id']) {
            $firstRow = 0;
        } else {
            $firstRow = ($page - 1) * $pageSize;
        }
        if ( $lat && $lng ) {
        	$order = "`store_sort` DESC , ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat} * PI() / 180- p.`lat` * PI()/180)/2),2)+COS({$lat} *PI()/180)*COS(p.`lat`*PI()/180)*POW(SIN(({$lng} *PI()/180- p.`lng`*PI()/180)/2),2)))*1000) ASC";
        }else{
        	$order = 'p.`store_sort` DESC';
        }
       	// 发现好菜
       	if ($condition['re_type'] == 'shop_goods') {
	        // 查询列表
	        $condition_goods = [];
	        $condition_goods['business_type'] = 'plat';
       		$condition_goods['city_id'] = $condition['city_id'];
       		$condition_goods['sort_id'] = $condition['sort_id'];
       		$condition_goods['lng'] = $lng;
       		$condition_goods['lat'] = $lat;
			$condition['area_id'] && $condition_goods['area_id'] = $condition['area_id'];
        	$goods_list = D('Shop_goods')->get_goods_by_guess_like($condition_goods);
        	$count = $goods_list['count'];
        	$list = $goods_list['goods_list'];
       	}else if ($condition['re_type'] == 'grass') {
               /** @var Grow_grass_articleModel $article */
               $article = D('Grow_grass_article');
               $item = $article->getArticleList($condition, $page, $pageSize, $lat, $lng);

               $count = $item['count'];
               $list = $article->formatData($item['list'], $uid);
        } else {
       		if (!$condition['re_type'] && $page == 1) {
       			// 活动
		        $condition_store = [];
		        $condition_store['business_type'] = 'plat';
	       		$condition_store['city_id'] = $condition['city_id'];
				$condition['area_id'] && $condition_store['area_id'] = $condition['area_id'];
       			$shop_list = D('Merchant_store_shop')->get_store_by_guess_like($condition_store,$lat,$lng);
       			$activity_list = $shop_list['shop_list'];
       		}

       		$condition = $this->add_pre($condition);
            //自提店铺搜索最大范围
            $length = is_null(C('config.pick__max_search_kilometer')) ? 20 : C('config.pick__max_search_kilometer');
            $maxLong = $length * 1000;
       		if($condition['p.re_type']=='shop'){
       			$condition['s.have_shop'] = 1;
       			$condition['s.shop_on_sale'] = 1;
				$condition['s.is_business_open'] = 1;
                if ($maxLong > 0) {
                    $condition['_string'] = "IF(`shop`.`deliver_type` = 2 , ROUND(6378.137 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$lng}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) < $maxLong,true)";
                }
       		}
			if (isset($condition['p.store_status'])) {
				$condition['s.status'] = intval($condition['p.store_status']);
				unset($condition['p.store_status']);
			}
       		// if(C('config.mobile_index_tpl') == 'simple'){
            //     $list = [];
            //     $count = 0;
            // } else {
                $list = $this->alias('p')
                    ->JOIN('left join ' . C('DB_PREFIX') . 'merchant_store as s on p.store_id=s.store_id')
                    ->JOIN('left join ' . C('DB_PREFIX') . 'merchant_store_shop as shop on shop.store_id=s.store_id')
                    ->JOIN('LEFT JOIN ' . C('DB_PREFIX') . 'merchant as m on p.mer_id=m.mer_id')->field('p.*,m.score_get_percent')
                    ->where($condition)->order($order)->limit($firstRow . ',' . $pageSize)->select();
                unset($condition['p.id']);
                $count = $this->alias('p')
                    ->JOIN('left join ' . C('DB_PREFIX') . 'merchant_store as s on p.store_id=s.store_id')
                    ->JOIN('left join ' . C('DB_PREFIX') . 'merchant_store_shop as shop on shop.store_id=s.store_id')
                    ->field('p.*')
                    ->where($condition)
                    ->count();
            //}
       	}

		foreach($list as $key => &$value){
			if (stripos($value['pic'] ,'&url=')) {
				$pic = explode('&', $value['pic']);
				foreach ($pic as $_param) {
					$tmp_param = explode('=', $_param);
					if ($tmp_param[0] == 'url') {
						$value['pic'] = urldecode($tmp_param[1]);
					}
				}
			}
			if($value['score_get_percent']<=0){
        		$value['score_get_percent'] = C('config.score_get_percent')&&C('config.open_score_get_percent')==1?C('config.score_get_percent'):'';
			}
			$value['pic'] = replace_file_domain($value['pic']);
			$value['pic'] = thumb_img($value['pic'],180,120,'fill');
			// 距离
			if($lat && $lng){
                if ($value['lat'] > 0 && $value['lng'] > 0) {
                    $value['range'] = getRange(getDistance($value['lat'],$value['lng'],$lat,$lng),false,true);
                } else {
                    $value['range'] = '';
                }
			}else{
				$value['range'] = '';

			}
			$value['info_arr'] = array();
			switch ($value['re_type']) {
				case 'group':
					$value['url'] = C('config.site_url').'/wap.php?g=Wap&c=Groupnew&a=detail&group_id='.$value['re_id'];
					break;
				case 'shop':
					$value['url'] = C('config.site_url').'/wap.php?g=Wap&c=Shop&a=index&shop-id='.$value['re_id'];
					$store = M(_view("Merchant_store"))->where(['store_id'=>$value['re_id']])->find();
                    $shop_store = M("Merchant_store_shop")->where(['store_id'=>$value['re_id']])->find();
                    $value['star'] = number_format($shop_store['score_mean'],1);
					$value['store_name'] = $store['name'];//数据多语言
                    if($is_shop){//目前只有外卖首页展示logo
                        $value['pic'] = replace_file_domain($store['logo']);
                    }
					$value['info_arr'] = json_decode($value['info']);
					break;
				case 'meal':
					$value['url'] = C('config.site_url'). D('House_village')->base_url . 'pages/store/homePage?store_id=' . $value['re_id'];
					break;
				case 'mall':
					$value['url'] = C('config.site_url').'/wap.php?g=Wap&c=Mall&a=detail&goods_id='.$value['re_id'];
					break;
				case 'store':
					$value['url'] = C('config.site_url').'/wap.php?g=Wap&c=Merchant&a=shop&store_id='.$value['re_id'];
					$store = M(_view("Merchant_store"))->where(['store_id'=>$value['re_id']])->find();
					$value['store_name'] = $store['name'];//数据多语言
					break;
				case 'hotel':
					$value['url'] = C('config.site_url').'/wap.php?g=Wap&c=Group&a=detail&group_type=hotel&group_id='.$value['re_id'];
					break;
				case 'pin_group':
					$value['url'] = C('config.site_url').'/wap.php?g=Wap&c=Group&a=detail&pin_num=3&group_id='.$value['re_id'];
					break;
				case 'shop_goods':
					$value['url'] = C('config.site_url').'/packapp/plat/pages/shop_new/goodsDetail/goodsDetail?store_id=388&=&=&product_id='.$value['goods_id'];
					break;
			}
			if (empty(floatval($value['star']))) {
				$value['star'] = '5.0';
			}
			$value['store_name'] = htmlspecialchars_decode($value['store_name'], ENT_QUOTES);

			$value['price'] = getFormatNumber($value['price']);
			$value['old_price'] = getFormatNumber($value['old_price']);
			$value['price_1'] = getFormatNumber($value['price_1']);
			$value['price_2'] = getFormatNumber($value['price_2']);
			$value['next_date']  = (object)array();//下一阶段营业时间
			$value['is_advance'] = false;
	
			if($value['re_type']=='shop'){//只有休息中的店铺才显示下一阶段营业时间
				$is_business_open=M('Merchant_store')->where(['store_id'=>$value['store_id']])->getField('is_business_open');
				if(empty($is_business_open)){
					$next_date = D('Merchant_store_open_time')->get_next_time($value['store_id']);
					$value['next_date'] = $next_date;
					if($value['type']<>3){
						$value['is_advance'] = true;
					}else{
						unset($list[$key]);
					}
				}	
			}
		}
        $return['count'] = ceil($count/$pageSize);
        $return['page'] = $page;
        $return['list'] = $list ? array_values($list) : array();
        $return['activity_list'] = $activity_list ? $activity_list : array();
        return $return;
    }

    private function add_pre($list,$pre='p.'){
    	$return = [];
    	foreach ($list as $k => $v) {
    		$new = $pre.$k;
    		$return[$new] = $v;

    	}
    	return $return;
    }
}

?>