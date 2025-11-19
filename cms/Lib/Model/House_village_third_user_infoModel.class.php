<?php

/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2020/9/24 16:22
 * Class House_village_third_user_infoModel
 */
class   House_village_third_user_infoModel extends Model{

    public $request_url = 'https://www.chinapopin.com';
    public $ai_request_url = 'https://ai.chinapopin.com';
   //  public $grant_type = 'password';
    public $grant_type = 'client_credentials';

    public $plan_sub_house_third = '';
    public function keepPlanThread() {
        if (!$this->plan_sub_house_third) {
            import('@.ORG.plan.plan_sub_house_third');
            $this->plan_sub_house_third = new plan_sub_house_third();
        }
        $this->plan_sub_house_third->keepThread();
    }
    
    public function getThirdType($thirdType)
    {
        $third_type_ary = [
            'ezc_chinapopin'=>'平安小区接口',
            'dopu_cloudintercom'=>'朵普接口',
        ];
        return $third_type_ary[$thirdType] ?? '社区注册';
    }
    
    public function getThirdTypeColor($thirdType)
    {
        $third_type_ary = [
            'ezc_chinapopin'=>'#f1d39b',
            'dopu_cloudintercom'=>'#84d5eb'
        ];
        return $third_type_ary[$thirdType] ?? '#eeeeee';
    }

    // 处理数据
    /**
     * Notes: 初次同步处理数据
     * @param $village_id
     * @return mixed
     * @throws Exception
     * @author: wanzy
     * @date_time: 2020/10/10 13:20
     */
    public function handle_third_user_info($village_id, $time=0) {
        fdump_api(["初次同步处理数据[{$village_id}]",$time,microtime(true)],"third/{$village_id}/handle_third_user_info_requst_log",1);
        $db_house_village_third_user_info = M('House_village_third_user_info');
        $chinapopinOpen  = D('Config_customization')->getChinapopinOpen();
        $type = 'chinapopin_sichuan';
        if($chinapopinOpen){
            $type = 'ezc_chinapopin';
        }
        $where = [
            'village_id' => $village_id,
            'third_type' => $type,
            'img_url'    => ['neq', ''],
            'status'     => ['in', [0,3]],
        ];
        $msg = $db_house_village_third_user_info->where($where)->select();
        $db_house_village_user_bind = M('House_village_user_bind');
        $db_house_village_user_vacancy = M('House_village_user_vacancy');
        $db_house_face_img = M('House_face_img');
        $db_user = M('User');
        $now_time = time();
        if ($msg) {
            foreach ($msg as $val) {
                $this->keepPlanThread();
                // 首先查询下房间
                $address = $val['address'];
                $changeAddress = '';
                if (strpos($address, '附') !== false) {
                    $address_arr = explode('号', $val['third_jzd_dzxz']);
                    $address = array_pop($address_arr);
                    if (!$address) {
                        $address = array_pop($address_arr);
                    }
                    if ($address) {
                        $changeAddress = $address;
                    }
                } elseif (!$address && (strpos($val['third_jzd_dzxz'], (D('House_village')->getHouseAlias('single_name')?:'栋')) !== false || strpos($val['third_jzd_dzxz'], '楼') !== false)) {
                    $address_arr = explode('号', $val['third_jzd_dzxz']);
                    $address = array_pop($address_arr);
                    if (!$address) {
                        $address = array_pop($address_arr);
                    }
                    if ($address) {
                        $changeAddress = $address;
                    }
                }
                // 1栋1单元2楼1号
                $address_txt = str_replace((D('House_village')->getHouseAlias('single_name')?:'栋'), '-', $address);
                $address_txt = str_replace('号楼', '-', $address_txt);
                $address_txt = str_replace('单元', '-', $address_txt);
                $address_txt = str_replace('楼', '-', $address_txt);
                $address_txt = str_replace('层', '-', $address_txt);
                $address_txt = str_replace('号', '-', $address_txt);
                $address_arr = explode('-', $address_txt);
                $single_name = $address_arr[0];
                $floor_name = $address_arr[1];
                $layer_name = $address_arr[2];
                $room = $address_arr[3];
                if (intval($layer_name)<10) {
                    $layer_name_num = intval($layer_name) * 100;
                    $room_num_other = '0'.$room;
                } elseif (intval($layer_name)<100) {
                    $layer_name_num = intval($layer_name) * 10;
                    $room_num_other = '0'.$room;
                } else {
                    $layer_name_num = intval($layer_name);
                    $room_num_other = '0'.$room;
                }
                if (strlen($room) < 3) {
                    $threeRoom = str_pad($room, 3, "0", STR_PAD_LEFT);
                } else {
                    $threeRoom = $room;
                }
                $room_num = $layer_name_num + intval($room);
                // 查询下房间
                $roomInt = intval($room);
                $where_room['_string'] = "a.is_del=0 AND a.village_id={$village_id} AND (a.room='{$roomInt}' OR a.room='{$room}' OR a.room='{$room_num}' OR a.room='{$room_num_other}' OR a.room='{$threeRoom}') AND b.status=1 AND (b.single_name='{$single_name}' OR b.single_name='{$single_name}栋' OR b.single_name='{$single_name}号楼') ";
                $where_room['_string'] .= " AND c.status=1 AND (c.floor_name='{$floor_name}' OR c.floor_name='{$floor_name}单元') AND d.status=1 AND (d.layer_name='{$layer_name}' OR d.layer_name='0{$layer_name}' OR d.layer_name='{$layer_name}层' OR d.layer_name='0{$layer_name}层' OR d.layer_name='{$layer_name}楼' OR d.layer_name='0{$layer_name}楼')";

                $vacancy_info = $db_house_village_user_vacancy
                    ->join('as a LEFT JOIN '.C('DB_PREFIX').'house_village_single as b ON b.id=a.single_id')
                    ->join(' LEFT JOIN '.C('DB_PREFIX').'house_village_floor as c ON c.floor_id=a.floor_id')
                    ->join(' LEFT JOIN '.C('DB_PREFIX').'house_village_layer as d ON d.id=a.layer_id')
                    ->where($where_room)
                    ->field('a.pigcms_id,a.floor_id,a.single_id,a.housesize,a.layer,a.room,a.layer_id,a.usernum')
                    ->find();
                $this->keepPlanThread();

                $sex_judge = substr($val['third_zjhm'],-2,1);
                if ($sex_judge%2==0){
                    // 偶数是女
                    $val['third_xbdm'] = 1;
                }else{
                    // 奇数是男
                    $val['third_xbdm'] = 2;
                }
                if (1==$val['from']) {
                    // 如果是通知信息直接身份证号匹配
                    if ($vacancy_info &&  $vacancy_info['pigcms_id']) {
                        // 如果存在房间  按照 房间 和 姓名中姓氏及 身份证首4位和身份证尾4位进行匹配
                        $name = $val['third_xm'];
                        $surname = mb_substr($name,0,1);
                        $vacancy_id = $vacancy_info['pigcms_id'];
                        $third_zjhm = $val['third_zjhm'];
                        $first_zjhm = mb_substr($third_zjhm,0,4);
                        $tail_zjhm = mb_substr($third_zjhm,-4,4);
                        $where_bind = [];
                        $where_bind['_string'] = "`name` LIKE '{$surname}%' AND village_id={$village_id} AND vacancy_id={$vacancy_id}";
                        if (strpos($third_zjhm, '*') !== false) {
                            $where_bind['_string'] .= " AND  `id_card` LIKE '{$first_zjhm}%' AND `id_card` LIKE '%{$tail_zjhm}'";
                        } else {
                            $where_bind['id_card'] = $third_zjhm;
                        }
                        if($chinapopinOpen && $val['phone']){//如果手机号相同的也算是同一个人
                            $where_bind['_string'] = " (". $where_bind['_string'].") OR (village_id={$village_id} AND vacancy_id={$vacancy_id} AND phone=".$val['phone'].")";
                        }
                        $where_bind['status'] = ['in', [0,1,2]];
                    } else {
                        $name = $val['third_xm'];
                        $surname = mb_substr($name,0,1);
                        $where_bind = [
                            'village_id' => $village_id,
                        ];
                        $third_zjhm = $val['third_zjhm'];
                        $first_zjhm = mb_substr($third_zjhm,0,4);
                        $tail_zjhm = mb_substr($third_zjhm,-4,4);
                        $where_bind['status'] = ['in', [0,1,2]];
                        $where_bind['_string'] = "`name` LIKE '{$surname}%'";
                        if (strpos($third_zjhm, '*') !== false) {
                            $where_bind['_string'] .= " AND `id_card` LIKE '{$first_zjhm}%' AND `id_card` LIKE '%{$tail_zjhm}'";
                        } else {
                            $where_bind['id_card'] = $third_zjhm;
                        }
                        $where_bind['status'] = ['in', [0,1,2]];
                        if ($val['type'] == '04') {
                            // 工作人员
                            $where_bind['type'] = 4;
                            $where_bind['status'] = 1;
                        }
                        if ($val['type'] == '04') {
                            // 工作人员
                            $where_bind['type'] = ['in', [4,6]];
                            $where_bind['status'] = 1;
                        }
                        if ($val['type'] == '05') {
                            // 服务人员
                            $where_bind['type'] = 5;
                            $where_bind['status'] = 1;
                        }
                    }
                    $info = $db_house_village_user_bind->where($where_bind)->order('pigcms_id DESC')->field('pigcms_id,status,uid,phone,name,authentication_field')->find();
                    if (!$info) {
                        fdump_api(["三方通知查不到身份[{$village_id}]",$val['third_user_id'],$where_bind],"third/{$village_id}/notice_handle_third_user_info_sql_no_log",1);
                    }
                    $this->keepPlanThread();
                }
                else {
                    $name = $val['third_xm'];
                    $surname = mb_substr($name,0,1);
                    $third_zjhm = $val['third_zjhm'];
                    $first_zjhm = mb_substr($third_zjhm,0,4);
                    $tail_zjhm = mb_substr($third_zjhm,-4,4);
                    if (!$vacancy_info || !$vacancy_info['pigcms_id']) {
                        // 没有房间，直接 status=2
                        $third_user_data = [
                            'status' => 2,
                            'last_time' => $now_time
                        ];
                        if ($changeAddress) {
                            $third_user_data['address'] = $changeAddress;
                        }
                        $db_house_village_third_user_info->where(['third_user_id' => $val['third_user_id']])->data($third_user_data)->save();
                        $info = [];
                    } else {
                        $vacancy_id = $vacancy_info['pigcms_id'];
                        $where = [];
                        $where['status'] = ['in', [0,1,2]];
                        $where['_string'] = "`name` LIKE '{$surname}%' AND village_id={$village_id} AND vacancy_id={$vacancy_id}";
                        if (strpos($third_zjhm, '*') !== false) {
                            $where['_string'] .= " AND  `id_card` LIKE '{$first_zjhm}%' AND `id_card` LIKE '%{$tail_zjhm}'";
                        } else {
                            $where['id_card'] = $third_zjhm;
                        }
                        $info = $db_house_village_user_bind->where($where)->order('pigcms_id DESC')->field('pigcms_id,status,uid,phone,name,authentication_field')->find();
                    }
                    $this->keepPlanThread();
                }
                if (empty($info)) {
                    fdump_api(["查不到对应身份[{$village_id}]",$val['third_user_id']],"third/{$village_id}/handle_third_user_info_add_log",1);
                    // 如果是空  处理成 对应房间添加数据 如果已经存在了 业主  自动添加为家属
                    $add_user_data = [];
                    ##市级平台来的数据标记为已审核。因为市级平台有审核功能
                    if($val['ownerId']){
                        $add_user_data['status'] = 1;
                    }else{
                        $add_user_data['status'] = 2;
                    }
//                    if(!$vacancy_info && $chinapopinOpen){
//                        //智慧平安小区的定制需要新增
//                        $vacancy_info = $this->add_vacancy_info($village_id,[$roomInt,$room,$room_num,$room_num_other],$single_name,$floor_name,$layer_name);
//                        if(!$vacancy_info){
//                            fdump_api(["创建房间失败[{$village_id}]",[$roomInt,$room,$room_num,$room_num_other],$single_name,$floor_name,$layer_name],"third/{$village_id}/handle_third_user_info_add_log",1);
//                        }
//                    }
                    $vacancy_id = isset($vacancy_info['pigcms_id']) ? intval($vacancy_info['pigcms_id']) : 0;
                    if ($vacancy_id > 0 || $val['type'] == '04' || $chinapopinOpen) {
                        if($val['type'] != '04'){
                            $add_user_data['village_id'] = $village_id;
                            if ($vacancy_id > 0) {
                                $add_user_data['floor_id'] = $vacancy_info['floor_id'];
                                $add_user_data['single_id'] = $vacancy_info['single_id'];
                                $add_user_data['layer_id'] = $vacancy_info['layer_id'];
                                $add_user_data['vacancy_id'] = $vacancy_id;
                                $add_user_data['housesize'] = $vacancy_info['housesize'];
                                $add_user_data['layer_num'] = $vacancy_info['layer'];
                                $add_user_data['room_addrss'] = $vacancy_info['room'];
                            }
                            $user_name = str_replace('*', '', $name);
                            $add_user_data['name'] = $user_name;
                            $add_user_data['address'] = $val['address'];
                            $add_user_data['usernum'] = rand(0,99999) . '-' . time().'-t1';
                            $add_user_data['id_card'] = $third_zjhm;
                            if ($vacancy_id > 0) {
                                $room_pigcms_id = $db_house_village_user_bind->where(['vacancy_id' => $vacancy_id,'type'=> ['in', [0,3], 'status'=>[0,1,2]]])->order('status ASC')->getField('pigcms_id');
                            } else {
                                $room_pigcms_id = 0;
                            }
                            if ($chinapopinOpen && isset($add_user_data['status']) && intval($add_user_data['status']) == 2) {
                                // 对接省级智慧平安小区如果是待审核的 默认统一为业主
                                $add_user_data['type'] = 0;
                            } elseif($chinapopinOpen && $val['type'] == '02'){//租户
                                $add_user_data['type'] =2;
                            }elseif ($room_pigcms_id) {
                                $add_user_data['parent_id'] = $room_pigcms_id;
                                $add_user_data['type'] =1;
                                if($val['type'] == '05'){
                                    $add_user_data['status'] = 1;
                                    $add_user_data['type'] = 5;
                                    $add_user_data['property_starttime'] = $val['visitTime'];
                                    $add_user_data['property_endtime'] = $val['effectiveVisitTime'];
                                }
                            } else {
                                // 不存在业主直接替换
                                if(!$chinapopinOpen){
                                    $add_user_data['usernum'] = $vacancy_info['usernum'].'-t1';
                                }
                                $add_user_data['type'] =0;
                            }
                        }else{	##服务人员使用出入证用户
                            $add_user_data = [
                                'village_id' => $village_id, 'id_card' => $val['third_zjhm'],
                                'name' => str_replace('*', '', $val['third_xm']),
                                'type' => 6, 'usernum' => rand(1000, 9999) . '-' . time(),
                            ];
                            $add_user_data['status'] = 1;
                            $add_user_data['property_starttime'] = $val['visitTime'];
                            $add_user_data['property_endtime'] = $val['effectiveVisitTime'];
                        }
                        $this->keepPlanThread();

                        $add_user_data['add_time'] = time();
                        if ($val['third_id']) {
                            $add_user_data['third_id'] = $val['third_id'];
                        }
                        if ($val['third_ryid']) {
                            $add_user_data['third_id'] = $val['third_ryid'];
                        }
                        if ($val['third_xqryid']) {
                            $add_user_data['third_xqryid'] = $val['third_xqryid'];
                        }
                        // 查询下手机号是否已经存在在这个房间(非工作人员不论所处状态)
                        if ($val['phone']) {
                            $where_replace = ['vacancy_id' => $vacancy_id, 'type' => ['neq', 4], 'phone' => $val['phone']];
                            $where_replace['status'] = ['not in', [3,4]];
                            $replace_pigcms_id = $db_house_village_user_bind->where($where_replace)->getField('pigcms_id');
                            // 手机号存在且没有重复数据局
                            if (!$replace_pigcms_id) {
                                $add_user_data['phone'] = $val['phone'];
                            }
                        }
                        if ($add_user_data['phone']) {
                            $uid = $db_user->where(['phone' => $add_user_data['phone'],'status'=>1])->getField('uid');
                            if (!$uid) {
                                $add_user = [
                                    'nickname' => $add_user_data['name']?$add_user_data['name']: substr($add_user_data['phone'],0,3).'****'.substr($add_user_data['phone'],7,4),
                                    'phone' => $add_user_data['phone']
                                ];
                                $uid = $db_user->data($add_user)->add();
                            }
                        } elseif($add_user_data['name']) {
                            $add_user = [
                                'nickname' => $add_user_data['name'],
                            ];
                            $uid = $db_user->data($add_user)->add();
                        }
                        if (!$uid) {
                            fdump_api(["没有平台用户uid[{$village_id}]",$val['third_user_id'],$add_user_data,$uid],"third/{$village_id}/err_handle_third_user_info_log",1);
                            // 没有用户uid 循环继续
                            continue;
                        }
                        $add_user_data['uid'] = $uid;
                        if($chinapopinOpen){
                            $add_user_data['third_type'] = $val['third_type'];
                        }
                        $add_pigcms_id = $db_house_village_user_bind->data($add_user_data)->add();
                        fdump_api(["添加住户身份[{$village_id}]",$val['third_user_id'],$add_pigcms_id,$add_user_data,$uid],"third/{$village_id}/handle_third_user_info_add_log",1);
                        if ($add_pigcms_id) {
                            $add_user_data['pigcms_id'] = $add_pigcms_id;
                        } else {
                            fdump_api(["添加住户身份失败[{$village_id}]",$val['third_user_id'],$add_pigcms_id,$add_user_data,$db_house_village_user_bind->getDbError()],"third/{$village_id}/err_handle_third_user_info_add_log",1);
                            continue;
                        }
                        $where_face_img = ['uid' => $uid, 'status' => ['in', [0, 3]], 'img_url' => ['neq', '']];
                        $face_id = $db_house_face_img->where($where_face_img)->order('add_time ASC,id ASC')->getField('id');
                        if ($face_id) {
                            $set = ['img_url' => $val['img_url'], 'last_time' => $now_time, 'status' => 0];
                            $db_house_face_img->where(['id' => $face_id])->data($set)->save();
                            $logParams = [
                                'msg' => "更换图片[{$village_id}]", 'face_id' => $face_id,
                                'add_pigcms_id' => $add_pigcms_id, 'add_user_data' => $add_user_data, 'uid' => $uid,
                                'set' => $set, 'val' => $val, 'vacancy_id' => $vacancy_id
                            ];
                            fdump_api($logParams,"third/{$village_id}/handle_third_user_info_add_log",1);
                        } elseif ($uid) {
                            $add = ['uid' => $uid, 'add_time' => $now_time, 'img_url' => $val['img_url'], 'status' => 0];
                            $logParams = [
                                'msg' => "添加图片[{$village_id}]", 'third_user_id' => $val['third_user_id'], 
                                'add_pigcms_id' => $add_pigcms_id, 'add_user_data' => $add_user_data, 'uid' => $uid,
                                'add' => $add, 'val' => $val, 'vacancy_id' => $vacancy_id
                            ];
                            fdump_api($logParams,"third/{$village_id}/handle_third_user_info_add_log",1);
                            $db_house_face_img->where(['id' => $face_id])->data($add)->add();
                        } else {
                            $logParams = [
                                'msg' => "添加住户身份[{$village_id}]", 'third_user_id' => $val['third_user_id'],
                                'add_pigcms_id' => $add_pigcms_id, 'add_user_data' => $add_user_data, 'uid' => $uid,
                                'val' => $val, 'vacancy_id' => $vacancy_id,
                            ];
                            fdump_api($logParams,"third/{$village_id}/handle_third_user_info_add_log",1);
                        }
                        /**
                         * 同步数据成功
                         */
                        $third_user_data = ['status' => 1, 'last_time' => $now_time];
                        if ($changeAddress) {
                            $third_user_data['address'] = $changeAddress;
                        }
                        $db_house_village_third_user_info->where(['third_user_id' => $val['third_user_id']])->data($third_user_data)->save();

                        ##如果状态为审核通过的，则直接同步下照片。适用于市级平台
                        if($add_user_data['status'] == 1 && $add_user_data['pigcms_id'] && !$chinapopinOpen){
                            if(!$val['ownerId']){
                                $ywlsh = trim($val['ywlsh']) ? trim($val['ywlsh']) : trim($val['third_xqryid']);
                                $this->chinapopinCheck($village_id,$ywlsh);
                            }
                            $this->keepPlanThread();
                            D('House_face_img')->house_user_face_device_info($uid, $add_user_data['pigcms_id']);
                        }
                        $this->keepPlanThread();

                    } else {
                        // 没有对应人员
                        $third_user_data = ['status' => 2, 'last_time' => $now_time];
                        if ($changeAddress) {
                            $third_user_data['address'] = $changeAddress;
                        }
                        $db_house_village_third_user_info->where(['third_user_id' => $val['third_user_id']])->data($third_user_data)->save();
                        fdump_api(["没有对应人员[{$village_id}]",$val['third_user_id'],$third_user_data],"third/{$village_id}/notice_handle_third_user_info_no_log",1);
                    }
                } elseif ($info) {
                    fdump_api(["存在对应身份[{$village_id}]",$val['third_user_id'],$info],"third/{$village_id}/handle_third_user_info_set_log",1);
                    // 同步记录下信息
                    if ($val['third_xbdm']==1) {
                        $sex='女';
                    }elseif ($val['third_xbdm']==2) {
                        $sex='男';
                    }
                    $set_bind = [];
                    if ($info && $info['authentication_field'] && $sex) {
                        $authentication_field = unserialize($info['authentication_field']);
                        if ($authentication_field['sex']) {
                            // 性别同步更新下
                            $authentication_field['sex']['value'] = $sex;
                            $set_bind['authentication_field'] = serialize($authentication_field);
                        }
                    }
                    if ($val['third_id']) {
                        $set_bind['third_id'] = $val['third_id'];
                    }
                    if ($val['third_ryid']) {
                        $set_bind['third_id'] = $val['third_ryid'];
                    }
                    if ($val['third_xqryid']) {
                        $set_bind['third_xqryid'] = $val['third_xqryid'];
                    }
                    if($val['third_xm']){
                        $set_bind['name'] = $val['third_xm'];
                    }

                    #设置开门时间 start
                    if ($val['visitTime']) {
                        $set_bind['property_starttime'] = $val['visitTime'];
                    }
                    if ($val['effectiveVisitTime']) {
                        $set_bind['property_endtime'] = $val['effectiveVisitTime'];
                    }
                    #设置开门时间 end

                    if ($set_bind) {
                        $db_house_village_user_bind->where(['pigcms_id' => $info['pigcms_id']])->data($set_bind)->save();
                    }
                    if ($info['uid']>0) {
                        $uid = $info['uid'];
                    } else {
                        if ($info['phone']) {
                            $uid = $db_user->where(['phone' => $info['phone'],'status'=>1])->getField('uid');
                            if (!$uid) {
                                $data_user = array(
                                    'nickname' 	=> $info['name'] ? $info['name'] : (substr($info['phone'],0,3).'****'.substr($info['phone'],7,4)),
                                    'phone' 	=> $info['phone'],
                                    'source' 	=> 'houseautoreg_sichuan',
                                );
                                $reg_result = D('User')->autoreg($data_user);
                                $uid = $reg_result['uid'];
                            }
                        } elseif ($info['name']) {
                            $data_user = array(
                                'nickname' 	=> $info['name'] ? $info['name'] : (substr($info['phone'],0,3).'****'.substr($info['phone'],7,4)),
                                'source' 	=> 'houseautoreg_sichuan',
                            );
                            $reg_result = D('User')->autoreg($data_user);
                            $uid = $reg_result['uid'];
                        }
                    }
                    if (!$uid) {
                        fdump_api(["没有uid[{$village_id}]",$val['third_user_id'],$info,$set_bind],"third/{$village_id}/err_handle_third_user_info_log",1);
                        // 没有用户uid 循环继续
                        if (1==$val['from'] && $info['status']!=2) {
                            // 用户端传递的信息如果是本来就是审核通过的自动反馈给公安
                            $ywlsh = trim($val['ywlsh']) ? trim($val['ywlsh']) : trim($val['third_xqryid']);
                            $this->keepPlanThread();
                            if ($ywlsh) {
                                if(!$val['ownerId']){
                                    $this->apply_access_callback($village_id,$info['pigcms_id'], 1,'', $ywlsh, $val['third_user_id']);
                                }
                                D('House_face_img')->house_user_face_device_info($uid,$info['pigcms_id']);
                            }
                        }
                        continue;
                    }
                    $where_face_img = [
                        'uid' => $uid,
                        'status' => ['in',[0, 3]],
                        'img_url' => ['neq', '']
                    ];
                    $face_id = $db_house_face_img->where($where_face_img)->order('add_time ASC,id ASC')->getField('id');
                    if ($face_id) {
                        $set = [
                            'img_url'=>$val['img_url'],
                            'last_time'=>$now_time,
                            'status' => 0
                        ];
                        $db_house_face_img->where(['id' => $face_id])->data($set)->save();
                        $logParams = [
                            'msg' => "添加住户身份[{$village_id}]", 'third_user_id' => $val['third_user_id'],
                            'add_pigcms_id' => $add_pigcms_id, 'add_user_data' => $add_user_data, 'uid' => $uid,
                            'set' => $set, 'val' => $val, 'info' => $info,
                        ];
                        fdump_api($logParams,"third/{$village_id}/handle_third_user_info_add_log",1);
                    }elseif($uid) {
                        $add = ['uid' => $uid, 'add_time' => $now_time, 'img_url' => $val['img_url'], 'status' => 0];
                        $db_house_face_img->where(['id' => $face_id])->data($add)->add();
                        if (!$info['uid']) {
                            $db_house_village_user_bind->where(['pigcms_id' => $info['pigcms_id']])->setField('uid', $uid);
                        }
                        $logParams = [
                            'msg' => "添加住户身份[{$village_id}]", 'third_user_id' => $val['third_user_id'],
                            'add_pigcms_id' => $add_pigcms_id, 'add_user_data' => $add_user_data, 'uid' => $uid,
                            'add' => $add, 'val' => $val, 'info' => $info,
                        ];
                        fdump_api($logParams, "third/{$village_id}/handle_third_user_info_add_log", 1);
                    } else {
                        $logParams = [
                            'msg' => "没有uid[{$village_id}]", 'third_user_id' => $val['third_user_id'],
                            'add_pigcms_id' => $add_pigcms_id, 'add_user_data' => $add_user_data, 'uid' => $uid,
                            'val' => $val, 'info' => $info,
                        ];
                        fdump_api($logParams, "third/{$village_id}/handle_third_user_info_add_log", 1);
                    }
                    if (1==$val['from'] && $info['status']!=2) {
                        // 用户端传递的信息如果是本来就是审核通过的自动反馈给公安
                        $ywlsh = trim($val['ywlsh']) ? trim($val['ywlsh']) : trim($val['third_xqryid']);
                        if ($ywlsh) {
                            $this->keepPlanThread();
                            if(!$val['ownerId']){
                                $this->apply_access_callback($village_id,$info['pigcms_id'], 1,'', $ywlsh, $val['third_user_id']);
                            }
                            D('House_face_img')->house_user_face_device_info($uid,$info['pigcms_id']);
                        }
                    }
                    /**
                     * 同步数据成功
                     */
                    $third_user_data = [
                        'status' => 1,
                        'last_time' => $now_time
                    ];
                    if ($changeAddress) {
                        $third_user_data['address'] = $changeAddress;
                    }
                    $db_house_village_third_user_info->where(['third_user_id' => $val['third_user_id']])->data($third_user_data)->save();
                }
                file_put_contents($time,time());
            }
        } else {
            fdump_api(["无需处理数据[{$village_id}]",$msg,$db_house_village_third_user_info->getDbError()],"third/{$village_id}/notice_handle_third_user_info_log",1);
        }

        fdump_api(["执行结束[{$village_id}]",microtime(true)],"third/{$village_id}/handle_third_user_info_requst_log",1);
        return true;
    }

    /**
     * Notes: 重新同步处理数据
     * @param int $village_id
     * @param int $times
     * @return mixed
     * @throws Exception
     * @author: wanzy
     * @date_time: 2020/10/10 13:22
     */
    public function handle_third_user_info_resynchronize($village_id, $times=0, $time=0) {
        fdump_api(["重新同步处理数据[{$village_id}]",$village_id, $times, $time,microtime(true)],"third/{$village_id}/handle_third_user_info_resynchronize_log",1);
        $db_house_village_third_user_info = M('House_village_third_user_info');
        $chinapopinOpen  = D('Config_customization')->getChinapopinOpen();
        $type = 'chinapopin_sichuan';
        if($chinapopinOpen){
            $type = 'ezc_chinapopin';
        }
        $where = [
            'village_id' => $village_id,
            'third_type' => $type,
            'img_url'    => ['neq', ''],
            'status'     => ['neq', 1],
        ];
        if ($times==0) {
            $max_times_info = $db_house_village_third_user_info->where($where)->field('MIN(times) as max_times')->find();
            if ($max_times_info) {
                $times = $max_times_info['max_times'];
            }
        }
        $where['times'] = $times;
        $times_new = $times + 1;
        $msg = $db_house_village_third_user_info->where($where)->select();
        if (empty($msg)) {
            $where['times'] = ['gt', $times];
            $msg = $db_house_village_third_user_info->where($where)->select();
        }
        $db_house_village_user_bind = M('House_village_user_bind');
        $db_house_village_user_vacancy = M('House_village_user_vacancy');
        $db_house_face_img = M('House_face_img');
        $db_user = M('User');
        $now_time = time();
        if ($msg) {
            foreach ($msg as $val) {
                $this->keepPlanThread();
                $db_house_village_third_user_info->where(['third_user_id' => $val['third_user_id']])->setField('times', $times_new);
                if ($val['status']==1 && $val['check_status']==0) {
                    if (1==$val['from']) {
                        // 用户端传递的信息如果是本来就是审核通过的自动反馈给公安
                        $where_bind_other = [];
                        if ($val['third_xqryid']) {
                            $where_bind_other['third_xqryid'] = $val['third_xqryid'];
                            $bind_other = D("House_village_user_bind")->where($where_bind_other)->field('pigcms_id,status')->find();
                            fdump_api(["用户端传递的信息如果是本来就是审核通过的自动反馈给公安[{$village_id}]",$bind_other],"third/{$village_id}/bind_other_log",1);
                            if ($bind_other && $bind_other['status']!=2) {
                                $this->keepPlanThread();
                                $this->apply_access_callback($village_id,$bind_other['pigcms_id'], 1,'', $val['third_xqryid'], $val['third_user_id']);
                                $bind_other_uid = $bind_other['uid'] ? intval($bind_other['uid']) : 0;
                                $bind_other_pigcms_id = $bind_other['pigcms_id'] ? intval($bind_other['pigcms_id']) : 0;
                                D('House_face_img')->house_user_face_device_info($bind_other_uid,$bind_other_pigcms_id);
//                                if ($bind_other) {
//                                    $bind_other_uid = $bind_other['uid'] ? intval($bind_other['uid']) : 0;
//                                    $bind_other_pigcms_id = $bind_other['pigcms_id'] ? intval($bind_other['pigcms_id']) : 0;
//                                    D('House_village_face_door')->create_village_plan($village_id,'house_user_face_device_info', $bind_other_uid, 'all', $bind_other_pigcms_id);
//                                }
                            }
                        }
                        continue;
                    }
                }
                $address = $val['address'];
                $changeAddress = '';
                if (strpos($address, '附') !== false) {
                    $address_arr = explode('号', $val['third_jzd_dzxz']);
                    $address = array_pop($address_arr);
                    if (!$address) {
                        $address = array_pop($address_arr);
                    }
                    if ($address) {
                        $changeAddress = $address;
                    }
                } elseif (!$address && (strpos($val['third_jzd_dzxz'], (D('House_village')->getHouseAlias('single_name')?:'栋')) !== false || strpos($val['third_jzd_dzxz'], '楼') !== false)) {
                    $address_arr = explode('号', $val['third_jzd_dzxz']);
                    $address = array_pop($address_arr);
                    if (!$address) {
                        $address = array_pop($address_arr);
                    }
                    if ($address) {
                        $changeAddress = $address;
                    }
                }
                // 1栋1单元2楼1号
                $address_txt = str_replace((D('House_village')->getHouseAlias('single_name')?:'栋'), '-', $address);
                $address_txt = str_replace('号楼', '-', $address_txt);
                $address_txt = str_replace('单元', '-', $address_txt);
                $address_txt = str_replace('楼', '-', $address_txt);
                $address_txt = str_replace('层', '-', $address_txt);
                $address_txt = str_replace('号', '-', $address_txt);
                $address_arr = explode('-', $address_txt);
                $single_name = $address_arr[0];
                $floor_name = $address_arr[1];
                $layer_name = $address_arr[2];
                $room = $address_arr[3];
                if (intval($layer_name)<10) {
                    $layer_name_num = intval($layer_name) * 100;
                    $room_num_other = '0'.$room;
                } elseif (intval($layer_name)<100) {
                    $layer_name_num = intval($layer_name) * 10;
                    $room_num_other = '0'.$room;
                } else {
                    $layer_name_num = intval($layer_name);
                    $room_num_other = '0'.$room;
                }
                if (strlen($room) < 3) {
                    $threeRoom = str_pad($room, 3, "0", STR_PAD_LEFT);
                } else {
                    $threeRoom = $room;
                }
                $room_num = $layer_name_num + intval($room);
                // 查询下房间
                $roomInt = intval($room);
                $where_room['_string'] = "a.is_del=0 AND a.village_id={$village_id} AND (a.room='{$roomInt}' OR a.room='{$room}' OR a.room='{$room_num}' OR a.room='{$room_num_other}' OR a.room='{$threeRoom}') AND b.status=1 AND (b.single_name='{$single_name}' OR b.single_name='{$single_name}栋' OR b.single_name='{$single_name}号楼') ";
                $where_room['_string'] .= " AND c.status=1 AND (c.floor_name='{$floor_name}' OR c.floor_name='{$floor_name}单元') AND d.status=1 AND (d.layer_name='{$layer_name}' OR d.layer_name='0{$layer_name}' OR d.layer_name='{$layer_name}层' OR d.layer_name='0{$layer_name}层' OR d.layer_name='{$layer_name}楼' OR d.layer_name='0{$layer_name}楼')";

                $vacancy_info = $db_house_village_user_vacancy
                    ->join('as a LEFT JOIN '.C('DB_PREFIX').'house_village_single as b ON b.id=a.single_id')
                    ->join(' LEFT JOIN '.C('DB_PREFIX').'house_village_floor as c ON c.floor_id=a.floor_id')
                    ->join(' LEFT JOIN '.C('DB_PREFIX').'house_village_layer as d ON d.id=a.layer_id')
                    ->where($where_room)
                    ->field('a.pigcms_id,a.floor_id,a.single_id,a.housesize,a.layer,a.room,a.layer_id,a.usernum')
                    ->find();
                $this->keepPlanThread();
                fdump_api(["对应房屋[{$village_id}]",$vacancy_info, 'sql' => $db_house_village_user_vacancy->_sql()],"third/{$village_id}/handle_third_user_info_resynchronize_log",1);


                $sex_judge = substr($val['third_zjhm'],-2,1);
                if ($sex_judge%2==0){
                    // 偶数是女
                    $val['third_xbdm'] = 1;
                }else{
                    // 奇数是男
                    $val['third_xbdm'] = 2;
                }
                if (1==$val['from']) {
                    // 如果是通知信息直接身份证号匹配
                    if ($vacancy_info &&  $vacancy_info['pigcms_id']) {
                        // 如果存在房间  按照 房间 和 姓名中姓氏及 身份证首4位和身份证尾4位进行匹配
                        $name = $val['third_xm'];
                        $surname = mb_substr($name,0,1);
                        $vacancy_id = $vacancy_info['pigcms_id'];
                        $third_zjhm = $val['third_zjhm'];
                        $first_zjhm = mb_substr($third_zjhm,0,4);
                        $tail_zjhm = mb_substr($third_zjhm,-4,4);
                        $where_bind = [
                            'village_id' => $village_id,
                            'vacancy_id' => $vacancy_id,
                        ];
                        $where_bind['_string'] = "`name` LIKE '{$surname}%'";
                        if (strpos($third_zjhm, '*') !== false) {
                            $where_bind['_string'] .= " AND `id_card` LIKE '{$first_zjhm}%' AND `id_card` LIKE '%{$tail_zjhm}'";
                        } else {
                            $where_bind['id_card'] = $third_zjhm;
                        }
                        $where_bind['status'] = ['in', [0,1,2]];
                    } else {
                        $where_bind = [
                            'village_id' => $village_id,
                        ];
                        $name = $val['third_xm'];
                        $surname = mb_substr($name,0,1);
                        $third_zjhm = $val['third_zjhm'];
                        $first_zjhm = mb_substr($third_zjhm,0,4);
                        $tail_zjhm = mb_substr($third_zjhm,-4,4);
                        $where_bind['status'] = ['in', [0,1,2]];
                        $where_bind['_string'] = "`name` LIKE '{$surname}%'";
                        if (strpos($third_zjhm, '*') !== false) {
                            $where_bind['_string'] .= " AND `id_card` LIKE '{$first_zjhm}%' AND `id_card` LIKE '%{$tail_zjhm}'";
                        } else {
                            $where_bind['id_card'] = $third_zjhm;
                        }
                        if ($val['type'] == '03') {
                            // 物业人员
                            $where_bind['type'] = 4;
                            $where_bind['status'] = 1;
                        }
                        if ($val['type'] == '04') {
                            // 工作人员
                            $where_bind['type'] = ['in', [4,6]];
                            $where_bind['status'] = 1;
                        }
                        if ($val['type'] == '05') {
                            // 服务人员
                            $where_bind['type'] = 5;
                            $where_bind['status'] = 1;
                        }
                    }
                    $info = $db_house_village_user_bind->where($where_bind)->order('pigcms_id DESC')->field('pigcms_id,status,uid,phone,name,authentication_field')->find();
                    if (!$info) {
                        fdump_api(["无对应身份[{$village_id}]",$val['third_user_id'],$where_bind,$db_house_village_user_bind->_sql()],"third/{$village_id}/no_house_role_user_log",1);
                    }
                    $this->keepPlanThread();
                } else {
                    $name = $val['third_xm'];
                    $surname = mb_substr($name,0,1);
                    $third_zjhm = $val['third_zjhm'];
                    $first_zjhm = mb_substr($third_zjhm,0,4);
                    $tail_zjhm = mb_substr($third_zjhm,-4,4);
                    if (!$vacancy_info || !$vacancy_info['pigcms_id']) {
                        // 没有房间，直接 status=2
                        $third_user_data = [
                            'status' => 2,
                            'last_time' => $now_time
                        ];
                        if ($changeAddress) {
                            $third_user_data['address'] = $changeAddress;
                        }
                        $db_house_village_third_user_info->where(['third_user_id' => $val['third_user_id']])->data($third_user_data)->save();
                        $info = [];
                    } else {
                        $vacancy_id = $vacancy_info['pigcms_id'];
                        $where = [];
                        $where['status'] = ['in', [0,1,2]];
                        $where['_string'] = "`name` LIKE '{$surname}%' AND village_id={$village_id} AND vacancy_id={$vacancy_id}";
                        if (strpos($third_zjhm, '*') !== false) {
                            $where['_string'] .= " AND  `id_card` LIKE '{$first_zjhm}%' AND `id_card` LIKE '%{$tail_zjhm}'";
                        } else {
                            $where['id_card'] = $third_zjhm;
                        }
                        $info = $db_house_village_user_bind->where($where)->order('pigcms_id DESC')->field('pigcms_id,status,uid,phone,name,authentication_field')->find();
                    }
                    $this->keepPlanThread();
                }
                if (empty($info)) {
                    // 如果是空  处理成 对应房间添加数据 如果已经存在了 业主  自动添加为家属
                    $add_user_data = [];
                    if($val['ownerId']){
                        $add_user_data['status'] = 1;
                    }else{
                        $add_user_data['status'] = 2;
                    }
                    $vacancy_id = isset($vacancy_info['pigcms_id']) ? intval($vacancy_info['pigcms_id']) : 0;
                    if ($vacancy_id > 0 || $val['type'] == '04' || $chinapopinOpen) {
                        if($val['type'] != '04') {
                            $add_user_data['village_id'] = $village_id;
                            if ($vacancy_id > 0) {
                                $add_user_data['floor_id'] = $vacancy_info['floor_id'];
                                $add_user_data['single_id'] = $vacancy_info['single_id'];
                                $add_user_data['layer_id'] = $vacancy_info['layer_id'];
                                $add_user_data['vacancy_id'] = $vacancy_id;
                                $add_user_data['housesize'] = $vacancy_info['housesize'];
                                $add_user_data['layer_num'] = $vacancy_info['layer'];
                                $add_user_data['room_addrss'] = $vacancy_info['room'];
                            }
                            $user_name = str_replace('*', '', $name);
                            $add_user_data['name'] = $user_name;
                            $add_user_data['address'] = $val['address'];
                            $add_user_data['usernum'] = rand(0, 99999) . '-' . time() . '-t1';
                            $add_user_data['id_card'] = $third_zjhm;
                            if ($vacancy_id > 0) {
                                $room_pigcms_id = $db_house_village_user_bind->where(['vacancy_id' => $vacancy_id, 'type' => ['in', [0, 3], 'status' => [0, 1, 2]]])->order('status ASC')->getField('pigcms_id');
                            } else {
                                $room_pigcms_id = 0;
                            }
                            if ($room_pigcms_id) {
                                $add_user_data['parent_id'] = $room_pigcms_id;
                                $add_user_data['type'] = 1;
                                if ($val['type'] == '05') {
                                    $add_user_data['status'] = 1;
                                    $add_user_data['type'] = 5;
                                    $add_user_data['property_starttime'] = $val['visitTime'];
                                    $add_user_data['property_endtime'] = $val['effectiveVisitTime'];
                                }
                            } else {
                                // 不存在业主直接替换
                                $add_user_data['usernum'] = $vacancy_info['usernum'] . '-t1';
                                $add_user_data['type'] = 0;
                            }
                        }else{	##服务人员使用出入证用户
                            $add_user_data = [
                                'village_id' => $village_id,
                                'id_card'    => $val['third_zjhm'],
                                'name'       => str_replace('*', '', $val['third_xm']),
                                'type'       => 6,
                                'usernum'=>rand(1000,9999).'-'.time(),
                            ];
                            $add_user_data['status'] = 1;
                            $add_user_data['property_starttime'] = $val['visitTime'];
                            $add_user_data['property_endtime'] = $val['effectiveVisitTime'];
                        }
                        $add_user_data['add_time'] = time();
                        if ($val['third_id']) {
                            $add_user_data['third_id'] = $val['third_id'];
                        }
                        if ($val['third_ryid']) {
                            $add_user_data['third_id'] = $val['third_ryid'];
                        }
                        if ($val['third_xqryid']) {
                            $add_user_data['third_xqryid'] = $val['third_xqryid'];
                        }
                        // 查询下手机号是否已经存在在这个房间(非工作人员不论所处状态)
                        if ($val['phone']) {
                            $where_replace = [
                                'vacancy_id' => $vacancy_id,
                                'type' => ['neq', 4],
                                'phone' => $val['phone']
                            ];
                            $where_replace['status'] = ['not in', [3,4]];
                            $replace_pigcms_id = $db_house_village_user_bind->where($where_replace)->getField('pigcms_id');
                            // 手机号存在且没有重复数据局
                            if (!$replace_pigcms_id) {
                                $add_user_data['phone'] = $val['phone'];
                            }
                        }
                        if ($add_user_data['phone']) {
                            $uid = $db_user->where(['phone' => $add_user_data['phone'],'status'=>1])->getField('uid');
                            if (!$uid) {
                                $add_user = [
                                    'nickname' => $add_user_data['name']?$add_user_data['name']: substr($add_user_data['phone'],0,3).'****'.substr($add_user_data['phone'],7,4),
                                    'phone' => $add_user_data['phone']
                                ];
                                $uid = $db_user->data($add_user)->add();
                            }
                        } elseif($add_user_data['name']) {
                            $add_user = [
                                'nickname' => $add_user_data['name'],
                            ];
                            $uid = $db_user->data($add_user)->add();
                        }
                        $this->keepPlanThread();
                        if (!$uid) {
                            fdump_api(["无对应身份，uid[{$village_id}]",$val['third_user_id'],$add_user_data,$uid],"third/{$village_id}/no_user_uid_log",1);
                            // 没有用户uid 循环继续
                            continue;
                        }
                        $add_user_data['uid'] = $uid;
                        if($chinapopinOpen){
                            $add_user_data['third_type'] = $val['third_type'];
                        }
                        fdump_api(["添加身份前[{$village_id}]",$add_user_data],"third/{$village_id}/handle_third_user_info_resynchronize_log",1);
                        $add_pigcms_id = $db_house_village_user_bind->data($add_user_data)->add();
                        if ($add_pigcms_id) {
                            $add_user_data['pigcms_id'] = $add_pigcms_id;
                        } else {
                            fdump_api(["添加失败[{$village_id}]",$add_pigcms_id,$db_house_village_user_bind->getDbError()],"third/{$village_id}/handle_third_user_info_resynchronize_log",1);
                            continue;
                        }
                        $where_face_img = [
                            'uid' => $uid,
                            'status' => ['in',[0, 3]],
                            'img_url' => ['neq', '']
                        ];
                        $face_id = $db_house_face_img->where($where_face_img)->order('add_time ASC,id ASC')->getField('id');

                        fdump_api(["添加失败[{$village_id}]",$face_id,$where_face_img],"third/{$village_id}/handle_third_user_info_resynchronize_log",1);
                        if ($face_id) {
                            $set = [
                                'img_url'=>$val['img_url'],
                                'last_time'=>$now_time,
                                'status' => 0
                            ];
                            $add_face_id = $db_house_face_img->where(['id' => $face_id])->data($set)->save();
                            if (!$add_face_id) {
                                fdump_api(["添加失败[{$village_id}]",$db_house_face_img->getDbError()],"third/{$village_id}/err_house_face_img_add_log",1);
                            }
                            $logParams = [
                                'msg' => "替换图片[{$village_id}]", 'add_face_id' => $add_face_id, 'face_id' => $face_id, 
                                'add_user_data' => $add_user_data, 'uid' => $uid,
                                'set' => $set, 'val' => $val, 'vacancy_id' => $vacancy_id,
                            ];
                            fdump_api($logParams, "third/{$village_id}/house_face_img_add_log", 1);
                        } elseif ($uid) {
                            $add = [
                                'uid'=>$uid,
                                'add_time'=> $now_time,
                                'img_url'=>$val['img_url'],
                                'status'=>0,
                            ];
                            $add_face_id = $db_house_face_img->data($add)->add();
                            if (!$add_face_id) {
                                fdump_api(["保存图片失败[{$village_id}]",$add_face_id,$db_house_face_img->getDbError()],"third/{$village_id}/err_house_face_img_add_log",1);
                            }
                            $logParams = [
                                'msg' => "保存图片[{$village_id}]", 'add_face_id' => $add_face_id, 'now_time' => $now_time,
                                'add_user_data' => $add_user_data, 'uid' => $uid, 'add' => $add, 'val' => $val, 'vacancy_id' => $vacancy_id,
                            ];
                            fdump_api($logParams, "third/{$village_id}/house_face_img_add_log", 1);
                        } else {
                            $logParams = [
                                'msg' => "保存图片[{$village_id}]", 'third_user_id' => $val['third_user_id'], 'now_time' => $now_time,
                                'add_user_data' => $add_user_data, 'uid' => $uid, 'val' => $val, 'vacancy_id' => $vacancy_id,
                            ];
                            fdump_api($logParams, "third/{$village_id}/house_face_img_add_log", 1);
                        }
                        /**
                         * 同步数据成功
                         */
                        $third_user_data = [
                            'status' => 1,
                            'last_time' => $now_time
                        ];
                        if ($changeAddress) {
                            $third_user_data['address'] = $changeAddress;
                        }
                        $db_house_village_third_user_info->where(['third_user_id' => $val['third_user_id']])->data($third_user_data)->save();

                        $this->keepPlanThread();
                        $ids = D('House_village_face_door')->create_village_plan($village_id,'house_user_face_device_info', $uid, 'all', $add_pigcms_id);
                        fdump_api(["保存图片[{$village_id}]",$val['third_user_id'],$uid, $add_pigcms_id,$ids,$third_user_data],"third/{$village_id}/handle_third_house_face_img_log",1);
                    } else {
                        // 没有对应人员
                        $third_user_data = [
                            'status' => 2,
                            'last_time' => $now_time
                        ];
                        if ($changeAddress) {
                            $third_user_data['address'] = $changeAddress;
                        }
                        $db_house_village_third_user_info->where(['third_user_id' => $val['third_user_id']])->data($third_user_data)->save();
                        fdump_api(["保存图片[{$village_id}]",$val['third_user_id'],$third_user_data],"third/{$village_id}/handle_third_house_face_img_log",1);
                    }
                } elseif ($info) {
                    // 同步记录下信息
                    if ($val['third_xbdm']==1) {
                        $sex='女';
                    }elseif ($val['third_xbdm']==2) {
                        $sex='男';
                    }
                    $set_bind = [];
                    if ($info && $info['authentication_field'] && $sex) {
                        $authentication_field = unserialize($info['authentication_field']);
                        if ($authentication_field['sex']) {
                            // 性别同步更新下
                            $authentication_field['sex']['value'] = $sex;
                            $set_bind['authentication_field'] = serialize($authentication_field);
                        }
                    }
                    if ($val['third_id']) {
                        $set_bind['third_id'] = $val['third_id'];
                    }
                    if ($val['third_ryid']) {
                        $set_bind['third_id'] = $val['third_ryid'];
                    }
                    if ($val['third_xqryid']) {
                        $set_bind['third_xqryid'] = $val['third_xqryid'];
                    }
                    #设置开门时间 start
                    if ($val['visitTime']) {
                        $set_bind['property_starttime'] = $val['visitTime'];
                    }
                    if ($val['effectiveVisitTime']) {
                        $set_bind['property_endtime'] = $val['effectiveVisitTime'];
                    }
                    #设置开门时间 end
                    if($chinapopinOpen){
                        $set_bind['third_type'] = $val['third_type'];
                    }
                    if ($set_bind) {
                        $db_house_village_user_bind->where(['pigcms_id' => $info['pigcms_id']])->data($set_bind)->save();
                    }
                    if ($info['uid']>0) {
                        $uid = $info['uid'];
                    } else {
                        if ($info['phone']) {
                            $uid = $db_user->where(['phone' => $info['phone'],'status'=>1])->getField('uid');
                            if (!$uid) {
                                $add_user = [
                                    'nickname' => $info['name']?$info['name']: substr($info['phone'],0,3).'****'.substr($info['phone'],7,4),
                                    'phone' => $info['phone']
                                ];
                                $uid = $db_user->data($add_user)->add();
                            }
                        } elseif($info['name']) {
                            $add_user = [
                                'nickname' => $info['name'],
                            ];
                            $uid = $db_user->data($add_user)->add();
                        }
                    }
                    if (!$uid) {
                        fdump('>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/third/handle_third_user_info_resynchronize_err_log',true);
                        fdump($val,'api/log/'.date('Ymd').'/third/handle_third_user_info_resynchronize_err_log',true);
                        fdump($info,'api/log/'.date('Ymd').'/third/handle_third_user_info_resynchronize_err_log',true);
                        // 没有用户uid 循环继续
                        if (1==$val['from'] && $info['status']!=2) {
                            // 用户端传递的信息如果是本来就是审核通过的自动反馈给公安
                            $ywlsh = trim($val['ywlsh']) ? trim($val['ywlsh']) : trim($val['third_xqryid']);
                            if ($ywlsh) {
                                $this->keepPlanThread();
                                $this->apply_access_callback($village_id,$info['pigcms_id'], 1,'', $ywlsh, $val['third_user_id']);
                                if ($info) {
                                    $info_uid = 0;
                                    $info_pigcms_id = $info['pigcms_id'] ? intval($info['pigcms_id']) : 0;
                                    $ids = D('House_village_face_door')->create_village_plan($village_id,'house_user_face_device_info', $info_uid, 'all', $info_pigcms_id);
                                    fdump(['添加计划任务'.__LINE__,$info_uid, $info_pigcms_id,$ids],'api/log/'.date('Ymd').'/third/handle_third_house_face_img_log',true);
                                }
                            }
                        }
                        continue;
                    }
                    $where_face_img = [
                        'uid' => $uid,
                        'status' => ['in',[0, 3]],
                        'img_url' => ['neq', '']
                    ];
                    $face_id = $db_house_face_img->where($where_face_img)->order('add_time ASC,id ASC')->getField('id');
                    fdump(['>>>>>>>'.__LINE__,$face_id,$info],'api/log/'.date('Ymd').'/third/house_face_img_add_log',true);
                    if ($face_id) {
                        $set = [
                            'img_url'=>$val['img_url'],
                            'last_time'=>$now_time,
                            'status' => 0
                        ];
                        $add_face_id = $db_house_face_img->where(['id' => $face_id])->data($set)->save();
                        if (!$add_face_id) {
                            fdump($db_house_face_img->getDbError(),'api/log/'.date('Ymd').'/third/house_face_img_add_log',true);
                        }
                        $logParams = [
                            'msg' => "保存图片[{$village_id}]", 'face_id' => $face_id, 'add_face_id' => $add_face_id, 'now_time' => $now_time,
                            'add_user_data' => $add_user_data, 'uid' => $uid, 'set' => $set, 'val' => $val, 'info' => $info,
                        ];
                        fdump_api($logParams, "third/{$village_id}/house_face_img_add_log", 1);
                    } elseif ($uid) {
                        $add = [
                            'uid'=>$uid,
                            'add_time'=> $now_time,
                            'img_url'=>$val['img_url'],
                            'status'=>0,
                        ];
                        $add_face_id = $db_house_face_img->data($add)->add();
                        if (!$info['uid']) {
                            $db_house_village_user_bind->where(['pigcms_id' => $info['pigcms_id']])->setField('uid', $uid);
                        }
                        $logParams = [
                            'msg' => "保存图片[{$village_id}]", 'face_id' => $face_id, 'add_face_id' => $add_face_id, 'now_time' => $now_time,
                            'add_user_data' => $add_user_data, 'uid' => $uid, 'add' => $add, 'val' => $val, 'info' => $info, 'info' => $info,
                        ];
                        fdump_api($logParams, "third/{$village_id}/house_face_img_add_log", 1);
                    } else {
                        $logParams = [
                            'msg' => "保存图片[{$village_id}]", 'face_id' => $face_id, 'add_face_id' => $add_face_id, 'now_time' => $now_time,
                            'add_user_data' => $add_user_data, 'uid' => $uid, 'info' => $info, 'val' => $val, 'info' => $info,
                        ];
                        fdump_api($logParams, "third/{$village_id}/house_face_img_add_log", 1);
                    }
                    if (1==$val['from'] && $info['status']!=2) {
                        // 用户端传递的信息如果是本来就是审核通过的自动反馈给公安
                        $ywlsh = trim($val['ywlsh']) ? trim($val['ywlsh']) : trim($val['third_xqryid']);
                        if ($ywlsh) {
                            $this->keepPlanThread();
                            $this->apply_access_callback($village_id,$info['pigcms_id'], 1,'', $ywlsh, $val['third_user_id']);
                            if ($info) {
                                $info_uid = $info['uid'] ? intval($info['uid']) : 0;
                                $info_pigcms_id = $info['pigcms_id'] ? intval($info['pigcms_id']) : 0;
                                $ids = D('House_village_face_door')->create_village_plan($village_id,'house_user_face_device_info', $info_uid, 'all', $info_pigcms_id);
                                fdump(['添加计划任务'.__LINE__,$info_uid, $info_pigcms_id,$ids],'api/log/'.date('Ymd').'/third/handle_third_house_face_img_log',true);
                            }
                        }
                    }
                    /**
                     * 同步数据成功
                     */
                    $third_user_data = [
                        'status' => 1,
                        'last_time' => $now_time
                    ];
                    if ($changeAddress) {
                        $third_user_data['address'] = $changeAddress;
                    }
                    $db_house_village_third_user_info->where(['third_user_id' => $val['third_user_id']])->data($third_user_data)->save();
                }
                file_put_contents($time,time());
            }
        } else {
            fdump_api(["没有数据[{$village_id}]",$village_id, $db_house_village_third_user_info->_sql()],"third/{$village_id}/handle_third_user_info_resynchronize_log",1);
        }
        return true;
    }

    public function add_plan($param = [], $unique_id='handle_third_user_info', $plan_time=-1000) {
        $now_time = time();
        if (!$param['type']) {
            $param['type'] = 'handle_third_user_info';
        }
        if (!$unique_id) {
            $unique_id = $param['type'];
        }
        if (isset($param['village_id']) && $param['village_id']>0) {
            $unique_id .= '_'.$param['village_id'];
        }
        if ($param['type'] == 'person_inout_info') {
            $day_time = date('Ymd');
            $record_key = 'person_inout_info_record'.$day_time.'_' . $param['village_id'];
            $record_day = S($record_key);
            if (!$record_day) {
                $plan_time = -99;
                S($record_key, 1);
            }
        }
        $arr= array();
        $arr['param'] = serialize($param);
        $arr['plan_time'] = $plan_time;
        $arr['space_time'] = 0;
        $arr['add_time'] = $now_time;
        $arr['file'] = 'sub_house_third';
        $arr['time_type'] = 1;
        $arr['unique_id'] = $unique_id;

        if($param['village_id']){
            $arr['rand_number'] = intval($param['village_id'] % (max(C('config.sub_process_num'), 3)));
            if($arr['rand_number'] == 0){
                $arr['rand_number'] = 1;
            }
        }else{
            $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
        }


        $task_id = M('Process_sub_plan')->data($arr)->add();
        return $task_id;
    }

    /**
     * Notes: 计划任务执行任务
     * @param $param
     * @param $time
     * @return int
     * @author: wanzy
     * @date_time: 2020/9/24 17:25
     */
    public function third_runtask($param, $time=0) {
        switch ($param['type']) {
            case 'handle_third_user_info': //处理数据
                $village_id = $param['village_id'];
                set_time_limit(0);
                ini_set('memory_limit', '-1');
                $this->handle_third_user_info($village_id, $time);
                return 0;
            case 'handle_third_user_info_resynchronize': //处理数据
                $village_id = $param['village_id'];
                $times = $param['times'] ? $param['times'] : 0;
                set_time_limit(0);
                ini_set('memory_limit', '-1');
                $this->handle_third_user_info_resynchronize($village_id, $times, $time);
                return 0;
            case 'person_inout_info': //处理数据
                $village_id = $param['village_id'];
                set_time_limit(0);
                ini_set('memory_limit', '-1');
                $this->personInoutInfo($village_id);
                return 0;
        }
    }

    /**四川省公安厅治安管理总队省级智慧小区“AI 能力层”接入**/
    /**
     * Notes: 4.1.1. AI 能力层小区鉴权身份认证接口
     * @param int $village_id
     * @param array $third_config 配置项
     * @return array|bool
     * @throws Exception
     * @author: wanzy
     * @date_time: 2020/9/25 16:34
     */
    public function getOauthToken($village_id, $third_config=[],$is_new = false) {
        if (!$third_config) {
            $chinapopinOpen  = D('Config_customization')->getChinapopinOpen();
            $type = 'chinapopin_sichuan';
            if($chinapopinOpen){
                $type = 'ezc_chinapopin';
            }
            $where = [
                'type' => $type,
                'village_id' => $village_id,
            ];
            $third_config = M('House_village_third_config')->where($where)->find();
        }
        if (empty($third_config)) {
            // 没有配置项无法进行下一步
            return false;
        }
        $db_house_village_third_token = M('House_village_third_token');
        $access_obj = $db_house_village_third_token->where(['village_id' => $village_id])->find();
        $now = time();
        if (!$is_new && $access_obj && intval($access_obj['expires_in']) > ($now + 180)) {
            return array('errcode' => 0, 'access_token' => $access_obj['access_token']);
        }
//        elseif ($access_obj) {
////            // 如果存在原来的值 刷新下token 值
////            $info = $this->getRefreshToken($village_id, $third_config, $access_obj);
////            return $info;
////        }
        $username = $third_config['username'];
        $password = $third_config['password'];
        if($third_config['city_village_num']) {
            $oauth_arr = [
                'grant_type' => $this->grant_type,
                'client_id' => $third_config['clientId'],
                'client_secret' => $third_config['clientSecret'],
                'username' => $username,
                'password' => $password,
            ];
        }else{
            $oauth_arr = [
                'grant_type' => $this->grant_type,
                'client_id' => $third_config['clientId'],
                'client_secret' => $third_config['clientSecret'],
                'scope' => 'community',
            ];
        }
        ##市级平台 有 city_village_num
        if($third_config['city_village_num']){
            $url = 'https://mianyang-auth.91-ec.com:19151/oauth/token';
        }else{
           //  $url = $this->request_url . '/framework/oauth/token';
            $url = 'https://ezc.chinapopin.com/zhxq-center/oauth/token';
        }

        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $msg = $this->third_http($url, $oauth_arr);
        fdump_api(['msg1'=> '获取token', 'village_id' => $village_id, 'url' => $url, 'oauth_arr' => $oauth_arr, 'msg' => $msg],"third/{$village_id}/getOauthToken_log");
        if($third_config['city_village_num']){
            if ($msg['access_token']) {
            $expires_in = intval($msg['expires_in']);
            if ($expires_in) {
                $expires_in += intval($now);
            }
            $data = [
                'village_id' => $village_id,
                'access_token' => $msg['access_token'],
                'token_type' => $msg['token_type']?$msg['token_type']:'',
                'refresh_token' => $msg['refresh_token']?$msg['refresh_token']:'',
                'expires_in' => $expires_in,
                'scope' => $msg['scope']?$msg['scope']:'',
            ];
            if ($access_obj && $access_obj['token_id']) {
                $data['last_time'] = time();
                $db_house_village_third_token->where(['token_id' => $access_obj['token_id']])->data($data)->save();
            } else {
                $data['add_time'] = time();
                $db_house_village_third_token->data($data)->add();
            }
            return array('errcode' => 0, 'access_token' => $msg['access_token']);
        } else {
            if ($msg['error_description']) {
                $errmsg = $msg['error_description'];
            } elseif ($msg['message']) {
                $errmsg = $msg['message'];
            }
            if ('server_error'==$msg['error']) {
                return array('errcode' => 0, 'access_token' => $access_obj['access_token']);
            }
            return array('errcode' => 1, 'errmsg' => $errmsg);
        }
        }else{
            if ($msg['value']) {
                $expires_in = intval($msg['expiresIn']);
                if ($expires_in) {
                    $expires_in += intval($now);
                }
                $data = [
                    'village_id' => $village_id,
                    'access_token' => $msg['value'],
                    'token_type' => $msg['tokenType']?$msg['tokenType']:'',
                    'refresh_token' => $msg['refreshToken']?$msg['refreshToken']:'',
                    'expires_in' => $expires_in,
                    'scope' => $msg['scope']?$msg['scope']:'',
                ];
                if ($access_obj && $access_obj['token_id']) {
                    $data['last_time'] = time();
                    $db_house_village_third_token->where(['token_id' => $access_obj['token_id']])->data($data)->save();
                } else {
                    $data['add_time'] = time();
                    $db_house_village_third_token->data($data)->add();
                }
                return array('errcode' => 0, 'access_token' => $msg['value']);
            } else {
                if ($msg['error_description']) {
                    $errmsg = $msg['error_description'];
                } elseif ($msg['message']) {
                    $errmsg = $msg['message'];
                }
                if ('server_error'==$msg['error']) {
                    return array('errcode' => 0, 'access_token' => $access_obj['access_token']);
                }
                return array('errcode' => 1, 'errmsg' => $errmsg);
            } 
        }
    }

    /**
     * Notes: 4.1.2. 刷新 token 接口
     * @param $village_id
     * @param array $third_config
     * @param array $access_obj
     * @return array|bool
     * @throws Exception
     * @author: wanzy
     * @date_time: 2020/9/25 16:53
     */
    public function getRefreshToken($village_id, $third_config=[], $access_obj=[]) {
        if (!$third_config) {
            $chinapopinOpen  = D('Config_customization')->getChinapopinOpen();
            $type = 'chinapopin_sichuan';
            if($chinapopinOpen){
                $type = 'ezc_chinapopin';
            }
            $where = [
                'type' => $type,
                'village_id' => $village_id,
            ];
            $third_config = M('House_village_third_config')->where($where)->find();
        }
        if (empty($third_config)) {
            // 没有配置项无法进行下一步
            return false;
        }
        $db_house_village_third_token = M('House_village_third_token');
        if (!$access_obj) {
            $access_obj = $db_house_village_third_token->where(['village_id' => $village_id])->field(true)->find();
        }
        if (empty($access_obj)) {
            return $this->getOauthToken($village_id, $third_config);
        }
        $oauth_arr = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $access_obj['refresh_token'],
            'client_id' => $third_config['clientId'],
            'client_secret' => $third_config['clientSecret'],
        ];
        $url = $this->request_url . '/framework/oauth/token';
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $msg = $this->third_http($url, $oauth_arr);
        fdump_api(['msg' => '刷新token', 'msg' => $msg,'oauth_arr'=>$oauth_arr], "third/{$village_id}/getRefreshToken", true);
        if ($msg['access_token']) {
            $now = time();
            $expires_in = intval($msg['expires_in']);
            if ($expires_in) {
                $expires_in += intval($now);
            }
            $data = [
                'village_id' => $village_id,
                'access_token' => $msg['access_token'],
                'token_type' => $msg['token_type']?$msg['token_type']:'',
                'refresh_token' => $msg['refresh_token']?$msg['refresh_token']:'',
                'expires_in' => $expires_in,
                'scope' => $msg['scope']?$msg['scope']:'',
            ];
            if ($access_obj && $access_obj['token_id']) {
                $data['last_time'] = time();
                $db_house_village_third_token->where(['token_id' => $access_obj['token_id']])->data($data)->save();
            } else {
                $data['add_time'] = time();
                $db_house_village_third_token->data($data)->add();
            }

            return array('errcode' => 0, 'access_token' => $access_obj['access_token']);
        } else {
            if ($msg['error_description']) {
                $errmsg = $msg['error_description'];
            } elseif ($msg['message']) {
                $errmsg = $msg['message'];
            }
            if ('server_error'==$msg['error']) {
                return array('errcode' => 0, 'access_token' => $access_obj['access_token']);
            }
            $db_house_village_third_token->where(['village_id' => $village_id])->delete();
            return array('errcode' => 1, 'errmsg' => $errmsg);
        }
    }
    
    public function personInoutInfo($village_id, $third_config=[]) {
        $chinapopinOpen  = D('Config_customization')->getChinapopinOpen();
        if (!$third_config) {
            $type = 'chinapopin_sichuan';
            if($chinapopinOpen){
                $type = 'ezc_chinapopin';
            }
            $where = [
                'type' => $type,
                'village_id' => $village_id,
            ];
            $third_config = M('House_village_third_config')->where($where)->find();
        }
        if($chinapopinOpen && (empty($third_config) || $third_config['status'] != 1)){
            return true;
        }
        if (empty($third_config)) {
            return true;
        }
        ## 市级平台同步
        if($third_config['city_village_num']){
            return $this->personCityInoutInfo($village_id, $third_config);
        }
        $rand_data16 = generate_password(16);
        if ($third_config['encrypted_public_key']) {
            import('@.ORG.face.rsa');
            $rsa = new rsa($third_config['encrypted_public_key']);
            $aesrsa = $rsa->encrypt($rand_data16);
        } else {
            return true;
        }
        fdump_api(['msg'=> '记录秘钥', 'village_id' => $village_id, 'rand_data16' => $rand_data16, 'aesrsa' => $aesrsa],"third/{$village_id}/person_inout_info_log", 1);

        $log_from_ary = [1,2];
        if($chinapopinOpen){
            $log_from_ary = [1,2,3];
        }

        $log_time = time() - 600;
        $log_where['a.log_business_id'] = $village_id;
        $log_where['b.village_id'] = $village_id;
        $log_where['a.log_from'] = ['in',$log_from_ary];
        $log_where['a.syn_third_status'] = ['in', [0,3]];
        $log_where['a.log_time'] = ['lt', $log_time];
        if ($chinapopinOpen) {
            $log_where['b.third_type'] = 'ezc_chinapopin';
        }
        $log_where['_string'] = " a.cut_image <> '' AND (b.third_id <> '' OR b.third_xqryid <> '')";
        $door_list = M('House_user_log')
            ->join('as a LEFT JOIN '.C('DB_PREFIX').'house_village_user_bind as b on a.log_bind_id=b.pigcms_id')
            ->where($log_where)
            ->order('a.log_time DESC')
            ->field('a.log_id,a.log_bind_id,a.log_from,a.log_business_id,a.cut_image,a.device_id,a.log_status,a.log_time,b.type as user_type,b.third_id,b.third_xqryid,b.usernum')
            ->limit(32)
            ->select();

        ## 市级的一条也上报
       // if (count($door_list) < 30 && !$third_config['city_village_num']) {
        if (count($door_list) < 30) {
            fdump_api(['msg'=> '开门记录不足30条，不进行同步', 'village_id' => $village_id, 'third_config' => $third_config, 'door_list' => count($door_list)],"third/{$village_id}/person_inout_info_err_log", 1);
            return false;
        }


        //独立读取 log_detail start
        //上面一块查出来，MYSQL非常卡，并且通过了上面判断大于30条才执行的代码再取
        $log_id_arr         = array_column($door_list, 'log_id');
        $log_detail_arr     = M('House_user_log')->field('`log_id`,`log_detail`')->where(['log_id' => ['in', $log_id_arr]])->select();
        $log_id_arr         = array_column($log_detail_arr, 'log_id');	//重新得到 log_id，避免数据不一致
        $log_detail_arr     = array_column($log_detail_arr, 'log_detail');
        $new_log_detail_arr = array_combine($log_id_arr, $log_detail_arr);
        foreach($door_list as $door_k=>$door_v){
            $door_list[$door_k]['log_detail'] = $new_log_detail_arr[$door_v['log_id']];
        }
        //独立读取 log_detail end


        $village_num = $third_config['village_num'];
        $village_num_arr = explode('-', $village_num);
        $rq = date('YmdHis');
        $desc_file_name = 'data_desc.txt';
        $sjwjm = $rq . '_' . $village_num . '_record_data.dat';
        $sjwjm_org = $rq . '_' . $village_num . '_record_data.dat.org';
        $sjwjm_new = $rq . '_' . $village_num . '_record_data.dat';
        $total_file_name = $rq . '_' . $village_num . '_02';
        $zip_file_name = $total_file_name. '.zip';

        $data = [];
        $now_time = time();
        if($door_list){
            // 文件处理
            $file_path = rtrim($_SERVER['DOCUMENT_ROOT'],'/').'/runtime/third/upload_file/'.$total_file_name.'/';
            $record_data_file = $file_path . $sjwjm_org;
            $record_data_file_new = $file_path . $sjwjm_new;
            $desc_file_name_path = $file_path . $desc_file_name;
            $zip_file_name_path = $file_path . $zip_file_name;
            $dirName = dirname($record_data_file);
            if(!file_exists($dirName)){
                mkdir($dirName,0777,true);
            }
            $dirName_new = dirname($record_data_file_new);
            if(!file_exists($dirName_new)){
                mkdir($dirName_new,0777,true);
            }
            $db_house_face_device = M('House_face_device');
            $db_house_village_floor = M('House_village_floor');
            $db_house_village_public_area = M('House_village_public_area');
            // 压缩文件成功生成，进行数据上传
            $where_log_arr = [];
            foreach ($door_list as $key=>&$item) {
                $where_log_arr[] = $item['log_id'];
                if ($item['log_detail']) {
                    $item['log_detail'] = unserialize($item['log_detail']);
                }
                if ($item['log_detail'] && $item['log_detail']['occurTime']) {
                    $cjsj = strtotime($item['log_detail']['occurTime']);
                } else {
                    $cjsj = $item['log_time']+($key*100);
                }
                if($cjsj<1100000000){
                    $cjsj=time();
                }else if($cjsj>time()){
                    $cjsj=time();
                }
                $cjsj = $cjsj - 28800;
                $uuid = $item['third_id'];
                if (!$uuid && $item['third_xqryid']) {
                    $uuid = $item['third_xqryid'];
                }
                if (!$uuid) {
                    fdump_api(['msg'=> '没有uuid', 'item' => $item],"third/{$village_id}/person_inout_info_err_log", 1);
                    continue;
                }
                $info = $db_house_face_device->where(['device_id' => $item['device_id']])->field('floor_id,device_direction,public_area_id')->find();
                $dwid = '001'; // 默认001
                if ($info['floor_id'] > 0) {
                    $dwid = $db_house_village_floor->where(['floor_id'=>$info['floor_id']])->getField('dwid');
                }elseif ($info['public_area_id'] > 0) {
                    $dwid = $db_house_village_public_area->where(['public_area_id'=>$info['public_area_id']])->getField('dwid');
                }
                if(empty($dwid)){
                    $dwid = '001'; // 默认001
                }
                if($info && $info['device_direction']){
                    $item['device_direction']=$info['device_direction'];
                }
                $inout = 'in';
                if (isset($item['device_direction'])) {
                    if (0==$item['device_direction']) {
                        $inout = 'in';
                    } else {
                        $inout = 'out';
                    }
                }
                if ($item['log_from']==1){
                    $openType='00';
                    $zpmc='';
                    if (!empty($item['cut_image'])){
                        $zp_arr=explode('/',$item['cut_image']);
                        if (!empty($zp_arr)){
                            $len=count($zp_arr);
                            $zpmc=$zp_arr[$len-1];
                        }
                        $cut_image=replace_file_domain($item['cut_image']);
                        put_file_from_url_content($cut_image,$file_path,$zpmc);
                    }
                    if(empty($zpmc)){
                        if (!empty($item['card_number'])){
                            $certificateNo=$item['card_number'];
                        }elseif(!empty($item['usernum'])){
                            $certificateNo=$item['usernum'];
                        }else{
                            $certificateNo='';
                        }
                        $openType='01';
                    }
                }else {
                    if (!empty($item['card_number'])){
                        $certificateNo=$item['card_number'];
                    }elseif(!empty($item['usernum'])){
                        $certificateNo=$item['usernum'];
                    }else{
                        $certificateNo='';
                    }
                    $openType='01';
                }
                ##省级平台
                if(!$third_config['city_village_num'])
                {
                    $data[] = [
                        'id' => $uuid,
                        'dwid' => $dwid,
                        'inout' => $inout,
                        'cjsj' => intval($cjsj),
                        'xqid' => $village_num,
                        'openType'=>$openType,
                        'certificateNo'=>isset($certificateNo)?$certificateNo:'',
                        'zpmc'=>isset($zpmc)?$zpmc:'',
                        
                    ];
                }else{	##市级平台
                    $userId = M('House_village_third_user_info')->where(['third_xqryid' => $item['third_xqryid'], 'village_id' => $village_id])->getField('ownerId');
                    $data[] = [
                        'way' => 'FACE',
                        'openDate' => date('Y-m-d H:i:s', $item['log_time']),
                        'communityId' => $third_config['city_village_num'],
                        'type' => ($item['user_type'] == 4 ? 'PROPERTY_STAFF' : ($item['user_type'] == 5 ? 'VISITOR' : ($item['user_type'] == 6 ? 'THE_WAITER' : 'USER'))),
                        'userId' => $userId,
                        'pointName' => $dwid,
                        'inout' => $inout,
                        'source' => 'THIRDPARTY_CLIENT',
                        'faceType' => '0',
                        'imageUrl' => replace_file_domain($item['log_detail']['face_img']),
                    ];
                }

            }

            ## 市级平台同步
            if($third_config['city_village_num']){
                $access_token_info = $this->getOauthToken($village_id);
                if (!$access_token_info['errcode']) {
                    $access_token = $access_token_info['access_token'];
                } else {
                    $access_token = '';
                }

                if ($access_token) {
                    $header = [
                        "Content-Type: application/json",
                        "charset=UTF-8",
                        "Authorization:bearer ".$access_token
                    ];
                }
                $url = 'https://mianyangpt.91-ec.com:19151/api/platform/person-inout-info';
                $msg = $this->third_http_post($url, $header, $data);
                fdump_api([$url, $data, $header, $msg], "third/{$village_id}/mianyang_upload_log", true);
                if($msg['responseCode'] == '_200'){
                    $set_data = [
                        'syn_third_status' => 1,
                        'syn_third_reason' => '',
                        'syn_third_time' => time(),
                    ];
                    M('House_user_log')->where(['log_id' => ['in', $where_log_arr]])->data($set_data)->save();
                }
                return true;
            }

            if ($data) {
                $data_json = json_encode($data,JSON_UNESCAPED_UNICODE);
                file_put_contents($record_data_file,$data_json);
                // 加密文件
                import('@.ORG.face.aes');
                $aes = new aes($rand_data16);
                $aes->filecrypt($record_data_file, $record_data_file_new);
                //unlink($record_data_file);
                $md5_data_file = md5_file($record_data_file_new);
              //   $aesrsa='';
                $data_desc = [
                    'xqid' => $village_num,
                    'aesrsa' => $aesrsa,
                    'ywlx' => 'inout-record-info',
                    'pcsid' => $village_num_arr[0],
                    'sfys' => 1,
                    'rq' => $rq,
                    'sjwjm' => $sjwjm,
                    'md5' => strtoupper($md5_data_file),
                ];
//                $data_desc_arr = [];
                $data_desc_arr = $data_desc;
                $data_desc_arr_json = json_encode($data_desc_arr,JSON_UNESCAPED_UNICODE);
                file_put_contents($desc_file_name_path,$data_desc_arr_json);
                import('ORG.Net.MakeZip');
                $makeZip = new MakeZip();
                $info = $makeZip->zip($file_path, $zip_file_name_path);
                if ($info) {
                    $access_token_info = $this->getOauthToken($village_id);
                    if (!$access_token_info['errcode']) {
                        $access_token = $access_token_info['access_token'];
                    } else {
                        $access_token = '';
                    }
                    fdump_api(['msg' => 'token', 'village_id' => $village_id, 'access_token_info' => $access_token_info], "third/{$village_id}/person_inout_info_log", true);
                    if ($access_token) {
                        $header =  array(
                            "Content-Type: multipart/form-data",
                            "charset=UTF-8"
                        );
                        if ($access_token) {
                            $header[] =   "Authorization:bearer ".$access_token;
                        }
                        $oauth_arr = [];
                       //  $url = $this->request_url . '/upload/person-inout-info';
                        $url='https://ezc.chinapopin.com/zhxq-resource/developer/file/upload';
//                        if (class_exists('CURLFile')) {
//                            $zip_file_name_paths = realpath($zip_file_name_path);
//                            $oauth_arr['file'] = new CURLFile($zip_file_name_paths);
//                        } else {
//                            $zip_file_name_paths = realpath($zip_file_name_path);
//                            $oauth_arr['file'] = '@'.$zip_file_name_paths;
//                        }
                        $cfile = curl_file_create($zip_file_name_path,'application/zip',$zip_file_name);
                        $oauth_arr['file'] = $cfile;
                        set_time_limit(0);
                        ini_set('memory_limit', '-1');
//                        $msg = $this->third_http($url, $oauth_arr, 'POST', $header);
                        $msg = $this->https_request($url,$oauth_arr,$header);

                        fdump_api(['url' => $url, 'oauth_arr' => $oauth_arr, 'header' => $header, 'msg' => $msg], "third/{$village_id}/person_inout_info_log", true);
                        
//                        $msg = $this->third_http_post($url, $header, $oauth_arr);
                        if ($msg && in_array($msg['code'],[1,20000])) {
                            // 同步成功
                            $set_data = [
                                'syn_third_status' => 1,
                                'syn_third_reason' => '',
                                'syn_third_time' => time(),
                            ];
                            M('House_user_log')->where(['log_id' => ['in', $where_log_arr]])->data($set_data)->save();
                            return true;
                        } else {
                            // 同步失败
                            if ('access_token 错误' == $msg['message'] || !$msg || is_null($msg)) {
                                // 如果是token失效 重置下token
                                $info = $this->getRefreshToken($village_id);
                                fdump_api(['msg' => '刷新token', 'info' => $info], "third/{$village_id}/person_inout_info_log", true);
                                if (!$info['errcode']) {
                                    $access_token = $info['access_token'];
                                    if ($access_token) {
                                        $header =  array(
                                            "Content-Type:multipart /form-data",
                                            "charset=UTF-8"
                                        );
                                        $header[] =   "Authorization:bearer ".$access_token;
                                        set_time_limit(0);
                                        ini_set('memory_limit', '-1');
                                        $msg = $this->https_request($url,$oauth_arr,$header);
                                        fdump_api(['msg' => '刷新token', 'url' => $url, 'oauth_arr' => $oauth_arr, 'header' => $header, 'result' => $msg], "third/{$village_id}/person_inout_info_log", true);
                                        if ($msg && $msg['code']==1) {
                                            // 同步成功
                                            $set_data = [
                                                'syn_third_status' => 1,
                                                'syn_third_reason' => '',
                                                'syn_third_time' => time(),
                                            ];
                                            M('House_user_log')->where(['log_id' => ['in', $where_log_arr]])->data($set_data)->save();
                                            return true;
                                        } else {
                                            $set_data = [
                                                'syn_third_status' => 3,
                                                'syn_third_reason' => $msg['message'],
                                                'syn_third_time' => time(),
                                            ];
                                            M('House_user_log')->where(['log_id' => ['in', $where_log_arr]])->data($set_data)->save();
                                        }
                                    }
                                } else {
                                    fdump_api(['msg' => '刷新token返回错误', 'info' => $info], "third/{$village_id}/person_inout_info_err_log", true);
                                }
                            } else {
                                $set_data = [
                                    'syn_third_status' => 3,
                                    'syn_third_reason' => $msg['message'],
                                    'syn_third_time' => time(),
                                ];
                                M('House_user_log')->where(['log_id' => ['in', $where_log_arr]])->data($set_data)->save();
                            }
                        }
                    }
                }
            } else {
                fdump_api(['msg' => '数据为空', 'village_id' => $village_id, 'third_config' => $third_config, 'door_list' => $door_list, 'data' => $data], "third/{$village_id}/person_inout_info_err_log", true);
            }
        } else {
            fdump_api(['msg' => '数据为空', 'village_id' => $village_id, 'third_config' => $third_config, 'door_list' => $door_list, 'data' => $data], "third/{$village_id}/person_inout_info_err_log", true);
        }
        return true;
    }

    public function personCityInoutInfo($village_id, $third_config=[]) {
        if (!$third_config) {
            $where = [
                'type' => 'chinapopin_sichuan',
                'village_id' => $village_id,
            ];
            $third_config = M('House_village_third_config')->where($where)->find();
        }
        if (empty($third_config)) {
            return true;
        }
        $rand_data16 = generate_password(16);
        if ($third_config['encrypted_public_key']) {
            import('@.ORG.face.rsa');
            $rsa = new rsa($third_config['encrypted_public_key']);
            $aesrsa = $rsa->encrypt($rand_data16);
        } else {
            return true;
        }
        fdump_api(['msg'=> '记录秘钥', 'village_id' => $village_id, 'rand_data16' => $rand_data16, 'aesrsa' => $aesrsa],"third/{$village_id}/person_inout_info_log", 1);

        $log_time = time() - 600;
        $log_where['a.log_business_id'] = $village_id;
        $log_where['b.village_id'] = $village_id;
        $log_where['a.log_from'] = ['in',[1,2]];
        $log_where['a.log_time'] = ['lt', $log_time];
        $log_where['a.syn_third_status'] = ['in', [0,3]];
        $log_where['_string'] = "(b.third_id <> '' OR b.third_xqryid <> '')";
        $door_list = M('House_user_log')
            ->join('as a LEFT JOIN '.C('DB_PREFIX').'house_village_user_bind as b on a.log_bind_id=b.pigcms_id')
            ->where($log_where)
            ->order('a.log_time DESC')
            ->field('a.log_id,a.log_bind_id,a.log_from,a.log_business_id,a.device_id,a.log_status,a.log_time,b.type as user_type,b.third_id,b.third_xqryid')
            ->limit(32)
            ->select();

        ## 市级的一条也上报
        if (count($door_list) < 30 && !$third_config['city_village_num']) {
            fdump_api(['msg'=> '开门记录不足30条，不进行同步', 'village_id' => $village_id, 'sql' => M('House_user_log')->_sql(), 'third_config' => $third_config, 'door_list' => $door_list],"third/{$village_id}/person_inout_info_err_log", 1);
            return false;
        }


        //独立读取 log_detail start
        //上面一块查出来，MYSQL非常卡，并且通过了上面判断大于30条才执行的代码再取
        $log_id_arr         = array_column($door_list, 'log_id');
        $log_detail_arr     = M('House_user_log')->field('`log_id`,`log_detail`')->where(['log_id' => ['in', $log_id_arr]])->select();
        $log_id_arr         = array_column($log_detail_arr, 'log_id');	//重新得到 log_id，避免数据不一致
        $log_detail_arr     = array_column($log_detail_arr, 'log_detail');
        $new_log_detail_arr = array_combine($log_id_arr, $log_detail_arr);
        foreach($door_list as $door_k=>$door_v){
            $door_list[$door_k]['log_detail'] = $new_log_detail_arr[$door_v['log_id']];
        }
        //独立读取 log_detail end


        $village_num = $third_config['village_num'];
        $village_num_arr = explode('-', $village_num);
        $rq = date('YmdHis');
        $desc_file_name = 'data_desc.txt';
        $sjwjm = $rq . '_' . $village_num . '_record_data.dat';
        $sjwjm_org = $rq . '_' . $village_num . '_record_data.dat.org';
        $sjwjm_new = $rq . '_' . $village_num . '_record_data.dat';
        $total_file_name = $rq . '_' . $village_num . '_02';
        $zip_file_name = $total_file_name. '.zip';

        $data = [];
        $now_time = time();
        if($door_list){
            // 文件处理
            $file_path = rtrim($_SERVER['DOCUMENT_ROOT'],'/').'/runtime/third/upload_file/'.$total_file_name.'/';
            $record_data_file = $file_path . $sjwjm_org;
            $record_data_file_new = $file_path . $sjwjm_new;
            $desc_file_name_path = $file_path . $desc_file_name;
            $zip_file_name_path = $file_path . $zip_file_name;
            $dirName = dirname($record_data_file);
            if(!file_exists($dirName)){
                mkdir($dirName,0777,true);
            }
            $dirName_new = dirname($record_data_file_new);
            if(!file_exists($dirName_new)){
                mkdir($dirName_new,0777,true);
            }
            $db_house_face_device = M('House_face_device');
            $db_house_village_floor = M('House_village_floor');
            $db_house_village_public_area = M('House_village_public_area');
            // 压缩文件成功生成，进行数据上传
            $where_log_arr = [];
            foreach ($door_list as $key=>&$item) {
                $where_log_arr[] = $item['log_id'];
                if ($item['log_detail']) {
                    $item['log_detail'] = unserialize($item['log_detail']);
                }
                if ($item['log_detail'] && $item['log_detail']['occurTime']) {
                    $cjsj = strtotime($item['log_detail']['occurTime']);
                } else {
                    $cjsj = $item['log_time']+($key*100);
                }
                if($cjsj<1100000000){
                    $cjsj=time();
                }else if($cjsj>time()){
                    $cjsj=time();
                }
                $cjsj = $cjsj - 28800;
                $uuid = $item['third_id'];
                if (!$uuid && $item['third_xqryid']) {
                    $uuid = $item['third_xqryid'];
                }
                if (!$uuid) {
                    fdump_api(['msg'=> '没有uuid', 'item' => $item],"third/{$village_id}/person_inout_info_err_log", 1);
                    continue;
                }
                $info = $db_house_face_device->where(['device_id' => $item['device_id']])->field('floor_id,device_direction, public_area_id')->find();
                $dwid = '001'; // 默认001
                if ($info['floor_id'] > 0) {
                    $dwid = $db_house_village_floor->where(['floor_id'=>$info['floor_id']])->getField('dwid');
                }elseif ($info['public_area_id'] > 0) {
                    $dwid = $db_house_village_public_area->where(['public_area_id'=>$info['public_area_id']])->getField('dwid');
                }
                if($info && $info['device_direction']){
                    $item['device_direction']=$info['device_direction'];
                }
                $inout = 'in';
                if (isset($item['device_direction'])) {
                    if (0==$item['device_direction']) {
                        $inout = 'in';
                    } else {
                        $inout = 'out';
                    }
                }

                ##省级平台
                if(!$third_config['city_village_num'])
                {
                    $data[] = [
                        'id' => $uuid,
                        'dwid' => $dwid,
                        'inout' => $inout,
                        'cjsj' => intval($cjsj),
                        'xqid' => $village_num,
                    ];
                }else{	##市级平台
                    $userId = M('House_village_third_user_info')->where(['third_xqryid' => $item['third_xqryid'], 'village_id' => $village_id])->getField('ownerId');
                    $data[] = [
                        'way' => 'FACE',
                        'openDate' => date('Y-m-d H:i:s', $item['log_time']),
                        'communityId' => $third_config['city_village_num'],
                        'type' => ($item['user_type'] == 4 ? 'PROPERTY_STAFF' : ($item['user_type'] == 5 ? 'VISITOR' : ($item['user_type'] == 6 ? 'THE_WAITER' : 'USER'))),
                        'userId' => $userId,
                        'pointName' => $dwid,
                        'inout' => $inout,
                        'source' => 'THIRDPARTY_CLIENT',
                        'faceType' => '0',
                        'imageUrl' => replace_file_domain($item['log_detail']['face_img']),
                    ];
                }

            }

            ## 市级平台同步
            if($third_config['city_village_num']){
                $access_token_info = $this->getOauthToken($village_id, $third_config);
                if (!$access_token_info['errcode']) {
                    $access_token = $access_token_info['access_token'];
                } else {
                    fdump_api($access_token_info, "third/{$village_id}/err_mianyang_upload_log", true);
                    $access_token_info = $this->getOauthToken($village_id, $third_config,1);
                    if (!$access_token_info['errcode']) {
                        $access_token = $access_token_info['access_token'];
                    } else {
                        $access_token = '';
                    }
                }

                if ($access_token) {
                    $header = [
                        "Content-Type: application/json",
                        "charset=UTF-8",
                        "Authorization:bearer ".$access_token
                    ];
                    $url = 'https://mianyangpt.91-ec.com:19151/api/platform/person-inout-info';
                    $msg = $this->third_http_post($url, $header, $data);
                    fdump_api([$url, $data, $header, $msg], "third/{$village_id}/mianyang_upload_log", true);
                    if($msg['responseCode'] == '_200'){
                        $set_data = [
                            'syn_third_status' => 1,
                            'syn_third_reason' => '',
                            'syn_third_time' => time(),
                        ];
                        M('House_user_log')->where(['log_id' => ['in', $where_log_arr]])->data($set_data)->save();
                    }
                }
                return true;
            }

            if ($data) {
                $data_json = json_encode($data,JSON_UNESCAPED_UNICODE);
                file_put_contents($record_data_file,$data_json);
                // 加密文件
                import('@.ORG.face.aes');
                $aes = new aes($rand_data16);
                $aes->filecrypt($record_data_file, $record_data_file_new);
                unlink($record_data_file);
                $md5_data_file = md5_file($record_data_file_new);

                $data_desc = [
                    'xqid' => $village_num,
                    'aesrsa' => $aesrsa,
                    'ywlx' => 'inout-record-info',
                    'pcsid' => $village_num_arr[0],
                    'sfys' => 1,
                    'rq' => $rq,
                    'sjwjm' => $sjwjm,
                    'md5' => strtoupper($md5_data_file),
                ];
//                $data_desc_arr = [];
                $data_desc_arr = $data_desc;
                $data_desc_arr_json = json_encode($data_desc_arr,JSON_UNESCAPED_UNICODE);
                file_put_contents($desc_file_name_path,$data_desc_arr_json);
                import('ORG.Net.MakeZip');
                $makeZip = new MakeZip();
                $info = $makeZip->zip($file_path, $zip_file_name_path);
                if ($info) {
                    $access_token_info = $this->getOauthToken($village_id);
                    if (!$access_token_info['errcode']) {
                        $access_token = $access_token_info['access_token'];
                    } else {
                        $access_token = '';
                    }
                    fdump_api(['msg' => 'token', 'village_id' => $village_id, 'access_token_info' => $access_token_info], "third/{$village_id}/person_inout_info_log", true);
                    if ($access_token) {
                        $header =  array(
                            "Content-Type: multipart/form-data",
                            "charset=UTF-8"
                        );
                        if ($access_token) {
                            $header[] =   "Authorization:bearer ".$access_token;
                        }
                        $oauth_arr = [];
                        $url = $this->request_url . '/upload/person-inout-info';
//                        if (class_exists('CURLFile')) {
//                            $zip_file_name_paths = realpath($zip_file_name_path);
//                            $oauth_arr['file'] = new CURLFile($zip_file_name_paths);
//                        } else {
//                            $zip_file_name_paths = realpath($zip_file_name_path);
//                            $oauth_arr['file'] = '@'.$zip_file_name_paths;
//                        }
                        $cfile = curl_file_create($zip_file_name_path,'application/zip',$zip_file_name);
                        $oauth_arr['file'] = $cfile;
                        set_time_limit(0);
                        ini_set('memory_limit', '-1');
//                        $msg = $this->third_http($url, $oauth_arr, 'POST', $header);
                        $msg = $this->https_request($url,$oauth_arr,$header);

                        fdump_api(['url' => '$url', 'oauth_arr' => $oauth_arr, 'header' => $header, 'msg' => $msg], "third/{$village_id}/person_inout_info_log", true);

//                        $msg = $this->third_http_post($url, $header, $oauth_arr);
                        if ($msg && $msg['code']==1) {
                            // 同步成功
                            $set_data = [
                                'syn_third_status' => 1,
                                'syn_third_reason' => '',
                                'syn_third_time' => time(),
                            ];
                            M('House_user_log')->where(['log_id' => ['in', $where_log_arr]])->data($set_data)->save();
                            return true;
                        } else {
                            // 同步失败
                            if ('access_token 错误'==$msg['message']) {
                                // 如果是token失效 重置下token
                                $info = $this->getRefreshToken($village_id);
                                if (!$info['errcode']) {
                                    $access_token = $info['access_token'];
                                    if ($access_token) {
                                        $header =  array(
                                            "Content-Type:multipart /form-data",
                                            "charset=UTF-8"
                                        );
                                        $header[] =   "Authorization:bearer ".$access_token;
                                        set_time_limit(0);
                                        ini_set('memory_limit', '-1');
                                        $msg = $this->https_request($url,$oauth_arr,$header);
                                        if ($msg && $msg['code']==1) {
                                            // 同步成功
                                            $set_data = [
                                                'syn_third_status' => 1,
                                                'syn_third_reason' => '',
                                                'syn_third_time' => time(),
                                            ];
                                            M('House_user_log')->where(['log_id' => ['in', $where_log_arr]])->data($set_data)->save();
                                            return true;
                                        } else {
                                            $set_data = [
                                                'syn_third_status' => 3,
                                                'syn_third_reason' => $msg['message'],
                                                'syn_third_time' => time(),
                                            ];
                                            M('House_user_log')->where(['log_id' => ['in', $where_log_arr]])->data($set_data)->save();
                                        }
                                    }
                                } else {
                                    fdump_api(['msg' => '刷新token返回错误', 'info' => $info], "third/{$village_id}/person_inout_info_err_log", true);
                                }
                            } else {
                                $set_data = [
                                    'syn_third_status' => 3,
                                    'syn_third_reason' => $msg['message'],
                                    'syn_third_time' => time(),
                                ];
                                M('House_user_log')->where(['log_id' => ['in', $where_log_arr]])->data($set_data)->save();
                            }
                        }
                    }
                }
            } else {
                fdump_api(['msg' => '数据为空', 'village_id' => $village_id, 'third_config' => $third_config, 'door_list' => $door_list, 'data' => $data], "third/{$village_id}/person_inout_info_err_log", true);
            }
        } else {
            fdump_api(['msg' => '数据为空', 'village_id' => $village_id, 'third_config' => $third_config, 'door_list' => $door_list, 'data' => $data], "third/{$village_id}/person_inout_info_err_log", true);
        }
        return true;
    }

    public function rsa_data($rand_data16, $encrypted_public_key) {
        $pubKey = $this->get_public_key($encrypted_public_key);
        $pu_key = openssl_pkey_get_public($pubKey);
        openssl_public_encrypt($rand_data16, $data, $pu_key);
        $data = base64_encode($data);
        return $data;
    }

    public function get_public_key($pubKey){
        $p_key = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($pubKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        return $p_key;
    }


    protected function https_request($url, $data = null, $header = ''){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if($header){
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);
        if ($error) {
            fdump('>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/third/third_http_post_err_log',true);
            fdump('***请求出错***','api/log/'.date('Ymd').'/third/third_http_post_err_log',true);
            fdump($error,'api/log/'.date('Ymd').'/third/third_http_post_err_log',true);
        }
        $result = json_decode($output, true);
        return $result;
    }

    public $status_txt = [
        '未处理','同步成功','无同步对象','同步失败'
    ];
    public $sex_txt = [
        1=>'女',
        2=>'男'
    ];
    public $type_txt = [
        '01' => '住户',
        '02' => '租户',
        '03' => '物业人员',
        '04' => '服务人员',
        '05' => '来访人员',
    ];
    public $from_txt = [
        '0' =>'文件导入',
        '1' => '用户人脸上传'
    ];
    // 0 待审核 1 审核通过 2 审核拒绝
    public $check_status_txt = [
        '0' => '待审核',
        '1' => '审核通过',
        '2' => '审核拒绝',
    ];
    /**
     * Notes: 查询下同步的数据
     * @param array $where
     * @param int $page_size
     * @return array
     * @author: wanzy
     * @date_time: 2020/9/29 14:36
     */
    public function handle_third_user_list($where = [], $page_size=15) {
        if(!isset($where['third_type'])){
            $chinapopinOpen  = D('Config_customization')->getChinapopinOpen();
            $type = 'chinapopin_sichuan';
            if($chinapopinOpen){
                $type = 'ezc_chinapopin';
            }
            $where['third_type'] = $type;
        }
        $count_user = $this->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, $page_size);
        $list = $this->where($where)->order('status asc, img_url DESC, third_user_id ASC')->limit($p->firstRow . ',' . $p->listRows)->select();
        if($list){
            foreach($list as $k=>&$v){
                $v['status_txt']       = $this->status_txt[$v['status']];
                $v['type_txt']         = $this->type_txt[$v['type']];
                $v['from_txt']         = $this->from_txt[$v['from']];
                $v['check_status_txt'] = $this->check_status_txt[$v['check_status']];
                if ($v['img_url']) {
                    $v['img_url_txt'] =replace_file_domain($v['img_url']);
                }
                $sex_judge = substr($v['third_zjhm'],-2,1);
                if (intval($sex_judge)%2==0){
                    // 偶数是女
                    $v['third_xbdm'] = 1;
                }else{
                    // 奇数是男
                    $v['third_xbdm'] = 2;
                }
                $v['sex_txt'] = $this->sex_txt[$v['third_xbdm']];
            }
        }
        $arr = [];
        $arr['list'] = $list;
        $arr['pagebar'] = $p->show();
        return $arr;
    }

    // 4.2.2. 接收人员照片信息回调接口

    /**
     * Notes: 接收人员照片信息回调接口
     * @param int $village_id 小区id
     * @param int $bind_id 用户小区绑定id
     * @param int $status 申请结果  1：同意进出，0：不同意进出
     * @param string $reason 审核拒绝原因
     * @param string $ywlsh 业务流水号
     * @param string $errorcode 错误码 101： 照片质量不达标 ， 201：身份核验不通过
     * @param array $third_config 第三方配置信息
     * @return bool
     * @throws Exception
     * @author: wanzy
     * @date_time: 2020/10/9 20:53
     */
    public function apply_access_callback($village_id, $bind_id, $status = 1, $reason='小区审核拒绝入住', $ywlsh='',$errorcode='201',$third_config=[], $third_user_id=0) {
        $chinapopinOpen  = D('Config_customization')->getChinapopinOpen();
        if (!$third_config) {
            $type = 'chinapopin_sichuan';
            if($chinapopinOpen){
                $type = 'ezc_chinapopin';
            }
            $where = [
                'type' => $type,
                'village_id' => $village_id,
            ];
            $third_config = M('House_village_third_config')->where($where)->find();
        }
        if (empty($third_config)) {
            fdump('>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/third/apply_access_callback_err_log',true);
            fdump('***未配置配置项***','api/log/'.date('Ymd').'/third/apply_access_callback_err_log',true);
            fdump($village_id,'api/log/'.date('Ymd').'/third/apply_access_callback_err_log',true);
            return true;
        }
        $db_house_village_user_bind = M('House_village_user_bind');
        if (!$ywlsh) {
            $where_bind = [
                'village_id' => $village_id,
                'pigcms_id' => $bind_id
            ];
            $ywlsh = $db_house_village_user_bind->where($where_bind)->getField('third_xqryid');
        }
        if (!$ywlsh) {
            fdump('>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/third/apply_access_callback_err_log',true);
            fdump('***缺少业务流水号***','api/log/'.date('Ymd').'/third/apply_access_callback_err_log',true);
            fdump($village_id,'api/log/'.date('Ymd').'/third/apply_access_callback_err_log',true);
            fdump($bind_id,'api/log/'.date('Ymd').'/third/apply_access_callback_err_log',true);
            fdump($ywlsh,'api/log/'.date('Ymd').'/third/apply_access_callback_err_log',true);
            fdump($third_config,'api/log/'.date('Ymd').'/third/apply_access_callback_err_log',true);
            return true;
        }
        if(!$third_config['city_village_num'] && $chinapopinOpen) {
            // 省级走新的接口进行审核
            return $this->ezcChinapopinCheck($bind_id,$ywlsh,$village_id,$status);
        }else
        if(!$third_config['city_village_num']) {
            // 省级走新的接口进行审核
            return $this->chinapopinCheck($village_id,$ywlsh);
        }

        $access_token_info = $this->getOauthToken($village_id);
        if (!$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $header =  array(
                "Content-Type: application/json",
                "charset=UTF-8"
            );
            if ($access_token) {
                $header[] =   "Authorization:bearer ".$access_token;
            }
            $oauth_arr = [];
            $oauth_arr['ywlsh'] = $ywlsh;
            $oauth_arr['result'] = $status;
            if ($status==1) {
                $oauth_arr['period'] = 'always';
            } else {
                $oauth_arr['errorcode'] = $errorcode;
                $oauth_arr['message'] = $reason;
            }
            $data = [];
            $data[] = $oauth_arr;
            $url = $this->ai_request_url . '/community/api/community/apply-access-callback';
            set_time_limit(0);
            ini_set('memory_limit', '-1');
            $msg = $this->third_http_post($url, $header, $data);
            if ($msg && $msg['code']==1) {
                // 同步成功
                $where_third = [
                    'village_id' => $village_id
                ];
                if ($third_user_id) {
                    $where_third['third_user_id'] = $third_user_id;
                } else {
                    $where_third['_string'] = "third_ryid={$ywlsh} OR third_xqryid={$ywlsh}";
                }
                $third_save = [
                    'check_status' => ($status==1) ? 1 : 2,
                    'check_reason' => $reason ? $reason : '',
                    'check_time' => time(),
                    'last_time' => time()
                ];
                if ($where_third) {
                    D('House_village_third_user_info')->where($where_third)->data($third_save)->save();
                }
                fdump('>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/thirduser/userCallback/apply_access_callback_log',true);
                fdump('***审核同步成功***','api/log/'.date('Ymd').'/third/apply_access_callback_log',true);
                fdump($url,'api/log/'.date('Ymd').'/third/apply_access_callback_log',true);
                fdump($where_third,'api/log/'.date('Ymd').'/third/apply_access_callback_log',true);
                fdump($third_save,'api/log/'.date('Ymd').'/third/apply_access_callback_log',true);
                fdump($header,'api/log/'.date('Ymd').'/third/apply_access_callback_log',true);
                fdump($data,'api/log/'.date('Ymd').'/third/apply_access_callback_log',true);
                fdump($msg,'api/log/'.date('Ymd').'/third/apply_access_callback_log',true);
            } else {
                fdump('>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/third/apply_access_callback_err_log',true);
                fdump('***审核同步失败***','api/log/'.date('Ymd').'/third/apply_access_callback_err_log',true);
                fdump($village_id,'api/log/'.date('Ymd').'/third/apply_access_callback_err_log',true);
                fdump($bind_id,'api/log/'.date('Ymd').'/third/apply_access_callback_err_log',true);
                fdump($ywlsh,'api/log/'.date('Ymd').'/third/apply_access_callback_err_log',true);
                fdump($third_config,'api/log/'.date('Ymd').'/third/apply_access_callback_err_log',true);
                fdump($url,'api/log/'.date('Ymd').'/third/apply_access_callback_err_log',true);
                fdump($header,'api/log/'.date('Ymd').'/third/apply_access_callback_err_log',true);
                fdump($data,'api/log/'.date('Ymd').'/third/apply_access_callback_err_log',true);
                fdump($msg,'api/log/'.date('Ymd').'/third/apply_access_callback_err_log',true);
            }
            return true;
        }
    }

    /**
     * Notes: 接口请求 调用接口函数
     * @param string $url 接口地址
     * @param array $params 数组
     * @param string $method GET\POST\DELETE\PUT
     * @param array $header HTTP头信息
     * @param int $timeout 超时时间
     * @return mixed 接口返回数据
     * @throws Exception
     * @author: wanzy
     * @date_time: 2020/9/27 17:27
     */
    public function third_http($url, $params, $method = 'POST', $header = array(), $timeout = 45){
        // POST 提交方式的传入 $params 必须是字符串形式
        if (empty($header)) {
            $header[] =  "content-type:application/x-www-form-urlencoded";
        }
        $opts = array(
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => $header
        );

        /* 根据请求类型设置特定参数 */
        switch (strtoupper($method)) {
            case 'GET':
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
                break;
            case 'POST':
                $params = http_build_query($params);
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            case 'DELETE':
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_HTTPHEADER] = array("X-HTTP-Method-Override: DELETE");
                $opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            case 'PUT':
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 0;
                $opts[CURLOPT_CUSTOMREQUEST] = 'PUT';
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            default:
                throw new Exception('不支持的请求方式！');
        }

        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data = curl_exec($ch);
        $error = curl_error($ch);
        if ($error) {
            fdump('>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/third/third_http_err_log',true);
            fdump('***请求出错***','api/log/'.date('Ymd').'/third/third_http_err_log',true);
            fdump($error,'api/log/'.date('Ymd').'/third/third_http_err_log',true);
        }
        $result = json_decode($data, true);
        return $result;
    }

    /**
     * Notes: post请求
     * @param $url
     * @param $headers
     * @param $post
     * @return mixed
     * @author: wanzy
     * @date_time: 2020/10/10 10:35
     */
    public function third_http_post($url, $headers, $post)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));//postData
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //success
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        $data = curl_exec($ch);
        $error = curl_error($ch);
        if ($error) {
            fdump('>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/third/third_http_post_err_log',true);
            fdump('***请求出错***','api/log/'.date('Ymd').'/third/third_http_post_err_log',true);
            fdump($error,'api/log/'.date('Ymd').'/third/third_http_post_err_log',true);
        }
        $result = json_decode($data, true);
        return $result;
    }


    public function chinapopinCheck($village_id, $id, $third_config = [], $third_user_id = 0, $is_new = false) {
        if (!$third_config) {
            $where = [
                'type' => 'chinapopin_sichuan',
                'village_id' => $village_id,
            ];
            $third_config = M('House_village_third_config')->where($where)->find();
        }
        if (empty($third_config) || !$third_config['village_num']) {
            fdump_api(['配置项缺失',$village_id, $id, $third_config], 'third/errChinapopinCheckLog',1);
            return true;
        }
        if($third_config['city_village_num']) {
            fdump_api(['市级小区不进行这个审核',$village_id, $id, $third_config], 'third/errChinapopinCheckLog',1);
            return true;
        }
        $communityCode = $third_config['village_num'];

        $access_token_info = $this->getOauthToken($village_id, $third_config, $is_new);

        if (!$access_token_info['access_token']) {
            fdump_api(['token获取失败',$village_id, $id], 'third/errChinapopinCheckLog',1);
        }
        $access_token = $access_token_info['access_token'];
        $header =  array(
            "Content-Type: application/json",
            "charset=UTF-8"
        );
        if ($access_token) {
            $header[] =   "Authorization:bearer ".$access_token;
        }
        $oauth_arr = [];
        $oauth_arr['id']            = intval($id);
        $oauth_arr['statusName']    = '审核通过';
        $oauth_arr['statusCode']    = '02';
        $oauth_arr['communityCode'] = $communityCode;
        $oauth_arr['period']        = 'always';
        $url = 'https://ezc.chinapopin.com/zhxq-resource/user/userCallback';
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $msg = $this->third_http_post($url, $header, $oauth_arr);
        fdump_api(['自动审核',$oauth_arr, $header, $url,$msg], 'third/chinapopinCheckLog',1);
        if ($msg && ($msg['msg']=='success' || $msg['success'])) {
            // 同步成功
            $where_third = [
                'village_id' => $village_id
            ];
            if ($third_user_id) {
                $where_third['third_user_id'] = $third_user_id;
            } else {
                $where_third['_string'] = "third_ryid={$id} OR third_xqryid={$id}";
            }
            $third_save = [
                'check_status' => 1,
                'check_reason' => '审核通过',
                'check_time' => time(),
                'last_time' => time()
            ];
            fdump_api(['自动审核',$where_third, $third_save], 'third/chinapopinCheckLog',1);
            if ($where_third) {
                D('House_village_third_user_info')->where($where_third)->data($third_save)->save();
            }
            $where_user_bind = [
                'village_id' => $village_id,
                'third_xqryid' => $third_user_id,
                'status' => 2,
            ];
            $pigcms_id = D('House_village_user_bind')->where($where_user_bind)->getField('pigcms_id');
            fdump_api(['自动审核-对应人员','pigcms_id' => $pigcms_id], 'third/chinapopinCheckLog',1);
            if ($pigcms_id) {
                $user_bind_save = [
                    'status' => 1,
                    'pass_time' => time(),
                ];
                fdump_api(['自动审核-对应人员状态更新','user_bind_save' => $user_bind_save], 'third/chinapopinCheckLog',1);
                D('House_village_user_bind')->where($where_user_bind)->data($user_bind_save)->save();
                D('House_face_img')->house_user_face_device_info(0,$pigcms_id,false,[],true,true);
            }
        } elseif (!$is_new) {
            $this->chinapopinCheck($village_id, $id, $third_config = [], $third_user_id = 0, true);
        }
        return [
            'url'    => $url,
            'header' => $header,
            'data'   => $oauth_arr,
            'msg'    => $msg,
        ];
    }

    public function restful_curl_post($url, $param, $header) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($param,JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => $header,
        ));
        $response = curl_exec($curl);
        $error = curl_error($curl);
        if ($error) {
            fdump_api([$url, $param, $header, $error, $response],'/third/err_restful_curl_post',true);
        }
        curl_close($curl);
        fdump_api([$url, $param, $header, $error, $response],'/third/restful_curl_post',true);
        $result = json_decode($response, true);
        return $result;
    }
    
    /**
     * 创建新的楼栋、单元、楼层、房间
     */
    public function add_vacancy_info($village_id,$roomAry,$single_name,$floor_name,$layer_name)
    {
        //查询楼栋是否存在
        $single_id = M('House_village_single')->where(['village_id'=>$village_id,'single_name'=>['in',[$single_name,$single_name.'栋']]])->getField('id');
        $floor_id = 0;
        $layer_id = 0;
        $vacancy_id = 0;
        $layer_name_add = '';
        if($single_id){
            //查询单元是否存在
            $floor_id = M('House_village_floor')->where(['village_id'=>$village_id,'single_id'=>$single_id,'floor_name'=>['in',[$floor_name,$floor_name.'单元']]])->getField('floor_id');
            if($floor_id){
                //查询楼层是否存在
                $layer_info = M('House_village_layer')->where(['floor_id'=>$floor_id,'layer_name'=>['in',[$layer_name,$layer_name.'层','0'.$layer_name,'0'.$layer_name.'层']]])->field('id')->find();
                $layer_id = $layer_info['id'] ?? 0;
                $layer_name_add =  $layer_info['layer_name'] ?? '';
                if($layer_id){
                    //查询房间是否存在
                    $vacancy_id = M('House_village_user_vacancy')->where(['layer_id'=>$layer_id,'room'=>['in',$roomAry]])->getField('pigcms_id');
                }
            }
        }
        if(!$single_id){
            //创建楼栋
            $single_id = M('House_village_single')->add([
                'village_id'=>$village_id,
                'status'=>1,
                'single_name'=>$single_name
            ]);
        }
        if($single_id && !$floor_id){
            //创建单元
            $floor_id = M('House_village_floor')->add([
                'status'=>1,
                'village_id'=>$village_id,
                'single_id'=>$single_id,
                'floor_name'=>$floor_name,
                'add_time'=>time(),
            ]);
        }
        if($single_id && $floor_id && !$layer_id){
            //创建楼层
            $layer_id = M('House_village_layer')->add([
                'status'=>1,
                'village_id'=>$village_id,
                'single_id'=>$single_id,
                'floor_id'=>$floor_id,
                'layer_name'=>$layer_name,
                'add_time'=>time(),
            ]);
            $layer_name_add = $layer_name;
        }
        $vacancy_info = [];
        if($single_id && $floor_id && $layer_id && !$vacancy_id){
            //创建房间
            $vacancy_info = [
                'status'=>1,
                'is_del'=>0,
                'village_id'=>$village_id,
                'single_id'=>$single_id,
                'floor_id'=>$floor_id,
                'layer_id'=>$layer_id,
                'room'=>$roomAry[0],
                'layer'=>$layer_name_add,
                'housesize'=>0,
                'add_time'=>time(),
            ];
            $vacancy_id = M('House_village_user_vacancy')->add($vacancy_info);
            M('House_village_user_vacancy')->where(['pigcms_id'=>$vacancy_id])->save(['usernum'=>$village_id . '-' . $single_id . '-' . $floor_id . '-' . $layer_id.'-'.$vacancy_id]);
            $vacancy_info['pigcms_id'] = $vacancy_id;
            $vacancy_info['usernum'] = $village_id . '-' . $single_id . '-' . $floor_id . '-' . $layer_id.'-'.$vacancy_id;
        }
        return $vacancy_info;
    }

    public function ezcChinapopinCheck($pigcms_id,$third_xqryid,$village_id,$status) {
        if(!$third_xqryid){
            fdump_api(['第三方id缺失',$third_xqryid,$village_id], 'third/errEzcChinapopinCheckLog',1);
            return true;
        }
        $third_user_info = $this->where(['third_xqryid'=>$third_xqryid,'village_id'=>$village_id])->field('status')->find();
        if(!$third_user_info){
            fdump_api(['第三方信息缺失',$third_user_info, $this->getLastSql()], 'third/errEzcChinapopinCheckLog',1);
            return true;
        }
        if($third_user_info['status'] != 1){
            fdump_api(['设备未开启',$village_id], 'third/errEzcChinapopinCheckLog',1);
            return true;
        }
        $where = [
            'type' => 'ezc_chinapopin',
            'village_id' => $village_id,
        ];
        $third_config = M('House_village_third_config')->where($where)->find();
        if (empty($third_config) || !$third_config['village_num']) {
            fdump_api(['配置项缺失',$village_id, $third_config], 'third/errEzcChinapopinCheckLog',1);
            return true;
        }
        $communityCode = $third_config['village_num'];

        $access_token_info = $this->getOauthToken($village_id, $third_config, true);

        if (!$access_token_info['access_token']) {
            fdump_api(['token获取失败',$village_id], 'third/errEzcChinapopinCheckLog',1);
        }
        $access_token = $access_token_info['access_token'];
        $header =  array(
            "Content-Type: application/json",
            "charset=UTF-8"
        );
        if ($access_token) {
            $header[] =   "Authorization:bearer ".$access_token;
        }
        if($status == 1){//审核通过
            $statusName = '审核通过';
            $statusCode = '02';
        }else{//审核不通过
            $statusName = '审核不通过';
            $statusCode = '03';
        }
        $oauth_arr = [];
        $oauth_arr['id']            = intval($third_xqryid);
        $oauth_arr['statusName']    = $statusName;
        $oauth_arr['statusCode']    = $statusCode;
        $oauth_arr['communityCode'] = $communityCode;
        $oauth_arr['period']        = 'always';
        $url = 'https://ezc.chinapopin.com/zhxq-resource/user/userCallback';
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $msg = $this->third_http_post($url, $header, $oauth_arr);
        fdump_api(['自动审核',$oauth_arr, $header, $url,$msg], 'third/errEzcChinapopinCheckLog',1);
        if ($msg && ($msg['msg']=='success' || $msg['success'])) {
            // 同步成功
            $where_third = [
                'village_id' => $village_id,
                'third_xqryid' => $third_xqryid
            ];
            $third_save = [
                'check_status' => $status,
                'check_reason' => $status == 1 ? '审核通过' : '审核不通过',
                'check_time' => time(),
                'last_time' => time()
            ];
            fdump_api(['自动审核',$where_third, $third_save], 'third/errEzcChinapopinCheckLog',1);
            if ($where_third) {
                D('House_village_third_user_info')->where($where_third)->data($third_save)->save();
            }
            if ($pigcms_id && $status == 1) {
                //一键同步
                D('House_face_img')->house_user_face_device_info(0,$pigcms_id,false,[],true,true);
            }
        }
        return [
            'url'    => $url,
            'header' => $header,
            'data'   => $oauth_arr,
            'msg'    => $msg,
        ];
    }
    
    public function allPersonInoutInfo()
    {
        $chinapopinOpen  = D('Config_customization')->getChinapopinOpen();
        if(!$chinapopinOpen){
            return true;
        }
        $village_ary = M('House_village_third_config')->where(['status'=>1,'type'=>'ezc_chinapopin'])->getField('village_id',true);
        foreach ($village_ary as $village_id){
            $this->personInoutInfo($village_id);
        }
        return true;
    }
}
