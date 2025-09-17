<?php


namespace app\common\model\service;

use app\common\model\db\MembershipCard;
use app\common\model\db\MembershipCardOrderDeliver;
use app\common\model\db\MembershipCardUserOpenRecord;
use app\common\model\db\SystemCoupon;
use app\common\model\db\SystemCouponHadpull;
use think\Exception;

/**
 * 平台会员卡
 * @package app\common\model\service
 */
class MembershipCardService
{
    public $membershipCardMod = null;

    public function __construct()
    {
        $this->membershipCardMod = new MembershipCard();
    }

    /**
     * 获取平台会员卡详细信息
     * @author: 张涛
     * @date: 2020/12/22
     */
    public function getDetail()
    {
        $card = $this->membershipCardMod->where('status', '=', 1)->findOrEmpty()->toArray();
        if (empty($card)) {
            throw new Exception('平台会员卡已关闭');
        }
        $cardDiscount = round($card['price'] * 10 / $card['old_price'], 2);
        $rs = [
            'card_name' => $card['card_name'],
            'price' => get_format_number($card['price']),
            'old_price' => get_format_number($card['old_price']),
            'discount_txt' => $cardDiscount > 0 ? sprintf('%s折优惠', $cardDiscount) : '1折起优惠',
            'share_title' => $card['share_title'] ?: $card['card_name'],
            'share_sub_title' => $card['share_sub_title'],
            'share_image' => replace_file_domain($card['share_image'] ?: cfg('wechat_share_img')),
            'days' => $card['days'],
            'note' => sprintf('开通后，以上优惠在%d日内使用有效，可以累加购买', $card['days']),
        ];
        $discount = [];
        $defaultShareSubArr = [];
        $businessType = cfg('shop_alias_name') ?: '外卖';
        if ($card['discount_type'] & 1) {
            //优惠券
            $discount[] = [
                'tag' => sprintf('特权%s', count($discount) + 1),
                'title' => sprintf('购买后，享受%s元红包', get_format_number($card['coupon_num'] * $card['coupon_price'])),
                'sub_title' => sprintf('%s红包', $businessType),
                'cost' => get_format_number($card['coupon_num'] * $card['coupon_price']),
                'price' => get_format_number($card['coupon_price']),
                'num' => $card['coupon_num'],
                'use_instruction' => $card['coupon_order_price'] > 0 ? sprintf('满%s元可用', get_format_number($card['coupon_order_price'])) : '无门槛'
            ];
            $defaultShareSubArr[] = sprintf('%d张优惠券', $card['coupon_num']);
        }
        if ($card['discount_type'] & 2) {
            //平台配送费
            $discount[] = [
                'tag' => sprintf('特权%s', count($discount) + 1),
                'title' => sprintf('购买后，享受%d次平台配送费立减机会', $card['deliver_num']),
                'sub_title' => sprintf('%s业务%d次平台配送费立减机会', $businessType, $card['deliver_num']),
                'cost' => get_format_number($card['deliver_num'] * $card['deliver_price']),
                'price' => get_format_number($card['deliver_price']),
                'num' => $card['deliver_num'],
                'use_instruction' => '配送费立减'
            ];
            $defaultShareSubArr[] = sprintf('%d次配送立减机会', $card['deliver_num']);
        }

        if(empty($rs['share_sub_title'])){
            $rs['share_sub_title'] = '内含'.implode($defaultShareSubArr,'与');
        }
        $rs['list'] = $discount;
        return $rs;
    }

    /**
     * 获取一条记录
     * @author: 张涛
     * @date: 2020/12/22
     */
    public function getOne($where = [], $fields = '*')
    {
        return $this->membershipCardMod->where($where)->field($fields)->findOrEmpty()->toArray();
    }

    /**
     * 获取平台会员卡详细信息-新版
     */
    public function getNewDetail($uid)
    {
        $card = $this->membershipCardMod->where([['status', '=', 1]])->findOrEmpty()->toArray();
        if (empty($card)) {
            throw new Exception('平台会员卡已关闭');
        }
        $is_buy = 0; //0-未购买/未登录，1-已购买
        $is_can_buy = 0; //0-不可以购买,1-可以购买
        $effective_date = '0天'; //购买后有效期
        $save_money = 0; //已省金额
        $use_coupon_num = 0; //已使用优惠券数量
        $use_deliver_num = 0; //已使用配送券优惠数量
        $card_id = 0;
        $buy_btn_txt = '立刻购买';
        //判断用户是否已领取
        if($uid){
            $user_card = (new MembershipCardUserOpenRecord())->where([['uid','=',$uid],['expire_time','>',time()]])->order('id asc')->select()->toArray();
            if($user_card){
                $is_buy = 1;
                $now_use_card = []; //正在使用的会员卡
                $last_card = []; //最后购买的会员卡
                $change_date = 0;
                if(count($user_card)==1){
                    $now_use_card = $user_card[0];
                    $change_date = ($now_use_card['expire_time']-time())/3600/24;
                }else{
                    $now_use_card = $user_card[0];
                    $last_card = $user_card[(count($user_card)-1)];
                    $change_date = ($last_card['expire_time']-time())/3600/24;
                }
                $change_time = $change_date-$card['early_renewal'];
                if($change_time<=0){
                    $is_can_buy = 1;
                    $buy_btn_txt = '立刻续费';
                }
                if($change_date>=1){
                    $effective_date = intval($change_date).'天';
                }else{
                    $effective_date = ceil($change_date*24).'小时';
                }
                
                $card_id = $now_use_card['id'];
                //获取当前会员卡节省金额
                //优惠券优惠金额
                $discount_coupon_total = (new SystemCouponHadpull())->field('count(a.id) as total,sum(b.discount) as total_price')->alias('a')->join('system_coupon b','a.coupon_id = b.coupon_id')->where([['membership_record_id','=',$now_use_card['id']],['is_use','=',1],['uid','=',$uid]])->find()->toArray();
                $save_money += $discount_coupon_total['total_price']?:0;
                $use_coupon_num = $discount_coupon_total['total']?:0;

                //配送费优惠金额
                $discount_deliver_total = (new MembershipCardOrderDeliver())->field('count(id) as total,sum(deliver_price) as total_price')->where([['rid','=',$now_use_card['id']],['is_use','=',1],['uid','=',$uid]])->find()->toArray();
                $save_money += $discount_deliver_total['total_price']?:0;
                $use_deliver_num = $discount_deliver_total['total']?:0;

                $save_money = $save_money?get_format_number($save_money):0;
            }else{
                $is_can_buy = 1;
            }

        }
        $cardDiscount = round($card['price'] * 10 / $card['old_price'], 2);
        $rs = [
            'card_name' => $card['card_name'],
            'price' => get_format_number($card['price']),
            'old_price' => get_format_number($card['old_price']),
            'discount_txt' => $cardDiscount > 0 ? sprintf('%s折优惠', $cardDiscount) : '1折起优惠',
            'share_title' => $card['share_title'] ?: $card['card_name'],
            'share_sub_title' => $card['share_sub_title'],
            'share_image' => replace_file_domain($card['share_image'] ?: cfg('wechat_share_img')),
            'days' => $card['days'],
            'note' => sprintf('开通后，以上优惠在%d日内使用有效，可以累加购买', $card['days']),
            'is_can_buy' => $is_can_buy,
            'is_buy' => $is_buy,
            'effective_date' => $effective_date,
            'save_money' => $save_money,
            'my_coupon_list_url' => cfg('site_url').'/packapp/plat/pages/coupon/myCoupon',
            'buy_info' => $card['buy_info'],
            'card_id' => $card_id,
            'bg_img' => $card['img_url']?replace_file_domain($card['img_url']):cfg('site_url').'/static/wxapp/membership/bj-vein.png',
            'buy_btn_txt' => $buy_btn_txt,
        ];
        $discount = [];
        $defaultShareSubArr = [];
        $businessType = cfg('shop_alias_name') ?: '外卖';
        $economy_money = 0;  //预计可节省金额
        if ($card['discount_type'] & 1) {
            //优惠券
            $discount[] = [
                'tag' => sprintf('特权%s', count($discount) + 1),
                'title' => sprintf('购买后，享受%s元红包', get_format_number($card['coupon_num'] * $card['coupon_price'])),
                'sub_title' => sprintf('%s红包', $businessType),
                'cost' => get_format_number($card['coupon_num'] * $card['coupon_price']),
                'price' => get_format_number($card['coupon_price']),
                'num' => $card['coupon_num'],
                'use_instruction' => $card['coupon_order_price'] > 0 ? sprintf('满%s元可用', get_format_number($card['coupon_order_price'])) : '无门槛',
                'group_type_txt' => L_('外卖券'),
                'group_type' => 'shop',
                'use_url' => cfg('site_url').'/packapp/plat/pages/shop_index/index',
                'use_num' => $use_coupon_num
            ];
            $defaultShareSubArr[] = sprintf('%d张优惠券', $card['coupon_num']);
            $economy_money += get_format_number($card['coupon_num'] * $card['coupon_price']);
        }
        if ($card['discount_type'] & 2) {
            //平台配送费
            $discount[] = [
                'tag' => sprintf('特权%s', count($discount) + 1),
                'title' => sprintf('购买后，享受%d次平台配送费立减机会', $card['deliver_num']),
                'sub_title' => sprintf('%s业务%d次平台配送费立减机会', $businessType, $card['deliver_num']),
                'cost' => get_format_number($card['deliver_num'] * $card['deliver_price']),
                'price' => get_format_number($card['deliver_price']),
                'num' => $card['deliver_num'],
                'use_instruction' => L_('配送费立减'),
                'group_type' => 'deliver',
                'group_type_txt' => L_('配送券'),
                'use_url' => cfg('site_url').'/packapp/plat/pages/shop_index/index',
                'use_num' => $use_deliver_num
            ];
            $defaultShareSubArr[] = sprintf('%d次配送立减机会', $card['deliver_num']);
            $economy_money += get_format_number($card['coupon_num'] * $card['coupon_price']);
        }

        if(empty($rs['share_sub_title'])){
            $rs['share_sub_title'] = '内含'.implode('与',$defaultShareSubArr);
        }
        $rs['list'] = $discount;
        $rs['economy_money'] = $economy_money;
        return $rs;
    }
    
    /**
     * 获取使用会员卡的订单列表
     */
    public function getOrderList($param){
        $uid = $param['uid'];
        $card_id = $param['card_id'];
        if(!$card_id){
            throw new \think\Exception(L_('参数错误'));
        }
        //判断会员卡是否购买
        $card_info = (new MembershipCardUserOpenRecord())->where([['uid','=',$uid],['id','=',$card_id]])->find();
        if(empty($card_info)||!$card_info){
            throw new \think\Exception(L_('会员卡不存在！'));
        }
        $where = [
            ['b.paid','=',1],
            ['b.status','in',[0,1,2,3]]
        ];
        $field = 'b.order_id,b.total_price,b.create_time,c.name as store_name';
        //获取优惠券使用订单
        $coupon_order_list = (new SystemCouponHadpull())->field($field.',b.coupon_price')->alias('a')
            ->join('shop_order b','a.id = b.coupon_id')
            ->join('merchant_store c','b.store_id = c.store_id','left')
            ->where($where)
            ->where([['a.membership_record_id','=',$card_id]])
            ->select()->toArray();
        //获取配送优惠订单
        $deliver_order_list = (new MembershipCardOrderDeliver())->field($field.',a.deliver_price')->alias('a')
            ->join('shop_order b','a.use_order_id = b.order_id')
            ->join('merchant_store c','b.store_id = c.store_id','left')
            ->where($where)
            ->where([['a.rid','=',$card_id]])
            ->select()->toArray();
        $coupon_order_arr = $discount_price = [];
        foreach ($coupon_order_list as $item){
            $coupon_order_arr[$item['order_id']] = $item;
            $discount_price[$item['order_id']] = $item['coupon_price'];
        }
        $deliver_order_arr = [];
        foreach ($deliver_order_list as $item){
            $deliver_order_arr[$item['order_id']] = $item;
            if(isset($discount_price[$item['order_id']])){
                $discount_price[$item['order_id']] += $item['deliver_price'];
            }else{
                $discount_price[$item['order_id']] = $item['deliver_price'];
            }
        }
        $order_list = $coupon_order_arr+$deliver_order_arr;
        $res = [];
        $k = 0;
        foreach ($order_list as $key=>$value){
            $res[$k] = [
                'order_id' => $value['order_id'],
                'note' => ($value['store_name']?'在 '.$value['store_name'].' ':'').'消费'.getFormatNumber($value['total_price']).'元节省'.(isset($discount_price[$value['order_id']])?getFormatNumber($discount_price[$value['order_id']]):0).'元',
                'create_time' => $value['create_time']?date('Y/m/d H:i',$value['create_time']):''
            ];
            $k++;
        }
        return $res;
    }
}