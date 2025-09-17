 <?php

/**
 * 设备相关
 */
class FaceDeviceAction extends BaseAction {

    public function deviceInfoPage() {
        $device_sn = I('device_sn');
        $db_house_face_device = D('House_face_device');
        $where_device = [
            'device_sn' => $device_sn,
            'is_del' => 0,
        ];
        $faceDevice['device_type'] = 5;
        $faceDevice = $db_house_face_device->where($where_device)->find();
        if (empty($faceDevice)) {
            $this->error('对应设备不存在');
            exit;
        }
        switch ($faceDevice['device_type']) {
            case 5:
                set_time_limit(0);
                ini_set('memory_limit', '2048M');
                $aboutInfo = D('House_face_device_log')->deviceAboutInfo($device_sn);
                break;

        }
        $this->assign('aboutInfo',$aboutInfo);
        $this->display();
    }

    public function housePersonPage() {
        $pigcms_id = I('personNo');
        $db_house_village_user_bind = D('House_village_user_bind');
        $where_bind = [
            'pigcms_id' => $pigcms_id,
        ];
        $userBind = $db_house_village_user_bind->where($where_bind)->find();
        if (empty($userBind)) {
            $this->error('对应人员不存在');
            exit;
        }
        // 暂时只是查询A5
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $aboutInfo = D('House_face_device_log')->personAboutInfo($pigcms_id,trim($_GET['type']));

        $this->assign('aboutInfo',$aboutInfo);
        $this->display();
    }


    // 直接同步用户
    public function synDeviceUser() {
        $pigcms_id = I('pigcms_id','', 'intval');
        $d_house_village_user_bind = M('House_village_user_bind');
        $where = array(
            'pigcms_id' => $pigcms_id,
            'village_id' => $this->house_session['village_id'],
        );
        $bindUserInfo = $d_house_village_user_bind->where($where)->find();
        if(empty($bindUserInfo)){
            exit(json_encode(array('errorCode'=>1001,'errorMsg'=>'用户不存在！')));
        }
        if (empty($bindUserInfo['uid'])) {
            if(!empty($bindUserInfo['phone'])){
                $bindUserInfo['uid'] = M('User')->where(['phone' => $bindUserInfo['phone']])->getField('uid');

                if(empty($bindUserInfo['uid'])){
                    $data_user = array(
                        'nickname' 	=> $bindUserInfo['name'],
                        'phone' 	=> $bindUserInfo['phone'],
                        'source' 	=> 'houseautoreg',
                    );
                    $reg_result = D('User')->autoreg($data_user);
                    if($reg_result['error_code']){
                        exit(json_encode(array('errorCode'=>1, 'msg'=>$reg_result['error_code'])));
                    }
                    $bindUserInfo['uid'] = $reg_result['uid'];
                }

                if(!empty($bindUserInfo['uid'])){
                    $d_house_village_user_bind->where($where)->data(['uid' => $bindUserInfo['uid']])->save();
                }else{
                    exit(json_encode(array('errorCode'=>1001,'errorMsg'=>'请先绑定为平台用户！')));
                }
            }
        }
        $info = D('House_face_img')->house_user_face_device_info($bindUserInfo['uid'],$pigcms_id,false,[],true,true);
        if($info && (!isset($info['err_msg']) || !$info['err_msg'])){
            exit(json_encode(array('errorCode'=>0,'errorMsg'=>'操作成功！')));
        } elseif (isset($info['err_msg']) && $info['err_msg']) {
            exit(json_encode(array('errorCode'=>1001,'errorMsg'=>$info['err_msg'])));
        }else{
            exit(json_encode(array('errorCode'=>1001,'errorMsg'=>'没有可同步设备！')));
        }
    }

    // 同步删除
    public function sysDelDeviceUser() {
        $pigcms_id = I('pigcms_id','', 'intval');
        $d_house_village_user_bind = M('House_village_user_bind');
        $where = array(
            'pigcms_id' => $pigcms_id,
        );
        $bindUserInfo = $d_house_village_user_bind->where($where)->find();
        if(!empty($bindUserInfo) && $this->house_session['village_id']!=$bindUserInfo['village_id']){
            exit(json_encode(array('errorCode'=>1001,'errorMsg'=>'用户不存在！')));
        }
        if (!empty($bindUserInfo)&&in_array($bindUserInfo['status'],[1,2])) {
            fdump_api(['同步删除[用户当前状态正常不可同步删除]'.__LINE__,$_SERVER,$_GET,$_POST],'FaceDevice/errSysDelDeviceUseLog',true);
            exit(json_encode(array('errorCode'=>1001,'errorMsg'=>'用户当前状态正常不可同步删除！')));
        } elseif (!empty($bindUserInfo) && $bindUserInfo['parent_id']) {
            $where = array(
                'pigcms_id' => $bindUserInfo['parent_id'],
            );
            $parentUserInfo = $d_house_village_user_bind->where($where)->find();
            if (!in_array($bindUserInfo['status'],[0,3,4]) && !in_array($parentUserInfo['status'],[3,4])) {
                fdump_api(['同步删除[用户当前不可同步删除]'.__LINE__,$_SERVER,$_GET,$_POST],'FaceDevice/errSysDelDeviceUseLog',true);
                exit(json_encode(array('errorCode'=>1001,'errorMsg'=>'用户当前不可同步删除！')));
            }
        }
        // 看下是处于删除状态还是禁用状态  禁用状态移除权限组   删除状态先移除后删除
        if (empty($bindUserInfo)) {
            // 调用移除方法
            $del = D('Face_door_a5_service')->removeMember($pigcms_id);
            $del1 = D('Face_door_a5_service')->deleteMember($pigcms_id);
        } elseif ($bindUserInfo && in_array($bindUserInfo['status'],[0,3,4])) {
            // 调用移除方法
            $del = D('Face_door_a5_service')->removeMember($pigcms_id);
            $del1 = D('Face_door_a5_service')->deleteMember($pigcms_id);
        } elseif ($parentUserInfo && in_array($parentUserInfo['status'],[3,4])) {
            // 调用移除方法
            $del = D('Face_door_a5_service')->removeMember($pigcms_id);
            $del1 = D('Face_door_a5_service')->deleteMember($pigcms_id);
        } else {
            exit(json_encode(array('errorCode'=>1001,'errorMsg'=>'用户当前不可同步删除！')));
        }
        fdump_api(['同步删除'.__LINE__,$del,$del1],'FaceDevice/SysDelDeviceUseLog',true);
        if($del){
            exit(json_encode(array('errorCode'=>0,'errorMsg'=>'操作成功！')));
        }else{
            exit(json_encode(array('errorCode'=>1001,'errorMsg'=>'操作失败！')));
        }
    }

    public function floorSysUser() {
        // 网址/appapi.php?c=Comm_group_expansion&a=floorSysUser&personNo=10563&startNo=10541  对应单元起始物业时间没有同步最新的重新同步
        $startNo = I('startNo');
        $pigcms_id = I('personNo',10513);
        $floor_id = I('floor_id',289);
        $type = I('type');
        $village_id = $this->house_session['village_id'];
        if (!$village_id) {
            $village_id = 10;
        }
        $db_house_village_user_bind = D('House_village_user_bind');
        $where = [];
        $where['village_id'] = $village_id;
        if ($startNo && $pigcms_id) {
            $where['pigcms_id'] = ['between', [$startNo,$pigcms_id]];
        } else if ($pigcms_id) {
            $where['pigcms_id'] = ['elt', $pigcms_id];
        }
        if ($floor_id) {
            $where['floor_id'] = $floor_id;
        }
        if ($type==4) {
            $where['type'] = 4;
        }
        $where['status'] = 1;
        $list = $db_house_village_user_bind->where($where)->select();
        $nowTime = 1664553600;
        $dataArr = [];
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $data = [];
        foreach ($list as $item) {
            sleep(1);
            $personMsg = D('Face_door_a5_service')->getMember($item['pigcms_id']);
            dump($personMsg);
            if ($personMsg['data']) {
                $personInfo = $personMsg['data'];
            }
            if (isset($personInfo['validEndTime'])&&$personInfo['validEndTime']) {
                $judgment = strtotime($personInfo['validEndTime']);
                if ($judgment<=$nowTime) {
                    D('House_face_img')->house_user_face_device_info($item['uid'],$item['pigcms_id'],false,[],true,true);
                    $dataArr[] = $item['pigcms_id'];
                }
            }
            $data = $item;
        }
        dump($dataArr);
        dump($data);
        dump($db_house_village_user_bind->_sql());
    }
}