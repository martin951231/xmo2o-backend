<?php

/**
 * 团体票管理
 */
namespace app\life_tools\controller\merchant;

use app\life_tools\model\service\group\LifeToolsGroupSettingService;
use app\life_tools\model\service\group\LifeToolsGroupTicketService;
use app\merchant\controller\merchant\AuthBaseController;

class GroupController extends AuthBaseController
{
     /**
     * 获取已选中景区门票列表
     */
    public function getGroupTicketList()
    {
        $params = []; 
        $params['pageSize'] = $this->request->post('pageSize', 10, 'trim,intval'); 
        $params['page'] = $this->request->post('page', 1, 'trim,intval'); 
        $params['keyword'] = $this->request->post('keyword', '', 'trim'); 
        $params['search_type'] = $this->request->post('search_type', '', 'trim'); 
        $params['search_keyword'] = $this->request->post('search_keyword', '', 'trim'); 
        $params['mer_id'] = $this->merId;
        try {
            $data = (new LifeToolsGroupTicketService)->getTicketList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    
     /**
     * 添加团购票
     */
    public function addGroupTicket()
    {
        $params = []; 
        $params['seleted_ticket_list'] = $this->request->post('seleted_ticket_list', '', 'trim'); 
        $params['mer_id'] = $this->merId;

        try {
            $data = (new LifeToolsGroupTicketService)->addGroupTicket($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    
    /**
    * 添加团购票
    */
   public function editGroupTicket()
   {
       $params = []; 
       $params['id'] = $this->request->post('id', '', 'intval');
       $params['type'] = $this->request->post('type', '', 'trim'); 
       $params['max_num'] = $this->request->post('max_num', '', 'trim'); 
       $params['group_price'] = $this->request->post('group_price', '', 'trim'); 
       $params['mer_id'] = $this->merId;

       try {
           $data = (new LifeToolsGroupTicketService)->editGroupTicket($params);
       } catch (\Exception $e) {
           return api_output_error(1001, $e->getMessage());
       }
       return api_output(0, $data);
   }
    
     /**
     * 添加团购票
     */
    public function delGroupTicket()
    {
        $params = []; 
        $params['id'] = $this->request->post('id', '', 'trim'); 
        $params['mer_id'] = $this->merId;

        try {
            $data = (new LifeToolsGroupTicketService)->delGroupTicket($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    
    
     /**
     * 获得配置详情
     */
    public function getSettingDataDetail()
    {
        $params = []; 
        $params['mer_id'] = $this->merId;

        try {
            $data = (new LifeToolsGroupSettingService)->getDataDetail($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    
    /**
     * 获得配置详情
     */
    public function editSettingData()
    {
        $params = []; 
        $params['travel_agency_audit'] = $this->request->post('travel_agency_audit', '', 'trim'); 
        $params['buy_audit'] = $this->request->post('buy_audit', '', 'trim'); 
        $params['expiration_time'] = $this->request->post('expiration_time', '', 'trim'); 
        $params['travel_agency_custom_form'] = $this->request->post('travel_agency_custom_form', '', 'trim'); 
        $params['tour_guide_custom_form'] = $this->request->post('tour_guide_custom_form', '', 'trim'); 
        $params['tourists_custom_form'] = $this->request->post('tourists_custom_form', '', 'trim'); 
        $params['mer_id'] = $this->merId;

        try {
            $data = (new LifeToolsGroupSettingService)->editData($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

}