<?php

namespace app\merchant\controller\merchant;

use app\merchant\controller\merchant\AuthBaseController;
use app\merchant\model\service\pay\PayService;

class PayController extends AuthBaseController
{

    /**
     * 去支付
     * @author: 衡婷妹
     * @date: 2021/09/02
     */
    public function goPay()
    {
        $param['order_id'] = $this->request->param('order_id', 0, 'intval');
        $param['order_type'] = $this->request->param('order_type', '', 'string');
        $param['pay_type'] = $this->request->param('pay_type', '', 'string');
        $param['is_mobine'] = $this->request->param('is_mobine', '0', 'string');
        $param['mer_id'] = $this->merId;
        $openid = $this->request->param('openid', 0, 'string');//1=手机端
        
        if($openid){
            $_SESSION['openid'] = $openid;
        }
        $rs = (new PayService)->goPay($param);
        return api_output(0, $rs);
    }

}
