<?php
/**
 * 餐饮商品service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/19 10:43
 */

namespace app\foodshop\model\service\goods;
use app\foodshop\model\db\FoodshopGoodsLibrary as FoodshopGoodsModel;
use app\foodshop\model\db\MerchantStoreFoodshop;
use app\foodshop\model\service\goods\FoodshopGoodsSortService as FoodshopGoodsSortService;
use app\merchant\model\service\MerchantStoreOpenTimeService;
use app\merchant\model\service\print_order\OrderprintListService;
use app\merchant\model\service\print_order\OrderprintService;
use app\shop\model\service\goods\ShopGoodsService as ShopGoodsService;
use app\shop\model\service\goods\GoodsImageService as  GoodsImageService;
use app\shop\model\service\store\MerchantStoreShopService as  MerchantStoreShopService;
use app\shop\model\service\goods\ShopSubsidiaryPieceService as  ShopSubsidiaryPieceService;
use app\foodshop\model\service\store\MerchantStoreFoodshopService as MerchantStoreFoodshopService;
use app\foodshop\model\service\store\MerchantStoreFoodshopDataService as MerchantStoreFoodshopDataService;
use app\merchant\model\service\MerchantStoreService;
use app\shop\model\service\goods\ShopSubsidiaryPieceGoodsService;
use app\foodshop\model\service\package\FoodshopGoodsPackageService;
use think\facade\Db;
class FoodshopGoodsLibraryService {
    public $foodshopGoodsModel = null;
    public $weekList = [];
    public $unweekList = [];
    const TABLE_NAME = 'foodshop_goods_library';
    public function __construct()
    {
        $this->foodshopGoodsModel = new FoodshopGoodsModel();

        $this->weekList = [
            '1' => L_('星期一'),
            '2' => L_('星期二'),
            '3' => L_('星期三'),
            '4' => L_('星期四'),
            '5' => L_('星期五'),
            '6' => L_('星期六'),
            '0' => L_('星期日'),
        ];

        $this->unweekList = [
            L_('星期一') => '1',
            L_('星期二') => '2',
            L_('星期三') => '3',
            L_('星期四') => '4',
            L_('星期五') => '5',
            L_('星期六') => '6',
            L_('星期日') => '0',
        ];
    }
    
    /**
     * 获得店铺橱窗店铺信息
     * @param $areaId
     * @return array
     */
    public function getStoreIndex($storeId, $user)
    {
        if(empty($storeId)){
			throw new \think\Exception(L_("缺少参数"));
        }

        // 验证店铺状态
        // 不验证营业时间
        $foodshop = (new MerchantStoreFoodshopService())->checkStore($storeId,true,false);
        

        // 返回数组
        $returnArr = [];

        // 获得橱窗信息
        $returnArr = (new MerchantStoreFoodshopDataService())->getStoreDataInfo($storeId);

        // 店铺信息
        $returnArr['store_info']['name'] = $foodshop['name'];
        $returnArr['store_info']['store_id'] = $foodshop['store_id'];
        $returnArr['store_info']['logo'] = !empty($foodshop['image'])?thumb_img($foodshop['image'],112,112,'fill'):'';
        $returnArr['store_info']['score_mean'] = !empty($foodshop['score_mean'])?$foodshop['score_mean']:5.0; //评价平均分数
        $returnArr['store_info']['month_sale'] = 0; //月售 暂无字段 先传0
        $returnArr['store_info']['store_notice'] = $foodshop['store_notice']; //公告

        //营业时间
        $returnArr['store_info']['time_arr'] = (new MerchantStoreOpenTimeService())->getShowTimeByStore($storeId);
        // 下一个营业时间段
        $returnArr['store_info']['next_time'] = (new MerchantStoreOpenTimeService())->getNextTime($storeId);
        // 是否关店
        $returnArr['store_info']['is_close'] = $foodshop['is_business_open'] ? 0 : 1;

        // 分享信息
        $returnArr['share_info']['title'] = L_('X1喊你一起点餐~',['X1'=>$user['nickname']]);
        $returnArr['share_info']['desc'] = L_('点击立即参与');
//        if(!empty($foodshop['logo'])){
//        	$returnArr['share_info']['image'] = $foodshop['logo'];
//		}elseif(!empty($now_store['pic_info'])){
        	$returnArr['share_info']['image'] = thumb_img($foodshop['image'],'200','200');
//		}

        //餐饮优惠
        $returnArr['foodshop_discount'] = [];
        $foodshop_discount = (new \app\merchant\model\service\ShopDiscountService())->getDiscounts($foodshop['mer_id'], $foodshop['store_id'], '',0,1);
        if($foodshop_discount){
            $foodshop_discount = (new \app\merchant\model\service\ShopDiscountService())->formartDiscount($foodshop_discount, $foodshop['store_id']);
            $foodshop_discount = (new \app\merchant\model\service\ShopDiscountService())->simpleParseCoupon($foodshop_discount['coupon_list']);
            $returnArr['foodshop_discount'] = $foodshop_discount; //优惠信息
        }

        return $returnArr;
    }

    /**
     * 获得商品列表
     * @param $where
     * @return array
     */
    public function getGoodsList($where = [], $showTabs = 0)
    {
        $limit = isset($where['limit']) ? $where['limit'] : 0;//每页显示数量
        $page = request()->param('page', '1', 'intval');//页码
        $shopGoodsService = new ShopGoodsService();

        // 构造查询条件
        $condition = [];
        
        // 排序
        $order = [
            't.sort' => 'DESC',
            't.goods_id' => 'ASC',
        ];

        // 是否必点菜
        if(isset($where['is_must']) && $where['is_must']){
            $condition[] = ['t.is_must', '=', $where['is_must']];
        }

        // 搜索商品状态
        if(isset($where['status']) && $where['status']){
            $condition[] = ['t.status', '=', $where['status']];
        }

        // 搜索商品名称
        if(isset($where['name']) && $where['name']){
            if (cfg('open_multilingual') && cfg('system_lang') != 'chinese') {
                $condition[] = ['s.name','exp',Db::raw('like "%' . $where['name'] . '%" OR s.name_'.cfg('system_lang').' like "%'.$where['name'].'%"')];
            } else {
                $condition[] = ['s.name','like','%' . $where['name'] . '%'];
            }
        }

        // 根据商品id查询
        if(isset($where['goods_ids']) && $where['goods_ids']){
            $condition[] = ['s.goods_id','in', $where['goods_ids']];
        }

        // 根据商品id查询
        if(isset($where['pigcms_id']) && $where['pigcms_id']){
            $condition[] = ['t.pigcms_id','in', $where['pigcms_id']];
        }

        // 店铺id
        if(isset($where['store_id']) && $where['store_id']){
            $condition[] = ['t.store_id','=', $where['store_id']];
        }

        //统计条件（不带状态条件）
        $countWhereBase = $condition;

        // 分类id
        if(isset($where['sort_id']) && $where['sort_id']){
            $condition[] = ['t.spec_sort_id','=', $where['sort_id']];
        }

        // 是否有附属商品 1有 0没有
        if(isset($where['is_subsidiary_goods'])){
            $condition[] = ['s.is_subsidiary_goods','=', $where['is_subsidiary_goods']];
        }

        // 状态
        $tabs = [];
        if($showTabs){
            // 商家后台，返回统计tabs
            // 全部不带分类
            $countWhere = $countWhereBase;
            $total = $shopGoodsService->getGoodsCountByModule(self::TABLE_NAME, $countWhere);

            // 售卖中
            $countWhere = $countWhereBase;
            $countWhere[] =  ['t.status','=', 1];
            $sellTotal = $shopGoodsService->getGoodsCountByModule(self::TABLE_NAME, $countWhere);
            // 已下架
            $countWhere = $countWhereBase;
            $countWhere[] =  ['t.status','=', 0];
            $sellDownTotal = $shopGoodsService->getGoodsCountByModule(self::TABLE_NAME, $countWhere);
            // 售卖中
            $countWhere = $countWhereBase;
            $countWhere[] = ['t.spec_stock|s.stock_num','=', 0];
            $sellOutTotal = $shopGoodsService->getGoodsCountByModule(self::TABLE_NAME, $countWhere);
            $tabs = [
                '0' => L_('全部商品（X1）',['X1'=>$total]),
                '1' => L_('售卖中（X1）',['X1'=>$sellTotal]),
                '2' => L_('已下架（X1）',['X1'=>$sellDownTotal]),
                '3' => L_('已售完（X1）',['X1'=>$sellOutTotal]),
            ];
        }
        if(isset($where['order_status']) && $where['order_status']){
            unset($countWhere);

            switch ($where['order_status']){
                case '0'://全部商品
                    break;
                case '1'://售卖中
                    $condition[] = ['t.status','=', 1];
                    break;
                case '2'://已下架
                    $condition[] = ['t.status','=', 0];
                    break;
                case '3'://已售完
                    $condition[] = ['t.spec_stock|s.stock_num','=', 0];
                    break;
            }
            $condition[] = ['t.spec_sort_id','=', $where['sort_id']];
        }


        // 商品列表
        $goodsList = $shopGoodsService->getGoodsListByModule(self::TABLE_NAME, $condition, $order, $limit, $page);

        // 查看分类相关信息
        $sortListArr = [];
        if(isset($where['select_sort'])&&$where['select_sort']){
            $sortIdArr = array_column($goodsList, 'spec_sort_id');
            $whereSort['sort_id'] = implode(',', $sortIdArr);
            $sortList = (new FoodshopGoodsSortService())->getList($whereSort);
            $sortListArr = [];
            foreach($sortList as $sort){
                $sortListArr[$sort['sort_id']] = $sort;
            }
        }

        $goodsImageService = new GoodsImageService();
        foreach($goodsList as &$goods){
            if($sortListArr){
                // 分类打印机配置
                $goods['is_open_print'] = $sortListArr[$goods['spec_sort_id']]['is_open_print'];
                $goods['sort_print_id'] = $sortListArr[$goods['spec_sort_id']]['print_id'];
            }
            $tmpPicArr = $goodsImageService->getAllImageByPath($goods['image'], 's');
            $goods['product_image'] = $goodsImageService->getOneImage($tmpPicArr);
            $goods['product_image'] = thumb_img($goods['product_image'],180,180,'fill');
            if($showTabs){
                // 多规格显示最低价
                $specPrice = $this->getGoodsSpecPrice($goods);
                $goods['min_price'] = $specPrice['min_price'];
            }
        }


        $returnArr['list'] = $goodsList;
        $returnArr['tabs'] = $tabs;
        return $returnArr;
    }
    


    /**
     * 根据店铺id获得商品列表
     * @param $storeId
     * @param array $where
     * @param string $plat
     * @param string $type
     * @return array
     */
    public function getGoodsListByStoreId($storeId, $where = [], $plat = 'wap', $typeInfo = [], $userType='storestaff')
    {
        // 分类列表
        $foodshopGoodsSortService = new FoodshopGoodsSortService();

        $sortList = $foodshopGoodsSortService->getShowSortListByStoreId($storeId);

        $limit = 0;//每页显示数量
        $page = request()->param('page', '1', 'intval');//页码

        // 构造查询条件
        $condition[] = ['t.store_id','=',$storeId];
        if($plat == 'wap'){
            $condition[] = ['t.status','=',1];
        }
        
        // 排序
        $order = [
            't.sort' => 'DESC',
            't.goods_id' => 'ASC',
        ];

        // 搜索商品名称
        if(isset($where['name']) && $where['name'] !== ''){
            if (cfg('open_multilingual') && cfg('system_lang') != 'chinese') {
                $condition[] = ['s.name','exp',Db::raw('like "%' . $where['name'] . '%" OR s.name_'.cfg('system_lang').' like "%'.$where['name'].'%"')];
            } else {
                $condition[] = ['s.name','like','%' . $where['name'] . '%'];
            }
        }

        // 搜索关键字
        if(isset($where['keyword']) && $where['keyword'] !== ''){
            $where['keyword'] = addslashes($where['keyword']);
            if (cfg('open_multilingual') && cfg('system_lang') != 'chinese') {
                $condition[] = ['s.name','exp',Db::raw('like "%' . $where['keyword'] . '%" OR s.name_'.cfg('system_lang').' like "%'.$where['keyword']. '%" OR t.abbreviation like "%'.$where['keyword'].'%"  OR t.abbreviation_first_word like "%' . $where['keyword'] . '%"  OR s.name_first_word like "%' . $where['keyword'] . '%" OR s.goods_name_first_spell like "%' . $where['keyword'] . '%"')];
            } else {
                $condition[] = ['s.name','exp',Db::raw('like "%' . $where['keyword'] . '%" OR t.abbreviation like "%'.$where['keyword'].'%"  OR t.abbreviation_first_word like "%' . $where['keyword'] . '%"  OR s.name_first_word like "%' . $where['keyword'] . '%" OR s.goods_name_first_spell like "%' . $where['keyword'] . '%"')];
            }
            $limit = 0;
        }

        // 必点菜
        if(isset($where['is_must']) ){
            $condition[] = ['t.is_must','=',$where['is_must']];
        }

        // 根据商品id查询
        if(isset($where['goods_ids']) && $where['goods_ids']){
            $condition[] = ['s.goods_id','in', $where['goods_ids']];
        }

        // 根据商品库存查询
        if(isset($where['spec_stock'])){
            $condition[] = ['t.spec_stock','=', $where['spec_stock']];
            $where['spec_stock']==0 && $condition[] = ['t.is_must','=',0];
        }

        $isClearStock = $where['is_clear_stock'] ?? 0;//是否估清列表
        if($isClearStock){
            // 估清列表不显示必点菜
           $condition[] = ['t.is_must','=',0];
        }

        // 商品列表
        $shopGoodsService = new ShopGoodsService();
        $goodsList = $shopGoodsService->getGoodsListByModule(self::TABLE_NAME, $condition, $order, $limit, $page);

        if($plat == 'wap'){
            // 获得前端需要商品字段
            foreach ($goodsList as &$_good) {
                $_good = $this->getFormartData($_good, 0, $isClearStock);
            }

            if(isset($where['show_type']) && $where['show_type'] == 'tree'){
                if(isset($typeInfo) && !empty($typeInfo)) { //热销 和 优惠分类
                    $temp = [];
                    $temp[0]['cat_id'] = $typeInfo['cat_id'];
                    $temp[0]['cat_name'] = $typeInfo['cat_name'];
                    $temp[0]['desc'] = $typeInfo['desc'] ?? '';
                    $temp[0]['goods_list'] = $goodsList;

                    $goodsList = $temp;
                } else {
                    // 将分类与商品组装
                    $goodsList = $foodshopGoodsSortService->getSortGoodsTree($sortList, $goodsList);
                }
                
            }
        }

        //是否组装套餐菜品列表 1：不组装 2：组装
        $package_flag = 1;
        if (!isset($where['spec_stock'])) {
            $package_flag = 2;
        }

        if (!empty($typeInfo)) {
            $package_flag = 1;
        }
        if ($package_flag == 2) {
            // 组装套餐数据
            $package_param = [];
            $package_param['store_id'] = $storeId;
            // 搜索关键字
            if(isset($where['keyword']) && $where['keyword'] !== ''){
                $package_param['keyword'] = $where['keyword'];
            }
            // 搜索关键字
            if(isset($where['name']) && $where['name'] !== ''){
                $package_param['keyword'] = $where['name'];
            }
            $foodshopGoodsPackageService = new FoodshopGoodsPackageService();
            $package_param['user_type'] = $userType;
            $package_count = $foodshopGoodsPackageService->getPackagerCount($package_param);
            if ($package_count) {
                $package_field = 'p.id,p.`name`,p.price,p.image,d.pid,d.goods_detail,d.package_name';
                $package_list = $foodshopGoodsPackageService->getPackageDetailList($package_param, $package_field);
                $show_type = isset($where['show_type']) ? $where['show_type'] : 'list';
                $packageList = $foodshopGoodsPackageService->getPackageGoodsTree($show_type, $package_list);
                $goodsList = array_merge($goodsList, $packageList);
            }
        }
        
        return $goodsList;
    }



    /**
     * 批量更新商品信息
     * @param $param
     * @return array
     */
    public function editGoods($param, $merchantUser){
        $pigcmsId = $param['pigcms_id'] ?? 0;
        $indexs = $param['indexs'] ?? 0;
        $prices = $param['prices'] ?? 0;
        $stock_nums = $param['stock_nums'] ?? 0;

        if (empty($param['goods_id']) || empty($param['store_id'])) {
            throw new \think\Exception(L_("缺少参数"),1001);
        }

        if ($param['price'] === '' ) {
            throw new \think\Exception(L_("商品价格可以设置为0，但是必填！"),1003);
        }
        if ($param['price'] < 0 ) {
            throw new \think\Exception(L_("商品价格必须大于或等于0！"));
        }
//        if ($param['old_price']>0 && $param['old_price'] < $param['price'] ) {
//            throw new \think\Exception(L_("商品原价不能小于现价！"),1003);
//        }

        // 验证时间
        if(!$param['all_date']){
            if(!$param['show_start_date'] || !$param['show_end_date']){
                throw new \think\Exception(L_("请填写自定义日期"),1003);
            }
            $param['show_start_date'] = strtotime($param['show_start_date']);
            $param['show_end_date'] = strtotime($param['show_end_date']);
            if($param['all_time']==0){
                if($param['show_start_time'] > $param['show_end_time']){
                    throw new \think\Exception(L_("开启时间不能大于结束时间"),1003);
                }
                if($param['show_start_time2'] > $param['show_end_time2']){
                    throw new \think\Exception(L_("开启时间不能大于结束时间"),1003);
                }
                if($param['show_start_time3'] > $param['show_end_time3']){
                    throw new \think\Exception(L_("开启时间不能大于结束时间"),1003);
                }
            }
        }
        // 库存类型1-沿用商品库商品库存2-独有库存
        if($param['spec_stock_type']==1){
            $param['spec_stock'] = -1;
            $param['spec_original_stock'] = -1;
        }
        unset($param['date_range']);
        unset($param['name']);
        if(isset($param['indexs'])){
            unset($param['indexs']);
            unset($param['prices']);
            unset($param['stock_nums']);
        }
        $weekArr = [];
        if(isset($param['week']) && $param['week']){
            foreach ($param['week'] as $week){
                $weekArr[] = $this->unweekList[$week];
            }
        }
        $param['week'] = implode(',',  $weekArr);

        // 简称首字母
        isset($param['abbreviation']) && $param['abbreviation_first_word']  = get_first_charter($param['abbreviation']);

        // 查询商品是否已经添加过
        $whereGoods = [
            'goods_id' => $param['goods_id'],
            'store_id' => $param['store_id'],
        ];
        $goods = $this->getOne($whereGoods);

        if($goods){
            $pigcmsId = $goods['pigcms_id'];
            //编辑
            $where = [
                'pigcms_id' =>$goods['pigcms_id']
            ];
            $res = $this->updateThis($where, $param);
        }else{
            // 新增
            $pigcmsId = $res = $this->add($param);
        }

        // 规格保存
        if($indexs){
            $where = [
                'store_id' => $param['store_id'],
                'goods_id' => $param['goods_id']
            ];
            (new FoodshopGoodsSkuService())->del($where);

            $data = [];
            foreach ($indexs as $key => $value) {
                $data[] = [
                    'goods_id' => $param['goods_id'],
                    'store_id' => $param['store_id'],
                    'index' => $value,
                    'price' => $prices[$key],
                    'spec_stock' => $stock_nums[$key],
                    'spec_original_stock' => $stock_nums[$key],
                    'last_time' => time(),
                ];
            }
//            var_dump($data);
            (new FoodshopGoodsSkuService())->addAll($data);
        }

        if($res===false){
            throw new \think\Exception(L_("操作失败请重试"),1003);

        }
        return true;
    }

    /**
     * 批量添加商品
     * @param $param
     * @return array
     */
    public function addGoods($param, $merchantUser){
        $storeId = $param['store_id'] ?? 0;
        $sortId = $param['sort_id'] ?? 0;
        $goodsId = $param['goods_id'] ?? [];


        if (empty($storeId) || empty($sortId) || empty($goodsId)) {
            throw new \think\Exception(L_("缺少参数"),1001);
        }

        // 验证店铺和分类
        $store = (new MerchantStoreFoodshop())->getStoreByStoreId($storeId);
        if(empty($store)){
            throw new \think\Exception(L_("店铺不存在"),1003);
        }

        $where = [
            'sort_id' => $sortId
        ];
        $sort = (new FoodshopGoodsSortService())->getOne($where);
        if(empty($sort)){
            throw new \think\Exception(L_("分类不存在"),1003);
        }

        // 查看商品是否已经添加过了
        $where = [
            'store_id' => $storeId
        ];
        $foodshopGoods = $this->getSome($where,'goods_id');
        $foodshopGoodsId = array_column($foodshopGoods,'goods_id');

        // 查询商品库商品
        $condition = [];
        $condition[] = ['store_id','=', $storeId];
        $condition[] = ['goods_id','in', $goodsId];
        $order = [
            'sort' => 'DESC',
            'goods_id' => 'ASC',
        ];
        $goodsInfo = (new ShopGoodsService())->getGoodsListByCondition($condition, $order, 0);

        $foodshopGoods = []; // 添加的商品数组
        $tm = time();
        foreach ($goodsInfo as $v) {
            if(in_array($v['goods_id'],$foodshopGoodsId)){
                // 已经添加过不能重复添加
                continue;
            }
            $foodshopGoods[] = [
                'store_id' => $storeId,
                'last_time' => $tm,
                'goods_id' => $v['goods_id'],
                'spec_stock' => -1,
                'spec_original_stock' => $v['original_stock'],
                'spec_sort_id' => $sortId,
                'price' => $v['price'],
                'old_price' => $v['old_price'],
                'all_date' => 1,
                'all_time' => 1,
                'status' => 0,
            ];
        }
        $res = $this->addAll($foodshopGoods);

        if($res===false){
            throw new \think\Exception(L_("操作失败请重试"),1003);

        }
        return ['msg'=>'添加成功'];
    }

    /**
     * 批量更新商品信息
     *  1-沽清，2-置满，3-修改库存，4-上架，5-下架，6-删除，7-修改分类，
     * @param $param
     * @return array
     */
    public function editGoodsBatch($param, $merchantUser=[]){
        $goodsId = isset($param['goods_id'])&&$param['goods_id'] ? implode(',',$param['goods_id']) : '';
        $pigcmsId = isset($param['pigcms_id']) && $param['pigcms_id'] ? implode(',',$param['pigcms_id']) : '';
        $storeId = $param['store_id'] ?? 0;
        $stockNum = $param['stock_num'] ?? '';//等于full时表示要更改跟原始库存一样的值
        $originalStock = $param['original_stock'] ?? '';//原始库存
        $type = $param['type'] ?? '';// 修改类型：1-沽清，2-置满，3-修改库存，4-上架，5-下架，6-删除，7-修改分类
        $sortId = $param['sort_id'] ??  0;
        $name = $param['name'] ??  0;
        $index = $param['index'] ?? '';

        if(empty($storeId)){
            throw new \think\Exception(L_("店铺id不存在"), 1001);
        }

        if($merchantUser){
            $where['mer_id'] = $merchantUser['mer_id'];
        }
        $where['store_id'] = $storeId;
        $store = (new MerchantStoreService())->getOne($where);
        if(empty($store)){
            throw new \think\Exception(L_("店铺不存在"), 1001);
        }

        if((empty($goodsId)&&empty($pigcmsId)) || empty($type)){
            throw new \think\Exception(L_("缺少参数"), 1001);
        }

        // 商品列表
        $where = [
            ['store_id' , '=', $storeId],
        ];
        $whereGoods['store_id'] = $storeId;
        if($pigcmsId){
            //商品主键id
            $where[] = ['pigcms_id' , 'in', $pigcmsId];
            $whereGoods['pigcms_id'] = $pigcmsId;
        }elseif($goodsId){
            //商品库id
            $where[] = ['goods_id' , 'in', $goodsId];
            $whereGoods['goods_ids'] = $goodsId;
        }
        $goodsList = $this->getGoodsList($whereGoods);
        $goodsList = $goodsList['list'];
        if(empty($goodsList)){
            throw new \think\Exception(L_("商品不存在"), 1003);
        }

        
        $update = [];//更新数据
        $returnStatus = true;
        foreach ($goodsList as $key => $nowGoods) {
            switch ($type) {
                case '1'://沽清
                    //多规格沽清
                    if($index){
                        (new FoodshopGoodsSkuService())->updateSku($index, [
                            'spec_stock'    =>  0,
                            'last_time'     =>  time()
                        ]);

                        $goodsSkuList = (new FoodshopGoodsSkuService())->getSkuListByGoodsId($goodsId);
                        $allSpecStock = 0;
                        foreach($goodsSkuList as $key => $val){
                            if($val['spec_stock'] == -1){
                                $allSpecStock = -1;
                                break;
                            }else{
                                $allSpecStock += $val['spec_stock'];
                            }
                        }
 
                        $update['spec_stock'] = $allSpecStock;

                    }else{
                        //单规格沽清
                        $update['spec_stock'] = 0;
                        //                    $update['sell_day'] = date('Ymd');
                    }
                    
                    break;
                case '2'://置满
                    //多规格置满
                    if($index){                             
                        $goodsSku = (new FoodshopGoodsSkuService())->getSkuByIndex($index, $goodsId);
                        if(!$goodsSku){
                            throw new \think\Exception('规格不存在！');
                        }
                        (new FoodshopGoodsSkuService())->updateSku($index, [
                            'spec_stock'    =>  $goodsSku['spec_original_stock'],
                            'last_time'     =>  time()
                        ]);

                        
                        $goodsSkuList = (new FoodshopGoodsSkuService())->getSkuListByGoodsId($goodsId);
                        $allSpecStock = 0;
                        foreach($goodsSkuList as $key => $val){
                            if($val['spec_stock'] == -1){
                                $allSpecStock = -1;
                                break;
                            }else{
                                $allSpecStock += $val['spec_stock'];
                            }
                        }

                        $update['spec_stock'] = $allSpecStock;

                    }else{
                        $fillUpBatch = [
                            'spec_stock' => $nowGoods['spec_original_stock'],
    //                        'sell_day' => date('Ymd'),
                        ];
    
                        // 批量置满
                        $whereBatch= [
                            'pigcms_id' => $nowGoods['pigcms_id'],
                            'store_id' => $storeId,
                        ];
                        if($this->updateThis($whereBatch, $fillUpBatch)===false){
                            $returnStatus = false;
                        }

                        $goodsSkuList = (new FoodshopGoodsSkuService())->getSkuListByGoodsId($goodsId);
                        if($goodsSkuList){
                            $time = time();
                            foreach($goodsSkuList as $key => $val){
                                (new FoodshopGoodsSkuService())->updateSku($val['index'], [
                                    'spec_stock'    =>  $val['spec_original_stock'],
                                    'last_time'     =>  $time
                                ]);
                            }
                        }
    
                    }
                    
                    break;
                case '3'://修改库存
                    if($originalStock === '' || $stockNum === ''){
                        throw new \think\Exception(L_("商品库存必填"), 1003);
                    }
                    $update['spec_stock'] = $stockNum;
                    $update['spec_original_stock'] = $originalStock;
//                    $update['sell_day'] = date('Ymd');

                    if(($originalStock != -1 && $stockNum > $originalStock) || ($stockNum == -1 && $originalStock != -1)){
                        throw new \think\Exception(L_("当前库存不能大于原始库存"), 1003);
                    }
                    break;
                case '4':
                    $update['status'] = 1;
                    break;
                case '5':
                    $update['status'] = 0;
                    break;
                case '6':
                    break;
                case '7':
                    $foodshopGoodsSortService = new FoodshopGoodsSortService();
                    $whereSort = ['sort_id' => $sortId, 'store_id' => $storeId];
                    if (!$sort = $foodshopGoodsSortService->getOne($whereSort)) {
                        throw new \think\Exception(L_("商品分类不存在"), 1003);
                    }
                    $update['spec_sort_id'] = $sortId;

                    break;
            }
        }

        if ($type==6) {
            // 删除
            if(!$this->del($where)){
                throw new \think\Exception(L_("删除失败"), 1003);
            }

            // 删除规格属性sku
            (new FoodshopGoodsSkuService())->del($where);
            $return = array('msg' => L_('删除成功'));
        }else{
            if($update){
                if($this->updateThis($where, $update) === false){
                    throw new \think\Exception(L_("更新失败"), 1003);
                }

                $return = array('msg' => L_('更新成功'));
            }elseif ($returnStatus) {
                $returnData = [];
                if (count($goodsList)==1) {
                    $returnData = array('update' => $goodsList[0]['original_stock']);
                }
                $return = array( 'msg' => L_('更新成功'), 'data' => $returnData);
            }else{
                throw new \think\Exception(L_("更新失败"), 1003);
            }
        }
        return $return;
    }

    /**
     * 批量更新商品信息
     *  1-排序，2-价格，3-库存，4-多规格价格，5-多规格库存，6-删除，7-修改分类，
     * @param $param
     * @return array
     */
    public function editSingleGoods($param, $merchantUser){
        $pigcmsId = $param['pigcms_id'] ?? 0;
        $goodsId = $param['goods_id'] ?? 0;
        $sort = $param['sort'] ?? '';//排序
        $type = $param['type'] ?? '';//编辑类型

        if((empty($pigcmsId)&&empty($goodsId)) || empty($type)){
            throw new \think\Exception(L_("缺少参数"), 1001);
        }

        // 商品
        if($goodsId){
            $where = [
                ['goods_id' , '=', $goodsId],
            ];
        }else{
            $where = [
                ['pigcms_id' , '=', $pigcmsId],
            ];
        }
        $goods = $this->getOne($where);
        if(empty($goods)){
            throw new \think\Exception(L_("商品不存在"), 1003);
        }


        //如果是多规格商品 获取默认多规格组合
        $shopGoodsService = new ShopGoodsService();
        $goodsDetail = $shopGoodsService->getGoodsDetailByModule(self::TABLE_NAME, $goodsId);
        $defaultSpec = [];
        if (!empty($goodsDetail['spec_value'])) {
            $shopSpecValue = $shopGoodsService->formatSpecValue($goodsDetail['spec_value'], $goodsDetail['goods_id']);
            $defaultSpec = $shopSpecValue['list'] ?? [];
        } 

        // 更新数据
        $update = [
            'last_time' => time()
        ];
        switch ($type){
            case '1':
                // 修改排序
                $update['sort'] = $sort;
                break;
            case '2':
                // 修改价格
                $update['price'] = $param['price'];
                break;
            case '3':
                if($param['spec_original_stock'] === '' || $param['spec_stock'] === ''){
                    throw new \think\Exception(L_("商品库存必填"), 1003);
                }
                $update['spec_stock'] = $param['spec_stock'];
                $update['spec_original_stock'] = $param['spec_original_stock'];

                if(($param['spec_original_stock'] != -1 && $param['spec_stock'] > $param['spec_original_stock']) || ($param['spec_stock'] == -1 && $param['spec_original_stock'] != -1)){
                    throw new \think\Exception(L_("当前库存不能大于原始库存"), 1003);
                }
                break;
            case '4':
                // 多规格价格
                if(isset($param['indexs'])&&$param['indexs']){
                    $where = [
                        'goods_id' => $goods['goods_id'],
                        'store_id' => $goods['store_id']
                    ];

                    $foodshopGoodsSku = (new FoodshopGoodsSkuService())->getSkuListByGoodsId($where);
                    $foodshopGoodsSkuStockNum = array_column($foodshopGoodsSku ,'spec_stock', 'index');
                    (new FoodshopGoodsSkuService())->del($where);
                    $data = [];
                    foreach ($param['indexs'] as $key => $value) {
                        $data[] = [
                            'goods_id' => $goods['goods_id'],
                            'store_id' => $goods['store_id'],
                            'index' => $value,
                            'price' => $param['prices'][$key],
                            'spec_stock' => $foodshopGoodsSkuStockNum[$value] ?? (isset($defaultSpec[$value])?$defaultSpec[$value]['stock_num']:0),
                            'last_time' => time(),
                        ];
                    }
                    (new FoodshopGoodsSkuService())->addAll($data);
                }
                break;
            case '5':
                // 多规格库存
                if(isset($param['indexs'])&&$param['indexs']){
                    $where = [
                        'goods_id' => $goods['goods_id'],
                        'store_id' => $goods['store_id']
                    ];

                    $foodshopGoodsSku = (new FoodshopGoodsSkuService())->getSkuListByGoodsId($where);
                    $foodshopGoodsSkuPrice = array_column($foodshopGoodsSku ,'price', 'index');
                    (new FoodshopGoodsSkuService())->del($where);

                    $data = [];
                    foreach ($param['indexs'] as $key => $value) {
                        $data[] = [
                            'goods_id' => $goods['goods_id'],
                            'store_id' => $goods['store_id'],
                            'index' => $value,
                            'spec_stock' => $param['stock_nums'][$key],
                            'spec_original_stock' => $param['stock_nums'][$key],
                            'price' => $foodshopGoodsSkuPrice[$value] ?? (isset($defaultSpec[$value]) ? $defaultSpec[$value]['price'] : 0),
                            'last_time' => time(),
                        ];
                    }
                    (new FoodshopGoodsSkuService())->addAll($data);
                }
                break;
        }

        if($this->updateThis($where, $update) !== false){
            $return = array('msg' => L_('更新成功'));
        }else{
            throw new \think\Exception(L_("更新失败"), 1003);
        }
        return $return;
    }

    /**
     * 上下架
     * @param $param
     * @return array
     */
    public function changeStatus($param, $merchantUser){
        $pigcmsId = $param['pigcms_id'] ? $param['pigcms_id'] : 0;
        $storeId = $param['store_id'] ?? 0;

        if(empty($storeId)){
            throw new \think\Exception(L_("店铺id不存在"), 1001);
        }
        $where['mer_id'] = $merchantUser['mer_id'];
        $where['store_id'] = $storeId;
        $store = (new MerchantStoreService())->getOne($where);
        if(empty($store)){
            throw new \think\Exception(L_("店铺不存在"), 1001);
        }

        if((empty($pigcmsId))){
            throw new \think\Exception(L_("缺少参数"), 1001);
        }

        // 商品列表
        $where = [
            ['store_id' , '=', $storeId],
            ['pigcms_id' , '=', $pigcmsId],
        ];
        $goods = $this->getOne($where);
        if(empty($goods)){
            throw new \think\Exception(L_("商品不存在"), 1003);
        }

        $update = [];//更新数据
        if($goods['status'] == 1){
            $update['status'] = 0;
            $return = array('msg' => L_('下架成功'));
        }else{
            $update['status'] = 1;
            $return = array('msg' => L_('上架成功'));
        }

        if($this->updateThis($where, $update) === false){
            throw new \think\Exception(L_("操作失败，请稍后重试"), 1003);
        }
        return $return;
    }

    /**
     * 获得单个商品规格
     * @param $param
     * @return array
     */
    public function getGoodsSpecPrice($goodsDetail){
        $specs = '';
        $pre = '';
        $returnArr['min_price'] = 0;//最小金额
        $returnArr['is_sell_out'] = true;//是否售罄
        if ($goodsDetail['spec_value']) {
            // 餐饮规格
            $foodshopSku = (new FoodshopGoodsSkuService())->getSkuListByGoodsId($goodsDetail['goods_id']);
            $foodshopSpecValue = array();
            if($foodshopSku){
                foreach ($foodshopSku as $key => $value) {
                    $foodshopSpecValue[$value['index']]['price'] = $value['price'];
                    $foodshopSpecValue[$value['index']]['stock_num'] = $value['spec_stock'];
                }
            }

            $shopSpecValue = (new ShopGoodsService())->formatSpec($goodsDetail['spec_value'],$goodsDetail['goods_id']);
            foreach ($shopSpecValue['list'] as $k => $v) {
                if(isset($foodshopSpecValue[$k])){
                    $specs .= $pre . $k . '|' . $foodshopSpecValue[$k]['price']. ':' . $foodshopSpecValue[$k]['stock_num']. '|#';
                }
            }

            $return = (new ShopGoodsService())->formatSpec($goodsDetail['spec_value'], $goodsDetail['goods_id'],  $goodsDetail['min_num'], $specs);

            if(isset($return['list'])){
                $return['list'] = array_values($return['list']);
                foreach ($return['list'] as $key => $_spec){
                    if($key == 0){
                        $returnArr['min_price'] = $_spec['price'];
                    }else{
                        $returnArr['min_price'] = min($returnArr['min_price'],$_spec['price']);
                    }
                    if($_spec['stock_num'] == -1 || $_spec['stock_num'] > 0){
                        $returnArr['is_sell_out'] = false;
                    }
                }
            }
        }

        return $returnArr;
    }

    /**
     * 根据商品id获得商品详情
     * @param $goodsId
     * @param $isWap 是否用户端
     * @return array
     */
	public function getGoodsDetailByGoodsId($goodsId, $plat = 'wap'){
        if(!$goodsId){
            throw new \think\Exception("参数错误");
        }

        $returnArr = [];
        $shopGoodsService = new ShopGoodsService();
        $goodsDetail = $shopGoodsService->getGoodsDetailByModule(self::TABLE_NAME, $goodsId);
		if(!$goodsDetail) {
            throw new \think\Exception("商品不存在");
        }
        
        // 外卖店铺详情
        $shopStore = (new MerchantStoreShopService())->getStoreByStoreId($goodsDetail['store_id']);

        // 获得商品规格属性
        $today = date('Ymd');
        $specs = '';
        $pre = '';
        if ($goodsDetail['spec_value']) {
            // 餐饮规格
            $foodshopSku = (new FoodshopGoodsSkuService())->getSkuListByGoodsId($goodsId);
            $foodshopSpecValue = array();
            if($foodshopSku){
                foreach ($foodshopSku as $key => $value) {
                    $foodshopSpecValue[$value['index']]['price'] = $value['price'];
                    $foodshopSpecValue[$value['index']]['stock_num'] = $value['spec_stock'];
                }
            }

            $shopSpecValue = $shopGoodsService->formatSpecValue($goodsDetail['spec_value'],$goodsDetail['goods_id']);
            foreach ($shopSpecValue['list'] as $k => $v) {
                if(isset($foodshopSpecValue[$k])){
                    $specs .= $pre . $k . '|' . $foodshopSpecValue[$k]['price']. ':' . $foodshopSpecValue[$k]['stock_num']. '|#';
                }else{

                }
            }
        } 
        
        $return = $shopGoodsService->formatSpecValue($goodsDetail['spec_value'], $goodsDetail['goods_id'], $goodsDetail['is_properties'], $goodsDetail['min_num'], [], $specs);

        $goodsDetail['json'] = isset($return['json']) ? json_encode($return['json']) : [];
        $goodsDetail['properties_list'] = isset($return['properties_list']) ? $return['properties_list'] : [];
        $goodsDetail['properties_status_list'] = isset($return['properties_status_list']) ? $return['properties_status_list'] : [];
        $goodsDetail['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
        $goodsDetail['list'] = isset($return['list']) ? $return['list'] : '';
        
		$goodsDetail['today_sell_spec'] = json_decode($goodsDetail['today_sell_spec'], true);
        if($plat == 'wap') {
            // 处理规格属性，获得需要的数据类型
            $goodsDetail = $shopGoodsService->getWapSpecValue($goodsDetail);
        }


        // 附属菜
        $goodsDetail['subsidiary_piece'] = (new ShopSubsidiaryPieceService())->getWapSubsidiaryPieceByGoodsId($goodsId,false);
        foreach ($goodsDetail['subsidiary_piece'] as $key => $_subsidiary_piece) {
            foreach($_subsidiary_piece['goods'] as $k => $good_value){
                $_subsidiary_piece['goods'][$k] = $this->getFormartSubsidiaryGoods($good_value, $shopStore['stock_type']);
            }
            $goodsDetail['subsidiary_piece'][$key]['goods'] = $_subsidiary_piece['goods'];
        }
        $goodsDetail['subsidiary_piece'] = $goodsDetail['subsidiary_piece'] ?? [];

        // 获得前端需要商品字段
        if($plat == 'wap'){
            $goodsDetail = $this->getFormartData($goodsDetail,1);
            $src = '<img style="max-width: 100%!important;vertical-align: middle;" src="'.file_domain().'/';
            $goodsDetail['des'] = str_replace('<img src="/', $src, $goodsDetail['des']);
            $goodsDetail['des'] = str_replace('<img src="'.cfg('site_url').'/', $src, $goodsDetail['des']);
            $goodsDetail['des'] = str_replace('<img alt="" src="/', $src, $goodsDetail['des']);
            $goodsDetail['des'] = str_replace('<img src="http', '<img style="max-width: 100%!important;vertical-align: middle;" src="http', $goodsDetail['des']);
            $goodsDetail['des'] = str_replace('<img alt="" ', '<img alt="" style="max-width:100%;vertical-align:top;" ', $goodsDetail['des']);
        }else{
            // 处理多规格
            if($goodsDetail['list']){
                foreach ($goodsDetail['list'] as $key => &$_list){
                    $_list['index'] = $key;
                    $_list['index_name'] = '';
                    foreach ($_list['spec'] as &$_spec){
                        $_list['spec_val_sid_'.$_spec['spec_val_sid']] = $_spec['spec_val_name'];
                        $_list['index_name'] .= $_spec['spec_val_name']. ' ';
                    }
                }
                $goodsDetail['list'] = array_values($goodsDetail['list']);
            }
            // 显示日期
            $goodsDetail['show_start_date'] = $goodsDetail['show_start_date'] ? date('Y-m-d',$goodsDetail['show_start_date']) : '';
            $goodsDetail['show_end_date'] = $goodsDetail['show_end_date'] ? date('Y-m-d',$goodsDetail['show_end_date']) : '';
            // 显示星期
            $goodsDetail['week'] = $goodsDetail['week'] ? explode(',', $goodsDetail['week']) : '';
            if($goodsDetail['week']){
                foreach ($goodsDetail['week'] as &$_week){
                    $_week= $this->weekList[$_week];
                }
            }
            $goodsDetail['properties_list'] = array_values($goodsDetail['properties_list']);
            if($goodsDetail['print_id']){
                $where = [
                    ['pigcms_id' , '=', $goodsDetail['print_id']]
                ];
                $print = (new OrderprintService())->getOne($where);
                if($print){
                    $goodsDetail['print_name'] = $print['name'];
                }
            }
        }
		return $goodsDetail;
	}

    /**
     * 根据条件获取商品列表
     * @param $nowGoods array
     * @param $isDetail bool 是否是商品详情页
     * @param $isClearStock bool 是否是店员估清菜单列表页
     * @return array
     */
	public function getFormartData($nowGoods, $isDetail = 0, $isClearStock = 0){
        $goodsImageService = new GoodsImageService();
        $spicyArray = (new ShopGoodsService())->spicyArray;
        $returnArr = [];
        // 售卖时间

        // 是否在售卖时间内
        $returnArr['is_time_out'] = false;
        if (!$this->checkTime($nowGoods)){
            $returnArr['is_time_out'] = true;
        }
        // 库存
        if(!$isClearStock){
            if ($nowGoods['spec_stock'] == -1) {
                $returnArr['stock_num'] = $nowGoods['stock_num'];
            }else{
                $returnArr['stock_num'] = $nowGoods['spec_stock'];
            }
        }else{
            $returnArr['stock_num'] = $nowGoods['spec_stock'];
        }

        // 是否售完
        $returnArr['is_sell_out'] = false;
        if ($returnArr['stock_num'] != -1 && $returnArr['stock_num'] <=0 ) {
            // 已售完
            $returnArr['is_sell_out'] = true;
        }
        // 起购数量
        $returnArr['mini_num'] = $nowGoods['min_num'];

        if ($returnArr['stock_num'] != -1 && $returnArr['stock_num'] < $nowGoods['min_num'] ) {
            // 已售完
            $returnArr['is_sell_out'] = true;
        }

        // 商品id
        $returnArr['product_id'] = $nowGoods['goods_id'];
        // 店铺ID
        $returnArr['store_id'] = $nowGoods['store_id'];
        // 分类
        $returnArr['spec_sort_id'] = $nowGoods['spec_sort_id'];
        // 商品名
        $returnArr['product_name'] = html_entity_decode($nowGoods['name'], ENT_QUOTES);
        // 商品简称
        $returnArr['product_abbreviation'] = html_entity_decode($nowGoods['abbreviation'], ENT_QUOTES);
        // 商品详情描述
        $returnArr['des'] = html_entity_decode($nowGoods['des'], ENT_QUOTES);
        // 商品描述
        $returnArr['product_describe'] = html_entity_decode($nowGoods['describe'], ENT_QUOTES);
        // 商品价格
        $returnArr['product_price'] = strval(floatval($nowGoods['price']));
        // 商品原价
        $returnArr['old_price'] = strval(floatval($nowGoods['old_price']));
        // 商品条形码
        $returnArr['number'] = $nowGoods['number'];
        // 打包费
        $returnArr['packing_charge'] = strval(floatval($nowGoods['packing_charge']));
        // 单位
        $returnArr['unit'] = $nowGoods['unit'];
        // 是否推荐 1推荐
        $returnArr['is_recommend'] = $nowGoods['is_recommend'];
        // 是否仅店员可操作0-否，1-是
        $returnArr['only_staff'] = $nowGoods['only_staff'];
        //辣度
        $returnArr['spicy_name'] = isset($spicyArray[$nowGoods['spicy']]) ? $spicyArray[$nowGoods['spicy']] : '';
        $returnArr['spicy'] = $nowGoods['spicy'];
        //口味
        $returnArr['flavor'] = $nowGoods['flavor'] ? explode('&^&',$nowGoods['flavor']) : array();
        //原料
        $returnArr['material'] = $nowGoods['material'] ? explode('&^&',$nowGoods['material']) : array();
        //特殊食材
        $returnArr['special_food_materials'] = $nowGoods['special_food_materials'] ? explode('&^&',$nowGoods['special_food_materials']) : array();
        //做法
        $returnArr['cooking_methods'] = $nowGoods['cooking_methods'] ? explode('&^&',$nowGoods['cooking_methods']) : array();
        
        //商品缩略图
        
        if($isDetail){
            $tmpPicArr = $goodsImageService->getAllImageByPath($nowGoods['image'], 'b');
            $returnArr['product_image'] = $tmpPicArr;
            $returnArr['product_image'] = $returnArr['product_image'] ? $returnArr['product_image'] : '';
        }else{
            $tmpPicArr = $goodsImageService->getAllImageByPath($nowGoods['image'], 's');
            $returnArr['product_image'] = $goodsImageService->getOneImage($tmpPicArr);
            $returnArr['product_image'] = thumb_img($returnArr['product_image'],180,180,'fill');
        }

        // 已售数量
        $returnArr['product_sale'] = $nowGoods['sell_count'];

        // $returnArr['product_reply'] = $nowGoods['reply_count'];

        // 是否有规格属性
        $returnArr['has_format'] = false;
        if ($nowGoods['is_properties']) {
            $returnArr['has_format'] = true;
        }

        //商品是否有规格
        $returnArr['has_spec']  = $nowGoods['spec_value'] ? true : false;
        if($returnArr['has_spec']){
            $specArr = $this->getGoodsSpecPrice($nowGoods);
            $returnArr['product_price'] = $specArr['min_price'];
            $returnArr['is_sell_out'] = $specArr['is_sell_out'];


        }

        //商品是否有附属菜
        $returnArr['is_subsidiary_goods']  = $nowGoods['is_subsidiary_goods'] ? true : false;
        
        $returnArr['spec_stock_type']  = $nowGoods['spec_stock_type'];

        if($isDetail){
            // $returnArr['json'] = isset($nowGoods['json']) ? $nowGoods['json'] : '';
            $returnArr['properties_list'] = isset($nowGoods['properties_list']) ? $nowGoods['properties_list'] : '';
            $returnArr['properties_status_list'] = isset($nowGoods['properties_status_list']) ? $nowGoods['properties_status_list'] : '';
            $returnArr['spec_list'] = isset($nowGoods['spec_list']) ? $nowGoods['spec_list'] : '';
            $returnArr['list'] = isset($nowGoods['list']) ? $nowGoods['list'] : '';
            $returnArr['subsidiary_piece'] = isset($nowGoods['subsidiary_piece']) ? $nowGoods['subsidiary_piece'] : '';
        }
        //是否可用分类优惠
        $returnArr['is_sort_discount'] = $nowGoods['is_sort_discount'];
		return $returnArr;
    }

    /**
     * 获得附属商品
     * @return array
     */
	public function getFormartSubsidiaryGoods($nowGoods, $stockType=0){
        $goodsImageService = new GoodsImageService();
        $spicyArray = (new ShopGoodsService())->spicyArray;
        $returnArr = [];
        $today = date('Ymd');
        $returnArr['sell_day'] = $stockType ? $today : $nowGoods['sell_day'];

        $returnArr['product_price']= floatval($nowGoods['price_sug']);
        $returnArr['product_sale'] = $nowGoods['sell_count'];
        $returnArr['product_id']   = $nowGoods['goods_id'];
        $returnArr['store_id'] = $nowGoods['store_id'];
        $returnArr['product_name'] = htmlspecialchars_decode($nowGoods['name'], ENT_QUOTES);
        $tmpPicArr = $goodsImageService->getAllImageByPath($nowGoods['image'], 's');
        $returnArr['product_image'] = $goodsImageService->getOneImage($tmpPicArr);
        $returnArr['product_image'] = thumb_img($returnArr['product_image'],200,134,'fill');
        $returnArr['has_format']   = $nowGoods['is_properties'] ? true : false;
        $returnArr['has_spec']   = $nowGoods['spec_value'] ? true : false;
        $returnArr['max_num']   = $nowGoods['maxnum_sug'];
        $returnArr['mini_num'] = $nowGoods['mininum_sug'];
        $returnArr['stock_num'] = $nowGoods['stock_num'];
        $returnArr['sort'] = $nowGoods['sort_sug'];
        $returnArr['unit'] = $nowGoods['unit'];

        // 外卖商品service
        $shopGoodsService = new ShopGoodsService();
        // 规格属性
        // 获得商品规格属性
        $return = $shopGoodsService->formatSpecValue($nowGoods['spec_value'], $nowGoods['goods_id'], $nowGoods['is_properties'], $nowGoods['min_num'], []);
        $returnArr['json'] = isset($return['json']) ? json_encode($return['json']) : '';
        $returnArr['properties_list'] = isset($return['properties_list']) ? $return['properties_list'] : '';
        $returnArr['properties_status_list'] = isset($return['properties_status_list']) ? $return['properties_status_list'] : '';
        $returnArr['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
        $returnArr['list'] = isset($return['list']) ? $return['list'] : '';
        $returnArr['today_sell_spec'] = json_decode($nowGoods['today_sell_spec'], true);

        // 处理规格属性，获得需要的数据类型
        $returnArr = $shopGoodsService->getWapSpecValue($returnArr);

		return $returnArr;
	}


    
    /**
     * 根据店铺id获得商品列表
     * @param $areaId
     * @return array
     */
    public function getRecommendGoodsList($storeId)
    {
        $limit = 10;//每页显示数量
        $page = request()->param('page', '1', 'intval');//页码

        // 构造查询条件
        $condition[] = ['t.store_id','=',$storeId];
        $condition[] = ['t.status','=',1];
        $condition[] = ['t.is_recommend','=',1];

        // 排序
        $order = [
            's.sort' => 'DESC',
            't.goods_id' => 'ASC',
        ];

        // 商品列表
        $shopGoodsService = new ShopGoodsService();
        $goodsList = $shopGoodsService->getGoodsListByModule(self::TABLE_NAME, $condition, $order, $limit, $page);
        
        // 获得前端需要商品字段
        foreach ($goodsList as &$_good) {
            $_good = $this->getFormartData($_good);
        }
        
        return $goodsList;
    }
    
    /**
     * 根据店铺id获得必点菜
     * @param $areaId
     * @return array
     */
    public function getMustGoodsByStoreId($storeId)
    {
        // 构造查询条件
        $condition[] = ['t.store_id','=',$storeId];
        $condition[] = ['t.status','=',1];
        $condition[] = ['t.is_must','=',1];

        // 排序
        $order = [
            's.sort' => 'DESC',
            't.goods_id' => 'ASC',
        ];

        // 商品列表
        $shopGoodsService = new ShopGoodsService();
        $goodsList = $shopGoodsService->getGoodsListByModule(self::TABLE_NAME, $condition, $order);
        // 必点菜
        $mustGoodsList = [];
        foreach ($goodsList as $_good) {
            // 有规格属性附属菜的必点属性失效
            if (!$_good['spec_value'] && !$_good['is_properties'] && !$_good['is_subsidiary_goods']) {
                $mustGoodsList[] = $_good;
            }
        }
        return $mustGoodsList;
    }

    /**
     * 根据条件获取商品列表
     * @return array
     */
	public function getGoodsListByCondition($where,$order=[]){
        if(empty($order)){
            // 排序
            $order = [
                'sort' => 'DESC',
                'goods_id' => 'ASC',
            ];
        }
		$goodsList = $this->foodshopGoodsModel->getGoodsListByCondition($where,$order);
		if(!$goodsList) {
            return [];
        }
		return $goodsList->toArray();
    }

    /**
     * 获取套餐商品信息
     * @param int $goodsId
     * @param int $num 购买数量
     * @param string $specIds = 'id_id'
     * @param array $store 店铺信息
     * @param int $hostGoodsId
     * @return array
     */
    public function getPackageGoodsInfo($goodsId, $num, $specIds = '', $store = [], $hostGoodsId = 0)
    {
        $shopGoodsService = new ShopGoodsService();
        // 外卖商品
        $shopGoods = $shopGoodsService->getGoodsByGoodsId($goodsId);

        $storeId = $store['store_id'] ?? '0';
        // 餐饮商品
        $foodshopGoods = $this->getGoodsByGoodsId($goodsId,$storeId);

        // 原价
        $oldPrice = floatval($shopGoods['old_price']);


        // 原价
        $price = floatval($shopGoods['price']);

        $returnArr = [
            'status' => 1,
            'num' => $num,
            'goods_id' => $shopGoods['goods_id'],
            'old_price' => $oldPrice,
            'price' => $price,
            'unit' => $shopGoods['unit'],
            'sort_id' => $foodshopGoods ? $foodshopGoods['spec_sort_id'] :  $shopGoods['sort_id'],
            'name' => $shopGoods['name'],
            'sell_type' => $shopGoods['sell_type'],
            'only_staff' => $foodshopGoods['only_staff'] ?? 0,
        ];
        return $returnArr;
    }

     /**
     * 检查库存
     * @param int $goodsId
     * @param int $num 购买数量
     * @param string $specIds = 'id_id' 
     * @param array $store 店铺信息
     * @param int $hostGoodsId
     * @return array
     */
    public function checkStock($goodsId, $num, $specIds = '', $store = [], $hostGoodsId = 0)
    {
        $shopGoodsService = new ShopGoodsService();
        // 外卖商品
        $shopGoods = $shopGoodsService->getGoodsByGoodsId($goodsId);

        $storeId = $store['store_id'] ?? '0';
        // 餐饮商品
        $foodshopGoods = $this->getGoodsByGoodsId($goodsId,$storeId);
        $returnArr = [
            'status' => 0,
            'msg' => '',
        ];
        if (empty($shopGoods)){
            $returnArr['msg'] = L_("商品不存在");
            return $returnArr;
//            throw new \think\Exception(L_("商品不存在"));
        }

        $stockType = $store['stock_type'] ?? 0;

        // 商品库存
        $stockNum = 0;

        if ($hostGoodsId==0) {
            // 非附属菜 餐饮菜品 主菜
            if (empty($foodshopGoods)){
                $returnArr['msg'] = L_("商品不存在");
                return $returnArr;
                throw new \think\Exception(L_("商品不存在"));
            }

            if ($foodshopGoods['status'] != 1){
                $returnArr['msg'] = L_("商品已下架");
                return $returnArr;
//                throw new \think\Exception(L_("商品已下架"));
            }

            // 验证商品是否在售卖时间内
            if(!$this->checkTime($foodshopGoods)){
                $returnArr['msg'] = L_("不在售卖时间内");
                return $returnArr;
//                throw new \think\Exception(L_("不在售卖时间内"));
            }

            $shopGoods['price'] = $foodshopGoods['price'];
            $shopGoods['old_price'] = $foodshopGoods['old_price'];

            // 餐饮库存
            if ($foodshopGoods['spec_stock'] == '-1') {
                $stockNum = $foodshopGoods['stock_num'] = $shopGoods['stock_num'];
            }else{
                $stockNum = $foodshopGoods['stock_num'] = $foodshopGoods['spec_stock'];
            }

        }else{
            // 附属菜
            if ($shopGoods['status'] != 1){
                $returnArr['msg'] = L_("商品已下架");
                return $returnArr;
//                throw new \think\Exception(L_("商品已下架"));
            }
        }
        
        
        //商品缩略图
        // $goods_image_class = new goods_image();
        // $tmp_pic_arr = $goods_image_class->get_allImage_by_path($shopGoods['image'], 's');
        // $image = thumb_img($tmp_pic_arr[0],130,130,'fill');

    
        $today = date('Ymd');

        //商品的库存类型（0：每日更新相同的库存，1:商品的总库存不会自动更新）
        $shopGoods['sell_day'] = $stockType ? $today : $shopGoods['sell_day'];

       
        $is_seckill_price = false;
        // if ($openTime < $nowTime && $nowTime < $closeTime && floatval($shopGoods['seckill_price']) > 0 && $seckill_stock_num != 0) {
        //     // 单价
        //     $price = floatval($shopGoods['seckill_price']);
        //     $is_seckill_price = true;
        // } else {
        //     // 单价
        //     $price = floatval($shopGoods['price']);
        // }

        $maxNum = $shopGoods['max_num'];

        /*商品总量*/
        $weight = $shopGoods['weight'];

        // 原价
        $oldPrice = floatval($shopGoods['old_price']);

        
        // 原价
        $price = floatval($shopGoods['price']);

        // 进价
        $costPrice = floatval($shopGoods['cost_price']);

        //  有规格商品
        if ($specIds && $shopGoods['spec_value']) {
            // 有规格
            if ($hostGoodsId==0) {
                // 餐饮规格
                $foodshopSku = (new FoodshopGoodsSkuService())->getSkuByIndex($specIds,$goodsId); // 附属菜
                if (!$foodshopSku){//没有则继承商品库的规格
//                    throw new \think\Exception(L_("您选择的规格可能被商家修改了"));
                }

                // 组合规格
                $foodshopSpecValue = (new FoodshopGoodsSkuService())->combineSku([$foodshopSku]);

                // 主菜
                $return = $shopGoodsService->formatSpecValue($shopGoods['spec_value'], $shopGoods['goods_id'], $shopGoods['is_properties'], 0,'',$foodshopSpecValue);
            }else{
                $return = $shopGoodsService->formatSpecValue($shopGoods['spec_value'], $shopGoods['goods_id'], $shopGoods['is_properties'], 0);
            }

            $list = isset($return['list']) ? $return['list'] : '';

            if (!isset($list[$specIds])) {
                $returnArr['msg'] = L_("您选择的规格可能被商家修改了");
                return $returnArr;
//                throw new \think\Exception(L_("您选择的规格可能被商家修改了"));
            }

            // 规格已售
            $todaySellSpec = json_decode($shopGoods['today_sell_spec'], true);

            // 单价
            $price = $list[$specIds]['price'];

            
            $oldPrice = floatval($list[$specIds]['price']);
            $costPrice = floatval($list[$specIds]['cost_price']);
            $number = $list[$specIds]['number'];
            $maxNum = $list[$specIds]['max_num'];
            $weight = $list[$specIds]['weight'];
            if ($is_seckill_price) {
//                $stockNum = $seckill_stock_num;
            } else {  
                if ($hostGoodsId!=0) {
                    if ($today == $shopGoods['sell_day']) {
                        $sell_count = isset($todaySellSpec[$specIds]) ? intval($todaySellSpec[$specIds]) : 0;
                        $stockNum = $list[$specIds]['stock_num'] == -1 ? -1 : (intval($list[$specIds]['stock_num'] - $sell_count) > 0 ? intval($list[$specIds]['stock_num'] - $sell_count) : 0);
                    } else {
                        $stockNum = $list[$specIds]['stock_num'];
                    }
                }else{
                    $stockNum = $list[$specIds]['stock_num'];
                }
            }
        } else {
            if ($hostGoodsId==0) {
                if ($foodshopGoods['stock_num'] != -1) {
                    if ($foodshopGoods['stock_num']<=0) {
                        $returnArr['msg'] = L_("商品已售罄");
                        return $returnArr;
//                        throw new \think\Exception(L_("商品已售罄"));
                    }else{
                        $stockNum = $foodshopGoods['stock_num'];
                    }
                }
            }else{
                $stockNum = $shopGoods['stock_num'];
                
            }
        }

        // 条形码
        $number = $shopGoods['number'];
        
        $maxNum = intval($maxNum);
        if ($shopGoods['min_num'] > 1) {
            $maxNum = 0;
        }

        // 如果是附属商品和非多规格商品取出对应的商品价格
        if ($hostGoodsId && empty($shopGoods['spec_value'])) {
            $where['sug.goods_id'] = $goodsId;
            $where['sug.host_goods_id'] = $hostGoodsId;
            $subsidiaryPieceGoods = (new ShopSubsidiaryPieceGoodsService())->getSubsidiaryPieceGoods($where);
            $price = $subsidiaryPieceGoods[0]['price_sug'];
        }
        
        if ($stockNum == 0) {
            $returnArr['msg'] = L_("商品已售罄");
            return $returnArr;
//            throw new \think\Exception(L_("商品已售罄"));
        }

        if ($stockNum != -1 && $stockNum - $num < 0) {
            $returnArr['msg'] = L_('库存不足,最多能购买') . $stockNum . $shopGoods['unit'];
            return $returnArr;
//            throw new \think\Exception(L_('库存不足,最多能购买') . $stockNum . $shopGoods['unit']
        }

        $returnArr = [
            'status' => 1, 
            'num' => $num, 
            'goods_id' => $shopGoods['goods_id'], 
            'maxNum' => $maxNum, 
            'old_price' => $oldPrice,
            'price' => $price, 
            'unit' => $shopGoods['unit'], 
            'sort_id' => $foodshopGoods ? $foodshopGoods['spec_sort_id'] :  $shopGoods['sort_id'],
            'name' => $shopGoods['name'], 
            'sell_type' => $shopGoods['sell_type'],
            'only_staff' => $foodshopGoods['only_staff'] ?? 0,
        ];
        return $returnArr;
    } 

    
    /**
     * 更新库存
     *
     * $type 操作类型 0：加销量，减库存，1：加库存，减销量
     * 餐饮都是固定库存
     */
    public function updateStock($goods, $type = 0)
    {
        // 餐饮商品
        $nowGoods = $this->getGoodsByGoodsId($goods['goods_id'],$goods['store_id']);
        if (empty($nowGoods)){
            return false;
        }

        // 外卖商品
        $shopGoodsService = new ShopGoodsService();
        $nowShopGoods = $shopGoodsService->getGoodsByGoodsId($goods['goods_id']);
        if (empty($nowGoods)&&!$nowShopGoods) {
            throw new \think\Exception("商品不存在");
        }
        if (!$goods['host_goods_id']) {
            //主菜
            if ($type == 0) {//加销量
                $num = $goods['num'];
                $total_num = $goods['num'];
            } else {//减销量
                $num = $goods['num'] * -1;
                $total_num = $goods['num'] * -1;
            }
            
            $sell_count = $nowGoods['sell_count'];//总销量
            // $seckill_count = $nowGoods['seckill_count'];//总销量
            // if ($goods['is_seckill']) {
            //     $seckill_count += $num;
            // } 
            
            $sell_count += $total_num;
            if (isset($goods['spec_id']) && $goods['spec_id']) {
                //更新规格库存
                $sku = (new FoodshopGoodsSkuService())->getSkuByIndex($goods['spec_id'], $goods['goods_id']);
                if($sku){
                    $skuStock = $sku['spec_stock'];
                    if ($skuStock >= 0) {
                        if ($skuStock - $num >= 0) {
                            $skuStock -= $num;
                        } else {
                            $skuStock = 0;
                        }
                    }
                    // 更新
                    $data = ['spec_stock'=>$skuStock];
                    (new FoodshopGoodsSkuService())->updateSku($goods['spec_id'],$data);

                }
               
            }

            //原库存
            $stock_num = $nowGoods['spec_stock'];
            if ($stock_num >= 0) {
                if ($stock_num - $num >= 0) {
                    $stock_num -= $num;
                } else {
                    $stock_num = 0;
                }
            }

            //原限时优惠库存
            // $seckill_stock = $nowGoods['seckill_stock'];
            // if ($seckill_stock >= 0) {
            //     if ($seckill_stock - $num >= 0) {
            //         $seckill_stock -= $num;
            //     } else {
            //         $seckill_stock = 0;
            //     }
            // }

            $sell_count = max(0, $sell_count);
//            $seckill_count = max(0, $seckill_count);

            // 更新餐饮库存
			$updateData = [
                'spec_stock' => $stock_num,
//                'seckill_count' => $seckill_count,
                'sell_count' => $sell_count, 
                // 'seckill_stock' => $seckill_stock
			];
            $res = $this->updateByGoodsId($goods['goods_id'], $updateData);
            if($res === false){
                throw new \think\Exception("更新库存失败");
			}

            // 更新快店库存
            $shopGoodsService->updateStock($goods, $type, 1);
        }else{
            // 更新快店库存
            $shopGoodsService->updateStock($goods, $type);
        }
        return true;
    }

    /**
     * 验证商品是否在售卖时间内
     * @param $where
     * @return array
     */
    public function checkTime($data) {
        if(empty($data)){
            return false;
        }

        // 没有开启自定义时间段
        if($data['all_date'] == 1){
            return true;
        }

        // 验证日期
        $datatime = strtotime(date('Y-m-d'));
        if($datatime < strtotime(date('Y-m-d',$data['show_start_date'])) || $datatime > strtotime(date('Y-m-d',$data['show_end_date']))){
            return false;
        }

        // 验证星期几
        if (empty($data['week'])){
            // 没有设置代表不显示
            return false;
        }
        // 今日星期几
        $todayWeek = date('w');
        if($data['week'] === ''){
            return false;
        }

        $weekArr = explode(',', $data['week']);
        if(!in_array($todayWeek, $weekArr)){
            return false;
        }

        // 验证每日时间段（没有设置代表全时段）
        if ($data['all_time'] == 1) {
            return true;
        }

        $time = time();
        if($data['show_start_time'] != '00:00:00' || $data['show_end_time'] != '00:00:00'){
            // 时间段一
            $sTime = strtotime(date('Y-m-d ' . $data['show_start_time']));
            $eTime = strtotime(date('Y-m-d ' . $data['show_end_time']));
            if ($time >= $sTime && $time <= $eTime) {
                // 符合直接返回true，否则向下验证其他时间段
                return true;
            }

            if($data['show_start_time2'] != '00:00:00' || $data['show_end_time2'] != '00:00:00'){
                // 时间段二
                $sTime = strtotime(date('Y-m-d ' . $data['show_start_time2']));
                $eTime = strtotime(date('Y-m-d ' . $data['show_end_time2']));
                if ($time >= $sTime && $time <= $eTime) {
                    return true;
                }

                if($data['show_start_time3'] != '00:00:00' || $data['show_end_time3'] != '00:00:00'){
                    // 时间段三
                    $sTime = strtotime(date('Y-m-d ' . $data['show_start_time3']));
                    $eTime = strtotime(date('Y-m-d ' . $data['show_end_time3']));
                    if ($time >= $sTime && $time <= $eTime) {
                        return true;
                    }
                }
            }
        }elseif($data['show_start_time2'] != '00:00:00' || $data['show_end_time2'] != '00:00:00'){
            // 时间段二
            $sTime = strtotime(date('Y-m-d ' . $data['show_start_time2']));
            $eTime = strtotime(date('Y-m-d ' . $data['show_end_time2']));
            if ($time >= $sTime && $time <= $eTime) {
                return true;
            }

            if($data['show_start_time3'] != '00:00:00' || $data['show_end_time3'] != '00:00:00'){
                // 时间段三
                $sTime = strtotime(date('Y-m-d ' . $data['show_start_time3']));
                $eTime = strtotime(date('Y-m-d ' . $data['show_end_time3']));
                if ($time >= $sTime && $time <= $eTime) {
                    return true;
                }
            }
        }elseif($data['show_start_time3'] != '00:00:00' || $data['show_end_time3'] != '00:00:00'){
            // 时间段三
            $sTime = strtotime(date('Y-m-d ' . $data['show_start_time3']));
            $eTime = strtotime(date('Y-m-d ' . $data['show_end_time3']));
            if ($time >= $sTime && $time <= $eTime) {
                return true;
            }
        }

        return false;
    }

    /**
     * 根据条件获取商品列表
     * @param $goodsId
     * @return array
     */
	public function getGoodsByGoodsId($goodsId,$storeId=0){
	    $where = [
	        'goods_id' => $goodsId,
        ];
        $storeId && $where['store_id'] = $storeId;
		$goods = $this->foodshopGoodsModel->getOne($where);
		if(!$goods) {
            return [];
        }
		return $goods->toArray();
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getOne($where){
        $detail = $this->foodshopGoodsModel->getOne($where);
        if(!$detail) {
            return [];
        }
        return $detail->toArray();
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getSome($where){
        $list = $this->foodshopGoodsModel->getSome($where);
        if(!$list) {
            return [];
        }
        return $list->toArray();
    }

    /**
     * 更新数据
     * @param $goodsId
     * @param $data
     * @return bool
     */
    public function updateByGoodsId($goodsId,$data){
        if (!$goodsId || !$data) {
            return false;
        }

        $result = $this->foodshopGoodsModel->updateByGoodsId($goodsId,$data);
//            var_dump($this->foodshopGoodsModel->getLastSql());
        if($result === false){
            return false;
        }
        
        return $result;
    }


    /**
     *批量插入数据
     * @param $data array
     * @return array
     */
    public function addAll($data){
        if(empty($data)){
            return false;
        }

        try {
            // insertAll方法添加数据成功返回添加成功的条数
            $result = $this->foodshopGoodsModel->insertAll($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }
    /**
     * 添加数据
     * @param $where array 条件
     * @return array
     */
    public function add($data) {
        if( empty($data)){
            return false;
        }
        try {
            $result = $this->foodshopGoodsModel->add($data);
        }catch (\Exception $e) {
            return false;
        }

        return $this->foodshopGoodsModel->id;
    }

    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->foodshopGoodsModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }

    /**
     * 获得商品数量
     * @param where
     * @return bool
     */
    public function getCount($where){
        try {
            $result = $this->foodshopGoodsModel->getCount($where);
        }catch (\Exception $e) {
            return 0;
        }

        return $result;
    }

    /**
     * 删除
     * @param where
     * @return bool
     */
    public function del($where){
        if(empty($where)){
            return false;
        }
        try {
            $result = $this->foodshopGoodsModel->where($where)->delete();
        }catch (\Exception $e) {
            return false;
        }

        return $result;
    }


}