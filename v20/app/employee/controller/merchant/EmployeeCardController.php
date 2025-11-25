<?php


namespace app\employee\controller\merchant;

use app\employee\model\service\EmployeeCardService;
use app\employee\model\service\ExportService;
use app\merchant\controller\merchant\AuthBaseController;

class EmployeeCardController extends AuthBaseController
{
    /**
     * 员工卡列表
     */
   public function getCardList(){
       $params['name'] = $this->request->post('name', '', 'trim');
       $params['mer_id'] =$this->merId;
       try {
           $list=(new EmployeeCardService())->getCardList($params);
       } catch (\Exception $e) {
           return api_output_error(1001, $e->getMessage());
       }
       return api_output(0, $list);
   }

    /**
     * 卡优惠券列表
     */
    public function getCouponList(){
        $params['card_id'] = $this->request->post('card_id', 0, 'intval');
        try {
            $list=(new EmployeeCardService())->getCouponList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $list);
    }

    /**
     * 员工卡编辑
     */
    public function editCard(){
        $params['mer_id'] = $this->merId;
        try {
            if(empty($params['mer_id'])){
                return api_output_error(1001, "缺少必要参数");
            }else{
                $list=(new EmployeeCardService())->editCard($params);
            }
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $list);
    }

    /**
     * 优惠券编辑
     */
    public function editCoupon(){
        $params['pigcms_id'] = $this->request->post('pigcms_id', 0, 'intval');
        try {
            $list=[];
            if(!empty($params['pigcms_id'])){
                $list=(new EmployeeCardService())->editCoupon($params);
            }
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $list);
    }

    /**
     * 员工卡保存
     */
    public function saveCard(){
        $params['mer_id'] =$this->merId;
        $params['card_id'] = $this->request->post('card_id', 0, 'intval');
        $params['name'] = $this->request->post('name', '', 'trim');
        $params['description'] = $this->request->post('description', '', 'trim');
        $params['bg_image'] = $this->request->post('bg_image', '', 'trim');
        $params['bg_color'] = $this->request->post('bg_color', '', 'trim');
        $params['status'] = $this->request->post('status', 1, 'intval');
        $params['store'] = $this->request->post('store', '', 'trim');
        $params['user_agreement'] = $this->request->post('user_agreement', '', 'trim');
        $params['clear_date'] = $this->request->post('clear_date', 1, 'intval');
        $params['clear_notice_date'] = $this->request->post('clear_notice_date', 0, 'intval');
        $params['clear_score'] = $this->request->post('clear_score', 0, 'intval');
        $params['clear_time'] = $this->request->post('clear_time', '00:00', 'trim');
        $params['clear_week'] = $this->request->post('clear_week', 1, 'intval');
        $params['clear_time'] = date('H:i', strtotime($params['clear_time'])) ?: '00:00';
        $params['pay_merchants'] = $this->request->post('pay_merchants', '');
        $params['is_balance_pay'] = $this->request->post('is_balance_pay', 1, 'intval');
        $params['is_score_pay'] = $this->request->post('is_score_pay', 1, 'intval');
        $params['pay_store'] = $this->request->post('pay_store', []);
        try {
            $ret=(new EmployeeCardService())->saveCard($params);
            if(empty($ret)){
                return api_output_error(1001, "保存失败");
            }else{
                return api_output(0, $ret);
            }
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 优惠券保存
     */
    public function saveCoupon()
    {
        $params['mer_id'] =$this->merId;
        $params['card_id'] = $this->request->post('card_id', 0, 'intval');
        $params['pigcms_id'] = $this->request->post('pigcms_id', 0, 'intval');
        $params['name'] = $this->request->post('name', '', 'trim');
        $params['start_time'] = $this->request->post('start_time', '', 'trim');
        $params['end_time'] = $this->request->post('end_time', '', 'trim');
        $params['send_num'] = $this->request->post('send_num', 0, 'intval');
        $params['money'] = $this->request->post('money', 0, 'intval');
        $params['add_score_num'] = $this->request->post('add_score_num', 0, 'intval');
        $params['deduct_money'] = $this->request->post('deduct_money', 0, 'intval');
        $params['status'] = $this->request->post('status', 0, 'intval');
        $params['coupon_price'] = $this->request->post('coupon_price', 0, 'trim');
        $params['label_ids'] = $this->request->post('label_ids');
        $params['send_by'] = $this->request->post('send_by', 0, 'intval');
        $params['clickDates'] = $this->request->post('clickDates');
        $params['send_rule'] = $this->request->post('send_rule');
        $params['send_dates'] = $this->request->post('send_dates');
        $params['send_week'] = $this->request->post('send_week');
        $params['overdue_time'] = $this->request->post('overdue_time', '', 'trim');
        $params['is_auto_turn_score'] = $this->request->post('is_auto_turn_score', 0, 'intval');
        try {
            $ret=(new EmployeeCardService())->saveCoupon($params);
            if(empty($ret)){
                return api_output_error(1001, "保存失败");
            }else{
                return api_output(0, []);
            }
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 员工卡删除
     */
    public function delCoupon(){
        $params['pigcms_id'] = $this->request->post('pigcms_id', 0, 'intval');
        try {
            if(empty($params['pigcms_id'])){
                return api_output_error(1001, "缺少必要参数");
            }else{
                $ret=(new EmployeeCardService())->delCoupon($params);
                if(!empty($ret)){
                    return api_output(0, []);
                }else{
                    return api_output_error(1001, "删除失败");
                }
            }
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }

    } 
    
    /**
     * 核销列表
     */
    public function cardLogList()
    {
        $params = [];
        $params['mer_id'] =$this->merId;
        $params['page_size'] = $this->request->post('pageSize', 10, 'intval');
        $params['keywords'] = $this->request->post('keywords', '', 'trim');
        $params['search_by'] = $this->request->post('search_by', 0, 'intval');
        $params['start_date'] = $this->request->post('start_date', '', 'trim');
        $params['end_date'] = $this->request->post('end_date', '', 'trim');
        $params['type'] = $this->request->post('type', 'coupon', 'trim');
        $params['verify_type'] = $this->request->post('verify_type', 0, 'intval');
        try {
            $data = (new EmployeeCardService)->cardLogListNew($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    
    /**
     * 导出
     */
	public function export(){
        $params = [];
        $params['mer_id'] = $this->merId;
        $params['request_type'] = 'export';
        $params['keywords'] = $this->request->post('keywords', '', 'trim');
        $params['search_by'] = $this->request->post('search_by', 0, 'intval');
        $params['start_date'] = $this->request->post('start_date', '', 'trim');
        $params['end_date'] = $this->request->post('end_date', '', 'trim');
        $params['type'] = $this->request->post('type', 'coupon', 'trim');
        $params['verify_type'] = $this->request->post('verify_type', 0, 'intval');
        try {
            $result = (new ExportService())->addExport($params); 
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 开启关闭使用余额消费
     */
    public function isOpenUseMoney()
    {
        $params = [];
        $params['mer_id'] = $this->merId;
        $params['pigcms_id'] = $this->request->post('pigcms_id', 0, 'intval');
        $params['status'] = $this->request->post('status', 0, 'intval');
        try {
            $data = (new EmployeeCardService)->isOpenUseMoney($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data); 
    }

    /**
     * 获取发券日期
     */
    public function getSendCouponDateList()
    {
        $params = [];
        $params['mer_id'] = $this->merId;
        $params['pigcms_id'] = $this->request->post('pigcms_id', 0, 'intval');
        $params['time'] = $this->request->post('time', 0, 'intval');
        try {
            $data = (new EmployeeCardService)->getSendCouponDateList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data); 
    }

    /**
     * 计算发券日期
     */
    public function getCalcDateList()
    {
        $params = [];
        $params['mer_id'] = $this->merId;
        $params['time'] = $this->request->post('time', 0, 'intval');
        $params['send_by'] = $this->request->post('send_by', 0, 'intval');
        $params['send_rule'] = $this->request->post('send_rule');
        try {
            $data = (new EmployeeCardService)->getCalcDateList($params['time'] ?: time(), $params['send_by'], $params['send_rule']);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data); 
    }

    /**
     * 清理积分记录
     */
    public function getClearScoreList()
    {
        $params = [];
        $params['mer_id'] = $this->merId;
        $params['page_size'] = $this->request->post('page_size', 10, 'intval');
        $params['start_date'] = $this->request->post('start_date', '', 'trim');
        $params['end_date'] = $this->request->post('end_date', '', 'trim');
        try {
            $data = (new EmployeeCardService)->getClearScoreList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data); 
    }
}