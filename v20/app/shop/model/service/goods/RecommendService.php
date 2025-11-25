<?php
/**
 * 订单详情页推荐商品管理
 */

namespace app\shop\model\service\goods;


use app\common\model\service\user\UserLongLatService;
use app\group\model\db\Group;
use app\group\model\db\GroupStore;
use app\grow_grass\model\db\GrowGrassArticle;
use app\mall\model\db\MallGoods;
use app\mall\model\service\MallGoodsService;
use app\shop\model\db\ShopCategory;
use app\shop\model\db\ShopCategoryRelation;
use app\shop\model\db\ShopGoods;
use app\shop\model\db\ShopRecommend;
use app\shop\model\db\ShopRecommendCategory;
use app\shop\model\db\ShopRecommendGoods;
use think\facade\Db;

class RecommendService
{
    /**
     * 获取推荐商品组
     */
    public function getRecommendList($params)
    {
        $where = [];
        $where['is_del'] = 0;
        $data = (new ShopRecommend())->getList($where,'id,name,update_time,scale,status',$params['pageSize']);
        foreach ($data['data'] as &$v){
            $v['update_time'] = date('Y-m-d H:i:s',$v['update_time']);
        }
        return $data;
    }
    
    /**
     * 查询外卖店铺分类(共两级)
     */
    public function getCategory()
    {
        $where = [];
        $where['a.cat_status'] = 1;
        $where['b.cat_status'] = 1;
        $where['a.cat_fid'] = 0;
        $list = (new ShopCategory())->getLableAll($where,'a.cat_id,a.cat_name,b.cat_id as s_cat_id,b.cat_name as s_cat_name');
        $data = [];
        foreach ($list as $v){//获取一级
            $data[$v['cat_id']] = [
                'key'=>$v['cat_id'],
                'cat_id'=>$v['cat_id'],
                'cat_name'=>$v['cat_name'],
                'children'=>[]
            ];
        }
        foreach ($list as $v){//获取二级
            $data[$v['cat_id']]['children'][] = [
                'key'=>$v['cat_id'].'_'.$v['s_cat_id'],
                'cat_id'=>$v['s_cat_id'],
                'cat_name'=>$v['s_cat_name']
            ];
        }
        $data = array_values($data);
        return $data;
    }

    /**
     * 添加商品推荐组
     */
    public function addRecommend($params)
    {
        if(!$params['name']){
            throw new \think\Exception('推荐组标题不能为空！');
        }
        if(!$params['cat_id_ary']){
            throw new \think\Exception('管理分类不能为空！');
        }
        if($params['scale']>100){
            throw new \think\Exception('推荐占比不能超过100%！');
        }

//        $params['cat_id_ary'] = ['1_1', '1_2', '3', '4'];
        $catDataAry = [];
        foreach ($params['cat_id_ary'] as $v){
            $catAry = explode('_',$v);
            if(!$catAry[0]){
                continue;
            }
            $catId = $catAry[0];
            $catIdSecond = 0;
            if(isset($catAry[1]) && $catAry){//是二级分类
                $catIdSecond = $catAry[1];
            }
            $catDataAry[] = [
                'cat_id'=>$catId,
                'cat_id_second'=>$catIdSecond,
            ];
        }
        
        $addData = [
            'name'=>$params['name'],
            'scale'=>$params['scale'],
            'status'=>$params['status'],
            'create_time'=>time(),
            'update_time'=>time()
        ];
        if(!$catDataAry){
            throw new \think\Exception('管理分类不能为空！');
        }


        Db::startTrans();
        try {
            $recommend_id = (new ShopRecommend())->insertGetId($addData);
            if(!$recommend_id){
                throw new \think\Exception('创建推荐组失败！');
            }

            $addCategoryData = [];
            foreach ($catDataAry as $cat){
                $catId = $cat['cat_id'];
                $catFId = 0;
                if($cat['cat_id_second']){//二级分类
                    $catId = $cat['cat_id_second'];
                    $catFId = $cat['cat_id'];
                }
                $addCategoryData[] = [
                    'recommend_id'=>$recommend_id,
                    'cat_id'=>$catId,
                    'cat_fid'=>$catFId,
                    'update_time'=>time()
                ];
            }
            $add = (new ShopRecommendCategory())->insertAll($addCategoryData);
            if(!$add){
                throw new \think\Exception('创建推荐组分类记录失败！');
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 添加商品推荐组
     */
    public function editRecommend($params)
    {
        if(!$params['id']){
            throw new \think\Exception('操作对象不能为空！');
        }
        if(!$params['name'] && $params['update_type']==0){
            throw new \think\Exception('推荐组标题不能为空！');
        }
        if(!$params['cat_id_ary'] && $params['update_type']==0){
            throw new \think\Exception('管理分类不能为空！');
        }
        if($params['scale']>100 && in_array($params['update_type'],[0,2])){
            throw new \think\Exception('推荐占比不能超过100%！');
        }

        $updateData = [
            'update_time'=>time()
        ];

        $catDataAry = [];
        if($params['update_type'] == 0){
            $updateData['name'] = $params['name'];
            $updateData['scale'] = $params['scale'];
            $updateData['status'] = $params['status'];
            foreach ($params['cat_id_ary'] as $v){
                $catAry = explode('_',$v);
                if(!$catAry[0]){
                    continue;
                }
                $catId = $catAry[0];
                $catIdSecond = 0;
                if(isset($catAry[1]) && $catAry){//是二级分类
                    $catIdSecond = $catAry[1];
                }
                $catDataAry[] = [
                    'cat_id'=>$catId,
                    'cat_id_second'=>$catIdSecond,
                ];
            }
            if(!$catDataAry){
                throw new \think\Exception('管理分类不能为空！');
            }
            
        }elseif ($params['update_type'] == 1){
            $updateData['status'] = $params['status'];
        }elseif ($params['update_type'] == 2){
            $updateData['scale'] = $params['scale'];
        }


        Db::startTrans();
        try {
            $recommend_id = (new ShopRecommend())->where('id',$params['id'])->update($updateData);
            if(!$recommend_id){
                throw new \think\Exception('编辑推荐组信息失败！');
            }

            $addCategoryData = [];
            if($catDataAry){
                foreach ($catDataAry as $cat){
                    $catId = $cat['cat_id'];
                    $catFId = 0;
                    if($cat['cat_id_second']){//二级分类
                        $catId = $cat['cat_id_second'];
                        $catFId = $cat['cat_id'];
                    }
                    $addCategoryData[] = [
                        'recommend_id'=>$params['id'],
                        'cat_id'=>$catId,
                        'cat_fid'=>$catFId,
                        'update_time'=>time()
                    ];
                }

                (new ShopRecommendCategory())->where('recommend_id',$params['id'])->delete();
                $add = (new ShopRecommendCategory())->insertAll($addCategoryData);
                if(!$add){
                    throw new \think\Exception('创建推荐组分类记录失败！');
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }
    
    /**
     * 获取推荐组详情
     */
    public function getRecommendDetail($params)
    {
        if(!$params['id']){
            throw new \think\Exception('操作对象不能为空！');
        }
        $where = [];
        $where['id'] = $params['id'];
        $where['is_del'] = 0;
        $data = (new ShopRecommend)->getDetail($where,'id,name,scale,status');
        if(!$data){
            throw new \think\Exception('操作对象不存在或者已被删除！');
        }

        //查询分类
        $cateAry = [];
        $cateFidAry = [];
        $whereCat = [];
        $whereCat['recommend_id'] = $params['id'];
        $category = (new ShopRecommendCategory())->getList($whereCat,'cat_id,cat_fid');
        foreach ($category as $v){
            $cateAry[] = $v['cat_fid'] ? $v['cat_fid'].'_'.$v['cat_id'] : $v['cat_id'];
            if($v['cat_fid']){
                $cateFidAry[] = $v['cat_fid'];
            }
        }
        $data['cat_id_ary'] = $cateAry;
        $data['cat_fid_ary'] = array_values(array_unique($cateFidAry));
        return $data;
    }
    
    /**
     * 批量删除推荐组
     */
    public function delRecommend($params)
    {
        if(!$params['ids']){
            throw new \think\Exception('操作对象不能为空！');
        }
        $isAry = explode(',',$params['ids']);
        $del = (new ShopRecommend())->where([['id','IN',$isAry]])->update([
            'is_del'=>1,
            'update_time'=>time()
        ]);
        if(!$del){
            throw new \think\Exception('操作失败！');
        }
        return true;
    }
    
    /**
     * 查询推荐组商品列表
     */
    public function getRecommendGoodsList($params)
    {
        if(!$params['id']){
            throw new \think\Exception('操作对象不能为空！');
        }
        $where = [];
        $where[] = ['is_del','=',0];
        $where[] = ['recommend_id','=',$params['id']];
        if($params['business']){
            $where[] = ['business','=',$params['business']];
        }
        if($params['business'] && $params['serch_type']==1 && $params['keyword']){//根据商品名称查询
            $whereGoods = [];
            $goodsIds = [];
            $whereGoods[] = ['a.name','like','%'.$params['keyword'].'%'];
            $goodsAry = $this->getGoodsList(['business'=>$params['business'],'where'=>$whereGoods,'pageSize'=>$params['pageSize']]);
            if($goodsAry['data']){
                $goodsIds = array_column($goodsAry['data'],'goods_id');
            }
            $where[] = ['goods_id','IN',$goodsIds];
        }
        $data = (new ShopRecommendGoods())->getList($where,'id,goods_id,business,sort,status',$params['pageSize']);
        foreach ($data['data'] as $k=>$v){
            $data['data'][$k]['business_str'] = (new ShopRecommendGoods())->getBusinessMsg($v['business']);
            
            //查询商品名称以及商家名称
            $goodsInfo = $this->getGoodsInfoByBusiness($v['goods_id'],$v['business']);
            $data['data'][$k]['goods_name'] = $goodsInfo['goods_name']??'';
            $data['data'][$k]['mer_name'] = $goodsInfo['mer_name']??'';
            $data['data'][$k]['img'] = $goodsInfo['img']??'';
            $data['data'][$k]['img'] = $data['data'][$k]['img'] ? replace_file_domain($data['data'][$k]['img']) : '';//商品图片
            $data['data'][$k]['warn'] = $goodsInfo['warn']??'';
        }
        return $data;
    }
    
    /**
     * 查询商品信息
     */
    public function getGoodsInfoByBusiness($goodsId,$business)
    {
        $goodsInfo = [];
        switch ($business){
            case 'shop':
                $where = [];
                $where['r.goods_id'] = $goodsId;
                $goodsInfo = (new ShopGoods())->getDetail($where,'r.name as goods_name,m.name as mer_name,r.image as img,r.audit_status,r.status,ms.status as store_status,ms.have_shop');
                if($goodsInfo){
                    $goodsInfo['warn'] = (new ShopGoods())->getWarn($goodsInfo);
                }
                break;
            case 'mall':
                $where = [];
                $where['a.goods_id'] = $goodsId;
                $goodsInfo = (new MallGoods())->getGoodsInfoAndMerchant($where,'a.name as goods_name,b.name as mer_name,a.image as img,a.audit_status,a.status,a.is_del,c.status as store_status,c.have_mall');
                if($goodsInfo){
                    $goodsInfo['warn'] = (new MallGoods())->getWarn($goodsInfo);
                }
                break;
            case 'group':
                $where = [];
                $where['a.group_id'] = $goodsId;
                $goodsInfo = (new Group())->getGoodsInfoAndMerchant($where,'a.name as goods_name,b.name as mer_name,a.pic as img,a.status');
                if($goodsInfo){
                    $goodsInfo['warn'] = (new Group())->getWarn($goodsInfo);
                }
                break;
            case 'grow_grass':
                $where = [];
                $where['article_id'] = $goodsId;
                $goodsInfo = (new GrowGrassArticle())->getDetail($where,'a.name as goods_name,b.nickname as mer_name,a.img,a.status,a.is_del,a.is_system_del');
                if($goodsInfo){
                    $goodsInfo['warn'] = (new GrowGrassArticle())->getWarn($goodsInfo);
                }
                break;
        }
        return $goodsInfo;
    }
    
    /**
     * 根据业务查询商品列表
     */
    public function getGoodsList($params)
    {
        $params['goods_id'] = $params['goods_id']??0;
        $params['serch_type'] = $params['serch_type']??0;
        $params['pageSize'] = $params['pageSize']??0;
        $params['id'] = $params['id']??0;
        $params['where'] = $params['where']??[];
        $params['now_city'] = $params['now_city']??0;
        if(!in_array($params['business'],['shop','mall','group','grow_grass'])){
            throw new \think\Exception('当前业务类型不支持！');
        }
        
        $where = $params['where'] ?: [];
        $where[] = ['b.status','=',1];

        //查询推荐组信息
        $recommendInfo = [];
        if($params['id']){
            $recommendInfo = (new ShopRecommendGoods())->where(['recommend_id'=>$params['id'],'business'=>$params['business'],'is_del'=>0])->field('goods_id,business')->select();
            if($recommendInfo){
                $recommendInfo = $recommendInfo->toArray();
            }
        }
        if(in_array($params['business'],['shop','mall','group']) && $params['serch_type']==1){
            $where[] = ['a.name','like','%'.$params['keyword'].'%'];
        }
        if(in_array($params['business'],['shop','mall','group']) && $params['serch_type']==2){
            $where[] = ['b.name','like','%'.$params['keyword'].'%'];
        }
        if($params['business']=='grow_grass' && $params['serch_type']==3){
            $where[] = ['a.name','like','%'.$params['keyword'].'%'];
        }
        if($params['business']=='grow_grass' && $params['serch_type']==4){
            $where[] = ['b.nickname','like','%'.$params['keyword'].'%'];
        }
        if(in_array($params['business'],['shop','mall']) && $params['serch_type']==5){//店铺名称搜索
            $where[] = ['c.name','like','%'.$params['keyword'].'%'];
        }
        
        if($params['goods_id'] && in_array($params['business'],['shop','mall'])){
            $where[] = ['a.goods_id','=',$params['goods_id']];
        }
        if($params['goods_id'] && $params['business']=='group'){
            $where[] = ['a.group_id','=',$params['goods_id']];
        }
        if($params['goods_id'] && $params['business']=='grow_grass'){
            $where[] = ['a.article_id','=',$params['goods_id']];
        }
        
        if($params['now_city'] && in_array($params['business'],['mall','shop'])){
            $where[] = ['c.city_id','=',$params['now_city']];
        }
        switch ($params['business']){
            case 'shop':
                $where[] = ['a.audit_status','=',1];
                $where[] = ['a.status','=',1];
                $where[] = ['c.status','=',1];
                $where[] = ['c.have_shop','=',1];
                $list = (new ShopGoods())->getShopList($where,'a.goods_id,a.name as goods_name,b.name as mer_name,a.price,a.spec_value,c.long,c.lat,a.image as img,a.store_id,a.sort_id,c.name as store_name',$params['pageSize']);
                //查询多规格
                $mobileHeadColor = cfg('mobile_head_color') ? ltrim(cfg('mobile_head_color'),'#') : '06C1AE';
                foreach ($list['data'] as $k=>$v){
                    $skuAry = [];
                    $price = '';
                    if($v['spec_value']){
                        $skuInfo = (new ShopGoodsService())->formatSpecValue($v['spec_value'],$v['goods_id'],0);
                        foreach ($skuInfo['list'] as $skuId=>$vv){
                            $sku_name = '';
                            foreach ($vv['spec'] as $vSpec){
                                $sku_name .= $sku_name ? ','.$vSpec['spec_val_name'] : $vSpec['spec_val_name'];
                            }
                            $skuAry[] = [
                                'sku_id'=>$skuId,
                                'sku_name'=>$sku_name,
                                'sku_price'=>get_format_number($vv['price'])
                            ];

                            if($price === ''){
                                $price = $vv['price'];
                            }else{
                                $price = $vv['price'] <= $price ? $vv['price'] : $price;
                            }
                        }
                    }
                    unset($list['data'][$k]['spec_value']);
                    $list['data'][$k]['is_sku'] = $skuAry ? 1 : 0;
                    $list['data'][$k]['sku'] = $skuAry;
                    $list['data'][$k]['link'] = get_base_url('pages/shop_new/shopDetail/shopDetail?store_id='.$v['store_id'].'&backgroundColor='.$mobileHeadColor.'&frontColor=ffffff&sort_id='.$v['sort_id'].'&goods_id='.$v['goods_id']);
                    unset($list['data'][$k]['store_id']);
                    unset($list['data'][$k]['sort_id']);
                }
                break;
            case 'mall':
                $where[] = ['a.audit_status','=',1];
                $where[] = ['a.status','=',1];
                $where[] = ['a.is_del','=',0];
                $where[] = ['c.status','=',1];
                $where[] = ['c.have_mall','=',1];
                $list = (new MallGoods())->getMallList($where,'a.goods_id,a.name as goods_name,b.name as mer_name,a.price,a.goods_type,a.image as img,c.name as store_name',$params['pageSize']);
                //查询多规格
                foreach ($list['data'] as $k=>$v){
                    $skuAry = [];
                    $price = '';
                    if($v['goods_type'] == 'sku'){
                        $skuInfo = (new MallGoodsService())->getGoodsSkuInfo($v['goods_id']);
                        foreach ($skuInfo as $vv){
                            $skuAry[] = [
                                'sku_id'=>$vv['sku_id'],
                                'sku_name'=>$vv['sku_str'],
                                'sku_price'=>get_format_number($vv['price'])
                            ];
                            if($price === ''){
                                $price = $vv['price'];
                            }else{
                                $price = $vv['price'] <= $price ? $vv['price'] : $price;
                            }
                        }
                        $list['data'][$k]['price'] = $price;
                    }
                    unset($list['data'][$k]['goods_type']);
                    $list['data'][$k]['is_sku'] = $skuAry ? 1 : 0;
                    $list['data'][$k]['sku'] = $skuAry;
                    $list['data'][$k]['link'] = get_base_url('pages/shopmall_third/commodity_details?goods_id='.$v['goods_id']);
                }
                break;
            case 'group':
                if($params['now_city']){
                    //查询团购店铺
                    $storeAry = (new GroupStore())->getStoreByGroup(['g.group_id'=>$params['goods_id'],'s.city_id'=>$params['now_city']],'s.store_id');
                    $storeAry = $storeAry->toArray();
                    if(!$storeAry){
                        $where[] = ['a.group_id','=',-1];
                    }
                }
                $where[] = ['a.status','=',1];
                $list = (new Group())->getGroupList($where,'a.group_id as goods_id,a.name as goods_name,b.name as mer_name,a.price,a.pic as img',$params['pageSize']);
                foreach ($list['data'] as $k=>$v){
                    $list['data'][$k]['link'] = get_base_url('pages/group/v1/groupDetail/index?group_id='.$v['goods_id'],1);
                }
                break;
            case 'grow_grass':
                $where[] = ['a.status','=',20];
                $where[] = ['a.is_del','=',0];
                $where[] = ['a.is_system_del','=',0];
                $where[] = ['a.is_manuscript','=',0];
                $list = (new GrowGrassArticle())->getGrowGrassList($where,'a.article_id as goods_id,a.name as goods_name,b.nickname as mer_name,0 as price,a.img',$params['pageSize']);
                foreach ($list['data'] as $k=>$v){
                    $list['data'][$k]['link'] = get_base_url('pages/wantToBuy/v1/articleDetail/index?article_id='.$v['goods_id'],1);
                }
                break;
        }
        foreach ($list['data'] as $key=>$vList){
            $list['data'][$key]['price'] = $vList['price'] ?? 0;
            $list['data'][$key]['price'] = get_format_number($vList['price']);
            $list['data'][$key]['is_sku'] = $vList['is_sku'] ?? 0;
            $list['data'][$key]['sku'] = $vList['sku'] ?? [];
            $list['data'][$key]['is_check'] = 0;
            $list['data'][$key]['img'] = $vList['img'] ? replace_file_domain($vList['img']) : '';
            foreach ($recommendInfo as $vRInfo){
                if($vRInfo['goods_id'] == $vList['goods_id']){
                    $list['data'][$key]['is_check'] = 1;
                }
            }
            $list['data'][$key]['store_name'] = $vList['store_name'] ?? '';
        }
        return $list;
    }
    
    /**
     * 添加推荐组商品
     */
    public function addRecommendGoods($params)
    {
        if(!$params['id']){
            throw new \think\Exception('操作对象不能为空！');
        }
        if(!$params['goods_ids']){
            throw new \think\Exception('请选择商品！');
        }
        if(!in_array($params['business'],['shop','mall','group','grow_grass'])){
            throw new \think\Exception('当前业务类型商品不支持！');
        }
        
        //查询推荐组商品列表
        $goodsAryReal = (new ShopRecommendGoods())->where(['recommend_id'=>$params['id'],'is_del'=>0,'business'=>$params['business']])->column('goods_id');
        
        $goodsAry = explode(',',$params['goods_ids']);
        $goodsAry = array_diff($goodsAry,$goodsAryReal);
        if(!$goodsAry){
            throw new \think\Exception('请勿重复添加相同的商品，刷新后重试！');
        }
        $addData = [];
        foreach ($goodsAry as $v){
            $addData[] = [
                'recommend_id'=>$params['id'],
                'goods_id'=>$v,
                'business'=>$params['business'],
                'create_time'=>time(),
                'update_time'=>time()
            ];
        }
        $add = (new ShopRecommendGoods())->insertAll($addData);
        if(!$add){
            throw new \think\Exception('添加商品失败！');
        }
        return true;
    }
    
    /**
     * 编辑推荐商品信息
     */
    public function editRecommendGoods($params)
    {
        if(!$params['id']){
            throw new \think\Exception('操作对象不能为空！');
        }
        $info = (new ShopRecommendGoods())->where('id',$params['id'])->find();
        $updateData = ['update_time'=>time()];
        if($params['update_type'] == 1){
            $updateData['status'] = $params['status'];
            if($params['status']){
                //需要查询是否已上架8个商品了
                $count = (new ShopRecommendGoods())->where(['recommend_id'=>$info['recommend_id'],'is_del'=>0,'status'=>1])->count();
                if($count >= 8){
                    throw new \think\Exception('用户端最多只能展示8个商品！');
                }
            }
        }
        if($params['update_type'] == 2){
            $updateData['sort'] = $params['sort'];
        }
        $update = (new ShopRecommendGoods())->where('id',$params['id'])->update($updateData);
        if(!$update){
            throw new \think\Exception('操作失败！');
        }
        return true;
    }
    
    /**
     * 批量删除推荐商品
     */
    public function delRecommendGoods($params)
    {
        if(!$params['ids']){
            throw new \think\Exception('操作对象不能为空！');
        }
        $idAry = explode(',',$params['ids']);
        $update = (new ShopRecommendGoods())->where([['id','IN',$idAry]])->update([
            'is_del'=>1,
            'update_time'=>time()
        ]);
        if(!$update){
            throw new \think\Exception('操作失败！');
        }
        return true;
    }
    
    /**
     * 通过店铺来随机获取一个商品推荐组
     */
    public function getRecommendDetailByStore($params)
    {
        if(!$params['store_id']){
            return [];
        }
        //查询店铺分类
        $catInfo = (new ShopCategoryRelation())->where('store_id',$params['store_id'])->select()->toArray();
        $whereOr = [];
        foreach ($catInfo as $v){
            $whereOr[] = [
                ['a.cat_id','=',$v['cat_id']],
                ['a.cat_fid','=',$v['cat_fid']]
            ];
        }
        $where = [['b.status','=',1],['b.is_del','=',0]];
        //获取拥有这些分类的所有推荐组
        $recommendList = (new ShopRecommendCategory())->getListByCategory($where,$whereOr,'b.id,b.scale');
        $scaleAry = array_column($recommendList,'scale');
        $allScale = array_sum($scaleAry);
        if(!$allScale){
            return [];
        }
        //根据比例随机获取推荐组
        $getRandRecommendId = $this->getRandByScale($allScale,$recommendList);
        if(!$getRandRecommendId){
            return [];
        }
        $recommendDetail = (new ShopRecommend())->getDetail(['id'=>$getRandRecommendId],'name');
        $recommendGoodsInfo = (new ShopRecommendGoods())->getList(['recommend_id'=>$getRandRecommendId,'is_del'=>0,'status'=>1],'id,goods_id,business');
        foreach ($recommendGoodsInfo as $kGoods=>&$vGoods){
            $vGoods['business_str'] = (new ShopRecommendGoods())->getBusinessAlias($vGoods['business']);//业务类型描述
            $goodsInfo = $this->getGoodsList(['goods_id'=>$vGoods['goods_id'],'business'=>$vGoods['business'],'now_city'=>$params['now_city']]);
            $goodsInfo = $goodsInfo['data'][0] ?? [];
            if(!$goodsInfo){
                unset($recommendGoodsInfo[$kGoods]);
                continue;
            }
            $vGoods['img'] = $goodsInfo['img']??'';//商品图片
            $vGoods['img'] = $vGoods['img'] ? replace_file_domain($vGoods['img']) : '';//商品图片
            $vGoods['goods_name'] = $goodsInfo['goods_name']??'';//商品名称
            $vGoods['price'] = $goodsInfo['price']??0;//商品价格
            $vGoods['is_sku'] = $goodsInfo['is_sku']??0;//是否是多规格
            $vGoods['distance_time'] = 0;//多久到达店铺
            $vGoods['distance_type'] = '小时';//时间单位
            if($vGoods['business'] == 'shop' && isset($goodsInfo['lat']) && isset($goodsInfo['long']) && $goodsInfo['lat'] && $goodsInfo['long'] ){
                //查询距离，先看定位，再看默认地址，都没有就不展示
                $vGoods['distance_time'] = 0;
                $vGoods['distance_type'] = '小时';
                $long = $params['user_long'];
                $lat = $params['user_lat'];
                if(!$long || !$lat){
                    //查询默认地址
                    if($params['userInfo'] && $params['userInfo']['openid']){
                        $user_long_lat = (new UserLongLatService())->getLocation($params['userInfo']['openid'], 0);
                        if($user_long_lat){
                            $long = $user_long_lat['long'];
                            $lat = $user_long_lat['lat'];
                        }
                    }
                }
                if($lat && $long){
                    $distance = get_distance($lat,$long,$goodsInfo['lat'],$goodsInfo['long']);//单位:m,人运行速度平均1m/s
                    if($distance < 3600){
                        $vGoods['distance_time'] = bcdiv($distance,60,0);
                        $vGoods['distance_type'] = '分钟';
                    }else{
                        $vGoods['distance_time'] = bcdiv($distance,3600,0);
                    }
                }
            }
            //跳转链接
            $vGoods['link'] = $goodsInfo['link'];
        }
        $returnAry = [
            'name'=>$recommendDetail['name'],
            'list'=>array_values($recommendGoodsInfo),
        ];
        return $returnAry;
    }
    
    /**
     * 根据比例获取随机数据
     */
    public function getRandByScale($allScale,$recommendList)
    {
        $id = 0;//最终获取到的id
        //查询每份所占比例，划分区段，例如：70%，5%，35%  随机数总区段是1~110，所属随机数区段分别是1~70,71~75,76~110
        $scaleAry = [];
        $min = 0;
        foreach ($recommendList as &$v){
            $scaleAry[$v['id']] = $v['scale'];
            $v['min'] = $min + 1;
            $v['max'] = $min + $v['scale'];
            $min = $v['max'];
        }
        
        //获取随机数，抽中数字属于哪个区段，则选中该区段
        $num = rand(1,$allScale);
        foreach ($recommendList as $vList){
            if($num <= $vList['max'] && $num >= $vList['min']){
                $id = $vList['id'];
                break;
            }
        }
        return $id;
    }
}