<?php
/**
 * Created by PhpStorm.
 * Author: lihongshun
 * Date Time: 2023/7/25 9:20
 */

namespace app\community\model\service;
use app\community\model\db\HouseCameraDevice;
use app\community\model\db\HouseDeviceChannel;
use app\community\model\db\HouseVillageCarWorkerRecord;
use app\community\model\db\HouseVillageCarAccessRecord;

use app\community\model\db\HouseVillageParkingCar;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\db\HouseWorker;
use app\community\model\db\ParkOpenLog;
use app\community\model\db\ParkPassage;
use app\community\model\db\HouseVillageParkingGarage;
use app\community\model\db\ParkPlateresultLog;
use app\community\model\service\Car\CarHandleGetInfoService;
use app\community\model\service\HouseVillageParkingService;

use think\facade\Cache;
use think\facade\Db;
use customization\customization;
class HouseVillageCarWorkerRecordService
{
    use customization;
    public function getOneData($whereArr = array())
    {
        $oneCarWorkerRecord = array();
        $houseVillageCarWorkerRecordDb = new HouseVillageCarWorkerRecord();
        if (!empty($whereArr)) {
            $oneCarWorkerRecordObj = $houseVillageCarWorkerRecordDb->getOneData($whereArr);
            if ($oneCarWorkerRecordObj && !$oneCarWorkerRecordObj->isEmpty()) {
                $oneCarWorkerRecord = $oneCarWorkerRecordObj->toArray();
            }
        }
        return $oneCarWorkerRecord;
    }
    public function getOneWorker($where,$field='*')
    {
        $dbHouseWorker = new HouseWorker();
        $data = $dbHouseWorker->get_one($where,$field);
        if (!$data || $data->isEmpty()) {
            $data = [];
        }else{
            $data=$data->toArray();
        }
        return $data;
    }
    public function  handleCurrentLoginSentry($loginType=0,$login_role=0,$adminUser=array(),$xtype=''){
        
        $houseVillageCarWorkerRecordDb = new HouseVillageCarWorkerRecord();
        $nowtime=time();
        $dateStr=date('Y-m-d H:i:s');
        $property_id = isset($adminUser['property_id'])? $adminUser['property_id']:0;
        if($loginType==2){
            //物业登录
            $whereArr=array(['login_role','=',$login_role]);
            $whereArr[]=array('property_id','=',$property_id);
            $user_table_type='house_property';
            $user_id=0;
            $realname='物业总管理员';
            if($login_role==202){
                $realname=$adminUser['realname'] ? $adminUser['realname'] : $adminUser['account'];
                $realname=$realname.'【物业普通管理员】';
                $user_id=$adminUser['id'];
                $whereArr[]=array('user_id','=',$user_id);
                $user_table_type='property_admin';
            }else{
                $whereArr[]=array('user_id','=','0');
            }
            $workerRecord=$houseVillageCarWorkerRecordDb->getOneData($whereArr,'*','last_time desc');
            if($workerRecord && !$workerRecord->isEmpty()){
                if($workerRecord['is_login']<1){
                    //已经下线了 重新登录
                    $addData=array('wid'=>0,'village_id'=>'0','login_role'=>$login_role);
                    $addData['user_id']=$user_id;
                    $addData['user_name']=$realname;
                    $addData['user_table_type']=$user_table_type;
                    $addData['property_id']=$property_id;
                    $addData['is_login']=1;
                    $addData['last_time']=$nowtime;
                    $addData['start_time']=$nowtime;
                    $addData['bak']=$dateStr.'登录上线！';
                    $insert_id=$houseVillageCarWorkerRecordDb->addOneData($addData);
                    
                    $whereSaveArr=array(['login_role','=',$login_role]);
                    $whereSaveArr[]=array('property_id','=',$property_id);
                    $whereSaveArr[]=array('is_login','=','1');
                    $whereSaveArr[]=array('id','<',$insert_id);
                    $start_time=$nowtime-24*3600;
                    $whereSaveArr[]=array('start_time','<',$start_time);
                    
                    $updateArr=array('is_login'=>'0','end_time'=>$nowtime,'bak'=>$dateStr.'被下线！');
                    $houseVillageCarWorkerRecordDb->saveData($whereSaveArr,$updateArr);
                    
                }else{
                    //在线 将之前的线下
                    $whereSaveArr=array(['login_role','=',$login_role]);
                    $whereSaveArr[]=array('property_id','=',$property_id);
                    $whereSaveArr[]=array('is_login','=','1');
                    $whereSaveArr[]=array('user_id','=',$user_id);
                    $whereSaveArr[]=array('id','<',$workerRecord['id']);

                    $updateArr=array('is_login'=>'0','end_time'=>$nowtime,'bak'=>$dateStr.'被下线！');
                    $houseVillageCarWorkerRecordDb->saveData($whereSaveArr,$updateArr);
                }
            }else{
                //从来没登录过
                $addData=array('wid'=>0,'village_id'=>'0','login_role'=>$login_role);
                $addData['user_id']=$user_id;
                $addData['user_name']=$realname;
                $addData['user_table_type']=$user_table_type;
                $addData['property_id']=$property_id;
                $addData['is_login']=1;
                $addData['last_time']=$nowtime;
                $addData['start_time']=$nowtime;
                $addData['bak']=$dateStr.'登录上线！';
                $insert_id=$houseVillageCarWorkerRecordDb->addOneData($addData);
                
                $whereSaveArr=array(['login_role','=',$login_role]);
                $whereSaveArr[]=array('property_id','=',$property_id);
                $whereSaveArr[]=array('is_login','=','1');
                $whereSaveArr[]=array('id','<',$insert_id);
                $start_time=$nowtime-24*3600;
                $whereSaveArr[]=array('start_time','<',$start_time);
                $updateArr=array('is_login'=>'0','end_time'=>$nowtime,'bak'=>$dateStr.'被下线！');
                $houseVillageCarWorkerRecordDb->saveData($whereSaveArr,$updateArr);
                
            }
        }else if($loginType==1){
            //小区工作人员登录
            $village_id = isset($adminUser['village_id'])? $adminUser['village_id']:0;
            $wid= isset($adminUser['wid'])? $adminUser['wid']:0;
            $bakk='被下线！';
            $user_name='';
            $user_table_type='house_admin';
            if($xtype=='wap_manage'){
                $user_id= $wid;
                $bakk='来自移动管理端登录被下线！';
                $user_name=$adminUser['name'] ? $adminUser['name'] : $adminUser['account'];
                $user_table_type='house_worker';
            }else{
                $user_id= isset($adminUser['id'])? $adminUser['id']:0;
                $user_name=$adminUser['realname'] ? $adminUser['realname'] : $adminUser['account'];
            }
            $whereArr=array(['login_role','in',array(5,6,401)]);
            $whereArr[]=array('village_id','=',$village_id);
            $whereArr[]=array('wid','=',$wid);
            //$whereArr[]=array('user_id','=',$user_id);
            $workerRecord=$houseVillageCarWorkerRecordDb->getOneData($whereArr,'*','last_time desc');
            if($workerRecord && !$workerRecord->isEmpty()){
                $workerRecord=$workerRecord->toArray();
                if($workerRecord['is_login']<1){
                    //已经下线了 重新登录
                    $addData=array('wid'=>$wid,'village_id'=>$village_id,'login_role'=>$login_role);
                    $addData['user_id']=$user_id;
                    $addData['user_table_type']=$user_table_type;
                    $addData['user_name']=$user_name;
                    $addData['property_id']=$property_id;
                    $addData['is_login']=1;
                    $addData['last_time']=$nowtime;
                    $addData['start_time']=$nowtime;
                    $addData['bak']=$dateStr.'登录上线！';
                    $insert_id=$houseVillageCarWorkerRecordDb->addOneData($addData);
                    $whereSaveArr=array(['login_role','in',array(5,6,401)]);
                    $whereSaveArr[]=array('village_id','=',$village_id);
                    $whereSaveArr[]=array('is_login','=','1');
                    $whereSaveArr[]=array('id','<',$insert_id);
                    $updateArr=array('is_login'=>'0','end_time'=>$nowtime,'bak'=>$dateStr.$bakk);
                    $houseVillageCarWorkerRecordDb->saveData($whereSaveArr,$updateArr);
                }else{
                    //在线  将之前的 
                    $whereSaveArr=array(['login_role','in',array(5,6,401)]);
                    $whereSaveArr[]=array('village_id','=',$village_id);
                    $whereSaveArr[]=array('is_login','=','1');
                    $whereSaveArr[]=array('id','<',$workerRecord['id']);
                    $updateArr=array('is_login'=>'0','end_time'=>$nowtime,'bak'=>$dateStr.$bakk);
                    $houseVillageCarWorkerRecordDb->saveData($whereSaveArr,$updateArr);
                }
            }else{
                //从来没登录过
                $addData=array('wid'=>$wid,'village_id'=>$village_id,'login_role'=>$login_role);
                $addData['user_id']=$user_id;
                $addData['user_name']=$user_name;
                $addData['user_table_type']=$user_table_type;
                $addData['property_id']=$property_id;
                $addData['is_login']=1;
                $addData['last_time']=$nowtime;
                $addData['start_time']=$nowtime;
                $addData['bak']=$dateStr.'登录上线！';
                $insert_id=$houseVillageCarWorkerRecordDb->addOneData($addData);
                $whereSaveArr=array(['login_role','in',array(5,6,401)]);
                $whereSaveArr[]=array('village_id','=',$village_id);
                $whereSaveArr[]=array('is_login','=','1');
                $whereSaveArr[]=array('id','<',$insert_id);
                $updateArr=array('is_login'=>'0','end_time'=>$nowtime,'bak'=>$dateStr.$bakk);
                $houseVillageCarWorkerRecordDb->saveData($whereSaveArr,$updateArr);
            }
        }
        return  true;
    }

    public function  checkCurrentLoginSentry($loginType=0,$login_role=0,$adminUser=array(),$xtype=''){
        $houseVillageCarWorkerRecordDb = new HouseVillageCarWorkerRecord();
        $property_id = isset($adminUser['property_id'])? $adminUser['property_id']:0;
        $loginSentry=array();
        $fieldStr='*';
        if($loginType==2){
            //物业登录
            $whereArr=array(['login_role','=',$login_role]);
            $whereArr[]=array('property_id','=',$property_id);
            $user_table_type='house_property';
            $user_id=0;
            if($login_role==202){
                $user_id=$adminUser['id'];
                $whereArr[]=array('user_id','=',$user_id);
            }else{
                $whereArr[]=array('user_id','=','0');
            }
            $workerRecord=$houseVillageCarWorkerRecordDb->getOneData($whereArr,$fieldStr,'last_time desc');
            if($workerRecord && !$workerRecord->isEmpty()){
                $workerRecord=$workerRecord->toArray();
                $loginSentry=$workerRecord;
            }
        }else if($loginType==1){
            //小区工作人员登录
            $village_id = isset($adminUser['village_id'])? $adminUser['village_id']:0;
            $wid= isset($adminUser['wid'])? $adminUser['wid']:0;
            if($xtype=='wap_manage'){
                $user_id= $wid;
            }else{
                $user_id= isset($adminUser['id'])? $adminUser['id']:0;
            }
            $whereArr=array(['login_role','in',array(5,6,401)]);
            $whereArr[]=array('village_id','=',$village_id);
            $whereArr[]=array('wid','=',$wid);
            //$whereArr[]=array('user_id','=',$user_id);
            $workerRecord=$houseVillageCarWorkerRecordDb->getOneData($whereArr,$fieldStr,'last_time desc');
            if($workerRecord && !$workerRecord->isEmpty()){
                $workerRecord=$workerRecord->toArray();
                $loginSentry=$workerRecord;
            }
        }
        return $loginSentry;
    }
    public function getCurrentSentryStatistics($current_village_id=0,$loginType=0,$sentry_info=array(),$is_cache=false){
        $houseVillageCarAccessRecord=new HouseVillageCarAccessRecord();
        $houseVillageCarWorkerRecordDb = new HouseVillageCarWorkerRecord();

        if($loginType==1){
            $cacheTagKey='village:cache:'.$current_village_id.':car_sentry_statistics_b'.$sentry_info['id'];
            if($is_cache){
                $data = Cache::get($cacheTagKey);
                if($data){
                    return $data;
                }
            }
            $whereArr=array(['park_id','=',$current_village_id]);
            $whereArr[]=array('accessTime','>=',$sentry_info['start_time']);
            if($sentry_info['end_time']>100){
                $whereArr[]=array('accessTime','<=',$sentry_info['end_time']);
            }
            $whereArr[]=array('accessType','=',2);
            $whereArr[]=array('del_time','<',2);
            
            $totalMoney=$houseVillageCarAccessRecord->getSum($whereArr,'total');
            
            if($totalMoney<=0){
                $sentry_info['total_money']=0;
                $sentry_info['cash_money']=0;
                $sentry_info['online_money']=0;
                $sentry_info['stored_money']=0;
            }else{
                $sentry_info['total_money']=round($totalMoney,2);
                $whereStoredArr=$whereArr;
                $whereStoredArr[]=array('car_type','=','storedCar');
                $stored_money=$houseVillageCarAccessRecord->getSum($whereStoredArr,'total');
                $sentry_info['stored_money']=$stored_money > 0 ? round($stored_money,2):0;
                
                $whereTmpdArr=$whereArr;
                $whereTmpdArr[]=array('accessMode','=',7);
                $cash_money=$houseVillageCarAccessRecord->getSum($whereTmpdArr,'total');
                $sentry_info['cash_money']=$cash_money > 0 ? round($cash_money,2):0;
                $sentry_info['online_money']=$totalMoney-$stored_money-$cash_money;
                $sentry_info['online_money']=$sentry_info['online_money']>0 ? round($sentry_info['online_money'],2):0;
            }
            $whereTmpdArr=$whereArr;
            $whereTmpdArr[]=array('order_id','<>','');
            $total_num=$houseVillageCarAccessRecord->getCount($whereArr);
            $sentry_info['total_num']=$total_num>0 ? $total_num:0;
            $whereTmpdArr[]=array('total','<=',0);
            $free_num=$houseVillageCarAccessRecord->getCount($whereTmpdArr);
            $sentry_info['free_num']=$free_num>0 ? $free_num:0;
            
            $whereSaveArr=array(['id','=',$sentry_info['id']]);
            $saveArr=array('total_money'=>$sentry_info['total_money'],'cash_money'=>$sentry_info['cash_money']);
            $saveArr['online_money']=$sentry_info['online_money'];
            $saveArr['stored_money']=$sentry_info['stored_money'];
            $saveArr['total_num']=$sentry_info['total_num'];
            $saveArr['free_num']=$sentry_info['free_num'];
            $houseVillageCarWorkerRecordDb->saveData($whereSaveArr,$saveArr);
            Cache::set($cacheTagKey,$sentry_info,120);
        }else{
            
        }
        
        return $sentry_info;
    }

    public function getCurrentCarStatistics($current_village_id=0,$xtype=''){
        $returnArr=array('in_park_car'=>0,'position_count'=>0,'tmp_car'=>0,'month_car'=>0,'today_in_car'=>0,'today_out_car'=>0);
        $cacheTagKey='village:cache:'.$current_village_id.':car_sentry_statistics';
        $data = Cache::get($cacheTagKey);
        if($data){
            return $data;
        }
        $where = [];
        $where[] = ['park_id','=',$current_village_id];
        $where[] = ['accessType','=',1];
        $where[] = ['is_out','=',0];
        $where[] = ['del_time','<',1];
        $frontmonthtime=time()-62*24*3600;  //2个大月
        $where[] = ['accessTime','>',$frontmonthtime];
        $houseVillageCarAccessRecord=new HouseVillageCarAccessRecord();
        $count =$houseVillageCarAccessRecord->getCount($where);
        $returnArr['in_park_car']=$count>0 ? $count:0;

        $dbHouseVillageParkingPosition = new HouseVillageParkingPosition();
        $positionWhere=array();
        $positionWhere[] = ['pp.village_id','=',$current_village_id];
        $positionWhere[] = ['pg.status','=',1];
        $positionWhere[] = ['pg.village_id','=',$current_village_id];
        $positionWhere[] = ['pp.position_pattern', '=', 1];
        $positionCount = $dbHouseVillageParkingPosition->getCountss($positionWhere);//总固定车位
        $returnArr['position_count']=$positionCount;

        $whereArr = [];
        $whereArr[] = ['park_id','=',$current_village_id];
        $whereArr[] = ['accessType','=',1];
        $whereArr[] = ['is_out','=',0];
        $frontmonthtime=time()-62*24*3600;
        $whereArr[] = ['accessTime','>',$frontmonthtime];
        $whereArr[] = ['del_time','<',1];
        $whereArr[] = ['position_id','<',1];   //没车位的算临时车
        $houseVillageCarAccessRecord=new HouseVillageCarAccessRecord();
        $count =$houseVillageCarAccessRecord->getCount($whereArr);
        $returnArr['tmp_car']=$count;

        $whereArr = [];
        $whereArr[] = ['park_id','=',$current_village_id];
        $whereArr[] = ['accessType','=',1];
        $whereArr[] = ['accessTime','>',$frontmonthtime];
        $whereArr[] = ['is_out','=',0];
        $whereArr[] = ['del_time','<',1];
        $whereArr[] = ['position_id','>=',1];   //没车位的算临时车
        $houseVillageCarAccessRecord=new HouseVillageCarAccessRecord();
        $count =$houseVillageCarAccessRecord->getCount($whereArr);
        $returnArr['month_car']=$count;
        $today_date=date('Y-m-d');
        if($xtype=='wap_manage'){
            $whereArr = [];
            $whereArr[] = ['park_id','=',$current_village_id];
            $whereArr[] = ['accessType','=',1];
            $whereArr[] = ['del_time','<',1];
            $frontmonthtime=strtotime($today_date);  //今日
            $whereArr[] = ['accessTime','>',$frontmonthtime];
            $houseVillageCarAccessRecord=new HouseVillageCarAccessRecord();
            $count =$houseVillageCarAccessRecord->getCount($whereArr);
            $returnArr['today_in_car']=$count>0 ? $count:0;

            $whereArr = [];
            $whereArr[] = ['park_id','=',$current_village_id];
            $whereArr[] = ['accessType','=',2];
            $whereArr[] = ['order_id','<>',''];
            $whereArr[] = ['del_time','<',1];
            $frontmonthtime=strtotime($today_date);  //今日
            $whereArr[] = ['accessTime','>',$frontmonthtime];
            $houseVillageCarAccessRecord=new HouseVillageCarAccessRecord();
            $count =$houseVillageCarAccessRecord->getCount($whereArr);
            $returnArr['today_out_car']=$count>0 ? $count:0;
        }
        Cache::set($cacheTagKey,$returnArr,600);
        return $returnArr;
    }

    public function getCarAccessRecord($current_village_id=0,$page=1,$limit=10){
        $where = [];
        $where[] = ['park_id','=',$current_village_id];
        $where[] = ['del_time','<',1];
        $where[]=array('order_id','<>','');
        $houseVillageCarAccessRecord=new HouseVillageCarAccessRecord();
        $count =$houseVillageCarAccessRecord->getCount($where);
        $count=$count>0 ? $count:0;
        $returnArr=array('page_size'=>$limit,'list'=>array(),'count'=>$count);
        $list =$houseVillageCarAccessRecord->get_list($where,'record_id,car_number,accessType,accessTime,channel_name,user_name,user_phone,position_id,position_num,garage_id',$page,$limit,'record_id DESC');
        if($list && !$list->isEmpty()){
            $list=$list->toArray();
            foreach ($list as $kk=>$vv){
                $list[$kk]['accessTypeStr']='进';
                $list[$kk]['car_type']='临时车';
                if($vv['position_id']>0){
                    $list[$kk]['car_type']='月租车';
                }
                if($vv['accessType']==2){
                    $list[$kk]['accessTypeStr']='出';
                }
                $list[$kk]['accessTimeStr']=$vv['accessTime']>100 ? date('Y-m-d H:i:s',$vv['accessTime']) :'';
            }
            $returnArr['list']=$list;
        }
        return $returnArr;
    }

    public function getCarParkPassages($current_village_id,$xtype=''){
        $parkPassageDb= new ParkPassage();
        $houseVillageParkingGarage= new HouseVillageParkingGarage();
        $where = [];
        $where[] = ['village_id','=',$current_village_id];
        $where[] = ['status','=',1];
        $where[] = ['park_sys_type','in',array('D3','A11')];
        $where[] = ['device_number','<>',''];
        $fieldStr='id,village_id,passage_name,passage_direction,device_number,channel_number,last_heart_time,passage_area,area_type,garage_id,mac_address,device_protocol,opt_protocol_type,device_ip,device_account,device_pwd,public_v_url';
        $carPassage=$parkPassageDb->getList($where,$fieldStr);
        $returnArr=array('list'=>array(),'in_list'=>array(),'out_list'=>array());
        $garageArr=array();
        $offlinetime=time()-600;
        $site_url=cfg('site_url');
        $site_url=rtrim($site_url,'/');
        $site_url=str_replace('https:','http:',$site_url);
        $in_list=array();
        $out_list=array();
        if($carPassage && !$carPassage->isEmpty()){
            $carPassage=$carPassage->toArray();
            $house_camera_deviceDb = new HouseCameraDevice();
            $house_camera_channelDb = new HouseDeviceChannel();
            foreach ($carPassage as $kk=>$vv){
                $carPassage[$kk]['state_str']='在线';
                $carPassage[$kk]['device_protocol_net']='设备网络：HTTP';
                $carPassage[$kk]['is_close_passage_btn']=0;  //关闭闸
                if($vv['device_protocol']==1 && $vv['opt_protocol_type']==1){
                    $carPassage[$kk]['is_close_passage_btn']=1;
                    $carPassage[$kk]['device_protocol_net']='设备网络：MQTT';
                }
                $device_number = isset($vv['device_number']) && trim($vv['device_number']) ? trim($vv['device_number']) : '';
                if($device_number){
                    $heartTime = (new CarHandleGetInfoService())->getParkPassageHeart($device_number);
                }else{
                    $heartTime=$vv['last_heart_time'];
                }
                if($heartTime<$offlinetime){
                    $carPassage[$kk]['state_str']='离线';
                }
                $garage_id=$vv['garage_id'];
                if ($garage_id<1 && !empty($vv['passage_area'])&& $vv['area_type']==2){
                    $garage_id=$vv['passage_area'];
                }
                $carPassage[$kk]['intranet_v_url']='';
                if($vv['device_ip'] && $vv['device_account']){
                    $carPassage[$kk]['intranet_v_url']=$site_url.'/d3_camera/index.html?ip='.$vv['device_ip'].'&username='.$vv['device_account'].'&password='.$vv['device_pwd'];
                }
                $carPassage[$kk]['public_v_type']='';
                $carPassage[$kk]['public_v_camera_id']=0;
                $carPassage[$kk]['public_v_channelId']='0';
                $carPassage[$kk]['public_v_is_camera']=0;
                $carPassage[$kk]['public_v_show_frame']=false;
                $carPassage[$kk]['is_iframe_v']=0;
                $carPassage[$kk]['gatewayinfo']=array('host'=>'','networkHost'=>'','port'=>"",'protocol'=>'http','rtspPort'=>'','username'=> "admin",'password'=>'');
                if($this->hasCloudIntercom()){
                    $carPassage[$kk]['is_iframe_v']=0;
                    if($vv['public_v_url']){
                        $public_v_arr=explode('.',$vv['public_v_url']);
                        $v_count=count($public_v_arr);
                        $vkey=$v_count-1;
                        $vtype=$public_v_arr[$vkey];
                        $vtype=strtolower($vtype);
                        if($vtype=='m3u8'){
                            $vtype='hls';
                        }
                        $carPassage[$kk]['public_v_type']=$vtype;
                    }
                    if(false && isset($vv['camera_channel_id'])){
                        $carPassage[$kk]['gatewayinfo']=array('host'=>'117.177.233.13','networkHost'=>'117.177.233.13','port'=>"9080",'protocol'=>'http','rtspPort'=>'9554','username'=> "admin",'password'=>'');
                        $carPassage[$kk]['public_v_is_camera']=1;
                        $whereArr=array('channel_id'=>$vv['camera_channel_id']);
                        $camera_channel_obj=$house_camera_channelDb->getOne($whereArr,'*');
                        if($camera_channel_obj && $camera_channel_obj['isDel']<10){
                            $carPassage[$kk]['public_v_camera_id']=$camera_channel_obj['device_id'];
                            $carPassage[$kk]['public_v_channelId']=$camera_channel_obj['channelId'];
                            $public_v_url=$camera_channel_obj['rtsp'];
                            $carPassage[$kk]['public_v_type']='rtsp';
                            if(empty($public_v_url) && $camera_channel_obj['flv']){
                                $public_v_url=$camera_channel_obj['flv'];
                                $carPassage[$kk]['public_v_type']='flv';
                            }
                            if(empty($public_v_url) && $camera_channel_obj['hls']){
                                $public_v_url=$camera_channel_obj['hls'];
                                $carPassage[$kk]['public_v_type']='hls';
                            }
                            if(empty($public_v_url) && $camera_channel_obj['rtmp']){
                                $public_v_url=$camera_channel_obj['rtmp'];
                                $carPassage[$kk]['public_v_type']='rtmp';
                            }
                        }
                        $carPassage[$kk]['public_v_url']=$public_v_url;
                    }
                    //$carPassage[$kk]['public_v_url']='ws://hz.huizhisq.com/:'.$vv['device_port'].'/ws.flv';
                }else if(!empty($vv['mac_address'])){
                    $carPassage[$kk]['is_iframe_v']=1;
                    $mac_address=str_replace(':', '_', $vv['mac_address']);
                    $carPassage[$kk]['public_v_url']='https://' . $mac_address . '.lsqv.com:96/monitorext';
                }
                $carPassage[$kk]['garage_name']='';
                $carPassage[$kk]['garage_position']='';
                if(isset($garageArr[$garage_id]) && $garageArr[$garage_id]){
                    $carPassage[$kk]['garage_name']=$garageArr[$garage_id]['garage_num'];
                    $carPassage[$kk]['garage_position']=$garageArr[$garage_id]['garage_position'];
                }else{
                    $garageInfo=$houseVillageParkingGarage->getOne(['garage_id'=>$garage_id],'garage_id,garage_num,garage_position');
                    if($garageInfo && !$garageInfo->isEmpty()){
                        $garageInfo=$garageInfo->toArray();
                        $garageArr[$garage_id]=$garageInfo;
                        $carPassage[$kk]['garage_name']=$garageInfo['garage_num'];
                        $carPassage[$kk]['garage_position']=$garageInfo['garage_position'];
                    }
                   
                }
                if($xtype=='wap_manage' && $vv['passage_direction']<1){
                     //入口
                    $out_list[]=$carPassage[$kk];
                }else{
                    $in_list[]=$carPassage[$kk];
                }
            }
            if($xtype=='wap_manage'){
                $returnArr['in_list']=$in_list;
                $returnArr['out_list']=$out_list;
            }else{
                $returnArr['list']=$carPassage; 
            }
        }
        return $returnArr;
    }
    
    public function getSentryWatchList($whereArr,$page=1,$limit=10){
        $returnArr=array('page_size'=>$limit,'list'=>array(),'count'=>0);
        $houseVillageCarWorkerRecordDb = new HouseVillageCarWorkerRecord();
        $count=$houseVillageCarWorkerRecordDb->getCount($whereArr);
        $returnArr['count']=$count;
        $listObj=$houseVillageCarWorkerRecordDb->getListData($whereArr,'*','id DESC',$page,$limit);
        if($listObj && !$listObj->isEmpty()){
            $list=$listObj->toArray();
            foreach ($list as $kk=>$vv){
                $list[$kk]['start_time_str']=$vv['start_time'] > 100 ? date('Y-m-d H:i:s',$vv['start_time']):'';
                $list[$kk]['end_time_str']=$vv['end_time'] > 100 ? date('Y-m-d H:i:s',$vv['end_time']):'';
                $list[$kk]['is_login_str']=$vv['is_login'] > 0 ? '在线':'离线';
            }
            $returnArr['list']=$list;
        }
        return $returnArr;
    }
    
    public function getCurrentPassagePlateresult($passage_id=0,$current_village_id=0){
        $parkPlateresultLog=new ParkPlateresultLog();
        $parkPassageDb= new ParkPassage();
        $whereArr=array('id'=>$passage_id);
        $returnArr=array('in_park'=>array('car_number'=>'','in_time'=>'','passage_name'=>'','car_type'=>'临时车'),'car_access_type'=>'','out_park'=>array('car_number'=>'','out_time'=>'','passage_name'=>'','car_type'=>'临时车'));
        $returnArr['device_protocol_net']='设备网络：HTTP';
        $returnArr['is_close_passage_btn']=0;  //关闭闸
        $returnArr['out_park']['park_time']='';
        $returnArr['out_park']['park_time_m']=0;
        $returnArr['out_park']['pay_money']=0;
        $returnArr['out_park']['total_money']=0;
        $returnArr['out_park']['coupon_money']=0;
        $returnArr['out_park']['coupon_id']=0;
        $returnArr['out_park']['park_temp_id']=0;
        $parkPassage=$parkPassageDb->getFind($whereArr,'id,village_id,passage_name,passage_direction,device_number,channel_number,garage_id,area_type,passage_area,device_protocol,opt_protocol_type');
        if($parkPassage && !$parkPassage->isEmpty()){
            $device_number=$parkPassage['device_number'];
            $device_number=strval($device_number);
            $device_number=trim($device_number);
            $returnArr['device_number']=$device_number;
            $whereTmp=array(['channel_id','=',$device_number]);
            $now_time=time();
            $tmpTime=$now_time-180; //3分钟内最新识别
            $whereTmp[]=array('add_time','>=',$tmpTime);
            $plateresultLog=$parkPlateresultLog->get_one($whereTmp,'*','id DESC');
            $db_house_village_parking_car = new HouseVillageParkingCar();
            $garage_id=0;
            if($parkPassage && isset($parkPassage['garage_id']) && $parkPassage['garage_id']>0){
                $garage_id=$parkPassage['garage_id'];
            }else if($parkPassage && isset($parkPassage['passage_area']) && !empty($parkPassage['passage_area']) && $parkPassage['area_type']==2){
                $garage_id=intval($parkPassage['passage_area']);
            }
            if($parkPassage['device_protocol']==1 && $parkPassage['opt_protocol_type']==1){
                $returnArr['is_close_passage_btn']=1;
                $returnArr['device_protocol_net']='设备网络：MQTT';
            }
            if($plateresultLog && !$plateresultLog->isEmpty()){
                $returnArr['plateresult_time']=date('Y-m-d H:i:s',$plateresultLog['add_time']);
                $province = mb_substr($plateresultLog['car_number'], 0, 1);
                $car_no = mb_substr($plateresultLog['car_number'], 1);
                $car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no, 'village_id' => $parkPassage['village_id']]);
                $bind_position_ids = array();
                if ($car_info && !$car_info->isEmpty()) {
                    $car_info = $car_info->toArray();
                    if ($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids'])) {
                        $bind_position_ids = explode(',', $car_info['bind_position_ids']);
                        $bind_position_ids = array_map('intval', $bind_position_ids);
                    }
                    if ($car_info && $car_info['car_position_id']) {
                        $bind_position_ids[] = intval($car_info['car_position_id']);
                    }
                    $bind_position_ids = $bind_position_ids ? array_unique($bind_position_ids):array();
                }else {
                    $car_info=array();
                }
                
                $houseVillageParkingService= new HouseVillageParkingService();
                if($parkPassage['passage_direction']>=1){
                    $returnArr['in_park']['passage_name']=$parkPassage['passage_name'];
                    $returnArr['in_park']['car_number']=$plateresultLog['car_number'];
                    $returnArr['in_park']['in_time']=date('Y-m-d H:i:s',$plateresultLog['add_time']);
                    $returnArr['car_access_type']='in';
                    if ($bind_position_ids) {
                        $db_house_village_parking_position = new HouseVillageParkingPosition();
                        $whereArr=array(['position_id','in',$bind_position_ids],['garage_id','=',$garage_id],['village_id','=',$parkPassage['village_id']]);
                        $returnArr['conditionArr']=$whereArr;
                        $parking_position=$db_house_village_parking_position->getFind($whereArr,'position_id,garage_id,access_record_id,use_start_time,use_car_number,use_car_id','use_car_number asc');
                        if($parking_position && !$parking_position->isEmpty() && (empty($parking_position['use_car_number']) || $parking_position['use_car_number']==$plateresultLog['car_number'])){
                            $returnArr['in_park']['car_type'] = '月租车';
                            $returnArr['out_park']['car_type'] = '月租车';
                        }
                    }
                }else{
                    $houseVillageCarAccessRecord=new HouseVillageCarAccessRecord();
                        //判断是否已出场
                        $order_id='';
                        $accessTime=time()-120; //2分钟内时候有出场记录
                        $access_record_where = [
                            ['car_number', '=', $plateresultLog['car_number']],
                            ['park_id', '=', $parkPassage['village_id']],
                            ['accessTime', '>', $accessTime],
                            ['del_time', '<', 1],
                        ];
                        //查入场记录
                        $carAccessRecordObj=$houseVillageCarAccessRecord->getOne($access_record_where,'*','accessTime DESC');
                        if($carAccessRecordObj && !$carAccessRecordObj->isEmpty() && $carAccessRecordObj['accessType']==2 && !empty($carAccessRecordObj['order_id'])){
                             //2分钟内已经有出场记录了 已出场了
                            $order_id=$carAccessRecordObj['order_id'];
                        }
                        
                    //出场
                    if ($bind_position_ids) {
                        $outRightPosition = $houseVillageParkingService->getCarRightOutPosition($car_info, $parkPassage['village_id'], $garage_id, $plateresultLog['car_number']);
                        if (!empty($outRightPosition)) {
                            if ($outRightPosition['start_time'] <= $now_time && $outRightPosition['end_time'] >= $car_info['end_time']) {
                                $car_info['end_time'] = $outRightPosition['end_time'];
                                if (isset($outRightPosition['temporary_end_time']) && $outRightPosition['temporary_end_time'] > 0) {
                                    $car_info['end_time'] = $outRightPosition['temporary_end_time'];
                                    $car_info['temporary_end_time']=$outRightPosition['temporary_end_time'];
                                }
                            }
                        } else {
                            $car_info['end_time'] = 0;
                        }
                    }
                    if ($car_info && $car_info['end_time'] > $now_time) {
                        $returnArr['in_park']['car_type'] = '月租车';
                        $returnArr['out_park']['car_type'] = '月租车';
                    }
                    $returnArr['car_access_type'] = 'out';
                    
                    $returnArr['in_park']['car_number']=$plateresultLog['car_number'];
                    $returnArr['out_park']['car_number']=$plateresultLog['car_number'];
                    $returnArr['out_park']['passage_name']=$parkPassage['passage_name'];
                    $returnArr['out_park']['out_time']=date('Y-m-d H:i:s',$plateresultLog['add_time']);
                    
                    $accessTime=time()-30*24*3600;

                    if($order_id){
                        $access_record_where = [
                            ['car_number', '=', $plateresultLog['car_number']],
                            ['park_id', '=', $parkPassage['village_id']],
                            ['accessType', '=',1],
                            ['order_id', '=', $order_id],
                            ['del_time', '<', 1],
                        ];
                    }else{
                        $access_record_where = [
                            ['car_number', '=', $plateresultLog['car_number']],
                            ['park_id', '=', $parkPassage['village_id']],
                            ['accessType', '=',1],
                            ['accessTime', '>', $accessTime],
                            ['del_time', '<', 1],
                        ];
                    }
                    //查入场记录
                    $carAccessRecord=$houseVillageCarAccessRecord->getOne($access_record_where,'*','is_out ASC,record_id DESC');
                    if($carAccessRecord && !$carAccessRecord->isEmpty()){
                        $returnArr['in_park']['passage_name']=$carAccessRecord['channel_name'];
                        $returnArr['in_park']['in_time']=date('Y-m-d H:i:s',$carAccessRecord['accessTime']);
                        $out_time=$plateresultLog['add_time'];
                        $park_time= $out_time-$carAccessRecord['accessTime'];
                        $h_time=0;
                        $m_time=0;
                        $park_time_str='';
                        if($park_time<60){
                            $park_time_str=$park_time.'秒';
                            $returnArr['out_park']['park_time_m']=1;
                        }else{
                            $m_all_time=$park_time/60;
                            $m_all_time=ceil($m_all_time);
                            $returnArr['out_park']['park_time_m']=$m_all_time;
                            if($park_time>=3600){
                                $h_time=($park_time/3600);
                                $h_time=floor($h_time);
                                $park_time_str=$h_time.'小时';
                                $park_time=$park_time-$h_time*3600;
                            }
                            if($park_time>=60){
                                $m_time=($park_time/60);
                                $m_time=ceil($m_time);
                                $park_time_str=$park_time_str.$m_time.'分钟';
                            }
                        }
                        $returnArr['out_park']['park_time']=$park_time_str;
                        $pay_data = [
                            'car_number' => $plateresultLog['car_number'],
                            'village_id' => $parkPassage['village_id'],
                            'device_number' => $parkPassage['device_number'],
                            'channel_number' => $parkPassage['channel_number'],
                            'garage_id'=>$garage_id,
                            'car_end_time'=>0,
                            'uid'=>$carAccessRecord['uid'],
                            'in_record_id'=>$carAccessRecord['record_id'],
                        ];
                        /*
                        if($car_info && isset($car_info['end_time'])){
                            $pay_data['car_end_time']=$car_info['end_time'];
                        }
                        */
                        if(empty($order_id)){
                            $pay_money = $houseVillageParkingService->get_temp_pay($pay_data);
                            if($pay_money && isset($pay_money['pay_money'])){
                                $returnArr['out_park']['pay_money']=$pay_money['pay_money'];
                                $returnArr['out_park']['total_money']=$pay_money['total_money'];
                                $returnArr['out_park']['coupon_money']=$pay_money['coupon_money'];
                                $returnArr['out_park']['coupon_id']=$pay_money['coupon_id'];
                                $returnArr['out_park']['park_temp_id']=$pay_money['park_temp_id'];

                            }
                        }
                    }
                }

            }
        }
        return $returnArr;
    }

    public function outSentryStatistics($current_village_id=0,$loginType=0,$sentry_info=array()){
        $houseVillageCarWorkerRecordDb = new HouseVillageCarWorkerRecord();
        $whereArr=array('id'=>$sentry_info['id']);
        $workerRecord=$houseVillageCarWorkerRecordDb->getOneData($whereArr,'*','last_time desc');
        $returnArr=array('id'=>$sentry_info['id']);
        if($workerRecord && !$workerRecord->isEmpty()){
            $nowtime=time();
            $saveArr=array('end_time'=>$nowtime,'is_login'=>'0');
            $houseVillageCarWorkerRecordDb->saveData($whereArr,$saveArr);
            
        }
        return $returnArr;
    }

    public function closeParkPassageCmd($passage_id=0,$village_id=0){
        $parkPassageDb= new ParkPassage();
        $whereArr=array('id'=>$passage_id);
        $returnArr=array('is_support_close'=>0,'tips_msg'=>'操作失败，不支持关闭操作','log_id'=>0);
        if($passage_id<1 ){
            return $returnArr;
        }
        $parkPassage=$parkPassageDb->getFind($whereArr,'id,village_id,passage_name,passage_direction,device_number,channel_number,garage_id,area_type,passage_area,device_protocol,opt_protocol_type');
        if($parkPassage && !$parkPassage->isEmpty()){
            if($parkPassage['device_protocol']==1 && $parkPassage['opt_protocol_type']==1){
                $db_park_open_log=new ParkOpenLog();
                $whereArr=array(['channel_id','=',$parkPassage['device_number']],['open_type','=',4]);
                $log_info_obj = $db_park_open_log->get_one($whereArr,'*');
                if($log_info_obj && !$log_info_obj->isEmpty()){
                    $returnArr['log_id']=$log_info_obj['id'];
                }else{
                    $park_log_data = [];
                    $park_log_data['car_number'] = '';
                    $park_log_data['channel_id'] = $parkPassage['device_number'];
                    $park_log_data['park_type'] = $parkPassage['passage_direction']==1 ? 1:2;
                    $park_log_data['add_time'] = time();
                    $park_log_data['open_type'] = 4;
                    $log_id = $db_park_open_log->add($park_log_data);
                    $returnArr['log_id']=$log_id; 
                }
                $returnArr['is_support_close']=1;
                $returnArr['tips_msg']='操作成功';
            }
        }
        return $returnArr;
    }
}
