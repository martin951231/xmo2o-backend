<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      挂单操作
 */

namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\ConfigService;
use app\community\model\service\HouseListDatasExportOutService;
use app\community\model\service\HouseNewCashierService;
use app\community\model\service\HousePropertyDigitService;
use app\community\model\service\HouseVillageCheckauthSetService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageUserVacancyService;
use app\community\model\service\NewPayService;
use app\community\model\service\order\HouseNewOrderHangService;

class CashierHangOrderController extends CommunityBaseController
{
    /**
     * 挂单操作.
     */
    public function hangOrder()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $order_id = $this->request->post('order_id', 0, 'intval');
        $opt_type= $this->request->post('xtype', '', 'trim');  //batch_opt 批量挂单操作
        $order_ids= $this->request->post('hang_order_ids', '');  //batch_opt 批量挂单操作的订单id
        $data = [];
        try {
            $param = [
                'village_id' => $village_id,
                'order_id' => $order_id,
                'order_ids'=>$order_ids,
                'opt_type'=>$opt_type,
            ];
            $result = (new HouseNewOrderHangService())->hangOrder($param);
            if (isset($result['error_code']) && isset($result['msg']) && $result['error_code']) {
                return api_output_error(-1, $result['msg']);
            }
            $data['result'] = $result;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 获取挂单订单.
     */
    public function getHangPayOrders() {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $page = $this->request->post('page', 1);
        $limit = $this->request->post('limit', 10);
        $garage_id = $this->request->post('garage_id', 0);
        $project_id = $this->request->post('project_id', 0);
        $rule_id = $this->request->post('rule_id', 0, 'intval');
        $position_num = $this->request->post('position_num', '');
        $room_id = $this->request->post('room_id', 0);
        $pigcms_id = $this->request->post('pigcms_id', 0);
        $order_status = $this->request->post('order_type', 0);
        $service_start_time = $this->request->post('service_start_time', '', 'trim');
        $service_end_time = $this->request->post('service_end_time', '', 'trim');
        $position_id = $this->request->post('position_id', 0,'intval');
        $room_id_arr = [];
        $where=array();
        if (!empty($room_id)) {
            $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
            $where_vacancy = [];
            if (is_array($room_id)) {
                if (isset($room_id[3])) {
                    $where_vacancy[] = ['pigcms_id', '=', $room_id[3]];
                } elseif (isset($room_id[2])) {
                    $where_vacancy[] = ['layer_id', '=', $room_id[2]];
                } elseif (isset($room_id[1])) {
                    $where_vacancy[] = ['floor_id', '=', $room_id[1]];
                } else {
                    $where_vacancy[] = ['single_id', '=', $room_id[0]];
                }
                $vacancy_info = $service_house_village_user_vacancy->getVacancyList($where_vacancy, 'pigcms_id,single_id,floor_id,layer_id,village_id');
                if ($vacancy_info && !is_array($vacancy_info)) {
                    $vacancy_info = $vacancy_info->toArray();
                }
                if (!empty($vacancy_info)) {
                    foreach ($vacancy_info as $vv) {
                        $room_id_arr[] = $vv['pigcms_id'];
                    }
                }
                $where[] = ['o.room_id', 'in', $room_id_arr];
            } else {
                $where[] = ['o.room_id', '=', $room_id];
            }
        }
        $key_val = $this->request->post('key_val', '');
        $time_slot = $this->request->post('time_slot');
        $value = $this->request->post('value', '');
        $service_house_new_cashier = new HouseNewCashierService();
        if ($garage_id) {
            $where[] = ['pp.garage_id', '=', $garage_id];
        }
        if ($project_id) {
            $where[] = ['o.project_id', '=', $project_id];
        }
        if ($rule_id > 0) {
            $where[] = ['o.rule_id', '=', $rule_id];
        }
        if ($position_num) {
            $where[] = ['pp.position_num', 'like', '%' . $position_num . '%'];
        }elseif ($position_id>0){
            $where[] = ['o.position_id', '=', $position_id];
        }
        if ($key_val && $value) {
            if ($key_val == 'name') {
                $where[] = ['o.name', 'like', '%' . $value . '%'];
            } else {
                $where[] = ['o.phone', 'like', '%' . $value . '%'];
            }
        }
        // 时间段
        if ($time_slot && !empty($time_slot[0]) && !empty($time_slot[1])) {
            $where[] = ['o.add_time', '>=', strtotime($time_slot[0])];
            $where[] = ['o.add_time', '<=', strtotime(($time_slot[1] . ' 23:59:59'))];
        }
        if ($order_status == 1) {
            $where[] = ['o.check_status', 'in', array(1, 2)];
        }
        if ($service_start_time) {
            $where[] = ['o.service_start_time', '>=', strtotime($service_start_time . ' 00:00:00')];
        }
        if ($service_end_time) {
            $where[] = ['o.service_end_time', '<=', strtotime($service_end_time . ' 23:59:59')];
        }
        $where[] = ['o.is_paid', '=', 2];
        // 只查询已挂单账单
        $where[] = ['o.is_discard', '=', 3];
        $where[] = ['o.village_id', '=', $village_id];
        $where[] = ['o.room_id|o.position_id', '<>', ''];
        // 兼容企微预存管理
        if (!empty($pigcms_id)) {
            $where[] = ['o.pigcms_id', '=', $pigcms_id];
        }
        try {
            // 挂单只能修改费用 
            $houseVillageCheckauthSetService =new HouseVillageCheckauthSetService();
            $orderModifyCheck = ['village_id' => $this->adminUser['village_id'], 'xtype' => 'order_modify_check'];
            $orderModifyCheckOpen = $houseVillageCheckauthSetService->isOpenSet($orderModifyCheck);
            $modifyCheckLevelArr = [];
            if (in_array($this->login_role, $this->villageOrderCheckRole) && $orderModifyCheckOpen > 0 && isset($this->adminUser['wid']) && ($this->adminUser['wid'] > 0)) {
                $wid = $this->adminUser['wid'];
                $userAuthModifyLevel = $houseVillageCheckauthSetService->getOneCheckauthSet($orderModifyCheck);
                if (!empty($userAuthModifyLevel)) {
                    $modifyCheckLevelArr = array('wid' => $wid, 'check_level' => $userAuthModifyLevel['check_level']);
                }
            }
            $where['modifyCheckLevelArr'] = $modifyCheckLevelArr;
            $fieldStr = 'o.*,r.charge_name,p.name as project_name,n.charge_number_name';
            $showID = false;
            $where['showID'] = $showID;
            $data = $service_house_new_cashier->getNewPayOrders($where, $fieldStr, $page, $limit, 'o.order_id DESC');
            $where = unsetParam($where, ['modifyCheckLevelArr', 'showID']);
            $count = $service_house_new_cashier->getNewPayOrdersCount($where);
            $total_money = $service_house_new_cashier->getNewPayOrder2Sum($where, 'total_money');
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        $result = [];
        $result['list'] = $data;
        $result['total_money'] = $total_money;
        $result['count'] = $count;
        $result['payment_btn'] = 1;
        $houseVillageService=new HouseVillageService();
        $result['role_mdymoney'] =$houseVillageService->checkPermissionMenu(112083,$this->adminUser,$this->login_role,$this->dismissPermissionRole);;
        $result['role_payment']=$houseVillageService->checkPermissionMenu(112082,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
        $result['total_limit'] = $limit;
        $result['login_role'] = $this->login_role;
        $site_url=cfg('site_url');
        $site_url=trim($site_url,'/');
        $result['excelExportOutFileUrl']=$site_url.'/index.php?g=Index&c=ExportFile&a=download_house_export_file&export_from=house_order_export_out';
        return api_output(0, $result);
    }
    public function excelExportHangOrders(){
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $garage_id = $this->request->post('garage_id', 0);
        $project_id = $this->request->post('project_id', 0);
        $rule_id = $this->request->post('rule_id', 0, 'intval');
        $position_num = $this->request->post('position_num', '');
        $room_id = $this->request->post('room_id', 0);
        $pigcms_id = $this->request->post('pigcms_id', 0);
        $order_status = $this->request->post('order_type', 0);
        $service_start_time = $this->request->post('service_start_time', '', 'trim');
        $service_end_time = $this->request->post('service_end_time', '', 'trim');
        $position_id = $this->request->post('position_id', 0,'intval');
        $room_id_arr = [];
        $where=array();
        if (!empty($room_id)) {
            $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
            $where_vacancy = [];
            if (is_array($room_id)) {
                if (isset($room_id[3])) {
                    $where_vacancy[] = ['pigcms_id', '=', $room_id[3]];
                } elseif (isset($room_id[2])) {
                    $where_vacancy[] = ['layer_id', '=', $room_id[2]];
                } elseif (isset($room_id[1])) {
                    $where_vacancy[] = ['floor_id', '=', $room_id[1]];
                } else {
                    $where_vacancy[] = ['single_id', '=', $room_id[0]];
                }
                $vacancy_info = $service_house_village_user_vacancy->getVacancyList($where_vacancy, 'pigcms_id,single_id,floor_id,layer_id,village_id');
                if ($vacancy_info && !is_array($vacancy_info)) {
                    $vacancy_info = $vacancy_info->toArray();
                }
                if (!empty($vacancy_info)) {
                    foreach ($vacancy_info as $vv) {
                        $room_id_arr[] = $vv['pigcms_id'];
                    }
                }
                $where[] = ['o.room_id', 'in', $room_id_arr];
            } else {
                $where[] = ['o.room_id', '=', $room_id];
            }
        }
        $key_val = $this->request->post('key_val', '');
        $time_slot = $this->request->post('time_slot');
        $value = $this->request->post('value', '');
        if ($garage_id) {
            $where[] = ['pp.garage_id', '=', $garage_id];
        }
        if ($project_id) {
            $where[] = ['o.project_id', '=', $project_id];
        }
        if ($rule_id > 0) {
            $where[] = ['o.rule_id', '=', $rule_id];
        }
        if ($position_num) {
            $where[] = ['pp.position_num', 'like', '%' . $position_num . '%'];
        }elseif ($position_id>0){
            $where[] = ['o.position_id', '=', $position_id];
        }
        if ($key_val && $value) {
            if ($key_val == 'name') {
                $where[] = ['o.name', 'like', '%' . $value . '%'];
            } else {
                $where[] = ['o.phone', 'like', '%' . $value . '%'];
            }
        }
        // 时间段
        if ($time_slot && !empty($time_slot[0]) && !empty($time_slot[1])) {
            $where[] = ['o.add_time', '>=', strtotime($time_slot[0])];
            $where[] = ['o.add_time', '<=', strtotime(($time_slot[1] . ' 23:59:59'))];
        }
        if ($order_status == 1) {
            $where[] = ['o.check_status', 'in', array(1, 2)];
        }
        if ($service_start_time) {
            $where[] = ['o.service_start_time', '>=', strtotime($service_start_time . ' 00:00:00')];
        }
        if ($service_end_time) {
            $where[] = ['o.service_end_time', '<=', strtotime($service_end_time . ' 23:59:59')];
        }
        $where[] = ['o.is_paid', '=', 2];
        // 只查询已挂单账单
        $where[] = ['o.is_discard', '=', 3];
        $where[] = ['o.village_id', '=', $village_id];
        $where[] = ['o.room_id|o.position_id', '<>', ''];
        // 兼容企微预存管理
        if (!empty($pigcms_id)) {
            $where[] = ['o.pigcms_id', '=', $pigcms_id];
        }
        $fieldStr = 'o.*,r.charge_name,p.name as project_name,n.charge_number_name';
        $houseListDatasExportOutService= new HouseListDatasExportOutService();
        $exportParam=array();
        $exportParam['village_id']=$village_id;
        $exportParam['where']=$where;
        $exportParam['field']=$fieldStr;
        $exportParam['order']='o.order_id DESC';
        $exportParam['xtype']='no_pay_hang_order';  //挂单订单
        $exportParam['export_type']='house_order_export_out';
        $exportParam['export_title']='历史欠费账单列表';
        $exportInfo=$houseListDatasExportOutService->addExportLog($village_id,$exportParam);
        return api_output(0,$exportInfo);
    }
    
    /**
     * 挂单订单线下支付.
     */
    public function goPay()
    {
        $role_id = 0;
        if (in_array($this->login_role, $this->villageOrderCheckRole)) {
            $role_id = $this->_uid;
        }
        $village_id = $this->adminUser['village_id'];
        $pay_type = $this->request->post('pay_type', 0);
        if ($pay_type != 2) {
            return api_output_error(1001,'挂单订单只支持线下支付');
        }
        $offline_pay_type = $this->request->post('offline_pay_type', '');
        $order_list = $this->request->post('order_list', []);
        $auth_code = $this->request->post('auth_code', '');
        $remark = $this->request->post('remark', '');
        $offline_pay_type_mix = $this->request->post('offline_pay_type_mix', '');  //混合下线支付
        $extra_data = array();
        if (!empty($offline_pay_type_mix) && is_array($offline_pay_type_mix)) {
            $extra_data['offline_pay_type_mix'] = $offline_pay_type_mix;
        }
        $param = [];
        $param['pay_money'] = $this->request->post('pay_money','');//待支付金额
        $param['deposit_money'] = $this->request->post('deposit_money','');//押金抵扣金额
        $param['deposit_type'] = $this->request->post('deposit_type','');//是否开启押金抵扣
        if(empty($pay_type) || empty($order_list)) {
            return api_output_error(1001,'缺少必传参数');
        }
        if($pay_type == 3 && empty($auth_code)){
            return api_output_error(1001,'缺少付款码');
        }
        $service_new_pay = new NewPayService();
        try{
            $param['from'] = 1;
            $param['isHang'] = 1;
            foreach ($order_list as $kk=>$ovv){
                $order_list[$kk]['is_only_pay_this']=1;
            }
            $data = $service_new_pay->goPay($order_list,$village_id,$pay_type,$offline_pay_type,$auth_code,$remark,$extra_data,$role_id,$param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }
}