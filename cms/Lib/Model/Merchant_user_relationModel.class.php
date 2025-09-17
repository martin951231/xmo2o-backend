<?php
class Merchant_user_relationModel extends Model{
    public function get_collect_num($uid,$openid=''){
        $type = array('appoint_detail','group_detail','group_shop','meal_detail','merchant_id');
        $now_time = time();
        // 预约
        $res['appoint_count'] = D('')->table(array(C('DB_PREFIX').'appoint'=>'a', C('DB_PREFIX').'merchant_user_relation'=>'ac'))->where("ac.uid= '".$uid."' AND ac.appoint_id = a.appoint_id AND `a`.`start_time`<'$now_time' AND `a`.`end_time`>'$now_time'")->count();

        $now_time = time();
        //团购收藏
        $condition_where = "`g`.`mer_id`=`m`.`mer_id` AND `m`.`status`='1' AND `g`.`status`='1' AND `g`.`type`='1' AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time' AND `g`.`group_id`=`c`.`group_id` AND `c`.`uid`='$uid' AND `c`.`type`='group'";
        $condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'merchant_user_relation'=>'c');
        $res['group_count'] = D('')->table($condition_table)->where($condition_where)->count();
    
        //餐饮收藏
        $res['meal_count'] = D('')->table(array(C('DB_PREFIX').'merchant_store'=>'ms', C('DB_PREFIX').'merchant_user_relation'=>'msc',C('DB_PREFIX').'merchant_store_foodshop'=>'msf',C('DB_PREFIX').'merchant'=>'m'))->where("msc.uid= '".$uid."' AND ms.store_id = msc.store_id AND m.mer_id = ms.mer_id AND msf.store_id = msc.store_id AND m.status=1 AND ms.have_meal=1 AND ms.status=1 AND ms.foodshop_on_sale=1 AND msc.type = 'foodshop'")->count();
        
        //商家收藏
        if($openid){
            $sql = "SELECT b.* FROM ". C('DB_PREFIX') . "merchant_user_relation AS a INNER JOIN  ". C('DB_PREFIX') . "merchant as b ON a.mer_id=b.mer_id WHERE a.openid='$openid'";
            $tmp_res = M('')->query($sql);
            $res['merchant_count'] = count($tmp_res);
        }else{
            $res['merchant_count'] = 0;
        }

        //商城收藏
        // $res['mall_count'] = D('')->table(array(C('DB_PREFIX').'merchant_store'=>'ms', C('DB_PREFIX').'merchant_user_relation'=>'msc',C('DB_PREFIX').'merchant_store_shop'=>'msf'))->where("msc.uid= '".$uid."' AND ms.store_id = msc.store_id AND msf.store_id = msc.store_id AND msc.type = 'mall'")->count();

        // $res['mallgoods_count'] = D('')->table(array(C('DB_PREFIX').'shop_goods'=>'s', C('DB_PREFIX').'merchant_user_relation'=>'msc'))->where("msc.uid= '".$uid."' AND s.goods_id = msc.goods_id AND msc.type = 'mallgoods'")->count();

        $res['mall_count'] =  D('')->table(array(C('DB_PREFIX').'merchant_store'=>'ms', C('DB_PREFIX').'merchant_store_collection'=>'msc',C('DB_PREFIX').'merchant_store_shop'=>'msf'))->where("msc.uid= '".$uid."' AND ms.store_id = msc.store_id AND msf.store_id = msc.store_id AND ms.have_shop=1 AND msf.store_theme=1 AND ms.shop_on_sale=1 AND ms.auth>2")->count();
        $res['mallgoods_count'] = M('Mall_user_collect')->join(' as msc join '.C('DB_PREFIX').'mall_goods as g on g.goods_id=msc.goods_id')->where(['uid'=>$uid, 'is_collect' => 1, 'is_del' => 0])->count();
       

        // 外卖
        $res['shop_count'] = D('')->table(array(C('DB_PREFIX').'merchant_store'=>'ms', C('DB_PREFIX').'merchant_user_relation'=>'msc',C('DB_PREFIX').'merchant_store_shop'=>'msf'))->where("msc.uid= '".$uid."' AND ms.store_id = msc.store_id AND msf.store_id = msc.store_id AND msc.type = 'shop' AND ms.status=1 AND ms.have_shop=1 AND ms.shop_on_sale=1")->count();

        
        return $res;
    }

    // 获得用户团购收藏详情
    public function get_group_collect($uid,$group_id){
        $detail = $this->where(array('uid'=>$uid,'group_id'=>$group_id,'type'=>'group'))->find();
        return $detail ? $detail : array();
    }

    // 获得用户店铺收藏详情
    public function get_store_collect($uid,$store_id){
        $detail = $this->where(array('uid'=>$uid,'store_id'=>$store_id,'type'=>'store'))->find();
        return $detail ? $detail : array();
    }

    // 获得用户餐饮店铺收藏详情
    public function get_foodshop_collect($uid,$store_id){
        $detail = $this->where(array('uid'=>$uid,'store_id'=>$store_id,'type'=>'foodshop'))->find();
        return $detail ? $detail : array();
    }

    // 获得用户预约收藏详情
    public function get_appoint_collect($uid,$appoint_id){
        $detail = $this->where(array('uid'=>$uid,'appoint_id'=>$appoint_id,'type'=>'appoint'))->find();
        return $detail ? $detail : array();
    }

    // 获得用户快店收藏详情
    public function get_shop_collect($uid,$store_id){
        $detail = $this->where(array('uid'=>$uid,'store_id'=>$store_id,'type'=>'shop'))->find();
        return $detail ? $detail : array();
    }

    // 获得用户商城收藏详情
    public function get_mall_collect($uid,$store_id){
        $detail = $this->where(array('uid'=>$uid,'store_id'=>$store_id,'type'=>'mall'))->find();
        return $detail ? $detail : array();
    }
    // 获得用户商城收藏详情
    public function get_mallgoods_collect($uid,$goods_id){
        $detail = $this->where(array('uid'=>$uid,'goods_id'=>$goods_id,'type'=>'mallgoods'))->find();
        return $detail ? $detail : array();
    }

    // 获得用户商城收藏详情
    public function getCollectList($uid,$type){
        $collectList = $this->where(array('uid'=>$uid,'type'=>$type))->select();
        if ($type=='mall') {
            $collectList = M('Merchant_store_collection')->where(array('uid'=>$uid))->select();
            // $mallgoodsList = $this->where(array('uid'=>$uid,'type'=>'mallgoods'))->select();
            $mallgoodsList = M('Mall_user_collect')->where(['uid'=>$uid, 'is_collect' => 1, 'is_del' => 0])->order('create_time desc')->select();
            if (!$collectList && !$mallgoodsList) {
                return array('storeList'=>[],'goodsList'=>[]);
            }
        }

        $returnArr = [];
        switch ($type) {
            case 'store':
                $where['uid'] = $uid;
                $return = D('Merchant_store')->get_store_collect_list($where);
                $returnArr = $return['store_list'];
                break;
            case 'foodshop':
                if($collectList){
                    $storeIdArray = array_column($collectList, 'store_id');
                    $where['store_id'] = $storeIdArray;
                    $where['queue'] = -1;
                    $where['limit'] = 1000;
                    $return = D('Merchant_store_foodshop')->wap_get_storeList_by_catid($where);
                    $returnArr = $return['store_list'];
                    foreach ($returnArr as &$value) {
                        $value['Currency_txt'] = C('config.Currency_txt');
                    }

                }
                break;
            case 'shop':
                if($collectList){
                    $storeIdArray = array_column($collectList, 'store_id');
                    $_POST['store_ids'] = $storeIdArray;
                    $_POST['data_is_return'] = true;
                    $_POST['type_url'] = 'skip';
                    $_POST['limit'] = 1000;
                    $return = R('Appapi/Shop_new/ajax_list');
                    $returnArr = $return['store_list'];
                }
                break;
               
            case 'mall':
                // 店铺
                $storeIdArray = array_column($collectList, 'store_id');
                $where['store_id'] = $storeIdArray;
                $where['limit'] = 1000;
                $return = D('Merchant_store_shop')->get_store_by_search($where,2,1000);
                $returnArr['storeList'] = $return['store_list'];

                // 商品
    //             $goodsIdArray = array_column($mallgoodsList, 'goods_id');
                // if($goodsIdArray){
                //  $whereGoods['goods_ids'] = $goodsIdArray;
                //  $whereGoods['page_size'] = 1000;
                //  $return = D('Shop_goods')->get_list_by_option($whereGoods, 1, 1,1,2);
                //  $returnArr['goodsList'] = $return['goods_list'];
                // }else{
                //  $returnArr['goodsList'] = [];
                // }
                $goodsIdArray = array_column($mallgoodsList, 'goods_id');
                if($goodsIdArray){
                    $returnArr['goodsList'] = [];
                    $whereGoods['goods_id'] = ['in', $goodsIdArray];
                    $data = M('Mall_goods')->where($whereGoods)->select();
                    foreach ($data as $mgoods) {
                        $returnArr['goodsList'][] = [
                            'image' => replace_file_domain($mgoods['image']),
                            'name' => $mgoods['name'],
                            'ismul' => $mgoods['goods_type'] == 'sku' ? 1 : 0,
                            'price' => getFormatNumber($mgoods['min_price']),
                            'sell_count' => $mgoods['sale_num'],
                            'url' => C("config.site_url").D('House_village')->base_url. 'pages/shopmall_third/commodity_details?goods_id='.$mgoods['goods_id'],
                        ];
                    }
                }
                
                break;
            case 'group':
                if($collectList){
                    $groupIdArray = array_column($collectList, 'group_id');
                    $where['group_id'] = $groupIdArray;
                    $returnArr = D('Group')->get_group_list('new',1000,true,0,$groupIdArray);
                }
                break;
            case 'appoint':
                if($collectList){
                    $_POST['page'] = 1;
                    $appointIdArray = array_column($collectList, 'appoint_id');
                    $return = D('Appoint')->wap_get_appoint_list_by_catid(0,0,0,0,0,0,0, $appointIdArray,1000);
                    $returnArr = $return['group_list'];
                    foreach ($returnArr as &$value) {
                        $value['extra_price_alias_name'] = C('config.extra_price_alias_name');
                        
                        $appointProduct = M('Appoint_product')->where(['appoint_id' => $value['appoint_id']])->select();
                        
                        if(!empty($appointProduct)){
                            if(empty($value['payment_status'])){
                                if(empty($value['appoint_price'])){
                                    $value['appoint_price'] = '面议';
                                }
                            }else{
                                $productPrice = array_column($appointProduct, 'price');
                                $value['appoint_price'] = min($productPrice);
                            }
                        }
                    }
                }
                break;
            case 'mer': 
                break;
        }
        return $returnArr ? $returnArr : array();
    }


}
?>