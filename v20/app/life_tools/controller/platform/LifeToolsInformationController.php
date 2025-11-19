<?php
namespace app\life_tools\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\life_tools\model\service\LifeToolsInformationService;

/**
 * 资讯
 */
class LifeToolsInformationController extends AuthBaseController
{
    /**
     * 获取资讯列表
     */
    public function getInformationList()
    {
        $params = [];
        $params['type'] = $this->request->post('type', 'sports', 'trim');//'sports';//体育
        $params['page_size'] = $this->request->post('page_size', 10, 'trim,intval'); 
        $params['keywords'] = $this->request->post('keywords', '', 'trim'); 
        try {
            $data = (new LifeToolsInformationService)->getInformationList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 增加修改资讯
     */
    public function addEditInformation()
    {
        $params = [];
        $params['pigcms_id'] = $this->request->post('pigcms_id', 0, 'trim,intval'); 
        $params['type'] = $this->request->post('type', '', 'trim');//'sports';//体育
        $params['title'] = $this->request->post('title', '', 'trim,htmlspecialchars'); 
        $params['show_type'] = $this->request->post('show_type', 1, 'trim,intval'); 
        $params['start_time'] = $this->request->post('start_time', '', 'trim'); 
        $params['end_time'] = $this->request->post('end_time', '', 'trim'); 
        $params['content'] = $this->request->post('content', '', 'trim');  
        $params['images'] = $this->request->post('images', '', 'trim');
        $params['tools_id'] = $this->request->post('tools_id', 0, 'intval');
        try {
            $data = (new LifeToolsInformationService)->addEditInformation($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 获取资讯详情
     */
    public function getInformationDetail()
    {
        $params = [];
        $params['pigcms_id'] = $this->request->post('pigcms_id', 0, 'trim,intval');  
        try {
            $data = (new LifeToolsInformationService)->getInformationDetail($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 删除资讯
     */
    public function delInformation()
    {
        $params = [];
        $params['pigcms_id'] = $this->request->post('pigcms_id', 0, 'trim,intval');  
        try {
            $data = (new LifeToolsInformationService)->delInformation($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
}