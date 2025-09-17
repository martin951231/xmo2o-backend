<?php

namespace app\paotui\model\service;

use app\deliver\model\db\DeliverSupply;
use app\deliver\model\db\DeliverUser;
use app\paotui\model\db\ServiceUserPublish;
use app\paotui\model\db\ServiceUserPublishGive;

/**
 * 帮我送
 * @author: 张涛
 * @date: 2020/10/16
 * @package app\paotui\model\service
 */
class ServiceUserPublishGiveService
{
    public $giveMod = null;

    public function __construct()
    {
        $this->giveMod = new ServiceUserPublishGive();
    }

    public function getInfoByPublishId($publishId)
    {
        $publishOrder = (new ServiceUserPublish())->where('publish_id', $publishId)->findOrEmpty()->toArray();
        $giveOrder = $this->giveMod->where('publish_id', $publishId)->findOrEmpty()->toArray();
        if (empty($giveOrder)) {
            return [];
        }
        $rs = [];
        $rs['expect_use_time'] = (isset($giveOrder['arrival_time_info']) && $giveOrder['arrival_time_info']) ? $giveOrder['arrival_time_info'] : $giveOrder['fetch_time'];
        $rs['user_address'] = [
            'title' => $giveOrder['end_adress_name'],
            'sub_title' => $giveOrder['end_adress_name'],
            'lng' => $giveOrder['end_adress_lng'],
            'lat' => $giveOrder['end_adress_lat'],
            'tag' => L_('送货'),
            'type' => 2,
            'miles' =>  $publishOrder['destance_sum'] . 'km'
        ];

        $rs['pick_address'] = [
            'title' => $giveOrder['start_adress_name'],
            'sub_title' => $giveOrder['start_adress_name'],
            'lng' => $giveOrder['start_adress_lng'],
            'lat' => $giveOrder['start_adress_lat'],
            'tag' => L_('取货'),
            'type' => 1,
            'miles' => '0km'
        ];
        $phoneLists = $labels = [];
        $buyer = (new \app\common\model\db\User())->where('uid', $publishOrder['uid'])->field('uid,phone,nickname')->findOrEmpty()->toArray();

        //手机号
        if ($giveOrder['end_adress_phone']) {
            $phoneLists[] = [
                "name" => $giveOrder['end_adress_nickname'],
                "type" => 1,
                "txt" => L_("收货人"),
                "show_phone" =>  $giveOrder['end_adress_phone'],
                "phone" => $giveOrder['end_adress_phone'],
                "im_url" => ''
            ];
        }
        if ($giveOrder['start_adress_phone']) {
            $phoneLists[] = [
                "name" => $giveOrder['start_adress_nickname'],
                "type" => 3,
                "txt" => L_("下单人"),
                "show_phone" => $giveOrder['start_adress_phone'],
                "phone" => $giveOrder['start_adress_phone'],
                "im_url" => ''
            ];
        }else if($buyer && $buyer['phone']){
            $phoneLists[] = [
                "name" => $buyer['nickname'],
                "type" => 3,
                "txt" => L_("下单人"),
                "show_phone" => $buyer['phone'],
                "phone" => $buyer['phone'],
                "im_url" => ''
            ];
        }
        $rs['phone_lists'] = $phoneLists;

        //标签
        $labels = [
            [
                "txt" => L_("帮送"),
                "background" => "#A057F5",
                "font_color" => "#FFFFFF",
                "with_border" => false
            ]
        ];

        if ($giveOrder['goods_catgory']) {
            $labels[] = [
                "txt" => $giveOrder['goods_catgory'] . ' ' . (is_numeric($giveOrder['weight']) ? $giveOrder['weight'] . 'kg' : $giveOrder['weight']),
                "background" => "#FFFFFF",
                "font_color" => "#27A3F7",
                "with_border" => true
            ];
        }
        $rs['labels'] = $labels;
        $rs['order'] = $giveOrder;
        $rs['username'] = $giveOrder['end_adress_nickname'];
        $rs['phone'] = $giveOrder['end_adress_phone'];
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

        $giveOrder = $this->giveMod->where('publish_id', $publishOrder['publish_id'])->findOrEmpty()->toArray();
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
