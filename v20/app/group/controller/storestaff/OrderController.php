<?php
/**
 * 团购订单管理
 */

namespace app\group\controller\storestaff;
use app\group\model\service\order\GroupPassRelationService;
use app\storestaff\controller\storestaff\AuthBaseController;

class OrderController extends AuthBaseController
{
    /**
     * 核销记录列表
     */
    public function groupCouponList()
    {
        $groupPassRelationService = new GroupPassRelationService();
        $param['store_id'] = $this->staffUser['store_id'];
        $param['page'] = $this->request->param('page',1,'intval');
        $param['pageSize'] = $this->request->param('pageSize',10,'intval');
        $param['keywords'] = $this->request->param('keywords','','trim');
        $param['select_time_type'] = $this->request->param('select_time_type',0,'intval');
        $param['start_time'] = $this->request->param('start_time','','trim');
        $param['end_time'] = $this->request->param('end_time','','trim');
        try {
            $result = $groupPassRelationService->groupCouponList($param);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, $result);
    }

}
