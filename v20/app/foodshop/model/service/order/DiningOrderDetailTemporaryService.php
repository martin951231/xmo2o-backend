<?php
/**
 * 餐饮订单商品详情service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/30 17:46
 */

namespace app\foodshop\model\service\order;
use app\foodshop\model\db\DiningOrderDetailTemporary as DiningOrderDetailTemporaryModel;
use app\shop\model\service\goods\ShopGoodsService as ShopGoodsService;
class DiningOrderDetailTemporaryService {
    public $diningOrderDetailTemporaryModel = null;
    public function __construct()
    {
        $this->diningOrderDetailTemporaryModel = new DiningOrderDetailTemporaryModel();
    }   
    /**
 *获得用户可支付商品(属于当前用户的商品)
 * @param $orderId int  
 * @return array
 */
public function getGoPayGoods($tempId, $user){
    if(empty($tempId) || empty($user)){
        throw new \think\Exception(L_("参数错误"), 1001);
    }

    // 获得当前用户待支付商品
    $where = [
        ['temp_id', '=', $tempId],
    ];
    $orderDetail = $this->getGoodsListByCondition($where);
    if(!$orderDetail){
        throw new \think\Exception(L_("抱歉！您当前没有需要结算的商品，暂不可支付哦~"), 1003);
    }

    //订单套餐商品详情
    $orderPackageDetail = [];
    foreach ($orderDetail as $key => $value)
    {
        if ($value['package_id'] > 0) {
            $orderPackageDetail[] = $value;
            unset($orderDetail[$key]);
        }
    }

    $returnArr = [];
    // 组合附属商品
    $orderDetail = (new ShopGoodsService())->combinationData($orderDetail, 'spec', 0);
    // 组合套餐商品
    if (!empty($orderPackageDetail)) {
        $orderPackageDetail = (new ShopGoodsService())->combinationPackageData($orderPackageDetail, 'spec', 0);

        $orderDetail = array_merge($orderDetail, $orderPackageDetail);
    }

    // 第几次下单
    $orderNum = '0';

    // 商品总数
    $goodsGount = '0';

    // 商品总价
    $goodsTotalPrice = '0';
    
    // 商品待支付总价
    $goPayMoney = '0';

    //可以优惠的金额
    $canDiscountsPrice = '0';

    //原价
    $goodsOldTotalPrice = '0';

    $goodsList = [];

    foreach($orderDetail as $_detail){
        $tempDetail = [];
        if ($_detail['package_id'] == 0) {
            $tempDetail['num'] = $_detail['num']-($_detail['refundNum']??0);
            $tempDetail['is_package_goods'] = false;
        } else {
            $tempDetail['num'] = $_detail['package_num']-($_detail['refundNum']??0)-($_detail['verificNum']??0);
            $tempDetail['is_package_goods'] = true;
        }
//        $goodsPrice = get_format_number($_detail['price'] * ($_detail['num']-$_detail['verificNum']));
        $goodsPrice = get_format_number($_detail['discount_price'] * $tempDetail['num']);
        $tempDetail['goods_id'] = $_detail['goods_id'];
        $tempDetail['sort_id'] = $_detail['sort_id'];
        $tempDetail['name'] = $_detail['name'];
        $tempDetail['spec'] = trim(str_replace($_detail['spec_sub'],'',$_detail['spec']));
        $tempDetail['spec_sub'] = $_detail['spec_sub'];
        $tempDetail['price'] = $_detail['price'];
        $tempDetail['unit'] = $_detail['unit'];
        $tempDetail['num'] = $_detail['num'];
        $tempDetail['uniqueness_number'] = $_detail['uniqueness_number'];
        $tempDetail['total_price'] = $goodsPrice;//商品总价
        $oldTotalPrice = get_format_number($_detail['price'] * $tempDetail['num']);
        if ($_detail['package_id'] == 0) {
            $tempDetail['is_package_goods'] = false;
        } else {
            $tempDetail['is_package_goods'] = true;
        }

        
        // 商品总数
        $goodsGount += $_detail['num'];

        // 商品总价
        $goodsTotalPrice += $goodsPrice;
        $goodsOldTotalPrice += $oldTotalPrice;

        if(isset($goodsList[$_detail['goods_id']])){
            $goodsList[$_detail['goods_id']]['total_price'] += $_detail['price'];
            $goodsList[$_detail['goods_id']]['num'] += $_detail['num'];
        }else{
            $goodsList[$_detail['goods_id']] = $tempDetail;
        }
        //可优惠的金额
        if(isset($_detail['can_discounts']) && $_detail['can_discounts']){
            $canDiscountsPrice += $goodsPrice;
        }
        $goodsList[$_detail['goods_id']]['total_price'] = get_format_number($goodsList[$_detail['goods_id']]['total_price']);
    }

    $returnArr['goods_list'] = array_values($goodsList);
    $returnArr['goods_count'] = $goodsGount;
    $returnArr['order_num'] = 1;
    $returnArr['goods_total_price'] = get_format_number($goodsTotalPrice);
    $returnArr['go_pay_money'] = get_format_number($goodsTotalPrice);
    $returnArr['can_discounts_price'] = get_format_number($canDiscountsPrice);
    $returnArr['goods_old_total_price'] = get_format_number($goodsOldTotalPrice);
    return $returnArr;
}

   
    /**
     * 根据订单id返回购物车列表
     * @param $tempId array 条件
     * @return array
     */
    public function getGoodsListByTempId($tempId){
        $order = [
            'id' => 'desc'
        ];
        $goodsList = $this->diningOrderDetailTemporaryModel->getGoodsListByTempId($tempId,$order);
        if(!$goodsList) {
            return [];
        }
        return $goodsList->toArray();
    }


    /**
     * 根据订单id返回购物车列表
     * @param $orderId array 条件
     * @return array
     */
    public function getGoodsListByOrderId($orderId){
        $goodsList = $this->diningOrderDetailTemporaryModel->getGoodsListByOrderId($orderId);
        if(!$goodsList) {
            return [];
        }
        return $goodsList->toArray();
    }

    /**
     * 获取购物车商品信息
     * @param $where array 条件
     * @return array
     */
    public function getGoodsByCondition($where){
        $goods = $this->diningOrderDetailTemporaryModel->getGoodsByCondition($where);
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
        $goods = $this->diningOrderDetailTemporaryModel->getGoodsListByCondition($where);
        if(!$goods) {
            return [];
        }
        return $goods->toArray();
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
        
        $where = [
            'id' => $id
        ];

        try {
            $result = $this->diningOrderDetailTemporaryModel->where($where)->update($data);
        }catch (\Exception $e) {
            return false;
        }
        
        return $result;
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
//        $result = $this->diningOrderDetailTemporaryModel->save($data);
//        if(!$result) {
//            return false;
//        }
//
//        return $this->diningOrderDetailTemporaryModel->id;
        unset($data['staff_id']);
        unset($data['is_staff']);
        $id = $this->diningOrderDetailTemporaryModel->insertGetId($data);
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
            $result = $this->diningOrderDetailTemporaryModel->insertAll($data);
        } catch (\Exception $e) {
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
        $result = $this->diningOrderDetailTemporaryModel->where($where)->delete();
        if(!$result) {
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
        $result = $this->diningOrderDetailTemporaryModel->where($where)->delete();
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
            'temp_id' => $id
        ];
        
        // delete方法返回影响数据的条数，没有删除返回 
        $result = $this->diningOrderDetailTemporaryModel->where($where)->delete();
        if(!$result) {
            return false;
        }
        return $result;
    }

}