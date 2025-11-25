<?php
/**
 * @author : liukezhu
 * @date : 2022/1/10
 */
namespace app\community\model\service\Park;

use app\community\model\db\HouseVillageBindCar;
use app\community\model\db\HouseVillageCarAccessRecord;
use app\community\model\db\HouseVillageParkingCar;
use app\community\model\service\HouseNewParkingService;
use file_handle\FileHandle;
use app\community\model\db\ProcessSubPlan;
use app\community\model\db\HouseVillageParkConfig;
use app\community\model\db\ParkPassage;
use app\community\model\service\HouseVillageParkGarageConfigService;
class D5Service{

    public $plan_interval= 3600 * 2; //两小时 （秒数）
    public $plan_pageNum = 1; //页数
    public $plan_pageSize = 50; //条数
    public $front_days=30;//拉取数据的前**天
    public $end_hour=12;//拉取当前截止小时
    //invoke_v20_service('\app\community\model\service\Park\D5Service/getToken');

    public function file_exists($url)
    {
        if(file_get_contents($url,0,null,0,1)){
            return true;
        } else {
            return false;
        }
    }

    //下载图片
    public function downloadImg($base_img,$is_filename=false) {
        $result = parse_url($base_img);
        $base_img = $result['scheme'].'://'.$result['host'];
        if(isset($result['port'])){
            $base_img.=':'.$result['port'];
        }
        $base_img.=$result['path'];
        if(isset($result['query'])){
            parse_str($result['query'], $query);
            $base_img.='?'.http_build_query($query);
        }
        $file_exists_msg = $this->file_exists($base_img);
        if (!$file_exists_msg) {
            return $base_img;
        }
        $rand_num = date('Ymd');// 换成日期存储
        $dir='/upload/house/d5_img/'.$rand_num.'/';
        $up_dir = request()->server('DOCUMENT_ROOT').$dir;
        $filename=date('YmdHis_').uniqid();
        if (!is_dir($up_dir)) {
            mkdir($up_dir, 0777, true);
        }
        $file_info =  pathinfo($base_img);
        if ($base_img && isset($file_info['extension'])) {
            if ($is_filename) {
                $type = 'jpg';
                $file_url = $filename.'.'.$type;
            } elseif ($file_info['basename']) {
                $file_url = $filename.'.'.$file_info['extension'];
            } elseif ($file_info['extension']) {
                $file_url = $filename.'.'.$file_info['extension'];
            } else {
                $type = 'jpg';
                $file_url = $filename.'.'.$type;
            }
            $new_file=$up_dir.$file_url;
            # 远程文件处理
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL,$base_img);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);  # 过期时间
            //当请求https的数据时，会要求证书，加上下面这两个参数，规避ssl的证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            $img = curl_exec($ch);
            curl_close($ch);
            $fp2 = @fopen($new_file ,'a');
            fwrite($fp2,$img);
            fclose($fp2);
            unset($img,$base_img);
            $img_path = trim($new_file,'.');
            //判断是否需要上传至云存储
            $file_handle = new FileHandle();
            $file_handle_url = trim($new_file,'./');
            $file_handle->download($file_handle_url);
            return $dir.$file_url;;
        }
        else {
            return false;
        }
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

    //todo 替换图片路径
    public function imgReplace($config,$url){
        $str=$url;
        if(false !== strpos($url,'/api/')){
            $c0=strpos($url,'/api/');
            $c1=strrpos($url, ':');
            $c2=strrpos($config['base_url'], ':');
            if($c0 > $c1){
                $str=substr_replace($url,substr($config['base_url'],0,$c2), 0, $c1);
            }
        }
        return $str;
    }

    /**
     * 设备--进出场组装数据 统一返回
     * @author: liukezhu
     * @date : 2022/1/12
     * @param $d5
     * @param $park
     * @return array
     */
    public function returnParkData($d5,$park){
        $data=[];
        if(isset($park['data']['pageData']) && !empty($park['data']['pageData'])){
            foreach ($park['data']['pageData'] as &$v){
                $img_list=[];
                if(isset($v['inVoucherCode']) && !empty($v['inVoucherCode'])){
                    $img=$d5->getParkImg($v['inVoucherCode']);
                    if($img['success']){
                        $img_list=$img['data'];
                    }
                }
                $v['car_img']=$img_list;
             /*   $person_list=[];
                if(isset($v['inAutoPlate']) && !empty($v['inAutoPlate'])){
                    $person=$d5->getPerson(['carNum'=>$v['inAutoPlate']]);
                    if($person['success']){
                        $person_list=$person['data'];
                    }
                }
                $v['person']=$person_list;*/
            }
            unset($v);
            $data=$park['data'];
        }
        return $data;
    }

    /**
     * 设备--查询进场数据   currentPage:当前请求的页数  totalPage:查询结果页数
     * @author: liukezhu
     * @date : 2022/1/12
     * @param $config
     * @param $start
     * @param $end
     * @param int $pageNum
     * @param int $pageSize
     * @param string $carNum
     * @return array
     */
    public function getParkInList($config,$start,$end,$pageNum=1,$pageSize=20,$carNum=''){
        $param=[
            'queryTimeBegin'=>$start,
            'queryTimeEnd'=>$end,
            'carNum'=>$carNum,
            'pageNum'=>(int)$pageNum,
            'pageSize'=>(int)$pageSize
        ];
        $d5=new D5SdkService($config);
        $data=$d5->getParkIn($param);
        if(!$data['success']){
            return ['success' => false, 'data' =>$data['errMsg']];
        }
        return ['success' => true, 'data' =>self::returnParkData($d5,$data)];
    }

    /**
     * 设备--查询出场数据   currentPage:当前请求的页数  totalPage:查询结果页数
     * @author: liukezhu
     * @date : 2022/1/12
     * @param $config
     * @param $start
     * @param $end
     * @param int $pageNum
     * @param int $pageSize
     * @param string $carNum
     * @return array
     */
    public function getParkOutList($config,$start,$end,$pageNum=1,$pageSize=20,$carNum=''){
        $param=[
            'queryTimeBegin'=>$start,
            'queryTimeEnd'=>$end,
            'carNum'=>$carNum,
            'pageNum'=>(int)$pageNum,
            'pageSize'=>(int)$pageSize
        ];
        $d5=new D5SdkService($config);
        $data=$d5->getParkOut($param);
        if(!$data['success']){
            return ['success' => false, 'data' =>$data['errMsg']];
        }
        return ['success' => true, 'data' =>self::returnParkData($d5,$data)];
    }

    /**
     * 设备--查询所有设备
     * @author: liukezhu
     * @date : 2022/1/11
     * @param $config
     * @return array
     */
    public function getChannelList($config){
        return (new D5SdkService($config))->getChannel();
    }

    /**
     * 设备--删除车辆
     * @author: liukezhu
     * @date : 2022/1/17
     * @param $config
     * @param $car_id
     * @return array
     */
    public function parkCarDel($config,$car_id){
        return (new D5SdkService($config))->monthCarDel('carId_'.$car_id);
    }

    /**
     * 获取车牌对应用户
     * @author: liukezhu
     * @date : 2022/1/13
     * @param $village_id
     * @param $car_number
     * @return mixed
     */
    public function getCarUser($village_id,$car_number){
        $where=[];
        $where[] = ['c.village_id', '=', $village_id];
        $where[] = ['c.car_number', '=', $car_number];
        $data=(new HouseVillageParkingCar())->getLists($where,'c.car_id,c.car_number,u.uid,u.pigcms_id,u.name,u.phone');
        if(!empty($data)){
            $data=$data->toArray();
        }
        return $data;
    }

    /**
     * 查询停车设备数据
     * @author: liukezhu
     * @date : 2022/1/13
     * @param $village_id
     * @param $device_number
     * @return mixed
     */
    public function getParkPassage($village_id,$device_number){
        $where=[];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['park_sys_type', '=', 'D5'];
        $where[] = ['device_number', '=', $device_number];
        $data=(new ParkPassage())->getFind($where,'id,passage_name');
        if(!empty($data)){
            $data=$data->toArray();
        }
        return $data;
    }


   //-------------- todo 处理下发到停车设备数据---start

    /**
     * D5设备添加车辆
     * @author: liukezhu
     * @date : 2022/1/15
     * @param $village_id
     * @param $car_id
     * @param $start_time
     * @param $end_time
     * @param string $type
     * @return array
     */
    public function D5AddCar($village_id,$car_id,$start_time,$end_time,$type='mobile',$garage_id=0){
        $where=array();
        $where[] = ['bc.village_id','=',$village_id];
        $where[] = ['bc.car_id','=',$car_id];
        $where[] = ['bc.user_id', '>', 0];
        $field='p.position_num,p.garage_id,b.pigcms_id,b.name,c.car_number,c.province';
        $info=(new HouseVillageBindCar())->getUserBind($where,$field);
        if(empty($info)){
            fdump_api(['该车辆关联的用户不存在--line:'.__LINE__,$where,$info],'d5_park/device_addMonthCar',1);
            return ['success'=>false,'errMsg'=>'该车辆关联的用户不存在','data'=>[]];
        }
        if($garage_id<1 && isset($info['garage_id']) && $info['garage_id']>0){
            $garage_id=$info['garage_id'];
        }
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$village_id);
        fdump_api(['添加车辆同步到设备上--line:'.__LINE__,$village_id,$car_id,$start_time,$end_time,$type,$park_config],'d5_park/device_addMonthCar',1);
        if(empty($park_config) || $park_config['park_sys_type'] != 'D5' || $park_config['park_versions'] != '2'){
            fdump_api(['D5智慧停车配置项未生效--line:'.__LINE__,$village_id,$car_id,$start_time,$end_time,$type,$park_config],'d5_park/device_addMonthCar',1);
            return ['success'=>false,'errMsg'=>'D5智慧停车配置项未生效','data'=>[]];
        }
        
        $param=[
            'village_id'=> $village_id,
            'config'    =>[
                'base_url' => $park_config['d5_url'],
                'userName' => $park_config['d5_name'],
                'passWord' => $park_config['d5_pass']
            ],
            'parkingNo' => !empty($info['position_num']) ? $info['position_num'] : '',
            'beginTime' => $start_time,
            'endTime'   => $end_time,
            'id'        => $car_id,
            'plateNo'   => $this->checkCarNumber($info['province'],$info['car_number']),
            'owner'     => $info['name'],
            'userId'    => $info['pigcms_id']
        ];
        //来源后台操作
        return $this->addMonthCar($param,$type);
    }

    /**
     * 月租车同步到设备上
     * @author: liukezhu
     * @date : 2022/1/15
     * @param $param
     * @param $type
     * @return array
     */
    public function addMonthCar($param,$type='mobile'){
        if($param['beginTime'] > $param['endTime']){
            $param['beginTime'] = $param['endTime'];
        }
        $deviceIds=[];
        $device=self::getChannelList($param['config']);
        if(!$device['success']){
            fdump_api(['D5设备集合为空--line:'.__LINE__,$param,$type,$device],'d5_park/device_addMonthCar_error',1);
        }
        else{
            if(!empty($device['data'])){
                $deviceIds = array_column($device['data'], 'channelId');
            }
        }
        $cars=[
            [
                'plateNo'   =>  $param['plateNo'], //车牌号
                'owner'     =>  $param['owner'], //车主姓名
                'userId'    =>  (string)$param['userId'], //用户ID
                'deviceIds' =>  $deviceIds //设备集合(要办理哪些车闸权限,从 2.3.1 方法获取道闸信息)
            ]
        ];
        $data=[
            'parkingNo'=>  $param['parkingNo'], //车位编号
            'beginTime'=>  intval(floatval($param['beginTime']) * 1000), //起始日间(13 位时间戳)
            'endTime'  =>  intval(floatval($param['endTime']) * 1000),//截止日间(13 位时间戳)
            'id'       =>  'carId_'.$param['id'],       //记录 ID[作唯一标识用]，(修改删除时需要传入一样的 ID)
            'cars'     =>   $cars
        ];
        $d5=new D5SdkService($param['config']);
        $result= $d5->monthCarAdd($data);
        fdump_api(['设备返回--line:'.__LINE__,$result],'d5_park/device_addMonthCar',1);
        return $result;
    }

    //-------------------------------------end

    //todo 主计划任务 查询所有小区开启D5停车设备
    public function villageD5Park(){
        $where[] = ['park_sys_type','=','D5'];
        $where[] = ['park_versions','=',2];
        $where[] = ['d5_url','<>',''];
        $where[] = ['d5_name','<>',''];
        $where[] = ['d5_pass','<>',''];
        $time=time();
        $today=strtotime(date('Y-m-d 00:00:00',strtotime("-".$this->front_days." day")));
        $end_time=$time - ($this->end_hour * 60*60);
        $HouseVillageParkConfig=new HouseVillageParkConfig();
        for ($x=1; $x<=50; $x++) {
            $list = $HouseVillageParkConfig->get_list($where,'id,village_id,d5_url,d5_name,d5_pass,d5_in_plan as in_park,d5_out_plan as out_park',$x,50);
            if(!empty($list)){
                $list=$list->toArray();
            }
            fdump_api(['line:'.__LINE__,$list],'d5_park/plan_d5_park',1);
            if(empty($list)){
               break;
            }
            $data=[];
            foreach ($list as $v){
                $add_time=$this->plan_interval;
                $start = date('Y-m-d H:i:s',$today);
                $end   = date('Y-m-d H:i:s',($today + $add_time));
                $pageNum=$this->plan_pageNum;
                $plan_type=['in_park','out_park'];
                foreach ($plan_type as $vv){
                    if(!empty($v[$vv])){
                        $d5_plan =explode(',',$v[$vv]);
                        if(is_array($d5_plan) && isset($d5_plan[1])){
                            $start   = $d5_plan[0];
                            $end     = $d5_plan[1];
                            $pageNum = $d5_plan[2];
                        }
                    }
                    if(strtotime($end) > $end_time){
                        fdump_api(['大于截止时间==line:'.__LINE__,strtotime($end),$end_time],'d5_park/plan_d5_park',1);
                        continue;
                    }
                    $data[]=[
                        'param'=>serialize([
                            'village_id'=>  $v['village_id'],
                            'config'    =>  [
                                'base_url' => $v['d5_url'],
                                'userName' => $v['d5_name'],
                                'passWord' => $v['d5_pass']
                            ],
                            'type'      =>  $vv, // in_park:进场  out_park:离场
                            'start'     =>  $start,
                            'end'       =>  $end,
                            'pageNum'   =>  $pageNum,
                            'pageSize'  =>  $this->plan_pageSize
                        ]),
                        'plan_time'     =>  -110,
                        'space_time'    =>  0,
                        'add_time'      =>  $time,
                        'file'          =>  'sub_d5_park',
                        'time_type'     =>  1,
                        'unique_id'     =>  $v['village_id'].'_d5_'.$vv.'_'.$time.'_'.uniqid(),
                        'rand_number'   =>  mt_rand(1, max(cfg('sub_process_num'), 3)),
                    ];
                }
            }
            (new ProcessSubPlan())->addAll($data);
        }
        return true;
    }

    //todo 生成子计划任务
    public function addParkTask($village_id,$config,$start,$end,$data,$type,$garage_id=0){
        fdump_api(['生成计划任务-进来了,line:'.__LINE__,$village_id,$config,$start,$end,$data,$type,(!empty($data) && isset($data['currentPage']) && isset($data['totalPage']) && (count($data['currentPage']) < $data['totalPage']))],'d5_park/addParkTask',1);
        $rr=[];
        $time=time();
        $park_type=[
            'in_park'=>'d5_in_plan',
            'out_park'=>'d5_out_plan'
        ];
        $HouseVillageParkConfig=new HouseVillageParkConfig();
        /*
        $where=[];
        $where[] = ['park_sys_type','=','D5'];
        $where[] = ['park_versions','=',2];
        $where[] = ['village_id','=',$village_id];
        $info=$HouseVillageParkConfig->getFind($where,'id,d5_in_plan as in_park,d5_out_plan as out_park');
        */
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $whereArr=array(['village_id','=',$village_id], ['park_sys_type','=','D5'],['park_versions','=',2]);
        if($garage_id>0){
            $whereArr[]=['garage_id','=',$garage_id];
        }
        $infos=$houseVillageParkGarageConfigService->getListData($whereArr);
        
        if(empty($infos)){
            fdump_api(['config-error,line:'.__LINE__,$type,$infos],'d5_park/park_config_error',1);
            return true;
        }
        foreach ($infos as $info) {
            $info['in_park']=$info['d5_in_plan'];
            $info['out_park']=$info['d5_out_plan'];
            $garage_id=0;
            if(isset($info['garage_id']) && $info['garage_id']>0){
                $garage_id=$info['garage_id'];
            }
            if (!empty($data) && isset($data['currentPage']) && isset($data['totalPage']) && (count($data['currentPage']) < $data['totalPage'])) { //当前页数小于总分页，开启分页查询，查询时间不变
                $arr = array();
                $pageNum = intval($data['currentPage']) + 1;
                $arr['param'] = serialize(array(
                    'village_id' => $village_id,
                    'config' => $config,
                    'type' => $type, // in_park:进场  out_park:离场
                    'start' => $start,
                    'end' => $end,
                    'pageNum' => $pageNum,
                    'garage_id'=>$garage_id,
                    'pageSize' => $data['pageSize']
                ));
                $arr['plan_time'] = -100;
                $arr['space_time'] = 0;
                $arr['add_time'] = $time;
                $arr['file'] = 'sub_d5_park';
                $arr['time_type'] = 1;
                $arr['unique_id'] = $village_id . '_d5_' . $type . '_' . $time . '_' . uniqid();
                $arr['rand_number'] = mt_rand(1, max(cfg('sub_process_num'), 3));
                (new ProcessSubPlan())->add($arr);
                $value = explode(',', $info[$type]);
                if (is_array($value) && !empty($value)) {
                    $rr[$park_type[$type]] = $value[0] . ',' . $value[1] . ',' . $pageNum;
                }
                fdump_api(['分页数据,line:' . __LINE__, $data, $pageNum, $arr], 'd5_park/page_data', 1);
            } else {   //该查询时间内所有数据查询完毕，开启下个时间段的数据拉取
                if (isset($info[$type])) {
                    if (empty($info[$type])) {
                        $value = $end;
                    } else {
                        $value = explode(',', $info[$type])[1];
                    }
                    $end_times = $time - ($this->end_hour * 60 * 60);
                    $start_time = strtotime($value);
                    if ($start_time >= $time) { //开始查询时间不可大于当前时间
                        return true;
                    }
                    $end_time = $start_time + $this->plan_interval;
                    if ($end_time > $end_times) {
                        return true;
                    }
                    $d = $value . ',' . (date('Y-m-d H:i:s', $end_time)) . ',1';
                    $rr[$park_type[$type]] = $d;
                }
            }
            if (!empty($rr)) {
                if( $garage_id>0){
                    $whereArr=array(['id','=',$info['id']],['village_id','=',$village_id], ['garage_id','=',$garage_id]);
                    $houseVillageParkGarageConfigService->saveVillageParkGarageConfig($whereArr,$rr);
                }else{
                    $whereArr=array(['id','=',$info['id']],['village_id','=',$village_id]);
                    $rel = $HouseVillageParkConfig->save_one($whereArr, $rr);
                }
                fdump_api(['生成计划任务-进来了,line:' . __LINE__, $type, $rel, $rr, $info], 'd5_park/park_config', 1);
            }
        }
        return true;
    }

    //todo 拉取进出场数据
    public function pullCarPark($type,$village_id,$config,$start,$end,$pageNum=1,$pageSize=20,$garage_id=0){
        fdump_api(['拉取进出场数据,line:'.__LINE__,$type,$village_id,$config,$start,$end,$pageNum,$pageSize],'d5_park/pullCarPark',1);
        if($type == 'in_park'){
            $accessType=1;
            $list=self::getParkInList($config,$start,$end,$pageNum,$pageSize);
        }
        elseif ($type == 'out_park'){
            $accessType=2;
            $list=self::getParkOutList($config,$start,$end,$pageNum,$pageSize);
        }
        else{
            return true;
        }
        self::addParkTask($village_id,$config,$start,$end,$list['data'],$type,$garage_id);
        if(empty($list) || empty($list['data'])){
            fdump_api(['进出场数据-data为空,line:'.__LINE__,$type,$village_id,$config,$start,$end,$pageNum,$pageSize],'d5_park/carParkInTask',1);
            return true;
        }
        $time=time();
        $HouseVillageCarAccessRecord=new HouseVillageCarAccessRecord();
        foreach ($list['data']['pageData'] as $v){
            if($accessType == 1){//进场
                $is_out=0;
                $accessTime=$v['inTime'];
                $channel_number=$v['inPassagewayRid'];
                $accessImage=(isset($v['car_img']['inImage2'])) ? ($this->imgReplace($config,$v['car_img']['inImage2'])) : '';
                $accessBigImage=(isset($v['car_img']['inImage'])) ? ($this->imgReplace($config,$v['car_img']['inImage'])) : '';
            }
            else{ //出场
                $is_out=1;
                $accessTime=$v['outTime'];
                $channel_number=$v['outPassagewayRid'];
                $accessImage=(isset($v['car_img']['outImage2'])) ? ($this->imgReplace($config,$v['car_img']['outImage2'])) : '';
                $accessBigImage=(isset($v['car_img']['outImage'])) ? ($this->imgReplace($config,$v['car_img']['outImage'])) : '';
            }
            if(!isset($v['id']) || empty($v['id'])){
                continue;
            }
            $third_id=$HouseVillageCarAccessRecord->getOne(['third_id'=>$v['id']],'third_id');
            if(isset($third_id['third_id']) || !empty($third_id['third_id'])){
                continue;
            }
            $service_house_new_parking_service=new HouseNewParkingService();
            $inParkInfo=$service_house_new_parking_service->getInParkInfo($v['inAutoPlate'],$village_id);
            if (!empty($inParkInfo)){
                $order_id=$inParkInfo['order_id'];
            }else{
                $order_id=$time.$v['id'];
            }
            $data=[
                'third_id'=>$v['id'],
                'business_type'=>0,
                'business_id'=>$village_id,
                'car_number'=>$v['inAutoPlate'],
                'accessType'=>$accessType,
                'accessTime'=>strtotime($accessTime),
                'accessImage'=>$accessImage,
                'accessBigImage'=>$accessBigImage,
                'channel_id'=>'',
                'channel_number'=>$channel_number,
                'channel_name'=>'',
                'accessMode'=>5,
                'park_sys_type'=>'D5',
                'park_id'=>$village_id,
                'is_out'=>$is_out,
                'order_id'=>$order_id,
                'park_name'=>'',
                'uid'=>'',
                'car_id'=>'',
                'bind_id'=>'',
                'user_name'=>'',
                'user_phone'=>'',
                'totalMoney'=>$v['payableAmount'],
                'total'=>$v['paidAmount'],
                'deductionTotal'=>$v['discountAmount'],
                'update_time'=>$time,
                'park_time'=>$v['parkingTime'],
                'car_type'=>'monthCar'
            ];
            $user=self::getCarUser($village_id,$v['inAutoPlate']);
            if($user){
                $data['uid']=$user['uid'];
                $data['car_id']=$user['car_id'];
                $data['bind_id']=$user['pigcms_id'];
                $data['user_name']=$user['name'];
                $data['user_phone']=$user['phone'];
            }
            $park=self::getParkPassage($village_id,$channel_number);
            if($park){
                $data['channel_id']=$park['id'];
                $data['channel_name']=$user['device_number'];
            }
            $result=$HouseVillageCarAccessRecord->addOne($data);
            if($result){
                if($accessType != 1&&!empty($inParkInfo)) {//出场
                    $HouseVillageCarAccessRecord->saveOne(['record_id'=>$inParkInfo['record_id']],['is_out'=>1]);
                }
                $rr=[
                    'param'=>serialize([
                        'type'=>'download_pic',
                        'village_id'=>  $village_id,
                        'record_id'=>  $result,
                        'data'=>array(
                            'accessImage'=>$accessImage,'accessBigImage'=>$accessBigImage
                        )
                    ]),
                    'plan_time'     =>  -100,
                    'space_time'    =>  0,
                    'add_time'      =>  $time,
                    'file'          =>  'sub_d5_park',
                    'time_type'     =>  1,
                    'unique_id'     =>  'd5_img_'.$result.'_'.$time.'_'.uniqid(),
                    'rand_number'   =>  mt_rand(1, max(cfg('sub_process_num'), 3)),
                ];
                (new ProcessSubPlan())->add($rr);
            }

        }

    }

    //todo 下载抓拍同步到数据中
    public function synPicRecord($village_id,$record_id,$data){
        fdump_api(['下载抓拍同步到数据中,line:'.__LINE__,$village_id,$record_id,$data],'d5_park/synPicRecord',1);
        $where=[];
        $where[] = ['record_id', '=', $record_id];
        $where[] = ['business_type', '=', 0];
        $where[] = ['business_id', '=', $village_id];
        if(!empty($data)){
            $accessImage=self::downloadImg($data['accessImage']);
            $accessBigImage=self::downloadImg($data['accessBigImage']);
            $data['accessImage']=$accessImage ? $accessImage : '';
            $data['accessBigImage']=$accessBigImage ? $accessBigImage : '';
            (new HouseVillageCarAccessRecord())->saveOne($where,$data);
        }
        return true;
    }
}