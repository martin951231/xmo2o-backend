<?php
namespace app\life_tools\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\life_tools\model\service\LifeToolsComplaintAdviceService; 

/**
 * 投诉建议
 */
class LifeToolsComplaintAdviceController extends AuthBaseController
{
    /**
     * 投诉建议列表
     */
    public function getComplaintAdviceList()
    {
        $params = [];
        $params['page_size'] = $this->request->post('page_size', 10, 'intval');
        $params['keywords'] = $this->request->post('keywords', '', 'trim');
        $params['is_main'] = $this->request->post('is_main', 0, 'intval');
        try {
            $data = (new LifeToolsComplaintAdviceService)->getComplaintAdviceList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 改变投诉建议状态
     */
    public function changeComplaintAdviceStatus()
    {
        $params = [];
        $params['pigcms_id'] = $this->request->post('pigcms_id', 0, 'intval'); 
        $params['is_main'] = $this->request->post('is_main', 0, 'intval'); 
        try {
            $data = (new LifeToolsComplaintAdviceService)->changeComplaintAdviceStatus($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    
    /**
     * 删除投诉建议
     */
    public function delComplaintAdvice()
    {
        $params = [];
        $params['pigcms_id'] = $this->request->post('pigcms_id', 0, 'intval'); 
        try {
            $data = (new LifeToolsComplaintAdviceService)->delComplaintAdvice($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    
    /**
     * 投诉建议详情
     */
    public function getComplaintAdviceDetail()
    {
        $params = [];
        $params['pigcms_id'] = $this->request->post('pigcms_id', 0, 'intval'); 
        try {
            $data = (new LifeToolsComplaintAdviceService)->getComplaintAdviceDetail($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
}