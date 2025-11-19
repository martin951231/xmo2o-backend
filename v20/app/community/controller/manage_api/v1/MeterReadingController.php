<?php


namespace app\community\controller\manage_api\v1;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\controller\manage_api\BaseController;
use app\community\model\db\HouseNewChargeNumber;
use app\community\model\db\HouseNewChargeProject;
use app\community\model\db\HouseNewChargeRule;
use app\community\model\db\HouseVillageRoomCustomizedMeters;
use app\community\model\db\HouseVillageUserPaylist;
use app\community\model\service\HouseNewCashierService;
use app\community\model\service\HouseNewChargeProjectService;
use app\community\model\service\HouseNewChargeRuleService;
use app\community\model\service\HouseNewMeterService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\HouseVillageUserPaylistService;
use app\community\model\service\HouseVillageUserVacancyService;
use app\community\model\service\HouseWorkerService;
use app\community\model\service\MeterReadPersonService;
use app\community\model\service\StorageService;
use app\community\model\service\UserService;
use app\community\model\service\HouseVillageUserEditPriceRecordService;
use app\community\model\service\HouseNewPorpertyService;
use app\community\model\service\HousePropertyDigitService;
use app\community\model\service\HeatingService;
use think\facade\Request;

class MeterReadingController extends BaseController
{

    /**
     * 移动抄表(房间列表)
     * @author lijie
     * @date_time 2020/11/02
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function villageRoomList()
    {
        $info = $this->getLoginInfo();
        if (isset($info['status']) && $info['status']!=1000) {
            return api_output($info['status'],[],$info['msg']);
        }
        $wid = $this->_uid;
        if (empty($wid)) {
            return api_output_error(1001,'登录失败');
        }
        $phone = $info['user']['login_phone'];
        $floor_id = $this->request->post('floor_id',0);
        if(!$floor_id){
            return api_output_error(1001,'缺少楼层ID');
        }
        //增加工作人员判断
        $village_id = $this->request->post('village_id',0);
        if (empty($village_id)) {
            return api_output_error(1001,'缺少village_id');
        }
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_worker = new HouseWorkerService();
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $service_house_village = new HouseVillageService();
        $village_user_bind['village_id'] = $village_id;
        $village_user_bind['type'] = 4;
        $village_user_bind['phone'] = $phone;
        $bind_info = $service_house_village_user_bind->getBindInfo($village_user_bind);
        if(empty($bind_info)){
            return api_output_error(1001,'你不是本小区的工作的人员,请联系管理员添加!');
        }
        $house_worker = $service_house_worker->getOneWorker(array('village_id'=>$bind_info['village_id'],'phone'=>$bind_info['phone'],'status'=>1,'is_del'=>0));
        if(empty($house_worker)){
            return api_output_error(1001,'你不是本小区的工作的人员,请联系管理员添加!');
        }
        $arr = [];
        //查询单元信息 小区信息
        $find_village_floor = $service_house_village->getHouseVillageFloorInfo(['floor_id'=>$floor_id],'village_id,floor_name,floor_layer');
        $find_village = $service_house_village->getHouseVillageInfo(['village_id'=>$find_village_floor['village_id']] , 'village_name,village_address');
        $find_info = array(
            'village_name'    =>  $find_village['village_name'],
            'village_address' =>  $find_village['village_address'],
            'floor_name'      =>  $find_village_floor['floor_name'],
            'floor_layer'     =>  $find_village_floor['floor_layer'],
            'village_id'     =>  $find_village_floor['village_id']
        );
        $arr['find_info'] = $find_info;

        $vacancy_where[] = ['floor_id','=',$floor_id];
        $vacancy_where[] = ['uid','<>',0];
        $vacancy_where[] = ['status','<>',0];

        $data = $service_house_village_user_vacancy->getUserVacancy($vacancy_where , true , 'pigcms_id asc',$house_worker['wid'],$find_village_floor['village_id']);
        return api_output(0,$data);
    }

    /**
     * 移动抄表==>提交数据
     * @author lijie
     * @date_time 2020/11/02
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addMeterRead()
    {
        $info = $this->getLoginInfo();
        if (isset($info['status']) && $info['status']!=1000) {
            return api_output($info['status'],[],$info['msg']);
        }
        $wid = $this->_uid;
        if (empty($wid)) {
            return api_output_error(1001,'登录失败');
        }
        $phone = $info['user']['login_phone'];
        //增加工作人员判断
        $village_id = $this->request->post('village_id',0);
        if (empty($village_id)) {
            return api_output_error(1001,'缺少village_id');
        }
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_worker = new HouseWorkerService();
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $service_meter_read_person = new MeterReadPersonService();
        $service_house_village_user_paylist = new HouseVillageUserPaylistService();
        $service_house_new_meter = new HouseNewMeterService();
        $service_house_new_charge_rule = new HouseNewChargeRuleService();
        $service_house_new_charge_project = new HouseNewChargeProjectService();
        $village_user_bind['village_id'] = $village_id;
        $village_user_bind['type'] = 4;
        $village_user_bind['phone'] = $phone;
        $bind_info = $service_house_village_user_bind->getBindInfo($village_user_bind);
        $service_house_village = new HouseVillageService();
        $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'property_id');
        $service_house_new_property = new HouseNewPorpertyService();
        $is_new_version = $service_house_new_property->getTakeEffectTimeJudge($village_info['property_id']);
        if(empty($bind_info)){
            return api_output_error(1001,'你不是本小区的工作的人员,请联系管理员添加!');
        }
        $house_worker = $service_house_worker->getOneWorker(array('village_id'=>$bind_info['village_id'],'phone'=>$bind_info['phone'],'status'=>1,'is_del'=>0));
        if(empty($house_worker)){
            return api_output_error(1001,'你不是本小区的工作的人员,请联系管理员添加!');
        }
        $cate_id = $this->request->post('cate_id',0);//水电煤分类id
        if(empty($cate_id)){
            return api_output_error(1001,'缺少水电煤分类ID');
        }

        $meter_id = $this->request->post('meter_id',0,'intval');//水电表id
        $electric_service_rule_id = $this->request->post('electric_service_rule_id',0);//服务费项目标准id

        $room_id = $this->request->post('room_id',0);//房间ID
        if(empty($room_id)){
            return api_output_error(1001,'缺少房间ID');
        }
        $user_bind_info = $service_house_village_user_bind->getBindInfo([['vacancy_id','=',$room_id],['status','=',1],['type','in','0,3'],['village_id','=',$village_id]]);
        $find_village_room = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id'=>$room_id] , "pigcms_id,uid,name,phone,floor_id,village_id,layer,room,housesize,single_id,layer_id");
        $floor_id = $this->request->post('floor_id',0);//房间单元ID
        if(empty($floor_id)){
            return api_output_error(1001,'缺少房间单元ID');
        }

        $user_bind_id = $this->request->post('user_bind_id',0);//业主ID
        if(empty($user_bind_id)){
            return api_output_error(1001,'缺少业主ID');
        }

        $user_bind_phone = $this->request->post('user_bind_phone','');//业主手机号
        if(empty($user_bind_phone)){
            return api_output_error(1001,'缺少业主手机号');
        }

        $start_num = $this->request->post('start_num',0);//起度
        $end_num = $this->request->post('end_num',0);//止度
        if(empty($end_num)){
            return api_output_error(1001,'缺少止度');
        }
        //$is_exceed = $this->request->post('is_exceed',0);//是否超过最大刻度，1：超过，0：不超过。超过最大刻度，需要增加一轮刻度
        $is_exceed=0;
        if($is_new_version){
            $where['cate_id'] = 0;
            $where['project_id'] = $cate_id;
        }else{
            $where['cate_id'] = $cate_id;
        }
        $jz_wsxdsj_net_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        $where['room_id'] = $room_id;
        $where['floor_id'] = $floor_id;
        $where['village_id'] = $village_id;
        if(!$is_new_version){
            $record = $service_meter_read_person->getMeterReadRecord($where,true,'id DESC');
        }else{
            $whereMeter = ['project_id'=>$cate_id,'layer_num'=>$room_id,'village_id'=>$village_id];
            if($jz_wsxdsj_net_customized_open){
                $whereMeter['meter_id'] = $meter_id;
            }
            $record = $service_house_new_meter->getMeterRecordInfo($whereMeter,'last_ammeter,water_type');
        }
        // 获取最小 起度
        if($record){
            $start_num_min = isset($record['end_num'])?$record['end_num']:$record['last_ammeter'];
            $jz_wsxdsj_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
            if($start_num_min > 0 && $jz_wsxdsj_customized_open){
                //查询水电表的最大刻度，如果大于0需要处理一下
                $meterInfo = (new HouseVillageRoomCustomizedMeters())->getOneData(['id'=>$meter_id],'meter_max_v');
                $max = (isset($meterInfo['meter_max_v']) && $meterInfo['meter_max_v']>0) ? $meterInfo['meter_max_v'] : 0;
                $start_num_min = $max ? fmod(floatval($start_num_min), $max) : $start_num_min;
            }
        }else{
            $start_num_min = 0.00;
        }
        if (!$start_num) {
            $start_num = $start_num_min;
        }
        if (floatval($start_num_min)>floatval($start_num)) {
            return api_output_error(1001,'当前起度不能小于上次抄表的止度'.$start_num_min);
        }
        if(!$is_new_version){
            $project = $service_meter_read_person->getMeterReadProjectList(array('cate_id'=>$cate_id,'village_id'=>$village_id),true);
            $read_person = $service_meter_read_person->getMeterReadPerson(array('uid'=>$house_worker['wid'],'village_id'=>$village_id,'status'=>1,'cate_id'=>$cate_id),array(),true,'id ASC');
        }else{
            $project = $service_house_new_charge_project->getProjectInfo(array('id'=>$cate_id,'village_id'=>$village_id,'status'=>1),true);
            $read_person = $service_house_new_meter->getMeterDirectorInfo(['worker_id'=>$house_worker['wid'],'village_id'=>$village_id,'status'=>1,'project_id'=>$cate_id],true);
        }
        if(empty($read_person)){
            return api_output_error(1001,'移动抄表负责人不存在,请管理员进入移动抄表后台配置');
        }
        if(empty($project)){
            return api_output_error(1001,'抄表项目不存在!');
        }
        
        $insert_last_ammeter = $end_num;//需要写入的止度
        $meter_number='';
        $meter_type=0;
        $meter_extra_data='';
        if($jz_wsxdsj_net_customized_open && $meter_id){
            //查询最大刻度
            $meterInfoObj = (new HouseVillageRoomCustomizedMeters())->getOneData(['id'=>$meter_id],'id,village_id,room_id,meter_type,meter_number,meter_max_v');
            $meterInfo=array();
            if($meterInfoObj && !$meterInfoObj->isEmpty()){
                $meterInfo= $meterInfoObj->toArray();
            }
            $meter_extra_data=$meterInfo;
            $max = (isset($meterInfo['meter_max_v']) && $meterInfo['meter_max_v']>0) ? $meterInfo['meter_max_v'] : 0;
            if($meterInfo && $meterInfo['meter_type']=='water'){
                $meter_type=1;
            }else if($meterInfo && $meterInfo['meter_type']=='electric'){
                $meter_type=2;
            }
            if($meterInfo && $meterInfo['meter_number']){
                $meter_number=$meterInfo['meter_number'];
            }
            if($max <= 0){
                $is_exceed = 0;
            }
            if($max>0 && $end_num>$max){
                $is_exceed = 1; //止度 超过最大刻度
                $insert_last_ammeter=$end_num-$max;
            }else if($max>0 && $start_num>=$end_num){
                $is_exceed = 1; //止度小于等于起度 超过最大刻度
                $end_num = bcadd(strval($end_num),strval($max),3);//用来计算的止度
            }
        }
        if (floatval($start_num)>=floatval($end_num) && (!$jz_wsxdsj_net_customized_open || $is_exceed == 0)) {
            return api_output_error(1001,'止度需要大于起度');
        }
        
        if(!$is_new_version){
            foreach($project as $k=>$v){
                if($v['room_id']){
                    $room_ids_arr = $v['room_id'];
                    $room_ids_arr = explode(',',$room_ids_arr);
                    if(in_array($room_id,$room_ids_arr)){
                        $data['village_id'] = $village_id;
                        $data['floor_id'] = $floor_id;
                        $data['room_id'] = $room_id;
                        $data['cate_id'] = $cate_id;
                        $data['project_id'] = $v['id'];//项目id
                        $data['user_bind_id'] = $user_bind_id;//业主id
                        $data['user_name'] = $user_bind_info['name'];//业主名称
                        $data['user_bind_phone'] = $user_bind_phone;//业主手机号
                        $data['unit_price'] = $v['unit_price'];//单价
                        $data['rate'] = $v['rate'];//倍率
                        $data['pool_ammeter'] = $v['pool'];//公摊
                        $data['person_id'] = $house_worker['wid'];//负责人ID
                        $data['start_num'] = $start_num;//起度
                        $data['end_num'] = $insert_last_ammeter;//止度
                        $data['add_time'] = time();
                        $data['cost_num'] = (floatval($end_num)-floatval($start_num)+floatval($v['pool']))*floatval($v['rate']);//未缴数量//($data['last_ammeter'] - $data['start_ammeter'] +$data['pool_ammeter'])*$data['rate']
                        $data['cost_money'] = $data['cost_num'] * $v['unit_price'];//未缴费用//$data['unit_price'] * $data['cost_num']
                        $insertID = $service_meter_read_person->addMeterReadRecord($data);
                        if($insertID){
                            // 金额 = （止度-起度+公摊）*倍率 * 单价
                            $meter_read_cate = $service_meter_read_person->getMeterReadCate(['id'=>$cate_id],true);
                            $where_bind =[];
                            $where_bind[] = ['floor_id', '=', $floor_id];
                            $where_bind[] = ['vacancy_id', '=', $room_id];
                            $where_bind[] = ['type', 'in', '0,3'];
                            $res = $service_house_village_user_bind->getBindInfo($where_bind,true);
                            if($res){
                                $gas['type']= $electric['type']= $water['type'] = 1;
                                $gas['village_id']= $electric['village_id']= $water['village_id'] = $res['village_id'];
                                $gas['uid']= $electric['uid']= $water['uid'] = $bind_info['uid'];
                                $gas['usernum']= $electric['usernum']= $water['usernum'] = $res['usernum'];
                                $gas['name']= $electric['name']= $water['name'] = $res['name'];
                                $gas['phone']= $electric['phone']= $water['phone'] = $res['phone'];
                                $gas['bind_id']= $electric['bind_id']= $water['bind_id'] = $res['pigcms_id'];
                                $gas['add_time']= $electric['add_time']= $water['add_time'] = time();
                                $gas['rate']= $electric['rate']= $water['rate'] = $data['rate'];
                                $gas['pool_ammeter']= $electric['pool_ammeter']= $water['pool_ammeter'] = $data['pool_ammeter'];
                                $gas['unit_price']= $electric['unit_price']= $water['unit_price'] = $data['unit_price'];
                            }
                            $service_house_record = new HouseVillageUserEditPriceRecordService();
                            if($meter_read_cate && $meter_read_cate['cate_name'] == '电费' && $data['cost_money']){
                                $service_house_village_user_bind->updateUserFee(array('floor_id'=>$floor_id,'vacancy_id'=>$room_id),'electric_price',$data['cost_money']);
                                $electric['electric_price'] = $data['cost_money'];
                                $electric['use_electric'] = $data['cost_num'];
                                $service_house_village_user_paylist->addPaylist($electric);
                                $service_house_record->addRecord([
                                    'pigcms_id'=>$res['pigcms_id'],
                                    'money'=>$data['cost_money'],
                                    'detail'=>'增加'.$data['cost_money'].'元',
                                    'action'=>'抄表抄表-增加',
                                    'name'=>$info['user']['login_name'],
                                    'phone'=>$phone,
                                    'add_time'=>time(),
                                    'remark'=>'',
                                    'type'=>2
                                ]);
                            }elseif($meter_read_cate && ($meter_read_cate['cate_name'] == '煤气费' || $meter_read_cate['cate_name'] == '燃气费') && $data['cost_money']){
                                $service_house_village_user_bind->updateUserFee(array('floor_id'=>$floor_id,'vacancy_id'=>$room_id),'gas_price',$data['cost_money']);
                                $gas['gas_price'] = $data['cost_money'];
                                $gas['use_gas'] = $data['cost_num'];
                                $service_house_village_user_paylist->addPaylist($gas);
                                $service_house_record->addRecord([
                                    'pigcms_id'=>$res['pigcms_id'],
                                    'money'=>$data['cost_money'],
                                    'detail'=>'增加'.$data['cost_money'].'元',
                                    'action'=>'抄表抄表-增加',
                                    'name'=>$info['user']['login_name'],
                                    'phone'=>$phone,
                                    'add_time'=>time(),
                                    'remark'=>'',
                                    'type'=>3
                                ]);
                            }elseif($meter_read_cate && $meter_read_cate['cate_name'] == '水费' && $data['cost_money']){
                                $service_house_village_user_bind->updateUserFee(array('floor_id'=>$floor_id,'vacancy_id'=>$room_id),'water_price',$data['cost_money']);
                                $water['water_price'] = $data['cost_money'];
                                $water['use_water'] = $data['cost_num'];
                                $service_house_village_user_paylist->addPaylist($water);
                                $service_house_record->addRecord([
                                    'pigcms_id'=>$res['pigcms_id'],
                                    'money'=>$data['cost_money'],
                                    'detail'=>'增加'.$data['cost_money'].'元',
                                    'action'=>'抄表抄表-增加',
                                    'name'=>$info['user']['login_name'],
                                    'phone'=>$phone,
                                    'add_time'=>time(),
                                    'remark'=>'',
                                    'type'=>1
                                ]);
                            }
                        }else{
                            return api_output_error(1001,'上传失败');
                        }
                    }
                }
            }
        }else{
            $rule_id = $service_house_new_charge_rule->getValidChargeRule($cate_id);
            $rule_info = $service_house_new_meter->getMeterProjectInfo(['p.id'=>$cate_id,'r.id'=>$rule_id],'r.unit_price,r.rate,r.rule_digit,c.charge_number_name,p.name,c.charge_type,c.water_type');

            $rule_digit=-1;
            if(isset($rule_info['rule_digit']) && $rule_info['rule_digit']>-1 && $rule_info['rule_digit']<5){
                $rule_digit=$rule_info['rule_digit'];
            }
            $digit_type=1;
            $db_house_property_digit_service = new HousePropertyDigitService();
            $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$village_info['property_id']]);
            if(!empty($digit_info)){
                $digit_type=$digit_info['type']==2 ? 2:1;
                if($rule_digit<=-1 || $rule_digit>=5){
                    $rule_digit=intval($digit_info['meter_digit']);
                }
            }
            $rule_digit=$rule_digit>-1 ? $rule_digit:2;
            $whereArr=['project_id'=>$cate_id,'village_id'=>$village_id,'layer_num'=>$room_id];
            $meter_reading=$service_house_new_meter->getMeterRecordInfo($whereArr,'*','id DESC');
            $data=array();
            if (isset($rule_info['water_type']) && intval($rule_info['water_type']) > 0) {
                $data['water_type'] = intval($rule_info['water_type']);
            } elseif (isset($record['water_type']) && intval($record['water_type']) > 0) {
                $data['water_type'] = intval($record['water_type']);
            } elseif (isset($meter_reading['water_type']) && intval($meter_reading['water_type']) > 0) {
                $data['water_type'] = intval($meter_reading['water_type']);
            }
            $custom_2value=1;
            if($rule_info['charge_type']=='new_nuanqifei'){
                $data['meter_type']=3; //新版暖气
                $heatingService=new HeatingService();
                $param=array('village_id'=>$village_id,'type'=>1,'xtype'=>'flowCharg');
                $flowChargSet=$heatingService->getHeatingProtocol($param);
                if(!is_array($meter_extra_data)){
                    $meter_extra_data=array();
                }
                $meter_extra_data['flowChargSet']=$flowChargSet;
                if($flowChargSet['order_yh_per']>0){
                    $custom_2value=$flowChargSet['order_yh_per']/100;
                }
                if($flowChargSet['unit_price_base']>0){
                    $custom_2value=$custom_2value*$flowChargSet['unit_price_base'];
                }
                $data['meter_extra_data']= json_encode($meter_extra_data,JSON_UNESCAPED_UNICODE);
            }
          
            
            $data['village_id'] = $village_id;
            $data['single_id'] = $find_village_room['single_id'];
            $data['layer_id'] = $find_village_room['layer_id'];
            $data['floor_id'] = $floor_id;
            $data['layer_num'] = $room_id;
            $data['project_id'] = $cate_id;//项目id
            $data['user_bind_id'] = $user_bind_id;//业主id
            $data['user_name'] = $user_bind_info['name'];//业主名称
            $data['user_bind_phone'] = $user_bind_phone;//业主手机号
            $data['unit_price'] = $rule_info['unit_price'];//单价
            $data['charge_name'] = $rule_info['name'];//项目名称
            $data['rate'] = $rule_info['rate'];//倍率
            //$data['pool_ammeter'] = '无';//公摊
            $data['person_id'] = $house_worker['wid'];//负责人ID
            $data['start_ammeter'] = $start_num;//起度
            $data['last_ammeter'] = $insert_last_ammeter;//止度
            $data['add_time'] = time();
            $data['cost_num'] = (floatval($end_num)-floatval($start_num))*floatval($rule_info['rate']);//未缴数量//($data['last_ammeter'] - $data['start_ammeter'] +$data['pool_ammeter'])*$data['rate']
            $cost_money= $data['cost_num'] * $rule_info['unit_price']*$custom_2value;//未缴费用//$data['unit_price'] * $data['cost_num']
            $cost_money=formatNumber($cost_money, $rule_digit, $digit_type);
            $cost_money=formatNumber($cost_money, 2, 1);
            $data['cost_money'] =$cost_money;
            $data['work_name'] = $house_worker['name'];
            if($jz_wsxdsj_net_customized_open && $rule_info['charge_type'] == 'new_nuanqifei'){
                $pic_start = $this->request->post('pic_start',[]);
                $pic_last = $this->request->post('pic_last',[]);
                $pic_start = $pic_start ? implode(',',$pic_start) : '';
                $pic_last = $pic_last ? implode(',',$pic_last) : '';
                $data['pic_start'] = $pic_start;
                $data['pic_last'] = $pic_last;
                
            }

            $electric_rule_name = '';
            $electric_service_fee = 0;
            $charge_project_id = 0;
            if($jz_wsxdsj_net_customized_open && $meter_id){
                //查询是否超过设定异常刻度
                $data['meter_id'] = $meter_id;
                $meterMax = (new HouseNewChargeProject())->getOne(['id'=>$cate_id],'meter_use_v');
                $ammeter = bcsub(strval($end_num),strval($start_num),4);
                $data['meter_number']=$meter_number;
                $data['meter_type']=$meter_type;
                if($meter_extra_data){
                    $data['meter_extra_data']= json_encode($meter_extra_data,JSON_UNESCAPED_UNICODE);
                }
                if($meterMax && $meterMax['meter_use_v'] > 0 && $ammeter > $meterMax['meter_use_v']){
                    $data['is_unusual'] = 1;
                }
                //查询服务费标准信息
                $ruleIInfo = (new HouseNewChargeRule())->getOne(['id'=>$electric_service_rule_id],'charge_name,unit_price,charge_project_id');
                $electric_rule_name = $ruleIInfo['charge_name'] ?? '';
                $electric_service_fee = $ruleIInfo['unit_price'] ?? 0;
                $charge_project_id = $ruleIInfo['charge_project_id'] ?? 0;
            }
            
            $insertID = $service_house_new_meter->addMeterReading($data);
            if($insertID){
                $where_bind =[];
                $where_bind[] = ['floor_id', '=', $floor_id];
                $where_bind[] = ['vacancy_id', '=', $room_id];
                $where_bind[] = ['type', 'in', '0,3'];
                $res = $service_house_village_user_bind->getBindInfo($where_bind,true);
                $orderData = [];
                if($res){
                    $gas['type']= $electric['type']= $water['type'] = 1;
                    $gas['village_id']= $electric['village_id']= $water['village_id'] = $res['village_id'];
                    $gas['uid']= $electric['uid']= $water['uid'] = $bind_info['uid'];
                    $gas['usernum']= $electric['usernum']= $water['usernum'] = $res['usernum'];
                    $gas['name']= $electric['name']= $water['name'] = $res['name'];
                    $gas['phone']= $electric['phone']= $water['phone'] = $res['phone'];
                    $gas['bind_id']= $electric['bind_id']= $water['bind_id'] = $res['pigcms_id'];
                    $gas['add_time']= $electric['add_time']= $water['add_time'] = time();
                    $orderData['uid'] = $res['uid'];
                    $orderData['name'] = $res['name'];
                    $orderData['phone'] = $res['phone'];
                    $orderData['pigcms_id'] = $res['pigcms_id'];
                    $orderData['village_id'] = $village_id;
                    $orderData['property_id'] = $village_info['property_id'];
                }
                $orderData['order_type'] = $rule_info['charge_type'];
                $orderData['order_name'] = $rule_info['name'];
                $orderData['room_id'] = $room_id;
                $orderData['total_money'] = $data['cost_money'];
                $orderData['modify_money'] = $data['cost_money'];
                $orderData['project_id'] = $cate_id;
                $orderData['rule_id'] = $rule_id;
                $orderData['unit_price'] = $rule_info['unit_price'];
                $orderData['last_ammeter'] = $start_num;
                $orderData['now_ammeter'] = $insert_last_ammeter;
                $orderData['add_time'] = time();
                $orderData['meter_reading_id'] = $insertID;
                $service_start_time=time();
                if($meter_reading && !$meter_reading->isEmpty()){
                    $service_start_time=$meter_reading['add_time'];
                }
                $orderData['service_start_time']=$service_start_time;
                $service_end_time=time();
                $orderData['service_end_time']=$service_end_time;
                $service_house_new_cashier = new HouseNewCashierService();
                
                $electricityOrder = true;
                if($jz_wsxdsj_net_customized_open && $meter_id && $electric_service_rule_id && $electric_rule_name){
                    //水表编号仅仅记录一下
                    $unify_flage_id = 'electricityloss_'.$insertID;
                    //有服务费，生成关联标记，unify_flage_id：unify_flage_id：electricityloss_电表编号id   order_type_flag：electricityloss
                    $orderData['unify_flage_id'] = $unify_flage_id;
                    $orderData['order_type_flag'] = 'electricityloss';
                    //有服务费，生成一条有关联订单的服务费订单
                    $electricityOrderData = [
                        'uid'=>$orderData['uid']??0,
                        'name'=>$orderData['name']??'',
                        'phone'=>$orderData['phone']??'',
                        'pigcms_id'=>$orderData['pigcms_id']??0,
                        'meter_reading_id'=>$orderData['meter_reading_id'],
                        'property_id'=>$orderData['property_id'],
                        'village_id'=>$orderData['village_id'],
                        'order_type'=>'electricity_loss',
                        'order_name'=>$electric_rule_name,
                        'room_id'=>$orderData['room_id'],
                        'total_money'=>$electric_service_fee,
                        'modify_money'=>$electric_service_fee,
                        'project_id'=>$charge_project_id,
                        'rule_id'=>$electric_service_rule_id,
                        'unit_price'=>$electric_service_fee,
                        'add_time'=>time(),
                        'service_start_time'=>$orderData['service_start_time'],
                        'service_end_time'=>$orderData['service_end_time'],
                        'unify_flage_id'=>$orderData['unify_flage_id'],
                        'order_type_flag'=>$orderData['order_type_flag'],
                    ];
                    $electricityOrder = $service_house_new_cashier->addOrder($electricityOrderData);
                }
                $id = $service_house_new_cashier->addOrder($orderData);
                if($id && $electricityOrder){
                    //todo 自动扣费
                    if(isset($res['pigcms_id']) && $res['uid']){
                        $service_storage = new StorageService();
                        if($rule_info['charge_type'] == 'water')
                            $user_remarks = '移动端水费缴费操作';
                        elseif ($rule_info['charge_type'] == 'gas')
                            $user_remarks = '移动端燃气缴费费操作';
                        else
                            $user_remarks = '移动端电费缴费操作';
                        $system_remarks = '工作人员表 工作人员ID：'.$house_worker['wid'].' 名称：'.$house_worker['name'].' 手机号：'.$house_worker['phone'];
                        $opt_money_type = '';
                        if($rule_info['charge_type'] == 'water' && cfg('customized_meter_reading')==1 && isset($rule_info['water_type']) && $rule_info['water_type']==1){//冷水表
                            $opt_money_type = 'cold_water_balance';
                            $user_remarks = '移动端冷水费缴费操作';
                        }elseif($rule_info['charge_type'] == 'water' && cfg('customized_meter_reading')==1 && isset($rule_info['water_type']) && $rule_info['water_type']==2){//热水表
                            $opt_money_type = 'hot_water_balance';
                            $user_remarks = '移动端热水费缴费操作';
                        }elseif($rule_info['charge_type'] == 'electric' && cfg('customized_meter_reading')==1){
                            $opt_money_type = 'electric_balance';
                        }
                        $service_storage->userBalanceChange($res['uid'],2,$data['cost_money'],$system_remarks,$user_remarks,$id,$village_id,$opt_money_type);
                    }
                    return api_output(0,'抄表成功');
                }else{
                    return api_output_error(1001,'抄表失败');
                }
            }else{
                return api_output_error(1001,'抄表失败');
            }
        }
        return api_output(0,'抄表成功');
    }

    /**
     * 移动抄表分类
     * @author lijie
     * @date_time 2020/11/02
     * @return \json
     */
    public function meterReadCate()
    {
        $village_id = $this->request->post('village_id',0);
        if (!$village_id) {
            return api_output_error(1001,'缺少小区ID');
        }
        $service_meter_read_person = new MeterReadPersonService();
        $service_house_village = new HouseVillageService();
        $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'property_id');
        $service_house_new_property = new HouseNewPorpertyService();
        $is_new_version = $service_house_new_property->getTakeEffectTimeJudge($village_info['property_id']);
        if($is_new_version){
            $service_house_new_meter = new HouseNewMeterService();
            $where[] = ['p.village_id','=',$village_id];
            $where[] = ['p.status','=',1];
            $where[] = ['c.charge_type','in',['water','electric','gas']];
            $data = $service_house_new_meter->getMeterProject($where,'p.name as cate_name,p.id as cate_id',0,0,'p.id DESC');
            $list['cate_name'] = '全部';
            $list['cate_id'] = '';
            $list['village_id'] = $village_id;
            $dataList[] = $list;
            if($data){
                foreach ($data as $k=>$v){
                    $dataList[] = $v;
                }
            }
            $arr['dataList'] = $dataList;
        }else{
            $read_cate = $service_meter_read_person->getMeterReadCateList(['village_id'=>$village_id],true);
            $dataList = [];
            foreach($read_cate as $v){
                $list['cate_id'] = $v['id'];
                $list['cate_name'] = $v['cate_name'];
                $list['village_id'] = $v['village_id'];
                $dataList[] = $list;
            }
            $arr = [];
            $test_cate_list = array(
                'cate_name'=>'全部',
                'cate_id'=>'',
                'village_id' =>''
            );
            array_unshift($dataList,$test_cate_list);
            $arr['dataList'] = $dataList;
        }
        return api_output(0,$arr);
    }

    /**
     * 移动抄表(对应房间)==>记录
     * @author lijie
     * @date_time 2020/11/02
     * @return \json
     */
    public function recordRoomList()
    {
        $room_id = $this->request->post('room_id',0);
        if(!$room_id){
            return api_output_error(1001,'缺少房间ID');
        }
        $aPage = $this->request->post('page',1);
        $village_id = $this->request->post('village_id',0);
        $cate_id = $this->request->post('cate_id',0);
        $add_time = $this->request->post('add_time',0);
        $service_house_village = new HouseVillageService();
        $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'property_id');
        $service_house_new_property = new HouseNewPorpertyService();
        $is_new_version = $service_house_new_property->getTakeEffectTimeJudge($village_info['property_id']);
        $where = [];
        $where[] = ['village_id','=',$village_id];
        if(!$is_new_version){
            if($cate_id){
                $where[] = ['cate_id','=',$cate_id];
            }
            $where[] = ['room_id','=',$room_id];
        }else{
            if($cate_id){
                $where[] = ['project_id','=',$cate_id];
            }
            $where[] = ['layer_num','=',$room_id];
        }
        if($add_time){
            $start_time = strtotime($add_time.' 00:00:00');
            $end_time = strtotime($add_time.' 23:59:59');
            $where[] = ['add_time','between',array($start_time,$end_time)];
        }
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $service_house_new_meter = new HouseNewMeterService();
        $service_house_village = new HouseVillageService();
        $service_meter_read_person = new MeterReadPersonService();
        $service_house_worker = new HouseWorkerService();
        if(!$is_new_version){
            $record_list = $service_meter_read_person->getMeterReadRecordList($where,true,$aPage,10);
            $record_list_count = $service_meter_read_person->getMeterReadRecordCount($where);
        }else{
            $record_list = $service_house_new_meter->getMeterReadingRecordList($where,true,$aPage,10);
            $record_list_count = $service_house_new_meter->getMeterReadingRecordCount($where);
        }
        $arr = [];
        $dataList = [];
        foreach($record_list as $k=>$v){
            $list['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
            $list['start_num'] = isset($v['start_num'])?$v['start_num']:$v['start_ammeter'];//起度
            $list['end_num'] = isset($v['end_num'])?$v['end_num']:$v['last_ammeter'];//止度
            $house_worker = $service_house_worker->getOneWorker(array('wid'=>$v['person_id'],'is_del'=>0,'status'=>1,'village_id'=>$v['village_id']));
            $list['user_name'] = $v['user_name'];
            $find_village_room = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id'=>isset($v['room_id'])?$v['room_id']:$v['layer_num']], "uid,name,phone,floor_id,village_id,layer,room,housesize,pigcms_id,single_id,layer_id");
            $list['room_name'] = $find_village_room['room'];
            $list['user_name'] = $find_village_room['name']?$find_village_room['name']:$v['user_name'];
            $list['user_phone'] = $find_village_room['phone']?$find_village_room['phone']:$v['user_bind_phone'];
            $address = $service_house_village->getSingleFloorRoom($find_village_room['single_id'],$find_village_room['floor_id'],$find_village_room['layer_id'],$find_village_room['pigcms_id'],$find_village_room['village_id']);
            $list['user_address'] = $address;

            if($is_new_version){
                $service_house_new_meter = new HouseNewMeterService();
                $project = $service_house_new_meter->getMeterProjectInfo(['p.id'=>$v['project_id']],'r.unit_price,r.rate,p.name as project_name,c.charge_number_name as cate_name');
                $list['cate_name'] = $project['cate_name'];
                $list['worker_name'] = $v['work_name'];
                $list['project_name'] = $project['project_name'];
                $list['unit_price'] = $v['unit_price'];//单价
                $list['rate'] = $v['rate'];//倍率
                $list['pool'] = '无';//公摊
            }else{
                $project = $service_meter_read_person->getMeterReadProject(array('id'=>$v['project_id'],'village_id'=>$v['village_id'],'cate_id'=>$v['cate_id']),true);
                $cate = $service_meter_read_person->getMeterReadCate(array('id'=>$v['cate_id']));
                $list['cate_name'] = $cate['cate_name'];
                $list['worker_name'] = $house_worker['name'];
                $list['project_name'] = $project['project_name'];
                $list['unit_price'] = $project['unit_price'];//单价
                $list['rate'] = $project['rate'];//倍率
                $list['pool'] = $project['pool'];//公摊
            }
            $dataList[] = $list;
        }
        $arr['dataList'] = $dataList;
        $arr['record_list_count'] = $record_list_count;
        return api_output(0,$arr);
    }

    /**
     * 移动抄表所有记录===>已完成
     * @author lijie
     * @date_time 2020/11/02
     * @return \json
     */
    public function recordList()
    {
        $info = $this->getLoginInfo();
        if (isset($info['status']) && $info['status']!=1000) {
            return api_output($info['status'],[],$info['msg']);
        }
        $wid = $this->_uid;
        if (empty($wid)) {
            return api_output_error(1001,'登录失败');
        }
        $phone = $info['user']['login_phone'];
        $aPage = $this->request->post('page',1);
        $where = [];
        $cate_id = $this->request->post('cate_id',0);
        $village_id = $this->request->post('village_id',0);
        if (empty($village_id)) {
            return api_output_error(1001,'缺少village_id');
        }
        $service_house_village = new HouseVillageService();
        $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'property_id');
        $service_house_new_property = new HouseNewPorpertyService();
        $is_new_version = $service_house_new_property->getTakeEffectTimeJudge($village_info['property_id']);
        $where[] = ['village_id','=',$village_id];
        if($cate_id && !$is_new_version){
            $where[] = ['cate_id','=',$cate_id];
        }
        if($cate_id && $is_new_version){
            $where[] = ['project_id','=',$cate_id];
        }
        $add_time = $this->request->post('add_time',0);
        if($add_time){
            $start_time = strtotime($add_time.' 00:00:00');
            $end_time = strtotime($add_time.' 23:59:59');
            $where[] = ['add_time','>=',$start_time];
            $where[] = ['add_time','<=',$end_time];
        }
        $keyword = $this->request->post('keyword','');
        if($keyword){
            $where[] = ['user_name|user_bind_phone','=',$keyword];
        }
        //增加工作人员判断
        $village_user_bind['village_id'] = $village_id;
        $village_user_bind['type'] = 4;
        $village_user_bind['phone'] = $phone;
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_worker = new HouseWorkerService();
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $service_meter_read_person = new MeterReadPersonService();
        $service_house_new_meter = new HouseNewMeterService();
        $service_user = new UserService();
        $bind_info = $service_house_village_user_bind->getBindInfo($village_user_bind);
        if(empty($bind_info)){
            return api_output_error(1001,'你不是本小区的工作的人员,请联系管理员添加!');
        }
        $house_worker = $service_house_worker->getOneWorker(array('village_id'=>$bind_info['village_id'],'phone'=>$bind_info['phone'],'status'=>1,'is_del'=>0));
        if(empty($house_worker)){
            return api_output_error(1001,'你不是本小区的工作的人员,请联系管理员添加!');
        }
        //$where[] = ['person_id','=',$house_worker['wid']];
        if(!$is_new_version){
            $record_list = $service_meter_read_person->getMeterReadRecordList($where,true,$aPage,10);
            $record_list_count = $service_meter_read_person->getMeterReadRecordCount($where);
        }else{
            $record_list = $service_house_new_meter->getMeterReadingRecordList($where,true,$aPage,10);
            $record_list_count = $service_house_new_meter->getMeterReadingRecordCount($where);
        }
        $arr = [];
        $dataList = [];
        foreach($record_list as $k=>$v){
            $list['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
            $list['start_num'] = isset($v['start_num'])?$v['start_num']:$v['start_ammeter'];//起度
            $list['end_num'] = isset($v['end_num'])?$v['end_num']:$v['last_ammeter'];//止度
            $house_worker = $service_house_worker->getOneWorker(array('wid'=>$v['person_id'],'is_del'=>0,'status'=>1,'village_id'=>$v['village_id']));
            $list['user_name'] = $v['user_name'];
            $list['worker_name'] = isset($house_worker['name'])?$house_worker['name']:'';
			$room_id=0;
			if(isset($v['room_id'])){
				$room_id=$v['room_id'];
			}elseif(isset($v['layer_num'])){
				$room_id=$v['layer_num'];
			}
            $find_village_room = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id'=>$room_id], "pigcms_id,uid,name,phone,floor_id,village_id,layer,room,housesize,single_id,layer_id");
            $list['room_name'] = isset($find_village_room['room'])?$find_village_room['room']:'';
            $list['user_name'] = isset($find_village_room['name'])?$find_village_room['name']:$v['user_name'];
            $list['user_phone'] = isset($find_village_room['phone'])?$find_village_room['phone']:$v['user_bind_phone'];
            $address = $service_house_village->getSingleFloorRoom($find_village_room['single_id'],$find_village_room['floor_id'],$find_village_room['layer_id'],$find_village_room['pigcms_id'],$find_village_room['village_id']);
            $list['user_address'] = $address;
			$list['room_id']=$room_id;
			$list['is_new_version']=$is_new_version;
            if($is_new_version){
                $service_house_new_meter = new HouseNewMeterService();
                $project = $service_house_new_meter->getMeterProjectInfo(['p.id'=>$v['project_id']],'r.unit_price,r.rate,p.name as project_name,c.charge_number_name as cate_name');
                $list['cate_name'] = $project['cate_name'];
                $list['project_name'] = $project['project_name'];
                $list['unit_price'] = $project['unit_price'];//单价
                $list['rate'] = $project['rate'];//倍率
                $list['pool'] = '无';//公摊
            }else{
                $project = $service_meter_read_person->getMeterReadProject(array('id'=>$v['project_id'],'village_id'=>$v['village_id'],'cate_id'=>$v['cate_id']),true);
                $cate = $service_meter_read_person->getMeterReadCate(array('id'=>$v['cate_id']));
                $list['cate_name'] = $cate['cate_name'];
                $list['project_name'] = $project['project_name'];
                $list['unit_price'] = $project['unit_price'];//单价
                $list['rate'] = $project['rate'];//倍率
                $list['pool'] = $project['pool'];//公摊
            }
            $dataList[] = $list;
        }
        $arr['dataList'] = $dataList;
        $arr['record_list_count'] = $record_list_count;
        return api_output(0,$arr);
    }

    /**
     * 房间抄表项目详情
     * @author lijie
     * @date_time 2020/11/03
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function roomInfo()
    {
        $info = $this->getLoginInfo();
        if (isset($info['status']) && $info['status']!=1000) {
            return api_output($info['status'],[],$info['msg']);
        }
        $wid = $this->_uid;
        if (empty($wid)) {
            return api_output_error(1001,'登录失败');
        }
        $phone = $info['user']['login_phone'];
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $service_house_village = new HouseVillageService();
        $service_house_worker = new HouseWorkerService();
        $service_meter_read_person = new MeterReadPersonService();
        $service_user = new UserService();
        $village_id = $this->request->post('village_id',0);
        if (!$village_id) {
            return api_output_error(1001,'缺少village_id');
        }
        $village_user_bind['village_id'] = $village_id;
        $village_user_bind['type'] = 4;
        $village_user_bind['phone'] = $phone;

        $bind_info = $service_house_village_user_bind->getBindInfo($village_user_bind);
        if(empty($bind_info)){
            return api_output_error(1001,'你不是本小区的工作的人员,请联系管理员添加!');
        }
        $house_worker = $service_house_worker->getOneWorker(array('village_id'=>$bind_info['village_id'],'phone'=>$bind_info['phone'],'status'=>1,'is_del'=>0));
        if(empty($house_worker)){
            return api_output_error(1001,'你不是本小区的工作的人员,请联系管理员添加!');
        }
        $cate_id = $this->request->post('cate_id',0);//水电煤分类id
        if(!$cate_id){
            return api_output_error(1001,'缺少水电费分类ID');
        }
        $pigcms_id = $this->request->post('pigcms_id',0);//房间id
        if(!$pigcms_id){
            return api_output_error(1001,'缺少房间ID');
        }
        //查询 房间信息 单元信息 小区信息
        $find_village_room = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id'=>$pigcms_id], "pigcms_id,uid,name,phone,floor_id,village_id,layer,room,housesize,single_id,floor_id,layer_id");
        $find_village_floor = $service_house_village->getHouseVillageFloorInfo(['floor_id'=>$find_village_room['floor_id']],'village_id,floor_name,floor_layer');
        $find_village = $service_house_village->getHouseVillageInfo(['village_id'=>$find_village_floor['village_id']] , '*');
        $room_address = $service_house_village->getSingleFloorRoom($find_village_room['single_id'],$find_village_room['floor_id'],$find_village_room['layer_id'],$find_village_room['pigcms_id'],$find_village_room['village_id']);
        $user_bind_info = $service_house_village_user_bind->getBindInfo([['village_id','=',$village_id],['vacancy_id','=',$pigcms_id],['status','=',1],['type','in','0,3']],'pigcms_id,phone,name');
        $find_info = array(
            'room_id'         =>  $find_village_room['pigcms_id'],//房间id
            'user_name'         =>  isset($user_bind_info['name'])?$user_bind_info['name']:0,//业主名称
            'user_phone'         =>  isset($user_bind_info['phone'])?$user_bind_info['phone']:0,//业主联系方式
            'user_uid'         =>  isset($user_bind_info['pigcms_id'])?$user_bind_info['pigcms_id']:0,//业主uid
            'vacancy_layer'         =>  $find_village_room['layer'],
            'vacancy_floor_id'      =>  $find_village_room['floor_id'],
            'vacancy_room'          =>  $find_village_room['room'],
            'vacancy_housesize'          =>  $find_village_room['housesize'],
            'village_name'          =>  $find_village['village_name'],
            'village_address'       =>  $find_village['village_address'],
            'floor_name'            =>  $find_village_floor['floor_name'],
            'floor_layer'           =>  $find_village_floor['floor_layer'],
            'village_id'            =>  $find_village_floor['village_id'],
            'room_address' => $room_address
        );
        $find_info['user_phone']=$service_house_village->phoneDesensitizationV($find_info['user_phone'],$village_id,$find_village);
        $arr = [];
        $arr['find_info'] = $find_info;
        $service_house_new_meter = new HouseNewMeterService();
        $service_house_new_property = new HouseNewPorpertyService();
        $service_house_new_charge_project = new HouseNewChargeProjectService();
        $is_new_version = $service_house_new_property->getTakeEffectTimeJudge($find_village['property_id']);
        if(!$is_new_version){
            $read_person = $service_meter_read_person->getMeterReadPerson(array('uid'=>$house_worker['wid'],'village_id'=>$village_id,'status'=>1,'cate_id'=>$cate_id),array(),true,'id ASC');
            $project = $service_meter_read_person->getMeterReadProjectList(array('cate_id'=>$cate_id,'village_id'=>$village_id),true);
        }else{
            $read_person = $service_house_new_meter->getMeterDirectorInfo(['worker_id'=>$house_worker['wid'],'village_id'=>$village_id,'status'=>1,'project_id'=>$cate_id],true);
            $project = $service_house_new_charge_project->getProjectInfo(array('id'=>$cate_id,'village_id'=>$village_id,'status'=>1),true);
        }

        if(empty($project)){
            return api_output_error(1001,'抄表项目不存在!');
        }
        $now_user = $service_user->getUserOne(array('uid'=>$bind_info['uid']));
        if(empty($read_person)){
            return api_output_error(1001,'移动抄表负责人不存在,请管理员进入移动抄表后台配置！');
        }
        $db_house_property_digit_service = new HousePropertyDigitService();

        $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$find_village['property_id']]);
        if($is_new_version){
            $last_record = $service_house_new_meter->getMeterRecordInfo(['project_id'=>$cate_id,'layer_num'=>$pigcms_id,'village_id'=>$village_id],'last_ammeter');
            if($last_record){
                $arr['start_num'] = $last_record['last_ammeter'];
            }else{
                $arr['start_num'] = 0.00;
            }
            $service_house_new_charge_rule = new HouseNewChargeRuleService();
            $rule_id = $service_house_new_charge_rule->getValidChargeRule($cate_id);
            $rule_info = $service_house_new_meter->getMeterProjectInfo(['p.id'=>$cate_id,'r.id'=>$rule_id],'r.unit_price,r.rate,r.measure_unit,p.id as project_id,p.name,c.charge_type');
            $measure_unit = $rule_info['measure_unit']?'（'.$rule_info['measure_unit'].'）':'（元/度）';//单位
            $onerule_digit = $db_house_property_digit_service->get_onerule_digit(['id' => $rule_id], 'id,rule_digit');
            if (!empty($onerule_digit) && $onerule_digit['rule_digit'] > -1 && $onerule_digit['rule_digit'] < 5) {
                $rule_digit = $onerule_digit['rule_digit'];
                    if (!empty($digit_info)) {
                        $digit_info['meter_digit'] = $rule_digit;
                        $digit_info['other_digit'] = $rule_digit;
                    } else {
                        $digit_info = array('type' => 1);
                        $digit_info['meter_digit'] = $rule_digit;
                        $digit_info['other_digit'] = $rule_digit;
                    }
            }

            $rule_info['unit_price']=formatNumber($rule_info['unit_price'],4,1);

            $arr['room_project'] = array(
                'unit_price'=>$rule_info['unit_price'],//单价
                'rate'=>$rule_info['rate'],//倍率
                'pool'=>0,//公摊
                'measure_unit'=>$rule_info['unit_price'].$measure_unit,
                'project_id'=>$rule_info['project_id'],
                'charge_type'=>$rule_info['charge_type'],
                'project_name'=>$rule_info['name'],
            );
            $arr['room_project']['order_yh_per']=0;
            $arr['room_project']['unit_price_base']=0;
            if($rule_info['charge_type']=='new_nuanqifei'){
                $param=array('village_id'=>$village_id,'type'=>1,'xtype'=>'flowCharg');
                $heatingService= new HeatingService();
                $flowChargSet=$heatingService->getHeatingProtocol($param);
                if($flowChargSet && $flowChargSet['order_yh_per']){
                    $arr['room_project']['order_yh_per']= $flowChargSet['order_yh_per'];
                }
                if($flowChargSet && $flowChargSet['unit_price_base']){
                    $arr['room_project']['unit_price_base']= $flowChargSet['unit_price_base'];
                }
            }
        }else{
            $read_cate = $service_meter_read_person->getMeterReadCate(array('id'=>$cate_id,'village_id'=>$village_id));
            if(empty($read_cate)){
                return api_output_error(1001,'水电煤ID不存在');
            }
            $where['cate_id'] = $read_cate['id'];
            $where['village_id'] = $village_id;
            $read_project_list = $service_meter_read_person->getMeterReadProjectList($where);
            if(empty($read_project_list)){
                return api_output_error(1001,'没有水电煤分类');
            }
            foreach($read_project_list as $k=>$v){
                if($v['room_id']){
                    $room_ids = explode(',',$v['room_id']);
                    foreach($room_ids as $k1=>$v1){
                        if($pigcms_id == $v1){
                            $where['room_id'] = $pigcms_id;
                            $where['floor_id'] = $find_village_room['floor_id'];
                            $where['village_id'] = $village_id;
                            $record = $service_meter_read_person->getMeterReadRecord($where);
                            if($record){
                                $arr['start_num'] = $record['end_num'];
                            }else{
                                $arr['start_num'] = 0.00;
                            }

                            $v['unit_price']=formatNumber($v['unit_price'],4,1);

                            $arr['room_project'] = array(
                                'unit_price'=>$v['unit_price'],//单价
                                'rate'=>$v['rate'],//倍率
                                'pool'=>$v['pool'],//公摊
                                'measure_unit'=>$v['unit_price'].'（元/立方米）',
                            );
                        }
                    }
                }else{
                    return api_output_error(1001,'移动抄表配置不存在,请管理员进入后台配置。');
                }
            }
        }
        if(empty($arr['room_project']['unit_price'])){
            $arr['room_project'] = [];
        }
        $jz_wsxdsj_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        $arr['sign_btn_show'] = $project['sign_btn_show'] ?? 0;//是否展示签字功能
        $arr['meter_info'] = [
            'jz_wsxdsj_customized_open'=>$jz_wsxdsj_customized_open ? 1 : 0,//是否定制
            'reset_btn_show'=>$project['reset_btn_show'] ?? 0,//是否可以重置起度
            'meter_use_v'=>$project['meter_use_v'] ?? 0,//提醒最大用量（0为不限制）
            'meter_list'=>[],//水电表编号列表
            'electric_service_info'=>[],//服务费标准列表
        ];
        $subjectId = $project['subject_id'] ?? 0;
        $chargeType = '';
        if($subjectId){
            $chargeType = (new HouseNewChargeNumber())->getOneField(['id'=>$subjectId],'charge_type');
        }
        $arr['attachments_show'] = $jz_wsxdsj_customized_open && $chargeType == 'new_nuanqifei' ? 1 : 0;//是否展示附录上传(仅取暖费)
        if($is_new_version && $jz_wsxdsj_customized_open && $chargeType){
            $meter_info = (new HouseVillageRoomCustomizedMeters())->getListData([
                'room_id'=>$find_village_room['pigcms_id'],
                'meter_type'=>$chargeType,
                'del_time'=>0
            ],'','id DESC',0);
            $meter_info = $meter_info ? $meter_info->toArray() : [];
            foreach ($meter_info as $vMeter){
                $arr['meter_info']['meter_list'][] = [
                    'id'=>$vMeter['id'],
                    'meter_number'=>$vMeter['meter_number'],
                    'meter_type'=>$vMeter['meter_type']
                ];
            }
            $arr['start_num'] = 0.00;
        }
        if($is_new_version && $jz_wsxdsj_customized_open && $chargeType && $chargeType == 'electric'){
            $houseNewChargeNumber = new HouseNewChargeNumber();
            $where = [
                'n.charge_type'=>'electricity_loss',
                'n.status'=>1,
                'p.village_id'=>$village_id,
                'p.status'=>1,
                'r.village_id'=>$village_id,
                'r.status'=>1
            ];
            $ruleList = $houseNewChargeNumber->getRuleList($where,'r.id,r.charge_name,r.unit_price,r.charge_project_id','r.id desc');
            $arr['meter_info']['electric_service_info'] = $ruleList ? $ruleList->toArray() : [];
            foreach ($arr['meter_info']['electric_service_info'] as &$vRule){
                $vRule['unit_price'] = getFormatNumber($vRule['unit_price']);
            }
        }
        return api_output(0,$arr);
    }

    /**
     * 房间抄表项目
     * @author lijie
     * @date_time 2020/11/03
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function roomInfoNewProject()
    {
        $aPage = $this->request->post('page',0);
        if(!$aPage){
            return api_output_error(1001,'分页参数必须传');
        }
        $layer_id = $this->request->post('layer_id',0);
        if(empty($layer_id)){
            return api_output_error(1001,'楼层id必须存在');
        }
        $pigcms_id = $this->request->post('pigcms_id',0);
        if(!$pigcms_id){
            return api_output_error(1001,'参数传递有误！');
        }
        $arr = [];
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_worker = new HouseWorkerService();
        $service_house_village = new HouseVillageService();
        $service_meter_read_person = new MeterReadPersonService();
        $service_house_new_property = new HouseNewPorpertyService();
        $vacancy_where[] = ['layer_id','=',$layer_id];
        //$vacancy_where[] = ['uid','<>',0];
        $vacancy_list = $service_house_village_user_vacancy->getVacancyList($vacancy_where , true , 'sort desc, pigcms_id asc');
        if($vacancy_list){
            $total_page = count($vacancy_list);
        }else{
            $total_page = 0;
        }
        $vacancy_arr = [];
        foreach($vacancy_list as $k1=>$v1) {
            $list['page'] = $k1 + 1;
            $list['room_id'] = $v1['pigcms_id'];
            $list['room_info'] = $v1['layer'] . '#'.$v1['room'];
            $vacancy_arr[] = $list;
        }
        if($vacancy_arr){
            $arr['start_room_info'] = '';
            $arr['last_room_info'] = '';
            foreach($vacancy_arr as $k=>$v){
                if($v['page'] == $aPage){
                    $pigcms_id = $v['room_id'];
                }

                if($v['page'] == ($aPage-1)){
                    $arr['start_room_info'] = $v['room_info'];
                }
                if($v['page'] == ($aPage+1)){
                    $arr['last_room_info'] = $v['room_info'];
                }
            }
        }
        $info = $this->getLoginInfo();
        if (isset($info['status']) && $info['status']!=1000) {
            return api_output($info['status'],[],$info['msg']);
        }
        $wid = $this->_uid;
        if (empty($wid)) {
            return api_output_error(1001,'登录失败');
        }
        $phone = $info['user']['login_phone'];
        //增加工作人员判断
        $village_id = $this->request->post('village_id',0);
        if (!$village_id) {
            return api_output_error(1001,'缺少必传参数village_id');
        }
        $village_user_bind['village_id'] = $village_id;
        $village_user_bind['type'] = 4;
        $village_user_bind['phone'] = $phone;
        $bind_info = $service_house_village_user_bind->getBindInfo($village_user_bind);
        if(empty($bind_info)){
            return api_output_error(1001,'你不是本小区的工作的人员,请联系管理员添加!');
        }
        $house_worker = $service_house_worker->getOneWorker(array('village_id'=>$bind_info['village_id'],'phone'=>$bind_info['phone'],'status'=>1,'is_del'=>0));
        if(empty($house_worker)){
            return api_output_error(1001,'你不是本小区的工作的人员,请联系管理员添加!');
        }

        $bind_where['pigcms_id'] = $pigcms_id;
        //查询 房间信息 单元信息 小区信息
        $find_village_room = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id'=>$pigcms_id], "pigcms_id,uid,name,phone,floor_id,village_id,layer,room,housesize,single_id,layer_id");
        $find_village_floor = $service_house_village->getHouseVillageFloorInfo(['floor_id'=>$find_village_room['floor_id']],'village_id,floor_name,floor_layer');
        $find_village = $service_house_village->getHouseVillageInfo(['village_id'=>$find_village_floor['village_id']] , '*');
        $is_new_version = $service_house_new_property->getTakeEffectTimeJudge($find_village['property_id']);
        $room_address = $service_house_village->getSingleFloorRoom($find_village_room['single_id'],$find_village_room['floor_id'],$find_village_room['layer_id'],$find_village_room['pigcms_id'],$find_village_room['village_id']);
        $find_info = array(
            'room_id'         =>  $find_village_room['pigcms_id'],//房间id
            'user_name'         =>  $find_village_room['name'],//业主名称
            'user_phone'         =>  $find_village_room['phone'],//业主联系方式
            'vacancy_layer'         =>  $find_village_room['layer'],
            'vacancy_floor_id'      =>  $find_village_room['floor_id'],
            'vacancy_room'          =>  $find_village_room['room'],
            'vacancy_housesize'          =>  $find_village_room['housesize'],
            'village_name'          =>  $find_village['village_name'],
            'village_address'       =>  $find_village['village_address'],
            'floor_name'            =>  $find_village_floor['floor_name'],
            'floor_layer'           =>  $find_village_floor['floor_layer'],
            'village_id'            =>  $find_village_floor['village_id'],
            'room_address' => $room_address

        );
        $find_info['user_phone']=$service_house_village->phoneDesensitizationV($find_info['user_phone'],$village_id,$find_village);
        $db_house_property_digit_service = new HousePropertyDigitService();

        $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$find_village['property_id']]);
        $arr['find_info'] = $find_info;
        if($is_new_version){
            $service_house_new_meter = new HouseNewMeterService();
            $where[] = ['p.village_id','=',$village_id];
            $where[] = ['p.status','=',1];
            $where[] = ['c.charge_type','in',['water','electric','gas','new_nuanqifei']];
            $read_cate = $service_house_new_meter->getMeterProject($where,'p.name as cate_name,p.village_id,c.charge_number_name as subject_name,charge_type,p.id',0,0,'p.id DESC');
            if($read_cate){
                $service_house_new_charge_rule = new HouseNewChargeRuleService();
                foreach ($read_cate as $k=>$v){
                    $rule_id = $service_house_new_charge_rule->getValidChargeRule($v['id']);
                    if(!$rule_id){
                        unset($read_cate[$k]);
                        continue;
                    }
                    $bindInfo=$service_house_new_meter->getIsBind(['project_id'=>$v['id'],'vacancy_id'=>$find_village_room['pigcms_id'],'rule_id'=>$rule_id,'is_del'=>1]);
                    if(empty($bindInfo)){
                        unset($read_cate[$k]);
                        continue;
                    }
                    $onerule_digit = $db_house_property_digit_service->get_onerule_digit(['id' => $rule_id], 'id,rule_digit');
                    if (!empty($onerule_digit) && $onerule_digit['rule_digit'] > -1 && $onerule_digit['rule_digit'] < 5) {
                        $rule_digit = $onerule_digit['rule_digit'];
                        if (!empty($digit_info)) {
                            $digit_info['meter_digit'] = $rule_digit;
                            $digit_info['other_digit'] = $rule_digit;
                        } else {
                            $digit_info = array('type' => 1);
                            $digit_info['meter_digit'] = $rule_digit;
                            $digit_info['other_digit'] = $rule_digit;
                        }
                    }
                    $rule_info = $service_house_new_charge_rule->getCallInfo(['r.id'=>$rule_id],'r.charge_name,r.unit_price,r.rate,r.id as rule_id');
                    if(!empty($digit_info)){
                        $rule_info['unit_price']=formatNumber($rule_info['unit_price'],$digit_info['meter_digit'],$digit_info['type']);
                    }
                    $read_cate[$k]['charge_name'] = $rule_info['charge_name'];
                    $read_cate[$k]['unit_price'] = $rule_info['unit_price'];
                    $read_cate[$k]['rate'] = $rule_info['rate'];
                    $read_cate[$k]['rule_id'] = $rule_info['rule_id'];
                }
            }
        }else{
            $read_person = $service_meter_read_person->getMeterReadPersonList(array('uid'=>$house_worker['wid'],'village_id'=>$find_village_floor['village_id'],'status'=>1));
            if(empty($read_person)){
                return api_output_error(1001,'移动抄表负责人不存在,请管理员进入移动抄表后台配置');
            }
            $read_persons = array();
            foreach($read_person as $k=>$v){
                $read_persons[] = $v['cate_id'];
            }
            $where[] = ['id','in',$read_persons];
            $read_cate = $service_meter_read_person->getMeterReadCateList($where,true);

            foreach($read_cate as $k1=>&$v1){
                $room_str = $service_meter_read_person->getMeterReadProject(['cate_id'=>$v1['id'],'village_id'=>$village_id],'room_id');
                if(empty($room_str)){
                    unset($read_cate[$k1]);
                }else{
                    $room_arr = explode(',',$room_str['room_id']);
                    if(!in_array($find_village_room['pigcms_id'],$room_arr)){
                        unset($read_cate[$k1]);
                    }
                }
                $record = $service_meter_read_person->getMeterReadRecord(array('cate_id'=>$v1['id'],'room_id'=>$find_village_room['pigcms_id'],'village_id'=>$find_village_floor['village_id']));
                if($record){
                    $add_time_d = date('d',$record['add_time']);
                    if(intval($add_time_d) >= intval($v1['cycle_time'])){
                        $v1['status'] = 2;
                        $v1['statusDesc'] = '已抄';
                    }else{
                        $v1['status'] = 1;
                        $v1['statusDesc'] = '未抄完';
                    }
                }else{
                    $v1['status'] = 1;
                    $v1['statusDesc'] = '未抄';
                }
            }
        }
        if($read_cate){
            $read_cate = $read_cate->toArray();
        }
        $arr['read_cate'] = array_values($read_cate);
        $arr['total_page'] = $total_page;
        $arr['is_new_version'] = $is_new_version;
        return api_output(0,$arr);
    }

    /**
     * 小区房间选择
     * @author lijie
     * @date_time 2020/11/03
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function villageRoomSelectList(){
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_worker = new HouseWorkerService();
        $service_house_village = new HouseVillageService();
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $service_meter_read_person = new MeterReadPersonService();
        $info = $this->getLoginInfo();
        if (isset($info['status']) && $info['status']!=1000) {
            return api_output($info['status'],[],$info['msg']);
        }
        $wid = $this->_uid;
        if (empty($wid)) {
            return api_output_error(1001,'登录失败');
        }
        $phone = $info['user']['login_phone'];
        $floor_id = $this->request->post('floor_id',0);
        if(!$floor_id){
            return api_output_error(1001,'参数传递有误！');
        }
        //增加工作人员判断
        $village_id = $this->request->post('village_id',0);
        if (!$village_id) {
            return api_output_error(1001,'社区ID不能为空');
        }
        $aPage = $this->request->post('page',1);
        $village_user_bind['village_id'] = $village_id;
        $village_user_bind['type'] = 4;
        $village_user_bind['phone'] = $phone;
        $bind_info = $service_house_village_user_bind->getBindInfo($village_user_bind);
        if(empty($bind_info)){
            return api_output_error(1001,'你不是本小区的工作的人员,请联系管理员添加!');
        }
        $house_worker = $service_house_worker->getOneWorker(array('village_id'=>$bind_info['village_id'],'phone'=>$bind_info['phone'],'status'=>1,'is_del'=>0));
        if(empty($house_worker)){
            return api_output_error(1001,'你不是本小区的工作的人员,请联系管理员添加!');
        }
        $arr = [];
        //查询单元信息 小区信息
        $find_village_floor = $service_house_village->getHouseVillageFloorInfo(['floor_id'=>$floor_id],'village_id,floor_name,floor_layer');
        $find_village = $service_house_village->getHouseVillageInfo(['village_id'=>$find_village_floor['village_id']] , 'village_name,village_address');
        $find_info = array(
            'village_name'    =>  $find_village['village_name'],
            'village_address' =>  $find_village['village_address'],
            'floor_name'      =>  $find_village_floor['floor_name'],
            'floor_layer'     =>  $find_village_floor['floor_layer'],
            'village_id'     =>  $find_village_floor['village_id']
        );
        $arr['find_info'] = $find_info;

        $vacancy_where['floor_id'] = $floor_id;
        $vacancy_list = $service_house_village_user_vacancy->getVacancyList($vacancy_where , true , 'pigcms_id asc');
        $dataList = [];
        foreach($vacancy_list as $k=>$v){
            $list['room_id'] = $v['pigcms_id'];
            $list['floor_id'] = $v['floor_id'];
            $list['village_id'] = $v['village_id'];
            $list['layer'] = $v['layer'];
            $list['room'] = $v['room'];
            $list['pigcms_id'] = $v['pigcms_id'];
            $list['room_info'] = $v['layer'] . '#'.$v['room'];
            //类别
            $read_person = $service_meter_read_person->getMeterReadPersonList(array('uid'=>$house_worker['wid'],'village_id'=>$find_village_floor['village_id'],'status'=>1));
            if(empty($read_person)){
                return api_output_error(1001,'移动抄表负责人不存在,请管理员进入移动抄表后台配置');
            }
            foreach($read_person as $k1=>$v1){
                $read_persons[] = $v1['cate_id'];
            }
            $water = $service_meter_read_person->getMeterReadCate([['id','in',$read_persons],['village_id','=',$v['village_id']],['cate_name','=','水费']]);
            $electric = $service_meter_read_person->getMeterReadCate([['id','in',$read_persons],['village_id','=',$v['village_id']],['cate_name','=','电费']]);
            $gas = $service_meter_read_person->getMeterReadCate([['id','in',$read_persons],['village_id','=',$v['village_id']],['cate_name','=','燃气费']]);

            $water_data['village_id'] = $electric_data['village_id'] = $gas_data['village_id'] = $v['village_id'];
            $water_data['floor_id'] =$electric_data['floor_id'] =$gas_data['floor_id'] = $v['floor_id'];
            $water_data['room_id']=$electric_data['room_id']=$gas_data['room_id'] = $v['pigcms_id'];

            $water_data['cate_id'] = $water['id'];
            $electric_data['cate_id'] = $electric['id'];
            $gas_data['cate_id'] = $gas['id'];

            $water_record = $service_meter_read_person->getMeterReadRecord($water_data);
            $electric_record = $service_meter_read_person->getMeterReadRecord($electric_data);
            $gas_record = $service_meter_read_person->getMeterReadRecord($gas_data);
            if($water_record && $electric_record && $gas_record){
                if(date('d',$water_record['add_time']) >= $water['cycle_time']){

                    if(date('d',$electric_record['add_time']) >= $electric['cycle_time']){

                        if(date('d',$gas_record['add_time']) >= $gas['cycle_time']){
                            $list['status'] = 2;
                            $list['statusDesc'] = '已抄完';
                        }else{
                            $list['status'] = 1;
                            $list['statusDesc'] = '未抄完';
                        }
                    }else{
                        $list['status'] = 1;
                        $list['statusDesc'] = '未抄完';
                    }
                }else{
                    $list['status'] = 1;
                    $list['statusDesc'] = '未抄完';
                }

            }elseif($water_record && $electric_record){
                if(date('d',$water_record['add_time']) >= $water['cycle_time']){
                    if(date('d',$electric_record['add_time']) >= $electric['cycle_time']){
                        $list['status'] = 2;
                        $list['statusDesc'] = '已抄完';
                    }else{
                        $list['status'] = 1;
                        $list['statusDesc'] = '未抄完';
                    }
                }else{
                    $list['status'] = 1;
                    $list['statusDesc'] = '未抄完';
                }
            }elseif($water_record && $gas_record){
                if(date('d',$water_record['add_time']) >= $water['cycle_time']){
                    if(date('d',$gas_record['add_time']) >= $gas['cycle_time']){
                        $list['status'] = 2;
                        $list['statusDesc'] = '已抄完';
                    }else{
                        $list['status'] = 1;
                        $list['statusDesc'] = '未抄完';
                    }
                }else{
                    $list['status'] = 1;
                    $list['statusDesc'] = '未抄完';
                }
            }elseif($gas_record && $electric_record){
                if(date('d',$electric_record['add_time']) >= $electric['cycle_time']){
                    if(date('d',$gas_record['add_time']) >= $gas['cycle_time']){
                        $list['status'] = 2;
                        $list['statusDesc'] = '已抄完';
                    }else{
                        $list['status'] = 1;
                        $list['statusDesc'] = '未抄完';
                    }
                }else{
                    $list['status'] = 1;
                    $list['statusDesc'] = '未抄完';
                }
            }else{
                $list['status'] = 1;
                $list['statusDesc'] = '未抄完';
            }


            $dataList[] = $list;
        }
        $arr['vacancy_list'] = $dataList;
        return api_output(0,$arr);
    }

    /**
     * 移动抄表(房间列表)
     * @author lijie
     * @date_time 2020/11/03
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function villageRoomNewList(){
        $info = $this->getLoginInfo();
        if (isset($info['status']) && $info['status']!=1000) {
            return api_output($info['status'],[],$info['msg']);
        }
        $wid = $this->_uid;
        if (empty($wid)) {
            return api_output_error(1001,'登录失败');
        }
        $phone = $info['user']['login_phone'];
        $layer_id = $this->request->post('layer_id',0);
        if(!$layer_id){
            return api_output_error(1001,'楼号不存在');
        }
        $service_house_village = new HouseVillageService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $service_house_worker = new HouseWorkerService();
        $service_meter_read_person = new MeterReadPersonService();
        $layer = $service_house_village->getHouseVillageLayerInfo(['id'=>$layer_id]);
        //增加工作人员判断
        $village_id = $this->request->post('village_id',0);
        if (!$village_id) {
            return api_output_error(1001,'社区ID不能为空');
        }
        $village_user_bind['village_id'] = $village_id;
        $village_user_bind['type'] = 4;
        $village_user_bind['phone'] = $phone;
        $bind_info = $service_house_village_user_bind->getBindInfo($village_user_bind);
        if(empty($bind_info)){
            return api_output_error(1001,'你不是本小区的工作的人员,请联系管理员添加!');
        }
        $house_worker = $service_house_worker->getOneWorker(array('village_id'=>$bind_info['village_id'],'phone'=>$bind_info['phone'],'status'=>1,'is_del'=>0));
        if(empty($house_worker)){
            return api_output_error(1001,'你不是本小区的工作的人员,请联系管理员添加!');
        }
        $arr = [];
        //查询单元信息 小区信息
        $find_village_floor = $service_house_village->getHouseVillageFloorInfo(['floor_id'=>$layer['floor_id']],'village_id,floor_name,floor_layer');
        $find_village = $service_house_village->getHouseVillageInfo(['village_id'=>$find_village_floor['village_id']] , 'village_name,village_address,property_id');
        $service_house_new_property = new HouseNewPorpertyService();
        $is_new_version = $service_house_new_property->getTakeEffectTimeJudge($find_village['property_id']);
        $find_info = array(
            'village_name'    =>  $find_village['village_name'],
            'village_address' =>  $find_village['village_address'],
            'floor_name'      =>  $find_village_floor['floor_name'],
            'floor_layer'     =>  $find_village_floor['floor_layer'],
            'village_id'     =>  $find_village_floor['village_id']
        );
        $arr['find_info'] = $find_info;

        $vacancy_where[] = ['layer_id','=',$layer_id];
        //$vacancy_where[] = ['uid','<>',0];
        $vacancy_list = $service_house_village_user_vacancy->getVacancyList($vacancy_where , true , 'sort desc, pigcms_id asc');
        $dataList = [];
        if($vacancy_list){
            $total_page = count($vacancy_list);
        }else{
            $total_page = 0;
        }
        $where=array();
        $service_house_new_meter = new HouseNewMeterService();
        $where[] = ['p.village_id','=',$village_id];
        $where[] = ['p.status','=',1];
        $where[] = ['c.charge_type','in',['water','electric','gas']];
        $meterProjectDataObj = $service_house_new_meter->getMeterProject($where,'p.name as cate_name,p.village_id,c.charge_number_name as subject_name,charge_type,p.id,p.mday',0,0,'p.id DESC');
        $meterProjectData=array();
        $nowtime=time();
        $service_house_new_charge_rule = new HouseNewChargeRuleService();
        if($meterProjectDataObj && !$meterProjectDataObj->isEmpty()){
            $meterProjectData=$meterProjectDataObj->toArray();
            if($meterProjectData){
                foreach ($meterProjectData as $kk=>$vv){
                    if($vv['mday']<1 || $vv['mday']>30){
                        unset($meterProjectData[$kk]);
                        continue;
                    }
                    $month=date('Y-m');
                    $mday_time=$month.'-'.$vv['mday'].' 00:00:01';
                    //$opt_meter_time=strtotime($mday_time);
                    $meterProjectData[$kk]['opt_meter_time']=strtotime($mday_time);
                    $rule_id = $service_house_new_charge_rule->getValidChargeRule($vv['id']);
                    if(!$rule_id){
                        unset($meterProjectData[$kk]);
                        continue;
                    }
                    $meterProjectData[$kk]['rule_id']=$rule_id;
                }
                if($meterProjectData){
                    $meterProjectData=array_values($meterProjectData);
                }else{
                    $meterProjectData=array();
                }
            }
        }
        foreach($vacancy_list as $k=>$v){
            $list=array();
            $list['page'] = $k+1;
            $list['room_id'] = $v['pigcms_id'];
            $list['floor_id'] = $v['floor_id'];
            $list['village_id'] = $v['village_id'];
            $list['layer'] = $v['layer'];
            $list['layer_id'] = $v['layer_id'];
            $list['room'] = $v['room'];
            $list['pigcms_id'] = $v['pigcms_id'];
            $list['room_info'] = $v['room'];
            $vacancy_id=$v['pigcms_id'];
            if($is_new_version){
                if($meterProjectData){
                    foreach ($meterProjectData as $kk=>$vv){
                        $bindInfo=$service_house_new_meter->getIsBind(['project_id'=>$vv['id'],'vacancy_id'=>$vacancy_id,'rule_id'=>$vv['rule_id'],'is_del'=>1]);
                        if(!empty($bindInfo)){
                            $list['status'] = 2;
                            $list['statusDesc'] = '已抄完';
                            $whereArr=array(array('project_id','=',$vv['id']),array('layer_num','=',$vacancy_id));
                            $whereArr[]=array('add_time','>',$vv['opt_meter_time']);
                            $oneMeterReading=$service_house_new_meter->getOneMeterReading($whereArr,'id DESC',true);
                            if(empty($oneMeterReading)){
                                $list['status'] = 1;
                                $list['statusDesc'] = '未抄完';
                            }
                        }
                    }
                }
            } else {
                //类别
                $read_person = $service_meter_read_person->getMeterReadPersonList(array('uid' => $house_worker['wid'], 'village_id' => $find_village_floor['village_id'], 'status' => 1));
                if (empty($read_person)) {
                    return api_output_error(1001, '移动抄表负责人不存在,请管理员进入移动抄表后台配置');
                }
                $read_persons = array();
                foreach ($read_person as $k1 => $v1) {
                    $read_persons[] = $v1['cate_id'];
                }
                $water = $service_meter_read_person->getMeterReadCate([['id', 'in', $read_persons], ['village_id', '=', $v['village_id']], ['cate_name', '=', '水费']]);
                $electric = $service_meter_read_person->getMeterReadCate([['id', 'in', $read_persons], ['village_id', '=', $v['village_id']], ['cate_name', '=', '电费']]);
                $gas = $service_meter_read_person->getMeterReadCate([['id', 'in', $read_persons], ['village_id', '=', $v['village_id']], ['cate_name', '=', '燃气费']]);

                $water_data['village_id'] = $electric_data['village_id'] = $gas_data['village_id'] = $v['village_id'];
                $water_data['floor_id'] = $electric_data['floor_id'] = $gas_data['floor_id'] = $v['floor_id'];
                $water_data['room_id'] = $electric_data['room_id'] = $gas_data['room_id'] = $v['pigcms_id'];

                $water_data['cate_id'] = $water['id'];
                $electric_data['cate_id'] = $electric['id'];
                $gas_data['cate_id'] = $gas['id'];

                $water_record = $service_meter_read_person->getMeterReadRecord($water_data);
                $electric_record = $service_meter_read_person->getMeterReadRecord($electric_data);
                $gas_record = $service_meter_read_person->getMeterReadRecord($gas_data);
                if ($water_record && $electric_record && $gas_record) {
                    if (date('d', $water_record['add_time']) >= $water['cycle_time']) {

                        if (date('d', $electric_record['add_time']) >= $electric['cycle_time']) {

                            if (date('d', $gas_record['add_time']) >= $gas['cycle_time']) {
                                $list['status'] = 2;
                                $list['statusDesc'] = '已抄完';
                            } else {
                                $list['status'] = 1;
                                $list['statusDesc'] = '未抄完';
                            }
                        } else {
                            $list['status'] = 1;
                            $list['statusDesc'] = '未抄完';
                        }
                    } else {
                        $list['status'] = 1;
                        $list['statusDesc'] = '未抄完';
                    }

                } elseif ($water_record && $electric_record) {
                    if (date('d', $water_record['add_time']) >= $water['cycle_time']) {
                        if (date('d', $electric_record['add_time']) >= $electric['cycle_time']) {
                            $cat_list = $service_meter_read_person->getMeterReadCateList([['village_id', '=', $v['village_id']], ['cate_name', '=', '燃气费']], 'id');
                            if ($cat_list) {
                                foreach ($cat_list as $k_1 => $v_1) {
                                    $room_list = $service_meter_read_person->getMeterReadProject(['village_id' => $village_id, 'cate_id' => $v_1['id']], 'room_id');
                                    if (in_array($v['pigcms_id'], explode(',', $room_list['room_id']))) {
                                        $list['status'] = 1;
                                        $list['statusDesc'] = '未抄完';
                                        break;
                                    } else {
                                        $list['status'] = 2;
                                        $list['statusDesc'] = '已抄完';
                                    }
                                }
                            } else {
                                $list['status'] = 2;
                                $list['statusDesc'] = '已抄完';
                            }
                        } else {
                            $list['status'] = 1;
                            $list['statusDesc'] = '未抄完';
                        }
                    } else {
                        $list['status'] = 1;
                        $list['statusDesc'] = '未抄完';
                    }
                } elseif ($water_record && $gas_record) {
                    if (date('d', $water_record['add_time']) >= $water['cycle_time']) {
                        if (date('d', $gas_record['add_time']) >= $gas['cycle_time']) {
                            $cat_list = $service_meter_read_person->getMeterReadCateList([['village_id', '=', $v['village_id']], ['cate_name', '=', '电费']], 'id');
                            if ($cat_list) {
                                foreach ($cat_list as $k_1 => $v_1) {
                                    $room_list = $service_meter_read_person->getMeterReadProject(['village_id' => $village_id, 'cate_id' => $v_1['id']], 'room_id');
                                    if (in_array($v['pigcms_id'], explode(',', $room_list['room_id']))) {
                                        $list['status'] = 1;
                                        $list['statusDesc'] = '未抄完';
                                        break;
                                    } else {
                                        $list['status'] = 2;
                                        $list['statusDesc'] = '已抄完';
                                    }
                                }
                            } else {
                                $list['status'] = 2;
                                $list['statusDesc'] = '已抄完';
                            }
                        } else {
                            $list['status'] = 1;
                            $list['statusDesc'] = '未抄完';
                        }
                    } else {
                        $list['status'] = 1;
                        $list['statusDesc'] = '未抄完';
                    }
                } elseif ($gas_record && $electric_record) {
                    if (date('d', $electric_record['add_time']) >= $electric['cycle_time']) {
                        if (date('d', $gas_record['add_time']) >= $gas['cycle_time']) {
                            $cat_list = $service_meter_read_person->getMeterReadCateList([['village_id', '=', $v['village_id']], ['cate_name', '=', '水费']], 'id');
                            if ($cat_list) {
                                foreach ($cat_list as $k_1 => $v_1) {
                                    $room_list = $service_meter_read_person->getMeterReadProject(['village_id' => $village_id, 'cate_id' => $v_1['id']], 'room_id');
                                    if (in_array($v['pigcms_id'], explode(',', $room_list['room_id']))) {
                                        $list['status'] = 1;
                                        $list['statusDesc'] = '未抄完';
                                        break;
                                    } else {
                                        $list['status'] = 2;
                                        $list['statusDesc'] = '已抄完';
                                    }
                                }
                            } else {
                                $list['status'] = 2;
                                $list['statusDesc'] = '已抄完';
                            }
                        } else {
                            $list['status'] = 1;
                            $list['statusDesc'] = '未抄完';
                        }
                    } else {
                        $list['status'] = 1;
                        $list['statusDesc'] = '未抄完';
                    }
                } else {
                    if ($gas_record) {
                        if (date('d', $gas_record['add_time']) >= $gas['cycle_time']) {
                            $cat_list = $service_meter_read_person->getMeterReadCateList([['village_id', '=', $v['village_id']], ['cate_name', 'in', ['电费', '水费']]], 'id');
                            if ($cat_list) {
                                foreach ($cat_list as $k_1 => $v_1) {
                                    $room_list = $service_meter_read_person->getMeterReadProject(['village_id' => $village_id, 'cate_id' => $v_1['id']], 'room_id');
                                    if (in_array($v['pigcms_id'], explode(',', $room_list['room_id']))) {
                                        $list['status'] = 1;
                                        $list['statusDesc'] = '未抄完';
                                        break;
                                    } else {
                                        $list['status'] = 2;
                                        $list['statusDesc'] = '已抄完';
                                    }
                                }
                            } else {
                                $list['status'] = 2;
                                $list['statusDesc'] = '已抄完';
                            }
                        } else {
                            $list['status'] = 1;
                            $list['statusDesc'] = '未抄完';
                        }
                    } elseif ($electric_record) {
                        if (date('d', $electric_record['add_time']) >= $electric['cycle_time']) {
                            $cat_list = $service_meter_read_person->getMeterReadCateList([['village_id', '=', $v['village_id']], ['cate_name', 'in', ['燃气费', '水费']]], 'id');
                            if ($cat_list) {
                                foreach ($cat_list as $k_1 => $v_1) {
                                    $room_list = $service_meter_read_person->getMeterReadProject(['village_id' => $village_id, 'cate_id' => $v_1['id']], 'room_id');
                                    if (in_array($v['pigcms_id'], explode(',', $room_list['room_id']))) {
                                        $list['status'] = 1;
                                        $list['statusDesc'] = '未抄完';
                                        break;
                                    } else {
                                        $list['status'] = 2;
                                        $list['statusDesc'] = '已抄完';
                                    }
                                }
                            } else {
                                $list['status'] = 2;
                                $list['statusDesc'] = '已抄完';
                            }
                        } else {
                            $list['status'] = 1;
                            $list['statusDesc'] = '未抄完';
                        }
                    } elseif ($water_record) {
                        if (date('d', $water_record['add_time']) >= $water['cycle_time']) {
                            $cat_list = $service_meter_read_person->getMeterReadCateList([['village_id', '=', $v['village_id']], ['cate_name', 'in', ['电费', '燃气费']]], 'id');
                            if ($cat_list) {
                                foreach ($cat_list as $k_1 => $v_1) {
                                    $room_list = $service_meter_read_person->getMeterReadProject(['village_id' => $village_id, 'cate_id' => $v_1['id']], 'room_id');
                                    if (in_array($v['pigcms_id'], explode(',', $room_list['room_id']))) {
                                        $list['status'] = 1;
                                        $list['statusDesc'] = '未抄完';
                                        break;
                                    } else {
                                        $list['status'] = 2;
                                        $list['statusDesc'] = '已抄完';
                                    }
                                }
                            } else {
                                $list['status'] = 2;
                                $list['statusDesc'] = '已抄完';
                            }
                        } else {
                            $list['status'] = 1;
                            $list['statusDesc'] = '未抄完';
                        }
                    } else {
                        $list['status'] = 1;
                        $list['statusDesc'] = '未抄完';
                    }
                }
            }
            $dataList[] = $list;
        }
        $arr['vacancy_list'] = $dataList;
        $arr['total_page'] = $total_page;
        $arr['is_new_version'] = false;
        return api_output(0,$arr);
    }

    /**
     * 获取对应小区楼栋信息
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/6 14:31
     * @return \json
     */
    public function villageSingleList() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        $site_url = cfg('site_url');
        //小区信息
        $service_house_village = new HouseVillageService();
        $now_village = $service_house_village->getHouseVillage($village_id,'village_id,village_name,village_address,village_logo');
        $arr = [];
        $arr['village_id'] = $now_village['village_id'];
        $arr['village_name'] = $now_village['village_name'];
        $arr['village_address'] = $now_village['village_address'];
        $arr['village_logo'] = $now_village['village_logo'] ? $now_village['village_logo'] : $site_url.'/static/images/wap_house/village_logo.png';


        $where = [];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['status', '=', 1];

        $list = $service_house_village->getSingleList($where,true,'sort DESC,id desc');
        $dataList = [];
        foreach($list as $k=>$v){
            $item = [];
            $item['village_id'] = $v['village_id'];
            $item['single_name'] = $v['single_name'];
            $item['single_id'] = $v['id'];
            $dataList[] = $item;
        }
        $arr['list'] = $dataList;
        return api_output(0,$arr);
    }

    /**
     * 获取对应小区楼栋下单元信息
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'single_id'=> '楼栋id 必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/6 15:23
     * @return \json
     */
    public function villageFloorList() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        $single_id = $this->request->param('single_id','','intval');
        if (empty($single_id)) {
            return api_output(1001,[],'缺少对应楼栋！');
        }
        $site_url = cfg('site_url');
        //小区信息
        $service_house_village = new HouseVillageService();
        $now_village = $service_house_village->getHouseVillage($village_id,'village_id,village_name,village_address,village_logo');
        $arr = [];
        $arr['village_id'] = $now_village['village_id'];
        $arr['village_name'] = $now_village['village_name'];
        $arr['village_address'] = $now_village['village_address'];
        $arr['village_logo'] = $now_village['village_logo'] ? $now_village['village_logo'] : $site_url.'/static/images/wap_house/village_logo.png';


        $where = [];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['single_id', '=', $single_id];
        $where[] = ['status', '=', 1];

        $list = $service_house_village->getFloorList($where,true,'sort DESC,floor_id desc');
        $dataList = [];
        foreach($list as $k=>$v){
            $item = [];
            $item['village_id'] = $v['village_id'];
            $item['floor_id'] = $v['floor_id'];
            $item['floor_name'] = $v['floor_name'];
            $item['floor_layer'] = $v['floor_layer'];
            $dataList[] = $item;
        }
        $arr['list'] = $dataList;
        return api_output(0,$arr);
    }

    /**
     * 获取对应小区楼栋单元下信息
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'floor_id'=> '单元id 必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/6/12 17:38
     * @return \json
     */
    public function villageLayerList() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        $floor_id = $this->request->param('floor_id','','intval');
        if (empty($floor_id)) {
            return api_output(1001,[],'缺少对应单元！');
        }
        $service_house_village = new HouseVillageService();
        //小区信息
        $now_village = $service_house_village->getHouseVillage($village_id,'village_id,village_name,village_address,village_logo');
        // 单元信息
        $where_floor = [];
        $where_floor[] = ['floor_id', '=', $floor_id];
        $now_floor_info = $service_house_village->getHouseVillageFloorWhere($where_floor,'floor_name,floor_layer,single_id');
        // 楼栋信息
        $where_single = [];
        $where_single[] = ['id', '=', $now_floor_info['single_id']];
        $single_info = $service_house_village->get_house_village_single_where($where_single,'single_name');

        $arr = [
            'village_name'    =>  $now_village['village_name'],
            'village_address' =>  $now_village['village_address'],
            'single_name'      =>  $single_info['single_name'],
            'floor_name'      =>  $now_floor_info['floor_name'],
            'floor_layer'     =>  $now_floor_info['floor_layer'],
            'village_id'     =>  $now_village['village_id']
        ];

        $where = [];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['floor_id', '=', $floor_id];
        $where[] = ['status', '=', 1];

        $list = $service_house_village->getHouseVillageLayerList($where,true,'sort DESC,id desc');
        $dataList = [];
        foreach($list as $k=>$v){
            $item = [];
            $item['layer_id'] = $v['id'];
            $item['single_id'] = $v['single_id'];
            $item['floor_id'] = $v['floor_id'];
            $item['village_id'] = $v['village_id'];
            $item['layer'] = $v['layer_name'];
            $dataList[] = $item;
        }
        $arr['list'] = $dataList;
        return api_output(0,$arr);
    }


    /**
     * 获取上一次抄表止度
     */
    public function getLastMeter()
    {
        $project_id = $this->request->post('project_id',0);
        $vacancy_id = $this->request->post('vacancy_id',0);
        $meter_id = $this->request->post('meter_id',0);
        $service_house_new_meter = new HouseNewMeterService();
        try{
            $where = ['project_id'=>$project_id,'layer_num'=>$vacancy_id];
            if($meter_id){
                $where['meter_id'] = $meter_id;
            }
            $data = $service_house_new_meter->getMeterRecordInfo($where,'last_ammeter','id DESC');
            $jz_wsxdsj_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
            if(isset($data['last_ammeter']) && $data['last_ammeter'] > 0 && $jz_wsxdsj_customized_open && $meter_id){
                //查询水电表的最大刻度，如果大于0需要处理一下
                $meterInfo = (new HouseVillageRoomCustomizedMeters())->getOneData(['id'=>$meter_id],'meter_max_v');
                $max = (isset($meterInfo['meter_max_v']) && $meterInfo['meter_max_v']>0) ? $meterInfo['meter_max_v'] : 0;
                $data['last_ammeter'] = $max ? fmod(floatval($data['last_ammeter']), $max) : $data['last_ammeter'];
            }else{
                $data['last_ammeter'] = 0.00;
            }
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }
}