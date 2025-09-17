<?php
/**
 * 餐饮临时订单service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/02 11:27
 */

namespace app\foodshop\model\service\order;
use app\foodshop\model\db\DiningOrderTemporary as DiningOrderTemporaryModel;
use app\foodshop\model\service\store\MerchantStoreFoodshopService;
use app\foodshop\model\service\package\FoodshopGoodsPackageService;
class DiningOrderTemporaryService {
    public $diningOrderTemporaryModel = null;
    public function __construct()
    {
        $this->diningOrderTemporaryModel = new DiningOrderTemporaryModel();
    }

    /**
     * 根据条件返回订单列表
     * @param $tempId int 订单id
     * @return array
     */
    public function moveOrder($tempId, $status = 1, $confirmOrder='0'){
        $tempOrder = $this->getOrderByTempId($tempId);
        if(!$tempOrder) {
            throw new \think\Exception(L_('订单不存在'), 1003);
        }

        if ($tempOrder['user_type'] == 'uid' && $tempOrder['user_id']) {
            $real_orderid = build_real_orderid($tempOrder['user_id']);//real_orderid
        }else{
            $id = mt_rand(1000,9999);
            $real_orderid = build_real_orderid($id);//real_orderid
        }

        $nowTime = time();

        // 订单数据
        $addData = [
            'mer_id' => $tempOrder['mer_id'],
            'store_id' => $tempOrder['store_id'],
            'order_from' => $tempOrder['order_from'],
            'user_type' => $tempOrder['user_type'],
            'user_id' => $tempOrder['user_id'],
            'status' => $status,
            'real_orderid' => $real_orderid,
            'create_time' => $nowTime,
            'temp_id' => $tempOrder['temp_id'],
            'avatar' => $tempOrder['avatar'],
            'name' => $tempOrder['name'],
        ];

        // 餐饮店铺详情
        $foodshopStore = (new MerchantStoreFoodshopService())->getStoreByStoreId($tempOrder['store_id']);
        $addData['settle_accounts_type'] = $foodshopStore['settle_accounts_type'];//结算方式 1-先吃后付 2-先付后吃

        if($orderId = (new DiningOrderService())->add($addData)){

            // 更新临时订单信息
            $saveData = [
                'order_id' => $orderId,
                'status' => 1,
//                'update_time' => time(),
            ];
            $this->updateByTempId($tempId, $saveData);

            // 更新日志
            (new DiningOrderLogService())->updateThis(['temp_id'=>$tempId],['order_id'=>$orderId]);

            // 查看购物车
            $goodsList = (new DiningOrderDetailTemporaryService())->getGoodsListByTempId($tempId);
            if($goodsList){
                $addGoodsList = [];
                if($confirmOrder){//简易模式直接生成待结算订单
                    // 订单总额
                    $totalPrice = 0;
                    
                    // 商品总数
                    $goodsNum = 0;

                    // 迁移临时购物车至订单详情表
                    $addGoodsList = [];
                    $goodsListFormat = [];
                    foreach($goodsList as $_goods){
                        $tempGoods = $_goods;
                        $tempGoods['create_time'] = $nowTime;
                        $tempGoods['order_id'] = $orderId;
                        $tempGoods['status'] = 2;
                        $tempGoods['order_num'] = 1;
                        $tempGoods['is_lock'] = 1;
                        $tempGoods['user_type'] =  $tempOrder['user_type'];
                        $tempGoods['user_id'] = $tempOrder['user_id'];
                        $tempGoods['discount_price'] = $_goods['price'];

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

                    //临时购物车套餐商品
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

                    $diningOrderdetailService = new DiningOrderDetailService();
                    // 迁移购物车至订单详情表
                    foreach($goodsListFormat as $_goods){
                        unset($_goods['goods']['id']);
                        unset($_goods['goods']['temp_id']);
                        $insertId = $diningOrderdetailService->add($_goods['goods']);
                        $addGoodsList[] = $_goods;
                        if($insertId){
                            if(isset($_goods['child']) && $_goods['child']) {
                                $addSubGoodsList = [];
                                foreach ($_goods['child'] as $_child) {
                                    unset($_child['id']);
                                    unset($_child['temp_id']);
                                    $_child['host_id'] = $insertId;
                                    $addGoodsList[] = $_child;
                                    $addSubGoodsList[] = $_child;
                                }
                                $diningOrderdetailService->addAll($addSubGoodsList);
                            }
                        }else {
                            throw new \think\Exception(L_('提交失败请稍后重试'), 1003);
                        }
                    }

                    // 更新订单金额
                    $data = [
                        'total_price' => get_format_number($totalPrice),
                        'goods_num' => $goodsNum
                    ];
                    (new DiningOrderService())->updateByOrderId($orderId, $data);

                }else{
                     // 迁移临时购物车至购物车
                    $addGoodsList = [];
                    $goodsListFormat = [];
                    foreach($goodsList as $_goods){
                        $tempGoods = $_goods;
                        $tempGoods['create_time'] = $nowTime;
                        $tempGoods['order_id'] = $orderId;

                        if($_goods['host_id'] == 0){//主菜
                            $goodsListFormat[$_goods['id']]['goods'] = $tempGoods;
                        }else{
                            $goodsListFormat[$_goods['host_id']]['child'][] = $tempGoods;
                        }
                    }

                    $diningOrderTempService = new DiningOrderTempService();
                    // 迁移购物车至订单详情表
                    foreach($goodsListFormat as $_goods){
                        unset($_goods['goods']['id']);

                        if(isset($_goods['goods']['temp_id'])){
                            unset($_goods['goods']['temp_id']);
                        }
                        $insertId = $diningOrderTempService->add($_goods['goods']);
                        $addGoodsList[] = $_goods;
                        if($insertId){
                            if(isset($_goods['child'])&&$_goods['child']) {
                                $addSubGoodsList = [];
                                foreach ($_goods['child'] as $_child) {
                                    unset($_child['id']);
                                    if(isset($_child['temp_id'])){
                                        unset($_child['temp_id']);
                                    }
                                    $_child['host_id'] = $insertId;
                                    $addGoodsList[] = $_child;
                                    $addSubGoodsList[] = $_child;
                                }
                                $diningOrderTempService->addAll($addSubGoodsList);
                            }
                        }else {
                            throw new \think\Exception(L_('提交失败请稍后重试'), 1003);
                        }
                    }
                }
            }
            return $orderId;
        }else{
            throw new \think\Exception(L_('订单保存失败，请稍后重试'), 1003);
        }
    }    
    

    /**
     * 根据条件返回订单列表
     * @param $tempId int 订单id
     * @return array
     */
    public function getOrderByTempId($tempId,$order=[]){
        $order = $this->diningOrderTemporaryModel->getOrderByTempId($tempId,$order);
        if(!$order) {
            return [];
        }
        return $order->toArray();
        
    }    
    
    /**
    * 根据条件返回订单列表
    * @param $where array 条件
    * @param $order array 排序
    * @return array
    */
   public function getOrderListByCondition($where, $order = ['order_id'=>'desc']){
       $orderList = $this->diningOrderTemporaryModel->getOrderListByCondition($where, $order);
       if(!$orderList) {
           return [];
       }
       return $orderList->toArray();
       
   }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $result = $this->diningOrderTemporaryModel->save($data);
        if(!$result) {
            return false;
        }
        return $this->diningOrderTemporaryModel->id;
        
    }
    
       
    /**
     * 更新数据
     * @param $tempId
     * @param $data
     * @return bool
     */
    public function updateByTempId($tempId,$data){
        if (!$tempId || !$data) {
            return false;
        }
        
        $where = [
            'temp_id' => $tempId
        ];

        try {
            $result = $this->diningOrderTemporaryModel->where($where)->update($data);
        }catch (\Exception $e) {
            return false;
        }
        
        return $result;
    }
    
    
    

}