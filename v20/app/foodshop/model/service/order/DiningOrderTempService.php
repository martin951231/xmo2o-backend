<?php
/**
 * 餐饮订单购物车详情service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/03 10:44
 */

namespace app\foodshop\model\service\order;
use app\foodshop\model\db\DiningOrder;
use app\foodshop\model\db\DiningOrderTemp as DiningOrderTempModel;
use app\foodshop\model\db\FoodshopGoodsLibrary;
use \app\foodshop\model\service\order\FoodshopCartService;
use app\foodshop\model\service\package\FoodshopGoodsPackageService;
use app\foodshop\model\service\goods\FoodshopGoodsLibraryService;
use app\shop\model\service\goods\ShopGoodsService as ShopGoodsService;
use app\foodshop\model\service\order_print\PrintHaddleService;
use app\foodshop\model\service\store\MerchantStoreFoodshopService;

class DiningOrderTempService {
    public $diningOrderTempModel = null;
    public $foodshopGoodsLibraryModel = null;
    public function __construct()
    {
        $this->diningOrderTempModel = new DiningOrderTempModel();
        $this->foodshopGoodsLibraryModel = new FoodshopGoodsLibrary();
    }

    /**
     * 保存购物车菜品
     * @param $orderId int 订单id
     * @return array
     */
    public function saveCart($param, $status = 1, $staff = [], $payOrderId = 0){
        $orderId = $param['order_id'] ?? 0;
        $tempId = $param['temp_id'] ?? 0;// 是否快速点餐1-是
        if(empty($orderId)){
            throw new \think\Exception(L_('参数错误'), 1001);
        }

        $nowTime = time();

        // 查看购物车
        $goodsList = $this->getGoodsListByOrderId($orderId);
        if(empty($goodsList)){
            throw new \think\Exception(L_('购物车暂无商品'), 1003);
        }
        
        //获取分类折扣信息
        $goodsList = $this->getSortDiscountInfo($goodsList);
      
        // 订单
        $order = (new DiningOrderService())->getOrderByOrderId($orderId);
        if(empty($order)){
            throw new \think\Exception(L_('订单不存在'), 1003);
        }

        // 第几次下单
        $orderNum = (new DiningOrderDetailService()) ->getNextOrderNum($orderId);

        $mustGoods = [];
        if($orderNum == 1){
            // 第一次下单 添加必点菜
            if($order['book_num'] > 0){
                $flag = 1;
                // 必点菜列表
                $mustGoodsList = (new FoodshopGoodsLibraryService())->getMustGoodsByStoreId($order['store_id']);
                if (!empty($mustGoodsList)) {
                    $mustGoodsID = array_column($mustGoodsList,'goods_id');
                    $packageMustGoodsID = [];
                    foreach ($goodsList as $goods) {
                        if ($goods['package_id'] > 0) {
                            $packageMustGoodsID[] = $goods['goods_id'];
                        }
                    }
                    if (!empty($packageMustGoodsID)) {
                        $intersect = array_intersect($mustGoodsID,$packageMustGoodsID);
                        if (!empty($intersect)) {
                            $flag = 2;
                        }
                    }

                    //套装中已有必点菜，则无需再添加必点菜
                    if ($flag == 1) {
                        $mustGoodsRes = (new DiningOrderService())->addMustGoods($orderId, 0, $orderNum, $status);
                        if($mustGoodsRes){
                            $mustGoods = $mustGoodsRes['goods'];
                        }
                        $order = (new DiningOrderService())->getOrderByOrderId($orderId);
                    }
                }
            }
        }

        // 订单备注
        $goodsNote = $order['goods_note'] ? unserialize($order['goods_note']) : [];

        $goodsNote[($orderNum-1)] = $param['goods_note'];

        // 订单总额
        $totalPrice = 0;
        
        // 商品总数
        $goodsNum = 0;

        //分类折扣金额
        $sortDiscount = $order['sort_discount'];

        $addGoodsList = [];
        $goodsListFormat = [];  
        foreach($goodsList as $_goods){
            $tempGoods = $_goods;
            $tempGoods['create_time'] = $nowTime;
            $tempGoods['order_id'] = $orderId;
            $tempGoods['order_num'] = $orderNum;
            $tempGoods['discount_price'] = $_goods['sort_discount'] ? $_goods['price'] * ($_goods['sort_discount'] / 10 ) : $_goods['price'];
            $tempGoods['sort_discount'] = $_goods['sort_discount'];
            if($_goods['sort_discount']){
                $sortDiscount += ($_goods['price'] -  $_goods['price'] * ($_goods['sort_discount'] / 10 )) * $_goods['num'];
            } 
            if($_goods['host_id'] == 0){//主菜
                $goodsListFormat[$_goods['id']]['goods'] = $tempGoods;
            }else{
                $goodsListFormat[$_goods['host_id']]['child'][] = $tempGoods;
            }

            //非套餐商品
            if ($_goods['package_id'] == 0) {
                $totalPrice += $_goods['price']*$_goods['num'];
                $goodsNum += $_goods['num'];
            }
        }

        if(count($mustGoods) > 0){
            foreach($mustGoods as $_goods){
                if($_goods['sort_discount']){
                    $sortDiscount += ($_goods['price'] -  $_goods['price'] * ($_goods['sort_discount'] / 10 )) * $_goods['num'];
                }
            }
        }

        //购物车套餐商品
        $packageList = [];
        foreach ($goodsList as $value)
        {
            if ($value['package_id'] > 0) {
                $packageList[$value['uniqueness_number']]['package_id'] = $value['package_id'];
                $packageList[$value['uniqueness_number']]['package_num'] = $value['package_num'];
            }
        }

        if (!empty($packageList)) {
            foreach ($packageList as $_package) {
                $detail = (new FoodshopGoodsPackageService())->getOne($param = ['id' => $_package['package_id']]);
                $totalPrice += $detail['price'] * $_package['package_num'];
                $goodsNum += $_package['package_num'];
            }
        } 
        $diningOrderdetailService = new DiningOrderdetailService();
        // 迁移购物车至订单详情表
        foreach($goodsListFormat as $_goods){
            unset($_goods['goods']['id']);
            $_goods['goods']['status'] = $status;
            $_goods['goods']['third_id'] = $payOrderId;
            $insertId = $diningOrderdetailService->add($_goods['goods']);
            $addGoodsList[] = $_goods['goods'];
            if($insertId){
                if(isset($_goods['child'])&&$_goods['child']) {
                    $addSubGoodsList = [];
                    foreach ($_goods['child'] as $_child) {
                        $_child['host_id'] = $insertId;
                        $_child['status'] = $status;
                        $addGoodsList[] = $_child;
                        unset($_child['id']);
                        $addSubGoodsList[] = $_child;
                    }
                    $diningOrderdetailService->addAll($addSubGoodsList);
                }
            }else {
                throw new \think\Exception(L_('提交失败请稍后重试'), 1003);
            }
        }

        $storeFoodshop = (new MerchantStoreFoodshopService())->getStoreByStoreId($order['store_id']);
        if(!empty($storeFoodshop) && $storeFoodshop['settle_accounts_type'] == 1) {// 堂食先吃后付，提交订单后自动触发标签打印机
            foreach($goodsList as $key => $goods){
                if(isset($_goods['host_id']) && $_goods['host_id'] > 0) {// 只打印主菜
                    unset($goodsList[$key]);
                }
            }
            (new PrintHaddleService())->printLabel($goodsList,$order);
        }

        // 删除购物车
        $this->delGoodsByOrder($orderId);

        $note = L_('确认菜品');
        $data = [];
        if($staff){
            $data = [
                'operator_type' => 3,
                'operator_name' => $staff['name'],
                'operator_id' => $staff['id'],
            ];
        }

        if($tempId){// 快速点餐
            $data = [
                'operator_type' => 3,
            ];
            $note = L_('快速点餐确认菜品');
        }
        // 添加日志
        $logId = (new DiningOrderLogService())->addOrderLog($orderId, '13', $note,[],$data);
        // 更新订单
        $data = [
            'total_price' => get_format_number($order['total_price'] + $totalPrice),
            'goods_num' => $order['goods_num'] + $goodsNum,
            'goods_note' => serialize($goodsNote),
            'sort_discount' => $sortDiscount
        ];
        (new DiningOrderService())->updateByOrderId($orderId,$data);

        $returnArr['goods_list'] = array_merge($mustGoods,$addGoodsList);
        $returnArr['order_num'] = $orderNum;
        return $returnArr;
    }

    /**
     * 获取分类折扣信息
     * $onlySelect 是否仅仅是查询分类折扣信息
     */
    public function getSortDiscountInfo($goodsList,$onlySelect=false)
    {
        if(empty($goodsList)){
            return [];
        }

        foreach($goodsList as $key => $goods)
        {
            $condition = [];
            $condition[] = ['goods_id', '=', $goods['goods_id']];
            $condition[] = ['status', '=', 1];
            $goodsLibrary = $this->foodshopGoodsLibraryModel->field(['is_sort_discount','is_can_use_coupon','spec_sort_id'])->with(['sorts'])->where($condition)->find();
            $sort_discount = 0;
            if(empty($goodsLibrary) && !$onlySelect){
                throw_exception(L_('订单部分商品已下架，请重新选择商品！'));
            }
            if($goodsLibrary && $goodsLibrary->is_sort_discount == 1 && isset($goodsLibrary->sorts) && isset($goodsLibrary->sorts->sort_discount) && $goodsLibrary->sorts->sort_discount > 0){
                $sort_discount = $goodsLibrary->sorts->sort_discount / 10;
            }
            $goodsList[$key]['sort_discount'] = $sort_discount;
            $goodsList[$key]['can_discounts'] = $goodsLibrary ? $goodsLibrary->is_can_use_coupon :0;
        }
        return $goodsList;
    }

    /**
     * 根据订单id返回购物车列表
     * @param $orderId array 条件
     * @return array
     */
    public function getFormartGoodsDetailByOrderId($orderId){
        $goodsDetail = (new FoodshopCartService())->getCartDetail(['orderId'=>$orderId],[]);
        if(empty($goodsDetail['goods_list'])){
            return [];
        }

        $returnArr['goods_list'][0]['total_price'] = $goodsDetail['total_price'];
        $returnArr['goods_list'][0]['show_unlock'] = '0';
        $returnArr['goods_list'][0]['pay_price'] = 0;
        $returnArr['goods_list'][0]['count'] = $goodsDetail['num'];
        $returnArr['goods_list'][0]['status_str'] = L_('未下单');
        $returnArr['goods_list'][0]['goods_combine'] = [[
            'total_price' => $goodsDetail['total_price'],
            'discount_detail' => [],
            'number' => 1,
            'count' => $goodsDetail['num'],
            'number_str' => L_('#第X1次下单',['X1'=>1]),
            'goods' => $goodsDetail['goods_list'],
        ]];
        $returnArr['goods_list'][0]['discount_detail'] = [];
        $returnArr['goods_list'][0]['coupon_list'] = [];
        $returnArr['goods_list'][0]['discount_detail'] = [];
        $returnArr['num'] = $goodsDetail['num'];
        $returnArr['total_price'] =  $goodsDetail['total_price'];
        $returnArr['go_pay_money'] =  $goodsDetail['go_pay_money'];
        return $returnArr;
    }

    /**
     *获得可支付商品(当前购物车商品)快速点餐时需要
     * @param $orderId int
     * @param $user array 用户信息
     * @param $isStaff int 是否店员操作
     * @return array
     */
    public function getGoPayGoods($orderId, $isCheck = 1){
        if(empty($orderId)){
            throw new \think\Exception(L_("参数错误"), 1001);
        }


        // 获得当前待支付商品
        $orderDetail = $this->getGoodsListByOrderId($orderId);
        if(!$orderDetail && $isCheck){
            throw new \think\Exception(L_("抱歉！您当前没有需要结算的商品，暂不可支付哦~"), 1003);
        }

        $returnArr = [];
        // 组合附属商品
        $orderDetail = (new ShopGoodsService())->combinationData($orderDetail, 'spec', 0);

        // 第几次下单
        $orderNum = '0';

        // 商品总数
        $goodsGount = '0';

        // 商品总价
        $goodsTotalPrice = '0';

        //可以优惠的金额
        $canDiscountsPrice = '0';

        // 商品待支付总价
        $goPayMoney = '0';

        $goodsList = [];

        foreach($orderDetail as $_detail){
            $tempDetail = [];
            $tempDetail['goods_id'] = $_detail['goods_id'];
            $tempDetail['sort_id'] = $_detail['sort_id'];
            $tempDetail['name'] = $_detail['name'];
            $tempDetail['spec'] = trim(str_replace($_detail['spec_sub'],'',$_detail['spec']));
            $tempDetail['spec_sub'] = $_detail['spec_sub'];
            $tempDetail['sub_list'] = $_detail['sub_list'];
            $tempDetail['price'] = $_detail['price'];
            $tempDetail['discount_price'] = $_detail['price'];
            $tempDetail['unit'] = $_detail['unit'];
//            $tempDetail['num'] = $_detail['num'];
            if ($_detail['package_id'] == 0) {
                $tempDetail['num'] = $_detail['num'];
                $tempDetail['is_package_goods'] = false;
            } else {
                $tempDetail['num'] = $_detail['package_num'];
                $tempDetail['is_package_goods'] = true;
            }
            $tempDetail['uniqueness_number'] = $_detail['uniqueness_number'];
            $tempDetail['is_must'] = 2;//是否必点1是2否
            $tempDetail['is_staff'] = $_detail['is_staff']; //是否店员下单1是2否
            $goodsPrice = get_format_number($_detail['price'] * $tempDetail['num']);
            $tempDetail['total_price'] = $goodsPrice;//商品总价
            $tempDetail['old_total_price'] = $goodsPrice;//商品总价

            if($tempDetail['num']<=0){
                continue;
            }

            // 商品总数
            $goodsGount += $_detail['num'];

            // 商品总价
            $goodsTotalPrice += $goodsPrice;

            if(isset($goodsList[$_detail['uniqueness_number']]) ){
                $goodsList[$_detail['uniqueness_number']]['total_price'] += $_detail['price']*$tempDetail['num'];
                $goodsList[$_detail['uniqueness_number']]['num'] += $tempDetail['num'];
            }else{
                $goodsList[$_detail['uniqueness_number']] = $tempDetail;
            }

            //可优惠的金额
            if(isset($_detail['can_discounts']) && $_detail['can_discounts']){
                $canDiscountsPrice += $goodsPrice;
            }

            $goodsList[$_detail['uniqueness_number']]['total_price'] = get_format_number($goodsList[$_detail['uniqueness_number']]['total_price']);
        }

        $returnArr['goods_list'] = array_values($goodsList);
        $returnArr['goods_count'] = $goodsGount;
        $returnArr['order_num'] = $orderNum;
        $returnArr['goods_total_price'] = get_format_number($goodsTotalPrice);
        $returnArr['go_pay_money'] = get_format_number($goodsTotalPrice);
        $returnArr['can_discounts_price'] = get_format_number($canDiscountsPrice);
        $returnArr['goods_old_total_price'] = get_format_number($goodsTotalPrice);
        return $returnArr;
    }

    /**
     * 根据订单id返回购物车列表
     * @param $orderId array 条件
     * @return array
     */
    public function getGoodsListByOrderId($orderId){
        $order = [
            'id' => 'desc'
        ];
        $goodsList = $this->diningOrderTempModel->getGoodsListByOrderId($orderId,$order);
        if(!$goodsList) {
            return [];
        }
        return $goodsList->toArray();
    } 

    /**
     * 获取单个购物车商品信息
     * @param $where array 条件
     * @return array
     */
    public function getGoodsByCondition($where){
        $goods = $this->diningOrderTempModel->getGoodsByCondition($where);
        if(!$goods) {
            return [];
        }
        return $goods->toArray();
    }    
    
    /**
    * 获取购物车商品列表
    * @param $where array 条件
    * @return array
    */
   public function getGoodsListByCondition($where){
       $goods = $this->diningOrderTempModel->getGoodsListByCondition($where);
       if(!$goods) {
           return [];
       }
       return $goods->toArray();
   }


    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){

        $id = $this->diningOrderTempModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
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
            $result = $this->diningOrderTempModel->insertAll($data);
        } catch (\Exception $e) {
            return false;
        }
        
        return $result;
    }
       
    /**
     * 更新数据
     * @param $id
     * @param $data
     * @return bool
     */
    public function updateById($id,$data){
        if (!$id || !$data) {
            return false;
        }
        
        if (is_array($id)) {
            $where = [
                ['id', 'in', $id]
            ];
        } else {
            $where = [
                'id' => $id
            ];
        }
        

        try {
            $result = $this->diningOrderTempModel->where($where)->update($data);
        }catch (\Exception $e) {
            return false;
        }
        
        return $result;
    }
    
    
    /**
     * 删除数据
     * @param $where array 条件
     * @return array
     */
    public function delGoods($where){
        if(!$where){
            return false;
        }
        // delete方法返回影响数据的条数，没有删除返回 
        $result = $this->diningOrderTempModel->where($where)->delete();
        if(!$result) {
            return false;
        }
        return $result;
    }
    
    /**
     * 根据主键删除数据
     * @param $id sting 条件
     * @return array
     */
    public function delGoodsById($id){
        if(!$id){
            return false;
        }
        $where = [
            'id' => $id
        ];
        // delete方法返回影响数据的条数，没有删除返回 
        $result = $this->diningOrderTempModel->where($where)->delete();
        if(!$result) {
            return false;
        }
        return $result;
    }
    
    /**
     * 根据订单id删除数据
     * @param $id sting 条件
     * @return array
     */
    public function delGoodsByOrder($id){
        if(!$id){
            return false;
        }

        $where = [
            'order_id' => $id
        ];

        // delete方法返回影响数据的条数，没有删除返回 
        $result = $this->diningOrderTempModel->where($where)->delete();
        if(!$result) {
            return false;
        }
        return $result;
    }
    
}