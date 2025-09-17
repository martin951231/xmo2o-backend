<?php
namespace app\life_tools\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\life_tools\model\service\LifeToolsHelpNoticeService;
use app\life_tools\model\service\LifeToolsInformationService;

/**
 * 寻人求助
 */
class LifeToolsHelpNoticeController extends AuthBaseController
{
    /**
     * 获取求助列表
     */
    public function getHelpNoticeList()
    {
        $params = [];
        $params['page_size'] = $this->request->post('page_size', 10, 'intval');
        $params['keywords'] = $this->request->post('keywords', '', 'trim');
        $params['is_solve'] = $this->request->post('is_solve', 0, 'intval');
        try {
            $data = (new LifeToolsHelpNoticeService)->getHelpNoticeList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 删除寻人求助
     */
    public function delHelpNotice()
    {
        $params = [];
        $params['pigcms_id'] = $this->request->post('pigcms_id', 0, 'intval'); 
        try {
            $data = (new LifeToolsHelpNoticeService)->delHelpNotice($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    
    /**
     * 寻人求助详情
     */
    public function getHelpNoticeDetail()
    {
        $params = [];
        $params['pigcms_id'] = $this->request->post('pigcms_id', 0, 'intval'); 
        try {
            $data = (new LifeToolsHelpNoticeService)->getHelpNoticeDetail($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 改变寻人求助状态
     */
    public function changeHelpNoticeStatus()
    {
        $params = [];
        $params['pigcms_id'] = $this->request->post('pigcms_id', 0, 'intval'); 
        $params['is_solve'] = $this->request->post('is_solve', 0, 'intval'); 
        try {
            $data = (new LifeToolsHelpNoticeService)->changeHelpNoticeStatus($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
}