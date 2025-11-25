<?php 

namespace app\employee\controller\api;

use app\employee\model\service\EmployeeCardService;

class EmployeeCardController extends ApiBaseController
{

    /**
     * 员工卡列表
     */
    public function cardList()
    {
        $params = [];
        $params['uid'] = $this->_uid;
        try {
            $data = (new EmployeeCardService)->cardList($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success'); 
    }

    /**
     * 员工卡详情
     */
    public function cardDetail()
    {
       $params = [];
       $params['uid'] = $this->_uid;
       $params['card_id'] = $this->request->post('card_id', 0, 'trim,intval'); 
       try {
           $data = (new EmployeeCardService)->cardDetail($params);
       } catch (\Exception $e) {
           return api_output_error(1001, $e->getMessage());
       }
       return api_output(0, $data);
    }

    /**
     * 消息列表
     */
    public function cardLog()
    {
        $params = [];
        $params['uid'] = $this->_uid;  
        $params['page_size'] = $this->request->post('page_size', 10, 'trim,intval'); 
        $params['card_id'] = $this->request->post('card_id', 0, 'trim,intval'); 
        $params['type'] = $this->request->post('type', '', 'trim'); 
        try {
            $data = (new EmployeeCardService)->cardLog($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 会员权益
     */
    public function cardSpecial()
    {
        $params = [];
        $params['uid'] = $this->_uid;  
        $params['card_id'] = $this->request->post('card_id', 0, 'trim,intval'); 
        try {
            $data = (new EmployeeCardService)->cardSpecial($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 个人信息
     */
    public function cardUserInfo()
    {
        $params = [];
        $params['uid'] = $this->_uid;   
        $params['card_id'] = $this->request->post('card_id', 0, 'trim,intval'); 
        try {
            $data = (new EmployeeCardService)->cardUserInfo($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 获取用户协议
     */
    public function getCardUserAgreement()
    {
        $params = [];
        $params['uid'] = $this->_uid;   
        $params['card_id'] = $this->request->post('card_id', 0, 'intval'); 
        try {
            $data = (new EmployeeCardService)->getCardUserAgreement($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 同意/不同意用户协议
     */
    public function agreeCardUserAgreement()
    {
        $params = [];
        $params['uid'] = $this->_uid;   
        $params['card_id'] = $this->request->post('card_id', 0, 'intval'); 
        $params['type'] = $this->request->post('type', 0, 'intval'); 
        try {
            $data = (new EmployeeCardService)->agreeCardUserAgreement($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    
    /**
     * 充值金额选择
     */
    public function getRechargeSelectList()
    {
        $params = [];
        $params['uid'] = $this->_uid; 
        try {
            $data = (new EmployeeCardService)->getRechargeSelectList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
}