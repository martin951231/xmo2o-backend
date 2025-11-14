<?php
/**
 * @author : 合肥快鲸科技有限公司
 * @date : 2022/11/17
 */

namespace app\community\model\service\Park;


use app\common\model\service\image\ImageService;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\HouseNewChargeRule;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageBindCar;
use app\community\model\db\HouseVillageCarAccessRecord;
use app\community\model\db\HouseVillageParkBlack;
use app\community\model\db\HouseVillageParkCharge;
use app\community\model\db\HouseVillageParkChargeCartype;
use app\community\model\db\HouseVillageParkConfig;
use app\community\model\db\HouseVillageParkCoupon;
use app\community\model\db\HouseVillageParkFree;
use app\community\model\db\HouseVillageParkingCar;
use app\community\model\db\HouseVillageParkingCarBindPosition;
use app\community\model\db\HouseVillageParkingGarage;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\db\HouseVillageParkingTemp;
use app\community\model\db\HouseVillageParkShowscreenConfig;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageVisitor;
use app\community\model\db\InPark;
use app\community\model\db\OutPark;
use app\community\model\db\ParkCoupons;
use app\community\model\db\ParkOpenLog;
use app\community\model\db\ParkPassage;
use app\community\model\db\ParkPlateresultLog;
use app\community\model\db\ParkShowscreenLog;
use app\community\model\db\ParkWhiteRecord;
use app\community\model\db\User;
use app\community\model\service\Car\ParkCarToJobService;
use app\community\model\service\HouseNewParkingService;
use app\community\model\service\HouseVillageParkGarageConfigService;
use app\traits\ParkingHouseTraits;
use app\traits\ImageHandleTraits;
use file_handle\FileHandle;
use TencentCloud\Vod\V20180717\Models\EditMediaFileInfo;
use think\Exception;
use think\exception\InvalidArgumentException;

class A11Service
{
    use ParkingHouseTraits;

    protected $park_sys_type;
    protected $time;
    protected $ParkPassage;
    protected $HouseVillageParkingCar;
    protected $HouseVillageParkFree;
    protected $HouseVillageParkConfig;
    protected $ParkWhiteRecord;
    protected $HouseVillageParkBlack;
    protected $HouseVillage;
    public $register_day = 15;
    public $pay_time = 900;
    // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
    public $pay_type=[0=>'cash',1=>'wallet',2=>'sweepcode',3=>'escape',4=>'monthuser',5=>'free',6=>'scancode'];

    //A11停车卡类
    const  Month_A = 1;
    const  Month_B = 2;
    const  Month_C = 3;
    const  Month_D = 4;
    const  Month_E = 5;
    const  Month_F = 6;
    const  Month_G = 7;
    const  Month_H = 8;
    const  Temp_A = 9;
    const  Temp_B = 10;
    const  Temp_C = 11;
    const  Temp_D = 12;
    const  Temp_E = 13;
    const  Temp_F = 14;
    const  Temp_G = 15;
    const  Temp_H = 16;
    const  Stored_A = 17;
    const  Stored_B = 18;
    const  Stored_C = 19;
    const  Stored_D = 20;
    const  Free_A = 21;

    public $parking_a11_car_type_arr  = [
        self::Month_A => '月租车A',
        self::Month_B => '月租车B',
        self::Month_C => '月租车C',
        self::Month_D => '月租车D',
        self::Month_E => '月租车E',
        self::Month_F => '月租车F',
        self::Month_G => '月租车G',
        self::Month_H => '月租车H',
        self::Temp_A => '临时车A',
        self::Temp_B => '临时车B',
        self::Temp_C => '临时车C',
        self::Temp_D => '临时车D',
        self::Temp_E => '临时车E',
        self::Temp_F => '临时车F',
        self::Temp_G => '临时车G',
        self::Temp_H => '临时车H',
        self::Stored_A => '储值车A',
        self::Stored_B => '储值车B',
        self::Stored_C => '储值车C',
        self::Stored_D => '储值车D',
        self::Free_A => '免费车A',
    ];
    //A11月租车卡类
    public $A11_Month=[self::Month_A,self::Month_B,self::Month_C,self::Month_D,self::Month_E,self::Month_F,self::Month_G,self::Month_H];
    //A11临时车卡类
    public $A11_Temp=[self::Temp_A,self::Temp_B,self::Temp_C,self::Temp_D,self::Temp_E,self::Temp_F,self::Temp_G,self::Temp_H];
    //A11储值车卡类
    public $A11_Stored=[self::Stored_A,self::Stored_B,self::Stored_C,self::Stored_D];

    /**
     * @var string [白名单]处理车道 查询车库关系 生成队列
     */
    const HANDLE_BMD_PARK_PASSAGE= 'handle_bmd_park_passage';

    /**
     * @var string [白名单]处理车辆 生成下发数据
     */
    const HANDLE_BMD_PARK_VEHICLE= 'handle_bmd_park_vehicle';

    /**
     * @var string [黑名单]处理车道 生成下发数据
     */
    const HANDLE_HMD_PARK_VEHICLE= 'handle_hmd_park_passage';


    public function __construct($park_sys_type='A11'){
        $this->park_sys_type=$park_sys_type;
        $this->time=time();
        $this->ParkPassage=new ParkPassage();
        $this->HouseVillageParkingCar=new HouseVillageParkingCar();
        $this->HouseVillageParkFree=new HouseVillageParkFree();
        $this->HouseVillageParkConfig = new HouseVillageParkConfig();
        $this->ParkWhiteRecord=new ParkWhiteRecord();
        $this->HouseVillageParkBlack=new HouseVillageParkBlack();
        $this->HouseVillage=new HouseVillage();
    }

    //====================================todo 共用方法 start======================================

    //todo 校验车库配置项
    public function getVillageParkConfigMethod($village_id){
        $park_sys_type=(new HouseNewParkingService())->one_park_sys_type;
        
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $village_id);
        $whereArr[] = array('park_sys_type', '=', 'A11');
        $a11Config = $houseVillageParkGarageConfigService->getVillageParkConfigByWhere($whereArr);
        if(empty($a11Config)){
            return ['error'=>false,'msg'=>'请在小区后台=>人房车管理=>车库管理=>车库功能设置里[停车设备类型]选择【'.$park_sys_type[$this->park_sys_type].'】并提交保存','data'=>[]];
        }
        return ['error'=>true,'msg'=>'查询成功','data'=>[]];
    }

    //todo 获取单个车辆数据
    public function getParkingCarFindMethod($village_id,$car_number,$field=true){
        $where=[
            ['village_id','=',$village_id],
            ['is_del','=',1],
        ];
        $carNumber=(new D6Service())->getCarNumber($car_number);
        if($carNumber['province']){
            $where[]= ['province','=',$carNumber['province']];
        }
        if($carNumber['car_number']){
            $where[]= ['car_number','=',$carNumber['car_number']];
        }
        $info=$this->HouseVillageParkingCar->getOne($where,$field);
        if($info && !$info->isEmpty()){
            $info=$info->toArray();
        }else{
            $info=[];
        }
        return $info;
    }

    //todo 获取单个免费车数据
    public function getParkingFreeFindMethod($village_id,$car_number,$field=true){
        $where=[
            ['village_id','=',$village_id],
            ['park_sys_type','=',$this->park_sys_type],
            ['free_park','=',$car_number],
        ];
        $info=$this->HouseVillageParkFree->getFind($where,$field);
        if($info && !$info->isEmpty()){
            $info=$info->toArray();
        }else{
            $info=[];
        }
        return $info;
    }

    //todo 获取单个黑名单车数据
    public function getParkingVetoFindMethod($village_id,$car_number,$field=true){
        $where=[
            ['village_id','=',$village_id],
            ['car_number','=',$car_number],
        ];
        $info=$this->HouseVillageParkBlack->getFind($where,$field);
        if($info && !$info->isEmpty()){
            $info=$info->toArray();
        }else{
            $info=[];
        }
        return $info;
    }

    //todo 校验白名单相同数据的重复性
    public function checkWhiteRecordMethod($village_id,$channel_id,$car_number,$need_alarm,$operate_type){
        $where=[
            ['village_id','=',$village_id],
            ['park_sys_type','=',$this->park_sys_type],
            ['channel_id','=',$channel_id],
            ['car_number','=',$car_number],
            ['need_alarm','=',$need_alarm],
            ['operate_type','=',$operate_type],
        ];
        $info=$this->ParkWhiteRecord->getFind($where,'id');
        if($info && !$info->isEmpty()){
            return (int)$info['id'];
        }else{
           return 0;
        }
    }

    //todo 获取单个名单
    public function getParkingBlackFindMethod($village_id,$car_number,$field=true){
        $where=[
            ['village_id','=',$village_id],
            ['park_sys_type','=',$this->park_sys_type],
            ['free_park','=',$car_number],
        ];
        $info=$this->HouseVillageParkFree->getFind($where,$field);
        if($info && !$info->isEmpty()){
            $info=$info->toArray();
        }else{
            $info=[];
        }
        return $info;
    }

    //todo 通过车牌获取车辆时间
    public function getParkCarTimeMethod($village_id,$car_number){
        $msg='查询成功';
        $is_free_car=false;
        $is_veto_car=false;
        $start_time=$this->time;
        $free_A=(new HouseNewParkingService())::Free_A;
        $veto_info=$this->getParkingVetoFindMethod($village_id,$car_number,'id,car_number');
        if($veto_info){
            $msg='该['.$car_number.']车牌属于黑名单！';
            $is_veto_car=true;
            $end_time=$this->time;
        }else{
            $car_info=$this->getParkingCarFindMethod($village_id,$car_number,'*');
            if(!$car_info){
                return ['error'=>false,'msg'=>'暂无该['.$car_number.']车牌数据，请先添加车辆！','data'=>[]];
            }
            if($car_info['parking_car_type'] && (int)$car_info['parking_car_type'] == $free_A){
                $car_info['end_time']=2145888000; //todo 针对免费车 到期时间至 2038-01-01 00:00:00
                $is_free_car=true;
            }else{
                /*
                if(!$car_info['end_time'] || intval($car_info['end_time']) <= $this->time){
                    return ['error'=>false,'msg'=>'该['.$car_number.']车牌未设置到期时间或已到期','data'=>[]];
                }
                */
            }
            $start_time=$car_info['start_time'];
            $end_time=$car_info['end_time'];
            $bind_position_ids=array();
            if($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids'])){
                $bind_position_ids=explode(',',$car_info['bind_position_ids']);
                $bind_position_ids=array_map('intval', $bind_position_ids);
            }
            if($car_info && $car_info['car_position_id']){
                $bind_position_ids[]=intval($car_info['car_position_id']);
            }
            $db_house_village_parking_position = new HouseVillageParkingPosition();
            $position = array();
            if($bind_position_ids) {
                $bind_position_ids = array_unique($bind_position_ids);
                $whereArr = array();
                $whereArr[] = array('village_id', '=', $village_id);
                $whereArr[] = array('position_id', 'in', $bind_position_ids);
                $positionObj = $db_house_village_parking_position->getFind($whereArr, '*', 'end_time desc');
                if ($positionObj && !$positionObj->isEmpty()) {
                    $position = $positionObj->toArray();
                    $start_time_tmp=$position['start_time']>100 ? $position['start_time']:$start_time;
                    $start_time=$start_time_tmp;
                    $end_time_tmp=$position['end_time']>=$end_time ? $position['end_time']:$end_time;
                    $end_time=$end_time_tmp;
                }
            }

        }
        $today=date('Y-m-d');
        $today_time=strtotime($today);
        if($end_time>0 && $start_time<100){
            $endday=date('Y-m-d',$end_time);
            $start_time = strtotime($endday);
            if($start_time> $today_time){
                $start_time= $today_time;
            }
        } else if($end_time<100){
            $end_time=$today_time;
            $start_time=$end_time-100;
        }
        return ['error'=>true,'msg'=>$msg,'data'=>[
            'is_free_car'=>$is_free_car,
            'is_veto_car'=>$is_veto_car,
            'start_time'=>$start_time,
            'end_time'=>$end_time
        ]];
    }

    //====================================todo 业务逻辑 start======================================

    /**
     * 出队列
     * @author : lkz
     * @date : 2022/11/17
     * @param $param type 根据类型处理不同业务
     */
    public function parkingQueue($param){
        $result=[];
        $this->parkingHouseFdump(['出队列start1=='.__LINE__,$param,$result],'a11_park/parkingQueue',1);
        switch ($param['type']) {
            case self::HANDLE_BMD_PARK_PASSAGE: //[白名单]处理车道 查询车库关系 生成队列
                $result=$this->synHouseGarageBindPosition($param);
                break;
            case self::HANDLE_BMD_PARK_VEHICLE: //[白名单]处理车辆 生成下发数据
                $result=$this->synHouseBindFreeVehicle($param);
                break;
            case self::HANDLE_HMD_PARK_VEHICLE: //[黑名单]处理车道 生成下发数据
                $result=$this->synHouseBindVetoVehicle($param);
                break;
        }
        $this->parkingHouseFdump(['出队列start2=='.__LINE__,$param,$result],'a11_park/parkingQueue',1);
    }

    /**
     * 触发下发黑/白名单
     * @author : lkz
     * @date : 2022/11/18
     * @param $village_id 小区id
     * @param string $car_number  车牌 (注意需要完整车牌 例如 京A123456)
     * @param int $is_alarm 0:白名单 1:黑名单
     * @param int $operate_type 操作类型(0:增加 1：删除)
     * @return array
     */
    public function triggerWhitelist($village_id,$car_number='',$is_alarm=0,$operate_type=0){
        if(empty($car_number)){
            $whereArr=array(['village_id','=',$village_id],['park_sys_type','=','A11'],['enable','=',1]);
            $findDataObj=$this->ParkWhiteRecord->getFind($whereArr,'*','id desc');
            $findCount=0;
            if($findDataObj && !$findDataObj->isEmpty()){
                $findCount=$this->ParkWhiteRecord->get_count($whereArr);
            }
            $timeX=time()-7200;
            if($findCount>50 || ($findCount>10 && $findDataObj['add_time']>$timeX)){
                return ['error'=>true,'msg'=>'操作成功（正有命令下发中）!','data'=>[]];
            }
        }
        $where=[
            ['village_id','=',$village_id],
            ['park_sys_type','=','A11'],
            ['status','=',1],
        ];
        $field='id,village_id,area_type,passage_area,garage_id';
        $list= $this->ParkPassage->getList($where,$field);
        fdump_api([__LINE__,$village_id,$car_number,'is_alarm'=>$is_alarm,'operate_type'=>$operate_type],'a11_park/triggerWhitelist',1);
        if(!$list || $list->isEmpty()){
            return ['error'=>false,'msg'=>'暂无车道数据，请先添加车道！','data'=>[]];
        }
        $list=$list->toArray();
        if($is_alarm == 1){ //黑名单
            if(empty($car_number)){
                return ['error'=>false,'msg'=>'缺少车牌参数！','data'=>[]];
            }
            if(mb_strlen($car_number) <7 || mb_strlen($car_number) > 10){
                fdump_api(['黑名单车牌不合法--'.__LINE__,$village_id,$car_number,'该['.$car_number.']车牌不合法！'],'a11_park/triggerWhitelist_error',1);
                return ['error'=>false,'msg'=>'该['.$car_number.']车牌不合法！','data'=>[]];
            }
        }
        $operate_arr=[
            0=> self::HANDLE_BMD_PARK_PASSAGE,//白名单
            1=> self::HANDLE_HMD_PARK_VEHICLE //黑名单
        ];
        
        foreach ($list as $v){
            $garage_id=$v['garage_id'];
            if($garage_id<1 && $v['area_type']==2 && $v['passage_area']){
                $garage_id=intval($v['passage_area']);
            }
            if($garage_id<1){
                continue;
            }
            $data=[
                'type'=>$operate_arr[$is_alarm],
                'village_id'=>$v['village_id'],
                'channel_id'=>$v['id'],
                'garage_id'=>$garage_id,
                'car_number'=>$car_number,
                'operate_type'=>$operate_type
            ];
            //fdump_api([__LINE__,'data'=>$data],'a11_park/triggerWhitelist',1);
            $this->parkingHouseQueuePushToJob($data);
        }
        return ['error'=>true,'msg'=>'操作成功（命令下发中）','data'=>[]];
    }

    //todo 查询车库下所有 绑定车位的车辆
    public function synHouseGarageBindPosition($param){
        $D6Service=new D6Service();
        $where=[
            ['pc.village_id','=',$param['village_id']],
            ['pc.is_del','=',1],
            ['pc.end_time','>=',100]
        ];
        if(!empty($param['car_number'])){
            $carNumber=$D6Service->getCarNumber($param['car_number']);
            if($carNumber['province']){
                $where[]= ['pc.province','=',$carNumber['province']];
            }
            if($carNumber['car_number']){
                $where[]= ['pc.car_number','=',$carNumber['car_number']];
            }
        }else{
            $where[]= ['pp.garage_id','=',$param['garage_id']];
        }
        $list=$this->HouseVillageParkingCar->getHouseBindPositionCar($where,'pc.car_id DESC','pc.village_id,pc.province,pc.car_number as last_car_number');
        if(!$list || $list->isEmpty()){
            return ['error'=>false,'msg'=>'该车库ID:['.$param['garage_id'].']下，车道ID：['.$param['channel_id'].']数据，暂无车辆！','data'=>[]];
        }
        foreach ($list as $v){
            $data=[
                'type'=> self::HANDLE_BMD_PARK_VEHICLE,
                'village_id'=>$v['village_id'],
                'garage_id'=>$param['garage_id'],
                'channel_id'=>$param['channel_id'],
                'date_time'=>date('Y-m-d H:i:s'),
                'car_number'=>$D6Service->checkCarNumber($v['province'],$v['last_car_number']),
                'operate_type'=>$param['operate_type'],
            ];
            //fdump_api([__LINE__,'data'=>$data],'a11_park/synHouseGarageBindPosition',1);
            $this->parkingHouseQueuePushToJob($data);
        }
        return ['error'=>true,'msg'=>'车库ID:['.$param['garage_id'].']，查询成功','data'=>[]];
    }

    //todo 查询车辆触发写入白名单
    public function synHouseBindFreeVehicle($param){
        $car_info=$this->getParkCarTimeMethod($param['village_id'],$param['car_number']);
        //$this->parkingHouseFdump(['接收参数=='.__LINE__,$param,$car_info],'a11_park/synHouseBindFreeVehicle',1);
        if(!$car_info['error']){
            $this->parkingHouseFdump(['流程报错=='.__LINE__,$param,$car_info],'a11_park/synHouseBindFreeVehicle_error',1);
            return $car_info;
        }
        $car_info=$car_info['data'];
        //针对该车牌为黑名单 不下发设备
        if($car_info['is_veto_car']){
            $this->parkingHouseFdump(['该车牌为黑名单=='.__LINE__,$param,$car_info],'a11_park/synHouseBindFreeVehicle',1);
            return ['error'=>false,'msg'=>'该['.$param['car_number'].']车牌[白名单]，属于黑名单！','data'=>[]];
        }
        $white_record=$this->checkWhiteRecordMethod($param['village_id'],$param['channel_id'],$param['car_number'],0,$param['operate_type']);
        if($white_record){
            $this->parkingHouseFdump(['白名单命令已存在=='.__LINE__,$param,$car_info,$white_record],'a11_park/synHouseBindFreeVehicle',1);
            return ['error'=>false,'msg'=>'该['.$param['car_number'].']车牌[白名单]命令已下发，请勿重复操作！','data'=>[]];
        }
        $car_end_time=time();
        if(!empty($car_info)&& isset($car_info['end_time']) && $car_info['end_time']>100){
            $car_end_time=$car_info['end_time'];
        }
        if(isset($param['car_end_time']) && $param['car_end_time']>0){
            $car_end_time=$param['car_end_time'];
        }
        $dateTime=date('H');
        $start_time=$car_info['start_time'] ? $car_info['start_time']:time();
        //$start_time=$start_time-60;
        $white_arr=[
            'village_id'=>$param['village_id'],
            'park_sys_type'=>$this->park_sys_type,
            'channel_id'=>$param['channel_id'],
            'car_number'=>$param['car_number'],
            'enable'=>1,
            'need_alarm'=>0,
            'operate_type'=>$param['operate_type'],
            'start_time'=>$start_time,
            'end_time'=>$car_end_time,
            'add_time'=>$this->time,
        ];
        $whereArr=array(['village_id','=',$param['village_id']]);
        $whereArr[]=array('park_sys_type','=',$this->park_sys_type);
        $whereArr[]=array('channel_id','=',$param['channel_id']);
        $whereArr[]=array('car_number','=',$param['car_number']);
        $whereArr[]=array('operate_type','=',$param['operate_type']);
        $findDataObj=$this->ParkWhiteRecord->getFind($whereArr,'*');
        if($findDataObj && !$findDataObj->isEmpty()){
            $findData=$findDataObj->toArray();
            $saveData=array('enable'=>1,'start_time'=>$white_arr['start_time'],'end_time'=>$car_end_time,'add_time'=>$white_arr['add_time']);
            $this->ParkWhiteRecord->save_one(array('id'=>$findData['id']),$saveData);
            $this->parkingHouseFdump(['findData'=>$findData],'a11_park/synHouseBindFreeVehicle'.$dateTime,1);
        }else{
            $result=$this->ParkWhiteRecord->add($white_arr);
            $this->parkingHouseFdump(['id'=>$result,$white_arr],'a11_park/synHouseBindFreeVehicle'.$dateTime,1);
        }
        return ['error'=>true,'msg'=>'该['.$param['car_number'].']车牌[白名单]命令写入成功！','data'=>[]];
    }

    //todo 查询车辆触发写入黑名单
    public function synHouseBindVetoVehicle($param){
        $this->parkingHouseFdump(['接收参数=='.__LINE__,$param],'a11_park/synHouseBindVetoVehicle',1);
        $white_record=$this->checkWhiteRecordMethod($param['village_id'],$param['channel_id'],$param['car_number'],1,$param['operate_type']);
        if($white_record){
            $this->parkingHouseFdump(['黑名单命令已存在=='.__LINE__,$param,$white_record],'a11_park/synHouseBindVetoVehicle',1);
            return ['error'=>false,'msg'=>'该['.$param['car_number'].']车牌[黑名单]命令已下发，请勿重复操作！','data'=>[]];
        }
        $white_arr=[
            'village_id'=>$param['village_id'],
            'park_sys_type'=>$this->park_sys_type,
            'channel_id'=>$param['channel_id'],
            'car_number'=>$param['car_number'],
            'enable'=>1,
            'need_alarm'=>1,
            'operate_type'=>$param['operate_type'],
            'start_time'=>$this->time,
            'end_time'=>$this->time,
            'add_time'=>$this->time,
        ];
        $result=$this->ParkWhiteRecord->add($white_arr);
        if(!$result){
            $this->parkingHouseFdump(['黑名单写入失败=='.__LINE__,$param,$white_arr],'a11_park/synHouseBindVetoVehicle_error',1);
            return ['error'=>false,'msg'=>'该['.$param['car_number'].']车牌[黑名单]命令写入错误！','data'=>[]];
        }
        return ['error'=>true,'msg'=>'该['.$param['car_number'].']车牌[黑名单]命令写入成功！','data'=>[]];
    }

    //todo 删除车辆 下发白名单解绑命令
    public function a11DelBindVehicle($village_id,$car_info){
        $where=[
            ['village_id','=',$village_id],
            ['park_sys_type','=',$this->park_sys_type],
            ['area_type','=',2],
            ['status','=',1],
        ];
        $field='id,village_id';
        $list= $this->ParkPassage->getList($where,$field);
        if(!$list || $list->isEmpty()){
            return ['error'=>false,'msg'=>'暂无车道数据，请先添加车道！','data'=>[]];
        }
        $car_number=(new D6Service())->checkCarNumber($car_info['province'],$car_info['car_number']);
        $data=[];
        foreach ($list as $value){
            $data[]=[
                'village_id'=>$village_id,
                'park_sys_type'=>$this->park_sys_type,
                'channel_id'=>$value['id'],
                'car_number'=>$car_number,
                'enable'=>1,
                'need_alarm'=>0,
                'operate_type'=>1,
                'start_time'=>$this->time,
                'end_time'=>$this->time,
                'add_time'=>$this->time,
            ];
        }
        if($data){
            $this->ParkWhiteRecord->addAll($data);
        }
        return true;
    }

    /**
     * 设备获取车牌后回调接口
     * @author:zhubaodi
     * @date_time: 2022/11/22 11:29
     */
    public function add_park_info($data,$passage_info,$car_number,$serialno,$parkTime){
       $res=false;
        //语音显屏指令初始化
        $showscreen_data=[
            'passage'=>$passage_info,
            'village_id'=>$passage_info['village_id'],
            'car_number'=>$car_number,
            'channel_id'=>$serialno,
            'car_type'=>'',
            'content'=>'',
            'voice_content'=>1
        ];
        if (!empty(cache($passage_info['village_id']))) {
            $village_info = cache('village_info_'.$passage_info['village_id']);
        } else {
            $village_info = $this->HouseVillage->getOne($passage_info['village_id'], 'village_name,village_id,property_id');
            cache('village_info_'.$passage_info['village_id'], $village_info, 86400);
        }
        if($passage_info && !is_array($passage_info) && is_object($passage_info)){
            $passage_info=$passage_info->toArray();
        }
        if ($car_number) {
            $passage_info_arr = $passage_info;
            $village_info_arr = $village_info && !is_array($village_info) ? $village_info->toArray() : $village_info;
            fdump_api(['设备获取车牌后回调接口'=>$car_number,'serialno'=>$serialno,'park_sys_type'=>'A11', 'passage_info'=>$passage_info,'village_info'=>$village_info_arr],'park_temp/log_'.$car_number,1);
        } elseif ($parkTime) {
            fdump_api(['设备获取车牌后回调接口'=>$car_number,'passage_info'=>$passage_info,'village_info'=>$village_info],'D3Park/plateresult'.$parkTime,true);
        }
        if (!empty(cache('backList_'.$passage_info['village_id'].'_'.$car_number))) {
            $black_list = cache('backList_' . $passage_info['village_id'].'_'.$car_number);
        } else {
            //查询黑名单
            $black_list = $this->getParkingVetoFindMethod($passage_info['village_id'],$car_number,'id');
            cache('backList_' . $passage_info['village_id'].'_'.$car_number,$black_list, 86400);
        }
        if ($car_number) {
            $black_list_arr = $black_list && !is_array($black_list) ? $black_list->toArray() : $black_list;
            fdump_api(['查询黑名单'=>$car_number,'serialno'=>$serialno,'park_sys_type'=>'A11', 'black_list'=>$black_list_arr],'park_temp/log_'.$car_number,1);
        } elseif ($parkTime) {
            fdump_api(['查询黑名单'.__LINE__=>$car_number,$black_list],'D3Park/plateresult'.$parkTime,true);
        }
        if($black_list){
            //TODO:屏显和语音提示错误信息
            //黑名单车辆不允许通行
            if ($car_number) {
                $black_list_arr = $black_list && !is_array($black_list) ? $black_list->toArray() : $black_list;
                fdump_api(['黑名单车辆不允许通行'=>$car_number,'serialno'=>$serialno,'park_sys_type'=>'A11', 'black_list'=>$black_list_arr],'park_temp/log_'.$car_number,1);
            } elseif ($parkTime) {
                fdump_api(['黑名单车辆不允许通行=='.__LINE__=>$car_number,'black_list' => $black_list],'a11_park/plateresult'.$parkTime,true);
            }
            $showscreen_data['car_type']= 'black_type';
            $showscreen_data['content']='黑名单禁止通行';
            $showscreen_data['voice_content']= 10;
            $this->addParkShowScreenLog($showscreen_data);
            return $res;
        }
        $param = [
            'parkTime' => $parkTime,
            'car_number' => $car_number,
            'serialno' => $serialno,
        ];
        if ($passage_info['passage_direction'] == 1){
            //车辆入场
            if ($car_number) {
                fdump_api(['车辆入场'=>$car_number,'data'=>$data,'park_sys_type'=>'A11', 'param'=>$param],'park_temp/log_'.$car_number,1);
            }
            $res =$this->in_park($car_number,$data,$village_info,$passage_info,$param);
        } elseif ($passage_info['passage_direction'] == 0){
            //车辆出场
            if ($car_number) {
                fdump_api(['车辆出场'=>$car_number,'data'=>$data,'park_sys_type'=>'A11', 'param'=>$param],'park_temp/log_'.$car_number,1);
            }
            $res =$this->out_park($car_number,$data,$village_info,$passage_info,$param);
        } elseif ($car_number) {
            fdump_api(['通道错误'=>$car_number,'data'=>$data,'park_sys_type'=>'A11', 'param'=>$param],'park_temp/log_'.$car_number,1);
        }
        return $res;
    }

    /***
     *获取车辆绑定的车位 最长停车时间
     ***/
    public function getCarRightInPosition($car = array(), $village_id = 0, $garage_id = 0)
    {

        $bind_position_ids = array();
        if ($car && isset($car['bind_position_ids']) && !empty($car['bind_position_ids'])) {
            $bind_position_ids = explode(',', $car['bind_position_ids']);
            $bind_position_ids = array_map('intval', $bind_position_ids);
        }
        if ($car && $car['car_position_id']) {
            $bind_position_ids[] = intval($car['car_position_id']);
        }
        $parkTime = date('YmdH');
        $nowtime = time();
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        if ($car && isset($car['car_number'])) {
            //检查一下异常的占用 重置成未占用
            $car_number = $car['province'] . $car['car_number'];
            $whereArr = array();
            $whereArr[] = array('village_id', '=', $village_id);
            //$whereArr[] = array('use_car_id', '=', $car['car_id']);
            $whereArr[] = array('use_car_number', '=', $car_number);
            $whereArr[] = array('use_start_time', '<', $nowtime);
            $fieldStr = 'position_id,garage_id,start_time,end_time,access_record_id,use_start_time,use_car_number,use_car_id';
            $use_position_obj = $db_house_village_parking_position->getList($whereArr, $fieldStr, 0);
            if ($use_position_obj && !$use_position_obj->isEmpty()) {
                $use_position = $use_position_obj->toArray();
                $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => 0);
                $this->updateParkingPositionUse($whereArr, $saveArr, $car_number, 'in_park_check');
            }
        }
        fdump_api(['bind_position_ids' => $bind_position_ids, 'car_number' => $car_number], 'a11_park/plateresult'. $parkTime, 1);
        $position = array();
        if ($bind_position_ids) {
            $bind_position_ids = array_unique($bind_position_ids);
            $whereArr = array();
            $whereArr[] = array('village_id', '=', $village_id);
            $whereArr[] = array('position_id', 'in', $bind_position_ids);
            if ($garage_id > 0) {
                $whereArr[] = array('garage_id', '=', $garage_id);
            }
            //$end_time = $nowtime - 86400;
            //$whereArr[] = array('end_time', '>', $end_time);
            $whereArr[] = array('use_car_id', '=', 0);
            $whereArr[] = array('use_start_time', '<', 100);
            $positionObj = $db_house_village_parking_position->getFind($whereArr, '*', 'end_time desc');
            if ($positionObj && !$positionObj->isEmpty()) {
                $position = $positionObj->toArray();
            }
            fdump_api(['whereArr' => $whereArr, 'car_number' => $car_number,'position'=>$position], 'a11_park/plateresult' . $parkTime, 1);
            if (empty($position) && count($bind_position_ids)==1 && $car && isset($car['car_id'])) {
                try {
                    $db_house_village_parking_car = new HouseVillageParkingCar();
                    $whereCarPositionCount = [];
                    $whereCarPositionCount[] = ['is_del', '=', 1];
                    $whereCarPositionCount[] = ['village_id', '=', $village_id];
                    $whereCarPositionCount[] = ['car_id', '<>', $car['car_id']];
                    $whereRaw='(car_position_id ='.$bind_position_ids['0'] .' OR FIND_IN_SET('.$bind_position_ids['0'].',bind_position_ids))';
                    $otherCarObj = $db_house_village_parking_car->getOneCar($whereCarPositionCount,$whereRaw, 'car_id');
                    if (empty($otherCarObj) || $otherCarObj->isEmpty()) {
                        $wherePosition = [];
                        $wherePosition[] = ['village_id', '=', $village_id];
                        $wherePosition[] = ['position_id', '=', $bind_position_ids['0']];
                        if ($garage_id > 0) {
                            $wherePosition[] = array('garage_id', '=', $garage_id);
                        }
                        $positionObj = $db_house_village_parking_position->getFindOne($wherePosition, '*');
                        if ($positionObj && !$positionObj->isEmpty()) {
                            $position = $positionObj->toArray();
                        }
                    }
                } catch (\Exception $e) {
                    fdump_api(['bind_position_ids' => $bind_position_ids, 'car_number' => $car_number,'err'=>$e->getMessage()], 'a11_park/plateresult'.$parkTime, 1);
                }
            }
        }
        if (!empty($position)) {
            $position['start_time'] = isset($position['start_time']) && intval($position['start_time']) > 0 ? intval($position['start_time']) : 0;
        }
        $this->updateA113WhiteList($position, $car, $village_id, 'in_park');
        return $position;
    }
    
    public function updateA113WhiteList($position=array(),$car=array(),$village_id=0,$comfrom=''){
        $now_time=time();
        $car_end_time=$car['end_time'];
        $garage_id=0;
        if($position && isset($position['garage_id'])){
            $garage_id=$position['garage_id'];
        }
        if($position && $position['start_time']<$now_time && $position['end_time']>$car_end_time){
            $car_end_time=$position['end_time'];
        }
        $wData=array('village_id'=>$village_id,'garage_id'=>$garage_id,'car_end_time'=>$car_end_time);
        $wData['car_number']=$car['province'].$car['car_number'];
        $wData['cfrom']=$comfrom;
        //$wData['car_info']=$car;
        
        $whereArr=array('village_id'=>$village_id,'park_sys_type'=>'A11','status'=>1);
        if(isset($data['garage_id']) && $data['garage_id']>0){
            $whereArr['_string']='garage_id='.$data['garage_id'].' OR (area_type=2 AND passage_area='.$data['garage_id'].')';
        }
        $field='id,village_id,channel_number,device_number,passage_direction,area_type,passage_area,garage_id';
        $listObj= $this->ParkPassage->getList($whereArr,$field);
        if($listObj && !$listObj->isEmpty()){
            $list=$listObj->toArray();
            foreach ($list as $pvv){
                $garage_id=$pvv['garage_id'];
                if($garage_id<1 && $pvv['area_type']==2 && $pvv['passage_area']){
                    $garage_id=intval($pvv['passage_area']);
                }
                if($garage_id<1){
                    continue;
                }
                if($car_end_time>$now_time){
                    //月租车，添加白名单
                    $param=$wData;
                    $param['operate_type']=0;
                    $param['channel_id']=$pvv['id'];
                    $param['garage_id']=$garage_id;
                    $this->synHouseBindFreeVehicle($param);
                }else{
                    //月租车，删除白名单
                    $param=$wData;
                    $param['operate_type']=1;
                    $param['channel_id']=$pvv['id'];
                    $param['garage_id']=$garage_id;
                    $this->synHouseBindFreeVehicle($param);
                }
            }
        }
    }

    public function getCarRightOutPosition($car = array(), $village_id = 0, $garage_id = 0, $car_number = '')
    {
        $whereArr = array();
        if (!empty($car_number)) {
            $whereArr[] = array('use_car_number', '=', $car_number);
        } elseif ($car && isset($car['car_id']) && !empty($car['car_id'])) {
            $whereArr[] = array('use_car_id', '=', $car['car_id']);
        }
        $parkTime=date('YmdH');
        $position = array();
        if (!empty($whereArr)) {
            $db_house_village_parking_position = new HouseVillageParkingPosition();
            $whereArr[] = array('village_id', '=', $village_id);
            if ($garage_id > 0) {
                $whereArr[] = array('garage_id', '=', $garage_id);
            }
            //$whereArr[] = array('use_start_time', '>', 100);
            $positionObj = $db_house_village_parking_position->getFind($whereArr, '*', 'end_time desc');
            if ($positionObj && !$positionObj->isEmpty()) {
                $position = $positionObj->toArray();
            }
            /*
            if (empty($position) && isset($car['car_position_id']) && intval($car['car_position_id']) > 0 && isset($car['car_id']) && intval($car['car_id']) > 0) {
                try {
                    $db_house_village_parking_car = new HouseVillageParkingCar();
                    $whereCarPositionCount = [];
                    $whereCarPositionCount[] = ['car_position_id', '=', intval($car['car_position_id'])];
                    $whereCarPositionCount[] = ['is_del', '=', 1];
                    $whereCarPositionCount[] = ['car_id', '<>', $car['car_id']];
                    $otherCar = $db_house_village_parking_car->getOne($whereCarPositionCount, 'car_id');
                    if (!isset($otherCar['car_id']) || !$otherCar['car_id']) {
                        $wherePosition = [];
                        $wherePosition[] = ['village_id', '=', $village_id];
                        $wherePosition[] = ['position_id', '=', intval($car['car_position_id'])];
                        $positionObj = $db_house_village_parking_position->getFindOne($wherePosition, 'position_id, village_id, start_time, end_time, garage_id, position_num');
                        if ($positionObj && !is_array($positionObj)) {
                            $position = $positionObj->toArray();
                        }
                    }
                } catch (\Exception $e) {
                    fdump_api(['执行错误'=>$e->getMessage(),'car_number'=>$car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'func_get_args' => func_get_args()],'$a11GetCarRightOutPosition');
                }
            }
            */
        }
        if (!empty($position)) {
            $position['start_time'] = isset($position['start_time']) && intval($position['start_time']) > 0 ? intval($position['start_time']) : 0;
        }
        $nowTime = time();
        if(empty($position)){
            $bind_position_ids=array();
            if($car && isset($car['bind_position_ids']) && !empty($car['bind_position_ids'])){
                $bind_position_ids=explode(',',$car['bind_position_ids']);
                $bind_position_ids=array_map('intval', $bind_position_ids);
            }
            if($car && $car['car_position_id']){
                $bind_position_ids[]=intval($car['car_position_id']);
            }
            if(!empty($bind_position_ids)){
                $bind_position_ids = array_unique($bind_position_ids);
                $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
                $access_record_where_near = [
                    ['car_number', '=', $car_number],
                    ['park_id', '=', $village_id],
                    ['accessType', '=',1],
                    ['del_time', '<', 1],
                    ['accessTime', '<=', $nowTime],
                ];
                $houseVillageParkingCarBindPositionDb=new HouseVillageParkingCarBindPosition();
                if(count($bind_position_ids)==1){
                    //只绑了一个车位
                    $whereArr=array(['position_id','=',$bind_position_ids['0']]);
                    if ($garage_id > 0) {
                        $whereArr[] = array('garage_id', '=', $garage_id);
                    }
                    $positionObj = $db_house_village_parking_position->getFind($whereArr, '*');
                    if ($positionObj && !$positionObj->isEmpty()) {
                        //绑定的这个没被占用
                        $position = $positionObj->toArray();
                        if($position['use_car_id']<1 && empty($position['use_car_number'])){
                            $position['start_time']=$position['start_time'] ? $position['start_time']:0;
                            $whereBind=array('position_id'=>$bind_position_ids['0'],'car_id'=>$car['car_id'],'del_time'=>0);
                            $oneBindObj=$houseVillageParkingCarBindPositionDb->getOneData($whereBind,'*');
                            if($oneBindObj && !$oneBindObj->isEmpty()){
                                $bind_position_time=$oneBindObj['bind_position_time'];
                                // 通行时间小于出场时间的最近的一条入场时间的记录
                                $park_access_record_obj = $db_house_village_car_access_record->getOne($access_record_where_near,'*','record_id DESC');
                                if($park_access_record_obj && !$park_access_record_obj->isEmpty()){
                                    $park_access_record=$park_access_record_obj->toArray();
                                    if($park_access_record['flag_status']=='temporaryCarNoPosition' || $park_access_record['park_car_type']=='temporaryCarNoPosition'|| $park_access_record['car_type']=='temporaryCarNoPosition'){
                                        //无车位进场的
                                        $inAccessTime=$park_access_record['accessTime']>100 ? $park_access_record['accessTime']:$nowTime;
                                        $inAccessTimeDay=date('Ymd',$inAccessTime);
                                        $inAccessTimeDay=intval($inAccessTimeDay);
                                        $bindTimeDay=date('Ymd',$bind_position_time);
                                        $bindTimeDay=intval($bindTimeDay);
                                        $chaDay=$bindTimeDay-$inAccessTimeDay;
                                        if($chaDay>0 && $position['end_time'] > $nowTime){
                                            //车位有效，并且绑定时间和进场时间隔了至少一个零点
                                            $bindTimeDate=date('Y-m-d',$bind_position_time);
                                            $bindTimeDay_time=strtotime($bindTimeDate);
                                            //绑定时间日 凌晨算临时车结束时间
                                            $position['temporary_end_time']=$bindTimeDay_time-1;
                                        }
                                    }
                                }

                            }
                        }else{
                            $position=array();
                        }
                    }
                }
                fdump_api(['position'=>$position,'bind_position_ids' => $bind_position_ids], 'a11_park/plateresult'.$parkTime, 1);
                //还是没找到车位
                if(empty($position)){
                    $whereBindArr=array();
                    $whereBindArr[]=array('position_id','in',$bind_position_ids);
                    $whereBindArr[]=array('del_time','<',1);
                    $whereBindArr[] = array('village_id', '=', $village_id);
                    $carPositionBindObj=$houseVillageParkingCarBindPositionDb->getListData($whereBindArr,'position_id,car_id,bind_position_time,garage_id,village_id','id DESC', 0,100);
                    $usePositionArr=array();
                    if($carPositionBindObj && !$carPositionBindObj->isEmpty()){
                        $carPositionBind= $carPositionBindObj->toArray();
                        $tmpCarPositionBind=array();
                        foreach ($carPositionBind as $ppv){
                            if(isset($tmpCarPositionBind[$ppv['position_id']])){
                                $tmpCarPositionBind[$ppv['position_id']][]=$ppv['car_id'];
                            }else{
                                $tmpCarPositionBind[$ppv['position_id']]=array($ppv['car_id']);
                            }
                        }
                        foreach ($bind_position_ids as $p_id){
                            $tmpxPosition=isset($tmpCarPositionBind[$p_id]) && $tmpCarPositionBind[$p_id] ? array_unique($tmpCarPositionBind[$p_id]):'';
                            if(empty($tmpxPosition)){
                                $usePositionArr[]=$p_id;
                            }else if(is_array($tmpxPosition) && in_array($car['car_id'],$tmpxPosition) && count($tmpxPosition)==1){
                                $usePositionArr[]=$p_id;
                            }
                        }
                    }else{
                        $usePositionArr=$bind_position_ids;
                    }
                    fdump_api(['usePositionArr'=>$usePositionArr,'bind_position_ids' => $bind_position_ids], 'a11_park/plateresult'.$parkTime, 1);
                    if(!empty($usePositionArr)){
                        $whereArr=array(['position_id','in',$usePositionArr]);
                        $whereArr[]=array('start_time','<',$nowTime);
                        $whereArr[]=array('end_time','>',$nowTime);
                        if ($garage_id > 0) {
                            $whereArr[] = array('garage_id', '=', $garage_id);
                        }
                        $positionObj = $db_house_village_parking_position->getFind($whereArr, '*','use_start_time ASC,position_id DESC');
                        if ($positionObj && !$positionObj->isEmpty()) {
                            $position = $positionObj->toArray();
                        }
                    }
                }
            }
        }

        $this->updateA113WhiteList($position, $car, $village_id, 'out_park');
        return $position;
    }
    public function updateParkingPositionUse($whereArr=array(),$saveArr=array(),$car_number='',$comfrom='out_park'){
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        fdump_api(['car_number'=>$car_number,'comfrom'=>$comfrom,'whereArr'=>$whereArr,'saveArr'=>$saveArr],'park_temp/00updateParkingPositionUse'.date('H'),1);
        $ret=$db_house_village_parking_position->saveOne($whereArr,$saveArr);
        return  $ret;
    }
    /**
     * 车辆入场
     * @author:zhubaodi
     * @date_time: 2022/3/17 11:52
     */
    public function in_park($car_number,$data,$village_info,$passage_info,$param=[]){
        if (isset($param['parkTime'])&&$param['parkTime']) {
            $parkTime = $param['parkTime'];
        } else {
            $parkTime = 0;
        }
        if (isset($param['serialno'])&&$param['serialno']) {
            $serialno = $param['serialno'];
        } else {
            $serialno='';
        }
        //查询设备所属车库属性
        $db_house_village_parking_garage=new HouseVillageParkingGarage();
        $garage_id=0;
        if (!empty($passage_info['passage_area'])&&$passage_info['area_type']==2){
            $garage_id=$passage_info['passage_area'];
        }
        if(isset($passage_info['garage_id']) && $passage_info['garage_id']>0){
            $garage_id=$passage_info['garage_id'];
        }
        $passage_info['garage_id']=$garage_id;
        $car_number_type=$data['AlarmInfoPlate']['result']['PlateResult']['type'];
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $db_house_village_bind_car = new HouseVillageBindCar();
        $db_park_plateresult_log = new ParkPlateresultLog();
        $service_image=new ImageService();
        $fileHandle   = new FileHandle();
        $showscreen_data=[];
        $showscreen_data['passage']=$passage_info;
        $showscreen_data['village_id']=$passage_info['village_id'];
        $showscreen_data['car_number']= $car_number;
        $showscreen_data['channel_id']= $serialno;
        $showscreen_data['car_type']= '';
        $showscreen_data['voice_content']= 1;
        $showscreen_data['content']= '欢迎光临';
        $park_log_data = [];
        $park_log_data['car_number'] = $car_number;
        $park_log_data['channel_id'] = $serialno;
        $park_log_data['park_type'] = 1;
        $park_log_data['park_sys_type'] = 'A11';
        $park_log_data['village_id'] =$passage_info['village_id'];
        $park_log_data['add_time'] = time();
        $logId = $db_park_plateresult_log->add($park_log_data);
        if ($car_number) {
            fdump_api(['添加识别记录'=>$car_number,'serialno'=>$serialno,'park_sys_type'=>'A11', 'park_log_data'=>$park_log_data, 'showscreen_data'=>$showscreen_data, 'logId'=>$logId],'park_temp/log_'.$car_number,1);
        } elseif ($parkTime) {
            fdump_api(['添加识别记录=='.__LINE__=>$car_number,'park_log_data' => $park_log_data,'showscreen_data' => $showscreen_data,'logId' => $logId],'a11_park/plateresult'.$parkTime,true);
        }
        $rand_num = date('Ymd');// 换成日期存储
        $path = '/upload/park_log/' . $rand_num . '/' . $serialno . '/';
        $up_dir = $_SERVER['DOCUMENT_ROOT'] . $path;
        if (strpos($up_dir, 'v20/public') !== false) {
            $up_dir = str_replace('/v20/public', '', $up_dir);
        }
        $now_time=time();
        $park_data = [];
        // 记录车辆进入信息
        $car_access_record = [];
        $park_data['car_number'] = $car_number;
        $park_data['in_time'] = $now_time;
        $park_data['order_id'] = uniqid();
        $park_data['in_channel_id'] = $passage_info['id'];
        $park_data['is_paid'] = 0;
        $park_data['park_id'] = $passage_info['village_id'];
        $park_data['park_sys_type'] = 'A11';
        $park_data['park_name'] = $village_info['village_name'];
        if ($car_number) {
            fdump_api(['车辆信息整合'=>$car_number,'serialno'=>$serialno,'park_sys_type'=>'A11', 'park_data'=>$park_data],'park_temp/log_'.$car_number,1);
        } elseif ($parkTime) {
            fdump_api(['添加识别记录=='.__LINE__=>$car_number,$data['AlarmInfoPlate']['result']['PlateResult']],'a11_park/plateresult'.$parkTime,true);
        }
        $ymdhis = date('Ymdhis');
        $imageToJob = [];
        $isImageToJob = false;
        if (isset($data['AlarmInfoPlate']['result']['PlateResult']['imageFile']) && !empty($data['AlarmInfoPlate']['result']['PlateResult']['imageFile'])) {
            $imageFile = trim($data['AlarmInfoPlate']['result']['PlateResult']['imageFile']);
            $savePathFile = $ymdhis . '_image_big_' . $car_number;
            $isImageToJob = true;
            $imageToJob['park_type'] = 'in_park';
            $imageToJob['isCompressImage'] = 1;
            $imageToJob['serialno'] = $serialno;
            $imageToJob['car_number'] = $car_number;
            $imageToJob['in_time'] = $now_time;
            $imageToJob['order_id'] = $park_data['order_id'];
            $imageToJob['village_id'] = $passage_info['village_id'];
            $imageToJob['nowTime'] = $now_time;
            $imageToJob['imageSavePath'] = $up_dir;
            $imageToJob['imageFile'] = $imageFile;
            $imageToJob['imageFileSaveFileName'] = $savePathFile;
        }
        if (isset($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile']) && !empty($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile'])) {
            $imageFragmentFile = trim($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile']);
            $savePathFragmentFile = $ymdhis . '_image_small_' . $car_number;
            if (!$isImageToJob) {
                $isImageToJob = true;
                $imageToJob['park_type'] = 'in_park';
                $imageToJob['isCompressImage'] = 0;
                $imageToJob['serialno'] = $serialno;
                $imageToJob['car_number'] = $car_number;
                $imageToJob['in_time'] = $now_time;
                $imageToJob['order_id'] = $park_data['order_id'];
                $imageToJob['village_id'] = $passage_info['village_id'];
                $imageToJob['nowTime'] = $now_time;
                $imageToJob['imageSavePath'] = $up_dir;
            }
            $imageToJob['imageFragmentFile'] = $imageFragmentFile;
            $imageToJob['imageFragmentFileSaveFileName'] = $savePathFragmentFile;
        }
        if(isset($data['AlarmInfoPlate']['result']['PlateResult']['imageUrl']) && $data['AlarmInfoPlate']['result']['PlateResult']['imageUrl']){
            $imageUrlPathTmp= $data['AlarmInfoPlate']['result']['PlateResult']['imageUrl'];
            if($imageUrlPathTmp && (strpos($imageUrlPathTmp,'ttp://') || strpos($imageUrlPathTmp,'ttps://'))){
                $imageUrlPathArr=explode('?',$imageUrlPathTmp);
                $imageBigUrlPath=$imageUrlPathArr['0'];
                $car_access_record['accessBigImage']=$imageBigUrlPath;
                $park_data['in_image_big']=$imageBigUrlPath;
            }
        }
        if(isset($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentUrl']) && $data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentUrl']){
            $imageUrlPathTmp= $data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentUrl'];
            if($imageUrlPathTmp && (strpos($imageUrlPathTmp,'ttp://') || strpos($imageUrlPathTmp,'ttps://'))){
                $imageUrlPathArr=explode('?',$imageUrlPathTmp);
                $imageSmallUrlPath=$imageUrlPathArr['0'];
                $car_access_record['accessImage']=$imageSmallUrlPath;
                $park_data['in_image_small']=$imageSmallUrlPath;
            }
        }
        if ($car_number) {
            fdump_api(['车辆信息整合'=>$car_number,'serialno'=>$serialno,'park_sys_type'=>'A11', 'park_data'=>$park_data],'park_temp/log_'.$car_number,1);
        } elseif ($parkTime) {
            fdump_api(['车辆相关信息=='.__LINE__=>$car_number,'park_data' => $park_data],'a11_park/plateresult'.$parkTime,true);
        }
        $car_access_record['channel_id'] = $passage_info['id'];
        $car_access_record['channel_number'] = $passage_info['channel_number'];
        $car_access_record['channel_name'] = $passage_info['passage_name'];
        $car_access_record['imageToJob'] = $imageToJob;
        $garage_id=0;
        if($passage_info && isset($passage_info['garage_id']) && $passage_info['garage_id']>0){
            $garage_id=$passage_info['garage_id'];
        }else if($passage_info && isset($passage_info['passage_area']) && !empty($passage_info['passage_area']) && $passage_info['area_type']==2){
            $garage_id=intval($passage_info['passage_area']);
        }
        $car_access_record['garage_id']=$garage_id;
        $passage_info['garage_id']=$garage_id;
        //查询免费车
        $res_free=$this->free_in_park($car_number,$passage_info,$park_data,$village_info,$showscreen_data,$now_time,$parkTime,$car_number_type,$car_access_record);
        if ($res_free){
            return $res_free;
        }
        //查询车辆到期时间
        $province = mb_substr($car_number, 0, 1);
        $car_no = mb_substr($car_number, 1);
        if ($car_number) {
            fdump_api(['查询条件'=>$car_number,'serialno'=>$serialno,'park_sys_type'=>'A11','province' => $province,'car_no' => $car_no],'park_temp/log_'.$car_number,1);
        } elseif ($parkTime) {
            fdump_api(['查询条件=='.__LINE__=>$car_number,'province' => $province,'car_no' => $car_no],'a11_park/plateresult'.$parkTime,true);
        }
        $car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no,'village_id'=>$passage_info['village_id']]);
        if($car_info && !$car_info->isEmpty()){
            $car_info=$car_info->toArray();
        }else{
            $car_info=array();
        }
        
        if ($car_number) {
            fdump_api(['查询条件'=>$car_number,'serialno'=>$serialno,'park_sys_type'=>'A11','car_info' => $car_info],'park_temp/log_'.$car_number,1);
        } elseif ($parkTime) {
            fdump_api(['查询车辆信息=='.__LINE__=>$car_number,'car_info' => $car_info],'a11_park/plateresult'.$parkTime,true);
        }
        $parking_car_type = isset($car_info['parking_car_type']) && intval($car_info['parking_car_type']) > 0 ? intval($car_info['parking_car_type']) : 0;
        if (isset($car_info['stored_balance']) && floatval($car_info['stored_balance']) > 0) {
            $showscreen_data['stored_balance'] = $car_info['stored_balance'];
            $showscreen_data['car_type'] = 'storedCar';
        } elseif (in_array($parking_car_type, [13, 14, 15, 16])) {
            $showscreen_data['stored_balance'] = $car_info['stored_balance'];
            $showscreen_data['car_type'] = 'storedCar';
        }
        $uid=0;
        $bind_id=0;
        $car_user_name='';
        $car_user_phone='';
        $rightPosition=array();
        
        if ($car_info && isset($car_info['car_id']) && $car_info['car_id']>0) {
            if($car_info['start_time']>$now_time){
                //还未开始
                $car_info['end_time']=0;
            }
            $car_user_name	=isset($car_info['car_user_name']) ? $car_info['car_user_name']:'';
            $car_user_phone=isset($car_info['car_user_phone']) ? $car_info['car_user_phone']:'';
            $rightPosition=$this->getCarRightInPosition($car_info,$passage_info['village_id'],$garage_id);
            fdump_api(['car_info'=>$car_info,'rightPosition' => $rightPosition], 'park_temp/log_' . $car_number, 1);
            if(!empty($rightPosition)){
                if($rightPosition['start_time']<=$now_time && $rightPosition['end_time']>=$car_info['end_time']){
                    $car_info['end_time']=$rightPosition['end_time'];
                }
                if($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids']) && $rightPosition['start_time']>$now_time){
                    //车位时间还没开始
                    $car_info['end_time']=0;
                }
                if (isset($rightPosition['position_id']) && $rightPosition['position_id']) {
                    $car_access_record['position_id'] = $rightPosition['position_id'];
                }
                if (isset($rightPosition['garage_id']) && $rightPosition['garage_id']) {
                    $car_access_record['garage_id'] = $rightPosition['garage_id'];
                }
                if (isset($rightPosition['position_num']) && $rightPosition['position_num']) {
                    $car_access_record['position_num'] = $rightPosition['position_num'];
                }
            }elseif(empty($rightPosition)){
                $bind_position_ids=array();
                if($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids'])){
                    //新的绑定车位 多车多位逻辑，没有可有车位时
                    $bind_position_ids=explode(',',$car_info['bind_position_ids']);
                    $car_info['end_time']=0;   //没有可用车位时
                }
                if(isset($car_info['garage_id']) && $car_info['garage_id']>0 &&  $car_info['garage_id']!=$garage_id){
                    //进入的不是同一个车库
                    $car_info['end_time']=0;
                }
                if($car_info && $car_info['car_position_id']){
                    $bind_position_ids[]=$car_info['car_position_id'];
                }
                if(empty($bind_position_ids)){
                    //标记一下无车位进场临时车
                    $car_access_record['flag_status']='temporaryCarNoPosition';
                    $car_access_record['park_car_type']='temporaryCarNoPosition';
                    $car_access_record['car_type']='temporaryCarNoPosition';
                }
            }
            $car_access_record['car_id']=$car_info['car_id'];
            $bind_info = $db_house_village_bind_car->getFind(['car_id' => $car_info['car_id'],'village_id'=>$passage_info['village_id']]);
            $uid = $bind_info && isset($bind_info['uid']) && $bind_info['uid'] ? $bind_info['uid'] : 0;
            $bind_id = $bind_info && isset($bind_info['user_id']) && $bind_info['user_id'] ? $bind_info['user_id'] : 0;
        }else{
            //标记一下无车位进场临时车
            $car_access_record['flag_status']='temporaryCarNoPosition';
            $car_access_record['park_car_type']='temporaryCarNoPosition';
            $car_access_record['car_type']='temporaryCarNoPosition';
        }
        $car_access_record['user_name']=$car_user_name;
        $car_access_record['user_phone']=$car_user_phone;
        $car_access_record['uid']=$uid;
        $car_access_record['bind_id']=$bind_id;
        //查询设备所属车库属性
        //车辆卡类为免费车时
        if (!empty($car_info)&&$car_info['parking_car_type']==self::Free_A){
            //todo:不能直接调用免费车接口
            $res_free_1=$this->free_car_type_in($car_number,$passage_info,$park_data,$village_info,$showscreen_data,$now_time,$parkTime,$car_info,$car_number_type,$car_access_record);
            if ($res_free_1){
                if ($car_number) {
                    fdump_api(['免费车入场'=>$car_number,'res_free_1'=>$res_free_1],'park_temp/log_'.$car_number,1);
                }
                return $res_free_1;
            }
        }
        //获取车辆所属车库id
        if (!empty($car_info) &&empty($car_info['garage_id'])&&!empty($car_info['car_position_id'])){
            $position_info=$db_house_village_parking_position->getFind(['position_id'=>$car_info['car_position_id']]);
            if (!empty($position_info)){
                $car_info['garage_id']=$position_info['garage_id'];
            }
        }
        if (!empty($passage_info['garage_id']) &&!empty($car_info['garage_id']) && $car_info['end_time'] > $now_time && $passage_info['garage_id']==$car_info['garage_id']) {
           //月租车入场
            if ($car_number) {
                $passage_info_arr =  $passage_info;
                $car_info_arr     = $car_info && !is_array($car_info) ? $car_info->toArray() : $car_info;
                $village_info_arr = $village_info && !is_array($village_info) ? $village_info->toArray() : $village_info;
                fdump_api([
                    '月租车入场'=>$car_number, 'passage_info' => $passage_info, 'car_info' => $car_info_arr,
                    'park_data' => $park_data, 'village_info' => $village_info_arr, 'showscreen_data' => $showscreen_data,
                    'now_time' => $now_time, 'parkTime' => $parkTime, 'car_number_type' => $car_number_type,
                    ],'park_temp/log_'.$car_number,1);
            }
            $res_month=$this->month_in_park($car_number,$passage_info,$car_info,$park_data,$village_info,$showscreen_data,$parkTime,$car_number_type,$car_access_record,$rightPosition);
           if ($res_month){
               return  $res_month;
           }
        } 
        else {
            //临时车入场
            if ($car_number) {
                $passage_info_arr = $passage_info;
                $car_info_arr     = $car_info && !is_array($car_info) ? $car_info->toArray() : $car_info;
                $village_info_arr = $village_info && !is_array($village_info) ? $village_info->toArray() : $village_info;
                fdump_api([
                    '临时车入场'=>$car_number, 'passage_info' => $passage_info, 'car_info' => $car_info_arr,'car_access_record' => $car_access_record,
                    'park_data' => $park_data, 'village_info' => $village_info_arr, 'showscreen_data' => $showscreen_data,
                    'now_time' => $now_time, 'parkTime' => $parkTime, 'car_number_type' => $car_number_type,
                ],'park_temp/log_'.$car_number,1);
            }
            $res_temp= $this->temp_in_park($car_number,$passage_info,$car_info,$park_data,$car_access_record,$village_info,$showscreen_data,$parkTime,$car_number_type);
            return $res_temp;
        }
        return false;
    }
    /**
     * 车辆出场
     * @author:zhubaodi
     * @date_time: 2022/3/17 11:52
     */
    public function out_park($car_number,$data,$village_info,$passage_info,$param=[]){
        if (isset($param['parkTime'])&&$param['parkTime']) {
            $parkTime = $param['parkTime'];
        } else {
            $parkTime = 0;
        }
        if (isset($param['serialno'])&&$param['serialno']) {
            $serialno = $param['serialno'];
        } else {
            $serialno='';
        }
        $db_in_park = new InPark();
        $db_out_park = new OutPark();
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $db_park_plateresult_log = new ParkPlateresultLog();
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $db_house_village_bind_car = new HouseVillageBindCar();
        $db_park_passage = new ParkPassage();
        $service_image=new ImageService();
        $fileHandle   = new FileHandle();
        $db_house_village_parking_garage=new HouseVillageParkingGarage();
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $garage_id=0;
        if($passage_info && isset($passage_info['garage_id']) && $passage_info['garage_id']>0){
            $garage_id=$passage_info['garage_id'];
        }else if($passage_info && isset($passage_info['passage_area']) && !empty($passage_info['passage_area']) && $passage_info['area_type']==2){
            $garage_id=intval($passage_info['passage_area']);
        }
        $park_config =$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$passage_info['village_id']);

        $car_number_type=$data['AlarmInfoPlate']['result']['PlateResult']['type'];
        $showscreen_data=[];
        $showscreen_data['passage']       = $passage_info;
        $showscreen_data['village_id']    = $passage_info['village_id'];
        $showscreen_data['car_number']    = $data['AlarmInfoPlate']['result']['PlateResult']['license'];
        $showscreen_data['channel_id']    = $data['AlarmInfoPlate']['serialno'];
        $showscreen_data['car_type']      = '';
        $showscreen_data['voice_content'] = 2;
        $showscreen_data['content']       = '一路平安';
        $now_time = time();
        $park_log_data = [];
        $park_log_data['car_number'] = $data['AlarmInfoPlate']['result']['PlateResult']['license'];
        $park_log_data['car_number_type'] = $data['AlarmInfoPlate']['result']['PlateResult']['type'];
        $park_log_data['channel_id'] = $data['AlarmInfoPlate']['serialno'];
        $park_log_data['park_type'] = 2;
        $park_log_data['park_sys_type'] = 'A11';
        $park_log_data['village_id'] =$passage_info['village_id'];
        $park_log_data['add_time'] = $now_time;
        $logId = $db_park_plateresult_log->add($park_log_data);
        if ($car_number) {
            fdump_api(['添加识别记录'=>$car_number,'serialno'=>$serialno,'park_sys_type'=>'A11', 'park_log_data'=>$park_log_data, 'showscreen_data'=>$showscreen_data, 'logId'=>$logId],'park_temp/log_'.$car_number,1);
        }
        $rand_num = date('Ymd');// 换成日期存储
        $path = '/upload/park_log/' . $rand_num . '/' . $data['AlarmInfoPlate']['serialno'] . '/';
        $up_dir = $_SERVER['DOCUMENT_ROOT'] . $path;
        if (strpos($up_dir, 'v20/public') !== false) {
            $up_dir = str_replace('/v20/public', '', $up_dir);
        }
        $park_data = [];
        $park_data['out_time'] = $now_time;
        $park_data['out_channel_id'] = $passage_info['id'];
        $park_data['is_out'] = 1;
        $park_data['is_paid'] = 1;
        // 记录车辆进入信息
        $car_access_record = [];
        $ymdhis = date('Ymdhis');
        $imageToJob = [];
        $isImageToJob = false;
        if (isset($data['AlarmInfoPlate']['result']['PlateResult']['imageFile']) && !empty($data['AlarmInfoPlate']['result']['PlateResult']['imageFile'])) {
            $imageFile = trim($data['AlarmInfoPlate']['result']['PlateResult']['imageFile']);
            $savePathFile = $ymdhis . '_image_big_' . $car_number;
            $isImageToJob = true;
            $imageToJob['park_type'] = 'out_park';
            $imageToJob['serialno'] = $serialno;
            $imageToJob['car_number'] = $car_number;
            $imageToJob['out_time'] = $now_time;
            $imageToJob['village_id'] = $passage_info['village_id'];
            $imageToJob['nowTime'] = $now_time;
            $imageToJob['imageSavePath'] = $up_dir;
            $imageToJob['imageFile'] = $imageFile;
            $imageToJob['imageFileSaveFileName'] = $savePathFile;
        }
        if (isset($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile']) && !empty($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile'])) {
            $imageFragmentFile = trim($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile']);
            $savePathFragmentFile = $ymdhis . '_image_small_' . $car_number;
            if (!$isImageToJob) {
                $isImageToJob = true;
                $imageToJob['park_type'] = 'out_park';
                $imageToJob['serialno'] = $serialno;
                $imageToJob['car_number'] = $car_number;
                $imageToJob['out_time'] = $now_time;
                $imageToJob['village_id'] = $passage_info['village_id'];
                $imageToJob['nowTime'] = $now_time;
                $imageToJob['imageSavePath'] = $up_dir;
            }
            $imageToJob['imageFragmentFile'] = $imageFragmentFile;
            $imageToJob['imageFragmentFileSaveFileName'] = $savePathFragmentFile;
        }
        $imageBigUrlPath='';
        $imageSmallUrlPath='';
        if(isset($data['AlarmInfoPlate']['result']['PlateResult']['imageUrl']) && $data['AlarmInfoPlate']['result']['PlateResult']['imageUrl']){
            $imageUrlPathTmp= $data['AlarmInfoPlate']['result']['PlateResult']['imageUrl'];
            if($imageUrlPathTmp && (strpos($imageUrlPathTmp,'ttp://') || strpos($imageUrlPathTmp,'ttps://'))){
                $imageUrlPathArr=explode('?',$imageUrlPathTmp);
                $imageBigUrlPath=$imageUrlPathArr['0'];
                $car_access_record['accessBigImage']=$imageBigUrlPath;
                $park_data['out_image_big']=$imageBigUrlPath;
            }
        }
        if(isset($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentUrl']) && $data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentUrl']){
            $imageUrlPathTmp= $data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentUrl'];
            if($imageUrlPathTmp && (strpos($imageUrlPathTmp,'ttp://') || strpos($imageUrlPathTmp,'ttps://'))){
                $imageUrlPathArr=explode('?',$imageUrlPathTmp);
                $imageSmallUrlPath=$imageUrlPathArr['0'];
                $car_access_record['accessImage']=$imageSmallUrlPath;
                $park_data['out_image_small']=$imageSmallUrlPath;
            }
        }
        $car_access_record['channel_id']=$passage_info['id'];
        $car_access_record['channel_number']=$passage_info['channel_number'];
        $car_access_record['channel_name']=$passage_info['passage_name'];
        //查询入场信息
        $whereArr=['car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'park_sys_type' => 'A11', 'is_out' => 0, 'del_time' => 0];
        $in_park_info = $db_in_park->getOne1($whereArr);
        $whereTmpArr=['car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'park_sys_type' => 'A11', 'is_out' => 1, 'del_time' => 0];
        $in_park_tmp = $db_in_park->getOne1($whereTmpArr);
        if($in_park_tmp && !$in_park_tmp->isEmpty()){
            //同一个车道 如果之前很短暂时间内已经出去过了
            $in_park_tmp=$in_park_tmp->toArray();
            $tmp_out_time=$now_time-60;
            if(($in_park_tmp['out_time']>$tmp_out_time && $in_park_tmp['out_time']<$now_time) && $passage_info['id']==$in_park_tmp['out_channel_id']){
                //放行
                fdump_api(['同一个车道 如果之前很短暂时间内已经出去过了=='.__LINE__=>$car_number,'now_time'=>$now_time,'in_park_tmp'=>$in_park_tmp,'passage_info'=>$passage_info],'park_temp/log_'.$car_number,1);
                return true;
            }
        }
        if ($car_number) {
            $in_park_info_arr = $in_park_info && !is_array($in_park_info) ? $in_park_info->toArray() : $in_park_info;
            fdump_api(['添加识别记录=='.__LINE__=>$car_number,'park_log_data' => $park_log_data,'in_park_info' => $in_park_info_arr],'park_temp/log_'.$car_number,1);
        } elseif ($parkTime) {
            fdump_api(['添加识别记录=='.__LINE__=>$car_number,'park_log_data' => $park_log_data,'in_park_info' => $in_park_info],'a11_park/plateresult'.$parkTime,true);
        }
        if ($isImageToJob) {
            $imageToJob['in_park_id'] = isset($in_park_info['id']) ? intval($in_park_info['id']) : 0;
            $imageToJob['in_time'] = isset($in_park_info['in_time']) ? intval($in_park_info['in_time']) : 0;
            $imageToJob['order_id'] = isset($in_park_info['order_id']) ? trim($in_park_info['order_id']) : '';
        }
        $car_access_record['imageToJob'] = $imageToJob;
        
        $park_time = $now_time-$in_park_info['in_time'];
        $out_data = [];
        $out_data['car_number'] = $car_number;
        $out_park_info=[];
        $out_time = $now_time;
        if (!empty($in_park_info)) {
            if ($in_park_info['in_time'] > ($now_time - 10)) {
                return false;
            }
            $out_data['in_time'] = $in_park_info['in_time'];
            $out_data['order_id'] = $in_park_info['order_id'];
        } else {
            $out_park_info = $db_in_park->getOne1(['car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'park_sys_type' => 'A11', 'is_out' => 1, 'del_time' => 0]);
            $out_time = $now_time - $park_config['out_park_time'] * 60;
        }
        $out_data['out_time'] = $now_time;
        $out_data['park_id'] = $passage_info['village_id'];
        if ($park_time > 0) {
            $showscreen_data['duration'] = $park_time;
        }
        $car_access_record['garage_id']=$garage_id;
        //查询免费车
        $in_park_id= isset($in_park_info['id']) ? intval($in_park_info['id']) : 0;
        $res_free=$this->free_out_park($car_number,$passage_info,$park_data,$showscreen_data,$village_info,$now_time,$parkTime,$car_number_type,$car_access_record,$in_park_id);
        if ($res_free){
            return $res_free;
        }
        $park_data['park_time'] = $park_time; 
        //查询车辆到期时间
        $province = mb_substr($car_number, 0, 1);
        $car_no = mb_substr($car_number, 1);
        $car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no,'village_id'=>$passage_info['village_id']]);
        if($car_info && !$car_info->isEmpty()){
            $car_info=$car_info->toArray();
        }else{
            $car_info=array();
        }
        if ($car_number) {
            fdump_api(['查询车辆到期时间=='.__LINE__=>$car_number,'province' => $province,'car_no' => $car_no,'car_info' => $car_info],'park_temp/log_'.$car_number,1);
        } elseif ($parkTime) {
            fdump_api(['查询车辆到期时间=='.__LINE__=>$car_number,'province' => $province,'car_no' => $car_no,'car_info' => $car_info,'in_park_info' => $in_park_info],'a11_park/plateresult'.$parkTime,true);
        }
        $bind_id=0;
        $uid=0;
        $car_user_name	='';
        $car_user_phone='';
        
        $outRightPosition=array();
        if ($car_info && isset($car_info['car_id']) && $car_info['car_id']>0) {
            $outRightPosition=$this->getCarRightOutPosition($car_info,$passage_info['village_id'],$garage_id,$car_number);
            fdump_api(['car_info'=>$car_info,'rightPosition' => $outRightPosition], 'a11_park/plateresult'.$parkTime, 1);
            if(!empty($outRightPosition)){
                if($outRightPosition['start_time']<=$now_time && $outRightPosition['end_time']>=$car_info['end_time']){
                    $car_info['end_time']=$outRightPosition['end_time'];
                    if(isset($outRightPosition['temporary_end_time']) && $outRightPosition['temporary_end_time']>0){
                        $car_info['end_time']=$outRightPosition['temporary_end_time'];
                    }
                    if($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids']) && $outRightPosition['start_time']>$now_time){
                        //车位时间还没开始
                        $car_info['end_time']=0;
                    }
                }
                if (isset($outRightPosition['position_id']) && $outRightPosition['position_id']) {
                    $car_access_record['position_id']=$outRightPosition['position_id'];
                }
                if (isset($outRightPosition['garage_id']) && $outRightPosition['garage_id']) {
                    $car_access_record['garage_id']=$outRightPosition['garage_id'];
                }
                if (isset($outRightPosition['position_num']) && $outRightPosition['position_num']) {
                    $car_access_record['position_num']=$outRightPosition['position_num'];
                }
            }else{
                //没有可用车位
                $bind_position_ids=array();
                if($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids'])){
                    //新的绑定车位 多车多位逻辑
                    $bind_position_ids=explode(',',$car_info['bind_position_ids']);
                    $car_info['end_time']=0;   //没有可用车位时
                }
            }
            $car_access_record['car_id']=$car_info['car_id'];
            $car_user_name	= isset($car_info['car_user_name']) ? $car_info['car_user_name']:'';
            $car_user_phone=isset($car_info['car_user_phone']) ? $car_info['car_user_phone']:'';
            $bind_info = $db_house_village_bind_car->getFind(['car_id' => $car_info['car_id'],'village_id'=>$passage_info['village_id']]);
            $uid = $bind_info && isset($bind_info['uid']) && $bind_info['uid'] ? $bind_info['uid'] : 0;
            $bind_id = $bind_info && isset($bind_info['user_id']) && $bind_info['user_id'] ? $bind_info['user_id'] : 0;
        }
        if(empty($car_user_name) || empty($car_user_phone)){
            $car_access_record_where = [
                ['car_number', '=', $car_number],
                ['park_id', '=', $passage_info['village_id']],
                ['accessType', '=',1],
                ['user_phone', '<>',''],
            ];
            $tmp_car_access_record = $db_house_village_car_access_record->getOne($car_access_record_where);
            if($tmp_car_access_record && !is_array($tmp_car_access_record) && !$tmp_car_access_record->isEmpty()){
                $tmp_car_access_record=$tmp_car_access_record->toArray();
            }
            if($tmp_car_access_record && $tmp_car_access_record['user_phone'] && empty($car_user_phone)){
                $car_user_phone=$tmp_car_access_record['user_phone'];
            }
            if($tmp_car_access_record && $tmp_car_access_record['user_name'] && empty($car_user_name)){
                $car_user_name=$tmp_car_access_record['user_name'];
            }
        }
        $car_access_record['user_name']=$car_user_name;
        $car_access_record['user_phone']=$car_user_phone;
        $car_access_record['uid']=$uid;
        $car_access_record['bind_id']=$bind_id;
        $car_access_record['is_out']=1;
        //免费车卡类出场
        if($car_info['parking_car_type']==self::Free_A){
            $res_free1=$this->free_car_type_out($car_number,$passage_info,$park_data,$showscreen_data,$village_info,$parkTime,$car_info,$car_number_type, $park_time,$car_access_record,$outRightPosition);
            if ($res_free1){
                return $res_free1;
            }
        }
        //查询设备所属车库属性
        $passage_info['garage_id']=$garage_id;
        $is_real_garage=1;
        if ($garage_id>0){
            $garage_info=$db_house_village_parking_garage->getOne(['garage_id'=>$garage_id]);
            if($garage_info && !$garage_info->isEmpty()){
                $garage_info=$garage_info->toArray();
                if($garage_info['fid']>0 && $garage_info['fid']==$garage_info['garage_id']){
                    $garage_info['fid']=0;
                }

                if($garage_info['fid']>0 && $garage_info['fid']!=$garage_info['garage_id'] ){
                    $is_real_garage=0;
                }
            }else{
                $garage_info=array();
            }
            fdump_api(['line=='.__LINE__,'garage_info'=>$garage_info],'park_temp/log_'.$car_number,1);
        }
        $passage_info['garage_id']=$garage_id;

        if ($car_number) {
            $in_park_info_arr = $in_park_info && !is_array($in_park_info) ? $in_park_info->toArray() : $in_park_info;
            $out_park_info_arr = $out_park_info && !is_array($out_park_info) ? $out_park_info->toArray() : $out_park_info;
            fdump_api(['车辆进出场[旧]信息=='.__LINE__=>$car_number,'in_park_info' => $in_park_info_arr,'out_park_info' => $out_park_info_arr],'park_temp/log_'.$car_number,1);
        }
        fdump_api(['line=='.__LINE__,$passage_info,$car_info,$in_park_info,$out_park_info],'park_temp/log_'.$car_number,1);
        if ($car_info && !empty($passage_info['garage_id']) &&!empty($car_info['garage_id']) && $car_info['end_time'] > $now_time && $passage_info['garage_id']==$car_info['garage_id']) {
            //小场月租车出场
            $hours = intval($park_time / 3600);
            $mins = intval(($park_time - $hours * 3600) / 60);
            $second = $park_time - $hours * 3600 - $mins * 60;
            $time = '';
            if ($hours > 0) {
                $time .= $hours . '小时';
            }
            if ($mins > 0) {
                $time .= $mins . '分钟';
            }
            if ($second > 0) {
                $time .= $second . '秒';
            }
            if ($car_number) {
                fdump_api(['月租车[车通道和通过通道相同]=='.__LINE__=>$car_number,'time' => $time,'end_time' => $car_info['end_time']],'park_temp/log_'.$car_number,1);
            }
            if (isset($garage_info)&&!empty($garage_info['fid'])){
                if ($car_number) {
                    $garage_info_arr = $garage_info && !is_array($garage_info) ? $garage_info->toArray() : $garage_info;
                    fdump_api(['月租车 存在父级车库[当前子车库出场,也就是小场出场]=='.__LINE__=>$car_number, 'garage_info' => $garage_info_arr],'park_temp/log_'.$car_number,1);
                }
                $res_month1=$this->month_out_park($car_number,$passage_info,$car_info,$park_data,$showscreen_data,$in_park_info,$village_info,$out_time,$parkTime,$car_number_type,$out_park_info,$car_access_record,$outRightPosition,$is_real_garage);
                if($res_month1){
                    //发送模板消息
                    $openid=$this->getUserInfo($car_number,$passage_info['village_id']);
                    if (!empty($openid)){
                        $this->remarkTxt      = '\n支付金额： 月租车出场';
                        $this->pay_price = '0';
                        $this->stored_balance = isset($car_info['stored_balance']) && $car_info['stored_balance'] ? $car_info['stored_balance'] : 0;
                        $this->sendCarMeassage($openid, $car_number, $car_info, $village_info, $garage_info, $passage_info, $time, $village_info['property_id'], [],$now_time, false);
                    }
                    return $res_month1;
                }
            }
            elseif (isset($garage_info)&&empty($garage_info['fid'])){
                $is_real_garage=1;
                if ($car_number) {
                    $garage_info_arr = $garage_info && !is_array($garage_info) ? $garage_info->toArray() : $garage_info;
                    fdump_api(['月租车 父级车库[当前就是父车库出场,也就是大场出场]=='.__LINE__=>$car_number, 'garage_info' => $garage_info_arr],'park_temp/log_'.$car_number,1);
                }
                //大场月租车出场
                $out_park_info = $db_in_park->getOne1(['car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'park_sys_type' => 'A11', 'is_out' => 1, 'del_time' => 0]);
                if ($car_number) {
                    $out_park_info_arr = $out_park_info && !is_array($out_park_info) ? $out_park_info->toArray() : $out_park_info;
                    fdump_api(['月租车 检查改车辆是否第一次出场[也就是是否已经有了出场]=='.__LINE__=>$car_number, 'out_park_info' => $out_park_info_arr],'park_temp/log_'.$car_number,1);
                }
                if (empty($out_park_info)){
                    if ($car_number) {
                        fdump_api(['月租车 无出场记录 直接出场=='.__LINE__=>$car_number],'park_temp/log_'.$car_number,1);
                    }
                    $res_month=$this->month_out_park($car_number,$passage_info,$car_info,$park_data,$showscreen_data,$in_park_info,$village_info,$out_time,$parkTime,$car_number_type,$out_park_info,$car_access_record,$outRightPosition,$is_real_garage);
                    if($res_month){
                        //发送模板消息
                        $openid=$this->getUserInfo($car_number,$passage_info['village_id']);
                        if (!empty($openid)){
                            $this->remarkTxt      = '\n支付金额： 月租车出场';
                            $this->pay_price = '0';
                            $this->stored_balance = isset($car_info['stored_balance']) && $car_info['stored_balance'] ? $car_info['stored_balance'] : 0;
                            $this->sendCarMeassage($openid, $car_number, $car_info, $village_info, $garage_info, $passage_info, $time, $village_info['property_id'], [],$now_time, false);
                        }
                        return $res_month; 
                    }
                }
                else{
                    //查询小场停车时长
                    $small_park_time=$out_park_info['park_time'];
                    $small_passage=$db_park_passage->getFind(['id'=>$out_park_info['out_channel_id']]);
					$small_garage_id=0;
					if($small_passage && isset($small_passage['garage_id']) && $small_passage['garage_id']>0){
						$small_garage_id=$small_passage['garage_id'];
					}else if($small_passage && isset($small_passage['passage_area']) && !empty($small_passage['passage_area']) && $small_passage['area_type']==2){
						$small_garage_id=intval($small_passage['passage_area']);
					}
                    if (!empty($small_passage)&& $small_garage_id>0){
                        $small_garage_info = $db_house_village_parking_garage->getOne(['garage_id'=>$small_garage_id]);
                    } else {
                        $small_garage_info = [];
                    }
                    if ($car_number) {
                        $small_passage_arr = $small_passage && !is_array($small_passage) ? $small_passage->toArray() : $small_passage;
                        $small_garage_info_arr = $small_garage_info && !is_array($small_garage_info) ? $small_garage_info->toArray() : $small_garage_info;
                        fdump_api(['月租车 获取小场通道和车库信息=='.__LINE__=>$car_number, 'small_passage' => $small_passage_arr, 'small_garage_info' => $small_garage_info_arr],'park_temp/log_'.$car_number,1);
                    }
                    //临时车小场停车不收费
                    if(isset($small_garage_info)&&empty($small_garage_info['is_month_charge'])){
                        // is_month_charge【针对A11智慧停车】月租车进入非月租场 是否收费 0:不收费 1:收费
                        if ($car_number) {
                            fdump_api(['月租车 小场按照配置不收费直接出场=='.__LINE__=>$car_number],'park_temp/log_'.$car_number,1);
                        }
                        $res_month=$this->month_out_park($car_number,$passage_info,$car_info,$park_data,$showscreen_data,$in_park_info,$village_info,$out_time,$parkTime,$car_number_type,$out_park_info,$car_access_record,$outRightPosition,$is_real_garage);
                        if($res_month){
                            //发送模板消息
                            $openid=$this->getUserInfo($car_number,$passage_info['village_id']);
                            if (!empty($openid)){
                                $this->remarkTxt      = '\n支付金额： 月租车出场';
                                $this->pay_price = '0';
                                $this->stored_balance = isset($car_info['stored_balance']) && $car_info['stored_balance'] ? $car_info['stored_balance'] : 0;
                                $this->sendCarMeassage($openid, $car_number, $car_info, $village_info, $garage_info, $passage_info, $time, $village_info['property_id'], [],$now_time, false);
                            }
                            return $res_month;
                        }
                    }elseif(isset($small_garage_info)&&!empty($small_garage_info['is_month_charge'])){
                        // is_month_charge【针对A11智慧停车】月租车进入非月租场 是否收费 0:不收费 1:收费
                        if ($car_number) {
                            fdump_api(['月租车 收费=='.__LINE__=>$car_number],'park_temp/log_'.$car_number,1);
                        }
                        $this->month_out_park($car_number,$passage_info,$car_info,$park_data,$showscreen_data,$in_park_info,$village_info,$out_time,$parkTime,$car_number_type,$out_park_info,$car_access_record,$outRightPosition);
                        //小场停车时长
                        $hours = intval($out_park_info['park_time'] / 3600);
                        $mins = intval(($out_park_info['park_time'] - $hours * 3600) / 60);
                        $second = $out_park_info['park_time'] - $hours * 3600 - $mins * 60;
                        $time = '';
                        if ($hours > 0) {
                            $time .= $hours . '小时';
                        }
                        if ($mins > 0) {
                            $time .= $mins . '分钟';
                        }
                        if ($second > 0) {
                            $time .= $second . '秒';
                        }
                        if ($out_park_info['total']>0&&!empty($car_info)&&$car_info['stored_balance']>$out_park_info['total']){
                          
                            $park_info_car111 = $db_house_village_car_access_record->getOne(['from_id'=>$out_park_info['id'],'accessType'=>2]);
                            $car_access_record1=[];
                            $car_access_record1['car_type'] = 'storedCar';
                            $car_access_record1['stored_balance']= getFormatNumber($car_info['stored_balance']-$out_park_info['total']);
                            $car_access_record1['pay_type'] = $this->pay_type[1];
                            $car_access_record1['update_time'] = $now_time;
                            $car_access_record1['is_out'] = 1;
                            if($park_info_car111 && !$park_info_car111->isEmpty()){
                                $wherexArr=[];
                                $wherexArr[]=['record_id','=',$park_info_car111['record_id']];
                                $db_house_village_car_access_record->saveOne($wherexArr,$car_access_record1);
                                
                                //将此条之前的 都改掉
                                /*
                                $wherexArr=[];
                                $wherexArr[]=['car_number','=',$car_number];
                                $wherexArr[]=['is_out','=',0];
                                $wherexArr[]=['park_id','=',$passage_info['village_id']];
                                $db_house_village_car_access_record->saveOne($wherexArr,['is_out'=>1,'update_time'=>$now_time]);
                                */
                                $tmpUpdateRecordArr=array('car_number'=> $car_number,'in_park_id'=>$in_park_id,'village_id'=>$passage_info['village_id'],'record_id'=>$park_info_car111['record_id'],'access_order_id'=>$park_info_car111['order_id'],'access_type'=>2,'access_time'=>time());
                                (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                                
                            }
                            
                            //TODO:屏显和语音提示车辆通行
                            $showscreen_data['car_type']      = 'temporary_type';
                            $showscreen_data['duration_txt']  = $time;
                            $showscreen_data['duration']      = $out_park_info['park_time'];
                            $showscreen_data['price']         = $out_park_info['total'];
                            $showscreen_data['voice_content'] = 9;
                            $showscreen_data['content']       = '';
                            if (isset($car_access_record1['stored_balance'])) {
                                $showscreen_data['stored_balance'] = $car_access_record1['stored_balance'];
                                $showscreen_data['car_type'] = 'storedCar';
                            }

                            fdump_api(['对应车辆储值余额变动'=>$car_number,'stored_balance' => $car_info['stored_balance'],'total' => $out_park_info['total'],'stored_balance1' => $car_access_record1['stored_balance']], 'park_temp/stored_balance_' . $car_number, 1);
                            if ($car_number) {
                               fdump_api(['月租车 储值抵扣显屏通行=='.__LINE__=>$car_number,'car_access_record1' => $car_access_record1,'showscreen_data' => $showscreen_data],'park_temp/log_'.$car_number,1);
                            } elseif ($parkTime) {
                                fdump_api(['车辆通行=='.__LINE__=>$car_number,'showscreen_data' => $showscreen_data],'a11_park/plateresult'.$parkTime,true);
                            }
                            $this->addParkShowScreenLog($showscreen_data);
                            $db_house_village_parking_car->editHouseVillageParkingCar(['province' => $province, 'car_number' => $car_no,'village_id'=>$passage_info['village_id']],['stored_balance'=>$car_access_record1['stored_balance']]);

                            //车辆通行
                            //发送模板消息
                            $openid=$this->getUserInfo($car_number,$passage_info['village_id']);
                            if (!empty($openid)){
                                $this->remarkTxt      = '\n支付金额：'.$out_park_info['total'].'元（储值抵扣）';
                                $this->pay_price = $out_park_info['total'];
                                if (isset($car_access_record1['stored_balance'])) {
                                    $this->stored_balance = $car_access_record1['stored_balance'];
                                    $this->remarkTxt .= '（储值抵扣）';
                                } else {
                                    $this->stored_balance = isset($car_info['stored_balance']) && $car_info['stored_balance'] ? $car_info['stored_balance'] : 0;
                                }
                                $this->sendCarMeassage($openid, $car_number, $car_info, $village_info, $garage_info, $passage_info, $time, $village_info['property_id'], [],$now_time, false);
                            }
                            return true;
                        }
                        elseif($out_park_info['total']==0){
                            //将此条之前的 都改掉
                            /*
                            $wherexArr=[];
                            $wherexArr[]=['car_number','=',$car_number];
                            $wherexArr[]=['is_out','=',0];
                            $wherexArr[]=['park_id','=',$passage_info['village_id']];
                            $db_house_village_car_access_record->saveOne($wherexArr,['is_out'=>1,'update_time'=>$now_time]);
                            */
                            //发送模板消息
                            if ($car_number) {
                                fdump_api(['月租车 小场代收费为0直接出场=='.__LINE__=>$car_number],'park_temp/log_'.$car_number,1);
                            }
                            $openid=$this->getUserInfo($car_number,$passage_info['village_id']);
                            if (!empty($openid)){
                                $this->remarkTxt      = '\n支付金额： 月租车出场';
                                $this->pay_price = '0';
                                $this->stored_balance = isset($car_info['stored_balance']) && $car_info['stored_balance'] ? $car_info['stored_balance'] : 0;
                                $this->sendCarMeassage($openid, $car_number, $car_info, $village_info, $garage_info, $passage_info, $time, $village_info['property_id'], [],$now_time, false);
                            }
                            return true;
                        }
                        else{
                            //TODO:屏显和语音提示错误信息
                            $showscreen_data['voice_content']= 5;
                            $showscreen_data['content']= '';
                            $showscreen_data['car_type']= 'temporary_type';
                            $showscreen_data['duration_txt']= $time;
                            $showscreen_data['duration']= $out_park_info['park_time'];
                            $showscreen_data['price']= $out_park_info['total'];
                            if ($car_number) {
                                fdump_api(['月租车 小场代收费显屏语音收费=='.__LINE__=>$car_number,'showscreen_data' => $showscreen_data],'park_temp/log_'.$car_number,1);
                            } elseif ($parkTime) {
                                fdump_api(['屏显和语音提示错误信息'=>$car_number,'showscreen_data' => $showscreen_data],'a11_park/plateresult'.$parkTime,true);
                            }
                            $this->addParkShowScreenLog($showscreen_data);
                            //需缴费通行
                            return false;
                        }
                    }
                    
                }
            }
        } 
        else {
            if (isset($garage_info)&&!empty($garage_info['fid']) && $garage_info['garage_id']!=$garage_info['fid']){
                if ($car_number) {
                    fdump_api(['小场临时车出场=='.__LINE__=>$car_number],'park_temp/log_'.$car_number,1);
                }
                //小场临时车出场
                //查询停车费用
                $temp_pay=[
                    'village_id'      => $garage_info['village_id'],
                    'car_number'      => $car_number,
                    'device_number'   => $passage_info['device_number'],
                    'car_number_type' => $car_number_type
                ];
                $pay = $this->get_temp_pay($temp_pay,$garage_id);
                if ($car_number) {
                    fdump_api(['小场临时车出场[费用]=='.__LINE__=>$car_number, 'temp_pay' => $temp_pay, 'pay' => $pay],'park_temp/log_'.$car_number,1);
                }
                if(!empty($in_park_info)){
                    //写入车辆入场表 避免遗留车辆在场 都处理成出场
                    $db_in_park->saveOne(['id'=>$in_park_info['id'],'car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'park_sys_type' => 'A11', 'is_out' => 0], $park_data);
                    //把之前未出场的全部改成出场
                    $whereInPark=[];
                    $whereInPark[]=['id','<',$in_park_info['id']];
                    $whereInPark[]=['car_number','=',$car_number];
                    $whereInPark[]=['park_id','=',$passage_info['village_id']];
                    $whereInPark[]=['park_sys_type','=','A11'];
                    $whereInPark[]=['is_out','=','0'];
                    $saveInPark=array('is_out'=>1,'out_time'=>$park_data['out_time']);
                    $db_in_park->saveOne($whereInPark,$saveInPark);
                }
                $out_data['total'] = $pay['pay_money'];
                $out_data['pay_type'] = 'free';
                $starttime = time() - 30;
                $endtime = time() + 50;
                $park_where = [
                    ['car_number', '=', $car_number],
                    ['park_id', '=', $passage_info['village_id']],
                    ['out_time', '>=', $starttime],
                    ['out_time', '<=', $endtime],
                ];
                $park_info_car = $db_out_park->getOne($park_where);
                if ($car_number) {
                    $park_info_car_arr = $park_info_car && !is_array($park_info_car) ? $park_info_car->toArray() : $park_info_car;
                    fdump_api(['查询车出场记录=='.__LINE__=>$car_number, 'park_info_car' => $park_info_car],'park_temp/log_'.$car_number,1);
                }
                //写入车辆入场表
                if (!isset($park_info_car['id']) || !$park_info_car['id']) {
                    if ($car_number) {
                        fdump_api(['记录入场==' . __LINE__ => $car_number, 'out_data' => $out_data], 'park_temp/log_' . $car_number, 1);
                    }
                    $insert_id = $db_out_park->insertOne($out_data);
                } else {
                    $insert_id = $park_info_car['id'];
                }
                if ($isImageToJob) {
                    $imageToJob['out_park_id'] = $insert_id;
                }
                if ($car_number) {
                    fdump_api(['车辆相关信息整合=='.__LINE__=>$car_number, 'park_data' => $park_data],'park_temp/log_'.$car_number,1);
                } elseif ($parkTime) {
                    fdump_api(['车辆通行'=>$car_number,'park_data' => $park_data],'a11_park/plateresult'.$parkTime,true);
                }
                $starttime = time() - 30;
                $endtime = time() + 50;
                $park_where = [
                    ['car_number', '=', $car_number],
                    ['park_id', '=', $passage_info['village_id']],
                    ['accessType', '=',2],
                    ['accessTime', '>=', $starttime],
                    ['accessTime', '<=', $endtime],
                ];
                $park_info_car = $db_house_village_car_access_record->getOne($park_where);
                if ($car_number) {
                    $park_info_car_arr = $park_info_car && !is_array($park_info_car) ? $park_info_car->toArray() : $park_info_car;
                    fdump_api(['查询车出场记录[新]=='.__LINE__=>$car_number, 'park_info_car' => $park_info_car_arr],'park_temp/log_'.$car_number,1);
                }
                //写入车辆入场表
                if (empty($park_info_car)) {
                    $park_where = [
                        ['car_number', '=', $car_number],
                        ['park_id', '=', $passage_info['village_id']],
                        ['accessType', '=',1],
                        ['is_out', '=', 0],
                    ];
                    $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                    $in_record_id = isset($park_info_car111['record_id']) ? intval($park_info_car111['record_id']) : 0;
                    $inAccessTime = isset($park_info_car111['accessTime']) ? intval($park_info_car111['accessTime']) : 0;
                    !$inAccessTime && $inAccessTime = isset($imageToJob['in_time']) ? intval($imageToJob['in_time']) : 0;
                    if ($isImageToJob && $in_record_id) {
                        $imageToJob['in_record_id'] = $in_record_id;
                    }
                    if (!isset($car_info['parking_car_type'])||empty($car_info['parking_car_type'])){
                        $db_house_village_park_charge_cartype=new HouseVillageParkChargeCartype();
                        $car_types=$db_house_village_park_charge_cartype->getFind(['village_id'=>$passage_info['village_id'],'car_number_type'=>$car_number_type,'status'=>1,'park_sys_type'=>'A11'],'car_type');
                        if (!empty($car_types)){
                            $car_info['parking_car_type']=$car_types['car_type'];
                        }else{
                            $car_info['parking_car_type']=self::Temp_A;
                        } 
                    }
                    $car_access_record = unsetParam($car_access_record, 'imageToJob');
                    $car_access_record['business_type'] = 0;
                    $car_access_record['business_id'] = $passage_info['village_id'];
                    $car_access_record['car_number'] = $car_number;
                    $car_access_record['accessType'] = 2;
                    $car_access_record['accessTime'] = $now_time;
                    $inAccessTime > 0 && $car_access_record['park_time'] = $now_time - $inAccessTime;
                    $car_access_record['accessMode'] = 3;
                    $car_access_record['park_sys_type'] = 'A11';
                    $car_access_record['park_car_type'] = $this->parking_a11_car_type_arr[$car_info['parking_car_type']];
                    $car_access_record['coupon_id'] = $pay['coupon_id'];
                    $car_access_record['park_id'] = $passage_info['village_id'];
                    $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                    $car_access_order_id = isset($park_info_car111['order_id']) && $park_info_car111['order_id'] ? $park_info_car111['order_id'] : '';
                    $car_access_record['order_id'] = $car_access_order_id;
                    $car_access_record['total'] = $pay['pay_money'];
                    // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                    // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                    //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                    $car_access_record['update_time'] = $now_time;
                    $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                    if (isset($insert_id)) {
                        $car_access_record['from_id'] = $insert_id;
                    }
                    $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                    $tmpUpdateRecordArr=array('car_number'=> $car_number,'in_park_id'=>$in_park_id,'village_id'=>$passage_info['village_id'],'record_id'=>$record_id,'access_order_id'=>$car_access_order_id,'access_type'=>2,'access_time'=>time());
                    (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                    
                    if(!empty($outRightPosition)){
                        //释放车位
                        $whereArr=array('position_id'=>$outRightPosition['position_id'],'use_car_id'=>$car_info['car_id']);
                        $saveArr=array('use_car_id'=>0,'use_start_time'=>0,'use_car_number'=>'','access_record_id'=>$record_id);
                        $this->updateParkingPositionUse($whereArr,$saveArr,$car_number);
                    }
                    if ($car_number) {
                        $park_info_car111_arr = $park_info_car111 && !is_array($park_info_car111) ? $park_info_car111->toArray() : $park_info_car111;
                        fdump_api(['查询车出场记录[新]=='.__LINE__=>$car_number,'park_info_car111' => $park_info_car111_arr,'car_access_record' => $car_access_record,'record_id' => $record_id],'park_temp/log_'.$car_number,1);
                    } elseif ($parkTime) {
                        fdump_api(['车辆通行'=>$car_number,'park_info_car111' => $park_info_car111,'car_access_record' => $car_access_record,'record_id' => $record_id],'a11_park/plateresult'.$parkTime,true);
                    }
                } else {
                    $record_id = isset($park_info_car['record_id']) ? intval($park_info_car['record_id']) : 0;
                    $car_access_order_id = isset($park_info_car['order_id']) ? trim($park_info_car['order_id']) : '';
                }
                //TODO:屏显和语音提示车辆通行
                $showscreen_data['car_type']     = 'temporary_type';
                $showscreen_data['duration_txt'] = $pay['time'];
                $showscreen_data['duration']     = $pay['park_time'];
                $showscreen_data['price']        = $pay['pay_money'];
                if ($car_number) {
                    fdump_api(['车辆通行[屏显和语音提示车辆通行]=='.__LINE__=>$car_number,'showscreen_data' => $showscreen_data],'park_temp/log_'.$car_number,1);
                } elseif ($parkTime) {
                    fdump_api(['车辆通行'=>$car_number,'showscreen_data' => $showscreen_data],'a11_park/plateresult'.$parkTime,true);
                }
                $this->addParkShowScreenLog($showscreen_data);
                //发送模板消息
                $openid=$this->getUserInfo($car_number,$passage_info['village_id']);
                if (!empty($openid)){
                    $this->remarkTxt      = '\n支付金额： 0';
                    $this->pay_price = '0';
                    $this->stored_balance = isset($car_info['stored_balance']) && $car_info['stored_balance'] ? $car_info['stored_balance'] : 0;
                    $this->sendCarMeassage($openid, $car_number, $car_info, $village_info, $garage_info, $passage_info, $pay['time'], $village_info['property_id'], [],$now_time, true);
                }
                //车辆通行
                if ($isImageToJob && ($record_id || $insert_id)) {
                    if (isset($car_access_order_id) && $car_access_order_id) {
                        $imageToJob['car_access_order_id'] = $car_access_order_id;
                    }
                    $imageToJob['out_record_id'] = $record_id;
                    (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                }
                return true;
                
            }
            elseif (isset($garage_info)&& (empty($garage_info['fid']) || $garage_info['garage_id']==$garage_info['fid'])){
                //大场临时车出场
                if ($car_number) {
                    fdump_api(['大场临时车出场='.__LINE__=>$car_number],'park_temp/log_'.$car_number,1);
                }
                $is_real_garage=1;
                //查询车辆在小场的类型 
                $out_park_info = $db_in_park->getOne1(['car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'park_sys_type' => 'A11', 'is_out' => 1, 'del_time' => 0]);
                $small_passage=$db_park_passage->getFind(['id'=>$out_park_info['out_channel_id']]);
				
					$small_garage_id=0;
					if($small_passage && isset($small_passage['garage_id']) && $small_passage['garage_id']>0){
						$small_garage_id=$small_passage['garage_id'];
					}else if($small_passage && isset($small_passage['passage_area']) && !empty($small_passage['passage_area']) && $small_passage['area_type']==2){
						$small_garage_id=intval($small_passage['passage_area']);
					}
				    if (!empty($small_passage)&& $small_garage_id>0){
                        $small_garage_info = $db_house_village_parking_garage->getOne(['garage_id'=>$small_garage_id]);
                    } else {
                        $small_garage_info = [];
                    }
                if ($car_number) {
                    $out_park_info_arr = $out_park_info && !is_array($out_park_info) ? $out_park_info->toArray() : $out_park_info;
                    $small_passage_arr = $small_passage && !is_array($small_passage) ? $small_passage->toArray() : $small_passage;
                    $small_garage_info_arr = $small_garage_info && !is_array($small_garage_info) ? $small_garage_info->toArray() : $small_garage_info;
                    fdump_api(['月租车 获取出场记录和小场通道和车库信息=='.__LINE__=>$car_number, 'out_park_info' => $out_park_info_arr, 'small_passage' => $small_passage_arr, 'small_garage_info' => $small_garage_info_arr],'park_temp/log_'.$car_number,1);
                }
                if ($car_info['end_time'] > $now_time){
                    //月租车 按照配置大场是否收费，不收费直接抬竿
                    if (isset($garage_info) && empty($garage_info['is_month_charge'])) {
                        if ($car_number) {
                            fdump_api(['月租车 按照配置大场是否收费，不收费直接抬竿=' . __LINE__ => $car_number], 'park_temp/log_' . $car_number, 1);
                        }
                        $starttime = time() - 20;
                        $endtime = time() + 50;
                        $park_where = [
                            ['car_number', '=', $car_number],
                            ['park_id', '=', $passage_info['village_id']],
                            ['accessType', '=', 2],
                            ['accessTime', '>=', $starttime],
                            ['accessTime', '<=', $endtime],
                        ];
                        $park_info_car = $db_house_village_car_access_record->getOne($park_where);
                        if (empty($park_info_car) || $park_info_car->isEmpty()) {
                            $park_where = [
                                ['car_number', '=', $car_number],
                                ['park_id', '=', $passage_info['village_id']],
                                ['accessType', '=', 1],
                                ['is_out', '=', 0],
                            ];
                            $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                            $in_record_id = isset($park_info_car111['record_id']) ? intval($park_info_car111['record_id']) : 0;
                            $inAccessTime = isset($park_info_car111['accessTime']) ? intval($park_info_car111['accessTime']) : 0;
                            !$inAccessTime && $inAccessTime = isset($imageToJob['in_time']) ? intval($imageToJob['in_time']) : 0;
                            if ($isImageToJob && $in_record_id) {
                                $imageToJob['in_record_id'] = $in_record_id;
                            }
                            if (!isset($car_info['parking_car_type']) || empty($car_info['parking_car_type'])) {
                                $db_house_village_park_charge_cartype = new HouseVillageParkChargeCartype();
                                $car_types = $db_house_village_park_charge_cartype->getFind(['village_id' => $passage_info['village_id'], 'car_number_type' => $car_number_type, 'status' => 1, 'park_sys_type' => 'A11'], 'car_type');
                                if (!empty($car_types)) {
                                    $car_info['parking_car_type'] = $car_types['car_type'];
                                } else {
                                    $car_info['parking_car_type'] = self::Temp_A;
                                }
                            }
                            $out_data['total'] = 0;
                            $out_data['pay_type'] = 'free';
                            $starttime = time() - 20;
                            $endtime = time() + 50;
                            $park_where = [
                                ['car_number', '=', $car_number],
                                ['park_id', '=', $passage_info['village_id']],
                                ['out_time', '>=', $starttime],
                                ['out_time', '<=', $endtime],
                            ];
                            $out_park_car = $db_out_park->getOne($park_where);
                            //写入车辆出场表
                            if (!isset($out_park_car['id']) || !$out_park_car['id']) {
                                if ($car_number) {
                                    fdump_api(['记录入场==' . __LINE__ => $car_number, 'out_data' => $out_data], 'park_temp/log_' . $car_number, 1);
                                }
                                $insert_id = $db_out_park->insertOne($out_data);
                            } else {
                                $insert_id = $out_park_car['id'];
                            }
                            if ($isImageToJob) {
                                $imageToJob['out_park_id'] = $insert_id;
                            }
                            $park_time_samll_tmps=0;
                            if (!empty($in_park_info)) {
                                $in_park_in_time=$in_park_info['in_time'];
                                if($in_park_in_time>0){
                                    $where_out = [];
                                    $where_out[]=['car_number','=',$car_number];
                                    $where_out[]=['park_id','=',$passage_info['village_id']];
                                    $where_out[]=['park_sys_type','=','A11'];
                                    $where_out[]=['is_out','=',1];
                                    $where_out[]=['del_time','=',0];
                                    $where_out[]=['in_time','>',$in_park_in_time];
                                    $fieldStr='id,car_number,in_time,in_channel_id,is_paid,park_id,out_time,park_time';
                                    $park_info1 = $db_in_park->getList($where_out,$fieldStr,1,100);
                                    $xtmp_park_time=0;   //之前小场停车时间
                                    if($park_info1 && !$park_info1->isEmpty()){
                                        $park_info1=$park_info1->toArray();
                                        foreach ($park_info1 as $pvv){
                                            if( $pvv['out_time'] > $pvv['in_time'] && $pvv['out_time'] < $now_time){
                                                $xtmp_park_time+=$pvv['park_time'];
                                            }
                                        }
                                    }
                                    $park_time_samll_tmps=$now_time-$in_park_in_time-$xtmp_park_time;
                                    $park_time_samll_tmps=$park_time_samll_tmps>0 ?$park_time_samll_tmps:0;
                                }
                                //写入车辆入场表
                                $db_in_park->saveOne(['id' => $in_park_info['id'], 'car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'park_sys_type' => 'A11', 'is_out' => 0], $park_data);
                                //把之前未出场的全部改成出场
                                $whereInPark = [];
                                $whereInPark[] = ['id', '<', $in_park_info['id']];
                                $whereInPark[] = ['car_number', '=', $car_number];
                                $whereInPark[] = ['park_id', '=', $passage_info['village_id']];
                                $whereInPark[] = ['park_sys_type', '=', 'A11'];
                                $whereInPark[] = ['is_out', '=', '0'];
                                $saveInPark = array('is_out' => 1,'park_time'=>$park_time_samll_tmps,'out_time' => $park_data['out_time']);
                                $db_in_park->saveOne($whereInPark, $saveInPark);
                            }
                            //加一条出场记录
                            $car_access_record = unsetParam($car_access_record, 'imageToJob');
                            $car_access_record['business_type'] = 0;
                            $car_access_record['business_id'] = $passage_info['village_id'];
                            $car_access_record['car_number'] = $car_number;
                            $car_access_record['accessType'] = 2;
                            $car_access_record['accessTime'] = $now_time;
                            $inAccessTime > 0 && $car_access_record['park_time'] = $now_time - $inAccessTime;
                            $car_access_record['accessMode'] = 3;
                            $car_access_record['park_sys_type'] = 'A11';
                            $car_access_record['park_car_type'] = $this->parking_a11_car_type_arr[$car_info['parking_car_type']];
                            $car_access_record['coupon_id'] = 0;
                            $car_access_record['park_time'] = $park_time_samll_tmps;
                            $car_access_record['park_id'] = $passage_info['village_id'];
                            $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                            $car_access_order_id = isset($park_info_car111['order_id']) && $park_info_car111['order_id'] ? $park_info_car111['order_id'] : '';
                            $car_access_record['order_id'] = $car_access_order_id;
                            $car_access_record['total'] = 0;
                            // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                            // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                            //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                            $car_access_record['pay_type'] = $this->pay_type[5];
                            $car_access_record['update_time'] = $now_time;
                            $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                            if (isset($insert_id)) {
                                $car_access_record['from_id'] = $insert_id;
                            }
                            $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                            
                            if(!empty($outRightPosition)) {
                                //释放车位
                                $whereArr = array('position_id' => $outRightPosition['position_id'], 'use_car_id' => $car_info['car_id']);
                                $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                                $this->updateParkingPositionUse($whereArr, $saveArr, $car_number);
                            }
                            //将此条之前的 都改掉
                            if($record_id>0 && $is_real_garage>0) {
                                $tmpUpdateRecordArr=array('car_number'=> $car_number,'in_park_id'=>$in_park_id,'village_id'=>$passage_info['village_id'],'record_id'=>$record_id,'access_order_id'=>$car_access_order_id,'access_type'=>2,'access_time'=>time());
                                (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                            }
                        } else {
                            $record_id = isset($park_info_car['record_id']) ? intval($park_info_car['record_id']) : 0;
                            $car_access_order_id = isset($park_info_car['order_id']) ? trim($park_info_car['order_id']) : '';
                        }
                        //发送模板消息
                        $openid = $this->getUserInfo($car_number, $passage_info['village_id']);
                        if (!empty($openid)) {
                            $hours = intval($park_time / 3600);
                            $mins = intval(($park_time - $hours * 3600) / 60);
                            $second = $park_time - $hours * 3600 - $mins * 60;
                            $time = '';
                            if ($hours > 0) {
                                $time .= $hours . '小时';
                            }
                            if ($mins > 0) {
                                $time .= $mins . '分钟';
                            }
                            if ($second > 0) {
                                $time .= $second . '秒';
                            }
                            $this->remarkTxt = '\n支付金额： 月租车出场';
                            $this->pay_price = '0';
                            $this->stored_balance = isset($car_info['stored_balance']) && $car_info['stored_balance'] ? $car_info['stored_balance'] : 0;
                            $this->sendCarMeassage($openid, $car_number, $car_info, $village_info, $garage_info, $passage_info, $time, $village_info['property_id'], $small_garage_info, $now_time, true);
                        }
                        $showscreen_data['car_type'] = 'month_type';
                        $showscreen_data['duration_txt'] = $time;
                        $showscreen_data['duration'] = $park_time;
                        $showscreen_data['price'] = 0;
                        $showscreen_data['voice_content'] = 4;
                        $showscreen_data['content'] = '';
                        $this->addParkShowScreenLog($showscreen_data);
                        if ($isImageToJob && ($record_id || $insert_id)) {
                            if (isset($car_access_order_id) && $car_access_order_id) {
                                $imageToJob['car_access_order_id'] = $car_access_order_id;
                            }
                            $imageToJob['out_record_id'] = $record_id;
                            (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                        }
                        return true;
                    }else{
                        $pay_data = [
                            'car_number' => $car_number,
                            'village_id' => $passage_info['village_id'],
                            'device_number' => $passage_info['device_number'],
                            'car_number_type'=>$car_number_type,
                            'car_end_time'=>0,
                        ];
                        if($car_info && isset($car_info['end_time'])){
                            $pay_data['car_end_time']=$car_info['end_time'];
                        }
                        $pay_money=$this->get_temp_pay($pay_data,$garage_id);
                        if ($car_number) {
                            fdump_api(['月租车 按照配置大场是否收费，小场费用收费计算='.__LINE__=>$car_number, 'pay_data' => $pay_data, 'pay_money' => $pay_money],'park_temp/log_'.$car_number,1);
                        }
                        $out_image_save=array();
                        if(isset($park_data['out_image_big'])){
                            $out_image_save['out_image_big']=$park_data['out_image_big'];
                        }
                        if(isset($park_data['out_image_small'])){
                            $out_image_save['out_image_small']=$park_data['out_image_small'];
                        }
                        if($out_image_save && !empty($in_park_info) && !empty($in_park_info['id'])){
                            $db_in_park->saveOne(['id'=>$in_park_info['id'],'car_number' => $car_number, 'park_id' => $passage_info['village_id']], $out_image_save);
                        }
                        if ($pay_money['pay_money']==0){
                            if ($car_number) {
                                fdump_api(['月租车 按照配置大场是否收费，收费为0='.__LINE__=>$car_number],'park_temp/log_'.$car_number,1);
                            }
                            if(!empty($in_park_info)) {
                                //写入车辆入场表
                                $db_in_park->saveOne(['id' => $in_park_info['id'], 'car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'park_sys_type' => 'A11', 'is_out' => 0], $park_data);
                                //把之前未出场的全部改成出场
                                $whereInPark = [];
                                $whereInPark[] = ['id', '<', $in_park_info['id']];
                                $whereInPark[] = ['car_number', '=', $car_number];
                                $whereInPark[] = ['park_id', '=', $passage_info['village_id']];
                                $whereInPark[] = ['park_sys_type', '=', 'A11'];
                                $whereInPark[] = ['is_out', '=', '0'];
                                $saveInPark = array('is_out' => 1, 'out_time' => $park_data['out_time']);
                                $db_in_park->saveOne($whereInPark, $saveInPark);
                            }
                            $out_data['total'] = $pay_money['pay_money'];
                            $out_data['pay_type'] = 'free';
                            $starttime = time() - 30;
                            $endtime = time() + 50;
                            $park_where = [
                                ['car_number', '=', $car_number],
                                ['park_id', '=', $passage_info['village_id']],
                                ['out_time', '>=', $starttime],
                                ['out_time', '<=', $endtime],
                            ];
                            $park_info_car = $db_out_park->getOne($park_where);
                            //写入车辆出场表
                            if (!isset($park_info_car['id']) || !$park_info_car['id']) {
                                if ($car_number) {
                                    fdump_api(['记录入场==' . __LINE__ => $car_number, 'out_data' => $out_data], 'park_temp/log_' . $car_number, 1);
                                }
                                $insert_id = $db_out_park->insertOne($out_data);
                            } else {
                                $insert_id = $park_info_car['id'];
                            }
                            if ($isImageToJob) {
                                $imageToJob['out_park_id'] = $insert_id;
                            }
                            if ($car_number) {
                                $park_info_car_arr = $park_info_car && !is_array($park_info_car) ? $park_info_car->toArray() : $park_info_car;
                                fdump_api(['修改记录[旧]=='.__LINE__=>$car_number, 'park_info_car' => $park_info_car_arr, 'out_data' => $out_data],'park_temp/log_'.$car_number,1);
                            } elseif ($parkTime) {
                                fdump_api(['车辆通行'=>$car_number,'park_data' => $park_data],'a11_park/plateresult'.$parkTime,true);
                            }
                            $starttime = time() - 30;
                            $endtime = time() + 50;
                            $park_where = [
                                ['car_number', '=', $car_number],
                                ['park_id', '=', $passage_info['village_id']],
                                ['accessType', '=',2],
                                ['accessTime', '>=', $starttime],
                                ['accessTime', '<=', $endtime],
                            ];
                            $park_info_car = $db_house_village_car_access_record->getOne($park_where);
                            if ($car_number) {
                                $park_info_car_arr = $park_info_car && !is_array($park_info_car) ? $park_info_car->toArray() : $park_info_car;
                                fdump_api(['修改记录[新]=='.__LINE__=>$car_number, 'park_info_car' => $park_info_car_arr, 'park_where' => $park_where],'park_temp/log_'.$car_number,1);
                            }
                            //写入车辆出场表
                            if (empty($park_info_car)) {
                                $park_where = [
                                    ['car_number', '=', $car_number],
                                    ['park_id', '=', $passage_info['village_id']],
                                    ['accessType', '=',1],
                                    ['is_out', '=', 0],
                                ];
                                $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                                $in_record_id = isset($park_info_car111['record_id']) ? intval($park_info_car111['record_id']) : 0;
                                $inAccessTime = isset($park_info_car111['accessTime']) ? intval($park_info_car111['accessTime']) : 0;
                                !$inAccessTime && $inAccessTime = isset($imageToJob['in_time']) ? intval($imageToJob['in_time']) : 0;
                                if ($isImageToJob && $in_record_id) {
                                    $imageToJob['in_record_id'] = $in_record_id;
                                }
                                if (!isset($car_info['parking_car_type'])||empty($car_info['parking_car_type'])){
                                    $db_house_village_park_charge_cartype=new HouseVillageParkChargeCartype();
                                    $car_types=$db_house_village_park_charge_cartype->getFind(['village_id'=>$passage_info['village_id'],'car_number_type'=>$car_number_type,'status'=>1,'park_sys_type'=>'A11'],'car_type');
                                    if (!empty($car_types)){
                                        $car_info['parking_car_type']=$car_types['car_type'];
                                    }else{
                                        $car_info['parking_car_type']=self::Temp_A;
                                    }
                                }
                                $car_access_record = unsetParam($car_access_record, 'imageToJob');
                                $car_access_record['business_type'] = 0;
                                $car_access_record['business_id'] = $passage_info['village_id'];
                                $car_access_record['car_number'] = $car_number;
                                $car_access_record['accessType'] = 2;
                                $car_access_record['accessTime'] = $now_time;
                                $inAccessTime > 0 && $car_access_record['park_time'] = $now_time - $inAccessTime;
                                $car_access_record['accessMode'] = 3;
                                $car_access_record['park_sys_type'] = 'A11';
                                $car_access_record['park_car_type'] = $this->parking_a11_car_type_arr[$car_info['parking_car_type']];
                                $car_access_record['coupon_id'] = $pay_money['coupon_id'];
                                $car_access_record['park_id'] = $passage_info['village_id'];
                                $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                                $car_access_order_id = isset($park_info_car111['order_id']) && $park_info_car111['order_id'] ? $park_info_car111['order_id'] : '';
                                $car_access_record['order_id'] = $car_access_order_id;
                                $car_access_record['total'] = $pay_money['parkTemp_money'];
                                // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                                // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                                //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                                $car_access_record['pay_type'] = $this->pay_type[5];
                                if($car_access_record['total']>0){
                                    $car_access_record['pay_type'] = $this->pay_type[6];
                                }
                                $car_access_record['update_time'] = $now_time;
                                $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                                if (isset($insert_id)) {
                                    $car_access_record['from_id'] = $insert_id;
                                }
                                $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                                if(!empty($outRightPosition)) {
                                    //释放车位
                                    $whereArr = array('position_id' => $outRightPosition['position_id'], 'use_car_id' => $car_info['car_id']);
                                    $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                                    $this->updateParkingPositionUse($whereArr, $saveArr, $car_number);
                                }
                                //将此条之前的 都改掉
                                if($record_id>0 && $is_real_garage>0) {
                                    $tmpUpdateRecordArr=array('car_number'=> $car_number,'in_park_id'=>$in_park_id,'village_id'=>$passage_info['village_id'],'record_id'=>$record_id,'access_order_id'=>$car_access_order_id,'access_type'=>2,'access_time'=>time());
                                    (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);

                                }
                                if ($car_number) {
                                    $park_info_car111_arr = $park_info_car111 && !is_array($park_info_car111) ? $park_info_car111->toArray() : $park_info_car111;
                                    fdump_api(['修改记录[新]=='.__LINE__=>$car_number, 'park_info_car111' => $park_info_car111_arr,'car_access_record' => $car_access_record,'record_id' => $record_id],'park_temp/log_'.$car_number,1);
                                } elseif ($parkTime) {
                                    fdump_api(['车辆通行'=>$car_number,'park_info_car111' => $park_info_car111,'car_access_record' => $car_access_record,'record_id' => $record_id],'a11_park/plateresult'.$parkTime,true);
                                }
                            } else {
                                $record_id = isset($park_info_car['record_id']) ? intval($park_info_car['record_id']) : 0;
                                $car_access_order_id = isset($park_info_car['order_id']) ? trim($park_info_car['order_id']) : '';
                            }
                            //TODO:屏显和语音提示车辆通行
                            $showscreen_data['car_type']= 'temporary_type';
                            $showscreen_data['duration_txt']= $pay_money['time'];
                            $showscreen_data['duration']= $pay_money['park_time'];
                            $showscreen_data['price']= 0;
                            if ($car_number) {
                                fdump_api(['屏显和语音提示车辆通行[新]=='.__LINE__=>$car_number, 'showscreen_data' => $showscreen_data],'park_temp/log_'.$car_number,1);
                            } elseif ($parkTime) {
                                fdump_api(['车辆通行'=>$car_number,'showscreen_data' => $showscreen_data],'a11_park/plateresult'.$parkTime,true);
                            }
                            $this->addParkShowScreenLog($showscreen_data);
                            //发送模板消息
                            $openid=$this->getUserInfo($car_number,$passage_info['village_id']);
                            if (!empty($openid)){
                                $this->remarkTxt      = '\n支付金额： 0';
                                $this->pay_price = '0';
                                $this->stored_balance = isset($car_info['stored_balance']) && $car_info['stored_balance'] ? $car_info['stored_balance'] : 0;
                                $this->sendCarMeassage($openid, $car_number, $car_info, $village_info, $garage_info, $passage_info, $pay_money['time'], $village_info['property_id'], $small_garage_info,$now_time, true);
                            }
                            //车辆通行
                            if ($isImageToJob && ($record_id || $insert_id)) {
                                if (isset($car_access_order_id) && $car_access_order_id) {
                                    $imageToJob['car_access_order_id'] = $car_access_order_id;
                                }
                                $imageToJob['out_record_id'] = $record_id;
                                (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                            }
                            return true;
                        }
                        elseif ($pay_money['pay_money']>0&&!empty($car_info)&&$car_info['stored_balance']>$pay_money['pay_money']){
                            if ($car_number) {
                                fdump_api(['月租车 按照配置大场是否收费，小场费用收费由储值余额抵扣='.__LINE__=>$car_number],'park_temp/log_'.$car_number,1);
                            }
                            if(!empty($in_park_info)) {
                                $db_in_park->saveOne(['id' => $in_park_info['id'], 'car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'park_sys_type' => 'A11', 'is_out' => 0], $park_data);
                                //把之前未出场的全部改成出场
                                $whereInPark = [];
                                $whereInPark[] = ['id', '<', $in_park_info['id']];
                                $whereInPark[] = ['car_number', '=', $car_number];
                                $whereInPark[] = ['park_id', '=', $passage_info['village_id']];
                                $whereInPark[] = ['park_sys_type', '=', 'A11'];
                                $whereInPark[] = ['is_out', '=', '0'];
                                $saveInPark = array('is_out' => 1, 'out_time' => $park_data['out_time']);
                            }
                            $db_in_park->saveOne($whereInPark,$saveInPark);
                            $out_data['total'] = $pay_money['pay_money'];
                            $out_data['pay_type'] = 'wallet';
                            $starttime = time() - 30;
                            $endtime = time() + 50;
                            $park_where = [
                                ['car_number', '=', $car_number],
                                ['park_id', '=', $passage_info['village_id']],
                                ['out_time', '>=', $starttime],
                                ['out_time', '<=', $endtime],
                            ];
                            $park_info_car = $db_out_park->getOne($park_where);
                            //写入车辆入场表
                            if (!isset($park_info_car['id']) || !$park_info_car['id']) {
                                if ($car_number) {
                                    fdump_api(['记录入场==' . __LINE__ => $car_number, 'out_data' => $out_data], 'park_temp/log_' . $car_number, 1);
                                }
                                $insert_id = $db_out_park->insertOne($out_data);
                            } else {
                                $insert_id = $park_info_car['id'];
                            }
                            if ($isImageToJob) {
                                $imageToJob['out_park_id'] = $insert_id;
                            }
                            if ($car_number) {
                                $park_info_car_arr = $park_info_car && !is_array($park_info_car) ? $park_info_car->toArray() : $park_info_car;
                                fdump_api(['车辆出场记录[旧]=='.__LINE__=>$car_number, 'park_info_car' => $park_info_car_arr, 'out_data' => $out_data],'park_temp/log_'.$car_number,1);
                            } elseif ($parkTime) {
                                fdump_api(['车辆通行'=>$car_number,'park_data' => $park_data],'a11_park/plateresult'.$parkTime,true);
                            }
                            $starttime = time() - 30;
                            $endtime = time() + 50;
                            $park_where = [
                                ['car_number', '=', $car_number],
                                ['park_id', '=', $passage_info['village_id']],
                                ['accessType', '=',2],
                                ['accessTime', '>=', $starttime],
                                ['accessTime', '<=', $endtime],
                            ];
                            $park_info_car = $db_house_village_car_access_record->getOne($park_where);
                            if ($car_number) {
                                $park_info_car_arr = $park_info_car && !is_array($park_info_car) ? $park_info_car->toArray() : $park_info_car;
                                fdump_api(['车辆出场记录[新]=='.__LINE__=>$car_number, 'park_info_car' => $park_info_car_arr, 'out_data' => $out_data],'park_temp/log_'.$car_number,1);
                            }
                            //写入车辆入场表
                            if (empty($park_info_car)) {
                                $park_where = [
                                    ['car_number', '=', $car_number],
                                    ['park_id', '=', $passage_info['village_id']],
                                    ['accessType', '=',1],
                                    ['is_out', '=', 0],
                                ];
                                $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                                $in_record_id = isset($park_info_car111['record_id']) ? intval($park_info_car111['record_id']) : 0;
                                $inAccessTime = isset($park_info_car111['accessTime']) ? intval($park_info_car111['accessTime']) : 0;
                                !$inAccessTime && $inAccessTime = isset($imageToJob['in_time']) ? intval($imageToJob['in_time']) : 0;
                                if ($isImageToJob && $in_record_id) {
                                    $imageToJob['in_record_id'] = $in_record_id;
                                }
                                if (!isset($car_info['parking_car_type'])||empty($car_info['parking_car_type'])){
                                    $db_house_village_park_charge_cartype=new HouseVillageParkChargeCartype();
                                    $car_types=$db_house_village_park_charge_cartype->getFind(['village_id'=>$passage_info['village_id'],'car_number_type'=>$car_number_type,'status'=>1,'park_sys_type'=>'A11'],'car_type');
                                    if (!empty($car_types)){
                                        $car_info['parking_car_type']=$car_types['car_type'];
                                    }else{
                                        $car_info['parking_car_type']=self::Temp_A;
                                    }
                                }
                                $car_access_record = unsetParam($car_access_record, 'imageToJob');
                                $car_access_record['business_type'] = 0;
                                $car_access_record['business_id'] = $passage_info['village_id'];
                                $car_access_record['car_number'] = $car_number;
                                $car_access_record['accessType'] = 2;
                                $car_access_record['accessTime'] = $now_time;
                                $inAccessTime > 0 && $car_access_record['park_time'] = $now_time - $inAccessTime;
                                $car_access_record['car_type'] = 'storedCar';
                                $car_access_record['user_name'] = $car_info['car_user_name'];
                                $car_access_record['user_phone'] = $car_info['car_user_phone'];
                                $car_access_record['park_sys_type'] = 'A11';
                                $car_access_record['park_car_type'] = $this->parking_a11_car_type_arr[$car_info['parking_car_type']];
                                $car_access_record['coupon_id'] = $pay_money['coupon_id'];
                                $car_access_record['park_id'] = $passage_info['village_id'];
                                $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                                $car_access_order_id = isset($park_info_car111['order_id']) && $park_info_car111['order_id'] ? $park_info_car111['order_id'] : '';
                                $car_access_record['order_id'] = $car_access_order_id;
                                $car_access_record['total'] = $pay_money['pay_money'];
                                $car_access_record['stored_balance'] = getFormatNumber($car_info['stored_balance']-$pay_money['pay_money']);
                                // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                                // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                                //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                                $car_access_record['pay_type'] = $this->pay_type[1];
                                $car_access_record['update_time'] = $now_time;
                                $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                                if (isset($insert_id)) {
                                    $car_access_record['from_id'] = $insert_id;
                                }
                                $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                                if(!empty($outRightPosition)){
                                    //释放车位
                                    $whereArr=array('position_id'=>$outRightPosition['position_id'],'use_car_id'=>$car_info['car_id']);
                                    $saveArr=array('use_car_id'=>0,'use_start_time'=>0,'use_car_number'=>'','access_record_id'=>$record_id);
                                    $this->updateParkingPositionUse($whereArr,$saveArr,$car_number);
                                }
                                if ($record_id>0){
                                    if ($is_real_garage>0) {
                                        //将此条之前的 都改掉

                                        $tmpUpdateRecordArr=array('car_number'=> $car_number,'in_park_id'=>$in_park_id,'village_id'=>$passage_info['village_id'],'record_id'=>$record_id,'access_order_id'=>$car_access_order_id,'access_type'=>2,'access_time'=>time());
                                        (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                                    }
                                    $db_house_village_parking_car->editHouseVillageParkingCar(['province' => $province, 'car_number' => $car_no,'village_id'=>$passage_info['village_id']],['stored_balance'=>$car_access_record['stored_balance']]);
                                    
                                }
                                fdump_api(['对应车辆储值余额变动'=>$car_number,'stored_balance' => $car_info['stored_balance'],'pay_money' => $pay_money['pay_money'],'stored_balance1' => $car_access_record['stored_balance']], 'park_temp/stored_balance_' . $car_number, 1);
                                if ($car_number) {
                                    $park_info_car111_arr = $park_info_car111 && !is_array($park_info_car111) ? $park_info_car111->toArray() : $park_info_car111;
                                    fdump_api(['添加车辆出场记录[新]=='.__LINE__=>$car_number,'park_info_car111' => $park_info_car111_arr,'car_access_record' => $car_access_record,'record_id' => $record_id],'park_temp/log_'.$car_number,1);
                                } elseif ($parkTime) {
                                    fdump_api(['车辆通行'=>$car_number,'park_info_car111' => $park_info_car111,'car_access_record' => $car_access_record,'record_id' => $record_id],'a11_park/plateresult'.$parkTime,true);
                                }
                            } else {
                                $record_id = isset($park_info_car['record_id']) ? intval($park_info_car['record_id']) : 0;
                                $car_access_order_id = isset($park_info_car['order_id']) ? trim($park_info_car['order_id']) : '';
                            }
                            //TODO:屏显和语音提示车辆通行
                            $showscreen_data['car_type']= 'temporary_type';
                            $showscreen_data['duration_txt']= $pay_money['time'];
                            $showscreen_data['duration']= $pay_money['park_time'];
                            $showscreen_data['price']= $pay_money['pay_money'];
                            $showscreen_data['voice_content']= 9;
                            $showscreen_data['content']= '';
                            if (isset($car_access_record['stored_balance'])) {
                                $showscreen_data['stored_balance'] = $car_access_record['stored_balance'];
                                $showscreen_data['car_type'] = 'storedCar';
                            }
                            if ($car_number) {
                                fdump_api(['屏显和语音提示车辆通行[新]=='.__LINE__=>$car_number,'showscreen_data' => $showscreen_data],'park_temp/log_'.$car_number,1);
                            } elseif ($parkTime) {
                                fdump_api(['车辆通行'=>$car_number,'showscreen_data' => $showscreen_data],'a11_park/plateresult'.$parkTime,true);
                            }
                            $this->addParkShowScreenLog($showscreen_data);
                            //发送模板消息
                            $openid=$this->getUserInfo($car_number,$passage_info['village_id']);
                            if (!empty($openid)){
                                $this->remarkTxt = '\n支付金额：'.$pay_money['pay_money'].'元';
                                $this->pay_price = $pay_money['pay_money'];
                                if (isset($car_access_record['stored_balance'])) {
                                    $this->stored_balance = $car_access_record['stored_balance'];
                                    $this->remarkTxt .= '（储值抵扣）'; 
                                } else {
                                    $this->stored_balance = isset($car_info['stored_balance']) && $car_info['stored_balance'] ? $car_info['stored_balance'] : 0;
                                }
                                $this->sendCarMeassage($openid, $car_number, $car_info, $village_info, $garage_info, $passage_info, $pay_money['time'], $village_info['property_id'], $small_garage_info,$now_time, true);
                            }
                            //车辆通行
                            if ($isImageToJob && ($record_id || $insert_id)) {
                                if (isset($car_access_order_id) && $car_access_order_id) {
                                    $imageToJob['car_access_order_id'] = $car_access_order_id;
                                }
                                $imageToJob['out_record_id'] = $record_id;
                                (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                            }
                            return true;
                        }
                        else {
                            //TODO:屏显和语音提示错误信息
                            $showscreen_data['voice_content']= 5;
                            $showscreen_data['content']= '';
                            $showscreen_data['car_type']= 'temporary_type';
                            $showscreen_data['duration_txt']= $pay_money['time'];
                            $showscreen_data['duration']= $pay_money['park_time'];
                            $showscreen_data['price']= $pay_money['pay_money'];
                            if ($car_number) {
                                fdump_api(['屏显和语音提示车辆收费[新]=='.__LINE__=>$car_number,'showscreen_data' => $showscreen_data],'park_temp/log_'.$car_number,1);
                            } elseif ($parkTime) {
                                fdump_api(['屏显和语音提示错误信息'=>$car_number,'showscreen_data' => $showscreen_data],'a11_park/plateresult'.$parkTime,true);
                            }
                            $this->addParkShowScreenLog($showscreen_data);
                            //需缴费通行
                            if ($parkTime) {
                                fdump_api(['屏显和语音提示错误信息'=>$car_number,'data' => $data],'a11_park/plateresult'.$parkTime,true);
                            }
                            return false;
                        }
                    }
                }else{
                    //查询车辆在小场的类型 临时车直接计算停车费用
                    $pay_data = [
                        'car_number' => $car_number,
                        'village_id' => $passage_info['village_id'],
                        'device_number' => $passage_info['device_number'],
                        'car_number_type'=>$car_number_type,
                        'car_end_time'=>0,
                        ];
                   if($car_info && isset($car_info['end_time'])){
                       $pay_data['car_end_time']=$car_info['end_time'];
                    }
                    $pay_money=$this->get_temp_pay($pay_data,$garage_id);
                    $out_image_save=array();
                    if(isset($park_data['out_image_big'])){
                        $out_image_save['out_image_big']=$park_data['out_image_big'];
                    }
                    if(isset($park_data['out_image_small'])){
                        $out_image_save['out_image_small']=$park_data['out_image_small'];
                    }
                    if($out_image_save && !empty($in_park_info) && !empty($in_park_info['id'])){
                        $db_in_park->saveOne(['id'=>$in_park_info['id'],'car_number' => $car_number, 'park_id' => $passage_info['village_id']], $out_image_save);
                    }
                    
                    if ($car_number) {
                        fdump_api(['临时车 费用收费计算='.__LINE__=>$car_number, 'pay_data' => $pay_data, 'pay_money' => $pay_money],'park_temp/log_'.$car_number,1);
                    }
                    if ($pay_money['pay_money']==0){
                        if ($car_number) {
                            fdump_api(['临时车 费用收费0='.__LINE__=>$car_number],'park_temp/log_'.$car_number,1);
                        }
                        if(!empty($in_park_info)) {
                            //写入车辆入场表
                            $db_in_park->saveOne(['id' => $in_park_info['id'], 'car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'park_sys_type' => 'A11', 'is_out' => 0], $park_data);
                            //把之前未出场的全部改成出场
                            $whereInPark = [];
                            $whereInPark[] = ['id', '<', $in_park_info['id']];
                            $whereInPark[] = ['car_number', '=', $car_number];
                            $whereInPark[] = ['park_id', '=', $passage_info['village_id']];
                            $whereInPark[] = ['park_sys_type', '=', 'A11'];
                            $whereInPark[] = ['is_out', '=', '0'];
                            $saveInPark = array('is_out' => 1, 'out_time' => $park_data['out_time']);
                            $db_in_park->saveOne($whereInPark, $saveInPark);
                        }
                        $out_data['total'] = $pay_money['pay_money'];
                        $out_data['pay_type'] = 'free';
                        $starttime = time() - 30;
                        $endtime = time() + 50;
                        $park_where = [
                            ['car_number', '=', $car_number],
                            ['park_id', '=', $passage_info['village_id']],
                            ['out_time', '>=', $starttime],
                            ['out_time', '<=', $endtime],
                        ];
                        $park_info_car = $db_out_park->getOne($park_where);
                        //写入车辆入场表
                        if (!isset($park_info_car['id']) || !$park_info_car['id']) {
                            $insert_id = $db_out_park->insertOne($out_data);
                        } else {
                            $insert_id = $park_info_car['id'];
                        }
                        if ($isImageToJob) {
                            $imageToJob['out_park_id'] = $insert_id;
                        }
                        if ($car_number) {
                            $park_info_car_arr = $park_info_car && !is_array($park_info_car) ? $park_info_car->toArray() : $park_info_car;
                            fdump_api(['出场记录[旧]='.__LINE__=>$car_number, 'park_info_car' => $park_info_car_arr, 'out_data' => $out_data],'park_temp/log_'.$car_number,1);
                        } elseif ($parkTime) {
                            fdump_api(['车辆通行'=>$car_number,'park_data' => $park_data],'a11_park/plateresult'.$parkTime,true);
                        }
                        $starttime = time() - 30;
                        $endtime = time() + 50;
                        $park_where = [
                            ['car_number', '=', $car_number],
                            ['park_id', '=', $passage_info['village_id']],
                            ['accessType', '=',2],
                            ['accessTime', '>=', $starttime],
                            ['accessTime', '<=', $endtime],
                        ];
                        $park_info_car = $db_house_village_car_access_record->getOne($park_where);
                        if ($car_number) {
                            $park_info_car_arr = $park_info_car && !is_array($park_info_car) ? $park_info_car->toArray() : $park_info_car;
                            fdump_api(['出场记录[新]='.__LINE__=>$car_number, 'park_info_car' => $park_info_car_arr, 'out_data' => $out_data],'park_temp/log_'.$car_number,1);
                        }
                        //写入车辆入场表
                        if (empty($park_info_car)) {
                            $park_where = [
                                ['car_number', '=', $car_number],
                                ['park_id', '=', $passage_info['village_id']],
                                ['accessType', '=',1],
                                ['is_out', '=', 0],
                            ];
                            $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                            $in_record_id = isset($park_info_car111['record_id']) ? intval($park_info_car111['record_id']) : 0;
                            $inAccessTime = isset($park_info_car111['accessTime']) ? intval($park_info_car111['accessTime']) : 0;
                            !$inAccessTime && $inAccessTime = isset($imageToJob['in_time']) ? intval($imageToJob['in_time']) : 0;
                            if ($isImageToJob && $in_record_id) {
                                $imageToJob['in_record_id'] = $in_record_id;
                            }
                            if (!isset($car_info['parking_car_type'])||empty($car_info['parking_car_type'])){
                                $db_house_village_park_charge_cartype=new HouseVillageParkChargeCartype();
                                $car_types=$db_house_village_park_charge_cartype->getFind(['village_id'=>$passage_info['village_id'],'car_number_type'=>$car_number_type,'status'=>1,'park_sys_type'=>'A11'],'car_type');
                                if (!empty($car_types)){
                                    $car_info['parking_car_type']=$car_types['car_type'];
                                }else{
                                    $car_info['parking_car_type']=self::Temp_A;
                                }
                            }
                            $car_access_record = unsetParam($car_access_record, 'imageToJob');
                            $car_access_record['business_type'] = 0;
                            $car_access_record['business_id'] = $passage_info['village_id'];
                            $car_access_record['car_number'] = $car_number;
                            $car_access_record['accessType'] = 2;
                            $car_access_record['accessTime'] = $now_time;
                            $inAccessTime > 0 && $car_access_record['park_time'] = $now_time - $inAccessTime;
                            $car_access_record['accessMode'] = 3;
                            $car_access_record['park_sys_type'] = 'A11';
                            $car_access_record['park_car_type'] = $this->parking_a11_car_type_arr[$car_info['parking_car_type']];
                            $car_access_record['coupon_id'] =$pay_money['coupon_id'];
                            $car_access_record['park_id'] = $passage_info['village_id'];
                            $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                            $car_access_order_id = isset($park_info_car111['order_id']) && $park_info_car111['order_id'] ? $park_info_car111['order_id'] : '';
                            $car_access_record['order_id'] = $car_access_order_id;
                            $car_access_record['total'] = $pay_money['parkTemp_money'];
                            // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                            // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                            //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                            $car_access_record['pay_type'] = $this->pay_type[5];
                            if($car_access_record['total']>0){
                                $car_access_record['pay_type'] = $this->pay_type[6];
                            }
                            $car_access_record['update_time'] = $now_time;
                            $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                            if (isset($insert_id)) {
                                $car_access_record['from_id'] = $insert_id;
                            }
                            $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                            if(!empty($outRightPosition)) {
                                //释放车位
                                $whereArr = array('position_id' => $outRightPosition['position_id'], 'use_car_id' => $car_info['car_id']);
                                $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                                $this->updateParkingPositionUse($whereArr, $saveArr, $car_number);
                            }
                            if ($record_id>0 && $is_real_garage>0) {
                                //将此条之前的 都改掉

                                $tmpUpdateRecordArr=array('car_number'=> $car_number,'in_park_id'=>$in_park_id,'village_id'=>$passage_info['village_id'],'record_id'=>$record_id,'access_order_id'=>$car_access_order_id,'access_type'=>2,'access_time'=>time());
                                (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);

                            }
                            if ($car_number) {
                                $park_info_car111_arr = $park_info_car111 && !is_array($park_info_car111) ? $park_info_car111->toArray() : $park_info_car111;
                                fdump_api(['出场记录[新]='.__LINE__=>$car_number, 'park_info_car111' => $park_info_car111_arr,'car_access_record' => $car_access_record,'record_id' => $record_id],'park_temp/log_'.$car_number,1);
                            } elseif ($parkTime) {
                                fdump_api(['车辆通行'=>$car_number,'park_info_car111' => $park_info_car111,'car_access_record' => $car_access_record,'record_id' => $record_id],'A11Park/plateresult'.$parkTime,true);
                            }
                        } else {
                            $record_id = isset($park_info_car['record_id']) ? intval($park_info_car['record_id']) : 0;
                            $car_access_order_id = isset($park_info_car['order_id']) ? trim($park_info_car['order_id']) : '';
                        }
                        //TODO:屏显和语音提示车辆通行
                        $showscreen_data['car_type']= 'temporary_type';
                        $showscreen_data['duration_txt']= $pay_money['time'];
                        $showscreen_data['duration']= $pay_money['park_time'];
                        $showscreen_data['price']= 0;
                        if ($car_number) {
                            fdump_api(['出场记录[新]='.__LINE__=>$car_number, 'showscreen_data' => $showscreen_data],'park_temp/log_'.$car_number,1);
                        } elseif ($parkTime) {
                            fdump_api(['车辆通行'=>$car_number,'showscreen_data' => $showscreen_data],'a11_park/plateresult'.$parkTime,true);
                        }
                        $this->addParkShowScreenLog($showscreen_data);
                        //发送模板消息
                        $openid=$this->getUserInfo($car_number,$passage_info['village_id']);
                        if (!empty($openid)){
                            $this->remarkTxt      = '\n支付金额： 0';
                            $this->pay_price = '0';
                            $this->stored_balance = isset($car_info['stored_balance']) && $car_info['stored_balance'] ? $car_info['stored_balance'] : 0;
                            $this->sendCarMeassage($openid, $car_number, $car_info, $village_info, $garage_info, $passage_info, $pay_money['time'], $village_info['property_id'], $small_garage_info,$now_time, true);
                        }
                        //车辆通行
                        if ($isImageToJob && ($record_id || $insert_id)) {
                            if (isset($car_access_order_id) && $car_access_order_id) {
                                $imageToJob['car_access_order_id'] = $car_access_order_id;
                            }
                            $imageToJob['out_record_id'] = $record_id;
                            (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                        }
                        return true;
                    }
                    elseif ($pay_money['pay_money']>0&&!empty($car_info)&&$car_info['stored_balance']>$pay_money['pay_money']){
                        if ($car_number) {
                            fdump_api(['临时车 费用收费0='.__LINE__=>$car_number],'park_temp/log_'.$car_number,1);
                        }
                        if(!empty($in_park_info)) {
                            $db_in_park->saveOne(['id' => $in_park_info['id'], 'car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'park_sys_type' => 'A11', 'is_out' => 0], $park_data);
                            //把之前未出场的全部改成出场
                            $whereInPark = [];
                            $whereInPark[] = ['id', '<', $in_park_info['id']];
                            $whereInPark[] = ['car_number', '=', $car_number];
                            $whereInPark[] = ['park_id', '=', $passage_info['village_id']];
                            $whereInPark[] = ['park_sys_type', '=', 'A11'];
                            $whereInPark[] = ['is_out', '=', '0'];
                            $saveInPark = array('is_out' => 1, 'out_time' => $park_data['out_time']);
                            $db_in_park->saveOne($whereInPark, $saveInPark);
                        }
                        $out_data['total'] = $pay_money['pay_money'];
                        $out_data['pay_type'] = 'wallet';
                        $starttime = time() - 30;
                        $endtime = time() + 50;
                        $park_where = [
                            ['car_number', '=', $car_number],
                            ['park_id', '=', $passage_info['village_id']],
                            ['out_time', '>=', $starttime],
                            ['out_time', '<=', $endtime],
                        ];
                        $park_info_car = $db_out_park->getOne($park_where);
                        //写入车辆入场表
                        if (!isset($park_info_car['id']) || !$park_info_car['id']) {
                            $insert_id = $db_out_park->insertOne($out_data);
                        } else {
                            $insert_id = $park_info_car['id'];
                        }
                        if ($isImageToJob) {
                            $imageToJob['out_park_id'] = $insert_id;
                        }
                        if ($car_number) {
                            $park_info_car_arr = $park_info_car && !is_array($park_info_car) ? $park_info_car->toArray() : $park_info_car;
                            fdump_api(['出场记录[旧]='.__LINE__=>$car_number, 'park_info_car' => $park_info_car_arr,'out_data' => $out_data],'park_temp/log_'.$car_number,1);
                        } elseif ($parkTime) {
                            fdump_api(['车辆通行'=>$car_number,'park_data' => $park_data],'A11/plateresult'.$parkTime,true);
                        }
                        $starttime = time() - 30;
                        $endtime = time() + 50;
                        $park_where = [
                            ['car_number', '=', $car_number],
                            ['park_id', '=', $passage_info['village_id']],
                            ['accessType', '=',2],
                            ['accessTime', '>=', $starttime],
                            ['accessTime', '<=', $endtime],
                        ];
                        $park_info_car = $db_house_village_car_access_record->getOne($park_where);
                        if ($car_number) {
                            $park_info_car_arr = $park_info_car && !is_array($park_info_car) ? $park_info_car->toArray() : $park_info_car;
                            fdump_api(['出场记录[新]='.__LINE__=>$car_number, 'park_info_car' => $park_info_car_arr,'out_data' => $out_data],'park_temp/log_'.$car_number,1);
                        }
                        //写入车辆入场表
                        if (empty($park_info_car)) {
                            $park_where = [
                                ['car_number', '=', $car_number],
                                ['park_id', '=', $passage_info['village_id']],
                                ['accessType', '=',1],
                                ['is_out', '=', 0],
                            ];
                            $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                            $in_record_id = isset($park_info_car111['record_id']) ? intval($park_info_car111['record_id']) : 0;
                            $inAccessTime = isset($park_info_car111['accessTime']) ? intval($park_info_car111['accessTime']) : 0;
                            !$inAccessTime && $inAccessTime = isset($imageToJob['in_time']) ? intval($imageToJob['in_time']) : 0;
                            if ($isImageToJob && $in_record_id) {
                                $imageToJob['in_record_id'] = $in_record_id;
                            }
                            if (!isset($car_info['parking_car_type'])||empty($car_info['parking_car_type'])){
                                $db_house_village_park_charge_cartype=new HouseVillageParkChargeCartype();
                                $car_types=$db_house_village_park_charge_cartype->getFind(['village_id'=>$passage_info['village_id'],'car_number_type'=>$car_number_type,'status'=>1,'park_sys_type'=>'A11'],'car_type');
                                if (!empty($car_types)){
                                    $car_info['parking_car_type']=$car_types['car_type'];
                                }else{
                                    $car_info['parking_car_type']=self::Temp_A;
                                }
                            }
                            $car_access_record = unsetParam($car_access_record, 'imageToJob');
                            $car_access_record['business_type'] = 0;
                            $car_access_record['business_id'] = $passage_info['village_id'];
                            $car_access_record['car_number'] = $car_number;
                            $car_access_record['accessType'] = 2;
                            $car_access_record['accessTime'] = $now_time;
                            $inAccessTime > 0 && $car_access_record['park_time'] = $now_time - $inAccessTime;
                            $car_access_record['car_type'] = 'storedCar';
                            $car_access_record['user_name'] = $car_info['car_user_name'];
                            $car_access_record['user_phone'] = $car_info['car_user_phone'];
                            $car_access_record['park_sys_type'] = 'A11';
                            $car_access_record['park_car_type'] = $this->parking_a11_car_type_arr[$car_info['parking_car_type']];
                            $car_access_record['coupon_id'] = $pay_money['coupon_id'];
                            $car_access_record['park_id'] = $passage_info['village_id'];
                            $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                            $car_access_order_id = isset($park_info_car111['order_id']) && $park_info_car111['order_id'] ? $park_info_car111['order_id'] : '';
                            $car_access_record['order_id'] = $car_access_order_id;
                            $car_access_record['total'] = $pay_money['pay_money'];
                            $car_access_record['stored_balance'] = getFormatNumber($car_info['stored_balance']-$pay_money['pay_money']);
                            // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                            // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                            //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                            $car_access_record['pay_type'] = $this->pay_type[1];
                            $car_access_record['update_time'] = $now_time;
                            $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                            if (isset($insert_id)) {
                                $car_access_record['from_id'] = $insert_id;
                            }
                            $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                            if(!empty($outRightPosition)){
                                //释放车位
                                $whereArr=array('position_id'=>$outRightPosition['position_id'],'use_car_id'=>$car_info['car_id']);
                                $saveArr=array('use_car_id'=>0,'use_start_time'=>0,'use_car_number'=>'','access_record_id'=>$record_id);
                                $this->updateParkingPositionUse($whereArr,$saveArr,$car_number);
                            }
                            if ($record_id>0){
                                if ($is_real_garage>0) {
                                    //将此条之前的 都改掉

                                    $tmpUpdateRecordArr=array('car_number'=> $car_number,'in_park_id'=>$in_park_id,'village_id'=>$passage_info['village_id'],'record_id'=>$record_id,'access_order_id'=>$car_access_order_id,'access_type'=>2,'access_time'=>time());
                                    (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                                }
                                $db_house_village_parking_car->editHouseVillageParkingCar(['province' => $province, 'car_number' => $car_no,'village_id'=>$passage_info['village_id']],['stored_balance'=>$car_access_record['stored_balance']]);

                            }
                            fdump_api(['对应车辆储值余额变动'=>$car_number,'stored_balance' => $car_info['stored_balance'],'pay_money' => $pay_money['pay_money'],'stored_balance1' => $car_access_record['stored_balance']], 'park_temp/stored_balance_' . $car_number, 1);
                            if ($car_number) {
                                $park_info_car_arr = $park_info_car && !is_array($park_info_car) ? $park_info_car->toArray() : $park_info_car;
                                fdump_api(['出场记录[新]='.__LINE__=>$car_number, 'park_info_car' => $park_info_car_arr,'out_data' => $out_data],'park_temp/log_'.$car_number,1);
                            } elseif ($parkTime) {
                                fdump_api(['车辆通行'=>$car_number,'park_info_car111' => $park_info_car111,'car_access_record' => $car_access_record,'record_id' => $record_id],'a11_park/plateresult'.$parkTime,true);
                            }
                        } else {
                            $record_id = isset($park_info_car['record_id']) ? intval($park_info_car['record_id']) : 0;
                            $car_access_order_id = isset($park_info_car['order_id']) ? trim($park_info_car['order_id']) : '';
                        }
                        //TODO:屏显和语音提示车辆通行
                        $showscreen_data['car_type']= 'temporary_type';
                        $showscreen_data['duration_txt']= $pay_money['time'];
                        $showscreen_data['duration']= $pay_money['park_time'];
                        $showscreen_data['price']= $pay_money['pay_money'];
                        $showscreen_data['voice_content']= 9;
                        $showscreen_data['content']= '';
                        if (isset($car_access_record['stored_balance'])) {
                            $showscreen_data['stored_balance'] = $car_access_record['stored_balance'];
                            $showscreen_data['car_type'] = 'storedCar';
                        }
                        if ($car_number) {
                            fdump_api(['出场记录[新]='.__LINE__=>$car_number, 'showscreen_data' => $showscreen_data],'park_temp/log_'.$car_number,1);
                        } elseif ($parkTime) {
                            fdump_api(['车辆通行'=>$car_number,'showscreen_data' => $showscreen_data],'a11_park/plateresult'.$parkTime,true);
                        }
                        $this->addParkShowScreenLog($showscreen_data);
                        //发送模板消息
                        $openid=$this->getUserInfo($car_number,$passage_info['village_id']);
                        if (!empty($openid)){
                            $this->remarkTxt = '\n支付金额： '.$pay_money['pay_money'];
                            $this->pay_price = $pay_money['pay_money'];
                            if (isset($car_access_record['stored_balance'])) {
                                $this->stored_balance = $car_access_record['stored_balance'];
                                $this->remarkTxt .= '（储值抵扣）';
                            } else {
                                $this->stored_balance = isset($car_info['stored_balance']) && $car_info['stored_balance'] ? $car_info['stored_balance'] : 0;
                            }
                            $this->sendCarMeassage($openid, $car_number, $car_info, $village_info, $garage_info, $passage_info, $pay_money['time'], $village_info['property_id'], $small_garage_info,$now_time, true);
                        }
                        //车辆通行
                        if ($isImageToJob && ($record_id || $insert_id)) {
                            if (isset($car_access_order_id) && $car_access_order_id) {
                                $imageToJob['car_access_order_id'] = $car_access_order_id;
                            }
                            $imageToJob['out_record_id'] = $record_id;
                            (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                        }
                        return true;
                    }
                    else {
                        $coupon_info = $this->get_temp_pay_coupon($passage_info['village_id'], $car_number, 'A11', $pay_money['park_temp_id'],$uid);
                        if (!empty($coupon_info)) {
                            $coupon_id = $coupon_info['id'];
                            $payMoney = $pay_money['pay_money'];
                            $pay_money['pay_money'] = $pay_money['pay_money'] - $coupon_info['money'];
                            if ($pay_money['pay_money'] <= 0) {
                                $pay_money['pay_money'] = 0;
                            }
                            if ($pay_money['pay_money'] == 0) {
                                if ($car_number) {
                                    fdump_api(['优惠券抵扣金额后为0=' . __LINE__ => $car_number, 'payMoney' => $payMoney, 'coupon_info' => $coupon_info], 'park_temp/log_' . $car_number, 1);
                                }
                                if (!empty($in_park_info)) {
                                    //写入车辆入场表
                                    $db_in_park->saveOne(['id' => $in_park_info['id'], 'car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'park_sys_type' => 'A11', 'is_out' => 0], $park_data);
                                    //把之前未出场的全部改成出场
                                    $whereInPark = [];
                                    $whereInPark[] = ['id', '<', $in_park_info['id']];
                                    $whereInPark[] = ['car_number', '=', $car_number];
                                    $whereInPark[] = ['park_id', '=', $passage_info['village_id']];
                                    $whereInPark[] = ['park_sys_type', '=', 'A11'];
                                    $whereInPark[] = ['is_out', '=', '0'];
                                    $saveInPark = array('is_out' => 1, 'out_time' => $park_data['out_time']);
                                    $db_in_park->saveOne($whereInPark, $saveInPark);
                                }
                                $out_data['total'] = $pay_money['pay_money'];
                                $out_data['pay_type'] = 'free';
                                $starttime = time() - 30;
                                $endtime = time() + 50;
                                $park_where = [
                                    ['car_number', '=', $car_number],
                                    ['park_id', '=', $passage_info['village_id']],
                                    ['out_time', '>=', $starttime],
                                    ['out_time', '<=', $endtime],
                                ];
                                $park_info_car = $db_out_park->getOne($park_where);
                                //写入车辆入场表
                                if (!isset($park_info_car['id']) || !$park_info_car['id']) {
                                    $insert_id = $db_out_park->insertOne($out_data);
                                } else {
                                    $insert_id = $park_info_car['id'];
                                }
                                if ($isImageToJob) {
                                    $imageToJob['out_park_id'] = $insert_id;
                                }
                                if ($car_number) {
                                    $park_info_car_arr = $park_info_car && !is_array($park_info_car) ? $park_info_car->toArray() : $park_info_car;
                                    fdump_api(['出场记录(旧)=' . __LINE__ => $car_number, 'park_info_car' => $park_info_car_arr, 'out_data' => $out_data], 'park_temp/log_' . $car_number, 1);
                                } elseif ($parkTime) {
                                    fdump_api(['车辆通行' => $car_number, 'park_data' => $park_data], 'a11_park/plateresult' . $parkTime, true);
                                }
                                $starttime = time() - 30;
                                $endtime = time() + 50;
                                $park_where = [
                                    ['car_number', '=', $car_number],
                                    ['park_id', '=', $passage_info['village_id']],
                                    ['accessType', '=', 2],
                                    ['accessTime', '>=', $starttime],
                                    ['accessTime', '<=', $endtime],
                                ];
                                $park_info_car = $db_house_village_car_access_record->getOne($park_where);
                                if ($car_number) {
                                    $park_info_car_arr = $park_info_car && !is_array($park_info_car) ? $park_info_car->toArray() : $park_info_car;
                                    fdump_api(['出场记录(新)=' . __LINE__ => $car_number, 'park_info_car' => $park_info_car_arr], 'park_temp/log_' . $car_number, 1);
                                }
                                //写入车辆入场表
                                if (empty($park_info_car)) {
                                    $park_where = [
                                        ['car_number', '=', $car_number],
                                        ['park_id', '=', $passage_info['village_id']],
                                        ['accessType', '=', 1],
                                        ['is_out', '=', 0],
                                    ];
                                    $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                                    $in_record_id = isset($park_info_car111['record_id']) ? intval($park_info_car111['record_id']) : 0;
                                    $inAccessTime = isset($park_info_car111['accessTime']) ? intval($park_info_car111['accessTime']) : 0;
                                    !$inAccessTime && $inAccessTime = isset($imageToJob['in_time']) ? intval($imageToJob['in_time']) : 0;
                                    if ($isImageToJob && $in_record_id) {
                                        $imageToJob['in_record_id'] = $in_record_id;
                                    }

                                    if (!isset($car_info['parking_car_type']) || empty($car_info['parking_car_type'])) {
                                        $db_house_village_park_charge_cartype = new HouseVillageParkChargeCartype();
                                        $car_types = $db_house_village_park_charge_cartype->getFind(['village_id' => $passage_info['village_id'], 'car_number_type' => $car_number_type, 'status' => 1, 'park_sys_type' => 'A11'], 'car_type');
                                        if (!empty($car_types)) {
                                            $car_info['parking_car_type'] = $car_types['car_type'];
                                        } else {
                                            $car_info['parking_car_type'] = self::Temp_A;
                                        }
                                    }
                                    $car_access_record = unsetParam($car_access_record, 'imageToJob');
                                    $car_access_record['business_type'] = 0;
                                    $car_access_record['business_id'] = $passage_info['village_id'];
                                    $car_access_record['car_number'] = $car_number;
                                    $car_access_record['accessType'] = 2;
                                    $car_access_record['accessTime'] = $now_time;
                                    $inAccessTime > 0 && $car_access_record['park_time'] = $now_time - $inAccessTime;
                                    $car_access_record['accessMode'] = 3;
                                    $car_access_record['park_sys_type'] = 'A11';
                                    $car_access_record['park_car_type'] = $this->parking_a11_car_type_arr[$car_info['parking_car_type']];
                                    $car_access_record['coupon_id'] = $coupon_id;
                                    $car_access_record['park_id'] = $passage_info['village_id'];
                                    $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                                    $car_access_order_id = isset($park_info_car111['order_id']) && $park_info_car111['order_id'] ? $park_info_car111['order_id'] : '';
                                    $car_access_record['order_id'] = $car_access_order_id;
                                    $car_access_record['total'] = $pay_money['pay_money'];
                                    // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                                    // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                                    //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                                    $car_access_record['pay_type'] = $this->pay_type[5];
                                    $car_access_record['update_time'] = $now_time;
                                    $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                                    if (isset($insert_id)) {
                                        $car_access_record['from_id'] = $insert_id;
                                    }
                                    $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                                    if(!empty($outRightPosition)) {
                                        //释放车位
                                        $whereArr = array('position_id' => $outRightPosition['position_id'], 'use_car_id' => $car_info['car_id']);
                                        $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                                        $this->updateParkingPositionUse($whereArr, $saveArr, $car_number);
                                    }
                                    //将此条之前的 都改掉
                                    if($record_id>0 && $is_real_garage>0) {
                                        $tmpUpdateRecordArr=array('car_number'=> $car_number,'in_park_id'=>$in_park_id,'village_id'=>$passage_info['village_id'],'record_id'=>$record_id,'access_order_id'=>$car_access_order_id,'access_type'=>2,'access_time'=>time());
                                        (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                                    }
                                    if ($car_number) {
                                        $park_info_car111_arr = $park_info_car111 && !is_array($park_info_car111) ? $park_info_car111->toArray() : $park_info_car111;
                                        fdump_api(['出场记录(新)=' . __LINE__ => $car_number, 'park_info_car111' => $park_info_car111_arr, 'car_access_record' => $car_access_record, 'record_id' => $record_id], 'park_temp/log_' . $car_number, 1);
                                    } elseif ($parkTime) {
                                        fdump_api(['车辆通行' => $car_number, 'park_info_car111' => $park_info_car111, 'car_access_record' => $car_access_record, 'record_id' => $record_id], 'A11Park/plateresult' . $parkTime, true);
                                    }
                                } else {
                                    $record_id = isset($park_info_car['record_id']) ? intval($park_info_car['record_id']) : 0;
                                    $car_access_order_id = isset($park_info_car['order_id']) ? trim($park_info_car['order_id']) : '';
                                }
                                //TODO:屏显和语音提示车辆通行
                                $showscreen_data['car_type'] = 'temporary_type';
                                $showscreen_data['duration_txt'] = $pay_money['time'];
                                $showscreen_data['duration'] = $pay_money['park_time'];
                                $showscreen_data['price'] = 0;
                                if ($car_number) {
                                    fdump_api(['车辆通行屏显和语音提示车辆通行=' . __LINE__ => $car_number, 'showscreen_data' => $showscreen_data], 'park_temp/log_' . $car_number, 1);
                                } elseif ($parkTime) {
                                    fdump_api(['车辆通行' => $car_number, 'showscreen_data' => $showscreen_data], 'a11_park/plateresult' . $parkTime, true);
                                }
                                $this->addParkShowScreenLog($showscreen_data);
                                //车辆通行

                                //发送模板消息
                                $openid = $this->getUserInfo($car_number, $passage_info['village_id']);
                                if (!empty($openid)) {
                                    $this->remarkTxt = '\n支付金额： ' . $payMoney . '（优惠抵扣）';
                                    $this->pay_price = $pay_money['pay_money'];
                                    $this->stored_balance = isset($car_info['stored_balance']) && $car_info['stored_balance'] ? $car_info['stored_balance'] : 0;
                                    $this->sendCarMeassage($openid, $car_number, $car_info, $village_info, $garage_info, $passage_info, $pay_money['time'], $village_info['property_id'], $small_garage_info, $now_time, true);
                                }
                                if ($isImageToJob && ($record_id || $insert_id)) {
                                    if (isset($car_access_order_id) && $car_access_order_id) {
                                        $imageToJob['car_access_order_id'] = $car_access_order_id;
                                    }
                                    $imageToJob['out_record_id'] = $record_id;
                                    (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                                }
                                return true;
                            }
                        } 
                            
                        //TODO:屏显和语音提示错误信息
                        $showscreen_data['voice_content']= 5;
                        $showscreen_data['content']= '';
                        $showscreen_data['car_type']= 'temporary_type';
                        $showscreen_data['duration_txt']= $pay_money['time'];
                        $showscreen_data['duration']= $pay_money['park_time'];
                        $showscreen_data['price']= $pay_money['pay_money'];
                        if ($car_number) {
                            fdump_api(['车辆通行屏显和语音提示车辆通行='.__LINE__=>$car_number, 'showscreen_data' => $showscreen_data],'park_temp/log_'.$car_number,1);
                        } elseif ($parkTime) {
                            fdump_api(['屏显和语音提示错误信息'=>$car_number,'showscreen_data' => $showscreen_data],'a11_park/plateresult'.$parkTime,true);
                        }
                        $this->addParkShowScreenLog($showscreen_data);
                        //需缴费通行
                        return false;
                    }
                }
            }
            else {
                fdump_api(['车道未绑定车场','err' => '车道未绑定车场'],'park_temp/log_'.$car_number,1);
                throw new \Exception('车道未绑定车场');
            }
        }
        return false;
    }

    public function get_temp_pay_coupon($village_id=0,$car_number='',$park_sys_type='A11',$park_temp_id=0,$uid=0){
        if (empty($car_number)) {
            return array();
        }
        $this->checkCouponUseStatus($village_id,$car_number,$park_sys_type,$uid);
        $db_house_village_park_coupon = new HouseVillageParkCoupon();
        $db_house_village_park_temp = new HouseVillageParkingTemp();
        $where_coupon = [];
        $where_coupon['village_id'] = $village_id;
        $where_coupon['car_number'] = $car_number;
        $where_coupon['park_sys_type'] = $park_sys_type;
        $where_coupon['is_use'] = 0;
        $coupon_info = $db_house_village_park_coupon->getFind($where_coupon);
        if($coupon_info && !$coupon_info->isEmpty()){
            $coupon_info=$coupon_info->toArray();
            $db_house_village_park_coupon->saveOne($where_coupon,['is_use'=>1]);
            if($park_temp_id>0 && !empty($coupon_info)){
                $db_house_village_park_temp->saveOne(['id'=>$park_temp_id],['coupon_id'=>$coupon_info['id']]);
            }
        }else{
            $coupon_info=array();
        }
        fdump_api(['优惠券='.__LINE__=>$car_number, 'coupon_info' => $coupon_info,'where_coupon' => $where_coupon],'park_temp/log_'.$car_number,1);
        return $coupon_info;
    }
    /**
     * 临时车停车计费
     * @author:zhubaodi
     * @date_time: 2022/11/25 18:42
     */
    public function get_temp_pay($data,$garage_id=0){
        $car_number = isset($data['car_number']) && $data['car_number'] ? $data['car_number'] : '';
        fdump_api(['1计算停车费','data' => $data,'garage_id'=>$garage_id],'a11_temp_pay/log_'.$car_number,1);
        $db_in_park = new InPark();
        $db_house_village_park_charge = new HouseVillageParkCharge();
        $db_house_village_park_charge_cartype=new HouseVillageParkChargeCartype();
        $db_house_new_charge_rule = new HouseNewChargeRule();
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $db_house_village_park_config = new HouseVillageParkConfig();
        $db_park_passage=new ParkPassage();
        $db_house_village_parking_garage=new HouseVillageParkingGarage();
        
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $park_config =$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$data['village_id']);
        $car_type_month_to_temp=array();
        $tmp_car_type_month_to_temp=array();
        if(isset($park_config['car_type_month_to_temp']) && !empty($park_config['car_type_month_to_temp'])){
            $tmp_car_type_month_to_temp=json_decode($park_config['car_type_month_to_temp'],1);
        }
        if($tmp_car_type_month_to_temp){
            foreach ($tmp_car_type_month_to_temp as $vv){
                $car_type_month_to_temp[$vv['month_car_type']]=$vv;
            }
        }
        $where = [];
        $where['car_number'] = $car_number;
        $where['park_id'] = $data['village_id'];
        $where['park_sys_type'] = 'A11';
        $where['is_paid'] = 0;
        $where['is_out'] = 0;
        $where['del_time'] = 0;
        $park_info = $db_in_park->getOne1($where);
        $park_count=$db_in_park->getCount(['car_number'=>$car_number,'park_id'=>$data['village_id'], 'del_time' => 0]);
        fdump_api(['2计算停车费','park_info' => $park_info,'where' => $where,'park_count' => $park_count],'a11_temp_pay/log_'.$car_number,1);
        $db_house_village_park_temp = new HouseVillageParkingTemp();
        if (empty($park_info) || $park_info->isEmpty()) {
            $houseVillage=new HouseVillage();
            $houseVillageInfo=$houseVillage->getOne(['village_id'=>$data['village_id']],'village_name');
            $park_name='';
            if($houseVillageInfo && !$houseVillageInfo->isEmpty()){
                $park_name=$houseVillageInfo['village_name'];
            }
            if($park_config['not_inPark_money']>0){
                /***无入场记录收费**/
                $data_temp = [];
                $data_temp['village_id'] = $data['village_id'];
                $data_temp['add_time'] = time();
                $data_temp['free_out_time'] = 0;
                $data_temp['duration'] = 0;
                $data_temp['derate_money'] = 0;
                $data_temp['derate_duration'] = 0;
                $order_id='330'.date('YmdHis').rand(100,999);
                $data_temp['order_id'] = $order_id;
                $data_temp['query_order_no'] = build_real_orderid($data['uid']);
                $data_temp['errmsg'] = '';
                $data_temp['price'] = $park_config['not_inPark_money'];
                $data_temp['total'] = $park_config['not_inPark_money'];
                $data_temp['in_time'] = 0;
                $data_temp['car_number'] = $data['car_number'];
                $data_temp['out_channel_id'] = isset($data['device_number']) ? $data['device_number'] : '';
                $data_temp['is_pay_scene'] = 0;
                $data_temp['park_sys_type'] = 'A11';
                $data_temp['garage_id']=$garage_id;
                fdump_api([__LINE__, 'data_temp' => $data_temp], 'a11_temp_pay/log_' . $car_number, 1);
                $id1 = $db_house_village_park_temp->addOne($data_temp);
                fdump_api([__LINE__, 'id1' => $id1], 'a11_temp_pay/log_' . $car_number, 1);

                $list = [];
                $list['time'] = '--';
                $list['park_time'] = 0;
                $list['pay_money'] = $park_config['not_inPark_money'];
                $list['order_id'] = $order_id;
                $list['park_name'] = $park_name;
                $list['in_time'] = '--';
                $list['pay_time'] = time();
                $list['total_money'] = $park_config['not_inPark_money'];
                $list['coupon_money'] = 0;
                $list['coupon_id'] = 0;
                $list['parkTemp_money'] = 0;
                $list['park_temp_id'] = 0;
                fdump_api([__LINE__, 'list' => $list], 'a11_temp_pay/log_' . $car_number, 1);
                return $list;
            }else{
                fdump_api(['00计算停车费', 'err' => '没有停车纪录,直接开门'], 'a11_temp_pay/log_' . $car_number, 1);
                $list = [];
                $order_id='300'.date('YmdHis').rand(100,999);
                $list['time'] = '--';
                $list['park_time'] = 0;
                $list['pay_money'] = 0;
                $list['order_id'] = $order_id;
                $list['park_name'] = $park_name;
                $list['in_time'] = '--';
                $list['pay_time'] = time();
                $list['total_money'] = 0;
                $list['coupon_money'] = 0;
                $list['coupon_id'] = 0;
                $list['parkTemp_money'] = 0;
                $list['park_temp_id'] = 0;
                return $list;
                //throw new \Exception('没有停车纪录');
            }
        }
        $now_time = time();
        $province = mb_substr($car_number, 0, 1);
        $car_no = mb_substr($car_number, 1);
        $car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no, 'village_id' => $data['village_id']]);
        if (empty($car_info)) {
            $car_types = $db_house_village_park_charge_cartype->getFind(['village_id' => $data['village_id'], 'car_number_type' => $data['car_number_type'], 'status' => 1, 'park_sys_type' => 'A11'], 'car_type');
            if (!empty($car_types)) {
                $car_type = $car_types['car_type'];
            } else {
                $car_type = self::Temp_A;
            }
        } else {
            if (!in_array($car_info['parking_car_type'], $this->A11_Temp) && !in_array($car_info['parking_car_type'], $this->A11_Stored)) {
                $car_type = self::Temp_A;
                if($car_type_month_to_temp && isset($car_type_month_to_temp[$car_info['parking_car_type']]) && isset($car_type_month_to_temp[$car_info['parking_car_type']]['temp_car_type']) && in_array($car_type_month_to_temp[$car_info['parking_car_type']]['temp_car_type'], $this->A11_Temp)){
                    $car_type = $car_type_month_to_temp[$car_info['parking_car_type']]['temp_car_type'];
                }
            } else {
                $car_type = $car_info['parking_car_type'];
            }
        }
        $rule_ids = $db_house_village_park_charge_cartype->getColumn(['village_id' => $data['village_id'], 'car_type' => $car_type, 'status' => 1, 'park_sys_type' => 'A11'], 'rule_id');
        if (empty($rule_ids)) {
            fdump_api(['01计算停车费', 'err' => '当前卡类未绑定收费标准', 'car_type' => $car_type], 'a11_temp_pay/log_' . $car_number, 1);
            throw new \Exception('当前卡类未绑定收费标准');
        }
        $where_rule = [
            ['id', 'in', $rule_ids],
            ['village_id', '=', $data['village_id']],
            ['status', '=', 1],
            ['charge_valid_time', '<=', $now_time],
            ['fees_type', '=', 3],
        ];
        $rule_info = $db_house_new_charge_rule->getOne($where_rule, '*', 'charge_valid_time DESC');
        if (empty($rule_info)) {
            fdump_api(['02计算停车费', 'err' => '该停车场未绑定收费规则', 'where_rule' => $where_rule], 'a11_temp_pay/log_' . $car_number, 1);
            throw new \Exception('该停车场未绑定收费规则');
        }
        $rule_id=$rule_info['id'];
        $project_id=$rule_info['charge_project_id'];
        //查询当前车库信息
        $garage_info = [];
        $passage_info = $db_park_passage->getFind(['id' => $park_info['in_channel_id']]);
        if ($garage_id>0) {
            $garage_info = $db_house_village_parking_garage->getOne(['garage_id' => $garage_id]);
        }
        $where_charge = [];
        $where_charge['village_id'] = $data['village_id'];
        //$where_charge['park_sys_type'] = 'A11';
        $where_charge['status'] = 1;
        $where_charge['id'] = $rule_info['park_charge_id'];
        $park_charge = $db_house_village_park_charge->getFind($where_charge);
        if (empty($park_charge)) {
            fdump_api(['03计算停车费', 'err' => '该停车场未绑定收费规则', 'where_charge' => $where_charge], 'a11_temp_pay/log_' . $car_number, 1);
            throw new \Exception('该停车场未绑定收费规则');
        }
       
        //查询当前车库是否是车辆绑定的车库
        $park_time_total = $park_time = $now_time - $park_info['in_time'];
        if ($car_info && !is_array($car_info)) {
            $car_info_arr = $car_info->toArray();
        } else {
            $car_info_arr = [];
        }
        fdump_api(['3计算停车费', 'car_info' => $car_info_arr, 'park_time' => $park_time], 'a11_temp_pay/log_' . $car_number, 1);
        if (!empty($garage_info) && !empty($car_info['garage_id']) && $garage_id == $car_info['garage_id']) {
            if(isset($data['car_end_time']) && $data['car_end_time']>100 && $park_info['in_time'] < $data['car_end_time'] && $now_time > $data['car_end_time']  ){
                $park_time = $now_time - $data['car_end_time'];
            }else if (!empty($car_info) && $car_info['end_time'] > 1 && $park_info['in_time'] < $car_info['end_time'] && $car_info['end_time'] < $now_time) {
                $park_time = $now_time - $car_info['end_time'];
                fdump_api(['4计算停车费', 'park_time' => $park_time, 'now_time' => $now_time, 'garage_info' => $garage_info, 'passage_info' => $passage_info, 'end_time' => $car_info['end_time']], 'a11_temp_pay/log_' . $car_number, 1);
            } elseif (!empty($car_info) && $car_info['end_time'] > 1 && $park_info['in_time'] < $car_info['end_time'] && $car_info['end_time'] >= $now_time) {
                $park_time = 0;
                fdump_api(['5计算停车费', 'park_time' => $park_time, 'now_time' => $now_time, 'garage_info' => $garage_info, 'passage_info' => $passage_info, 'end_time' => $car_info['end_time']], 'a11_temp_pay/log_' . $car_number, 1);
            }
        } else {
            fdump_api(['6计算停车费', 'now_time' => $now_time, 'in_time' => $park_info['in_time'], 'park_time' => $park_time], 'a11_temp_pay/log_' . $car_number, 1);
            $where_out = [];
            $where_out[]=['car_number','=',$car_number];
            $where_out[]=['park_id','=',$data['village_id']];
            $where_out[]=['park_sys_type','=','A11'];
            $where_out[]=['is_out','=',1];
            $where_out[]=['del_time','=',0];
            $where_out[]=['in_time','>',$park_info['in_time']];
            $fieldStr='id,car_number,in_time,in_channel_id,is_paid,park_id,out_time,park_time';
            $park_info1 = $db_in_park->getList($where_out,$fieldStr,1,100);
            $tmp_park_time=0;   //之前小场停车时间
            $in_channel_id=0;
            if($park_info1 && !$park_info1->isEmpty()){
                $park_info1=$park_info1->toArray();
                fdump_api(['6计算停车费', 'now_time' => $now_time, 'park_info1' => $park_info1, 'park_time' => $park_time], 'a11_temp_pay/log_' . $car_number, 1);
                foreach ($park_info1 as $pvv){
                    if( $pvv['out_time'] > $pvv['in_time'] && $pvv['out_time'] < $now_time){
                        $tmp_park_time+=$pvv['park_time'];
                        $in_channel_id=$pvv['in_channel_id'];
                    }
                }
            }
            if($in_channel_id>0 && $tmp_park_time>0) {
                $passage_info1 = $db_park_passage->getFind(['id' =>$in_channel_id]);
                $small_garage_id= $passage_info1['garage_id'];
                if($small_garage_id<1 && $passage_info1['area_type']==2 && $passage_info1['passage_area']){
                    $small_garage_id=$passage_info1['passage_area'];
                }
                if (!empty($passage_info1) && !empty($car_info['garage_id']) && $small_garage_id == $car_info['garage_id']) {
                    $park_time = $now_time - $park_info['in_time'] - $tmp_park_time;
                    fdump_api(['7计算停车费', 'now_time' => $now_time, 'in_time' => $park_info['in_time'], 'park_info1' => $park_info1, 'park_time' => $park_time], 'a11_temp_pay/log_' . $car_number, 1);
                }
            }
        }
        fdump_api(['8计算停车费', 'park_time' => $park_time], 'a11_temp_pay/log_' . $car_number, 1);
        $whereTemp = [];
        $whereTemp[] = ['car_number', '=', $data['car_number']];
        $whereTemp[] = ['village_id', '=', $data['village_id']];
        $whereTemp[] = ['pay_time', '>', 0];
        $parkTemp = $db_house_village_park_temp->getOne($whereTemp, true, 'pay_time DESC,id DESC');
        $last_pay_time=0;
        $is_advance_pay=0;  //付过了，识别比心跳过来的快
        if($parkTemp && is_object($parkTemp) && !$parkTemp->isEmpty()){
            $parkTemp=$parkTemp->toArray();
        }
        fdump_api(['9计算停车费', 'parkTemp' => $parkTemp, 'in_time' => $park_info['in_time'], 'park_time' => $park_time], 'a11_temp_pay/log_' . $car_number, 1);
        if ($parkTemp && isset($parkTemp['pay_time']) && $parkTemp['pay_time'] > $park_info['in_time']) {
            $parkTemp_money = $parkTemp['price'];
            $last_pay_time=$parkTemp['pay_time'];
            $next_last_park_time=time()-$parkTemp['pay_time'];
            if($next_last_park_time<600 && $parkTemp['order_id']==$park_info['order_id']){
                //10分钟中内相同 入场记录如果有 支付记录 记下钱
                $is_advance_pay=1;
            }
            if (empty($park_config['free_park_time'])) {
                if (time() - $parkTemp['pay_time'] < 900) {
                    $park_time = 0;
                }
            } else {
                if (time() - $parkTemp['pay_time'] < ($park_config['free_park_time'] * 60)) {
                    $park_time = 0;
                }
            }
        } else {
            $parkTemp_money = 0;
        }
        $park_charge_info = array();
        if ($park_charge['charge_type'] == 1) {
            $park_charge_info = unserialize($park_charge['charge_set']);
            $last_ages = array_column($park_charge_info, 'time');
            array_multisort($last_ages, SORT_DESC, $park_charge_info);
            fdump_api(['10计算停车费', 'park_charge_info' => $park_charge_info, 'park_time' => $park_time], 'a11_temp_pay/log_' . $car_number, 1);
        } elseif ($park_charge['charge_type'] == 4) {
            //24小时逐时收费制
            $park_charge_info = unserialize($park_charge['charge_set']);
            fdump_api(['11计算停车费', 'park_charge_info' => $park_charge_info, 'park_time' => $park_time], 'a11_temp_pay/log_' . $car_number, 1);
            if (empty($park_charge_info)) {
                fdump_api([__LINE__, 'err' => '费用设置数据错误', 'park_charge' => $park_charge], 'a11_temp_pay/log_' . $car_number, 1);
                throw new \Exception('费用设置数据错误！');
            }
        }
        fdump_api([__LINE__, 'charge_type' => $park_charge['charge_type'], 'park_time' => $park_time, 'park_charge' => $park_charge, 'park_charge_info' => $park_charge_info], 'a11_temp_pay/log_' . $car_number, 1);
        $park_money = 0;
        $pay_money = 0;
        $coupon_id = 0;
        $total_money=0;
        if ($park_time > 0) {
            /***
             *新按次收费
             * 1连续多少小时只收取一次费(在原来charge_type是3 上面时间不一定是24了) 2按次收费（和 charge_type是2一样） 3当天只收取一次费(和 charge_type是3一样)
             */
            $charge_type5_set = isset($park_charge['charge_type5_set']) ? $park_charge['charge_type5_set'] : 0;
            $parkTime = ceil($park_time / 60);
            if ($parkTime <= $park_charge['free_time']) {
                $pay_money = 0;
                fdump_api(['11计算停车费', 'pay_money' => $pay_money], 'a11_temp_pay/log_' . $car_number, 1);
            } else {
                if (isset($park_charge['free_time_no_count']) && $park_charge['free_time_no_count'] == 1 && $park_charge['free_time'] > 0) {
                    //1从免费时间后开始计算费用 
                    $park_time = $park_time - ($park_charge['free_time'] * 60);
                    $park_time = $park_time > 0 ? $park_time : 0;
                }
                $parkTime = ceil($park_time / 60);
                if ($park_count == 1 && !empty($park_charge['first_free_time']) && $parkTime <= $park_charge['first_free_time']) {
                    $pay_money = $park_charge['first_charge_money'];
                    fdump_api(['12计算停车费', 'pay_money' => $pay_money], 'a11_temp_pay/log_' . $car_number, 1);
                } else {
                    if ($park_charge['charge_type'] == 1) {
                        if (!empty($park_charge_info)) {
                            $parkTime1 = ceil($park_time / 3600);
                            fdump_api(['12计算停车费', 'parkTime1' => $parkTime1], 'a11_temp_pay/log_' . $car_number, 1);
                            if ($park_charge['max_charge_money'] > 0 && $parkTime1 > 24) {
                                $level = intval($park_time / 86400);
                                $park_money = $level * $park_charge['max_charge_money'];
                                $park_time = $park_time - $level * 86400;
                                $parkTime1 = $parkTime1 - 24 * $level;
                                fdump_api(['13计算停车费', 'level' => $level, 'park_money' => $park_money, 'park_time' => $park_time, 'parkTime1' => $parkTime1], 'a11_temp_pay/log_' . $car_number, 1);
                            }
                            if ($park_charge_info[0]['time'] < $parkTime1) {
                                $pay_money = $park_charge_info[0]['money'];
                                $parkTime2 = $park_time - $park_charge_info[0]['time'] * 3600;
                                fdump_api(['14计算停车费', 'pay_money' => $pay_money, 'parkTime2' => $parkTime2], 'a11_temp_pay/log_' . $car_number, 1);
                                if ($park_charge['max_charge_money'] > 0) {
                                    /* if($parkTime1>24){
                                         $level=intval($park_time / 86400);
                                         $park_money=$level*$park_charge['max_charge_money'];
                                         $parkTime2=$park_time-$level*86400;
                                     }*/
                                    $parkTime3 = ceil($parkTime2 / ($park_charge['charge_time'] * 60));
                                    $park_money1 = $parkTime3 * $park_charge['charge_money'];
                                    fdump_api(['15计算停车费', 'parkTime3' => $parkTime3, 'park_money1' => $park_money1], 'a11_temp_pay/log_' . $car_number, 1);
                                    if ($parkTime1 > 24 && ($pay_money + $park_money1) > $park_charge['max_charge_money']) {
                                        $park_money += $park_charge['max_charge_money'];
                                        fdump_api(['16计算停车费', 'park_money' => $park_money], 'a11_temp_pay/log_' . $car_number, 1);
                                    } elseif ($parkTime1 <= 24 && ($pay_money + $park_money1) > $park_charge['max_charge_money']) {
                                        $pay_money = $park_charge['max_charge_money'];
                                        fdump_api(['17计算停车费', 'park_money' => $park_money], 'a11_temp_pay/log_' . $car_number, 1);
                                    } else {
                                        $park_money += $park_money1;
                                        fdump_api(['18计算停车费', 'park_money' => $park_money], 'a11_temp_pay/log_' . $car_number, 1);
                                    }

                                } else {
                                    $parkTime3 = ceil($parkTime2 / ($park_charge['charge_time'] * 60));
                                    $park_money1 = $parkTime3 * $park_charge['charge_money'];
                                    $park_money += $park_money1;
                                    fdump_api(['19计算停车费', 'parkTime3' => $parkTime3, 'park_money1' => $park_money1, 'park_money' => $park_money], 'a11_temp_pay/log_' . $car_number, 1);
                                }
                            } else {
                                foreach ($park_charge_info as $k => $v) {
                                    if ($v['time'] == $parkTime1) {
                                        $pay_money = $v['money'];
                                        fdump_api(['20计算停车费', 'park_money' => $park_money], 'a11_temp_pay/log_' . $car_number, 1);
                                        break;
                                    } elseif ($v['time'] < $parkTime1) {
                                        $pay_money = $park_charge_info[$k - 1]['money'];
                                        fdump_api(['21计算停车费', 'park_money' => $park_money], 'a11_temp_pay/log_' . $car_number, 1);
                                        break;
                                    } elseif (!isset($park_charge_info[$k + 1]) && $v['time'] > $parkTime1) {
                                        $pay_money = $park_charge_info[$k]['money'];
                                        fdump_api(['22计算停车费', 'park_money' => $park_money], 'a11_temp_pay/log_' . $car_number, 1);
                                        break;
                                    }
                                }
                            }
                        }
                    } elseif ($park_charge['charge_type'] == 4) {
                        $parkTimeHour = ceil($park_time / 3600); //算小时
                        $tmp_pay_money = 0;
                        do {
                            foreach ($park_charge_info as $kk => $vv) {
                                $new_key = $kk + 1;
                                if ($vv['hour'] < 24 && ($parkTimeHour >= $vv['hour']) && ($parkTimeHour < $park_charge_info[$new_key]['hour'])) {
                                    $tmp_pay_money += $vv['fee'];
                                } else if ($vv['hour'] == 1 && $parkTimeHour <= $vv['hour']) {
                                    $tmp_pay_money += $vv['fee'];
                                } else if (($vv['hour'] == 24) && ($parkTimeHour >= $vv['hour'])) {
                                    $tmp_pay_money += $vv['fee'];
                                }
                            }
                            $parkTimeHour = $parkTimeHour - 24;
                        } while ($parkTimeHour > 0);
                        $pay_money = round($tmp_pay_money, 2);
                    } elseif ($park_charge['charge_type'] == 2 || ($park_charge['charge_type'] == 5 && $charge_type5_set == 2)) {
                        $parkTime_charge2 = ceil($park_time / 86400);
                        $pay_money = $park_charge['max_charge_money'] * $parkTime_charge2;
                        fdump_api(['23计算停车费', 'parkTime_charge2' => $parkTime_charge2, 'park_money' => $park_money], 'a11_temp_pay/log_' . $car_number, 1);
                    } else {
                        $parkTime_charge3 = $park_info['in_time'] + $park_time;
                        $oneDaySecond = 86400;  //24小时
                        if ($park_charge['charge_type'] == 5 && $charge_type5_set == 1 && $park_charge['hour_once_fee'] > 0) {
                            $oneDaySecond = $park_charge['hour_once_fee'] * 3600;  //自定义的
                        }
                        $pay_money=0;
                        $time_charge = strtotime(date('Y-m-d 00:00:00', $park_info['in_time'])) + $oneDaySecond;
                        fdump_api(['24计算停车费', 'parkTime_charge3' => $parkTime_charge3, 'time_charge' => $time_charge], 'a11_temp_pay/log_' . $car_number, 1);
                        if ($time_charge < $parkTime_charge3) {
                            $parkTime_charge2 = ceil($park_time / $oneDaySecond);
                            $pay_money = $park_charge['max_charge_money'] * $parkTime_charge2;
                            fdump_api(['25计算停车费', 'parkTime_charge2' => $parkTime_charge2, 'pay_money' => $pay_money], 'a11_temp_pay/log_' . $car_number, 1);
                        } elseif(empty($parkTemp) || !isset($parkTemp['price']) || ($parkTemp['price']<=0)) {
                            //第一次
                            $pay_money = $park_charge['max_charge_money'];
                            if($charge_type5_set == 3 || $charge_type5_set == 1){
                                $faccessTime=strtotime(date('Y-m-d 00:00:00'));
                                $recordWhere=[
                                    ['business_type','=','0'],
                                    ['business_id','=',$data['village_id']],
                                    ['accessType','=',2],
                                    ['car_number','=',$car_number],
                                    ['is_out','=',1],
                                ];
                                $recordWhere[]=['accessTime','>',$faccessTime];
                                $parkRecord = (new HouseVillageCarAccessRecord())->getOne($recordWhere, 'record_id,accessTime,car_number');
                                if($parkRecord && !$parkRecord->isEmpty()){
                                    $pay_money = 0;
                                }
                            }
                            fdump_api(['26计算停车费', 'park_money' => $park_money], 'a11_temp_pay/log_' . $car_number, 1);
                        }
                    }
                }
            }
            $pay_money = $pay_money + $park_money - $parkTemp_money;
            $pay_money=$pay_money>0 ? $pay_money:0;
            $total_money = $pay_money;
            fdump_api(['27计算停车费', 'park_money' => $park_money,'parkTemp_money'=>$parkTemp_money, 'total_money' => $total_money], 'a11_temp_pay/log_' . $car_number, 1);
        } elseif ($park_time_total <= 0) {
            fdump_api(['28计算停车费', 'err' => '没有停车时长'], 'a11_temp_pay/log_' . $car_number, 1);
            throw new \Exception('没有停车时长');
        }
        $data_temp = [];
        $data_temp['village_id'] = $data['village_id'];
        $data_temp['add_time'] = time();
        $data_temp['free_out_time'] = $park_charge['free_time'];
        $data_temp['duration'] = round_number($park_time_total / 60, 2);
        $data_temp['derate_money'] = isset($coupon_info['money']) ? $coupon_info['money'] : 0;
        $data_temp['derate_duration'] = 0;
        $data_temp['order_id'] = $park_info['order_id'];
        $data_temp['query_order_no'] = build_real_orderid($data['uid']);
        $data_temp['errmsg'] = '';
        $data_temp['price'] = $pay_money;
        $data_temp['total'] = $total_money;
        $data_temp['in_time'] = $park_info['in_time'];
        $data_temp['car_number'] = $data['car_number'];
        $data_temp['out_channel_id'] = isset($data['device_number']) ? $data['device_number'] : '';
        $data_temp['is_pay_scene'] = 0;
        $data_temp['park_sys_type'] = 'A11';
        $data_temp['garage_id']=$garage_id;
        if ($pay_money == 0) {
            $data_temp['is_paid'] = 1;
            $data_temp['pay_time'] = time();
        }
        $data_temp['car_type'] = $car_type;
        $data_temp['park_charge'] = json_encode($park_charge_info,JSON_UNESCAPED_UNICODE);
        $data_temp['rule_id']=$rule_id;
        $data_temp['project_id']=$project_id;
        fdump_api(['29计算停车费', 'data_temp' => $data_temp], 'a11_temp_pay/log_' . $car_number, 1);
        $id1 = $db_house_village_park_temp->addOne($data_temp);
        fdump_api(['30计算停车费', 'id1' => $id1], 'a11_temp_pay/log_' . $car_number, 1);

        $hours = intval($park_time_total / 3600);
        $mins = intval(($park_time_total - $hours * 3600) / 60);
        $second = $park_time_total - $hours * 3600 - $mins * 60;
        $time = '';
        if ($hours > 0) {
            $time .= $hours . '小时';
        }
        if ($mins > 0) {
            $time .= $mins . '分钟';
        }
        if ($second > 0) {
            $time .= $second . '秒';
        }
        $list = [];
        $list['time'] = $time;
        $list['park_time'] = $park_time_total;
        $list['pay_money'] = $pay_money;
        $list['order_id'] = $park_info['order_id'];
        $list['park_name'] = $park_info['park_name'];
        $list['in_time'] = date('Y-m-d H:i:s', $park_info['in_time']);
        $list['pay_time'] = time();
        $list['total_money'] = $total_money;
        $list['coupon_money'] = 0;
        $list['coupon_id'] = 0;
        $list['parkTemp_money'] = $parkTemp_money;
        $list['park_temp_id'] = $id1;
        $list['car_type'] = $car_type;
        $list['park_charge'] = $park_charge_info;
	    $list['is_advance_pay'] = $is_advance_pay;
        fdump_api(['31计算停车费', 'list' => $list], 'a11_temp_pay/log_' . $car_number, 1);
        return $list; 
        
    }
    
    public function checkCouponUseStatus($village_id=0,$car_number='',$park_sys_type='',$uid=0){
        $db_house_village_park_temp = new HouseVillageParkingTemp();
        $whereTemp = [];
        $whereTemp[] = ['village_id', '=', $village_id];
        $whereTemp[] = ['car_number', '=', $car_number];
        $whereTemp[] = ['is_paid', '=', 0];
        if($park_sys_type){
            $whereTemp[] = ['park_sys_type', '=', $park_sys_type];
        }
        $nowtime=time();
        $tmptime=$nowtime-36000;
        $whereTemp[] = array('add_time','<',$nowtime);
        $whereTemp[] = array('add_time','>',$tmptime);
        $whereTemp[] = ['coupon_id', '>', 0];
        $parkTemp = $db_house_village_park_temp->getOne($whereTemp,'coupon_id','id DESC');
        if($parkTemp && !$parkTemp->isEmpty()){
            $db_house_village_park_coupon = new HouseVillageParkCoupon();
            $whereArr=array('vpc.id'=>$parkTemp['coupon_id'],'vpc.village_id'=>$village_id,'sc.village_id'=>$village_id);
            $oneCoupon= $db_house_village_park_coupon->getOneCoupon($whereArr,'sc.*');
            $is_coupon_can_use=0;
            if($oneCoupon && !$oneCoupon->isEmpty() && $oneCoupon['cid']){
                $parkCouponsDb= new ParkCoupons();
                $whereCoupon=array('village_id'=>$village_id,'c_id'=>$oneCoupon['cid']);
                $parkCouponsObj=$parkCouponsDb->getFind($whereCoupon,'*');
                if($parkCouponsObj && !$parkCouponsObj->isEmpty() && $parkCouponsObj['status']==0){
                    $is_coupon_can_use=1;
                }
            }
            if($is_coupon_can_use>0){
                $where_coupon = [];
                $where_coupon['village_id'] =$village_id;
                $where_coupon['car_number'] = $car_number;
                $where_coupon['id'] = $parkTemp['coupon_id'];
                if($uid) {
                    $where_coupon['user_id'] = $uid;
                }
                $saveArr=array('is_use'=>0,'use_time'=>0,'use_txt'=>'');
                $db_house_village_park_coupon->saveOne($where_coupon,$saveArr);
                return $parkTemp['coupon_id'];
            }
        }
        return true;
    }
    /**
     * 月租车出场
     * @author:zhubaodi
     * @date_time: 2022/11/25 17:28
     */
    public function month_out_park($car_number,$passage_info,$car_info,$park_data,$showscreen_data,$in_park_info,$village_info,$out_time,$parkTime,$car_number_type,$out_park_info=array(),$car_access_record=array(),$outRightPosition=array(),$is_real_garage=0){
        $db_in_park = new InPark();
        $db_out_park = new OutPark();
        $now_time=time();
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();

        $imageToJob = isset($car_access_record['imageToJob']) ? $car_access_record['imageToJob'] : [];
        $car_access_record = unsetParam($car_access_record, 'imageToJob');
        
        if (empty($in_park_info)&&!empty($out_park_info)&&$out_park_info['out_time']>$out_time){
            //TODO:屏显和语音提示车辆通行
            $showscreen_data['car_type']     = 'month_type';
            $showscreen_data['end_time']     = $car_info['end_time'];
            $showscreen_data['end_time_txt'] = date('Y-m-d',$car_info['end_time']);
            if ($car_number) {
                $out_park_info_arr = $out_park_info && !is_array($out_park_info) ? $out_park_info->toArray() : $out_park_info;
                fdump_api(['无入场记录，但是有出场记录且大于当前时间直接出场=='.__LINE__=>$car_number, 'out_park_info' => $out_park_info_arr],'park_temp/log_'.$car_number,1);
            } elseif ($parkTime) {
                fdump_api(['屏显和语音提示车辆通行=='.__LINE__=>$car_number,'showscreen_data' => $showscreen_data],'a11_park/plateresult'.$parkTime,true);
            }
            $this->addParkShowScreenLog($showscreen_data);
            //车辆通行
            return true;
        }
        $in_park_id=0;
        //写入车辆入场表
        if ($in_park_info && isset($in_park_info['id']) && $in_park_info['id']) {
            $in_park_id=$in_park_info['id'];
            $db_in_park->saveOne(['id'=>$in_park_info['id'],'car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'park_sys_type' => 'A11', 'is_out' => 0], $park_data);
        }
        $out_data=array();
        $out_data['car_number'] = $car_number;
        $out_data['in_time'] = $in_park_info['in_time'];
        $out_data['order_id'] = $in_park_info['order_id'];
        $out_data['out_time'] = $now_time;
        $out_data['park_id'] = $passage_info['village_id'];
        $out_data['total'] = 0;
        $out_data['pay_type'] = 'monthuser';
        //  $out_data['is_paid'] = 1;
        $starttime = time() - 30;
        $endtime = time() + 50;
        $park_where = [
            ['car_number', '=', $car_number],
            ['park_id', '=', $passage_info['village_id']],
            ['out_time', '>=', $starttime],
            ['out_time', '<=', $endtime],
        ];
        $park_info_car = $db_out_park->getOne($park_where);
        //写入车辆出场表

        if (!isset($park_info_car['id']) || !$park_info_car['id']) {
            if ($car_number) {
                fdump_api(['写入出场表=='.__LINE__=>$car_number, 'out_data' => $out_data],'park_temp/log_'.$car_number,1);
            }
            $insert_id= $db_out_park->insertOne($out_data);
        } else {
            $insert_id = $park_info_car['id'];
        }
        if (!empty($imageToJob)) {
            $imageToJob['out_park_id'] = $insert_id;
        }
        $starttime = time() - 30;
        $endtime = time() + 50;
        $park_where = [
            ['car_number', '=', $car_number],
            ['park_id', '=', $passage_info['village_id']],
            ['accessType', '=',2],
            ['accessTime', '>=', $starttime],
            ['accessTime', '<=', $endtime],
        ];
        $park_info_car = $db_house_village_car_access_record->getOne($park_where);
        if ($car_number) {
            $park_info_car_arr = $park_info_car && !is_array($park_info_car) ? $park_info_car->toArray() : $park_info_car;
            fdump_api(['出场记录[新]=='.__LINE__=>$car_number, 'park_info_car' => $park_info_car_arr, 'park_where' => $park_where],'park_temp/log_'.$car_number,1);
        } elseif ($parkTime) {
            fdump_api(['查询是否有对应出场车辆=='.__LINE__=>$car_number,'park_where' => $park_where,'park_info_car' => $park_info_car],'a11_park/plateresult'.$parkTime,true);
        }
        //写入车辆场表
        if (empty($park_info_car)) {
            $park_where = [
                ['car_number', '=', $car_number],
                ['park_id', '=', $passage_info['village_id']],
                ['accessType', '=',1],
                ['is_out', '=', 0],
            ];
            $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
            $in_record_id = isset($park_info_car111['record_id']) ? intval($park_info_car111['record_id']) : 0;
            $inAccessTime = isset($park_info_car111['accessTime']) ? intval($park_info_car111['accessTime']) : 0;
            !$inAccessTime && $inAccessTime = isset($imageToJob['in_time']) ? intval($imageToJob['in_time']) : 0;
            if ($in_record_id) {
                $db_house_village_car_access_record->saveOne(['record_id' => $in_record_id], ['is_out' => 1]);
                if (!empty($imageToJob)) {
                    $imageToJob['in_record_id'] = $in_record_id;
                }
            }
            
            if (!isset($car_info['parking_car_type'])||empty($car_info['parking_car_type'])){
                $db_house_village_park_charge_cartype=new HouseVillageParkChargeCartype();
                $car_types=$db_house_village_park_charge_cartype->getFind(['village_id'=>$passage_info['village_id'],'car_number_type'=>$car_number_type,'status'=>1,'park_sys_type'=>'A11'],'car_type');
                if (!empty($car_types)){
                    $car_info['parking_car_type']=$car_types['car_type'];
                }else{
                    $car_info['parking_car_type']=self::Temp_A;
                }
            }
            $car_access_record['business_type'] = 0;
            $car_access_record['business_id'] = $passage_info['village_id'];
            $car_access_record['car_number'] = $car_number;
            $car_access_record['accessType'] = 2;
            $car_access_record['accessTime'] = $now_time;
            $car_access_record['park_time'] = $now_time-$inAccessTime;
            $car_access_record['accessMode'] = 5;
            $car_access_record['park_sys_type'] = 'A11';
            $car_access_record['park_car_type'] = $this->parking_a11_car_type_arr[$car_info['parking_car_type']];
            $car_access_record['is_out'] = 1;
            $car_access_record['park_id'] = $passage_info['village_id'];
            $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
            $car_access_order_id = isset($park_info_car111['order_id']) && $park_info_car111['order_id'] ? $park_info_car111['order_id'] : '';
            $car_access_record['order_id'] = $car_access_order_id;
            $car_access_record['total'] = 0;
            // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
            // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
            //交易流水号(pay_type为wallet、scancode、sweepcode必传)
            $car_access_record['pay_type'] = $this->pay_type[4];
            $car_access_record['update_time'] = $now_time;
            $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
            if (isset($insert_id)) {
                $car_access_record['from_id'] = $insert_id;
            }
            $record_id = $db_house_village_car_access_record->addOne($car_access_record);

            if(!empty($outRightPosition)){
                //释放车位
                $whereArr=array('position_id'=>$outRightPosition['position_id'],'use_car_id'=>$car_info['car_id']);
                $saveArr=array('use_car_id'=>0,'use_start_time'=>0,'use_car_number'=>'','access_record_id'=>$record_id);
                $this->updateParkingPositionUse($whereArr,$saveArr,$car_number);
            }
            if($record_id>0 && $is_real_garage>0){
                //将此条之前的 都改掉
                /*
                $wherexArr=[];
                $wherexArr[]=['record_id','<',$record_id];
                $wherexArr[]=['car_number','=',$car_number];
                $wherexArr[]=['is_out','=',0];
                $wherexArr[]=['park_id','=',$passage_info['village_id']];
                $db_house_village_car_access_record->saveOne($wherexArr,['is_out'=>1,'update_time'=>$now_time]);
                */
                $tmpUpdateRecordArr=array('car_number'=> $car_number,'in_park_id'=>$in_park_id,'village_id'=>$passage_info['village_id'],'record_id'=>$record_id,'access_order_id'=>$car_access_order_id,'access_type'=>2,'access_time'=>time());
                (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
            }
            if ($car_number) {
                $park_info_car111_arr = $park_info_car111 && !is_array($park_info_car111) ? $park_info_car111->toArray() : $park_info_car111;
                fdump_api(['记录入场记录[新]=='.__LINE__=>$car_number, 'park_info_car111' => $park_info_car111_arr, 'car_access_record' => $car_access_record,'record_id' => $record_id],'park_temp/log_'.$car_number,1);
            } elseif ($parkTime) {
                fdump_api(['记录入场记录=='.__LINE__=>$car_number,'park_info_car111' => $park_info_car111,'car_access_record' => $car_access_record,'record_id' => $record_id],'a11_park/plateresult'.$parkTime,true);
            }
        } else {
            $record_id = isset($park_info_car['record_id']) ? intval($park_info_car['record_id']) : 0;
            $car_access_order_id = isset($park_info_car['order_id']) ? trim($park_info_car['order_id']) : '';
        }

        //TODO:屏显和语音提示车辆通行
        $showscreen_data['voice_content']= 4;
        $showscreen_data['content']= '';
        $showscreen_data['car_type']= 'month_type';
        $showscreen_data['end_time']= $car_info['end_time'];
        $showscreen_data['end_time_txt']= date('Y-m-d',$car_info['end_time']);
        if ($car_number) {
            fdump_api(['屏显和语音提示车辆通行[新]=='.__LINE__=>$car_number, 'showscreen_data' => $showscreen_data],'park_temp/log_'.$car_number,1);
        } elseif ($parkTime) {
            fdump_api(['记录入场记录=='.__LINE__=>$car_number,'showscreen_data' => $showscreen_data],'a11_park/plateresult'.$parkTime,true);
        }
        $this->addParkShowScreenLog($showscreen_data);
        //车辆通行
        if (!empty($imageToJob) && ($record_id || $insert_id)) {
            if (isset($car_access_order_id) && $car_access_order_id) {
                $imageToJob['car_access_order_id'] = $car_access_order_id;
            }
            $imageToJob['out_record_id'] = $record_id;
            (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
        }
        return true;
    }
    
    /**
     * 免费车出场
     * @author:zhubaodi
     * @date_time: 2022/11/24 16:06
     */
    public function free_out_park($car_number,$passage_info,$park_data,$showscreen_data,$village_info,$now_time,$parkTime,$car_number_type,$car_access_record=array(),$in_park_id=0){
        $db_house_village_park_free = new HouseVillageParkFree();
        $db_in_park = new InPark();
        $db_out_park = new OutPark();
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();

        if (!empty(cache('freeList_A11_'.$passage_info['village_id']))) {
            $free_list= cache('freeList_A11_'.$passage_info['village_id']);
        }else {
            $free_list = $db_house_village_park_free->getList(['village_id' => $passage_info['village_id']]);
            cache('freeList_A11_'.$passage_info['village_id'],$free_list, 7200);
        }
        $imageToJob = isset($car_access_record['imageToJob']) ? $car_access_record['imageToJob'] : [];
        $car_access_record = unsetParam($car_access_record, 'imageToJob');
        if (!empty($free_list)) {
            $park_sys_type='A11';
            if($passage_info && isset($passage_info['park_sys_type']) && $passage_info['park_sys_type']){
                $park_sys_type=$passage_info['park_sys_type'];
            }
            $free_list = $free_list->toArray();
            if ($car_number) {
                fdump_api(['免费车信息=='.__LINE__=>$car_number,'free_list' => $free_list],'park_temp/log_'.$car_number,1);
            } elseif ($parkTime) {
                fdump_api(['免费车信息=='.__LINE__=>$car_number,'free_list' => $free_list],'a11_park/plateresult'.$parkTime,true);
            }
            if (!empty($free_list)) {
                foreach ($free_list as $v) {
                    $first_number=substr($car_number,0,strlen($v['first_name']));
                    $last_name=substr($car_number,'-'.strlen($v['last_name']));
                    if ($car_number) {
                        fdump_api(['免费车信息=='.__LINE__=>$car_number,'first_number' => $first_number,'last_name' => $last_name,'first_name' => $v['first_name'],'v_last_name' => $v['last_name'],'free_park' => $v['free_park']],'park_temp/log_'.$car_number,1);
                    } elseif ($parkTime) {
                        fdump_api(['免费车信息条件=='.__LINE__=>$car_number,'first_number' => $first_number,'last_name' => $last_name,'first_name' => $v['first_name'],'v_last_name' => $v['last_name'],'free_park' => $v['free_park']],'a11_park/plateresult'.$parkTime,true);
                    }
                    $isFreePark = false;
                    if (!empty($v['first_name']) && $first_number==$v['first_name']) {
                        $isFreePark = true;
                    }elseif (!empty($v['last_name']) && $last_name==$v['last_name']) {
                        $isFreePark = true;
                    }elseif ($car_number == $v['free_park']) {
                        $isFreePark = true;
                    }
                    if ($isFreePark) {
                        if ($car_number) {
                            fdump_api(['是免费车=='.__LINE__=>$car_number],'park_temp/log_'.$car_number,1);
                        } elseif ($parkTime) {
                            fdump_api(['免费车信息=='.__LINE__=>$car_number,'first_name' => $v['first_name']],'a11_park/plateresult'.$parkTime,true);
                        }
                        $db_in_park->saveOne(['car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'park_sys_type' => 'A11', 'is_out' => 0], $park_data);
                        $out_data['total'] = 0;
                        $out_data['pay_type'] = 'free';
                        $starttime = time() - 30;
                        $endtime = time() + 50;
                        $park_where = [
                            ['car_number', '=', $car_number],
                            ['park_id', '=', $passage_info['village_id']],
                            ['out_time', '>=', $starttime],
                            ['out_time', '<=', $endtime],
                        ];
                        $park_info_car = $db_out_park->getOne($park_where);
                        if ($car_number) {
                            $park_info_car_arr = $park_info_car && !is_array($park_info_car) ? $park_info_car->toArray() : $park_info_car;
                            fdump_api(['旧出场记录=='.__LINE__=>$car_number, 'park_where' => $park_where, 'park_info_car' => $park_info_car_arr,'out_data' => $out_data],'park_temp/log_'.$car_number,1);
                        } elseif ($parkTime) {
                            fdump_api(['免费车信息=='.__LINE__=>$car_number,'park_where' => $park_where,'park_info_car' => $park_info_car,'out_data' => $out_data],'a11_park/plateresult'.$parkTime,true);
                        }
                        //写入车辆入场表
                        if (!isset($park_info_car['id']) || !$park_info_car['id']) {
                            $insert_id= $db_out_park->insertOne($out_data);
                        } else {
                            $insert_id = $park_info_car['id'];
                        }
                        if (!empty($imageToJob)) {
                            $imageToJob['out_park_id'] = $insert_id;
                        }

                        $starttime = time() - 30;
                        $endtime = time() + 50;
                        $park_where = [
                            ['car_number', '=', $car_number],
                            ['park_id', '=', $passage_info['village_id']],
                            ['accessType', '=',2],
                            ['accessTime', '>=', $starttime],
                            ['accessTime', '<=', $endtime],
                        ];
                        $park_info_car = $db_house_village_car_access_record->getOne($park_where);
                        if ($car_number) {
                            $park_info_car_arr = $park_info_car && !is_array($park_info_car) ? $park_info_car->toArray() : $park_info_car;
                            fdump_api(['新的出场记录=='.__LINE__=>$car_number, 'park_where' => $park_where, 'park_info_car' => $park_info_car_arr,'out_data' => $out_data],'park_temp/log_'.$car_number,1);
                        }
                        //写入车辆入场表
                        if (empty($park_info_car)) {
                            $park_where = [
                                ['car_number', '=', $car_number],
                                ['park_id', '=', $passage_info['village_id']],
                                ['accessType', '=',1],
                                ['is_out', '=', 0],
                            ];
                            $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                            $in_record_id = isset($park_info_car111['record_id']) ? intval($park_info_car111['record_id']) : 0;
                            $inAccessTime = isset($park_info_car111['accessTime']) ? intval($park_info_car111['accessTime']) : 0;
                            !$inAccessTime && $inAccessTime = isset($imageToJob['in_time']) ? intval($imageToJob['in_time']) : 0;
                            if ($in_record_id) {
                                $db_house_village_car_access_record->saveOne(['record_id' => $in_record_id], ['is_out' => 1]);
                                if (!empty($imageToJob)) {
                                    $imageToJob['in_record_id'] = $in_record_id;
                                }
                            }
                            if (!isset($car_info['parking_car_type'])||empty($car_info['parking_car_type'])){
                                $db_house_village_park_charge_cartype=new HouseVillageParkChargeCartype();
                                $car_types=$db_house_village_park_charge_cartype->getFind(['village_id'=>$passage_info['village_id'],'car_number_type'=>$car_number_type,'status'=>1,'park_sys_type'=>'A11'],'car_type');
                                if (!empty($car_types)){
                                    $car_info['parking_car_type']=$car_types['car_type'];
                                }else{
                                    $car_info['parking_car_type']=self::Temp_A;
                                }
                            }
                            $car_access_record['business_type'] = 0;
                            $car_access_record['business_id'] = $passage_info['village_id'];
                            $car_access_record['car_number'] = $car_number;
                            $car_access_record['accessType'] = 2;
                            $car_access_record['accessTime'] = $now_time;
                            $car_access_record['park_time'] = $now_time - $inAccessTime;
                            $car_access_record['accessMode'] = '3';
                            $car_access_record['park_sys_type'] = $park_sys_type;
                            $car_access_record['park_car_type'] = $this->parking_a11_car_type_arr[$car_info['parking_car_type']];
                            $car_access_record['is_out'] = 1;
                            $car_access_record['park_id'] = $passage_info['village_id'];
                            $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                            $car_access_order_id = isset($park_info_car111['order_id']) && $park_info_car111['order_id'] ? $park_info_car111['order_id'] : '';
                            $car_access_record['order_id'] = $car_access_order_id;
                            $car_access_record['total'] = 0;
                            // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                            // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                            //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                            $car_access_record['pay_type'] = $this->pay_type[5];
                            $car_access_record['update_time'] = $now_time;
                            $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                            if (isset($insert_id)) {
                                $car_access_record['from_id'] = $insert_id;
                            }
                            $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                            $tmpUpdateRecordArr=array('car_number'=> $car_number,'in_park_id'=>$in_park_id,'village_id'=>$passage_info['village_id'],'record_id'=>$record_id,'access_order_id'=>$car_access_order_id,'access_type'=>2,'access_time'=>time());
                            (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                            if ($car_number) {
                                fdump_api(['记录出场=='.__LINE__=>$car_number,'car_access_record' => $car_access_record,'record_id' => $record_id],'park_temp/log_'.$car_number,1);
                            } elseif ($parkTime) {
                                fdump_api(['记录出场=='.__LINE__=>$car_number,'car_access_record' => $car_access_record,'record_id' => $record_id],'a11_park/plateresult'.$parkTime,true);
                            }
                        } else {
                            $record_id = isset($park_info_car['record_id']) ? intval($park_info_car['record_id']) : 0;
                            $car_access_order_id = isset($park_info_car['order_id']) ? trim($park_info_car['order_id']) : '';
                        }
                        //TODO:屏显和语音提示特殊车辆通行
                        $showscreen_data['content']='免费车通行';
                        if ($car_number) {
                            $park_info_car_arr = $park_info_car && !is_array($park_info_car) ? $park_info_car->toArray() : $park_info_car;
                            fdump_api(['记录出场=='.__LINE__=>$car_number, 'showscreen_data' => $showscreen_data],'park_temp/log_'.$car_number,1);
                        } elseif ($parkTime) {
                            fdump_api(['记录出场=='.__LINE__=>$car_number,'showscreen_data' => $showscreen_data],'a11_park/plateresult'.$parkTime,true);
                        }
                        $this->addParkShowScreenLog($showscreen_data);
                        //发送模板消息
                        $openid=$this->getUserInfo($car_number,$passage_info['village_id']);
                        if (!empty($openid)){
                            $db_house_village_parking_garage=new HouseVillageParkingGarage();
                            $garage_info = $db_house_village_parking_garage->getOne(['garage_id'=>$passage_info['garage_id']]);
                            $this->remarkTxt = '\n入场类型： 免费车';
                            $this->pay_price = '0';
                            $this->stored_balance = isset($car_info['stored_balance']) && $car_info['stored_balance'] ? $car_info['stored_balance'] : 0;
                            $this->sendCarMeassage($openid, $car_number, $car_info, $village_info, $garage_info, $passage_info, 0, $village_info['property_id'], [],time(), false);
                        }
                        //特殊车辆通行
                        if (!empty($imageToJob) && ($record_id || $insert_id)) {
                            if (isset($car_access_order_id) && $car_access_order_id) {
                                $imageToJob['car_access_order_id'] = $car_access_order_id;
                            }
                            $imageToJob['out_record_id'] = $record_id;
                            (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                        }
                        return true;
                    }
                }
            }
        }
        return  false;
    }

    /**
     * 免费车卡类出场
     * @author:zhubaodi
     * @date_time: 2022/11/26 15:36
     */
    public function free_car_type_out($car_number,$passage_info,$park_data,$showscreen_data,$village_info,$parkTime,$car_info,$car_number_type, $park_time = '',$car_access_record=array(),$outRightPosition=array()){
        $db_in_park = new InPark();
        $db_out_park = new OutPark();
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $db_in_park->saveOne(['car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'park_sys_type' => 'A11', 'is_out' => 0], $park_data);
        $db_in_park->saveOne(['car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'park_sys_type' => 'A11', 'is_out' => 0], $park_data);
        
        $imageToJob = isset($car_access_record['imageToJob']) ? $car_access_record['imageToJob'] : [];
        $car_access_record = unsetParam($car_access_record, 'imageToJob');
        
        $out_data['total'] = 0;
        $out_data['pay_type'] = 'free';
        $now_time=time();
        $starttime = time() - 30;
        $endtime = time() + 50;
        $park_where = [
            ['car_number', '=', $car_number],
            ['park_id', '=', $passage_info['village_id']],
            ['out_time', '>=', $starttime],
            ['out_time', '<=', $endtime],
        ];
        $park_info_car = $db_out_park->getOne($park_where);
        if ($parkTime) {
            fdump_api(['免费车信息=='.__LINE__=>$car_number,'park_where' => $park_where,'park_info_car' => $park_info_car,'out_data' => $out_data],'a11_park/plateresult'.$parkTime,true);
        }
        //写入车辆入场表
        if (!isset($park_info_car['id']) || !$park_info_car['id']) {
            $insert_id= $db_out_park->insertOne($out_data);
        } else {
            $insert_id = $park_info_car['id'];
        }
        if (!empty($imageToJob)) {
            $imageToJob['out_park_id'] = $insert_id;
        }

        $starttime = time() - 30;
        $endtime = time() + 50;
        $park_where = [
            ['car_number', '=', $car_number],
            ['park_id', '=', $passage_info['village_id']],
            ['accessType', '=',2],
            ['accessTime', '>=', $starttime],
            ['accessTime', '<=', $endtime],
        ];
        $park_info_car = $db_house_village_car_access_record->getOne($park_where);
        //写入车辆入场表
        if (empty($park_info_car)) {
            $park_where = [
                ['car_number', '=', $car_number],
                ['park_id', '=', $passage_info['village_id']],
                ['accessType', '=',1],
                ['is_out', '=', 0],
            ];
            $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
            $in_record_id = isset($park_info_car111['record_id']) ? intval($park_info_car111['record_id']) : 0;
            $inAccessTime = isset($park_info_car111['accessTime']) ? intval($park_info_car111['accessTime']) : 0;
            !$inAccessTime && $inAccessTime = isset($imageToJob['in_time']) ? intval($imageToJob['in_time']) : 0;
            if ($in_record_id) {
                $db_house_village_car_access_record->saveOne(['record_id' => $in_record_id], ['is_out' => 1]);
                if (!empty($imageToJob)) {
                    $imageToJob['in_record_id'] = $in_record_id;
                }
            }
            if (!isset($car_info['parking_car_type'])||empty($car_info['parking_car_type'])){
                $db_house_village_park_charge_cartype=new HouseVillageParkChargeCartype();
                $car_types=$db_house_village_park_charge_cartype->getFind(['village_id'=>$passage_info['village_id'],'car_number_type'=>$car_number_type,'status'=>1,'park_sys_type'=>'A11'],'car_type');
                if (!empty($car_types)){
                    $car_info['parking_car_type']=$car_types['car_type'];
                }else{
                    $car_info['parking_car_type']=self::Temp_A;
                }
            }
            $car_access_record['business_type'] = 0;
            $car_access_record['business_id'] = $passage_info['village_id'];
            $car_access_record['car_number'] = $car_number;
            $car_access_record['accessType'] = 2;
            $car_access_record['accessTime'] = $now_time;
            $car_access_record['park_time'] = $now_time-$park_info_car111['accessTime'];
            $car_access_record['accessMode'] = '3';
            $car_access_record['park_sys_type'] = 'A11';
            $car_access_record['park_car_type'] = $this->parking_a11_car_type_arr[$car_info['parking_car_type']];
            $car_access_record['is_out'] = 1;
            $car_access_record['park_id'] = $passage_info['village_id'];
            $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
            $car_access_order_id = isset($park_info_car111['order_id']) && $park_info_car111['order_id'] ? $park_info_car111['order_id'] : '';
            $car_access_record['order_id'] = $car_access_order_id;
            $car_access_record['total'] = 0;
            // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
            // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
            //交易流水号(pay_type为wallet、scancode、sweepcode必传)
            $car_access_record['pay_type'] = $this->pay_type[5];
            $car_access_record['update_time'] = $now_time;
            $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
            if (isset($insert_id)) {
                $car_access_record['from_id'] = $insert_id;
            }
            $record_id = $db_house_village_car_access_record->addOne($car_access_record);
            if(!empty($outRightPosition)){
                //释放车位
                $whereArr=array('position_id'=>$outRightPosition['position_id'],'use_car_id'=>$car_info['car_id']);
                $saveArr=array('use_car_id'=>0,'use_start_time'=>0,'use_car_number'=>'','access_record_id'=>$record_id);
                $this->updateParkingPositionUse($whereArr,$saveArr,$car_number);
            }
            if ($parkTime) {
                fdump_api(['记录出场=='.__LINE__=>$car_number,'car_access_record' => $car_access_record,'record_id' => $record_id],'a11_park/plateresult'.$parkTime,true);
            }
        } else {
            $record_id = isset($park_info_car['record_id']) ? intval($park_info_car['record_id']) : 0;
            $car_access_order_id = isset($park_info_car['order_id']) ? trim($park_info_car['order_id']) : '';
        }
        //TODO:屏显和语音提示特殊车辆通行
        $showscreen_data['content']='免费车通行';
        if ($parkTime) {
            fdump_api(['记录出场=='.__LINE__=>$car_number,'showscreen_data' => $showscreen_data],'a11_park/plateresult'.$parkTime,true);
        }
        $this->addParkShowScreenLog($showscreen_data);
        //发送模板消息
        $openid=$this->getUserInfo($car_number,$passage_info['village_id']);
        if (!empty($openid)){
            $hours = intval($park_time / 3600);
            $mins = intval(($park_time - $hours * 3600) / 60);
            $second = $park_time - $hours * 3600 - $mins * 60;
            $time = '';
            if ($hours > 0) {
                $time .= $hours . '小时';
            }
            if ($mins > 0) {
                $time .= $mins . '分钟';
            }
            if ($second > 0) {
                $time .= $second . '秒';
            }
            $db_house_village_parking_garage=new HouseVillageParkingGarage();
            $garage_info = $db_house_village_parking_garage->getOne(['garage_id'=>$passage_info['garage_id']]);
            $this->remarkTxt      = '\n支付金额： 免费车';
            $this->pay_price = '0';
            $this->stored_balance = isset($car_info['stored_balance']) && $car_info['stored_balance'] ? $car_info['stored_balance'] : 0;
            $this->sendCarMeassage($openid, $car_number, $car_info, $village_info, $garage_info, $passage_info, $time, $village_info['property_id'], [],$now_time, false);
        }
        //特殊车辆通行
        if (!empty($imageToJob) && ($record_id || $insert_id)) {
            if (isset($car_access_order_id) && $car_access_order_id) {
                $imageToJob['car_access_order_id'] = $car_access_order_id;
            }
            $imageToJob['out_record_id'] = $record_id;
            (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
        }
        return true;
    }

    /**
     * 免费车入场
     * @author:zhubaodi
     * @date_time: 2022/11/23 15:36
     */
    public function free_in_park($car_number,$passage_info,$park_data,$village_info,$showscreen_data,$now_time,$parkTime,$car_number_type,$car_access_record=array()){
        $db_house_village_park_free = new HouseVillageParkFree();
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $db_in_park = new InPark();
        $imageToJob = isset($car_access_record['imageToJob']) ? $car_access_record['imageToJob'] : [];
        $car_access_record = unsetParam($car_access_record, 'imageToJob');
        //查询免费车
        $free_list = $db_house_village_park_free->getList(['village_id' => $passage_info['village_id']]);
        $park_sys_type='A11';
        if($passage_info && isset($passage_info['park_sys_type']) && $passage_info['park_sys_type']){
            $park_sys_type=$passage_info['park_sys_type'];
        }
        if (!empty($free_list)) {
            $free_list = $free_list->toArray();
            if ($parkTime) {
                fdump_api(['免费车=='.__LINE__=>$car_number,'free_list' => $free_list],'a11_park/plateresult'.$parkTime,true);
            }
            if (!empty($free_list)) {
                foreach ($free_list as $v) {
                    $first_number=substr($car_number,0,strlen($v['first_name']));
                    $last_name=substr($car_number,'-'.strlen($v['last_name']));
                    if ($parkTime) {
                        fdump_api(['免费车=='.__LINE__=>$car_number,'first_number' => $first_number,'last_name' => $last_name],'a11_park/plateresult'.$parkTime,true);
                    }
                    $isFreePark = false;
                    if (!empty($v['first_name']) && $first_number==$v['first_name']) {
                        $isFreePark = true;
                    }elseif (!empty($v['last_name']) && $last_name==$v['last_name']) {
                        $isFreePark = true;
                    }elseif ($car_number == $v['free_park']) {
                        $isFreePark = true;
                    }
                    if($isFreePark) {
                        if ($parkTime) {
                            fdump_api(['符合免费车条件=='.__LINE__=>$car_number,'first_name' => $v['first_name']],'a11_park/plateresult'.$parkTime,true);
                        }
                        $starttime = time() - 30;
                        $endtime = time() + 50;
                        $park_where = [
                            ['car_number', '=', $car_number],
                            ['park_id', '=', $passage_info['village_id']],
                            ['in_channel_id','=',$passage_info['id']],
                            ['in_time', '>=', $starttime],
                            ['in_time', '<=', $endtime],
                            ['del_time', '=', 0],
                        ];
                        $park_info_car = $db_in_park->getOne1($park_where);
                        //写入车辆入场表
                        if (empty($park_info_car)) {
                            $insert_id=$db_in_park->insertOne($park_data);
                            if (!isset($car_info['parking_car_type'])||empty($car_info['parking_car_type'])){
                                $db_house_village_park_charge_cartype=new HouseVillageParkChargeCartype();
                                $car_types=$db_house_village_park_charge_cartype->getFind(['village_id'=>$passage_info['village_id'],'car_number_type'=>$car_number_type,'status'=>1,'park_sys_type'=>'A11'],'car_type');
                                if (!empty($car_types)){
                                    $car_info['parking_car_type']=$car_types['car_type'];
                                }else{
                                    $car_info['parking_car_type']=self::Temp_A;
                                }
                            }
                            if (!empty($imageToJob)) {
                                $imageToJob['in_park_id'] = $insert_id;
                            }
                            $car_access_record['business_type'] = 0;
                            $car_access_record['business_id'] = $passage_info['village_id'];
                            $car_access_record['car_number'] = $car_number;
                            $car_access_record['accessType'] = 1;
                            $car_access_record['accessTime'] = $now_time;
                            $car_access_record['accessMode'] = 8;
                            $car_access_record['park_sys_type'] = $park_sys_type;
                            $car_access_record['park_car_type'] = $this->parking_a11_car_type_arr[$car_info['parking_car_type']];
                            $car_access_record['is_out'] = 0;
                            $car_access_record['park_id'] = $passage_info['village_id'];
                            $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                            $car_access_order_id = date('YmdHis') . rand(100, 999);
                            $car_access_record['order_id'] = $car_access_order_id;
                            $car_access_record['update_time'] = $now_time;
                            if (isset($insert_id)&&!empty($insert_id)) {
                                $car_access_record['from_id'] = $insert_id;
                            }
                            if ($parkTime) {
                                fdump_api(['记录通行记录=='.__LINE__=>$car_number,'car_access_record' => $car_access_record],'a11_park/plateresult'.$parkTime,true);
                            }
                            $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                            if ($parkTime) {
                                fdump_api(['记录通行记录=='.__LINE__=>$car_number,'record_id' => $record_id],'a11_park/plateresult'.$parkTime,true);
                            }
                            if (!empty($imageToJob) && ($record_id || $insert_id)) {
                                if (isset($car_access_order_id) && $car_access_order_id) {
                                    $imageToJob['car_access_order_id'] = $car_access_order_id;
                                }
                                $imageToJob['in_record_id'] = $record_id;
                                (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                            }
                        }
                        //TODO:屏显和语音提示特殊车辆通行
                        if ($parkTime) {
                            fdump_api(['屏显和语音提示特殊车辆通行=='.__LINE__=>$car_number,'showscreen_data' => $showscreen_data],'a11_park/plateresult'.$parkTime,true);
                        }
                        $this->addParkShowScreenLog($showscreen_data);
                        //发送模板消息
                        $openid=$this->getUserInfo($car_number,$passage_info['village_id']);
                        if (!empty($openid)){
                            $db_house_village_parking_garage=new HouseVillageParkingGarage();
                            $garage_info = $db_house_village_parking_garage->getOne(['garage_id'=>$passage_info['garage_id']]);
                            $this->remarkTxt      = '\n入场类型： 免费车';
                            $this->pay_price = '0';
                            $this->stored_balance = isset($car_info['stored_balance']) && $car_info['stored_balance'] ? $car_info['stored_balance'] : 0;
                            $this->sendCarMeassage($openid, $car_number, $car_info, $village_info, $garage_info, $passage_info, 0, $village_info['property_id'], [],$now_time, false);
                        }
                        //特殊车辆通行
                        return true;
                    }
                }
            }
        } 
        return false;
    }

    /**
     * 免费车入场
     * @author:zhubaodi
     * @date_time: 2022/11/23 15:36
     */
    public function free_car_type_in($car_number,$passage_info,$park_data,$village_info,$showscreen_data,$now_time,$parkTime,$car_info,$car_number_type,$car_access_record=array()){
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $db_in_park = new InPark();
        $starttime = time() - 30;
        $endtime = time() + 50;
        $park_where = [
            ['car_number', '=', $car_number],
            ['park_id', '=', $passage_info['village_id']],
            ['in_channel_id','=',$passage_info['id']],
            ['in_time', '>=', $starttime],
            ['in_time', '<=', $endtime],
            ['del_time', '=', 0],
        ];
        $park_info_car = $db_in_park->getOne1($park_where);
        if ($car_number) {
            $park_info_car_arr = $park_info_car && !is_array($park_info_car) ? $park_info_car->toArray() : $park_info_car;
            fdump_api(['免费车入场查询入场记录'=>$car_number, 'park_sys_type'=>'A11','park_info_car' => $park_info_car_arr],'park_temp/log_'.$car_number,1);
        }
        $imageToJob = isset($car_access_record['imageToJob']) ? $car_access_record['imageToJob'] : [];
        $car_access_record = unsetParam($car_access_record, 'imageToJob');
        //写入车辆入场表
        if (empty($park_info_car)) {
            $insert_id=$db_in_park->insertOne($park_data);
            if (!isset($car_info['parking_car_type'])||empty($car_info['parking_car_type'])){
                $db_house_village_park_charge_cartype=new HouseVillageParkChargeCartype();
                $car_types=$db_house_village_park_charge_cartype->getFind(['village_id'=>$passage_info['village_id'],'car_number_type'=>$car_number_type,'status'=>1,'park_sys_type'=>'A11'],'car_type');
                if (!empty($car_types)){
                    $car_info['parking_car_type']=$car_types['car_type'];
                }else{
                    $car_info['parking_car_type']=self::Temp_A;
                }
            }
            if (!empty($imageToJob)) {
                $imageToJob['in_park_id'] = $insert_id;
            }
            $car_access_record['business_type'] = 0;
            $car_access_record['business_id'] = $passage_info['village_id'];
            $car_access_record['car_number'] = $car_number;
            $car_access_record['accessType'] = 1;
            $car_access_record['accessTime'] = $now_time;
            $car_access_record['accessMode'] = 8;
            $car_access_record['park_sys_type'] = 'A11';
            $car_access_record['park_car_type'] = $this->parking_a11_car_type_arr[$car_info['parking_car_type']];
            $car_access_record['is_out'] = 0;
            $car_access_record['park_id'] = $passage_info['village_id'];
            $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
            $car_access_order_id = date('YmdHis') . rand(100, 999);
            $car_access_record['order_id'] = $car_access_order_id;
            $car_access_record['update_time'] = $now_time;
            if (isset($insert_id)&&!empty($insert_id)) {
                $car_access_record['from_id'] = $insert_id;
            }
            if ($parkTime) {
                fdump_api(['记录通行记录=='.__LINE__=>$car_number,'car_access_record' => $car_access_record],'a11_park/plateresult'.$parkTime,true);
            }
            if ($car_number) {
                fdump_api(['记录通行记录'=>$car_number, 'park_sys_type'=>'A11','car_access_record' => $car_access_record],'park_temp/log_'.$car_number,1);
            } elseif ($parkTime) {
                fdump_api(['记录通行记录=='.__LINE__=>$car_number,'car_access_record' => $car_access_record],'a11_park/plateresult'.$parkTime,true);
            }
            $record_id = $db_house_village_car_access_record->addOne($car_access_record);
            if ($car_number) {
                fdump_api(['记录通行记录'=>$car_number, 'park_sys_type'=>'A11','record_id' => $record_id],'park_temp/log_'.$car_number,1);
            } elseif ($parkTime) {
                fdump_api(['记录通行记录=='.__LINE__=>$car_number,'record_id' => $record_id],'a11_park/plateresult'.$parkTime,true);
            }
            if (!empty($imageToJob) && ($record_id || $insert_id)) {
                if (isset($car_access_order_id) && $car_access_order_id) {
                    $imageToJob['car_access_order_id'] = $car_access_order_id;
                }
                $imageToJob['in_record_id'] = $record_id;
                (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
            }
        }
        //TODO:屏显和语音提示特殊车辆通行
        if ($car_number) {
            fdump_api(['屏显和语音提示特殊车辆通行'=>$car_number, 'park_sys_type'=>'A11','showscreen_data' => $showscreen_data],'park_temp/log_'.$car_number,1);
        } elseif ($parkTime) {
            fdump_api(['屏显和语音提示特殊车辆通行=='.__LINE__=>$car_number,'showscreen_data' => $showscreen_data],'a11_park/plateresult'.$parkTime,true);
        }
        $this->addParkShowScreenLog($showscreen_data);
        //发送模板消息
        $openid=$this->getUserInfo($car_number,$passage_info['village_id']);
        if (!empty($openid)){
            $db_house_village_parking_garage=new HouseVillageParkingGarage();
            $garage_info = $db_house_village_parking_garage->getOne(['garage_id'=>$passage_info['garage_id']]);
            $this->remarkTxt      = '\n入场类型： 免费车';
            $this->pay_price = '0';
            $this->stored_balance = isset($car_info['stored_balance']) && $car_info['stored_balance'] ? $car_info['stored_balance'] : 0;
            $this->sendCarMeassage($openid, $car_number, $car_info, $village_info, $garage_info, $passage_info, 0, $village_info['property_id'], [],time(), false);
        }
        //特殊车辆通行
        return true;
    }

    /**
     * 月租车入场
     * @author:zhubaodi
     * @date_time: 2022/11/23 17:42
     */
    public function month_in_park($car_number,$passage_info,$car_info,$park_data,$village_info,$showscreen_data,$parkTime,$car_number_type,$car_access_record=array(),$rightPosition=array()){
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $db_in_park = new InPark();
        $now_time=time();
        $starttime = $now_time - 30;
        $endtime = $now_time + 50;
        $park_where = [
            ['car_number', '=', $car_number],
            ['park_id', '=', $passage_info['village_id']],
            ['in_channel_id','=',$passage_info['id']],
            ['in_time', '>=', $starttime],
            ['in_time', '<=', $endtime],
            ['del_time', '=', 0],
        ];
        if ($car_number) {
            fdump_api(['查询下1分钟内入场时间'=>$car_number,'park_where'=>$park_where,'park_sys_type'=>'A11'],'park_temp/log_'.$car_number,1);
        } elseif ($parkTime) {
            fdump_api(['查询下1分钟内入场时间=='.__LINE__=>$car_number,'park_where' => $park_where],'a11_park/plateresult'.$parkTime,true);
        }
        $park_info_car = $db_in_park->getOne1($park_where);
        if ($car_number) {
            $park_info_car_arr = $park_info_car && !is_array($park_info_car) ? $park_info_car->toArray() : $park_info_car;
            fdump_api(['查询结果'=>$car_number,'park_where'=>$park_where,'park_sys_type'=>'A11','park_info_car'=>$park_info_car_arr],'park_temp/log_'.$car_number,1);
        } elseif ($parkTime) {
            fdump_api(['查询条件=='.__LINE__=>$car_number,'park_info_car' => $park_info_car],'a11_park/plateresult'.$parkTime,true);
        }
        //查询设备所属车库属性
        $db_house_village_parking_garage=new HouseVillageParkingGarage();
        $garage_id=0;
        if (!empty($passage_info['passage_area'])&&$passage_info['area_type']==2){
            $garage_id=$passage_info['passage_area'];
        }
        if(isset($passage_info['garage_id']) && $passage_info['garage_id']>0){
            $garage_id=$passage_info['garage_id'];
        }
        $garage_info=array();
        $is_real_garage=1;
        if($garage_id>0){
            $garage_info=$db_house_village_parking_garage->getOne(['garage_id'=>$garage_id]);
            if($garage_info && !$garage_info->isEmpty()){
                $garage_info=$garage_info->toArray();
                if($garage_info['fid']>0 && $garage_info['fid']==$garage_info['garage_id']){
                    $garage_info['fid']=0;
                }

                if($garage_info['fid']>0 && $garage_info['fid']!=$garage_info['garage_id'] ){
                    $is_real_garage=0;
                }
            }
        }
        $imageToJob = isset($car_access_record['imageToJob']) ? $car_access_record['imageToJob'] : [];
        $car_access_record = unsetParam($car_access_record, 'imageToJob');
        //写入车辆入场表
        $insert_id=0;
        if (empty($park_info_car)) {
            $insert_id=$db_in_park->insertOne($park_data);
            if (!isset($car_info['parking_car_type'])||empty($car_info['parking_car_type'])){
                $db_house_village_park_charge_cartype=new HouseVillageParkChargeCartype();
                $car_types=$db_house_village_park_charge_cartype->getFind(['village_id'=>$passage_info['village_id'],'car_number_type'=>$car_number_type,'status'=>1,'park_sys_type'=>'A11'],'car_type');
                if (!empty($car_types)){
                    $car_info['parking_car_type']=$car_types['car_type'];
                }else{
                    $car_info['parking_car_type']=self::Temp_A;
                }
            }
            if (!empty($imageToJob)) {
                $imageToJob['in_park_id'] = $insert_id;
            }
            $car_access_record['business_type'] = 0;
            $car_access_record['business_id'] = $passage_info['village_id'];
            $car_access_record['car_number'] = $car_number;
            $car_access_record['accessType'] = 1;
            $car_access_record['accessTime'] = $now_time;
            $car_access_record['accessMode'] = 5;
            $car_access_record['park_sys_type'] = 'A11';
            $car_access_record['park_car_type'] = $this->parking_a11_car_type_arr[$car_info['parking_car_type']];
            $car_access_record['is_out'] = 0;
            $car_access_record['park_id'] = $passage_info['village_id'];
            $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
            $car_access_order_id = date('YmdHis') . rand(100, 999);
            $car_access_record['order_id'] = $car_access_order_id;
            $car_access_record['update_time'] = $now_time;
            if (isset($insert_id)&&!empty($insert_id)) {
                $car_access_record['from_id'] = $insert_id;
            }
            if ($car_number) {
                fdump_api(['入车辆入场表'=>$car_number,'park_where'=>$park_where,'park_sys_type'=>'A11','car_access_record'=>$car_access_record],'park_temp/log_'.$car_number,1);
            } elseif ($parkTime) {
                fdump_api(['入车辆入场表=='.__LINE__=>$car_number,'car_access_record' => $car_access_record],'a11_park/plateresult'.$parkTime,true);
            }
            $record_id = $db_house_village_car_access_record->addOne($car_access_record);

            if(!empty($rightPosition)) {
                $whereArr = array('position_id' => $rightPosition['position_id']);
                $saveArr = array('use_car_id' => $car_info['car_id'], 'use_start_time' => $now_time, 'use_car_number' => $car_number, 'access_record_id' => $record_id);
                $this->updateParkingPositionUse($whereArr, $saveArr, $car_number, 'in_park');
            }
            //将此条之前的 都改掉
            if($record_id>0 && $is_real_garage>0) {
                /*
                $wherexArr = [];
                $wherexArr[]=['record_id','<',$record_id];
                $wherexArr[] = ['car_number', '=', $car_number];
                $wherexArr[] = ['is_out', '=', 0];
                $wherexArr[] = ['park_id', '=', $passage_info['village_id']];
                $db_house_village_car_access_record->saveOne($wherexArr, ['is_out' => 1, 'update_time' => $now_time]);
                */
                $tmpUpdateRecordArr = array('car_number' => $car_number, 'in_park_id' => $insert_id, 'village_id' => $passage_info['village_id'], 'record_id' => $record_id, 'access_order_id' => $car_access_order_id, 'access_type' => 1, 'access_time' => time());
                (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
            }
            if ($car_number) {
                fdump_api(['入车辆入场表'=>$car_number,'park_where'=>$park_where,'park_sys_type'=>'A11','record_id'=>$record_id],'park_temp/log_'.$car_number,1);
            } elseif ($parkTime) {
                fdump_api(['入车辆入场表=='.__LINE__=>$car_number,'car_access_record' => $car_access_record],'a11_park/plateresult'.$parkTime,true);
            }
            if (!empty($imageToJob) && ($record_id || $insert_id)) {
                if (isset($car_access_order_id) && $car_access_order_id) {
                    $imageToJob['car_access_order_id'] = $car_access_order_id;
                }
                $imageToJob['in_record_id'] = $record_id;
                (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
            }
        }
        //TODO:屏显和语音提示特殊车辆通行
        $showscreen_data['voice_content']= 4;
        $showscreen_data['car_type']= 'month_type';
        $showscreen_data['end_time']= $car_info['end_time'];
        $showscreen_data['end_time_txt']= date('Y-m-d',$car_info['end_time']);
        if ($car_number) {
            fdump_api(['屏显和语音提示特殊车辆通行'=>$car_number,'park_where'=>$park_where,'park_sys_type'=>'A11','showscreen_data'=>$showscreen_data],'park_temp/log_'.$car_number,1);
        } elseif ($parkTime) {
            fdump_api(['屏显和语音提示特殊车辆通行=='.__LINE__=>$car_number,'showscreen_data' => $showscreen_data],'a11_park/plateresult'.$parkTime,true);
        }
        $this->addParkShowScreenLog($showscreen_data);
        //发送模板消息
        $openid=$this->getUserInfo($car_number,$passage_info['village_id']);
        if (!empty($openid)){
            $db_house_village_parking_garage=new HouseVillageParkingGarage();
            $garage_info = $db_house_village_parking_garage->getOne(['garage_id'=>$passage_info['garage_id']]);
            $this->remarkTxt      = '\n入场类型： 月租车';
            $this->pay_price = '0';
            $this->stored_balance = isset($car_info['stored_balance']) && $car_info['stored_balance'] ? $car_info['stored_balance'] : 0;
            $this->sendCarMeassage($openid, $car_number, $car_info, $village_info, $garage_info, $passage_info, 0, $village_info['property_id'], [],time(), false);
        }
        //特殊车辆通行
        return true;
    }


    /**
     * 临时车入场
     * @author:zhubaodi
     * @date_time: 2022/11/23 17:42
     */
    public function temp_in_park($car_number,$passage_info,$car_info,$park_data,$car_access_record,$village_info,$showscreen_data,$parkTime,$car_number_type){
        $db_house_village_park_config = new HouseVillageParkConfig();
        $db_house_village_visitor = new HouseVillageVisitor();
        $db_house_village_parking_garage=new HouseVillageParkingGarage();
        $garage_id=0;
        if($passage_info && isset($passage_info['garage_id']) && $passage_info['garage_id']>0){
            $garage_id=$passage_info['garage_id'];
        }else if($passage_info && isset($passage_info['passage_area']) && !empty($passage_info['passage_area']) && $passage_info['area_type']==2){
            $garage_id=intval($passage_info['passage_area']);
        }
        $now_time=time();
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $park_config =$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$passage_info['village_id']);
        $showscreen_data=!empty($showscreen_data) ?$showscreen_data:array();
        if (!empty($car_info['garage_id'])&&$car_info['end_time']>$now_time){
            $garage_info = $db_house_village_parking_garage->getOne(['garage_id'=>$car_info['garage_id']]);
            $garage_info1 = $db_house_village_parking_garage->getOne(['garage_id'=>$passage_info['garage_id']]);
         
            if (!empty($garage_info)&&!empty($garage_info['fid'])&&$garage_info['fid']==$passage_info['garage_id']){
                //写入进场信息
                if ($car_number) {
                    fdump_api(['[月租车]车辆对应车库父级车库为当前通行车道车库'=>$car_number,'fid'=>$garage_info['fid'],'garage_id'=>$passage_info['garage_id'],'park_sys_type'=>'A11'],'park_temp/log_'.$car_number,1);
                }
                $res_month1=$this->add_temp_in_park($car_number,$passage_info,$park_data,$showscreen_data,$car_access_record,$village_info,$now_time,$parkTime,$car_info,$car_number_type);
                if ($res_month1){
                    //发送模板消息
                    $openid=$this->getUserInfo($car_number,$passage_info['village_id']);
                    if (!empty($openid)){
                        $this->remarkTxt      = '\n入场类型： 临时车';
                        $this->stored_balance = isset($car_info['stored_balance']) && $car_info['stored_balance'] ? $car_info['stored_balance'] : 0;
                        $this->sendCarMeassage($openid, $car_number, $car_info, $village_info, $garage_info1, $passage_info, 0, $village_info['property_id'], [],time(), false);
                    }
                    return $res_month1;
                }
            }
            if (!empty($garage_info1)&&!empty($garage_info1['fid'])&&$garage_info1['fid']==$car_info['garage_id']&&$garage_info1['is_month_access']==1){
              //写入进场信息
                if ($car_number) {
                    fdump_api(['[月租车]车辆对应车库为当前通行车道车库的父车库'=>$car_number,'fid'=>$garage_info1['fid'],'garage_id'=>$car_info['garage_id'],'park_sys_type'=>'A11'],'park_temp/log_'.$car_number,1);
                }
               $res_month=$this->add_temp_in_park($car_number,$passage_info,$park_data,$showscreen_data,$car_access_record,$village_info,$now_time,$parkTime,$car_info,$car_number_type);
               if ($res_month){
                   //发送模板消息
                   $openid=$this->getUserInfo($car_number,$passage_info['village_id']);
                   if (!empty($openid)){
                       $this->remarkTxt      = '\n入场类型： 临时车';
                       $this->stored_balance = isset($car_info['stored_balance']) && $car_info['stored_balance'] ? $car_info['stored_balance'] : 0;
                       $this->sendCarMeassage($openid, $car_number, $car_info, $village_info, $garage_info1, $passage_info, 0, $village_info['property_id'], [],time(), false);
                   }
                   return $res_month;
               }
            }
            elseif (!empty($garage_info1)&&!empty($garage_info1['fid'])&&$garage_info1['fid']==$car_info['garage_id']&&$garage_info1['is_month_access']==0){
                $showscreen_data['car_type']= 'temporary_type';
                $showscreen_data['content']= '临时车禁止通行';
                $showscreen_data['voice_content']= 7;
                //TODO:屏显和语音提示错误信息
                $this->addParkShowScreenLog($showscreen_data);
                //停车设备不存在
                if ($car_number) {
                    fdump_api(['临时车禁止通行'=>$car_number,'showscreen_data'=>$showscreen_data,'park_config'=>$park_config,'park_sys_type'=>'A11'],'park_temp/log_'.$car_number,1);
                } elseif ($parkTime) {
                    fdump_api(['停车设备不存在=='.__LINE__=>$car_number,'showscreen_data' => $showscreen_data],'a11_park/plateresult'.$parkTime,true);
                }
                return false;
            }
        }
        $expire_month_car_notin=0;
        //曾今是月租车 现在过期了
        if(($car_info && $car_info['end_time']>100 && $car_info['end_time']<$now_time) && $park_config && isset($park_config['expire_month_car_type']) && $park_config['expire_month_car_type']){
            /*** 1月租车过期按临时车收费2月租车过期禁止入场3过期多少天后禁止入场**/
            $expire_month_car_type=$park_config['expire_month_car_type'];
            $expire_month_car_day=$park_config['expire_month_car_day'];
            if($expire_month_car_type==2 || ($expire_month_car_type==3 && empty($expire_month_car_day))){
                $expire_month_car_notin=1;
            }else if($expire_month_car_type==3 && $expire_month_car_day>0){
                $expire_month_car_second=$expire_month_car_day*24*3600;
                $month_end_time=!empty($car_info) && $car_info['end_time']>0 ? $car_info['end_time']:0;
                $expire_month_time_tmp=$now_time-$month_end_time;
                if($expire_month_time_tmp>$expire_month_car_second){
                    $expire_month_car_notin=1;
                }
            }
        }
        //开启禁止临时车入场配置，禁止临时车入场
        if (!empty($park_config)&&(empty($park_config['temp_in_park_type']) || ($expire_month_car_notin==1))){
            $showscreen_data['car_type']= 'temporary_type';
            $showscreen_data['content']= '临时车禁止通行';
            $showscreen_data['voice_content']= 7;
            //TODO:屏显和语音提示错误信息
            $this->addParkShowScreenLog($showscreen_data);
            //停车设备不存在
            if ($car_number) {
                fdump_api(['临时车禁止通行'=>$car_number,'showscreen_data'=>$showscreen_data,'park_config'=>$park_config,'park_sys_type'=>'A11'],'park_temp/log_'.$car_number,1);
            } elseif ($parkTime) {
                fdump_api(['停车设备不存在=='.__LINE__=>$car_number,'showscreen_data' => $showscreen_data],'a11_park/plateresult'.$parkTime,true);
            }
            return false;
        }
        //获取配置的免登记时长
        elseif (!empty($park_config)&&!empty($park_config['register_day'])){
            $addTime=strtotime(date('Y-m-d 00:00:00'))-$park_config['register_day']*86400;
        }
        else{
            $addTime=strtotime(date('Y-m-d 00:00:00'))-$this->register_day*86400;
            
        }
        if (!empty($car_info)){
            //写入进场信息
            if ($car_number) {
                fdump_api(['[临时车]小区车辆直接进入'=>$car_number ,'park_sys_type'=>'A11'],'park_temp/log_'.$car_number,1);
            }
            $res_temp=$this->add_temp_in_park($car_number,$passage_info,$park_data,$showscreen_data,$car_access_record,$village_info,$now_time,$parkTime,$car_info,$car_number_type);
            if ($res_temp){
                //发送模板消息
                $openid=$this->getUserInfo($car_number,$passage_info['village_id']);
                if (!empty($openid)){
                    $this->remarkTxt      = '\n入场类型： 临时车';
                    $this->stored_balance = isset($car_info['stored_balance']) && $car_info['stored_balance'] ? $car_info['stored_balance'] : 0;
                    $this->sendCarMeassage($openid, $car_number, $car_info, $village_info, $garage_info1, $passage_info, 0, $village_info['property_id'], [],time(), false);
                }
                return $res_temp;
            }
        }
        //查询临时车
        $where_temp = [
            ['car_id', '=', $car_number],
            ['village_id', '=', $passage_info['village_id']],
            ['add_time', '>',$addTime],
            ['add_time', '<', strtotime(date('Y-m-d 23:59:59'))],
        ];
        if ($car_number) {
            fdump_api(['[临时车]查询临时车'=>$car_number ,'park_sys_type'=>'A11','where_temp'=>$where_temp],'park_temp/log_'.$car_number,1);
        } elseif ($parkTime) {
            fdump_api(['查询临时车=='.__LINE__=>$car_number,'where_temp' => $where_temp],'a11_park/plateresult'.$parkTime,true);
        }
        if(!empty($park_config) && !empty($park_config['register_day'])){
            //没设置时间 不默认15天
            $temp_info = $db_house_village_visitor->get_one($where_temp);
        }else{
            $temp_info=array();
        }
        if ($car_number) {
            fdump_api(['[临时车]查询临时车'=>$car_number ,'park_sys_type'=>'A11','temp_info'=>$temp_info],'park_temp/log_'.$car_number,1);
        } elseif ($parkTime) {
            fdump_api(['查询临时车结果=='.__LINE__=>$car_number,'park_config' => $park_config,'temp_info' => $temp_info,'where_temp' => $where_temp],'a11_park/plateresult'.$parkTime,true);
        }
        // 是否是多车多位的月租车
        $isCarsAndPositionsMonth = 0;
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        if (isset($car_info['car_position_id']) && intval($car_info['car_position_id']) > 0 && isset($car_info['car_id']) && intval($car_info['car_id']) > 0) {
            try {
                $wherePosition = [];
                $wherePosition[] = ['village_id', '=', $passage_info['village_id']];
                $wherePosition[] = ['position_id', '=', intval($car_info['car_position_id'])];
                $wherePosition[] = ['end_time', '>=', $now_time];
                $positionObj = $db_house_village_parking_position->getFindOne($wherePosition, 'position_id, village_id, start_time, end_time');
                if ($positionObj && !is_array($positionObj)) {
                    $position = $positionObj->toArray();
                }
                if (!empty($position) && isset($position['position_id']) && $position['position_id']) {
                    $isCarsAndPositionsMonth = 1;
                }
            } catch (\Exception $e) {
                fdump_api(['执行错误' => $e->getMessage(), 'car_number' => $car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'func_get_args' => func_get_args()], '$a11IsCarsAndPositionsMonth');
            }
        }
        $showscreen_data['car_type']= 'temporary_type';
        $showscreen_data['content']= '临时车未登记，请扫码入场';
        $showscreen_data['voice_content']= 3;
        //开启临时车登记配置项，查询登记信息
        if (empty($temp_info) && $park_config['register_type'] == 1 && $isCarsAndPositionsMonth == 0) {
            //TODO:屏显和语音提示错误信息
            $this->addParkShowScreenLog($showscreen_data);
            //停车设备不存在
            if ($car_number) {
                fdump_api(['屏显和语音提示错误信息'=>$car_number ,'park_sys_type'=>'A11','showscreen_data'=>$showscreen_data],'park_temp/log_'.$car_number,1);
            } elseif ($parkTime) {
                fdump_api(['屏显和语音提示错误信息=='.__LINE__=>$car_number],'a11_park/plateresult'.$parkTime,true);
            }
            return false;
        }  else {
            if(!empty($car_access_record['user_name']) && isset($temp_info['visitor_name'])&& !empty($temp_info['visitor_name'])){
                $car_access_record['user_name']=$temp_info['visitor_name'];
            }
            if(!empty($car_access_record['user_phone'])&& isset($temp_info['visitor_phone']) && !empty($temp_info['visitor_phone'])){
                $car_access_record['user_phone']=$temp_info['visitor_phone'];
            }
            if(!empty($car_access_record['uid'])&& isset($temp_info['uid']) && !empty($temp_info['uid'])){
                $car_access_record['uid']=$temp_info['uid'];
            }
            //写入进场信息
            if ($car_number) {
                fdump_api(['[临时车]登记过直接进场'=>$car_number ,'park_sys_type'=>'A11'],'park_temp/log_'.$car_number,1);
            }
            $res_temp1=$this->add_temp_in_park($car_number,$passage_info,$park_data,$showscreen_data,$car_access_record,$village_info,$now_time,$parkTime,$car_info,$car_number_type);
            if ($res_temp1){
                //发送模板消息
                $openid=$this->getUserInfo($car_number,$passage_info['village_id']);
                if (!empty($openid)){
                    $this->remarkTxt      = '\n入场类型： 临时车';
                    $this->stored_balance = isset($car_info['stored_balance']) && $car_info['stored_balance'] ? $car_info['stored_balance'] : 0;
                    $this->sendCarMeassage($openid, $car_number, $car_info, $village_info, $garage_info1, $passage_info, 0, $village_info['property_id'], [],time(), false);
                }
                return $res_temp1;
            }
        }
        return false;
    }

    /**
     * 添加临时车入场信息
     * @author:zhubaodi
     * @date_time: 2022/11/24 9:20
     */
    public function add_temp_in_park($car_number,$passage_info,$park_data,$showscreen_data,$car_access_record,$village_info,$now_time,$parkTime,$car_info,$car_number_type){
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $db_in_park = new InPark();
        $starttime = time() - 30;
        $endtime = time() + 50;
        $park_where = [
            ['car_number', '=', $car_number],
            ['park_id', '=', $passage_info['village_id']],
            ['in_channel_id','=',$passage_info['id']],
            ['in_time', '>=', $starttime],
            ['in_time', '<=', $endtime],
            ['del_time', '=', 0],
        ];
        $park_info_car = $db_in_park->getOne1($park_where);

        if ($car_number) {
            $park_info_car_arr = $park_info_car && !is_array($park_info_car) ? $park_info_car->toArray() : $park_info_car;
            fdump_api(['查询入场1分钟间记录' => $car_number, 'park_where' => $park_where, 'park_info_car' => $park_info_car_arr, 'park_sys_type' => 'A11'], 'park_temp/log_' . $car_number, 1);
        } elseif ($parkTime) {
            fdump_api(['查询车辆==' . __LINE__ => $car_number, 'park_where' => $park_where, 'park_info_car' => $park_info_car], 'a11_park/plateresult' . $parkTime, true);
        }
        //查询设备所属车库属性
        $db_house_village_parking_garage=new HouseVillageParkingGarage();
        $garage_id=0;
        if (!empty($passage_info['passage_area'])&&$passage_info['area_type']==2){
            $garage_id=$passage_info['passage_area'];
        }
        if(isset($passage_info['garage_id']) && $passage_info['garage_id']>0){
            $garage_id=$passage_info['garage_id'];
        }
        $garage_info=array();
        $is_real_garage=1;
        if($garage_id>0){
            $garage_info=$db_house_village_parking_garage->getOne(['garage_id'=>$garage_id]);
            if($garage_info && !$garage_info->isEmpty()){
                $garage_info=$garage_info->toArray();
                if($garage_info['fid']>0 && $garage_info['fid']==$garage_info['garage_id']){
                    $garage_info['fid']=0;
                }

                if($garage_info['fid']>0 && $garage_info['fid']!=$garage_info['garage_id'] ){
                    $is_real_garage=0;
                }
            }
        }
        $imageToJob = isset($car_access_record['imageToJob']) ? $car_access_record['imageToJob'] : [];
        $car_access_record = unsetParam($car_access_record, 'imageToJob');
        //写入车辆入场表
        $insert_id=0;
        if (empty($park_info_car)) {
            $insert_id = $db_in_park->insertOne($park_data);
            if (!isset($car_info['parking_car_type'])||empty($car_info['parking_car_type'])){
                $db_house_village_park_charge_cartype=new HouseVillageParkChargeCartype();
                $car_types=$db_house_village_park_charge_cartype->getFind(['village_id'=>$passage_info['village_id'],'car_number_type'=>$car_number_type,'status'=>1,'park_sys_type'=>'A11'],'car_type');
                if (!empty($car_types)){
                    $car_info['parking_car_type']=$car_types['car_type'];
                }else{
                    $car_info['parking_car_type']=self::Temp_A;
                }
            }
            if (!empty($imageToJob)) {
                $imageToJob['in_park_id'] = $insert_id;
            }
            $car_access_record['business_type'] = 0;
            $car_access_record['business_id'] = $passage_info['village_id'];
            $car_access_record['car_number'] = $car_number;
            $car_access_record['accessType'] = 1;
            $car_access_record['accessTime'] = $now_time;
            $car_access_record['accessMode'] = 9;
            $car_access_record['park_sys_type'] = 'A11';
            $car_access_record['park_car_type'] = $this->parking_a11_car_type_arr[$car_info['parking_car_type']];

            $car_access_record['is_out'] = 0;
            $car_access_record['park_id'] = $passage_info['village_id'];
            $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
            $car_access_order_id = date('YmdHis') . rand(100, 999);
            $car_access_record['order_id'] = $car_access_order_id;
            $car_access_record['update_time'] = $now_time;
            if (isset($insert_id) && !empty($insert_id)) {
                $car_access_record['from_id'] = $insert_id;
            }
            $record_id = $db_house_village_car_access_record->addOne($car_access_record);
            //将此条之前的 都改掉
            if($record_id>0 && $is_real_garage>0) {
                /*
                $wherexArr = [];
                $wherexArr[]=['record_id','<',$record_id];
                $wherexArr[] = ['car_number', '=', $car_number];
                $wherexArr[] = ['is_out', '=', 0];
                $wherexArr[] = ['park_id', '=', $passage_info['village_id']];
                $db_house_village_car_access_record->saveOne($wherexArr, ['is_out' => 1, 'update_time' => $now_time]);
                */
                $tmpUpdateRecordArr = array('car_number' => $car_number, 'in_park_id' => $insert_id, 'village_id' => $passage_info['village_id'], 'record_id' => $record_id, 'access_order_id' => $car_access_order_id, 'access_type' => 1, 'access_time' => time());
                (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
            }
            if ($car_number) {
                fdump_api(['写入车辆' => $car_number, 'record_id' => $record_id, 'car_access_record' => $car_access_record, 'park_sys_type' => 'A11'], 'park_temp/log_' . $car_number, 1);
            } elseif ($parkTime) {
                fdump_api(['写入车辆=='.__LINE__=>$car_number,'car_access_record' => $car_access_record,'record_id' => $record_id],'a11_park/plateresult'.$parkTime,true);
            }
            if (!empty($imageToJob) && ($record_id || $insert_id)) {
                if (isset($car_access_order_id) && $car_access_order_id) {
                    $imageToJob['car_access_order_id'] = $car_access_order_id;
                }
                $imageToJob['in_record_id'] = $record_id;
                (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
            }
        }
        //TODO:屏显和语音提示特殊车辆通行
        $showscreen_data['content'] = '欢迎光临';
        $showscreen_data['voice_content'] = 1;
        if ($car_number) {
            fdump_api(['写入车辆' => $car_number, 'showscreen_data' => $showscreen_data,  'park_sys_type' => 'A11'], 'park_temp/log_' . $car_number, 1);
        } elseif ($parkTime) {
            fdump_api(['写入车辆=='.__LINE__=>$car_number,'showscreen_data' => $showscreen_data],'a11_park/plateresult'.$parkTime,true);
        }
        $this->addParkShowScreenLog($showscreen_data);
        //特殊车辆通行
        return true;
    }
    
    /**
     * 添加显屏指令
     * @author:zhubaodi
     * @date_time: 2022/4/6 17:45
     */
    public function addParkShowScreenLog($data)
    {
        $db_park_showscreen_log = new ParkShowscreenLog();
        /*----------添加显屏指令start-----------*/
        //临时车入场
        $temp_in_content = '欢迎光临';
        //临时车出场
        $temp_out_content1 = '停车';
        $temp_out_content2 = '请缴费';
        $temp_out_content3 = '一路顺风';
        //月租车入场
        $mouth_in_content = '欢迎光临';
        //月租车出场
        $mouth_out_content1 = '停车有效期';
        //黑名单禁止通行
        $black_content = '黑名单禁止通行';
        
        $is_log = false;
        $car_number = isset($data['car_number']) ? trim($data['car_number']) : '';
        $village_id = isset($data['village_id']) ? intval($data['village_id']) : 0;
        !$village_id && $village_id = isset($data['passage']['village_id']) ? intval($data['passage']['village_id']) : 0;
        $channel_id = isset($data['channel_id']) ? trim($data['channel_id']) : '';
        !$channel_id && $channel_id = isset($data['passage']['device_number']) ? trim($data['passage']['device_number']) : '';
//        if ($car_number == '川A43611') {
//            $is_log = true;
//        }
        if ($is_log) {
            fdump_api(['data' => $data], 'screen/addParkShowScreenLog', 1);
        }

        //车辆类型
        $carType = isset($data['car_type']) ? $data['car_type'] : '';
        //通道方向
        $passageDirection = isset($data['passage']['passage_direction']) ? intval($data['passage']['passage_direction']) : 0;
        //设备类型
        $deviceType = isset($data['passage']['device_type']) ? intval($data['passage']['device_type']) : 0;
        //设备类型 下发显屏指令类型（27 临显 25广告） 
        $orderType = 27;
        //通道类型
        if ((isset($data['passage']['passage_type']) && $data['passage']['passage_type'] == 2) && $passageDirection == 1) {
            $passageType = 2;
        } else {
            $passageType = 1;
        }
        $showContentArr = [];
        $data_voice_content = isset($data['voice_content']) && intval($data['voice_content']) > 0 ? intval($data['voice_content']) : 0;
        //todo 进出场 显屏
        $data_end_time = isset($data['end_time']) ? intval($data['end_time']) : 0;
        $data_end_time_text = isset($data['end_time_txt']) ? trim($data['end_time_txt']) : '';
        if ($data_end_time > 0 && !$data_end_time_text) {
            $data_end_time_text = date('Y-m-d');
        }
        $data_duration = isset($data['duration']) ? $data['duration'] : 0;
        $data_duration_txt = isset($data['duration_txt']) ? $data['duration_txt'] : '';
        $data_price = isset($data['price']) ? $data['price'] : 0;
        if (floatval($data_price) > 0) {
            $data_price = getFormatNumber($data_price);
        }

        $stored_balance = isset($data['stored_balance']) && floatval($data['stored_balance']) > 0 ? $data['stored_balance'] : 0;
        if ($stored_balance > 0) {
            $stored_balance = getFormatNumber($stored_balance);
        }
        $car_type = isset($data['car_type']) ? trim($data['car_type']) : '';
        switch ($carType) {
            case 'black_type':
                if ($passageDirection == 1) {
                    //todo 车辆入场
                    if ($is_log) {
                        fdump_api(['msg' => '黑名单-车辆入场'], 'screen/addParkShowScreenLog', 1);
                    }
                    if ($deviceType == 1) {
                        //todo 横屏
                        $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $black_content;
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number];
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content];
                        if ($is_log) {
                            fdump_api(['msg' => '显屏-入场横屏-黑名单 2-车牌 3-内容', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                        }
                    }
                    if ($deviceType == 2) {
                        //todo 竖屏
                        $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $black_content;
                        if (mb_strlen($content) > 6) {
                            $content = mb_substr($content, 0, 6);
                        }
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number];
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content];
                        if ($is_log) {
                            fdump_api(['msg' => '显屏-入场竖屏-黑名单 1-车牌 2-内容', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                        }
                    }
                }
                if ($passageDirection == 0) {
                    //todo 车辆出场
                    if ($is_log) {
                        fdump_api(['msg' => '黑名单-车辆出场'], 'screen/addParkShowScreenLog', 1);
                    }
                    if ($deviceType == 1) {
                        //todo 横屏
                        $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $black_content;
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number];
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content];
                        if ($is_log) {
                            fdump_api(['msg' => '显屏-出场横屏-黑名单 1-车牌 2-内容', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                        }
                    }
                    if ($deviceType == 2) {
                        //todo 竖屏
                        $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $black_content;
                        if (mb_strlen($content) > 6) {
                            $content = mb_substr($content, 0, 6);
                        }
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number];
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content];
                        if ($is_log) {
                            fdump_api(['msg' => '显屏-出场竖屏-黑名单 1-车牌 2-内容', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                        }
                    }
                }
                break;
            case 'month_type':
                if ($passageDirection == 1) {
                    //todo 车辆入场
                    if ($is_log) {
                        fdump_api(['msg' => '月租车-车辆入场'], 'screen/addParkShowScreenLog', 1);
                    }
                    if ($deviceType == 1) {
                        //todo 横屏
                        $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $mouth_in_content;
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number, 'end_time' => $data_end_time];
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content, 'end_time' => $data_end_time];
                        if ($is_log) {
                            fdump_api(['msg' => '显屏-入场横屏-月租车 2-车牌 3-内容', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                        }
                    }
                    if ($deviceType == 2) {
                        //todo 竖屏
                        $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $mouth_in_content;
                        if (mb_strlen($content) > 6) {
                            $content = mb_substr($content, 0, 6);
                        }
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number, 'end_time' => $data_end_time];
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content, 'end_time' => $data_end_time];
                        if ($is_log) {
                            fdump_api(['msg' => '显屏-入场竖屏-月租车 1-车牌 2-内容', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                        }
                    }
                }
                if ($passageDirection == 0) {
                    //todo 车辆出场
                    if ($is_log) {
                        fdump_api(['msg' => '月租车-车辆出场'], 'screen/addParkShowScreenLog', 1);
                    }
                    if ($deviceType == 1) {
                        //todo 横屏
                        $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $mouth_out_content1 . $data_end_time_text;
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number, 'end_time' => $data_end_time];
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content, 'end_time' => $data_end_time];
                        if ($is_log) {
                            fdump_api(['msg' => '显屏-出场横屏-月租车 1-车牌 2-内容', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                        }
                    }
                    if ($deviceType == 2) {
                        //todo 竖屏
                        $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $mouth_in_content . $data_end_time_text;
                        if (mb_strlen($content) > 6) {
                            $content = mb_substr($content, 0, 6);
                        }
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number, 'end_time' => $data_end_time];
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content, 'end_time' => $data_end_time];
                        if ($is_log) {
                            fdump_api(['msg' => '显屏-出场竖屏-月租车 1-车牌 2-内容', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                        }
                    }
                }
                break;
            case 'temporary_type':
            default:
                if ($passageDirection == 1) {
                    //todo 车辆入场
                    if ($is_log) {
                        fdump_api(['msg' => '车辆入场'], 'screen/addParkShowScreenLog', 1);
                    }
                    if ($deviceType == 1) {
                        //todo 横屏
                        $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $temp_in_content;
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number];
                        if ($car_type == 'storedCar') {
                            $content .= ",余额{$stored_balance}元";
                        }
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content];
                        if ($is_log) {
                            fdump_api(['msg' => '显屏-入场横屏 1-车牌 2-内容', 'car_number' => $car_number, 'content' => $content], 'screen/addParkShowScreenLog', 1);
                        }
                    }
                    if ($deviceType == 2) {
                        //todo 竖屏
                        $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $temp_in_content;
                        if (mb_strlen($content) > 6) {
                            $content = mb_substr($content, 0, 6);
                        }
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number];
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content];
                        if ($is_log) {
                            fdump_api(['msg' => '显屏-入场竖屏 1-车牌 2-内容', 'car_number' => $car_number, 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                        }
                    }
                }
                if ($passageDirection == 0) {
                    //todo 车辆出场
                    if ($is_log) {
                        fdump_api(['msg' => '车辆出场'], 'screen/addParkShowScreenLog', 1);
                    }
                    if ($deviceType == 1) {
                        //todo 横屏
                        if (empty($data_price)) {
                            $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $temp_out_content3;
                            $showContentArr[] = [
                                'content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number, 'duration' => $data_duration, 'price' => 0,
                            ];
                            if ($car_type == 'storedCar') {
                                $data_duration .= ",余额{$stored_balance}元";
                            }
                            $showContentArr[] = [
                                'content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content, 'duration' => $data_duration, 'price' => 0
                            ];
                            if ($is_log) {
                                fdump_api(['msg' => '显屏-出场横屏（无金额） 1-车牌 2-内容', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                            }
                        } else {
                            if (empty($data['content'])) {
                                if ($data_voice_content == 9) {
                                    $content = '扣款' . $data_price . '元';
                                } else {
                                    $content = $temp_out_content2 . $data_price . '元';
                                }
                            } else {
                                $content = $data['content'];
                            }
                            $data_content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $temp_out_content1 . $data_duration_txt;
                            $showContentArr[] = [
                                'content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number, 'duration' => $data_duration, 'price' => $data_price,
                            ];
                            // 将金额+内容展示在第2行
                            $text = $content;
                            if ($car_type == 'storedCar') {
                                $text .= ",余额{$stored_balance}元";
                            }
                            $text .= ",".$data_content;
                            $showContentArr[] = [
                                'content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $text, 'duration' => $data_duration, 'price' => $data_price
                            ];
//                            $showContentArr[] = [
//                                'content_type' => 1, 'screen_row' => 3, 'serial' => '03', 'content' => $content, 'duration' => $data_duration, 'price' => $data_price
//                            ];
                            if ($is_log) {
                                fdump_api(['msg' => '显屏-出场横屏（有金额） 1-车牌 2-金额内容+内容', 'data_price' => $data_price, 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                            }
                        }
                    }
                    if ($deviceType == 2) {
                        //todo 竖屏
                        if (empty($data_price)) {
                            $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $temp_out_content3;
                            if (mb_strlen($content) > 6) {
                                $content = mb_substr($content, 0, 6);
                            }
                            $showContentArr[] = [
                                'content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number, 'duration' => $data_duration, 'price' => 0,
                            ];
                            $showContentArr[] = [
                                'content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content, 'duration' => $data_duration, 'price' => 0
                            ];
                            if ($is_log) {
                                fdump_api(['msg' => '显屏-出场竖屏（无金额） 2-车牌 3-内容', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                            }
                        } else {
                            $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $temp_out_content1 . $data_duration_txt;
                            if (mb_strlen($content) > 6) {
                                $content = mb_substr($content, 0, 6);
                            }
                            $content2 = isset($data['content']) && trim($data['content']) ? trim($data['content']) : '';
                            if (empty($content2)) {
                                if ($data_voice_content == 9) {
                                    $content2 = '扣款' . $data['price'] . '元,请通行';
                                } else {
                                    $content2 = $data['price'] . '元';
                                }
                            }
                            if (mb_strlen($content2) > 6) {
                                $content2 = mb_substr($content2, 0, 6);
                            }
                            $showContentArr[] = [
                                'content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number, 'duration' => $data_duration, 'price' => $data_price,
                            ];
                            // 将金额+内容展示在第2行
                            $text = $content2 . $content;
                            $showContentArr[] = [
                                'content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $text, 'duration' => $data_duration, 'price' => $data_price
                            ];
//                            $showContentArr[] = [
//                                'content_type' => 1, 'screen_row' => 3, 'serial' => '03', 'content' => $content2, 'duration' => $data_duration, 'price' => $data['price']
//                            ];
                            if ($is_log) {
                                fdump_api(['msg' => '显屏-出场竖屏（有金额） 1-车牌 2-金额+内容', 'data_price' => $data_price, 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                            }
                        }
                    }
                }
                break;
        }
        //todo 进场 语音
        if ($passageDirection == 1) {
            $voice_content = $data_voice_content ? $data_voice_content : 0;
            $showContentArr[] = [
                'content_type' => 2, 'screen_row' => 2, 'serial' => '01', 'car_number' => $car_number, 'content' => $voice_content,
                'end_time' => $data_end_time, 'duration' => $data_duration, 'price' => $data_price
            ];
            if ($is_log) {
                fdump_api(['msg' => '进场-语音', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
            }
        }
        //todo 出场 语音
        if ($passageDirection == 0) {
            switch ($carType) {
                case 'temporary_type':
                    if (!empty($data_duration)) {
                        $voice_content = $data_voice_content ? $data_voice_content : 5;
                        $content = $voice_content;
                    } else {
                        $voice_content = $data_voice_content ? $data_voice_content : 2;
                        $content = $voice_content;
                    }
                    $showContentArr[] = [
                        'content_type' => 2, 'screen_row' => 2, 'serial' => '01', 'car_number' => $car_number,
                        'content' => $content, 'end_time' => $data_end_time, 'duration' => $data_duration, 'price' => $data_price
                    ];
                    if ($is_log) {
                        fdump_api(['msg' => '临时车-出场-语音', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                    }
                    break;
                case 'black_type':
                    $voice_content = $data_voice_content ? $data_voice_content : 5;
                    $showContentArr[] = [
                        'content_type' => 2, 'screen_row' => 2, 'serial' => '01', 'car_number' => $car_number,
                        'content' => $voice_content, 'end_time' => $data_end_time, 'duration' => $data_duration, 'price' => $data_price
                    ];
                    if ($is_log) {
                        fdump_api(['msg' => '黑名单-出场-语音', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                    }
                    break;
                case 'month_type':
                    if ($data_end_time > 1) {
                        if (!$data_voice_content) {
                            $content = 4;
                        } else {
                            $content = $data_voice_content;
                        }
                    } else {
                        if (!$data_voice_content) {
                            $content = 2;
                        } else {
                            $content = $data_voice_content;
                        }
                    }
                    $showContentArr[] = [
                        'content_type' => 2, 'screen_row' => 2, 'serial' => '01', 'car_number' => $car_number,
                        'content' => $content, 'end_time' => $data_end_time, 'duration' => $data_duration, 'price' => $data_price
                    ];
                    if ($is_log) {
                        fdump_api(['msg' => '月租车-出场-语音', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                    }
                    break;
                default:
                    if (!$data_voice_content) {
                        $content = 2;
                    } else {
                        $content = $data_voice_content;
                    }
                    $showContentArr[] = [
                        'content_type' => 2, 'screen_row' => 2, 'serial' => '01', 'car_number' => $car_number,
                        'content' => $content, 'end_time' => $data_end_time, 'duration' => $data_duration, 'price' => $data_price
                    ];
                    if ($is_log) {
                        fdump_api(['msg' => '默认类型-出场-语音', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                    }
                    break;
            }
        }

        $parkShowScreen = new D3ShowScreenService();
        $serialData = [];
        if ($showContentArr) {
            foreach ($showContentArr as $showKey => $item) {
                if ($item['content_type'] == 1) { //显屏
                    $showVoiceTxt = $parkShowScreen->showVoiceTxt($item['content'], $item['screen_row'], $item['serial'], $orderType, $passageType);
                } else { //语音
                    $showVoiceTxt = $parkShowScreen->showVoice($item, '01', $passageType);
                }
                if ($is_log) {
                    fdump_api(['msg' => '显屏语音解析', 'item' => $item, 'showVoiceTxt' => $showVoiceTxt], 'screen/addParkShowScreenLog', 1);
                }
//                $base64DataText = $parkShowScreen->translation($showVoiceTxt);
                $base64DataText = $parkShowScreen->handleBase64Bit16($showVoiceTxt);
                $base64DataTextLength = strlen($base64DataText);
                $serialData[] = [
                    'serialChannel' => 0,
                    'data' => $base64DataText,
                    'dataLen' => $base64DataTextLength,
                ];
            }
            if ($is_log) {
                fdump_api(['msg' => '处理好的相关信息', 'showContentArr' => $showContentArr, 'serialData' => $serialData], 'screen/addParkShowScreenLog', 1);
            }
        }
        $showscreen_log = [
            'village_id' => $village_id, 'park_sys_type' => 'A11', 'content' => $car_number, 'content_type' => 1, 'screen_row' => 1, 'serial' => '01',
            'car_number' => $car_number, 'channel_id' => $channel_id, 'park_type' => 1, 'add_time' => time(),
            'showcontent' => json_encode($serialData, JSON_UNESCAPED_UNICODE)
        ];
        if ($is_log) {
            fdump_api(['msg' => '处理好的相关信息', 'showscreen_log' => $showscreen_log], 'screen/addParkShowScreenLog', 1);
        }
        $logId = $db_park_showscreen_log->add($showscreen_log);
        if ($is_log) {
            fdump_api(['msg' => '处理好的相关信息', 'logId' => $logId], 'screen/addParkShowScreenLog', 1);
        }
        fdump_api(['参数进来了==' . __LINE__, [
            '$passageType' => $passageType,
            'car_number' => $data['car_number'],
            '$showContentArr' => $showContentArr,
            '$logId' => $logId,
            '$showscreen_log' => $showscreen_log
        ]], 'park/A11/addShowScreenLog', 1);
        return $logId;


        //车辆入场
        if ($data['passage']['passage_direction'] == 1) {
            //横屏
            if ($data['passage']['device_type'] == 1) {
                if ($data['car_type'] == 'temporary_type') {
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    $log_data['content'] = $data['car_number'];
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 1;
                    $log_data['serial'] = '01';
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 1;
                    $log_data['add_time'] = time();
                    $db_park_showscreen_log->add($log_data);
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    if (empty($data['content'])) {
                        $log_data['content'] = $temp_in_content;
                    } else {
                        $log_data['content'] = $data['content'];
                    }
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 2;
                    $log_data['serial'] = '02';
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 1;
                    $log_data['add_time'] = time();
                    // fdump_api([$log_data,$data],'showscreen_log_1',1);
                    $db_park_showscreen_log->add($log_data);
                } elseif ($data['car_type'] == 'black_type') {
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    $log_data['content'] = $data['car_number'];
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 1;
                    $log_data['serial'] = '01';
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 1;
                    $log_data['add_time'] = time();
                    $db_park_showscreen_log->add($log_data);
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    if (empty($data['content'])) {
                        $log_data['content'] = $black_content;
                    } else {
                        $log_data['content'] = $data['content'];
                    }
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 1;
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 2;
                    $log_data['serial'] = '02';
                    $log_data['add_time'] = time();
                    $db_park_showscreen_log->add($log_data);
                } elseif ($data['car_type'] == 'month_type') {
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    if (empty($data['content'])) {
                        $log_data['content'] = $mouth_in_content;
                    } else {
                        $log_data['content'] = $data['content'];
                    }
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['end_time'] = $data['end_time'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 1;
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 2;
                    $log_data['serial'] = '02';
                    $log_data['add_time'] = time();
                    //  fdump_api([$log_data,$data],'showscreen_log_1',1);
                    $db_park_showscreen_log->add($log_data);
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    $log_data['content'] = $data['car_number'];
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['end_time'] = $data['end_time'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 1;
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 1;
                    $log_data['serial'] = '01';
                    $log_data['add_time'] = time();

                    //  fdump_api([$log_data,$data],'showscreen_log_1',1);

                    $db_park_showscreen_log->add($log_data);
                } else {
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    if (empty($data['content'])) {
                        $log_data['content'] = $mouth_in_content;
                    } else {
                        $log_data['content'] = $data['content'];
                    }
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 1;
                    $log_data['add_time'] = time();
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 2;
                    $log_data['serial'] = '02';
                    //  fdump_api([$log_data,$data],'showscreen_log_1',1);

                    $db_park_showscreen_log->add($log_data);
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    $log_data['content'] = $data['car_number'];
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 1;
                    $log_data['add_time'] = time();
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 1;
                    $log_data['serial'] = '01';

                    //   fdump_api([$log_data,$data],'showscreen_log_1',1);

                    $db_park_showscreen_log->add($log_data);
                }
            } //竖屏
            elseif ($data['passage']['device_type'] == 2) {
                if ($data['car_type'] == 'temporary_type') {
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    $log_data['content'] = $data['car_number'];
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 2;
                    $log_data['serial'] = '01';
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 1;
                    $log_data['add_time'] = time();
                    // fdump_api([$log_data,$data],'showscreen_log_1',1);

                    $db_park_showscreen_log->add($log_data);
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    if (empty($data['content'])) {
                        $log_data['content'] = $temp_in_content;
                    } else {
                        if (mb_strlen($data['content']) > 6) {
                            $log_data['content'] = mb_substr($data['content'], 0, 6);
                        } else {
                            $log_data['content'] = $data['content'];
                        }
                    }
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 3;
                    $log_data['serial'] = '02';
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 1;
                    $log_data['add_time'] = time();

                    // fdump_api([$log_data,$data],'showscreen_log_1',1);

                    $db_park_showscreen_log->add($log_data);
                } elseif ($data['car_type'] == 'black_type') {
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    $log_data['content'] = $data['car_number'];
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 2;
                    $log_data['serial'] = '01';
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 1;
                    $log_data['add_time'] = time();
                    $db_park_showscreen_log->add($log_data);
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    if (empty($data['content'])) {
                        $log_data['content'] = $black_content;
                    } else {
                        if (mb_strlen($data['content']) > 6) {
                            $log_data['content'] = mb_substr($data['content'], 0, 6);
                        } else {
                            $log_data['content'] = $data['content'];
                        }
                    }
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 1;
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 3;
                    $log_data['serial'] = '02';
                    $log_data['add_time'] = time();
                    $db_park_showscreen_log->add($log_data);
                } elseif ($data['car_type'] == 'month_type') {
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    if (empty($data['content'])) {
                        $log_data['content'] = $mouth_in_content;
                    } else {
                        if (mb_strlen($data['content']) > 6) {
                            $log_data['content'] = mb_substr($data['content'], 0, 6);
                        } else {
                            $log_data['content'] = $data['content'];
                        }

                    }
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['end_time'] = $data['end_time'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 1;
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 3;
                    $log_data['serial'] = '02';
                    $log_data['add_time'] = time();

                    // fdump_api([$log_data,$data],'showscreen_log_1',1);

                    $db_park_showscreen_log->add($log_data);
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    $log_data['content'] = $data['car_number'];
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['end_time'] = $data['end_time'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 1;
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 2;
                    $log_data['serial'] = '01';
                    $log_data['add_time'] = time();

                    //  fdump_api([$log_data,$data],'showscreen_log_1',1);

                    $db_park_showscreen_log->add($log_data);
                } else {
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    if (empty($data['content'])) {
                        $log_data['content'] = $mouth_in_content;
                    } else {
                        if (mb_strlen($data['content']) > 6) {
                            $log_data['content'] = mb_substr($data['content'], 0, 6);
                        } else {
                            $log_data['content'] = $data['content'];
                        }
                    }
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 1;
                    $log_data['add_time'] = time();
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 3;
                    $log_data['serial'] = '02';

                    //  fdump_api([$log_data,$data],'showscreen_log_1',1);

                    $db_park_showscreen_log->add($log_data);
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    $log_data['content'] = $data['car_number'];
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 1;
                    $log_data['add_time'] = time();
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 2;
                    $log_data['serial'] = '01';

                    //  fdump_api([$log_data,$data],'showscreen_log_1',1);

                    $db_park_showscreen_log->add($log_data);
                }
            }

        }
        //车辆出场
        if ($data['passage']['passage_direction'] == 0) {
            //横屏
            if ($data['passage']['device_type'] == 1) {
                if ($data['car_type'] == 'temporary_type') {
                    if (!isset($data['price']) || empty($data['price'])) {
                        $log_data = [];
                        $log_data['village_id'] = $data['village_id'];
                        $log_data['park_sys_type'] = 'A11';
                        $log_data['content'] = $data['car_number'];
                        $log_data['content_type'] = 1;
                        $log_data['screen_row'] = 1;
                        $log_data['serial'] = '01';
                        $log_data['car_number'] = $data['car_number'];
                        $log_data['channel_id'] = $data['channel_id'];
                        $log_data['park_type'] = 2;
                        $log_data['duration'] = $data['duration'];
                        $log_data['price'] = 0;
                        $log_data['add_time'] = time();

                        fdump_api([$log_data, $data], 'showscreen_log_2', 1);

                        $db_park_showscreen_log->add($log_data);
                        $log_data = [];
                        $log_data['village_id'] = $data['village_id'];
                        $log_data['park_sys_type'] = 'A11';
                        if (empty($data['content'])) {
                            $log_data['content'] = $temp_out_content3;
                        } else {
                            $log_data['content'] = $data['content'];
                        }
                        $log_data['content_type'] = 1;
                        $log_data['screen_row'] = 2;
                        $log_data['serial'] = '02';
                        $log_data['car_number'] = $data['car_number'];
                        $log_data['channel_id'] = $data['channel_id'];
                        $log_data['park_type'] = 2;
                        $log_data['add_time'] = time();
                        $log_data['duration'] = $data['duration'];
                        $log_data['price'] = 0;

                        fdump_api([$log_data, $data], 'showscreen_log_2', 1);

                        $db_park_showscreen_log->add($log_data);
                    } else {
                        $log_data['content'] = $data['car_number'];
                        $log_data['content_type'] = 1;
                        $log_data['screen_row'] = 1;
                        $log_data['serial'] = '01';
                        $log_data['car_number'] = $data['car_number'];
                        $log_data['duration'] = $data['duration'];
                        $log_data['price'] = $data['price'];
                        $log_data['channel_id'] = $data['channel_id'];
                        $log_data['park_type'] = 2;
                        $log_data['add_time'] = time();

                        fdump_api([$log_data, $data], 'showscreen_log_stored_0907', 1);

                        $db_park_showscreen_log->add($log_data);
                        if (empty($data['content'])) {
                            $log_data['content'] = $temp_out_content1 . $data['duration_txt'];
                        } else {
                            $log_data['content'] = $data['content'];
                        }
                        $log_data['content_type'] = 1;
                        $log_data['screen_row'] = 2;
                        $log_data['serial'] = '02';
                        $log_data['car_number'] = $data['car_number'];
                        $log_data['duration'] = $data['duration'];
                        $log_data['price'] = $data['price'];
                        $log_data['channel_id'] = $data['channel_id'];
                        $log_data['park_type'] = 2;
                        $log_data['add_time'] = time();

                        fdump_api([$log_data, $data], 'showscreen_log_stored_0907', 1);
                        $db_park_showscreen_log->add($log_data);
                        $log_data = [];
                        $log_data['village_id'] = $data['village_id'];
                        $log_data['park_sys_type'] = 'A11';
                        if (empty($data['content'])) {
                            if (isset($data['voice_content']) && $data['voice_content'] == 9) {
                                $log_data['content'] = '扣款' . $data['price'] . '元,请通行';
                            } else {
                                $log_data['content'] = $temp_out_content2 . $data['price'] . '元';
                            }

                        } else {
                            $log_data['content'] = $data['content'];
                        }
                        $log_data['content_type'] = 1;
                        $log_data['screen_row'] = 1;
                        $log_data['serial'] = '03';
                        $log_data['car_number'] = $data['car_number'];
                        $log_data['duration'] = $data['duration'];
                        $log_data['price'] = $data['price'];
                        $log_data['channel_id'] = $data['channel_id'];
                        $log_data['park_type'] = 2;
                        $log_data['add_time'] = time();

                        fdump_api([$log_data, $data], 'showscreen_log_stored_0907', 1);

                        $db_park_showscreen_log->add($log_data);
                    }
                } elseif ($data['car_type'] == 'black_type') {
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    $log_data['content'] = $data['car_number'];
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 1;
                    $log_data['serial'] = '01';
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 2;
                    $log_data['add_time'] = time();
                    $db_park_showscreen_log->add($log_data);
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    if (empty($data['content'])) {
                        $log_data['content'] = $black_content;
                    } else {
                        $log_data['content'] = $data['content'];
                    }
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 2;
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 2;
                    $log_data['serial'] = '02';
                    $log_data['add_time'] = time();
                    $db_park_showscreen_log->add($log_data);
                } elseif ($data['car_type'] == 'month_type') {
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    if (empty($data['content'])) {
                        $log_data['content'] = $mouth_out_content1 . $data['end_time_txt'];
                    } else {
                        $log_data['content'] = $data['content'];
                    }
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['end_time'] = $data['end_time'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 2;
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 2;
                    $log_data['serial'] = '02';
                    $log_data['add_time'] = time();

                    fdump_api([$log_data, $data], 'showscreen_log_2', 1);

                    $db_park_showscreen_log->add($log_data);
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    $log_data['content'] = $data['car_number'];
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['end_time'] = $data['end_time'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 2;
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 1;
                    $log_data['serial'] = '01';
                    $log_data['add_time'] = time();

                    fdump_api([$log_data, $data], 'showscreen_log_2', 1);

                    $db_park_showscreen_log->add($log_data);
                } else {
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    if (empty($data['content'])) {
                        $log_data['content'] = '一路顺风';
                    } else {
                        $log_data['content'] = $data['content'];
                    }
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 2;
                    $log_data['add_time'] = time();
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 2;
                    $log_data['serial'] = '02';

                    fdump_api([$log_data, $data], 'showscreen_log_2', 1);

                    $db_park_showscreen_log->add($log_data);
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    $log_data['content'] = $data['car_number'];
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 2;
                    $log_data['add_time'] = time();
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 1;
                    $log_data['serial'] = '01';

                    fdump_api([$log_data, $data], 'showscreen_log_2', 1);

                    $db_park_showscreen_log->add($log_data);
                }
            } //竖屏
            elseif ($data['passage']['device_type'] == 2) {
                if ($data['car_type'] == 'temporary_type') {
                    if (empty($data['price'])) {
                        $log_data = [];
                        $log_data['village_id'] = $data['village_id'];
                        $log_data['park_sys_type'] = 'A11';
                        $log_data['content'] = $data['car_number'];
                        $log_data['content_type'] = 1;
                        $log_data['screen_row'] = 2;
                        $log_data['serial'] = '01';
                        $log_data['car_number'] = $data['car_number'];
                        $log_data['channel_id'] = $data['channel_id'];
                        $log_data['park_type'] = 2;
                        $log_data['duration'] = $data['duration'];
                        $log_data['price'] = 0;
                        $log_data['add_time'] = time();

                        fdump_api([$log_data, $data], 'showscreen_log_2', 1);

                        $db_park_showscreen_log->add($log_data);
                        $log_data = [];
                        $log_data['village_id'] = $data['village_id'];
                        $log_data['park_sys_type'] = 'A11';
                        if (empty($data['content'])) {
                            $log_data['content'] = $temp_out_content3;
                        } else {
                            if (mb_strlen($data['content']) > 6) {
                                $log_data['content'] = mb_substr($data['content'], 0, 6);
                            } else {
                                $log_data['content'] = $data['content'];
                            }
                        }
                        $log_data['content_type'] = 1;
                        $log_data['screen_row'] = 3;
                        $log_data['serial'] = '02';
                        $log_data['car_number'] = $data['car_number'];
                        $log_data['channel_id'] = $data['channel_id'];
                        $log_data['park_type'] = 2;
                        $log_data['add_time'] = time();
                        $log_data['duration'] = $data['duration'];
                        $log_data['price'] = 0;

                        fdump_api([$log_data, $data], 'showscreen_log_2', 1);

                        $db_park_showscreen_log->add($log_data);
                    } else {
                        $log_data['content'] = $data['car_number'];
                        $log_data['content_type'] = 1;
                        $log_data['screen_row'] = 2;
                        $log_data['serial'] = '01';
                        $log_data['car_number'] = $data['car_number'];
                        $log_data['duration'] = $data['duration'];
                        $log_data['price'] = $data['price'];
                        $log_data['channel_id'] = $data['channel_id'];
                        $log_data['park_type'] = 2;
                        $log_data['add_time'] = time();

                        fdump_api([$log_data, $data], 'showscreen_log_stored_0907', 1);

                        $db_park_showscreen_log->add($log_data);
                        if (empty($data['content'])) {
                            $log_data['content'] = $temp_out_content1 . $data['duration_txt'];
                        } else {
                            if (mb_strlen($data['content']) > 6) {
                                $log_data['content'] = mb_substr($data['content'], 0, 6);
                            } else {
                                $log_data['content'] = $data['content'];
                            }
                        }
                        $log_data['content_type'] = 1;
                        $log_data['screen_row'] = 3;
                        $log_data['serial'] = '02';
                        $log_data['car_number'] = $data['car_number'];
                        $log_data['duration'] = $data['duration'];
                        $log_data['price'] = $data['price'];
                        $log_data['channel_id'] = $data['channel_id'];
                        $log_data['park_type'] = 2;
                        $log_data['add_time'] = time();

                        fdump_api([$log_data, $data], 'showscreen_log_stored_0907', 1);

                        $db_park_showscreen_log->add($log_data);
                        $log_data = [];
                        $log_data['village_id'] = $data['village_id'];
                        $log_data['park_sys_type'] = 'A11';
                        if (empty($data['content'])) {
                            if (isset($data['voice_content']) && $data['voice_content'] == 9) {
                                $log_data['content'] = '扣款' . $data['price'] . '元,请通行';
                                if (mb_strlen($log_data['content']) > 6) {
                                    $log_data['content'] = mb_substr($log_data['content'], 0, 6);
                                }
                            } else {
                                $log_data['content'] = $temp_out_content2 . $data['price'] . '元';
                            }
                        } else {
                            if (mb_strlen($data['content']) > 6) {
                                $log_data['content'] = mb_substr($data['content'], 0, 6);
                            } else {
                                $log_data['content'] = $data['content'];
                            }
                        }
                        $log_data['content_type'] = 1;
                        $log_data['screen_row'] = 2;
                        $log_data['serial'] = '03';
                        $log_data['car_number'] = $data['car_number'];
                        $log_data['duration'] = $data['duration'];
                        $log_data['price'] = $data['price'];
                        $log_data['channel_id'] = $data['channel_id'];
                        $log_data['park_type'] = 2;
                        $log_data['add_time'] = time();

                        fdump_api([$log_data, $data], 'showscreen_log_stored_0907', 1);

                        $db_park_showscreen_log->add($log_data);
                    }
                } elseif ($data['car_type'] == 'black_type') {
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    $log_data['content'] = $data['car_number'];
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 2;
                    $log_data['serial'] = '01';
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 2;
                    $log_data['add_time'] = time();
                    $db_park_showscreen_log->add($log_data);
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    if (empty($data['content'])) {
                        $log_data['content'] = $black_content;
                    } else {
                        if (mb_strlen($data['content']) > 6) {
                            $log_data['content'] = mb_substr($data['content'], 0, 6);
                        } else {
                            $log_data['content'] = $data['content'];
                        }
                    }
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 2;
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 3;
                    $log_data['serial'] = '02';
                    $log_data['add_time'] = time();
                    $db_park_showscreen_log->add($log_data);
                } elseif ($data['car_type'] == 'month_type') {
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    if (empty($data['content'])) {
                        $log_data['content'] = $mouth_out_content1 . $data['end_time_txt'];
                    } else {
                        if (mb_strlen($data['content']) > 6) {
                            $log_data['content'] = mb_substr($data['content'], 0, 6);
                        } else {
                            $log_data['content'] = $data['content'];
                        }
                    }
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['end_time'] = $data['end_time'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 2;
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 3;
                    $log_data['serial'] = '02';
                    $log_data['add_time'] = time();

                    fdump_api([$log_data, $data], 'showscreen_log_2', 1);

                    $db_park_showscreen_log->add($log_data);
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    $log_data['content'] = $data['car_number'];
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['end_time'] = $data['end_time'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 2;
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 2;
                    $log_data['serial'] = '01';
                    $log_data['add_time'] = time();

                    fdump_api([$log_data, $data], 'showscreen_log_2', 1);

                    $db_park_showscreen_log->add($log_data);
                } else {
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    if (empty($data['content'])) {
                        $log_data['content'] = '一路顺风';
                    } else {
                        if (mb_strlen($data['content']) > 6) {
                            $log_data['content'] = mb_substr($data['content'], 0, 6);
                        } else {
                            $log_data['content'] = $data['content'];
                        }
                    }
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 2;
                    $log_data['add_time'] = time();
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 3;
                    $log_data['serial'] = '02';

                    fdump_api([$log_data, $data], 'showscreen_log_2', 1);

                    $db_park_showscreen_log->add($log_data);
                    $log_data = [];
                    $log_data['village_id'] = $data['village_id'];
                    $log_data['park_sys_type'] = 'A11';
                    $log_data['content'] = $data['car_number'];
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 2;
                    $log_data['add_time'] = time();
                    $log_data['content_type'] = 1;
                    $log_data['screen_row'] = 2;
                    $log_data['serial'] = '01';

                    fdump_api([$log_data, $data], 'showscreen_log_2', 1);

                    $db_park_showscreen_log->add($log_data);
                }
            }
        }
        /*----------添加显屏指令end-----------*/

        /*----------添加语音指令start-----------*/
        // $config_info=$db_house_village_park_showScreen_config->getFind(['village_id'=>$data['village_id'],'screen_type'=>2,'passage_id'=>$data['passage']['id']]);
        fdump_api([$data], 'showVoice_log_1', 1);
        //入场
        if ($data['passage']['passage_direction'] == 1) {
            $log_data = [];
            if (!isset($data['voice_content']) || empty($data['voice_content'])) {
                $log_data['content'] = 1;
            } else {
                $log_data['content'] = $data['voice_content'];
            }
            $log_data['park_sys_type'] = 'A11';
            $log_data['content_type'] = 2;
            $log_data['screen_row'] = 2;
            $log_data['serial'] = '01';
            $log_data['car_number'] = $data['car_number'];
            $log_data['channel_id'] = $data['channel_id'];
            $log_data['park_type'] = 1;
            $log_data['add_time'] = time();
            fdump_api([$log_data, $data], 'showVoice_log_1', 1);
            $db_park_showscreen_log->add($log_data);
        }
        //出场
        if ($data['passage']['passage_direction'] == 0) {
            $log_data['park_sys_type'] = 'A11';
            if ($data['car_type'] == 'temporary_type') {
                if (!empty($data['duration'])) {
                    if (!isset($data['voice_content']) || empty($data['voice_content'])) {
                        $log_data['content'] = 5;
                    } else {
                        $log_data['content'] = $data['voice_content'];
                    }
                    $log_data['content_type'] = 2;
                    $log_data['screen_row'] = 2;
                    $log_data['serial'] = '01';
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['duration'] = $data['duration'];
                    $log_data['price'] = $data['price'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 2;
                    $log_data['add_time'] = time();
                } else {
                    if (!isset($data['voice_content']) || empty($data['voice_content'])) {
                        $log_data['content'] = 2;
                    } else {
                        $log_data['content'] = $data['voice_content'];
                    }
                    $log_data['content_type'] = 2;
                    $log_data['screen_row'] = 2;
                    $log_data['serial'] = '01';
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['duration'] = $data['duration'];
                    $log_data['price'] = $data['price'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 2;
                    $log_data['add_time'] = time();
                }
                fdump_api([$log_data, $data], 'showVoice_log_1', 1);

                $db_park_showscreen_log->add($log_data);
            } elseif ($data['car_type'] == 'black_type') {
                if (!isset($data['voice_content']) || empty($data['voice_content'])) {
                    $log_data['content'] = 10;
                } else {
                    $log_data['content'] = $data['voice_content'];
                }
                $log_data['content_type'] = 2;
                $log_data['screen_row'] = 2;
                $log_data['serial'] = '01';
                $log_data['car_number'] = $data['car_number'];
                $log_data['channel_id'] = $data['channel_id'];
                $log_data['park_type'] = 2;
                $log_data['add_time'] = time();
                fdump_api([$log_data, $data], 'showVoice_log_1', 1);
                $db_park_showscreen_log->add($log_data);
            } elseif ($data['car_type'] == 'month_type') {
                if ($data['end_time'] > 1) {
                    if (!isset($data['voice_content']) || empty($data['voice_content'])) {
                        $log_data['content'] = 4;
                    } else {
                        $log_data['content'] = $data['voice_content'];
                    }
                    $log_data['content_type'] = 2;
                    $log_data['screen_row'] = 2;
                    $log_data['serial'] = '01';
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['end_time'] = $data['end_time'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 2;
                    $log_data['add_time'] = time();
                } else {
                    if (!isset($data['voice_content']) || empty($data['voice_content'])) {
                        $log_data['content'] = 2;
                    } else {
                        $log_data['content'] = $data['voice_content'];
                    }
                    $log_data['content_type'] = 2;
                    $log_data['screen_row'] = 2;
                    $log_data['serial'] = '01';
                    $log_data['car_number'] = $data['car_number'];
                    $log_data['duration'] = $data['duration'];
                    $log_data['price'] = $data['price'];
                    $log_data['channel_id'] = $data['channel_id'];
                    $log_data['park_type'] = 2;
                    $log_data['add_time'] = time();
                }
                fdump_api([$log_data, $data], 'showVoice_log_1', 1);

                $db_park_showscreen_log->add($log_data);
            } else {
                if (!isset($data['voice_content']) || empty($data['voice_content'])) {
                    $log_data['content'] = 2;
                } else {
                    $log_data['content'] = $data['voice_content'];
                }
                $log_data['content_type'] = 2;
                $log_data['screen_row'] = 2;
                $log_data['serial'] = '01';
                $log_data['car_number'] = $data['car_number'];
                $log_data['duration'] = $data['duration'];
                $log_data['price'] = $data['price'];
                $log_data['channel_id'] = $data['channel_id'];
                $log_data['park_type'] = 2;
                $log_data['add_time'] = time();
                fdump_api([$log_data, $data], 'showVoice_log_1', 1);
                $db_park_showscreen_log->add($log_data);
            }
        }


        /*----------添加语音指令end-----------*/
    }

    protected $remarkTxt = '';
    protected $stored_balance = 0;
    protected $pay_price = 0;
    /**
     * 整合下发模板消息
     * @param $openid
     * @param $car_number
     * @param $car_info
     * @param $village_info
     * @param $garage_info
     * @param $passage_info
     * @param $property_id
     * @param $small_garage_info
     * @param $now_time
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sendCarMeassage($openid, $car_number, $car_info, $village_info, $garage_info, $passage_info, $time = '', $property_id = 0, $small_garage_info = [], $now_time = 0, $is_samll = true) {
        $db_house_village_parking_garage=new HouseVillageParkingGarage();
        if (!$now_time) {
            $now_time = time();
        }
        $park_name = $village_info['village_name'];
        if ($garage_info && isset($garage_info['garage_num']) && $garage_info['garage_num']) {
            $park_name .= $garage_info['garage_num'] . '（车场）';
        }
        $remark = '';
        if ($time) {
            $remark .= '停留时长：'.$time;
        }
        if ($is_samll) {
            $park_month_name = isset($small_garage_info['garage_num']) && $small_garage_info['garage_num'] ? $small_garage_info['garage_num'].'（车场）' : '';
            if (!$park_month_name && isset($car_info['garage_id']) && $car_info['garage_id']) {
                $park_month_garage_info = $db_house_village_parking_garage->getOne(['garage_id'=>$car_info['garage_id']]);
                $park_month_name = isset($park_month_garage_info['garage_num']) && $park_month_garage_info['garage_num'] ? $park_month_garage_info['garage_num'].'（车场）' : '';
            }
        } else {
            $park_month_name = '';
        }
        if (isset($car_info['end_time']) && $car_info['end_time'] > 1) {
            $remark .= '\n'.$park_month_name.'月租期限：'.date('Y-m-d H:i:s', $car_info['end_time']);
            if ($car_info['end_time'] < $now_time) {
                $remark .= '（已过期）';
            }
        }
        if ($this->stored_balance > 0) {
            // 车辆储值余额
            $remark .= '\n储值余额：'.$this->stored_balance.'元';
        }
        if ($this->remarkTxt) {
            $remark .= $this->remarkTxt;
        }
        $param = [
            'openid'            => $openid,
            'passage_direction' => $passage_info['passage_direction'],
            'car_number'        => $car_number,
            'village_name'      => $village_info['village_name'],
            'park_name'         => $park_name,
            'remark'            => $remark,
            'time'            => $time,
        ];
        if ($car_number) {
            fdump_api(['推送模板=='.__LINE__=>$car_number,'param' => $param,'property_id' => $property_id],'park_temp/log_'.$car_number,1);
        }
        return $this->sendMassage($param, $property_id);
    }
    
    /**
     * 发送模板消息
     * @author:zhubaodi
     * @date_time: 2022/11/29 16:18
     */
    public function sendMassage($param,$property_id=0){
        if ($param['passage_direction']==1){
            $passage_direction='入场';
        }else{
            $passage_direction='出场';  
        }
        $remark     = isset($param['remark'])     && $param['remark']     ? $param['remark']     : '';
        $park_name  = isset($param['park_name'])  && $param['park_name']  ? $param['park_name']  : $param['village_name'].'(小区)';
        $car_number = isset($param['car_number']) && $param['car_number'] ? $param['car_number'] : '';
        $data=[
            'wecha_id' => $param['openid'],
            'first'    => '车辆'.$passage_direction.'成功',
            'keyword1' => $car_number,
            'keyword2' => $park_name,
            'keyword3' => date('Y-m-d H:i:s'),
            'remark'   => $remark,
            'new_info' => [//新版本发送需要的信息
                'tempKey'=>'43021',//新模板号
                'car_number2'=>$car_number,//车牌号
                'thing3'=>$park_name,//停车场
                'phrase6'=>$passage_direction,//进出类型
                'time4'=>date('Y-m-d H:i:s'),//进出时间
            ],
        ];
        if ($car_number) {
            fdump_api(['模板消息结果=='.__LINE__=>$car_number,'param' => $param,'data' => $data],'park_temp/log_'.$car_number,1);
        }
        $result=(new TemplateNewsService())->sendTempMsg('OPENTM207117419', $data,0,$property_id,($property_id ? 1 : 0));//todo 类目模板OPENTM207117419
        if ($car_number) {
            fdump_api(['模板消息结果=='.__LINE__=>$car_number,'result' => $result,'data' => $data,'property_id' => $property_id],'park_temp/log_'.$car_number,1);
        }

        if ($param['passage_direction'] != 1){//出场增加一个停车缴费通知
            $amount20 = $this->stored_balance > 0 ? getFormatNumber($this->stored_balance) : '0';
            $amount5 = $this->pay_price > 0 ? getFormatNumber($this->pay_price) : '0';
            $thing4 = $param['time'] ?? '无';
            $sendMsg = [
                'wecha_id' => $param['openid'],
                'car_number2'=>$car_number,//车牌号
                'thing3'=>$park_name,//停车场
                'thing4'=>$thing4,//停车时长
                'amount5'=>$amount5.'元',//缴费金额
                'amount20'=>$amount20.'元',//储值卡余额
            ];
            (new TemplateNewsService())->sendTempMsg('43837', $sendMsg,0,$property_id,($property_id ? 1 : 0));
        }
        fdump_api(['发送模板消息==line:'.__LINE__,$data,$result],'park_month_day/send_wx',1);
        return true;
    }

    /**
     * 根据车牌号查询用户信息
     * @author:zhubaodi
     * @date_time: 2022/11/29 16:33
     */
    public function getUserInfo($car_number,$village_id){
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $db_house_village_bind_car = new HouseVillageBindCar();
        $db_house_village_user_bind = new HouseVillageUserBind();
        $db_house_village_visitor = new HouseVillageVisitor();
        $db_user=new User();
        $province = mb_substr($car_number, 0, 1);
        $car_no = mb_substr($car_number, 1);
        $car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no,'village_id'=>$village_id]);
        $car_id = isset($car_info['car_id']) && $car_info['car_id'] ? $car_info['car_id'] : 0;
        $car_user_phone = isset($car_info['car_user_phone']) && $car_info['car_user_phone'] ? trim($car_info['car_user_phone']) : '';
        if ($car_id > 0) {
            $bind_info = $db_house_village_bind_car->getFind(['car_id' => $car_id,'village_id'=>$village_id]);
            $bind_info_uid = isset($bind_info['uid']) && $bind_info['uid'] ? $bind_info['uid'] : 0;
        }
        $where_visitor = [
            ['car_id', '=', $car_number],
            ['village_id', '=', $village_id],
        ];
        $temp_visitor = $db_house_village_visitor->get_one($where_visitor);
        $visitor_uid = isset($temp_visitor['visitor']) && $temp_visitor['visitor'] ? $temp_visitor['visitor'] : 0;
        
        if ($car_user_phone) {
            $user_info = $db_user->getOne(['phone'=>$car_user_phone],'openid');
            if (isset($user_info['openid']) && $user_info['openid']){
                return $user_info['openid'];
            }
        }
        
        if (isset($bind_info_uid) && $bind_info_uid > 0){
           $user_info = $db_user->getOne(['uid'=>$bind_info_uid],'openid');
           if (isset($user_info['openid']) && $user_info['openid']){
               return $user_info['openid'];
           }
        }
        if (isset($bind_info) && isset($bind_info['user_id']) && $bind_info['user_id'] > 0) {
            $user_info = $db_house_village_user_bind->getUserBindInfo(['hvb.pigcms_id' => $bind_info['user_id']], 'u.openid');
            if (isset($user_info['openid']) && $user_info['openid']) {
                return $user_info['openid'];
            }
        }
        if ($visitor_uid > 0) {
            $user_info = $db_user->getOne(['uid' => $visitor_uid], 'openid');
            if (isset($user_info['openid']) && $user_info['openid']) {
                return $user_info['openid'];
            }
        }
        return '';
    }
    
    public function CarNoPayToOut($param = [], $car_number = '', $village_id = 0, $device_number = '', $inParkOrderId = '') {
        fdump_api([
            '$param' => $param, '$car_number' => $car_number, '$village_id' => $village_id, 
            '$device_number' => $device_number, '$inParkOrderId' => $inParkOrderId,
        ], '$CarNoPayToOut', 1);
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $db_in_park  = new InPark();
        $db_out_park = new OutPark();
        $db_park_passage = new ParkPassage();
        $db_house_village_parking_temp = new HouseVillageParkingTemp();

        $query_order_no = isset($param['query_order_no']) && $param['query_order_no'] ? trim($param['query_order_no']) : '';
        $car_number     = isset($param['car_number'])     && $param['car_number']     ? trim($param['car_number'])     : $car_number;
        $village_id     = isset($param['village_id'])     && $param['village_id']     ? $param['village_id']           : $village_id;
        $device_number  = isset($param['device_number'])  && $param['device_number']  ? trim($param['device_number'])  : $device_number;
        $inParkOrderId  = isset($param['inParkOrderId'])  && $param['inParkOrderId']  ? trim($param['inParkOrderId'])  : $inParkOrderId;
        $where_temp = [];
        if ($query_order_no) {
            $where_temp[] = ['query_order_no', '=', $query_order_no];
            $parking_temp = $db_house_village_parking_temp->getFind($where_temp);
        } elseif ($car_number && (!$village_id || !$device_number || !$inParkOrderId)) {
            $where_temp[] = ['car_number', '=', $car_number];
            $parking_temp = $db_house_village_parking_temp->getFind($where_temp);
        }
        if (isset($parking_temp) && !$car_number && isset($parking_temp['car_number'])) {
            $car_number = $parking_temp['car_number'];
        }
        if (isset($parking_temp) && !$village_id && isset($parking_temp['village_id'])) {
            $village_id = $parking_temp['village_id'];
        }
        if (isset($parking_temp) && !$device_number && isset($parking_temp['out_channel_id'])) {
            $device_number = $parking_temp['out_channel_id'];
        }
        if (isset($parking_temp) && !$inParkOrderId && isset($parking_temp['order_id'])) {
            $inParkOrderId = $parking_temp['order_id'];
        }
        fdump_api([
            '$parking_temp' => $parking_temp
        ], '$CarNoPayToOut', 1);
        if (!$car_number) {
            return false;
        }
        
        $village_info = $this->HouseVillage->getOne($village_id, 'village_name,village_id,property_id');
        
        $now_time = time();
        if ($inParkOrderId) {
            $whereInPark = [];
            $whereInPark[] = ['order_id',    '=', $inParkOrderId];
            $in_park_info = $db_in_park->getOne1($whereInPark);
        } else {
            $whereInPark = [];
            $whereInPark[] = ['car_number',    '=', $car_number];
            $whereInPark[] = ['park_id',       '=', $village_id];
            $whereInPark[] = ['park_sys_type', '=', 'A11'];
            $whereInPark[] = ['is_out',        '=', 0];
            $whereInPark[] = ['del_time',        '=', 0];
            $in_park_info = $db_in_park->getOne1($whereInPark);
        }
        if (isset($parking_temp) && isset($parking_temp['duration'])) {
            $park_time = $parking_temp['duration'];
        } else {
            $park_time = $now_time - $in_park_info['in_time'];
        }
        $passage_info = $db_park_passage->getFind(['device_number'=> $device_number]);
        $park_data = [];
        $park_data['out_time']       = $now_time;
        $park_data['out_channel_id'] = $passage_info['id'];
        $park_data['is_out']         = 1;
        $park_data['is_paid']        = 1;
        $park_data['park_time']      = $park_time;
        //写入车辆入场表
        $whereInPark = [];
        $whereInPark[] = ['id',    '=', $in_park_info['id']];
        fdump_api([
            '$in_park_info' => $in_park_info, '$passage_info' => $passage_info, '$park_data' => $park_data,
        ], '$CarNoPayToOut', 1);
        
        $db_in_park->saveOne($whereInPark, $park_data);
        $out_data['total']    = 0;
        $out_data['pay_type'] = 'free';
        $starttime = time() - 30;
        $endtime   = time() + 50;
        $park_where = [
            ['car_number', '=', $car_number],
            ['park_id',    '=', $passage_info['village_id']],
            ['out_time',   '>=', $starttime],
            ['out_time',   '<=', $endtime],
        ];
        $park_info_car = $db_out_park->getOne($park_where);
        //写入车辆出场表
        if (empty($park_info_car)) {
            $out_data['car_number'] = $car_number;
            $out_data['park_id']    = $passage_info['village_id'];
            $out_data['in_time']    = $in_park_info['in_time'];
            $out_data['out_time']   = $now_time;
            $out_data['order_id']   = $in_park_info['order_id'];
            fdump_api([
                '$out_data' =>$out_data,
            ], '$CarNoPayToOut', 1);
            $insert_id = $db_out_park->insertOne($out_data);
        }
        $park_where = [
            ['car_number' , '=', $car_number],
            ['park_id'    , '=', $passage_info['village_id']],
            ['accessType' , '=',2],
            ['accessTime' , '>=', $starttime],
            ['accessTime' , '<=', $endtime],
        ];
        $park_info_car = $db_house_village_car_access_record->getOne($park_where);
        //写入车辆出场表
        if (empty($park_info_car)) {
            $park_where = [
                ['car_number'  , '=', $car_number],
                ['park_id'     , '=', $passage_info['village_id']],
                ['accessType'  , '=',1],
                ['is_out', '=' , 0],
            ];
            $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
            fdump_api([
                '$park_info_car111' =>$park_info_car111,
            ], '$CarNoPayToOut', 1);
            $db_house_village_car_access_record->saveOne(['record_id'=>$park_info_car111['record_id']],['is_out'=>1]);
            $parking_car_type = self::Temp_A;
            $car_access_record['business_type'] = 0;
            $car_access_record['business_id']   = $passage_info['village_id'];
            $car_access_record['car_number']    = $car_number;
            $car_access_record['accessType']    = 2;
            $car_access_record['accessTime']    = $now_time;
            $car_access_record['accessMode']    = 3;
            $car_access_record['park_sys_type'] = 'A11';
            $car_access_record['park_car_type'] = $this->parking_a11_car_type_arr[$parking_car_type];
            $car_access_record['coupon_id']     = 0;
            $car_access_record['park_id']       = $passage_info['village_id'];
            $car_access_record['park_name']     = $village_info['village_name'] ? $village_info['village_name']:'';
            $car_access_record['order_id']      = $park_info_car111['order_id'];
            $car_access_record['total']         = 0;
            // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
            // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
            //交易流水号(pay_type为wallet、scancode、sweepcode必传)
            $car_access_record['pay_type']      = $this->pay_type[5];
            $car_access_record['update_time']   = $now_time;
            $car_access_record['trade_no']      = $now_time . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
            if (isset($insert_id)) {
                $car_access_record['from_id'] = $insert_id;
            }
            fdump_api([
                '$car_access_record' =>$car_access_record,
            ], '$CarNoPayToOut', 1);
            $record_id = $db_house_village_car_access_record->addOne($car_access_record);
        }
        $hours  = intval($park_time / 3600);
        $mins   = intval(($park_time - $hours * 3600) / 60);
        $second = $park_time - $hours * 3600 - $mins * 60;
        $duration_txt = '';
        if ($hours > 0) {
            $duration_txt .= $hours.'小时';
        }
        if ($mins > 0) {
            $duration_txt .= $mins.'分钟';
        }
        if ($second > 0) {
            $duration_txt .= $second.'秒';
        }
        $channel_id = $passage_info['device_number'];
        //TODO:屏显和语音提示车辆通行
        $showscreen_data['car_type']     = 'temporary_type';
        $showscreen_data['passage']      = $passage_info;
        $showscreen_data['duration_txt'] = $duration_txt;
        $showscreen_data['duration']     = $park_time;
        $showscreen_data['village_id']   = $village_id;
        $showscreen_data['channel_id']   = $channel_id;
        $showscreen_data['price']        = 0;
        fdump_api([
            '$showscreen_data' =>$showscreen_data,
        ], '$CarNoPayToOut', 1);
        $this->addParkShowScreenLog($showscreen_data);
        
        $data_screen = [
            'passage'       => $passage_info,
            'car_type'      => 'temporary_type',
            'village_id'    => $village_id,
            'car_number'    => $car_number,
            'channel_id'    => $channel_id,
            'content'       => '请通行,祝您一路平安',
            'voice_content' => 6
        ];
        fdump_api([
            '$showscreen_data' =>$showscreen_data,
        ], '$CarNoPayToOut', 1);
        $this->addParkShowScreenLog($data_screen);
        
        //发送模板消息
        $openid=$this->getUserInfo($car_number,$passage_info['village_id']);
        fdump_api([
            '$openid' =>$openid,
        ], '$CarNoPayToOut', 1);
        if (!empty($openid)){
            fdump_api([
                '$param' =>$param,
                '$village_info' =>$village_info,
            ], '$CarNoPayToOut', 1);
            $db_house_village_parking_garage=new HouseVillageParkingGarage();
            $garage_info = $db_house_village_parking_garage->getOne(['garage_id'=>$passage_info['garage_id']]);
            $this->remarkTxt      = '\n出场类型： 临时车';
            $this->pay_price = '0';
            $this->stored_balance = isset($car_info['stored_balance']) && $car_info['stored_balance'] ? $car_info['stored_balance'] : 0;
            $this->sendCarMeassage($openid, $car_number, $car_info, $village_info, $garage_info, $passage_info, $duration_txt, $village_info['property_id'], [],$now_time, false);
        }
        $db_park_open_log = new ParkOpenLog();

        $park_log_data = [];
        $park_log_data['car_number'] = $car_number;
        $park_log_data['channel_id'] = $channel_id;
        $park_log_data['park_type']  = 2;
        $park_log_data['add_time']   = $now_time;
        fdump_api([
            '$park_log_data' =>$park_log_data,
        ], '$CarNoPayToOut', 1);
        $log_id = $db_park_open_log->add($park_log_data);
        //车辆通行
        return true;
    }
}