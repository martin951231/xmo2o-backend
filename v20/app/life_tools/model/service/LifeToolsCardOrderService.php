<?php
/**
 * 次卡订单
 */

namespace app\life_tools\model\service;

use app\common\model\db\CardNew;
use app\common\model\service\coupon\MerchantCouponService;
use app\common\model\service\coupon\SystemCouponService;
use app\common\model\service\export\ExportService as BaseExportService;
use app\common\model\service\merchant_card\MerchantCardService;
use app\common\model\service\order\SystemOrderService;
use app\common\model\service\UserService;
use app\group\model\db\TempOrderData;
use app\life_tools\model\db\LifeTools;
use app\life_tools\model\db\LifeToolsCard;
use app\life_tools\model\db\LifeToolsCardOrder;
use app\life_tools\model\db\LifeToolsCardOrderRecord;
use app\life_tools\model\db\LifeToolsCardTools;
use app\life_tools\model\db\LifeToolsMember;
use app\life_tools\model\db\LifeToolsMessage;
use app\life_tools\model\db\LifeToolsOrder;
use app\life_tools\model\db\LifeToolsOrderDetail;
use app\life_tools\model\db\LifeToolsTicket;
use app\life_tools\model\db\LifeToolsTicketSaleDay;
use app\common\model\db\Merchant;
use app\merchant\model\service\card\CardNewService;
use app\merchant\model\service\MerchantMoneyListService;
use app\pay\model\db\PayOrderInfo;
use app\pay\model\service\PayService;
use think\facade\Db;
use think\Model;

class LifeToolsCardOrderService
{
    public $LifeTools                = null;
    public $LifeToolsCard            = null;
    public $LifeToolsCardTools       = null;
    public $LifeToolsCardOrder       = null;
    public $LifeToolsCardOrderRecord = null;
    public $LifeToolsMessage       = null;
    public function __construct()
    {
        $this->LifeTools                = new LifeTools();
        $this->LifeToolsCard            = new LifeToolsCard();
        $this->LifeToolsCardTools       = new LifeToolsCardTools();
        $this->LifeToolsCardOrder       = new LifeToolsCardOrder();
        $this->LifeToolsCardOrderRecord = new LifeToolsCardOrderRecord();
        $this->LifeToolsMessage         = new LifeToolsMessage();
    }

    /**
     *获取订单列表
     * @param $param array
     * @return array
     */
    public function getList($param) {
        $limit = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];
        if(isset($param['select_all']) && $param['select_all']){
            $limit = [];
        }
        if (!empty($param['mer_id'])) {
            $where[] = ['o.mer_id', '=', $param['mer_id']];
        }
        if (!empty($param['keyword'])) {
            $search_type = '';
            switch ($param['search_type']) {
                case 1:
                    $search_type = 'order_id|orderid';
                    break;
                case 2:
                    $search_type = 'nickname';
                    break;
                case 3:
                    $search_type = 'phone';
                    break;
                case 4:
                    $search_type = 'o.title';
                    break;
                case 5:
                    $tools_ids = $this->LifeTools->where([['title', 'like', '%' . $param['keyword'] . '%'], ['is_del', '=', 0], ['status', '=', 1]])->column('tools_id') ?? [];
                    $card_ids  = $this->LifeToolsCardTools->where([['tools_id', 'in', $tools_ids]])->column('card_id') ?? [];
                    $where[]   = ['o.card_id', 'in', $card_ids];
                    break;
            }
            $search_type && $where[] = [$search_type, 'like', '%' . $param['keyword'] . '%'];
        }
        if (!empty($param['type']) && $param['type'] != 'all') {
            if($param['type'] == 'sports'){
                $where[] = ['o.type', 'exp', Db::raw('= "stadium" OR o.type = "course" OR o.type = "sports"')];
            }else{
                $where[] = ['o.type', '=', $param['type']];
            }
        }
        if (isset($param['status']) && $param['status'] != -1) {
            $where[] = ['o.order_status', '=', $param['status']];
        }
        if (isset($param['begin_time']) && isset($param['end_time']) && !empty($param['begin_time']) && !empty($param['end_time'])) {
            switch ($param['time_type']) {
                case 1:
                    $where[] = ['o.add_time', '>=', strtotime($param['begin_time'])];
                    $where[] = ['o.add_time', '<', strtotime($param['end_time']) + 86400];
                    break;
                case 2:
                    $where[] = ['o.out_time', '>=', strtotime($param['begin_time'])];
                    $where[] = ['o.out_time', '<', strtotime($param['end_time']) + 86400];
                    break;
            }
        }
        $result = $this->LifeToolsCardOrder->getOrderList($where, $limit);
        $data = $limit ? $result['data'] : $result;
        if (!empty($data)) {
            $typeMap = [
                'stadium' => '体育次卡',
                'course' => '体育次卡',
                'scenic' => '景区次卡',
                'sports' => '体育次卡'
            ];
            foreach ($data as $k => $v) {
                $data[$k]['type_txt']     = $typeMap[$v['type']] ?? '';
                $data[$k]['add_time'] = !empty($v['add_time']) ? date('Y-m-d H:i:s', $v['add_time']) : '无';
                $data[$k]['out_time'] = !empty($v['out_time']) ? date('Y-m-d H:i:s', $v['out_time']) : '无';
                $data[$k]['tools_ids']        = $this->LifeToolsCardTools->where(['card_id' => $v['card_id']])->column('tools_id');
                $data[$k]['tools_title']      = implode(',', $this->LifeTools->where([['tools_id', 'in', $data[$k]['tools_ids']]])->column('title'));
                $data[$k]['tools_sub_title']  = mb_strlen($data[$k]['tools_title'], 'utf-8') > 10 ? mb_substr($data[$k]['tools_title'], 0, 10, 'utf-8') . '...' : $data[$k]['tools_title'];
                $data[$k]['order_status_val'] = $this->LifeToolsCardOrder->getOrderStatus($v['order_status']);
                $data[$k]['use_num'] = $this->LifeToolsCardOrderRecord->getCount([['order_id', '=', $v['order_id']]]);
                $data[$k]['first_time_text'] = $v['first_time'] == 0 ? '无' : date('Y-m-d H:i:s', $v['first_time']);
            }
        }
        $result['data'] = $data;
        $result['all_num']   = $this->LifeToolsCardOrder->alias('o')->where($where)->sum('num') ?? 0;
        $result['all_price'] = $this->LifeToolsCardOrder->alias('o')->where($where)->sum('total_price') ?? 0;
        return $result;
    }

    /**
     *获取订单详情
     * @param $order_id array
     * @return array
     */
    public function getDetail($order_id) {
        $result = $this->LifeToolsCardOrder->getAllDetail(['o.order_id' => $order_id]);
        if(!$result){
            throw new \think\Exception('未查询到订单信息');
        }
        $typeMap = [
            'stadium' => '体育馆',
            'course' => '体育课程',
            'scenic' => '景区',
            'sports' => '体育'
        ];
        $result['type_name']   = $typeMap[$result['type']] ?? '';
        $result['add_time']    = !empty($result['add_time']) ? date('Y-m-d H:i:s', $result['add_time']) : '无';
        $result['add_time_text'] = $result['add_time'];
        $result['out_time']    = !empty($result['out_time']) ? date('Y-m-d H:i:s', $result['out_time']) : '无';
        $result['refund_time'] = !empty($result['refund_time']) ? date('Y-m-d H:i:s', $result['refund_time']) : '无';
        $result['tools_id']    = $this->LifeToolsCardTools->where(['card_id' => $result['card_id']])->column('tools_id');
        $result['tools_title'] = $this->LifeTools->where([['tools_id', 'in', $result['tools_id']]])->column('title');
        $result['tools_title_val']  = implode(',', $result['tools_title']);
        $result['order_status_val'] = $this->LifeToolsCardOrder->getOrderStatus($result['order_status']);
        $result['total_num']   = $result['all_num'];
        $result['today_num']   = $result['day_num'] > 0 ? $result['day_num'] - $this->LifeToolsCardOrderRecord->getCount([['order_id', '=', $result['order_id']], ['add_time', '>=', strtotime(date('Y-m-d'))]]) : '不限';
        $result['total_num']     = $result['all_num'];
        $result['all_num']     =  $result['all_num']-$this->LifeToolsCardOrderRecord->getCount([['order_id', '=', $result['order_id']]])??0;
        $result['can_back']     = in_array($result['order_status'],[20,30,40,50]) ? 1 : 0;
        $result['merchant_name'] = (new Merchant())->where('mer_id', $result['mer_id'])->value('name');
        // $result['merchant_name'] = $v['num'] - $this->LifeToolsCardOrderRecord->getCount([['order_id', '=', $v['order_id']]]) ?? 0; //总剩余次数
        $result['first_time_text'] = $result['first_time'] == 0 ? '无' : date('Y-m-d H:i:s', $result['first_time']);
        return $result;
    }

    /**
     * 购买次卡提交订单
     * @param $arr array
     */
    public function saveOrder($card_id, $userInfo) {
        $PayService = new PayService();
        $orderNo    = $PayService->createOrderNo();
        $now_time   = time();
        $cardData   = $this->LifeToolsCard->getOne(['pigcms_id' => $card_id]);
        if (!$cardData) {
            throw new \think\Exception('次卡不存在');
        }
        $cardData = $cardData->toArray();
        $buy_num  = $this->LifeToolsCardOrder->getSum('uid = '.$userInfo['uid'].' and card_id = '.$card_id.' and order_status not in (51, 60) and (out_time > '.time().' or out_time = 0)', 'num');
        if ($buy_num >= $cardData['user_num']) {
            throw new \think\Exception('一个用户有效期内限购' . $cardData['user_num'] . '张');
        }
        $date_type = array(1=>'day',2=>'month',3=>'year');
        $order = [
            'orderid'      => $orderNo,
            'card_id'      => $cardData['pigcms_id'],
            'mer_id'       => $cardData['mer_id'],
            'type'         => $cardData['type'],
            'title'        => $cardData['title'],
            'num'          => 1,
            'uid'          => $userInfo['uid'],
            'nickname'     => $userInfo['nickname'],
            'phone'        => $userInfo['phone'],
            'total_price'  => $cardData['price'],
            'order_status' => 10,
            'code'         => createRandomStr(13),
            'out_time'     => 0,//$now_time + $this->LifeToolsCard->getTermNum($cardData['term_type'], $cardData['term_num']),
            'last_time'    => $now_time,
            'add_time'     => $now_time
        ];
        Db::startTrans();
        try {
            $order_id = $this->LifeToolsCardOrder->add($order);
            $this->changeOrderStatus($order_id, 10, '订单下单成功');
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return [
            'order_type' => 'lifetoolscard',
            'order_id'   => $order_id
        ];
    }


    /**
     * 获取用户端订单详情
     * @param $order_id array
     * @return array
     */
    public function getUserDetail($param) {
        $orderId = $param['order_id'] ?? 0;
        $uid     = $param['uid'] ?? 0;
        if(empty($orderId) || empty($uid)){
            throw new \think\Exception('缺少参数', 1001);
        }
        $result = $this->getDetail($orderId);
        if(empty($result)){
            throw new \think\Exception('订单不存在', 1001);
        }
        $result['image'] = replace_file_domain($result['image']);
        require_once '../extend/phpqrcode/phpqrcode.php';
        $result['code_img']  = $this->getQrCode($result['code']);
        $result['code_img1'] = $this->getBarCode($result['code']);
        $payInfo = [];// 获得支付信息
        if ($result['orderid']) {
            $payInfo = (new PayService())->getPayOrderData([$result['orderid']]);
            $payInfo = $payInfo[$result['orderid']] ?? [];
            $payInfo['pay_type_chanel'] = '';
            if (isset($payInfo['pay_type'])) {
                $payInfo['pay_type_chanel'] = ($payInfo['pay_type'] ? $payInfo['pay_type_txt'] : '') . ($payInfo['channel'] ? '(' . $payInfo['channel_txt'] . ')' : '');
            }
        }
        $result['pay_info'] = $payInfo;
        $resultArr['order'] = $result;

        // 订单详情展示的操作按钮
        $resultArr['button'] = [
            'pay_btn'    => 0, //是否显示去支付按钮,0=否1=是
            'refund_btn' => 0, //是否显示申请退款按钮,0=否1=是
            'revoke_btn' => 0, //是否显示撤销申请按钮,0=否1=是
            'again_btn'  => 0, //是否显示再来一单按钮,0=否1=是
        ];
        switch ($result['order_status']) {
            case 10;// 未支付

                // 计算自动取消时间
                $resultArr['order']['countdown'] = max(0,1800 - (time() - strtotime($result['add_time'])));
                if ($resultArr['order']['countdown']) {
                    $resultArr['button']['pay_btn'] = 1;
                } else {
                    $this->changeOrderStatus($result['order_id'], 60, '超时自动取消');
                }
                break;
            case 20; //已付款
                if ($result['total_price'] > 0) {
                    $resultArr['button']['refund_btn'] = 1;
                }
                break;
            case 30;//已完成首次核销
            case 40;//已完成
                $resultArr['button']['again_btn'] = 1;
                break;
            case 50;//申请退款中
                $resultArr['button']['revoke_btn'] = 1;
                break;
        }
        return $resultArr;
    }

    /**
     * 次卡申请退款
     * @param $param array
     */
    public function supplyRefund($param) {
        if (empty($param['order_id']) || empty($param['reason'])) {
            throw new \think\Exception('参数缺失');
        }
        $orderDetail = $this->getDetail($param['order_id']);
        if (empty($orderDetail) || $orderDetail['order_status'] != 20) {
            throw new \think\Exception('该订单状态不支持申请退款');
        }
        $this->changeOrderStatus($param['order_id'], 50, '用户申请退款');
        $refund = [
            'reply_refund_reason' => $param['reason'],
            'reply_refund_time'   => time(),
            'refund_money'        => $orderDetail['total_price']
        ];
        $this->LifeToolsCardOrder->updateThis(['order_id' => $param['order_id']], $refund);
        return true;
    }

    /**
     * 次卡直接退款
     * @param $param array
     */
    public function CardOrderBack($param,$admin) {
        if (empty($param['order_id']) || empty($param['reason'])) {
            throw new \think\Exception('参数缺失');
        }
        $orderDetail = $this->getDetail($param['order_id']);
        if (empty($orderDetail) || $orderDetail['order_status'] == 51) {
            throw new \think\Exception('该订单已经退款');
        }
        $refund = [
            'reply_refund_reason' => $param['reason'],
            'reply_refund_time'   => time(),
            'refund_money'        => $orderDetail['total_price']
        ];
        $this->LifeToolsCardOrder->updateThis(['order_id' => $param['order_id']], $refund);
        $this->changeOrderStatus($param['order_id'], 51, $param['reason']);
        return true;
    }

    /**
     * 次卡撤销申请
     * @param $param array
     */
    public function revokeRefund($order_id) {
        if (empty($order_id)) {
            throw new \think\Exception('参数缺失');
        }
        $orderDetail = $this->getDetail($order_id);
        if (empty($orderDetail) || $orderDetail['order_status'] != 50) {
            throw new \think\Exception('该订单状态不支持撤销申请');
        }
        $this->changeOrderStatus($order_id, 20, '用户撤销申请');
        $refund = [
            'reply_refund_reason' => '',
            'reply_refund_time'   => 0,
            'refund_money'        => 0
        ];
        $this->LifeToolsCardOrder->updateThis(['order_id' => $order_id], $refund);
        return true;
    }

    /**
     * 同意退款
     * @param $order_ids array
     */
    public function agreeRefund($order_ids) {
        foreach ($order_ids as $order_id) {
            $this->changeOrderStatus($order_id, 51, '商家同意退款');
        }
        return true;
    }

    /**
     * 拒绝退款
     * @param $order_ids array
     */
    public function refuseRefund($order_ids, $reason = '') {
        foreach ($order_ids as $order_id) {
            $where  = ['order_id' => $order_id];
            $update = [
                'refund_refuse_reason' => $reason,
                'refund_refuse_time'   => time(),
                'refund_money' => 0,
                'refund_time'  => 0
            ];
            $this->LifeToolsCardOrder->updateThis($where, $update);
            $this->changeOrderStatus($order_id, 20, '商家拒绝退款');
        }
        return true;
    }

    /**
     * 核销成功之后逻辑
     */
    public function afterVerify($order_id) {
        if (empty($order_id)) {
            throw new \think\Exception('参数缺失');
        }
        $orderDetail = $this->LifeToolsCardOrder->getAllDetail(['o.order_id' => $order_id]);
        $status = 0;
        if ($orderDetail['all_num'] <= $this->LifeToolsCardOrderRecord->getCount([['order_id', '=', $order_id]])) {
            $status = 40;
        } else if ($orderDetail['order_status'] == 20) {
            $status = 30;
        }
        $status && $this->changeOrderStatus($order_id, $status, '核销次卡');
        return true;
    }

    /**
     * 统一的修改订单状态（所有修改订单状态的地方必须调用这个方法）
     * 方便统一处理
     */
    public function changeOrderStatus($order_id, $status, $note)
    {
        $orderDetail = $this->LifeToolsCardOrder->getAllDetail(['o.order_id' => $order_id]);
        if (empty($orderDetail)) return false;
        Db::startTrans();
        try {
            $where  = ['order_id' => $order_id];
            $update = [
                'order_status' => $status,
                'last_time'    => time()
            ];
            if ($status == 30) {
                $update['verify_time'] = time();
            }
            if ($status == 51) {
                $update['refund_time'] = time();
            }
            $res = $this->LifeToolsCardOrder->updateThis($where, $update);
            if ($res !== false) {
                $res = true;
                //逻辑业务处理
                switch ($status) {
                    case 10://下单
                        $saveData = [
                            'mer_id'      => $orderDetail['mer_id'],
                            'price'       => $orderDetail['total_price'],
                            'total_price' => $orderDetail['total_price'],
                            'keywords'    => $orderDetail['title']
                        ];
                        (new SystemOrderService)->saveOrder('lifetoolscard', $order_id, $orderDetail['uid'], $saveData);
                        $this->LifeToolsCard->setInc(['pigcms_id' => $orderDetail['card_id']], 'sale_count', $orderDetail['num']); //增加销量
                        break;
                    case 20://付款
                        (new SystemOrderService)->paidOrder('lifetoolscard', $order_id);
                        $title = '次卡购买成功';
                        $msg = '恭喜您在' . date('Y-m-d H:i:s') . '成功购买了' . $orderDetail['title'];
                        $this->LifeToolsMessage->add([
                            'tools_id' => 0,
                            'type'     => 'scenic',
                            'uid'      => $orderDetail['uid'],
                            'order_id' => $order_id,
                            'title'    => $title,
                            'content'  => $msg,
                            'add_time' => time()
                        ]);
                        break;
                    case 30://初次核销
                        break;
                    case 40://已完成
                    case 70://已付款已过期
                        (new SystemOrderService)->commentOrder('lifetoolscard', $order_id);
                        if ($status == 70) {
                            $title = '次卡已过期';
                            $msg = $orderDetail['title'] . '在于' . date('Y-m-d H:i:s') . '过期';
                            $this->LifeToolsMessage->add([
                                'tools_id' => 0,
                                'type'     => 'scenic',
                                'uid'      => $orderDetail['uid'],
                                'order_id' => $order_id,
                                'title'    => $title,
                                'content'  => $msg,
                                'add_time' => time()
                            ]);
                        }
                        $this->merchantAddMoney($order_id);
                        break;
                    case 50://退款申请中
                        (new SystemOrderService)->refundingOrder('lifetoolscard', $order_id);
                        break;
                    case 51://已退款
                    case 60://未付款已过期
                        if ($orderDetail['order_status'] <= 10) {
                            (new SystemOrderService)->cancelOrder('lifetoolscard', $order_id);
                        } else {
                            if ($orderDetail['refund_money'] <= 0) throw new \think\Exception('退款金额错误，请检查');
                            (new SystemOrderService)->refundOrder('lifetoolscard', $order_id);
                        }
                        $this->LifeToolsCard->setDec(['pigcms_id' => $orderDetail['card_id']], 'sale_count', $orderDetail['num']); //减少销量
                        $this->refund($order_id);
                        break;
                    default:
                        # code...
                        break;
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return $res;
    }

    /**
     * 退款
     * @param  [type] $order_id     订单ID
     * @return [type]               [description]
     */
    public function refund($order_id) {
        $order = $this->LifeToolsCardOrder->getAllDetail(['o.order_id' => $order_id]);
        if($order['paid'] != 1) { //未支付
            return true;
        }
        $refund_money = $order['refund_money'];
        $alias_name   = '景区次卡';
        try {
            //退款顺序  商家会员卡余额-商家会员卡赠送余额-平台余额-在线支付
            //退商家会员卡余额
            $refund_merchant_card = $order['merchant_balance_pay'];
            $_money1 = 0;
            if ($refund_merchant_card > 0) {
                $_money1 = $refund_money >= $refund_merchant_card ? $refund_merchant_card : $refund_money;
                $result  = (new CardNewService())->addUserMoney($order['mer_id'], $order['uid'], $_money1, 0, 0, '', $alias_name . '订单退款,增加余额,订单编号' . $order['orderid']);
                if ($result['error_code']) {
                    throw new \think\Exception($result['msg']);
                }
                $refund_money -= $_money1;
            }
            if($refund_money <= 0) return true;

            //商家会员卡赠送余额
            $refund_merchant_card_give = ($order['merchant_balance_give'] + $order['merchant_balance_pay']) - $_money1;
            $_money2 = 0;
            if ($refund_merchant_card_give > 0) {
                $_money2 = $refund_money >= $refund_merchant_card_give ? $refund_merchant_card_give : $refund_money;
                $result  = (new CardNewService())->addUserMoney($order['mer_id'], $order['uid'], 0, $_money2, 0, '', $alias_name . '订单退款,增加余额,订单编号' . $order['orderid']);
                if ($result['error_code']) {
                    throw new \think\Exception($result['msg']);
                }
                $refund_money -= $_money2;
            }
            if($refund_money <= 0) return true;

            //平台余额
            $refund_system_balance = ($order['system_balance'] + $order['merchant_balance_give'] + $order['merchant_balance_pay']) - ($_money1 + $_money2);
            $_money4 = 0;
            if ($refund_system_balance > 0) {
                $_money4 = $refund_money >= $refund_system_balance ? $refund_system_balance : $refund_money;
                $result  = (new UserService())->addMoney($order['uid'], $_money4, $alias_name . '订单退款,增加余额,订单编号' . $order['orderid']);
                if ($result['error_code']) {
                    throw new \think\Exception($result['msg']);
                }
                $refund_money -= $_money4;
            }
            if($refund_money <= 0) return true;

            //在线支付退款
            $refund_online_pay = ($order['pay_money'] + $order['system_balance'] + $order['merchant_balance_give'] + $order['merchant_balance_pay']) - ($_money1 + $_money2 + $_money4);
            if ($refund_online_pay > 0) {
                $PayService = new PayService();
                $_money5 = $refund_money >= $refund_online_pay ? $refund_online_pay : $refund_money;
                if ($order['orderid']) {
                    $end_order = $PayService->getPayOrderInfo($order['orderid']);
                    if ($end_order['paid'] == '1' && $end_order['paid_money'] > 0) {
                        $end_money = ($end_order['paid_money'] - $end_order['refund_money']) / 100;
                        if ($end_money > 0) {
                            $end_refund_money = $end_money >= $_money5 ? $_money5 : $end_money;
                            $PayService->refund($order['orderid'], $end_refund_money);
                        }
                    }
                }
                $refund_money -= $_money5;
            }
            if ($refund_money <= 0) {
                return true;
            }
            throw new \think\Exception('退款金额计算溢出了，请检查');
        } catch(\Exception $e) {
            throw new \think\Exception($e->getMessage(), 1005);
        }
    }

    /**
     * 订单完成增加商家余额
     * @param $orderId
     */
    public function merchantAddMoney($orderId)
    {
        try {
            $nowOrder = $this->LifeToolsCardOrder->getAllDetail(['o.order_id' => $orderId]);
            if (!$nowOrder) {
                throw new \Exception('订单不存在');
            }
            $money_real = $nowOrder['total_price'];
            //当前商家应该入账的金额 = 实际金额（除去优惠的金额） - 商家会员卡赠送余额付了多少钱 - 商家会员卡余额付了多少钱 - 运费 - 已成功退款金额
            $bill_money = $money_real- $nowOrder['merchant_balance_pay'] - $nowOrder['merchant_balance_give'] - $nowOrder['refund_money'];

            $money_system_take = $bill_money;//平台抽成基数
            $orderInfo = [];
            $orderInfo['pay_order_id'] = [$nowOrder['orderid']];//支付单号
            $orderInfo['total_money'] = $nowOrder['total_price'];//当前订单总金额
            $orderInfo['bill_money'] = $bill_money;
            $orderInfo['balance_pay'] = $nowOrder['system_balance'];//平台余额支付金额
            $orderInfo['merchant_balance'] = $nowOrder['merchant_balance_pay'];//商家会员卡支付金额
            $orderInfo['card_give_money'] = $nowOrder['merchant_balance_give'];//商家会员卡赠送支付金额
            $orderInfo['payment_money'] = $nowOrder['pay_money'];//在线支付金额（不包含自有支付）
            $orderInfo['score_deducte'] = 0;//积分支付金额
            $orderInfo['order_from'] = 1;
            $orderInfo['order_type'] = 'lifetoolscard';
            $orderInfo['num'] = $nowOrder['num'];//数量
            $orderInfo['store_id'] = 0;
            $orderInfo['mer_id'] = $nowOrder['mer_id'];
            $orderInfo['order_id'] = $nowOrder['order_id'];
            $orderInfo['group_id'] = '0';
            $orderInfo['real_orderid'] = $nowOrder['orderid'];//订单编号
            $orderInfo['union_mer_id'] = '0';//商家联盟id
            $orderInfo['uid'] = $nowOrder['uid'];//用户ID
            $orderInfo['desc'] = '用户在 ' . $nowOrder['title'] . ' 中消费' . $nowOrder['total_price'] . '元记入收入';
            $orderInfo['is_own'] = $orderInfo['is_own'] = (new PayOrderInfo())->where([
                    'business' => 'lifetoolscard',
                    'business_order_id' => $orderId,
                    'orderid' => $nowOrder['orderid']
                ])->value('is_own') ?? 0;//自有支付类型;//自有支付类型
            $orderInfo['own_pay_money'] = '0';//自有支付在线支付金额
            $orderInfo['pay_for_system'] = 0;
            $orderInfo['pay_for_store'] = '0';
            $orderInfo['score_used_count'] = '';
            $orderInfo['score_discount_type'] = '';
            $orderInfo['money_system_take'] = $money_system_take;
            $orderInfo['discount_detail'] = '';
            $orderInfo['system_coupon_plat_money'] = '';
            $orderInfo['system_coupon_merchant_money'] = '';
            $orderInfo['extra_price'] = 0;
            $merchantMoneyListService = new MerchantMoneyListService();
            $merchantMoneyListService->addMoney($orderInfo);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 获取条形码
     */
    private function getBarCode($code)
    {
        $generator   = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $base64Image = base64_encode($generator->getBarcode($code, $generator::TYPE_CODE_128, 1, 1));
        $path = request()->server('DOCUMENT_ROOT').'/runtime/life_tools/Barcode/';
        if(!is_dir($path)){
            mkdir($path, 0777, true);
        }
        base64_to_img($path, $code, $base64Image, 'png');
        return cfg('site_url').'/runtime/life_tools/Barcode/'.$code.'.png';
    }

    /**
     * 获取二维码
     */
    private function getQrCode($code)
    {
        $date   = date('Y-m-d');
        $time   = date('Hi');
        $qrcode = new \QRcode();
        $errorLevel = "L";
        $size = "9";
        $dir  = '../../runtime/qrcode/employee/'.$date. '/' .$time;
        if(!is_dir($dir)){
            mkdir($dir, 0777, true);
        }
        $filename_url = '../../runtime/qrcode/employee/'.$date.'/'.$time . '/' . $code.'.png';
        $qrcode->png($code, $filename_url, $errorLevel, $size);
        $QR = 'runtime/qrcode/employee/'.$date.'/'.$time . '/' . $code.'.png';      //已经生成的原始二维码图片文件
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        return $http_type.$_SERVER["HTTP_HOST"].'/'.$QR;
    }

    /**************************************以下为对接支付中心的方法*********************************/

    /**
     * 获取订单信息
     * $order_id  订单表的主键ID
     */
    public function getOrderPayInfo($order_id) {
        $orderInfo = $this->LifeToolsCardOrder->getAllDetail(['o.order_id' => $order_id]);
        if (empty($orderInfo)) throw new \think\Exception("没有找到订单");
        $orderInfo['timeout']      = ($orderInfo['add_time'] + 30 * 60) > time() ? 0 : 1; //1=已过期,0=未过期
        $orderInfo['time_surplus'] = $orderInfo['timeout'] == 0 ? ($orderInfo['add_time'] + 30 * 60) - time() : 0; //有效时间
        if ($orderInfo['paid'] != 1 && $orderInfo['timeout'] == 1) { //超时未支付
            $this->changeOrderStatus($order_id, 60, '超时未支付（支付中心）');
        }
        $return = [
            'paid'        => $orderInfo['paid'],
            'is_cancel'   => $orderInfo['timeout'],
            'mer_id'      => $orderInfo['mer_id'],
            'city_id'     => 0,
            'store_id'    => 0,
            'uid'         => $orderInfo['uid'],
            'order_no'    => $orderInfo['orderid'],
            'title'       => '景区次卡订单',
            'order_money' => get_format_number($orderInfo['total_price'] - $orderInfo['system_balance'] - $orderInfo['merchant_balance_pay'] - $orderInfo['merchant_balance_give']),
            'time_remaining'        => $orderInfo['time_surplus'],
            'merchant_balance_open' => true,
            'business_order_sn' => $orderInfo['orderid'],
        ];
        return $return;
    }

    /**
     * 获取支付结果页地址
     * @param  [type]  $order_id  [description]
     * @param  integer $is_cancel 1=已取消  0=未取消
     */
    public function getPayResultUrl($order_id, $is_cancel = 0) {
        $orderInfo = $this->LifeToolsCardOrder->getAllDetail(['o.order_id' => $order_id]);
        return ['redirect_url' => get_base_url('pages/lifeTools/pocket/timeCardDetail?order_id=' . $order_id), 'direct' => 0]; //前端提供跳转地址
    }
    
    /**
     * 支付成功逻辑
     * $order_id  订单表的主键ID
     */
    public function afterPay($order_id, $pay_info = []) {
        $orderInfo = $this->LifeToolsCardOrder->getAllDetail(['o.order_id' => $order_id]);
        if(empty($orderInfo)) throw new \think\Exception("没有找到订单");
        $alias_name   = '景区次卡';
        $order_record = [
            'current_score_use'             => 0,
            'current_score_deducte'         => 0,
            'current_system_balance'        => 0,
            'current_merchant_balance'      => 0,
            'current_merchant_give_balance' => 0,
            'current_qiye_balance'          => 0,
            'money_online_pay'              => 0
        ];
        $UserService = new UserService();
        if ($orderInfo['uid']) {
            $now_user = $UserService->getUser($orderInfo['uid']);
        }
        $MerchantCardService = new MerchantCardService();
        $CardNewService      = new CardNewService();
        //扣除使用商家会员卡余额
        if ($pay_info['current_merchant_balance'] > 0) {
            $current_card = $MerchantCardService->getUserCard($orderInfo['uid'], $orderInfo['mer_id']);
            if ($current_card && $current_card['card_money'] < $pay_info['current_merchant_balance']) {
                throw new \think\Exception("商家会员卡余额不足！");
            }
            $desc = "购买 " . $alias_name . "商品 扣除会员卡余额，订单编号" . $orderInfo['orderid'];
            $use_result = $CardNewService->useMoney($orderInfo['mer_id'], $orderInfo['uid'], $pay_info['current_merchant_balance'], $desc);
            if ($use_result['error_code']) {
                throw new \think\Exception($use_result['error_code']);
            }
            $order_record['current_merchant_balance'] = $pay_info['current_merchant_balance'];
        }
        //扣除使用商家会员卡赠送余额
        if ($pay_info['current_merchant_give_balance'] > 0) {
            $current_card = $MerchantCardService->getUserCard($orderInfo['uid'], $orderInfo['mer_id']);
            if ($current_card && $current_card['card_money_give'] < $pay_info['current_merchant_give_balance']) {
                throw new \think\Exception("商家会员卡赠送余额不足！");
            }
            $desc = "购买 " . $alias_name . "商品 扣除会员卡赠送余额，订单编号" . $orderInfo['orderid'];
            $use_result = $CardNewService->useGiveMoney($orderInfo['mer_id'], $orderInfo['uid'], $pay_info['current_merchant_give_balance'], $desc);
            if ($use_result['error_code']) {
                throw new \think\Exception($use_result['error_code']);
            }
            $order_record['current_merchant_give_balance'] = $pay_info['current_merchant_give_balance'];
        }
        //扣除使用平台余额
        if ($pay_info['current_system_balance'] > 0) {
            if ($now_user['now_money'] < $pay_info['current_system_balance']) {
                throw new \think\Exception("用户余额不足！");
            }
            $desc = "购买" . $alias_name . "商品 ，扣除余额 ，订单编号" . $orderInfo['orderid'];
            $use_result = $UserService->userMoney($now_user['uid'], $pay_info['current_system_balance'], $desc);
            if ($use_result['error_code']) {
                throw new \think\Exception($use_result['error_code']);
            }
            $order_record['current_system_balance'] = $pay_info['current_system_balance'];
        }
        //在线支付金额
        if ($pay_info['paid_money'] > 0) {
            $order_record['money_online_pay'] = $pay_info['paid_money'];
        }
        $order_status = 20;
        $status_note  = '订单支付成功';
        $save_data    = [
            'system_balance'        => $order_record['current_system_balance'],
            'merchant_balance_pay'  => $order_record['current_merchant_balance'],
            'merchant_balance_give' => $order_record['current_merchant_give_balance'],
            'pay_type'  => $pay_info['paid_type'],
            'pay_money' => $order_record['money_online_pay'],
            'orderid'   => $pay_info['paid_orderid'],
            'paid'      => 1,
            'pay_time'  => time(),
            'last_time' => time()
        ];
        $this->LifeToolsCardOrder->updateThis(['order_id' => $orderInfo['order_id']], $save_data);
        $this->changeOrderStatus($orderInfo['order_id'], $order_status, $status_note);
    }

     /**
     * 核销列表
     */
    public function verifyList($param)
    {
//         $condition = [];
// //        $condition[] = ['o.order_status', 'between', [30, 40]];
//         if(!empty($params['mer_id'])){
//             $condition[] = ['o.mer_id', '=', $params['mer_id']];
//         }
//         if(!empty($params['staff_id'])){
//             $condition[] = ['od.staff_id', '=', $params['staff_id']];
//         }
//         if(!empty($params['tools_type'])){
//             $condition[] = ['o.type', '=', $params['tools_type']];
//         }

//         if(!empty($params['keywords'])){
//             switch ($params['search_by']) {
//                 case 1: //订单号
//                     $condition[] = ['o.orderid', 'like', "%{$params['keywords']}%"];
//                     break;
//                 case 2: //活动名称
//                     $condition[] = ['c.title', 'like', "%{$params['keywords']}%"];
//                     break;
//                 case 3: //手机号
//                     $condition[] = ['o.phone', 'like', "%{$params['keywords']}%"];
//                     break;
//                 case 4: //景区名称
//                     $condition[] = ['t.title', 'like', "%{$params['keywords']}%"];
//                     break; 
//             } 
//         }

//         if(!empty($params['start_date']) && !empty($params['end_date'])){
//             switch ($params['date_by']) {
//                 case 1: //下单日期
//                     $condition[] = ['o.add_time', 'BETWEEN', [strtotime($params['start_date'] . ' 00:00:00'), strtotime($params['end_date'] . ' 23:59:59')]];
//                     break;
//                 case 2: //核销日期
//                     $condition[] = ['o.refund_time', 'BETWEEN', [strtotime($params['start_date'] . ' 00:00:00'), strtotime($params['end_date'] . ' 23:59:59')]];
//                     break;
//             }
//             $condition[] = ['a.type', '=', $params['tools_type']];
//         }

//         $field = 'o.*,od.add_time as verify_time';
//         return $this->LifeToolsCardOrder->getVerifyList($condition, $params['page_size'], $field);



        $limit = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['page_size'] ?? 10
        ];
        $where = [];
        $where[] = ['r.staff_id', '=', $param['staff_id']];
        if (!empty($param['mer_id'])) {
            $where[] = ['r.mer_id', '=', $param['mer_id']];
        }
        if (!empty($param['keywords'])) {
            switch ($param['search_by']) {
                case 1:
                    $where[] = ['c.title', 'like', "%{$param['keywords']}%"];
                    break;
                case 2:
                    $where[] = ['o.nickname', 'like', "%{$param['keywords']}%"];
                    break;
                case 3:
                    $where[] = ['o.phone', 'like', "%{$param['keywords']}%"];
                    break;
            }
        }
        if (!empty($param['start_date']) && !empty($param['end_date'])) {
            $where[] = ['r.add_time', '>=', strtotime($param['start_date'])];
            $where[] = ['r.add_time', '<', strtotime($param['end_date']) + 86400];
        }
        if (!empty($param['staffId'])) {
            $where[] = ['r.staff_id', '=', $param['staffId']];
        }
        if(!empty($param['order_id'])){
            $where[] = ['o.order_id', '=', $param['order_id']];
        }
        $result = $this->LifeToolsCardOrderRecord->getList($where, $limit);
        if (!empty($result['data'])) {
            foreach ($result['data'] as $k => $v) {
                $result['data'][$k]['add_time'] = !empty($v['add_time']) ? date('Y-m-d H:i:s', $v['add_time']) : '无';
            }
        }
        return $result;


    }

    /**
     * 订单列表
     */
    public function orderList($params)
    {
        $condition = [];
//        $condition[] = ['o.order_status', 'between', [30, 40]];
        if(!empty($params['mer_id'])){
            $condition[] = ['o.mer_id', '=', $params['mer_id']];
        }
        if(!empty($params['staff_id'])){
            $condition[] = ['od.staff_id', '=', $params['staff_id']];
        }
        if(!empty($params['tools_type'])){
            $condition[] = ['o.type', '=', $params['tools_type']];
        }

        if(!empty($params['keywords'])){
            switch ($params['search_by']) {
                case 1: //订单号
                    $condition[] = ['o.orderid', 'like', "%{$params['keywords']}%"];
                    break;
                case 2: //活动名称
                    $condition[] = ['c.title', 'like', "%{$params['keywords']}%"];
                    break;
                case 3: //手机号
                    $condition[] = ['o.phone', 'like', "%{$params['keywords']}%"];
                    break;
                case 4: //景区名称
                    $condition[] = ['t.title', 'like', "%{$params['keywords']}%"];
                    break; 
            } 
        }

        if(!empty($params['start_date']) && !empty($params['end_date'])){
            switch ($params['date_by']) {
                case 1: //下单日期
                    $condition[] = ['o.add_time', 'BETWEEN', [strtotime($params['start_date'] . ' 00:00:00'), strtotime($params['end_date'] . ' 23:59:59')]];
                    break;
                case 2: //核销日期
                    $condition[] = ['o.refund_time', 'BETWEEN', [strtotime($params['start_date'] . ' 00:00:00'), strtotime($params['end_date'] . ' 23:59:59')]];
                    break;
            }
            $condition[] = ['a.type', '=', $params['tools_type']];
        }

        $field = 'o.*,od.add_time as verify_time';
        return $this->LifeToolsCardOrder->getVerifyList($condition, $params['page_size'], $field);
    }

    public function getVerifyList($params)
    {
        $condition = [];
        //        $condition[] = ['o.order_status', 'between', [30, 40]];
            if(!empty($params['mer_id'])){
                $condition[] = ['o.mer_id', '=', $params['mer_id']];
            }
            if(!empty($params['staff_id'])){
                if($params['staffUser']['type'] != 2){
                    $condition[] = ['r.staff_id', '=', $params['staff_id']];
                }
            }
            if(!empty($params['tools_type'])){
                if($params['tools_type'] == 'sports'){
                    $condition[] = ['o.type', 'exp', Db::raw('= "stadium" OR o.type = "course" OR o.type = "sports"')];
                }else{
                    $condition[] = ['o.type', '=', $params['tools_type']];
                }
            }
    
            if(!empty($params['keywords'])){
                switch ($params['search_by']) {
                    case 1: //订单号
                        $condition[] = ['o.orderid', 'like', "%{$params['keywords']}%"];
                        break;
                    case 2: //活动名称
                        $condition[] = ['c.title', 'like', "%{$params['keywords']}%"];
                        break;
                    case 3: //手机号
                        $condition[] = ['o.phone', 'like', "%{$params['keywords']}%"];
                        break;
                    case 4: //景区名称
                        $condition[] = ['t.title', 'like', "%{$params['keywords']}%"];
                        break; 
                } 
            }
    
            if(!empty($params['start_date']) && !empty($params['end_date'])){
                switch ($params['date_by']) {
                    case 1: //下单日期
                        $condition[] = ['o.add_time', 'BETWEEN', [strtotime($params['start_date'] . ' 00:00:00'), strtotime($params['end_date'] . ' 23:59:59')]];
                        break;
                    case 2: //核销日期
                        $condition[] = ['o.refund_time', 'BETWEEN', [strtotime($params['start_date'] . ' 00:00:00'), strtotime($params['end_date'] . ' 23:59:59')]];
                        break;
                }
                $condition[] = ['a.type', '=', $params['tools_type']];
            }
    
            $field = 'o.*,o.add_time as order_time,r.add_time as verify_time,r.staff_name';
            $data = $this->LifeToolsCardOrderRecord->getVerifyList($condition, $params['page_size'], $field);
            $data->each(function($item, $key){
                $item->add_time_text = date('Y-m-d H:i:s', $item->order_time);
                $item->verify_time_text = date('Y-m-d H:i:s', $item->verify_time);
                $item->card_title = $item->title;
            });
            return $data;
    }

    /**
     * 导出订单
     * @author Nd
     * @date 2022/5/13
     */
    public function orderExport($params)
    {
        $csvHead = array(
            L_('订单号'),
            L_('次卡名称'),
            L_('景区名称'),
            L_('用户呢称'),
            L_('用户手机号'),
            L_('数量'),
            L_('总价'),
            L_('订单状态'),
            L_('过期时间'),
            L_('下单时间'),
        );

        $params['select_all'] = true;
        $data = $this->getList($params);

        $csvData = [];

        if (!empty($data['data'])) {
            foreach ($data['data'] as $key => $value) {
                $csvData[$key] = [
                    $value['orderid'] . "\t",
                    $value['title'],
                    $value['tools_title'],
                    $value['nickname'],
                    $value['phone'],
                    $value['num'],
                    $value['total_price'],
                    $value['order_status_val'],
                    $value['out_time'],
                    $value['add_time'],
                ];
            }
        }
        $filename = date("Y-m-d", time()) . '-' . $params['rand_number'] . '.csv';
        (new BaseExportService())->putCsv($filename, $csvData, $csvHead);

        $downFileUrl = cfg('site_url').'/v20/runtime/' . $filename;
        return ['file_url' => $downFileUrl];
    }

    /**
     * 导出统计
     * @author Nd
     * @date 2022/5/13
     * @param $params
     * @return string[]
     */
    public function dataExport($params)
    {
        $tools_type = $params['type'] == 'scenic' ? 'scenic' : 'sports';
        $csvHead = array(
            L_('日期'),
            L_('订单数量'),
            L_('订单金额'),
            L_('退款金额')
        );

        $data = $this->LifeToolsCardOrder->getExportData($params['export_type'], $tools_type);
        // return $data;
        $csvData = [];
        $total = [];
        $total['dates'] = '本页总计';
        $total['total_order'] = 0;
        $total['total_money'] = 0;
        $total['refund_money'] = 0;
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $csvData[$key] = [
                    ' ' . $value['dates'] . ' ',
                    $value['total_order'],
                    $value['total_money'],
                    $value['refund_money']
                ];
                $total['total_order'] += $value['total_order'];
                $total['total_money'] += $value['total_money'];
                $total['refund_money'] += $value['refund_money'];
            }
        }
        $csvData[] = $total;
        $filename = date("Y-m-d", time()) . '-' . $params['rand_number'] . '.csv';
        (new BaseExportService())->putCsv($filename, $csvData, $csvHead);

        $downFileUrl = cfg('site_url').'/v20/runtime/' . $filename;
        return ['file_url' => $downFileUrl];
    }

    /**
     * 删除次卡
     */
    public function delCard($params)
    {
        $condition = [];
        $condition[] = ['order_id', '=', $params['order_id']];
        $condition[] = ['uid', '=', $params['uid']];
        $order = $this->LifeToolsCardOrder->where($condition)->find();
        if(!$order){
            throw new \think\Exception('订单不存在！');
        }
        $order->is_show = 0;
        $order->last_time = time();
        return $order->save();
    }

    public function cardVerifyList($param){
        $where[] = ['od.staff_id', '=', $param['staff_id']];
        $where[] = ['o.type', '=', 'scenic'];
        if ($param['stime'] && $param['etime'] ) {
            $where[] = ['od.add_time', '>=', strtotime($param['stime'] . ' 00:00:00')];
            $where[] = ['od.add_time', '<=', strtotime($param['etime'] . ' 23:59:59')];
        }
        switch ($param['ftype']) {
            case 'oid': //订单id
                $param['fvalue'] && $where[] = array('o.orderid', 'like', '%' . $param['fvalue'] . '%');
                break;
            case 'xm':  //次卡名称
                $param['fvalue'] && $where[] = array('c.title', 'like', '%' . $param['fvalue'] . '%');
                break;
            case 'dh':  //景区名称
                $param['fvalue'] && $where[] = array('t.title', 'like', '%' . $param['fvalue'] . '%');
                break;
            default:
                $param['fvalue'] && $where[] = array('o.orderid', 'like', '%' . $param['fvalue'] . '%');
                break;
        }
        $verifyList = $this->LifeToolsCardOrder->getCardVerifyList($where,$param['page_size']);
        $card_list = $verifyList->toArray();
        foreach ($verifyList as $k=>$v){
            $card_list['data'][$k] = [
                'address' => '',
                'last_time' => $v?date('Y-m-d :H:i:s',$v['verify_time']):'',
                'order_from' => '次卡',
                'real_orderid' => $v['orderid'],
                'order_id' => $v['order_id'],
                'price' => $v['total_price'],
                'ticket_title' => $v['title'],
                'title' => $v['title'],
                'type' => 'card',
                'username' => $v['nickname'],
                'userphone' => $v['phone'],
                'all_num' => $v['all_num']-$this->LifeToolsCardOrderRecord->getCount([['order_id', '=', $v['order_id']]])??0
            ];
        }
        return $card_list;
    }
}