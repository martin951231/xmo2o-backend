<?php

/**
 * 餐饮订单service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/30 17:46
 */

namespace app\foodshop\model\service\order;

use app\common\model\service\coupon\MerchantCouponService;
use app\common\model\service\coupon\SystemCouponService;
use app\common\model\service\GoodsScoreService;
use app\common\model\service\ScrollMsgService;
use app\common\model\service\weixin\TemplateNewsService;
use app\foodshop\model\db\DiningOrder as DiningOrderModel;
use app\foodshop\model\db\DiningOrder;
use app\foodshop\model\db\DiningOrderPay;
use app\foodshop\model\service\goods\FoodshopGoodsLibraryService;
use app\common\model\service\UserService;
use app\common\model\service\AreaService;
use app\foodshop\model\service\message\SmsSendService;
use app\merchant\model\db\MerchantMoneyList;
use app\merchant\model\service\card\CardUserlistService;
use app\merchant\model\service\MerchantMoneyListService;
use app\merchant\model\service\MerchantStoreService as MerchantStoreService;
use app\merchant\model\service\MerchantService as MerchantService;
use app\foodshop\model\service\store\MerchantStoreFoodshopService as MerchantStoreFoodshopService;
use app\merchant\model\service\ShopDiscountService as ShopDiscountService;
use app\foodshop\model\service\store\FoodshopTableService as FoodshopTableService;
use app\foodshop\model\service\store\FoodshopTableTypeService as FoodshopTableTypeService;
use app\pay\model\service\PayService;
use app\shop\model\service\goods\ShopGoodsService as ShopGoodsService;
use app\merchant\model\service\card\CardNewService;
use app\foodshop\model\service\order_print\PrintHaddleService;
use app\foodshop\model\service\store\MerchantStoreStaffService;
use app\common\model\service\order\SystemOrderService;
use app\common\model\service\UserSpreadListService;
use app\foodshop\model\db\FoodshopGoodsLibrary;
use think\facade\Db;

class DiningOrderService
{
    public $diningOrderModel = null;
    public $diningOrderLogService = null;
    public $orderStatusArr = null;
    public $orderFromArr = null;
    public $tableOrderStatusArr = null;
    public $foodshopGoodsLibraryModel = null;
    public function __construct()
    {
        $this->diningOrderModel = new DiningOrderModel();
        $this->diningOrderLogService = new DiningOrderLogService();
        $this->foodshopGoodsLibraryModel = new FoodshopGoodsLibrary();
        $this->orderStatusArr = [
            "0" => L_("订单生成"),
            "1" => L_("待支付"),
            "2" => L_("未接单"),
            "3" => L_("就餐中"),
            "4" => L_("已完成"),
            "5" => L_("已取消"),
            "6" => L_("待落座"),
            "7" => L_("点餐中"),
        ];
        $this->orderFromArr = [
            "0" => "提前选桌",
            "1" => "桌台码",
            "2" => "线下开台",
            "3" => "提前选菜",
            "4" => "通用码",
            "5" => "快速点餐",
        ];
        $this->tableOrderStatusArr = [
            "1" => "就餐中",
            "2" => "点餐中",
            "3" => "待清台",
        ];
    }

    /**
     * 保存预订单
     * @param $param array 数据
     * @return array
     */
    public function bookSave($param, $user = [])
    {
        $storeId = $param['storeId'];
        // 订单来源 0-在线预订选桌，1-桌台二维码，2-店员下单，3-在线预订选菜，4-直接选菜
        $orderFrom = isset($param['orderFrom']) ? $param['orderFrom'] : 0;
        // 用餐人数
        $bookNum = isset($param['bookNum']) ? $param['bookNum'] : 2;
        // 桌台类型
        $tableType = isset($param['tableType']) ? $param['tableType'] : 0;
        // 预定时间
        $bookTime = isset($param['bookTime']) ? $param['bookTime'] : '';
        // 用户姓名
        $name = isset($param['name']) ? $param['name'] : '';
        // 手机号
        $phone = isset($param['phone']) ? $param['phone'] : '';
        // 手机区号
        $phoneCountryType = isset($param['phoneCountryType']) ? $param['phoneCountryType'] : '';
        // 性别1-男2-女0-其他
        $sex = isset($param['sex']) ? $param['sex'] : 0;
        // 性别1-男2-女0-其他
        $note = isset($param['note']) ? $param['note'] : '';

        // 用户id
        $uid = isset($param['uid']) ? $param['uid'] : 0;
        $nowUser = (new UserService())->getUser($uid);

        if (empty($storeId)) {
            throw new \think\Exception(L_("缺少参数"));
        }

        // 验证店铺状态
        try {
            // 不验证营业时间
            $foodshop = (new MerchantStoreFoodshopService())->checkStore($storeId, true, false);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }

        //在线订桌
        if (empty($name)) {
            throw new \think\Exception(L_("您的姓名不能为空"));
        }

        if (empty($phone)) {
            throw new \think\Exception(L_("您的电话不能为空"));
        }

        if (empty($bookTime)) {
            throw new \think\Exception(L_("预订时间不能为空"));
        }

        // 验证预定时间
        if (time() > strtotime($bookTime)) {
            throw new \think\Exception(L_('当前预订时间不符，请重新选择时间'));
        }

        $nowTime = time() + $foodshop['advance_time'] * 60;
        $bookTime = strtotime($bookTime);
        if ($nowTime > $bookTime) {
            throw new \think\Exception(L_('至少提前X1分钟预定', ['X1' => $foodshop['advance_time']]));
        }

        // 桌台类型信息
        $tableTypeDetail = (new \app\foodshop\model\service\store\FoodshopTableTypeService())->geTableTypeById($tableType);
        if (empty($tableTypeDetail)) {
            throw new \think\Exception(L_("没有您选择的桌位"));
        }

        if ($bookNum < $tableTypeDetail['min_people']) {
            throw new \think\Exception(L_("请您选择更少人数的桌位"));
        }

        if ($tableTypeDetail['is_add'] == 0 && $bookNum > $tableTypeDetail['max_people']) {
            throw new \think\Exception(L_("请您选择更多人数的桌位"));
        }

        // 查询是否已经被预定了
        $where[] = ['store_id', '=', $storeId];
        $where[] = ['book_time', '=', $bookTime];
        $where[] = ['table_type', '=', $tableType];
        $where[] = ['status', '>=', '0'];
        $where[] = ['status', '<', '40'];
        $orders = $this->getOrderListByCondition($where);
        if (count($orders) >= $tableTypeDetail['num']) {
            throw new \think\Exception(L_("该时段该桌台已订满，请重新选择桌台"));
        }

        // 保存订单信息
        $data = array(
            'mer_id' => $foodshop['mer_id'],
            'uid' => $uid,
            'store_id' => $storeId,
            'name' => $name,
            'phone' => $phone,
            'phone_country_type' => $phoneCountryType,
            'sex' => $sex,
            'book_num' => $bookNum,
            'book_time' => $bookTime,
            'table_type' => $tableType,
            'book_price' => isset($tableTypeDetail['deposit']) ? $tableTypeDetail['deposit'] : 0,
            'avatar' => $nowUser['avatar'],
            'note' => $note,
            'settle_accounts_type' => $foodshop['settle_accounts_type'], //结算方式 1-先吃后付 2-先付后吃
        );
        $data['create_time'] = time();
        $data['price'] = isset($tableTypeDetail['deposit']) ? $tableTypeDetail['deposit'] : 0;
        $data['is_book_pay'] = 3; //支付页面获知是定金不能使用折扣
        $data['real_orderid'] = build_real_orderid($uid); //real_orderid
        $data['user_type'] = 'uid';
        $data['user_id'] = $uid;

        if ($data['book_price'] <= 0) {
            // 不需要支付订单 状态直接保存为已预订
            $data['status'] = 1;
        }

        $returnArr = [];
        if ($orderId = $this->add($data)) {
            // 订单类型
            $returnArr['order_type'] = 'foodshop';
            // 餐饮订单id
            $returnArr['order_id'] = $orderId;
            // 添加日志
            $this->diningOrderLogService->addOrderLog($orderId, '0', '在线选桌预定下单', $user);

            // 定金大于0，需要支付
            if ($data['book_price'] > 0) {
                $returnArr['order_type'] = 'dining';
                $payOrderParam = array(
                    'order_id' => $orderId,
                    'order_name' => '餐饮定金支付订单',
                    'user_id' => $uid,
                    'user_type' => 'uid',
                    'store_id' => $storeId,
                    'mer_id' => $foodshop['mer_id'],
                    'price' => $tableTypeDetail['deposit'],
                    'wx_cheap' => 0,
                    'orderid' => build_real_orderid($uid),
                );


                try {
                    // 添加支付订单
                    $result = (new DiningOrderPayService())->addPayOrder($payOrderParam);
                } catch (\Exception $e) {
                    throw new \think\Exception($e->getMessage());
                }

                // 保存订单为待支付
                $data = [
                    'status' => 2, //预定金待支付
                ];
                $this->updateByOrderId($orderId, $data);

                $returnArr['order_id'] = $result;
                $returnArr['type'] = 'dining';
            }
            return $returnArr;
        } else {
            throw new \think\Exception(L_("订座失败，稍后重试！"));
        }
    }

    /**
     * 就餐人数页(扫码点餐或提前选菜进入此方法)
     * @param $param array 数据
     * @return array
     */
    public function saveBookNum($param, $user)
    {
        // 店铺id
        $storeId = $param['storeId'];

        // 订单来源 0-在线预订选桌，1-桌台二维码，2-店员下单，3-在线预订选菜，4-直接选菜
        $orderFrom = isset($param['orderFrom']) ? $param['orderFrom'] : 0;

        // 桌台id
        $tableId = isset($param['tableId']) ? $param['tableId'] : 0;

        // 就餐人数
        $bookNum = isset($param['bookNum']) ? $param['bookNum'] : 0;


        // 订单id
        $orderId = isset($param['orderId']) ? $param['orderId'] : 0;

        if (empty($storeId)) {
            throw new \think\Exception(L_("缺少参数"));
        }

        // 验证店铺状态
        try {
            // 不验证营业时间
            $foodshop = (new MerchantStoreFoodshopService())->checkStore($storeId, true, false);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }

        //扫桌台二维码
        // 桌台信息
        $table = (new \app\foodshop\model\service\store\FoodshopTableService())->geTableById($tableId);
        if (!$table) {
            throw new \think\Exception(L_("桌台不存在"));
        }

        // 桌台类型
        $tableType = (new \app\foodshop\model\service\store\FoodshopTableTypeService())->geTableTypeById($table['tid']);

        if (!$bookNum) {
            throw new \think\Exception(L_("请输入就餐人数"));
        }

        // 更新数据
        $saveData = [];
        $saveData['book_num'] = $bookNum;

        // 查看自己就餐中订单
        $where = [];
        $where[] = ['user_type', '=', $user['user_type']];
        $where[] = ['user_id', '=', $user['user_id']];
        $where[] = ['table_id', '=', $tableId];
        $where[] = ['store_id', '=', $storeId];
        $myDiningOrder = $this->getDiningOrder($where);
        // 有自己的订单直接进入详情
        if ($myDiningOrder) { //有订单直接返回
            $returnArr['order_id'] = $myDiningOrder[0]['order_id'];
            return $returnArr;
        }

        // 验证当前有没有就餐中的订单
        $where = [];
        $where[] = ['table_id', '=', $tableId];
        $where[] = ['store_id', '=', $storeId];
        $diningOrder = $this->getDiningOrder($where);
        if ($diningOrder) {

            if ($foodshop['share_table_type'] == 1) {
                // 多人点餐 直接进入别人订单
                $returnArr['order_id'] = $diningOrder[0]['order_id'];
                return $returnArr;
            } elseif ($foodshop['share_table_type'] == 3) {
                // 关闭多人点餐
                throw new \think\Exception(L_("该桌台已有就餐中订单，请更换桌台扫码"));
            }
        }

        if ($orderId) {
            $where['order_id'] = $orderId;
            $saveData['status'] = '21'; //确认订单 点餐中
            $saveData['table_id'] = $tableId; //桌台id
            $saveData['table_type'] = $tableType['id']; //桌台类型id
            $result = $this->updateByOrderId($orderId, $saveData);
            if ($result) {
                $returnArr['order_id'] = $orderId;

                // 添加日志
                $this->diningOrderLogService->addOrderLog($orderId, '4', '输入就餐人数确认订单', $user);
            } else {
                throw new \think\Exception(L_("操作失败，请稍后重试"));
            }
        } else {
            //扫桌台码下单
            $data = [];
            $data['create_time'] = time();
            $data['status'] = 21; // 点餐中
            $data['order_from'] = 1;
            $data['book_num'] = $bookNum;
            $data['user_type'] = $user['user_type'];
            $data['user_id'] = $user['user_id'];
            $data['mer_id'] = $foodshop['mer_id'];
            $data['store_id'] = $foodshop['store_id'];
            $data['table_id'] = $tableId;
            $data['table_type'] = $tableType['id'];
            $data['avatar'] = $user['avatar'];
            $data['name'] = $user['nickname'] ?? '';
            $data['settle_accounts_type'] = $foodshop['settle_accounts_type']; //结算方式 1-先吃后付 2-先付后吃
            if ($user['uid']) {
                $data['real_orderid'] = build_real_orderid($user['uid']); //real_orderid
            } else {
                $id = mt_rand(1000, 9999);
                $data['real_orderid'] = build_real_orderid($id); //real_orderid
            }

            $orderId = $this->add($data);
            if ($orderId) {
                $returnArr['order_id'] = $orderId;

                // 添加日志
                $this->diningOrderLogService->addOrderLog($orderId, '5', '扫桌台码下单', $user);
            } else {
                throw new \think\Exception(L_("订座失败，请稍后重试"));
            }
        }

        // 更新桌台状态
        $tableData['id'] = $tableId;
        $tableData['status'] = 1;
        (new FoodshopTableService())->updateTable($tableData);

        return $returnArr;
    }

    /**
     *添加必点菜
     * @param $orderId int 订单id
     * @return array
     */
    public function addMustGoods($orderId, $num = 0, $orderNum = 0, $status = 1)
    {
        if (empty($orderId)) {
            return false;
        }

        // 订单详情
        $orderDetail = $this->getOrderByOrderId($orderId);
        if (!$orderDetail || $orderDetail['book_num'] == 0) {
            return false;
        }

        // 必点菜列表
        $mustGoods = (new FoodshopGoodsLibraryService())->getMustGoodsByStoreId($orderDetail['store_id']);
        if (!$mustGoods) {
            return false;
        }

        if (empty($orderNum)) {
            $orderNum = (new DiningOrderdetailService())->getNextOrderNum($orderId);
        }

        if (empty($num)) {
            $num = $orderDetail['book_num'];
        }
        $saveData = [];
        $goodsNum = 0;
        $totalPrice = 0;
        foreach ($mustGoods as $_goods) {
            $tmpData = [];
            $tmpData['order_id'] = $orderDetail['order_id'];
            $tmpData['store_id'] = $orderDetail['store_id'];
            $tmpData['goods_id'] = $_goods['goods_id'];
            $tmpData['sort_id'] = $_goods['spec_sort_id'];
            $tmpData['name'] = $_goods['name'];
            $tmpData['price'] = $_goods['price'];
            $tmpData['discount_price'] = $_goods['price'];
            $tmpData['package_id'] = 0;
            $tmpData['unit'] = $_goods['unit'];
            $tmpData['num'] = $num;
            $tmpData['create_time'] = time();
            $tmpData['is_must'] = 1;
            $tmpData['order_num'] = $orderNum;
            $tmpData['status'] = $status;
            $tmpData['uniqueness_number'] = $_goods['goods_id'];

            //查看优惠
            $condition = [];
            $condition[] = ['goods_id', '=', $_goods['goods_id']];
            $condition[] = ['status', '=', 1];
            $goodsLibrary = $this->foodshopGoodsLibraryModel->field(['is_sort_discount','is_can_use_coupon','spec_sort_id'])->with(['sorts'])->where($condition)->find();
            $sort_discount = 0;
            if($goodsLibrary && $goodsLibrary->is_sort_discount == 1 && isset($goodsLibrary->sorts) && isset($goodsLibrary->sorts->sort_discount) && $goodsLibrary->sorts->sort_discount > 0){
                $sort_discount = $goodsLibrary->sorts->sort_discount / 10;
            }
            $tmpData['sort_discount'] = $sort_discount;
            $tmpData['can_discounts'] = $goodsLibrary->is_can_use_coupon;
            $tmpData['discount_price'] = $sort_discount ? $_goods['price'] * ($sort_discount / 10 ) : $_goods['price'];

            $saveData[] = $tmpData;
            $goodsNum += $num;
            $totalPrice += $_goods['price'] * $num;
        }

        $returnArr = [];
        $returnArr['goods'] = $saveData;
        $returnArr['order_num'] = $orderNum;
        if ((new DiningOrderDetailService())->addAll($saveData)) {
            // 订单备注
            $goodsNote = $orderDetail['goods_note'] ? unserialize($orderDetail['goods_note']) : [];

            $goodsNote[($orderNum - 1)] = '';

            $data = [
                'total_price' => get_format_number($orderDetail['total_price'] + $totalPrice),
                //                'goods_num' => $orderDetail['goods_num'] + $goodsNum,
                'goods_note' => serialize($goodsNote)
            ];
            (new DiningOrderService())->updateByOrderId($orderId, $data);
            return $returnArr;
        } else {
            return false;
        }
    }

    /**
     *删除订单
     */
    public function orderDel($orderId)
    {
        $ret=(new DiningOrder())->updateThis(['order_id'=>$orderId],['is_del'=>1]);
        if($ret!==false){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 选菜页没有订单的情况下获取订单信息
     * @param $param array 
     * @param $user array 
     * @return array
     */
    public function getOrderInfo($param, $user)
    {
        // 店铺id
        $storeId = $param['storeId'];

        // 订单来源 0-在线预订选桌，1-桌台二维码，2-店员下单，3-在线预订选菜，4-直接选菜
        $orderFrom = isset($param['orderFrom']) ? $param['orderFrom'] : 0;

        // 桌台id
        $tableId = isset($param['tableId']) ? $param['tableId'] : 0;

        // 扫码
        $scan = isset($param['scan']) ? $param['scan'] : 0;

        // 返回数据
        $returnArr = [];

        if(empty($storeId)){
			throw new \think\Exception(L_("缺少参数"),1001);
        }

        // 验证店铺状态
        // 不验证营业时间
        $foodshop = (new MerchantStoreFoodshopService())->checkStore($storeId,true,false);
       

        if ($scan == 1) { //扫码
            if ($tableId) {
                //扫桌台二维码
                // 桌台信息
                $table = (new \app\foodshop\model\service\store\FoodshopTableService())->geTableById($tableId);   
                if(!$table){
                    throw new \think\Exception(L_("桌台不存在"),1003);
                }

                // 查看自己就餐中订单
                $where = [];
                $where[] = ['user_type', '=', $user['user_type']];
                $where[] = ['user_id', '=', $user['user_id']];
                $where[] = ['table_id', '=', $tableId];
                $where[] = ['store_id', '=', $storeId];
                $myDiningOrder = $this->getDiningOrder($where);
                // 有自己的订单直接进入详情
                if ($myDiningOrder) { //有订单直接返回
                    $returnArr['order_id'] = $myDiningOrder['order_id'];
                    $returnArr['type'] = 1;

                    // 查看是否有已确认菜品 有则跳到订单详情没有则留在选菜页
                    $returnArr['redirect'] = '';
                    $goods = (new DiningOrderDetailService())->getOrderDetailByOrderId($returnArr['order_id']);
                    if ($goods) {
                        $returnArr['redirect'] = 'order_detail';
                    }
                    return $returnArr;
                }

                // 验证当前有没有就餐中的订单
                $where = [];
                $where[] = ['table_id', '=', $tableId];
                $where[] = ['store_id', '=', $storeId];
                $diningOrder = $this->getDiningOrder($where);
                if ($diningOrder) {

                    if ($foodshop['share_table_type'] == 1) {
                        // 多人点餐
                        $returnArr['order_id'] = $diningOrder['order_id'];
                        $returnArr['type'] = 1;

                        // 查看是否有已确认菜品 有则跳到订单详情没有则留在选菜页
                        $returnArr['redirect'] = '';
                        $goods = (new DiningOrderDetailService())->getOrderDetailByOrderId($returnArr['order_id']);
                        if ($goods) {
                            $returnArr['redirect'] = 'order_detail';
                        }
                    } elseif ($foodshop['share_table_type'] == 2) {
                        // 拼桌
                        // 查找预订单
                        $where = [];
                        $where[] = ['store_id', '=', $storeId];
                        $bookOrderList = $this->getBookOrder($user, $where);
                        if ($bookOrderList) {
                            foreach ($bookOrderList as &$_bookOrder) {
                                $_bookOrder['take_seat'] = 0;
                                if ($_bookOrder['book_num'] > 0 && $_bookOrder['order_from'] == 0) {
                                    // 提前选桌需要携带是否落座字段
                                    $_bookOrder['take_seat'] = 1;
                                }
                            }
                            if (count($bookOrderList) == 1) {
                                $returnArr['order_id'] = $bookOrderList[0]['order_id'];
                                $returnArr['take_seat'] = $bookOrderList[0]['take_seat'];
                                $returnArr['table_type'] = $bookOrderList[0]['table_type'];

                                $returnArr['type'] = 1;
                            } else {
                                $returnArr['order_list'] = $bookOrderList;
                                $returnArr['type'] = 3;
                            }
                        } else {
                            // 跳转选择人数页面
                            $returnArr['order_from'] = 1;
                            $returnArr['type'] = 4;
                        }
                    } elseif ($foodshop['share_table_type'] == 3) {
                        // 关闭多人点餐
                        $returnArr['msg'] = '当前桌台正在就餐中，您可以去看看其他空闲桌台哦！';
                        $returnArr['type'] = 5;
                    }
                } else {
                    $where = [];
                    $where[] = ['store_id', '=', $storeId];
                    $bookOrderList = $this->getBookOrder($user, $where);
                    if ($bookOrderList) {
                        foreach ($bookOrderList as &$_bookOrder) {
                            $_bookOrder['take_seat'] = 0;
                            if ($_bookOrder['book_num'] > 0 && $_bookOrder['order_from'] == 0) {
                                // 提前选桌需要携带是否落座字段
                                $_bookOrder['take_seat'] = 1;
                            }
                        }
                        if (count($bookOrderList) == 1) {
                            $returnArr['order_id'] = $bookOrderList[0]['order_id'];
                            $returnArr['take_seat'] = $bookOrderList[0]['take_seat'];
                            $returnArr['order_from'] = $bookOrderList[0]['order_from'];
                            $returnArr['table_type'] = $bookOrderList[0]['table_type'];

                            $returnArr['type'] = 1;
                            if ($bookOrderList[0]['order_from'] == 3) {
                                $returnArr['redirect'] = 'book_num';
                            }
                        } else {
                            $returnArr['order_list'] = $bookOrderList;
                            $returnArr['type'] = 3;
                        }
                    } else {
                        // 跳转选择人数页面
                        $returnArr['order_from'] = 1;
                        $returnArr['type'] = 4;
                    }
                }
            } else {
                //扫固定二维码
                // 查看自己就餐中订单(不绑定桌台号)
                $where = [];
                $where[] = ['user_type', '=', $user['user_type']];
                $where[] = ['user_id', '=', $user['user_id']];
                $where[] = ['table_id', '=', '0'];
                $where[] = ['store_id', '=', $storeId];
                $myDiningOrder = $this->getDiningOrder($where);
                // 有自己的订单直接进入详情
                if ($myDiningOrder) { //有订单直接返回

                    $returnArr['order_id'] = $myDiningOrder['order_id'];
                    $returnArr['type'] = 1;

                    // 查看是否有已确认菜品 有则跳到订单详情没有则留在选菜页
                    $returnArr['redirect'] = '';
                    $goods = (new DiningOrderDetailService())->getOrderDetailByOrderId($returnArr['order_id']);
                    if ($goods) {
                        $returnArr['redirect'] = 'order_detail';
                    }
                    return $returnArr;
                } else {
                    // 查找预订单
                    $where = [];
                    $where[] = ['store_id', '=', $storeId];
                    $bookOrderList = $this->getBookOrder($user, $where);
                    if ($bookOrderList) {
                        foreach ($bookOrderList as &$_bookOrder) {
                            $_bookOrder['take_seat'] = 0;
                            if ($_bookOrder['book_num'] > 0 && $_bookOrder['order_from'] == 0) {
                                // 提前选桌需要携带是否落座字段
                                $_bookOrder['take_seat'] = 1;
                            }
                        }
                        if (count($bookOrderList) == 1) {
                            $returnArr['order_id'] = $bookOrderList[0]['order_id'];
                            $returnArr['take_seat'] = $bookOrderList[0]['take_seat'];
                            $returnArr['type'] = 1;
                        } else {
                            $returnArr['order_list'] = $bookOrderList;
                            $returnArr['type'] = 3;
                        }
                    } else {
                        //创建临时订单
                        $data = [];
                        $data['user_type'] = $user['user_type'];
                        $data['user_id'] = $user['user_id'];
                        $data['name'] = $user['nickname'] ?? '';
                        $data['mer_id'] = $foodshop['mer_id'];
                        $data['store_id'] = $foodshop['store_id'];
                        $data['create_time'] = time();
                        $data['update_time'] = time();
                        $data['order_from'] = 4;
                        $tempId = (new DiningOrderTemporaryService())->add($data);
                        if ($tempId) {
                            $returnArr['type'] = 2;
                            $returnArr['temp_id'] = $tempId;

                            // 添加日志
                            $this->diningOrderLogService->addTempOrderLog($tempId, '0', '扫通用码下单', $user);
                        }else{
                            throw new \think\Exception(L_("订单创建失败，请重试"),1005);
                        }
                    }
                }
            }
        } else {
            //通过连链接进入的
            if ($orderFrom == 3) { //选菜
                // 提前选菜
                $where[] = ['order_from', '=', '3'];
                $where[] = ['store_id', '=', $storeId];
                $bookOrder = $this->getBookOrder($user, $where);
                if ($bookOrder) {
                    $returnArr['type'] = 1;
                    $returnArr['order_id'] = $bookOrder[0]['order_id'];
                } else {
                    //创建临时订单
                    // var_dump($user);
                    $data = [];
                    $data['user_type'] = $user['user_type'];
                    $data['user_id'] = $user['user_id'];
                    $data['name'] = $user['nickname'] ?? '';
                    $data['mer_id'] = $foodshop['mer_id'];
                    $data['store_id'] = $foodshop['store_id'];
                    $data['create_time'] = time();
                    $data['update_time'] = time();
                    $data['order_from'] = 3;
                    $tempId = (new DiningOrderTemporaryService())->add($data);

                    if ($tempId) {
                        $returnArr['type'] = 2;
                        $returnArr['temp_id'] = $tempId;

                        // 添加日志
                        $this->diningOrderLogService->addTempOrderLog($tempId, '0', '提前选菜下单', $user);
                    }else{
                        throw new \think\Exception(L_("订单创建失败，请重试"),1005);
                    }
                }
            } elseif ($orderFrom == 4) { //快速点餐
                $where = [];
                $where[] = ['user_type', '=', $user['user_type']];
                $where[] = ['user_id', '=', $user['user_id']];
                $where[] = ['order_from', '=', '4'];
                $where[] = ['store_id', '=', $storeId];
                $myDiningOrder = $this->getDiningOrder($where);
                if ($myDiningOrder) {
                    $returnArr['type'] = 1;
                    $returnArr['order_id'] = $myDiningOrder['order_id'];

                    // 查看是否有已确认菜品 有则跳到订单详情没有则留在选菜页
                    $returnArr['redirect'] = '';
                    $goods = (new DiningOrderDetailService())->getOrderDetailByOrderId($returnArr['order_id']);
                    if ($goods) {
                        $returnArr['redirect'] = 'order_detail';
                    }
                } else {
                    //创建临时订单
                    $data = [];
                    $data['user_type'] = $user['user_type'];
                    $data['user_id'] = $user['user_id'];
                    $data['name'] = $user['nickname'] ?? '';
                    $data['mer_id'] = $foodshop['mer_id'];
                    $data['store_id'] = $foodshop['store_id'];
                    $data['create_time'] = time();
                    $data['update_time'] = time();
                    $data['order_from'] = 4;
                    $tempId = (new DiningOrderTemporaryService())->add($data);
                    if ($tempId) {
                        $returnArr['type'] = 2;
                        $returnArr['temp_id'] = $tempId;

                        // 添加日志
                        $this->diningOrderLogService->addTempOrderLog($tempId, '0', '快速点餐下单', $user);
                    }else{
                        throw new \think\Exception(L_("订单创建失败，请重试"),1005);
                    }
                }
            }
        }
        return $returnArr;
    }


    /**
     * 在线选桌落座签到
     * @param $param 
     * @param $user array 用户
     * @return array
     */
    public function takeSeat($param, $user){
        $orderId = $param['orderId'];//订单id
        $tableId = $param['tableId'];//桌台id
        if(empty($orderId)){
			throw new \think\Exception(L_('参数错误'),1001);
        }

        // 查询订单
        $where[] = ['order_id', '=', $orderId];
        $where[] = ['user_type', '=', $user['user_type']];
        $where[] = ['user_id', '=', $user['user_id']];
        $where[] = ['is_temp', '=', 0];
        $nowOrder = $this->getOrderByCondition($where); 
        if(empty($nowOrder)){
			throw new \think\Exception(L_('订单不存在'),1003);
        }

        if($nowOrder['status'] != '1' && $nowOrder['status'] != '4'){
			throw new \think\Exception(L_('当前订单状态不可落座'),1003);
        }

        // 桌台信息
        $tableDetail = (new \app\foodshop\model\service\store\FoodshopTableService())->geTableById($tableId);
        if (empty($tableDetail)) {
            throw new \think\Exception(L_("桌台不存在"),1003);
        }

        if($nowOrder['store_id'] != $tableDetail['store_id']){
			throw new \think\Exception(L_('当前订单不可落座'),1003);
        }

        // 抱歉！您在线预订的桌台类型【大厅】与当前的桌台类型【包间】不符，请联系店员确认。
        if ($tableDetail['tid'] != $nowOrder['table_type']) {
            // 当前桌台的桌台类型信息
            $tableType = (new \app\foodshop\model\service\store\FoodshopTableTypeService())->geTableTypeById($tableDetail['tid']);
            // 预订的桌台类型信息
            $bookTableType = (new \app\foodshop\model\service\store\FoodshopTableTypeService())->geTableTypeById($nowOrder['table_type']);
            throw new \think\Exception(L_("抱歉！您在线预订的桌台类型【X1】与当前的桌台类型【X2】不符，请联系店员确认。",['X1'=>$bookTableType['name'],'X2'=>$tableType['name']]),1003);
           
        }

        // 更新数据
        $saveData = [
            'table_id' => $tableId,
            'status' => '21',
        ];

        // 保存订单信息
        try {
            $result = $this->updateByOrderId($orderId, $saveData);

            // 更新桌台状态
            $tableData['id'] = $tableId;
            $tableData['status'] = 1;
            (new FoodshopTableService())->updateTable($tableData);

            // 添加日志
            $this->diningOrderLogService->addOrderLog($orderId, '15', '签到落座', $user);
            return true;
        } catch (\Exception $e) {
            throw new \think\Exception(L_("落座失败，请稍后重试"), 1003);
        }
    }

    /**
     * 订单详情
     * @param $orderId int 订单id
     * @param $user array 用户
     * @param $order array 订单详情
     * @param $staff array 店员详情
     * @return array
     */
    public function getOrderDetail($orderId, $user = [], $order = [], $staff = [])
    {
        $returnArr = [];
        if ($order) {
            $returnArr['order'] = $order;
            $orderId = $order['order_id'];
        } else {
            $returnArr = $this->getWapOrderDetail($orderId, $user);
        }

        $returnArr['order']['total_price'] = get_format_number($returnArr['order']['total_price']-$returnArr['order']['refund_goods_price']);

        // 餐饮店铺详情
        $foodshopStore = (new MerchantStoreFoodshopService())->getStoreByStoreId($returnArr['order']['store_id']);

        $returnArr['order']['goods_detail'] = [];
        $returnArr['order']['goods_num'] = 0; // 已点菜品总数
        $returnArr['order']['go_pay_money'] = 0; // 商品待支付总价
        $returnArr['order']['go_pay_num'] = 0; // 商品待支付数量
        $returnArr['order']['goods_total_price'] = 0; // 商品总价
        $returnArr['order']['order_status'] = $this->getOrderStatus($returnArr['order']);
        $returnArr['order']['order_status_txt'] = $this->orderStatusArr[$returnArr['order']['order_status']];

        // 判断是否显示第几次下单 预订单，快速点餐，自取不显示第几次下单
        $returnArr['order']['show_order_num'] = 1;

        // 订单商品详情
        if ($returnArr['order']['is_temp'] || (in_array($returnArr['order']['status'], [1, 4, 51, 60]) && in_array($returnArr['order']['order_from'], [0, 3]))) {
            // 预订单显示购物车详情
            $returnArr['order']['show_order_num'] = 0; //不显示第几次下单
            $goodsDetail = (new DiningOrderTempService())->getFormartGoodsDetailByOrderId($orderId, $user);
        } else {
            $goodsDetail = (new DiningOrderDetailService())->getFormartGoodsDetailByOrderId($returnArr['order'], $foodshopStore, $user, $staff);
            $goods_list = [];
            if (!empty($goodsDetail['goods_list'])) {
                $order_num = array_unique(array_column($goodsDetail['goods_list'], 'order_num'));
                sort($order_num);

                foreach ($order_num as $num) {
                    foreach ($goodsDetail['goods_list'] as $goods) {
                        if ($num == $goods['order_num']) {
                            $goods_list[] = $goods;
                        }
                    }
                }
                foreach ($goods_list as $kG=>$vG){
                    $discount_price = 0;
                    if(isset($vG['discount_detail']) && is_array($vG['discount_detail'])){
                        foreach ($vG['discount_detail'] as $vDiscount){
                            $discount_price = $discount_price??0;
                            $discount_price += $vDiscount['minus'];
                        }
                    }
                    if(isset($vG['coupon_list']) && is_array($vG['coupon_list'])){
                        foreach ($vG['coupon_list'] as $vCoupon){
                            $discount_price = $discount_price??0;
                            $discount_price += $vCoupon['minus'];
                        }
                    }
                    $goods_list[$kG]['discount_price'] = $discount_price;
                }
            }
            $goodsDetail['goods_list'] = $goods_list;
        }

        // 快速点餐，自取不显示第几次下单
        if ($returnArr['order']['order_from'] == 5 || $returnArr['order']['is_self_take'] == 1) {
            $returnArr['order']['show_order_num'] = 0;
        }

        if ($goodsDetail) {
            $returnArr['order']['goods_detail'] = $goodsDetail['goods_list'];

            $returnArr['order']['goods_num'] = $goodsDetail['num'] ?? 0; // 已点菜品总数

            $returnArr['order']['go_pay_money'] = $goodsDetail['go_pay_money'] ?? 0; // 商品待支付总价

            $returnArr['order']['go_pay_num'] = $goodsDetail['go_pay_num'] ?? 0; // 商品待支付数量

            $returnArr['order']['goods_total_price'] = $goodsDetail['total_price'] ?? 0; // 商品总价

            //消费一元获得积分
            $buy_can_get_score = (new GoodsScoreService())->getGoodsScore($returnArr['order']['go_pay_money'],'dining');
            $returnArr['order']['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
            $returnArr['order']['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
        }

        //套餐核销信息
        $verificResult = (new DiningOrderDetailService())->checktVerific($orderId);
        if (empty($verificResult)) {
            $returnArr['verificStatus'] = false;
            $returnArr['verificMoney'] = 0;
            $returnArr['verificPackageList'] = [];
        } else {
            $returnArr['verificStatus'] = $verificResult['verificStatus'];
            $returnArr['verificMoney'] = $verificResult['verificMoney'];
            $returnArr['verificPackageList'] = $verificResult['verificPackageList'];
            $returnArr['order']['goods_total_price'] = $goodsDetail['total_price']; // 商品总价
            $returnArr['order']['discount_price'] = $goodsDetail['discount_price'] ?? 0; // 优惠总价
        }


        return $returnArr;
    }


    /**
     * 获得简单的订单详情
     * @param $orderId int 订单id
     * @param $user int 用户
     * @return array
     */
    public function getSimpleOrderDetail($orderId, $user = [])
    {
        $returnArr = [];
        try {
            $returnArr = $this->getOrderByOrderId($orderId);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage(), 1003);
        }

        // 餐饮店铺详情
        $foodshopStore = (new MerchantStoreFoodshopService())->getStoreByStoreId($returnArr['store_id']);

        $returnArr['goods_detail'] = [];
        // 订单商品详情
        $goodsDetail = (new DiningOrderDetailService())->getOrderDetailByOrderId($orderId);

        if ($goodsDetail) {
            $returnArr['goods_detail'] = $goodsDetail;
        }
        return $returnArr;
    }

    /**
     * 获取订单和商品详情
     * @param $orderId int 订单id
     * @param $user int 用户
     * @return array
     */
    public function getPrintOrderDetail($orderId, $payOrderId = 0, $orderNum = 0)
    {

        $returnArr = $this->getWapOrderDetail($orderId, []);
        if ($payOrderId) {
            $payOrder = (new DiningOrderPayService())->getOrderDetail($payOrderId);
            $returnArr['order']['pay_price'] = $payOrder['pay_price'];
            $returnArr['order']['discount_price'] = $payOrder['discount_price']; //优惠总额
            // 打印单次支付商品
            $goodsDetail = (new DiningOrderDetailService())->getGoodsPrintDetailByPayOrderId($payOrderId);

            if($returnArr['store']['print_type'] == 1){// 分单打印
                $goodsDetailSingle = (new DiningOrderDetailService())->getFormartGoodsDetailPrint($returnArr['order'],$goodsDetail['goods_list']);
            }
        } elseif ($orderNum) {
            // 打印单次提交商品
            $goodsDetail = (new DiningOrderDetailService())->getGoodsPrintDetailByOrderNum($orderId, $orderNum);
            if($returnArr['store']['print_type'] == 1){// 分单打印
                $goodsDetailSingle = (new DiningOrderDetailService())->getFormartGoodsDetailPrint($returnArr['order'],$goodsDetail['goods_list']);
            }
        } else {
            // 打印所有商品
            $goodsDetail = (new DiningOrderDetailService())->getFormartGoodsDetailByOrderId($returnArr['order']);
        }

        if ($goodsDetail) {

            $returnArr['order']['goods_num'] = $goodsDetail['goods_count']; // 已点菜品总数
            $returnArr['order']['order_num'] = $goodsDetail['order_num'] ?? 0; // 下单次数

            $returnArr['order']['go_pay_money'] = $goodsDetail['go_pay_money']; // 商品待支付总价

            $returnArr['order']['goods_total_price'] = $goodsDetail['goods_total_price']; // 商品总价
            $returnArr['order']['total_price'] = $goodsDetail['old_total_price']??$goodsDetail['goods_total_price']; // 商品原总价

            $returnArr['order']['goods_detail'] =  $goodsDetail['goods_detail'] ?? [];

            if($returnArr['store']['print_type'] == 1){// 分单打印
                if(isset($goodsDetailSingle) && $goodsDetailSingle){
                    $returnArr['order']['goods_list'] =  $goodsDetailSingle['goods_list'];
                }else{
                    $returnArr['order']['goods_list'] =  $goodsDetail['goods_list'];
                    $returnArr['order']['goods_detail'] =  $goodsDetail['goods_detail_old'];
                }
                $returnArr['order']['print_type'] =  1;
            }
        }
        return $returnArr;
    }

    /**
     * 获得订单的用户手机号
     * @param $orderId int 订单id
     * @param $user int 用户
     * @return array
     */
    public function getOrderPhone($order)
    {
        // 手机号
        $phone = $order['phone'];
        if (!$order['phone'] && $order['uid']) {
            // 获取用户手机号
            $nowUser = (new UserService())->getUser($order['uid']);
            $phone = $nowUser['phone'];
        }
        return $phone;
    }

    /**
     * 获得前端所需字段详情
     * @param $orderId int 订单id
     * @param $user int 用户
     * @return array
     */
    public function getWapOrderDetail($orderId, $user = [])
    {

        if (!$orderId) {
            throw new \think\Exception(L_("参数错误"), 1003);
        }

        // 订单详情
        $order = $this->getOrderByOrderId($orderId);
        if (!$order) {
            throw new \think\Exception(L_("订单不存在"), 1003);
        }

        // 下单用户
        $orderUser = (new UserService())->getUser($order['user_id'], $order['user_type']);

        // 餐饮店铺详情
        $foodshopStore = (new MerchantStoreFoodshopService())->getStoreByStoreId($order['store_id']);


        // 店铺详情
        $store = (new MerchantStoreService())->getStoreInfo($order['store_id']);

        $returnArr = [];
        // 店铺详情
        $returnArr['store']['store_id'] = $store['store_id'];
        $returnArr['store']['mer_id'] = $store['mer_id'];
        $returnArr['store']['name'] = $store['name'];
        $returnArr['store']['logo'] = thumb($store['image_old'], '112', '112', 'fill');
        $returnArr['store']['province_id'] = $store['province_id'];
        $returnArr['store']['city_id'] = $store['city_id'];
        $returnArr['store']['area_id'] = $store['area_id'];
        $returnArr['store']['circle_id'] = $store['circle_id'];
        $returnArr['store']['adress'] = $store['adress'];
        $returnArr['store']['phone'] = $store['phone'];

        // 提前取消时间单位分钟
        $returnArr['store']['cancel_time'] = isset($foodshopStore['cancel_time']) ? $foodshopStore['cancel_time'] : 0;

        // 打印类型0-整合打印1-分单打印
        $returnArr['store']['print_type'] = isset($foodshopStore['print_type']) ? $foodshopStore['print_type'] : 0;

        // 订单详情
        $returnArr['order']['order_id'] = $order['order_id'];
        $returnArr['order']['store_id'] = $order['store_id'];
        $returnArr['order']['create_time_str'] = date('Y-m-d H:i:s', $order['create_time']);
        $returnArr['order']['create_time'] = $order['create_time'];
        $returnArr['order']['book_num'] = $order['book_num'];
        $returnArr['order']['username'] = $order['name'] ? $order['name'] : ($orderUser['nickname'] ?? '');
        $returnArr['order']['phone'] = $order['phone'] ? $order['phone'] : '';
        $returnArr['order']['user_phone'] = $orderUser && $orderUser['phone'] ?: '';
        $returnArr['order']['avatar'] = $order['avatar'];
        $returnArr['order']['real_orderid'] = $order['real_orderid'];
        $returnArr['order']['pay_price'] = $order['pay_price'];
        $returnArr['order']['status'] = $order['status'];
        $returnArr['order']['note'] = $order['note'];
        $returnArr['order']['fetch_number_show'] = '0'; //是否显示取餐号
        $returnArr['order']['fetch_number'] = $order['fetch_number']; //取餐号
        $returnArr['order']['uid'] = $order['uid']; //用户uid
        $returnArr['order']['user_type'] = $order['user_type']; //用户类型
        $returnArr['order']['user_id'] = $order['user_id']; //用户id
        $returnArr['order']['order_from'] = $order['order_from']; //订单来源
        $returnArr['order']['goods_note'] = $order['goods_note']; //每次提交订单备注
        $returnArr['order']['is_temp'] = $order['is_temp']; //临时订单
        $returnArr['order']['cancel_reason'] = $order['cancel_reason']; //取消原因
        $returnArr['order']['settle_accounts_type'] = $order['settle_accounts_type']; //先吃后父先付后吃
        $returnArr['order']['is_self_take'] = $order['is_self_take']; //是否自取=
        $returnArr['order']['self_take_time'] = date('m-d H:i', $order['self_take_time']) . '~' . date('H:i', $order['self_take_time'] + 20 * 60); //取餐时间
        $returnArr['order']['cancel_time'] = date('Y-m-d H:i:s', $order['cancel_time']); //取消时间
        $returnArr['order']['cancel_reason'] = $order['cancel_reason']; //取消原因
        $returnArr['order']['book_price'] = $order['book_price']; //定金金额
        $returnArr['order']['is_book_pay'] = $order['is_book_pay']; //定金是否已支付
        if ($order['is_self_take'] == 1) {
            $returnArr['order']['fetch_number_show'] = '1'; //是否显示取餐号
        }

        $order['book_time'] && $returnArr['order']['book_time_str'] = date('Y-m-d H:i', (int)$order['book_time']); // 预定时间
        $returnArr['order']['book_price'] = $order['book_price'];
        $order['pay_time'] && $returnArr['order']['pay_time'] = date('Y-m-d H:i:s', (int)$order['pay_time']); //支付时间
        $returnArr['order']['total_price'] = $order['total_price'];
        $returnArr['order']['refund_goods_price'] = $order['refund_goods_price']; //退款金额
        $returnArr['order']['go_pay_money'] = $order['price'];
        // 优惠总价
        $returnArr['order']['discount_price'] = get_format_number($order['merchant_reduce'] + $order['balance_reduce'] + $order['merchant_discount_money'] + $order['card_price'] + $order['coupon_price'] + $order['sort_discount']);

        $returnArr['order']['goods_detail'] = [];

        // 判断是否显示第几次下单 预订单，快速点餐，自取不显示第几次下单
        $returnArr['order']['show_order_num'] = 1;

        // 订单商品详情
        if (in_array($order['status'], [1, 4, 51, 60]) && in_array($order['order_from'], [0, 3])) {
            // 预订单显示购物车详情
            $returnArr['order']['show_order_num'] = 0; //不显示第几次下单
        }

        // 详情页按钮显示情况
        $returnArr['button'] = [
            'cancel_btn' => '0', //取消按钮1-显示0不显示
            'add_btn' => '0', //底部加菜按钮1-显示0不显示
            'pay_btn' => '0', //底部买单按钮1-显示0不显示
            'scan_btn' => '0', //扫码按钮1-显示0不显示
            'book_pay_btn' => '0', //订金待支付
            'choice_goods_btn' => '0', //提前选菜按钮1-显示0不显示
            'show_user' => '0', //显示头部下单人信息
        ];


        //用户进入订单代表加入订单
        if ($user) {
            // 添加日志
            $this->diningOrderLogService->addOrderLog($orderId, '3', '访问订单详情接口', $user);
        }

        // 订单状态
        // 订单状态 0-订单生成，1-预定单，2-预订单待支付，3-自取订单待支付，4-已接单，20-已确认，21-点餐中,30-已支付，40-已完成，41-已评价，50-部分退款，51-全部退款，60-取消
        // 1. 订金待支付状态， 如果没有订金则直接跳过此状态 。
        // 2. 待落座状态。
        // 3. 就餐中状态。
        // 4. 已完成状态。
        // 5. 已取消状态
        $status_str = '订单生成';
        if ($order['status'] == 1) {
            if (($order['book_price'] > 0 && !$order['is_book_pay']) || $order['status'] == 2) {
                $status_str = L_('订金待支付');
                $returnArr['button']['book_pay_btn'] = '1'; //订金待支付

                // 查询待支付订单
                $whereOrder = [
                    'paid' => 0,
                    'order_type' => 0,
                ];
                $payOrder = (new DiningOrderPayService())->getOne($whereOrder);
                $order['pay_order_id'] = $payOrder['pay_order_id'];
                $order['pay_type'] = 'dining';
            } else {
                if ($user && isset($user['user_type']) && $user['user_type'] != $order['user_type'] || (isset($user['user_id']) && $user['user_id'] != $order['user_id'])) {
                    //不是自己的订单 不能扫码落座/取消
                    $returnArr['button']['show_user'] = '1'; //显示头部下单人信息

                } else { //提前选桌
                    if ($order['order_from'] == 0) {
                        $returnArr['button']['cancel_btn'] = '1'; //取消
                    }
                    if (isset($foodshopStore['take_seat_by_scan']) && $foodshopStore['take_seat_by_scan']) {
                        $returnArr['button']['scan_btn'] = '1'; //扫码落座
                    }
                }
                $returnArr['button']['choice_goods_btn'] = '1'; //提前选菜
                $status_str = L_('未接单');
            }
        } elseif ($order['status'] == 4) {
            $status_str = L_('待落座');
            if ($user && isset($user['user_type']) && $user['user_type'] != $order['user_type'] || ($user && $user['user_id'] != $order['user_id'])) {
                //不是自己的订单 不能扫码落座/取消
                $returnArr['button']['show_user'] = '1'; //显示头部下单人信息

            } else {
                if ($order['order_from'] == 0) {
                    $returnArr['button']['cancel_btn'] = '1'; //取消
                }
                if (isset($foodshopStore['take_seat_by_scan']) && $foodshopStore['take_seat_by_scan']) {
                    $returnArr['button']['scan_btn'] = '1'; //扫码落座
                }
            }
            $returnArr['button']['choice_goods_btn'] = '1'; //提前选菜
        } elseif ($order['status'] >= 20 && $order['status'] < 40) {
            $status_str = L_('就餐中');
            if ($order['status'] == 21) {
                $status_str = L_('点餐中');
            }
            $returnArr['button']['add_btn'] = '1'; //底部加菜按钮
            $returnArr['button']['pay_btn'] = '1'; //底部买单按钮
        } elseif ($order['status'] >= 40 && $order['status'] < 60) {
            $status_str = L_('已完成');
        } elseif ($order['status'] ==  3) { //尾款待支付
            $status_str = L_('待支付');
            // 查询待支付订单
            $whereOrder = [
                'paid' => 0,
                'order_type' => 1,
            ];
            $payOrder = (new DiningOrderPayService())->getOne($whereOrder);
            $order['pay_order_id'] = $payOrder['pay_order_id'];
            $order['pay_type'] = 'dining';
        } else {
            $status_str = L_('已取消');
        }
        $returnArr['order']['status_str'] = $status_str;

        //是否显示下单人
        $returnArr['order']['book_user_show'] = 0;
        if ($user && in_array($order['status'], [1, 4]) && (isset($user['user_type']) && $order['user_type'] != $order['user_type'] || $order['user_id'] != $order['user_id'])) {
            $returnArr['order']['book_user_show'] = 1; //是否显示下单人
        }

        // 是否预订单1是2-否
        $returnArr['order']['is_book'] = 0;
        if (in_array($order['order_from'], [0, 3]) && $order['status'] == 1) {
            $returnArr['order']['is_book'] = 1;
        }

        // 桌台信息
        $returnArr['table_info'] = [];
        if ($order['table_type']) {
            $tableType = (new \app\foodshop\model\service\store\FoodshopTableTypeService())->geTableTypeById($order['table_type']);
            if ($tableType) {
                $returnArr['table_info']['table_type_name'] = $tableType['name'];
                $returnArr['table_info']['tid'] = $tableType['id'];
            }
        }

        if ($order['table_id']) {
            $table = (new \app\foodshop\model\service\store\FoodshopTableService())->geTableById($order['table_id']);
            if ($table) {
                $returnArr['table_info']['table_name'] = $table['name'];
                $returnArr['table_info']['id'] = $table['id'];
            }
        }

        return $returnArr;
    }

    /**
     * 去结算（锁定商品）
     * @param $orderId 
     * @param $user array 用户
     * @return array
     */
    public function goCash($orderId, $user)
    {
        if (!$orderId) {
            throw new \think\Exception(L_("参数错误"), 1001);
        }

        $order = $this->getOrderByOrderId($orderId);
        if (!$order) {
            throw new \think\Exception(L_("订单不存在"), 1003);
        }

        $storeId = $order['store_id'];

        // 店铺信息
        $store = (new MerchantStoreService())->getStoreByStoreId($storeId);
        if ($store['have_meal'] == 0 || $store['status'] != 1) {
            throw new \think\Exception(L_("商家已经关闭了该业务,不能下单了!"), 1003);
        }

        // 餐饮店铺
        $foodshopStore = (new MerchantStoreFoodshopService())->getStoreByStoreId($storeId);
        if (!$foodshopStore['open_online_pay'] && $order['settle_accounts_type'] == 1) {
            //先吃后付关闭在线支付
            throw new \think\Exception(L_("商家暂未开启在线支付功能，如需买单请到柜台买单哦！"), 1003);
        }

        // 验证当前是否有待支付商品
        try {
            $result = (new DiningOrderDetailService())->getNoPayGoods($orderId, $user);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage(), $e->getCode());
        }

        // 锁定商品
        try {
            (new DiningOrderDetailService())->lockGoods($orderId, $user);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage(), $e->getCode());
        }
        return true;
    }

    /**
     * 解锁商品
     * @param $orderId 
     * @param $user array 用户
     * @return array
     */
    public function deblocking($orderId, $user = [], $orderNum = 0, $staffUser = [])
    {
        if (!$orderId) {
            throw new \think\Exception(L_("参数错误"), 1001);
        }

        $order = $this->getOrderByOrderId($orderId);
        if (!$order) {
            throw new \think\Exception(L_("订单不存在"), 1003);
        }

        // 解锁商品
        try {
            (new DiningOrderDetailService())->deblockingGoods($orderId, $user, $orderNum, $staffUser);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage(), $e->getCode());
        }
        return true;
    }

    /**
     * 获得结算页详情
     * @param $param 
     * @param $user array 用户
     * @return array
     */
    public function cashDetail($param, $user)
    {

        // 订单id
        $orderId = isset($param['orderId']) ? $param['orderId'] : 0;
        // 临时订单id
        $tempId = isset($param['tempId']) ? $param['tempId'] : 0;
        // 是否自取1-是0-否
        $isSelfTake = isset($param['isSelfTake']) ? $param['isSelfTake'] : 0;
        // 自取时间
        $selfTakeTime = isset($param['selfTakeTime']) ? $param['selfTakeTime'] : 0;
        // 商家优惠券id
        $merchantCouponId = isset($param['merchantCouponId']) ? $param['merchantCouponId'] : 0;
        // 平台优惠券id
        $systemCouponId = isset($param['systemCouponId']) ? $param['systemCouponId'] : 0;
        // 是否使用平台优惠券
        $useSysCoupon = isset($param['useSysCoupon']) ? $param['useSysCoupon'] : 0;
        // 是否使用商家优惠券
        $useMerCoupon = isset($param['useMerCoupon']) ? $param['useMerCoupon'] : 0;
        if ($tempId) {
            $order = (new DiningOrderTemporaryService())->getOrderByTempId($tempId);
            if (!$order) {
                throw new \think\Exception(L_("订单不存在"), 1003);
            }
        } else {

            $order = $this->getOrderByOrderId($orderId);
            if (!$order) {
                throw new \think\Exception(L_("订单不存在"), 1003);
            }
        }

        $returnArr = [];
        $result = $this->checkCart($param, $user);


        // $returnArr = $result;
        // dd($result['goods']);
        // 返回数组
        $returnArr['goods_list'] = $result['goods'];
        $store =  $result['store'];
        //        var_dump($store);


        $returnArr['store_id'] = $result['store']['store_id'];
        $returnArr['mer_id'] = $result['store']['mer_id'];
        $returnArr['name'] = $result['store']['name'];
        // $returnArr['images'] = $result['store']['images'];
        $returnArr['cn_name'] = $result['store']['name'];
        $returnArr['order_time'] = date('H:i d/m/y');
        $returnArr['userphone'] = $result['userphone'];

        // 取餐方式 堂食就餐方式 1-堂食 2-自取  3-堂食或自取

        $returnArr['show_dining_type'] = 0;
        $returnArr['dining_type'] = 1; //堂食就餐方式 1-堂食 2-自取  3-堂食或自取 默认堂食
        $returnArr['pick_time_list'] = [];

        //查询以往支付订单
        $whereOrder = [
            'order_id' => $orderId,
            'order_type' => 1,
            'paid' => 1,
        ];
        $payCount = (new DiningOrderPayService())->getOne($whereOrder);
        $is_self_take = $order['is_self_take'] ?? 0;
        if (in_array($order['order_from'], ['0', '3', '4']) && $store['settle_accounts_type'] == 2 && in_array($store['dining_type'], [2, 3]) && (!isset($order['table_id']) || !$order['table_id']) && (!$payCount && !$is_self_take)) {

            //扫通用码才有自取方法方式
            $returnArr['show_dining_type'] = 1;
            $returnArr['dining_type'] = $store['dining_type'];
            $dateListNew = [];
            // 获得自提时间
            $dateList = (new MerchantStoreService())->getSelectTime($result, time(), 1, 1200);
            foreach ($dateList as $key => $rowset) {
                $temp = array();
                asort($rowset);
                foreach ($rowset as $rv) {
                    $temp[] = $rv;
                }
                if (!empty($temp)) {
                    if ($key == date('Y-m-d')) {
                        $dateListNew[] = array('ymd' => $key, 'show_date' => L_('今天'), 'date_list' => $temp);
                    } else if ($key == date('Y-m-d', time() + 86400)) {
                        $dateListNew[] = array('ymd' => $key, 'show_date' => L_('明天'), 'date_list' => $temp);
                    } else if ($key == date('Y-m-d', time() + 86400 * 2)) {
                        $dateListNew[] = array('ymd' => $key, 'show_date' => L_('后天'), 'date_list' => $temp);
                    } else {
                        $dateListNew[] = array('ymd' => $key, 'show_date' => date('m月d日', strtotime($key)), 'date_list' => $temp);
                    }
                }
            }

            if (empty($dateListNew)) {
                //                throw new \think\Exception(L_("当前暂无可自取时间"), 1003);
            }
            $returnArr['pick_time_list'] = $dateListNew ? array_slice($dateListNew, 0, 7) : array();
        }

        // 桌台名称
        $returnArr['table_info'] = [];
        if (isset($order['table_id']) && $order['table_id']) {
            $table = (new FoodshopTableService())->geTableById($order['table_id']);
            $returnArr['table_info']['table_id'] = $table['id'];
            $returnArr['table_info']['table_name'] = $table['name'];
        }

        // 桌台类型
        if (isset($order['table_type']) && $order['table_type']) {
            $tableType = (new FoodshopTableTypeService())->geTableTypeById($order['table_type']);
            $returnArr['table_info']['table_type_id'] = $tableType['id'];
            $returnArr['table_info']['table_type_name'] = $tableType['name'];
        }


        // 订单金额
        $returnArr['price'] = get_format_number($result['price']);

        //订单总金额
        $returnArr['total_price'] = get_format_number($result['total_price']);

        $returnArr['old_total_price'] = get_format_number($result['goods_old_total_price']);

        // 应付金额 = 订单金额-平台优惠-商家优惠
        $returnArr['pay_price'] =  $returnArr['price'] -  $result['sys_first_reduce'] - $result['sys_full_reduce'] - $result['sto_first_reduce'] - $result['sto_full_reduce'];
        $result['can_discounts_price'] =  $result['can_discounts_price'] -  $result['sys_first_reduce'] - $result['sys_full_reduce'] - $result['sto_first_reduce'] - $result['sto_full_reduce'];
        
        // 优惠金额
        $returnArr['discount_price'] = get_format_number($returnArr['old_total_price'] - $returnArr['pay_price']);
        // 满减优惠信息
        $returnArr['discount_list'] =  (new ShopDiscountService())->discountFormart($result['discount_list']);

        if($result['sort_discount_price'] > 0){
            $returnArr['discount_list'][] = [
                "type" => "plat_discount",
                "time_select" => 1,
                "value" => "分类折扣",
                "minus" => get_format_number($result['sort_discount_price'])
            ];
        }

 
        // 会员卡折扣金额
        $returnArr['merchant_discount_money'] = '0';
        $returnArr['merchant_discount'] = '0';
        $returnArr['has_merchant_discount'] = '0'; 
        // $cardDiscount = (new CardNewService())->getCardDiscountMoney($returnArr['pay_price'], $result['mer_id'], $user['user_id']);
        $cardDiscount = (new CardNewService())->getCardDiscountMoney($result['can_discounts_price'], $result['mer_id'], $user['user_id']); //可用优惠的商品价格
        if ($cardDiscount['card_discount_money'] > 0) {
            $returnArr['merchant_discount_money'] = $cardDiscount['card_discount_money'];
            $returnArr['merchant_discount'] = $cardDiscount['card_discount'];
            $returnArr['has_merchant_discount'] = '1';
            $returnArr['pay_price'] -= $returnArr['merchant_discount_money'];
            $result['can_discounts_price'] -= $returnArr['merchant_discount_money'];
            $returnArr['pay_price'] = get_format_number($returnArr['pay_price']);
            $returnArr['discount_price'] += $cardDiscount['card_discount_money'];
        }

        if ($returnArr['pay_price'] < 0) {
            $returnArr['pay_price'] = 0;
        }

        // 优惠券
        $returnArr['system_coupon'] = (object)[];
        $returnArr['mer_coupon'] = (object)[];
        // $canCouponMoney = $returnArr['pay_price'];
        $canCouponMoney = $result['can_discounts_price']; //可用优惠的商品价格
        if ($user['user_type'] == 'uid') { //用户登录的情况下才有优惠券
            $card_info = (new CardNewService())->getCardByUidAndMerId($result['mer_id'], $user['user_id']);
            // $tmpOrder['can_coupon_money'] = $returnArr['pay_price'];
            $tmpOrder['can_coupon_money'] = $result['can_discounts_price']; //可用优惠的商品价格
            $tmpOrder['mer_id'] = $returnArr['mer_id'];
            $tmpOrder['store_id'] = $returnArr['store_id'];
            $tmpOrder['business'] = 'meal';
            $tmpOrder['platform'] = 'wap';

            //商家优惠券
            $tmpCoupon = [];
            $merchantCouponService = new \app\common\model\service\coupon\MerchantCouponService();

            if (!empty($merchantCouponId) && isset($useMerCoupon) && $useMerCoupon) {
                $tmpCoupon = $merchantCouponService->getCouponByHadpullId($merchantCouponId);
            } else {
                // 会员卡于优惠不同享
                if ($cardDiscount['card_discount_money'] > 0) {
                    $tmpOrder['merchant_card'] = true;
                }
                $cardCouponList = $merchantCouponService->getAvailableCoupon($user['user_id'], $returnArr['mer_id'], $tmpOrder);
                if($result['can_discounts_price'] == 0){
                    $cardCouponList = [];
                }
                //                var_dump($cardCouponList);
                $use_mer_coupon = request()->param('use_mer_coupon');
                if ($use_mer_coupon && $cardCouponList) {
                    // 初次默认使用优惠券
                    $tmpCoupon = $cardCouponList[0];
                }
                //                var_dump($tmpCoupon);
            }
            if (!empty($tmpCoupon)) {
                $merCoupon['had_id'] = $tmpCoupon['id'];
                $merCoupon['order_money'] = get_format_number($tmpCoupon['order_money']); //优惠条件
                $merCoupon['discount'] = get_format_number($tmpCoupon['discount']); //优惠金额
                $merCoupon['discount_desc'] = $merchantCouponService->formatDiscount([$tmpCoupon], true)[0]['discount_des']; //描述

                // 优惠后金额
                // $tmpOrder['can_coupon_money'] -= empty($merCoupon['discount']) ? 0 : $merCoupon['discount'];
                $returnArr['pay_price'] -= empty($merCoupon['discount']) ? 0 : $merCoupon['discount'];

                $returnArr['discount_price'] += $merCoupon['discount'];
            } else {
                $merCoupon = [];
            }

            // //平台优惠券
            $tmpCoupon = array();
            $systemCouponService = new \app\common\model\service\coupon\SystemCouponService();
            if (!empty($systemCouponId) && isset($useSysCoupon) && $useSysCoupon) { //选择了优惠券
                $tmpCoupon = $systemCouponService->getCouponByHadpullId($systemCouponId);

                // 获得优惠金额
                if ($tmpCoupon) {
                    $discountMoney = $systemCouponService->computeDiscount($tmpCoupon['coupon_id'], $tmpOrder['can_coupon_money']);
                    $tmpCoupon['discount_money'] = $discountMoney['order_money_discount'];
                }
            } else {
                $systemCouponList = $systemCouponService->getAvailableCoupon($user['user_id'], $tmpOrder);
                $use_sys_coupon = request()->param('use_sys_coupon');
                if ($use_sys_coupon && $systemCouponList) {
                    // 初次默认使用优惠券
                    $tmpCoupon = $systemCouponList ? $systemCouponList[0] : [];
                }
            }

            if ($tmpCoupon) {
                $systemCoupon['had_id'] = $tmpCoupon['id'];
                $systemCoupon['order_money'] = get_format_number($tmpCoupon['order_money']); //优惠条件
                $systemCoupon['discount'] = get_format_number($tmpCoupon['discount_money']); //优惠金额
                $systemCoupon['is_discount'] = $tmpCoupon['is_discount']; //是否折扣
                $systemCoupon['discount_value'] = $tmpCoupon['discount_value']; //折扣值
                $systemCoupon['discount_type'] = $tmpCoupon['discount_type']; //减免类型（目前仅支持外卖业务）：0不限，1减免运费，2减免餐费
                $systemCoupon['discount_desc'] = $systemCouponService->formatDiscount([$tmpCoupon], true)[0]['discount_des']; //描述

                // 优惠后金额
                // $tmpOrder['can_coupon_money'] -= $systemCoupon['discount'];
                $returnArr['pay_price'] -= $systemCoupon['discount'];
                $returnArr['discount_price'] += $systemCoupon['discount'];
            } else {
                $systemCoupon = [];
            }


            // $returnArr['pay_price'] = $tmpOrder['can_coupon_money'];
            $returnArr['system_coupon'] = $systemCoupon;
            $returnArr['mer_coupon'] = $merCoupon;
        }
        $returnArr['discount_price'] = get_format_number($returnArr['discount_price']);

        // 定金抵扣
        $returnArr['book_money'] = '0';
        $returnArr['has_book_pay'] = '0';
        if ($result['order_num'] == '1') {
            $bookMoney = (new DiningOrderPayService())->getBookMoney($orderId);
            if ($bookMoney) {
                $bookMoney = min($bookMoney, $returnArr['pay_price']);
                $returnArr['has_book_pay'] = 1;
                $returnArr['book_money'] = $bookMoney;
                $returnArr['pay_price'] -= $bookMoney;
            }
        }

        $returnArr['pay_price'] = get_format_number($returnArr['pay_price']);
        if ($returnArr['pay_price'] < 0) {
            $returnArr['pay_price'] = 0;
        }


        // 保存临时支付订单
        $tempOrderData = [
            'user_id' => $user['user_id'],
            'user_type' => $user['user_type'],
            'mer_id' => $returnArr['mer_id'],
            'store_id' => $returnArr['store_id'],
            'order_id' => $orderId,
            'temp_id' => $tempId,
            'total_money' => $canCouponMoney,
        ];
        $tempOrderDataId = (new DiningOrderPayTempService())->add($tempOrderData);
        $returnArr['temp_id'] = $tempOrderDataId;

        return $returnArr;
    }

    //校验用户下单信息
    public function checkCart($param, $user)
    {
        // 订单id
        $orderId = isset($param['orderId']) ? $param['orderId'] : 0;
        // 临时订单id
        $tempId = isset($param['tempId']) ? $param['tempId'] : 0;
        // 商家优惠券id
        $merchantCouponId = isset($param['merchantCouponId']) ? $param['merchantCouponId'] : 0;
        // 平台优惠券id
        $systemCouponId = isset($param['systemCouponId']) ? $param['systemCouponId'] : 0;
        // 是否使用平台优惠券
        $useSysCoupon = isset($param['useSysCoupon']) ? $param['useSysCoupon'] : 0;
        // 是否使用商家优惠券
        $systemCouponId = isset($param['useMerCoupon']) ? $param['useMerCoupon'] : 0;

        // 商品信息
        $goodsDetail = isset($param['goodsDetail']) ? $param['goodsDetail'] : [];

        // 店铺id
        $storeId = isset($param['storeId']) ? $param['storeId'] : 0;

        if (!$goodsDetail) {
            if ($tempId) {
                $order = (new DiningOrderTemporaryService())->getOrderByTempId($tempId);
                if (!$order) {
                    throw new \think\Exception(L_("订单不存在"), 1003);
                }
                // 获得商品信息
                $goodsDetail = (new DiningOrderDetailTemporaryService())->getGoPayGoods($tempId, $user);
            } else {

                $order = $this->getOrderByOrderId($orderId);
                if (!$order) {
                    throw new \think\Exception(L_("订单不存在"), 1003);
                }

                // 获得商品信息
                $goodsDetail = (new DiningOrderDetailService())->getGoPayGoods($orderId, $user);
            }

            // 店铺ID
            $storeId = $order['store_id'];
        }

        // 店铺信息
        $store = (new MerchantStoreService())->getStoreByStoreId($storeId);

        if ($store['have_meal'] == 0 || $store['status'] != 1) {
            throw new \think\Exception(L_("商家已经关闭了该业务,不能下单了!"), 1003);
        }

        $foodshopStore = (new MerchantStoreFoodshopService())->getStoreByStoreId($storeId);
        if (empty($store) || empty($foodshopStore)) {
            throw new \think\Exception(L_("店铺信息有误"), 1003);
        }

        $store = array_merge($store, $foodshopStore);


        // 商家信息
        $merchant = (new MerchantService())->getMerchantByMerId($store['mer_id']);


        // 订单总价
        $totalPrice = $goodsDetail['goods_total_price'];

        // 待支付金额
        $price = $goodsDetail['go_pay_money'];
        
        //参与优惠的金额
        $can_discounts_price = $goodsDetail['can_discounts_price'];

        //参加满减优惠的金额  
        $canDiscountMoney = round($can_discounts_price, 2);

        $isDiscount = 0;

        // 满减service
        $shopDiscountService = new ShopDiscountService();

        // 获得所有满减优惠
        $discounts = $shopDiscountService->getDiscounts($store['mer_id'], $storeId, '', 0, 1);
        // 查询用户在该店铺下单数
        $where = [
            'user_type' => $user['user_type'],
            'user_id' => $user['user_id'],
            'store_id' => $storeId,
        ];
        $storeOrderCount = $this->getOrderCountByCondition($where);

        //查询用户在平台下单数
        $where = [
            ['user_type', '=', $user['user_type']],
            ['user_id', '=', $user['user_id']],
            ['status', 'not in', '0,5']
        ];
        $systemOrderCount = $this->getOrderCountByCondition($where);

        // 获得符合条件的满减优惠
        $discountResult = $shopDiscountService->getDiscountList($discounts, $canDiscountMoney, $storeId,  $user['user_id'], $storeOrderCount, $systemOrderCount, 0);

        $data = [];
        // $data['total'] = $total;
        $data['price'] = $price; //商品实际总价
        // $data['total_price'] = $totalPrice; //商品原价总价
        $data['total_price'] = $goodsDetail['goods_old_total_price'] ?? $totalPrice; //商品原价总价
        $data['discount_price'] = $canDiscountMoney; //折扣后的总价
        $data['goods'] = $goodsDetail['goods_list'];
        $data['order_num'] = $goodsDetail['order_num'];
        $data['store_id'] = $storeId;
        $data['mer_id'] = $store['mer_id'];
        $data['store'] = $store;
        $data['merchant'] = $merchant;
        $data['discount_list'] = $discountResult['discountList'];
        $data['sys_first_reduce'] = $discountResult['systemFirstReduce']; //平台新单优惠的金额
        $data['sys_full_reduce'] = $discountResult['systemFullReduce']; //平台满减优惠的金额
        $data['sto_first_reduce'] = $discountResult['storeFirstReduce']; //店铺新单优惠的金额
        $data['sto_full_reduce'] = $discountResult['storeFullReduce']; //店铺满减优惠的金额
        $data['platform_merchant'] = $discountResult['platformMerchant']; //平台优惠中商家补贴的总和统计
        $data['platform_plat'] = $discountResult['platformPlat']; //平台优惠中平台补贴的总和统计
        $data['can_discount_money'] = $price; //可用商家优惠券的总价
        $data['vip_discount_money'] = $canDiscountMoney; //VIP折扣后的总价
        $data['userphone'] = isset($user['phone']) && $user['phone'] ? $user['phone'] : '';
        $data['goods_old_total_price'] = $goodsDetail['goods_old_total_price'];
        $data['sort_discount_price'] = $goodsDetail['goods_old_total_price'] - $price;
        $data['can_discounts_price'] = $goodsDetail['can_discounts_price'];
        
        return $data;
    }

    /**
     * 结算页提交订单
     * @param $param 
     * @param $user array 用户
     * @return array
     */
    public function cash($param, $user)
    {
        // 订单id
        $orderId = isset($param['orderId']) ? $param['orderId'] : 0;
        // 临时订单id
        $tempId = isset($param['tempId']) ? $param['tempId'] : 0;
        // 是否自取1-是0-否
        $isSelfTake = isset($param['isSelfTake']) ? $param['isSelfTake'] : 0;
        // 自取时间
        $selfTakeTime = isset($param['selfTakeTime']) ? $param['selfTakeTime'] : 0;
        // 商家优惠券id
        $merchantCouponId = isset($param['merchantCouponId']) ? $param['merchantCouponId'] : 0;
        // 平台优惠券id
        $systemCouponId = isset($param['systemCouponId']) ? $param['systemCouponId'] : 0;
        // 是否使用平台优惠券
        $useSysCoupon = isset($param['useSysCoupon']) ? $param['useSysCoupon'] : 0;
        // 是否使用商家优惠券
        $useMerCoupon = isset($param['useMerCoupon']) ? $param['useMerCoupon'] : 0;

        if ($tempId) {
            $order = (new DiningOrderTemporaryService())->getOrderByTempId($tempId);
            if (!$order) {
                throw new \think\Exception(L_("订单不存在"), 1003);
            }
        } else {

            $order = $this->getOrderByOrderId($orderId);
            if (!$order) {
                throw new \think\Exception(L_("订单不存在"), 1003);
            }
        }

        $returnArr = [];
        $result = $this->checkCart($param, $user);


        $store =  $result['store'];

        // 商品信息
        $goods = $result['goods'];

        // 备注
        $note = isset($_POST['note']) ? htmlspecialchars($_POST['note']) : '';

        // $user_info = D('User')->where(array('uid' => $this->_uid))->find();

        // 更新主订单信息
        $foodshopData = [];
        if ($store['dining_type'] == 1) { //堂食
            $foodshopData['is_self_take'] = 0;
        } elseif ($store['dining_type'] == 2 && $store['settle_accounts_type'] == 3) { //自取
            if (in_array($order['order_from'], ['0', '3', '4']) && (!isset($order['table_id']) || !$order['table_id'])) { //扫通用码
                //扫通用码才有自取方法方式
                $foodshopData['is_self_take'] = 1;
                if (!$selfTakeTime) {
                    throw new \think\Exception(L_("请选择自取时间"), 1003);
                }
                if (strtotime($selfTakeTime) < time()) {
                    throw new \think\Exception(L_("自取时间不能小于当前时间"), 1003);
                }
            } else {
                $foodshopData['is_self_take'] = 0;
            }
        } else { //堂食或自取
            // 更新主订单信息
            if ($isSelfTake == 1 && !$selfTakeTime) {
                throw new \think\Exception(L_("请选择自取时间"), 1003);
            }
            if ($isSelfTake == 1 && strtotime($selfTakeTime) < time()) {
                throw new \think\Exception(L_("自取时间不能小于当前时间"), 1003);
            }
            $foodshopData['is_self_take'] = $isSelfTake;
            $foodshopData['self_take_time'] = strtotime($selfTakeTime);
        }

        // 状态改为待支付
        if ($foodshopData['is_self_take']) {
            $foodshopData['status'] = '3'; //菜品待支付
        }

        //主订单如果没记录到uid这里同步下
        if (empty($order['uid']) && $user['user_id']) {
            $foodshopData['uid'] = $user['user_id'];
        }

        $nowTime = time();

        // 支付订单信息
        $payOrderParam = [
            'order_id' => $orderId,
            'order_name' => L_('餐饮订单'),
            'user_type' => $user['user_type'],
            'user_id' => $user['user_id'],
            'store_id' => $store['store_id'],
            'mer_id' => $store['mer_id'],
            'wx_cheap' => 0,
            'order_type' => '1', //尾款
            'add_time' => $nowTime,
        ];
        if ($user['uid']) {
            $orderid = build_real_orderid($user['uid']); //real_orderid
        } else {
            $id = mt_rand(1000, 9999);
            $orderid = build_real_orderid($id); //real_orderid
        }

        $payOrderParam['total_price'] = $result['total_price'];
        $payOrderParam['orderid'] = $orderid;
        $payOrderParam['last_time'] = $nowTime;
        $payOrderParam['merchant_reduce'] = $result['sto_first_reduce'] + $result['sto_full_reduce']; //店铺优惠
        $payOrderParam['balance_reduce'] = $result['sys_first_reduce'] + $result['sys_full_reduce']; //平台优惠
        $payOrderParam['platform_merchant'] = $result['platform_merchant']; //平台优惠中商家补贴的总和统计
        $payOrderParam['platform_plat'] = $result['platform_plat']; //平台优惠中平台补贴的总和统计
        $payOrderParam['discount_detail'] = $result['discount_list'] ? serialize($result['discount_list']) : ''; //优惠详情
        $payOrderParam['sort_discount'] = $result['sort_discount_price'];//分类折扣

        // 订单总价 优惠后价格
        $price = $result['price'] - $payOrderParam['merchant_reduce'] - $payOrderParam['balance_reduce']; //实际要支付的价格
        $result['can_discounts_price'] =  $result['can_discounts_price'] -  $payOrderParam['merchant_reduce'] - $payOrderParam['balance_reduce']; //实际要支付的价格
        //可用商家优惠券的总价
        $payOrderParam['can_discount_money'] = $price;

        if ($user['user_type'] == 'uid' && $user['user_id']) {
            $payOrderParam['merchant_coupon_id'] = 0;
            $payOrderParam['merchant_coupon_price'] = 0;
            $payOrderParam['systemCoupon_id'] = 0;
            $payOrderParam['systemCoupon_price'] = 0;

            // 会员卡折扣金额
            $payOrderParam['merchant_discount_money'] = '0';
            $payOrderParam['merchant_discount'] = '0';
            // $cardDiscount = (new CardNewService())->getCardDiscountMoney($price, $store['mer_id'], $user['user_id']);
            $cardDiscount = (new CardNewService())->getCardDiscountMoney($result['can_discounts_price'], $result['mer_id'], $user['user_id']); //可用优惠的商品价格
            if ($cardDiscount['card_discount_money'] > 0) {
                $payOrderParam['merchant_discount_money'] = $cardDiscount['card_discount_money'];
                $payOrderParam['merchant_discount'] = $cardDiscount['card_discount'];

                // 抵扣金额
                $price -= $payOrderParam['merchant_discount_money'];
                $result['can_discounts_price'] -= $payOrderParam['merchant_discount_money'];

                //可用商家优惠券的总价
                $payOrderParam['can_discount_money'] = $price;
            }

            // 可用商家优惠券的总价
            $tmpOrder['can_coupon_money'] = $payOrderParam['can_discount_money'];

            $cardInfo = (new CardNewService())->getCardByUidAndMerId($result['mer_id'], $user['user_id']);
            // 商家优惠券
            if ($useMerCoupon) {
                if (!empty($merchantCouponId)) {
                    $tmpCoupon = (new MerchantCouponService())->getCouponByHadpullId($merchantCouponId);
                    if ($tmpCoupon['discount'] > $tmpOrder['can_coupon_money']) {
                        $tmpCoupon['discount'] = $tmpOrder['can_coupon_money'];
                    }

                    if (!empty($tmpCoupon)) {
                        $payOrderParam['merchant_coupon_id'] = $tmpCoupon['id'];
                        $payOrderParam['merchant_coupon_price'] = max(0, $tmpCoupon['discount']);
                        $tmpOrder['can_coupon_money'] -= empty($tmpCoupon['discount']) ? 0 : $tmpCoupon['discount'];
                    }
                    // 抵扣金额
                    $price -= $payOrderParam['merchant_coupon_price'];
                }
            }

            //平台优惠券
            if ($useSysCoupon) {
                $tmpCoupon = [];
                $payOrderParam['system_coupon_price'] = 0;
                if (!empty($systemCouponId) && $tmpOrder['can_coupon_money'] > 0) {
                    $tmpCoupon = (new SystemCouponService())->getCouponByHadpullId($systemCouponId);
                }
                if ($tmpCoupon) {
                    $payOrderParam['system_coupon_id'] = $systemCouponId;
                    // 获得优惠金额
                    $discountMoney = (new SystemCouponService())->computeDiscount($tmpCoupon['coupon_id'], $tmpOrder['can_coupon_money']);
                    if ($discountMoney['order_money_discount'] > $tmpOrder['can_coupon_money']) {
                        $discountMoney['order_money_discount'] = $tmpOrder['can_coupon_money'];
                    }
                    $payOrderParam['system_coupon_price'] = $discountMoney['order_money_discount'];

                    $tmpOrder['can_coupon_money'] -= empty($payOrderParam['system_coupon_price']) ? 0 : $payOrderParam['system_coupon_price'];
                }
                // 抵扣金额
                $price -= $payOrderParam['system_coupon_price'];
            }

            $tmpOrder['can_coupon_money'] = $tmpOrder['can_coupon_money'] < 0 ? 0 : $tmpOrder['can_coupon_money'];
        }

        // 定金抵扣
        if ($result['order_num'] == '1') {
            $bookMoney = (new DiningOrderPayService())->getBookMoney($orderId);
            if ($bookMoney) {
                $payOrderParam['book_money'] = $bookMoney <= $price ? $bookMoney : $price;
                $price -= $payOrderParam['book_money'];
                $price = get_format_number($price);
            }
        }

        $price = get_format_number($price);
        // 金额不能小于0
        $price = max(0, $price);

        // 需要支付金额
        $payOrderParam['price'] = $price;

        if ($tempId) {
            // 临时订单生成正式订单
            $orderId = (new DiningOrderTemporaryService())->moveOrder($tempId, '20', '1');
            $payOrderParam['order_id'] = $orderId;
        }

        // 添加支付订单
        $payOrderId = (new DiningOrderPayService())->addPayOrder($payOrderParam);

        // 更新主订单信息
        $result = $this->updateByOrderId($orderId, $foodshopData);

        // 更新商品信息
        $data = [
            'third_id' => $payOrderId,
        ];
        if (empty($order['uid']) && $user['user_id']) {
            $data['uid'] = $user['user_id'];
            $data['user_type'] = $order['user_type'] ?? 'uid';
            $where = [
                ['order_id', '=', $orderId],
                ['status', 'in', '0,1,2'],
                ['third_id', '=', '0'],
            ];
        }else{
            $where = [
                ['order_id', '=', $orderId],
                ['user_type', '=', $user['user_type']],
                ['user_id', '=', $user['user_id']],
                ['status', 'in', '0,1,2'],
                ['third_id', '=', '0'],
            ];
        }
        
        (new DiningOrderDetailService())->updateByCondition($where, $data);

        // 添加日志
        $this->diningOrderLogService->addOrderLog($orderId, '17', L_('用户去结算'), $user);

        $returnArr['order_id'] = $payOrderId;
        $returnArr['type'] = 'dining';
        return $returnArr;
    }

    /**
     * 支付成功取餐详情
     * @param $param 
     * @param $user array 用户
     * @return array
     */
    public function takeMealsDetail($orderId)
    {
        if (!$orderId) {
            throw new \think\Exception(L_("参数错误"), 1001);
        }

        $order = $this->getOrderByOrderId($orderId);
        if (!$order) {
            throw new \think\Exception(L_("订单不存在"), 1003);
        }


        //异步通知延迟导致获取不到取餐号，这里临时循环10次
        if ($order['is_self_take'] == 1) {
            if (empty($order['fetch_number'])) {
                for ($i = 0; $i < 10; $i++) {
                    sleep(1);
                    $order = $this->getOrderByOrderId($orderId);
                    if ($order['fetch_number']) {
                        break;
                    }
                }
            }
        }

        // 店铺信息
        $store = (new MerchantStoreService())->getStoreInfo($order['store_id']);

        $returnArr = [];
        $returnArr['fetch_number_show'] = '0'; //是否显示取餐号
        $returnArr['fetch_number'] = $order['fetch_number']; //取餐号
        if ($order['is_self_take'] == 1) {
            $returnArr['fetch_number_show'] = '1'; //是否显示取餐号
        }
        $returnArr['store_name'] = $store['name']; //店铺名称
        $returnArr['store_id'] = $store['store_id']; //店铺id
        $returnArr['pay_time'] = date('Y-m-d H:i:s', $order['pay_time']); //支付时间
        $returnArr['create_time_str'] = date('Y-m-d H:i:s', $order['create_time']);; //下单时间

        $image = thumb_img($store['image_old'], '74', '74', 'fill');
        $returnArr['image'] = $image; //店铺图片
        $imageInfo = getimagesize($image);
        $returnArr['image_base64'] = 'data:' . $imageInfo['mime'] . ';base64,' . chunk_split(base64_encode(file_get_contents($image)));

        return $returnArr;
    }

    /**
     * 获得支付订单信息（供支付调用）
     * @param $param 
     * @param $payParam array 支付后的支付数据
     * @return array 
     */
    public function afterPay($orderId, $payParam)
    {
        fdump('diningOrder', 'diningOrder', 1);
        $nowOrder = $this->getOrderByOrderId($orderId);
        fdump_sql([$nowOrder, $payParam], 'diningOrder', 1);

        if ($nowOrder['status'] >= 40) {
            throw new \think\Exception(L_("订单已完成"), 1003);
        }

        $saveData = [];
        if ($nowOrder['status'] == 2) { //预定金支付成功
            $nowOrder['status'] = 1;

            // 推送app消息
            (new MerchantStoreStaffService())->sendMsgFoodShop($nowOrder);

            // 更新订单信息
            $saveData = [
                'book_pay_time' => time(),
                'status' => 1,
                'is_book_pay' => 1
            ];
        } else {
            $saveData = [
                'status' => 30,
                'pay_type' => $payParam['pay_type']
            ];
            $saveData['pay_time'] = $_SERVER['REQUEST_TIME'];
            $saveData['payment_method'] = $payParam['pay_type'] ? $payParam['pay_type'] : '';

            $nowOrder['status'] = 30;
        }

        // 优惠信息处理
        $saveData['coupon_id'] = trim($nowOrder['coupon_id'] . ',' . $payParam['system_coupon_id'], ',');
        $saveData['coupon_price'] = get_format_number($nowOrder['coupon_price'] + $payParam['system_coupon_price']); //平台优惠券金额
        $saveData['card_id'] = trim($nowOrder['card_id'] . ',' . $payParam['merchant_coupon_id'], ',');
        $saveData['card_price'] =  get_format_number($nowOrder['card_price'] + $payParam['merchant_coupon_price']); //商家优惠券金额
        $saveData['merchant_reduce'] =  get_format_number($nowOrder['merchant_reduce'] + $payParam['merchant_reduce']); //商家优惠的金额
        $saveData['balance_reduce'] =  get_format_number($nowOrder['balance_reduce'] + $payParam['balance_reduce']); //平台优惠的金额
        $saveData['platform_merchant'] =  get_format_number($nowOrder['platform_merchant'] + $payParam['platform_merchant']); //平台优惠中商家补贴的总和统计
        $saveData['platform_plat'] =  get_format_number($nowOrder['platform_plat'] + $payParam['platform_plat']); //平台优惠中平台补贴的总和统计
        $saveData['merchant_discount_money'] =  get_format_number($nowOrder['merchant_discount_money'] + $payParam['merchant_discount_money']); //商家会员卡折扣金额
        $saveData['internal_card_pay_money'] =  get_format_number($nowOrder['internal_card_pay_money'] + $payParam['internal_card_pay_money']); //一卡通支付金额

        $saveData['merchant_balance_pay'] =  get_format_number($nowOrder['merchant_balance_pay'] + $payParam['merchant_balance_pay']); //商家余额支付金额
        $saveData['merchant_balance_give'] =  get_format_number($nowOrder['merchant_balance_give'] + $payParam['merchant_balance_give']); //商家赠送余额支付金额
        $saveData['pay_money'] =  get_format_number($nowOrder['pay_money'] + $payParam['pay_money']); //在线支付金额
        $saveData['system_balance'] =  get_format_number($nowOrder['system_balance'] + $payParam['system_balance']); //平台余额支付金额
        $saveData['system_score'] =  get_format_number($nowOrder['system_score'] + $payParam['system_score']); //积分抵扣数
        $saveData['system_score_money'] =  get_format_number($nowOrder['system_score_money'] + $payParam['system_score_money']); //积分抵扣金额
        $saveData['qiye_pay'] =  get_format_number($nowOrder['qiye_pay'] + $payParam['qiye_pay']); //企业预存款余额支付金额
        $saveData['pay_price'] =  get_format_number($nowOrder['pay_price'] + $payParam['price']); //支付的总额
        $saveData['price'] =  get_format_number($nowOrder['price'] + $payParam['price']); //支付的总额

        // 店铺信息
        $nowStore = (new MerchantStoreService())->getStoreByStoreId($nowOrder['store_id']);

        // 临时订单改成正式
        $saveData['is_temp'] = 0;

        // 保存订单信息
        fdump_sql([$orderId, $saveData], 'diningOrder', 1);
        if (!$this->updateByOrderId($orderId, $saveData)) {
            throw new \think\Exception(L_("订单保存失败"), 1003);
        }

        // 临时订单 将购物车商品写入订单详情表
        if ($nowOrder['is_temp'] == 1) {
            (new DiningOrderTempService())->saveCart($nowOrder, 3, [], $payParam['pay_order_id']);
        }

        // 本次支付的商品
        $where = [
            ['third_id', '=', $payParam['pay_order_id']],
        ];
        $goodsList = (new DiningOrderDetailService())->getOrderDetailByCondition($where);

        // 店员操作
        $staff = [];
        if ($payParam['staff_id']) {
            $staff = (new \app\merchant\model\service\storestaff\MerchantStoreStaffService())->getStaffById($payParam['staff_id']);
            $nowOrder['staff_name'] = $staff['name'];
        }

        if ($nowOrder['uid']) {
            $nowUser = (new UserService())->getUser($nowOrder['uid']);

            //增加粉丝 5 group 6 shop 7 meal 8 appoint 9 store
            (new MerchantService())->saveRelation($nowUser['openid'], $nowOrder['mer_id'], 7);
        }

       


        if ($nowOrder['is_temp'] == 1) { //临时订单
            //通知店员 打印菜品
            $this->callStaff($nowOrder, $goodsList, $payParam['pay_order_id']);

            $this->completeOrder($orderId);
        } elseif ($nowOrder['settle_accounts_type'] == 2 && $nowOrder['status'] != 1) { //先付后吃通知上菜
            //通知店员 打印菜品
            $this->callStaff($nowOrder, $goodsList, $payParam['pay_order_id']);

            // 先付后吃 自取 直接完成订单
            if ($nowOrder['is_self_take'] == 1 && $nowOrder['status'] == 30) {
                $this->completeOrder($orderId);
                // 发送自取模板消息
                // 微信公众号openid
                $openid = '';
                // 获得用户的openID
                if ($nowOrder['uid']) {
                    $nowUser = (new UserService())->getUser($nowOrder['uid']);
                    $openid = $nowUser['openid'];
                } elseif ($nowOrder['user_type'] == 'openid') {
                    $openid = $nowOrder['openid'];
                }
                if ($openid && request()->agent == 'wechat_h5') {
                    // 通过微信公众号下单
                    $msgDataWx = [
                        'href' => cfg('site_url') . 'pages/foodshop/order/orderDetail?order_id=' . $nowOrder['order_id'],
                        'wecha_id' => $openid,
                        'first' => '您已下单成功，取餐时间为：' . date("Y-m-d H:i", $nowOrder['self_take_time']) . '~' . date("H:i", $nowOrder['self_take_time'] + 1200) . '，取餐号为：' . $nowOrder['fetch_number'] . '；为避免影响您的用餐口感，请准时取餐。', //门店名称
                        'keyword1' => L_('取餐号提醒'),
                        'keyword2' => L_('下单成功'),
                        'keyword3' => date("Y-m-d H:i"),
                        'remark' => L_('点击查看您在【X1】的餐饮订单', $nowStore['name']),

                    ];
                    $res = (new TemplateNewsService())->sendTempMsg('OPENTM400166399', $msgDataWx);
                }
            }
        } elseif ($nowOrder['status'] != 1) { //先吃后付通知支付成功
            //            if ($saveData['status'] == 30) {//通知店员支付成功
            //                $nowOrder['status'] = 30;
            //                (new MerchantStoreStaffService())->sendMsgFoodShop($nowOrder);
            //            }
        }


        // 修改菜品状态
        $saveGoodsData = [
            'status' => 3,
            'pay_time' => time()
        ];
        $where = [
            ['third_id', '=', $payParam['pay_order_id']],
        ];
        (new DiningOrderDetailService())->updateByCondition($where, $saveGoodsData);

        //小票打印
        $printHaddleService = new PrintHaddleService();
        if ($saveData['status'] == 30) {
            // 结账单 客看单
            $param['order_id'] = $nowOrder['order_id'];
            $param['old_status'] = $nowOrder['status'];
            $param['pay_order_id'] = $payParam['pay_order_id'];
            $param['type'] = ['bill_account']; //打印类型 结账单
            $param['staff_name'] = ($staff && isset($staff['name'])) ? $staff['name'] : ''; //店员名称
            $printHaddleService->printOrder($param);

            // 通知店员支付成功
            $nowOrder['status'] = 30;
            (new MerchantStoreStaffService())->sendMsgFoodShop($nowOrder);
        }

        // 首页滚动信息
        if($nowOrder['uid']){
            (new ScrollMsgService())->addMsg('foodshop', $nowOrder['uid'], L_('用户x1于x2购买x3成功', array('x1' => str_replace_name($payParam['nickname']), 'x2' => date('Y-m-d H:i', time()), 'x3' => cfg('meal_alias_name'))));
        }

        // 用户推广分佣
        $spreadInfo = [
            'order_id' => $nowOrder['order_id'],
            'pay_order_id' => $payParam['pay_order_id'],
            'mer_id' => $nowOrder['mer_id'],
            'store_id' => $nowOrder['store_id'],
            'uid' => $nowOrder['uid'],
            'system_balance' => $payParam['system_balance'],
            'pay_money' => $payParam['pay_money'],
            'system_score_money' => $payParam['system_score_money'],
            'system_coupon_price' => $payParam['system_coupon_price'],
            'merchant_balance_pay' => $payParam['merchant_balance_pay'],
            'order_type' => 'dining',
            'real_orderid' => $nowOrder['real_orderid'],
        ];
        if (cfg('add_spread_by_system_commission') != 1) {
            try {
                $res = (new UserSpreadListService())->addSpreadList($spreadInfo);
            } catch (\Exception $e) {
                fdump_sql($e->getMessage(), 'addSpreadList_error');
            }
        }
        
        return true;

        //商家推广分佣
        // D('Merchant_spread')->add_spread_list($nowOrder,$now_user,'meal',L_("X1用户购买X2商品获得佣金",array("X1" => $now_user['nickname'],"X2" => cfg('meal_alias_name'))));


        //短信提醒
        // $sms_data = array('mer_id' => $nowOrder['mer_id'], 'store_id' => $nowOrder['mer_id'], 'type' => 'food');
        // if (cfg('sms_success_order') == 1 || cfg('sms_success_order') == 3) {
        // 	if (empty($nowOrder['phone'])) {
        // 		$nowOrder['phone'] = isset($now_user['phone']) && $now_user['phone'] ? $now_user['phone'] : '';
        // 	}
        // 	$sms_data['uid'] = $nowOrder['uid'];
        // 	$sms_data['mobile'] = $nowOrder['phone'];
        // 	$sms_data['sendto'] = 'user';

        // 	if ($nowOrder['status'] == 1) {
        // 		$sms_data['content'] = L_('您在x1时，预订了x2的x3人，已成功生成订单，订单号：x4',array('x1'=>date('Y-m-d H:i:s'),'x2'=>$store['name'],'x3'=>$nowOrder['table_type_name'],'x4'=>$nowOrder['real_orderid']));
        // 	} else {
        // 		$sms_data['content'] = L_('您预订的x1人的订单(订单号：x2)已经完成支付。欢迎下次光临！',array('x1'=>$store['name'] . $nowOrder['table_type_name'] . $nowOrder['book_num'],'x2'=>$nowOrder['real_orderid']));
        // 	}
        // 	$sms_data['mobile'] && Sms::sendSms($sms_data);
        // }

        // if (cfg('sms_success_order') == 2 || cfg('sms_success_order') == 3) {
        // 	$sms_data['uid'] = 0;
        // 	$sms_data['mobile'] = $store['phone'];
        // 	$sms_data['sendto'] = 'merchant';

        // 	if ($nowOrder['status'] == 1) {
        // 		$sms_data['content'] = L_('顾客在x1时，预订了x2的x3人，已成功生成订单，订单号：x4',array('x1'=>date('Y-m-d H:i:s'),'x2'=>$store['name'],'x3'=>$nowOrder['table_type_name'] . $nowOrder['book_num'],'x4'=>$nowOrder['real_orderid']));
        // 	} else {
        // 		$sms_data['content'] = L_('顾客预订的x1的x2人的订单(订单号：x3),在x4时已经完成了支付！',array('x1'=>$store['name'],'x2'=>$nowOrder['table_type_name'] . $nowOrder['book_num'],'x3'=>$nowOrder['real_orderid'],'x4'=>date('Y-m-d H:i:s')));
        // 	}
        // 	$sms_data['mobile'] && Sms::sendSms($sms_data);
        // }

    }

    /**
     * 商家对账
     * @param $orderId 订单id
     * @return array 
     */
    public function completeOrder($orderId)
    {

        $nowOrder = $this->getOrderByOrderId($orderId);
        if (!$nowOrder) {
            throw new \think\Exception(L_("订单不存在"), 1003);
        }

        if ($nowOrder['status'] >= 40) {
            throw new \think\Exception(L_("订单已完成"), 1003);
        }

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');

        Db::startTrans();

        // 更新订单信息
        $data['status'] = 40;
        $data['is_temp'] = 0;
        $where = [
            'order_id' => $orderId
        ];
        $res = Db::table($prefix . 'dining_order')->where($where)->update($data);
        if (!$res) {
            // 回滚事务
            Db::rollback();
            throw new \think\Exception(L_("订单状态修改失败请重试"), 1003);
        }

        if ($nowOrder['table_id']) {
            // 查看原桌台下是否有就餐中订单，没有则置为空台
            $where = [];
            $where[] = ['table_id', '=', $nowOrder['table_id']];
            if (!$this->getDiningOrder($where)) {
                $where = [];
                $where['id'] = $nowOrder['table_id'];
                $tableData['status'] = 0;
                $res = Db::table($prefix . 'foodshop_table')->where($where)->update($tableData);
                if ($res === false) {
                    // 回滚事务
                    Db::rollback();
                    throw new \think\Exception(L_("桌台状态修改失败请重试"), 1003);
                }
                //                (new FoodshopTableService())->updateTable($tableData);
            }
        }

        // 获得支付订单列表
        $where = [
            'order_id' => $orderId,
            'paid' => 1
        ];
        $foodshopOrderPayService = new DiningOrderPayService();
        $payOrderList = $foodshopOrderPayService->getOrderListByCondition($where);


        $billMoney = 0;
        $moneySystemTake = 0;
        $totalPrice = 0;
        $orderInfo = [];
        $orderInfo['order_id'] = $nowOrder['order_id'];
        $orderInfo['real_orderid'] = $nowOrder['real_orderid'];
        $orderInfo['mer_id'] = $nowOrder['mer_id'];
        $orderInfo['store_id'] = $nowOrder['store_id']; //当前门店ID
        $orderInfo['uid'] = $nowOrder['uid']; //用户id
        $orderInfo['order_type'] = 'dining'; //业务代号
        $orderInfo['num'] = $nowOrder['goods_num'] - $nowOrder['refund_num'];
        $orderInfo['order_from'] = $nowOrder['order_from'];
        //        $orderInfo['score_discount_type'] = $nowOrder['score_discount_type'];
        $orderInfo['discount_detail'] = $nowOrder['discount_detail']; //满减优惠信息
        $orderInfo['score_discount_type'] = 0; //优惠通道 0 都行 1 优惠 2 积分 3 2选一

        $orderInfo['payment_money'] = '0'; //在线支付金额（不包含自有支付）
        $orderInfo['balance_pay'] = '0'; //平台余额支付金额
        $orderInfo['score_deducte'] = '0'; //平台积分抵扣金额
        $orderInfo['merchant_balance'] = '0'; //商家会员卡支付金额
        $orderInfo['card_give_money'] = '0'; //商家会员卡赠送支付金额
        $orderInfo['card_give_money'] = '0'; //商家会员卡赠送支付金额
        $orderInfo['system_coupon_plat_money'] = '0'; //平台优惠券平台抵扣金额
        $orderInfo['system_coupon_merchant_money'] = '0'; //平台优惠券商家抵扣金额
        $orderInfo['union_mer_id'] = '0'; //商家联盟id
        $orderInfo['union_merchant_balance'] = '0'; //商家联盟会员卡支付金额
        $orderInfo['pay_for_system'] = '0'; //外卖商城使用平台配送的配送费快递费
        $orderInfo['score_used_count'] = '0'; //积分使用数量

        $orderInfo['is_own'] = '0'; //自有支付类型
        $orderInfo['own_pay_money'] = '0'; //自有支付在线支付金额
        $orderInfo['pay_order_id'] = []; //支付单号id
        $orderInfo['pay_type'] = ''; //支付方式

        $payTypeArr = []; //支付方式
        //        var_dump($payOrderList);
        foreach ($payOrderList as $_order) {
            $orderInfo['pay_type'] = $_order['pay_type'];
            $orderInfo['pay_order_id'][] = $_order['third_id'];

            // 获得订已退款金额
            $feild = 'sum(price) as price, sum(system_score_money) as system_score_money, sum(system_score) as system_score, sum(merchant_balance_give) as merchant_balance_give, sum(merchant_balance_pay) as merchant_balance_pay, sum(system_balance) as system_balance, sum(payment_money) as pay_money, sum(book_money) as book_money';
            $refundMoneyList = (new DiningOrderRefundService())->getOne(['third_id' => $_order['pay_order_id']], $feild);
            if ($refundMoneyList) {
                $_order['system_balance'] -= $refundMoneyList['system_balance'];
                $_order['system_score_money'] -= $refundMoneyList['system_score_money'];
                $_order['system_score'] -= $refundMoneyList['system_score'];
                $_order['merchant_balance_pay'] -= $refundMoneyList['merchant_balance_pay'];
                $_order['merchant_balance_give'] -= $refundMoneyList['merchant_balance_give'];
                $_order['pay_money'] -= $refundMoneyList['pay_money'];
                $_order['price'] -= $refundMoneyList['price'];
            }

            $totalPrice += floatval($_order['price']);
            $payTypeArr[] = $_order['pay_type'];
            $payMoney = '0';
            if ($_order['is_own'] > 0) { //自有支付
                $orderInfo['is_own'] = $_order['is_own'];
                $orderInfo['own_pay_money'] += $_order['pay_money'];
            } else {
                $payMoney = $_order['pay_money'];
                $orderInfo['payment_money'] += $_order['pay_money'];
            }


            $orderInfo['balance_pay'] += $_order['system_balance'];
            $orderInfo['score_deducte'] += $_order['system_score_money'];
            $orderInfo['score_used_count'] += $_order['system_score'];
            $orderInfo['merchant_balance'] += $_order['merchant_balance_pay'];
            $orderInfo['card_give_money'] += $_order['merchant_balance_give'];

            $orderBillMoney = 0;
            // 商家联盟定制功能，会员卡余额对账到使用的联盟商家余额
            if ($_order['union_mer_id']) {
                $orderInfo['union_merchant_balance'] += $_order['merchant_balance_pay'];
                $orderInfo['union_mer_id'] = $_order['union_mer_id'];
                $orderBillMoney = $_order['system_balance'] + $payMoney + $_order['system_score_money'];
            } else {
                $orderBillMoney = $_order['system_balance'] + $payMoney + $_order['system_score_money'] + $orderInfo['merchant_balance'];
            }

            //1 平台出 不抽成   0 商家出 抽成 
            // 平台优惠不抽成 商家优惠抽成
            $platPayMoney = 0;
            $merchantPayMoney = 0;
            $platPayMoney = $_order['platform_plat'];
            $merchantPayMoney  = $_order['platform_merchant'] + $_order['merchant_coupon_price'] + $_order['merchant_discount_money'];

            // 平台抽成基数
            $moneySystemTake += $orderBillMoney + floatval($merchantPayMoney);

            // 平台优惠参与对账
            $orderBillMoney += $platPayMoney + $_order['system_coupon_price'];

            // 加上平台满减优惠金额
            $billMoney += $orderBillMoney;

            if ($_order['system_coupon_id'] > 0 && $_order['system_coupon_price'] > 0) {
                $systemCouponService = new \app\common\model\service\coupon\SystemCouponService();
                $useSystemCoupon = $systemCouponService->getCouponByHadpullId($_order['system_coupon_id'], false);

                if ($useSystemCoupon) {
                    if ($useSystemCoupon['plat_money'] == 0 && $useSystemCoupon['merchant_money'] == 0) {
                        $useSystemCoupon['plat_money'] = 1;
                    }
                }
                // 平台优惠券 平台出的金额
                $systemCouponPlatMoney = $_order['system_coupon_price'] * ($useSystemCoupon['plat_money'] / ($useSystemCoupon['plat_money'] + $useSystemCoupon['merchant_money']));
                $orderInfo['system_coupon_plat_money'] += $systemCouponPlatMoney;
                $orderInfo['system_coupon_merchant_money'] += $_order['system_coupon_price'] - $systemCouponPlatMoney;
            }
        }

        $orderInfo['desc'] = $orderInfo['union_mer_id'] ? L_('商家会员卡联盟----购买餐饮商品') : L_('购买餐饮商品');
        $orderInfo['total_money'] = $totalPrice;
        $orderInfo['money_system_take'] = $moneySystemTake; //平台抽成基数
        $orderInfo['bill_money'] = $billMoney; //当前商家应该入账的金额(在线支付，余额，商家余额，积分抵扣，平台优惠券抵扣金额，满减优惠平台部分)

        fdump($orderInfo, 'billMethod', 1);

        try {
            (new MerchantMoneyListService())->addMoney($orderInfo);
            // 增加商家余额
            $systemBillService = new \app\merchant\model\service\SystemBillService();
            //          $res = $systemBillService->billMethod($orderInfo['is_own'],$orderInfo);

            // 更新订单信息
            $data = [];
            $data['is_pay_bill'] = 1;
            //            $this->updateByOrderId($orderId, $data);

            $this->addSystemOrder($orderId,1);
            Db::commit();
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage(), $e->getCode());
            Db::rollback();
        }

        //微信派发优惠券 支付到平台 微信支付
        if ($orderInfo['uid'] && $orderInfo['is_own'] == 0 && in_array('weixin', $payTypeArr) && $orderInfo['payment_money'] >= cfg('weixin_send_money')) {
            // D('System_coupon')->weixin_send($orderInfo['payment_money'],$orderInfo['uid']);
        }

        // y用户增加加积分 TODO
        // 加积分,根据订单金额送积分
        if (cfg('add_score_by_system_commission') == 0 && cfg('add_score_by_percent') == 0 && cfg('user_score_get') > 0) {
            $score_round_two = cfg('score_round_two') ? 2 : 1; //积分保留几位小数
            if (cfg('open_score_get_percent')) { //百分比
                $score = bcdiv(bcmul($orderInfo['total_money'], cfg('user_score_get'), 0), 100, $score_round_two);
            } else { //积分
                $score = bcmul($orderInfo['total_money'], cfg('user_score_get'), $score_round_two);
            }
            $info = [
                'mer_id' => $orderInfo['mer_id'],
                'store_id' => $orderInfo['store_id'],
                'order_id' => $orderInfo['order_id'],
                'order_type' => 'dining',
            ];
            (new UserService())->addScore($orderInfo['uid'], $score, cfg('meal_alias_name') . '消费获得积分', 0, $info);
        }

        return true;
    }

    /**
     * 商家对账
     * @param $orderId 订单id
     * @return array
     */
    public function getMerchantBillData($nowOrder)
    {
        if ($nowOrder['status'] >= 40) {
            return [];
        }

        // 获得支付订单列表
        $where = [
            'order_id' => $orderId,
            'paid' => 1
        ];
        $foodshopOrderPayService = new DiningOrderPayService();
        $payOrderList = $foodshopOrderPayService->getOrderListByCondition($where);


        $billMoney = 0;
        $moneySystemTake = 0;
        $totalPrice = 0;
        $orderInfo = [];
        $orderInfo['order_id'] = $nowOrder['order_id'];
        $orderInfo['real_orderid'] = $nowOrder['real_orderid'];
        $orderInfo['mer_id'] = $nowOrder['mer_id'];
        $orderInfo['store_id'] = $nowOrder['store_id']; //当前门店ID
        $orderInfo['uid'] = $nowOrder['uid']; //用户id
        $orderInfo['order_type'] = 'dining'; //业务代号
        $orderInfo['num'] = $nowOrder['goods_num'] - $nowOrder['refund_num'];
        $orderInfo['order_from'] = $nowOrder['order_from'];
        //        $orderInfo['score_discount_type'] = $nowOrder['score_discount_type'];
        $orderInfo['discount_detail'] = $nowOrder['discount_detail']; //满减优惠信息
        $orderInfo['score_discount_type'] = 0; //优惠通道 0 都行 1 优惠 2 积分 3 2选一

        $orderInfo['payment_money'] = '0'; //在线支付金额（不包含自有支付）
        $orderInfo['balance_pay'] = '0'; //平台余额支付金额
        $orderInfo['score_deducte'] = '0'; //平台积分抵扣金额
        $orderInfo['merchant_balance'] = '0'; //商家会员卡支付金额
        $orderInfo['card_give_money'] = '0'; //商家会员卡赠送支付金额
        $orderInfo['card_give_money'] = '0'; //商家会员卡赠送支付金额
        $orderInfo['system_coupon_plat_money'] = '0'; //平台优惠券平台抵扣金额
        $orderInfo['system_coupon_merchant_money'] = '0'; //平台优惠券商家抵扣金额
        $orderInfo['union_mer_id'] = '0'; //商家联盟id
        $orderInfo['union_merchant_balance'] = '0'; //商家联盟会员卡支付金额
        $orderInfo['pay_for_system'] = '0'; //外卖商城使用平台配送的配送费快递费
        $orderInfo['score_used_count'] = '0'; //积分使用数量

        $orderInfo['is_own'] = '0'; //自有支付类型
        $orderInfo['own_pay_money'] = '0'; //自有支付在线支付金额
        $orderInfo['pay_order_id'] = []; //支付单号id
        $orderInfo['pay_type'] = ''; //支付方式

        $payTypeArr = []; //支付方式
        //        var_dump($payOrderList);
        foreach ($payOrderList as $_order) {
            $orderInfo['pay_type'] = $_order['pay_type'];
            $orderInfo['pay_order_id'][] = $_order['third_id'];

            // 获得订已退款金额
            $feild = 'sum(price) as price, sum(system_score_money) as system_score_money, sum(system_score) as system_score, sum(merchant_balance_give) as merchant_balance_give, sum(merchant_balance_pay) as merchant_balance_pay, sum(system_balance) as system_balance, sum(payment_money) as pay_money, sum(book_money) as book_money';
            $refundMoneyList = (new DiningOrderRefundService())->getOne(['third_id' => $_order['pay_order_id']], $feild);
            if ($refundMoneyList) {
                $_order['system_balance'] -= $refundMoneyList['system_balance'];
                $_order['system_score_money'] -= $refundMoneyList['system_score_money'];
                $_order['system_score'] -= $refundMoneyList['system_score'];
                $_order['merchant_balance_pay'] -= $refundMoneyList['merchant_balance_pay'];
                $_order['merchant_balance_give'] -= $refundMoneyList['merchant_balance_give'];
                $_order['pay_money'] -= $refundMoneyList['pay_money'];
                $_order['price'] -= $refundMoneyList['price'];
            }

            $totalPrice += floatval($_order['price']);
            $payTypeArr[] = $_order['pay_type'];
            $payMoney = '0';
            if ($_order['is_own'] > 0) { //自有支付
                $orderInfo['is_own'] = $_order['is_own'];
                $orderInfo['own_pay_money'] += $_order['pay_money'];
            } else {
                $payMoney = $_order['pay_money'];
                $orderInfo['payment_money'] += $_order['pay_money'];
            }


            $orderInfo['balance_pay'] += $_order['system_balance'];
            $orderInfo['score_deducte'] += $_order['system_score_money'];
            $orderInfo['score_used_count'] += $_order['system_score'];
            $orderInfo['merchant_balance'] += $_order['merchant_balance_pay'];
            $orderInfo['card_give_money'] += $_order['merchant_balance_give'];

            $orderBillMoney = 0;
            // 商家联盟定制功能，会员卡余额对账到使用的联盟商家余额
            if ($_order['union_mer_id']) {
                $orderInfo['union_merchant_balance'] += $_order['merchant_balance_pay'];
                $orderInfo['union_mer_id'] = $_order['union_mer_id'];
                $orderBillMoney = $_order['system_balance'] + $payMoney + $_order['system_score_money'];
            } else {
                $orderBillMoney = $_order['system_balance'] + $payMoney + $_order['system_score_money'] + $orderInfo['merchant_balance'];
            }

            //1 平台出 不抽成   0 商家出 抽成
            // 平台优惠不抽成 商家优惠抽成
            $platPayMoney = 0;
            $merchantPayMoney = 0;
            $platPayMoney = $_order['platform_plat'];
            $merchantPayMoney  = $_order['merchant_reduce'] + $_order['merchant_coupon_price'] + $_order['merchant_discount_money'];

            // 平台抽成基数
            $moneySystemTake += $orderBillMoney + floatval($merchantPayMoney);

            // 平台优惠参与对账
            $orderBillMoney += $platPayMoney + $_order['system_coupon_price'];

            // 加上平台满减优惠金额
            $billMoney += $orderBillMoney;

            if ($_order['system_coupon_id'] > 0 && $_order['system_coupon_price'] > 0) {
                $systemCouponService = new \app\common\model\service\coupon\SystemCouponService();
                $useSystemCoupon = $systemCouponService->getCouponByHadpullId($_order['system_coupon_id'], false);

                if ($useSystemCoupon) {
                    if ($useSystemCoupon['plat_money'] == 0 && $useSystemCoupon['merchant_money'] == 0) {
                        $useSystemCoupon['plat_money'] = 1;
                    }
                }
                // 平台优惠券 平台出的金额
                $systemCouponPlatMoney = $_order['system_coupon_price'] * ($useSystemCoupon['plat_money'] / ($useSystemCoupon['plat_money'] + $useSystemCoupon['merchant_money']));
                $orderInfo['system_coupon_plat_money'] += $systemCouponPlatMoney;
                $orderInfo['system_coupon_merchant_money'] += $_order['system_coupon_price'] - $systemCouponPlatMoney;
            }
        }

        $orderInfo['desc'] = $orderInfo['union_mer_id'] ? L_('商家会员卡联盟----购买餐饮商品') : L_('购买餐饮商品');
        $orderInfo['total_money'] = $totalPrice;
        $orderInfo['money_system_take'] = $moneySystemTake; //平台抽成基数
        $orderInfo['bill_money'] = $billMoney; //当前商家应该入账的金额(在线支付，余额，商家余额，积分抵扣，平台优惠券抵扣金额，满减优惠平台部分)


        return $orderInfo;
    }

    /**
     * 获得每个支付订单商家对账的金额
     * @param $orderId 订单id
     * @return array
     */
    public function getMerchantBillDataSingle($payOrder)
    {
        $billMoney = 0;
        $moneySystemTake = 0;
        $totalPrice = 0;
        $orderInfo = [];
        $orderInfo['order_id'] = $nowOrder['order_id'];
        $orderInfo['real_orderid'] = $nowOrder['real_orderid'];
        $orderInfo['mer_id'] = $nowOrder['mer_id'];
        $orderInfo['store_id'] = $nowOrder['store_id']; //当前门店ID
        $orderInfo['uid'] = $nowOrder['uid']; //用户id
        $orderInfo['order_type'] = 'dining'; //业务代号
        $orderInfo['num'] = $nowOrder['goods_num'] - $nowOrder['refund_num'];
        $orderInfo['order_from'] = $nowOrder['order_from'];
        //        $orderInfo['score_discount_type'] = $nowOrder['score_discount_type'];
        $orderInfo['discount_detail'] = $nowOrder['discount_detail']; //满减优惠信息
        $orderInfo['score_discount_type'] = 0; //优惠通道 0 都行 1 优惠 2 积分 3 2选一

        $orderInfo['payment_money'] = '0'; //在线支付金额（不包含自有支付）
        $orderInfo['balance_pay'] = '0'; //平台余额支付金额
        $orderInfo['score_deducte'] = '0'; //平台积分抵扣金额
        $orderInfo['merchant_balance'] = '0'; //商家会员卡支付金额
        $orderInfo['card_give_money'] = '0'; //商家会员卡赠送支付金额
        $orderInfo['card_give_money'] = '0'; //商家会员卡赠送支付金额
        $orderInfo['system_coupon_plat_money'] = '0'; //平台优惠券平台抵扣金额
        $orderInfo['system_coupon_merchant_money'] = '0'; //平台优惠券商家抵扣金额
        $orderInfo['union_mer_id'] = '0'; //商家联盟id
        $orderInfo['union_merchant_balance'] = '0'; //商家联盟会员卡支付金额
        $orderInfo['pay_for_system'] = '0'; //外卖商城使用平台配送的配送费快递费
        $orderInfo['score_used_count'] = '0'; //积分使用数量

        $orderInfo['is_own'] = '0'; //自有支付类型
        $orderInfo['own_pay_money'] = '0'; //自有支付在线支付金额
        $orderInfo['pay_order_id'] = []; //支付单号id
        $orderInfo['pay_type'] = ''; //支付方式

        $payTypeArr = []; //支付方式
        $orderInfo['pay_type'] = $payOrder['pay_type'];

        // 获得订已退款金额
        $feild = 'sum(price) as price, sum(system_score_money) as system_score_money, sum(system_score) as system_score, sum(merchant_balance_give) as merchant_balance_give, sum(merchant_balance_pay) as merchant_balance_pay, sum(system_balance) as system_balance, sum(payment_money) as pay_money, sum(book_money) as book_money';
        $refundMoneyList = (new DiningOrderRefundService())->getOne(['third_id' => $payOrder['pay_order_id']], $feild);
        if ($refundMoneyList) {
            $_order['system_balance'] -= $refundMoneyList['system_balance'];
            $_order['system_score_money'] -= $refundMoneyList['system_score_money'];
            $_order['system_score'] -= $refundMoneyList['system_score'];
            $_order['merchant_balance_pay'] -= $refundMoneyList['merchant_balance_pay'];
            $_order['merchant_balance_give'] -= $refundMoneyList['merchant_balance_give'];
            $_order['pay_money'] -= $refundMoneyList['pay_money'];
            $_order['price'] -= $refundMoneyList['price'];
        }

        $totalPrice += floatval($_order['price']);
        $payTypeArr[] = $_order['pay_type'];
        $payMoney = '0';
        if ($_order['is_own'] > 0) { //自有支付
            $orderInfo['is_own'] = $_order['is_own'];
            $orderInfo['own_pay_money'] += $_order['pay_money'];
        } else {
            $payMoney = $_order['pay_money'];
            $orderInfo['payment_money'] += $_order['pay_money'];
        }


        $orderInfo['balance_pay'] += $_order['system_balance'];
        $orderInfo['score_deducte'] += $_order['system_score_money'];
        $orderInfo['score_used_count'] += $_order['system_score'];
        $orderInfo['merchant_balance'] += $_order['merchant_balance_pay'];
        $orderInfo['card_give_money'] += $_order['merchant_balance_give'];

        $orderBillMoney = 0;
        // 商家联盟定制功能，会员卡余额对账到使用的联盟商家余额
        if ($_order['union_mer_id']) {
            $orderInfo['union_merchant_balance'] += $_order['merchant_balance_pay'];
            $orderInfo['union_mer_id'] = $_order['union_mer_id'];
            $orderBillMoney = $_order['system_balance'] + $payMoney + $_order['system_score_money'];
        } else {
            $orderBillMoney = $_order['system_balance'] + $payMoney + $_order['system_score_money'] + $orderInfo['merchant_balance'];
        }

        //1 平台出 不抽成   0 商家出 抽成
        // 平台优惠不抽成 商家优惠抽成
        $platPayMoney = 0;
        $merchantPayMoney = 0;
        $platPayMoney = $_order['platform_plat'];
        $merchantPayMoney  = $_order['merchant_reduce'] + $_order['merchant_coupon_price'] + $_order['merchant_discount_money'];

        // 平台抽成基数
        $moneySystemTake += $orderBillMoney + floatval($merchantPayMoney);

        // 平台优惠参与对账
        $orderBillMoney += $platPayMoney + $_order['system_coupon_price'];

        // 加上平台满减优惠金额
        $billMoney += $orderBillMoney;

        if ($_order['system_coupon_id'] > 0 && $_order['system_coupon_price'] > 0) {
            $systemCouponService = new \app\common\model\service\coupon\SystemCouponService();
            $useSystemCoupon = $systemCouponService->getCouponByHadpullId($_order['system_coupon_id'], false);

            if ($useSystemCoupon) {
                if ($useSystemCoupon['plat_money'] == 0 && $useSystemCoupon['merchant_money'] == 0) {
                    $useSystemCoupon['plat_money'] = 1;
                }
            }
            // 平台优惠券 平台出的金额
            $systemCouponPlatMoney = $_order['system_coupon_price'] * ($useSystemCoupon['plat_money'] / ($useSystemCoupon['plat_money'] + $useSystemCoupon['merchant_money']));
            $orderInfo['system_coupon_plat_money'] += $systemCouponPlatMoney;
            $orderInfo['system_coupon_merchant_money'] += $_order['system_coupon_price'] - $systemCouponPlatMoney;
        }


        $orderInfo['desc'] = $orderInfo['union_mer_id'] ? L_('商家会员卡联盟----购买餐饮商品') : L_('购买餐饮商品');
        $orderInfo['total_money'] = $totalPrice;
        $orderInfo['money_system_take'] = $moneySystemTake; //平台抽成基数
        $orderInfo['bill_money'] = $billMoney; //当前商家应该入账的金额(在线支付，余额，商家余额，积分抵扣，平台优惠券抵扣金额，满减优惠平台部分)


        return $orderInfo;
    }

    /**
     * 增加流水号
     * @param $param 
     * @param $user array 用户
     * @return array
     */
    public function setFetchNumber($nowOrder)
    {
        if (!$nowOrder) {
            return false;
        }

        if ($nowOrder['fetch_number']) {
            return false;
        }

        // 当前日期
        $nowDay = date('Ymd');

        // 默认1001
        $fetchNumber = '1001';

        // 查询当前最大的流水
        $where = [
            'store_id' => $nowOrder['store_id'],
            'is_temp' => 0
        ];
        $order['fetch_day'] = 'DESC';
        $order['fetch_number'] = 'DESC';
        $foodshop = $this->getOrderByCondition($where, $order);
        if ($foodshop) {
            if ($foodshop['fetch_day'] == $nowDay) {
                $fetchNumber = $foodshop['fetch_number'] + 1;
            } else {
                $fetchNumber = $fetchNumber;
            }
        }

        $data = [];
        $data['fetch_number'] = $fetchNumber;
        $data['fetch_day'] = $nowDay;
        $this->updateByOrderId($nowOrder['order_id'], $data);
        return true;
    }

    /**
     * 通知店员上菜
     * @param $param 
     * @param $user array 用户
     * @return array
     */
    public function callStaff($order, $goodsList, $payOrderId = 0, $orderNum = 0)
    {
        $goodsList = $goodsList ?: [];
        // 生成流水号
        $this->setFetchNumber($order);

        // pc端通知店员
        $data = [
            'running_call_status' => 1,
            'running_state' => 1,
            'note' => $order['note'],
            'running_time' => time()
        ];
        $this->updateByOrderId($order['order_id'], $data);

        //订单中套餐商品
        $packagesList = [];
        foreach ($goodsList as $key => $value) {
            if ($value['package_id'] > 0) {
                $packagesList[] = $value;
                unset($goodsList[$key]);
            }
        }

        // 组合套餐商品
        if (!empty($packagesList)) {
            $packagesList = (new ShopGoodsService())->combinationPackageData($packagesList, 'spec', 0);
        }


        // 组合附属商品
        $goodsList = (new ShopGoodsService())->combinationData($goodsList, 'spec', 0);
        foreach ($goodsList as &$_goods) {
            $_goods['spec'] = trim(str_replace($_goods['spec_sub'], '', $_goods['spec']));
            $_goods['total_price'] = get_format_number($_goods['price'] * $_goods['num']);
        }
        if (!empty($packagesList)) {
            $goodsList = array_merge($goodsList, $packagesList);
        }
        // 打印后厨单
        $printHaddleService = new PrintHaddleService();
        $param['order_id'] = $order['order_id'];
        $param['goods_list'] = $goodsList;
        $param['old_status'] = $order['status'];
        $param['staff_name'] = $order['staff_name'] ?? '';
        $param['pay_order_id'] = $payOrderId;
        $param['order_num'] = $orderNum;
        $param['type'] = ['customer_account', 'menu']; //打印类型 客看单 后厨单

        $printHaddleService->printOrder($param);

        // 店员app通知店员上菜
        $order['status'] = 2;
        (new MerchantStoreStaffService())->sendMsgFoodShop($order);

        return true;
    }

    /**
     * 将多个商品合并成一条记录
     * @param $param 
     * @param $user array 用户
     * @return array
     */
    public function combinationGoods($goodsList)
    {
        $returnGoodsArr = [];
        foreach ($goodsList as $_good) {
            $index = $_good['uniqueness_number'] . $_good['goods_id'] . $_good['spec'];
            if (isset($returnGoodsArr[$index])) {
                $returnGoodsArr[$index]['num'] += $_good['num'];
                isset($_good['total_price']) && $returnGoodsArr[$index]['total_price'] += $_good['total_price'];
            } else {
                $returnGoodsArr[$index] = $_good;
            }
        }
        return array_values($returnGoodsArr);
    }


    /**
     * 返回用户信息
     * @param $tableId int 桌台id
     * @return array
     */
    public function getFormatUser($user)
    {
        if (isset($user['uid']) && $user['uid']) {
            $user['user_type'] = 'uid';
            $user['user_id'] = $user['uid'];
        } elseif (isset($user['openid']) && $user['uid'] == 0 && $user['openid']) {
            $user['user_type'] = 'openid';
            $user['user_id'] = $user['openid'];
        } elseif (isset($user['alipay_uid']) && $user['uid'] == 0 && $user['alipay_uid']) {
            $user['user_type'] = 'alipay_uid';
            $user['user_id'] = $user['alipay_uid'];
        } elseif (isset($user['wxapp_openid']) && $user['uid'] == 0 && $user['wxapp_openid']) {
            $user['user_type'] = 'wxapp_openid';
            $user['user_id'] = $user['wxapp_openid'];
        } else {
            $user = [];
        }
        return $user;
    }

    /**
     * 获取订单列表
     * @param $param 
     * @param $systemUser 平台用户
     * @param $isAll 是否查询所有数据
     * @return array
     */
    public function getOrderListLimit($param, $systemUser = [],  $merchantUser = [], $user = [])
    {
        $page = isset($param['page']) ? $param['page'] : 0;
        $payType = $param['payType'] ?? '';
        $pageSize = isset($param['pageSize']) ? $param['pageSize'] : 0; //每页限制
        $keyword = isset($param['keyword']) ? $param['keyword'] : ''; //搜索关键词
        $keywords = isset($param['keywords']) ? $param['keywords'] : ''; //搜索关键词
        $searchtype = isset($param['searchtype']) ? $param['searchtype'] : ''; //搜索关键词类型
        $startTime = isset($param['start_time']) ? $param['start_time'] : ''; //开始时间
        $endTime = isset($param['end_time']) ? $param['end_time'] : ''; //结束时间
        $orderStatus = isset($param['order_status']) ? $param['order_status'] : ''; //订单状态
        $historyOrderStatus = isset($param['history_order_status']) ? $param['history_order_status'] : ''; //历史订单状态
        $provinceId = isset($param['province_id']) ? $param['province_id'] : ''; //省份
        $cityId = isset($param['city_id']) ? $param['city_id'] : ''; //城市
        $areaId = isset($param['area_id']) ? $param['area_id'] : ''; //区域
        $storeId = isset($param['store_id']) ? $param['store_id'] : ''; //店铺id
        $orderBy = isset($param['order_by']) ? $param['order_by'] : []; //排序
        $staff = isset($param['staff']) ? $param['staff'] : []; //店员信息
        $orderId = $param['order_id']??0;
        // 搜索条件
        $where = [];
        // 排序
        $order = $orderBy;
        $where[] = ['o.is_del', '=', 0];
        // 关键词搜索
        if (!empty($keyword)) {
            switch ($searchtype) {
                case 'real_orderid':
                    $where[] = ['o.real_orderid', 'like', '%' . $keyword . '%'];
                    break;
                case 'store_name':
                    $where[] = ['s.name', 'like', '%' . $keyword . '%'];
                    break;
                case 'merchant_name':
                    $where[] = ['m.name', 'like', '%' . $keyword . '%'];
                    break;
                case 'username':
                    $where[] = ['o.name', 'like', '%' . $keyword . '%'];
                    break;
                case 'phone':
                    $where[] = ['o.phone', 'like', '%' . $keyword . '%'];
                    break;
                case 'third_id': //第三方流水号
                    $payOrderList = (new DiningOrderPayService())->getOrderListByThirdId($keyword, 'op.order_id');
                    $payOrderIds = array_column($payOrderList, 'order_id');
                    if ($payOrderIds) {
                        $where[] = ['o.order_id', 'in', implode(',', $payOrderIds)];
                    } else {
                        $where[] = ['o.order_id', '=', null];
                    }
                    break;
                case 'table_num':
                    $where[] = ['o.table_id', '=',  $keyword];
                    break;
            }
        }

        if($orderId > 0){
            $where[] = ['o.order_id', '=', $orderId];
        }

        if($payType!='all'){
            switch ($payType) {
                case 'alipay':
                case 'wechat':
                    $where[] = ['pay_order.pay_type', '=', $payType];
                    break;
                case 'balance':
                    $where[] = ['pay_order.pay_type', 'not in', ['alipay','wechat']];
                    break;
            }
        }
        // 关键词搜索
        if (!empty($keywords)) {
            $keywords = addslashes($keywords);
            $where[] = ['o.name', 'exp', Db::raw('like "%' . $keywords . '%" OR o.phone like "%' . $keywords . '%"  OR o.order_id like "%' . $keywords . '%" OR o.real_orderid like "%' . $keywords . '%" OR tab.`name` like "%' . $keywords . '%" OR tab_type.`name` like "%' . $keywords . '%" OR u.`phone` like "%' . $keywords . '%"')];
        }

        // 省市区搜索
        if ($systemUser) { //系统后台才有省市区搜索
            if ($systemUser['area_id']) {
                $nowArea = (new AreaService())->getAreaByAreaId($systemUser['area_id']);
                switch ($nowArea['area_type']) {
                    case '3':
                        $areaIndex = 'area_id';
                        $areaId = $systemUser['area_id'];
                        $cityId = $nowArea['area_pid'];
                        $tempArea = (new AreaService())->getAreaByAreaId($cityId);
                        $provinceId = $nowArea['area_pid'];

                        break;
                    case '2':
                        $cityId = $systemUser['area_id'];
                        $provinceId = $nowArea['area_pid'];
                        break;
                    case '1':
                        $areaIndex = 'province_id';
                        $provinceId = $systemUser['area_id'];
                        break;
                }
            }
        }

        if ($merchantUser && $merchantUser['mer_id']) { //商家后台
            $where[] = ['o.mer_id', '=', $merchantUser['mer_id']];
        }

        if ($storeId > 0) { // 店铺id
            $where[] = ['o.store_id', '=', $storeId];
        }

        if (isset($param['table_id'])) { // 桌台id
            $where[] = ['o.table_id', '=', $param['table_id']];
        }

        if ($user) { // 用户订单
            if ($user['user_type'] == 'uid') {
                $whereUser[] = ['uid', '=', $user['user_id']];
            } else {
                $whereUser[] = ['user_type', '=', $user['user_type']];
                $whereUser[] = ['user_id', '=', $user['user_id']];
            }

            // 多人点餐
            $orderLog = $this->diningOrderLogService->getLogListByCondition($whereUser);
            if ($orderLog) {
                $orderIds = array_unique(array_column($orderLog, 'order_id'));
                $where[] = ['o.order_id', 'in', implode(',', $orderIds)];
            } else {
                $where[] = ['o.order_id', '=', null];
            }
        }
        if ($provinceId) {
            $where[] = ['s.province_id', '=', $provinceId];
        }
        if ($cityId) {
            $where[] = ['s.city_id', '=', $cityId];
        }
        if ($areaId) {
            $where[] = ['s.area_id', '=', $areaId];
        }
        // 时间
        if (!empty($startTime) && !empty($endTime)) {
            if ($startTime > $endTime) {
                throw new \think\Exception(L_('结束时间应大于开始时间'), 1003);
            }

            $endTime = $endTime . ' 23:59:59';
            $period = array(
                strtotime($startTime),
                strtotime($endTime)
            );
            $where[] = ['o.create_time', 'BETWEEN', $period];
        }

        switch ($orderStatus) {
            case '1': //待付款
                $where[] = ['o.status', 'in', '2,3'];
                break;
            case '2': //待落座
                $where[] = ['o.status', 'in', '1,4'];
                $where[] = ['o.order_from', 'in', '0,3'];
                break;
            case '3': //就餐中
                $where[] = ['o.status', '>=', '20'];
                $where[] = ['o.status', '<', '40'];
                break;
            case '4': //已完成
                $where[] = ['o.status', '>=', '40'];
                $where[] = ['o.status', '<', '50'];
                break;
            case '5': //已取消
                $where[] = ['o.status', '>', '50'];
                break;
        }

        // 历史订单筛选状态
        switch ($historyOrderStatus) {
            case '1': //进行中
                $where[] = ['o.status', '<', '40'];
                break;
            case '2': //已完成
                $where[] = ['o.status', '>=', '40'];
                $where[] = ['o.status', '<=', '50'];
                break;
            case '3': //已取消
                $where[] = ['o.status', '>', '50'];
                break;
        }
        $field = 'o.*,o.name as username,s.store_id,s.name as store_name,m.mer_id,m.name as merchant_name,u.nickname as card_name,u.phone as user_phone';
        // 订单列表
        $orderList = $this->getOrderListByJoin($where, $field, $order, $page, $pageSize,$payType);
        // 订单总数
        $count = $this->getOrderCountByJoin($where,$payType);

        // 统计订单金额
        $statisticsData = $this->getOrderSumByJoin($where,$payType,'sum(o.total_price - o.refund_goods_price) as total_price,sum(o.price) as price,sum(o.merchant_balance_pay+o.merchant_balance_give) as balance_merchant,sum(o.system_balance) as system_balance,sum(o.merchant_reduce+o.balance_reduce+o.merchant_discount_money+o.card_price+o.coupon_price+o.system_score_money) as discount_money,sum(o.pay_money+o.system_balance+o.merchant_balance_pay+o.merchant_balance_give+o.qiye_pay-o.refund_money) as online_money');

         // 统计订单金额
         $offlineMoney = $this->getOrderOfflineMoneyTotal($where,'sum(order_pay.offline_money) as offline_money');
        $statisticsData['offline_money'] = $offlineMoney['offline_money'] ?? 0;

        $uidArr = array_column($orderList, 'uid');
        $whereCard = [
            ['uid', 'in', implode(',', $uidArr)],
            ['uid', '>', 0],
            ['status', '=', 1],
        ];
        $cardList = (new CardUserlistService())->getSome($whereCard, 'id,uid,mer_id');
        $cardListFormat = [];
        foreach ($cardList as $_card) {
            $cardListFormat[$_card['uid'] . $_card['mer_id']] = $_card['id'];
        }

        // 多语言处理
        if (cfg('open_multilingual')) {
            $store_ids = array_unique(array_column($orderList, 'store_id'));
            $multilingualMerchantStore = (new MerchantStoreService())->getStoresByIds($store_ids);
            $multilingualMerchantStore = array_column($multilingualMerchantStore, NULL, 'store_id');

            $mer_ids = array_unique(array_column($orderList, 'mer_id'));
            $multilingualMerchant = (new MerchantService())->getMerchantByMerIds($mer_ids);
            $multilingualMerchant = array_column($multilingualMerchant, NULL, 'mer_id');
            foreach ($orderList as &$ord)
            {
                $ord['store_name'] = isset($multilingualMerchantStore[$ord['store_id']]['name']) ? $multilingualMerchantStore[$ord['store_id']]['name'] : $ord['store_name'];
                $ord['merchant_name'] = isset($multilingualMerchant[$ord['mer_id']]['name']) ? $multilingualMerchant[$ord['mer_id']]['name'] : $ord['merchant_name'];
            }
        }

        foreach($orderList as &$_order){
            $table_type_name='';
            $table_name ='';
            if ($_order['table_type']) {
                $tableType = (new \app\foodshop\model\service\store\FoodshopTableTypeService())->geTableTypeById($_order['table_type']);
                if ($tableType) {
                    $table_type_name = $tableType['name'];
                }
            }
            $_order['del_btn'] =0;
            if ($_order['table_id']) {
                $table = (new \app\foodshop\model\service\store\FoodshopTableService())->geTableById($_order['table_id']);
                if ($table) {
                    $table_name = $table['name'];
                }
            }

            $_order['table_id'] =$table_type_name.' - '.$table_name;
            $_order['store_mer_name'] = $_order['store_name'].'/'.$_order['merchant_name'];
            $_order['order_status'] = $this->getOrderStatus($_order);
            $_order['order_status_txt'] = $this->orderStatusArr[$_order['order_status']];
            if($_order['order_status']==4 || $_order['order_status']==5){
                $_order['del_btn'] =1;
            }
            $_order['order_from_txt'] = $this->orderFromArr[$_order['order_from']];
            $_order['create_time_s'] = $_order['create_time'];
            $_order['create_time'] = date('Y-m-d H:i:s', $_order['create_time']);
            $_order['table_order_status'] = $this->getTableOrderStatus($_order);
            $_order['table_order_status'] && $_order['table_order_status_txt'] = $this->tableOrderStatusArr[$_order['table_order_status']];
            $totalPrice = $_order['total_price'] = max(0, get_format_number($_order['total_price'] - $_order['refund_goods_price']));
            $_order['phone'] = $_order['phone'] ?: $_order['user_phone'];

            // 线上支付金额
            $_order['online_money'] =  get_format_number($_order['system_balance'] + $_order['merchant_balance_pay'] + $_order['merchant_balance_give'] + $_order['pay_money']);
            
            // 余额支付金额
            $_order['balance_merchant'] =  get_format_number($_order['merchant_balance_pay'] + $_order['merchant_balance_give']);

            // 统计线下支付金额
            $_order['offline_money'] = (new DiningOrderPayService())->getNums(['order_id'=>$_order['order_id']], 'offline_money');

            $_order['pay_price'] = max(0, get_format_number($_order['pay_price'] - $_order['refund_money']));

            $_order['pay_type_txt']="";
            $msg=(new DiningOrderPay())->getOne(['order_id'=>$_order['order_id'],'paid'=>1],true,['pay_order_id'=>'desc']);
            if(!empty($msg)){
                $msg=$msg->toArray();
                if($msg['third_id']){
                    $payInfo = (new PayService())->getPayOrderData([$msg['third_id']]);
                    $payInfo = $payInfo[$msg['third_id']] ?? [];
                    if(isset($payInfo['pay_type'])){
                        $_order['pay_type_txt'] =  ($payInfo['pay_type_txt'] ? $payInfo['pay_type_txt'] : '').($payInfo['channel'] ? '('.$payInfo['channel_txt'].')' : '');
                    }
                }
            }
            if(empty($_order['pay_type_txt'])){
                if ($_order['payment_method'] == 'offline') {
                    $_order['pay_type_txt'] =  L_('线下支付') . ((!empty($msg) && $msg['offline_pay_name']) ? '(' . $msg['offline_pay_name'] . ')' : '');
                } else {
                    $_order['pay_type_txt'] =  "--";
                }
            }
            if(!empty($msg['internal_card_pay'])) {
                $internalCardPayMoney = 0;
                $internalCardPay = @json_decode($msg['internal_card_pay'], true);
                if(is_array($internalCardPay)){
                    $internalCardPayMoney = array_sum($internalCardPay);
                }
                $internalCardPayMoney && $_order['pay_type_txt'] .=  "一卡通支付";
            }
            // 是否有商家会员卡
            $hasMerchantCard = 0;
            if (isset($cardListFormat[$_order['uid'] . $_order['mer_id']]) > 0) {
                $hasMerchantCard = 1;
            }

            if ($user) {
                // 用户订单列表
                $_order = $this->formatOrder($_order);
            } elseif (isset($param['show_goods_detail']) && $param['show_goods_detail'] == 1) {
                // 显示商品详情
                $_order = $this->getOrderDetail('', [], $_order, $staff)['order'];
                // 桌台信息
                $_order['table_info'] = [];
                if ($_order['table_type']) {
                    $tableType = (new \app\foodshop\model\service\store\FoodshopTableTypeService())->geTableTypeById($_order['table_type']);
                    if ($tableType) {
                        $_order['table_info']['table_type_name'] = $tableType['name'];
                        $_order['table_info']['tid'] = $tableType['id'];
                    }
                }

                if ($_order['table_id']) {
                    $table = (new \app\foodshop\model\service\store\FoodshopTableService())->geTableById($_order['table_id']);
                    if ($table) {
                        $_order['table_info']['table_name'] = $table['name'];
                        $_order['table_info']['id'] = $table['id'];
                    }
                }
                $_order['order_from_txt'] = $this->orderFromArr[$_order['order_from']];
                $_order['book_time_str'] = $_order['book_time'] ? date('Y-m-d H:i', $_order['book_time']) : '';

                if (isset($param['has_btn']) && $param['has_btn']) {
                    $_order['button'] = $this->getOrderOperateBtn($_order, $param);
                }
            }

            // 是否预订单1是2-否
            $_order['is_book'] = 0;
            if (in_array($_order['order_from'], [0, 3]) && $_order['status'] == 1) {
                $_order['is_book'] = 1;
            }

            $_order['self_take_time'] = $_order['self_take_time'] ? date('m-d H:i', $_order['self_take_time']) . '~' . date('H:i', $_order['self_take_time'] + 20 * 60) : ''; //取餐时间

            $_order['hasMerchantCard'] = $hasMerchantCard;
            $_order['total_price'] = get_format_number($totalPrice);

            //平台结算给商家的钱
            $_order['settlement_merchant_money'] = MerchantMoneyList::where([
                'mer_id'   => $_order['mer_id'],
                'order_id' => $_order['order_id'],
                'type'     => 'dining',
                'income'   => 1
            ])->value('money');
        }
        $returnArr['list'] = $orderList;
        $returnArr['total'] = $count;
        $sh_money=array_column($orderList, 'total_price');
        $returnArr['collect_num']['sh_money'] =get_number_format(array_sum($sh_money));
        $zf_money=array_column($orderList, 'pay_price');
        $returnArr['collect_num']['zf_money'] =get_number_format(array_sum($zf_money));
        $returnArr['statistics_data'] = $statisticsData;
        return $returnArr;
    }

    /**
     * 获取待操作订单列表
     * @param $param
     * @param $systemUser 平台用户
     * @param $isAll 是否查询所有数据
     * @return array
     */
    public function getOrderOperateOrderList($param, $staff = [])
    {
        $page = isset($param['page']) ? $param['page'] : 0;
        $pageSize = isset($param['pageSize']) ? $param['pageSize'] : 0; //每页限制
        $keywords = isset($param['keywords']) ? $param['keywords'] : ''; //搜索关键
        $orderStatus = isset($param['order_status']) ? $param['order_status'] : ''; // 订单状态：0-全部，1-点餐中，2-就餐中
        $type = isset($param['type']) ? $param['type'] : ''; // 订单类型：1-预订单，2-堂食单，3-自取单
        $orderSource = $param["order_source"] ?? 0;  // 订单来源：0-全部，1-扫桌台码，2-线下开台，3-通用码
        $orderTime = isset($param['order_time']) ? $param['order_time'] : ''; // 预定时间
        $storeId = isset($param['store_id']) ? $param['store_id'] : ''; //店铺id

        // 搜索条件
        $where = [];

        // 排序
        $order = [
            'o.order_id' => 'desc'
        ];

        $where[] = ['o.store_id', '=', $storeId];

        // 关键词搜索
        if (!empty($keywords)) {
            $keywords = addslashes($keywords);
            $where[] = ['o.name', 'exp', Db::raw('like "%' . $keywords . '%" OR o.phone like "%' . $keywords . '%"  OR o.order_id like "%' . $keywords . '%" OR o.real_orderid like "%' . $keywords . '%" OR tab.`name` like "%' . $keywords . '%" OR tab_type.`name` like "%' . $keywords . '%" OR u.`phone` like "%' . $keywords . '%"')];
        }

        // 预定时间
        if ($orderTime !== '' && $orderTime !== '-1') {
            $bookStartTime = strtotime(date('Y-m-d')) + $orderTime * 86400;
            $bookEndTime = strtotime(date('Y-m-d')) + $orderTime * 86400 + 86400;
            $period = array(
                $bookStartTime,
                $bookEndTime
            );
            $where[] = ['o.book_time', 'BETWEEN', $period];
        }

        // 订单类型：1-预订单，2-堂食单，3-自取单
        if (empty($type)) {
            throw new \think\Exception(L_('订单类型错误'));
        }
        switch ($type) {
            case '1': //预订单
                $where[] = ['o.status', 'in', '1,4'];
                break;
            case '2': //堂食单
                $where[] = ['o.status', 'between', [20, 39]];
                break;
        }

        // 订单来源：0-全部，1-扫桌台码，2-线下开台，3-通用码
        if ($orderSource) {
            switch ($orderSource) {
                case '1': //扫桌台码
                    $where[] = ['o.order_from', 'in', '0,1,3'];
                    break;
                case '2': //线下开台
                    $where[] = ['o.order_from', 'in', '2,5'];
                    break;
                case '3': //通用码
                    $where[] = ['o.order_from', '=', '4'];
                    break;
            }
        }

        // 订单状态：0-全部，1-点餐中，2-就餐中
        if ($orderStatus) {
            switch ($orderStatus) {
                case '1': //点餐中
                    $where[] = ['o.status', '=', '21'];
                    break;
                case '2': //就餐中
                    $where[] = ['o.status', 'not in', '2, 21'];
                    break;
            }
        }

        $field = 'o.*,o.name, s.store_id,s.name as store_name,m.mer_id,m.name as merchant_name,u.phone as user_phone';
		// 订单列表
        $orderList = $this->getOrderListByJoin($where, $field, $order, $page, $pageSize);

        if (cfg('open_multilingual')) {
            $store_ids = array_unique(array_column($orderList, 'store_id'));
            $multilingualMerchantStore = (new MerchantStoreService())->getStoresByIds($store_ids);
            $multilingualMerchantStore = array_column($multilingualMerchantStore, NULL, 'store_id');

            $mer_ids = array_unique(array_column($orderList, 'mer_id'));
            $multilingualMerchant = (new MerchantService())->getMerchantByMerIds($mer_ids);
            $multilingualMerchant = array_column($multilingualMerchant, NULL, 'mer_id');
            foreach ($orderList as &$ord)
            {
                $ord['store_name'] = isset($multilingualMerchantStore[$ord['store_id']]['name']) ? $multilingualMerchantStore[$ord['store_id']]['name'] : $ord['store_name'];
                $ord['merchant_name'] = isset($multilingualMerchant[$ord['mer_id']]['name']) ? $multilingualMerchant[$ord['mer_id']]['name'] : $ord['merchant_name'];
            }
        }

        // 订单总数
        $count = $this->getOrderCountByJoin($where);
        $uidArr = array_column($orderList, 'uid');
        $whereCard = [
            ['uid', 'in', implode(',', $uidArr)],
            ['uid', '>', 0],
            ['status', '=', 1],
        ];
        $cardList = (new CardUserlistService())->getSome($whereCard, 'id,uid,mer_id');
        $cardListFormat = [];
        foreach ($cardList as $_card) {
            $cardListFormat[$_card['uid'] . $_card['mer_id']] = $_card['id'];
        }
        foreach ($orderList as &$_order) {
            // 是否有商家会员卡
            $hasMerchantCard = 0;
            if (isset($cardListFormat[$_order['uid'] . $_order['mer_id']]) > 0) {
                $hasMerchantCard = 1;
            }
            // 显示商品详情
            $_order = $this->getOrderDetail('', [], $_order, $staff)['order'];
            $_order['order_from_txt'] = $this->orderFromArr[$_order['order_from']];
            //            var_dump($_order);
            // 桌台信息
            $_order['table_info'] = [];
            if ($_order['table_type']) {
                $tableType = (new \app\foodshop\model\service\store\FoodshopTableTypeService())->geTableTypeById($_order['table_type']);
                if ($tableType) {
                    $_order['table_info']['table_type_name'] = $tableType['name'];
                    $_order['table_info']['tid'] = $tableType['id'];
                }
            }

            if ($_order['table_id']) {
                $table = (new \app\foodshop\model\service\store\FoodshopTableService())->geTableById($_order['table_id']);
                if ($table) {
                    $_order['table_info']['table_name'] = $table['name'];
                    $_order['table_info']['id'] = $table['id'];
                }
            }

            $_order['hasMerchantCard'] = $hasMerchantCard;
            $_order['button'] = $this->getOrderOperateBtn($_order, $param);
            //            预计今日时间点到店
            //            预计明日时间点到店
            //            超过２天的就是
            //            预计日期（月日）时间点到店
            //            如预计０８／２６　１５：００到店
            $_order['book_time_txt'] = '';
            if (in_array($_order['order_status'], [2, 6]) && $_order['order_from'] == 0) {

                if (date('Y-m-d', $_order['book_time']) == date('Y-m-d')) {
                    $_order['book_time_txt'] = L_('预计今日X1到店', date('H:i', $_order['book_time']));
                } elseif (strtotime(date('Y-m-d', $_order['book_time'])) - strtotime(date('Y-m-d')) == 86400) {
                    $_order['book_time_txt'] = L_('预计明日X1到店', date('H:i', $_order['book_time']));
                } else {
                    $_order['book_time_txt'] = L_('预计X1到店', date('m/d H:i', $_order['book_time']));
                }
            }

            // 近180天下单次数生成订单即可
            $where = [];
            $_order['order_count'] = '';
            if ($_order['user_id']) {
                $where[] = [
                    'create_time', 'between', [time() - 86400 * 180, $_order['create_time']]
                ];
                $orderCount = $this->getOrderCountByOrderUser($_order, $where);
                $_order['order_count'] = L_('近180天第X1次下单', $orderCount);
            }

            $_order['create_time'] = date('Y-m-d H:i:s', $_order['create_time']);
        }

        $returnArr['list'] = $orderList;
        $returnArr['total'] = $count;
        return $returnArr;
    }

    /**
     * 获得店员端订单列表页操作按钮
     * @param $order array 订单详情
     * @param $param array 其他条件
     * @return array
     */
    public function getOrderOperateBtn($order, $param = [])
    {
        $appType = $param['app_type'] ?? '';
        $orderStatus = $order['order_status'];
        $goPayNum = $order['go_pay_num']; //待支付商品数量
        $buttonArr = [];
        $buttonArr['detail_btn'] = 1; //查看详情
        $buttonArr['cancel_btn'] = 0; //取消按钮1-显示0不显示
        $buttonArr['change_goods_btn'] = 0; //点菜按钮1-显示0不显示
        $buttonArr['cash_btn'] = 0; //结算按钮1-显示0不显示
        $buttonArr['take_btn'] = 0; //接单按钮1-显示0不显示
        $buttonArr['print_btn'] = 0; //打印按钮1-显示0不显示
        $buttonArr['take_seat_btn'] = 0; //确认到店按钮1-显示0不显示
        $buttonArr['clear_btn'] = 0; //清台按钮1-显示0不显示
        if (in_array($orderStatus, [2])) { //未接单
            $buttonArr['cancel_btn'] = 1;
            $buttonArr['take_btn'] = 1;
        } elseif (in_array($orderStatus, [6])) { //待落座
            $buttonArr['cancel_btn'] = 1;
            $buttonArr['take_seat_btn'] = 1;
        } elseif (in_array($orderStatus, [3])) { //就餐中
            $buttonArr['change_goods_btn'] = 1;
            if ($goPayNum || $appType) {
                // 有待支付的商品显示
                $buttonArr['cash_btn'] = 1;
            } elseif ($order['table_id'] == 0) {
                // 没有桌台的待清台订单要显示清台按钮
                $buttonArr['clear_btn'] = 1;
            }

            $buttonArr['print_btn'] = 1;
        } elseif (in_array($orderStatus, [7])) { //点餐中
            $buttonArr['cancel_btn'] = 1;
            $buttonArr['change_goods_btn'] = 1;
            if ($order['order_from'] == 4) {
                $buttonArr['clear_btn'] = 1;
            }
        } elseif (in_array($orderStatus, [4])) { //已完成
            $buttonArr['print_btn'] = 1;
        }
        return $buttonArr;
    }

    /**
     * 获得下单人的所有订单总数
     * @param $where array
     * @return array
     */
    public function getOrderCountByOrderUser($user, $where)
    {
        $where[] = ['status', '>', 0];
        if ($user['uid'] && $user['user_id']) {
            $where[] = ['uid', 'exp',  Db::raw('= ' . $user['uid'] . ' OR (user_type = "' . $user['user_type'] . '" AND user_id = "' . $user['user_id'] . '")')];
        } elseif (!$user['uid'] &&  $user['user_id']) {
            $where[] = ['user_id', '=', $user['user_id']];
            $where[] = ['user_type', '=', $user['user_type']];
        } elseif ($user['uid'] &&  !$user['user_id']) {
            $where[] = ['uid', '=', $user['uid']];
        } else {
            $where[] = ['uid', '=', null];
        }

        $orderCount = $this->getOrderCountByCondition($where);
        return $orderCount;
    }

    /**
     * 订单提醒
     * @param $param array
     * @return array
     */
    public function orderMessageCount($param)
    {
        $param['time'] = $param['time'] ?? 0;
        $type = $param['type'] ?? 1;
        $time = $param['time'] > 0 ? $param['time'] : time();

        // 时间
        $where[] = [
            'create_time', '>', $time
        ];
        $where[] = [
            'store_id', '=', $param['store_id']
        ];
        switch ($type) {
            case '1': //新订单
                $where[] = ['status', 'in', '1,21,20'];
                $newOrderCount = $this->getOrderCountByCondition($where);
                $returnArr['new_order_count'] = $newOrderCount;
                break;
        }

        $returnArr['time'] = time();
        return $returnArr;
    }

    /**
     * 修改桌台号
     * @param $where array
     * @return array
     */
    public function chanegeTable($param, $staff)
    {
        if (empty($param)) {
            throw new \think\Exception(L_('缺少参数'), 1003);
        }

        // 订单详情
        $order = $this->getOrderByOrderId($param['order_id']);
        if (empty($order)) {
            throw new \think\Exception(L_('订单不存在'), 1005);
        }

        // 桌台
        $table = (new FoodshopTableService())->geTableById($param['table_id']);
        if (empty($table)) {
            throw new \think\Exception(L_('桌台不存在'), 1005);
        }

        $data = [
            'table_id' => $param['table_id'],
            'table_type' => $table['tid']
        ];
        if (!$this->updateByOrderId($param['order_id'], $data)) {
            throw new \think\Exception(L_('修改失败'), 1005);
        }

        // 更新桌台状态
        $tableData['id'] = $param['table_id'];
        $tableData['status'] = 1;
        (new FoodshopTableService())->updateTable($tableData);

        if ($order['table_id']) {
            // 查看原桌台下是否有就餐中订单，没有则置为空台
            $where = [];
            $where[] = ['table_id', '=', $order['table_id']];
            if (!$this->getDiningOrder($where)) {
                $tableData['id'] = $order['table_id'];
                $tableData['status'] = 0;
                (new FoodshopTableService())->updateTable($tableData);
            }
        }

        // 添加日志
        $this->diningOrderLogService->addOrderLog($param['table_id'], '9', '店员修改桌台');

        return true;
    }

    /**
     * 修改备注
     * @param $where array
     * @return array
     */
    public function editNote($param)
    {
        if (empty($param['type'])) {
            throw new \think\Exception(L_('缺少参数'), 1003);
        }

        // 订单详情
        $order = $this->getOrderByOrderId($param['order_id']);
        if (empty($order)) {
            throw new \think\Exception(L_('订单不存在'), 1005);
        }

        // 1-整单备注，2-每次提交订单备注，3-商品备注
        switch ($param['type']) {
            case '1':
                $data['note'] = $param['note'];

                $res = $this->updateByOrderId($param['order_id'], $data);
                break;
            case "2":
                if (empty($param['order_num'])) {
                    throw new \think\Exception(L_('缺少参数'), 1003);
                }
                $goodsNote = unserialize($order['goods_note']);
                if (!isset($goodsNote[($param['order_num'] - 1)])) {
                    throw new \think\Exception(L_('下单次数不存在'), 1004);
                }
                $goodsNote[($param['order_num'] - 1)] = $param['note'];

                $data['goods_note'] = serialize($goodsNote);
                $res = $this->updateByOrderId($param['order_id'], $data);
                break;
            case "3":
                if (empty($param['id'])) {
                    throw new \think\Exception(L_('缺少参数'), 1003);
                }
                $where = [
                    'id' => $param['id']
                ];

                $data['note'] = $param['note'];
                $res = (new DiningOrderDetailService())->updateByCondition($where, $data);
                break;
        }

        if ($res === false) {
            throw new \think\Exception(L_('修改失败'), 1005);
        }

        return true;
    }

    /**
     * 修改人数
     * @param $where array
     * @return array
     */
    public function changePeopleNum($param, $staff = [])
    {
        if (empty($param['order_id']) || empty($param['number'])) {
            throw new \think\Exception(L_('缺少参数'), 1003);
        }

        // 订单详情
        $order = $this->getOrderByOrderId($param['order_id']);
        if (empty($order)) {
            throw new \think\Exception(L_('订单不存在'), 1005);
        }

        $data['book_num'] = $param['number'];

        $res = $this->updateByOrderId($param['order_id'], $data);

        if ($res === false) {
            throw new \think\Exception(L_('修改失败'), 1005);
        }

        //必点菜
        $num = $param['number'] - $order['book_num'];

        $goods = (new DiningOrderDetailService())->getOrderDetailByOrderId($param['order_id']);
        if ($goods) {
            if($num > 0){// 增加人数 增加必点菜
                $mustGoods = $this->addMustGoods($param['order_id'], $num);
                if ($order['settle_accounts_type'] == 1) { //先吃后付
                    //通知店员打印菜品
                    $this->callStaff($order, $mustGoods['goods'], 0, $mustGoods['order_num']);
                }
            }elseif($num < 0){// 减少人数 必点菜退菜
                 // 必点菜列表
                $mustGoods = (new FoodshopGoodsLibraryService())->getMustGoodsByStoreId($order['store_id']);
                if (!$mustGoods) {
                    return false;
                }

                foreach ($mustGoods as $_goods) {
                    $goods = (new DiningOrderDetailService())->getOrderDetailByCondition(['order_id'=>$param['order_id'], 'goods_id'=>$_goods['goods_id'],'is_must'=>1]);
                    $refundNum = $num * -1;
                    foreach ($goods as $_refundGoods) {
                        $goodsNum = $_refundGoods['num'] - $_refundGoods['refundNum'];
                        if($goodsNum){// 还有可退的必点菜
                            if($goodsNum >= $refundNum){// 数量大于需要退款的数量 一次退完
                                $refundParam = [
                                    'order_id' => $param['order_id'],
                                    'id' => $_refundGoods['id'],
                                    'num' => $refundNum,
                                ];
                                $refundNum = 0;
                            }else{// 数量小于需要退款的数量 多次退完
                                $refundParam = [
                                    'order_id' => $param['order_id'],
                                    'id' => $_refundGoods['id'],
                                    'num' => $goodsNum,
                                ];
                                $refundNum -= $goodsNum;
                            }

                            // 退菜
                            $res = (new DiningOrderRefundService())->refundGoods($refundParam, $staff);
                        }

                        if($refundNum <= 0){// 退菜完成
                            break;
                        }
                    }
                }
            }
        }

        return true;
    }



    /**
     * 修改状态
     * @param $where array
     * @return array
     */
    public function editOrderStatus($param)
    {
        if (empty($param['order_id'])) {
            throw new \think\Exception(L_('缺少参数'), 1003);
        }

        // 订单详情
        $order = $this->getOrderByOrderId($param['order_id']);
        if (empty($order)) {
            throw new \think\Exception(L_('订单不存在'), 1005);
        }

        // 状态
        $status = $param['status'] ?? 0;

        if (!in_array($status, [4, 21])) {
            throw new \think\Exception(L_('传入状态错误'), 1005);
        }

        $saveData = []; // 要保存的数据数组
        switch ($status) {
            case '4': // 接单
                if ($order['status'] != 1) {
                    throw new \think\Exception(L_('该状态下不能接单'), 1005);
                }
                $saveData['status'] = 4;

                // 店铺信息
                $nowStore = (new MerchantStoreService())->getStoreByStoreId($order['store_id']);

                // 手机号
                $phone = (new DiningOrderService())->getOrderPhone($order);

                // 发送短信
                $sendData = [
                    'order' => $order,
                    'store' => $nowStore,
                    'phone' => $phone,
                    'type' => 'order_taking'
                ];
                (new SmsSendService())->sendSms($sendData);
                break;
            case '21': // 确认到店
                if (!in_array($order['status'], [1, 4])) {
                    throw new \think\Exception(L_('该状态下不能确认到店'), 1005);
                }
                $saveData['status'] = 21;
                $saveData['table_id'] = $param['table_id'] ?? 0;
                if (empty($param['table_id'])) {
                    throw new \think\Exception(L_('缺少参数桌台号'), 1003);
                }

                // 桌台信息
                $table = (new FoodshopTableService())->geTableById($param['table_id']);
                if (empty($param['table_id'])) {
                    throw new \think\Exception(L_('缺少参数桌台号'), 1003);
                }
                $saveData['table_type'] = $table['tid'];

                // 更新桌台状态
                $tableData['id'] = $param['table_id'];
                $tableData['status'] = 1;
                (new FoodshopTableService())->updateTable($tableData);

                break;
        }

        // 保存
        $res = $this->updateByOrderId($param['order_id'], $saveData);

        if ($res === false) {
            throw new \think\Exception(L_('修改失败'), 1005);
        }
        // 添加日志
        $this->diningOrderLogService->addOrderLog($param['order_id'], '15', '店员操作订单', []);

        return true;
    }

    /**
     * 店员开台
     * @param $where array
     * @return array
     */
    public function createOrderByStaff($param, $staff)
    {
        $tableId = $param['table_id'] ?? 0;
        $isTemp = $param['is_temp'] ?? 0;
        $orderFrom = $param['order_from'] ?? 2;
        if (!$tableId && $isTemp != 1) {
            throw new \think\Exception(L_('缺少参数'), 1003);
        }

        $store = (new MerchantStoreService())->getStoreByStoreId($staff['store_id']);
        $foodStore = (new MerchantStoreFoodshopService())->getStoreByStoreId($staff['store_id']);
        if ($store && empty($foodStore)) {
            throw new \think\Exception(L_('抱歉！请先到商家后台完善该店铺信息后才可使用此功能'), 1005);
        }

        $data = [];

        if (isset($param['is_temp']) && $param['is_temp'] == 1) { //临时订单查看是否已经存在
            $data['is_temp'] = 1;
            $data['staff_id'] = $staff['id'];
            $where = [
                ['staff_id', '=', $staff['id']],
                ['is_temp', '=', 1],
                ['status', '<', 40]
            ];
            $order = $this->getOrderByCondition($where);
            if ($order) {
                // 查看订单是否已经保存过购物车 ，有则生成新的订单，没有则直接返回订单id（阻止多次下单）
                $goodsDetail = (new DiningOrderDetailService())->getOrderDetailByOrderId($order['order_id']);
                if (empty($goodsDetail)) {
                    $returnArr = [];
                    $returnArr['order_id'] = $order['order_id'];
                    return $returnArr;
                }
            }
        }

        if (isset($param['table_id']) && $param['table_id']) {
            // 桌台
            $table = (new FoodshopTableService())->geTableById($param['table_id']);
            if (empty($table)) {
                throw new \think\Exception(L_('桌台不存在'), 1005);
            }
        }

        $data['mer_id'] = $store['mer_id'];
        $data['book_num'] = $param['book_num'] ?? 0;
        $data['store_id'] = $store['store_id'];
        $data['table_id'] = $table['id'] ?? 0;
        $data['table_type'] = $table['tid'] ?? 0;
        $data['create_time'] = time();
        $data['order_from'] = $orderFrom ?: 2;
        $data['status'] = 21; //点餐中
        $data['real_orderid'] = build_real_orderid($staff['id']); //real_orderid
        $data['settle_accounts_type'] = $foodStore['settle_accounts_type'] ?? 1;

        if (!$orderId = $this->add($data)) {
            throw new \think\Exception(L_('开台失败，请稍后重试'), 1005);
        }

        if ($tableId) {
            // 更新桌台状态
            $tableData['id'] = $param['table_id'];
            $tableData['status'] = 1;
            (new FoodshopTableService())->updateTable($tableData);
        }
        // 添加日志
        $this->diningOrderLogService->addOrderLog($orderId, '2', '店员开台');

        $returnArr = [];
        $returnArr['order_id'] = $orderId;
        return $returnArr;
    }



    /**
     * 待操作订单筛选条件
     * @param $where array
     * @return array
     */
    public function getSearchCondition($param, $staff)
    {
        $returnArr = [];

        // 预订单总数
        $where[] = ['store_id', '=', $staff['store_id']];
        $where[] = ['status', 'in', '1,4'];
        $bookCount = $this->getCount($where);

        // 就餐中订单总数
        $where = [];
        $where[] = ['store_id', '=', $staff['store_id']];
        $where[] = ['status', '>=', '20'];
        $where[] = ['status', '<', '40'];
        $diningCount = $this->getCount($where);

        // 订单类型
        $returnArr['type'] = [
            [
                'key' => 1,
                'value' => L_('预订单') . '(' . $bookCount . ')'
            ],
            [
                'key' => 2,
                'value' => L_('堂食单') . '(' . $diningCount . ')'
            ],
            //            [
            //                'key' => 3,
            //                'value' => L_('自取单')
            //            ]
        ];

        //订单状态
        $returnArr['order_status'] = [
            [
                'key' => 0,
                'value' => L_('全部')
            ],
            [
                'key' => 1,
                'value' => L_('点餐中')
            ],
            [
                'key' => 2,
                'value' => L_('就餐中')
            ],
        ];

        //历史订单状态 
        if (request()->param('app_type')) {

            // 统计
            $where = [];
            $where[] = ['store_id', '=', $staff['store_id']];
            $allCount = $this->getCount($where);

            // 进行中
            $where = [];
            $where[] = ['store_id', '=', $staff['store_id']];
            $where[] = ['status', '<', 40];
            $diningCount = $this->getCount($where);

            // 已完成
            $where = [];
            $where[] = ['store_id', '=', $staff['store_id']];
            $where[] = ['status', '>=', 40];
            $where[] = ['status', '<=', 50];
            $completeCount = $this->getCount($where);

            // 已取消
            $where = [];
            $where[] = ['store_id', '=', $staff['store_id']];
            $where[] = ['status', '>', 50];
            $cancelCount = $this->getCount($where);

            $returnArr['history_order_status'] = [
                [
                    'key' => 0,
                    'value' => L_('全部') . '(' . $allCount . ')'
                ],
                [
                    'key' => 1,
                    'value' => L_('进行中') . '(' . $diningCount . ')'
                ],
                [
                    'key' => 2,
                    'value' => L_('已完成') . '(' . $completeCount . ')'
                ],
                [
                    'key' => 3,
                    'value' => L_('已取消') . '(' . $cancelCount . ')'
                ],
            ];
        }

        //订单来源
        $returnArr['order_source'] = [
            [
                'key' => 0,
                'value' => L_('全部')
            ],
            [
                'key' => 1,
                'value' => L_('扫桌台码')
            ],
            [
                'key' => 2,
                'value' => L_('线下开台')
            ],
            [
                'key' => 3,
                'value' => L_('通用码')
            ],
        ];

        //order_time 预定时间（值不固定，预订单时显示）
        $returnArr['order_time'] = [];

        $storeId = $param['store_id'] ?? 0;
        $store = (new MerchantStoreFoodshopService())->getStoreByStoreId($storeId);
        if ($store) {
            if ($store['book_day']) {
                $orderTimeArr = [];
                $orderTimeArr['-1'] = [
                    'key' => '-1',
                    'value' => L_('全部')
                ];
                $i = 0;
                while ($i + 1 <= $store['book_day']) {
                    $orderTimeArr[$i] = [
                        'key' => $i,
                    ];
                    switch ($i) {
                        case '0':
                            $orderTimeArr[$i]['value'] = L_('今日');
                            break;
                        case '1':
                            $orderTimeArr[$i]['value'] = L_('明日');
                            break;
                        default:
                            $orderTimeArr[$i]['value'] = L_('X1天后', $i);
                            break;
                    }
                    $i++;
                }
                $returnArr['order_time'] = array_values($orderTimeArr);
            }
        }
        return $returnArr;
    }


    /**
     * 多人点餐订单列表
     * @param $where array
     * @return array
     */
    public function formatOrder($order)
    {
        $returnArr = $order;
        $returnArr['show_price'] = $order['total_price'];

        if ($returnArr['order_status'] == 4) { //已完成 显示支付金额
            $returnArr['show_price'] = $order['pay_price'];
        }
        $returnArr['order_status_txt'] = $this->orderStatusArr[$order['order_status']];

        if ($order['status'] == 2) { // 定金待支付
            // 查询待支付订单
            $whereOrder = [
                'paid' => 0,
                'order_type' => 0,
                'order_id' => $order['order_id'],
            ];
            $payOrder = (new DiningOrderPayService())->getOne($whereOrder);
            if ($payOrder) {
                $returnArr['pay_order_id'] = $payOrder['pay_order_id'];
                $returnArr['show_price'] = $payOrder['price'];
                $returnArr['pay_type'] = 'dining';
            }
        } elseif ($order['status'] == 3) { // 尾款待支付 自取尾款待支付
            // 查询待支付订单
            $whereOrder = [
                'paid' => 0,
                'order_type' => 1,
                'order_id' => $order['order_id'],
            ];
            $payOrder = (new DiningOrderPayService())->getOne($whereOrder);
            if ($payOrder) {
                $returnArr['pay_order_id'] = $payOrder['pay_order_id'];
                $returnArr['show_price'] = $payOrder['price'];
                $returnArr['pay_type'] = 'dining';
            }
        }
        return $returnArr;
    }


    /**
     * 超时取消
     * @param $order array 订单详情
     * @return bool 返回是否取消了订单
     */
    public function overTimeCancelOrder($order)
    {
        if ($order['status'] == 2) { // 定金待支付
            // 查询待支付订单
            $whereOrder = [
                'paid' => 0,
                'order_type' => 0,
                'order_id' => $order['order_id'],
            ];
            $payOrder = (new DiningOrderPayService())->getOne($whereOrder);
            if ($payOrder) {
                if ((900 - (time() - $payOrder['add_time'])) <= 0) {
                    // 已超时

                    // 取消订单
                    $data['status'] = 60;
                    $data['order_id'] = $order['order_id'];
                    $data['user_type'] = 'system';
                    try {
                        $res = (new DiningOrderRefundService())->cancelOrder($data);
                        return true;
                    } catch (\Exception $e) {
                        return false;
                    }
                }
            }
        } elseif ($order['status'] == 3 && $order['order_from'] == 4 && $order['is_self_take'] == 1) { // 尾款待支付 自取尾款待支付
            // 查询待支付订单
            $whereOrder = [
                'paid' => 0,
                'order_type' => 1,
                'order_id' => $order['order_id'],
            ];
            $payOrder = (new DiningOrderPayService())->getOne($whereOrder);
            if ($payOrder) {
                if ((900 - (time() - $payOrder['add_time'])) <= 0) {
                    // 已超时
                    $data['status'] = 60;
                    $data['order_id'] = $order['order_id'];
                    try {
                        $res = (new DiningOrderRefundService())->cancelOrderAndGoods($data);
                        return true;
                    } catch (\Exception $e) {
                        return false;
                    }
                }
            }
        }
        return false;
    }

    /**
     * 返回订单状态
     * @param $where array 
     * @return array
     */
    public function getOrderStatus($order)
    {
        if ($order['status'] == 0) { //订单生成
            return 0;
        }

        if (in_array($order['status'], [2, 3])) { //待支付
            return 1;
        }

        if (in_array($order['status'], [4])) { //已接单
            return 6;
        }

        if (in_array($order['status'], [21])) { //点餐中
            return 7;
        }

        if ($order['status'] == 1 && in_array($order['order_from'], [0, 3])) { //预定单
            return 2;
        }

        if ($order['status'] >= 20 && $order['status'] < 40) { //就餐中
            return 3;
        }

        if ($order['status'] >= 40 && $order['status'] < 50) { //已完成
            return 4;
        }

        if ($order['status'] == 51) { //已退款
            return 5;
        }

        if ($order['status'] == 60) { //已取消
            return 5;
        }
    }

    /**
     * 返回在桌台中订单状态
     * @param $where array
     * @return array
     */
    public function getTableOrderStatus($order)
    {


        if (in_array($order['status'], [20])) { //就餐中
            $where = [
                ['order_id', '=', $order['order_id']],
                ['status', 'in', '0,1,2'],
                ['num', 'exp', Db::raw(' > refundNum')],
            ];
            $goPayGoods = (new DiningOrderDetailService())->getOne($where);
            if ($goPayGoods) {
                return 1; //就餐中
            } else {
                return 3; //待清台
            }
        }

        if (in_array($order['status'], [21])) { //点餐中
            return 2;
        }

        if (in_array($order['status'], [30])) { //待清台
            $where = [
                ['order_id', '=', $order['order_id']],
                ['status', 'in', '0,1,2'],
                ['num', 'exp', Db::raw(' > refundNum')],
            ];
            $goPayGoods = (new DiningOrderDetailService())->getOne($where);
            if ($goPayGoods) {
                return 1;
            } else {
                return 3;
            }
        }
    }



    /**
     * 返回店员所需订单状态
     * @param $where array
     * @return array
     */
    public function getStaffOrderStatus($order)
    {
        if ($order['status'] == 21) { //点餐中
            return 3;
        }

        if ($order['status'] == 20) { //就餐中
            $where = [
                ['order_id', '=', $order['order_id']],
                ['status', 'in', '0,1,2'],
                ['num', 'exp', Db::raw(' > refundNum')],
            ];
            $goPayGoods = (new DiningOrderDetailService())->getOne($where);
            if ($goPayGoods) {
                return 2;
            } else {
                return 4;
            }
        }

        if ($order['status'] >= 30) { //待清台

            $where = [
                ['order_id', '=', $order['order_id']],
                ['status', 'in', '0,1,2'],
                ['num', 'exp', Db::raw(' > refundNum')],
            ];
            $goPayGoods = (new DiningOrderDetailService())->getOne($where);
            if ($goPayGoods) {
                return 2;
            } else {
                return 4;
            }
        }
        return 1; //空台
    }

    /**
     * 返回就餐中订单
     * @param $where array 
     * @return array
     */
    public function getDiningOrder($where)
    {
        // 已确认未完成
        $where[] = ['status', '>=', '20'];
        $where[] = ['status', '<', '40'];
        $where[] = ['is_temp', '=', '0'];
        $orderList = $this->getOrderListByCondition($where);
        $myDiningOrder = [];
        // 排除自取且生成了支付单的
        foreach ($orderList as $_diningOrder) {
            if ($_diningOrder['is_self_take'] == 1) {
                // 查询支付订单
                $payWhere = [
                    'order_type' => 1,
                    'order_id' => $_diningOrder['order_id']
                ];
                $payOrder = (new DiningOrderPayService())->getOne($payWhere);
                if ($payOrder) {
                    continue;
                }
            }
            $myDiningOrder = $_diningOrder;
        }
        return $myDiningOrder;
    }


    /**
     * 返回预订单
     * @param $where array 条件
     * @return array
     */
    public function getBookOrder($user, $where = [])
    {
        // 预订单
        $where[] = ['status', 'in', '1,4'];
        $where[] = ['user_type', '=', $user['user_type']];
        $where[] = ['user_id', '=', $user['user_id']];
        $where[] = ['is_temp', '=', '0'];
        $orderList = $this->getOrderListByCondition($where);
        foreach ($orderList as &$detail) {
            $detail['book_time_str'] = date('Y-m-d H:i', $detail['book_time']);
        }
        return $orderList;
    }

    /**
     * 根据条件返回订单列表
     * @param $where array 条件
     * @param $order array 排序
     * @param $page int 页数，为0时表示不分页
     * @return array
     */
    public function getOrderListByCondition($where, $order = ['order_id' => 'desc'], $page = 0, $pageSize = 10)
    {
        $orderList = $this->diningOrderModel->getOrderListByCondition($where, $order, $page, $pageSize);
        if (!$orderList) {
            return [];
        }
        return $orderList->toArray();
    }

    /**
     * 根据条件返回订单列表
     * @param $where array 条件
     * @param $order array 排序
     * @return array
     */
    public function getOrderListByJoin($where, $field = '', $order = ['o.order_id' => 'DESC'], $page = 0, $pageSize = 0,$payType='all')
    {
        if (empty($order)) {
            $order = ['o.order_id' => 'DESC'];
        }
        $orderList = $this->diningOrderModel->getOrderListByJoin($where, $field, $order, $page, $pageSize,$payType);
        if (!$orderList) {
            return [];
        }
        return $orderList->toArray();
    }

    /**
     * 根据条件返回订单总数
     * @param $where array 条件
     * @return array
     */
    public function getOrderCountByJoin($where,$payType='all')
    {
        $count = $this->diningOrderModel->getOrderCountByJoin($where,$payType);
        //        var_dump($this->diningOrderModel->getLastSql());
        if (!$count) {
            return 0;
        }
        return $count;
    }

    /**
     * 根据条件返回订单总数
     * @param $where array 条件
     * @return array
     */
    public function getOrderSumByJoin($where,$payType='all', $field = true)
    {
        $count = $this->diningOrderModel->getOrderSumByJoin($where, $payType, $field);
        //        var_dump($this->diningOrderModel->getLastSql());
        if (!$count) {
            return [];
        }
        return $count;
    }    
    
    /**
    * 根据条件返回订单线下支付总额
    * @param $where array 条件
    * @return array
    */
   public function getOrderOfflineMoneyTotal($where, $field = true)
   {
       $count = $this->diningOrderModel->getOrderOfflineMoneyTotal($where, $field);
       if (!$count) {
           return [];
       }
       return $count;
   }
    
    /**
     * 根据条件返回订单总数
     * @param $where array 条件
     * @return array
     */
    public function getCount($where)
    {
        $orderCount = $this->diningOrderModel->getCount($where);
        if (!$orderCount) {
            return '0';
        }
        return $orderCount;
    }

    /**
     * 更新数据
     * @param $orderId int 
     * @param $data array 
     * @return array
     */
    public function updateByOrderId($orderId, $data)
    {
        if (empty($orderId) || empty($data)) {
            return false;
        }

        $where = [
            'order_id' => $orderId
        ];
        $result = $this->diningOrderModel->where($where)->update($data);
        if ($result === false) {
            return false;
        }

        $this->addSystemOrder($orderId, 1);
        return $result;
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data)
    {
        if (isset($data['user_type']) && $data['user_type'] == 'uid') {
            $data['uid'] = $data['user_id'];
        }
        $result = $this->diningOrderModel->save($data);
        if (!$result) {
            return false;
        }

        $nowOrder = $this->getOrderByOrderId($this->diningOrderModel->id);
        $this->setFetchNumber($nowOrder);
        $this->addSystemOrder($this->diningOrderModel->id,0);
        return $this->diningOrderModel->id;
    }

    /**
     * 写入平台总订单
     * @param $tableId int 桌台id
     * @return array
     */
    public function addSystemOrder($orderId = 0,$step = 1){
    	if(!$orderId) return false;

        $nowOrder = $this->getOrderByOrderId($orderId);
        if ($nowOrder['is_temp'] == 1) { //临时订单
            return false;
        }
        $business = 'dining'; 
        $businessOrderId = $orderId; 

        // 如果系统订单不存在该订单则添加
        $systemOrderService = new SystemOrderService();
        $systemWhere= [
            'type' => $business,
            'order_id' => $businessOrderId,
        ];
        $systemOrder = $systemOrderService->getCount($systemWhere);
        $step = empty($systemOrder) ? 0 : 1;

        $data['price'] = $nowOrder['price'];
        $data['discount_price'] = $nowOrder['price'];
        $data['total_price'] = $nowOrder['total_price'];
        $data['paid'] = $nowOrder['pay_time'] > 0 ? 1 : 0;
        $data['pay_time'] = $nowOrder['pay_time'];
        if($step == 0){
            $data['store_id'] = $nowOrder['store_id'];
            $data['mer_id'] = $nowOrder['mer_id'];
            $data['real_orderid'] = $nowOrder['real_orderid'];
            $data['system_status'] = 0;
            // 添加系统总订单
            if (in_array($nowOrder['status'], [2, 3])) {
                // 待支付
                $data['system_status'] = 0;
            } elseif ($nowOrder['status'] == 40) {
                //完成订单 待评价
               $data['system_status'] = 2;
               $data['paid'] = 1;
               $data['pay_type'] = time();
            }elseif($nowOrder['status']>=41 && $nowOrder['status']<50){
                //完成订单
                $data['system_status'] = 3;
            } elseif (in_array($nowOrder['status'], [51, 60])) {
                // 取消订单
               $data['system_status'] = 5;
            }else{
               $data['system_status'] = 10;
            } 
            $data['last_time'] = time();
            $systemOrderService->saveOrder($business, $businessOrderId, $nowOrder['uid'], $data);
        }else{
            if(in_array($nowOrder['status'],[2,3])) {
                // 待支付
                $data['system_status'] = 0;
                $systemOrderService->editOrder($business, $businessOrderId, $data);
            } elseif ($nowOrder['status'] == 40) {
                //完成订单 待评价
                $systemOrderService->paidOrder($business, $businessOrderId, $data);
                $systemOrderService->completeOrder($business, $businessOrderId);
            } elseif ($nowOrder['status'] >= 41 && $nowOrder['status'] < 50) {
                //完成订单
                $systemOrderService->commentOrder($business, $businessOrderId, $data);
            } elseif (in_array($nowOrder['status'], [51, 60])) {
                // 取消订单
                $systemOrderService->cancelOrder($business, $businessOrderId);
            }else{
                $systemOrderService->editOrder($business, $businessOrderId, $data);
            }
        }
        return true;
    }

    /**
     * 根据订单id条件返回订单
     * @param $orderId int 条件
     * @return array
     */
    public function getOrderByOrderId($orderId)
    {
        $order = $this->diningOrderModel->getOrderByOrderId($orderId);
        if (!$order) {
            return [];
        }
        return $order->toArray();
    }

    /**
     * 根据条件返回单个订单
     * @param $where array 条件
     * @return array
     */
    public function getOrderByCondition($where, $order = ['order_id' => 'desc'])
    {
        if (empty($order)) {
            $order = ['order_id' => 'DESC'];
        }
        $order = $this->diningOrderModel->getOrderByCondition($where, $order);
        if (!$order) {
            return [];
        }
        return $order->toArray();
    }

    /**
     * 根据条件返回订单数量
     * @param $where array 条件
     * @return array
     */
    public function getOrderCountByCondition($where)
    {
        $orderCount = $this->diningOrderModel->getOrderCountByCondition($where);
        if (!$orderCount) {
            return '0';
        }
        return $orderCount;
    }

    /**
     * 根据长订单号获取短订单号
     * @param $where array 条件
     * @return array
     */
    public function getOrderIdByRealOrderid($realOrderid)
    {
        return $this->diningOrderModel->where('real_orderid', $realOrderid)->value('order_id');
    }
}
