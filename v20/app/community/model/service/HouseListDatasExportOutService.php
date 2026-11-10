<?php
/**
 * Created by PhpStorm.
 * 交易汇总
 * User: wanzy
 * DateTime: 2021/11/16 14:56
 */

namespace app\community\model\service;

use app\common\model\service\export\ExportService as BaseExportService;
use app\community\model\db\ExportLog;
use app\community\model\service\order\PropertyBillOrderService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use app\community\model\service\HouseNewTransactionSummaryService;
use app\traits\HouseListDatasExportOutTraits;
use app\community\model\service\HousePaidOrderRecordService;
use app\community\model\service\HouseNewCashierService;
use think\Exception;
use app\community\model\service\ConfigService;
use app\common\model\service\config\ConfigCustomizationService;
class HouseListDatasExportOutService
{
    use HouseListDatasExportOutTraits;

    public function startExecExportOut($data = array())
    {
        $root=dirname(dirname(dirname(__DIR__)));
        $root=dirname(dirname($root));
        $basePath =  rtrim($root,'/');
        $_SERVER['DOCUMENT_ROOT']=$basePath;
        fdump_api(['data' => $data], '000startExecExportOut', 1);
        $export_type = '';  //导出类型
        $export_id = 0;
        if (empty($data) || !isset($data['export_type'])) {
            return true;
        }
        $export_type = $data['export_type'];
        if (isset($data['export_id'])) {
            $export_id = $data['export_id'];
        }
        if ($export_id < 1) {
            return true;
        }
        $configService= new ConfigService();
        $tmpConfig=$configService->get_config('house_desensitization_tel','value');
        if($tmpConfig && isset($tmpConfig['value'])){
            cfg('house_desensitization_tel',$tmpConfig['value']);
        }
        $tmpConfig=$configService->get_config('house_desensitization_idnum','value');
        if($tmpConfig && isset($tmpConfig['value'])){
            cfg('house_desensitization_idnum',$tmpConfig['value']);
        }
        $exportLogDb = new ExportLog();
        $whereArr = array('export_id' => $export_id);
        $exportLog = $exportLogDb->getOneData($whereArr);
        if ($exportLog && !$exportLog->isEmpty()) {
            $exportLog = $exportLog->toArray();
            if ($exportLog['status'] == 1 && !empty($exportLog['file_name'])) {
                return true;
            }
            $param = $exportLog['param'] ? json_decode($exportLog['param'], 1) : array();
            $param = $param && is_array($param) ? $param : array();
            $param = array_merge($data, $param);
            switch ($export_type){
                case 'house_financial_statement':
                    $xtype='';
                    $pay_status='';
                    $tj_type=0;  //0 按标准  1按项目
                    if(isset($param['xtype'])){
                        $xtype=trim($param['xtype']);
                    }
                    if(isset($param['pay_status'])){
                        $pay_status=trim($param['pay_status']);
                    }
                    if(isset($param['tj_type'])){
                        $tj_type=intval($param['tj_type']);
                    }
                    if(!in_array($pay_status,['no_pay','is_pay'])){
                        $pay_status='no_pay';
                    }
                    $village_id=0;
                    if(isset($param['village_id'])){
                        $village_id=trim($param['village_id']);
                    }
                    if($village_id>0 && ($xtype=='daily' || $xtype=='monthly')){
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
                        if(isset($param['charge_project_id']) && $param['charge_project_id']>0){
                            $whereArr[]=array('o.project_id','=',$param['charge_project_id']);
                        }
                        if(isset($param['rule_id']) && $param['rule_id']>0 && $tj_type<1){
                            $whereArr[]=array('o.rule_id','=',$param['rule_id']);
                        }
                        $date='';
                        if(isset($param['date']) && $param['date']){
                            $date=$param['date'];
                        }
                        $end_date='';
                        if(isset($param['end_date']) && $param['end_date']){
                            $end_date=$param['end_date'];
                        }
                        if($xtype=='daily' && !empty($date)){
                            if(isset($param['order_service_type']) && $param['order_service_type']==0){
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
                            }else if(isset($param['order_service_type']) && $param['order_service_type']==1){
                                //按开始时间
                                if (!empty($date[0])) {
                                    $whereArr[] = ['o.service_start_time', '>=', strtotime($date[0] . ' 00:00:00')];
                                }
                                if (!empty($date[1])) {
                                    $whereArr[] = ['o.service_start_time', '<=', strtotime($date[1] . ' 23:59:59')];
                                }
                            }elseif(isset($param['order_service_type']) && $param['order_service_type']==2){
                                //按结束时间
                                if (!empty($date[0])) {
                                    $whereArr[] = ['o.service_end_time', '>=', strtotime($date[0] . ' 00:00:00')];
                                }
                                if (!empty($date[1])) {
                                    $whereArr[] = ['o.service_end_time', '<=', strtotime($date[1] . ' 23:59:59')];
                                }
                            }else if(isset($param['order_service_type']) && $param['order_service_type']==3){
                                //按开始结束时间
                                if (!empty($date[0])) {
                                    $whereArr[] = ['o.service_start_time', '>=', strtotime($date[0] . ' 00:00:00')];
                                }
                                if (!empty($date[1])) {
                                    $whereArr[] = ['o.service_end_time', '<=', strtotime($date[1] . ' 23:59:59')];
                                }
                            }
                        }else  if($xtype=='monthly' &&  (!empty($date) || !empty($end_date))){

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
                            if(isset($param['order_service_type']) && $param['order_service_type']==0){
                                if($pay_status=='no_pay'){
                                    //按账单生成时间
                                    $whereArr[] = ['o.add_time', '>=', strtotime($stratTime)];
                                    $whereArr[] = ['o.add_time', '<=', strtotime($endTime)];
                                }else{
                                    //按账单支付时间
                                    $whereArr[] = ['o.pay_time', '>=', strtotime($stratTime)];
                                    $whereArr[] = ['o.pay_time', '<=', strtotime($endTime)];
                                }
                            }else if(isset($param['order_service_type']) && $param['order_service_type']==1){
                                //按开始时间
                                $whereArr[] = ['o.service_start_time', '>=', strtotime($stratTime)];
                                $whereArr[] = ['o.service_start_time', '<=', strtotime($endTime)];

                            }elseif(isset($param['order_service_type']) && $param['order_service_type']==2){
                                //按结束时间
                                $whereArr[] = ['o.service_end_time', '>=', strtotime($stratTime)];
                                $whereArr[] = ['o.service_end_time', '<=', strtotime($endTime)];
                            }else if(isset($param['order_service_type']) && $param['order_service_type']==3){
                                //按开始结束时间
                                $whereArr[] = ['o.service_start_time', '>=', strtotime($stratTime)];
                                $whereArr[] = ['o.service_end_time', '<=', strtotime($endTime)];
                            }
                        }
                        $houseNewTransactionSummaryService = new HouseNewTransactionSummaryService();
                        if($tj_type==1){
                            $exportArr = $houseNewTransactionSummaryService->getSummaryByRoomAndProjectList($village_id,$whereArr,$pay_status, 0, 20,true);
                        }else{
                            $exportArr = $houseNewTransactionSummaryService->getSummaryByRoomAndRuleList($village_id,$whereArr,$pay_status, 0, 20,true); 
                        }
                        $filename = date("YmdHis") . '-' .rand(1000000,9999999).'.xlsx' ;
                        $tips='';
                        if($pay_status=='no_pay'){
                            $tips='应收账单';
                        }elseif ($pay_status=='is_pay'){
                            $tips='已缴账单';
                        }
                        if($xtype=='daily'){
                            $tips.='日报表';
                        }else if($xtype=='monthly'){
                            $tips.='月报表';
                        }
                        $this->saveExcel($exportArr['export_title'], $exportArr, $filename, $tips,'orderByRule');
                        $whereArr = array('export_id' => $export_id);
                        $saveArr=array('status'=>1,'file_name'=>$filename);
                        $exportLogDb->saveOneData($whereArr,$saveArr);
                    }elseif ($village_id>0 && $xtype=='yearly'){
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
                        if(isset($param['charge_project_id']) && $param['charge_project_id']>0){
                            $whereArr[]=array('project_id','=',$param['charge_project_id']);
                        }
                        if(isset($param['rule_id']) && $param['rule_id']>0 && $tj_type<1){
                            $whereArr[]=array('rule_id','=',$param['rule_id']);
                        }
                        $year_v=0;
                        if(isset($param['year_v']) && $param['year_v']>0){
                            $year_v=$param['year_v'];
                        }
                        $order_service_type=0;
                        if(isset($param['order_service_type'])){
                            $order_service_type=$param['order_service_type'] ? intval($param['order_service_type']):0;
                        }
                        $year_v=$year_v>1971 ? $year_v:date('Y');
                        $extraArr=array('year_v'=>$year_v,'order_service_type'=>$order_service_type);
                        $houseNewTransactionSummaryService = new HouseNewTransactionSummaryService();
                        if($tj_type==1){
                            $exportArr = $houseNewTransactionSummaryService->getSummaryByYearAndProjectList($village_id,$whereArr,$pay_status,$extraArr, 0, 20,true);
                        }else{
                            $exportArr = $houseNewTransactionSummaryService->getSummaryByYearAndRuleList($village_id,$whereArr,$pay_status,$extraArr, 0, 20,true);
                        }
                        
                        $filename = date("YmdHis") . '-' .rand(1000000,9999999).'.xlsx' ;
                        $tips='';
                        if($pay_status=='no_pay'){
                            $tips='应收账单年报表';
                        }elseif ($pay_status=='is_pay'){
                            $tips='已缴账单年报表';
                        }
                        $this->saveExcel($exportArr['export_title'], $exportArr, $filename, $tips,'orderByYear');
                        $whereArr = array('export_id' => $export_id);
                        $saveArr=array('status'=>1,'file_name'=>$filename);
                        $exportLogDb->saveOneData($whereArr,$saveArr);
                    }
                    break;
                case 'house_paid_order_record':
                    $village_id=0;
                    if(isset($param['village_id'])){
                        $village_id=trim($param['village_id']);
                    }
                    $housePaidOrderRecordService = new HousePaidOrderRecordService();
                    $whereArr = $housePaidOrderRecordService->getExportWhere($village_id, $param);
                    $date = $param['date'] ?? [];
                    $fieldStr='*';
                    $exportArr = $housePaidOrderRecordService->getPaidOrderRecordList($village_id,$whereArr,$fieldStr, 0, 20,true);
                    $filename = date("YmdHis") . '-' .rand(1000000,9999999).'.xlsx' ;
                    $villageInfo = (new \app\community\model\db\HouseVillage())->getInfo(['village_id' => $village_id], 'village_name');
                    $spreadsheet = $housePaidOrderRecordService->buildPaidRecordSpreadsheet($exportArr['lists'], $villageInfo['village_name'] ?? '', $date);
                    // 此处只保存文件，直接执行时不能给 JSON 响应设置 Excel 下载头。
                    \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx')->save(dirname(__DIR__, 4) . '/runtime/' . $filename);
                    $whereArr = array('export_id' => $export_id);
                    $saveArr=array('status'=>1,'file_name'=>$filename);
                    $exportLogDb->saveOneData($whereArr,$saveArr);
                    break;
                case 'house_order_export_out':
                    $service_house_new_cashier = new HouseNewCashierService();
                    $xtype='';
                    if(isset($param['xtype'])){
                        $xtype=trim($param['xtype']);
                    }
                    $village_id=isset($param['village_id']) ? $param['village_id']:0;
                    $root=dirname(dirname(dirname(__DIR__)));
                    $root=dirname(dirname($root));
                    $basePath =  rtrim($root,'/') . '/v20/runtime/';
                    $configCustomizationService= new ConfigCustomizationService();
                    $isYuyanAihorsenetSpecial=$configCustomizationService->getYuyanAihorsenetSpecialJudge(true);
                    if($xtype=='is_payed_order'){
                        $where=isset($param['where']) && $param['where']? $param['where']:'';
                        $where1=isset($param['where1']) && $param['where1'] ? $param['where1']:'(`o`.`is_refund`=1 OR `o`.`refund_money`<`o`.`modify_money`)';
                        $field=isset($param['field']) && $param['field'] ? $param['field']:'o.*,p.name as project_name,n.charge_number_name,r.charge_name';
                        $order=isset($param['order']) && $param['order'] ?$param['order']:'o.room_id DESC,o.position_id DESC,o.service_start_time DESC,o.pay_time DESC' ;
                        $exportPattern=isset($param['exportPattern']) ? $param['exportPattern']:1;
                        $single_id_arr=isset($param['single_id_arr']) ? $param['single_id_arr']:'';
                        if($exportPattern==1){
                            $order='o.pay_time DESC,o.service_start_time DESC,o.service_end_time DESC';
                        }
                        if($isYuyanAihorsenetSpecial){
                            $order='o.room_id DESC,o.position_id DESC,o.project_id DESC,o.service_start_time ASC,o.pay_time DESC';
                        }
                        $exporttype=array();
                        $filename = date("YmdHis") . 'payed_order_export-' .rand(100,999);
                        $exporttype['basePath']=$basePath;
                        $exporttype['filename']=$filename;
                        $exporttype['is_redis_queue']=1;
                        if($where && is_array($where)){
                            $filename_tmp = $service_house_new_cashier->printPayOrder($where,$where1,$field,$order,$exporttype,$exportPattern,$single_id_arr);
                            if($filename_tmp){
                                $whereArr = array('export_id' => $export_id);
                                $saveArr=array('status'=>1,'file_name'=>$filename_tmp);
                                $exportLogDb->saveOneData($whereArr,$saveArr);
                            }
                        }
                        
                    }elseif($xtype=='no_pay_order_item'){
                        $where=isset($param['where']) && $param['where']? $param['where']:'';
                        $fieldStr=isset($param['field']) && $param['field'] ? $param['field']:'o.village_id,o.order_id,o.total_money,o.name,o.phone,o.room_id,o.position_id,o.uid,o.property_id,o.order_type,o.project_id,o.rule_id,r.charge_name,p.name as project_name,n.charge_number_name,o.service_start_time,o.service_end_time,o.last_ammeter,o.now_ammeter';
                        $order=isset($param['order']) && $param['order'] ? $param['order']:'o.order_id DESC' ;
                        $single_id_arr=isset($param['single_id_arr']) ? $param['single_id_arr']:'';
                        if($isYuyanAihorsenetSpecial){
                            $order='o.room_id DESC,o.position_id DESC,o.project_id DESC,o.service_start_time ASC,o.order_id DESC' ;
                        }
                        $exportPattern=isset($param['exportPattern']) ? $param['exportPattern']:1;
                        if($single_id_arr && is_array($single_id_arr)){
                            $where['single_id_arr']=$single_id_arr;
                        }
                        $data = $service_house_new_cashier->getNewPayOrders($where,$fieldStr,0,0,$order);
                        if($exportPattern != 1){
                            $data = $this->sortOrderData($data);
                        }
                        $title = ['物业编号','车位号','车位号/房间号','业主名','电话','所属收费科目','收费项目名称','收费标准名称','应收金额','计费开始时间','计费结束时间','上次度数','本次度数'];
                        $filename = date("YmdHis") . 'no_pay_order_item_export-' .rand(100,999);
                        $exportInfo=array();
                        $exportInfo['basePath']=$basePath;
                        $exportInfo['filename']=$filename;
                        $exportInfo['is_redis_queue']=1;
                        $filename_tmp=$service_house_new_cashier->receivableOrderImport($data,$title,$filename,$exportPattern,$exportInfo);
                        if($filename_tmp){
                            $whereArr = array('export_id' => $export_id);
                            $saveArr=array('status'=>1,'file_name'=>$filename_tmp);
                            $exportLogDb->saveOneData($whereArr,$saveArr);
                        }
                    }elseif($xtype=='no_pay_order_merge'){
                        $where=isset($param['where']) && $param['where']? $param['where']:'';
                        $fieldStr=isset($param['field']) && $param['field'] ? $param['field']:'o.village_id,r.charge_name,p.name as project_name,o.order_id,n.charge_number_name,o.total_money,o.service_start_time,o.service_end_time,o.last_ammeter,o.now_ammeter,o.name,o.phone,o.room_id,o.position_id';
                        $order=isset($param['order']) && $param['order'] ? $param['order']:'o.room_id DESC,o.position_id DESC,n.id DESC,o.project_id DESC';
                        $single_id_arr=isset($param['single_id_arr']) ? $param['single_id_arr']:'';
                        if($isYuyanAihorsenetSpecial){
                            $order='o.room_id DESC,o.position_id DESC,o.project_id DESC,o.service_start_time ASC,o.order_id DESC' ;
                        }
                        $filename = date("YmdHis") . 'no_pay_order_merge-' .rand(100,999);
                        $exportInfo=array();
                        $exportInfo['basePath']=$basePath;
                        $exportInfo['filename']=$filename;
                        $exportInfo['is_redis_queue']=1;
                        $filename_tmp = $service_house_new_cashier->printOrder($where,$fieldStr,$order,$exportInfo,$single_id_arr);
                        if($filename_tmp){
                            $whereArr = array('export_id' => $export_id);
                            $saveArr=array('status'=>1,'file_name'=>$filename_tmp);
                            $exportLogDb->saveOneData($whereArr,$saveArr);
                        }
                    }elseif($xtype=='no_pay_hang_order'){
                        //欠费账单导出
                        $where=isset($param['where']) && $param['where']? $param['where']:'';
                        $fieldStr=isset($param['field']) && $param['field'] ? $param['field']:'o.*,r.charge_name,p.name as project_name,n.charge_number_name';
                        $order=isset($param['order']) && $param['order'] ? $param['order']:'o.order_id DESC';
                        $where['showID']=false;
                        $data = $service_house_new_cashier->getNewPayOrders($where, $fieldStr, 0, 20, $order);
                        $title = ['车位号/房间号','业主','电话','所属收费科目','收费项目名称','收费标准名称','实际缴费金额','应收费用','计费开始时间','计费结束时间','账单生成时间','物业编号','车位号'];
                        $filename = date("YmdHis") . 'no_pay_hang_order_export-' .rand(100,999).'.xlsx' ;
                        $tips='历史欠费账单列表';
                        $this->saveByFieldExcel($title, $data, $filename, $tips,'no_pay_hang_order');
                        $whereArr = array('export_id' => $export_id);
                        $saveArr=array('status'=>1,'file_name'=>$filename);
                        $exportLogDb->saveOneData($whereArr,$saveArr);
                    }elseif($xtype=='no_pay_discard_order'){
                        //欠费账单导出
                        $where=isset($param['where']) && $param['where']? $param['where']:'';
                        $fieldStr=isset($param['field']) && $param['field'] ? $param['field']:'o.village_id,o.update_time,o.property_id,o.project_id,o.last_ammeter,o.order_type,o.now_ammeter,o.role_id,o.login_role,o.role_name,o.order_id,o.room_id,o.position_id,p.name as project_name,n.charge_number_name,o.modify_money as total_money,o.name as user_name,o.phone as user_phone,o.service_start_time,o.service_end_time,o.discard_reason,o.is_refund';
                        $order=isset($param['order']) && $param['order'] ? $param['order']:'o.order_id DESC';
                        $data = $service_house_new_cashier->getCancelOrder1($where,'',$fieldStr,0,20,$order);
                        //fdump_api(['where'=>$where,'data' => $data], '000startExecExportOut', 1);
                        $title = ['房间号/车位号','业主名','电话','所属收费科目','收费项目名称','应收金额','计费开始时间','计费结束时间','上次读数','本次读数','账单作废时间','作废原因','物业编号','车位号'];
                        $filename = date("YmdHis") . 'no_pay_discard_order_export-' .rand(100,999).'.xlsx' ;
                        $tips='作废账单列表';
                        $this->saveByFieldExcel($title, $data['list'], $filename, $tips,'no_pay_discard_order');
                        $whereArr = array('export_id' => $export_id);
                        $saveArr=array('status'=>1,'file_name'=>$filename);
                        $exportLogDb->saveOneData($whereArr,$saveArr);
                    }
                    
                    break;
                case 'village_monthly_bill_export':
                    (new PropertyBillOrderService())->handleVillageMonthlyBillExport($param);
                    break;
                case 'village_yearly_bill_export':
                    (new PropertyBillOrderService())->handleVillageYearlyBillExport($param);
                    break;
                case 'village_yearly_receivable_export':
                    (new PropertyBillOrderService())->handleVillageYearlyReceivableExport($param);
                    break;
                case 'house_financial_by_project':
                    $village_id=0;
                    if(isset($param['village_id'])){
                        $village_id=intval($param['village_id']);
                    }
                    $date='';
                    if(isset($param['date']) && $param['date']){
                        $date=trim($param['date']);
                    }
                    $pay_status='is_pay' ;//no_pay未支付 is_pay已支付
                    if(isset($param['pay_status']) && $param['pay_status']){
                        $pay_status=trim($param['pay_status']);
                    }
                    $xtype='daily'; //daily 按日
                    if(isset($param['xtype']) && $param['xtype']){
                        $xtype=trim($param['xtype']);
                    }
                    $charge_project_id=0;
                    if(isset($param['charge_project_id']) && $param['charge_project_id']){
                        $charge_project_id=intval($param['charge_project_id']);
                    }
                    $property_id = 0;
                    if (isset($param['property_id']) && $param['property_id']) {
                        $property_id = intval($param['property_id']);
                    }
                    $whereArr = array();
                    $whereArr[] = array('o.village_id', '=', $village_id);
                    $whereArr[] = array('o.order_type', '<>', 'non_motor_vehicle');
                    $whereRefundArr=$whereArr;
                    if ($pay_status == 'no_pay') {
                        $whereArr[] = array('o.is_paid', '=', 2);
                        $whereArr[] = array('o.is_discard', '=', 1);
                    } else if ($pay_status == 'is_pay') {
                        $whereArr[] = array('o.is_paid', '=', 1);
                        $whereArr[] = array('o.is_discard', '=', 1);
                        $whereRefundArr[]=array('o.is_paid','=',1);
                        $whereRefundArr[]=array('o.is_discard','=',1);
                        $whereArr['string']='(`o`.`refund_money`<`o`.`pay_money`)';
                    }
                    $dateTime = $date ? strtotime($date) : 0;
                    if ($dateTime < 100) {
                        $dateTimeStr = date('Y-m-d');
                        $dateTime = strtotime($dateTimeStr);
                    }
                    $dateTimeEnd = $dateTime + 3600 * 24 - 1;
                    //按账单支付时间
                    $whereArr[] = ['o.pay_time', '>=', $dateTime];
                    $whereArr[] = ['o.pay_time', '<=', $dateTimeEnd];
                    $whereRefundArr[]  = ['o.update_time', '>=', $dateTime];
                    $whereRefundArr[]  = ['o.update_time', '<=', $dateTimeEnd];
                    $houseNewTransactionSummaryService = new HouseNewTransactionSummaryService();
                    $extraArr = array('xtype' => $xtype, 'charge_project_id' => $charge_project_id, 'property_id' => $property_id,'date_time'=>$dateTime,'date_time_end'=>$dateTimeEnd);
                    $exportArr = $houseNewTransactionSummaryService->getSummaryByProjectList($village_id, $whereArr, $extraArr, 0, 20, $whereRefundArr,true);
                    $filename = date("YmdHis") . '-' . rand(1000000, 9999999) . '.xlsx';
                    $tips = '';
                    if ($pay_status == 'no_pay') {
                        $tips = '未缴日报表数据';
                    } elseif ($pay_status == 'is_pay') {
                        $tips = '已缴日报表数据';
                    }
                    $this->saveExcel($exportArr['export_title'], $exportArr, $filename, $tips, 'financialByProject');
                    $whereArr = array('export_id' => $export_id);
                    $saveArr = array('status' => 1, 'file_name' => $filename);
                    $exportLogDb->saveOneData($whereArr, $saveArr);
                    break;
                default:
                    return true;
            }
        }
        return true;
    }

    public function sortOrderData($data)
    {
        if(!$data){
            return $data;
        }
        $returnData = [];
        $newData = [];
        $numberData = [];
        foreach ($data as $v){
            $numberData[$v['number']][] = $v;
        }
        foreach ($numberData as $vNumber){
            foreach ($vNumber as $kkk=>$vvv){
                $newData[$vvv['number']][$vvv['charge_number_name']][] = $vvv;
            }
        }
        $newData = array_values($newData);
        foreach ($newData as $vData){
            foreach ($vData as $vvData){
                $returnData = array_merge($returnData,$vvData);
            }
        }
        return $returnData;
    }
    public function addExportLog($village_id=0,$param=array()){
        $exportLogDb=new ExportLog();
        $type='';
        $retArr=array('export_id'=>0,'msg'=>'');
        if(isset($param['export_type']) && !empty($param['export_type'])){
            $type=$param['export_type'];
        }
        if(empty($type)){
            $retArr['msg']='缺少导出类型标识！';
            return $retArr;
        }
        $title='';
        if(isset($param['export_title']) && !empty($param['export_title'])){
            $title=$param['export_title'];
        }
        $paramStr=json_encode($param,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $addArr=array('type'=>$type,'param'=>$paramStr,'status'=>0,'dateline'=>time());
        $addArr['title']=$title;
        $export_id=$exportLogDb->addOneData($addArr);
        $retArr['export_id']=$export_id;
        $queueData=$param;
        $queueData['export_id']=$export_id;
        $queueData['export_type']=$type;
        $this->exportExcelOutJob($queueData);
        return $retArr;
    }
    
    /**
     * 导出文件
     * @author:zhubaodi
     * @date_time: 2021/5/21 14:44
     */
    public function saveExcel($title, $data, $filename, $tips = '', $exporttype = '')
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getDefaultColumnDimension()->setWidth(24);
        $sheet->getDefaultRowDimension()->setRowHeight(22);//设置行高
        $sheet->getRowDimension('1')->setRowHeight(25);//设置行高
        $mergeCellCoordinate = 'A1:Q1';
        $sheet->getStyle($mergeCellCoordinate)->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells($mergeCellCoordinate); //合并单元格
        $sheet->getColumnDimension('A')->setWidth(32);
        $sheet->setCellValue('A1', $tips);

        //设置单元格内容
        $titCol = 'A';
        foreach ($title as $key => $value) {
            //单元格内容写入
            $sheet->setCellValue($titCol . '2', $value);
            $titCol++;
        }
        $titleCellCoordinate='A2:'.$titCol.'2';
        $cellStyle='A1:'.$titCol;
        $sheet->getStyle($titleCellCoordinate)->getFont()->setBold(true);
        //设置单元格内容
        $row = 3;
        if($data && isset($data['lists']) && $data['lists']){
            foreach ($data['lists'] as $k => $item) {
                $dataCol = 'A';
                foreach ($item as $value) {
                    //单元格内容写入
                    // $sheet->setCellValue($dataCol . $row, $value);
                    $value=$value ? strval($value):'';
                    if($exporttype=='financialByProject' && !$value){
                        $value='0';
                    }
                    $sheet->setCellValueExplicit($dataCol . $row, $value,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $dataCol++;
                }
                $row++;
            }
        }

        //保存
        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中
        $sheet->getStyle($cellStyle . $total_rows)->applyFromArray($styleArrayBody);
        //下载
        $basePath='';
        $root=dirname(dirname(dirname(__DIR__)));
        $root=dirname(dirname($root));
        $basePath =  rtrim($root,'/') . '/v20/runtime/';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet,$basePath);
        return true;
    }
    public function saveByFieldExcel($titleArr, $data, $filename, $tips = '', $exporttype = '')
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getDefaultColumnDimension()->setWidth(24);
        $sheet->getDefaultRowDimension()->setRowHeight(22);//设置行高
        $sheet->getRowDimension('1')->setRowHeight(25);//设置行高

        $sheet->getColumnDimension('A')->setWidth(32);
        $sheet->setCellValue('A1', $tips);
        $configCustomizationService= new ConfigCustomizationService();
        $is_func_efamily365_special=$configCustomizationService->getfuncEfamily365Special(true);
        if($is_func_efamily365_special && $exporttype=='no_pay_hang_order'){
            $title[]='房屋类型';
            $title[]='房屋出售状态';
            $title[]='房屋出售状态';
        }
        //设置单元格内容
        $titCol = 'A';
        foreach ($titleArr as $key => $value) {
            //单元格内容写入
            $sheet->setCellValue($titCol . '2', $value);
            $titCol++;
        }
        $mergeCellCoordinate = 'A1:'.$titCol.'1';
        $sheet->getStyle($mergeCellCoordinate)->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells($mergeCellCoordinate); //合并单元格
        $sheet->setCellValue('A1', $tips);
        
        $titleCellCoordinate='A2:'.$titCol.'2';
        $cellStyle='A1:'.$titCol;
        $sheet->getStyle($titleCellCoordinate)->getFont()->setBold(true);
        //设置单元格内容
        $row = 3;
        if($data){
            foreach ($data as $k => $item) {
                //单元格内容写入
                if($exporttype=='no_pay_hang_order'){
                    $sheet->setCellValue('A' . $row, $item['number']);
                    $sheet->setCellValue('B' . $row, $item['pay_bind_name']);
                    $sheet->setCellValue('C' . $row, $item['pay_bind_phone']);
                    $sheet->setCellValue('D' . $row, $item['charge_number_name']);
                    $sheet->setCellValue('E' . $row, $item['project_name']);
                    $sheet->setCellValue('F' . $row, $item['charge_name']);
                    $sheet->setCellValue('G' . $row, $item['modify_money']);
                    $sheet->setCellValue('H' . $row, $item['total_money']);
                    $sheet->setCellValue('I' . $row, $item['service_start_time_txt']);
                    $sheet->setCellValue('J' . $row, $item['service_end_time_txt']);
                    $sheet->setCellValue('K' . $row, $item['add_time_txt']);
                    $usernum=isset($item['usernum']) ? $item['usernum']:'';
                    $sheet->setCellValue('L' . $row, $usernum);
                    $position_num=isset($item['position_num']) ? strval($item['position_num']):'';
                    $sheet->setCellValueExplicit('M' . $row, $position_num,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    if($is_func_efamily365_special){
                        $house_type_str=isset($item['house_type_str']) ? $item['house_type_str']:'';
                        $sheet->setCellValue('N' . $row, $house_type_str);
                        $sell_status_str=isset($item['sell_status_str']) ? $item['sell_status_str']:'';
                        $sheet->setCellValue('O' . $row, $sell_status_str);
                        $user_status_str=isset($item['user_status_str']) ? $item['user_status_str']:'';
                        $sheet->setCellValue('P' . $row, $user_status_str);
                    }
                }else if($exporttype=='no_pay_discard_order'){
                    $sheet->setCellValue('A' . $row, $item['numbers']);
                    $sheet->setCellValue('B' . $row, $item['user_name']);
                    $sheet->setCellValue('C' . $row, $item['user_phone']);
                    $sheet->setCellValue('D' . $row, $item['charge_number_name']);
                    $sheet->setCellValue('E' . $row, $item['project_name']);
                    $sheet->setCellValue('F' . $row, $item['total_money']);
                    $sheet->setCellValue('G' . $row, $item['service_start_time']);
                    $sheet->setCellValue('H' . $row, $item['service_end_time']);
                    $sheet->setCellValue('I' . $row, $item['last_ammeter']);
                    $sheet->setCellValue('J' . $row, $item['now_ammeter']);
                    $sheet->setCellValue('K' . $row, $item['updateTime']);
                    $sheet->setCellValue('L' . $row, $item['discard_reason']);
                    $usernum=isset($item['usernum']) ? $item['usernum']:'';
                    $sheet->setCellValue('M' . $row, $usernum);
                    $position_num=isset($item['position_num']) ? strval($item['position_num']):'';
                    $sheet->setCellValueExplicit('N' . $row, $position_num,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
                $row++;
            }
        }
        //保存
        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中
        $sheet->getStyle($cellStyle . $total_rows)->applyFromArray($styleArrayBody);
        //下载
        $basePath='';
        $root=dirname(dirname(dirname(__DIR__)));
        $root=dirname(dirname($root));
        $basePath =  rtrim($root,'/') . '/v20/runtime/';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet,$basePath);
        return true;
    }
    
    /**
     * 下载表格
     */
    public function downloadExportFile($param)
    {
        $returnArr = [];
        if (!file_exists(request()->server('DOCUMENT_ROOT') . '/v20/runtime/' . $param)) {
            $returnArr['error'] = 1;
            return $returnArr;
        }
        $filename = $param;

        $ua = request()->server('HTTP_USER_AGENT');
        $ua = strtolower($ua);
        if (preg_match('/msie/', $ua) || preg_match('/edge/', $ua) || preg_match('/trident/', $ua)) { //判断是否为IE或Edge浏览器
            $filename = str_replace('+', '%20', urlencode($filename)); //使用urlencode对文件名进行重新编码
        }
        $returnArr['url'] = cfg('site_url') . '/v20/runtime/' . $param;
        $returnArr['error'] = 0;

        return $returnArr;
    }
}
