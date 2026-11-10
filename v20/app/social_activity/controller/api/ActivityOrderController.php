<?php

namespace app\social_activity\controller\api;

use app\common\controller\api\ApiBaseController;
use app\social_activity\model\service\SocialActivityOrderService;
use app\social_activity\model\service\SocialActivityPayConfigService;
use app\social_activity\model\db\SocialActivity;

class ActivityOrderController extends ApiBaseController
{
    public function initialize()
    {
        parent::initialize();
        $this->checkLogin();
    }

    public function participants()
    {
        try {
            return api_output(0, (new \app\social_activity\model\service\SocialActivityParticipantService())->options([
                'uid' => $this->uid, 'activity_id' => input('activity_id', 0, 'intval'),
                'village_id' => input('village_id', 0, 'intval'), 'pigcms_id' => input('pigcms_id', 0, 'intval'),
            ]));
        } catch (\Exception $e) { return api_output_error(1003, $e->getMessage()); }
    }

    public function saveOrder()
    {
        $params = [
            'activity_id' => input('activity_id', 0, 'trim,intval'),
            'session_id' => input('session_id', 0, 'trim,intval'),
            'ticket_id' => input('ticket_id', 0, 'trim,intval'),
            'village_id' => input('village_id', 0, 'trim,intval'),
            'buy_date' => input('buy_date', '', 'trim,string'),
            'buy_num' => input('buy_num', 1, 'trim,intval'),
            'uid' => $this->uid,
            'is_save' => input('is_save', 0, 'trim,intval'),
            'form_question' => input('form_question', []),
            'spend_time' => input('spend_time', 0, 'trim,intval'),
            'user_info' => input('user_info', []),
            'pigcms_id' => input('pigcms_id', 0, 'trim,intval'),
            'participants' => input('participants', []),
        ];
        try {
            $res = (new SocialActivityOrderService())->saveOrder($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error($e->getCode() ?: 1003, $e->getMessage());
        }
    }

    /**
     * 获取小程序端支付配置
     * @return \think\response\Json
     */
    public function getPayConfig()
    {
        $activityId = input('activity_id', 0, 'trim,intval');
        if ($activityId <= 0) {
            return api_output_error(1003, '活动ID不能为空');
        }

        try {
            $villageId = (new SocialActivity())->where('id', $activityId)->value('host_village_id');
            if (!$villageId) {
                throw new \Exception('当前活动未配置主办社区, 暂不能报名');
            }
            $config = (new SocialActivityPayConfigService())->getEnabledConfig($villageId);
            return api_output(0, [
                'status' => intval($config['status'] ?? 0),
                'host_village_id' => intval($villageId),
            ]);
        } catch (\Exception $e) {
            return api_output_error($e->getCode() ?: 1003, $e->getMessage());
        }
    }

    /**
     * 用户取主动消订单
     * @return \think\response\Json
     */
    public function refundOrder(){
        $params = [
            'order_id' => input('order_id', 0, 'trim,intval'),
            'is_cancel_activity' => 0,
            'refund_msg' => '用户取主动消订单',
            'uid' => $this->uid,
        ];
        try {
            (new SocialActivityOrderService())->assertCreator($params['order_id'], $this->uid);
            $res = (new \app\social_activity\model\service\SocialActivityRefundService())->apply($params['order_id'], $this->uid);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error($e->getCode() ?: 1003, $e->getMessage());
        }
    }
    public function refundStatus()
    {
        try {
            $res = (new \app\social_activity\model\service\SocialActivityRefundService())->userStatus(input('order_id', 0, 'intval'), $this->uid);
            return api_output(0, $res);
        } catch (\Throwable $e) { return api_output_error(1003, $e->getMessage()); }
    }

}
