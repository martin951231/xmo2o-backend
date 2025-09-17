<?php

namespace app\paotui\model\service;

use app\common\model\db\User;
use app\deliver\Code;
use app\deliver\model\db\DeliverSupply;
use app\deliver\model\db\DeliverUser;
use app\deliver\model\service\DeliverSupplyService;
use app\deliver\model\service\DeliverUserService;
use app\paotui\model\db\ServiceUserPublish;
use think\Exception;

/**
 * 服务快派服务
 * @author: 张涛
 * @date: 2020/10/18
 * @package app\paotui\model\service
 */
class ServiceUserPublishService
{
    public $publishMod = null;
    public $buyService = null;
    public $giveService = null;

    public function __construct()
    {
        $this->publishMod = new ServiceUserPublish();
        $this->buyService = new ServiceUserPublishBuySerivce();
        $this->giveService = new ServiceUserPublishGiveService();
    }


    public function getOrderInfoForRoutePoint($orderId)
    {
        $order = $this->publishMod->where('publish_id', $orderId)->findOrEmpty()->toArray();
        if (empty($order)) {
            return [];
        }

        $rs = [];
        if ($order['catgory_type'] == 2) {
            //帮我买
            $publishOrder = $this->buyService->getInfoByPublishId($order['publish_id']);
            $rs['fetch_time'] = $publishOrder['order']['arrival_time_info'];
        } else if ($order['catgory_type'] == 3) {
            //帮我送
            $publishOrder = $this->giveService->getInfoByPublishId($order['publish_id']);
            $rs['fetch_time'] = $publishOrder['order']['fetch_time'];
        }
        if (empty($publishOrder)) {
            return [];
        }



        $rs['pick_address'] = $publishOrder['pick_address'];
        $rs['user_address'] = $publishOrder['user_address'];
        return $rs;
    }


    /**
     * 获取订单信息（针对配送app订单列表展示，其他地方勿用）
     * @param $orderId
     * @author: 张涛
     * @date: 2020/10/18
     */
    public function getInfoForDeliverList($orderId)
    {
        $order = $this->publishMod->where('publish_id', $orderId)->findOrEmpty()->toArray();
        if (empty($order)) {
            return [];
        }

        $rs = [];
        $rs['publish_id'] = $order['publish_id'];
        $rs['real_orderid'] = $order['order_sn'];
        $rs['fetch_number'] = 0;
        $rs['note'] = '';
        $rs['desc'] = '';
        if ($order['catgory_type'] == 2) {
            //帮我买
            $publishOrder = $this->buyService->getInfoByPublishId($order['publish_id']);
        } else if ($order['catgory_type'] == 3) {
            //帮我送
            $publishOrder = $this->giveService->getInfoByPublishId($order['publish_id']);
        }
        if ($publishOrder) {
            $publishOrder['order'] = array_merge($order, $publishOrder['order']);
            $rs = array_merge($rs, $publishOrder);
        }
        return $rs;
    }


    /**
     * 抢单
     * @param $uid
     * @param $supplyId
     * @author: 张涛
     * @date: 2020/10/18
     */
    public function grabOrder($uid, $supplyId)
    {
        $supplyMod = new DeliverSupply();
        $supply = $supplyMod->where('supply_id', $supplyId)->findOrEmpty()->toArray();
        $user = (new DeliverUser())->where('uid', $uid)->findOrEmpty()->toArray();
        if ($supply['uid'] != 0) {
            throw new Exception('订单已被抢', Code::ORDER_ACCEPTED);
        }

        if ($user['group'] == 2 && ($user['store_id'] || $user['store_ids'])) {
            $storeIds = !empty($user['store_ids']) ? explode(',', $user['store_ids']) : [$user['store_id']];
            if (!in_array($supply['store_id'], $storeIds)) {
                throw new Exception('非该店铺配送员禁止接单！');
            }
        }

        //检测最大接单量
        if ($user['max_num'] > 0) {
            $count = $supplyMod->where("uid={$uid} AND status >0 AND status < 5")->count();
            if ($count >= $user['max_num']) {
                throw new Exception(L_('您当前已有X1单没有完成配送，请配送完成再来抢单！', array('X1' => $count)));
            }
        }
        // 开启配送员等级
        if (cfg('open_deliver_level') && $user['group'] == 1) {
            $nowLevel = (new DeliverUserService())->getUserLevel($user['score']);
            if ($nowLevel['order_number'] >= 0) {
                // 今日接单量
                $todayNum = $supplyMod->where("uid={$uid} AND start_time >" . strtotime(date("Y-m-d")))->count();
                if ($todayNum >= $nowLevel['order_number']) {
                    throw new Exception('今日接单量已达上限！');
                }
            }
        }

        $order = $this->publishMod->where('publish_id', $supply['order_id'])->findOrEmpty()->toArray();
        if (empty($order)) {
            throw new Exception('业务订单不存在！');
        }
        if ($order['catgory_type'] == 2) {
            //帮我买
            $this->buyService->grabOrder($uid, $supplyId, $order);
        } else if ($order['catgory_type'] == 3) {
            //帮我送
            $this->giveService->grabOrder($uid, $supplyId, $order);
        }
        return true;
    }

    /**
     * 上报到店
     * @author: 张涛
     * @date: 2020/10/18
     */
    public function reportArriveStore($uid, $supplyId)
    {
        $supply = (new DeliverSupply())->where('supply_id', $supplyId)->findOrEmpty()->toArray();
        if($supply['status']==5){
            throw new Exception(L_('操作失败，订单已完成！'));
        }
        $columns = [
            'status' => 3,
            'deliver_status' => 3,
            'is_fetch_order' => 1,
            'real_fetch_good_time' => date('Y-m-d H:i:s'),
            'report_arrive_store_time' => time()
        ];
        (new DeliverSupply())->where(['supply_id' => $supplyId, 'uid' => $uid])->update($columns);
    }

    /**
     * 我已取货(迁移旧版取货 + 配送功能)
     * @author: 张涛
     * @date: 2020/10/18
     */
    public function pickOrder($uid, $supplyId)
    {
        $supply = (new DeliverSupply())->where('supply_id', $supplyId)->findOrEmpty()->toArray();
        $user = (new DeliverUser())->where('uid', $uid)->findOrEmpty()->toArray();
        $columns = [
            'uid' => $uid,
            'status' => 4,
            'deliver_status' => 4,
            'end_time' => time(),
            'pick_time' => time(),
            'real_deliver_start_time' => date('Y-m-d H:i:s')
        ];
        $result = (new DeliverSupply())->where(['supply_id' => $supplyId, 'status' => 3])->update($columns);
        if (!$result) {
            throw new Exception('更新配送信息失败！');
        }
        invoke_cms_model('Service_offer/offer_save_status', [$supply['order_id'], $uid, $supply['offer_id'], 9], true);
    }


    /**
     * 我已送达
     * @author: 张涛
     * @date: 2020/10/18
     */
    public function finishOrder($uid, $supplyId)
    {
        $supply = (new DeliverSupply())->where('supply_id', $supplyId)->findOrEmpty()->toArray();
        $user = (new DeliverUser())->where('uid', $uid)->findOrEmpty()->toArray();
        if ($supply['status'] != 4) {
            throw new Exception('当前状态不能修改成完成状态！');
        }
        $columns = [
            'uid' => $uid,
            'status' => 5,
            'deliver_status' => 5,
            'end_time' => time(),
            'finish_time' => time()
        ];
        $result = (new DeliverSupply())->where(['supply_id' => $supplyId, 'status' => 4])->update($columns);
        if (!$result) {
            throw new Exception('更新配送信息失败！');
        }

        //统计每日配送量
        (new DeliverUserService())->updateDeliverUserTotalNum($uid);
        (new DeliverUserService())->updateDeliverUserNumByDate($uid, date('Ymd'));

        invoke_cms_model('Service_offer/offer_save_status', [$supply['order_id'], $uid, $supply['offer_id'], 4], true);

        // 完成订单获取配送员积分
        if (cfg('open_deliver_level') && $user['group'] == 1) {
            $score = cfg('deliver_level_complate_get_score');
            $desc = '完成订单获得' . $score . '积分';
            (new DeliverUserService())->addScore($uid, $score, $desc, 2, $supply);
        }

        //结算一下吧，防止旧框架代码结算不了
        $nowSupply = (new DeliverSupply())->where('supply_id', $supplyId)->where('is_settlement', 0)->findOrEmpty()->toArray();
        if ($nowSupply && $nowSupply['uid'] > 0) {
            (new DeliverUserService())->addMoney($nowSupply['uid'], 'service', $nowSupply['order_id'], $nowSupply['deliver_user_fee'], '配送收入', $nowSupply['tip_price'], '小费收入');
            (new DeliverSupply())->where(['supply_id' => $supplyId])->update(['is_settlement' => 1]);
        }
    }

    /**
     * 扔回
     * @author: 张涛
     * @date: 2020/9/10
     */
    public function throwOrder($uid, $supplyId)
    {
        $supply = (new DeliverSupply())->where(['supply_id' => $supplyId, 'uid' => $uid])->findOrEmpty()->toArray();
        $user = (new DeliverUser())->where('uid', $uid)->findOrEmpty()->toArray();

        if (empty($supply)) {
            throw new Exception('配送记录不存在');
        }
        if ($user['is_cancel_order'] == 0) {
            throw new Exception('您没有扔回的权限');
        }
        if (!in_array($supply['deliver_status'], [2])) {
            throw new Exception('该配送状态禁止扔回');
        }

        $saveData = ['uid' => 0, 'status' => 1, 'start_time' => 0, 'offer_id' => 0, 'back_time' => time(), 'is_fetch_order' => 0, 'deliver_status' => 1];
        $saveData['back_log'] = $supply['back_log'] ? $supply['back_log'] . ',' . $uid : $uid;
        $result = (new DeliverSupply())->where(['supply_id' => $supplyId])->update($saveData);
        if (!$result) {
            throw new Exception('更新配送信息失败！');
        }

        $orderId = $supply['order_id'];
        invoke_cms_model('Service_offer/cancel_order', [$orderId, $uid], true);

        //记录扔回日志
        $cancelData = [];
        $cancelData['uid'] = $user['uid'];
        $cancelData['username'] = $user['name'];
        $cancelData['userphone'] = $user['phone'];
        $cancelData['supply_id'] = $supply['supply_id'];
        $cancelData['store_id'] = $supply['store_id'];
        $cancelData['order_id'] = $supply['order_id'];
        $cancelData['item'] = $supply['item'];
        $cancelData['dateline'] = time();
        \think\facade\Db::name('deliver_cancel_log')->insert($cancelData);

        $supply['uid'] = 0;
        $supply['status'] = 1;
        $supply['start_time'] = 0;
        invoke_cms_model('Deliver_supply/sendMsg', ['supply' => $supply, 'excludeUid' => $uid], true);
    }

    /**
     * 转单
     * @author: 张涛
     * @date: 2020/9/10
     */
    public function transfterOrder($uid, $supplyId, $transferToUid)
    {
        $supply = (new DeliverSupply())->where(['supply_id' => $supplyId, 'uid' => $uid])->findOrEmpty()->toArray();
        $transferUser = (new DeliverUser())->where('uid', $transferToUid)->findOrEmpty()->toArray();
        if (empty($supply)) {
            throw new Exception('配送记录不存在');
        }
        if (!in_array($supply['deliver_status'], [2, 3, 4])) {
            throw new Exception('该配送状态禁止转单');
        }
        if ($transferUser['status'] != 1) {
            throw new Exception('转单配送员不存在');
        }
        if ($transferUser['is_notice'] != 0) {
            throw new Exception('转单配送员暂不接单');
        }
        $data = [
            'transfer_from_uid' => $uid,
            'transfer_deliver_status' => $supply['deliver_status'],
            'transfer_to_uid' => $transferToUid,
            'transfer_accept_time' => 0,
            'transfer_refuse_time' => 0,
            'transfer_status' => 0,
            'transfer_time' => time()
        ];
        (new DeliverSupply())->where(['supply_id' => $supplyId])->update($data);

        //增加推送新订单
        invoke_cms_model('Deliver_supply/newOrder', [$transferUser]);
    }

    /**
     * 接受转单
     * @author: 张涛
     * @date: 2020/9/10
     */
    public function acceptTransferOrder($uid, $supplyId)
    {
        $supply = (new DeliverSupply())->where(['supply_id' => $supplyId, 'transfer_to_uid' => $uid])->findOrEmpty()->toArray();
        if (empty($supply)) {
            throw new Exception('转单记录不存在', Code::TRANSFER_ORDER_NOT_EXIST);
        }

        //超时了
        if (time() > $supply['transfer_time'] + 180) {
            (new DeliverSupplyService())->transferExpiredUpdae($supplyId);
            throw new Exception('转单超时', Code::TRANSFER_ORDER_EXPIRED);
        } else {
            $user = (new DeliverUser())->where('uid', $uid)->findOrEmpty()->toArray();
            //转单后重新计算佣金
            $deliver_user_fee = $supply['freight_charge'] - ($supply['freight_charge'] * ($user['take_percent'] / 100));
            $data = [
                'uid' => $supply['transfer_to_uid'],
                'transfer_accept_time' => time(),
                'transfer_status' => 1,
                'deliver_user_fee'  =>  $deliver_user_fee
            ];
            (new DeliverSupply())->where(['supply_id' => $supplyId])->update($data);
        }
    }

    /**
     * 拒绝转单
     * @author: 张涛
     * @date: 2020/9/10
     */
    public function refuseOrder($uid, $supplyId)
    {
        $supply = (new DeliverSupply())->where(['supply_id' => $supplyId])->findOrEmpty()->toArray();
        if (empty($supply)) {
            throw new Exception('配送单记录不存在');
        }
        if ($supply['transfer_to_uid'] > 0) {
            //拒绝转单
            if (time() > $supply['transfer_time'] + 180) {
                (new DeliverSupplyService())->transferExpiredUpdae($supplyId);
                throw new Exception('转单超时', Code::TRANSFER_ORDER_EXPIRED);
            }
        }

        $refuseLog = explode(',', $supply['refuse_log']);
        $refuseLog[] = $uid;
        $newRefuseLog = implode(',', array_filter(array_unique($refuseLog)));
        $data = [
            'transfer_refuse_time' => time(),
            'refuse_log' => $newRefuseLog,
            'transfer_status' => 2,
            'uid' => $supply['transfer_from_uid'] > 0 ? $supply['transfer_from_uid'] : 0
        ];
        if ($supply['transfer_from_uid'] == 0) {
            $data['status'] = 1;
            $data['deliver_status'] = 1;
        }
        (new DeliverSupply())->where(['supply_id' => $supplyId])->update($data);
    }

    /**
     * 接受派单
     * @param $uid
     * @param $supplyId
     * @throws Exception
     * @author: 张涛
     * @date: 2020/10/24
     */
    public function fetchOrder($uid, $supplyId){
        $supply = (new DeliverSupply())->where(['supply_id' => $supplyId, 'uid' => $uid])->findOrEmpty()->toArray();
        $nowUser = (new DeliverUser())->where('uid', $uid)->findOrEmpty()->toArray();
        if (empty($supply)) {
            throw new Exception('该订单已指派给其他配送员，请刷新订单');
        }
        if ($supply['is_fetch_order'] != 0) {
            throw new Exception('当前订单已接单');
        }
        if (empty($nowUser)) {
            throw new Exception('配送员不存在');
        }


        $data = ['is_fetch_order' => 1];
        (new DeliverSupply())->where(['supply_id' => $supplyId])->update($data);
    }



}
