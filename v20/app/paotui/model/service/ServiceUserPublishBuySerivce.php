<?php

namespace app\paotui\model\service;


use app\deliver\model\db\DeliverSupply;
use app\deliver\model\db\DeliverUser;
use app\paotui\model\db\ServiceUserPublish;
use app\paotui\model\db\ServiceUserPublishBuy;

/**
 * 帮我买
 * @author: 张涛
 * @date: 2020/10/16
 * @package app\paotui\model\service
 */
class ServiceUserPublishBuySerivce
{
    public $buyMod = null;

    public function __construct()
    {
        $this->buyMod = new ServiceUserPublishBuy();
    }

    public function getInfoByPublishId($publishId)
    {
        $publishOrder = (new ServiceUserPublish())->where('publish_id', $publishId)->findOrEmpty()->toArray();
        $buyOrder = $this->buyMod->where('publish_id', $publishId)->findOrEmpty()->toArray();
        if (empty($buyOrder)) {
            return [];
        }
        $rs = [];
        $rs['expect_use_time'] = $buyOrder['arrival_time_info'];
        $rs['user_address'] = [
            'title' => $buyOrder['end_adress_name'],
            'sub_title' => $buyOrder['end_adress_name'],
            'lng' => $buyOrder['end_adress_lng'],
            'lat' => $buyOrder['end_adress_lat'],
            'tag' => L_('送货'),
            'type' => 2,
            'miles' => $buyOrder['buy_type'] == 1 ? '' : $publishOrder['destance_sum'] . 'km'
        ];

        if ($buyOrder['buy_type'] == 1) {
            //周边购买
            $rs['pick_address'] = [
                'title' => L_('就近购买'),
                'sub_title' => '',
                'lng' => $buyOrder['end_adress_lng'],
                'lat' => $buyOrder['end_adress_lat'],
                'tag' => L_('取货'),
                'type' => 1,
                'miles' => ''
            ];
        } else {
            //指定地址
            $rs['pick_address'] = [
                'title' => $buyOrder['address'],
                'sub_title' => $buyOrder['address'],
                'lng' => $buyOrder['address_lng'],
                'lat' => $buyOrder['address_lat'],
                'tag' => L_('取货'),
                'type' => 1,
                'miles' => '0km'
            ];
        }


        $phoneLists = $labels = [];

        //手机号
        if($buyOrder['adress_phone']){
            $phoneLists[] = [
                "name" => $buyOrder['adress_nickname'],
                "type" => 1,
                "txt" => L_("商家"),
                "show_phone" => $buyOrder['adress_phone'],
                "phone" => $buyOrder['adress_phone'],
                "im_url" => ''
            ];
        }
        if ($buyOrder['end_adress_phone']) {
            $phoneLists[] = [
                "name" => $buyOrder['end_adress_nickname'],
                "type" => 1,
                "txt" => L_("收货人"),
                "show_phone" => $buyOrder['end_adress_phone'],
                "phone" => $buyOrder['end_adress_phone'],
                "im_url" => ''
            ];
        }
        $rs['phone_lists'] = $phoneLists;

        //标签
        $labels = [
            [
                "txt" => L_("帮买"),
                "background" => "#A057F5",
                "font_color" => "#FFFFFF",
                "with_border" => false
            ]
        ];
        if ($buyOrder['weight']) {
            $labels[] = [
                "txt" => (is_numeric($buyOrder['weight']) ? $buyOrder['weight'] . 'kg' : $buyOrder['weight']),
                "background" => "#FFFFFF",
                "font_color" => "#27A3F7",
                "with_border" => true
            ];
        }
        $buyOrder['fetch_time'] = $buyOrder['arrival_time_info'];
        $buyOrder['remarks'] = $buyOrder['goods_remarks'];
        $rs['labels'] = $labels;
        $rs['order'] = $buyOrder;
        $rs['username'] = $buyOrder['end_adress_nickname'];
        $rs['phone'] = $buyOrder['end_adress_phone'];
        return $rs;
    }


    /**
     * 抢单
     * @param $uid
     * @param $supplyId
     * @author: 张涛
     * @date: 2020/10/18
     */
    public function grabOrder($uid, $supplyId, $publishOrder)
    {
        $supplyMod = new DeliverSupply();
        $supply = $supplyMod->where('supply_id', $supplyId)->findOrEmpty()->toArray();
        $user = (new DeliverUser())->where('uid', $uid)->findOrEmpty()->toArray();
        $tm = time();

        $columns = [
            'uid' => $uid,
            'status' => 2,
            'deliver_status' => 2,
            'is_fetch_order' => 1,
            'start_time' => $tm,
            'grab_time' => $tm
        ];

        $giveOrder = $this->buyMod->where('publish_id', $publishOrder['publish_id'])->findOrEmpty()->toArray();
        if ($giveOrder) {
            $fc = $giveOrder['basic_distance_price'] + $giveOrder['weight_price'] + $giveOrder['distance_price'];//基础配送费+超重费用+超距离配送
            $columns['deliver_user_fee'] = $fc - ($fc * ($user['take_percent'] / 100));//配送员所得费用（扣除平台抽成）
        }

        $rs = invoke_cms_model('Service_offer/add_offer', [$publishOrder['publish_id'], $uid], true);
        $rs['retval'] > 0 && $columns['offer_id'] = $rs['retval'];
        if (($supply['server_type'] == 2 || $supply['server_type'] == 3) && !$supply['appoint_time'] && $supply['server_time']) {
            $columns['appoint_time'] = $tm + 60 * $supply['server_time'];
        }
        (new DeliverSupply())->where('supply_id', $supplyId)->update($columns);
    }
}
