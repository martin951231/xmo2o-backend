<?php

namespace app\common\model\service;

use app\common\model\db\MembershipCardOrder;
use app\common\model\db\MembershipCardOrderDeliver;
use app\common\model\db\MembershipCardUserOpenRecord;
use app\common\model\db\SystemCouponHadpull;
use think\Exception;

/**
 * 平台会员卡订单
 * @package app\common\model\service
 */
class MembershipCardOrderService
{
    public $membershipCardOrderMod = null;

    public $membershipCardUserOpenRecordMod = null;

    public function __construct()
    {
        $this->membershipCardOrderMod = new MembershipCardOrder();
        $this->membershipCardUserOpenRecordMod = new MembershipCardUserOpenRecord();
    }

    /**
     * 保存订单
     * @author: 张涛
     * @date: 2020/12/22
     */
    public function saveOrder($uid)
    {
        $cardInfo = (new MembershipCardService())->getOne(['status' => 1]);
        if (empty($cardInfo)) {
            throw new Exception('平台会员卡未开启');
        }
        $order = [
            'order_sn' => build_real_orderid($uid),
            'order_name' => $cardInfo['card_name'],
            'paid' => 0,
            'uid' => $uid,
            'price' => $cardInfo['price'],
            'create_time' => time()
        ];
        $orderId = $this->membershipCardOrderMod->insertGetId($order);

        $saveData = [
            'store_id' => 0,
            'real_orderid' => $order['order_sn'],
            'system_status' => 0
        ];
        (new \app\common\model\service\order\SystemOrderService())->saveOrder('membership_card', $orderId, $uid, $saveData);
        return $orderId;
    }

    /**
     * 我的订单
     * @author: 张涛
     * @date: 2020/12/22
     */
    public function myOrder($uid, $ext = [])
    {
        $records = $this->membershipCardUserOpenRecordMod->where(['uid' => $uid])->order('id', 'desc')->page($ext['page'] ?? 0, $ext['pageSize'] ?? 0)->select()->toArray();
        
        $lists = [];
        $tm = time();
        $couponPullMod = new SystemCouponHadpull();
        $deliverMod = new MembershipCardOrderDeliver();
        foreach ($records as $o) {
            $item = [
                'id' => $o['id'],
                'card_name' =>  $o['card_name'],
                'price' => get_format_number($o['price']),
                'purchase_date' => date('Y.m.d H:i', $o['create_time']),
                'expire_date' => date('Y.m.d H:i', $o['expire_time']),
                'status_txt' => $o['expire_time'] > $tm ? '使用中' : '已过期',
                'status' => $o['expire_time'] > $tm ? 1 : -1,
                'info' => []
            ];
            //查一下是否有优惠券
            $couponItems = $couponPullMod->alias('p')
                ->join('system_coupon c', 'c.coupon_id=p.coupon_id')
                ->field('c.*,p.is_use,count(1) AS count')
                ->where(['p.membership_record_id' => $o['id']])
                ->group('is_use')
                ->select()
                ->toArray();
            $useIndex = array_column($couponItems, 'count', 'is_use');
            if ($couponItems) {
                $item['info'][] = [
                    'num' => array_sum($useIndex),
                    'price' => get_format_number($couponItems[0]['discount']),
                    'type' => '优惠券',
                    'left_num' => $useIndex[0] ?? 0,
                    'unit' => '张'
                ];
            }


            //查一下是否有配送费有优惠
            $deliverItems = $deliverMod->field('deliver_price,deliver_cate,is_use,count(1) AS count')->where(['rid' => $o['id']])->group('is_use')->select()->toArray();
            $useIndex = array_column($deliverItems, 'count', 'is_use');
            if ($deliverItems) {
                $item['info'][] = [
                    'num' => array_sum($useIndex),
                    'price' => get_format_number($deliverItems[0]['deliver_price']),
                    'type' => '平台配送费立减机会',
                    'left_num' => $useIndex[0] ?? 0,
                    'unit' => '次'
                ];
            }

            //检查是否还有剩余次数
            $leftNumSum = array_sum(array_column($item['info'], 'left_num'));
            if ($leftNumSum == 0 && $item['status'] == 1) {
                $item['status_txt'] = '已使用';
                $item['status'] = -1;
            }
            $lists[] = $item;
        }
        return $lists;
    }

}