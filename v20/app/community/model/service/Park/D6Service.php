<?php
/**
 * @author : liukezhu
 * @date : 2022/3/10
 */

namespace app\community\model\service\Park;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageCarAccessRecord;
use app\community\model\db\HouseVillageParkConfig;
use app\community\model\db\HouseVillageParkingCar;
use app\community\model\db\HouseVillageParkingGarage;
use app\community\model\db\HouseVillageParkingTemp;
use app\community\model\db\ParkD6RequestRecord;
use app\community\model\db\ProcessSubPlan;
use app\community\model\service\HouseVillageParkGarageConfigService;
use socket\GatewayJdk;
use think\facade\Cache;

class D6Service
{
    protected $time;
    protected $village_id=0;
    protected $HouseVillageParkConfig;
    protected $HouseVillageParkingGarage;
    protected $HouseVillageParkingCar;
    protected $HouseVillage;
    protected $ParkD6RequestRecord;
    protected $HouseVillageCarAccessRecord;
    protected $HouseVillageParkingTemp;

    public function __construct(){
        $this->time=time();
        $this->HouseVillageParkConfig = new HouseVillageParkConfig();
        $this->HouseVillageParkingGarage = new HouseVillageParkingGarage();
        $this->HouseVillageParkingCar = new HouseVillageParkingCar();
        $this->HouseVillage = new HouseVillage();
        $this->ParkD6RequestRecord = new ParkD6RequestRecord();
        $this->HouseVillageCarAccessRecord = new HouseVillageCarAccessRecord();
        $this->HouseVillageParkingTemp=new HouseVillageParkingTemp();
    }


    //todo 校验小区配置参数合法性
    private function d6Check($village_id){
        $village_info=$this->HouseVillage->getOne($village_id,'village_id,village_address');
        if (!$village_info || $village_info->isEmpty()){
            return ['error'=>false,'msg'=>'小区不存在','data'=>[]];
        }
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $whereArr=array(['village_id','=',$village_id], ['park_sys_type','=','D6']);
        $village_config=$houseVillageParkGarageConfigService->getVillageParkConfigByWhere($whereArr,0,'village_id,d6_comid,d6_client_id');
        /*
        $village_config=$this->HouseVillageParkConfig->getFind([
            ['village_id','=',$village_id],
            ['park_sys_type','=','D6'],
        ],'village_id,d6_comid,d6_client_id');
        */
        if (empty($village_config)){
            return ['error'=>false,'msg'=>'请在小区后台=>停车设置=>停车设备类型选择【D6智慧停车】并保存','data'=>[]];
        }
        $village_config['village_address']=$village_info['village_address'];
        return ['error'=>true,'msg'=>'ok','data'=>$village_config];
    }

    //todo 校验组装车牌
    public function checkCarNumber($province='',$car_number=''){
        $str=$car_number;
        if(!empty($province) && !empty($car_number)){
            if(strpos($car_number, $province) !== false){
                $str=$car_number;
            }else{
                $str=$province.$car_number;
            }
        }
        return $str;
    }

    //todo 解析车牌
    public function getCarNumber($car_number){
        if(preg_match('/[\x7f-\xff]/', $car_number)){
            return [
                'province'=>mb_substr($car_number,0,1),
                'car_number'=>mb_substr($car_number,1)
            ];
        }
        else{
            return [
                'province'=>'',
                'car_number'=>$car_number
            ];
        }
    }

    //todo 组装命令写入
    private function assembleData($list,$data,$param){
        $this->checkOperation($list['village_id'],$list['car_id'],$param['cmd_name'],$param['car_number']);
        $command=[
            'parking_query'=>5005,
            'car_query'=>5007,
            'charging_query'=>5008,
            'charging_result'=>5009
        ];
        $rr=[
            'car_id'        =>  intval($list['car_id']),
            'car_number'    =>  $param['car_number'],
            'village_id'    =>  $list['village_id'],
            'comid'         =>  $list['d6_comid'],
            'cmd_name'      =>  $param['cmd_name'],
            'version'       =>  '1.0',
            'status'        =>  0,
            'add_time'      =>  $this->time,
            'device_ip'     =>  get_client_ip(),
            'temp_id'       =>  isset($list['temp_id']) ? $list['temp_id'] : 0,
            'sort'          =>  $param['sort'],
            'data'          =>  serialize([
                            'command'=>$command[$param['cmd_name']],
                            'requestid'=>'1',
                            'version'=>'1.0',
                            'timestamp'=>str_pad($this->time,13,"0",STR_PAD_RIGHT),
                            'data'=>$data
            ])
        ];
        $result=$this->ParkD6RequestRecord->add($rr);
        if($result){
            return ['error'=>true,'msg'=>'下发成功'];
        }
        else{
            return ['error'=>false,'msg'=>'下发失败'];
        }
    }

    //todo 查询车辆数据
    private function getParkingCar($where,$field=true){
        $info=$this->HouseVillageParkingCar->getOne($where,$field);
        if($info && !$info->isEmpty()){
            return $info->toArray();
        }else{
            return [];
        }
    }

    //todo 查询集合数据
    private function getList($village_id,$where,$field,$param=[]){
        $village_config=$this->d6Check($village_id);
        if(!$village_config['error']){
            return $village_config;
        }
        $list=$this->getParkingCar($where,$field);
        if(!$list){
            if(!empty($param)){
                $lists=$this->getTemporaryCar($param['village_id'],$param['province'],$param['car_number']);
                if(!$lists['error']){
                    return $lists;
                }
                $list=$lists['data'];
            }else{
                return ['error'=>false,'msg'=>'该车辆数据不存在'];
            }
        }
        $list['d6_comid']=$village_config['data']['d6_comid'];
        $list['d6_client_id']=$village_config['data']['d6_client_id'];
        return ['error'=>true,'msg'=>'ok','data'=>$list];
    }

    //todo 临时车获取进场数据
    public function getTemporaryCar($village_id,$province,$car_number){
        $where=[];
        $where[] = ['business_id','=',$village_id];
        $where[] = ['business_type','=',0];
        $where[] = ['car_number','=',$this->checkCarNumber($province,$car_number)];
        $where[] = ['is_out','=',0];
        fdump_api(['临时车获取进场数据=='.__LINE__,$village_id,$province,$car_number,$where],'d6_park/getTemporaryCar',1);
        $result=$this->HouseVillageCarAccessRecord->getOne($where,'record_id');
        if($result && !$result->isEmpty()){
            return ['error'=>true,'msg'=>'ok','data'=>['car_id'=>0,'province'=>$province,'village_id'=>$village_id]];
        }else{
            return ['error'=>false,'msg'=>'该车辆无进场数据，暂无法出场','data'=>[]];
        }
    }

    //todo 获取车辆数据
    public function getCar($village_id,$car_number,$is_temporary=0){ //$is_temporary == 1 针对临时车处理
        $data=$this->getCarNumber($car_number);
        $where=$param=[];
        $where[] = ['car_type','=',0];
        $where[] = ['village_id','=',$village_id];
        $where[] = ['province','=',$data['province']];
        $where[] = ['car_number','=',$data['car_number']];
        if($is_temporary == 1){
            $param=[
                'village_id'=>$village_id,
                'province'=>$data['province'],
                'car_number'=>$data['car_number']
            ];
        }
        return $this->getList($village_id,$where,'car_id,province,village_id',$param);
    }

    //todo 同步下发单个车辆
    public function d6SynSingle($param){
        $where=[
            ['c.village_id','=',$param['village_id']],
            ['c.car_id','=',$param['car_id']],
            ['c.car_type','=',0],
            ['c.examine_status','=',1],
        ];
        $field='c.car_id,c.village_id,c.province,c.car_number,c.car_user_name,c.car_user_phone,c.end_time,p.position_num,u.name,u.phone';
        $list=$this->HouseVillageParkingCar->get_list($where,$field);
        if (!$list || $list->isEmpty()){
            fdump_api(['暂无数据下发=='.__LINE__,$param,$where],'d6_park/d6synVehicle_error',1);
            return ['error'=>false,'msg'=>'暂无数据下发'];
        }
        $data=[];
        $bgnTime=$this->time;
        foreach ($list as $v){
            $record=$this->ParkD6RequestRecord->getOne([
                ['car_id','=',$v['car_id']],
                ['cmd_name','in',['car_add','car_edit']],
                ['status','=',1],
                ['state','=',1]
            ],'car_id');
            if(isset($record['car_id']) && !empty($record['car_id'])){
                $cmd_name='car_edit';
                $oper=2;
            }
            else{
                $cmd_name='car_add';
                $oper=1;
            }
            $end_time=$v['end_time'];
            if(isset($param['d6_end_time']) && !empty($param['d6_end_time'])){
                $end_time=$param['d6_end_time'];
            }
            if($param['is_del'] == 1){
                if($oper == 1){
                    continue;
                }else{
                    $cmd_name='car_del';
                    $oper=3;
                }
            }
            $endTime=($bgnTime > $end_time) ? (mktime(23,59,59,date("m"),date("d"),date("Y"))) : $end_time;
            $data[]=[
                'car_id'        =>  $v['car_id'],
                'village_id'    =>  $v['village_id'],
                'comid'         =>  $param['d6_comid'],
                'cmd_name'      =>  $cmd_name,
                'version'       =>  '1.0',
                'status'        =>  0,
                'add_time'      =>  $this->time,
                'device_ip'     =>  get_client_ip(),
                'sort'          =>  90,
                'data'          =>  serialize([
                    'command'   => 5006,
                    'requestid' => '1',
                    'version'   => '1.0',
                    'timestamp' => str_pad($this->time,13,"0",STR_PAD_RIGHT),
                    'data'      =>[
                        'plate'     =>  $this->checkCarNumber($v['province'],$v['car_number']),
                        'name'      =>  $v['name'],
                        'tel'       =>  $v['phone'],
                        'address'   =>  $param['village_address'],
                        'bgnTime'   =>  date('Y-m-d',$bgnTime),
                        'endTime'   =>  date('Y-m-d',$endTime),
                        'fixplace'  =>  $v['position_num'],
                        'memo'      =>  '车辆数据下发到设备',
                        'oper'      =>  $oper,
                        'dataTime'  =>  str_pad($this->time,13,"0",STR_PAD_RIGHT)
                    ]
                ])
            ];
            $this->checkOperation($v['village_id'],$v['car_id'],$cmd_name);
        }
        fdump_api(['暂无数据下发=='.__LINE__,$param,$where,$data],'d6_park/d6synVehicle',1);
        if(empty($data)){
            return ['error'=>false,'msg'=>'暂无数据'];
        }
        $result=$this->ParkD6RequestRecord->addAll($data);
        if($result){
            return ['error'=>true,'msg'=>'下发成功'];
        }
        else{
            return ['error'=>false,'msg'=>'下发失败'];
        }
    }

    //todo 5、车场记录查询5005 (平台 -> 车场)
    public function d6QueryRecord($village_id,$car_number,$start_time='',$end_time='',$pay_type){
        $list=$this->getCar($village_id,$car_number);
        if(!$list['error']){
            return $list;
        }
        $plate= $this->checkCarNumber($list['data']['province'],$car_number);
        return $this->assembleData($list['data'],[
            'plate'     => $plate,
            'bgnTime'   =>  $start_time ? str_pad($start_time,13,"0",STR_PAD_RIGHT) : '',
            'endTime'   =>  $end_time ? str_pad($end_time,13,"0",STR_PAD_RIGHT) : '',
            'payType'   =>  $pay_type
        ],['cmd_name'=>'parking_query','sort'=>30,'car_number'=>$plate]);
    }

    //todo 6、固定车辆信息更新 5006 (平台 ->车场)： 新增、编辑、删除车辆数据    同步车辆到D6停车设备
    public function d6synVehicle($village_id,$car_id=0,$is_del=0,$park_set=[]){
        $village_config=$this->d6Check($village_id);
        if(!$village_config['error']){
            fdump_api(['固定车辆信息更新=='.__LINE__,$village_id,$car_id,$is_del,$park_set,$village_config],'d6_park/d6synVehicle_error',1);
            return $village_config;
        }
        $village_config=$village_config['data'];
        $where = [];
        $where[] = ['car_type','=',0];
        $where[] = ['examine_status','=',1];
        $where[] = ['village_id','=',$village_id];
        if($car_id > 0){
            $where[] = ['car_id','=',$car_id];
        }
        $field='car_id,village_id';
        $list=$this->HouseVillageParkingCar->getHouseVillageParkingCarLists($where,$field,0);
        if (!$list || $list->isEmpty()){
            fdump_api(['暂无数据下发=='.__LINE__,$village_id,$car_id,$is_del,$park_set,$village_config,$where],'d6_park/d6synVehicle_error',1);
            return ['error'=>false,'msg'=>'暂无数据下发'];
        }
        $data=[];
        foreach ($list as $v){
            $param=[
                'village_id'=>$v['village_id'],
                'car_id'=>$v['car_id'],
                'is_del'=>$is_del,
                'd6_comid'=>$village_config['d6_comid'],
                'village_address'=>$village_config['village_address'],
            ];
            if(isset($park_set['end_time']) && !empty($park_set['end_time'])){
                $param['d6_end_time']=$park_set['end_time'];
            }
            if($car_id > 0){ //单个车辆 直接同步
                $this->d6SynSingle($param);
            }else{ //多个车辆 走计划任务
                $param['type']='d6_syn_single_car';
                $data[]=[
                    'param'         =>  serialize($param),
                    'plan_time'     =>  -130,
                    'space_time'    =>  0,
                    'add_time'      =>  $this->time,
                    'file'          =>  'sub_d5_park',
                    'time_type'     =>  1,
                    'unique_id'     =>  'd6_syn_single_car_'.$v['village_id'].'_'.$this->time.'_'.uniqid(),
                    'rand_number'   =>  mt_rand(1, max(cfg('sub_process_num'), 3)),
                ];
            }
        }
        if($data){
            (new ProcessSubPlan())->addAll($data);
        }
        return ['error'=>true,'msg'=>'数据下发成功'];
    }

    //todo 7、固定车辆信息查询5007 (平台 ->车场)；  查询单个车辆数据
    public function d6QueryVehicle($village_id,$car_number,$name='',$fixplace=''){
        $list=$this->getCar($village_id,$car_number);
        if(!$list['error']){
           return $list;
        }
        $plate=$this->checkCarNumber($list['data']['province'],$car_number);
        return $this->assembleData($list['data'],[
            'plate'  =>  $plate,
            'name'   =>  $name,
            'fixplace'  => $fixplace,
        ],['cmd_name'=>'car_query','sort'=>50,'car_number'=>$plate]);
    }

    //todo 8、计费信息查询 5008 (平台->车场)；      查询单个车牌计费数据
    public function d6ChargingQuery($village_id,$car_number,$time,$temp_id=0){
        $list=$this->getCar($village_id,$car_number,1);
        if(!$list['error']){
            return $list;
        }
        $d6_comid=$list['data']['d6_comid'];
        $client_id=$list['data']['d6_client_id'];
        if(empty($client_id)){
            return ['error'=>false,'msg'=>'设备不存在，请联系车场'.PHP_EOL.'[车场ID：'.$d6_comid.']','data'=>[]];
        }
        $check=$this->d6Send($client_id);
        if(!$check['status']){
            return ['error'=>false,'msg'=>'设备不在线，请联系车场'.PHP_EOL.'[车场ID：'.$d6_comid.']','data'=>[]];
        }
        $list['data']['temp_id']=$temp_id;
        $plate=$this->checkCarNumber($list['data']['province'],$car_number);
        return $this->assembleData($list['data'],[
            'carnumber'  =>  $plate,
            'dataTime'  =>  str_pad($time,13,"0",STR_PAD_RIGHT)
        ],['cmd_name'=>'charging_query','sort'=>80,'car_number'=>$plate]);
    }

    /**
     * todo 9、计费结果通知 5009 (平台 -> 车场)；    车辆支付成功 通知设备
     * @author: liukezhu
     * @date : 2022/3/18
     * @param $temp_param house_village_parking_temp表 返回的数据
     * @param $pay_price 支付金额 单位：元
     * @param $pay_type 支付方式 1：微信 2：支付宝 3：银联 4：现金 5：其它
     * @param $pay_time 支付时间 时间戳
     * @return array
     */
    public function d6ChargingResult($temp_param,$pay_price,$pay_type,$pay_time){
        $param=[
            'village_id'=>$temp_param['village_id'],
            'orderId'=>$temp_param['order_id'],
            'plate'=>$temp_param['car_number'],
            'vehicleType'=>$temp_param['vehicle_type'],
        ];
        $list=$this->getCar($param['village_id'],$param['plate'],1);
        if(!$list['error']){
            fdump_api(['计费结果通知=='.__LINE__,$temp_param,$pay_price,$pay_type,$pay_time,$param],'d6_park/d6ChargingResult_error',1);
            return $list;
        }
        $plate=$this->checkCarNumber($list['data']['province'],$param['plate']);
        return $this->assembleData($list['data'],[
            'orderId'       =>  $param['orderId'],
            'plate'         =>  $plate,
            'vehicleType'   =>  (int)$param['vehicleType'],
            'payMoney'      =>  ($pay_price * 100),
            'payStatus'     =>  1,
            'payType'       =>  $pay_type,
            'payTime'       =>  (int)str_pad($pay_time,13,"0",STR_PAD_RIGHT),
            'remark'        =>  '支付成功',
            'dataTime'      =>  (int)str_pad($this->time,13,"0",STR_PAD_RIGHT)
        ],['cmd_name'=>'charging_result','sort'=>70,'car_number'=>$plate]);
    }

    //todo 处理已存在相同操作相同命令
    public function checkOperation($village_id,$car_id=0,$cmd_name,$car_number=''){
        $where=[
            ['village_id','=',$village_id],
            ['cmd_name','=',$cmd_name],
            ['status','=',0]
        ];
        if(!empty($car_id)){
            $where[]=['car_id','=',$car_id];
        }
        if(!empty($car_number)){
            $where[]=['car_number','=',$car_number];
        }
        return $this->ParkD6RequestRecord->edit($where,['status'=>4,'remark'=>'相同命令覆盖，已失效']);
    }

    //todo 查询设备是否在线
    public function d6Send($client_id){
        return (new GatewayJdk())->checkOnline($client_id);
    }

    //todo 下发账单查询命令
    public function addQueryCarCost($village_id,$car_number){
        $data_temp=array(
            'service_name'=>'d6_charging',
            'village_id'=>$village_id,
            'add_time'=>$this->time,
            'state'=>0,
            'status'=>0,
            'is_pay_scene'=>1,
            'out_channel_id'=>0,
            'car_number'=>$car_number,
            'park_sys_type'=>'D6'
        );
        $temp_id=$this->HouseVillageParkingTemp->addOne($data_temp);
        if(!$temp_id){
            return ['error'=>false,'msg'=>'查询失败,请稍后再试','data'=>[]];
        }
        $result=$this->d6ChargingQuery($village_id,$car_number,$data_temp['add_time'],$temp_id);
        if(!$result['error']){
            $this->HouseVillageParkingTemp->delOne(['id'=>$temp_id]);
            return $result;
        }
        return ['error'=>true,'msg'=>'查询下发成功','data'=>['temp_id'=>$temp_id]];
    }

}