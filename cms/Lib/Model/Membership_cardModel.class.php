<?php

/**
 * 平台会员卡模型
 */
class Membership_cardModel extends Model
{
    const HADPULL_BEGIN_ID = 2147482647;

    /**
     * 写入操作日志
     * @param $action
     * @param $before
     * @param $after
     * @author: 张涛
     * @date: 2020/12/21
     */
    public function wirte_log($action, $before, $after,$adminUser)
    {
        $data = [
            'action' => $action,
            'before_data' => json_encode($before, JSON_UNESCAPED_UNICODE),
            'after_data' => json_encode($after, JSON_UNESCAPED_UNICODE),
            'log_time' => time(),
            'admin_user' => $adminUser
        ];
        M('Membership_card_log')->add($data);
    }

    /**
     * 获取当前平台会员卡配置
     * @author: 张涛
     * @date: 2020/12/23
     */
    public function getCurrentMembershipCard()
    {
        return $this->where(['status' => 1])->find();
    }

    /**
     * 发放平台会员卡
     * @author: 张涛
     * @date: 2020/12/23
     */
    public function sendCard($uid, $orderId, $businessType)
    {
        $tm = time();
        $card = $this->getCurrentMembershipCard();
        $user = M('User')->where(['uid' => $uid])->find();
        if (empty($card) || empty($user)) {
            return false;
        } else {
            //针对快店订单，使用了平台会员卡赠送优惠券
            $nowOrder = [];
            if ($businessType == 'shop') {
                $nowOrder = D('Shop_order')->where(['order_id' => $orderId])->find();
            }

            //优惠券领取时间去会员时间，因为是续费
            $receiveTime = $user['membership_card_time'] > $tm ? $user['membership_card_time'] : $tm;
            $expireTime = strtotime("+" . $card['days'] . "days", $receiveTime);

            //记录下开通记录
            $record = [
                'uid' => $uid,
                'create_time' => $tm,
                'expire_time' => $expireTime,
                'business_type' => $businessType,
                'business_order_id' => $orderId,
                'card_name' => $card['card_name'],
                'price' => $card['price']
            ];
            $recId = M('Membership_card_user_open_record')->add($record);


            if (($card['discount_type'] & 1) && $card['coupon_id'] > 0) {
                //优惠券
                $hadPullData = [];
                for ($i = 0; $i < $card['coupon_num']; $i++) {
                    $hadPullData[] = [
                        'coupon_id' => $card['coupon_id'],
                        'num' => 1,
                        'receive_time' => $receiveTime,
                        'status' => 0,
                        'uid' => $uid,
                        'membership_record_id' => $recId
                    ];
                }
                $insertId = M('System_coupon_hadpull')->addAll($hadPullData);
            }

            if ($insertId > 0 && $nowOrder['coupon_id'] >= Membership_cardModel::HADPULL_BEGIN_ID) {
                D('System_coupon')->user_coupon($insertId, $orderId, 'shop', $nowOrder['mer_id'], $nowOrder['uid']);
                D('Shop_order')->where(['order_id' => $orderId])->save(['coupon_id' => $insertId]);
            }

            if ($card['discount_type'] & 2) {
                //平台配送费减免
                $deliver = [];
                for ($i = 0; $i < $card['deliver_num']; $i++) {
                    $deliver[] = [
                        'rid' => $recId,
                        'uid' => $uid,
                        'deliver_price' => $card['deliver_price'],
                        'deliver_cate' => $card['deliver_cate'],
                        'start_time' => $receiveTime,
                        'end_time' => $expireTime
                    ];
                }
                $insertId = M('Membership_card_order_deliver')->addAll($deliver);
            }

            if ($insertId > 0 && $nowOrder['reduce_freight_charge'] > 0) {
                $this->useOrderDeliverItem($insertId, $orderId);
                D('Shop_order')->where(['order_id' => $orderId])->save(['reduce_id' => $insertId]);
            }
            return ['expire_time' => $expireTime];
        }
    }

    /**
     * 获取当前平台会员卡活动优惠券信息
     * @author: 张涛
     * @date: 2021/01/05
     */
    public function getCouponInfo()
    {
        $info = [];
        $tm = time();
        $membershipCard = $this->getCurrentMembershipCard();
        if ($membershipCard && ($membershipCard['discount_type'] & 1)) {
            $catePlatform = D('System_coupon')->cate_platform();
            $thisCoupon = D('System_coupon')->where(['coupon_id' => $membershipCard['coupon_id']])->find();
            $thisCoupon['coupon_type'] = 'system';
            $thisCoupon['coupon_type_name'] = L_('平台券');
            $thisCoupon['type'] = $thisCoupon['cate_name'];
            $thisCoupon['discount_money'] = $thisCoupon['discount'];
            $thisCoupon['platform_s'] = $thisCoupon['platform'];
            $thisCoupon['platform'] = unserialize($thisCoupon['platform']);
            if (!empty($thisCoupon['cate_id'])) {
                $thisCoupon['cate_id'] = unserialize($thisCoupon['cate_id']);
            }
            foreach ($thisCoupon['platform'] as &$vv) {
                $vv = $catePlatform['platform'][$vv];
            }
            $thisCoupon['cate_name_s'] = $thisCoupon['cate_name'];
            if ($thisCoupon['cate_name'] != 'all') {
                $thisCoupon['cate_name'] = $catePlatform['category'][$thisCoupon['cate_name']];
            }
            $thisCoupon['platform'] = trim(implode(',', $thisCoupon['platform']), ',');
            $thisCoupon['end_time'] = $tm + 86400 * $membershipCard['days'];
            for ($i = 1; $i <= $membershipCard['coupon_num']; $i++) {
                $thisCoupon['had_id'] = self::HADPULL_BEGIN_ID + $i;
                $thisCoupon['is_membership_card'] = true;
                $info[] = $thisCoupon;
            }
        }
        return $info;
    }

    /**
     * 根据用户id获取一条平台会员卡减免平台配送费记录
     * @param $uid
     * @author: 张涛
     * @date: 2021/02/19
     */
    public function getOneOrderDeliverItem($uid)
    {
        $tm = time();
        $where = [
            'is_use' => 0,
            'uid' => $uid,
            'use_time' => 0,
            'use_order_id' => 0,
            'start_time' => ['LT', $tm],
            'end_time' => ['GT', $tm]
        ];
        return M('Membership_card_order_deliver')->where($where)->find();
    }

    /**
     * 使用平台配送费减免
     * @param $id
     * @author: 张涛
     * @date: 2021/02/19
     */
    public function useOrderDeliverItem($id, $orderId)
    {
        $update = [
            'use_time' => time(),
            'use_order_id' => $orderId,
            'is_use' => 1
        ];
        M('Membership_card_order_deliver')->where(['id' => $id])->save($update);
    }
}

?>