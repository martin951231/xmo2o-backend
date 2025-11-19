<?php


namespace app\store_marketing\controller\api;


use app\store_marketing\model\service\StoreMarketingOrderService;

class StoreMarketingOrderController extends ApiBaseController
{
    /**
     * @return \json
     * 订单列表
     */
    public function getOrderList()
    {
        $data['uid']=$this->_uid??12;
        $data['page'] = $this->request->param("page", 1, "intval");
        $data['pageSize'] = $this->request->param("pageSize", 10, "intval");
        if(empty($data['uid'])){
            return api_output_error(1002, "获取用户信息失败,请重新登录");
        }

        $ret=(new StoreMarketingOrderService())->getOrderList($data);

        return api_output(0, $ret);
    }
}