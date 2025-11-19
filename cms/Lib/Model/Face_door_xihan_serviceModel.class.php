<?php

class Face_door_xihan_serviceModel extends Model
{

    public function xihan_add_person($device_id = 0, $bind_arr_id = array())
    {
        if ($device_id < 1 || empty($bind_arr_id)) {
            return true;
        }
        $info = D('House_face_device')->where(array('device_id' => $device_id))->find();
        if ($info && $info['device_type'] == 31) {
            $village_id = $info['village_id'];
            $houseVillageUserBindDb = M('House_village_user_bind');
            $face_user_bind_deviceDb = M('Face_user_bind_device');
            $house_face_device_cmdDb = M('House_face_device_cmd');
            $now_time = time();
            foreach ($bind_arr_id as $pigcms_id) {
                $user_bind_arr = array();
                $whereArr = array();
                $whereArr['pigcms_id'] = $pigcms_id;
                $whereArr['village_id'] = $village_id;
                $userBind = $houseVillageUserBindDb->where($whereArr)->find();
                if (empty($userBind) || !isset($userBind['pigcms_id'])) {
                    fdump_api([__LINE__, 'device_id' => $device_id, 'pigcms_id' => $pigcms_id, 'err_msg' => '房间人员信息不存在！'], 'xihan/add_person', 1);
                    continue;
                }
                $relatedType = 1;  //1-内部员工,2-访客, 3-黑名单
                //$userBind['type'] 0户主，1租户，3夫妻，4子女，5父母，6朋友，7其他
                if ($userBind['type'] == 0 || $userBind['type'] == 3) {
                    $relatedType = 1;
                } else if ($userBind['type'] == 1) {
                    $relatedType = 1;
                } else if ($userBind['type'] == 2) {
                    $relatedType = 1;
                } else if ($userBind['type'] == 6 || $userBind['type'] == 7) {
                    //访客
                    $relatedType = 2;
                } else if ($userBind['type'] == 4) {
                    //工作人员
                    $relatedType = 1;
                }
                $user_bind_arr['bind_id'] = $pigcms_id;
                $user_bind_arr['device_id'] = $device_id;
                $user_bind_arr['device_type'] = 31;
                $user_bind_arr['person_id'] = $pigcms_id;
                $user_bind_arr['add_time'] = $now_time;

                $whereArr = ['bind_pigcms_id' => $pigcms_id, 'status' => ['in', [0, 3]]];
                $imgUrlInfo = D('House_face_img')->where($whereArr)->order('id desc')->field('img_url,status')->find();
                if (empty($imgUrlInfo) && $userBind['uid'] > 0) {
                    $whereArr = ['uid' => $userBind['uid'], 'status' => ['in', [0, 3]]];
                    $imgUrlInfo = D('House_face_img')->where($whereArr)->order('id desc')->field('img_url,status')->find();
                }
                $imgUrl = '';
                $face_id = 0;
                if ($imgUrlInfo) {
                    $face_id = $imgUrlInfo['id'];
                    fdump_api([__LINE__, 'device_id' => $device_id, 'pigcms_id' => $pigcms_id, 'imgUrlInfo' => $imgUrlInfo], 'xihan/add_person', 1);
                    $imgUrl = $imgUrlInfo['img_url'];
                    if (3 == $imgUrlInfo['status']) {
                        import('@.ORG.face.word_encryption');
                        $word_encryption = new word_encryption();
                        // 针对加密图片解密
                        $imgUrl = $word_encryption->text_decrypt($imgUrl);
                    }
                    $imgUrl = replace_file_domain($imgUrl);
                }
                $user_bind_arr['face_img'] = $imgUrl;
                $person_text = array();
                $personName = $userBind['name'] ? $userBind['name'] : '';
                $personInfon = array('Type' => $relatedType, 'Code' => strval($pigcms_id), 'GroupName' => "默认权限组", 'Name' => $personName, 'Sex' => 'male', 'Birthday' => '2000-01-01');
                if ($userBind['id_card']) {
                    $personInfon['CredentialType'] = 'IC';
                    $personInfon['CredentialNo'] = $userBind['id_card'];
                    $idCardInfo = idCardAnalysis($userBind['id_card']);
                    if ($idCardInfo && is_array($idCardInfo)) {
                        $personInfon['Birthday'] = $idCardInfo['birthday'];
                        $personInfon['Sex'] = $idCardInfo['sex'] == '男' ? 'male' : 'female';
                    }
                }
                //$personInfon['Status']=1;
                $personInfon['URL'] = array($imgUrl);
                $personInfon['AccessTimes'] = array();
                $accessTime = array("AccessType" => 1, "TimeSection" => array());
                $stat_time = time() - 10;
                $accessTime['TimeSection'][] = $stat_time;
                $end_time = 0;
                if ($relatedType == 2) {
                    //访客
                    $end_time = $now_time + 24 * 3600;
                } else if ($userBind['type'] == 4) {
                    $end_time = 2461420800;  //2048-01-01 
                } else {
                    $userTimes = D('House_village_user_bind')->getUserEndTime($pigcms_id);
                    if (isset($userTimes['propertyEndTime']) && $userTimes['propertyEndTime'] > 0) {
                        $end_time = intval($userTimes['propertyEndTime']);
                    }
                }
                if ($end_time > $now_time) {
                    $accessTime['TimeSection'][] = $end_time;
                } else {
                    $accessTime['TimeSection'][] = $now_time;
                }
                if ($relatedType != 2) {
                    $personInfon['AccessTimes'][] = $accessTime;
                } else {
                    unset($personInfon['AccessTimes']);
                    $visitor_bind_id = isset($userBind['pigcms_id']) && $userBind['pigcms_id'] ? $userBind['pigcms_id'] : 0;
                    $db_house_village_visitor = M('House_village_visitor');
                    $where_visitor = ['village_id' => $village_id, 'visitor_bind_id' => $visitor_bind_id];
                    $temp_visitor = $db_house_village_visitor->where($where_visitor)->find();
                    $is_car = isset($temp_visitor['is_car']) && $temp_visitor['is_car'] ? intval($temp_visitor['is_car']) : 0;
                    $car_id = isset($temp_visitor['car_id']) && $temp_visitor['car_id'] ? trim($temp_visitor['car_id']) : '';
                    $owner_name = isset($temp_visitor['owner_name']) && $temp_visitor['owner_name'] ? trim($temp_visitor['owner_name']) : '';
                    $carLicense = '';
                    if ($is_car == 1 && $car_id) {
                        $carLicense = $car_id;
                    }
                    $from = $stat_time;
                    if ($end_time > $now_time) {
                        $to = $end_time;
                    } else {
                        $to = $now_time;
                    }
                    $guestInfo = [
                        'Corp' => '', 'Phone' => isset($userBind['phone']) && $userBind['phone'] ? $userBind['phone'] : '',
                        'CarLicense' => $carLicense, 'Partner' => 1, 'Host' => $owner_name,
                        'AccessTime' => [['from' => $from, 'to' => $to]],
                    ];
                    $personInfon['GuestInfo'] = $guestInfo;
                }

                $cards = array();
                $dbHouseVillageUserBindIccard = M('House_village_user_bind_iccard');
                $whereIcCard = array('village_id' => $village_id);
                $whereIcCard['bind_id'] = $pigcms_id;
                $whereIcCard['del_time'] = array('lt', 1);
                $icCardList = $dbHouseVillageUserBindIccard->where($whereIcCard)->field('*')->limit(5)->order('type ASC,end_time DESC')->select();

                fdump_api(['icCardList' => $icCardList, 'whereIcCard' => $whereIcCard], 'xihan/add_person', 1);
                $db_house_device_qrcode = M('House_device_qrcode');
                $whereQrcode = array('device_equipment_type'=> 'face');
                $whereQrcode['bind_type'] ='village_user';
                $whereQrcode['bind_from'] = 'house_village_user_bind';
                $whereQrcode['bind_id'] = $pigcms_id;
                $whereQrcode['device_id'] = $device_id;
                $whereQrcode['delete_time'] =  0;
                $whereQrcode['device_type'] = 31;
                $qrcodeInfo = $db_house_device_qrcode->where($whereQrcode)->field('*')->find();
                $opened_times = isset($qrcodeInfo['opened_times']) && $qrcodeInfo['opened_times'] ? intval($qrcodeInfo['opened_times']) : 0;
                $onlyQrCode = 1;
                $hasQrCode = 0;
                if ($icCardList) {
                    foreach ($icCardList as $icCard) {
                        $ic_card_no = isset($icCard['ic_card_no']) && $icCard['ic_card_no'] ? $icCard['ic_card_no'] : '';
                        // 1-普通 2-胁迫 6-二维码
                        $type = isset($icCard['type']) && $icCard['type'] ? intval($icCard['type']) : 1;
                        if ($type == 6 && $icCard['end_time'] < $now_time) {
                            //过期的 二维码
                            continue;
                        }
                        if ($type != 6) {
                            $onlyQrCode = 0;
                        } elseif ($hasQrCode == 1) {
                            // 二维码只同步最新的 其他的跳过
                            continue;
                        } else {
                            $hasQrCode = 1;
                        }
                        $effective_start_time = isset($icCard['start_time']) && $icCard['start_time'] ? $icCard['start_time'] : 0;
                        $effective_end_time = isset($icCard['end_time']) && $icCard['end_time'] ? $icCard['end_time'] : 0;
                        $open_times = isset($icCard['open_times']) && $icCard['open_times'] ? intval($icCard['open_times']) : 0;
                        if ($open_times > 0 && $opened_times > 0 && in_array($type, [5, 6])) {
                            $LeftTimes = $open_times - $opened_times;
                        } elseif ($open_times > 0 && in_array($type, [5, 6])) {
                            $LeftTimes = $open_times;
                        } else {
                            $LeftTimes = -1;
                        }
                        if ($type <= 2 && ($effective_start_time < 100 || $effective_end_time < 100)) {
                            $effective_start_time = $stat_time;
                            $effective_end_time = $end_time > $now_time ? $end_time : $now_time;
                        }
                        $startTimeText = date('Y-m-d', $effective_start_time);
                        $endTimeText = date('Y-m-d', $effective_end_time);
                        $itemCard = [
                            'ID' => $ic_card_no,
                            'Type' => $type,
                            'Validity' => [$startTimeText, $endTimeText],
                            'ValidityTime'=>['00:00:01','23:59:59'],
                        ];
                        if ($type == 6) {
                            $itemCard['LeftTimes'] = $LeftTimes;
                        }
                        $cards[] = $itemCard;
                    }
                }
                $user_ic_card = isset($userBind['ic_card']) && trim($userBind['ic_card']) ? trim($userBind['ic_card']) : '';
                if ($onlyQrCode == 1 && $user_ic_card && is_numeric($user_ic_card) && strlen($user_ic_card)<11) {
                    $whereIcCardTmp = array('village_id' => $village_id);
                    $whereIcCardTmp['bind_id'] = $pigcms_id;
                    $whereIcCardTmp['type']=array('in',array(1,2));
                    $haveBindCard=$dbHouseVillageUserBindIccard->where($whereIcCardTmp)->find();
                    if(empty($haveBindCard)){
                        $accessStartTimeText = date('Y-m-d H:i:s', $stat_time);
                        if ($end_time > $now_time) {
                            $accessEndTimeText = date('Y-m-d H:i:s', $end_time);
                        } else {
                            $accessEndTimeText = date('Y-m-d H:i:s', $now_time);
                        }
                        $cards[] = [
                            'ID' => $user_ic_card,
                            'Type' => 1,
                            'Validity' => [$accessStartTimeText, $accessEndTimeText],
                            'ValidityTime'=>['00:00:01','23:59:59'],
                        ];
                        $card_data=array('village_id'=>$village_id,'bind_id'=>$pigcms_id,'ic_card_no'=>$user_ic_card);
                        $card_data['type']=1;
                        $card_data['last_time']=$now_time;
                        $card_data['start_time']=$stat_time;
                        $card_data['end_time']=$end_time>$now_time ? $end_time:$now_time;
                        $dbHouseVillageUserBindIccard->data($card_data)->add();  
                    }
                }
                $personInfon['Cards'] = $cards;
                $personTmp = array('Person' => $personInfon);
                $personInfonArr = array('params' => array($personTmp));
                $user_bind_arr['person_text'] = json_encode($personInfonArr, JSON_UNESCAPED_UNICODE);
                $id = $face_user_bind_deviceDb->data($user_bind_arr)->add();
                $cmdSynData = array('village_id' => $village_id, 'relate_id' => $id, 'bind_id' => $pigcms_id, 'cmd_name' => 'personnelData.savePersons');
                $cmd_data = array('method' => 'personnelData.savePersons', 'params' => array($personTmp));
                $cmdSynData['cmd_data'] = json_encode($cmd_data, JSON_UNESCAPED_UNICODE);
                $cmdSynData['add_time'] = time();
                $cmdSynData['device_id'] = $device_id;
                $cmdSynData['device_sn'] = $info['device_sn'];
                $cmdSynData['device_type'] = 31;
                $cmdSynData['device_type_str'] = 'xihan';
                $cmdSynData['relate_type'] = 'face_user_bind_device';
                $insert_id = $house_face_device_cmdDb->data($cmdSynData)->add();

                $faceSyncLogArr = array('pigcms_id' => $pigcms_id, 'face_id' => $face_id, 'device_id' => $device_id, 'device_sn' => $info['device_sn'], 'log_time' => $now_time);
                $faceSyncLogArr['status'] = 0;
                $faceSyncLogArr['device_info'] = json_encode($info, JSON_UNESCAPED_UNICODE);
                $faceSyncLogArr['device_cmd_id'] = $insert_id;
                $faceSyncLogArr['last_time'] = $now_time;
                $house_face_sync_logDb = M('House_face_sync_log');

                $where = array('pigcms_id' => $pigcms_id);
                if ($device_id) {
                    $where['device_id'] = $device_id;
                } else {
                    $where['device_sn'] = $info['device_sn'];
                }
                $face_sync_log = $house_face_sync_logDb->where($where)->field('log_id')->find();
                if ($face_sync_log && $face_sync_log['log_id']) {
                    $house_face_sync_logDb->where(array('log_id' => $face_sync_log['log_id']))->data($faceSyncLogArr)->save();
                } else {
                    $house_face_sync_logDb->data($faceSyncLogArr)->add();
                }
            }
        }
        return true;
    }

    public function del_face_door_user($pigcms_id = 0, $village_id = 0, $device_id = 0)
    {
        $whereArr = array('device_type' => 31, 'village_id' => $village_id);
        if ($device_id > 0) {
            $whereArr['device_id'] = $device_id;
        } else {
            $whereArr['is_del'] = array('lt', 4);
        }
        $face_devices = D('House_face_device')->where($whereArr)->select();
        if ($face_devices) {
            $now_time = time();
            $house_face_device_cmdDb = M('House_face_device_cmd');
            $house_face_sync_logDb = M('House_face_sync_log');
            foreach ($face_devices as $dvv) {
                $cmdSynData = array('village_id' => $village_id, 'relate_id' => 0, 'bind_id' => $pigcms_id, 'cmd_name' => 'personnelData.removePersons');
                $personTmp = array('Code' => strval($pigcms_id));
                $cmd_data = array('method' => 'personnelData.removePersons', 'params' => array($personTmp));
                $cmdSynData['cmd_data'] = json_encode($cmd_data, JSON_UNESCAPED_UNICODE);
                $cmdSynData['add_time'] = time();
                $cmdSynData['device_id'] = $dvv['device_id'];
                $cmdSynData['device_sn'] = $dvv['device_sn'];
                $cmdSynData['device_type'] = 31;
                $cmdSynData['device_type_str'] = 'xihan';
                $cmdSynData['relate_type'] = 'house_village_user_bind';
                $insert_id = $house_face_device_cmdDb->data($cmdSynData)->add();

                $faceSyncLogArr = array('pigcms_id' => $pigcms_id, 'face_id' => 0, 'device_id' => $dvv['device_id'], 'device_sn' => $dvv['device_sn'], 'log_time' => $now_time);
                $faceSyncLogArr['status'] = 0;
                $faceSyncLogArr['device_info'] = json_encode($dvv, JSON_UNESCAPED_UNICODE);
                $faceSyncLogArr['device_cmd_id'] = $insert_id;
                $faceSyncLogArr['last_time'] = $now_time;
                $faceSyncLogArr['sync_delete_time'] = $now_time;
                $faceSyncLogArr['sync_delete_msg'] = '人员删除';
                $house_face_sync_logDb->data($faceSyncLogArr)->add();

            }
        }
        return true;
    }

    public function del_face_door_device_user($device_id = 0, $village_id = 0)
    {
        $whereArr = array('device_type' => 31, 'village_id' => $village_id);
        $whereArr['device_id'] = $device_id;
        $face_device = D('House_face_device')->where($whereArr)->find();
        if ($face_device) {
            $now_time = time();
            $house_face_device_cmdDb = M('House_face_device_cmd');
            $house_face_sync_logDb = M('House_face_sync_log');
            $whereUser = array('village_id' => $village_id, 'status' => array('lt', 4));
            $village_user_binds = M('House_village_user_bind')->where($whereUser)->field('pigcms_id')->select();
            if ($village_user_binds) {
                foreach ($village_user_binds as $pvv) {
                    $cmdSynData = array('village_id' => $village_id, 'relate_id' => 0, 'bind_id' => $pvv['pigcms_id'], 'cmd_name' => 'personnelData.removePersons');
                    $personTmp = array('Code' => strval($pvv['pigcms_id']));
                    $cmd_data = array('method' => 'personnelData.removePersons', 'params' => array($personTmp));
                    $cmdSynData['cmd_data'] = json_encode($cmd_data, JSON_UNESCAPED_UNICODE);
                    $cmdSynData['add_time'] = time();
                    $cmdSynData['device_id'] = $face_device['device_id'];
                    $cmdSynData['device_sn'] = $face_device['device_sn'];
                    $cmdSynData['device_type'] = 31;
                    $cmdSynData['device_type_str'] = 'xihan';
                    $cmdSynData['relate_type'] = 'house_village_user_bind';
                    $insert_id = $house_face_device_cmdDb->data($cmdSynData)->add();

                    $faceSyncLogArr = array('pigcms_id' => $pvv['pigcms_id'], 'face_id' => 0, 'device_id' => $face_device['device_id'], 'device_sn' => $face_device['device_sn'], 'log_time' => $now_time);
                    $faceSyncLogArr['status'] = 0;
                    $faceSyncLogArr['device_info'] = json_encode($face_device, JSON_UNESCAPED_UNICODE);
                    $faceSyncLogArr['device_cmd_id'] = $insert_id;
                    $faceSyncLogArr['last_time'] = $now_time;
                    $faceSyncLogArr['sync_delete_time'] = $now_time;
                    $faceSyncLogArr['sync_delete_msg'] = '人员删除';
                    $house_face_sync_logDb->data($faceSyncLogArr)->add();

                }
            }
        }
        return true;
    }
    public function delxiHanFaceDoorCard($card_code='',$village_id = 0,$pigcms_id=0){
        $card_code=strval($card_code);
        $house_face_device_cmdDb = M('House_face_device_cmd');
        $whereArr = array('device_type' => 31, 'village_id' => $village_id);
        $whereArr['is_del'] = array('lt', 4);
        $face_devices = D('House_face_device')->where($whereArr)->select();
        if($face_devices){
            foreach ($face_devices as $dvv){
                $cmdSynData = array('village_id' => $village_id, 'relate_id' => 0, 'bind_id' => $pigcms_id, 'cmd_name' => 'cardManager.removeCard');
                $cmd_data = array('method' => 'cardManager.removeCard', 'params' => array('ID'=>$card_code));
                $cmdSynData['cmd_data'] = json_encode($cmd_data, JSON_UNESCAPED_UNICODE);
                $cmdSynData['add_time'] = time();
                $cmdSynData['device_id'] = $dvv['device_id'];
                $cmdSynData['device_sn'] = $dvv['device_sn'];
                $cmdSynData['device_type'] = 31;
                $cmdSynData['device_type_str'] = 'xihan';
                $cmdSynData['relate_type'] = 'house_village_user_bind';
                $insert_id = $house_face_device_cmdDb->data($cmdSynData)->add();
            }
        }
        return true;
    }
    public function xiHanStandbyPicture($village_id = 0, $advId = 0, $deviceIds = [])
    {
        $db_adver_village = M('Adver_village');
        if ($advId) {
            $whereAdv = [];
            $whereAdv['adver_id'] = $advId;
            $advertVillageInfo = $db_adver_village->where($whereAdv)->find();
            $village_id = isset($advertVillageInfo['village_id']) && $advertVillageInfo['village_id'] ? intval($advertVillageInfo['village_id']) : 0;
        }
        $db_house_face_device = M('House_face_device');
        $whereDevice = [
            'village_id' => $village_id, 'is_del' => 0, 'device_type' => 31,
        ];
        if (!empty($deviceIds)) {
            $whereDevice['device_id'] = ['in', $deviceIds];
        }
        $faceList = $db_house_face_device->where($whereDevice)->order('login_time ASC')->select();
        $nowTime = time();
        // 批量操作配置下发设备
        if (empty($faceList)) {
            return true;
        }
        $db_house_open_door_adver = M('House_open_door_adver');

        $where = [];
        $where['village_id'] = $village_id;
        $where['status'] = 1;
        $advertIds = $db_adver_village->where($where)->getField('adver_id', true);

        $whereAdvert = [];
        $whereAdvert['id'] = ['in', $advertIds];
        $whereAdvert['status'] = 1;
        $whereAdvert['android_pic'] = ['neq', ''];
        $advertList = $db_house_open_door_adver->where($whereAdvert)->limit(10)->order('add_time DESC')->select();
        $picture = [];
        if (!empty($advertList)) {
            foreach ($advertList as $advert) {
                if (!isset($picture['Path'])) {
                    $picture['Path'] = [];
                }
                if ($advId && isset($advert['id']) && $advId == $advert['id']) {
                    $picture['Interval'] = isset($advert['play_time']) && intval($advert['play_time']) > 0 ? intval($advert['play_time']) : 10;
                } elseif (!isset($picture['Interval']) || !$picture['Interval']) {
                    $picture['Interval'] = isset($advert['play_time']) && intval($advert['play_time']) > 0 ? intval($advert['play_time']) : 10;
                }
                $path = '';
                if (isset($advert['android_pic']) && $advert['android_pic']) {
                    $path = replace_file_domain($advert['android_pic']);
                } elseif (isset($advert['android_pic']) && $advert['android_pic']) {
                    $path = replace_file_domain($advert['android_pic']);
                }
                $picture['Path'][] = $path;
            }
        } else {
            $picture['Interval'] = 0;
            $picture['Path'] = [];
        }
        $movie = [];
        $movie['Interval'] = 0;
        $movie['Path'] = [];
        $db_house_face_device_cmd = M('House_face_device_cmd');
        $cmdParams = [
            'village_id' => $village_id, 'cmd_name' => 'configCentre.setConfig', 'add_time' => $nowTime,
            'exec_status' => 0, 'device_type_str' => 'xihan',
        ];
        foreach ($faceList as $face) {
            $cmdParams['device_id'] = $face['device_id'];
            $cmdParams['device_sn'] = $face['device_sn'];
            $cmdParams['device_type'] = $face['device_type'];
            $cmdParams['relate_id'] = isset($advertIds[0]) ? $advertIds[0] : 0;
            $cmdParams['relate_type'] = 'house_open_door_adver';
            $cmd_data = [
                'method' => 'configCentre.setConfig',
                'params' => [
                    'name' => 'Standby', 'content' => ['Mode' => 'picture', 'Movie' => $movie, 'Picture' => $picture],
                ],
            ];
            $dataCmd = $cmdParams;
            $dataCmd['cmd_data'] = json_encode($cmd_data, JSON_UNESCAPED_UNICODE);
            $db_house_face_device_cmd->add($dataCmd);
        }
        return true;
    }

    public function getCardNumberType($card_number_type)
    {
        // 卡片类型 1-普通 2-胁迫卡 5-VIP 6-二维码
        $arr = [
            '1' => '普通', '2' => '胁迫卡', '5' => 'VIP', '6' => '二维码',
        ];
        return isset($arr[$card_number_type]) ? $arr[$card_number_type] : '';
    }
}