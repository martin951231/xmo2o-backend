<?php

/**
 * 餐饮订单日志service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/04 13:53
 */

namespace app\foodshop\model\service\order;

use app\foodshop\model\db\DiningOrderLog as DiningOrderLogModel;
use app\foodshop\model\service\store\MerchantStoreStaffService;
use app\merchant\model\service\VoiceBoxService;
use think\facade\Db;

class DiningOrderLogService
{
    public $diningOrderLogModel = null;
    public function __construct()
    {
        $this->diningOrderLogModel = new DiningOrderLogModel();
    }

    /**
     *获取订单日志信息
     * @param $orderType 订单类型 
     * @param $orderId 订单id 
     * @param $time int 时间戳 
     * @return array
     */
    public function getOrderLog($param, $time = '0', $user = [])
    {

        $orderId = isset($param['orderId']) ? $param['orderId'] : '0'; //订单id
        $tempId = isset($param['tempId']) ? $param['tempId'] : '0'; //临时订单id

        if (!$orderId && !$tempId) {
            return [];
        }

        $returnArr = [];
        //商品数量
        $num = '0';
        // 购物车总价
        $totalPrice = '0';

        // 订单类型 temp-临时 order-正式
        $orderType = '';
        $orderTypeId = '';

        $tempOrderId = '';
        // 返回数据
        $returnArr = [];
        $returnArr['time'] = time();
        if ($tempId) {
            // 临时订单详情
            $tempOrder = (new DiningOrderTemporaryService())->getOrderByTempId($tempId);

            if (empty($tempOrder)) {
                return $returnArr;
            }

            $orderType = 'temp';
            $orderTypeId = $tempId;

            if ($tempOrder['order_id']) {
                // 已绑定正式订单 查询正是订单购物车
                //                $orderId = $tempOrder['order_id'];

                $tempOrderId = $tempOrder['order_id'];
            } else {
            }
        }
        if ($orderId) { // 正式订单
            $nowOrder = (new DiningOrderService())->getOrderByOrderId($orderId);
            if (empty($nowOrder)) {
                throw new \think\Exception(L_('订单不存在'), 1003);
            }
            $orderType = 'order';
            $orderTypeId = $orderId;
        }
        // 查询订单日志
        if ($tempOrderId) {
            $where[] = [$orderType . '_id', 'exp', Db::raw('= ' . $orderTypeId . ' OR order_id=' . $tempOrderId)];
        } else {
            $where[] = [$orderType . '_id', '=', $orderTypeId];
        }
        $where[] = ['create_time', '>=', $time];
        $logList = $this->getLogListByCondition($where);
        $changeGoods = '0';
        $confirmGoods = '0';
        $operatorType = ''; //提交购物车用户类型0 -用户1-系统2-管理员3-店员4-商户
        $clearGoods = '0';
        $confirmOrder = '0';
        $lockOrder = '0';
        $orderDetail = '0';
        $changOrder = '0';

        // 记录变更菜品的日志id
        $goodsLogId = [];
        $returnArr['goods_log'] = [];
        if ($logList) {
            foreach ($logList as $_log) {
                switch ($_log['status']) {
                    case '11': //店员点菜
                        // 返回菜品信息 请求购物车
                        $changeGoods = '1';
                        $goodsLogId[] = $_log['id'];
                        break;
                    case '12': //用户点菜
                        // 返回菜品信息 请求购物车
                        $changeGoods = '1';
                        $goodsLogId[] = $_log['id'];
                        break;
                    case '13': //确认菜品
                        $confirmGoods = '1';
                        $operatorType = $_log['operator_type'];

                        break;
                    case '14': //清空购物车
                        $clearGoods = '1';
                        $operatorType = $_log['operator_type'];
                        break;
                    case '15': //确认订单
                        $changOrder = '1';
                        break;
                    case '16': //锁定订单
                        $lockOrder = '1';
                        break;
                    case '18': //完成订单
                    case '20': //取消订单
                    case '21': //取消订单
                    case '22': //取消订单
                    case '23': //取消订单
                    case '30': //退款
                        $orderDetail = '1';
                        break;
                    default:
                        $changOrder = '1';
                        break;
                }
            }

            if ($orderDetail) { //直接跳转订单详情
                $returnArr['type'] = 'order_detail';
                if ($user) {
                    if ($user['user_id'] == $_log['user_id'] && $user['user_type'] == $_log['user_type']) {
                        $returnArr['type'] = 'change_order';
                    }
                }
            } elseif ($confirmGoods || $lockOrder) { //提交菜品 将其他人带到订单详情
                $returnArr['type'] = 'order_detail';
                $returnArr['operator_type'] = $operatorType;
                if ($user) {
                    if ($user['user_id'] == $_log['user_id'] && $user['user_type'] == $_log['user_type']) {
                        $returnArr['type'] = 'change_order';
                    }
                }
            } elseif ($clearGoods) {
                $returnArr['type'] = 'clear_goods';
                $returnArr['operator_type'] = $operatorType;
                if ($user) {
                    if ($user['user_id'] == $_log['user_id'] && $user['user_type'] == $_log['user_type']) {
                        $returnArr['type'] = 'change_order';
                    }
                }
            } elseif ($changeGoods) {
                $returnArr['type'] = 'change_goods';
                $returnArr['goods_log'] = (new DiningOrderGoodsLogService())->getGoodsLog($goodsLogId);
            } elseif ($changOrder) {
                $returnArr['type'] = 'change_order';
            }
        }
        return $returnArr;
    }


    /**
     *批量插入数据
     * @param $data array 
     * @return array
     */
    public function addOrderLog($orderId, $status, $note = '', $user = [], $data = [])
    {
        if (empty($orderId)) {
            return false;
        }

        if ($user) {
            if (isset($data['user_type'])) {
                $data['user_type'] =  $user['user_type'];
                $data['user_id'] = $user['user_id'];
            } elseif (isset($user['uid'])) {
                $data['uid'] = $user['uid'];
                $data['user_type'] = 'uid';
                $data['user_id'] = $user['uid'];
            }
        }

        $data['order_id'] = $orderId;
        $data['status'] = $status;
        $data['note'] = $note;
        $data['create_time'] = time();
        try {
            // insertAll方法添加数据成功返回添加成功的条数
            $result = $this->diningOrderLogModel->save($data);

            //云音响 提示内容
            $msg = L_('X1新订单提醒，请及时查看！', cfg('meal_alias_name'));

            // 订单信息
            $nowOrder = (new DiningOrderService())->getOrderByOrderId($orderId);
            switch ($status) {
                case '0': // 在线预订下单
                    if ($nowOrder['book_price'] <= 0) {
                        $tempOrder = $nowOrder;
                        $tempOrder['status'] = 1;
                        // 推送app消息
                        (new MerchantStoreStaffService())->sendMsgFoodShop($tempOrder);
                    }
                    break;
                case '1': // 生成预订单 提前选菜生成正式订单
                    $tempOrder = $nowOrder;
                    $tempOrder['status'] = 1;
                    // 推送app消息
                    (new MerchantStoreStaffService())->sendMsgFoodShop($tempOrder);
                    break;
                case '2':// 生成就餐中订单  通用码和直接选菜生成正式订单
                    break;
                case '13': // 确认菜品

                    // 通用码和直接选菜 第一次确认菜品通知
                    if ($nowOrder['settle_accounts_type'] == 1 && $nowOrder['order_from'] == 4) { // 先吃后付
                        //第几次下单
                        $orderNum = (new DiningOrderDetailService()) ->getNextOrderNum($nowOrder['order_id']);
                        if($orderNum <= 2){
                            $tempOrder = $nowOrder;
                            $tempOrder['status'] = 1;
                            // 推送app消息
                            (new MerchantStoreStaffService())->sendMsgFoodShop($tempOrder);
                        }
                    }
                    break;
                case '3': // 用户访问订单详情
                    break;
                case '5': // 扫桌台码下单
                    if ($nowOrder['settle_accounts_type'] == 1) { // 先吃后付
                        $tempOrder = $nowOrder;
                        $tempOrder['status'] = 1;
                        // 推送app消息
                        (new MerchantStoreStaffService())->sendMsgFoodShop($tempOrder);
                    }
                    break;
                case '8': // 店员修改人数
                case '9': // 店员修改桌台
                case '10': // 用户支付定金
                case '11': // 点菜【店员】
                case '12': // 点菜【用户】
                case '14': // 清空购物车
                case '15': // 确认订单
                case '16': // 订单锁定
                case '17': // 用户支付尾款
                case '18': // 订单完成
                case '19': // 订单解锁
                case '20': // 取消订单（未支付定金超时取消）
                case '21': // 取消订单（未到店超时取消，手动取消订单[店员、用户]）
                case '22': // 取消订单（用户）
                case '23': // 取消订单（店员）
                case '30': // 退款
                    break;
            }
        } catch (\Exception $e) {
            return false;
        }

        return $this->diningOrderLogModel->id;
    }

    /**
     *批量插入数据
     * @param $data array 
     * @return array
     */
    public function addTempOrderLog($tempId, $status, $note = '', $user = [], $data = [])
    {
        if (empty($tempId)) {
            return false;
        }

        if ($user) {
            $data['user_type'] =  $user['user_type'];
            $data['user_id'] = $user['user_id'];
            $data['uid'] = $user['uid'];
        }

        $data['temp_id'] = $tempId;
        $data['status'] = $status;
        $data['note'] = $note;
        $data['create_time'] = time();

        try {
            // insertAll方法添加数据成功返回添加成功的条数
            $result = $this->diningOrderLogModel->save($data);
        } catch (\Exception $e) {
            return false;
        }

        return $this->diningOrderLogModel->id;
    }


    /**
     * 获取日志列表
     * @param $where array 条件
     * @return array
     */
    public function getLogListByCondition($where)
    {
        $logList = $this->diningOrderLogModel->getLogListByCondition($where);
        if (!$logList) {
            return [];
        }
        return $logList->toArray();
    }
    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data)
    {
        if (empty($where) || empty($data)) {
            return false;
        }

        $result = $this->diningOrderLogModel->updateThis($where, $data);
        if ($result === false) {
            return false;
        }

        return $result;
    }
}
