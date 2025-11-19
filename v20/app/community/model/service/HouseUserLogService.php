<?php
/**
 * 开门记录
 * @author weili
 * @datetime 2020/8/3
 */

namespace app\community\model\service;
use app\community\model\db\HouseFaceDevice;
use app\community\model\db\HouseUserLog;
use app\community\model\db\HouseVillageDoor;
use app\community\model\db\HouseVillageOpenDoor;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillagePublicArea;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\User;
use app\community\model\service\Car\CarHandleGetInfoService;
use app\community\model\service\HouseVillageService;
use app\community\model\db\HouseVillage;
use app\consts\Car\CarFlowConst;
use think\facade\Cache;

class HouseUserLogService
{
    public $userType = [
        '0'=>'房主',
        '1'=>'家人',
        '2'=>'租客',
        '3'=>'房主',
        '4'=>'亲朋好友',
        '5'=>'出入证',
        '6'=>'访客'
    ];
    
    public function getOpenDoorLogList($params)
    {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $in_log_from = isset($params['in_log_from']) ? $params['in_log_from'] : [];
        $log_from = isset($params['log_from']) ? $params['log_from'] : '';
        $page = isset($params['page']) ? intval($params['page']) : 0;
        $pageSize = isset($params['pageSize']) ? intval($params['pageSize']) : 10;

        $newArr = [];
        $count = 0;
        $package_content = $this->getOrderPackage($village_id);
        $hardware_intersect = array_intersect([7, 8], $package_content);
        
        $dbHouseUserLog = new HouseUserLog();
        $serviceHouseVillage = new HouseVillageService();
        $dbHouseFaceDevice = new HouseFaceDevice();
        $dbHouseVillageDoor = new HouseVillageDoor();

        $whereUserLog = [];
        $whereUserLog[] = ['log_business_id', '=', $village_id];
        if (!empty($in_log_from)) {
            $whereUserLog[] = ['log_from', 'in', $in_log_from];
        } elseif ($log_from) {
            $whereUserLog[] = ['log_from', '=', $log_from];
        }
        $field = 'log_id,log_bind_id,device_id,log_name,log_from,log_time,cut_image,uid,log_detail';
        if ($hardware_intersect && count($hardware_intersect) > 0) {
            $list = $dbHouseUserLog->getLists($whereUserLog, $field);
            if ($list && !is_array($list)) {
                $list = $list->toArray();
            }
            if (!empty($list)) {
                $deviceIds = array_unique(array_column($list, 'device_id'));
                if (!empty($deviceIds)) {
                    $whereFaceDevice = [];
                    $whereFaceDevice[] = ['device_id', 'in', $deviceIds];
                    $deviceColumn = $dbHouseFaceDevice->getColumn($whereFaceDevice, 'device_id, device_name', 'device_id');
                } else {
                    $deviceColumn = [];
                }
                $userBindIds = array_unique(array_column($list, 'log_bind_id'));
                if (!empty($userBindIds)) {
                    $dbHouseVillageUserBind = new HouseVillageUserBind();
                    $whereUserBind = [];
                    $whereUserBind[] = ['pigcms_id', 'in', $userBindIds];
                    $column = "pigcms_id, name, phone, village_id, single_id, floor_id, layer_id, vacancy_id, type";
                    $userBindColumn = $dbHouseVillageUserBind->getColumn($whereUserBind, $column, 'pigcms_id');
                } else {
                    $userBindColumn = [];
                }
                $uidIds = array_unique(array_column($list, 'uid'));
                if (!empty($uidIds)) {
                    $dbUser = new User();
                    $whereUser = [];
                    $whereUser[] = ['uid', 'in', $uidIds];
                    $userColumn = $dbUser->getColumn($whereUser, 'uid, phone, nickname', 'uid');
                } else {
                    $userColumn = [];
                }
                $defaultImg = cfg('site_url') . '/static/default_img.png';
                foreach ($list as $key => $val) {
                    $device_id = isset($val['device_id']) ? intval($val['device_id']) : 0;
                    $deviceInfo = isset($deviceColumn[$device_id]) ? $deviceColumn[$device_id] : [];
                    $device_name = isset($deviceInfo['device_name']) ? trim($deviceInfo['device_name']) : '';
    
                    $log_bind_id = isset($val['log_bind_id']) ? intval($val['log_bind_id']) : 0;
                    $userBindInfo = isset($userBindColumn[$log_bind_id]) ? $userBindColumn[$log_bind_id] : [];
                    $name = isset($userBindInfo['name']) ? trim($userBindInfo['name']) : '';
                    $phone = isset($userBindInfo['phone']) ? trim($userBindInfo['phone']) : '';
                    !$village_id && $village_id = isset($userBindInfo['village_id']) ? intval($userBindInfo['village_id']) : 0;
                    $single_id = isset($userBindInfo['single_id']) ? intval($userBindInfo['single_id']) : 0;
                    $floor_id = isset($userBindInfo['floor_id']) ? intval($userBindInfo['floor_id']) : 0;
                    $layer_id = isset($userBindInfo['layer_id']) ? intval($userBindInfo['layer_id']) : 0;
                    $vacancy_id = isset($userBindInfo['vacancy_id']) ? intval($userBindInfo['vacancy_id']) : 0;
                    $type = isset($userBindInfo['type']) ? intval($userBindInfo['type']) : 0;
                    $type=strval($type);
                    $uid = isset($val['uid']) ? intval($val['uid']) : 0;
                    $userInfo = isset($userColumn[$uid]) ? $userColumn[$uid] : [];
                    $u_phone = isset($userInfo['phone']) ? trim($userInfo['phone']) : '';
                    $u_nickname = isset($userInfo['nickname']) ? trim($userInfo['nickname']) : '';
                    $cut_image=$defaultImg;
                    if($val['cut_image']  && strpos($val['cut_image'],'http')===false && file_exists('.'.$val['cut_image'])){
                        $cut_image=cfg('site_url').$val['cut_image'];
                    }else if($val['log_detail']){
                        $log_detail=json_decode($val['log_detail'],true);
                        if(isset($log_detail['imgUrl']) && $log_detail['imgUrl']){
                            $cut_image=$log_detail['imgUrl'];
                        }elseif (isset($log_detail['mediaUrls']) && $log_detail['mediaUrls']){
                            $cut_image=$log_detail['mediaUrls'];
                        }elseif (isset($log_detail['pictureURL']) && $log_detail['pictureURL']){
                            $cut_image=$log_detail['pictureURL'];
                        }elseif (isset($log_detail['faceCapture']) && $log_detail['faceCapture']['0']['bkgUrl']){
                            $cut_image=$log_detail['faceCapture']['0']['bkgUrl'];
                        }elseif (isset($log_detail['face_img_url']) && $log_detail['face_img_url']){
                            $cut_image=$log_detail['face_img_url'];
                        }else if($val['cut_image']){
                            $cut_image =replace_file_domain($val['cut_image']) ;
                        }
                    }else if($val['cut_image']){
                        $cut_image =replace_file_domain($val['cut_image']) ;
                    }
                   
                    $newArr[$key]['title'] = '人脸开门';
                    $newArr[$key]['face_img'] = $cut_image;
                    if ($val['log_from'] == 2)//手动开门
                    {
                        $newArr[$key]['title'] = '远程开门';
                        $newArr[$key]['face_img'] = $defaultImg;
                    }
                    $newArr[$key]['phone'] = $phone ?? $u_phone;
                    if ($single_id > 0 && $floor_id > 0 && $layer_id > 0 && $vacancy_id > 0 && $village_id > 0) {
                        $address = $serviceHouseVillage->getSingleFloorRoom($single_id, $floor_id, $layer_id, $vacancy_id, $village_id);
                    } elseif (isset($val['log_name']) && trim($val['log_name'])) {
                        $address = trim($val['log_name']);
                    } else {
                        $address = '';
                    }
                    $newArr[$key]['address'] = $address;
                    if (isset($val['log_time']) && intval($val['log_time']) > 100) {
                        $newArr[$key]['log_time_old'] = $val['log_time'];
                        $newArr[$key]['log_time'] = date('H:i:s', $val['log_time']);
                    }
                    $newArr[$key]['type'] = '暂无';
                    if ($userBindInfo && isset($this->userType[$type])) {
                        $newArr[$key]['type'] = $this->userType[$type];
                    } 
                    $newArr[$key]['device_name'] = $device_name ?? '';
                }
                $count = count($list);
            }
        }
        //套餐过滤
        if (in_array(6, $package_content)) {
            $dbHouseVillageOpenDoor = new HouseVillageOpenDoor();
            $whereOpenDoor = [];
            $whereOpenDoor[] = ['village_id', '=', $village_id];
            $fields = 'id, village_id, uid, add_time, door_device_id';
            $bluetooth = $dbHouseVillageOpenDoor->getLists($whereOpenDoor, $fields);
            if ($bluetooth && !is_array($bluetooth)) {
                $bluetooth = $bluetooth->toArray();
            }
            if (!empty($bluetooth)) {
                $deviceIds = array_unique(array_column($bluetooth, 'door_device_id'));
                if (!empty($deviceIds)) {
                    $whereFaceDevice = [];
                    $whereFaceDevice[] = ['door_device_id', 'in', $deviceIds];
                    $deviceColumn = $dbHouseVillageDoor->getColumn($whereFaceDevice, 'door_device_id, door_name', 'door_device_id');
                } else {
                    $deviceColumn = [];
                }
                $uidIds = array_unique(array_column($bluetooth, 'uid'));
                if (!empty($uidIds)) {
                    $dbUser = new User();
                    $whereUser = [];
                    $whereUser[] = ['uid', 'in', $uidIds];
                    $userColumn = $dbUser->getColumn($whereUser, 'uid, phone, nickname', 'uid');
                } else {
                    $userColumn = [];
                }
                $defaultImg = cfg('site_url') . '/static/default_img.png';
                foreach ($bluetooth as $key1 => $val1) {
                    $door_device_id = isset($val1['door_device_id']) ? intval($val1['door_device_id']) : 0;
                    $deviceInfo = isset($deviceColumn[$door_device_id]) ? $deviceColumn[$door_device_id] : [];
                    $door_name = isset($deviceInfo['door_name']) ? trim($deviceInfo['door_name']) : '';

                    $uid = isset($val1['uid']) ? intval($val1['uid']) : 0;
                    $userInfo = isset($userColumn[$uid]) ? $userColumn[$uid] : [];
                    $u_phone = isset($userInfo['phone']) ? trim($userInfo['phone']) : '';
                    $u_nickname = isset($userInfo['nickname']) ? trim($userInfo['nickname']) : '';
                    
                    $keyItem = $count + $key1;
                    $newArr[$keyItem]['title'] = '蓝牙开门';
                    $newArr[$keyItem]['type'] = '户主';
                    if (isset($val1['add_time']) && intval($val1['add_time']) > 0) {
                        $newArr[$keyItem]['log_time_old'] = $val1['add_time'];
                        $newArr[$keyItem]['log_time'] = date('H:i:s', $val1['add_time']);
                    } else {
                        $newArr[$keyItem]['log_time'] = '';
                    }
                    $newArr[$keyItem]['device_name'] = $door_name ?? '';
                    $newArr[$keyItem]['face_img'] = $defaultImg;
                    $newArr[$keyItem]['phone'] = $u_phone ?? '';
                    $newArr[$keyItem]['address'] = '--';
                }
            }
        }
        $list = $this->getRsort($newArr, 'log_time_old');
        $arr = [
            ['title' => '拍照图片', 'type' => 0], ['title' => '开门方式', 'type' => 0], ['title' => '抓拍时间', 'type' => 0],
            ['title' => '联系方式', 'type' => 0], ['title' => '用户类型', 'type' => 0], ['title' => '设备名称', 'type' => 0],
            ['title' => '地址', 'type' => 0],
        ];
        $list_arr = [];
        $list_arr[0] = $arr;
        $open_log_msg_hide = cfg('open_log_msg_hide');
        foreach ($list as $key2 => $val2) {
            if ($open_log_msg_hide && 1 == $open_log_msg_hide) {
                if (strlen($val2['phone']) == 11) {
                    $val2['phone'] = substr($val2['phone'], 0, 3) . '****' . substr($val2['phone'], 7, 4);
                } else if (strlen($val2['phone']) == 10) {
                    $val2['phone'] = substr($val2['phone'], 0, 2) . '****' . substr($val2['phone'], 7, 4);
                } else if (strlen($val2['phone']) == 9) {
                    $val2['phone'] = substr($val2['phone'], 0, 2) . '****' . substr($val2['phone'], 7, 3);
                } else if (strlen($val2['phone']) == 8) {
                    $val2['phone'] = substr($val2['phone'], 0, 2) . '***' . substr($val2['phone'], 7, 3);
                } else if (strlen($val2['phone']) == 7) {
                    $val2['phone'] = substr($val2['phone'], 0, 2) . '***' . substr($val2['phone'], 7, 2);
                } else {
                    $val2['phone'] = substr($val2['phone'], 0, 3) . '****' . substr($val2['phone'], 7, 4);
                }
                $val2['address'] = '***';
            }
            $keyItem1 = $key2 + 1;
            $list_arr[$keyItem1] = [
                ['title' => $val2['face_img'], 'type' => 1], ['title' => $val2['title'], 'type' => 0], ['title' => $val2['log_time'], 'type' => 0],
                ['title' => isset($val2['phone']) ? $val2['phone'] : '', 'type' => 0], ['title' => $val2['type'], 'type' => 0],
                ['title' => $val2['device_name'], 'type' => 0], ['title' => $val2['address'], 'type' => 0],
            ];
        }
        $newList['list'] = $list_arr;
        $doorWhere = [];
        $doorWhere[] = ['village_id', '=', $village_id];
        $doorCount = $dbHouseVillageDoor->getCount($doorWhere);

        $wheres = [];
        $wheres[] = ['village_id', '=', $village_id];
        $wheres[] = ['is_del', '=', 0];
        $faceCount = $dbHouseFaceDevice->getCount($wheres);
        //开门设备 显示状态
        if ($doorCount || $faceCount) {
            $status = 1;
        } else {
            $status = 0;
        }
        $newList['status'] = $status;
        $hardware_intersect = array_intersect([6, 7, 8], $package_content);
        if ($hardware_intersect && count($hardware_intersect) > 0) {
            $newList['machine_show'] = true;
        } else {
            $newList['machine_show'] = false;
        }
        return $newList;
    }
    
    /**
     * Notes:获取开门记录列表
     * @param $where
     * @param $field
     * @param $village_id
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/3 13:39
     */
    public function getOpenDoorLogListBak($where,$field,$village_id)
    {
        $dbHouseUserLog = new HouseUserLog();
        $serviceHouseVillage = new HouseVillageService();
        $dbHouseVillageOpenDoor = new HouseVillageOpenDoor();
        $newArr = [];
        $count=0;
        //套餐过滤
        $house_village_user_bind_service = new HouseVillageUserBindService();
        $package_content = $this->getOrderPackage($village_id);
        $hardware_intersect = array_intersect([7,8],$package_content);
        if($hardware_intersect && count($hardware_intersect)>0) {
            $list = $dbHouseUserLog->getList($where, $field);
            if ($list) {
                foreach ($list as $key => &$val) {
                    $newArr[$key]['title'] = '人脸开门';
                    $log_detail = [];
                    if (isset($val['log_detail']) && $val['log_detail']) {
                        try {
                            $log_detail = unserialize($val['log_detail']);
                        } catch (\Exception $e) {
                            $log_detail = [];
                        }
                    }
                    if (!$log_detail && isset($val['log_detail']) && $val['log_detail']) {
                        $log_detail = json_decode($val['log_detail'], true);
                    }
                    if (!empty($log_detail) && count($log_detail) > 0) {
                        if (array_key_exists('face_img', $log_detail)) {
                            $newArr[$key]['face_img'] = replace_file_domain($log_detail['face_img']);
                        } elseif (array_key_exists('imgUrl', $log_detail)) {
                            $newArr[$key]['face_img'] = replace_file_domain($log_detail['imgUrl']);
                        } elseif (array_key_exists('mediaUrls', $log_detail)) {
                            $newArr[$key]['face_img'] = replace_file_domain($log_detail['mediaUrls']);
                        } elseif (array_key_exists('face_img_url', $log_detail)) {
                            $newArr[$key]['face_img'] = replace_file_domain($log_detail['face_img_url']);
                        } elseif (array_key_exists('imgUrl', $log_detail)) {
                            $newArr[$key]['face_img'] = replace_file_domain($log_detail['imgUrl']);
                        } elseif (array_key_exists('capture_img_url', $log_detail)) {
                            $newArr[$key]['face_img'] = replace_file_domain($log_detail['capture_img_url']);
                        } elseif (array_key_exists('pictureURL', $log_detail)) {//A5设备
                            $newArr[$key]['face_img'] = replace_file_domain($log_detail['pictureURL']);
                        } else {
                            $newArr[$key]['face_img'] = cfg('site_url') . '/static/default_img.png';
                        }
                        if ($val['log_from'] == 2)//手动开门
                        {
                            $newArr[$key]['title'] = '远程开门';
                            $newArr[$key]['face_img'] = cfg('site_url') . '/static/default_img.png';
                        }
                        if (!$newArr[$key]['face_img']) {
                            $newArr[$key]['face_img'] = cfg('site_url') . '/static/default_img.png';
                        }
                        if (!$val['phone']) {
                            $newArr[$key]['phone'] = '';
                        } else {
                            $newArr[$key]['phone'] = $val['phone'];
                        }
                        if (isset($val['single_id']) && $val['single_id'] && isset($val['floor_id']) && $val['floor_id'] && isset($val['layer_id']) && $val['layer_id'] && isset($val['vacancy_id']) && $val['vacancy_id']){
                            $newArr[$key]['address'] = $serviceHouseVillage->getSingleFloorRoom($val['single_id'], $val['floor_id'], $val['layer_id'], $val['vacancy_id'], $village_id);
                        } elseif ($val['log_name']) {
                            $newArr[$key]['address'] = $val['log_name'];
                        }
                        if ($val['log_time']) {
                            $newArr[$key]['log_time_old'] = $val['log_time'];
                            $newArr[$key]['log_time'] = date('H:i:s', $val['log_time']);
                        }
                        if (isset($val['type'])) {
                            $newArr[$key]['type'] = $this->userType[$val['type']];
                        } else {
                            $newArr[$key]['type'] = '暂无';
                        }
                        if ($val['device_name']) {
                            $newArr[$key]['device_name'] = $val['device_name'];
                        } else {
                            $newArr[$key]['device_name'] = '';
                        }
                        if (!$val['phone'] && (isset($log_detail['info']) && !empty($log_detail['info']))) {
                            $info = $log_detail['info'];
                            $info_arr = json_decode($info, true);
                            // 用户信息数组
                            $target_info = $info_arr['Target'];
                            if($target_info){
                                $target_msg = reset($target_info);
                                $person = $target_msg['person'];
                            }else{
                                $person = [];
                            }
                            if ($person) {
                                $name = (isset($person['name']) && $person['name'])?$person['name']:'';
                                $ID = (isset($person['ID']) && $person['ID'])?$person['ID']:'';
                                $where_bind = [];
                                $is_where_bind = false;
                                if (is_idcard($ID)) {
                                    $is_where_bind = true;
                                    $where_bind[] = ['id_card','=',$ID];
                                } elseif ($name) {
                                    $is_where_bind = true;
                                    $where_bind[] = ['name','=',$name];
                                }
                                if ($is_where_bind) {
                                    $users = $house_village_user_bind_service->getBindInfo($where_bind,'pigcms_id,type,phone,single_id,floor_id,layer_id,vacancy_id,address');
                                    if ($users) {
                                        $users = $users->toArray();
                                        if (isset($users['type'])) {
                                            $newArr[$key]['type'] = $this->userType[$users['type']];
                                        } else {
                                            $newArr[$key]['type'] = '暂无';
                                        }
                                        if (isset($users['phone']) && $users['phone']) {
                                            $newArr[$key]['phone'] = $users['phone'];
                                        } else {
                                            $newArr[$key]['phone'] = '';
                                        }
                                        if (isset($users['single_id']) && $users['single_id'] && isset($users['floor_id']) && $users['floor_id'] && isset($users['layer_id']) && $users['layer_id'] && isset($users['vacancy_id']) && $users['vacancy_id']) {
                                            $newArr[$key]['address'] = $serviceHouseVillage->getSingleFloorRoom($users['single_id'], $users['floor_id'], $users['layer_id'], $users['vacancy_id'], $village_id);
                                        } elseif ($users['address']) {
                                            $newArr[$key]['address'] = $users['address'];
                                        } elseif ($person['address']) {
                                            $newArr[$key]['address'] = $person['address'];
                                        }
                                    }
                                }
                                if (!$newArr[$key]['address']) {
                                    $newArr[$key]['address'] = $person['address']?$person['address']:'';
                                }
                            }
                        } elseif (!$newArr[$key]['address'] && (isset($log_detail['info']) && !empty($log_detail['info']))) {
                            $info = $log_detail['info'];
                            $info_arr = json_decode($info, true);
                            // 用户信息数组
                            $target_info = $info_arr['Target'];
                            if($target_info) {
                                $target_msg = reset($target_info);
                                $person = $target_msg['person'];
                                $newArr[$key]['address'] = $person['address'] ? $person['address'] : '';
                            }
                        }
                    } else {
                        $newArr[$key]['face_img'] = cfg('site_url') . '/static/default_img.png';
                    }
                }
                $count = count($list);
            }
        }
        //套餐过滤
        if(in_array(6,$package_content)) {
            //蓝牙门禁
            $map[] = ['o.village_id', '=', $village_id];
            $fields = 'o.add_time,u.phone,d.door_name';
            $bluetooth = $dbHouseVillageOpenDoor->getList($map, $fields);
            if ($bluetooth) {
                foreach ($bluetooth as $key => $val) {
                    $newArr[$count + $key]['title'] = '蓝牙开门';
                    $newArr[$count + $key]['type'] = '户主';
                    if ($val['add_time']) {
                        $newArr[$count + $key]['log_time_old'] = $val['add_time'];
                        $newArr[$count + $key]['log_time'] = date('H:i:s', $val['add_time']);
                    } else {
                        $newArr[$count + $key]['log_time'] = '';
                    }
                    $newArr[$count + $key]['device_name'] = $val['door_name'];
                    $newArr[$count + $key]['face_img'] = 'https://o2o-demo-img.oss-cn-hangzhou.aliyuncs.com/upload/houseface/000/002/699/5f2a230125804777.png';
                    if ($val['phone']) {
                        $newArr[$count + $key]['phone'] = $val['phone'];
                    } else {
                        $newArr[$count + $key]['phone'] = '';
                    }
                    $newArr[$count + $key]['address'] = '--';
                }
            }
        }
        //$newList['list'] = $this->getRsort($newArr,'log_time');
        $list = $this->getRsort($newArr,'log_time_old');
        $arr = [
            ['title'=>'拍照图片','type'=>0],
            ['title'=>'开门方式','type'=>0],
            ['title'=>'抓拍时间','type'=>0],
            ['title'=>'联系方式','type'=>0],
            ['title'=>'用户类型','type'=>0],
            ['title'=>'设备名称','type'=>0],
            ['title'=>'地址','type'=>0],
        ];
        $list_arr = [];
        $list_arr[0] = $arr;
        $open_log_msg_hide = cfg('open_log_msg_hide');
        foreach ($list as $key=>$val)
        {
            if ($open_log_msg_hide && 1==$open_log_msg_hide) {
                if(strlen($val['phone']) == 11){
                    $val['phone'] = substr($val['phone'],0,3).'****'.substr($val['phone'],7,4);
                }else if(strlen($val['phone']) == 10){
                    $val['phone'] = substr($val['phone'],0,2).'****'.substr($val['phone'],7,4);
                }else if(strlen($val['phone']) == 9){
                    $val['phone'] = substr($val['phone'],0,2).'****'.substr($val['phone'],7,3);
                }else if(strlen($val['phone']) == 8){
                    $val['phone'] = substr($val['phone'],0,2).'***'.substr($val['phone'],7,3);
                }else if(strlen($val['phone']) == 7){
                    $val['phone'] = substr($val['phone'],0,2).'***'.substr($val['phone'],7,2);
                } else {
                    $val['phone'] = substr($val['phone'],0,3).'****'.substr($val['phone'],7,4);
                }
                $val['address'] = '***';
            }
            $list_arr[$key+1] = [
                ['title'=>$val['face_img'],'type'=>1],
                ['title'=>$val['title'],'type'=>0],
                ['title'=>$val['log_time'],'type'=>0],
                ['title'=>isset($val['phone'])?$val['phone']:'','type'=>0],
                ['title'=>$val['type'],'type'=>0],
                ['title'=>$val['device_name'],'type'=>0],
                ['title'=>$val['address'],'type'=>0],
            ];
        }
        $newList['list'] = $list_arr;
        $dbHouseVillageDoor = new HouseVillageDoor();//蓝牙门禁
        $dbHouseFaceDevice = new HouseFaceDevice();  //人脸设备
        $doorWhere[] = ['village_id','=',$village_id];
        $doorCount = $dbHouseVillageDoor->getCount($doorWhere);

        $wheres[] = ['village_id','=',$village_id];
        $wheres[] = ['is_del','=',0];
        $faceCount = $dbHouseFaceDevice->getCount($wheres);
        //开门设备 显示状态
        if($doorCount || $faceCount){
            $status = 1;
        }else{
            $status = 0;
        }
        $newList['status'] = $status;
        $hardware_intersect = array_intersect([6,7,8],$package_content);
        if($hardware_intersect && count($hardware_intersect)>0) {
            $newList['machine_show'] = true;
        }else{
            $newList['machine_show'] = false;
        }
        return $newList;
    }

    /**
     * Notes: 获取开门坐标
     * @param $where
     * @param $field
     * @param $village_id
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/5 9:44
     */
    public function getOpenAddress($where,$field,$village_id,$sTime)
    {
        $cacheTag  = 'redisGetOpenAddress';
        $cacheKey  = 'redisGetOpenAddressKey' . md5(\json_encode($where));
        $resultJson = Cache::store('redis')->get($cacheKey);
        if ($resultJson) {
            $result  = json_decode($resultJson, true);
            return $result;
        }
        $dbHouseUserLog = new HouseUserLog();
        $serviceHouseVillage = new HouseVillageService();
        $dbHouseVillage = new HouseVillage();
        $dbHouseVillageOpenDoor = new HouseVillageOpenDoor();
        $newArr = [];
        $houseFaceDeviceDb = new HouseFaceDevice();
        $houseVillagePublicAreaDb=new HouseVillagePublicArea();
        $houseVillageFloor=new HouseVillageFloor();
        //套餐过滤
        $package_content = $this->getOrderPackage($village_id);
        $hardware_intersect = array_intersect([7,8],$package_content);
        if($hardware_intersect && count($hardware_intersect)>0) {
            //人脸门禁坐标
            $where[] = ['l.log_time', '>=', $sTime];
            $where[] = ['l.log_time', '<=', time()];
            $list = $dbHouseUserLog->getAddressList($where, $field);
            if ($list) {
                $faceDeviceArr=array();
                foreach ($list as $key => $val) {
                    if(isset($faceDeviceArr[$val['device_id']])){
                        $faceDeviceInfo=$faceDeviceArr[$val['device_id']];
                    }else{
                        $faceDeviceInfoObj=$houseFaceDeviceDb->getOne(array('device_id'=>$val['device_id']),'device_id,device_name,device_type,device_sn,village_id,floor_id,public_area_id');
                        $faceDeviceInfo=array();
                        if($faceDeviceInfoObj && !$faceDeviceInfoObj->isEmpty()){
                            $faceDeviceInfo=$faceDeviceInfoObj->toArray();
                            $faceDeviceArr[$val['device_id']]=$faceDeviceInfo;
                        }
                    }
                    $lng='';
                    $lat='';
                    if($faceDeviceInfo && isset($faceDeviceInfo['device_id'])){
                        if($faceDeviceInfo['public_area_id']>0){
                           $publicAreaObj= $houseVillagePublicAreaDb->getOne(array('public_area_id'=>$faceDeviceInfo['public_area_id']),'lng,lat');
                           if($publicAreaObj && !$publicAreaObj->isEmpty()){
                               $lng=$publicAreaObj['lng'];
                               $lat=$publicAreaObj['lat'];
                           }
                        }
                        $faceDeviceInfo['floor_id']=$faceDeviceInfo['floor_id'] ? intval($faceDeviceInfo['floor_id']):0; 
                        if($faceDeviceInfo['floor_id']>0){
                            $villageFloorObj=$houseVillageFloor->getOne(array('floor_id'=>$faceDeviceInfo['floor_id']),'`long`,lat');
                            if($villageFloorObj && !$villageFloorObj->isEmpty()){
                                $lng=$villageFloorObj['long'];
                                $lat=$villageFloorObj['lat'];
                            }
                        }
                    }
                    if($lng && $lat){
                        $log_time= $val['add_time']>100 ? date('Y-m-d H:i:s', $val['add_time']):'';
                        if(empty($log_time) && $val['log_time']>100){
                            $log_time= date('Y-m-d H:i:s', $val['log_time']);
                        }
                        $newArr[]=array('lng'=>$lng,'lat'=>$lat,'log_id'=>$val['log_id'],'log_time'=>$log_time);
                    }
                }
            }
        }
        $count = count($newArr);
        //套餐过滤
        if(in_array(6,$package_content)) {
            //蓝牙门禁坐标
            $map[] = ['o.village_id', '=', $village_id];
            $map[] = ['o.add_time', '>=', $sTime];
            $map[] = ['o.add_time', '<=', time()];
            $fields = 'o.id,o.add_time,d.lng,d.lat,f.long as lngf,f.lat as latf,a.lng as lnga,a.lat as lata';
            $bluetooth = $dbHouseVillageOpenDoor->getAddressList($map, $fields);
            if ($bluetooth) {
                foreach ($bluetooth as $key => $val) {
                    if ($val['lng'] && $val['lat']) {
                        $newArr[$count + $key]['lng'] = $val['lng'];
                        $newArr[$count + $key]['lat'] = $val['lat'];
                        $newArr[$count + $key]['log_time'] = date('Y-m-d H:i:s', $val['add_time']);
                        $newArr[$count + $key]['log_id'] = $val['id'];
                    } elseif ($val['lngf'] && $val['latf']) {
                        $newArr[$count + $key]['lng'] = $val['lngf'];
                        $newArr[$count + $key]['lat'] = $val['latf'];
                        $newArr[$count + $key]['log_time'] = date('Y-m-d H:i:s', $val['add_time']);
                        $newArr[$count + $key]['log_id'] = $val['id'];
                    } elseif ($val['lnga'] && $val['lata']) {
                        $newArr[$count + $key]['lng'] = $val['lnga'];
                        $newArr[$count + $key]['lat'] = $val['lata'];
                        $newArr[$count + $key]['log_time'] = date('Y-m-d H:i:s', $val['add_time']);
                        $newArr[$count + $key]['log_id'] = $val['id'];
                    }
                }
            }
        }
        $village_field = 'village_id,long,lat';
        $village_info = $dbHouseVillage->getOne($village_id,$village_field);
        $newList = $this->getRsort($newArr,'log_time');
        $data['list'] = $newList;
        $data['info'] = $village_info;
        $resultJson = json_encode($data, JSON_UNESCAPED_UNICODE);
        Cache::store('redis')->tag($cacheTag)->set($cacheKey,$resultJson, 1700 + mt_rand(100, 1200));
        return $data;
    }
    /**
     * Notes: 获取开门总数量
     * @author: weili
     * @datetime: 2020/8/3 14:34
     */
    public function getOpenDoorNum($where)
    {
        $dbHouseUserLog = new HouseUserLog();
        $count = $dbHouseUserLog->getCount($where);
        return $count;
    }
    /**
     * Notes: 排序倒叙
     * @param $list
     * @param $field
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/3 17:02
     */
    public function getRsort($list,$field){
        $finishTime = [];
        foreach ($list as $val) {
            $finishTime[] = $val[$field];
        }
        array_multisort($finishTime,SORT_DESC,$list);
        return $list;
    }

    public function getDoorLogList($where,$field,$village_id,$page,$limit,$floor_id)
    {
        $dbHouseUserLog = new HouseUserLog();
        $dbHouseVillageOpenDoor = new HouseVillageOpenDoor();
        $list = $dbHouseUserLog->getList($where,$field,$page,$limit,'l.log_id desc',1);
        $where_count[] = ['f.village_id','=',$village_id];
        $where_count[] = ['f.is_del','=',0];
        $where_count[] = ['l.log_from','in',[1,2,6]];
        if($floor_id)
            $where_count[] = ['l.log_more_id','=',$floor_id];
        $door_open_count = $dbHouseUserLog->getVillageFloorCount($where_count);
        $newArr = [];
        if($list) {
            foreach ($list as $key => &$val) {
                $newArr[$key]['title'] = '开门方式-人脸开门';
                $newArr[$key]['log_time_str'] = date('Y-m-d H:i:s',$val['log_time']);
                $newArr[$key]['log_time'] = $val['log_time'];
                $newArr[$key]['log_name'] = $val['log_name'];
                $newArr[$key]['name'] = $val['name'];
                switch ($val['log_status']){
                    case 0:
                        $newArr[$key]['log_status'] = '比对通过';
                        break;
                    case 1:
                        $newArr[$key]['log_status'] = '比对失败';
                        break;
                    case 2:
                        $newArr[$key]['log_status'] = '无模板';
                        break;
                    case 3:
                        $newArr[$key]['log_status'] = '无效卡';
                        break;
                    case 4:
                        $newArr[$key]['log_status'] = '无现场照';
                        break;
                    case 5:
                        $newArr[$key]['log_status'] = '非活体';
                        break;
                    case 6:
                        $newArr[$key]['log_status'] = '证件过期';
                        break;
                    case 7:
                        $newArr[$key]['log_status'] = '超过时间限制';
                        break;
                    case 8:
                        $newArr[$key]['log_status'] = '超过次数限制';
                        break;
                    default:
                        $newArr[$key]['log_status'] = '位置错误';
                }
            }
        }
        $count = count($newArr);
        //蓝牙门禁
        $map[] = ['o.village_id','=',$village_id];
        //$map[] = ['o.add_time','<',$start_time];
        if($floor_id){
            $map[] = ['o.floor_id','=',$floor_id];
        }
        $fields='o.add_time,u.phone,d.door_name,u.nickname as name,o.open_status';
        $bluetooth = $dbHouseVillageOpenDoor->getList($map,$fields,$page,$limit,'o.id desc',1);
        $blue_where['village_id'] = $village_id;
        if($floor_id){
            $blue_where['floor_id'] = $floor_id;
        }
        $bluetooth_open_count = $dbHouseVillageOpenDoor->getCount($blue_where);
        if($bluetooth) {
            foreach ($bluetooth as $key => $val) {
                $newArr[$count + $key]['title'] = '开门方式-蓝牙开门';
                if ($val['add_time']) {
                    $newArr[$count + $key]['log_time_str'] = date('H:i:s', $val['add_time']);
                    $newArr[$count + $key]['log_time'] = $val['add_time'];
                } else {
                    $newArr[$count + $key]['log_time_str'] = '';
                    $newArr[$count + $key]['log_time'] = 1;
                }
                $newArr[$count + $key]['log_name'] = $val['door_name'];
                $newArr[$count + $key]['phone'] = $val['phone'];
                $newArr[$count + $key]['name'] = $val['name'];
                switch ($val['open_status']){
                    case 1:
                        $newArr[$count + $key]['log_status'] = '扫描失败';
                        break;
                    case 2:
                        $newArr[$count + $key]['log_status'] = '连接失败';
                        break;
                    case 3:
                        $newArr[$count + $key]['log_status'] = '重连失败';
                        break;
                    case 4:
                        $newArr[$count + $key]['log_status'] = '获取不到蓝牙关键字';
                        break;
                    default:
                        $newArr[$count + $key]['log_status'] = '开门成功';
                }
            }
        }
        $list = $this->getRsort($newArr,'log_time');
        $list = array_slice($list, $page, 5, true);
        $list = array_values($list);
        $return['count'] = $door_open_count+$bluetooth_open_count;
        $return['list'] = $list;
        return $return;
    }
    
    //过滤套餐 2020/11/11 start
    public function getOrderPackage($village_id, $property_id = 0)
    {
        if (!$property_id && $village_id) {
            $dbHouseVillage = new HouseVillage();
            $village_info = $dbHouseVillage->getOne($village_id, 'property_id');
            $property_id = isset($village_info['property_id']) ? intval($village_info['property_id']) : 0;
        }
        $this->setCacheTag('propertyOrderPackage:' . $property_id);
        $cacheKey = $property_id ? 'orderPackage' . $property_id : '';
        if ($cacheKey) {
            $package_content = $this->CacheTagGet($cacheKey);
            if (!empty($package_content)) {
                return $package_content;
            }
        }
        $servicePackageOrder = new PackageOrderService();
        $dataPackage = $servicePackageOrder->getPropertyOrderPackage($property_id,$village_id);
        if($dataPackage) {
            $dataPackage = $dataPackage->toArray();
            $package_content = isset($dataPackage['content']) ? $dataPackage['content'] : [];
            $cacheKey && $this->CacheTagSet($cacheKey, $package_content, 3600);
        }else{
            $package_content =[];
        }
        return $package_content;
    }
    //过滤套餐 2020/11/11 end

    /**
     * todo 对应小区车辆登记变动了清除下.
     */
    public function clearPackageOrder($property_id = 0)
    {
        try {
            $this->setCacheTag('propertyOrderPackage:' . $property_id);
            $this->CacheTagClear();
        } catch (\Exception $e){
            fdump_api(['msg' => '清除临时车登记信息缓存错误', 'errLine' => $e->getLine(), 'errMsg' => $e->getMessage(), 'errFile' => $e->getFile(), 'errCode' => $e->getCode(), 'line' => __LINE__], 'packageOrder/clearPackageOrderLog', 1);
        }
    }

    public function setCacheTag($type)
    {
        $this->cacheTagKey = 'packageOrder:cache:' . $type;
    }

    public function CacheTagSet($cacheKey, $data, $expire = null)
    {
        return Cache::tag($this->cacheTagKey)->set($cacheKey, $data, $expire);
    }

    public function CacheTagGet($cacheKey)
    {
        return Cache::get($cacheKey);
    }

    public function CacheTagClear()
    {
        return Cache::tag($this->cacheTagKey)->clear();
    }
}