<?php


namespace app\recruit\controller\api;


use app\recruit\model\service\RecruitJobDeliveryService;

class JobDeliveryController extends ApiBaseController
{
    /**
     * 投递记录
     */
    public function deliveryList()
    {
        $param['uid'] = $this->_uid;
        if (!$param['uid']) {
            return api_output_error(1002, L_('请登录'));
        }
        $data['page']=$this->request->param('page', '1', 'intval');
        $data['pageSize']=$this->request->param('pageSize', '10', 'intval');
        $list=(new RecruitJobDeliveryService())->deliveryList($param['uid'],$data['page'],$data['pageSize']);
        return api_output(0, $list);
    }


}