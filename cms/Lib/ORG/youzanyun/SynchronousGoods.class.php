<?php

require_once APP_PATH . 'Lib/ORG/youzanyun/open-sdk/vendor/autoload.php';
class SynchronousGoods
{
    public $clientId;
    public $clientSecret;
    public $grantId;
    public $storeId;

    public function __construct($clientId, $clientSecret, $grantId, $storeId)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->grantId = $grantId;
        $this->storeId = $storeId;
    }

    /*
     * 批量同步商品分组
     * @param int $storeId 店铺id
     * @param bool $syncGoods 是否同步商品
    */
    public function synchronousGoodsSort($storeId, $syncGoods = true)
    {
        $result = $this->getGoodsGroup();
        if($result['error']){
            return ['error'=>1,'msg'=>$result['msg']];
        }

        $goodsSortList = $result['data'];
        if(empty($goodsSortList)){
            return ['error'=>1,'msg'=>'有赞云商品分组为空'];
        }
        
        $nowGoodsSortList = M('Shop_goods_sort')->where(['store_id'=> $storeId])->field('sort_id,youzanyun_sort_id')->select();
        $nowSortIdArr = array_column($nowGoodsSortList, 'youzanyun_sort_id');
        $nowSortNameArr =  array_column($nowGoodsSortList, 'sort_name', 'youzanyun_sort_id');
        $addData = [];
        $now_time = time();
        foreach($goodsSortList as $sort){
            if(in_array($sort['id'], $nowSortIdArr)){// 已存在更新
                if($nowSortNameArr[$sort['id']] != $sort['name']){// 名称不同则更新
                    $saveData = [
                        'sort_name' => $sort['name'],
                    ];
                    $where = [
                        'youzanyun_sort_id' => $sort['id'],
                    ];
                    M('Shop_goods_sort')->where($where)->save($saveData);
                }
            }else{// 新增
                $addData[] = [
                    'sort_name' => $sort['name'],
                    'store_id' => $storeId,
                    'youzanyun_sort_id' => $sort['id'],
                    'status' => 1,
                ];
            }

            if($syncGoods){// 同步商品 使用子计划任务执行
                $arr= array();
                $arr['param'] = serialize(array('id'=> $sort['id'],'item_num'=>$sort['item_num']));
                $arr['plan_time'] = 0;
                $arr['space_time'] = 0;
                $arr['add_time'] = $now_time;
                $arr['file'] = 'sub_youzanyun_synchronous_goods';
                $arr['time_type'] = 1;
                $arr['unique_id'] = 'sub_synchronous_goods'.$sort['id'];
				$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                M('process_sub_plan')->data($arr)->add();
            }
        }
        M('Shop_goods_sort')->addAll($addData);

        return ['error'=>0,'msg'=>'同步请求成功！'.($syncGoods ? '商品数据同步需要一段时间，请稍后查看' : '')];
    }


    /*
     * 批量同步商品
     * @param int $id 有赞云商品分组id
     * @param int $itemNum 商品数量
    */
    public function synchronousGoodsSave($id, $youzanyunGoodsId)
    {
        // 获得分类信息
        $sortDetail = M('Shop_goods_sort')->where(['youzanyun_sort_id'=>$id])->find();
        if(!$sortDetail){
            return ['error'=>1,'msg'=>'分类不存在'];
        }

        // 查询本系统商品
        $nowGoods = M('Shop_goods')->where(['youzanyun_goods_id' => $youzanyunGoodsId])->find();

        // 获得有赞云商品详情
        $goods = $this->getGoodsDetail($youzanyunGoodsId);

        // 获得有赞云商品活动详情 这个接口收费的客户不让再使用
        // $goodsActivity = $this->getGoodsActivityDetail($youzanyunGoodsId);
        // if($goodsActivity['price_d_t_o']['sku_price_d_t_o_list'] && $goodsActivity['activity_d_t_o_list']){// 活动价格
        //     $priceList = array_column($goodsActivity['price_d_t_o']['sku_price_d_t_o_list'], 'discount_price', 'sku_id');
        // }
        // fdump($goods,'synchronousGoodsSave');
       
        if($nowGoods){// 已存在更新    
            $goodsId = $nowGoods['goods_id'];
            $saveData = [
                'original_stock' => $goods['quantity'],// 库存
                'stock_num' => $goods['quantity'],// 库存
                'price' => $goods['price']/100,// 价格
                'old_price' => $goods['origin_price'] ?? 0,// 原价
                'cost_price' => $goods['cost_price'] ? $goods['cost_price']/100 : 0,// 成本价
                'image' => $goods['pic_url'] ?? '',// 图片
                'name' => $goods['title'] ?? '',// 商品名称
                'number' => $goods['item_no'] ?? '',// 条形码
                'min_num' => $goods['start_sale_num'] ?? 0,// 起售数量
                'max_num' => $goods['buy_quota'] ?? 0,// 限制每人可购买数量。0代表无限购，默认为0
                'des' => $goods['desc'] ?? '',// 详情
                'spec_value' => '',
                'youzanyun_sku_ids' => '',
                'last_time' => time()
            ];

            if($goods['item_channel_extra_open_model']){
                $saveData['unit'] = $goods['item_channel_extra_open_model']['unit'];
                $saveData['sell_type'] = $goods['item_channel_extra_open_model']['pricing_strategy'] == 0 ? 0 : 1;
            }

            $where = [
                'youzanyun_goods_id' => $goods['item_id'],
            ];
            M('Shop_goods')->where($where)->data($saveData)->save();
            
        }else{// 新增
            $addData = [
                'store_id' => $sortDetail['store_id'],// 店铺d
                'sort_id' => $sortDetail['sort_id'],// 分类id
                'original_stock' => $goods['quantity'],// 库存
                'price' => $goods['price']/100,// 价格
                'old_price' => $goods['origin_price'] ?? 0,// 原价
                'cost_price' => $goods['cost_price'] ? $goods['cost_price']/100 : 0,// 成本价
                'image' => $goods['pic_url'] ?? '',// 图片
                'name' => $goods['title'] ?? '',// 商品名称
                'number' => $goods['item_no'] ?? '',// 条形码
                'min_num' => $goods['start_sale_num'] ?? 0,// 起售数量
                'max_num' => $goods['buy_quota'] ?? 0,// 限制每人可购买数量。0代表无限购，默认为0
                'des' => $goods['desc'] ?? '',// 详情
                'youzanyun_goods_id' => $goods['item_id'] ?? '',// 详情
                'spec_value' => '',
                'add_time' => time(),
                'last_time' => time()
            ];

            if($goods['item_channel_extra_open_model']){
                $addData['unit'] = $goods['item_channel_extra_open_model']['unit'];
                $addData['sell_type'] = $goods['item_channel_extra_open_model']['pricing_strategy'] == 0 ? 0 : 1;
            }

            $goodsId = M('Shop_goods')->add($addData);
        }

        if(empty($goodsId)) ['error'=>1,'msg'=>'同步失败'];

        // 最小价格
        $minPrice = $goods['price']/100;

        /* 处理规格 */
        $spec_obj = M('Shop_goods_spec'); //规格表
        $spec_value_obj = M('Shop_goods_spec_value');//规格对应的属性值
        $properties_obj = M('Shop_goods_properties');//属性表

        //查找已有的属性和规格 并删除
        $old_spec = $spec_obj->field(true)->where(array('goods_id' => $goodsId))->select();
        $delete_spec_ids = array_column($old_spec, 'id');
        if ($delete_spec_ids) {
            $spec_obj->field(true)->where(array('goods_id' => $goodsId))->delete();
            $spec_value_obj->field(true)->where(array('sid' => array('in', $delete_spec_ids)))->delete();
        }

        if($goods['skus']){// 多规格

            $spec = array();
            $list = array();
            $dataSpec = array('store_id' => $sortDetail['store_id'], 'goods_id' => $goodsId);

            $specAddList = [];// 已添加的规格id
            $specs = '';
            $pre = '';

            $list = [];
            $skuIdArr = [];// sku_id集合
            foreach ($goods['skus'] as $k => $_sku) {//规格
                
                // 规格详情
                $specList = json_decode($_sku['properties_name_json'], true);

                $index  = []; // 规格值id组合
                foreach ($specList as $_spec) {
                    // 规格信息
                    if(isset($specAddList[$_spec['kid']])){
                        $specId = $specAddList[$_spec['kid']];
                    }else{
                        $spec = [
                            'name' => $_spec['k'],
                            'youzanyun_id' => $_spec['kid'],
                        ];
                        $spec = array_merge($spec, $dataSpec);
                        $specId = $spec_obj->add($spec);
                        $specAddList[$_spec['kid']] = [$specId];
                    }

                    // 规格值信息
                    $specValue = [
                        'name' => $_spec['v'],
                        'sid' => $specId,
                        'youzanyun_id' => $_spec['vid'],
                    ];
                    $specValueId = $spec_value_obj->add($specValue);

                    $index[] = $specValueId;
                }

                if(isset($priceList[$_sku['sku_id']]) && $priceList[$_sku['sku_id']] < $_sku['price']){// 活动价格
                    $_sku['price'] = $priceList[$_sku['sku_id']];
                }

                $minPrice = min($minPrice,$_sku['price']/100);

                // 规格值id组合
                $indexStr = implode(':', $index);

                //规格值ID:规格值ID:...:规格值ID|old_price:price:seckill_price:stock_num:cost_price:max_num:weight:account_money:account_score|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号 # 

                $specs .= $pre . $indexStr . '|' . '' . ':' . ($_sku['price']/100) . ':' .  ($_sku['price']/100) . ':' . $_sku['quantity'] . ':' . $_sku['cost_price']  .  ':' . '0'  . ':' . $_sku['sku_weight']. ':'.'0'.  ':'.'0'. '|';

                $index_ = implode('_', $index);
                $skuIdArr[$index_] = $_sku['sku_id'];


                $specs .= '|' . $_sku['item_no'];
                $specs .= '||' ;
                $pre = '#';
            }

            $saveData = [
                'spec_value' => $specs, 
                'price' => $minPrice, 
                'last_time' => time(),
                'youzanyun_sku_ids' => json_encode($skuIdArr),
            ];
            M('Shop_goods')->where(array('goods_id' => $goodsId))->save($saveData);
        }else{
            // if(isset($priceList[$goods['spu']['spu_id']])){// 活动价格
            //     $saveData['old_price'] = $goods['price'];
            //     $saveData['price'] = $priceList[$goods['spu']['spu_id']];
            //     M('Shop_goods')->where(array('goods_id' => $goodsId))->save($saveData);
            // }
        }
        
        return ['error'=>0,'msg'=>'同步成功'];
    }

    /*
     * 获得商品列表
     * @param int $id 有赞云商品分组id
     * @param int $itemNum 商品数量
    */
    public function synchronousGoodsList($id, $page, $pageSize)
    {
        // 获得分类信息
        $sortDetail = M('Shop_goods_sort')->where(['youzanyun_sort_id'=>$id])->find();
        if(!$sortDetail){
            return ['error'=>1,'msg'=>'分类不存在'];
        }

        // 获取有赞商品数组
        $result = $this->getGoodsList($id, $page, $pageSize);
        if($result['error']){
            return ['error'=>1,'msg'=>$result['msg']];
        }

        $goodsList = $result['data'];
        if(empty($goodsList)){
            return ['error'=>1,'msg'=>'有赞云商品为空'];
        }
       
        return ['error'=>0,'list' => $goodsList];
    }

    /*
     * 查询商品分组
    */
    public function getGoodsGroup()
    {
        $accessToken = $this->getToken();
        if(!$accessToken){
            return ['error'=>1,'msg'=>'获取有赞云token失败'];
        }

        $client = new \Youzan\Open\Client($accessToken);
        // fdump($client, 'YouzanyunService', 1);
        $method = 'youzan.itemcategories.tags.get';
        $apiVersion = '3.0.0';

        $params = [
            // 'node_kdt_id' => $storeId,
            'is_sort' => false,
            // 'node_kdt_id' => 58928915,
            // 'channel' => 0
        ];

        $response = $client->post($method, $apiVersion, $params); 
        if(!isset($response['code']) || $response['code'] != 200){
            return ['error'=>1,'msg'=>'获取有赞云商品分组失败'];
        }
        return ['error'=>0,'data'=>$response['data']['tags']];
    }
    

    /*
     * 查询商品列表
    */
    public function getGoodsList($id, $page, $pageSize)
    {
        $accessToken = $this->getToken();
        if(!$accessToken){
            return ['error'=>1,'msg'=>'获取有赞云token失败'];
        }
        
        $client = new \Youzan\Open\Client($accessToken);
        // fdump($client, 'YouzanyunService', 1);
        $method = 'youzan.showcase.render.api.listGoodsByTagId';
        $apiVersion = '1.0.0';

        $params = [
            // 'node_kdt_id' => $storeId,
            //'is_sort' => false,
            'tag_id' => $id, // 商品分组Id
            'page' => $page,
            'page_size' => $pageSize,
        ];

        // 获取店铺
        $response = $client->post($method, $apiVersion, $params);
        // dump($response);
        if(!isset($response['code']) || $response['code'] != 200){
            return ['error'=>1,'msg'=>'获取有赞云商品失败'];
        }
        return ['error'=>0,'data'=>$response['data']['list']];
    }

    
    /*
     * 查询商品详情
    */
    public function getGoodsDetail($id)
    {
        $accessToken = $this->getToken();
        if(!$accessToken){
            return [];
        }
        
        $client = new \Youzan\Open\Client($accessToken);
        $method = 'youzan.item.get';
        $apiVersion = '3.0.0';

        $params = [
            'item_id' => $id, 
        ];
        $response = $client->post($method, $apiVersion, $params); 
        if(!isset($response['code']) || $response['code'] != 200){
            return [];
        }
        $goods = $response['data']['item'];

        // 查询sku库存信息
        if($this->storeId){// 获得库存
            $method = 'youzan.multistore.goods.sku.get';
            $apiVersion = '3.0.0';
            $params = [
                'num_iid' => $id, 
                'offline_id' => $this->storeId, 
            ];


            $response = $client->post($method, $apiVersion, $params);

            if($response['response']['item']){
                $skuGoods = $response['response']['item'];
                $goods['price'] = $skuGoods['price']*100;
                $goods['quantity'] = $skuGoods['num'];

                if($goods['skus']){
                    $skuStockNum = array_column($skuGoods['skus'], 'quantity', 'sku_id');
                    $skuPrice = array_column($skuGoods['skus'], 'price', 'sku_id');
                    foreach($goods['skus'] as &$_sku){
                        $_sku['quantity'] = $skuStockNum[$_sku['sku_id']];
                        $_sku['price'] = $skuPrice[$_sku['sku_id']]*100;
                    }
                }
            }
        }

        return $goods;
    }

    
    /*
     * 查询商品活动详情
    */
    public function getGoodsActivityDetail($id)
    {
        $accessToken = $this->getToken();
        if(!$accessToken){
            return [];
        }
        
        $client = new \Youzan\Open\Client($accessToken);
        $method = 'youzan.ump.promotion.get';
        $apiVersion = '3.0.0';

        $params = [
            'item_id' => $id, 
        ];

        $response = $client->post($method, $apiVersion, $params);   
        if(!isset($response['code']) || $response['code'] != 200){
            return [];
        }
        return $response['data'];
    }

    /*
     * 更新有赞云商品库存
     * @param int $goodsId
     * @param int $skuId 规格id
     * @param int $quantity 总库存
    */
    public function updateStock($goodsId, $quantity, $skuId = 0, $sku= [])
    {
        $accessToken = $this->getToken();
        if(!$accessToken){
            return ['error'=>1,'msg'=>'获取有赞云token失败'];
        }
        
        $client = new \Youzan\Open\Client($accessToken);

        if(empty($this->storeId)){
            return ['error'=>1,'msg'=>'未设置有赞云store_id'];
        }

        if(empty($this->storeId)){
            // $method = 'youzan.item.quantity.update';
            // $apiVersion = '3.0.0';

            // $params = [
            //     'item_id' => $goodsId, // 商品Id
            //     'quantity' => $quantity,// 库存
            //     'type' => 0,// 库存更新方式，0—全量更新，1—增量更新(可选，默认为0)
            // ];

            // if($skuId){// 更新规格库存
            //     $params['sku_id'] = $skuId;
            // }

            // dump($params);
            // fdump($params, 'updateSkuStock', 1);
            // $response = $client->post($method, $apiVersion, $params); 
            // dump($response);
            // fdump($response, 'updateSkuStock', 1);  
            // if(!isset($response['code']) || $response['code'] != 200){
            //     return ['error'=>1,'msg'=>'更新有赞云商品失败'];
            // }
        }else{
            $method = 'youzan.multistore.goods.sku.get';
            $apiVersion = '3.0.0';
            $params = [
                'num_iid' => $goodsId, 
                'offline_id' => $this->storeId, 
            ];
            $response = $client->post($method, $apiVersion, $params);

            if(!$response['response']['item']){
                return ['error'=>1,'msg'=>'未查询到商品'];
            }

            $skuGoods = $response['response']['item'];
            if($skuGoods['skus']){
                $outerIdArr = array_column($skuGoods['skus'], 'outer_id', 'sku_id');
            }

            $method = 'youzan.multistore.goods.sku.update';
            $apiVersion = '4.0.0';
            $params = [
                'item_no' => $skuGoods['outer_id'], // 商品Id
                'offline_id' => $this->storeId, // 商品Id
                'item_id' => $goodsId, // 商品Id
                'quantity' => $quantity,// 库存
            ];

            if($skuId){
                $sku_with_json = [[
                    'sku_quantity' => $quantity,
                    'sku_price' => $sku['price'],
                    'sku_id' => $skuId,
                    'sku_outer_id' => $outerIdArr[$skuId],
                ]];
                $params['sku_with_json'] = json_encode($sku_with_json);
            }
            // dump($params);

            $response = $client->post($method, $apiVersion, $params); 

            if(!isset($response['code']) || $response['code'] != 200 || !$response['success']){
                return ['error'=>1,'msg'=>'更新有赞云商品失败'];
            }
            // dump($response);
            die;
        }
        return ['error'=>0,'msg'=>'更新有赞云商品成功'];
    }

    /*
     * 授权信息
    */
    public function getToken()
    {
        $resp = (new \Youzan\Open\Token($this->clientId, $this->clientSecret))->getSelfAppToken($this->grantId);
        if(!isset($resp['access_token'])){
            return false;
        }

        if($resp['expires']<time()){
            $resp = (new \Youzan\Open\Token($this->clientId, $this->clientSecret))->refreshToken($resp['access_token']);
        }
        return $resp['access_token'];
    }
}
?>
