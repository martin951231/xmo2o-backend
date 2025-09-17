<?php

namespace app\community\controller\property_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseNewChargeProjectService;
use app\community\model\service\HouseNewChargeRuleService;
use app\community\model\service\HouseNewTransactionSummaryService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\order\PropertyBillOrderService;

class ChargeController extends CommunityBaseController
{
    /**
     * 收费项目列表.
     */
    public function chargeProjectList()
    {
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $villageIds = isset($this->adminUser['menus']) ? $this->adminUser['menus'] : '';
        if (!empty($villageIds)) {
            if (!is_array($villageIds)) {
                $villageIds = explode(',', $villageIds);
            }
        }
        if (empty($villageIds)) {
            $whereVillage = [];
            $whereVillage[] = ['property_id', '=', $property_id];
            $whereVillage[] = ['status', '=', 1];
            $villageIds = (new HouseVillageService())->getVillageColumn($whereVillage, 'village_id');
        }
        $page = $this->request->param('page', 0, 'int');
        $limit = $this->request->param('limit', 0, 'int');
        $keyword = $this->request->param('keyword');
        $type = $this->request->param('type', '', 'trim');
        $HouseNewChargeProjectService = new HouseNewChargeProjectService();
        $param = [
            'villageIds' => $villageIds,
            'page' => $page,
            'limit' => $limit,
            'keyword' => $keyword,
            'property_id' => $property_id,
            'type' => $type,
        ];
        try {
            $list = $HouseNewChargeProjectService->getList($param);
            $villageNames = (new HouseVillageService())->getVillageColumn($whereVillage, 'village_name', 'village_id');
            if (isset($list['list']) && !empty($list['list'])) {
                foreach ($list['list'] as &$item) {
                    $village_id = isset($item['village_id']) ? intval($item['village_id']) : 0;
                    $item['village_name'] = isset($villageNames[$village_id]) ? $villageNames[$village_id] : '';
                    if (isset($item['village_name']) && $item['village_name']) {
                        $item['name'] .= "({$item['village_name']})";
                    }
                }
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list);
    }

    public function getVillageList()
    {
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        try {
            $condition_where = array(['property_id', '=', $property_id]);
            $condition_where[] = array('status', '<>', 5);
            $houseVillageService = new HouseVillageService();
            $fieldStr = 'village_id,village_name';
            $list = $houseVillageService->getList($condition_where, $fieldStr, 0, 1000, 'village_id DESC', 0);
            $dataArr=array('list'=>[]);
            if ($list && !$list->isEmpty()) {
                $list = $list->toArray();
                $dataArr['list'] = $list;
            }
            return api_output(0, $dataArr);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    /**
     * 收费标准列表.
     */
    public function chargeRuleList()
    {
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        /*
        $villageIds = isset($this->adminUser['menus']) ? $this->adminUser['menus'] : '';
        if (!empty($villageIds)) {
            if (!is_array($villageIds)) {
                $villageIds = explode(',', $villageIds);
            }
        }
        if (empty($villageIds)) {
            $whereVillage = [];
            $whereVillage[] = ['property_id', '=', $property_id];
            $whereVillage[] = ['status', '=', 1];
            $villageIds = (new HouseVillageService())->getVillageColumn($whereVillage, 'village_id');
        }
        */
        $charge_project_id = $this->request->param('charge_project_id', 0);
        $subject_id = $this->request->param('subject_id', 0);
        $page = $this->request->param('page', 1, 'intval');
        $charge_type= $this->request->param('charge_type', '', 'trim');
        $xtype= $this->request->param('xtype', '', 'trim');
        $keyword= $this->request->param('keyword', '', 'trim');
        $charge_village_id= $this->request->param('village_id', 0, 'intval');
        $limit = 12;
        $HouseNewChargeRuleService = new HouseNewChargeRuleService();
        $param = [
            'page' => $page,
            'limit' => $limit,
            'charge_project_id' => $charge_project_id,
            'subjectId' => $subject_id,
            'keyword'=>$keyword,
            'village_id'=>$charge_village_id,
            'from_type'=>2
        ];
        try {
            $list = $HouseNewChargeRuleService->getList($param);
            $list['param'] = $param;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage() . $e->getLine());
        }
        return api_output(0, $list);
    }

    /**
     * 小区月度已缴报表.
     */
    public function villageMonthlyBill()
    {
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $villageIds = isset($this->adminUser['menus']) ? $this->adminUser['menus'] : '';
        if (!empty($villageIds)) {
            if (!is_array($villageIds)) {
                $villageIds = explode(',', $villageIds);
            }
        }
        if (empty($villageIds)) {
            $whereVillage = [];
            $whereVillage[] = ['property_id', '=', $property_id];
            $whereVillage[] = ['status', '=', 1];
            $villageIds = (new HouseVillageService())->getVillageColumn($whereVillage, 'village_id');
        }
        $payDate = $this->request->param('payDate');
        $service_start_time = $this->request->param('service_start_time', '', 'trim');
        $service_end_time = $this->request->param('service_end_time', '', 'trim');
        $project_id = $this->request->param('project_id');
        try {
            $params = [
                'payDate' => $payDate, 'property_id' => $property_id, 'villageIds' => $villageIds,
                'service_start_time' => $service_start_time, 'service_end_time' => $service_end_time,
                'project_id' => $project_id
            ];
            $count_list = (new PropertyBillOrderService())->villageMonthlyBill($params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$count_list);
    }

    /**
     * 小区月度已缴报表导出.
     */
    public function villageMonthlyBillExport()
    {
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $villageIds = isset($this->adminUser['menus']) ? $this->adminUser['menus'] : '';
        if (!empty($villageIds)) {
            if (!is_array($villageIds)) {
                $villageIds = explode(',', $villageIds);
            }
        }
        if (empty($villageIds)) {
            $whereVillage = [];
            $whereVillage[] = ['property_id', '=', $property_id];
            $whereVillage[] = ['status', '=', 1];
            $villageIds = (new HouseVillageService())->getVillageColumn($whereVillage, 'village_id');
        }
        $payDate = $this->request->param('payDate');
        $service_start_time = $this->request->param('service_start_time', '', 'trim');
        $service_end_time = $this->request->param('service_end_time', '', 'trim');
        $project_id = $this->request->param('project_id');
        try {
            $params = [
                'payDate' => $payDate, 'property_id' => $property_id, 'villageIds' => $villageIds,
                'service_start_time' => $service_start_time, 'service_end_time' => $service_end_time,
                'project_id' => $project_id, 'property_name' => $this->adminUser['property_name']
            ];
            $count_list = (new PropertyBillOrderService())->villageMonthlyBillExport($params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$count_list);
    }

    /**
     * 小区年度已缴报表.
     */
    public function villageYearlyBill()
    {
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $villageIds = isset($this->adminUser['menus']) ? $this->adminUser['menus'] : '';
        if (!empty($villageIds)) {
            if (!is_array($villageIds)) {
                $villageIds = explode(',', $villageIds);
            }
        }
        if (empty($villageIds)) {
            $whereVillage = [];
            $whereVillage[] = ['property_id', '=', $property_id];
            $whereVillage[] = ['status', '=', 1];
            $villageIds = (new HouseVillageService())->getVillageColumn($whereVillage, 'village_id');
        }
        $payDate = $this->request->param('payDate');
        $service_start_time = $this->request->param('service_start_time', '', 'trim');
        $service_end_time = $this->request->param('service_end_time', '', 'trim');
        $project_id = $this->request->param('project_id');
        try {
            $params = [
                'payDate' => $payDate, 'property_id' => $property_id, 'villageIds' => $villageIds,
                'service_start_time' => $service_start_time, 'service_end_time' => $service_end_time,
                'project_id' => $project_id,
            ];
            $count_list = (new PropertyBillOrderService())->villageYearlyBill($params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$count_list);
    }
    
    /**
     * 小区年度已缴报表导出.
     */
    public function villageYearlyBillExport()
    {
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $villageIds = isset($this->adminUser['menus']) ? $this->adminUser['menus'] : '';
        if (!empty($villageIds)) {
            if (!is_array($villageIds)) {
                $villageIds = explode(',', $villageIds);
            }
        }
        if (empty($villageIds)) {
            $whereVillage = [];
            $whereVillage[] = ['property_id', '=', $property_id];
            $whereVillage[] = ['status', '=', 1];
            $villageIds = (new HouseVillageService())->getVillageColumn($whereVillage, 'village_id');
        }
        $payDate = $this->request->param('payDate');
        $service_start_time = $this->request->param('service_start_time', '', 'trim');
        $service_end_time = $this->request->param('service_end_time', '', 'trim');
        $project_id = $this->request->param('project_id');
        try {
            $params = [
                'payDate' => $payDate, 'property_id' => $property_id, 'villageIds' => $villageIds,
                'service_start_time' => $service_start_time, 'service_end_time' => $service_end_time,
                'project_id' => $project_id, 'property_name' => $this->adminUser['property_name']
            ];
            $count_list = (new PropertyBillOrderService())->villageYearlyBillExport($params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$count_list);
    }

    /**
     * 小区年度应收报表.
     */
    public function villageYearlyReceivable()
    {
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $villageIds = isset($this->adminUser['menus']) ? $this->adminUser['menus'] : '';
        if (!empty($villageIds)) {
            if (!is_array($villageIds)) {
                $villageIds = explode(',', $villageIds);
            }
        }
        if (empty($villageIds)) {
            $whereVillage = [];
            $whereVillage[] = ['property_id', '=', $property_id];
            $whereVillage[] = ['status', '=', 1];
            $villageIds = (new HouseVillageService())->getVillageColumn($whereVillage, 'village_id');
        }
        $service_time = $this->request->post('service_time','');
        $project_id = $this->request->post('project_id',0,'iintval');
        try {
            $params = [
                'property_id' => $property_id, 'villageIds' => $villageIds,
                'service_time' => $service_time,
                'project_id' => $project_id,
            ];
            $count_list = (new PropertyBillOrderService())->villageYearlyReceivable($params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$count_list);
    }

    /**
     * 小区年度应收报表导出.
     */
    public function villageYearlyReceivableExport()
    {
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $villageIds = isset($this->adminUser['menus']) ? $this->adminUser['menus'] : '';
        if (!empty($villageIds)) {
            if (!is_array($villageIds)) {
                $villageIds = explode(',', $villageIds);
            }
        }
        if (empty($villageIds)) {
            $whereVillage = [];
            $whereVillage[] = ['property_id', '=', $property_id];
            $whereVillage[] = ['status', '=', 1];
            $villageIds = (new HouseVillageService())->getVillageColumn($whereVillage, 'village_id');
        }
        $service_time = $this->request->post('service_time');
        $project_id = $this->request->post('project_id');
        try {
            $params = [
                'property_id' => $property_id, 'villageIds' => $villageIds,
                'service_time' => $service_time,
                'project_id' => $project_id,
            ];
            $count_list = (new PropertyBillOrderService())->villageYearlyReceivableExport($params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$count_list);
    }
    
    public function getRoomFeeRateList(){
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $village_ids = $this->request->param('village_ids', ''); //小区ids
        $w_singles = $this->request->param('w_singles', '');
        $year_v = $this->request->param('year_v', 0,'intval');
        if($year_v<1){
            $year_v=date('Y');
        }
        $singleArr=array();
        if($w_singles){
            foreach ($w_singles as $tvv){
                $tmpArr=explode('_',$tvv);
                $singleArr[]=$tmpArr['1'];
            }
            $singleArr=array_unique($singleArr,SORT_NUMERIC);
        }
        if(empty($village_ids)){
            $condition_where = array(['property_id', '=', $property_id]);
            $condition_where[] = array('status', '<>', 5);
            $houseVillageService = new HouseVillageService();
            $fieldStr = 'village_id,village_name';
            $list = $houseVillageService->getList($condition_where, $fieldStr, 0, 1000, 'village_id DESC', 0);
            if ($list && !$list->isEmpty()) {
                $list = $list->toArray();
                foreach ($list as $vvv){
                    $village_ids[]=$vvv['village_id'];
                }
            }
        }
        
        try {
            $houseNewTransactionSummaryService = new HouseNewTransactionSummaryService();
            $rets=$houseNewTransactionSummaryService->getRoomFeeRateCustomizedList($year_v,$village_ids,$singleArr,'property');
            return api_output(0, $rets);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function getRoomUnPayRateList(){
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $village_ids = $this->request->param('village_ids', ''); //小区ids
        $w_singles = $this->request->param('w_singles', '');
        $year_v = $this->request->param('year_v', 0,'intval');
        $is_split = $this->request->param('is_split', 0 ,'intval');
        if($year_v<1){
            $year_v=date('Y');
        }
        $singleArr=array();
        if($w_singles){
            foreach ($w_singles as $tvv){
                $tmpArr=explode('_',$tvv);
                $singleArr[]=$tmpArr['1'];
            }
            $singleArr=array_unique($singleArr,SORT_NUMERIC);
        }
        if(empty($village_ids)){
            $condition_where = array(['property_id', '=', $property_id]);
            $condition_where[] = array('status', '<>', 5);
            $houseVillageService = new HouseVillageService();
            $fieldStr = 'village_id,village_name';
            $list = $houseVillageService->getList($condition_where, $fieldStr, 0, 1000, 'village_id DESC', 0);
            if ($list && !$list->isEmpty()) {
                $list = $list->toArray();
                foreach ($list as $vvv){
                    $village_ids[]=$vvv['village_id'];
                }
            }
        }

        try {
            $houseNewTransactionSummaryService = new HouseNewTransactionSummaryService();
            $rets=$houseNewTransactionSummaryService->getVillageRoomUnPayRateList($year_v,$village_ids,$singleArr,'property',$is_split);
            return api_output(0, $rets);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    
    public function getRoomRateSummaryRecordList(){
        $property_id = $this->adminUser['property_id'];
        $rate_type = $this->request->param('rate_type', 0,'intval');
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        try {
            $houseNewTransactionSummaryService = new HouseNewTransactionSummaryService();
            $whereArr=array(['property_id','=',$property_id],['rate_type','=',$rate_type],['xtype','=',1]);
            $rets=$houseNewTransactionSummaryService->getRoomRateSummaryRecordData($whereArr);
            return api_output(0, $rets);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }


}