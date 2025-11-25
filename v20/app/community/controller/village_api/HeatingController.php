<?php
/**
 * 供暖功能管理
 */

namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HeatingService;
use app\community\model\service\HouseNewChargeRuleService;
use app\traits\house\AdminLoginTraits;

class HeatingController extends CommunityBaseController
{
    use AdminLoginTraits;
    /**
     * 申请停暖审核列表
     */
    public function closeHeatingList()
    {
        $param['village_id'] =  $this->adminUser['village_id'];
        $param['page'] = $this->request->param('page',0,'int');
        $param['limit']=$this->request->param('pageSize',10,'int');
        $param['name']= $this->request->param('name','','trim');
        $param['phone']= $this->request->param('phone','','trim');
        $param['audit_status']= $this->request->param('audit_status',-1,'trim');
        $param['is_measure_up']= $this->request->param('is_measure_up',-1,'trim');
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        $param['adminUser'] = [];
        $param['login_role'] = $this->login_role;
        $param['dismissPermissionRole'] = $this->dismissPermissionRole;
        try{
            $list = (new HeatingService())->closeHeatingList($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    
    /**
     * 新增申请停暖
     */
    public function addCloseHeating()
    {
        $param['village_id'] =  $this->adminUser['village_id'];
        $param['room_id'] = $this->request->param('room_id',0,'trim');
        $param['name']= $this->request->param('name','','trim');
        $param['phone']= $this->request->param('phone','','trim');
        $param['application_time']=$this->request->param('application_time',time(),'trim');
        $param['house_property_evidence_img']= $this->request->param('house_property_evidence_img','','trim');
        $param['id_card_img']= $this->request->param('id_card_img','','trim');
        $param['id_card_back_img']= $this->request->param('id_card_back_img','','trim');
        
        $param['audit_status'] = $this->request->param('audit_status',0,'trim');
        $param['user_type'] = $this->request->param('user_type',0,'trim');
        $param['audit_msg'] = $this->request->param('audit_msg','','trim');
        $param['is_measure_up'] = $this->request->param('is_measure_up',0,'trim');
        $param['measure_up_msg'] = $this->request->param('measure_up_msg','','trim');
        $param['setChargeStandardBindId'] = $this->request->param('setChargeStandardBindId',0,'intval');
        $param['setProjectId'] = $this->request->param('setProjectId',0,'intval');
        $param['setRulesId'] = $this->request->param('setRulesId',0,'intval');
        $param['service_start_time'] = $this->request->param('service_start_time','','trim');
        $param['service_end_time'] = $this->request->param('service_end_time','','trim');
        $role_id = 0;
        if (in_array($this->login_role, $this->villageOrderCheckRole)) {
            $role_id = $this->_uid;
        }
        $param['role_id'] = $role_id;
        $param['roleData'] = [
            'uid' => $this->_uid, 'login_role' => $this->login_role, 'adminUser' => $this->adminUser,
        ];
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $list = (new HeatingService())->addCloseHeating($param,false);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    
    /**
     * 查看申请停暖详情
     */
    public function getCloseHeatingInfo()
    {
        $param['village_id'] =  $this->adminUser['village_id'];
        $param['pigcms_id'] = $this->request->param('pigcms_id',0,'trim');
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        $param['return_base'] = 0;
        try{
            $list = (new HeatingService())->getCloseHeatingInfo($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    
    /**
     * 审核停暖申请
     */
    public function auditCloseHeating()
    {
        $userId = trim($this->request->log_uid);
        $login_role = trim($this->request->log_extends);
        $parseInfo = $this->traitPaseTokenRequestWhoim($userId,$login_role);
        $param['village_id'] =  $this->adminUser['village_id'];
        $param['audit_status'] = $this->request->param('audit_status',0,'trim');
        $param['audit_msg'] = $this->request->param('audit_msg','','trim');
        $param['user_type'] = $this->request->param('user_type',0,'trim');
        $param['pigcms_id'] = $this->request->param('pigcms_id',0,'trim');
        $param['operate_type'] = $parseInfo['op_type'] ?? 0;
        $param['operate_uid'] = $parseInfo['op_id'] ?? 0;
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $list = (new HeatingService())->auditCloseHeating($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    
    /**
     * 检查停暖申请
     */
    public function inspectCloseHeating()
    {
        $userId = trim($this->request->log_uid);
        $login_role = trim($this->request->log_extends);
        $param['village_id'] =  $this->adminUser['village_id'];
        $parseInfo = $this->traitPaseTokenRequestWhoim($userId,$login_role);
        $param['pigcms_id'] = $this->request->param('pigcms_id',0,'trim');
        $param['is_measure_up'] = $this->request->param('is_measure_up',0,'trim');
        $param['measure_up_msg'] = $this->request->param('measure_up_msg','','trim');
        $param['measure_up_img'] = $this->request->param('measure_up_img','','trim');
        $param['operate_type'] = $parseInfo['op_type'] ?? 0;
        $param['operate_uid'] = $parseInfo['op_id'] ?? 0;
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        $role_id = 0;
        if (in_array($this->login_role, $this->villageOrderCheckRole)) {
            $role_id = $this->_uid;
        }
        $param['role_id'] = $role_id;
        $param['roleData'] = [
            'uid' => $this->_uid, 'login_role' => $this->login_role, 'adminUser' => $this->adminUser,
        ];
        try{
            $list = (new HeatingService())->inspectCloseHeating($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    /**
     * 账单提醒设置
     */
    public function setOrderNotice()
    {
        $param=array();
        $param['village_id'] =  $this->adminUser['village_id'];
        $param['is_notice'] = $this->request->param('is_notice',0,'trim');
        $param['notice_date'] = $this->request->param('notice_date',0,'trim');
        $param['work_time'] = $this->request->param('work_time','','trim');
        $param['stop_heating_start'] = $this->request->param('stop_heating_start','','trim');
        $param['stop_heating_end'] = $this->request->param('stop_heating_end','','trim');
        $xtype = $this->request->param('xtype','','trim');
        $rule_id = $this->request->param('rule_id','');
        if($xtype=='notice'){
            $param['rule_id']=!empty($rule_id) ? json_encode($rule_id,JSON_UNESCAPED_UNICODE):'';
        }
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $list = (new HeatingService())->setOrderNotice($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    public function flowChargSet()
    {
        $param=array();
        $param['village_id'] =  $this->adminUser['village_id'];
        $param['order_yh_per'] = $this->request->param('order_yh_per',0,'trim');
        $param['unit_price_base'] = $this->request->param('unit_price_base',0,'trim');
        $apply_start_day= $this->request->param('apply_start_day','','trim');
        $apply_end_day = $this->request->param('apply_end_day','','trim');
        $xtype = $this->request->param('xtype','','trim');
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        $param['xtype']=$xtype;
        if(empty($apply_start_day) || empty($apply_end_day)){
            return api_output_error(1001,'请设置申请流量计费时间范围');
        }
        $param['apply_start_day'] =$apply_start_day;
        $param['apply_end_day'] =$apply_end_day;
        try{
            $list = (new HeatingService())->setFlowChargSet($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    
    /**
     * 查看停暖协议
     */
    public function getCloseHeatingProtocol()
    {
        $param['village_id'] =  $this->adminUser['village_id'];
        $xtype = $this->request->param('xtype','','trim');
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $list = (new HeatingService())->getHeatingProtocol($param);
            if($xtype=='notice'){
                $property_id=$this->adminUser['property_id'];
                $houseNewChargeRuleService= new HouseNewChargeRuleService();
                $ruleData=$houseNewChargeRuleService->getChargeTypeRuleList($this->adminUser['village_id'],['new_nuanqifei','heating'],$property_id);
                $list['ruleData']=$ruleData;
            }

        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    
    /**
     * 编辑停暖协议
     */
    public function editCloseHeatingProtocol()
    {
        $param['is_open'] = $this->request->param('is_open',0,'trim');
        $param['title'] = $this->request->param('title','','trim');
        $param['content'] = $this->request->param('content','','trim');
        $param['village_id'] =  $this->adminUser['village_id'];
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $list = (new HeatingService())->editHeatingProtocol($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 删除停暖申请
     */
    public function delCloseHeating()
    {
        $userId = trim($this->request->log_uid);
        $login_role = trim($this->request->log_extends);
        $parseInfo = $this->traitPaseTokenRequestWhoim($userId,$login_role);
        $param['operate_type'] = $parseInfo['op_type'] ?? 0;
        $param['operate_uid'] = $parseInfo['op_id'] ?? 0;
        $param['village_id'] =  $this->adminUser['village_id'];
        $param['pigcms_id'] = $this->request->param('pigcms_id',0,'trim');
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $list = (new HeatingService())->delCloseHeating($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 申请流量计费列表
     */
    public function flowChargingList()
    {
        $param['village_id'] =  $this->adminUser['village_id'];
        $param['page'] = $this->request->param('page',0,'int');
        $param['limit']=$this->request->param('pageSize',10,'int');
        $param['name']= $this->request->param('name','','trim');
        $param['phone']= $this->request->param('phone','','trim');
        $param['audit_status']= $this->request->param('audit_status',-1,'trim');
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        $param['adminUser'] = $this->adminUser;
        $param['login_role'] = $this->login_role;
        $param['dismissPermissionRole'] = $this->dismissPermissionRole;
        try{
            $list = (new HeatingService())->flowChargingList($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 新增申请流量计费
     */
    public function addFlowCharging()
    {
        $param['village_id'] =  $this->adminUser['village_id'];
        $param['room_id'] = $this->request->param('room_id',0,'trim');
        $param['name']= $this->request->param('name','','trim');
        $param['phone']= $this->request->param('phone','','trim');
        $param['application_time']=$this->request->param('application_time','','trim');
        $param['house_property_evidence_img']= $this->request->param('house_property_evidence_img','','trim');
        $param['id_card_img']= $this->request->param('id_card_img','','trim');
        $param['id_card_back_img']= $this->request->param('id_card_back_img','','trim');
        $param['audit_status']= $this->request->param('audit_status',0,'intval');
        $param['audit_msg']= $this->request->param('audit_msg','','intval');
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $list = (new HeatingService())->addFlowCharging($param,false);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 查看申请流量计费详情
     */
    public function getFlowChargingInfo()
    {
        $param['village_id'] = $this->adminUser['village_id'];
        $param['pigcms_id'] = $this->request->param('pigcms_id',0,'trim');
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        $param['return_base'] = 0;
        try{
            $list = (new HeatingService())->getFlowChargingInfo($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 审核流量计费申请
     */
    public function auditFlowCharging()
    {
        $userId = trim($this->request->log_uid);
        $login_role = trim($this->request->log_extends);
        $parseInfo = $this->traitPaseTokenRequestWhoim($userId,$login_role);
        $param['village_id'] =  $this->adminUser['village_id'];
        $param['audit_status'] = $this->request->param('audit_status',0,'trim');
        $param['audit_msg'] = $this->request->param('audit_msg','','trim');
        $param['user_type'] = $this->request->param('user_type',0,'trim');
        $param['pigcms_id'] = $this->request->param('pigcms_id',0,'trim');
        $param['operate_type'] = $parseInfo['op_type'] ?? 0;
        $param['operate_uid'] = $parseInfo['op_id'] ?? 0;
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $list = (new HeatingService())->auditFlowCharging($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 删除流量计费申请
     */
    public function delFlowCharging()
    {
        $userId = trim($this->request->log_uid);
        $login_role = trim($this->request->log_extends);
        $parseInfo = $this->traitPaseTokenRequestWhoim($userId,$login_role);
        $param['operate_type'] = $parseInfo['op_type'] ?? 0;
        $param['operate_uid'] = $parseInfo['op_id'] ?? 0;
        $param['village_id'] =  $this->adminUser['village_id'];
        $param['pigcms_id'] = $this->request->param('pigcms_id',0,'trim');
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $list = (new HeatingService())->delFlowCharging($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 查看流量计费申请协议
     */
    public function getFlowChargingProtocol()
    {
        $param['village_id'] =  $this->adminUser['village_id'];
        $xtype = $this->request->param('xtype','','trim');
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        $param['type'] = 1;
        $param['xtype']=$xtype;
        try{
            $list = (new HeatingService())->getHeatingProtocol($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 编辑流量计费申请协议
     */
    public function editFlowChargingProtocol()
    {
        $param['is_open'] = $this->request->param('is_open',0,'trim');
        $param['title'] = $this->request->param('title','','trim');
        $param['content'] = $this->request->param('content','','trim');
        $param['village_id'] =  $this->adminUser['village_id'];
        if(!$param['village_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        $param['type'] = 1;
        try{
            $list = (new HeatingService())->editHeatingProtocol($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    
    /**
     * 查询是否是流量计费房间
     */
    public function getFlowChargingRoom()
    {
        $param=array();
        $param['room_id'] = $this->request->param('room_id',0,'trim');
        $param['village_id'] =  $this->adminUser['village_id'];
        $xtype= $this->request->param('xtype','','trim');
        try{
            $heatingService= new HeatingService();
            $list = $heatingService->getFlowCharging($param);
            if($xtype=='get_bind_project'){
                //获取绑定的 标准信息
                $bindRule=$heatingService->getRoomBindProjectRule($param['room_id'],$param['village_id'],'new_nuanqifei');
                $list['bindRules']=$bindRule;
            }
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 获取登录人信息
     */
    public function getRoomInfo()
    {
        $param['room_id'] =  $this->request->param('room_id', 0,'intval');
        if(!$param['room_id']){
            return api_output_error(1001,'缺少必传参数');
        }
        $village_id =  $this->adminUser['village_id'];
        try{
            $list = (new HeatingService())->getRoomInfo($param,$village_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
}