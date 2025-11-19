<?php

/**
 * SendTemplateMsgService.php
 * 推送模板消息
 * Create on 2021/1/22 10:11
 * Created by zhumengqun
 */

namespace app\mall\model\service;

use app\common\model\db\UserScoreList;
use app\common\model\db\WxappTemplate;
use app\common\model\db\WxappTemplateBindUserList;
use app\common\model\service\send_message\AppPushMsgService;
use app\common\model\service\send_message\WebPushMsgService;
use app\common\model\service\weixin\TemplateNewsService;
use app\common\model\service\weixin\WxappTemplateService;
use app\life_tools\model\db\LifeToolsTicket;
use app\mall\model\db\MallGoods;
use app\mall\model\db\MallNewGroupSku;
use app\mall\model\db\MallOrderRefund;
use app\mall\model\db\MallOrderRefundDetail;
use app\mall\model\db\MerchantStoreMall;
use app\mall\model\db\User;
use app\merchant\model\service\storestaff\MerchantStoreStaffService;

class SendTemplateMsgService
{
    /**
     * 发送小程序订阅消息
     */
    public function sendWxappMessage($param)
    {
        $type = $param['type'] ?? 0;
        switch ($type) {
            case 'group_success'://拼团成功通知
                $templateId = 3353;
                $param['template_key'] = $this->checkTemplate($templateId);
                $msg = $this->groupSuccess($param);
                break;
            case 'order_status_change_notice'://订单状态变更通知
                $templateId = 3115;
                $param['template_key'] = $this->checkTemplate($templateId);
                $msg = $this->orderStatusChangeNotice($param);
                break;
            case 'commission_settlement'://佣金到账
                $templateId = 14403;
                $param['template_key'] = $this->checkTemplate($templateId);
                $msg = $this->commissionSettle($param);
                break;
            case 'integer_settlement'://积分到账
                $templateId = 335;
                $param['template_key'] = $this->checkTemplate($templateId);
                $msg = $this->integerSettle($param);
                break;
            case 'seckill_remind'://开抢提醒
                $templateId = 2111;
                $param['template_key'] = $this->checkTemplate($templateId);
                $msg = $this->seckillRemind($param);
                break;
            case 'seckill_scenic_remind'://开抢提醒
                $templateId = 2111;
                $param['template_key'] = $this->checkTemplate($templateId);
                $msg = $this->seckillScenicRemind($param);
                break;
            case 'bargain_success'://砍价成功提醒
                $templateId = 2727;
                $param['template_key'] = $this->checkTemplate($templateId);
                $msg = $this->bargainSuccess($param);
                break;
            case 'friend_pay_success'://朋友代付支付成功提醒
                $templateId = 3578;
                $param['template_key'] = $this->checkTemplate($templateId);
                $msg = $this->friendPaySuccess($param);
                break;
            case 'refund_success'://退款成功提醒
                $templateId = 1451;
                $param['template_key'] = $this->checkTemplate($templateId);
                $msg = $this->refundSuccess($param);
                break;
            case 'reduce_success'://降价提醒
                $templateId = 1811;
                $param['template_key'] = $this->checkTemplate($templateId);
                $msg = $this->reduceSuccess($param);
                break;
            case 'prepare_rest_pay'://预售尾款支付提醒
                $templateId = 10225;
                $param['template_key'] = $this->checkTemplate($templateId);
                $msg = $this->prepareRestPay($param);
                break;
            case 'luck_draw_success'://中奖通知
                $templateId = 3561;
                $param['template_key'] = $this->checkTemplate($templateId);
                $msg = $this->luckDrawSuccess($param);
                break;
        }
        $msg['wxapp_openid'] = $msg['wxapp_openid']??'';
        $msg['openid'] = $msg['openid']??'';
        // 发送订阅消息
        $res = false;
        if($type!='seckill_remind'){//根据定制，限时秒杀没有订阅号信息，如果有了，再做修改
            if ($msg['wxapp_openid']) {
                $res = (new WxappTemplateService())->sendTempMsg($param['template_key'], $msg['msg_data']);
            }
        }
        // 订阅消息发送失败后， 尝试发送公众号模板消息
        if (!$res && $msg['openid'] && is_array($msg['msg_data_wx'])) {
            $res = (new TemplateNewsService())->sendTempMsg('OPENTM400166399', $msg['msg_data_wx']);
           
        }

        if($type=='seckill_remind'){
            $res = (new TemplateNewsService())->sendTempMsg('OPENTM400166399', $msg['msg_data_wx']);
        }
        return $res;
    }

    /**
     * @param $type
     * 获取模板消息弹框列表
     */
    public function getTemplateId($type)
    {
        if (empty($type)) {
            throw new \think\Exception('type参数不能为空');
        }
        $templateIds = array();
        switch ($type) {
            case 'group_pay_pop':
                $templateIds = [['template_id' => 3353, 'template_key' => 'OeC61Db0Q-ghhZujVybojdJV6QmE9DaTi2hgLL2e8Mw'], ['template_id' => 3115, 'template_key' => 'Rk0Pd9iAzZFaE6X6OXSJFlxJ6RdMivnZS-0Eeqm4d64'], ['template_id' => 14403, 'template_key' => 'LnMtra5bzRDOEUCUoRbHB73kHHIwBXuCdV-kpnnGgAE'], ['template_id' => 335, 'template_key' => 's8GtP_r1F0cqdYJe1kV0nl2yXShUYXlDjUZjCk0gXas']];
                break;
            case 'limit_remind_pop':
                $templateIds = [['template_id' => 2111, 'template_key' => 'KezHqXPtDValKQm_Qj71eUhhYahq84cvzFrkkNWgPmc']];
                break;
            case 'bargain_pop':
                $templateIds = [['template_id' => 2727, 'template_key' => 'd-yAaM_B_pZp-rbMrtedXHnTvrwWcNjAl2iSUW7V-hE']];
                break;
            case 'common_pay_pop':
                $templateIds = [['template_id' => 3115, 'template_key' => 'Rk0Pd9iAzZFaE6X6OXSJFlxJ6RdMivnZS-0Eeqm4d64'], ['template_id' => 14403, 'template_key' => 'LnMtra5bzRDOEUCUoRbHB73kHHIwBXuCdV-kpnnGgAE'], ['template_id' => 335, 'template_key' => 's8GtP_r1F0cqdYJe1kV0nl2yXShUYXlDjUZjCk0gXas']];
                break;
            case 'friend_pay_pop':
                $templateIds = [['template_id' => 3578, 'template_key' => 'JsTMGhJVnusAKbPY1Hz9CiL8BFiVXGZXXFQyATd-stQ'], ['template_id' => 3115, 'template_key' => 'Rk0Pd9iAzZFaE6X6OXSJFlxJ6RdMivnZS-0Eeqm4d64'], ['template_id' => 14403, 'template_key' => 'LnMtra5bzRDOEUCUoRbHB73kHHIwBXuCdV-kpnnGgAE'], ['template_id' => 335, 'template_key' => 's8GtP_r1F0cqdYJe1kV0nl2yXShUYXlDjUZjCk0gXas']];
                break;
            case 'refund_pop':
                $templateIds = [['template_id' => 1451, 'template_key' => 'oWiB21SvruzQn86Hq6EHcxMcANdeh0ojVyYjBMHiecc']];
                break;
            case 'reduce_pop':
                $templateIds = [['template_id' => 1811, 'template_key' => 'xYxdpDrFo2h_FTflUE3LIhVzul1dTg3YFwb-uSLftCI']];
                break;
            case 'prepare_pay_pop':
                $templateIds = [['template_id' => 10225, 'template_key' => '7Fl7IjI1haB97xS-77z6aahKqwEBfM6pXxz_E11Irgs'], ['template_id' => 3115, 'template_key' => 'Rk0Pd9iAzZFaE6X6OXSJFlxJ6RdMivnZS-0Eeqm4d64'], ['template_id' => 14403, 'template_key' => 'LnMtra5bzRDOEUCUoRbHB73kHHIwBXuCdV-kpnnGgAE'], ['template_id' => 335, 'template_key' => 's8GtP_r1F0cqdYJe1kV0nl2yXShUYXlDjUZjCk0gXas']];
                break;
            case 'free_pay_pop':
                $templateIds = [['template_id' => 3353, 'template_key' => 'OeC61Db0Q-ghhZujVybojdJV6QmE9DaTi2hgLL2e8Mw'], ['template_id' => 3115, 'template_key' => 'Rk0Pd9iAzZFaE6X6OXSJFlxJ6RdMivnZS-0Eeqm4d64'], ['template_id' => 3561, 'template_key' => 'OOqZAwRlcK8_CwEUOjfahsRAek4wyNVxilB6liyFLXE']];
                break;
        }
        return $templateIds;
    }

    /**
     * @param $templateId
     * @return array
     * 验证模板是否可用
     */
    public function checkTemplate($templateId)
    {
        $where = ['template_id' => $templateId];
        $template = (new WxappTemplate())->getOne($where);
        if (empty($template)) {
            return $returnArr['msg'] = L_('模板不存在，请添加模板');
        } else {
            $template_key = ($template->toArray())['template_key'];
            return $template_key;
        }
    }

    /**
     * @param $param
     * @return array
     * @throws \think\Exception
     * 拼团成功通知
     */
    public function groupSuccess($param)
    {
        $orderId = $param['order_id'] ?? 0;
        $nowOrder = (new MallOrderService())->getOne($orderId);
        if (empty($nowOrder)) {
            throw new \think\Exception(L_("订单不存在"), 1003);
        }
        //订单商品
        $nowGoodsInfo = (new MallOrderDetailService())->getByOrderId('name,uid,sku_id', $orderId);
        if (empty($nowGoodsInfo)) {
            throw new \think\Exception(L_("订单商品不存在"), 1003);
        }
        $param['goods_name'] = $nowGoodsInfo[0]['name'];
        //拼团信息
        $param['act_price'] = (new MallNewGroupSku())->getBySkuId(['sku_id' => $nowGoodsInfo[0]['sku_id']]) ? (new MallNewGroupSku())->getBySkuId(['sku_id' => $nowGoodsInfo[0]['sku_id']])['act_price'] : 0;
        $bind = $this->userInfo($nowGoodsInfo[0]['uid'], $param['template_key']);
        if (empty($bind)) {
            return false;
        } else {
            $wxappOpenid = $bind['wxapp_openid'];
            $openid = $bind['openid'];
        }
        $msgData = [
            'page' => cfg('site_url') . '/packapp/plat/pages/shopmall_third/myGroup',
            'wecha_id' => $wxappOpenid,
            'phrase8' => L_('拼团成功'),//拼团状态
            'thing2' => $param['goods_name'],//商品名称
            'thing6' => $param['complete_num'],//成团人数
            'amount4' => $param['act_price']//拼团价
        ];
        $msgDataWx = [
            'href' => cfg('site_url') . '/packapp/plat/pages/shopmall_third/myGroup',
            'wecha_id' => $openid,
            'first' => L_('恭喜您，您的商品拼团成功啦！点击查看详情哦~'),
            'keyword1' => $param['goods_name'],
            'keyword2' => L_('拼团成功'),
            'keyword3' => date("Y-m-d H:i"),
            'remark' => L_('点击查看详情'),
        ];
        return ['msg_data' => $msgData, 'msg_data_wx' => $msgDataWx, 'wxapp_openid' => $wxappOpenid, 'openid' => $openid];
    }

    /**
     * @param $param
     * @return array[]
     * @throws \think\Exception
     * 订单状态变更通知
     */
    public function orderStatusChangeNotice($param)
    {
        $orderId = $param['order_id'] ?? 0;
        $nowOrder = (new MallOrderService())->getOne($orderId);
        if (empty($nowOrder)) {
            throw new \think\Exception(L_("订单不存在"), 1003);
        }
        //订单商品
        $nowGoodsInfo = (new MallOrderDetailService())->getByOrderId('name', $orderId);
        if (empty($nowGoodsInfo)) {
            throw new \think\Exception(L_("订单商品不存在"), 1003);
        }
        $param['goods_name'] = implode(',', array_column($nowGoodsInfo, 'name'));
        $bind = $this->userInfo($nowOrder['uid'], $param['template_key']);
        if (empty($bind)) {
            return false;
        } else {
            $wxappOpenid = $bind['wxapp_openid'];
            $openid = $bind['openid'];
        }
        $msgData = [
            'page' => cfg('site_url') . '/packapp/plat/pages/shopmall_third/orderDetails?order_id=' . $param['order_id'],
            'wecha_id' => $wxappOpenid,
            'thing3' => $param['goods_name'],
            'character_string4' => $nowOrder['order_no'],
            'phrase9' => ($param['status'] >= 20 && $param['status'] < 30) ? '已发货' : '已送达',
            'amount1' => $nowOrder['money_real'],
            'thing2' => '您的订单' . (($param['status'] >= 20 && $param['status'] < 30) ? '已发货' : '已送达') . '请及时关注',
        ];
        $msgDataWx = [
            'href' => cfg('site_url') . '/packapp/plat/pages/shopmall_third/orderDetails?order_id=' . $param['order_id'],
            'wecha_id' => $openid,
            'first' => '亲，您的商品已经在路上了，点击查看商品哦~',
            'keyword1' => date("Y-m-d H:i"),
            'keyword2' => $param['goods_name'],
            'keyword3' => $nowOrder['order_no'],
            'remark' => L_('点击查看详情'),
        ];
        return ['msgData' => $msgData, 'msgDataWx' => $msgDataWx];
    }

    /**
     * @param $param
     * @return array
     * @throws \think\Exception
     * 佣金到账通知
     */
    public function commissionSettle($param)
    {
        $orderId = $param['order_id'] ?? 0;
        $nowOrder = (new MallOrderService())->getOne($orderId);
        if (empty($nowOrder)) {
            throw new \think\Exception(L_("订单不存在"), 1003);
        }
        $bind = $this->userInfo($param['uid'], $param['template_key']);
        if (empty($bind)) {
            return false;
        } else {
            $wxappOpenid = $bind['wxapp_openid'];
            $openid = $bind['openid'];
        }
        $msgData = [
            'page' => cfg('site_url') . '/wap.php?g=Wap&c=My&a=spread_list',
            'wecha_id' => $wxappOpenid,
            'amount4' => $param['price'],//佣金金额
            'time1' => date("Y-m-d H:i"),//到账时间
            'thing5' => $param['type'],//佣金类型
        ];
        $msgDataWx = [
            'href' => cfg('site_url') . '/wap.php?g=Wap&c=My&a=spread_list',
            'wecha_id' => $openid,
            'first' => L_('您的下级用户已完成订单，您获得的佣金已存入账户，点击查看~'),
            'keyword1' => date("Y-m-d H:i"),//时间
            'keyword2' => $param['price'],//佣金金额
            'remark' => L_('点击查看详情'),
        ];
        return ['msg_data' => $msgData, 'msg_data_wx' => $msgDataWx, 'wxapp_openid' => $wxappOpenid, 'openid' => $openid];
    }

    /**
     * @param $param
     * @return array/boolean
     * @throws \think\Exception
     * 积分到账通知
     */
    public function integerSettle($param)
    {
        $bind = $this->userInfo($param['uid'], $param['template_key']);
        if (empty($bind)) {
            return false;
        } else {
            $wxappOpenid = $bind['wxapp_openid'];
            $openid = $bind['openid'];
        }
        //获取总积分
        $scoreInfo = (new UserScoreList())->getOne(['uid' => $param['uid']]);
        if (empty($scoreInfo)) {
            return false;
        }
        $msgData = [
            'page' => cfg('site_url') . '/wap.php?g=Wap&c=My&a=integral',
            'wecha_id' => $wxappOpenid,
            'character_string2' => isset($param['order_no'])?$param['order_no']:"",//订单编号
            'number5' => $param['integer'],//获得积分
            'number6' => $scoreInfo['used_count'],//累计积分
            'phrase10' => $param['reason'],//变更原因
            'date7' => date("Y-m-d H:i"),//交易时间
        ];
        $msgDataWx = [
            'href' => cfg('site_url') . '/wap.php?g=Wap&c=My&a=integral',
            'wecha_id' => $openid,
            'first' => L_('亲，您的积分变动喽，点击查看详情~'),
            'keyword1' => L_('积分变动'),
            'keyword2' => L_('增加积分') . $param['integer'],
            'keyword3' => date("Y-m-d H:i"),
            'remark' => L_('点击查看详情'),
        ];
        return ['msg_data' => $msgData, 'msg_data_wx' => $msgDataWx, 'wxapp_openid' => $wxappOpenid, 'openid' => $openid];
    }

    /**
     * @param $param
     * @return array
     * @throws \think\Exception
     * 秒杀开始通知
     */
    public function seckillRemind($param)
    {
        //商品
        $nowGoodsInfo = (new MallGoods())->getOne($param['goods_id']);
        if (empty($nowGoodsInfo)) {
            throw new \think\Exception(L_("订单商品不存在"), 1003);
        }
        $param['goods_name'] = $nowGoodsInfo['name'];
        $bind = $this->userInfo($param['uid'], $param['template_key']);
        if (empty($bind)) {
            return false;
        } else {
            $wxappOpenid = $bind['wxapp_openid'];
            $openid = $bind['openid'];
        }
        $msgData = [
            'page' => cfg('site_url') . '/packapp/plat/pages/shopmall_third/commodity_details?goods_id=' . $param['goods_id'],
            'wecha_id' => $wxappOpenid,
            'thing1' => $param['goods_name'],//商品名称
            'date2' => date('Y:m:d', $param['start_time']),//开抢日期
            'date3' => date('H:i:s', $param['start_time']),//开抢时间
            'thing4' => L_('请到指定门店抢购')//温馨提示
        ];
        $msgDataWx = [
            'href' => cfg('site_url') . '/packapp/plat/pages/shopmall_third/commodity_details?goods_id=' . $param['goods_id'],
            'wecha_id' => $openid,
            'first' => L_('您关注的商品即将秒杀开始啦！手慢无~'),
            'keyword1' => L_('商品秒杀活动提醒'),
            'keyword2' => L_('秒杀活动即将开始'),
            'keyword3' => date("Y-m-d H:i"),
            'remark' => L_('点击查看详情'),
        ];
        return ['msg_data' => $msgData, 'msg_data_wx' => $msgDataWx, 'wxapp_openid' => $wxappOpenid, 'openid' => $openid];
    }

    /**
     * @param $param
     * @return array
     * @throws \think\Exception
     * scenic秒杀开始通知
     */
    public function seckillScenicRemind($param)
    {
        //商品
        $nowGoodsInfo = (new LifeToolsTicket())->getOne(['ticket_id'=>$param['ticket_id']]);
        if (empty($nowGoodsInfo)) {
            fdump_sql(['ticket_id'=>$param['ticket_id'],'uid'=>$param['uid'],'msg'=>'门票不存在'],'seckillScenicRemindError');
            //throw new \think\Exception(L_("门票不存在"), 1003);
        }else{
            $nowGoodsInfo=$nowGoodsInfo->toArray();
        }
        $param['goods_name'] = $nowGoodsInfo['title'];
        $bind = $this->userInfo($param['uid'], $param['template_key']);
        if (empty($bind)) {
            return false;
        } else {
            $wxappOpenid = $bind['wxapp_openid'];
            $openid = $bind['openid'];
        }
        $msgData = [
            'page' => cfg('site_url') . '/packapp/plat/pages/lifeTools/tools/detail?id=' . $param['ticket_id'],
            'wecha_id' => $wxappOpenid,
            'thing1' => $param['goods_name'],//商品名称
            'date2' => date('Y:m:d', $param['start_time']),//开抢日期
            'date3' => date('H:i:s', $param['start_time']),//开抢时间
            'thing4' => L_('请到指定景区抢购')//温馨提示
        ];
        $msgDataWx = [
            'href' => cfg('site_url') . '/packapp/plat/pages/lifeTools/tools/detail?id=' . $param['ticket_id'],
            'wecha_id' => $openid,
            'first' => L_('您关注的景区门票即将开抢啦！手慢无~'),
            'keyword1' => L_('门票秒杀活动提醒'),
            'keyword2' => L_('秒杀活动即将开始'),
            'keyword3' => date("Y-m-d H:i"),
            'remark' => L_('点击查看详情'),
        ];
        return ['msg_data' => $msgData, 'msg_data_wx' => $msgDataWx, 'wxapp_openid' => $wxappOpenid, 'openid' => $openid];
    }

    /**
     * @param $param
     * @return array
     * @throws \think\Exception
     * 砍价成功通知
     */
    public function bargainSuccess($param)
    {
        $bind = $this->userInfo($param['uid'], $param['template_key']);
        if (empty($bind)) {
            return false;
        } else {
            $wxappOpenid = $bind['wxapp_openid'];
            $openid = $bind['openid'];
        }
        $msgData = [
            'page' => cfg('site_url') . '/packapp/plat/pages/shopmall_third/mybargaining',
            'wecha_id' => $wxappOpenid,
            'thing7' => L_('好友助力成功'),//砍价状态
            'thing1' => $param['goods_name'],//商品名称
            'amount4' => $param['bargain_price'],//砍价金额
            'date5' => date("Y-m-d H:i"),//砍价时间
            'date9' => $param['end_time'],//到期时间
        ];
        $msgDataWx = [
            'href' => cfg('site_url') . '/packapp/plat/pages/shopmall_third/mybargaining',
            'wecha_id' => $openid,
            'first' => '亲，您的商品砍价成功啦~，',
            'keyword1' => $param['goods_name'],
            'keyword2' => L_('砍价成功'),
            'keyword3' => date("Y-m-d H:i"),
            'remark' => L_('点击查看详情'),
        ];
        return ['msg_data' => $msgData, 'msg_data_wx' => $msgDataWx, 'wxapp_openid' => $wxappOpenid, 'openid' => $openid];
    }

    /**
     * @param $param
     * @return array
     * @throws \think\Exception
     * 支付成功通知
     */
    public function friendPaySuccess($param)
    {
        $orderId = $param['order_id'] ?? 0;
        $nowOrder = (new MallOrderService())->getOne($orderId);
        if (empty($nowOrder)) {
            throw new \think\Exception(L_("订单不存在"), 1003);
        }
        //订单商品
        $nowGoodsInfo = (new MallOrderDetailService())->getByOrderId('name', $orderId);
        if (empty($nowGoodsInfo)) {
            throw new \think\Exception(L_("订单商品不存在"), 1003);
        }
        $param['goods_name'] = implode(',', array_column($nowGoodsInfo, 'name'));
        $bind = $this->userInfo($nowOrder['uid'], $param['template_key']);
        if (empty($bind)) {
            return false;
        } else {
            $wxappOpenid = $bind['wxapp_openid'];
            $openid = $bind['openid'];
        }
        $msgData = [
            'page' => cfg('site_url') . '/packapp/plat/pages/shopmall_third/orderDetails?order_id=' . $param['order_id'],
            'wecha_id' => $wxappOpenid,
            'character_string3' => $nowOrder['order_no'],//订单编号
            'thing1' => $param['goods_name'],//商品名称
            'amount2' => $nowOrder['money_real'],//支付金额
            'time4' => date("Y-m-d H:i"),//支付时间
            'time6' => '您的好友已帮您支付订单啦！戳一戳查看~'//温馨提示
        ];
        $msgDataWx = [
            'href' => cfg('site_url') . '/packapp/plat/pages/shopmall_third/orderDetails?order_id=' . $param['order_id'],
            'wecha_id' => $openid,
            'first' => '您的好友已帮您支付订单啦！戳一戳查看~',
            'keyword1' => $param['goods_name'],
            'keyword2' => $nowOrder['order_no'],
            'keyword3' => $nowOrder['money_real'],
            'keyword4' => date("Y-m-d H:i"),
            'remark' => L_('点击查看详情'),
        ];
        return ['msg_data' => $msgData, 'msg_data_wx' => $msgDataWx, 'wxapp_openid' => $wxappOpenid, 'openid' => $openid];
    }

    /**
     * @param $param
     * @return array
     * @throws \think\Exception
     * 退款成功通知
     */
    public function refundSuccess($param)
    {
        $orderId = $param['order_id'] ?? 0;
        $nowOrder = (new MallOrderService())->getOne($orderId);
        if (empty($nowOrder)) {
            throw new \think\Exception(L_("订单不存在"), 1003);
        }
        //退款订单信息
        $nowRefundInfo = (new MallOrderRefund())->getOne(['order_id' => $orderId, 'refund_id' => $param['refund_id']]);
        if (empty($nowRefundInfo)) {
            throw new \think\Exception(L_("订单不存在"), 1003);
        }
        //退款订单商品信息
        $nowGoodsInfo = (new MallOrderRefundDetail())->getJoinData(['r.order_id' => $orderId, 'r.refund_id' => $param['refund_id']]);
        if (empty($nowGoodsInfo)) {
            throw new \think\Exception(L_("订单商品不存在"), 1003);
        }
        $param['goods_name'] = implode(',', array_column($nowGoodsInfo->toArray(), 'name'));
        $bind = $this->userInfo($nowOrder['uid'], $param['template_key']);
        if (empty($bind)) {
            return false;
        } else {
            $wxappOpenid = $bind['wxapp_openid'];
            $openid = $bind['openid'];
        }
        $msgData = [
            'page' => cfg('site_url') . '/packapp/plat/pages/shopmall_third/orderDetails?order_id=' . $param['order_id'],
            'wecha_id' => $wxappOpenid,
            'thing1' => L_('退款成功（店主已同意退款）'),//退款状态
            'thing2' => $param['goods_name'] . ' X ' . $nowRefundInfo['refund_nums'],//商品名称
            'amount3' => $nowRefundInfo['refund_money'],//退款金额
            'date4' => date("Y-m-d H:i"),//退款时间
            'character_string6' => $nowRefundInfo['order_no']//退款单号
        ];
        $msgDataWx = [
            'href' => cfg('site_url') . '/packapp/plat/pages/shopmall_third/orderDetails?order_id=' . $param['order_id'],
            'wecha_id' => $openid,
            'first' => L_('亲，您申请的商品退款已同意，点击查看具体详情~'),
            'keyword1' => $nowRefundInfo['order_no'],
            'keyword2' => $nowRefundInfo['refund_money'],
            'keyword3' => date("Y-m-d H:i"),
            'remark' => L_('点击查看详情'),
        ];
        return ['msg_data' => $msgData, 'msg_data_wx' => $msgDataWx, 'wxapp_openid' => $wxappOpenid, 'openid' => $openid];
    }

    /**
     * @param $param
     * @return array
     * @throws \think\Exception
     * 降价提醒通知
     */
    public function reduceSuccess($param)
    {
        $bind = $this->userInfo($param['uid'], $param['template_key']);
        if (empty($bind)) {
            return false;
        } else {
            $wxappOpenid = $bind['wxapp_openid'];
            $openid = $bind['openid'];
        }
        $msgData = [
            'page' => cfg('site_url') . '/packapp/plat/pages/shopmall_third/commodity_details?goods_id=' . $param['goods_id'],
            'wecha_id' => $wxappOpenid,
            'thing1' => $param['goods_name'],//商品名称
            'amount2' => $param['origin_price'],//原价
            'amount4' => $param['now_price'],//现价
            'date3' => date("Y-m-d H:i"),//降价时间
        ];
        $msgDataWx = [
            'href' => cfg('site_url') . '/packapp/plat/pages/shopmall_third/commodity_details?goods_id=' . $param['goods_id'],
            'wecha_id' => $openid,
            'first' => L_('您关注的商品降价啦！快戳一戳查看吧~'),
            'keyword1' => L_('商品降价提醒'),
            'keyword2' => L_('降价') . ($param['origin_price'] - $param['now_price']),
            'keyword3' => date("Y-m-d H:i"),
            'remark' => L_('点击查看详情'),
        ];
        return ['msg_data' => $msgData, 'msg_data_wx' => $msgDataWx, 'wxapp_openid' => $wxappOpenid, 'openid' => $openid];
    }

    /**
     * @param $param
     * @return array
     * @throws \think\Exception
     * 支付尾款通知
     */
    public function prepareRestPay($param)
    {
        $orderId = $param['order_id'] ?? 0;
        $nowOrder = (new MallOrderService())->getOne($orderId);
        if (empty($nowOrder)) {
            throw new \think\Exception(L_("订单不存在"), 1003);
        }
        //时间展示
        if ($param['rest_type'] === 0) {
            $param['date_txt'] = '请于' . date('Y-m-d H:i:s', $param['rest_start_time']) . '至' . date('Y-m-d H:i:s', $param['rest_end_time']) . '支付尾款哦';
        } elseif ($param['rest_type'] === 1) {
            $param['date_txt'] = '请于' . date('Y-m-d H:i:s', $param['rest_start_time'] + $param['rest_end_time'] + time()) . '之前支付尾款哦';
        }
        $bind = $this->userInfo($nowOrder['uid'], $param['template_key']);
        if (empty($bind)) {
            return false;
        } else {
            $wxappOpenid = $bind['wxapp_openid'];
            $openid = $bind['openid'];
        }
        $msgData = [
            'page' => cfg('site_url') . '/packapp/plat/pages/shopmall_third/orderDetails?order_id=' . $param['order_id'],
            'wecha_id' => $wxappOpenid,
            'amount2' => $param['rest_money'],//尾款金额
            'time3' => $param['date_txt'],//支付尾款时间
        ];
        $msgDataWx = [
            'href' => cfg('site_url') . '/packapp/plat/pages/shopmall_third/orderDetails?order_id=' . $param['order_id'],
            'wecha_id' => $openid,
            'first' => L_('尾款支付提醒'),
            'keyword1' => $param['goods_name'],
            'keyword2' => $param['date_txt'],
            'keyword3' => date("Y-m-d H:i"),
            'remark' => L_('点击查看详情'),
        ];
        return ['msg_data' => $msgData, 'msg_data_wx' => $msgDataWx, 'wxapp_openid' => $wxappOpenid, 'openid' => $openid];
    }

    /**
     * @param $param
     * @throws \think\Exception
     * 0元抽奖中奖、未中奖通知
     */
    public function luckDrawSuccess($param)
    {
        $orderId = $param['order_id'] ?? 0;
        $nowOrder = (new MallOrderService())->getOne($orderId);
        if (empty($nowOrder)) {
            throw new \think\Exception(L_("订单不存在"), 1003);
        }
//        //订单商品
//        $nowGoodsInfo = (new MallOrderDetailService())->getByOrderId('name', $orderId);
//        if (empty($nowGoodsInfo)) {
//            throw new \think\Exception(L_("订单商品不存在"), 1003);
//        }
//        $goodsNames = implode(',', array_column($nowGoodsInfo, 'name'));
        $bind = $this->userInfo($param['uid'], $param['template_key']);
        if (empty($bind)) {
            return false;
        } else {
            $wxappOpenid = $bind['wxapp_openid'];
            $openid = $bind['openid'];
        }
        $msgData = [
            'page' => $param['result'] == 1 ? cfg('site_url') . '/packapp/plat/pages/shopmall_third/orderDetails?order_id=' . $param['order_id'] : cfg('site_url') . '/packapp/plat/pages/shopmall_third/shopmall_index',
            'wecha_id' => $wxappOpenid,
            'phrase1' => $param['result'] == 1 ? '恭喜您中奖啦' : '抱歉您未中奖',
            'thing2' => $param['goods_name'],//商品名称
            'thing10' => $param['result'] == 1 ? '快去查看您的订单吧' : '您可以再看看其他正在活动的商品哦',//成团人数
        ];
        $msgDataWx = [
            'href' => $param['result'] == 1 ? cfg('site_url') . '/packapp/plat/pages/shopmall_third/orderDetails?order_id=' . $param['order_id'] : cfg('site_url') . '/packapp/plat/pages/shopmall_third/shopmall_index',
            'wecha_id' => $openid,
            'first' => L('您参加的0元抽奖活动开奖啦~'),
            'keyword1' => $param['goods_name'],
            'keyword2' => $param['result'] == 1 ? '恭喜您中奖啦' : '抱歉您未中奖',
            'keyword3' => date("Y-m-d H:i"),
            'remark' => L_('点击查看详情'),
        ];
        return ['msg_data' => $msgData, 'msg_data_wx' => $msgDataWx, 'wxapp_openid' => $wxappOpenid, 'openid' => $openid];
    }

    public function userInfo($uid, $templateKey)
    {
        //获取授权信息
        $info = (new User())->getUserById($uid);
        if (!empty($info)) {
            $authority = ['wxapp_openid' => $info['wxapp_openid'], 'openid' => $info['openid']];
            // 验证用户是否授权过
            $where['template_key'] = $templateKey;
            $where['wxapp_openid'] = $authority['wxapp_openid'];
            $bind = (new WxappTemplateBindUserList())->getSome($where);
            if (empty($bind)) {
                return false;
            } else {
                return $authority;
            }
        }
    }

    /**
     * 通知店员
     * @param $order array 订单详情
     * @return bool
     */
    public function staffPushMessage($order)
    {
        // 获得店员信息
        $merchantStoreStaffObj = new MerchantStoreStaffService();
        $staffs = array();
        $where[] = ['store_id', '=', $order['store_id']];
        $where[] = ['is_notice', '=', 0];
        $where[] = ['last_time', '>', 0];
        $staffs = $merchantStoreStaffObj->getStaffListByCondition($where);

        if(empty($staffs)){// 没有店员
            return true;
        }

        $newOrder = false;
        if ($order['status'] == 10) {
            $newOrder = true;
        }

        //查询商城店铺设置
        $storeInfo = (new MerchantStoreMall())->where('store_id',$order['store_id'])->field('new_order_warn')->find();
        if(!$storeInfo){
            return true;
        }
        if($newOrder && $storeInfo['new_order_warn']){// PC店员通知
            $data['business_type'] = 'mall_new';
            $data['operate_type'] = 'new_order';
            $data['platform'] = 'pc';
            $data['store_id'] = $order['store_id'];
            (new WebPushMsgService())->add($data);
            //app店员提醒
            (new AppPushMsgService())->sendMsg($order['store_id'],$order,'mall3');
        }
        return true;
    }
}