<?php 

namespace app\employee\controller\api;

use app\employee\model\service\EmployeeCardOrderService;

class OrderController extends ApiBaseController
{
    // 提交订单
    public function goPay()
    {
        if(empty($this->_uid)){// 验证登录
            return api_output_error(1002);
        }

        $params = [];
        $params['uid'] = $this->_uid;
        $params['nickname'] = $this->userInfo['nickname'];
        $params['phone'] = $this->userInfo['phone'];
        $params['user_id'] = $this->request->param('user_id','0',"intval");
        $params['money'] = $this->request->param('money','0',"trim");
      
        $data = (new EmployeeCardOrderService)->goPay($params);
        return api_output(0, $data, 'success'); 
    }
}