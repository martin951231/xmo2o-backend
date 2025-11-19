<?php 

namespace app\employee\controller\api;

use app\employee\model\service\EmployeeCardCouponService;

class EmployeeCardCouponController extends ApiBaseController
{

    /**
     * 发券
     */
    public function sendCoupon()
    {
        $params = []; 
        $params['card_id'] = $this->request->post('card_id', 0, 'trim,intval');
        $params['coupon_id'] = $this->request->post('coupon_id', 0, 'trim,intval');
        $params['mer_id'] = $this->request->post('mer_id', 0, 'trim,intval');
        $params['user_id'] = $this->request->post('user_id', 0, 'trim,intval');
        try {
            $data = (new EmployeeCardCouponService)->sendCoupon($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success'); 
    }

    /**
     * 未核销的消费券转为积分
     */
    public function couponChangeToScore()
    {
        $params = []; 
        $params['card_id'] = $this->request->post('card_id', 0, 'trim,intval');
        $params['coupon_id'] = $this->request->post('coupon_id', 0, 'trim,intval');
        $params['mer_id'] = $this->request->post('mer_id', 0, 'trim,intval');
        try {
            $data = (new EmployeeCardCouponService)->couponChangeToScore($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success'); 
    }
    
    /**
     * 消费券列表
     */
    public function getCouponList()
    {
        $params = []; 
        $params['uid'] = $this->_uid; 
        $params['card_id'] = $this->request->post('card_id', 0, 'trim,intval');
        $params['is_pay'] = $this->request->post('is_pay', 2, 'trim,intval'); //1可手动转积分,2可核销的
        try {
            $data = (new EmployeeCardCouponService)->getCouponList($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success'); 
    }

    /**
     * 消费券转积分
     */
    public function couponTurnScore()
    {
        $params = []; 
        $params['uid'] = $this->_uid; 
        $params['pigcms_id'] = $this->request->post('pigcms_id', 0, 'trim,intval');
        try {
            $data = (new EmployeeCardCouponService)->couponTurnScore($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success'); 
    }

}