<?php 

namespace app\employee\controller\api;
 
use app\employee\model\service\EmployeePayCodeService;

class EmployeePayCodeController extends ApiBaseController
{

    /**
     * 付款码详情
     */
    public function payCodeDetail()
    {
       $params['type'] = $this->request->post('type', 1, 'trim,intval');
       $params['card_id'] = $this->request->post('card_id', 0, 'trim,intval');
       $params['uid'] = $this->_uid;
       try {
           $data = (new EmployeePayCodeService)->payCodeDetai($params);
       } catch (\Exception $e) {
           return api_output_error(1001, $e->getMessage());
       }
       return api_output(0, $data);
    }


    /**
     * 查询支付状态
     */
    public function payCodeStatus()
    {
        $params['pay_code_id'] = $this->request->post('pay_code_id', 0, 'trim,intval');
        $params['uid'] = $this->_uid;
        try {
            $data = (new EmployeePayCodeService)->payCodeStatus($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data, $data['msg']);
    }

    /**
     * 同意用积分或余额支付
     */
    public function useScoreOrMoneyPay()
    {
        $params['pay_code_id'] = $this->request->post('pay_code_id', 0, 'trim,intval');
        $params['uid'] = $this->_uid;
        try {
            $data = (new EmployeePayCodeService)->useScoreOrMoneyPay($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, true);
    }
}
