<?php


namespace app\merchant\model\service;


use app\group\model\service\GroupImageService;
use app\mall\model\db\MallGoodsSku;
use app\merchant\model\db\Group;
use app\merchant\model\db\GroupStore;
use app\merchant\model\db\MerchantMemberGoods;
use app\merchant\model\db\MerchantStore;
use app\merchant\model\db\ShopGoods;
use app\shop\model\db\ShopGoodsSpec;

class MemberGoodsService
{
    /**
     * 获取会员商品列表
     * @param $params
     * @return \think\Collection|\think\Paginator
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function goodsList($params){
        $mer_id = $params['mer_id'];
        $page = $params['page'];
        $page_size = $params['page_size'];
        $type = $params['type'];
        $keywords = $params['keywords'];
        if($type&&!in_array($type,array('shop','mall','group'))){
            throw new \think\Exception(L_('业务类型错误！'));
        }
        $where[] = ['mer_id','=',$mer_id];
        if($type){
            $where[] = ['type','=',$type];
        }
        if($keywords){
            $where[] = ['goods_name','like','%'.$keywords.'%'];
        }
        $list = (new MerchantMemberGoods())->getList($where,$page,$page_size);
        $type_name = $this->typeName();
        $groupImage = new GroupImageService();
        foreach ($list as $item){
            $item['type_name'] = $type_name[$item['type']]??'';
            $item['sku_name'] = '';
            if($item['is_sku']==1){
                $item['sku_name'] = '多规格';
                $item['sku_price'] = $item['sku_price']?unserialize($item['sku_price']):[];
            }
            $item['pic'] = '';
            switch ($item['type']){
                case 'shop':
                    $goods = (new ShopGoods())->where(['goods_id'=>$item['goods_id']])->column('image');
                    // 图片
                    if ($goods) {
                        $tmp_pic_arr = explode(';', $goods[0]);
                        foreach ($tmp_pic_arr as $k => $v) {
                            $pic_arr[$k]['title'] = $v;
                            $pic_arr[$k]['url'] = $this->get_image_by_path($v);
                            $k==0 && $rl['image_url'] = $pic_arr[$k]['url']['s_image'];
                        }
                        $item['pic'] = $pic_arr?replace_file_domain($pic_arr[0]['url']['s_image']):'';
                    }else{
                        if (cfg('is_open_goods_default_image')&&cfg('goods_default_image')) {
                            $item['pic'] = cfg('goods_default_image');
                        }else{
                            $item['pic'] = cfg('site_url').'/tpl/Merchant/default/static/images/default_img.png';
                        }
                    }
                    break;
                case 'mall':
                    $goods = (new ShopGoods())->where(['goods_id'=>$item['goods_id']])->column('image');
                    $item['pic'] = $goods?replace_file_domain($goods[0]):'';
                    break;
                case 'group':
                    $goods = (new ShopGoods())->where(['group_id'=>$item['goods_id']])->column('pic');
                    $tmpPicArr = $goods?explode(';', $goods[0]):[];
                    $pic_arr = [];
                    foreach ($tmpPicArr as $value) {
                        $pic_arr[] = replace_file_domain($groupImage->getImageByPath($value,'s'));
                    }
                    $item['pic'] = $pic_arr?$pic_arr[0]:'';
                    break;
                default :
                    break;
            }
        }
        return $list;
    }

    /**
     * 获取业务类型
     * @return array
     */
    public function getGoodsType(){
        $type_list = $this->typeName();
        $data = [];
        foreach ($type_list as $key=>$item){
            $data[] = [
                'key' => $key,
                'value' => $item
            ];
        }
        return $data;
    }

    /**
     * 会员价商品-删除
     * @param $params
     * @return bool
     * @throws \think\Exception
     */
    public function goodsDel($params){
        $mer_id = $params['mer_id'];
        $id = $params['id'];
        if(!$id){
            throw new \think\Exception(L_('请选择要删除的商品'));
        }
        if(!is_array($id)){
            $id = array($id);
        }
        $where[] = ['id','in',$id];
        $where[] = ['mer_id','=',$mer_id];
        try {
            (new MemberGoods())->where($where)->delete();
        }catch (\Exception $e){
            throw new \Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * 会员价商品-修改价格
     * @param $params
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function goodsChangePrice($params){
        $mer_id = $params['mer_id'];
        $id = $params['id'];
        $new_price = $params['new_price'];
        $new_price_info = $params['new_price_info'];
        $stock = $params['stock'];
        if(!$id){
            throw new \think\Exception(L_('参数错误！'));
        }
        $where[] = ['id','=',$id];
        $where[] = ['mer_id','=',$mer_id];
        $goods_info = (new MemberGoods())->where($where)->find();
        if(!$goods_info){
            throw new \think\Exception(L_('商品不存在！'));
        }
        if($goods_info['is_sku']==1){
            if(!$new_price_info||!is_array($new_price_info)){
                throw new \think\Exception(L_('规格信息错误！'));
            }
            $price_arr = [];
            foreach ($new_price_info as $item){
                if(!is_numeric($item['price'])){
                    throw new \think\Exception(L_($item['name'].'会员价只支持数字！'));
                }
                if($item['price']<0){
                    throw new \think\Exception(L_($item['name'].'会员价不能小于0！'));
                }
                if($item['price']>$item['old_price']){
                    throw new \think\Exception(L_($item['name'].'会员价不能大于原价！'));
                }
                if(!is_numeric($item['stock'])||$item['stock']<0){
                    throw new \think\Exception(L_($item['name'].'活动库存格式错误！'));
                }
                $price_arr[] = $item['price'];
            }
            sort($price_arr);
            $saveData = [
                'sku_price' => serialize($new_price_info),
                'price' => $price_arr[0],
                'status' => 1
            ];
        }else{
            if(!is_numeric($new_price)){
                throw new \think\Exception(L_('会员价只支持数字！'));
            }
            if($new_price<0){
                throw new \think\Exception(L_('会员价不能小于0！'));
            }
            if($goods_info['old_price']<$new_price){
                throw new \think\Exception(L_('会员价不能大于原价！'));
            }
            if(!is_numeric($stock)||$stock<0){
                throw new \think\Exception(L_('活动库存格式错误！'));
            }
            $saveData = [
                'price' => $new_price,
                'status' => 1
            ];
        }
        try {
            (new MemberGoods())->where($where)->save($saveData);
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * 会员价商品-修改排序
     * @param $params
     */
    public function goodsChangeSort($params){
        $mer_id = $params['mer_id'];
        $id = $params['id'];
        $sort = $params['sort'];
        if(!$id){
            throw new \think\Exception(L_('请选择商品！'));
        }
        
        if(!is_numeric($sort)||$sort<0){
            throw new \think\Exception(L_('序号格式错误！'));
        }
        
        $where[] = ['id','=',$id];
        $where[] = ['mer_id','=',$mer_id];
        $goods_info = (new MerchantMemberGoods())->where($where)->find();
        if(!$goods_info){
            throw new \think\Exception(L_('商品不存在！'));
        }
        try {
            (new MerchantMemberGoods())->where($where)->save(['sort'=>$sort]);
        }catch (\Exception $e){
            throw new \Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * 获取商品列表
     * @param $params
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGoodsList($params){
        $mer_id = $params['mer_id'];
        $type = $params['type'];
        $search_type = $params['search_type'];
        $keywords = $params['keywords'];
        $page = $params['page'];
        $page_size = $params['page_size'];
        if(!in_array($type,array('village_group','shop','mall','group','meal','appoint'))){
            throw new \think\Exception(L_('业务类型错误'));
        }
        //获取已加入会员价商品
        $ready_goods = (new MerchantMemberGoods())->field('goods_id')->where(['mer_id'=>$mer_id,'type'=>$type])->select();
        $ready_goods = $ready_goods?$ready_goods->toArray():[];
        $goods_ids = array_column($ready_goods,'goods_id');
        $data = [
            "total"=>0,
            "per_page"=>$page_size,
            "current_page"=>1,
            "last_page"=>0,
            "data"=>[]
        ];
        switch ($type){
            case 'village_group':
                $where[] = ['mer_id','=',$mer_id];
                $where[] = ['goods_id','not in',$goods_ids];
                $where[] = ['status','=',1];
                $where[] = ['end_time','>',time()];
                if($keywords){
                    $where[] = ['name','like','%'.$keywords.'%'];
                }
                $village_group_list = (new VillageGroupGoods())->getList($where,$page,$page_size);
                $village_group_list = $village_group_list->toArray();
                $data = [
                    "total"=>$village_group_list['total'],
                    "per_page"=>$village_group_list['per_page'],
                    "current_page"=>$village_group_list['current_page'],
                    "last_page"=>$village_group_list['last_page'],
                    "data"=>[]
                ];

                foreach ($village_group_list['data'] as $k=>$v){
                    $data['data'][] = [
                        'goods_id' => $v['goods_id'],
                        'goods_name' => $v['name'],
                        'goods_price' => $v['price'],
                        'store_name' => ''
                    ];
                }
                break;
            case 'shop':
                //获取商家下面的所有店铺
                $store_list = (new MerchantStore())->where(['mer_id'=>$mer_id,'status'=>1])->column('store_id');
                if($store_list){
                    $where[] = ['a.store_id','in',$store_list];
                    $where[] = ['a.goods_id','not in',$goods_ids];
                    $where[] = ['a.status','=',1];
                    if($keywords){
                        if($search_type==1){
                            $where[] = ['a.name','like','%'.$keywords.'%'];
                        }else{
                            $where[] = ['b.name','like','%'.$keywords.'%'];
                        }

                    }
                    $shop_list = (new ShopGoods())->getList($where,$page,$page_size);
                    $shop_list = $shop_list->toArray();
                    $data = [
                        "total"=>$shop_list['total'],
                        "per_page"=>$shop_list['per_page'],
                        "current_page"=>$shop_list['current_page'],
                        "last_page"=>$shop_list['last_page'],
                        "data"=>[]
                    ];
                    foreach ($shop_list['data'] as $k=>$v){
                        list($is_sku,$sku_price) = $this->getSku('shop',$v);
                        $data['data'][] = [
                            'goods_id' => $v['goods_id'],
                            'goods_name' => $v['name'],
                            'goods_price' => $v['price'],
                            'store_name' => $v['store_name'],
                            'is_sku' => $is_sku,
                            'sku_price' => $sku_price
                        ];
                    }
                }
                break;
            case 'mall':
                $where[] = ['a.mer_id','=',$mer_id];
                $where[] = ['a.goods_id','not in',$goods_ids];
                $where[] = ['a.status','=',1];
                $where[] = ['a.is_del','=',0];
                if($keywords){
                    $where[] = ['a.name','like','%'.$keywords.'%'];
                }
                $mall_list = (new \app\merchant\model\db\MallGoods())->getList($where,$page,$page_size);
                $mall_list = $mall_list->toArray();
                $data = [
                    "total"=>$mall_list['total'],
                    "per_page"=>$mall_list['per_page'],
                    "current_page"=>$mall_list['current_page'],
                    "last_page"=>$mall_list['last_page'],
                    "data"=>[]
                ];
                
                foreach ($mall_list['data'] as $k=>$v){
                    list($is_sku,$sku_price) = $this->getSku('mall',$v);
                    $data['data'][] = [
                        'goods_id' => $v['goods_id'],
                        'goods_name' => $v['name'],
                        'goods_price' => $v['price'],
                        'store_name' => $v['store_name'],
                        'is_sku' => $is_sku,
                        'sku_price' => $sku_price
                    ];
                }
                break;
            case 'group':
                $where[] = ['mer_id','=',$mer_id];
                $where[] = ['group_id','not in',$goods_ids];
                $where[] = ['status','=',1];
                $where[] = ['end_time','>',time()];
                if($keywords){
                    $where[] = ['name|s_name','like','%'.$keywords.'%'];
                }
                $group_list = (new Group())->getList($where,$page,$page_size);
                $group_list = $group_list->toArray();
                $data = [
                    "total"=>$group_list['total'],
                    "per_page"=>$group_list['per_page'],
                    "current_page"=>$group_list['current_page'],
                    "last_page"=>$group_list['last_page'],
                    "data"=>[]
                ];
                //获取商品所属店铺信息
                $store_where = [
                    ['a.group_id','in',array_column($group_list['data'],'group_id')]
                ];
                $store_list = (new GroupStore())->getStoreList($store_where,'a.group_id,a.store_id,b.name');
                $store_list_arr = [];
                foreach ($store_list as $item){
                    if(isset($store_list_arr[$item['group_id']])){
                        $store_list_arr[$item['group_id']] = [];
                    }
                    $store_list_arr[$item['group_id']][] = $item['name'];
                }
                foreach ($group_list['data'] as $k=>$v){
                    list($is_sku,$sku_price) = $this->getSku('group',$v);
                    $data['data'][] = [
                        'goods_id' => $v['group_id'],
                        'goods_name' => $v['name']?:$v['s_name'],
                        'goods_price' => $v['price'],
                        'store_name' => isset($store_list_arr[$v['group_id']])?implode('/',$store_list_arr[$v['group_id']]):'',
                        'is_sku' => $is_sku,
                        'sku_price' => $sku_price
                    ];
                }
                break;
            case 'meal':
                // 获取商家下面的所有店铺
                $store_list = (new MerchantStore())->where(['mer_id'=>$mer_id,'status'=>1])->column('store_id');
                if($store_list){
                    $where[] = ['a.store_id','in',$store_list];
                    $where[] = ['a.goods_id','not in',$goods_ids];
                    $where[] = ['a.status','=',1];
                    if($keywords){
                        if($search_type==1){
                            $where[] = ['a.name','like','%'.$keywords.'%'];
                        }else{
                            $where[] = ['b.name','like','%'.$keywords.'%'];
                        }

                    }
                    $shop_list = (new ShopGoods())->getList($where,$page,$page_size);
                    $shop_list = $shop_list->toArray();
                    $data = [
                        "total"=>$shop_list['total'],
                        "per_page"=>$shop_list['per_page'],
                        "current_page"=>$shop_list['current_page'],
                        "last_page"=>$shop_list['last_page'],
                        "data"=>[]
                    ];
                    foreach ($shop_list['data'] as $k=>$v){
                        $data['data'][] = [
                            'goods_id' => $v['goods_id'],
                            'goods_name' => $v['name'],
                            'goods_price' => $v['price'],
                            'store_name' => $v['store_name']
                        ];
                    }
                }
                break;
            case 'appoint':
                $where[] = ['mer_id','=',$mer_id];
                $where[] = ['appoint_id','not in',$goods_ids];
                $where[] = ['appoint_status','=',0];
                $where[] = ['check_status','=',1];
                $where[] = ['end_time','>',time()];
                $where[] = ['payment_status','=',0];
                if($keywords){
                    $where[] = ['appoint_name','like','%'.$keywords.'%'];
                }
                //获取商家分类
                $merchant_cat = (new AppointCategory())->field('cat_id')->where([['cat_fid','<>','0'],['is_autotrophic','=',0]])->select();
                $merchant_cat_arr = array_column($merchant_cat->toArray(),'cat_id');
                if($merchant_cat_arr){
                    $where[] = ['cat_id','in',$merchant_cat_arr];
                }
                $group_list = (new Appoint())->getList($where,$page,$page_size);
                $group_list = $group_list->toArray();
                //获取预约id
                $appoint_ids = array_column($group_list['data'],'appoint_id');
                $store_list = (new AppointStore())->getStoreList([['a.appoint_id','in',$appoint_ids]]);
                $store_name_list = [];
                foreach ($store_list as $item){
                    $store_name_list[$item['appoint_id']][] = $item['name'];
                }
                $data = [
                    "total"=>$group_list['total'],
                    "per_page"=>$group_list['per_page'],
                    "current_page"=>$group_list['current_page'],
                    "last_page"=>$group_list['last_page'],
                    "data"=>[]
                ];

                foreach ($group_list['data'] as $k=>$v){
                    $data['data'][] = [
                        'goods_id' => $v['appoint_id'],
                        'goods_name' => $v['appoint_name'],
                        'goods_price' => $v['appoint_price'],
                        'store_name' => isset($store_name_list[$v['appoint_id']])?implode('/',$store_name_list[$v['appoint_id']]):''
                    ];
                }
                break;
        }
        return $data;
    }

    /**
     * 添加商品
     * @param $params
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveGoods($params){
        $mer_id = $params['mer_id'];
        $type = $params['type'];
        $goods_ids = $params['goods_ids']?:[311];
        if(!in_array($type,array('village_group','shop','mall','group','meal','appoint'))){
            throw new \think\Exception(L_('业务类型错误!'));
        }
        if(!$goods_ids||!is_array($goods_ids)){
            throw new \think\Exception(L_('请选择商品'));
        }
        //获取已加入会员价商品
        $ready_goods = (new MerchantMemberGoods())->field('goods_id')->where([['mer_id','=',$mer_id],['type','=',$type],['goods_id','in',$goods_ids]])->select();
        foreach ($ready_goods as $item){
            if(in_array($item['goods_Id'],$goods_ids)){
                unset($goods_ids[array_search($item['goods_id'],$goods_ids)]);
            }
        }
        if(!$goods_ids){
            throw new \think\Exception(L_('请选择商品'));
        }
        $goods_ids = array_values($goods_ids);
        $saveData = [];
        switch ($type){
            case 'village_group':
                $where[] = ['mer_id','=',$mer_id];
                $where[] = ['goods_id','in',$goods_ids];
                $where[] = ['status','=',1];
                $where[] = ['end_time','>',time()];
                $village_group_list = (new VillageGroupGoods())->getList($where);
                foreach ($village_group_list as $k=>$v){
                    $saveData[$k] = [
                        'goods_id' => $v['goods_id'],
                        'goods_name' => $v['name'],
                        'type' => $type,
                        'mer_id' => $mer_id,
                        'old_price' => $v['price'],
                        'is_sku' => 0,
                        'add_time' => time()
                    ];
                }
                break;
            case 'shop':
                $where[] = ['a.goods_id','in',$goods_ids];
                $where[] = ['a.status','=',1];
                $shop_list = (new ShopGoods())->getList($where)->toArray();
                $now_goods_ids = array_column($shop_list,'goods_id');
                if(!$now_goods_ids){
                    throw new \think\Exception(L_('请选择商品'));
                }
                //获取商品对应规格属性
                $spec_list = (new ShopGoodsSpec())->field('a.goods_id,b.id,b.name')->alias('a')
                    ->join('shop_goods_spec_value b','a.id = b.sid')
                    ->where([['a.goods_id','in', $now_goods_ids]])
                    ->select();
                $spec_name_arr = [];
                foreach ($spec_list as $item){
                    $spec_name_arr[$item['id']] = $item['name'];
                }
                foreach ($shop_list as $k=>$v){
                    $saveData[$k] = [
                        'goods_id' => $v['goods_id'],
                        'store_id' => $v['store_id'],
                        'goods_name' => $v['name'],
                        'type' => $type,
                        'mer_id' => $mer_id,
                        'old_price' => $v['price'],
                        'is_sku' => 0,
                        'add_time' => time()
                    ];
                    if($v['spec_value']){
                        $spec_array = explode('#',$v['spec_value']);
                        $spacification_arr = [];
                        foreach ($spec_array as $key=>$row) {
                            $row_array = explode('|', $row);
                            $price_arr = explode(':',$row_array[1]);

                            //多种规格名称处理
                            $spec_name_list = [];
                            $row_arr_list = explode(':',$row_array[0]);
                            foreach ($row_arr_list as $vv){
                                if(isset($spec_name_arr[$vv])){
                                    $spec_name_list[] = $spec_name_arr[$vv];
                                }
                            }

                            $spacification_arr[] = [
                                'specifications_id' => $row_array[0],
                                'name' => $spec_name_list?implode('/',$spec_name_list):'',
                                'old_price' => $price_arr[1],
                                'price' => 0.00,
                            ];

                        }
                        $saveData[$k]['is_sku'] = 1;
                        $saveData[$k]['sku_price'] = $spacification_arr?serialize($spacification_arr):'';
                    }
                }

                break;
            case 'mall':
                $where[] = ['a.mer_id','=',$mer_id];
                $where[] = ['a.goods_id','in',$goods_ids];
                $where[] = ['a.status','=',1];
                $where[] = ['a.is_del','=',0];
                $group_list = (new \app\member_goods\model\db\MallGoods())->getList($where);
                foreach ($group_list as $k=>$v){
                    $saveData[$k] = [
                        'goods_id' => $v['goods_id'],
                        'goods_name' => $v['name'],
                        'type' => $type,
                        'mer_id' => $mer_id,
                        'old_price' => $v['price'],
                        'is_sku' => 0,
                        'add_time' => time(),
                        'store_id' => $v['store_id']
                    ];
                    //获取规格详情
                    $spacification = (new MallGoodsSku())->where(['goods_id'=>$v['goods_id']])->order('sku_id desc')->select();
                    if($spacification&&$spacification[0]['sku_info']){
                        $spacification_arr = [];
                        foreach ($spacification as $vv){
                            $spacification_arr[] = [
                                'specifications_id' => $vv['sku_id'],
                                'name' => str_replace(',','/',$vv['sku_str']),
                                'old_price' => $vv['price'],
                                'price' => 0.00,
                            ];
                        }
                        $saveData[$k]['is_sku'] = 1;
                        $saveData[$k]['sku_price'] = $spacification_arr?serialize($spacification_arr):'';
                    }
                }
                break;
            case 'group':
                $where[] = ['mer_id','=',$mer_id];
                $where[] = ['group_id','in',$goods_ids];
                $where[] = ['status','=',1];
                $where[] = ['end_time','>',time()];
                $group_list = (new Group())->getList($where);
                //获取商品所属店铺信息
                $store_where = [
                    ['a.group_id','in',array_column($group_list->toArray(),'group_id')]
                ];
                $store_list = (new GroupStore())->getStoreList($store_where,'a.group_id,a.store_id,b.name');
                $store_list_arr = [];
                foreach ($store_list as $item){
                    if(isset($store_list_arr[$item['group_id']])){
                        $store_list_arr[$item['group_id']] = [];
                    }
                    $store_list_arr[$item['group_id']][] = $item['store_id'];
                }
                foreach ($group_list as $k=>$v){
                    $saveData[$k] = [
                        'goods_id' => $v['group_id'],
                        'goods_name' => $v['name']?:$v['s_name'],
                        'type' => $type,
                        'mer_id' => $mer_id,
                        'old_price' => $v['price'],
                        'is_sku' => 0,
                        'add_time' => time(),
                        'store_id' => isset($store_list_arr[$v['group_id']])?implode(',',$store_list_arr[$v['group_id']]):'',
                    ];
                    if($v['is_sku']==1){
                        //获取多规格详情
                        $spacification = (new GroupSpecifications())->where(['group_id'=>$v['group_id']])->select();
                        $spacification_arr = [];
                        foreach ($spacification as $vv){
                            $spacification_arr[] = [
                                'specifications_id' => $vv['specifications_id'],
                                'name' => $vv['specifications_name'],
                                'old_price' => $vv['price'],
                                'price' => 0.00,
                            ];
                        }
                        $saveData[$k]['is_sku'] = 1;
                        $saveData[$k]['sku_price'] = $spacification_arr?serialize($spacification_arr):'';
                    }
                }

                break;
            case 'meal':
                //
                $where[] = ['a.goods_id','in',$goods_ids];
                $where[] = ['a.status','=',1];
                $shop_list = (new ShopGoods())->getList($where)->toArray();
                $now_goods_ids = array_column($shop_list,'goods_id');
                if(!$now_goods_ids){
                    throw new \think\Exception(L_('请选择商品'));
                }
                //获取商品对应规格属性
                $spec_list = (new ShopGoodsSpec())->field('a.goods_id,b.id,b.name')->alias('a')
                    ->join('shop_goods_spec_value b','a.id = b.sid')
                    ->where([['a.goods_id','in', $now_goods_ids]])
                    ->select();
                $spec_name_arr = [];
                foreach ($spec_list as $item){
                    $spec_name_arr[$item['id']] = $item['name'];
                }
                foreach ($shop_list as $k=>$v){
                    $saveData[$k] = [
                        'goods_id' => $v['goods_id'],
                        'store_id' => $v['store_id'],
                        'goods_name' => $v['name'],
                        'type' => $type,
                        'mer_id' => $mer_id,
                        'old_price' => $v['price'],
                        'is_sku' => 0,
                        'add_time' => time()
                    ];
                    if($v['spec_value']){
                        $spec_array = explode('#',$v['spec_value']);
                        $spacification_arr = [];
                        foreach ($spec_array as $key=>$row) {
                            $row_array = explode('|', $row);
                            $price_arr = explode(':',$row_array[1]);

                            //多种规格名称处理
                            $spec_name_list = [];
                            $row_arr_list = explode(':',$row_array[0]);
                            foreach ($row_arr_list as $vv){
                                if(isset($spec_name_arr[$vv])){
                                    $spec_name_list[] = $spec_name_arr[$vv];
                                }
                            }

                            $spacification_arr[] = [
                                'specifications_id' => $row_array[0],
                                'name' => $spec_name_list?implode('/',$spec_name_list):'',
                                'old_price' => $price_arr[1],
                                'price' => 0.00,
                            ];

                        }
                        $saveData[$k]['is_sku'] = 1;
                        $saveData[$k]['sku_price'] = $spacification_arr?serialize($spacification_arr):'';
                    }
                }
                break;
            case 'appoint':
                $where[] = ['mer_id','=',$mer_id];
                $where[] = ['appoint_id','in',$goods_ids];
                $where[] = ['appoint_status','=',0];
                $where[] = ['check_status','=',1];
                $where[] = ['end_time','>',time()];
                //获取商家分类
                $merchant_cat = (new AppointCategory())->field('cat_id')->where([['cat_fid','<>','0'],['is_autotrophic','=',0]])->select();
                $merchant_cat_arr = array_column($merchant_cat->toArray(),'cat_id');
                if($merchant_cat_arr){
                    $where[] = ['cat_id','in',$merchant_cat_arr];
                }
                $appoint_list = (new Appoint())->getList($where);
                //获取预约规格
                $spec_list = (new AppointProduct())->where([['appoint_id','in',$goods_ids]])->select();
                $spec_arr = [];
                foreach ($spec_list as $item){
                    $spec_arr[$item['appoint_id']][] = $item;
                }
                //获取预约id
                $appoint_ids = array_column($appoint_list->toArray(),'appoint_id');
                $store_list = (new AppointStore())->getStoreList([['a.appoint_id','in',$appoint_ids]]);
                $store_name_list = [];
                foreach ($store_list as $item){
                    $store_name_list[$item['appoint_id']][] = $item['store_id'];
                }
                foreach ($appoint_list as $k=>$v){
                    $saveData[$k] = [
                        'goods_id' => $v['appoint_id'],
                        'goods_name' => $v['appoint_name'],
                        'type' => $type,
                        'mer_id' => $mer_id,
                        'old_price' => $v['appoint_price'],
                        'is_sku' => 0,
                        'add_time' => time(),
                        'store_id' => isset($store_name_list[$v['appoint_id']])?implode(',',$store_name_list[$v['appoint_id']]):''
                    ];
                    if(isset($spec_arr[$v['appoint_id']])){
                        $spacification_arr = [];
                        foreach ($spec_arr[$v['appoint_id']] as $key=>$row) {
                            $spacification_arr[] = [
                                'specifications_id' => $row['id'],
                                'name' => $row['name'],
                                'old_price' => $row['price'],
                                'price' => 0.00,
                            ];

                        }
                        $saveData[$k]['is_sku'] = 1;
                        $saveData[$k]['sku_price'] = $spacification_arr?serialize($spacification_arr):'';
                    }
                }
                break;
        }
        try {
            (new MemberGoods())->saveAll($saveData);
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * 获取商品规格
     * @param $type
     * @param $goods
     * @return array
     */
    private function getSku($type,$goods){
        $is_sku = 0;
        $sku_price = [];
        switch ($type){
            case 'shop':
                if($goods['spec_value']){
                    $now_goods_id = $goods['goods_id'];
                    //获取商品对应规格属性
                    $spec_list = (new ShopGoodsSpec())->field('a.goods_id,b.id,b.name')->alias('a')
                        ->join('shop_goods_spec_value b','a.id = b.sid')
                        ->where([['a.goods_id','=', $now_goods_id]])
                        ->select();
                    $spec_name_arr = [];
                    foreach ($spec_list as $item){
                        $spec_name_arr[$item['id']] = $item['name'];
                    }
                    $spec_array = explode('#',$goods['spec_value']);
                    $spacification_arr = [];
                    foreach ($spec_array as $key=>$row) {
                        $row_array = explode('|', $row);
                        $price_arr = explode(':',$row_array[1]);

                        //多种规格名称处理
                        $spec_name_list = [];
                        $row_arr_list = explode(':',$row_array[0]);
                        foreach ($row_arr_list as $vv){
                            if(isset($spec_name_arr[$vv])){
                                $spec_name_list[] = $spec_name_arr[$vv];
                            }
                        }

                        $spacification_arr[] = [
                            'specifications_id' => $row_array[0],
                            'name' => $spec_name_list?implode('/',$spec_name_list):'',
                            'old_price' => $price_arr[1],
                            'price' => 0.00,
                        ];

                    }
                    $is_sku = 1;
                    $sku_price = $spacification_arr;
                }
                
                break;
            case 'mall':
                //获取规格详情
                $spacification = (new MallGoodsSku())->where(['goods_id'=>$goods['goods_id']])->order('sku_id desc')->select();
                if($spacification&&$spacification[0]['sku_info']){
                    $spacification_arr = [];
                    foreach ($spacification as $vv){
                        $spacification_arr[] = [
                            'specifications_id' => $vv['sku_id'],
                            'name' => str_replace(',','/',$vv['sku_str']),
                            'old_price' => $vv['price'],
                            'price' => 0.00,
                        ];
                    }
                    $is_sku = 1;
                    $sku_price = $spacification_arr;
                }
                break;
            case 'group':
                if($goods['is_sku']==1){
                    //获取多规格详情
                    $spacification = (new GroupSpecifications())->where(['group_id'=>$goods['group_id']])->select();
                    $spacification_arr = [];
                    foreach ($spacification as $vv){
                        $spacification_arr[] = [
                            'specifications_id' => $vv['specifications_id'],
                            'name' => $vv['specifications_name'],
                            'old_price' => $vv['price'],
                            'price' => 0.00,
                        ];
                    }
                    $is_sku = 1;
                    $sku_price = $spacification_arr;
                }
                break;
        }
        
        return [$is_sku,$sku_price];
    }

    private function typeName(){
        $data = [
            'shop' => '外卖',
            'mall' => '商城',
            'group' => '团购',
        ];
        return $data;
    }

    public function getJoinGoods($params){
        if(!is_array($params['goods_ids'])){
            $params['goods_ids'] = array($params['goods_ids']);
        }
        $where[] = ['type','=',$params['type']];
        $where[] = ['status','=',1];
        $where[] = ['goods_id','in',$params['goods_ids']];
        $where[] = ['status','=',1];
        $member_goods = (new MemberGoods())->where($where)->select();
        return $member_goods->toArray();
    }

    /*根据商品数据表的图片字段的一段来得到图片*/
    public function get_image_by_path($path, $image_type='-1')
    {
        if (!empty($path)) {
            $filepath = 'goods';
            if (strstr($path, 'sysgoods_')) {
                $filepath = 'sysgoods';
            }
            if ($image_type == '-1') {
                if ('http' === substr($path, 0, 4)) {
                    $return['image'] = replace_file_domain($path);
                    $return['m_image'] = replace_file_domain($path);
                    $return['s_image'] = replace_file_domain($path);
                } else if (strpos($path, ',') !== false) {
                    $temp_path = explode(',',$path);
                    if (strpos($path,'/upload/' . $filepath. '/')!==false){
                        $return['image'] = file_domain() .$temp_path[0].'/'.$temp_path[1];
                        $return['m_image'] = file_domain() .$temp_path[0].'/m_'.$temp_path[1];
                        $return['s_image'] = file_domain() .$temp_path[0].'/s_'.$temp_path[1];
                    }else{
                        $return['image'] = file_domain() .'/upload/' . $filepath. '/'.$temp_path[0].'/'.$temp_path[1];
                        $return['m_image'] = file_domain() .'/upload/' . $filepath. '/'.$temp_path[0].'/m_'.$temp_path[1];
                        $return['s_image'] = file_domain() .'/upload/' . $filepath. '/'.$temp_path[0].'/s_'.$temp_path[1];
                    }
                }else{
                    $return['image'] = file_domain() . $path;
                    $return['m_image'] = file_domain() . $path;
                    $return['s_image'] = file_domain() . $path;
                }
            } else {
                if('http' === substr($path,0,4)){
                    $return = $path;
                }else{
                    if (strpos($path,',')!==false) {
                        if (strpos($path,'/upload/' . $filepath. '/')!==false){
                            $path = explode(',',$path);
                            $return = file_domain() .$path[0].'/'.$path[1];
                        }else{
                            $path = explode(',',$path);
                            $return = file_domain() .'/upload/' . $filepath. '/'.$path[0].'/'.$path[1];
                        }
                    }else{
                        $return = file_domain() . $path;
                    }
                }
            }
            return $return;
        } else if(cfg('goods_default_image')){
            return cfg('goods_default_image');
        } else if (cfg('GOODS_DEFAULT_IMAGE')) {
            return cfg('GOODS_DEFAULT_IMAGE');
        }else {
            return false;
        }
    }
}