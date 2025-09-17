<?php
/**
 * 商家和用户关系数据处理
 * Author: chenxiang
 * Date Time: 2020/5/25 18:01
 */

namespace app\common\model\service;

use app\appoint\model\service\goods\AppointImageService;
use app\appoint\model\service\goods\AppointService;
use app\common\model\db\MerchantUserRelation;
use app\group\model\service\GroupService;
use app\group\model\service\StoreGroupService;
use app\grow_grass\model\service\GrowGrassArticleCollectService;
use app\grow_grass\model\service\GrowGrassBindStoreService;
use app\merchant\model\service\MerchantStoreService;
use app\merchant\model\service\store\MerchantCategoryService;
use app\merchant\model\service\storeImageService;
use app\shop\model\service\goods\ShopGoodsService;
use map\longLat;

class MerchantUserRelationService
{
    public $merchantUserRelationObj = null;

    public function __construct()
    {
        $this->merchantUserRelationObj = new MerchantUserRelation();
    }
    
	/**
     * 删除收藏
     * User: hengtingmei
	 * Date: 2021/5/18
     */
	public function delCollect($param){
		$collectId = $param['collect_id'] ?? 0;
        if(empty($collectId)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }

        $where = [
            'id' => $collectId
        ];
		$res = $this->delMerUserReal($where);
        if($res === false){
            throw new \think\Exception(L_('删除失败'), 1003);
        }
		return ['msg' => L_('删除成功')];
	}

    /**
     * 删除收藏
     * User: hengtingmei
	 * Date: 2021/5/18
     */
	public function getCollectTab($param){
		$returnArr['list'] = [];
		$user = request()->user;
        $uid = $user['uid'] ?? 0;
        $uid = isset($param['uid'])&& $param['uid'] ? $param['uid'] :  $uid;

        // 店铺总数
        $where['c.uid'] = $uid;
        $storeCount = $this->getStoreCollectCount($where);

        
        // 商品总数
        $whereCollect = [];
        $whereCollect[] = ['uid', '=', $uid];
        $whereCollect[] = ['type', 'in', ['group','appoint','mallgoods']];
        $goodsCount = $this->getCount($whereCollect);


        // 文章总数
        $where = [];
        $where['c.uid'] = $uid;
        $where['c.is_del'] = 0;
        $articleCount = (new GrowGrassArticleCollectService())->getCount($where);


        $returnArr['list'] = [
            [
                'title' => L_('店铺'),
                'type' => 'store',
                'count' => $storeCount,
            ],
            [
                'title' => L_('文章'),
                'type' => 'article',
                'count' => $articleCount,
            ],
            [
                'title' => L_('商品'),
                'type' => 'goods',
                'count' => $goodsCount,
            ],
        ];
		return $returnArr;
	}
    
    /**
     * 个人中心收藏列表
     * User: chenxiang
     * Date: 2020/5/25 19:58
     * @param array $where
     * @return array|mixed|\think\Model|null
     */
    public function getCollectList($param = []){
        $type = $param['type'] ?? 'store';
        $page = $param['page'] ?? '1';
        $pageSize = $param['pageSize'] ?? 10;
        $lng = $param['user_lng'] ?? '';
        $lat = $param['user_lat'] ??  '';

        // 用户信息
		$user = request()->user;
        $uid = $user['uid'] ?? 0;
        $uid = isset($param['uid'])&& $param['uid'] ? $param['uid'] :  $uid;
        
        $returnArr = [];
        $returnArr['list'] = [];
        $returnArr['page_size'] = $pageSize;
        switch ($type) {
            case 'store':// 店铺
                $where['c.uid'] = $uid;
                $returnArr = $this->getStoreCollectList($where,'c.*,s.*',[], $page, $pageSize, $lng, $lat);
                break;
            case 'goods':// 商品
                // 查询收藏数据
                $whereCollect = [];
                $whereCollect[] = ['uid', '=', $uid];
                $whereCollect[] = ['type', 'in', ['group','appoint','mallgoods']];
                $collectList = $this->getSome($whereCollect, true, ['id' => 'desc'], ($page-1)*$pageSize, $pageSize);

                // 本次查询到的条数
                $count = count($collectList);

                // 获得商品详情
                $goodsList = [];
                if($collectList){
                    foreach($collectList as $goods){
                        switch($goods['type']){
                            case 'group':// 团购
                                $where = [
                                    'group_id' => $goods['group_id']
                                ];
                                $tempGoods = (new GroupService())->getOne($where);
                                if($tempGoods){
                                    $tempGoods = (new GroupService())->dealCommonInfo($tempGoods);
                                    $goodsList[] = [
                                        'collect_id' => $goods['id'],
                                        'name' => $tempGoods['s_name'],
                                        'goods_id' => $tempGoods['group_id'],
                                        'describe' => $tempGoods['name'],
                                        'price' => get_format_number($tempGoods['price']),
                                        'sell_count' => $tempGoods['sale_count'],
                                        'image' => $tempGoods['image'][0] ?? '',
                                        'url' => get_base_url('',1).'pages/group/v1/groupDetail/index?group_id='.$tempGoods['group_id']
                                    ];
                                }else{
                                    $this->delMerUserReal(['id'=>$goods['id']]);
                                }
                                
                                break;
                            case 'appoint':// 预约 
                                $where = [
                                    'appoint_id' => $goods['appoint_id'],
                                ];
                                $tempGoods = (new AppointService())->getOne($where);
                                $appointImageService = new AppointImageService();

                                if($tempGoods){

                                    $tmp_pic_arr = explode(';',$tempGoods['pic']);
                                    $tempGoods['image'] = $appointImageService->getImageByPath($tmp_pic_arr[0],'s');

                                    $goodsList[] = [
                                        'collect_id' => $goods['id'],
                                        'name' => $tempGoods['appoint_name'],
                                        'goods_id' => $tempGoods['appoint_id'],
                                        'describe' => $tempGoods['appoint_content'],
                                        'price' => get_format_number($tempGoods['appoint_price']),
                                        'sell_count' => $tempGoods['appoint_sum'],
                                        'image' => $tempGoods['image'] ?? '',
                                        'url' => cfg('site_url').get_base_url().'pages/appoint/goods/productDetail?appoint_id='.$tempGoods['appoint_id']
                                    ];
                                }else{
                                    $this->delMerUserReal(['id'=>$goods['id']]);
                                }
                                break;
                            case 'mallgoods':// 旧版商城
                                $where['goods_id'] =  $goods['goods_id'];
                                
                                $tempGoods = (new ShopGoodsService())->getGoodsDetail($where);
                                if($tempGoods && $tempGoods['cat_fid'] > 0 ){
                                    $goodsList[] = [
                                        'collect_id' => $goods['id'],
                                        'name' => $tempGoods['name'],
                                        'goods_id' => $tempGoods['goods_id'],
                                        'describe' => $tempGoods['describe'],
                                        'price' => get_format_number($tempGoods['price']),
                                        'sell_count' => $tempGoods['sell_count'],
                                        'image' => $tempGoods['image_url'] ?? '',
                                        'url' => cfg('site_url').'/wap.php?g=Wap&c=Mall&a=detail&goods_id='.$tempGoods['goods_id']
                                    ];
                                }else{
                                    $this->delMerUserReal(['id'=>$goods['id']]);
                                }
                                break;
                        }
                    }
                }

                $returnArr['list'] = $goodsList;

                // 防止前端不在继续加载
                $nowCount = count($goodsList);
                if($count >= $pageSize && $nowCount < $count){
                    $returnArr['page_size'] = $nowCount;
                }
                break;
            case 'article':// 文章
                $where['c.uid'] = $uid;
                $where['c.is_del'] = 0;
                $returnArr = (new GrowGrassArticleCollectService())->getCollectList($where,'',[], $page, $pageSize);

                if($returnArr['list']){
                    foreach($returnArr['list'] as &$article){
                        // 绑定店铺列表
                        $article['store_list'] = (new GrowGrassBindStoreService())->getBindStoreList($article['article_id'], $lng, $lat);
                
                    }

                }
                
                break;
        }
        $returnArr['type'] = $type;
        return $returnArr;
    }

    /**
     * 获取商家和用户的关系 数据
     * User: chenxiang
     * Date: 2020/5/25 19:58
     * @param array $where
     * @return array|mixed|\think\Model|null
     */
    public function getDataMerUserRel($where = [])
    {
        $result = $this->merchantUserRelationObj->getDataMerUserRel($where);
        return $result;
    }

    /**
     * 添加商家和用户关系信息
     * User: chenxiang
     * Date: 2020/5/25 20:04
     * @param array $data
     * @return int|string
     */
    public function addMerUserRel($data = [])
    {
        $result = $this->merchantUserRelationObj->addMerUserRel($data);
        return $result;
    }

    /**
     * 删除商家和用户关系信息
     * User: 朱梦群
     * Date: 2020/11/11 20:04
     * @param array $data
     * @return int|string
     */
    public function delMerUserReal($where)
    {
        if (empty($where)) {
            throw new \think\Exception('缺少参数');
        }
        $result = $this->merchantUserRelationObj->delOne($where);
        return $result;
    }

    /**
     * 获取粉丝数
     * User: 朱梦群
     * Date: 2020/11/11 20:04
     * @param array $data
     * @return int|string
     */
    public function get_merchant_fans($where)
    {
        $count = $this->merchantUserRelationObj->get_merchant_fans($where);
        return $count;
    }

    /**
     * 获得用户收藏的店铺列表
     * User: hengtingmei
     * Date: 2021/05/18 
     * @param array $where
     * @param string $field
     * @param array $order
     * @param int $page
     * @param int $pageSize
     * @return array
     */
	public function getStoreCollectList($where,  $field='c.*,s.*', $order = ['c.id'=>'DESC'], $page=1, $pageSize = 10, $lng='', $lat=''){

        $storeList = $this->merchantUserRelationObj->getStoreCollectList($where, $field, $order, $page, $pageSize, $lng, $lat);
        $storeList = $storeList ? $storeList->toArray() : [];

        $list = $this->formatData($storeList, $lng, $lat);
		
		$return['list'] = $list ?: [];
		$return['page_size'] = $pageSize;

		return $return;
	}

    /**
     * 获得用户收藏的店铺总数
     * User: hengtingmei
     * Date: 2021/05/28 
     * @param array $where
     * @return array
     */
	public function getStoreCollectCount($where){

        $count = $this->merchantUserRelationObj->getStoreCollectCount($where);
        if(empty($count)){
            return 0;
        }

		return $count;
	}

        /** 处理店铺显示字段
     * @param array $list 店铺列表
     * @param string $long 
     * @param string $lat 
     * @param array $extro // 其他参数
     * @return array
     *
     */
    public function formatData($list = [], $long = '', $lat = ''){
        $returnArr = [];

        foreach($list as $store){
            $tempStore = [];
            $tempStore['store_id'] = $store['store_id'];
            $tempStore['collect_id'] = $store['id'];
            //店铺名称
            $tempStore['name'] = $store['name'];
            //评分
            $tempStore['score'] = $store['score']==0 ? 5.0 : $store['score'];
           
            $tempStore['long'] = $store['long'];
            $tempStore['lat'] = $store['lat'];
            //惠(团购)
            $tempStore['group_goods_str'] = '';

            // 店铺图片
            $images = (new storeImageService())->getAllImageByPath($store['pic_info']);
            $tempStore['image'] = $images ? thumb(array_shift($images),180) : '';

            //距离
            if (isset($store['distance'])&&$store['distance']) {
                $tempStore['range'] = get_range($store['distance']);
            } else if($long && $lat){
                $location2 = (new longLat())->gpsToBaidu($store['lat'], $store['long']);//转换腾讯坐标到百度坐标
                $jl = get_distance($location2['lat'], $location2['lng'], $lat, $long);
                $tempStore['range'] = get_range($jl);
            }else{
                $tempStore['range'] = '';
            }
         
            // 店铺地址
            $tempStore['address'] = isset($store['address']) ? $store['address'] : $store['adress'];

            $tempStore['url'] = get_base_url('',1).'pages/store/v1/home/index?store_id='.$store['store_id'];

            // 店铺分类
            $tempStore['cate_name'] = '';
            if(isset($store['cat_fid']) && $store['cat_fid']){
                $cate = (new MerchantCategoryService())->getOne(['cat_id'=>$store['cat_fid']]);
                $tempStore['cate_name'] = $cate['cat_name'] ?? '';
            }
            if(isset($store['cat_id']) && $store['cat_id']){
                $cate = (new MerchantCategoryService())->getOne(['cat_id'=>$store['cat_id']]);
                $cate['cat_name'] = $cate['cat_name']?? '';
                $tempStore['cate_name'] = $tempStore['cate_name'] ? $tempStore['cate_name'].'/'.$cate['cat_name'] : $cate['cat_name'];
            }

            // 商圈
            if(isset($store['cat_id']) && $store['circle_id']){
                $area = (new AreaService())->getAreaByAreaId($store['circle_id']);
                $tempStore['area_name'] = $area['area_name'];
            }
           
            $returnArr[] =  $tempStore;
        }
		

        //惠(团购)
        $storeGroupService = new StoreGroupService();
        $storeIdArr = array_column($list,'store_id');
        // 店铺ID
        $storeIdStr = implode(',', $storeIdArr);

        if ($storeIdArr) {
			// 店铺ID
			$storeIdStr = implode(',', $storeIdArr);
            // 团购列表
            $field = 'distinct a.store_id, g.group_id, g.name,g.price';
            $groupList = $storeGroupService->getNormalStoreGroup($storeIdStr,$field,['g.price' => 'ASC']);
            foreach ($groupList as $row) {
                foreach($returnArr as $key => $store){
                    if ($row['store_id'] == $store['store_id']) {
                        $returnArr[$key]['group_goods_str'] = $row['name'];
                    }
                }
               
            }
            
			
		}

    
		return array_values($returnArr);

    }

     /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $id = $this->merchantUserRelationObj->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     * 更新数据
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data){
        if(empty($data) || empty($where)){
            return false;
        }

        try {
            $result = $this->merchantUserRelationObj->updateThis($where, $data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where, $order = []){
        if(empty($where)){
            return [];
        }

        $result = $this->merchantUserRelationObj->getOne($where, $order);
        if(empty($result)){
            return [];
        }

        return $result->toArray();
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $result = $this->merchantUserRelationObj->getSome($where, $field, $order, $page, $limit);
        return $result->toArray();
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getCount($where){
        $count = $this->merchantUserRelationObj->getCount($where);
        if(!$count) {
            return 0;
        }
        return $count;
    }

}
