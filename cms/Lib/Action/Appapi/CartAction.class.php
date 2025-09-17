<?php

/**
 * 购物车
 */
class CartAction extends BaseAction
{
    protected function _initialize()
    {
        parent::_initialize();
        if (empty($this->_uid)) {
            $this->returnCode('20044010');
        }
    }

    /**
     * 满减神器，加入商品
     */
    public function addFullReduce()
    {
        $uid = $this->_uid;
        $cartMod = M('Cart');
        $storeId = $_POST['store_id'];
        $reduceGoodsId = isset($_POST['full_reduce_goods_id']) ? trim($_POST['full_reduce_goods_id']) : '';
        $goodsIds = explode('_', $reduceGoodsId);
        if ($uid < 0) {
            $this->returnCode(30010010, array());
        }
        if (empty($goodsIds)) {
            $this->returnCode(1, array(), L_('参数有误'));
        }

        $goodsCountIndex = array_count_values($goodsIds);
        $goodsMod = M('Shop_goods');
        $goods = $goodsMod->where(['goods_id' => ['IN', $goodsIds], 'store_id' => $storeId])->select();
        if (empty($goods)) {
            $this->returnCode(78000002, array());
        }
        $cartMod->startTrans();
        try {
            $cartMod->where(['uid' => $uid, 'store_id' => $storeId])->delete();
            foreach ($goods as $g) {
                $goodInfo = D('Shop_goods')->get_goods_by_id($g['goods_id']);
                if (empty($goodInfo) || $goodInfo['status'] != 1) {
                    $this->returnCode(78000003, array());
                }
                $uniqueNumber = $goodInfo['goods_id'];

                $productParam = [];
                foreach ($goodInfo['spec_list'] as $s) {
                    $valList = array_values($s['list']);
                    if (!empty($valList[0])) {
                        $uniqueNumber .= '_' . $valList[0]['id'];
                        $productParam[] = [
                            'type' => 'spec',
                            'name' => $valList[0]['name'],
                            'spec_id' => $valList[0]['sid'],
                            'id' => $valList[0]['id']
                        ];
                    }
                }

                $propertiesData = [];
                foreach ($goodInfo['properties_list'] as $p) {
                    $valList = array_values($p['val_status']);
                    $data = [];
                    foreach ($valList as $k => $v) {
                        if ($v[1] == 1) {
                            $uniqueNumber .= '_' . $p['id'] . '_' . $k;
                            $propertiesData[] = [
                                'id' => $k,
                                'list_id' => $p['id'],
                                'name' => $v[0]
                            ];
                            break 2;
                        }
                    }
                }
                if ($propertiesData) {
                    $productParam[] = [
                        'type' => 'properties',
                        'data' => $propertiesData
                    ];
                }
                $data = [
                    'host_goods_id' => 0,
                    'productId' => $goodInfo['goods_id'],
                    'productName' => $goodInfo['name'],
                    'productPrice' => $goodInfo['price'],
                    'count' => 1,
                    'uniqueness_number' => $uniqueNumber,
                    'productParam' => $productParam
                ];
                $cartData = [
                    'uid' => $uid,
                    'store_id' => $storeId,
                    'goods_id' => $goodInfo['goods_id'],
                    'uniqueness_number' => $uniqueNumber,
                    'info' => json_encode([$data], JSON_UNESCAPED_UNICODE),
                    'num' => $goodsCountIndex[$goodInfo['goods_id']] ?? 1
                ];
                $cartMod->add($cartData);
            }
            $cartMod->commit();
            $this->returnCode(0);
        } catch (Exception $e) {
            $cartMod->rollback();
            $this->returnCode(10000001);
        }

    }

    /**
     * 添加套餐
     */
    public function addPackage()
    {
        $uid = $this->_uid;
        $cartMod = M('Cart');
        $storeId = $_POST['store_id'];
        $packageId = isset($_POST['com_id']) && $_POST['com_id'] > 0 ? intval($_POST['com_id']) : 8;
        if ($uid < 0) {
            $this->returnCode(30010010, array());
        }
        $goodsMod = M('Shop_goods');
        $comMod = M('Shop_combination');
        $comGoodsMod = M('Shop_combination_goods');
        if (!$comMod->where(['store_id' => $storeId, 'com_id' => $packageId])->find()) {
            $this->returnCode(78000001, array());
        }
        $goods = $comGoodsMod->where(['com_id' => $packageId, 'store_id' => $storeId])->select();
        if (empty($goods)) {
            $this->returnCode(78000002, array());
        }
        $cartMod->startTrans();
        try {
            $cartMod->where(['uid' => $uid, 'store_id' => $storeId])->delete();
            foreach ($goods as $g) {
                $goodInfo = D('Shop_goods')->get_goods_by_id($g['goods_id']);
                if (empty($goodInfo) || $goodInfo['status'] != 1) {
                    $this->returnCode(78000003, array());
                }
                $uniqueNumber = $goodInfo['goods_id'];

                $productParam = [];
                foreach ($goodInfo['spec_list'] as $s) {
                    $valList = array_values($s['list']);
                    if (!empty($valList[0])) {
                        $uniqueNumber .= '_' . $valList[0]['id'];
                        $productParam[] = [
                            'type' => 'spec',
                            'name' => $valList[0]['name'],
                            'spec_id' => $valList[0]['sid'],
                            'id' => $valList[0]['id']
                        ];
                        break;
                    }
                }

                $propertiesData = [];
                foreach ($goodInfo['properties_list'] as $p) {
                    $valList = array_values($p['val_status']);
                    $data = [];
                    foreach ($valList as $k => $v) {
                        if ($v[1] == 1) {
                            $uniqueNumber .= '_' . $p['id'].'_'.$k;
                            $propertiesData[]=[
                                'id' => $k,
                                'list_id' => $p['id'],
                                'name'=>$v[0]
                            ];
                            break 2;
                        }
                    }
                }
                if ($propertiesData) {
                    $productParam[] = [
                        'type' => 'properties',
                        'data' => $propertiesData
                    ];
                }
                $data = [
                    'host_goods_id' => 0,
                    'productId' => $goodInfo['goods_id'],
                    'productName' => $goodInfo['name'],
                    'productPrice' => $goodInfo['price'],
                    'count' => 1,
                    'uniqueness_number' => $uniqueNumber,
                    'productParam' => $productParam
                ];
                $cartData = [
                    'uid' => $uid,
                    'store_id' => $storeId,
                    'com_id' => $packageId,
                    'goods_id' => $goodInfo['goods_id'],
                    'uniqueness_number' => $uniqueNumber,
                    'info' => json_encode([$data], JSON_UNESCAPED_UNICODE),
                    'num' => 1
                ];
                $cartMod->add($cartData);
            }
            D('Shop_goods')->checkGoodsMaxCountInCart($uid,$storeId);
            $cartMod->commit();
            $this->returnCode(0);
        } catch (Exception $e) {
            $cartMod->rollback();
            $this->returnCode(1, array(),$e->getMessage());
        }

    }

    public function add()
    {
        $cartMod = M('Cart');
        $uid = $this->_uid;
        $storeId = $_POST['store_id'];
        $cartMod->startTrans();
        try {
            $masterGoodsId = 0;
            if (!empty($_POST['product'])) {
                $product = $_POST['product'];
                if (!is_array($product)) {
                    $product = json_decode(htmlspecialchars_decode($product), true);
                }

                $uniquenessNumber = '';
                $initNum = 1;
                foreach ($product as $k=>$v) {
                    if ($v['host_goods_id'] > 0) {
                        continue;
                    }
                    $masterGoodsId = $v['productId'];
                    $uniquenessNumber = $v['uniqueness_number'];
                    $product[$k]['count'] = 1;
                    $initNum = $v['count'];
                    break;
                }
                $isShopGoods = (new Shop_goodsModel())->isShopOrMallGoods($masterGoodsId, true);
                if (!$isShopGoods) {
                    throw new Exception(L_('该商品只能在' . C('config.mall_alias_name') . '购买'));
                }
                if (empty($uniquenessNumber)) {
                    $this->returnCode(77000002, array());
                }
                $old = $cartMod->where(['uid' => $uid, 'store_id' => $storeId, 'uniqueness_number' => $uniquenessNumber])->find();

                $nowNum = $old ? $old['num'] + 1 : $initNum;

                if ($old) {
                    $cartMod->where(['id' => $old['id']])->setInc('num');
                } else {
                    $data = [
                        'uid' => $uid,
                        'store_id' => $storeId,
                        'goods_id' => $masterGoodsId,
                        'uniqueness_number' => $uniquenessNumber,
                        'info' => json_encode($product, JSON_UNESCAPED_UNICODE),
                        'num' => $initNum ?? 1
                    ];
                    $cartMod->add($data);
                }
            } else {
                $uniquenessNumber = $_POST['uniqueness_number'];
                $number = isset($_POST['number']) ? intval($_POST['number']) : 0;
                if ($number == 0) {
                    $cartMod->where(['uniqueness_number' => $uniquenessNumber, 'uid' => $this->_uid])->delete();
                } else {
                    $old = $cartMod->where(['uniqueness_number' => $uniquenessNumber, 'uid' => $this->_uid])->find();
                    if ($old) {
                        $cartMod->where(['id' => $old['id']])->save(['num' => $number]);
                        $masterGoodsId = $old['goods_id'];
                    } else {
                        $this->returnCode(77000002, array());
                    }
                }
            }

            if ($masterGoodsId && !D('Shop_goods')->isShow($masterGoodsId, 1 | 4)) {
                throw new Exception(L_('抱歉，当前商品暂未在售卖时间范围内哦'));
            }

            D('Shop_goods')->checkGoodsMaxCountInCart($uid, $storeId, $masterGoodsId);

            $calculate = $this->getCartProduct(true);

            $this->checkSortLimitNum();
            $cartMod->commit();
            if(is_array($calculate) && !empty($calculate['empty_cart'])){
                $this->returnCode(77000001, array());
            }
            $this->returnCode(0, $calculate);
        } catch (\Exception $e) {
            $cartMod->rollback();
            $this->returnCode(1, array(),$e->getMessage());
        }


    }

    private function checkSortLimitNum()
    {   
        $cartMod = D('Cart');
        $uid = $this->_uid;
        $storeId = $_POST['store_id'];
        $productCart = $cartMod->getShopGoodsList(['c.uid' => $uid, 'c.store_id' => $storeId]); 
 
        $sortLimitNum = M('Shop_goods_sort')->where(['store_id'=>$storeId])->getField('sort_id,category_limit_num');
       
        $sortArr = [];
        foreach($productCart as $key => $val){
            if(isset($sortArr[$val['sort_id']])){
                $sortArr[$val['sort_id']] += $val['num'];
            }else{
                $sortArr[$val['sort_id']] = $val['num'];
            }
            
        }
        foreach($sortArr as $key => $val){
            $category_limit_num = $sortLimitNum[$key] ?? -1; 
            if($category_limit_num == -1){
                continue;
            }
            if($val > $category_limit_num){
                throw new \Exception('该分类下限购'.$category_limit_num. '件商品！');
            }
        }
    }

    public function getCartProduct($master = false)
    {
        $cartMod = M('Cart');
        $uid = $this->_uid;
        $storeId = $_POST['store_id'];
        $deliverExtraPrice = isset($_POST['deliverExtraPrice']) ? intval($_POST['deliverExtraPrice']) : 0;
        $productCart = $cartMod->where(['uid' => $uid, 'store_id' => $storeId])->select();
        $cartMod->where(['uid' => $uid, 'store_id' => $storeId])->save(['is_choose' => 1]);
        $storeInfo = D('Merchant_store')->getStoreInfoById($storeId);
        $product = [];
        if(isset($_POST['spellProduct'])){
            $product = $_POST['spellProduct'];
        }else{
            foreach ($productCart as $v) {
                $info = json_decode($v['info'], true);
                foreach ($info as &$i) {
                    $i['count'] = $i['count'] * $v['num'];
                }
                $product = array_merge($product, $info);
            }
        }
        if (empty($product)) {
            if($master){
                return ['empty_cart' => 1];
            }
            $this->returnCode(77000001, array());
        }

        //店铺logo
        $storeImage = '';
        if ($storeInfo['logo']) {
            $storeImage = replace_file_domain($storeInfo['logo']);
        }
        $storeImage = thumb_img($storeImage, 180, 120, 'fill');

        //获取店铺是否存在必选分类商品
        $mustSort = D('Merchant_store')->getMustSortIdsByStoreId($storeId, 'sort_id,sort_name');
        if ($mustSort) {
            $mustSortId = D('Shop_goods_sort')->getAllSonIds($mustSort['sort_id'], $storeId, true, false);
        } else {
            $mustSortId = [];
        }

        $totalPrice = 0;
        $totalNum = 0;
        $isAddMustGood = empty($mustSortId) ? true : false;
        $packPrice = 0;
        $goods = [];
        $sortNum = [];
        $allTotalPrice = 0;
        $buy_can_get_score_total = '';
        $buy_can_get_score_total_txt = '';

        //计算str
        foreach ($product as $k => $p) {
            if (!D('Shop_goods')->isShow($p['productId'], 1 | 4)) {
                $cartMod->where(['uid' => $uid, 'store_id' => $storeId, 'goods_id' => $p['productId']])->delete();
                unset($product[$k]);
            }

            $strSpec = [];
            $strParam = [];
            foreach ($p['productParam'] as $r) {
                if ($r['type'] == 'spec') {
                    $strSpec[] = $r['name'];
                } else {
                    foreach ($r['data'] as $d) {
                        $strParam[] = $d['name'];
                    }
                }
            }
            $str = '';
            $strSpec && $str = implode(',', $strSpec);
            $strParam && $str = $str ? $str . '、' . implode(',', $strParam) : implode(',', $strParam);
            $product[$k]['str'] = $str;
            $product[$k]['num'] = $p['count'];
            $product[$k]['name'] = $p['productName'];
            $product[$k]['goods_id'] = $p['productId'];

        }
        $product = array_values($product);
        $combineProduct = D('Shop_goods')->combination_data($product, 'str', 0);
        $combineProduct = array_combine(array_column($combineProduct, 'uniqueness_number'), array_column($combineProduct, 'str'));
        $pieceGoodsMod = D('Shop_subsidiary_piece_goods');

        $levelDiscount = (new Merchant_store_shopModel())->getVipLevelDiscount($uid, $storeId, 'shop');

        //店铺优惠条件
        $sorts_discout = D('Shop_goods_sort')->get_sorts_discount($storeId, 1);

        foreach ($product as $p) {
            $goods_all_total = 0; //商品原价
            if ($p['host_goods_id'] == 0) {
                $goodInfo = D('Shop_goods')->get_goods_by_id($p['productId']);
                $goods_all_total = ($goodInfo['is_seckill_price']?$goodInfo['seckill_price']:$goodInfo['price'])*$p['count'];
                $old_price = $goodInfo['price'];
                $old_seckill_price = $goodInfo['seckill_price'];
                if (C('config.show_vip_price') == 1) {
                    $goodInfo['price'] = getFormatNumber($goodInfo['price'] * $levelDiscount['discount'] - $levelDiscount['discount_price'], 2, false);
                    $goodInfo['o_price'] = getFormatNumber($goodInfo['o_price'] * $levelDiscount['discount'] - $levelDiscount['discount_price'], 2, false);
                    if ($goodInfo['is_seckill_price']) {
                        $goodInfo['seckill_price'] = getFormatNumber($goodInfo['seckill_price'] * $levelDiscount['discount'] - $levelDiscount['discount_price'], 2, false);
                    }
                }

                if ($mustSortId && !$isAddMustGood && in_array($goodInfo['sort_id'], $mustSortId)) {
                    $isAddMustGood = true;
                }
                $packPrice += $goodInfo['packing_charge'] * $p['count'];
                $totalNum += $p['count'];

                $shop_sort_discount = isset($sorts_discout[$goodInfo['sort_id']]['discount']) && $sorts_discout[$goodInfo['sort_id']]['discount'] ? $sorts_discout[$goodInfo['sort_id']]['discount'] : 100;
                $shop_sort_discount = $shop_sort_discount>0?strval(round($shop_sort_discount*0.1, 2)):0;
                if($shop_sort_discount>0){
                    $sort_dis_price = getFormatNumber($shop_sort_discount*$old_price/10,2,false);
                    $seckill_dis_price = $goodInfo['is_seckill_price']?getFormatNumber($shop_sort_discount*$old_seckill_price/10,2,false):0;
                    if (C('config.show_vip_price') == 1) {
                        $goodInfo['price'] = $sort_dis_price - ($old_price - $goodInfo['price']);
                        if ($goodInfo['is_seckill_price']) {
                            $goodInfo['seckill_price'] = $seckill_dis_price - ($old_seckill_price - $goodInfo['seckill_price']);
                        }else{
                            $goodInfo['seckill_price'] = $seckill_dis_price;
                        }
                    }else{
                        $goodInfo['price'] = $sort_dis_price;
                        $goodInfo['seckill_price'] = $seckill_dis_price;
                    }
                }

                //计算总金额，限时优惠部分按照限价处理，超出按照原价
                $goodSumMoney = 0;
                if ($goodInfo['spec_list']) {
                    //多规格
                    $spec_index = [];
                    foreach ($p['productParam'] as $spec) {
                        if ($spec['type'] == 'spec') {
                            $spec_index[] = $spec['id'];
                        }
                    }
                    $spec_index_str = implode('_', $spec_index);
                    $spec_info = $goodInfo['list'][$spec_index_str];
                    $goods_all_total = ($goodInfo['is_seckill_price']?$spec_info['seckill_price']:$spec_info['price'])*$p['count'];
                    $old_spec_price = $spec_info['price'];
                    $old_spec_seckill_price = $spec_info['seckill_price'];
                    if (C('config.show_vip_price') == 1) {
                        $spec_info['price'] = getFormatNumber($spec_info['price'] * $levelDiscount['discount'] - $levelDiscount['discount_price'], 2, false);
                        if($goodInfo['is_seckill_price']){
                            $spec_info['seckill_price'] = getFormatNumber($spec_info['seckill_price'] * $levelDiscount['discount'] - $levelDiscount['discount_price'], 2, false);
                        }
                    }
                    if($shop_sort_discount>0){
                        $sort_spec_dis_price = getFormatNumber($shop_sort_discount*$old_spec_price/10,2,false);
                        $seckill_spec_dis_price = $goodInfo['is_seckill_price']?getFormatNumber($shop_sort_discount*$old_spec_seckill_price/10,2,false):0;
                        if (C('config.show_vip_price') == 1) {
                            $spec_info['price'] = $sort_spec_dis_price - ($old_spec_price - $spec_info['price']);
                            if ($goodInfo['is_seckill_price']) {
                                $spec_info['seckill_price'] = $seckill_spec_dis_price - ($old_spec_seckill_price - $spec_info['seckill_price']);
                            }else{
                                $spec_info['seckill_price'] = $seckill_spec_dis_price;
                            }
                        }else{
                            $spec_info['price'] = $sort_spec_dis_price;
                            $spec_info['seckill_price'] = $seckill_spec_dis_price;
                        }
                    }
                    if ($goodInfo['is_seckill_price']) {
                        $spec_info['seckill_price'] = $spec_info['seckill_price'] > 0 ? $spec_info['seckill_price'] : $spec_info['price'];
                        if ($spec_info['limit_num'] == -1) {
                            $goodSumMoney = $spec_info['seckill_price'] * $p['count'];
                        } else {
                            $overCount = $p['count'] - $spec_info['limit_num'];
                            if ($overCount > 0) {
                                $goodSumMoney = $spec_info['seckill_price'] * $spec_info['limit_num'];
                                $goodSumMoney += $spec_info['price'] * $overCount;
                                $goods_all_total = $old_spec_seckill_price * $spec_info['limit_num'];
                            } else {
                                $goodSumMoney = $spec_info['seckill_price'] * $p['count'];
                            }
                        }
                    } else {
                        $goodSumMoney = $spec_info['price'] * $p['count'];
                    }
                } else {
                    //单一规格
                    if ($goodInfo['is_seckill_price']) {
                        if ($goodInfo['limit_num'] == -1) {
                            $goodSumMoney = $goodInfo['seckill_price'] * $p['count'];
                        } else {
                            $overCount = $p['count'] - $goodInfo['limit_num'];
                            if ($overCount > 0) {
                                $goodSumMoney = $goodInfo['seckill_price'] * $goodInfo['limit_num'];
                                $goodSumMoney += $goodInfo['o_price'] * $overCount;
                            } else {
                                $goodSumMoney = $goodInfo['seckill_price'] * $p['count'];
                            }
                        }
                    } else {
                        $goodSumMoney = $goodInfo['price'] * $p['count'];
                    }
                }
                $goodSumMoney = getFormatNumber($goodSumMoney);
                $goods_all_total = getFormatNumber($goods_all_total);

                //消费一元获得积分
                $buy_can_get_score = D('Goods_score')->getGoodsScore($goodSumMoney,'shop',($goodInfo['spec_list']?true:false),$goodInfo['score_percent']);
                $goods[] = [
                    'goods_id' => $p['productId'],
					'product_id' => $p['productId'],
                    'sort_id' => $goodInfo['sort_id'],
                    'name' => $goodInfo['name'],
                    'str' => isset($combineProduct[$p['uniqueness_number']]) ? $combineProduct[$p['uniqueness_number']] : '',
                    'price' => $goodSumMoney,  //是秒杀的时候是秒杀价，不是的时候是原始价
                    'is_seckill_price' => $goodInfo['is_seckill_price'], //是否是秒杀价(0:否，1：是)
                    'seckill_stock' => isset($goodInfo['seckill_stock'])?$goodInfo['seckill_stock']:$goodInfo['stock_num'],
                    'unit' => $goodInfo['unit'],
                    'min_num' => $goodInfo['min_num'],
                    'max_num' => $goodInfo['max_num'],
                    'num' => $p['count'],
                    'stock' => $goodInfo['stock_num'],
                    'uniqueness_number' => $p['uniqueness_number'],
                    'single_no_delivery' => $goodInfo['single_no_delivery'],
                    'goods_all_total' => $goods_all_total, //商品原价总额
                    'buy_can_get_score' => $buy_can_get_score?$buy_can_get_score['score']:0,
                    'buy_can_get_score_txt' => $buy_can_get_score?$buy_can_get_score['score_txt']:'',
                ];

                if (isset($sortNum[$goodInfo['sort_id']])) {
                    $sortNum[$goodInfo['sort_id']] += $p['count'];
                } else {
                    $sortNum[$goodInfo['sort_id']] = $p['count'];
                }
                $totalPrice += $goodSumMoney;
                $allTotalPrice += $goods_all_total;
                $buy_can_get_score_total += $buy_can_get_score?$buy_can_get_score['score']:0;
                $buy_can_get_score_total_txt = $buy_can_get_score?$buy_can_get_score['score_txt']:0;
            }else{
                $piecePrice = $pieceGoodsMod->where(['host_goods_id'=>$p['host_goods_id'],'is_delete'=>0,'goods_id'=>$p['productId']])->getField('price');
                if (!is_null($piecePrice)) {
                    $pieceTotalPrice= $piecePrice * $p['count'];
                } else {
                    $pieceTotalPrice= $p['productPrice'] * $p['count'];
                }
                $totalPrice += $pieceTotalPrice;
                if ($p['uniqueness_number']) {
                    foreach ($goods as $gk => $gv) {
                        if ($gv['uniqueness_number'] == $p['uniqueness_number']) {
                            $goods[$gk]['price'] += $pieceTotalPrice;
                        }
                    }
                }
                $allTotalPrice += $pieceTotalPrice;
            }
        }
        
        //按订单收取配送费
        if ($storeInfo['pack_type'] == 2) {
            $packPrice = $storeInfo['pack_fee'];
        }

        foreach ($sortNum as $sort => $num) {
            $fids = D('Shop_goods_sort')->getAllFidsBySortId($sort);
            foreach ($fids as $f) {
                if ($f > 0) {
                    if (isset($sortNum[$f])) {
                        $sortNum[$f] += $num;
                    } else {
                        $sortNum[$f] = $num;
                    }
                }
            }
        }

        //存在平台配送方式，根据商品原价allTotalPrice计算下预估配送费
        $tm = time();
        if ($storeInfo['deliver_type'] == 0 || $storeInfo['deliver_type'] == 3) {
            $deliverReturn = D('Deliver_set')->getDeliverInfo($storeInfo, $allTotalPrice);
            if (strtotime($deliverReturn['delivertime_start']) <= $tm && $tm <= strtotime($deliverReturn['delivertime_stop'])) {
                $deliveryFee = $deliverReturn['delivery_fee'];
            } else if (strtotime($deliverReturn['delivertime_start2']) <= $tm && $tm <= strtotime($deliverReturn['delivertime_stop2'])) {
                $deliveryFee = $deliverReturn['delivery_fee2'];
            } else if (strtotime($deliverReturn['delivertime_start3']) <= $tm && $tm <= strtotime($deliverReturn['delivertime_stop3'])) {
                $deliveryFee = $deliverReturn['delivery_fee3'];
            }
        }

        //起送价和加价送
        $priceList = D('Merchant_store')->getStoreBasePrice($storeId);
        $basePrice = isset($priceList['delivery_price']) ? $priceList['delivery_price'] : 0;
        $extraPrice = isset($priceList['extra_price']) ? $priceList['extra_price'] : 0;

        //是否可以结算和文案
        $totalPrice += $packPrice;
        $allTotalPrice += $packPrice;
        $canBuy = false;
        $canNotBuyTxt = '';
        $btnTxt = '';
        $useExtraPrice = 0;
        if($deliverExtraPrice == 1 && $totalPrice >= $basePrice){
            $deliverExtraPrice = 0;
        }

        $singleNoDeliveryArr = array_column($goods, 'single_no_delivery');
        if (!$isAddMustGood) {
            $btnTxt = $canNotBuyTxt = '未点必选品';
        }else if(!in_array(0,$singleNoDeliveryArr)){
            $btnTxt = $canNotBuyTxt = '单点不配送';
        } else {
            if ($totalPrice >= $basePrice) {
                $canBuy = true;
            } elseif ($deliverExtraPrice == 1) {
                $canBuy = true;
                $totalPrice += $extraPrice;
                $useExtraPrice = $extraPrice;
                $allTotalPrice += $extraPrice;
            } elseif ($totalPrice < $basePrice) {
                $canNotBuyTxt = '没有达到起送价';
            }
        }
        $originTotalPrice = $totalPrice;

        //满减
        $tip = '';
        $reduceMoney = 0;

        //开启了加价送，则显示加价送。否则显示凑单
        if ($extraPrice <= 0 && $totalPrice < $basePrice) {
            //凑单
            $tip = L_('还差X1就能起送', ['X1' => C('config.Currency_symbol') . getFormatNumber($basePrice - $totalPrice)]);
            $collectGoods = D('Shop_goods')->getCollectGoodsByPrice(0.01, $storeId);
        } else {
            $discounts = D('Shop_discount')->getDiscounts($storeInfo['mer_id'], $storeId, 1, 5, 0, ['type' => 1, 'percentage_status' => 0, 'is_share' => 1]);
            $discounts = empty($discounts) ? [] : (isset($discounts[$storeId]) ? $discounts[$storeId] : []);
            $gtDiscount = [];
            $ltDiscount = [];
            foreach ($discounts as $d) {
                if ($originTotalPrice - $useExtraPrice - $packPrice >= $d['full_money']) {
                    $gtDiscount[] = $d;
                } else {
                    $ltDiscount[] = $d;
                }
            }
            array_multisort(array_column($gtDiscount, 'full_money'), SORT_DESC, $gtDiscount);
            array_multisort(array_column($gtDiscount, 'full_money'), SORT_ASC, $ltDiscount);
            if ($gtDiscount) {
                $firstGtDiscount = $gtDiscount[0];
                if($firstGtDiscount['full_money'] > 0){
                    $tip .= L_('已享X1减X2', ['X1' => getFormatNumber($firstGtDiscount['full_money']), 'X2' => getFormatNumber($firstGtDiscount['reduce_money'])]);
                }else{
                    $tip .= L_('已享满X1减X2', ['X1' => getFormatNumber($firstGtDiscount['full_money']), 'X2' => getFormatNumber($firstGtDiscount['reduce_money'])]);
                }
                $reduceMoney += $firstGtDiscount['reduce_money'];
                $totalPrice -= $firstGtDiscount['reduce_money'];
                $allTotalPrice -= $firstGtDiscount['reduce_money'];
            }
            if ($ltDiscount) {
                $firstLtDiscount = $ltDiscount[0];
                $leftMoney = getFormatNumber($firstLtDiscount['full_money'] - ($originTotalPrice - $useExtraPrice - $packPrice));
                $tip .= ($tip == '' ? '' : ',') . L_('再买X1可享X2减X3', ['X1' => C('config.Currency_symbol') . getFormatNumber($leftMoney), 'X2' => getFormatNumber($firstLtDiscount['full_money']), 'X3' => getFormatNumber($firstLtDiscount['reduce_money'])]);
                $collectGoods = D('Shop_goods')->getCollectGoodsByPrice(0.01, $storeId);
            }
        }
        $rs = [
            'goods_list' => $goods,
            'total_num' => $totalNum,
            'origin_total_price' => ($originTotalPrice > 0 ? getFormatNumber($originTotalPrice) : 0),
            'total_price' => ($totalPrice > 0 ? getFormatNumber($totalPrice) : 0),
            'pack_price' => getFormatNumber($packPrice),
            'tip' => $tip,
            'required_sort' => $isAddMustGood ? new \stdClass() : $mustSort,
            'can_buy' => $canBuy,
            'can_not_buy_txt' => $canNotBuyTxt,
            'settle_btn_txt' => $btnTxt,
            'sort_num' => $sortNum,
            'store_name' => $storeInfo['name'],
            'store_id' => $storeId,
            'image' => $storeImage,
            'base_price' => getFormatNumber($basePrice),
            'extra_price' => getFormatNumber($extraPrice),
            'collect_goods' => isset($collectGoods) ? $collectGoods : [],
            'use_deliver_extra_price'=>$deliverExtraPrice,
            'all_total_price' => ($allTotalPrice>0?getFormatNumber($allTotalPrice):0), //优惠之前金额
            'buy_can_get_score' => $buy_can_get_score_total,
            'buy_can_get_score_txt' => $buy_can_get_score_total_txt,
        ];
        !is_null($deliveryFee) && $rs['delivery_fee'] = getFormatNumber($deliveryFee);
        return $rs;
    }

    public function detail()
    {
        try {
            $this->returnCode(0, $this->getCartProduct());
        } catch (\Exception $e) {
            $this->returnCode($e->getCode(), array(), L_($e->getMessage()));
        }

    }

    public function allStore()
    {
        try {
            $lat = isset($_POST['lat']) ? $_POST['lat'] : 0;
            $lng = isset($_POST['lng']) ? $_POST['lng'] : 0;
            $storeIds = M('Cart')->where(['uid' => $this->_uid])->getField('store_id', true);
            $storeIds = array_unique($storeIds);
            $retval = [];
            foreach ($storeIds as $id) {
                $retval[] = $this->getSampleCartProduct($id, $this->_uid, $lat, $lng);
            }
            $this->returnCode(0, $retval);
        } catch (\Exception $e) {
            $this->returnCode($e->getCode(), array(), L_($e->getMessage()));
        }
    }

    private function getSampleCartProduct($storeId, $uid, $lat, $lng)
    {
        $cartMod = M('Cart');
        $productCart = $cartMod->where(['uid' => $uid, 'store_id' => $storeId])->select();
        $storeInfo = D('Merchant_store')->getStoreInfoById($storeId);
        $product = [];
        foreach ($productCart as $v) {
            $info = json_decode($v['info'], true);
            foreach ($info as &$i) {
                $i['count'] = $i['count'] * $v['num'];
            }
            $product = array_merge($product, $info);
        }
        if (empty($product)) {
            return [];
        }

        //店铺logo
        $storeImage = '';
        if ($storeInfo['logo']) {
            $storeImage = replace_file_domain($storeInfo['logo']);
        }
        $storeImage = thumb_img($storeImage, 120, 120, 'fill');

        $totalPrice = 0;
        $totalNum = 0;
        $packPrice = 0;
        $goods = [];

        //计算str
        foreach ($product as $k => $p) {
            if (!D('Shop_goods')->isShow($p['productId'], 1 | 4)) {
                $cartMod->where(['uid' => $uid, 'store_id' => $storeId, 'goods_id' => $p['productId']])->delete();
                unset($product[$k]);
            }

            $strSpec = [];
            $strParam = [];
            foreach ($p['productParam'] as $r) {
                if ($r['type'] == 'spec') {
                    $strSpec[] = $r['name'];
                } else {
                    foreach ($r['data'] as $d) {
                        $strParam[] = $d['name'];
                    }
                }
            }
            $str = '';
            $strSpec && $str = implode(',', $strSpec);
            $strParam && $str = $str ? $str . '、' . implode(',', $strParam) : implode(',', $strParam);
            $product[$k]['str'] = $str;
            $product[$k]['num'] = $p['count'];
            $product[$k]['name'] = $p['productName'];
            $product[$k]['goods_id'] = $p['productId'];
        }
        $product = array_values($product);
        $combineProduct = D('Shop_goods')->combination_data($product, 'str', 0);
        $combineProduct = array_combine(array_column($combineProduct, 'uniqueness_number'), array_column($combineProduct, 'str'));
        $goods_image_class = new goods_image();
        foreach ($product as $p) {

            if ($p['host_goods_id'] == 0) {
                $goodInfo = D('Shop_goods')->get_goods_by_id($p['productId']);

                $goodSumMoney = 0;
                if ($goodInfo['spec_list']) {
                    //多规格
                    $spec_index = [];
                    foreach ($p['productParam'] as $spec) {
                        if ($spec['type'] == 'spec') {
                            $spec_index[] = $spec['id'];
                        }
                    }
                    $spec_index_str = implode('_', $spec_index);
                    $spec_info = $goodInfo['list'][$spec_index_str];
                    if ($goodInfo['is_seckill_price']) {
                        $spec_info['seckill_price'] = $spec_info['seckill_price'] > 0 ? $spec_info['seckill_price'] : $spec_info['price'];
                        if ($spec_info['limit_num'] == -1) {
                            $goodSumMoney = $spec_info['seckill_price'] * $p['count'];
                        } else {
                            $overCount = $p['count'] - $spec_info['limit_num'];
                            if ($overCount > 0) {
                                $goodSumMoney = $spec_info['seckill_price'] * $spec_info['limit_num'];
                                $goodSumMoney += $spec_info['price'] * $overCount;
                            } else {
                                $goodSumMoney = $spec_info['seckill_price'] * $p['count'];
                            }
                        }
                    } else {
                        $goodSumMoney = $spec_info['price'] * $p['count'];
                    }
                } else {
                    //单一规格
                    if ($goodInfo['is_seckill_price']) {
                        if ($goodInfo['limit_num'] == -1) {
                            $goodSumMoney = $goodInfo['seckill_price'] * $p['count'];
                        } else {
                            $overCount = $p['count'] - $goodInfo['limit_num'];
                            if ($overCount > 0) {
                                $goodSumMoney = $goodInfo['seckill_price'] * $goodInfo['limit_num'];
                                $goodSumMoney += $goodInfo['o_price'] * $overCount;
                            } else {
                                $goodSumMoney = $goodInfo['seckill_price'] * $p['count'];
                            }
                        }
                    } else {
                        $goodSumMoney = $goodInfo['price'] * $p['count'];
                    }
                }
                $goodSumMoney = getFormatNumber($goodSumMoney);
                $totalPrice += $goodSumMoney;

                $totalNum += $p['count'];
                $tmpPicArr = $goods_image_class->get_allImage_by_path($goodInfo['image'], 's');
                $pimage = thumb_img($tmpPicArr[0], 240, 240, 'fill');
                $goods[] = [
                    'goods_id' => $p['productId'],
                    'sort_id' => $goodInfo['sort_id'],
                    'name' => $goodInfo['name'],
                    'image' => $pimage,
                    'packing_charge' => $goodInfo['packing_charge'],
                    'str' => isset($combineProduct[$p['uniqueness_number']]) ? $combineProduct[$p['uniqueness_number']] : '',
                    'price' => $p['productPrice'],  //是秒杀的时候是秒杀价，不是的时候是原始价
                    'is_seckill_price' => $goodInfo['is_seckill_price'],
                    'price' => $goodSumMoney,
                    'unit' => $goodInfo['unit'],
                    'min_num' => $goodInfo['min_num'],
                    'max_num' => $goodInfo['max_num'],
                    'num' => $p['count'],
                    'uniqueness_number' => $p['uniqueness_number'],
                    'single_no_delivery' => $goodInfo['single_no_delivery']
                ];
            }
        }

        $mustSort = D('Merchant_store')->getMustSortIdsByStoreId($storeId, 'sort_id,sort_name');
        //起送价和加价送
        $priceList = D('Merchant_store')->getStoreBasePrice($storeId);
        $basePrice = isset($priceList['delivery_price']) ? $priceList['delivery_price'] : 0;

        $singleNoDeliveryArr = array_column($goods, 'single_no_delivery');
        $btnTxt = '';
        if (!in_array(0, $singleNoDeliveryArr)) {
            $btnTxt = '单点不配送';
        }

        $rs = [
            'goods_list' => $goods,
            'total_num' => $totalNum,
            'total_price' => getFormatNumber($totalPrice),
            'pack_type' => $storeInfo['pack_type'],
            'pack_fee' => getFormatNumber($storeInfo['pack_fee']),
            'required_sort' => $mustSort ? $mustSort : new \stdClass(),
            'store_name' => $storeInfo['name'],
            'store_id' => $storeId,
            'image' => $storeImage,
            'basic_price' => getFormatNumber($basePrice),
            'settle_btn_txt' => $btnTxt,
        ];
        return $rs;
    }

    /**
     * 清空购物车
     */
    public function clear()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20044010');
        }
        $storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        if ($storeId == 0) {
            M('Cart')->where(['uid' => $this->_uid])->delete();
        } else {
            M('Cart')->where(['uid' => $this->_uid, 'store_id' => $storeId])->delete();
        }
        $this->returnCode(0, []);
    }

    public function productNum()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20044010');
        }
        $storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $where = [
            'uid' => $this->_uid
        ];
        if ($storeId > 0) {
            $where['store_id'] = $storeId;
        }
        $totalNum = M('Cart')->where($where)->sum('num');
        $this->returnCode(0, ['total_num' => $totalNum ? $totalNum : 0]);
    }

    public function storeNum()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20044010');
        }
        $storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $where = [
            'uid' => $this->_uid
        ];
        if ($storeId > 0) {
            $where['store_id'] = $storeId;
        }
        $storeNum = M('Cart')->where($where)->field("sum(`num`) AS total_num,store_id")->group('store_id')->select();
        if ($storeNum) {
            $storeNum = array_combine(array_column($storeNum, 'store_id'), array_column($storeNum, 'total_num'));
        } else {
            $storeNum = [];
        }
        $this->returnCode(0, $storeNum);
    }

    public function chooseProduct()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20044010');
        }
        $storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $uniquenessNumber = isset($_POST['uniqueness_number']) ? $_POST['uniqueness_number'] : [];
        if($storeId < 1){
            $this->returnCode(1, null, L_('缺少参数store_id'));
        }
        if (!is_array($uniquenessNumber)) {
            $uniquenessNumber = json_decode(htmlspecialchars_decode($uniquenessNumber), true);
        }
        if ($uniquenessNumber) {
            M('Cart')->where(['uid' => $this->_uid, 'store_id' => $storeId, 'uniqueness_number' => ['NOT IN', $uniquenessNumber]])->save(['is_choose' => 0]);
            M('Cart')->where(['uid' => $this->_uid, 'store_id' => $storeId, 'uniqueness_number' => ['IN', $uniquenessNumber]])->save(['is_choose' => 1]);
        }
        $this->returnCode(0);
    }

    /**
     * 删除
     */
    public function deleteProduct()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20044010');
        }
        $uniquenessNumber = isset($_POST['uniqueness_number']) ? $_POST['uniqueness_number'] : [];
        if (!is_array($uniquenessNumber)) {
            $uniquenessNumber = json_decode(htmlspecialchars_decode($uniquenessNumber), true);
        }
        if ($uniquenessNumber) {
            M('Cart')->where(['uid' => $this->_uid, 'uniqueness_number' => ['IN', $uniquenessNumber]])->delete();
        }
        $this->returnCode(0);
    }

    /**
     * 初始化分袋数据，把购物车商品丢给第一个分袋打包人
     */
    public function initSpellPackage()
    {
        if (empty($_POST)) {
            $_POST = json_decode(file_get_contents('php://input'), true);
        }
        if (empty($this->_uid)) {
            $this->returnCode('20044010');
        }
        $cartMod = M('Cart');
        $uid = $this->_uid;
        $storeId = $_POST['store_id'];
        $cart = $cartMod->where(['uid' => $this->_uid, 'store_id' => $storeId, 'is_choose' => 1])->select();
        $product = [];
        foreach ($cart as $v) {
            $info = json_decode($v['info'], true);
            foreach ($info as &$i) {
                $i['count'] = $i['count'] * $v['num'];
            }
            $product = array_merge($product, $info);
        }
        $productCart = $product;
        $shop = D('Merchant_store')->getStoreInfoById($storeId);
        if ($shop['have_shop'] == 0 || $shop['status'] != 1) {
            $this->returnCode(1, null, L_('商家已经关闭了该业务,不能下单了!'));
        }
        if (C('config.store_shop_auth') == 1 && $shop['auth'] < 3) {
            $this->returnCode(1, null, L_('您查看的X1没有通过资质审核！', C('config.shop_alias_name')));
        }
        $store_image_class = new store_image();
        $images = $store_image_class->get_allImage_by_path($shop['pic_info']);
        $shop['images'] = $images ? array_shift($images) : '';

        $index = 1;
        $cartid = md5($storeId . uniqid() . date('YmdHis'));
        $cartData = $productCart;
        if ($cartData) {
            $shopGoodsDB = D('Shop_goods');
            $resultGoods = D('Shop_order_temp')->getGoods($cartid, $storeId, $index);
            foreach ($cartData as &$row) {
                $goods_id = $row['productId'];
                $num = $row['count'];
                if ($num < 1) continue;
                $spec_ids = array();
                $str_s = array();
                $str_p = array();
                foreach ($row['productParam'] as $r) {
                    if ($r['type'] == 'spec') {
                        $spec_ids[] = $r['id'];
                        $str_s[] = $r['name'];
                    } else {
                        foreach ($r['data'] as $d) {
                            $str_p[] = $d['name'];
                        }
                    }
                }
                if (isset($resultGoods[$goods_id])) {
                    $num += $resultGoods[$goods_id]['count'];
                }
                $spec_str = $spec_ids ? implode('_', $spec_ids) : '';
                $t_return = $shopGoodsDB->check_stock($goods_id, $num, $spec_str, $shop['stock_type'], $storeId, false, $this->_uid, '', '', '', '', $row['host_goods_id']);
                if ($t_return['status'] != 1) {
                    $this->returnCode(1, null, $t_return['msg']);
                }
                $row['productPrice'] = floatval($t_return['price']);
                $row['oldPrice'] = floatval($t_return['old_price']);
                $row['productPackCharge'] = floatval($t_return['packing_charge']);
                $row['maxNum'] = $t_return['maxNum'];
                $row['limit_type'] = $t_return['limit_type'];
            }
        }

        $data = array('cartid' => $cartid, 'store_id' => $storeId, 'uid' => $uid);
        $info = array();
        $info['index'] = $index;
        $info['from'] = 0;
        $info['data'] = $cartData;
        $info['name'] = L_('X1号订购人', $index);
        $info['uid'] = $this->_uid;
        $info['avatar'] = "";

        $arrIndex = is_numeric($index) ? 'index_' . $index : $index;
        if ($info['data']) {
            foreach ($info['data'] as &$nowData) {
                $nowData['packname'] = $info['name'];
            }
        }
        $data['info'] = json_encode(array($arrIndex => $info));
        $data['max_index'] = 1;
        D('Shop_order_temp')->add($data);
        $data = array(
            'name' => $info['name'],
            'avatar' => "",
            'cartid' => $cartid,
            'index' => $index,
            'share_url' => str_replace('appapi.php', 'wap.php', U('Wap/Shop/spell', ['cartid' => $cartid, 'store_id' => $storeId], true, false, true))

        );
        $this->returnCode(0, $data);
    }


    /**
     * 拼单购物车
     */
    public function addSpellProduct()
    {
        if (empty($_POST)) {
            $_POST = json_decode(file_get_contents('php://input'), true);
        }
        if (empty($this->_uid)) {
            $this->returnCode('20044010');
        }
        $uid = $this->_uid;
        $storeId = $_POST['store_id'];
        $from = isset($_POST['from']) ? intval($_POST['from']) : 0;
        if (!is_array($_POST['product'])) {
            $_POST['product'] = json_decode(htmlspecialchars_decode($_POST['product']), true);
        }

        $uniquenessNumber = isset($_POST['uniqueness_number']) ? trim($_POST['uniqueness_number']) : '';
        $number = isset($_POST['number']) ? intval($_POST['number']) : 0;
        $cartid = isset($_POST['cartid']) ? htmlspecialchars(trim($_POST['cartid'])) : '';
        $avatar = isset($_POST['avatar']) ? htmlspecialchars(trim($_POST['avatar'])) : '';
        $name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : '';
        $index = isset($_POST['index']) ? htmlspecialchars(trim($_POST['index'])) : '';
        $copyindex = isset($_POST['copyindex']) ? htmlspecialchars(trim($_POST['copyindex'])) : '';
        if ($copyindex) {
            $copyindex = is_numeric($copyindex) ? 'index_' . $copyindex : $copyindex;
        }
        $orderTemp = D('Shop_order_temp')->where(['cartid' => $cartid, 'store_id' => $storeId])->find();
        $oldCartGoods = json_decode($orderTemp['info'], true);
        $oldCartGoods = $oldCartGoods ?: [];
        if ($uniquenessNumber) {
            //增减
            $oldCartGoods = json_decode($orderTemp['info'], true);
            if ($index <= 0) {
                $this->returnCode(1, null, L_('分单打包序号参数有误'));
            }
            $oldCartGoodsIndex = isset($oldCartGoods['index_' . $index]) ? $oldCartGoods['index_' . $index] : [];
            foreach ($oldCartGoodsIndex['data'] as $k=>$g) {
                if ($g['uniqueness_number'] == $uniquenessNumber) {
                    if($number == 0){
                        unset($oldCartGoodsIndex['data'][$k]);
                    }else{
                        $oldCartGoodsIndex['data'][$k]['count'] = $number;
                    }
                    break;
                }
            }
            $productCart = array_values($oldCartGoodsIndex['data']);
        } else {
            $productCart = $_POST['product'];
            //新商品
            $oldCartGoods = json_decode($orderTemp['info'], true);
            if ($index <= 0) {
                $this->returnCode(1, null, L_('分单打包序号参数有误'));
            }
            $oldCartGoodsIndex = isset($oldCartGoods['index_' . $index]) ? $oldCartGoods['index_' . $index] : [];
            if (!empty($oldCartGoodsIndex['data'])) {
                $insertUniquenessNumber = $productCart[0]['uniqueness_number'];
                $newFlag = true;
                foreach ($oldCartGoodsIndex['data'] as &$g) {
                    if ($g['uniqueness_number'] == $insertUniquenessNumber) {
                        $g['count'] = $g['count'] + 1;
                        $newFlag = false;
                        break;
                    }
                }
                $newFlag && $oldCartGoodsIndex['data'][] = $productCart[0];
                $productCart = $oldCartGoodsIndex['data'];
            }
        }

        $shop = D('Merchant_store')->getStoreInfoById($storeId);
        if ($shop['have_shop'] == 0 || $shop['status'] != 1) {
            $this->returnCode(1, null, L_('商家已经关闭了该业务,不能下单了!'));
        }
        if (C('config.store_shop_auth') == 1 && $shop['auth'] < 3) {
            $this->returnCode(1, null, L_('您查看的X1没有通过资质审核！', C('config.shop_alias_name')));
        }
        $store_image_class = new store_image();
        $images = $store_image_class->get_allImage_by_path($shop['pic_info']);
        $shop['images'] = $images ? array_shift($images) : '';


        $orderTemp = D('Shop_order_temp')->where(['cartid' => $cartid, 'store_id' => $storeId])->find();

        if (empty($index)) {
            $index = $orderTemp ? $orderTemp['max_index'] + 1 : 1;
        }
        is_numeric($index) && $index = max(1, $index);

        if (empty($cartid)) {
            $this->returnCode(1, null, L_('拼单ID不存在!'));
        }
        $cartData = $productCart;
        if ($cartData) {
            $shopGoodsDB = D('Shop_goods');
            $resultGoods = D('Shop_order_temp')->getGoods($cartid, $storeId, $index);
            foreach ($cartData as &$row) {
                $goods_id = $row['productId'];
                $num = $row['count'];
                if ($num < 1) continue;
                $spec_ids = array();
                $str_s = array();
                $str_p = array();
                foreach ($row['productParam'] as $r) {
                    if ($r['type'] == 'spec') {
                        $spec_ids[] = $r['id'];
                        $str_s[] = $r['name'];
                    } else {
                        foreach ($r['data'] as $d) {
                            $str_p[] = $d['name'];
                        }
                    }
                }
                if (isset($resultGoods[$goods_id])) {
                    $num += $resultGoods[$goods_id]['count'];
                }
                $spec_str = $spec_ids ? implode('_', $spec_ids) : '';
                $t_return = $shopGoodsDB->check_stock($goods_id, $num, $spec_str, $shop['stock_type'], $storeId, false, $this->_uid, '', '', '', '', $row['host_goods_id']);
                if ($t_return['status'] != 1) {
                    $this->returnCode(1, null, $t_return['msg']);
                }
                $row['productPrice'] = floatval($t_return['price']);
                $row['oldPrice'] = floatval($t_return['old_price']);
                $row['productPackCharge'] = floatval($t_return['packing_charge']);
                $row['maxNum'] = $t_return['maxNum'];
                $row['limit_type'] = $t_return['limit_type'];
            }
        }

        $data = array('cartid' => $cartid, 'store_id' => $storeId);
        $info = array();
        $info['index'] = $index;
        $info['from'] = $from;
        $info['data'] = $cartData;
        $info['name'] = $name ?: L_('X1号订购人', $index);
        $info['avatar'] = $avatar;
        $info['uid'] = $this->_uid;

        $arrIndex = is_numeric($index) ? 'index_' . $index : $index;
        if ($orderTemp) {
            $data = array();
            if (is_numeric($index) && intval($orderTemp['max_index']) < $index) {
                $data['max_index'] = $index;
            }
            $orderTemp['info'] = json_decode($orderTemp['info'], true);

            if (isset($orderTemp['info'][$arrIndex])) {
                if (empty($name) && $orderTemp['info'][$arrIndex]['name']) {
                    $info['name'] = $orderTemp['info'][$arrIndex]['name'];
                }
                if (empty($avatar) && $orderTemp['info'][$arrIndex]['avatar']) {
                    $info['avatar'] = $orderTemp['info'][$arrIndex]['avatar'];
                }
            }

            if ($copyindex && isset($orderTemp['info'][$copyindex]['data']) && $orderTemp['info'][$copyindex]['data']) {
                $info['data'] = $orderTemp['info'][$copyindex]['data'];
            }
            if ($info['data']) {
                foreach ($info['data'] as &$nowData) {
                    $nowData['packname'] = $info['name'];
                }
            }
            $orderTemp['info'][$arrIndex] = $info;
            $data['info'] = json_encode($orderTemp['info']);
            D('Shop_order_temp')->where(['cartid' => $cartid, 'store_id' => $storeId])->save($data);
        }
        $_POST['spellProduct'] = $info['data'];
        $data = array(
            'name' => $info['name'],
            'avatar' => $avatar,
            'cartid' => $cartid,
            'index' => $index,
            'share_url' => str_replace('appapi.php', 'wap.php', U('Wap/Shop/spell', ['cartid' => $cartid, 'store_id' => $storeId], true, false, true))
        );
        $data = array_merge($data,$this->getCartProduct());
        $this->returnCode(0, $data);
    }

    public function spellDetail()
    {
        $uid = $this->_uid;
        $storeId = $_POST['store_id'];
        $cartid = isset($_POST['cartid']) ? htmlspecialchars(trim($_POST['cartid'])) : '';
        $index = isset($_POST['index']) ? htmlspecialchars(trim($_POST['index'])) : '';

        $orderTemp = D('Shop_order_temp')->where(['cartid' => $cartid, 'store_id' => $storeId])->find();
        if (empty($orderTemp)) {
            $this->returnCode(1, null, L_('拼单不存在'));
        }
        $oldCartGoods = json_decode($orderTemp['info'], true);
        if ($index <= 0) {
            $this->returnCode(1, null, L_('分单打包序号参数有误'));
        }
        $oldCartGoodsIndex = isset($oldCartGoods['index_' . $index]) ? $oldCartGoods['index_' . $index] : [];
        $_POST['spellProduct'] = $oldCartGoodsIndex?$oldCartGoodsIndex['data']:[];
        $data = array(
            'name' => $oldCartGoodsIndex ? $oldCartGoodsIndex['name'] : '',
            'avatar' => "",
            'cartid' => $cartid,
            'index' => $index,
            'share_url' => str_replace('appapi.php', 'wap.php', U('Wap/Shop/spell', ['cartid' => $cartid, 'store_id' => $storeId], true, false, true))
        );
        $data = array_merge($data,$this->getCartProduct());
        $this->returnCode(0, $data);
    }

    public function clearSpellProduct()
    {
        $uid = $this->_uid;
        $storeId = $_POST['store_id'];
        $cartid = isset($_POST['cartid']) ? htmlspecialchars(trim($_POST['cartid'])) : '';
        $index = isset($_POST['index']) ? htmlspecialchars(trim($_POST['index'])) : '';

        $orderTemp = D('Shop_order_temp')->where(['cartid' => $cartid, 'store_id' => $storeId])->find();
        if (empty($orderTemp)) {
            $this->returnCode(1, null, L_('拼单不存在'));
        }
        if (empty($index)) {
            D('Shop_order_temp')->where(['id' => $orderTemp['id']])->delete();
            $this->returnCode(0, []);
        }
        $oldCartGoods = json_decode($orderTemp['info'], true);
        if ($index <= 0) {
            $this->returnCode(1, null, L_('分单打包序号参数有误'));
        }
        if (!isset($oldCartGoods['index_' . $index])) {
            $this->returnCode(1, null, L_('分单打包序号参数有误'));
        }
        $oldCartGoods['index_' . $index]['data'] = [];
        $info = json_encode($oldCartGoods);
        D('Shop_order_temp')->where(['id' => $orderTemp['id']])->save(['info' => $info]);

        $_POST['spellProduct'] = [];
        $data = array(
            'name' => $oldCartGoods['index_' . $index]['name'],
            'avatar' => "",
            'cartid' => $cartid,
            'index' => $index,
            'share_url' => str_replace('appapi.php', 'wap.php', U('Wap/Shop/spell', ['cartid' => $cartid, 'store_id' => $storeId], true, false, true))
        );
        $data = array_merge($data, $this->getCartProduct());
        $this->returnCode(0, $data);
    }

    /**
     * 生成拼单数据
     */
    public function addNew()
    {
        if (empty($_POST)) {
            $input_post = file_get_contents('php://input');
            $_POST = json_decode($input_post, true);
        }
        $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $cartid = isset($_POST['cartid']) ? htmlspecialchars(trim($_POST['cartid'])) : '';
        $from = isset($_POST['from']) ? intval($_POST['from']) : 0;
        $cartData= $_POST['productCart'];
        if (!is_array($cartData)) {
            $cartData = json_decode(htmlspecialchars_decode($cartData), true);
        }

        $where = array('cartid' => $cartid, 'store_id' => $store_id);
        $orderTemp = D('Shop_order_temp')->field(true)->where($where)->find();
        $shop = D('Merchant_store')->getStoreInfoById($store_id);

        if (empty($index) && $orderTemp) {
            $index = $orderTemp['max_index'] + 1;
        } elseif (empty($index)) {
            $index = 1;
        }
        is_numeric($index) && $index = max(1, $index);

        if (empty($cartid)) {
            $cartid = md5($store_id . uniqid() . date('YmdHis'));
        }

        if ($cartData) {
            $shopGoodsDB = D('Shop_goods');
            $resultGoods = D('Shop_order_temp')->getGoods($cartid, $store_id, $index);
            foreach ($cartData as &$row) {
                $goods_id = $row['productId'];
                $num = $row['count'];
                if ($num < 1) continue;
                $spec_ids = array();
                $str_s = array(); $str_p = array();
                foreach ($row['productParam'] as $r) {
                    if ($r['type'] == 'spec') {
                        $spec_ids[] = $r['id'];
                        $str_s[] = $r['name'];
                    } else {
                        foreach ($r['data'] as $d) {
                            $str_p[] = $d['name'];
                        }
                    }
                }
                if (isset($resultGoods[$goods_id])) {
                    $num += $resultGoods[$goods_id]['count'];
                }
                $spec_str = $spec_ids ? implode('_', $spec_ids) : '';
                $t_return = $shopGoodsDB->check_stock($goods_id, $num, $spec_str, $shop['stock_type'], $store_id, false, $this->_uid, '', '', '', '', $row['host_goods_id']);
                if ($t_return['status'] != 1) {
                    $this->returnCode(1, null, $t_return['msg']);
                }
                $row['productPrice'] = floatval($t_return['price']);
                $row['oldPrice'] = floatval($t_return['old_price']);
                $row['productPackCharge'] = floatval($t_return['packing_charge']);
                $row['maxNum'] = $t_return['maxNum'];
                $row['limit_type'] = $t_return['limit_type'];
            }
        }

        $info = array();
        $info['index'] = $index;
        $info['from'] = $from;
        $info['data'] = $cartData ?: [];
        $info['name'] = L_('X1号订购人', $index);
        $info['avatar'] = "";
        $info['uid'] = $this->_uid;

        $arrIndex = is_numeric($index) ? 'index_' . $index : $index;
        if ($orderTemp) {
            $data = array();
            if (is_numeric($index) && intval($orderTemp['max_index']) < $index) {
                $data['max_index'] = $index;
            }
            $orderTemp['info'] = json_decode($orderTemp['info'], true);

            if (isset($orderTemp['info'][$arrIndex])) {
                if (empty($name) && $orderTemp['info'][$arrIndex]['name']) {
                    $info['name'] = $orderTemp['info'][$arrIndex]['name'];
                }
                if (empty($avatar) && $orderTemp['info'][$arrIndex]['avatar']) {
                    $info['avatar'] = $orderTemp['info'][$arrIndex]['avatar'];
                }
            }
            if ($info['data']) {
                foreach ($info['data'] as &$nowData) {
                    $nowData['packname'] = $info['name'];
                }
            }
            $orderTemp['info'][$arrIndex] = $info;
            $data['info'] = json_encode($orderTemp['info']);
            D('Shop_order_temp')->where($where)->save($data);
        } else {
            if ($info['data']) {
                foreach ($info['data'] as &$nowData) {
                    $nowData['packname'] = $info['name'];
                }
            }
            $data['info'] = json_encode(array($arrIndex => $info));
            $data['max_index'] = 1;
            D('Shop_order_temp')->add($data);
        }

        $data = array(
            'name' => $info['name'],
            'avatar' => "",
            'cartid' => $cartid,
            'index' => $index,
            'share_url' => str_replace('appapi.php', 'wap.php', U('Wap/Shop/spell', ['cartid' => $cartid, 'store_id' => $store_id], true, false, true)),
        );
        $this->returnCode(0, $data);
    }

    /**
     * 外卖订单-再来一单-添加购物车
     */
    public function addOrderAgain()
    {
        $order_id = $_POST['order_id'] ?? 0;
        $uid      = $this->_uid;
        $cartMod  = M('Cart');
        if (empty($order_id)) {
            $this->returnCode(1, null, L_('订单不存在'));
        }
        $now_order = D('Shop_order')->get_order_detail(array('order_id' => $order_id, 'uid' => $uid));
        $storeId   = $now_order['store_id'];
        $cartMod->startTrans();
        try {
            $product       = [];
            foreach ($now_order['info'] as $iv) {
                $product[] = [
                    'count'             => 1,
                    'num'               => $iv['num'],
                    'host_goods_id'     => $iv['host_goods_id'],
                    'productId'         => $iv['goods_id'],
                    'productName'       => $iv['name'],
                    'productPrice'      => $iv['price'],
                    'uniqueness_number' => $iv['uniqueness_number']
                ];
            }
            foreach ($product as $v) {
                if ($v['host_goods_id'] > 0) {
                    continue;
                }
                $isShopGoods = (new Shop_goodsModel())->isShopOrMallGoods($v['productId'], true);
                if (!$isShopGoods) {
                    $this->returnCodeError(L_('该商品只能在' . C('config.mall_alias_name') . '购买'));
                }
                if (empty($v['uniqueness_number'])) {
                    $this->returnCode(77000002, array());
                }
                $old = $cartMod->where(['uid' => $uid, 'store_id' => $storeId, 'uniqueness_number' => $v['uniqueness_number']])->find();
                if ($old) {
                    $cartMod->where(['id' => $old['id']])->setInc('num', $v['num']);
                } else {
                    $data = [
                        'uid' => $uid,
                        'store_id' => $storeId,
                        'goods_id' => $v['productId'],
                        'uniqueness_number' => $v['uniqueness_number'],
                        'info' => json_encode([$v], JSON_UNESCAPED_UNICODE),
                        'num' => $v['num']
                    ];
                    $cartMod->add($data);
                }
                D('Shop_goods')->checkGoodsMaxCountInCart($uid, $storeId, $v['productId']);
            }
            $cartMod->commit();
            $this->returnCode(0);
        } catch (\Exception $e) {
            $cartMod->rollback();
            $this->returnCode(1, array(),$e->getMessage());
        }
    }

}
