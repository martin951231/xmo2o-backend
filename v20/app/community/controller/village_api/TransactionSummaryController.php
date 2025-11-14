<?php
/**
 * Created by PhpStorm.
 * 小区交易汇总
 * User: wanzy
 * DateTime: 2021/11/16 14:28
 */
namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;

use app\community\model\service\HouseNewChargeProjectService;
use app\community\model\service\HouseNewChargeService;
use app\community\model\service\HouseNewCashierService;
use app\community\model\service\HouseNewTransactionSummaryService;
use app\community\model\service\HousePaidOrderRecordService;
use app\community\model\service\KefuService;

class TransactionSummaryController extends CommunityBaseController{

    public function houseSummaryList() {
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        $login_role = $this->login_role;
        if (in_array($login_role,$this->propertyRole)) {
            //物业
            $village_id = 0;
        } else {
            //小区
//            $property_id = 0;
        }
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $vacancy = $this->request->post('vacancy');
        $date = $this->request->post('date');
        $name = $this->request->post('name','','trim');
        $phone = $this->request->post('phone','','trim');
        $service_start_time   = $this->request->post('service_start_time','','trim');
        $service_end_time    = $this->request->post('service_end_time','','trim');
        $houseNewTransactionSummaryService = new HouseNewTransactionSummaryService();
        $where = [];
        if (!empty($date)&&count($date)>0){
            if (!empty($date[0])){
                $where[] = ['pay_time','>=',strtotime($date[0].' 00:00:00')];
            }
            if (!empty($date[1])){
                $where[] = ['pay_time','<=',strtotime($date[1].' 23:59:59')];
            }
        }
        if (!empty($vacancy)&&!empty($vacancy[3])){
            $where[] = ['room_id','=',$vacancy[3]];
        }
        $param = [];
        if (!empty($name)){
            $param['name']=$name;
            $where[] = ['name','=',$name];
        }
        if (!empty($phone)){
            $param['phone']=$phone;
            $where[] = ['phone','=',$phone];
        }
        if($service_start_time){
            $where[] =['service_start_time','>=',strtotime($service_start_time.' 00:00:00')];
        }
        if($service_end_time){
            $where[] =['service_end_time','<=',strtotime($service_end_time.' 23:59:59')];
        }
        try{
            $list = $houseNewTransactionSummaryService->HouseFeeSummaryList($where,$village_id, $property_id,$page,$limit);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    public function getChargeTypeList() {
        $property_id =  $this->adminUser['property_id'];
        if (!$property_id) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $serviceHouseNewPorperty = new HouseNewChargeService();
        return api_output(0,$serviceHouseNewPorperty->charge_type_arr);
    }

    public function getChargeProjectList(){
        $village_id = $this->adminUser['village_id'];
        $charge_type_key =  $this->request->post('charge_type_key','','trim');
        if(empty($charge_type_key)){
            return api_output_error(1003, '请选择收费类别');
        }
        $property_id =  $this->adminUser['property_id'];
        $HouseNewChargeProjectService = new HouseNewChargeProjectService();
        $where = [];
        $where[] = ['c.property_id','=',$property_id];
        $where[] = ['c.charge_type','=',$charge_type_key];
        $where[] = ['p.village_id','=',$village_id];
        $where[] = ['p.subject_id','>',0];
        $where[] = ['p.status','=',1];
        try{
            $list = $HouseNewChargeProjectService->getLists($where,'p.id,p.name,p.subject_id');
        }catch (\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$list);
    }
    public function getSummaryByRuleList()
    {
        $village_id = $this->adminUser['village_id'];
        //$property_id =  $this->adminUser['property_id'];
        $page = $this->request->post('page', 1);
        $limit = $this->request->post('limit', 10);
        $charge_type = $this->request->post('charge_type', '', 'trim');
        $charge_project_id = $this->request->post('charge_project_id', 0, 'intval');
        $rule_name = $this->request->post('rule_name', '', 'trim');
        $date = $this->request->post('date');
        $order_service_type = $this->request->post('order_service_type', 0, 'intval'); //0 计费开始结束时间 1计费开始时间 2计费结束时间
        $houseNewTransactionSummaryService = new HouseNewTransactionSummaryService();
        $where = [];
        $where[] = ['is_discard', '=', 1];
        $whereRule = [];
        $whereRule[] = ['village_id', '=', $village_id];
        $whereRule[] = ['status', '<>', 4];
        if (!empty($date) && count($date) > 0) {
            switch ($order_service_type){
                case 0:
                    if (!empty($date[0])) {
                        $where[] = ['service_start_time', '>=', strtotime($date[0] . ' 00:00:00')];
                    }
                    if (!empty($date[1])) {
                        $where[] = ['service_end_time', '<=', strtotime($date[1] . ' 23:59:59')];
                    }
                    break;
                case 1:
                    if (!empty($date[0])) {
                        $where[] = ['service_start_time', '>=', strtotime($date[0] . ' 00:00:00')];
                    }
                    if (!empty($date[1])) {
                        $where[] = ['service_start_time', '<=', strtotime($date[1] . ' 23:59:59')];
                    }
                    break;
                case 2:
                    if (!empty($date[0])) {
                        $where[] = ['service_end_time', '>=', strtotime($date[0] . ' 00:00:00')];
                    }
                    if (!empty($date[1])) {
                        $where[] = ['service_end_time', '<=', strtotime($date[1] . ' 23:59:59')];
                    }
                    break;
            }
        }
        if (!empty($charge_type)) {
            $where[] = ['order_type', '=', $charge_type];
        }
        if (!empty($charge_project_id)) {
            $where[] = ['project_id', '=', $charge_project_id];
            $whereRule[] = ['charge_project_id', '=', $charge_project_id];
        }
        if (!empty($rule_name)) {
            $whereRule[] = ['charge_name', 'like', '%' . $rule_name . '%'];
            $houseNewChargeProjectService = new HouseNewChargeProjectService();
            $ruleIdList = $houseNewChargeProjectService->getChargeRuleList($whereRule, 'id');
            $ruleIds = array();
            if ($ruleIdList) {
                foreach ($ruleIdList as $rvv) {
                    $ruleIds[] = $rvv['id'];
                }
            }
            if ($ruleIds) {
                $ruleIds = array_unique($ruleIds);
                $where[] = ['rule_id', 'in', $ruleIds];
            }
        }

        try {
            $list = $houseNewTransactionSummaryService->HouseFeeSummaryList($where, $village_id, 0, $page, $limit, 'rule_id');
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list);
    }
    public function exportHouseVillageFee(){
        set_time_limit(0);
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        $export_type = $this->request->post('export_type',0,'intval');//1物业月度收入结转统计 2物业费收缴率统计
        $type1month = $this->request->post('type1month','','trim');
        $type2monthstart = $this->request->post('type2monthstart','','trim');
        $type2monthend = $this->request->post('type2monthend','','trim');
        if($export_type==1){
            $where=array();
            $where[] = ['o.village_id','=',$this->adminUser['village_id']];
            $where[] = ['o.is_discard','=',1];
            $where[] = ['o.is_paid','=',1];
            if(empty($type1month)){
                return api_output_error(1001,'请正确选择选择年月时间！');
            }else{
                $where[] = ['o.pay_time','>=',strtotime($type1month.'-01 00:00:00')];
                $t=date('t',strtotime($type1month.'-10'));
                $where[] = ['o.pay_time','<=',strtotime($type1month.'-'.$t.' 23:59:59')];
            }
            $where[] = ['o.order_type','<>','non_motor_vehicle'];
            $where1 = '`o`.`refund_money`<`o`.`pay_money`';
            $field='o.*,p.name as project_name,n.charge_number_name,r.charge_name';
            $order='o.room_id DESC,o.position_id DESC,o.project_id DESC';
            try{
                $service_house_new_cashier = new HouseNewCashierService();
                $extraArr=array('filename'=>$type1month.'月物业收入结转统计','exporttype'=>'');
                $list = $service_house_new_cashier->printPayOrder($where,$where1,$field,$order,$extraArr,2);
                return api_output(0,$list);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
        }elseif($export_type==2){
            $monthstart=0;
            $monthend=0;
            if($type2monthstart && strlen($type2monthstart)>5){
                $monthstart=$type2monthstart.'-01 00:00:00';
                $monthstart=strtotime($monthstart);
            }
            if($type2monthend && strlen($type2monthend)>5){
                $t=date('t',strtotime($type2monthend.'-10'));
                $monthend=$type2monthend.'-'.$t.' 23:59:59';
                $monthend=strtotime($monthend);
            }
            if($monthstart<1 && $monthend<1){
                return api_output_error(1001,'请正确选择选择年月时间！');
            }
            try{
                $houseNewTransactionSummaryService = new HouseNewTransactionSummaryService();
                $ret = $houseNewTransactionSummaryService->exportHouseVillageFeeRate($village_id,$monthstart,$monthend);
                return api_output(0,$ret);
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
        }

    }
    public function getOrderTableRuleList(){
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        $pay_status=$this->request->post('pay_status',0,'trim'); //no_pay未支付 is_pay已支付
        $xtype=$this->request->post('xtype','','trim');
        try{
            $houseNewTransactionSummaryService = new HouseNewTransactionSummaryService();
            $ret = $houseNewTransactionSummaryService->getOrderTableRuleList($village_id,$pay_status);
            return api_output(0,$ret);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function getOrderTableProjectList(){
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        $pay_status=$this->request->post('pay_status',0,'trim'); //no_pay未支付 is_pay已支付
        $xtype=$this->request->post('xtype','','trim');
        try{
            $houseNewTransactionSummaryService = new HouseNewTransactionSummaryService();
            $ret = $houseNewTransactionSummaryService->getOrderTableProjectList($village_id,$pay_status);
            return api_output(0,$ret);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function getSummaryByRoomAndRuleList(){
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        $pay_status=$this->request->post('pay_status','','trim'); //no_pay未支付 is_pay已支付
        $xtype=$this->request->post('xtype','','trim');
        $charge_project_id=$this->request->post('charge_project_id',0,'intval');
        $rule_id=$this->request->post('rule_id',0,'intval');  
        $order_service_type=$this->request->post('order_service_type',0,'intval');
        $date=$this->request->post('date');
        $end_date=$this->request->post('end_date','','trim');
        $page = $this->request->post('page', 1);
        $tj_type=$this->request->post('tj_type',0,'intval');  //0 按标准  1按项目
        $limit=10;
        try{
            $whereArr=array();
            $whereArr[]=array('o.village_id','=',$village_id);
            $whereArr[] = array('o.order_type','<>','non_motor_vehicle');
            if($pay_status=='no_pay'){
                $whereArr[]=array('o.is_paid','=',2);
                $whereArr[]=array('o.is_discard','=',1);
            }else if($pay_status=='is_pay'){
                $whereArr[]=array('o.is_paid','=',1);
                $whereArr[] = array('o.is_discard','=',1);
            }
            if($charge_project_id>0){
                $whereArr[]=array('o.project_id','=',$charge_project_id);
            }
            if($rule_id>0 && $tj_type<1){
                $whereArr[]=array('o.rule_id','=',$rule_id);
            }

            $dateArr=array();
            if($xtype=='daily' && !empty($date) ){
                if($order_service_type==0){
                    if($pay_status=='no_pay'){
                        //按账单生成时间
                        if (!empty($date[0])) {
                            $whereArr[] = ['o.add_time', '>=', strtotime($date[0] . ' 00:00:00')];
                        }
                        if (!empty($date[1])) {
                            $whereArr[] = ['o.add_time', '<=', strtotime($date[1] . ' 23:59:59')];
                        }
                    }else{
                        //按账单支付时间
                        if (!empty($date[0])) {
                            $whereArr[] = ['o.pay_time', '>=', strtotime($date[0] . ' 00:00:00')];
                        }
                        if (!empty($date[1])) {
                            $whereArr[] = ['o.pay_time', '<=', strtotime($date[1] . ' 23:59:59')];
                        }
                    }
                }else if($order_service_type==1){
                    //按开始时间
                    if (!empty($date[0])) {
                        $whereArr[] = ['o.service_start_time', '>=', strtotime($date[0] . ' 00:00:00')];
                    }
                    if (!empty($date[1])) {
                        $whereArr[] = ['o.service_start_time', '<=', strtotime($date[1] . ' 23:59:59')];
                    }
                }elseif($order_service_type==2){
                    //按结束时间
                    if (!empty($date[0])) {
                        $whereArr[] = ['o.service_end_time', '>=', strtotime($date[0] . ' 00:00:00')];
                    }
                    if (!empty($date[1])) {
                        $whereArr[] = ['o.service_end_time', '<=', strtotime($date[1] . ' 23:59:59')];
                    }
                }else if($order_service_type==3){
                    //按开始结束时间
                    if (!empty($date[0])) {
                        $whereArr[] = ['o.service_start_time', '>=', strtotime($date[0] . ' 00:00:00')];
                    }
                    if (!empty($date[1])) {
                        $whereArr[] = ['o.service_end_time', '<=', strtotime($date[1] . ' 23:59:59')];
                    }
                }
                $dateArr=$date;
            }else  if($xtype=='monthly' && (!empty($date) || !empty($end_date))){
                $stratTime='';
                $endTime='';
                $date=$date ? trim($date):'';
                $end_date=$end_date ? trim($end_date):'';
                if(!empty($date) && empty($end_date)){
                    $tmp_time=strtotime($date . '-10 10:00:00');
                    $last_day=date('t',$tmp_time);
                    $stratTime=$date . '-01 00:00:00';
                    $endTime=$date . '-'.$last_day.' 23:59:59';
                }else  if(!empty($date) && !empty($end_date)){
                    $stratTime=$date . '-01 00:00:00';
                    $tmp_time=strtotime($end_date . '-10 10:00:00');
                    $last_day=date('t',$tmp_time);
                    $endTime=$end_date . '-'.$last_day.' 23:59:59';
                }else if(empty($date) && !empty($end_date)){
                    $date=$end_date;
                    $end_date='';
                    $tmp_time=strtotime($date . '-10 10:00:00');
                    $last_day=date('t',$tmp_time);
                    $stratTime=$date . '-01 00:00:00';
                    $endTime=$date . '-'.$last_day.' 23:59:59';
                }
                if($order_service_type==0){
                    if($pay_status=='no_pay'){
                        //按账单生成时间
                        $whereArr[] = ['o.add_time', '>=', strtotime($stratTime)];
                        $whereArr[] = ['o.add_time', '<=', strtotime($endTime)];
                    }else{
                        //按账单支付时间
                        $whereArr[] = ['o.pay_time', '>=', strtotime($stratTime)];
                        $whereArr[] = ['o.pay_time', '<=', strtotime($endTime)];
                    }
                }else if($order_service_type==1){
                    //按开始时间
                    $whereArr[] = ['o.service_start_time', '>=', strtotime($stratTime)];
                    $whereArr[] = ['o.service_start_time', '<=', strtotime($endTime)];
               
                }elseif($order_service_type==2){
                    //按结束时间
                    $whereArr[] = ['o.service_end_time', '>=', strtotime($stratTime)];
                    $whereArr[] = ['o.service_end_time', '<=', strtotime($endTime)];
                }else if($order_service_type==3){
                    //按开始结束时间
                    $whereArr[] = ['o.service_start_time', '>=', strtotime($stratTime)];
                    $whereArr[] = ['o.service_end_time', '<=', strtotime($endTime)];
                }
                $dateArr[]=$stratTime;
                $dateArr[]=$endTime;
            }
            $houseNewTransactionSummaryService = new HouseNewTransactionSummaryService();
            if($tj_type==1){
                $ret = $houseNewTransactionSummaryService->getSummaryByRoomAndProjectList($village_id,$whereArr,$pay_status, $page, $limit);
            }else{
                $ret = $houseNewTransactionSummaryService->getSummaryByRoomAndRuleList($village_id,$whereArr,$pay_status, $page, $limit);
            }
            $site_url=cfg('site_url');
            $site_url=trim($site_url,'/');
            $ret['search_date']=$dateArr;
            $ret['excelExportOutFileUrl']=$site_url.'/index.php?g=Index&c=ExportFile&a=download_house_export_file&export_from=house_financial_statement';
            return api_output(0,$ret);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function  getSummaryByYearAndRuleList(){
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        $pay_status=$this->request->post('pay_status','','trim'); //no_pay未支付 is_pay已支付
        $xtype=$this->request->post('xtype','','trim');
        $charge_project_id=$this->request->post('charge_project_id',0,'intval');
        $rule_id=$this->request->post('rule_id',0,'intval');
        $order_service_type=$this->request->post('order_service_type',0,'intval');
        $year_v=$this->request->post('year_v',0,'intval');
        $year_v=$year_v>1971 ? $year_v:date('Y');
        $page = $this->request->post('page', 1);
        $tj_type=$this->request->post('tj_type',0,'intval');  //0 按标准  1按项目
        $limit=10;
        try{
            $whereArr=array();
            $whereArr[]=array('village_id','=',$village_id);
            $whereArr[] = array('order_type','<>','non_motor_vehicle');
            if($pay_status=='no_pay'){
                $whereArr[]=array('is_paid','=',2);
                $whereArr[]=array('is_discard','=',1);
            }else if($pay_status=='is_pay'){
                $whereArr[]=array('is_paid','=',1);
                $whereArr[] = array('is_discard','=',1);
            }
            if($charge_project_id>0){
                $whereArr[]=array('project_id','=',$charge_project_id);
            }
            if($rule_id>0 && $tj_type<1){
                $whereArr[]=array('rule_id','=',$rule_id);
            }
            $extraArr=array('year_v'=>$year_v,'order_service_type'=>$order_service_type);
            $houseNewTransactionSummaryService = new HouseNewTransactionSummaryService();
           
            if($tj_type==1) {
                $ret = $houseNewTransactionSummaryService->getSummaryByYearAndProjectList($village_id,$whereArr,$pay_status,$extraArr, $page, $limit);
            }else{
                $ret = $houseNewTransactionSummaryService->getSummaryByYearAndRuleList($village_id,$whereArr,$pay_status,$extraArr, $page, $limit);
            }
            $site_url=cfg('site_url');
            $site_url=trim($site_url,'/');
            $ret['excelExportOutFileUrl']=$site_url.'/index.php?g=Index&c=ExportFile&a=download_house_export_file&export_from=house_financial_statement';
            return api_output(0,$ret);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function getSummaryByProjectList(){
        $village_id = $this->adminUser['village_id'];
        $date=$this->request->post('date','','trim');
        $pay_status=$this->request->post('pay_status','is_pay','trim'); //no_pay未支付 is_pay已支付
        $xtype=$this->request->post('xtype','daily','trim'); //daily 按日
        $charge_project_id=$this->request->post('charge_project_id',0,'intval');
        $page = $this->request->post('page', 1);
        $limit=10;
        $property_id =  $this->adminUser['property_id'];
        try{
            $whereArr=array();
            $whereArr[]=array('o.village_id','=',$village_id);
            $whereArr[] = array('o.order_type','<>','non_motor_vehicle');
            $whereRefundArr=$whereArr;
            if($pay_status=='no_pay'){
                $whereArr[]=array('o.is_paid','=',2);
                $whereArr[]=array('o.is_discard','=',1);
            }else if($pay_status=='is_pay'){
                $whereArr[]=array('o.is_paid','=',1);
                $whereArr[] = array('o.is_discard','=',1);
                $whereRefundArr[]=array('o.is_paid','=',1);
                $whereRefundArr[]=array('o.is_discard','=',1);
                $whereArr['string']='(`o`.`refund_money`<`o`.`pay_money`)';
            }
            $dateTime=$date ? strtotime($date) :0;
            if($dateTime<100){
                $dateTimeStr=date('Y-m-d');
                $dateTime=strtotime($dateTimeStr);
            }
            $dateTimeEnd=$dateTime+3600*24-1;
            //按账单支付时间
            $whereArr[] = ['o.pay_time', '>=', $dateTime];
            $whereArr[] = ['o.pay_time', '<=', $dateTimeEnd];
            $whereRefundArr[]  = ['o.update_time', '>=', $dateTime];
            $whereRefundArr[]  = ['o.update_time', '<=', $dateTimeEnd];
            $houseNewTransactionSummaryService = new HouseNewTransactionSummaryService();
            $extraArr=array('xtype'=>$xtype,'charge_project_id'=>$charge_project_id,'property_id'=>$property_id,'date_time'=>$dateTime,'date_time_end'=>$dateTimeEnd);
            $ret = $houseNewTransactionSummaryService->getSummaryByProjectList($village_id,$whereArr, $extraArr,$page, $limit,$whereRefundArr);
            $site_url=cfg('site_url');
            $site_url=trim($site_url,'/');
            $ret['excelExportOutFileUrl']=$site_url.'/index.php?g=Index&c=ExportFile&a=download_house_export_file&export_from=house_financial_by_project';
            return api_output(0,$ret);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function  excelExportFinancialOut(){
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        $pay_status=$this->request->post('pay_status','','trim'); //no_pay未支付 is_pay已支付
        $xtype=$this->request->post('xtype','','trim');
        $charge_project_id=$this->request->post('charge_project_id',0,'intval');
        $rule_id=$this->request->post('rule_id',0,'intval');
        $order_service_type=$this->request->post('order_service_type',0,'intval');
        $date=$this->request->post('date');
        $end_date=$this->request->post('end_date','','trim');
        $year_v=$this->request->post('year_v',0,'intval');
        $year_v=$year_v>1971 ? $year_v:date('Y');
        $export_type=$this->request->post('export_type','','trim');
        $tj_type=$this->request->post('tj_type',0,'intval');  //0 按标准  1按项目
        $export_type=$export_type ?  $export_type:'house_financial_statement';
        $export_title='小区财务报表统计数据导出';
        if($export_type=='house_financial_by_project'){
            $export_title='小区财务已缴日报表数据';
        }
        try{
            $param=array();
            $param['village_id']=$village_id;
            $param['property_id']=$property_id;
            $param['pay_status']=$pay_status;
            $param['xtype']=$xtype;
            $param['charge_project_id']=$charge_project_id;
            $param['rule_id']=$rule_id;
            $param['order_service_type']=$order_service_type;
            $param['date']=!empty($date) ? $date:'';
            $param['end_date']=!empty($end_date) ? $end_date:'';
            $param['year_v']=$year_v;
            $param['export_type']=$export_type;
            $param['export_title']=$export_title;
            $param['tj_type']=$tj_type;
            $houseNewTransactionSummaryService = new HouseNewTransactionSummaryService();
            $ret = $houseNewTransactionSummaryService->addExportLog($village_id,$param);
            return api_output(0,$ret);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function getOrderBusinessTypeInfo(){
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        try{
            $housePaidOrderRecordService = new HousePaidOrderRecordService();
            $ret=$housePaidOrderRecordService->getBusinessTypeInfo();
            return api_output(0,$ret);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function getPaidOrderRecordList(){
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        $order_type=$this->request->post('order_type','','intval'); //1业务订单编号 2商户支付订单号 3第三方支付流水号
        $order_no=$this->request->post('order_no','','trim');
        $order_status=$this->request->post('order_status',0,'intval'); //1已支付 2部分退款 3全额退款
        $business_type=$this->request->post('business_type','','trim'); //所有业务类型
        $date=$this->request->post('date','');
        $page = $this->request->post('page', 1,'intval');
        $limit=15;
        try{
            $whereArr=array();
            $whereArr[]=array('village_id','=',$village_id);
            $whereArr[] = array('source_from','=','1');
            if($order_type==1 && !empty($order_no)){
                $whereArr[]=array('order_no','=',$order_no);
            }else if($order_type==2){
                $whereArr[]=array('pay_order_no','=',$order_no);
            }else if($order_type==3){
                $whereArr[]=array('third_transaction_no','=',$order_no);
            }
            if($order_status==1){
                $whereArr[]=array('refund_status','=',0);
            }elseif ($order_status==2){
                $whereArr[]=array('refund_status','=',1);
            }elseif ($order_status==3){
                $whereArr[]=array('refund_status','=',2);
            }
            if(!empty($business_type)){
                $whereArr[]=array('house_type','like','%'.$business_type.'%');
                //$whereArr[] = array('house_type','find in set',$business_type);
            }
            if(!empty($date) && is_array($date)){
                if (!empty($date[0])) {
                    $whereArr[] = ['pay_time', '>=', strtotime($date[0] . ' 00:00:00')];
                }
                if (!empty($date[1])) {
                    $whereArr[] = ['pay_time', '<=', strtotime($date[1] . ' 23:59:59')];
                }
            }
            $housePaidOrderRecordService = new HousePaidOrderRecordService();
            $fieldStr='*';
            $ret = $housePaidOrderRecordService->getPaidOrderRecordList($village_id,$whereArr,$fieldStr, $page, $limit);
            $site_url=cfg('site_url');
            $site_url=trim($site_url,'/');
            $ret['excelExportOutFileUrl']=$site_url.'/index.php?g=Index&c=ExportFile&a=download_house_export_file&export_from=house_paid_order_record';
            return api_output(0,$ret);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function getHouseNewPayOrderList(){
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        $order_id=$this->request->post('order_id',0,'intval');
        $sub_order_ids=$this->request->post('sub_order_ids','','trim');
        $table_name=$this->request->post('table_name','','trim');
        $record_id=$this->request->post('record_id',0,'intval');
        $room_id=$this->request->post('room_id',0,'intval');
        $page = $this->request->post('page', 1,'intval');
        $limit=15;
        $whereArr=array();
        $whereArr[]=array('o.village_id','=',$village_id);
        $whereArr[]=array('o.summary_id','=',$order_id);
        $whereArr[] = ['o.is_discard','=',1];
        $whereArr[] = ['o.is_paid','=',1];
        $whereArr[] = ['o.order_type','<>','non_motor_vehicle'];
        try {
            $service_house_new_cashier = new HouseNewCashierService();
            $field='o.*,p.name as project_name';
            $list = $service_house_new_cashier->getCancelOrder($whereArr,'',$field,$page,$limit,'o.pay_time DESC');
            if($room_id<=0 && isset($list['list']) && $list['list'] && $list['list']['0']['room_id']){
                $room_id=$list['list']['0']['room_id'];
            }
            $list['user_info']=$service_house_new_cashier->getVacancyUserInfo($village_id,$room_id,true);
            $list['user_info']['room_id']=$room_id;
            return api_output(0,$list);
        }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
        }
    }

    public function  excelExportPaidOrderRecordOut(){
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        $order_type=$this->request->post('order_type','','intval'); //1业务订单编号 2商户支付订单号 3第三方支付流水号
        $order_no=$this->request->post('order_no','','trim');
        $order_status=$this->request->post('order_status',0,'intval'); //1已支付 2部分退款 3全额退款
        $business_type=$this->request->post('business_type','','trim'); //所有业务类型
        $date=$this->request->post('date','');
        try{
            $param=array();
            $param['village_id']=$village_id;
            $param['property_id']=$property_id;
            $param['order_type']=$order_type;
            $param['order_no']=$order_no;
            $param['order_status']=$order_status;
            $param['business_type']=$business_type;
            $param['date']=!empty($date) ? $date:'';
            $param['export_type']='house_paid_order_record';
            $param['export_title']='小区已支付订单流水导出';
            $houseNewTransactionSummaryService = new HouseNewTransactionSummaryService();
            $ret = $houseNewTransactionSummaryService->addExportLog($village_id,$param);
            return api_output(0,$ret);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function getVillageSingleKeepers()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id = $this->adminUser['property_id'];
        if ($village_id < 1) {
            return api_output(1002, [], '登录超时，请先去登录！');
        }
        try {
            $where = array(
                ['village_id', '=', $village_id],
                ['single_id', '>', 0],
                ['floor_id', '<', 1],
                ['work_arr', '<>', ''],
            );
            $housekeepers = (new KefuService())->getHousekeepersOfSingle($where);
            return api_output(0, $housekeepers);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    
    public function getVillageKeepersList()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id = $this->adminUser['property_id'];
        if ($village_id < 1) {
            return api_output(1002, [], '登录超时，请先去登录！');
        }
        try {
            $where = array(
                ['village_id', '=', $village_id],
                ['single_id', '>', 0],
                ['floor_id', '<', 1],
                ['work_arr', '<>', ''],
            );
            $housekeepers = (new KefuService())->getHousekeepersList($where);
            return api_output(0, $housekeepers);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    
    public function getVillageSingleListByWid()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id = $this->adminUser['property_id'];
        $wids = $this->request->param('wids', '');
        if ($village_id < 1) {
            return api_output(1002, [], '登录超时，请先去登录！');
        }
        try {
            $where = array(
                ['village_id', '=', $village_id],
                ['single_id', '>', 0],
                ['floor_id', '<', 1],
            );
            $singleList = (new KefuService())->getVillageSingleListByWid($where,$wids);
            return api_output(0, $singleList);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    
    public function getVillageRoomFeeRateList(){
        $village_id = $this->adminUser['village_id'];
        $property_id = $this->adminUser['property_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '登录超时，请先去登录！');
        }
        $single_ids = $this->request->param('single_ids', '');
        $wids = $this->request->param('wids', '');
        $w_singles = $this->request->param('w_singles', '');
        $year_v = $this->request->param('year_v', 0,'intval');
        if($year_v<1){
            $year_v=date('Y');
        }
        $singleArr=array();
        /*
        if($w_singles){
            foreach ($w_singles as $tvv){
                $tmpArr=explode('_',$tvv);
                $singleArr[]=$tmpArr['1'];
            }
            $singleArr=array_unique($singleArr,SORT_NUMERIC);
        }
        */
        if(!empty($single_ids)){
            $singleArr=array_unique($single_ids);
        }else if(!empty($wids)){
            $where = array(
                ['village_id', '=', $village_id],
                ['single_id', '>', 0],
                ['floor_id', '<', 1],
            );
            $singleList = (new KefuService())->getVillageSingleListByWid($where,$wids);
            $singleArr=$singleList['single_ids'];
        }
        try {
            $houseNewTransactionSummaryService = new HouseNewTransactionSummaryService();
            $rets=$houseNewTransactionSummaryService->getRoomFeeRateCustomizedList($year_v,$village_id,$singleArr,'village');
            return api_output(0, $rets);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function getVillageRoomUnPayRateList(){
        $village_id = $this->adminUser['village_id'];
        $property_id = $this->adminUser['property_id'];
        
        if (empty($village_id)) {
            return api_output(1002, [], '登录超时，请先去登录！');
        }
        $w_singles = $this->request->param('w_singles', '');
        $year_v = $this->request->param('year_v', 0,'intval');
        $is_split = $this->request->param('is_split', 0 ,'intval');
        $single_ids = $this->request->param('single_ids', '');
        $wids = $this->request->param('wids', '');
        if($year_v<1){
            $year_v=date('Y');
        }
        $singleArr=array();
        /*
        if($w_singles){
            foreach ($w_singles as $tvv){
                $tmpArr=explode('_',$tvv);
                $singleArr[]=$tmpArr['1'];
            }
            $singleArr=array_unique($singleArr,SORT_NUMERIC);
        }
        */
        if(!empty($single_ids)){
            $singleArr=array_unique($single_ids);
        }else if(!empty($wids)){
            $where = array(
                ['village_id', '=', $village_id],
                ['single_id', '>', 0],
                ['floor_id', '<', 1],
            );
            $singleList = (new KefuService())->getVillageSingleListByWid($where,$wids);
            $singleArr=$singleList['single_ids'];
        }
        try {
            $houseNewTransactionSummaryService = new HouseNewTransactionSummaryService();
            $rets=$houseNewTransactionSummaryService->getVillageRoomUnPayRateList($year_v,$village_id,$singleArr,'village',$is_split);
            return api_output(0, $rets);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    
    public function getRoomRateSummaryRecordList(){
        $village_id = $this->adminUser['village_id'];
        $property_id = $this->adminUser['property_id'];
        $rate_type = $this->request->param('rate_type', 0,'intval');
        if (empty($village_id)) {
            return api_output(1002, [], '登录超时，请先去登录！');
        }
        try {
            $houseNewTransactionSummaryService = new HouseNewTransactionSummaryService();
            $whereArr=array(['village_id','=',$village_id],['rate_type','=',$rate_type],['xtype','<',1]);
            $rets=$houseNewTransactionSummaryService->getRoomRateSummaryRecordData($whereArr);
            return api_output(0, $rets);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function getOrderInfoByRuleList(){
        $village_id = $this->adminUser['village_id'];
        $page = $this->request->post('page', 1,'intval');
        $limit = $this->request->post('limit', 10,'intval');
        $charge_type = $this->request->post('charge_type', '', 'trim');
        $charge_project_id = $this->request->post('charge_project_id', 0, 'intval');
        $rule_name = $this->request->post('rule_name', '', 'trim');
        $date = $this->request->post('date');
        $order_service_type = $this->request->post('order_service_type', 0, 'intval'); //0 计费开始时间 1计费结束时间 2计费开始或结束时间
        $xtype = $this->request->post('xtype','', 'trim');
        $houseNewTransactionSummaryService = new HouseNewTransactionSummaryService();
        $where = [];
        $where[] = ['is_discard', '<>', 2];
        $where[] = ['village_id', '=', $village_id];
        
        $whereRule = [];
        $whereRule[] = ['village_id', '=', $village_id];
        $whereRule[] = ['status', '<>', 4];
        $service_start_time=0;
        $service_end_time=0;
        if (!empty($date) && count($date) > 0) {
            switch ($order_service_type){
                case 0:
                    if (!empty($date[0])) {
                        $where[] = ['service_start_time', '>=', strtotime($date[0] . ' 00:00:00')];
                    }
                    if (!empty($date[1])) {
                        $where[] = ['service_start_time', '<=', strtotime($date[1] . ' 23:59:59')];
                    }
                    break;
                case 1:
                    if (!empty($date[0])) {
                        $where[] = ['service_end_time', '>=', strtotime($date[0] . ' 00:00:00')];
                    }
                    if (!empty($date[1])) {
                        $where[] = ['service_end_time', '<=', strtotime($date[1] . ' 23:59:59')];
                    }
                    break;
                case 2:
                    /*
                    if (!empty($date[0])) {
                        $where[] = ['service_start_time', '>=', strtotime($date[0] . ' 00:00:00')];
                    }
                    if (!empty($date[1])) {
                        $where[] = ['service_end_time', '<=', strtotime($date[1] . ' 23:59:59')];
                    }
                    */
                    $where[] = ['service_start_time|service_end_time', 'between', [strtotime($date[0] . ' 00:00:00'),strtotime($date[1] . ' 23:59:59')]];
                    
                    break;
            }
        }
        if (!empty($charge_type)) {
            $where[] = ['order_type', '=', $charge_type];
        }
        if (!empty($charge_project_id)) {
            $where[] = ['project_id', '=', $charge_project_id];
            $whereRule[] = ['charge_project_id', '=', $charge_project_id];
        }
        if (!empty($rule_name)) {
            $whereRule[] = ['charge_name', 'like', '%' . $rule_name . '%'];
            $houseNewChargeProjectService = new HouseNewChargeProjectService();
            $ruleIdList = $houseNewChargeProjectService->getChargeRuleList($whereRule, 'id');
            $ruleIds = array();
            if ($ruleIdList) {
                foreach ($ruleIdList as $rvv) {
                    $ruleIds[] = $rvv['id'];
                }
            }
            if ($ruleIds) {
                $ruleIds = array_unique($ruleIds);
                $where[] = ['rule_id', 'in', $ruleIds];
            }
        }
        if (!empty($date) && !empty($date[0])) {
            $service_start_time=strtotime($date[0] . ' 00:00:00');
        }
        if (!empty($date) && !empty($date[1])) {
            $service_end_time=strtotime($date[1] . ' 23:59:59');
        }
        $extraArr=array('order_service_type'=>$order_service_type);
        $extraArr['service_start_time']=$service_start_time;
        $extraArr['service_end_time']=$service_end_time;
        try {
            if($xtype=='getOrderTotalInfoByServiceTime'){
                $list = $houseNewTransactionSummaryService->getOrderTotalInfoByServiceTime($where, $village_id, $extraArr,'rule_id');
            }else{
                $list = $houseNewTransactionSummaryService->HouseFeeOrderInfoList($where, $village_id, $extraArr, $page, $limit, 'rule_id');
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list);
    }
}