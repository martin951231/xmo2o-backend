<?php 

namespace app\life_tools\controller\storestaff;

use app\life_tools\model\service\LifeToolsAppointService;
use app\life_tools\model\service\LifeToolsOrderService;
use app\storestaff\controller\storestaff\AuthBaseController;
use app\storestaff\model\service\ScanService;
use app\life_tools\model\service\LifeToolsCardOrderService;

class LifeToolsSportsController extends AuthBaseController
{
 
    /**
     * 体育核销
     */
    public function verification()
    {
        $params = [];
        $params['code'] = $this->request->post('code', '', 'trim');
        try {
            $data = (new ScanService())->indexScan($params, $this->staffUser);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        } 
        return api_output(0, $data);
        
    }
 
    /**
     * 核销列表
     */
    public function verifyList()
    {
        $params = [];
        $params['staff_id'] = $this->staffId;
        $params['mer_id'] = $this->merId;
        $params['order_type'] = $this->request->post('order_type', 'all', 'trim');
        $params['page_size'] = $this->request->post('page_size', 10, 'trim');
        $params['keywords'] = $this->request->post('keywords', '', 'trim');
        $params['search_by'] = $this->request->post('search_by', 1, 'intval');
        $params['start_date'] = $this->request->post('start_date', '', 'trim');
        $params['end_date'] = $this->request->post('end_date', '', 'trim');
        $params['date_by'] = $this->request->post('date_by', 1, 'intval');        
        $params['type'] = $this->request->post('type', '', 'trim');
        $params['tools_type'] = $this->request->post('tools_type', 'sports', 'trim');
        $params['tools_type'] = $params['tools_type'] ?: 'sports';
        $params['staffUser'] = $this->staffUser;
        try {
            if($params['type'] == 'card'){
                $data = (new LifeToolsCardOrderService())->getVerifyList($params);
            }else{
                $data = (new LifeToolsOrderService())->verifyList($params);
            }
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        } 
        return api_output(0, $data);
        
    }
    
}
