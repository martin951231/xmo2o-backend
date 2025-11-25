<?php


namespace app\life_tools\controller\platform;


use app\common\controller\platform\AuthBaseController;
use app\common\model\service\CommonService;
use app\life_tools\model\db\LifeTools;
use app\life_tools\model\service\LifeToolsOrderService;
use app\life_tools\model\service\LifeToolsService;

class LifeToolsController extends AuthBaseController
{
    /**
     * 按条件查询
     * @return \json
     */
    public function getList()
    {
        $params = []; 
        $params['page_size'] = $this->request->post('page_size', 10, 'trim,intval'); 
        $params['keywords'] = $this->request->post('keywords', '', 'trim'); 
        $params['type'] = $this->request->post('type', '', 'trim'); 
        $params['tools_type'] = $this->request->post('tools_type', '', 'trim'); 
        try {
            $data = (new LifeToolsService)->getLifeToolsList($params);
            return api_output(0, $data, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    
    /**
     * 设置属性
     */
    public function setLifeToolsAttrs()
    {
        $params = []; 
        $params['sort'] = $this->request->post('sort', null, 'trim,intval'); 
        $params['status'] = $this->request->post('status', null, 'trim,intval');   
        $params['is_hot'] = $this->request->post('is_hot', null, 'trim,intval');   
        $params['tools_id'] = $this->request->post('tools_id', 0, 'trim,intval');  
        try {
            $data = (new LifeToolsService)->setLifeToolsAttrs($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 获取所有景区
     */
    public function getAllScenic() {
        try {
            $arr = [
                [
                    'tools_id' => 0,
                    'title'    => '---请选择---'
                ]
            ];
            $data = (new LifeTools())->getSome([
                ['type', '=', 'scenic'],
                ['is_del', '=', 0],
                ['status', '=', 1]
            ], 'tools_id,title', 'sort desc, tools_id desc');
            if ($data) {
                $data = $data->toArray();
                $arr  = array_merge($arr, $data);
            }
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $arr);
    }

    /**
     * 获取地图配置信息
     */
    public function getMapConfig()
    {
        $params = [];

        customization('default_map_area_id') && $params['city_id'] = customization('default_map_area_id');
        try {
            $data = (new LifeToolsService)->getMapConfig($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
      
    }

    /**
     * 审核列表
     */
    public function lifeToolsAuditList()
    {
        $params = [];
        $params['tools_type'] = $this->request->post('tools_type', '', 'trim');
        $params['page_size'] = $this->request->post('page_size', 10, 'intval');
        $params['audit_status'] = $this->request->post('audit_status', null, 'intval');
        $params['keywords'] = $this->request->post('keywords', '', 'trim');
        try {
            $data = (new LifeToolsService)->getLifeToolsList($params,'t.add_audit_time DESC,t.add_time DESC');
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 审核
     */
    public function lifeToolsAudit()
    {
        $params = [];
        $params['tools_ids'] = $this->request->post('tools_ids', []);
        $params['audit_status'] = $this->request->post('audit_status', 1, 'intval');
        $params['audit_msg'] = $this->request->post('audit_msg','', 'trim');
        $params['admin_id'] = $this->systemUser['id'];
        try {
            $data = (new LifeToolsService)->lifeToolsAudit($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 获取场馆课程详情
     */
    public function getLifeToolsDetail()
    {
        $params = [];
        $params['tools_id'] = $this->request->post('tools_id', 0, 'trim,intval');
        try {
            $data = (new LifeToolsService)->getLifeToolsDetail($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
 
      /**
     * 伪登录
     */
    public function loginMerchant()
    {
        $mer_id = $this->request->param('mer_id', 0, 'intval');
        $result = (new CommonService())->platformLoginMerchant($mer_id);
        return api_output(0,  $result, 'success');
    }
}