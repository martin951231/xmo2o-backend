<?php
/**
 * 出入证绑定人员相关
 * Class Pass
 */
class House_village_pass_bind_memberModel extends Model
{
    //人脸列表
    public function passMemberList($where,$field=true,$order='m.id desc',$pageSize = 20)
    {
        import('@.ORG.merchant_page');
        $left_join = "as m left join ".C('DB_PREFIX').'house_village_pass as p on `m`.`pass_id`=`p`.`pass_id`';
        $field = 'm.*,p.code_num';
        $count = $this->join($left_join)->where($where)->field($field)->count();
        $p = new Page($count,$pageSize,'page');

        $member_list = $this->join($left_join)->where($where)->field($field)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
        foreach ($member_list as &$val){
            if($val['status'] == 0 && $val['due_time']<time()){
                $val['status'] = 2;
                $this->where(['id'=>$val['id']])->data(['status'=>2])->save();
            }
            $val['start_time'] = date('Y-m-d',$val['start_time']);
            $val['due_time'] = date('Y-m-d',$val['due_time']);

        }
        $list['list'] = $member_list;
        $list['pagebar'] = $p->show();
        return $list;
    }
    //出入证绑定人脸
    public function addBindMember($post,$village_id,$id='')
    {
        $map['phone'] = $post['come_phone'];
        $map['village_id'] = $village_id;
        $map['type'] = 6;
        $user_bind = D('House_village_user_bind')->where($map)->field('pigcms_id,uid')->find();
        if(!$user_bind){
            $userInfo = D('User')->where(['phone'=>$post['come_phone']])->field('uid,phone')->find();
            if(!$userInfo)
            {
                $data_user = array(
                    'nickname' 	=> $post['come_name'],
                    'phone' 	=> $post['come_phone'],
                    'source' 	=> 'houseautoreg_villagepass',
                );
                $reg_result = D('User')->autoreg($data_user);
                $uid = $reg_result['uid'];
            }else{
                $uid = $userInfo['uid'];
            }
            $arr = [
                'village_id'=>$village_id,
                'uid'=>$uid,
                'phone'=>$post['come_phone'],
                'name'=>$post['come_name'],
                'type'=>6,
                'usernum'=>rand(1000,9999).'-'.time(),
            ];
            $pigcms_id = D('House_village_user_bind')->add($arr);
        }else{
            if(!$user_bind['uid']){
                $userInfo = D('User')->where(['phone'=>$post['come_phone']])->field('uid,phone')->find();
                if($userInfo){
                    $uid = $userInfo['uid'];
                }else{
                    $data_user = array(
                        'nickname' 	=> $post['come_name'],
                        'phone' 	=> $post['come_phone'],
                        'source' 	=> 'houseautoreg_villagepass',
                    );
                    $reg_result = D('User')->autoreg($data_user);
                    $uid = $reg_result['uid'];
                    D('House_village_user_bind')->where(['pigcms_id'=>$user_bind['pigcms_id']])->data(['uid'=>$uid])->save();
                }
            }else{
                $uid = $user_bind['uid'];
            }
            $pigcms_id = $user_bind['pigcms_id'];
        }
        $type = $post['type']?$post['type']:'';
        unset($post['type']);
        $face_id = explode(',',$post['face_id']);
        unset($post['face_id']);
        $guard = $post['guard'];
        if($post['open_status'] == 1)
        {
            $post['guard']='';
        }
        $door_arr=[];
        $location= [];
        if($guard){
            $guard_arr = explode(',',$guard);
            foreach ($guard_arr as $key=>$val){
                $new_arr = explode('-',$val);
                if($new_arr[0] == 0){
                    //公共区域门禁设备所在地址id
                    $door_arr['public_area_id'][] = $new_arr[1];//public_area_id
                    $location[] = 'public_'.$new_arr[1];
                }else{
                    //设备所在的楼栋和单元 id
                    $door_arr['private_arr'][] = [
                        'single_id'=>$new_arr[0],
                        'floor_id'=>$new_arr[1],
                    ];
                    $location[] = 'floor_'.$new_arr[1];
                }
            }
        }
        $code_num = $post['code_num'];
        unset($post['code_num']);
        $post['pass_id']=D('House_village_pass')->where(['code_num'=>$code_num])->getField('pass_id');
        $post['uid'] = $uid;
        $post['bind_id'] = $pigcms_id;
        $post['start_time'] = strtotime($post['start_time']);
        $post['due_time'] =strtotime($post['due_time'].' 23:59:59');

        if($id){
            $post['edit_time'] = time();
            $insert = $this->where(['id'=>$id])->data($post)->save();
        }else{
            $post['add_time']= time();
            $post['village_id'] = $village_id;
            $insert  = $this->add($post);
        }
        if($insert){
            //不需要审核  或者 已通过审核的
            if($type || $post['status'] == 0) {
                D('House_village_pass')->where(['code_num' => $code_num])->data(['use_status' => 1])->save();
                $user_bind_data['property_starttime'] = $post['start_time'];
                $user_bind_data['property_endtime'] = $post['due_time'];
                D('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id,'type'=>5])->data($user_bind_data)->save();
                //不给开门权限
                if($post['open_status'] == 1){
                    if ($face_id && count($face_id) > 0) {
                        //删除人脸图片
                        $this->face_img_bind($face_id, $uid,$location,$pigcms_id,$village_id, 1);
                    }
                }else {
                    //查看设置开门方式
                    $open_door_type = D('House_village_about_config')->where(['village_id' => $village_id])->getField('open_door_type');
                    if ($open_door_type == 1) { //1人脸开门 2远程开门
                        if ($face_id && count($face_id) > 0) {
                            //将人脸图片关联用户uid
                            $this->face_img_bind($face_id, $uid,$location,$pigcms_id,$village_id, $post['open_status']);
                        }
                    } else {
                        if ($face_id && count($face_id) > 0) {
                            //删除人脸图片
                            $this->face_img_bind($face_id, $uid,$location,$pigcms_id,$village_id, 1);
                        }
                    }
                }

            }
        }
        return $insert;
    }
    //将人脸图片关联用户uid
    public function face_img_bind($face_id,$uid,$location,$pigcms_id,$village_id,$type='')
    {
        $where['id'] = ['in', $face_id];
        if(!$type) {
            //将人脸图片关联用户uid
            $res = D('House_face_img')->where($where)->data(['uid' => $uid])->save();
            if ($res) {
                //执行人脸同步对应人脸设备
                $face_device = D('House_face_img')->face_user_to_device($pigcms_id, $village_id, $location);
                if ($face_device) {
                    $map['pigcms_id'] =$pigcms_id;
                    $data_bind = [
                        'face_img_status' => '0',
                        'add_face_time' => time(),
                    ];
                    D('House_village_user_bind')->where($map)->data($data_bind)->save();
                }
            }
        }else{
            //不给开门权限，将上传的图片删除
            D('House_face_img')->where($where)->data(['status'=>1,'del_time'=>time()])->save();
            //调用删除设备上人脸信息

            D('House_village_face_door')->del_a3_user_bind($pigcms_id, $village_id);
        }
    }
    //获取开门记录
    public function getOpenDoorRecord($pass_id,$village_id,$page=0,$pagesize=5)
    {
        $pass_bind = $this->where(['pass_id'=>$pass_id,'village_id'=>$village_id])->select();
        $arr_uid = array_column($pass_bind,'uid');
        $arr_uid = array_unique($arr_uid);
        $arr_bind_id = array_column($pass_bind,'bind_id');
        $arr_bind_id = array_unique($arr_bind_id);
//        $arr_uid = [112358790,1358611,1681];
//        $arr_bind_id = [3229];
        //人脸开门记录
        $map['l.uid']=['in',$arr_uid];
        $map['l.log_bind_id']=['in',$arr_bind_id];
        $map['l.log_from']=['in',[1,2]];
        $left_join = "as l left join ".C('DB_PREFIX').'user as u on `l`.`uid`=`u`.`uid`';
        $face_record = D('House_user_log')->where($map)->field('`l`.*,`u`.`phone`,`u`.`nickname`')->join($left_join)->select();
        $new_face_list = [];
        foreach ($face_record as $key=>$val){
            if($val['log_from'] == 1){
                $msg = '人脸开门';
            }else{
                $msg = '远程开门';
            }
            $new_face_list[$key] = [
                'log_time'=>date('Y-m-d H:i:s',$val['log_time']),
                'log_address'=>$val['log_name'],
                'name'=>$val['nickname']?$val['nickname']:'--',
                'phone'=>$val['phone']?$val['phone']:'--',
                'log_status'=>$val['log_status'],
                'log_from'=>$val['log_from'],
                'log_from_text'=>'开门方式-'.$msg,
            ];
        }
        //蓝牙开门
        $village_id = 2;
        $where['d.uid'] = ['in',$arr_uid];
        $where['d.village_id'] = $village_id;
        $left_join = "as d left join ".C('DB_PREFIX').'user as u on `d`.`uid`=`u`.`uid`';
        $door_list = M('House_village_open_door')->field('`d`.*,`u`.`phone`,`u`.`nickname`')->join($left_join)->where($where)->order('`d`.`add_time` DESC')->select();
        $new_door_list = [];
        foreach ($door_list as $k=>$val){
            if($val['floor_id'] == '-1'){
                $log_address = '所有楼栋';
            }else{
                $left_join = "as f left join ".C('DB_PREFIX').'house_village_single as s on `f`.`single_id`=`s`.`id`';
                $address = D('House_village_floor')->join($left_join)->where(['f.floor_id'=>$val['floor_id']])->field('f.floor_name,f.single_id,s.single_name')->find();
                if($address) {
                    if (preg_match('/[\x7f-\xff]/', $address['single_name'] . $address['floor_name'])) {
                        $address_text = $address['single_name'] . '-' . $address['floor_name'];
                    } else {
                        $address_text = $address['single_name'] . '楼栋' . '-' . $address['floor_name'] . '单元';
                    }
                    $log_address = $address_text;
                }else{
                    $log_address = '--';
                }
            }
            $new_door_list[$k] = [
                'log_time'=>date('Y-m-d H:i:s',$val['add_time']),
                'log_address'=>$log_address,
                "name" =>$val['nickname']?$val['nickname']:'--',
                "phone" => $val['phone']?$val['phone']:'--',
                'log_status'=>$val['open_status'],
                'log_from_text'=>'开门方式-蓝牙开门',
            ];
        }
        import('@.ORG.merchant_page');

        $list = array_merge($new_face_list,$new_door_list);
        $list = $this->getRsort($list,'log_time');
        $count = count($list);//总条数

        if($page){
            $start=($page-1)*$pagesize;//偏移量，当前页-1乘以每页显示条数
            $article = array_slice($list,$start,$pagesize);
        }else{
            $p = new Page($count,$pagesize,'page');
            $article = array_slice($list,$p->firstRow,$pagesize);
            $data['pagebar'] = $p->show();
            $data['pageCount'] = ceil($count/$pagesize);
        }

        $data['count'] = $count;
        $data['list'] = $article;
        return $data;
    }
    //处理数组倒叙
    public function getRsort($list,$field){
        $finishTime = [];
        foreach ($list as $val) {
            $finishTime[] = $val[$field];
        }
        array_multisort($finishTime,SORT_DESC,$list);
        return $list;
    }
    //获取人员信息
    public function getMemberInfo($where)
    {
        $field = 'm.*,p.code_num';
        $left_join = "as m left join ".C('DB_PREFIX').'house_village_pass as p on `m`.`pass_id`=`p`.`pass_id`';
        $info = $this->join($left_join)->where($where)->field($field)->find();
        return $info;
    }
    //删除人员 (软删除)
    public function delMember($id)
    {
        $info = $this->where(['id'=>$id])->find();

        if($info){
            //先删除人脸
            $face_id = D('House_face_img')->where(['uid'=>$info['uid']])->getField('id',true);
            if($info['open_status'] == 1 && $info['guard']) {
                $guard = explode(',',$info['guard']);
                foreach ($guard as $val){
                    $newArr = explode('-',$val);
                    if($newArr[0] == 0){
                        $location[] = 'public_'.$newArr[1];
                    }else{
                        $location[] = 'floor_'.$newArr[1];
                    }
                }
                $this->face_img_bind($face_id, $info['uid'], $location, $info['bind_id'], $info['village_id'], 1);
            }
            if($info['status'] == 0 || $info['status'] == 1){
                D('house_village_pass')->where(['pass_id'=>$info['pass_id']])->data(['status'=>1])->save();
            }
            //删除人员
            $res = $this->where(['id'=>$id])->data(['status'=>-1])->save();
            return $res;
        }else{
            return 0;
        }
    }
    //用户自主绑定人脸
    public function subBindMember($post,$village_id,$id='')
    {
        $user_info = D('User')->where(['uid'=>$post['uid']])->getField('phone');
        if($user_info){
            $post['come_phone'] = $user_info;
        }
        $map['phone'] = $post['come_phone'];
        $map['village_id'] = $village_id;
        $map['type'] = 6;
        $user_bind = D('House_village_user_bind')->where($map)->field('pigcms_id,uid')->find();
        if(!$user_bind){
            $userInfo = D('User')->where(['phone'=>$post['come_phone']])->field('uid,phone')->find();
            if(!$userInfo)
            {
                $data_user = array(
                    'nickname' 	=> $post['come_name'],
                    'phone' 	=> $post['come_phone'],
                    'source' 	=> 'houseautoreg_villagepass',
                );
                $reg_result = D('User')->autoreg($data_user);
                $uid = $reg_result['uid'];
            }else{
                $uid = $userInfo['uid'];
            }
            $arr = [
                'village_id'=>$village_id,
                'uid'=>$uid,
                'phone'=>$post['come_phone'],
                'name'=>$post['come_name'],
                'type'=>6,
                'usernum'=>rand(1000,9999).'-'.time(),
            ];
            $pigcms_id = D('House_village_user_bind')->add($arr);
        }else{
            if(!$user_bind['uid']){
                $userInfo = D('User')->where(['phone'=>$post['come_phone']])->field('uid,phone')->find();
                if($userInfo){
                    $uid = $userInfo['uid'];
                }else{
                    $data_user = array(
                        'nickname' 	=> $post['come_name'],
                        'phone' 	=> $post['come_phone'],
                        'source' 	=> 'houseautoreg_villagepass',
                    );
                    $reg_result = D('User')->autoreg($data_user);
                    $uid = $reg_result['uid'];
                    D('House_village_user_bind')->where(['pigcms_id'=>$user_bind['pigcms_id']])->data(['uid'=>$uid])->save();
                }
            }else{
                $uid = $user_bind['uid'];
            }
            $pigcms_id = $user_bind['pigcms_id'];
        }
        $code_num = $post['code_num'];
        unset($post['code_num']);
        $post['pass_id']=D('House_village_pass')->where(['code_num'=>$code_num])->getField('pass_id');
        $post['uid'] = $uid;
        $post['bind_id'] = $pigcms_id;
        $post['start_time'] = strtotime($post['start_time']);
        $post['due_time'] =strtotime($post['due_time'].' 23:59:59');
        if($id){
            $post['edit_time'] = time();
            $insert = $this->where(['id'=>$id])->data($post)->save();
        }else{
            $post['add_time']= time();
            $post['village_id'] = $village_id;
            $insert  = $this->add($post);
        }
        D('House_village_pass')->where(['code_num'=>$code_num])->data(['use_status'=>1])->save();
        return $insert;
    }
    //添加人脸图片
    public function  add_face($img_url,$uid)
    {
        $face_img = D('House_face_img');
        $whereFace = [];
        if ($img_url) {
            import('@.ORG.face.word_encryption');
            $word_encryption = new word_encryption();
            $img_url = $word_encryption->text_encryption($img_url);
            $data['status'] = 3;
            $whereFace['status'] = 3;
        } else {
            $data['status'] = 0;
            $whereFace['status'] = 0;
        }
        $whereFace['uid'] = $uid;
        $whereFace['img_url'] = $img_url;
        $faceId = $face_img->where($whereFace)->getField('id');
        if ($faceId) {
            $id = $faceId;
        } else {
            $whereFaceNum = [];
            $whereFaceNum['uid'] = $uid;
            $whereFaceNum['status'] = ['in', [0,3]];
            $face_num = $face_img->where($whereFaceNum)->count();
            $data['uid'] = $uid;
            $data['img_url'] = $img_url;
            $data['add_time'] = time();
            $id = $face_img->add_face_img($data);
            $judgeNum = $face_img->userMaxFaceImgNum;
            if (!$judgeNum) {
                $judgeNum = 5;
            }
            $judgeNum = $judgeNum-1;
            if ($id && $face_num>$judgeNum) {
                $saveDel = [
                    'status' => 1,
                    'del_time' => time(),
                ];
                $whereFaceDel = [
                    'uid' => $uid,
                    'status' => ['in', [0,3]]
                ];
                $limit = $face_num - $judgeNum;
                $del = $face_img->where($whereFaceDel)->order('id ASC')->limit($limit)->save($saveDel);
            }
        }
        return $id;
    }

}