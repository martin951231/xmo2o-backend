<?php

/**
 * Class PassAction
 * 出入证相关接口
 */
class PassAction extends BaseAction{
    //获取出入证码的相关参数
    public function getCodeInfo()
    {
        $code_num = I('code_num');
        $village_id = I('village_id');
        $uid = $this->_uid;
        if(!$code_num){
            $this->returnCodeError('当前二维码异常');
        }
        if($village_id){
            $where['village_id'] = $village_id;
        }
        $where['code_num'] = $code_num;
        $where['status'] = ['neq','-1'];
        $pass_info = D('House_village_pass')->getInfo($where);
        if(!$pass_info){
            $this->returnCodeError('当前二维码不存在');
        }
//        $this->getStatus($pass_info['status']);
        if(in_array($pass_info['status'],[1,2])){
            $this->returnCodeError('无效码，请联系物业');
        }
        if(empty($village_id) && !empty($pass_info) && !empty($pass_info['village_id'])){
            $village_id=$pass_info['village_id'];
        }
        $user_bind = D('House_village_user_bind')->where(['village_id'=>$village_id,'uid'=>$uid,'type'=>6])->field('pigcms_id,phone')->find();

        $map['status'] = ['neq','-1'];
        $map['village_id'] = $village_id;
        $map['pass_id'] = $pass_info['pass_id'];
        $info = D('House_village_pass_bind_member')->where($map)->order('id desc')->find();
        $new_arr = [];
        if($info){
            $info['start_time'] = date('Y-m-d H:i',$info['start_time']);
            $info['due_time_text'] = date('Y-m-d H:i',$info['due_time']);
            if($info['uid'] != $uid){
                $this->returnCodeError('无效码，请联系物业');//当前二维码已被使用1
            }
            if($info['due_time']+86400<time()){
                $this->returnCodeError('无效码，请联系物业');
            }
            if ($info['status'] == 1) {
                $this->returnCodeError('您的申请正在审核中，可联系物业工作人员');
            }
            $where_face['uid'] = $uid;
            $where_face['status'] = ['neq',1];
//                $where_face['del_time'] = ['eq',''];
            $face_count = D('House_face_img')->where($where_face)->count();
            if($face_count){
                $info['face_entry'] = '已上传';
            }else{
                $info['face_entry'] = '未上传';
            }
            //出入证申请被拒绝，返回提交的审核信息
            if ($info['status'] == 3) {
                $info['reason'] = '您的申请未通过：' . $info['reason'];
                $new_arr['info'] = $info;
            }
            //状态正常返回出入证
            if($info['status'] == 0){
                $new_arr['info'] = $this->passInfo($info['id'],$info['village_id']);
            }


        }else{
            //查询是否绑定其他出入证码
            $map = [];
            $map['m.status'] = ['neq','-1'];
            $map['m.village_id'] = $village_id;
            $map['m.due_time'] = ['gt',strtotime(date('Y-m-d',time()))];
            $map['m.uid'] = $uid;
            $map['m.come_phone'] = $user_bind['phone'];
            $map['m.bind_id'] = $user_bind['pigcms_id'];
//            $map['b.status'] = ['in',[0,1]];
            $left_join = "as m left join ".C('DB_PREFIX').'house_village_pass as p on `m`.`pass_id`=`p`.`pass_id`';
            $info_user = D('House_village_pass_bind_member')->join($left_join)->field('m.*')->where($map)->order('m.id desc')->find();
            if($info_user){
                $info_user['start_time_text'] = date('Y-m-d H:i',$info_user['start_time']);
                $info_user['due_time_text'] = date('Y-m-d H:i',$info_user['due_time']);
                if ($info_user['status'] == 0) {
                    $this->returnCodeError('您当前使用的出入码未到期');
                }
                if ($info_user['status'] == 3) {
                    $this->returnCodeError('您当前申请出入码未通过审核，请扫原码重新提交');
                }
                if ($info_user['status'] == 1) {
                    $this->returnCodeError('您当前申请出入码正在审核中，请联系管理员');
                }
            }
            if($pass_info['use_status'] == 1){
                $map = [];
                $map['status'] = ['neq','-1'];
                $map['village_id'] = $pass_info['village_id'];
                $map['pass_id'] = $pass_info['pass_id'];
                $info = D('House_village_pass_bind_member')->where($map)->order('id desc')->find();
                if($info['uid'] != $uid){
                    $this->returnCodeError('无效码，请联系物业');//当前二维码已被使用1
                }
                if($info['due_time']+86400<time()){
                    $this->returnCodeError('无效码，请联系物业');
                }
                if ($info['status'] == 1) {
                    $this->returnCodeError('您的申请正在审核中，可联系物业工作人员');
                }
                $where_face['uid'] = $uid;
                $where_face['status'] = ['neq',1];
//                $where_face['del_time'] = ['eq',''];
                $face_count = D('House_face_img')->where($where_face)->count();
                if($face_count){
                    $info['face_entry'] = '已上传';
                }else{
                    $info['face_entry'] = '未上传';
                }
                //出入证申请被拒绝，返回提交的审核信息
                if ($info['status'] == 3) {
                    $info['reason'] = '您的申请未通过：' . $info['reason'];
                    $new_arr['info'] = $info;
                }
                //状态正常返回出入证
                if($info['status'] == 0){
                    $new_arr['info'] = $this->passInfo($info['id'],$info['village_id']);
                }
                $this->returnCodeError('无效码，请联系物业');
            }else{
                $where_face['uid'] = $uid;
                $where_face['status'] = ['neq',1];
//                $where_face['del_time'] = ['eq',''];
                $face_count = D('House_face_img')->where($where_face)->count();
                if($face_count){
                    $pass_info['face_entry'] = '已上传';
                }else{
                    $pass_info['face_entry'] = '未上传';
                }
                $user_info = D('User')->where(['uid'=>$uid])->field('uid,nickname,phone')->find();

                $new_arr['pass_info'] =  $pass_info;
                $new_arr['pass_info']['name'] = $user_info['nickname'];
                $new_arr['pass_info']['phone'] = $user_info['phone'];
                $new_arr['info'] =  '';

                $db_house_village = D('House_village');
                $app_version = intval($_POST['app_version']);
                $deviceId = trim($_POST['Device-Id']);
                $param = [
                    'pageName' => 'faceAccessControl',
                    'isAll' => true,
                    'urlParam' => 'type=1&village_id='.$village_id,
                ];
                $faceAccessControlUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
                $new_arr['pass_info']['face_url'] =  $faceAccessControlUrl;
            }
        }
        $open_door_type = D('House_village_about_config')->where(['village_id' => $village_id])->getField('open_door_type');
        $new_arr['open_door_type'] = $open_door_type; //1人脸开门 2远程开门
        $this->returnCode(0,$new_arr);
    }
    //处理状态
    public function getStatus($status)
    {
        switch ($status){
            case '1':
                $this->returnCodeError('当前二维码被禁用');
                break;
            case '2':
                $this->returnCodeError('当前二维码过期');
                break;
        }
    }
    //提交出入证信息
    public function subPassUser()
    {
        $post = I('post.');
        $village_id = $post['village_id'];
        $uid = $this->_uid;
        if(!$post['code_num']){
            $this->returnCodeError('二维码ID异常');
        }
        if(!$post['name']){
            $this->returnCodeError('请填写姓名');
        }
        if(!$post['phone']){
            $this->returnCodeError('请填写电话');
        }
        if(!$post['start_time']){
            $this->returnCodeError('请选择出入开始时间');
        }elseif(strtotime($post['start_time']) < strtotime( date('Y-m-d',time()) ) ){
            $this->returnCodeError('出入开始时间不得小于当前时间');
        }
        if(!$post['end_time']){
            $this->returnCodeError('请选择出入到期时间');
        }elseif(strtotime($post['end_time'].' 23:59:59')<time()){
            $this->returnCodeError('出入到期时间不得小于当前时间');
        }
        if(strtotime($post['start_time']) > strtotime($post['end_time'].' 23:59:59')){
            $this->returnCodeError('出入开始时间不得小于出入到期时间');
        }
        if(!$post['remark']){
            $this->returnCodeError('请输入出入理由');
        }
        //过滤
        $map['code_num'] = $post['code_num'];
        $map['status'] = ['neq',-1];
        $pass_info = D('House_village_pass')->where(['code_num'=>$post['code_num']])->field('pass_id,code_num,use_status,status')->find();
        if(!$pass_info){
            $this->returnCodeError('二维码不存在');
        }
        if($pass_info['use_status'] == 1){
            $this->returnCodeError('二维码已被绑定');
        }
        if($pass_info['status'] == 1){
            $this->returnCodeError('无效二维码');
        }
        if($pass_info['use_status'] == 2 || $pass_info['status'] == 2){
            $this->returnCodeError('二维码已被绑定');
        }
        $pass_bind_member = D('House_village_pass_bind_member')->where(['pass_id'=>$pass_info['pass_id']])->find();
        if($pass_bind_member){
            $this->returnCodeError('二维码已被绑定');
        }
        $map = [];
        $map['phone'] = $post['phone'];
        $map['village_id'] = $village_id;
        $map['type'] = 6;
        $map['uid'] = $uid;
        $user_bind = D('House_village_user_bind')->where($map)->field('pigcms_id,uid')->find();
        $pigcms_id = $user_bind['pigcms_id'];
        if(!$user_bind){
            $arr = [
                'village_id'=>$village_id,
                'uid'=>$uid,
                'phone'=>$post['come_phone'],
                'name'=>$post['come_name'],
                'type'=>6,
                'usernum'=>rand(1000,9999).'-'.time(),
            ];
            $pigcms_id = D('House_village_user_bind')->add($arr);
        }
        $open_door_type = D('House_village_about_config')->where(['village_id' => $village_id])->getField('open_door_type');
        if($open_door_type == 1) { //1人脸开门 2远程开门
            $face_img = D('House_face_img')->where(['uid'=>$this->_uid])->count();
            if(!$face_img){
                $this->returnCodeError('请上传人脸');
            }
        }
        $data = [
            'come_name'=>$post['name'],
            'come_phone'=>$post['phone'],
            'start_time'=>$post['start_time'],
            'due_time'=>$post['end_time'],
            'remark'=>$post['remark'],
            'code_num'=>$post['code_num'],
            'status' => 1,//审核
            'uid'=>$uid,
            'bind_id'=>$pigcms_id,
        ];
        $res = D('House_village_pass_bind_member')->subBindMember($data,$village_id,$post['id']);
        if($res){
            $this->returnCode(0,$res,'您的申请正在审核中，可联系物业工作人员');
        }else{
            $this->returnCodeError('提交失败');
        }
    }
    //绑定人脸
    public function bindFace()
    {
        $uid = $this->_uid;
        if(!$uid){
            $this->returnCodeError('登录异常');
        }
        $img_path = I('img_path');
        $village_id = I('village_id');
        $app_type = I('app_type');
        if(!$village_id){
            $this->returnCodeError('传参异常');
        }
        $open_door_type = D('House_village_about_config')->where(['village_id' => $village_id])->getField('open_door_type');
        if($open_door_type == 1) { //1人脸开门 2远程开门
            if($app_type != 'packapp'){
                $img_path = explode(',',$img_path);
            }
        }else{
            $this->returnCodeError('远程开门不需要上传人脸');
            $img_path = [];
        }
        $House_village_pass_bind_member = D('House_village_pass_bind_member');
        $res = 0;
        $face_img_id = [];
        if($img_path && count($img_path)>0) {
            $face_id = D('House_face_img')->where(['uid'=>$uid])->getField('id',true);
            D('House_face_img')->where(['id'=>['in',$face_id]])->data(['status'=>1])->save();
            foreach ($img_path as $key=>$val){
                $res = $House_village_pass_bind_member->add_face($val,$uid);
                $face_img_id[] = $res;
            }
        }
        if($res){
            $this->returnCode(0,['info'=>$face_img_id],'保存成功');
        }else{
            $this->returnCodeError('保存失败');
        }
    }
    //出入证页面
    public function passInfo($id,$village_id)
    {
//        if (empty($this->_uid)) {
//            $this->returnCode('20000009');
//        }
        $uid = $this->_uid;
        $db_house_village = D('House_village');
        $app_version = intval($_POST['app_version']);
        $deviceId = trim($_POST['Device-Id']);
        $param = [
            'pageName' => 'visitorInfo',
            'isAll' => true,
            'urlParam' => 'village_id='.$village_id.'&id='.$id,
        ];
        $visitorInfoUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
        $qrcode_url = $visitorInfoUrl;
        if(empty($id)){
            $this->returnCodeError('出入证参数异常');
        }
        $database_house_village_pass_bind_member = D('House_village_pass_bind_member');
        $res = $database_house_village_pass_bind_member->where(array('id'=>$id))->find();
        if(empty($res)){
            $this->returnCodeError('出入证内容不存在');
        }
        $arr = [];
        if($res['uid'] != $uid){
            //处理绑定人脸开门
//            D('House_village_bind_share')->bind_share($res['door_share_id'], $this->_uid);
            $arr['edit_phone_status'] = true;
        }else{
            $arr['edit_phone_status'] = false;
        }
        $where_face['uid'] = $res['uid'];
        $where_face['status'] = ['neq',1];
//        $where_face['del_time'] = ['eq',''];
        $face_count = D('House_face_img')->where($where_face)->count();
        if($face_count){
            $arr['face_entry'] = '已上传';
        }else{
            $arr['face_entry'] = '未上传';
        }
        $pigcms_id = $res['bind_id'];
        if(!$pigcms_id){
            $this->returnCodeError('出入证未关联小区用户身份');
        }
        $house_village_info = M('House_village')->where(array('village_id' => $res['village_id']))->field('village_address,long,lat,village_name')->find();

        $arr['village_address'] = $house_village_info['village_address'];//社区地址
        $arr['village_lng'] = $house_village_info['long'];//社区经纬度
        $arr['village_lat'] = $house_village_info['lat'];//社区经纬度
        $arr['village_name'] = $house_village_info['village_name'];//小区名称

        if ($arr['village_lat'] && $arr['village_lng']) {
            $longlatClass = new longlat();
            $longlat = $longlatClass->baiduToGcj02($arr['village_lat'],$arr['village_lng']);
            $arr['village_lng_gcj02'] = $longlat['lng'];//腾讯地图的坐标 经纬度
            $arr['village_lat_gcj02'] = $longlat['lat'];//腾讯地图的坐标 经纬度
        }

        $arr['id'] = $res['id'];//id
        $arr['come_name'] = $res['come_name']?$res['come_name']:'';//出入人员姓名
        $arr['come_phone'] = $res['come_phone']?$res['come_phone']:'';//出入人员手机号
        if ($res['start_time']) {
            $arr['start_time'] = date('Y-m-d H:i',$res['start_time']);//出入开始时间
        } else {
            $arr['start_time'] = '';//出入开始时间
        }
        $arr['due_time'] = date('Y-m-d H:i',$res['due_time']);//出入证到期时间
        $arr['open_status'] = $res['open_status'];//是否有开门权限 0有  1无
        $arr['qrcode_url'] = $this->config['site_url'].U('get_own_qrcode',array('qrCon'=>urlencode(htmlspecialchars_decode($qrcode_url))));
        $arr['qrcode_path_url'] = $qrcode_url;
        $arr['pass_id'] = $res['pass_id'];
        $arr['status'] = $res['status'];
        //访客开门方式 （1人脸开门 2远程开门)
        $open_door_type = D('House_village_about_config')->where(['village_id' => $res['village_id']])->getField('open_door_type');
        $arr['open_door']['open_door_type'] = (int)$open_door_type;//1人脸开门 2远程开门
        //人脸门禁
        $face_device = D('House_face_device')->where(['village_id' => $res['village_id'],'device_status'=>1])->count();
        //蓝牙门禁
        $door_bluetooth = D('house_village_door')->where(['village_id' => $res['village_id'],'door_status'=>1])->count();
        //如果访客开门方式设置人脸开门且有蓝牙门禁时
        if( $open_door_type==1){
            if($door_bluetooth && $face_device){
                $arr['open_door']['face_device_type'] =2; //蓝牙开门、人脸开门都有 (有去开门按钮)
            }elseif($face_device){
                $arr['open_door']['face_device_type'] =3;//没有蓝牙开门、只有人脸开门（录入人脸）
            }else{
                $arr['open_door']['face_device_type'] =0;//蓝牙开门、人脸开门都没有
            }
        }else{
            if($face_device){
                $arr['open_door']['face_device_type'] =1; //原有远程开门
            }else{
                $arr['open_door']['face_device_type'] =0;//蓝牙开门、人脸开门都没有
            }
        }
        return $arr;
//        $this->returnCode(0, $arr);
    }
    public function get_own_qrcode(){
        header('Content-Type: image/png');
        ob_clean();
        $qrCon = $_GET['qrCon'];
        import('@.ORG.phpqrcode');
        $size = $_GET['size'] ? $_GET['size']: 10;
        $is_down = $_GET['down'] ? true: false;

        QRcode::png(htmlspecialchars_decode(urldecode($qrCon)),false,0,$size,1,$is_down);
    }
    //出行记录
    public function tripRecord()
    {
        $pass_id = I('pass_id');
        $village_id = I('village_id');
        $page = I('page','1');
        if(!$pass_id || !$village_id){
            $this->returnCodeError('必传参数不能为空');
        }
        $list = D('House_village_pass_bind_member')->getOpenDoorRecord($pass_id,$village_id,$page);
        $this->returnCode(0, $list);
    }
    //扫码查看信息
    public function getPassMemberInfo()
    {
        $id = I('id');
        if(!$id){
            $this->returnCodeError('参数异常');
        }
        $database_house_village_pass_bind_member = D('House_village_pass_bind_member');
        $info = $database_house_village_pass_bind_member->where(array('id'=>$id))->find();
        if(empty($info)){
            $this->returnCodeError('出入证内容不存在');
        }
        $info['start_time'] = date('Y-m-d H:i',$info['start_time']);
        $info['due_time'] = date('Y-m-d H:i',$info['due_time']);
        $data['info'] = $info;
        $this->returnCode(0, $data);
    }
}