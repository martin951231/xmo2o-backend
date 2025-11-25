<?php
/** 
 * 店员端员工卡付款码核销控制器 
 */

namespace app\employee\controller\storestaff;

use app\employee\model\service\EmployeePayCodeService;
use app\storestaff\controller\storestaff\AuthBaseController;

class EmployeePayCodeController extends AuthBaseController
{
    public function deductions()
    {
        $params = [];
        $params['mer_id'] =  $this->merId;
        $params['staff'] = $this->staffUser;
        $params['staff_id'] = $this->staffId;
        $params['code'] = $this->request->post('code', '', 'trim');
        $params['score'] = $this->request->post('score', 0, 'trim,intval');
        $params['remark'] = $this->request->post('remark', '', 'trim');
        $params['operate_type'] = $this->request->post('operate_type', 'staff', 'trim');
        $params['card_type'] = $this->request->post('card_type', 'coupon', 'trim'); 
        $params['pay_sort'] = $this->request->post('pay_sort', 0, 'intval'); //支付顺序 0=先积分后余额，1=先余额后积分
        $params['money'] = $this->request->post('money', 0, 'trim'); 
        $params['code'] = rtrim($params['code'], '?'); 
        try {
            $data = (new EmployeePayCodeService)->deductions($params);
            fdump_sql([$this->request->param(), $this->staffId, $data], 'EmployeePayCode_deductions');
        } catch (\Exception $e) {
            fdump_sql([$this->request->param(), $this->staffId,$e->getMessage()], 'EmployeePayCode_deductions');
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
}