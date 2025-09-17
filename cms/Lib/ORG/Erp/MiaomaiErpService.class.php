<?php
require LIB_PATH . 'ORG/Erp/Type/Miaomaiyun.class.php';

class MiaomaiErpService
{
    public static function getInstance()
    {
        return (new Miaomaiyun());
    }

    public static function getErpConfigByMerchantId($merchantId)
    {
        $merchant = D('Merchant')->where(['mer_id' => $merchantId])->find();
        if(empty($merchant['miaomaiyun_switch'])){
            throw_exception('商家未开启淼迈ERP');
        }
        if(
            empty($merchant['miaomaiyun_erp_appid']) ||
            empty($merchant['miaomaiyun_erp_appsecret']) ||
            empty($merchant['miaomaiyun_url'])
        ){
            throw_exception('商家未配置淼迈ERP');
        }
        return [
            'miaomaiyun_erp_appid' => $merchant['miaomaiyun_erp_appid'],
            'miaomaiyun_erp_appsecret' => $merchant['miaomaiyun_erp_appsecret'],
            'miaomaiyun_url' => $merchant['miaomaiyun_url'],
            'miaomaiyun_erp_pt_user_id' => 99,
        ];
    }
    
    public static function syncErpGoodsToSystem($merId, $storeId, $page)
    {
        $config = MiaomaiErpService::getErpConfigByMerchantId($merId);
        $store = D('Merchant_store')->where(['store_id' => $storeId])->find();
        //获取商品基本信息
        $erpGoods = (MiaomaiErpService::getInstance())
            ->setConfig($config['miaomaiyun_erp_appid'], $config['miaomaiyun_erp_appsecret'], $config['miaomaiyun_url'])
            ->setParam('stores_id', $store['miaomaiyun_erp_storeid'])
            ->setParam('page', $page - 1)
            ->setParam('spec_id',1)
            ->setParam('limit', 50)
            ->run(Miaomaiyun::GET_GOODS_ALL);
        
        $tm = time();
        $erpGoods = $erpGoods['data'];
        if (empty($erpGoods)) {
            return false;
        }
        try{
            foreach ($erpGoods as $goodsDetail) {
                $goods = D('Miaomai_goods')->where(['store_id' => $storeId, 'goods_id' => $goodsDetail['goods_id']])->find();
                $data = [
                    'mer_id' => $merId,
                    'store_id' => $storeId,
                    'goods_id' => $goodsDetail['goods_id'],
                    'only_coding' => $goodsDetail['only_coding'],
                    'barcode_id' => $goodsDetail['barcode_id'],
                    'goods_info' => json_encode($goodsDetail, JSON_UNESCAPED_UNICODE),
                ];
                if(empty($goods)){
                    D('Miaomai_goods')->add($data);
                }else{
                    D('Miaomai_goods')->where(['store_id' => $storeId, 'goods_id' => $goodsDetail['goods_id']])->save($data);
                }
                $sortId = D('Shop_goods_sort')
                    ->where(['store_id' => $storeId, 'miaomai_category_id' => $goodsDetail['category_id']])
                    ->getField('sort_id');
                if(empty($sortId)){
                    D('Shop_goods_sort')->syncMiaomaiCategory($storeId, [
                        'category_name' => $goodsDetail['category_name'],
                        'category_id' => $goodsDetail['category_id'],
                        'depth' => 1,
                    ]);
                }
                //保存商品基本信息
                $goodsMod = D('Shop_goods');
                $goodsData = [
                    'sort_id' => $sortId ?? 0,
                    'store_id' => $storeId,
                    'name' => $goodsDetail['goods_title'],
                    'describe' => '',
                    'unit' => $goodsDetail['unit_name'],
                    'old_price' => $goodsDetail['retail_price'],
                    'price' => $goodsDetail['trade_price'],
                    'image' => $goodsDetail['img_url'],
                    'last_time' => $tm,
                    'status' => ($goodsDetail['is_sales'] && $goodsDetail['goods_status'] == 1) ? 1 : 0,
                    'sort' => $goodsDetail['sort'] ?? 0,
                    'sell_type' => $goodsDetail['spec_id'] == 1 ? 1 : 2,
                    'miaomaiyun_barcode_id' => $goodsDetail['barcode_id']
                ];
    
                //获取库存
                $stockRs = self::getInstance()
                    ->clearParam()
                    ->setConfig($config['miaomaiyun_erp_appid'], $config['miaomaiyun_erp_appsecret'], $config['miaomaiyun_url'])
                    ->setParam('stores_id', $store['miaomaiyun_erp_storeid'])
                    ->setParam('xtype', 'only_coding')
                    ->setParam('keyword', $goodsDetail['only_coding'])
                    ->run('/api_v2/stock_query/xlist');
                
                $stock = $stockRs['data'][0]['stock_num'] ?? 0;
                $goodsData['stock_num'] = $stock;
                $stock < -1 && $goodsData['status'] = 0;
    
                $old = $goodsMod->where(['miaomaiyun_barcode_id' => $goodsDetail['barcode_id']])->find();
                if ($old) {
                    $goodsId = $old['goods_id'];
                    $goodsMod->where(['goods_id' => $goodsId])->save($goodsData);
                } else {
                    $goodsId = $goodsMod->add($goodsData);
                }
                fdump_api([$goodsMod->_sql()], 'job/miaomai_goods_sync_goods_data', 1);
                
                $syncRows = (int)S('miaomai_goods_already_sync_goods_total_rows_'.$storeId);
                S('miaomai_goods_already_sync_goods_total_rows_'.$storeId, $syncRows + 1);
            
//                //获取多规格信息
//                $specMod = D('Shop_goods_spec');
//                $specValMod = D('Shop_goods_spec_value');
//                
//                $goodsSpec = [];
//                
//                $split = array_map(function ($i) {
//                    $i = trim($i, "[,]");
//                    $i = explode(':', $i);
//                    return count($i) == 2 ? $i : [];
//                }, array_filter(explode(' ', $goodsDetail['specifi'])));
//                foreach ($split as $s) {
//                    $goodsSpec[$s[0]][] = $s[1];
//                    $goodsSpec[$s[0]] = array_unique($goodsSpec[$s[0]]);
//                }
//                
//                if ($goodsSpec) {
//                    $newspecIds = [];
//                    foreach ($goodsSpec as $specName => $specVals) {
//                        $specData = [
//                            'store_id' => $storeId,
//                            'goods_id' => $goodsId,
//                            'name' => $specName
//                        ];
//                        $oldSpec = $specMod->where($specData)->find();
//                        if ($oldSpec) {
//                            $specId = $oldSpec['id'];
//                        } else {
//                            $specId = $specMod->add($specData);
//                        }
//                        $newspecIds[] = $specId;
//    
//                        $newSpecValIds = [];
//                        foreach ($specVals as $val) {
//                            $specValData = [
//                                'sid' => $specId,
//                                'name' => $val,
//                            ];
//                            $oldSpecVal = $specValMod->where($specValData)->find();
//                            if ($oldSpecVal) {
//                                $newSpecValIds[] = $oldSpecVal['id'];
//                            } else {
//                                $newSpecValIds[] = $specValMod->add($specValData);
//                            }
//                        }
//                        $specValMod->where(['sid' => $specId, 'id' => ['NOT IN', $newSpecValIds]])->delete();
//                    }
//                    $specMod->where(['goods_id' => $goodsId, 'id' => ['NOT IN', $newspecIds]])->delete();
//    
//                    //更新spec_value字段
//                    $specString = '';
//                    
//                    $specifi = $goodsDetail['specifi'];
//                    if ($specifi) {
//                        $thisSpecInfo = array_map(function ($i) {
//                            $i = trim($i, "[,]");
//                            $i = explode(':', $i);
//                            return count($i) == 2 ? $i : [];
//                        }, array_filter(explode(' ', $specifi)));
//                        $specValId = [];
//                        foreach ($thisSpecInfo as $v) {
//                            $specValId[] = $specMod->alias('s')->join(C('DB_PREFIX') . 'shop_goods_spec_value as v ON s.id=v.sid')
//                                ->where(['s.goods_id' => $goodsId, 's.store_id' => $storeId, 's.name' => $v[0], 'v.name' => $v[1]])
//                                ->getField('v.id');
//                        }
//                        if ($specString) {
//                            $specString .= '#' . implode($specValId, ':') . '|' . $goodsDetail['retail_price'] . ':' . $goodsDetail['retail_price'] . '::' . $stock . ':0::' . '||' . $goodsDetail['barcode'] . '||';
//                        } else {
//                            $specString .= implode($specValId, ':') . '|' . $goodsDetail['retail_price'] . ':' . $goodsDetail['retail_price'] . '::' . $stock . ':0::' . '||' . $goodsDetail['barcode'] . '||';
//                        }
//                    }
//                    
//                    if ($specString) {
//                        $goodsMod->where(['goods_id' => $goodsId])->save(['spec_value' => $specString]);
//                    }
//                }
            }
        }catch (\Exception $e){
            fdump_api(['error'=>$e->getMessage()],'job/MiaomaiSyncJob_error', 1);
        }
        return true;
    }
    //现金抵用券-会员券列表
    public static function getMiaomaiCouponList($phone, $page = 1, $params=[])
    {
        $config = D('Merchant')
            ->alias('m')
            ->join('INNER JOIN ' . C("DB_PREFIX") . 'merchant_store s', 'm.mer_id = s.mer_id')
            ->where([
                's.miaomaiyun_erp_storeid' => $params['store_id'],
            ])
            ->field('m.miaomaiyun_erp_appid, m.miaomaiyun_erp_appsecret, m.miaomai_url')
            ->find();
        $data = [
            'member_mark' => $phone,
            'page' => $page - 1,
            'stores_id' => $params['store_id'],
        ];
        !empty($params['is_expired']) && $data['overdue'] = $params['is_expired'];
        !empty($params['use_status']) && $data['use_status'] = $params['use_status'];

        $erpCouponList = (MiaomaiErpService::getInstance())
            ->setConfig($config['miaomaiyun_erp_appid'], $config['miaomaiyun_erp_appsecret'], $config['miaomai_url'])
            ->setParams($data)
            ->run(Miaomaiyun::GET_COUPON_LIST);

        return $erpCouponList;
    }
}