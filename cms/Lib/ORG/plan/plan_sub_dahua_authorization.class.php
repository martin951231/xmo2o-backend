<?php


class plan_sub_dahua_authorization extends plan_base
{
    /**
     * 给小区住户开卡和绑定人脸
     * @author lijie
     * @date_time 2021/12/08
     * @param $param
     * @return bool
     */
    public function runTask($param)
    {
        $dahua_service = D('Dahua_service');
        $pigcms_id = $param['pigcms_id'];
        $device_id = $param['device_id'];
        $cardPrivilegeDetails = $param['cardPrivilegeDetails'];
        $bind_info = M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->field('name,phone,single_id,village_id,id_card,property_endtime,property_starttime,type,uid,vacancy_id')->find();
        $info = D('House_village_user_bind')->getUserEndTime($pigcms_id);
        $bind_info['property_starttime'] = $info['propertyStartTime'];
        $bind_info['property_endtime'] = $info['propertyEndTime'];
        if(empty($bind_info['phone']) || empty($bind_info['id_card'])){
            fdump_api(['msg' =>'用户未设置手机号或身份证号','pigcms_id' => $pigcms_id,'device_id' => $device_id,'line' => __LINE__],'dahua/dahuaErrorMessage',1);
            M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data([
                'face_img_status'=>2,'face_img_reason' => '用户未设置手机号或身份证号',
            ])->save();
            return true;
        }
        $village_id = $bind_info['village_id'];
        $this->keepThread();
        if(empty($bind_info['property_endtime'])){
            $end_time = '2035-12-31';
            $start_time = date('Y-m-d',time());
        }elseif ($bind_info['property_endtime'] < time()){
            $end_time = date('Y-m-d',$bind_info['property_endtime']);
            $start_time = date('Y-m-d',$bind_info['property_endtime']-86400);
        }else{
            $end_time = date('Y-m-d',$bind_info['property_endtime']);
            if($bind_info['property_starttime']){
                if($bind_info['property_starttime'] > $bind_info['property_endtime']){
                    $start_time = date('Y-m-d',time());
                }else{
                    $start_time = date('Y-m-d',$bind_info['property_starttime']);
                }
            }else{
                $start_time = date('Y-m-d',time());
            }
        }
        if(!$village_id && !$bind_info['single_id']){
            fdump_api(['msg' =>'小区id和楼栋id不能同时为空','pigcms_id' => $pigcms_id,'device_id' => $device_id,'bind_info' => $bind_info,'line' => __LINE__],'dahua/dahuaErrorMessage',1);
            M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data([
                'face_img_status'=>2,'face_img_reason' => '小区和楼栋归属不存在',
            ])->save();
            return true;
        }
        //获取开门计划id
        $process_id = $this->time_quan_tum($device_id,$pigcms_id);
        if(!$process_id){
            fdump_api(['msg' =>'开门计划id不存在','pigcms_id' => $pigcms_id,'device_id' => $device_id,'process_id' => $process_id,'line' => __LINE__],'dahua/dahuaErrorMessage',1);
            M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data([
                'face_img_status'=>2,'face_img_reason' => '联系管理员，开门计划异常',
            ])->save();
            return true;
        }
        //获取部门id
        $department_id = $this->get_department($device_id,$bind_info['single_id'],$village_id,$pigcms_id);
        if(!$department_id){
            fdump_api(['msg' =>'部门id不存在','pigcms_id' => $pigcms_id,'device_id' => $device_id,'bind_info' => $bind_info,'line' => __LINE__],'dahua/dahuaErrorMessage',1);
            M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data([
                'face_img_status'=>2,'face_img_reason' => '部门归属不存在',
            ])->save();
            return true;
        }
        //获取身份
        $identity_id = $this->get_identity($department_id,$bind_info['type'],$pigcms_id);
        if(!$identity_id){
            fdump_api(['msg' =>'部门id不存在','pigcms_id' => $pigcms_id,'device_id' => $device_id,'bind_info' => $bind_info,'line' => __LINE__],'dahua/dahuaErrorMessage',1);
            M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data([
                'face_img_status'=>2,'face_img_reason' => '非合规身份',
            ])->save();
            return true;
        }
        $face_user_bind_device_info = M('Face_user_bind_device')->where(['bind_type'=>0,'bind_id'=>$pigcms_id,'device_type'=>88])->find();
        $this->keepThread();
        //todo 添加人员，目前生日、性别、人员等信息先默认写死
        if($face_user_bind_device_info){
            $bind_device_id = $face_user_bind_device_info['id'];
            if($face_user_bind_device_info['person_id']){
                $code = $face_user_bind_device_info['code'];
                $person_id = $face_user_bind_device_info['person_id'];
            }else{
                $code = $dahua_service->create_code();//人员编号
                $res_add_person = $dahua_service->addPerson('身份证',$bind_info['id_card'],$name=!empty($bind_info['name'])?$bind_info['name']:'无名氏',$code,$department_id,'男',$birthday='2018-11-03',$bind_info['phone'],$status='在职',$identity_id);
                if($res_add_person['status']){
                    $person_id = $res_add_person['person_id'];
                    M('Face_user_bind_device')->where(['bind_type'=>0,'bind_id'=>$pigcms_id,'device_type'=>88])->data([
                        'person_id'=>$person_id,
                        'code'=>$code,
                        'last_time'=>time()
                    ])->save();
                } elseif (isset($res_add_person['dhUser'])&&isset($res_add_person['dhUser']['code'])) {
                    $code = $res_add_person['dhUser']['code'];
                    $person_id = $res_add_person['dhUser']['id'];
                    M('Face_user_bind_device')->where(['bind_type'=>0,'bind_id'=>$pigcms_id,'device_type'=>88])->data([
                        'person_id'=>$person_id,
                        'code'=>$code,
                        'last_time'=>time()
                    ])->save();
                } else{
                    fdump_api(['msg' =>$res_add_person['errMsg'],'pigcms_id' => $pigcms_id,'device_id' => $device_id,'bind_info' => $bind_info,'line' => __LINE__],'dahua/dahuaErrorMessage',1);
                    M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data([
                        'face_img_status'=>2,'face_img_reason' => $res_add_person['errMsg'],
                    ])->save();
                    return true;
                }
            }
            $this->keepThread();
        }else{
            $code = $dahua_service->create_code();//人员编号
            $res_add_person = $dahua_service->addPerson('身份证',$bind_info['id_card'],$name=!empty($bind_info['name'])?$bind_info['name']:'无名氏',$code,$department_id,'男',$birthday='2018-11-03',$bind_info['phone'],$status='在职',$identity_id);
            if($res_add_person['status']){
                $person_id = $res_add_person['person_id'];
                $dataBind = [
                    'bind_type'=>0,
                    'bind_id'=>$pigcms_id,
                    'device_type'=>88,
                    'person_id'=>$person_id,
                    'group_id'=>$department_id,
                    'groupID'=>$identity_id,
                    'code'=>$code,
                    'add_time'=>time()
                ];
                $bind_device_id = M('Face_user_bind_device')->data($dataBind)->add();
                fdump_api(['res_add_person' => $res_add_person,'$bind_device_id' => $bind_device_id,'$dataBind' => $dataBind,'Funs' => __FUNCTION__],'dahua/create_code',1);
            } elseif (isset($res_add_person['dhUser'])&&isset($res_add_person['dhUser']['code'])) {
                $code = $res_add_person['dhUser']['code'];
                $person_id = $res_add_person['dhUser']['id'];
                $department_id = $res_add_person['dhUser']['deptId'];
                $identity_id = $res_add_person['dhUser']['personIdentityId'];
                $dataBind = [
                    'bind_type'=>0,
                    'bind_id'=>$pigcms_id,
                    'device_type'=>88,
                    'person_id'=>$person_id,
                    'group_id'=>$department_id,
                    'groupID'=>$identity_id,
                    'code'=>$code,
                    'add_time'=>time()
                ];
                $bind_device_id = M('Face_user_bind_device')->data($dataBind)->add();
                fdump_api(['res_add_person' => $res_add_person,'$bind_device_id' => $bind_device_id,'$dataBind' => $dataBind,'Funs' => __FUNCTION__],'dahua/create_code',1);
            } else{
                fdump_api(['msg' =>$res_add_person,'pigcms_id' => $pigcms_id,'device_id' => $device_id,'bind_info' => $bind_info,'line' => __LINE__],'dahua/dahuaErrorMessage',1);
                M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data([
                    'face_img_status'=>2,'face_img_reason' => $res_add_person['errMsg'],
                ])->save();
                return true;
            }
            $this->keepThread();
        }
        //todo 上传人员图片
        $where_img['uid'] = $bind_info['uid'];
        $where_img['status'] = array('in','0,3');
        $house_face_img = M('House_face_img')->where($where_img)->field('status,img_url,id,face_img_id')->select();
        if(!empty($house_face_img)){
            import('@.ORG.face.word_encryption');
            $word_encryption = new word_encryption();
            foreach ($house_face_img as $k=>$v){
                $this->keepThread();
                if($v['status'] == 3){
                    // 针对加密图片解密
                    $imgUrl = $word_encryption->text_decrypt($v['img_url']);
                }else{
                    $imgUrl = $v['img_url'];
                }
                try {
                    $imgUrl = D('Image')->transform_image($imgUrl);
                    $imgUrl = thumb_img($imgUrl, 640, 480, '', true);
                    $img = $dahua_service->imgToBase64($imgUrl);
                    $res_add_img = $dahua_service->save_mobile_base64_image_to_byte($code,$img); //上传人员头像
                } catch( \Exception $e) {
                    $res_add_img = [];
                    $res_add_img['errMsg'] = $e->getMessage();
                }
                if($res_add_img['status']){
                    M('Face_user_bind_device')->where(['id'=>$bind_device_id])->data([
                        'last_time'=>time(),
                        'face_num'=>1,
                        'face_id'=>$v['id'],
                        'face_image_id'=>$v['face_image_id'],
                        'face_img'=>$imgUrl,
                        'face_time'=>time(),
                        'face_status'=>2,
                        'face_grant'=>1
                    ])->save();
                    M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data([
                        'face_img_status'=>1,'face_img_reason' => '',
                    ])->save();
                }else{
                    M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data([
                        'face_img_status'=>2,'face_img_reason' => $res_add_img['errMsg'],
                    ])->save();
                    fdump_api(['msg' =>$res_add_img['errMsg'],'pigcms_id' => $pigcms_id,'device_id' => $device_id,'imgUrl' => $imgUrl,'$code' => $code,'line' => __LINE__],'dahua/dahuaErrorMessage',1);
                }
            }
        }
        //todo 人员开卡,卡密码先默认身份证后六位
        $card_num = $dahua_service->create_card_number();
        if($face_user_bind_device_info){
            if($face_user_bind_device_info['cardid']){
                if(true){
                    $this->keepThread();
                    $cardPrivilegeDetails1 = $this->getPersonDevices($pigcms_id,$village_id);
                    if (!empty($cardPrivilegeDetails1)) {
                        $cardPrivilegeDetails = $cardPrivilegeDetails1;
                    }
                    $this->keepThread();
                    $res_door_authority_update = $dahua_service->door_authority_update($face_user_bind_device_info['cardid'],$process_id,$cardPrivilegeDetails);
                    if($res_door_authority_update['status']){
                        M('Face_user_bind_device')->where(['id'=>$bind_device_id])->data([
                            'card_grant'=>1
                        ])->save();
                    }else{
                        M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data([
                            'face_img_status'=>2,'face_img_reason' => $res_door_authority_update['errMsg'],
                        ])->save();
                        fdump_api(['msg' =>$res_door_authority_update['errMsg'],'pigcms_id' => $pigcms_id,'device_id' => $device_id,'cardid' => $face_user_bind_device_info['cardid'],'process_id' => $process_id,'line' => __LINE__],'dahua/dahuaErrorMessage',1);
                    }
                }
                $this->keepThread();
                $res_card_info = $dahua_service->card_info('',$face_user_bind_device_info['cardid']);
                if($res_card_info['status']){
                    $card_id = $res_card_info['data']['pageData'][0]['id'];
                    $res_update_card = $dahua_service->update_card($face_user_bind_device_info['cardid'],$face_user_bind_device_info['card_pass'],$start_time,$end_time,$card_id);
                    if(!$res_update_card['status']){
                        M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data([
                            'face_img_status'=>2,'face_img_reason' => $res_update_card['errMsg'],
                        ])->save();
                        fdump_api(['msg' =>$res_update_card['errMsg'],'pigcms_id' => $pigcms_id,'device_id' => $device_id,'cardid' => $face_user_bind_device_info['cardid'],'card_pass' => $face_user_bind_device_info['card_pass'],'id' => $card_id,'line' => __LINE__],'dahua/dahuaErrorMessage',1);
                    }
                }else{
                    M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data([
                        'face_img_status'=>2,'face_img_reason' => $res_card_info['errMsg'],
                    ])->save();
                    fdump_api(['msg' =>$res_card_info['errMsg'],'pigcms_id' => $pigcms_id,'device_id' => $device_id,'cardid' => $face_user_bind_device_info['cardid'],'res_card_info' => $res_card_info,'line' => __LINE__],'dahua/dahuaErrorMessage',1);
                }
                $this->keepThread();
            }else{
                $res_open_batch = $dahua_service->open_batch($person_id,$card_num,'0','0','ACTIVE',$start_time,$end_time,substr($bind_info['id_card'],-6),'1');  //开卡
                if($res_open_batch['status']){
                    M('Face_user_bind_device')->where(['id'=>$bind_device_id])->data([
                        'card_status'=>1,
                        'cardid'=>$card_num,
                        'card_pass'=>substr($bind_info['id_card'],-6),
                    ])->save();
                    $res_door_authority_update = $dahua_service->door_authority_update($card_num,$process_id,$cardPrivilegeDetails);
                    if($res_door_authority_update['status']){
                        M('Face_user_bind_device')->where(['id'=>$bind_device_id])->data([
                            'card_grant'=>1
                        ])->save();
                    }else{
                        fdump_api([$res_door_authority_update['errMsg'],$pigcms_id,$device_id,__LINE__],'dahuaErrorMessage',1);
                        M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data([
                            'face_img_status'=>2,'face_img_reason' => $res_door_authority_update['errMsg'],
                        ])->save();
                        fdump_api(['msg' =>$res_door_authority_update['errMsg'],'pigcms_id' => $pigcms_id,'device_id' => $device_id,'card_num' => $card_num,'process_id' => $process_id,'cardPrivilegeDetails' => $cardPrivilegeDetails,'line' => __LINE__],'dahua/dahuaErrorMessage',1);
                    }
                }else{
                    fdump_api([$res_open_batch['errMsg'],$pigcms_id,$device_id,__LINE__],'dahuaErrorMessage',1);
                    M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data([
                        'face_img_status'=>2,'face_img_reason' => $res_open_batch['errMsg'],
                    ])->save();
                    fdump_api(['msg' =>$res_open_batch['errMsg'],'pigcms_id' => $pigcms_id,'device_id' => $device_id,'card_num' => $card_num,'substr_id_card' => substr($bind_info['id_card'],-6),'line' => __LINE__],'dahua/dahuaErrorMessage',1);
                }
                $this->keepThread();
            }
        }else{
            $res_open_batch = $dahua_service->open_batch($person_id,$card_num,'0','0','ACTIVE',$start_time,$end_time,substr($bind_info['id_card'],-6),'1');  //开卡
            if($res_open_batch['status']){
                M('Face_user_bind_device')->where(['id'=>$bind_device_id])->data([
                    'card_status'=>1,
                    'cardid'=>$card_num,
                    'card_pass'=>substr($bind_info['id_card'],-6),
                ])->save();
                $this->keepThread();
                $cardPrivilegeDetails1 = $this->getPersonDevices($pigcms_id,$village_id);
                if (!empty($cardPrivilegeDetails1)) {
                    $cardPrivilegeDetails = $cardPrivilegeDetails1;
                }
                $this->keepThread();
                $res_door_authority_update = $dahua_service->door_authority_update($card_num,$process_id,$cardPrivilegeDetails);
                if($res_door_authority_update['status']){
                    M('Face_user_bind_device')->where(['id'=>$bind_device_id])->data([
                        'card_grant'=>1
                    ])->save();
                }else{
                    fdump_api([$res_door_authority_update['errMsg'],$pigcms_id,$device_id,__LINE__],'dahuaErrorMessage',1);
                    M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data([
                        'face_img_status'=>2,'face_img_reason' => $res_door_authority_update['errMsg'],
                    ])->save();
                    fdump_api(['msg' =>$res_door_authority_update['errMsg'],'pigcms_id' => $pigcms_id,'device_id' => $device_id,'card_num' => $card_num,'process_id' => $process_id,'line' => __LINE__],'dahua/dahuaErrorMessage',1);
                }
            }else{
                M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data([
                    'face_img_status'=>2,'face_img_reason' => $res_open_batch['errMsg'],
                ])->save();
                fdump_api(['msg' =>$res_open_batch['errMsg'],'pigcms_id' => $pigcms_id,'device_id' => $device_id,'card_num' => $card_num,'process_id' => $process_id,'line' => __LINE__],'dahua/dahuaErrorMessage',1);
            }
            $this->keepThread();
        }
        return true;
    }

    /**
     * 获取开门计划id
     * @author lijie
     * @date_time 2021/12/08
     * @param $device_id
     * @param $pigcms_id
     * @return bool|mixed
     */
    public function time_quan_tum($device_id,$pigcms_id)
    {
        $dahua_service = D('Dahua_service');
        $face_bind_about_info = M('Face_bind_about_info')->where(['bind_type'=>7,'device_type'=>88])->find();
        if($face_bind_about_info){
            $process_id = $face_bind_about_info['third_id'];
        }else{
            $res_time_quan_tum = $dahua_service->time_quan_tum();
            if($res_time_quan_tum['status']){
                $process_id = $res_time_quan_tum['process_id'];
                M('Face_bind_about_info')->data([
                    'bind_type'=>7,
                    'bind_name'=>'24小时开门',
                    'third_id'=>$process_id,
                    'device_type'=>88,
                    'add_time'=>time()
                ])->add();
            }else{
                fdump_api(['msg' =>$res_time_quan_tum['errMsg'],'pigcms_id' => $pigcms_id,'device_id' => $device_id,'line' => __LINE__],'dahua/dahuaErrorMessage',1);
                return false;
            }
        }
        return $process_id;
    }

    /**
     * 获取身份id
     * @author lijie
     * @date_time 2021/12/08
     * @param $device_id
     * @param $type
     * @param $pigcms_id
     * @return bool|mixed
     */
    public function get_identity($device_id,$type,$pigcms_id)
    {
        $dahua_service = D('Dahua_service');
        $face_bind_about_info = M('Face_bind_about_info')->where(['bind_type'=>6,'bind_id'=>$type,'device_type'=>88])->find();
        if($face_bind_about_info){
            $identity_id = $face_bind_about_info['third_id'];
        }else{
            switch ($type){
                case 0:
                    $name = '房主';
                    break;
                case 1:
                    $name = '家人';
                    break;
                case 2:
                    $name = '租客';
                    break;
                case 3:
                    $name = '更新房主';
                    break;
                case 4:
                    $name = '工作人员';
                    break;
                case 5:
                    $name = '访客';
                    break;
                case 6:
                    $name = '出入证';
                    break;
                default:
                    $name = '房主';
            }
            $res_add_identity = $dahua_service->add_identity($name,0,1,1,$name);
            if($res_add_identity['status']){
                $identity_id = $res_add_identity['identity_id'];
                M('Face_bind_about_info')->data([
                    'bind_type'=>6,
                    'bind_id'=>$type,
                    'bind_name'=>$name,
                    'third_id'=>$identity_id,
                    'device_type'=>88,
                    'add_time'=>time()
                ])->add();
            }else{
                fdump_api(['msg' =>$res_add_identity['errMsg'],'pigcms_id' => $pigcms_id,'device_id' => $device_id,'line' => __LINE__],'dahua/dahuaErrorMessage',1);
                return false;
            }
        }
        return $identity_id;
    }

    /**
     * 获取部门id
     * @author lijie
     * @date_time 2021/12/08
     * @param $device_id
     * @param $single_id
     * @param $village_id
     * @param $pigcms_id
     * @return bool|mixed
     */
    public function get_department($device_id,$single_id,$village_id,$pigcms_id)
    {
        $dahua_service = D('Dahua_service');
        $face_bind_about_info = M('Face_bind_about_info')->where(['bind_type'=>2,'bind_id'=>$single_id,'device_type'=>88])->find();
        $village_info = M('House_village')->where(['village_id'=>$village_id])->field('village_id,village_name')->find();
        if($face_bind_about_info){
            $department_id = $face_bind_about_info['third_id'];
        }else{
            if(!$single_id){
                $face_bind_about_info = M('Face_bind_about_info')->where(['bind_type'=>0,'bind_id'=>$village_id,'device_type'=>88])->find();
                if($face_bind_about_info){
                    $department_id = $face_bind_about_info['third_id'];
                }else{
                    $res_add_department = $dahua_service->add_department($village_info['village_name'],'添加部门',1);
                    if($res_add_department['status']){
                        $department_id = $res_add_department['department_id'];
                        M('Face_bind_about_info')->data([
                            'bind_type'=>0,
                            'bind_id'=>$village_id,
                            'bind_name'=>$village_info['village_name'],
                            'third_id'=>$department_id,
                            'device_type'=>88,
                            'add_time'=>time()
                        ])->add();
                    }else{
                        fdump_api(['msg' =>$res_add_department['errMsg'],'pigcms_id' => $pigcms_id,'device_id' => $device_id,'village_name'=>$village_info['village_name'],'line' => __LINE__],'dahua/dahuaErrorMessage',1);
                        return false;
                    }
                }
            }else{
                $single_name = M('House_village_single')->where(['id'=>$single_id])->getField('single_name');
                $res_add_department = $dahua_service->add_department($single_id.'_'.$single_name,'添加部门',1);
                if($res_add_department['status']){
                    $department_id = $res_add_department['department_id'];
                    M('Face_bind_about_info')->data([
                        'bind_type'=>2,
                        'bind_id'=>$single_id,
                        'bind_name'=>$single_name.'_'.$single_name,
                        'third_id'=>$department_id,
                        'device_type'=>88,
                        'add_time'=>time()
                    ])->add();
                }else{
                    fdump_api(['msg' =>$res_add_department['errMsg'],'pigcms_id' => $pigcms_id,'device_id' => $device_id,'name' => $single_id.'_'.$single_name,'line' => __LINE__],'dahua/dahuaErrorMessage',1);
                    return false;
                }
            }
        }
        return $department_id;
    }


    public function getPersonDevices($pigcms_id,$village_id) {
        $db_house_village_user_bind = D('House_village_user_bind');
        $userBind = $db_house_village_user_bind->where(['pigcms_id' => $pigcms_id])->find();
        $db_house_face_device = D('House_face_device');
        $cardPrivilegeDetails = [];
        if ($userBind['type']==4) {
            $whereFace = [
                'is_del' => 0,
                'device_type' => 88,
                'village_id' => $village_id,
            ];
            $devices = $db_house_face_device->where($whereFace)->field('device_id,device_name,device_sn,device_ip')->select();
        } elseif ($userBind['floor_id']>0) {
            $whereFace = [];
            $whereFace['_string'] = "((concat(',',floor_id,',') like '%,".$userBind['floor_id'].",%') OR (floor_id=-1)) AND is_del=0 AND village_id={$village_id}";
            $devices = $db_house_face_device->where($whereFace)->field('device_id,device_name,device_sn,device_ip')->select();
        } else {
            return $cardPrivilegeDetails;
        }
        if ($devices) {
            foreach ($devices as $item) {
                $this->keepThread();
                $deviceName = $item['device_sn'];
                $deviceIp = $item['device_ip'];
                $cardDetails = D('Dahua_service')->get_door_device($deviceName,$deviceIp);
                if (!empty($cardDetails)) {
                    $cardPrivilegeDetails = array_merge($cardDetails,$cardPrivilegeDetails);
                }
            }
        }
        fdump_api(['$pigcms_id' => $pigcms_id,'$village_id' => $village_id,'$devices' => $devices,'$cardPrivilegeDetails' => $cardPrivilegeDetails,'funs' => __FUNCTION__],'dahua/getPersonDevices',1);
        return $cardPrivilegeDetails;
    }
}