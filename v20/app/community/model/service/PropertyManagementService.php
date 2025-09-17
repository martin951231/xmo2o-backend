<?php
/**
 * 工单处理挪至物业移动管理端，物业人员可统一进行登录管理
 * @author weili
 * @date 2020/10/20
 */

namespace app\community\model\service;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\db\HouseMenuNew;
use app\community\model\db\HouseVillageCheckauthDetail;
use app\community\model\db\HouseVillageInfo;
use app\community\model\db\HouseVillageMeterReading;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageRepairCate;
use app\community\model\db\HouseVillageRepairFollow;
use app\community\model\db\HouseVillageRepairList;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\HouseWorker;
use app\community\model\db\MeterReadPerson;
use app\community\model\db\MerchantStoreKefu;
use app\community\model\db\MeterReadRecord;
use app\community\model\service\HouseVillageRepairLogService;
use app\community\model\service\HouseNewRepairService;
use app\community\model\service\HouseNewMeterService;
use app\community\model\service\HouseNewChargeRuleService;
use app\community\model\service\message\PushMessageHandleService;
use app\consts\HouseWorkConst;
use customization\customization;
use file_handle\FileHandle;
use think\facade\Db;
class PropertyManagementService
{
    use customization;
    /**
     * Notes: 首页统计
     * @param integer $property_id 物业id
     * @param string $phone 手机号码
     * @param integer $wid 工作人员id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: weili
     * @datetime: 2020/10/20 11:54
     */
    public function getRepair($wid=0, $property_id=0, $login_name='',$login_role = 0,$app_type = 'packapp',$worker_id=0)
    {
        $dbHouseVillageRepairList = new HouseVillageRepairList();
        $dbHouseVillageUserVacancy = new HouseVillageUserVacancy();
        $dbHouseVillageMeterReading = new HouseVillageMeterReading();
        $service_login = new ManageAppLoginService();
        $service_house_worker = new HouseWorkerService();
        $service_house_village = new HouseVillageService();
        $village_id=0;
        $is_car_sentry=0;
        if ($wid) {
            $work_info = $service_house_worker->getOneWorker(['wid'=>$wid],'*');
            $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$work_info['village_id']],'village_name,property_id');
            $village_name = $village_info['village_name'];
            $village_id=$work_info['village_id'];
            if(!$property_id) {
                $property_id = $village_info['property_id'];
            }
            if(isset($work_info['is_sentry']) && $work_info['is_sentry']>0 && $work_info['xtype']==2 && $work_info['relation_id']>0){
                $is_car_sentry=$work_info['is_sentry'];
            }
        } elseif ($property_id) {
            $property_info = $service_house_village->get_house_property($property_id,'property_name');
            $village_name = $property_info['property_name'];
        }
        if ($login_name) {
            $login_name = '，'.$login_name;
        }
        $prompt = $service_login->time_tip(0,$login_name, $village_name);
        if ($property_id) {
            $dbHouseVillage = new HouseVillage();
            $village_where[] = ['property_id', '=', $property_id];
            $village_where[] = ['status', '=', 1];
            $village_id_arr = $dbHouseVillage->getColumn($village_where, 'village_id');
            if($village_id_arr && count($village_id_arr)>0) {
                $public_where = ['village_id', 'in', $village_id_arr];
                $public_wheres = ['c.village_id', 'in', $village_id_arr];
            }else{
                $public_where = ['village_id','<',0];
                $public_wheres = ['c.village_id','<',0];
            }
        } else {
            $public_where = ['village_id','=',$work_info['village_id']];
            $public_wheres = ['c.village_id','=',$work_info['village_id']];
            $village_id_arr = [$work_info['village_id']];
        }
        $where = [];
        $where[] = $public_where;
//        $where[] = ['uid','<>',0];//用户uid
//        $where[] = ['status','=',0];
        $where[] = ['status','=',2];
        $where[] = ['wid','=',$wid];
        $pending_count = $dbHouseVillageRepairList->get_repair_list_num($where,'');//未处理工单
        $where1 = [];
        $where1[] = $public_where;
//        $where1[] = ['uid','<>',0];//用户uid
        $where1[] = ['status','=',1];
        $where1[] = ['wid','=',$wid];
        $not_receive_count = $dbHouseVillageRepairList->get_repair_list_num($where1,'');//未接工单

        $map = [];
        $map[] = $public_where;
        $map[] = ['status','in',[1,3]];
        $map[] = ['type','=',0];
        $map[] = ['uid','<>',0];
        $vacancy_count = $dbHouseVillageUserVacancy->getCount($map);//获取房屋总数量
        $reading_map[] = $public_where;
        $meter_reading_count = $dbHouseVillageMeterReading->getCount($reading_map,'layer_num');//已抄表房屋数量
        $no_meter_reading = $vacancy_count-$meter_reading_count;//没有抄表的房屋数量
        $db_meter_read_person = new MeterReadPerson();
        $map = [];
        $map[] = $public_where;
        $map[] = ['uid','=',$wid];
        $map[] = ['status','=',1];
        $meter_read_person_data = $db_meter_read_person->getLists($map);
        if($meter_read_person_data && !$meter_read_person_data->isEmpty())
        {
            $service_meter_read_person = new MeterReadPersonService();
            $meter_read_person_data =  $meter_read_person_data->toArray();
            $read_persons = array_column($meter_read_person_data,'cate_id');
            $where_meter = [];
            $where_meter[] = ['id','in',$read_persons];
            $where_meter[] = ['village_id','in',$village_id_arr];
            $waterList = $service_meter_read_person->getMeterReadCateList($where_meter, 'id,village_id,cate_name,cycle_time');
            $waterArr = [];
            $electricArr = [];
            $gasArr = [];
            $meterReadIds = [];
            if (!empty($waterList)) {
                $waterList = $waterList->toArray();
                foreach ($waterList as $vals) {
                    if ((isset($waterArr[$vals['village_id']]) && $waterArr[$vals['village_id']]) && $vals['village_id'] && '水费'==$vals['cate_name']) {
                        $waterArr[$vals['village_id']] = $vals;
                    } elseif ((isset($electricArr[$vals['village_id']]) && $electricArr[$vals['village_id']]) && $vals['village_id'] && '电费'==$vals['cate_name']) {
                        $electricArr[$vals['village_id']] = $vals;
                    } elseif ((isset($gasArr[$vals['village_id']]) && $gasArr[$vals['village_id']]) && $vals['village_id'] && '燃气费'==$vals['cate_name']) {
                        $gasArr[$vals['village_id']] = $vals;
                    }
                    if ($vals['village_id'] && $vals['cate_name']) {
                        $keys = $vals['village_id'].'_'.$vals['cate_name'];
                        if (!isset($meterRead[$keys])) {
                            $meterRead[$keys] = [];
                        }
                        $meterReadIds[$keys][] = $vals['id'];
                    }
                }
            }

            $where_record = [];
            $where_record[] = ['id','in',$read_persons];
            $where_record[] = ['village_id','in',$village_id_arr];
            $list_record = $service_meter_read_person->getMeterReadRecordList($where_record,'village_id,floor_id,room_id,cate_id,add_time');
            $listRecordArr = [];
            if (!empty($list_record)) {
                $list_record = $list_record->toArray();
                foreach ($list_record as $val1) {
                    if ($val1['village_id'] && $val1['floor_id'] && $val1['room_id'] && $val1['cate_id']) {
                        $keyVal = $val1['village_id'].'_'.$val1['floor_id'].'_'.$val1['room_id'].'_'.$val1['cate_id'];
                        $listRecordArr[$keyVal] = $val1;
                    }
                }
            }


            $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
            if($wid>0 && $village_id>0){
                $public_where = ['village_id','=',$village_id];
            }
            $vacancy_where[] = $public_where;
            $vacancy_where[] = ['uid','<>',0];
            $vacancy_list = $service_house_village_user_vacancy->getVacancyList($vacancy_where , true , 'pigcms_id asc');
            $meter_num = 0;
            foreach($vacancy_list as $k=>$v){
                $water = isset($waterArr[$v['village_id']])?$waterArr[$v['village_id']]:null;
                $electric = isset($electricArr[$v['village_id']])?$electricArr[$v['village_id']]:null;
                $gas = isset($gasArr[$v['village_id']])?$gasArr[$v['village_id']]:null;

                $water_data['village_id'] = $electric_data['village_id'] = $gas_data['village_id'] = $v['village_id'];
                $water_data['floor_id'] =$electric_data['floor_id'] =$gas_data['floor_id'] = $v['floor_id'];
                $water_data['room_id']=$electric_data['room_id']=$gas_data['room_id'] = $v['pigcms_id'];

                $water_data['cate_id'] = $water['id'];
                $electric_data['cate_id'] = $electric['id'];
                $gas_data['cate_id'] = $gas['id'];

                $waterKeyVal = $v['village_id'].'_'.$v['floor_id'].'_'.$v['pigcms_id'].'_'.$water['id'];
                $water_record = isset($listRecordArr[$waterKeyVal])?$listRecordArr[$waterKeyVal]:null;

                $electricKeyVal = $v['village_id'].'_'.$v['floor_id'].'_'.$v['pigcms_id'].'_'.$electric['id'];
                $electric_record = isset($listRecordArr[$electricKeyVal])?$listRecordArr[$electricKeyVal]:null;

                $gasKeyVal = $v['village_id'].'_'.$v['floor_id'].'_'.$v['pigcms_id'].'_'.$gas['id'];
                $gas_record = isset($listRecordArr[$gasKeyVal])?$listRecordArr[$gasKeyVal]:null;

                if($water_record && $electric_record && $gas_record){
                    if(date('d',$water_record['add_time']) >= $water['cycle_time']){
                        if(date('d',$electric_record['add_time']) >= $electric['cycle_time']){
                            if(date('d',$gas_record['add_time']) < $gas['cycle_time']){
                                $meter_num++;
                            }
                        }else{
                            $meter_num++;
                        }
                    }else{
                        $meter_num++;
                    }

                }elseif($water_record && $electric_record){
                    if(date('d',$water_record['add_time']) >= $water['cycle_time']){
                        if(date('d',$electric_record['add_time']) >= $electric['cycle_time']){
                            $key1 = $v['village_id'] .'_'.'燃气费';
                            $catIds = $meterReadIds[$key1];
                            if($catIds){
                                $whereCount = [];
                                $whereCount[] = ['village_id','=',$v['village_id']];
                                $whereCount[] = ['cate_id','in',$catIds];
                                $whereCount[] = ['', 'exp', Db::raw("FIND_IN_SET({$v['pigcms_id']},room_id)")];
                                $room_count = $service_meter_read_person->getMeterReadProjectCount($whereCount);
                                if ($room_count>0) {
                                    $meter_num += $room_count;
                                }
                            }
                        }else{
                            $meter_num++;
                        }
                    }else{
                        $meter_num++;
                    }
                }elseif($water_record && $gas_record){
                    if(date('d',$water_record['add_time']) >= $water['cycle_time']){
                        if(date('d',$gas_record['add_time']) >= $gas['cycle_time']){
                            $key1 = $v['village_id'] .'_'.'电费';
                            $catIds = $meterReadIds[$key1];
                            if($catIds){
                                $whereCount = [];
                                $whereCount[] = ['village_id','=',$v['village_id']];
                                $whereCount[] = ['cate_id','in',$catIds];
                                $whereCount[] = ['', 'exp', Db::raw("FIND_IN_SET({$v['pigcms_id']},room_id)")];
                                $room_count = $service_meter_read_person->getMeterReadProjectCount($whereCount);
                                if ($room_count>0) {
                                    $meter_num += $room_count;
                                }
                            }
                        }else{
                            $meter_num++;
                        }
                    }else{
                        $meter_num++;
                    }
                }elseif($gas_record && $electric_record){
                    if(date('d',$electric_record['add_time']) >= $electric['cycle_time']){
                        if(date('d',$gas_record['add_time']) >= $gas['cycle_time']){
                            $key1 = $v['village_id'] .'_'.'水费';
                            $catIds = $meterReadIds[$key1];
                            if($catIds){
                                $whereCount = [];
                                $whereCount[] = ['village_id','=',$v['village_id']];
                                $whereCount[] = ['cate_id','in',$catIds];
                                $whereCount[] = ['', 'exp', Db::raw("FIND_IN_SET({$v['pigcms_id']},room_id)")];
                                $room_count = $service_meter_read_person->getMeterReadProjectCount($whereCount);
                                if ($room_count>0) {
                                    $meter_num += $room_count;
                                }
                            }
                        }else{
                            $meter_num++;
                        }
                    }else{
                        $meter_num++;
                    }
                }else{
                    if($gas_record){
                        if(date('d',$gas_record['add_time']) >= $gas['cycle_time']){
                            $key1 = $v['village_id'] .'_'.'水费';
                            $cat1Ids = $meterReadIds[$key1];
                            $key2 = $v['village_id'] .'_'.'电费';
                            $cat2Ids = $meterReadIds[$key2];
                            $catIds = array_merge($cat1Ids,$cat2Ids);
                            if($catIds){
                                $whereCount = [];
                                $whereCount[] = ['village_id','=',$v['village_id']];
                                $whereCount[] = ['cate_id','in',$catIds];
                                $whereCount[] = ['', 'exp', Db::raw("FIND_IN_SET({$v['pigcms_id']},room_id)")];
                                $room_count = $service_meter_read_person->getMeterReadProjectCount($whereCount);
                                if ($room_count>0) {
                                    $meter_num += $room_count;
                                }
                            }
                        }else{
                            $meter_num++;
                        }
                    }elseif($electric_record){
                        if(date('d',$electric_record['add_time']) >= $electric['cycle_time']){
                            $key1 = $v['village_id'] .'_'.'水费';
                            $cat1Ids = $meterReadIds[$key1];
                            $key2 = $v['village_id'] .'_'.'燃气费';
                            $cat2Ids = $meterReadIds[$key2];
                            $catIds = array_merge($cat1Ids,$cat2Ids);
                            if($catIds){
                                $whereCount = [];
                                $whereCount[] = ['village_id','=',$v['village_id']];
                                $whereCount[] = ['cate_id','in',$catIds];
                                $whereCount[] = ['', 'exp', Db::raw("FIND_IN_SET({$v['pigcms_id']},room_id)")];
                                $room_count = $service_meter_read_person->getMeterReadProjectCount($whereCount);
                                if ($room_count>0) {
                                    $meter_num += $room_count;
                                }
                            }
                        }else{
                            $meter_num++;
                        }
                    }elseif ($water_record){
                        if(date('d',$water_record['add_time']) >= $water['cycle_time']){
                            $key1 = $v['village_id'] .'_'.'电费';
                            $cat1Ids = $meterReadIds[$key1];
                            $key2 = $v['village_id'] .'_'.'燃气费';
                            $cat2Ids = $meterReadIds[$key2];
                            $catIds = array_merge($cat1Ids,$cat2Ids);
                            if($catIds){
                                $whereCount = [];
                                $whereCount[] = ['village_id','=',$v['village_id']];
                                $whereCount[] = ['cate_id','in',$catIds];
                                $whereCount[] = ['', 'exp', Db::raw("FIND_IN_SET({$v['pigcms_id']},room_id)")];
                                $room_count = $service_meter_read_person->getMeterReadProjectCount($whereCount);
                                if ($room_count>0) {
                                    $meter_num += $room_count;
                                }
                            }
                        }else{
                            $meter_num++;
                        }
                    }else{
                        $meter_num++;
                    }
                }
            }
            $no_meter_reading = $meter_num;
            $is_meter_show = true;
        }else{
            if($wid>0 && $village_id>0){
                $public_where = ['village_id','=',$village_id];
            }
            $service_house_new_meter = new HouseNewMeterService();
            $where = [];
            $new_meter_where= $public_where;
            $new_meter_where['0']='p.village_id';
            $where[] = $new_meter_where;
            $where[] = ['p.status','=',1];
            $where[] = ['c.charge_type','in',['water','electric','gas']];
            try{
                $all_count_num=0;
                $data = $service_house_new_meter->getMeterProject($where,'p.village_id,p.name as project_name,c.charge_number_name as subject_name,charge_type,p.id as project_id,p.subject_id,p.mday',1,1000,'p.id DESC')->toArray();
                if($data){
                    $tday=date('j');
                    $allday =date('t');
                    $lastday=date('Y-m-'.$allday.' 23:59:59');
                    $lasttime=strtotime($lastday);
                    $service_house_new_charge_rule = new HouseNewChargeRuleService();
                    foreach ($data as $k=>$v){
                        $rule_id = $service_house_new_charge_rule->getValidChargeRule($v['project_id']);
                        if(!$rule_id){
                            continue;
                        }
                        if($v['mday']>0){
                            $startdate=date('Y-m-'.$v['mday'].' 00:00:00');
                            $starttime=strtotime($startdate);
                            $whereArr=array();
                            $whereArr[]=array('b.rule_id','=',$rule_id);
                            $whereArr[]=array('b.vacancy_id','>',0);
                            $whereArr[]=array('b.is_del','=',1);
                            $whereArr[]=array('b.project_id','=',$v['project_id']);
                            $whereArr[]=array('b.village_id','=',$v['village_id']);
                            $whereArr[]=array('b.bind_type','=',1);
                            $whereArr[]=array('v.uid','<>',0);
                            $count_num=$service_house_new_charge_rule->get_rule_bind_vecancy_count($whereArr);
                            $count_num=$count_num>0 ? $count_num:0;
                            
                            //已抄的
                            $wheremArr=array();
                            $wheremArr[]=array('village_id','=',$v['village_id']);
                            $wheremArr[]=array('project_id','=',$v['project_id']);
                            $wheremArr[]=array('charge_name','=',$v['project_name']);
                            $wheremArr[]=array('add_time','>=',$starttime);
                            $wheremArr[]=array('add_time','<=',$lasttime);
                            $tmp_count=$service_house_new_meter->getMeterReadingRecordCount($wheremArr);
                            $tmp_count=$tmp_count>0 ? $tmp_count:0;
                            $no_meter_reading_count= $count_num-$tmp_count;
                            $no_meter_reading_count=$no_meter_reading_count>0 ?$no_meter_reading_count:0;
                            $all_count_num +=$no_meter_reading_count;
                        }
                    }
                }
                
            }catch (\Exception $e){
                $all_count_num=0;
            }
            
            $no_meter_reading = $all_count_num;
            $is_meter_show = true;
        }

        $work_infos = $service_house_worker->getOneWorker(['wid'=>$wid],'type');
        if(in_array($work_infos['type'],[-1,0,1,3,4,5,6,7,8,9])){
            $is_repair_show = true;
        }else{
            $is_repair_show = false;
        }

        $is_wisdom_qrcode=false;

        $wisdow_qrcode = new WisdowQrcodeService();
        $where = [];
        $where[] = $public_wheres;
        $where[] = ['p.uid','=',$wid];
        $where[] = ['p.status','=',1];
        $field = 'c.id,c.cate_name';
        $wisdom_qrcode_cate_list = $wisdow_qrcode->getWisdomQrcodeCateLists($where,$field)->toArray();;
        $is_no_complete = 0;
        if($wisdom_qrcode_cate_list) {
            foreach ($wisdom_qrcode_cate_list as $key => $val) {
                /*
                $where_con['cate_id'] = $val['id'];
                $where_con['status'] = 1;
                $where_con['uid'] = $wid;
                $datas = $wisdow_qrcode->getWisdomQrcodePerson($where_con);
                if (empty($datas)){
                    unset($wisdom_qrcode_cate_list[$key]);
                }
                $is_complete = $wisdow_qrcode->isComplete($datas);
                if ($is_complete) {
                    $wisdom_qrcode_cate_list[$key]['is_complete'] = 1;
                } else {
                    $wisdom_qrcode_cate_list[$key]['is_complete'] = 0;
                    $is_no_complete++;//待巡查任务
                }
                */
                $is_no_complete++;
            }
            $is_show_qrcode = true;
        }else{
            $is_show_qrcode = false;
        }

        //过滤套餐 2020/11/9 start
        $servicePackageOrder = new PackageOrderService();
        $dataPackage = $servicePackageOrder->getPropertyOrderPackage($property_id,'');
        if($dataPackage) {
            $dataPackage = $dataPackage->toArray();
            $package_content = $dataPackage['content'];
        }else{
            $package_content = [];
        }
        //过滤套餐 2020/11/9 end
        //todo 开启新版工单 隐藏老版工单入口
        $works_order_switch=0;
        $villag_info=(new HouseVillageInfo())->getOne([['village_id','=',$village_id]],'works_order_switch');
        if ($villag_info && !$villag_info->isEmpty()){
            $works_order_switch=(int)$villag_info['works_order_switch'];
        }
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $http = $http_type.$_SERVER['SERVER_NAME'].'/v20/public/static/community/images/statistics_column/';
        //套餐过滤
        $work_order_intersect = array_intersect([16,18,19,40],$package_content);
        if($work_order_intersect && count($work_order_intersect)>0 && $is_repair_show) {
            $new_work_order_intersect = array_intersect([40],$package_content);
            if($works_order_switch == 0 && empty($new_work_order_intersect)){
                $data['menu_list'][] = ['title' => '工单处理', 'ico' => $http . 'work_order.png', 'url' => cfg('site_url') . '/packapp/community/pages/Community/Worksheets/onlineReport?current=0','type'=>'work_order'];
                $data['statistics'][] = ['title' => '未处理工单', 'num' => $pending_count, 'url' => cfg('site_url') . '/packapp/community/pages/Community/Worksheets/onlineReport?type=1&current=1','type'=>'pending_count'];//未处理工单 pending_count
                $data['statistics'][] = ['title' => '未接工单', 'num' => $not_receive_count, 'url' => cfg('site_url') . '/packapp/community/pages/Community/Worksheets/onlineReport?type=0&current=0','type'=>'no_receive_count'];//未接工单 no_receive_count
            }else{
                $new_pending_count=0;
                $new_processing_count=0;
                if($worker_id>0){
                    $house_new_repair_works_order_service = new HouseNewRepairService();
                    $new_pending_count=$house_new_repair_works_order_service->workGetWorksOrderLists($worker_id,$login_role,'todo','','','',true);
                    $new_processing_count=$house_new_repair_works_order_service->workGetWorksOrderLists($worker_id,$login_role,'processing','','','',true);
                    $is_new_repair_work=1;
                }
                $data['menu_list'][] = ['title' => '工单处理中心', 'ico' => $http . 'work_order.png', 'url' => cfg('site_url') . '/packapp/community/pages/CommunityPages/workOrder/eventList','type'=>'repair_order'];
                $data['statistics'][] = ['title' => '未处理工单', 'num' => $new_pending_count, 'url' => cfg('site_url') . '/packapp/community/pages/CommunityPages/workOrder/eventList','type'=>'pending_count'];//未处理工单 pending_count
               $data['statistics'][] = ['title' => '处理中工单', 'num' => $new_processing_count, 'url' => cfg('site_url') . '/packapp/community/pages/CommunityPages/workOrder/eventList?tab_type=no_receive_count','type'=>'no_receive_count'];//未接工单 no_receive_count
            }
        }
        //套餐过滤
        if(in_array(20,$package_content) && $is_show_qrcode) {
            $is_wisdom_qrcode=true;
            $data['menu_list'][] = ['title' => '智慧二维码', 'ico' => $http . 'qrcode.png', 'url' => cfg('site_url') . '/packapp/community/pages/Community/smartQRcode/taskList','type'=>'tasks'];
        }
        //套餐过滤
        if(in_array(14,$package_content) && $is_meter_show) {
            $data['menu_list'][] = ['title' => '移动抄表', 'ico' => $http . 'meter_reading.png', 'url' => cfg('site_url') . '/packapp/community/pages/Community/mobileMeterReading/selectRoomy','type'=>'meter_reading'];
            $data['statistics'][] = ['title' => '未抄表户数', 'num' => $no_meter_reading, 'url' => cfg('site_url') . '/packapp/community/pages/Community/mobileMeterReading/selectRoomy','type'=>'roomy'];//没有抄表的房屋数量 no_meter_reading
        }
        if($wid){
            $work_info = $service_house_worker->getOneWorker(['wid'=>$wid],'village_id,type');
            $db_merchant_store_kefu = new MerchantStoreKefu();
            $kefu_info = $db_merchant_store_kefu->getOne(['k.belong'=>'village','k.bind_uid'=>$wid,'k.store_id'=>$work_info['village_id']],'k.username');
            if($kefu_info){
                $kfUrl = build_store_im_conversation_url($kefu_info['username'], 'village2villageBind');
                $data['menu_list'][] = ['title'=>'在线客服','ico'=>$http.'chat.png','url'=>$kfUrl,'type'=>'kefu'];
            }
        }
        $unitRentalArr=array('id'=>'112041','module'=>'UnitRental','status'=>1,'show'=>1);
        $houseMenuNew=new HouseMenuNew();
        $unitRentalObj=$houseMenuNew->getOne($unitRentalArr);
        if($unitRentalObj && !$unitRentalObj->isEmpty()) {
            //todo 公租房模块
            $data['menu_list'][] = ['title' => '入住审核', 'ico' => $http . 'house_public_ruzhu_examine.png', 'url' => cfg('site_url') . '/packapp/community/pages/Community/checkoutApplication/leaseCancellation?page_type=checkin', 'type' => 'house_public_ruzhu_examine'];

            $data['menu_list'][] = ['title' => '退租审核', 'ico' => $http . 'house_public_tuizu_examine.png', 'url' => cfg('site_url') . '/packapp/community/pages/Community/checkoutApplication/leaseCancellation?page_type=rentcancell', 'type' => 'house_public_tuizu_examine'];

            $data['menu_list'][] = ['title' => '验房管理', 'ico' => $http . 'house_public_inspection.png', 'url' => cfg('site_url') . '/packapp/community/pages/Community/checkoutApplication/inspectionManagement', 'type' => 'house_public_inspection'];
        }

        if($app_type == 'ios' || $app_type == 'android'){
            $data['menu_list'][] = ['title' => '设置', 'ico' => $http . 'setting.png', 'url' => cfg('site_url') . '/packapp/plat_dev/pages/Community/index/setup','type'=>'setting'];
        }elseif($app_type == 'packapp'){
            $data['menu_list'][] = ['title' => '设置', 'ico' => $http . 'setting.png', 'url' => cfg('site_url') . '/packapp/community/pages/Community/index/setup','type'=>'setting'];
        }

        $judgeIsPropertyOrVillage = (new ManageAppLoginService())->judgeIsPropertyOrVillage($login_role, $village_id, $property_id);
        if ($judgeIsPropertyOrVillage) {
            $data['menu_list'][] = ['title' => '视频监控', 'ico' => $http . 'video_camera.png', 'url' => cfg('site_url') . '/packapp/community/pages/Community/videoSurveillance/hawkEye', 'type' => 'camera'];
        }
        
        if(in_array(20,$package_content) && $is_show_qrcode) {
            $data['statistics'][] = ['title' => '待巡查任务', 'num' => $is_no_complete, 'url' => cfg('site_url') . '/packapp/community/pages/Community/smartQRcode/taskList','type'=>'task'];//待巡查任务 patrol_task
        }
        
        // 处理是否有内部推送消息
        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        if ($jz_open) {
            // 合同入口
            $data['menu_list'][] = ['title' => '合同', 'ico' => $http . 'contract.png', 'url' => cfg('site_url') . '/packapp/community/pages/Community/contractManage/pages/contractUser', 'type' => 'contract'];
            
            $messageCount = (new PushMessageHandleService())->getHouseWorkMessageNum($wid);
            if ($messageCount > 99) {
                $messageCount = '99+';
            }
            $data['menu_list'][] = ['title' => '消息', 'ico' => $http . 'message.png', 'num' => $messageCount, 'url' => cfg('site_url') . '/packapp/community/pages/Community/contractManage/index', 'type' => 'message'];
        }
        if($is_car_sentry>0 && $this->hasCloudIntercom()){
            $data['menu_list'][] = ['title' => '岗亭管理', 'ico' => $http . 'video_camera.png', 'url' => cfg('site_url') . '/packapp/community/pages/Community/contractManage/pages/watchbox', 'type' => 'sentry_watch'];
        }
        if (!cfg('ComingSoonHide')) {
            $data['menu_list'][] = ['title'=>'敬请期待','ico'=>$http.'other.png','url'=>'','type'=>'false'];
        }

        $data['prompt'] = $prompt;

        // 作废、退款审核 小区物业工作人员 增加（我的审批）入口
        //fdump_api(['$wid' => $wid], '$wid');
        if($login_role == HouseWorkConst::MANAGE_APP_VILLAGE_WORK && $wid){
            $db_checkauth_detail = new HouseVillageCheckauthDetail();
            $where_check = [];
            $where_check[] = ['wid','=',$wid];
            $where_check[] = ['status','=',0];
            $where_check[] = ['village_id','=',$work_info['village_id']];
            $my_check_num = $db_checkauth_detail->statisticsCheck($where_check);
            $data['statistics'][] = ['title' => '我的审批', 'num' => $my_check_num, 'url' => cfg('site_url') . '/packapp/community/pages/Community/reviewRefund/myApproval','type'=>'my_check'];
        }
        $data['qrcode_position']=(new SmartQrCodeService())->getWisdomQrcodePersonTask($village_id,$wid,$is_wisdom_qrcode);

        return $data;
    }
    /**
     * Notes: 工单列表
     * @param $where
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param $field
     * @param $property_id
     * @return array
     * @author: weili
     * @datetime: 2020/10/22 11:21
     */
    public function getWorkOrderList($where,$page,$limit,$order,$field,$property_id=0)
    {
        $dbHouseVillageRepairList = new HouseVillageRepairList();
        $serviceHouseVillage = new HouseVillageService();
        if ($property_id) {
            $dbHouseVillage = new HouseVillage();
            $village_where[] = ['property_id', '=', $property_id];
            $village_where[] = ['status', '=', 1];
            $village_id_arr = $dbHouseVillage->getColumn($village_where, 'village_id');
            if($village_id_arr && count($village_id_arr)>0) {
                $public_where = ['r.village_id', 'in', $village_id_arr];
            }else{
                $public_where = ['r.village_id','<',0];
            }
            $where[] = $public_where;
        }
        $data = $dbHouseVillageRepairList->getListLimit($where,$field,$page,$limit,$order);
        $dataArr = [];
        foreach ($data as $key=>$val)
        {
            if(empty($val['user_name']))
            {
                $val['user_name'] = $val['name'];
                unset($val['name']);
            }else{
                unset($val['name']);
            }
            if(empty($val['phone']))
            {
                $val['phone'] = $val['uphone'];
                unset($val['uphone']);
            }else{
                unset($val['uphone']);
            }
            $val['time'] = date('Y-m-d H:i:s',$val['time']);
            if($val['reply_time'])
            {
                $val['reply_time'] = date('Y-m-d H:i:s',$val['reply_time']);
            }
            //根据楼栋id 单元id 楼层id 门牌号id 社区id 获取地址
//            $val['address'] = $serviceHouseVillage->getSingleFloorRoom($val['single_id'],$val['floor_id'],$val['layer_id'],$val['vacancy_id'],$val['village_id']);
            $single_id = $val['single_id'] ? $val['single_id'] : $val['u_single_id'];
            $floor_id = $val['floor_id'] ? $val['floor_id'] : $val['u_floor_id'];
            $layer_id = $val['layer_id'] ? $val['layer_id'] : $val['u_layer_id'];
            $vacancy_id = $val['vacancy_id'] ? $val['vacancy_id'] : $val['u_vacancy_id'];
            $val['address'] = $serviceHouseVillage->getSingleFloorRoom($single_id, $floor_id, $layer_id, $vacancy_id,$val['village_id']);
            //工单 状态
            $statusData = $this->getStatus($val['status']);
            $dataArr[$key]['pigcms_id'] = $val['pigcms_id'];
            $dataArr[$key]['time'] = $val['time'];
            $dataArr[$key]['status_msg'] = $statusData['status_msg'];
            $dataArr[$key]['status'] = $val['status'];
            $dataArr[$key]['status_color'] = $statusData['status_color'];
            $dataArr[$key]['phone'] = $val['phone'];
            $dataArr[$key]['list'] = [
                ['title'=>'报修人员','type'=>1,'content'=>$val['user_name']],
                ['title'=>'报修内容','type'=>1,'content'=>$val['content']],
                ['title'=>'报修地址','type'=>1,'content'=>$val['address']],
            ];
            if($val['status']>=3) {
                $dataArr[$key]['list'][] = ['title' => '处理意见', 'type' => 1, 'content' => $val['reply_content']];
                $dataArr[$key]['list'][] = ['title' => '处理时间', 'type' => 1, 'content' => $val['reply_time']];
            }
            if($val['status']>=4){
                $dataArr[$key]['list'][] = ['title' => '评价内容','type'=>1, 'content' => $val['comment']];
                $dataArr[$key]['list'][] = ['title' => '评价时间','type'=>1, 'content' => $val['comment_time']];
            }
        }
        return $dataArr;
    }

    /***
     * Notes: 状态处理
     * @param $status
     * @return mixed
     * @author: weili
     * @datetime: 2020/10/22 11:20
     */
    public function getStatus($status)
    {
        //在线报修 0未受理1已指派2已受理3已处理4业主已评价
        //水电煤上报/投诉建议 0未受理1物业已受理2客服专员已受理3客服专员已处理4业主已评价
        switch ($status){
            case '0':
                $val['status_msg'] = '未接任务';
                $val['status_color'] = '#a685fe';
                return $val;
                break;
            case '1':
                $val['status_msg'] = '未接任务';
                $val['status_color'] = '#a685fe';
                return $val;
                break;
            case '2':
                $val['status_msg'] = '未处理';//已接单
                $val['status_color'] = 'red';//#7ca6f7
                return $val;
                break;
            case '3':
                $val['status_msg'] = '已处理';
                $val['status_color'] = '#1ed19f';
                return $val;
                break;
            case '4':
                $val['status_msg'] = '业主已评价';
                $val['status_color'] = '#ffa801';
                return $val;
                break;
        }
    }
    /**
     * Notes: 工单详情
     * @param $where
     * @param $field
     * @return array
     * @author: weili
     * @datetime: 2020/10/22 11:20
     */
    public function getRepairFind($where,$field,$app_type)
    {
        $dbHouseVillageRepairList = new HouseVillageRepairList();
        $dbHouseVillageUserBind   = new HouseVillageUserBind();
        $dbHouseWorker            = new HouseWorker();
        $dbHouseVillageRepairFollow = new HouseVillageRepairFollow();
        $serviceHouseVillage = new HouseVillageService();
        $dbHouseVillageRepairCate = new HouseVillageRepairCate();
        $info = $dbHouseVillageRepairList->getFind($where,$field);
        
        $dataArr = [];
        if($info) {
            $map[] = ['village_id', '=', $info['village_id']];
            $map[] = ['uid', '=', $info['uid']];
            $map[] = ['pigcms_id', '=', $info['bind_id']];
            $userBindField = 'usernum,name,phone,address';
            $userBindInfo = $dbHouseVillageUserBind->getOne($map, $userBindField);
            if ($userBindInfo) {
                $info['usernum'] = $userBindInfo['usernum'];
                $info['address'] = $userBindInfo['address'];
                if (!$info['user_name']) {
                    $info['user_name'] = $userBindInfo['name'];
                }
                if (!$info['phone']) {
                    $info['phone'] = $userBindInfo['phone'];
                }
            } else {
                $info['usernum'] = '';
                $info['address'] = '';
            }
            //报修类别 (1公共维修 2个人维修)
            if($info['type']==1 && $info['repair_type']){
                $info['repair_types'] = $info['repair_type'];
                $arr = array(
                    '1'=>'公共维修',
                    '2'=>'个人维修'
                );
                $info['repair_type'] = $arr[$info['repair_type']];
            }
            //获取分类
            if($info['cate_id']){//二级分类的情况
                $whereCate['a.id'] = $info['cate_id'];
                $field = 'a.cate_name,b.cate_name as cate_f_name';
                $dataCate = $dbHouseVillageRepairCate->getRelevance($whereCate,$field);
                if($dataCate){
                    if($dataCate['cate_f_name'] && $dataCate['cate_name']) {
                        $info['cate_name'] = $dataCate['cate_f_name'] . '-' . $dataCate['cate_name'];
                    }elseif (!$dataCate['cate_f_name'] && $dataCate['cate_name']){
                        $info['cate_name'] =$dataCate['cate_name'];
                    }elseif ($dataCate['cate_f_name'] && !$dataCate['cate_name']){
                        $info['cate_name'] =$dataCate['cate_f_name'];
                    }else{
                        $info['cate_name'] = '';
                    }
                }else{
                    $info['cate_name'] = '';
                }
            }elseif ($info['cate_fid']){//只有一级分类的情况
                $whereCateF['id'] = $info['cate_fid'];
                $dataCate = $dbHouseVillageRepairCate->getFind($whereCateF,'cate_name');
                $info['cate_name'] = $dataCate['cate_name'];
            }
            //添加时间
            if ($info['time']) {
                $info['time'] = date('Y-m-d H:i:s', $info['time']);
            }
            //处理时间
            if($info['reply_time']){
                $info['reply_time'] = date('Y-m-d H:i:s',$info['reply_time']);
            }
            //上报图例
            $picArray = [];
            if ($info['pic']) {
                $pic = explode('|', $info['pic']);
                foreach ($pic as $val) {
                    if (substr($val, 1, 6) == 'upload') {
                        $picArray[] = replace_file_domain($val);
                    } elseif (substr($val, 3, 7) == '000/000') {
                        $picArray[] = file_domain() . "/upload/activity/" . $val;
                    } else {
                        $picArray[] = file_domain() . "/upload/house/" . $val;
                    }
                }
            }
            $info['pic'] = $picArray;
            //未处理状态下不需要处理相应参数
            if ($info['status'] <> 0) {
                //处理图例
                if ($info['reply_pic']) {
                    $replyPic = explode('|', $info['reply_pic']);
                    $replyPicArr = [];
                    foreach ($replyPic as $val) {
                        $replyPicArr[] = file_domain() . "/upload/worker/" . $val;
                    }
                    $info['reply_pic'] = $replyPicArr;
                }
                //上门时间
                if ($info['repair_time']) {
                    $info['repair_time'] = date('Y-m-d H:i:s', $info['repair_time']);
                }
                $info['follow']= [];
                //查询跟进内容
                if ($info['status'] >= 2) {
                    $followWhere[] = ['repair_id', '=', $info['pigcms_id']];
                    $followField = 'time,content';
                    $followInfo = $dbHouseVillageRepairFollow->getAll($followWhere, $followField,'follow_id asc');
                    $content = [];
                    if ($followInfo) {
                        foreach ($followInfo as &$value) {
                            $value['time'] = date('Y-m-d H:i:s', $value['time']);
//                            if($app_type =='packapp'){
//                                $content[] = ['contents'=>$value['time'].' '.$value['content']];
//                            }else{
                                $content[] = [
                                    'time'=>$value['time'],
                                    'content_text'=>$value['content'],
                                ];
//                            }
                        }
                    }
//                    $info['follow'] = $followInfo;
                    $info['follow'] = $content;
                }
                //评论图例
                if ($info['comment_pic']) {
                    $commentPic = explode('|', $info['comment_pic']);
                    $commentPicArr = [];
                    foreach ($commentPic as $val) {
                        $commentPicArr[] = file_domain() . "/upload/house/" . $val;
                    }
                    $v['comment_pic'] = $commentPicArr;
                }
                //评论时间
                if ($info['comment_time']) {
                    $info['comment_time'] = date('Y-m-d H:i:s', $info['comment_time']);
                }
                //处理人员
                $w_map[] = ['village_id', '=', $info['village_id']];
                $w_map[] = ['wid', '=', $info['wid']];
                $field = 'name,phone';
                $workerInfo = $dbHouseWorker->getOne($w_map, $field);
                if ($workerInfo) {
                    $info['worker_name'] = $workerInfo['name'];
                    $info['worker_phone'] = $workerInfo['phone'];
                } else {
                    $info['worker_name'] = '';
                    $info['worker_phone'] = '';
                }
            }
            //根据楼栋id 单元id 楼层id 门牌号id 社区id 获取地址
//            $info['address'] = $serviceHouseVillage->getSingleFloorRoom($info['single_id'],$info['floor_id'],$info['layer_id'],$info['vacancy_id'],$info['village_id']);
            $single_id = $info['single_id'] ? $info['single_id'] : $info['u_single_id'];
            $floor_id = $info['floor_id'] ? $info['floor_id'] : $info['u_floor_id'];
            $layer_id = $info['layer_id'] ? $info['layer_id'] : $info['u_layer_id'];
            $vacancy_id = $info['vacancy_id'] ? $info['vacancy_id'] : $info['u_vacancy_id'];
            $info['address'] = $serviceHouseVillage->getSingleFloorRoom($single_id, $floor_id, $layer_id, $vacancy_id,$info['village_id']);
            $dataArr['pigcms_id'] = $info['pigcms_id'];

            $dataArr['list']=[
                ['title'=>'业主编号', 'content'=>$info['usernum'],'type'=>1],
                ['title'=>'上报地址', 'content'=>$info['address'],'type'=>1],
                ['title'=>'业主姓名', 'content'=>$info['user_name'],'type'=>1],
                ['title'=>'上报时间', 'content'=>$info['time'],'type'=>1],
                ['title'=>'联系方式', 'content'=>$info['phone'],'type'=>1,'is_phone'=>1],
            ];
            if(in_array($info['type'],[1,3])) {
                if($info['type'] == 1) {
                    $dataArr['list'][] = ['title' => '报修类别', 'content' => $info['repair_type'], 'type' => 1];
                    $dataArr['list'][] = ['title' => '报修分类', 'content' => $info['cate_name'], 'type' => 1];
                }else{
                    $dataArr['list'][] = ['title' => '投诉建议分类', 'content' => $info['cate_name'], 'type' => 1];
                }
            }
            $dataArr['list'][]=['title'=>'上报内容', 'content'=>$info['content'],'type'=>1];
            if($info['repair_types'] == 2 && $info['type'] == 1)//只有在线报修的个人报修才有上门时间
            {
                $dataArr['list'][]=['title'=>'上门时间', 'content'=>$info['repair_time'],'type'=>1];
            }
            $dataArr['list'][]=['title'=>'上报图片', 'content'=>!empty($info['pic'])?$info['pic']:array(),'type'=>2];
            //已接单
            if($info['status'] >= 2){
                $dataArr['list'][] = ['title'=>'接单留言','content'=>$info['msg'],'type'=>1];
            }
            if($info['status']>=2) {
                if ($info['status'] == 2) {
//                    $dataArr['list'][] = ['title' => '跟进内容', 'content' => $info['follow'], 'type' => 4];
                    $dataArr['schedule'][] = ['title' => '跟进内容', 'content' => $info['follow'], 'type' => 1];
                } else {
//                    $dataArr['list'][] = ['title' => '跟进内容', 'content' => $info['follow'], 'type' => 4];
                    $dataArr['schedule'][] = ['title' => '接单跟进', 'content' => $info['follow'], 'type' => 1];
                }
            }
//            if($info['status'] >= 3){
//                $dataArr['list'][] = ['title' => '跟进内容', 'content' => $info['follow'], 'type' => 4];
//            }
            //已处理
            if($info['status'] >= 3) {
                $dataArr['list'][] = ['title'=>'处理时间','content'=>$info['reply_time'],'type'=>1];
                $dataArr['list'][] = ['title'=>'处理意见','content'=>$info['reply_content'],'type'=>1];
                $dataArr['list'][] = ['title'=>'上报图例','content'=>$info['reply_pic'],'type'=>2];
            }
            //已评价
            if($info['status'] >= 4){
                $dataArr['list'][] = ['title'=>'评论时间','content'=>$info['comment_time'],'type'=>1];
                $dataArr['list'][] = ['title'=>'评分','content'=>$info['score'],'type'=>1];
                $dataArr['list'][] = ['title'=>'评论内容','content'=>$info['comment'],'type'=>1];
                $dataArr['list'][] = ['title'=>'评论图例','content'=>$info['comment_pic'],'type'=>2];
            }
        }
        return $dataArr;
    }
    /**
     * Notes:接单/拒绝 以及处理意见 工单
     * @param $post
     * @param $field
     * @param $type
     * @return HouseVillageRepairList|int
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/10/22 11:20
     */
    public function operationRepair($post,$field,$type)
    {
        $dbHouseVillageRepairList = new HouseVillageRepairList();
        $dbHouseWorker            = new HouseWorker();
        $where[] = ['r.pigcms_id','=',$post['pigcms_id']];
        $info = $dbHouseVillageRepairList->getFind($where,$field);
        if($info['wid']>0 && ($info['wid'] != $post['wid'])){
            throw new \think\Exception("工作人员不一致！");
        }
        if($type == 1) {//处理意见
            $data['reply_time'] = time();
            $data['reply_content'] = $post['reply_content'];
            $data['reply_pic'] = $post['reply_pic'];
            $data['status'] = 3;
            $post['status'] = 3;
        }else{//接单/拒接
            if($post['status'] ==2 && $post['status']<=$info['status']){
                throw new \think\Exception("已接单，不可重复接单！");
            }
            $data['msg'] = $post['message'];
            $status = $post['status'] == 2?$post['status']:0;
            $data['status'] = $status;
            if($post['status'] == 1){
                $data['wid'] = 0;
            }elseif($data['status'] ==2 && empty($info['wid'])){
                $data['wid']=$post['wid'];
            }
        }
        $res = 0;
        if($data) {
            $maps[] = ['pigcms_id','=',$post['pigcms_id']];
            $res = $dbHouseVillageRepairList->updateInfo($maps, $data);
            if($res && (in_array($post['status'],[1,2]) || ($type ==1 && $data['status'] == 3))){
                //工单操作记录
                $serviceHouseVillageRepairLog = new HouseVillageRepairLogService();
                $worker_where[] = ['wid','=',$info['wid']];
                $worker_info = $dbHouseWorker->getOne($worker_where,'name,phone');
                if($type == 1){
                    $log_data['status'] = $data['status'];
                }else {
                    $log_data['status'] = $post['status'] == 2 ? 2 : 0;
                }
                $log_data['repair_id'] = $post['pigcms_id'];
                $log_data['name'] = $worker_info['name'];
                $log_data['phone'] = $worker_info['phone'];
                $serviceHouseVillageRepairLog->addLog($log_data);
            }
        }
        return $res;
    }
    /**
     * Notes:更进工单内容
     * @param $post
     * @return int|string
     * @author: weili
     * @datetime: 2020/10/22 11:20
     */
    public function disposeRepair($post)
    {
        $dbHouseVillageRepairFollow = new HouseVillageRepairFollow();
        $data = [
            'repair_id'=>$post['pigcms_id'],
            'worker_id'=>$post['wid'],
            'content'=>$post['content'],
            'time'=>time(),
            'village_id'=>$post['village_id'],
        ];
        $res = $dbHouseVillageRepairFollow->addData($data);
        return $res;
    }
    public function uploads($file,$village_id)
    {
        if(!$village_id){
            $village_id = mt_rand(10, 99);
        }
        $img_mer_id = sprintf("%09d", $village_id);
        $rand_num = mt_rand(10, 99) . '/' . substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);

        $upload_img_dir = 'worker/' . $rand_num;
        // 上传到本地服务器
        $savename = \think\facade\Filesystem::disk('public_upload')->putFile($upload_img_dir, $file);
        if (strpos($savename, "\\") !== false) {
            $savename = str_replace('\\', '/', $savename);
        }
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $http = $http_type . $_SERVER['SERVER_NAME'];
        //判断是否需要上传至云存储
//        $file_handle = new file_handle();
//        $file_handle->upload($savename);
//            $http_img_url = replace_file_domain('/upload/' . $savename);
        $imgurl = '/upload/'.$savename;
        $http_img_url = cfg('site_url').'/upload/' . $savename;

        $data['url'] = thumb_img($http_img_url,200, 200, 'fill');
        $data['imageUrl_path'] = ltrim($savename, 'worker/');
        $data['imageUrl'] = $http_img_url;
        $params = ['savepath'=>'/upload/' . $imgurl];
        $file_handle = new FileHandle();
        if($file_handle->check_open_oss()) {
            invoke_cms_model('Image/oss_upload_image', $params);
        }
        return $data;
    }
}