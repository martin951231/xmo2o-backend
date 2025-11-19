<?php

namespace app\community\model\service;

use app\community\model\db\Device\GarbageCollectionMachineList;
use app\community\model\db\HouseFaceDevice;
use app\community\model\db\HouseMaintenanLog;
use app\community\model\db\HouseUserLog;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillagePileEquipment;
use app\community\model\db\HouseVillagePilePayOrder;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\User;
use think\facade\Cache;

class HardwareServe{


    protected $time;
    protected $HouseFaceDevice;
    protected $HouseUserLog;
    protected $HouseVillagePileEquipment;
    protected $HouseVillagePilePayOrder;
    protected $HouseVillageService;
    protected $HouseUserLogService;
    protected $HouseMaintenanLog;
    protected $HouseVillagePileEquipmentService;
    protected $device_status;
    protected $GarbageCollectionMachineList;

    public function __construct()
    {
        $this->time=time();
        $this->HouseFaceDevice=new HouseFaceDevice();
        $this->HouseUserLog=new HouseUserLog();
        $this->HouseVillagePileEquipment=new HouseVillagePileEquipment();
        $this->HouseVillagePilePayOrder=new HouseVillagePilePayOrder();
        $this->HouseVillageService = new HouseVillageService();
        $this->HouseUserLogService = new HouseUserLogService();
        $this->HouseMaintenanLog = new HouseMaintenanLog();
        $this->HouseVillagePileEquipmentService = new HouseVillagePileEquipmentService();
        $this->GarbageCollectionMachineList=new GarbageCollectionMachineList();
        $this->device_status=[
            1=>'在线',
            2=>'离线'
        ];
    }

    //todo 获取首页参数
    public function getIndex(){
        $data['pile_device']=[
            array(
                'title'=>'今日消费金额',
                'color'=>'blue'
            ),
            array(
                'title'=>'昨日消费金额',
                'color'=>'green'
            ),
            array(
                'title'=>'累计消费金额',
                'color'=>'yellow'
            )
        ];
        $data['open_door']=[
            array(
                'key'=>1,
                'value'=>'今日总开门'
            ),
            array(
                'key'=>2,
                'value'=>'刷脸开门数'
            ),
            array(
                'key'=>3,
                'value'=>'APP远程开门数'
            )
        ];
        $data['current_time']=time();
        return $data;
    }

    /**
     * 门禁设备
     * @author: liukezhu
     * @date : 2021/10/25
     *
     */
    public function getFaceDevice(){
        $data=[];
        //门禁总数
        $where[]=[ 'd.is_del','=',0];
        $where[]=[ 'v.status','=',1];
        $total=$this->HouseFaceDevice->getCounts($where);
        //在线设备
        $where=[];
        $where[]=[ 'd.is_del','=',0];
        $where[]=[ 'd.device_status','=',1];
        $where[]=[ 'v.status','=',1];
        $online=$this->HouseFaceDevice->getCounts($where);
        //离线设备
        $where=[];
        $where[]=[ 'd.is_del','=',0];
        $where[]=[ 'd.device_status','=',2];
        $where[]=[ 'v.status','=',1];
        $offline=$this->HouseFaceDevice->getCounts($where);
        $online_ratio=0;
        if($online > 0 && $total > 0){
            $online_ratio=get_number_format($online/$total);
        }
        $offline_ratio=0;
        if($offline > 0 && $total > 0){
            $offline_ratio=get_number_format($offline/$total);
        }
        $data['device_total']=array(
            'total'     =>  ['num'=>$total,'ratio'=>'1'],
            'online'    =>  ['num'=> $online,'ratio'=>$online_ratio],
            'offline'   =>  ['num'=> $offline,'ratio'=>$offline_ratio]
        );
        $data['device_type']=array(
            array(
                'title'=>'在线',
                'color'=>'blue'
            ),
            array(
                'title'=>'离线',
                'color'=>'red'
            )
        );
        return $data;
    }

    /**
     * 充电桩
     * @author: liukezhu
     * @date : 2021/10/25
     * @return array
     */
    public function getCharging(){
        $data=[];
        //充电桩总数
        $where[]=[ 'e.is_del','=',1];
        $where[]=[ 'v.status','=',1];
        $total=$this->HouseVillagePileEquipment->getCounts($where);
        //在线充电桩
        $where=[];
        $where[]=[ 'e.is_del','=',1];
        $where[]=[ 'v.status','=',1];
        $where['_string']= ' ( e.type = 2 and e.last_heart_time is not null and e.last_heart_time >= '.($this->time - 60).') or (e.type <> 2 and e.status = 1)';
        $online=$this->HouseVillagePileEquipment->getCounts($where);
        //离线充电桩
        $where=[];
        $where[]=[ 'e.is_del','=',1];
        $where[]=[ 'v.status','=',1];
        $where['_string']= ' ( e.type = 2 and (e.last_heart_time is null or e.last_heart_time < '.($this->time - 60).')) or (e.type <> 2 and e.status <> 1)';
        $offline=$this->HouseVillagePileEquipment->getCounts($where);
        $online_ratio=0;
        if($online > 0 && $total > 0){
            $online_ratio=get_number_format($online/$total);
        }
        $offline_ratio=0;
        if($offline > 0 && $total > 0){
            $offline_ratio=get_number_format($offline/$total);
        }
        $data['device_total']=array(
            'total'     =>  ['num'=>$total,'ratio'=>'1'],
            'online'    =>  ['num'=>$online,'ratio'=>$online_ratio],
            'offline'   =>  ['num'=>$offline,'ratio'=>$offline_ratio],
        );
        $where=[];
        $where[] = ['p.pay_time' , '>', 1];
        $field='sum(p.use_money) as use_money,sum(p.refund_money) as refund_money';
        //查询累计
        $cumulative=$this->HouseVillagePilePayOrder->get_ones($where,$field);
        $p=get_number_format($cumulative['use_money'] - $cumulative['refund_money']);
        $data['cumulative']=[
            'price'=>$p,
            'ratio'=>'100%'
        ];
        //查询当天
        $cumulative=$this->HouseVillagePilePayOrder->get_ones($where,$field,1);
        $p2=get_number_format($cumulative['use_money'] - $cumulative['refund_money']);
        $p21='0%';
        if($p > 0 && $p2 > 0){
            $p21=(get_number_format($p2 / $p) * 100).'%';
        }
        $data['today']=[
            'price'=>$p2,
            'ratio'=>$p21
        ];
        //查询昨天
        $cumulative=$this->HouseVillagePilePayOrder->get_ones($where,$field,2);
        $p3=get_number_format($cumulative['use_money'] - $cumulative['refund_money']);
        $p31='0%';
        if($p > 0 && $p3 > 0){
            $p31=(get_number_format($p3 / $p) * 100).'%';
        }
        $data['yesterday']=[
            'price'=>$p3,
            'ratio'=>$p31
        ];
        return $data;
    }

    //todo 查询所有小区下门禁统计
    public function getVillageFaceDevice($page=0,$limit=10){
        $where[]=[ 'd.is_del','=',0];
        $where[]=[ 'v.status','in',[1]];
        $field='v.village_id,v.village_name,COUNT( v.village_id ) AS total_num,COUNT( IF ( d.device_status = "1", 1, NULL ) ) AS online_num,COUNT( IF ( d.device_status = "2", 1, NULL ) ) AS offline_num ';
        $group='d.village_id ';
        $order='total_num DESC';
        $list=$this->HouseFaceDevice->getDeviceList($where,$group,$field,$order,$page,$limit);
        $count=$this->HouseFaceDevice->getDeviceCount($where,$group);
        if($list){
            $list=$list->toArray();
        }
        $data=array(
            'list'=>$list,
            'page'=>$page,
            'limit'=>$limit,
            'count'=>$count
        );
        return $data;
    }

    private function getDayOpenDoor($list,$total_num){
        $base=1;
        $background=[
            '#0079FF','#06D3C4','#FFBB32','#21C46A','#CB65E2','#F96D65'
        ];
        foreach ($list as $k=>&$v){
            $ratio='0%';
            if($total_num > 0 && $v['total_num'] > 0){
                $ratio=(get_number_format(($v['total_num'] * $base ) / $total_num) * 100).'%';
            }
            $v['ratio']=$ratio;
            $v['background']=$background[$k];
        }
        unset($v);
        return $list;
    }

    //todo 查询所有小区开门
    public function getVillageOpenDoor($log_from=0,$page=0,$limit=10){
        //$log_from 1 刷脸开门 2 操作开门
        $cacheKey=md5($log_from.$page.$limit);
        $cacheData=Cache::get($cacheKey);
        if($cacheData){
            $data=json_decode($cacheData,true);
            $cache_total_num=Cache::get(md5('total_num'.$log_from));
            if($cache_total_num){
                if($log_from == 0){
                    $cache_total_num1=Cache::get(md5('total_num1'));
                    $cache_total_num2=Cache::get(md5('total_num2'));
                    if($cache_total_num1 && $cache_total_num2){
                        $cache_total_num12=$cache_total_num1+$cache_total_num2;
                        if($cache_total_num12 > $cache_total_num){
                            $cache_total_num=$cache_total_num12;
                        }
                    }
                }
                $data['list']=$this->getDayOpenDoor($data['list'],$cache_total_num);
                $data['total_num']=$cache_total_num;
            }
            return $data;
        }
        $where[]=[ 'v.status','in',[1]];
        if($log_from == 1){
            $where[]=[ 'g.log_from','=',1];
            $where[]=[ 'g.log_status','=',0];
            $where2='g.log_from = 1 and g.log_status = 0';
        }
        elseif ($log_from == 2){
            $where[]=[ 'g.log_from','=',2];
            $where2='g.log_from = 2';
        }
        else{
            $where['_string']='(g.log_from = 1 and g.log_status = 0) or (g.log_from = 2)';
            $where2='(g.log_from = 1 and g.log_status = 0) or (g.log_from = 2)';
        }
        $field='v.village_id,v.village_name,COUNT( v.village_id ) AS total_num';
        $group='g.log_business_id ';
        $order='total_num DESC';
        $list=$this->HouseUserLog->getDayOpenDoorList($where,$group,$field,$order,$page,$limit)->toArray();
        $count=$total_num=0;
        $total_num=$this->HouseUserLog->getOpenDoorCount($where2);
        Cache::set(md5('total_num'.$log_from),$total_num,7200);
        if($list){
            $count=$this->HouseUserLog->getDayOpenDoorCount($where,$group);
            $list=$this->getDayOpenDoor($list,$total_num);
        }
        $data=array(
            'list'=>$list,
            'page'=>$page,
            'limit'=>$limit,
            'count'=>$count,
            'total_num'=>$total_num
        );
        if(!empty($list)){
            Cache::set($cacheKey,json_encode($data,JSON_UNESCAPED_UNICODE),7200);
        }
        return $data;
    }

    //todo 查询所有小区充电桩统计
    public function getVillagePileOrder($page=0,$limit=10){
        $date=date('Y-m-d');
        $timeArr=[];
        for($i=1;$i>=0;$i--) {
            if ($i == 0) {
                $today_zero = strtotime($date . ' 00:00:00');
            } else {
                $today_zero = strtotime('-' . $i . ' day', strtotime($date . ' 00:00:00'));
            }
            $timeArr[]=[
                'start'=>$today_zero,
                'end'=>$today_zero+86400-1
            ];
        }
        $where[] = ['o.pay_time' , '>', 1];
        $where[] = [ 'o.type','in',[2,21]];
        $field='v.village_id,v.village_name,count(distinct e.id) as pile_num,sum( o.use_money - o.refund_money ) AS total_price,	
        sum(IF( ( o.pay_time >= '.$timeArr[1]['start'].' AND o.pay_time <= '.$timeArr[1]['end'].' ), ( o.use_money - o.refund_money ), 0 )) AS today_price,
	     sum(IF( ( o.pay_time >= '.$timeArr[0]['start'].' AND o.pay_time <= '.$timeArr[0]['end'].' ), ( o.use_money - o.refund_money ), 0 ) ) AS yesterday_price';
        $group='o.village_id ';
        $order='pile_num desc,total_price desc';
        $list=$this->HouseVillagePilePayOrder->getVillageList($where,$group,$field,$order,$page,$limit);
        if($list){
            $list=$list->toArray();
            foreach ($list as &$v){
                $total_price=get_number_format($v['total_price']);
                $v['total_price']=($total_price == '0.00') ? '0' : $total_price;
                $today_price=get_number_format($v['today_price']);
                $v['today_price']=($today_price == '0.00') ? '0' : $today_price;
                $yesterday_price=get_number_format($v['yesterday_price']);
                $v['yesterday_price']=($yesterday_price == '0.00') ? '0' : $yesterday_price;
                unset($v['pile_num']);
            }
            unset($v);
        }
        $count=$this->HouseVillagePilePayOrder->getVillageCount($where,'count(distinct o.village_id) as num');
        $data=array(
            'list'=>$list,
            'page'=>$page,
            'limit'=>$limit,
            'count'=>isset($count['num']) ? $count['num'] : 0
        );
        return $data;
    }

    public function getVillageOpenDoorLog($page = 1, $limit = 10)
    {
        $where = [];
        $where[] = ['log_from', 'in', [1, 2, 3, 21]];
        $field = 'log_id, log_name, log_from, log_status, cut_image, log_time, open_status, device_id, log_bind_id, log_business_id, uid';
        $order = 'log_time desc, log_id DESC';
        $logList = $this->HouseUserLog->getTableList($where, $field, $page, $limit, $order);
        if ($logList && !is_array($logList)) {
            $logList = $logList->toArray();
        }
        $count = 0;
        $list = [];
        if ($page == 1) {
            $list[] = [
                ['title' => '小区名称', 'type' => 0], ['title' => '拍照图片', 'type' => 0], ['title' => '开门方式', 'type' => 0],
                ['title' => '抓拍时间', 'type' => 0], ['title' => '联系方式', 'type' => 0], ['title' => '用户类型', 'type' => 0],
                ['title' => '设备名称', 'type' => 0], ['title' => '地址', 'type' => 0],
            ];
        }
        if (!empty($logList)) {
            $villageIds = array_unique(array_column($logList, 'log_business_id'));
            if (!empty($villageIds)) {
                $dbHouseVillage = new HouseVillage();
                $whereVillage = [];
                $whereVillage[] = ['village_id', 'in', $villageIds];
                $villageColumn = $dbHouseVillage->getColumn($whereVillage, 'village_id, village_name', 'village_id');
            } else {
                $villageColumn = [];
            }
            $deviceIds = array_unique(array_column($logList, 'device_id'));
            if (!empty($deviceIds)) {
                $dbHouseFaceDevice = new HouseFaceDevice();
                $whereFaceDevice = [];
                $whereFaceDevice[] = ['device_id', 'in', $deviceIds];
                $deviceColumn = $dbHouseFaceDevice->getColumn($whereFaceDevice, 'device_id, device_name', 'device_id');
            } else {
                $deviceColumn = [];
            }
            $userBindIds = array_unique(array_column($logList, 'log_bind_id'));
            if (!empty($userBindIds)) {
                $dbHouseVillageUserBind = new HouseVillageUserBind();
                $whereUserBind = [];
                $whereUserBind[] = ['pigcms_id', 'in', $userBindIds];
                $column = "pigcms_id, name, phone, village_id, single_id, floor_id, layer_id, vacancy_id, type";
                $userBindColumn = $dbHouseVillageUserBind->getColumn($whereUserBind, $column, 'pigcms_id');
            } else {
                $userBindColumn = [];
            }
            $uidIds = array_unique(array_column($logList, 'uid'));
            if (!empty($uidIds)) {
                $dbUser = new User();
                $whereUser = [];
                $whereUser[] = ['uid', 'in', $uidIds];
                $userColumn = $dbUser->getColumn($whereUser, 'uid, phone, nickname', 'uid');
            } else {
                $userColumn = [];
            }

            foreach ($logList as $v) {
                $log_business_id = isset($v['log_business_id']) ? intval($v['log_business_id']) : 0;
                $villageInfo = isset($villageColumn[$log_business_id]) ? $villageColumn[$log_business_id] : [];
                $village_name = isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) : '';
                $village_id = isset($villageInfo['village_id']) ? intval($villageInfo['village_id']) : 0;
                
                $device_id = isset($v['device_id']) ? intval($v['device_id']) : 0;
                $deviceInfo = isset($deviceColumn[$device_id]) ? $deviceColumn[$device_id] : [];
                $device_name = isset($deviceInfo['device_name']) ? trim($deviceInfo['device_name']) : '';
                
                $log_bind_id = isset($v['log_bind_id']) ? intval($v['log_bind_id']) : 0;
                $userBindInfo = isset($userBindColumn[$log_bind_id]) ? $userBindColumn[$log_bind_id] : [];
                $name = isset($userBindInfo['name']) ? trim($userBindInfo['name']) : '';
                $phone = isset($userBindInfo['phone']) ? trim($userBindInfo['phone']) : '';
                !$village_id && $village_id = isset($userBindInfo['village_id']) ? intval($userBindInfo['village_id']) : 0;
                $single_id = isset($userBindInfo['single_id']) ? intval($userBindInfo['single_id']) : 0;
                $floor_id = isset($userBindInfo['floor_id']) ? intval($userBindInfo['floor_id']) : 0;
                $layer_id = isset($userBindInfo['layer_id']) ? intval($userBindInfo['layer_id']) : 0;
                $vacancy_id = isset($userBindInfo['vacancy_id']) ? intval($userBindInfo['vacancy_id']) : 0;
                $type = isset($userBindInfo['type']) ? intval($userBindInfo['type']) : 0;

                $uid = isset($v['uid']) ? intval($v['uid']) : 0;
                $userInfo = isset($userColumn[$uid]) ? $userColumn[$uid] : [];
                $u_phone = isset($userInfo['phone']) ? trim($userInfo['phone']) : '';
                $u_nickname = isset($userInfo['nickname']) ? trim($userInfo['nickname']) : '';

                if ($v['log_from'] == 21) {
                    //蓝牙开门
                    $newArr = [
                        ['title' => $village_name, 'type' => 0], ['title' => $defaultImg, 'type' => 1], ['title' => '蓝牙开门', 'type' => 0],
                        ['title' => date('H:i:s', $v['log_time']), 'type' => 0], ['title' => $phone ?? $u_phone, 'type' => 0],
                        ['title' => '户主', 'type' => 0], ['title' => '--', 'type' => 0], ['title' => '--', 'type' => 0],
                    ];
                } else {
                    //门禁设备开门
                    $address = '';
                    $title = '人脸开门';
                    $type = '暂无';
                    $face_img = cfg('site_url') . '/static/default_img.png';
                    if (isset($v['cut_image']) && trim($v['cut_image'])) {
                        $face_img = replace_file_domain($v['cut_image']);
                    } else {
                        $log_detail = [];
                        if (isset($v['log_detail']) && $v['log_detail']) {
                            try {
                                $log_detail = json_decode($v['log_detail'], 1);
                                !$log_detail && $log_detail = unserialize($v['log_detail']);
                            } catch (\Exception $e) {
                            } catch (\Throwable $e) {
                            } catch (\RuntimeException $e) {
                            } catch (\InvalidArgumentException $e) {
                            }
                        }
                        if (!empty($log_detail) && count($log_detail) > 0) {
                            fdump_api(['line' => __LINE__], '$log_detail', 1);
                            if (array_key_exists('face_img', $log_detail)) {
                                $face_img = replace_file_domain($log_detail['face_img']);
                            } elseif (array_key_exists('imgUrl', $log_detail)) {
                                $face_img = replace_file_domain($log_detail['imgUrl']);
                            } elseif (array_key_exists('mediaUrls', $log_detail)) {
                                $face_img = replace_file_domain($log_detail['mediaUrls']);
                            } elseif (array_key_exists('face_img_url', $log_detail)) {
                                $face_img = replace_file_domain($log_detail['face_img_url']);
                            } elseif (array_key_exists('imgUrl', $log_detail)) {
                                $face_img = replace_file_domain($log_detail['imgUrl']);
                            } elseif (array_key_exists('capture_img_url', $log_detail)) {
                                $face_img = replace_file_domain($log_detail['capture_img_url']);
                            } elseif (array_key_exists('pictureURL', $log_detail)) {//A5设备
                                $face_img = replace_file_domain($log_detail['pictureURL']);
                            } else {
                                $face_img = cfg('site_url') . '/static/default_img.png';
                            }
                        }
                    }
                    if ($v['log_from'] == 2)//手动开门
                    {
                        $title = '远程开门';
                        $face_img = $defaultImg;
                    }
                    if (isset($v['type'])) {
                        $type = $this->HouseUserLogService->userType[$v['type']];
                    }
                    if ($single_id > 0 && $floor_id > 0 && $layer_id > 0 && $vacancy_id > 0 && $village_id > 0) {
                        $address = $this->HouseVillageService->getSingleFloorRoom($single_id, $floor_id, $layer_id, $vacancy_id, $village_id);
                    } elseif (isset($v['log_name']) && trim($v['log_name'])) {
                        $address = trim($v['log_name']);
                    } else {
                        $address = '';
                    }
                    $newArr = [
                        ['title' => $village_name, 'type' => 0], ['title' => $face_img, 'type' => 1], ['title' => $title, 'type' => 0],
                        ['title' => date('H:i:s', $v['log_time']), 'type' => 0], ['title' => $phone ?? $u_phone, 'type' => 0],
                        ['title' => $type, 'type' => 0], ['title' => $device_name, 'type' => 0], ['title' => $address, 'type' => 0],
                    ];
                }
                $list[] = $newArr;
            }
            $whereCount = [];
            $whereCount[] = ['g.log_from', 'in', [1, 2, 3, 21]];
            $count = $this->HouseUserLog->getOpenDoorCount($whereCount, 0);
        }
        $data = array(
            'list' => $list, 'page' => $page,
            'page_count' => ($count > 0 && $limit > 0) ? (ceil($count / $limit)) : 0,
            'limit' => $limit, 'count' => $count
        );
        return $data;
    }

    //todo 查询所有小区人脸门禁实时记录
    public function getVillageOpenDoorLogBak($page=1,$limit=10){
        $where[]=[ 'g.log_from','in',[1,2,3,21]];
        $field='g.log_id,v.village_id,v.village_name,g.log_name,d.device_name,g.log_from,g.log_status,g.log_detail,g.log_time,g.open_status,ub.phone,ub.single_id,ub.floor_id,ub.layer_id,ub.vacancy_id,ub.type,u.phone as u_phone';
        $order='g.log_time desc,g.log_id DESC';
        $count=0;
        $log=$this->HouseUserLog->getFaceOpenLog($where,$field,$order,$page,$limit);
        $list= [];
        if($page == 1){
            $list[]=[
                ['title'=>'小区名称','type'=>0],
                ['title'=>'拍照图片','type'=>0],
                ['title'=>'开门方式','type'=>0],
                ['title'=>'抓拍时间','type'=>0],
                ['title'=>'联系方式','type'=>0],
                ['title'=>'用户类型','type'=>0],
                ['title'=>'设备名称','type'=>0],
                ['title'=>'地址','type'=>0],
            ];
        }
        if($log){
           foreach ($log as $v){
               if($v['log_from'] == 21){
                   //蓝牙开门
                   $newArr=[
                       ['title'=>$v['village_name'],'type'=>0],
                       ['title'=>'https://o2o-demo-img.oss-cn-hangzhou.aliyuncs.com/upload/houseface/000/002/699/5f2a230125804777.png','type'=>1],
                       ['title'=>'蓝牙开门','type'=>0],
                       ['title'=>date('H:i:s',$v['log_time']),'type'=>0],
                       ['title'=>$v['u_phone'],'type'=>0],
                       ['title'=>'户主','type'=>0],
                       ['title'=>'--','type'=>0],
                       ['title'=>'--','type'=>0],
                   ];
               }
               else{
                   //门禁设备开门
                   $log_detail = [];
                   if (isset($v['log_detail']) && $v['log_detail']) {
                       try {
                           $log_detail = json_decode($v['log_detail'], 1);
                           !$log_detail && $log_detail = unserialize($v['log_detail']);
                       } catch (\Exception $e) {
                       } catch (\Throwable $e) {
                       } catch (\RuntimeException $e) {
                       } catch (\InvalidArgumentException $e) {
                       }
                   }
                   $address='';
                   $title = '人脸开门';
                   $type = '暂无';
                   $face_img = cfg('site_url') . '/static/default_img.png';
                   if (!empty($log_detail) && count($log_detail) > 0) {
                       fdump_api(['line' => __LINE__], '$log_detail', 1);
                       if (array_key_exists('face_img', $log_detail)) {
                           $face_img = replace_file_domain($log_detail['face_img']);
                       }
                       elseif (array_key_exists('imgUrl', $log_detail)) {
                           $face_img = replace_file_domain($log_detail['imgUrl']);
                       }
                       elseif (array_key_exists('mediaUrls', $log_detail))
                       {
                           $face_img= replace_file_domain($log_detail['mediaUrls']);
                       }
                       elseif (array_key_exists('face_img_url', $log_detail)) {
                           $face_img = replace_file_domain($log_detail['face_img_url']);
                       }
                       elseif (array_key_exists('imgUrl', $log_detail)) {
                           $face_img = replace_file_domain($log_detail['imgUrl']);
                       }
                       elseif (array_key_exists('capture_img_url', $log_detail)) {
                           $face_img = replace_file_domain($log_detail['capture_img_url']);
                       }
                       elseif (array_key_exists('pictureURL', $log_detail)) {//A5设备
                           $face_img = replace_file_domain($log_detail['pictureURL']);
                       }
                       else {
                           $face_img = cfg('site_url') . '/static/default_img.png';
                       }
                   }
                   if ($v['log_from'] == 2)//手动开门
                   {
                       $title = '远程开门';
                       $face_img = cfg('site_url') . '/static/default_img.png';
                   }
                   if (isset($v['type'])) {
                       $type = $this->HouseUserLogService->userType[$v['type']];
                   }
                   if (isset($v['single_id']) && $v['single_id'] && isset($v['floor_id']) && $v['floor_id'] && isset($v['layer_id']) && $v['layer_id'] && isset($v['vacancy_id']) && $v['vacancy_id']) {
                       $address = $this->HouseVillageService->getSingleFloorRoom($v['single_id'], $v['floor_id'], $v['layer_id'], $v['vacancy_id'], $v['village_id']);
                   }
                   elseif ($v['log_name']) {
                       $address = $v['log_name'];
                   }
                   $newArr=[
                       ['title'=>$v['village_name'],'type'=>0],
                       ['title'=>$face_img,'type'=>1],
                       ['title'=>$title,'type'=>0],
                       ['title'=>date('H:i:s',$v['log_time']),'type'=>0],
                       ['title'=>$v['phone'],'type'=>0],
                       ['title'=>$type,'type'=>0],
                       ['title'=>$v['device_name'],'type'=>0],
                       ['title'=>$address,'type'=>0],
                   ];
               }
               $list[]=$newArr;
           }
            $count=$this->HouseUserLog->getOpenDoorCount($where,0);
        }
        $data=array(
            'list'=>$list,
            'page'=>$page,
            'page_count'=>($count > 0 && $limit > 0) ? (ceil($count/$limit)) : 0,
            'limit'=>$limit,
            'count'=>$count
        );
        return $data;
    }

    //todo 查询设备报警
    public function getMaintenanLog($page=1,$limit=10){
        $where=[];
        $field='village_name,address,device_name,reason,add_time';
        $order='id DESC,add_time DESC';
        $list=$this->HouseMaintenanLog->getList($where,$field,$order,$page,$limit);
        $data=[];
        if($page == 1){
            $data[]=[
                ['title'=>'小区名称','type'=>0],
                ['title'=>'地址','type'=>0],
                ['title'=>'设备名称','type'=>0],
                ['title'=>'故障现像','type'=>0],
                ['title'=>'发生时间','type'=>0],
            ];
        }
        if($list){
            $list=$list->toArray();
            foreach ($list as &$v){
                $data[]=[
                    ['title'=>$v['village_name'],'type'=>0],
                    ['title'=>$v['address'],'type'=>0],
                    ['title'=>$v['device_name'],'type'=>0],
                    ['title'=>$v['reason'],'type'=>0],
                    ['title'=>date('Y-m-d H:i:s',$v['add_time']),'type'=>0]

                ];
            }
            unset($v);
        }
        $count=$this->HouseMaintenanLog->getCount($where);
        $data=array(
            'list'=>$data,
            'page'=>$page,
            'page_count'=>($count > 0 && $limit > 0) ? (ceil($count/$limit)) : 0,
            'limit'=>$limit,
            'count'=>$count
        );
        return $data;
    }


    /**
     * 获取界面设备参数
     * @author: liukezhu
     * @date : 2021/11/26
     * @param $type
     * @return array
     * @throws \think\Exception
     */
    public function getDeviceParam($type){
        $data=[
            'device_status'=>[
                ['key'=>1,'value'=>'在线'],
                ['key'=>2,'value'=>'离线']
            ]
        ];
        if($type == 1){
            $data['device_type']=$this->HouseFaceDevice->device_type_info;
        }
        elseif ($type == 2){
            $data['device_type']=$this->HouseVillagePileEquipmentService->device_type_info;
        }
        else{
            throw new \think\Exception("参数不合法");
        }
        return $data;
    }

    /**
     * 门禁设备数据查询
     * @author: liukezhu
     * @date : 2021/11/26
     * @param $where
     * @param $field
     * @param $order
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getFaceDeviceList($where,$field,$order,$page=0,$limit=10){
        $device_type_info=$this->HouseFaceDevice->device_type_info;
        $list=$this->HouseFaceDevice->getLists($where,$field,$order,$page,$limit);
        if($list){
            foreach ($list as &$v){
                $device_type_name='';
                foreach ($device_type_info as $v2){
                    if(isset($v['device_type']) && ($v2['device_type'] == $v['device_type'])){
                        $device_type_name=$v2['name'];
                        break;
                    }
                }
                $v['device_type_name']=$device_type_name;
                unset($v['device_type']);
            }
            unset($v);
        }
        $count=$this->HouseFaceDevice->getCounts($where);
        return ['list'=>$list,'total_limit'=>$limit,'count'=>$count];
    }

    /**
     * 充电桩数据查询
     * @author: liukezhu
     * @date : 2021/11/26
     * @param $where
     * @param $field
     * @param $order
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getPileEquipmentList($where,$field,$order,$page=0,$limit=10){
        $device_type_info=$this->HouseVillagePileEquipmentService->device_type_info;
        $list=$this->HouseVillagePileEquipment->getLists($where,$field,$order,$page,$limit);
        if($list){
            foreach ($list as &$v){
                $device_type_name='';
                foreach ($device_type_info as $v2){
                    if($v2['device_type'] == $v['type']){
                        $device_type_name=$v2['name'];
                        break;
                    }
                }
                $status=1;
                if( ($v['type'] == 2 && (empty($v['last_heart_time']) || $v['last_heart_time'] < ($this->time - 60) )) || ($v['type'] != 2 and $v['status'] != 1) ){
                    $status=2;
                }
                $v['device_type_name']=$device_type_name;
                $v['device_status']=$status;
                unset($v['type'],$v['last_heart_time'],$v['status']);
            }
            unset($v);
        }
        $count=$this->HouseVillagePileEquipment->getCounts($where);
        return ['list'=>$list,'total_limit'=>$limit,'count'=>$count];
    }

    public function getMainTenanLogList($where=array(),$page=1,$limit=20,$workerStr=''){
        $field='*';
        $order='id DESC,add_time DESC';
        $list=$this->HouseMaintenanLog->getList($where,$field,$order,$page,$limit);
        if($list && !$list->isEmpty()){
            $list=$list->toArray();
            $device_type_info=$this->HouseFaceDevice->device_type_info;
            $device_type_arr=array('1'=>'人脸门禁', '2'=>'充电桩','3'=>'人脸识别摄像机','4'=>'智能快递柜','5'=>'智慧停车','6'=>'蓝牙门禁','7'=>'智能垃圾回收设备');
            $device_brand_arr=$this->HouseFaceDevice->device_brand_arr;
            $fieldStr='device_id,device_name,device_type,device_alive,device_sn,village_id,device_status,device_direction,device_brand,is_del';
            foreach ($list as $k=>$v){
                $list[$k]['key']=$v['id'];
                unset($list[$k]['maintenan_id']);
                $whereArr=array(['device_id','=',$v['device_id']]);
                $faceDevice = [];
                if($v['device_type'] == 7){
                    $faceDevice=$this->GarbageCollectionMachineList->getOne(['id'=>$v['device_id']],['device_no as device_sn,name as device_name']);//垃圾智能回收设备
                }else{
                    $faceDevice=$this->HouseFaceDevice->getOne($whereArr,$fieldStr);
                }
                $list[$k]['device_sn']='';
                $list[$k]['device_type_str']='';
                $list[$k]['device_status_str']='离线';
                $list[$k]['add_time_str']=date('Y-m-d H:i:s',$v['add_time']);
                if(isset($device_type_arr[$v['device_type']])){
                    $list[$k]['device_type_str']=$device_type_arr[$v['device_type']];
                }
                $list[$k]['device_brand_str']=$v['device_name'];
                $list[$k]['worker_str']=$workerStr;
                if($faceDevice && !$faceDevice->isEmpty()){
                    $faceDevice=$faceDevice->toArray();
                    $list[$k]['device_sn']=$faceDevice['device_sn'];
                    $list[$k]['device_brand_str']=$faceDevice['device_name'];
                    if(!empty($faceDevice['device_brand']) && isset($device_brand_arr[$faceDevice['device_brand']])){
                        $list[$k]['device_brand_str']=$device_brand_arr[$faceDevice['device_brand']]['title'];
                    }
                    if($faceDevice['is_del']==4){
                        $list[$k]['device_sn']=$faceDevice['device_sn'].'（设备已删除）';
                    }
                    $list[$k]['device_type_str']='人脸门禁';
                    if(empty($list[$k]['device_type_str'])){

                    }
                }
            }
        }else{
            $list=array();
        }
        $count=$this->HouseMaintenanLog->getCount($where);
        $data=array(
            'list'=>$list,
            'page'=>$page,
            'page_count'=>($count > 0 && $limit > 0) ? (ceil($count/$limit)) : 0,
            'total_limit'=>$limit,
            'count'=>$count
        );
        return $data;
    }
}