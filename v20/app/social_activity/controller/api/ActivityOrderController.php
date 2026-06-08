<?php

namespace app\social_activity\controller\api;

use app\common\controller\api\ApiBaseController;
use app\social_activity\model\service\SocialActivityOrderService;

class ActivityOrderController extends ApiBaseController
{
    public function initialize()
    {
        parent::initialize();
        $this->checkLogin();
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
        ];
        try {
            $res = (new SocialActivityOrderService())->saveOrder($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}
