<?php
/**
 * @author : liukezhu
 * @date : 2021/9/26
 */
namespace app\community\model\service;


use app\community\model\db\HouseCameraDevice;

use app\community\model\db\HouseCameraDeviceVtype;
use app\community\model\db\HouseElectricDevice;
use app\community\model\db\HouseFaceDevice;
use app\community\model\db\HouseMaintenanLog;
use app\community\model\db\HouseVillageAc;
use app\community\model\db\HouseVillageBindCar;
use app\community\model\db\HouseVillageBindPosition;
use app\community\model\db\HouseVillageCoordinateLog;
use app\community\model\db\HouseVillageDeviceCount;
use app\community\model\db\HouseVillageDoor;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageInfo;
use app\community\model\db\HouseVillageLayer;
use app\community\model\db\HouseVillagePayOrder;
use app\community\model\db\HouseVillagePileEquipment;
use app\community\model\db\HouseVillagePublicArea;
use app\community\model\db\HouseVillageRepairList;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\InPark;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\IntelligenceExpress;
use app\community\model\db\ParkPassage;
use app\community\model\db\HouseVillageCarAccessRecord;
use app\community\model\db\HouseNewPayOrder;
use app\traits\house\HouseTraits;
use net\Http as Http;
use think\facade\Cache;
use think\facade\Db;

class AockpitService
{

	use HouseTraits;
	
    protected $HouseVillage;
    protected $HouseVillageSingle;
    protected $HouseVillageFloor;
    protected $HouseVillageLayer;
    protected $HouseVillageUserVacancy;
    protected $HouseVillageUserBind;
    protected $HouseVillageBindPosition;
    protected $HouseVillageBindCar;
    protected $HouseVillageInfo;
    protected $houseType;


    public $device_family=['1'=>'人脸识别门禁', '2'=>'蓝牙门禁', '3'=>'人脸识别摄像机', '4'=>'智能快递柜', '5'=>'智慧停车', '6'=>'智能充电桩','7'=>'无线AP'];
    public $face_device_type=['1'=>'A1智能门禁机', '2'=>'A2智能门禁机', '3'=>'A3智能门禁机', '4'=>'A4智能门禁机','5'=>'A5智能门禁机','7'=>'A7智能门禁机', '8'=>'A8智能门禁','22'=>'D2 4G微信远程开门','21'=>'D1智能门禁', '23'=>'D3智能门禁','25'=>'D5智能门禁','26'=>'D6智能门禁','61'=>'A185智能门禁', '29'=>'D9智能门禁','24'=>'D4智能门禁', '88'=>'大华人脸门禁'];
    public $status_color=[
            [
                'color'=>'#008000',
                'title'=>'在线状态'
            ],
            [
                'color'=>'#ffa500',
                'title'=>'离线状态'
            ]
        ];
    //1：人脸 4：充电桩 3：AC 2：停车 5：视频监控
    public $sub_series_type_arr=[1=>1,2=>5,3=>7,4=>6,5=>3];
	public $cacheTag = 'village_Aockpit';
	
    public function __construct()
    {
        $this->HouseVillage=new HouseVillage();
        $this->HouseVillageSingle=new HouseVillageSingle();
        $this->HouseVillageFloor=new HouseVillageFloor();
        $this->HouseVillageLayer=new HouseVillageLayer();
        $this->HouseVillageUserVacancy=new HouseVillageUserVacancy();
        $this->HouseVillageUserBind=new HouseVillageUserBind();
        $this->HouseVillageBindPosition=new HouseVillageBindPosition();
        $this->HouseVillageBindCar=new HouseVillageBindCar();
        $this->HouseVillageInfo = new HouseVillageInfo();
        $this->HouseVillageCarAccessRecord=new HouseVillageCarAccessRecord();
        $this->HouseMaintenanLog = new HouseMaintenanLog();

        $this->houseType=[
            array(
                'key'=>1,
                'value'=>'空置',
                'color'=>'#1452DA'
            ),
            array(
                'key'=>2,
                'value'=>'业主入住',
                'color'=>'#F53867'
            ),
            array(
                'key'=>3,
                'value'=>'租客入住',
                'color'=>'#FFA732'
            )
        ];
    }
	
	
	public function deleteCacheTag()
	{
		Cache::tag($this->cacheTag)->clear();
	}

    /**
     * 获取环境信息
     * @author lijie
     * @date_time 2021/09/26
     * @param $data
     * @return array|string
     */
    public function weather($data){
        $params = array(
            'cityname' => $data,//要查询的城市，如：温州、上海、北京
            'key' => cfg('oneboxWeatherQueryAppKey'),//应用APPKEY(应用详细页查询)
            'dtype' => 'json',//返回数据的格式,xml或json，默认json
        );
        $return = Http::curlQyWxPost('http://op.juhe.cn/onebox/weather/query',$params);
        if($return['error_code']){
            return '';
        } else {
            $result	=	$return['result']['data']['realtime'];
            $results	=	$return['result']['data']['weather'][0]['info'];
            $temperature=array();
            $temperature[]=$results['night'][2];
            $temperature[]=$results['day'][2];
            $temperature[]=$results['dawn'][2];
            $max=max($temperature);
            $min=min($temperature);

            $arr	=	array(
                'info'	=>	$result['weather']['info'],	//天气
                'date'	=>	$result['date'], // 获取到的天气日期
                'time'	=>	$result['time'], // 获取到的天气时间
                'temperature'	=>	$result['weather']['temperature'],	//温度
                'humidity'	=>	$result['weather']['humidity'],	//湿度
                'pm25'	=>	$return['result']['data']['pm25']['pm25']['pm25'],	//pm2.5
                'pm10'	=>	$return['result']['data']['pm25']['pm25']['pm10'],	//pm10
                'quality'	=>	$return['result']['data']['pm25']['pm25']['quality'],	//空气质量
                'level'	=>	$return['result']['data']['pm25']['pm25']['level'],	//空气质量等级
                'min'	=>	$min,	//最小温度
                'max'	=>	$max,	//最大温度
                'city_name'	=>	$result['city_name'],	//城市
                'direct'	=>	$result['wind']['direct'],	//风向
                'power'	=>	$result['wind']['power'],		//风级
            );
            return	$arr;
        }
    }

    public function getVillageData($village_id){
        $data=array();
        //$data['village_icon']=cfg('config.site_url').'/static/images/cockpit/village.png';
        $data['village_icon']='';
        $village_Info= $this->HouseVillageInfo->getOne(array('village_id'=>$village_id),'village_cockpit_logo');
        if($village_Info && !$village_Info->isEmpty()){
            $village_cockpit_logo=$village_Info['village_cockpit_logo'];
            if($village_cockpit_logo){
                $data['village_icon']=replace_file_domain($village_cockpit_logo);
            }
        }
        return $data;
    }

    /**
     * 设施管理
     * @author: liukezhu
     * @date : 2021/9/26
     * @param $village_id
     * @return array
     */
    public function getFacilities($village_id){
        $cameraDevice= new HouseCameraDevice();
        $faceDevice= new HouseFaceDevice();
        $passage= new ParkPassage();
        $data=[
            'title'=> '设施管理',
            'img'=> cfg('config.site_url').'/static/images/cockpit/facilities.png'
        ];
        $data['type']=array(
            array(
                'color'=> 'green',
                'title'=> '设施数量'
            ),
            array(
                'color'=> 'red',
                'title'=> '报警数量'
            ),
            array(
                'color'=> 'yellow',
                'title'=> '断线数量'
            )
        );
        //todo 视频监控
        $where[]=[ 'village_id','=',$village_id];
        $total=$cameraDevice->getCount($where);
        $where_log=[];
        $where_log[]=[ 'village_id','=',$village_id];
        $where_log[]=[ 'device_type','=',3];
        $alarm=$this->HouseMaintenanLog->getCount($where_log);
        $offline=0;
        $data['list'][]=array(
            'title'         => '视频监控',
            'img'           => cfg('config.site_url').'/static/images/cockpit/video.png',
            'total_arr'     => ['color'=>'green','num'=>$total],
            'alarm_arr'     => ['color'=>'red','num'=>$alarm],
            'offline_arr'   => ['color'=>'yellow','num'=>$offline],
            'total'         => $total,
            'alarm'         => $alarm,
            'offline'       => $offline
        );
        //todo 出入口门禁
        $where=[];
        $where[]=[ 'village_id','=',$village_id];
        $where[]=[ 'is_del','=',0];
        $total=$faceDevice->getCount($where);
        $where_log=[];
        $where_log[]=[ 'village_id','=',$village_id];
        $where_log[]=[ 'device_type','=',1];
        $alarm=$this->HouseMaintenanLog->getCount($where_log);
        $where=[];
        $where[]=[ 'village_id','=',$village_id];
        $where[]=[ 'is_del','=',0];
        $where[]=[ 'device_status','=',2];
        $offline=$faceDevice->getCount($where);
        $data['list'][]=array(
            'title'         => '出入口门禁',
            'img'           => cfg('config.site_url').'/static/images/cockpit/control.png',
            'total_arr'     => ['color'=>'green','num'=>$total],
            'alarm_arr'     => ['color'=>'red','num'=>$alarm],
            'offline_arr'   => ['color'=>'yellow','num'=>$offline],
            'total'         => $total,
            'alarm'         => $alarm,
            'offline'       => $offline
        );
        //todo 机动车道闸
        $where=[];
        $where[]=[ 'village_id','=',$village_id];
        $total=$passage->getCount($where);
        $where_log=[];
        $where_log[]=[ 'village_id','=',$village_id];
        $where_log[]=[ 'device_type','=',5];
        $alarm=$this->HouseMaintenanLog->getCount($where_log);
        $where=[];
        $where[]=[ 'village_id','=',$village_id];
        $where[]=[ 'status','<>',1];
        $offline=$passage->getCount($where);
        $data['list'][]=array(
            'title'         => '机动车道闸',
            'img'           => cfg('config.site_url').'/static/images/cockpit/gate.png',
            'total_arr'     => ['color'=>'green','num'=>$total],
            'alarm_arr'     => ['color'=>'red','num'=>$alarm],
            'offline_arr'   => ['color'=>'yellow','num'=>$offline],
            'total'         => $total,
            'alarm'         => $alarm,
            'offline'       => $offline
        );
        //todo 环境监测
        $total=0;
        $alarm=0;
        $offline=0;
        $data['list'][]=array(
            'title'         => '环境监测',
            'img'           => cfg('config.site_url').'/static/images/cockpit/huanjing.png',
            'total_arr'     => ['color'=>'green','num'=>$total],
            'alarm_arr'     => ['color'=>'red','num'=>$alarm],
            'offline_arr'   => ['color'=>'yellow','num'=>$offline],
            'total'         => $total,
            'alarm'         => $alarm,
            'offline'       => $offline
        );
        $data['device_total']= array_sum(array_column($data['list'], 'total'));
        return $data;
    }

    /**
     * 车辆信息
     * @author: liukezhu
     * @date : 2021/9/26
     * @param $village_id
     */
    public function getVehicle($village_id){
        //pigcms_house_village_parking_car表 card_id有值表示月租车 card_id和stored_card都为空表示临时车
        $InParkServe= new InPark();
        $data=[
            'title'=> '车辆信息',
            'img'=> cfg('config.site_url').'/static/images/cockpit/cheliang.png',
        ];
        $data['type']=array(
            array(
                'color'=> 'yellow',
                'title'=> '入场车辆'
            ),
            array(
                'color'=> 'blue',
                'title'=> '出场车辆'
            )
        );
        $time=time();
        $start_time=strtotime(date('Y-m',$time));
        $end_time=strtotime(date('Y-m',$time).'+1 month');
        //todo 月租车--进场
        $where=[];
        $where[] = ['business_type','=','0'];
        $where[] = ['business_id','=',$village_id];
        $where[] = ['car_type','=','monthCar'];
        $where[] = ['accessType','=','1'];
        $where[] = ['accessTime', '>=',$start_time];
        $where[] = ['accessTime', '<', $end_time];
        $enter=$this->HouseVillageCarAccessRecord->getCount($where);
        //todo 月租车--出场
        $where=[];
        $where[] = ['business_type','=','0'];
        $where[] = ['business_id','=',$village_id];
        $where[] = ['car_type','=','monthCar'];
        $where[] = ['accessType','=','2'];
        $where[] = ['accessTime', '>=',$start_time];
        $where[] = ['accessTime', '<', $end_time];
        $out=$this->HouseVillageCarAccessRecord->getCount($where);
        $data['list'][]=array(
            'title' => '月租车',
            'img'   => cfg('config.site_url').'/static/images/cockpit/ruchang.png',
            'enter' => $enter,
            'out'   => $out,
            'unit'  => '辆'
        );

        //todo 临时车--进场
        $where=[];
        $where[] = ['business_type','=','0'];
        $where[] = ['business_id','=',$village_id];
        $where[] = ['car_type', 'in', ['temporaryuUnlicensed','temporaryCar']];;
        $where[] = ['accessType','=','1'];
        $where[] = ['accessTime', '>=',$start_time];
        $where[] = ['accessTime', '<', $end_time];
        $enter=$this->HouseVillageCarAccessRecord->getCount($where);
        //todo 临时车--出场
        $where=[];
        $where[] = ['business_type','=','0'];
        $where[] = ['business_id','=',$village_id];
        $where[] = ['car_type', 'in', ['temporaryuUnlicensed','temporaryCar']];;
        $where[] = ['accessType','=','2'];
        $where[] = ['accessTime', '>=',$start_time];
        $where[] = ['accessTime', '<', $end_time];
        $out=$this->HouseVillageCarAccessRecord->getCount($where);
        $data['list'][]=array(
            'title' => '临时车',
            'img'   => cfg('config.site_url').'/static/images/cockpit/car_out.png',
            'enter' => $enter,
            'out'   => $out,
            'unit'  => '辆'
        );
        return $data;
    }

    //todo 查询单个小区数据
    public function getVillageInfo($village_id){
        $where[]=[ 'village_id','=',$village_id];
        $where[]=[ 'status','=',1];
        $village_info=$this->HouseVillage->getInfo($where,'village_id,village_name');
        if(empty($village_info)){
            throw new \think\Exception("该小区不存在或禁用");
        }
        return $village_info;
    }

    //todo 查询单个楼栋数据
    public function getSingleInfo($village_id,$single_id){
        $where[]=[ 'id','=',$single_id];
        $where[]=[ 'village_id','=',$village_id];
        $where[]=[ 'status','=',1];
        $single_info=$this->HouseVillageSingle->getOne($where);
        if(empty($single_info)){
            throw new \think\Exception("该楼栋不存在或禁用");
        }
        return $single_info;
    }

    /**
     *单个楼栋信息
     * @author: liukezhu
     * @date : 2021/9/27
     * @param $village_id
     * @param $single_id
     * @return array
     * @throws \think\Exception
     */
    public function getBuilding($village_id=0,$single_id=0){
        $houseNewPayOrderDb=new HouseNewPayOrder();
        $village_info=self::getVillageInfo($village_id);
        $single_info=self::getSingleInfo($village_id,$single_id);
        $where=[];
        $where[]=[ 'village_id','=',$village_id];
        $where[]=[ 'single_id','=',$single_info['id']];
        $where[]=[ 'status','=',1];
        $floor_count=$this->HouseVillageFloor->getCount($where);
        $layer_count=$this->HouseVillageLayer->getCount($where);
        $where=[];
        $where[]=[ 'village_id','=',$village_id];
        $where[]=[ 'single_id','=',$single_info['id']];
        $where[] = ['status', 'in', '1,3'];
        $where[]=[ 'is_del','=',0];
        $vacancy_count=$this->HouseVillageUserVacancy->getCount($where);
        $whereArr=array();
        $whereArr[]=array('o.village_id','=',$village_id);
        $whereArr[]=array('r.village_id','=',$village_id);
        $whereArr[]=array('r.single_id','=',$single_id);
        $whereArr[]=array('r.is_del','<',1);
        $whereArr[]=array('o.is_paid','=',2);
        $whereArr[]=array('o.is_discard','=',1);
        $whereArr[] = array('o.order_type','<>','non_motor_vehicle');
        $countOrder=$houseNewPayOrderDb->getOrderRoomCount($whereArr,'o.room_id');
        $countOrder=$countOrder>0 ? $countOrder:0;
        $modify_money=$houseNewPayOrderDb->getOrderRoomSum($whereArr,'modify_money');
        $late_payment_money=$houseNewPayOrderDb->getOrderRoomSum($whereArr,'late_payment_money');
        $noPayOrder=$modify_money+$late_payment_money;
        $noPayOrder=$noPayOrder>0 ? round($noPayOrder,2):0;
        $data=array(
            'single_id'             =>  $single_info['id'],
            'single_title'          =>  $village_info['village_name'].$single_info['single_name'],
            'list'=>array(
                array(
                    'key'=>'楼栋编号',
                    'value'=>$single_info['single_number']
                ),
                array(
                    'key'=>'楼栋管家',
                    'value'=> empty($single_info['single_keeper_name']) ? '' : $single_info['single_keeper_name']
                ),
                array(
                    'key'=>'楼栋名称',
                    'value'=>$single_info['single_name']
                ),
                array(
                    'key'=>'单元数',
                    'value'=>$floor_count
                ),
                array(
                    'key'=>'楼层数',
                    'value'=>$layer_count
                ),
                array(
                    'key'=>'户数',
                    'value'=>$vacancy_count
                ),
                array(
                    'key'=>'欠费总户数',
                    'value'=>$countOrder
                ),
                array(
                    'key'=>'欠费总金额',
                    'value'=>$noPayOrder
                ),
                
            )
        );
        return $data;
    }

    /**
     * 楼栋信息总览
     * @author: liukezhu
     * @date : 2021/9/27
     * @param $village_id
     * @param $single_id
     * @return array
     * @throws \think\Exception
     */
    public function getBuildingData($village_id,$single_id){
        $village_info=self::getVillageInfo($village_id);
        $single_info=self::getSingleInfo($village_id,$single_id);
        $where=[];
        $where[]=[ 'village_id','=',$village_id];
        $where[]=[ 'single_id','=',$single_info['id']];
        $where[]=[ 'status','=',1];
        $floor_info=$this->HouseVillageFloor->getList($where,'floor_id,floor_name');
        $data=array(
            'village_name'  =>  $village_info['village_name'],
            'single_name'   =>  $single_info['single_name'],
            'single_img'    =>  cfg('config.site_url').'/static/images/cockpit/loudong.png',
            'floor_list'    =>  $floor_info,
            'house_type'    =>  $this->houseType
        );
        return $data;
    }

    /**
     * 楼栋单元下所有房间
     * @author: liukezhu
     * @date : 2021/9/27
     * @param $village_id
     * @param $single_id
     * @param $floor_id
     * @param array $param
     * @return array
     */
    public function getVacancyData($village_id,$single_id,$floor_id,$param=[]){
        $where[]=[ 'a.village_id','=',$village_id];
        $where[]=[ 'a.single_id','=',$single_id];
        $where[]=[ 'a.is_del','=',0];
        if(intval($floor_id) > 0){
            $where[]=[ 'a.floor_id','=',$floor_id];
        }
        if(isset($param['vacancy_id']) && !empty($param['vacancy_id'])){
            $where[]=[ 'a.pigcms_id','=',intval($param['vacancy_id'])];
        }
        if(isset($param['room']) && !empty($param['room'])){
            $where[]=[ 'a.room','=',$param['room']];
        }
        if(isset($param['house_type']) && !empty($param['house_type'])){
            $user_status=[];
            $sql=[];
            $house_type=explode(',',$param['house_type']);
            //房间空置
            if(in_array(1,$house_type)){
                $sql[]=' (a.user_status is null) ';
            }
            //业主入住
            if(in_array(2,$house_type)){
                $user_status[]=1;
            }
            //租客入住
            if(in_array(3,$house_type)){
                $user_status[]=3;
            }
            if(!empty($user_status)){
                $sql[]=' (a.user_status in ('.(implode(',',$user_status)).')) ';
//                $where[]=[ 'a.user_status','in',(implode(',',$user_status))];
            }
            if(!empty($sql)){
                $where['_string']=implode('OR',$sql);
            }

        }
        $list=$this->HouseVillageUserVacancy->getVacancyInfo($where,'a.pigcms_id as vacancy_id,a.room,a.status,a.user_status,a.housesize,a.house_type as house_purpose,b.name');
        if(!empty($list)){
            $list=$list->toArray();
        }
        $houseType=$this->houseType;
        $data=[];
        $house_purpose=[
            1=>'住宅', 2=>'商铺', 3=>'办公',
        ];
        foreach ($list as $v){
            $position_list=$car_list=[];
            //查询该房间里所有用户
            $where=[];
            $where[]=[ 'village_id','=',$village_id];
            $where[]=[ 'vacancy_id','=',$v['vacancy_id']];
            $where[]=[ 'status','=',1];
            $where['_string']='uid not in (0,"") OR phone not in (0,"") OR name not in (0,"")';
            $pigcms_ids=$this->HouseVillageUserBind->getUserColumn($where,'pigcms_id');
            if(!empty($pigcms_ids)){
                $user_id=implode(',',$pigcms_ids);
                $where=[];
                $where[]=[ 'b.village_id','=',$village_id];
                $where[]=[ 'b.user_id','in',$user_id];
                $where['_string']='garage_id <> 9999'; //去掉临时车位
                //车位
                $position_list=$this->HouseVillageBindPosition->getUserPosition($where,'p.position_num');
                $where=[];
                $where[]=[ 'b.village_id','=',$village_id];
                $where[]=[ 'b.user_id','in',$user_id];
                //车辆
                $car_list=$this->HouseVillageBindCar->getUserCar($where,'c.car_number');
            }
            if($v['user_status'] == 1){
                //业主入住
                $house_type=2;
            }
            elseif ($v['user_status'] == 3){
                //租客入住
                $house_type=3;
            }
            else{
                $house_type=1;
            }
            $house_title='';
            $house_color='';
            foreach ($houseType as $v2){
                if($v2['key'] == $house_type){
                    $house_title=$v2['value'];
                    $house_color=$v2['color'];
                }
            }
            $data[]=array(
                'vacancy_id'=>$v['vacancy_id'],
                'room'=>$v['room'],
                'house_type'=>$house_type,
                'house_title'=>$house_title,
                'house_color'=>$house_color,
                'list'=>array(
                    array(
                        'key'=>'面积',
                        'value'=>empty($v['housesize']) ? '--' : $v['housesize'].'平米'
                    ),
                    array(
                        'key'=>'人数',
                        'value'=>count($pigcms_ids)
                    ),
                    array(
                        'key'=>'用途',
                        'value'=>isset($house_purpose[$v['house_purpose']]) ? $house_purpose[$v['house_purpose']] : '--'
                    ),
                    array(
                        'key'=>'户主',
                        'value'=>$v['name']
                    ),
                    array(
                        'key'=>'车位',
                        'value'=>empty($position_list)? '' : implode(',',$position_list)
                    ),
                    array(
                        'key'=>'车辆',
                        'value'=>empty($car_list) ? '' : implode(',',$car_list)
                    ),
                )
            );
        }
      return $data;

    }


    /**
     * 智能设施
     * @author: liukezhu
     * @date : 2021/9/29
     * @param $village_id
     * @return array
     */
    public function getFacilitiesData($village_id,$type,$device_type,$sub_series_type,$extra_data=array()){
        $db_village_device_count=new HouseVillageDeviceCount();
        $db_house_face_device=new HouseFaceDevice();
        $db_park_passage=new ParkPassage();
        $db_house_camera_device=new HouseCameraDevice();
        $db_house_village_ac=new HouseVillageAc();
        if ($type==1){
            $field='village_photo2';
        }elseif($type==2){
            $field='village_photo1';
        }elseif($type==3){
            $field='village_photo3';
        }else{
            $field='village_photo';
        }

        $not_where[] = ['village_id','=',$village_id];
        $village_photo= $this->HouseVillageInfo->getColumn($not_where,$field);
        $data['is_photo']=empty($village_photo) ? 0 : 1;
        $village_photo[0]=replace_file_domain($village_photo[0]);
        $data['village_photo']=$village_photo;
        $data['jump_url']='/village/village.iframe/house_index_config';
        $data['type']=$this->status_color;
        $device_coordinate_list=$db_village_device_count->getLists([['village_id','=',$village_id],['address_type','=',$type]],'*',0);
        $device_data=[];
        $db_houseCameraDeviceVtype = new HouseCameraDeviceVtype();
        $whereArr=array();
        $whereArr[]=array('village_id','=',$village_id);
        $whereArr[]=array('del_time','=',0);
        $whereArr[]=array('status','=',1);
        $deviceVtypelist = $db_houseCameraDeviceVtype->getDataLists($whereArr, 'id as cate_id,vname as name,xtype', 'xsort desc,id desc', 0, 100);
        $dev_cate=[['cate_id'=>1,'name'=>'普通视频监控'], ['cate_id'=>2,'name'=>'高空抛物视频监控']];
        if($deviceVtypelist){
            foreach ($deviceVtypelist as $ckk=>$cvv){
                if($cvv['xtype']>0){
                    $deviceVtypelist[$ckk]['cate_id']=$cvv['xtype'];
                }
            }
            $dev_cate=$deviceVtypelist;
        }
        $device_data_list=array('vmonitoring'=>array('total'=>0,'type_name'=>'视频监控','dev_cate'=>$dev_cate,'series_img'=>'https://hf.pigcms.com/static/village_icon/jiankong.png','device_type'=>'vmonitoring','device_type_arr'=>array(),'sub_dev'=>array()),'access_dev'=>array('total'=>0,'type_name'=>'门禁设备','series_img'=>'https://hf.pigcms.com/static/village_icon/renlian.png','device_type'=>'access_dev','device_type_arr'=>array(),'sub_dev'=>array()),'parking_dev'=>array('total'=>0,'type_name'=>'停车设备','series_img'=>'https://hf.pigcms.com/static/village_icon/car.png','device_type'=>'parking_dev','device_type_arr'=>array(),'sub_dev'=>array()),'wireless_ap'=>array('total'=>0,'type_name'=>'无线AP','series_img'=>'https://hf.pigcms.com/static/village_icon/wuxian.png','device_type'=>'wireless_ap','device_type_arr'=>array(),'sub_dev'=>array()));
        $device_type_to_family=array('vmonitoring'=>3,'access_dev'=>1,'parking_dev'=>5,'wireless_ap'=>7);
        $device_type_ids=array();
        if($device_type && strpos($device_type,',')>0){
            $device_type_arr=explode(',',$device_type);
            foreach ($device_type_arr as $ddv){
                if(isset($device_type_to_family[$ddv])){
                    $device_type_ids[]=$device_type_to_family[$ddv];
                }
            }
        }elseif($device_type && isset($device_type_to_family[$device_type])){
            $device_type_ids[]=$device_type_to_family[$device_type];
        }
        if (!empty($device_coordinate_list)){
            $device_coordinate_list=$device_coordinate_list->toArray();
            //print_r($device_coordinate_list);exit;
            if (!empty($device_coordinate_list)){
                foreach ($device_coordinate_list as $k=>&$v){
                    $is_unset=false;
                    $v['device_status']=$this->status_color[1]['title'];
                    $v['status_color']=$this->status_color[1]['color'];
                    if ($v['device_family']==1){
                       $face_info=$db_house_face_device->getOne(['device_id'=>$v['device_id']],'device_status,device_id,device_name,device_type');
                        if (!empty($face_info)){
                            $device_data[1][]=$face_info['device_type'];
                            $device_data_list['access_dev']['total']++;
                            $device_data_list['access_dev']['device_type_arr'][]=$face_info['device_type'];
                            $v['device_status']=$face_info['device_status']==1?$this->status_color[0]['title']:$this->status_color[1]['title'];
                            $v['status_color']=$face_info['device_status']==1?$this->status_color[0]['color']:$this->status_color[1]['color'];
                        }else{
                            $is_unset=true;
                        }
                    }
                    elseif($v['device_family']==5){
                        $park_passage_info=$db_park_passage->getFind(['id'=>$v['device_id']],'status,id,passage_name,park_sys_type');
                        if (!empty($park_passage_info)){
                            $device_data_list['parking_dev']['total']++;
                            $device_data_list['parking_dev']['device_type_arr'][]=$park_passage_info['park_sys_type'];
                            $device_data[5][]=$park_passage_info['park_sys_type'];
                            $v['device_status']=$park_passage_info['status']==0?$this->status_color[0]['title']:$this->status_color[1]['title'];
                            $v['status_color']=$park_passage_info['status']==0?$this->status_color[0]['color']:$this->status_color[1]['color'];
                        }else{
                            $is_unset=true;
                        }
                    }
                    elseif($v['device_family']==3){
                        $whereArr=[];
                        $whereArr[]=['camera_id','=',$v['device_id']];
                        if(!empty($extra_data)&& !empty($extra_data['cate_id']) && in_array($v['device_family'],$device_type_ids)){
                            $cate_id_arr=explode(',',$extra_data['cate_id']);
                            $whereArr[]=['device_type','in',$cate_id_arr];
                        }
                        $camera_info=$db_house_camera_device->getOne($whereArr,'camera_status,camera_id,camera_name,product_model,device_type');
                        if (!empty($camera_info)){
                            $device_data_list['vmonitoring']['total']++;
                            $device_data_list['vmonitoring']['device_type_arr'][]=$camera_info['product_model'];

                            $device_data[3][]=$camera_info['product_model'];
                            $v['device_status']=$camera_info['camera_status']==0?$this->status_color[0]['title']:$this->status_color[1]['title'];
                            $v['status_color']=$camera_info['camera_status']==0?$this->status_color[0]['color']:$this->status_color[1]['color'];
                        }else{
                            $is_unset=true;
                        }
                    }elseif($v['device_family']==7){
                        $ac_info=$db_house_village_ac->get_one(['id'=>$v['device_id']],'status,id,ac_name,ac_series');
                        if (!empty($ac_info)){
                            $device_data[7][]='无线AP';
                            $device_data_list['wireless_ap']['total']++;
                            $device_data_list['wireless_ap']['device_type_arr'][]='无线AP';
                            $v['device_status']=$ac_info['status']==1?$this->status_color[0]['title']:$this->status_color[1]['title'];
                            $v['status_color']=$ac_info['status']==1?$this->status_color[0]['color']:$this->status_color[1]['color'];
                        }else{
                            $is_unset=true;
                        }
                    }

                    if (!empty($v['coordinate'])){
                        $v['coordinate']=json_decode($v['coordinate'],true);
                    }else{
                        $is_unset=true;
                       // unset($device_coordinate_list[$k]);
                    }
                    if (!empty($sub_series_type)&&$this->sub_series_type_arr[$sub_series_type]!=$v['device_family']){
                        $is_unset=true;
                       // unset($device_coordinate_list[$k]);
                    }
                    if($device_type && !empty($device_type_ids) && !in_array($v['device_family'],$device_type_ids)){
                        $is_unset=true;
                        //unset($device_coordinate_list[$k]);
                    }elseif(!empty($device_type)&& empty($device_type_ids) && $device_type!=$v['device_type']){
                        $is_unset=true;
                        //unset($device_coordinate_list[$k]);
                    }
                    if($extra_data && $extra_data['device_status']==1 && $v['device_status']!='在线状态'){
                        $is_unset=true;
                        //unset($device_coordinate_list[$k]);
                    }elseif ($extra_data && $extra_data['device_status']==2 && $v['device_status']!='离线状态'){
                        $is_unset=true;
                        //unset($device_coordinate_list[$k]);
                    }
                    if($is_unset){
                        unset($device_coordinate_list[$k]);
                    }
                }
            }

        }
        $device_type_list=array();
        /*
        if (!empty($device_data)){
            foreach ($device_data as &$v1){
                $v1 = array_unique($v1);
                $v1=array_values($v1);
            }
        }
        */
            foreach ($device_data_list as $kk=>$dvv){
                if(!empty($dvv['device_type_arr'])){
                    $device_data_list[$kk]['device_type_arr']=array_unique($dvv['device_type_arr']);
                }
            }
            $HardwareBrandService=new HardwareBrandService();
            $brand_type_list=$HardwareBrandService->getType();
            if($brand_type_list){
                foreach ($brand_type_list as $v2){
                    $v2['series_img']=$v2['icon'];
                    if($v2['sub_series_type']==1 && in_array($v2['device_type'],$device_data_list['access_dev']['device_type_arr'])){
                        $device_data_list['access_dev']['sub_dev']=$v2;
                    }elseif ($v2['sub_series_type']==2  && in_array($v2['sub_series_key'],$device_data_list['parking_dev']['device_type_arr'])){
                        $device_data_list['parking_dev']['sub_dev']=$v2;
                    }elseif($v2['sub_series_type']==5 && in_array($v2['series_key'],$device_data_list['vmonitoring']['device_type_arr'])){
                        $device_data_list['vmonitoring']['sub_dev']=$v2;
                    }
                    /*
                    if (!empty($device_data[1])&&$v2['sub_series_type']==1&&in_array($v2['device_type'],$device_data[1])){
                        $device_type_list[]=$v2;
                    }elseif(!empty($device_data[5])&&$v2['sub_series_type']==2&&in_array($v2['sub_series_key'],$device_data[5])){
                        $device_type_list[]=$v2;
                    }elseif(!empty($device_data[3])&&$v2['sub_series_type']==5&&in_array($v2['series_key'],$device_data[3])){
                        $device_type_list[]=$v2;
                    }
                    */

                }
            }
        foreach ($device_data_list as $kk=>$dvv){
            $device_type_list[]=$dvv;
        }
        $data['device_type_list']=$device_type_list;
        $data['device_coordinate_list']=array_values($device_coordinate_list);
        return $data;
    }

    /**
     * 智能设施
     * @author: liukezhu
     * @date : 2021/9/29
     * @param $village_id
     * @return array
     */
    public function getFacilitiesData1($village_id){
        $not_where[] = ['village_id','=',$village_id];
        $village_photo= $this->HouseVillageInfo->getOne($not_where,'village_photo1,village_photo2');
        $data['is_photo']=empty($village_photo) ? 0 : 1;
        $data['village_photo1']=$village_photo['village_photo2'];
        $data['village_photo2']=$village_photo['village_photo1'];
        $data['jump_url']='/village/village.iframe/house_index_config';
        $data['type']=$this->status_color;
        $data['list']=[
            [
                'id'=>1,
                'title'=>'视频监控',
                'img'=>cfg('config.site_url').'/static/images/cockpit/monitor.png',
            ]
        ];
        return $data;
    }

    /**
     * 设备点位信息
     * @author: liukezhu
     * @date : 2021/9/29
     * @param $village_id
     * @return mixed
     */
    public function getFacilitiesDetails($village_id,$coordinate_id,$device_id){
        $db_village_device_count=new HouseVillageDeviceCount();
        $db_House_village_floor=new HouseVillageFloor();
        $db_House_village_single=new HouseVillageSingle();
        $db_House_village_public_area=new HouseVillagePublicArea();
        $db_house_face_device=new HouseFaceDevice();
        $db_park_passage=new ParkPassage();
        $db_house_camera_device=new HouseCameraDevice();
        $db_house_village_pile_equipment=new HouseVillagePileEquipment();//充电桩设备表
        $db_house_village_ac=new HouseVillageAc();
        $device_coordinate_info=$db_village_device_count->getOne([['village_id','=',$village_id],['id','=',$coordinate_id],['device_id','=',$device_id]]);
        $device_info=[];
        if (!empty($device_coordinate_info)){
            $face_info_obj=$db_house_face_device->getOne(['device_id'=>$device_coordinate_info['device_id']]);
            $face_info=array();
            if($face_info_obj && !$face_info_obj->isEmpty()){
                $face_info= $face_info_obj->toArray();
            }
            if ($device_coordinate_info['device_family']==1){
                if (!empty($face_info)){
                    $device_info['list'][0]['key']='设备品牌';
                    $device_info['list'][0]['value']=$face_info['device_brand'];
                    $device_info['list'][1]['key']='设备类型';
                    $device_info['list'][1]['value']=$this->face_device_type[$face_info['device_type']];
                    $device_info['list'][2]['key']='设备编号';
                    $device_info['list'][2]['value']=$face_info['device_sn'];
                    $device_info['list'][3]['key']='设备名称';
                    $device_info['list'][3]['value']=$face_info['device_name'];
                    $device_info['list'][4]['key']='设备位置';
                    //查询人脸设备对应位置
                    if (isset($face_info['public_area_id']) && $face_info['public_area_id']>0) {
                        $public_area_name =$db_House_village_public_area->getOne(['public_area_id'=>$face_info['public_area_id']],'public_area_name');
                        $floor_name	=$public_area_name['public_area_name'];
                        $floor_layer=cfg('house_name');
                    } else if ($face_info['floor_id'] == -1) {
                        $floor_name	='大门';
                        $floor_layer=cfg('house_name');
                    }else{
                        $floor_arr = explode(',',$face_info['floor_id']);
                        if (count($floor_arr)==1) {
                            $face_info['floor_id'] = $floor_arr[0];
                            $aFloor	=$db_House_village_floor->getOne(['floor_id'=>$face_info['floor_id']],'floor_name,floor_layer,single_id');
                            $single_name=$db_House_village_single->getOne(['id'=>$aFloor['single_id']],'single_name');
                            $floor_name=$aFloor['floor_name'] ? $aFloor['floor_name'] : '';
                            $floor_layer=$single_name['single_name'] ? $single_name['single_name'] : $aFloor['floor_layer'];
                        } else {

                            $floor_about_info = $db_House_village_floor->floorSingleName(['floor_id' => $floor_arr],'f.floor_id, s.id as single_id, f.floor_name, s.single_name');
                            $floor_name='';
                            $floor_layer='关联多单元';
                        }
                    }
                    $address_arr='';
                    if (isset($floor_about_info)&&!empty($floor_about_info)){
                        $floor_about_info=$floor_about_info->toArray();
                        foreach ($floor_about_info as $vf){
                            $address=$vf['single_name'].'-'.$vf['floor_name'];
                            $address_arr=$address.'|'.$address_arr;
                        }
                    }else{
                        $address_arr=$floor_layer.'-'.$floor_name;
                    }
                    $device_info['list'][4]['value']=$address_arr;
                    $device_info['type']['title']=$face_info['device_status']==1?$this->status_color[0]['title']:$this->status_color[1]['title'];
                    $device_info['type']['color']=$face_info['device_status']==1?$this->status_color[0]['color']:$this->status_color[1]['color'];
                }
            }elseif($device_coordinate_info['device_family']==5){
                $park_passage_info=$db_park_passage->getFind(['id'=>$device_coordinate_info['device_id']],'*');
                if (!empty($park_passage_info)){
                    $device_info['list'][0]['key']='设备品牌';
                    $device_info['list'][0]['value']=$park_passage_info['park_brand'];
                    $device_info['list'][1]['key']='设备类型';
                    $device_info['list'][1]['value']=$park_passage_info['park_type'];
                    $device_info['list'][2]['key']='设备编号';
                    $device_info['list'][2]['value']=$park_passage_info['device_number'];
                    $device_info['list'][3]['key']='设备名称';
                    $device_info['list'][3]['value']=$park_passage_info['passage_name'];
                    $device_info['list'][4]['key']='设备位置';
                    $device_info['list'][4]['value']=$park_passage_info['passage_direction']==1?'入口':($park_passage_info['passage_direction']==2?'出入口':'出口');
                    $device_info['type']['title']=$park_passage_info['status']==0?$this->status_color[0]['title']:$this->status_color[1]['title'];
                    $device_info['type']['color']=$park_passage_info['status']==0?$this->status_color[0]['color']:$this->status_color[1]['color'];
                }
            }elseif($device_coordinate_info['device_family']==3){
                $camera_info=$db_house_camera_device->getOne(['camera_id'=>$device_coordinate_info['device_id']],'*');
                if (!empty($camera_info)){
                    $device_info['list'][0]['key']='设备品牌';
                    $device_info['list'][0]['value']=$camera_info['brand_name'];
                    $device_info['list'][1]['key']='设备类型';
                    $device_info['list'][1]['value']=$camera_info['product_model'];
                    $device_info['list'][2]['key']='设备编号';
                    $device_info['list'][2]['value']=$camera_info['camera_sn'];
                    $device_info['list'][3]['key']='设备名称';
                    $device_info['list'][3]['value']=$camera_info['camera_name'];
                    $device_info['list'][4]['key']='设备位置';

                    //查询视频监控设备对应位置
                    if ($face_info && isset($face_info['public_area_id']) && $face_info['public_area_id']>0) {
                        $public_area_name =$db_House_village_public_area->getOne(['public_area_id'=>$face_info['public_area_id']],'public_area_name');
                        $floor_name	=$public_area_name['public_area_name'];
                        $floor_layer=cfg('house_name');
                    }else if ($face_info && $face_info['floor_id'] == -1) {
                        $floor_name	='大门';
                        $floor_layer=cfg('house_name');
                    }else{
                        $aFloor	=$db_House_village_floor->getOne(['floor_id'=>$camera_info['floor_id']],'floor_name,floor_layer,single_id');
                        $single_name=$db_House_village_single->getOne(['id'=>$aFloor['single_id']],'single_name');
                        $floor_name=$aFloor['floor_name'] ? $aFloor['floor_name'] : '';
                        $floor_layer=$single_name['single_name'] ? $single_name['single_name'] : $aFloor['floor_layer'];
                    }
                    $device_info['list'][4]['value']=$floor_layer.'-'.$floor_name;
                    $device_info['type']['title']=$camera_info['camera_status']==0?$this->status_color[0]['title']:$this->status_color[1]['title'];
                    $device_info['type']['color']=$camera_info['camera_status']==0?$this->status_color[0]['color']:$this->status_color[1]['color'];
                }
            }elseif($device_coordinate_info['device_family']==7){
                $ac_info=$db_house_village_ac->get_one(['id'=>$device_coordinate_info['device_id']],'*');
                if (!empty($ac_info)){
                    $device_info['list'][0]['key']='设备品牌';
                    $device_info['list'][0]['value']='未对接';
                    $device_info['list'][1]['key']='设备类型';
                    $device_info['list'][1]['value']='无线AP';
                    $device_info['list'][2]['key']='设备编号';
                    $device_info['list'][2]['value']=$ac_info['ac_series'];
                    $device_info['list'][3]['key']='设备名称';
                    $device_info['list'][3]['value']=$ac_info['ac_name'];
                    $device_info['list'][4]['key']='设备位置';
                    if ($ac_info['area_type']==1){
                        $area_info=$db_House_village_public_area->getOne(['village_id'=>$ac_info['village_id'],'public_area_id'=>$ac_info['ac_area']],'public_area_id,public_area_name');
                        $address='小区-'.$area_info['public_area_name'];
                    }elseif($ac_info['area_type']==2){
                        $where=['village_id'=>$ac_info['village_id'],'id'=>$ac_info['ac_area']];
                        $single_info=$db_House_village_single->getOne($where,'id,single_name');
                        $address='楼栋-'.$single_info['single_name'];
                    }
                    $device_info['list'][4]['value']=$address;
                    $device_info['type']['title']=$ac_info['status']==1?$this->status_color[0]['title']:$this->status_color[1]['title'];
                    $device_info['type']['color']=$ac_info['status']==1?$this->status_color[0]['color']:$this->status_color[1]['color'];
                }
            } else{
                    $device_info['list'][0]['key']='设备品牌';
                    $device_info['list'][0]['value']='未对接';
                    $device_info['list'][1]['key']='设备类型';
                    $device_info['list'][1]['value']='未对接';
                    $device_info['list'][2]['key']='设备编号';
                    $device_info['list'][2]['value']='未对接';
                    $device_info['list'][3]['key']='设备名称';
                    $device_info['list'][3]['value']='未对接';
                    $device_info['list'][4]['key']='设备位置';
                    $device_info['list'][4]['value']='未对接';
                    $device_info['type']['title']=$this->status_color[1]['title'];
                    $device_info['type']['color']=$this->status_color[1]['color'];
                }
        }

        return $device_info;
    }


    /**
     * 天使之眼设备
     * @author: liukezhu
     * @date : 2021/10/18
     * @param $village_id
     * @return mixed
     */
    public function getMonitor($village_id){
        $not_where[] = ['village_id','=',$village_id];
        $village_photo= $this->HouseVillageInfo->getColumn($not_where,'village_photo');
        $data['village_photo']=$village_photo;
        $data['jump_url']='/village/village.iframe/house_index_config';
        $data['type']=[
            [
                'color'=>'blue',
                'title'=>'在线状态'
            ],
            [
                'color'=>'orange',
                'title'=>'离线状态'
            ]
        ];
        return $data;
    }

    /**
     * 天使之眼设备详情
     * @author: liukezhu
     * @date : 2021/9/29
     * @param $village_id
     * @return mixed
     */
    public function getMonitorDevice($village_id,$coordinate_id,$device_id){
        $db_village_device_count=new HouseVillageDeviceCount();
        $db_House_village_floor=new HouseVillageFloor();
        $db_House_village_single=new HouseVillageSingle();
        $db_House_village_public_area=new HouseVillagePublicArea();
        $db_house_camera_device=new HouseCameraDevice();
        $db_house_village_ac=new HouseVillageAc();
        $device_coordinate_info=$db_village_device_count->getOne([['village_id','=',$village_id],['id','=',$coordinate_id],['device_id','=',$device_id]]);
        $device_info=[];
        if (!empty($device_coordinate_info)){
            $camera_info=$db_house_camera_device->getOne(['camera_id'=>$device_coordinate_info['device_id']],'*');
            if (!empty($camera_info)){
                if (isset($camera_info['device_brand']) && $camera_info['device_brand']=='iSecureCenter' && $camera_info['camera_sn']) {
                    $httpSiteUrl = str_replace('https', 'http', cfg('site_url'));
//                    $device_info['videoPreviewUrl'] = $httpSiteUrl.'/shequ.php?g=House&c=iSecureCenter&a=videoPreviewFullScreen&camera_id='.$camera_info['camera_sn'];
                    $device_info['videoPreviewUrl'] = '';
                    $device_info['openVideoUrl'] = $httpSiteUrl.'/shequ.php?g=House&c=iSecureCenter&a=videoPreviewFullScreen&camera_id='.$camera_info['camera_sn'];
                    $device_info['url']='';
                } else {
                    $device_info['videoPreviewUrl'] = '';
                    $device_info['openVideoUrl'] = '';
                    $device_info['url']=$camera_info['look_url'];
                }
                $device_info['list'][0]['key']='设备品牌';
                $device_info['list'][0]['value']=$camera_info['brand_name'];
                $device_info['list'][1]['key']='设备类型';
                $device_info['list'][1]['value']=$camera_info['product_model']?$camera_info['product_model']:$camera_info['product_name'];
                $device_info['list'][2]['key']='设备编号';
                $device_info['list'][2]['value']=$camera_info['camera_sn'];
                $device_info['list'][3]['key']='设备名称';
                $device_info['list'][3]['value']=$camera_info['camera_name'];
                $device_info['list'][4]['key']='设备位置';

                //查询视频监控设备对应位置
                if (isset($camera_info['public_area_id']) && $camera_info['public_area_id']>0) {
                    $public_area_name =$db_House_village_public_area->getOne(['public_area_id'=>$camera_info['public_area_id']],'public_area_name');
                    $floor_name	=$public_area_name['public_area_name'];
                    $floor_layer=cfg('house_name');
                }else if ( $camera_info['floor_id'] == -1) {
                    $floor_name	='大门';
                    $floor_layer=cfg('house_name');
                }else{
                    $aFloor	=$db_House_village_floor->getOne(['floor_id'=>$camera_info['floor_id']],'floor_name,floor_layer,single_id');
                    $single_name=$db_House_village_single->getOne(['id'=>$aFloor['single_id']],'single_name');
                    $floor_name=$aFloor['floor_name'] ? $aFloor['floor_name'] : '';
                    $floor_layer=$single_name['single_name'] ? $single_name['single_name'] : $aFloor['floor_layer'];
                }
                if (!$floor_layer && !$floor_name && isset($camera_info['device_brand']) && $camera_info['device_brand']=='iSecureCenter' && $camera_info['camera_sn']) {
                    $device_info['list'][4]['value']=$camera_info['camera_name'];
                } else {
                    $device_info['list'][4]['value']=$floor_layer.'-'.$floor_name;
                }
                $device_info['type']['title']=$camera_info['camera_status']==0?$this->status_color[0]['title']:$this->status_color[1]['title'];
                $device_info['type']['color']=$camera_info['camera_status']==0?$this->status_color[0]['color']:$this->status_color[1]['color'];
            }
        }


        return $device_info;
    }

    /**
     * 报警信息查询
     * @author:zhubaodi
     * @date_time: 2021/9/26 11:19
     */
    public function getAlarmList($village_id,$page,$limit){
        $data[0]=[
            ['title'=>'设备类型'],
            ['title'=>'报警类型'],
            ['title'=>'状态'],
            ['title'=>'发生时间'],
        ];
        $where=[];
        $where[]=['village_id','=',$village_id];
        $nowtime=time();
        $now24time=$nowtime-86400;
        $where[]=['add_time','<=',$nowtime];
        $where[]=['add_time','>=',$now24time];
        $list=$this->HouseMaintenanLog->getList($where,'*','id DESC',$page,$limit);

        if (!empty($list)){
            $list=$list->toArray();
            if (!empty($list)){
                foreach ($list as $value){
                    $info=[];
                    $info[]['title']=$value['device_name'];
                    $info[]['title']=$value['reason'];
                    if (empty($value['next_key'])){
                        $info[]['title']='已解决';
                    }else{
                        $info[]['title']='新增';
                    }
                    $info[]['title']=date('Y-m-d H:i',$value['add_time']);
                    $data[]=$info;
                }
            }
        }
       /* $data[1]=[
            ['title'=>'智慧路灯'],
            ['title'=>'尾随报警'],
            ['title'=>'已解决'],
            ['title'=>'2021-09-26 11:11'],
        ];*/
        $list1=[];
        $list1['list']=$data;
        $list1['img']=cfg('site_url').'/static/images/cockpit/alarm.png';
        return $list1;
    }

    /**
     * 房屋统计信息查询
     * @author:zhubaodi
     * @date_time: 2021/9/26 11:39
     */
    public function getHouseCountInfo($village_id){
        $db_single=new HouseVillageSingle();
        $db_vacancy=new HouseVillageUserVacancy();
        //楼栋统计
        $res['countSingle']=$db_single->getCount(['village_id'=>$village_id,'status'=>1]);
        //房屋统计
        $res['countVacancy']=$db_vacancy->getCount(['village_id'=>$village_id,'is_del'=>0]);
        //自主统计
        $countUser=$db_vacancy->getCount(['village_id'=>$village_id,'status'=>1,'is_del'=>0,'user_status'=>1]);
        //租赁统计
        $countRent=$db_vacancy->getCount(['village_id'=>$village_id,'status'=>1,'is_del'=>0,'user_status'=>3]);
        //住户统计
        //$res['countBind']=$countUser+$countRent;
        $res['countBind']=$db_vacancy->getCount(['village_id'=>$village_id,'status'=>3,'is_del'=>0]);
        //自主占比
        $res['countUser']=empty($res['countBind']) ? 0 : $countUser/$res['countBind']*100;
        //租赁占比
        $res['countRent']=empty($res['countBind']) ? 0 : $countRent/$res['countBind']*100;
        //空户统计
        $res['countEmpty']=$res['countVacancy']-$res['countBind'];
        $res['house_img']=cfg('site_url').'/static/images/cockpit/house2.png';
        $res['house_img1']=cfg('site_url').'/static/images/cockpit/0v01_029.png';
        $res['house_img2']=cfg('site_url').'/static/images/cockpit/house_680.png';
        $res['house_img3']=cfg('site_url').'/static/images/cockpit/0v01_031.png';
        $res['house_img4']=cfg('site_url').'/static/images/cockpit/0v01_031.png';
        return $res;
    }

    /**
     * 添加楼栋区域绑定
     * @author:zhubaodi
     * @date_time: 2021/10/11 17:57
     */
    public function addVillgeArea($data){
       // var_dump($data['area'][0]);exit;
        if (!empty($data['area'])){
            $count=count($data['area']);
            if ($data['area'][0]['x']!=$data['area'][$count-1]['x']||$data['area'][0]['y']!=$data['area'][$count-1]['y']){
                throw new \think\Exception('该楼栋区域绘制未闭合');
            }
        }
        $db_single=new HouseVillageSingle();
        $single_info=$db_single->getOne(['id'=>$data['single_id'],'status'=>1]);
        if (!empty($single_info)){
            if (!empty($single_info['area'])){
                throw new \think\Exception('该楼栋已绑定区域');
            }
            $data_arr=[
                'area'=>json_encode($data['area']),
                'img'=>$data['img'],
                'area_time'=>time(),
            ];
            $res=$db_single->saveOne(['id'=>$data['single_id']],$data_arr);
           return $res;
        }else{
            throw new \think\Exception('该楼栋信息不存在');
        }

    }



    /**
     * 添加楼栋区域绑定
     * @author:zhubaodi
     * @date_time: 2021/10/11 17:57
     */
    public function editVillgeArea($data){
        if (!empty($data['area'])){
            $count=count($data['area']);
            if ($data['area'][0]['x']!=$data['area'][$count-1]['x']||$data['area'][0]['y']!=$data['area'][$count-1]['y']){
                throw new \think\Exception('该楼栋区域绘制未闭合');
            }
        }
        $db_single=new HouseVillageSingle();
        $single_info=$db_single->getOne(['id'=>$data['single_id'],'status'=>1]);
        if (!empty($single_info)){
            $data_arr=[
                'area'=>json_encode($data['area']),
                'area_time'=>time(),
            ];
            $res=$db_single->saveOne(['id'=>$data['single_id']],$data_arr);
            return $res;
        }else{
            throw new \think\Exception('该楼栋信息不存在');
        }

    }

    /**
     * 查询楼栋区域信息列表
     * @author:zhubaodi
     * @date_time: 2021/10/11 18:19
     */
    public function getVillgeAreaList($village_id,$village_name,$single_id=0){
        $db_single=new HouseVillageSingle();
        $single_List=$db_single->getList(['village_id'=>$village_id,'status'=>1],true,'area_time DESC');
        $single_arr=[];
        $single_data=[];
        if (!empty($single_List)){
            $single_List=$single_List->toArray();
            if (!empty($single_List)){
                fdump($single_List,'$single_List');
                foreach ($single_List as $k=>$v){
                    if (!empty($v['area'])){
                       $data['single_id']=$v['id'];
                       if ($single_id && $data['single_id']==intval($single_id)) {
                           $data['bgColor']='rgba(223, 6, 27, 0.5)';
                       } else {
                           $data['bgColor']='rgba(21, 236, 255, 0.5)';
                       }
                       $data['single_name']=$village_name.$v['single_name'];
                       $data['area']=json_decode($v['area'],true);
                       if (!empty($data['area'])&&is_array($data['area'])){
                           foreach ($data['area'] as &$vv){
                               $vv['x']=intval($vv['x']);
                               $vv['y']=intval($vv['y']);
                           }
                       }
                       $single_arr[]=$data;
                    }else{
                        unset($single_List[$k]);
                    }
                }
                $single_data['list']=$single_arr;
              //  $single_data['img']=$single_List[0]['img'];
            }
        }
        $img=$this->getVillageArea($village_id);
        $single_data['img']=$img['village_floor'];
      //  var_dump($single_data);exit;
        return $single_data;
    }

    /**
     * 查询楼栋区域信息列表
     * @author:zhubaodi
     * @date_time: 2021/10/11 18:19
     */
    public function getVillgeAreaInfo($village_id,$single_id){
        $db_single=new HouseVillageSingle();
        $single_data=$db_single->getOne(['village_id'=>$village_id,'id'=>$single_id,'status'=>1]);
        return $single_data;
    }

    /**
     * 查询楼栋列表
     * @author:zhubaodi
     * @date_time: 2021/10/11 18:19
     */
    public function getSingleList($village_id){
        //,['area','exp', Db::raw('is null')]
        $db_single=new HouseVillageSingle();
        $db_village_coordinate_log=new HouseVillageCoordinateLog();
        $single_list=$db_single->getList([['village_id','=',$village_id],['status','=',1]],'id,single_name,area');
       if (!empty($single_list)){
           $single_list=$single_list->toArray();
           if (!empty($single_list)){
              foreach ($single_list as $k=>$v){
                  if (!empty($v['area'])){
                      $coordinate_log_obj=$db_village_coordinate_log->getOne(['id'=>$v['area'],'village_id'=>$village_id]);
                      $is_good_area=1;
                      if(!empty($coordinate_log_obj) && !$coordinate_log_obj->isEmpty()){
                          $coordinate_log=$coordinate_log_obj->toArray();
                          if($coordinate_log && $coordinate_log['coordinate']){
                              $coordinate_log_data=json_decode($coordinate_log['coordinate'],1);
                              if(empty($coordinate_log_data)){
                                  $is_good_area=0;
                              }
                          }
                      }else{
                          $is_good_area=0;
                      }
                      if($is_good_area){
                          unset($single_list[$k]);
                          continue;
                      }
                  }
                  unset($single_list[$k]['area']);
              }
           }
       }
        return $single_list;
    }

    /**
     * 获取小区绘制区域图片
     * @author:zhubaodi
     * @date_time: 2021/10/18 15:03
     */
    public function getVillageArea($village_id){
        $db_village=new HouseVillageInfo();
        $village_area=$db_village->getOne([['village_id','=',$village_id]],'village_id,village_floor,village_photo');
      //  var_dump($village_id);exit;
        if (!empty($village_area)){
            if (!empty($village_area['village_floor'])){
                $village_area['village_floor']=cfg('site_url').$village_area['village_floor'];
            }
            if (!empty($village_area['village_photo'])){
                $village_area['village_photo']=cfg('site_url').$village_area['village_photo'];
            }
            $village_area['url']=cfg('site_url').'/v20/public/platform/#/village/village.iframe/house_index_config';
        }
        return $village_area;
    }

    /**
     * 查询小区缴费信息
     * @author:zhubaodi
     * @date_time: 2021/10/11 18:19
     */
    public function getVillgePayOrder($village_id){
        $db_order=new HouseVillagePayOrder();
        $dbHouseNewCashierService=new HouseNewCashierService();
        $property_money=$db_order->get_order_limit_list1(['a.village_id'=>$village_id,'a.order_type'=>'property','a.paid'=>1,'a.order_status'=>1],0,[' SUM(`a`.`money` ) AS totalMoney']);
        $park_money=$db_order->get_order_limit_list1(['a.village_id'=>$village_id,'a.order_type'=>'park','a.paid'=>1,'a.order_status'=>1],0,[' SUM(`a`.`money` ) AS totalMoney']);
        $property_money=$property_money->toArray();
        $park_money=$park_money->toArray();

        $price1=0;
        if(isset($property_money[0]['totalMoney'])){
            //$price1+=$property_money[0]['totalMoney'];
        }
        $price2=0;
        if(isset($park_money[0]['totalMoney'])){
            $price2=$park_money[0]['totalMoney'];
        }
        $price1 += $dbHouseNewCashierService->getChargeProjectMoney([
            ['is_paid','=',1],
            ['village_id','=',$village_id],
            ['order_type','=','property']
        ]);
        $price2 += $dbHouseNewCashierService->getChargeProjectMoney([
            ['is_paid','=',1],
            ['village_id','=',$village_id],
            ['order_type','=','park']
        ]);
        $price1=round_number($price1,2);
        $price2=round_number($price2,2);
        $price3=$price1+$price2;
        $property_rate  =   $price3 > 0 ? round_number($price1/$price3,2) : 0;
        $park_rate      =   $price3 > 0 ? round_number($price2/$price3,2) : 0;
        $data['property_money']=$price1;
        $data['park_money']=$price2;
        $data['property_rate']=$property_rate;
        $data['park_rate']=$park_rate;
        $data['park_img']=cfg('site_url').'/static/images/cockpit/parkMoney.png';
        $data['property_img']=cfg('site_url').'/static/images/cockpit/propertyMoney.png';
        $data['pay_img']=cfg('site_url').'/static/images/cockpit/payment.png';
        return $data;
    }


    /**
     * 查询小区缴费信息
     * @author:zhubaodi
     * @date_time: 2021/10/11 18:19
     */
    public function getVillgeRepair($village_id){
        $db_repair=new HouseVillageRepairList();
        $repair_sum=$db_repair->get_repair_list_num(['village_id'=>$village_id]);
        $repair_sum_finish=$db_repair->get_repair_list_num(['village_id'=>$village_id,'status'=>[3,4]]);
        $repair_sum_ing=$db_repair->get_repair_list_num(['village_id'=>$village_id,'status'=>[0,1,2]]);
        $repair_1=$db_repair->get_repair_list_num(['village_id'=>$village_id,'type'=>1]);
        $repair_2=$db_repair->get_repair_list_num(['village_id'=>$village_id,'type'=>2]);
        $repair_3=$db_repair->get_repair_list_num(['village_id'=>$village_id,'type'=>3]);
        $data['repair_sum']=$repair_sum;
        $data['repair_sum_finish']=$repair_sum_finish;
        $data['repair_sum_ing']=$repair_sum_ing;
        $data['repair_1']=$repair_sum > 0 ?round_number(($repair_1/$repair_sum),2) : 0;
        $data['repair_2']=$repair_sum > 0 ? round_number(($repair_2/$repair_sum),2) : 0;
        $data['repair_3']=$repair_sum > 0 ? round_number(($repair_3/$repair_sum),2) : 0;

        $data['repair_img']=cfg('site_url').'/static/images/cockpit/repair.png';
        $data['sum_img']=cfg('site_url').'/static/images/cockpit/repairSum.png';
        $data['finish_img']=cfg('site_url').'/static/images/cockpit/repairFinish.png';
        $data['ing_img']=cfg('site_url').'/static/images/cockpit/repairIng.png';

        return $data;
    }

    /**
     * 获取天使之眼的底图
     * @author:zhubaodi
     * @date_time: 2021/12/20 13:56
     *
     */
    public function getAngeleyeImg($village_id,$type){
        $db_village=new HouseVillageInfo();
        $db_village_device_count=new HouseVillageDeviceCount();
        $db_village_coordinate_log=new HouseVillageCoordinateLog();
        $village_area=$db_village->getOne([['village_id','=',$village_id]],'village_id,village_photo,village_photo1,village_photo2,village_photo3');
        $whereArr=[['village_id','=',$village_id],['address_type','=',$type],['coordinate','<>','']];
        $device_coordinate_list=$db_village_device_count->getLists($whereArr,'*',0);
        $coordinate_log_list=$db_village_coordinate_log->getLists([['village_id','=',$village_id],['address_type','=',$type]],'*',0);
        $village_area1=[];

        if (!empty($village_area)){
            $village_area1['village_id']=$village_area['village_id'];
            if ($type==1){
                if (!empty($village_area['village_photo2'])){
                    $village_area1['village_photo1']=replace_file_domain($village_area['village_photo2']);
                }
            }elseif ($type==2){
                if (!empty($village_area['village_photo1'])){
                    $village_area1['village_photo2']=replace_file_domain($village_area['village_photo1']);
                }
            }elseif ($type==3){
                if (!empty($village_area['village_photo3'])){
                    $village_area1['village_photo3']=replace_file_domain($village_area['village_photo3']);
                }
            }else{
                if (!empty($village_area['village_photo'])){
                    $village_area1['village_photo4']=replace_file_domain($village_area['village_photo']);
                }
            }

            $village_area1['url']=cfg('site_url').'/v20/public/platform/#/village/village.iframe/house_index_config';
        }
        if (!empty($device_coordinate_list)){
            $coordinate_list=$device_coordinate_list->toArray();
        }else{
            $coordinate_list=[];
        }
        if (!empty($coordinate_log_list)){
            $coordinate_log_list=$coordinate_log_list->toArray();
        }else{
            $coordinate_log_list=[];
        }

        if (!empty($coordinate_log_list)&&!empty($coordinate_list)){
            $coordinate_list1=array_merge($coordinate_log_list,$coordinate_list);
        }elseif (!empty($coordinate_log_list)&&empty($coordinate_list)){
            $coordinate_list1=$coordinate_log_list;
        }elseif (empty($coordinate_log_list)&&!empty($coordinate_list)){
            $coordinate_list1=$coordinate_list;
        }else{
            $coordinate_list1=[];
        }
        if (!empty($coordinate_list1)){
            foreach ($coordinate_list1 as &$v){
                $v['coordinate']=json_decode($v['coordinate'],true);
            }
        }
        $data=[];
        $data['village_info']=$village_area1;
        $data['coordinate_list']=$coordinate_list1;
        return $data;
    }

    /**
     * 添加天使之眼定位坐标
     * @author:zhubaodi
     * @date_time: 2021/12/20 13:56
     *
     */
    public function addCoordinate($village_id,$coordinate,$type){
        $db_village_coordinate_log=new HouseVillageCoordinateLog();
        $db_village_device_count=new HouseVillageDeviceCount();
        if(!empty($coordinate)){
            $coordinate=json_encode($coordinate);
        }
        $coordinate_log=$db_village_coordinate_log->getOne(['village_id'=>$village_id,'coordinate'=>$coordinate,'address_type'=>$type]);
        $device_coordinate=$db_village_device_count->getOne(['village_id'=>$village_id,'coordinate'=>$coordinate,'address_type'=>$type]);
        $id=0;
        if (empty($coordinate_log)&&empty($device_coordinate)){
            $data=[];
            $data['village_id']=$village_id;
            $data['coordinate']=$coordinate;
            $data['add_time']=time();
            $data['address_type']=$type;
           $id=$db_village_coordinate_log->addOne($data);
        }
        return $id;
    }

    /**
     * 添加天使之眼定位坐标
     * @author:zhubaodi
     * @date_time: 2021/12/20 13:56
     *
     */
    public function delCoordinate($village_id,$coordinate,$type){
        $db_village_coordinate_log=new HouseVillageCoordinateLog();
        $db_village_device_count=new HouseVillageDeviceCount();
        if(!empty($coordinate)){
            $coordinate=json_encode($coordinate);
        }
        $coordinate_log=$db_village_coordinate_log->getOne(['village_id'=>$village_id,'coordinate'=>$coordinate,'address_type'=>$type]);
        $device_coordinate=$db_village_device_count->getOne(['village_id'=>$village_id,'coordinate'=>$coordinate,'address_type'=>$type]);
        $id=0;
        if (!empty($coordinate_log)){
            $id=$db_village_coordinate_log->del(['village_id'=>$village_id,'coordinate'=>$coordinate,'address_type'=>$type]);
        }elseif (!empty($device_coordinate)){
            $id=$db_village_device_count->saveOne(['village_id'=>$village_id,'coordinate'=>$coordinate],['coordinate'=>'']);
        }
        return $id;
    }

    /**
     * 编辑天使之眼定位坐标
     * @author:zhubaodi
     * @date_time: 2021/12/20 13:56
     *
     */
    public function editCoordinate($village_id,$coordinate,$device_id,$type){
        $db_village_coordinate_log=new HouseVillageCoordinateLog();
        $db_village_device_count=new HouseVillageDeviceCount();
        if(!empty($coordinate)){
            $coordinate=json_encode($coordinate);
        }
        $coordinate_log=$db_village_coordinate_log->getOne(['village_id'=>$village_id,'coordinate'=>$coordinate,'address_type'=>$type]);
        $device_coordinate=$db_village_device_count->getOne(['village_id'=>$village_id,'id'=>$device_id,'address_type'=>$type]);
        $device_coordinate1=$db_village_device_count->getOne(['village_id'=>$village_id,'coordinate'=>$coordinate,'address_type'=>$type]);
        $id=0;
        if (!empty($device_coordinate1)||!empty($coordinate_log)){
            throw new \think\Exception('设备定位坐标不能重复');
        }
        if (!empty($device_coordinate)){
            $id=$db_village_device_count->saveOne(['village_id'=>$village_id,'id'=>$device_id],['coordinate'=>$coordinate,'address_type'=>$type]);
        }else{
            $data=[];
            $data['village_id']=$village_id;
            $data['coordinate']=$coordinate;
            $data['add_time']=time();
            $data['address_type']=$type;
            $id=$db_village_coordinate_log->addOne($data);
        }
        return $id;
    }

    /**
     * 查询设备列表
     * @author:zhubaodi
     * @date_time: 2021/12/20 13:56
     */
    public function getDeviceList($data){
        $db_village_device_count=new HouseVillageDeviceCount();
        $where=[];
        $where['village_id']=$data['village_id'];
        if (!empty($data['devicce_no'])){
            $where['devicce_no']=$data['devicce_no'];
        }
        if (!empty($data['devicce_name'])){
            $where['devicce_name']=$data['devicce_name'];
        }
        if (!empty($data['devicce_type'])){
            $where['devicce_type']=mb_substr($data['devicce_type'],0,2);
        }
        if (!empty($data['type'])&&$data['type']==3){
            $where['device_family']=3;
        }
        $device_coordinate=$db_village_device_count->getLists($where,'*',0);
        if (!empty($device_coordinate)){
            $device_coordinate=$device_coordinate->toArray();

            if (!empty($device_coordinate)){
                foreach ($device_coordinate as $k=>&$v){
                    if (!empty($v['coordinate'])){
                        unset($device_coordinate[$k]);
                        continue;
                    }
                    $v['device_family_txt']=$this->device_family[$v['device_family']];
                    if (in_array($v['device_family'],[1,6])){
                        $v['device_type_txt']=$v['device_type'];
                    }else{
                        $v['device_type_txt']=$v['device_type'].$this->device_family[$v['device_family']];
                    }

                   // $v['coordinate']=json_decode($v['coordinate'],true);
                }
            }
            if (!empty($device_coordinate)){
                $device_coordinate=array_values($device_coordinate);
            }
        }else{
            $device_coordinate=[];
        }
        return ['list'=>$device_coordinate];
    }

    /**
     * 绑定设备
     * @author:zhubaodi
     * @date_time: 2021/12/20 13:56
     */
    public function addDeviceCoordinate($data){
        $db_village_device_count=new HouseVillageDeviceCount();
        $db_village_coordinate_log=new HouseVillageCoordinateLog();
        if (!empty($data['real_device_id'])){
            $device_coordinate=$db_village_device_count->getOne(['village_id'=>$data['village_id'],'id'=>$data['real_device_id']]);
            if (empty($device_coordinate)){
                throw new \think\Exception('设备信息不存在');
            }
        }
        if (!empty($data['device_id'])){
            $device_coordinate1=$db_village_device_count->getOne(['village_id'=>$data['village_id'],'device_id'=>$data['device_id']]);
            if (empty($device_coordinate1)){
                throw new \think\Exception('设备信息不存在');
            }
        }
        $coordinate[]=$data['coordinateX'];
        $coordinate[]=$data['coordinateY'];
        $res=0;
        if (empty($data['device_id']) && !empty($data['real_device_id'])) {
            $res = $db_village_device_count->saveOne(['village_id' => $data['village_id'], 'id' => $data['real_device_id']], ['coordinate' => json_encode($coordinate), 'img' => $data['img'], 'address_type' => $data['type']]);
        } else {
            //编辑
            if (!empty($data['real_device_id']) && !empty($data['id'])) {
                $res = $db_village_device_count->saveOne(['village_id' => $data['village_id'], 'device_id' => $data['device_id'], 'id' => $data['id']], ['coordinate' => '', 'img' => '', 'address_type' => 0]);
            }
            if (!empty($data['real_device_id'])) {
                $res = $db_village_device_count->saveOne(['village_id' => $data['village_id'], 'id' => $data['real_device_id']], ['coordinate' => json_encode($coordinate), 'img' => $data['img'], 'address_type' => $data['type']]);
            }elseif(empty($data['real_device_id']) && !empty($data['id'])){
                $res = $db_village_device_count->saveOne(['village_id' => $data['village_id'], 'id' => $data['id']], ['coordinate' => json_encode($coordinate), 'img' => $data['img'], 'address_type' => $data['type']]);
            }
        }
       if ($res>0){
           $coordinate_log=$db_village_coordinate_log->getOne(['village_id'=>$data['village_id'],'coordinate'=>json_encode($coordinate),'address_type'=>$data['type']]);
           if (!empty($coordinate_log)){
               $db_village_coordinate_log->del(['village_id'=>$data['village_id'],'coordinate'=>json_encode($coordinate),'address_type'=>$data['type']]);
           }
       }
        return $res;
    }

    /**
     * 同步设备
     * @author:zhubaodi
     * @date_time: 2021/12/23 16:50
     */
    public function getDeviceInfo(){
        //同步人脸设备
        $this->get_face_list();
        //蓝牙门禁表
        $this->get_door_list();
        //摄像头
        $this->get_camera_list();
        //停车设备表
        $this->get_park_passage();
        //充电桩设备表
        $this->get_pile_equipment();
        //电瓶车设备表
        $this->get_electric_device();
        //无线AP设备表
        $this->get_ap_device();
        return true;

    }

    /**
     * 同步人脸设备
     * @author:zhubaodi
     * @date_time: 2021/12/23 16:52
     */
    public function get_face_list(){
        $db_house_face_device=new HouseFaceDevice();//人脸门禁表
        $db_village_device_count=new HouseVillageDeviceCount();
        $face_list=$db_house_face_device->getList(['is_del'=>0],'device_id,device_name,device_type,device_sn,village_id','1');
        if (!empty($face_list)){
            $face_list=$face_list->toArray();
            if (!empty($face_list)){
                //设备类型  1 【A1智能门禁机】 2 【A2智能门禁机】3 【A3智能门禁机】5【A5智能门禁机】8【A8智能门禁】22【D2 4G微信远程开门】23【D3智能门禁】 61【A185智能门禁】24【D4智能门禁】88【大华人脸门禁】
                foreach ($face_list as $v1){
                    $face_info=$db_village_device_count->getOne(['village_id'=>$v1['village_id'],'device_id'=>$v1['device_id'],'device_family'=>1]);
                    if (empty($face_info)){
                        $data1=[];
                        $data1['village_id']=$v1['village_id'];
                        $data1['device_name']=$v1['device_name'];
                        $data1['device_id']=$v1['device_id'];
                        $data1['device_no']=$v1['device_sn'];
                        $data1['device_type']=isset($this->face_device_type[$v1['device_type']])?$this->face_device_type[$v1['device_type']]:'未知';
                        $data1['device_family']=1;
                        $data1['add_time']=time();
                        $db_village_device_count->addOne($data1);
                    }
                }
            }
        }

        return true;
    }


    /**
     * 蓝牙门禁表
     * @author:zhubaodi
     * @date_time: 2021/12/23 16:52
     */
    public function get_door_list(){
        $db_house_village_door=new HouseVillageDoor();//蓝牙门禁表
        $db_village_device_count=new HouseVillageDeviceCount();
        $face_list=$db_house_village_door->getLists([]);
        if (!empty($face_list)){
            $face_list=$face_list->toArray();
            if (!empty($face_list)){
               foreach ($face_list as $v1){
                    $face_info=$db_village_device_count->getOne(['village_id'=>$v1['village_id'],'device_id'=>$v1['door_id'],'device_family'=>2]);
                    if (empty($face_info)){
                        $data1=[];
                        $data1['village_id']=$v1['village_id'];
                        $data1['device_name']=$v1['door_name'];
                        $data1['device_id']=$v1['door_id'];
                        $data1['device_no']=$v1['door_device_id'];
                        $data1['device_type']='A1';
                        $data1['device_family']=2;
                        $data1['add_time']=time();
                        $db_village_device_count->addOne($data1);
                    }
                }
            }
        }

        return true;
    }


    /**
     * 摄像头设备表
     * @author:zhubaodi
     * @date_time: 2021/12/23 16:52
     */
    public function get_camera_list(){
        $db_house_camera_device=new HouseCameraDevice();//摄像头设备表
        $db_village_device_count=new HouseVillageDeviceCount();
        $face_list=$db_house_camera_device->getList([],true,0);
        if (!empty($face_list)){
            $face_list=$face_list->toArray();
            if (!empty($face_list)){
                foreach ($face_list as $v1){
                    $face_info=$db_village_device_count->getOne(['village_id'=>$v1['village_id'],'device_id'=>$v1['camera_id'],'device_family'=>3]);
                    if (empty($face_info)){
                        $device_type = 'A1';
                        if (isset($v1['device_brand'])&&'iSecureCenter'==$v1['device_brand']) {
                            $device_type = 'iSecureCenter';
                        }
                        $data1=[];
                        $data1['village_id']=$v1['village_id'];
                        $data1['device_name']=$v1['camera_name'];
                        $data1['device_id']=$v1['camera_id'];
                        $data1['device_no']=$v1['camera_sn'];
                        $data1['device_type']=$device_type;
                        $data1['device_family']=3;
                        $data1['add_time']=time();
                        $db_village_device_count->addOne($data1);
                    }
                }
            }
        }

        return true;
    }

    /**
     * 智能快递柜
     * @author:zhubaodi
     * @date_time: 2021/12/23 16:52
     */
    public function get_intelligence_express(){
        $db_intelligence_express=new IntelligenceExpress();//智能快递柜
        $db_village_device_count=new HouseVillageDeviceCount();
        $face_list=$db_intelligence_express->getList([]);
        if (!empty($face_list)){
            $face_list=$face_list->toArray();
            if (!empty($face_list)){
                foreach ($face_list as $v1){
                    $face_info=$db_village_device_count->getOne(['village_id'=>$v1['village_id'],'device_id'=>$v1['id'],'device_family'=>4]);
                    if (empty($face_info)){
                        $data1=[];
                        $data1['village_id']=$v1['village_id'];
                        $data1['device_name']=$v1['equipment_name'];
                        $data1['device_id']=$v1['id'];
                        $data1['device_no']=$v1['equipment_id'];
                        $data1['device_type']='A1';
                        $data1['device_family']=4;
                        $data1['add_time']=time();
                        $db_village_device_count->addOne($data1);
                    }
                }
            }
        }

        return true;
    }


    /**
     * 停车设备表
     * @author:zhubaodi
     * @date_time: 2021/12/23 16:52
     */
    public function get_park_passage(){
        $db_park_passage=new ParkPassage();//停车设备表
        $db_village_device_count=new HouseVillageDeviceCount();
        $face_list=$db_park_passage->getList([]);
        if (!empty($face_list)){
            $face_list=$face_list->toArray();
            if (!empty($face_list)){
                foreach ($face_list as $v1){
                    $face_info=$db_village_device_count->getOne(['village_id'=>$v1['village_id'],'device_id'=>$v1['id'],'device_family'=>5]);
                    if (empty($face_info)){
                        $data1=[];
                        $data1['village_id']=$v1['village_id'];
                        $data1['device_name']=$v1['passage_name'];
                        $data1['device_id']=$v1['id'];
                        if (in_array($v1['park_sys_type'],['D3','D5'])){
                            $data1['device_no']=$v1['device_number'];
                        }else{
                            $data1['device_no']=$v1['channel_number'];
                        }

                        $data1['device_type']=$v1['park_sys_type'];
                        $data1['device_family']=5;
                        $data1['add_time']=time();
                        $db_village_device_count->addOne($data1);
                    }
                }
            }
        }

        return true;
    }


    /**
     * 充电桩设备表
     * @author:zhubaodi
     * @date_time: 2021/12/23 16:52
     */
    public function get_pile_equipment(){
        $db_house_village_pile_equipment=new HouseVillagePileEquipment();//充电桩设备表
        $db_village_device_count=new HouseVillageDeviceCount();
        $face_list=$db_house_village_pile_equipment->getList(['is_del'=>1]);
        if (!empty($face_list)){
            $face_list=$face_list->toArray();
            if (!empty($face_list)){
                foreach ($face_list as $v1){
                    $face_info=$db_village_device_count->getOne(['village_id'=>$v1['village_id'],'device_id'=>$v1['id'],'device_family'=>6]);
                    if (empty($face_info)){
                        $data1=[];
                        $data1['village_id']=$v1['village_id'];
                        $data1['device_name']=$v1['equipment_name'];
                        $data1['device_id']=$v1['id'];
                        if ($v1['type']==1){
                            $data1['device_no']=$v1['equipment_num'];
                            $data1['device_type']='驴充充定制充电桩';
                        }elseif ($v1['type']==2){
                            $data1['device_no']=$v1['equipment_serial'];
                            $data1['device_type']='A1智能充电桩';
                        }else{
                            $data1['device_no']=$v1['equipment_num'];
                            $data1['device_type']='艾特充智能充电桩';
                        }
                        $data1['device_family']=6;
                        $data1['add_time']=time();
                        $db_village_device_count->addOne($data1);
                    }
                }
            }
        }

        return true;
    }


    /**
     * 无线AP设备表
     * @author:zhubaodi
     * @date_time: 2021/12/23 16:52
     */
    public function get_ap_device(){
        $db_house_village_ac=new HouseVillageAc();//充电桩设备表
        $db_village_device_count=new HouseVillageDeviceCount();
        $face_list=$db_house_village_ac->getList(['is_del'=>1]);
        if (!empty($face_list)){
            $face_list=$face_list->toArray();
            if (!empty($face_list)){
                foreach ($face_list as $v1){
                    $face_info=$db_village_device_count->getOne(['village_id'=>$v1['village_id'],'device_id'=>$v1['id'],'device_family'=>7]);
                    if (empty($face_info)){
                        $data1=[];
                        $data1['village_id']=$v1['village_id'];
                        $data1['device_name']=$v1['ac_name'];
                        $data1['device_id']=$v1['id'];
                        $data1['device_no']=$v1['ac_num'];
                        $data1['device_type']='无线AP';
                        $data1['device_family']=7;
                        $data1['add_time']=time();
                        $db_village_device_count->addOne($data1);
                    }
                }
            }
        }

        return true;
    }

    /**
     * 电瓶车设备表
     * @author:zhubaodi
     * @date_time: 2021/12/23 16:52
     */
    public function get_electric_device(){
        $db_house_electric_device=new HouseElectricDevice();//电瓶车设备表
        $db_village_device_count=new HouseVillageDeviceCount();
        $face_list=$db_house_electric_device->getList([]);
        if (!empty($face_list)){
            $face_list=$face_list->toArray();
            if (!empty($face_list)){
                foreach ($face_list as $v1){
                    $face_info=$db_village_device_count->getOne(['village_id'=>$v1['village_id'],'device_id'=>$v1['id'],'device_family'=>7]);
                    if (empty($face_info)){
                        $data1=[];
                        $data1['village_id']=$v1['village_id'];
                        $data1['device_name']=$v1['device_name'];
                        $data1['device_id']=$v1['id'];
                        $data1['device_no']=$v1['device_id'];
                        $data1['device_type']='A1';
                        $data1['device_family']=7;
                        $data1['add_time']=time();
                        $db_village_device_count->addOne($data1);
                    }
                }
            }
        }

        return true;
    }


    /**
     * 计算点位
     * @author:zhubaodi
     * @date_time: 2022/5/17 18:27
     */
    public function count_coordinate($data){
       // $data['floor_photo_coordinate']=json_decode($data['floor_photo_coordinate']);//原有的left,top坐标
      //  $data['floor_photo_size']=json_decode($data['floor_photo_size']);//原有的图片尺寸
       // $data['floor_photo_coordinate_new']=json_decode($data['floor_photo_coordinate_new']);//现有的left,top坐标
     //   $data['floor_photo_size_new']=json_decode($data['floor_photo_size_new']);//现有的图片尺寸
        if (!is_array($data['coordinate'])){
            $data['coordinate']=json_decode($data['coordinate']);//原有的点位坐标
        }
        $count1=count($data['floor_photo_coordinate']);
        $count2=count($data['floor_photo_size']);
        $count3=count($data['floor_photo_coordinate_new']);
        $count4=count($data['floor_photo_size_new']);
        $count5=count($data['coordinate']);
        $x=0;
        $y=0;
        if ($count1==2&&$count2==2&&$count3==2&&$count4==2&&$count5==2){
            $x=($data['coordinate']['x']-$data['floor_photo_coordinate']['left'])*$data['floor_photo_size_new']['width']/$data['floor_photo_size']['width']+$data['floor_photo_coordinate_new']['left'];
            $y=($data['coordinate']['y']-$data['floor_photo_coordinate']['top'])*$data['floor_photo_size_new']['height']/$data['floor_photo_size']['height']+$data['floor_photo_coordinate_new']['top'];
            if ($x>0){
                $x=(float) round_number($x,4);
            }
            if ($y>0){
                $y=(float) round_number($y,4);
            }

        }
        return ['x'=>$x,'y'=>$y];
    }


    /**
     * 添加绘制区域
     * @author:zhubaodi
     * @date_time: 2022/5/17 19:22
     */
    public function addCoordinatefloor($data){
        $db_village_coordinate_log=new HouseVillageCoordinateLog();
        $db_village_info=new HouseVillageInfo();
        $id=[];
        if (is_array($data['singleArr'])){
            foreach ($data['singleArr'] as $v){
                if (empty($v['area_id'])){
                    $arr_data=[
                        'village_id'=>$data['village_id'],
                        'address_type'=>11,
                        'coordinate'=>json_encode($v['pointArr']),
                        'add_time'=>time()
                    ];
                    $id[]=$db_village_coordinate_log->addOne($arr_data);
                }else{
                    $id[]=$db_village_coordinate_log->saveOne(['id'=>$v['area_id']],['coordinate'=>json_encode($v['pointArr'])]);
                }
            }
        }
	    $this->deleteCacheTag();
        if (!empty($id)){
            $db_village_info->saveOne(['village_id'=>$data['village_id']],['floor_photo_coordinate'=>json_encode($data['floor_photo_coordinate']),'floor_photo_size'=>json_encode($data['floor_photo_size'])]);
        }
        return $id;
    }


    /**
     * 绑定楼顶区域
     * @author:zhubaodi
     * @date_time: 2022/5/17 19:24
     */
    public function addAreaSingle($data){
        $db_village_coordinate_log=new HouseVillageCoordinateLog();
        $db_house_village_single=new HouseVillageSingle();
        $info=$db_village_coordinate_log->getOne(['village_id'=>$data['village_id'],'id'=>$data['area_id']]);
        if (empty($info)){
            throw new \think\Exception("区域信息不存在");
        }
	    $this->deleteCacheTag();
        $village_single_obj=$db_house_village_single->getOne(['village_id'=>$data['village_id'],'id'=>$data['single_id']]);
        if(!empty($village_single_obj) && !$village_single_obj->isEmpty()){
            $village_single=$village_single_obj->toArray();
            if($village_single && !empty($village_single['area_id']) && is_numeric($village_single['area_id'])){
                $delAreaArr=array('village_id'=>$data['village_id'],'area_id'=>$village_single['area_id']);
                $this->delArea($delAreaArr);
            }
        }else{
            throw new \think\Exception("楼栋信息不存在");
        }
        $res=$db_house_village_single->saveOne(['id'=>$data['single_id'],'village_id'=>$data['village_id']],['area'=>$data['area_id'],'area_time'=>time()]);
        return $res;
    }

    /**
     * 删除区域
     * @author:zhubaodi
     * @date_time: 2022/5/17 19:24
     */
    public function delArea($data){
        $db_village_coordinate_log=new HouseVillageCoordinateLog();
        $db_house_village_single=new HouseVillageSingle();
        $info=$db_village_coordinate_log->getOne(['village_id'=>$data['village_id'],'id'=>$data['area_id']]);
        if (empty($info)){
            throw new \think\Exception("区域信息不存在");
        }
		$this->deleteCacheTag();
        $res_area=$db_village_coordinate_log->del(['village_id'=>$data['village_id'],'id'=>$data['area_id']]);
        if ($res_area>0){
            $single_info=$db_house_village_single->getOne(['area'=>$data['area_id'],'village_id'=>$data['village_id']],'id');
           if (!empty($single_info)){
               $res=$db_house_village_single->saveOne(['id'=>$single_info['id'],'village_id'=>$data['village_id']],['area'=>'','area_time'=>time()]);
           }
        }
        return $res_area;
    }


    /**
     * 绘制操作查询区域点位
     * @author:zhubaodi
     * @date_time: 2022/5/18 8:13
     */
    public function getAreaCoordinate($data){
        $db_village_coordinate_log=new HouseVillageCoordinateLog();
        $db_village_info=new HouseVillageInfo();
        $db_house_village_single=new HouseVillageSingle();
        $village_info=$db_village_info->getOne(['village_id'=>$data['village_id']],'floor_photo_coordinate,floor_photo_size');
        $list=$db_village_coordinate_log->getLists(['village_id'=>$data['village_id'],'address_type'=>11],'*',0);
        if (!empty($list)){
            $list=$list->toArray();
        }

        $dataList=[];
        if (!empty($list)){
            foreach ($list as $v){
                $data_arr=[];
                $data_arr['area_id']=$v['id'];
                $data_arr['pointArr']=[];
                $single_info=$db_house_village_single->getOne(['area'=>$v['id'],'village_id'=>$data['village_id']],'id');
                if (!empty($single_info)){
                    $data_arr['single_id']=$single_info['id'];
                }
                $new_coordinate=[];
                if (!empty($v['coordinate'])){
                    $coordinate=json_decode($v['coordinate'],true);
                    if (!empty($coordinate)){
                        foreach ($coordinate as $vv){
                            $arr=[];
                            $arr['floor_photo_coordinate_new']=$data['floor_photo_coordinate'];
                            $arr['floor_photo_size_new']=$data['floor_photo_size'];
                            $arr['floor_photo_coordinate']=json_decode($village_info['floor_photo_coordinate'],true);
                            $arr['floor_photo_size']=json_decode($village_info['floor_photo_size'],true);
                            $arr['coordinate']=$vv;
                          //   print_r($arr);die;
                            $new_coordinate[]=$this->count_coordinate($arr);
                        }
                    }
                    $data_arr['pointArr']=$new_coordinate;
                }
                $dataList[]= $data_arr;
            }
        }
        return $dataList;
    }

    /**
     * 查询已绑定区域点位
     * @author:zhubaodi
     * @date_time: 2022/5/18 8:13
     */
    public function getSingleAreaCoordinate($data){
		$cacheKey = 'village:getSingleAreaCoordinate:'.md5(\json_encode($data));
	    $data1 = Cache::get($cacheKey);
		if (!empty($data1)){
			return  $data1;
		}
        $db_village_coordinate_log=new HouseVillageCoordinateLog();
        $db_village_info=new HouseVillageInfo();
        $village_info=$db_village_info->getOne(['village_id'=>$data['village_id']],'village_floor,floor_photo_coordinate,floor_photo_size');
        $list=$db_village_coordinate_log->getList(['l.village_id'=>$data['village_id'],'l.address_type'=>11],'l.*,s.id as single_id,s.single_name',0);
        if (!empty($list)){
            $list=$list->toArray();
        }
        $dataList=[];
        if (!empty($list)){
            foreach ($list as &$v){
                if (!isset($v['single_id'])||empty($v['single_id'])){
                    continue;
                }
                $data_arr=[];
                $data_arr['area_id']=$v['id'];
                $data_arr['single_id']=$v['single_id'];
                $data_arr['single_name']=$this->traitAutoFixLouDongTips($v['single_name'],true);
                $data_arr['pointArr']=[];
                $new_coordinate=[];
                if (!empty($v['coordinate'])){
                    $coordinate=json_decode($v['coordinate'],true);
                    if (!empty($coordinate)){
                        foreach ($coordinate as $vv){
                            $arr=[];
                            $arr['floor_photo_coordinate_new']=$data['floor_photo_coordinate'];
                            $arr['floor_photo_size_new']=$data['floor_photo_size'];
                            $arr['floor_photo_coordinate']=json_decode($village_info['floor_photo_coordinate'],true);
                            $arr['floor_photo_size']=json_decode($village_info['floor_photo_size'],true);
                            $arr['coordinate']=$vv;
                            $new_coordinate[]=$this->count_coordinate($arr);
                        }
                    }
                    $data_arr['pointArr']=$new_coordinate;
                }
                $dataList[]= $data_arr;
            }
        }
        $data1=[
            'src'=>replace_file_domain($village_info['village_floor']),
            'list'=>$dataList
        ];
	    
		Cache::tag($this->cacheTag)->set($cacheKey,$data1,86400*30);
        return $data1;
    }
}